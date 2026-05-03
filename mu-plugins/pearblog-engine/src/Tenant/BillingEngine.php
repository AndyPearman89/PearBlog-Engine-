<?php
/**
 * Tenant Billing Engine – usage metering and Stripe billing integration.
 *
 * Tracks per-tenant AI token consumption and image generation costs.
 * At the end of each billing cycle, triggers Stripe usage reporting
 * (Stripe Billing metered usage) or sends a usage alert email.
 *
 * Features:
 *  - AI token usage metering per site (WordPress multisite or single-site
 *    with tenant context via TenantContext).
 *  - Configurable monthly quota with over-quota alerts.
 *  - Stripe Billing integration: reports usage to a Stripe subscription item.
 *  - Usage history stored per-site for the last 12 billing periods.
 *  - REST endpoint for tenant self-service usage summary.
 *
 * Configuration (WP options):
 *   pearblog_billing_enabled          – (bool) enable billing engine
 *   pearblog_billing_stripe_secret    – Stripe secret key
 *   pearblog_billing_stripe_item_id   – Stripe subscription item ID
 *   pearblog_billing_monthly_quota    – max AI cents budget per month
 *   pearblog_billing_alert_threshold  – fraction (0.8 = alert at 80% usage)
 *
 * @package PearBlogEngine\Tenant
 */

declare(strict_types=1);

namespace PearBlogEngine\Tenant;

/**
 * Meters AI usage and integrates with Stripe Billing.
 */
class BillingEngine {

	/** WP option keys. */
	public const OPTION_ENABLED    = 'pearblog_billing_enabled';
	public const OPTION_STRIPE_KEY = 'pearblog_billing_stripe_secret';
	public const OPTION_ITEM_ID    = 'pearblog_billing_stripe_item_id';
	public const OPTION_QUOTA      = 'pearblog_billing_monthly_quota';
	public const OPTION_THRESHOLD  = 'pearblog_billing_alert_threshold';

	/** Usage option key (current billing period). */
	public const OPTION_USAGE_CURRENT = 'pearblog_billing_usage_current';

	/** Usage history option key (array of monthly snapshots). */
	public const OPTION_USAGE_HISTORY = 'pearblog_billing_usage_history';

	/** Default monthly quota: $10 in cents. */
	public const DEFAULT_QUOTA = 1000;

	/** Default alert threshold: 80%. */
	public const DEFAULT_THRESHOLD = 0.8;

	/** Cron hook for monthly billing cycle reset. */
	private const CRON_HOOK_RESET  = 'pearblog_billing_cycle_reset';

	/** Cron hook for daily usage check / Stripe report. */
	private const CRON_HOOK_REPORT = 'pearblog_billing_daily_report';

	/** Stripe usage record API endpoint. */
	private const STRIPE_USAGE_URL = 'https://api.stripe.com/v1/subscription_items/%s/usage_records';

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'pearblog_revenue_tracked', [ $this, 'on_ai_cost_incurred' ], 10, 3 );
		add_action( 'pearblog_pipeline_completed', [ $this, 'record_generation_event' ], 10, 1 );
		add_action( self::CRON_HOOK_RESET, [ $this, 'reset_billing_cycle' ] );
		add_action( self::CRON_HOOK_REPORT, [ $this, 'report_daily_usage' ] );
		add_action( 'init', [ $this, 'maybe_schedule' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Schedule crons if not already scheduled.
	 */
	public function maybe_schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK_RESET ) ) {
			// First day of next month at midnight.
			$next = strtotime( 'first day of next month midnight' );
			wp_schedule_event( $next, 'monthly', self::CRON_HOOK_RESET );
		}

		if ( ! wp_next_scheduled( self::CRON_HOOK_REPORT ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK_REPORT );
		}
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/billing/usage', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_usage_summary' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/billing/history', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_usage_history' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );
	}

	// -----------------------------------------------------------------------
	// Usage tracking
	// -----------------------------------------------------------------------

	/**
	 * Record AI cost incurred by a pipeline run.
	 *
	 * @param int    $post_id       Post ID.
	 * @param float  $amount_cents  AI cost in USD cents.
	 * @param string $source        Revenue source type.
	 */
	public function on_ai_cost_incurred( int $post_id, float $amount_cents, string $source ): void {
		// We care about generation cost, not revenue here – hook is used for metering.
		$this->add_usage( $amount_cents );
	}

	/**
	 * Record a generation event (called on pipeline completion).
	 *
	 * @param int $post_id Published post ID.
	 */
	public function record_generation_event( int $post_id ): void {
		// Increment generation count.
		$usage = $this->get_current_usage();
		$usage['generations']++;
		update_option( self::OPTION_USAGE_CURRENT, $usage );
	}

	/**
	 * Add usage in cents to current billing period.
	 *
	 * @param float $cents Amount in USD cents.
	 */
	public function add_usage( float $cents ): void {
		$usage              = $this->get_current_usage();
		$usage['cost_cents'] += $cents;
		update_option( self::OPTION_USAGE_CURRENT, $usage );

		$quota     = (float) get_option( self::OPTION_QUOTA, self::DEFAULT_QUOTA );
		$threshold = (float) get_option( self::OPTION_THRESHOLD, self::DEFAULT_THRESHOLD );

		// Fire alert if threshold exceeded.
		if ( $quota > 0 && ( $usage['cost_cents'] / $quota ) >= $threshold ) {
			$this->send_quota_alert( $usage['cost_cents'], $quota );
		}
	}

	/**
	 * Get current billing period usage.
	 *
	 * @return array{cost_cents: float, generations: int, period_start: int}
	 */
	public function get_current_usage(): array {
		$default = [
			'cost_cents'   => 0.0,
			'generations'  => 0,
			'period_start' => strtotime( 'first day of this month midnight' ),
		];

		$stored = get_option( self::OPTION_USAGE_CURRENT, $default );
		return is_array( $stored ) ? array_merge( $default, $stored ) : $default;
	}

	/**
	 * Get usage as a percentage of the monthly quota.
	 *
	 * @return float Usage percentage (0–100+).
	 */
	public function get_usage_percentage(): float {
		$usage = $this->get_current_usage();
		$quota = (float) get_option( self::OPTION_QUOTA, self::DEFAULT_QUOTA );
		return $quota > 0 ? round( ( $usage['cost_cents'] / $quota ) * 100, 1 ) : 0.0;
	}

	// -----------------------------------------------------------------------
	// Billing cycle management
	// -----------------------------------------------------------------------

	/**
	 * Reset current billing cycle (called by monthly cron).
	 */
	public function reset_billing_cycle(): void {
		$current_usage = $this->get_current_usage();

		// Archive to history.
		$history   = (array) get_option( self::OPTION_USAGE_HISTORY, [] );
		$history[] = array_merge( $current_usage, [ 'period_end' => time() ] );

		// Keep last 12 months.
		if ( count( $history ) > 12 ) {
			$history = array_slice( $history, -12 );
		}

		update_option( self::OPTION_USAGE_HISTORY, $history );

		// Reset current period.
		update_option( self::OPTION_USAGE_CURRENT, [
			'cost_cents'   => 0.0,
			'generations'  => 0,
			'period_start' => time(),
		] );

		/**
		 * Action: pearblog_billing_cycle_reset
		 *
		 * @param array<string,mixed> $previous_usage Previous period usage data.
		 */
		do_action( 'pearblog_billing_cycle_reset', $current_usage );
	}

	// -----------------------------------------------------------------------
	// Stripe integration
	// -----------------------------------------------------------------------

	/**
	 * Report daily usage to Stripe (called by daily cron).
	 */
	public function report_daily_usage(): void {
		if ( ! (bool) get_option( self::OPTION_ENABLED, false ) ) {
			return;
		}

		$stripe_key = (string) get_option( self::OPTION_STRIPE_KEY, '' );
		$item_id    = (string) get_option( self::OPTION_ITEM_ID, '' );

		if ( '' === $stripe_key || '' === $item_id ) {
			return;
		}

		$usage = $this->get_current_usage();

		// Report usage in whole cents to Stripe.
		$this->stripe_report_usage( $item_id, (int) $usage['cost_cents'], $stripe_key );
	}

	/**
	 * Report usage to Stripe Billing metered usage API.
	 *
	 * @param string $item_id    Stripe subscription item ID.
	 * @param int    $quantity   Usage quantity (cents).
	 * @param string $secret_key Stripe secret key.
	 * @return bool
	 */
	public function stripe_report_usage( string $item_id, int $quantity, string $secret_key ): bool {
		$url = sprintf( self::STRIPE_USAGE_URL, $item_id );

		$response = wp_remote_post( $url, [
			'headers' => [
				'Authorization' => 'Bearer ' . $secret_key,
			],
			'body' => [
				'quantity'  => $quantity,
				'timestamp' => time(),
				'action'    => 'set',
			],
			'timeout' => 15,
		] );

		$success = ! is_wp_error( $response )
			&& 200 === wp_remote_retrieve_response_code( $response );

		if ( ! $success ) {
			error_log( 'PearBlog Billing: Stripe usage report failed for item ' . $item_id );
		}

		return $success;
	}

	// -----------------------------------------------------------------------
	// Alerts
	// -----------------------------------------------------------------------

	/**
	 * Send quota usage alert email to the site admin.
	 *
	 * @param float $current_cents Current usage in cents.
	 * @param float $quota_cents   Monthly quota in cents.
	 */
	private function send_quota_alert( float $current_cents, float $quota_cents ): void {
		$key      = 'pearblog_billing_alert_sent_' . date( 'Y-m' );
		if ( get_transient( $key ) ) {
			return; // Already sent this month.
		}

		$pct   = round( ( $current_cents / $quota_cents ) * 100, 1 );
		$admin = get_option( 'admin_email' );

		wp_mail(
			$admin,
			'[PearBlog] AI Budget Alert – ' . $pct . '% of quota used',
			sprintf(
				"Your PearBlog Engine AI budget is at %s%%.\n\nCurrent: $%s\nQuota: $%s\n\nLog in to manage your budget: %s",
				$pct,
				number_format( $current_cents / 100, 2 ),
				number_format( $quota_cents / 100, 2 ),
				admin_url( 'admin.php?page=pearblog-engine' )
			)
		);

		set_transient( $key, true, MONTH_IN_SECONDS );

		/**
		 * Action: pearblog_billing_quota_alert
		 *
		 * @param float $current_cents Current AI spend in cents.
		 * @param float $quota_cents   Monthly quota in cents.
		 */
		do_action( 'pearblog_billing_quota_alert', $current_cents, $quota_cents );
	}

	// -----------------------------------------------------------------------
	// REST callbacks
	// -----------------------------------------------------------------------

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_usage_summary( \WP_REST_Request $request ): \WP_REST_Response {
		$usage = $this->get_current_usage();
		$quota = (float) get_option( self::OPTION_QUOTA, self::DEFAULT_QUOTA );

		return new \WP_REST_Response( [
			'cost_cents'    => $usage['cost_cents'],
			'cost_usd'      => round( $usage['cost_cents'] / 100, 2 ),
			'generations'   => $usage['generations'],
			'quota_cents'   => $quota,
			'quota_usd'     => round( $quota / 100, 2 ),
			'usage_pct'     => $this->get_usage_percentage(),
			'period_start'  => $usage['period_start'],
		], 200 );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_usage_history( \WP_REST_Request $request ): \WP_REST_Response {
		$history = (array) get_option( self::OPTION_USAGE_HISTORY, [] );
		return new \WP_REST_Response( [ 'history' => $history ], 200 );
	}

	/**
	 * Permission callback.
	 */
	public function admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}
}

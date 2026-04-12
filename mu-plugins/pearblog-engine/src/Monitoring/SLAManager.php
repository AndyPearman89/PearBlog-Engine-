<?php
/**
 * SLA Manager — configurable per-site SLA targets, breach detection, and reporting.
 *
 * Site administrators define SLA targets for uptime, pipeline success rate, API
 * response time, and content generation cost.  The manager checks live metrics
 * against those targets and fires alerts on breach.  A monthly SLA report is
 * auto-generated and optionally e-mailed.
 *
 * Storage / options:
 *   pearblog_sla_targets       – JSON-encoded SLA target map
 *   pearblog_sla_status        – JSON-encoded current computed status
 *   pearblog_sla_history       – JSON-encoded monthly history (last 12 months)
 *   pearblog_sla_report_email  – E-mail address for monthly reports (empty = disabled)
 *
 * Cron hooks:
 *   pearblog_sla_check  – hourly SLA evaluation
 *   pearblog_sla_report – monthly report generation (first of the month)
 *
 * @package PearBlogEngine\Monitoring
 */

declare(strict_types=1);

namespace PearBlogEngine\Monitoring;

/**
 * Evaluates SLA targets and produces breach alerts and monthly reports.
 */
class SLAManager {

	/** WP option keys. */
	public const OPTION_TARGETS      = 'pearblog_sla_targets';
	public const OPTION_STATUS       = 'pearblog_sla_status';
	public const OPTION_HISTORY      = 'pearblog_sla_history';
	public const OPTION_REPORT_EMAIL = 'pearblog_sla_report_email';

	/** Cron hook names. */
	public const CRON_CHECK  = 'pearblog_sla_check';
	public const CRON_REPORT = 'pearblog_sla_report';

	/** SLA metric keys. */
	public const METRIC_UPTIME          = 'uptime_pct';
	public const METRIC_PIPELINE_SUCCESS = 'pipeline_success_pct';
	public const METRIC_API_RESPONSE_MS  = 'api_response_ms';
	public const METRIC_COST_PER_ARTICLE = 'cost_per_article_cents';

	/** Default SLA targets. */
	public const DEFAULTS = [
		self::METRIC_UPTIME           => 99.9,
		self::METRIC_PIPELINE_SUCCESS => 99.0,
		self::METRIC_API_RESPONSE_MS  => 2000,
		self::METRIC_COST_PER_ARTICLE => 10, // cents
	];

	/** Maximum monthly history entries to keep. */
	private const HISTORY_MAX = 12;

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register WordPress hooks and cron schedules.
	 */
	public function register(): void {
		add_action( self::CRON_CHECK,  [ $this, 'evaluate' ] );
		add_action( self::CRON_REPORT, [ $this, 'generate_monthly_report' ] );

		if ( ! wp_next_scheduled( self::CRON_CHECK ) ) {
			wp_schedule_event( time(), 'hourly', self::CRON_CHECK );
		}

		// Schedule monthly report on the first day of the month at 08:00 UTC.
		if ( ! wp_next_scheduled( self::CRON_REPORT ) ) {
			$next_month = mktime( 8, 0, 0, (int) gmdate( 'n' ) + 1, 1 );
			wp_schedule_event( $next_month, 'monthly', self::CRON_REPORT );
		}
	}

	// -----------------------------------------------------------------------
	// Target management
	// -----------------------------------------------------------------------

	/**
	 * Retrieve all SLA targets (custom + defaults for missing keys).
	 *
	 * @return array<string, float|int>
	 */
	public function get_targets(): array {
		$raw     = get_option( self::OPTION_TARGETS, '' );
		$custom  = is_string( $raw ) && '' !== $raw ? json_decode( $raw, true ) : [];
		$custom  = is_array( $custom ) ? $custom : [];

		return array_merge( self::DEFAULTS, $custom );
	}

	/**
	 * Update one or more SLA targets.
	 *
	 * @param array<string, float|int> $targets  Key-value map of metric → target value.
	 */
	public function set_targets( array $targets ): void {
		$current = $this->get_targets();
		$merged  = array_merge( $current, $targets );
		// Strip keys not in the known metric list.
		$filtered = array_intersect_key( $merged, self::DEFAULTS );
		update_option( self::OPTION_TARGETS, wp_json_encode( $filtered ) );
	}

	/**
	 * Get the target for a specific metric.
	 *
	 * @param string $metric
	 * @return float|int
	 */
	public function get_target( string $metric ) {
		return $this->get_targets()[ $metric ] ?? ( self::DEFAULTS[ $metric ] ?? 0 );
	}

	// -----------------------------------------------------------------------
	// SLA evaluation
	// -----------------------------------------------------------------------

	/**
	 * Evaluate current metrics against SLA targets and persist the status.
	 *
	 * @param array<string, float|int>|null $metrics  Live metric values; pass null to read from WP options.
	 * @return array<string, array{target: float|int, actual: float|int, breached: bool}>
	 */
	public function evaluate( ?array $metrics = null ): array {
		if ( null === $metrics ) {
			$metrics = $this->read_live_metrics();
		}

		$targets = $this->get_targets();
		$status  = [];

		foreach ( $targets as $key => $target ) {
			$actual   = (float) ( $metrics[ $key ] ?? 0 );
			$breached = $this->is_breached( $key, (float) $target, $actual );

			$status[ $key ] = [
				'target'   => $target,
				'actual'   => $actual,
				'breached' => $breached,
			];
		}

		update_option( self::OPTION_STATUS, wp_json_encode( [
			'evaluated_at' => gmdate( 'Y-m-d H:i:s' ),
			'metrics'      => $status,
		] ) );

		// Fire alert for any breach.
		foreach ( $status as $key => $entry ) {
			if ( $entry['breached'] ) {
				do_action( 'pearblog_sla_breached', $key, $entry['target'], $entry['actual'] );
			}
		}

		return $status;
	}

	/**
	 * Get the last computed SLA status.
	 *
	 * @return array{evaluated_at: string, metrics: array}|null
	 */
	public function get_status(): ?array {
		$raw = get_option( self::OPTION_STATUS, '' );
		if ( ! is_string( $raw ) || '' === $raw ) {
			return null;
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : null;
	}

	// -----------------------------------------------------------------------
	// Monthly report
	// -----------------------------------------------------------------------

	/**
	 * Generate and store the monthly SLA report, then optionally e-mail it.
	 *
	 * @param array<string, float|int>|null $metrics  Metrics to snapshot; null = live read.
	 * @return array  The generated report entry.
	 */
	public function generate_monthly_report( ?array $metrics = null ): array {
		if ( null === $metrics ) {
			$metrics = $this->read_live_metrics();
		}

		$status = $this->evaluate( $metrics );
		$month  = gmdate( 'Y-m' );
		$breaches = array_keys( array_filter( $status, fn( $e ) => $e['breached'] ) );

		$report = [
			'month'      => $month,
			'generated'  => gmdate( 'Y-m-d H:i:s' ),
			'status'     => $status,
			'breaches'   => $breaches,
			'sla_met'    => empty( $breaches ),
		];

		// Append to history.
		$history   = $this->get_history();
		$history[ $month ] = $report;

		// Trim to last N months.
		if ( count( $history ) > self::HISTORY_MAX ) {
			$history = array_slice( $history, -self::HISTORY_MAX, null, true );
		}

		update_option( self::OPTION_HISTORY, wp_json_encode( $history ) );

		// E-mail if configured.
		$email = (string) get_option( self::OPTION_REPORT_EMAIL, '' );
		if ( '' !== $email ) {
			$this->send_report_email( $email, $report );
		}

		do_action( 'pearblog_sla_report_generated', $report );

		return $report;
	}

	/**
	 * Retrieve stored monthly history.
	 *
	 * @return array<string, array>  Keys are 'YYYY-MM' strings.
	 */
	public function get_history(): array {
		$raw = get_option( self::OPTION_HISTORY, '{}' );
		$decoded = json_decode( is_string( $raw ) ? $raw : '{}', true );
		return is_array( $decoded ) ? $decoded : [];
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Determine whether a metric value breaches its target.
	 *
	 * For response-time and cost metrics lower is better (breach when ABOVE target).
	 * For uptime and success-rate metrics higher is better (breach when BELOW target).
	 *
	 * @param string $key     Metric key.
	 * @param float  $target
	 * @param float  $actual
	 * @return bool
	 */
	public function is_breached( string $key, float $target, float $actual ): bool {
		$lower_is_better = [
			self::METRIC_API_RESPONSE_MS,
			self::METRIC_COST_PER_ARTICLE,
		];

		if ( in_array( $key, $lower_is_better, true ) ) {
			return $actual > $target;
		}

		// Higher is better (uptime, success rate).
		return $actual < $target;
	}

	/**
	 * Read live metrics from WordPress options written by other subsystems.
	 *
	 * @return array<string, float|int>
	 */
	private function read_live_metrics(): array {
		$perf_raw = get_option( 'pearblog_perf_metrics', '{}' );
		$perf     = json_decode( is_string( $perf_raw ) ? $perf_raw : '{}', true );
		$perf     = is_array( $perf ) ? $perf : [];

		// Pipeline success rate.
		$total    = (int) ( $perf['pipeline_runs']   ?? 0 );
		$success  = (int) ( $perf['pipeline_ok']     ?? 0 );
		$success_pct = $total > 0 ? ( $success / $total ) * 100 : 100.0;

		// Average response time (ms).
		$avg_ms = (float) ( $perf['avg_response_ms'] ?? 0.0 );

		// Average cost per article (cents).
		$cost_cents   = (float) get_option( 'pearblog_ai_cost_cents', 0 );
		$articles     = max( 1, (int) ( $perf['articles_published'] ?? 1 ) );
		$cost_per_art = $cost_cents / $articles;

		return [
			self::METRIC_UPTIME           => 100.0, // Uptime tracked externally; default to 100.
			self::METRIC_PIPELINE_SUCCESS => round( $success_pct, 2 ),
			self::METRIC_API_RESPONSE_MS  => round( $avg_ms, 1 ),
			self::METRIC_COST_PER_ARTICLE => round( $cost_per_art, 2 ),
		];
	}

	/**
	 * Send a monthly SLA report e-mail.
	 *
	 * @param string $email
	 * @param array  $report
	 */
	private function send_report_email( string $email, array $report ): void {
		$subject = sprintf( 'PearBlog Engine — SLA Report %s', $report['month'] );
		$sla_met = $report['sla_met'] ? '✅ All targets met' : '⚠️ SLA breach detected';

		$body  = "PearBlog Engine SLA Report — {$report['month']}\n\n";
		$body .= "Status: {$sla_met}\n\n";

		foreach ( $report['status'] as $key => $entry ) {
			$icon  = $entry['breached'] ? '❌' : '✅';
			$body .= "{$icon} {$key}: actual={$entry['actual']} target={$entry['target']}\n";
		}

		if ( ! empty( $report['breaches'] ) ) {
			$body .= "\nBreached metrics: " . implode( ', ', $report['breaches'] ) . "\n";
		}

		wp_mail( $email, $subject, $body );
	}
}

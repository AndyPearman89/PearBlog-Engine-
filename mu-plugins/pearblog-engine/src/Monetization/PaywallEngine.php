<?php
/**
 * Paywall Engine – metered paywall with Stripe subscription integration.
 *
 * Provides a metered paywall: readers get X free articles, then must subscribe.
 *
 * Features:
 *  - Cookie-based free article count (no login required for counting).
 *  - Per-article paywall gate with configurable messaging.
 *  - Stripe checkout session creation for subscription.
 *  - Subscriber management via Stripe webhooks.
 *  - Premium article tagging (per-post meta `pearblog_paywall_premium`).
 *
 * Configuration (WP options):
 *   pearblog_paywall_enabled          – (bool) enable paywall
 *   pearblog_paywall_free_limit       – free articles per month (default: 3)
 *   pearblog_paywall_stripe_key       – Stripe publishable key
 *   pearblog_paywall_stripe_secret    – Stripe secret key
 *   pearblog_paywall_price_id         – Stripe Price ID for subscription
 *   pearblog_paywall_success_url      – redirect after successful subscription
 *
 * @package PearBlogEngine\Monetization
 */

declare(strict_types=1);

namespace PearBlogEngine\Monetization;

/**
 * Metered paywall with Stripe subscription support.
 */
class PaywallEngine {

	/** WP option keys. */
	public const OPTION_ENABLED     = 'pearblog_paywall_enabled';
	public const OPTION_FREE_LIMIT  = 'pearblog_paywall_free_limit';
	public const OPTION_STRIPE_PUB  = 'pearblog_paywall_stripe_key';
	public const OPTION_STRIPE_SEC  = 'pearblog_paywall_stripe_secret';
	public const OPTION_PRICE_ID    = 'pearblog_paywall_price_id';
	public const OPTION_SUCCESS_URL = 'pearblog_paywall_success_url';

	/** Post meta: mark post as premium-only. */
	public const META_PREMIUM = 'pearblog_paywall_premium';

	/** Cookie name for free article tracking. */
	private const COOKIE_NAME = 'pb_free_reads';

	/** Cookie lifetime: 30 days. */
	private const COOKIE_TTL = 30 * DAY_IN_SECONDS;

	/** Stripe Checkout Sessions endpoint. */
	private const STRIPE_CHECKOUT_URL = 'https://api.stripe.com/v1/checkout/sessions';

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register WordPress hooks.
	 */
	public function register(): void {
		add_filter( 'the_content', [ $this, 'maybe_gate_content' ], 10 );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/paywall/checkout', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_create_checkout' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( self::NAMESPACE, '/paywall/status', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_get_status' ],
			'permission_callback' => '__return_true',
		] );
	}

	// -----------------------------------------------------------------------
	// Core paywall logic
	// -----------------------------------------------------------------------

	/**
	 * Whether the paywall is enabled.
	 */
	public function is_enabled(): bool {
		return (bool) get_option( self::OPTION_ENABLED, false );
	}

	/**
	 * Whether the current user has subscriber access.
	 *
	 * Checks for a `pb_subscriber` capability or a valid subscriber cookie.
	 */
	public function has_access(): bool {
		// Logged-in subscribers always have access.
		if ( is_user_logged_in() && current_user_can( 'pb_subscriber' ) ) {
			return true;
		}

		// Admins always have access.
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Check subscriber session cookie.
		$subscriber_token = $_COOKIE['pb_subscriber_token'] ?? '';
		if ( '' !== $subscriber_token ) {
			return (bool) get_option( 'pb_subscriber_' . hash( 'sha256', $subscriber_token ) );
		}

		return false;
	}

	/**
	 * Get the number of free articles the current reader has consumed this month.
	 *
	 * @return int Number of articles read.
	 */
	public function get_reads_count(): int {
		$data = $this->get_cookie_data();
		return $data['count'] ?? 0;
	}

	/**
	 * Whether the reader has exhausted their free article allowance.
	 */
	public function has_exceeded_free_limit(): bool {
		$limit = (int) get_option( self::OPTION_FREE_LIMIT, 3 );
		return $this->get_reads_count() >= $limit;
	}

	/**
	 * Filter callback: gate article content if paywall is active.
	 *
	 * @param string $content Post content.
	 * @return string Original or gated content.
	 */
	public function maybe_gate_content( string $content ): string {
		if ( ! $this->is_enabled() || ! is_singular( 'post' ) ) {
			return $content;
		}

		// Check if this specific post is marked as premium.
		$post_id  = get_the_ID();
		$premium  = (bool) get_post_meta( $post_id, self::META_PREMIUM, true );

		if ( $this->has_access() ) {
			return $content;
		}

		if ( $premium || $this->has_exceeded_free_limit() ) {
			// Increment read counter before gating.
			if ( ! $premium ) {
				$this->increment_read_counter();
			}
			return $this->get_gate_html( $content );
		}

		// Increment read counter for metered articles.
		$this->increment_read_counter();

		return $content;
	}

	// -----------------------------------------------------------------------
	// Stripe checkout
	// -----------------------------------------------------------------------

	/**
	 * Create a Stripe Checkout Session.
	 *
	 * @param string $email    Subscriber email (optional).
	 * @param string $redirect Success redirect URL.
	 * @return array{url: string, session_id: string}
	 * @throws \RuntimeException On API failure.
	 */
	public function create_checkout_session( string $email = '', string $redirect = '' ): array {
		$secret_key = (string) get_option( self::OPTION_STRIPE_SEC, '' );
		$price_id   = (string) get_option( self::OPTION_PRICE_ID, '' );
		$success_url = $redirect ?: (string) get_option( self::OPTION_SUCCESS_URL, get_site_url() . '/?pb_subscribed=1' );

		if ( '' === $secret_key || '' === $price_id ) {
			throw new \RuntimeException( 'Stripe not configured.' );
		}

		$params = [
			'mode'                                 => 'subscription',
			'success_url'                          => $success_url . '&session_id={CHECKOUT_SESSION_ID}',
			'cancel_url'                           => get_permalink(),
			'line_items[0][price]'                 => $price_id,
			'line_items[0][quantity]'              => 1,
		];

		if ( '' !== $email ) {
			$params['customer_email'] = $email;
		}

		$response = wp_remote_post( self::STRIPE_CHECKOUT_URL, [
			'headers' => [
				'Authorization' => 'Bearer ' . $secret_key,
			],
			'body'    => $params,
			'timeout' => 15,
		] );

		if ( is_wp_error( $response ) ) {
			throw new \RuntimeException( 'Stripe API error: ' . $response->get_error_message() );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $data['url'] ) ) {
			throw new \RuntimeException( 'Failed to create Stripe checkout session.' );
		}

		return [
			'url'        => $data['url'],
			'session_id' => $data['id'] ?? '',
		];
	}

	// -----------------------------------------------------------------------
	// REST callbacks
	// -----------------------------------------------------------------------

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_create_checkout( \WP_REST_Request $request ) {
		try {
			$email    = sanitize_email( (string) ( $request->get_param( 'email' ) ?? '' ) );
			$redirect = esc_url_raw( (string) ( $request->get_param( 'success_url' ) ?? '' ) );
			$session  = $this->create_checkout_session( $email, $redirect );

			return new \WP_REST_Response( $session, 200 );
		} catch ( \Throwable $e ) {
			return new \WP_Error( 'checkout_failed', $e->getMessage(), [ 'status' => 500 ] );
		}
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_get_status( \WP_REST_Request $request ): \WP_REST_Response {
		$limit = (int) get_option( self::OPTION_FREE_LIMIT, 3 );
		$reads = $this->get_reads_count();

		return new \WP_REST_Response( [
			'has_access'   => $this->has_access(),
			'reads_this_month' => $reads,
			'free_limit'   => $limit,
			'remaining'    => max( 0, $limit - $reads ),
			'exceeded'     => $this->has_exceeded_free_limit(),
		], 200 );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Get paywall gate HTML (truncated content + subscribe CTA).
	 *
	 * @param string $content Full article content.
	 * @return string Gated HTML.
	 */
	private function get_gate_html( string $content ): string {
		// Show first ~20% of content.
		$paragraphs = explode( '</p>', $content );
		$preview    = implode( '</p>', array_slice( $paragraphs, 0, max( 1, (int) ( count( $paragraphs ) * 0.2 ) ) ) ) . '</p>';
		$limit      = (int) get_option( self::OPTION_FREE_LIMIT, 3 );
		$reads      = $this->get_reads_count();

		return $preview . '
<div class="pb-paywall-gate" style="position:relative;margin-top:-60px;padding:80px 32px 32px;background:linear-gradient(to bottom,transparent,#fff 40%,#fff);text-align:center;">
	<h3 style="font-size:22px;margin:0 0 12px;">' . esc_html__( 'Continue Reading', 'pearblog-engine' ) . '</h3>
	<p style="color:#555;margin:0 0 16px;">' . sprintf(
		esc_html__( "You've read %d of your %d free articles this month.", 'pearblog-engine' ),
		$reads,
		$limit
	) . '</p>
	<button onclick="pbStartCheckout()" style="background:#6366f1;color:#fff;border:none;padding:14px 32px;border-radius:8px;font-size:16px;cursor:pointer;">' . esc_html__( 'Subscribe for Full Access →', 'pearblog-engine' ) . '</button>
</div>
<script>
function pbStartCheckout(){
	fetch("' . esc_url( rest_url( 'pearblog/v1/paywall/checkout' ) ) . '",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({})})
	.then(r=>r.json()).then(function(d){ if(d.url) window.location.href=d.url; });
}
</script>';
	}

	/**
	 * Increment the cookie-based read counter.
	 */
	private function increment_read_counter(): void {
		$data          = $this->get_cookie_data();
		$data['count'] = ( $data['count'] ?? 0 ) + 1;
		$data['month'] = date( 'Y-m' );

		setcookie( self::COOKIE_NAME, wp_json_encode( $data ), time() + self::COOKIE_TTL, '/', '', is_ssl(), true );
	}

	/**
	 * Parse cookie data.
	 *
	 * @return array{count: int, month: string}
	 */
	private function get_cookie_data(): array {
		$raw  = $_COOKIE[ self::COOKIE_NAME ] ?? '';
		$data = is_string( $raw ) ? json_decode( $raw, true ) : null;

		if ( ! is_array( $data ) ) {
			return [ 'count' => 0, 'month' => date( 'Y-m' ) ];
		}

		// Reset counter if it's a new month.
		if ( ( $data['month'] ?? '' ) !== date( 'Y-m' ) ) {
			return [ 'count' => 0, 'month' => date( 'Y-m' ) ];
		}

		return $data;
	}
}

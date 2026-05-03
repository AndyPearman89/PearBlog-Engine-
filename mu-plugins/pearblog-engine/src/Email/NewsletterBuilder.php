<?php
/**
 * Newsletter Builder – converts top weekly articles into HTML newsletter.
 *
 * Extends the EmailDigest with a full newsletter system supporting:
 *  - HTML newsletter generation from recent top-performing articles.
 *  - Direct integration with SendGrid, Mailchimp, and Brevo (formerly Sendinblue).
 *  - Configurable subscriber list selection.
 *  - Weekly send cron.
 *
 * Configuration (WP options):
 *   pearblog_newsletter_enabled       – (bool) enable newsletter
 *   pearblog_newsletter_provider      – 'sendgrid' | 'mailchimp' | 'brevo'
 *   pearblog_newsletter_api_key       – API key for chosen provider
 *   pearblog_newsletter_list_id       – Subscriber list/audience ID
 *   pearblog_newsletter_from_email    – Sender email address
 *   pearblog_newsletter_from_name     – Sender display name
 *   pearblog_newsletter_subject       – Email subject (supports {date} placeholder)
 *   pearblog_newsletter_articles_n    – Number of articles to include (default: 5)
 *
 * @package PearBlogEngine\Email
 */

declare(strict_types=1);

namespace PearBlogEngine\Email;

/**
 * Builds and sends weekly HTML newsletters via email marketing APIs.
 */
class NewsletterBuilder {

	/** WP option keys. */
	public const OPTION_ENABLED    = 'pearblog_newsletter_enabled';
	public const OPTION_PROVIDER   = 'pearblog_newsletter_provider';
	public const OPTION_API_KEY    = 'pearblog_newsletter_api_key';
	public const OPTION_LIST_ID    = 'pearblog_newsletter_list_id';
	public const OPTION_FROM_EMAIL = 'pearblog_newsletter_from_email';
	public const OPTION_FROM_NAME  = 'pearblog_newsletter_from_name';
	public const OPTION_SUBJECT    = 'pearblog_newsletter_subject';
	public const OPTION_ARTICLES_N = 'pearblog_newsletter_articles_n';

	/** Default number of articles. */
	private const DEFAULT_ARTICLES_N = 5;

	/** Cron hook. */
	private const CRON_HOOK = 'pearblog_newsletter_send';

	/** SendGrid API endpoint. */
	private const SENDGRID_URL = 'https://api.sendgrid.com/v3/mail/send';

	/** Mailchimp API base (requires datacenter in URL). */
	private const MAILCHIMP_BASE = 'https://%s.api.mailchimp.com/3.0';

	/** Brevo (Sendinblue) transactional email API. */
	private const BREVO_URL = 'https://api.brevo.com/v3/emailCampaigns';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register WordPress hooks.
	 */
	public function register(): void {
		add_action( self::CRON_HOOK, [ $this, 'send_weekly_newsletter' ] );
		add_action( 'init', [ $this, 'maybe_schedule' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Schedule weekly newsletter cron.
	 */
	public function maybe_schedule(): void {
		if ( ! (bool) get_option( self::OPTION_ENABLED, false ) ) {
			return;
		}

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			// Send on Monday at 9am.
			$next_monday = strtotime( 'next monday 09:00' );
			wp_schedule_event( $next_monday, 'weekly', self::CRON_HOOK );
		}
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( 'pearblog/v1', '/newsletter/preview', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_preview' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );

		register_rest_route( 'pearblog/v1', '/newsletter/send', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_send' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );
	}

	// -----------------------------------------------------------------------
	// Newsletter generation
	// -----------------------------------------------------------------------

	/**
	 * Build newsletter HTML from recent articles.
	 *
	 * @param int $limit Number of articles to include.
	 * @return string HTML newsletter content.
	 */
	public function build_html( int $limit = 5 ): string {
		$articles = $this->get_featured_articles( $limit );
		$site_name = get_bloginfo( 'name' );
		$site_url  = get_site_url();
		$date      = date( 'F j, Y' );

		$articles_html = '';
		foreach ( $articles as $post ) {
			$image_url  = get_the_post_thumbnail_url( $post->ID, 'medium' ) ?: '';
			$excerpt    = wp_trim_words( get_the_excerpt( $post->ID ), 30 );
			$permalink  = get_permalink( $post->ID );
			$quality    = (int) get_post_meta( $post->ID, 'pearblog_quality_score', true );

			$image_block = $image_url
				? "<img src=\"{$image_url}\" alt=\"\" style=\"max-width:100%;height:auto;border-radius:4px;margin-bottom:12px;\">"
				: '';

			$quality_badge = $quality > 0
				? "<span style=\"background:#e8f5e9;color:#2e7d32;padding:2px 8px;border-radius:12px;font-size:12px;\">Quality: {$quality}/100</span>"
				: '';

			$articles_html .= "
<div style=\"border:1px solid #e9ecef;border-radius:8px;padding:20px;margin-bottom:20px;\">
	{$image_block}
	<h2 style=\"font-size:20px;margin:0 0 8px;color:#1a1a2e;\">{$post->post_title}</h2>
	{$quality_badge}
	<p style=\"color:#555;line-height:1.6;margin:12px 0;\">{$excerpt}</p>
	<a href=\"{$permalink}\" style=\"display:inline-block;background:#6366f1;color:#fff;padding:10px 20px;text-decoration:none;border-radius:6px;font-size:14px;\">Read Article →</a>
</div>";
		}

		return "
<!DOCTYPE html>
<html lang=\"en\">
<head>
<meta charset=\"UTF-8\">
<meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">
<title>{$site_name} Newsletter – {$date}</title>
</head>
<body style=\"margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;\">
<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f5f5f5;\">
<tr><td align=\"center\" style=\"padding:40px 20px;\">
<table width=\"600\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#fff;border-radius:12px;overflow:hidden;\">

<!-- Header -->
<tr><td style=\"background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:40px 32px;text-align:center;\">
<h1 style=\"color:#fff;margin:0;font-size:28px;\">{$site_name}</h1>
<p style=\"color:rgba(255,255,255,0.8);margin:8px 0 0;\">{$date} — Weekly Digest</p>
</td></tr>

<!-- Content -->
<tr><td style=\"padding:32px;\">
<p style=\"color:#333;font-size:16px;line-height:1.6;\">Here are this week's top articles:</p>
{$articles_html}
</td></tr>

<!-- Footer -->
<tr><td style=\"background:#f8f9fa;padding:24px 32px;text-align:center;border-top:1px solid #e9ecef;\">
<p style=\"color:#888;font-size:12px;margin:0;\">
	<a href=\"{$site_url}\" style=\"color:#6366f1;text-decoration:none;\">{$site_name}</a> |
	<a href=\"%%unsubscribe_url%%\" style=\"color:#888;\">Unsubscribe</a>
</p>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>";
	}

	/**
	 * Send the weekly newsletter via the configured provider.
	 *
	 * @return array{success: bool, provider: string, response: mixed}
	 */
	public function send_weekly_newsletter(): array {
		if ( ! (bool) get_option( self::OPTION_ENABLED, false ) ) {
			return [ 'success' => false, 'provider' => 'none', 'response' => 'disabled' ];
		}

		$n        = (int) get_option( self::OPTION_ARTICLES_N, self::DEFAULT_ARTICLES_N );
		$html     = $this->build_html( $n );
		$subject  = str_replace( '{date}', date( 'F j, Y' ), (string) get_option( self::OPTION_SUBJECT, '{date} – Newsletter' ) );
		$provider = (string) get_option( self::OPTION_PROVIDER, 'sendgrid' );

		$result = match ( $provider ) {
			'mailchimp' => $this->send_via_mailchimp( $subject, $html ),
			'brevo'     => $this->send_via_brevo( $subject, $html ),
			default     => $this->send_via_sendgrid( $subject, $html ),
		};

		update_option( 'pearblog_newsletter_last_sent', time() );

		/**
		 * Action: pearblog_newsletter_sent
		 *
		 * @param array<string,mixed> $result Send result.
		 */
		do_action( 'pearblog_newsletter_sent', $result );

		return $result;
	}

	// -----------------------------------------------------------------------
	// Provider implementations
	// -----------------------------------------------------------------------

	/**
	 * Send via SendGrid.
	 *
	 * @param string $subject Email subject.
	 * @param string $html    HTML body.
	 * @return array{success: bool, provider: string, response: mixed}
	 */
	private function send_via_sendgrid( string $subject, string $html ): array {
		$api_key   = (string) get_option( self::OPTION_API_KEY, '' );
		$list_id   = (string) get_option( self::OPTION_LIST_ID, '' );
		$from_email = (string) get_option( self::OPTION_FROM_EMAIL, get_option( 'admin_email' ) );
		$from_name  = (string) get_option( self::OPTION_FROM_NAME, get_bloginfo( 'name' ) );

		if ( '' === $api_key ) {
			return [ 'success' => false, 'provider' => 'sendgrid', 'response' => 'no_api_key' ];
		}

		$payload = [
			'from'                  => [ 'email' => $from_email, 'name' => $from_name ],
			'subject'               => $subject,
			'content'               => [ [ 'type' => 'text/html', 'value' => $html ] ],
			'personalizations'      => [ [ 'to' => [ [ 'email' => $from_email ] ] ] ],
		];

		// If list ID provided, use marketing campaign approach.
		if ( '' !== $list_id ) {
			// For SendGrid marketing campaigns, different endpoint is used.
			$payload['list_ids'] = [ $list_id ];
		}

		$response = wp_remote_post( self::SENDGRID_URL, [
			'headers' => [
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( $payload ),
			'timeout' => 20,
		] );

		$code    = wp_remote_retrieve_response_code( $response );
		$success = ! is_wp_error( $response ) && in_array( $code, [ 200, 201, 202 ], true );

		return [
			'success'  => $success,
			'provider' => 'sendgrid',
			'response' => is_wp_error( $response ) ? $response->get_error_message() : $code,
		];
	}

	/**
	 * Send via Brevo.
	 *
	 * @param string $subject Email subject.
	 * @param string $html    HTML body.
	 * @return array{success: bool, provider: string, response: mixed}
	 */
	private function send_via_brevo( string $subject, string $html ): array {
		$api_key   = (string) get_option( self::OPTION_API_KEY, '' );
		$list_id   = (int) get_option( self::OPTION_LIST_ID, 0 );
		$from_email = (string) get_option( self::OPTION_FROM_EMAIL, get_option( 'admin_email' ) );
		$from_name  = (string) get_option( self::OPTION_FROM_NAME, get_bloginfo( 'name' ) );

		if ( '' === $api_key ) {
			return [ 'success' => false, 'provider' => 'brevo', 'response' => 'no_api_key' ];
		}

		$payload = [
			'name'        => $subject,
			'subject'     => $subject,
			'type'        => 'classic',
			'htmlContent' => $html,
			'sender'      => [ 'email' => $from_email, 'name' => $from_name ],
			'recipients'  => [ 'listIds' => [ $list_id ] ],
			'scheduledAt' => gmdate( 'Y-m-d\TH:i:s\Z', time() + 60 ),
		];

		$response = wp_remote_post( self::BREVO_URL, [
			'headers' => [
				'api-key'      => $api_key,
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			],
			'body'    => wp_json_encode( $payload ),
			'timeout' => 20,
		] );

		$code    = wp_remote_retrieve_response_code( $response );
		$success = ! is_wp_error( $response ) && in_array( $code, [ 200, 201 ], true );

		return [
			'success'  => $success,
			'provider' => 'brevo',
			'response' => is_wp_error( $response ) ? $response->get_error_message() : $code,
		];
	}

	/**
	 * Send via Mailchimp.
	 *
	 * @param string $subject Email subject.
	 * @param string $html    HTML body.
	 * @return array{success: bool, provider: string, response: mixed}
	 */
	private function send_via_mailchimp( string $subject, string $html ): array {
		$api_key    = (string) get_option( self::OPTION_API_KEY, '' );
		$list_id    = (string) get_option( self::OPTION_LIST_ID, '' );
		$from_email = (string) get_option( self::OPTION_FROM_EMAIL, get_option( 'admin_email' ) );
		$from_name  = (string) get_option( self::OPTION_FROM_NAME, get_bloginfo( 'name' ) );

		if ( '' === $api_key || '' === $list_id ) {
			return [ 'success' => false, 'provider' => 'mailchimp', 'response' => 'no_api_key_or_list' ];
		}

		// Mailchimp datacenter is embedded in the API key after the dash.
		$dc       = substr( $api_key, strpos( $api_key, '-' ) + 1 );
		$base_url = sprintf( self::MAILCHIMP_BASE, $dc );

		// Step 1: Create campaign.
		$campaign_response = wp_remote_post( $base_url . '/campaigns', [
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode( 'user:' . $api_key ),
				'Content-Type'  => 'application/json',
			],
			'body' => wp_json_encode( [
				'type'       => 'regular',
				'recipients' => [ 'list_id' => $list_id ],
				'settings'   => [
					'subject_line' => $subject,
					'from_name'    => $from_name,
					'reply_to'     => $from_email,
				],
			] ),
			'timeout' => 15,
		] );

		if ( is_wp_error( $campaign_response ) ) {
			return [ 'success' => false, 'provider' => 'mailchimp', 'response' => 'campaign_creation_failed' ];
		}

		$campaign = json_decode( wp_remote_retrieve_body( $campaign_response ), true );
		$campaign_id = $campaign['id'] ?? '';

		if ( '' === $campaign_id ) {
			return [ 'success' => false, 'provider' => 'mailchimp', 'response' => 'no_campaign_id' ];
		}

		// Step 2: Set content.
		wp_remote_put( $base_url . "/campaigns/{$campaign_id}/content", [
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode( 'user:' . $api_key ),
				'Content-Type'  => 'application/json',
			],
			'body' => wp_json_encode( [ 'html' => $html ] ),
			'timeout' => 15,
		] );

		// Step 3: Send.
		$send_response = wp_remote_post( $base_url . "/campaigns/{$campaign_id}/actions/send", [
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode( 'user:' . $api_key ),
			],
			'timeout' => 15,
		] );

		$code    = wp_remote_retrieve_response_code( $send_response );
		$success = ! is_wp_error( $send_response ) && 204 === $code;

		return [
			'success'  => $success,
			'provider' => 'mailchimp',
			'response' => $campaign_id,
		];
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Get recent top-performing articles for the newsletter.
	 *
	 * @param int $limit Number of articles.
	 * @return \WP_Post[]
	 */
	private function get_featured_articles( int $limit ): array {
		return get_posts( [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => [
				[
					'key'     => 'pearblog_quality_score',
					'value'   => 50,
					'compare' => '>=',
					'type'    => 'NUMERIC',
				],
			],
		] );
	}

	// -----------------------------------------------------------------------
	// REST callbacks
	// -----------------------------------------------------------------------

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_preview( \WP_REST_Request $request ): \WP_REST_Response {
		$n    = (int) ( $request->get_param( 'n' ) ?? 5 );
		$html = $this->build_html( $n );
		return new \WP_REST_Response( [ 'html' => $html ], 200 );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_send( \WP_REST_Request $request ): \WP_REST_Response {
		$result = $this->send_weekly_newsletter();
		return new \WP_REST_Response( $result, $result['success'] ? 200 : 500 );
	}

	/**
	 * Permission callback.
	 */
	public function admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}
}

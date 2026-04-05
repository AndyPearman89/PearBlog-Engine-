<?php
/**
 * Email digest – sends a weekly newsletter of newly published articles.
 *
 * Supports:
 *  - Mailchimp Campaigns API (creates and sends a campaign)
 *  - ConvertKit Broadcasts API
 *  - Fallback: wp_mail to a configured list of recipients
 *
 * Triggered by WP-Cron weekly or via admin action.
 *
 * @package PearBlogEngine\Email
 */

declare(strict_types=1);

namespace PearBlogEngine\Email;

/**
 * Sends weekly email digests with newly published PearBlog articles.
 */
class EmailDigest {

	public const CRON_HOOK = 'pearblog_email_digest';

	/**
	 * Register the weekly cron event.
	 */
	public function register(): void {
		add_action( self::CRON_HOOK, [ $this, 'send' ] );
		add_action( 'init', [ $this, 'maybe_schedule' ] );
	}

	public function maybe_schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			// Every Monday at 09:00 UTC.
			$next_monday = strtotime( 'next Monday 09:00 UTC' );
			wp_schedule_event( $next_monday, 'weekly', self::CRON_HOOK );
		}
	}

	/**
	 * Build and send the weekly digest.
	 *
	 * @param int $days   Look back this many days for new posts.
	 * @return bool       True if dispatch succeeded via at least one channel.
	 */
	public function send( int $days = 7 ): bool {
		$posts = $this->get_recent_posts( $days );
		if ( empty( $posts ) ) {
			return false;
		}

		$subject = sprintf(
			/* translators: %s: site name */
			__( 'Weekly digest from %s', 'pearblog-engine' ),
			get_bloginfo( 'name' )
		);

		$html = $this->build_html( $posts );
		$text = $this->build_text( $posts );

		$provider = (string) get_option( 'pearblog_esp_provider', 'none' );

		$success = false;
		switch ( $provider ) {
			case 'mailchimp':
				$success = $this->send_mailchimp( $subject, $html );
				break;
			case 'convertkit':
				$success = $this->send_convertkit( $subject, $html );
				break;
		}

		// Always send via wp_mail as fallback / supplement if configured.
		$fallback_email = (string) get_option( 'pearblog_digest_email', '' );
		if ( '' !== $fallback_email ) {
			$success = wp_mail(
				$fallback_email,
				$subject,
				$html,
				[
					'Content-Type: text/html; charset=UTF-8',
					'Content-Transfer-Encoding: quoted-printable',
				]
			) || $success;
		}

		if ( $success ) {
			update_option( 'pearblog_last_digest_sent', current_time( 'mysql' ) );
		}

		return $success;
	}

	// -----------------------------------------------------------------------
	// Channel implementations
	// -----------------------------------------------------------------------

	private function send_mailchimp( string $subject, string $html ): bool {
		$api_key = (string) get_option( 'pearblog_mailchimp_api_key', '' );
		$list_id = (string) get_option( 'pearblog_mailchimp_list_id', '' );

		if ( '' === $api_key || '' === $list_id ) {
			return false;
		}

		// Extract datacenter from API key (format: key-us1).
		$dash_pos = strrpos( $api_key, '-' );
		if ( false === $dash_pos || strlen( $api_key ) - 1 === $dash_pos ) {
			error_log( 'PearBlog EmailDigest: Invalid Mailchimp API key format; unable to derive datacenter suffix.' );
			return false;
		}

		$dc = trim( substr( $api_key, $dash_pos + 1 ) );
		if ( '' === $dc ) {
			error_log( 'PearBlog EmailDigest: Invalid Mailchimp API key format; datacenter suffix is empty.' );
			return false;
		}

		$endpoint = "https://{$dc}.api.mailchimp.com/3.0/campaigns";

		// Create campaign.
		$response = wp_remote_post( $endpoint, [
			'timeout' => 15,
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode( 'anystring:' . $api_key ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				'Content-Type'  => 'application/json',
			],
			'body' => wp_json_encode( [
				'type'       => 'regular',
				'recipients' => [ 'list_id' => $list_id ],
				'settings'   => [
					'subject_line' => $subject,
					'title'        => $subject,
					'from_name'    => get_bloginfo( 'name' ),
					'reply_to'     => get_bloginfo( 'admin_email' ),
				],
			] ),
		] );

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			error_log( 'PearBlog EmailDigest: Mailchimp campaign creation failed.' );
			return false;
		}

		$data        = json_decode( wp_remote_retrieve_body( $response ), true );
		$campaign_id = $data['id'] ?? '';

		if ( '' === $campaign_id ) {
			return false;
		}

		// Set campaign content.
		wp_remote_post( "https://{$dc}.api.mailchimp.com/3.0/campaigns/{$campaign_id}/content", [
			'timeout' => 15,
			'method'  => 'PUT',
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode( 'anystring:' . $api_key ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				'Content-Type'  => 'application/json',
			],
			'body' => wp_json_encode( [ 'html' => $html ] ),
		] );

		// Send.
		$send_response = wp_remote_post( "https://{$dc}.api.mailchimp.com/3.0/campaigns/{$campaign_id}/actions/send", [
			'timeout' => 15,
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode( 'anystring:' . $api_key ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				'Content-Type'  => 'application/json',
			],
			'body' => '{}',
		] );

		$send_code = (int) wp_remote_retrieve_response_code( $send_response );
		return 204 === $send_code;
	}

	private function send_convertkit( string $subject, string $html ): bool {
		$api_key = (string) get_option( 'pearblog_convertkit_api_key', '' );

		if ( '' === $api_key ) {
			return false;
		}

		$response = wp_remote_post( 'https://api.convertkit.com/v3/broadcasts', [
			'timeout' => 15,
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( [
				'api_secret'   => $api_key,
				'subject'      => $subject,
				'content'      => $html,
				'public'       => true,
			] ),
		] );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		return in_array( $code, [ 200, 201 ], true );
	}

	// -----------------------------------------------------------------------
	// Content builders
	// -----------------------------------------------------------------------

	private function build_html( array $posts ): string {
		$site_name = get_bloginfo( 'name' );
		$site_url  = get_site_url();

		$html  = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . esc_html( $site_name ) . '</title></head><body>';
		$html .= '<h1 style="font-family:sans-serif;">' . esc_html( $site_name ) . ' – Weekly Digest</h1>';
		$html .= '<p style="font-family:sans-serif;">' . esc_html__( 'Here are this week\'s new articles:', 'pearblog-engine' ) . '</p>';
		$html .= '<ul style="font-family:sans-serif;line-height:1.8;">';

		foreach ( $posts as $post ) {
			$url   = esc_url( get_permalink( $post->ID ) );
			$title = esc_html( get_the_title( $post->ID ) );
			$desc  = esc_html( (string) get_post_meta( $post->ID, 'pearblog_meta_description', true ) );
			$html .= "<li><a href='{$url}'>{$title}</a>";
			if ( '' !== $desc ) {
				$html .= "<br><small>{$desc}</small>";
			}
			$html .= '</li>';
		}

		$html .= '</ul>';
		$html .= '<p style="font-family:sans-serif;"><a href="' . esc_url( $site_url ) . '">' . esc_html( $site_name ) . '</a></p>';
		$html .= '</body></html>';

		return $html;
	}

	private function build_text( array $posts ): string {
		$site_name = get_bloginfo( 'name' );
		$text      = "{$site_name} – Weekly Digest\n\n";

		foreach ( $posts as $post ) {
			$url   = get_permalink( $post->ID );
			$title = get_the_title( $post->ID );
			$text .= "• {$title}\n  {$url}\n\n";
		}

		return $text;
	}

	// -----------------------------------------------------------------------
	// Query helpers
	// -----------------------------------------------------------------------

	private function get_recent_posts( int $days ): array {
		return get_posts( [
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'date_query'     => [
				[
					'after'     => date( 'Y-m-d', time() - ( $days * DAY_IN_SECONDS ) ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					'inclusive' => true,
				],
			],
		] );
	}
}

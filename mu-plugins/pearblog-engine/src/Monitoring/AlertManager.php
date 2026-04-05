<?php
/**
 * Alert Manager – dispatches operational alerts via Slack, Discord, and email.
 *
 * Sends notifications when the pipeline fails, the circuit breaker opens,
 * the topic queue is empty, or on custom alert triggers.
 *
 * @package PearBlogEngine\Monitoring
 */

declare(strict_types=1);

namespace PearBlogEngine\Monitoring;

/**
 * Dispatches alerts to configured channels.
 */
class AlertManager {

	/** @var string[] Supported alert levels. */
	private const LEVELS = [ 'info', 'warning', 'critical' ];

	/**
	 * Register pipeline failure hooks for automatic alerting.
	 */
	public function register(): void {
		// Alert when pipeline completes (for daily digest).
		add_action( 'pearblog_pipeline_completed', [ $this, 'on_pipeline_completed' ], 10, 3 );
	}

	/**
	 * Send an alert to all configured channels.
	 *
	 * @param string $level   Alert level: 'info', 'warning', or 'critical'.
	 * @param string $title   Short summary line.
	 * @param string $message Detailed message body.
	 * @return bool True if at least one channel was notified.
	 */
	public function send( string $level, string $title, string $message ): bool {
		if ( ! in_array( $level, self::LEVELS, true ) ) {
			$level = 'info';
		}

		$sent = false;

		// Slack.
		$slack_webhook = (string) get_option( 'pearblog_alert_slack_webhook', '' );
		if ( '' !== $slack_webhook ) {
			$sent = $this->send_slack( $slack_webhook, $level, $title, $message ) || $sent;
		}

		// Discord.
		$discord_webhook = (string) get_option( 'pearblog_alert_discord_webhook', '' );
		if ( '' !== $discord_webhook ) {
			$sent = $this->send_discord( $discord_webhook, $level, $title, $message ) || $sent;
		}

		// Email.
		$email = (string) get_option( 'pearblog_alert_email', '' );
		if ( '' !== $email ) {
			$sent = $this->send_email( $email, $level, $title, $message ) || $sent;
		}

		/**
		 * Action: pearblog_alert_sent
		 *
		 * Fires after an alert has been dispatched (or attempted).
		 *
		 * @param string $level   Alert level.
		 * @param string $title   Alert title.
		 * @param string $message Alert body.
		 * @param bool   $sent    Whether at least one channel succeeded.
		 */
		do_action( 'pearblog_alert_sent', $level, $title, $message, $sent );

		return $sent;
	}

	/**
	 * Convenience: send a critical alert.
	 */
	public function critical( string $title, string $message ): bool {
		return $this->send( 'critical', $title, $message );
	}

	/**
	 * Convenience: send a warning alert.
	 */
	public function warning( string $title, string $message ): bool {
		return $this->send( 'warning', $title, $message );
	}

	/**
	 * Convenience: send an info alert.
	 */
	public function info( string $title, string $message ): bool {
		return $this->send( 'info', $title, $message );
	}

	/**
	 * Hook callback: log pipeline completion.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $topic   Topic.
	 * @param object $context Tenant context.
	 */
	public function on_pipeline_completed( int $post_id, string $topic, $context ): void {
		// Only alert on first article of the day (avoid noise).
		$today_count = (int) get_transient( 'pearblog_pipeline_today_count' );
		$today_count++;
		set_transient( 'pearblog_pipeline_today_count', $today_count, DAY_IN_SECONDS );

		if ( 1 === $today_count ) {
			$this->info(
				'Pipeline Active',
				sprintf( 'First article published today: "%s" (Post #%d)', $topic, $post_id )
			);
		}
	}

	// -----------------------------------------------------------------------
	// Channel implementations
	// -----------------------------------------------------------------------

	/**
	 * Send alert via Slack Incoming Webhook.
	 */
	private function send_slack( string $webhook_url, string $level, string $title, string $message ): bool {
		$emoji = match ( $level ) {
			'critical' => '🚨',
			'warning'  => '⚠️',
			default    => 'ℹ️',
		};

		$payload = [
			'text'   => sprintf( '%s *[%s] %s*', $emoji, strtoupper( $level ), $title ),
			'blocks' => [
				[
					'type' => 'section',
					'text' => [
						'type' => 'mrkdwn',
						'text' => sprintf( "%s *[%s] %s*\n%s", $emoji, strtoupper( $level ), $title, $message ),
					],
				],
			],
		];

		$response = wp_remote_post( $webhook_url, [
			'timeout' => 10,
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => (string) wp_json_encode( $payload ),
		] );

		return ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Send alert via Discord Webhook.
	 */
	private function send_discord( string $webhook_url, string $level, string $title, string $message ): bool {
		$color = match ( $level ) {
			'critical' => 0xFF0000, // Red
			'warning'  => 0xFFA500, // Orange
			default    => 0x2563EB, // Blue
		};

		$payload = [
			'embeds' => [
				[
					'title'       => sprintf( '[%s] %s', strtoupper( $level ), $title ),
					'description' => $message,
					'color'       => $color,
					'timestamp'   => gmdate( 'c' ),
					'footer'      => [ 'text' => 'PearBlog Engine' ],
				],
			],
		];

		$response = wp_remote_post( $webhook_url, [
			'timeout' => 10,
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => (string) wp_json_encode( $payload ),
		] );

		$status = wp_remote_retrieve_response_code( $response );

		return ! is_wp_error( $response ) && $status >= 200 && $status < 300;
	}

	/**
	 * Send alert via WordPress email.
	 */
	private function send_email( string $to, string $level, string $title, string $message ): bool {
		$subject = sprintf( '[PearBlog %s] %s', strtoupper( $level ), $title );

		$body = sprintf(
			"PearBlog Engine Alert\n" .
			"Level: %s\n" .
			"Time: %s\n\n" .
			"%s\n\n" .
			"--\nPearBlog Engine v6.0 · %s",
			strtoupper( $level ),
			current_time( 'mysql' ),
			$message,
			home_url()
		);

		return wp_mail( $to, $subject, $body );
	}
}

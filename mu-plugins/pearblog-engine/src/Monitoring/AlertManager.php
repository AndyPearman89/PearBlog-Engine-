<?php
/**
 * Alert manager – sends notifications via Slack or Discord webhooks.
 *
 * Configuration is stored in WordPress options:
 *   pearblog_alert_slack_webhook   – Slack incoming-webhook URL
 *   pearblog_alert_discord_webhook – Discord webhook URL
 *   pearblog_alert_email           – Optional fallback e-mail address
 *
 * Severity levels: info, warning, error, critical
 *
 * @package PearBlogEngine\Monitoring
 */

declare(strict_types=1);

namespace PearBlogEngine\Monitoring;

/**
 * Dispatches structured alert messages to configured notification channels.
 */
class AlertManager {

	public const LEVEL_INFO     = 'info';
	public const LEVEL_WARNING  = 'warning';
	public const LEVEL_ERROR    = 'error';
	public const LEVEL_CRITICAL = 'critical';

	/** Slack colour attachments mapped to severity level. */
	private const SLACK_COLORS = [
		self::LEVEL_INFO     => '#36a64f',
		self::LEVEL_WARNING  => '#ffc107',
		self::LEVEL_ERROR    => '#e53935',
		self::LEVEL_CRITICAL => '#b71c1c',
	];

	/** Discord embed colours (decimal) mapped to severity level. */
	private const DISCORD_COLORS = [
		self::LEVEL_INFO     => 3779158,
		self::LEVEL_WARNING  => 16750848,
		self::LEVEL_ERROR    => 15019040,
		self::LEVEL_CRITICAL => 12010260,
	];

	/** WordPress option key prefix for deduplication. */
	private const DEDUP_TRANSIENT_PREFIX = 'pb_alert_dedup_';

	/** @var string */
	private string $slack_webhook;

	/** @var string */
	private string $discord_webhook;

	/** @var string */
	private string $alert_email;

	public function __construct() {
		$this->slack_webhook   = (string) get_option( 'pearblog_alert_slack_webhook', '' );
		$this->discord_webhook = (string) get_option( 'pearblog_alert_discord_webhook', '' );
		$this->alert_email     = (string) get_option( 'pearblog_alert_email', '' );
	}

	/**
	 * Send an alert to all configured channels.
	 *
	 * @param string $title    Short title / headline for the alert.
	 * @param string $message  Detailed alert body.
	 * @param string $level    Severity: info, warning, error, critical.
	 * @param array  $context  Optional key-value pairs added as fields.
	 * @param bool   $dedup    When true, suppress duplicate alerts within 5 min.
	 */
	public function alert(
		string $title,
		string $message,
		string $level = self::LEVEL_ERROR,
		array $context = [],
		bool $dedup = true
	): void {
		if ( $dedup && $this->is_duplicate( $title, $level ) ) {
			return;
		}

		$site_name = get_bloginfo( 'name' );
		$site_url  = get_site_url();

		$context = array_merge(
			[
				'Site'  => "{$site_name} ({$site_url})",
				'Time'  => current_time( 'Y-m-d H:i:s T' ),
				'Level' => strtoupper( $level ),
			],
			$context
		);

		if ( '' !== $this->slack_webhook ) {
			$this->send_slack( $title, $message, $level, $context );
		}

		if ( '' !== $this->discord_webhook ) {
			$this->send_discord( $title, $message, $level, $context );
		}

		if ( '' !== $this->alert_email ) {
			$this->send_email( $title, $message, $level, $context );
		}

		// Always log to PHP error_log as a baseline.
		error_log( sprintf(
			'PearBlog Alert [%s] %s: %s',
			strtoupper( $level ),
			$title,
			$message
		) );
	}

	/**
	 * Shorthand for pipeline/content error alerts.
	 */
	public function pipeline_error( string $message, array $context = [] ): void {
		$this->alert( 'Pipeline Error', $message, self::LEVEL_ERROR, $context );
	}

	/**
	 * Shorthand for critical system alerts (circuit breaker open, etc.).
	 */
	public function critical( string $title, string $message, array $context = [] ): void {
		$this->alert( $title, $message, self::LEVEL_CRITICAL, $context );
	}

	/**
	 * Shorthand for informational alerts (new article published, etc.).
	 */
	public function info( string $title, string $message, array $context = [] ): void {
		$this->alert( $title, $message, self::LEVEL_INFO, $context, false );
	}

	// -----------------------------------------------------------------------
	// Channel implementations
	// -----------------------------------------------------------------------

	private function send_slack( string $title, string $message, string $level, array $context ): void {
		$fields = [];
		foreach ( $context as $key => $value ) {
			$fields[] = [
				'title' => $key,
				'value' => (string) $value,
				'short' => mb_strlen( (string) $value ) <= 40,
			];
		}

		$payload = [
			'attachments' => [
				[
					'color'      => self::SLACK_COLORS[ $level ] ?? '#888888',
					'title'      => $title,
					'text'       => $message,
					'fields'     => $fields,
					'footer'     => 'PearBlog Engine',
					'ts'         => time(),
					'mrkdwn_in'  => [ 'text' ],
				],
			],
		];

		$this->post_json( $this->slack_webhook, $payload );
	}

	private function send_discord( string $title, string $message, string $level, array $context ): void {
		$fields = [];
		foreach ( $context as $key => $value ) {
			$fields[] = [
				'name'   => $key,
				'value'  => (string) $value,
				'inline' => mb_strlen( (string) $value ) <= 40,
			];
		}

		$payload = [
			'embeds' => [
				[
					'title'       => $title,
					'description' => $message,
					'color'       => self::DISCORD_COLORS[ $level ] ?? 8947848,
					'fields'      => $fields,
					'footer'      => [ 'text' => 'PearBlog Engine' ],
					'timestamp'   => gmdate( 'c' ),
				],
			],
		];

		$this->post_json( $this->discord_webhook, $payload );
	}

	private function send_email( string $title, string $message, string $level, array $context ): void {
		$subject = sprintf( '[PearBlog %s] %s', strtoupper( $level ), $title );

		$body  = $message . "\n\n";
		foreach ( $context as $key => $value ) {
			$body .= "{$key}: {$value}\n";
		}

		wp_mail(
			$this->alert_email,
			$subject,
			$body,
			[ 'Content-Type: text/plain; charset=UTF-8' ]
		);
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function post_json( string $url, array $payload ): void {
		$response = wp_remote_post( $url, [
			'timeout'     => 5,
			'headers'     => [ 'Content-Type' => 'application/json' ],
			'body'        => wp_json_encode( $payload ),
			'blocking'    => false, // Fire-and-forget.
		] );

		if ( is_wp_error( $response ) ) {
			error_log( 'PearBlog AlertManager: webhook delivery failed – ' . $response->get_error_message() );
		}
	}

	/**
	 * Return true when an identical alert (same title + level) was already
	 * sent within the last 5 minutes (deduplication window).
	 */
	private function is_duplicate( string $title, string $level ): bool {
		$key = self::DEDUP_TRANSIENT_PREFIX . substr( md5( $title . $level ), 0, 16 );
		if ( get_transient( $key ) !== false ) {
			return true;
		}
		set_transient( $key, 1, 5 * MINUTE_IN_SECONDS );
		return false;
	}
}

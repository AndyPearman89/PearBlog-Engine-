<?php
/**
 * Alert manager – sends notifications via Slack, Discord, email, or PagerDuty.
 *
 * Configuration is stored in WordPress options:
 *   pearblog_alert_slack_webhook    – Slack incoming-webhook URL
 *   pearblog_alert_discord_webhook  – Discord webhook URL
 *   pearblog_alert_email            – Optional fallback e-mail address
 *   pearblog_alert_pagerduty_key    – PagerDuty Events API v2 routing key
 *   pearblog_alert_thresholds       – JSON-encoded threshold rules
 *   pearblog_alert_silences         – JSON-encoded active silences
 *   pearblog_alert_templates        – JSON-encoded custom message templates
 *   pearblog_alert_history          – JSON-encoded ring buffer of recent alerts
 *
 * Severity levels: info, warning, error, critical
 *
 * @package PearBlogEngine\Monitoring
 */

declare(strict_types=1);

namespace PearBlogEngine\Monitoring;

/**
 * Dispatches structured alert messages to configured notification channels.
 *
 * Features:
 *  - Configurable thresholds per alert title/type
 *  - Alert priority levels (P0–P3)
 *  - Deduplication with configurable window
 *  - Silence / mute support (by title pattern or level)
 *  - Alert escalation rules
 *  - PagerDuty integration (P0 alerts)
 *  - Custom message templates
 *  - Alert history ring buffer (last 100 alerts)
 */
class AlertManager {

	/** Severity level constants. */
	public const LEVEL_INFO     = 'info';
	public const LEVEL_WARNING  = 'warning';
	public const LEVEL_ERROR    = 'error';
	public const LEVEL_CRITICAL = 'critical';

	/** Priority constants (P0 = highest, P3 = lowest). */
	public const PRIORITY_P0 = 0;
	public const PRIORITY_P1 = 1;
	public const PRIORITY_P2 = 2;
	public const PRIORITY_P3 = 3;

	/** Default deduplication window in seconds. */
	public const DEFAULT_DEDUP_SECONDS = 300;

	/** Maximum alerts to keep in history. */
	public const HISTORY_MAX = 100;

	/** WordPress option key for alert history. */
	public const OPTION_HISTORY = 'pearblog_alert_history';

	/** WordPress option key for active silences. */
	public const OPTION_SILENCES = 'pearblog_alert_silences';

	/** WordPress option key for thresholds. */
	public const OPTION_THRESHOLDS = 'pearblog_alert_thresholds';

	/** WordPress option key for templates. */
	public const OPTION_TEMPLATES = 'pearblog_alert_templates';

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

	/** Priority→level escalation map used when thresholds are exceeded. */
	private const ESCALATION_LEVELS = [
		self::PRIORITY_P0 => self::LEVEL_CRITICAL,
		self::PRIORITY_P1 => self::LEVEL_ERROR,
		self::PRIORITY_P2 => self::LEVEL_WARNING,
		self::PRIORITY_P3 => self::LEVEL_INFO,
	];

	/** WordPress transient key prefix for deduplication. */
	private const DEDUP_TRANSIENT_PREFIX = 'pb_alert_dedup_';

	/** @var string */
	private string $slack_webhook;

	/** @var string */
	private string $discord_webhook;

	/** @var string */
	private string $alert_email;

	/** @var string PagerDuty Events API v2 routing key. */
	private string $pagerduty_key;

	/**
	 * Threshold rules.
	 * Array of [ 'title_pattern' => string, 'level' => string, 'max_per_hour' => int ]
	 *
	 * @var array<int, array<string,mixed>>
	 */
	private array $thresholds;

	/**
	 * Active silences.
	 * Array of [ 'pattern' => string, 'level' => string|null, 'until' => int ]
	 *
	 * @var array<int, array<string,mixed>>
	 */
	private array $silences;

	/**
	 * Custom message templates keyed by title_pattern.
	 * Array of [ 'title_pattern' => string, 'slack_template' => string, 'email_template' => string ]
	 *
	 * @var array<int, array<string,string>>
	 */
	private array $templates;

	public function __construct() {
		$this->slack_webhook   = (string) get_option( 'pearblog_alert_slack_webhook', '' );
		$this->discord_webhook = (string) get_option( 'pearblog_alert_discord_webhook', '' );
		$this->alert_email     = (string) get_option( 'pearblog_alert_email', '' );
		$this->pagerduty_key   = (string) get_option( 'pearblog_alert_pagerduty_key', '' );
		$this->thresholds      = $this->decode_json_option( self::OPTION_THRESHOLDS );
		$this->silences        = $this->decode_json_option( self::OPTION_SILENCES );
		$this->templates       = $this->decode_json_option( self::OPTION_TEMPLATES );
	}

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Send an alert to all configured channels.
	 *
	 * @param string $title    Short title / headline for the alert.
	 * @param string $message  Detailed alert body.
	 * @param string $level    Severity: info, warning, error, critical.
	 * @param array  $context  Optional key-value pairs added as fields.
	 * @param bool   $dedup    When true, suppress duplicate alerts within the configured window.
	 * @param int    $priority Priority level (P0–P3); P0 also triggers PagerDuty.
	 */
	public function alert(
		string $title,
		string $message,
		string $level = self::LEVEL_ERROR,
		array $context = [],
		bool $dedup = true,
		int $priority = self::PRIORITY_P2
	): void {
		// Apply threshold-based escalation.
		$level = $this->apply_threshold( $title, $level );

		// Enforce the minimum severity level required by the given priority.
		$min_for_priority = self::ESCALATION_LEVELS[ $priority ] ?? self::LEVEL_INFO;
		$level            = $this->enforce_minimum_level( $level, $min_for_priority );

		// Check silence rules.
		if ( $this->is_silenced( $title, $level ) ) {
			return;
		}

		// Deduplication.
		if ( $dedup && $this->is_duplicate( $title, $level ) ) {
			return;
		}

		$site_name = get_bloginfo( 'name' );
		$site_url  = get_site_url();

		$full_context = array_merge(
			[
				'Site'     => "{$site_name} ({$site_url})",
				'Time'     => current_time( 'Y-m-d H:i:s T' ),
				'Level'    => strtoupper( $level ),
				'Priority' => "P{$priority}",
			],
			$context
		);

		// Apply custom template if available.
		$message = $this->apply_template( $title, $message, $full_context );

		if ( '' !== $this->slack_webhook ) {
			$this->send_slack( $title, $message, $level, $full_context );
		}

		if ( '' !== $this->discord_webhook ) {
			$this->send_discord( $title, $message, $level, $full_context );
		}

		if ( '' !== $this->alert_email ) {
			$this->send_email( $title, $message, $level, $full_context );
		}

		// P0 alerts always trigger PagerDuty if configured.
		if ( self::PRIORITY_P0 === $priority && '' !== $this->pagerduty_key ) {
			$this->send_pagerduty( $title, $message, $level, $full_context );
		}

		// Always log to PHP error_log as a baseline.
		error_log( sprintf(
			'PearBlog Alert [%s] %s: %s',
			strtoupper( $level ),
			$title,
			$message
		) );

		// Record in history.
		$this->record_history( $title, $message, $level, $priority );
	}

	/**
	 * Shorthand for pipeline/content error alerts.
	 *
	 * @param string $message Pipeline error message.
	 * @param array  $context Additional context.
	 */
	public function pipeline_error( string $message, array $context = [] ): void {
		$this->alert( 'Pipeline Error', $message, self::LEVEL_ERROR, $context, true, self::PRIORITY_P1 );
	}

	/**
	 * Shorthand for critical system alerts (circuit breaker open, etc.).
	 *
	 * @param string $title   Alert title.
	 * @param string $message Alert message.
	 * @param array  $context Additional context.
	 */
	public function critical( string $title, string $message, array $context = [] ): void {
		$this->alert( $title, $message, self::LEVEL_CRITICAL, $context, true, self::PRIORITY_P0 );
	}

	/**
	 * Shorthand for informational alerts (new article published, etc.).
	 *
	 * @param string $title   Alert title.
	 * @param string $message Alert message.
	 * @param array  $context Additional context.
	 */
	public function info( string $title, string $message, array $context = [] ): void {
		$this->alert( $title, $message, self::LEVEL_INFO, $context, false, self::PRIORITY_P3 );
	}

	// -----------------------------------------------------------------------
	// Silence management
	// -----------------------------------------------------------------------

	/**
	 * Add a silence rule that suppresses matching alerts until the given timestamp.
	 *
	 * @param string      $pattern Title substring or regex pattern to match.
	 * @param int         $until   Unix timestamp when the silence expires.
	 * @param string|null $level   Optionally restrict silence to a specific level.
	 */
	public function add_silence( string $pattern, int $until, ?string $level = null ): void {
		$silences = $this->decode_json_option( self::OPTION_SILENCES );
		// Remove expired silences.
		$now      = time();
		$silences = array_values( array_filter( $silences, fn( $s ) => (int) $s['until'] > $now ) );

		$silences[] = [
			'pattern' => $pattern,
			'level'   => $level,
			'until'   => $until,
		];
		update_option( self::OPTION_SILENCES, wp_json_encode( $silences ) );
		$this->silences = $silences;
	}

	/**
	 * Remove all silence rules that match the given pattern.
	 *
	 * @param string $pattern Pattern to remove.
	 */
	public function remove_silence( string $pattern ): void {
		$silences       = array_values( array_filter(
			$this->decode_json_option( self::OPTION_SILENCES ),
			fn( $s ) => $s['pattern'] !== $pattern
		) );
		update_option( self::OPTION_SILENCES, wp_json_encode( $silences ) );
		$this->silences = $silences;
	}

	/**
	 * Get all currently active (non-expired) silences.
	 *
	 * @return array<int, array<string,mixed>>
	 */
	public function get_active_silences(): array {
		$now = time();
		return array_values( array_filter(
			$this->decode_json_option( self::OPTION_SILENCES ),
			fn( $s ) => (int) $s['until'] > $now
		) );
	}

	// -----------------------------------------------------------------------
	// Threshold management
	// -----------------------------------------------------------------------

	/**
	 * Add or update a threshold rule.
	 *
	 * When the number of identical alerts exceeds max_per_hour, the level
	 * is escalated to the next severity.
	 *
	 * @param string $title_pattern Exact title or substring to match.
	 * @param string $level         Minimum level this threshold applies to.
	 * @param int    $max_per_hour  Escalation trigger count per hour.
	 */
	public function set_threshold( string $title_pattern, string $level, int $max_per_hour ): void {
		$thresholds = $this->decode_json_option( self::OPTION_THRESHOLDS );
		// Update existing or append.
		$found = false;
		foreach ( $thresholds as &$t ) {
			if ( $t['title_pattern'] === $title_pattern && $t['level'] === $level ) {
				$t['max_per_hour'] = $max_per_hour;
				$found = true;
				break;
			}
		}
		if ( ! $found ) {
			$thresholds[] = compact( 'title_pattern', 'level', 'max_per_hour' );
		}
		update_option( self::OPTION_THRESHOLDS, wp_json_encode( $thresholds ) );
		$this->thresholds = $thresholds;
	}

	// -----------------------------------------------------------------------
	// Template management
	// -----------------------------------------------------------------------

	/**
	 * Register a custom message template.
	 *
	 * Templates may use {title}, {message}, {level}, {site} placeholders.
	 *
	 * @param string $title_pattern  Exact title to match.
	 * @param string $slack_template Slack message template.
	 * @param string $email_template Email body template.
	 */
	public function set_template( string $title_pattern, string $slack_template, string $email_template ): void {
		$templates = $this->decode_json_option( self::OPTION_TEMPLATES );
		$found = false;
		foreach ( $templates as &$t ) {
			if ( $t['title_pattern'] === $title_pattern ) {
				$t['slack_template'] = $slack_template;
				$t['email_template'] = $email_template;
				$found = true;
				break;
			}
		}
		if ( ! $found ) {
			$templates[] = compact( 'title_pattern', 'slack_template', 'email_template' );
		}
		update_option( self::OPTION_TEMPLATES, wp_json_encode( $templates ) );
		$this->templates = $templates;
	}

	// -----------------------------------------------------------------------
	// Alert history
	// -----------------------------------------------------------------------

	/**
	 * Return the most recent alerts from the history ring buffer.
	 *
	 * @param int $limit Maximum number of entries to return (default: 50).
	 * @return array<int, array<string,mixed>>
	 */
	public function get_history( int $limit = 50 ): array {
		$history = $this->decode_json_option( self::OPTION_HISTORY );
		return array_slice( array_reverse( $history ), 0, $limit );
	}

	/**
	 * Clear the alert history.
	 */
	public function clear_history(): void {
		update_option( self::OPTION_HISTORY, '[]' );
	}

	// -----------------------------------------------------------------------
	// Channel implementations
	// -----------------------------------------------------------------------

	/**
	 * Send alert to Slack via incoming webhook.
	 *
	 * @param string $title   Alert title.
	 * @param string $message Alert message.
	 * @param string $level   Severity level.
	 * @param array  $context Contextual key-value pairs.
	 */
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

	/**
	 * Send alert to Discord via webhook embed.
	 *
	 * @param string $title   Alert title.
	 * @param string $message Alert message.
	 * @param string $level   Severity level.
	 * @param array  $context Contextual key-value pairs.
	 */
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

	/**
	 * Send alert via WordPress wp_mail().
	 *
	 * @param string $title   Alert title.
	 * @param string $message Alert message.
	 * @param string $level   Severity level.
	 * @param array  $context Contextual key-value pairs.
	 */
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

	/**
	 * Trigger a PagerDuty Events API v2 incident for P0 alerts.
	 *
	 * @param string $title   Alert title.
	 * @param string $message Alert message.
	 * @param string $level   Severity level.
	 * @param array  $context Contextual key-value pairs.
	 */
	private function send_pagerduty( string $title, string $message, string $level, array $context ): void {
		$severity_map = [
			self::LEVEL_INFO     => 'info',
			self::LEVEL_WARNING  => 'warning',
			self::LEVEL_ERROR    => 'error',
			self::LEVEL_CRITICAL => 'critical',
		];

		$payload = [
			'routing_key'  => $this->pagerduty_key,
			'event_action' => 'trigger',
			'payload'      => [
				'summary'   => $title . ': ' . $message,
				'severity'  => $severity_map[ $level ] ?? 'error',
				'source'    => get_site_url(),
				'timestamp' => gmdate( 'c' ),
				'custom_details' => $context,
			],
		];

		wp_remote_post( 'https://events.pagerduty.com/v2/enqueue', [
			'timeout' => 5,
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( $payload ),
			'blocking' => false,
		] );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Post a JSON payload to a URL (non-blocking fire-and-forget).
	 *
	 * @param string               $url     Target webhook URL.
	 * @param array<string, mixed> $payload Data to encode and post.
	 */
	private function post_json( string $url, array $payload ): void {
		$response = wp_remote_post( $url, [
			'timeout'  => 5,
			'headers'  => [ 'Content-Type' => 'application/json' ],
			'body'     => wp_json_encode( $payload ),
			'blocking' => false, // Fire-and-forget.
		] );

		if ( is_wp_error( $response ) ) {
			error_log( 'PearBlog AlertManager: webhook delivery failed – ' . $response->get_error_message() );
		}
	}

	/**
	 * Return true when an identical alert was already sent within the dedup window.
	 *
	 * @param string $title Alert title.
	 * @param string $level Severity level.
	 * @return bool
	 */
	private function is_duplicate( string $title, string $level ): bool {
		$window = (int) get_option( 'pearblog_alert_dedup_seconds', self::DEFAULT_DEDUP_SECONDS );
		$key    = self::DEDUP_TRANSIENT_PREFIX . substr( md5( $title . $level ), 0, 16 );
		if ( get_transient( $key ) !== false ) {
			return true;
		}
		set_transient( $key, 1, $window );
		return false;
	}

	/**
	 * Return true when any active silence rule matches this title+level.
	 *
	 * @param string $title Alert title.
	 * @param string $level Severity level.
	 * @return bool
	 */
	private function is_silenced( string $title, string $level ): bool {
		$now = time();
		foreach ( $this->silences as $silence ) {
			if ( (int) $silence['until'] <= $now ) {
				continue;
			}
			$pattern = (string) $silence['pattern'];
			if ( null !== $silence['level'] && $silence['level'] !== $level ) {
				continue;
			}
			// Support both literal substring and regex.
			$is_regex = ( @preg_match( $pattern, '' ) !== false && str_starts_with( $pattern, '/' ) );
			if ( $is_regex ) {
				if ( preg_match( $pattern, $title ) ) {
					return true;
				}
			} elseif ( str_contains( $title, $pattern ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Ensure $current is at least as severe as $minimum.
	 *
	 * Used together with ESCALATION_LEVELS to guarantee that a high-priority
	 * alert is never sent at a lower severity than its priority warrants.
	 *
	 * @param string $current The severity level that was requested or escalated.
	 * @param string $minimum The minimum acceptable severity for this priority.
	 * @return string The higher of the two severity levels.
	 */
	private function enforce_minimum_level( string $current, string $minimum ): string {
		$order       = [ self::LEVEL_INFO, self::LEVEL_WARNING, self::LEVEL_ERROR, self::LEVEL_CRITICAL ];
		$current_idx = array_search( $current, $order, true );
		$minimum_idx = array_search( $minimum, $order, true );

		if ( false === $current_idx || false === $minimum_idx ) {
			return $current;
		}

		return $current_idx >= $minimum_idx ? $current : $minimum;
	}

	/**
	 * Apply threshold escalation rules.
	 *
	 * Counts the number of times this alert has fired in the last hour.
	 * If the count exceeds the threshold's max_per_hour, the level is
	 * escalated to the next severity.
	 *
	 * @param string $title Current alert title.
	 * @param string $level Current severity level.
	 * @return string Potentially escalated level.
	 */
	private function apply_threshold( string $title, string $level ): string {
		foreach ( $this->thresholds as $threshold ) {
			if ( $threshold['level'] !== $level ) {
				continue;
			}
			if ( ! str_contains( $title, (string) $threshold['title_pattern'] ) ) {
				continue;
			}
			// Count occurrences in the last hour.
			$count_key = 'pb_alert_count_' . substr( md5( $title . $level ), 0, 16 );
			$count     = (int) get_transient( $count_key );
			set_transient( $count_key, $count + 1, HOUR_IN_SECONDS );
			if ( $count + 1 >= (int) $threshold['max_per_hour'] ) {
				// Escalate.
				$levels = [ self::LEVEL_INFO, self::LEVEL_WARNING, self::LEVEL_ERROR, self::LEVEL_CRITICAL ];
				$idx    = array_search( $level, $levels, true );
				if ( false !== $idx && $idx < count( $levels ) - 1 ) {
					return $levels[ $idx + 1 ];
				}
			}
		}
		return $level;
	}

	/**
	 * Apply a matching template to the message, if one exists.
	 *
	 * @param string $title   Alert title.
	 * @param string $message Original message.
	 * @param array  $context Full context array.
	 * @return string Potentially overridden message.
	 */
	private function apply_template( string $title, string $message, array $context ): string {
		foreach ( $this->templates as $template ) {
			if ( (string) $template['title_pattern'] !== $title ) {
				continue;
			}
			$tpl = (string) ( $template['slack_template'] ?? '' );
			if ( '' === $tpl ) {
				continue;
			}
			$replacements = [
				'{title}'   => $title,
				'{message}' => $message,
				'{level}'   => $context['Level'] ?? '',
				'{site}'    => $context['Site'] ?? '',
				'{time}'    => $context['Time'] ?? '',
			];
			return strtr( $tpl, $replacements );
		}
		return $message;
	}

	/**
	 * Append an entry to the alert history ring buffer.
	 *
	 * @param string $title    Alert title.
	 * @param string $message  Alert message.
	 * @param string $level    Severity level.
	 * @param int    $priority Priority level.
	 */
	private function record_history( string $title, string $message, string $level, int $priority ): void {
		$history   = $this->decode_json_option( self::OPTION_HISTORY );
		$history[] = [
			'title'     => $title,
			'message'   => $message,
			'level'     => $level,
			'priority'  => $priority,
			'timestamp' => time(),
			'site'      => get_site_url(),
		];
		// Trim to ring buffer size.
		if ( count( $history ) > self::HISTORY_MAX ) {
			$history = array_slice( $history, -self::HISTORY_MAX );
		}
		update_option( self::OPTION_HISTORY, wp_json_encode( $history ) );
	}

	/**
	 * Decode a JSON-encoded option into an array, returning [] on failure.
	 *
	 * @param string $option_key WordPress option key.
	 * @return array<int, array<string, mixed>>
	 */
	private function decode_json_option( string $option_key ): array {
		$raw  = (string) get_option( $option_key, '[]' );
		$data = json_decode( $raw, true );
		return is_array( $data ) ? $data : [];
	}
}

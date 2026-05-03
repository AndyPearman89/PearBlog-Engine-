<?php
/**
 * Event Tracking System
 *
 * Tracks all user interactions with articles for performance analysis.
 *
 * @package PearBlog\Poradnik
 */

namespace PearBlog\Poradnik;

/**
 * Class EventTracker
 *
 * Tracks views, scrolls, CTA clicks, leads, and revenue events.
 */
class EventTracker {
	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * Track a page view event.
	 *
	 * @param int    $post_id Post ID.
	 * @param int    $article_id Article ID.
	 * @param string $session_id Session identifier.
	 * @param array  $utm Optional UTM parameters.
	 * @return int|false Event ID or false on failure.
	 */
	public function track_view( int $post_id, int $article_id, string $session_id, array $utm = array() ) {
		return $this->track_event(
			'view',
			array(
				'post_id'    => $post_id,
				'article_id' => $article_id,
				'session_id' => $session_id,
				'utm'        => $utm,
			)
		);
	}

	/**
	 * Track a scroll event.
	 *
	 * @param int    $post_id Post ID.
	 * @param int    $article_id Article ID.
	 * @param string $session_id Session identifier.
	 * @param int    $depth Scroll depth percentage (0-100).
	 * @param int    $time_seconds Time spent in seconds.
	 * @return int|false Event ID or false on failure.
	 */
	public function track_scroll( int $post_id, int $article_id, string $session_id, int $depth, int $time_seconds ) {
		return $this->track_event(
			'scroll',
			array(
				'post_id'      => $post_id,
				'article_id'   => $article_id,
				'session_id'   => $session_id,
				'event_data'   => array(
					'depth'        => $depth,
					'time_seconds' => $time_seconds,
				),
			)
		);
	}

	/**
	 * Track a CTA click event.
	 *
	 * @param int    $post_id Post ID.
	 * @param int    $article_id Article ID.
	 * @param string $session_id Session identifier.
	 * @param string $cta_text CTA text/anchor.
	 * @param string $target_url Target URL.
	 * @return int|false Event ID or false on failure.
	 */
	public function track_cta_click( int $post_id, int $article_id, string $session_id, string $cta_text, string $target_url ) {
		return $this->track_event(
			'cta_click',
			array(
				'post_id'    => $post_id,
				'article_id' => $article_id,
				'session_id' => $session_id,
				'event_data' => array(
					'cta_text'   => $cta_text,
					'target_url' => $target_url,
				),
			)
		);
	}

	/**
	 * Track a lead conversion event.
	 *
	 * @param int    $post_id Post ID.
	 * @param int    $article_id Article ID.
	 * @param string $session_id Session identifier.
	 * @param int    $user_id Optional user ID.
	 * @param array  $lead_data Lead form data.
	 * @return int|false Event ID or false on failure.
	 */
	public function track_lead( int $post_id, int $article_id, string $session_id, int $user_id = 0, array $lead_data = array() ) {
		return $this->track_event(
			'lead',
			array(
				'post_id'    => $post_id,
				'article_id' => $article_id,
				'session_id' => $session_id,
				'user_id'    => $user_id,
				'event_data' => $lead_data,
			)
		);
	}

	/**
	 * Track a revenue event.
	 *
	 * @param int    $post_id Post ID.
	 * @param int    $article_id Article ID.
	 * @param string $session_id Session identifier.
	 * @param float  $amount Revenue amount.
	 * @param string $currency Currency code.
	 * @param array  $metadata Additional metadata.
	 * @return int|false Event ID or false on failure.
	 */
	public function track_revenue( int $post_id, int $article_id, string $session_id, float $amount, string $currency = 'PLN', array $metadata = array() ) {
		return $this->track_event(
			'revenue',
			array(
				'post_id'    => $post_id,
				'article_id' => $article_id,
				'session_id' => $session_id,
				'event_data' => array_merge(
					array(
						'amount'   => $amount,
						'currency' => $currency,
					),
					$metadata
				),
			)
		);
	}

	/**
	 * Track a generic event.
	 *
	 * @param string $event_type Event type (view, scroll, cta_click, lead, revenue).
	 * @param array  $data Event data.
	 * @return int|false Event ID or false on failure.
	 */
	private function track_event( string $event_type, array $data ) {
		$table_name = $this->wpdb->prefix . 'pearblog_events';

		$insert_data = array(
			'event_type'   => $event_type,
			'article_id'   => $data['article_id'] ?? null,
			'post_id'      => $data['post_id'] ?? null,
			'user_id'      => $data['user_id'] ?? null,
			'session_id'   => $data['session_id'] ?? null,
			'ip_hash'      => $this->get_ip_hash(),
			'event_data'   => isset( $data['event_data'] ) ? wp_json_encode( $data['event_data'] ) : null,
			'referrer'     => $this->get_referrer(),
			'utm_source'   => $data['utm']['source'] ?? $_GET['utm_source'] ?? null,
			'utm_medium'   => $data['utm']['medium'] ?? $_GET['utm_medium'] ?? null,
			'utm_campaign' => $data['utm']['campaign'] ?? $_GET['utm_campaign'] ?? null,
			'created_at'   => current_time( 'mysql' ),
		);

		$result = $this->wpdb->insert(
			$table_name,
			$insert_data,
			array( '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return $result ? $this->wpdb->insert_id : false;
	}

	/**
	 * Get aggregated statistics for an article.
	 *
	 * @param int    $article_id Article ID.
	 * @param string $start_date Start date (Y-m-d).
	 * @param string $end_date End date (Y-m-d).
	 * @return array Statistics.
	 */
	public function get_article_stats( int $article_id, string $start_date, string $end_date ): array {
		$table_name = $this->wpdb->prefix . 'pearblog_events';

		// Get view count
		$views = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name}
				WHERE article_id = %d
				AND event_type = 'view'
				AND DATE(created_at) BETWEEN %s AND %s",
				$article_id,
				$start_date,
				$end_date
			)
		);

		// Get unique visitors
		$unique_visitors = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(DISTINCT session_id) FROM {$table_name}
				WHERE article_id = %d
				AND event_type = 'view'
				AND DATE(created_at) BETWEEN %s AND %s",
				$article_id,
				$start_date,
				$end_date
			)
		);

		// Get CTA clicks
		$cta_clicks = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name}
				WHERE article_id = %d
				AND event_type = 'cta_click'
				AND DATE(created_at) BETWEEN %s AND %s",
				$article_id,
				$start_date,
				$end_date
			)
		);

		// Get leads
		$leads = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name}
				WHERE article_id = %d
				AND event_type = 'lead'
				AND DATE(created_at) BETWEEN %s AND %s",
				$article_id,
				$start_date,
				$end_date
			)
		);

		// Get total revenue
		$revenue = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT SUM(CAST(JSON_EXTRACT(event_data, '$.amount') AS DECIMAL(10,2)))
				FROM {$table_name}
				WHERE article_id = %d
				AND event_type = 'revenue'
				AND DATE(created_at) BETWEEN %s AND %s",
				$article_id,
				$start_date,
				$end_date
			)
		);

		// Get average scroll depth
		$avg_scroll = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT AVG(CAST(JSON_EXTRACT(event_data, '$.depth') AS DECIMAL(5,2)))
				FROM {$table_name}
				WHERE article_id = %d
				AND event_type = 'scroll'
				AND DATE(created_at) BETWEEN %s AND %s",
				$article_id,
				$start_date,
				$end_date
			)
		);

		// Get average time spent
		$avg_time = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT AVG(CAST(JSON_EXTRACT(event_data, '$.time_seconds') AS UNSIGNED))
				FROM {$table_name}
				WHERE article_id = %d
				AND event_type = 'scroll'
				AND DATE(created_at) BETWEEN %s AND %s",
				$article_id,
				$start_date,
				$end_date
			)
		);

		return array(
			'views'            => (int) $views,
			'unique_visitors'  => (int) $unique_visitors,
			'cta_clicks'       => (int) $cta_clicks,
			'cta_ctr'          => $views > 0 ? round( ( $cta_clicks / $views ) * 100, 4 ) : 0,
			'leads'            => (int) $leads,
			'lead_conversion'  => $views > 0 ? round( ( $leads / $views ) * 100, 4 ) : 0,
			'revenue'          => round( (float) $revenue, 2 ),
			'avg_scroll_depth' => round( (float) $avg_scroll, 2 ),
			'avg_time_seconds' => (int) $avg_time,
			'bounce_rate'      => $this->calculate_bounce_rate( $article_id, $start_date, $end_date ),
		);
	}

	/**
	 * Calculate bounce rate for an article.
	 *
	 * @param int    $article_id Article ID.
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 * @return float Bounce rate percentage.
	 */
	private function calculate_bounce_rate( int $article_id, string $start_date, string $end_date ): float {
		$table_name = $this->wpdb->prefix . 'pearblog_events';

		// Get sessions with only one view event
		$bounced_sessions = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(DISTINCT session_id)
				FROM {$table_name}
				WHERE article_id = %d
				AND DATE(created_at) BETWEEN %s AND %s
				AND session_id IN (
					SELECT session_id
					FROM {$table_name}
					WHERE article_id = %d
					GROUP BY session_id
					HAVING COUNT(*) = 1
				)",
				$article_id,
				$start_date,
				$end_date,
				$article_id
			)
		);

		// Get total sessions
		$total_sessions = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(DISTINCT session_id)
				FROM {$table_name}
				WHERE article_id = %d
				AND DATE(created_at) BETWEEN %s AND %s",
				$article_id,
				$start_date,
				$end_date
			)
		);

		if ( $total_sessions == 0 ) {
			return 0;
		}

		return round( ( $bounced_sessions / $total_sessions ) * 100, 2 );
	}

	/**
	 * Get hashed IP address.
	 *
	 * @return string Hashed IP.
	 */
	private function get_ip_hash(): string {
		$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		return hash( 'sha256', $ip . wp_salt() );
	}

	/**
	 * Get referrer URL.
	 *
	 * @return string|null Referrer URL.
	 */
	private function get_referrer(): ?string {
		return $_SERVER['HTTP_REFERER'] ?? null;
	}

	/**
	 * Generate or retrieve session ID from cookie.
	 *
	 * @return string Session ID.
	 */
	public static function get_session_id(): string {
		$cookie_name = 'poradnik_session_id';

		if ( isset( $_COOKIE[ $cookie_name ] ) ) {
			return sanitize_text_field( $_COOKIE[ $cookie_name ] );
		}

		$session_id = wp_generate_uuid4();
		setcookie( $cookie_name, $session_id, time() + ( 30 * DAY_IN_SECONDS ), '/', '', is_ssl(), true );

		return $session_id;
	}
}

<?php
/**
 * Content ROI Engine – combines GA4 traffic, AI cost and revenue data
 * into a unified return-on-investment dashboard.
 *
 * Metrics produced per article:
 *  - Sessions (from GA4)
 *  - AI generation cost (USD cents)
 *  - Revenue (from RevenueTracker)
 *  - ROI (%) and break-even traffic threshold
 *  - Revenue per 1 000 sessions (RPM equivalent)
 *
 * Cron: weekly refresh (pearblog_roi_refresh) stores snapshot in
 * `pearblog_roi_snapshot` WP option.
 *
 * REST endpoint:
 *   GET /pearblog/v1/roi          – full snapshot
 *   GET /pearblog/v1/roi/{post_id} – single article ROI
 *
 * @package PearBlogEngine\Analytics
 */

declare(strict_types=1);

namespace PearBlogEngine\Analytics;

use PearBlogEngine\Monetization\RevenueTracker;

/**
 * Unified ROI engine linking traffic, cost and revenue.
 */
class ContentROIEngine {

	/** WP option that stores the cached ROI snapshot. */
	public const OPTION_SNAPSHOT = 'pearblog_roi_snapshot';

	/** Cron hook name. */
	private const CRON_HOOK = 'pearblog_roi_refresh';

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Hook into WordPress and register cron + REST routes.
	 */
	public function register(): void {
		add_action( self::CRON_HOOK, [ $this, 'refresh' ] );
		add_action( 'init', [ $this, 'maybe_schedule' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Schedule weekly refresh if not already scheduled.
	 */
	public function maybe_schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'weekly', self::CRON_HOOK );
		}
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/roi', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_full_snapshot' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/roi/(?P<post_id>\d+)', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_article_roi' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );
	}

	// -----------------------------------------------------------------------
	// Core computation
	// -----------------------------------------------------------------------

	/**
	 * Compute ROI metrics for a single article.
	 *
	 * @param int $post_id WordPress post ID.
	 * @return array<string,mixed>
	 */
	public function compute_article_roi( int $post_id ): array {
		$ga4     = new GA4Client();
		$tracker = new RevenueTracker();

		$path     = parse_url( (string) get_permalink( $post_id ), PHP_URL_PATH ) ?? '/';
		$sessions = $ga4->is_configured()
			? $ga4->get_page_views( $path, '30daysAgo', 'today' )
			: 0;

		$cost_cents    = (float) get_post_meta( $post_id, 'pearblog_generation_cost_cents', true );
		$revenue_data  = $tracker->get_article_data( $post_id );
		$revenue_cents = $revenue_data['total_cents'];

		$roi_cents = $revenue_cents - $cost_cents;
		$roi_pct   = $cost_cents > 0 ? ( $roi_cents / $cost_cents ) * 100 : 0.0;
		$rpm       = $sessions > 0 ? ( $revenue_cents / $sessions ) * 1000 : 0.0; // cents per 1k sessions

		// Break-even: sessions needed to cover AI cost at current RPM rate.
		$break_even_sessions = ( $rpm > 0 && $cost_cents > 0 )
			? (int) ceil( ( $cost_cents / $rpm ) * 1000 )
			: 0;

		return [
			'post_id'             => $post_id,
			'title'               => get_the_title( $post_id ),
			'url'                 => get_permalink( $post_id ),
			'sessions_30d'        => $sessions,
			'cost_cents'          => $cost_cents,
			'cost_usd'            => round( $cost_cents / 100, 4 ),
			'revenue_cents'       => $revenue_cents,
			'revenue_usd'         => round( $revenue_cents / 100, 2 ),
			'roi_cents'           => $roi_cents,
			'roi_pct'             => round( $roi_pct, 1 ),
			'rpm_cents'           => round( $rpm, 2 ),
			'break_even_sessions' => $break_even_sessions,
			'is_profitable'       => $roi_cents >= 0,
			'revenue_by_source'   => $revenue_data['totals'],
		];
	}

	/**
	 * Build a full site-wide ROI snapshot.
	 *
	 * @param int $limit Max articles to include.
	 * @return array<string,mixed>
	 */
	public function build_snapshot( int $limit = 50 ): array {
		$posts = get_posts( [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'DESC',
		] );

		$articles       = [];
		$total_revenue  = 0.0;
		$total_cost     = 0.0;
		$profitable     = 0;

		foreach ( $posts as $post_id ) {
			$roi          = $this->compute_article_roi( (int) $post_id );
			$articles[]   = $roi;
			$total_revenue += $roi['revenue_cents'];
			$total_cost    += $roi['cost_cents'];
			if ( $roi['is_profitable'] ) {
				$profitable++;
			}
		}

		// Sort by revenue descending.
		usort( $articles, fn( $a, $b ) => $b['revenue_cents'] <=> $a['revenue_cents'] );

		$net_roi     = $total_revenue - $total_cost;
		$net_roi_pct = $total_cost > 0 ? ( $net_roi / $total_cost ) * 100 : 0.0;

		return [
			'generated_at'         => time(),
			'articles_analysed'    => count( $articles ),
			'total_revenue_cents'  => $total_revenue,
			'total_revenue_usd'    => round( $total_revenue / 100, 2 ),
			'total_cost_cents'     => $total_cost,
			'total_cost_usd'       => round( $total_cost / 100, 4 ),
			'net_roi_cents'        => $net_roi,
			'net_roi_pct'          => round( $net_roi_pct, 1 ),
			'profitable_articles'  => $profitable,
			'articles'             => $articles,
		];
	}

	/**
	 * Refresh and persist the snapshot (called by cron).
	 */
	public function refresh(): void {
		$snapshot = $this->build_snapshot();
		update_option( self::OPTION_SNAPSHOT, $snapshot );

		/**
		 * Action: pearblog_roi_refreshed
		 *
		 * @param array<string,mixed> $snapshot Full ROI snapshot.
		 */
		do_action( 'pearblog_roi_refreshed', $snapshot );
	}

	/**
	 * Return the cached snapshot or build fresh if missing.
	 *
	 * @return array<string,mixed>
	 */
	public function get_snapshot(): array {
		$cached = get_option( self::OPTION_SNAPSHOT );
		return is_array( $cached ) ? $cached : $this->build_snapshot( 20 );
	}

	// -----------------------------------------------------------------------
	// REST callbacks
	// -----------------------------------------------------------------------

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_full_snapshot( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response( $this->get_snapshot(), 200 );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_article_roi( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'post_id' );
		return new \WP_REST_Response( $this->compute_article_roi( $post_id ), 200 );
	}

	/**
	 * Permission callback – require manage_options.
	 */
	public function admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}
}

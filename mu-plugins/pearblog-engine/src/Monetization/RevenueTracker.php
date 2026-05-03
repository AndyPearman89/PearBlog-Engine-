<?php
/**
 * Revenue Tracker – per-article revenue attribution engine.
 *
 * Tracks AdSense earnings, affiliate commissions, and subscription revenue
 * attributed to individual articles.  Provides ROI calculations by combining
 * revenue data with AI generation cost stored in `pearblog_ai_cost_cents`.
 *
 * Storage:
 *   pearblog_revenue_{post_id}   – per-article revenue snapshot (array)
 *   pearblog_revenue_total       – cumulative site revenue in USD cents
 *
 * REST endpoints:
 *   GET  /pearblog/v1/revenue                 – site-wide revenue summary
 *   GET  /pearblog/v1/revenue/{post_id}       – revenue for a single article
 *   POST /pearblog/v1/revenue/{post_id}/track – record a revenue event
 *
 * @package PearBlogEngine\Monetization
 */

declare(strict_types=1);

namespace PearBlogEngine\Monetization;

/**
 * Records and queries per-article revenue data.
 */
class RevenueTracker {

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Option prefix for per-article revenue. */
	private const OPTION_PREFIX = 'pearblog_revenue_';

	/** Option key for cumulative totals. */
	private const OPTION_TOTAL = 'pearblog_revenue_total';

	/** Revenue source types. */
	public const SOURCE_ADSENSE    = 'adsense';
	public const SOURCE_AFFILIATE  = 'affiliate';
	public const SOURCE_PAYWALL    = 'paywall';
	public const SOURCE_LEAD       = 'lead';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Hook into WordPress and register REST routes.
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'pearblog_pipeline_completed', [ $this, 'init_article_revenue' ], 10, 1 );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/revenue', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_site_summary' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/revenue/(?P<post_id>\d+)', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_article_revenue' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/revenue/(?P<post_id>\d+)/track', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_track_event' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );
	}

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Record a revenue event for a specific article.
	 *
	 * @param int    $post_id     WordPress post ID.
	 * @param float  $amount_cents Revenue amount in USD cents.
	 * @param string $source      Revenue source (adsense|affiliate|paywall|lead).
	 * @param array<string,mixed> $meta Additional metadata.
	 */
	public function track( int $post_id, float $amount_cents, string $source, array $meta = [] ): void {
		$data = $this->get_article_data( $post_id );

		$event = [
			'timestamp'    => time(),
			'source'       => $source,
			'amount_cents' => $amount_cents,
			'meta'         => $meta,
		];

		$data['events'][]             = $event;
		$data['totals'][ $source ]    = ( $data['totals'][ $source ] ?? 0.0 ) + $amount_cents;
		$data['total_cents']          = ( $data['total_cents'] ?? 0.0 ) + $amount_cents;
		$data['last_updated']         = time();

		update_option( self::OPTION_PREFIX . $post_id, $data );

		// Update site cumulative total.
		$total = (float) get_option( self::OPTION_TOTAL, 0 );
		update_option( self::OPTION_TOTAL, $total + $amount_cents );

		/**
		 * Action: pearblog_revenue_tracked
		 *
		 * @param int    $post_id      Post ID.
		 * @param float  $amount_cents Amount in cents.
		 * @param string $source       Revenue source.
		 */
		do_action( 'pearblog_revenue_tracked', $post_id, $amount_cents, $source );
	}

	/**
	 * Get revenue data for a specific article.
	 *
	 * @param int $post_id WordPress post ID.
	 * @return array<string,mixed>
	 */
	public function get_article_data( int $post_id ): array {
		$default = [
			'post_id'      => $post_id,
			'total_cents'  => 0.0,
			'totals'       => [],
			'events'       => [],
			'ai_cost_cents'=> 0.0,
			'roi_cents'    => 0.0,
			'last_updated' => 0,
		];

		$stored = get_option( self::OPTION_PREFIX . $post_id, $default );
		return is_array( $stored ) ? array_merge( $default, $stored ) : $default;
	}

	/**
	 * Calculate ROI for a specific article.
	 *
	 * @param int $post_id WordPress post ID.
	 * @return array{revenue_cents: float, cost_cents: float, roi_cents: float, roi_pct: float}
	 */
	public function calculate_roi( int $post_id ): array {
		$data      = $this->get_article_data( $post_id );
		$revenue   = $data['total_cents'];
		$cost      = (float) get_post_meta( $post_id, 'pearblog_generation_cost_cents', true );
		$roi       = $revenue - $cost;
		$roi_pct   = $cost > 0 ? ( $roi / $cost ) * 100 : 0.0;

		return [
			'revenue_cents' => $revenue,
			'cost_cents'    => $cost,
			'roi_cents'     => $roi,
			'roi_pct'       => round( $roi_pct, 2 ),
		];
	}

	/**
	 * Get top-earning articles.
	 *
	 * @param int $limit Number of articles to return.
	 * @return array<int, array<string,mixed>>
	 */
	public function get_top_articles( int $limit = 10 ): array {
		$posts = get_posts( [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'fields'         => 'ids',
		] );

		$results = [];
		foreach ( $posts as $post_id ) {
			$data = $this->get_article_data( (int) $post_id );
			if ( $data['total_cents'] > 0 ) {
				$roi              = $this->calculate_roi( (int) $post_id );
				$results[]        = array_merge( $data, $roi, [
					'title' => get_the_title( (int) $post_id ),
					'url'   => get_permalink( (int) $post_id ),
				] );
			}
		}

		usort( $results, fn( $a, $b ) => $b['total_cents'] <=> $a['total_cents'] );

		return array_slice( $results, 0, $limit );
	}

	/**
	 * Get site-wide revenue summary.
	 *
	 * @return array<string,mixed>
	 */
	public function get_site_summary(): array {
		return [
			'total_cents'     => (float) get_option( self::OPTION_TOTAL, 0 ),
			'total_usd'       => round( (float) get_option( self::OPTION_TOTAL, 0 ) / 100, 2 ),
			'top_articles'    => $this->get_top_articles( 5 ),
			'generated_at'    => time(),
		];
	}

	// -----------------------------------------------------------------------
	// WordPress action callbacks
	// -----------------------------------------------------------------------

	/**
	 * Initialise revenue tracking record when article is first published.
	 *
	 * @param int $post_id Published post ID.
	 */
	public function init_article_revenue( int $post_id ): void {
		if ( get_option( self::OPTION_PREFIX . $post_id ) ) {
			return;
		}

		$data = [
			'post_id'       => $post_id,
			'total_cents'   => 0.0,
			'totals'        => [],
			'events'        => [],
			'ai_cost_cents' => (float) get_post_meta( $post_id, 'pearblog_generation_cost_cents', true ),
			'created_at'    => time(),
			'last_updated'  => time(),
		];

		update_option( self::OPTION_PREFIX . $post_id, $data );
	}

	// -----------------------------------------------------------------------
	// REST callbacks
	// -----------------------------------------------------------------------

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_site_summary( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response( $this->get_site_summary(), 200 );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_article_revenue( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'post_id' );
		$data    = $this->get_article_data( $post_id );
		$roi     = $this->calculate_roi( $post_id );

		return new \WP_REST_Response( array_merge( $data, $roi ), 200 );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_track_event( \WP_REST_Request $request ) {
		$post_id = (int) $request->get_param( 'post_id' );
		$amount  = (float) $request->get_param( 'amount_cents' );
		$source  = (string) $request->get_param( 'source' );

		$valid_sources = [ self::SOURCE_ADSENSE, self::SOURCE_AFFILIATE, self::SOURCE_PAYWALL, self::SOURCE_LEAD ];
		if ( ! in_array( $source, $valid_sources, true ) ) {
			return new \WP_Error( 'invalid_source', 'Invalid revenue source.', [ 'status' => 400 ] );
		}

		$this->track( $post_id, $amount, $source, $request->get_param( 'meta' ) ?? [] );

		return new \WP_REST_Response( [ 'success' => true, 'post_id' => $post_id ], 200 );
	}

	/**
	 * Permission callback – require manage_options.
	 */
	public function admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}
}

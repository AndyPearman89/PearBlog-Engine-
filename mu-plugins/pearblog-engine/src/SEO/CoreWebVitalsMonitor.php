<?php
/**
 * Core Web Vitals Monitor – integrates with Google PageSpeed Insights API.
 *
 * Monitors Core Web Vitals (LCP, FID/INP, CLS) for published articles and
 * fires alerts when performance drops below configured thresholds.
 *
 * Features:
 *  - Per-URL CWV measurement via PageSpeed Insights API v5.
 *  - Configurable pass/fail thresholds per metric.
 *  - Weekly audit of all published articles.
 *  - Admin dashboard summary showing failing pages.
 *  - Action hook `pearblog_cwv_threshold_breached` when a URL fails.
 *
 * Configuration (WP options):
 *   pearblog_cwv_enabled       – (bool) enable CWV monitoring
 *   pearblog_cwv_api_key       – Google PageSpeed Insights API key
 *   pearblog_cwv_lcp_threshold – max LCP in milliseconds (default: 2500)
 *   pearblog_cwv_cls_threshold – max CLS (default: 0.1)
 *   pearblog_cwv_fid_threshold – max FID in milliseconds (default: 100)
 *
 * @package PearBlogEngine\SEO
 */

declare(strict_types=1);

namespace PearBlogEngine\SEO;

/**
 * Core Web Vitals monitoring via PageSpeed Insights API.
 */
class CoreWebVitalsMonitor {

	/** WP option keys. */
	public const OPTION_ENABLED       = 'pearblog_cwv_enabled';
	public const OPTION_API_KEY       = 'pearblog_cwv_api_key';
	public const OPTION_LCP_THRESHOLD = 'pearblog_cwv_lcp_threshold';
	public const OPTION_CLS_THRESHOLD = 'pearblog_cwv_cls_threshold';
	public const OPTION_FID_THRESHOLD = 'pearblog_cwv_fid_threshold';

	/** WP option storing the last CWV snapshot. */
	public const OPTION_SNAPSHOT = 'pearblog_cwv_snapshot';

	/** Default thresholds (Google "good" thresholds). */
	public const DEFAULT_LCP_MS  = 2500;
	public const DEFAULT_CLS     = 0.1;
	public const DEFAULT_FID_MS  = 100;

	/** PageSpeed Insights API endpoint. */
	private const API_URL = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

	/** Cron hook. */
	private const CRON_HOOK = 'pearblog_cwv_audit';

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register WordPress hooks.
	 */
	public function register(): void {
		add_action( self::CRON_HOOK, [ $this, 'run_audit' ] );
		add_action( 'init', [ $this, 'maybe_schedule' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
	}

	/**
	 * Schedule weekly audit cron.
	 */
	public function maybe_schedule(): void {
		if ( ! (bool) get_option( self::OPTION_ENABLED, false ) ) {
			return;
		}

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'weekly', self::CRON_HOOK );
		}
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/cwv/snapshot', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_get_snapshot' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/cwv/measure', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_measure_url' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );
	}

	/**
	 * Add admin menu page.
	 */
	public function add_admin_menu(): void {
		add_submenu_page(
			'pearblog-engine',
			__( 'Core Web Vitals', 'pearblog-engine' ),
			__( 'Core Web Vitals', 'pearblog-engine' ),
			'manage_options',
			'pearblog-cwv',
			[ $this, 'render_admin_page' ]
		);
	}

	// -----------------------------------------------------------------------
	// CWV measurement
	// -----------------------------------------------------------------------

	/**
	 * Measure Core Web Vitals for a specific URL.
	 *
	 * @param string $url Target URL to measure.
	 * @return array{url: string, lcp_ms: float, cls: float, fid_ms: float, performance_score: int, status: string}
	 */
	public function measure_url( string $url ): array {
		$api_key = (string) get_option( self::OPTION_API_KEY, '' );
		$default = [
			'url'               => $url,
			'lcp_ms'            => 0.0,
			'cls'               => 0.0,
			'fid_ms'            => 0.0,
			'performance_score' => 0,
			'status'            => 'unavailable',
		];

		if ( ! (bool) get_option( self::OPTION_ENABLED, false ) || '' === $api_key ) {
			return $default;
		}

		$cache_key = 'pearblog_cwv_' . md5( $url );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$response = wp_remote_get( self::API_URL . '?' . http_build_query( [
			'url'      => $url,
			'key'      => $api_key,
			'strategy' => 'mobile',
			'category' => 'performance',
		] ), [ 'timeout' => 30 ] );

		if ( is_wp_error( $response ) ) {
			return $default;
		}

		$data      = json_decode( wp_remote_retrieve_body( $response ), true );
		$metrics   = $data['lighthouseResult']['audits'] ?? [];
		$cat_score = $data['lighthouseResult']['categories']['performance']['score'] ?? 0;

		$lcp_ms = (float) ( $metrics['largest-contentful-paint']['numericValue'] ?? 0 );
		$cls    = (float) ( $metrics['cumulative-layout-shift']['numericValue'] ?? 0 );
		$fid_ms = (float) ( $metrics['total-blocking-time']['numericValue'] ?? 0 ); // TBT as FID proxy.

		$result = [
			'url'               => $url,
			'lcp_ms'            => round( $lcp_ms, 0 ),
			'cls'               => round( $cls, 3 ),
			'fid_ms'            => round( $fid_ms, 0 ),
			'performance_score' => (int) round( $cat_score * 100 ),
			'status'            => $this->get_status( $lcp_ms, $cls, $fid_ms ),
			'measured_at'       => time(),
		];

		// Cache for 24 hours.
		set_transient( $cache_key, $result, DAY_IN_SECONDS );

		return $result;
	}

	/**
	 * Run a full audit of all published articles.
	 */
	public function run_audit(): void {
		$posts = get_posts( [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'DESC',
		] );

		$results  = [];
		$failures = [];

		foreach ( $posts as $post_id ) {
			$url    = (string) get_permalink( (int) $post_id );
			$result = $this->measure_url( $url );
			$result['post_id'] = (int) $post_id;
			$result['title']   = get_the_title( (int) $post_id );
			$results[]         = $result;

			if ( 'fail' === $result['status'] ) {
				$failures[] = $result;

				/**
				 * Action: pearblog_cwv_threshold_breached
				 *
				 * @param int    $post_id Post ID.
				 * @param string $url     URL that failed.
				 * @param array  $result  CWV measurement data.
				 */
				do_action( 'pearblog_cwv_threshold_breached', (int) $post_id, $url, $result );
			}
		}

		$snapshot = [
			'generated_at' => time(),
			'total'        => count( $results ),
			'passing'      => count( $results ) - count( $failures ),
			'failing'      => count( $failures ),
			'results'      => $results,
		];

		update_option( self::OPTION_SNAPSHOT, $snapshot );

		/**
		 * Action: pearblog_cwv_audit_completed
		 *
		 * @param array<string,mixed> $snapshot Audit snapshot.
		 */
		do_action( 'pearblog_cwv_audit_completed', $snapshot );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Determine pass/fail status based on thresholds.
	 *
	 * @param float $lcp_ms  LCP in milliseconds.
	 * @param float $cls     CLS score.
	 * @param float $fid_ms  FID/TBT in milliseconds.
	 * @return string 'pass' or 'fail'
	 */
	private function get_status( float $lcp_ms, float $cls, float $fid_ms ): string {
		$lcp_threshold = (float) get_option( self::OPTION_LCP_THRESHOLD, self::DEFAULT_LCP_MS );
		$cls_threshold = (float) get_option( self::OPTION_CLS_THRESHOLD, self::DEFAULT_CLS );
		$fid_threshold = (float) get_option( self::OPTION_FID_THRESHOLD, self::DEFAULT_FID_MS );

		if ( $lcp_ms > $lcp_threshold || $cls > $cls_threshold || $fid_ms > $fid_threshold ) {
			return 'fail';
		}

		return 'pass';
	}

	// -----------------------------------------------------------------------
	// REST callbacks
	// -----------------------------------------------------------------------

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_get_snapshot( \WP_REST_Request $request ): \WP_REST_Response {
		$snapshot = get_option( self::OPTION_SNAPSHOT, [] );
		return new \WP_REST_Response( $snapshot, 200 );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_measure_url( \WP_REST_Request $request ) {
		$url = esc_url_raw( (string) $request->get_param( 'url' ) );
		if ( '' === $url ) {
			return new \WP_Error( 'missing_url', 'URL is required.', [ 'status' => 400 ] );
		}

		return new \WP_REST_Response( $this->measure_url( $url ), 200 );
	}

	/**
	 * Render admin page.
	 */
	public function render_admin_page(): void {
		$snapshot = (array) get_option( self::OPTION_SNAPSHOT, [] );
		$results  = $snapshot['results'] ?? [];
		?>
		<div class="wrap">
			<h1><?php esc_html_e( '⚡ Core Web Vitals', 'pearblog-engine' ); ?></h1>

			<?php if ( ! empty( $snapshot ) ) : ?>
				<div style="display:flex;gap:16px;margin-bottom:24px;">
					<div style="background:#d4edda;padding:16px 24px;border-radius:8px;">
						<strong style="font-size:24px;"><?php echo esc_html( $snapshot['passing'] ?? 0 ); ?></strong>
						<p style="margin:4px 0 0;"><?php esc_html_e( 'Passing', 'pearblog-engine' ); ?></p>
					</div>
					<div style="background:#f8d7da;padding:16px 24px;border-radius:8px;">
						<strong style="font-size:24px;"><?php echo esc_html( $snapshot['failing'] ?? 0 ); ?></strong>
						<p style="margin:4px 0 0;"><?php esc_html_e( 'Failing', 'pearblog-engine' ); ?></p>
					</div>
				</div>

				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Article', 'pearblog-engine' ); ?></th>
							<th><?php esc_html_e( 'LCP (ms)', 'pearblog-engine' ); ?></th>
							<th><?php esc_html_e( 'CLS', 'pearblog-engine' ); ?></th>
							<th><?php esc_html_e( 'TBT (ms)', 'pearblog-engine' ); ?></th>
							<th><?php esc_html_e( 'Score', 'pearblog-engine' ); ?></th>
							<th><?php esc_html_e( 'Status', 'pearblog-engine' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $results as $result ) : ?>
						<tr>
							<td><?php echo esc_html( $result['title'] ?? $result['url'] ?? '' ); ?></td>
							<td><?php echo esc_html( number_format( $result['lcp_ms'] ?? 0 ) ); ?></td>
							<td><?php echo esc_html( $result['cls'] ?? '0.000' ); ?></td>
							<td><?php echo esc_html( number_format( $result['fid_ms'] ?? 0 ) ); ?></td>
							<td><?php echo esc_html( ( $result['performance_score'] ?? 0 ) . '/100' ); ?></td>
							<td>
								<?php $status = $result['status'] ?? 'unknown'; ?>
								<span style="color:<?php echo 'pass' === $status ? 'green' : 'red'; ?>">
									<?php echo 'pass' === $status ? '✅ Pass' : '❌ Fail'; ?>
								</span>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p><?php esc_html_e( 'No CWV data yet. Enable monitoring and wait for the weekly audit to run.', 'pearblog-engine' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Permission callback.
	 */
	public function admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}
}

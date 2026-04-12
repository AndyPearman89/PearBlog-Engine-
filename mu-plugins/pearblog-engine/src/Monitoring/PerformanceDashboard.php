<?php
/**
 * Performance monitoring dashboard.
 *
 * Collects and stores performance metrics for the PearBlog Engine pipeline:
 *  - Pipeline execution time
 *  - Memory usage (peak and current)
 *  - Database query count per pipeline run
 *  - AI API response time
 *  - Queue processing throughput
 *  - Error rate
 *  - Articles published (daily/weekly/monthly)
 *  - Cost per article
 *  - Circuit breaker open count
 *  - Cache hit rate (when ContentCache is enabled)
 *
 * Metrics are stored as a circular buffer in a WP option (last 30 days).
 * The class also exposes a REST endpoint and an admin UI tab.
 *
 * @package PearBlogEngine\Monitoring
 */

declare(strict_types=1);

namespace PearBlogEngine\Monitoring;

use PearBlogEngine\AI\AIClient;
use PearBlogEngine\Content\TopicQueue;

/**
 * Collects, stores, and surfaces performance metrics for the admin dashboard.
 */
class PerformanceDashboard {

	// ------------------------------------------------------------------
	// Constants
	// ------------------------------------------------------------------

	/** WordPress option holding the metrics ring-buffer. */
	public const OPTION_METRICS = 'pearblog_perf_metrics';

	/** WordPress option holding daily aggregate data. */
	public const OPTION_DAILY = 'pearblog_perf_daily';

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Maximum run records to keep. */
	private const MAX_RUN_RECORDS = 200;

	/** Thresholds for alerting (configurable via filter). */
	private const DEFAULT_THRESHOLDS = [
		'pipeline_duration_sec' => 120,   // Alert if single run > 2 min.
		'memory_peak_mb'        => 256,   // Alert if peak memory > 256 MB.
		'error_rate_pct'        => 10.0,  // Alert if > 10 % of runs fail.
		'api_response_sec'      => 30,    // Alert if OpenAI latency > 30 s.
		'cost_per_article_usd'  => 0.15,  // Alert if cost > $0.15 / article.
	];

	// ------------------------------------------------------------------
	// Registration
	// ------------------------------------------------------------------

	/**
	 * Register WordPress hooks.
	 */
	public function register(): void {
		// Record metrics after each pipeline run.
		add_action( 'pearblog_pipeline_completed', [ $this, 'record_pipeline_run' ], 10, 3 );
		add_action( 'pearblog_pipeline_cron_error', [ $this, 'record_pipeline_error' ], 10, 2 );

		// REST endpoint.
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

		// Daily aggregation cron.
		add_action( 'pearblog_daily_aggregation', [ $this, 'aggregate_daily_stats' ] );
		if ( ! wp_next_scheduled( 'pearblog_daily_aggregation' ) ) {
			wp_schedule_event( strtotime( 'tomorrow midnight' ), 'daily', 'pearblog_daily_aggregation' );
		}
	}

	// ------------------------------------------------------------------
	// Metric recording
	// ------------------------------------------------------------------

	/**
	 * Record a successful pipeline run.
	 *
	 * @param int    $post_id          ID of the published post.
	 * @param string $topic            Article topic.
	 * @param float  $duration_seconds Time the pipeline took (seconds).
	 */
	public function record_pipeline_run( int $post_id, string $topic = '', float $duration_seconds = 0.0 ): void {
		$memory_peak = round( memory_get_peak_usage( true ) / ( 1024 * 1024 ), 2 );
		$cost_cents  = AIClient::get_total_cost_cents();

		$record = [
			'ts'          => time(),
			'type'        => 'success',
			'post_id'     => $post_id,
			'topic'       => mb_substr( $topic, 0, 100 ),
			'duration'    => round( $duration_seconds, 3 ),
			'memory_mb'   => $memory_peak,
			'cost_cents'  => round( $cost_cents, 4 ),
			'db_queries'  => $this->get_db_query_count(),
		];

		$this->push_record( $record );
		$this->check_thresholds( $record );
	}

	/**
	 * Record a failed pipeline run.
	 *
	 * @param int    $site_id Site where the error occurred.
	 * @param string $message Error message.
	 */
	public function record_pipeline_error( int $site_id, string $message = '' ): void {
		$record = [
			'ts'      => time(),
			'type'    => 'error',
			'site_id' => $site_id,
			'message' => mb_substr( $message, 0, 255 ),
		];

		$this->push_record( $record );
	}

	// ------------------------------------------------------------------
	// Aggregation
	// ------------------------------------------------------------------

	/**
	 * Compute daily aggregate stats from the ring-buffer.
	 * Runs nightly via WP cron.
	 */
	public function aggregate_daily_stats(): void {
		$today   = gmdate( 'Y-m-d' );
		$records = $this->get_all_records();

		$daily_records = array_filter( $records, function ( array $r ) use ( $today ): bool {
			return gmdate( 'Y-m-d', $r['ts'] ) === $today;
		} );

		$successes = array_filter( $daily_records, fn( $r ) => 'success' === $r['type'] );
		$errors    = array_filter( $daily_records, fn( $r ) => 'error' === $r['type'] );

		$total   = count( $daily_records );
		$success_count = count( $successes );

		$avg_duration = $success_count > 0
			? array_sum( array_column( $successes, 'duration' ) ) / $success_count
			: 0;

		$avg_memory = $success_count > 0
			? array_sum( array_column( $successes, 'memory_mb' ) ) / $success_count
			: 0;

		$aggregate = [
			'date'           => $today,
			'total_runs'     => $total,
			'successes'      => $success_count,
			'errors'         => count( $errors ),
			'error_rate_pct' => $total > 0 ? round( count( $errors ) / $total * 100, 1 ) : 0,
			'avg_duration'   => round( $avg_duration, 2 ),
			'avg_memory_mb'  => round( $avg_memory, 2 ),
			'articles_today' => $this->count_articles_today(),
		];

		$daily = (array) get_option( self::OPTION_DAILY, [] );
		$daily[ $today ] = $aggregate;

		// Keep only 30 days.
		$keys = array_keys( $daily );
		rsort( $keys );
		$daily = array_intersect_key( $daily, array_flip( array_slice( $keys, 0, 30 ) ) );

		update_option( self::OPTION_DAILY, $daily );
	}

	// ------------------------------------------------------------------
	// Public data access
	// ------------------------------------------------------------------

	/**
	 * Return a summary of current system metrics.
	 *
	 * @return array<string, mixed>
	 */
	public function get_summary(): array {
		$records = $this->get_all_records();

		$successes = array_filter( $records, fn( $r ) => 'success' === $r['type'] );
		$errors    = array_filter( $records, fn( $r ) => 'error' === $r['type'] );
		$total     = count( $records );

		// Last 24-hour window.
		$since_24h  = time() - DAY_IN_SECONDS;
		$recent     = array_filter( $successes, fn( $r ) => $r['ts'] >= $since_24h );

		$avg_duration_24h = count( $recent ) > 0
			? round( array_sum( array_column( $recent, 'duration' ) ) / count( $recent ), 2 )
			: 0;

		$avg_memory_24h = count( $recent ) > 0
			? round( array_sum( array_column( $recent, 'memory_mb' ) ) / count( $recent ), 2 )
			: 0;

		// Overall error rate.
		$error_rate = $total > 0 ? round( count( $errors ) / $total * 100, 1 ) : 0;

		// Current resource usage.
		$memory_now_mb    = round( memory_get_usage( true ) / ( 1024 * 1024 ), 2 );
		$memory_peak_mb   = round( memory_get_peak_usage( true ) / ( 1024 * 1024 ), 2 );

		return [
			'total_runs'          => $total,
			'successes'           => count( $successes ),
			'errors'              => count( $errors ),
			'error_rate_pct'      => $error_rate,
			'articles_last_24h'   => count( $recent ),
			'avg_duration_24h'    => $avg_duration_24h,
			'avg_memory_24h_mb'   => $avg_memory_24h,
			'memory_now_mb'       => $memory_now_mb,
			'memory_peak_mb'      => $memory_peak_mb,
			'ai_cost_total_usd'   => round( AIClient::get_total_cost_cents() / 100, 4 ),
			'queue_size'          => ( new TopicQueue( get_current_blog_id() ) )->count(),
			'circuit_open'        => AIClient::is_circuit_open(),
			'articles_today'      => $this->count_articles_today(),
			'php_version'         => PHP_VERSION,
			'wp_version'          => defined( 'WPINC' ) ? get_bloginfo( 'version' ) : 'n/a',
			'db_queries_total'    => $this->get_db_query_count(),
		];
	}

	/**
	 * Return recent run records.
	 *
	 * @param int $limit Maximum number of records to return (newest first).
	 * @return array
	 */
	public function get_recent_runs( int $limit = 50 ): array {
		$records = $this->get_all_records();
		$records = array_reverse( $records ); // newest first.
		return array_slice( $records, 0, $limit );
	}

	/**
	 * Return 30-day daily aggregates (most recent first).
	 *
	 * @return array
	 */
	public function get_daily_stats(): array {
		$daily = (array) get_option( self::OPTION_DAILY, [] );
		krsort( $daily );
		return array_values( $daily );
	}

	// ------------------------------------------------------------------
	// REST endpoint
	// ------------------------------------------------------------------

	/**
	 * Register REST routes.
	 */
	public function register_rest_routes(): void {
		register_rest_route( self::NAMESPACE, '/performance', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ $this, 'rest_get_performance' ],
			'permission_callback' => fn() => current_user_can( 'manage_options' ),
		] );

		register_rest_route( self::NAMESPACE, '/performance/export', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ $this, 'rest_export_json' ],
			'permission_callback' => fn() => current_user_can( 'manage_options' ),
		] );

		register_rest_route( self::NAMESPACE, '/performance/reset', [
			'methods'             => \WP_REST_Server::EDITABLE,
			'callback'            => [ $this, 'rest_reset_metrics' ],
			'permission_callback' => fn() => current_user_can( 'manage_options' ),
		] );
	}

	/**
	 * REST callback: GET /pearblog/v1/performance
	 */
	public function rest_get_performance( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response( [
			'summary'     => $this->get_summary(),
			'recent_runs' => $this->get_recent_runs( (int) ( $request->get_param( 'limit' ) ?: 20 ) ),
			'daily'       => $this->get_daily_stats(),
			'thresholds'  => $this->get_thresholds(),
		], 200 );
	}

	/**
	 * REST callback: GET /pearblog/v1/performance/export
	 * Returns all data as a downloadable JSON payload.
	 */
	public function rest_export_json( \WP_REST_Request $request ): \WP_REST_Response {
		$response = new \WP_REST_Response( [
			'exported_at' => gmdate( 'c' ),
			'summary'     => $this->get_summary(),
			'runs'        => $this->get_all_records(),
			'daily'       => $this->get_daily_stats(),
		], 200 );

		$response->header( 'Content-Disposition', 'attachment; filename="pearblog-performance-' . gmdate( 'Y-m-d' ) . '.json"' );

		return $response;
	}

	/**
	 * REST callback: POST /pearblog/v1/performance/reset
	 */
	public function rest_reset_metrics( \WP_REST_Request $request ): \WP_REST_Response {
		$this->reset_metrics();
		return new \WP_REST_Response( [ 'status' => 'ok', 'message' => 'Metrics reset.' ], 200 );
	}

	// ------------------------------------------------------------------
	// Admin UI rendering (called from AdminPage tab)
	// ------------------------------------------------------------------

	/**
	 * Render the Monitoring admin tab content.
	 */
	public function render_admin_tab(): void {
		$summary     = $this->get_summary();
		$recent_runs = $this->get_recent_runs( 20 );
		$daily       = $this->get_daily_stats();
		$thresholds  = $this->get_thresholds();

		$error_rate_class = $summary['error_rate_pct'] > $thresholds['error_rate_pct'] ? 'pb-metric-danger' : 'pb-metric-ok';
		$circuit_class    = $summary['circuit_open'] ? 'pb-metric-danger' : 'pb-metric-ok';
		$memory_class     = $summary['memory_peak_mb'] > $thresholds['memory_peak_mb'] ? 'pb-metric-warn' : 'pb-metric-ok';
		?>
		<div class="pb-admin-card">
			<div class="pb-admin-card-header">
				<span class="pb-admin-card-icon" aria-hidden="true">📊</span>
				<h2 class="pb-admin-card-title"><?php esc_html_e( 'Performance Monitoring', 'pearblog-engine' ); ?></h2>
			</div>

			<!-- Summary metrics grid -->
			<div class="pb-perf-grid">
				<?php $this->metric_card( __( 'Total Pipeline Runs', 'pearblog-engine' ), (string) $summary['total_runs'], '' ); ?>
				<?php $this->metric_card( __( 'Articles Last 24h', 'pearblog-engine' ), (string) $summary['articles_last_24h'], '' ); ?>
				<?php $this->metric_card( __( 'Avg Duration (24h)', 'pearblog-engine' ), $summary['avg_duration_24h'] . ' s', '' ); ?>
				<?php $this->metric_card( __( 'Error Rate', 'pearblog-engine' ), $summary['error_rate_pct'] . ' %', $error_rate_class ); ?>
				<?php $this->metric_card( __( 'Memory (peak)', 'pearblog-engine' ), $summary['memory_peak_mb'] . ' MB', $memory_class ); ?>
				<?php $this->metric_card( __( 'AI Cost (total)', 'pearblog-engine' ), '$' . $summary['ai_cost_total_usd'], '' ); ?>
				<?php $this->metric_card( __( 'Queue Size', 'pearblog-engine' ), (string) $summary['queue_size'], '' ); ?>
				<?php $this->metric_card( __( 'Circuit Breaker', 'pearblog-engine' ), $summary['circuit_open'] ? 'OPEN' : 'closed', $circuit_class ); ?>
				<?php $this->metric_card( __( 'Articles Today', 'pearblog-engine' ), (string) $summary['articles_today'], '' ); ?>
				<?php $this->metric_card( __( 'DB Queries', 'pearblog-engine' ), (string) $summary['db_queries_total'], '' ); ?>
				<?php $this->metric_card( __( 'PHP Version', 'pearblog-engine' ), $summary['php_version'], '' ); ?>
				<?php $this->metric_card( __( 'WP Version', 'pearblog-engine' ), $summary['wp_version'], '' ); ?>
			</div>

			<!-- Export / Reset actions -->
			<p style="margin-top:1rem;">
				<a href="<?php echo esc_url( rest_url( self::NAMESPACE . '/performance/export' ) ); ?>"
				   class="button button-secondary" download>
					<?php esc_html_e( 'Export JSON', 'pearblog-engine' ); ?>
				</a>
			</p>
		</div>

		<!-- Daily stats table -->
		<?php if ( ! empty( $daily ) ) : ?>
		<div class="pb-admin-card">
			<div class="pb-admin-card-header">
				<span class="pb-admin-card-icon" aria-hidden="true">📅</span>
				<h2 class="pb-admin-card-title"><?php esc_html_e( '30-Day History', 'pearblog-engine' ); ?></h2>
			</div>
			<table class="widefat striped" style="margin-top:.5rem;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'pearblog-engine' ); ?></th>
						<th><?php esc_html_e( 'Runs', 'pearblog-engine' ); ?></th>
						<th><?php esc_html_e( 'Successes', 'pearblog-engine' ); ?></th>
						<th><?php esc_html_e( 'Errors', 'pearblog-engine' ); ?></th>
						<th><?php esc_html_e( 'Error %', 'pearblog-engine' ); ?></th>
						<th><?php esc_html_e( 'Avg Duration (s)', 'pearblog-engine' ); ?></th>
						<th><?php esc_html_e( 'Avg Memory (MB)', 'pearblog-engine' ); ?></th>
						<th><?php esc_html_e( 'Articles', 'pearblog-engine' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $daily as $day ) : ?>
					<tr>
						<td><?php echo esc_html( $day['date'] ); ?></td>
						<td><?php echo esc_html( $day['total_runs'] ); ?></td>
						<td><?php echo esc_html( $day['successes'] ); ?></td>
						<td><?php echo esc_html( $day['errors'] ); ?></td>
						<td><?php echo esc_html( $day['error_rate_pct'] ); ?> %</td>
						<td><?php echo esc_html( $day['avg_duration'] ); ?></td>
						<td><?php echo esc_html( $day['avg_memory_mb'] ); ?></td>
						<td><?php echo esc_html( $day['articles_today'] ?? '—' ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>

		<!-- Recent runs log -->
		<?php if ( ! empty( $recent_runs ) ) : ?>
		<div class="pb-admin-card">
			<div class="pb-admin-card-header">
				<span class="pb-admin-card-icon" aria-hidden="true">🕐</span>
				<h2 class="pb-admin-card-title"><?php esc_html_e( 'Recent Pipeline Runs', 'pearblog-engine' ); ?></h2>
			</div>
			<table class="widefat striped" style="margin-top:.5rem;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Time', 'pearblog-engine' ); ?></th>
						<th><?php esc_html_e( 'Type', 'pearblog-engine' ); ?></th>
						<th><?php esc_html_e( 'Topic', 'pearblog-engine' ); ?></th>
						<th><?php esc_html_e( 'Duration (s)', 'pearblog-engine' ); ?></th>
						<th><?php esc_html_e( 'Memory (MB)', 'pearblog-engine' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $recent_runs as $run ) : ?>
					<tr class="<?php echo 'error' === $run['type'] ? 'pb-row-error' : ''; ?>">
						<td><?php echo esc_html( gmdate( 'Y-m-d H:i:s', $run['ts'] ) ); ?></td>
						<td><?php echo esc_html( $run['type'] ); ?></td>
						<td><?php echo esc_html( $run['topic'] ?? ( $run['message'] ?? '—' ) ); ?></td>
						<td><?php echo isset( $run['duration'] ) ? esc_html( $run['duration'] ) : '—'; ?></td>
						<td><?php echo isset( $run['memory_mb'] ) ? esc_html( $run['memory_mb'] ) : '—'; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>
		<?php
	}

	// ------------------------------------------------------------------
	// Helpers
	// ------------------------------------------------------------------

	/**
	 * Push a run record into the ring-buffer option.
	 */
	private function push_record( array $record ): void {
		$records   = $this->get_all_records();
		$records[] = $record;

		if ( count( $records ) > self::MAX_RUN_RECORDS ) {
			$records = array_slice( $records, -self::MAX_RUN_RECORDS );
		}

		update_option( self::OPTION_METRICS, $records, false );
	}

	/**
	 * Load all records from the WP option.
	 *
	 * @return array
	 */
	public function get_all_records(): array {
		return (array) get_option( self::OPTION_METRICS, [] );
	}

	/**
	 * Reset (delete) all stored metrics.
	 */
	public function reset_metrics(): void {
		delete_option( self::OPTION_METRICS );
		delete_option( self::OPTION_DAILY );
	}

	/**
	 * Return threshold configuration (filterable).
	 *
	 * @return array<string, float|int>
	 */
	public function get_thresholds(): array {
		return (array) apply_filters( 'pearblog_performance_thresholds', self::DEFAULT_THRESHOLDS );
	}

	/**
	 * Check if any metric crosses a threshold and dispatch an alert.
	 */
	private function check_thresholds( array $record ): void {
		$thresholds = $this->get_thresholds();

		if (
			isset( $record['duration'] ) &&
			$record['duration'] > $thresholds['pipeline_duration_sec']
		) {
			( new AlertManager() )->alert(
				'Pipeline Slow',
				"Pipeline took {$record['duration']} s (threshold: {$thresholds['pipeline_duration_sec']} s)",
				AlertManager::LEVEL_WARNING,
				[ 'Post ID' => $record['post_id'] ?? 0 ]
			);
		}

		if (
			isset( $record['memory_mb'] ) &&
			$record['memory_mb'] > $thresholds['memory_peak_mb']
		) {
			( new AlertManager() )->alert(
				'High Memory Usage',
				"Pipeline consumed {$record['memory_mb']} MB (threshold: {$thresholds['memory_peak_mb']} MB)",
				AlertManager::LEVEL_WARNING
			);
		}

		$cost_usd = isset( $record['cost_cents'] ) ? $record['cost_cents'] / 100 : 0;
		if ( $cost_usd > $thresholds['cost_per_article_usd'] ) {
			( new AlertManager() )->alert(
				'High Article Cost',
				"Article cost $ {$cost_usd} (threshold: \${$thresholds['cost_per_article_usd']})",
				AlertManager::LEVEL_WARNING,
				[ 'Post ID' => $record['post_id'] ?? 0 ]
			);
		}
	}

	/**
	 * Count published articles created today.
	 */
	private function count_articles_today(): int {
		$today = current_time( 'Y-m-d' );
		$q     = new \WP_Query( [
			'post_status'    => 'publish',
			'date_query'     => [ [ 'after' => $today . ' 00:00:00', 'column' => 'post_date' ] ],
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => false,
		] );
		return (int) $q->found_posts;
	}

	/**
	 * Return the total number of DB queries executed so far (if SAVEQUERIES enabled).
	 */
	private function get_db_query_count(): int {
		global $wpdb;
		return isset( $wpdb->num_queries ) ? (int) $wpdb->num_queries : 0;
	}

	/**
	 * Render a single metric card in the admin UI.
	 */
	private function metric_card( string $label, string $value, string $extra_class = '' ): void {
		?>
		<div class="pb-perf-metric <?php echo esc_attr( $extra_class ); ?>">
			<div class="pb-perf-metric-value"><?php echo esc_html( $value ); ?></div>
			<div class="pb-perf-metric-label"><?php echo esc_html( $label ); ?></div>
		</div>
		<?php
	}
}

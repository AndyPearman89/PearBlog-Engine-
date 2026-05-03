<?php
/**
 * Poradnik Engine V2 Bootstrap
 *
 * Initializes and registers all Poradnik Engine V2 components.
 *
 * @package PearBlog\Poradnik
 */

namespace PearBlog\Poradnik;

use PearBlog\Database\PoradnikSchema;

/**
 * Class PoradnikEngine
 *
 * Main bootstrap class for Poradnik Engine V2.
 */
class PoradnikEngine {
	/**
	 * Instance.
	 *
	 * @var PoradnikEngine
	 */
	private static $instance = null;

	/**
	 * Worker manager.
	 *
	 * @var WorkerManager
	 */
	private $worker_manager;

	/**
	 * API controller.
	 *
	 * @var PoradnikAPI
	 */
	private $api;

	/**
	 * Get instance (singleton).
	 *
	 * @return PoradnikEngine Instance.
	 */
	public static function get_instance(): PoradnikEngine {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->worker_manager = new WorkerManager();
		$this->api            = new PoradnikAPI();

		$this->init();
	}

	/**
	 * Initialize Poradnik Engine V2.
	 */
	private function init(): void {
		// Register activation hook
		register_activation_hook( PEARBLOG_PLUGIN_FILE, array( $this, 'activate' ) );

		// Register deactivation hook
		register_deactivation_hook( PEARBLOG_PLUGIN_FILE, array( $this, 'deactivate' ) );

		// Register workers
		add_action( 'init', array( $this->worker_manager, 'register' ) );

		// Register REST API
		add_action( 'rest_api_init', array( $this->api, 'register_routes' ) );

		// Register frontend tracking
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_tracking_script' ) );

		// Register admin menu
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
	}

	/**
	 * Plugin activation.
	 */
	public function activate(): void {
		// Create database tables
		$schema  = new PoradnikSchema();
		$results = $schema->create_tables();

		// Log results
		foreach ( $results as $table => $success ) {
			if ( ! $success ) {
				error_log( "[Poradnik Engine] Failed to create table: {$table}" );
			}
		}

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate(): void {
		// Clear scheduled cron jobs
		wp_clear_scheduled_hook( 'poradnik_scoring_worker' );
		wp_clear_scheduled_hook( 'poradnik_optimize_worker' );

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Enqueue tracking script.
	 */
	public function enqueue_tracking_script(): void {
		if ( ! is_single() ) {
			return;
		}

		wp_enqueue_script(
			'poradnik-tracker',
			plugins_url( 'assets/js/poradnik-tracker.js', PEARBLOG_PLUGIN_FILE ),
			array( 'jquery' ),
			'2.0.0',
			true
		);

		// Get article ID from post meta
		$article_id = get_post_meta( get_the_ID(), '_poradnik_article_id', true );

		wp_localize_script(
			'poradnik-tracker',
			'poradnikData',
			array(
				'ajaxUrl'    => rest_url( 'pearblog/v1/event' ),
				'postId'     => get_the_ID(),
				'articleId'  => $article_id,
				'sessionId'  => EventTracker::get_session_id(),
			)
		);
	}

	/**
	 * Register admin menu.
	 */
	public function register_admin_menu(): void {
		add_menu_page(
			'Poradnik Engine',
			'Poradnik',
			'manage_options',
			'poradnik-engine',
			array( $this, 'render_admin_page' ),
			'dashicons-chart-line',
			30
		);

		add_submenu_page(
			'poradnik-engine',
			'Dashboard',
			'Dashboard',
			'manage_options',
			'poradnik-engine',
			array( $this, 'render_admin_page' )
		);

		add_submenu_page(
			'poradnik-engine',
			'Articles',
			'Articles',
			'manage_options',
			'poradnik-articles',
			array( $this, 'render_articles_page' )
		);

		add_submenu_page(
			'poradnik-engine',
			'Statistics',
			'Statistics',
			'manage_options',
			'poradnik-stats',
			array( $this, 'render_stats_page' )
		);

		add_submenu_page(
			'poradnik-engine',
			'Import CSV',
			'Import CSV',
			'manage_options',
			'poradnik-import',
			array( $this, 'render_import_page' )
		);
	}

	/**
	 * Render admin dashboard page.
	 */
	public function render_admin_page(): void {
		global $wpdb;

		$stats_table    = $wpdb->prefix . 'pearblog_article_stats';
		$articles_table = $wpdb->prefix . 'pearblog_articles';

		// Get summary statistics
		$total_articles = $wpdb->get_var( "SELECT COUNT(*) FROM {$articles_table} WHERE status = 'published'" );

		$today_stats = $wpdb->get_row(
			"SELECT
				SUM(views) as total_views,
				SUM(cta_clicks) as total_clicks,
				SUM(leads) as total_leads,
				SUM(revenue) as total_revenue
			FROM {$stats_table}
			WHERE date = CURDATE()",
			ARRAY_A
		);

		$category_distribution = $wpdb->get_results(
			"SELECT score_category, COUNT(*) as count
			FROM {$stats_table}
			WHERE date = CURDATE()
			GROUP BY score_category",
			ARRAY_A
		);

		include __DIR__ . '/views/dashboard.php';
	}

	/**
	 * Render articles page.
	 */
	public function render_articles_page(): void {
		global $wpdb;

		$articles_table = $wpdb->prefix . 'pearblog_articles';
		$stats_table    = $wpdb->prefix . 'pearblog_article_stats';

		$articles = $wpdb->get_results(
			"SELECT a.*, s.score, s.score_category, s.revenue, s.views
			FROM {$articles_table} a
			LEFT JOIN {$stats_table} s ON a.id = s.article_id AND s.date = CURDATE()
			ORDER BY s.score DESC
			LIMIT 100",
			ARRAY_A
		);

		include __DIR__ . '/views/articles.php';
	}

	/**
	 * Render statistics page.
	 */
	public function render_stats_page(): void {
		$scoring_engine = new ScoringEngine();

		$scale_articles    = $scoring_engine->get_articles_by_category( 'SCALE', 10 );
		$boost_articles    = $scoring_engine->get_articles_by_category( 'BOOST', 10 );
		$optimize_articles = $scoring_engine->get_articles_by_category( 'OPTIMIZE', 10 );
		$delete_articles   = $scoring_engine->get_articles_by_category( 'DELETE', 10 );

		include __DIR__ . '/views/statistics.php';
	}

	/**
	 * Render import page.
	 */
	public function render_import_page(): void {
		// Handle CSV upload
		if ( isset( $_POST['poradnik_import_csv'] ) && check_admin_referer( 'poradnik_import_csv' ) ) {
			$this->handle_csv_import();
		}

		include __DIR__ . '/views/import.php';
	}

	/**
	 * Handle CSV import.
	 */
	private function handle_csv_import(): void {
		if ( ! isset( $_FILES['csv_file'] ) ) {
			return;
		}

		$file     = $_FILES['csv_file'];
		$importer = new CSVImporter();

		$rows = $importer->import( $file['tmp_name'] );

		if ( is_wp_error( $rows ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $rows->get_error_message() ) . '</p></div>';
			return;
		}

		// Queue articles for generation
		global $wpdb;
		$table_name = $wpdb->prefix . 'pearblog_articles';
		$queued     = 0;

		foreach ( $rows as $row ) {
			$result = $wpdb->insert(
				$table_name,
				array(
					'topic'   => $row['topic'],
					'city'    => $row['city'],
					'service' => $row['topic'],
					'slug'    => sanitize_title( $row['topic'] . '-' . $row['city'] ),
					'status'  => 'draft',
				),
				array( '%s', '%s', '%s', '%s', '%s' )
			);

			if ( $result ) {
				$queued++;
			}
		}

		echo '<div class="notice notice-success"><p>' . sprintf( 'Successfully queued %d articles for generation.', $queued ) . '</p></div>';
	}
}

// Initialize Poradnik Engine V2
add_action( 'plugins_loaded', function() {
	if ( class_exists( 'PearBlog\Poradnik\PoradnikEngine' ) ) {
		PoradnikEngine::get_instance();
	}
}, 20 );

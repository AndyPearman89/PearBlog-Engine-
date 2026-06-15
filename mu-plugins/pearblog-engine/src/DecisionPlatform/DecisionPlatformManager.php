<?php
/**
 * Decision Platform Manager - Coordinates all Decision Platform features
 *
 * @package PearBlogEngine\DecisionPlatform
 */

declare(strict_types=1);

namespace PearBlogEngine\DecisionPlatform;

/**
 * Decision Platform Manager
 */
class DecisionPlatformManager {

	/** @var ComparisonEngine */
	private ComparisonEngine $comparison_engine;

	/** @var RankingEngine */
	private RankingEngine $ranking_engine;

	/** @var LeadGenerator */
	private LeadGenerator $lead_generator;

	/** @var DecisionAssistant */
	private DecisionAssistant $decision_assistant;

	/** @var IntentDetector */
	private IntentDetector $intent_detector;

	/** @var LinkGraph */
	private LinkGraph $link_graph;

	public function __construct() {
		$this->comparison_engine = new ComparisonEngine();
		$this->ranking_engine = new RankingEngine();
		$this->lead_generator = new LeadGenerator();
		$this->decision_assistant = new DecisionAssistant();
		$this->intent_detector = new IntentDetector();
		$this->link_graph = new LinkGraph();
	}

	/**
	 * Register all Decision Platform features
	 */
	public function register(): void {
		// Register custom post types
		add_action( 'init', [ $this, 'register_post_types' ] );

		// Register taxonomies
		add_action( 'init', [ $this, 'register_taxonomies' ] );

		// Register REST API routes
		add_action( 'rest_api_init', [ $this, 'register_api_routes' ] );

		// Hook into content pipeline
		add_action( 'pearblog_pipeline_completed', [ $this, 'enrich_published_content' ], 10, 2 );

		// Add content filters
		add_filter( 'the_content', [ $this, 'inject_internal_links' ], 20 );

		// Register shortcodes
		add_shortcode( 'decision_assistant', [ $this, 'decision_assistant_shortcode' ] );
		add_shortcode( 'lead_form', [ $this, 'lead_form_shortcode' ] );
		add_shortcode( 'experts', [ $this, 'experts_shortcode' ] );
		add_shortcode( 'comparison', [ $this, 'comparison_shortcode' ] );
		add_shortcode( 'ranking', [ $this, 'ranking_shortcode' ] );
		add_shortcode( 'calculator', [ $this, 'calculator_shortcode' ] );

		// Register admin menu
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
	}

	/**
	 * Register custom post types
	 */
	public function register_post_types(): void {
		$this->comparison_engine->register_post_type();
		$this->ranking_engine->register_post_type();
		$this->lead_generator->register_post_type();

		// Register Calculator post type
		register_post_type( 'pearblog_calculator', [
			'labels' => [
				'name' => 'Kalkulatory',
				'singular_name' => 'Kalkulator',
			],
			'public' => true,
			'has_archive' => true,
			'rewrite' => [ 'slug' => 'kalkulator' ],
			'supports' => [ 'title', 'editor' ],
			'show_in_rest' => true,
		] );

		// Register Offer post type
		register_post_type( 'pearblog_offer', [
			'labels' => [
				'name' => 'Oferty',
				'singular_name' => 'Oferta',
			],
			'public' => true,
			'has_archive' => true,
			'rewrite' => [ 'slug' => 'oferta' ],
			'supports' => [ 'title', 'editor', 'thumbnail' ],
			'show_in_rest' => true,
		] );

		// Register Expert post type
		register_post_type( 'pearblog_expert', [
			'labels' => [
				'name' => 'Specjaliści',
				'singular_name' => 'Specjalista',
			],
			'public' => true,
			'has_archive' => true,
			'rewrite' => [ 'slug' => 'specjalista' ],
			'supports' => [ 'title', 'editor', 'thumbnail' ],
			'show_in_rest' => true,
		] );
	}

	/**
	 * Register taxonomies
	 */
	public function register_taxonomies(): void {
		// Offer tags
		register_taxonomy( 'pearblog_offer_tag', 'pearblog_offer', [
			'labels' => [
				'name' => 'Tagi ofert',
				'singular_name' => 'Tag oferty',
			],
			'hierarchical' => false,
			'show_in_rest' => true,
		] );
	}

	/**
	 * Register REST API routes
	 */
	public function register_api_routes(): void {
		( new DecisionPlatformAPI() )->register_routes();
	}

	/**
	 * Enrich published content with Decision Platform features
	 *
	 * @param int $post_id
	 * @param mixed $pipeline_data
	 */
	public function enrich_published_content( int $post_id, $pipeline_data = null ): void {
		if ( ! is_array( $pipeline_data ) ) {
			$pipeline_data = [];
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		// Detect intent
		$intent_data = $this->intent_detector->detect( $post->post_content );
		$this->intent_detector->enrich_content( $post_id, $intent_data );

		// Build link graph
		$this->link_graph->build_for_post( $post_id );
	}

	/**
	 * Inject internal links into content
	 *
	 * @param string $content
	 * @return string
	 */
	public function inject_internal_links( string $content ): string {
		if ( ! is_singular( 'post' ) ) {
			return $content;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return $content;
		}

		return $this->link_graph->inject_links( $post_id, $content );
	}

	/**
	 * Decision assistant shortcode
	 *
	 * @return string
	 */
	public function decision_assistant_shortcode(): string {
		return $this->decision_assistant->render_widget();
	}

	/**
	 * Lead form shortcode
	 *
	 * @param array<string, mixed> $atts
	 * @return string
	 */
	public function lead_form_shortcode( array $atts ): string {
		$atts = shortcode_atts( [
			'title' => 'Otrzymaj bezpłatną wycenę',
			'category' => '',
		], $atts );

		return BlockRenderer::render( [
			'type' => 'lead_form',
			'data' => $atts,
		] );
	}

	/**
	 * Experts shortcode
	 *
	 * @param array<string, mixed> $atts
	 * @return string
	 */
	public function experts_shortcode( array $atts ): string {
		$atts = shortcode_atts( [
			'ids' => '',
			'category' => '',
			'city' => '',
			'limit' => 3,
		], $atts );

		$expert_ids = ! empty( $atts['ids'] ) ? explode( ',', $atts['ids'] ) : [];

		// If no IDs provided, search by category/city
		if ( empty( $expert_ids ) ) {
			$args = [
				'post_type' => 'pearblog_expert',
				'post_status' => 'publish',
				'posts_per_page' => (int) $atts['limit'],
			];

			$meta_query = [];
			if ( ! empty( $atts['category'] ) ) {
				$meta_query[] = [
					'key' => 'pearblog_expert_category',
					'value' => $atts['category'],
					'compare' => '=',
				];
			}
			if ( ! empty( $atts['city'] ) ) {
				$meta_query[] = [
					'key' => 'pearblog_expert_location',
					'value' => $atts['city'],
					'compare' => 'LIKE',
				];
			}

			if ( ! empty( $meta_query ) ) {
				$args['meta_query'] = $meta_query;
			}

			$posts = get_posts( $args );
			$expert_ids = array_map( function( $post ) {
				return $post->ID;
			}, $posts );
		}

		return BlockRenderer::render( [
			'type' => 'experts',
			'data' => [
				'expert_ids' => $expert_ids,
			],
		] );
	}

	/**
	 * Comparison shortcode
	 *
	 * @param array<string, mixed> $atts
	 * @return string
	 */
	public function comparison_shortcode( array $atts ): string {
		$atts = shortcode_atts( [
			'id' => 0,
		], $atts );

		return BlockRenderer::render( [
			'type' => 'comparison',
			'data' => [
				'comparison_id' => (int) $atts['id'],
			],
		] );
	}

	/**
	 * Ranking shortcode
	 *
	 * @param array<string, mixed> $atts
	 * @return string
	 */
	public function ranking_shortcode( array $atts ): string {
		$atts = shortcode_atts( [
			'id' => 0,
			'limit' => 10,
		], $atts );

		return BlockRenderer::render( [
			'type' => 'ranking',
			'data' => [
				'ranking_id' => (int) $atts['id'],
				'limit' => (int) $atts['limit'],
			],
		] );
	}

	/**
	 * Calculator shortcode
	 *
	 * @param array<string, mixed> $atts
	 * @return string
	 */
	public function calculator_shortcode( array $atts ): string {
		$atts = shortcode_atts( [
			'id' => 0,
		], $atts );

		return BlockRenderer::render( [
			'type' => 'calculator',
			'data' => [
				'calculator_id' => (int) $atts['id'],
			],
		] );
	}

	/**
	 * Register admin menu
	 */
	public function register_admin_menu(): void {
		add_submenu_page(
			'pearblog-engine',
			'Decision Platform',
			'Decision Platform',
			'manage_options',
			'pearblog-decision-platform',
			[ $this, 'render_admin_page' ]
		);
	}

	/**
	 * Render admin page
	 */
	public function render_admin_page(): void {
		?>
		<div class="wrap">
			<h1>🚀 Poradnik.pro Decision Platform</h1>

			<div class="card">
				<h2>Status systemu</h2>
				<ul>
					<li>✅ Porównania: <?php echo wp_count_posts( 'pearblog_comparison' )->publish ?? 0; ?></li>
					<li>✅ Rankingi: <?php echo wp_count_posts( 'pearblog_ranking' )->publish ?? 0; ?></li>
					<li>✅ Kalkulatory: <?php echo wp_count_posts( 'pearblog_calculator' )->publish ?? 0; ?></li>
					<li>✅ Oferty: <?php echo wp_count_posts( 'pearblog_offer' )->publish ?? 0; ?></li>
					<li>✅ Specjaliści: <?php echo wp_count_posts( 'pearblog_expert' )->publish ?? 0; ?></li>
					<li>✅ Leady: <?php echo wp_count_posts( 'pearblog_lead' )->publish ?? 0; ?></li>
				</ul>
			</div>

			<div class="card">
				<h2>Narzędzia</h2>
				<form method="post">
					<?php wp_nonce_field( 'pearblog_rebuild_link_graph' ); ?>
					<button type="submit" name="action" value="rebuild_link_graph" class="button button-primary">
						Przebuduj graf linków
					</button>
				</form>
			</div>

			<div class="card">
				<h2>Shortcodes</h2>
				<p>Dostępne shortcodes:</p>
				<ul>
					<li><code>[decision_assistant]</code> - Asystent decyzji AI</li>
					<li><code>[lead_form category="kategoria"]</code> - Formularz leadowy</li>
					<li><code>[experts ids="1,2,3"]</code> - Lista specjalistów</li>
					<li><code>[comparison id="123"]</code> - Porównanie</li>
					<li><code>[ranking id="123" limit="10"]</code> - Ranking</li>
					<li><code>[calculator id="123"]</code> - Kalkulator</li>
				</ul>
			</div>
		</div>
		<?php

		// Handle actions
		if ( isset( $_POST['action'] ) && 'rebuild_link_graph' === $_POST['action'] ) {
			if ( wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'pearblog_rebuild_link_graph' ) ) {
				$this->link_graph->rebuild_all();
				echo '<div class="notice notice-success"><p>Graf linków został przebudowany!</p></div>';
			}
		}
	}

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_frontend_assets(): void {
		// Enqueue decision platform JavaScript
		wp_enqueue_script(
			'pearblog-decision-platform',
			PEARBLOG_ENGINE_URL . 'assets/js/decision-platform.js',
			[ 'jquery' ],
			PEARBLOG_ENGINE_VERSION,
			true
		);

		// Localize script with REST API URLs
		wp_localize_script( 'pearblog-decision-platform', 'pearBlogDecision', [
			'apiUrl' => rest_url( 'pearblog/v1' ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
		] );

		// Enqueue styles
		wp_enqueue_style(
			'pearblog-decision-platform',
			PEARBLOG_ENGINE_URL . 'assets/css/decision-platform.css',
			[],
			PEARBLOG_ENGINE_VERSION
		);
	}
}

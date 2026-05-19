<?php
/**
 * Search Engine abstraction
 *
 * Provides a unified search interface that works with:
 *  - WordPress native search (default, zero-config)
 *  - Meilisearch (fast full-text, when configured)
 *  - Typesense (alternative fast search)
 *
 * The driver is selected via the `pearblog_search_driver` option:
 *   'wordpress' | 'meilisearch' | 'typesense'
 *
 * @package PearBlogEngine\Search
 */

declare(strict_types=1);

namespace PearBlogEngine\Search;

use PearBlogEngine\Core\FeatureFlags;
use PearBlogEngine\Core\ModuleRegistry;

/**
 * SearchEngine — unified search gateway.
 */
class SearchEngine {

	private string $driver;
	private SearchIndexer $indexer;

	public function __construct( ?SearchIndexer $indexer = null ) {
		$this->driver  = (string) get_option( 'pearblog_search_driver', 'wordpress' );
		$this->indexer = $indexer ?? new SearchIndexer( $this );
	}

	// -----------------------------------------------------------------------
	// Boot
	// -----------------------------------------------------------------------

	/**
	 * Register the search module.
	 */
	public function register(): void {
		if ( FeatureFlags::disabled( 'search_engine' ) ) {
			return;
		}

		ModuleRegistry::add( 'search_engine', 'Search Engine', '1.0.0', __NAMESPACE__ );

		$this->indexer->register();

		// Override WordPress native search if an external driver is active.
		if ( $this->driver !== 'wordpress' ) {
			add_filter( 'pre_get_posts', [ $this, 'intercept_search_query' ] );
		}

		// Autocomplete REST endpoint.
		add_action( 'rest_api_init', [ $this, 'register_search_routes' ] );
	}

	// -----------------------------------------------------------------------
	// Search
	// -----------------------------------------------------------------------

	/**
	 * Execute a search query.
	 *
	 * @param string               $query   Raw search string.
	 * @param array<string, mixed> $options Optional: post_type, category, city, limit, offset.
	 * @return array{
	 *   total: int,
	 *   hits:  array<array<string, mixed>>,
	 *   query: string,
	 *   driver: string,
	 * }
	 */
	public function search( string $query, array $options = [] ): array {
		return match ( $this->driver ) {
			'meilisearch' => $this->search_meilisearch( $query, $options ),
			'typesense'   => $this->search_typesense( $query, $options ),
			default       => $this->search_wordpress( $query, $options ),
		};
	}

	/**
	 * Return autocomplete suggestions for a partial query.
	 *
	 * @param string $partial  User's current input.
	 * @param int    $limit
	 * @return list<array{label: string, url: string, type: string}>
	 */
	public function autocomplete( string $partial, int $limit = 6 ): array {
		$partial = sanitize_text_field( $partial );

		if ( strlen( $partial ) < 2 ) {
			return [];
		}

		// Combine trending searches + WordPress live results.
		$trending    = $this->trending_suggestions( $partial, 3 );
		$live        = $this->wordpress_autocomplete( $partial, $limit - count( $trending ) );

		return array_slice( array_merge( $trending, $live ), 0, $limit );
	}

	// -----------------------------------------------------------------------
	// Driver implementations
	// -----------------------------------------------------------------------

	/**
	 * WordPress native search (fallback, always available).
	 *
	 * @param string               $query
	 * @param array<string, mixed> $options
	 * @return array<string, mixed>
	 */
	private function search_wordpress( string $query, array $options ): array {
		$args = [
			's'              => sanitize_text_field( $query ),
			'posts_per_page' => max( 1, min( 50, (int) ( $options['limit'] ?? 10 ) ) ),
			'paged'          => max( 1, (int) ( $options['page'] ?? 1 ) ),
			'post_status'    => 'publish',
		];

		if ( ! empty( $options['post_type'] ) ) {
			$args['post_type'] = $options['post_type'];
		}

		$wp_query = new \WP_Query( $args );
		$hits     = [];

		foreach ( $wp_query->posts as $post ) {
			$hits[] = [
				'id'      => $post->ID,
				'title'   => $post->post_title,
				'url'     => get_permalink( $post->ID ),
				'excerpt' => wp_trim_words( $post->post_content, 20 ),
				'type'    => $post->post_type,
			];
		}

		return [
			'total'  => (int) $wp_query->found_posts,
			'hits'   => $hits,
			'query'  => $query,
			'driver' => 'wordpress',
		];
	}

	/**
	 * Meilisearch driver (when PEARBLOG_MEILISEARCH_HOST is configured).
	 *
	 * @param string               $query
	 * @param array<string, mixed> $options
	 * @return array<string, mixed>
	 */
	private function search_meilisearch( string $query, array $options ): array {
		$host   = defined( 'PEARBLOG_MEILISEARCH_HOST' ) ? PEARBLOG_MEILISEARCH_HOST : '';
		$key    = defined( 'PEARBLOG_MEILISEARCH_KEY' )  ? PEARBLOG_MEILISEARCH_KEY  : '';
		$index  = defined( 'PEARBLOG_MEILISEARCH_INDEX' ) ? PEARBLOG_MEILISEARCH_INDEX : 'pearblog';

		if ( ! $host ) {
			return $this->search_wordpress( $query, $options ); // graceful fallback
		}

		$limit  = max( 1, min( 50, (int) ( $options['limit'] ?? 10 ) ) );
		$offset = max( 0, (int) ( $options['offset'] ?? 0 ) );

		$response = wp_remote_post(
			trailingslashit( $host ) . "indexes/{$index}/search",
			[
				'timeout' => 5,
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => "Bearer {$key}",
				],
				'body' => wp_json_encode( [
					'q'      => $query,
					'limit'  => $limit,
					'offset' => $offset,
				] ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return $this->search_wordpress( $query, $options );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return [
			'total'  => $body['totalHits'] ?? 0,
			'hits'   => $body['hits']      ?? [],
			'query'  => $query,
			'driver' => 'meilisearch',
		];
	}

	/**
	 * Typesense driver stub — mirrors Meilisearch structure.
	 *
	 * @param string               $query
	 * @param array<string, mixed> $options
	 * @return array<string, mixed>
	 */
	private function search_typesense( string $query, array $options ): array {
		$host       = defined( 'PEARBLOG_TYPESENSE_HOST' )       ? PEARBLOG_TYPESENSE_HOST       : '';
		$api_key    = defined( 'PEARBLOG_TYPESENSE_API_KEY' )     ? PEARBLOG_TYPESENSE_API_KEY    : '';
		$collection = defined( 'PEARBLOG_TYPESENSE_COLLECTION' )  ? PEARBLOG_TYPESENSE_COLLECTION : 'pearblog';

		if ( ! $host ) {
			return $this->search_wordpress( $query, $options );
		}

		$limit = max( 1, min( 50, (int) ( $options['limit'] ?? 10 ) ) );
		$page  = max( 1, (int) ( $options['page'] ?? 1 ) );

		$response = wp_remote_get(
			trailingslashit( $host ) . "collections/{$collection}/documents/search?" . http_build_query( [
				'q'         => $query,
				'query_by'  => 'title,content',
				'per_page'  => $limit,
				'page'      => $page,
			] ),
			[
				'timeout' => 5,
				'headers' => [ 'X-TYPESENSE-API-KEY' => $api_key ],
			]
		);

		if ( is_wp_error( $response ) ) {
			return $this->search_wordpress( $query, $options );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$hits = array_map( fn( $h ) => $h['document'] ?? $h, $body['hits'] ?? [] );

		return [
			'total'  => $body['found'] ?? 0,
			'hits'   => $hits,
			'query'  => $query,
			'driver' => 'typesense',
		];
	}

	// -----------------------------------------------------------------------
	// Autocomplete helpers
	// -----------------------------------------------------------------------

	/**
	 * @param string $partial
	 * @param int    $limit
	 * @return list<array<string, mixed>>
	 */
	private function trending_suggestions( string $partial, int $limit ): array {
		$trending = [
			'remont łazienki'      => '/kalkulator/remont-lazienki/',
			'pompa ciepła'         => '/porownanie/pompa-ciepla-vs-gaz/',
			'hydraulik warszawa'   => '/hydraulik/warszawa/',
			'mechanik krakow'      => '/mechanik/krakow/',
			'koszt budowy domu'    => '/kalkulator/budowa-domu/',
			'prawnik gdansk'       => '/prawnik/gdansk/',
			'elektryk poznan'      => '/elektryk/poznan/',
		];

		$results = [];
		foreach ( $trending as $label => $url ) {
			if ( stripos( $label, $partial ) !== false ) {
				$results[] = [ 'label' => $label, 'url' => $url, 'type' => 'trending' ];
			}
			if ( count( $results ) >= $limit ) {
				break;
			}
		}

		return $results;
	}

	/**
	 * @param string $partial
	 * @param int    $limit
	 * @return list<array<string, mixed>>
	 */
	private function wordpress_autocomplete( string $partial, int $limit ): array {
		if ( $limit <= 0 ) {
			return [];
		}

		$posts = get_posts( [
			's'              => $partial,
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
			'post_type'      => [ 'post', 'page', 'pearblog_ranking', 'pearblog_comparison' ],
		] );

		return array_map( fn( $p ) => [
			'label' => $p->post_title,
			'url'   => get_permalink( $p->ID ),
			'type'  => $p->post_type,
		], $posts );
	}

	// -----------------------------------------------------------------------
	// WP Query intercept
	// -----------------------------------------------------------------------

	/**
	 * Intercept main search query and redirect to external driver.
	 *
	 * @param \WP_Query $query
	 */
	public function intercept_search_query( \WP_Query $query ): void {
		if ( ! $query->is_main_query() || ! $query->is_search() ) {
			return;
		}

		$search_term = $query->get( 's' );
		if ( ! $search_term ) {
			return;
		}

		$results = $this->search( $search_term, [ 'limit' => (int) $query->get( 'posts_per_page' ) ] );

		// Inject IDs so WP loads the actual posts.
		if ( ! empty( $results['hits'] ) ) {
			$ids = array_column( $results['hits'], 'id' );
			$ids = array_filter( array_map( 'intval', $ids ) );

			if ( ! empty( $ids ) ) {
				$query->set( 'post__in', $ids );
				$query->set( 'orderby', 'post__in' );
				$query->set( 's', '' ); // Prevent double WP search.
			}
		}
	}

	// -----------------------------------------------------------------------
	// REST routes
	// -----------------------------------------------------------------------

	public function register_search_routes(): void {
		register_rest_route( 'pearblog/v1', '/search', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ $this, 'handle_search_request' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'q'     => [ 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
				'limit' => [ 'type' => 'integer', 'default' => 10, 'minimum' => 1, 'maximum' => 50 ],
			],
		] );

		register_rest_route( 'pearblog/v1', '/search/autocomplete', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ $this, 'handle_autocomplete_request' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'q'     => [ 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
				'limit' => [ 'type' => 'integer', 'default' => 6, 'minimum' => 1, 'maximum' => 10 ],
			],
		] );
	}

	/** @param \WP_REST_Request $request */
	public function handle_search_request( $request ): \WP_REST_Response {
		$results = $this->search( (string) $request->get_param( 'q' ), $request->get_params() );
		return new \WP_REST_Response( array_merge( $results, [ 'success' => true ] ) );
	}

	/** @param \WP_REST_Request $request */
	public function handle_autocomplete_request( $request ): \WP_REST_Response {
		$suggestions = $this->autocomplete(
			(string) $request->get_param( 'q' ),
			(int) $request->get_param( 'limit' )
		);
		return new \WP_REST_Response( [ 'success' => true, 'suggestions' => $suggestions ] );
	}
}

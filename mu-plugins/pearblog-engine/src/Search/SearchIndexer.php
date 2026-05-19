<?php
/**
 * Search Indexer
 *
 * Keeps the external search driver (Meilisearch / Typesense) in sync
 * with WordPress content via publish/update/delete hooks.
 *
 * Falls back to a no-op when no external driver is configured.
 *
 * @package PearBlogEngine\Search
 */

declare(strict_types=1);

namespace PearBlogEngine\Search;

/**
 * SearchIndexer
 *
 * Registers WordPress hooks and synchronises content to the
 * configured search driver.
 */
class SearchIndexer {

	/**
	 * Post types to index.
	 *
	 * @var list<string>
	 */
	private array $indexed_post_types = [
		'post',
		'page',
		'pearblog_ranking',
		'pearblog_comparison',
		'pearblog_calculator',
		'pearblog_expert',
	];

	private SearchEngine $engine;

	public function __construct( SearchEngine $engine ) {
		$this->engine = $engine;
	}

	// -----------------------------------------------------------------------
	// Boot
	// -----------------------------------------------------------------------

	/**
	 * Register WordPress hooks.
	 */
	public function register(): void {
		// Index on publish/update.
		add_action( 'save_post', [ $this, 'on_save_post' ], 99, 2 );

		// Remove on trash/delete.
		add_action( 'trashed_post', [ $this, 'on_delete_post' ] );
		add_action( 'deleted_post', [ $this, 'on_delete_post' ] );

		// WP-CLI command for full re-index.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'pearblog search reindex', [ $this, 'cli_reindex' ] );
		}
	}

	// -----------------------------------------------------------------------
	// Hooks
	// -----------------------------------------------------------------------

	/**
	 * Index or re-index a post when it is saved.
	 *
	 * @param int      $post_id
	 * @param \WP_Post $post
	 */
	public function on_save_post( int $post_id, \WP_Post $post ): void {
		if (
			wp_is_post_autosave( $post_id ) ||
			wp_is_post_revision( $post_id ) ||
			! in_array( $post->post_type, $this->indexed_post_types, true )
		) {
			return;
		}

		if ( $post->post_status === 'publish' ) {
			$this->index_post( $post );
		} else {
			$this->remove_post( $post_id );
		}
	}

	/**
	 * Remove a post from the index when trashed/deleted.
	 *
	 * @param int $post_id
	 */
	public function on_delete_post( int $post_id ): void {
		$this->remove_post( $post_id );
	}

	// -----------------------------------------------------------------------
	// Indexing operations
	// -----------------------------------------------------------------------

	/**
	 * Build and dispatch an index document for a post.
	 *
	 * @param \WP_Post $post
	 */
	public function index_post( \WP_Post $post ): void {
		$document = $this->build_document( $post );
		$driver   = (string) get_option( 'pearblog_search_driver', 'wordpress' );

		match ( $driver ) {
			'meilisearch' => $this->push_to_meilisearch( $document ),
			'typesense'   => $this->push_to_typesense( $document ),
			'wordpress'   => null, // WordPress native search — no external index needed.
			default       => error_log( "[PearBlogEngine] SearchIndexer: unknown driver '{$driver}', skipping index push for post {$post->ID}." ),
		};
	}

	/**
	 * Remove a document from the external index.
	 *
	 * @param int $post_id
	 */
	public function remove_post( int $post_id ): void {
		$driver = (string) get_option( 'pearblog_search_driver', 'wordpress' );

		match ( $driver ) {
			'meilisearch' => $this->delete_from_meilisearch( $post_id ),
			'typesense'   => $this->delete_from_typesense( $post_id ),
			'wordpress'   => null,
			default       => error_log( "[PearBlogEngine] SearchIndexer: unknown driver '{$driver}', skipping delete for post {$post_id}." ),
		};
	}

	/**
	 * Full site re-index.
	 *
	 * @return int Number of documents indexed.
	 */
	public function reindex_all(): int {
		$indexed = 0;

		foreach ( $this->indexed_post_types as $post_type ) {
			$posts = get_posts( [
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
			] );

			foreach ( $posts as $post ) {
				$this->index_post( $post );
				$indexed++;
			}
		}

		return $indexed;
	}

	// -----------------------------------------------------------------------
	// Document builder
	// -----------------------------------------------------------------------

	/**
	 * Build a flat document for indexing from a WP_Post.
	 *
	 * @param \WP_Post $post
	 * @return array<string, mixed>
	 */
	private function build_document( \WP_Post $post ): array {
		return [
			'id'          => $post->ID,
			'title'       => $post->post_title,
			'content'     => wp_strip_all_tags( $post->post_content ),
			'excerpt'     => wp_trim_words( $post->post_content, 30 ),
			'url'         => get_permalink( $post->ID ),
			'post_type'   => $post->post_type,
			'category'    => get_post_meta( $post->ID, 'pearblog_expert_category', true ) ?: '',
			'city'        => get_post_meta( $post->ID, 'pearblog_expert_location', true ) ?: '',
			'published_at' => strtotime( $post->post_date_gmt ),
			'modified_at'  => strtotime( $post->post_modified_gmt ),
		];
	}

	// -----------------------------------------------------------------------
	// Driver: Meilisearch
	// -----------------------------------------------------------------------

	/** @param array<string, mixed> $document */
	private function push_to_meilisearch( array $document ): void {
		$host  = defined( 'PEARBLOG_MEILISEARCH_HOST' ) ? PEARBLOG_MEILISEARCH_HOST : '';
		$key   = defined( 'PEARBLOG_MEILISEARCH_KEY' )  ? PEARBLOG_MEILISEARCH_KEY  : '';
		$index = defined( 'PEARBLOG_MEILISEARCH_INDEX' ) ? PEARBLOG_MEILISEARCH_INDEX : 'pearblog';

		if ( ! $host ) {
			return;
		}

		wp_remote_post(
			trailingslashit( $host ) . "indexes/{$index}/documents",
			[
				'timeout' => 5,
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => "Bearer {$key}",
				],
				'body' => wp_json_encode( [ $document ] ),
			]
		);
	}

	private function delete_from_meilisearch( int $post_id ): void {
		$host  = defined( 'PEARBLOG_MEILISEARCH_HOST' ) ? PEARBLOG_MEILISEARCH_HOST : '';
		$key   = defined( 'PEARBLOG_MEILISEARCH_KEY' )  ? PEARBLOG_MEILISEARCH_KEY  : '';
		$index = defined( 'PEARBLOG_MEILISEARCH_INDEX' ) ? PEARBLOG_MEILISEARCH_INDEX : 'pearblog';

		if ( ! $host ) {
			return;
		}

		wp_remote_request(
			trailingslashit( $host ) . "indexes/{$index}/documents/{$post_id}",
			[
				'method'  => 'DELETE',
				'timeout' => 5,
				'headers' => [ 'Authorization' => "Bearer {$key}" ],
			]
		);
	}

	// -----------------------------------------------------------------------
	// Driver: Typesense
	// -----------------------------------------------------------------------

	/** @param array<string, mixed> $document */
	private function push_to_typesense( array $document ): void {
		$host       = defined( 'PEARBLOG_TYPESENSE_HOST' )      ? PEARBLOG_TYPESENSE_HOST      : '';
		$api_key    = defined( 'PEARBLOG_TYPESENSE_API_KEY' )    ? PEARBLOG_TYPESENSE_API_KEY   : '';
		$collection = defined( 'PEARBLOG_TYPESENSE_COLLECTION' ) ? PEARBLOG_TYPESENSE_COLLECTION : 'pearblog';

		if ( ! $host ) {
			return;
		}

		wp_remote_post(
			trailingslashit( $host ) . "collections/{$collection}/documents?action=upsert",
			[
				'timeout' => 5,
				'headers' => [
					'Content-Type'       => 'application/json',
					'X-TYPESENSE-API-KEY' => $api_key,
				],
				'body' => wp_json_encode( $document ),
			]
		);
	}

	private function delete_from_typesense( int $post_id ): void {
		$host       = defined( 'PEARBLOG_TYPESENSE_HOST' )      ? PEARBLOG_TYPESENSE_HOST      : '';
		$api_key    = defined( 'PEARBLOG_TYPESENSE_API_KEY' )    ? PEARBLOG_TYPESENSE_API_KEY   : '';
		$collection = defined( 'PEARBLOG_TYPESENSE_COLLECTION' ) ? PEARBLOG_TYPESENSE_COLLECTION : 'pearblog';

		if ( ! $host ) {
			return;
		}

		wp_remote_request(
			trailingslashit( $host ) . "collections/{$collection}/documents/{$post_id}",
			[
				'method'  => 'DELETE',
				'timeout' => 5,
				'headers' => [ 'X-TYPESENSE-API-KEY' => $api_key ],
			]
		);
	}

	// -----------------------------------------------------------------------
	// WP-CLI
	// -----------------------------------------------------------------------

	/**
	 * WP-CLI: wp pearblog search reindex
	 *
	 * @param array<int, string>   $args
	 * @param array<string, mixed> $assoc_args
	 */
	public function cli_reindex( array $args, array $assoc_args ): void {
		\WP_CLI::log( 'Starting full search re-index…' );
		$count = $this->reindex_all();
		\WP_CLI::success( "Indexed {$count} documents." );
	}
}

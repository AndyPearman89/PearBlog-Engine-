<?php
/**
 * Content Import / Export — bulk import topics and export generated articles.
 *
 * Import:
 *   CSV columns (header required): topic [,priority] [,tags]
 *   JSON format: array of objects with "topic" key (plus optional "priority", "tags").
 *   Duplicate topics (compared against the existing queue and published post titles)
 *   are skipped automatically.
 *
 * Export:
 *   Articles (WP posts) are exported with: post_id, title, slug, status,
 *   quality_score, word_count, published_date, meta_description.
 *   Supported formats: CSV and JSON.
 *
 * REST endpoints:
 *   POST /pearblog/v1/import/topics        – accepts multipart CSV or raw JSON body
 *   GET  /pearblog/v1/export/articles      – returns CSV (default) or JSON
 *
 * @package PearBlogEngine\Pipeline
 */

declare(strict_types=1);

namespace PearBlogEngine\Pipeline;

use PearBlogEngine\Content\TopicQueue;

/**
 * Handles bulk import of topics and bulk export of generated articles.
 */
class ContentImportExport {

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Post meta key for quality score (set by QualityScorer). */
	private const META_QUALITY_SCORE  = '_pearblog_quality_score';

	/** Post meta key for SEO description (set by SEOEngine). */
	private const META_META_DESCRIPTION = '_pearblog_meta_description';

	/** Maximum import batch size (topics per request). */
	public const MAX_IMPORT_BATCH = 500;

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register REST routes.
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register REST routes for import and export.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/import/topics', [
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'rest_import_topics' ],
			'permission_callback' => [ $this, 'rest_admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/export/articles', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ $this, 'rest_export_articles' ],
			'permission_callback' => [ $this, 'rest_permission' ],
			'args'                => [
				'format' => [
					'type'              => 'string',
					'default'           => 'csv',
					'enum'              => [ 'csv', 'json' ],
					'sanitize_callback' => 'sanitize_text_field',
				],
				'limit' => [
					'type'              => 'integer',
					'default'           => 100,
					'minimum'           => 1,
					'maximum'           => 1000,
					'sanitize_callback' => 'absint',
				],
			],
		] );
	}

	// -----------------------------------------------------------------------
	// Permission callbacks
	// -----------------------------------------------------------------------

	/**
	 * Admin-only permission (manage_options).
	 */
	public function rest_admin_permission( \WP_REST_Request $request ): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Read permission: manage_options OR valid API key.
	 */
	public function rest_permission( \WP_REST_Request $request ): bool {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		$stored_key = (string) get_option( 'pearblog_api_key', '' );
		if ( '' !== $stored_key ) {
			$auth   = (string) $request->get_header( 'authorization' );
			$bearer = ltrim( substr( $auth, 6 ) );
			if ( '' !== $bearer && hash_equals( $stored_key, $bearer ) ) {
				return true;
			}
		}
		return false;
	}

	// -----------------------------------------------------------------------
	// REST callbacks
	// -----------------------------------------------------------------------

	/**
	 * POST /pearblog/v1/import/topics
	 *
	 * Body:  { "format": "csv"|"json", "data": "<content>", "site_id": 1 }
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_import_topics( \WP_REST_Request $request ): \WP_REST_Response {
		$format  = (string) ( $request->get_param( 'format' ) ?? 'csv' );
		$data    = (string) ( $request->get_param( 'data' )   ?? '' );
		$site_id = (int)    ( $request->get_param( 'site_id' ) ?? 1 );

		if ( '' === $data ) {
			return new \WP_REST_Response( [ 'error' => '"data" is required' ], 400 );
		}

		try {
			if ( 'json' === $format ) {
				$result = $this->import_topics_json( $data, $site_id );
			} else {
				$result = $this->import_topics_csv( $data, $site_id );
			}
		} catch ( \InvalidArgumentException $e ) {
			return new \WP_REST_Response( [ 'error' => $e->getMessage() ], 422 );
		}

		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * GET /pearblog/v1/export/articles
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_export_articles( \WP_REST_Request $request ): \WP_REST_Response {
		$format = (string) ( $request->get_param( 'format' ) ?? 'csv' );
		$limit  = (int)    ( $request->get_param( 'limit' )  ?? 100 );

		$rows = $this->get_article_rows( $limit );

		if ( 'json' === $format ) {
			return new \WP_REST_Response( [ 'articles' => $rows, 'count' => count( $rows ) ], 200 );
		}

		return new \WP_REST_Response(
			[ 'csv' => $this->rows_to_csv( $rows ), 'count' => count( $rows ) ],
			200
		);
	}

	// -----------------------------------------------------------------------
	// Topic import
	// -----------------------------------------------------------------------

	/**
	 * Import topics from a CSV string.
	 *
	 * Expected header row: topic[,priority[,tags]]
	 * Returns a result summary array.
	 *
	 * @param string $csv_content Raw CSV text.
	 * @param int    $site_id     WP blog / site ID.
	 * @return array{imported: int, skipped: int, errors: string[]}
	 * @throws \InvalidArgumentException When the CSV cannot be parsed.
	 */
	public function import_topics_csv( string $csv_content, int $site_id = 1 ): array {
		$csv_content = $this->normalise_line_endings( $csv_content );
		$lines       = array_filter( explode( "\n", $csv_content ) );

		if ( empty( $lines ) ) {
			throw new \InvalidArgumentException( 'CSV content is empty.' );
		}

		// Parse header.
		$header   = str_getcsv( array_shift( $lines ) );
		$header   = array_map( 'strtolower', array_map( 'trim', $header ) );
		$topic_idx = array_search( 'topic', $header, true );

		if ( false === $topic_idx ) {
			throw new \InvalidArgumentException( 'CSV must have a "topic" header column.' );
		}

		$topics = [];
		$errors = [];
		foreach ( array_values( $lines ) as $i => $line ) {
			$row = str_getcsv( $line );
			if ( ! isset( $row[ $topic_idx ] ) ) {
				$errors[] = "Row " . ( $i + 2 ) . ": missing topic column.";
				continue;
			}
			$topic = trim( $row[ $topic_idx ] );
			if ( '' === $topic ) {
				continue;
			}
			$topics[] = $topic;
		}

		return $this->push_deduped( $topics, $site_id, $errors );
	}

	/**
	 * Import topics from a JSON string.
	 *
	 * Accepts: [ "topic string", ... ] or [ { "topic": "..." }, ... ]
	 *
	 * @param string $json    Raw JSON string.
	 * @param int    $site_id WP blog / site ID.
	 * @return array{imported: int, skipped: int, errors: string[]}
	 * @throws \InvalidArgumentException When JSON is invalid or missing "topic" keys.
	 */
	public function import_topics_json( string $json, int $site_id = 1 ): array {
		$decoded = json_decode( $json, true );

		if ( ! is_array( $decoded ) ) {
			throw new \InvalidArgumentException( 'Invalid JSON: expected a top-level array.' );
		}

		$topics = [];
		$errors = [];
		foreach ( $decoded as $i => $item ) {
			if ( is_string( $item ) ) {
				$topic = trim( $item );
			} elseif ( is_array( $item ) && isset( $item['topic'] ) ) {
				$topic = trim( (string) $item['topic'] );
			} else {
				$errors[] = "Item {$i}: expected a string or an object with a \"topic\" key.";
				continue;
			}

			if ( '' === $topic ) {
				continue;
			}
			$topics[] = $topic;
		}

		return $this->push_deduped( $topics, $site_id, $errors );
	}

	// -----------------------------------------------------------------------
	// Article export
	// -----------------------------------------------------------------------

	/**
	 * Export generated articles as a CSV string.
	 *
	 * @param int[] $post_ids Post IDs to export; empty = all pearblog posts.
	 * @return string         RFC-4180 CSV with UTF-8 BOM.
	 */
	public function export_articles_csv( array $post_ids = [] ): string {
		$rows = $this->get_article_rows( 1000, $post_ids );
		return $this->rows_to_csv( $rows );
	}

	/**
	 * Export generated articles as a JSON string.
	 *
	 * @param int[] $post_ids Post IDs to export; empty = all pearblog posts.
	 * @return string         Pretty-printed JSON.
	 */
	public function export_articles_json( array $post_ids = [] ): string {
		$rows = $this->get_article_rows( 1000, $post_ids );
		return (string) wp_json_encode( $rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Push a de-duplicated batch of topics into the TopicQueue.
	 *
	 * @param string[] $topics  Raw topic strings.
	 * @param int      $site_id WP blog / site ID.
	 * @param string[] $errors  Pre-existing error messages.
	 * @return array{imported: int, skipped: int, errors: string[]}
	 */
	private function push_deduped( array $topics, int $site_id, array $errors ): array {
		$queue   = new TopicQueue( $site_id );
		$existing = array_map( 'strtolower', $queue->all() );
		$imported = 0;
		$skipped  = 0;
		$batch    = array_slice( $topics, 0, self::MAX_IMPORT_BATCH );

		foreach ( $batch as $topic ) {
			if ( in_array( strtolower( $topic ), $existing, true ) ) {
				$skipped++;
				continue;
			}
			$queue->push( $topic );
			$existing[] = strtolower( $topic );
			$imported++;
		}

		return [
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		];
	}

	/**
	 * Build the data rows for export.
	 *
	 * @param int   $limit    Max posts.
	 * @param int[] $post_ids Specific post IDs (empty = query all pearblog posts).
	 * @return array<int, array>
	 */
	private function get_article_rows( int $limit = 100, array $post_ids = [] ): array {
		if ( ! empty( $post_ids ) ) {
			$ids = $post_ids;
		} else {
			$ids = $this->query_pearblog_post_ids( $limit );
		}

		$rows = [];
		foreach ( $ids as $id ) {
			$rows[] = $this->build_export_row( $id );
		}

		return array_filter( $rows, fn( ?array $r ) => null !== $r );
	}

	/**
	 * Build a single export row for a post.
	 *
	 * @param int $post_id
	 * @return array|null  Null when the post does not exist.
	 */
	private function build_export_row( int $post_id ): ?array {
		$title    = get_the_title( $post_id );
		if ( '' === (string) $title ) {
			return null;
		}

		$quality_score    = (float) get_post_meta( $post_id, self::META_QUALITY_SCORE, true );
		$meta_description = (string) get_post_meta( $post_id, self::META_META_DESCRIPTION, true );
		$content          = (string) get_post_field( 'post_content', $post_id );
		$word_count       = str_word_count( wp_strip_all_tags( $content ) );
		$status           = (string) get_post_field( 'post_status', $post_id );
		$slug             = (string) get_post_field( 'post_name', $post_id );
		$date             = (string) get_post_field( 'post_date', $post_id );

		return [
			'post_id'          => $post_id,
			'title'            => $title,
			'slug'             => $slug,
			'status'           => $status,
			'quality_score'    => $quality_score,
			'word_count'       => $word_count,
			'published_date'   => $date,
			'meta_description' => $meta_description,
		];
	}

	/**
	 * Query post IDs for all PearBlog-generated posts.
	 *
	 * Uses `_pearblog_quality_score` meta presence as the marker.
	 *
	 * @param int $limit
	 * @return int[]
	 */
	private function query_pearblog_post_ids( int $limit ): array {
		$args = [
			'post_type'      => 'post',
			'post_status'    => [ 'publish', 'draft' ],
			'posts_per_page' => $limit,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => [
				[
					'key'     => self::META_QUALITY_SCORE,
					'compare' => 'EXISTS',
				],
			],
		];

		$query = new \WP_Query( $args );
		return is_array( $query->posts ) ? array_map( 'intval', $query->posts ) : [];
	}

	/**
	 * Convert an array of rows to an RFC-4180 CSV string (UTF-8 BOM prepended).
	 *
	 * @param array<int, array> $rows
	 * @return string
	 */
	public function rows_to_csv( array $rows ): string {
		if ( empty( $rows ) ) {
			return '';
		}

		$output  = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel compatibility.
		$headers = array_keys( reset( $rows ) );
		$output .= $this->csv_line( $headers );

		foreach ( $rows as $row ) {
			$output .= $this->csv_line( array_values( $row ) );
		}

		return $output;
	}

	/**
	 * Format a single CSV line.
	 *
	 * @param array $values
	 * @return string
	 */
	private function csv_line( array $values ): string {
		$escaped = array_map( function ( $v ): string {
			$v = (string) $v;
			if ( str_contains( $v, '"' ) || str_contains( $v, ',' ) || str_contains( $v, "\n" ) ) {
				return '"' . str_replace( '"', '""', $v ) . '"';
			}
			return $v;
		}, $values );
		return implode( ',', $escaped ) . "\n";
	}

	/**
	 * Normalise CR+LF and lone CR to LF.
	 */
	private function normalise_line_endings( string $text ): string {
		return str_replace( [ "\r\n", "\r" ], "\n", $text );
	}
}

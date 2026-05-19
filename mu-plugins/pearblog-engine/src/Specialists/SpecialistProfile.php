<?php
/**
 * Specialist Profile — entity and DB CRUD.
 *
 * Wraps the pearblog_specialists custom table introduced by SpecialistsSchema.
 * Also syncs with the pearblog_expert WP CPT for backward compatibility.
 *
 * @package PearBlogEngine\Specialists
 */

declare(strict_types=1);

namespace PearBlogEngine\Specialists;

/**
 * SpecialistProfile — data access and persistence for one specialist.
 */
class SpecialistProfile {

	private \wpdb $wpdb;
	private string $table;

	public function __construct() {
		global $wpdb;
		$this->wpdb  = $wpdb;
		$this->table = $wpdb->prefix . 'pearblog_specialists';
	}

	// -----------------------------------------------------------------------
	// Read
	// -----------------------------------------------------------------------

	/**
	 * Find a specialist by their database ID.
	 *
	 * @param int $id
	 * @return array<string, mixed>|null
	 */
	public function find( int $id ): ?array {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d AND is_active = 1", $id ),
			ARRAY_A
		);
		return $row ? $this->decode_json_fields( $row ) : null;
	}

	/**
	 * Find a specialist by their URL slug.
	 *
	 * @param string $slug
	 * @return array<string, mixed>|null
	 */
	public function find_by_slug( string $slug ): ?array {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->table} WHERE slug = %s AND is_active = 1", $slug ),
			ARRAY_A
		);
		return $row ? $this->decode_json_fields( $row ) : null;
	}

	/**
	 * Search specialists by category and/or city.
	 *
	 * @param array{category?: string, city?: string, limit?: int, offset?: int, order_by?: string} $filters
	 * @return array<array<string, mixed>>
	 */
	public function search( array $filters = [] ): array {
		$where  = [ 'is_active = 1' ];
		$values = [];

		if ( ! empty( $filters['category'] ) ) {
			$where[]  = 'category = %s';
			$values[] = $filters['category'];
		}
		if ( ! empty( $filters['city'] ) ) {
			$where[]  = 'city = %s';
			$values[] = $filters['city'];
		}
		if ( ! empty( $filters['verification_level'] ) ) {
			$where[]  = 'verification_level = %s';
			$values[] = $filters['verification_level'];
		}

		$order_by = in_array( $filters['order_by'] ?? '', [ 'ranking_score', 'avg_rating', 'review_count', 'created_at' ], true )
			? $filters['order_by']
			: 'ranking_score';

		$limit  = max( 1, min( 100, (int) ( $filters['limit'] ?? 20 ) ) );
		$offset = max( 0, (int) ( $filters['offset'] ?? 0 ) );

		$where_sql = 'WHERE ' . implode( ' AND ', $where );
		$sql       = "SELECT * FROM {$this->table} {$where_sql} ORDER BY {$order_by} DESC LIMIT %d OFFSET %d";

		$values[] = $limit;
		$values[] = $offset;

		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare( $sql, ...$values ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		);

		return array_map( [ $this, 'decode_json_fields' ], $rows ?: [] );
	}

	// -----------------------------------------------------------------------
	// Write
	// -----------------------------------------------------------------------

	/**
	 * Create a new specialist profile.
	 *
	 * @param array<string, mixed> $data
	 * @return int|null  Inserted row ID, or null on failure.
	 */
	public function create( array $data ): ?int {
		$record = $this->prepare_record( $data );

		$result = $this->wpdb->insert( $this->table, $record, $this->formats( $record ) );

		return ( $result !== false ) ? (int) $this->wpdb->insert_id : null;
	}

	/**
	 * Update an existing specialist profile.
	 *
	 * @param int                  $id
	 * @param array<string, mixed> $data
	 * @return bool
	 */
	public function update( int $id, array $data ): bool {
		$record = $this->prepare_record( $data );
		unset( $record['created_at'] );   // never overwrite

		$result = $this->wpdb->update(
			$this->table,
			$record,
			[ 'id' => $id ],
			$this->formats( $record ),
			[ '%d' ]
		);

		return $result !== false;
	}

	/**
	 * Soft-delete a specialist (sets is_active = 0).
	 *
	 * @param int $id
	 * @return bool
	 */
	public function deactivate( int $id ): bool {
		return $this->wpdb->update(
			$this->table,
			[ 'is_active' => 0 ],
			[ 'id' => $id ],
			[ '%d' ],
			[ '%d' ]
		) !== false;
	}

	/**
	 * Update aggregated review stats after a new review is published.
	 *
	 * @param int $specialist_id
	 * @return bool
	 */
	public function refresh_review_stats( int $specialist_id ): bool {
		$reviews_table = $this->wpdb->prefix . 'pearblog_reviews';
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT AVG(rating) as avg_r, COUNT(*) as cnt FROM {$reviews_table}
				 WHERE specialist_id = %d AND is_published = 1",
				$specialist_id
			)
		);

		if ( ! $row ) {
			return false;
		}

		return $this->wpdb->update(
			$this->table,
			[
				'avg_rating'   => round( (float) $row->avg_r, 2 ),
				'review_count' => (int) $row->cnt,
			],
			[ 'id' => $specialist_id ],
			[ '%f', '%d' ],
			[ '%d' ]
		) !== false;
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Sanitise and normalise an input array before DB write.
	 *
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	private function prepare_record( array $data ): array {
		return [
			'wp_post_id'         => (int) ( $data['wp_post_id'] ?? 0 ),
			'name'               => sanitize_text_field( $data['name'] ?? '' ),
			'slug'               => sanitize_title( $data['slug'] ?? $data['name'] ?? '' ),
			'category'           => sanitize_key( $data['category'] ?? '' ),
			'city'               => sanitize_text_field( $data['city'] ?? '' ),
			'region'             => sanitize_text_field( $data['region'] ?? '' ),
			'phone'              => sanitize_text_field( $data['phone'] ?? '' ),
			'email'              => sanitize_email( $data['email'] ?? '' ),
			'website'            => esc_url_raw( $data['website'] ?? '' ),
			'bio'                => sanitize_textarea_field( $data['bio'] ?? '' ),
			'avatar_url'         => esc_url_raw( $data['avatar_url'] ?? '' ),
			'verification_level' => in_array( $data['verification_level'] ?? 'none', [ 'none', 'bronze', 'silver', 'gold' ], true )
				? $data['verification_level']
				: 'none',
			'is_premium'         => (int) ( ! empty( $data['is_premium'] ) ),
			'is_active'          => (int) ( $data['is_active'] ?? 1 ),
			'response_time'      => sanitize_text_field( $data['response_time'] ?? '' ),
			'specialties'        => wp_json_encode( array_map( 'sanitize_text_field', (array) ( $data['specialties'] ?? [] ) ) ),
			'portfolio_urls'     => wp_json_encode( array_map( 'esc_url_raw', (array) ( $data['portfolio_urls'] ?? [] ) ) ),
			'pricing_min'        => isset( $data['pricing_min'] ) ? (int) $data['pricing_min'] : null,
			'pricing_max'        => isset( $data['pricing_max'] ) ? (int) $data['pricing_max'] : null,
		];
	}

	/**
	 * Build a format array for wpdb.
	 *
	 * @param array<string, mixed> $record
	 * @return list<string>
	 */
	private function formats( array $record ): array {
		$int_keys  = [ 'wp_post_id', 'is_premium', 'is_active', 'review_count', 'pricing_min', 'pricing_max' ];
		$float_keys = [ 'avg_rating', 'response_rate', 'ranking_score' ];
		$fmt = [];
		foreach ( array_keys( $record ) as $key ) {
			if ( in_array( $key, $int_keys, true ) ) {
				$fmt[] = '%d';
			} elseif ( in_array( $key, $float_keys, true ) ) {
				$fmt[] = '%f';
			} else {
				$fmt[] = '%s';
			}
		}
		return $fmt;
	}

	/**
	 * Decode JSON-encoded columns to PHP arrays.
	 *
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	private function decode_json_fields( array $row ): array {
		foreach ( [ 'specialties', 'portfolio_urls' ] as $field ) {
			if ( isset( $row[ $field ] ) && is_string( $row[ $field ] ) ) {
				$row[ $field ] = json_decode( $row[ $field ], true ) ?? [];
			}
		}
		return $row;
	}
}

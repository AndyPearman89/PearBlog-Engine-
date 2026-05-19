<?php
/**
 * Comparison Engine — core domain service.
 *
 * Responsibilities:
 * - CRUD for comparisons, items, and attributes.
 * - Pros / cons aggregation.
 * - AI verdict generation via AIClient.
 * - Specialist recommendations linked to a comparison.
 * - Schema.org structured data generation.
 *
 * @package PearBlogEngine\Compare
 */

declare( strict_types=1 );

namespace PearBlogEngine\Compare;

use PearBlogEngine\AI\AIClient;

class CompareEngine {

	private AIClient $ai;

	public function __construct( ?AIClient $ai = null ) {
		$this->ai = $ai ?? new AIClient();
	}

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * Fetch a comparison by slug, including items and attributes.
	 *
	 * @return array<string,mixed>|null
	 */
	public function get_by_slug( string $slug ): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}pb_comparisons WHERE slug = %s AND status = 'publish' LIMIT 1",
				$slug
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		$row['items'] = $this->get_items( (int) $row['id'] );

		// Lazy-build AI verdict if missing.
		if ( empty( $row['ai_verdict'] ) ) {
			$row['ai_verdict'] = $this->generate_ai_verdict( $row );
			$this->save_ai_verdict( (int) $row['id'], $row['ai_verdict'] );
		}

		// Increment view counter (fire-and-forget).
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}pb_comparisons SET view_count = view_count + 1 WHERE id = %d",
				(int) $row['id']
			)
		);

		return $row;
	}

	/**
	 * List comparisons, optionally filtered by category.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function list_comparisons( string $category = '', int $limit = 20, int $offset = 0 ): array {
		global $wpdb;

		$where = "WHERE status = 'publish'";
		$args  = [];

		if ( $category !== '' ) {
			$where  .= ' AND category = %s';
			$args[]  = $category;
		}

		$args[] = $limit;
		$args[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, slug, title, category, ai_verdict, view_count, created_at
				 FROM {$wpdb->prefix}pb_comparisons
				 $where
				 ORDER BY view_count DESC
				 LIMIT %d OFFSET %d",
				...$args
			),
			ARRAY_A
		) ?: [];
	}

	/**
	 * Create or update a comparison (upsert by slug).
	 *
	 * @param array<string,mixed> $data
	 */
	public function upsert( array $data ): int {
		global $wpdb;

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}pb_comparisons WHERE slug = %s LIMIT 1",
				$data['slug']
			)
		);

		$row = [
			'slug'            => sanitize_title( $data['slug'] ),
			'title'           => sanitize_text_field( $data['title'] ),
			'category'        => sanitize_text_field( $data['category'] ?? '' ),
			'seo_description' => sanitize_textarea_field( $data['seo_description'] ?? '' ),
			'status'          => in_array( $data['status'] ?? 'publish', [ 'publish', 'draft' ], true )
								 ? $data['status']
								 : 'publish',
		];

		if ( $existing ) {
			$wpdb->update( "{$wpdb->prefix}pb_comparisons", $row, [ 'id' => (int) $existing ] );
			$id = (int) $existing;
		} else {
			$wpdb->insert( "{$wpdb->prefix}pb_comparisons", $row );
			$id = (int) $wpdb->insert_id;
		}

		// Sync items if provided.
		if ( ! empty( $data['items'] ) && is_array( $data['items'] ) ) {
			$this->sync_items( $id, $data['items'] );
		}

		return $id;
	}

	/**
	 * Return pros and cons grouped by item.
	 *
	 * @return array<string,array{pros:array<string>,cons:array<string>}>
	 */
	public function get_pros_cons( int $comparison_id ): array {
		$items  = $this->get_items( $comparison_id );
		$result = [];

		foreach ( $items as $item ) {
			$pros = [];
			$cons = [];

			foreach ( $item['attrs'] as $attr ) {
				if ( $attr['attr_type'] === 'pro' ) {
					$pros[] = $attr['attr_value'];
				} elseif ( $attr['attr_type'] === 'con' ) {
					$cons[] = $attr['attr_value'];
				}
			}

			$result[ $item['label'] ] = compact( 'pros', 'cons' );
		}

		return $result;
	}

	/**
	 * Build a Schema.org ItemList structured data block for a comparison.
	 *
	 * @param array<string,mixed> $comparison
	 * @return array<string,mixed>
	 */
	public function build_schema( array $comparison ): array {
		$items = [];

		foreach ( $comparison['items'] ?? [] as $idx => $item ) {
			$items[] = [
				'@type'    => 'ListItem',
				'position' => $idx + 1,
				'name'     => $item['label'],
				'description' => $item['ai_verdict_tag'] ?? '',
			];
		}

		return [
			'@context'        => 'https://schema.org',
			'@type'           => 'ItemList',
			'name'            => $comparison['title'],
			'description'     => $comparison['seo_description'] ?? '',
			'numberOfItems'   => count( $items ),
			'itemListElement' => $items,
		];
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private function get_items( int $comparison_id ): array {
		global $wpdb;

		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}pb_comparison_items
				 WHERE comparison_id = %d ORDER BY position ASC",
				$comparison_id
			),
			ARRAY_A
		) ?: [];

		foreach ( $items as &$item ) {
			$item['attrs'] = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}pb_comparison_attrs
					 WHERE item_id = %d ORDER BY position ASC",
					(int) $item['id']
				),
				ARRAY_A
			) ?: [];
		}
		unset( $item );

		return $items;
	}

	/**
	 * @param array<int,array<string,mixed>> $items
	 */
	private function sync_items( int $comparison_id, array $items ): void {
		global $wpdb;

		// Delete existing items and attrs (cascade via PHP since InnoDB constraints may vary).
		$old_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}pb_comparison_items WHERE comparison_id = %d",
				$comparison_id
			)
		);

		if ( $old_ids ) {
			$placeholders = implode( ',', array_fill( 0, count( $old_ids ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}pb_comparison_attrs WHERE item_id IN ($placeholders)",
					...$old_ids
				)
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}pb_comparison_items WHERE id IN ($placeholders)",
					...$old_ids
				)
			);
		}

		foreach ( $items as $pos => $item ) {
			$wpdb->insert(
				"{$wpdb->prefix}pb_comparison_items",
				[
					'comparison_id'  => $comparison_id,
					'position'       => $pos,
					'label'          => sanitize_text_field( $item['label'] ),
					'image_url'      => esc_url_raw( $item['image_url'] ?? '' ),
					'overall_score'  => isset( $item['overall_score'] ) ? (int) $item['overall_score'] : null,
					'ai_verdict_tag' => sanitize_text_field( $item['ai_verdict_tag'] ?? '' ),
					'meta_json'      => wp_json_encode( $item['meta'] ?? [] ),
				]
			);

			$item_id = (int) $wpdb->insert_id;

			if ( ! empty( $item['attrs'] ) && is_array( $item['attrs'] ) ) {
				foreach ( $item['attrs'] as $apos => $attr ) {
					$wpdb->insert(
						"{$wpdb->prefix}pb_comparison_attrs",
						[
							'item_id'    => $item_id,
							'attr_key'   => sanitize_key( $attr['key'] ),
							'attr_label' => sanitize_text_field( $attr['label'] ?? '' ),
							'attr_value' => sanitize_textarea_field( $attr['value'] ?? '' ),
							'attr_type'  => in_array( $attr['type'] ?? 'text', [ 'text', 'number', 'bool', 'pro', 'con', 'rating' ], true )
								? $attr['type']
								: 'text',
							'position'   => $apos,
						]
					);
				}
			}
		}
	}

	/**
	 * Ask the AI to produce a short comparison verdict.
	 *
	 * @param array<string,mixed> $comparison
	 */
	private function generate_ai_verdict( array $comparison ): string {
		$prompt = sprintf(
			'Napisz krótkie (2-3 zdania) podsumowanie porównania: "%s". ' .
			'Wskaż który wariant jest lepszy dla typowego użytkownika i dlaczego. ' .
			'Odpowiedz po polsku.',
			$comparison['title']
		);

		try {
			return $this->ai->generate( $prompt, [ 'max_tokens' => 200 ] );
		} catch ( \Throwable ) {
			return '';
		}
	}

	private function save_ai_verdict( int $id, string $verdict ): void {
		global $wpdb;
		$wpdb->update(
			"{$wpdb->prefix}pb_comparisons",
			[ 'ai_verdict' => $verdict ],
			[ 'id' => $id ]
		);
	}
}

<?php
/**
 * Ranking Model - TOP lists for products, services, providers
 *
 * @package PearBlogEngine\DecisionPlatform
 */

declare(strict_types=1);

namespace PearBlogEngine\DecisionPlatform;

/**
 * Ranking model with items and scoring
 */
class Ranking {

	/** @var int */
	public int $id;

	/** @var string */
	public string $title;

	/** @var string */
	public string $slug;

	/** @var string Category name */
	public string $category;

	/** @var array{city: string, region: string} */
	public array $location;

	/** @var array<array{name: string, score: float, data: array<string, mixed>, premium: bool}> */
	public array $items;

	/** @var array<string> */
	public array $criteria;

	/** @var string ISO 8601 timestamp */
	public string $created_at;

	/** @var string ISO 8601 timestamp */
	public string $updated_at;

	/**
	 * Create from custom post type
	 *
	 * @param \WP_Post $post
	 * @return self
	 */
	public static function from_post( \WP_Post $post ): self {
		$ranking = new self();
		$ranking->id = $post->ID;
		$ranking->title = $post->post_title;
		$ranking->slug = $post->post_name;

		$ranking->category = get_post_meta( $post->ID, 'pearblog_ranking_category', true ) ?: '';

		$location_data = get_post_meta( $post->ID, 'pearblog_ranking_location', true );
		$ranking->location = $location_data ? json_decode( $location_data, true ) : [];

		$items_data = get_post_meta( $post->ID, 'pearblog_ranking_items', true );
		$ranking->items = $items_data ? json_decode( $items_data, true ) : [];

		$criteria_data = get_post_meta( $post->ID, 'pearblog_ranking_criteria', true );
		$ranking->criteria = $criteria_data ? json_decode( $criteria_data, true ) : [];

		$ranking->created_at = $post->post_date;
		$ranking->updated_at = $post->post_modified;

		return $ranking;
	}

	/**
	 * Save to WordPress
	 *
	 * @return int Post ID
	 */
	public function save(): int {
		if ( $this->id > 0 ) {
			wp_update_post( [
				'ID' => $this->id,
				'post_title' => $this->title,
				'post_name' => $this->slug,
			] );
		} else {
			$this->id = wp_insert_post( [
				'post_title' => $this->title,
				'post_name' => $this->slug,
				'post_status' => 'publish',
				'post_type' => 'pearblog_ranking',
			] );
		}

		update_post_meta( $this->id, 'pearblog_ranking_category', $this->category );
		update_post_meta( $this->id, 'pearblog_ranking_location', wp_json_encode( $this->location ) );
		update_post_meta( $this->id, 'pearblog_ranking_items', wp_json_encode( $this->items ) );
		update_post_meta( $this->id, 'pearblog_ranking_criteria', wp_json_encode( $this->criteria ) );

		return $this->id;
	}

	/**
	 * Sort items by score
	 */
	public function sort_by_score(): void {
		usort( $this->items, function( $a, $b ) {
			return $b['score'] <=> $a['score'];
		} );
	}

	/**
	 * Filter premium items
	 *
	 * @return array<array{name: string, score: float, data: array<string, mixed>, premium: bool}>
	 */
	public function get_premium_items(): array {
		return array_filter( $this->items, function( $item ) {
			return $item['premium'] ?? false;
		} );
	}

	/**
	 * Render ranking list
	 *
	 * @param int $limit Number of items to show
	 * @return string HTML
	 */
	public function render_list( int $limit = 10 ): string {
		$this->sort_by_score();
		$items = array_slice( $this->items, 0, $limit );

		$html = '<div class="ranking-list">';
		$html .= '<ol class="ranking-items">';

		foreach ( $items as $index => $item ) {
			$premium_class = ( $item['premium'] ?? false ) ? ' premium' : '';
			$html .= sprintf(
				'<li class="ranking-item%s">',
				$premium_class
			);

			$html .= '<div class="ranking-position">' . ( $index + 1 ) . '</div>';
			$html .= '<div class="ranking-content">';
			$html .= '<h3>' . esc_html( $item['name'] ) . '</h3>';

			if ( isset( $item['data']['description'] ) ) {
				$html .= '<p>' . esc_html( $item['data']['description'] ) . '</p>';
			}

			$html .= '<div class="ranking-score">Ocena: ' . number_format( $item['score'], 1 ) . '/10</div>';

			if ( isset( $item['data']['cta_url'] ) ) {
				$html .= '<a href="' . esc_url( $item['data']['cta_url'] ) . '" class="btn btn-primary">Zobacz ofertę</a>';
			}

			$html .= '</div>';
			$html .= '</li>';
		}

		$html .= '</ol>';
		$html .= '</div>';

		return $html;
	}
}

<?php
/**
 * Comparison Model - Compare products, services, solutions
 *
 * @package PearBlogEngine\DecisionPlatform
 */

declare(strict_types=1);

namespace PearBlogEngine\DecisionPlatform;

/**
 * Comparison model with items and criteria
 */
class Comparison {

	/** @var int */
	public int $id;

	/** @var string */
	public string $title;

	/** @var string */
	public string $slug;

	/** @var array<array{name: string, data: array<string, mixed>}> */
	public array $items;

	/** @var array<array{name: string, weight: float, type: string}> */
	public array $criteria;

	/** @var int|null Winner item index */
	public ?int $winner;

	/** @var array<string, mixed> Content blocks */
	public array $blocks;

	/** @var array{city: string, region: string}|null */
	public ?array $geo;

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
		$comparison = new self();
		$comparison->id = $post->ID;
		$comparison->title = $post->post_title;
		$comparison->slug = $post->post_name;

		$items_data = get_post_meta( $post->ID, 'pearblog_comparison_items', true );
		$comparison->items = $items_data ? json_decode( $items_data, true ) : [];

		$criteria_data = get_post_meta( $post->ID, 'pearblog_comparison_criteria', true );
		$comparison->criteria = $criteria_data ? json_decode( $criteria_data, true ) : [];

		$comparison->winner = (int) get_post_meta( $post->ID, 'pearblog_comparison_winner', true ) ?: null;

		$blocks_data = get_post_meta( $post->ID, 'pearblog_comparison_blocks', true );
		$comparison->blocks = $blocks_data ? json_decode( $blocks_data, true ) : [];

		$geo_data = get_post_meta( $post->ID, 'pearblog_geo_data', true );
		$comparison->geo = $geo_data ? json_decode( $geo_data, true ) : null;

		$comparison->created_at = $post->post_date;
		$comparison->updated_at = $post->post_modified;

		return $comparison;
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
				'post_type' => 'pearblog_comparison',
			] );
		}

		update_post_meta( $this->id, 'pearblog_comparison_items', wp_json_encode( $this->items ) );
		update_post_meta( $this->id, 'pearblog_comparison_criteria', wp_json_encode( $this->criteria ) );
		update_post_meta( $this->id, 'pearblog_comparison_winner', $this->winner );
		update_post_meta( $this->id, 'pearblog_comparison_blocks', wp_json_encode( $this->blocks ) );
		update_post_meta( $this->id, 'pearblog_geo_data', wp_json_encode( $this->geo ) );

		return $this->id;
	}

	/**
	 * Calculate scores for each item
	 *
	 * @return array<int, float> Item index => total score
	 */
	public function calculate_scores(): array {
		$scores = [];

		foreach ( $this->items as $index => $item ) {
			$total = 0.0;

			foreach ( $this->criteria as $criterion ) {
				$value = $item['data'][ $criterion['name'] ] ?? 0;
				$total += $value * $criterion['weight'];
			}

			$scores[ $index ] = $total;
		}

		return $scores;
	}

	/**
	 * Determine winner based on scores
	 *
	 * @return int Winner index
	 */
	public function determine_winner(): int {
		$scores = $this->calculate_scores();
		arsort( $scores );
		return (int) array_key_first( $scores );
	}

	/**
	 * Render comparison table
	 *
	 * @return string HTML
	 */
	public function render_table(): string {
		$html = '<div class="comparison-table">';
		$html .= '<table class="table table-striped">';

		// Header
		$html .= '<thead><tr><th>Kryterium</th>';
		foreach ( $this->items as $item ) {
			$html .= '<th>' . esc_html( $item['name'] ) . '</th>';
		}
		$html .= '</tr></thead>';

		// Body
		$html .= '<tbody>';
		foreach ( $this->criteria as $criterion ) {
			$html .= '<tr>';
			$html .= '<td><strong>' . esc_html( $criterion['name'] ) . '</strong></td>';

			foreach ( $this->items as $item ) {
				$value = $item['data'][ $criterion['name'] ] ?? '-';
				$html .= '<td>' . esc_html( $value ) . '</td>';
			}

			$html .= '</tr>';
		}
		$html .= '</tbody>';

		$html .= '</table>';
		$html .= '</div>';

		return $html;
	}
}

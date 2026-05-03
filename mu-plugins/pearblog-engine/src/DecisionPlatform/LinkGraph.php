<?php
/**
 * Internal Link Graph - Builds relationships between content
 *
 * @package PearBlogEngine\DecisionPlatform
 */

declare(strict_types=1);

namespace PearBlogEngine\DecisionPlatform;

/**
 * Internal Link Graph
 */
class LinkGraph {

	/**
	 * Build link graph for post
	 *
	 * @param int $post_id
	 */
	public function build_for_post( int $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		$links = $this->discover_links( $post );

		// Save links
		update_post_meta( $post_id, 'pearblog_internal_links', wp_json_encode( $links ) );

		// Update link count
		update_post_meta( $post_id, 'pearblog_link_count', count( $links ) );
	}

	/**
	 * Discover related content for linking
	 *
	 * @param \WP_Post $post
	 * @return array<array{type: string, id: int, title: string, url: string, relevance: float}>
	 */
	private function discover_links( \WP_Post $post ): array {
		$links = [];

		// Get keywords
		$keywords = get_post_meta( $post->ID, 'pearblog_keywords', true );
		if ( ! $keywords ) {
			return [];
		}

		$keyword_array = explode( ',', $keywords );

		// Find related articles
		$related_articles = $this->find_related_posts( $keyword_array, 'post', 5 );
		foreach ( $related_articles as $related ) {
			$links[] = [
				'type' => 'article',
				'id' => $related->ID,
				'title' => $related->post_title,
				'url' => get_permalink( $related ),
				'relevance' => 0.8,
			];
		}

		// Find related comparisons
		$related_comparisons = $this->find_related_posts( $keyword_array, 'pearblog_comparison', 3 );
		foreach ( $related_comparisons as $related ) {
			$links[] = [
				'type' => 'comparison',
				'id' => $related->ID,
				'title' => $related->post_title,
				'url' => get_permalink( $related ),
				'relevance' => 0.9,
			];
		}

		// Find related rankings
		$geo = get_post_meta( $post->ID, 'pearblog_geo_data', true );
		if ( $geo ) {
			$geo_data = json_decode( $geo, true );
			if ( $geo_data && isset( $geo_data['city'] ) ) {
				$related_rankings = $this->find_local_rankings( $geo_data['city'], 3 );
				foreach ( $related_rankings as $related ) {
					$links[] = [
						'type' => 'ranking',
						'id' => $related->ID,
						'title' => $related->post_title,
						'url' => get_permalink( $related ),
						'relevance' => 0.85,
					];
				}
			}
		}

		// Find related calculators
		$related_calculators = $this->find_related_posts( $keyword_array, 'pearblog_calculator', 2 );
		foreach ( $related_calculators as $related ) {
			$links[] = [
				'type' => 'calculator',
				'id' => $related->ID,
				'title' => $related->post_title,
				'url' => get_permalink( $related ),
				'relevance' => 0.9,
			];
		}

		// Sort by relevance
		usort( $links, function( $a, $b ) {
			return $b['relevance'] <=> $a['relevance'];
		} );

		return $links;
	}

	/**
	 * Find related posts by keywords
	 *
	 * @param array<string> $keywords
	 * @param string $post_type
	 * @param int $limit
	 * @return array<\WP_Post>
	 */
	private function find_related_posts( array $keywords, string $post_type, int $limit ): array {
		if ( empty( $keywords ) ) {
			return [];
		}

		// Build search query
		$search_terms = implode( ' ', array_slice( $keywords, 0, 3 ) );

		$posts = get_posts( [
			'post_type' => $post_type,
			'post_status' => 'publish',
			's' => $search_terms,
			'posts_per_page' => $limit,
		] );

		return $posts;
	}

	/**
	 * Find local rankings
	 *
	 * @param string $city
	 * @param int $limit
	 * @return array<\WP_Post>
	 */
	private function find_local_rankings( string $city, int $limit ): array {
		$posts = get_posts( [
			'post_type' => 'pearblog_ranking',
			'post_status' => 'publish',
			'posts_per_page' => $limit,
			'meta_query' => [
				[
					'key' => 'pearblog_ranking_location',
					'value' => $city,
					'compare' => 'LIKE',
				],
			],
		] );

		return $posts;
	}

	/**
	 * Inject links into post content
	 *
	 * @param int $post_id
	 * @param string $content
	 * @return string Modified content
	 */
	public function inject_links( int $post_id, string $content ): string {
		$links_json = get_post_meta( $post_id, 'pearblog_internal_links', true );
		if ( ! $links_json ) {
			return $content;
		}

		$links = json_decode( $links_json, true );
		if ( ! is_array( $links ) || empty( $links ) ) {
			return $content;
		}

		// Add related links section at the end
		$related_html = '<div class="related-links">';
		$related_html .= '<h3>Powiązane treści</h3>';
		$related_html .= '<ul>';

		foreach ( array_slice( $links, 0, 5 ) as $link ) {
			$icon = $this->get_link_icon( $link['type'] );
			$related_html .= sprintf(
				'<li>%s <a href="%s">%s</a></li>',
				$icon,
				esc_url( $link['url'] ),
				esc_html( $link['title'] )
			);
		}

		$related_html .= '</ul>';
		$related_html .= '</div>';

		return $content . $related_html;
	}

	/**
	 * Get icon for link type
	 *
	 * @param string $type
	 * @return string
	 */
	private function get_link_icon( string $type ): string {
		$icons = [
			'article' => '📄',
			'comparison' => '⚖️',
			'ranking' => '🏆',
			'calculator' => '🧮',
			'expert' => '👤',
			'offer' => '💼',
		];

		return $icons[ $type ] ?? '🔗';
	}

	/**
	 * Rebuild entire link graph
	 */
	public function rebuild_all(): void {
		$posts = get_posts( [
			'post_type' => [ 'post', 'pearblog_comparison', 'pearblog_ranking' ],
			'post_status' => 'publish',
			'posts_per_page' => -1,
		] );

		foreach ( $posts as $post ) {
			$this->build_for_post( $post->ID );
		}
	}
}

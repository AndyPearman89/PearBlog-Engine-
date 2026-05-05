<?php
/**
 * Related Entity Manager
 *
 * Manages relationships between articles and external entities (listings, services).
 * Used for cross-linking articles with PearTree listings, Q&A, and other platforms.
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

/**
 * Manages related entity connections for posts.
 */
class RelatedEntityManager {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'save_meta' ], 10, 2 );
	}

	/**
	 * Add meta boxes for related entities.
	 */
	public function add_meta_boxes(): void {
		add_meta_box(
			'pb_related_entities',
			__( 'Related Entities', 'pearblog-engine' ),
			[ $this, 'render_meta_box' ],
			'post',
			'side',
			'default'
		);
	}

	/**
	 * Render related entities meta box.
	 *
	 * @param \WP_Post $post Current post object.
	 */
	public function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'pb_related_entities_meta', 'pb_related_entities_nonce' );

		$entities = $this->get_related_entities( $post->ID );

		?>
		<div id="pb-related-entities-list">
			<?php foreach ( $entities as $index => $entity ): ?>
				<div class="pb-related-entity-item" style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
					<div style="margin-bottom: 8px;">
						<label style="font-weight: 600;">
							<?php esc_html_e( 'Listing ID', 'pearblog-engine' ); ?>
						</label>
						<input type="text" name="pb_related_entities[<?php echo esc_attr( $index ); ?>][listing_id]" value="<?php echo esc_attr( $entity['listing_id'] ?? '' ); ?>" class="widefat" placeholder="e.g., listing-123" />
					</div>
					<div style="margin-bottom: 8px;">
						<label style="font-weight: 600;">
							<?php esc_html_e( 'Service', 'pearblog-engine' ); ?>
						</label>
						<input type="text" name="pb_related_entities[<?php echo esc_attr( $index ); ?>][service]" value="<?php echo esc_attr( $entity['service'] ?? '' ); ?>" class="widefat" placeholder="e.g., mechanik" />
					</div>
					<div style="margin-bottom: 8px;">
						<label style="font-weight: 600;">
							<?php esc_html_e( 'City', 'pearblog-engine' ); ?>
						</label>
						<input type="text" name="pb_related_entities[<?php echo esc_attr( $index ); ?>][city]" value="<?php echo esc_attr( $entity['city'] ?? '' ); ?>" class="widefat" placeholder="e.g., Kraków" />
					</div>
					<button type="button" class="button pb-remove-entity" onclick="this.parentElement.remove();"><?php esc_html_e( 'Remove', 'pearblog-engine' ); ?></button>
				</div>
			<?php endforeach; ?>
		</div>
		<p>
			<button type="button" class="button" id="pb-add-related-entity">
				<?php esc_html_e( 'Add Related Entity', 'pearblog-engine' ); ?>
			</button>
		</p>
		<script>
		jQuery(document).ready(function($) {
			$('#pb-add-related-entity').on('click', function() {
				var index = $('#pb-related-entities-list .pb-related-entity-item').length;
				var html = '<div class="pb-related-entity-item" style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">' +
					'<div style="margin-bottom: 8px;"><label style="font-weight: 600;"><?php esc_html_e( 'Listing ID', 'pearblog-engine' ); ?></label>' +
					'<input type="text" name="pb_related_entities[' + index + '][listing_id]" value="" class="widefat" placeholder="e.g., listing-123" /></div>' +
					'<div style="margin-bottom: 8px;"><label style="font-weight: 600;"><?php esc_html_e( 'Service', 'pearblog-engine' ); ?></label>' +
					'<input type="text" name="pb_related_entities[' + index + '][service]" value="" class="widefat" placeholder="e.g., mechanik" /></div>' +
					'<div style="margin-bottom: 8px;"><label style="font-weight: 600;"><?php esc_html_e( 'City', 'pearblog-engine' ); ?></label>' +
					'<input type="text" name="pb_related_entities[' + index + '][city]" value="" class="widefat" placeholder="e.g., Kraków" /></div>' +
					'<button type="button" class="button pb-remove-entity" onclick="this.parentElement.remove();"><?php esc_html_e( 'Remove', 'pearblog-engine' ); ?></button>' +
					'</div>';
				$('#pb-related-entities-list').append(html);
			});
		});
		</script>
		<?php
	}

	/**
	 * Save related entities meta.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function save_meta( int $post_id, \WP_Post $post ): void {
		// Verify nonce.
		if ( ! isset( $_POST['pb_related_entities_nonce'] ) || ! wp_verify_nonce( $_POST['pb_related_entities_nonce'], 'pb_related_entities_meta' ) ) {
			return;
		}

		// Check autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check post type.
		if ( 'post' !== $post->post_type ) {
			return;
		}

		// Process entities.
		$entities = [];

		if ( isset( $_POST['pb_related_entities'] ) && is_array( $_POST['pb_related_entities'] ) ) {
			foreach ( $_POST['pb_related_entities'] as $entity ) {
				if ( ! empty( $entity['listing_id'] ) || ! empty( $entity['service'] ) || ! empty( $entity['city'] ) ) {
					$entities[] = [
						'listing_id' => sanitize_text_field( $entity['listing_id'] ?? '' ),
						'service'    => sanitize_text_field( $entity['service'] ?? '' ),
						'city'       => sanitize_text_field( $entity['city'] ?? '' ),
					];
				}
			}
		}

		update_post_meta( $post_id, '_pb_related_entities', $entities );
	}

	/**
	 * Get related entities for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array Array of related entities.
	 */
	public function get_related_entities( int $post_id ): array {
		$entities = get_post_meta( $post_id, '_pb_related_entities', true );

		if ( ! is_array( $entities ) || empty( $entities ) ) {
			return [
				[ 'listing_id' => '', 'service' => '', 'city' => '' ],
			];
		}

		return $entities;
	}

	/**
	 * Find related articles for a given entity.
	 *
	 * @param string $listing_id Listing ID.
	 * @param string $service    Service type.
	 * @param string $city       City name.
	 * @return array Array of post IDs.
	 */
	public static function find_related_articles( string $listing_id = '', string $service = '', string $city = '' ): array {
		$args = [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'fields'         => 'ids',
			'meta_query'     => [ 'relation' => 'OR' ],
		];

		// Build meta query based on provided parameters.
		if ( ! empty( $listing_id ) ) {
			$args['meta_query'][] = [
				'key'     => '_pb_related_entities',
				'value'   => $listing_id,
				'compare' => 'LIKE',
			];
		}

		if ( ! empty( $service ) ) {
			$args['meta_query'][] = [
				'key'     => '_pb_related_entities',
				'value'   => $service,
				'compare' => 'LIKE',
			];
		}

		if ( ! empty( $city ) ) {
			$args['meta_query'][] = [
				'key'     => '_pb_related_entities',
				'value'   => $city,
				'compare' => 'LIKE',
			];
		}

		// If no criteria provided, return empty.
		if ( count( $args['meta_query'] ) === 1 ) {
			return [];
		}

		$query = new \WP_Query( $args );

		return $query->posts;
	}

	/**
	 * Generate HTML links to related articles.
	 *
	 * @param int $post_id Post ID.
	 * @param int $limit   Maximum number of links to generate.
	 * @return string HTML output of related article links.
	 */
	public static function generate_related_links( int $post_id, int $limit = 5 ): string {
		$entities = get_post_meta( $post_id, '_pb_related_entities', true );

		if ( ! is_array( $entities ) || empty( $entities ) ) {
			return '';
		}

		$related_posts = [];

		foreach ( $entities as $entity ) {
			$found = self::find_related_articles(
				$entity['listing_id'] ?? '',
				$entity['service'] ?? '',
				$entity['city'] ?? ''
			);

			foreach ( $found as $found_id ) {
				if ( $found_id !== $post_id ) {
					$related_posts[ $found_id ] = true;
				}
			}

			if ( count( $related_posts ) >= $limit ) {
				break;
			}
		}

		if ( empty( $related_posts ) ) {
			return '';
		}

		$related_ids = array_slice( array_keys( $related_posts ), 0, $limit );

		ob_start();
		?>
		<div class="pb-related-entities-links">
			<h3><?php esc_html_e( 'Related Articles', 'pearblog-engine' ); ?></h3>
			<ul>
				<?php foreach ( $related_ids as $related_id ): ?>
					<li>
						<a href="<?php echo esc_url( get_permalink( $related_id ) ); ?>">
							<?php echo esc_html( get_the_title( $related_id ) ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
		return ob_get_clean();
	}
}

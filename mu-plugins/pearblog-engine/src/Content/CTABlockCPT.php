<?php
/**
 * CTA Block Custom Post Type
 *
 * Stores reusable Call-to-Action blocks with type and placement configuration.
 * Supports lead generation, affiliate, and SaaS CTA types.
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

/**
 * Manages CTA Block CPT.
 */
class CTABlockCPT {

	public const POST_TYPE = 'pb_cta_block';
	public const TAXONOMY_TYPE = 'pb_cta_type';

	/**
	 * Register the CPT, taxonomy, and hooks.
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'init', [ $this, 'register_taxonomy' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_meta' ], 10, 2 );
		add_shortcode( 'cta', [ $this, 'shortcode' ] );
	}

	/**
	 * Register pb_cta_block custom post type.
	 */
	public function register_post_type(): void {
		$labels = [
			'name'                  => __( 'CTA Blocks', 'pearblog-engine' ),
			'singular_name'         => __( 'CTA Block', 'pearblog-engine' ),
			'menu_name'             => __( 'CTA Blocks', 'pearblog-engine' ),
			'add_new'               => __( 'Add New', 'pearblog-engine' ),
			'add_new_item'          => __( 'Add New CTA Block', 'pearblog-engine' ),
			'edit_item'             => __( 'Edit CTA Block', 'pearblog-engine' ),
			'view_item'             => __( 'View CTA Block', 'pearblog-engine' ),
			'all_items'             => __( 'All CTA Blocks', 'pearblog-engine' ),
		];

		$args = [
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => 'pearblog-engine',
			'query_var'           => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_icon'           => 'dashicons-megaphone',
			'show_in_rest'        => true,
			'supports'            => [ 'title', 'editor', 'revisions' ],
		];

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register pb_cta_type taxonomy for CTA type classification.
	 */
	public function register_taxonomy(): void {
		$labels = [
			'name'              => __( 'CTA Types', 'pearblog-engine' ),
			'singular_name'     => __( 'CTA Type', 'pearblog-engine' ),
			'menu_name'         => __( 'CTA Types', 'pearblog-engine' ),
		];

		$args = [
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => false,
			'show_in_rest'      => true,
			'rewrite'           => false,
		];

		register_taxonomy( self::TAXONOMY_TYPE, [ self::POST_TYPE ], $args );

		// Pre-populate default CTA types.
		$this->ensure_default_terms();
	}

	/**
	 * Ensure default CTA type terms exist.
	 */
	private function ensure_default_terms(): void {
		$default_types = [ 'lead', 'affiliate', 'saas' ];

		foreach ( $default_types as $slug ) {
			if ( ! term_exists( $slug, self::TAXONOMY_TYPE ) ) {
				wp_insert_term( ucfirst( $slug ), self::TAXONOMY_TYPE, [
					'slug' => $slug,
				] );
			}
		}
	}

	/**
	 * Add meta boxes for CTA fields.
	 */
	public function add_meta_boxes(): void {
		add_meta_box(
			'pb_cta_settings',
			__( 'CTA Settings', 'pearblog-engine' ),
			[ $this, 'render_meta_box' ],
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render CTA settings meta box.
	 *
	 * @param \WP_Post $post Current post object.
	 */
	public function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'pb_cta_meta', 'pb_cta_meta_nonce' );

		$label     = get_post_meta( $post->ID, '_pb_cta_label', true );
		$target_url = get_post_meta( $post->ID, '_pb_cta_target_url', true );
		$placement = get_post_meta( $post->ID, '_pb_cta_placement', true ) ?: 'inline';

		?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="pb_cta_label"><?php esc_html_e( 'Button Label', 'pearblog-engine' ); ?></label>
				</th>
				<td>
					<input type="text" id="pb_cta_label" name="pb_cta_label" value="<?php echo esc_attr( $label ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Text displayed on the CTA button.', 'pearblog-engine' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="pb_cta_target_url"><?php esc_html_e( 'Target URL', 'pearblog-engine' ); ?></label>
				</th>
				<td>
					<input type="url" id="pb_cta_target_url" name="pb_cta_target_url" value="<?php echo esc_attr( $target_url ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'URL where the CTA button links to.', 'pearblog-engine' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="pb_cta_placement"><?php esc_html_e( 'Placement', 'pearblog-engine' ); ?></label>
				</th>
				<td>
					<select id="pb_cta_placement" name="pb_cta_placement">
						<option value="inline" <?php selected( $placement, 'inline' ); ?>><?php esc_html_e( 'Inline (within content)', 'pearblog-engine' ); ?></option>
						<option value="footer" <?php selected( $placement, 'footer' ); ?>><?php esc_html_e( 'Footer (end of article)', 'pearblog-engine' ); ?></option>
						<option value="sticky" <?php selected( $placement, 'sticky' ); ?>><?php esc_html_e( 'Sticky (fixed position)', 'pearblog-engine' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Where this CTA should be displayed.', 'pearblog-engine' ); ?></p>
				</td>
			</tr>
		</table>
		<hr />
		<p>
			<strong><?php esc_html_e( 'Usage:', 'pearblog-engine' ); ?></strong><br />
			<code>[cta id="<?php echo esc_attr( $post->ID ); ?>"]</code>
		</p>
		<?php
	}

	/**
	 * Save CTA meta data.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function save_meta( int $post_id, \WP_Post $post ): void {
		// Verify nonce.
		if ( ! isset( $_POST['pb_cta_meta_nonce'] ) || ! wp_verify_nonce( $_POST['pb_cta_meta_nonce'], 'pb_cta_meta' ) ) {
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

		// Save label.
		if ( isset( $_POST['pb_cta_label'] ) ) {
			update_post_meta( $post_id, '_pb_cta_label', sanitize_text_field( $_POST['pb_cta_label'] ) );
		}

		// Save target URL.
		if ( isset( $_POST['pb_cta_target_url'] ) ) {
			update_post_meta( $post_id, '_pb_cta_target_url', esc_url_raw( $_POST['pb_cta_target_url'] ) );
		}

		// Save placement.
		if ( isset( $_POST['pb_cta_placement'] ) ) {
			$placement = sanitize_text_field( $_POST['pb_cta_placement'] );
			if ( in_array( $placement, [ 'inline', 'footer', 'sticky' ], true ) ) {
				update_post_meta( $post_id, '_pb_cta_placement', $placement );
			}
		}
	}

	/**
	 * CTA shortcode handler.
	 *
	 * Usage: [cta id="123"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string CTA HTML output.
	 */
	public function shortcode( $atts ): string {
		$atts = shortcode_atts( [
			'id' => 0,
		], $atts, 'cta' );

		$cta_id = absint( $atts['id'] );

		if ( ! $cta_id ) {
			return '';
		}

		$cta_post = get_post( $cta_id );

		if ( ! $cta_post || $cta_post->post_type !== self::POST_TYPE ) {
			return '';
		}

		$label      = get_post_meta( $cta_id, '_pb_cta_label', true ) ?: $cta_post->post_title;
		$target_url = get_post_meta( $cta_id, '_pb_cta_target_url', true );
		$placement  = get_post_meta( $cta_id, '_pb_cta_placement', true ) ?: 'inline';
		$content    = $cta_post->post_content;

		$terms = wp_get_post_terms( $cta_id, self::TAXONOMY_TYPE, [ 'fields' => 'slugs' ] );
		$cta_type = ! empty( $terms ) && ! is_wp_error( $terms ) ? $terms[0] : 'lead';

		ob_start();
		?>
		<div class="pb-cta-block pb-cta-<?php echo esc_attr( $placement ); ?> pb-cta-type-<?php echo esc_attr( $cta_type ); ?>">
			<div class="pb-cta-content">
				<?php if ( ! empty( $content ) ): ?>
					<?php echo wp_kses_post( wpautop( $content ) ); ?>
				<?php endif; ?>
			</div>
			<?php if ( ! empty( $target_url ) ): ?>
				<div class="pb-cta-action">
					<a href="<?php echo esc_url( $target_url ); ?>" class="pb-cta-button" rel="nofollow">
						<?php echo esc_html( $label ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get CTA data for a post.
	 *
	 * @param int $post_id CTA post ID.
	 * @return array|null CTA data or null.
	 */
	public static function get_cta_data( int $post_id ): ?array {
		$post = get_post( $post_id );

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return null;
		}

		$terms = wp_get_post_terms( $post_id, self::TAXONOMY_TYPE, [ 'fields' => 'slugs' ] );

		return [
			'id'         => $post_id,
			'type'       => ! empty( $terms ) && ! is_wp_error( $terms ) ? $terms[0] : 'lead',
			'label'      => get_post_meta( $post_id, '_pb_cta_label', true ) ?: $post->post_title,
			'target_url' => get_post_meta( $post_id, '_pb_cta_target_url', true ),
			'placement'  => get_post_meta( $post_id, '_pb_cta_placement', true ) ?: 'inline',
			'content'    => $post->post_content,
		];
	}
}

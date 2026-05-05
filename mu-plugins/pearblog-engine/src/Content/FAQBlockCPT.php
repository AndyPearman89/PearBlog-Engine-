<?php
/**
 * FAQ Block Custom Post Type
 *
 * Stores reusable FAQ question/answer pairs with schema.org integration.
 * Can be embedded in articles via shortcodes or blocks.
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

/**
 * Manages FAQ Block CPT and schema generation.
 */
class FAQBlockCPT {

	public const POST_TYPE = 'pb_faq_block';

	/**
	 * Register the CPT and hooks.
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_meta' ], 10, 2 );
		add_shortcode( 'faq', [ $this, 'shortcode' ] );
	}

	/**
	 * Register pb_faq_block custom post type.
	 */
	public function register_post_type(): void {
		$labels = [
			'name'                  => __( 'FAQ Blocks', 'pearblog-engine' ),
			'singular_name'         => __( 'FAQ Block', 'pearblog-engine' ),
			'menu_name'             => __( 'FAQ Blocks', 'pearblog-engine' ),
			'add_new'               => __( 'Add New', 'pearblog-engine' ),
			'add_new_item'          => __( 'Add New FAQ Block', 'pearblog-engine' ),
			'edit_item'             => __( 'Edit FAQ Block', 'pearblog-engine' ),
			'view_item'             => __( 'View FAQ Block', 'pearblog-engine' ),
			'all_items'             => __( 'All FAQ Blocks', 'pearblog-engine' ),
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
			'menu_icon'           => 'dashicons-editor-help',
			'show_in_rest'        => true,
			'supports'            => [ 'title', 'editor', 'revisions' ],
		];

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Add meta boxes.
	 */
	public function add_meta_boxes(): void {
		add_meta_box(
			'pb_faq_settings',
			__( 'FAQ Settings', 'pearblog-engine' ),
			[ $this, 'render_meta_box' ],
			self::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Render FAQ settings meta box.
	 *
	 * @param \WP_Post $post Current post object.
	 */
	public function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'pb_faq_meta', 'pb_faq_meta_nonce' );

		$question        = get_post_meta( $post->ID, '_pb_faq_question', true );
		$answer          = get_post_meta( $post->ID, '_pb_faq_answer', true );
		$schema_enabled  = get_post_meta( $post->ID, '_pb_faq_schema_enabled', true );

		if ( '' === $schema_enabled ) {
			$schema_enabled = '1'; // Default: enabled
		}

		?>
		<p>
			<label>
				<input type="checkbox" name="pb_faq_schema_enabled" value="1" <?php checked( $schema_enabled, '1' ); ?> />
				<?php esc_html_e( 'Enable Schema.org FAQPage markup', 'pearblog-engine' ); ?>
			</label>
		</p>
		<p class="description">
			<?php esc_html_e( 'When enabled, this FAQ will be included in FAQPage structured data for SEO.', 'pearblog-engine' ); ?>
		</p>
		<hr />
		<p>
			<strong><?php esc_html_e( 'Usage:', 'pearblog-engine' ); ?></strong><br />
			<code>[faq id="<?php echo esc_attr( $post->ID ); ?>"]</code>
		</p>
		<?php
	}

	/**
	 * Save FAQ meta data.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function save_meta( int $post_id, \WP_Post $post ): void {
		// Verify nonce.
		if ( ! isset( $_POST['pb_faq_meta_nonce'] ) || ! wp_verify_nonce( $_POST['pb_faq_meta_nonce'], 'pb_faq_meta' ) ) {
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

		// Save question (from title).
		update_post_meta( $post_id, '_pb_faq_question', sanitize_text_field( $post->post_title ) );

		// Save answer (from content).
		update_post_meta( $post_id, '_pb_faq_answer', wp_kses_post( $post->post_content ) );

		// Save schema enabled.
		$schema_enabled = isset( $_POST['pb_faq_schema_enabled'] ) ? '1' : '0';
		update_post_meta( $post_id, '_pb_faq_schema_enabled', $schema_enabled );
	}

	/**
	 * FAQ shortcode handler.
	 *
	 * Usage: [faq id="123"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string FAQ HTML output.
	 */
	public function shortcode( $atts ): string {
		$atts = shortcode_atts( [
			'id' => 0,
		], $atts, 'faq' );

		$faq_id = absint( $atts['id'] );

		if ( ! $faq_id ) {
			return '';
		}

		$faq_post = get_post( $faq_id );

		if ( ! $faq_post || $faq_post->post_type !== self::POST_TYPE ) {
			return '';
		}

		$question = get_post_meta( $faq_id, '_pb_faq_question', true ) ?: $faq_post->post_title;
		$answer   = get_post_meta( $faq_id, '_pb_faq_answer', true ) ?: $faq_post->post_content;

		ob_start();
		?>
		<div class="pb-faq-block" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
			<h3 class="pb-faq-question" itemprop="name"><?php echo esc_html( $question ); ?></h3>
			<div class="pb-faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
				<div itemprop="text">
					<?php echo wp_kses_post( wpautop( $answer ) ); ?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get FAQ data for a post.
	 *
	 * @param int $post_id FAQ post ID.
	 * @return array|null FAQ data or null.
	 */
	public static function get_faq_data( int $post_id ): ?array {
		$post = get_post( $post_id );

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return null;
		}

		return [
			'id'             => $post_id,
			'question'       => get_post_meta( $post_id, '_pb_faq_question', true ) ?: $post->post_title,
			'answer'         => get_post_meta( $post_id, '_pb_faq_answer', true ) ?: $post->post_content,
			'schema_enabled' => (bool) get_post_meta( $post_id, '_pb_faq_schema_enabled', true ),
		];
	}

	/**
	 * Get all FAQs embedded in a post's content.
	 *
	 * @param string $content Post content.
	 * @return array Array of FAQ IDs found in content.
	 */
	public static function extract_faq_ids( string $content ): array {
		preg_match_all( '/\[faq\s+id=["\']?(\d+)["\']?\]/', $content, $matches );

		if ( empty( $matches[1] ) ) {
			return [];
		}

		return array_map( 'absint', $matches[1] );
	}

	/**
	 * Generate FAQPage schema for FAQs in content.
	 *
	 * @param string $content Post content.
	 * @return array|null Schema.org FAQPage data or null.
	 */
	public static function generate_faq_schema( string $content ): ?array {
		$faq_ids = self::extract_faq_ids( $content );

		if ( empty( $faq_ids ) ) {
			return null;
		}

		$entities = [];

		foreach ( $faq_ids as $faq_id ) {
			$faq_data = self::get_faq_data( $faq_id );

			if ( ! $faq_data || ! $faq_data['schema_enabled'] ) {
				continue;
			}

			$entities[] = [
				'@type'          => 'Question',
				'name'           => $faq_data['question'],
				'acceptedAnswer' => [
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( $faq_data['answer'] ),
				],
			];
		}

		if ( empty( $entities ) ) {
			return null;
		}

		return [
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $entities,
		];
	}
}

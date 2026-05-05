<?php
/**
 * Topic Custom Post Type for Programmatic SEO
 *
 * Stores structured SEO topics with intent classification, location, and service data.
 * Used by Programmatic SEO Engine for automated content generation.
 *
 * Fields:
 * - keyword: Main search keyword/phrase
 * - intent_type: info / commercial / local
 * - city: Optional city name for local SEO
 * - service: Optional service type
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

/**
 * Registers and manages the pb_topic custom post type.
 */
class TopicCPT {

	public const POST_TYPE = 'pb_topic';
	public const TAXONOMY_INTENT = 'pb_topic_intent';

	/**
	 * Register the CPT and taxonomy.
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'init', [ $this, 'register_taxonomy' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_meta' ], 10, 2 );
	}

	/**
	 * Register pb_topic custom post type.
	 */
	public function register_post_type(): void {
		$labels = [
			'name'                  => __( 'SEO Topics', 'pearblog-engine' ),
			'singular_name'         => __( 'SEO Topic', 'pearblog-engine' ),
			'menu_name'             => __( 'SEO Topics', 'pearblog-engine' ),
			'name_admin_bar'        => __( 'SEO Topic', 'pearblog-engine' ),
			'add_new'               => __( 'Add New', 'pearblog-engine' ),
			'add_new_item'          => __( 'Add New Topic', 'pearblog-engine' ),
			'new_item'              => __( 'New Topic', 'pearblog-engine' ),
			'edit_item'             => __( 'Edit Topic', 'pearblog-engine' ),
			'view_item'             => __( 'View Topic', 'pearblog-engine' ),
			'all_items'             => __( 'All Topics', 'pearblog-engine' ),
			'search_items'          => __( 'Search Topics', 'pearblog-engine' ),
			'not_found'             => __( 'No topics found.', 'pearblog-engine' ),
			'not_found_in_trash'    => __( 'No topics found in Trash.', 'pearblog-engine' ),
		];

		$args = [
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => 'pearblog-engine',
			'query_var'           => true,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => null,
			'menu_icon'           => 'dashicons-lightbulb',
			'show_in_rest'        => true,
			'rest_base'           => 'topics',
			'supports'            => [ 'title', 'editor', 'author' ],
		];

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register pb_topic_intent taxonomy for intent classification.
	 */
	public function register_taxonomy(): void {
		$labels = [
			'name'              => __( 'Topic Intent', 'pearblog-engine' ),
			'singular_name'     => __( 'Intent', 'pearblog-engine' ),
			'search_items'      => __( 'Search Intents', 'pearblog-engine' ),
			'all_items'         => __( 'All Intents', 'pearblog-engine' ),
			'edit_item'         => __( 'Edit Intent', 'pearblog-engine' ),
			'update_item'       => __( 'Update Intent', 'pearblog-engine' ),
			'add_new_item'      => __( 'Add New Intent', 'pearblog-engine' ),
			'new_item_name'     => __( 'New Intent Name', 'pearblog-engine' ),
			'menu_name'         => __( 'Intent Types', 'pearblog-engine' ),
		];

		$args = [
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'show_in_rest'      => true,
			'rewrite'           => false,
		];

		register_taxonomy( self::TAXONOMY_INTENT, [ self::POST_TYPE ], $args );

		// Pre-populate default intent types.
		$this->ensure_default_terms();
	}

	/**
	 * Ensure default intent terms exist.
	 */
	private function ensure_default_terms(): void {
		$default_intents = [ 'info', 'commercial', 'local' ];

		foreach ( $default_intents as $slug ) {
			if ( ! term_exists( $slug, self::TAXONOMY_INTENT ) ) {
				wp_insert_term( ucfirst( $slug ), self::TAXONOMY_INTENT, [
					'slug' => $slug,
				] );
			}
		}
	}

	/**
	 * Add meta boxes for topic fields.
	 */
	public function add_meta_boxes(): void {
		add_meta_box(
			'pb_topic_details',
			__( 'Topic Details', 'pearblog-engine' ),
			[ $this, 'render_meta_box' ],
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render topic details meta box.
	 *
	 * @param \WP_Post $post Current post object.
	 */
	public function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'pb_topic_meta', 'pb_topic_meta_nonce' );

		$keyword = get_post_meta( $post->ID, '_pb_topic_keyword', true );
		$city    = get_post_meta( $post->ID, '_pb_topic_city', true );
		$service = get_post_meta( $post->ID, '_pb_topic_service', true );
		$status  = get_post_meta( $post->ID, '_pb_topic_status', true ) ?: 'pending';

		?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="pb_topic_keyword"><?php esc_html_e( 'Keyword', 'pearblog-engine' ); ?></label>
				</th>
				<td>
					<input type="text" id="pb_topic_keyword" name="pb_topic_keyword" value="<?php echo esc_attr( $keyword ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Main search keyword or phrase for this topic.', 'pearblog-engine' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="pb_topic_city"><?php esc_html_e( 'City', 'pearblog-engine' ); ?></label>
				</th>
				<td>
					<input type="text" id="pb_topic_city" name="pb_topic_city" value="<?php echo esc_attr( $city ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Optional: Target city for local SEO.', 'pearblog-engine' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="pb_topic_service"><?php esc_html_e( 'Service', 'pearblog-engine' ); ?></label>
				</th>
				<td>
					<input type="text" id="pb_topic_service" name="pb_topic_service" value="<?php echo esc_attr( $service ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Optional: Service type (e.g., "mechanik", "elektryk").', 'pearblog-engine' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="pb_topic_status"><?php esc_html_e( 'Status', 'pearblog-engine' ); ?></label>
				</th>
				<td>
					<select id="pb_topic_status" name="pb_topic_status">
						<option value="pending" <?php selected( $status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'pearblog-engine' ); ?></option>
						<option value="queued" <?php selected( $status, 'queued' ); ?>><?php esc_html_e( 'Queued', 'pearblog-engine' ); ?></option>
						<option value="generated" <?php selected( $status, 'generated' ); ?>><?php esc_html_e( 'Generated', 'pearblog-engine' ); ?></option>
						<option value="published" <?php selected( $status, 'published' ); ?>><?php esc_html_e( 'Published', 'pearblog-engine' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Current status in the content generation pipeline.', 'pearblog-engine' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save topic meta data.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function save_meta( int $post_id, \WP_Post $post ): void {
		// Verify nonce.
		if ( ! isset( $_POST['pb_topic_meta_nonce'] ) || ! wp_verify_nonce( $_POST['pb_topic_meta_nonce'], 'pb_topic_meta' ) ) {
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

		// Save keyword.
		if ( isset( $_POST['pb_topic_keyword'] ) ) {
			update_post_meta( $post_id, '_pb_topic_keyword', sanitize_text_field( $_POST['pb_topic_keyword'] ) );
		}

		// Save city.
		if ( isset( $_POST['pb_topic_city'] ) ) {
			update_post_meta( $post_id, '_pb_topic_city', sanitize_text_field( $_POST['pb_topic_city'] ) );
		}

		// Save service.
		if ( isset( $_POST['pb_topic_service'] ) ) {
			update_post_meta( $post_id, '_pb_topic_service', sanitize_text_field( $_POST['pb_topic_service'] ) );
		}

		// Save status.
		if ( isset( $_POST['pb_topic_status'] ) ) {
			$status = sanitize_text_field( $_POST['pb_topic_status'] );
			if ( in_array( $status, [ 'pending', 'queued', 'generated', 'published' ], true ) ) {
				update_post_meta( $post_id, '_pb_topic_status', $status );
			}
		}
	}

	/**
	 * Get topic data as structured array.
	 *
	 * @param int $post_id Topic post ID.
	 * @return array|null Topic data or null if not found.
	 */
	public static function get_topic_data( int $post_id ): ?array {
		$post = get_post( $post_id );

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return null;
		}

		$terms = wp_get_post_terms( $post_id, self::TAXONOMY_INTENT, [ 'fields' => 'slugs' ] );

		return [
			'id'          => $post_id,
			'keyword'     => get_post_meta( $post_id, '_pb_topic_keyword', true ),
			'intent_type' => ! empty( $terms ) && ! is_wp_error( $terms ) ? $terms[0] : '',
			'city'        => get_post_meta( $post_id, '_pb_topic_city', true ),
			'service'     => get_post_meta( $post_id, '_pb_topic_service', true ),
			'status'      => get_post_meta( $post_id, '_pb_topic_status', true ) ?: 'pending',
			'title'       => $post->post_title,
			'content'     => $post->post_content,
		];
	}

	/**
	 * Create a new topic programmatically.
	 *
	 * @param array $args Topic data array.
	 * @return int|\WP_Error Topic post ID or error.
	 */
	public static function create_topic( array $args ) {
		$defaults = [
			'title'       => '',
			'keyword'     => '',
			'intent_type' => 'info',
			'city'        => '',
			'service'     => '',
			'content'     => '',
		];

		$args = wp_parse_args( $args, $defaults );

		// Create the post.
		$post_id = wp_insert_post( [
			'post_type'    => self::POST_TYPE,
			'post_title'   => $args['title'] ?: $args['keyword'],
			'post_content' => $args['content'],
			'post_status'  => 'publish',
		], true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Set intent taxonomy.
		if ( ! empty( $args['intent_type'] ) ) {
			wp_set_post_terms( $post_id, [ $args['intent_type'] ], self::TAXONOMY_INTENT );
		}

		// Set meta fields.
		update_post_meta( $post_id, '_pb_topic_keyword', sanitize_text_field( $args['keyword'] ) );
		update_post_meta( $post_id, '_pb_topic_city', sanitize_text_field( $args['city'] ) );
		update_post_meta( $post_id, '_pb_topic_service', sanitize_text_field( $args['service'] ) );
		update_post_meta( $post_id, '_pb_topic_status', 'pending' );

		return $post_id;
	}
}

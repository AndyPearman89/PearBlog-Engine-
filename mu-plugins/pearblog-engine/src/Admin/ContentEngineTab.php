<?php
/**
 * Content Engine Tab - Batch Content Operations
 *
 * Batch generation, content templates, and rewrite/update functionality.
 *
 * @package PearBlogEngine\Admin
 * @since 7.3.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Admin;

use PearBlogEngine\Content\TopicQueue;

/**
 * Content Engine tab controller for Admin v7.
 */
class ContentEngineTab {

	/**
	 * Render the content engine tab HTML.
	 */
	public static function render(): void {
		$queue = new TopicQueue();
		$queue_count = $queue->count_pending();
		?>
		<div class="pearblog-v7-content-engine">
			<div class="content-engine-header">
				<h2><?php echo esc_html__( 'Content Engine', 'pearblog-engine' ); ?></h2>
				<p><?php echo esc_html__( 'Batch content generation, templates, and content operations.', 'pearblog-engine' ); ?></p>
			</div>

			<!-- Batch Generation -->
			<div class="engine-section">
				<h3><?php echo esc_html__( 'Batch Generation', 'pearblog-engine' ); ?></h3>
				<p class="section-description">
					<?php echo esc_html__( 'Generate multiple articles at once from your topic queue.', 'pearblog-engine' ); ?>
				</p>

				<div class="batch-gen-controls">
					<div class="batch-gen-info">
						<div class="info-card">
							<div class="info-label"><?php echo esc_html__( 'Topics in Queue', 'pearblog-engine' ); ?></div>
							<div class="info-value"><?php echo number_format( $queue_count ); ?></div>
						</div>
						<div class="info-card">
							<div class="info-label"><?php echo esc_html__( 'Estimated Time', 'pearblog-engine' ); ?></div>
							<div class="info-value"><?php echo esc_html( self::estimate_time( 10 ) ); ?></div>
						</div>
					</div>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="batch-gen-form">
						<input type="hidden" name="action" value="pearblog_batch_generate" />
						<?php wp_nonce_field( 'pearblog_batch_generate', 'pearblog_batch_nonce' ); ?>

						<div class="form-row">
							<label for="batch_count"><?php echo esc_html__( 'Number of Articles', 'pearblog-engine' ); ?></label>
							<input
								type="number"
								id="batch_count"
								name="batch_count"
								value="10"
								min="1"
								max="100"
								class="small-text"
							/>
							<span class="description"><?php echo esc_html__( 'Generate 1-100 articles', 'pearblog-engine' ); ?></span>
						</div>

						<div class="form-row">
							<label>
								<input type="checkbox" name="publish_immediately" value="1" checked />
								<?php echo esc_html__( 'Publish immediately', 'pearblog-engine' ); ?>
							</label>
							<span class="description"><?php echo esc_html__( 'Uncheck to save as drafts', 'pearblog-engine' ); ?></span>
						</div>

						<div class="form-row">
							<label>
								<input type="checkbox" name="generate_images" value="1" <?php checked( get_option( 'pearblog_enable_image_generation', false ) ); ?> />
								<?php echo esc_html__( 'Generate featured images', 'pearblog-engine' ); ?>
							</label>
							<span class="description"><?php echo esc_html__( 'Uses DALL-E API (costs apply)', 'pearblog-engine' ); ?></span>
						</div>

						<?php submit_button( __( 'Start Batch Generation', 'pearblog-engine' ), 'primary large', 'submit', false ); ?>
					</form>
				</div>

				<?php if ( $queue_count === 0 ) : ?>
					<div class="pearblog-notice pearblog-notice-warning">
						<p><strong><?php echo esc_html__( 'No topics in queue', 'pearblog-engine' ); ?></strong></p>
						<p><?php echo esc_html__( 'Add topics to the queue in the Automation tab before running batch generation.', 'pearblog-engine' ); ?></p>
					</div>
				<?php endif; ?>
			</div>

			<!-- Content Templates -->
			<div class="engine-section">
				<h3><?php echo esc_html__( 'Content Templates', 'pearblog-engine' ); ?></h3>
				<p class="section-description">
					<?php echo esc_html__( 'Define content structures and formats for different article types.', 'pearblog-engine' ); ?>
				</p>

				<?php self::render_templates(); ?>
			</div>

			<!-- Bulk Operations -->
			<div class="engine-section">
				<h3><?php echo esc_html__( 'Bulk Operations', 'pearblog-engine' ); ?></h3>
				<p class="section-description">
					<?php echo esc_html__( 'Rewrite, update, or optimize existing content in bulk.', 'pearblog-engine' ); ?>
				</p>

				<div class="bulk-operations-grid">
					<div class="operation-card">
						<div class="operation-icon">🔄</div>
						<div class="operation-title"><?php echo esc_html__( 'Rewrite Content', 'pearblog-engine' ); ?></div>
						<div class="operation-desc"><?php echo esc_html__( 'Refresh existing articles with new AI-generated content', 'pearblog-engine' ); ?></div>
						<button class="button" disabled><?php echo esc_html__( 'Coming Soon', 'pearblog-engine' ); ?></button>
					</div>

					<div class="operation-card">
						<div class="operation-icon">⚡</div>
						<div class="operation-title"><?php echo esc_html__( 'SEO Optimization', 'pearblog-engine' ); ?></div>
						<div class="operation-desc"><?php echo esc_html__( 'Optimize meta titles, descriptions, and headings', 'pearblog-engine' ); ?></div>
						<button class="button" disabled><?php echo esc_html__( 'Coming Soon', 'pearblog-engine' ); ?></button>
					</div>

					<div class="operation-card">
						<div class="operation-icon">🖼️</div>
						<div class="operation-title"><?php echo esc_html__( 'Regenerate Images', 'pearblog-engine' ); ?></div>
						<div class="operation-desc"><?php echo esc_html__( 'Generate new featured images for existing articles', 'pearblog-engine' ); ?></div>
						<button class="button" disabled><?php echo esc_html__( 'Coming Soon', 'pearblog-engine' ); ?></button>
					</div>

					<div class="operation-card">
						<div class="operation-icon">📊</div>
						<div class="operation-title"><?php echo esc_html__( 'Update Statistics', 'pearblog-engine' ); ?></div>
						<div class="operation-desc"><?php echo esc_html__( 'Refresh data, statistics, and facts in articles', 'pearblog-engine' ); ?></div>
						<button class="button" disabled><?php echo esc_html__( 'Coming Soon', 'pearblog-engine' ); ?></button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render content templates section.
	 */
	private static function render_templates(): void {
		$templates = self::get_templates();
		$active_template = get_option( 'pearblog_content_template', 'default' );
		?>
		<div class="templates-grid">
			<?php foreach ( $templates as $template_id => $template ) : ?>
				<div class="template-card <?php echo $template_id === $active_template ? 'is-active' : ''; ?>">
					<div class="template-header">
						<div class="template-icon"><?php echo esc_html( $template['icon'] ); ?></div>
						<div class="template-title"><?php echo esc_html( $template['name'] ); ?></div>
						<?php if ( $template_id === $active_template ) : ?>
							<span class="template-badge"><?php echo esc_html__( 'Active', 'pearblog-engine' ); ?></span>
						<?php endif; ?>
					</div>
					<div class="template-desc"><?php echo esc_html( $template['description'] ); ?></div>
					<div class="template-features">
						<?php foreach ( $template['features'] as $feature ) : ?>
							<span class="feature-tag"><?php echo esc_html( $feature ); ?></span>
						<?php endforeach; ?>
					</div>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="pearblog_set_template" />
						<input type="hidden" name="template_id" value="<?php echo esc_attr( $template_id ); ?>" />
						<?php wp_nonce_field( 'pearblog_set_template', 'pearblog_template_nonce' ); ?>
						<?php if ( $template_id === $active_template ) : ?>
							<button type="button" class="button" disabled><?php echo esc_html__( 'Active', 'pearblog-engine' ); ?></button>
						<?php else : ?>
							<button type="submit" class="button button-secondary"><?php echo esc_html__( 'Activate', 'pearblog-engine' ); ?></button>
						<?php endif; ?>
					</form>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Get available content templates.
	 *
	 * @return array Template definitions.
	 */
	private static function get_templates(): array {
		return [
			'default' => [
				'name'        => __( 'Default', 'pearblog-engine' ),
				'icon'        => '📄',
				'description' => __( 'Standard blog post format with introduction, body, and conclusion', 'pearblog-engine' ),
				'features'    => [ 'H2/H3 headings', 'Lists', 'Conclusion' ],
			],
			'how_to' => [
				'name'        => __( 'How-To Guide', 'pearblog-engine' ),
				'icon'        => '📋',
				'description' => __( 'Step-by-step instructional format with numbered steps', 'pearblog-engine' ),
				'features'    => [ 'Numbered steps', 'Tips box', 'FAQ section' ],
			],
			'listicle' => [
				'name'        => __( 'Listicle', 'pearblog-engine' ),
				'icon'        => '📝',
				'description' => __( 'List-based article format (Top 10, Best 5, etc.)', 'pearblog-engine' ),
				'features'    => [ 'Numbered list', 'Quick summary', 'Comparison table' ],
			],
			'review' => [
				'name'        => __( 'Product Review', 'pearblog-engine' ),
				'icon'        => '⭐',
				'description' => __( 'Detailed product review with pros/cons and ratings', 'pearblog-engine' ),
				'features'    => [ 'Pros/Cons', 'Rating system', 'Verdict box' ],
			],
			'comparison' => [
				'name'        => __( 'Comparison', 'pearblog-engine' ),
				'icon'        => '⚖️',
				'description' => __( 'Side-by-side comparison of products or services', 'pearblog-engine' ),
				'features'    => [ 'Comparison table', 'Winner badge', 'Specs chart' ],
			],
			'news' => [
				'name'        => __( 'News Article', 'pearblog-engine' ),
				'icon'        => '📰',
				'description' => __( 'Timely news format with inverted pyramid structure', 'pearblog-engine' ),
				'features'    => [ 'Lead paragraph', 'Quotes', 'Update timestamp' ],
			],
		];
	}

	/**
	 * Estimate generation time.
	 *
	 * @param int $count Number of articles.
	 * @return string Estimated time string.
	 */
	private static function estimate_time( int $count ): string {
		$minutes = $count * 2; // ~2 minutes per article
		if ( $minutes < 60 ) {
			return sprintf( _n( '%d minute', '%d minutes', $minutes, 'pearblog-engine' ), $minutes );
		}
		$hours = round( $minutes / 60, 1 );
		return sprintf( _n( '%s hour', '%s hours', $hours, 'pearblog-engine' ), $hours );
	}

	/**
	 * Handle batch generation request.
	 */
	public static function handle_batch_generate(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_batch_generate', 'pearblog_batch_nonce' );

		$count = isset( $_POST['batch_count'] ) ? absint( $_POST['batch_count'] ) : 10;
		$count = max( 1, min( 100, $count ) ); // Clamp between 1-100

		$publish_immediately = isset( $_POST['publish_immediately'] );
		$generate_images = isset( $_POST['generate_images'] );

		// Store batch generation request
		update_option( 'pearblog_batch_generation_pending', [
			'count'              => $count,
			'publish'            => $publish_immediately,
			'images'             => $generate_images,
			'started_at'         => current_time( 'mysql' ),
			'status'             => 'queued',
		] );

		// Redirect back with success message
		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'pearblog-engine-v7',
					'tab'     => 'content',
					'batch'   => 'queued',
					'count'   => $count,
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle template activation.
	 */
	public static function handle_set_template(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_set_template', 'pearblog_template_nonce' );

		$template_id = isset( $_POST['template_id'] ) ? sanitize_key( $_POST['template_id'] ) : 'default';
		update_option( 'pearblog_content_template', $template_id );

		// Redirect back with success message
		wp_safe_redirect(
			add_query_arg(
				[
					'page'     => 'pearblog-engine-v7',
					'tab'      => 'content',
					'template' => 'updated',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}

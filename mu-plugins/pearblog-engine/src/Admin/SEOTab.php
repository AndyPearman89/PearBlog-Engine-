<?php
/**
 * SEO Engine Tab for Admin Panel v7.0
 *
 * SEO automation controls, internal linking, meta optimization, and programmatic SEO.
 *
 * @package PearBlogEngine\Admin
 * @since 7.5.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Admin;

/**
 * SEO Engine tab component.
 */
class SEOTab {

	/**
	 * Render the SEO Engine tab content.
	 */
	public static function render(): void {
		$seo_enabled           = get_option( 'pearblog_seo_automation_enabled', true );
		$internal_links        = get_option( 'pearblog_internal_links_enabled', true );
		$internal_links_count  = get_option( 'pearblog_internal_links_count', 3 );
		$meta_optimization     = get_option( 'pearblog_meta_optimization_enabled', true );
		$schema_enabled        = get_option( 'pearblog_schema_enabled', true );
		$sitemap_enabled       = get_option( 'pearblog_sitemap_enabled', true );
		$programmatic_seo      = get_option( 'pearblog_programmatic_seo_enabled', false );
		?>
		<div class="pearblog-v7-seo">
			<div class="pearblog-seo-header">
				<h2><?php echo esc_html__( 'SEO Engine', 'pearblog-engine' ); ?></h2>
				<p class="description">
					<?php echo esc_html__( 'Automated SEO optimization, internal linking, and programmatic SEO for maximum search visibility.', 'pearblog-engine' ); ?>
				</p>
			</div>

			<!-- SEO Automation Settings -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'SEO Automation', 'pearblog-engine' ); ?></h3>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pearblog-seo-form">
					<input type="hidden" name="action" value="pearblog_save_seo_settings" />
					<?php wp_nonce_field( 'pearblog_seo_settings', 'pearblog_seo_nonce' ); ?>

					<div class="pearblog-seo-options-grid">
						<!-- SEO Automation Toggle -->
						<div class="pearblog-seo-option-card">
							<div class="pearblog-option-header">
								<span class="pearblog-option-icon">🎯</span>
								<h4><?php echo esc_html__( 'SEO Automation', 'pearblog-engine' ); ?></h4>
							</div>
							<p class="pearblog-option-description">
								<?php echo esc_html__( 'Automatically optimize content for search engines using AI-powered SEO analysis.', 'pearblog-engine' ); ?>
							</p>
							<label class="pearblog-switch">
								<input
									type="checkbox"
									name="pearblog_seo_automation_enabled"
									value="1"
									<?php checked( $seo_enabled ); ?>
								/>
								<span class="pearblog-switch-slider"></span>
							</label>
						</div>

						<!-- Meta Optimization -->
						<div class="pearblog-seo-option-card">
							<div class="pearblog-option-header">
								<span class="pearblog-option-icon">📝</span>
								<h4><?php echo esc_html__( 'Meta Optimization', 'pearblog-engine' ); ?></h4>
							</div>
							<p class="pearblog-option-description">
								<?php echo esc_html__( 'Auto-generate optimized meta titles, descriptions, and Open Graph tags.', 'pearblog-engine' ); ?>
							</p>
							<label class="pearblog-switch">
								<input
									type="checkbox"
									name="pearblog_meta_optimization_enabled"
									value="1"
									<?php checked( $meta_optimization ); ?>
								/>
								<span class="pearblog-switch-slider"></span>
							</label>
						</div>

						<!-- Schema Markup -->
						<div class="pearblog-seo-option-card">
							<div class="pearblog-option-header">
								<span class="pearblog-option-icon">🔖</span>
								<h4><?php echo esc_html__( 'Schema Markup', 'pearblog-engine' ); ?></h4>
							</div>
							<p class="pearblog-option-description">
								<?php echo esc_html__( 'Add structured data (Schema.org) for rich search results and featured snippets.', 'pearblog-engine' ); ?>
							</p>
							<label class="pearblog-switch">
								<input
									type="checkbox"
									name="pearblog_schema_enabled"
									value="1"
									<?php checked( $schema_enabled ); ?>
								/>
								<span class="pearblog-switch-slider"></span>
							</label>
						</div>

						<!-- XML Sitemap -->
						<div class="pearblog-seo-option-card">
							<div class="pearblog-option-header">
								<span class="pearblog-option-icon">🗺️</span>
								<h4><?php echo esc_html__( 'XML Sitemap', 'pearblog-engine' ); ?></h4>
							</div>
							<p class="pearblog-option-description">
								<?php echo esc_html__( 'Automatically update XML sitemap for better crawling and indexing.', 'pearblog-engine' ); ?>
							</p>
							<label class="pearblog-switch">
								<input
									type="checkbox"
									name="pearblog_sitemap_enabled"
									value="1"
									<?php checked( $sitemap_enabled ); ?>
								/>
								<span class="pearblog-switch-slider"></span>
							</label>
						</div>
					</div>

					<button type="submit" class="pearblog-button pearblog-button-primary">
						<?php echo esc_html__( 'Save SEO Settings', 'pearblog-engine' ); ?>
					</button>
				</form>
			</div>

			<!-- Internal Linking -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Internal Linking', 'pearblog-engine' ); ?></h3>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pearblog-seo-form">
					<input type="hidden" name="action" value="pearblog_save_internal_links" />
					<?php wp_nonce_field( 'pearblog_internal_links', 'pearblog_internal_links_nonce' ); ?>

					<div class="pearblog-form-section">
						<label class="pearblog-toggle-label">
							<input
								type="checkbox"
								name="pearblog_internal_links_enabled"
								value="1"
								<?php checked( $internal_links ); ?>
							/>
							<span class="pearblog-toggle-text">
								<strong><?php echo esc_html__( 'Enable Automatic Internal Linking', 'pearblog-engine' ); ?></strong>
								<span class="pearblog-toggle-description"><?php echo esc_html__( 'AI will automatically add contextual internal links to related content', 'pearblog-engine' ); ?></span>
							</span>
						</label>
					</div>

					<div class="pearblog-form-section">
						<label for="pearblog_internal_links_count"><?php echo esc_html__( 'Links Per Article', 'pearblog-engine' ); ?></label>
						<select name="pearblog_internal_links_count" id="pearblog_internal_links_count" class="pearblog-select">
							<?php for ( $i = 1; $i <= 10; $i++ ) : ?>
								<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $internal_links_count, $i ); ?>>
									<?php echo esc_html( sprintf( _n( '%d link', '%d links', $i, 'pearblog-engine' ), $i ) ); ?>
								</option>
							<?php endfor; ?>
						</select>
						<p class="description">
							<?php echo esc_html__( 'Number of internal links to automatically insert per article.', 'pearblog-engine' ); ?>
						</p>
					</div>

					<div class="pearblog-form-section">
						<label for="pearblog_link_strategy"><?php echo esc_html__( 'Linking Strategy', 'pearblog-engine' ); ?></label>
						<select name="pearblog_link_strategy" id="pearblog_link_strategy" class="pearblog-select">
							<option value="semantic" <?php selected( get_option( 'pearblog_link_strategy', 'semantic' ), 'semantic' ); ?>>
								<?php echo esc_html__( 'Semantic (AI-powered contextual matching)', 'pearblog-engine' ); ?>
							</option>
							<option value="category" <?php selected( get_option( 'pearblog_link_strategy', 'semantic' ), 'category' ); ?>>
								<?php echo esc_html__( 'Category-based (link to same category)', 'pearblog-engine' ); ?>
							</option>
							<option value="recent" <?php selected( get_option( 'pearblog_link_strategy', 'semantic' ), 'recent' ); ?>>
								<?php echo esc_html__( 'Recent posts (newest content)', 'pearblog-engine' ); ?>
							</option>
							<option value="popular" <?php selected( get_option( 'pearblog_link_strategy', 'semantic' ), 'popular' ); ?>>
								<?php echo esc_html__( 'Popular posts (most viewed)', 'pearblog-engine' ); ?>
							</option>
						</select>
					</div>

					<button type="submit" class="pearblog-button pearblog-button-primary">
						<?php echo esc_html__( 'Save Internal Linking Settings', 'pearblog-engine' ); ?>
					</button>
				</form>

				<!-- Internal Links Stats -->
				<div class="pearblog-seo-stats">
					<h4><?php echo esc_html__( 'Internal Linking Stats', 'pearblog-engine' ); ?></h4>
					<?php self::render_internal_links_stats(); ?>
				</div>
			</div>

			<!-- Programmatic SEO -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Programmatic SEO', 'pearblog-engine' ); ?></h3>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pearblog-seo-form">
					<input type="hidden" name="action" value="pearblog_save_programmatic_seo" />
					<?php wp_nonce_field( 'pearblog_programmatic_seo', 'pearblog_programmatic_seo_nonce' ); ?>

					<div class="pearblog-form-section">
						<label class="pearblog-toggle-label">
							<input
								type="checkbox"
								name="pearblog_programmatic_seo_enabled"
								value="1"
								<?php checked( $programmatic_seo ); ?>
							/>
							<span class="pearblog-toggle-text">
								<strong><?php echo esc_html__( 'Enable Programmatic SEO', 'pearblog-engine' ); ?></strong>
								<span class="pearblog-toggle-description"><?php echo esc_html__( 'Auto-generate landing pages for keyword variations and long-tail queries', 'pearblog-engine' ); ?></span>
							</span>
						</label>
					</div>

					<div class="pearblog-info-card">
						<p><strong><?php echo esc_html__( 'What is Programmatic SEO?', 'pearblog-engine' ); ?></strong></p>
						<p><?php echo esc_html__( 'Programmatic SEO creates hundreds or thousands of targeted landing pages using templates and data. This helps you rank for long-tail keywords and variations of your main topics.', 'pearblog-engine' ); ?></p>
					</div>

					<div class="pearblog-form-section">
						<label for="pearblog_pseo_template"><?php echo esc_html__( 'Page Template', 'pearblog-engine' ); ?></label>
						<select name="pearblog_pseo_template" id="pearblog_pseo_template" class="pearblog-select">
							<option value="location" <?php selected( get_option( 'pearblog_pseo_template', 'location' ), 'location' ); ?>>
								<?php echo esc_html__( 'Location-based (e.g., "Service in [City]")', 'pearblog-engine' ); ?>
							</option>
							<option value="comparison" <?php selected( get_option( 'pearblog_pseo_template', 'location' ), 'comparison' ); ?>>
								<?php echo esc_html__( 'Comparison (e.g., "[Product A] vs [Product B]")', 'pearblog-engine' ); ?>
							</option>
							<option value="alternative" <?php selected( get_option( 'pearblog_pseo_template', 'location' ), 'alternative' ); ?>>
								<?php echo esc_html__( 'Alternative (e.g., "Best [Product] Alternatives")', 'pearblog-engine' ); ?>
							</option>
							<option value="glossary" <?php selected( get_option( 'pearblog_pseo_template', 'location' ), 'glossary' ); ?>>
								<?php echo esc_html__( 'Glossary (e.g., "What is [Term]?")', 'pearblog-engine' ); ?>
							</option>
						</select>
					</div>

					<button type="submit" class="pearblog-button pearblog-button-primary">
						<?php echo esc_html__( 'Save Programmatic SEO Settings', 'pearblog-engine' ); ?>
					</button>
				</form>
			</div>

			<!-- SEO Performance Overview -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'SEO Performance', 'pearblog-engine' ); ?></h3>
				<?php self::render_seo_performance(); ?>
			</div>

			<!-- SEO Tools -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'SEO Tools', 'pearblog-engine' ); ?></h3>
				<div class="pearblog-seo-tools-grid">
					<div class="pearblog-tool-card">
						<span class="pearblog-tool-icon">🔗</span>
						<h4><?php echo esc_html__( 'Rebuild Internal Links', 'pearblog-engine' ); ?></h4>
						<p><?php echo esc_html__( 'Scan all posts and rebuild internal link structure.', 'pearblog-engine' ); ?></p>
						<button type="button" class="pearblog-button pearblog-button-secondary" onclick="alert('This will be implemented in Phase 5');">
							<?php echo esc_html__( 'Rebuild Links', 'pearblog-engine' ); ?>
						</button>
					</div>

					<div class="pearblog-tool-card">
						<span class="pearblog-tool-icon">🔍</span>
						<h4><?php echo esc_html__( 'Find Broken Links', 'pearblog-engine' ); ?></h4>
						<p><?php echo esc_html__( 'Scan content for broken internal and external links.', 'pearblog-engine' ); ?></p>
						<button type="button" class="pearblog-button pearblog-button-secondary" onclick="alert('This will be implemented in Phase 5');">
							<?php echo esc_html__( 'Scan Links', 'pearblog-engine' ); ?>
						</button>
					</div>

					<div class="pearblog-tool-card">
						<span class="pearblog-tool-icon">📊</span>
						<h4><?php echo esc_html__( 'SEO Audit', 'pearblog-engine' ); ?></h4>
						<p><?php echo esc_html__( 'Run comprehensive SEO audit on all content.', 'pearblog-engine' ); ?></p>
						<button type="button" class="pearblog-button pearblog-button-secondary" onclick="alert('This will be implemented in Phase 5');">
							<?php echo esc_html__( 'Run Audit', 'pearblog-engine' ); ?>
						</button>
					</div>

					<div class="pearblog-tool-card">
						<span class="pearblog-tool-icon">🗺️</span>
						<h4><?php echo esc_html__( 'Regenerate Sitemap', 'pearblog-engine' ); ?></h4>
						<p><?php echo esc_html__( 'Force regeneration of XML sitemap.', 'pearblog-engine' ); ?></p>
						<button type="button" class="pearblog-button pearblog-button-secondary" onclick="alert('This will be implemented in Phase 5');">
							<?php echo esc_html__( 'Regenerate', 'pearblog-engine' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render internal linking statistics.
	 */
	private static function render_internal_links_stats(): void {
		// Mock data - would be replaced with real stats from database
		$stats = [
			'total_links'     => 342,
			'avg_per_post'    => 3.2,
			'posts_with_links' => 98,
			'orphan_posts'    => 7,
		];
		?>
		<div class="pearblog-stats-grid">
			<div class="pearblog-stat-box">
				<div class="pearblog-stat-value"><?php echo esc_html( number_format( $stats['total_links'] ) ); ?></div>
				<div class="pearblog-stat-label"><?php echo esc_html__( 'Total Internal Links', 'pearblog-engine' ); ?></div>
			</div>
			<div class="pearblog-stat-box">
				<div class="pearblog-stat-value"><?php echo esc_html( number_format( $stats['avg_per_post'], 1 ) ); ?></div>
				<div class="pearblog-stat-label"><?php echo esc_html__( 'Avg Links Per Post', 'pearblog-engine' ); ?></div>
			</div>
			<div class="pearblog-stat-box">
				<div class="pearblog-stat-value"><?php echo esc_html( $stats['posts_with_links'] ); ?>%</div>
				<div class="pearblog-stat-label"><?php echo esc_html__( 'Posts With Links', 'pearblog-engine' ); ?></div>
			</div>
			<div class="pearblog-stat-box pearblog-stat-warning">
				<div class="pearblog-stat-value"><?php echo esc_html( $stats['orphan_posts'] ); ?></div>
				<div class="pearblog-stat-label"><?php echo esc_html__( 'Orphan Posts', 'pearblog-engine' ); ?></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render SEO performance overview.
	 */
	private static function render_seo_performance(): void {
		// Mock data - would be replaced with real SEO metrics
		$performance = [
			'avg_title_length'   => 58,
			'avg_desc_length'    => 152,
			'posts_with_schema'  => 87,
			'seo_score_avg'      => 78,
		];
		?>
		<div class="pearblog-stats-grid">
			<div class="pearblog-stat-box">
				<div class="pearblog-stat-value"><?php echo esc_html( $performance['avg_title_length'] ); ?></div>
				<div class="pearblog-stat-label"><?php echo esc_html__( 'Avg Title Length', 'pearblog-engine' ); ?></div>
				<div class="pearblog-stat-note"><?php echo esc_html__( 'Optimal: 50-60 chars', 'pearblog-engine' ); ?></div>
			</div>
			<div class="pearblog-stat-box">
				<div class="pearblog-stat-value"><?php echo esc_html( $performance['avg_desc_length'] ); ?></div>
				<div class="pearblog-stat-label"><?php echo esc_html__( 'Avg Description Length', 'pearblog-engine' ); ?></div>
				<div class="pearblog-stat-note"><?php echo esc_html__( 'Optimal: 150-160 chars', 'pearblog-engine' ); ?></div>
			</div>
			<div class="pearblog-stat-box">
				<div class="pearblog-stat-value"><?php echo esc_html( $performance['posts_with_schema'] ); ?>%</div>
				<div class="pearblog-stat-label"><?php echo esc_html__( 'Posts With Schema', 'pearblog-engine' ); ?></div>
				<div class="pearblog-stat-note"><?php echo esc_html__( 'Target: 100%', 'pearblog-engine' ); ?></div>
			</div>
			<div class="pearblog-stat-box pearblog-stat-success">
				<div class="pearblog-stat-value"><?php echo esc_html( $performance['seo_score_avg'] ); ?>/100</div>
				<div class="pearblog-stat-label"><?php echo esc_html__( 'Avg SEO Score', 'pearblog-engine' ); ?></div>
				<div class="pearblog-stat-note"><?php echo esc_html__( 'Good performance', 'pearblog-engine' ); ?></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle SEO settings form submission.
	 */
	public static function handle_save_seo_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_seo_settings', 'pearblog_seo_nonce' );

		$seo_enabled       = isset( $_POST['pearblog_seo_automation_enabled'] ) ? 1 : 0;
		$meta_optimization = isset( $_POST['pearblog_meta_optimization_enabled'] ) ? 1 : 0;
		$schema_enabled    = isset( $_POST['pearblog_schema_enabled'] ) ? 1 : 0;
		$sitemap_enabled   = isset( $_POST['pearblog_sitemap_enabled'] ) ? 1 : 0;

		update_option( 'pearblog_seo_automation_enabled', $seo_enabled );
		update_option( 'pearblog_meta_optimization_enabled', $meta_optimization );
		update_option( 'pearblog_schema_enabled', $schema_enabled );
		update_option( 'pearblog_sitemap_enabled', $sitemap_enabled );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'pearblog-engine-v7',
					'tab'     => 'seo',
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle internal links settings form submission.
	 */
	public static function handle_save_internal_links(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_internal_links', 'pearblog_internal_links_nonce' );

		$internal_links_enabled = isset( $_POST['pearblog_internal_links_enabled'] ) ? 1 : 0;
		$internal_links_count   = max( 1, min( 10, absint( $_POST['pearblog_internal_links_count'] ?? 3 ) ) );
		$link_strategy          = sanitize_key( $_POST['pearblog_link_strategy'] ?? 'semantic' );

		update_option( 'pearblog_internal_links_enabled', $internal_links_enabled );
		update_option( 'pearblog_internal_links_count', $internal_links_count );
		update_option( 'pearblog_link_strategy', $link_strategy );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'pearblog-engine-v7',
					'tab'     => 'seo',
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle programmatic SEO settings form submission.
	 */
	public static function handle_save_programmatic_seo(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_programmatic_seo', 'pearblog_programmatic_seo_nonce' );

		$programmatic_seo_enabled = isset( $_POST['pearblog_programmatic_seo_enabled'] ) ? 1 : 0;
		$pseo_template            = sanitize_key( $_POST['pearblog_pseo_template'] ?? 'location' );

		update_option( 'pearblog_programmatic_seo_enabled', $programmatic_seo_enabled );
		update_option( 'pearblog_pseo_template', $pseo_template );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'pearblog-engine-v7',
					'tab'     => 'seo',
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}

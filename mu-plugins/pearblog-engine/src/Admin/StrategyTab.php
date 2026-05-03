<?php
/**
 * Strategy Tab - AI-Driven Content Strategy
 *
 * Controls for AI keyword discovery, intent prioritization, and content planning.
 *
 * @package PearBlogEngine\Admin
 * @since 7.3.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Admin;

/**
 * Strategy tab controller for Admin v7.
 */
class StrategyTab {

	/**
	 * Render the strategy tab HTML.
	 */
	public static function render(): void {
		$keyword_source   = get_option( 'pearblog_keyword_source', 'manual' );
		$intent_priority  = get_option( 'pearblog_intent_priority', 'balanced' );
		$auto_discovery   = get_option( 'pearblog_auto_keyword_discovery', false );
		$discovery_limit  = get_option( 'pearblog_discovery_daily_limit', 10 );
		$scraping_enabled = get_option( 'pearblog_keyword_scraping_enabled', false );
		?>
		<div class="pearblog-v7-strategy">
			<div class="strategy-header">
				<h2><?php echo esc_html__( 'AI Content Strategy', 'pearblog-engine' ); ?></h2>
				<p><?php echo esc_html__( 'Configure AI-driven keyword discovery and content planning.', 'pearblog-engine' ); ?></p>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pearblog-strategy-form">
				<input type="hidden" name="action" value="pearblog_save_strategy" />
				<?php wp_nonce_field( 'pearblog_strategy_settings', 'pearblog_strategy_nonce' ); ?>

				<!-- Keyword Source Management -->
				<div class="strategy-section">
					<h3><?php echo esc_html__( 'Keyword Source', 'pearblog-engine' ); ?></h3>
					<p class="section-description"><?php echo esc_html__( 'Choose how PearBlog discovers and sources keywords for content generation.', 'pearblog-engine' ); ?></p>

					<div class="strategy-options">
						<label class="strategy-option-card <?php echo $keyword_source === 'manual' ? 'is-active' : ''; ?>">
							<input type="radio" name="pearblog_keyword_source" value="manual" <?php checked( $keyword_source, 'manual' ); ?> />
							<div class="option-content">
								<div class="option-icon">✍️</div>
								<div class="option-title"><?php echo esc_html__( 'Manual', 'pearblog-engine' ); ?></div>
								<div class="option-desc"><?php echo esc_html__( 'Add keywords manually via the Queue tab', 'pearblog-engine' ); ?></div>
							</div>
						</label>

						<label class="strategy-option-card <?php echo $keyword_source === 'auto' ? 'is-active' : ''; ?>">
							<input type="radio" name="pearblog_keyword_source" value="auto" <?php checked( $keyword_source, 'auto' ); ?> />
							<div class="option-content">
								<div class="option-icon">🤖</div>
								<div class="option-title"><?php echo esc_html__( 'Automatic', 'pearblog-engine' ); ?></div>
								<div class="option-desc"><?php echo esc_html__( 'AI discovers keywords based on your industry', 'pearblog-engine' ); ?></div>
							</div>
						</label>

						<label class="strategy-option-card <?php echo $keyword_source === 'scraping' ? 'is-active' : ''; ?>">
							<input type="radio" name="pearblog_keyword_source" value="scraping" <?php checked( $keyword_source, 'scraping' ); ?> />
							<div class="option-content">
								<div class="option-icon">🔍</div>
								<div class="option-title"><?php echo esc_html__( 'Competitive Scraping', 'pearblog-engine' ); ?></div>
								<div class="option-desc"><?php echo esc_html__( 'Scrape trending topics from competitors', 'pearblog-engine' ); ?></div>
							</div>
						</label>

						<label class="strategy-option-card <?php echo $keyword_source === 'hybrid' ? 'is-active' : ''; ?>">
							<input type="radio" name="pearblog_keyword_source" value="hybrid" <?php checked( $keyword_source, 'hybrid' ); ?> />
							<div class="option-content">
								<div class="option-icon">⚡</div>
								<div class="option-title"><?php echo esc_html__( 'Hybrid', 'pearblog-engine' ); ?></div>
								<div class="option-desc"><?php echo esc_html__( 'Combine manual, auto, and scraping', 'pearblog-engine' ); ?></div>
							</div>
						</label>
					</div>
				</div>

				<!-- Auto Discovery Settings -->
				<div class="strategy-section" id="auto-discovery-settings">
					<h3><?php echo esc_html__( 'Automatic Keyword Discovery', 'pearblog-engine' ); ?></h3>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="pearblog_auto_keyword_discovery"><?php echo esc_html__( 'Enable Auto Discovery', 'pearblog-engine' ); ?></label>
							</th>
							<td>
								<label>
									<input
										type="checkbox"
										id="pearblog_auto_keyword_discovery"
										name="pearblog_auto_keyword_discovery"
										value="1"
										<?php checked( $auto_discovery ); ?>
									/>
									<?php echo esc_html__( 'Automatically discover and queue new keywords daily', 'pearblog-engine' ); ?>
								</label>
								<p class="description">
									<?php echo esc_html__( 'AI will analyze your industry and add relevant keywords to the queue.', 'pearblog-engine' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="pearblog_discovery_daily_limit"><?php echo esc_html__( 'Daily Discovery Limit', 'pearblog-engine' ); ?></label>
							</th>
							<td>
								<input
									type="number"
									id="pearblog_discovery_daily_limit"
									name="pearblog_discovery_daily_limit"
									value="<?php echo esc_attr( $discovery_limit ); ?>"
									min="1"
									max="100"
									class="small-text"
								/>
								<?php echo esc_html__( 'keywords per day', 'pearblog-engine' ); ?>
								<p class="description">
									<?php echo esc_html__( 'Maximum number of keywords to discover automatically each day.', 'pearblog-engine' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<!-- Intent Priority System -->
				<div class="strategy-section">
					<h3><?php echo esc_html__( 'Search Intent Priority', 'pearblog-engine' ); ?></h3>
					<p class="section-description"><?php echo esc_html__( 'Prioritize which type of search intent to focus on for content generation.', 'pearblog-engine' ); ?></p>

					<div class="intent-priority-options">
						<label class="intent-option <?php echo $intent_priority === 'informational' ? 'is-active' : ''; ?>">
							<input type="radio" name="pearblog_intent_priority" value="informational" <?php checked( $intent_priority, 'informational' ); ?> />
							<div class="intent-content">
								<div class="intent-icon">📚</div>
								<div class="intent-title"><?php echo esc_html__( 'Informational', 'pearblog-engine' ); ?></div>
								<div class="intent-desc"><?php echo esc_html__( 'Educational content, "how to", guides', 'pearblog-engine' ); ?></div>
								<div class="intent-badge">TOFU</div>
							</div>
						</label>

						<label class="intent-option <?php echo $intent_priority === 'commercial' ? 'is-active' : ''; ?>">
							<input type="radio" name="pearblog_intent_priority" value="commercial" <?php checked( $intent_priority, 'commercial' ); ?> />
							<div class="intent-content">
								<div class="intent-icon">🛒</div>
								<div class="intent-title"><?php echo esc_html__( 'Commercial', 'pearblog-engine' ); ?></div>
								<div class="intent-desc"><?php echo esc_html__( 'Reviews, comparisons, "best" content', 'pearblog-engine' ); ?></div>
								<div class="intent-badge">MOFU</div>
							</div>
						</label>

						<label class="intent-option <?php echo $intent_priority === 'transactional' ? 'is-active' : ''; ?>">
							<input type="radio" name="pearblog_intent_priority" value="transactional" <?php checked( $intent_priority, 'transactional' ); ?> />
							<div class="intent-content">
								<div class="intent-icon">💳</div>
								<div class="intent-title"><?php echo esc_html__( 'Transactional', 'pearblog-engine' ); ?></div>
								<div class="intent-desc"><?php echo esc_html__( 'Product pages, service pages, buy intent', 'pearblog-engine' ); ?></div>
								<div class="intent-badge">BOFU</div>
							</div>
						</label>

						<label class="intent-option <?php echo $intent_priority === 'balanced' ? 'is-active' : ''; ?>">
							<input type="radio" name="pearblog_intent_priority" value="balanced" <?php checked( $intent_priority, 'balanced' ); ?> />
							<div class="intent-content">
								<div class="intent-icon">⚖️</div>
								<div class="intent-title"><?php echo esc_html__( 'Balanced', 'pearblog-engine' ); ?></div>
								<div class="intent-desc"><?php echo esc_html__( 'Mix of all intent types', 'pearblog-engine' ); ?></div>
								<div class="intent-badge">ALL</div>
							</div>
						</label>
					</div>
				</div>

				<!-- Competitive Scraping -->
				<div class="strategy-section" id="scraping-settings">
					<h3><?php echo esc_html__( 'Competitive Intelligence', 'pearblog-engine' ); ?></h3>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="pearblog_keyword_scraping_enabled"><?php echo esc_html__( 'Enable Scraping', 'pearblog-engine' ); ?></label>
							</th>
							<td>
								<label>
									<input
										type="checkbox"
										id="pearblog_keyword_scraping_enabled"
										name="pearblog_keyword_scraping_enabled"
										value="1"
										<?php checked( $scraping_enabled ); ?>
									/>
									<?php echo esc_html__( 'Monitor competitor sites for trending topics', 'pearblog-engine' ); ?>
								</label>
								<p class="description">
									<?php echo esc_html__( 'Analyze competitor RSS feeds and sitemaps to discover popular topics.', 'pearblog-engine' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="pearblog_competitor_urls"><?php echo esc_html__( 'Competitor URLs', 'pearblog-engine' ); ?></label>
							</th>
							<td>
								<textarea
									id="pearblog_competitor_urls"
									name="pearblog_competitor_urls"
									rows="5"
									class="large-text code"
									placeholder="https://competitor1.com&#10;https://competitor2.com"
								><?php echo esc_textarea( get_option( 'pearblog_competitor_urls', '' ) ); ?></textarea>
								<p class="description">
									<?php echo esc_html__( 'One URL per line. PearBlog will analyze these sites for trending topics.', 'pearblog-engine' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<div class="strategy-actions">
					<?php submit_button( __( 'Save Strategy Settings', 'pearblog-engine' ), 'primary', 'submit', false ); ?>
				</div>
			</form>

			<!-- Strategy Stats -->
			<div class="strategy-stats">
				<h3><?php echo esc_html__( 'Strategy Performance', 'pearblog-engine' ); ?></h3>
				<div class="stats-grid">
					<div class="stat-card">
						<div class="stat-label"><?php echo esc_html__( 'Keywords Discovered (30d)', 'pearblog-engine' ); ?></div>
						<div class="stat-value">0</div>
					</div>
					<div class="stat-card">
						<div class="stat-label"><?php echo esc_html__( 'Articles Generated', 'pearblog-engine' ); ?></div>
						<div class="stat-value">0</div>
					</div>
					<div class="stat-card">
						<div class="stat-label"><?php echo esc_html__( 'Avg. Search Volume', 'pearblog-engine' ); ?></div>
						<div class="stat-value">—</div>
					</div>
					<div class="stat-card">
						<div class="stat-label"><?php echo esc_html__( 'Success Rate', 'pearblog-engine' ); ?></div>
						<div class="stat-value">—</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle strategy settings save.
	 */
	public static function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_strategy_settings', 'pearblog_strategy_nonce' );

		// Save keyword source
		$keyword_source = isset( $_POST['pearblog_keyword_source'] ) ? sanitize_text_field( $_POST['pearblog_keyword_source'] ) : 'manual';
		update_option( 'pearblog_keyword_source', $keyword_source );

		// Save auto discovery settings
		$auto_discovery = isset( $_POST['pearblog_auto_keyword_discovery'] ) ? 1 : 0;
		update_option( 'pearblog_auto_keyword_discovery', $auto_discovery );

		$discovery_limit = isset( $_POST['pearblog_discovery_daily_limit'] ) ? absint( $_POST['pearblog_discovery_daily_limit'] ) : 10;
		update_option( 'pearblog_discovery_daily_limit', $discovery_limit );

		// Save intent priority
		$intent_priority = isset( $_POST['pearblog_intent_priority'] ) ? sanitize_text_field( $_POST['pearblog_intent_priority'] ) : 'balanced';
		update_option( 'pearblog_intent_priority', $intent_priority );

		// Save scraping settings
		$scraping_enabled = isset( $_POST['pearblog_keyword_scraping_enabled'] ) ? 1 : 0;
		update_option( 'pearblog_keyword_scraping_enabled', $scraping_enabled );

		$competitor_urls = isset( $_POST['pearblog_competitor_urls'] ) ? sanitize_textarea_field( $_POST['pearblog_competitor_urls'] ) : '';
		update_option( 'pearblog_competitor_urls', $competitor_urls );

		// Redirect back with success message
		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'pearblog-engine-v7',
					'tab'     => 'strategy',
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}

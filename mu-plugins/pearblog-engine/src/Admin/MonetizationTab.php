<?php
/**
 * Monetization Tab for Admin Panel v7.0
 *
 * Revenue tracking, ad management, and monetization optimization.
 *
 * @package PearBlogEngine\Admin
 * @since 7.7.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Admin;

/**
 * Monetization tab component.
 */
class MonetizationTab {

	/**
	 * Render the Monetization tab content.
	 */
	public static function render(): void {
		$adsense_enabled       = get_option( 'pearblog_adsense_enabled', false );
		$revenue_tracking      = get_option( 'pearblog_v7_revenue_enabled', false );
		$affiliate_enabled     = get_option( 'pearblog_affiliate_enabled', false );
		$sponsored_enabled     = get_option( 'pearblog_sponsored_posts_enabled', false );
		$adsense_id            = get_option( 'pearblog_adsense_publisher_id', '' );
		?>
		<div class="pearblog-v7-monetization">
			<div class="pearblog-monetization-header">
				<h2><?php echo esc_html__( 'Monetization & Revenue Tracking', 'pearblog-engine' ); ?></h2>
				<p class="description">
					<?php echo esc_html__( 'Track revenue per article, manage ad placements, and optimize monetization strategies.', 'pearblog-engine' ); ?>
				</p>
			</div>

			<!-- Revenue Overview -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Revenue Overview', 'pearblog-engine' ); ?></h3>
				<div class="pearblog-revenue-stats">
					<?php self::render_revenue_stats(); ?>
				</div>
			</div>

			<!-- AdSense Configuration -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Google AdSense', 'pearblog-engine' ); ?></h3>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pearblog-monetization-form">
					<input type="hidden" name="action" value="pearblog_save_adsense" />
					<?php wp_nonce_field( 'pearblog_adsense_settings', 'pearblog_adsense_nonce' ); ?>

					<div class="pearblog-form-section">
						<label class="pearblog-toggle-label">
							<input
								type="checkbox"
								name="pearblog_adsense_enabled"
								value="1"
								<?php checked( $adsense_enabled ); ?>
							/>
							<span class="pearblog-toggle-text">
								<strong><?php echo esc_html__( 'Enable Google AdSense', 'pearblog-engine' ); ?></strong>
								<span class="pearblog-toggle-description"><?php echo esc_html__( 'Display AdSense ads on your content', 'pearblog-engine' ); ?></span>
							</span>
						</label>
					</div>

					<div class="pearblog-form-section">
						<label for="pearblog_adsense_publisher_id"><?php echo esc_html__( 'Publisher ID', 'pearblog-engine' ); ?></label>
						<input
							type="text"
							name="pearblog_adsense_publisher_id"
							id="pearblog_adsense_publisher_id"
							value="<?php echo esc_attr( $adsense_id ); ?>"
							placeholder="ca-pub-XXXXXXXXXXXXXXXX"
							class="pearblog-input"
						/>
						<p class="description">
							<?php echo esc_html__( 'Your Google AdSense publisher ID (starts with ca-pub-)', 'pearblog-engine' ); ?>
						</p>
					</div>

					<div class="pearblog-adsense-placements">
						<h4><?php echo esc_html__( 'Ad Placements', 'pearblog-engine' ); ?></h4>
						<div class="pearblog-placements-grid">
							<?php
							$placements = [
								'header'         => [ 'label' => __( 'Header Ad', 'pearblog-engine' ), 'icon' => '📌' ],
								'in_content'     => [ 'label' => __( 'In-Content Ad', 'pearblog-engine' ), 'icon' => '📄' ],
								'sidebar'        => [ 'label' => __( 'Sidebar Ad', 'pearblog-engine' ), 'icon' => '📋' ],
								'footer'         => [ 'label' => __( 'Footer Ad', 'pearblog-engine' ), 'icon' => '⬇️' ],
								'between_posts'  => [ 'label' => __( 'Between Posts', 'pearblog-engine' ), 'icon' => '📰' ],
								'sticky_mobile'  => [ 'label' => __( 'Sticky Mobile', 'pearblog-engine' ), 'icon' => '📱' ],
							];

							foreach ( $placements as $key => $placement ) :
								$enabled = get_option( "pearblog_adsense_enable_{$key}", true );
								?>
								<label class="pearblog-placement-card">
									<input
										type="checkbox"
										name="pearblog_adsense_placements[]"
										value="<?php echo esc_attr( $key ); ?>"
										<?php checked( $enabled ); ?>
									/>
									<span class="pearblog-placement-icon"><?php echo esc_html( $placement['icon'] ); ?></span>
									<span class="pearblog-placement-label"><?php echo esc_html( $placement['label'] ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="pearblog-form-section">
						<label for="pearblog_adsense_strategy"><?php echo esc_html__( 'Monetization Strategy', 'pearblog-engine' ); ?></label>
						<select name="pearblog_adsense_strategy" id="pearblog_adsense_strategy" class="pearblog-select">
							<option value="aggressive" <?php selected( get_option( 'pearblog_adsense_strategy', 'balanced' ), 'aggressive' ); ?>>
								<?php echo esc_html__( 'Aggressive (Maximum revenue)', 'pearblog-engine' ); ?>
							</option>
							<option value="balanced" <?php selected( get_option( 'pearblog_adsense_strategy', 'balanced' ), 'balanced' ); ?>>
								<?php echo esc_html__( 'Balanced (Revenue + User experience)', 'pearblog-engine' ); ?>
							</option>
							<option value="conservative" <?php selected( get_option( 'pearblog_adsense_strategy', 'balanced' ), 'conservative' ); ?>>
								<?php echo esc_html__( 'Conservative (User experience first)', 'pearblog-engine' ); ?>
							</option>
							<option value="funnel_aware" <?php selected( get_option( 'pearblog_adsense_strategy', 'balanced' ), 'funnel_aware' ); ?>>
								<?php echo esc_html__( 'Funnel-Aware (Adaptive based on intent)', 'pearblog-engine' ); ?>
							</option>
						</select>
						<p class="description">
							<?php echo esc_html__( 'Funnel-Aware: TOFU (full ads), MOFU (limited), BOFU (minimal)', 'pearblog-engine' ); ?>
						</p>
					</div>

					<button type="submit" class="pearblog-button pearblog-button-primary">
						<?php echo esc_html__( 'Save AdSense Settings', 'pearblog-engine' ); ?>
					</button>
				</form>
			</div>

			<!-- Affiliate Marketing -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Affiliate Marketing', 'pearblog-engine' ); ?></h3>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pearblog-monetization-form">
					<input type="hidden" name="action" value="pearblog_save_affiliate" />
					<?php wp_nonce_field( 'pearblog_affiliate_settings', 'pearblog_affiliate_nonce' ); ?>

					<div class="pearblog-form-section">
						<label class="pearblog-toggle-label">
							<input
								type="checkbox"
								name="pearblog_affiliate_enabled"
								value="1"
								<?php checked( $affiliate_enabled ); ?>
							/>
							<span class="pearblog-toggle-text">
								<strong><?php echo esc_html__( 'Enable Affiliate Links', 'pearblog-engine' ); ?></strong>
								<span class="pearblog-toggle-description"><?php echo esc_html__( 'Automatically insert affiliate links in content', 'pearblog-engine' ); ?></span>
							</span>
						</label>
					</div>

					<div class="pearblog-form-section">
						<label for="pearblog_affiliate_disclosure"><?php echo esc_html__( 'Affiliate Disclosure Text', 'pearblog-engine' ); ?></label>
						<textarea
							name="pearblog_affiliate_disclosure"
							id="pearblog_affiliate_disclosure"
							rows="3"
							class="pearblog-textarea"
						><?php echo esc_textarea( get_option( 'pearblog_affiliate_disclosure', 'This post contains affiliate links. We may earn a commission if you make a purchase.' ) ); ?></textarea>
						<p class="description">
							<?php echo esc_html__( 'Legal disclosure text displayed on posts with affiliate links', 'pearblog-engine' ); ?>
						</p>
					</div>

					<button type="submit" class="pearblog-button pearblog-button-primary">
						<?php echo esc_html__( 'Save Affiliate Settings', 'pearblog-engine' ); ?>
					</button>
				</form>

				<!-- Affiliate Programs -->
				<div class="pearblog-affiliate-programs">
					<h4><?php echo esc_html__( 'Affiliate Programs', 'pearblog-engine' ); ?></h4>
					<?php self::render_affiliate_programs(); ?>
				</div>
			</div>

			<!-- Sponsored Content -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Sponsored Content', 'pearblog-engine' ); ?></h3>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pearblog-monetization-form">
					<input type="hidden" name="action" value="pearblog_save_sponsored" />
					<?php wp_nonce_field( 'pearblog_sponsored_settings', 'pearblog_sponsored_nonce' ); ?>

					<div class="pearblog-form-section">
						<label class="pearblog-toggle-label">
							<input
								type="checkbox"
								name="pearblog_sponsored_posts_enabled"
								value="1"
								<?php checked( $sponsored_enabled ); ?>
							/>
							<span class="pearblog-toggle-text">
								<strong><?php echo esc_html__( 'Enable Sponsored Posts', 'pearblog-engine' ); ?></strong>
								<span class="pearblog-toggle-description"><?php echo esc_html__( 'Mark posts as sponsored and track sponsored revenue', 'pearblog-engine' ); ?></span>
							</span>
						</label>
					</div>

					<div class="pearblog-form-section">
						<label for="pearblog_sponsored_badge"><?php echo esc_html__( 'Sponsored Badge Text', 'pearblog-engine' ); ?></label>
						<input
							type="text"
							name="pearblog_sponsored_badge"
							id="pearblog_sponsored_badge"
							value="<?php echo esc_attr( get_option( 'pearblog_sponsored_badge', 'Sponsored' ) ); ?>"
							class="pearblog-input"
						/>
					</div>

					<button type="submit" class="pearblog-button pearblog-button-primary">
						<?php echo esc_html__( 'Save Sponsored Settings', 'pearblog-engine' ); ?>
					</button>
				</form>
			</div>

			<!-- Revenue Tracking -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Revenue Tracking', 'pearblog-engine' ); ?></h3>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pearblog-monetization-form">
					<input type="hidden" name="action" value="pearblog_save_revenue_tracking" />
					<?php wp_nonce_field( 'pearblog_revenue_tracking', 'pearblog_revenue_nonce' ); ?>

					<div class="pearblog-form-section">
						<label class="pearblog-toggle-label">
							<input
								type="checkbox"
								name="pearblog_v7_revenue_enabled"
								value="1"
								<?php checked( $revenue_tracking ); ?>
							/>
							<span class="pearblog-toggle-text">
								<strong><?php echo esc_html__( 'Enable Per-Article Revenue Tracking', 'pearblog-engine' ); ?></strong>
								<span class="pearblog-toggle-description"><?php echo esc_html__( 'Track revenue generated by each article individually', 'pearblog-engine' ); ?></span>
							</span>
						</label>
					</div>

					<div class="pearblog-info-card">
						<p><strong><?php echo esc_html__( 'Revenue Attribution', 'pearblog-engine' ); ?></strong></p>
						<p><?php echo esc_html__( 'When enabled, revenue from AdSense, affiliate links, and sponsored posts will be attributed to specific articles. This helps identify your most profitable content.', 'pearblog-engine' ); ?></p>
					</div>

					<button type="submit" class="pearblog-button pearblog-button-primary">
						<?php echo esc_html__( 'Save Revenue Tracking', 'pearblog-engine' ); ?>
					</button>
				</form>
			</div>

			<!-- Top Earning Articles -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Top Earning Articles', 'pearblog-engine' ); ?></h3>
				<?php self::render_top_earning_articles(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render revenue statistics.
	 */
	private static function render_revenue_stats(): void {
		// Mock data - would be replaced with real revenue data
		$stats = [
			'today'     => 127.50,
			'week'      => 892.30,
			'month'     => 3456.80,
			'total'     => 15234.60,
		];
		?>
		<div class="pearblog-stats-grid">
			<div class="pearblog-revenue-card">
				<div class="pearblog-revenue-icon">💵</div>
				<div class="pearblog-revenue-amount">$<?php echo esc_html( number_format( $stats['today'], 2 ) ); ?></div>
				<div class="pearblog-revenue-label"><?php echo esc_html__( 'Today', 'pearblog-engine' ); ?></div>
				<div class="pearblog-revenue-change">+12.5%</div>
			</div>
			<div class="pearblog-revenue-card">
				<div class="pearblog-revenue-icon">📊</div>
				<div class="pearblog-revenue-amount">$<?php echo esc_html( number_format( $stats['week'], 2 ) ); ?></div>
				<div class="pearblog-revenue-label"><?php echo esc_html__( 'This Week', 'pearblog-engine' ); ?></div>
				<div class="pearblog-revenue-change">+8.3%</div>
			</div>
			<div class="pearblog-revenue-card">
				<div class="pearblog-revenue-icon">💰</div>
				<div class="pearblog-revenue-amount">$<?php echo esc_html( number_format( $stats['month'], 2 ) ); ?></div>
				<div class="pearblog-revenue-label"><?php echo esc_html__( 'This Month', 'pearblog-engine' ); ?></div>
				<div class="pearblog-revenue-change">+15.7%</div>
			</div>
			<div class="pearblog-revenue-card pearblog-revenue-card-highlight">
				<div class="pearblog-revenue-icon">🎯</div>
				<div class="pearblog-revenue-amount">$<?php echo esc_html( number_format( $stats['total'], 2 ) ); ?></div>
				<div class="pearblog-revenue-label"><?php echo esc_html__( 'All Time', 'pearblog-engine' ); ?></div>
				<div class="pearblog-revenue-change">Growing</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render affiliate programs list.
	 */
	private static function render_affiliate_programs(): void {
		$programs = get_option( 'pearblog_affiliate_programs', [] );
		?>
		<table class="pearblog-table">
			<thead>
				<tr>
					<th><?php echo esc_html__( 'Program', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Commission', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Clicks', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Revenue', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Status', 'pearblog-engine' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $programs ) ) : ?>
					<tr>
						<td colspan="5" class="pearblog-table-empty">
							<?php echo esc_html__( 'No affiliate programs configured. Add your first program to start tracking.', 'pearblog-engine' ); ?>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $programs as $program ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $program['name'] ?? 'Unknown' ); ?></strong></td>
							<td><?php echo esc_html( $program['commission'] ?? '0%' ); ?></td>
							<td><?php echo esc_html( number_format( $program['clicks'] ?? 0 ) ); ?></td>
							<td>$<?php echo esc_html( number_format( $program['revenue'] ?? 0, 2 ) ); ?></td>
							<td>
								<span class="pearblog-status-badge pearblog-status-<?php echo esc_attr( $program['status'] ?? 'active' ); ?>">
									<?php echo esc_html( ucfirst( $program['status'] ?? 'active' ) ); ?>
								</span>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render top earning articles.
	 */
	private static function render_top_earning_articles(): void {
		// Mock data - would be replaced with real top earning posts
		$top_articles = [
			[ 'title' => 'Best WordPress Plugins 2026', 'views' => 15234, 'revenue' => 234.50 ],
			[ 'title' => 'How to Start a Blog in 2026', 'views' => 12456, 'revenue' => 198.30 ],
			[ 'title' => 'SEO Guide for Beginners', 'views' => 10987, 'revenue' => 176.80 ],
			[ 'title' => 'AI Content Generation Tools', 'views' => 9876, 'revenue' => 165.40 ],
			[ 'title' => 'Monetization Strategies 2026', 'views' => 8765, 'revenue' => 145.20 ],
		];
		?>
		<table class="pearblog-table pearblog-earnings-table">
			<thead>
				<tr>
					<th><?php echo esc_html__( 'Article', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Views', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Revenue', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'RPM', 'pearblog-engine' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $top_articles as $article ) : ?>
					<?php
					$rpm = $article['views'] > 0 ? ( $article['revenue'] / $article['views'] ) * 1000 : 0;
					?>
					<tr>
						<td><strong><?php echo esc_html( $article['title'] ); ?></strong></td>
						<td><?php echo esc_html( number_format( $article['views'] ) ); ?></td>
						<td class="pearblog-revenue-cell">$<?php echo esc_html( number_format( $article['revenue'], 2 ) ); ?></td>
						<td>$<?php echo esc_html( number_format( $rpm, 2 ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Handle AdSense settings form submission.
	 */
	public static function handle_save_adsense(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_adsense_settings', 'pearblog_adsense_nonce' );

		$adsense_enabled  = isset( $_POST['pearblog_adsense_enabled'] ) ? 1 : 0;
		$publisher_id     = sanitize_text_field( $_POST['pearblog_adsense_publisher_id'] ?? '' );
		$placements       = isset( $_POST['pearblog_adsense_placements'] ) && is_array( $_POST['pearblog_adsense_placements'] )
			? array_map( 'sanitize_key', $_POST['pearblog_adsense_placements'] )
			: [];
		$strategy         = sanitize_key( $_POST['pearblog_adsense_strategy'] ?? 'balanced' );

		update_option( 'pearblog_adsense_enabled', $adsense_enabled );
		update_option( 'pearblog_adsense_publisher_id', $publisher_id );
		update_option( 'pearblog_adsense_strategy', $strategy );

		// Update individual placement settings
		$all_placements = [ 'header', 'in_content', 'sidebar', 'footer', 'between_posts', 'sticky_mobile' ];
		foreach ( $all_placements as $placement ) {
			$enabled = in_array( $placement, $placements, true ) ? 1 : 0;
			update_option( "pearblog_adsense_enable_{$placement}", $enabled );
		}

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'pearblog-engine-v7',
					'tab'     => 'monetization',
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle affiliate settings form submission.
	 */
	public static function handle_save_affiliate(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_affiliate_settings', 'pearblog_affiliate_nonce' );

		$affiliate_enabled   = isset( $_POST['pearblog_affiliate_enabled'] ) ? 1 : 0;
		$affiliate_disclosure = sanitize_textarea_field( $_POST['pearblog_affiliate_disclosure'] ?? '' );

		update_option( 'pearblog_affiliate_enabled', $affiliate_enabled );
		update_option( 'pearblog_affiliate_disclosure', $affiliate_disclosure );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'pearblog-engine-v7',
					'tab'     => 'monetization',
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle sponsored content settings form submission.
	 */
	public static function handle_save_sponsored(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_sponsored_settings', 'pearblog_sponsored_nonce' );

		$sponsored_enabled = isset( $_POST['pearblog_sponsored_posts_enabled'] ) ? 1 : 0;
		$sponsored_badge   = sanitize_text_field( $_POST['pearblog_sponsored_badge'] ?? 'Sponsored' );

		update_option( 'pearblog_sponsored_posts_enabled', $sponsored_enabled );
		update_option( 'pearblog_sponsored_badge', $sponsored_badge );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'pearblog-engine-v7',
					'tab'     => 'monetization',
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle revenue tracking settings form submission.
	 */
	public static function handle_save_revenue_tracking(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_revenue_tracking', 'pearblog_revenue_nonce' );

		$revenue_enabled = isset( $_POST['pearblog_v7_revenue_enabled'] ) ? 1 : 0;
		update_option( 'pearblog_v7_revenue_enabled', $revenue_enabled );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'pearblog-engine-v7',
					'tab'     => 'monetization',
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}

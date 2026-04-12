<?php
/**
 * Onboarding wizard – guides new users through the initial plugin setup.
 *
 * Displayed automatically on first activation (when no API key is configured).
 * Users can skip the wizard and configure settings manually.
 *
 * @package PearBlogEngine\Admin
 */

declare(strict_types=1);

namespace PearBlogEngine\Admin;

/**
 * Step-by-step setup wizard for first-time PearBlog Engine users.
 */
class OnboardingWizard {

	/** The option key used to mark the wizard as completed. */
	public const OPTION_COMPLETED = 'pearblog_onboarding_complete';

	/** Query parameter to force display or skip the wizard. */
	private const QUERY_PARAM = 'pearblog_wizard';

	/** Admin page slug. */
	private const PAGE_SLUG = 'pearblog-wizard';

	/** Total number of steps. */
	private const TOTAL_STEPS = 4;

	/**
	 * Register WordPress hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_wizard_page' ] );
		add_action( 'admin_init', [ $this, 'maybe_redirect_to_wizard' ] );
		add_action( 'admin_post_pearblog_wizard_step', [ $this, 'handle_step_submission' ] );
	}

	// ------------------------------------------------------------------
	// Routing
	// ------------------------------------------------------------------

	/**
	 * Register the hidden wizard admin page.
	 */
	public function add_wizard_page(): void {
		add_submenu_page(
			null, // hidden (no parent menu).
			__( 'PearBlog Engine Setup', 'pearblog-engine' ),
			__( 'Setup Wizard', 'pearblog-engine' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render_wizard' ]
		);
	}

	/**
	 * Redirect admins to the wizard on first activation.
	 * Only fires once, can be suppressed with ?pearblog_wizard=skip.
	 */
	public function maybe_redirect_to_wizard(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Never redirect during AJAX, CLI, or if already on the wizard page.
		if ( defined( 'DOING_AJAX' ) || defined( 'WP_CLI' ) ) {
			return;
		}

		if ( isset( $_GET[ self::QUERY_PARAM ] ) && 'skip' === $_GET[ self::QUERY_PARAM ] ) {
			update_option( self::OPTION_COMPLETED, true );
			return;
		}

		// Already completed.
		if ( get_option( self::OPTION_COMPLETED, false ) ) {
			return;
		}

		// API key already configured – skip wizard.
		if ( ! empty( get_option( 'pearblog_openai_api_key', '' ) ) ) {
			update_option( self::OPTION_COMPLETED, true );
			return;
		}

		// Avoid infinite redirect loop.
		$current_page = $_GET['page'] ?? '';
		if ( self::PAGE_SLUG === $current_page ) {
			return;
		}

		// Only redirect from the main WP admin screen.
		global $pagenow;
		if ( 'index.php' === $pagenow ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
			exit;
		}
	}

	// ------------------------------------------------------------------
	// Step form handling
	// ------------------------------------------------------------------

	/**
	 * Handle a wizard step form submission.
	 */
	public function handle_step_submission(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_wizard_step' );

		$step = absint( $_POST['wizard_step'] ?? 1 );

		switch ( $step ) {
			case 1:
				$api_key = sanitize_text_field( wp_unslash( $_POST['openai_api_key'] ?? '' ) );
				if ( '' !== $api_key ) {
					update_option( 'pearblog_openai_api_key', $api_key );
				}
				break;

			case 2:
				update_option( 'pearblog_industry', sanitize_text_field( wp_unslash( $_POST['industry'] ?? 'general' ) ) );
				update_option( 'pearblog_language', sanitize_text_field( wp_unslash( $_POST['language'] ?? 'en' ) ) );
				update_option( 'pearblog_tone', sanitize_text_field( wp_unslash( $_POST['tone'] ?? 'neutral' ) ) );
				break;

			case 3:
				update_option( 'pearblog_publish_rate', absint( $_POST['publish_rate'] ?? 1 ) );
				update_option( 'pearblog_monetization', sanitize_text_field( wp_unslash( $_POST['monetization'] ?? 'adsense' ) ) );

				// Seed sample topics if provided.
				$raw_topics = sanitize_textarea_field( wp_unslash( $_POST['sample_topics'] ?? '' ) );
				$topics     = array_filter( array_map( 'trim', explode( "\n", $raw_topics ) ) );
				if ( ! empty( $topics ) ) {
					( new \PearBlogEngine\Content\TopicQueue( get_current_blog_id() ) )->push( ...$topics );
				}
				break;

			case 4:
				// Final step – enable autonomous mode if requested.
				$autonomous = absint( $_POST['autonomous_mode'] ?? 0 );
				update_option( 'pearblog_autonomous_mode', $autonomous );
				update_option( self::OPTION_COMPLETED, true );

				wp_safe_redirect(
					add_query_arg(
						[ 'page' => 'pearblog-engine', 'pearblog_notice' => rawurlencode( __( 'Setup complete! PearBlog Engine is ready.', 'pearblog-engine' ) ), 'pearblog_type' => 'success' ],
						admin_url( 'admin.php' )
					)
				);
				exit;
		}

		// Advance to next step.
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&step=' . ( $step + 1 ) ) );
		exit;
	}

	// ------------------------------------------------------------------
	// Rendering
	// ------------------------------------------------------------------

	/**
	 * Render the wizard page.
	 */
	public function render_wizard(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$step = max( 1, min( self::TOTAL_STEPS, absint( $_GET['step'] ?? 1 ) ) );

		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width,initial-scale=1">
			<title><?php esc_html_e( 'PearBlog Engine Setup', 'pearblog-engine' ); ?></title>
			<?php wp_head(); ?>
			<style>
				.pb-wizard-wrap { max-width: 680px; margin: 60px auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
				.pb-wizard-header { text-align: center; margin-bottom: 32px; }
				.pb-wizard-header h1 { font-size: 2rem; margin: 0 0 .4rem; }
				.pb-wizard-steps { display: flex; justify-content: center; gap: 8px; margin-bottom: 32px; }
				.pb-wizard-step-dot { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .85rem; }
				.pb-wizard-step-dot.active { background: #2271b1; color: #fff; }
				.pb-wizard-step-dot.done { background: #00a32a; color: #fff; }
				.pb-wizard-step-dot.pending { background: #f0f0f1; color: #787c82; }
				.pb-wizard-card { background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; padding: 32px; }
				.pb-wizard-card h2 { margin-top: 0; }
				.pb-wizard-field { margin-bottom: 20px; }
				.pb-wizard-field label { display: block; font-weight: 600; margin-bottom: 6px; }
				.pb-wizard-field input[type="text"],
				.pb-wizard-field input[type="password"],
				.pb-wizard-field select,
				.pb-wizard-field textarea { width: 100%; padding: 8px 10px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 14px; box-sizing: border-box; }
				.pb-wizard-field .description { color: #646970; font-size: 13px; margin-top: 4px; }
				.pb-wizard-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 24px; }
				.pb-wizard-actions a { color: #646970; font-size: 13px; }
				.button-primary { background: #2271b1; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; font-size: 14px; cursor: pointer; }
				.button-primary:hover { background: #135e96; }
				.pb-progress { height: 6px; background: #f0f0f1; border-radius: 3px; margin-bottom: 24px; }
				.pb-progress-bar { height: 6px; background: #2271b1; border-radius: 3px; transition: width .3s; }
			</style>
		</head>
		<body class="wp-core-ui">
		<div class="pb-wizard-wrap">

			<!-- Header -->
			<div class="pb-wizard-header">
				<div style="font-size:3rem;margin-bottom:.5rem;">🍐</div>
				<h1><?php esc_html_e( 'PearBlog Engine Setup', 'pearblog-engine' ); ?></h1>
				<p style="color:#646970;"><?php esc_html_e( 'Let\'s configure your autonomous AI blog in 4 quick steps.', 'pearblog-engine' ); ?></p>
			</div>

			<!-- Step indicators -->
			<div class="pb-wizard-steps" aria-label="<?php esc_attr_e( 'Setup progress', 'pearblog-engine' ); ?>">
				<?php for ( $i = 1; $i <= self::TOTAL_STEPS; $i++ ) : ?>
					<div class="pb-wizard-step-dot <?php echo $i < $step ? 'done' : ( $i === $step ? 'active' : 'pending' ); ?>">
						<?php echo $i < $step ? '✓' : esc_html( $i ); ?>
					</div>
				<?php endfor; ?>
			</div>

			<!-- Progress bar -->
			<div class="pb-progress">
				<div class="pb-progress-bar" style="width:<?php echo esc_attr( round( ( $step - 1 ) / self::TOTAL_STEPS * 100 ) ); ?>%;"></div>
			</div>

			<!-- Step card -->
			<div class="pb-wizard-card">
				<?php $this->render_step( $step ); ?>
			</div>

			<!-- Skip link -->
			<p style="text-align:center;margin-top:16px;color:#646970;font-size:13px;">
				<a href="<?php echo esc_url( add_query_arg( self::QUERY_PARAM, 'skip', admin_url( 'index.php' ) ) ); ?>">
					<?php esc_html_e( 'Skip wizard and configure manually', 'pearblog-engine' ); ?>
				</a>
			</p>

		</div>
		<?php wp_footer(); ?>
		</body>
		</html>
		<?php
	}

	// ------------------------------------------------------------------
	// Individual step renderers
	// ------------------------------------------------------------------

	/**
	 * Dispatch to the correct step renderer.
	 */
	private function render_step( int $step ): void {
		match ( $step ) {
			1 => $this->render_step_api_key(),
			2 => $this->render_step_niche(),
			3 => $this->render_step_content(),
			4 => $this->render_step_launch(),
			default => $this->render_step_api_key(),
		};
	}

	/** Step 1: OpenAI API Key */
	private function render_step_api_key(): void {
		?>
		<h2>🔑 <?php esc_html_e( 'Step 1: OpenAI API Key', 'pearblog-engine' ); ?></h2>
		<p><?php esc_html_e( 'PearBlog Engine uses OpenAI to generate high-quality articles. Enter your API key to get started.', 'pearblog-engine' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'pearblog_wizard_step' ); ?>
			<input type="hidden" name="action" value="pearblog_wizard_step">
			<input type="hidden" name="wizard_step" value="1">
			<div class="pb-wizard-field">
				<label for="openai_api_key"><?php esc_html_e( 'OpenAI API Key', 'pearblog-engine' ); ?></label>
				<input type="password" id="openai_api_key" name="openai_api_key"
					value="<?php echo esc_attr( get_option( 'pearblog_openai_api_key', '' ) ); ?>"
					placeholder="sk-..." autocomplete="off" required>
				<p class="description">
					<?php esc_html_e( 'Get your key at platform.openai.com. We recommend setting a monthly usage limit.', 'pearblog-engine' ); ?>
				</p>
			</div>
			<div class="pb-wizard-actions">
				<span></span>
				<button type="submit" class="button-primary">
					<?php esc_html_e( 'Next: Content Settings →', 'pearblog-engine' ); ?>
				</button>
			</div>
		</form>
		<?php
	}

	/** Step 2: Niche & Language */
	private function render_step_niche(): void {
		$tones = [ 'neutral', 'professional', 'conversational', 'authoritative', 'friendly' ];
		?>
		<h2>🏷️ <?php esc_html_e( 'Step 2: Your Niche & Language', 'pearblog-engine' ); ?></h2>
		<p><?php esc_html_e( 'Tell the AI what your blog is about so every article fits your audience.', 'pearblog-engine' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'pearblog_wizard_step' ); ?>
			<input type="hidden" name="action" value="pearblog_wizard_step">
			<input type="hidden" name="wizard_step" value="2">
			<div class="pb-wizard-field">
				<label for="industry"><?php esc_html_e( 'Industry / Niche', 'pearblog-engine' ); ?></label>
				<input type="text" id="industry" name="industry"
					value="<?php echo esc_attr( get_option( 'pearblog_industry', '' ) ); ?>"
					placeholder="<?php esc_attr_e( 'e.g. travel, health, finance, technology', 'pearblog-engine' ); ?>" required>
			</div>
			<div class="pb-wizard-field">
				<label for="language"><?php esc_html_e( 'Content Language (ISO 639-1)', 'pearblog-engine' ); ?></label>
				<input type="text" id="language" name="language"
					value="<?php echo esc_attr( get_option( 'pearblog_language', 'en' ) ); ?>"
					placeholder="en" maxlength="5" style="width:80px;">
				<p class="description"><?php esc_html_e( 'e.g. en, pl, de, fr, es', 'pearblog-engine' ); ?></p>
			</div>
			<div class="pb-wizard-field">
				<label for="tone"><?php esc_html_e( 'Writing Tone', 'pearblog-engine' ); ?></label>
				<select id="tone" name="tone">
					<?php foreach ( $tones as $t ) : ?>
						<option value="<?php echo esc_attr( $t ); ?>" <?php selected( get_option( 'pearblog_tone', 'neutral' ), $t ); ?>>
							<?php echo esc_html( ucfirst( $t ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="pb-wizard-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&step=1' ) ); ?>">← <?php esc_html_e( 'Back', 'pearblog-engine' ); ?></a>
				<button type="submit" class="button-primary">
					<?php esc_html_e( 'Next: Content Plan →', 'pearblog-engine' ); ?>
				</button>
			</div>
		</form>
		<?php
	}

	/** Step 3: Publish rate, monetisation, seed topics */
	private function render_step_content(): void {
		$sample_topics = implode( "\n", [
			get_option( 'pearblog_industry', 'general' ) . ' beginner guide',
			'Best tips for ' . get_option( 'pearblog_industry', 'your niche' ),
			'How to get started with ' . get_option( 'pearblog_industry', 'your topic' ),
		] );
		?>
		<h2>📋 <?php esc_html_e( 'Step 3: Content Plan', 'pearblog-engine' ); ?></h2>
		<p><?php esc_html_e( 'Configure how many articles to publish and add your first topics.', 'pearblog-engine' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'pearblog_wizard_step' ); ?>
			<input type="hidden" name="action" value="pearblog_wizard_step">
			<input type="hidden" name="wizard_step" value="3">
			<div class="pb-wizard-field">
				<label for="publish_rate"><?php esc_html_e( 'Articles per pipeline cycle', 'pearblog-engine' ); ?></label>
				<input type="number" id="publish_rate" name="publish_rate"
					value="<?php echo esc_attr( get_option( 'pearblog_publish_rate', 1 ) ); ?>"
					min="1" max="50" style="width:80px;">
				<p class="description"><?php esc_html_e( 'How many articles to generate each time the cron job runs (default: 1).', 'pearblog-engine' ); ?></p>
			</div>
			<div class="pb-wizard-field">
				<label for="monetization"><?php esc_html_e( 'Monetisation Strategy', 'pearblog-engine' ); ?></label>
				<select id="monetization" name="monetization">
					<option value="adsense" <?php selected( get_option( 'pearblog_monetization', 'adsense' ), 'adsense' ); ?>><?php esc_html_e( 'AdSense', 'pearblog-engine' ); ?></option>
					<option value="affiliate" <?php selected( get_option( 'pearblog_monetization', 'adsense' ), 'affiliate' ); ?>><?php esc_html_e( 'Affiliate Marketing', 'pearblog-engine' ); ?></option>
					<option value="saas" <?php selected( get_option( 'pearblog_monetization', 'adsense' ), 'saas' ); ?>><?php esc_html_e( 'SaaS / Info Product', 'pearblog-engine' ); ?></option>
				</select>
			</div>
			<div class="pb-wizard-field">
				<label for="sample_topics"><?php esc_html_e( 'Starter Topics (one per line)', 'pearblog-engine' ); ?></label>
				<textarea id="sample_topics" name="sample_topics" rows="5"
					placeholder="<?php esc_attr_e( 'Enter one topic per line…', 'pearblog-engine' ); ?>"><?php echo esc_textarea( $sample_topics ); ?></textarea>
				<p class="description"><?php esc_html_e( 'These topics will be added to the article queue. You can add more later.', 'pearblog-engine' ); ?></p>
			</div>
			<div class="pb-wizard-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&step=2' ) ); ?>">← <?php esc_html_e( 'Back', 'pearblog-engine' ); ?></a>
				<button type="submit" class="button-primary">
					<?php esc_html_e( 'Next: Launch →', 'pearblog-engine' ); ?>
				</button>
			</div>
		</form>
		<?php
	}

	/** Step 4: Final review + enable autonomous mode */
	private function render_step_launch(): void {
		$api_ok    = ! empty( get_option( 'pearblog_openai_api_key', '' ) );
		$industry  = get_option( 'pearblog_industry', '—' );
		$language  = get_option( 'pearblog_language', 'en' );
		$rate      = get_option( 'pearblog_publish_rate', 1 );
		?>
		<h2>🚀 <?php esc_html_e( 'Step 4: Launch!', 'pearblog-engine' ); ?></h2>
		<p><?php esc_html_e( 'Review your settings and activate autonomous mode.', 'pearblog-engine' ); ?></p>

		<table class="widefat" style="margin-bottom:20px;">
			<tbody>
				<tr>
					<th style="width:40%;"><?php esc_html_e( 'OpenAI API Key', 'pearblog-engine' ); ?></th>
					<td><?php echo $api_ok ? '<span style="color:#00a32a;">✓ ' . esc_html__( 'Configured', 'pearblog-engine' ) . '</span>' : '<span style="color:#d63638;">✗ ' . esc_html__( 'Not set', 'pearblog-engine' ) . '</span>'; ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Industry', 'pearblog-engine' ); ?></th>
					<td><?php echo esc_html( $industry ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Language', 'pearblog-engine' ); ?></th>
					<td><?php echo esc_html( $language ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Articles / Cycle', 'pearblog-engine' ); ?></th>
					<td><?php echo esc_html( $rate ); ?></td>
				</tr>
			</tbody>
		</table>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'pearblog_wizard_step' ); ?>
			<input type="hidden" name="action" value="pearblog_wizard_step">
			<input type="hidden" name="wizard_step" value="4">
			<div class="pb-wizard-field">
				<label>
					<input type="checkbox" name="autonomous_mode" value="1" checked>
					<?php esc_html_e( 'Enable autonomous mode (recommended)', 'pearblog-engine' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'The pipeline will run automatically every hour and publish articles from your queue.', 'pearblog-engine' ); ?></p>
			</div>
			<div class="pb-wizard-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&step=3' ) ); ?>">← <?php esc_html_e( 'Back', 'pearblog-engine' ); ?></a>
				<button type="submit" class="button-primary">
					🚀 <?php esc_html_e( 'Complete Setup', 'pearblog-engine' ); ?>
				</button>
			</div>
		</form>
		<?php
	}
}

<?php
/**
 * Onboarding Wizard v2 - Interactive setup experience for new users.
 *
 * Guides users through essential configuration steps with progress tracking,
 * contextual help, and validation. Replaces the old linear onboarding flow
 * with a modern, step-based wizard.
 *
 * Steps:
 * 1. Welcome & Overview
 * 2. OpenAI API Configuration
 * 3. Content Strategy Setup
 * 4. First Topic Queue
 * 5. Publishing Schedule
 * 6. Monetization (Optional)
 * 7. Complete & Launch
 *
 * @package PearBlogEngine\Admin
 */

declare(strict_types=1);

namespace PearBlogEngine\Admin;

use PearBlogEngine\AI\AIClient;
use PearBlogEngine\Content\TopicQueue;

/**
 * Onboarding Wizard v2 controller.
 */
class OnboardingWizardV2 {

	/**
	 * Total number of steps in wizard.
	 */
	private const TOTAL_STEPS = 7;

	/**
	 * Option key for wizard completion status.
	 */
	private const WIZARD_COMPLETE_OPTION = 'pearblog_onboarding_v2_complete';

	/**
	 * Option key for current step.
	 */
	private const CURRENT_STEP_OPTION = 'pearblog_onboarding_v2_current_step';

	/**
	 * Register wizard hooks and routes.
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_wizard_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_wizard_assets' ] );
		add_action( 'wp_ajax_pearblog_wizard_save_step', [ $this, 'ajax_save_step' ] );
		add_action( 'wp_ajax_pearblog_wizard_test_api_key', [ $this, 'ajax_test_api_key' ] );
		add_action( 'wp_ajax_pearblog_wizard_generate_topics', [ $this, 'ajax_generate_topics' ] );
		add_action( 'wp_ajax_pearblog_wizard_skip', [ $this, 'ajax_skip_wizard' ] );
	}

	/**
	 * Add wizard page to admin menu.
	 */
	public function add_wizard_page(): void {
		// Only show wizard if not completed
		if ( $this->is_wizard_complete() ) {
			return;
		}

		add_menu_page(
			__( 'PearBlog Setup Wizard', 'pearblog-engine' ),
			__( 'Setup Wizard', 'pearblog-engine' ),
			'manage_options',
			'pearblog-wizard-v2',
			[ $this, 'render_wizard' ],
			'dashicons-welcome-learn-more',
			3 // High priority - show near top
		);
	}

	/**
	 * Enqueue wizard JavaScript and CSS.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_wizard_assets( string $hook ): void {
		if ( 'toplevel_page_pearblog-wizard-v2' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'pearblog-wizard-v2',
			plugins_url( 'assets/css/wizard-v2.css', dirname( __DIR__, 2 ) ),
			[],
			'7.10.0'
		);

		wp_enqueue_script(
			'pearblog-wizard-v2',
			plugins_url( 'assets/js/wizard-v2.js', dirname( __DIR__, 2 ) ),
			[ 'jquery' ],
			'7.10.0',
			true
		);

		wp_localize_script(
			'pearblog-wizard-v2',
			'PearBlogWizard',
			[
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'pearblog_wizard_nonce' ),
				'currentStep' => $this->get_current_step(),
				'totalSteps' => self::TOTAL_STEPS,
			]
		);
	}

	/**
	 * Render the wizard interface.
	 */
	public function render_wizard(): void {
		$current_step = $this->get_current_step();
		?>
		<div class="pearblog-wizard-v2">
			<div class="wizard-header">
				<h1><?php esc_html_e( 'Welcome to PearBlog Engine v7', 'pearblog-engine' ); ?></h1>
				<p class="wizard-subtitle"><?php esc_html_e( 'Let\'s get your AI-powered content system up and running in just a few minutes.', 'pearblog-engine' ); ?></p>
				<?php $this->render_progress_bar( $current_step ); ?>
			</div>

			<div class="wizard-body">
				<div class="wizard-content">
					<?php $this->render_step( $current_step ); ?>
				</div>

				<div class="wizard-sidebar">
					<?php $this->render_help_panel( $current_step ); ?>
				</div>
			</div>

			<div class="wizard-footer">
				<?php $this->render_navigation( $current_step ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render progress bar.
	 *
	 * @param int $current_step Current step number (1-based).
	 */
	private function render_progress_bar( int $current_step ): void {
		$progress_pct = ( ( $current_step - 1 ) / self::TOTAL_STEPS ) * 100;
		?>
		<div class="wizard-progress">
			<div class="progress-bar">
				<div class="progress-fill" style="width: <?php echo esc_attr( $progress_pct ); ?>%;"></div>
			</div>
			<div class="progress-label">
				<?php
				printf(
					/* translators: %1$d: Current step, %2$d: Total steps */
					esc_html__( 'Step %1$d of %2$d', 'pearblog-engine' ),
					(int) $current_step,
					(int) self::TOTAL_STEPS
				);
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render specific wizard step.
	 *
	 * @param int $step Step number (1-based).
	 */
	private function render_step( int $step ): void {
		switch ( $step ) {
			case 1:
				$this->render_step_welcome();
				break;
			case 2:
				$this->render_step_api_config();
				break;
			case 3:
				$this->render_step_strategy();
				break;
			case 4:
				$this->render_step_topics();
				break;
			case 5:
				$this->render_step_schedule();
				break;
			case 6:
				$this->render_step_monetization();
				break;
			case 7:
				$this->render_step_complete();
				break;
			default:
				$this->render_step_welcome();
		}
	}

	/**
	 * Step 1: Welcome & Overview.
	 */
	private function render_step_welcome(): void {
		?>
		<div class="wizard-step wizard-step-welcome">
			<div class="step-icon">🚀</div>
			<h2><?php esc_html_e( 'Welcome to PearBlog Engine!', 'pearblog-engine' ); ?></h2>
			<p class="lead"><?php esc_html_e( 'You\'re about to set up the most advanced AI-powered content generation system for WordPress.', 'pearblog-engine' ); ?></p>

			<div class="feature-grid">
				<div class="feature-card">
					<div class="feature-icon">✍️</div>
					<h3><?php esc_html_e( 'AI Content Generation', 'pearblog-engine' ); ?></h3>
					<p><?php esc_html_e( 'GPT-4 powered articles optimized for SEO and engagement.', 'pearblog-engine' ); ?></p>
				</div>

				<div class="feature-card">
					<div class="feature-icon">💰</div>
					<h3><?php esc_html_e( 'Funnel-Aware Monetization', 'pearblog-engine' ); ?></h3>
					<p><?php esc_html_e( 'Smart ad placement based on reader intent (TOFU/MOFU/BOFU).', 'pearblog-engine' ); ?></p>
				</div>

				<div class="feature-card">
					<div class="feature-icon">🔍</div>
					<h3><?php esc_html_e( 'SEO Automation', 'pearblog-engine' ); ?></h3>
					<p><?php esc_html_e( 'Auto-optimized titles, meta descriptions, and internal linking.', 'pearblog-engine' ); ?></p>
				</div>

				<div class="feature-card">
					<div class="feature-icon">⚙️</div>
					<h3><?php esc_html_e( 'Set & Forget Automation', 'pearblog-engine' ); ?></h3>
					<p><?php esc_html_e( 'Scheduled publishing, queue management, and performance tracking.', 'pearblog-engine' ); ?></p>
				</div>
			</div>

			<div class="wizard-note">
				<strong><?php esc_html_e( 'Time Required:', 'pearblog-engine' ); ?></strong>
				<?php esc_html_e( 'This wizard takes about 5 minutes to complete. You can skip steps and return later.', 'pearblog-engine' ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Step 2: OpenAI API Configuration.
	 */
	private function render_step_api_config(): void {
		$api_key = (string) get_option( 'pearblog_openai_api_key', '' );
		?>
		<div class="wizard-step wizard-step-api">
			<div class="step-icon">🔑</div>
			<h2><?php esc_html_e( 'Connect Your OpenAI Account', 'pearblog-engine' ); ?></h2>
			<p class="lead"><?php esc_html_e( 'PearBlog Engine uses OpenAI\'s GPT-4 to generate high-quality content. You\'ll need an API key to get started.', 'pearblog-engine' ); ?></p>

			<div class="form-section">
				<label for="openai-api-key">
					<?php esc_html_e( 'OpenAI API Key', 'pearblog-engine' ); ?>
					<span class="required">*</span>
				</label>
				<input
					type="password"
					id="openai-api-key"
					name="openai_api_key"
					class="large-text"
					value="<?php echo esc_attr( $api_key ); ?>"
					placeholder="sk-..."
					required
				/>
				<button type="button" id="test-api-key" class="button button-secondary">
					<?php esc_html_e( 'Test Connection', 'pearblog-engine' ); ?>
				</button>
				<div id="api-test-result"></div>
				<p class="description">
					<?php esc_html_e( 'Your API key is securely stored and never shared with third parties.', 'pearblog-engine' ); ?>
				</p>
			</div>

			<div class="wizard-callout wizard-callout-info">
				<h4><?php esc_html_e( 'Don\'t have an OpenAI account yet?', 'pearblog-engine' ); ?></h4>
				<ol>
					<li><?php esc_html_e( 'Visit', 'pearblog-engine' ); ?> <a href="https://platform.openai.com/signup" target="_blank">platform.openai.com/signup</a></li>
					<li><?php esc_html_e( 'Sign up for a free account (get $5 credit)', 'pearblog-engine' ); ?></li>
					<li><?php esc_html_e( 'Go to', 'pearblog-engine' ); ?> <strong><?php esc_html_e( 'API Keys', 'pearblog-engine' ); ?></strong> <?php esc_html_e( 'section', 'pearblog-engine' ); ?></li>
					<li><?php esc_html_e( 'Click', 'pearblog-engine' ); ?> <strong><?php esc_html_e( 'Create new secret key', 'pearblog-engine' ); ?></strong></li>
					<li><?php esc_html_e( 'Copy and paste the key above', 'pearblog-engine' ); ?></li>
				</ol>
			</div>

			<div class="form-section">
				<label for="openai-model"><?php esc_html_e( 'Model Selection', 'pearblog-engine' ); ?></label>
				<select id="openai-model" name="openai_model">
					<option value="gpt-4" <?php selected( get_option( 'pearblog_openai_model', 'gpt-4' ), 'gpt-4' ); ?>>
						<?php esc_html_e( 'GPT-4 (Best Quality, Higher Cost)', 'pearblog-engine' ); ?>
					</option>
					<option value="gpt-4-turbo" <?php selected( get_option( 'pearblog_openai_model' ), 'gpt-4-turbo' ); ?>>
						<?php esc_html_e( 'GPT-4 Turbo (Balanced)', 'pearblog-engine' ); ?>
					</option>
					<option value="gpt-3.5-turbo" <?php selected( get_option( 'pearblog_openai_model' ), 'gpt-3.5-turbo' ); ?>>
						<?php esc_html_e( 'GPT-3.5 Turbo (Fastest, Lowest Cost)', 'pearblog-engine' ); ?>
					</option>
				</select>
				<p class="description">
					<?php esc_html_e( 'Recommended: GPT-4 Turbo for production. GPT-3.5 for testing.', 'pearblog-engine' ); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Step 3: Content Strategy Setup.
	 */
	private function render_step_strategy(): void {
		$niche    = (string) get_option( 'pearblog_target_niche', '' );
		$audience = (string) get_option( 'pearblog_target_audience', '' );
		$keywords = (array) get_option( 'pearblog_topic_keywords', [] );
		?>
		<div class="wizard-step wizard-step-strategy">
			<div class="step-icon">🧠</div>
			<h2><?php esc_html_e( 'Define Your Content Strategy', 'pearblog-engine' ); ?></h2>
			<p class="lead"><?php esc_html_e( 'Tell the AI about your niche, audience, and topics. The more specific you are, the better the content quality.', 'pearblog-engine' ); ?></p>

			<div class="form-section">
				<label for="target-niche">
					<?php esc_html_e( 'Target Niche', 'pearblog-engine' ); ?>
					<span class="required">*</span>
				</label>
				<input
					type="text"
					id="target-niche"
					name="target_niche"
					class="large-text"
					value="<?php echo esc_attr( $niche ); ?>"
					placeholder="<?php esc_attr_e( 'e.g., Project management software for remote teams', 'pearblog-engine' ); ?>"
					required
				/>
				<p class="description">
					<?php esc_html_e( 'What industry or topic area will you write about?', 'pearblog-engine' ); ?>
				</p>
			</div>

			<div class="form-section">
				<label for="target-audience">
					<?php esc_html_e( 'Target Audience', 'pearblog-engine' ); ?>
					<span class="required">*</span>
				</label>
				<textarea
					id="target-audience"
					name="target_audience"
					class="large-text"
					rows="4"
					placeholder="<?php esc_attr_e( 'e.g., Remote team managers, project coordinators, and productivity enthusiasts at companies with 10-100 employees', 'pearblog-engine' ); ?>"
					required
				><?php echo esc_textarea( $audience ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'Describe your ideal reader: who they are, what they do, what challenges they face.', 'pearblog-engine' ); ?>
				</p>
			</div>

			<div class="form-section">
				<label for="topic-keywords">
					<?php esc_html_e( 'Topic Keywords', 'pearblog-engine' ); ?>
					<span class="required">*</span>
				</label>
				<div id="keyword-list" class="keyword-list">
					<?php
					if ( ! empty( $keywords ) ) {
						foreach ( $keywords as $keyword ) {
							echo '<span class="keyword-tag">' . esc_html( $keyword ) . ' <button type="button" class="remove-keyword">×</button></span>';
						}
					}
					?>
				</div>
				<div class="keyword-input-wrapper">
					<input
						type="text"
						id="keyword-input"
						placeholder="<?php esc_attr_e( 'Enter a keyword and press Enter', 'pearblog-engine' ); ?>"
					/>
					<button type="button" id="add-keyword" class="button button-secondary">
						<?php esc_html_e( 'Add Keyword', 'pearblog-engine' ); ?>
					</button>
				</div>
				<p class="description">
					<?php esc_html_e( 'Add 5-10 keywords that represent your core topics. These will guide content generation.', 'pearblog-engine' ); ?>
				</p>
			</div>

			<div class="wizard-callout wizard-callout-tip">
				<h4><?php esc_html_e( '💡 Pro Tip: Be Specific', 'pearblog-engine' ); ?></h4>
				<p>
					<?php esc_html_e( 'Instead of "marketing", use "email marketing for SaaS startups". Specific niches generate higher quality content.', 'pearblog-engine' ); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Step 4: First Topic Queue.
	 */
	private function render_step_topics(): void {
		?>
		<div class="wizard-step wizard-step-topics">
			<div class="step-icon">📝</div>
			<h2><?php esc_html_e( 'Add Your First Topics', 'pearblog-engine' ); ?></h2>
			<p class="lead"><?php esc_html_e( 'Let\'s add some topics to your content queue. You can type them manually or let AI suggest ideas based on your niche.', 'pearblog-engine' ); ?></p>

			<div class="topic-actions">
				<button type="button" id="generate-topics-btn" class="button button-primary">
					<?php esc_html_e( '✨ Generate Topic Ideas with AI', 'pearblog-engine' ); ?>
				</button>
			</div>

			<div id="suggested-topics" class="suggested-topics" style="display: none;">
				<h3><?php esc_html_e( 'Suggested Topics', 'pearblog-engine' ); ?></h3>
				<p><?php esc_html_e( 'Click to add topics to your queue:', 'pearblog-engine' ); ?></p>
				<div id="suggested-topics-list"></div>
			</div>

			<div class="form-section">
				<label><?php esc_html_e( 'Your Topic Queue', 'pearblog-engine' ); ?></label>
				<div id="topic-queue-list" class="topic-queue-list">
					<p class="empty-state"><?php esc_html_e( 'No topics in queue yet. Add topics above.', 'pearblog-engine' ); ?></p>
				</div>
			</div>

			<div class="form-section">
				<label for="manual-topic"><?php esc_html_e( 'Or add manually:', 'pearblog-engine' ); ?></label>
				<div class="topic-input-wrapper">
					<input
						type="text"
						id="manual-topic"
						placeholder="<?php esc_attr_e( 'e.g., How to Deploy Docker Containers in Production', 'pearblog-engine' ); ?>"
						class="large-text"
					/>
					<button type="button" id="add-topic-btn" class="button button-secondary">
						<?php esc_html_e( 'Add Topic', 'pearblog-engine' ); ?>
					</button>
				</div>
			</div>

			<div class="wizard-note">
				<?php esc_html_e( 'Minimum 1 topic required to continue. Add 3-5 topics to get started.', 'pearblog-engine' ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Step 5: Publishing Schedule.
	 */
	private function render_step_schedule(): void {
		?>
		<div class="wizard-step wizard-step-schedule">
			<div class="step-icon">⏰</div>
			<h2><?php esc_html_e( 'Set Your Publishing Schedule', 'pearblog-engine' ); ?></h2>
			<p class="lead"><?php esc_html_e( 'Decide when and how often articles should be published. You can change this later in settings.', 'pearblog-engine' ); ?></p>

			<div class="form-section">
				<label for="auto-publish-enabled">
					<input
						type="checkbox"
						id="auto-publish-enabled"
						name="auto_publish_enabled"
						value="1"
						<?php checked( get_option( 'pearblog_auto_publish_enabled', true ) ); ?>
					/>
					<?php esc_html_e( 'Enable Automatic Publishing', 'pearblog-engine' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'When enabled, articles will be published automatically on schedule. When disabled, they\'ll be saved as drafts for manual review.', 'pearblog-engine' ); ?>
				</p>
			</div>

			<div class="form-section">
				<label for="articles-per-day"><?php esc_html_e( 'Articles Per Day', 'pearblog-engine' ); ?></label>
				<select id="articles-per-day" name="articles_per_day">
					<option value="1" <?php selected( get_option( 'pearblog_articles_per_day', 1 ), 1 ); ?>>1 <?php esc_html_e( 'article per day', 'pearblog-engine' ); ?></option>
					<option value="2" <?php selected( get_option( 'pearblog_articles_per_day' ), 2 ); ?>>2 <?php esc_html_e( 'articles per day', 'pearblog-engine' ); ?></option>
					<option value="3" <?php selected( get_option( 'pearblog_articles_per_day' ), 3 ); ?>>3 <?php esc_html_e( 'articles per day', 'pearblog-engine' ); ?></option>
					<option value="5" <?php selected( get_option( 'pearblog_articles_per_day' ), 5 ); ?>>5 <?php esc_html_e( 'articles per day', 'pearblog-engine' ); ?></option>
				</select>
				<p class="description">
					<?php esc_html_e( 'Recommended: Start with 1 per day. Increase as your queue grows.', 'pearblog-engine' ); ?>
				</p>
			</div>

			<div class="form-section">
				<label for="publish-time"><?php esc_html_e( 'Preferred Publishing Time', 'pearblog-engine' ); ?></label>
				<input
					type="time"
					id="publish-time"
					name="publish_time"
					value="<?php echo esc_attr( get_option( 'pearblog_publish_time', '10:00' ) ); ?>"
				/>
				<p class="description">
					<?php esc_html_e( 'What time of day should articles be published? (Site local time)', 'pearblog-engine' ); ?>
				</p>
			</div>

			<div class="wizard-callout wizard-callout-info">
				<h4><?php esc_html_e( 'How does scheduling work?', 'pearblog-engine' ); ?></h4>
				<p>
					<?php esc_html_e( 'PearBlog Engine uses WordPress Cron to run the content pipeline automatically. Articles are generated from your topic queue and published at your specified time.', 'pearblog-engine' ); ?>
				</p>
				<p>
					<strong><?php esc_html_e( 'For production sites:', 'pearblog-engine' ); ?></strong>
					<?php esc_html_e( 'Replace WP-Cron with a system cron job for better reliability.', 'pearblog-engine' ); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Step 6: Monetization (Optional).
	 */
	private function render_step_monetization(): void {
		?>
		<div class="wizard-step wizard-step-monetization">
			<div class="step-icon">💰</div>
			<h2><?php esc_html_e( 'Monetization (Optional)', 'pearblog-engine' ); ?></h2>
			<p class="lead"><?php esc_html_e( 'Want to earn revenue from your content? Configure AdSense or affiliate links. You can skip this step and set it up later.', 'pearblog-engine' ); ?></p>

			<div class="form-section">
				<label for="revenue-enabled">
					<input
						type="checkbox"
						id="revenue-enabled"
						name="revenue_enabled"
						value="1"
						<?php checked( get_option( 'pearblog_v7_revenue_enabled', false ) ); ?>
					/>
					<?php esc_html_e( 'Enable Revenue Features', 'pearblog-engine' ); ?>
				</label>
			</div>

			<div id="monetization-settings" style="display: <?php echo get_option( 'pearblog_v7_revenue_enabled' ) ? 'block' : 'none'; ?>;">
				<div class="form-section">
					<label for="adsense-publisher-id"><?php esc_html_e( 'Google AdSense Publisher ID', 'pearblog-engine' ); ?></label>
					<input
						type="text"
						id="adsense-publisher-id"
						name="adsense_publisher_id"
						class="large-text"
						value="<?php echo esc_attr( get_option( 'pearblog_adsense_publisher_id', '' ) ); ?>"
						placeholder="ca-pub-XXXXXXXXXXXXXXXX"
					/>
					<p class="description">
						<?php esc_html_e( 'Find this in your Google AdSense dashboard under "Account → Account Information".', 'pearblog-engine' ); ?>
					</p>
				</div>

				<div class="form-section">
					<label for="adsense-strategy"><?php esc_html_e( 'Ad Placement Strategy', 'pearblog-engine' ); ?></label>
					<select id="adsense-strategy" name="adsense_strategy">
						<option value="funnel_aware" <?php selected( get_option( 'pearblog_adsense_strategy', 'funnel_aware' ), 'funnel_aware' ); ?>>
							<?php esc_html_e( 'Funnel-Aware (Recommended)', 'pearblog-engine' ); ?>
						</option>
						<option value="balanced" <?php selected( get_option( 'pearblog_adsense_strategy' ), 'balanced' ); ?>>
							<?php esc_html_e( 'Balanced', 'pearblog-engine' ); ?>
						</option>
						<option value="aggressive" <?php selected( get_option( 'pearblog_adsense_strategy' ), 'aggressive' ); ?>>
							<?php esc_html_e( 'Aggressive', 'pearblog-engine' ); ?>
						</option>
					</select>
					<p class="description">
						<?php esc_html_e( 'Funnel-Aware: More ads on informational content (TOFU), fewer on conversion-focused content (BOFU).', 'pearblog-engine' ); ?>
					</p>
				</div>

				<div class="wizard-callout wizard-callout-tip">
					<h4><?php esc_html_e( '💡 Why Funnel-Aware Ads Work Better', 'pearblog-engine' ); ?></h4>
					<p>
						<?php esc_html_e( 'Readers at the top of funnel (TOFU) are browsing and less likely to convert. Show more ads here to maximize ad revenue. Readers at bottom of funnel (BOFU) are ready to buy—reduce ads to increase conversion rate.', 'pearblog-engine' ); ?>
					</p>
					<p>
						<strong><?php esc_html_e( 'Result:', 'pearblog-engine' ); ?></strong>
						<?php esc_html_e( '30-40% higher overall revenue compared to uniform ad placement.', 'pearblog-engine' ); ?>
					</p>
				</div>
			</div>

			<button type="button" id="skip-monetization" class="button button-link">
				<?php esc_html_e( 'Skip this step, I\'ll configure monetization later', 'pearblog-engine' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Step 7: Complete & Launch.
	 */
	private function render_step_complete(): void {
		?>
		<div class="wizard-step wizard-step-complete">
			<div class="step-icon">🎉</div>
			<h2><?php esc_html_e( 'You\'re All Set!', 'pearblog-engine' ); ?></h2>
			<p class="lead"><?php esc_html_e( 'Congratulations! PearBlog Engine is configured and ready to start generating content.', 'pearblog-engine' ); ?></p>

			<div class="completion-summary">
				<h3><?php esc_html_e( 'What We Set Up:', 'pearblog-engine' ); ?></h3>
				<ul class="checklist">
					<li>✅ <?php esc_html_e( 'Connected to OpenAI API', 'pearblog-engine' ); ?></li>
					<li>✅ <?php esc_html_e( 'Configured content strategy for your niche', 'pearblog-engine' ); ?></li>
					<li>✅ <?php esc_html_e( 'Added topics to your content queue', 'pearblog-engine' ); ?></li>
					<li>✅ <?php esc_html_e( 'Set up automatic publishing schedule', 'pearblog-engine' ); ?></li>
					<?php if ( get_option( 'pearblog_v7_revenue_enabled' ) ) : ?>
						<li>✅ <?php esc_html_e( 'Configured monetization with AdSense', 'pearblog-engine' ); ?></li>
					<?php endif; ?>
				</ul>
			</div>

			<div class="next-steps">
				<h3><?php esc_html_e( 'Next Steps:', 'pearblog-engine' ); ?></h3>
				<ol>
					<li>
						<strong><?php esc_html_e( 'Generate Your First Article:', 'pearblog-engine' ); ?></strong>
						<?php esc_html_e( 'Click "Launch Admin Panel" below to access the Content Engine and run the pipeline manually.', 'pearblog-engine' ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Monitor Performance:', 'pearblog-engine' ); ?></strong>
						<?php esc_html_e( 'Check the Performance tab to track execution time, costs, and system health.', 'pearblog-engine' ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Customize Prompts:', 'pearblog-engine' ); ?></strong>
						<?php esc_html_e( 'Visit Strategy (AI) → Prompt Templates to fine-tune content generation for your niche.', 'pearblog-engine' ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Watch Tutorials:', 'pearblog-engine' ); ?></strong>
						<?php esc_html_e( 'Explore our', 'pearblog-engine' ); ?>
						<a href="https://docs.pearblog.ai/videos" target="_blank"><?php esc_html_e( 'video tutorials', 'pearblog-engine' ); ?></a>
						<?php esc_html_e( 'to learn advanced features.', 'pearblog-engine' ); ?>
					</li>
				</ol>
			</div>

			<div class="cta-buttons">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=pearblog-engine-v7' ) ); ?>" class="button button-primary button-hero">
					<?php esc_html_e( '🚀 Launch Admin Panel', 'pearblog-engine' ); ?>
				</a>
				<a href="https://docs.pearblog.ai" target="_blank" class="button button-secondary">
					<?php esc_html_e( '📖 View Documentation', 'pearblog-engine' ); ?>
				</a>
			</div>

			<div class="wizard-callout wizard-callout-success">
				<h4><?php esc_html_e( '🎯 Quick Win: Generate Your First Article Now', 'pearblog-engine' ); ?></h4>
				<p>
					<?php esc_html_e( 'Don\'t wait for the scheduled cron. Click "Launch Admin Panel" → Content Engine → "Run Pipeline Now" to see your first AI-generated article in under 60 seconds.', 'pearblog-engine' ); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render help panel for current step.
	 *
	 * @param int $step Current step number.
	 */
	private function render_help_panel( int $step ): void {
		?>
		<div class="wizard-help-panel">
			<h3><?php esc_html_e( 'Need Help?', 'pearblog-engine' ); ?></h3>

			<?php
			switch ( $step ) {
				case 2:
					?>
					<div class="help-section">
						<h4><?php esc_html_e( '🔐 Is my API key secure?', 'pearblog-engine' ); ?></h4>
						<p><?php esc_html_e( 'Yes. Your API key is stored in the WordPress database using WordPress security best practices. It\'s never transmitted to third parties—only directly to OpenAI.', 'pearblog-engine' ); ?></p>
					</div>
					<div class="help-section">
						<h4><?php esc_html_e( '💵 How much does it cost?', 'pearblog-engine' ); ?></h4>
						<p><?php esc_html_e( 'GPT-4 costs approximately $0.03-0.06 per article (1500-2000 words). For 30 articles/month, expect $1-2 in API costs.', 'pearblog-engine' ); ?></p>
					</div>
					<?php
					break;

				case 3:
					?>
					<div class="help-section">
						<h4><?php esc_html_e( '📝 What makes a good niche description?', 'pearblog-engine' ); ?></h4>
						<p><?php esc_html_e( 'Be specific! Instead of "technology", use "cloud computing for DevOps engineers". The AI generates much better content with narrow, well-defined niches.', 'pearblog-engine' ); ?></p>
					</div>
					<?php
					break;

				case 4:
					?>
					<div class="help-section">
						<h4><?php esc_html_e( '💡 How are AI-suggested topics generated?', 'pearblog-engine' ); ?></h4>
						<p><?php esc_html_e( 'We use GPT-4 to analyze your niche and keywords, then suggest trending, high-value topics with strong SEO potential.', 'pearblog-engine' ); ?></p>
					</div>
					<?php
					break;

				case 5:
					?>
					<div class="help-section">
						<h4><?php esc_html_e( '⏰ What if WP-Cron doesn\'t run?', 'pearblog-engine' ); ?></h4>
						<p><?php esc_html_e( 'WP-Cron only runs when someone visits your site. For production, use a system cron job:', 'pearblog-engine' ); ?></p>
						<code>0 * * * * wp cron event run --due-now</code>
					</div>
					<?php
					break;
			}
			?>

			<div class="help-links">
				<h4><?php esc_html_e( '📚 Resources', 'pearblog-engine' ); ?></h4>
				<ul>
					<li><a href="https://docs.pearblog.ai" target="_blank"><?php esc_html_e( 'Documentation', 'pearblog-engine' ); ?></a></li>
					<li><a href="https://docs.pearblog.ai/videos" target="_blank"><?php esc_html_e( 'Video Tutorials', 'pearblog-engine' ); ?></a></li>
					<li><a href="https://community.pearblog.ai" target="_blank"><?php esc_html_e( 'Community Forum', 'pearblog-engine' ); ?></a></li>
					<li><a href="mailto:support@pearblog.ai"><?php esc_html_e( 'Email Support', 'pearblog-engine' ); ?></a></li>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * Render navigation buttons.
	 *
	 * @param int $step Current step number.
	 */
	private function render_navigation( int $step ): void {
		?>
		<div class="wizard-nav">
			<?php if ( $step > 1 ) : ?>
				<button type="button" id="wizard-prev" class="button button-secondary">
					<?php esc_html_e( '← Previous', 'pearblog-engine' ); ?>
				</button>
			<?php endif; ?>

			<div class="wizard-nav-spacer"></div>

			<?php if ( $step < self::TOTAL_STEPS ) : ?>
				<button type="button" id="wizard-skip" class="button button-link">
					<?php esc_html_e( 'Skip wizard', 'pearblog-engine' ); ?>
				</button>
				<button type="button" id="wizard-next" class="button button-primary">
					<?php esc_html_e( 'Next →', 'pearblog-engine' ); ?>
				</button>
			<?php else : ?>
				<button type="button" id="wizard-finish" class="button button-primary">
					<?php esc_html_e( '✓ Complete Setup', 'pearblog-engine' ); ?>
				</button>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Check if wizard has been completed.
	 */
	private function is_wizard_complete(): bool {
		return (bool) get_option( self::WIZARD_COMPLETE_OPTION, false );
	}

	/**
	 * Get current wizard step.
	 */
	private function get_current_step(): int {
		return (int) get_option( self::CURRENT_STEP_OPTION, 1 );
	}

	/**
	 * AJAX: Save step data and advance to next step.
	 */
	public function ajax_save_step(): void {
		check_ajax_referer( 'pearblog_wizard_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized', 'pearblog-engine' ) ], 403 );
		}

		$step = isset( $_POST['step'] ) ? (int) $_POST['step'] : 0;
		$data = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// Save step-specific data
		switch ( $step ) {
			case 2:
				update_option( 'pearblog_openai_api_key', sanitize_text_field( $data['openai_api_key'] ?? '' ) );
				update_option( 'pearblog_openai_model', sanitize_text_field( $data['openai_model'] ?? 'gpt-4' ) );
				break;

			case 3:
				update_option( 'pearblog_target_niche', sanitize_text_field( $data['target_niche'] ?? '' ) );
				update_option( 'pearblog_target_audience', sanitize_textarea_field( $data['target_audience'] ?? '' ) );
				update_option( 'pearblog_topic_keywords', array_map( 'sanitize_text_field', $data['keywords'] ?? [] ) );
				break;

			case 4:
				// Topics handled separately via add_topic action
				break;

			case 5:
				update_option( 'pearblog_auto_publish_enabled', ! empty( $data['auto_publish_enabled'] ) );
				update_option( 'pearblog_articles_per_day', (int) ( $data['articles_per_day'] ?? 1 ) );
				update_option( 'pearblog_publish_time', sanitize_text_field( $data['publish_time'] ?? '10:00' ) );
				break;

			case 6:
				update_option( 'pearblog_v7_revenue_enabled', ! empty( $data['revenue_enabled'] ) );
				update_option( 'pearblog_adsense_publisher_id', sanitize_text_field( $data['adsense_publisher_id'] ?? '' ) );
				update_option( 'pearblog_adsense_strategy', sanitize_text_field( $data['adsense_strategy'] ?? 'funnel_aware' ) );
				break;
		}

		// Advance to next step
		$next_step = min( $step + 1, self::TOTAL_STEPS );
		update_option( self::CURRENT_STEP_OPTION, $next_step );

		// Mark wizard as complete if on final step
		if ( $next_step === self::TOTAL_STEPS + 1 ) {
			update_option( self::WIZARD_COMPLETE_OPTION, true );
		}

		wp_send_json_success( [
			'next_step' => $next_step,
			'message'   => __( 'Progress saved', 'pearblog-engine' ),
		] );
	}

	/**
	 * AJAX: Test OpenAI API key connectivity.
	 */
	public function ajax_test_api_key(): void {
		check_ajax_referer( 'pearblog_wizard_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized', 'pearblog-engine' ) ], 403 );
		}

		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';

		if ( '' === $api_key ) {
			wp_send_json_error( [ 'message' => __( 'API key is required', 'pearblog-engine' ) ] );
		}

		// Test connectivity by listing models
		$response = wp_remote_get( 'https://api.openai.com/v1/models', [
			'timeout' => 10,
			'headers' => [ 'Authorization' => 'Bearer ' . $api_key ],
		] );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( [ 'message' => $response->get_error_message() ] );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 === $code ) {
			wp_send_json_success( [ 'message' => __( '✓ API key valid and connected', 'pearblog-engine' ) ] );
		} elseif ( 401 === $code ) {
			wp_send_json_error( [ 'message' => __( '✗ Invalid API key (401 Unauthorized)', 'pearblog-engine' ) ] );
		} else {
			wp_send_json_error( [ 'message' => sprintf( __( '✗ API error (HTTP %d)', 'pearblog-engine' ), $code ) ] );
		}
	}

	/**
	 * AJAX: Generate topic ideas using AI.
	 */
	public function ajax_generate_topics(): void {
		check_ajax_referer( 'pearblog_wizard_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized', 'pearblog-engine' ) ], 403 );
		}

		$niche    = get_option( 'pearblog_target_niche', '' );
		$keywords = get_option( 'pearblog_topic_keywords', [] );

		if ( '' === $niche ) {
			wp_send_json_error( [ 'message' => __( 'Please set your target niche first', 'pearblog-engine' ) ] );
		}

		// Generate topics using AI
		$prompt = sprintf(
			"Generate 10 compelling blog post topics for a site about: %s\nKeywords: %s\nRequirements:\n- SEO-optimized titles\n- Mix of informational (TOFU), comparison (MOFU), and conversion-focused (BOFU) topics\n- Include numbers, how-to, and listicles\n- Keep under 60 characters\nReturn as JSON array.",
			$niche,
			implode( ', ', array_slice( $keywords, 0, 5 ) )
		);

		$client = new AIClient();
		$result = $client->complete( [ [ 'role' => 'user', 'content' => $prompt ] ], 'gpt-4' );

		if ( isset( $result['error'] ) ) {
			wp_send_json_error( [ 'message' => $result['error'] ] );
		}

		// Parse JSON response
		$content = $result['choices'][0]['message']['content'] ?? '';
		$topics  = json_decode( $content, true );

		if ( ! is_array( $topics ) ) {
			// Fallback: split by newlines if not JSON
			$topics = array_filter( array_map( 'trim', explode( "\n", $content ) ) );
		}

		wp_send_json_success( [ 'topics' => array_slice( $topics, 0, 10 ) ] );
	}

	/**
	 * AJAX: Skip wizard and mark as complete.
	 */
	public function ajax_skip_wizard(): void {
		check_ajax_referer( 'pearblog_wizard_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized', 'pearblog-engine' ) ], 403 );
		}

		update_option( self::WIZARD_COMPLETE_OPTION, true );

		wp_send_json_success( [
			'message'      => __( 'Wizard skipped', 'pearblog-engine' ),
			'redirect_url' => admin_url( 'admin.php?page=pearblog-engine-v7' ),
		] );
	}
}

<?php
/**
 * Admin page – settings and topic queue management UI.
 *
 * @package PearBlogEngine\Admin
 */

declare(strict_types=1);

namespace PearBlogEngine\Admin;

use PearBlogEngine\AI\ImageAnalyzer;
use PearBlogEngine\AI\ImageGenerator;
use PearBlogEngine\Content\TopicQueue;
use PearBlogEngine\SEO\ProgrammaticSEO;
use PearBlogEngine\Tenant\TenantContext;

/**
 * Registers a WordPress admin page under Settings → PearBlog Engine.
 */
class AdminPage {

	private const MENU_SLUG  = 'pearblog-engine';
	private const OPTION_GRP = 'pearblog_settings';

	/**
	 * Attach WordPress admin hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'admin_post_pearblog_add_topics', [ $this, 'handle_add_topics' ] );
		add_action( 'admin_post_pearblog_clear_queue', [ $this, 'handle_clear_queue' ] );
		add_action( 'admin_post_pearblog_generate_images', [ $this, 'handle_generate_images' ] );
		add_action( 'admin_post_pearblog_run_seo_audit', [ $this, 'handle_seo_audit' ] );
		add_action( 'admin_post_pearblog_fix_alt_texts', [ $this, 'handle_fix_alt_texts' ] );
		add_action( 'admin_post_pearblog_run_pipeline', [ $this, 'handle_run_pipeline' ] );
	}

	// -----------------------------------------------------------------------
	// Menu + settings registration
	// -----------------------------------------------------------------------

	public function add_menu(): void {
		add_menu_page(
			__( 'PearBlog Engine', 'pearblog-engine' ),
			__( 'PearBlog Engine', 'pearblog-engine' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_page' ],
			'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>' ),
			25
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Settings', 'pearblog-engine' ),
			__( 'Settings', 'pearblog-engine' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Enqueue admin CSS/JS only on our page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( false === strpos( $hook, self::MENU_SLUG ) ) {
			return;
		}

		wp_enqueue_style(
			'pearblog-admin',
			PEARBLOG_ENGINE_URL . 'assets/css/admin.css',
			[],
			PEARBLOG_ENGINE_VERSION
		);

		// Inline tab-switching JavaScript.
		wp_add_inline_script(
			'jquery',
			'document.addEventListener("DOMContentLoaded", function() {
				var tabBtns = document.querySelectorAll(".pb-tab-btn");
				var tabPanels = document.querySelectorAll(".pb-tab-panel");
				tabBtns.forEach(function(btn) {
					btn.addEventListener("click", function() {
						var target = this.dataset.tab;
						tabBtns.forEach(function(b) { b.classList.remove("is-active"); });
						tabPanels.forEach(function(p) { p.classList.remove("is-active"); });
						this.classList.add("is-active");
						var panel = document.getElementById("pb-tab-" + target);
						if (panel) panel.classList.add("is-active");
						if (window.history && window.history.replaceState) {
							var url = new URL(window.location);
							url.searchParams.set("tab", target);
							window.history.replaceState({}, "", url);
						}
					});
				});
				// Restore tab from URL
				var urlTab = new URLSearchParams(window.location.search).get("tab");
				if (urlTab) {
					var activeBtn = document.querySelector(".pb-tab-btn[data-tab=\"" + urlTab + "\"]");
					if (activeBtn) activeBtn.click();
				}
			});'
		);
	}

	public function register_settings(): void {
		// General settings.
		register_setting( self::OPTION_GRP, 'pearblog_openai_api_key',       [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_adsense_publisher_id', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_booking_affiliate_id', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_industry',             [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_tone',                 [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_monetization',         [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_publish_rate',         [ 'sanitize_callback' => 'absint' ] );
		register_setting( self::OPTION_GRP, 'pearblog_language',             [ 'sanitize_callback' => 'sanitize_text_field' ] );

		// Image generation settings.
		register_setting( self::OPTION_GRP, 'pearblog_enable_image_generation', [ 'sanitize_callback' => [ $this, 'sanitize_checkbox' ] ] );
		register_setting( self::OPTION_GRP, 'pearblog_image_style',             [ 'sanitize_callback' => 'sanitize_text_field' ] );

		// SaaS CTA settings.
		register_setting( self::OPTION_GRP, 'pearblog_saas_products', [ 'sanitize_callback' => [ $this, 'sanitize_saas_products' ] ] );

		// Autonomous mode.
		register_setting( self::OPTION_GRP, 'pearblog_autonomous_mode', [ 'sanitize_callback' => [ $this, 'sanitize_checkbox' ] ] );

		// Automation API settings.
		register_setting( self::OPTION_GRP, 'pearblog_api_key', [ 'sanitize_callback' => 'sanitize_text_field' ] );

		// Affiliate API settings.
		register_setting( self::OPTION_GRP, 'pearblog_booking_api_key',      [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_airbnb_affiliate_id',  [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_airbnb_api_key',       [ 'sanitize_callback' => 'sanitize_text_field' ] );

		// Email marketing settings.
		register_setting( self::OPTION_GRP, 'pearblog_esp_provider',         [ 'sanitize_callback' => 'sanitize_key' ] );
		register_setting( self::OPTION_GRP, 'pearblog_mailchimp_api_key',    [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_mailchimp_list_id',    [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_convertkit_api_key',   [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_convertkit_form_id',   [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_digest_email',         [ 'sanitize_callback' => 'sanitize_email' ] );

		// Monitoring / alert settings.
		register_setting( self::OPTION_GRP, 'pearblog_alert_slack_webhook',   [ 'sanitize_callback' => 'esc_url_raw' ] );
		register_setting( self::OPTION_GRP, 'pearblog_alert_discord_webhook', [ 'sanitize_callback' => 'esc_url_raw' ] );
		register_setting( self::OPTION_GRP, 'pearblog_alert_email',           [ 'sanitize_callback' => 'sanitize_email' ] );
		register_setting( self::OPTION_GRP, 'pearblog_alert_on_publish',      [ 'sanitize_callback' => [ $this, 'sanitize_checkbox' ] ] );

		// Content quality / duplicate detection settings.
		register_setting( self::OPTION_GRP, 'pearblog_duplicate_check_enabled', [ 'sanitize_callback' => [ $this, 'sanitize_checkbox' ] ] );

		// Social media settings.
		register_setting( self::OPTION_GRP, 'pearblog_social_enabled_channels',       [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_social_twitter_api_key',        [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_social_twitter_api_secret',     [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_social_twitter_access_token',   [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_social_twitter_access_secret',  [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_social_facebook_page_token',    [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_social_facebook_page_id',       [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_social_linkedin_access_token',  [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_social_linkedin_author_urn',    [ 'sanitize_callback' => 'sanitize_text_field' ] );
	}

	// -----------------------------------------------------------------------
	// Form handlers
	// -----------------------------------------------------------------------

	public function handle_add_topics(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_add_topics' );

		$raw    = sanitize_textarea_field( wp_unslash( $_POST['pearblog_topics'] ?? '' ) );
		$topics = array_filter( array_map( 'trim', explode( "\n", $raw ) ) );

		if ( ! empty( $topics ) ) {
			$queue = new TopicQueue( get_current_blog_id() );
			$queue->push( ...$topics );

			$count = count( $topics );
			$this->redirect_with_notice( sprintf(
				/* translators: %d: number of topics added */
				_n( '%d topic added to the queue.', '%d topics added to the queue.', $count, 'pearblog-engine' ),
				$count
			), 'success' );
		}

		$this->redirect_with_notice(
			__( 'No topics to add.', 'pearblog-engine' ),
			'warning'
		);
	}

	public function handle_clear_queue(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_clear_queue' );

		( new TopicQueue( get_current_blog_id() ) )->clear();

		$this->redirect_with_notice(
			__( 'Topic queue cleared.', 'pearblog-engine' ),
			'success'
		);
	}

	/**
	 * Handle image generation for posts without featured images.
	 */
	public function handle_generate_images(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_generate_images' );

		$keywords = sanitize_textarea_field( wp_unslash( $_POST['pearblog_image_keywords'] ?? '' ) );
		$post_ids = array_filter( array_map( 'absint', (array) ( $_POST['pearblog_image_post_ids'] ?? [] ) ) );

		$generator = new ImageGenerator();
		$generated = 0;

		if ( ! empty( $keywords ) && empty( $post_ids ) ) {
			// Generate a standalone image from keywords.
			$attachment_id = $this->generate_image_from_keywords( $generator, $keywords );
			if ( null !== $attachment_id ) {
				$generated = 1;
			}
		} elseif ( ! empty( $post_ids ) ) {
			// Generate images for specific posts.
			foreach ( $post_ids as $pid ) {
				if ( has_post_thumbnail( $pid ) ) {
					continue;
				}
				$topic = $keywords ?: get_the_title( $pid );
				$att   = $generator->generate_and_attach( $pid, $topic );
				if ( null !== $att ) {
					$generated++;
				}
			}
		}

		$this->redirect_with_notice(
			sprintf(
				/* translators: %d: number of images generated */
				_n( '%d image generated successfully.', '%d images generated successfully.', $generated, 'pearblog-engine' ),
				$generated
			),
			$generated > 0 ? 'success' : 'warning'
		);
	}

	/**
	 * Handle SEO audit request.
	 */
	public function handle_seo_audit(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_run_seo_audit' );

		$seo    = new ProgrammaticSEO();
		$audit  = $seo->bulk_audit( 100 );
		$fixed  = 0;

		// Auto-fix missing meta descriptions.
		foreach ( $audit['issues'] as $post_id => $data ) {
			if ( in_array( 'missing_meta_description', $data['issues'], true ) ) {
				$post = get_post( $post_id );
				if ( $post ) {
					$desc = $seo->generate_meta_description( $post->post_content );
					if ( '' !== $desc ) {
						update_post_meta( $post_id, 'pearblog_meta_description', $desc );
						update_post_meta( $post_id, '_yoast_wpseo_metadesc', $desc );
						update_post_meta( $post_id, 'rank_math_description', $desc );
						$fixed++;
					}
				}
			}
		}

		$this->redirect_with_notice(
			sprintf(
				/* translators: 1: issues found, 2: auto-fixed */
				__( 'SEO Audit: %1$d issues found, %2$d meta descriptions auto-generated.', 'pearblog-engine' ),
				$audit['issues_found'],
				$fixed
			),
			'success'
		);
	}

	/**
	 * Handle bulk alt text fix.
	 */
	public function handle_fix_alt_texts(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_fix_alt_texts' );

		$analyzer = new ImageAnalyzer();
		$missing  = $analyzer->find_images_without_alt( 100 );
		$fixed    = 0;

		foreach ( $missing as $img ) {
			$title = $img['title'];
			if ( empty( $title ) ) {
				$title = pathinfo( $img['file'] ?? '', PATHINFO_FILENAME );
				$title = str_replace( [ '-', '_' ], ' ', $title );
				$title = ucfirst( trim( $title ) );
			}
			if ( ! empty( $title ) ) {
				update_post_meta( $img['attachment_id'], '_wp_attachment_image_alt', sanitize_text_field( $title ) );
				$fixed++;
			}
		}

		$this->redirect_with_notice(
			sprintf(
				/* translators: %d: number of alt texts fixed */
				_n( '%d image alt text auto-generated.', '%d image alt texts auto-generated.', $fixed, 'pearblog-engine' ),
				$fixed
			),
			$fixed > 0 ? 'success' : 'warning'
		);
	}

	// -----------------------------------------------------------------------
	// Page render
	// -----------------------------------------------------------------------

	/**
	 * Trigger the content pipeline on demand.
	 */
	public function handle_run_pipeline(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_run_pipeline' );

		$queue = new TopicQueue( get_current_blog_id() );

		if ( $queue->count() === 0 ) {
			$this->redirect_with_notice(
				__( 'Queue is empty – add topics before running the pipeline.', 'pearblog-engine' ),
				'warning'
			);
			return;
		}

		// Trigger the cron action immediately (runs synchronously in the current request).
		do_action( 'pearblog_run_pipeline' );

		$this->redirect_with_notice(
			__( 'Pipeline triggered successfully.', 'pearblog-engine' ),
			'success'
		);
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$queue   = new TopicQueue( get_current_blog_id() );
		$context = TenantContext::for_site( get_current_blog_id() );

		// Flush notice from redirect.
		$notice_html = '';
		if ( isset( $_GET['pearblog_notice'] ) ) {
			$type         = isset( $_GET['pearblog_type'] ) && 'success' === $_GET['pearblog_type'] ? 'success' : 'warning';
			$notice_html  = '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>'
				. esc_html( urldecode( (string) $_GET['pearblog_notice'] ) )
				. '</p></div>';
		}

		// Quick stats for the header.
		$queue_count     = count( $queue->get_all() );
		$openai_ok       = ! empty( get_option( 'pearblog_openai_api_key', '' ) ) || defined( 'PEARBLOG_OPENAI_API_KEY' );
		$img_gen_enabled = get_option( 'pearblog_enable_image_generation', false );

		?>
		<div class="pb-admin-wrap">

			<!-- Page header -->
			<div class="pb-admin-header">
				<span class="pb-admin-header-icon" aria-hidden="true">🍐</span>
				<div>
					<h1 class="pb-admin-header-title"><?php esc_html_e( 'PearBlog Engine', 'pearblog-engine' ); ?></h1>
					<p class="pb-admin-header-subtitle"><?php esc_html_e( 'Autonomous AI content production system', 'pearblog-engine' ); ?></p>
				</div>
				<div class="pb-admin-badges">
					<span class="pb-badge <?php echo $openai_ok ? 'pb-badge-success' : 'pb-badge-danger'; ?>">
						<?php echo $openai_ok ? '✓ OpenAI' : '✗ OpenAI'; ?>
					</span>
					<span class="pb-badge pb-badge-info">
						<?php echo esc_html( PEARBLOG_ENGINE_VERSION ); ?>
					</span>
					<?php if ( $queue_count > 0 ) : ?>
						<span class="pb-badge pb-badge-warning">
							<?php echo esc_html( $queue_count ); ?> <?php esc_html_e( 'in queue', 'pearblog-engine' ); ?>
						</span>
					<?php endif; ?>
				</div>
			</div>

			<?php echo wp_kses_post( $notice_html ); ?>

			<!-- Tab navigation -->
			<div class="pb-admin-tabs" role="tablist">
				<button class="pb-tab-btn is-active" data-tab="general" role="tab" aria-selected="true" aria-controls="pb-tab-general">
					<span class="pb-tab-icon" aria-hidden="true">⚙️</span>
					<?php esc_html_e( 'General', 'pearblog-engine' ); ?>
				</button>
				<button class="pb-tab-btn" data-tab="images" role="tab" aria-selected="false" aria-controls="pb-tab-images">
					<span class="pb-tab-icon" aria-hidden="true">🖼️</span>
					<?php esc_html_e( 'AI Images', 'pearblog-engine' ); ?>
				</button>
				<button class="pb-tab-btn" data-tab="seo" role="tab" aria-selected="false" aria-controls="pb-tab-seo">
					<span class="pb-tab-icon" aria-hidden="true">📈</span>
					<?php esc_html_e( 'SEO', 'pearblog-engine' ); ?>
				</button>
				<button class="pb-tab-btn" data-tab="monetization" role="tab" aria-selected="false" aria-controls="pb-tab-monetization">
					<span class="pb-tab-icon" aria-hidden="true">💰</span>
					<?php esc_html_e( 'Monetization', 'pearblog-engine' ); ?>
				</button>
				<button class="pb-tab-btn" data-tab="email" role="tab" aria-selected="false" aria-controls="pb-tab-email">
					<span class="pb-tab-icon" aria-hidden="true">✉️</span>
					<?php esc_html_e( 'Email', 'pearblog-engine' ); ?>
				</button>
				<button class="pb-tab-btn" data-tab="queue" role="tab" aria-selected="false" aria-controls="pb-tab-queue">
					<span class="pb-tab-icon" aria-hidden="true">📋</span>
					<?php esc_html_e( 'Queue', 'pearblog-engine' ); ?>
				</button>
				<button class="pb-tab-btn" data-tab="automation" role="tab" aria-selected="false" aria-controls="pb-tab-automation">
					<span class="pb-tab-icon" aria-hidden="true">🤖</span>
					<?php esc_html_e( 'Automation', 'pearblog-engine' ); ?>
				</button>
			</div>

			<!-- ============================================================
				TAB: GENERAL
				============================================================ -->
			<div id="pb-tab-general" class="pb-tab-panel is-active">
				<div class="pb-admin-card">
					<div class="pb-admin-card-header">
						<span class="pb-admin-card-icon" aria-hidden="true">⚙️</span>
						<h2 class="pb-admin-card-title"><?php esc_html_e( 'General Settings', 'pearblog-engine' ); ?></h2>
					</div>
					<form method="post" action="options.php">
						<?php settings_fields( self::OPTION_GRP ); ?>
						<table class="form-table" role="presentation">
						<tr>
						<th scope="row"><label for="pearblog_autonomous_mode"><?php esc_html_e( 'Autonomous Mode', 'pearblog-engine' ); ?></label></th>
						<td>
							<label>
								<input type="checkbox" id="pearblog_autonomous_mode" name="pearblog_autonomous_mode" value="1" <?php checked( get_option( 'pearblog_autonomous_mode', true ) ); ?> />
								<?php esc_html_e( 'Enable fully autonomous content pipeline (WP-Cron)', 'pearblog-engine' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'When enabled, the pipeline runs automatically every hour and publishes articles from the topic queue without any manual intervention.', 'pearblog-engine' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pearblog_openai_api_key"><?php esc_html_e( 'OpenAI API Key', 'pearblog-engine' ); ?></label></th>
						<td><input type="password" id="pearblog_openai_api_key" name="pearblog_openai_api_key" value="<?php echo esc_attr( get_option( 'pearblog_openai_api_key', '' ) ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="pearblog_api_key"><?php esc_html_e( 'Automation API Key', 'pearblog-engine' ); ?></label></th>
						<td>
							<input type="password" id="pearblog_api_key" name="pearblog_api_key" value="<?php echo esc_attr( get_option( 'pearblog_api_key', '' ) ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Bearer token for external automation (GitHub Actions). Set this as API_KEY secret in your repository.', 'pearblog-engine' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pearblog_adsense_publisher_id"><?php esc_html_e( 'AdSense Publisher ID', 'pearblog-engine' ); ?></label></th>
						<td><input type="text" id="pearblog_adsense_publisher_id" name="pearblog_adsense_publisher_id" value="<?php echo esc_attr( get_option( 'pearblog_adsense_publisher_id', '' ) ); ?>" class="regular-text" placeholder="ca-pub-XXXXXXXXXXXXXXXX" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="pearblog_booking_affiliate_id"><?php esc_html_e( 'Booking.com Affiliate ID', 'pearblog-engine' ); ?></label></th>
						<td>
							<input type="text" id="pearblog_booking_affiliate_id" name="pearblog_booking_affiliate_id" value="<?php echo esc_attr( get_option( 'pearblog_booking_affiliate_id', '' ) ); ?>" class="regular-text" placeholder="1234567" />
							<p class="description"><?php esc_html_e( 'Your Booking.com partner/affiliate ID (aid). Required for Phase 2 affiliate monetisation.', 'pearblog-engine' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pearblog_booking_api_key"><?php esc_html_e( 'Booking.com API Key', 'pearblog-engine' ); ?></label></th>
						<td>
							<input type="password" id="pearblog_booking_api_key" name="pearblog_booking_api_key" value="<?php echo esc_attr( get_option( 'pearblog_booking_api_key', '' ) ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Optional. Reserved for future Booking.com Demand API integration.', 'pearblog-engine' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pearblog_airbnb_affiliate_id"><?php esc_html_e( 'Airbnb Affiliate ID', 'pearblog-engine' ); ?></label></th>
						<td>
							<input type="text" id="pearblog_airbnb_affiliate_id" name="pearblog_airbnb_affiliate_id" value="<?php echo esc_attr( get_option( 'pearblog_airbnb_affiliate_id', '' ) ); ?>" class="regular-text" placeholder="12345" />
							<p class="description"><?php esc_html_e( 'Your Airbnb partner ID used to build deep-link search URLs.', 'pearblog-engine' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pearblog_airbnb_api_key"><?php esc_html_e( 'Airbnb API Key', 'pearblog-engine' ); ?></label></th>
						<td>
							<input type="password" id="pearblog_airbnb_api_key" name="pearblog_airbnb_api_key" value="<?php echo esc_attr( get_option( 'pearblog_airbnb_api_key', '' ) ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Optional. Reserved for future Airbnb Partner API integration.', 'pearblog-engine' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pearblog_industry"><?php esc_html_e( 'Industry / Niche', 'pearblog-engine' ); ?></label></th>
						<td><input type="text" id="pearblog_industry" name="pearblog_industry" value="<?php echo esc_attr( get_option( 'pearblog_industry', 'general' ) ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="pearblog_tone"><?php esc_html_e( 'Writing Tone', 'pearblog-engine' ); ?></label></th>
						<td>
							<select id="pearblog_tone" name="pearblog_tone">
								<?php
								$current_tone = get_option( 'pearblog_tone', 'neutral' );
								$tones        = [ 'neutral', 'professional', 'conversational', 'authoritative', 'friendly' ];
								foreach ( $tones as $t ) {
									printf(
										'<option value="%s" %s>%s</option>',
										esc_attr( $t ),
										selected( $current_tone, $t, false ),
										esc_html( ucfirst( $t ) )
									);
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pearblog_monetization"><?php esc_html_e( 'Monetisation Strategy', 'pearblog-engine' ); ?></label></th>
						<td>
							<select id="pearblog_monetization" name="pearblog_monetization">
								<?php
								$current_mon = get_option( 'pearblog_monetization', 'adsense' );
								$strategies  = [ 'adsense' => 'AdSense (v1)', 'affiliate' => 'Affiliate (v2)', 'saas' => 'SaaS (v3)' ];
								foreach ( $strategies as $val => $label ) {
									printf(
										'<option value="%s" %s>%s</option>',
										esc_attr( $val ),
										selected( $current_mon, $val, false ),
										esc_html( $label )
									);
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pearblog_publish_rate"><?php esc_html_e( 'Publish Rate (articles/cycle)', 'pearblog-engine' ); ?></label></th>
						<td><input type="number" id="pearblog_publish_rate" name="pearblog_publish_rate" value="<?php echo esc_attr( get_option( 'pearblog_publish_rate', 1 ) ); ?>" min="1" max="50" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="pearblog_language"><?php esc_html_e( 'Language (ISO 639-1)', 'pearblog-engine' ); ?></label></th>
						<td><input type="text" id="pearblog_language" name="pearblog_language" value="<?php echo esc_attr( get_option( 'pearblog_language', 'en' ) ); ?>" class="small-text" maxlength="5" /></td>
					</tr>
				</table>
						<?php submit_button(); ?>
					</form>
				</div><!-- /pb-admin-card -->
			</div><!-- /pb-tab-general -->

			<!-- ============================================================
				TAB: AI IMAGES
				============================================================ -->
			<div id="pb-tab-images" class="pb-tab-panel">
				<div class="pb-admin-card">
					<div class="pb-admin-card-header">
						<span class="pb-admin-card-icon" aria-hidden="true">🎨</span>
						<h2 class="pb-admin-card-title"><?php esc_html_e( 'AI Image Generation Settings', 'pearblog-engine' ); ?></h2>
					</div>
					<form method="post" action="options.php">
						<?php settings_fields( self::OPTION_GRP ); ?>
						<h2><?php esc_html_e( 'AI Image Generation', 'pearblog-engine' ); ?></h2>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="pearblog_enable_image_generation"><?php esc_html_e( 'Enable Image Generation', 'pearblog-engine' ); ?></label></th>
								<td>
									<label>
										<input type="checkbox" id="pearblog_enable_image_generation" name="pearblog_enable_image_generation" value="1" <?php checked( get_option( 'pearblog_enable_image_generation', true ) ); ?> />
										<?php esc_html_e( 'Automatically generate featured images using DALL-E 3', 'pearblog-engine' ); ?>
									</label>
									<p class="description"><?php esc_html_e( 'Each article will get a unique AI-generated featured image. Requires OpenAI API key.', 'pearblog-engine' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="pearblog_image_style"><?php esc_html_e( 'Image Style', 'pearblog-engine' ); ?></label></th>
								<td>
									<select id="pearblog_image_style" name="pearblog_image_style">
										<?php
										$current_style = get_option( 'pearblog_image_style', 'photorealistic' );
										$styles        = [
											'photorealistic' => __( 'Photorealistic', 'pearblog-engine' ),
											'illustration'   => __( 'Digital Illustration', 'pearblog-engine' ),
											'artistic'       => __( 'Artistic / Painterly', 'pearblog-engine' ),
											'minimal'        => __( 'Minimal / Clean', 'pearblog-engine' ),
										];
										foreach ( $styles as $val => $label ) {
											printf(
												'<option value="%s" %s>%s</option>',
												esc_attr( $val ),
												selected( $current_style, $val, false ),
												esc_html( $label )
											);
										}
										?>
									</select>
									<p class="description"><?php esc_html_e( 'Visual style for AI-generated images', 'pearblog-engine' ); ?></p>
								</td>
							</tr>
						</table>
						<?php submit_button(); ?>
					</form>
				</div><!-- /image settings card -->

				<?php
				$img_analyzer = new ImageAnalyzer();
				$img_summary  = $img_analyzer->get_summary();
				?>

				<!-- Image stats -->
				<div class="pb-admin-stats-grid">
					<div class="pb-admin-stat">
						<div class="pb-admin-stat-value"><?php echo esc_html( (string) $img_summary['total_images'] ); ?></div>
						<div class="pb-admin-stat-label"><?php esc_html_e( 'Total Images', 'pearblog-engine' ); ?></div>
					</div>
					<div class="pb-admin-stat <?php echo $img_summary['posts_without_images'] > 0 ? 'is-warning' : 'is-success'; ?>">
						<div class="pb-admin-stat-value"><?php echo esc_html( (string) $img_summary['posts_without_images'] ); ?></div>
						<div class="pb-admin-stat-label"><?php esc_html_e( 'Posts Missing Image', 'pearblog-engine' ); ?></div>
					</div>
					<div class="pb-admin-stat">
						<div class="pb-admin-stat-value"><?php echo esc_html( (string) $img_summary['ai_generated'] ); ?></div>
						<div class="pb-admin-stat-label"><?php esc_html_e( 'AI Generated', 'pearblog-engine' ); ?></div>
					</div>
					<div class="pb-admin-stat <?php echo $img_summary['missing_alt'] > 0 ? 'is-warning' : 'is-success'; ?>">
						<div class="pb-admin-stat-value"><?php echo esc_html( (string) $img_summary['missing_alt'] ); ?></div>
						<div class="pb-admin-stat-label"><?php esc_html_e( 'Missing Alt Text', 'pearblog-engine' ); ?></div>
					</div>
				</div>

				<!-- Generate image from keywords -->
				<div class="pb-admin-card">
					<div class="pb-admin-card-header">
						<span class="pb-admin-card-icon" aria-hidden="true">✨</span>
						<h2 class="pb-admin-card-title"><?php esc_html_e( 'Generate Image from Keywords', 'pearblog-engine' ); ?></h2>
					</div>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="pearblog_generate_images" />
						<?php wp_nonce_field( 'pearblog_generate_images' ); ?>
						<div class="pb-admin-form-row">
							<label class="pb-admin-label" for="pearblog_image_keywords_single"><?php esc_html_e( 'Keywords / Title', 'pearblog-engine' ); ?></label>
							<input type="text" id="pearblog_image_keywords_single" name="pearblog_image_keywords" class="pb-admin-input"
								   placeholder="<?php esc_attr_e( 'e.g., Mountain landscape Tatry sunrise', 'pearblog-engine' ); ?>" />
							<p class="pb-admin-desc"><?php esc_html_e( 'Image generated via DALL-E 3 using the style selected above.', 'pearblog-engine' ); ?></p>
						</div>
						<div class="pb-admin-actions">
							<button type="submit" class="pb-admin-btn pb-admin-btn-primary">
								🖼️ <?php esc_html_e( 'Generate Image', 'pearblog-engine' ); ?>
							</button>
						</div>
					</form>
				</div>

				<!-- Posts without images for batch generation -->
				<?php
				$no_image_posts = $img_analyzer->find_posts_without_featured_image( 10 );
				if ( ! empty( $no_image_posts ) ) :
				?>
					<div class="pb-admin-card">
						<div class="pb-admin-card-header">
							<span class="pb-admin-card-icon" aria-hidden="true">📸</span>
							<h2 class="pb-admin-card-title"><?php esc_html_e( 'Posts Without Featured Images', 'pearblog-engine' ); ?></h2>
						</div>
						<p class="pb-admin-desc" style="margin-bottom: 16px;"><?php esc_html_e( 'Select posts to auto-generate featured images from their titles.', 'pearblog-engine' ); ?></p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="pearblog_generate_images" />
							<input type="hidden" name="pearblog_image_keywords" value="" />
							<?php wp_nonce_field( 'pearblog_generate_images' ); ?>
							<div class="pb-posts-table-wrapper">
								<table class="pb-admin-table">
									<thead>
										<tr>
											<th style="width: 32px;"><input type="checkbox" id="pearblog-select-all-posts" /></th>
											<th><?php esc_html_e( 'Post Title', 'pearblog-engine' ); ?></th>
											<th><?php esc_html_e( 'Suggested Keywords', 'pearblog-engine' ); ?></th>
											<th><?php esc_html_e( 'Date', 'pearblog-engine' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $no_image_posts as $nip ) : ?>
											<tr>
												<td><input type="checkbox" name="pearblog_image_post_ids[]" value="<?php echo esc_attr( (string) $nip['post_id'] ); ?>" /></td>
												<td>
													<a href="<?php echo esc_url( get_edit_post_link( $nip['post_id'], 'raw' ) ); ?>">
														<?php echo esc_html( $nip['title'] ); ?>
													</a>
												</td>
												<td><em><?php echo esc_html( implode( ', ', array_slice( $nip['keywords'], 0, 5 ) ) ); ?></em></td>
												<td><?php echo esc_html( $nip['date'] ); ?></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
							<div class="pb-admin-actions">
								<button type="submit" class="pb-admin-btn pb-admin-btn-secondary">
									🎨 <?php esc_html_e( 'Generate for Selected Posts', 'pearblog-engine' ); ?>
								</button>
							</div>
						</form>
					</div>
					<script>
					document.getElementById('pearblog-select-all-posts')?.addEventListener('change', function() {
						document.querySelectorAll('input[name="pearblog_image_post_ids[]"]').forEach(function(cb) {
							cb.checked = this.checked;
						}.bind(this));
					});
					</script>
				<?php endif; ?>

				<!-- Fix missing alt texts -->
				<?php if ( $img_summary['missing_alt'] > 0 ) : ?>
					<div class="pb-admin-card">
						<div class="pb-admin-card-header">
							<span class="pb-admin-card-icon" aria-hidden="true">♿</span>
							<h2 class="pb-admin-card-title"><?php esc_html_e( 'Fix Missing Alt Texts', 'pearblog-engine' ); ?></h2>
						</div>
						<p class="pb-admin-desc" style="margin-bottom: 16px;">
							<?php esc_html_e( 'Auto-generate alt text from image titles and filenames for SEO and accessibility.', 'pearblog-engine' ); ?>
						</p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="pearblog_fix_alt_texts" />
							<?php wp_nonce_field( 'pearblog_fix_alt_texts' ); ?>
							<div class="pb-admin-actions">
								<button type="submit" class="pb-admin-btn pb-admin-btn-success">
									✓ <?php echo esc_html( sprintf(
										/* translators: %d: number of images */
										__( 'Fix %d Missing Alt Texts', 'pearblog-engine' ),
										$img_summary['missing_alt']
									) ); ?>
								</button>
							</div>
						</form>
					</div>
				<?php endif; ?>
			</div><!-- /pb-tab-images -->

			<!-- ============================================================
				TAB: PROGRAMMATIC SEO
				============================================================ -->
			<div id="pb-tab-seo" class="pb-tab-panel">
				<?php
				$seo_engine = new ProgrammaticSEO();
				$seo_audit  = $seo_engine->bulk_audit( 20 );
				?>
				<!-- SEO stats -->
				<div class="pb-admin-stats-grid">
					<div class="pb-admin-stat">
						<div class="pb-admin-stat-value"><?php echo esc_html( (string) $seo_audit['posts_audited'] ); ?></div>
						<div class="pb-admin-stat-label"><?php esc_html_e( 'Posts Audited', 'pearblog-engine' ); ?></div>
					</div>
					<div class="pb-admin-stat <?php echo $seo_audit['issues_found'] > 0 ? 'is-danger' : 'is-success'; ?>">
						<div class="pb-admin-stat-value"><?php echo esc_html( (string) $seo_audit['issues_found'] ); ?></div>
						<div class="pb-admin-stat-label"><?php esc_html_e( 'Issues Found', 'pearblog-engine' ); ?></div>
					</div>
				</div>

				<!-- Auto-fix form -->
				<div class="pb-admin-card" style="margin-bottom: 20px;">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
						<input type="hidden" name="action" value="pearblog_run_seo_audit" />
						<?php wp_nonce_field( 'pearblog_run_seo_audit' ); ?>
						<button type="submit" class="pb-admin-btn pb-admin-btn-primary">
							📈 <?php esc_html_e( 'Run SEO Audit & Auto-Fix Meta Descriptions', 'pearblog-engine' ); ?>
						</button>
						<span class="pb-admin-desc"><?php esc_html_e( 'Scans all posts and auto-generates missing meta descriptions.', 'pearblog-engine' ); ?></span>
					</form>
				</div>

				<!-- Issues table -->
				<?php if ( ! empty( $seo_audit['issues'] ) ) : ?>
					<div class="pb-admin-card">
						<div class="pb-admin-card-header">
							<span class="pb-admin-card-icon" aria-hidden="true">⚠️</span>
							<h2 class="pb-admin-card-title"><?php esc_html_e( 'SEO Issues by Post', 'pearblog-engine' ); ?></h2>
						</div>
						<div style="overflow-x: auto;">
							<table class="pb-admin-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Post', 'pearblog-engine' ); ?></th>
										<th><?php esc_html_e( 'Issues', 'pearblog-engine' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php
									$issue_labels = [
										'missing_meta_description' => __( 'Missing meta description', 'pearblog-engine' ),
										'short_meta_description'   => __( 'Short meta description', 'pearblog-engine' ),
										'long_meta_description'    => __( 'Long meta description', 'pearblog-engine' ),
										'short_title'              => __( 'Short title', 'pearblog-engine' ),
										'long_title'               => __( 'Long title', 'pearblog-engine' ),
										'missing_featured_image'   => __( 'Missing featured image', 'pearblog-engine' ),
										'missing_image_alt'        => __( 'Missing image alt text', 'pearblog-engine' ),
										'thin_content'             => __( 'Thin content (<300 words)', 'pearblog-engine' ),
										'missing_h2'               => __( 'Missing H2 headings', 'pearblog-engine' ),
									];
									foreach ( array_slice( $seo_audit['issues'], 0, 15, true ) as $pid => $data ) :
									?>
										<tr>
											<td>
												<a href="<?php echo esc_url( get_edit_post_link( $pid, 'raw' ) ); ?>">
													<?php echo esc_html( $data['title'] ); ?>
												</a>
											</td>
											<td>
												<?php foreach ( $data['issues'] as $issue ) : ?>
													<span class="pb-issue-tag <?php echo in_array( $issue, [ 'missing_meta_description', 'missing_featured_image', 'thin_content' ], true ) ? 'pb-issue-tag-danger' : ''; ?>">
														<?php echo esc_html( $issue_labels[ $issue ] ?? $issue ); ?>
													</span>
												<?php endforeach; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				<?php else : ?>
					<div class="pb-admin-card" style="text-align: center; padding: 40px;">
						<div style="font-size: 2.5rem; margin-bottom: 12px;">✅</div>
						<h3 style="margin: 0 0 8px;"><?php esc_html_e( 'No SEO Issues Found', 'pearblog-engine' ); ?></h3>
						<p class="pb-admin-desc"><?php esc_html_e( 'All recently audited posts look good.', 'pearblog-engine' ); ?></p>
					</div>
				<?php endif; ?>
			</div><!-- /pb-tab-seo -->

			<!-- ============================================================
				TAB: MONETIZATION
				============================================================ -->
			<div id="pb-tab-monetization" class="pb-tab-panel">
				<div class="pb-admin-card">
					<div class="pb-admin-card-header">
						<span class="pb-admin-card-icon" aria-hidden="true">💰</span>
						<h2 class="pb-admin-card-title"><?php esc_html_e( 'Monetization Settings', 'pearblog-engine' ); ?></h2>
					</div>
					<form method="post" action="options.php">
						<?php settings_fields( self::OPTION_GRP ); ?>
						<h2><?php esc_html_e( 'SaaS CTA Monetisation (v3)', 'pearblog-engine' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Configure SaaS product recommendations. Articles are scanned for matching keywords and a CTA box is injected automatically. Set "Monetisation Strategy" in General to "SaaS (v3)" to activate.', 'pearblog-engine' ); ?></p>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="pearblog_saas_products"><?php esc_html_e( 'SaaS Products (JSON)', 'pearblog-engine' ); ?></label></th>
								<td>
									<textarea id="pearblog_saas_products" name="pearblog_saas_products" rows="10" class="large-text code"><?php echo esc_textarea( get_option( 'pearblog_saas_products', '[]' ) ); ?></textarea>
									<p class="description">
										<?php esc_html_e( 'JSON array. Each product: {"name":"…","url":"https://…","keywords":["kw1","kw2"],"description":"…","cta_text":"…"}', 'pearblog-engine' ); ?>
									</p>
								</td>
							</tr>
						</table>
						<?php submit_button(); ?>
					</form>
				</div>
			</div><!-- /pb-tab-monetization -->

			<!-- ============================================================
				TAB: EMAIL
				============================================================ -->
			<div id="pb-tab-email" class="pb-tab-panel">
				<div class="pb-admin-card">
					<div class="pb-admin-card-header">
						<span class="pb-admin-card-icon" aria-hidden="true">✉️</span>
						<h2 class="pb-admin-card-title"><?php esc_html_e( 'Email Marketing', 'pearblog-engine' ); ?></h2>
					</div>
					<form method="post" action="options.php">
						<?php settings_fields( self::OPTION_GRP ); ?>
						<h2><?php esc_html_e( 'Email Marketing (Phase 3.2)', 'pearblog-engine' ); ?></h2>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="pearblog_esp_provider"><?php esc_html_e( 'Email Service Provider', 'pearblog-engine' ); ?></label></th>
								<td>
									<select id="pearblog_esp_provider" name="pearblog_esp_provider">
										<?php
										$current_esp = get_option( 'pearblog_esp_provider', 'none' );
										$providers   = [
											'none'       => __( 'None (Local Only)', 'pearblog-engine' ),
											'mailchimp'  => __( 'Mailchimp', 'pearblog-engine' ),
											'convertkit' => __( 'ConvertKit', 'pearblog-engine' ),
										];
										foreach ( $providers as $val => $label ) {
											printf(
												'<option value="%s" %s>%s</option>',
												esc_attr( $val ),
												selected( $current_esp, $val, false ),
												esc_html( $label )
											);
										}
										?>
									</select>
									<p class="description"><?php esc_html_e( 'Select your email service provider for automated list syncing.', 'pearblog-engine' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="pearblog_mailchimp_api_key"><?php esc_html_e( 'Mailchimp API Key', 'pearblog-engine' ); ?></label></th>
								<td>
									<input type="password" id="pearblog_mailchimp_api_key" name="pearblog_mailchimp_api_key" value="<?php echo esc_attr( get_option( 'pearblog_mailchimp_api_key', '' ) ); ?>" class="regular-text" />
									<p class="description"><?php esc_html_e( 'Format: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx-us1', 'pearblog-engine' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="pearblog_mailchimp_list_id"><?php esc_html_e( 'Mailchimp List ID', 'pearblog-engine' ); ?></label></th>
								<td>
									<input type="text" id="pearblog_mailchimp_list_id" name="pearblog_mailchimp_list_id" value="<?php echo esc_attr( get_option( 'pearblog_mailchimp_list_id', '' ) ); ?>" class="regular-text" />
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="pearblog_convertkit_api_key"><?php esc_html_e( 'ConvertKit API Key', 'pearblog-engine' ); ?></label></th>
								<td>
									<input type="password" id="pearblog_convertkit_api_key" name="pearblog_convertkit_api_key" value="<?php echo esc_attr( get_option( 'pearblog_convertkit_api_key', '' ) ); ?>" class="regular-text" />
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="pearblog_convertkit_form_id"><?php esc_html_e( 'ConvertKit Form ID', 'pearblog-engine' ); ?></label></th>
								<td>
									<input type="text" id="pearblog_convertkit_form_id" name="pearblog_convertkit_form_id" value="<?php echo esc_attr( get_option( 'pearblog_convertkit_form_id', '' ) ); ?>" class="regular-text" />
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="pearblog_digest_email"><?php esc_html_e( 'Digest Fallback Email', 'pearblog-engine' ); ?></label></th>
								<td>
									<input type="email" id="pearblog_digest_email" name="pearblog_digest_email" value="<?php echo esc_attr( get_option( 'pearblog_digest_email', '' ) ); ?>" class="regular-text" />
									<p class="description"><?php esc_html_e( 'Weekly digest will also be sent to this address via wp_mail when no ESP is configured.', 'pearblog-engine' ); ?></p>
								</td>
							</tr>
						</table>
						<?php submit_button(); ?>
					</form>
				</div>
			</div><!-- /pb-tab-email -->

			<!-- ============================================================
				TAB: QUEUE
				============================================================ -->
			<div id="pb-tab-queue" class="pb-tab-panel">
				<!-- Site profile -->
				<div class="pb-admin-card" style="margin-bottom: 20px;">
					<div class="pb-admin-card-header">
						<span class="pb-admin-card-icon" aria-hidden="true">🏢</span>
						<h2 class="pb-admin-card-title"><?php esc_html_e( 'Active Site Profile', 'pearblog-engine' ); ?></h2>
					</div>
					<p><?php echo esc_html( $context->profile->summary() ); ?></p>
				</div>

				<!-- Queue status -->
				<div class="pb-admin-stats-grid">
					<div class="pb-admin-stat <?php echo $queue->count() > 0 ? 'is-success' : ''; ?>">
						<div class="pb-admin-stat-value"><?php echo esc_html( (string) $queue->count() ); ?></div>
						<div class="pb-admin-stat-label"><?php esc_html_e( 'Topics in Queue', 'pearblog-engine' ); ?></div>
					</div>
				</div>

				<!-- Queue list -->
				<?php if ( $queue->count() > 0 ) : ?>
					<div class="pb-admin-card">
						<div class="pb-admin-card-header">
							<span class="pb-admin-card-icon" aria-hidden="true">📋</span>
							<h2 class="pb-admin-card-title"><?php esc_html_e( 'Current Queue', 'pearblog-engine' ); ?></h2>
						</div>
						<ul class="pb-queue-list">
							<?php foreach ( $queue->all() as $i => $topic ) : ?>
								<li class="pb-queue-item">
									<span class="pb-queue-num"><?php echo esc_html( (string) ( $i + 1 ) ); ?></span>
									<?php echo esc_html( $topic ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="pearblog_clear_queue" />
							<?php wp_nonce_field( 'pearblog_clear_queue' ); ?>
							<div class="pb-admin-actions">
								<button type="submit" class="pb-admin-btn pb-admin-btn-danger">
									🗑️ <?php esc_html_e( 'Clear Queue', 'pearblog-engine' ); ?>
								</button>
							</div>
						</form>
					</div>
				<?php endif; ?>

				<!-- Run pipeline -->
				<div class="pb-admin-card">
					<div class="pb-admin-card-header">
						<span class="pb-admin-card-icon" aria-hidden="true">▶️</span>
						<h2 class="pb-admin-card-title"><?php esc_html_e( 'Run Pipeline', 'pearblog-engine' ); ?></h2>
					</div>
					<?php
					$autonomous_enabled = (bool) get_option( 'pearblog_autonomous_mode', true );
					$next_scheduled     = wp_next_scheduled( 'pearblog_run_pipeline' );
					?>
					<table class="widefat fixed" style="max-width:500px; margin-bottom:12px;">
						<tbody>
							<tr>
								<td><strong><?php esc_html_e( 'Mode', 'pearblog-engine' ); ?></strong></td>
								<td>
									<?php if ( $autonomous_enabled ) : ?>
										<span style="color:#00a32a;">&#9679; <?php esc_html_e( 'Autonomous (enabled)', 'pearblog-engine' ); ?></span>
									<?php else : ?>
										<span style="color:#d63638;">&#9679; <?php esc_html_e( 'Manual (disabled)', 'pearblog-engine' ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Next Scheduled Run', 'pearblog-engine' ); ?></strong></td>
								<td>
									<?php
									if ( $next_scheduled ) {
										echo esc_html( sprintf(
											/* translators: %s: human-readable time difference */
											__( 'In %s', 'pearblog-engine' ),
											human_time_diff( time(), $next_scheduled )
										) );
										echo ' (' . esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_scheduled ) ) . ')';
									} else {
										esc_html_e( 'Not scheduled', 'pearblog-engine' );
									}
									?>
								</td>
							</tr>
						</tbody>
					</table>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="pearblog_run_pipeline" />
						<?php wp_nonce_field( 'pearblog_run_pipeline' ); ?>
						<div class="pb-admin-actions">
							<?php
							$disabled_attrs = $queue->count() === 0 ? [ 'disabled' => 'disabled', 'title' => __( 'Add topics to the queue first', 'pearblog-engine' ) ] : [];
							submit_button( __( 'Run Pipeline Now', 'pearblog-engine' ), 'primary', '', false, $disabled_attrs );
							?>
						</div>
					</form>
				</div>

				<!-- Add topics -->
				<div class="pb-admin-card">
					<div class="pb-admin-card-header">
						<span class="pb-admin-card-icon" aria-hidden="true">➕</span>
						<h2 class="pb-admin-card-title"><?php esc_html_e( 'Add Topics to Queue', 'pearblog-engine' ); ?></h2>
					</div>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="pearblog_add_topics" />
						<?php wp_nonce_field( 'pearblog_add_topics' ); ?>
						<div class="pb-admin-form-row">
							<label class="pb-admin-label" for="pearblog_topics"><?php esc_html_e( 'Topics (one per line)', 'pearblog-engine' ); ?></label>
							<textarea id="pearblog_topics" name="pearblog_topics" rows="8" class="pb-admin-textarea" placeholder="<?php esc_attr_e( "Best hiking trails in Tatry\nWeather in Zakopane in winter\nTop restaurants in Kraków", 'pearblog-engine' ); ?>"></textarea>
							<p class="pb-admin-desc"><?php esc_html_e( 'One topic per line. The pipeline will process them in order on the next scheduled run.', 'pearblog-engine' ); ?></p>
						</div>
						<div class="pb-admin-actions">
							<button type="submit" class="pb-admin-btn pb-admin-btn-primary">
								➕ <?php esc_html_e( 'Add to Queue', 'pearblog-engine' ); ?>
							</button>
						</div>
					</form>
				</div>
			</div><!-- /pb-tab-queue -->

			<!-- ============================================================
				TAB: AUTOMATION
				============================================================ -->
			<div id="pb-tab-automation" class="pb-tab-panel">
				<div class="pb-admin-card">
					<div class="pb-admin-card-header">
						<span class="pb-admin-card-icon" aria-hidden="true">🔔</span>
						<h2 class="pb-admin-card-title"><?php esc_html_e( 'Monitoring &amp; Alerts', 'pearblog-engine' ); ?></h2>
					</div>
					<form method="post" action="options.php">
						<?php settings_fields( self::OPTION_GRP ); ?>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="pearblog_alert_slack_webhook"><?php esc_html_e( 'Slack Webhook URL', 'pearblog-engine' ); ?></label></th>
								<td>
									<input type="url" id="pearblog_alert_slack_webhook" name="pearblog_alert_slack_webhook" value="<?php echo esc_attr( get_option( 'pearblog_alert_slack_webhook', '' ) ); ?>" class="regular-text" placeholder="https://hooks.slack.com/services/…" />
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="pearblog_alert_discord_webhook"><?php esc_html_e( 'Discord Webhook URL', 'pearblog-engine' ); ?></label></th>
								<td>
									<input type="url" id="pearblog_alert_discord_webhook" name="pearblog_alert_discord_webhook" value="<?php echo esc_attr( get_option( 'pearblog_alert_discord_webhook', '' ) ); ?>" class="regular-text" placeholder="https://discord.com/api/webhooks/…" />
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="pearblog_alert_email"><?php esc_html_e( 'Alert Email', 'pearblog-engine' ); ?></label></th>
								<td>
									<input type="email" id="pearblog_alert_email" name="pearblog_alert_email" value="<?php echo esc_attr( get_option( 'pearblog_alert_email', '' ) ); ?>" class="regular-text" />
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Alert on Publish', 'pearblog-engine' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="pearblog_alert_on_publish" value="1" <?php checked( get_option( 'pearblog_alert_on_publish', false ) ); ?> />
										<?php esc_html_e( 'Send info alert every time a new article is published', 'pearblog-engine' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Duplicate Content Check', 'pearblog-engine' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="pearblog_duplicate_check_enabled" value="1" <?php checked( get_option( 'pearblog_duplicate_check_enabled', true ) ); ?> />
										<?php esc_html_e( 'Block publication of articles with &gt;80% cosine similarity to existing content', 'pearblog-engine' ); ?>
									</label>
								</td>
							</tr>
						</table>

						<h2><?php esc_html_e( 'Social Media Auto-posting', 'pearblog-engine' ); ?></h2>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="pearblog_social_enabled_channels"><?php esc_html_e( 'Enabled Channels', 'pearblog-engine' ); ?></label></th>
								<td>
									<input type="text" id="pearblog_social_enabled_channels" name="pearblog_social_enabled_channels" value="<?php echo esc_attr( get_option( 'pearblog_social_enabled_channels', '' ) ); ?>" class="regular-text" placeholder="twitter,facebook,linkedin" />
									<p class="description"><?php esc_html_e( 'Comma-separated: twitter, facebook, linkedin', 'pearblog-engine' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Twitter / X', 'pearblog-engine' ); ?></th>
								<td>
									<p><label><?php esc_html_e( 'API Key:', 'pearblog-engine' ); ?> <input type="password" name="pearblog_social_twitter_api_key" value="<?php echo esc_attr( get_option( 'pearblog_social_twitter_api_key', '' ) ); ?>" class="regular-text" /></label></p>
									<p><label><?php esc_html_e( 'API Secret:', 'pearblog-engine' ); ?> <input type="password" name="pearblog_social_twitter_api_secret" value="<?php echo esc_attr( get_option( 'pearblog_social_twitter_api_secret', '' ) ); ?>" class="regular-text" /></label></p>
									<p><label><?php esc_html_e( 'Access Token:', 'pearblog-engine' ); ?> <input type="password" name="pearblog_social_twitter_access_token" value="<?php echo esc_attr( get_option( 'pearblog_social_twitter_access_token', '' ) ); ?>" class="regular-text" /></label></p>
									<p><label><?php esc_html_e( 'Access Secret:', 'pearblog-engine' ); ?> <input type="password" name="pearblog_social_twitter_access_secret" value="<?php echo esc_attr( get_option( 'pearblog_social_twitter_access_secret', '' ) ); ?>" class="regular-text" /></label></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Facebook', 'pearblog-engine' ); ?></th>
								<td>
									<p><label><?php esc_html_e( 'Page Access Token:', 'pearblog-engine' ); ?> <input type="password" name="pearblog_social_facebook_page_token" value="<?php echo esc_attr( get_option( 'pearblog_social_facebook_page_token', '' ) ); ?>" class="regular-text" /></label></p>
									<p><label><?php esc_html_e( 'Page ID:', 'pearblog-engine' ); ?> <input type="text" name="pearblog_social_facebook_page_id" value="<?php echo esc_attr( get_option( 'pearblog_social_facebook_page_id', '' ) ); ?>" class="regular-text" /></label></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'LinkedIn', 'pearblog-engine' ); ?></th>
								<td>
									<p><label><?php esc_html_e( 'Access Token:', 'pearblog-engine' ); ?> <input type="password" name="pearblog_social_linkedin_access_token" value="<?php echo esc_attr( get_option( 'pearblog_social_linkedin_access_token', '' ) ); ?>" class="regular-text" /></label></p>
									<p><label><?php esc_html_e( 'Author URN:', 'pearblog-engine' ); ?> <input type="text" name="pearblog_social_linkedin_author_urn" value="<?php echo esc_attr( get_option( 'pearblog_social_linkedin_author_urn', '' ) ); ?>" class="regular-text" placeholder="urn:li:person:xxxx" /></label></p>
								</td>
							</tr>
						</table>
						<?php submit_button(); ?>
					</form>
				</div>
			</div><!-- /pb-tab-automation -->

		</div><!-- /pb-admin-wrap -->
		<?php
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Sanitize checkbox value to boolean.
	 *
	 * @param mixed $value Input value.
	 * @return bool
	 */
	public function sanitize_checkbox( $value ): bool {
		return (bool) $value;
	}

	/**
	 * Sanitize the SaaS products JSON field.
	 *
	 * Validates JSON structure and sanitises each product entry.
	 * Returns cleaned JSON string, or '[]' on invalid input.
	 *
	 * @param mixed $value Raw input value.
	 * @return string Sanitised JSON string.
	 */
	public function sanitize_saas_products( $value ): string {
		$value = (string) $value;

		if ( '' === trim( $value ) ) {
			return '[]';
		}

		$decoded = json_decode( $value, true );

		if ( ! is_array( $decoded ) ) {
			add_settings_error(
				'pearblog_saas_products',
				'invalid_json',
				__( 'SaaS Products: Invalid JSON. Value was reset to empty.', 'pearblog-engine' )
			);
			return '[]';
		}

		$clean = [];
		foreach ( $decoded as $product ) {
			if ( ! is_array( $product ) ) {
				continue;
			}

			$name = sanitize_text_field( $product['name'] ?? '' );
			$url  = esc_url_raw( $product['url'] ?? '' );

			if ( '' === $name || '' === $url ) {
				continue;
			}

			$keywords = $product['keywords'] ?? [];
			if ( is_string( $keywords ) ) {
				$keywords = array_filter( array_map( 'trim', explode( ',', $keywords ) ) );
			}
			$keywords = array_values( array_map( 'sanitize_text_field', (array) $keywords ) );

			$clean[] = [
				'name'        => $name,
				'url'         => $url,
				'keywords'    => $keywords,
				'description' => sanitize_text_field( $product['description'] ?? '' ),
				'cta_text'    => sanitize_text_field( $product['cta_text'] ?? '' ),
			];
		}

		return (string) wp_json_encode( $clean, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
	}

	/**
	 * Generate a standalone image from keywords and add to media library.
	 *
	 * @param ImageGenerator $generator Image generator instance.
	 * @param string         $keywords  Keywords for the image.
	 * @return int|null Attachment ID, or null on failure.
	 */
	private function generate_image_from_keywords( ImageGenerator $generator, string $keywords ): ?int {
		$image_url = $generator->generate( $keywords );
		if ( null === $image_url ) {
			return null;
		}

		// Download to media library without attaching to a post.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$temp_file = download_url( $image_url );
		if ( is_wp_error( $temp_file ) ) {
			return null;
		}

		$file_array = [
			'name'     => sanitize_file_name( $keywords ) . '.png',
			'tmp_name' => $temp_file,
		];

		$attachment_id = media_handle_sideload( $file_array, 0, $keywords );

		if ( file_exists( $temp_file ) ) {
			unlink( $temp_file );
		}

		if ( is_wp_error( $attachment_id ) ) {
			return null;
		}

		update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $keywords ) );
		update_post_meta( $attachment_id, '_pearblog_ai_generated', true );
		update_post_meta( $attachment_id, '_pearblog_generation_date', current_time( 'timestamp' ) );
		update_post_meta( $attachment_id, '_pearblog_image_source', 'dall-e-3' );

		return $attachment_id;
	}

	private function redirect_with_notice( string $message, string $type = 'success' ): void {
		wp_safe_redirect( add_query_arg(
			[
				'page'            => self::MENU_SLUG,
				'pearblog_notice' => rawurlencode( $message ),
				'pearblog_type'   => $type,
			],
			admin_url( 'options-general.php' )
		) );
		exit;
	}
}

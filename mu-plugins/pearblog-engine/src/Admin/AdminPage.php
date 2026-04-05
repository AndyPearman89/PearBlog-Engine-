<?php
/**
 * Admin page – settings and topic queue management UI.
 *
 * @package PearBlogEngine\Admin
 */

declare(strict_types=1);

namespace PearBlogEngine\Admin;

use PearBlogEngine\Content\TopicQueue;
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
		add_action( 'admin_post_pearblog_add_topics', [ $this, 'handle_add_topics' ] );
		add_action( 'admin_post_pearblog_clear_queue', [ $this, 'handle_clear_queue' ] );
	}

	// -----------------------------------------------------------------------
	// Menu + settings registration
	// -----------------------------------------------------------------------

	public function add_menu(): void {
		add_options_page(
			__( 'PearBlog Engine', 'pearblog-engine' ),
			__( 'PearBlog Engine', 'pearblog-engine' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_page' ]
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

		// Automation API settings.
		register_setting( self::OPTION_GRP, 'pearblog_api_key', [ 'sanitize_callback' => 'sanitize_text_field' ] );

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

	// -----------------------------------------------------------------------
	// Page render
	// -----------------------------------------------------------------------

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$queue   = new TopicQueue( get_current_blog_id() );
		$context = TenantContext::for_site( get_current_blog_id() );

		// Flush notice from redirect.
		$notice = '';
		if ( isset( $_GET['pearblog_notice'] ) ) {
			$type   = isset( $_GET['pearblog_type'] ) && 'success' === $_GET['pearblog_type'] ? 'success' : 'warning';
			$notice = '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>'
				. esc_html( urldecode( (string) $_GET['pearblog_notice'] ) )
				. '</p></div>';
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'PearBlog Engine', 'pearblog-engine' ); ?></h1>

			<?php echo wp_kses_post( $notice ); ?>

			<!-- Settings form -->
			<h2><?php esc_html_e( 'Settings', 'pearblog-engine' ); ?></h2>
			<form method="post" action="options.php">
				<?php settings_fields( self::OPTION_GRP ); ?>
				<table class="form-table" role="presentation">
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

				<h2><?php esc_html_e( 'SaaS CTA Monetisation (v3)', 'pearblog-engine' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Configure SaaS product recommendations. Articles are scanned for matching keywords and a CTA box is injected automatically. Set "Monetisation Strategy" above to "SaaS (v3)" to activate pipeline injection, or use the pearblog_saas_cta_content filter for manual control.', 'pearblog-engine' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="pearblog_saas_products"><?php esc_html_e( 'SaaS Products (JSON)', 'pearblog-engine' ); ?></label></th>
						<td>
							<textarea id="pearblog_saas_products" name="pearblog_saas_products" rows="10" class="large-text code"><?php echo esc_textarea( get_option( 'pearblog_saas_products', '[]' ) ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'JSON array of products. Each product: {"name":"…","url":"https://…","keywords":["kw1","kw2"],"description":"…","cta_text":"…"}', 'pearblog-engine' ); ?>
							</p>
						</td>
					</tr>
				</table>

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
							<p class="description"><?php esc_html_e( 'Weekly digest will also be sent to this address via wp_mail.', 'pearblog-engine' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Monitoring &amp; Alerts', 'pearblog-engine' ); ?></h2>
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
								<?php esc_html_e( 'Block publication of articles with >80% similarity to existing content', 'pearblog-engine' ); ?>
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
							<p class="description"><?php esc_html_e( 'Comma-separated list: twitter, facebook, linkedin', 'pearblog-engine' ); ?></p>
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

			<hr />

			<!-- Site profile summary -->
			<h2><?php esc_html_e( 'Active Site Profile', 'pearblog-engine' ); ?></h2>
			<p><?php echo esc_html( $context->profile->summary() ); ?></p>

			<hr />

			<!-- Topic queue -->
			<h2><?php esc_html_e( 'Topic Queue', 'pearblog-engine' ); ?></h2>
			<p>
				<?php
				echo esc_html( sprintf(
					/* translators: %d: number of topics in queue */
					_n( '%d topic in queue.', '%d topics in queue.', $queue->count(), 'pearblog-engine' ),
					$queue->count()
				) );
				?>
			</p>

			<?php if ( $queue->count() > 0 ) : ?>
				<ol>
					<?php foreach ( $queue->all() as $topic ) : ?>
						<li><?php echo esc_html( $topic ); ?></li>
					<?php endforeach; ?>
				</ol>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="pearblog_clear_queue" />
					<?php wp_nonce_field( 'pearblog_clear_queue' ); ?>
					<?php submit_button( __( 'Clear Queue', 'pearblog-engine' ), 'delete', '', false ); ?>
				</form>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="pearblog_add_topics" />
				<?php wp_nonce_field( 'pearblog_add_topics' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="pearblog_topics"><?php esc_html_e( 'Add Topics (one per line)', 'pearblog-engine' ); ?></label></th>
						<td><textarea id="pearblog_topics" name="pearblog_topics" rows="6" class="large-text"></textarea></td>
					</tr>
				</table>
				<?php submit_button( __( 'Add to Queue', 'pearblog-engine' ) ); ?>
			</form>
		</div>
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

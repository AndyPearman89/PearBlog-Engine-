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
		add_action( 'admin_post_pearblog_run_pipeline', [ $this, 'handle_run_pipeline' ] );
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
		register_setting( self::OPTION_GRP, 'pearblog_booking_api_key',      [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_airbnb_affiliate_id',  [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_airbnb_api_key',       [ 'sanitize_callback' => 'sanitize_text_field' ] );
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

		// Email marketing settings.
		register_setting( self::OPTION_GRP, 'pearblog_esp_provider',       [ 'sanitize_callback' => 'sanitize_key' ] );
		register_setting( self::OPTION_GRP, 'pearblog_mailchimp_api_key',  [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_mailchimp_list_id',  [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_convertkit_api_key', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_convertkit_form_id', [ 'sanitize_callback' => 'sanitize_text_field' ] );
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
				</table>
				<?php submit_button(); ?>
			</form>

			<hr />

			<!-- Autonomous pipeline status -->
			<h2><?php esc_html_e( 'Autonomous Pipeline Status', 'pearblog-engine' ); ?></h2>
			<?php
			$autonomous_enabled = (bool) get_option( 'pearblog_autonomous_mode', true );
			$next_scheduled     = wp_next_scheduled( 'pearblog_run_pipeline' );
			?>
			<table class="widefat fixed" style="max-width:500px;">
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
								echo esc_html(
									sprintf(
										/* translators: %s: human-readable time difference */
										__( 'In %s', 'pearblog-engine' ),
										human_time_diff( time(), $next_scheduled )
									)
								);
								echo ' (' . esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_scheduled ) ) . ')';
							} else {
								esc_html_e( 'Not scheduled', 'pearblog-engine' );
							}
							?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Topics in Queue', 'pearblog-engine' ); ?></strong></td>
						<td><?php echo esc_html( (string) $queue->count() ); ?></td>
					</tr>
				</tbody>
			</table>

			<p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
					<input type="hidden" name="action" value="pearblog_run_pipeline" />
					<?php wp_nonce_field( 'pearblog_run_pipeline' ); ?>
					<?php
					submit_button(
						__( 'Run Pipeline Now', 'pearblog-engine' ),
						'primary',
						'',
						false,
						$queue->count() === 0 ? [ 'disabled' => 'disabled', 'title' => __( 'Add topics to the queue first', 'pearblog-engine' ) ] : []
					);
					?>
				</form>
			</p>

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

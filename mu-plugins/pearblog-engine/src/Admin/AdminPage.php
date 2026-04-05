<?php
/**
 * Admin page – tabbed settings and topic queue management UI.
 *
 * @package PearBlogEngine\Admin
 */

declare(strict_types=1);

namespace PearBlogEngine\Admin;

use PearBlogEngine\Content\TopicQueue;
use PearBlogEngine\Tenant\TenantContext;

/**
 * Registers a top-level WordPress admin page for PearBlog Engine.
 *
 * Tabs: General | AI Images | Monetization | Email | Automation | Queue
 */
class AdminPage {

	private const MENU_SLUG  = 'pearblog-engine';
	private const OPTION_GRP = 'pearblog_settings';

	/** @var list<string> Valid tab IDs. */
	private const TABS = [ 'general', 'images', 'monetization', 'email', 'automation', 'queue' ];

	/**
	 * Attach WordPress admin hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'admin_post_pearblog_add_topics', [ $this, 'handle_add_topics' ] );
		add_action( 'admin_post_pearblog_clear_queue', [ $this, 'handle_clear_queue' ] );
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
			'dashicons-rest-api',
			25
		);
	}

	public function register_settings(): void {
		// General settings.
		register_setting( self::OPTION_GRP, 'pearblog_openai_api_key',       [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_industry',             [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_tone',                 [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_publish_rate',         [ 'sanitize_callback' => 'absint' ] );
		register_setting( self::OPTION_GRP, 'pearblog_language',             [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_autonomous_mode',      [ 'sanitize_callback' => [ $this, 'sanitize_checkbox' ] ] );

		// Image generation settings.
		register_setting( self::OPTION_GRP, 'pearblog_enable_image_generation', [ 'sanitize_callback' => [ $this, 'sanitize_checkbox' ] ] );
		register_setting( self::OPTION_GRP, 'pearblog_image_style',             [ 'sanitize_callback' => 'sanitize_text_field' ] );

		// Monetization settings.
		register_setting( self::OPTION_GRP, 'pearblog_monetization',         [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_adsense_publisher_id', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_booking_affiliate_id', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_saas_products',        [ 'sanitize_callback' => [ $this, 'sanitize_saas_products' ] ] );

		// Email marketing settings.
		register_setting( self::OPTION_GRP, 'pearblog_esp_provider',       [ 'sanitize_callback' => 'sanitize_key' ] );
		register_setting( self::OPTION_GRP, 'pearblog_mailchimp_api_key',  [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_mailchimp_list_id',  [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_convertkit_api_key', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_convertkit_form_id', [ 'sanitize_callback' => 'sanitize_text_field' ] );

		// Automation API key.
		register_setting( self::OPTION_GRP, 'pearblog_api_key', [ 'sanitize_callback' => 'sanitize_text_field' ] );
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
			), 'success', 'queue' );
		}

		$this->redirect_with_notice(
			__( 'No topics to add.', 'pearblog-engine' ),
			'warning',
			'queue'
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
			'success',
			'queue'
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

		// Determine active tab.
		$active_tab = isset( $_GET['tab'] ) && in_array( $_GET['tab'], self::TABS, true )
			? $_GET['tab']
			: 'general';

		// Flush notice from redirect.
		$notice = '';
		if ( isset( $_GET['pearblog_notice'] ) ) {
			$type   = isset( $_GET['pearblog_type'] ) && 'success' === $_GET['pearblog_type'] ? 'success' : 'warning';
			$notice = '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>'
				. esc_html( urldecode( (string) $_GET['pearblog_notice'] ) )
				. '</p></div>';
		}

		$tab_labels = [
			'general'      => __( 'General', 'pearblog-engine' ),
			'images'       => __( 'AI Images', 'pearblog-engine' ),
			'monetization' => __( 'Monetization', 'pearblog-engine' ),
			'email'        => __( 'Email', 'pearblog-engine' ),
			'automation'   => __( 'Automation', 'pearblog-engine' ),
			'queue'        => __( 'Queue', 'pearblog-engine' ),
		];

		?>
		<div class="wrap pb-engine-wrap">
			<h1 class="pb-page-title">
				<?php esc_html_e( 'PearBlog Engine', 'pearblog-engine' ); ?>
				<span class="pb-version-badge">v5.1</span>
			</h1>

			<?php echo wp_kses_post( $notice ); ?>

			<!-- Tab navigation -->
			<div class="pb-engine-tabs" role="tablist">
				<?php foreach ( $tab_labels as $tab_id => $tab_label ) : ?>
					<button
						class="pb-tab-btn <?php echo $tab_id === $active_tab ? 'active' : ''; ?>"
						data-tab="<?php echo esc_attr( $tab_id ); ?>"
						role="tab"
						aria-selected="<?php echo $tab_id === $active_tab ? 'true' : 'false'; ?>"
						aria-controls="pb-tab-panel-<?php echo esc_attr( $tab_id ); ?>"
					><?php echo esc_html( $tab_label ); ?></button>
				<?php endforeach; ?>
			</div>

			<!-- Settings form (wraps all tabs except Queue) -->
			<form method="post" action="options.php">
				<?php settings_fields( self::OPTION_GRP ); ?>

				<!-- ═══ Tab: General ══════════════════════════════════════════ -->
				<div id="pb-tab-panel-general"
					 class="pb-tab-panel <?php echo 'general' === $active_tab ? 'active' : ''; ?>"
					 role="tabpanel">

					<h3 class="pb-section-title"><?php esc_html_e( 'API Keys', 'pearblog-engine' ); ?></h3>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="pearblog_openai_api_key"><?php esc_html_e( 'OpenAI API Key', 'pearblog-engine' ); ?></label></th>
							<td>
								<input type="password" id="pearblog_openai_api_key" name="pearblog_openai_api_key" value="<?php echo esc_attr( get_option( 'pearblog_openai_api_key', '' ) ); ?>" class="regular-text" autocomplete="off" />
								<p class="description"><?php esc_html_e( 'Used for content generation (GPT-4) and image generation (DALL-E 3).', 'pearblog-engine' ); ?></p>
							</td>
						</tr>
					</table>

					<h3 class="pb-section-title"><?php esc_html_e( 'Content Settings', 'pearblog-engine' ); ?></h3>
					<table class="form-table" role="presentation">
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
									foreach ( [ 'neutral', 'professional', 'conversational', 'authoritative', 'friendly' ] as $t ) {
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
							<th scope="row"><label for="pearblog_language"><?php esc_html_e( 'Language (ISO 639-1)', 'pearblog-engine' ); ?></label></th>
							<td><input type="text" id="pearblog_language" name="pearblog_language" value="<?php echo esc_attr( get_option( 'pearblog_language', 'en' ) ); ?>" class="small-text" maxlength="5" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="pearblog_publish_rate"><?php esc_html_e( 'Publish Rate (articles/cycle)', 'pearblog-engine' ); ?></label></th>
							<td><input type="number" id="pearblog_publish_rate" name="pearblog_publish_rate" value="<?php echo esc_attr( get_option( 'pearblog_publish_rate', 1 ) ); ?>" min="1" max="50" class="small-text" /></td>
						</tr>
					</table>

					<h3 class="pb-section-title"><?php esc_html_e( 'Autonomous Mode', 'pearblog-engine' ); ?></h3>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="pearblog_autonomous_mode"><?php esc_html_e( 'Enable Autonomous Mode', 'pearblog-engine' ); ?></label></th>
							<td>
								<div class="pb-autonomous-toggle">
									<input type="checkbox" id="pearblog_autonomous_mode" name="pearblog_autonomous_mode" value="1" <?php checked( (bool) get_option( 'pearblog_autonomous_mode', false ) ); ?> />
									<label for="pearblog_autonomous_mode"><?php esc_html_e( 'Run pipeline automatically on cron schedule', 'pearblog-engine' ); ?></label>
								</div>
								<p class="description"><?php esc_html_e( 'When enabled the cron manager publishes articles automatically from the queue every cycle.', 'pearblog-engine' ); ?></p>
							</td>
						</tr>
					</table>

					<h3 class="pb-section-title"><?php esc_html_e( 'Active Site Profile', 'pearblog-engine' ); ?></h3>
					<div class="pb-profile-box"><?php echo esc_html( $context->profile->summary() ); ?></div>

					<?php submit_button(); ?>
				</div>

				<!-- ═══ Tab: AI Images ════════════════════════════════════════ -->
				<div id="pb-tab-panel-images"
					 class="pb-tab-panel <?php echo 'images' === $active_tab ? 'active' : ''; ?>"
					 role="tabpanel">

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="pearblog_enable_image_generation"><?php esc_html_e( 'Enable Image Generation', 'pearblog-engine' ); ?></label></th>
							<td>
								<label>
									<input type="checkbox" id="pearblog_enable_image_generation" name="pearblog_enable_image_generation" value="1" <?php checked( (bool) get_option( 'pearblog_enable_image_generation', true ) ); ?> />
									<?php esc_html_e( 'Automatically generate featured images using DALL-E 3', 'pearblog-engine' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Each article gets a unique AI-generated featured image. Requires OpenAI API key.', 'pearblog-engine' ); ?></p>
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
								<p class="description"><?php esc_html_e( 'Visual style for AI-generated images.', 'pearblog-engine' ); ?></p>
							</td>
						</tr>
					</table>
					<?php submit_button(); ?>
				</div>

				<!-- ═══ Tab: Monetization ═════════════════════════════════════ -->
				<div id="pb-tab-panel-monetization"
					 class="pb-tab-panel <?php echo 'monetization' === $active_tab ? 'active' : ''; ?>"
					 role="tabpanel">

					<h3 class="pb-section-title"><?php esc_html_e( 'Strategy', 'pearblog-engine' ); ?></h3>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="pearblog_monetization"><?php esc_html_e( 'Monetisation Strategy', 'pearblog-engine' ); ?></label></th>
							<td>
								<select id="pearblog_monetization" name="pearblog_monetization">
									<?php
									$current_mon = get_option( 'pearblog_monetization', 'adsense' );
									$strategies  = [
										'adsense'   => 'AdSense (v1)',
										'affiliate' => 'Affiliate (v2)',
										'saas'      => 'SaaS (v3)',
									];
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
							<th scope="row"><label for="pearblog_adsense_publisher_id"><?php esc_html_e( 'AdSense Publisher ID', 'pearblog-engine' ); ?></label></th>
							<td><input type="text" id="pearblog_adsense_publisher_id" name="pearblog_adsense_publisher_id" value="<?php echo esc_attr( get_option( 'pearblog_adsense_publisher_id', '' ) ); ?>" class="regular-text" placeholder="ca-pub-XXXXXXXXXXXXXXXX" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="pearblog_booking_affiliate_id"><?php esc_html_e( 'Booking.com Affiliate ID', 'pearblog-engine' ); ?></label></th>
							<td>
								<input type="text" id="pearblog_booking_affiliate_id" name="pearblog_booking_affiliate_id" value="<?php echo esc_attr( get_option( 'pearblog_booking_affiliate_id', '' ) ); ?>" class="regular-text" placeholder="1234567" />
								<p class="description"><?php esc_html_e( 'Partner/affiliate ID (aid). Required for Affiliate (v2) strategy.', 'pearblog-engine' ); ?></p>
							</td>
						</tr>
					</table>

					<h3 class="pb-section-title"><?php esc_html_e( 'SaaS CTA Products (v3)', 'pearblog-engine' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Articles are scanned for matching keywords and a CTA box is injected automatically when "SaaS (v3)" strategy is active.', 'pearblog-engine' ); ?></p>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="pearblog_saas_products"><?php esc_html_e( 'SaaS Products (JSON)', 'pearblog-engine' ); ?></label></th>
							<td>
								<textarea id="pearblog_saas_products" name="pearblog_saas_products" rows="10" class="large-text code"><?php echo esc_textarea( get_option( 'pearblog_saas_products', '[]' ) ); ?></textarea>
								<p class="description"><?php esc_html_e( 'JSON array. Each product: {"name":"…","url":"https://…","keywords":["kw1","kw2"],"description":"…","cta_text":"…"}', 'pearblog-engine' ); ?></p>
							</td>
						</tr>
					</table>
					<?php submit_button(); ?>
				</div>

				<!-- ═══ Tab: Email ════════════════════════════════════════════ -->
				<div id="pb-tab-panel-email"
					 class="pb-tab-panel <?php echo 'email' === $active_tab ? 'active' : ''; ?>"
					 role="tabpanel">

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
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="pearblog_mailchimp_api_key"><?php esc_html_e( 'Mailchimp API Key', 'pearblog-engine' ); ?></label></th>
							<td>
								<input type="password" id="pearblog_mailchimp_api_key" name="pearblog_mailchimp_api_key" value="<?php echo esc_attr( get_option( 'pearblog_mailchimp_api_key', '' ) ); ?>" class="regular-text" autocomplete="off" />
								<p class="description"><?php esc_html_e( 'Format: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx-us1', 'pearblog-engine' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="pearblog_mailchimp_list_id"><?php esc_html_e( 'Mailchimp List ID', 'pearblog-engine' ); ?></label></th>
							<td><input type="text" id="pearblog_mailchimp_list_id" name="pearblog_mailchimp_list_id" value="<?php echo esc_attr( get_option( 'pearblog_mailchimp_list_id', '' ) ); ?>" class="regular-text" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="pearblog_convertkit_api_key"><?php esc_html_e( 'ConvertKit API Key', 'pearblog-engine' ); ?></label></th>
							<td><input type="password" id="pearblog_convertkit_api_key" name="pearblog_convertkit_api_key" value="<?php echo esc_attr( get_option( 'pearblog_convertkit_api_key', '' ) ); ?>" class="regular-text" autocomplete="off" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="pearblog_convertkit_form_id"><?php esc_html_e( 'ConvertKit Form ID', 'pearblog-engine' ); ?></label></th>
							<td><input type="text" id="pearblog_convertkit_form_id" name="pearblog_convertkit_form_id" value="<?php echo esc_attr( get_option( 'pearblog_convertkit_form_id', '' ) ); ?>" class="regular-text" /></td>
						</tr>
					</table>
					<?php submit_button(); ?>
				</div>

				<!-- ═══ Tab: Automation ═══════════════════════════════════════ -->
				<div id="pb-tab-panel-automation"
					 class="pb-tab-panel <?php echo 'automation' === $active_tab ? 'active' : ''; ?>"
					 role="tabpanel">

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="pearblog_api_key"><?php esc_html_e( 'Automation API Key', 'pearblog-engine' ); ?></label></th>
							<td>
								<input type="password" id="pearblog_api_key" name="pearblog_api_key" value="<?php echo esc_attr( get_option( 'pearblog_api_key', '' ) ); ?>" class="regular-text" autocomplete="off" />
								<p class="description"><?php esc_html_e( 'Bearer token for external automation (GitHub Actions). Store as API_KEY secret in your repository.', 'pearblog-engine' ); ?></p>
							</td>
						</tr>
					</table>

					<h3 class="pb-section-title"><?php esc_html_e( 'REST API Endpoints', 'pearblog-engine' ); ?></h3>
					<table class="widefat striped" style="max-width:640px;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Endpoint', 'pearblog-engine' ); ?></th>
								<th><?php esc_html_e( 'Method', 'pearblog-engine' ); ?></th>
								<th><?php esc_html_e( 'Description', 'pearblog-engine' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><code>/pearblog/v1/automation/status</code></td>
								<td>GET</td>
								<td><?php esc_html_e( 'Queue health, profile, cron status', 'pearblog-engine' ); ?></td>
							</tr>
							<tr>
								<td><code>/pearblog/v1/automation/create-content</code></td>
								<td>POST</td>
								<td><?php esc_html_e( 'Add topic & run pipeline immediately', 'pearblog-engine' ); ?></td>
							</tr>
							<tr>
								<td><code>/pearblog/v1/automation/process-content</code></td>
								<td>POST</td>
								<td><?php esc_html_e( 'Trigger next pipeline cycle', 'pearblog-engine' ); ?></td>
							</tr>
						</tbody>
					</table>

					<?php submit_button(); ?>
				</div>

			</form><!-- end settings form -->

			<!-- ═══ Tab: Queue (standalone forms) ════════════════════════════ -->
			<div id="pb-tab-panel-queue"
				 class="pb-tab-panel <?php echo 'queue' === $active_tab ? 'active' : ''; ?>"
				 role="tabpanel">

				<p>
					<?php
					$count = $queue->count();
					echo esc_html( sprintf(
						/* translators: %d: number of topics in queue */
						_n( '%d topic in queue.', '%d topics in queue.', $count, 'pearblog-engine' ),
						$count
					) );
					?>
				</p>

				<?php if ( $queue->count() > 0 ) : ?>
					<div class="pb-queue-list">
						<ol>
							<?php foreach ( $queue->all() as $topic ) : ?>
								<li><?php echo esc_html( $topic ); ?></li>
							<?php endforeach; ?>
						</ol>
					</div>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom: 16px;">
						<input type="hidden" name="action" value="pearblog_clear_queue" />
						<?php wp_nonce_field( 'pearblog_clear_queue' ); ?>
						<?php submit_button( __( 'Clear Queue', 'pearblog-engine' ), 'delete', '', false ); ?>
					</form>
				<?php endif; ?>

				<h3 class="pb-section-title"><?php esc_html_e( 'Add Topics', 'pearblog-engine' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="pearblog_add_topics" />
					<?php wp_nonce_field( 'pearblog_add_topics' ); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="pearblog_topics"><?php esc_html_e( 'Topics (one per line)', 'pearblog-engine' ); ?></label></th>
							<td><textarea id="pearblog_topics" name="pearblog_topics" rows="8" class="large-text"></textarea></td>
						</tr>
					</table>
					<?php submit_button( __( 'Add to Queue', 'pearblog-engine' ) ); ?>
				</form>
			</div>

		</div><!-- .pb-engine-wrap -->

		<script>
		(function() {
			var tabs     = document.querySelectorAll('.pb-tab-btn');
			var panels   = document.querySelectorAll('.pb-tab-panel');
			var urlParam = new URLSearchParams(window.location.search);

			tabs.forEach(function(btn) {
				btn.addEventListener('click', function() {
					var target = this.dataset.tab;

					tabs.forEach(function(b) {
						b.classList.toggle('active', b.dataset.tab === target);
						b.setAttribute('aria-selected', b.dataset.tab === target ? 'true' : 'false');
					});

					panels.forEach(function(panel) {
						panel.classList.toggle('active', panel.id === 'pb-tab-panel-' + target);
					});

					// Update URL without reload so the active tab survives page refresh.
					urlParam.set('tab', target);
					history.replaceState(null, '', '?' + urlParam.toString());
				});
			});
		}());
		</script>
		<?php
	}

	// -----------------------------------------------------------------------
	// Admin assets
	// -----------------------------------------------------------------------

	/**
	 * Enqueue admin CSS for the PearBlog Engine page.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( 'toplevel_page_' . self::MENU_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'pearblog-engine-admin',
			PEARBLOG_ENGINE_URL . 'assets/css/admin.css',
			[],
			PEARBLOG_ENGINE_VERSION
		);
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
	 * Redirect back to the Engine page with a notice and optional tab.
	 *
	 * @param string $message Notice text.
	 * @param string $type    'success' or 'warning'.
	 * @param string $tab     Tab ID to return to.
	 */
	private function redirect_with_notice( string $message, string $type = 'success', string $tab = 'general' ): void {
		wp_safe_redirect( add_query_arg(
			[
				'page'            => self::MENU_SLUG,
				'tab'             => $tab,
				'pearblog_notice' => rawurlencode( $message ),
				'pearblog_type'   => $type,
			],
			admin_url( 'admin.php' )
		) );
		exit;
	}
}


	/**
	 * Attach WordPress admin hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ] );
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

		// Email marketing settings.
		register_setting( self::OPTION_GRP, 'pearblog_esp_provider',       [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_mailchimp_api_key',  [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_mailchimp_list_id',  [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_convertkit_api_key', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( self::OPTION_GRP, 'pearblog_convertkit_form_id', [ 'sanitize_callback' => 'sanitize_text_field' ] );

		// Automation API settings.
		register_setting( self::OPTION_GRP, 'pearblog_api_key', [ 'sanitize_callback' => 'sanitize_text_field' ] );
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
		<div class="wrap pearblog-engine-wrap">
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
				</table>
				<?php submit_button(); ?>
			</form>

			<hr />

			<!-- Site profile summary -->
			<h2><?php esc_html_e( 'Active Site Profile', 'pearblog-engine' ); ?></h2>
			<div class="pb-profile-box"><?php echo esc_html( $context->profile->summary() ); ?></div>

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
				<div class="pb-queue-list">
					<ol>
						<?php foreach ( $queue->all() as $topic ) : ?>
							<li><?php echo esc_html( $topic ); ?></li>
						<?php endforeach; ?>
					</ol>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 12px;">
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
	// Admin styles
	// -----------------------------------------------------------------------

	/**
	 * Enqueue admin styles for the settings page.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_admin_styles( string $hook ): void {
		if ( 'settings_page_' . self::MENU_SLUG !== $hook ) {
			return;
		}

		wp_add_inline_style( 'wp-admin', '
			.pearblog-engine-wrap {
				max-width: 900px;
			}
			.pearblog-engine-wrap .form-table th {
				width: 220px;
			}
			.pearblog-engine-wrap h2 {
				margin-top: 2em;
				padding-bottom: 8px;
				border-bottom: 1px solid #c3c4c7;
			}
			.pearblog-engine-wrap h2:first-of-type {
				margin-top: 0;
			}
			.pb-profile-box {
				background: #f0f6fc;
				border: 1px solid #c3c4c7;
				border-left: 4px solid #2563eb;
				padding: 12px 16px;
				border-radius: 4px;
				font-size: 13px;
				line-height: 1.6;
			}
			.pb-queue-list {
				background: #fff;
				border: 1px solid #c3c4c7;
				border-radius: 4px;
				padding: 12px 16px;
				max-height: 300px;
				overflow-y: auto;
			}
			.pb-queue-list ol {
				margin: 0;
				padding-left: 1.5em;
			}
			.pb-queue-list li {
				padding: 4px 0;
				border-bottom: 1px solid #f0f0f1;
			}
			.pb-queue-list li:last-child {
				border-bottom: none;
			}
		' );
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

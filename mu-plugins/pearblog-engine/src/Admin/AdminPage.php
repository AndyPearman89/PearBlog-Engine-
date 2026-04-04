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
		add_action( 'admin_post_pearblog_add_topics', [ $this, 'handle_add_topics' ] );
		add_action( 'admin_post_pearblog_clear_queue', [ $this, 'handle_clear_queue' ] );
		add_action( 'admin_post_pearblog_generate_images', [ $this, 'handle_generate_images' ] );
		add_action( 'admin_post_pearblog_run_seo_audit', [ $this, 'handle_seo_audit' ] );
		add_action( 'admin_post_pearblog_fix_alt_texts', [ $this, 'handle_fix_alt_texts' ] );
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
				</table>
				<?php submit_button(); ?>
			</form>

			<hr />

			<!-- Programmatic SEO -->
			<h2><?php esc_html_e( 'Programmatic SEO', 'pearblog-engine' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Automated SEO optimization: Schema.org markup, Open Graph tags, keyword density analysis, and bulk SEO audit for all published posts.', 'pearblog-engine' ); ?></p>

			<?php
			$seo_engine = new ProgrammaticSEO();
			$seo_audit  = $seo_engine->bulk_audit( 20 );
			?>

			<table class="widefat striped" style="max-width: 700px; margin: 15px 0;">
				<thead>
					<tr>
						<th colspan="2"><?php esc_html_e( 'SEO Audit Summary', 'pearblog-engine' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php esc_html_e( 'Posts Audited', 'pearblog-engine' ); ?></td>
						<td><strong><?php echo esc_html( (string) $seo_audit['posts_audited'] ); ?></strong></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Issues Found', 'pearblog-engine' ); ?></td>
						<td>
							<strong style="color: <?php echo $seo_audit['issues_found'] > 0 ? '#d63638' : '#00a32a'; ?>;">
								<?php echo esc_html( (string) $seo_audit['issues_found'] ); ?>
							</strong>
						</td>
					</tr>
				</tbody>
			</table>

			<?php if ( ! empty( $seo_audit['issues'] ) ) : ?>
				<table class="widefat striped" style="max-width: 900px; margin: 15px 0;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Post', 'pearblog-engine' ); ?></th>
							<th><?php esc_html_e( 'Issues', 'pearblog-engine' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( array_slice( $seo_audit['issues'], 0, 15, true ) as $pid => $data ) : ?>
							<tr>
								<td>
									<a href="<?php echo esc_url( get_edit_post_link( $pid, 'raw' ) ); ?>">
										<?php echo esc_html( $data['title'] ); ?>
									</a>
								</td>
								<td>
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
									$labels = array_map( function ( $issue ) use ( $issue_labels ) {
										return $issue_labels[ $issue ] ?? $issue;
									}, $data['issues'] );
									echo esc_html( implode( ', ', $labels ) );
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin: 10px 0;">
				<input type="hidden" name="action" value="pearblog_run_seo_audit" />
				<?php wp_nonce_field( 'pearblog_run_seo_audit' ); ?>
				<?php submit_button( __( 'Run SEO Audit & Auto-Fix', 'pearblog-engine' ), 'secondary', '', false ); ?>
				<p class="description"><?php esc_html_e( 'Scans all posts and auto-generates missing meta descriptions.', 'pearblog-engine' ); ?></p>
			</form>

			<hr />

			<!-- Image Generator & Analysis -->
			<h2><?php esc_html_e( 'Image Generator & Analysis', 'pearblog-engine' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Generate AI images from keywords, audit your media library, and fix missing alt texts.', 'pearblog-engine' ); ?></p>

			<?php
			$img_analyzer = new ImageAnalyzer();
			$img_summary  = $img_analyzer->get_summary();
			?>

			<table class="widefat striped" style="max-width: 700px; margin: 15px 0;">
				<thead>
					<tr>
						<th colspan="2"><?php esc_html_e( 'Image Library Summary', 'pearblog-engine' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php esc_html_e( 'Total Images', 'pearblog-engine' ); ?></td>
						<td><strong><?php echo esc_html( (string) $img_summary['total_images'] ); ?></strong></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Total Published Posts', 'pearblog-engine' ); ?></td>
						<td><strong><?php echo esc_html( (string) $img_summary['total_posts'] ); ?></strong></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Posts With Featured Image', 'pearblog-engine' ); ?></td>
						<td>
							<strong style="color: #00a32a;"><?php echo esc_html( (string) $img_summary['posts_with_images'] ); ?></strong>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Posts Without Featured Image', 'pearblog-engine' ); ?></td>
						<td>
							<strong style="color: <?php echo $img_summary['posts_without_images'] > 0 ? '#d63638' : '#00a32a'; ?>;">
								<?php echo esc_html( (string) $img_summary['posts_without_images'] ); ?>
							</strong>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'AI-Generated Images', 'pearblog-engine' ); ?></td>
						<td><strong><?php echo esc_html( (string) $img_summary['ai_generated'] ); ?></strong></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Images Missing Alt Text', 'pearblog-engine' ); ?></td>
						<td>
							<strong style="color: <?php echo $img_summary['missing_alt'] > 0 ? '#dba617' : '#00a32a'; ?>;">
								<?php echo esc_html( (string) $img_summary['missing_alt'] ); ?>
							</strong>
						</td>
					</tr>
				</tbody>
			</table>

			<!-- Generate images from keywords -->
			<h3><?php esc_html_e( 'Generate Image from Keywords', 'pearblog-engine' ); ?></h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="pearblog_generate_images" />
				<?php wp_nonce_field( 'pearblog_generate_images' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="pearblog_image_keywords"><?php esc_html_e( 'Keywords / Title', 'pearblog-engine' ); ?></label></th>
						<td>
							<input type="text" id="pearblog_image_keywords" name="pearblog_image_keywords" class="large-text" placeholder="<?php esc_attr_e( 'e.g., Mountain landscape Tatry sunrise', 'pearblog-engine' ); ?>" />
							<p class="description"><?php esc_html_e( 'Enter keywords or a descriptive title. The image will be generated using DALL-E 3 in the style configured above.', 'pearblog-engine' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Generate Image', 'pearblog-engine' ), 'primary', '', false ); ?>
			</form>

			<?php
			// Show posts without images for batch generation.
			$no_image_posts = $img_analyzer->find_posts_without_featured_image( 10 );
			if ( ! empty( $no_image_posts ) ) :
			?>
				<h3><?php esc_html_e( 'Posts Without Featured Images', 'pearblog-engine' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Select posts to generate featured images based on their titles and keywords.', 'pearblog-engine' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="pearblog_generate_images" />
					<?php wp_nonce_field( 'pearblog_generate_images' ); ?>
					<table class="widefat striped" style="max-width: 900px; margin: 10px 0;">
						<thead>
							<tr>
								<th style="width: 30px;"><input type="checkbox" id="pearblog-select-all-posts" /></th>
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
									<td><em><?php echo esc_html( implode( ', ', $nip['keywords'] ) ); ?></em></td>
									<td><?php echo esc_html( $nip['date'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<input type="hidden" name="pearblog_image_keywords" value="" />
					<?php submit_button( __( 'Generate Images for Selected Posts', 'pearblog-engine' ), 'secondary', '', false ); ?>
				</form>
				<script>
				document.getElementById('pearblog-select-all-posts')?.addEventListener('change', function() {
					var checkboxes = document.querySelectorAll('input[name="pearblog_image_post_ids[]"]');
					for (var i = 0; i < checkboxes.length; i++) {
						checkboxes[i].checked = this.checked;
					}
				});
				</script>
			<?php endif; ?>

			<!-- Fix missing alt texts -->
			<?php if ( $img_summary['missing_alt'] > 0 ) : ?>
				<h3><?php esc_html_e( 'Fix Missing Alt Texts', 'pearblog-engine' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin: 10px 0;">
					<input type="hidden" name="action" value="pearblog_fix_alt_texts" />
					<?php wp_nonce_field( 'pearblog_fix_alt_texts' ); ?>
					<?php submit_button(
						sprintf(
							/* translators: %d: number of images */
							__( 'Auto-Fix %d Missing Alt Texts', 'pearblog-engine' ),
							$img_summary['missing_alt']
						),
						'secondary',
						'',
						false
					); ?>
					<p class="description"><?php esc_html_e( 'Generates alt text from image titles and filenames for SEO and accessibility.', 'pearblog-engine' ); ?></p>
				</form>
			<?php endif; ?>

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
			@unlink( $temp_file );
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

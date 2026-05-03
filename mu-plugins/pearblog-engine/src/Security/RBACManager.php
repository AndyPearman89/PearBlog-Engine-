<?php
/**
 * RBAC Manager – Role-Based Access Control for PearBlog Engine.
 *
 * Registers granular WordPress custom capabilities for each engine feature
 * and assigns them to appropriate built-in roles.
 *
 * Custom capabilities:
 *   pearblog_generate_content     – trigger AI article generation
 *   pearblog_manage_queue         – manage topic queue
 *   pearblog_view_analytics       – access analytics dashboards
 *   pearblog_manage_monetization  – configure monetization settings
 *   pearblog_approve_content      – approve articles in the approval workflow
 *   pearblog_manage_settings      – change engine configuration options
 *   pearblog_view_roi             – access revenue/ROI dashboards
 *   pearblog_manage_billing       – access billing settings
 *
 * Default capability assignments:
 *   administrator  – all capabilities
 *   editor         – generate_content, manage_queue, view_analytics, approve_content
 *   author         – generate_content, view_analytics
 *   contributor    – view_analytics (read-only)
 *
 * @package PearBlogEngine\Security
 */

declare(strict_types=1);

namespace PearBlogEngine\Security;

/**
 * Manages PearBlog Engine capability registration and assignment.
 */
class RBACManager {

	/** Option key storing per-role capability overrides. */
	public const OPTION_OVERRIDES = 'pearblog_rbac_overrides';

	/** All PearBlog custom capabilities. */
	public const CAPABILITIES = [
		'pearblog_generate_content',
		'pearblog_manage_queue',
		'pearblog_view_analytics',
		'pearblog_manage_monetization',
		'pearblog_approve_content',
		'pearblog_manage_settings',
		'pearblog_view_roi',
		'pearblog_manage_billing',
	];

	/** Default role → capability assignments. */
	private const DEFAULT_ASSIGNMENTS = [
		'administrator' => [
			'pearblog_generate_content',
			'pearblog_manage_queue',
			'pearblog_view_analytics',
			'pearblog_manage_monetization',
			'pearblog_approve_content',
			'pearblog_manage_settings',
			'pearblog_view_roi',
			'pearblog_manage_billing',
		],
		'editor' => [
			'pearblog_generate_content',
			'pearblog_manage_queue',
			'pearblog_view_analytics',
			'pearblog_approve_content',
			'pearblog_view_roi',
		],
		'author' => [
			'pearblog_generate_content',
			'pearblog_view_analytics',
		],
		'contributor' => [
			'pearblog_view_analytics',
		],
	];

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'assign_capabilities' ] );
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_post_pearblog_save_rbac', [ $this, 'handle_save' ] );
	}

	/**
	 * Assign capabilities to roles based on defaults + overrides.
	 */
	public function assign_capabilities(): void {
		$overrides = (array) get_option( self::OPTION_OVERRIDES, [] );

		foreach ( self::DEFAULT_ASSIGNMENTS as $role_name => $default_caps ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}

			$caps = $overrides[ $role_name ] ?? $default_caps;

			foreach ( self::CAPABILITIES as $cap ) {
				if ( in_array( $cap, $caps, true ) ) {
					$role->add_cap( $cap, true );
				} else {
					$role->add_cap( $cap, false );
				}
			}
		}
	}

	// -----------------------------------------------------------------------
	// Admin UI
	// -----------------------------------------------------------------------

	/**
	 * Add RBAC settings page to admin menu.
	 */
	public function add_admin_menu(): void {
		add_submenu_page(
			'pearblog-engine',
			__( 'Access Control', 'pearblog-engine' ),
			__( 'Access Control', 'pearblog-engine' ),
			'manage_options',
			'pearblog-rbac',
			[ $this, 'render_admin_page' ]
		);
	}

	/**
	 * Handle RBAC settings save.
	 */
	public function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized', 403 );
		}

		check_admin_referer( 'pearblog_rbac_save' );

		$overrides = [];
		foreach ( array_keys( self::DEFAULT_ASSIGNMENTS ) as $role_name ) {
			$overrides[ $role_name ] = [];
			foreach ( self::CAPABILITIES as $cap ) {
				if ( ! empty( $_POST['cap'][ $role_name ][ $cap ] ) ) {
					$overrides[ $role_name ][] = $cap;
				}
			}
		}

		update_option( self::OPTION_OVERRIDES, $overrides );
		$this->assign_capabilities();

		wp_redirect( admin_url( 'admin.php?page=pearblog-rbac&saved=1' ) );
		exit;
	}

	/**
	 * Render the RBAC admin page.
	 */
	public function render_admin_page(): void {
		$overrides = (array) get_option( self::OPTION_OVERRIDES, [] );
		$roles     = array_keys( self::DEFAULT_ASSIGNMENTS );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( '🔐 Access Control (RBAC)', 'pearblog-engine' ); ?></h1>

			<?php if ( ! empty( $_GET['saved'] ) ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Settings saved.', 'pearblog-engine' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="pearblog_save_rbac">
				<?php wp_nonce_field( 'pearblog_rbac_save' ); ?>

				<table class="widefat">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Capability', 'pearblog-engine' ); ?></th>
							<?php foreach ( $roles as $role ) : ?>
								<th><?php echo esc_html( ucfirst( $role ) ); ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( self::CAPABILITIES as $cap ) : ?>
						<tr>
							<td><code><?php echo esc_html( $cap ); ?></code></td>
							<?php foreach ( $roles as $role ) :
								$default  = in_array( $cap, self::DEFAULT_ASSIGNMENTS[ $role ] ?? [], true );
								$override = $overrides[ $role ] ?? null;
								$checked  = null !== $override
									? in_array( $cap, $override, true )
									: $default;
							?>
								<td>
									<input type="checkbox"
										name="cap[<?php echo esc_attr( $role ); ?>][<?php echo esc_attr( $cap ); ?>]"
										value="1"
										<?php checked( $checked ); ?>
										<?php if ( 'administrator' === $role ) { echo 'disabled checked'; } ?>>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Permissions', 'pearblog-engine' ); ?></button>
				</p>
			</form>
		</div>
		<?php
	}

	// -----------------------------------------------------------------------
	// Static helpers
	// -----------------------------------------------------------------------

	/**
	 * Check if the current user has a specific PearBlog capability.
	 *
	 * @param string $capability PearBlog capability slug.
	 * @return bool
	 */
	public static function current_user_can( string $capability ): bool {
		return current_user_can( $capability ) || current_user_can( 'manage_options' );
	}
}

<?php
/**
 * Content calendar – schedule topics for specific dates in WP Admin.
 *
 * Provides:
 *  - A visual calendar page under Tools → Content Calendar.
 *  - REST endpoint to read/write scheduled topics per date.
 *  - Integration with TopicQueue: on the scheduled date the topic is auto-pushed.
 *
 * Storage: WordPress option `pearblog_content_calendar` → JSON array of
 *   { date: 'YYYY-MM-DD', topic: string, status: 'pending'|'queued'|'published' }
 *
 * @package PearBlogEngine\Admin
 */

declare(strict_types=1);

namespace PearBlogEngine\Admin;

use PearBlogEngine\Content\TopicQueue;

/**
 * Registers the Content Calendar admin page and REST endpoints.
 */
class ContentCalendar {

	private const MENU_SLUG    = 'pearblog-calendar';
	private const OPTION_KEY   = 'pearblog_content_calendar';
	private const REST_NS      = 'pearblog/v1';
	private const CRON_HOOK    = 'pearblog_calendar_dispatch';

	/**
	 * Attach WordPress hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'admin_post_pearblog_calendar_save', [ $this, 'handle_save' ] );
		add_action( self::CRON_HOOK, [ $this, 'dispatch_today' ] );
		add_action( 'init', [ $this, 'maybe_schedule' ] );
	}

	/**
	 * Schedule daily dispatch cron if not already scheduled.
	 */
	public function maybe_schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			// Run at 01:00 UTC daily.
			wp_schedule_event( strtotime( 'tomorrow 01:00' ), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Add content calendar page under Tools.
	 */
	public function add_menu(): void {
		add_management_page(
			__( 'Content Calendar', 'pearblog-engine' ),
			__( 'Content Calendar', 'pearblog-engine' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Register REST routes for AJAX calendar interactions.
	 */
	public function register_routes(): void {
		register_rest_route( self::REST_NS, '/calendar', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ $this, 'api_get_calendar' ],
			'permission_callback' => fn() => current_user_can( 'manage_options' ),
		] );

		register_rest_route( self::REST_NS, '/calendar', [
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'api_add_entry' ],
			'permission_callback' => fn() => current_user_can( 'manage_options' ),
			'args'                => [
				'date'  => [ 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
				'topic' => [ 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
			],
		] );

		register_rest_route( self::REST_NS, '/calendar/(?P<date>\d{4}-\d{2}-\d{2})', [
			'methods'             => \WP_REST_Server::DELETABLE,
			'callback'            => [ $this, 'api_delete_entry' ],
			'permission_callback' => fn() => current_user_can( 'manage_options' ),
		] );
	}

	// -----------------------------------------------------------------------
	// REST callbacks
	// -----------------------------------------------------------------------

	public function api_get_calendar( \WP_REST_Request $req ): \WP_REST_Response {
		return new \WP_REST_Response( $this->load_calendar(), 200 );
	}

	public function api_add_entry( \WP_REST_Request $req ): \WP_REST_Response {
		$date  = sanitize_text_field( $req->get_param( 'date' ) );
		$topic = sanitize_text_field( $req->get_param( 'topic' ) );

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) || '' === $topic ) {
			return new \WP_REST_Response( [ 'error' => 'Invalid date or topic.' ], 400 );
		}

		$calendar = $this->load_calendar();
		$calendar[] = [ 'date' => $date, 'topic' => $topic, 'status' => 'pending' ];
		$this->save_calendar( $calendar );

		return new \WP_REST_Response( [ 'success' => true, 'entries' => $calendar ], 200 );
	}

	public function api_delete_entry( \WP_REST_Request $req ): \WP_REST_Response {
		$date     = sanitize_text_field( $req->get_param( 'date' ) );
		$calendar = $this->load_calendar();
		$calendar = array_values( array_filter( $calendar, fn( $e ) => $e['date'] !== $date ) );
		$this->save_calendar( $calendar );

		return new \WP_REST_Response( [ 'success' => true ], 200 );
	}

	// -----------------------------------------------------------------------
	// Cron: auto-dispatch topics on their scheduled date
	// -----------------------------------------------------------------------

	/**
	 * Push today's pending topics into the content queue.
	 */
	public function dispatch_today(): void {
		$today    = current_time( 'Y-m-d' );
		$calendar = $this->load_calendar();
		$changed  = false;

		foreach ( $calendar as &$entry ) {
			if ( 'pending' === $entry['status'] && $entry['date'] === $today ) {
				$queue = new TopicQueue( get_current_blog_id() );
				$queue->push( $entry['topic'] );
				$entry['status'] = 'queued';
				$changed         = true;
				error_log( "PearBlog Engine: Calendar dispatched topic '{$entry['topic']}' to queue." );
			}
		}
		unset( $entry );

		if ( $changed ) {
			$this->save_calendar( $calendar );
		}
	}

	// -----------------------------------------------------------------------
	// Form handler (non-AJAX form submit)
	// -----------------------------------------------------------------------

	public function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'pearblog-engine' ) );
		}
		check_admin_referer( 'pearblog_calendar_save' );

		$date  = sanitize_text_field( wp_unslash( $_POST['pb_cal_date']  ?? '' ) );
		$topic = sanitize_text_field( wp_unslash( $_POST['pb_cal_topic'] ?? '' ) );

		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) && '' !== $topic ) {
			$calendar   = $this->load_calendar();
			$calendar[] = [ 'date' => $date, 'topic' => $topic, 'status' => 'pending' ];
			$this->save_calendar( $calendar );
		}

		wp_safe_redirect( admin_url( 'tools.php?page=' . self::MENU_SLUG . '&saved=1' ) );
		exit;
	}

	// -----------------------------------------------------------------------
	// Admin page render
	// -----------------------------------------------------------------------

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$calendar = $this->load_calendar();
		// Sort ascending by date.
		usort( $calendar, fn( $a, $b ) => strcmp( $a['date'], $b['date'] ) );

		$today    = current_time( 'Y-m-d' );
		$saved    = isset( $_GET['saved'] );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Content Calendar', 'pearblog-engine' ); ?></h1>

			<?php if ( $saved ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Topic scheduled.', 'pearblog-engine' ); ?></p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Schedule a Topic', 'pearblog-engine' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="pearblog_calendar_save" />
				<?php wp_nonce_field( 'pearblog_calendar_save' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="pb_cal_date"><?php esc_html_e( 'Publish Date', 'pearblog-engine' ); ?></label></th>
						<td><input type="date" id="pb_cal_date" name="pb_cal_date" value="<?php echo esc_attr( $today ); ?>" min="<?php echo esc_attr( $today ); ?>" required /></td>
					</tr>
					<tr>
						<th><label for="pb_cal_topic"><?php esc_html_e( 'Topic', 'pearblog-engine' ); ?></label></th>
						<td><input type="text" id="pb_cal_topic" name="pb_cal_topic" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. Best hotels in Kraków', 'pearblog-engine' ); ?>" required /></td>
					</tr>
				</table>
				<?php submit_button( __( 'Schedule Topic', 'pearblog-engine' ) ); ?>
			</form>

			<hr />

			<h2><?php esc_html_e( 'Upcoming Schedule', 'pearblog-engine' ); ?></h2>

			<?php if ( empty( $calendar ) ) : ?>
				<p><?php esc_html_e( 'No topics scheduled yet.', 'pearblog-engine' ); ?></p>
			<?php else : ?>
				<table class="widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date', 'pearblog-engine' ); ?></th>
							<th><?php esc_html_e( 'Topic', 'pearblog-engine' ); ?></th>
							<th><?php esc_html_e( 'Status', 'pearblog-engine' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $calendar as $entry ) : ?>
							<tr>
								<td><?php echo esc_html( $entry['date'] ); ?></td>
								<td><?php echo esc_html( $entry['topic'] ); ?></td>
								<td><?php echo esc_html( $entry['status'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	// -----------------------------------------------------------------------
	// Storage helpers
	// -----------------------------------------------------------------------

	private function load_calendar(): array {
		$raw = get_option( self::OPTION_KEY, [] );
		return is_array( $raw ) ? $raw : [];
	}

	private function save_calendar( array $calendar ): void {
		update_option( self::OPTION_KEY, array_values( $calendar ) );
	}
}

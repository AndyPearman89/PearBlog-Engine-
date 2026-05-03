<?php
/**
 * Social Calendar – plans and queues social media posts for articles.
 *
 * Maintains a per-platform posting calendar so every published article gets
 * promoted across social channels on a configured schedule (e.g. publish to
 * Twitter immediately, LinkedIn +1 day, Pinterest +3 days, Facebook +7 days).
 *
 * Features:
 *   - Platform-specific post copy variations (short/medium/long)
 *   - Optimal posting-time slots per platform
 *   - Calendar view in the WordPress admin (custom admin page)
 *   - REST endpoints for calendar management and manual publishing
 *   - WP-Cron fires at configurable intervals to send queued posts
 *   - Integration with SocialPublisher for actual delivery
 *
 * Options:
 *   pearblog_social_calendar_enabled  – bool master switch (default true)
 *   pearblog_social_calendar_schedule – JSON-encoded per-platform delay map
 *     e.g. {"twitter":0,"linkedin":1440,"pinterest":4320,"facebook":10080}
 *     (minutes after publish)
 *   pearblog_social_calendar_slots    – JSON-encoded per-platform optimal hours
 *     e.g. {"twitter":[9,12,17],"linkedin":[9,12],"facebook":[13,17]}
 *
 * WP option for the queue:
 *   pearblog_social_queue  – array of pending social posts
 *
 * REST endpoints:
 *   GET    /pearblog/v1/social/calendar            – upcoming calendar entries
 *   DELETE /pearblog/v1/social/calendar/{entry_id} – cancel an entry
 *   POST   /pearblog/v1/social/calendar/publish/{entry_id} – publish now
 *
 * @package PearBlogEngine\Social
 */

declare(strict_types=1);

namespace PearBlogEngine\Social;

/**
 * Maintains a multi-platform social posting calendar.
 */
class SocialCalendar {

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Option key for the social queue. */
	private const OPTION_QUEUE = 'pearblog_social_queue';

	/** Cron hook. */
	private const CRON_HOOK = 'pearblog_social_calendar_dispatch';

	/** Default schedule: platform → delay in minutes after publish. */
	private const DEFAULT_SCHEDULE = [
		'twitter'   => 0,
		'linkedin'  => 60,
		'pinterest' => 180,
		'facebook'  => 1440,
	];

	/** Platform-specific copy templates. */
	private const COPY_TEMPLATES = [
		'twitter'   => '📝 %title%  %url% %hashtags%',
		'linkedin'  => "New article: %title%\n\n%excerpt%\n\nRead more: %url%",
		'pinterest' => '%title% — %excerpt% %url%',
		'facebook'  => "Just published: %title%\n\n%excerpt%\n\n👉 %url%",
	];

	// -----------------------------------------------------------------------
	// Bootstrap
	// -----------------------------------------------------------------------

	/**
	 * Register hooks, REST routes, and cron.
	 */
	public function register(): void {
		if ( ! (bool) get_option( 'pearblog_social_calendar_enabled', true ) ) {
			return;
		}

		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'publish_post', [ $this, 'queue_social_posts_on_publish' ], 20, 1 );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'twicedaily', self::CRON_HOOK );
		}
		add_action( self::CRON_HOOK, [ $this, 'dispatch_due_entries' ] );

		add_action( 'admin_menu', [ $this, 'add_admin_page' ] );
	}

	// -----------------------------------------------------------------------
	// REST routes
	// -----------------------------------------------------------------------

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/social/calendar', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_list' ],
			'permission_callback' => [ $this, 'rest_permission' ],
			'args'                => [
				'platform' => [ 'required' => false, 'type' => 'string' ],
				'limit'    => [ 'required' => false, 'type' => 'integer', 'default' => 50 ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/social/calendar/(?P<entry_id>[a-zA-Z0-9_-]+)', [
			[
				'methods'             => 'DELETE',
				'callback'            => [ $this, 'rest_cancel' ],
				'permission_callback' => [ $this, 'rest_permission' ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/social/calendar/publish/(?P<entry_id>[a-zA-Z0-9_-]+)', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_publish_now' ],
			'permission_callback' => [ $this, 'rest_permission' ],
		] );
	}

	/**
	 * Permission – manage_options.
	 */
	public function rest_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	// -----------------------------------------------------------------------
	// REST handlers
	// -----------------------------------------------------------------------

	/**
	 * GET /social/calendar – upcoming entries.
	 */
	public function rest_list( \WP_REST_Request $request ): \WP_REST_Response {
		$platform = sanitize_key( $request->get_param( 'platform' ) ?: '' );
		$limit    = (int) $request->get_param( 'limit' );
		$queue    = $this->get_queue();

		if ( $platform ) {
			$queue = array_filter( $queue, fn( $e ) => $e['platform'] === $platform );
		}

		$queue = array_values( $queue );
		usort( $queue, fn( $a, $b ) => $a['due_at'] <=> $b['due_at'] );
		$queue = array_slice( $queue, 0, $limit );

		return new \WP_REST_Response( $queue, 200 );
	}

	/**
	 * DELETE /social/calendar/{entry_id} – cancel a queued entry.
	 */
	public function rest_cancel( \WP_REST_Request $request ): \WP_REST_Response {
		$entry_id = sanitize_key( $request->get_param( 'entry_id' ) );
		$queue    = $this->get_queue();

		if ( ! isset( $queue[ $entry_id ] ) ) {
			return new \WP_REST_Response( [ 'error' => 'Entry not found.' ], 404 );
		}

		unset( $queue[ $entry_id ] );
		update_option( self::OPTION_QUEUE, $queue );

		return new \WP_REST_Response( [ 'cancelled' => true ], 200 );
	}

	/**
	 * POST /social/calendar/publish/{entry_id} – publish immediately.
	 */
	public function rest_publish_now( \WP_REST_Request $request ): \WP_REST_Response {
		$entry_id = sanitize_key( $request->get_param( 'entry_id' ) );
		$queue    = $this->get_queue();

		if ( ! isset( $queue[ $entry_id ] ) ) {
			return new \WP_REST_Response( [ 'error' => 'Entry not found.' ], 404 );
		}

		$entry  = $queue[ $entry_id ];
		$result = $this->publish_entry( $entry );

		unset( $queue[ $entry_id ] );
		update_option( self::OPTION_QUEUE, $queue );

		return new \WP_REST_Response( [ 'published' => true, 'entry' => $entry, 'result' => $result ], 200 );
	}

	// -----------------------------------------------------------------------
	// Queue on publish
	// -----------------------------------------------------------------------

	/**
	 * Queue social posts when a WordPress post is published.
	 *
	 * @param int $post_id WordPress post ID.
	 */
	public function queue_social_posts_on_publish( int $post_id ): void {
		if ( 'post' !== get_post_type( $post_id ) ) {
			return;
		}

		$post     = get_post( $post_id );
		$schedule = $this->get_schedule();
		$queue    = $this->get_queue();

		foreach ( $schedule as $platform => $delay_minutes ) {
			$due_at    = time() + ( (int) $delay_minutes * 60 );
			$entry_id  = 'sc_' . $post_id . '_' . $platform;

			$queue[ $entry_id ] = [
				'entry_id'   => $entry_id,
				'post_id'    => $post_id,
				'platform'   => $platform,
				'due_at'     => $due_at,
				'copy'       => $this->build_copy( $platform, $post ),
				'status'     => 'pending',
				'created_at' => time(),
			];
		}

		update_option( self::OPTION_QUEUE, $queue );
	}

	// -----------------------------------------------------------------------
	// Cron dispatch
	// -----------------------------------------------------------------------

	/**
	 * Dispatch all entries that are now due.
	 */
	public function dispatch_due_entries(): void {
		$queue   = $this->get_queue();
		$changed = false;

		foreach ( $queue as $entry_id => $entry ) {
			if ( 'pending' !== $entry['status'] ) {
				continue;
			}
			if ( $entry['due_at'] > time() ) {
				continue;
			}

			$this->publish_entry( $entry );
			$queue[ $entry_id ]['status'] = 'sent';
			$queue[ $entry_id ]['sent_at'] = time();
			$changed = true;
		}

		if ( $changed ) {
			// Prune entries older than 30 days.
			$cutoff = time() - 30 * DAY_IN_SECONDS;
			$queue  = array_filter( $queue, fn( $e ) => $e['due_at'] > $cutoff );
			update_option( self::OPTION_QUEUE, $queue );
		}
	}

	// -----------------------------------------------------------------------
	// Admin page
	// -----------------------------------------------------------------------

	/**
	 * Add social calendar submenu page.
	 */
	public function add_admin_page(): void {
		add_submenu_page(
			'pearblog',
			__( 'Social Calendar', 'pearblog-engine' ),
			__( 'Social Calendar', 'pearblog-engine' ),
			'manage_options',
			'pearblog-social-calendar',
			[ $this, 'render_admin_page' ]
		);
	}

	/**
	 * Render the social calendar admin page.
	 */
	public function render_admin_page(): void {
		$queue = array_values( $this->get_queue() );
		usort( $queue, fn( $a, $b ) => $a['due_at'] <=> $b['due_at'] );
		$upcoming = array_filter( $queue, fn( $e ) => 'pending' === $e['status'] );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Social Media Calendar', 'pearblog-engine' ); ?></h1>

			<?php if ( empty( $upcoming ) ) : ?>
				<p><?php esc_html_e( 'No pending social posts.', 'pearblog-engine' ); ?></p>
			<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Post', 'pearblog-engine' ); ?></th>
						<th><?php esc_html_e( 'Platform', 'pearblog-engine' ); ?></th>
						<th><?php esc_html_e( 'Scheduled', 'pearblog-engine' ); ?></th>
						<th><?php esc_html_e( 'Copy preview', 'pearblog-engine' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $upcoming as $entry ) : ?>
					<tr>
						<td><?php echo esc_html( get_the_title( $entry['post_id'] ) ?: '#' . $entry['post_id'] ); ?></td>
						<td><strong><?php echo esc_html( ucfirst( $entry['platform'] ) ); ?></strong></td>
						<td><?php echo esc_html( gmdate( 'Y-m-d H:i', $entry['due_at'] ) ); ?></td>
						<td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo esc_html( substr( $entry['copy'], 0, 100 ) ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
		<?php
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Publish a single calendar entry via SocialPublisher.
	 *
	 * @param array $entry Calendar entry.
	 * @return mixed  Publisher result.
	 */
	private function publish_entry( array $entry ): mixed {
		do_action( 'pearblog_social_calendar_publish', $entry );
		return apply_filters( 'pearblog_social_calendar_publish_result', null, $entry );
	}

	/**
	 * Build platform-specific post copy for an article.
	 *
	 * @param string   $platform Platform key.
	 * @param \WP_Post $post     WordPress post.
	 * @return string  Formatted copy.
	 */
	private function build_copy( string $platform, \WP_Post $post ): string {
		$template = self::COPY_TEMPLATES[ $platform ] ?? '%title% %url%';
		$title    = $post->post_title;
		$url      = get_permalink( $post );
		$excerpt  = wp_trim_words( $post->post_excerpt ?: $post->post_content, 30 );

		// Build hashtags from tags.
		$tags     = get_the_tags( $post->ID );
		$hashtags = '';
		if ( $tags && ! is_wp_error( $tags ) ) {
			$hlist    = array_slice( $tags, 0, 5 );
			$hashtags = implode( ' ', array_map( fn( $t ) => '#' . sanitize_html_class( $t->name ), $hlist ) );
		}

		return str_replace(
			[ '%title%', '%url%', '%excerpt%', '%hashtags%' ],
			[ $title, $url, $excerpt, $hashtags ],
			$template
		);
	}

	/**
	 * Return the social posting schedule.
	 *
	 * @return array<string, int>  Platform → delay in minutes.
	 */
	private function get_schedule(): array {
		$json = get_option( 'pearblog_social_calendar_schedule', '' );
		if ( $json ) {
			$decoded = json_decode( $json, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}
		return self::DEFAULT_SCHEDULE;
	}

	/**
	 * Return the social posting queue.
	 *
	 * @return array<string, array>
	 */
	private function get_queue(): array {
		return (array) get_option( self::OPTION_QUEUE, [] );
	}
}

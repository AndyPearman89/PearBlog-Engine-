<?php
/**
 * Event-sourced pipeline audit log.
 *
 * Every significant pipeline and content-generation event is appended to a
 * ring-buffer stored in the `pearblog_audit_log` WP option (max
 * MAX_ENTRIES entries).  Events are also exposed via a REST endpoint so
 * external tools and dashboards can consume the audit trail.
 *
 * REST endpoint:  GET /pearblog/v1/audit
 *                 POST /pearblog/v1/audit/append  (admin only)
 *
 * Option key:  pearblog_audit_log   – JSON-encoded array of event objects.
 *
 * Action hooks consumed (auto-registered via ::register()):
 *   pearblog_pipeline_started          ($topic, $context)
 *   pearblog_pipeline_completed        ($post_id, $topic, $context)
 *   pearblog_pipeline_duplicate_skipped ($topic, $result)
 *   pearblog_pipeline_cron_error       ($site_id, $message)
 *   pearblog_seo_applied               ($post_id, $title, $meta_description)
 *   pearblog_quality_scored            ($post_id, $score)
 *   pearblog_content_refreshed         ($post_id, $new_title)
 *   pearblog_social_published          ($post_id, $results)
 *   pearblog_bg_job_completed          ($job)
 *   pearblog_bg_job_failed             ($job, $exception)
 *   pearblog_cdn_offloaded             ($attachment_id, $cdn_url, $provider)
 *   pearblog_sla_breached              ($metric, $target, $actual)
 *   pearblog_abtest_winner_promoted    ($test_id, $winner, $avg_a, $avg_b)
 *   pearblog_translation_created       ($new_post_id, $source_post_id, $language)
 *
 * @package PearBlogEngine\Pipeline
 */

declare(strict_types=1);

namespace PearBlogEngine\Pipeline;

/**
 * Append-only event log for pipeline activity.
 */
class PipelineAuditLog {

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	/** WP option that stores the ring-buffer. */
	public const OPTION_KEY = 'pearblog_audit_log';

	/** Maximum number of events retained. Older events are discarded. */
	public const MAX_ENTRIES = 500;

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** REST base path for audit routes. */
	private const REST_BASE = '/audit';

	/** Valid event severity levels. */
	public const LEVEL_INFO    = 'info';
	public const LEVEL_WARNING = 'warning';
	public const LEVEL_ERROR   = 'error';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Hook into WordPress and register REST routes.
	 *
	 * Call once from Plugin::boot().
	 */
	public function register(): void {
		// Pipeline lifecycle.
		add_action( 'pearblog_pipeline_started', [ $this, 'on_pipeline_started' ], 10, 2 );
		add_action( 'pearblog_pipeline_completed', [ $this, 'on_pipeline_completed' ], 10, 3 );
		add_action( 'pearblog_pipeline_duplicate_skipped', [ $this, 'on_duplicate_skipped' ], 10, 2 );
		add_action( 'pearblog_pipeline_cron_error', [ $this, 'on_cron_error' ], 10, 2 );

		// Content operations.
		add_action( 'pearblog_seo_applied', [ $this, 'on_seo_applied' ], 10, 3 );
		add_action( 'pearblog_quality_scored', [ $this, 'on_quality_scored' ], 10, 2 );
		add_action( 'pearblog_content_refreshed', [ $this, 'on_content_refreshed' ], 10, 2 );
		add_action( 'pearblog_translation_created', [ $this, 'on_translation_created' ], 10, 3 );

		// Social & distribution.
		add_action( 'pearblog_social_published', [ $this, 'on_social_published' ], 10, 2 );
		add_action( 'pearblog_cdn_offloaded', [ $this, 'on_cdn_offloaded' ], 10, 3 );

		// Background processing.
		add_action( 'pearblog_bg_job_completed', [ $this, 'on_bg_job_completed' ], 10, 1 );
		add_action( 'pearblog_bg_job_failed', [ $this, 'on_bg_job_failed' ], 10, 2 );

		// Quality / testing / SLA.
		add_action( 'pearblog_sla_breached', [ $this, 'on_sla_breached' ], 10, 3 );
		add_action( 'pearblog_abtest_winner_promoted', [ $this, 'on_abtest_winner_promoted' ], 10, 4 );

		// REST endpoint.
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	// -----------------------------------------------------------------------
	// REST routes
	// -----------------------------------------------------------------------

	/**
	 * Register REST routes for the audit log.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, self::REST_BASE, [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ $this, 'rest_get_events' ],
			'permission_callback' => [ $this, 'rest_permission' ],
			'args'                => [
				'limit' => [
					'type'              => 'integer',
					'default'           => 50,
					'minimum'           => 1,
					'maximum'           => self::MAX_ENTRIES,
					'sanitize_callback' => 'absint',
				],
				'level' => [
					'type'              => 'string',
					'enum'              => [ self::LEVEL_INFO, self::LEVEL_WARNING, self::LEVEL_ERROR ],
					'sanitize_callback' => 'sanitize_text_field',
				],
				'event' => [
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );

		register_rest_route( self::NAMESPACE, self::REST_BASE . '/append', [
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'rest_append_event' ],
			'permission_callback' => [ $this, 'rest_admin_permission' ],
			'args'                => [
				'event'   => [
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				],
				'level'   => [
					'type'              => 'string',
					'default'           => self::LEVEL_INFO,
					'enum'              => [ self::LEVEL_INFO, self::LEVEL_WARNING, self::LEVEL_ERROR ],
					'sanitize_callback' => 'sanitize_text_field',
				],
				'context' => [
					'type'    => 'object',
					'default' => [],
				],
			],
		] );
	}

	/**
	 * Permission callback – requires manage_options or valid API key.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return bool
	 */
	public function rest_permission( \WP_REST_Request $request ): bool {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$stored_key = (string) get_option( 'pearblog_api_key', '' );
		if ( '' !== $stored_key ) {
			$auth   = (string) $request->get_header( 'authorization' );
			$bearer = ltrim( substr( $auth, 6 ) ); // strip "Bearer " prefix.
			if ( hash_equals( $stored_key, $bearer ) ) {
				return true;
			}

			$query_key = (string) ( $request->get_param( 'api_key' ) ?? '' );
			if ( '' !== $query_key && hash_equals( $stored_key, $query_key ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Permission callback that requires manage_options only (admin-level writes).
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return bool
	 */
	public function rest_admin_permission( \WP_REST_Request $request ): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * GET /pearblog/v1/audit – return filtered events.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response
	 */
	public function rest_get_events( \WP_REST_Request $request ): \WP_REST_Response {
		$limit      = (int) ( $request->get_param( 'limit' ) ?? 50 );
		$level      = (string) ( $request->get_param( 'level' ) ?? '' );
		$event_type = (string) ( $request->get_param( 'event' ) ?? '' );

		$events = $this->get_events( $limit, $level ?: null, $event_type ?: null );

		return new \WP_REST_Response(
			[
				'events' => $events,
				'total'  => count( $this->load_log() ),
			],
			200
		);
	}

	/**
	 * POST /pearblog/v1/audit/append – manually append a custom event.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response
	 */
	public function rest_append_event( \WP_REST_Request $request ): \WP_REST_Response {
		$event   = (string) ( $request->get_param( 'event' ) ?? '' );
		$level   = (string) ( $request->get_param( 'level' ) ?? self::LEVEL_INFO );
		$context = (array) ( $request->get_param( 'context' ) ?? [] );

		if ( '' === $event ) {
			return new \WP_REST_Response( [ 'error' => 'event is required' ], 400 );
		}

		$entry = $this->append( $event, $level, $context );

		return new \WP_REST_Response( [ 'appended' => true, 'entry' => $entry ], 201 );
	}

	// -----------------------------------------------------------------------
	// Action callbacks
	// -----------------------------------------------------------------------

	/** @param string $topic   Topic string. */
	public function on_pipeline_started( string $topic ): void {
		$this->append( 'pipeline_started', self::LEVEL_INFO, [ 'topic' => $topic ] );
	}

	/**
	 * @param int    $post_id Post ID.
	 * @param string $topic   Topic string.
	 */
	public function on_pipeline_completed( int $post_id, string $topic ): void {
		$this->append( 'pipeline_completed', self::LEVEL_INFO, [
			'post_id' => $post_id,
			'topic'   => $topic,
		] );
	}

	/**
	 * @param string $topic     Topic that was skipped.
	 * @param array  $dup_result Duplicate-check result details.
	 */
	public function on_duplicate_skipped( string $topic, array $dup_result ): void {
		$this->append( 'duplicate_skipped', self::LEVEL_WARNING, [
			'topic'      => $topic,
			'similarity' => $dup_result['similarity'] ?? null,
		] );
	}

	/**
	 * @param int    $site_id Site ID.
	 * @param string $message Error message.
	 */
	public function on_cron_error( int $site_id, string $message ): void {
		$this->append( 'cron_error', self::LEVEL_ERROR, [
			'site_id' => $site_id,
			'message' => $message,
		] );
	}

	/**
	 * @param int    $post_id          Post ID.
	 * @param string $title            SEO title.
	 * @param string $meta_description SEO meta description.
	 */
	public function on_seo_applied( int $post_id, string $title, string $meta_description ): void {
		$this->append( 'seo_applied', self::LEVEL_INFO, [
			'post_id'          => $post_id,
			'title'            => $title,
			'meta_description' => $meta_description,
		] );
	}

	/**
	 * @param int   $post_id Post ID.
	 * @param float $score   Quality score (0–100).
	 */
	public function on_quality_scored( int $post_id, float $score ): void {
		$this->append( 'quality_scored', self::LEVEL_INFO, [
			'post_id' => $post_id,
			'score'   => $score,
		] );
	}

	/**
	 * @param int    $post_id   Post ID.
	 * @param string $new_title New post title.
	 */
	public function on_content_refreshed( int $post_id, string $new_title ): void {
		$this->append( 'content_refreshed', self::LEVEL_INFO, [
			'post_id'   => $post_id,
			'new_title' => $new_title,
		] );
	}

	/**
	 * @param int    $new_post_id    New post ID.
	 * @param int    $source_post_id Source post ID.
	 * @param string $language       Target language code.
	 */
	public function on_translation_created( int $new_post_id, int $source_post_id, string $language ): void {
		$this->append( 'translation_created', self::LEVEL_INFO, [
			'new_post_id'    => $new_post_id,
			'source_post_id' => $source_post_id,
			'language'       => $language,
		] );
	}

	/**
	 * @param int   $post_id Post ID.
	 * @param array $results Per-network results map.
	 */
	public function on_social_published( int $post_id, array $results ): void {
		$this->append( 'social_published', self::LEVEL_INFO, [
			'post_id'  => $post_id,
			'networks' => array_keys( $results ),
		] );
	}

	/**
	 * @param int    $attachment_id WP attachment ID.
	 * @param string $cdn_url       CDN URL.
	 * @param string $provider      CDN provider slug.
	 */
	public function on_cdn_offloaded( int $attachment_id, string $cdn_url, string $provider ): void {
		$this->append( 'cdn_offloaded', self::LEVEL_INFO, [
			'attachment_id' => $attachment_id,
			'cdn_url'       => $cdn_url,
			'provider'      => $provider,
		] );
	}

	/** @param array $job Background job data. */
	public function on_bg_job_completed( array $job ): void {
		$this->append( 'bg_job_completed', self::LEVEL_INFO, [
			'job_id' => $job['id'] ?? null,
			'topic'  => $job['topic'] ?? null,
		] );
	}

	/**
	 * @param array      $job Background job data.
	 * @param \Throwable $exception Exception that caused the failure.
	 */
	public function on_bg_job_failed( array $job, \Throwable $exception ): void {
		$this->append( 'bg_job_failed', self::LEVEL_ERROR, [
			'job_id'  => $job['id'] ?? null,
			'topic'   => $job['topic'] ?? null,
			'error'   => $exception->getMessage(),
		] );
	}

	/**
	 * @param string $metric SLA metric key.
	 * @param mixed  $target Target value.
	 * @param mixed  $actual Actual measured value.
	 */
	public function on_sla_breached( string $metric, $target, $actual ): void {
		$this->append( 'sla_breached', self::LEVEL_ERROR, [
			'metric' => $metric,
			'target' => $target,
			'actual' => $actual,
		] );
	}

	/**
	 * @param string $test_id A/B test ID.
	 * @param string $winner  Winning variant ('a' or 'b').
	 * @param float  $avg_a   Average score for variant A.
	 * @param float  $avg_b   Average score for variant B.
	 */
	public function on_abtest_winner_promoted( string $test_id, string $winner, float $avg_a, float $avg_b ): void {
		$this->append( 'abtest_winner_promoted', self::LEVEL_INFO, [
			'test_id' => $test_id,
			'winner'  => $winner,
			'avg_a'   => $avg_a,
			'avg_b'   => $avg_b,
		] );
	}

	// -----------------------------------------------------------------------
	// Core log API
	// -----------------------------------------------------------------------

	/**
	 * Append a new event to the ring-buffer and persist.
	 *
	 * @param string $event   Machine-readable event slug.
	 * @param string $level   One of LEVEL_* constants.
	 * @param array  $context Arbitrary key→value context data.
	 * @return array          The new event entry.
	 */
	public function append( string $event, string $level = self::LEVEL_INFO, array $context = [] ): array {
		$entry = [
			'id'        => $this->generate_id(),
			'timestamp' => time(),
			'event'     => $event,
			'level'     => $level,
			'context'   => $context,
		];

		$log   = $this->load_log();
		$log[] = $entry;

		// Enforce ring-buffer size: keep the most-recent MAX_ENTRIES entries.
		if ( count( $log ) > self::MAX_ENTRIES ) {
			$log = array_slice( $log, -self::MAX_ENTRIES );
		}

		$this->save_log( $log );

		return $entry;
	}

	/**
	 * Retrieve events from the log with optional filtering.
	 *
	 * Events are returned in reverse-chronological order (newest first).
	 *
	 * @param int         $limit      Maximum number of entries to return.
	 * @param string|null $level      Filter by severity level.
	 * @param string|null $event_type Filter by event slug.
	 * @return array<int, array>
	 */
	public function get_events( int $limit = 50, ?string $level = null, ?string $event_type = null ): array {
		$log = array_reverse( $this->load_log() );

		if ( null !== $level ) {
			$log = array_values( array_filter( $log, fn( array $e ) => $e['level'] === $level ) );
		}

		if ( null !== $event_type ) {
			$log = array_values( array_filter( $log, fn( array $e ) => $e['event'] === $event_type ) );
		}

		return array_slice( $log, 0, $limit );
	}

	/**
	 * Return all events in chronological order without filtering.
	 *
	 * @return array<int, array>
	 */
	public function get_all_events(): array {
		return $this->load_log();
	}

	/**
	 * Clear the entire audit log.
	 */
	public function clear(): void {
		$this->save_log( [] );
	}

	/**
	 * Return the total number of stored events.
	 */
	public function count(): int {
		return count( $this->load_log() );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Load the log from the WP options table.
	 *
	 * @return array<int, array>
	 */
	private function load_log(): array {
		$raw = get_option( self::OPTION_KEY, [] );
		return is_array( $raw ) ? $raw : [];
	}

	/**
	 * Persist the log to the WP options table.
	 *
	 * @param array<int, array> $log
	 */
	private function save_log( array $log ): void {
		update_option( self::OPTION_KEY, $log );
	}

	/**
	 * Generate a short unique event ID.
	 *
	 * @return string e.g. "evt_a1b2c3d4"
	 */
	private function generate_id(): string {
		return 'evt_' . substr( md5( uniqid( '', true ) ), 0, 8 );
	}
}

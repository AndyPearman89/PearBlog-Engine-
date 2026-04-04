<?php
/**
 * Cron manager – schedules and executes the content pipeline automatically.
 *
 * A single WP-Cron event (`pearblog_run_pipeline`) is registered for each
 * active site.  The interval is derived from the site's SiteProfile
 * publish_rate setting.
 *
 * @package PearBlogEngine\Scheduler
 */

declare(strict_types=1);

namespace PearBlogEngine\Scheduler;

use PearBlogEngine\Pipeline\ContentPipeline;
use PearBlogEngine\Tenant\TenantContext;

/**
 * Registers WP-Cron hooks and custom schedules for the pipeline.
 */
class CronManager {

	private const HOOK          = 'pearblog_run_pipeline';
	private const SCHEDULE_SLUG = 'pearblog_hourly';

	/**
	 * Attach WordPress hooks (call once during plugin boot).
	 */
	public function register(): void {
		add_filter( 'cron_schedules', [ $this, 'add_schedule' ] );
		add_action( self::HOOK, [ $this, 'run_pipeline_for_all_sites' ] );
		add_action( 'init', [ $this, 'maybe_schedule' ] );

		// Deactivation hook – remove scheduled events.
		register_deactivation_hook(
			PEARBLOG_ENGINE_DIR . 'pearblog-engine.php',
			[ $this, 'deactivate' ]
		);
	}

	// -----------------------------------------------------------------------
	// WP-Cron schedule
	// -----------------------------------------------------------------------

	/**
	 * Register a custom "every hour" schedule (if WP core lacks one).
	 *
	 * @param array $schedules Existing cron schedules.
	 * @return array
	 */
	public function add_schedule( array $schedules ): array {
		if ( ! isset( $schedules[ self::SCHEDULE_SLUG ] ) ) {
			$schedules[ self::SCHEDULE_SLUG ] = [
				'interval' => HOUR_IN_SECONDS,
				'display'  => __( 'Every Hour (PearBlog)', 'pearblog-engine' ),
			];
		}
		return $schedules;
	}

	/**
	 * Schedule the pipeline event if it is not already scheduled.
	 */
	public function maybe_schedule(): void {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time(), self::SCHEDULE_SLUG, self::HOOK );
		}
	}

	/**
	 * Remove all scheduled events on plugin deactivation.
	 */
	public function deactivate(): void {
		$timestamp = wp_next_scheduled( self::HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::HOOK );
		}
	}

	// -----------------------------------------------------------------------
	// Pipeline execution
	// -----------------------------------------------------------------------

	/**
	 * Iterate over every site in the network and run the pipeline for each.
	 *
	 * On single-site installs this processes only the current blog.
	 */
	public function run_pipeline_for_all_sites(): void {
		if ( is_multisite() ) {
			$sites = get_sites( [ 'fields' => 'ids', 'number' => 500 ] );
		} else {
			$sites = [ get_current_blog_id() ];
		}

		foreach ( $sites as $site_id ) {
			$this->run_pipeline_for_site( (int) $site_id );
		}
	}

	/**
	 * Run the pipeline for a single site according to its publish_rate.
	 *
	 * @param int $site_id WordPress blog ID.
	 */
	private function run_pipeline_for_site( int $site_id ): void {
		$switched = false;

		try {
			// Switch blog context so wp_insert_post, update_post_meta, etc.
			// operate on the correct site in multisite installations.
			if ( is_multisite() && $site_id !== get_current_blog_id() ) {
				switch_to_blog( $site_id );
				$switched = true;
			}

			$context  = TenantContext::for_site( $site_id );
			$pipeline = new ContentPipeline( $context );

			$articles_to_publish = max( 1, $context->profile->publish_rate );

			for ( $i = 0; $i < $articles_to_publish; $i++ ) {
				$result = $pipeline->run();
				if ( null === $result ) {
					// Queue is empty for this site – stop early.
					break;
				}
			}
		} catch ( \Throwable $e ) {
			// Log but do not bubble up so other sites are not affected.
			error_log( sprintf(
				'PearBlog Engine: Pipeline failed for site %d – %s',
				$site_id,
				$e->getMessage()
			) );
		} finally {
			if ( $switched ) {
				restore_current_blog();
			}
		}
	}
}

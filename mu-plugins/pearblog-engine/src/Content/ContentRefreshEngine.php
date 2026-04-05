<?php
/**
 * Content refresh engine – identifies and queues stale articles for re-optimisation.
 *
 * Logic:
 *  1. Find posts older than $stale_days that have NOT been refreshed recently.
 *  2. Optionally weight by traffic-decay signal (page-views drop stored in meta).
 *  3. Re-run the AI pipeline on those posts (update content, keep URL).
 *  4. Update the post's modified date and log the refresh.
 *
 * Meta keys used:
 *   _pearblog_refreshed_at    – Last refresh timestamp (MySQL datetime).
 *   _pearblog_refresh_count   – Number of times refreshed.
 *   _pearblog_traffic_trend   – 'growing' | 'stable' | 'declining' (set by analytics integration).
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

use PearBlogEngine\AI\AIClient;
use PearBlogEngine\SEO\SEOEngine;
use PearBlogEngine\Tenant\TenantContext;

/**
 * Refreshes stale published posts using the AI pipeline.
 */
class ContentRefreshEngine {

	/** @var string WordPress cron hook name. */
	public const CRON_HOOK = 'pearblog_content_refresh';

	/** Default: posts older than 90 days without a refresh are candidates. */
	public const DEFAULT_STALE_DAYS = 90;

	/** Default: refresh at most this many posts per cron run. */
	public const DEFAULT_BATCH_SIZE = 3;

	/** Meta key – last refresh timestamp. */
	public const META_REFRESHED_AT  = '_pearblog_refreshed_at';
	public const META_REFRESH_COUNT = '_pearblog_refresh_count';
	public const META_TRAFFIC_TREND = '_pearblog_traffic_trend';

	/** @var AIClient */
	private AIClient $ai;

	/** @var SEOEngine */
	private SEOEngine $seo;

	public function __construct( ?AIClient $ai = null, ?SEOEngine $seo = null ) {
		$this->ai  = $ai  ?? new AIClient();
		$this->seo = $seo ?? new SEOEngine();
	}

	/**
	 * Attach WordPress hooks.
	 */
	public function register(): void {
		add_action( self::CRON_HOOK, [ $this, 'run_batch' ] );
		add_action( 'init', [ $this, 'maybe_schedule' ] );
	}

	/**
	 * Schedule the weekly refresh cron event.
	 */
	public function maybe_schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'weekly', self::CRON_HOOK );
		}
	}

	/**
	 * Process one batch of stale posts.
	 *
	 * @param int $stale_days  Minimum post age (days) to qualify.
	 * @param int $batch_size  Maximum posts to refresh per call.
	 * @return array<int, string>  Map of post_id → 'refreshed'|'skipped'|'failed'.
	 */
	public function run_batch( int $stale_days = self::DEFAULT_STALE_DAYS, int $batch_size = self::DEFAULT_BATCH_SIZE ): array {
		$post_ids = $this->get_stale_post_ids( $stale_days, $batch_size );
		$results  = [];

		foreach ( $post_ids as $post_id ) {
			$results[ $post_id ] = $this->refresh_post( $post_id );
		}

		return $results;
	}

	/**
	 * Refresh a single post.
	 *
	 * @param int $post_id WordPress post ID.
	 * @return string 'refreshed' | 'skipped' | 'failed'
	 */
	public function refresh_post( int $post_id ): string {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post || 'publish' !== $post->post_status ) {
			return 'skipped';
		}

		$title = get_the_title( $post_id );

		try {
			// Build a refresh-focused prompt.
			$context = TenantContext::for_site( get_current_blog_id() );
			$prompt  = $this->build_refresh_prompt( $post, $context->profile->language );

			$new_content = $this->ai->generate( $prompt, 2048 );

			if ( '' === $new_content ) {
				return 'skipped';
			}

			// Apply SEO re-extraction.
			$seo_data    = $this->seo->apply( $post_id, $new_content );
			$final_title = $seo_data['title'] ?: $title;

			// Update the post (keeps same URL slug).
			wp_update_post( [
				'ID'            => $post_id,
				'post_title'    => $final_title,
				'post_content'  => $seo_data['content'],
				'post_modified' => current_time( 'mysql' ),
			] );

			// Track refresh.
			$refresh_count = (int) get_post_meta( $post_id, self::META_REFRESH_COUNT, true );
			update_post_meta( $post_id, self::META_REFRESHED_AT,  current_time( 'mysql' ) );
			update_post_meta( $post_id, self::META_REFRESH_COUNT, $refresh_count + 1 );

			/**
			 * Action: pearblog_content_refreshed
			 *
			 * @param int    $post_id Post ID that was refreshed.
			 * @param string $title   Post title.
			 */
			do_action( 'pearblog_content_refreshed', $post_id, $final_title );

			error_log( "PearBlog Engine: Refreshed post {$post_id} ({$title})" );

			return 'refreshed';

		} catch ( \Throwable $e ) {
			error_log( "PearBlog Engine: ContentRefreshEngine failed for post {$post_id} – " . $e->getMessage() );
			return 'failed';
		}
	}

	/**
	 * Return IDs of published posts that are stale and need refreshing.
	 *
	 * Posts with traffic_trend = 'declining' are prioritised by placing them
	 * first in the result list.
	 *
	 * @param int $stale_days  Minimum age in days.
	 * @param int $limit       Maximum number of IDs to return.
	 * @return int[]
	 */
	public function get_stale_post_ids( int $stale_days = self::DEFAULT_STALE_DAYS, int $limit = 10 ): array {
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $stale_days * DAY_IN_SECONDS ) );

		// First: declining traffic posts (highest priority).
		$declining = get_posts( [
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'fields'         => 'ids',
			'date_query'     => [ [ 'before' => $cutoff, 'column' => 'post_date', 'inclusive' => true ] ],
			'meta_query'     => [
				'relation' => 'AND',
				[
					'key'   => self::META_TRAFFIC_TREND,
					'value' => 'declining',
				],
				[
					'relation' => 'OR',
					[
						'key'     => self::META_REFRESHED_AT,
						'value'   => $cutoff,
						'compare' => '<',
						'type'    => 'DATETIME',
					],
					[
						'key'     => self::META_REFRESHED_AT,
						'compare' => 'NOT EXISTS',
					],
				],
			],
		] );

		if ( count( $declining ) >= $limit ) {
			return $declining;
		}

		// Then: oldest not-yet-refreshed posts.
		$remaining_limit = $limit - count( $declining );
		$exclude         = array_map( 'intval', $declining );

		$rest = get_posts( [
			'post_status'    => 'publish',
			'posts_per_page' => $remaining_limit,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'ASC',
			'post__not_in'   => $exclude,
			'date_query'     => [ [ 'before' => $cutoff, 'column' => 'post_date', 'inclusive' => true ] ],
			'meta_query'     => [
				'relation' => 'OR',
				[
					'key'     => self::META_REFRESHED_AT,
					'value'   => $cutoff,
					'compare' => '<',
					'type'    => 'DATETIME',
				],
				[
					'key'     => self::META_REFRESHED_AT,
					'compare' => 'NOT EXISTS',
				],
			],
		] );

		return array_map( 'intval', array_merge( $declining, $rest ) );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	private function build_refresh_prompt( \WP_Post $post, string $language = 'en' ): string {
		$plain_content = wp_strip_all_tags( $post->post_content );
		$excerpt       = mb_substr( $plain_content, 0, 500 );
		$age_days      = (int) ( ( time() - strtotime( $post->post_date ) ) / DAY_IN_SECONDS );

		return "You are an expert SEO content editor. The following article is {$age_days} days old and needs a refresh to stay current in search rankings.\n\n" .
			"Title: {$post->post_title}\n\n" .
			"Current content excerpt:\n{$excerpt}\n\n" .
			"Please rewrite and expand this article with:\n" .
			"1. Updated statistics and current information\n" .
			"2. New relevant sections or tips\n" .
			"3. Improved readability and structure\n" .
			"4. Strong SEO structure with H2/H3 headings\n" .
			"5. A META: description line at the very top\n\n" .
			"Language: {$language}\n" .
			"Minimum 1200 words. Start with META: <description>";
	}
}

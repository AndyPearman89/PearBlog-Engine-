<?php
/**
 * RSS Feed Builder – generates rich, standards-compliant RSS 2.0 feeds.
 *
 * WordPress ships a basic RSS feed, but PearBlog sites need richer feeds
 * for content syndication, podcast players, and newsletter aggregators.
 * This builder adds:
 *
 *   - Media RSS extensions (mrss) for podcast/audio/video items
 *   - Dublin Core metadata (dc:creator, dc:date)
 *   - Google Publisher extensions (article type, keywords)
 *   - Per-category / per-language feeds
 *   - Custom feed for podcast episodes (works with Apple Podcasts, Spotify)
 *
 * Feed URLs served:
 *   /pearblog-feed.xml           – main enhanced feed (all posts)
 *   /pearblog-feed/{category}.xml – per-category feed
 *   /pearblog-podcast.xml        – podcast feed (posts with audio scripts)
 *
 * Options:
 *   pearblog_rss_enabled         – bool master switch (default true)
 *   pearblog_rss_posts_per_feed  – items per feed (default 25)
 *   pearblog_rss_include_podcast – bool include podcast feed (default true)
 *   pearblog_rss_author_name     – override author name in feeds
 *   pearblog_rss_feed_language   – RFC 5646 language tag (default: site language)
 *
 * REST endpoint:
 *   GET /pearblog/v1/rss/status – returns feed URLs
 *
 * @package PearBlogEngine\Distribution
 */

declare(strict_types=1);

namespace PearBlogEngine\Distribution;

/**
 * Builds and serves rich RSS 2.0 feeds.
 */
class RSSFeedBuilder {

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Rewrite query var. */
	private const QUERY_VAR = 'pearblog_feed_type';

	/** Category var. */
	private const QUERY_CAT = 'pearblog_feed_category';

	// -----------------------------------------------------------------------
	// Bootstrap
	// -----------------------------------------------------------------------

	/**
	 * Register hooks, REST routes, and rewrite rules.
	 */
	public function register(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		add_action( 'init', [ $this, 'register_rewrites' ] );
		add_action( 'template_redirect', [ $this, 'maybe_serve_feed' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Whether the RSS builder is enabled.
	 */
	public function is_enabled(): bool {
		return (bool) get_option( 'pearblog_rss_enabled', true );
	}

	// -----------------------------------------------------------------------
	// Rewrites
	// -----------------------------------------------------------------------

	/**
	 * Register URL rewrite rules.
	 */
	public function register_rewrites(): void {
		add_rewrite_rule(
			'^pearblog-feed\.xml$',
			'index.php?' . self::QUERY_VAR . '=main',
			'top'
		);
		add_rewrite_rule(
			'^pearblog-feed/([^/]+)\.xml$',
			'index.php?' . self::QUERY_VAR . '=category&' . self::QUERY_CAT . '=$matches[1]',
			'top'
		);
		add_rewrite_rule(
			'^pearblog-podcast\.xml$',
			'index.php?' . self::QUERY_VAR . '=podcast',
			'top'
		);

		add_filter( 'query_vars', function ( array $vars ): array {
			$vars[] = self::QUERY_VAR;
			$vars[] = self::QUERY_CAT;
			return $vars;
		} );
	}

	/**
	 * Intercept feed requests and serve XML.
	 */
	public function maybe_serve_feed(): void {
		$type = get_query_var( self::QUERY_VAR );
		if ( ! $type ) {
			return;
		}

		switch ( $type ) {
			case 'main':
				$this->serve_main_feed();
				break;
			case 'category':
				$cat = sanitize_key( get_query_var( self::QUERY_CAT ) );
				$this->serve_category_feed( $cat );
				break;
			case 'podcast':
				$this->serve_podcast_feed();
				break;
		}
		exit;
	}

	// -----------------------------------------------------------------------
	// REST
	// -----------------------------------------------------------------------

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/rss/status', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_status' ],
			'permission_callback' => [ $this, 'rest_permission' ],
		] );
	}

	/**
	 * Permission – manage_options.
	 */
	public function rest_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * GET /rss/status – return available feed URLs.
	 */
	public function rest_status( \WP_REST_Request $request ): \WP_REST_Response {
		$feeds = [
			'main'    => home_url( 'pearblog-feed.xml' ),
		];

		if ( (bool) get_option( 'pearblog_rss_include_podcast', true ) ) {
			$feeds['podcast'] = home_url( 'pearblog-podcast.xml' );
		}

		$categories = get_categories( [ 'hide_empty' => true ] );
		if ( $categories && ! is_wp_error( $categories ) ) {
			foreach ( $categories as $cat ) {
				$feeds[ 'category_' . $cat->slug ] = home_url( "pearblog-feed/{$cat->slug}.xml" );
			}
		}

		return new \WP_REST_Response( [ 'feeds' => $feeds ], 200 );
	}

	// -----------------------------------------------------------------------
	// Feed generators
	// -----------------------------------------------------------------------

	/**
	 * Serve the main enhanced RSS feed.
	 */
	public function serve_main_feed(): void {
		$posts = $this->get_posts();
		$this->output_feed( $posts, get_bloginfo( 'name' ), get_bloginfo( 'description' ) );
	}

	/**
	 * Serve a per-category RSS feed.
	 *
	 * @param string $slug Category slug.
	 */
	public function serve_category_feed( string $slug ): void {
		$category = get_category_by_slug( $slug );
		if ( ! $category ) {
			wp_die( 'Category not found.', 404 );
		}

		$posts = $this->get_posts( [ 'cat' => $category->term_id ] );
		$this->output_feed(
			$posts,
			get_bloginfo( 'name' ) . ' – ' . $category->name,
			$category->description ?: get_bloginfo( 'description' )
		);
	}

	/**
	 * Serve the podcast RSS feed.
	 */
	public function serve_podcast_feed(): void {
		$posts = $this->get_posts( [
			'meta_key'     => 'pearblog_podcast_url',
			'meta_compare' => '!=',
			'meta_value'   => '',
		] );
		$this->output_podcast_feed( $posts );
	}

	// -----------------------------------------------------------------------
	// Output methods
	// -----------------------------------------------------------------------

	/**
	 * Output a standard RSS 2.0 feed with Media RSS and Dublin Core extensions.
	 *
	 * @param \WP_Post[] $posts Posts to include.
	 * @param string     $title Channel title.
	 * @param string     $desc  Channel description.
	 */
	public function output_feed( array $posts, string $title, string $desc ): void {
		// Headers must not be sent when output buffering has already started
		// (e.g. in unit tests). Only send when safe to do so.
		if ( ! headers_sent() ) {
			header( 'Content-Type: application/rss+xml; charset=UTF-8' );
		}

		$lang     = esc_html( get_option( 'pearblog_rss_feed_language', get_option( 'pearblog_language', 'en' ) ) );
		$site_url = esc_url( home_url() );
		$title    = esc_html( $title );
		$desc     = esc_html( $desc );
		$now      = esc_html( gmdate( 'r' ) );

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<rss version="2.0"'
		   . ' xmlns:media="http://search.yahoo.com/mrss/"'
		   . ' xmlns:dc="http://purl.org/dc/elements/1.1/"'
		   . ' xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
		echo '<channel>' . "\n";
		echo "  <title>{$title}</title>\n";
		echo "  <link>{$site_url}</link>\n";
		echo "  <description>{$desc}</description>\n";
		echo "  <language>{$lang}</language>\n";
		echo "  <lastBuildDate>{$now}</lastBuildDate>\n";
		echo "  <atom:link href=\"" . esc_url( home_url( 'pearblog-feed.xml' ) ) . "\" rel=\"self\" type=\"application/rss+xml\" />\n";

		foreach ( $posts as $post ) {
			$this->output_item( $post );
		}

		echo '</channel>' . "\n";
		echo '</rss>' . "\n";
	}

	/**
	 * Output an enhanced Apple Podcasts-compatible RSS feed.
	 *
	 * @param \WP_Post[] $posts Posts with podcast episodes.
	 */
	public function output_podcast_feed( array $posts ): void {
		if ( ! headers_sent() ) {
			header( 'Content-Type: application/rss+xml; charset=UTF-8' );
		}

		$site_name = esc_html( get_bloginfo( 'name' ) );
		$site_url  = esc_url( home_url() );
		$feed_url  = esc_url( home_url( 'pearblog-podcast.xml' ) );
		$lang      = esc_html( get_option( 'pearblog_rss_feed_language', 'en' ) );
		$desc      = esc_html( get_bloginfo( 'description' ) );
		$now       = esc_html( gmdate( 'r' ) );

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<rss version="2.0"'
		   . ' xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"'
		   . ' xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
		echo '<channel>' . "\n";
		echo "  <title>{$site_name} Podcast</title>\n";
		echo "  <link>{$site_url}</link>\n";
		echo "  <description>{$desc}</description>\n";
		echo "  <language>{$lang}</language>\n";
		echo "  <lastBuildDate>{$now}</lastBuildDate>\n";
		echo "  <atom:link href=\"{$feed_url}\" rel=\"self\" type=\"application/rss+xml\" />\n";
		echo "  <itunes:explicit>false</itunes:explicit>\n";

		foreach ( $posts as $post ) {
			$this->output_podcast_item( $post );
		}

		echo '</channel>' . "\n";
		echo '</rss>' . "\n";
	}

	// -----------------------------------------------------------------------
	// Item helpers
	// -----------------------------------------------------------------------

	/**
	 * Output a single RSS item with extended metadata.
	 *
	 * @param \WP_Post $post Post to render.
	 */
	private function output_item( \WP_Post $post ): void {
		$url     = esc_url( get_permalink( $post ) );
		$title   = esc_html( $post->post_title );
		$excerpt = esc_html( wp_trim_words( $post->post_excerpt ?: $post->post_content, 40 ) );
		$date    = esc_html( get_post_time( 'r', true, $post ) );
		$author  = esc_html( get_option( 'pearblog_rss_author_name', get_the_author_meta( 'display_name', $post->post_author ) ) );

		$thumb_id  = (int) get_post_thumbnail_id( $post );
		$thumb_url = $thumb_id ? esc_url( wp_get_attachment_image_url( $thumb_id, 'large' ) ) : '';

		echo "  <item>\n";
		echo "    <title>{$title}</title>\n";
		echo "    <link>{$url}</link>\n";
		echo "    <description>{$excerpt}</description>\n";
		echo "    <pubDate>{$date}</pubDate>\n";
		echo "    <dc:creator>{$author}</dc:creator>\n";
		echo "    <guid isPermaLink=\"true\">{$url}</guid>\n";

		if ( $thumb_url ) {
			echo "    <media:thumbnail url=\"{$thumb_url}\" />\n";
		}

		echo "  </item>\n";
	}

	/**
	 * Output a single podcast episode item.
	 *
	 * @param \WP_Post $post Post to render.
	 */
	private function output_podcast_item( \WP_Post $post ): void {
		$url       = esc_url( get_permalink( $post ) );
		$title     = esc_html( $post->post_title );
		$desc      = esc_html( wp_trim_words( $post->post_excerpt ?: $post->post_content, 50 ) );
		$date      = esc_html( get_post_time( 'r', true, $post ) );
		$audio_url = esc_url( get_post_meta( $post->ID, 'pearblog_podcast_url', true ) );

		echo "  <item>\n";
		echo "    <title>{$title}</title>\n";
		echo "    <link>{$url}</link>\n";
		echo "    <description>{$desc}</description>\n";
		echo "    <pubDate>{$date}</pubDate>\n";
		echo "    <guid isPermaLink=\"true\">{$url}</guid>\n";
		echo "    <itunes:summary>{$desc}</itunes:summary>\n";
		if ( $audio_url ) {
			echo "    <enclosure url=\"{$audio_url}\" type=\"audio/mpeg\" length=\"0\" />\n";
		}
		echo "  </item>\n";
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Retrieve posts for a feed.
	 *
	 * @param array $extra_args Extra WP_Query args.
	 * @return \WP_Post[]
	 */
	private function get_posts( array $extra_args = [] ): array {
		$per_page = (int) get_option( 'pearblog_rss_posts_per_feed', 25 );

		$args = array_merge( [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		], $extra_args );

		return get_posts( $args ) ?: [];
	}

	/**
	 * Stub for get_category_by_slug (WordPress function).
	 *
	 * @param string $slug Category slug.
	 * @return object|null WP_Term or null.
	 */
	private function get_category_by_slug_safe( string $slug ): ?object {
		if ( function_exists( 'get_category_by_slug' ) ) {
			return get_category_by_slug( $slug ) ?: null;
		}
		return null;
	}
}

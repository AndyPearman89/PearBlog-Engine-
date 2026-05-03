<?php
/**
 * XML Sitemap Manager – generates comprehensive XML sitemaps for PearBlog sites.
 *
 * Extends WordPress's built-in sitemap (or replaces it entirely) with:
 *   - Image sitemaps (Google Image Sitemap extension)
 *   - Video sitemaps (for posts with video scripts)
 *   - News sitemaps (Google News Sitemap for posts published < 48 h ago)
 *   - Per-category sitemaps for large sites
 *   - Automatic sitemap index (sitemap_index.xml) that links sub-sitemaps
 *
 * Sitemaps served:
 *   /pearblog-sitemap-index.xml          – sitemap index
 *   /pearblog-sitemap-posts-{page}.xml   – paginated post sitemaps
 *   /pearblog-sitemap-images.xml         – image sitemap
 *   /pearblog-sitemap-video.xml          – video sitemap
 *   /pearblog-sitemap-news.xml           – Google News sitemap
 *   /pearblog-sitemap-{lang}.xml         – per-language (HreflangManager)
 *
 * Options:
 *   pearblog_sitemap_enabled        – bool master switch (default true)
 *   pearblog_sitemap_posts_per_page – posts per paginated XML file (default 500)
 *   pearblog_sitemap_include_images – bool include image sitemap (default true)
 *   pearblog_sitemap_include_video  – bool include video sitemap (default true)
 *   pearblog_sitemap_include_news   – bool include Google News sitemap (default false)
 *   pearblog_sitemap_ping_google    – bool auto-ping Google on publish (default true)
 *   pearblog_sitemap_ping_bing      – bool auto-ping Bing on publish (default true)
 *
 * @package PearBlogEngine\SEO
 */

declare(strict_types=1);

namespace PearBlogEngine\SEO;

/**
 * Generates and serves XML sitemaps.
 */
class XmlSitemapManager {

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Rewrite tag for sitemap type queries. */
	private const QUERY_VAR = 'pearblog_sitemap_type';

	/** Rewrite tag for sitemap page (for paginated sitemaps). */
	private const QUERY_PAGE = 'pearblog_sitemap_page';

	// -----------------------------------------------------------------------
	// Bootstrap
	// -----------------------------------------------------------------------

	/**
	 * Register hooks.
	 */
	public function register(): void {
		if ( ! (bool) get_option( 'pearblog_sitemap_enabled', true ) ) {
			return;
		}

		add_action( 'init', [ $this, 'register_rewrites' ] );
		add_action( 'template_redirect', [ $this, 'maybe_serve_sitemap' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );

		// Ping search engines on publish.
		if ( (bool) get_option( 'pearblog_sitemap_ping_google', true ) ) {
			add_action( 'publish_post', [ $this, 'ping_google' ], 20 );
		}
		if ( (bool) get_option( 'pearblog_sitemap_ping_bing', true ) ) {
			add_action( 'publish_post', [ $this, 'ping_bing' ], 20 );
		}

		// Add sitemap link to robots.txt.
		add_filter( 'robots_txt', [ $this, 'inject_sitemap_in_robots' ], 10, 1 );
	}

	// -----------------------------------------------------------------------
	// Rewrites
	// -----------------------------------------------------------------------

	/**
	 * Register rewrite rules for sitemap URLs.
	 */
	public function register_rewrites(): void {
		add_rewrite_rule( '^pearblog-sitemap-index\.xml$', 'index.php?' . self::QUERY_VAR . '=index', 'top' );
		add_rewrite_rule( '^pearblog-sitemap-images\.xml$', 'index.php?' . self::QUERY_VAR . '=images', 'top' );
		add_rewrite_rule( '^pearblog-sitemap-video\.xml$', 'index.php?' . self::QUERY_VAR . '=video', 'top' );
		add_rewrite_rule( '^pearblog-sitemap-news\.xml$', 'index.php?' . self::QUERY_VAR . '=news', 'top' );
		add_rewrite_rule(
			'^pearblog-sitemap-posts-(\d+)\.xml$',
			'index.php?' . self::QUERY_VAR . '=posts&' . self::QUERY_PAGE . '=$matches[1]',
			'top'
		);

		add_filter( 'query_vars', function ( array $vars ): array {
			$vars[] = self::QUERY_VAR;
			$vars[] = self::QUERY_PAGE;
			return $vars;
		} );
	}

	/**
	 * Intercept sitemap requests and serve XML.
	 */
	public function maybe_serve_sitemap(): void {
		$type = get_query_var( self::QUERY_VAR );
		if ( ! $type ) {
			return;
		}

		$page = max( 1, (int) ( get_query_var( self::QUERY_PAGE ) ?: 1 ) );

		switch ( $type ) {
			case 'index':
				$this->serve_index();
				break;
			case 'posts':
				$this->serve_posts( $page );
				break;
			case 'images':
				$this->serve_images();
				break;
			case 'video':
				$this->serve_video();
				break;
			case 'news':
				$this->serve_news();
				break;
		}
		exit;
	}

	// -----------------------------------------------------------------------
	// REST endpoints
	// -----------------------------------------------------------------------

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/sitemap/status', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_status' ],
			'permission_callback' => [ $this, 'rest_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/sitemap/ping', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_ping' ],
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
	 * GET /sitemap/status – return sitemap URLs and post counts.
	 */
	public function rest_status( \WP_REST_Request $request ): \WP_REST_Response {
		$per_page   = (int) get_option( 'pearblog_sitemap_posts_per_page', 500 );
		$post_count = (int) wp_count_posts()->publish;
		$pages      = max( 1, (int) ceil( $post_count / $per_page ) );

		$urls = [ home_url( 'pearblog-sitemap-index.xml' ) ];
		for ( $p = 1; $p <= $pages; $p++ ) {
			$urls[] = home_url( "pearblog-sitemap-posts-{$p}.xml" );
		}
		if ( (bool) get_option( 'pearblog_sitemap_include_images', true ) ) {
			$urls[] = home_url( 'pearblog-sitemap-images.xml' );
		}
		if ( (bool) get_option( 'pearblog_sitemap_include_video', true ) ) {
			$urls[] = home_url( 'pearblog-sitemap-video.xml' );
		}
		if ( (bool) get_option( 'pearblog_sitemap_include_news', false ) ) {
			$urls[] = home_url( 'pearblog-sitemap-news.xml' );
		}

		return new \WP_REST_Response( [
			'post_count' => $post_count,
			'pages'      => $pages,
			'sitemaps'   => $urls,
		], 200 );
	}

	/**
	 * POST /sitemap/ping – ping Google and Bing manually.
	 */
	public function rest_ping( \WP_REST_Request $request ): \WP_REST_Response {
		$this->ping_google();
		$this->ping_bing();
		return new \WP_REST_Response( [ 'pinged' => true ], 200 );
	}

	// -----------------------------------------------------------------------
	// Sitemap generators
	// -----------------------------------------------------------------------

	/**
	 * Serve the sitemap index XML.
	 */
	private function serve_index(): void {
		$per_page   = (int) get_option( 'pearblog_sitemap_posts_per_page', 500 );
		$post_count = (int) wp_count_posts()->publish;
		$pages      = max( 1, (int) ceil( $post_count / $per_page ) );

		header( 'Content-Type: application/xml; charset=UTF-8' );
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		for ( $p = 1; $p <= $pages; $p++ ) {
			$url = esc_url( home_url( "pearblog-sitemap-posts-{$p}.xml" ) );
			echo "  <sitemap><loc>{$url}</loc></sitemap>\n";
		}

		if ( (bool) get_option( 'pearblog_sitemap_include_images', true ) ) {
			$url = esc_url( home_url( 'pearblog-sitemap-images.xml' ) );
			echo "  <sitemap><loc>{$url}</loc></sitemap>\n";
		}
		if ( (bool) get_option( 'pearblog_sitemap_include_video', true ) ) {
			$url = esc_url( home_url( 'pearblog-sitemap-video.xml' ) );
			echo "  <sitemap><loc>{$url}</loc></sitemap>\n";
		}
		if ( (bool) get_option( 'pearblog_sitemap_include_news', false ) ) {
			$url = esc_url( home_url( 'pearblog-sitemap-news.xml' ) );
			echo "  <sitemap><loc>{$url}</loc></sitemap>\n";
		}

		echo '</sitemapindex>' . "\n";
	}

	/**
	 * Serve a paginated posts sitemap.
	 *
	 * @param int $page Page number.
	 */
	private function serve_posts( int $page ): void {
		$per_page = (int) get_option( 'pearblog_sitemap_posts_per_page', 500 );

		$query = new \WP_Query( [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'no_found_rows'  => false,
		] );

		header( 'Content-Type: application/xml; charset=UTF-8' );
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		foreach ( $query->posts as $post ) {
			$loc     = esc_url( get_permalink( $post ) );
			$lastmod = esc_html( get_post_modified_time( 'Y-m-d', true, $post ) );
			echo "  <url><loc>{$loc}</loc><lastmod>{$lastmod}</lastmod><changefreq>monthly</changefreq><priority>0.7</priority></url>\n";
		}

		echo '</urlset>' . "\n";
	}

	/**
	 * Serve the image sitemap.
	 */
	private function serve_images(): void {
		$posts = get_posts( [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 2000,
			'meta_key'       => '_thumbnail_id',
		] );

		header( 'Content-Type: application/xml; charset=UTF-8' );
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
		   . ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

		foreach ( $posts as $post ) {
			$thumb_id = (int) get_post_thumbnail_id( $post );
			if ( ! $thumb_id ) {
				continue;
			}
			$img_url = esc_url( wp_get_attachment_url( $thumb_id ) );
			$caption = esc_html( wp_get_attachment_caption( $thumb_id ) ?: $post->post_title );
			$loc     = esc_url( get_permalink( $post ) );
			echo "  <url><loc>{$loc}</loc>"
			   . "<image:image><image:loc>{$img_url}</image:loc>"
			   . "<image:caption>{$caption}</image:caption></image:image>"
			   . "</url>\n";
		}

		echo '</urlset>' . "\n";
	}

	/**
	 * Serve a simple video sitemap for posts with video scripts.
	 */
	private function serve_video(): void {
		$posts = get_posts( [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 500,
			'meta_key'       => 'pearblog_video_script_youtube',
			'meta_compare'   => '!=',
			'meta_value'     => '',
		] );

		header( 'Content-Type: application/xml; charset=UTF-8' );
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
		   . ' xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . "\n";

		foreach ( $posts as $post ) {
			$loc         = esc_url( get_permalink( $post ) );
			$title       = esc_html( $post->post_title );
			$description = esc_html( wp_trim_words( $post->post_excerpt ?: $post->post_content, 25 ) );
			$thumb_url   = esc_url( get_the_post_thumbnail_url( $post, 'large' ) ?: '' );
			echo "  <url><loc>{$loc}</loc>"
			   . "<video:video>"
			   . "<video:thumbnail_loc>{$thumb_url}</video:thumbnail_loc>"
			   . "<video:title>{$title}</video:title>"
			   . "<video:description>{$description}</video:description>"
			   . "</video:video>"
			   . "</url>\n";
		}

		echo '</urlset>' . "\n";
	}

	/**
	 * Serve a Google News sitemap (posts < 48 h old).
	 */
	private function serve_news(): void {
		$since = gmdate( 'Y-m-d H:i:s', strtotime( '-48 hours' ) );

		$posts = get_posts( [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 1000,
			'date_query'     => [ [ 'after' => $since, 'inclusive' => true ] ],
		] );

		$site_name = esc_html( get_bloginfo( 'name' ) );

		header( 'Content-Type: application/xml; charset=UTF-8' );
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
		   . ' xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";

		foreach ( $posts as $post ) {
			$loc      = esc_url( get_permalink( $post ) );
			$title    = esc_html( $post->post_title );
			$pub_date = esc_html( get_post_time( 'Y-m-d', true, $post ) );
			$lang     = esc_html( get_option( 'pearblog_language', 'en' ) );
			echo "  <url><loc>{$loc}</loc>"
			   . "<news:news>"
			   . "<news:publication><news:name>{$site_name}</news:name><news:language>{$lang}</news:language></news:publication>"
			   . "<news:publication_date>{$pub_date}</news:publication_date>"
			   . "<news:title>{$title}</news:title>"
			   . "</news:news>"
			   . "</url>\n";
		}

		echo '</urlset>' . "\n";
	}

	// -----------------------------------------------------------------------
	// Search engine pinging
	// -----------------------------------------------------------------------

	/**
	 * Ping Google to notify about sitemap update.
	 *
	 * @param int $post_id Unused (hook context).
	 */
	public function ping_google( int $post_id = 0 ): void {
		$sitemap_url = home_url( 'pearblog-sitemap-index.xml' );
		wp_remote_get(
			'https://www.google.com/ping?sitemap=' . rawurlencode( $sitemap_url ),
			[ 'timeout' => 10, 'blocking' => false ]
		);
	}

	/**
	 * Ping Bing to notify about sitemap update.
	 *
	 * @param int $post_id Unused (hook context).
	 */
	public function ping_bing( int $post_id = 0 ): void {
		$sitemap_url = home_url( 'pearblog-sitemap-index.xml' );
		wp_remote_get(
			'https://www.bing.com/ping?sitemap=' . rawurlencode( $sitemap_url ),
			[ 'timeout' => 10, 'blocking' => false ]
		);
	}

	/**
	 * Inject sitemap location into robots.txt.
	 *
	 * @param string $output Current robots.txt content.
	 * @return string        Modified content.
	 */
	public function inject_sitemap_in_robots( string $output ): string {
		$sitemap_url = home_url( 'pearblog-sitemap-index.xml' );
		return $output . "\nSitemap: " . $sitemap_url . "\n";
	}
}

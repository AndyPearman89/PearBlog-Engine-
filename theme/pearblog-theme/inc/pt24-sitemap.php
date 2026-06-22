<?php
/**
 * PT24.PRO — XML sitemap + robots.txt Sitemap directive.
 *
 * WordPress core wp-sitemap.xml is unavailable on this install and would emit
 * origin-host URLs anyway. This provides a clean /sitemap.xml listing the
 * homepage, the seeded static pages and all service x city landings, using the
 * PUBLIC pt24.pro domain so search engines crawl the right URLs.
 *
 * Required ONLY on the PT24 install (host-guarded in functions.php).
 *
 * @package PearBlog
 * @subpackage PT24
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Absolute public URL helper (falls back if the SEO helper isn't loaded yet).
 *
 * @param string $path Request path.
 * @return string
 */
function pt24_sitemap_url( $path = '/' ) {
    if ( function_exists( 'pt24_public_home_url' ) ) {
        return pt24_public_home_url( $path );
    }
    $path = '/' . ltrim( (string) $path, '/' );
    return 'https://pt24.pro' . ( '/' === $path ? '/' : $path );
}

/**
 * Build the list of sitemap entries.
 *
 * @return array<int,array{loc:string,priority:string}>
 */
function pt24_sitemap_entries() {
    $entries = array();

    // Homepage.
    $entries[] = array( 'loc' => pt24_sitemap_url( '/' ), 'priority' => '1.0' );

    // Seeded static pages.
    $pages = array( 'jak-to-dziala', 'dla-firm', 'o-nas', 'kontakt', 'polityka-prywatnosci', 'regulamin' );
    foreach ( $pages as $slug ) {
        $entries[] = array( 'loc' => pt24_sitemap_url( '/' . $slug . '/' ), 'priority' => '0.6' );
    }

    // Blog index + published posts.
    $entries[]  = array( 'loc' => pt24_sitemap_url( '/blog/' ), 'priority' => '0.6' );
    $home_path  = untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
    $blog_posts = get_posts( array(
        'post_type'        => 'post',
        'post_status'      => 'publish',
        'numberposts'      => 200,
        'suppress_filters' => true,
    ) );
    foreach ( $blog_posts as $blog_post ) {
        $post_path = (string) wp_parse_url( (string) get_permalink( $blog_post ), PHP_URL_PATH );
        if ( '' !== $home_path && 0 === strpos( $post_path, $home_path ) ) {
            $post_path = substr( $post_path, strlen( $home_path ) );
        }
        if ( '' === $post_path ) {
            $post_path = '/';
        }
        $entries[] = array( 'loc' => pt24_sitemap_url( $post_path ), 'priority' => '0.5' );
    }

    // Service x city landings.
    if ( class_exists( 'PearBlog_PT24_Landing_CPT' ) ) {
        $services = array_keys( PearBlog_PT24_Landing_CPT::get_services() );
        $cities   = array_keys( PearBlog_PT24_Landing_CPT::get_cities() );
    } else {
        $services = array( 'hydraulik', 'elektryk', 'mechanik', 'pompa-ciepla', 'remont-lazienki', 'fotowoltaika' );
        $cities   = array( 'warszawa', 'krakow', 'wroclaw', 'poznan', 'gdansk', 'katowice' );
    }
    foreach ( $cities as $city ) {
        foreach ( $services as $service ) {
            $entries[] = array(
                'loc'      => pt24_sitemap_url( '/' . $city . '/' . $service . '/' ),
                'priority' => '0.8',
            );
            $entries[] = array(
                'loc'      => pt24_sitemap_url( '/ranking/' . $city . '/' . $service . '/' ),
                'priority' => '0.6',
            );
        }
    }

    // Company catalogue + profiles.
    $entries[] = array( 'loc' => pt24_sitemap_url( '/firmy/' ), 'priority' => '0.6' );
    $firms = get_posts( array(
        'post_type'        => 'pt24_firm',
        'post_status'      => 'publish',
        'numberposts'      => 200,
        'suppress_filters' => true,
    ) );
    foreach ( $firms as $firm ) {
        $entries[] = array( 'loc' => pt24_sitemap_url( '/firma/' . $firm->post_name . '/' ), 'priority' => '0.5' );
    }

    return $entries;
}

/**
 * Emit the XML sitemap when /sitemap.xml is requested, then exit.
 *
 * Hooked early on init because rewrite rules are unreliable on this host; the
 * request path is matched directly against the home path + 'sitemap.xml'.
 */
function pt24_maybe_output_sitemap() {
    $request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    $request_path = (string) wp_parse_url( $request_uri, PHP_URL_PATH );

    $home_path = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
    $home_path = ( '' === $home_path || '/' === $home_path ) ? '' : untrailingslashit( $home_path );

    $rel = $request_path;
    if ( '' !== $home_path && 0 === strpos( $rel, $home_path ) ) {
        $rel = substr( $rel, strlen( $home_path ) );
    }
    if ( 'sitemap.xml' !== trim( $rel, '/' ) ) {
        return;
    }

    if ( ! headers_sent() ) {
        status_header( 200 );
        header( 'Content-Type: application/xml; charset=UTF-8' );
    }

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ( pt24_sitemap_entries() as $entry ) {
        echo '  <url><loc>' . esc_url( $entry['loc'] ) . '</loc>'
            . '<changefreq>weekly</changefreq>'
            . '<priority>' . esc_html( $entry['priority'] ) . '</priority>'
            . '</url>' . "\n";
    }
    echo '</urlset>';
    exit;
}
add_action( 'init', 'pt24_maybe_output_sitemap', 1 );

/**
 * Advertise the sitemap in robots.txt.
 *
 * @param string $output Existing robots.txt body.
 * @return string
 */
function pt24_robots_sitemap( $output ) {
    $output .= "\nSitemap: " . pt24_sitemap_url( '/sitemap.xml' ) . "\n";
    return $output;
}
add_filter( 'robots_txt', 'pt24_robots_sitemap', 10, 1 );

<?php
/**
 * PT24.PRO Theme Functions
 *
 * @package PT24
 * @version 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

define('PT24_VERSION', '1.0.0');
define('PT24_DIR', get_template_directory());
define('PT24_URI', get_template_directory_uri());

/**
 * Theme setup
 */
function pt24_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ]);
    add_theme_support('custom-logo', [
        'height'      => 40,
        'width'       => 40,
        'flex-height' => true,
        'flex-width'  => true,
    ]);

    register_nav_menus([
        'primary'   => __('Menu główne', 'pt24'),
        'footer'    => __('Menu stopki', 'pt24'),
    ]);
}
add_action('after_setup_theme', 'pt24_setup');

/**
 * Enqueue scripts and styles
 */
function pt24_scripts() {
    // Google Fonts
    wp_enqueue_style(
        'pt24-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@700;800&display=swap',
        [],
        null
    );

    // Tailwind CDN (production should use compiled CSS)
    wp_enqueue_script(
        'pt24-tailwind',
        'https://cdn.tailwindcss.com',
        [],
        null,
        false
    );

    // Tailwind config — must appear right after the CDN script
    wp_add_inline_script('pt24-tailwind', pt24_tailwind_config());

    // Theme CSS
    wp_enqueue_style(
        'pt24-theme',
        PT24_URI . '/assets/css/pt24-theme.css',
        [],
        PT24_VERSION
    );

    // Theme JS
    wp_enqueue_script(
        'pt24-theme',
        PT24_URI . '/assets/js/pt24-theme.js',
        [],
        PT24_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', 'pt24_scripts');

/**
 * Public PT24 base URL used in frontend SEO/link output.
 */
function pt24_public_base_url() {
    return 'https://pt24.pro';
}

/**
 * Whether URL rewriting should run for the current request.
 *
 * Keep wp-admin/AJAX/REST internals untouched.
 */
function pt24_should_rewrite_public_urls() {
    if (is_admin() || wp_doing_ajax()) {
        return false;
    }

    // wp-login.php is outside wp-admin, but must keep core URL/cookie behavior intact.
    $pagenow = isset($GLOBALS['pagenow']) ? (string) $GLOBALS['pagenow'] : '';
    if ($pagenow === 'wp-login.php' || $pagenow === 'wp-register.php') {
        return false;
    }

    if (defined('REST_REQUEST') && REST_REQUEST) {
        return false;
    }

    if (defined('WP_CLI') && WP_CLI) {
        return false;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    if ($request_uri !== '') {
        $request_path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
        $core_prefixes = [
            '/wp-admin',
            '/wp-login.php',
            '/wp-register.php',
            '/wp-json',
            '/xmlrpc.php',
        ];

        foreach ($core_prefixes as $prefix) {
            if (strpos($request_path, $prefix) === 0) {
                return false;
            }
        }
    }

    return true;
}

/**
 * Rewrite origin host URLs to the public PT24 domain.
 *
 * @param string $url URL to normalize.
 * @return string
 */
function pt24_rewrite_to_public_url($url) {
    if (! is_string($url) || $url === '') {
        return $url;
    }

    return str_replace(
        [
            'https://wordpress2614653.home.pl/pt24',
            'http://wordpress2614653.home.pl/pt24',
            '//wordpress2614653.home.pl/pt24',
            'https:\\/\\/wordpress2614653.home.pl\\/pt24',
            'http:\\/\\/wordpress2614653.home.pl\\/pt24',
            'wordpress2614653.home.pl%2Fpt24',
            'wordpress2614653.home.pl%2fpt24',
            'wordpress2614653.home.pl',
            'https://pt24.pro/pt24',
            'http://pt24.pro/pt24',
            '//pt24.pro/pt24',
            'https:\\/\\/pt24.pro\\/pt24',
            'http:\\/\\/pt24.pro\\/pt24',
        ],
        [
            pt24_public_base_url(),
            pt24_public_base_url(),
            str_replace('https:', '', pt24_public_base_url()),
            'https:\\/\\/pt24.pro',
            'https:\\/\\/pt24.pro',
            'pt24.pro',
            'pt24.pro',
            'pt24.pro',
            pt24_public_base_url(),
            pt24_public_base_url(),
            str_replace('https:', '', pt24_public_base_url()),
            'https:\\/\\/pt24.pro',
            'https:\\/\\/pt24.pro',
        ],
        $url
    );
}

/**
 * Frontend-only URL normalization for core-generated links.
 */
function pt24_filter_public_frontend_url($url) {
    if (! pt24_should_rewrite_public_urls()) {
        return $url;
    }

    return pt24_rewrite_to_public_url($url);
}
add_filter('home_url', 'pt24_filter_public_frontend_url', 20);
add_filter('site_url', 'pt24_filter_public_frontend_url', 20);
add_filter('rest_url', 'pt24_filter_public_frontend_url', 20);
add_filter('get_shortlink', 'pt24_filter_public_frontend_url', 20);

/**
 * Keep Yoast canonical and OG URL on the public PT24 domain.
 */
function pt24_filter_wpseo_public_url($url) {
    if (! pt24_should_rewrite_public_urls()) {
        return $url;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    $request_path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
    $segments = array_values(array_filter(explode('/', trim($request_path, '/'))));

    if (!empty($segments) && strtolower((string) $segments[0]) === 'pt24') {
        array_shift($segments);
    }

    if (!empty($segments) && strtolower((string) $segments[0]) === 'uslugi') {
        if (isset($segments[1]) && $segments[1] !== '') {
            return pt24_public_base_url() . '/uslugi/' . sanitize_title((string) $segments[1]) . '/';
        }
        return pt24_public_base_url() . '/uslugi/';
    }

    if (!empty($segments) && strtolower((string) $segments[0]) === 'miasta') {
        return pt24_public_base_url() . '/miasta/';
    }

    if (!empty($segments) && in_array(strtolower((string) $segments[0]), ['panel', 'panel-firmy', 'admin'], true)) {
        return pt24_public_base_url() . '/' . sanitize_title((string) $segments[0]) . '/';
    }

    return pt24_filter_public_frontend_url($url);
}
add_filter('wpseo_canonical', 'pt24_filter_wpseo_public_url', 20);
add_filter('wpseo_opengraph_url', 'pt24_filter_wpseo_public_url', 20);

/**
 * Route-aware title correction for PT24 custom paths.
 */
function pt24_pre_get_document_title($title) {
    if (! pt24_should_rewrite_public_urls()) {
        return $title;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    $request_path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
    $segments = array_values(array_filter(explode('/', trim($request_path, '/'))));

    if (!empty($segments) && strtolower((string) $segments[0]) === 'pt24') {
        array_shift($segments);
    }

    if (!empty($segments) && strtolower((string) $segments[0]) === 'uslugi') {
        if (isset($segments[1]) && $segments[1] !== '') {
            return ucfirst(str_replace('-', ' ', sanitize_title((string) $segments[1]))) . ' - PT24.PRO';
        }
        return 'Usługi - PT24.PRO';
    }

    if (!empty($segments) && strtolower((string) $segments[0]) === 'miasta') {
        return 'Miasta - PT24.PRO';
    }

    if (!empty($segments) && strtolower((string) $segments[0]) === 'panel') {
        return 'Panel użytkownika - PT24.PRO';
    }

    if (!empty($segments) && strtolower((string) $segments[0]) === 'panel-firmy') {
        return 'Panel firmy - PT24.PRO';
    }

    if (!empty($segments) && strtolower((string) $segments[0]) === 'admin') {
        return 'Panel administratora - PT24.PRO';
    }

    return $title;
}
add_filter('pre_get_document_title', 'pt24_pre_get_document_title', 20);

/**
 * Rewrite any remaining origin-host URLs in final frontend HTML.
 *
 * This catches Yoast JSON-LD fields that may bypass URL filters.
 */
function pt24_buffer_public_host() {
    if (! pt24_should_rewrite_public_urls()) {
        return;
    }

    ob_start('pt24_rewrite_public_host_html');
}

/**
 * Output-buffer callback for host normalization.
 *
 * @param string $html Rendered HTML.
 * @return string
 */
function pt24_rewrite_public_host_html($html) {
    if (! is_string($html) || $html === '') {
        return $html;
    }

    return pt24_rewrite_to_public_url($html);
}
add_action('template_redirect', 'pt24_buffer_public_host', 1);

/**
 * Prevent WordPress from deferring the Tailwind CDN script.
 * Tailwind CDN must execute synchronously before the page renders.
 */
function pt24_prevent_tailwind_defer($tag, $handle) {
    if ($handle === 'pt24-tailwind') {
        // Remove any defer/async attributes WP may add
        $tag = str_replace(' defer', '', $tag);
        $tag = str_replace(' async', '', $tag);
        // Ensure blocking render
        $tag = str_replace(' type="text/javascript"', '', $tag);
    }
    return $tag;
}
add_filter('script_loader_tag', 'pt24_prevent_tailwind_defer', 10, 2);

/**
 * Tailwind configuration inline script
 */
function pt24_tailwind_config() {
    return "tailwind.config = {
        theme: {
            extend: {
                colors: {
                    brand: { start: '#1464F4', end: '#7A4FD3', mid: '#4A5FE3' },
                    pear: { green: '#4ADE80', blue: '#60A5FA' }
                },
                fontFamily: {
                    display: ['Poppins', 'system-ui', 'sans-serif'],
                    body: ['Inter', 'system-ui', 'sans-serif']
                },
                boxShadow: {
                    soft: '0 20px 60px -28px rgba(15,23,42,0.35)',
                    card: '0 4px 24px -4px rgba(15,23,42,0.08)',
                    glow: '0 0 40px -8px rgba(20,100,244,0.3)'
                }
            }
        }
    };";
}

/**
 * Get PT24 brand colors
 */
function pt24_get_colors() {
    return [
        'brand_start' => '#1464F4',
        'brand_mid'   => '#4A5FE3',
        'brand_end'   => '#7A4FD3',
        'pear_green'  => '#4ADE80',
        'pear_blue'   => '#60A5FA',
    ];
}

/**
 * Service categories data
 */
function pt24_get_categories() {
    return [
        ['name' => 'Hydraulik',                'slug' => '/hydraulik/',      'icon' => '💧'],
        ['name' => 'Elektryk',                 'slug' => '/elektryk/',       'icon' => '⚡'],
        ['name' => 'Mechanik samochodowy',     'slug' => '/mechanik/',       'icon' => '🔧'],
        ['name' => 'Klimatyzacja i wentylacja','slug' => '/klimatyzacja/',   'icon' => '❄️'],
        ['name' => 'Informatyk / IT',          'slug' => '/informatyk/',     'icon' => '💻'],
        ['name' => 'Złota rączka',             'slug' => '/zlota-raczka/',   'icon' => '🛠️'],
        ['name' => 'Malarz / Wykończenia',     'slug' => '/malarz/',         'icon' => '🎨'],
        ['name' => 'Przeprowadzki',            'slug' => '/przeprowadzki/',  'icon' => '📦'],
        ['name' => 'Ogrodnik',                 'slug' => '/ogrodnik/',       'icon' => '🌱'],
    ];
}

/**
 * Popular searches data
 */
function pt24_get_popular_searches() {
    return [
        'Hydraulik Warszawa',
        'Hydraulik Kraków',
        'Elektryk Warszawa',
        'Elektryk Kraków',
        'Mechanik Katowice',
        'Informatyk Wrocław',
        'Klimatyzacja Poznań',
        'Złota rączka Gdańsk',
        'Malarz Łódź',
    ];
}

/**
 * Testimonials data
 */
function pt24_get_testimonials() {
    return [
        [
            'text'     => '„Zgłosiłem awarię hydrauliki wieczorem — w 30 minut miałem kontakt do fachowca. Profesjonalna obsługa!"',
            'author'   => 'Anna K.',
            'location' => 'Warszawa',
        ],
        [
            'text'     => '„Świetna jakość i szybkie porównanie ofert. Bardzo intuicyjna platforma, polecam każdemu."',
            'author'   => 'Michał W.',
            'location' => 'Kraków',
        ],
        [
            'text'     => '„Dodałem zlecenie i od razu dostałem kilka odpowiedzi od lokalnych specjalistów. Super system."',
            'author'   => 'Karolina M.',
            'location' => 'Katowice',
        ],
    ];
}

/**
 * Yandex Metrica tracking code.
 * Set counter ID via: wp option update pt24_yandex_metrica_id YOUR_ID
 */
function pt24_yandex_metrica() {
    $counter_id = get_option('pt24_yandex_metrica_id', '');
    if (empty($counter_id) || is_admin()) {
        return;
    }
    ?>
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for(var j=0;j<document.scripts.length;j++){if(document.scripts[j].src===r)return;}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
        (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

        ym(<?php echo esc_js($counter_id); ?>, "init", {
            clickmap:true,
            trackLinks:true,
            accurateTrackBounce:true,
            webvisor:true
        });
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/<?php echo esc_attr($counter_id); ?>" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <!-- /Yandex.Metrika counter -->
    <?php
}
add_action('wp_head', 'pt24_yandex_metrica', 99);

/**
 * Register custom panel routes.
 */
function pt24_register_panel_routes() {
    add_rewrite_rule('^panel/?$', 'index.php?pt24_panel=user', 'top');
    add_rewrite_rule('^panel-firmy/?$', 'index.php?pt24_panel=company', 'top');
    add_rewrite_rule('^admin/?$', 'index.php?pt24_panel=admin', 'top');
    add_rewrite_rule('^uslugi/?$', 'index.php?pt24_service_hub=index', 'top');
    add_rewrite_rule('^uslugi/([^/]+)/?$', 'index.php?pt24_service_hub=$matches[1]', 'top');
    add_rewrite_rule('^([^/]+)/([^/]+)/?$', 'index.php?pt24_category=$matches[1]&pt24_city=$matches[2]', 'top');
    add_rewrite_rule('^miasta/?$', 'index.php?pt24_geo=city-index', 'top');
    add_rewrite_rule('^(katowice|gliwice|zabrze|bytom|krakow|warszawa)/?$', 'index.php?pt24_city_landing=$matches[1]', 'top');
    add_rewrite_rule('^(montaz-klimatyzacji|udraznianie-kanalizacji|awaria-pradu|wymiana-dachu)/?$', 'index.php?pt24_specific_service=$matches[1]', 'top');
    add_rewrite_rule('^(montaz-klimatyzacji|udraznianie-kanalizacji|awaria-pradu|wymiana-dachu)/(katowice|gliwice|zabrze|bytom|krakow|warszawa)/?$', 'index.php?pt24_specific_service=$matches[1]&pt24_city_landing=$matches[2]', 'top');
}
add_action('init', 'pt24_register_panel_routes');

/**
 * Add query var for custom panel routing.
 */
function pt24_add_panel_query_var($vars) {
    $vars[] = 'pt24_panel';
    $vars[] = 'pt24_service_hub';
    $vars[] = 'pt24_geo';
    $vars[] = 'pt24_city_landing';
    $vars[] = 'pt24_specific_service';
    $vars[] = 'pt24_category';
    $vars[] = 'pt24_city';
    return $vars;
}
add_filter('query_vars', 'pt24_add_panel_query_var');

/**
 * Reserved first URL segments that must stay under core WordPress routing.
 *
 * @param string $segment First path segment.
 * @return bool
 */
function pt24_is_reserved_route_segment($segment) {
    if (! is_string($segment) || $segment === '') {
        return false;
    }

    $segment = strtolower(sanitize_title($segment));
    $reserved = [
        'author',
        'category',
        'tag',
        'search',
        'feed',
        'blog',
        'wp-json',
        'wp-admin',
        'wp-content',
        'wp-includes',
        'index-php',
    ];

    return in_array($segment, $reserved, true);
}

/**
 * Route custom panel slugs to dedicated templates.
 */
function pt24_panel_template_include($template) {
    $specific_service = get_query_var('pt24_specific_service');
    $city_landing = get_query_var('pt24_city_landing');
    $service_category = get_query_var('pt24_category');
    $service_city = get_query_var('pt24_city');

    // Fallback routing for environments where rewrite query vars are not
    // propagated consistently (e.g. proxy/subdirectory setups).
    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    $request_path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
    $segments = array_values(array_filter(explode('/', trim($request_path, '/'))));

    if (!empty($segments) && strtolower((string) $segments[0]) === 'pt24') {
        array_shift($segments);
    }

    if (empty($segments) && function_exists('home_url')) {
        $home_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
        if ($home_path && '/' !== $home_path) {
            $normalized_home_path = trim($home_path, '/');
            if ($normalized_home_path !== '') {
                $segments = array_values(array_filter(explode('/', $normalized_home_path)));
            }
        }
    }

    if (isset($segments[0]) && pt24_is_reserved_route_segment((string) $segments[0])) {
        return $template;
    }

    if (isset($segments[0]) && strtolower((string) $segments[0]) === 'uslugi') {
        if (isset($segments[1]) && $segments[1] !== '') {
            set_query_var('pt24_service_hub', sanitize_title((string) $segments[1]));
        } else {
            set_query_var('pt24_service_hub', 'index');
        }

        $service_template = (isset($segments[1]) && $segments[1] !== '')
            ? PT24_DIR . '/services-single.php'
            : PT24_DIR . '/services-archive.php';

        if (file_exists($service_template)) {
            status_header(200);
            nocache_headers();
            return $service_template;
        }
    }

    if (isset($segments[0]) && strtolower((string) $segments[0]) === 'miasta' && !isset($segments[1])) {
        $cities_template = PT24_DIR . '/cities-archive.php';
        if (file_exists($cities_template)) {
            status_header(200);
            nocache_headers();
            return $cities_template;
        }
    }

    if (is_string($specific_service) && $specific_service !== '' && is_string($city_landing) && $city_landing !== '') {
        $specific_city_template = PT24_DIR . '/specific-service-city.php';
        if (file_exists($specific_city_template)) {
            status_header(200);
            nocache_headers();
            return $specific_city_template;
        }
    }

    if (is_string($service_category) && $service_category !== '' && is_string($service_city) && $service_city !== '') {
        $local_template = PT24_DIR . '/local-service-city.php';
        if (file_exists($local_template)) {
            status_header(200);
            nocache_headers();
            return $local_template;
        }
    }

    $geo = get_query_var('pt24_geo');
    if ($geo === 'city-index') {
        $cities_template = PT24_DIR . '/cities-archive.php';
        if (file_exists($cities_template)) {
            status_header(200);
            nocache_headers();
            return $cities_template;
        }
    }

    if (is_string($city_landing) && $city_landing !== '') {
        $city_template = PT24_DIR . '/city-landing.php';
        if (file_exists($city_template)) {
            status_header(200);
            nocache_headers();
            return $city_template;
        }
    }

    if (is_string($specific_service) && $specific_service !== '') {
        $specific_template = PT24_DIR . '/specific-service.php';
        if (file_exists($specific_template)) {
            status_header(200);
            nocache_headers();
            return $specific_template;
        }
    }

    $service_hub = get_query_var('pt24_service_hub');
    if (is_string($service_hub) && $service_hub !== '') {
        if ($service_hub === 'index') {
            $service_template = PT24_DIR . '/services-archive.php';
        } else {
            $service_template = PT24_DIR . '/services-single.php';
        }

        if (file_exists($service_template)) {
            status_header(200);
            nocache_headers();
            return $service_template;
        }
    }

    $panel = get_query_var('pt24_panel');
    if (! is_string($panel) || $panel === '') {
        return $template;
    }

    if ($panel === 'user') {
        $candidate = PT24_DIR . '/panel-user.php';
    } elseif ($panel === 'company') {
        $candidate = PT24_DIR . '/panel-company.php';
    } elseif ($panel === 'admin') {
        $candidate = PT24_DIR . '/panel-admin.php';
    } else {
        $candidate = '';
    }

    if ($candidate !== '' && file_exists($candidate)) {
        status_header(200);
        nocache_headers();
        return $candidate;
    }

    return $template;
}
add_filter('template_include', 'pt24_panel_template_include', 999);

/**
 * Flush rewrite rules after route changes.
 */
function pt24_maybe_flush_panel_rewrites() {
    $version = 'panel-routes-v5';
    if (get_option('pt24_panel_routes_version') !== $version) {
        pt24_register_panel_routes();
        flush_rewrite_rules(false);
        update_option('pt24_panel_routes_version', $version, false);
    }
}
add_action('init', 'pt24_maybe_flush_panel_rewrites', 99);

/**
 * Remove author archives from native WP sitemap on PT24.
 *
 * Author URLs are not used publicly on this installation.
 */
function pt24_filter_sitemaps_add_provider($provider, $name) {
    if ($name === 'users') {
        return false;
    }

    return $provider;
}
add_filter('wp_sitemaps_add_provider', 'pt24_filter_sitemaps_add_provider', 10, 2);

/**
 * Remove default blog taxonomies from sitemap to avoid soft-404 category URLs.
 */
function pt24_filter_sitemaps_taxonomies($taxonomies) {
    if (isset($taxonomies['category'])) {
        unset($taxonomies['category']);
    }

    if (isset($taxonomies['post_tag'])) {
        unset($taxonomies['post_tag']);
    }

    return $taxonomies;
}
add_filter('wp_sitemaps_taxonomies', 'pt24_filter_sitemaps_taxonomies');

/**
 * Force 404 status for legacy archive paths not used on PT24.
 */
function pt24_force_404_for_legacy_archives() {
    if (is_admin() || wp_doing_ajax()) {
        return;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    $request_path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
    $segments = array_values(array_filter(explode('/', trim($request_path, '/'))));

    if (!empty($segments) && strtolower((string) $segments[0]) === 'pt24') {
        array_shift($segments);
    }

    if (empty($segments)) {
        return;
    }

    $legacy = ['author', 'category', 'tag'];
    $first = strtolower((string) $segments[0]);

    if (! in_array($first, $legacy, true)) {
        return;
    }

    global $wp_query;
    if (isset($wp_query) && is_object($wp_query)) {
        $wp_query->set_404();
    }
    status_header(404);
    nocache_headers();
}
add_action('template_redirect', 'pt24_force_404_for_legacy_archives', 0);

/**
 * Handle company profile contact form submissions.
 */
function pt24_handle_business_contact_form() {
    $business_id = isset($_POST['business_id']) ? (int) $_POST['business_id'] : 0;
    if ($business_id <= 0) {
        wp_safe_redirect(home_url('/?contact=error'));
        exit;
    }

    $nonce = isset($_POST['pt24_contact_nonce']) ? sanitize_text_field((string) $_POST['pt24_contact_nonce']) : '';
    if (! wp_verify_nonce($nonce, 'pt24_business_contact_' . $business_id)) {
        wp_safe_redirect(get_permalink($business_id) . '?contact=error');
        exit;
    }

    $name = isset($_POST['name']) ? sanitize_text_field((string) $_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize_email((string) $_POST['email']) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field((string) $_POST['phone']) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field((string) $_POST['message']) : '';

    if ($name === '' || $email === '' || $message === '' || ! is_email($email)) {
        wp_safe_redirect(get_permalink($business_id) . '?contact=error');
        exit;
    }

    $recipient = (string) get_post_meta($business_id, 'pt24_contact_email', true);
    if ($recipient === '' || ! is_email($recipient)) {
        $author_id = (int) get_post_field('post_author', $business_id);
        $recipient = $author_id > 0 ? (string) get_the_author_meta('user_email', $author_id) : '';
    }

    if ($recipient === '' || ! is_email($recipient)) {
        $recipient = (string) get_option('admin_email');
    }

    $subject = sprintf('Nowe zapytanie do firmy: %s', get_the_title($business_id));
    $body = "Imię i nazwisko: {$name}\n";
    $body .= "Email: {$email}\n";
    $body .= "Telefon: {$phone}\n\n";
    $body .= "Wiadomość:\n{$message}\n";
    $headers = ['Reply-To: ' . $name . ' <' . $email . '>'];

    $sent = wp_mail($recipient, $subject, $body, $headers);
    wp_safe_redirect(get_permalink($business_id) . '?contact=' . ($sent ? 'sent' : 'error'));
    exit;
}
add_action('admin_post_pt24_business_contact', 'pt24_handle_business_contact_form');
add_action('admin_post_nopriv_pt24_business_contact', 'pt24_handle_business_contact_form');

/**
 * Handle service inquiry form submissions.
 */
function pt24_handle_service_inquiry_form() {
    $service_slug = isset($_POST['service_slug']) ? sanitize_title((string) $_POST['service_slug']) : '';
    if ($service_slug === '') {
        wp_safe_redirect(home_url('/uslugi/?inquiry=error'));
        exit;
    }

    $nonce = isset($_POST['pt24_service_inquiry_nonce']) ? sanitize_text_field((string) $_POST['pt24_service_inquiry_nonce']) : '';
    if (! wp_verify_nonce($nonce, 'pt24_service_inquiry_' . $service_slug)) {
        wp_safe_redirect(home_url('/uslugi/' . $service_slug . '/?inquiry=error'));
        exit;
    }

    $name = isset($_POST['name']) ? sanitize_text_field((string) $_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize_email((string) $_POST['email']) : '';
    $city = isset($_POST['city']) ? sanitize_text_field((string) $_POST['city']) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field((string) $_POST['message']) : '';

    if ($name === '' || $email === '' || $message === '' || ! is_email($email)) {
        wp_safe_redirect(home_url('/uslugi/' . $service_slug . '/?inquiry=error'));
        exit;
    }

    $service_term = get_term_by('slug', $service_slug, 'pt24_service_cat');
    $service_name = is_object($service_term) && isset($service_term->name)
        ? (string) $service_term->name
        : ucfirst(str_replace('-', ' ', $service_slug));

    $subject = sprintf('Nowe zapytanie o usługę: %s', $service_name);
    $body = "Imię i nazwisko: {$name}\n";
    $body .= "Email: {$email}\n";
    $body .= "Miasto: {$city}\n\n";
    $body .= "Wiadomość:\n{$message}\n";
    $headers = ['Reply-To: ' . $name . ' <' . $email . '>'];

    $sent = wp_mail((string) get_option('admin_email'), $subject, $body, $headers);

    wp_safe_redirect(home_url('/uslugi/' . $service_slug . '/?inquiry=' . ($sent ? 'sent' : 'error')));
    exit;
}
add_action('admin_post_pt24_service_inquiry', 'pt24_handle_service_inquiry_form');
add_action('admin_post_nopriv_pt24_service_inquiry', 'pt24_handle_service_inquiry_form');

/**
 * Lead pricing matrix for core PT24 service categories.
 *
 * @return array<string, array{label:string,min:int,max:int}>
 */
function pt24_get_lead_pricing_matrix() {
    return [
        'hydraulik' => ['label' => 'Hydraulik', 'min' => 20, 'max' => 40],
        'elektryk' => ['label' => 'Elektryk', 'min' => 25, 'max' => 50],
        'mechanik' => ['label' => 'Mechanik', 'min' => 20, 'max' => 60],
        'remont' => ['label' => 'Remont', 'min' => 50, 'max' => 150],
        'dach' => ['label' => 'Dach', 'min' => 80, 'max' => 250],
        'pompy-ciepla' => ['label' => 'Pompy ciepla', 'min' => 150, 'max' => 350],
        'fotowoltaika' => ['label' => 'Fotowoltaika', 'min' => 100, 'max' => 300],
    ];
}

/**
 * SaaS subscription plans for PT24 companies.
 *
 * @return array<string, array{name:string,price:int,period:string,features:string[]}>
 */
function pt24_get_subscription_plans() {
    return [
        'free' => [
            'name' => 'Free',
            'price' => 0,
            'period' => 'monthly',
            'features' => [
                'Profil firmy',
                'Opinie',
                'Podstawowa widocznosc',
            ],
        ],
        'starter' => [
            'name' => 'Starter',
            'price' => 49,
            'period' => 'monthly',
            'features' => [
                'Wiecej zdjec',
                'Statystyki',
                'Podstawowy CRM',
            ],
        ],
        'pro' => [
            'name' => 'Pro',
            'price' => 149,
            'period' => 'monthly',
            'features' => [
                'Wiecej leadow',
                'Priorytet',
                'AI',
                'Kalendarz',
            ],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price' => 499,
            'period' => 'monthly',
            'features' => [
                'Wiele oddzialow',
                'API',
                'Wielu pracownikow',
                'Integracje CRM',
            ],
        ],
    ];
}

/**
 * Premium listing upsells.
 *
 * @return array<string, array{label:string,price:int,period:string}>
 */
function pt24_get_premium_listing_packages() {
    return [
        'top3' => ['label' => 'TOP 3', 'price' => 299, 'period' => 'monthly'],
        'sponsored' => ['label' => 'Sponsorowana firma', 'price' => 199, 'period' => 'monthly'],
        'recommended' => ['label' => 'Polecana firma', 'price' => 149, 'period' => 'monthly'],
        'badge' => ['label' => 'Premium Badge', 'price' => 79, 'period' => 'monthly'],
        'highlight' => ['label' => 'Kolorowe wyroznienie', 'price' => 99, 'period' => 'monthly'],
    ];
}

/**
 * Lead credit packages for one-off purchases.
 *
 * @return array<string, array{label:string,credits:int,price:int,currency:string}>
 */
function pt24_get_lead_credit_packages() {
    return [
        'pack_25' => ['label' => 'Pakiet 25 leadow', 'credits' => 25, 'price' => 399, 'currency' => 'PLN'],
        'pack_60' => ['label' => 'Pakiet 60 leadow', 'credits' => 60, 'price' => 899, 'currency' => 'PLN'],
        'pack_150' => ['label' => 'Pakiet 150 leadow', 'credits' => 150, 'price' => 1999, 'currency' => 'PLN'],
    ];
}

/**
 * Included monthly lead credits by subscription plan.
 *
 * @return array<string, int>
 */
function pt24_get_plan_lead_allowances() {
    return [
        'free' => 0,
        'starter' => 10,
        'pro' => 40,
        'enterprise' => 160,
    ];
}

/**
 * Get monetization state for a company user.
 *
 * @param int $user_id User ID.
 * @return array{plan:string,credits:int,included:int}
 */
function pt24_get_company_monetization_state($user_id) {
    $user_id = (int) $user_id;
    $plans = pt24_get_subscription_plans();
    $allowances = pt24_get_plan_lead_allowances();

    $plan = sanitize_key((string) get_user_meta($user_id, 'pt24_company_plan', true));
    if ($plan === '' || ! isset($plans[$plan])) {
        $plan = 'free';
    }

    $included = (int) get_user_meta($user_id, 'pt24_company_plan_included_leads', true);
    if ($included <= 0 && isset($allowances[$plan])) {
        $included = (int) $allowances[$plan];
    }

    $credits = (int) get_user_meta($user_id, 'pt24_company_lead_credits', true);
    if ($credits < 0) {
        $credits = 0;
    }

    return [
        'plan' => $plan,
        'credits' => $credits,
        'included' => $included,
    ];
}

/**
 * Persist monetization state for company user.
 *
 * @param int    $user_id  User ID.
 * @param string $plan     Plan slug.
 * @param int    $credits  Lead credits.
 * @param int    $included Included monthly leads.
 */
function pt24_set_company_monetization_state($user_id, $plan, $credits, $included) {
    $user_id = (int) $user_id;
    update_user_meta($user_id, 'pt24_company_plan', sanitize_key((string) $plan));
    update_user_meta($user_id, 'pt24_company_lead_credits', max(0, (int) $credits));
    update_user_meta($user_id, 'pt24_company_plan_included_leads', max(0, (int) $included));
}

/**
 * Append billing event to company history.
 *
 * @param int   $user_id User ID.
 * @param array $entry   Billing event payload.
 */
function pt24_append_company_billing_history($user_id, $entry) {
    $user_id = (int) $user_id;
    if ($user_id <= 0 || ! is_array($entry)) {
        return;
    }

    $history = get_user_meta($user_id, 'pt24_company_billing_history', true);
    if (! is_array($history)) {
        $history = [];
    }

    $entry['created_at'] = current_time('mysql');
    array_unshift($history, $entry);
    $history = array_slice($history, 0, 60);
    update_user_meta($user_id, 'pt24_company_billing_history', $history);
}

/**
 * Consume one lead credit for company user.
 *
 * @param int $user_id User ID.
 * @param int $lead_id Lead ID.
 * @return bool
 */
function pt24_consume_company_lead_credit($user_id, $lead_id = 0) {
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return false;
    }

    $state = pt24_get_company_monetization_state($user_id);
    $credits = (int) $state['credits'];

    if ($credits <= 0) {
        pt24_append_company_billing_history($user_id, [
            'type' => 'lead_overdraft',
            'lead_id' => (int) $lead_id,
            'plan' => (string) $state['plan'],
            'credits_after' => 0,
            'amount' => 0,
            'currency' => 'PLN',
        ]);
        return false;
    }

    $credits--;
    pt24_set_company_monetization_state($user_id, (string) $state['plan'], $credits, (int) $state['included']);

    pt24_append_company_billing_history($user_id, [
        'type' => 'lead_consumed',
        'lead_id' => (int) $lead_id,
        'plan' => (string) $state['plan'],
        'credits_after' => $credits,
        'amount' => 1,
        'currency' => 'CREDIT',
    ]);

    return true;
}

/**
 * Parse JSON lead metadata.
 *
 * @param string|null $raw Raw metadata string.
 * @return array
 */
function pt24_parse_lead_metadata($raw) {
    $raw = is_string($raw) ? trim($raw) : '';
    if ($raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        return $decoded;
    }

    return ['legacy_meta' => $raw];
}

/**
 * Resolve PT24 table name across prefix conventions.
 *
 * Supports both `{prefix}pt24_*` and `{prefix}*` variants used by some installs.
 *
 * @param string $logical_name Logical table suffix, e.g. `pt24_leads`.
 * @return string
 */
function pt24_resolve_table_name($logical_name) {
    static $cache = [];

    $logical_name = sanitize_key((string) $logical_name);
    if ($logical_name === '') {
        return '';
    }

    if (isset($cache[$logical_name])) {
        return $cache[$logical_name];
    }

    global $wpdb;
    $candidates = [
        $wpdb->prefix . $logical_name,
        strpos($logical_name, 'pt24_') === 0 ? $wpdb->prefix . substr($logical_name, 5) : '',
        $wpdb->base_prefix . $logical_name,
    ];

    $candidates = array_values(array_unique(array_filter($candidates)));

    foreach ($candidates as $table_name) {
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name));
        if ($exists === $table_name) {
            $cache[$logical_name] = $table_name;
            return $table_name;
        }
    }

    $cache[$logical_name] = $candidates[0];
    return $cache[$logical_name];
}

/**
 * Sync unbilled assigned leads and consume credits once per lead.
 */
function pt24_sync_lead_billing_events() {
    static $processed = false;
    if ($processed) {
        return;
    }
    $processed = true;

    if (is_admin() && ! wp_doing_ajax()) {
        return;
    }

    global $wpdb;
    $leads_table = pt24_resolve_table_name('pt24_leads');
    $contractors_table = pt24_resolve_table_name('pt24_contractors');

    $rows = $wpdb->get_results(
        "SELECT l.id, l.assigned_contractor_id, l.metadata, c.email
        FROM {$leads_table} l
        LEFT JOIN {$contractors_table} c ON c.id = l.assigned_contractor_id
        WHERE l.assigned_contractor_id IS NOT NULL
          AND (
            l.metadata IS NULL
            OR l.metadata = ''
            OR l.metadata NOT LIKE '%\\\"billing_processed\\\":1%'
          )
        ORDER BY l.id ASC
        LIMIT 25"
    );

    if (! is_array($rows) || empty($rows)) {
        return;
    }

    foreach ($rows as $row) {
        $lead_id = isset($row->id) ? (int) $row->id : 0;
        $email = isset($row->email) ? sanitize_email((string) $row->email) : '';
        $meta = pt24_parse_lead_metadata(isset($row->metadata) ? (string) $row->metadata : '');

        if ($lead_id <= 0) {
            continue;
        }

        $user = $email !== '' ? get_user_by('email', $email) : false;
        if (! $user || ! isset($user->ID)) {
            $meta['billing_processed'] = 1;
            $meta['billing_charged'] = 0;
            $meta['billing_note'] = 'missing_user';
            $meta['billing_at'] = current_time('mysql');

            $wpdb->update(
                $leads_table,
                ['metadata' => wp_json_encode($meta)],
                ['id' => $lead_id],
                ['%s'],
                ['%d']
            );
            continue;
        }

        $charged = pt24_consume_company_lead_credit((int) $user->ID, $lead_id);
        $meta['billing_processed'] = 1;
        $meta['billing_charged'] = $charged ? 1 : 0;
        $meta['billing_user_id'] = (int) $user->ID;
        $meta['billing_at'] = current_time('mysql');

        $wpdb->update(
            $leads_table,
            ['metadata' => wp_json_encode($meta)],
            ['id' => $lead_id],
            ['%s'],
            ['%d']
        );
    }
}
add_action('init', 'pt24_sync_lead_billing_events', 30);

/**
 * Handle company plan change from panel.
 */
function pt24_handle_company_plan_change() {
    if (! is_user_logged_in()) {
        wp_safe_redirect(wp_login_url(home_url('/panel-firmy/')));
        exit;
    }

    $user_id = get_current_user_id();
    $nonce = isset($_POST['pt24_company_plan_nonce']) ? sanitize_text_field((string) $_POST['pt24_company_plan_nonce']) : '';
    if (! wp_verify_nonce($nonce, 'pt24_company_plan_' . $user_id)) {
        wp_safe_redirect(home_url('/panel-firmy/?billing=error'));
        exit;
    }

    $plans = pt24_get_subscription_plans();
    $allowances = pt24_get_plan_lead_allowances();
    $next_plan = isset($_POST['plan']) ? sanitize_key((string) $_POST['plan']) : 'free';

    if (! isset($plans[$next_plan])) {
        wp_safe_redirect(home_url('/panel-firmy/?billing=invalid-plan'));
        exit;
    }

    $state = pt24_get_company_monetization_state($user_id);
    $included = isset($allowances[$next_plan]) ? (int) $allowances[$next_plan] : 0;
    $credits = max((int) $state['credits'], $included);

    pt24_set_company_monetization_state($user_id, $next_plan, $credits, $included);
    pt24_append_company_billing_history($user_id, [
        'type' => 'plan_changed',
        'plan' => $next_plan,
        'credits_after' => $credits,
        'amount' => isset($plans[$next_plan]['price']) ? (int) $plans[$next_plan]['price'] : 0,
        'currency' => 'PLN',
    ]);
    wp_safe_redirect(home_url('/panel-firmy/?billing=plan-updated'));
    exit;
}
add_action('admin_post_pt24_company_change_plan', 'pt24_handle_company_plan_change');

/**
 * Handle lead credit package purchase from panel.
 */
function pt24_handle_company_buy_lead_pack() {
    if (! is_user_logged_in()) {
        wp_safe_redirect(wp_login_url(home_url('/panel-firmy/')));
        exit;
    }

    $user_id = get_current_user_id();
    $nonce = isset($_POST['pt24_company_pack_nonce']) ? sanitize_text_field((string) $_POST['pt24_company_pack_nonce']) : '';
    if (! wp_verify_nonce($nonce, 'pt24_company_pack_' . $user_id)) {
        wp_safe_redirect(home_url('/panel-firmy/?billing=error'));
        exit;
    }

    $packages = pt24_get_lead_credit_packages();
    $pack_key = isset($_POST['pack']) ? sanitize_key((string) $_POST['pack']) : '';

    if (! isset($packages[$pack_key])) {
        wp_safe_redirect(home_url('/panel-firmy/?billing=invalid-pack'));
        exit;
    }

    $state = pt24_get_company_monetization_state($user_id);
    $credits = (int) $state['credits'] + (int) $packages[$pack_key]['credits'];

    pt24_set_company_monetization_state($user_id, (string) $state['plan'], $credits, (int) $state['included']);
    pt24_append_company_billing_history($user_id, [
        'type' => 'credits_purchased',
        'plan' => (string) $state['plan'],
        'pack' => $pack_key,
        'credits_added' => (int) $packages[$pack_key]['credits'],
        'credits_after' => $credits,
        'amount' => (int) $packages[$pack_key]['price'],
        'currency' => (string) $packages[$pack_key]['currency'],
    ]);
    wp_safe_redirect(home_url('/panel-firmy/?billing=credits-added'));
    exit;
}
add_action('admin_post_pt24_company_buy_lead_pack', 'pt24_handle_company_buy_lead_pack');

/**
 * Calculate dynamic lead price for a service slug.
 *
 * @param string $service_slug Service slug.
 * @param string $quality      Lead quality: standard|high|exclusive.
 * @return array{service_slug:string,min:int,max:int,suggested:int,currency:string,quality:string}
 */
function pt24_calculate_lead_price($service_slug, $quality = 'standard') {
    $service_slug = sanitize_title((string) $service_slug);
    $quality = sanitize_key((string) $quality);

    $matrix = pt24_get_lead_pricing_matrix();
    $range = isset($matrix[$service_slug]) ? $matrix[$service_slug] : ['min' => 20, 'max' => 40];

    $multiplier = 1.0;
    if ($quality === 'high') {
        $multiplier = 1.2;
    } elseif ($quality === 'exclusive') {
        $multiplier = 1.5;
    }

    $min = (int) round(((int) $range['min']) * $multiplier);
    $max = (int) round(((int) $range['max']) * $multiplier);
    $suggested = (int) round(($min + $max) / 2);

    return [
        'service_slug' => $service_slug,
        'min' => $min,
        'max' => $max,
        'suggested' => $suggested,
        'currency' => 'PLN',
        'quality' => $quality,
    ];
}

/**
 * REST: return monetization config and dynamic lead pricing.
 */
function pt24_register_monetization_rest_routes() {
    register_rest_route('pt24/v1', '/monetization', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => function (WP_REST_Request $request) {
            $service = sanitize_title((string) $request->get_param('service'));
            $quality = sanitize_key((string) $request->get_param('quality'));

            if ($quality === '') {
                $quality = 'standard';
            }

            return [
                'leadPricing' => pt24_get_lead_pricing_matrix(),
                'plans' => pt24_get_subscription_plans(),
                'premiumListings' => pt24_get_premium_listing_packages(),
                'leadCreditPackages' => pt24_get_lead_credit_packages(),
                'dynamicLeadPrice' => pt24_calculate_lead_price($service, $quality),
                'revenueMixTarget' => [
                    'leadEngine' => 40,
                    'saas' => 20,
                    'premiumProfiles' => 10,
                    'adsense' => 8,
                    'affiliate' => 7,
                    'aiPremium' => 7,
                    'marketingServices' => 5,
                    'apiWhiteLabel' => 3,
                ],
            ];
        },
    ]);
}
add_action('rest_api_init', 'pt24_register_monetization_rest_routes');

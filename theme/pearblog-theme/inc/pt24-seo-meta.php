<?php
/**
 * PT24 SEO Meta Tags
 *
 * Dynamic SEO meta tags for PT24.PRO pages
 *
 * @package PearBlog
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Whether the current install is the PT24.PRO site.
 *
 * The pearblog-theme is shared across several installs (pt24.pro, poradnik.pro,
 * mucharski.pl, …). The hardcoded PT24 branding below must only apply to the
 * PT24 site; otherwise other sites inherit the wrong title / og:site_name.
 */
function pt24_is_pt24_site() {
    $host = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );
    return ( false !== stripos( $host, 'pt24' ) );
}

/**
 * Generate SEO meta tags for PT24 pages
 */
function pt24_output_seo_meta() {
    // Only emit PT24 branding on the PT24 site.
    if ( ! pt24_is_pt24_site() ) {
        return;
    }

    // Get current page info
    $service = get_query_var('pt24_service', '');
    $city = get_query_var('pt24_city', '');

    $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
    $request_path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
    $request_query = (string) wp_parse_url($request_uri, PHP_URL_QUERY);
    $home_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);

    if ($home_path && '/' !== $home_path) {
        $normalized_home_path = untrailingslashit($home_path);
        if (0 === strpos($request_path, $normalized_home_path . '/')) {
            $request_path = substr($request_path, strlen($normalized_home_path));
        }
    }

    if ('' === $request_path) {
        $request_path = '/';
    }

    $canonical_url = home_url($request_path);
    if ('' !== $request_query) {
        $canonical_url .= '?' . $request_query;
    }

    // Default meta
    $meta = array(
        'title' => get_bloginfo('name') . ' - Znajdź sprawdzonego fachowca',
        'description' => 'Porównaj oferty sprawdzonych fachowców. Otrzymaj wycenę nawet w 15 minut. Bez dzwonienia, bez stresu.',
        'canonical' => $canonical_url,
        'og_type' => 'website',
        'og_locale' => 'pl_PL',
    );

    // Homepage
    if (is_front_page()) {
        $meta['title'] = 'PT24.PRO - Znajdź sprawdzonego fachowca w swojej okolicy';
        $meta['description'] = 'Porównaj oferty, sprawdź opinie i otrzymaj wycenę nawet w 15 minut. Mechanik, hydraulik, elektryk i inne usługi w Twoim mieście.';
    }

    // Service page
    if (!empty($service) && empty($city)) {
        $service_name = ucfirst($service);
        $meta['title'] = "$service_name - Ranking najlepszych fachowców | PT24.PRO";
        $meta['description'] = "Znajdź najlepszego {$service}a w swojej okolicy. Sprawdzone firmy, opinie klientów, szybki kontakt. Porównaj oferty za darmo.";
    }

    // City page
    if (empty($service) && !empty($city)) {
        $city_name = ucfirst($city);
        $meta['title'] = "$city_name - Fachowcy i usługi lokalne | PT24.PRO";
        $meta['description'] = "Sprawdzeni fachowcy w mieście $city_name. Hydraulicy, mechanicy, elektrycy i inne usługi. Porównaj oferty i ceny.";
    }

    // Service + City page
    if (!empty($service) && !empty($city)) {
        $service_name = ucfirst($service);
        $city_name = ucfirst($city);
        $meta['title'] = "$service_name $city_name - Ranking i opinie | PT24.PRO";
        $meta['description'] = "Najlepsi {$service}cy w mieście $city_name. Sprawdź ranking, przeczytaj opinie, porównaj ceny. Szybki kontakt z fachowcami.";
    }

    // Output meta tags
    ?>
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo esc_attr($meta['description']); ?>">
    <link rel="canonical" href="<?php echo esc_url($meta['canonical']); ?>">

    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo esc_attr($meta['title']); ?>">
    <meta property="og:description" content="<?php echo esc_attr($meta['description']); ?>">
    <meta property="og:url" content="<?php echo esc_url($meta['canonical']); ?>">
    <meta property="og:type" content="<?php echo esc_attr($meta['og_type']); ?>">
    <meta property="og:locale" content="<?php echo esc_attr($meta['og_locale']); ?>">
    <meta property="og:site_name" content="PT24.PRO">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo esc_attr($meta['title']); ?>">
    <meta name="twitter:description" content="<?php echo esc_attr($meta['description']); ?>">

    <!-- Schema.org -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "PT24.PRO",
        "description": "<?php echo esc_js($meta['description']); ?>",
        "url": "<?php echo esc_url(home_url('/')); ?>",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "<?php echo esc_url(home_url('/')); ?>?s={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>

    <?php if (!empty($service) && !empty($city)): ?>
    <!-- Local Business Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "ItemList",
        "name": "<?php echo esc_js($service_name); ?> w mieście <?php echo esc_js($city_name); ?>",
        "description": "Ranking najlepszych <?php echo esc_js($service); ?>ów w mieście <?php echo esc_js($city_name); ?>",
        "url": "<?php echo esc_url($meta['canonical']); ?>"
    }
    </script>
    <?php endif; ?>

    <!-- Breadcrumb Schema -->
    <?php if (!empty($service) || !empty($city)): ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "Home",
                "item": "<?php echo esc_url(home_url('/')); ?>"
            }
            <?php if (!empty($city)): ?>,
            {
                "@type": "ListItem",
                "position": 2,
                "name": "<?php echo esc_js(ucfirst($city)); ?>",
                "item": "<?php echo esc_url(home_url('/' . $city . '/')); ?>"
            }
            <?php endif; ?>
            <?php if (!empty($service)): ?>,
            {
                "@type": "ListItem",
                "position": <?php echo !empty($city) ? 3 : 2; ?>,
                "name": "<?php echo esc_js(ucfirst($service)); ?>",
                "item": "<?php echo esc_url($meta['canonical']); ?>"
            }
            <?php endif; ?>
        ]
    }
    </script>
    <?php endif; ?>
    <?php
}

// Hook into wp_head
add_action('wp_head', 'pt24_output_seo_meta', 1);

/**
 * Filter document title
 */
function pt24_document_title($title) {
    if (is_front_page()) {
        return 'PT24.PRO - Znajdź sprawdzonego fachowca w swojej okolicy';
    }

    $service = get_query_var('pt24_service', '');
    $city = get_query_var('pt24_city', '');

    if (!empty($service) && !empty($city)) {
        $service_name = ucfirst($service);
        $city_name = ucfirst($city);
        return "$service_name $city_name - Ranking i opinie | PT24.PRO";
    }

    if (!empty($service)) {
        $service_name = ucfirst($service);
        return "$service_name - Ranking najlepszych fachowców | PT24.PRO";
    }

    if (!empty($city)) {
        $city_name = ucfirst($city);
        return "$city_name - Fachowcy i usługi lokalne | PT24.PRO";
    }

    return $title;
}
add_filter('document_title_parts', function($parts) {
    // Leave the document title untouched on non-PT24 installs.
    if ( ! pt24_is_pt24_site() ) {
        return $parts;
    }
    $new_title = pt24_document_title(implode(' ', $parts));
    return array('title' => $new_title);
});

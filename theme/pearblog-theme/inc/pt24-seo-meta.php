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
    // The PT24 install lives at home_url() = .../pt24 (marker in the path), so
    // match the full home_url string rather than only the host.
    $url = (string) home_url( '/' );
    return ( false !== stripos( $url, 'pt24' ) );
}

/**
 * Build an absolute URL on the PUBLIC PT24 domain (pt24.pro).
 *
 * WordPress home_url() returns the origin host (wordpress2614653.home.pl/pt24)
 * because the site is served through Cloudflare. Canonical, og:url and schema
 * URLs must use the public domain so search engines index pt24.pro, not the
 * origin. Always emits a clean, query-string-free URL.
 *
 * @param string $path Request path (with or without leading slash).
 * @return string Absolute URL like https://pt24.pro/warszawa/hydraulik/.
 */
function pt24_public_home_url( $path = '/' ) {
    $path = '/' . ltrim( (string) $path, '/' );
    return 'https://pt24.pro' . ( '/' === $path ? '/' : $path );
}

/**
 * Resolve the current service / city slugs.
 *
 * On landing pages the query vars (pt24_service / pt24_city) are present in the
 * main query, but PearBlog_PT24_Landing_CPT::load_template() swaps $wp_query for
 * a fresh WP_Query when rendering, which drops those vars. In that case fall
 * back to the post meta of the current landing post.
 *
 * @return array{0:string,1:string} [service, city]
 */
function pt24_current_service_city() {
    $service = (string) get_query_var( 'pt24_service', '' );
    $city    = (string) get_query_var( 'pt24_city', '' );

    if ( '' !== $service && '' !== $city ) {
        return array( $service, $city );
    }

    $post = get_post();
    if ( $post instanceof WP_Post && 'pt24_landing' === $post->post_type ) {
        if ( '' === $service ) {
            $service = (string) get_post_meta( $post->ID, 'pt24_service', true );
        }
        if ( '' === $city ) {
            $city = (string) get_post_meta( $post->ID, 'pt24_city', true );
        }
    }

    // Final fallback: derive from the request URI path (/{city}/{service}/).
    if ( '' === $service || '' === $city ) {
        $request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
        $request_path = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
        $home_path    = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
        if ( $home_path && '/' !== $home_path ) {
            $home_path = untrailingslashit( $home_path );
            if ( 0 === strpos( $request_path, $home_path . '/' ) ) {
                $request_path = substr( $request_path, strlen( $home_path ) );
            }
        }
        $segments = array_values( array_filter( explode( '/', trim( $request_path, '/' ) ) ) );
        if ( 2 === count( $segments ) ) {
            if ( '' === $city ) {
                $city = sanitize_title( $segments[0] );
            }
            if ( '' === $service ) {
                $service = sanitize_title( $segments[1] );
            }
        } elseif ( 3 === count( $segments ) && 'ranking' === strtolower( $segments[0] ) ) {
            // /ranking/{city}/{service}/
            if ( '' === $city ) {
                $city = sanitize_title( $segments[1] );
            }
            if ( '' === $service ) {
                $service = sanitize_title( $segments[2] );
            }
        }
    }

    return array( $service, $city );
}

/**
 * Whether the current request is a ranking page (/ranking/{city}/{service}/).
 *
 * @return bool
 */
function pt24_is_ranking_page() {
    $uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    $path = (string) wp_parse_url( $uri, PHP_URL_PATH );
    $home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
    if ( $home && '/' !== $home ) {
        $home = untrailingslashit( $home );
        if ( 0 === strpos( $path, $home . '/' ) ) {
            $path = substr( $path, strlen( $home ) );
        }
    }
    $segs = array_values( array_filter( explode( '/', trim( $path, '/' ) ) ) );
    return ( 3 === count( $segs ) && 'ranking' === strtolower( $segs[0] ) );
}

/**
 * Resolve human-readable display names (with Polish diacritics) for a
 * service / city slug, using the canonical maps from the landing CPT.
 *
 * @param string $service Service slug.
 * @param string $city    City slug.
 * @return array{0:string,1:string} [service_name, city_name]
 */
function pt24_display_names( $service, $city ) {
    $service_name = '' !== $service ? ucfirst( str_replace( '-', ' ', $service ) ) : '';
    $city_name    = '' !== $city ? ucfirst( str_replace( '-', ' ', $city ) ) : '';

    if ( class_exists( 'PearBlog_PT24_Landing_CPT' ) ) {
        $services = PearBlog_PT24_Landing_CPT::get_services();
        $cities   = PearBlog_PT24_Landing_CPT::get_cities();
        if ( '' !== $service && isset( $services[ $service ] ) ) {
            $service_name = $services[ $service ];
        }
        if ( '' !== $city && isset( $cities[ $city ] ) ) {
            $city_name = $cities[ $city ];
        }
    }

    return array( $service_name, $city_name );
}

/**
 * Generate SEO meta tags for PT24 pages
 */
function pt24_output_seo_meta() {
    // Only emit PT24 branding on the PT24 site.
    if ( ! pt24_is_pt24_site() ) {
        return;
    }

    // This function is the single canonical/OG source on PT24, so drop WordPress
    // core's rel_canonical (which would emit the origin-host URL) to prevent
    // duplicate, conflicting canonical tags.
    remove_action( 'wp_head', 'rel_canonical' );

    // Replace WordPress's own <title> tag with our PT24-branded one so the
    // document title is always correct regardless of $wp_query state.
    remove_action( 'wp_head', '_wp_render_title_tag', 1 );

    // Suppress Yoast SEO head output on landing/ranking pages — it generates
    // a second (wrong) <title> and canonical that point to /blog/. Our own
    // tags are authoritative here.
    if ( defined( 'WPSEO_VERSION' ) ) {
        add_filter( 'wpseo_head',             '__return_false', 99 );
        add_filter( 'wpseo_canonical',        '__return_false', 99 );
        add_filter( 'wpseo_title',            '__return_empty_string', 99 );
        add_filter( 'wpseo_opengraph_title',  '__return_false', 99 );
        add_filter( 'wpseo_opengraph_url',    '__return_false', 99 );
    }

    // Get current page info
    list( $service, $city ) = pt24_current_service_city();

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

    // Canonical must target the PUBLIC domain (pt24.pro) and omit volatile query
    // strings (utm_*, cb, fbclid, …) so every variant canonicalizes to one URL.
    $canonical_url = pt24_public_home_url($request_path);
    unset( $request_query );

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
        $meta['title'] = 'PT24.PRO - Znajdź sprawdzonego fachowca w kilka minut | Portal firm i leadów';
        $meta['description'] = 'Wyślij jedno zapytanie i otrzymaj wiele ofert od zweryfikowanych specjalistów w Twojej okolicy. Mechanik, elektryk, hydraulik i wiele więcej. Bezpłatnie i bez zobowiązań.';
    }

    // Resolve display names (with Polish diacritics) for service / city.
    list( $service_name, $city_name ) = pt24_display_names( $service, $city );

    // Is this a ranking page (/ranking/{city}/{service}/)?
    $is_ranking = pt24_is_ranking_page();
    $year       = gmdate( 'Y' );

    // Branded PT24 social share image (1200x630 PNG) served from the public
    // domain so social platforms (which ignore SVG) render a proper card.
    $pt24_og_image = pt24_public_home_url( '/wp-content/themes/pearblog-theme/assets/brand/pt24-og.png' );

    // Service page
    if (!empty($service) && empty($city)) {
        $meta['title'] = "$service_name - Ranking najlepszych fachowców | PT24.PRO";
        $meta['description'] = "Znajdź najlepszego specjalistę ($service_name) w swojej okolicy. Sprawdzone firmy, opinie klientów, szybki kontakt. Porównaj oferty za darmo.";
    }

    // City page
    if (empty($service) && !empty($city)) {
        $meta['title'] = "$city_name - Fachowcy i usługi lokalne | PT24.PRO";
        $meta['description'] = "Sprawdzeni fachowcy w mieście $city_name. Hydraulicy, mechanicy, elektrycy i inne usługi. Porównaj oferty i ceny.";
    }

    // Service + City page (landing or ranking)
    if (!empty($service) && !empty($city)) {
        if ( $is_ranking ) {
            $meta['title']       = "Ranking {$service_name} {$city_name} {$year} — najlepsze firmy | PT24.PRO";
            $meta['description'] = "Ranking najlepszych firm: {$service_name} {$city_name} {$year}. Porównaj oceny i liczbę zrealizowanych zleceń. Zamów bezpłatną wycenę.";
            $meta['canonical']   = pt24_public_home_url( "/ranking/{$city}/{$service}/" );
        } else {
            $meta['title']       = "{$service_name} {$city_name} — ceny i oferty | PT24.PRO";
            $meta['description'] = "{$service_name} {$city_name} — sprawdź ceny, opinie i dostępne firmy. Otrzymaj dopasowane oferty bez dzwonienia.";
        }
    }

    // /szukaj/ — finder / search page
    if ( rtrim( $request_path, '/' ) === '/szukaj' ) {
        $meta['title']       = 'Znajdź sprawdzonego fachowca w swoim mieście | PT24.PRO';
        $meta['description'] = 'Szukasz hydraulika, elektryka, mechanika lub innego fachowca? Wybierz usługę i miasto — PT24 połączy Cię z lokalnym specjalistą. Bezpłatna wycena.';
    }

    // AI Blog article — override from stored meta
    $current_post = get_post();
    if ( $current_post instanceof WP_Post && is_singular( 'post' ) && '1' === (string) get_post_meta( $current_post->ID, '_pt24_blog_ai', true ) ) {
        $ai_title = (string) get_post_meta( $current_post->ID, 'pt24_meta_title', true );
        $ai_desc  = (string) get_post_meta( $current_post->ID, 'pt24_meta_description', true );
        if ( '' !== $ai_title ) {
            $meta['title'] = $ai_title . ' | PT24.PRO';
        } else {
            $meta['title'] = get_the_title( $current_post ) . ' | PT24.PRO';
        }
        if ( '' !== $ai_desc ) {
            $meta['description'] = $ai_desc;
        }
        $meta['og_type'] = 'article';
        $meta['canonical'] = pt24_public_home_url( wp_make_link_relative( get_permalink( $current_post ) ) );
    }

    // Firm profile — override from stored meta
    if ( $current_post instanceof WP_Post && 'pt24_firm' === $current_post->post_type ) {
        $firm_title    = (string) get_post_meta( $current_post->ID, 'pt24_meta_title', true );
        $firm_desc     = (string) get_post_meta( $current_post->ID, 'pt24_meta_description', true );
        $firm_city     = (string) get_post_meta( $current_post->ID, 'pt24_firm_city_name', true );
        $firm_rating   = (string) get_post_meta( $current_post->ID, 'pt24_firm_rating', true );
        $firm_svc      = (string) get_post_meta( $current_post->ID, 'pt24_firm_services', true );
        $firm_svc_name = class_exists( 'PT24_Scale_Data' )
            ? PT24_Scale_Data::service_name( $firm_svc )
            : ucfirst( str_replace( '-', ' ', $firm_svc ) );

        if ( '' !== $firm_title ) {
            $meta['title'] = $firm_title . ' | PT24.PRO';
        } else {
            $meta['title'] = get_the_title( $current_post )
                . ( $firm_city ? " — {$firm_svc_name} {$firm_city}" : '' )
                . ' | PT24.PRO';
        }
        if ( '' !== $firm_desc ) {
            $meta['description'] = $firm_desc;
        } else {
            $meta['description'] = get_the_title( $current_post )
                . ( $firm_city ? " świadczy usługi z zakresu {$firm_svc_name} w {$firm_city}." : '.' )
                . ( $firm_rating ? " Ocena: {$firm_rating}/5." : '' )
                . ' Zamów bezpłatną wycenę na PT24.PRO.';
        }
        $meta['og_type'] = 'business.business';
        $meta['canonical'] = pt24_public_home_url( wp_make_link_relative( get_permalink( $current_post ) ) );
    }

    // Output meta tags
    ?>
    <title><?php echo esc_html( $meta['title'] ); ?></title>
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
    <meta property="og:image" content="<?php echo esc_url( $pt24_og_image ); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:alt" content="<?php echo esc_attr( $meta['title'] ); ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo esc_attr($meta['title']); ?>">
    <meta name="twitter:description" content="<?php echo esc_attr($meta['description']); ?>">
    <meta name="twitter:image" content="<?php echo esc_url( $pt24_og_image ); ?>">

    <!-- Schema.org -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "PT24.PRO",
        "description": "<?php echo esc_js($meta['description']); ?>",
        "url": "<?php echo esc_url(pt24_public_home_url('/')); ?>",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "<?php echo esc_url(pt24_public_home_url('/')); ?>?s={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>

    <?php if ( is_front_page() ) :
        $pt24_home_faq = array(
            array( 'Czy korzystanie z PT24 jest płatne?', 'Nie. Dla osób zlecających serwis jest w 100% bezpłatny i niezobowiązujący.' ),
            array( 'Jak szybko otrzymam oferty?', 'Najczęściej w kilka godzin, a najpóźniej do 24 godzin od wysłania zgłoszenia.' ),
            array( 'Czy muszę przyjąć którąś z ofert?', 'Nie. Decyzję podejmujesz samodzielnie — żadne zgłoszenie nie jest zobowiązaniem.' ),
            array( 'W jakich miastach działacie?', 'Obsługujemy największe miasta w Polsce, a lista jest stale poszerzana o kolejne lokalizacje.' ),
            array( 'Jak dołączyć jako fachowiec?', 'Wejdź na stronę „Dla firm” i wypełnij zgłoszenie — pomożemy uruchomić Twój profil.' ),
        );
        $pt24_home_faq_schema = array(
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => array(),
        );
        foreach ( $pt24_home_faq as $pt24_qa ) {
            $pt24_home_faq_schema['mainEntity'][] = array(
                '@type'          => 'Question',
                'name'           => $pt24_qa[0],
                'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $pt24_qa[1] ),
            );
        }
        ?>
    <script type="application/ld+json"><?php echo wp_json_encode( $pt24_home_faq_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
    <?php endif; ?>

    <?php if (!empty($service) && !empty($city)): ?>
    <!-- ItemList Schema (ranking / landing) -->
    <script type="application/ld+json">
    <?php
    if ( $is_ranking ) {
        // Full ItemList with ranked firms.
        $ranking_firms = get_posts( [
            'post_type'        => 'pt24_firm',
            'post_status'      => 'publish',
            'numberposts'      => 10,
            'meta_key'         => 'pt24_firm_city',
            'meta_value'       => $city,
            'suppress_filters' => true,
        ] );
        usort( $ranking_firms, function ( $a, $b ) {
            $ra = (float) str_replace( ',', '.', (string) get_post_meta( $a->ID, 'pt24_firm_rating', true ) );
            $rb = (float) str_replace( ',', '.', (string) get_post_meta( $b->ID, 'pt24_firm_rating', true ) );
            if ( $ra === $rb ) {
                return (int) get_post_meta( $b->ID, 'pt24_firm_jobs', true ) <=> (int) get_post_meta( $a->ID, 'pt24_firm_jobs', true );
            }
            return $rb <=> $ra;
        } );
        $list_items = [];
        foreach ( $ranking_firms as $pos => $rfirm ) {
            $r_rating = (string) get_post_meta( $rfirm->ID, 'pt24_firm_rating', true );
            $r_jobs   = (int)   get_post_meta( $rfirm->ID, 'pt24_firm_jobs',   true );
            $item = [
                '@type'    => 'ListItem',
                'position' => $pos + 1,
                'item'     => [
                    '@type'       => 'LocalBusiness',
                    'name'        => get_the_title( $rfirm ),
                    'url'         => pt24_public_home_url( '/firma/' . $rfirm->post_name . '/' ),
                    'areaServed'  => $city_name,
                    'makesOffer'  => [ '@type' => 'Offer', 'itemOffered' => [ '@type' => 'Service', 'name' => $service_name ] ],
                ],
            ];
            if ( $r_rating ) {
                $item['item']['aggregateRating'] = [
                    '@type'       => 'AggregateRating',
                    'ratingValue' => str_replace( ',', '.', $r_rating ),
                    'bestRating'  => '5',
                    'worstRating' => '1',
                    'reviewCount' => max( 1, $r_jobs ),
                ];
            }
            $list_items[] = $item;
        }
        $schema = [
            '@context'        => 'https://schema.org',
            '@type'           => 'ItemList',
            'name'            => "Ranking {$service_name} {$city_name} {$year}",
            'description'     => "Najwyżej oceniane firmy: {$service_name} w mieście {$city_name}",
            'url'             => pt24_public_home_url( "/ranking/{$city}/{$service}/" ),
            'numberOfItems'   => count( $list_items ),
            'itemListElement' => $list_items,
        ];
        echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
    } else {
        // Minimal ItemList for standard landing.
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'ItemList',
            'name'        => "{$service_name} w mieście {$city_name}",
            'description' => "Ranking najlepszych {$service_name} w mieście {$city_name}",
            'url'         => esc_url( $meta['canonical'] ),
        ];
        echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    }
    ?>
    </script>
    <?php endif; ?>

    // Breadcrumbs — adjust position for ranking pages.
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
                "item": "<?php echo esc_url(pt24_public_home_url('/')); ?>"
            }
            <?php if ( $is_ranking ): ?>,
            {
                "@type": "ListItem",
                "position": 2,
                "name": "Rankingi",
                "item": "<?php echo esc_url(pt24_public_home_url('/rankingi/')); ?>"
            },
            {
                "@type": "ListItem",
                "position": 3,
                "name": "<?php echo esc_js($service_name . ' ' . $city_name); ?>",
                "item": "<?php echo esc_url(pt24_public_home_url('/ranking/' . $city . '/' . $service . '/')); ?>"
            }
            <?php else: ?>
            <?php if (!empty($city)): ?>,
            {
                "@type": "ListItem",
                "position": 2,
                "name": "<?php echo esc_js(ucfirst($city)); ?>",
                "item": "<?php echo esc_url(pt24_public_home_url('/' . $city . '/')); ?>"
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
            <?php endif; ?>
        ]
    }
    </script>
    <?php endif; ?>

    <?php if ( $is_ranking && ! empty( $service ) && ! empty( $city ) ) :
        // Human-readable locative form of the city name (Polish grammar: "w Warszawie").
        $city_loc_map = [
            'warszawa'  => 'Warszawie',
            'krakow'    => 'Krakowie',
            'wroclaw'   => 'Wrocławiu',
            'poznan'    => 'Poznaniu',
            'gdansk'    => 'Gdańsku',
            'katowice'  => 'Katowicach',
        ];
        $city_loc = isset( $city_loc_map[ $city ] )
            ? $city_loc_map[ $city ]
            : 'mieście ' . $city_name;

        // Build service-specific FAQ questions for ranking pages.
        $svc_lc = mb_strtolower( $service_name );
        $ranking_faq = [
            [
                "Ile kosztuje {$svc_lc} w {$city_loc}?",
                "Ceny zależą od zakresu zlecenia i konkretnej firmy. Aby porównać oferty i dowiedzieć się, ile zapłacisz za {$svc_lc} w {$city_loc}, złóż bezpłatne zapytanie przez PT24 — odpowiedź najczęściej w ciągu 24 h.",
            ],
            [
                "Jak wybrać najlepszą firmę ({$svc_lc}) w {$city_loc}?",
                "Sprawdź oceny klientów, liczbę zrealizowanych zleceń i zakres usług. Nasz ranking {$svc_lc} w {$city_loc} zestawia firmy od najwyżej ocenianych — możesz też zamówić bezpłatną wycenę od kilku jednocześnie.",
            ],
            [
                "Jak szybko mogę umówić {$svc_lc} w {$city_loc}?",
                "Większość firm działa lokalnie i może przyjąć zlecenie tego samego lub następnego dnia. Wyślij zapytanie przez PT24 — specjaliści z {$city_name} skontaktują się sami.",
            ],
            [
                "Czy PT24 działa w {$city_loc}?",
                "Tak. PT24.PRO łączy klientów z fachowcami w {$city_loc} i innych miastach Polski. Usługa dla zamawiających jest w 100% bezpłatna.",
            ],
            [
                "Skąd wiem, że firmy w rankingu są sprawdzone?",
                "Ranking {$svc_lc} w {$city_loc} opiera się na opiniach klientów i liczbie zrealizowanych zleceń. Przed zamówieniem możesz przejrzeć profile firm i ich historię realizacji.",
            ],
        ];
        $ranking_faq_schema = [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => [],
        ];
        foreach ( $ranking_faq as $qa ) {
            $ranking_faq_schema['mainEntity'][] = [
                '@type'          => 'Question',
                'name'           => $qa[0],
                'acceptedAnswer' => [ '@type' => 'Answer', 'text' => $qa[1] ],
            ];
        }
    ?>
    <!-- FAQ Schema for ranking page -->
    <script type="application/ld+json"><?php echo wp_json_encode( $ranking_faq_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
    <?php endif; ?>
    <?php
}

// Hook into wp_head
add_action('wp_head', 'pt24_output_seo_meta', 1);

/**
 * Short-circuit WordPress document title for landing/ranking pages.
 *
 * The CPT load_template swaps $wp_query, which can leave is_home() truthy
 * and cause WordPress to produce "<blog-page-title> - PT24.PRO". Using
 * pre_get_document_title lets us intercept before that logic runs.
 */
add_filter( 'pre_get_document_title', function ( $title ) {
    if ( ! pt24_is_pt24_site() ) {
        return $title;
    }
    list( $service, $city ) = pt24_current_service_city();
    if ( '' === $service || '' === $city ) {
        return $title;
    }
    list( $service_name, $city_name ) = pt24_display_names( $service, $city );
    if ( pt24_is_ranking_page() ) {
        $year = gmdate( 'Y' );
        return "Ranking {$service_name} {$city_name} {$year} \u2014 najlepsze firmy | PT24.PRO";
    }
    return "{$service_name} {$city_name} \u2014 ceny i oferty | PT24.PRO";
}, 5 );

/**
 * Filter document title
 */
function pt24_document_title($title) {
    if (is_front_page()) {
        return 'PT24.PRO - Znajdź sprawdzonego fachowca w swojej okolicy';
    }

    $post = get_post();

    // AI blog article — use stored meta title
    if ( $post instanceof WP_Post && is_singular( 'post' ) && '1' === (string) get_post_meta( $post->ID, '_pt24_blog_ai', true ) ) {
        $meta_title = (string) get_post_meta( $post->ID, 'pt24_meta_title', true );
        if ( '' !== $meta_title ) {
            return $meta_title . ' | PT24.PRO';
        }
        return get_the_title( $post ) . ' | PT24.PRO';
    }

    // Firm profile — use stored meta title
    if ( $post instanceof WP_Post && 'pt24_firm' === $post->post_type ) {
        $meta_title = (string) get_post_meta( $post->ID, 'pt24_meta_title', true );
        if ( '' !== $meta_title ) {
            return $meta_title . ' | PT24.PRO';
        }
        $city_name = (string) get_post_meta( $post->ID, 'pt24_firm_city_name', true );
        return get_the_title( $post ) . ( $city_name ? ' — ' . $city_name : '' ) . ' | PT24.PRO';
    }

    list( $service, $city ) = pt24_current_service_city();

    if ( $post instanceof WP_Post && 'pt24_landing' === $post->post_type ) {
        $meta_title = (string) get_post_meta( $post->ID, 'pt24_meta_title', true );
        if ( '' !== $meta_title ) {
            return $meta_title . ' | PT24.PRO';
        }
    }

    list( $service_name, $city_name ) = pt24_display_names( $service, $city );

    if (!empty($service) && !empty($city)) {
        if ( function_exists( 'pt24_is_ranking_page' ) && pt24_is_ranking_page() ) {
            $year = gmdate( 'Y' );
            return "Ranking {$service_name} {$city_name} {$year} — najlepsze firmy | PT24.PRO";
        }
        return "$service_name $city_name — ceny i oferty | PT24.PRO";
    }

    if (!empty($service)) {
        return "$service_name - Ranking najlepszych fachowców | PT24.PRO";
    }

    if (!empty($city)) {
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

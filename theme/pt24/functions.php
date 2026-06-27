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
}
add_action('init', 'pt24_register_panel_routes');

/**
 * Add query var for custom panel routing.
 */
function pt24_add_panel_query_var($vars) {
    $vars[] = 'pt24_panel';
    $vars[] = 'pt24_service_hub';
    return $vars;
}
add_filter('query_vars', 'pt24_add_panel_query_var');

/**
 * Route custom panel slugs to dedicated templates.
 */
function pt24_panel_template_include($template) {
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
add_filter('template_include', 'pt24_panel_template_include');

/**
 * Flush rewrite rules after route changes.
 */
function pt24_maybe_flush_panel_rewrites() {
    $version = 'panel-routes-v2';
    if (get_option('pt24_panel_routes_version') !== $version) {
        pt24_register_panel_routes();
        flush_rewrite_rules(false);
        update_option('pt24_panel_routes_version', $version, false);
    }
}
add_action('admin_init', 'pt24_maybe_flush_panel_rewrites');

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

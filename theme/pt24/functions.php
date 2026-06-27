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

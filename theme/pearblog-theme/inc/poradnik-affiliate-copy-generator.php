<?php
/**
 * Poradnik.pro Affiliate Copy Generator
 *
 * AI-powered affiliate content generation with templates, optimization,
 * and conversion-focused copywriting for maximum affiliate revenue.
 *
 * @package PearBlog
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Affiliate Copy Generator Class
 */
class PoradnikAffiliateCopyGenerator {
    /**
     * Copy templates for different affiliate types
     */
    private const TEMPLATES = [
        'booking' => [
            'emotional' => [
                'headline' => 'Znajdź wymarzony nocleg w {location}',
                'intro' => 'Wyobraź sobie idealny pobyt w {location}. Komfortowy nocleg, świetna lokalizacja, atrakcyjna cena.',
                'cta' => 'Sprawdź najlepsze oferty →',
                'benefits' => ['Darmowa anulacja', 'Płatność przy wymeldowaniu', 'Najlepsze ceny gwarantowane'],
            ],
            'urgency' => [
                'headline' => 'Szybko! Sprawdź dostępność noclegów w {location}',
                'intro' => 'Popularne noclegi w {location} szybko się wyprzedają. Zabezpiecz swój pobyt już teraz!',
                'cta' => 'Zobacz dostępne terminy →',
                'benefits' => ['Rezerwuj bez opłat', 'Darmowa anulacja do 24h przed przyjazdem', 'Ponad 10,000+ obiektów'],
            ],
            'value' => [
                'headline' => 'Oszczędź na noclegu w {location}',
                'intro' => 'Porównaj ceny noclegów w {location} i znajdź najlepsze oferty. Gwarancja najniższej ceny!',
                'cta' => 'Porównaj ceny teraz →',
                'benefits' => ['Najniższe ceny w sieci', 'Rabaty do 50%', 'Nie ma ukrytych opłat'],
            ],
        ],
        'airbnb' => [
            'unique' => [
                'headline' => 'Odkryj wyjątkowe miejsca w {location}',
                'intro' => 'Zapomnij o standardowych hotelach. Znajdź unikalny nocleg, który sprawi, że Twój pobyt w {location} będzie niezapomniany.',
                'cta' => 'Przeglądaj unikalne noclegi →',
                'benefits' => ['Autentyczne doświadczenia', 'Mieszkaj jak lokalsi', 'Wyposażone kuchnie'],
            ],
            'experience' => [
                'headline' => 'Poczuj się jak w domu w {location}',
                'intro' => 'Wybierz spośród tysięcy przytulnych apartamentów, domów i pokoi w {location}. Idealne dla rodzin i grup.',
                'cta' => 'Znajdź swój dom z dala od domu →',
                'benefits' => ['Całe mieszkanie tylko dla Ciebie', 'Wyposażenie domowe', 'Oszczędź na jedzeniu'],
            ],
        ],
        'saas' => [
            'problem_solution' => [
                'headline' => '{product_name} - Rozwiązanie Twojego problemu',
                'intro' => 'Masz dość {pain_point}? {product_name} pomoże Ci {solution} w ciągu {timeframe}.',
                'cta' => 'Wypróbuj {product_name} za darmo →',
                'benefits' => ['Darmowy okres próbny', 'Bez karty kredytowej', 'Anuluj w każdej chwili'],
            ],
            'social_proof' => [
                'headline' => 'Dołącz do {user_count} użytkowników {product_name}',
                'intro' => '{product_name} to zaufane narzędzie dla {target_audience}. Zobacz dlaczego tysiące osób wybiera właśnie nas.',
                'cta' => 'Rozpocznij za darmo →',
                'benefits' => ['Zaufane przez liderów branży', 'Ocena {rating}/5', '{testimonial_count} pozytywnych opinii'],
            ],
        ],
    ];

    /**
     * Power words for better conversion
     */
    private const POWER_WORDS = [
        'urgent' => ['Teraz', 'Dzisiaj', 'Natychmiast', 'Szybko', 'Ostatnia szansa', 'Ograniczona oferta'],
        'value' => ['Darmowy', 'Oszczędź', 'Rabat', 'Bonus', 'Gwarancja', 'Bez ryzyka'],
        'emotional' => ['Wymarzony', 'Niesamowity', 'Wyjątkowy', 'Magiczny', 'Niezapomniany', 'Idealny'],
        'exclusive' => ['Ekskluzywny', 'VIP', 'Premium', 'Luksusowy', 'Specjalny', 'Tylko dla Ciebie'],
    ];

    /**
     * Initialize hooks
     */
    public static function init() {
        // Admin interface
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);

        // AJAX handlers
        add_action('wp_ajax_acg_generate_copy', [__CLASS__, 'ajax_generate_copy']);
        add_action('wp_ajax_acg_save_template', [__CLASS__, 'ajax_save_template']);
        add_action('wp_ajax_acg_optimize_copy', [__CLASS__, 'ajax_optimize_copy']);

        // Shortcodes
        add_shortcode('affiliate_box', [__CLASS__, 'shortcode_affiliate_box']);
        add_shortcode('affiliate_cta', [__CLASS__, 'shortcode_affiliate_cta']);
    }

    /**
     * Add admin menu
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'poradnik-landing-leads',
            'Affiliate Copy Generator',
            'Affiliate Copy Gen',
            'manage_options',
            'poradnik-affiliate-copy-generator',
            [__CLASS__, 'render_admin_page']
        );
    }

    /**
     * Enqueue admin assets
     */
    public static function enqueue_admin_assets($hook) {
        if ('landing-leads_page_poradnik-affiliate-copy-generator' !== $hook) {
            return;
        }

        wp_enqueue_script('acg-admin', get_template_directory_uri() . '/assets/js/affiliate-copy-generator.js', ['jquery'], '1.0.0', true);

        wp_localize_script('acg-admin', 'acgData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('acg_nonce'),
        ]);
    }

    /**
     * Generate affiliate copy
     */
    public static function generate_copy($params) {
        $type = $params['type'] ?? 'booking'; // booking, airbnb, saas
        $style = $params['style'] ?? 'emotional'; // emotional, urgency, value, unique, etc.
        $location = $params['location'] ?? '';
        $product_name = $params['product_name'] ?? '';

        // Get template
        $template = self::TEMPLATES[$type][$style] ?? self::TEMPLATES['booking']['emotional'];

        // Replace variables
        $headline = self::replace_variables($template['headline'], [
            'location' => $location,
            'product_name' => $product_name,
        ]);

        $intro = self::replace_variables($template['intro'], [
            'location' => $location,
            'product_name' => $product_name,
            'pain_point' => $params['pain_point'] ?? 'problemu',
            'solution' => $params['solution'] ?? 'osiągnąć cel',
            'timeframe' => $params['timeframe'] ?? '30 dni',
            'user_count' => $params['user_count'] ?? '10,000+',
            'target_audience' => $params['target_audience'] ?? 'profesjonalistów',
            'rating' => $params['rating'] ?? '4.8',
            'testimonial_count' => $params['testimonial_count'] ?? '1,000+',
        ]);

        $cta = self::replace_variables($template['cta'], [
            'product_name' => $product_name,
        ]);

        // Add power words if requested
        if (!empty($params['power_words'])) {
            $headline = self::add_power_words($headline, $params['power_word_category'] ?? 'emotional');
        }

        // Build final copy
        $copy = [
            'headline' => $headline,
            'intro' => $intro,
            'cta' => $cta,
            'benefits' => $template['benefits'],
            'full_html' => self::build_html($headline, $intro, $cta, $template['benefits'], $params),
        ];

        return $copy;
    }

    /**
     * Replace variables in text
     */
    private static function replace_variables($text, $variables) {
        foreach ($variables as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }

        return $text;
    }

    /**
     * Add power words to text
     */
    private static function add_power_words($text, $category = 'emotional') {
        $words = self::POWER_WORDS[$category] ?? self::POWER_WORDS['emotional'];
        $random_word = $words[array_rand($words)];

        // Add at the beginning if text doesn't start with power word
        $first_word = explode(' ', $text)[0];
        if (!in_array($first_word, $words)) {
            return $random_word . '! ' . $text;
        }

        return $text;
    }

    /**
     * Build HTML for affiliate box
     */
    private static function build_html($headline, $intro, $cta, $benefits, $params) {
        $url = $params['url'] ?? '#';
        $affiliate_id = $params['affiliate_id'] ?? '';
        $image = $params['image'] ?? '';

        // Build URL with affiliate parameters
        if (!empty($affiliate_id)) {
            $url = add_query_arg([
                'aid' => $affiliate_id,
                'ref' => 'poradnik-pro',
            ], $url);
        }

        ob_start();
        ?>
        <div class="acg-affiliate-box acg-affiliate-box--<?php echo esc_attr($params['type'] ?? 'booking'); ?>">
            <?php if (!empty($image)): ?>
            <div class="acg-affiliate-box__image">
                <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($headline); ?>">
            </div>
            <?php endif; ?>

            <div class="acg-affiliate-box__content">
                <h3 class="acg-affiliate-box__headline">
                    <?php echo esc_html($headline); ?>
                </h3>

                <p class="acg-affiliate-box__intro">
                    <?php echo esc_html($intro); ?>
                </p>

                <?php if (!empty($benefits)): ?>
                <ul class="acg-affiliate-box__benefits">
                    <?php foreach ($benefits as $benefit): ?>
                    <li>
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M6 12L2 8L3.4 6.6L6 9.2L12.6 2.6L14 4L6 12Z" fill="#00c853"/>
                        </svg>
                        <?php echo esc_html($benefit); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <a href="<?php echo esc_url($url); ?>"
                   class="acg-affiliate-box__cta"
                   target="_blank"
                   rel="noopener sponsored nofollow"
                   data-affiliate-type="<?php echo esc_attr($params['type'] ?? 'booking'); ?>">
                    <?php echo esc_html($cta); ?>
                </a>

                <p class="acg-affiliate-box__disclaimer">
                    <?php echo esc_html(get_option('acg_disclaimer', 'Link partnerski - możemy otrzymać prowizję')); ?>
                </p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Optimize existing copy for better conversion
     */
    public static function optimize_copy($text, $optimization_type = 'ctr') {
        $optimizations = [
            'ctr' => [
                'patterns' => [
                    '/Kliknij tutaj/i' => 'Zobacz oferty',
                    '/Sprawdź/i' => 'Odkryj',
                    '/Zobacz/i' => 'Porównaj',
                ],
            ],
            'urgency' => [
                'patterns' => [
                    '/dostępne/i' => 'dostępne tylko dzisiaj',
                    '/oferta/i' => 'ograniczona oferta',
                    '/cena/i' => 'specjalna cena',
                ],
            ],
            'value' => [
                'patterns' => [
                    '/darmow/i' => 'całkowicie darmowy',
                    '/bez/i' => 'bez zobowiązań',
                    '/rabat/i' => 'ekskluzywny rabat',
                ],
            ],
        ];

        $patterns = $optimizations[$optimization_type]['patterns'] ?? $optimizations['ctr']['patterns'];

        foreach ($patterns as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        return $text;
    }

    /**
     * Analyze copy performance
     */
    public static function analyze_copy($text) {
        $analysis = [
            'score' => 0,
            'readability' => 0,
            'power_words_count' => 0,
            'cta_quality' => 0,
            'length' => str_word_count($text),
            'suggestions' => [],
        ];

        // Check for power words
        $power_word_count = 0;
        foreach (self::POWER_WORDS as $category => $words) {
            foreach ($words as $word) {
                if (stripos($text, $word) !== false) {
                    $power_word_count++;
                }
            }
        }
        $analysis['power_words_count'] = $power_word_count;

        // Check length
        if ($analysis['length'] < 20) {
            $analysis['suggestions'][] = 'Tekst jest za krótki. Dodaj więcej szczegółów (minimum 20 słów).';
        } elseif ($analysis['length'] > 100) {
            $analysis['suggestions'][] = 'Tekst może być za długi. Rozważ skrócenie do 50-80 słów.';
        }

        // Check for CTA
        $cta_keywords = ['sprawdź', 'zobacz', 'kliknij', 'odkryj', 'znajdź', 'zarezerwuj', 'wypróbuj'];
        $has_cta = false;
        foreach ($cta_keywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                $has_cta = true;
                break;
            }
        }

        if (!$has_cta) {
            $analysis['suggestions'][] = 'Dodaj wyraźne wezwanie do działania (CTA).';
        } else {
            $analysis['cta_quality'] = 80;
        }

        // Calculate score
        $analysis['score'] = min(100, (
            ($power_word_count * 10) +
            ($has_cta ? 30 : 0) +
            (($analysis['length'] >= 20 && $analysis['length'] <= 80) ? 30 : 10)
        ));

        return $analysis;
    }

    /**
     * AJAX: Generate copy
     */
    public static function ajax_generate_copy() {
        check_ajax_referer('acg_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $params = [
            'type' => sanitize_text_field($_POST['type'] ?? 'booking'),
            'style' => sanitize_text_field($_POST['style'] ?? 'emotional'),
            'location' => sanitize_text_field($_POST['location'] ?? ''),
            'product_name' => sanitize_text_field($_POST['product_name'] ?? ''),
            'url' => esc_url_raw($_POST['url'] ?? ''),
            'affiliate_id' => sanitize_text_field($_POST['affiliate_id'] ?? ''),
            'image' => esc_url_raw($_POST['image'] ?? ''),
            'power_words' => isset($_POST['power_words']),
            'power_word_category' => sanitize_text_field($_POST['power_word_category'] ?? 'emotional'),
        ];

        $copy = self::generate_copy($params);
        $analysis = self::analyze_copy($copy['intro']);

        wp_send_json_success([
            'copy' => $copy,
            'analysis' => $analysis,
        ]);
    }

    /**
     * AJAX: Save custom template
     */
    public static function ajax_save_template() {
        check_ajax_referer('acg_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $template_name = sanitize_text_field($_POST['template_name'] ?? '');
        $template_data = [
            'headline' => sanitize_text_field($_POST['headline'] ?? ''),
            'intro' => sanitize_textarea_field($_POST['intro'] ?? ''),
            'cta' => sanitize_text_field($_POST['cta'] ?? ''),
            'benefits' => array_map('sanitize_text_field', $_POST['benefits'] ?? []),
        ];

        if (empty($template_name)) {
            wp_send_json_error(['message' => 'Template name is required']);
        }

        $custom_templates = get_option('acg_custom_templates', []);
        $custom_templates[$template_name] = $template_data;

        update_option('acg_custom_templates', $custom_templates);

        wp_send_json_success(['message' => 'Template saved successfully']);
    }

    /**
     * AJAX: Optimize copy
     */
    public static function ajax_optimize_copy() {
        check_ajax_referer('acg_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $text = sanitize_textarea_field($_POST['text'] ?? '');
        $optimization_type = sanitize_text_field($_POST['optimization_type'] ?? 'ctr');

        $optimized = self::optimize_copy($text, $optimization_type);
        $analysis = self::analyze_copy($optimized);

        wp_send_json_success([
            'optimized' => $optimized,
            'analysis' => $analysis,
        ]);
    }

    /**
     * Shortcode: Affiliate box
     */
    public static function shortcode_affiliate_box($atts) {
        $atts = shortcode_atts([
            'type' => 'booking',
            'style' => 'emotional',
            'location' => '',
            'url' => '',
            'affiliate_id' => '',
            'image' => '',
        ], $atts);

        $copy = self::generate_copy($atts);
        return $copy['full_html'];
    }

    /**
     * Shortcode: Affiliate CTA
     */
    public static function shortcode_affiliate_cta($atts, $content = '') {
        $atts = shortcode_atts([
            'url' => '#',
            'type' => 'primary',
        ], $atts);

        return sprintf(
            '<a href="%s" class="acg-cta acg-cta--%s" target="_blank" rel="noopener sponsored nofollow">%s</a>',
            esc_url($atts['url']),
            esc_attr($atts['type']),
            esc_html($content ?: 'Sprawdź ofertę →')
        );
    }

    /**
     * Render admin page
     */
    public static function render_admin_page() {
        $custom_templates = get_option('acg_custom_templates', []);
        require_once get_template_directory() . '/templates/admin/affiliate-copy-generator.php';
    }
}

// Initialize
PoradnikAffiliateCopyGenerator::init();

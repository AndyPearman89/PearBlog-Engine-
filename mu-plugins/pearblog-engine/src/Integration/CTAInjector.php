<?php
/**
 * CTA Injector
 *
 * Injects Call-to-Action components into PearBlog content for PT24 integration
 *
 * @package PearBlogEngine
 * @subpackage Integration
 */

namespace PearBlogEngine\Integration;

class CTAInjector {

    /**
     * @var array CTA configuration
     */
    private $config;

    /**
     * Constructor
     */
    public function __construct() {
        $this->config = [
            'enabled' => get_option('pearblog_pt24_cta_enabled', true),
            'inline_enabled' => get_option('pearblog_pt24_cta_inline', true),
            'sticky_enabled' => get_option('pearblog_pt24_cta_sticky', false),
            'exit_intent_enabled' => get_option('pearblog_pt24_cta_exit_intent', false)
        ];
    }

    /**
     * Get inline CTA box
     *
     * @param string $service Service name (e.g., "Mechanik samochodowy")
     * @param string $city City name (e.g., "Warszawa")
     * @param string $url Landing page URL
     * @return string HTML for inline CTA
     */
    public function get_inline_cta(string $service, string $city, string $url): string {
        if (!$this->config['inline_enabled']) {
            return '';
        }

        return sprintf(
            '<div class="pearblog-cta-inline" style="margin: 2rem 0; padding: 1.5rem; background: linear-gradient(135deg, #f3f4f6 0%%, #e5e7eb 100%); border-left: 4px solid #7c3aed; border-radius: 0.75rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <div class="cta-content">
                    <h3 style="margin: 0 0 0.75rem 0; color: #1f2937; font-size: 1.25rem; font-weight: 700;">
                        💡 Potrzebujesz %s w %s?
                    </h3>
                    <p style="margin: 0 0 1rem 0; color: #4b5563; line-height: 1.6;">
                        Sprawdź sprawdzonych specjalistów w Twojej okolicy. Szybki kontakt, uczciwe ceny, lokalna obsługa.
                    </p>
                    <a href="%s" class="cta-button" style="display: inline-block; padding: 0.75rem 1.5rem; background: #7c3aed; color: #ffffff; text-decoration: none; border-radius: 0.5rem; font-weight: 600; transition: all 0.3s;" onmouseover="this.style.background=\'#6d28d9\'" onmouseout="this.style.background=\'#7c3aed\'">
                        Zobacz oferty ▸
                    </a>
                </div>
            </div>',
            esc_html($service),
            esc_html($city),
            esc_url($url)
        );
    }

    /**
     * Get compact inline CTA
     *
     * @param string $text CTA text
     * @param string $url Landing page URL
     * @return string HTML for compact CTA
     */
    public function get_compact_cta(string $text, string $url): string {
        return sprintf(
            '<div class="pearblog-cta-compact" style="margin: 1.5rem 0; padding: 1rem; background: #f9fafb; border: 2px solid #7c3aed; border-radius: 0.5rem;">
                <p style="margin: 0; text-align: center;">
                    <strong style="color: #7c3aed;">👉</strong>
                    <a href="%s" style="color: #7c3aed; font-weight: 600; text-decoration: none;">%s</a>
                </p>
            </div>',
            esc_url($url),
            esc_html($text)
        );
    }

    /**
     * Get sticky bottom CTA bar
     *
     * @param string $phone Phone number
     * @param string $text CTA text
     * @return string HTML for sticky CTA
     */
    public function get_sticky_cta(string $phone, string $text = 'Zadzwoń teraz'): string {
        if (!$this->config['sticky_enabled']) {
            return '';
        }

        return sprintf(
            '<div class="pearblog-cta-sticky" style="position: fixed; bottom: 0; left: 0; right: 0; background: linear-gradient(135deg, #7c3aed 0%%, #6d28d9 100%%); padding: 1rem; box-shadow: 0 -4px 12px rgba(0,0,0,0.15); z-index: 9999; display: none;" id="pearblog-sticky-cta">
                <div style="max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                    <div style="color: #ffffff; font-weight: 600;">
                        ☎ Potrzebujesz pomocy? Zadzwoń do lokalnego specjalisty
                    </div>
                    <a href="tel:%s" style="display: inline-block; padding: 0.75rem 2rem; background: #ffffff; color: #7c3aed; text-decoration: none; border-radius: 0.5rem; font-weight: 700; transition: all 0.3s;" onmouseover="this.style.background=\'#f3f4f6\'" onmouseout="this.style.background=\'#ffffff\'">
                        %s: %s
                    </a>
                    <button onclick="document.getElementById(\'pearblog-sticky-cta\').style.display=\'none\'" style="background: transparent; border: none; color: #ffffff; font-size: 1.5rem; cursor: pointer; padding: 0.5rem;" aria-label="Zamknij">
                        ×
                    </button>
                </div>
            </div>
            <script>
            (function() {
                var scrollThreshold = 500;
                var stickyCta = document.getElementById("pearblog-sticky-cta");
                if (stickyCta) {
                    window.addEventListener("scroll", function() {
                        if (window.scrollY > scrollThreshold) {
                            stickyCta.style.display = "block";
                        }
                    });
                }
            })();
            </script>',
            esc_attr(str_replace(' ', '', $phone)),
            esc_html($text),
            esc_html($phone)
        );
    }

    /**
     * Get exit intent popup CTA
     *
     * @param string $service Service name
     * @param string $city City name
     * @param string $url Landing page URL
     * @return string HTML + JavaScript for exit intent CTA
     */
    public function get_exit_intent_cta(string $service, string $city, string $url): string {
        if (!$this->config['exit_intent_enabled']) {
            return '';
        }

        return sprintf(
            '<div id="pearblog-exit-intent-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 99999; align-items: center; justify-content: center;">
                <div style="background: #ffffff; padding: 2rem; border-radius: 1rem; max-width: 500px; margin: 1rem; position: relative; box-shadow: 0 20px 40px rgba(0,0,0,0.3);">
                    <button onclick="document.getElementById(\'pearblog-exit-intent-modal\').style.display=\'none\'" style="position: absolute; top: 1rem; right: 1rem; background: transparent; border: none; font-size: 2rem; cursor: pointer; color: #6b7280;" aria-label="Zamknij">
                        ×
                    </button>
                    <h2 style="margin: 0 0 1rem 0; color: #1f2937; font-size: 1.5rem; font-weight: 700;">
                        ⏰ Zanim wyjdziesz...
                    </h2>
                    <p style="margin: 0 0 1.5rem 0; color: #4b5563; line-height: 1.6;">
                        Potrzebujesz <strong>%s</strong> w <strong>%s</strong>?<br>
                        Zobacz sprawdzonych specjalistów i umów wizytę w 2 minuty!
                    </p>
                    <a href="%s" style="display: block; padding: 1rem; background: #7c3aed; color: #ffffff; text-decoration: none; border-radius: 0.5rem; font-weight: 600; text-align: center; transition: all 0.3s;" onmouseover="this.style.background=\'#6d28d9\'" onmouseout="this.style.background=\'#7c3aed\'">
                        Zobacz oferty teraz ▸
                    </a>
                </div>
            </div>
            <script>
            (function() {
                var exitIntentShown = false;
                var modal = document.getElementById("pearblog-exit-intent-modal");

                if (modal) {
                    document.addEventListener("mouseleave", function(e) {
                        if (!exitIntentShown && e.clientY < 50) {
                            modal.style.display = "flex";
                            exitIntentShown = true;

                            // Track exit intent trigger
                            if (typeof gtag !== "undefined") {
                                gtag("event", "exit_intent_shown", {
                                    "event_category": "engagement",
                                    "event_label": "%s %s"
                                });
                            }
                        }
                    });
                }
            })();
            </script>',
            esc_html($service),
            esc_html($city),
            esc_url($url),
            esc_js($service),
            esc_js($city)
        );
    }

    /**
     * Get CTA for content footer
     *
     * @param string $service Service name
     * @param string $city City name
     * @param string $url Landing page URL
     * @param string $phone Phone number
     * @return string HTML for footer CTA
     */
    public function get_footer_cta(string $service, string $city, string $url, string $phone): string {
        return sprintf(
            '<div class="pearblog-cta-footer" style="margin: 3rem 0; padding: 2rem; background: linear-gradient(135deg, #667eea 0%%, #764ba2 100%%); border-radius: 1rem; color: #ffffff; text-align: center;">
                <h3 style="margin: 0 0 1rem 0; font-size: 1.75rem; font-weight: 800;">
                    Szukasz %s w %s?
                </h3>
                <p style="margin: 0 0 1.5rem 0; font-size: 1.125rem; opacity: 0.95;">
                    Sprawdź sprawdzonych specjalistów w Twojej okolicy
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="%s" style="display: inline-block; padding: 1rem 2rem; background: #ffffff; color: #7c3aed; text-decoration: none; border-radius: 0.5rem; font-weight: 700; transition: all 0.3s;" onmouseover="this.style.transform=\'scale(1.05)\'" onmouseout="this.style.transform=\'scale(1)\'">
                        📋 Zobacz oferty
                    </a>
                    <a href="tel:%s" style="display: inline-block; padding: 1rem 2rem; background: #f59e0b; color: #ffffff; text-decoration: none; border-radius: 0.5rem; font-weight: 700; transition: all 0.3s;" onmouseover="this.style.background=\'#d97706\'" onmouseout="this.style.background=\'#f59e0b\'">
                        ☎ Zadzwoń teraz
                    </a>
                </div>
            </div>',
            esc_html($service),
            esc_html($city),
            esc_url($url),
            esc_attr(str_replace(' ', '', $phone))
        );
    }

    /**
     * Inject CTAs into content
     *
     * @param string $content Post content
     * @param array $params CTA parameters (service, city, url, phone)
     * @return string Modified content with CTAs
     */
    public function inject_ctas_into_content(string $content, array $params): string {
        if (!$this->config['enabled']) {
            return $content;
        }

        $service = $params['service'] ?? 'specjalistę';
        $city = $params['city'] ?? '';
        $url = $params['url'] ?? '#';
        $phone = $params['phone'] ?? '';

        // Split content into sections
        $sections = $this->split_content_into_sections($content);

        if (count($sections) < 3) {
            // Content too short, just add footer CTA
            if ($phone) {
                $content .= $this->get_footer_cta($service, $city, $url, $phone);
            } else {
                $content .= $this->get_inline_cta($service, $city, $url);
            }
            return $content;
        }

        // Inject CTAs at strategic positions
        $result = [];

        // Introduction section
        $result[] = $sections[0];

        // After introduction - inline CTA
        if ($this->config['inline_enabled'] && count($sections) > 1) {
            $result[] = $this->get_inline_cta($service, $city, $url);
        }

        // Middle sections
        for ($i = 1; $i < count($sections) - 1; $i++) {
            $result[] = $sections[$i];

            // Add compact CTA in middle section
            if ($i === floor(count($sections) / 2)) {
                $result[] = $this->get_compact_cta(
                    "Sprawdź {$service} w {$city}",
                    $url
                );
            }
        }

        // Last section
        if (isset($sections[count($sections) - 1])) {
            $result[] = $sections[count($sections) - 1];
        }

        // Footer CTA
        if ($phone) {
            $result[] = $this->get_footer_cta($service, $city, $url, $phone);
        }

        // Sticky CTA (will be added to footer)
        if ($this->config['sticky_enabled'] && $phone) {
            $result[] = $this->get_sticky_cta($phone);
        }

        // Exit intent (will be added to footer)
        if ($this->config['exit_intent_enabled']) {
            $result[] = $this->get_exit_intent_cta($service, $city, $url);
        }

        return implode("\n\n", $result);
    }

    /**
     * Split content into sections (by h2 tags)
     *
     * @param string $content HTML content
     * @return array Content sections
     */
    private function split_content_into_sections(string $content): array {
        // Split by H2 tags
        $sections = preg_split('/(<h2[^>]*>.*?<\/h2>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if (count($sections) <= 1) {
            // No H2 tags, try H3
            $sections = preg_split('/(<h3[^>]*>.*?<\/h3>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        }

        if (count($sections) <= 1) {
            // No headers, split by paragraphs
            $sections = preg_split('/(<\/p>\s*<p[^>]*>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        }

        // Merge header with following content
        $merged = [];
        for ($i = 0; $i < count($sections); $i++) {
            if (preg_match('/<h[23][^>]*>/i', $sections[$i]) && isset($sections[$i + 1])) {
                $merged[] = $sections[$i] . $sections[$i + 1];
                $i++; // Skip next element
            } else {
                $merged[] = $sections[$i];
            }
        }

        return $merged;
    }

    /**
     * Get landing URL for service and city
     *
     * @param string $service Service slug (e.g., "mechanik")
     * @param string $city City slug (e.g., "warszawa")
     * @return string Landing page URL
     */
    public function get_landing_url(string $service, string $city): string {
        $base_url = get_option('pearblog_pt24_base_url', 'https://pt24.pro');
        return "{$base_url}/{$service}/{$city}/";
    }

    /**
     * Track CTA interaction
     *
     * @param string $cta_type CTA type (inline, sticky, exit_intent, footer)
     * @param int $content_id Content ID
     * @return void
     */
    public function track_cta_interaction(string $cta_type, int $content_id): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pearblog_content_links';

        // Increment click count for this content's CTAs
        $wpdb->query($wpdb->prepare("
            UPDATE {$table_name}
            SET click_count = click_count + 1
            WHERE content_id = %d AND position = %s
        ", $content_id, $cta_type));
    }

    /**
     * Enable/disable CTA injection
     *
     * @param bool $enabled Enable status
     * @return void
     */
    public function set_enabled(bool $enabled): void {
        update_option('pearblog_pt24_cta_enabled', $enabled);
        $this->config['enabled'] = $enabled;
    }

    /**
     * Configure CTA types
     *
     * @param array $config Configuration array
     * @return void
     */
    public function configure(array $config): void {
        if (isset($config['inline'])) {
            update_option('pearblog_pt24_cta_inline', $config['inline']);
            $this->config['inline_enabled'] = $config['inline'];
        }

        if (isset($config['sticky'])) {
            update_option('pearblog_pt24_cta_sticky', $config['sticky']);
            $this->config['sticky_enabled'] = $config['sticky'];
        }

        if (isset($config['exit_intent'])) {
            update_option('pearblog_pt24_cta_exit_intent', $config['exit_intent']);
            $this->config['exit_intent_enabled'] = $config['exit_intent'];
        }
    }
}

<?php
/**
 * Lead Attributor
 *
 * Tracks lead sources from PearBlog content through PT24 landings to final conversion
 *
 * @package PearBlogEngine
 * @subpackage Integration
 */

namespace PearBlogEngine\Integration;

class LeadAttributor {

    /**
     * @var string Cookie name for source content
     */
    const COOKIE_SOURCE_CONTENT = 'pb_source_content';

    /**
     * @var string Cookie name for source landing
     */
    const COOKIE_SOURCE_LANDING = 'pb_source_landing';

    /**
     * @var string Session key for pageviews
     */
    const SESSION_PAGEVIEWS = 'pb_pageviews';

    /**
     * @var int Cookie expiration (24 hours)
     */
    const COOKIE_EXPIRATION = 86400;

    /**
     * Constructor
     */
    public function __construct() {
        // Register hooks
        add_action('init', [$this, 'maybe_start_session']);
        add_action('wp_footer', [$this, 'inject_tracking_script']);
    }

    /**
     * Start PHP session if needed
     *
     * @return void
     */
    public function maybe_start_session(): void {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
    }

    /**
     * Attribute lead to source content/landing
     *
     * @param int $lead_id Lead ID
     * @return bool Success status
     */
    public function attribute_lead(int $lead_id): bool {
        global $wpdb;

        // Get source from cookies/session
        $source_content_id = $this->get_source_content_from_cookie();
        $source_landing_id = $this->get_source_landing_from_cookie();
        $funnel_stage = $this->detect_funnel_stage();
        $session_id = $this->get_session_id();

        // Insert attribution record
        $table_name = $wpdb->prefix . 'pearblog_lead_attribution';

        $result = $wpdb->insert(
            $table_name,
            [
                'lead_id' => $lead_id,
                'source_content_id' => $source_content_id,
                'source_landing_id' => $source_landing_id,
                'funnel_stage' => $funnel_stage,
                'session_id' => $session_id,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%d', '%d', '%s', '%s', '%s']
        );

        if ($result) {
            // Update conversion count for source content links
            if ($source_content_id) {
                $this->increment_conversion_count($source_content_id);
            }

            // Clear attribution cookies after successful attribution
            $this->clear_attribution_cookies();
        }

        return (bool) $result;
    }

    /**
     * Get source content ID from cookie
     *
     * @return int|null Content ID or null
     */
    private function get_source_content_from_cookie(): ?int {
        if (isset($_COOKIE[self::COOKIE_SOURCE_CONTENT])) {
            $content_id = intval($_COOKIE[self::COOKIE_SOURCE_CONTENT]);
            return $content_id > 0 ? $content_id : null;
        }

        // Fallback: check referrer
        if (isset($_SERVER['HTTP_REFERER'])) {
            $content_id = $this->extract_content_id_from_referrer($_SERVER['HTTP_REFERER']);
            if ($content_id) {
                return $content_id;
            }
        }

        return null;
    }

    /**
     * Get source landing ID from cookie
     *
     * @return int|null Landing ID or null
     */
    private function get_source_landing_from_cookie(): ?int {
        if (isset($_COOKIE[self::COOKIE_SOURCE_LANDING])) {
            $landing_id = intval($_COOKIE[self::COOKIE_SOURCE_LANDING]);
            return $landing_id > 0 ? $landing_id : null;
        }

        // Try to get current page if it's a PT24 landing
        if (is_singular('pt24_landing')) {
            return get_the_ID();
        }

        return null;
    }

    /**
     * Extract content ID from referrer URL
     *
     * @param string $referrer Referrer URL
     * @return int|null Content ID or null
     */
    private function extract_content_id_from_referrer(string $referrer): ?int {
        // Parse URL
        $parsed = parse_url($referrer);

        if (!isset($parsed['path'])) {
            return null;
        }

        // Check if referrer is from same site
        $site_url = parse_url(home_url());
        if (isset($parsed['host']) && $parsed['host'] !== $site_url['host']) {
            return null;
        }

        // Try to get post ID from URL
        $post_id = url_to_postid($referrer);

        if ($post_id > 0) {
            $post = get_post($post_id);
            if ($post && $post->post_type === 'post') {
                return $post_id;
            }
        }

        return null;
    }

    /**
     * Detect funnel stage based on user behavior
     *
     * @return string Funnel stage (awareness, consideration, decision)
     */
    private function detect_funnel_stage(): string {
        $pageviews = $this->get_pageview_count();

        // Awareness: First interaction (1-2 pageviews)
        if ($pageviews <= 2) {
            return 'awareness';
        }

        // Consideration: Multiple pageviews (3-5)
        if ($pageviews <= 5) {
            return 'consideration';
        }

        // Decision: High engagement (6+ pageviews)
        return 'decision';
    }

    /**
     * Get pageview count from session
     *
     * @return int Pageview count
     */
    private function get_pageview_count(): int {
        if (isset($_SESSION[self::SESSION_PAGEVIEWS])) {
            return intval($_SESSION[self::SESSION_PAGEVIEWS]);
        }
        return 1;
    }

    /**
     * Increment pageview count
     *
     * @return void
     */
    public function increment_pageviews(): void {
        if (!isset($_SESSION[self::SESSION_PAGEVIEWS])) {
            $_SESSION[self::SESSION_PAGEVIEWS] = 1;
        } else {
            $_SESSION[self::SESSION_PAGEVIEWS]++;
        }
    }

    /**
     * Get or generate session ID
     *
     * @return string Session ID
     */
    private function get_session_id(): string {
        if (session_id()) {
            return session_id();
        }

        // Generate unique session ID
        return md5(uniqid('pb_', true) . $_SERVER['REMOTE_ADDR'] ?? '');
    }

    /**
     * Set source content cookie
     *
     * @param int $content_id Content ID
     * @return void
     */
    public function set_source_content(int $content_id): void {
        setcookie(
            self::COOKIE_SOURCE_CONTENT,
            strval($content_id),
            time() + self::COOKIE_EXPIRATION,
            '/',
            '',
            is_ssl(),
            true // HttpOnly
        );
    }

    /**
     * Set source landing cookie
     *
     * @param int $landing_id Landing page ID
     * @return void
     */
    public function set_source_landing(int $landing_id): void {
        setcookie(
            self::COOKIE_SOURCE_LANDING,
            strval($landing_id),
            time() + self::COOKIE_EXPIRATION,
            '/',
            '',
            is_ssl(),
            true // HttpOnly
        );
    }

    /**
     * Clear attribution cookies
     *
     * @return void
     */
    private function clear_attribution_cookies(): void {
        setcookie(self::COOKIE_SOURCE_CONTENT, '', time() - 3600, '/');
        setcookie(self::COOKIE_SOURCE_LANDING, '', time() - 3600, '/');
    }

    /**
     * Increment conversion count for content
     *
     * @param int $content_id Content ID
     * @return void
     */
    private function increment_conversion_count(int $content_id): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pearblog_content_links';

        $wpdb->query($wpdb->prepare("
            UPDATE {$table_name}
            SET conversion_count = conversion_count + 1
            WHERE content_id = %d
        ", $content_id));
    }

    /**
     * Inject tracking script into footer
     *
     * @return void
     */
    public function inject_tracking_script(): void {
        // Only inject on single posts and PT24 landings
        if (!is_singular(['post', 'pt24_landing'])) {
            return;
        }

        $post_id = get_the_ID();
        $post_type = get_post_type();

        ?>
        <script>
        (function() {
            'use strict';

            // Track pageview
            fetch('<?php echo esc_url(rest_url('pearblog/v1/pt24/track-pageview')); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                },
                body: JSON.stringify({
                    post_id: <?php echo intval($post_id); ?>,
                    post_type: '<?php echo esc_js($post_type); ?>'
                })
            });

            // Track clicks on PT24 links
            document.querySelectorAll('a[data-pb-source], a[href*="pt24.pro"]').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    var contentId = this.getAttribute('data-pb-source') || <?php echo intval($post_id); ?>;
                    var targetUrl = this.getAttribute('href');

                    // Set source cookie
                    document.cookie = 'pb_source_content=' + contentId + '; path=/; max-age=<?php echo self::COOKIE_EXPIRATION; ?>';

                    // Track click event
                    fetch('<?php echo esc_url(rest_url('pearblog/v1/pt24/track-conversion')); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                        },
                        body: JSON.stringify({
                            content_id: contentId,
                            event_type: 'click',
                            target_url: targetUrl
                        })
                    });

                    // Track with Google Analytics if available
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'pt24_link_click', {
                            'event_category': 'engagement',
                            'event_label': targetUrl,
                            'content_id': contentId
                        });
                    }
                });
            });

            // Track time on page
            var startTime = Date.now();
            window.addEventListener('beforeunload', function() {
                var timeOnPage = Math.floor((Date.now() - startTime) / 1000);

                if (timeOnPage > 10) { // Only track if user spent more than 10 seconds
                    navigator.sendBeacon(
                        '<?php echo esc_url(rest_url('pearblog/v1/pt24/track-time')); ?>',
                        JSON.stringify({
                            post_id: <?php echo intval($post_id); ?>,
                            time_seconds: timeOnPage
                        })
                    );
                }
            });
        })();
        </script>
        <?php
    }

    /**
     * Get attribution statistics
     *
     * @return array Statistics
     */
    public function get_attribution_stats(): array {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pearblog_lead_attribution';

        return [
            'total_attributions' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$table_name}"
            ),
            'by_funnel_stage' => $wpdb->get_results(
                "SELECT funnel_stage, COUNT(*) as count
                FROM {$table_name}
                GROUP BY funnel_stage",
                ARRAY_A
            ),
            'with_content_source' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$table_name}
                WHERE source_content_id IS NOT NULL"
            ),
            'with_landing_source' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$table_name}
                WHERE source_landing_id IS NOT NULL"
            ),
            'attribution_rate' => $this->calculate_attribution_rate()
        ];
    }

    /**
     * Calculate attribution rate (% of leads with source)
     *
     * @return float Attribution rate (0-100)
     */
    private function calculate_attribution_rate(): float {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pearblog_lead_attribution';

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        $with_source = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name}
            WHERE source_content_id IS NOT NULL OR source_landing_id IS NOT NULL"
        );

        if ($total == 0) {
            return 0.0;
        }

        return round(($with_source / $total) * 100, 2);
    }

    /**
     * Get top performing content by lead attribution
     *
     * @param int $limit Number of results
     * @return array Top content pieces
     */
    public function get_top_content_by_leads(int $limit = 10): array {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pearblog_lead_attribution';

        return $wpdb->get_results($wpdb->prepare("
            SELECT
                la.source_content_id as content_id,
                p.post_title,
                COUNT(*) as lead_count,
                COUNT(DISTINCT la.session_id) as unique_sessions
            FROM {$table_name} la
            INNER JOIN {$wpdb->posts} p ON la.source_content_id = p.ID
            WHERE la.source_content_id IS NOT NULL
            GROUP BY la.source_content_id, p.post_title
            ORDER BY lead_count DESC
            LIMIT %d
        ", $limit), ARRAY_A);
    }
}

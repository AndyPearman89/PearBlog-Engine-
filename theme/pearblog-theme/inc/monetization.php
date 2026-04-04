<?php
/**
 * Monetization Engine
 *
 * @package PearBlog
 * @version 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Auto Ad Injection Engine
 * Automatically inserts ads every X paragraphs in content
 */
function pearblog_auto_inject_ads($content) {
    if (is_admin() || is_feed() || !is_singular()) {
        return $content;
    }

    $config = pb_get_site_config();

    if (!$config['auto_ad_injection']) {
        return $content;
    }

    $paragraphs_between_ads = max(1, intval($config['ad_injection_paragraphs']));

    // Split content into paragraphs
    $paragraphs = preg_split('/(<p[^>]*>.*?<\/p>)/is', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

    $new_content = '';
    $paragraph_count = 0;
    $ad_count = 0;

    foreach ($paragraphs as $paragraph) {
        $new_content .= $paragraph;

        // Check if this is an actual paragraph tag
        if (preg_match('/<p[^>]*>/i', $paragraph)) {
            $paragraph_count++;

            // Insert ad after every X paragraphs
            if ($paragraph_count % $paragraphs_between_ads === 0) {
                $ad_count++;
                $new_content .= pearblog_get_inline_ad($ad_count);
            }
        }
    }

    return $new_content;
}
add_filter('the_content', 'pearblog_auto_inject_ads', 15);

/**
 * Get inline ad HTML
 */
function pearblog_get_inline_ad($position = 1) {
    $adsense_client = get_option('pearblog_adsense_client', '');
    $adsense_slot = get_option('pearblog_adsense_slot_content_' . $position, get_option('pearblog_adsense_slot_content', ''));

    if (empty($adsense_client) || empty($adsense_slot)) {
        return '';
    }

    ob_start();
    ?>
    <div class="pb-ad-inline pb-ad-position-<?php echo esc_attr($position); ?>">
        <div class="pb-ad-label"><?php _e('Advertisement', 'pearblog-theme'); ?></div>
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?php echo esc_attr($adsense_client); ?>"
                crossorigin="anonymous"></script>
        <ins class="adsbygoogle"
             style="display:block;text-align:center"
             data-ad-layout="in-article"
             data-ad-format="fluid"
             data-ad-client="<?php echo esc_attr($adsense_client); ?>"
             data-ad-slot="<?php echo esc_attr($adsense_slot); ?>"></ins>
        <script>
             (adsbygoogle = window.adsbygoogle || []).push({});
        </script>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Get sticky mobile ad
 */
function pearblog_get_sticky_mobile_ad() {
    $config = pb_get_site_config();

    if (!$config['sticky_mobile_cta'] || !wp_is_mobile()) {
        return '';
    }

    $adsense_client = get_option('pearblog_adsense_client', '');
    $adsense_slot = get_option('pearblog_adsense_slot_mobile_sticky', '');

    if (empty($adsense_client) || empty($adsense_slot)) {
        return '';
    }

    ob_start();
    ?>
    <div class="pb-ad-sticky-mobile" id="pb-sticky-mobile-ad">
        <button class="pb-ad-close" aria-label="<?php esc_attr_e('Close ad', 'pearblog-theme'); ?>" onclick="document.getElementById('pb-sticky-mobile-ad').style.display='none'">&times;</button>
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?php echo esc_attr($adsense_client); ?>"
                crossorigin="anonymous"></script>
        <ins class="adsbygoogle"
             style="display:block"
             data-ad-client="<?php echo esc_attr($adsense_client); ?>"
             data-ad-slot="<?php echo esc_attr($adsense_slot); ?>"
             data-ad-format="auto"
             data-full-width-responsive="true"></ins>
        <script>
             (adsbygoogle = window.adsbygoogle || []).push({});
        </script>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Output sticky mobile ad in footer
 */
function pearblog_output_sticky_mobile_ad() {
    echo pearblog_get_sticky_mobile_ad();
}
add_action('wp_footer', 'pearblog_output_sticky_mobile_ad');

/**
 * High CTR Zone Detection
 * Identifies scroll-based trigger zones for optimal ad placement
 */
function pearblog_track_ctr_zones() {
    if (!is_singular() || is_admin()) {
        return;
    }

    // This would be implemented with JavaScript to track user behavior
    // and send data back to optimize ad placement
    ?>
    <script>
    // CTR Zone Tracking
    (function() {
        let scrollDepths = [25, 50, 75, 100];
        let triggered = {};

        window.addEventListener('scroll', function() {
            let scrollPercent = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;

            scrollDepths.forEach(function(depth) {
                if (scrollPercent >= depth && !triggered[depth]) {
                    triggered[depth] = true;

                    // Track scroll depth event
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'scroll_depth', {
                            'depth': depth + '%',
                            'post_id': <?php echo get_the_ID(); ?>
                        });
                    }

                    // Trigger ad loading at optimal zones
                    if (depth === 50 && typeof pearblogLoadAd === 'function') {
                        pearblogLoadAd('mid-content');
                    }
                }
            });
        });
    })();
    </script>
    <?php
}
add_action('wp_footer', 'pearblog_track_ctr_zones');

/**
 * Affiliate Link Automation
 * Automatically convert product links to affiliate links
 */
function pearblog_auto_affiliate_links($content) {
    if (is_admin() || is_feed()) {
        return $content;
    }

    $affiliate_rules = get_option('pearblog_affiliate_rules', array());

    if (empty($affiliate_rules)) {
        return $content;
    }

    foreach ($affiliate_rules as $rule) {
        if (empty($rule['domain']) || empty($rule['affiliate_tag'])) {
            continue;
        }

        // Replace domain links with affiliate versions
        $pattern = '/href=["\'](https?:\/\/' . preg_quote($rule['domain'], '/') . '[^"\']*)["\']/ i';
        $content = preg_replace_callback($pattern, function($matches) use ($rule) {
            $url = $matches[1];

            // Add affiliate tag
            if (strpos($url, '?') !== false) {
                $url .= '&' . $rule['affiliate_tag'];
            } else {
                $url .= '?' . $rule['affiliate_tag'];
            }

            // Add rel="nofollow sponsored"
            return 'href="' . $url . '" rel="nofollow sponsored"';
        }, $content);
    }

    return $content;
}
add_filter('the_content', 'pearblog_auto_affiliate_links', 20);

/**
 * CTA Placement Logic
 * Intelligently place CTAs based on content analysis
 */
function pearblog_smart_cta_placement($content) {
    if (is_admin() || is_feed() || !is_singular()) {
        return $content;
    }

    // Count total paragraphs
    $paragraph_count = substr_count($content, '<p');

    if ($paragraph_count < 3) {
        return $content; // Too short for CTA insertion
    }

    // Insert CTA at optimal position (after 60% of content)
    $optimal_position = ceil($paragraph_count * 0.6);

    $paragraphs = preg_split('/(<p[^>]*>)/is', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    $current_paragraph = 0;
    $new_content = '';

    foreach ($paragraphs as $i => $paragraph) {
        $new_content .= $paragraph;

        if (preg_match('/<p[^>]*>/i', $paragraph)) {
            $current_paragraph++;

            if ($current_paragraph === $optimal_position) {
                // Insert CTA block
                ob_start();
                get_template_part('template-parts/block-cta', null, array(
                    'type' => 'inline',
                    'position' => 'content'
                ));
                $new_content .= ob_get_clean();
            }
        }
    }

    return $new_content;
}
// This filter is opt-in via settings
if (get_option('pearblog_smart_cta_enabled', false)) {
    add_filter('the_content', 'pearblog_smart_cta_placement', 25);
}

/**
 * Revenue tracking with per-post, per-type, and daily aggregate storage.
 *
 * Stores three kinds of data:
 *   1. Per-post lifetime total  – `_pearblog_revenue_{type}` post meta.
 *   2. Per-post daily total     – `_pearblog_revenue_{type}_{Y-m-d}` post meta.
 *   3. Site-wide daily total    – `pearblog_revenue_{type}_{Y-m-d}` option.
 *
 * @param float  $amount Revenue amount to record.
 * @param string $type   Revenue type: 'ad' | 'affiliate' | 'saas'.
 */
function pearblog_track_revenue($amount, $type = 'ad') {
    $amount = floatval($amount);
    if ($amount <= 0) {
        return;
    }

    $type  = sanitize_key($type);
    $today = gmdate('Y-m-d');

    // 1. Per-post lifetime total.
    $post_id = get_the_ID();
    if ($post_id) {
        $lifetime_key = '_pearblog_revenue_' . $type;
        $current      = floatval(get_post_meta($post_id, $lifetime_key, true));
        update_post_meta($post_id, $lifetime_key, $current + $amount);

        // 2. Per-post daily total.
        $daily_key = '_pearblog_revenue_' . $type . '_' . $today;
        $daily     = floatval(get_post_meta($post_id, $daily_key, true));
        update_post_meta($post_id, $daily_key, $daily + $amount);
    }

    // 3. Site-wide daily aggregate (stored as option for fast queries).
    $site_key = 'pearblog_revenue_' . $type . '_' . $today;
    $site_val = floatval(get_option($site_key, 0));
    update_option($site_key, $site_val + $amount, false);
}

/**
 * Get aggregate revenue for a date range.
 *
 * @param string $type       Revenue type: 'ad' | 'affiliate' | 'saas' | 'all'.
 * @param int    $days_back  Number of days to look back (default 30).
 * @return array Associative array with 'total', 'daily' (date => amount), 'types' breakdown.
 */
function pearblog_get_revenue_summary($type = 'all', $days_back = 30) {
    $types = ('all' === $type) ? array('ad', 'affiliate', 'saas') : array(sanitize_key($type));
    $daily = array();
    $by_type = array();

    for ($i = 0; $i < $days_back; $i++) {
        $date = gmdate('Y-m-d', strtotime("-{$i} days"));
        $day_total = 0;

        foreach ($types as $t) {
            $amount     = floatval(get_option('pearblog_revenue_' . $t . '_' . $date, 0));
            $day_total += $amount;
            if (!isset($by_type[$t])) {
                $by_type[$t] = 0;
            }
            $by_type[$t] += $amount;
        }

        $daily[$date] = round($day_total, 2);
    }

    $total = round(array_sum($by_type), 2);

    return array(
        'total'  => $total,
        'daily'  => $daily,
        'types'  => $by_type,
    );
}

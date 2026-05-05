<?php
/**
 * PearBlog Engine × PT24 Integration Helper Functions
 *
 * Helper functions for integrating PearBlog content into PT24 landing pages
 *
 * @package PearBlog-Theme
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get related PearBlog articles for PT24 landing pages
 *
 * @param string $service Service category (e.g., "mechanik")
 * @param string $city City name (e.g., "warszawa")
 * @param int $limit Number of articles to return
 * @return array Array of article data
 */
function pearblog_get_related_articles(string $service, string $city, int $limit = 3): array {
    global $wpdb;

    $table_name = $wpdb->prefix . 'pearblog_content_meta';

    // Build query
    $query = "
        SELECT p.ID, p.post_title, p.post_excerpt, p.guid, cm.seo_score
        FROM {$wpdb->posts} p
        INNER JOIN {$table_name} cm ON p.ID = cm.post_id
        WHERE cm.category_id = %s
        AND p.post_status = 'publish'
        AND p.post_type = 'post'
    ";

    $params = [$service];

    // Add city filter if provided
    if (!empty($city)) {
        $query .= " AND (cm.city_id = %s OR cm.city_id IS NULL)";
        $params[] = $city;
    }

    $query .= " ORDER BY cm.seo_score DESC, p.post_date DESC LIMIT %d";
    $params[] = $limit;

    $articles = $wpdb->get_results($wpdb->prepare($query, ...$params));

    if (empty($articles)) {
        return [];
    }

    // Format results
    return array_map(function($article) {
        return [
            'id' => $article->ID,
            'title' => $article->post_title,
            'excerpt' => $article->post_excerpt ?: wp_trim_words(get_the_content(null, false, $article->ID), 20),
            'url' => get_permalink($article->ID),
            'seo_score' => $article->seo_score ?? 0
        ];
    }, $articles);
}

/**
 * Display related articles section for PT24 landing pages
 *
 * @param string $service Service category
 * @param string $city City name
 * @param int $limit Number of articles
 * @return void
 */
function pearblog_display_related_articles(string $service, string $city, int $limit = 3): void {
    $articles = pearblog_get_related_articles($service, $city, $limit);

    if (empty($articles)) {
        return;
    }

    ?>
    <section class="pearblog-related-articles" style="margin: 3rem 0; padding: 2rem; background: #f9fafb; border-radius: 0.75rem;">
        <div class="container">
            <h2 style="margin: 0 0 1.5rem 0; color: #1f2937; font-size: 1.75rem; font-weight: 700;">
                📚 Porady eksperta
            </h2>
            <div class="articles-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                <?php foreach ($articles as $article) : ?>
                <article class="article-card" style="background: #ffffff; padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); transition: transform 0.3s, box-shadow 0.3s;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 4px 12px rgba(124,58,237,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.05)';">
                    <h3 style="margin: 0 0 0.75rem 0; font-size: 1.125rem; line-height: 1.4;">
                        <a href="<?php echo esc_url($article['url']); ?>" style="color: #1f2937; text-decoration: none; font-weight: 600;" onmouseover="this.style.color='#7c3aed';" onmouseout="this.style.color='#1f2937';">
                            <?php echo esc_html($article['title']); ?>
                        </a>
                    </h3>
                    <p style="margin: 0 0 1rem 0; color: #6b7280; line-height: 1.6; font-size: 0.95rem;">
                        <?php echo esc_html($article['excerpt']); ?>
                    </p>
                    <a href="<?php echo esc_url($article['url']); ?>" class="read-more" style="display: inline-flex; align-items: center; color: #7c3aed; font-weight: 600; text-decoration: none; font-size: 0.95rem;" onmouseover="this.style.color='#6d28d9';" onmouseout="this.style.color='#7c3aed';">
                        Czytaj więcej →
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}

/**
 * Get PT24 integration statistics
 *
 * @return array Integration stats
 */
function pearblog_get_integration_stats(): array {
    global $wpdb;

    $content_meta_table = $wpdb->prefix . 'pearblog_content_meta';
    $content_links_table = $wpdb->prefix . 'pearblog_content_links';
    $lead_attribution_table = $wpdb->prefix . 'pearblog_lead_attribution';

    return [
        'total_content' => $wpdb->get_var("SELECT COUNT(*) FROM {$content_meta_table}"),
        'total_links' => $wpdb->get_var("SELECT COUNT(*) FROM {$content_links_table}"),
        'total_clicks' => $wpdb->get_var("SELECT SUM(click_count) FROM {$content_links_table}") ?? 0,
        'total_conversions' => $wpdb->get_var("SELECT SUM(conversion_count) FROM {$content_links_table}") ?? 0,
        'total_attributed_leads' => $wpdb->get_var("SELECT COUNT(*) FROM {$lead_attribution_table}"),
        'conversion_rate' => 0 // Calculate below
    ];
}

/**
 * Check if PT24 integration is enabled
 *
 * @return bool
 */
function pearblog_is_integration_enabled(): bool {
    return (bool) get_option('pearblog_pt24_integration_enabled', false);
}

/**
 * Get PT24 landing URL for service and city
 *
 * @param string $service Service category
 * @param string $city City name
 * @return string Landing page URL
 */
function pearblog_get_landing_url(string $service, string $city): string {
    $service_slug = sanitize_title($service);
    $city_slug = sanitize_title($city);

    // Check if PT24 domain is configured
    $pt24_domain = get_option('pearblog_pt24_domain', 'pt24.pro');

    return "https://{$pt24_domain}/{$city_slug}/{$service_slug}/";
}

/**
 * Track content view from PT24 landing
 *
 * @param int $content_id Content ID
 * @param int $landing_id Landing page ID
 * @return void
 */
function pearblog_track_content_view(int $content_id, int $landing_id): void {
    // Set cookies for attribution
    setcookie('pb_source_landing', (string)$landing_id, time() + 86400, '/');
    setcookie('pb_last_content', (string)$content_id, time() + 86400, '/');

    // Fire action for tracking
    do_action('pearblog_content_viewed_from_landing', $content_id, $landing_id);
}

/**
 * Get content performance metrics
 *
 * @param int $content_id Content ID
 * @return array Performance metrics
 */
function pearblog_get_content_performance(int $content_id): array {
    global $wpdb;

    $links_table = $wpdb->prefix . 'pearblog_content_links';

    $metrics = $wpdb->get_row($wpdb->prepare("
        SELECT
            COUNT(*) as total_links,
            SUM(click_count) as total_clicks,
            SUM(conversion_count) as total_conversions
        FROM {$links_table}
        WHERE content_id = %d
    ", $content_id), ARRAY_A);

    return [
        'total_links' => intval($metrics['total_links'] ?? 0),
        'total_clicks' => intval($metrics['total_clicks'] ?? 0),
        'total_conversions' => intval($metrics['total_conversions'] ?? 0),
        'click_through_rate' => 0, // Calculate if pageviews available
        'conversion_rate' => $metrics['total_clicks'] > 0
            ? round(($metrics['total_conversions'] / $metrics['total_clicks']) * 100, 2)
            : 0
    ];
}

/**
 * Register PearBlog integration shortcodes
 *
 * @return void
 */
function pearblog_register_integration_shortcodes(): void {
    // [pearblog_related service="mechanik" city="warszawa" limit="3"]
    add_shortcode('pearblog_related', function($atts) {
        $atts = shortcode_atts([
            'service' => '',
            'city' => '',
            'limit' => 3
        ], $atts);

        if (empty($atts['service'])) {
            return '';
        }

        ob_start();
        pearblog_display_related_articles($atts['service'], $atts['city'], intval($atts['limit']));
        return ob_get_clean();
    });

    // [pearblog_stats]
    add_shortcode('pearblog_stats', function() {
        $stats = pearblog_get_integration_stats();

        ob_start();
        ?>
        <div class="pearblog-stats" style="padding: 2rem; background: #f3f4f6; border-radius: 0.5rem;">
            <h3 style="margin: 0 0 1rem 0;">📊 Statystyki integracji</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 0.5rem;"><strong>Treści:</strong> <?php echo number_format($stats['total_content']); ?></li>
                <li style="margin-bottom: 0.5rem;"><strong>Linki:</strong> <?php echo number_format($stats['total_links']); ?></li>
                <li style="margin-bottom: 0.5rem;"><strong>Kliknięcia:</strong> <?php echo number_format($stats['total_clicks']); ?></li>
                <li style="margin-bottom: 0.5rem;"><strong>Konwersje:</strong> <?php echo number_format($stats['total_conversions']); ?></li>
                <li><strong>Leady:</strong> <?php echo number_format($stats['total_attributed_leads']); ?></li>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    });
}

// Register shortcodes
add_action('init', 'pearblog_register_integration_shortcodes');

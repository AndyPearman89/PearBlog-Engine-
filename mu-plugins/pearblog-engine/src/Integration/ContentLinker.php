<?php
/**
 * Content Linker
 *
 * Automatically creates internal links from PearBlog content to PT24 resources
 *
 * @package PearBlogEngine
 * @subpackage Integration
 */

namespace PearBlogEngine\Integration;

class ContentLinker {

    /**
     * @var int Minimum links per article
     */
    private $min_links;

    /**
     * @var int Maximum links per article
     */
    private $max_links;

    /**
     * Constructor
     */
    public function __construct() {
        $this->min_links = get_option('pearblog_pt24_min_links', 3);
        $this->max_links = get_option('pearblog_pt24_max_links', 5);
    }

    /**
     * Add smart links to content
     *
     * @param int $post_id Post ID
     * @return array Created links
     */
    public function add_smart_links(int $post_id): array {
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'post') {
            return [];
        }

        // Get content metadata
        $meta = $this->get_content_meta($post_id);

        if (!$meta) {
            return [];
        }

        $links = [];

        // Strategy 1: Category link
        if ($meta['category_id']) {
            $links[] = $this->create_category_link($meta['category_id']);
        }

        // Strategy 2: City link
        if ($meta['category_id'] && $meta['city_id']) {
            $links[] = $this->create_city_link($meta['category_id'], $meta['city_id']);
        }

        // Strategy 3: Top listings links
        if ($meta['category_id']) {
            $listing_links = $this->create_listings_links(
                $meta['category_id'],
                $meta['city_id'],
                $this->max_links - count($links)
            );
            $links = array_merge($links, $listing_links);
        }

        // Store links in database
        $this->save_links($post_id, $links);

        return $links;
    }

    /**
     * Create category link
     *
     * @param string $category Category slug
     * @return array Link data
     */
    private function create_category_link(string $category): array {
        $category_names = [
            'mechanik' => 'Mechanik samochodowy',
            'hydraulik' => 'Hydraulik',
            'elektryk' => 'Elektryk samochodowy',
            'laweta' => 'Laweta',
            'wulkanizacja' => 'Wulkanizacja'
        ];

        $name = $category_names[$category] ?? ucfirst($category);

        return [
            'type' => 'category',
            'url' => "https://pt24.pro/{$category}/",
            'text' => "Znajdź {$name}",
            'target_id' => $category,
            'position' => 'body'
        ];
    }

    /**
     * Create city-specific link
     *
     * @param string $category Category slug
     * @param string $city City slug
     * @return array Link data
     */
    private function create_city_link(string $category, string $city): array {
        $category_names = [
            'mechanik' => 'Mechanik',
            'hydraulik' => 'Hydraulik',
            'elektryk' => 'Elektryk',
            'laweta' => 'Laweta',
            'wulkanizacja' => 'Wulkanizacja'
        ];

        $city_names = [
            'warszawa' => 'Warszawa',
            'krakow' => 'Kraków',
            'wroclaw' => 'Wrocław',
            'poznan' => 'Poznań',
            'gdansk' => 'Gdańsk'
        ];

        $cat_name = $category_names[$category] ?? ucfirst($category);
        $city_name = $city_names[$city] ?? ucfirst($city);

        return [
            'type' => 'city',
            'url' => "https://pt24.pro/{$category}/{$city}/",
            'text' => "{$cat_name} {$city_name}",
            'target_id' => "{$category}_{$city}",
            'position' => 'body'
        ];
    }

    /**
     * Create links to top listings
     *
     * @param string $category Category slug
     * @param string|null $city City slug
     * @param int $limit Maximum number of links
     * @return array Links data
     */
    private function create_listings_links(string $category, ?string $city, int $limit): array {
        // In future, query actual PT24 listings
        // For now, create placeholder links
        $links = [];

        if ($limit <= 0) {
            return $links;
        }

        // Create one generic "Zobacz ranking" link
        $links[] = [
            'type' => 'listing',
            'url' => $city ?
                "https://pt24.pro/{$category}/{$city}/#ranking" :
                "https://pt24.pro/{$category}/#ranking",
            'text' => "Zobacz ranking firm",
            'target_id' => "{$category}_ranking",
            'position' => 'body'
        ];

        return $links;
    }

    /**
     * Get content metadata
     *
     * @param int $post_id Post ID
     * @return array|null Metadata or null
     */
    private function get_content_meta(int $post_id): ?array {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pearblog_content_meta';

        $meta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE post_id = %d",
            $post_id
        ), ARRAY_A);

        if (!$meta) {
            // Try to get from post meta
            return [
                'post_id' => $post_id,
                'category_id' => get_post_meta($post_id, '_category_id', true),
                'city_id' => get_post_meta($post_id, '_city_id', true),
                'content_type' => get_post_meta($post_id, '_content_type', true)
            ];
        }

        return $meta;
    }

    /**
     * Save links to database
     *
     * @param int $post_id Post ID
     * @param array $links Links data
     * @return void
     */
    private function save_links(int $post_id, array $links): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pearblog_content_links';

        // Delete existing links for this content
        $wpdb->delete($table_name, ['content_id' => $post_id]);

        // Insert new links
        foreach ($links as $link) {
            $wpdb->insert($table_name, [
                'content_id' => $post_id,
                'target_type' => $link['type'],
                'target_id' => $link['target_id'],
                'link_text' => $link['text'],
                'position' => $link['position'] ?? 'body',
                'created_at' => current_time('mysql')
            ]);
        }
    }

    /**
     * Get links for content
     *
     * @param int $post_id Post ID
     * @return array Links
     */
    public function get_links(int $post_id): array {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pearblog_content_links';

        $links = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE content_id = %d",
            $post_id
        ), ARRAY_A);

        return $links ?? [];
    }

    /**
     * Inject links into content HTML
     *
     * @param string $content Post content
     * @param array $links Links to inject
     * @return string Modified content
     */
    public function inject_links_into_content(string $content, array $links): string {
        if (empty($links)) {
            return $content;
        }

        // Split content into paragraphs
        $paragraphs = preg_split('/(<\/p>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE);

        if (count($paragraphs) < 3) {
            return $content; // Too short to inject
        }

        // Inject strategy:
        // - First link after introduction (paragraph 2)
        // - Subsequent links spread throughout middle section
        // - Last link before conclusion

        $injection_points = [];
        $total_paragraphs = floor(count($paragraphs) / 2); // Each paragraph is element + </p>

        if ($total_paragraphs >= 3) {
            // After intro (paragraph 1-2)
            $injection_points[] = 2;

            // Middle section
            $middle_start = 3;
            $middle_end = $total_paragraphs - 2;

            if ($middle_end > $middle_start) {
                $links_to_inject = count($links) - 2; // Minus intro and conclusion
                $interval = ($middle_end - $middle_start) / max(1, $links_to_inject);

                for ($i = 1; $i <= $links_to_inject; $i++) {
                    $point = $middle_start + floor($interval * $i);
                    $injection_points[] = $point;
                }
            }

            // Before conclusion
            if ($total_paragraphs > 4) {
                $injection_points[] = $total_paragraphs - 2;
            }
        }

        // Inject links at calculated points
        $link_index = 0;
        $result = [];

        foreach ($paragraphs as $index => $paragraph) {
            $result[] = $paragraph;

            $paragraph_num = floor($index / 2);

            if (in_array($paragraph_num, $injection_points) && $link_index < count($links)) {
                $link = $links[$link_index];

                // Create CTA box
                $cta_html = sprintf(
                    '<div class="pearblog-pt24-link" style="margin: 1.5rem 0; padding: 1rem; background: #f3f4f6; border-left: 4px solid #2563eb; border-radius: 0.5rem;">' .
                    '<p style="margin: 0; font-weight: 600;">👉 <a href="%s" style="color: #2563eb; text-decoration: none;">%s</a></p>' .
                    '</div>',
                    esc_url($link['url']),
                    esc_html($link['text'])
                );

                $result[] = $cta_html;
                $link_index++;
            }
        }

        return implode('', $result);
    }

    /**
     * Bulk link content
     *
     * @param int $batch_size Batch size
     * @return array Results
     */
    public function bulk_link_content(int $batch_size = 100): array {
        $posts = get_posts([
            'post_type' => 'post',
            'posts_per_page' => $batch_size,
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => '_pt24_linked',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);

        $results = [
            'processed' => 0,
            'linked' => 0,
            'errors' => []
        ];

        foreach ($posts as $post) {
            try {
                $links = $this->add_smart_links($post->ID);

                if (!empty($links)) {
                    update_post_meta($post->ID, '_pt24_linked', true);
                    update_post_meta($post->ID, '_pt24_link_count', count($links));
                    $results['linked']++;
                }

                $results['processed']++;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'post_id' => $post->ID,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Get linking statistics
     *
     * @return array Statistics
     */
    public function get_stats(): array {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pearblog_content_links';

        return [
            'total_content_with_links' => $wpdb->get_var(
                "SELECT COUNT(DISTINCT content_id) FROM {$table_name}"
            ),
            'total_links' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$table_name}"
            ),
            'average_links_per_content' => $wpdb->get_var(
                "SELECT AVG(link_count) FROM (
                    SELECT COUNT(*) as link_count
                    FROM {$table_name}
                    GROUP BY content_id
                ) as counts"
            ) ?? 0,
            'links_by_type' => $wpdb->get_results(
                "SELECT target_type, COUNT(*) as count
                FROM {$table_name}
                GROUP BY target_type",
                ARRAY_A
            ),
            'top_performing_links' => $this->get_top_performing_links(10),
            'total_clicks' => $wpdb->get_var(
                "SELECT SUM(click_count) FROM {$table_name}"
            ) ?? 0,
            'total_conversions' => $wpdb->get_var(
                "SELECT SUM(conversion_count) FROM {$table_name}"
            ) ?? 0
        ];
    }

    /**
     * Get top performing links by clicks
     *
     * @param int $limit Limit
     * @return array Top links
     */
    public function get_top_performing_links(int $limit = 10): array {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pearblog_content_links';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT content_id, target_type, target_id, link_text,
                    click_count, conversion_count,
                    (conversion_count / GREATEST(click_count, 1) * 100) as conversion_rate
            FROM {$table_name}
            WHERE click_count > 0
            ORDER BY click_count DESC
            LIMIT %d",
            $limit
        ), ARRAY_A);
    }

    /**
     * Track link click
     *
     * @param int $link_id Link ID
     * @return bool Success
     */
    public function track_click(int $link_id): bool {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pearblog_content_links';

        $result = $wpdb->query($wpdb->prepare(
            "UPDATE {$table_name}
            SET click_count = click_count + 1
            WHERE id = %d",
            $link_id
        ));

        return $result !== false;
    }

    /**
     * Track link conversion
     *
     * @param int $link_id Link ID
     * @return bool Success
     */
    public function track_conversion(int $link_id): bool {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pearblog_content_links';

        $result = $wpdb->query($wpdb->prepare(
            "UPDATE {$table_name}
            SET conversion_count = conversion_count + 1
            WHERE id = %d",
            $link_id
        ));

        return $result !== false;
    }

    /**
     * Get link by target
     *
     * @param int $content_id Content ID
     * @param string $target_type Target type
     * @param string $target_id Target ID
     * @return array|null Link data
     */
    public function get_link_by_target(int $content_id, string $target_type, string $target_id): ?array {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pearblog_content_links';

        $link = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name}
            WHERE content_id = %d
            AND target_type = %s
            AND target_id = %s
            LIMIT 1",
            $content_id,
            $target_type,
            $target_id
        ), ARRAY_A);

        return $link ?: null;
    }

    /**
     * Register WordPress hooks for automatic link injection
     *
     * @return void
     */
    public function register_hooks(): void {
        // Automatically inject links into content display
        add_filter('the_content', [$this, 'filter_content'], 20);

        // Track link clicks via AJAX
        add_action('wp_ajax_pearblog_track_link_click', [$this, 'ajax_track_click']);
        add_action('wp_ajax_nopriv_pearblog_track_link_click', [$this, 'ajax_track_click']);
    }

    /**
     * Filter content to inject PT24 links
     *
     * @param string $content Post content
     * @return string Modified content
     */
    public function filter_content(string $content): string {
        // Only apply on single post pages
        if (!is_singular('post')) {
            return $content;
        }

        global $post;

        if (!$post) {
            return $content;
        }

        // Check if PT24 integration is enabled
        if (!get_option('pearblog_pt24_integration_enabled', false)) {
            return $content;
        }

        // Check if this post already has links injected
        $has_links = get_post_meta($post->ID, '_pt24_linked', true);

        if (!$has_links) {
            return $content;
        }

        // Get links for this post
        $links = $this->get_links($post->ID);

        if (empty($links)) {
            return $content;
        }

        // Inject links into content
        return $this->inject_links_into_content($content, $links);
    }

    /**
     * AJAX handler for tracking link clicks
     *
     * @return void
     */
    public function ajax_track_click(): void {
        // Verify nonce
        check_ajax_referer('pearblog_pt24_track', 'nonce');

        $link_id = isset($_POST['link_id']) ? intval($_POST['link_id']) : 0;

        if (!$link_id) {
            wp_send_json_error(['message' => 'Invalid link ID']);
            return;
        }

        $success = $this->track_click($link_id);

        if ($success) {
            wp_send_json_success(['message' => 'Click tracked']);
        } else {
            wp_send_json_error(['message' => 'Failed to track click']);
        }
    }
}

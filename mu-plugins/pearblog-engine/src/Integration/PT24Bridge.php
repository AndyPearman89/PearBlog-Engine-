<?php
/**
 * PT24 Bridge
 *
 * Main integration controller between PearBlog Engine and PT24 platform
 *
 * @package PearBlogEngine
 * @subpackage Integration
 */

namespace PearBlogEngine\Integration;

use PearBlogEngine\Database\PT24IntegrationSchema;

class PT24Bridge {

    /**
     * @var PT24IntegrationSchema Database schema handler
     */
    private $schema;

    /**
     * @var bool Integration enabled status
     */
    private $enabled;

    /**
     * Constructor
     */
    public function __construct() {
        $this->schema = new PT24IntegrationSchema();
        $this->enabled = get_option('pearblog_pt24_integration_enabled', false);
    }

    /**
     * Initialize the integration
     *
     * @return void
     */
    public function init(): void {
        if (!$this->enabled) {
            return;
        }

        // Hook into WordPress
        add_action('init', [$this, 'register_hooks']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', [$this, 'add_admin_menu']);
            add_action('admin_init', [$this, 'register_settings']);
        }
    }

    /**
     * Register WordPress hooks
     *
     * @return void
     */
    public function register_hooks(): void {
        // Content lifecycle hooks
        add_action('publish_post', [$this, 'on_post_published'], 10, 2);
        add_action('save_post', [$this, 'on_post_saved'], 10, 3);
        add_action('delete_post', [$this, 'on_post_deleted'], 10, 1);

        // Lead lifecycle hooks
        add_action('pearblog_lead_created', [$this, 'on_lead_created'], 10, 1);

        // Asset loading hooks
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // Initialize ContentLinker hooks
        $content_linker = new ContentLinker();
        $content_linker->register_hooks();
    }

    /**
     * Enqueue frontend CSS and JavaScript assets
     *
     * @return void
     */
    public function enqueue_frontend_assets(): void {
        // Only load on single posts
        if (!is_singular('post')) {
            return;
        }

        $plugin_url = plugin_dir_url(dirname(dirname(__FILE__)));
        $plugin_path = plugin_dir_path(dirname(dirname(__FILE__)));

        // Enqueue CTA components CSS
        wp_enqueue_style(
            'pearblog-pt24-cta-components',
            $plugin_url . 'assets/css/pt24-cta-components.css',
            [],
            filemtime($plugin_path . 'assets/css/pt24-cta-components.css')
        );

        // Enqueue CTA tracking JavaScript
        wp_enqueue_script(
            'pearblog-pt24-cta-tracking',
            $plugin_url . 'assets/js/pt24-cta-tracking.js',
            [],
            filemtime($plugin_path . 'assets/js/pt24-cta-tracking.js'),
            true
        );

        // Localize script with WordPress REST API settings
        wp_localize_script('pearblog-pt24-cta-tracking', 'wpApiSettings', [
            'root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest')
        ]);

        // Add post ID to body for JavaScript tracking
        add_filter('body_class', function($classes) {
            global $post;
            if ($post) {
                return array_merge($classes, ['post-id-' . $post->ID]);
            }
            return $classes;
        });
    }

    /**
     * Register REST API routes
     *
     * @return void
     */
    public function register_rest_routes(): void {
        // Integration status endpoint
        register_rest_route('pearblog/v1', '/pt24/status', [
            'methods' => 'GET',
            'callback' => [$this, 'get_integration_status'],
            'permission_callback' => [$this, 'check_admin_permission']
        ]);

        // Get related content endpoint
        register_rest_route('pearblog/v1', '/pt24/related-content', [
            'methods' => 'GET',
            'callback' => [$this, 'get_related_content'],
            'permission_callback' => '__return_true',
            'args' => [
                'service' => [
                    'required' => true,
                    'type' => 'string'
                ],
                'city' => [
                    'required' => false,
                    'type' => 'string'
                ],
                'limit' => [
                    'default' => 3,
                    'type' => 'integer'
                ]
            ]
        ]);

        // Track conversion endpoint
        register_rest_route('pearblog/v1', '/pt24/track-conversion', [
            'methods' => 'POST',
            'callback' => [$this, 'track_conversion'],
            'permission_callback' => '__return_true',
            'args' => [
                'content_id' => [
                    'required' => true,
                    'type' => 'integer'
                ],
                'landing_id' => [
                    'required' => false,
                    'type' => 'integer'
                ],
                'event_type' => [
                    'required' => true,
                    'type' => 'string',
                    'enum' => ['click', 'view', 'lead']
                ]
            ]
        ]);

        // Track link click endpoint
        register_rest_route('pearblog/v1', '/pt24/track-click', [
            'methods' => 'POST',
            'callback' => [$this, 'track_link_click'],
            'permission_callback' => '__return_true',
            'args' => [
                'content_id' => [
                    'required' => true,
                    'type' => 'integer'
                ],
                'link_type' => [
                    'required' => true,
                    'type' => 'string'
                ],
                'link_url' => [
                    'required' => true,
                    'type' => 'string'
                ]
            ]
        ]);

        // Track pageview endpoint
        register_rest_route('pearblog/v1', '/pt24/track-pageview', [
            'methods' => 'POST',
            'callback' => [$this, 'track_pageview'],
            'permission_callback' => '__return_true',
            'args' => [
                'post_id' => [
                    'required' => true,
                    'type' => 'integer'
                ],
                'pageviews' => [
                    'required' => true,
                    'type' => 'integer'
                ],
                'funnel_stage' => [
                    'required' => true,
                    'type' => 'string'
                ]
            ]
        ]);
    }

    /**
     * Get integration status
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response
     */
    public function get_integration_status(\WP_REST_Request $request): \WP_REST_Response {
        $status = [
            'enabled' => $this->enabled,
            'schema' => $this->schema->get_status(),
            'stats' => $this->get_integration_stats()
        ];

        return new \WP_REST_Response($status, 200);
    }

    /**
     * Get related content for PT24 landing pages
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response
     */
    public function get_related_content(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;

        $service = sanitize_text_field($request->get_param('service'));
        $city = sanitize_text_field($request->get_param('city'));
        $limit = intval($request->get_param('limit'));

        $table_name = $wpdb->prefix . 'pearblog_content_meta';

        // Build query
        $query = "
            SELECT p.ID, p.post_title, p.post_excerpt, p.guid, cm.seo_score
            FROM {$wpdb->posts} p
            INNER JOIN {$table_name} cm ON p.ID = cm.post_id
            WHERE cm.category_id = %s
            AND p.post_status = 'publish'
        ";

        $params = [$service];

        if ($city) {
            $query .= " AND (cm.city_id = %s OR cm.city_id IS NULL)";
            $params[] = $city;
        }

        $query .= " ORDER BY cm.seo_score DESC, p.post_date DESC LIMIT %d";
        $params[] = $limit;

        $articles = $wpdb->get_results($wpdb->prepare($query, ...$params));

        $formatted = array_map(function($article) {
            return [
                'id' => $article->ID,
                'title' => $article->post_title,
                'excerpt' => $article->post_excerpt,
                'url' => get_permalink($article->ID),
                'seo_score' => $article->seo_score ?? 0
            ];
        }, $articles);

        return new \WP_REST_Response([
            'articles' => $formatted,
            'count' => count($formatted)
        ], 200);
    }

    /**
     * Track conversion event
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response
     */
    public function track_conversion(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;

        $content_id = intval($request->get_param('content_id'));
        $landing_id = intval($request->get_param('landing_id'));
        $event_type = sanitize_text_field($request->get_param('event_type'));

        $table_name = $wpdb->prefix . 'pearblog_content_links';

        // Update click or conversion count
        if ($event_type === 'click') {
            $wpdb->query($wpdb->prepare("
                UPDATE {$table_name}
                SET click_count = click_count + 1
                WHERE content_id = %d
            ", $content_id));
        } elseif ($event_type === 'lead') {
            $wpdb->query($wpdb->prepare("
                UPDATE {$table_name}
                SET conversion_count = conversion_count + 1
                WHERE content_id = %d
            ", $content_id));
        }

        return new \WP_REST_Response([
            'success' => true,
            'event_type' => $event_type
        ], 200);
    }

    /**
     * Track link click
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response
     */
    public function track_link_click(\WP_REST_Request $request): \WP_REST_Response {
        $content_id = intval($request->get_param('content_id'));
        $link_type = sanitize_text_field($request->get_param('link_type'));
        $link_url = esc_url_raw($request->get_param('link_url'));

        $linker = new ContentLinker();

        // Extract target_id from URL or link type
        $target_id = $this->extract_target_id_from_url($link_url, $link_type);

        // Get link from database
        $link = $linker->get_link_by_target($content_id, $link_type, $target_id);

        if ($link && isset($link['id'])) {
            $linker->track_click(intval($link['id']));

            return new \WP_REST_Response([
                'success' => true,
                'link_id' => $link['id'],
                'clicks' => intval($link['click_count']) + 1
            ], 200);
        }

        return new \WP_REST_Response([
            'success' => false,
            'message' => 'Link not found'
        ], 404);
    }

    /**
     * Track pageview
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response
     */
    public function track_pageview(\WP_REST_Request $request): \WP_REST_Response {
        $post_id = intval($request->get_param('post_id'));
        $pageviews = intval($request->get_param('pageviews'));
        $funnel_stage = sanitize_text_field($request->get_param('funnel_stage'));

        // Store pageview data (could be extended to store in database)
        do_action('pearblog_pt24_pageview_tracked', $post_id, $pageviews, $funnel_stage);

        return new \WP_REST_Response([
            'success' => true,
            'post_id' => $post_id,
            'pageviews' => $pageviews,
            'funnel_stage' => $funnel_stage
        ], 200);
    }

    /**
     * Extract target ID from URL
     *
     * @param string $url URL
     * @param string $link_type Link type
     * @return string Target ID
     */
    private function extract_target_id_from_url(string $url, string $link_type): string {
        // Parse URL to extract target ID
        $path = parse_url($url, PHP_URL_PATH);
        $parts = array_filter(explode('/', $path));

        if ($link_type === 'category' && count($parts) >= 1) {
            return $parts[0];
        }

        if ($link_type === 'city' && count($parts) >= 2) {
            return $parts[0] . '_' . $parts[1];
        }

        if ($link_type === 'listing') {
            return implode('_', $parts) . '_ranking';
        }

        return 'unknown';
    }

    /**
     * Handle post published event
     *
     * @param int $post_id Post ID
     * @param \WP_Post $post Post object
     * @return void
     */
    public function on_post_published(int $post_id, \WP_Post $post): void {
        // Trigger auto-linking when post is published
        do_action('pearblog_pt24_content_published', $post_id, $post);
    }

    /**
     * Handle post saved event
     *
     * @param int $post_id Post ID
     * @param \WP_Post $post Post object
     * @param bool $update Whether this is an update
     * @return void
     */
    public function on_post_saved(int $post_id, \WP_Post $post, bool $update): void {
        // Only process regular posts
        if ($post->post_type !== 'post') {
            return;
        }

        // Update content metadata
        $this->update_content_metadata($post_id);
    }

    /**
     * Handle post deleted event
     *
     * @param int $post_id Post ID
     * @return void
     */
    public function on_post_deleted(int $post_id): void {
        global $wpdb;

        // Clean up content metadata
        $table_name = $wpdb->prefix . 'pearblog_content_meta';
        $wpdb->delete($table_name, ['post_id' => $post_id]);

        // Clean up content links
        $table_name = $wpdb->prefix . 'pearblog_content_links';
        $wpdb->delete($table_name, ['content_id' => $post_id]);
    }

    /**
     * Handle lead created event
     *
     * @param int $lead_id Lead ID
     * @return void
     */
    public function on_lead_created(int $lead_id): void {
        // Trigger lead attribution
        do_action('pearblog_pt24_lead_attribution', $lead_id);
    }

    /**
     * Update content metadata
     *
     * @param int $post_id Post ID
     * @return void
     */
    private function update_content_metadata(int $post_id): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pearblog_content_meta';

        // Get or create metadata
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE post_id = %d",
            $post_id
        ));

        $data = [
            'post_id' => $post_id,
            'content_type' => get_post_meta($post_id, '_content_type', true),
            'category_id' => get_post_meta($post_id, '_category_id', true),
            'city_id' => get_post_meta($post_id, '_city_id', true),
            'updated_at' => current_time('mysql')
        ];

        if ($exists) {
            $wpdb->update($table_name, $data, ['post_id' => $post_id]);
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($table_name, $data);
        }
    }

    /**
     * Get integration statistics
     *
     * @return array Statistics
     */
    private function get_integration_stats(): array {
        global $wpdb;

        return [
            'total_content' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}pearblog_content_meta"
            ),
            'total_links' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}pearblog_content_links"
            ),
            'total_attributions' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}pearblog_lead_attribution"
            ),
            'total_clicks' => $wpdb->get_var(
                "SELECT SUM(click_count) FROM {$wpdb->prefix}pearblog_content_links"
            ) ?? 0,
            'total_conversions' => $wpdb->get_var(
                "SELECT SUM(conversion_count) FROM {$wpdb->prefix}pearblog_content_links"
            ) ?? 0
        ];
    }

    /**
     * Check admin permission
     *
     * @return bool
     */
    public function check_admin_permission(): bool {
        return current_user_can('manage_options');
    }

    /**
     * Add admin menu
     *
     * @return void
     */
    public function add_admin_menu(): void {
        add_submenu_page(
            'pearblog-engine',
            'PT24 Integration',
            'PT24 Integration',
            'manage_options',
            'pearblog-pt24-integration',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Register settings
     *
     * @return void
     */
    public function register_settings(): void {
        register_setting('pearblog_pt24_integration', 'pearblog_pt24_integration_enabled');
        register_setting('pearblog_pt24_integration', 'pearblog_pt24_min_links');
        register_setting('pearblog_pt24_integration', 'pearblog_pt24_max_links');
    }

    /**
     * Render admin page
     *
     * @return void
     */
    public function render_admin_page(): void {
        $status = $this->schema->get_status();
        $stats = $this->get_integration_stats();

        ?>
        <div class="wrap">
            <h1>PearBlog × PT24 Integration</h1>

            <div class="card">
                <h2>Schema Status</h2>
                <p><strong>Version:</strong> <?php echo esc_html($status['version']); ?></p>
                <p><strong>Installed:</strong> <?php echo esc_html($status['installed_at'] ?? 'Not installed'); ?></p>

                <h3>Tables:</h3>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Table</th>
                            <th>Status</th>
                            <th>Rows</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($status['tables'] as $key => $table): ?>
                        <tr>
                            <td><?php echo esc_html($table['name']); ?></td>
                            <td><?php echo $table['exists'] ? '✅ Exists' : '❌ Missing'; ?></td>
                            <td><?php echo number_format($table['row_count']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2>Integration Statistics</h2>
                <ul>
                    <li><strong>Total Content Pieces:</strong> <?php echo number_format($stats['total_content']); ?></li>
                    <li><strong>Total Links:</strong> <?php echo number_format($stats['total_links']); ?></li>
                    <li><strong>Total Clicks:</strong> <?php echo number_format($stats['total_clicks']); ?></li>
                    <li><strong>Total Conversions:</strong> <?php echo number_format($stats['total_conversions']); ?></li>
                    <li><strong>Conversion Rate:</strong> <?php
                        $ctr = $stats['total_clicks'] > 0 ?
                            ($stats['total_conversions'] / $stats['total_clicks'] * 100) : 0;
                        echo number_format($ctr, 2) . '%';
                    ?></li>
                </ul>
            </div>

            <div class="card">
                <h2>Settings</h2>
                <form method="post" action="options.php">
                    <?php settings_fields('pearblog_pt24_integration'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Enable Integration</th>
                            <td>
                                <input type="checkbox" name="pearblog_pt24_integration_enabled"
                                    value="1" <?php checked($this->enabled); ?>>
                                <p class="description">Enable PT24 integration features</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Min Links per Article</th>
                            <td>
                                <input type="number" name="pearblog_pt24_min_links"
                                    value="<?php echo esc_attr(get_option('pearblog_pt24_min_links', 3)); ?>"
                                    min="1" max="10">
                                <p class="description">Minimum PT24 links per content piece</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Max Links per Article</th>
                            <td>
                                <input type="number" name="pearblog_pt24_max_links"
                                    value="<?php echo esc_attr(get_option('pearblog_pt24_max_links', 5)); ?>"
                                    min="1" max="20">
                                <p class="description">Maximum PT24 links per content piece</p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Enable integration
     *
     * @return bool Success status
     */
    public function enable(): bool {
        update_option('pearblog_pt24_integration_enabled', true);
        $this->enabled = true;
        return true;
    }

    /**
     * Disable integration
     *
     * @return bool Success status
     */
    public function disable(): bool {
        update_option('pearblog_pt24_integration_enabled', false);
        $this->enabled = false;
        return true;
    }
}

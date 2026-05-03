<?php
/**
 * Poradnik.pro Landing V5 - Admin Dashboard
 *
 * Lead management interface, analytics, export functionality
 *
 * @package PearBlog
 * @version 5.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Landing V5 Admin Dashboard Class
 */
class PoradnikLandingV5Admin {
    /**
     * Initialize admin interface
     */
    public static function init() {
        // Add admin menu
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);

        // Handle AJAX requests
        add_action('wp_ajax_plv5_get_leads', [__CLASS__, 'ajax_get_leads']);
        add_action('wp_ajax_plv5_update_lead_status', [__CLASS__, 'ajax_update_lead_status']);
        add_action('wp_ajax_plv5_delete_lead', [__CLASS__, 'ajax_delete_lead']);
        add_action('wp_ajax_plv5_export_leads', [__CLASS__, 'ajax_export_leads']);
        add_action('wp_ajax_plv5_get_analytics', [__CLASS__, 'ajax_get_analytics']);

        // Handle CSV export download
        add_action('admin_init', [__CLASS__, 'handle_csv_export']);

        // Enqueue admin assets
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
    }

    /**
     * Add admin menu page
     */
    public static function add_admin_menu() {
        add_menu_page(
            'Landing V5 Leads',
            'Landing Leads',
            'manage_options',
            'poradnik-landing-leads',
            [__CLASS__, 'render_dashboard_page'],
            'dashicons-email',
            26
        );

        add_submenu_page(
            'poradnik-landing-leads',
            'Analytics',
            'Analytics',
            'manage_options',
            'poradnik-landing-analytics',
            [__CLASS__, 'render_analytics_page']
        );

        add_submenu_page(
            'poradnik-landing-leads',
            'Settings',
            'Settings',
            'manage_options',
            'poradnik-landing-settings',
            [__CLASS__, 'render_settings_page']
        );
    }

    /**
     * Enqueue admin assets
     */
    public static function enqueue_admin_assets($hook) {
        if (strpos($hook, 'poradnik-landing') === false) {
            return;
        }

        // Enqueue Chart.js for analytics
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
            [],
            '3.9.1',
            true
        );

        // Enqueue admin styles
        wp_add_inline_style('wp-admin', self::get_admin_styles());

        // Enqueue admin scripts
        wp_add_inline_script('jquery', self::get_admin_scripts());

        // Localize script
        wp_localize_script('jquery', 'plv5AdminData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('plv5_admin_nonce'),
            'exportUrl' => admin_url('admin.php?page=poradnik-landing-leads&action=export_csv'),
            'exportNonce' => wp_create_nonce('plv5_export'),
        ]);
    }

    /**
     * Render dashboard page
     */
    public static function render_dashboard_page() {
        ?>
        <div class="wrap plv5-admin-dashboard">
            <h1>
                Landing V5 - Lead Management
                <a href="#" class="page-title-action" id="plv5-export-csv">Export CSV</a>
                <a href="#" class="page-title-action" id="plv5-refresh-leads">Refresh</a>
            </h1>

            <!-- Summary Cards -->
            <div class="plv5-summary-cards">
                <div class="plv5-card">
                    <div class="plv5-card-icon">📊</div>
                    <div class="plv5-card-content">
                        <div class="plv5-card-value" id="total-leads">0</div>
                        <div class="plv5-card-label">Total Leads</div>
                    </div>
                </div>

                <div class="plv5-card">
                    <div class="plv5-card-icon">🆕</div>
                    <div class="plv5-card-content">
                        <div class="plv5-card-value" id="new-leads">0</div>
                        <div class="plv5-card-label">New (Uncontacted)</div>
                    </div>
                </div>

                <div class="plv5-card">
                    <div class="plv5-card-icon">📧</div>
                    <div class="plv5-card-content">
                        <div class="plv5-card-value" id="contacted-leads">0</div>
                        <div class="plv5-card-label">Contacted</div>
                    </div>
                </div>

                <div class="plv5-card">
                    <div class="plv5-card-icon">✅</div>
                    <div class="plv5-card-content">
                        <div class="plv5-card-value" id="converted-leads">0</div>
                        <div class="plv5-card-label">Converted</div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="plv5-filters">
                <select id="plv5-filter-status">
                    <option value="">All Statuses</option>
                    <option value="new">New</option>
                    <option value="contacted">Contacted</option>
                    <option value="converted">Converted</option>
                    <option value="rejected">Rejected</option>
                </select>

                <select id="plv5-filter-source">
                    <option value="">All Sources</option>
                    <option value="hero">Hero Form</option>
                    <option value="cta">CTA Form</option>
                </select>

                <input type="date" id="plv5-filter-date-from" placeholder="From Date">
                <input type="date" id="plv5-filter-date-to" placeholder="To Date">

                <button type="button" class="button" id="plv5-apply-filters">Apply Filters</button>
                <button type="button" class="button" id="plv5-clear-filters">Clear</button>
            </div>

            <!-- Leads Table -->
            <div class="plv5-table-container">
                <table class="wp-list-table widefat fixed striped" id="plv5-leads-table">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="15%">Service</th>
                            <th width="15%">Email</th>
                            <th width="10%">Source</th>
                            <th width="10%">Status</th>
                            <th width="15%">Date</th>
                            <th width="12%">IP Address</th>
                            <th width="18%">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="plv5-leads-tbody">
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                <span class="spinner is-active" style="float: none; margin: 0;"></span>
                                Loading leads...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="plv5-pagination">
                <button class="button" id="plv5-prev-page" disabled>Previous</button>
                <span id="plv5-page-info">Page 1 of 1</span>
                <button class="button" id="plv5-next-page" disabled>Next</button>
            </div>
        </div>

        <!-- Lead Details Modal -->
        <div id="plv5-lead-modal" class="plv5-modal" style="display: none;">
            <div class="plv5-modal-content">
                <span class="plv5-modal-close">&times;</span>
                <h2>Lead Details</h2>
                <div id="plv5-lead-details"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Render analytics page
     */
    public static function render_analytics_page() {
        ?>
        <div class="wrap plv5-admin-analytics">
            <h1>Landing V5 - Analytics Dashboard</h1>

            <!-- Date Range Selector -->
            <div class="plv5-date-range">
                <label>
                    <input type="radio" name="dateRange" value="7" checked> Last 7 Days
                </label>
                <label>
                    <input type="radio" name="dateRange" value="30"> Last 30 Days
                </label>
                <label>
                    <input type="radio" name="dateRange" value="90"> Last 90 Days
                </label>
                <label>
                    <input type="radio" name="dateRange" value="custom"> Custom Range
                </label>
                <input type="date" id="custom-from" style="display: none;">
                <input type="date" id="custom-to" style="display: none;">
            </div>

            <!-- Charts -->
            <div class="plv5-charts-grid">
                <div class="plv5-chart-card">
                    <h3>Leads Over Time</h3>
                    <canvas id="leads-timeline-chart"></canvas>
                </div>

                <div class="plv5-chart-card">
                    <h3>Lead Status Distribution</h3>
                    <canvas id="status-pie-chart"></canvas>
                </div>

                <div class="plv5-chart-card">
                    <h3>Leads by Source</h3>
                    <canvas id="source-bar-chart"></canvas>
                </div>

                <div class="plv5-chart-card">
                    <h3>A/B Variant Performance</h3>
                    <canvas id="ab-variant-chart"></canvas>
                </div>

                <div class="plv5-chart-card">
                    <h3>Industry Performance</h3>
                    <canvas id="industry-chart"></canvas>
                </div>

                <div class="plv5-chart-card">
                    <h3>Conversion Funnel</h3>
                    <canvas id="funnel-chart"></canvas>
                </div>
            </div>

            <!-- Stats Table -->
            <div class="plv5-stats-table">
                <h3>UTM Performance</h3>
                <table class="wp-list-table widefat">
                    <thead>
                        <tr>
                            <th>Source</th>
                            <th>Medium</th>
                            <th>Campaign</th>
                            <th>Leads</th>
                            <th>Conversion Rate</th>
                        </tr>
                    </thead>
                    <tbody id="utm-stats-tbody">
                        <tr><td colspan="5" style="text-align: center;">Loading...</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="plv5-stats-table">
                <h3>A/B Variants Table</h3>
                <table class="wp-list-table widefat">
                    <thead>
                        <tr>
                            <th>Variant</th>
                            <th>Leads</th>
                            <th>Converted</th>
                            <th>Conversion Rate</th>
                        </tr>
                    </thead>
                    <tbody id="ab-stats-tbody">
                        <tr><td colspan="4" style="text-align: center;">Loading...</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="plv5-stats-table">
                <h3>Recent Leads Snapshot</h3>
                <table class="wp-list-table widefat">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Service</th>
                            <th>Variant</th>
                            <th>Industry</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody id="recent-leads-tbody">
                        <tr><td colspan="7" style="text-align: center;">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Render settings page
     */
    public static function render_settings_page() {
        // Save settings
        if (isset($_POST['plv5_save_settings'])) {
            check_admin_referer('plv5_settings');

            update_option('plv5_hero_title', sanitize_text_field($_POST['plv5_hero_title']));
            update_option('plv5_hero_subtitle', sanitize_text_field($_POST['plv5_hero_subtitle']));
            update_option('plv5_admin_email', sanitize_email($_POST['plv5_admin_email']));
            update_option('plv5_enable_user_emails', isset($_POST['plv5_enable_user_emails']));

            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }

        $hero_title = get_option('plv5_hero_title', 'Znajdź idealnego wykonawcę w 60 sekund');
        $hero_subtitle = get_option('plv5_hero_subtitle', 'Porównaj oferty, sprawdź opinie i podejmij najlepszą decyzję. Za darmo i bez zobowiązań.');
        $admin_email = get_option('plv5_admin_email', get_option('admin_email'));
        $enable_user_emails = get_option('plv5_enable_user_emails', true);
        ?>
        <div class="wrap">
            <h1>Landing V5 - Settings</h1>

            <form method="post" action="">
                <?php wp_nonce_field('plv5_settings'); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="plv5_hero_title">Hero Title</label></th>
                        <td>
                            <input type="text" id="plv5_hero_title" name="plv5_hero_title"
                                   value="<?php echo esc_attr($hero_title); ?>"
                                   class="regular-text">
                        </td>
                    </tr>

                    <tr>
                        <th><label for="plv5_hero_subtitle">Hero Subtitle</label></th>
                        <td>
                            <textarea id="plv5_hero_subtitle" name="plv5_hero_subtitle"
                                      rows="3" class="large-text"><?php echo esc_textarea($hero_subtitle); ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="plv5_admin_email">Admin Notification Email</label></th>
                        <td>
                            <input type="email" id="plv5_admin_email" name="plv5_admin_email"
                                   value="<?php echo esc_attr($admin_email); ?>"
                                   class="regular-text">
                            <p class="description">Email address to receive new lead notifications</p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="plv5_enable_user_emails">User Confirmation Emails</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="plv5_enable_user_emails" name="plv5_enable_user_emails"
                                       <?php checked($enable_user_emails); ?>>
                                Send confirmation emails to users
                            </label>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="plv5_save_settings" class="button button-primary" value="Save Settings">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * AJAX: Get leads
     */
    public static function ajax_get_leads() {
        check_ajax_referer('plv5_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'poradnik_leads';

        // Get filters
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : '';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        // Build query
        $where = ['1=1'];
        $where_values = [];

        if ($status) {
            $where[] = 'status = %s';
            $where_values[] = $status;
        }

        if ($source) {
            $where[] = 'source = %s';
            $where_values[] = $source;
        }

        if ($date_from) {
            $where[] = 'DATE(created_at) >= %s';
            $where_values[] = $date_from;
        }

        if ($date_to) {
            $where[] = 'DATE(created_at) <= %s';
            $where_values[] = $date_to;
        }

        $where_clause = implode(' AND ', $where);

        // Get total count
        $total_query = "SELECT COUNT(*) FROM $table WHERE $where_clause";
        if (!empty($where_values)) {
            $total_query = $wpdb->prepare($total_query, $where_values);
        }
        $total = $wpdb->get_var($total_query);

        // Get leads
        $leads_query = "SELECT * FROM $table WHERE $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $query_values = array_merge($where_values, [$per_page, $offset]);
        $leads = $wpdb->get_results($wpdb->prepare($leads_query, $query_values));

        // Get summary stats
        $stats = $wpdb->get_row("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new,
                SUM(CASE WHEN status = 'contacted' THEN 1 ELSE 0 END) as contacted,
                SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted
            FROM $table
        ");

        wp_send_json_success([
            'leads' => $leads,
            'total' => $total,
            'pages' => ceil($total / $per_page),
            'current_page' => $page,
            'stats' => $stats
        ]);
    }

    /**
     * AJAX: Update lead status
     */
    public static function ajax_update_lead_status() {
        check_ajax_referer('plv5_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $lead_id = isset($_POST['lead_id']) ? absint($_POST['lead_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        if (!$lead_id || !in_array($status, ['new', 'contacted', 'converted', 'rejected'])) {
            wp_send_json_error(['message' => 'Invalid parameters']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'poradnik_leads';

        $result = $wpdb->update(
            $table,
            ['status' => $status],
            ['id' => $lead_id],
            ['%s'],
            ['%d']
        );

        if ($result !== false) {
            wp_send_json_success(['message' => 'Status updated']);
        } else {
            wp_send_json_error(['message' => 'Update failed']);
        }
    }

    /**
     * AJAX: Delete lead
     */
    public static function ajax_delete_lead() {
        check_ajax_referer('plv5_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $lead_id = isset($_POST['lead_id']) ? absint($_POST['lead_id']) : 0;

        if (!$lead_id) {
            wp_send_json_error(['message' => 'Invalid lead ID']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'poradnik_leads';

        $result = $wpdb->delete($table, ['id' => $lead_id], ['%d']);

        if ($result) {
            wp_send_json_success(['message' => 'Lead deleted']);
        } else {
            wp_send_json_error(['message' => 'Delete failed']);
        }
    }

    /**
     * AJAX: Export leads
     */
    public static function ajax_export_leads() {
        check_ajax_referer('plv5_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        // Set export flag in transient
        set_transient('plv5_export_' . get_current_user_id(), true, 60);

        wp_send_json_success([
            'message' => 'Export ready',
            'download_url' => admin_url('admin.php?page=poradnik-landing-leads&action=export_csv&nonce=' . wp_create_nonce('plv5_export'))
        ]);
    }

    /**
     * Handle CSV export download
     */
    public static function handle_csv_export() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'poradnik-landing-leads') {
            return;
        }

        if (!isset($_GET['action']) || $_GET['action'] !== 'export_csv') {
            return;
        }

        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'plv5_export')) {
            wp_die('Invalid nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'poradnik_leads';
        $leads = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC", ARRAY_A);

        // Set headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=landing-v5-leads-' . date('Y-m-d') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output CSV
        $output = fopen('php://output', 'w');

        // UTF-8 BOM for Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Headers
        fputcsv($output, [
            'ID',
            'Service',
            'Email',
            'Source',
            'Status',
            'IP Address',
            'User Agent',
            'UTM Source',
            'UTM Medium',
            'UTM Campaign',
            'Created At'
        ]);

        // Data
        foreach ($leads as $lead) {
            $utm = json_decode($lead['utm_data'], true);
            fputcsv($output, [
                $lead['id'],
                $lead['service'],
                $lead['email'],
                $lead['source'],
                $lead['status'],
                $lead['ip_address'],
                $lead['user_agent'],
                $utm['source'] ?? '',
                $utm['medium'] ?? '',
                $utm['campaign'] ?? '',
                $lead['created_at']
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * AJAX: Get analytics
     */
    public static function ajax_get_analytics() {
        check_ajax_referer('plv5_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'poradnik_leads';

        $days = isset($_POST['days']) ? absint($_POST['days']) : 7;
        $date_from = isset($_POST['date_from']) ? sanitize_text_field(wp_unslash($_POST['date_from'])) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field(wp_unslash($_POST['date_to'])) : '';

        $use_custom_range = !empty($date_from) && !empty($date_to) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to);
        if ($use_custom_range && $date_from > $date_to) {
            wp_send_json_error(['message' => 'Invalid custom date range']);
        }

        $date_sql = $use_custom_range
            ? $wpdb->prepare('created_at >= %s AND created_at < DATE_ADD(%s, INTERVAL 1 DAY)', $date_from, $date_to)
            : $wpdb->prepare('created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)', $days);

        // Leads timeline
        $timeline = $wpdb->get_results("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM $table
            WHERE $date_sql
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");

        // Status distribution (period)
        $status_dist = $wpdb->get_results("
            SELECT status, COUNT(*) as count
            FROM $table
            WHERE $date_sql
            GROUP BY status
        ");

        // Source distribution (period)
        $source_dist = $wpdb->get_results("
            SELECT source, COUNT(*) as count
            FROM $table
            WHERE $date_sql
            GROUP BY source
        ");

        // UTM performance (period)
        $utm_rows = $wpdb->get_results("
            SELECT utm_data, status
            FROM $table
            WHERE $date_sql
              AND utm_data IS NOT NULL
              AND utm_data != ''
        ");

        $utm_stats = [];
        foreach ($utm_rows as $row) {
            $utm = json_decode($row->utm_data, true);
            if (!is_array($utm)) {
                continue;
            }

            $key = ($utm['source'] ?? '(none)') . '|' . ($utm['medium'] ?? '(none)') . '|' . ($utm['campaign'] ?? '(none)');
            if (!isset($utm_stats[$key])) {
                $utm_stats[$key] = [
                    'source' => $utm['source'] ?? '(none)',
                    'medium' => $utm['medium'] ?? '(none)',
                    'campaign' => $utm['campaign'] ?? '(none)',
                    'leads' => 0,
                    'conversions' => 0,
                ];
            }

            $utm_stats[$key]['leads']++;
            if ($row->status === 'converted') {
                $utm_stats[$key]['conversions']++;
            }
        }

        $utm_performance = array_values($utm_stats);
        usort($utm_performance, static function($a, $b) {
            return $b['leads'] <=> $a['leads'];
        });

        // A/B and industry analytics from lead_meta (period)
        $meta_rows = $wpdb->get_results("
            SELECT id, service, source, status, created_at, lead_meta
            FROM $table
            WHERE $date_sql
            ORDER BY created_at DESC
        ");

        $ab_stats = [
            'a' => ['variant' => 'a', 'leads' => 0, 'converted' => 0],
            'b' => ['variant' => 'b', 'leads' => 0, 'converted' => 0],
        ];
        $industry_stats = [];
        $recent_leads = [];

        foreach ($meta_rows as $row) {
            $meta = self::decode_json_assoc($row->lead_meta);
            $variant = isset($meta['ab_variant']) && in_array($meta['ab_variant'], ['a', 'b'], true)
                ? $meta['ab_variant']
                : 'a';
            $industry = !empty($meta['industry']) ? sanitize_key($meta['industry']) : 'general';

            if (!isset($ab_stats[$variant])) {
                $ab_stats[$variant] = ['variant' => $variant, 'leads' => 0, 'converted' => 0];
            }
            $ab_stats[$variant]['leads']++;
            if ($row->status === 'converted') {
                $ab_stats[$variant]['converted']++;
            }

            if (!isset($industry_stats[$industry])) {
                $industry_stats[$industry] = [
                    'industry' => $industry,
                    'leads' => 0,
                    'converted' => 0,
                ];
            }
            $industry_stats[$industry]['leads']++;
            if ($row->status === 'converted') {
                $industry_stats[$industry]['converted']++;
            }

            if (count($recent_leads) < 15) {
                $recent_leads[] = [
                    'id' => (int) $row->id,
                    'service' => $row->service,
                    'source' => $row->source,
                    'status' => $row->status,
                    'created_at' => $row->created_at,
                    'variant' => $variant,
                    'industry' => $industry,
                ];
            }
        }

        $ab_distribution = array_values($ab_stats);
        usort($ab_distribution, static function($a, $b) {
            return strcmp($a['variant'], $b['variant']);
        });

        $industry_distribution = array_values($industry_stats);
        usort($industry_distribution, static function($a, $b) {
            return $b['leads'] <=> $a['leads'];
        });

        wp_send_json_success([
            'timeline' => $timeline,
            'status_dist' => $status_dist,
            'source_dist' => $source_dist,
            'utm_performance' => $utm_performance,
            'ab_distribution' => $ab_distribution,
            'industry_distribution' => $industry_distribution,
            'recent_leads' => $recent_leads,
        ]);
    }

    /**
     * Decode JSON string into associative array
     *
     * @param string|null $json Raw JSON string
     * @return array
     */
    private static function decode_json_assoc($json) {
        if (empty($json) || !is_string($json)) {
            return [];
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get admin styles
     */
    private static function get_admin_styles() {
        return "
        .plv5-admin-dashboard, .plv5-admin-analytics {
            max-width: 1400px;
        }

        .plv5-summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .plv5-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .plv5-card-icon {
            font-size: 32px;
        }

        .plv5-card-value {
            font-size: 32px;
            font-weight: 600;
            color: #1d2327;
        }

        .plv5-card-label {
            font-size: 13px;
            color: #646970;
        }

        .plv5-filters {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .plv5-filters select,
        .plv5-filters input[type='date'] {
            height: 32px;
        }

        .plv5-table-container {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin: 20px 0;
        }

        .plv5-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin: 20px 0;
        }

        .plv5-modal {
            display: none;
            position: fixed;
            z-index: 999999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }

        .plv5-modal-content {
            background: #fff;
            margin: 5% auto;
            padding: 30px;
            border: 1px solid #888;
            width: 80%;
            max-width: 800px;
            border-radius: 4px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .plv5-modal-close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .plv5-modal-close:hover {
            color: #000;
        }

        .plv5-charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .plv5-chart-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }

        .plv5-chart-card h3 {
            margin-top: 0;
        }

        .plv5-date-range {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }

        .plv5-date-range label {
            margin-right: 15px;
        }

        .plv5-stats-table {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }

        .lead-status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .lead-status-badge.new {
            background: #00a0d2;
            color: #fff;
        }

        .lead-status-badge.contacted {
            background: #46b450;
            color: #fff;
        }

        .lead-status-badge.converted {
            background: #00ba37;
            color: #fff;
        }

        .lead-status-badge.rejected {
            background: #dc3232;
            color: #fff;
        }
        ";
    }

    /**
     * Get admin scripts
     */
    private static function get_admin_scripts() {
        return "
        jQuery(document).ready(function($) {
            let currentPage = 1;
            let currentFilters = {};
            const charts = {};

            function makeConversionRate(conversions, leads) {
                if (!leads) {
                    return '0.0%';
                }

                return ((conversions / leads) * 100).toFixed(1) + '%';
            }

            function drawChart(id, config) {
                if (typeof Chart === 'undefined') {
                    return;
                }

                const canvas = document.getElementById(id);
                if (!canvas) {
                    return;
                }

                if (charts[id]) {
                    charts[id].destroy();
                }

                charts[id] = new Chart(canvas.getContext('2d'), config);
            }

            // Load leads
            function loadLeads(page = 1) {
                if (!$('#plv5-leads-tbody').length) {
                    return;
                }

                page = page || currentPage;
                $('#plv5-leads-tbody').html('<tr><td colspan=\"8\" style=\"text-align: center;\"><span class=\"spinner is-active\" style=\"float: none;\"></span> Loading...</td></tr>');

                $.post(plv5AdminData.ajaxUrl, {
                    action: 'plv5_get_leads',
                    nonce: plv5AdminData.nonce,
                    page: page,
                    ...currentFilters
                }, function(response) {
                    if (response.success) {
                        displayLeads(response.data);
                        updateStats(response.data.stats);
                        updatePagination(response.data.current_page, response.data.pages);
                        currentPage = response.data.current_page;
                    }
                });
            }

            // Load analytics
            function loadAnalytics(days = 7, customRange = null) {
                if (!$('#leads-timeline-chart').length) {
                    return;
                }

                const payload = {
                    action: 'plv5_get_analytics',
                    nonce: plv5AdminData.nonce,
                    days: days
                };

                if (customRange && customRange.from && customRange.to) {
                    payload.date_from = customRange.from;
                    payload.date_to = customRange.to;
                }

                $.post(plv5AdminData.ajaxUrl, {
                    ...payload
                }, function(response) {
                    if (!response.success) {
                        return;
                    }

                    renderAnalytics(response.data);
                });
            }

            function renderAnalytics(data) {
                drawChart('leads-timeline-chart', {
                    type: 'line',
                    data: {
                        labels: (data.timeline || []).map(row => row.date),
                        datasets: [{
                            label: 'Leads',
                            data: (data.timeline || []).map(row => Number(row.count || 0)),
                            borderColor: '#0073aa',
                            backgroundColor: 'rgba(0, 115, 170, 0.1)',
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } }
                    }
                });

                drawChart('status-pie-chart', {
                    type: 'doughnut',
                    data: {
                        labels: (data.status_dist || []).map(row => row.status),
                        datasets: [{
                            data: (data.status_dist || []).map(row => Number(row.count || 0)),
                            backgroundColor: ['#00a0d2', '#46b450', '#00ba37', '#dc3232']
                        }]
                    },
                    options: { responsive: true }
                });

                drawChart('source-bar-chart', {
                    type: 'bar',
                    data: {
                        labels: (data.source_dist || []).map(row => row.source),
                        datasets: [{
                            label: 'Leads',
                            data: (data.source_dist || []).map(row => Number(row.count || 0)),
                            backgroundColor: '#0073aa'
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } }
                    }
                });

                drawChart('ab-variant-chart', {
                    type: 'bar',
                    data: {
                        labels: (data.ab_distribution || []).map(row => ('Variant ' + String(row.variant || '').toUpperCase())),
                        datasets: [{
                            label: 'Leads',
                            data: (data.ab_distribution || []).map(row => Number(row.leads || 0)),
                            backgroundColor: ['#6f42c1', '#20c997']
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } }
                    }
                });

                drawChart('industry-chart', {
                    type: 'bar',
                    data: {
                        labels: (data.industry_distribution || []).map(row => row.industry),
                        datasets: [{
                            label: 'Leads',
                            data: (data.industry_distribution || []).map(row => Number(row.leads || 0)),
                            backgroundColor: '#f56e28'
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } }
                    }
                });

                const statusMap = {};
                (data.status_dist || []).forEach(function(row) {
                    statusMap[row.status] = Number(row.count || 0);
                });

                drawChart('funnel-chart', {
                    type: 'bar',
                    data: {
                        labels: ['New', 'Contacted', 'Converted'],
                        datasets: [{
                            label: 'Leads',
                            data: [statusMap.new || 0, statusMap.contacted || 0, statusMap.converted || 0],
                            backgroundColor: ['#00a0d2', '#46b450', '#00ba37']
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } }
                    }
                });

                const utmTbody = $('#utm-stats-tbody');
                if (utmTbody.length) {
                    utmTbody.empty();
                    if (!(data.utm_performance || []).length) {
                        utmTbody.html('<tr><td colspan=\"5\" style=\"text-align:center;\">No UTM data</td></tr>');
                    } else {
                        (data.utm_performance || []).forEach(function(row) {
                            const leads = Number(row.leads || 0);
                            const conversions = Number(row.conversions || 0);
                            const tr = $('<tr>')
                                .append('<td>' + (row.source || '(none)') + '</td>')
                                .append('<td>' + (row.medium || '(none)') + '</td>')
                                .append('<td>' + (row.campaign || '(none)') + '</td>')
                                .append('<td>' + leads + '</td>')
                                .append('<td>' + makeConversionRate(conversions, leads) + '</td>');
                            utmTbody.append(tr);
                        });
                    }
                }

                const abTbody = $('#ab-stats-tbody');
                if (abTbody.length) {
                    abTbody.empty();
                    (data.ab_distribution || []).forEach(function(row) {
                        const leads = Number(row.leads || 0);
                        const converted = Number(row.converted || 0);
                        const tr = $('<tr>')
                            .append('<td>Variant ' + String(row.variant || '').toUpperCase() + '</td>')
                            .append('<td>' + leads + '</td>')
                            .append('<td>' + converted + '</td>')
                            .append('<td>' + makeConversionRate(converted, leads) + '</td>');
                        abTbody.append(tr);
                    });
                }

                const recentTbody = $('#recent-leads-tbody');
                if (recentTbody.length) {
                    recentTbody.empty();
                    if (!(data.recent_leads || []).length) {
                        recentTbody.html('<tr><td colspan=\"7\" style=\"text-align:center;\">No data in selected range</td></tr>');
                    } else {
                        (data.recent_leads || []).forEach(function(lead) {
                            const statusBadge = '<span class=\"lead-status-badge ' + lead.status + '\">' + lead.status + '</span>';
                            const tr = $('<tr>')
                                .append('<td>' + lead.id + '</td>')
                                .append('<td>' + (lead.service || '-') + '</td>')
                                .append('<td>' + String(lead.variant || '').toUpperCase() + '</td>')
                                .append('<td>' + (lead.industry || 'general') + '</td>')
                                .append('<td>' + (lead.source || '-') + '</td>')
                                .append('<td>' + statusBadge + '</td>')
                                .append('<td>' + lead.created_at + '</td>');
                            recentTbody.append(tr);
                        });
                    }
                }
            }

            // Display leads
            function displayLeads(data) {
                const tbody = $('#plv5-leads-tbody');
                tbody.empty();

                if (data.leads.length === 0) {
                    tbody.html('<tr><td colspan=\"8\" style=\"text-align: center; padding: 40px;\">No leads found</td></tr>');
                    return;
                }

                data.leads.forEach(function(lead) {
                    const statusBadge = '<span class=\"lead-status-badge ' + lead.status + '\">' + lead.status + '</span>';
                    const row = $('<tr>')
                        .append('<td>' + lead.id + '</td>')
                        .append('<td>' + (lead.service || '-') + '</td>')
                        .append('<td>' + (lead.email || '-') + '</td>')
                        .append('<td>' + lead.source + '</td>')
                        .append('<td>' + statusBadge + '</td>')
                        .append('<td>' + lead.created_at + '</td>')
                        .append('<td>' + lead.ip_address + '</td>')
                        .append('<td>' +
                            '<button class=\"button button-small plv5-view-lead\" data-id=\"' + lead.id + '\">View</button> ' +
                            '<select class=\"plv5-update-status\" data-id=\"' + lead.id + '\">' +
                                '<option value=\"new\" ' + (lead.status === 'new' ? 'selected' : '') + '>New</option>' +
                                '<option value=\"contacted\" ' + (lead.status === 'contacted' ? 'selected' : '') + '>Contacted</option>' +
                                '<option value=\"converted\" ' + (lead.status === 'converted' ? 'selected' : '') + '>Converted</option>' +
                                '<option value=\"rejected\" ' + (lead.status === 'rejected' ? 'selected' : '') + '>Rejected</option>' +
                            '</select> ' +
                            '<button class=\"button button-small plv5-delete-lead\" data-id=\"' + lead.id + '\">Delete</button>' +
                        '</td>');
                    tbody.append(row);
                });
            }

            // Update stats
            function updateStats(stats) {
                $('#total-leads').text(stats.total);
                $('#new-leads').text(stats.new);
                $('#contacted-leads').text(stats.contacted);
                $('#converted-leads').text(stats.converted);
            }

            // Update pagination
            function updatePagination(current, total) {
                $('#plv5-page-info').text('Page ' + current + ' of ' + total);
                $('#plv5-prev-page').prop('disabled', current <= 1);
                $('#plv5-next-page').prop('disabled', current >= total);
            }

            // Event listeners
            $('#plv5-refresh-leads').on('click', function(e) {
                e.preventDefault();
                loadLeads();
            });

            $('#plv5-apply-filters').on('click', function() {
                currentFilters = {
                    status: $('#plv5-filter-status').val(),
                    source: $('#plv5-filter-source').val(),
                    date_from: $('#plv5-filter-date-from').val(),
                    date_to: $('#plv5-filter-date-to').val()
                };
                loadLeads(1);
            });

            $('#plv5-clear-filters').on('click', function() {
                $('#plv5-filter-status, #plv5-filter-source').val('');
                $('#plv5-filter-date-from, #plv5-filter-date-to').val('');
                currentFilters = {};
                loadLeads(1);
            });

            $('#plv5-prev-page').on('click', function() {
                if (currentPage > 1) {
                    loadLeads(currentPage - 1);
                }
            });

            $('#plv5-next-page').on('click', function() {
                loadLeads(currentPage + 1);
            });

            $('input[name=\"dateRange\"]').on('change', function() {
                const value = $(this).val();
                if (value === 'custom') {
                    $('#custom-from, #custom-to').show();
                    return;
                }

                $('#custom-from, #custom-to').hide();
                loadAnalytics(Number(value));
            });

            $('#custom-from, #custom-to').on('change', function() {
                const selectedRange = $('input[name=\"dateRange\"]:checked').val();
                if (selectedRange !== 'custom') {
                    return;
                }

                const from = $('#custom-from').val();
                const to = $('#custom-to').val();

                if (!from || !to) {
                    return;
                }

                loadAnalytics(0, { from: from, to: to });
            });

            $(document).on('change', '.plv5-update-status', function() {
                const leadId = $(this).data('id');
                const status = $(this).val();

                $.post(plv5AdminData.ajaxUrl, {
                    action: 'plv5_update_lead_status',
                    nonce: plv5AdminData.nonce,
                    lead_id: leadId,
                    status: status
                }, function(response) {
                    if (response.success) {
                        loadLeads(currentPage);
                    } else {
                        alert('Failed to update status: ' + response.data.message);
                    }
                });
            });

            $(document).on('click', '.plv5-delete-lead', function() {
                if (!confirm('Are you sure you want to delete this lead?')) {
                    return;
                }

                const leadId = $(this).data('id');

                $.post(plv5AdminData.ajaxUrl, {
                    action: 'plv5_delete_lead',
                    nonce: plv5AdminData.nonce,
                    lead_id: leadId
                }, function(response) {
                    if (response.success) {
                        loadLeads(currentPage);
                    } else {
                        alert('Failed to delete lead: ' + response.data.message);
                    }
                });
            });

            $('#plv5-export-csv').on('click', function(e) {
                e.preventDefault();
                window.location.href = plv5AdminData.exportUrl + '&nonce=' + plv5AdminData.exportNonce;
            });

            // Initial data load by page type
            if ($('#plv5-leads-tbody').length) {
                loadLeads();
            }

            if ($('#leads-timeline-chart').length) {
                loadAnalytics(7);
            }
        });
        ";
    }
}

// Initialize
PoradnikLandingV5Admin::init();

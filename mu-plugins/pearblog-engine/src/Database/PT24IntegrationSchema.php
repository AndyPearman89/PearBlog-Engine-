<?php
/**
 * PT24 Integration Schema
 *
 * Database schema for PearBlog × PT24 integration tables
 *
 * @package PearBlogEngine
 * @subpackage Database
 */

namespace PearBlogEngine\Database;

class PT24IntegrationSchema {

    /**
     * @var string WordPress database prefix
     */
    private $prefix;

    /**
     * @var \wpdb WordPress database object
     */
    private $wpdb;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->prefix = $wpdb->prefix;
    }

    /**
     * Create all integration tables
     *
     * @return array Results of table creation
     */
    public function create_tables(): array {
        $results = [];

        $results['content_meta'] = $this->create_content_meta_table();
        $results['content_links'] = $this->create_content_links_table();
        $results['lead_attribution'] = $this->create_lead_attribution_table();

        // Update schema version
        update_option('pearblog_pt24_schema_version', '1.0.0');
        update_option('pearblog_pt24_schema_installed', current_time('mysql'));

        return $results;
    }

    /**
     * Create pearblog_content_meta table
     *
     * Stores metadata for content pieces integrated with PT24
     *
     * @return bool Success status
     */
    private function create_content_meta_table(): bool {
        $table_name = $this->prefix . 'pearblog_content_meta';

        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            content_type VARCHAR(50) DEFAULT NULL COMMENT 'article, ranking, comparison, guide',
            category_id VARCHAR(50) DEFAULT NULL COMMENT 'mechanik, hydraulik, etc.',
            city_id VARCHAR(50) DEFAULT NULL COMMENT 'warszawa, krakow, etc.',
            seo_score INT(11) DEFAULT 0,
            traffic_estimate INT(11) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_post_id (post_id),
            KEY idx_content_type (content_type),
            KEY idx_category_city (category_id, city_id)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        return $this->table_exists($table_name);
    }

    /**
     * Create pearblog_content_links table
     *
     * Tracks internal links from content to PT24 resources
     *
     * @return bool Success status
     */
    private function create_content_links_table(): bool {
        $table_name = $this->prefix . 'pearblog_content_links';

        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            content_id BIGINT(20) UNSIGNED NOT NULL,
            target_type VARCHAR(50) NOT NULL COMMENT 'category, city, listing, landing',
            target_id VARCHAR(100) NOT NULL,
            link_text VARCHAR(255) DEFAULT NULL,
            link_context TEXT DEFAULT NULL COMMENT 'surrounding text for SEO',
            position VARCHAR(50) DEFAULT NULL COMMENT 'header, body, sidebar, footer',
            click_count INT(11) DEFAULT 0,
            conversion_count INT(11) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_content (content_id),
            KEY idx_target (target_type, target_id),
            KEY idx_performance (click_count, conversion_count)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        return $this->table_exists($table_name);
    }

    /**
     * Create pearblog_lead_attribution table
     *
     * Tracks lead sources from content to final conversion
     *
     * @return bool Success status
     */
    private function create_lead_attribution_table(): bool {
        $table_name = $this->prefix . 'pearblog_lead_attribution';

        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            lead_id BIGINT(20) UNSIGNED NOT NULL,
            source_content_id BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'PearBlog article',
            source_landing_id BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'PT24 landing page',
            listing_id BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'Final listing',
            funnel_stage VARCHAR(50) DEFAULT NULL COMMENT 'awareness, consideration, decision',
            session_id VARCHAR(100) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_lead (lead_id),
            KEY idx_source_content (source_content_id),
            KEY idx_source_landing (source_landing_id),
            KEY idx_funnel_stage (funnel_stage)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        return $this->table_exists($table_name);
    }

    /**
     * Check if table exists
     *
     * @param string $table_name Full table name with prefix
     * @return bool
     */
    private function table_exists(string $table_name): bool {
        $query = $this->wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        );

        return $this->wpdb->get_var($query) === $table_name;
    }

    /**
     * Drop all integration tables (for testing/rollback)
     *
     * @return array Results of table drops
     */
    public function drop_tables(): array {
        $results = [];

        $tables = [
            'pearblog_content_meta',
            'pearblog_content_links',
            'pearblog_lead_attribution'
        ];

        foreach ($tables as $table) {
            $full_name = $this->prefix . $table;
            $this->wpdb->query("DROP TABLE IF EXISTS {$full_name}");
            $results[$table] = !$this->table_exists($full_name);
        }

        // Remove schema version
        delete_option('pearblog_pt24_schema_version');
        delete_option('pearblog_pt24_schema_installed');

        return $results;
    }

    /**
     * Get schema status
     *
     * @return array Status information
     */
    public function get_status(): array {
        $tables = [
            'content_meta' => $this->prefix . 'pearblog_content_meta',
            'content_links' => $this->prefix . 'pearblog_content_links',
            'lead_attribution' => $this->prefix . 'pearblog_lead_attribution'
        ];

        $status = [
            'version' => get_option('pearblog_pt24_schema_version', 'not_installed'),
            'installed_at' => get_option('pearblog_pt24_schema_installed', null),
            'tables' => []
        ];

        foreach ($tables as $key => $table_name) {
            $status['tables'][$key] = [
                'name' => $table_name,
                'exists' => $this->table_exists($table_name),
                'row_count' => $this->table_exists($table_name) ?
                    $this->wpdb->get_var("SELECT COUNT(*) FROM {$table_name}") : 0
            ];
        }

        return $status;
    }

    /**
     * Verify schema integrity
     *
     * @return array Validation results
     */
    public function verify_schema(): array {
        $errors = [];
        $warnings = [];

        $tables = [
            'pearblog_content_meta',
            'pearblog_content_links',
            'pearblog_lead_attribution'
        ];

        foreach ($tables as $table) {
            $full_name = $this->prefix . $table;

            if (!$this->table_exists($full_name)) {
                $errors[] = "Table {$full_name} does not exist";
                continue;
            }

            // Check required columns exist
            $columns = $this->wpdb->get_results("DESCRIBE {$full_name}", ARRAY_A);
            $column_names = array_column($columns, 'Field');

            // Verify primary key
            if (!in_array('id', $column_names)) {
                $errors[] = "Table {$full_name} missing primary key 'id'";
            }

            // Verify created_at timestamp
            if (!in_array('created_at', $column_names)) {
                $warnings[] = "Table {$full_name} missing 'created_at' timestamp";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
}

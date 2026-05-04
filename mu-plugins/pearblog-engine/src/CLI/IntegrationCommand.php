<?php
/**
 * Integration Command
 *
 * WP-CLI commands for PT24 integration management
 *
 * @package PearBlogEngine
 * @subpackage CLI
 */

namespace PearBlogEngine\CLI;

use PearBlogEngine\Database\PT24IntegrationSchema;
use PearBlogEngine\Integration\PT24Bridge;
use PearBlogEngine\Integration\ContentLinker;
use PearBlogEngine\Integration\LeadAttributor;

class IntegrationCommand {

    /**
     * @var PT24IntegrationSchema Schema handler
     */
    private $schema;

    /**
     * @var PT24Bridge Integration bridge
     */
    private $bridge;

    /**
     * @var ContentLinker Content linker
     */
    private $linker;

    /**
     * Constructor
     */
    public function __construct() {
        $this->schema = new PT24IntegrationSchema();
        $this->bridge = new PT24Bridge();
        $this->linker = new ContentLinker();
    }

    /**
     * Install PT24 integration schema
     *
     * ## EXAMPLES
     *
     *     wp pearblog integration install
     *
     * @when after_wp_load
     */
    public function install($args, $assoc_args): void {
        \WP_CLI::log('Installing PT24 integration schema...');

        $results = $this->schema->create_tables();

        foreach ($results as $table => $success) {
            if ($success) {
                \WP_CLI::success("Table {$table} created successfully");
            } else {
                \WP_CLI::error("Failed to create table {$table}");
            }
        }

        // Verify schema
        $verification = $this->schema->verify_schema();

        if ($verification['valid']) {
            \WP_CLI::success('Schema installation completed successfully!');
        } else {
            \WP_CLI::warning('Schema installed with warnings:');
            foreach ($verification['warnings'] as $warning) {
                \WP_CLI::warning("  - {$warning}");
            }
        }
    }

    /**
     * Show integration status
     *
     * ## EXAMPLES
     *
     *     wp pearblog integration status
     *
     * @when after_wp_load
     */
    public function status($args, $assoc_args): void {
        $status = $this->schema->get_status();

        \WP_CLI::line('');
        \WP_CLI::line('PT24 Integration Status');
        \WP_CLI::line('========================');
        \WP_CLI::line("Version: {$status['version']}");
        \WP_CLI::line("Installed: " . ($status['installed_at'] ?? 'Not installed'));
        \WP_CLI::line('');

        \WP_CLI::line('Tables:');
        foreach ($status['tables'] as $key => $table) {
            $status_icon = $table['exists'] ? '✓' : '✗';
            \WP_CLI::line("  {$status_icon} {$table['name']}: {$table['row_count']} rows");
        }

        \WP_CLI::line('');

        // Get integration stats if schema is installed
        if ($status['version'] !== 'not_installed') {
            $this->stats($args, $assoc_args);
        }
    }

    /**
     * Show integration statistics
     *
     * ## EXAMPLES
     *
     *     wp pearblog integration stats
     *
     * @when after_wp_load
     */
    public function stats($args, $assoc_args): void {
        global $wpdb;

        $stats = [
            'total_content' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}pearblog_content_meta"
            ) ?? 0,
            'total_links' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}pearblog_content_links"
            ) ?? 0,
            'total_attributions' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}pearblog_lead_attribution"
            ) ?? 0,
            'total_clicks' => $wpdb->get_var(
                "SELECT SUM(click_count) FROM {$wpdb->prefix}pearblog_content_links"
            ) ?? 0,
            'total_conversions' => $wpdb->get_var(
                "SELECT SUM(conversion_count) FROM {$wpdb->prefix}pearblog_content_links"
            ) ?? 0
        ];

        // Calculate CTR
        $ctr = $stats['total_clicks'] > 0 ?
            ($stats['total_conversions'] / $stats['total_clicks'] * 100) : 0;

        \WP_CLI::line('Integration Statistics');
        \WP_CLI::line('======================');
        \WP_CLI::line("Content pieces: " . number_format($stats['total_content']));
        \WP_CLI::line("Internal links: " . number_format($stats['total_links']));
        \WP_CLI::line("Lead attributions: " . number_format($stats['total_attributions']));
        \WP_CLI::line("Total clicks: " . number_format($stats['total_clicks']));
        \WP_CLI::line("Total conversions: " . number_format($stats['total_conversions']));
        \WP_CLI::line("Conversion rate: " . number_format($ctr, 2) . "%");
    }

    /**
     * Link existing content to PT24
     *
     * ## OPTIONS
     *
     * [--batch=<number>]
     * : Number of posts to process (default: 100)
     *
     * [--force]
     * : Re-link already linked content
     *
     * ## EXAMPLES
     *
     *     wp pearblog integration link-content
     *     wp pearblog integration link-content --batch=50
     *     wp pearblog integration link-content --force
     *
     * @when after_wp_load
     */
    public function link_content($args, $assoc_args): void {
        $batch = isset($assoc_args['batch']) ? intval($assoc_args['batch']) : 100;
        $force = isset($assoc_args['force']);

        \WP_CLI::log("Processing {$batch} posts...");

        $query_args = [
            'post_type' => 'post',
            'posts_per_page' => $batch,
            'post_status' => 'publish'
        ];

        if (!$force) {
            $query_args['meta_query'] = [
                [
                    'key' => '_pt24_linked',
                    'compare' => 'NOT EXISTS'
                ]
            ];
        }

        $posts = get_posts($query_args);

        if (empty($posts)) {
            \WP_CLI::warning('No posts found to link');
            return;
        }

        $progress = \WP_CLI\Utils\make_progress_bar('Linking content', count($posts));

        $linked = 0;
        $errors = 0;

        foreach ($posts as $post) {
            try {
                $links = $this->linker->add_smart_links($post->ID);

                if (!empty($links)) {
                    update_post_meta($post->ID, '_pt24_linked', true);
                    update_post_meta($post->ID, '_pt24_link_count', count($links));
                    $linked++;
                }
            } catch (\Exception $e) {
                \WP_CLI::warning("Failed to link post {$post->ID}: " . $e->getMessage());
                $errors++;
            }

            $progress->tick();
        }

        $progress->finish();

        \WP_CLI::success("Linked {$linked} posts with PT24 resources");

        if ($errors > 0) {
            \WP_CLI::warning("{$errors} posts failed to link");
        }
    }

    /**
     * Enable PT24 integration
     *
     * ## EXAMPLES
     *
     *     wp pearblog integration enable
     *
     * @when after_wp_load
     */
    public function enable($args, $assoc_args): void {
        $this->bridge->enable();
        \WP_CLI::success('PT24 integration enabled');
    }

    /**
     * Disable PT24 integration
     *
     * ## EXAMPLES
     *
     *     wp pearblog integration disable
     *
     * @when after_wp_load
     */
    public function disable($args, $assoc_args): void {
        $this->bridge->disable();
        \WP_CLI::success('PT24 integration disabled');
    }

    /**
     * Verify schema integrity
     *
     * ## EXAMPLES
     *
     *     wp pearblog integration verify
     *
     * @when after_wp_load
     */
    public function verify($args, $assoc_args): void {
        \WP_CLI::log('Verifying schema integrity...');

        $verification = $this->schema->verify_schema();

        if ($verification['valid']) {
            \WP_CLI::success('Schema is valid ✓');
        } else {
            \WP_CLI::error('Schema validation failed:');
            foreach ($verification['errors'] as $error) {
                \WP_CLI::error("  - {$error}");
            }
        }

        if (!empty($verification['warnings'])) {
            \WP_CLI::warning('Warnings:');
            foreach ($verification['warnings'] as $warning) {
                \WP_CLI::warning("  - {$warning}");
            }
        }
    }

    /**
     * Uninstall PT24 integration (drops all tables)
     *
     * ## OPTIONS
     *
     * [--yes]
     * : Skip confirmation prompt
     *
     * ## EXAMPLES
     *
     *     wp pearblog integration uninstall --yes
     *
     * @when after_wp_load
     */
    public function uninstall($args, $assoc_args): void {
        $skip_confirm = isset($assoc_args['yes']);

        if (!$skip_confirm) {
            \WP_CLI::confirm('This will DROP all PT24 integration tables and data. Continue?', $assoc_args);
        }

        \WP_CLI::log('Dropping integration tables...');

        $results = $this->schema->drop_tables();

        foreach ($results as $table => $success) {
            if ($success) {
                \WP_CLI::success("Table {$table} dropped");
            } else {
                \WP_CLI::error("Failed to drop table {$table}");
            }
        }

        \WP_CLI::success('PT24 integration uninstalled');
    }

    /**
     * Get top performing content by leads
     *
     * ## OPTIONS
     *
     * [--limit=<number>]
     * : Number of results to show (default: 10)
     *
     * [--format=<format>]
     * : Output format (table, json, csv, yaml)
     *
     * ## EXAMPLES
     *
     *     wp pearblog integration top-content
     *     wp pearblog integration top-content --limit=20
     *     wp pearblog integration top-content --format=json
     *
     * @when after_wp_load
     */
    public function top_content($args, $assoc_args): void {
        $limit = isset($assoc_args['limit']) ? intval($assoc_args['limit']) : 10;
        $format = $assoc_args['format'] ?? 'table';

        $attributor = new LeadAttributor();
        $top_content = $attributor->get_top_content_by_leads($limit);

        if (empty($top_content)) {
            \WP_CLI::warning('No attribution data available');
            return;
        }

        \WP_CLI\Utils\format_items($format, $top_content, ['content_id', 'post_title', 'lead_count', 'unique_sessions']);
    }

    /**
     * Sync content metadata
     *
     * Updates content_meta table with latest post metadata
     *
     * ## OPTIONS
     *
     * [--batch=<number>]
     * : Number of posts to process (default: 100)
     *
     * ## EXAMPLES
     *
     *     wp pearblog integration sync
     *
     * @when after_wp_load
     */
    public function sync($args, $assoc_args): void {
        $batch = isset($assoc_args['batch']) ? intval($assoc_args['batch']) : 100;

        \WP_CLI::log("Syncing metadata for {$batch} posts...");

        $posts = get_posts([
            'post_type' => 'post',
            'posts_per_page' => $batch,
            'post_status' => 'publish'
        ]);

        if (empty($posts)) {
            \WP_CLI::warning('No posts found');
            return;
        }

        $progress = \WP_CLI\Utils\make_progress_bar('Syncing metadata', count($posts));

        global $wpdb;
        $table_name = $wpdb->prefix . 'pearblog_content_meta';

        $synced = 0;

        foreach ($posts as $post) {
            $data = [
                'post_id' => $post->ID,
                'content_type' => get_post_meta($post->ID, '_content_type', true),
                'category_id' => get_post_meta($post->ID, '_category_id', true),
                'city_id' => get_post_meta($post->ID, '_city_id', true),
                'updated_at' => current_time('mysql')
            ];

            // Check if exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table_name} WHERE post_id = %d",
                $post->ID
            ));

            if ($exists) {
                $wpdb->update($table_name, $data, ['post_id' => $post->ID]);
            } else {
                $data['created_at'] = current_time('mysql');
                $wpdb->insert($table_name, $data);
            }

            $synced++;
            $progress->tick();
        }

        $progress->finish();

        \WP_CLI::success("Synced {$synced} posts");
    }
}

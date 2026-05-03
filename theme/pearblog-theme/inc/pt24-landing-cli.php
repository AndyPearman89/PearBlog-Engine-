<?php
/**
 * PT24 Landing Generator WP-CLI Commands
 *
 * Bulk generation and CSV import via command line
 *
 * @package PearBlog
 * @version 2.0.0
 */

if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

/**
 * PT24 Landing Generator CLI Commands
 */
class PT24_Landing_CLI {

    /**
     * Generate PT24 landing pages in bulk
     *
     * ## OPTIONS
     *
     * [--services=<services>]
     * : Comma-separated list of services (default: all)
     *
     * [--cities=<cities>]
     * : Comma-separated list of cities (default: all)
     *
     * [--dry-run]
     * : Show what would be generated without creating posts
     *
     * ## EXAMPLES
     *
     *     wp pt24 generate
     *     wp pt24 generate --services=hydraulik,elektryk --cities=krakow,warszawa
     *     wp pt24 generate --dry-run
     *
     * @when after_wp_load
     */
    public function generate($args, $assoc_args) {
        $services = isset($assoc_args['services'])
            ? explode(',', $assoc_args['services'])
            : array_keys(PearBlog_PT24_Landing_CPT::get_services());

        $cities = isset($assoc_args['cities'])
            ? explode(',', $assoc_args['cities'])
            : array_keys(PearBlog_PT24_Landing_CPT::get_cities());

        $dry_run = isset($assoc_args['dry-run']);

        WP_CLI::line('');
        WP_CLI::line('PT24 Landing Generator');
        WP_CLI::line('=====================');
        WP_CLI::line('');
        WP_CLI::line('Services: ' . implode(', ', $services));
        WP_CLI::line('Cities: ' . implode(', ', $cities));
        WP_CLI::line('Total combinations: ' . (count($services) * count($cities)));
        WP_CLI::line('');

        if ($dry_run) {
            WP_CLI::warning('DRY RUN MODE - No posts will be created');
            WP_CLI::line('');

            foreach ($cities as $city) {
                foreach ($services as $service) {
                    $service_name = PearBlog_PT24_Landing_CPT::get_services()[$service] ?? ucfirst($service);
                    $city_name = PearBlog_PT24_Landing_CPT::get_cities()[$city] ?? ucfirst($city);
                    WP_CLI::line("Would create: /$city/$service - $service_name $city_name");
                }
            }

            return;
        }

        // Confirm before proceeding
        WP_CLI::confirm('Generate ' . (count($services) * count($cities)) . ' landing pages?');

        // Generate
        $progress = \WP_CLI\Utils\make_progress_bar('Generating landing pages', count($services) * count($cities));

        $generated = 0;
        $errors = 0;
        $skipped = 0;

        foreach ($cities as $city) {
            foreach ($services as $service) {
                $result = PearBlog_PT24_Landing_CPT::generate_landing($service, $city);

                if ($result) {
                    if (is_numeric($result)) {
                        $generated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    $errors++;
                }

                $progress->tick();
            }
        }

        $progress->finish();

        WP_CLI::line('');
        WP_CLI::success("Generated: $generated pages");
        if ($skipped > 0) {
            WP_CLI::line("Skipped (already exist): $skipped pages");
        }
        if ($errors > 0) {
            WP_CLI::warning("Errors: $errors pages");
        }

        // Flush rewrite rules
        WP_CLI::line('');
        WP_CLI::line('Flushing rewrite rules...');
        flush_rewrite_rules();
        WP_CLI::success('Done!');
    }

    /**
     * Import PT24 landing pages from CSV
     *
     * ## OPTIONS
     *
     * <file>
     * : Path to CSV file (format: service,city)
     *
     * ## EXAMPLES
     *
     *     wp pt24 import landings.csv
     *     wp pt24 import /path/to/data.csv
     *
     * @when after_wp_load
     */
    public function import($args, $assoc_args) {
        $file_path = $args[0];

        if (!file_exists($file_path)) {
            WP_CLI::error("File not found: $file_path");
        }

        WP_CLI::line('');
        WP_CLI::line('PT24 Landing CSV Import');
        WP_CLI::line('=======================');
        WP_CLI::line('File: ' . $file_path);
        WP_CLI::line('');

        // Count rows
        $line_count = count(file($file_path));
        WP_CLI::line("Rows in CSV: $line_count");
        WP_CLI::line('');

        WP_CLI::confirm('Import landing pages from this CSV?');

        $result = PearBlog_PT24_Landing_CPT::import_csv($file_path);

        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        }

        WP_CLI::line('');
        WP_CLI::success("Generated: " . $result['total'] . " pages");

        if (!empty($result['errors'])) {
            WP_CLI::warning("Errors: " . count($result['errors']) . " rows");
            foreach ($result['errors'] as $error) {
                WP_CLI::line("  Row {$error['row']}: {$error['service']}, {$error['city']}");
            }
        }

        // Flush rewrite rules
        WP_CLI::line('');
        WP_CLI::line('Flushing rewrite rules...');
        flush_rewrite_rules();
        WP_CLI::success('Done!');
    }

    /**
     * List all PT24 landing pages
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Output format (table, csv, json)
     * ---
     * default: table
     * options:
     *   - table
     *   - csv
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *     wp pt24 list
     *     wp pt24 list --format=csv
     *
     * @when after_wp_load
     */
    public function list($args, $assoc_args) {
        $format = $assoc_args['format'] ?? 'table';

        $posts = get_posts([
            'post_type' => 'pt24_landing',
            'posts_per_page' => -1,
            'post_status' => 'any',
        ]);

        $items = [];

        foreach ($posts as $post) {
            $service = get_post_meta($post->ID, 'pt24_service', true);
            $city = get_post_meta($post->ID, 'pt24_city', true);
            $url = home_url("/$city/$service/");

            $items[] = [
                'ID' => $post->ID,
                'Service' => $service,
                'City' => $city,
                'URL' => $url,
                'Status' => $post->post_status,
            ];
        }

        if (empty($items)) {
            WP_CLI::warning('No PT24 landing pages found');
            return;
        }

        WP_CLI\Utils\format_items($format, $items, ['ID', 'Service', 'City', 'URL', 'Status']);
    }

    /**
     * Delete all PT24 landing pages
     *
     * ## OPTIONS
     *
     * [--yes]
     * : Skip confirmation
     *
     * ## EXAMPLES
     *
     *     wp pt24 delete-all
     *     wp pt24 delete-all --yes
     *
     * @when after_wp_load
     */
    public function delete_all($args, $assoc_args) {
        $posts = get_posts([
            'post_type' => 'pt24_landing',
            'posts_per_page' => -1,
            'post_status' => 'any',
        ]);

        if (empty($posts)) {
            WP_CLI::warning('No PT24 landing pages found');
            return;
        }

        WP_CLI::line('Found ' . count($posts) . ' landing pages');

        if (!isset($assoc_args['yes'])) {
            WP_CLI::confirm('Delete all PT24 landing pages?');
        }

        $progress = \WP_CLI\Utils\make_progress_bar('Deleting landing pages', count($posts));

        $deleted = 0;

        foreach ($posts as $post) {
            if (wp_delete_post($post->ID, true)) {
                $deleted++;
            }
            $progress->tick();
        }

        $progress->finish();

        WP_CLI::success("Deleted $deleted landing pages");
    }

    /**
     * Flush rewrite rules for PT24 landings
     *
     * ## EXAMPLES
     *
     *     wp pt24 flush-rewrites
     *
     * @when after_wp_load
     */
    public function flush_rewrites($args, $assoc_args) {
        flush_rewrite_rules();
        WP_CLI::success('Rewrite rules flushed');
    }
}

WP_CLI::add_command('pt24', 'PT24_Landing_CLI');

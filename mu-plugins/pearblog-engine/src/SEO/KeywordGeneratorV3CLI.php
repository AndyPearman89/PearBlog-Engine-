<?php
/**
 * SEO Keyword Generator V3 Enterprise WP-CLI Commands
 *
 * Multi-vertical keyword generation and management
 * Supports 8 verticals with 150,000+ keyword combinations
 *
 * @package PearBlogEngine
 * @subpackage SEO
 * @version 3.0.0
 */

if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

use PearBlogEngine\SEO\KeywordDatabaseV3;

/**
 * SEO Keyword Generator V3 CLI Commands
 */
class SEO_Keyword_V3_CLI {

    /**
     * Generate SEO keywords from V3 database
     *
     * ## OPTIONS
     *
     * [--city=<city>]
     * : Generate for specific city
     *
     * [--vertical=<vertical>]
     * : Generate for specific vertical (mechanik, elektryk, hydraulik, etc.)
     *
     * [--problem=<problem>]
     * : Generate for specific problem
     *
     * [--modifier=<modifier>]
     * : Include specific modifier
     *
     * [--type=<type>]
     * : Keyword type (high_intent, problem, long_tail, all)
     *
     * [--intent=<intent>]
     * : Filter by intent (transactional, commercial, informational)
     *
     * [--no-modifiers]
     * : Exclude modifier-based keywords
     *
     * [--limit=<number>]
     * : Limit number of keywords
     *
     * [--format=<format>]
     * : Output format (table, csv, json)
     * ---
     * default: table
     * ---
     *
     * ## EXAMPLES
     *
     *     # Generate all keywords
     *     wp pearblog seo-v3:keywords
     *
     *     # Generate for specific vertical
     *     wp pearblog seo-v3:keywords --vertical=elektryk
     *
     *     # Generate for city
     *     wp pearblog seo-v3:keywords --city=katowice --vertical=hydraulik
     *
     *     # Filter by intent
     *     wp pearblog seo-v3:keywords --intent=transactional --limit=100
     *
     *     # Export to CSV
     *     wp pearblog seo-v3:keywords --format=csv > keywords-v3.csv
     *
     * @when after_wp_load
     */
    public function keywords($args, $assoc_args) {
        $options = [];

        if (isset($assoc_args['city'])) {
            $options['cities'] = [$assoc_args['city']];
        }

        if (isset($assoc_args['vertical'])) {
            $options['verticals'] = [$assoc_args['vertical']];
        }

        if (isset($assoc_args['problem'])) {
            $options['problems'] = [$assoc_args['problem']];
        }

        if (isset($assoc_args['modifier'])) {
            $options['modifiers'] = [$assoc_args['modifier']];
        }

        if (isset($assoc_args['limit'])) {
            $options['limit'] = (int) $assoc_args['limit'];
        }

        if (isset($assoc_args['no-modifiers'])) {
            $options['include_modifiers'] = false;
        }

        $format = $assoc_args['format'] ?? 'table';
        $type_filter = $assoc_args['type'] ?? null;
        $intent_filter = $assoc_args['intent'] ?? null;

        WP_CLI::log("Generating SEO keywords (V3 Enterprise)...");

        $keywords = KeywordDatabaseV3::generate_keywords($options);

        // Filter by type
        if ($type_filter && $type_filter !== 'all') {
            $keywords = array_filter($keywords, function($k) use ($type_filter) {
                return $k['type'] === $type_filter;
            });
        }

        // Filter by intent
        if ($intent_filter) {
            $keywords = array_filter($keywords, function($k) use ($intent_filter) {
                return $k['intent'] === $intent_filter;
            });
        }

        if (empty($keywords)) {
            WP_CLI::warning('No keywords generated');
            return;
        }

        WP_CLI::success(sprintf('Generated %d keywords', count($keywords)));

        // Format output
        $output_items = [];
        foreach ($keywords as $keyword) {
            $output_items[] = [
                'Keyword' => $keyword['keyword'],
                'Type' => $keyword['type'],
                'City' => $keyword['city_name'],
                'Region' => $keyword['region'],
                'Vertical' => $keyword['vertical'],
                'Service' => $keyword['service_name'] ?? '-',
                'Intent' => $keyword['intent'],
                'Slug' => $keyword['slug'],
            ];
        }

        WP_CLI\Utils\format_items($format, $output_items, ['Keyword', 'Type', 'City', 'Region', 'Vertical', 'Service', 'Intent']);
    }

    /**
     * Show SEO keyword V3 statistics
     *
     * ## EXAMPLES
     *
     *     wp pearblog seo-v3:stats
     *
     * @when after_wp_load
     */
    public function stats() {
        WP_CLI::log("\n=== SEO Keyword Database V3 Enterprise Statistics ===\n");

        $stats = KeywordDatabaseV3::get_stats();

        WP_CLI::log("Data Sources:");
        WP_CLI::log("  Cities: " . $stats['cities']);
        WP_CLI::log("  Verticals: " . $stats['verticals']);
        WP_CLI::log("  Services: " . $stats['services']);
        WP_CLI::log("  Problems: " . $stats['problems']);
        WP_CLI::log("  Modifiers: " . $stats['modifiers']);
        WP_CLI::log("");

        WP_CLI::log("Keyword Combinations:");
        WP_CLI::log("  High Intent: " . number_format($stats['combinations']['high_intent']));
        WP_CLI::log("  Problem: " . number_format($stats['combinations']['problem']));
        WP_CLI::log("  Long Tail: " . number_format($stats['combinations']['long_tail']));
        WP_CLI::log("  ───────────────");
        WP_CLI::log("  Total: " . number_format($stats['combinations']['total']));
        WP_CLI::log("");

        WP_CLI::log("Scaling Formula:");
        WP_CLI::log(sprintf(
            "  %d cities × %d verticals × %d services × %d problems × %d modifiers",
            $stats['cities'],
            $stats['verticals'],
            $stats['services'],
            $stats['problems'],
            $stats['modifiers']
        ));
        WP_CLI::log("");
    }

    /**
     * Generate SEO landing pages in bulk (V3)
     *
     * ## OPTIONS
     *
     * [--city=<city>]
     * : Generate for specific city
     *
     * [--vertical=<vertical>]
     * : Generate for specific vertical
     *
     * [--batch=<number>]
     * : Number of pages to generate
     * ---
     * default: 100
     * ---
     *
     * [--dry-run]
     * : Show what would be generated without creating pages
     *
     * [--type=<type>]
     * : Page type to generate (high_intent, problem, long_tail, all)
     * ---
     * default: all
     * ---
     *
     * [--intent=<intent>]
     * : Filter by intent (transactional, commercial, informational)
     *
     * [--no-modifiers]
     * : Skip modifier-based long-tail keywords
     *
     * ## EXAMPLES
     *
     *     # Generate 100 pages
     *     wp pearblog seo-v3:generate --batch=100
     *
     *     # Generate for specific vertical
     *     wp pearblog seo-v3:generate --vertical=elektryk --batch=50
     *
     *     # Generate high intent only
     *     wp pearblog seo-v3:generate --type=high_intent --batch=20
     *
     *     # Dry run
     *     wp pearblog seo-v3:generate --batch=10 --dry-run
     *
     * @when after_wp_load
     */
    public function generate($args, $assoc_args) {
        $options = [];

        if (isset($assoc_args['city'])) {
            $options['cities'] = [$assoc_args['city']];
        }

        if (isset($assoc_args['vertical'])) {
            $options['verticals'] = [$assoc_args['vertical']];
        }

        if (isset($assoc_args['no-modifiers'])) {
            $options['include_modifiers'] = false;
        }

        $batch = (int) ($assoc_args['batch'] ?? 100);
        $dry_run = isset($assoc_args['dry-run']);
        $type_filter = $assoc_args['type'] ?? 'all';
        $intent_filter = $assoc_args['intent'] ?? null;

        $options['limit'] = $batch;

        WP_CLI::log("Generating SEO landing pages (V3 Enterprise)...");
        if ($dry_run) {
            WP_CLI::warning("DRY RUN MODE - No pages will be created");
        }

        $keywords = KeywordDatabaseV3::generate_keywords($options);

        // Filter by type
        if ($type_filter !== 'all') {
            $keywords = array_filter($keywords, function($k) use ($type_filter) {
                return $k['type'] === $type_filter;
            });
        }

        // Filter by intent
        if ($intent_filter) {
            $keywords = array_filter($keywords, function($k) use ($intent_filter) {
                return $k['intent'] === $intent_filter;
            });
        }

        if (empty($keywords)) {
            WP_CLI::warning('No keywords to generate');
            return;
        }

        WP_CLI::log(sprintf('Keywords to generate: %d', count($keywords)));

        $progress = \WP_CLI\Utils\make_progress_bar('Generating pages', count($keywords));

        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($keywords as $keyword_data) {
            if ($dry_run) {
                WP_CLI::log(sprintf(
                    "Would create: %s (%s) [%s]",
                    $keyword_data['keyword'],
                    $keyword_data['vertical'],
                    $keyword_data['intent']
                ));
                $created++;
                $progress->tick();
                continue;
            }

            // Check if page exists
            $existing = $this->find_existing_page($keyword_data);
            if ($existing) {
                $skipped++;
                $progress->tick();
                continue;
            }

            // Create the page
            $post_id = $this->create_seo_page($keyword_data);

            if ($post_id) {
                $created++;
            } else {
                $errors++;
            }

            $progress->tick();
        }

        $progress->finish();

        WP_CLI::log("");
        WP_CLI::success(sprintf('Created: %d pages', $created));
        if ($skipped > 0) {
            WP_CLI::log(sprintf('Skipped: %d pages (already exist)', $skipped));
        }
        if ($errors > 0) {
            WP_CLI::warning(sprintf('Errors: %d pages', $errors));
        }
    }

    /**
     * List available verticals
     *
     * ## EXAMPLES
     *
     *     wp pearblog seo-v3:verticals
     *
     * @when after_wp_load
     */
    public function verticals() {
        $verticals = KeywordDatabaseV3::get_verticals();

        WP_CLI::log(sprintf("\n=== Available Verticals (%d) ===\n", count($verticals)));

        $items = [];
        foreach ($verticals as $slug => $name) {
            $services = KeywordDatabaseV3::get_services($slug);
            $items[] = [
                'Slug' => $slug,
                'Name' => $name,
                'Services' => count($services),
            ];
        }

        WP_CLI\Utils\format_items('table', $items, ['Slug', 'Name', 'Services']);
    }

    /**
     * List services for vertical
     *
     * ## OPTIONS
     *
     * [<vertical>]
     * : Vertical slug
     *
     * ## EXAMPLES
     *
     *     wp pearblog seo-v3:services
     *     wp pearblog seo-v3:services elektryk
     *
     * @when after_wp_load
     */
    public function services($args) {
        $vertical = $args[0] ?? null;

        if ($vertical) {
            $services = KeywordDatabaseV3::get_services($vertical);
            WP_CLI::log(sprintf("\n=== Services for '%s' (%d) ===\n", $vertical, count($services)));

            $items = [];
            foreach ($services as $slug => $service) {
                $items[] = [
                    'Slug' => $slug,
                    'Name' => $service['name'],
                    'Price Range' => sprintf('%d - %d zł', $service['avg_price_min'], $service['avg_price_max']),
                ];
            }

            WP_CLI\Utils\format_items('table', $items, ['Slug', 'Name', 'Price Range']);
        } else {
            $all_services = KeywordDatabaseV3::get_services();
            $total = 0;
            foreach ($all_services as $services) {
                $total += count($services);
            }

            WP_CLI::log(sprintf("\n=== All Services (%d total) ===\n", $total));

            foreach ($all_services as $vertical_slug => $services) {
                WP_CLI::log(sprintf("\n%s (%d services):", $vertical_slug, count($services)));
                foreach ($services as $slug => $service) {
                    WP_CLI::log(sprintf("  - %s (%s)", $service['name'], $slug));
                }
            }
        }
    }

    /**
     * List available modifiers
     *
     * ## EXAMPLES
     *
     *     wp pearblog seo-v3:modifiers
     *
     * @when after_wp_load
     */
    public function modifiers() {
        $modifiers = KeywordDatabaseV3::get_modifiers();

        WP_CLI::log(sprintf("\n=== Available Modifiers (%d) ===\n", count($modifiers)));

        $items = [];
        foreach ($modifiers as $slug => $modifier) {
            $items[] = [
                'Slug' => $slug,
                'Name' => $modifier['name'],
                'Intent' => $modifier['intent'],
            ];
        }

        WP_CLI\Utils\format_items('table', $items, ['Slug', 'Name', 'Intent']);
    }

    /**
     * Find existing page
     */
    private function find_existing_page(array $keyword_data): ?int {
        global $wpdb;

        // Check in pt24_landing
        $query = $wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = 'pt24_city'
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'pt24_vertical'
            LEFT JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = 'pt24_service'
            WHERE p.post_type = 'pt24_landing'
            AND pm1.meta_value = %s
            AND pm2.meta_value = %s
            AND (pm3.meta_value = %s OR pm3.meta_value IS NULL)
            LIMIT 1",
            $keyword_data['city'],
            $keyword_data['vertical'],
            $keyword_data['service'] ?? ''
        );

        $post_id = $wpdb->get_var($query);
        return $post_id ? (int) $post_id : null;
    }

    /**
     * Create SEO page
     */
    private function create_seo_page(array $keyword_data): ?int {
        $title = $this->generate_title($keyword_data);
        $meta_description = $this->generate_meta_description($keyword_data);
        $h1 = $this->generate_h1($keyword_data);

        // Create post
        $post_id = wp_insert_post([
            'post_title' => $title,
            'post_type' => 'pt24_landing',
            'post_status' => 'publish',
        ]);

        if (is_wp_error($post_id) || !$post_id) {
            return null;
        }

        // Add metadata
        update_post_meta($post_id, 'pt24_city', $keyword_data['city']);
        update_post_meta($post_id, 'pt24_city_display', $keyword_data['city_name']);
        update_post_meta($post_id, 'pt24_region', $keyword_data['region']);
        update_post_meta($post_id, 'pt24_vertical', $keyword_data['vertical']);
        update_post_meta($post_id, 'pt24_vertical_display', $keyword_data['vertical_name']);

        if (!empty($keyword_data['service'])) {
            update_post_meta($post_id, 'pt24_service', $keyword_data['service']);
            update_post_meta($post_id, 'pt24_service_display', $keyword_data['service_name']);
        }

        if (!empty($keyword_data['problem'])) {
            update_post_meta($post_id, 'pt24_problem', $keyword_data['problem']);
            update_post_meta($post_id, 'pt24_problem_display', $keyword_data['problem_name']);
        }

        if (!empty($keyword_data['modifier'])) {
            update_post_meta($post_id, 'pt24_modifier', $keyword_data['modifier']);
            update_post_meta($post_id, 'pt24_modifier_display', $keyword_data['modifier_name']);
        }

        update_post_meta($post_id, 'pt24_keyword', $keyword_data['keyword']);
        update_post_meta($post_id, 'pt24_keyword_type', $keyword_data['type']);
        update_post_meta($post_id, 'pt24_intent', $keyword_data['intent']);
        update_post_meta($post_id, 'pt24_h1', $h1);
        update_post_meta($post_id, 'pt24_meta_title', $title);
        update_post_meta($post_id, 'pt24_meta_description', $meta_description);

        return $post_id;
    }

    /**
     * Generate title
     */
    private function generate_title(array $keyword_data): string {
        $vertical_name = $keyword_data['vertical_name'];
        $city = $keyword_data['city_name'];

        if (!empty($keyword_data['service_name'])) {
            $service = $keyword_data['service_name'];
            return "$service $city - Sprawdź ceny i oferty | $vertical_name";
        }

        return "$vertical_name $city - Sprawdź ceny i oferty";
    }

    /**
     * Generate H1
     */
    private function generate_h1(array $keyword_data): string {
        return ucfirst($keyword_data['keyword']) . " — najlepsze oferty";
    }

    /**
     * Generate meta description
     */
    private function generate_meta_description(array $keyword_data): string {
        $keyword = $keyword_data['keyword'];
        $city = $keyword_data['city_name'];

        return "Szukasz: $keyword? ✓ Sprawdź ceny ✓ Porównaj oferty ✓ Darmowe zapytanie. Najlepsze firmy w $city.";
    }
}

WP_CLI::add_command('pearblog seo-v3:keywords', [new SEO_Keyword_V3_CLI(), 'keywords']);
WP_CLI::add_command('pearblog seo-v3:stats', [new SEO_Keyword_V3_CLI(), 'stats']);
WP_CLI::add_command('pearblog seo-v3:generate', [new SEO_Keyword_V3_CLI(), 'generate']);
WP_CLI::add_command('pearblog seo-v3:verticals', [new SEO_Keyword_V3_CLI(), 'verticals']);
WP_CLI::add_command('pearblog seo-v3:services', [new SEO_Keyword_V3_CLI(), 'services']);
WP_CLI::add_command('pearblog seo-v3:modifiers', [new SEO_Keyword_V3_CLI(), 'modifiers']);

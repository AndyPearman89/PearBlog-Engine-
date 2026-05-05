<?php
/**
 * SEO Keyword Generator WP-CLI Commands
 *
 * Bulk generation and management of programmatic SEO pages
 * Supports 1000+ keyword combinations from city × service × problem matrix
 *
 * @package PearBlogEngine
 * @subpackage SEO
 * @version 1.0.0
 */

if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

use PearBlogEngine\SEO\KeywordDatabase;

/**
 * SEO Keyword Generator CLI Commands
 */
class SEO_Keyword_CLI {

    /**
     * Generate SEO keywords from database
     *
     * ## OPTIONS
     *
     * [--city=<city>]
     * : Generate for specific city
     *
     * [--service=<service>]
     * : Generate for specific service
     *
     * [--problem=<problem>]
     * : Generate for specific problem
     *
     * [--type=<type>]
     * : Keyword type (high_intent, problem, long_tail)
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
     *     wp pearblog seo:keywords
     *
     *     # Generate for specific city
     *     wp pearblog seo:keywords --city=katowice
     *
     *     # Generate high intent keywords only
     *     wp pearblog seo:keywords --type=high_intent --limit=100
     *
     *     # Export to CSV
     *     wp pearblog seo:keywords --format=csv > keywords.csv
     *
     * @when after_wp_load
     */
    public function keywords($args, $assoc_args) {
        $options = [];

        if (isset($assoc_args['city'])) {
            $options['cities'] = [$assoc_args['city']];
        }

        if (isset($assoc_args['service'])) {
            $options['services'] = [$assoc_args['service']];
        }

        if (isset($assoc_args['problem'])) {
            $options['problems'] = [$assoc_args['problem']];
        }

        if (isset($assoc_args['limit'])) {
            $options['limit'] = (int) $assoc_args['limit'];
        }

        $format = $assoc_args['format'] ?? 'table';
        $type_filter = $assoc_args['type'] ?? null;

        WP_CLI::log("Generating SEO keywords...");

        $keywords = KeywordDatabase::generate_keywords($options);

        // Filter by type if specified
        if ($type_filter) {
            $keywords = array_filter($keywords, function($k) use ($type_filter) {
                return $k['type'] === $type_filter;
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
                'Slug' => $keyword['slug'],
                'City' => $keyword['city_name'],
                'Service' => $keyword['service_name'] ?? '-',
                'Problem' => $keyword['problem_name'] ?? '-',
                'Intent' => $keyword['intent'],
            ];
        }

        WP_CLI\Utils\format_items($format, $output_items, ['Keyword', 'Type', 'City', 'Service', 'Problem', 'Intent', 'Slug']);
    }

    /**
     * Show SEO keyword statistics
     *
     * ## EXAMPLES
     *
     *     wp pearblog seo:stats
     *
     * @when after_wp_load
     */
    public function stats() {
        WP_CLI::log("\n=== SEO Keyword Database Statistics ===\n");

        $stats = KeywordDatabase::get_stats();

        WP_CLI::log("Data Sources:");
        WP_CLI::log("  Cities: " . $stats['cities']);
        WP_CLI::log("  Services: " . $stats['services']);
        WP_CLI::log("  Problems: " . $stats['problems']);
        WP_CLI::log("");

        WP_CLI::log("Keyword Combinations:");
        WP_CLI::log("  High Intent: " . number_format($stats['combinations']['high_intent']));
        WP_CLI::log("  Problem: " . number_format($stats['combinations']['problem']));
        WP_CLI::log("  Long Tail: " . number_format($stats['combinations']['long_tail']));
        WP_CLI::log("  ───────────────");
        WP_CLI::log("  Total: " . number_format($stats['combinations']['total']));
        WP_CLI::log("");
    }

    /**
     * Generate SEO landing pages in bulk
     *
     * ## OPTIONS
     *
     * [--city=<city>]
     * : Generate for specific city
     *
     * [--service=<service>]
     * : Generate for specific service
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
     * ## EXAMPLES
     *
     *     # Generate 100 pages
     *     wp pearblog seo:generate --batch=100
     *
     *     # Generate for specific city
     *     wp pearblog seo:generate --city=katowice --batch=50
     *
     *     # Dry run
     *     wp pearblog seo:generate --batch=10 --dry-run
     *
     * @when after_wp_load
     */
    public function generate($args, $assoc_args) {
        $options = [];

        if (isset($assoc_args['city'])) {
            $options['cities'] = [$assoc_args['city']];
        }

        if (isset($assoc_args['service'])) {
            $options['services'] = [$assoc_args['service']];
        }

        $batch = (int) ($assoc_args['batch'] ?? 100);
        $dry_run = isset($assoc_args['dry-run']);
        $type_filter = $assoc_args['type'] ?? 'all';

        $options['limit'] = $batch;

        WP_CLI::log("Generating SEO landing pages...");
        if ($dry_run) {
            WP_CLI::warning("DRY RUN MODE - No pages will be created");
        }

        $keywords = KeywordDatabase::generate_keywords($options);

        // Filter by type
        if ($type_filter !== 'all') {
            $keywords = array_filter($keywords, function($k) use ($type_filter) {
                return $k['type'] === $type_filter;
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
                    "Would create: %s (%s)",
                    $keyword_data['keyword'],
                    $keyword_data['slug']
                ));
                $created++;
                $progress->tick();
                continue;
            }

            // Check if page already exists
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
     * Search for keywords
     *
     * ## OPTIONS
     *
     * <query>
     * : Search query
     *
     * [--limit=<number>]
     * : Number of results
     * ---
     * default: 20
     * ---
     *
     * ## EXAMPLES
     *
     *     wp pearblog seo:search "wymiana oleju"
     *     wp pearblog seo:search "katowice" --limit=50
     *
     * @when after_wp_load
     */
    public function search($args, $assoc_args) {
        $query = $args[0] ?? '';
        $limit = (int) ($assoc_args['limit'] ?? 20);

        if (empty($query)) {
            WP_CLI::error('Please provide a search query');
            return;
        }

        WP_CLI::log(sprintf('Searching for: "%s"', $query));

        $results = KeywordDatabase::search($query, $limit);

        if (empty($results)) {
            WP_CLI::warning('No keywords found');
            return;
        }

        WP_CLI::success(sprintf('Found %d keywords', count($results)));

        $output_items = [];
        foreach ($results as $keyword) {
            $output_items[] = [
                'Keyword' => $keyword['keyword'],
                'Type' => $keyword['type'],
                'City' => $keyword['city_name'],
                'Intent' => $keyword['intent'],
                'Slug' => $keyword['slug'],
            ];
        }

        WP_CLI\Utils\format_items('table', $output_items, ['Keyword', 'Type', 'City', 'Intent', 'Slug']);
    }

    /**
     * List available cities
     *
     * ## EXAMPLES
     *
     *     wp pearblog seo:cities
     *
     * @when after_wp_load
     */
    public function cities() {
        $cities = KeywordDatabase::get_cities();

        WP_CLI::log(sprintf("\n=== Available Cities (%d) ===\n", count($cities)));

        $items = [];
        foreach ($cities as $slug => $city) {
            $items[] = [
                'Slug' => $slug,
                'Name' => $city['name'],
                'Voivodeship' => $city['voivodeship'],
                'Population' => number_format($city['population']),
            ];
        }

        WP_CLI\Utils\format_items('table', $items, ['Slug', 'Name', 'Voivodeship', 'Population']);
    }

    /**
     * List available services
     *
     * ## EXAMPLES
     *
     *     wp pearblog seo:services
     *
     * @when after_wp_load
     */
    public function services() {
        $services = KeywordDatabase::get_services();

        WP_CLI::log(sprintf("\n=== Available Services (%d) ===\n", count($services)));

        $items = [];
        foreach ($services as $slug => $service) {
            $items[] = [
                'Slug' => $slug,
                'Name' => $service['name'],
                'Category' => $service['category'],
                'Price Range' => sprintf('%d - %d zł', $service['avg_price_min'], $service['avg_price_max']),
            ];
        }

        WP_CLI\Utils\format_items('table', $items, ['Slug', 'Name', 'Category', 'Price Range']);
    }

    /**
     * List available problems
     *
     * ## EXAMPLES
     *
     *     wp pearblog seo:problems
     *
     * @when after_wp_load
     */
    public function problems() {
        $problems = KeywordDatabase::get_problems();

        WP_CLI::log(sprintf("\n=== Available Problems (%d) ===\n", count($problems)));

        $items = [];
        foreach ($problems as $slug => $problem) {
            $items[] = [
                'Slug' => $slug,
                'Name' => $problem['name'],
                'Intent' => $problem['intent'],
                'Related Services' => implode(', ', $problem['related_services'] ?? []),
            ];
        }

        WP_CLI\Utils\format_items('table', $items, ['Slug', 'Name', 'Intent', 'Related Services']);
    }

    /**
     * Find existing page by keyword data
     */
    private function find_existing_page(array $keyword_data): ?int {
        global $wpdb;

        $slug = $keyword_data['slug'];

        // Check in pt24_landing custom post type
        $query = $wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = 'pt24_city'
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'pt24_service'
            LEFT JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = 'pt24_problem'
            WHERE p.post_type = 'pt24_landing'
            AND pm1.meta_value = %s
            AND (pm2.meta_value = %s OR pm2.meta_value IS NULL)
            AND (pm3.meta_value = %s OR pm3.meta_value IS NULL)
            LIMIT 1",
            $keyword_data['city'],
            $keyword_data['service'] ?? '',
            $keyword_data['problem'] ?? ''
        );

        $post_id = $wpdb->get_var($query);

        return $post_id ? (int) $post_id : null;
    }

    /**
     * Create SEO page from keyword data
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

        if (!empty($keyword_data['service'])) {
            update_post_meta($post_id, 'pt24_service', $keyword_data['service']);
            update_post_meta($post_id, 'pt24_service_display', $keyword_data['service_name']);
        }

        if (!empty($keyword_data['problem'])) {
            update_post_meta($post_id, 'pt24_problem', $keyword_data['problem']);
            update_post_meta($post_id, 'pt24_problem_display', $keyword_data['problem_name']);
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
     * Generate page title
     */
    private function generate_title(array $keyword_data): string {
        $type = $keyword_data['type'];
        $city = $keyword_data['city_name'];

        if ($type === 'high_intent' && !empty($keyword_data['service_name'])) {
            $service = $keyword_data['service_name'];
            if (!empty($keyword_data['problem'])) {
                return "$service $city - Cena i oferty";
            }
            return "$service $city - Sprawdź ceny i dostępne firmy";
        }

        if ($type === 'problem' && !empty($keyword_data['problem_name'])) {
            $problem = ucfirst($keyword_data['problem_name']);
            return "$problem $city - Przyczyny i rozwiązania";
        }

        if ($type === 'long_tail' && !empty($keyword_data['service_name']) && !empty($keyword_data['problem_name'])) {
            $service = $keyword_data['service_name'];
            $problem = $keyword_data['problem_name'];
            return "$service - $problem $city - Co robić?";
        }

        return $keyword_data['keyword'];
    }

    /**
     * Generate H1 heading
     */
    private function generate_h1(array $keyword_data): string {
        $type = $keyword_data['type'];
        $city = $keyword_data['city_name'];

        if ($type === 'high_intent' && !empty($keyword_data['service_name'])) {
            $service = $keyword_data['service_name'];
            return "$service $city — sprawdź ceny i dostępne firmy";
        }

        if ($type === 'problem' && !empty($keyword_data['problem_name'])) {
            $problem = ucfirst($keyword_data['problem_name']);
            return "$problem w $city — przyczyny i co robić?";
        }

        if ($type === 'long_tail') {
            return ucfirst($keyword_data['keyword']) . " — przewodnik 2024";
        }

        return ucfirst($keyword_data['keyword']);
    }

    /**
     * Generate meta description
     */
    private function generate_meta_description(array $keyword_data): string {
        $city = $keyword_data['city_name'];

        if ($keyword_data['type'] === 'high_intent' && !empty($keyword_data['service_name'])) {
            $service = $keyword_data['service_name'];
            return "Szukasz: $service w $city? ✓ Sprawdź ceny ✓ Porównaj oferty ✓ Darmowe zapytanie. Najlepsze firmy w Twojej okolicy.";
        }

        if ($keyword_data['type'] === 'problem') {
            $problem = $keyword_data['problem_name'];
            return "$problem w $city? Sprawdź przyczyny, dowiedz się co robić i znajdź specjalistę w Twojej okolicy.";
        }

        return "Wszystko o: " . $keyword_data['keyword'] . ". Ceny, porady, ranking firm.";
    }
}

WP_CLI::add_command('pearblog seo:keywords', [new SEO_Keyword_CLI(), 'keywords']);
WP_CLI::add_command('pearblog seo:stats', [new SEO_Keyword_CLI(), 'stats']);
WP_CLI::add_command('pearblog seo:generate', [new SEO_Keyword_CLI(), 'generate']);
WP_CLI::add_command('pearblog seo:search', [new SEO_Keyword_CLI(), 'search']);
WP_CLI::add_command('pearblog seo:cities', [new SEO_Keyword_CLI(), 'cities']);
WP_CLI::add_command('pearblog seo:services', [new SEO_Keyword_CLI(), 'services']);
WP_CLI::add_command('pearblog seo:problems', [new SEO_Keyword_CLI(), 'problems']);

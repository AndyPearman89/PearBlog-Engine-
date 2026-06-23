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

/**
 * WP-CLI: Blog Engine commands
 *
 *     wp pt24-blog generate --topic="Pękła rura" --service=hydraulik --city=katowice
 *     wp pt24-blog queue-starters [--city=katowice]
 *     wp pt24-blog import-csv <file.csv>
 *     wp pt24-blog run-queue [--batches=5]
 *     wp pt24-blog stats
 */
class PT24_Blog_CLI {

    /**
     * Generate a single blog article via OpenAI.
     *
     * ## OPTIONS
     * <topic>
     * : Article topic (e.g. "Pękła rura - co robić?")
     *
     * --service=<slug>
     * : PT24 service slug (e.g. hydraulik)
     *
     * [--city=<slug>]
     * : PT24 city slug (optional; creates generic article if omitted)
     *
     * [--cat=<slug>]
     * : Category slug (auto-detected if omitted)
     *
     * ## EXAMPLES
     *
     *     wp pt24-blog generate "Pękła rura - co robić?" --service=hydraulik --city=katowice
     *
     * @when after_wp_load
     */
    public function generate( $args, $assoc_args ) {
        if ( ! class_exists( 'PT24_Blog_Engine' ) ) {
            WP_CLI::error( 'PT24_Blog_Engine not found. Ensure pt24-blog-engine.php is in mu-plugins.' );
        }

        $topic   = $args[0] ?? '';
        $service = $assoc_args['service'] ?? '';
        $city    = $assoc_args['city']    ?? '';
        $cat     = $assoc_args['cat']     ?? '';

        if ( '' === $topic || '' === $service ) {
            WP_CLI::error( 'Provide topic as first argument and --service=<slug>.' );
        }

        WP_CLI::log( "Generating: \"$topic\" | $service" . ( $city ? " | $city" : '' ) . ' …' );

        $ai = PT24_Blog_Engine::generate_article( $topic, $service, $city );
        if ( null === $ai ) {
            WP_CLI::error( 'AI generation failed. Check pt24_openai_api_key option.' );
        }

        $post_id = PT24_Blog_Engine::publish_article( $ai, $cat );
        if ( 0 === $post_id ) {
            WP_CLI::error( 'Failed to create post.' );
        }

        WP_CLI::success( "Published: #{$post_id} — {$ai['h1']}" );
        WP_CLI::log( 'URL: ' . get_permalink( $post_id ) );
    }

    /**
     * Queue the 100 starter topics for batch generation.
     *
     * ## OPTIONS
     *
     * [--city=<slug>]
     * : Apply this city to all generic starter topics.
     *
     * ## EXAMPLES
     *
     *     wp pt24-blog queue-starters --city=katowice
     *
     * @when after_wp_load
     */
    public function queue_starters( $args, $assoc_args ) {
        if ( ! class_exists( 'PT24_Blog_Engine' ) ) {
            WP_CLI::error( 'PT24_Blog_Engine not found.' );
        }

        $city   = sanitize_title( $assoc_args['city'] ?? '' );
        $queued = PT24_Blog_Engine::queue_starters( $city );
        WP_CLI::success( "Queued $queued starter topics. Run: wp pt24-blog run-queue --batches=20" );
    }

    /**
     * Import topics from a CSV file.
     *
     * ## OPTIONS
     *
     * <file>
     * : Path to CSV file (format: temat,usluga,miasto[,kategoria])
     *
     * ## EXAMPLES
     *
     *     wp pt24-blog import-csv topics.csv
     *
     * @when after_wp_load
     */
    public function import_csv( $args, $assoc_args ) {
        if ( ! class_exists( 'PT24_Blog_Engine' ) ) {
            WP_CLI::error( 'PT24_Blog_Engine not found.' );
        }

        $file = $args[0] ?? '';
        if ( ! file_exists( $file ) ) {
            WP_CLI::error( "File not found: $file" );
        }

        $csv    = file_get_contents( $file );
        $result = PT24_Blog_Engine::queue_from_csv( $csv );
        WP_CLI::success( "Queued: {$result['queued']}, skipped: {$result['skipped']}." );
        if ( ! empty( $result['errors'] ) ) {
            foreach ( $result['errors'] as $err ) {
                WP_CLI::warning( $err );
            }
        }
    }

    /**
     * Process the blog article generation queue.
     *
     * ## OPTIONS
     *
     * [--batches=<n>]
     * : Number of batches to run (default: 1, each batch = 5 articles)
     *
     * ## EXAMPLES
     *
     *     wp pt24-blog run-queue --batches=10
     *
     * @when after_wp_load
     */
    public function run_queue( $args, $assoc_args ) {
        if ( ! class_exists( 'PT24_Blog_Engine' ) ) {
            WP_CLI::error( 'PT24_Blog_Engine not found.' );
        }

        $batches = max( 1, (int) ( $assoc_args['batches'] ?? 1 ) );
        $total   = 0;

        $progress = \WP_CLI\Utils\make_progress_bar( 'Generating articles', $batches );
        for ( $i = 0; $i < $batches; $i++ ) {
            $done   = PT24_Blog_Engine::process_queue();
            $total += $done;
            $progress->tick();
            if ( $done < PT24_Blog_Engine::BATCH_SIZE ) {
                break; // Queue exhausted
            }
        }
        $progress->finish();

        WP_CLI::success( "Generated $total articles total." );
    }

    /**
     * Show Blog Engine statistics.
     *
     * ## EXAMPLES
     *
     *     wp pt24-blog stats
     *
     * @when after_wp_load
     */
    public function stats( $args, $assoc_args ) {
        if ( ! class_exists( 'PT24_Blog_Engine' ) ) {
            WP_CLI::error( 'PT24_Blog_Engine not found.' );
        }

        $s = PT24_Blog_Engine::get_stats();
        WP_CLI::log( "Articles published : {$s['total_articles']}" );
        WP_CLI::log( "Queue size         : {$s['queue_size']}" );
        WP_CLI::log( "Starter topics     : {$s['starters']}" );
        WP_CLI::log( "OpenAI key         : " . ( $s['has_openai_key'] ? 'SET' : 'MISSING' ) );
    }
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'pt24-blog', 'PT24_Blog_CLI' );
}

/**
 * WP-CLI: Google Places Seeder commands
 *
 *     wp pt24-places seed --service=mechanik --city=katowice [--ai]
 *     wp pt24-places queue-all [--service=mechanik] [--ai]
 *     wp pt24-places run-queue [--batches=10]
 *     wp pt24-places import-csv places.csv [--ai]
 *     wp pt24-places stats
 */
class PT24_Places_CLI {

    /**
     * Seed firms from Google Places for a specific service+city.
     *
     * ## OPTIONS
     *
     * --service=<slug>
     * : PT24 service slug
     *
     * --city=<slug>
     * : PT24 city slug
     *
     * [--ai]
     * : Generate AI-enriched profiles (requires pt24_openai_api_key)
     *
     * [--max=<n>]
     * : Max firms to save (default: 5)
     *
     * ## EXAMPLES
     *
     *     wp pt24-places seed --service=mechanik --city=katowice --ai
     *
     * @when after_wp_load
     */
    public function seed( $args, $assoc_args ) {
        if ( ! class_exists( 'PT24_Places_Seeder' ) ) {
            WP_CLI::error( 'PT24_Places_Seeder not found.' );
        }

        $service = sanitize_key( $assoc_args['service'] ?? '' );
        $city    = sanitize_title( $assoc_args['city']    ?? '' );
        $use_ai  = isset( $assoc_args['ai'] );
        $max     = max( 1, min( 20, (int) ( $assoc_args['max'] ?? 5 ) ) );
        $api_key = (string) get_option( PT24_Places_Seeder::OPTION_API_KEY, '' );

        if ( '' === $service || '' === $city ) {
            WP_CLI::error( 'Provide --service=<slug> and --city=<slug>.' );
        }
        if ( '' === $api_key ) {
            WP_CLI::error( 'Google Places API key not set. Run: wp option set pt24_google_places_api_key YOUR_KEY' );
        }

        WP_CLI::log( "Seeding: $service / $city (max $max firms, AI=" . ( $use_ai ? 'yes' : 'no' ) . ') …' );
        $saved = PT24_Places_Seeder::seed_service_city( $service, $city, $api_key, $use_ai, $max );
        WP_CLI::success( "Saved $saved firms." );
    }

    /**
     * Queue all city × service combinations for batch seeding.
     *
     * ## OPTIONS
     *
     * [--service=<slug>]
     * : Limit to a single service (optional)
     *
     * [--ai]
     * : Enable AI enrichment for queued items
     *
     * ## EXAMPLES
     *
     *     wp pt24-places queue-all --ai
     *     wp pt24-places queue-all --service=mechanik
     *
     * @when after_wp_load
     */
    public function queue_all( $args, $assoc_args ) {
        if ( ! class_exists( 'PT24_Places_Seeder' ) ) {
            WP_CLI::error( 'PT24_Places_Seeder not found.' );
        }

        $service = sanitize_key( $assoc_args['service'] ?? '' );
        $use_ai  = isset( $assoc_args['ai'] );
        $api_key = (string) get_option( PT24_Places_Seeder::OPTION_API_KEY, '' );

        if ( '' === $api_key ) {
            WP_CLI::error( 'Google Places API key not set.' );
        }

        $service_filter = $service ? [ $service ] : [];
        $queued = PT24_Places_Seeder::queue_all( $api_key, $service_filter, [], $use_ai );
        WP_CLI::success( "Queued $queued pairs. Run: wp pt24-places run-queue --batches=100" );
    }

    /**
     * Process the Places seeder queue.
     *
     * ## OPTIONS
     *
     * [--batches=<n>]
     * : Number of batches (default: 1, each = 3 service×city pairs)
     *
     * ## EXAMPLES
     *
     *     wp pt24-places run-queue --batches=50
     *
     * @when after_wp_load
     */
    public function run_queue( $args, $assoc_args ) {
        if ( ! class_exists( 'PT24_Places_Seeder' ) ) {
            WP_CLI::error( 'PT24_Places_Seeder not found.' );
        }

        $batches  = max( 1, (int) ( $assoc_args['batches'] ?? 1 ) );
        $total    = 0;
        $progress = \WP_CLI\Utils\make_progress_bar( 'Seeding firms', $batches );

        for ( $i = 0; $i < $batches; $i++ ) {
            $done   = PT24_Places_Seeder::process_queue();
            $total += $done;
            $progress->tick();
        }
        $progress->finish();
        WP_CLI::success( "Saved $total firms total." );
    }

    /**
     * Import firms from a places_seed CSV file.
     *
     * ## OPTIONS
     *
     * <file>
     * : Path to CSV (format: place_id,company_name,service,city,address,phone,website,rating,reviews,status)
     *
     * [--ai]
     * : Generate AI profiles for imported firms
     *
     * ## EXAMPLES
     *
     *     wp pt24-places import-csv places_seed.csv --ai
     *
     * @when after_wp_load
     */
    public function import_csv( $args, $assoc_args ) {
        if ( ! class_exists( 'PT24_Places_Seeder' ) ) {
            WP_CLI::error( 'PT24_Places_Seeder not found.' );
        }

        $file   = $args[0] ?? '';
        $use_ai = isset( $assoc_args['ai'] );

        if ( ! file_exists( $file ) ) {
            WP_CLI::error( "File not found: $file" );
        }

        $csv    = file_get_contents( $file );
        $result = PT24_Places_Seeder::import_from_csv( $csv, $use_ai );
        WP_CLI::success( "Imported: {$result['imported']}, skipped: {$result['skipped']}." );
        foreach ( $result['errors'] as $err ) {
            WP_CLI::warning( $err );
        }
    }

    /**
     * Show Places Seeder statistics.
     *
     * ## EXAMPLES
     *
     *     wp pt24-places stats
     *
     * @when after_wp_load
     */
    public function stats( $args, $assoc_args ) {
        if ( ! class_exists( 'PT24_Places_Seeder' ) ) {
            WP_CLI::error( 'PT24_Places_Seeder not found.' );
        }

        $s = PT24_Places_Seeder::get_stats();
        WP_CLI::log( "Total firms (published) : {$s['total_firms']}" );
        WP_CLI::log( "From Google Places      : {$s['places_firms']}" );
        WP_CLI::log( "AI-enriched             : {$s['ai_enriched']}" );
        WP_CLI::log( "Queue size              : {$s['queue_size']}" );
        WP_CLI::log( "Google Places API key   : " . ( $s['has_places_key'] ? 'SET' : 'MISSING' ) );
        WP_CLI::log( "OpenAI key              : " . ( $s['has_openai_key'] ? 'SET' : 'MISSING' ) );
        WP_CLI::log( "Possible pairs          : {$s['possible_pairs']}" );
    }
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'pt24-places', 'PT24_Places_CLI' );
}

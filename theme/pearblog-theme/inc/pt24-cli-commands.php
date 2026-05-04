<?php
/**
 * PT24 WP-CLI Commands
 *
 * Mass generation of local landing pages
 *
 * @package PearBlog
 * @version 1.0.0
 */

if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

/**
 * PT24 WP-CLI Commands
 */
class PT24_CLI_Commands {

    /**
     * Generate local landing pages in bulk
     *
     * ## OPTIONS
     *
     * [--service=<service>]
     * : Service slug (mechanik, hydraulik, etc.)
     *
     * [--city=<city>]
     * : City slug
     *
     * [--batch=<number>]
     * : Number of pages to generate
     * ---
     * default: 10
     * ---
     *
     * ## EXAMPLES
     *
     *     wp pt24 generate-pages --service=mechanik --city=warszawa
     *     wp pt24 generate-pages --batch=100
     *
     * @when after_wp_load
     */
    public function generate_pages($args, $assoc_args) {
        $service = $assoc_args['service'] ?? null;
        $city = $assoc_args['city'] ?? null;
        $batch = intval($assoc_args['batch'] ?? 10);

        // Services
        $services = [
            'mechanik' => 'Mechanik samochodowy',
            'hydraulik' => 'Hydraulik',
            'elektryk' => 'Elektryk samochodowy',
            'laweta' => 'Laweta',
            'wulkanizacja' => 'Wulkanizacja',
        ];

        // Top 50 cities
        $cities = [
            'warszawa' => 'Warszawa',
            'krakow' => 'Kraków',
            'lodz' => 'Łódź',
            'wroclaw' => 'Wrocław',
            'poznan' => 'Poznań',
            'gdansk' => 'Gdańsk',
            'szczecin' => 'Szczecin',
            'bydgoszcz' => 'Bydgoszcz',
            'lublin' => 'Lublin',
            'katowice' => 'Katowice',
            'bialystok' => 'Białystok',
            'gdynia' => 'Gdynia',
            'czestochowa' => 'Częstochowa',
            'radom' => 'Radom',
            'sosnowiec' => 'Sosnowiec',
            'torun' => 'Toruń',
            'kielce' => 'Kielce',
            'rzeszow' => 'Rzeszów',
            'gliwice' => 'Gliwice',
            'zabrze' => 'Zabrze',
            'ruda-slaska' => 'Ruda Śląska',
            'bytom' => 'Bytom',
            'chorzow' => 'Chorzów',
            'tychy' => 'Tychy',
            'dabrowa-gornicza' => 'Dąbrowa Górnicza',
        ];

        // Filter if specific service/city requested
        if ($service) {
            $services = array_filter($services, function($k) use ($service) {
                return $k === $service;
            }, ARRAY_FILTER_USE_KEY);
        }

        if ($city) {
            $cities = array_filter($cities, function($k) use ($city) {
                return $k === $city;
            }, ARRAY_FILTER_USE_KEY);
        }

        WP_CLI::log("Generating PT24 landing pages...");
        WP_CLI::log("Services: " . count($services));
        WP_CLI::log("Cities: " . count($cities));

        $generated = 0;
        $skipped = 0;

        foreach ($services as $service_slug => $service_name) {
            foreach ($cities as $city_slug => $city_name) {
                if ($generated >= $batch) {
                    break 2;
                }

                // Check if page already exists
                $existing = get_posts([
                    'post_type' => 'pt24_landing',
                    'meta_query' => [
                        'relation' => 'AND',
                        [
                            'key' => 'pt24_service',
                            'value' => $service_slug,
                        ],
                        [
                            'key' => 'pt24_city',
                            'value' => $city_slug,
                        ],
                    ],
                    'posts_per_page' => 1,
                ]);

                if (!empty($existing)) {
                    WP_CLI::log("⏭️  Skipped: $service_name / $city_name (already exists)");
                    $skipped++;
                    continue;
                }

                // Create landing page
                $post_id = $this->create_landing_page($service_slug, $service_name, $city_slug, $city_name);

                if ($post_id) {
                    WP_CLI::success("✅ Created: $service_name / $city_name (ID: $post_id)");
                    $generated++;
                } else {
                    WP_CLI::error("❌ Failed: $service_name / $city_name");
                }
            }
        }

        WP_CLI::success("Generated: $generated pages");
        WP_CLI::log("Skipped: $skipped pages (already exist)");
    }

    /**
     * Create a single landing page
     */
    private function create_landing_page($service_slug, $service_name, $city_slug, $city_name) {
        $title = "$service_name $city_name";
        $h1 = "$service_name $city_name — sprawdź ceny i dostępne firmy";
        $hero_text = "Znajdź najlepszych specjalistów w $city_name i otrzymaj dopasowane oferty.";
        $meta_title = "$service_name $city_name — ceny, oferty i ranking firm";
        $meta_description = "Szukasz $service_slug w $city_name? ✓ Sprawdź ceny ✓ Porównaj oferty ✓ Zobacz ranking firm. Darmowe zapytanie.";

        // Create post
        $post_id = wp_insert_post([
            'post_title' => $title,
            'post_status' => 'publish',
            'post_type' => 'pt24_landing',
        ]);

        if (is_wp_error($post_id) || !$post_id) {
            return false;
        }

        // Add meta
        update_post_meta($post_id, 'pt24_service', $service_slug);
        update_post_meta($post_id, 'pt24_city', $city_slug);
        update_post_meta($post_id, 'pt24_service_display', $service_name);
        update_post_meta($post_id, 'pt24_city_display', $city_name);
        update_post_meta($post_id, 'pt24_h1', $h1);
        update_post_meta($post_id, 'pt24_hero_text', $hero_text);
        update_post_meta($post_id, 'pt24_meta_title', $meta_title);
        update_post_meta($post_id, 'pt24_meta_description', $meta_description);

        return $post_id;
    }

    /**
     * List PT24 statistics
     *
     * ## EXAMPLES
     *
     *     wp pt24 stats
     *
     * @when after_wp_load
     */
    public function stats() {
        global $wpdb;

        WP_CLI::log("\n=== PT24 Platform Statistics ===\n");

        // Landing pages
        $landings = wp_count_posts('pt24_landing');
        WP_CLI::log("Landing Pages: " . $landings->publish);

        // Businesses
        $businesses = wp_count_posts('pt24_business');
        WP_CLI::log("Businesses (Total): " . ($businesses->publish + $businesses->pending + $businesses->draft));
        WP_CLI::log("  - Published: " . $businesses->publish);
        WP_CLI::log("  - Pending: " . $businesses->pending);

        // Leads
        $leads_table = $wpdb->prefix . 'pt24_leads';
        $total_leads = $wpdb->get_var("SELECT COUNT(*) FROM $leads_table");
        $new_leads = $wpdb->get_var("SELECT COUNT(*) FROM $leads_table WHERE status = 'new'");
        WP_CLI::log("Leads (Total): $total_leads");
        WP_CLI::log("  - New: $new_leads");

        // Cities
        $cities = wp_count_terms(['taxonomy' => 'pt24_city']);
        WP_CLI::log("Cities: $cities");

        // Services
        $services = wp_count_terms(['taxonomy' => 'pt24_service_cat']);
        WP_CLI::log("Service Categories: $services");

        WP_CLI::log("\n");
    }

    /**
     * Initialize default PT24 data
     *
     * ## EXAMPLES
     *
     *     wp pt24 init
     *
     * @when after_wp_load
     */
    public function init() {
        WP_CLI::log("Initializing PT24 platform data...");

        // Create default service categories
        $services = ['mechanik', 'hydraulik', 'elektryk', 'laweta', 'wulkanizacja'];
        foreach ($services as $service) {
            if (!term_exists($service, 'pt24_service_cat')) {
                $term = wp_insert_term(ucfirst($service), 'pt24_service_cat', ['slug' => $service]);
                if (!is_wp_error($term)) {
                    WP_CLI::success("Created service: $service");
                }
            } else {
                WP_CLI::log("Service already exists: $service");
            }
        }

        // Create top 20 cities
        $cities = [
            'warszawa' => 'Warszawa',
            'krakow' => 'Kraków',
            'lodz' => 'Łódź',
            'wroclaw' => 'Wrocław',
            'poznan' => 'Poznań',
            'gdansk' => 'Gdańsk',
            'szczecin' => 'Szczecin',
            'bydgoszcz' => 'Bydgoszcz',
            'lublin' => 'Lublin',
            'katowice' => 'Katowice',
            'bialystok' => 'Białystok',
            'gdynia' => 'Gdynia',
            'czestochowa' => 'Częstochowa',
            'radom' => 'Radom',
            'sosnowiec' => 'Sosnowiec',
            'torun' => 'Toruń',
            'kielce' => 'Kielce',
            'rzeszow' => 'Rzeszów',
            'gliwice' => 'Gliwice',
            'zabrze' => 'Zabrze',
        ];

        foreach ($cities as $slug => $name) {
            if (!term_exists($name, 'pt24_city')) {
                $term = wp_insert_term($name, 'pt24_city', ['slug' => $slug]);
                if (!is_wp_error($term)) {
                    WP_CLI::success("Created city: $name");
                }
            } else {
                WP_CLI::log("City already exists: $name");
            }
        }

        // Flush rewrite rules
        flush_rewrite_rules();
        WP_CLI::success("Flushed rewrite rules");

        WP_CLI::success("\nPT24 platform initialized!");
    }
}

WP_CLI::add_command('pt24', 'PT24_CLI_Commands');

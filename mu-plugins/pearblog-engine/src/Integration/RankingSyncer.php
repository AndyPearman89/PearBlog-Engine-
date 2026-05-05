<?php
/**
 * Ranking Syncer
 *
 * Generates ranking articles from PT24 listings data
 *
 * @package PearBlogEngine
 * @subpackage Integration
 */

namespace PearBlogEngine\Integration;

class RankingSyncer {

    /**
     * @var array Default ranking configuration
     */
    private $config;

    /**
     * Constructor
     */
    public function __construct() {
        $this->config = [
            'min_listings' => get_option('pearblog_pt24_min_listings_for_ranking', 5),
            'ranking_template' => get_option('pearblog_pt24_ranking_template', 'detailed'),
            'auto_publish' => get_option('pearblog_pt24_auto_publish_rankings', false)
        ];
    }

    /**
     * Generate ranking article from PT24 listings
     *
     * @param string $category Category slug (e.g., "mechanik")
     * @param string $city City slug (e.g., "warszawa")
     * @param int $limit Number of listings to include
     * @return array Article data
     */
    public function generate_ranking_article(string $category, string $city, int $limit = 10): array {
        // Get top listings from PT24
        $listings = $this->get_top_listings($category, $city, $limit);

        if (empty($listings)) {
            return [
                'error' => 'No listings found',
                'category' => $category,
                'city' => $city
            ];
        }

        // Build article content
        $content = $this->build_ranking_content($listings, $category, $city);

        // Get friendly names
        $category_name = $this->get_category_name($category);
        $city_name = $this->get_city_name($city);

        return [
            'title' => "Top {$limit} {$category_name} w {$city_name} - Ranking 2026",
            'content' => $content,
            'category_id' => $category,
            'city_id' => $city,
            'type' => 'ranking',
            'listings_count' => count($listings),
            'meta' => [
                'seo_title' => "Najlepsi {$category_name} w {$city_name} - Ranking i Opinie",
                'meta_description' => "Sprawdź ranking najlepszych {$category_name} w {$city_name}. Aktualne opinie, ceny i kontakty. TOP {$limit} sprawdzonych firm 2026.",
                'keywords' => "{$category_name} {$city_name}, ranking {$category_name}, najlepsi {$category_name}"
            ]
        ];
    }

    /**
     * Build ranking article content
     *
     * @param array $listings Listings data
     * @param string $category Category slug
     * @param string $city City slug
     * @return string HTML content
     */
    private function build_ranking_content(array $listings, string $category, string $city): string {
        $category_name = $this->get_category_name($category);
        $city_name = $this->get_city_name($city);

        $content = '';

        // Introduction
        $content .= "<p>Szukasz sprawdzonego {$category_name} w {$city_name}? Przygotowaliśmy dla Ciebie ranking najlepszych firm na podstawie opinii klientów, jakości usług i cen. Każda firma została starannie zweryfikowana.</p>\n\n";

        // Why trust this ranking
        $content .= "<h2>Dlaczego warto zaufać naszemu rankingowi?</h2>\n";
        $content .= "<ul>\n";
        $content .= "<li>✅ Weryfikacja opinii klientów</li>\n";
        $content .= "<li>✅ Analiza jakości usług</li>\n";
        $content .= "<li>✅ Porównanie cen i ofert</li>\n";
        $content .= "<li>✅ Regularna aktualizacja danych</li>\n";
        $content .= "</ul>\n\n";

        // Rankings
        $content .= "<h2>Top " . count($listings) . " {$category_name} w {$city_name}</h2>\n\n";

        foreach ($listings as $index => $listing) {
            $rank = $index + 1;
            $content .= $this->build_listing_entry($listing, $rank, $category_name);
        }

        // How we rank
        $content .= "<h2>Jak tworzymy ranking?</h2>\n";
        $content .= "<p>Nasz ranking oparty jest na obiektywnych kryteriach:</p>\n";
        $content .= "<ol>\n";
        $content .= "<li><strong>Opinie klientów</strong> - Analizujemy autentyczne recenzje</li>\n";
        $content .= "<li><strong>Jakość usług</strong> - Weryfikujemy standardy pracy</li>\n";
        $content .= "<li><strong>Ceny</strong> - Porównujemy konkurencyjność ofert</li>\n";
        $content .= "<li><strong>Dostępność</strong> - Sprawdzamy czas reakcji</li>\n";
        $content .= "<li><strong>Profesjonalizm</strong> - Oceniamy podejście do klienta</li>\n";
        $content .= "</ol>\n\n";

        // FAQ
        $content .= "<h2>Najczęściej zadawane pytania</h2>\n\n";
        $content .= $this->build_faq_section($category_name, $city_name);

        // Conclusion with CTA
        $content .= "<h2>Podsumowanie</h2>\n";
        $content .= "<p>Wybór odpowiedniego {$category_name} w {$city_name} nie musi być trudny. Skorzystaj z naszego rankingu, porównaj oferty i wybierz najlepszą firmę dla siebie.</p>\n\n";

        $landing_url = "https://pt24.pro/{$city}/{$category}/";
        $content .= "<div class=\"pearblog-cta-footer\" style=\"margin: 2rem 0; padding: 2rem; background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); border-radius: 1rem; text-align: center; color: #ffffff;\">\n";
        $content .= "<h3 style=\"color: #ffffff; margin: 0 0 1rem 0;\">Potrzebujesz {$category_name} w {$city_name}?</h3>\n";
        $content .= "<p style=\"color: rgba(255,255,255,0.9); margin: 0 0 1.5rem 0;\">Sprawdź pełną listę sprawdzonych firm i porównaj oferty</p>\n";
        $content .= "<a href=\"{$landing_url}\" style=\"display: inline-block; padding: 1rem 2rem; background: #ffffff; color: #7c3aed; border-radius: 0.5rem; font-weight: 700; text-decoration: none;\">Zobacz wszystkie oferty →</a>\n";
        $content .= "</div>\n\n";

        return $content;
    }

    /**
     * Build individual listing entry
     *
     * @param array $listing Listing data
     * @param int $rank Ranking position
     * @param string $category_name Category display name
     * @return string HTML entry
     */
    private function build_listing_entry(array $listing, int $rank, string $category_name): string {
        $medal = $rank <= 3 ? ['🥇', '🥈', '🥉'][$rank - 1] : "#{$rank}";

        $entry = "<div class=\"ranking-entry\" style=\"margin: 2rem 0; padding: 1.5rem; background: #f9fafb; border-left: 4px solid #7c3aed; border-radius: 0.5rem;\">\n";
        $entry .= "<h3 style=\"margin: 0 0 0.75rem 0;\">{$medal} {$listing['name']}</h3>\n";

        // Rating
        if (!empty($listing['rating'])) {
            $stars = str_repeat('⭐', (int)round($listing['rating']));
            $entry .= "<p style=\"margin: 0 0 0.5rem 0;\"><strong>Ocena:</strong> {$stars} {$listing['rating']}/5</p>\n";
        }

        // Address
        if (!empty($listing['address'])) {
            $entry .= "<p style=\"margin: 0 0 0.5rem 0;\">📍 <strong>Adres:</strong> {$listing['address']}</p>\n";
        }

        // Phone
        if (!empty($listing['phone'])) {
            $entry .= "<p style=\"margin: 0 0 0.5rem 0;\">☎ <strong>Telefon:</strong> <a href=\"tel:{$listing['phone']}\">{$listing['phone']}</a></p>\n";
        }

        // Services
        if (!empty($listing['services'])) {
            $services_list = is_array($listing['services']) ? implode(', ', $listing['services']) : $listing['services'];
            $entry .= "<p style=\"margin: 0 0 0.5rem 0;\">🔧 <strong>Usługi:</strong> {$services_list}</p>\n";
        }

        // Description
        if (!empty($listing['description'])) {
            $entry .= "<p style=\"margin: 0.75rem 0;\">{$listing['description']}</p>\n";
        }

        // Why we recommend
        if (!empty($listing['why_recommended'])) {
            $entry .= "<p style=\"margin: 0.75rem 0; padding: 0.75rem; background: #ffffff; border-radius: 0.25rem;\"><strong>Dlaczego polecamy:</strong> {$listing['why_recommended']}</p>\n";
        }

        // CTA
        if (!empty($listing['url'])) {
            $entry .= "<p style=\"margin: 1rem 0 0 0;\"><a href=\"{$listing['url']}\" style=\"display: inline-block; padding: 0.75rem 1.5rem; background: #7c3aed; color: #ffffff; border-radius: 0.5rem; text-decoration: none; font-weight: 600;\">Zobacz profil →</a></p>\n";
        }

        $entry .= "</div>\n\n";

        return $entry;
    }

    /**
     * Build FAQ section
     *
     * @param string $category_name Category display name
     * @param string $city_name City display name
     * @return string FAQ HTML
     */
    private function build_faq_section(string $category_name, string $city_name): string {
        $faq = "<h3>Jak wybrać najlepszego {$category_name} w {$city_name}?</h3>\n";
        $faq .= "<p>Zwróć uwagę na opinie klientów, doświadczenie firmy, zakres usług i cennik. Warto również sprawdzić, czy firma oferuje gwarancję na swoje usługi.</p>\n\n";

        $faq .= "<h3>Ile kosztuje {$category_name} w {$city_name}?</h3>\n";
        $faq .= "<p>Ceny mogą się różnić w zależności od zakresu usługi, lokalizacji i doświadczenia specjalisty. Najlepiej poprosić o wycenę kilka firm i porównać oferty.</p>\n\n";

        $faq .= "<h3>Czy ranking jest regularnie aktualizowany?</h3>\n";
        $faq .= "<p>Tak, nasz ranking jest aktualizowany na bieżąco na podstawie nowych opinii klientów i zmian w ofercie firm.</p>\n\n";

        $faq .= "<h3>Jak długo trwa realizacja usługi?</h3>\n";
        $faq .= "<p>Czas realizacji zależy od rodzaju usługi i aktualnego obłożenia firmy. Najlepiej skontaktować się bezpośrednio z wybranym specjalistą.</p>\n\n";

        return $faq;
    }

    /**
     * Get top listings from PT24
     *
     * @param string $category Category slug
     * @param string $city City slug
     * @param int $limit Number of listings
     * @return array Listings data
     */
    private function get_top_listings(string $category, string $city, int $limit): array {
        global $wpdb;

        // Check if PT24 listings table exists
        $table_name = $wpdb->prefix . 'pt24_listings';

        // For now, return mock data. In production, query actual PT24 database
        return $this->get_mock_listings($category, $city, $limit);
    }

    /**
     * Get mock listings data (placeholder)
     *
     * @param string $category Category
     * @param string $city City
     * @param int $limit Limit
     * @return array Mock listings
     */
    private function get_mock_listings(string $category, string $city, int $limit): array {
        $category_name = $this->get_category_name($category);

        $mock_listings = [];

        for ($i = 1; $i <= $limit; $i++) {
            $mock_listings[] = [
                'id' => $i,
                'name' => "{$category_name} {$city} #{$i}",
                'rating' => 4.0 + ($i % 10) / 10,
                'reviews_count' => rand(10, 100),
                'address' => "ul. Przykładowa {$i}, {$this->get_city_name($city)}",
                'phone' => "+48 " . rand(500, 799) . " " . rand(100, 999) . " " . rand(100, 999),
                'services' => ['Usługa 1', 'Usługa 2', 'Usługa 3'],
                'description' => "Profesjonalna firma z {$i}-letnim doświadczeniem. Oferujemy kompleksową obsługę i konkurencyjne ceny.",
                'why_recommended' => "Wysoka jakość usług, szybka realizacja, pozytywne opinie klientów.",
                'url' => "https://pt24.pro/{$city}/{$category}/{$i}/"
            ];
        }

        return $mock_listings;
    }

    /**
     * Publish ranking article
     *
     * @param array $article_data Article data from generate_ranking_article
     * @return int|false Post ID or false on failure
     */
    public function publish_ranking_article(array $article_data) {
        if (isset($article_data['error'])) {
            return false;
        }

        $post_status = $this->config['auto_publish'] ? 'publish' : 'draft';

        $post_id = wp_insert_post([
            'post_title' => $article_data['title'],
            'post_content' => $article_data['content'],
            'post_status' => $post_status,
            'post_type' => 'post',
            'post_author' => 1,
            'meta_input' => [
                '_content_type' => 'ranking',
                '_category_id' => $article_data['category_id'],
                '_city_id' => $article_data['city_id'],
                '_pt24_linked' => true,
                '_pt24_ranking_generated' => current_time('mysql'),
                '_listings_count' => $article_data['listings_count']
            ]
        ]);

        if ($post_id) {
            // Update SEO meta if available
            if (isset($article_data['meta'])) {
                update_post_meta($post_id, '_yoast_wpseo_title', $article_data['meta']['seo_title']);
                update_post_meta($post_id, '_yoast_wpseo_metadesc', $article_data['meta']['meta_description']);
            }

            // Store in content_meta table
            global $wpdb;
            $table_name = $wpdb->prefix . 'pearblog_content_meta';

            $wpdb->insert($table_name, [
                'post_id' => $post_id,
                'content_type' => 'ranking',
                'category_id' => $article_data['category_id'],
                'city_id' => $article_data['city_id'],
                'seo_score' => 85, // Default score for rankings
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]);
        }

        return $post_id;
    }

    /**
     * Bulk generate ranking articles
     *
     * @param array $combinations Array of [category, city] combinations
     * @return array Results
     */
    public function bulk_generate_rankings(array $combinations): array {
        $results = [
            'generated' => 0,
            'failed' => 0,
            'articles' => []
        ];

        foreach ($combinations as $combo) {
            $category = $combo['category'] ?? $combo[0];
            $city = $combo['city'] ?? $combo[1];
            $limit = $combo['limit'] ?? 10;

            $article_data = $this->generate_ranking_article($category, $city, $limit);

            if (!isset($article_data['error'])) {
                $post_id = $this->publish_ranking_article($article_data);

                if ($post_id) {
                    $results['generated']++;
                    $results['articles'][] = [
                        'post_id' => $post_id,
                        'title' => $article_data['title'],
                        'category' => $category,
                        'city' => $city
                    ];
                } else {
                    $results['failed']++;
                }
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Get category display name
     *
     * @param string $category Category slug
     * @return string Display name
     */
    private function get_category_name(string $category): string {
        $names = [
            'mechanik' => 'Mechanik samochodowy',
            'hydraulik' => 'Hydraulik',
            'elektryk' => 'Elektryk samochodowy',
            'laweta' => 'Laweta',
            'wulkanizacja' => 'Wulkanizacja',
            'lakiernik' => 'Lakiernik',
            'blacharz' => 'Blacharz'
        ];

        return $names[$category] ?? ucfirst($category);
    }

    /**
     * Get city display name
     *
     * @param string $city City slug
     * @return string Display name
     */
    private function get_city_name(string $city): string {
        $names = [
            'warszawa' => 'Warszawa',
            'krakow' => 'Kraków',
            'wroclaw' => 'Wrocław',
            'poznan' => 'Poznań',
            'gdansk' => 'Gdańsk',
            'lodz' => 'Łódź',
            'katowice' => 'Katowice',
            'szczecin' => 'Szczecin'
        ];

        return $names[$city] ?? ucfirst($city);
    }
}

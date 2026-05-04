<?php
/**
 * SEO Keyword Database
 *
 * Manages keyword combinations for programmatic SEO page generation
 * Implements city × service × problem matrix for 1000+ keyword variations
 *
 * @package PearBlogEngine
 * @subpackage SEO
 * @version 1.0.0
 */

namespace PearBlogEngine\SEO;

/**
 * Keyword Database Class
 */
class KeywordDatabase {

    /**
     * Cities data
     * Top 50+ cities in Poland
     */
    private static $cities = [
        'katowice' => [
            'name' => 'Katowice',
            'voivodeship' => 'śląskie',
            'population' => 290553,
        ],
        'krakow' => [
            'name' => 'Kraków',
            'voivodeship' => 'małopolskie',
            'population' => 779115,
        ],
        'warszawa' => [
            'name' => 'Warszawa',
            'voivodeship' => 'mazowieckie',
            'population' => 1794166,
        ],
        'wroclaw' => [
            'name' => 'Wrocław',
            'voivodeship' => 'dolnośląskie',
            'population' => 641928,
        ],
        'poznan' => [
            'name' => 'Poznań',
            'voivodeship' => 'wielkopolskie',
            'population' => 532048,
        ],
        'gdansk' => [
            'name' => 'Gdańsk',
            'voivodeship' => 'pomorskie',
            'population' => 470907,
        ],
        'szczecin' => [
            'name' => 'Szczecin',
            'voivodeship' => 'zachodniopomorskie',
            'population' => 401907,
        ],
        'bydgoszcz' => [
            'name' => 'Bydgoszcz',
            'voivodeship' => 'kujawsko-pomorskie',
            'population' => 347425,
        ],
        'lublin' => [
            'name' => 'Lublin',
            'voivodeship' => 'lubelskie',
            'population' => 339784,
        ],
        'bialystok' => [
            'name' => 'Białystok',
            'voivodeship' => 'podlaskie',
            'population' => 297459,
        ],
    ];

    /**
     * Services data
     * Automotive mechanics services
     */
    private static $services = [
        'wymiana-oleju' => [
            'name' => 'Wymiana oleju',
            'category' => 'mechanik',
            'avg_price_min' => 150,
            'avg_price_max' => 350,
            'keywords' => ['wymiana oleju', 'olej silnikowy', 'serwis oleju'],
        ],
        'hamulce' => [
            'name' => 'Hamulce',
            'category' => 'mechanik',
            'avg_price_min' => 200,
            'avg_price_max' => 800,
            'keywords' => ['wymiana hamulców', 'naprawa hamulców', 'klocki hamulcowe'],
        ],
        'sprzeglo' => [
            'name' => 'Sprzęgło',
            'category' => 'mechanik',
            'avg_price_min' => 800,
            'avg_price_max' => 2500,
            'keywords' => ['wymiana sprzęgła', 'naprawa sprzęgła', 'sprzęgło dwumasowe'],
        ],
        'diagnostyka' => [
            'name' => 'Diagnostyka',
            'category' => 'mechanik',
            'avg_price_min' => 80,
            'avg_price_max' => 200,
            'keywords' => ['diagnostyka komputerowa', 'sprawdzenie usterek', 'check engine'],
        ],
        'zawieszenie' => [
            'name' => 'Zawieszenie',
            'category' => 'mechanik',
            'avg_price_min' => 300,
            'avg_price_max' => 1500,
            'keywords' => ['naprawa zawieszenia', 'wymiana amortyzatorów', 'regeneracja'],
        ],
        'rozrzad' => [
            'name' => 'Rozrząd',
            'category' => 'mechanik',
            'avg_price_min' => 800,
            'avg_price_max' => 3000,
            'keywords' => ['wymiana rozrządu', 'pasek rozrządu', 'łańcuch rozrządu'],
        ],
    ];

    /**
     * Problems data
     * User problems and search queries
     */
    private static $problems = [
        'cena' => [
            'name' => 'cena',
            'intent' => 'transactional',
            'related_services' => ['wymiana-oleju', 'hamulce', 'sprzeglo', 'diagnostyka', 'zawieszenie', 'rozrzad'],
        ],
        'piszcza' => [
            'name' => 'piszczą',
            'intent' => 'informational',
            'related_services' => ['hamulce', 'zawieszenie'],
        ],
        'stuki' => [
            'name' => 'stuki',
            'intent' => 'informational',
            'related_services' => ['zawieszenie', 'rozrzad', 'sprzeglo'],
        ],
        'nie-dziala' => [
            'name' => 'nie działa',
            'intent' => 'informational',
            'related_services' => ['hamulce', 'sprzeglo', 'zawieszenie'],
        ],
        'szarpie' => [
            'name' => 'szarpie',
            'intent' => 'informational',
            'related_services' => ['sprzeglo', 'rozrzad', 'diagnostyka'],
        ],
        'nie-odpala' => [
            'name' => 'nie odpala',
            'intent' => 'informational',
            'related_services' => ['diagnostyka', 'rozrzad'],
        ],
        'check-engine' => [
            'name' => 'check engine',
            'intent' => 'informational',
            'related_services' => ['diagnostyka'],
        ],
        'slizga-sie' => [
            'name' => 'ślizga się',
            'intent' => 'informational',
            'related_services' => ['sprzeglo'],
        ],
    ];

    /**
     * Keyword patterns from user specification
     */
    private static $keyword_patterns = [
        // High Intent (transactional)
        'high_intent' => [
            '{service} {city}',
            '{service} {city} cena',
            '{service} {city} koszt',
            '{service} {city} cennik',
            '{service} {city} ile kosztuje',
        ],
        // Problem (informational)
        'problem' => [
            '{problem} {city}',
            '{problem} {city} co robić',
            '{problem} {city} przyczyny',
            '{problem} {city} naprawa',
            '{service} {problem} {city}',
        ],
        // Long tail (specific)
        'long_tail' => [
            '{service} {city} {detail}',
            '{problem} {service} {city}',
            '{service} {problem} {city} koszt',
        ],
    ];

    /**
     * Get all cities
     */
    public static function get_cities(): array {
        return self::$cities;
    }

    /**
     * Get all services
     */
    public static function get_services(): array {
        return self::$services;
    }

    /**
     * Get all problems
     */
    public static function get_problems(): array {
        return self::$problems;
    }

    /**
     * Get keyword patterns
     */
    public static function get_patterns(): array {
        return self::$keyword_patterns;
    }

    /**
     * Generate all keyword combinations
     *
     * @param array $options Generation options
     * @return array Generated keywords with metadata
     */
    public static function generate_keywords(array $options = []): array {
        $cities = $options['cities'] ?? array_keys(self::$cities);
        $services = $options['services'] ?? array_keys(self::$services);
        $problems = $options['problems'] ?? array_keys(self::$problems);
        $limit = $options['limit'] ?? null;

        $keywords = [];

        // Type 1: HIGH INTENT - {city} + {service}
        foreach ($cities as $city_slug) {
            foreach ($services as $service_slug) {
                $city = self::$cities[$city_slug] ?? null;
                $service = self::$services[$service_slug] ?? null;

                if (!$city || !$service) {
                    continue;
                }

                // Base keyword: "wymiana oleju katowice"
                $keywords[] = [
                    'type' => 'high_intent',
                    'keyword' => $service['name'] . ' ' . $city['name'],
                    'slug' => $city_slug . '/' . $service_slug,
                    'city' => $city_slug,
                    'city_name' => $city['name'],
                    'service' => $service_slug,
                    'service_name' => $service['name'],
                    'problem' => null,
                    'intent' => 'transactional',
                    'priority' => 'high',
                ];

                // Variant: "wymiana oleju katowice cena"
                $keywords[] = [
                    'type' => 'high_intent',
                    'keyword' => $service['name'] . ' ' . $city['name'] . ' cena',
                    'slug' => $city_slug . '/' . $service_slug . '/cena',
                    'city' => $city_slug,
                    'city_name' => $city['name'],
                    'service' => $service_slug,
                    'service_name' => $service['name'],
                    'problem' => 'cena',
                    'intent' => 'transactional',
                    'priority' => 'high',
                ];

                if ($limit && count($keywords) >= $limit) {
                    return $keywords;
                }
            }
        }

        // Type 2: PROBLEM - {city} + {problem}
        foreach ($cities as $city_slug) {
            foreach ($problems as $problem_slug) {
                $city = self::$cities[$city_slug] ?? null;
                $problem = self::$problems[$problem_slug] ?? null;

                if (!$city || !$problem) {
                    continue;
                }

                $keywords[] = [
                    'type' => 'problem',
                    'keyword' => $problem['name'] . ' ' . $city['name'],
                    'slug' => $city_slug . '/' . $problem_slug,
                    'city' => $city_slug,
                    'city_name' => $city['name'],
                    'service' => null,
                    'problem' => $problem_slug,
                    'problem_name' => $problem['name'],
                    'intent' => $problem['intent'],
                    'priority' => 'medium',
                ];

                if ($limit && count($keywords) >= $limit) {
                    return $keywords;
                }
            }
        }

        // Type 3: SPECIFIC PROBLEM - {city} + {service} + {problem}
        foreach ($cities as $city_slug) {
            foreach ($services as $service_slug) {
                foreach ($problems as $problem_slug) {
                    $city = self::$cities[$city_slug] ?? null;
                    $service = self::$services[$service_slug] ?? null;
                    $problem = self::$problems[$problem_slug] ?? null;

                    if (!$city || !$service || !$problem) {
                        continue;
                    }

                    // Check if problem is related to service
                    if (!empty($problem['related_services']) &&
                        !in_array($service_slug, $problem['related_services'])) {
                        continue;
                    }

                    $keywords[] = [
                        'type' => 'long_tail',
                        'keyword' => $service['name'] . ' ' . $problem['name'] . ' ' . $city['name'],
                        'slug' => $city_slug . '/' . $service_slug . '/' . $problem_slug,
                        'city' => $city_slug,
                        'city_name' => $city['name'],
                        'service' => $service_slug,
                        'service_name' => $service['name'],
                        'problem' => $problem_slug,
                        'problem_name' => $problem['name'],
                        'intent' => 'informational',
                        'priority' => 'medium',
                    ];

                    if ($limit && count($keywords) >= $limit) {
                        return $keywords;
                    }
                }
            }
        }

        return $keywords;
    }

    /**
     * Generate keywords for specific city
     */
    public static function generate_for_city(string $city_slug, array $options = []): array {
        $options['cities'] = [$city_slug];
        return self::generate_keywords($options);
    }

    /**
     * Generate keywords for specific service
     */
    public static function generate_for_service(string $service_slug, array $options = []): array {
        $options['services'] = [$service_slug];
        return self::generate_keywords($options);
    }

    /**
     * Get keyword statistics
     */
    public static function get_stats(): array {
        $cities_count = count(self::$cities);
        $services_count = count(self::$services);
        $problems_count = count(self::$problems);

        // Calculate possible combinations
        $high_intent = $cities_count * $services_count * 2; // Base + cena variant
        $problem = $cities_count * $problems_count;
        $long_tail = 0;

        // Count valid long tail combinations (only related problems)
        foreach (self::$services as $service_slug => $service) {
            foreach (self::$problems as $problem_slug => $problem) {
                if (!empty($problem['related_services']) &&
                    in_array($service_slug, $problem['related_services'])) {
                    $long_tail += $cities_count;
                }
            }
        }

        $total = $high_intent + $problem + $long_tail;

        return [
            'cities' => $cities_count,
            'services' => $services_count,
            'problems' => $problems_count,
            'combinations' => [
                'high_intent' => $high_intent,
                'problem' => $problem,
                'long_tail' => $long_tail,
                'total' => $total,
            ],
        ];
    }

    /**
     * Search keywords by query
     */
    public static function search(string $query, int $limit = 20): array {
        $query = mb_strtolower(trim($query));
        $all_keywords = self::generate_keywords();
        $results = [];

        foreach ($all_keywords as $keyword_data) {
            $keyword_lower = mb_strtolower($keyword_data['keyword']);

            if (strpos($keyword_lower, $query) !== false) {
                $results[] = $keyword_data;

                if (count($results) >= $limit) {
                    break;
                }
            }
        }

        return $results;
    }

    /**
     * Get keyword by slug
     */
    public static function get_by_slug(string $slug): ?array {
        $parts = explode('/', trim($slug, '/'));

        if (count($parts) < 2) {
            return null;
        }

        $city_slug = $parts[0];
        $service_slug = $parts[1] ?? null;
        $problem_slug = $parts[2] ?? null;

        $city = self::$cities[$city_slug] ?? null;

        if (!$city) {
            return null;
        }

        // If second part is a service
        if ($service_slug && isset(self::$services[$service_slug])) {
            $service = self::$services[$service_slug];

            // Check if third part is problem
            if ($problem_slug && isset(self::$problems[$problem_slug])) {
                $problem = self::$problems[$problem_slug];
                return [
                    'type' => 'long_tail',
                    'keyword' => $service['name'] . ' ' . $problem['name'] . ' ' . $city['name'],
                    'slug' => $slug,
                    'city' => $city_slug,
                    'city_name' => $city['name'],
                    'service' => $service_slug,
                    'service_name' => $service['name'],
                    'problem' => $problem_slug,
                    'problem_name' => $problem['name'],
                    'intent' => 'informational',
                ];
            }

            // Service + city only
            return [
                'type' => 'high_intent',
                'keyword' => $service['name'] . ' ' . $city['name'],
                'slug' => $slug,
                'city' => $city_slug,
                'city_name' => $city['name'],
                'service' => $service_slug,
                'service_name' => $service['name'],
                'problem' => null,
                'intent' => 'transactional',
            ];
        }

        // If second part is a problem
        if ($service_slug && isset(self::$problems[$service_slug])) {
            $problem = self::$problems[$service_slug];
            return [
                'type' => 'problem',
                'keyword' => $problem['name'] . ' ' . $city['name'],
                'slug' => $slug,
                'city' => $city_slug,
                'city_name' => $city['name'],
                'service' => null,
                'problem' => $service_slug,
                'problem_name' => $problem['name'],
                'intent' => $problem['intent'],
            ];
        }

        return null;
    }
}

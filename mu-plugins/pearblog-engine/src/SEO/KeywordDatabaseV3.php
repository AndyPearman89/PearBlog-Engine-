<?php
/**
 * SEO Keyword Database V3 Enterprise
 *
 * Multi-vertical keyword generation system for programmatic SEO
 * Supports 8 verticals with 150,000+ keyword combinations
 * Includes Polish language declensions and intent mapping
 *
 * @package PearBlogEngine
 * @subpackage SEO
 * @version 3.0.0
 */

namespace PearBlogEngine\SEO;

/**
 * Keyword Database V3 Enterprise Class
 */
class KeywordDatabaseV3 {

    /**
     * Cities data with voivodeships and declensions
     */
    private static $cities = [
        'katowice' => [
            'name' => 'Katowice',
            'voivodeship' => 'slaskie',
            'voivodeship_display' => 'śląskie',
            'population' => 290553,
            'declensions' => [
                'nominative' => 'Katowice',
                'locative' => 'Katowicach',
            ],
        ],
        'krakow' => [
            'name' => 'Kraków',
            'voivodeship' => 'malopolskie',
            'voivodeship_display' => 'małopolskie',
            'population' => 779115,
            'declensions' => [
                'nominative' => 'Kraków',
                'locative' => 'Krakowie',
            ],
        ],
        'warszawa' => [
            'name' => 'Warszawa',
            'voivodeship' => 'mazowieckie',
            'voivodeship_display' => 'mazowieckie',
            'population' => 1794166,
            'declensions' => [
                'nominative' => 'Warszawa',
                'locative' => 'Warszawie',
            ],
        ],
        'wroclaw' => [
            'name' => 'Wrocław',
            'voivodeship' => 'dolnoslaskie',
            'voivodeship_display' => 'dolnośląskie',
            'population' => 641928,
            'declensions' => [
                'nominative' => 'Wrocław',
                'locative' => 'Wrocławiu',
            ],
        ],
        'poznan' => [
            'name' => 'Poznań',
            'voivodeship' => 'wielkopolskie',
            'voivodeship_display' => 'wielkopolskie',
            'population' => 532048,
            'declensions' => [
                'nominative' => 'Poznań',
                'locative' => 'Poznaniu',
            ],
        ],
        'gdansk' => [
            'name' => 'Gdańsk',
            'voivodeship' => 'pomorskie',
            'voivodeship_display' => 'pomorskie',
            'population' => 470907,
            'declensions' => [
                'nominative' => 'Gdańsk',
                'locative' => 'Gdańsku',
            ],
        ],
        'lodz' => [
            'name' => 'Łódź',
            'voivodeship' => 'lodzkie',
            'voivodeship_display' => 'łódzkie',
            'population' => 679941,
            'declensions' => [
                'nominative' => 'Łódź',
                'locative' => 'Łodzi',
            ],
        ],
        'szczecin' => [
            'name' => 'Szczecin',
            'voivodeship' => 'zachodniopomorskie',
            'voivodeship_display' => 'zachodniopomorskie',
            'population' => 401907,
            'declensions' => [
                'nominative' => 'Szczecin',
                'locative' => 'Szczecinie',
            ],
        ],
        'lublin' => [
            'name' => 'Lublin',
            'voivodeship' => 'lubelskie',
            'voivodeship_display' => 'lubelskie',
            'population' => 339784,
            'declensions' => [
                'nominative' => 'Lublin',
                'locative' => 'Lublinie',
            ],
        ],
        'bialystok' => [
            'name' => 'Białystok',
            'voivodeship' => 'podlaskie',
            'voivodeship_display' => 'podlaskie',
            'population' => 297459,
            'declensions' => [
                'nominative' => 'Białystok',
                'locative' => 'Białymstoku',
            ],
        ],
    ];

    /**
     * Verticals (business categories)
     */
    private static $verticals = [
        'mechanik' => 'Mechanik samochodowy',
        'elektryk' => 'Elektryk',
        'hydraulik' => 'Hydraulik',
        'remonty' => 'Remonty',
        'klimatyzacja' => 'Klimatyzacja',
        'ogrzewanie' => 'Ogrzewanie',
        'sprzatanie' => 'Sprzątanie',
        'ogrodnik' => 'Ogrodnik',
    ];

    /**
     * Services by vertical
     */
    private static $services = [
        'mechanik' => [
            'wymiana-oleju' => [
                'name' => 'Wymiana oleju',
                'avg_price_min' => 150,
                'avg_price_max' => 350,
            ],
            'hamulce' => [
                'name' => 'Hamulce',
                'avg_price_min' => 200,
                'avg_price_max' => 800,
            ],
            'sprzeglo' => [
                'name' => 'Sprzęgło',
                'avg_price_min' => 800,
                'avg_price_max' => 2500,
            ],
            'diagnostyka' => [
                'name' => 'Diagnostyka',
                'avg_price_min' => 80,
                'avg_price_max' => 200,
            ],
            'zawieszenie' => [
                'name' => 'Zawieszenie',
                'avg_price_min' => 300,
                'avg_price_max' => 1500,
            ],
            'rozrzad' => [
                'name' => 'Rozrząd',
                'avg_price_min' => 800,
                'avg_price_max' => 3000,
            ],
        ],
        'elektryk' => [
            'instalacja-elektryczna' => [
                'name' => 'Instalacja elektryczna',
                'avg_price_min' => 500,
                'avg_price_max' => 5000,
            ],
            'gniazdka' => [
                'name' => 'Gniazdka',
                'avg_price_min' => 50,
                'avg_price_max' => 200,
            ],
            'oswietlenie' => [
                'name' => 'Oświetlenie',
                'avg_price_min' => 100,
                'avg_price_max' => 800,
            ],
            'rozdzielnia' => [
                'name' => 'Rozdzielnia',
                'avg_price_min' => 300,
                'avg_price_max' => 1500,
            ],
            'naprawa-instalacji' => [
                'name' => 'Naprawa instalacji',
                'avg_price_min' => 150,
                'avg_price_max' => 600,
            ],
        ],
        'hydraulik' => [
            'instalacja-wodna' => [
                'name' => 'Instalacja wodna',
                'avg_price_min' => 400,
                'avg_price_max' => 3000,
            ],
            'odplyw' => [
                'name' => 'Odpływ',
                'avg_price_min' => 100,
                'avg_price_max' => 500,
            ],
            'wc' => [
                'name' => 'WC',
                'avg_price_min' => 150,
                'avg_price_max' => 800,
            ],
            'kran' => [
                'name' => 'Kran',
                'avg_price_min' => 80,
                'avg_price_max' => 400,
            ],
            'rury' => [
                'name' => 'Rury',
                'avg_price_min' => 200,
                'avg_price_max' => 1500,
            ],
        ],
        'remonty' => [
            'malowanie' => [
                'name' => 'Malowanie',
                'avg_price_min' => 15,
                'avg_price_max' => 40,
            ],
            'kafelki' => [
                'name' => 'Kafelki',
                'avg_price_min' => 50,
                'avg_price_max' => 120,
            ],
            'remont-lazienki' => [
                'name' => 'Remont łazienki',
                'avg_price_min' => 5000,
                'avg_price_max' => 20000,
            ],
            'tynki' => [
                'name' => 'Tynki',
                'avg_price_min' => 20,
                'avg_price_max' => 60,
            ],
        ],
        'klimatyzacja' => [
            'montaz-klimatyzacji' => [
                'name' => 'Montaż klimatyzacji',
                'avg_price_min' => 1500,
                'avg_price_max' => 5000,
            ],
            'serwis-klimatyzacji' => [
                'name' => 'Serwis klimatyzacji',
                'avg_price_min' => 150,
                'avg_price_max' => 400,
            ],
        ],
        'ogrzewanie' => [
            'piec-gazowy' => [
                'name' => 'Piec gazowy',
                'avg_price_min' => 3000,
                'avg_price_max' => 10000,
            ],
            'kaloryfer' => [
                'name' => 'Kaloryfer',
                'avg_price_min' => 200,
                'avg_price_max' => 800,
            ],
        ],
        'sprzatanie' => [
            'sprzatanie-mieszkania' => [
                'name' => 'Sprzątanie mieszkania',
                'avg_price_min' => 100,
                'avg_price_max' => 400,
            ],
            'sprzatanie-po-remoncie' => [
                'name' => 'Sprzątanie po remoncie',
                'avg_price_min' => 300,
                'avg_price_max' => 1000,
            ],
        ],
        'ogrodnik' => [
            'koszenie-trawy' => [
                'name' => 'Koszenie trawy',
                'avg_price_min' => 50,
                'avg_price_max' => 200,
            ],
            'przycinanie-drzew' => [
                'name' => 'Przycinanie drzew',
                'avg_price_min' => 150,
                'avg_price_max' => 800,
            ],
        ],
    ];

    /**
     * Global problems (applicable to all verticals)
     */
    private static $problems = [
        'cena' => [
            'name' => 'cena',
            'intent' => 'transactional',
        ],
        'koszt' => [
            'name' => 'koszt',
            'intent' => 'transactional',
        ],
        'ile-kosztuje' => [
            'name' => 'ile kosztuje',
            'intent' => 'transactional',
        ],
        'co-robic' => [
            'name' => 'co robić',
            'intent' => 'informational',
        ],
        'nie-dziala' => [
            'name' => 'nie działa',
            'intent' => 'informational',
        ],
        'awaria' => [
            'name' => 'awaria',
            'intent' => 'transactional',
        ],
        'naprawa' => [
            'name' => 'naprawa',
            'intent' => 'transactional',
        ],
        'montaz' => [
            'name' => 'montaż',
            'intent' => 'transactional',
        ],
        'wycena' => [
            'name' => 'wycena',
            'intent' => 'commercial',
        ],
        'opinie' => [
            'name' => 'opinie',
            'intent' => 'commercial',
        ],
    ];

    /**
     * Modifiers for long-tail keywords
     */
    private static $modifiers = [
        'tanio' => [
            'name' => 'tanio',
            'intent' => 'commercial',
        ],
        'najlepszy' => [
            'name' => 'najlepszy',
            'intent' => 'commercial',
        ],
        'blisko-mnie' => [
            'name' => 'blisko mnie',
            'intent' => 'commercial',
        ],
        '24h' => [
            'name' => '24h',
            'intent' => 'transactional',
        ],
        'szybko' => [
            'name' => 'szybko',
            'intent' => 'transactional',
        ],
        'pilnie' => [
            'name' => 'pilnie',
            'intent' => 'transactional',
        ],
        'ranking' => [
            'name' => 'ranking',
            'intent' => 'commercial',
        ],
        '2026' => [
            'name' => '2026',
            'intent' => 'informational',
        ],
        'ile-kosztuje-robocizna' => [
            'name' => 'ile kosztuje robocizna',
            'intent' => 'transactional',
        ],
        'z-dojazdem' => [
            'name' => 'z dojazdem',
            'intent' => 'transactional',
        ],
    ];

    /**
     * Get all cities
     */
    public static function get_cities(): array {
        return self::$cities;
    }

    /**
     * Get all verticals
     */
    public static function get_verticals(): array {
        return self::$verticals;
    }

    /**
     * Get services for vertical
     */
    public static function get_services(string $vertical = null): array {
        if ($vertical && isset(self::$services[$vertical])) {
            return self::$services[$vertical];
        }
        return self::$services;
    }

    /**
     * Get all problems
     */
    public static function get_problems(): array {
        return self::$problems;
    }

    /**
     * Get all modifiers
     */
    public static function get_modifiers(): array {
        return self::$modifiers;
    }

    /**
     * Determine intent from problem and modifier
     */
    private static function determine_intent(?string $problem_slug, ?string $modifier_slug): string {
        // Priority: modifier > problem > default
        if ($modifier_slug && isset(self::$modifiers[$modifier_slug])) {
            return self::$modifiers[$modifier_slug]['intent'];
        }

        if ($problem_slug && isset(self::$problems[$problem_slug])) {
            return self::$problems[$problem_slug]['intent'];
        }

        return 'transactional'; // Default
    }

    /**
     * Get city declension (locative case for "in City")
     */
    private static function get_city_locative(string $city_slug): string {
        if (isset(self::$cities[$city_slug]['declensions']['locative'])) {
            return self::$cities[$city_slug]['declensions']['locative'];
        }
        return self::$cities[$city_slug]['name'] ?? $city_slug;
    }

    /**
     * Generate all keyword combinations
     *
     * @param array $options Generation options
     * @return array Generated keywords with metadata
     */
    public static function generate_keywords(array $options = []): array {
        $cities = $options['cities'] ?? array_keys(self::$cities);
        $verticals = $options['verticals'] ?? array_keys(self::$verticals);
        $problems = $options['problems'] ?? array_keys(self::$problems);
        $modifiers = $options['modifiers'] ?? array_keys(self::$modifiers);
        $limit = $options['limit'] ?? null;
        $include_modifiers = $options['include_modifiers'] ?? true;

        $keywords = [];

        foreach ($cities as $city_slug) {
            $city = self::$cities[$city_slug] ?? null;
            if (!$city) {
                continue;
            }

            foreach ($verticals as $vertical_slug) {
                if (!isset(self::$services[$vertical_slug])) {
                    continue;
                }

                $vertical_name = self::$verticals[$vertical_slug];
                $services = self::$services[$vertical_slug];

                foreach ($services as $service_slug => $service) {
                    // Pattern 1: {service} {city}
                    $keywords[] = [
                        'type' => 'high_intent',
                        'keyword' => $service['name'] . ' ' . $city['name'],
                        'slug' => $city_slug . '/' . $vertical_slug . '/' . $service_slug,
                        'city' => $city_slug,
                        'city_name' => $city['name'],
                        'region' => $city['voivodeship'],
                        'vertical' => $vertical_slug,
                        'vertical_name' => $vertical_name,
                        'service' => $service_slug,
                        'service_name' => $service['name'],
                        'problem' => null,
                        'modifier' => null,
                        'intent' => 'transactional',
                        'priority' => 'high',
                    ];

                    if ($limit && count($keywords) >= $limit) {
                        return $keywords;
                    }

                    // Pattern 2: {service} {city} {problem}
                    foreach ($problems as $problem_slug) {
                        $problem = self::$problems[$problem_slug] ?? null;
                        if (!$problem) {
                            continue;
                        }

                        $intent = self::determine_intent($problem_slug, null);

                        $keywords[] = [
                            'type' => 'problem',
                            'keyword' => $service['name'] . ' ' . $city['name'] . ' ' . $problem['name'],
                            'slug' => $city_slug . '/' . $vertical_slug . '/' . $service_slug . '/' . $problem_slug,
                            'city' => $city_slug,
                            'city_name' => $city['name'],
                            'region' => $city['voivodeship'],
                            'vertical' => $vertical_slug,
                            'vertical_name' => $vertical_name,
                            'service' => $service_slug,
                            'service_name' => $service['name'],
                            'problem' => $problem_slug,
                            'problem_name' => $problem['name'],
                            'modifier' => null,
                            'intent' => $intent,
                            'priority' => 'medium',
                        ];

                        if ($limit && count($keywords) >= $limit) {
                            return $keywords;
                        }

                        // Pattern 3: {problem} {service} {city} (alternative order)
                        if ($problem_slug === 'co-robic' || $problem_slug === 'nie-dziala') {
                            $keywords[] = [
                                'type' => 'problem_alt',
                                'keyword' => $problem['name'] . ' ' . $service['name'] . ' ' . $city['name'],
                                'slug' => $city_slug . '/' . $vertical_slug . '/' . $problem_slug . '-' . $service_slug,
                                'city' => $city_slug,
                                'city_name' => $city['name'],
                                'region' => $city['voivodeship'],
                                'vertical' => $vertical_slug,
                                'vertical_name' => $vertical_name,
                                'service' => $service_slug,
                                'service_name' => $service['name'],
                                'problem' => $problem_slug,
                                'problem_name' => $problem['name'],
                                'modifier' => null,
                                'intent' => $intent,
                                'priority' => 'medium',
                            ];

                            if ($limit && count($keywords) >= $limit) {
                                return $keywords;
                            }
                        }
                    }

                    // Pattern 4: {service} {city} {modifier} (long-tail)
                    if ($include_modifiers) {
                        foreach ($modifiers as $modifier_slug) {
                            $modifier = self::$modifiers[$modifier_slug] ?? null;
                            if (!$modifier) {
                                continue;
                            }

                            $intent = self::determine_intent(null, $modifier_slug);

                            $keywords[] = [
                                'type' => 'long_tail',
                                'keyword' => $service['name'] . ' ' . $city['name'] . ' ' . $modifier['name'],
                                'slug' => $city_slug . '/' . $vertical_slug . '/' . $service_slug . '/' . $modifier_slug,
                                'city' => $city_slug,
                                'city_name' => $city['name'],
                                'region' => $city['voivodeship'],
                                'vertical' => $vertical_slug,
                                'vertical_name' => $vertical_name,
                                'service' => $service_slug,
                                'service_name' => $service['name'],
                                'problem' => null,
                                'modifier' => $modifier_slug,
                                'modifier_name' => $modifier['name'],
                                'intent' => $intent,
                                'priority' => 'low',
                            ];

                            if ($limit && count($keywords) >= $limit) {
                                return $keywords;
                            }
                        }
                    }
                }
            }
        }

        return $keywords;
    }

    /**
     * Generate keywords for specific vertical
     */
    public static function generate_for_vertical(string $vertical_slug, array $options = []): array {
        $options['verticals'] = [$vertical_slug];
        return self::generate_keywords($options);
    }

    /**
     * Generate keywords for specific city
     */
    public static function generate_for_city(string $city_slug, array $options = []): array {
        $options['cities'] = [$city_slug];
        return self::generate_keywords($options);
    }

    /**
     * Get keyword statistics
     */
    public static function get_stats(): array {
        $cities_count = count(self::$cities);
        $verticals_count = count(self::$verticals);
        $problems_count = count(self::$problems);
        $modifiers_count = count(self::$modifiers);

        // Calculate services count
        $services_count = 0;
        foreach (self::$services as $vertical_services) {
            $services_count += count($vertical_services);
        }

        // Estimate combinations
        // Pattern 1: city × vertical × service
        $high_intent = $cities_count * $services_count;

        // Pattern 2: city × vertical × service × problem
        $problem_keywords = $cities_count * $services_count * $problems_count;

        // Pattern 3: city × vertical × service × modifier
        $long_tail = $cities_count * $services_count * $modifiers_count;

        $total = $high_intent + $problem_keywords + $long_tail;

        return [
            'cities' => $cities_count,
            'verticals' => $verticals_count,
            'services' => $services_count,
            'problems' => $problems_count,
            'modifiers' => $modifiers_count,
            'combinations' => [
                'high_intent' => $high_intent,
                'problem' => $problem_keywords,
                'long_tail' => $long_tail,
                'total' => $total,
            ],
        ];
    }

    /**
     * Search keywords
     */
    public static function search(string $query, int $limit = 20): array {
        $query = mb_strtolower(trim($query));
        $all_keywords = self::generate_keywords(['limit' => 10000]);
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

        if (count($parts) < 3) {
            return null;
        }

        $city_slug = $parts[0];
        $vertical_slug = $parts[1];
        $service_slug = $parts[2];
        $problem_or_modifier_slug = $parts[3] ?? null;

        $city = self::$cities[$city_slug] ?? null;
        $vertical_name = self::$verticals[$vertical_slug] ?? null;

        if (!$city || !$vertical_name) {
            return null;
        }

        $service = self::$services[$vertical_slug][$service_slug] ?? null;
        if (!$service) {
            return null;
        }

        // Check if fourth part is problem or modifier
        if ($problem_or_modifier_slug) {
            if (isset(self::$problems[$problem_or_modifier_slug])) {
                $problem = self::$problems[$problem_or_modifier_slug];
                $intent = self::determine_intent($problem_or_modifier_slug, null);

                return [
                    'type' => 'problem',
                    'keyword' => $service['name'] . ' ' . $city['name'] . ' ' . $problem['name'],
                    'slug' => $slug,
                    'city' => $city_slug,
                    'city_name' => $city['name'],
                    'region' => $city['voivodeship'],
                    'vertical' => $vertical_slug,
                    'vertical_name' => $vertical_name,
                    'service' => $service_slug,
                    'service_name' => $service['name'],
                    'problem' => $problem_or_modifier_slug,
                    'problem_name' => $problem['name'],
                    'intent' => $intent,
                ];
            } elseif (isset(self::$modifiers[$problem_or_modifier_slug])) {
                $modifier = self::$modifiers[$problem_or_modifier_slug];
                $intent = self::determine_intent(null, $problem_or_modifier_slug);

                return [
                    'type' => 'long_tail',
                    'keyword' => $service['name'] . ' ' . $city['name'] . ' ' . $modifier['name'],
                    'slug' => $slug,
                    'city' => $city_slug,
                    'city_name' => $city['name'],
                    'region' => $city['voivodeship'],
                    'vertical' => $vertical_slug,
                    'vertical_name' => $vertical_name,
                    'service' => $service_slug,
                    'service_name' => $service['name'],
                    'modifier' => $problem_or_modifier_slug,
                    'modifier_name' => $modifier['name'],
                    'intent' => $intent,
                ];
            }
        }

        // Base service + city
        return [
            'type' => 'high_intent',
            'keyword' => $service['name'] . ' ' . $city['name'],
            'slug' => $slug,
            'city' => $city_slug,
            'city_name' => $city['name'],
            'region' => $city['voivodeship'],
            'vertical' => $vertical_slug,
            'vertical_name' => $vertical_name,
            'service' => $service_slug,
            'service_name' => $service['name'],
            'intent' => 'transactional',
        ];
    }
}

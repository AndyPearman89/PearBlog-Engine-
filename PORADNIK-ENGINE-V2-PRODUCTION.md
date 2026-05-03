# Poradnik.pro — Engine V2 (Full Production)

**Version:** 2.0
**Date:** 2026-05-03
**Status:** 🚀 Production Architecture Specification

---

## 🎯 North Star Metric

```
Revenue per Article ↑

SEO → Click → PT24 → Lead → Revenue ($$$)
```

**Goal:** Maximize revenue generation per published article through data-driven optimization.

---

## 📋 Table of Contents

1. [System Architecture](#system-architecture)
2. [Database Schema](#database-schema)
3. [Data Scraper](#data-scraper)
4. [Data Engine](#data-engine)
5. [AI Generator](#ai-generator)
6. [SEO Enhancer](#seo-enhancer)
7. [Internal Linker](#internal-linker)
8. [Publisher System](#publisher-system)
9. [Event Tracking](#event-tracking)
10. [Scoring Engine V2](#scoring-engine-v2)
11. [Content Segmentation](#content-segmentation)
12. [AI Optimizer](#ai-optimizer)
13. [A/B Testing](#ab-testing)
14. [Decision Engine](#decision-engine)
15. [Background Workers](#background-workers)
16. [Cron Jobs](#cron-jobs)
17. [API Contract](#api-contract)
18. [Implementation Guide](#implementation-guide)

---

## 🏗️ System Architecture

```
┌────────────────────────────────────────────────────────────┐
│           PORADNIK.PRO ENGINE V2 - FULL FLOW               │
└────────────────────────────────────────────────────────────┘

1. [ SCRAPER ]
   │ • Collect market data
   │ • Respect robots.txt
   │ • Rate limiting
   ↓
2. [ DATA ENGINE ]
   │ • Clean & normalize
   │ • Extract pricing
   │ • Enrich with metadata
   ↓
3. [ AI GENERATOR ]
   │ • Generate article
   │ • Use service data
   │ • Apply template
   ↓
4. [ SEO ENHANCER ]
   │ • Optimize title/meta
   │ • Add long-tail keywords
   │ • Generate schema
   ↓
5. [ INTERNAL LINKER ]
   │ • Add PT24 links (2-3)
   │ • Build internal graph
   │ • Natural anchors
   ↓
6. [ PUBLISHER ]
   │ • Draft → Review → Publish
   │ • WordPress REST API
   │ • Metadata storage
   ↓
7. [ TRACKING ]
   │ • View events
   │ • CTA clicks
   │ • Lead conversions
   │ • Revenue tracking
   ↓
8. [ SCORING ENGINE ]
   │ • Calculate article score
   │ • SEO + Engagement + CTR + Revenue
   │ • Daily updates
   ↓
9. [ SEGMENTATION ]
   │ • 90-100: SCALE HARD
   │ • 70-90: BOOST
   │ • 50-70: OPTIMIZE
   │ • 0-50: REWRITE/DELETE
   ↓
10. [ AI OPTIMIZER ]
    │ • Analyze performance
    │ • Rewrite weak sections
    │ • Generate variants
    ↓
11. [ A/B TEST ]
    │ • Test variants (50/50)
    │ • Track winner
    │ • Auto-publish best
    ↓
12. [ DECISION ENGINE ]
    │ • Decide action per score
    │ • Scale winners
    │ • Delete losers
    ↓
13. [ PT24 CONVERSION ]
    │ • Lead generation
    │ • Revenue tracking
    │ • Attribution
    ↓
14. [ FEEDBACK LOOP ]
    │ • Feed data back to AI
    │ • Continuous improvement
    │ • Model learning
```

---

## 💾 Database Schema

### **Table: `wp_pearblog_articles`**

```sql
CREATE TABLE wp_pearblog_articles (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    topic VARCHAR(255) NOT NULL,
    city VARCHAR(100),
    service VARCHAR(100),
    status ENUM('draft', 'review', 'published', 'archived') DEFAULT 'draft',
    variant VARCHAR(50) DEFAULT 'original',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_topic_city (topic, city),
    INDEX idx_created (created_at)
);
```

### **Table: `wp_pearblog_article_stats`**

```sql
CREATE TABLE wp_pearblog_article_stats (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    article_id BIGINT NOT NULL,
    date DATE NOT NULL,

    -- Traffic metrics
    views INT DEFAULT 0,
    unique_visitors INT DEFAULT 0,
    avg_time_seconds INT DEFAULT 0,
    scroll_depth_avg DECIMAL(5,2) DEFAULT 0,
    bounce_rate DECIMAL(5,2) DEFAULT 0,

    -- Conversion metrics
    cta_clicks INT DEFAULT 0,
    cta_ctr DECIMAL(5,4) DEFAULT 0,
    leads INT DEFAULT 0,
    lead_conversion_rate DECIMAL(5,4) DEFAULT 0,
    revenue DECIMAL(10,2) DEFAULT 0,

    -- SEO metrics
    seo_impressions INT DEFAULT 0,
    seo_clicks INT DEFAULT 0,
    seo_ctr DECIMAL(5,4) DEFAULT 0,
    seo_position_avg DECIMAL(5,2) DEFAULT 0,

    -- Computed score
    score DECIMAL(5,2) DEFAULT 0,
    score_category ENUM('delete', 'rewrite', 'optimize', 'boost', 'scale') DEFAULT 'optimize',

    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_article_date (article_id, date),
    INDEX idx_article (article_id),
    INDEX idx_score (score DESC),
    INDEX idx_category (score_category)
);
```

### **Table: `wp_pearblog_service_data`**

```sql
CREATE TABLE wp_pearblog_service_data (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    service VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,

    -- Pricing data
    price_min DECIMAL(10,2),
    price_max DECIMAL(10,2),
    price_avg DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'PLN',

    -- Service details
    services_json TEXT,  -- JSON array of specific services
    providers_count INT DEFAULT 0,

    -- FAQ data
    faq_json TEXT,  -- JSON array of Q&A pairs

    -- Metadata
    data_source VARCHAR(255),
    scraped_at DATETIME,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_service_city (service, city),
    INDEX idx_service (service),
    INDEX idx_city (city)
);
```

### **Table: `wp_pearblog_events`**

```sql
CREATE TABLE wp_pearblog_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    event_type ENUM('view', 'scroll', 'cta_click', 'lead', 'revenue') NOT NULL,
    article_id BIGINT NOT NULL,
    post_id BIGINT,

    -- User tracking
    user_id BIGINT,
    session_id VARCHAR(100),
    ip_hash VARCHAR(64),  -- Hashed for privacy

    -- Event data
    event_data JSON,  -- Additional event-specific data

    -- Attribution
    referrer VARCHAR(255),
    utm_source VARCHAR(100),
    utm_medium VARCHAR(100),
    utm_campaign VARCHAR(100),

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_article (article_id),
    INDEX idx_type (event_type),
    INDEX idx_created (created_at),
    INDEX idx_session (session_id)
);
```

### **Table: `wp_pearblog_ab_tests`**

```sql
CREATE TABLE wp_pearblog_ab_tests (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    article_id BIGINT NOT NULL,
    test_name VARCHAR(100) NOT NULL,
    variant_a TEXT NOT NULL,
    variant_b TEXT NOT NULL,

    -- Performance tracking
    variant_a_views INT DEFAULT 0,
    variant_a_conversions INT DEFAULT 0,
    variant_b_views INT DEFAULT 0,
    variant_b_conversions INT DEFAULT 0,

    -- Test status
    status ENUM('running', 'completed', 'cancelled') DEFAULT 'running',
    winner ENUM('a', 'b', 'none') DEFAULT 'none',

    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,

    INDEX idx_article (article_id),
    INDEX idx_status (status)
);
```

---

## 🕷️ Data Scraper

### Purpose
Collect real market data (pricing, services, FAQs) to enrich content.

### Rules

**1. Ethical Scraping**
```php
// Check robots.txt
if (!$this->is_allowed_by_robots($url)) {
    return false;
}

// Rate limiting
sleep(rand(1, 3)); // 1-3 second delay between requests

// Rotating User-Agent
$user_agents = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64)...',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)...',
    'Mozilla/5.0 (X11; Linux x86_64)...'
];
$ua = $user_agents[array_rand($user_agents)];
```

**2. Data Sources**
- Industry forums
- Public price lists
- Review sites
- Official directories
- FAQ databases

**3. Output Format**
```php
[
    'service' => 'hydraulik',
    'city' => 'Kraków',
    'raw_text' => 'Full scraped content...',
    'source_url' => 'https://...',
    'scraped_at' => '2026-05-03 10:00:00'
]
```

### Implementation

```php
class DataScraper {
    public function scrape_service_data(string $service, string $city): array {
        $url = $this->build_search_url($service, $city);

        if (!$this->is_allowed($url)) {
            return ['error' => 'Blocked by robots.txt'];
        }

        $this->rate_limit();

        $html = $this->fetch_html($url);
        $data = $this->parse_html($html);

        return $data;
    }
}
```

---

## 🔧 Data Engine

### Clean

**Strip HTML & Normalize**
```php
function clean_text(string $raw_text): string {
    // Remove HTML tags
    $text = strip_tags($raw_text);

    // Normalize whitespace
    $text = preg_replace('/\s+/', ' ', $text);

    // Remove special characters
    $text = preg_replace('/[^\p{L}\p{N}\s\-.,]/u', '', $text);

    return trim($text);
}
```

**Deduplicate**
```php
function deduplicate(array $texts): array {
    $hashes = [];
    $unique = [];

    foreach ($texts as $text) {
        $hash = md5(strtolower($text));
        if (!in_array($hash, $hashes)) {
            $hashes[] = $hash;
            $unique[] = $text;
        }
    }

    return $unique;
}
```

### Normalize

**Extract Prices (PLN)**
```php
function extract_prices(string $text): array {
    // Match patterns like: 100 zł, 100zł, 100 PLN, 100-200 zł
    preg_match_all('/(\d+)\s*(?:-\s*(\d+)\s*)?(?:zł|PLN)/i', $text, $matches);

    $prices = [];
    foreach ($matches[1] as $i => $price) {
        $prices[] = (int)$price;
        if (!empty($matches[2][$i])) {
            $prices[] = (int)$matches[2][$i];
        }
    }

    return $prices;
}
```

**Extract Services**
```php
function extract_services(string $text): array {
    $services = [];

    // Common service keywords
    $keywords = [
        'naprawa',
        'wymiana',
        'instalacja',
        'montaż',
        'konserwacja',
        'przegląd'
    ];

    foreach ($keywords as $keyword) {
        if (stripos($text, $keyword) !== false) {
            $services[] = $keyword;
        }
    }

    return array_unique($services);
}
```

**Extract FAQ**
```php
function extract_faq(string $text): array {
    $faq = [];

    // Match question patterns
    preg_match_all('/(?:Czy|Jak|Ile|Kiedy|Dlaczego)\s+[^?]+\?/u', $text, $questions);

    foreach ($questions[0] as $question) {
        $faq[] = [
            'question' => trim($question),
            'answer' => '' // To be filled by AI
        ];
    }

    return $faq;
}
```

### Enrich

**Calculate Averages**
```php
function enrich_data(array $prices, array $services): array {
    return [
        'price_min' => !empty($prices) ? min($prices) : null,
        'price_max' => !empty($prices) ? max($prices) : null,
        'price_avg' => !empty($prices) ? round(array_sum($prices) / count($prices), 2) : null,
        'price_range' => !empty($prices) ? max($prices) - min($prices) : null,
        'services_count' => count($services),
        'services' => $services
    ];
}
```

---

## 🤖 AI Generator

### Input Data Structure

```php
$input = [
    'topic' => 'hydraulik',
    'city' => 'Kraków',
    'service_data' => [
        'price_min' => 150,
        'price_max' => 500,
        'price_avg' => 280,
        'services' => ['naprawa', 'wymiana', 'instalacja'],
        'faq' => [
            ['question' => 'Ile kosztuje hydraulik?', 'answer' => ''],
            ['question' => 'Jak wybrać hydraulika?', 'answer' => '']
        ]
    ]
];
```

### Prompt (Optimized for Production)

```php
$prompt = "Napisz artykuł poradnikowy w języku polskim.

TEMAT: {$topic}
LOKALIZACJA: {$city}

DANE RYNKOWE:
- Cena minimalna: {$price_min} zł
- Cena maksymalna: {$price_max} zł
- Średnia cena: {$price_avg} zł
- Usługi: " . implode(', ', $services) . "

WYMAGANIA:
- Długość: 1200-2000 słów
- Używaj konkretnych liczb i cen
- Nagłówki: H2/H3 (co najmniej 6)
- FAQ: 3-5 pytań z odpowiedziami
- Ton: ekspercki ale przystępny
- NIE pisz marketingowo

STRUKTURA:
## Wprowadzenie
## Co to jest {$topic}?
## Ile kosztuje {$topic} w {$city}?
## Od czego zależy cena?
## Jak wybrać najlepszego {$topic}?
## Najczęstsze błędy
## FAQ
## Podsumowanie

Pisz konkretnie, używaj prawdziwych cen.";
```

### Output

```php
[
    'title' => 'Hydraulik Kraków - ile kosztuje i jak wybrać',
    'content' => '<p>Full article HTML...</p>',
    'meta_description' => 'Sprawdź ceny hydraulika...',
    'word_count' => 1650,
    'headings_count' => 8
]
```

---

## 🔍 SEO Enhancer

### Title Optimization

```php
function optimize_title(string $topic, string $city): string {
    $patterns = [
        "{$topic} {$city} - ile kosztuje i jak wybrać",
        "{$topic} {$city} - ceny 2026 i porady",
        "{$topic} w {$city} - kompletny przewodnik",
        "Ile kosztuje {$topic} w {$city}? [2026]"
    ];

    // Rotate for variety
    return $patterns[array_rand($patterns)];
}
```

### Meta Description

```php
function generate_meta(string $topic, string $city, int $price_avg): string {
    return "Sprawdź ceny {$topic} w {$city}. Średni koszt: {$price_avg} zł. " .
           "Dowiedz się jak wybrać najlepszego specjalistę. Porady i opinie.";
}
```

### Long-Tail Keywords

```php
$long_tail = [
    "ile kosztuje {$topic}",
    "cena {$topic} {$city}",
    "{$topic} {$city} opinie",
    "najlepszy {$topic} {$city}",
    "{$topic} koszt 2026",
    "jak wybrać {$topic}",
    "{$topic} porównanie cen",
    "{$topic} {$city} tanio"
];
```

### FAQ Schema (JSON-LD)

```php
function generate_faq_schema(array $faq): string {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => []
    ];

    foreach ($faq as $item) {
        $schema['mainEntity'][] = [
            '@type' => 'Question',
            'name' => $item['question'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $item['answer']
            ]
        ];
    }

    return '<script type="application/ld+json">' .
           json_encode($schema, JSON_UNESCAPED_UNICODE) .
           '</script>';
}
```

---

## 🔗 Internal Linker

### Linking Rules

**PT24 Links (2-3 per article)**

```php
$pt24_anchors = [
    'sprawdź opcje w Twojej okolicy',
    'zobacz dostępne rozwiązania',
    'porównaj oferty lokalnych specjalistów',
    'znajdź sprawdzonych wykonawców',
    'sprawdź ceny w {city}'
];

$pt24_url = "https://pt24.pro/{$city}/{$service}";
```

**Internal Content Graph**

```php
function build_internal_links(string $content, string $topic, string $city): string {
    global $wpdb;

    // Find related articles
    $related = $wpdb->get_results($wpdb->prepare("
        SELECT post_id, topic, slug
        FROM wp_pearblog_articles
        WHERE (topic LIKE %s OR city = %s)
        AND status = 'published'
        AND id != %d
        LIMIT 5
    ", "%{$topic}%", $city, $current_article_id));

    // Add links naturally
    foreach ($related as $article) {
        $anchor = "więcej o {$article->topic}";
        $link = "<a href=\"/{$article->slug}\">{$anchor}</a>";

        // Insert link after relevant paragraph
        $content = $this->insert_link_naturally($content, $link);
    }

    return $content;
}
```

---

## 📝 Publisher System

### WordPress REST API

**Endpoint:**
```
POST /wp-json/wp/v2/posts
```

**Request:**
```php
$post_data = [
    'title' => $article['title'],
    'content' => $article['content'],
    'status' => 'draft',  // draft → review → publish
    'categories' => [$category_id],
    'meta' => [
        'pearblog_topic' => $topic,
        'pearblog_city' => $city,
        'pearblog_score' => 0,
        'pearblog_variant' => 'original'
    ]
];

$response = wp_remote_post('https://site.com/wp-json/wp/v2/posts', [
    'headers' => [
        'Authorization' => 'Bearer ' . $jwt_token,
        'Content-Type' => 'application/json'
    ],
    'body' => json_encode($post_data)
]);
```

### Publishing Workflow

```
┌──────┐     ┌────────┐     ┌───────────┐
│ DRAFT│ ──> │ REVIEW │ ──> │ PUBLISHED │
└──────┘     └────────┘     └───────────┘
```

**Automated Decision:**
```php
if ($quality_score >= 85 && $auto_publish_enabled) {
    $status = 'publish';
} elseif ($quality_score >= 70) {
    $status = 'draft';  // Manual review
} else {
    $status = 'draft';  // Needs revision
}
```

---

## 📊 Event Tracking

### Frontend JavaScript

```javascript
// Track view
function trackView(articleId) {
    track('view', articleId, {
        referrer: document.referrer,
        timestamp: Date.now()
    });
}

// Track scroll depth
let maxScroll = 0;
window.addEventListener('scroll', () => {
    let scrollPercentage = (window.scrollY + window.innerHeight) /
                           document.body.scrollHeight * 100;
    maxScroll = Math.max(maxScroll, scrollPercentage);
});

window.addEventListener('beforeunload', () => {
    track('scroll', articleId, {
        depth: Math.round(maxScroll),
        duration: (Date.now() - startTime) / 1000
    });
});

// Track CTA click
document.querySelectorAll('.pt24-cta').forEach(cta => {
    cta.addEventListener('click', () => {
        track('cta_click', articleId, {
            cta_text: cta.textContent,
            cta_position: getCTAPosition(cta)
        });
    });
});

// Generic track function
function track(eventType, articleId, data = {}) {
    fetch('/wp-json/pearblog/v1/track', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            event_type: eventType,
            article_id: articleId,
            event_data: data,
            session_id: getSessionId(),
            utm_source: getUTMParam('source'),
            utm_medium: getUTMParam('medium'),
            utm_campaign: getUTMParam('campaign')
        })
    });
}
```

### Backend Endpoint

```php
register_rest_route('pearblog/v1', '/track', [
    'methods' => 'POST',
    'callback' => function($request) {
        global $wpdb;

        $data = $request->get_json_params();

        $wpdb->insert('wp_pearblog_events', [
            'event_type' => $data['event_type'],
            'article_id' => $data['article_id'],
            'event_data' => json_encode($data['event_data']),
            'session_id' => $data['session_id'],
            'ip_hash' => hash('sha256', $_SERVER['REMOTE_ADDR']),
            'referrer' => $data['referrer'] ?? '',
            'utm_source' => $data['utm_source'] ?? '',
            'utm_medium' => $data['utm_medium'] ?? '',
            'utm_campaign' => $data['utm_campaign'] ?? ''
        ]);

        return ['success' => true];
    }
]);
```

---

## 📈 Scoring Engine V2

### Formula

```
Score = (SEO × 0.2) + (ENG × 0.2) + (CTR × 0.2) + (REV × 0.4)

Where:
- SEO = (seo_clicks / seo_impressions) × 100
- ENG = (avg_time_seconds / 60) + (scroll_depth_avg / 100)
- CTR = (cta_clicks / views) × 100
- REV = revenue (normalized 0-100)
```

### Implementation

```php
class ScoringEngine {
    public function calculate_score(int $article_id, string $date): array {
        global $wpdb;

        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM wp_pearblog_article_stats
            WHERE article_id = %d AND date = %s
        ", $article_id, $date));

        // SEO Score (0-100)
        $seo_score = $stats->seo_impressions > 0
            ? ($stats->seo_clicks / $stats->seo_impressions) * 100
            : 0;

        // Engagement Score (0-100)
        $time_score = min(100, ($stats->avg_time_seconds / 60) * 50);
        $scroll_score = $stats->scroll_depth_avg;
        $eng_score = ($time_score + $scroll_score) / 2;

        // CTR Score (0-100)
        $ctr_score = $stats->views > 0
            ? ($stats->cta_clicks / $stats->views) * 100
            : 0;

        // Revenue Score (0-100, normalized)
        $rev_score = min(100, $stats->revenue * 10);  // $10 = 100 points

        // Weighted Total
        $total_score = (
            ($seo_score * 0.2) +
            ($eng_score * 0.2) +
            ($ctr_score * 0.2) +
            ($rev_score * 0.4)
        );

        return [
            'seo_score' => round($seo_score, 2),
            'eng_score' => round($eng_score, 2),
            'ctr_score' => round($ctr_score, 2),
            'rev_score' => round($rev_score, 2),
            'total_score' => round($total_score, 2)
        ];
    }
}
```

---

## 🎯 Content Segmentation

### Score-Based Categories

```php
function categorize_by_score(float $score): string {
    return match(true) {
        $score >= 90 => 'scale',      // Scale hard
        $score >= 70 => 'boost',      // Boost visibility
        $score >= 50 => 'optimize',   // Optimize content
        default => 'rewrite'          // Rewrite or delete
    };
}
```

### Action Matrix

| Score Range | Category | Action |
|------------|----------|--------|
| **90-100** | SCALE | • Generate 3 variants<br>• Increase internal linking<br>• Promote on social<br>• Build more similar content |
| **70-90** | BOOST | • Improve title/meta<br>• Add more internal links<br>• Update with fresh data<br>• Add more CTAs |
| **50-70** | OPTIMIZE | • Rewrite intro<br>• Improve CTA copy<br>• Add more value<br>• Test A/B variants |
| **0-50** | REWRITE | • Complete rewrite<br>• Change angle<br>• Redirect to better article<br>• Delete if no potential |

---

## 🤖 AI Optimizer

### Optimization Rules

```php
class AIOptimizer {
    public function analyze_and_optimize(int $article_id): array {
        $stats = $this->get_stats($article_id);
        $actions = [];

        // Rule 1: Low CTR
        if ($stats->cta_ctr < 0.05) {  // Less than 5%
            $actions[] = 'rewrite_cta';
        }

        // Rule 2: Low engagement
        if ($stats->avg_time_seconds < 40) {
            $actions[] = 'rewrite_intro';
        }

        // Rule 3: No revenue
        if ($stats->revenue == 0 && $stats->views > 100) {
            $actions[] = 'add_cta';
            $actions[] = 'reposition_cta';
        }

        // Rule 4: High impressions, low clicks
        if ($stats->seo_impressions > 1000 && $stats->seo_ctr < 0.02) {
            $actions[] = 'rewrite_title';
            $actions[] = 'rewrite_meta';
        }

        // Rule 5: High bounce
        if ($stats->bounce_rate > 70) {
            $actions[] = 'improve_intro';
            $actions[] = 'add_images';
        }

        return $this->execute_actions($article_id, $actions);
    }

    private function execute_actions(int $article_id, array $actions): array {
        $results = [];

        foreach ($actions as $action) {
            $results[$action] = $this->$action($article_id);
        }

        return $results;
    }

    private function rewrite_cta(int $article_id): string {
        // Use AI to generate new CTA
        $current_cta = $this->get_current_cta($article_id);

        $prompt = "Przepisz to CTA aby było bardziej przekonujące:\n{$current_cta}";

        $new_cta = $this->call_ai($prompt);

        return $new_cta;
    }
}
```

---

## 🧪 A/B Testing

### Test Configuration

```php
class ABTestManager {
    public function create_test(int $article_id, string $test_name, array $variants): int {
        global $wpdb;

        return $wpdb->insert('wp_pearblog_ab_tests', [
            'article_id' => $article_id,
            'test_name' => $test_name,
            'variant_a' => $variants['a'],
            'variant_b' => $variants['b'],
            'status' => 'running'
        ]);
    }

    public function serve_variant(int $test_id): string {
        // 50/50 split
        $variant = rand(0, 1) ? 'a' : 'b';

        // Track view
        $this->track_variant_view($test_id, $variant);

        return $variant;
    }

    public function check_winner(int $test_id): ?string {
        global $wpdb;

        $test = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM wp_pearblog_ab_tests WHERE id = %d
        ", $test_id));

        // Minimum sample size
        if ($test->variant_a_views < 100 || $test->variant_b_views < 100) {
            return null;
        }

        $ctr_a = $test->variant_a_conversions / $test->variant_a_views;
        $ctr_b = $test->variant_b_conversions / $test->variant_b_views;

        // Statistical significance check (simplified)
        if (abs($ctr_a - $ctr_b) > 0.02) {  // 2% difference
            return $ctr_a > $ctr_b ? 'a' : 'b';
        }

        return null;
    }
}
```

### Test Types

**1. CTA Copy**
```php
$variants = [
    'a' => 'sprawdź dostępne opcje',
    'b' => 'zobacz najlepsze oferty już teraz'
];
```

**2. Introduction**
```php
$variants = [
    'a' => 'Problem-focused intro...',
    'b' => 'Question-based intro...'
];
```

**3. Headings**
```php
$variants = [
    'a' => 'Jak wybrać hydraulika',
    'b' => '7 sposobów na wybranie najlepszego hydraulika'
];
```

---

## 🎮 Decision Engine

### Decision Logic

```php
class DecisionEngine {
    public function decide_action(int $article_id, float $score): array {
        $actions = [];

        if ($score > 90) {
            // SCALE HARD
            $actions[] = [
                'action' => 'generate_variants',
                'params' => ['count' => 3]
            ];
            $actions[] = [
                'action' => 'increase_linking',
                'params' => ['links_to_add' => 5]
            ];
            $actions[] = [
                'action' => 'create_similar_content',
                'params' => ['topics' => $this->find_similar_topics($article_id)]
            ];
        } elseif ($score < 40) {
            // REWRITE OR DELETE
            $potential = $this->assess_potential($article_id);

            if ($potential > 50) {
                $actions[] = [
                    'action' => 'complete_rewrite',
                    'params' => ['keep_topic' => true]
                ];
            } else {
                $actions[] = [
                    'action' => 'redirect_and_delete',
                    'params' => ['redirect_to' => $this->find_redirect_target($article_id)]
                ];
            }
        } elseif ($score >= 50 && $score < 70) {
            // OPTIMIZE
            $actions[] = [
                'action' => 'ab_test',
                'params' => [
                    'test_elements' => ['intro', 'cta']
                ]
            ];
            $actions[] = [
                'action' => 'update_data',
                'params' => ['refresh_service_data' => true]
            ];
        }

        return $actions;
    }
}
```

---

## ⚙️ Background Workers

### Worker Architecture

```php
// Worker: Generate Articles
class GenerateWorker {
    public function process() {
        // Read from CSV or database queue
        $topics = $this->get_pending_topics();

        foreach ($topics as $topic) {
            try {
                $article = $this->generate_article($topic);
                $this->save_article($article);
                $this->mark_completed($topic->id);
            } catch (Exception $e) {
                $this->log_error($topic->id, $e);
            }

            sleep(5);  // Rate limiting
        }
    }
}

// Worker: Calculate Scores
class ScoringWorker {
    public function process() {
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $articles = $this->get_active_articles();

        foreach ($articles as $article) {
            $score = $this->calculate_score($article->id, $yesterday);
            $this->update_score($article->id, $score);
        }
    }
}

// Worker: Optimize Content
class OptimizeWorker {
    public function process() {
        // Find articles that need optimization
        $articles = $this->get_articles_for_optimization();

        foreach ($articles as $article) {
            $actions = $this->determine_optimizations($article);
            $this->apply_optimizations($article->id, $actions);
        }
    }
}

// Worker: Publish to WordPress
class PublishWorker {
    public function process() {
        $pending = $this->get_pending_publications();

        foreach ($pending as $article) {
            $post_id = $this->publish_to_wp($article);
            $this->update_status($article->id, 'published', $post_id);
        }
    }
}
```

---

## ⏰ Cron Jobs

### Schedule

```php
// Daily jobs
add_action('pearblog_daily_scoring', function() {
    $worker = new ScoringWorker();
    $worker->process();
});

add_action('pearblog_daily_optimize', function() {
    $worker = new OptimizeWorker();
    $worker->process();
});

// Weekly jobs
add_action('pearblog_weekly_rewrite', function() {
    $articles = get_low_score_articles(40);  // Score < 40
    foreach ($articles as $article) {
        schedule_rewrite($article->id);
    }
});

add_action('pearblog_weekly_prune', function() {
    $articles = get_zero_traffic_articles(30);  // 30 days no traffic
    foreach ($articles as $article) {
        consider_deletion($article->id);
    }
});

// Register schedules
if (!wp_next_scheduled('pearblog_daily_scoring')) {
    wp_schedule_event(strtotime('03:00:00'), 'daily', 'pearblog_daily_scoring');
}

if (!wp_next_scheduled('pearblog_weekly_rewrite')) {
    wp_schedule_event(strtotime('Sunday 02:00:00'), 'weekly', 'pearblog_weekly_rewrite');
}
```

---

## 🔌 API Contract

### Endpoints

**1. Generate Content**
```
POST /api/content/generate

Request:
{
  "topic": "hydraulik",
  "city": "Kraków",
  "service_data": {
    "price_min": 150,
    "price_max": 500
  }
}

Response:
{
  "article_id": 123,
  "title": "...",
  "status": "draft"
}
```

**2. Optimize Content**
```
POST /api/content/optimize

Request:
{
  "article_id": 123,
  "actions": ["rewrite_cta", "improve_intro"]
}

Response:
{
  "article_id": 123,
  "optimizations_applied": 2,
  "new_score": 75.5
}
```

**3. Get Score**
```
GET /api/content/score?article_id=123

Response:
{
  "article_id": 123,
  "score": 78.5,
  "category": "boost",
  "breakdown": {
    "seo": 80,
    "engagement": 75,
    "ctr": 6.5,
    "revenue": 12.50
  }
}
```

**4. Publish**
```
POST /api/content/publish

Request:
{
  "article_id": 123,
  "status": "publish"
}

Response:
{
  "post_id": 456,
  "url": "https://site.com/hydraulik-krakow",
  "status": "published"
}
```

**5. Track Event**
```
POST /api/event

Request:
{
  "event_type": "cta_click",
  "article_id": 123,
  "session_id": "abc123",
  "event_data": {
    "cta_text": "sprawdź opcje"
  }
}

Response:
{
  "event_id": 789,
  "tracked": true
}
```

---

## 🚀 Implementation Guide

### Phase 1: Database Setup (Week 1)

```sql
-- Run all table creation scripts
-- wp_pearblog_articles
-- wp_pearblog_article_stats
-- wp_pearblog_service_data
-- wp_pearblog_events
-- wp_pearblog_ab_tests
```

### Phase 2: Core Components (Week 2-3)

1. **Data Engine** - Clean/normalize/enrich
2. **AI Generator** - Enhanced with service data
3. **SEO Enhancer** - Title/meta optimization
4. **Internal Linker** - PT24 + content graph

### Phase 3: Tracking (Week 4)

1. **Frontend JS** - Event tracking
2. **Backend API** - Event storage
3. **Dashboard** - View stats

### Phase 4: Scoring & Optimization (Week 5-6)

1. **Scoring Engine** - Calculate daily scores
2. **AI Optimizer** - Rule-based optimization
3. **Decision Engine** - Action determination

### Phase 5: Workers & Automation (Week 7-8)

1. **Background Workers** - Generate, score, optimize, publish
2. **Cron Jobs** - Daily/weekly automation
3. **A/B Testing** - Variant testing framework

### Phase 6: Production Launch (Week 9+)

1. **Start with 5-10 articles/day**
2. **Monitor scores closely**
3. **Optimize based on data**
4. **Scale to 20-50 articles/day**

---

## 📊 Success Metrics

### Week 1
- Database setup complete
- Core components working
- 10 test articles generated

### Month 1
- 300 articles published
- Tracking implemented
- Scoring engine operational
- Average score: 60+

### Month 3
- 1,000+ articles live
- Top 10% scoring 90+
- Revenue per article: $2+
- Auto-optimization working
- A/B testing active

---

## 🔗 Related Documentation

- [PORADNIK-CLEAN-CONTENT-SYSTEM.md](./PORADNIK-CLEAN-CONTENT-SYSTEM.md) - Content template
- [AI-CONTENT-ENGINE-V2.md](./AI-CONTENT-ENGINE-V2.md) - Content factory
- [COMPLETE-STEP-BY-STEP-GUIDE.md](./COMPLETE-STEP-BY-STEP-GUIDE.md) - Operations guide
- [SYSTEM-ARCHITECTURE-MAP.md](./SYSTEM-ARCHITECTURE-MAP.md) - System overview

---

**Status:** 🚀 **PRODUCTION ARCHITECTURE**
**Implementation:** Ready for development
**Last Updated:** 2026-05-03

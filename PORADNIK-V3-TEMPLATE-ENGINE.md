# PORADNIK V3 TEMPLATE ENGINE – FULL DOCUMENTATION

**Version:** 3.0.0
**Type:** SEO Landing + Lead Engine + Data Collector
**Status:** Production Ready ✅

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Core Components](#core-components)
4. [Template Structure](#template-structure)
5. [Smart Calculator](#smart-calculator)
6. [Live Pricing Data Layer](#live-pricing-data-layer)
7. [Conversion Tracking](#conversion-tracking)
8. [A/B Testing](#ab-testing)
9. [REST API Endpoints](#rest-api-endpoints)
10. [Database Schema](#database-schema)
11. [Implementation Guide](#implementation-guide)
12. [Performance](#performance)
13. [Analytics & Metrics](#analytics--metrics)

---

## 🎯 Overview

**Poradnik V3 Template Engine** transforms cost-focused articles into conversion-optimized landing pages with:

### Core Mission
- **Capture** high-intent users (cost searches)
- **Build trust** with data and transparency
- **Convert** visitors to leads
- **Collect** pricing data for continuous optimization

### Key Features
- ✅ Smart calculator with real-time cost estimates
- ✅ Live pricing data aggregated from user submissions
- ✅ Conversion flow tracking and optimization
- ✅ A/B testing framework for continuous improvement
- ✅ Data feedback loop for pricing accuracy
- ✅ Mobile-first, performance-optimized
- ✅ Full schema.org markup (Article, FAQPage, Breadcrumb)

### Upgrade from V1
V3 adds:
- Interactive calculator (V1 had static CTA)
- Live pricing widget (V1 had fixed examples)
- Conversion tracking (V1 had basic analytics)
- A/B testing support (V1 had single variant)
- Data collection layer (V1 had no feedback loop)

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     V3 LANDING PAGE                         │
├─────────────────────────────────────────────────────────────┤
│ Hero → Quick Answer → Calculator → Live Pricing            │
│   ↓         ↓            ↓              ↓                   │
│ [CTA]    [Trust]    [Calculate]   [Data Layer]            │
│   ↓         ↓            ↓              ↓                   │
│ Cost Table → Lead Form → FAQ → Final CTA                   │
├─────────────────────────────────────────────────────────────┤
│                   BACKEND SYSTEMS                           │
├─────────────────────────────────────────────────────────────┤
│ SmartCalculatorEngine  │  LivePricingDataLayer             │
│ ConversionFlowTracker  │  PoradnikV3API                    │
│ ABTestEngine           │  PoradnikV3Schema (Database)      │
└─────────────────────────────────────────────────────────────┘
```

### Data Flow

```
User Entry (SEO)
    ↓
Page View (tracked)
    ↓
Quick Answer (trust building)
    ↓
Calculator Interaction (engagement)
    ↓  [data collected]
    ├→ Calculator Submission DB
    ↓
Lead Form (conversion)
    ↓  [lead captured]
    ├→ Leads DB
    ↓
Live Pricing Update (feedback loop)
    ↓
Improved Accuracy → Better SEO → More Traffic
```

---

## 🧩 Core Components

### 1. PoradnikV3TemplateBuilder
**File:** `src/Content/PoradnikV3TemplateBuilder.php`

Generates AI prompts for V3 landing pages with 11 mandatory sections.

**Usage:**
```php
use PearBlogEngine\Content\PoradnikV3TemplateBuilder;
use PearBlogEngine\Tenant\SiteProfile;

$profile = SiteProfile::for_site(1);
$builder = new PoradnikV3TemplateBuilder($profile);

$prompt = $builder->build('budowa domu', [
    'year' => 2026,
    'city' => '',
    'service' => 'budowa-domu',
    'min_price' => '5000',
    'max_price' => '10000',
    'price_per' => 'm²',
    'calculator_enabled' => true,
    'live_pricing_enabled' => true,
    'ab_test_variant' => '',
]);

// Use $prompt to generate content via AI
```

### 2. SmartCalculatorEngine
**File:** `src/Content/SmartCalculatorEngine.php`

Interactive calculator with multi-input fields and real-time calculation.

**Features:**
- Validates user inputs (metraz, standard, lokalizacja, typ)
- Applies multipliers based on selections
- Returns min/max/avg costs + breakdown
- Stores submissions for pricing data layer

**Usage:**
```php
use PearBlogEngine\Content\SmartCalculatorEngine;

$result = SmartCalculatorEngine::calculate('budowa-domu', [
    'metraz' => 120,
    'standard' => 'sredni',
    'lokalizacja' => 'przedmiescia',
    'typ' => 'pietrowy'
]);

// $result contains:
// - min_cost: 600000
// - max_cost: 900000
// - avg_cost: 750000
// - cost_per_unit: 6250
// - breakdown: [...]
```

**Render HTML:**
```php
echo SmartCalculatorEngine::render('budowa-domu');
```

### 3. LivePricingDataLayer
**File:** `src/Content/LivePricingDataLayer.php`

Aggregates real pricing data from calculator submissions and displays dynamic statistics.

**Features:**
- Queries calculator_submissions table
- Calculates avg/min/max from real data
- Confidence levels (low/medium/high)
- Pricing trend analysis (up/down/stable)
- Cached for performance (1 hour)

**Usage:**
```php
use PearBlogEngine\Content\LivePricingDataLayer;

// Get current pricing
$data = LivePricingDataLayer::get_live_pricing('budowa-domu', [
    'standard' => 'sredni',
    'days' => 90
]);

// $data contains:
// - avg_price_per_unit: 6500.00
// - min_price: 500000
// - max_price: 1200000
// - sample_count: 127
// - confidence: 'high'

// Render widget
echo LivePricingDataLayer::render('budowa-domu');

// Get trend
$trend = LivePricingDataLayer::get_pricing_trend('budowa-domu', 30);
```

### 4. ConversionFlowTracker
**File:** `src/Analytics/ConversionFlowTracker.php`

Tracks user journey through landing page for optimization.

**Events Tracked:**
- `page_view` - Initial page load
- `calculator_use` - Calculator interaction
- `form_view` - Lead form displayed
- `form_submit` - Lead form submitted
- `cta_click` - CTA button clicked

**Features:**
- Session-based tracking
- UTM parameter capture
- Funnel drop-off analysis
- Conversion rate calculation

**Usage:**
```php
use PearBlogEngine\Analytics\ConversionFlowTracker;

// Get conversion metrics
$metrics = ConversionFlowTracker::get_conversion_metrics('budowa-domu', 30);
// Returns: total_views, calculator_uses, form_views, form_submits, conversion_rate

// Analyze drop-off points
$dropoff = ConversionFlowTracker::get_funnel_dropoff('budowa-domu', 30);
// Returns: page_to_calculator, calculator_to_form, form_to_submit rates

// Get specific session funnel
$funnel = ConversionFlowTracker::get_session_funnel('pb_abc123');
```

### 5. PoradnikV3API
**File:** `src/API/PoradnikV3API.php`

REST API endpoints for V3 features.

**Endpoints:**
- `POST /pearblog/v3/calculator/calculate` - Calculate cost
- `GET /pearblog/v3/pricing/{service}` - Get live pricing
- `GET /pearblog/v3/pricing/{service}/trend` - Get pricing trend
- `POST /pearblog/v3/tracking/event` - Track conversion event
- `GET /pearblog/v3/abtest/variant` - Get A/B test variant

### 6. PoradnikV3Schema
**File:** `src/Database/PoradnikV3Schema.php`

Database schema manager for V3 tables.

**Tables Created:**
- `wp_pearblog_calculator_submissions` - Calculator data
- `wp_pearblog_conversion_events` - User behavior tracking
- `wp_pearblog_ab_test_variants` - A/B test configuration

---

## 📄 Template Structure

### SECTION 0: URL & Meta (CTR Optimization)

**URL Format:**
```
/ile-kosztuje-{service}-w-polsce-{year}
/ile-kosztuje-{service}-{city}-{year}
```

**Meta Tags:**
```html
<title>Ile kosztuje budowa domu 2026? Ceny + kalkulator</title>
<meta name="description" content="Sprawdź aktualne koszty budowy domu w 2026. Ceny za m², przykłady i darmowa wycena online.">
```

### SECTION 1: Hero (Conversion-First)

**H1:**
```
Ile kosztuje budowa domu w Polsce w 2026? [Aktualne ceny + kalkulator]
```

**Subhead:**
```
Sprawdź ile kosztuje budowa domu za m² i otrzymaj realną wycenę od firm w Twojej okolicy.
```

**Trust Badges:**
- ✔ aktualizacja 2026
- ✔ dane rynkowe
- ✔ realne wyceny

**CTAs:**
- Primary: "👉 Oblicz koszt budowy domu"
- Secondary: "📩 Otrzymaj wycenę"

### SECTION 2: Quick Answer (Snippet Optimized)

Featured snippet box with:
- Price tiers (3-4 levels)
- Example calculation
- Note about factors

### SECTION 3: Smart Calculator

Interactive calculator with:
- **Inputs:** Metraż, Standard, Lokalizacja, Typ
- **Outputs:** Min/Max/Avg costs, Cost per unit
- **CTA:** "Wyślij zapytanie → otrzymaj oferty od firm"

### SECTION 4: Live Pricing Widget

Dynamic section showing:
- Average price per m²
- Min/Max range
- Sample count
- Confidence level
- Last updated timestamp

### SECTION 5: Cost Breakdown Table

HTML table with:
- Stage/Element column
- Cost per m² column
- What's included column
- 6-8 rows with specific data

### SECTION 6: Mid-Page Lead Capture

Lead form with:
- Metraż/zakres
- Lokalizacja (miasto)
- Standard/wymagania
- Budżet (optional)
- Email or phone
- Trust signals below form

### SECTION 7: Intent Expansion

6-7 H3 subsections covering factors:
1. Metraż i bryła budynku
2. Projekt (gotowy vs indywidualny)
3. Lokalizacja (miasto vs wieś)
4. Materiały budowlane
5. Robocizna
6. Technologie
7. Inflacja i ceny rynku

### SECTION 8: Programmatic SEO Grid

City links grid (2-3 columns):
- Katowice, Kraków, Warszawa
- Wrocław, Gdańsk, Poznań
- + more cities

### SECTION 9: Related Content

Internal links to:
- Related cost articles
- Selection guides
- Firm rankings

### SECTION 10: FAQ

6-8 FAQ items with:
- Question as H3
- Answer as paragraph
- FAQPage schema markup

### SECTION 11: Final CTA

Bottom conversion section with:
- Strong headline
- Lead form (same as mid-page)
- High-contrast design

---

## 🧮 Smart Calculator

### Calculator Configuration

Base prices per service (per m²):
```php
'budowa-domu' => ['min' => 5000, 'max' => 7500],
'remont-domu' => ['min' => 1500, 'max' => 3500],
'remont-mieszkania' => ['min' => 1200, 'max' => 2800],
'dach' => ['min' => 150, 'max' => 350],
```

### Multipliers

**Standard:**
- Podstawowy: 0.85x
- Średni: 1.0x
- Premium: 1.3x

**Lokalizacja:**
- Miasto: 1.15x
- Przedmieścia: 1.0x
- Wieś: 0.85x

**Typ (example for budowa-domu):**
- Parterowy: 0.9x
- Piętrowy: 1.0x
- Bliźniak: 0.95x
- Szeregowy: 0.85x

### Calculation Formula

```
base_cost = base_price_per_m² * metraz
total_multiplier = standard_multiplier * lokalizacja_multiplier * typ_multiplier
final_cost = base_cost * total_multiplier
```

### REST API Example

```javascript
fetch('/wp-json/pearblog/v3/calculator/calculate', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        service: 'budowa-domu',
        metraz: 120,
        standard: 'sredni',
        lokalizacja: 'przedmiescia',
        typ: 'pietrowy'
    })
})
.then(res => res.json())
.then(data => {
    console.log(data);
    // {success: true, data: {min_cost: 600000, max_cost: 900000, ...}}
});
```

---

## 📊 Live Pricing Data Layer

### How It Works

1. **Data Collection:** Every calculator submission is stored in `wp_pearblog_calculator_submissions` table
2. **Aggregation:** LivePricingDataLayer queries the table and calculates statistics
3. **Caching:** Results cached for 1 hour to reduce database load
4. **Display:** Widget shows real-time averages to users

### Confidence Levels

- **High:** 50+ samples in 90-day period
- **Medium:** 15-49 samples
- **Low:** 3-14 samples

### Trend Analysis

Compares current 30-day period vs previous 30 days:
- **Up:** >5% increase
- **Down:** >5% decrease
- **Stable:** ±5% change

### Widget Display

```html
<div class="live-pricing-widget">
    <h3>📊 Średnie ceny – aktualizacja live</h3>
    <div class="pricing-stats">
        <div class="stat-item">
            <div class="stat-label">Średnia cena za m²</div>
            <div class="stat-value">6 500 zł</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Zakres cen</div>
            <div class="stat-value">500 000 - 1 200 000 zł</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Analizowane wyceny</div>
            <div class="stat-value">127</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Wiarygodność danych</div>
            <div class="stat-value">Wysoka</div>
        </div>
    </div>
    <p>Dane aktualizowane na podstawie realnych wycen użytkowników.</p>
</div>
```

---

## 📈 Conversion Tracking

### Event Types

| Event Type      | Trigger                           | Data Collected                |
|-----------------|-----------------------------------|-------------------------------|
| `page_view`     | Page load                         | URL, referrer, UTM params     |
| `calculator_use`| Calculator submit                 | Service, inputs, result       |
| `form_view`     | Lead form displayed               | Form ID, location             |
| `form_submit`   | Lead form submitted               | Form data, service            |
| `cta_click`     | CTA button clicked                | CTA ID, text, location        |

### Session Tracking

- Cookie-based: `pearblog_session_id`
- Duration: 30 days
- Used for funnel analysis

### Conversion Funnel

```
100 Page Views
  ↓ 65% engage
65 Calculator Uses
  ↓ 35% continue
23 Form Views
  ↓ 60% submit
14 Form Submissions
= 14% overall conversion rate
```

### REST API Tracking

```javascript
// Track event
fetch('/wp-json/pearblog/v3/tracking/event', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        event_type: 'calculator_use',
        service: 'budowa-domu',
        event_data: {calculator_result: {...}}
    })
});
```

---

## 🧪 A/B Testing

### How It Works

1. **Variant Creation:** Define test variants in `wp_pearblog_ab_test_variants` table
2. **Traffic Allocation:** Assign traffic percentage to each variant (e.g., 50/50)
3. **Assignment:** Server assigns variant based on session hash
4. **Tracking:** All conversion events tagged with variant name
5. **Analysis:** Compare conversion rates across variants

### Example Test: H1 Variants

```sql
INSERT INTO wp_pearblog_ab_test_variants (test_name, variant_name, traffic_allocation, config)
VALUES
('h1_test', 'control', 50, '{"h1": "Ile kosztuje budowa domu w 2026?"}'),
('h1_test', 'variant_b', 50, '{"h1": "Budowa domu 2026 - sprawdź ceny i otrzymaj wycenę"}');
```

### Get Variant via API

```javascript
fetch('/wp-json/pearblog/v3/abtest/variant?test_name=h1_test&service=budowa-domu')
    .then(res => res.json())
    .then(data => {
        // Apply variant config
        console.log(data.data.variant_name); // 'control' or 'variant_b'
        console.log(data.data.config); // {h1: "..."}
    });
```

---

## 🔌 REST API Endpoints

### 1. Calculate Cost

**Endpoint:** `POST /wp-json/pearblog/v3/calculator/calculate`

**Request:**
```json
{
    "service": "budowa-domu",
    "metraz": 120,
    "standard": "sredni",
    "lokalizacja": "przedmiescia",
    "typ": "pietrowy"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "min_cost": 600000,
        "max_cost": 900000,
        "avg_cost": 750000,
        "cost_per_unit": 6250,
        "breakdown": {
            "Fundamenty": 112500,
            "Ściany i strop": 187500,
            ...
        }
    }
}
```

### 2. Get Live Pricing

**Endpoint:** `GET /wp-json/pearblog/v3/pricing/{service}`

**Parameters:**
- `standard` (optional): podstawowy, sredni, premium
- `lokalizacja` (optional): miasto, przedmiescia, wies
- `days` (optional): 7-365, default 90

**Response:**
```json
{
    "success": true,
    "data": {
        "avg_price_per_unit": 6500.00,
        "min_price": 500000,
        "max_price": 1200000,
        "sample_count": 127,
        "last_updated": "2026-05-04 10:30:00",
        "confidence": "high"
    }
}
```

### 3. Get Pricing Trend

**Endpoint:** `GET /wp-json/pearblog/v3/pricing/{service}/trend`

**Parameters:**
- `days` (optional): 7-90, default 30

**Response:**
```json
{
    "success": true,
    "data": {
        "current_avg": 6500.00,
        "previous_avg": 6200.00,
        "change_percent": 4.8,
        "trend": "up"
    }
}
```

### 4. Track Event

**Endpoint:** `POST /wp-json/pearblog/v3/tracking/event`

**Request:**
```json
{
    "event_type": "calculator_use",
    "service": "budowa-domu",
    "event_data": {...},
    "page_url": "https://example.com/ile-kosztuje-budowa-domu",
    "utm_source": "google",
    "utm_medium": "cpc"
}
```

### 5. Get A/B Variant

**Endpoint:** `GET /wp-json/pearblog/v3/abtest/variant`

**Parameters:**
- `test_name` (required)
- `service` (optional)

**Response:**
```json
{
    "success": true,
    "data": {
        "test_name": "h1_test",
        "variant_name": "variant_b",
        "config": {"h1": "..."}
    }
}
```

---

## 💾 Database Schema

### Table: `wp_pearblog_calculator_submissions`

Stores all calculator submissions for pricing data layer.

```sql
CREATE TABLE wp_pearblog_calculator_submissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service VARCHAR(100) NOT NULL,
    metraz DECIMAL(10,2),
    standard VARCHAR(50),
    lokalizacja VARCHAR(50),
    typ VARCHAR(50),
    min_cost DECIMAL(12,2) NOT NULL,
    max_cost DECIMAL(12,2) NOT NULL,
    avg_cost DECIMAL(12,2) NOT NULL,
    cost_per_unit DECIMAL(12,2) NOT NULL,
    session_id VARCHAR(100),
    user_id BIGINT UNSIGNED,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    converted_to_lead TINYINT(1) DEFAULT 0,
    submitted_at DATETIME NOT NULL,
    KEY service (service),
    KEY submitted_at (submitted_at)
);
```

### Table: `wp_pearblog_conversion_events`

Tracks all user interaction events for funnel analysis.

```sql
CREATE TABLE wp_pearblog_conversion_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    event_data TEXT,
    page_url VARCHAR(500),
    service VARCHAR(100),
    ab_variant VARCHAR(50),
    user_id BIGINT UNSIGNED,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    referrer VARCHAR(500),
    utm_source VARCHAR(100),
    utm_medium VARCHAR(100),
    utm_campaign VARCHAR(100),
    created_at DATETIME NOT NULL,
    KEY session_id (session_id),
    KEY event_type (event_type),
    KEY service (service)
);
```

### Table: `wp_pearblog_ab_test_variants`

Stores A/B test configuration and results.

```sql
CREATE TABLE wp_pearblog_ab_test_variants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    test_name VARCHAR(100) NOT NULL,
    variant_name VARCHAR(50) NOT NULL,
    service VARCHAR(100),
    config TEXT,
    traffic_allocation INT(3) DEFAULT 50,
    views INT UNSIGNED DEFAULT 0,
    calculator_uses INT UNSIGNED DEFAULT 0,
    form_submissions INT UNSIGNED DEFAULT 0,
    leads INT UNSIGNED DEFAULT 0,
    conversion_rate DECIMAL(5,2) DEFAULT 0.00,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL,
    UNIQUE KEY test_variant (test_name, variant_name)
);
```

---

## 🚀 Implementation Guide

### Step 1: Generate V3 Content

```php
use PearBlogEngine\Content\PoradnikV3TemplateBuilder;
use PearBlogEngine\Tenant\SiteProfile;

$profile = SiteProfile::for_site(get_current_blog_id());
$builder = new PoradnikV3TemplateBuilder($profile);

$prompt = $builder->build('budowa domu', [
    'year' => 2026,
    'service' => 'budowa-domu',
    'calculator_enabled' => true,
    'live_pricing_enabled' => true,
]);

// Send $prompt to AI (GPT-4o-mini or Claude)
$content = generate_ai_content($prompt);

// Save as post
$post_id = wp_insert_post([
    'post_title' => 'Ile kosztuje budowa domu w 2026?',
    'post_content' => $content,
    'post_status' => 'publish',
    'post_type' => 'post'
]);

// Mark as V3 template
update_post_meta($post_id, '_pearblog_template_version', 'v3');
update_post_meta($post_id, '_pearblog_service', 'budowa-domu');
```

### Step 2: Replace Placeholders

The generated content will contain placeholders that need to be replaced with actual widgets:

```php
// Replace [CALCULATOR_ENGINE] with calculator widget
$content = str_replace(
    '[CALCULATOR_ENGINE id="budowa-domu"]',
    SmartCalculatorEngine::render('budowa-domu'),
    $content
);

// Replace [LIVE_PRICING] with pricing widget
$content = str_replace(
    '[LIVE_PRICING service="budowa-domu"]',
    LivePricingDataLayer::render('budowa-domu'),
    $content
);

// Replace [LEAD_FORM] with lead form
$content = str_replace(
    '[LEAD_FORM id="mid-page"]',
    render_lead_form('mid-page', 'budowa-domu'),
    $content
);

// Update post content
wp_update_post([
    'ID' => $post_id,
    'post_content' => $content
]);
```

### Step 3: Enable Tracking

Tracking is automatically enabled for V3 pages. The `ConversionFlowTracker` checks for:
- `_pearblog_template_version` = 'v3' meta
- OR URL contains 'ile-kosztuje'

### Step 4: Create Local Variants (Optional)

```php
use PearBlogEngine\SEO\ProgrammaticLocalSEO;

// Create 15 city variants
$topic_ids = ProgrammaticLocalSEO::create_local_topics(
    'budowa-domu',
    'budowa domu',
    2026
);

// Each variant gets its own V3 page with city-specific content
```

### Step 5: Set Up A/B Tests (Optional)

```sql
INSERT INTO wp_pearblog_ab_test_variants
(test_name, variant_name, service, traffic_allocation, config, is_active, created_at)
VALUES
('cta_text', 'control', 'budowa-domu', 50, '{"primary_cta": "Oblicz koszt"}', 1, NOW()),
('cta_text', 'variant_urgency', 'budowa-domu', 50, '{"primary_cta": "Sprawdź cenę teraz"}', 1, NOW());
```

---

## ⚡ Performance

### Optimization Rules

1. **Mobile-First Design**
   - All components responsive
   - Touch-friendly calculator inputs
   - Lazy load below-fold content

2. **Fast Load Time (<2s)**
   - Minimal JavaScript (progressive enhancement)
   - Inline critical CSS
   - Defer non-critical scripts

3. **Database Performance**
   - Pricing data cached (1 hour)
   - Indexed tables (service, submitted_at, session_id)
   - Query optimization with proper WHERE clauses

4. **Asset Optimization**
   - Minified CSS/JS
   - Optimized images (WebP format)
   - Font subsetting

### Caching Strategy

- **Live Pricing:** 1 hour (wp_cache)
- **Calculator Results:** Not cached (user-specific)
- **A/B Variants:** Session-based (cookie)
- **Full Page:** 5 minutes (with cache invalidation on data update)

---

## 📊 Analytics & Metrics

### Key Metrics to Track

1. **Traffic Metrics**
   - Page views
   - Bounce rate
   - Avg time on page
   - Scroll depth

2. **Engagement Metrics**
   - Calculator usage rate (calculator_uses / page_views)
   - Form view rate (form_views / calculator_uses)
   - Conversion rate (form_submits / page_views)

3. **Data Quality**
   - Live pricing confidence level
   - Sample count growth
   - Pricing accuracy (user feedback)

4. **A/B Test Results**
   - Variant performance comparison
   - Statistical significance
   - Winner declaration

### Conversion Funnel Analysis

```php
use PearBlogEngine\Analytics\ConversionFlowTracker;

// Get metrics for last 30 days
$metrics = ConversionFlowTracker::get_conversion_metrics('budowa-domu', 30);

echo "Total Views: " . $metrics['total_views'];
echo "Calculator Uses: " . $metrics['calculator_uses'];
echo "Form Submissions: " . $metrics['form_submits'];
echo "Conversion Rate: " . $metrics['conversion_rate'] . "%";

// Analyze drop-off
$dropoff = ConversionFlowTracker::get_funnel_dropoff('budowa-domu', 30);

echo "Drop-off at Calculator: " . $dropoff['page_to_calculator']['dropoff'] . "%";
echo "Drop-off at Form: " . $dropoff['calculator_to_form']['dropoff'] . "%";
echo "Drop-off at Submit: " . $dropoff['form_to_submit']['dropoff'] . "%";
```

### WP-CLI Commands (Future)

```bash
# Generate V3 article
wp pearblog v3 generate "budowa domu" --service=budowa-domu --year=2026

# Update live pricing cache
wp pearblog v3 refresh-pricing --service=budowa-domu

# Analyze conversion funnel
wp pearblog v3 analyze-funnel --service=budowa-domu --days=30

# Export calculator data
wp pearblog v3 export-calculator-data --service=budowa-domu --format=csv
```

---

## 🎯 Success Criteria

### V3 Template is successful if:

1. **Conversion Rate:** >10% (page view → lead)
2. **Calculator Usage:** >60% of visitors
3. **Data Quality:** >50 samples per service in 90 days
4. **Load Time:** <2s (75th percentile)
5. **Mobile Conversion:** >8% (mobile devices)

### Continuous Improvement Loop

```
1. Collect Data (Calculator + Tracking)
    ↓
2. Analyze Performance (Conversion rates, drop-offs)
    ↓
3. Generate Hypotheses (A/B test ideas)
    ↓
4. Run A/B Tests (Test variants)
    ↓
5. Implement Winners (Update templates)
    ↓
[Repeat]
```

---

## 🔧 Troubleshooting

### Calculator Not Working

1. Check if tables exist: `PoradnikV3Schema::tables_exist()`
2. Verify service has base prices defined
3. Check JavaScript console for errors
4. Ensure REST API endpoint is accessible

### Live Pricing Shows "No Data"

1. Check if calculator submissions exist: `SELECT COUNT(*) FROM wp_pearblog_calculator_submissions WHERE service = 'X'`
2. Verify minimum 3 samples requirement
3. Clear cache: `wp_cache_flush()`

### Tracking Not Recording

1. Check if conversion_events table exists
2. Verify session cookie is being set
3. Check REST API permissions
4. Review ConversionFlowTracker::is_v3_landing_page() logic

---

## 📝 Summary

Poradnik V3 Template Engine is a complete solution for converting SEO traffic into leads through:

✅ **Smart Calculator** - Interactive, engaging, data-collecting
✅ **Live Pricing** - Real-time, trustworthy, self-improving
✅ **Conversion Tracking** - Detailed, actionable insights
✅ **A/B Testing** - Continuous optimization
✅ **Performance** - Fast, mobile-first, scalable

**Result:** High-converting landing pages that get better with every visitor.

---

**Version:** 3.0.0
**Last Updated:** 2026-05-04
**Maintained by:** PearBlog Engine Team

# Poradnik.pro Advanced Monetization Suite
## Complete Guide to Ads Layout Pro, Affiliate Copy Generator, and RPM Lead Fusion

**Version:** 1.0.0
**Last Updated:** 2026-05-03
**Author:** PearBlog Engine Team

---

## Table of Contents

1. [Overview](#overview)
2. [Ads Layout Pro](#ads-layout-pro)
3. [Affiliate Copy Generator](#affiliate-copy-generator)
4. [RPM Lead Fusion](#rpm-lead-fusion)
5. [Integration Guide](#integration-guide)
6. [Best Practices](#best-practices)
7. [Troubleshooting](#troubleshooting)

---

## Overview

The Advanced Monetization Suite consists of three powerful tools designed to maximize revenue from your content:

### 🎯 **Ads Layout Pro**
Professional ad placement system with intelligent positioning, A/B testing, and performance optimization for maximum CTR and RPM.

### ✍️ **Affiliate Copy Generator**
AI-powered affiliate content generation with templates, optimization, and conversion-focused copywriting.

### 📊 **RPM Lead Fusion**
Revenue Per Mille (RPM) optimization integrated with lead generation metrics for comprehensive ROI analysis.

---

## Ads Layout Pro

### Features

- **3 Placement Strategies:**
  - **Aggressive:** 6 ads max, every 2 paragraphs
  - **Balanced:** 3 ads max, every 4 paragraphs (recommended)
  - **Conservative:** 2 ads max, every 6 paragraphs

- **Intelligent Positioning:**
  - After intro (0-15% of content)
  - Early content (15-45%)
  - Mid content (45-65%)
  - Late content (65-85%)
  - Before conclusion (85-100%)

- **Ad Formats:**
  - Google AdSense (auto-optimized)
  - Custom HTML ads
  - A/B testing variants

- **Advanced Tracking:**
  - Impression tracking
  - Click tracking
  - Viewability tracking (50% visible for 1 second)
  - Position-based performance

### Getting Started

#### 1. Access the Dashboard

Navigate to **WordPress Admin → Landing Leads → Ads Layout Pro**

#### 2. Configure Settings

```php
// Enable/disable ads globally
update_option('alp_enabled', true);

// Set placement strategy
update_option('alp_strategy', 'balanced'); // aggressive, balanced, conservative

// Set ad format
update_option('alp_ad_format', 'adsense'); // adsense, custom, ab_test

// Configure AdSense
update_option('alp_adsense_publisher_id', 'ca-pub-1234567890123456');
```

#### 3. Monitor Performance

The dashboard displays:
- Total impressions
- Total clicks
- CTR (Click-Through Rate)
- Viewability percentage
- Performance by position
- Daily trends

### Ad Placement Strategies

#### Aggressive Strategy
**Best for:** High-traffic informational content (TOFU)

```php
$strategy = [
    'paragraphs_between' => 2,
    'positions' => ['after_intro', 'mid_content', 'before_conclusion', 'sidebar'],
    'max_ads' => 6,
];
```

**Pros:**
- Maximum ad impressions
- Higher revenue potential
- Good for long-form content

**Cons:**
- May impact user experience
- Not ideal for conversion-focused pages

#### Balanced Strategy (Recommended)
**Best for:** Most content types

```php
$strategy = [
    'paragraphs_between' => 4,
    'positions' => ['after_intro', 'mid_content', 'sidebar'],
    'max_ads' => 3,
];
```

**Pros:**
- Good user experience
- Optimal revenue/experience balance
- Works for most content

**Cons:**
- Lower ad density than aggressive

#### Conservative Strategy
**Best for:** Conversion-focused content (BOFU)

```php
$strategy = [
    'paragraphs_between' => 6,
    'positions' => ['mid_content'],
    'max_ads' => 2,
];
```

**Pros:**
- Best user experience
- Ideal for sales pages
- Higher conversion rates

**Cons:**
- Lowest ad revenue
- Fewer impressions

### A/B Testing Ads

Create ad variants to test performance:

```javascript
// Via AJAX
fetch(ajaxUrl, {
    method: 'POST',
    body: new FormData({
        action: 'alp_create_ab_test',
        nonce: alpData.nonce,
        name: 'Test: In-Article vs Display',
        position: 'mid_content',
        variant_a: '<ins class="adsbygoogle" data-ad-layout="in-article">...',
        variant_b: '<ins class="adsbygoogle" data-ad-format="auto">...'
    })
});
```

**Viewing Results:**

After 1,000+ impressions per variant:
- Variant A impressions & CTR
- Variant B impressions & CTR
- Statistical significance indicator
- Winner recommendation

### Custom Ad Integration

Insert custom HTML ads:

```php
// Set custom ad for a specific position
update_option('alp_custom_ad_mid_content', '
    <div class="custom-ad">
        <a href="https://example.com" target="_blank">
            <img src="banner.jpg" alt="Sponsored">
        </a>
    </div>
');
```

### Performance Tracking

#### Real-Time Metrics

```javascript
// Track custom ad events
document.addEventListener('click', function(e) {
    if (e.target.closest('.alp-ad')) {
        // Automatically tracked via beacon API
    }
});
```

#### Viewability Standard

Ads are considered "viewable" when:
- 50% of ad pixels are visible
- Visible for at least 1 second
- Matches IAB standard

---

## Affiliate Copy Generator

### Features

- **Pre-Built Templates:**
  - Booking.com (emotional, urgency, value)
  - Airbnb (unique, experience)
  - SaaS (problem-solution, social proof)

- **Power Words Library:**
  - Urgent: "Teraz", "Dzisiaj", "Natychmiast"
  - Value: "Darmowy", "Oszczędź", "Rabat"
  - Emotional: "Wymarzony", "Niesamowity", "Wyjątkowy"
  - Exclusive: "Ekskluzywny", "VIP", "Premium"

- **Copy Analysis:**
  - Readability scoring
  - Power word detection
  - CTA quality assessment
  - Length optimization

- **Optimization Engine:**
  - CTR optimization
  - Urgency injection
  - Value proposition enhancement

### Getting Started

#### 1. Access the Generator

Navigate to **WordPress Admin → Landing Leads → Affiliate Copy Gen**

#### 2. Generate Copy

**Via Admin Interface:**

1. Select affiliate type (Booking, Airbnb, SaaS)
2. Choose copy style (emotional, urgency, value, etc.)
3. Enter location or product name
4. Add optional parameters
5. Click "Generate Copy"

**Via PHP:**

```php
$copy = PoradnikAffiliateCopyGenerator::generate_copy([
    'type' => 'booking',
    'style' => 'emotional',
    'location' => 'Zakopane',
    'url' => 'https://www.booking.com/...',
    'affiliate_id' => 'your-affiliate-id',
    'power_words' => true,
]);

echo $copy['full_html'];
```

#### 3. Use Shortcodes

**Affiliate Box Shortcode:**

```
[affiliate_box
    type="booking"
    style="emotional"
    location="Zakopane"
    url="https://booking.com/..."
    affiliate_id="12345"]
```

**Affiliate CTA Shortcode:**

```
[affiliate_cta url="https://example.com" type="primary"]
    Sprawdź najlepsze oferty →
[/affiliate_cta]
```

### Copy Templates

#### Booking.com - Emotional Style

```
Headline: Znajdź wymarzony nocleg w {location}
Intro: Wyobraź sobie idealny pobyt w {location}.
       Komfortowy nocleg, świetna lokalizacja, atrakcyjna cena.
CTA: Sprawdź najlepsze oferty →
Benefits:
  ✓ Darmowa anulacja
  ✓ Płatność przy wymeldowaniu
  ✓ Najlepsze ceny gwarantowane
```

#### Booking.com - Urgency Style

```
Headline: Szybko! Sprawdź dostępność noclegów w {location}
Intro: Popularne noclegi w {location} szybko się wyprzedają.
       Zabezpiecz swój pobyt już teraz!
CTA: Zobacz dostępne terminy →
Benefits:
  ✓ Rezerwuj bez opłat
  ✓ Darmowa anulacja do 24h przed przyjazdem
  ✓ Ponad 10,000+ obiektów
```

#### SaaS - Problem-Solution Style

```
Headline: {product_name} - Rozwiązanie Twojego problemu
Intro: Masz dość {pain_point}? {product_name} pomoże Ci
       {solution} w ciągu {timeframe}.
CTA: Wypróbuj {product_name} za darmo →
Benefits:
  ✓ Darmowy okres próbny
  ✓ Bez karty kredytowej
  ✓ Anuluj w każdej chwili
```

### Copy Optimization

**Optimize for CTR:**

```php
$original = "Kliknij tutaj aby zobaczyć oferty";
$optimized = PoradnikAffiliateCopyGenerator::optimize_copy($original, 'ctr');
// Result: "Zobacz oferty"
```

**Optimize for Urgency:**

```php
$original = "Oferta dostępna teraz";
$optimized = PoradnikAffiliateCopyGenerator::optimize_copy($original, 'urgency');
// Result: "Ograniczona oferta dostępna tylko dzisiaj"
```

**Optimize for Value:**

```php
$original = "Darmowa wysyłka";
$optimized = PoradnikAffiliateCopyGenerator::optimize_copy($original, 'value');
// Result: "Całkowicie darmowa wysyłka"
```

### Copy Analysis

Analyze copy quality:

```php
$analysis = PoradnikAffiliateCopyGenerator::analyze_copy($your_text);

// Returns:
[
    'score' => 75,                  // Overall quality score (0-100)
    'readability' => 0,             // Reserved for future use
    'power_words_count' => 3,       // Number of power words found
    'cta_quality' => 80,            // CTA effectiveness (0-100)
    'length' => 45,                 // Word count
    'suggestions' => [
        'Tekst jest w optymalnej długości',
        'Dodano skuteczne wezwanie do działania',
    ],
]
```

### Custom Templates

Create and save custom templates:

```javascript
// Via AJAX
fetch(ajaxUrl, {
    method: 'POST',
    body: new FormData({
        action: 'acg_save_template',
        nonce: acgData.nonce,
        template_name: 'My Custom Template',
        headline: 'Custom headline with {variables}',
        intro: 'Custom intro text...',
        cta: 'Custom CTA',
        benefits: ['Benefit 1', 'Benefit 2', 'Benefit 3']
    })
});
```

---

## RPM Lead Fusion

### Features

- **Revenue Tracking:**
  - AdSense earnings
  - Booking.com affiliate
  - Airbnb affiliate
  - SaaS referrals
  - Lead generation value
  - Other sources

- **Lead Value Tiers:**
  - **Cold:** 10 PLN (basic inquiry)
  - **Warm:** 50 PLN (qualified lead)
  - **Hot:** 150 PLN (ready to convert)
  - **Converted:** 500 PLN (actual conversion)

- **RPM Calculation:**
  - Per-post RPM
  - Site-wide RPM
  - Source-specific RPM
  - Daily trends

- **Integration:**
  - Google Analytics 4 views
  - Landing V5 lead data
  - Funnel stage optimization
  - Automatic lead scoring

### Getting Started

#### 1. Access the Dashboard

Navigate to **WordPress Admin → Landing Leads → RPM Lead Fusion**

#### 2. Dashboard Overview

The main dashboard displays:

**Summary Cards:**
- Total Revenue (30 days)
- Total Leads
- Total Page Views
- Overall RPM

**Revenue by Source:**
- AdSense
- Booking Affiliate
- Airbnb Affiliate
- SaaS Referrals
- Lead Generation

**Lead Value Breakdown:**
- Cold leads count & value
- Warm leads count & value
- Hot leads count & value
- Converted leads count & value

**Daily Revenue Trend:**
- 30-day chart showing daily earnings
- Source breakdown available

**Top Performing Posts:**
- Posts ranked by RPM
- Revenue, views, and RPM for each
- Direct links to edit/view posts

### Revenue Tracking

#### Automatic Tracking

Revenue is tracked automatically for:

1. **Landing V5 Leads:**
```php
// Automatically tracked when lead is submitted
add_action('plv5_lead_submitted', function($lead_id, $lead_data) {
    // Lead value assigned based on tier
    // Tier calculated from email, phone, service, UTM, message
}, 10, 2);
```

2. **Funnel Stage Detection:**
```php
// Monetization optimized based on funnel stage
add_action('pearblog_funnel_stage_detected', function($post_id, $stage) {
    // TOFU: aggressive ads, low lead priority
    // MOFU: balanced ads, medium lead priority
    // BOFU: conservative ads, high lead priority
}, 10, 2);
```

#### Manual Tracking

Track revenue manually via dashboard or API:

**Via Dashboard:**
1. Go to RPM Lead Fusion
2. Click "Track Revenue"
3. Enter amount, source, post ID (optional), and note
4. Click "Save"

**Via PHP:**

```php
// Track AdSense revenue
PoradnikRPMLeadFusion::track_revenue(
    15.50,              // Amount in PLN
    'adsense',          // Source
    123,                // Post ID (optional)
    ['note' => 'May 2026 earnings']  // Metadata (optional)
);

// Track affiliate commission
PoradnikRPMLeadFusion::track_revenue(
    85.00,
    'affiliate_booking',
    456,
    ['booking_id' => 'BK123456']
);

// Track SaaS referral
PoradnikRPMLeadFusion::track_revenue(
    250.00,
    'saas',
    789,
    ['product' => 'Mailchimp', 'tier' => 'premium']
);
```

### RPM Calculation

**Formula:**
```
RPM = (Revenue / Page Views) × 1000
```

**Example:**
- Post Revenue: 150 PLN
- Page Views (30 days): 5,000
- RPM = (150 / 5,000) × 1000 = 30 PLN

**Get RPM for a Post:**

```php
$post_id = 123;
$days = 30;
$rpm = PoradnikRPMLeadFusion::calculate_rpm($post_id, $days);
echo "RPM: {$rpm} PLN";
```

### Lead Tier Calculation

Leads are automatically scored and assigned tiers:

**Scoring Factors:**
- Email provided: +20 points
- Phone provided: +30 points
- Service specified: +20 points
- Paid traffic source (Google Ads, Facebook Ads): +20 points
- Message length > 50 chars: +10 points

**Tier Assignment:**
- **80+ points:** Hot (150 PLN value)
- **50-79 points:** Warm (50 PLN value)
- **< 50 points:** Cold (10 PLN value)
- **Manually converted:** Converted (500 PLN value)

**Example:**

```php
$lead_data = [
    'email' => 'user@example.com',      // +20
    'phone' => '+48123456789',          // +30
    'service' => 'Website Design',      // +20
    'utm' => ['source' => 'google_ads'], // +20
    'message' => 'Long detailed message with specific requirements...', // +10
];
// Total: 100 points → Hot lead → 150 PLN value
```

### Funnel Stage Optimization

Revenue strategy adapts automatically based on content funnel stage:

**TOFU (Top of Funnel - Informational):**
```php
[
    'strategy' => 'aggressive',
    'ad_density' => 'high',
    'affiliate_focus' => 'informational',
    'lead_priority' => 'low',
]
```
- Maximum ad impressions
- Affiliate links to informational resources
- Lead forms lower priority

**MOFU (Middle of Funnel - Consideration):**
```php
[
    'strategy' => 'balanced',
    'ad_density' => 'medium',
    'affiliate_focus' => 'comparison',
    'lead_priority' => 'medium',
]
```
- Balanced ad placement
- Affiliate comparison tools
- Lead forms moderate priority

**BOFU (Bottom of Funnel - Decision):**
```php
[
    'strategy' => 'conservative',
    'ad_density' => 'low',
    'affiliate_focus' => 'direct',
    'lead_priority' => 'high',
]
```
- Minimal ads to avoid distraction
- Direct affiliate booking links
- Lead forms maximum priority

### Performance Analysis

**Get Dashboard Summary:**

```php
$summary = PoradnikRPMLeadFusion::get_dashboard_summary(30);

// Returns:
[
    'total_revenue' => 1250.50,
    'revenue_by_source' => [
        ['source' => 'adsense', 'total' => 450.00],
        ['source' => 'affiliate_booking', 'total' => 600.00],
        ['source' => 'lead_gen', 'total' => 200.50],
    ],
    'total_leads' => 45,
    'lead_value_by_tier' => [
        'cold' => ['count' => 20, 'value' => 10, 'total' => 200],
        'warm' => ['count' => 15, 'value' => 50, 'total' => 750],
        'hot' => ['count' => 8, 'value' => 150, 'total' => 1200],
        'converted' => ['count' => 2, 'value' => 500, 'total' => 1000],
    ],
    'total_views' => 45000,
    'overall_rpm' => 27.79,
    'daily_trend' => [
        '2026-05-01' => 42.50,
        '2026-05-02' => 38.75,
        // ...
    ],
]
```

**Get Top Posts by RPM:**

```php
$top_posts = PoradnikRPMLeadFusion::get_top_posts_by_rpm(10, 30);

// Returns array of posts sorted by RPM:
[
    [
        'post_id' => 123,
        'title' => 'Best Hotels in Zakopane',
        'rpm' => 45.50,
        'revenue' => 227.50,
        'views' => 5000,
        'url' => 'https://...',
        'edit_url' => 'https://...',
    ],
    // ...
]
```

### Data Export

Export revenue data to CSV:

**Via Dashboard:**
1. Go to RPM Lead Fusion
2. Click "Export Data"
3. Select date range
4. Download CSV file

**Via AJAX:**

```javascript
fetch(ajaxUrl, {
    method: 'POST',
    body: new FormData({
        action: 'rpmf_export_data',
        nonce: rpmfData.nonce,
        days: 30
    })
}).then(response => response.blob())
  .then(blob => {
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'rpm-data.csv';
      a.click();
  });
```

---

## Integration Guide

### System Requirements

- WordPress 5.8+
- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.2+
- Landing V5 installed and activated
- Google Analytics 4 (recommended for accurate view counts)

### Installation

All three systems are automatically loaded via `functions.php`:

```php
// Poradnik.pro Advanced Monetization Suite
require_once PEARBLOG_DIR . '/inc/poradnik-ads-layout-pro.php';
require_once PEARBLOG_DIR . '/inc/poradnik-affiliate-copy-generator.php';
require_once PEARBLOG_DIR . '/inc/poradnik-rpm-lead-fusion.php';
```

### Database Tables

**RPM Lead Fusion Revenue Table:**

```sql
CREATE TABLE wp_rpmf_revenue (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    amount decimal(10,2) NOT NULL,
    source varchar(50) NOT NULL,
    post_id bigint(20) unsigned DEFAULT NULL,
    metadata text DEFAULT NULL,
    created_at datetime NOT NULL,
    PRIMARY KEY (id),
    KEY source (source),
    KEY post_id (post_id),
    KEY created_at (created_at)
);
```

Tables are created automatically on first use.

### Configuration

#### Ads Layout Pro

```php
// Basic setup
update_option('alp_enabled', true);
update_option('alp_strategy', 'balanced');
update_option('alp_ad_format', 'adsense');
update_option('alp_adsense_publisher_id', 'ca-pub-XXXXX');
update_option('alp_tracking_enabled', true);

// Ad labels
update_option('alp_ad_label', 'Reklama');
```

#### Affiliate Copy Generator

```php
// Affiliate IDs
update_option('pearblog_booking_affiliate_id', 'your-booking-aid');
update_option('pearblog_airbnb_affiliate_id', 'your-airbnb-id');

// Disclaimer text
update_option('acg_disclaimer', 'Link partnerski - możemy otrzymać prowizję');
```

#### RPM Lead Fusion

```php
// Lead value customization
define('RPMF_LEAD_VALUE_COLD', 10);
define('RPMF_LEAD_VALUE_WARM', 50);
define('RPMF_LEAD_VALUE_HOT', 150);
define('RPMF_LEAD_VALUE_CONVERTED', 500);
```

### Hooks and Filters

#### Actions

```php
// Track custom revenue
do_action('rpmf_track_revenue', $amount, $source, $post_id, $metadata);

// Lead submitted
do_action('plv5_lead_submitted', $lead_id, $lead_data);

// Funnel stage detected
do_action('pearblog_funnel_stage_detected', $post_id, $funnel_stage);
```

#### Filters

```php
// Modify monetized content
add_filter('pearblog_monetized_content', function($content, $post_id, $profile) {
    // Your custom logic
    return $content;
}, 10, 3);

// Modify affiliate box HTML
add_filter('acg_affiliate_box_html', function($html, $params) {
    // Your custom modifications
    return $html;
}, 10, 2);

// Modify RPM calculation
add_filter('rpmf_rpm_multiplier', function($multiplier, $post_id) {
    // Adjust RPM calculation
    return $multiplier;
}, 10, 2);
```

---

## Best Practices

### Content Strategy

#### 1. Match Strategy to Funnel Stage

- **TOFU Content:** Use aggressive ad strategy
- **MOFU Content:** Use balanced strategy + affiliate boxes
- **BOFU Content:** Use conservative strategy + lead forms

#### 2. Optimize for User Experience

```php
// Don't overload short articles
if (str_word_count($content) < 500) {
    update_option('alp_strategy', 'conservative');
}

// Use aggressive only for long-form (1500+ words)
if (str_word_count($content) > 1500) {
    update_option('alp_strategy', 'aggressive');
}
```

#### 3. Test Everything

- Run A/B tests for 1,000+ impressions
- Test ad positions
- Test copy variants
- Test lead form placement

### Revenue Optimization

#### 1. Track Everything

```php
// Track all revenue sources
PoradnikRPMLeadFusion::track_revenue($amount, $source, $post_id, $metadata);

// Even small amounts add up
if ($commission > 0) {
    PoradnikRPMLeadFusion::track_revenue($commission, 'affiliate_booking', $post_id);
}
```

#### 2. Focus on High-RPM Content

Use RPM data to identify winners:

```php
$top_posts = PoradnikRPMLeadFusion::get_top_posts_by_rpm(20, 30);

// Double down on high performers
foreach ($top_posts as $post) {
    if ($post['rpm'] > 40) {
        // Promote more, create similar content
    }
}
```

#### 3. Optimize Lead Quality

```php
// Improve lead scoring by adding custom factors
add_filter('rpmf_lead_score', function($score, $lead_data) {
    // Bonus for returning visitors
    if ($lead_data['is_returning_visitor']) {
        $score += 15;
    }

    // Bonus for high-value service
    if (in_array($lead_data['service'], ['Custom Development', 'Enterprise'])) {
        $score += 25;
    }

    return $score;
}, 10, 2);
```

### Copy Writing

#### 1. Use Power Words Strategically

Don't overuse:
```
❌ Niesamowity wymarzony ekskluzywny nocleg teraz!
```

Use sparingly for impact:
```
✅ Wymarzony nocleg w Zakopanem - Zarezerwuj dzisiaj
```

#### 2. Match Copy to Intent

- **Informational:** Focus on value and education
- **Comparison:** Focus on benefits and features
- **Transactional:** Focus on urgency and action

#### 3. Test Multiple Variants

```php
// Generate 3 variants
$variants = [
    'emotional' => PoradnikAffiliateCopyGenerator::generate_copy(['style' => 'emotional', ...]),
    'urgency' => PoradnikAffiliateCopyGenerator::generate_copy(['style' => 'urgency', ...]),
    'value' => PoradnikAffiliateCopyGenerator::generate_copy(['style' => 'value', ...]),
];

// Rotate or A/B test
```

---

## Troubleshooting

### Ads Not Showing

**Issue:** Ads don't appear in content

**Solutions:**

1. Check if ads are enabled:
```php
var_dump(get_option('alp_enabled'));
// Should return: bool(true)
```

2. Check content length:
```php
// Content must have at least 3 paragraphs
$paragraph_count = substr_count($content, '<p>');
echo "Paragraphs: " . $paragraph_count;
```

3. Check AdSense publisher ID:
```php
$publisher_id = get_option('alp_adsense_publisher_id');
echo "Publisher ID: " . $publisher_id;
// Should be: ca-pub-XXXXX
```

4. Check for JavaScript errors in browser console

### Low RPM

**Issue:** RPM is lower than expected

**Solutions:**

1. Verify view counting:
```php
$post_id = 123;
$views = get_post_meta($post_id, '_pearblog_ga4_views_30d', true);
echo "Views (30d): " . $views;

// If 0, GA4 sync may not be working
```

2. Ensure revenue is being tracked:
```php
$revenue = get_post_meta($post_id, '_rpmf_total_revenue', true);
echo "Revenue: " . $revenue . " PLN";
```

3. Check source breakdown:
```php
$summary = PoradnikRPMLeadFusion::get_dashboard_summary(30);
print_r($summary['revenue_by_source']);
```

4. Consider content quality:
- Low RPM may indicate low ad viewability
- Check bounce rate and time on page
- Improve content engagement

### Copy Generator Not Working

**Issue:** Generated copy is empty or incomplete

**Solutions:**

1. Check template exists:
```php
$templates = PoradnikAffiliateCopyGenerator::TEMPLATES;
var_dump($templates['booking']['emotional']);
```

2. Verify required parameters:
```php
$params = [
    'type' => 'booking',       // Required
    'style' => 'emotional',    // Required
    'location' => 'Zakopane', // Required for location-based
];
```

3. Check for PHP errors:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Lead Values Not Calculating

**Issue:** Leads show $0 value

**Solutions:**

1. Check lead tier assignment:
```php
$lead_id = 123;
$tier = get_post_meta($lead_id, '_rpmf_lead_tier', true);
$value = get_post_meta($lead_id, '_rpmf_lead_value', true);
echo "Tier: {$tier}, Value: {$value} PLN";
```

2. Verify lead submission hook:
```php
add_action('plv5_lead_submitted', function($lead_id, $lead_data) {
    error_log('Lead submitted: ' . $lead_id);
    error_log('Lead data: ' . print_r($lead_data, true));
}, 5, 2); // Priority 5 to run before RPM Lead Fusion
```

3. Check tier thresholds:
```php
$tiers = PoradnikRPMLeadFusion::LEAD_VALUE_TIERS;
print_r($tiers);
```

### Database Issues

**Issue:** Revenue table doesn't exist

**Solution:**

Force table creation:
```php
global $wpdb;
$table = $wpdb->prefix . 'rpmf_revenue';

// Check if exists
$exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");

if (!$exists) {
    // Manually trigger creation
    PoradnikRPMLeadFusion::init();
    PoradnikRPMLeadFusion::track_revenue(0.01, 'test', null, ['init' => true]);
}
```

---

## Support

For additional help:

1. Check WordPress debug logs
2. Enable WordPress debugging:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

3. View logs at: `wp-content/debug.log`

4. Contact PearBlog support with:
   - WordPress version
   - PHP version
   - Error messages
   - Steps to reproduce issue

---

**Version History:**
- **1.0.0** (2026-05-03): Initial release

**Next Updates:**
- Machine learning ad positioning
- Real-time RPM optimization
- Advanced copy personalization
- Multi-language support

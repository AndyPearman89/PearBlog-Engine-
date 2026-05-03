# 🚀 PT24.PRO Integration Guide

**Version:** 1.0.0
**Date:** 2026-05-03
**Status:** ✅ PRODUCTION READY

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Installation](#installation)
4. [Configuration](#configuration)
5. [Usage](#usage)
6. [CTA Styles](#cta-styles)
7. [Shortcode Reference](#shortcode-reference)
8. [Analytics & Tracking](#analytics--tracking)
9. [Customization](#customization)
10. [Troubleshooting](#troubleshooting)
11. [Best Practices](#best-practices)

---

## 🎯 Overview

The PT24.PRO integration system creates a seamless conversion funnel between **Poradnik.pro** (content/SEO portal) and **PT24.pro** (lead generation/business directory portal).

### Business Model

```
Google Search
    ↓
Poradnik.pro (SEO Content)
    ↓
Strategic CTA Blocks
    ↓
PT24.pro (Local Business Listings)
    ↓
Lead Form Submission
    ↓
Lead Sale → Revenue
```

### Key Features

✅ **Smart URL Generation** - Automatic city/service mapping
✅ **3 CTA Styles** - Hybrid, banner, inline variants
✅ **Auto-Injection** - Intelligent CTA placement in content
✅ **Click Tracking** - Full analytics integration
✅ **Geo-Targeting** - City-specific business listings
✅ **A/B Testing Ready** - Multiple style variants
✅ **Mobile Optimized** - Responsive design

---

## 🏗️ Architecture

### Two-Portal Strategy

| Portal | Purpose | Monetization |
|--------|---------|--------------|
| **Poradnik.pro** | SEO content, guides, comparisons | AdSense, Affiliate, Traffic value |
| **PT24.pro** | Local businesses, lead capture | Lead sales, Premium listings, Subscriptions |

### URL Structure

**Poradnik.pro URLs:**
```
/poradnik/pompa-ciepla
/poradnik/remont-lazienki
/poradnik/fotowoltaika
```

**PT24.pro URLs:**
```
/krakow/pompa-ciepla?ref=poradnik
/warszawa/remont?ref=poradnik
/gdansk/fotowoltaika?ref=poradnik
```

### Category Mapping

The system automatically maps Poradnik topics to PT24 services:

| Poradnik Topic | PT24 Service | Example URL |
|----------------|--------------|-------------|
| pompa-ciepla | pompa-ciepla | `/krakow/pompa-ciepla` |
| remont-lazienki | remont | `/warszawa/remont` |
| fotowoltaika | fotowoltaika | `/gdansk/fotowoltaika` |
| kredyt-hipoteczny | kredyty | `/poznan/kredyty` |

---

## 💻 Installation

### Step 1: Files Created

The integration consists of 4 files:

1. **Core Integration** - `theme/pearblog-theme/inc/pt24-integration.php`
2. **CTA Template** - `theme/pearblog-theme/template-parts/pt24-cta-block.php`
3. **Styling** - `theme/pearblog-theme/assets/css/pt24-cta.css`
4. **Tracking JS** - `theme/pearblog-theme/assets/js/pt24-cta-tracking.js`

### Step 2: Register Integration in functions.php

Add to `/theme/pearblog-theme/functions.php`:

```php
/**
 * Load PT24.PRO Integration
 */
require_once get_template_directory() . '/inc/pt24-integration.php';

/**
 * Enqueue PT24 Assets
 */
function pearblog_enqueue_pt24_assets() {
    // CSS
    wp_enqueue_style(
        'pt24-cta',
        get_template_directory_uri() . '/assets/css/pt24-cta.css',
        [],
        '1.0.0'
    );

    // JavaScript
    wp_enqueue_script(
        'pt24-tracking',
        get_template_directory_uri() . '/assets/js/pt24-cta-tracking.js',
        [],
        '1.0.0',
        true
    );

    // Localize script for AJAX
    wp_localize_script('pt24-tracking', 'pearblogData', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('pt24_tracking'),
    ]);
}
add_action('wp_enqueue_scripts', 'pearblog_enqueue_pt24_assets');
```

### Step 3: Create Database Table

The system will automatically create the tracking table on theme activation. To manually create it:

```php
// Run once
PearBlog_PT24_Integration::create_tables();
```

SQL schema:
```sql
CREATE TABLE wp_pt24_clicks (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    timestamp datetime NOT NULL,
    service varchar(100) NOT NULL,
    city varchar(50) NOT NULL,
    post_id bigint(20) NOT NULL,
    url text NOT NULL,
    user_ip varchar(45) NOT NULL,
    user_agent text NOT NULL,
    PRIMARY KEY (id),
    KEY post_id (post_id),
    KEY service (service),
    KEY city (city),
    KEY timestamp (timestamp)
);
```

---

## ⚙️ Configuration

### WordPress Options

Configure via WordPress admin or programmatically:

```php
// Enable/disable integration
update_option('pt24_integration_enabled', true);

// Set default city
update_option('pt24_default_city', 'warszawa');

// Set PT24 base URL (if different)
update_option('pt24_base_url', 'https://pt24.pro');
```

### Post Meta Configuration

For individual posts, set:

```php
// Specify service category
update_post_meta($post_id, 'pt24_service_category', 'pompa-ciepla');

// Target specific cities
update_post_meta($post_id, 'pt24_target_cities', ['krakow', 'warszawa']);

// Custom CTA positions (paragraph indices)
update_post_meta($post_id, 'pt24_cta_positions', [3, 7]);
```

---

## 🎨 Usage

### Method 1: Shortcode (Manual)

Add shortcode anywhere in post content:

```
[pt24_cta service="pompa-ciepla" city="krakow"]
```

**Parameters:**
- `service` - Service slug (required)
- `city` - City slug (optional, defaults to "auto")
- `style` - CTA style: `hybrid`, `card`, `banner`, `inline` (default: `hybrid`)
- `title` - Custom title text
- `cta_text` - Custom button text

**Examples:**

```
[pt24_cta service="remont" city="warszawa" style="card"]

[pt24_cta service="fotowoltaika" style="banner" title="Sprawdź instalatorów fotowoltaiki"]

[pt24_cta service="kredyt" city="gdansk" style="inline" cta_text="Porównaj oferty"]
```

### Method 2: Auto-Injection (Automatic)

CTAs are automatically injected into post content at strategic positions:

- **Position 1:** After 33% of content
- **Position 2:** After 66% of content (if post is long enough)
- **Final CTA:** At end of post

**To disable auto-injection for a specific post:**

```php
// In post meta
update_post_meta($post_id, 'pt24_auto_inject', false);
```

**To disable globally:**

```php
// In wp-config.php or functions.php
add_filter('pt24_integration_enabled', '__return_false');
```

### Method 3: PHP Template

Use in theme templates:

```php
<?php
echo PearBlog_PT24_Integration::generate_cta_html([
    'service' => 'pompa-ciepla',
    'city' => 'krakow',
    'style' => 'hybrid',
]);
?>
```

---

## 🎭 CTA Styles

### 1. Hybrid Style (Recommended)

**Best for:** Mid-content and end-of-article CTAs

**Features:**
- Large visual card
- Benefit checkmarks
- Trust badge with ratings
- Gradient background (purple/violet)

**Preview:**
```
┌─────────────────────────────────────────┐
│  Sprawdź ceny i dostępne firmy          │
│  ✓ Porównanie ofert                     │
│  ✓ Firmy lokalne                        │
│  ✓ Aktualne ceny                        │
│  [Zobacz oferty →]         ⭐ 4.8/5     │
│  Darmowe wyceny • Bez zobowiązań        │
└─────────────────────────────────────────┘
```

### 2. Card Style

**Best for:** Standalone CTAs, end-of-article

Similar to Hybrid but optimized for standalone placement.

### 3. Banner Style

**Best for:** Top of article, sticky positions

**Features:**
- Compact horizontal layout
- Green gradient (conversion-focused)
- Quick action button

**Preview:**
```
┌─────────────────────────────────────────┐
│  Sprawdź firmy w okolicy  [Zobacz →]    │
│  Sprawdzone firmy w Twojej okolicy       │
└─────────────────────────────────────────┘
```

### 4. Inline Style

**Best for:** Mid-content, between paragraphs

**Features:**
- Minimal design
- Doesn't disrupt reading flow
- Light background with border accent

**Preview:**
```
┌─────────────────────────────────────────┐
│ 💡  Sprawdź firmy w okolicy             │
│     Otrzymaj bezpłatne oferty           │
│     [Zobacz oferty →]                   │
└─────────────────────────────────────────┘
```

---

## 📊 Analytics & Tracking

### Automatic Tracking

The system tracks:

1. **Impressions** - CTA was displayed
2. **Visibility** - CTA was 50%+ visible in viewport
3. **Clicks** - User clicked CTA button

### Data Collected

For each event:
- Timestamp
- Service category
- City
- Post ID
- URL clicked
- User IP
- User agent
- Scroll depth
- Viewport size
- CTA style

### Database Queries

**Get stats for a post:**

```php
$stats = PearBlog_PT24_Integration::get_post_stats($post_id);

echo "Total clicks: " . $stats['total_clicks'];
print_r($stats['clicks_by_city']);
print_r($stats['clicks_by_service']);
```

**Get global statistics:**

```php
global $wpdb;
$table = $wpdb->prefix . 'pt24_clicks';

// Total clicks today
$today_clicks = $wpdb->get_var("
    SELECT COUNT(*) FROM $table
    WHERE DATE(timestamp) = CURDATE()
");

// Top cities
$top_cities = $wpdb->get_results("
    SELECT city, COUNT(*) as clicks
    FROM $table
    GROUP BY city
    ORDER BY clicks DESC
    LIMIT 10
", ARRAY_A);

// Top services
$top_services = $wpdb->get_results("
    SELECT service, COUNT(*) as clicks
    FROM $table
    GROUP BY service
    ORDER BY clicks DESC
    LIMIT 10
", ARRAY_A);
```

### Google Analytics Integration

The tracking script automatically sends events to Google Analytics (if gtag.js is loaded):

**Events sent:**
- `pt24_cta_click` - User clicked CTA
- `pt24_cta_visible` - CTA became visible

**Event parameters:**
- service
- city
- cta_style
- scroll_depth

---

## 🎨 Customization

### Custom Category Mapping

Edit mapping in `inc/pt24-integration.php`:

```php
private static $category_mapping = [
    'pompa-ciepla' => 'pompa-ciepla',
    'remont' => 'remont',
    // Add your mappings:
    'nowy-temat' => 'nowa-usluga',
];
```

### Custom City List

Edit major cities:

```php
private static $major_cities = [
    'warszawa',
    'krakow',
    // Add more cities:
    'katowice',
    'rzeszow',
];
```

### Custom CSS Styling

Override styles in your theme:

```css
/* Change gradient colors */
.pt24-cta--hybrid {
    background: linear-gradient(135deg, #your-color-1 0%, #your-color-2 100%);
}

/* Change button colors */
.pt24-cta__button {
    background: #your-button-color;
    color: #your-text-color;
}
```

### Custom CTA Text

Use filters:

```php
// Change default title
add_filter('pt24_cta_default_title', function($title) {
    return 'Twój własny tytuł';
});

// Change default button text
add_filter('pt24_cta_default_button_text', function($text) {
    return 'Kliknij tutaj';
});
```

---

## 🔧 Troubleshooting

### CTAs Not Showing

**Check 1:** Integration enabled?
```php
$enabled = get_option('pt24_integration_enabled', true);
var_dump($enabled); // Should be true
```

**Check 2:** Assets enqueued?
```php
// View page source, check for:
// - /assets/css/pt24-cta.css
// - /assets/js/pt24-cta-tracking.js
```

**Check 3:** Template file exists?
```bash
ls theme/pearblog-theme/template-parts/pt24-cta-block.php
```

### Tracking Not Working

**Check 1:** AJAX available?
```javascript
console.log(typeof pearblogData); // Should be 'object'
console.log(pearblogData.ajaxurl); // Should be '/wp-admin/admin-ajax.php'
```

**Check 2:** Database table exists?
```sql
SHOW TABLES LIKE 'wp_pt24_clicks';
```

**Check 3:** Manually create table:
```php
PearBlog_PT24_Integration::create_tables();
```

### Wrong City Detected

**Set city cookie:**
```javascript
document.cookie = 'pt24_user_city=krakow; path=/; max-age=31536000';
```

**Set in post meta:**
```php
update_post_meta($post_id, 'pt24_target_cities', ['krakow']);
```

---

## ✅ Best Practices

### Content Strategy

1. **3 CTAs per article maximum**
   - Intro/early (banner style)
   - Mid-content (inline style)
   - End (hybrid/card style)

2. **Match service to content**
   - Article about heat pumps → link to heat pump installers
   - Article about renovation → link to renovation companies

3. **Use city-specific content**
   - Mention city in article content
   - Use local examples and case studies

### CTA Placement

1. **Natural integration**
   - Place after explaining the problem
   - Place after showing benefits
   - Place after providing value

2. **Spacing**
   - Minimum 500 words between CTAs
   - Don't stack multiple CTAs

3. **Mobile optimization**
   - Use sticky banner for mobile
   - Ensure buttons are thumb-friendly

### A/B Testing

Test different variants:

```php
// Randomly assign style
$styles = ['hybrid', 'card', 'banner'];
$random_style = $styles[array_rand($styles)];

echo do_shortcode("[pt24_cta service='remont' style='$random_style']");
```

Track results and optimize for highest CTR.

---

## 📈 Expected Results

### Conversion Funnel Metrics

| Metric | Expected Range | Good | Excellent |
|--------|----------------|------|-----------|
| **CTA Click Rate** | 2-5% | 5-8% | 8%+ |
| **PT24 Bounce Rate** | 40-60% | 30-40% | <30% |
| **Form Submission** | 10-20% | 20-30% | 30%+ |
| **Overall Conversion** | 0.2-1% | 1-2% | 2%+ |

### Revenue Estimates

**Per 1,000 Poradnik visitors:**
- 30-50 clicks to PT24 (3-5% CTR)
- 3-10 form submissions (10-20% conversion)
- 1-3 sales (30% close rate)
- **Revenue:** €20-150 per 1,000 visitors

**Scaling:**
- 10,000 visitors/month → €200-1,500/month
- 100,000 visitors/month → €2,000-15,000/month

---

## 🎯 Success Metrics

Track these KPIs:

1. **Traffic Metrics**
   - Poradnik page views
   - PT24 referral traffic
   - Bounce rate on PT24

2. **Engagement Metrics**
   - CTA impressions
   - CTA click-through rate
   - Time on PT24 after click

3. **Conversion Metrics**
   - Lead form starts
   - Lead form completions
   - Lead quality score

4. **Revenue Metrics**
   - Leads generated
   - Leads sold
   - Revenue per article
   - ROI per visitor

---

## 🔐 Security

The integration includes:

✅ **Nonce verification** - All AJAX requests
✅ **Input sanitization** - All user inputs
✅ **SQL injection protection** - Prepared statements
✅ **XSS prevention** - Escaped output
✅ **CSRF protection** - WordPress nonces

---

## 📞 Support

**Documentation:** This guide
**Issues:** GitHub repository issues
**Updates:** Follow repository for updates

---

## 🚀 Deployment Checklist

Before going live:

- [ ] All 4 files uploaded to theme
- [ ] Assets registered in functions.php
- [ ] Database table created
- [ ] Test shortcode on draft post
- [ ] Test auto-injection
- [ ] Verify tracking works
- [ ] Check mobile responsive
- [ ] Test on multiple browsers
- [ ] Set up analytics
- [ ] Monitor first 100 clicks

---

**Version:** 1.0.0
**Last Updated:** 2026-05-03
**Status:** Production Ready ✅

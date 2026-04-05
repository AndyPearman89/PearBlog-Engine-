# PearBlog Theme v5.2

**SEO-first WordPress theme with AI personalization, monetization, and multisite branding.**

## Overview

PearBlog Theme is a frontend system for AI-powered content sites. It combines an SEO-optimised static layout with a dynamic AI personalization layer that adapts to every visitor.

**Core principle:** Server-rendered content for search engines, progressive JavaScript UX for users — no SEO impact.

---

## Features

### SEO Layout

Every single post follows a mandatory structure:

1. H1 → TL;DR → Ad (top) → Affiliate (top) → TOC → Content (split at 33%/66%)
2. Ads (middle / bottom) → Affiliate (middle / bottom) → FAQ → Related Posts

### AI Personalization Engine (v4)

The frontend adapts in real-time based on:

- **User context** — location, device, behavior (`inc/user-context.php`)
- **Dynamic content** — headlines, CTA copy, section order (`inc/dynamic-content.php`)
- **A/B testing** — automatic headline testing with daily winner detection (`inc/ai-optimizer.php`)
- **Behavior tracking** — scroll depth, time on page, clicks (`inc/behavior-tracking.php`)
- **Smart monetization** — ads shown only to engaged users above 50% scroll

### Monetization

- Auto ad injection every N paragraphs (configurable)
- Sticky mobile ads
- Affiliate priority: Booking.com → Airbnb → fallback CTA
- SaaS CTA injection via keyword matching
- Per-post revenue tracking (lifetime + daily)
- Smart CTA placement at 60 % content depth

### Performance

- Lazy loading images
- Core Web Vitals optimised
- ~8 KB gzipped personalization JS
- Async data collection, cached user context

### Multisite

- Dynamic branding, colors, logo per site
- Site-specific configuration via `pb_get_site_config()`

---

## Directory Structure

```
theme/pearblog-theme/
├── style.css                        # Theme metadata
├── functions.php                    # Theme setup (v4.0.0)
├── header.php / footer.php          # Layout frames
├── index.php                        # Homepage
├── single.php                       # Single post (SEO layout)
├── category.php                     # Category archive
├── assets/
│   ├── css/
│   │   ├── base.css                 # Design system variables
│   │   ├── components.css           # Component styles
│   │   ├── utilities.css            # Utility classes
│   │   ├── blocks-editor.css        # Gutenberg editor styles
│   │   └── analytics-admin.css      # Admin analytics styles
│   └── js/
│       ├── app.js                   # Main application JS
│       ├── personalization.js       # AI personalization client
│       ├── blocks-editor.js         # Gutenberg block editor
│       ├── lazyload.js              # Image lazy loading
│       └── customizer-preview.js    # Live Customizer preview
├── inc/
│   ├── ui.php                       # UI helpers
│   ├── layout.php                   # Layout helpers
│   ├── components.php               # Component registration
│   ├── performance.php              # PageSpeed, caching
│   ├── monetization.php             # Ad injection, revenue tracking
│   ├── affiliate-api.php            # Booking/Airbnb affiliate API
│   ├── customizer.php               # WordPress Customizer settings
│   ├── gutenberg-blocks.php         # Custom Gutenberg blocks
│   ├── widgets.php                  # Widget definitions
│   ├── analytics-page.php           # Analytics dashboard
│   ├── dashboard-widget.php         # WP Dashboard widget
│   ├── user-context.php             # User context detection
│   ├── dynamic-content.php          # Content adaptation
│   ├── ai-optimizer.php             # A/B testing engine
│   ├── behavior-tracking.php        # User metrics
│   ├── ab-testing-metabox.php       # Post editor metabox
│   ├── email-list.php               # Email list integrations
│   └── lead-generation.php          # Lead capture forms
├── template-parts/
│   ├── hero.php                     # Hero component
│   ├── card.php / grid.php          # Card / grid layouts
│   ├── block-ads.php                # Ad blocks
│   ├── block-affiliate.php          # Affiliate blocks
│   ├── block-cta.php                # CTA blocks
│   ├── block-saas-cta.php           # SaaS CTA blocks
│   ├── block-faq.php                # FAQ accordion
│   ├── block-toc.php                # Table of contents
│   ├── block-related.php            # Related posts
│   ├── form-email-subscribe.php     # Email subscribe form
│   └── form-lead-capture.php        # Lead capture form
└── examples/
    └── affiliate-usage-examples.php # Affiliate integration examples
```

---

## Installation

1. Upload `pearblog-theme/` to `/wp-content/themes/`.
2. Activate in **Appearance → Themes**.
3. Configure options (colors, logo, AdSense, API keys) in the Customizer or via `update_option()`.

---

## Configuration

### Colors & Branding

```php
update_option('pearblog_primary_color', '#your-color');
update_option('pearblog_secondary_color', '#your-color');
update_option('pearblog_logo_url', 'https://your-site.com/logo.png');
```

### AdSense

```php
update_option('pearblog_ads_enabled', true);
update_option('pearblog_adsense_client', 'ca-pub-XXXXXXXXXXXXXXXX');
update_option('pearblog_auto_ad_injection', true);
update_option('pearblog_ad_injection_paragraphs', 3);
update_option('pearblog_sticky_mobile_cta', true);
```

### AI Personalization

```php
update_option('pearblog_ai_personalization', true);
update_option('pearblog_ab_testing', true);
update_option('pearblog_smart_recommendations', true);
update_option('pearblog_behavior_tracking', true);
```

---

## Database Tables

Created automatically on theme activation:

| Table | Purpose |
|-------|---------|
| `wp_pb_user_analytics` | Per-session context (device, country, traffic source, segment) |
| `wp_pb_user_metrics` | Per-session metrics (scroll, time, clicks, ad views) |
| `wp_pb_ab_tests` | A/B test impressions and clicks per variant |

---

## Privacy & GDPR

- **Cookies:** `pb_session_id` (1 day), `pb_behavior` (30 days)
- All data is anonymous — no PII stored
- IP addresses hashed
- Opt-out mechanisms available

---

## License

GNU General Public License v2 or later — <http://www.gnu.org/licenses/gpl-2.0.html>

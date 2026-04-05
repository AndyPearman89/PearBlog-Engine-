# PearBlog Theme v5.1

**SEO-first WordPress theme with AI personalization, monetization, reading UX, and multisite branding.**

## Overview

PearBlog Theme is the frontend system for AI-powered content sites. It combines a server-rendered SEO layout with a dynamic AI personalization layer that adapts to every visitor.

**Core principle:** Static HTML for search engines, progressive JavaScript UX for humans — no SEO impact.

---

## What's New in v5.1

| Feature | Description |
|---------|-------------|
| **Reading progress bar** | Sticky top indicator (`#pb-reading-progress-bar`) fills as user scrolls |
| **Dark mode toggle** | Moon/sun button (`#pb-dark-mode-toggle`); persists via `localStorage`; respects `prefers-color-scheme` |
| **Search panel** | Slide-down form (`#pb-search-panel`); triggered from header icon; Escape/outside-click to close |
| **Sticky header** | `.pb-nav--sticky` class toggled on scroll; header shrinks with box-shadow |
| **Google Fonts** | Poppins (display) + Inter (UI) loaded via `wp_enqueue_style` with `display=swap` |
| **`page.php`** | Full static page template (breadcrumbs, hero, featured image, content, share) |
| **`search.php`** | Search results with card grid, result count, refine form, "no results" + category browser |
| **`404.php`** | Error page: 404 hero, search form, popular posts grid, category browser |
| **Multi-column footer** | Brand column + 2 widget areas (`footer-1`, `footer-2`) + back-to-top button |
| **CSS variables** | `--pb-font-display` (Poppins) + `--pb-font-ui` (Inter) + `--pb-header-height` |

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
- Smart CTA placement at 60% content depth

### Performance

- Lazy loading images (`lazyload.js`)
- Core Web Vitals optimised
- ~8 KB gzipped personalization JS
- Async data collection, cached user context
- Google Fonts with `display=swap`

### Multisite

- Dynamic branding, colors, logo per site
- Site-specific configuration via `pb_get_site_config()`

---

## Directory Structure

```
theme/pearblog-theme/
├── style.css                        # Theme metadata
├── functions.php                    # Theme setup v5.1.0
├── index.php                        # Homepage (hero + card grid)
├── single.php                       # Single post (SEO layout, 12 elements)
├── page.php                         # Static page (NEW v5.1)
├── search.php                       # Search results (NEW v5.1)
├── 404.php                          # Error page (NEW v5.1)
├── category.php                     # Category archive
├── assets/
│   ├── css/
│   │   ├── base.css                 # Design system variables + sticky header + search panel
│   │   ├── components.css           # Component styles (cards, hero, CTA, 404, search)
│   │   ├── utilities.css            # Utility classes
│   │   ├── blocks-editor.css        # Gutenberg editor styles
│   │   └── analytics-admin.css      # Admin analytics styles
│   └── js/
│       ├── app.js                   # Main app: dark mode, sticky header, search panel,
│       │                            #   reading progress, TOC, back-to-top, mobile menu
│       ├── personalization.js       # AI personalization client (conditional)
│       ├── lazyload.js              # Image lazy loading
│       └── customizer-preview.js    # Live Customizer preview
├── inc/
│   ├── layout.php                   # Header (progress bar, dark mode, search, sticky nav)
│   │                                # Footer (multi-column, back-to-top)
│   ├── components.php               # Component registration + Schema.org helpers
│   ├── ui.php                       # UI helpers (breadcrumbs, pagination, social share)
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
│   ├── block-tldr.php               # TL;DR summary block
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
3. Configure via **WP Admin → PearBlog Engine** (API keys, AdSense, Booking.com, image generation).

---

## JavaScript — `app.js` Functions

| Function | Trigger | Description |
|----------|---------|-------------|
| `initDarkMode()` | Load | Applies saved `localStorage` preference or system preference; toggles `.pb-dark-mode` |
| `initStickyHeader()` | Scroll | Adds `.pb-nav--sticky` after 60px scroll |
| `initReadingProgress()` | Scroll | Updates `#pb-reading-progress` bar width 0–100% |
| `initSearchPanel()` | Click | Opens/closes `#pb-search-panel`; autofocuses input |
| `initMobileMenu()` | Click | Toggles `.pb-menu.active` on hamburger click |
| `initTOC()` | Scroll | Highlights active TOC link; smooth-scrolls on click |
| `initFAQ()` | Click | Accordion toggle on `.pb-faq-question` |
| `initBackToTop()` | Click | Smooth-scrolls to top via `#pb-back-to-top` |
| `initSmoothScroll()` | Click | Smooth-scrolls all `a[href^="#"]` links |

---

## Configuration

### Colors & Branding

```php
update_option('pearblog_primary_color', '#2563eb');
update_option('pearblog_secondary_color', '#7c3aed');
update_option('pearblog_logo_url', 'https://your-site.com/logo.png');
```

### AdSense

```php
update_option('pearblog_adsense_publisher_id', 'ca-pub-XXXXXXXXXXXXXXXX');
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

### Dark Mode / Search Panel

Both are always active — no configuration needed. Dark mode respects `prefers-color-scheme` on first visit and stores user preference in `localStorage.pb_dark_mode`.

---

## Widget Areas

| ID | Name | Location |
|----|------|----------|
| `sidebar-1` | Sidebar | Single post + page sidebar |
| `footer-1` | Footer Column 1 | Footer grid column 2 |
| `footer-2` | Footer Column 2 | Footer grid column 3 |
| `after-post` | After Post Content | Below article content |

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

## Typography

| Role | Font | Variable |
|------|------|----------|
| Display / Headings | Poppins | `--pb-font-display` |
| UI / Body | Inter | `--pb-font-ui` |
| Code / Mono | JetBrains Mono | `--pb-font-mono` |

---

## License

GNU General Public License v2 or later — <http://www.gnu.org/licenses/gpl-2.0.html>

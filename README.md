# PearBlog Engine PRO

**Enterprise Frontend Operating System (FOS) for AI SEO SaaS**

> Not just a theme. A complete frontend operating system.

## 📸 Live Demo

![PearBlog.pro Front Page](.github/images/pearblog-frontpage-screenshot.png)

*Screenshot of PearBlog.pro showcasing the Frontend Operating System in action*

## 🚀 What's New in v2 PRO

PearBlog has evolved from a theme into a **Frontend Operating System (FOS)** - an enterprise-grade platform for maximum UX, SEO, and monetization with multisite white-label support.

### Major Upgrades

- ✨ **Frontend Operating System Architecture** - Modular, scalable, enterprise-ready
- 🌓 **Dark Mode** - Auto-detection + manual toggle with smooth transitions
- 📑 **Table of Contents** - Auto-generated from H2/H3, sticky sidebar with progress
- 💰 **Auto Ad Injection Engine** - Smart ad placement every X paragraphs
- ⚡ **Performance Module** - Critical CSS inline, preloading, Gzip compression
- 🎯 **Smart Monetization** - CTR tracking, affiliate automation, revenue analytics
- 🎨 **Grid/List Views** - Flexible post layouts with view switcher
- 🎬 **Video Hero** - Support for video backgrounds
- 🤖 **AI Blocks** - Dynamic content sections and recommendations
- 🎨 **Enhanced Design System** - Complete utility class library

## Repository Contents

### `/mu-plugins/pearblog-engine/` - AI Content Generation Engine

Enterprise-grade AI SEO content pipeline with specialized travel content builders:

**Content Generation:**
- ✅ **PromptBuilder** - Generic SEO content for any industry
- ✅ **TravelPromptBuilder** - Structured travel content with mandatory sections
- ✅ **BeskidyPromptBuilder** - Enhanced Beskidy mountains content with weather + day planner
- ✅ **MultiLanguageTravelBuilder** - True localization for PL/EN/DE markets (not translation)
- ✅ **PromptBuilderFactory** - Automatic builder selection based on industry keywords

**Features:**
- 🗻 **Weather-Aware Content** - Seasonal recommendations, weather impact, what to bring
- 📅 **AI Day Planner** - Morning/midday/evening itineraries with time estimates
- 🔄 **Plan B Alternatives** - Indoor/backup options for bad weather
- 🌍 **Multi-Language Localization** - Culturally adapted content (not just translated)
- ✅ **Content Validator** - Ensures all mandatory sections and quality standards
- 🎯 **Intent-Aware** - Adapts to informational, navigational, or transactional queries
- 💰 **Travel Monetization** - Natural accommodation recommendations with soft CTAs

**See:** [`TRAVEL-CONTENT-ENGINE.md`](TRAVEL-CONTENT-ENGINE.md) for full documentation
**Quick Ref:** [`BESKIDY-QUICK-REFERENCE.md`](BESKIDY-QUICK-REFERENCE.md) for quick access

### `/theme/pearblog-theme/` v2 PRO

Production-ready Frontend Operating System featuring:

**SEO & Performance:**
- ✅ **Critical CSS Inline** - Instant above-the-fold rendering
- ✅ **Auto-Generated TOC** - From H2/H3 headings with reading progress
- ✅ **Semantic HTML** - Schema.org Article, FAQPage, BreadcrumbList
- ✅ **Lazy Loading** - Images, iframes, videos, background images
- ✅ **Core Web Vitals Optimized** - LCP, FID, CLS tracking

**UX & Design:**
- ✅ **Dark Mode** - System preference detection + manual toggle
- ✅ **Grid/List Views** - Flexible post layouts with view persistence
- ✅ **Video Hero** - Background video support with overlay
- ✅ **Sticky TOC** - Sidebar navigation with active section highlighting
- ✅ **Mobile-First** - Touch-friendly, responsive, fast

**Monetization:**
- ✅ **Auto Ad Injection** - Smart placement every X paragraphs
- ✅ **Sticky Mobile Ads** - Bottom CTA with close button
- ✅ **Affiliate Automation** - Auto-convert product links
- ✅ **Smart CTA Placement** - Content analysis for optimal positioning
- ✅ **Revenue Tracking** - Per-post monetization analytics

**Enterprise & Multisite:**
- ✅ **White-Label Ready** - Complete multisite branding system
- ✅ **pb_get_site_config()** - Centralized configuration API
- ✅ **Layout Variants** - Default, minimal, magazine styles
- ✅ **Feature Toggles** - Per-site enable/disable controls

## Quick Start

### Installation

```bash
# Copy theme to WordPress installation
cp -r theme/pearblog-theme /path/to/wordpress/wp-content/themes/

# Or create a symlink for development
ln -s $(pwd)/theme/pearblog-theme /path/to/wordpress/wp-content/themes/pearblog-theme
```

### Activation

1. Log in to WordPress Admin
2. Navigate to **Appearance → Themes**
3. Activate **PearBlog Theme**

## Features

### Page Types
- **Homepage** - Hero + cards grid + CTA
- **Single Post** - Full SEO layout with all components
- **Category Pages** - Archive with category header

### Components
- Hero (dynamic header with gradient/image)
- Card (article listing)
- Related Posts (internal linking)
- FAQ (with Schema.org markup)
- Ads (monetization blocks)

### SEO Features
- Breadcrumbs
- Schema.org Article markup
- Schema.org FAQPage markup
- Meta descriptions
- Semantic HTML structure
- Internal linking

### Performance
- Lazy loading images
- Minimal JavaScript footprint
- CSS variables for theming
- No external dependencies
- Core Web Vitals optimized

## Documentation

Full documentation is available in [`/theme/pearblog-theme/README.md`](theme/pearblog-theme/README.md)

## Architecture

```
PearBlog Engine v1
├── Components (Hero, Card, Related, FAQ, Ads)
├── Design System (CSS Variables)
├── SEO Layout (H1, Intro, TL;DR, Sections, FAQ, Related, CTA)
├── Performance (Lazy Load, Minimal JS)
├── Mobile First (Responsive)
├── Monetization (AdSense, CTA, Affiliate)
└── Multisite (Dynamic Branding)
```

## Use Cases

Perfect for:
- AI-powered content sites
- SEO-focused blogs
- SaaS marketing sites
- Multisite networks
- Affiliate marketing sites
- Content monetization

## Roadmap

### v1.0 (Current)
- ✅ Core theme structure
- ✅ All essential components
- ✅ SEO optimization
- ✅ Performance features
- ✅ Multisite support

### v2.0 (Planned)
- UI PRO components
- Admin dashboard
- Custom widgets
- Advanced customization panel
- Gutenberg blocks
- Page builder integration

## Tech Stack

- **PHP** 7.4+
- **WordPress** 5.9+
- **CSS3** with CSS Variables
- **Vanilla JavaScript** (no jQuery)
- **HTML5** semantic markup

## License

GNU General Public License v2 or later

## Support

For issues, questions, or feature requests, please open an issue in this repository.

---

**Built for AI + SEO + SaaS**
## 🏗️ Architecture

```
PearBlog v2 PRO - Frontend Operating System
├── Core Modules
│   ├── performance.php    - Critical CSS, preloading, optimization
│   ├── monetization.php   - Auto ads, affiliate, CTR tracking
│   ├── ui.php            - UI utilities and helpers
│   ├── layout.php        - Layout rendering system
│   └── components.php    - Component registration & Schema.org
│
├── Template Components
│   ├── hero.php          - Video/image/gradient hero
│   ├── card.php          - Article cards
│   ├── grid.php          - Grid/list view system
│   ├── block-toc.php     - Table of Contents
│   ├── block-cta.php     - Dynamic CTA blocks
│   ├── block-faq.php     - FAQ with Schema.org
│   ├── block-related.php - Internal linking
│   └── block-ads.php     - Monetization blocks
│
├── CSS Architecture
│   ├── base.css          - Variables, reset, typography
│   ├── components.css    - All component styles
│   └── utilities.css     - 100+ utility classes
│
└── JavaScript Modules
    ├── lazyload.js       - Advanced lazy loading
    └── app.js            - Dark mode, TOC, FAQ, interactions
```

## 📦 What's Included

### Page Templates
- **index.php** - Homepage with hero, featured posts, grid
- **single.php** - Full SEO layout with TOC integration
- **category.php** - Category archive with cluster SEO

### Components (Template Parts)
- **Hero v2** - Gradient, image, or video backgrounds with CTA
- **Card System** - Grid/list views with featured post support
- **Grid** - Flexible layout with view switcher and filtering
- **TOC** - Auto-generated from H2/H3 with sticky sidebar
- **CTA Blocks** - Affiliate, lead capture, click variants
- **FAQ** - Accordion with Schema.org markup
- **Related Posts** - SEO-optimized internal linking
- **Ads Engine** - Auto injection and sticky mobile ads

### Modules (inc/)
- **performance.php** - Critical CSS, preloading, Gzip, caching
- **monetization.php** - Ad injection, affiliate automation, tracking
- **ui.php** - Breadcrumbs, social share, pagination, reading time
- **layout.php** - Header, footer, TL;DR, CTA rendering
- **components.php** - Registration, Schema.org, shortcodes

### Assets
- **base.css** - Design system foundation
- **components.css** - Complete component library
- **utilities.css** - Utility-first helpers
- **lazyload.js** - Intersection Observer lazy loading
- **app.js** - All interactive features

## 🎯 Key Features Deep Dive

### Dark Mode System
```php
// Auto-detection + manual toggle
localStorage preference + system preference
Smooth transitions for all elements
Complete variable system for dark theme
Dark mode toggle button with icon switching
```

### Table of Contents (TOC)
```php
// Auto-generated from content
Extracts H2 and H3 headings
Adds IDs to headings automatically
Sticky sidebar navigation
Reading progress bar
Active section highlighting on scroll
Mobile collapsible version
```

### Auto Ad Injection
```php
// Smart placement engine
Inject ads every X paragraphs (configurable)
Multiple ad positions (top, middle, bottom)
Scroll-depth tracking for optimization
CTR zone detection
Sticky mobile ads with close button
```

### Multisite Configuration
```php
// Centralized config system
pb_get_site_config() - Single source of truth
Per-site colors (primary/secondary/accent)
Per-site logos (light/dark variants)
Per-site hero styles
Per-site layout variants
Feature toggles per site
AI feature flags
```

### Performance Optimization
```php
// Enterprise-grade performance
Critical CSS inline for instant rendering
DNS prefetch for external resources
Resource preloading (images, fonts)
Lazy loading (images, iframes, videos, backgrounds)
Gzip compression
Cache control headers
Core Web Vitals tracking
```

## 🔧 Configuration

### Multisite Branding
```php
// Set via WordPress options or pb_get_site_config()
update_option('pearblog_primary_color', '#your-color');
update_option('pearblog_secondary_color', '#your-color');
update_option('pearblog_accent_color', '#your-color');
update_option('pearblog_logo_url', 'https://your-site.com/logo.png');
update_option('pearblog_logo_dark_url', 'https://your-site.com/logo-dark.png');
```

### Hero Configuration
```php
update_option('pearblog_hero_style', 'video'); // gradient, image, video
update_option('pearblog_hero_title', 'Your Title');
update_option('pearblog_hero_subtitle', 'Your subtitle');
update_option('pearblog_hero_video', 'https://your-site.com/hero.mp4');
update_option('pearblog_hero_cta_text', 'Get Started');
update_option('pearblog_hero_cta_url', '/signup');
```

### Monetization Settings
```php
// AdSense
update_option('pearblog_adsense_client', 'ca-pub-XXXXXXXXXXXXXXXX');
update_option('pearblog_adsense_slot_content', 'XXXXXXXXXX');

// Auto injection
update_option('pearblog_auto_ad_injection', true);
update_option('pearblog_ad_injection_paragraphs', 3); // Every 3 paragraphs

// Affiliate automation
update_option('pearblog_affiliate_rules', array(
    array(
        'domain' => 'amazon.com',
        'affiliate_tag' => 'tag=your-tag-20'
    )
));
```

### Feature Toggles
```php
update_option('pearblog_dark_mode_enabled', true);
update_option('pearblog_toc_enabled', true);
update_option('pearblog_sticky_mobile_cta', true);
update_option('pearblog_smart_cta_enabled', false);
update_option('pearblog_ai_summaries', false);
update_option('pearblog_ai_recommendations', false);
```

## 🎨 Design System

### CSS Variables
```css
:root {
  /* Colors */
  --pb-primary: #2563eb;
  --pb-secondary: #7c3aed;
  --pb-accent: #f59e0b;
  
  /* Spacing */
  --pb-space-xs to --pb-space-3xl
  
  /* Typography */
  --pb-font-base, --pb-font-heading
  
  /* Effects */
  --pb-radius, --pb-shadow, --pb-transition
}
```

### Utility Classes
```css
/* Over 100 utility classes */
.pb-mt-{size}, .pb-mb-{size}, .pb-p-{size}
.pb-text-{align}, .pb-text-{size}, .pb-text-{color}
.pb-flex, .pb-grid, .pb-justify-{value}, .pb-items-{value}
.pb-bg-{color}, .pb-rounded-{size}, .pb-shadow-{size}
.pb-hover-{effect}, .pb-transition, .pb-fade-in
```

## 📊 Performance Metrics

- **Core Web Vitals Optimized** - LCP, FID, CLS tracking
- **Critical CSS** - Above-the-fold instant rendering
- **Lazy Loading** - All media types supported
- **Minimal JavaScript** - ~15KB gzipped
- **Lighthouse Ready** - 90+ scores achievable

## 🔮 Roadmap

### v2.0 (Current) ✅
- Complete FOS architecture
- Dark mode system
- TOC with progress tracking
- Auto ad injection
- Performance module
- Monetization engine
- Grid/list views
- Video hero support

### v2.1 (Planned)
- Gutenberg blocks integration
- Advanced customizer panel
- More AI-powered features
- A/B testing framework
- Advanced analytics dashboard

### v3.0 (Future)
- Headless architecture (Next.js + API)
- Ultra SEO scaling
- GraphQL API
- Advanced caching layers
- Multi-cloud support

## 📄 License

GNU General Public License v2 or later

## 🤝 Support

For issues, questions, or feature requests, please open an issue in this repository.

---

**Frontend Operating System (FOS) - Built for AI + SEO + SaaS**

*v2 PRO - The enterprise WordPress theme that's not just a theme.*

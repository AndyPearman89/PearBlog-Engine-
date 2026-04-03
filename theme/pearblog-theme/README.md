# PearBlog Frontend Engine v1

**SEO-first WordPress theme with UX optimization, monetization, and multisite branding.**

## Overview

PearBlog Theme is a canonical frontend system designed for AI-powered content sites, SaaS platforms, and SEO-focused blogs. Built with performance, mobile-first design, and Core Web Vitals optimization in mind.

## Features

### ✨ Core Components

- **Hero** - Dynamic header with gradient/image backgrounds, title, and intro
- **Card** - Article listing with thumbnails, excerpts, and CTAs
- **Related Posts** - Internal linking for SEO
- **FAQ** - Accordion-style FAQ with Schema.org markup
- **Ads** - Monetization blocks with AdSense integration

### 🎨 Design System

CSS Variables for easy customization:
- `--pb-primary` - Primary brand color
- `--pb-secondary` - Secondary brand color
- `--pb-bg` - Background color
- `--pb-text` - Text color

### 📱 Mobile First

- Responsive layout optimized for all devices
- Large, readable fonts
- Fast navigation with sticky header
- Touch-friendly UI elements

### 🚀 Performance

- Lazy loading images
- Minimal JavaScript
- Fast rendering
- Core Web Vitals optimized
- Clean, semantic HTML

### 🔍 SEO Layout

Every single post follows an SEO-optimized structure:
1. **H1** - Main title
2. **Intro** - Post excerpt/introduction
3. **TL;DR** - Quick summary
4. **Content Sections** - H2/H3 structured content
5. **FAQ** - Schema.org FAQPage markup
6. **Related Posts** - Internal linking
7. **CTA** - Conversion optimization

### 💰 Monetization

PearBlog includes a comprehensive monetization engine designed for maximum revenue:

- **Auto Ad Injection** - Automatically place ads every X paragraphs (configurable)
- **Sticky Mobile Ads** - Bottom CTA with close button for mobile users
- **Affiliate Automation** - Auto-convert product links to affiliate links
- **CTR Tracking** - Scroll-depth tracking for ad optimization
- **Revenue Analytics** - Per-post revenue tracking
- **Smart CTA Placement** - Intelligent CTA positioning based on content analysis

See [BUSINESS-STRATEGY.md](../../BUSINESS-STRATEGY.md) for complete monetization roadmap and revenue expectations.

### 🌐 Multisite Support

- Dynamic branding per site
- Custom colors per site
- Custom logo per site
- Site-specific configuration

## Directory Structure

```
/theme/pearblog-theme/
├── assets/
│   ├── css/
│   │   └── components.css    # Component styles
│   └── js/
│       └── main.js            # Minimal JS for interactions
├── template-parts/
│   ├── hero.php               # Hero component
│   ├── card.php               # Card component
│   ├── block-related.php      # Related posts
│   ├── block-faq.php          # FAQ accordion
│   └── block-ads.php          # Ad blocks
├── inc/
│   ├── ui.php                 # UI helper functions
│   ├── layout.php             # Layout helpers
│   └── components.php         # Component registration
├── style.css                  # Main stylesheet
├── functions.php              # Theme functions
├── index.php                  # Homepage template
├── single.php                 # Single post template
├── category.php               # Category archive template
├── header.php                 # Header template
└── footer.php                 # Footer template
```

## Installation

1. Upload the `pearblog-theme` folder to `/wp-content/themes/`
2. Activate the theme in WordPress Admin → Appearance → Themes
3. Configure theme options (optional):
   - Set primary/secondary colors
   - Upload logo
   - Configure AdSense (if monetizing)
   - Set hero section content

## Page Types

### Homepage (index.php)
- Hero section
- Cards grid of latest posts
- Pagination
- CTA section

### Single Post (single.php)
- SEO-optimized layout
- Breadcrumbs
- Featured image
- Intro/excerpt
- TL;DR section
- Main content
- FAQ section
- Related posts
- Social sharing
- Comments

### Category Page (category.php)
- Category header
- Category description
- Posts grid
- Pagination
- CTA section

## Customization

### Colors (Multisite)

Use WordPress options to override default colors:

```php
update_option('pearblog_primary_color', '#your-color');
update_option('pearblog_secondary_color', '#your-color');
```

### Logo

```php
update_option('pearblog_logo_url', 'https://your-site.com/logo.png');
```

### Hero Section

```php
update_option('pearblog_hero_title', 'Your Title');
update_option('pearblog_hero_intro', 'Your intro text');
update_option('pearblog_hero_image', 'https://your-site.com/hero.jpg');
```

### AdSense

**Basic Setup:**
```php
update_option('pearblog_ads_enabled', true);
update_option('pearblog_adsense_client', 'ca-pub-XXXXXXXXXXXXXXXX');
update_option('pearblog_adsense_slot_header', 'XXXXXXXXXX');
update_option('pearblog_adsense_slot_content', 'XXXXXXXXXX');
```

**Auto Ad Injection:**
```php
// Enable automatic ad injection every N paragraphs
update_option('pearblog_auto_ad_injection', true);
update_option('pearblog_ad_injection_paragraphs', 3); // Every 3 paragraphs
```

**Sticky Mobile Ads:**
```php
// Enable sticky bottom ad on mobile devices
update_option('pearblog_sticky_mobile_cta', true);
update_option('pearblog_adsense_slot_mobile_sticky', 'XXXXXXXXXX');
```

**Affiliate Link Automation:**
```php
// Automatically convert links to affiliate links
update_option('pearblog_affiliate_rules', array(
    array(
        'domain' => 'amazon.com',
        'affiliate_tag' => 'tag=your-tag-20'
    ),
    array(
        'domain' => 'booking.com',
        'affiliate_tag' => 'aid=XXXXXX'
    )
));
```

**Smart CTA Placement:**
```php
// Enable intelligent CTA placement (60% through content)
update_option('pearblog_smart_cta_enabled', true);
```

**Monetization Best Practices:**

1. **Phase 1 (Months 1-3):** Start with basic AdSense
   - Configure auto ad injection every 3-4 paragraphs
   - Enable sticky mobile ads
   - Focus on content creation

2. **Phase 2 (Months 3-6):** Add affiliate monetization
   - Set up affiliate automation rules
   - Add affiliate CTAs to relevant content
   - Track conversion rates

3. **Phase 3 (Months 6-12):** Optimize for revenue
   - A/B test ad positions
   - Analyze revenue per post
   - Focus on high-converting content

See [BUSINESS-STRATEGY.md](../../BUSINESS-STRATEGY.md) for complete monetization timeline and expected revenue thresholds.

## Component Usage

### Hero Component

```php
pearblog_hero(array(
    'title' => 'Page Title',
    'intro' => 'Introduction text',
    'image' => 'https://example.com/image.jpg',
));
```

### Cards Grid

```php
pearblog_render_cards($posts, array(
    'cta_text' => 'Read More'
));
```

### Related Posts

```php
pearblog_related_posts(array(
    'post_id' => get_the_ID(),
    'limit' => 3,
));
```

### FAQ

```php
pearblog_faq(array(
    'faq_items' => array(
        array(
            'question' => 'Question here?',
            'answer' => 'Answer here.'
        ),
        // More items...
    )
));
```

### CTA

```php
pearblog_render_cta(array(
    'title' => 'Ready to Get Started?',
    'button_text' => 'Learn More',
    'button_url' => home_url('/contact'),
));
```

## Shortcodes

- `[pearblog_hero title="Title" intro="Intro"]`
- `[pearblog_faq]`
- `[pearblog_cta title="Title" button_text="Click Here" button_url="/page"]`

## Post Meta Fields

### TL;DR

```php
update_post_meta($post_id, 'pearblog_tldr', array(
    'Summary point 1',
    'Summary point 2',
    'Summary point 3',
));
```

### FAQ

```php
update_post_meta($post_id, 'pearblog_faq', array(
    array(
        'question' => 'Question 1?',
        'answer' => 'Answer 1'
    ),
    array(
        'question' => 'Question 2?',
        'answer' => 'Answer 2'
    ),
));
```

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers

## Performance Best Practices

1. Use lazy loading for images (automatic)
2. Optimize images before upload
3. Enable caching
4. Use a CDN for assets
5. Minimize plugins

## SEO Best Practices

1. Use descriptive H1 tags
2. Add meta descriptions
3. Structure content with H2/H3
4. Add FAQ sections for rich snippets
5. Internal linking with related posts
6. Use breadcrumbs
7. Add Schema.org markup (automatic)

## Roadmap (v2)

- UI PRO components
- Admin dashboard
- Custom widgets
- Advanced customization panel
- Gutenberg blocks
- Page builder integration

## Support

For issues and feature requests, please visit the GitHub repository.

## License

GNU General Public License v2 or later
http://www.gnu.org/licenses/gpl-2.0.html

---

**Built for AI + SEO + SaaS**

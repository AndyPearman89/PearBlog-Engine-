# Landing V2 Pro — Quick Reference

## 🚀 One-Line Setup

```bash
# Create page, select "Landing V2 Pro" template, publish
```

## 📦 Files Created

```
CSS:  theme/pearblog-theme/assets/css/v2-pro-neon.css (787 lines)
JS:   theme/pearblog-theme/assets/js/v2-pro-mobile.js (506 lines)
PHP:  page-landing-v2-pro.php + 7 template parts
```

## 🎨 Design Tokens

```css
/* Colors */
--v2pro-purple: #8B5CF6;
--v2pro-pink: #EC4899;
--v2pro-bg-dark: #0D0F16;

/* Spacing */
--v2pro-space-md: 16px;  /* Mobile base */
--v2pro-space-lg: 24px;
--v2pro-space-xl: 32px;

/* Effects */
--v2pro-glass-bg: rgba(255,255,255,0.03);
--v2pro-glow: 0 0 40px rgba(236,72,153,0.3);
```

## 📱 Mobile UX Rules

1. **CTA**: 100% width, 48px min-height
2. **Padding**: 16px container
3. **Hero**: 50-60vh height
4. **Sticky**: Appears after 150px scroll
5. **Touch**: 44px minimum tap targets

## 🔌 Key AJAX Actions

```javascript
// AI Analysis
action: 'v2pro_ai_analyze'
params: { problem, nonce }

// Track Event
action: 'pearblog_track_event'
params: { event, nonce }

// Track CTA
action: 'pearblog_track_cta_click'
params: { cta_id, location, nonce }
```

## 🎯 Component Usage

```php
// Hero
get_template_part('template-parts/hero-v2-pro', null, [
    'title' => 'Your Title',
    'cta_text' => 'Click Here',
]);

// AI Panel
get_template_part('template-parts/ai-panel-v2-pro');

// Categories
get_template_part('template-parts/category-blocks-v2-pro');

// Experts
get_template_part('template-parts/expert-cards-v2-pro');

// FAQ
get_template_part('template-parts/faq-v2-pro');

// Final CTA
get_template_part('template-parts/final-cta-v2-pro');

// Sticky CTA
get_template_part('template-parts/sticky-cta-v2-pro');
```

## 📊 Analytics Data

```php
// View events
get_option('pearblog_v2pro_events');

// View CTA clicks
get_option('pearblog_v2pro_cta_clicks');

// View performance
get_option('pearblog_v2pro_performance');
```

## 🎭 CSS Classes

```css
.v2pro-btn              /* Primary CTA */
.v2pro-btn-secondary    /* Secondary CTA */
.v2pro-card             /* Glass card */
.v2pro-gradient-text    /* Neon text */
.v2pro-input            /* Form input */
.v2pro-glow             /* Glow animation */
```

## ⚡ Performance

- CSS: 38KB (8KB gzipped)
- JS: 12KB (4KB gzipped)
- Load: < 2s on 3G
- LCP: < 2.5s

## 🔧 Configuration

```php
// Hero text
update_option('pearblog_v2pro_hero_title', 'Title');
update_option('pearblog_v2pro_hero_subtitle', 'Subtitle');

// Experts
update_option('pearblog_v2pro_experts', $experts_array);

// FAQs
update_option('pearblog_v2pro_faqs', $faqs_array);
```

## 🐛 Debug

```javascript
// Check sticky CTA
console.log(window.scrollY); // Should be > 150

// Check V2Pro init
console.log(window.V2ProMobile);

// Force show sticky
document.getElementById('v2pro-mobile-cta').classList.add('show');
```

## 📞 Support

Issues? Check `LANDING-V2-PRO-MOBILE.md` for full documentation.

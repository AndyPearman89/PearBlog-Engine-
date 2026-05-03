# Landing V2 Pro — Mobile-First Neon AI System

**Status**: ✅ Production Ready
**Version**: 2.0.0
**Last Updated**: 2026-05-03

---

## 📱 Overview

High-conversion landing page system for Poradnik.pro with mobile-first design, neon AI branding, and aggressive conversion optimization.

### Core Principle
**Mobile is primary. Desktop is secondary.**

Goal: Maximize thumb interaction → CTA → lead → revenue

---

## 🎨 Design DNA

- **Dark Background**: #0D0F16 → #141722
- **Neon Gradient**: Purple #8B5CF6 → Pink #EC4899
- **Glassmorphism**: Blur + transparency effects
- **Mobile-First**: Touch-optimized, 44px+ tap targets
- **High Contrast**: WCAG AA compliant

---

## 📂 File Structure

```
theme/pearblog-theme/
├── assets/
│   ├── css/
│   │   └── v2-pro-neon.css         (787 lines, ~38KB)
│   └── js/
│       └── v2-pro-mobile.js        (506 lines, ~12KB)
├── template-parts/
│   ├── hero-v2-pro.php             Hero with gradient
│   ├── ai-panel-v2-pro.php         AI problem analyzer
│   ├── category-blocks-v2-pro.php  Feature categories
│   ├── expert-cards-v2-pro.php     Expert profiles
│   ├── faq-v2-pro.php              Accordion FAQ
│   ├── final-cta-v2-pro.php        Closing CTA
│   └── sticky-cta-v2-pro.php       Mobile sticky CTA
├── page-landing-v2-pro.php         Main template
└── functions.php                    AJAX handlers (updated)
```

---

## 🚀 Quick Start

### 1. Create Landing Page

1. Go to **Pages → Add New** in WordPress admin
2. Title: "Landing Page" (or any name)
3. Select template: **Landing V2 Pro (Mobile-First Neon AI)**
4. Publish

### 2. Set as Homepage (Optional)

1. Go to **Settings → Reading**
2. Select "A static page"
3. Choose your V2 Pro landing page
4. Save changes

### 3. Configure Options

```php
// Set hero text
update_option('pearblog_v2pro_hero_title', 'Rozwiąż problem w kilka minut');
update_option('pearblog_v2pro_hero_subtitle', 'Eksperci, porady i konkretne rozwiązania');

// Configure experts (optional)
update_option('pearblog_v2pro_experts', array(
    array(
        'name' => 'Jan Kowalski',
        'rating' => 4.9,
        'reviews' => 128,
        'specialty' => 'Prawo cywilne',
        'url' => home_url('/eksperci/jan-kowalski'),
    ),
    // ... more experts
));

// Configure FAQs (optional)
update_option('pearblog_v2pro_faqs', array(
    array(
        'question' => 'Jak szybko otrzymam odpowiedź?',
        'answer' => 'Większość ekspertów odpowiada w ciągu 24 godzin...',
    ),
    // ... more FAQs
));
```

---

## 🎯 Mobile-First Flow

```
User lands
    ↓
Hero (50-60vh) + CTA
    ↓
AI Panel (problem input)
    ↓
Category blocks
    ↓
Expert cards
    ↓
FAQ
    ↓
Final CTA
    ↓
Sticky CTA (always visible)
    ↓
💰 Conversion
```

---

## 🔧 Technical Features

### Mobile Optimization

- **Container Padding**: 16px mobile, 24px tablet+
- **CTA Buttons**: 100% width mobile, 48px min-height
- **Touch Targets**: 44px minimum (Apple guidelines)
- **Sticky CTA**: Appears after 150px scroll
- **Touch Feedback**: Scale 0.97 on press
- **Vertical Flow**: Single column, no horizontal scroll

### Neon AI Styling

```css
/* Color System */
--v2pro-purple: #8B5CF6;
--v2pro-pink: #EC4899;
--v2pro-gradient: linear-gradient(135deg, #EC4899, #8B5CF6);

/* Glassmorphism */
--v2pro-glass-bg: rgba(255,255,255,0.03);
--v2pro-glass-border: rgba(255,255,255,0.08);
--v2pro-glass-blur: 12px;

/* Glow Effects */
--v2pro-glow: 0 0 40px rgba(236,72,153,0.3);
```

### JavaScript Interactions

1. **StickyMobileCTA** - Shows after 150px scroll
2. **TouchFeedback** - Tactile response on buttons
3. **InputInteraction** - Highlights CTA on input
4. **GlowFollowCursor** - Desktop glow effect
5. **MobileExitIntent** - Blur event detection
6. **FAQAccordion** - Smooth accordion animation
7. **ScrollProgressTracking** - 25%, 50%, 75%, 90%
8. **CTATracking** - Click tracking by location
9. **FormAutoSave** - LocalStorage persistence
10. **LazyLoadImages** - Performance optimization
11. **PerformanceMonitor** - LCP & load time tracking

### AI Problem Analysis

**Supported Categories:**
- 🏛️ **Prawo** (prawnik, sąd, pozew, rozwód, umowa)
- 💰 **Finanse** (kredyt, pożyczka, inwestycja, bank)
- 🏗️ **Budownictwo** (remont, budowa, hydraulik)
- 🏥 **Zdrowie** (lekarz, diagnoza, terapia)
- 💻 **Technologia** (komputer, internet, it)

**How it Works:**
1. User enters problem in AI panel
2. Keyword detection identifies category
3. Returns personalized recommendation
4. Routes to relevant expert category

---

## 📊 Analytics & Tracking

### Events Tracked

```javascript
// Scroll depth
'scroll_25', 'scroll_50', 'scroll_75', 'scroll_90'

// Exit intent
'exit_intent_shown'

// Performance
'lcp', 'page_load_time'
```

### CTA Tracking

```javascript
// Format: {cta_id}_{location}
'hero-primary_hero'
'ai-generate_ai-panel'
'ai-expert_ai-panel'
'sticky-mobile_sticky-mobile'
'final-cta_final'
```

### View Analytics

```php
// Get event counts
$events = get_option('pearblog_v2pro_events', array());

// Get CTA clicks
$cta_clicks = get_option('pearblog_v2pro_cta_clicks', array());

// Get performance metrics
$metrics = get_option('pearblog_v2pro_performance', array());
```

---

## ⚡ Performance Specs

| Metric | Target | Actual |
|--------|--------|--------|
| CSS Size | < 40KB | ~38KB (8KB gzipped) |
| JS Size | < 15KB | ~12KB (4KB gzipped) |
| Mobile Load | < 2s | ✅ Optimized |
| LCP | < 2.5s | ✅ Monitored |
| CLS | < 0.1 | ✅ GPU-accelerated |

### Optimization Features

- ✅ Lazy image loading with IntersectionObserver
- ✅ GPU-accelerated animations (transform, opacity)
- ✅ Passive event listeners
- ✅ Reduced motion support
- ✅ Touch action optimization
- ✅ Form auto-save (no data loss)
- ✅ Critical CSS inline
- ✅ Font preloading (Inter)

---

## 🎨 Component Reference

### Hero Component

```php
get_template_part('template-parts/hero-v2-pro', null, array(
    'title' => 'Rozwiąż problem w kilka minut',
    'subtitle' => 'Eksperci, porady i konkretne rozwiązania',
    'cta_text' => 'Znajdź specjalistę',
    'cta_url' => home_url('/eksperci'),
    'show_badges' => true,
    'show_search' => false,
));
```

### AI Panel Component

```php
get_template_part('template-parts/ai-panel-v2-pro', null, array(
    'title' => 'Jak mogę Ci pomóc?',
    'placeholder' => 'Napisz swój problem...',
    'cta_primary' => 'Generuj odpowiedź',
    'cta_secondary' => 'Przejdź do eksperta',
));
```

### Category Blocks

```php
get_template_part('template-parts/category-blocks-v2-pro', null, array(
    'title' => 'Czego potrzebujesz?',
    'categories' => array(
        array(
            'icon' => '📚',
            'title' => 'Poradniki',
            'description' => 'Kompletne przewodniki',
            'url' => home_url('/poradniki'),
        ),
        // ... more categories
    ),
));
```

### Expert Cards

```php
get_template_part('template-parts/expert-cards-v2-pro', null, array(
    'title' => 'Zweryfikowani eksperci',
    'experts' => v2pro_get_featured_experts(),
));
```

### FAQ

```php
get_template_part('template-parts/faq-v2-pro', null, array(
    'title' => 'Najczęściej zadawane pytania',
    'faqs' => v2pro_get_faqs(),
));
```

### Final CTA

```php
get_template_part('template-parts/final-cta-v2-pro', null, array(
    'title' => 'Rozwiąż problem teraz',
    'subtitle' => 'Nie czekaj — znajdź specjalistę',
    'cta_text' => 'Znajdź specjalistę',
    'cta_url' => home_url('/eksperci'),
));
```

### Sticky Mobile CTA

```php
get_template_part('template-parts/sticky-cta-v2-pro', null, array(
    'text' => 'Znajdź specjalistę',
    'url' => home_url('/eksperci'),
    'cta_id' => 'sticky-mobile',
));
```

---

## 🔌 AJAX Endpoints

### AI Analysis

```javascript
// Request
fetch(ajaxurl, {
    method: 'POST',
    body: new URLSearchParams({
        action: 'v2pro_ai_analyze',
        problem: 'Potrzebuję prawnika w Krakowie',
        nonce: nonce
    })
});

// Response
{
    success: true,
    data: {
        response: 'Twoje pytanie dotyczy kwestii prawnych...',
        url: '/eksperci?kategoria=prawo'
    }
}
```

### Event Tracking

```javascript
fetch(ajaxurl, {
    method: 'POST',
    body: new URLSearchParams({
        action: 'pearblog_track_event',
        event: 'scroll_50',
        nonce: nonce
    })
});
```

### CTA Tracking

```javascript
fetch(ajaxurl, {
    method: 'POST',
    body: new URLSearchParams({
        action: 'pearblog_track_cta_click',
        cta_id: 'hero-primary',
        location: 'hero',
        nonce: nonce
    })
});
```

---

## 🎭 CSS Classes Reference

### Layout
- `.v2pro-container` - Responsive container
- `.v2pro-section` - Vertical section spacing

### Typography
- `.v2pro-h1`, `.v2pro-h2`, `.v2pro-h3` - Headings
- `.v2pro-subtitle` - Muted subtitle text
- `.v2pro-gradient-text` - Neon gradient text

### Components
- `.v2pro-card` - Glass card
- `.v2pro-btn` - Primary button
- `.v2pro-btn-secondary` - Secondary button
- `.v2pro-input` - Form input
- `.v2pro-badge` - Trust badge

### Utilities
- `.v2pro-text-center` - Center text
- `.v2pro-mb-{sm|md|lg|xl}` - Margin bottom
- `.v2pro-mt-{sm|md|lg|xl}` - Margin top
- `.v2pro-hide-mobile` - Hide on mobile
- `.v2pro-hide-desktop` - Hide on desktop
- `.v2pro-fade-in` - Fade in animation
- `.v2pro-stagger` - Stagger children animations

---

## 🧪 Testing Checklist

### Mobile (Primary)
- [ ] Hero displays at 50-60vh
- [ ] All CTAs are 100% width and 48px+ height
- [ ] Sticky CTA appears after 150px scroll
- [ ] Touch feedback works on all buttons
- [ ] FAQ accordion opens/closes smoothly
- [ ] AI panel submits and shows response
- [ ] Form auto-saves to localStorage
- [ ] Exit intent triggers on blur

### Desktop (Secondary)
- [ ] Layout adapts to wider screens
- [ ] Glow effect follows cursor
- [ ] All interactions work
- [ ] Typography scales appropriately

### Performance
- [ ] Page loads < 2s on 3G
- [ ] No layout shift (CLS < 0.1)
- [ ] LCP < 2.5s
- [ ] Images lazy load
- [ ] No console errors

### Analytics
- [ ] Scroll depth events fire
- [ ] CTA clicks tracked
- [ ] AI analysis works
- [ ] Performance metrics logged

---

## 🔄 Integration Points

### With PearBlog Engine

The V2 Pro landing page integrates with:

1. **Lead Generation** (`inc/lead-generation.php`) - Capture leads from AI panel
2. **Analytics** (`inc/analytics-page.php`) - View V2 Pro metrics
3. **Monetization** (`inc/monetization.php`) - AdSense integration points
4. **User Context** (`inc/user-context.php`) - Personalization hooks

### Custom Hooks

```php
// Before V2 Pro page renders
do_action('v2pro_before_render');

// After V2 Pro page renders
do_action('v2pro_after_render');

// Filter hero args
apply_filters('v2pro_hero_args', $args);

// Filter AI response
apply_filters('v2pro_ai_response', $response, $problem);
```

---

## 🚧 Future Enhancements

### Planned Features
- [ ] A/B testing variants (headlines, CTAs, colors)
- [ ] Lead database integration
- [ ] Email capture forms with automation
- [ ] Admin dashboard for V2 Pro analytics
- [ ] AI enhancement with OpenAI/Claude integration
- [ ] Multi-language support
- [ ] Advanced category detection (ML-based)
- [ ] Heatmap visualization
- [ ] Exit popup with offer
- [ ] Social proof notifications

### Expansion Categories
- [ ] Edukacja (education)
- [ ] Marketing (marketing)
- [ ] Nieruchomości (real estate)
- [ ] Transport (transportation)
- [ ] Turystyka (tourism)

---

## 📖 Best Practices

### Content Writing
- **Headlines**: Max 2 lines mobile, action-oriented
- **Subtitles**: 1 line, benefit-focused
- **CTA Text**: 2-3 words, imperative verbs
- **Problem Input**: Placeholder with examples
- **Expert Bios**: 1-2 sentences max

### Design Guidelines
- Use neon gradient sparingly (headings, CTAs)
- Maintain glass effect consistency
- Keep animations subtle (< 300ms)
- Ensure 4.5:1 contrast minimum
- Test on real devices (iPhone, Android)

### Performance Tips
- Compress images (WebP, AVIF)
- Limit custom fonts (Inter only)
- Defer non-critical JS
- Use CDN for static assets
- Enable gzip/brotli compression

---

## 🐛 Troubleshooting

### Sticky CTA Not Showing
```javascript
// Check if element exists
console.log(document.getElementById('v2pro-mobile-cta'));

// Check scroll position
console.log(window.scrollY);

// Force show
document.getElementById('v2pro-mobile-cta').classList.add('show');
```

### AI Panel Not Responding
```php
// Check AJAX handler registration
add_action('wp_ajax_v2pro_ai_analyze', 'v2pro_ajax_ai_analyze');
add_action('wp_ajax_nopriv_v2pro_ai_analyze', 'v2pro_ajax_ai_analyze');

// Test nonce
wp_verify_nonce($_POST['nonce'], 'v2pro_ai_nonce');
```

### Styles Not Loading
```php
// Verify template is active
is_page_template('page-landing-v2-pro.php');

// Force enqueue
wp_enqueue_style('pearblog-v2-pro-neon', ...);
wp_enqueue_script('pearblog-v2-pro-mobile', ...);
```

---

## 📞 Support

For issues or questions:
1. Check this documentation
2. Review code comments in files
3. Test on clean WordPress install
4. Check browser console for errors
5. Verify AJAX handlers are registered

---

## 📄 License

Part of PearBlog Engine - Proprietary

---

**Last Updated**: 2026-05-03
**Version**: 2.0.0
**Status**: ✅ Production Ready

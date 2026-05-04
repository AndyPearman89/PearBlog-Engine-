# PT24.PRO – HOMEPAGE V3 (PRODUCTION COPY)

**Complete implementation guide for the high-conversion PT24.PRO homepage**

---

## 📋 TABLE OF CONTENTS

1. [Overview](#overview)
2. [Key Features](#key-features)
3. [File Structure](#file-structure)
4. [Installation](#installation)
5. [Usage](#usage)
6. [Content Sections](#content-sections)
7. [Lead Generation Flow](#lead-generation-flow)
8. [Customization](#customization)
9. [Analytics & Tracking](#analytics--tracking)
10. [SEO Optimization](#seo-optimization)

---

## 🎯 OVERVIEW

The PT24.PRO Homepage V3 is a **production-ready, high-conversion landing page** designed specifically for local services marketplaces. It focuses on:

- **Lead generation** through multiple entry points
- **Trust building** with social proof and testimonials
- **SEO optimization** with extensive internal linking
- **Mobile-first design** with responsive layouts
- **Conversion tracking** with built-in analytics

### Design Philosophy

**"Every element drives action"**

- Hero section → Search or send inquiry
- Categories → Direct service navigation
- How It Works → Process clarity
- Rankings → Trust and discovery
- Social Proof → FOMO and urgency
- Pricing → Intent qualification
- Guides → SEO and education
- Final CTA → Last chance conversion

---

## ✨ KEY FEATURES

### 🔍 Smart Search System
- Dual-input search (service + city)
- Auto-redirect to service/city landing pages
- Enter key support
- Polish character normalization

### 📝 Lead Capture Form
- 5-field quick quote form
- AJAX submission (no page reload)
- Real-time validation
- Success/error handling
- Email notifications (admin + user)
- Database storage

### 🎨 Visual Elements
- **8 service categories** with emoji icons
- **Trust bar** (4.8/5 rating, 25K+ reviews, 12K+ firms)
- **3 trust badges** (verified, fast response, no commitment)
- **Dynamic social proof ticker** with rotating messages
- **Pricing cards** for 6 common services
- **SEO footer** with 5 link columns

### 📊 Analytics & Tracking
- Page view tracking
- Scroll depth tracking (25%, 50%, 75%, 100%)
- Time on page tracking (30s, 60s, 120s)
- Lead submission tracking
- Custom event tracking
- Google Analytics integration ready

---

## 📁 FILE STRUCTURE

```
theme/pearblog-theme/
├── page-pt24-home-v3.php              # Main template file
├── assets/
│   ├── css/
│   │   └── pt24-home-v3.css           # V3 styles (responsive)
│   └── js/
│       └── pt24-home-v3.js            # V3 functionality
└── inc/
    └── pt24-form-handler.php          # Lead form + event tracking
```

### File Sizes
- **page-pt24-home-v3.php**: ~18 KB
- **pt24-home-v3.css**: ~12 KB
- **pt24-home-v3.js**: ~8 KB
- **Total**: ~38 KB (minified: ~22 KB)

---

## 🚀 INSTALLATION

### Step 1: Upload Files

Files are already in the theme directory:
```bash
theme/pearblog-theme/page-pt24-home-v3.php
theme/pearblog-theme/assets/css/pt24-home-v3.css
theme/pearblog-theme/assets/js/pt24-home-v3.js
```

### Step 2: Create WordPress Page

```bash
# Option A: Via WP-CLI
wp post create \
  --post_type=page \
  --post_title="PT24.PRO - Homepage V3" \
  --post_status=publish \
  --page_template=page-pt24-home-v3.php

# Option B: Via WordPress Admin
# 1. Go to Pages → Add New
# 2. Title: "PT24.PRO - Homepage V3"
# 3. Template: "PT24.PRO - Homepage V3 (Production)"
# 4. Publish
```

### Step 3: Set as Homepage (Optional)

```bash
# Via WP-CLI
wp option update show_on_front page
wp option update page_on_front <PAGE_ID>

# Or via WordPress Admin:
# Settings → Reading → "A static page" → Select your page
```

### Step 4: Verify Installation

1. Visit your homepage
2. Check browser console for JavaScript errors
3. Test search functionality
4. Submit test lead form
5. Verify responsive design on mobile

---

## 💻 USAGE

### Accessing the V3 Homepage

**Direct URL**: `https://yoursite.com/pt24-pro-homepage-v3/`

**Set as homepage**: Settings → Reading → Static page

### Testing Lead Form

```javascript
// Test form submission in browser console
document.getElementById('pt24-quote-form').dispatchEvent(new Event('submit'));
```

### Checking Analytics

```bash
# View today's tracked events
wp transient get pt24_events_$(date +%Y-%m-%d)

# View recent leads
wp db query "SELECT * FROM wp_pt24_leads ORDER BY created_at DESC LIMIT 10"
```

---

## 📐 CONTENT SECTIONS

### 1. HERO (Above the Fold)

**Headline**: "Znajdź sprawdzonego fachowca w swojej okolicy"

**Sub-headline**: "Porównaj opinie, ceny i otrzymaj wycenę nawet w 15 minut."

**Elements**:
- Dual search bar (service + city)
- Primary CTA: "Znajdź fachowca"
- Secondary CTA: "Wyślij zapytanie" (scrolls to form)
- Trust bar: ⭐ 4.8/5 | 25,000+ opinii | 12,000+ firm
- Trust badges: ✔ sprawdzone firmy | szybka odpowiedź | bez zobowiązań

**Purpose**: Immediate action + trust building

---

### 2. SZYBKA WYCENA (Lead Hook)

**Headline**: "Otrzymaj wycenę w 15 minut"

**Description**: "Opisz swój problem, a lokalni fachowcy prześlą Ci oferty."

**Form Fields**:
1. Service (dropdown): 8 options
2. City (text input)
3. Description (textarea)
4. Name (text input)
5. Phone (tel input)

**CTA**: "Wyślij zapytanie"

**Purpose**: Primary lead generation point

---

### 3. KATEGORIE (Entry Point)

**Headline**: "Popularne usługi"

**Services** (8 cards with emoji icons):
- 🔧 Mechanik
- ⚡ Elektryk
- 🚰 Hydraulik
- 🏗️ Remonty
- ❄️ Klimatyzacja
- 🔥 Ogrzewanie
- 🧹 Sprzątanie
- 🌳 Ogrodnik

**CTA**: "Zobacz wszystkie usługi"

**Purpose**: Service discovery + SEO entry points

---

### 4. JAK TO DZIAŁA (Prostota)

**3-step process**:

1. **Opisz problem**
   - "Dodaj krótkie zapytanie – zajmie to mniej niż minutę"

2. **Otrzymaj oferty**
   - "Fachowcy z Twojej okolicy skontaktują się z Tobą"

3. **Wybierz najlepszą opcję**
   - "Porównaj ceny i opinie – decyzja należy do Ciebie"

**CTA**: "Wyślij zapytanie"

**Purpose**: Process clarity + objection handling

---

### 5. RANKINGI (SEO + Trust)

**Headline**: "Najlepsi fachowcy w Twoim mieście"

**Rankings** (6 links with trophy icons):
- 🏆 Mechanik Katowice – ranking
- 🏆 Elektryk Kraków – ranking
- 🏆 Hydraulik Warszawa – ranking
- 🏆 Remonty Wrocław – ranking
- 🏆 Klimatyzacja Poznań – ranking
- 🏆 Ogrzewanie Gdańsk – ranking

**CTA**: "Zobacz ranking"

**Purpose**: Trust building + SEO link juice

---

### 6. SOCIAL PROOF (Dynamic)

**Rotating messages**:
- "Klient z Katowic otrzymał 3 oferty w 12 minut"
- "Nowe zapytanie: hydraulik Kraków – wysłano do 4 firm"
- "Klient z Warszawy wybrał ofertę – naprawiona w 2 godziny"
- "Mechanik z Wrocławia odebrał lead – klient umówiony na jutro"
- "Zapytanie: remont łazienki Gdańsk – 5 firm zainteresowanych"
- "Elektryk z Poznania odpowiedział w 8 minut"

**Animation**: Horizontal ticker with seamless loop

**Purpose**: FOMO + urgency + activity proof

---

### 7. CENY (High Intent)

**Headline**: "Ile kosztują usługi?"

**Pricing cards** (6 services):
1. **Wymiana oleju**: 150–400 zł (+ koszt oleju i filtra)
2. **Hydraulik**: od 100 zł (za godzinę pracy)
3. **Elektryk**: od 120 zł (za godzinę pracy)
4. **Remont łazienki**: 8,000–25,000 zł (w zależności od zakresu)
5. **Klimatyzacja**: 2,500–5,000 zł (montaż + urządzenie)
6. **Pielęgnacja ogrodu**: od 80 zł (za wizytę)

**Note**: "Ceny zależą od zakresu pracy i lokalizacji."

**CTA**: "Sprawdź dokładną wycenę"

**Purpose**: Intent qualification + expectation setting

---

### 8. PORADNIKI (SEO Engine)

**Headline**: "Poradniki i wskazówki"

**Guides** (6 links with book icons):
- 📖 Ile kosztuje remont łazienki?
- 📖 Auto nie odpala – co robić?
- 📖 Jak wybrać dobrego elektryka?
- 📖 Awaria centralnego ogrzewania – jak reagować?
- 📖 Klimatyzacja w domu – czy warto?
- 📖 Jak przygotować ogród na wiosnę?

**CTA**: "Zobacz poradniki"

**Purpose**: SEO content hub + education

---

### 9. FINAL CTA (Closer)

**Headline**: "Masz problem? Znajdziemy fachowca za Ciebie"

**Description**: "Wyślij zapytanie i otrzymaj oferty od sprawdzonych firm."

**CTAs**:
- Primary: "Wyślij zapytanie" (scrolls to form)
- Secondary: "Sprawdź dostępność" (rankings page)

**Purpose**: Last chance conversion

---

### 10. FOOTER SEO (Navigation)

**5 columns**:

1. **Miasta**: Katowice, Warszawa, Kraków, Wrocław, Poznań, Gdańsk
2. **Usługi**: Mechanik, Elektryk, Hydraulik, Remonty, Klimatyzacja, Ogrzewanie
3. **Rankingi**: Najlepsi mechanicy, elektrycy, hydraulicy, firmy remontowe
4. **Poradniki**: Wszystkie poradniki, Mechanika, Elektryka, Hydraulika
5. **Kontakt**: Kontakt, Dla firm, Regulamin, Polityka prywatności

**Purpose**: SEO internal linking + navigation

---

## 🎯 LEAD GENERATION FLOW

### User Journey

```
Landing → Hero Search OR Scroll
    ↓
Categories → Service Selection
    ↓
Quick Quote Form → Lead Submission
    ↓
Success Message → Email Confirmation
    ↓
Business Receives Lead → Sales Trigger
```

### Lead Storage

Leads are stored in `wp_pt24_leads` table:

```sql
CREATE TABLE wp_pt24_leads (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  email varchar(255),
  phone varchar(50) NOT NULL,
  city varchar(100) NOT NULL,
  service varchar(100) NOT NULL,
  message text,
  source varchar(500),
  status varchar(50) DEFAULT 'new',
  created_at datetime NOT NULL,
  PRIMARY KEY (id)
);
```

### Notification Flow

**On Lead Submission**:

1. **Admin Email**:
   - Subject: "Nowy lead: {service} w {city}"
   - Content: Lead details + edit link

2. **User Email**:
   - Subject: "Otrzymaliśmy Twoje zapytanie — PT24.pro"
   - Content: Confirmation + next steps

3. **Business Notification** (via Sales Automation System V3):
   - SMS: "Właśnie wysłaliśmy Ci klienta z {city}"
   - Trigger: Lead routed to business

---

## 🎨 CUSTOMIZATION

### Change Hero Text

Edit `page-pt24-home-v3.php` lines 22-26:

```php
<h1 class="pt24-v3-hero__title">
    Your Custom Headline
</h1>

<p class="pt24-v3-hero__subtitle">
    Your custom subtitle text.
</p>
```

### Modify Service Categories

Edit `page-pt24-home-v3.php` lines 132-202 (categories section):

```php
<a href="/your-service/" class="pt24-v3-category-card">
    <div class="pt24-v3-category-card__icon">🔧</div>
    <h3 class="pt24-v3-category-card__title">Your Service</h3>
    <span class="pt24-v3-category-card__arrow">→</span>
</a>
```

### Update Trust Bar Stats

Edit `page-pt24-home-v3.php` lines 63-71:

```php
<div class="pt24-v3-trust-bar">
    <span class="pt24-v3-trust-bar__item">
        <span class="pt24-v3-trust-bar__icon">⭐</span>
        4.9/5  <!-- Update rating -->
    </span>
    <span class="pt24-v3-trust-bar__divider">|</span>
    <span class="pt24-v3-trust-bar__item">30,000+ opinii</span>  <!-- Update count -->
    <span class="pt24-v3-trust-bar__divider">|</span>
    <span class="pt24-v3-trust-bar__item">15,000+ firm w całej Polsce</span>  <!-- Update count -->
</div>
```

### Customize Colors

Edit `assets/css/pt24-home-v3.css` lines 9-19 (CSS variables):

```css
:root {
    --pt24-primary: #2563eb;        /* Primary blue */
    --pt24-primary-dark: #1d4ed8;   /* Darker blue */
    --pt24-secondary: #10b981;      /* Green */
    --pt24-accent: #f59e0b;         /* Orange */
    /* Modify as needed */
}
```

### Add Social Proof Messages

Edit `assets/js/pt24-home-v3.js` lines 120-127:

```javascript
const messages = [
    '"Your custom message 1"',
    '"Your custom message 2"',
    '"Your custom message 3"',
    // Add more messages...
];
```

---

## 📊 ANALYTICS & TRACKING

### Built-in Event Tracking

The V3 homepage tracks the following events automatically:

1. **page_view**: Homepage loaded
2. **scroll_depth_25/50/75/100**: User scrolled to depth
3. **time_on_page_30s/60s/120s**: User stayed on page
4. **lead_submitted**: Lead form submitted

### View Analytics Data

```bash
# Get today's events
wp transient get pt24_events_$(date +%Y-%m-%d)

# Example output:
# {
#   "page_view": 245,
#   "scroll_depth_50": 178,
#   "lead_submitted": 12
# }
```

### Google Analytics Integration

Add this to your theme's `functions.php`:

```php
add_action('wp_head', function() {
    if (is_page_template('page-pt24-home-v3.php')) {
        ?>
        <!-- Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
        <script>
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());
          gtag('config', 'GA_MEASUREMENT_ID');
        </script>
        <?php
    }
});
```

### Custom Event Tracking

```javascript
// Track custom events in your code
if (typeof pt24Data !== 'undefined') {
    fetch(pt24Data.ajaxurl, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action: 'pt24_track_event',
            event_type: 'custom_event_name',
            nonce: pt24Data.nonce
        })
    });
}
```

---

## 🔍 SEO OPTIMIZATION

### On-Page SEO

**Title Tag**: Set via WordPress page settings
- Recommended: "PT24.PRO - Znajdź Fachowca w Swojej Okolicy | 12,000+ Sprawdzonych Firm"

**Meta Description**: Set via SEO plugin (Yoast, Rank Math, etc.)
- Recommended: "Porównaj opinie, ceny i otrzymaj wycenę nawet w 15 minut. 25,000+ opinii, 12,000+ sprawdzonych firm w całej Polsce. Mechanik, elektryk, hydraulik i więcej."

**H1**: "Znajdź sprawdzonego fachowca w swojej okolicy"

**Internal Links**: 50+ internal links to:
- Service pages (/mechanik/, /elektryk/, etc.)
- City pages (/katowice/, /krakow/, etc.)
- Rankings (/rankingi/, /mechanik/katowice/)
- Guides (/poradniki/)

### Schema.org Markup

Add to `page-pt24-home-v3.php` before `</main>`:

```php
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "PT24.PRO",
  "description": "Platforma łącząca klientów z lokalnymi fachowcami",
  "url": "<?php echo home_url(); ?>",
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.8",
    "reviewCount": "25000"
  },
  "address": {
    "@type": "PostalAddress",
    "addressCountry": "PL"
  }
}
</script>
```

### Sitemap Integration

Ensure V3 homepage is in your XML sitemap:

```bash
# Regenerate sitemap (Yoast SEO)
wp yoast index --reindex

# Or use WP-CLI
wp sitemap-generate
```

---

## 📱 RESPONSIVE DESIGN

### Breakpoints

- **Desktop**: > 768px
- **Tablet**: 481px - 768px
- **Mobile**: ≤ 480px

### Mobile Optimizations

1. **Search bar**: Stacked layout (vertical)
2. **Categories**: 2 columns → 1 column
3. **Steps**: Single column layout
4. **CTAs**: Full-width buttons
5. **Footer**: Single column

### Test Responsiveness

```bash
# Use Chrome DevTools
# 1. Open DevTools (F12)
# 2. Toggle device toolbar (Ctrl+Shift+M)
# 3. Test: iPhone SE, iPad, Desktop
```

---

## 🐛 TROUBLESHOOTING

### Form Not Submitting

**Check**:
1. JavaScript console for errors
2. AJAX URL is correct: `wp_localize_script` in template
3. Nonce is valid
4. `pt24_leads` table exists

**Fix**:
```bash
# Create leads table
wp eval "
global \$wpdb;
\$table = \$wpdb->prefix . 'pt24_leads';
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta(\"CREATE TABLE \$table (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  phone varchar(50) NOT NULL,
  city varchar(100) NOT NULL,
  service varchar(100) NOT NULL,
  message text,
  created_at datetime NOT NULL,
  PRIMARY KEY (id)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\");
"
```

### CSS Not Loading

**Check**:
1. File path: `assets/css/pt24-home-v3.css` exists
2. File permissions (644)
3. Browser cache (hard refresh: Ctrl+Shift+R)

**Fix**:
```bash
# Verify file exists
ls -la theme/pearblog-theme/assets/css/pt24-home-v3.css

# Clear cache
wp cache flush
```

### Search Not Working

**Check**:
1. JavaScript loaded: Check Network tab
2. Slugs normalized correctly
3. Landing pages exist

**Debug**:
```javascript
// Test in console
const service = 'mechanik';
const city = 'katowice';
console.log('URL:', '/' + service + '/' + city + '/');
```

---

## 📈 PERFORMANCE

### Page Speed Metrics

**Target**:
- Load time: < 2 seconds
- First Contentful Paint: < 1 second
- Time to Interactive: < 3 seconds

### Optimization Tips

1. **Minify CSS/JS**:
```bash
# Use WP plugins or build tools
wp plugin install autoptimize --activate
```

2. **Enable Caching**:
```bash
wp plugin install w3-total-cache --activate
```

3. **Image Optimization**:
- Use WebP format for images
- Lazy load images below fold

4. **CDN Integration**:
- Cloudflare, BunnyCDN, or similar

---

## 🔐 SECURITY

### Form Security

- ✅ Nonce verification
- ✅ Input sanitization
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS prevention (esc_* functions)

### Rate Limiting

Add to `functions.php`:

```php
add_action('wp_ajax_pt24_submit_lead', 'pt24_rate_limit_leads', 1);
add_action('wp_ajax_nopriv_pt24_submit_lead', 'pt24_rate_limit_leads', 1);

function pt24_rate_limit_leads() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $transient_key = 'pt24_lead_' . md5($ip);

    if (get_transient($transient_key)) {
        wp_send_json_error(['message' => 'Zbyt wiele zapytań. Spróbuj za chwilę.'], 429);
    }

    set_transient($transient_key, true, 60); // 1 minute
}
```

---

## 🚀 NEXT STEPS

1. **Test thoroughly** on staging environment
2. **A/B test** different headlines and CTAs
3. **Monitor analytics** to identify drop-off points
4. **Optimize conversion** based on data
5. **Scale content** (add more services, cities, guides)

---

## 📞 SUPPORT

For issues or questions:

1. Check this documentation first
2. Review code comments in template files
3. Test in browser console
4. Check WordPress error logs

---

## 📝 CHANGELOG

### Version 3.0.0 (2026-05-04)
- ✅ Initial production release
- ✅ Complete homepage template
- ✅ Responsive CSS styles
- ✅ JavaScript functionality
- ✅ Lead form integration
- ✅ Event tracking system
- ✅ Documentation

---

**Built with ❤️ for PT24.PRO**

**Production-ready. High-conversion. SEO-optimized.**

# PT24 Homepage V5 - Streamlined Conversion Layout

**Version:** 5.0.0
**Date:** 2026-05-04
**Template:** `page-pt24-home-v5.php`
**Stylesheet:** `assets/css/pt24-home-v5.css`

---

## 🎯 Overview

PT24 Homepage V5 is a streamlined, conversion-focused homepage template based on simplified wireframe specifications. This version prioritizes clarity and quick user action with a clean, modern design.

---

## 📋 Page Structure

### 1. **Header**
- Logo (PT24.PRO)
- Navigation menu (Usługi, Miasta, Firmy, Dodaj firmę, Kontakt)
- Phone contact link

### 2. **Hero Section**
- H1: "Znajdź sprawdzonego fachowca w swojej okolicy"
- Descriptive text
- Dual CTAs: "Znajdź usługę" + "Dodaj firmę"
- Purple gradient background

### 3. **Services Grid (5 Categories)**
- Mechanik 🔧
- Hydraulik 🚰
- Elektryk ⚡
- Laweta 🚗
- Wulkanizacja 🛞
- Responsive grid layout
- Hover effects

### 4. **How It Works (3 Steps)**
- Step 1: Wybierz usługę
- Step 2: Wybierz miasto
- Step 3: Zadzwoń do fachowca
- Numbered visual indicators

### 5. **CTA Bar**
- "Masz problem? Znajdź fachowca w 2 minuty"
- Prominent "Zadzwoń teraz" button
- Full-width purple background

### 6. **Popular Cities**
- Links to city pages:
  - Ruda Śląska
  - Katowice
  - Kraków
  - Wrocław
  - Warszawa

### 7. **Why Us (Benefits)**
- 📍 Lokalni fachowcy
- ⚡ Szybki kontakt
- 🤝 Brak pośredników
- 🕒 Dostępność 24/7

### 8. **For Business Section**
- "Dodaj swoją firmę i zdobywaj klientów"
- Features list:
  - ✓ Własny profil
  - ✓ Widoczność w Google
  - ✓ Leady lokalne
- "Dodaj firmę" CTA button
- Purple gradient background

### 9. **Footer**
- Navigation links
- Contact info (Phone, Email)
- Copyright notice
- Dark background

---

## 🎨 Design Features

### Colors
- **Primary Purple**: `#7c3aed` (gradient: `#667eea` → `#764ba2`)
- **White Background**: `#ffffff`
- **Light Gray BG**: `#f9fafb`
- **Dark Text**: `#1f2937`
- **Accent Orange**: `#f59e0b`

### Typography
- System font stack: `-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto`
- Hero H1: 2.5rem (mobile: 2rem)
- Section titles: 2rem
- Body text: 1rem

### Layout
- Max-width: 1200px
- Responsive padding: 1rem (mobile) → 2rem (desktop)
- Sticky header
- Grid-based layouts

---

## 📱 Responsive Design

### Mobile (< 768px)
- Navigation hidden (mobile menu recommended for production)
- 2-column service grid
- Stacked CTA buttons
- Reduced font sizes
- Optimized spacing

### Tablet & Desktop (≥ 768px)
- Full navigation visible
- Multi-column grids
- Horizontal layouts
- Enhanced hover effects

---

## 🚀 Setup Instructions

### 1. Create WordPress Page

```bash
# Via WP-CLI
wp post create \
  --post_type=page \
  --post_title="PT24 Home V5" \
  --post_status=publish \
  --page_template=page-pt24-home-v5.php
```

### 2. Set as Homepage

```bash
# Get page ID
PAGE_ID=$(wp post list --post_type=page --name="pt24-home-v5" --field=ID --format=csv)

# Set as front page
wp option update show_on_front page
wp option update page_on_front $PAGE_ID
```

### 3. Via WordPress Admin

1. Go to **Pages → Add New**
2. Title: "PT24 Home V5"
3. Template: Select **"PT24.PRO - Homepage V5"**
4. Publish page
5. Go to **Settings → Reading**
6. Select "A static page" as homepage
7. Choose "PT24 Home V5" as front page

---

## ⚙️ Customization

### Update Phone Number

Edit `page-pt24-home-v5.php`:

```php
// Line 43 (header) and Line 157 (CTA bar)
<a href="tel:+48123456789" class="pt24-v5-phone">
```

### Update Service Categories

Edit the services grid section (lines 100-128):

```php
<a href="/your-service/" class="pt24-v5-service-card">
    <div class="pt24-v5-service-card__icon">🔧</div>
    <h3 class="pt24-v5-service-card__title">Your Service</h3>
</a>
```

### Update Cities

Edit the cities section (lines 175-181):

```php
<a href="/your-city/" class="pt24-v5-city-link">Your City</a>
```

### Update Colors

Edit `assets/css/pt24-home-v5.css`:

```css
/* Primary gradient */
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

/* Accent color */
background: #f59e0b;
```

---

## 🔄 Differences from V4

### V5 (Current)
- Streamlined, minimal structure
- Focus on immediate action
- Simplified sections
- Clean, modern design
- Header with navigation
- Dual CTA hero
- Benefits section

### V4 (Previous)
- More detailed content
- Search bar in hero
- Trust signals
- 6 service categories
- Extended descriptions
- Minimal header

---

## 📊 Conversion Optimization

### Primary CTAs
1. Hero: "Znajdź usługę" (scroll to services)
2. Hero: "Dodaj firmę" (business signup)
3. CTA Bar: "Zadzwoń teraz" (immediate contact)
4. Business Section: "Dodaj firmę" (business signup)

### User Flow
```
Landing → Service Selection → City Selection → Contact Expert
Landing → "Dodaj firmę" → Business Registration
```

### Trust Signals
- "Lokalni fachowcy" badge
- "24/7 dostępność" assurance
- "Brak pośredników" transparency
- Popular cities display

---

## 🎯 SEO Considerations

### On-Page Elements
- Clear H1: "Znajdź sprawdzonego fachowca w swojej okolicy"
- Structured heading hierarchy (H1 → H2 → H3)
- Semantic HTML5 elements
- Internal linking to service/city pages

### Performance
- Minimal external dependencies
- CSS/JS versioning for cache busting
- Optimized grid layouts
- No heavy images in template

### Local SEO
- City links for local targeting
- Service category structure
- Phone number prominently displayed
- Schema markup ready (can be added)

---

## 🧪 Testing Checklist

- [x] Desktop display (1920px, 1440px, 1024px)
- [x] Tablet display (768px)
- [x] Mobile display (375px, 414px)
- [x] All CTAs functional
- [x] Navigation links working
- [x] Phone links opening dialer
- [x] Email links opening mail client
- [x] Hover states on interactive elements
- [x] Sticky header behavior
- [x] Cross-browser compatibility (Chrome, Firefox, Safari, Edge)

---

## 📝 Notes

- Template uses WordPress template system
- Requires PearBlog theme as parent
- Compatible with all modern browsers
- Mobile-first responsive design
- No external dependencies
- Lightweight and fast-loading

---

## 🔗 Related Files

- Template: `theme/pearblog-theme/page-pt24-home-v5.php`
- Stylesheet: `theme/pearblog-theme/assets/css/pt24-home-v5.css`
- JavaScript: `theme/pearblog-theme/assets/js/pt24-landing.js` (shared)
- Header: `theme/pearblog-theme/header-minimal.php` (if exists)
- Blueprint: `PT24-PRO-PLATFORM-BLUEPRINT.md`

---

**Version:** 5.0.0
**Status:** ✅ Production Ready
**Author:** PearBlog Team

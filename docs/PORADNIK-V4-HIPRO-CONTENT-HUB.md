# Poradnik.pro V4 HI-PRO Content Hub

Complete high-conversion homepage template for Poradnik.pro with 10 optimized sections focused on problem-solving and practical information delivery.

## Overview

The V4 HI-PRO Content Hub is a conversion-optimized homepage design that prioritizes:
- **Problem-solving focus**: Direct answers to common questions
- **Practical information**: No fluff, only actionable content
- **Clear conversion paths**: Multiple CTAs strategically placed
- **SEO optimization**: Comprehensive internal linking structure
- **Trust building**: Authority signals and social proof throughout

## Template Structure

### File Locations

```
theme/pearblog-theme/
├── page-poradnik-v4-hipro.php          # Main template file
├── assets/css/poradnik-v4-hipro.css    # Styles
└── functions.php                        # Asset enqueuing (updated)
```

### 10 Core Sections

#### [1] Hero – Content + Intent
**Purpose**: Immediate value proposition with clear search functionality

**Elements**:
- **H1**: "Sprawdzone poradniki, które rozwiązują realne problemy"
- **Subtitle**: Value proposition explanation
- **Search Bar**: Large, prominent search with example queries
- **Dual CTAs**: Primary (Znajdź poradnik) + Secondary (Przejdź do usług)
- **Trust Signals**: 3 checkmarks (konkretne odpowiedzi, aktualne ceny, sprawdzone rozwiązania)
- **Microcopy**: "Bez lania wody — tylko praktyczne informacje"

**Design**: Purple gradient background, white text, centered layout

#### [2] Quick Answer Hub
**Purpose**: Fast access to most common problems

**Elements**:
- 4 quick-answer links to popular guides
- Examples: "Auto nie odpala", "Ile kosztuje remont łazienki"
- Arrow indicators for clickability
- "Zobacz wszystkie poradniki" CTA

**Design**: Light gray background, list-style layout with hover effects

#### [3] Category Grid (Topical Authority)
**Purpose**: Establish topical authority across main content pillars

**Elements**:
- 6 main categories with emoji icons:
  - 🚗 Motoryzacja
  - 🏠 Dom i remont
  - ⚡ Instalacje elektryczne
  - 🚿 Hydraulika
  - ❄️ Klimatyzacja i ogrzewanie
  - 🧹 Utrzymanie domu
- "Przeglądaj kategorie" CTA

**Design**: Responsive grid, cards with hover lift effect

#### [4] Featured Articles (SEO Driver)
**Purpose**: Showcase best content and drive organic traffic

**Elements**:
- 4 featured articles (dynamic or fallback)
- Thumbnail images (if available)
- Excerpt text
- "Czytaj więcej" links
- "Czytaj więcej" section CTA

**Design**: Card grid with images, light background

#### [5] Cost Hub (High Intent)
**Purpose**: Capture high-intent users researching costs

**Elements**:
- 3 pricing examples:
  - Remont łazienki: 10 000–40 000 zł
  - Wymiana oleju: 150–400 zł
  - Hydraulik: od 100 zł
- Description: "Sprawdź realne ceny i porównaj oferty"
- "Sprawdź ceny w Twoim mieście" CTA

**Design**: Purple gradient background (matches hero), glassmorphism cards

#### [6] Problem → Solution → Lead
**Purpose**: Convert problem-aware visitors into leads

**Elements**:
- Title: "Masz konkretny problem?"
- Description: Solution promise
- Dual CTAs:
  - "Znajdź rozwiązanie" (primary)
  - "Wyślij zapytanie" (secondary)

**Design**: Clean, centered, white background

#### [7] Internal Linking (to PT24)
**Purpose**: Cross-site traffic and SEO link juice distribution

**Elements**:
- 6 local service links (e.g., "Mechanik Katowice")
- Connects to PT24.pro local services
- "Zobacz ranking" CTA

**Design**: Grid layout, light background, hover effects

#### [8] Trust Block (Authority)
**Purpose**: Build credibility and authority

**Elements**:
- 4 trust pillars:
  - Praktyczne poradniki
  - Aktualne ceny
  - Realne rozwiązania
  - Połączenie z lokalnymi usługami
- Check icons for each pillar
- Descriptive text under each

**Design**: 4-column grid (responsive), centered icons

#### [9] Final CTA (Closer)
**Purpose**: Strong conversion driver before footer

**Elements**:
- Title: "Nie tylko poradniki — realna pomoc"
- Description: Complete value proposition
- Dual large CTAs:
  - "Znajdź rozwiązanie"
  - "Sprawdź dostępność fachowców"

**Design**: Purple gradient (matches hero), large buttons

#### [10] Footer (SEO Engine)
**Purpose**: Comprehensive SEO link structure

**Elements**:
- 5 footer columns:
  - Poradniki (category links)
  - Kategorie (topic links)
  - Rankingi (ranking links)
  - Miasta (location links)
  - Kontakt (utility links)
- Copyright notice

**Design**: Dark background, multi-column responsive layout

## Usage

### Creating a Page with This Template

1. **In WordPress Admin**:
   - Create a new page or edit existing page
   - From the "Template" dropdown, select **"Poradnik.pro V4 HI-PRO Content Hub"**
   - Publish the page

2. **Set as Homepage** (optional):
   - Go to Settings → Reading
   - Select "A static page" for homepage
   - Choose your V4 HI-PRO page as homepage

### Customization Options

#### Update Hero Text
Edit `page-poradnik-v4-hipro.php` lines 18-21:
```php
<h1 class="hipro-hero__title">
    Your custom title here
</h1>
```

#### Modify Quick Answers
Edit the `$quick_answers` array (lines 75-80):
```php
$quick_answers = [
    ['title' => 'Your question', 'url' => '/your-url/'],
    // Add more...
];
```

#### Change Categories
Edit the `$categories` array (lines 116-123):
```php
$categories = [
    ['icon' => '🚗', 'title' => 'Your category', 'url' => '/url/'],
    // Add more...
];
```

#### Adjust Cost Examples
Edit the cost items in section 5 (lines 208-222).

#### Customize Footer Links
Edit the footer link sections (lines 323-375).

## CSS Customization

### Color Scheme

The design uses CSS variables for easy customization. Edit `poradnik-v4-hipro.css`:

```css
:root {
    --hipro-primary: #2563eb;      /* Primary blue */
    --hipro-secondary: #64748b;    /* Secondary gray */
    --hipro-accent: #f59e0b;       /* Accent orange */
    --hipro-success: #10b981;      /* Success green */
    --hipro-text: #1e293b;         /* Text color */
}
```

### Gradient Backgrounds

Hero and Final CTA use gradient:
```css
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
```

Change colors to match your brand.

### Spacing

Adjust section spacing:
```css
:root {
    --hipro-spacing: 2rem;      /* Standard spacing */
    --hipro-spacing-lg: 4rem;   /* Large spacing */
}
```

## Dynamic Content Integration

### Featured Articles

The template automatically loads posts from WordPress:

```php
$featured_posts = get_posts([
    'posts_per_page' => 4,
    'post_status' => 'publish',
    'orderby' => 'meta_value_num',
    'meta_key' => 'post_views_count',
    'order' => 'DESC'
]);
```

If no posts exist, it falls back to hardcoded examples.

### Extending with WordPress Options

You can make more elements dynamic using WordPress options:

```php
// Example: Make hero title editable from admin
$hero_title = get_option('poradnik_v4_hipro_hero_title', 'Default title');
```

Then create an admin settings page to edit these options.

## Performance Optimization

### Conditional CSS Loading

CSS only loads when template is active:
```php
if (is_page_template('page-poradnik-v4-hipro.php')) {
    wp_enqueue_style('poradnik-v4-hipro', ...);
}
```

### Image Optimization

If adding featured images:
- Use WebP format where possible
- Lazy load images below the fold
- Use responsive image sizes

### Critical CSS (Recommended)

Extract above-the-fold CSS and inline it for faster First Contentful Paint.

## SEO Considerations

### Internal Linking Strategy

The template creates strong internal link structure:
- Category links in section 3
- Featured article links in section 4
- Local service links in section 7
- Comprehensive footer in section 10

### Schema Markup (Recommended Addition)

Add FAQ schema for Quick Answer Hub:
```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [...]
}
```

### Meta Tags

Ensure proper meta tags on the page:
- Title: "Poradnik.pro - Sprawdzone poradniki, które rozwiązują realne problemy"
- Description: "Dowiedz się, co zrobić, ile to kosztuje i kiedy warto skorzystać z fachowca. Bez lania wody — tylko praktyczne informacje."

## Conversion Optimization

### Multiple CTA Placements

The template includes 6 CTA opportunities:
1. Hero section (2 CTAs)
2. Cost Hub
3. Problem-Solution section (2 CTAs)
4. Final CTA (2 CTAs)

### Trust Signal Placement

Trust elements appear in 3 locations:
1. Hero trust bullets
2. Trust block (section 8)
3. Footer brand message

### A/B Testing Recommendations

Test variations of:
- Hero headline
- CTA button text
- Cost examples (different services)
- Quick answer topics

## Mobile Responsiveness

All sections are fully responsive:

### Breakpoints
- **Desktop**: 1200px+ (multi-column layouts)
- **Tablet**: 768px-1199px (2-column layouts)
- **Mobile**: <768px (single column, stacked)

### Mobile-Specific Optimizations
- Larger touch targets (buttons)
- Simplified navigation
- Reduced text sizes
- Stacked CTAs

## Browser Support

- Chrome/Edge: Full support
- Firefox: Full support
- Safari: Full support (including iOS)
- IE11: Graceful degradation (no gradients, simplified layout)

## Troubleshooting

### Template Not Appearing in Dropdown

1. Check file is in theme root: `theme/pearblog-theme/page-poradnik-v4-hipro.php`
2. Verify "Template Name" comment at top of file
3. Clear WordPress cache

### Styles Not Loading

1. Check CSS file exists: `assets/css/poradnik-v4-hipro.css`
2. Verify enqueue code in `functions.php`
3. Hard refresh browser (Ctrl+Shift+R)
4. Check browser console for 404 errors

### Dynamic Content Not Showing

1. Verify posts exist in WordPress
2. Check post status is "publish"
3. Confirm `post_views_count` meta key exists (or remove that filter)

## Integration with PT24.pro

Section 7 creates cross-site links to PT24.pro local services:

```php
['title' => 'Mechanik Katowice', 'url' => '/katowice/mechanik/'],
```

These URLs should match your PT24.pro URL structure. Update as needed.

## Future Enhancements

Potential additions:
- [x] Admin settings page for easy customization
- [x] Widget areas for flexible content
- [x] FAQ schema markup automation
- [x] A/B testing framework integration
- [x] Analytics event tracking
- [x] Lead capture form integration
- [x] Live chat integration
- [x] Social proof widgets (testimonials, ratings)

## Version History

### v4.1.0 (2026-05-04)
- Initial V4 HI-PRO Content Hub implementation
- 10 conversion-optimized sections
- Mobile-responsive design
- Dynamic content integration
- PT24.pro cross-linking

## Support

For issues or questions:
1. Check this documentation
2. Review WordPress admin settings
3. Inspect browser console for errors
4. Contact development team

## License

Part of PearBlog Theme v8.0.0

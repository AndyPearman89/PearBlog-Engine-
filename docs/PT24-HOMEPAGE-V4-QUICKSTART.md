# PT24 Homepage V4 - Quick Start Guide

## Overview

The PT24 Homepage V4 template is a high-conversion homepage design for PT24.pro local services platform featuring:

- **Purple gradient hero** (inspired by Poradnik V4 HI-PRO design)
- **Modern, clean aesthetics** optimized for conversions
- **Mobile-first responsive design**
- **6 popular service categories** with hover effects
- **Trust signals** throughout the page
- **Business CTA section** for service providers
- **Popular cities grid** for local navigation

## Files Created

1. **Template File:** `theme/pearblog-theme/page-pt24-home-v4.php`
   - Template Name: "PT24.PRO - Homepage V4"
   - 6 sections: Hero, Services, How It Works, Cities, Business, Final CTA

2. **CSS File:** `theme/pearblog-theme/assets/css/pt24-home-v4.css`
   - Purple gradient styling (`linear-gradient(135deg, #667eea 0%, #764ba2 100%)`)
   - Responsive design with mobile optimizations
   - Smooth transitions and hover effects

## Installation & Setup

### Step 1: Create the Homepage

Use WP-CLI to create a new page with the V4 template:

```bash
# Create the page and capture the ID
PAGE_ID=$(wp post create \
  --post_type=page \
  --post_title="PT24 Homepage V4" \
  --post_status=publish \
  --page_template=page-pt24-home-v4.php \
  --porcelain)

echo "Created page with ID: $PAGE_ID"
```

### Step 2: Set as Homepage

Configure WordPress to use the new page as the homepage:

```bash
# Set homepage to display a static page
wp option update show_on_front page

# Set the page as the homepage (use the ID from Step 1)
wp option update page_on_front $PAGE_ID

echo "Homepage set to page ID: $PAGE_ID"
```

### Complete One-Liner Script

For quick setup, you can use this complete script:

```bash
#!/bin/bash
# Create PT24 Homepage V4 and set as homepage

PAGE_ID=$(wp post create \
  --post_type=page \
  --post_title="PT24 Homepage V4" \
  --post_status=publish \
  --page_template=page-pt24-home-v4.php \
  --porcelain)

if [ $? -eq 0 ]; then
  echo "✓ Created page with ID: $PAGE_ID"

  wp option update show_on_front page
  wp option update page_on_front $PAGE_ID

  echo "✓ Homepage set to page ID: $PAGE_ID"
  echo "✓ Visit: $(wp option get siteurl)"
else
  echo "✗ Failed to create page"
  exit 1
fi
```

### Verification

To verify the setup:

```bash
# Check current homepage setting
wp option get show_on_front
# Expected output: page

# Check which page is set as homepage
wp option get page_on_front
# Should return the page ID

# View page details
wp post get $(wp option get page_on_front)
```

## Design Features

### Color Scheme (V4 Purple Gradient)

- **Primary Gradient:** `#667eea` → `#764ba2`
- **Text Colors:** `#1e293b` (primary), `#64748b` (secondary)
- **Background:** `#ffffff` (primary), `#f8fafc` (secondary)
- **Border:** `#e2e8f0`

### Sections

1. **Hero Section**
   - Purple gradient background
   - Search bar with gradient button
   - Trust signals (3 checkmarks)
   - Clean, conversion-focused messaging

2. **Services Grid**
   - 6 service cards (Mechanik, Hydraulik, Elektryk, Elektryk Samochodowy, Laweta, Wulkanizacja)
   - Hover effects with transform and border color change
   - Icon-first design

3. **How It Works**
   - 3-step process with gradient circular numbers
   - Light background for visual separation

4. **Popular Cities**
   - Grid of 12 cities with hover effects
   - Responsive columns

5. **Business CTA**
   - Centered card layout
   - 4 benefits with checkmark icons
   - Strong call-to-action button

6. **Final CTA**
   - Purple gradient background
   - Dual CTA buttons (Find Service + Add Business)

### Responsive Design

- **Desktop (≥768px):** Full multi-column grids, larger typography
- **Mobile (<768px):** Single/dual columns, stacked elements
- **Touch-friendly:** Large tap targets, adequate spacing

## Customization

### Changing Hero Text

Edit in `page-pt24-home-v4.php`:

```php
<h1 class="pt24-v4-hero__title">
    Your custom title here
</h1>

<p class="pt24-v4-hero__subtitle">
    Your custom subtitle here
</p>
```

### Adding/Removing Services

Edit the services grid in `page-pt24-home-v4.php` around line 90:

```php
<a href="/your-service/" class="pt24-v4-service-card">
    <div class="pt24-v4-service-card__icon">🔧</div>
    <h3 class="pt24-v4-service-card__title">Your Service</h3>
    <p class="pt24-v4-service-card__description">Description here</p>
    <span class="pt24-v4-service-card__arrow">→</span>
</a>
```

### Customizing Cities

Edit the `$cities` array in `page-pt24-home-v4.php` around line 224:

```php
$cities = [
    'city-slug' => 'City Name',
    // Add more cities...
];
```

### Modifying Colors

Edit CSS variables in `pt24-home-v4.css`:

```css
:root {
    --pt24-v4-primary: #2563eb;  /* Change main color */
    /* Modify other variables as needed */
}
```

## Comparison with Other Templates

| Feature | V4 (New) | Original PT24 | Poradnik V4 HI-PRO |
|---------|----------|---------------|-------------------|
| Gradient Hero | ✓ Purple | ✗ Solid | ✓ Purple |
| Search Bar | ✓ In Hero | ✗ | ✓ In Hero |
| Trust Signals | ✓ | ✗ | ✓ |
| Service Cards | 6 | 5 | N/A |
| Business CTA | Card Style | Section | N/A |
| Design System | V4 Modern | Classic | V4 Modern |

## Troubleshooting

### Template not appearing in WordPress admin

Make sure the template file is in the theme root directory and has the correct template header:

```php
/**
 * Template Name: PT24.PRO - Homepage V4
 */
```

### CSS not loading

Verify the CSS file path in the template:

```php
wp_enqueue_style('pt24-home-v4', get_template_directory_uri() . '/assets/css/pt24-home-v4.css', array(), '4.0.0');
```

### Page not set as homepage

Check settings:

```bash
wp option get show_on_front  # Should be "page"
wp option get page_on_front  # Should be your page ID
```

## Next Steps

1. **Test the page** - Visit your site homepage
2. **Customize content** - Edit service links, cities, copy
3. **Add tracking** - Implement analytics for conversion tracking
4. **Mobile test** - Verify responsive behavior on mobile devices
5. **Performance** - Run Lighthouse audit and optimize if needed

## Support

For issues or questions:
- Check WordPress admin → Pages → Find your V4 homepage
- Verify template is selected in Page Attributes
- Check browser console for CSS/JS errors
- Test in incognito mode to rule out caching issues

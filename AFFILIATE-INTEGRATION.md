# SEO-Optimized Article Page with Affiliate Integration

## Overview

This implementation provides a production-ready, SEO-optimized article page layout for PearBlog with integrated monetization through AdSense and affiliate programs (Booking.com, Airbnb).

## Features

### Layout Structure

The article page follows a proven layout structure designed to maximize:
- **SEO Performance** - Semantic HTML, proper heading hierarchy, internal linking
- **AdSense Revenue** - Strategic ad placement in high-CTR positions
- **Affiliate Conversions** - Multiple affiliate touchpoints with fallback logic

### Mandatory Layout Elements

1. **H1** - Main keyword optimized title
2. **TL;DR Box** - Quick summary at the top
3. **Ads Block (Top)** - High CTR position
4. **Affiliate Box (Top)** - High conversion position
5. **Table of Contents** - Auto-generated navigation
6. **Content Sections** - Split into 3 parts with strategic insertions
7. **Ads Block (Middle)** - Mid-content monetization
8. **Affiliate Box (Middle)** - Second conversion opportunity
9. **Ads Block (Bottom)** - Final ad placement
10. **FAQ Section** - Schema.org markup for rich snippets
11. **Related Posts** - Internal linking for SEO
12. **Final Affiliate CTA** - Last conversion opportunity

## Components

### 1. Affiliate Box Component

**Location:** `template-parts/block-affiliate.php`

Features:
- Displays up to 3 offers per box
- Shows offer name, price, rating, and image
- Priority logic: Booking.com → Airbnb → Fallback
- Fallback displays generic CTA buttons when no offers available
- Includes click tracking via JavaScript
- Responsive design (mobile-first)

Usage:
```php
pearblog_affiliate_box(array(
    'position' => 'top',        // top, middle, bottom
    'location' => 'Babia Góra', // Location name
    'fallback_enabled' => true,  // Show fallback if no offers
));
```

### 2. Affiliate API

**Location:** `inc/affiliate-api.php`

REST API Endpoints:

#### GET `/wp-json/pearblog/v1/affiliate/offers`
Fetch affiliate offers for a location.

Parameters:
- `location` (string) - Location name
- `post_id` (int) - Post ID to extract location from

Response:
```json
{
  "success": true,
  "location": "Babia Góra",
  "offers": [...],
  "count": 3
}
```

#### POST `/wp-json/pearblog/v1/track-affiliate`
Track affiliate clicks for analytics.

Parameters:
- `source` (string) - booking, airbnb
- `position` (string) - top, middle, bottom
- `post_id` (int) - Post ID
- `url` (string) - Affiliate URL

### 3. Updated Single Post Template

**Location:** `single.php`

The template has been completely redesigned to follow the mandatory SEO layout with strategic ad and affiliate placements.

Key improvements:
- Content split into 3 sections for optimal ad/affiliate insertion
- Mobile-optimized TOC placement
- Automatic location extraction from post title/content
- Schema.org markup for FAQ section
- Internal linking through related posts

## Configuration

### Setting Up Affiliate Offers

#### Option 1: Manual Offers (Recommended for Start)

Add manual offers using the helper function:

```php
pearblog_add_manual_offer('Babia Góra', array(
    'source' => 'booking',
    'name' => 'Schronisko PTTK Markowe Szczawiny',
    'price' => '120 zł',
    'rating' => 8.5,
    'url' => 'https://www.booking.com/hotel/pl/example?aid=YOUR_ID',
    'image' => 'https://example.com/image.jpg',
));
```

#### Option 2: API Integration

Configure API credentials in WordPress options:

```php
// Booking.com API
update_option('pearblog_booking_api_key', 'YOUR_API_KEY');
update_option('pearblog_booking_affiliate_id', 'YOUR_AFFILIATE_ID');

// Airbnb
update_option('pearblog_airbnb_api_key', 'YOUR_API_KEY');
update_option('pearblog_airbnb_affiliate_id', 'YOUR_AFFILIATE_ID');
```

### Setting Location for Posts

Add location meta to posts:

```php
update_post_meta($post_id, 'pearblog_location', 'Babia Góra');
```

Or the system will auto-extract from title/content.

### Configuring Fallback Links

Update fallback affiliate links in `template-parts/block-affiliate.php`:

```php
// Line 162-166
href="https://www.booking.com/?aid=YOUR_BOOKING_ID"

// Line 172-176
href="https://www.airbnb.com/?affiliate_id=YOUR_AIRBNB_ID"
```

## Styling

All affiliate box styles are in `assets/css/components.css` starting at line 961.

Key style features:
- CSS custom properties for theming
- Responsive breakpoints (mobile, tablet, desktop)
- Hover effects and transitions
- Mobile-first approach
- Dark mode support (inherits from theme variables)

## Analytics & Tracking

### Affiliate Click Tracking

All affiliate clicks are tracked automatically via:
- Google Analytics (gtag events)
- Custom REST API endpoint
- Post meta storage

View stats:
```php
$stats = pearblog_get_affiliate_stats($post_id);
// Returns: array('booking_clicks' => 42, 'airbnb_clicks' => 18, 'total_clicks' => 60)
```

## SEO Best Practices

The implementation follows these SEO principles:

1. **Semantic HTML** - Proper heading hierarchy (H1, H2, H3)
2. **Schema.org Markup** - Article, FAQ, Breadcrumb schemas
3. **Internal Linking** - Related posts section
4. **Mobile-First** - Responsive design, fast loading
5. **User Experience** - Clear CTAs, easy navigation
6. **Content Structure** - TL;DR, TOC, FAQ sections

## Performance Optimization

- **Lazy Loading** - Images loaded on demand
- **Caching** - Affiliate offers cached for 6 hours
- **Async Scripts** - Non-blocking JavaScript
- **Minimal Dependencies** - Vanilla JS, no jQuery

## Mobile Optimization

Special considerations for mobile:
- Full-width affiliate boxes on mobile
- Stacked offer layout
- Touch-friendly CTA buttons
- Simplified navigation
- Optimized image sizes

## Revenue Optimization

### Ad Placement Strategy

1. **Top Ad** - After TL;DR (33% scroll depth)
2. **Middle Ad** - Between content sections (66% scroll depth)
3. **Bottom Ad** - Before related posts

### Affiliate Placement Strategy

1. **Top Affiliate** - After first ad (high intent)
2. **Middle Affiliate** - Mid-content (engagement point)
3. **Bottom Affiliate** - Final conversion opportunity

### Conversion Optimization

- Clear CTAs ("Sprawdź dostępność")
- Price and rating displayed prominently
- Fallback ensures monetization even without offers
- Multiple touchpoints increase conversion probability

## Example Use Case: Travel Article

For a travel article about "Babia Góra szlaki":

1. H1: "Babia Góra szlaki - Kompletny przewodnik 2024"
2. TL;DR: Key trail information
3. Top Ad: AdSense display ad
4. Top Affiliate: Mountain hut accommodations
5. TOC: Trail sections
6. Content Part 1: Introduction and preparation
7. Middle Ad: AdSense in-article ad
8. Content Part 2: Trail descriptions
9. Middle Affiliate: Nearby hotels
10. Content Part 3: Tips and recommendations
11. FAQ: Common questions about trails
12. Related Posts: Other mountain guides
13. Bottom Ad: Final AdSense placement
14. Final Affiliate: Last chance booking CTA

## Maintenance

### Updating Offers

Offers are cached for 6 hours. To force refresh:

```php
delete_transient('pearblog_offers_' . md5($location));
```

### Monitoring Performance

Track key metrics:
- Affiliate click-through rate
- AdSense RPM
- Page engagement time
- Conversion rates

Access stats via:
```php
$global_stats = pearblog_get_affiliate_stats();
```

## Future Enhancements

Potential improvements:
- Dynamic pricing updates via API
- A/B testing for affiliate placement
- ML-based content optimization
- Automated offer matching
- Enhanced analytics dashboard

## Support

For issues or questions about the affiliate integration:
1. Check WordPress error logs
2. Verify API credentials in options
3. Test REST endpoints manually
4. Review browser console for JavaScript errors

---

**Built for PearBlog.pro - AI SaaS Content Platform**
*Optimized for SEO, AdSense Revenue, and Affiliate Conversions*

# PT24 Landing Page Generator System V2

Complete system for programmatically generating SEO-optimized landing pages at scale for PT24.PRO local business directory.

## 🎯 Overview

The PT24 Landing Generator creates thousands of location-specific landing pages for service/city combinations with:
- Custom Post Type with SEO-friendly URLs (`/{city}/{service}`)
- Bulk generation via admin UI or WP-CLI
- CSV import for mass data loading
- Dynamic content rendering from post meta
- Full integration with PT24 lead capture system

## 📊 Scale Capability

```
5 services × 6 cities = 30 pages
10 services × 100 cities = 1,000 pages
50 services × 200 cities = 10,000 pages
```

## 🏗️ Architecture

### Custom Post Type: `pt24_landing`

**URL Structure:**
```
/{miasto}/{usluga}

Examples:
/krakow/hydraulik
/warszawa/elektryk
/wroclaw/pompa-ciepla
```

**Post Meta Fields:**
- `pt24_service` - Service slug (e.g., "hydraulik")
- `pt24_city` - City slug (e.g., "krakow")
- `pt24_service_display` - Service display name (e.g., "Hydraulik")
- `pt24_city_display` - City display name (e.g., "Kraków")
- `pt24_h1` - Page H1 heading
- `pt24_meta_title` - SEO title tag
- `pt24_meta_description` - SEO meta description
- `pt24_hero_text` - Hero section subtitle

### Components

1. **CPT Registration** (`inc/pt24-landing-cpt.php`)
   - Post type registration
   - Custom rewrite rules
   - Permalink structure
   - Admin columns
   - Generator functions

2. **Single Template** (`single-pt24_landing.php`)
   - Dynamic content rendering
   - Uses existing PT24 landing design
   - Lead form integration
   - SEO meta tags

3. **WP-CLI Commands** (`inc/pt24-landing-cli.php`)
   - Bulk generation
   - CSV import
   - Management commands

4. **Admin Interface** (`inc/pt24-landing-admin.php`)
   - Web-based generator
   - Service/city selection
   - Statistics dashboard

## 🚀 Usage

### Method 1: Admin Interface

1. Navigate to **PT24 Landings → Generator** in WordPress admin
2. Select services and cities to generate
3. Click "Generate Landing Pages"
4. Pages are created instantly

### Method 2: WP-CLI (Recommended for Bulk)

**Generate all combinations:**
```bash
wp pt24 generate
```

**Generate specific combinations:**
```bash
wp pt24 generate --services=hydraulik,elektryk --cities=krakow,warszawa
```

**Dry run (preview without creating):**
```bash
wp pt24 generate --dry-run
```

**Import from CSV:**
```bash
wp pt24 import landings.csv
```

**List all landing pages:**
```bash
wp pt24 list
wp pt24 list --format=csv
wp pt24 list --format=json
```

**Delete all landing pages:**
```bash
wp pt24 delete-all
wp pt24 delete-all --yes
```

**Flush rewrite rules:**
```bash
wp pt24 flush-rewrites
```

### Method 3: Programmatic API

```php
// Generate single landing
$post_id = PearBlog_PT24_Landing_CPT::generate_landing('hydraulik', 'krakow');

// Bulk generate
$result = PearBlog_PT24_Landing_CPT::bulk_generate(
    ['hydraulik', 'elektryk'],
    ['krakow', 'warszawa']
);

// Import from CSV
$result = PearBlog_PT24_Landing_CPT::import_csv('/path/to/file.csv');
```

## 📄 CSV Import Format

Create a CSV file with two columns: `service,city`

```csv
service,city
hydraulik,krakow
elektryk,warszawa
pompa-ciepla,wroclaw
remont-lazienki,katowice
fotowoltaika,poznan
```

Then import:
```bash
wp pt24 import landings.csv
```

## ⚙️ Configuration

### Services

Edit in `inc/pt24-landing-cpt.php`:

```php
private static $services = [
    'hydraulik' => 'Hydraulik',
    'elektryk' => 'Elektryk',
    'pompa-ciepla' => 'Pompa ciepła',
    'remont-lazienki' => 'Remont łazienki',
    'fotowoltaika' => 'Fotowoltaika',
    // Add more services...
];
```

### Cities

Edit in `inc/pt24-landing-cpt.php`:

```php
private static $cities = [
    'krakow' => 'Kraków',
    'warszawa' => 'Warszawa',
    'wroclaw' => 'Wrocław',
    'katowice' => 'Katowice',
    'poznan' => 'Poznań',
    'gdansk' => 'Gdańsk',
    // Add more cities...
];
```

## 🎨 Template Customization

The single template (`single-pt24_landing.php`) uses the existing PT24 landing page design with these sections:

1. **Hero** - Dynamic heading with service/city
2. **Lead Form** - Integrated with PT24 lead capture
3. **Map/Proof** - Social proof section
4. **Cost Block** - Dynamic pricing information
5. **Ranking** - Company ranking criteria
6. **Second CTA** - Mid-page conversion
7. **FAQ** - Common questions
8. **Final CTA** - Bottom conversion

### Customizing Content

Content is pulled from post meta fields. To customize for specific pages:

```php
// In your theme or plugin
add_action('wp', function() {
    if (is_singular('pt24_landing')) {
        $post_id = get_the_ID();

        // Update custom content
        update_post_meta($post_id, 'pt24_hero_text', 'Your custom hero text');
        update_post_meta($post_id, 'pt24_h1', 'Your custom H1');
    }
});
```

## 🔍 SEO Features

### Automatic Meta Tags

Each landing page automatically generates:
- **Title Tag**: `{Service} {City} — ceny i oferty`
- **Meta Description**: `Znajdź {Service} w {City}. Sprawdź ceny i dostępne firmy.`
- **Open Graph Tags**: Automatic OG title and description
- **Keywords**: Service + city combinations

### URL Structure

SEO-friendly URLs follow the pattern: `/{city}/{service}/`

Examples:
- `/krakow/hydraulik/`
- `/warszawa/elektryk/`
- `/wroclaw/pompa-ciepla/`

### Schema Markup (Future)

To add schema.org markup for local businesses:

```php
add_action('wp_head', function() {
    if (is_singular('pt24_landing')) {
        // Add LocalBusiness schema
        // Add Service schema
        // Add BreadcrumbList schema
    }
});
```

## 📈 Conversion Optimization

### Lead Capture Integration

All landing pages integrate with the existing PT24 lead capture system:
- Form pre-filled with service/city
- AJAX submission
- Email notifications (admin + user)
- Database storage in `wp_pt24_leads`

### CTA Placement

Strategic CTA placement throughout the page:
1. Hero section (above fold)
2. After form section
3. Cost block CTA
4. Second CTA section
5. Final CTA (bottom)

### Trust Signals

Each page includes:
- ✔ Darmowe i bez zobowiązań
- ✔ Tylko sprawdzone firmy
- ✔ Oferty nawet w 24h

## 🧪 Testing

After generation, test a sample landing page:

1. **Visit URL:**
   ```
   https://yoursite.com/krakow/hydraulik/
   ```

2. **Check elements:**
   - Dynamic H1 with service and city
   - Lead form working
   - Form submission to database
   - Email notifications sent
   - Mobile responsiveness

3. **Test form:**
   - Fill in all required fields
   - Submit form
   - Check `wp_pt24_leads` table for entry
   - Verify admin notification email
   - Verify user confirmation email

## 🔧 Troubleshooting

### URLs not working (404)

Flush rewrite rules:
```bash
wp pt24 flush-rewrites
```

Or in WordPress admin:
Settings → Permalinks → Save Changes

### Duplicate pages

The generator checks for existing pages before creating. If you see duplicates:
```bash
# List all pages
wp pt24 list

# Delete all and regenerate
wp pt24 delete-all --yes
wp pt24 generate
```

### Template not loading

Ensure `single-pt24_landing.php` exists in theme root directory.

### Form submissions not saving

Check that PT24 integration is loaded:
- File: `inc/pt24-integration.php`
- AJAX handler: `pt24_submit_lead`
- Database table: `wp_pt24_leads`

## 🚀 Deployment Checklist

1. ✅ Upload all files to theme
2. ✅ Activate theme or reload if already active
3. ✅ Flush rewrite rules: `wp pt24 flush-rewrites`
4. ✅ Configure services and cities in `inc/pt24-landing-cpt.php`
5. ✅ Generate landing pages: `wp pt24 generate`
6. ✅ Test sample URL: `/krakow/hydraulik/`
7. ✅ Test form submission
8. ✅ Verify email notifications
9. ✅ Submit sitemap to Google Search Console
10. ✅ Monitor conversions

## 📊 Performance Optimization

### Caching

Landing pages are standard WordPress posts, so they work with any caching plugin:
- WP Super Cache
- W3 Total Cache
- WP Rocket
- Cloudflare

### Database Indexing

The CPT includes proper database indexes for:
- Post type queries
- Meta queries (service, city)
- URL lookups

### Bulk Operations

For generating 1000+ pages, use WP-CLI instead of admin interface:
```bash
wp pt24 generate --services=all --cities=all
```

## 🎯 Monetization Strategy

### Lead Flow

```
SEO Traffic → Landing Page → Lead Form → Database → Firm Sale
```

### Revenue Streams

1. **Lead Sales** - Charge per qualified lead
2. **Premium Listings** - Featured placement in rankings
3. **Firm Subscriptions** - Monthly access to leads
4. **Sponsored Results** - Pay-per-click model

## 📱 Mobile Optimization

All landing pages are mobile-first:
- Responsive grid layouts
- Touch-friendly buttons
- Optimized form fields
- Fast loading times
- Minimal JavaScript

## 🔐 Security

### Form Security

- WordPress nonce verification
- Input sanitization
- CSRF protection
- SQL injection prevention

### Access Control

- Only admins can generate pages
- Form submissions validated
- Email addresses verified
- Phone numbers sanitized

## 📈 Analytics Integration

Track conversions with Google Analytics:

```javascript
// Already integrated in pt24-landing.js
gtag('event', 'conversion', {
    'send_to': 'AW-XXXXX/XXXXX',
    'transaction_id': lead_id
});
```

## 🔄 Updates and Maintenance

### Adding New Services

1. Edit `inc/pt24-landing-cpt.php`
2. Add to `$services` array
3. Run: `wp pt24 generate --services=new-service`

### Adding New Cities

1. Edit `inc/pt24-landing-cpt.php`
2. Add to `$cities` array
3. Run: `wp pt24 generate --cities=new-city`

### Bulk Updates

To update existing landing pages:

```bash
# Delete all
wp pt24 delete-all --yes

# Regenerate with new template
wp pt24 generate
```

## 📁 File Structure

```
theme/pearblog-theme/
├── inc/
│   ├── pt24-integration.php        # Core PT24 integration
│   ├── pt24-landing-cpt.php        # CPT registration & generator
│   ├── pt24-landing-cli.php        # WP-CLI commands
│   └── pt24-landing-admin.php      # Admin interface
├── single-pt24_landing.php         # Single template
├── assets/
│   ├── css/
│   │   └── pt24-landing.css        # Landing page styles
│   └── js/
│       └── pt24-landing.js         # Form handling & FAQ
└── functions.php                    # Includes all files
```

## 🎓 Best Practices

1. **Start small** - Generate 5-10 pages first to test
2. **Use WP-CLI** - For bulk operations (100+ pages)
3. **Monitor performance** - Check page load times
4. **Test forms** - Verify lead capture works
5. **Flush rewrites** - After any changes to URLs
6. **Backup database** - Before bulk operations
7. **Use staging** - Test on staging site first

## 🆘 Support

For issues or questions:
1. Check `PT24-LANDING-IMPLEMENTATION.md` for original PT24 system docs
2. Review `PT24-INTEGRATION-GUIDE.md` for integration details
3. Check WordPress debug log for errors
4. Test with WP_DEBUG enabled

## 📝 Version History

**Version 2.0.0** (Current)
- Custom Post Type generator system
- Bulk generation via WP-CLI
- CSV import functionality
- Admin interface
- SEO-optimized URLs
- Dynamic template rendering

**Version 1.0.0** (Previous)
- Single page template with URL parameters
- Manual page creation

## 🚀 Future Enhancements

- [x] Schema.org markup for local businesses
- [x] Automated content variation (AI-generated)
- [x] Dynamic pricing data integration
- [x] Company listing integration
- [x] Review/rating system
- [x] Heatmap tracking
- [x] A/B testing framework
- [x] Automatic sitemap generation

---

**Ready to scale! Generate your landing pages and start capturing leads.**

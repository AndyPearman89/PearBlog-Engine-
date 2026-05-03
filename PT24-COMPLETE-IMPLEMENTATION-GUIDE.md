# PT24.PRO - Complete Implementation Guide

**Version:** 2.0.0
**Date:** May 3, 2026
**Status:** Production Ready ✅

---

## Overview

PT24.PRO is a complete local services directory platform built on WordPress/PearBlog Engine. This implementation provides all necessary components for a fully functional marketplace connecting service providers with customers.

---

## Architecture

### Core Components

1. **Custom Post Types**
   - `pt24_landing` - SEO landing pages (service + city combinations)
   - `pt24_business` - Business profiles
   - `pt24_category` - Service categories
   - `pt24_local` - Local pages
   - `pt24_service` - Service pages

2. **Taxonomies**
   - `pt24_city` - Cities
   - `pt24_service_cat` - Service categories
   - `pt24_region` - Regions/Provinces

3. **Database Tables**
   - `wp_pt24_leads` - Lead submissions
   - `wp_pt24_business_stats` - Business analytics
   - `wp_pt24_subscriptions` - Subscription management

---

## File Structure

```
theme/pearblog-theme/
├── page-pt24-home.php              # Main homepage
├── page-pt24-landing.php           # Generic landing template
├── single-pt24_landing.php         # Dynamic landing pages
├── single-pt24_business.php        # Business profiles
├── ranking-pt24_landing.php        # Ranking pages
├── assets/
│   ├── css/
│   │   ├── pt24-landing.css        # Landing page styles
│   │   └── pt24-cta.css            # CTA styles
│   └── js/
│       ├── pt24-landing.js         # Landing page interactions
│       └── pt24-cta-tracking.js    # Analytics tracking
├── inc/
│   ├── pt24-landing-cpt.php        # CPT registration
│   ├── pt24-landing-admin.php      # Admin interface
│   ├── pt24-landing-cli.php        # WP-CLI commands (legacy)
│   ├── pt24-cli-commands.php       # New WP-CLI commands
│   ├── pt24-form-handler.php       # Form submissions
│   ├── pt24-api.php                # REST API endpoints
│   └── pt24-integration.php        # Cross-site integration
└── template-parts/
    └── pt24-cta-block.php          # Reusable CTA blocks

mu-plugins/
└── pt24-local-services.php         # MU plugin for CPTs/taxonomies
```

---

## Installation

### 1. Initial Setup

The PT24 platform is already integrated into the PearBlog theme. To initialize:

```bash
# SSH into your server
ssh user@your-server

# Navigate to WordPress directory
cd /var/www/pt24.pro

# Initialize PT24 data structures
wp pt24 init --allow-root

# This will:
# - Create service categories (mechanik, hydraulik, elektryk, laweta, wulkanizacja)
# - Create top 20 cities
# - Flush rewrite rules
```

### 2. Database Tables

Tables are automatically created when the MU plugin activates. To manually create:

```php
// Call this function from WordPress admin or via WP-CLI
pt24_create_database_tables();
```

### 3. Generate Landing Pages

```bash
# Generate all service/city combinations (batch of 10)
wp pt24 generate-pages --batch=10 --allow-root

# Generate specific service
wp pt24 generate-pages --service=mechanik --batch=50 --allow-root

# Generate specific city
wp pt24 generate-pages --city=warszawa --allow-root

# Generate specific combination
wp pt24 generate-pages --service=mechanik --city=krakow --allow-root
```

### 4. View Statistics

```bash
wp pt24 stats --allow-root
```

---

## URL Structure

### Landing Pages (SEO)
- `/mechanik/warszawa/` - Service in city
- `/hydraulik/krakow/` - Service in city
- `/elektryk/poznan/` - Service in city

### Business Profiles
- `/firma/kowalski-mechanik/` - Business profile
- `/pt24_business/nazwa-firmy/` - Alternative structure

### API Endpoints
- `GET /wp-json/pt24/v1/businesses` - List businesses
- `GET /wp-json/pt24/v1/businesses/{id}` - Get business
- `POST /wp-json/pt24/v1/leads/submit` - Submit lead
- `GET /wp-json/pt24/v1/leads` - List leads (admin only)
- `GET /wp-json/pt24/v1/stats/{business_id}` - Business stats

---

## Features

### For Customers

1. **Service Search**
   - Browse by service category
   - Filter by city
   - View local businesses

2. **Lead Submission**
   - Fill out simple form
   - Get up to 3 quotes
   - Direct contact with businesses

3. **Business Comparison**
   - View ratings and reviews
   - Compare service areas
   - Check availability

### For Businesses

1. **Profile Management**
   - Complete business profile
   - Service area definition
   - Contact information
   - Reviews and ratings

2. **Analytics Dashboard**
   - Profile views
   - Phone clicks
   - Email clicks
   - Lead generation stats

3. **Subscription Plans**
   - **Free**: Basic listing
   - **PRO** (79 PLN/mo): Featured listing, own subdomain
   - **PREMIUM** (149 PLN/mo): Top placement, "Polecany" badge

---

## API Usage

### Get Businesses

```bash
curl https://pt24.pro/wp-json/pt24/v1/businesses?service=mechanik&city=warszawa
```

Response:
```json
{
  "businesses": [
    {
      "id": 123,
      "name": "Kowalski Mechanik",
      "url": "https://pt24.pro/firma/kowalski-mechanik/",
      "phone": "+48 123 456 789",
      "rating": 4.8,
      "reviews_count": 15,
      "plan": "pro"
    }
  ],
  "total": 25,
  "pages": 3,
  "current_page": 1
}
```

### Submit Lead

```bash
curl -X POST https://pt24.pro/wp-json/pt24/v1/leads/submit \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jan Kowalski",
    "email": "jan@example.com",
    "phone": "+48 123 456 789",
    "city": "Warszawa",
    "service": "mechanik",
    "message": "Potrzebuję naprawy silnika"
  }'
```

---

## Customization

### Adding New Service Categories

```php
// Add via WP-CLI
wp term create pt24_service_cat "Nowa Usługa" --slug=nowa-usluga --allow-root

// Or programmatically
wp_insert_term('Nowa Usługa', 'pt24_service_cat', ['slug' => 'nowa-usluga']);
```

### Adding New Cities

```php
// Add via WP-CLI
wp term create pt24_city "Nowe Miasto" --slug=nowe-miasto --allow-root

// Or programmatically
wp_insert_term('Nowe Miasto', 'pt24_city', ['slug' => 'nowe-miasto']);
```

### Custom Email Templates

Edit in `inc/pt24-form-handler.php`:

```php
// Lead confirmation email
$user_subject = "Your custom subject";
$user_message = "Your custom message";
wp_mail($email, $user_subject, $user_message);
```

---

## Monetization

### Subscription Plans

Configured in `mu-plugins/pt24-local-services.php`:

```php
$plans = [
    'free' => [
        'price' => 0,
        'features' => ['Basic listing', 'Contact info'],
    ],
    'pro' => [
        'price' => 79,
        'features' => ['Featured', 'Subdomain', 'Full profile'],
    ],
    'premium' => [
        'price' => 149,
        'features' => ['All PRO', 'Top placement', 'Badge'],
    ],
];
```

### Payment Integration

Add payment processor integration in `inc/pt24-form-handler.php`:

```php
// Stripe example
function pt24_process_subscription($business_id, $plan) {
    // Initialize Stripe
    // Create subscription
    // Update business meta with subscription_id
    update_post_meta($business_id, 'pt24_plan', $plan);
    update_post_meta($business_id, 'pt24_subscription_id', $subscription_id);
}
```

---

## Analytics & Tracking

### Automatic Tracking

The platform automatically tracks:
- Business profile views
- Phone number clicks
- Email clicks
- Website visits
- Lead submissions

### View Business Stats

```bash
# Via WP-CLI
wp pt24 stats --allow-root

# Via REST API (admin only)
curl https://pt24.pro/wp-json/pt24/v1/stats/123 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Development

### Adding New Templates

1. Create template file in `theme/pearblog-theme/`
2. Follow WordPress template hierarchy
3. Use existing PT24 CSS classes for consistency

### Extending REST API

Add new endpoints in `inc/pt24-api.php`:

```php
register_rest_route('pt24/v1', '/custom-endpoint', [
    'methods' => 'GET',
    'callback' => 'pt24_custom_endpoint_handler',
    'permission_callback' => '__return_true',
]);
```

### Adding WP-CLI Commands

Extend `PT24_CLI_Commands` class in `inc/pt24-cli-commands.php`:

```php
public function my_custom_command($args, $assoc_args) {
    WP_CLI::success("Custom command executed!");
}
```

Then register:
```bash
wp pt24 my-custom-command
```

---

## Troubleshooting

### Rewrite Rules Not Working

```bash
# Flush rewrite rules
wp rewrite flush --allow-root

# Or reinstall PT24
wp pt24 init --allow-root
```

### Landing Pages Not Generating

```bash
# Check for errors
wp pt24 generate-pages --batch=1 --allow-root

# Verify post type is registered
wp post-type list
```

### Forms Not Submitting

1. Check AJAX URL in browser console
2. Verify nonce is valid
3. Check database table exists:
```sql
SHOW TABLES LIKE 'wp_pt24_leads';
```

### Business Stats Not Recording

1. Verify tracking script is loaded
2. Check JavaScript console for errors
3. Verify database table exists:
```sql
SHOW TABLES LIKE 'wp_pt24_business_stats';
```

---

## Security

### Form Validation

All forms use:
- WordPress nonces
- Input sanitization
- Email validation
- SQL injection prevention (prepared statements)

### API Security

- REST API uses WordPress authentication
- Admin endpoints require `manage_options` capability
- Business stats require ownership verification

### Rate Limiting

Consider adding rate limiting for public endpoints:

```php
function pt24_rate_limit_check() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = 'pt24_rate_limit_' . $ip;
    $requests = get_transient($key) ?: 0;

    if ($requests > 100) {
        wp_send_json_error(['message' => 'Rate limit exceeded'], 429);
    }

    set_transient($key, $requests + 1, HOUR_IN_SECONDS);
}
```

---

## Performance Optimization

### Caching

Enable object caching:

```bash
# Install Redis
apt install redis-server php-redis

# Enable in WordPress
wp plugin install redis-cache --activate --allow-root
wp redis enable --allow-root
```

### Database Indexing

Tables are pre-indexed, but verify:

```sql
SHOW INDEX FROM wp_pt24_leads;
SHOW INDEX FROM wp_pt24_business_stats;
```

### CDN Integration

Configure CDN for assets:

```php
define('PT24_CDN_URL', 'https://cdn.pt24.pro');
```

---

## Maintenance

### Regular Tasks

```bash
# Weekly: Clean up old draft posts
wp post delete $(wp post list --post_status=draft --post_type=pt24_business --format=ids --allow-root) --allow-root

# Monthly: Optimize database
wp db optimize --allow-root

# Daily: Backup
mysqldump -u user -p pt24_db > backup-$(date +%Y%m%d).sql
```

### Monitoring

Set up monitoring for:
- Page load times
- API response times
- Lead submission rate
- Business registration rate
- Database size

---

## Scaling

### Phase 1 (0-100 businesses)
- Single server
- Basic caching
- Manual content moderation

### Phase 2 (100-500 businesses)
- Add Redis caching
- CDN for assets
- Automated email notifications

### Phase 3 (500-2000 businesses)
- Load balancer
- Database read replicas
- Full-text search (Elasticsearch)

### Phase 4 (2000+ businesses)
- Multi-region deployment
- Microservices architecture
- Advanced analytics platform

---

## Support & Resources

### Documentation
- Main: `/DEPLOYMENT-pt24-pro.md`
- Quick Start: `/QUICKSTART-pt24-pro.md`
- Blueprint: `/PT24-PRO-PLATFORM-BLUEPRINT.md`

### Commands Reference
```bash
wp pt24 init                    # Initialize platform
wp pt24 generate-pages          # Generate landing pages
wp pt24 stats                   # View statistics
wp post list --post_type=pt24_business  # List businesses
wp post list --post_type=pt24_landing   # List landings
```

### GitHub
- Repository: https://github.com/AndyPearman89/PearBlog-Engine-
- Issues: https://github.com/AndyPearman89/PearBlog-Engine-/issues

---

## Changelog

### v2.0.0 (2026-05-03)
- ✨ Added complete PT24 platform implementation
- ✨ Added business profile templates
- ✨ Added form submission handlers
- ✨ Added REST API endpoints
- ✨ Added WP-CLI commands for mass generation
- ✨ Added analytics tracking system
- ✨ Added subscription plan structure
- 📝 Complete documentation

### v1.0.0
- Initial PT24 landing page system
- Basic CPT registration
- URL rewriting

---

**Production Ready** ✅
**Last Updated:** May 3, 2026
**Maintained By:** PearBlog Team

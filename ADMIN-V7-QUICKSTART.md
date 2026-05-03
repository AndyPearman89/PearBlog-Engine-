# Admin Panel v7.0 - Quick Start Guide

## Overview

PearBlog Engine v7.0 introduces a revolutionary **SaaS Control Center** that transforms the admin interface from a settings-focused page into a comprehensive revenue management platform.

## Enabling v7 Admin

### Method 1: Feature Flag (Recommended)

Add this line to your `wp-config.php` file:

```php
define('PEARBLOG_ADMIN_VERSION', 'v7');
```

Place it before the line that says `/* That's all, stop editing! */`

### Method 2: Automatic (Future)

In future versions, v7 will become the default. You can still opt back to v6 using:

```php
define('PEARBLOG_ADMIN_VERSION', 'v6');
```

## Database Migration

The v7 admin requires three new database tables. Run the migration:

### Via WP-CLI
```bash
wp eval "PearBlogEngine\Admin\DatabaseMigration::migrate_to_v7();"
```

### Via PHP
```php
use PearBlogEngine\Admin\DatabaseMigration;
DatabaseMigration::migrate_to_v7();
```

### Migration Status
Check migration status:
```bash
wp eval "print_r(PearBlogEngine\Admin\DatabaseMigration::get_migration_status());"
```

## New Features

### 10-Tab Navigation

1. **📊 Dashboard** - Real-time revenue & performance overview
2. **🧠 Strategy (AI)** - AI-driven content strategy control
3. **✍️ Content Engine** - Content generation & batch operations
4. **🔍 SEO Engine** - SEO automation & optimization
5. **💰 Monetization** - Revenue tracking & ad management
6. **👥 Leads & Experts** - Lead management & expert routing
7. **⚙️ Automation** - Queue & scheduling management
8. **📈 Analytics** - Advanced metrics & filtering
9. **🌐 Multisite/SaaS** - Multi-tenant management
10. **⚙️ Settings** - Configuration

### Database Tables

#### `wp_pb_revenue`
Tracks revenue per article with daily granularity:
- Revenue source (AdSense, affiliate, sponsored)
- Revenue amount and currency
- Views, clicks, RPM calculations
- Date-based aggregations

#### `wp_pb_leads`
Captures and routes leads to domain experts:
- Contact information (name, email, phone, company)
- Lead source tracking
- Status workflow (new → contacted → qualified → converted)
- Expert assignment and routing
- Priority levels
- Conversion tracking

#### `wp_pb_experts`
Expert profiles for lead routing:
- User association
- Specialties and expertise areas
- Category-based routing rules
- Daily lead capacity limits
- Availability status
- Performance metrics (conversion rate, rating)

## Implementation Phases

### ✅ Phase 1: Foundation (v7.1 - June 2026) - COMPLETE
- New admin architecture with 10-tab navigation
- Feature flag system for v6/v7 switching
- Database schema and migration system
- Modern v7 design language
- Backward compatibility

### 🚧 Phase 2: Dashboard & Analytics (v7.2 - July 2026)
- Real-time revenue dashboard with KPIs
- Advanced analytics with filtering
- Chart.js visualizations
- Performance metrics tracking

### 🚧 Phase 3: AI Strategy & Content Engine (v7.3 - August 2026)
- AI-driven keyword discovery
- Intent priority system
- Batch content generation (10-100 articles)
- Content templates

### 🚧 Phase 4: Leads & Expert Management (v7.4 - September 2026)
- Lead capture forms
- Automatic expert routing
- Performance tracking
- Notification system

### 🚧 Phase 5: Monetization & Multisite (v7.5 - October 2026)
- Per-article revenue tracking
- RPM optimization
- Central SaaS dashboard
- Multi-tenant management

## Switching Between v6 and v7

### Switch to v7
```php
// In wp-config.php
define('PEARBLOG_ADMIN_VERSION', 'v7');
```

### Switch back to v6
```php
// In wp-config.php
define('PEARBLOG_ADMIN_VERSION', 'v6');
```

### Check Current Version
The active version is displayed in the admin interface title:
- v6: "PearBlog Engine" (standard)
- v7: "PearBlog Engine v7.0" with "SaaS Control Center" badge

## Rollback

If needed, you can rollback the v7 database migration:

```bash
wp eval "PearBlogEngine\Admin\DatabaseMigration::rollback_v7();"
```

**⚠️ WARNING:** This will delete all data in v7 tables (revenue, leads, experts).

## Demo Data

To seed demo data for testing:

```bash
wp eval "PearBlogEngine\Admin\DatabaseMigration::seed_demo_data();"
```

This creates:
- Sample revenue data for last 30 days
- Demo expert profile for current admin user

## Troubleshooting

### v7 Admin Not Showing
1. Verify `PEARBLOG_ADMIN_VERSION` is set to 'v7' in wp-config.php
2. Clear WordPress cache
3. Check PHP error logs for any fatal errors

### Database Tables Not Created
1. Run migration manually: `DatabaseMigration::migrate_to_v7()`
2. Check database user has CREATE TABLE privileges
3. Verify table names don't conflict with existing tables

### Charts Not Rendering
1. Verify Chart.js is loading (check browser console)
2. Check for JavaScript errors
3. Ensure jQuery is available

### Missing Features
Some tabs display "Coming in Phase X" notices. These features are planned for future releases according to the implementation roadmap.

## Support

For issues or questions:
- Review ADMIN-PANEL-V7-PLAN.md for detailed specifications
- Check GitHub issues for known problems
- Report bugs with `[Admin v7]` prefix

## Developer Notes

### Extending v7 Admin

To add custom functionality to v7 tabs:

```php
// Hook into v7 tab rendering
add_filter('pearblog_v7_tab_content', function($content, $tab_id) {
    if ($tab_id === 'custom') {
        $content .= '<div>Custom content here</div>';
    }
    return $content;
}, 10, 2);
```

### REST API Integration

v7 admin uses REST API for dynamic data:
- `/wp-json/pearblog/v1/dashboard/kpis` - Dashboard KPIs
- `/wp-json/pearblog/v1/revenue/*` - Revenue endpoints (Phase 5)
- `/wp-json/pearblog/v1/leads/*` - Lead endpoints (Phase 4)
- `/wp-json/pearblog/v1/experts/*` - Expert endpoints (Phase 4)

### CSS Customization

Override v7 styles by enqueueing custom CSS after `admin-v7.css`:

```php
add_action('admin_enqueue_scripts', function() {
    wp_enqueue_style('my-v7-customizations',
        get_stylesheet_directory_uri() . '/admin-v7-custom.css',
        ['pearblog-admin-v7'],
        '1.0.0'
    );
});
```

## Roadmap

- **June 2026:** Phase 1 Foundation ✅
- **July 2026:** Phase 2 Dashboard & Analytics
- **August 2026:** Phase 3 AI Strategy & Content Engine
- **September 2026:** Phase 4 Leads & Expert Management
- **October 2026:** Phase 5 Monetization & Multisite

---

*PearBlog Engine v7.0 - Transforming autonomous content generation into revenue-driven SaaS*

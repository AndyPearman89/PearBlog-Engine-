# PearBlog Admin Panel v7.0 - Implementation Plan

**Status:** Planning Phase
**Date:** May 3, 2026
**Target:** Post-v7.0 Launch (v7.1+ Feature)

---

## Executive Summary

Transform the current PearBlog admin settings page into a comprehensive **SaaS Control Center** focused on autonomous revenue management, not just configuration. This is a major architectural enhancement that repositions PearBlog as a revenue-optimization platform.

---

## Current State Analysis

### Existing Admin Structure (v6.0/v7.0)

**Location:** `mu-plugins/pearblog-engine/src/Admin/AdminPage.php`

**Current Tabs (8):**
1. General - API keys, site profile settings
2. Images - Image generation, DALL-E settings
3. SEO - Programmatic SEO, audit tools
4. Monetization - AdSense, affiliate settings
5. Email - ESP integration, lead notifications
6. Queue - Topic queue management
7. Automation - Publish rate, autonomous mode
8. Monitoring - Performance metrics, alerts

**Current Focus:** Configuration and settings management

**Menu Structure:**
- Top-level menu: "PearBlog Engine"
- Single submenu: "Settings"

---

## Proposed v7.0 Architecture

### New Navigation Structure (10 Tabs)

1. **Dashboard** 🔥 - Real-time revenue & performance overview
2. **Strategy (AI)** - AI-driven content strategy control
3. **Content Engine** - Content generation & batch operations
4. **SEO Engine** - SEO automation & optimization
5. **Monetization** 💰 - Revenue tracking & ad management
6. **Leads & Experts** - Lead management & expert routing
7. **Automation** - Queue & scheduling management
8. **Analytics** 📊 - Advanced metrics & filtering
9. **Multisite / SaaS** - Tenant management & branding
10. **Settings** - API keys & system configuration

### Key Paradigm Shifts

| Current (v6.0/v7.0) | Proposed (v7.0 Admin) |
|---------------------|----------------------|
| Settings page | Control center |
| Configuration-focused | Revenue-focused |
| Manual operations | AI-driven decisions |
| Single-site | Multi-tenant ready |
| Static forms | Real-time dashboards |
| Admin manages content | Admin manages revenue system |

---

## Technical Architecture

### New PHP Classes Required

```
mu-plugins/pearblog-engine/src/Admin/
├── AdminPageV7.php              # Main admin controller (new)
├── DashboardTab.php             # Revenue dashboard (new)
├── StrategyTab.php              # AI strategy controls (new)
├── ContentEngineTab.php         # Content operations (new)
├── SEOEngineTab.php             # SEO automation (new)
├── MonetizationTab.php          # Revenue management (enhanced)
├── LeadsExpertsTab.php          # Lead/expert management (new)
├── AutomationTab.php            # Queue & scheduling (enhanced)
├── AnalyticsTab.php             # Advanced analytics (new)
├── MultisiteTab.php             # Tenant management (new)
└── SettingsTab.php              # System settings (refactored)
```

### New Database Tables

```sql
-- Revenue tracking
CREATE TABLE wp_pb_revenue (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id BIGINT UNSIGNED NOT NULL,
  revenue_date DATE NOT NULL,
  revenue_source VARCHAR(50) NOT NULL, -- 'adsense', 'booking', 'airbnb', 'saas_cta'
  revenue_amount DECIMAL(10,2) NOT NULL,
  currency VARCHAR(3) DEFAULT 'USD',
  created_at DATETIME NOT NULL,
  INDEX post_date_idx (post_id, revenue_date),
  INDEX source_idx (revenue_source)
);

-- Lead management
CREATE TABLE wp_pb_leads (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(50),
  category VARCHAR(100),
  status VARCHAR(50) DEFAULT 'new', -- 'new', 'assigned', 'contacted', 'closed'
  assigned_expert_id BIGINT UNSIGNED,
  post_id BIGINT UNSIGNED, -- source post
  created_at DATETIME NOT NULL,
  updated_at DATETIME,
  INDEX status_idx (status),
  INDEX category_idx (category),
  INDEX expert_idx (assigned_expert_id)
);

-- Expert profiles
CREATE TABLE wp_pb_experts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL, -- WordPress user ID
  name VARCHAR(255) NOT NULL,
  categories TEXT, -- JSON array of categories
  subscription_plan VARCHAR(50), -- 'basic', 'pro', 'enterprise'
  performance_score DECIMAL(5,2) DEFAULT 0,
  leads_handled INT UNSIGNED DEFAULT 0,
  leads_closed INT UNSIGNED DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME,
  UNIQUE KEY user_idx (user_id)
);

-- User analytics (already exists, enhance if needed)
-- wp_pb_user_analytics
-- wp_pb_user_metrics

-- A/B tests (already exists, verify structure)
-- wp_pb_ab_tests
```

### REST API Endpoints (New)

```php
// Revenue API
GET    /wp-json/pearblog/v1/revenue/summary
GET    /wp-json/pearblog/v1/revenue/by-post/{post_id}
GET    /wp-json/pearblog/v1/revenue/by-source

// Leads API
GET    /wp-json/pearblog/v1/leads
POST   /wp-json/pearblog/v1/leads
PUT    /wp-json/pearblog/v1/leads/{id}
DELETE /wp-json/pearblog/v1/leads/{id}
POST   /wp-json/pearblog/v1/leads/{id}/assign

// Experts API
GET    /wp-json/pearblog/v1/experts
POST   /wp-json/pearblog/v1/experts
PUT    /wp-json/pearblog/v1/experts/{id}
DELETE /wp-json/pearblog/v1/experts/{id}

// Strategy API
POST   /wp-json/pearblog/v1/strategy/auto-keywords
POST   /wp-json/pearblog/v1/strategy/analyze-intent
GET    /wp-json/pearblog/v1/strategy/recommendations

// Content Engine API
POST   /wp-json/pearblog/v1/content/batch-generate
POST   /wp-json/pearblog/v1/content/rewrite/{post_id}
POST   /wp-json/pearblog/v1/content/apply-template
```

---

## Implementation Phases

### Phase 1: Foundation (v7.1 - June 2026)

**Goal:** Establish new admin architecture without breaking existing functionality

**Tasks:**
1. Create `AdminPageV7.php` alongside existing `AdminPage.php`
2. Implement feature flag: `pearblog_admin_version` (v6/v7)
3. Build tab routing system
4. Migrate existing tabs to new architecture
5. Create database migration script for new tables
6. Update admin CSS for new design language

**Deliverables:**
- ✅ New admin controller with tab system
- ✅ Feature flag for gradual rollout
- ✅ Database schema updates
- ✅ Backward compatibility maintained

**Testing:**
- Switch between v6 and v7 admin without issues
- All existing settings preserved
- No data loss during migration

### Phase 2: Dashboard & Analytics (v7.2 - July 2026)

**Goal:** Implement real-time revenue dashboard and advanced analytics

**Tasks:**
1. Build `DashboardTab.php` with revenue widgets
2. Implement `AnalyticsTab.php` with filtering
3. Create revenue tracking system
4. Integrate with GA4 for traffic data
5. Build Chart.js visualizations
6. Implement CTR, scroll depth, time-on-page tracking

**Deliverables:**
- ✅ Dashboard with revenue metrics
- ✅ Analytics tab with advanced filtering
- ✅ Revenue tracking system operational
- ✅ Real-time performance widgets

**Testing:**
- Revenue data accurately tracked
- Dashboard loads in <2s
- Analytics filters work correctly
- Charts render properly on all devices

### Phase 3: AI Strategy & Content Engine (v7.3 - August 2026)

**Goal:** Implement AI-driven strategy controls and content operations

**Tasks:**
1. Build `StrategyTab.php` for AI controls
2. Implement keyword source management (manual/auto/scraping)
3. Create intent priority system
4. Build `ContentEngineTab.php`
5. Implement batch generation (10-100 articles)
6. Create rewrite/update functionality
7. Build template assignment system

**Deliverables:**
- ✅ AI strategy configuration UI
- ✅ Keyword automation system
- ✅ Batch content generation
- ✅ Content templates system

**Testing:**
- Keyword sources work correctly
- Batch generation handles 100 articles
- Templates apply consistently
- AI decisions logged and auditable

### Phase 4: Leads & Expert Management (v7.4 - September 2026)

**Goal:** Implement lead routing and expert management system

**Tasks:**
1. Build `LeadsExpertsTab.php`
2. Implement lead capture system
3. Create expert profile management
4. Build category-based routing
5. Implement status tracking
6. Create performance metrics
7. Build notification system

**Deliverables:**
- ✅ Lead management interface
- ✅ Expert profiles system
- ✅ Automatic routing engine
- ✅ Performance tracking

**Testing:**
- Leads captured correctly
- Routing algorithm works
- Expert notifications sent
- Status updates persist

### Phase 5: Monetization & Multisite (v7.5 - October 2026)

**Goal:** Complete revenue optimization and multi-tenant features

**Tasks:**
1. Enhance `MonetizationTab.php` with revenue tracking
2. Implement per-post revenue attribution
3. Build `MultisiteTab.php`
4. Create tenant management system
5. Implement white-label branding
6. Build per-site configuration
7. Create tenant isolation

**Deliverables:**
- ✅ Revenue per post tracking
- ✅ Multi-tenant admin panel
- ✅ White-label branding system
- ✅ Tenant configuration UI

**Testing:**
- Revenue attribution accurate
- Tenants properly isolated
- Branding applies correctly
- Performance at scale (100+ tenants)

---

## UI/UX Design Specifications

### Dashboard Widget Layout

```
┌─────────────────────────────────────────────────────┐
│  Revenue Today: $523    |  Revenue MTD: $15,420     │
│  Leads: 23              |  Articles: 145            │
├─────────────────────────────────────────────────────┤
│  Top Performing Articles                            │
│  1. "Best Hotels Prague" - $420/month              │
│  2. "Krakow Travel Guide" - $385/month             │
│  3. "Warsaw Restaurants" - $310/month              │
├─────────────────────────────────────────────────────┤
│  Best Keywords                                      │
│  • "prague hotels" - 1.2K clicks, 8.5% CTR        │
│  • "krakow guide" - 890 clicks, 7.2% CTR          │
├─────────────────────────────────────────────────────┤
│  Conversion Rate: 3.2% ▲ 0.4%                      │
│  Traffic: 45K visitors this month ▲ 12%           │
└─────────────────────────────────────────────────────┘
```

### Color Scheme (v7 Dark Theme)

```css
:root {
  --pb-admin-bg: #0B1118;
  --pb-admin-surface: #111827;
  --pb-admin-primary: #4ADE80;
  --pb-admin-secondary: #60A5FA;
  --pb-admin-text: #ffffff;
  --pb-admin-text-muted: #9CA3AF;
  --pb-admin-border: rgba(255,255,255,0.08);
  --pb-admin-success: #10B981;
  --pb-admin-warning: #F59E0B;
  --pb-admin-error: #EF4444;
}
```

### Tab Design

```html
<div class="pb-admin-v7">
  <nav class="pb-admin-tabs">
    <button class="pb-tab active" data-tab="dashboard">
      <span class="pb-tab-icon">🔥</span>
      <span class="pb-tab-label">Dashboard</span>
    </button>
    <button class="pb-tab" data-tab="strategy">
      <span class="pb-tab-icon">🧠</span>
      <span class="pb-tab-label">Strategy</span>
    </button>
    <!-- ... more tabs ... -->
  </nav>
  <div class="pb-admin-content">
    <div id="tab-dashboard" class="pb-tab-panel active">
      <!-- Dashboard widgets -->
    </div>
  </div>
</div>
```

---

## Migration Strategy

### Backward Compatibility

```php
// Feature flag in wp-config.php
define('PEARBLOG_ADMIN_VERSION', 'v7'); // or 'v6'

// Auto-detect and route
if (defined('PEARBLOG_ADMIN_VERSION') && PEARBLOG_ADMIN_VERSION === 'v7') {
    $admin = new AdminPageV7();
} else {
    $admin = new AdminPage(); // Existing v6 admin
}
$admin->register();
```

### Data Migration

```php
// Run once on activation
function pearblog_migrate_to_v7() {
    global $wpdb;

    // Create new tables
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_revenue);
    dbDelta($sql_leads);
    dbDelta($sql_experts);

    // Migrate existing data
    // - Convert old monetization settings to new format
    // - Populate revenue table from historical data
    // - Create default expert profiles for admins

    update_option('pearblog_admin_v7_migrated', true);
}
```

### Rollback Plan

1. Keep v6 admin code in place
2. Set `PEARBLOG_ADMIN_VERSION` back to 'v6'
3. Database tables persist (no data loss)
4. Full rollback within 5 minutes

---

## Performance Considerations

### Dashboard Loading

- **Target:** <2s page load
- **Strategy:**
  - Cache revenue calculations (5-minute TTL)
  - Lazy load charts
  - Paginate top articles lists
  - Use WordPress transients for expensive queries

### Scalability

- **Target:** Support 100+ tenants
- **Strategy:**
  - Database indexing on all foreign keys
  - Query optimization with EXPLAIN
  - Consider Redis for session management
  - Implement rate limiting on API endpoints

### Browser Compatibility

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

---

## Security Considerations

### Access Control

```php
// Capability checks for each tab
'dashboard'     => 'manage_options',
'strategy'      => 'manage_options',
'content'       => 'edit_posts',
'seo'           => 'manage_options',
'monetization'  => 'manage_options',
'leads'         => 'manage_options',
'automation'    => 'manage_options',
'analytics'     => 'manage_options',
'multisite'     => 'manage_network', // Super admin only
'settings'      => 'manage_options',
```

### Data Protection

- All revenue data encrypted at rest
- Lead information GDPR compliant
- Export/delete capabilities per GDPR
- Audit logging for all data access
- Nonce verification on all actions

---

## Testing Strategy

### Unit Tests

```php
// Test each tab controller
AdminPageV7Test.php
DashboardTabTest.php
StrategyTabTest.php
// ... etc
```

### Integration Tests

- Test tab switching
- Test data flow between tabs
- Test API endpoints
- Test multisite isolation

### UI Tests

- Automated browser tests with Playwright
- Test dashboard widgets load
- Test form submissions
- Test responsive design

### Performance Tests

- Load test dashboard with 1000+ posts
- Stress test batch generation (100 articles)
- Test multisite with 100 tenants

---

## Documentation Requirements

### User Documentation

1. **Admin Panel v7 User Guide** - Complete walkthrough
2. **Dashboard Reference** - Widget descriptions
3. **Strategy Configuration** - AI controls explained
4. **Lead Management Guide** - Workflow documentation
5. **Monetization Setup** - Revenue tracking guide

### Developer Documentation

1. **Admin Panel v7 Architecture** - Technical overview
2. **Tab Development Guide** - How to add new tabs
3. **API Reference** - All new endpoints
4. **Database Schema** - Table structures
5. **Migration Guide** - v6 to v7 upgrade

---

## Success Metrics

### Adoption Metrics

- % of sites using v7 admin
- User satisfaction score
- Time to complete common tasks
- Support ticket volume

### Performance Metrics

- Dashboard load time <2s
- Revenue tracking accuracy 99%+
- Lead routing success rate 95%+
- System uptime 99.9%+

### Business Metrics

- Revenue per site increase
- Lead generation increase
- Content output increase
- Customer retention increase

---

## Risks & Mitigation

### Risk: Complexity Overwhelms Users

**Mitigation:**
- Gradual rollout with feature flag
- Comprehensive onboarding wizard
- Tooltips and contextual help
- Video tutorials for each tab

### Risk: Performance Degradation

**Mitigation:**
- Extensive performance testing
- Query optimization from day one
- Caching strategy implemented
- Monitoring and alerting

### Risk: Data Migration Issues

**Mitigation:**
- Thorough testing on staging
- Backup requirement before migration
- Rollback procedure documented
- Data validation checks

### Risk: Breaking Changes

**Mitigation:**
- Maintain v6 admin in parallel
- Feature flag for safe rollout
- Backward compatibility layer
- Clear deprecation timeline

---

## Timeline & Resources

### Estimated Effort

- **Phase 1 (Foundation):** 4 weeks
- **Phase 2 (Dashboard/Analytics):** 3 weeks
- **Phase 3 (AI/Content):** 4 weeks
- **Phase 4 (Leads/Experts):** 3 weeks
- **Phase 5 (Monetization/Multisite):** 3 weeks

**Total:** ~17 weeks (~4 months)

### Required Resources

- 1 Senior PHP Developer (full-time)
- 1 Frontend Developer (full-time)
- 1 UX/UI Designer (part-time)
- 1 QA Engineer (part-time)

---

## Next Steps

### Immediate Actions (Post v7.0 Launch)

1. ✅ **Approve this implementation plan**
2. ⏳ **Create detailed technical specifications** for Phase 1
3. ⏳ **Design mockups** for all 10 tabs
4. ⏳ **Set up development environment** with feature flag
5. ⏳ **Begin Phase 1 implementation** (Foundation)

### Decision Points

- [ ] Approve overall architecture
- [ ] Approve database schema
- [ ] Approve UI/UX design
- [ ] Set priority for phases
- [ ] Allocate development resources

---

## Conclusion

PearBlog Admin Panel v7.0 transforms the plugin from a content management tool into an **autonomous revenue optimization system**. This is a significant strategic upgrade that positions PearBlog as a SaaS platform rather than just a WordPress plugin.

**Key Value Proposition:**
> "Not managing content. Managing autonomous revenue."

This implementation plan provides a clear roadmap for delivering this vision over 4-5 months with minimal risk and maximum flexibility.

---

**Document Status:** Draft for Review
**Author:** Claude Code Agent
**Review Date:** May 3, 2026
**Target Start:** Post v7.0 Launch (May 10+)

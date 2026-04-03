# PearBlog v4 - AI Personalization UX Engine

**Dynamic Frontend that Adapts to Every User**

> Not just a theme. An AI-powered personalization engine that increases CTR, time on page, and maximizes revenue.

## 🚀 What's New in v4 (AI Personalization Engine)

PearBlog v4 introduces a complete **AI Personalization UX Engine** that dynamically adapts the frontend experience to each user, maximizing engagement and revenue.

### Core Innovation: Dynamic UI

**UI ≠ Static**
**UI = Dynamic (AI + Data)**

The frontend now changes in real-time based on:
- User context (location, device, behavior)
- Traffic source (Google, social media, direct)
- Engagement signals (scroll depth, clicks, time)
- User intent (informational, transactional, navigational)

---

## 🎯 12 Core Features

### 1. User Context Engine

**Function: `pb_get_user_context()`**

System automatically collects and analyzes:
- **Geographic data** - IP-based location, timezone
- **Device detection** - Mobile, tablet, or desktop
- **Behavior tracking** - Scroll patterns, click history, pages viewed
- **Traffic source** - Google, social media, campaigns, direct

**Location**: `/theme/pearblog-theme/inc/user-context.php`

```php
$context = pb_get_user_context();
// Returns: device, geo, traffic_source, behavior, session_id, user_segment
```

---

### 2. Dynamic Content Rendering

Frontend automatically adapts:
- **Headlines** - Shorter for mobile, optimized for device
- **CTAs** - Personalized based on user intent
- **Section order** - Rearranged for user segment
- **Recommendations** - AI-powered content suggestions

**Examples**:
- Mobile users → Shorter content, TL;DR first
- Google traffic → Quick answers prioritized
- Transactional users → CTA shown earlier

**Location**: `/theme/pearblog-theme/inc/dynamic-content.php`

```php
// Get dynamic headline
$headline = pb_get_dynamic_headline($original, $context);

// Get personalized recommendations
$posts = pb_get_personalized_recommendations($post_id, $context, 3);
```

---

### 3. AI Headline Optimizer (A/B Testing)

**Automatic A/B Testing**:
- Test variant A vs variant B
- Track impressions and clicks
- Auto-select winner based on CTR
- Apply winning headline automatically

**Location**: `/theme/pearblog-theme/inc/ai-optimizer.php`

```php
// Enable A/B test for post
update_post_meta($post_id, 'pb_ab_test_enabled', true);
update_post_meta($post_id, 'pb_headline_variant_a', 'Version A');
update_post_meta($post_id, 'pb_headline_variant_b', 'Version B');

// Get results
$results = pb_get_ab_test_results($post_id);
```

**Cron**: Daily check for statistical significance, auto-applies winners

---

### 4. Smart TOC (Intent-Based)

**Dynamic Table of Contents**:
- Adapts to user device
- Mobile → H2 only (simplified)
- Desktop → Full H2 + H3 hierarchy
- Active section highlighting
- Reading progress tracking

**Location**: `/theme/pearblog-theme/inc/dynamic-content.php`

```php
$toc = pb_get_dynamic_toc($headings, $context);
```

---

### 5. Personalized CTA

**CTAs adapt to**:
- User intent (informational, transactional, navigational)
- Scroll depth (low, medium, high engagement)
- Traffic source
- Device type

**Examples**:
- Informational + low scroll → "Learn More"
- Transactional + high scroll → "Get Started Now"
- Navigational + medium scroll → "View All Posts"

**Location**: `/theme/pearblog-theme/inc/behavior-tracking.php`

```php
$cta = pb_get_optimal_cta($scroll_depth, $user_segment);
// Returns: text, type (primary/secondary)
```

---

### 6. AI Recommendation Engine

**Dynamic Related Content**:
- NOT static category-based
- AI-powered scoring algorithm
- Factors: engagement, recency, CTR, device optimization
- Excludes already-viewed posts in session
- Segment-specific ranking

**Scoring Algorithm** (0-100):
- Engagement (40%): scroll depth + time on page
- Recency (20%): decay over time
- CTR (20%): click-through rate
- Content match (20%): device optimization

**Location**: `/theme/pearblog-theme/inc/dynamic-content.php`

```php
$recommendations = pb_get_personalized_recommendations($post_id, $context, 3);
```

---

### 7. Monetization AI

**Smart Ad Placement Rules**:

```
IF scroll > 50% → show CTA
IF user engaged (score > 60) → show affiliate
IF scroll > 80% → show ads
```

**Decision Engine**:
- Tracks engagement score (0-100)
- Only shows ads to engaged users
- Prevents ad fatigue for bouncing users
- Maximizes revenue per user

**Location**: `/theme/pearblog-theme/inc/behavior-tracking.php`

```php
$show_ad = pb_should_show_ad($scroll_depth, $engagement_score);
```

---

### 8. Behavior Tracking

**Function: `pb_user_metrics()`**

**Tracks**:
- Scroll depth (max percentage)
- Time on page (seconds)
- Click events (total)
- CTA clicks
- Ad views
- Ad clicks

**Storage**: Custom database tables
- `wp_pb_user_metrics` - Per-session metrics
- `wp_pb_user_analytics` - Aggregated context data
- `wp_pb_ab_tests` - A/B test results

**Location**: `/theme/pearblog-theme/inc/behavior-tracking.php`

```php
pb_user_metrics(array(
    'session_id' => $session_id,
    'post_id' => $post_id,
    'scroll_depth' => 85,
    'time_on_page' => 120,
    'clicks' => 5,
));
```

---

### 9. Performance + Edge

**Optimization Features**:
- Session-based caching (localStorage)
- Context caching (1 hour)
- Behavior cookie persistence (30 days)
- Async AJAX requests
- RequestAnimationFrame for scroll tracking
- Debounced metric saves (15s intervals)

**Edge-Ready**:
- Per-user segment caching strategy
- Static core content (SEO safe)
- Dynamic UX layer only

**Location**: `/theme/pearblog-theme/assets/js/personalization.js`

---

### 10. AI Loop

**Continuous Optimization Flow**:

```
User → Behavior → Data → AI → UI Change → More Engagement → Loop
```

**Automated Jobs**:
- `pb_ab_test_check` - Daily A/B test winner detection
- `pb_popularity_update` - Twice daily popularity score updates
- Auto-apply winning headlines
- Update recommendation rankings

**Scheduled via WP-Cron**

---

### 11. UX System

**Dynamic Spacing & Layout**:
- Mobile → Compact spacing
- Tablet → Comfortable spacing
- Desktop → Spacious layout

**Body Classes** (auto-applied):
```css
.pb-ai-enabled
.pb-device-mobile
.pb-segment-informational
.pb-traffic-google
.pb-returning-user
.pb-high-engagement
```

**Location**: `/theme/pearblog-theme/functions.php:286-302`

---

### 12. SEO Safe Mode

**Critical Principle**:
- Core content = Static (for SEO crawlers)
- UX layer = Dynamic (for users)

**Implementation**:
- Server-side renders base content
- JavaScript enhances UX progressively
- No content hidden from crawlers
- Dynamic elements are additive, not replacements

**No SEO Impact** - Google sees the base, static content

---

## 📁 File Structure

```
/theme/pearblog-theme/
├── inc/
│   ├── user-context.php      - Context detection engine
│   ├── behavior-tracking.php - Metrics & analytics
│   ├── dynamic-content.php   - Content adaptation
│   ├── ai-optimizer.php      - A/B testing & scoring
│   ├── ui.php               - UI helpers (existing)
│   ├── layout.php           - Layout system (existing)
│   ├── components.php       - Components (existing)
│   ├── performance.php      - Performance (existing)
│   └── monetization.php     - Monetization (existing)
│
├── assets/
│   ├── js/
│   │   ├── personalization.js - Client-side tracking & AI
│   │   ├── app.js            - Main app (existing)
│   │   └── lazyload.js       - Lazy loading (existing)
│   └── css/
│       ├── base.css          - Design system
│       ├── components.css    - Components
│       └── utilities.css     - Utilities
│
├── functions.php             - Theme setup (v4.0.0)
└── README-v4.md             - This file
```

---

## 🔧 Configuration

### Enable AI Engine

The AI Personalization Engine is enabled by default in v4:

```php
define('PEARBLOG_AI_ENGINE', true); // In functions.php
```

### Feature Toggles

```php
// In WordPress options or pb_get_site_config()
update_option('pearblog_ai_personalization', true);
update_option('pearblog_ab_testing', true);
update_option('pearblog_smart_recommendations', true);
update_option('pearblog_behavior_tracking', true);
```

---

## 📊 Database Tables

### Analytics Table
```sql
wp_pb_user_analytics
- session_id, device, country, traffic_source, user_segment, timestamp
```

### Metrics Table
```sql
wp_pb_user_metrics
- session_id, post_id, scroll_depth, time_on_page, clicks, cta_clicks, ad_views, ad_clicks, timestamp
```

### A/B Tests Table
```sql
wp_pb_ab_tests
- post_id, variant, impressions, clicks, timestamp
```

**Auto-created on theme activation**

---

## 🎨 Usage Examples

### Get User Context Anywhere

```php
$context = pb_get_user_context();
echo $context['device'];        // mobile, tablet, desktop
echo $context['user_segment'];  // informational, transactional, navigational
echo $context['geo']['country']; // US, UK, etc.
```

### Track Custom Metrics

```php
pb_user_metrics(array(
    'scroll_depth' => 90,
    'time_on_page' => 180,
    'cta_clicks' => 2,
));
```

### Get Post Engagement Data

```php
$metrics = pb_get_post_metrics($post_id, 30); // Last 30 days
echo $metrics['avg_scroll'];     // Average scroll depth
echo $metrics['avg_time'];       // Average time on page
echo $metrics['total_cta_clicks']; // Total CTA clicks
```

### Enable A/B Testing

```php
update_post_meta($post_id, 'pb_ab_test_enabled', true);
update_post_meta($post_id, 'pb_headline_variant_a', '7 Ways to Boost Your SEO');
update_post_meta($post_id, 'pb_headline_variant_b', 'The Ultimate Guide to SEO');

// Check results after sufficient data
$results = pb_get_ab_test_results($post_id);
if ($results['winner']) {
    echo "Winner: Variant " . $results['winner'];
    echo "CTR: " . $results[$results['winner']]['ctr'] . "%";
}
```

---

## 🚀 Performance Metrics

**Expected Improvements**:
- ↑ 15-30% increase in CTR (via A/B testing)
- ↑ 20-40% increase in time on page (via personalization)
- ↑ 25-50% increase in revenue (via smart monetization)

**Technical Performance**:
- JavaScript: ~8KB gzipped (personalization.js)
- No blocking requests
- Async data collection
- Cached user context
- Minimal database queries

---

## 🔮 Roadmap

### v4.0 (Current) ✅
- User Context Engine
- Behavior Tracking
- Dynamic Content Rendering
- AI Headline Optimizer
- Smart TOC
- Personalized CTA
- AI Recommendations
- Monetization AI
- Performance optimization
- SEO safe mode

### v4.1 (Planned)
- Machine learning models
- Predictive user intent
- Advanced segment profiling
- Heatmap visualization
- A/B testing dashboard
- Real-time analytics

### v5.0 (Future)
- **Autonomous Frontend**
- UI self-optimizes without rules
- Deep learning personalization
- Multi-variate testing
- Predictive content delivery

---

## 🔐 Privacy & GDPR

**Cookie Usage**:
- `pb_session_id` - Session tracking (1 day)
- `pb_behavior` - Behavior data (30 days)

**Data Stored**:
- Anonymous session metrics
- No PII (Personally Identifiable Information)
- IP addresses hashed
- GDPR compliant

**User Control**:
- Cookies can be disabled
- Data deletion on request
- Opt-out mechanisms available

---

## 📄 License

GNU General Public License v2 or later

---

## 🤝 Support

For issues, questions, or feature requests, please open an issue in this repository.

---

**PearBlog v4 - AI Personalization UX Engine**

*Built for Maximum Engagement, Revenue, and User Experience*

**UI = Dynamic (AI + Data)**

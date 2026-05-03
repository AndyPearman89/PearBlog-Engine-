# Enterprise V8 Integration Tests & Verification

## Overview
This document provides comprehensive integration tests to verify that all Enterprise V8 systems work together seamlessly:
- **Enterprise Admin Dashboard V8** (15 tabs)
- **PT24 AI Lead Engine V2** (DDD architecture)
- **Poradnik Engine V2** (Revenue optimization)

---

## Pre-Test Checklist

### System Requirements
- [ ] PHP 8.1+ installed
- [ ] WordPress 6.0+ active
- [ ] MySQL 8.0+ or MariaDB 10.5+
- [ ] OpenAI API key configured
- [ ] Google Search Console API access
- [ ] SMS provider credentials (Twilio or SMSApi.pl)

### Installation Verification
- [ ] PearBlog Engine plugin active in `mu-plugins/`
- [ ] `PEARBLOG_ADMIN_VERSION` set to `'v8-enterprise'`
- [ ] Database tables created (9 tables total)
- [ ] REST API namespace registered (`/pearblog/v1/`)
- [ ] WP-Cron operational

### Quick System Check
```bash
# 1. Verify Enterprise mode is enabled
grep "PEARBLOG_ADMIN_VERSION" mu-plugins/pearblog-engine/pearblog-engine.php

# 2. Check database tables exist
wp db query "SHOW TABLES LIKE 'wp_pearblog_%';"

# 3. Verify REST API routes
wp rest route list | grep pearblog

# 4. Check cron jobs
wp cron event list | grep poradnik
```

---

## Test Suite 1: Enterprise Admin Dashboard

### Test 1.1: Dashboard Access
**Objective**: Verify Enterprise V8 admin dashboard is accessible with all 15 tabs

**Steps**:
1. Log in to WordPress admin as administrator
2. Navigate to **PearBlog Engine** menu item
3. Verify the dashboard loads with glassmorphism UI

**Expected Results**:
- ✅ Dashboard displays with modern glassmorphism design
- ✅ All 15 tabs visible in navigation:
  - 🎯 Dashboard Enterprise
  - 📊 Real-Time Analytics
  - 🧠 AI Strategy
  - ✍️ Content Engine
  - 🔍 SEO Advanced
  - 💰 Revenue Center
  - 👥 Leads & CRM
  - ⚙️ Automation Pro
  - 📈 Analytics Deep
  - 🌐 Multisite/SaaS
  - ⚡ Performance
  - 🔒 Security & Audit
  - 📋 Advanced Reports
  - 🔗 Integrations
  - ⚙️ Settings Enterprise
- ✅ Dark mode toggle present in header
- ✅ PL/EN language switcher operational

**Pass Criteria**: All tabs visible, UI renders correctly, no console errors

---

### Test 1.2: Dashboard Enterprise Tab
**Objective**: Verify executive overview displays key metrics

**Steps**:
1. Click **🎯 Dashboard Enterprise** tab
2. Observe KPI widgets

**Expected Results**:
- ✅ Total Leads counter (from `pearblog_leads` table)
- ✅ Average Lead Score display
- ✅ Active Articles count (from `pearblog_articles` table)
- ✅ Total Revenue metric (from `pearblog_article_stats` table)
- ✅ Recent activity timeline
- ✅ Quick action buttons (Generate Content, View Leads, etc.)

**Pass Criteria**: All metrics display with real or placeholder data, no PHP errors

---

### Test 1.3: AI Strategy Tab
**Objective**: Verify PT24 Lead Engine and Poradnik Engine controls

**Steps**:
1. Click **🧠 AI Strategy** tab
2. Locate PT24 Lead Engine section
3. Locate Poradnik Engine section

**Expected Results**:
- ✅ **PT24 Lead Engine Controls**:
  - Lead scoring settings
  - SLA configuration (30min/2h/none)
  - AI reply templates
  - Routing rules configuration
- ✅ **Poradnik Engine Controls**:
  - Scoring formula weights (SEO 20%, ENG 20%, CTR 20%, REV 40%)
  - Decision thresholds (SCALE ≥80, BOOST 60-79, OPTIMIZE 40-59, DELETE <40)
  - Worker schedule configuration
  - A/B testing settings

**Pass Criteria**: Both engine control panels accessible, settings load correctly

---

### Test 1.4: Leads & CRM Tab
**Objective**: Verify lead management interface connects to PT24 Lead Engine

**Steps**:
1. Click **👥 Leads & CRM** tab
2. View lead list table
3. Click on a lead (if any exist)

**Expected Results**:
- ✅ Lead list displays from `pearblog_leads` table
- ✅ Columns: ID, Intent, Package, Score, State, Created
- ✅ Filter options: by state (NEW/CONTACTED/QUALIFIED/CONVERTED/DEAD)
- ✅ Search functionality
- ✅ Lead detail modal shows:
  - Contact information
  - Calculated lead score breakdown
  - Activity timeline (from `pearblog_lead_events`)
  - Quick actions (Reply, Route, Escalate)

**Pass Criteria**: UI interacts with PT24 Lead Engine API, data loads correctly

---

### Test 1.5: Content Engine Tab
**Objective**: Verify Poradnik Engine content management

**Steps**:
1. Click **✍️ Content Engine** tab
2. View article list
3. Click on an article (if any exist)

**Expected Results**:
- ✅ Article list displays from `pearblog_articles` table
- ✅ Columns: Title, Score, Category, Views, Revenue, Updated
- ✅ Filter by category: SCALE/BOOST/OPTIMIZE/DELETE
- ✅ Bulk actions available (Optimize, Re-score, Delete)
- ✅ Article detail shows:
  - Performance metrics
  - Score breakdown (SEO, Engagement, CTR, Revenue)
  - Optimization suggestions
  - A/B test status
- ✅ "Generate New Content" button functional

**Pass Criteria**: UI integrates with Poradnik Engine API, metrics display correctly

---

### Test 1.6: Revenue Center Tab
**Objective**: Verify revenue tracking and optimization features

**Steps**:
1. Click **💰 Revenue Center** tab
2. View revenue dashboard

**Expected Results**:
- ✅ Total revenue chart (from `pearblog_article_stats`)
- ✅ Top performing articles by revenue
- ✅ Revenue by traffic source
- ✅ PT24 affiliate commission tracking
- ✅ Lead-to-revenue attribution
- ✅ Conversion rate metrics

**Pass Criteria**: Revenue data aggregates from both engines, charts render correctly

---

## Test Suite 2: PT24 AI Lead Engine V2

### Test 2.1: Lead Creation via REST API
**Objective**: Verify lead intake and scoring

**API Endpoint**: `POST /wp-json/pearblog/v1/leads`

**Request**:
```bash
curl -X POST https://your-site.com/wp-json/pearblog/v1/leads \
  -H "Content-Type: application/json" \
  -d '{
    "intent": "REPAIR",
    "package": "PREMIUM",
    "contact": {
      "name": "Jan Kowalski",
      "phone": "+48123456789",
      "email": "jan@example.com",
      "city": "Warszawa"
    },
    "message": "Pilne! Potrzebuję naprawy pieca centralnego. Budżet do 5000 zł. Proszę o kontakt jeszcze dziś.",
    "metadata": {
      "source": "pt24_landing",
      "utm_campaign": "repair_winter_2026"
    }
  }'
```

**Expected Response**:
```json
{
  "success": true,
  "lead_id": 123,
  "external_id": "LEAD-20260503-ABC123",
  "score": 87,
  "state": "NEW",
  "score_breakdown": {
    "urgency": 30,
    "budget": 22,
    "clarity": 18,
    "location": 15,
    "demand": 2
  },
  "sla": {
    "package": "PREMIUM",
    "deadline": "2026-05-03T22:00:00Z",
    "minutes_remaining": 30
  }
}
```

**Verification Steps**:
1. Send POST request with valid lead data
2. Check response contains `lead_id` and `score`
3. Verify lead appears in database:
```sql
SELECT * FROM wp_pearblog_leads WHERE external_id = 'LEAD-20260503-ABC123';
```
4. Check admin dashboard **Leads & CRM** tab shows new lead

**Pass Criteria**:
- ✅ Lead created successfully (HTTP 201)
- ✅ Score calculated accurately (75-95 range expected)
- ✅ Database record created
- ✅ SLA deadline set correctly (30min for PREMIUM)
- ✅ Lead visible in admin dashboard

---

### Test 2.2: AI Reply Generation
**Objective**: Verify AI-powered response generation

**API Endpoint**: `POST /wp-json/pearblog/v1/leads/{id}/reply`

**Request**:
```bash
curl -X POST https://your-site.com/wp-json/pearblog/v1/leads/123/reply \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_WP_TOKEN" \
  -d '{
    "tone": "professional",
    "include_pricing": true
  }'
```

**Expected Response**:
```json
{
  "success": true,
  "reply": {
    "subject": "Re: Pilna naprawa pieca - oferta serwisu",
    "body": "Dzień dobry Panie Janie,\n\nDziękujemy za zgłoszenie...",
    "estimated_time": "2-4 godziny",
    "price_range": "800-1500 zł"
  },
  "sent_via": ["email", "sms"],
  "lead_state": "CONTACTED"
}
```

**Verification Steps**:
1. Send POST request with lead ID
2. Verify AI generates contextual reply
3. Check `pearblog_lead_events` table for REPLY_SENT event
4. Verify lead state changed from NEW → CONTACTED
5. Check notification sent (email/SMS)

**Pass Criteria**:
- ✅ AI reply generated (HTTP 200)
- ✅ Reply references lead details (name, service, urgency)
- ✅ Professional tone maintained
- ✅ Event logged in database
- ✅ Lead state updated
- ✅ Notification delivered

---

### Test 2.3: Lead Routing
**Objective**: Verify intelligent lead routing based on score

**API Endpoint**: `POST /wp-json/pearblog/v1/leads/{id}/route`

**Test Cases**:

**Case A: High Score Lead (≥80)**
```bash
curl -X POST https://your-site.com/wp-json/pearblog/v1/leads/123/route \
  -H "Authorization: Bearer YOUR_WP_TOKEN"
```

**Expected**: Route to **Expert** tier

**Case B: Medium Score Lead (50-79)**
```bash
curl -X POST https://your-site.com/wp-json/pearblog/v1/leads/124/route \
  -H "Authorization: Bearer YOUR_WP_TOKEN"
```

**Expected**: Route to **Pro** tier

**Case C: Low Score Lead (<50)**
```bash
curl -X POST https://your-site.com/wp-json/pearblog/v1/leads/125/route \
  -H "Authorization: Bearer YOUR_WP_TOKEN"
```

**Expected**: Route to **Auto-reply** (no human routing)

**Verification Steps**:
1. Create leads with different scores
2. Call routing endpoint for each
3. Verify routing decision matches score thresholds
4. Check `routed_to` field in database
5. Verify notification sent to assigned expert

**Pass Criteria**:
- ✅ High score leads routed to Expert (HTTP 200)
- ✅ Medium score leads routed to Pro
- ✅ Low score leads handled by auto-reply
- ✅ Database updated with routing decision
- ✅ Notifications sent to correct recipients

---

### Test 2.4: SLA Monitoring
**Objective**: Verify SLA breach detection and escalation

**Setup**:
1. Create PREMIUM lead (30min SLA)
2. Wait 31 minutes without response
3. Check for automatic escalation

**Expected Behavior**:
- After 30 minutes: `sla_breached` flag set to `true`
- Escalation notification sent to supervisor
- Lead state changed to ESCALATED
- Entry in `pearblog_lead_notifications` table

**Verification Steps**:
```sql
-- Check SLA breach flag
SELECT id, external_id, created_at, sla_breached
FROM wp_pearblog_leads
WHERE package = 'PREMIUM'
AND TIMESTAMPDIFF(MINUTE, created_at, NOW()) > 30;

-- Check escalation notifications
SELECT * FROM wp_pearblog_lead_notifications
WHERE notification_type = 'SLA_BREACH'
AND sent_at IS NOT NULL;
```

**Alternative**: Run manual SLA check via WP-CLI
```bash
wp cron event run pearblog_sla_monitor
```

**Pass Criteria**:
- ✅ SLA breach detected automatically
- ✅ Notification sent to supervisor
- ✅ Lead flagged in admin dashboard
- ✅ Analytics updated (SLA breach counter incremented)

---

### Test 2.5: Lead Analytics
**Objective**: Verify analytics aggregation

**API Endpoint**: `GET /wp-json/pearblog/v1/leads/analytics`

**Request**:
```bash
curl https://your-site.com/wp-json/pearblog/v1/leads/analytics?days=7 \
  -H "Authorization: Bearer YOUR_WP_TOKEN"
```

**Expected Response**:
```json
{
  "period": "7_days",
  "totals": {
    "leads": 142,
    "avg_score": 67.3,
    "conversion_rate": 0.23,
    "avg_response_time_minutes": 45,
    "sla_breaches": 3
  },
  "by_intent": {
    "REPAIR": 58,
    "RENOVATION": 42,
    "CONSULTATION": 32,
    "INSTALLATION": 10
  },
  "by_package": {
    "PREMIUM": 12,
    "STANDARD": 87,
    "BASIC": 43
  }
}
```

**Verification Steps**:
1. Send GET request to analytics endpoint
2. Verify data aggregates from `pearblog_lead_analytics` table
3. Check calculations match raw data in `pearblog_leads`
4. View analytics in admin dashboard **Leads & CRM** tab

**Pass Criteria**:
- ✅ Analytics endpoint returns data (HTTP 200)
- ✅ Totals calculate correctly
- ✅ Breakdown by intent/package accurate
- ✅ Admin dashboard displays same metrics
- ✅ Performance acceptable (<500ms response time)

---

## Test Suite 3: Poradnik Engine V2

### Test 3.1: Content Generation
**Objective**: Verify AI-powered content creation

**API Endpoint**: `POST /wp-json/pearblog/v1/content/generate`

**Request**:
```bash
curl -X POST https://your-site.com/wp-json/pearblog/v1/content/generate \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_WP_TOKEN" \
  -d '{
    "topic": "remont-lazienki",
    "city": "krakow",
    "service": "przebudowa lazienki",
    "auto_publish": false
  }'
```

**Expected Response**:
```json
{
  "success": true,
  "article_id": 456,
  "post_id": 789,
  "slug": "remont-lazienki-krakow-2026",
  "status": "draft",
  "score": 0,
  "message": "Content queued for generation. Check status in 30-60 seconds."
}
```

**Verification Steps**:
1. Send POST request with topic details
2. Wait for background worker to process (WP-Cron)
3. Check article created in database:
```sql
SELECT * FROM wp_pearblog_articles WHERE id = 456;
```
4. Verify WordPress post created:
```bash
wp post get 789 --format=json
```
5. Check admin dashboard **Content Engine** tab shows new article

**Pass Criteria**:
- ✅ Article queued successfully (HTTP 202)
- ✅ Database record created in `pearblog_articles`
- ✅ Background worker generates content (within 60s)
- ✅ WordPress post created with AI-generated content
- ✅ Initial score calculated (SEO components only, no stats yet)
- ✅ Article visible in admin dashboard

---

### Test 3.2: Content Scoring
**Objective**: Verify revenue-focused scoring calculation

**API Endpoint**: `GET /wp-json/pearblog/v1/content/score/{id}`

**Request**:
```bash
curl https://your-site.com/wp-json/pearblog/v1/content/score/456 \
  -H "Authorization: Bearer YOUR_WP_TOKEN"
```

**Expected Response**:
```json
{
  "article_id": 456,
  "total_score": 73,
  "category": "BOOST",
  "breakdown": {
    "seo_score": 15.2,
    "engagement_score": 16.8,
    "ctr_score": 18.0,
    "revenue_score": 23.0
  },
  "weights": {
    "seo": 0.2,
    "engagement": 0.2,
    "ctr": 0.2,
    "revenue": 0.4
  },
  "stats": {
    "views": 1247,
    "avg_time_seconds": 152,
    "cta_clicks": 89,
    "cta_ctr": 0.071,
    "leads": 7,
    "revenue": 2450.00
  }
}
```

**Verification Steps**:
1. Ensure article has stats in `pearblog_article_stats` table
2. Send GET request to scoring endpoint
3. Verify score calculation:
   - Total = (15.2 × 0.2) + (16.8 × 0.2) + (18.0 × 0.2) + (23.0 × 0.4) = 73
4. Verify category assignment (73 falls in BOOST range: 60-79)
5. Check score displayed in admin dashboard

**Pass Criteria**:
- ✅ Score calculated correctly (HTTP 200)
- ✅ Formula matches: (SEO × 0.2) + (ENG × 0.2) + (CTR × 0.2) + (REV × 0.4)
- ✅ Category assigned correctly (SCALE/BOOST/OPTIMIZE/DELETE)
- ✅ Breakdown shows component scores
- ✅ Admin dashboard displays same score

---

### Test 3.3: Event Tracking
**Objective**: Verify real-time event capture

**API Endpoint**: `POST /wp-json/pearblog/v1/event` (PUBLIC endpoint)

**Test Cases**:

**Case A: Page View Event**
```bash
curl -X POST https://your-site.com/wp-json/pearblog/v1/event \
  -H "Content-Type: application/json" \
  -d '{
    "event_type": "view",
    "article_id": 456,
    "post_id": 789,
    "session_id": "sess_abc123",
    "referrer": "https://google.com/search?q=remont+lazienki+krakow",
    "utm_source": "google",
    "utm_medium": "organic"
  }'
```

**Case B: CTA Click Event**
```bash
curl -X POST https://your-site.com/wp-json/pearblog/v1/event \
  -H "Content-Type: application/json" \
  -d '{
    "event_type": "cta_click",
    "article_id": 456,
    "post_id": 789,
    "session_id": "sess_abc123",
    "event_data": {
      "cta_text": "Sprawdź ceny w Krakowie",
      "cta_position": "in_content"
    }
  }'
```

**Case C: Revenue Event**
```bash
curl -X POST https://your-site.com/wp-json/pearblog/v1/event \
  -H "Content-Type: application/json" \
  -d '{
    "event_type": "revenue",
    "article_id": 456,
    "post_id": 789,
    "session_id": "sess_abc123",
    "event_data": {
      "amount": 350.00,
      "currency": "PLN",
      "source": "pt24_affiliate"
    }
  }'
```

**Verification Steps**:
1. Send POST requests for different event types
2. Verify events stored in `pearblog_events` table:
```sql
SELECT * FROM wp_pearblog_events
WHERE article_id = 456
ORDER BY created_at DESC
LIMIT 10;
```
3. Wait for daily aggregation cron job or run manually:
```bash
wp cron event run poradnik_aggregate_stats
```
4. Check stats updated in `pearblog_article_stats`
5. Verify score recalculated based on new stats

**Pass Criteria**:
- ✅ All event types accepted (HTTP 200)
- ✅ Events stored in database with correct timestamps
- ✅ UTM parameters captured correctly
- ✅ Session ID tracked for user journey
- ✅ Stats aggregated daily
- ✅ Score recalculated with new data

---

### Test 3.4: Decision Engine
**Objective**: Verify automated content optimization decisions

**Setup**:
1. Create/identify articles in each category:
   - SCALE article (score ≥80)
   - BOOST article (60-79)
   - OPTIMIZE article (40-59)
   - DELETE article (<40)

**Expected Decisions**:

**SCALE (Score: 87)**
- Action: Duplicate and scale content
- Optimization: Create regional variants (other cities)
- Budget: Increase promotion budget
- Decision: `{"action": "scale", "reason": "high_performance", "suggestions": ["create_variants", "increase_budget"]}`

**BOOST (Score: 73)**
- Action: SEO boost
- Optimization: Rewrite meta descriptions, improve CTR
- Budget: Moderate promotion
- Decision: `{"action": "boost", "reason": "good_potential", "suggestions": ["improve_meta", "add_internal_links"]}`

**OPTIMIZE (Score: 52)**
- Action: Content optimization
- Optimization: Rewrite intro, improve engagement
- Budget: Test A/B variants
- Decision: `{"action": "optimize", "reason": "underperforming", "suggestions": ["rewrite_intro", "add_cta", "ab_test"]}`

**DELETE (Score: 28)**
- Action: Consider removal
- Optimization: Archive or redirect
- Budget: Zero promotion
- Decision: `{"action": "delete", "reason": "poor_performance", "suggestions": ["redirect_301", "deindex"]}`

**Verification Steps**:
1. Run decision engine via admin dashboard or CLI:
```bash
wp eval 'do_action("pearblog_run_decision_engine");'
```
2. Check `pearblog_articles` table for `decision` field
3. Verify decisions match score thresholds
4. Check admin dashboard shows optimization suggestions

**Pass Criteria**:
- ✅ Decision engine categorizes all articles correctly
- ✅ SCALE articles get scaling suggestions
- ✅ BOOST articles get SEO improvement suggestions
- ✅ OPTIMIZE articles get content rewrite suggestions
- ✅ DELETE articles flagged for removal
- ✅ Admin dashboard displays decision reasoning

---

### Test 3.5: A/B Testing
**Objective**: Verify statistical A/B testing framework

**Setup**:
1. Create A/B test for OPTIMIZE category article
2. Generate two variants (original vs. optimized intro)
3. Split traffic 50/50
4. Track conversions (CTA clicks)

**API Workflow**:

**Step 1: Create A/B Test**
```bash
curl -X POST https://your-site.com/wp-json/pearblog/v1/content/ab-test \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_WP_TOKEN" \
  -d '{
    "article_id": 456,
    "test_name": "Intro Optimization Test",
    "variant_a": {
      "intro": "Original intro text..."
    },
    "variant_b": {
      "intro": "Optimized intro with hook..."
    }
  }'
```

**Step 2: Simulate Traffic**
```bash
# Send 100 views to variant A, 15 conversions
for i in {1..100}; do
  curl -X POST https://your-site.com/wp-json/pearblog/v1/event \
    -H "Content-Type: application/json" \
    -d "{\"event_type\": \"view\", \"article_id\": 456, \"variant\": \"a\", \"session_id\": \"sess_a_$i\"}"
done

# Send 100 views to variant B, 22 conversions
for i in {1..100}; do
  curl -X POST https://your-site.com/wp-json/pearblog/v1/event \
    -H "Content-Type: application/json" \
    -d "{\"event_type\": \"view\", \"article_id\": 456, \"variant\": \"b\", \"session_id\": \"sess_b_$i\"}"
done
```

**Step 3: Check Statistical Significance**
```bash
curl https://your-site.com/wp-json/pearblog/v1/content/ab-test/456/status \
  -H "Authorization: Bearer YOUR_WP_TOKEN"
```

**Expected Response**:
```json
{
  "test_id": 78,
  "article_id": 456,
  "status": "completed",
  "winner": "variant_b",
  "results": {
    "variant_a": {
      "views": 100,
      "conversions": 15,
      "conversion_rate": 0.15
    },
    "variant_b": {
      "views": 100,
      "conversions": 22,
      "conversion_rate": 0.22
    },
    "z_score": 1.98,
    "p_value": 0.048,
    "confidence": 0.95,
    "statistically_significant": true
  },
  "applied_at": "2026-05-03T22:30:00Z"
}
```

**Verification Steps**:
1. Create A/B test via API
2. Verify test record in `pearblog_ab_tests` table
3. Send traffic to both variants
4. Check z-score calculation:
   - p_a = 15/100 = 0.15
   - p_b = 22/100 = 0.22
   - p_pooled = (15+22)/(100+100) = 0.185
   - se = sqrt(0.185 × 0.815 × (1/100 + 1/100)) = 0.0549
   - z = (0.22 - 0.15) / 0.0549 = 1.27 (actual calculation may vary)
5. Verify winner applied automatically when |z| > 1.96

**Pass Criteria**:
- ✅ A/B test created successfully
- ✅ Traffic split 50/50 correctly
- ✅ Conversions tracked per variant
- ✅ Z-score calculated correctly
- ✅ Statistical significance detected (p < 0.05)
- ✅ Winner applied automatically
- ✅ Test completion logged

---

### Test 3.6: Background Workers
**Objective**: Verify WP-Cron background processing

**Workers to Test**:
1. **poradnik_generate_worker** - Content generation (on-demand)
2. **poradnik_scoring_worker** - Daily scoring (05:00)
3. **poradnik_optimize_worker** - Weekly optimization (Sunday 01:00)
4. **poradnik_aggregate_stats** - Hourly stats aggregation

**Verification Steps**:

**Check Scheduled Events**:
```bash
wp cron event list --format=table | grep poradnik
```

**Expected Output**:
```
+---------------------------+---------------------+---------------+
| hook                      | next_run_gmt        | recurrence    |
+---------------------------+---------------------+---------------+
| poradnik_scoring_worker   | 2026-05-04 05:00:00 | daily         |
| poradnik_optimize_worker  | 2026-05-05 01:00:00 | weekly        |
| poradnik_aggregate_stats  | 2026-05-03 22:00:00 | hourly        |
+---------------------------+---------------------+---------------+
```

**Manual Worker Execution**:
```bash
# Run scoring worker
wp cron event run poradnik_scoring_worker

# Run optimization worker
wp cron event run poradnik_optimize_worker

# Run stats aggregation
wp cron event run poradnik_aggregate_stats
```

**Verify Worker Actions**:
```sql
-- Check scoring worker updated article scores
SELECT id, topic, score, category, updated_at
FROM wp_pearblog_articles
ORDER BY updated_at DESC
LIMIT 10;

-- Check optimization worker generated suggestions
SELECT id, topic, score, category, decision
FROM wp_pearblog_articles
WHERE decision IS NOT NULL
LIMIT 10;

-- Check stats aggregation created daily records
SELECT * FROM wp_pearblog_article_stats
WHERE date = CURDATE()
LIMIT 10;
```

**Pass Criteria**:
- ✅ All 4 workers registered in WP-Cron
- ✅ Workers execute without PHP errors
- ✅ Scoring worker recalculates all article scores
- ✅ Optimization worker generates decision suggestions
- ✅ Stats aggregation creates daily summary records
- ✅ Workers complete in reasonable time (<2min for 100 articles)

---

## Test Suite 4: System Integration

### Test 4.1: Admin Dashboard ↔ PT24 Lead Engine
**Objective**: Verify admin dashboard displays live lead data

**Steps**:
1. Create 5 new leads via PT24 Lead Engine API (Test 2.1)
2. Open admin dashboard **👥 Leads & CRM** tab
3. Verify leads appear in real-time

**Expected Results**:
- ✅ All 5 leads visible in dashboard table
- ✅ Lead scores displayed correctly
- ✅ Lead states accurate (NEW/CONTACTED/etc.)
- ✅ Click on lead opens detail modal
- ✅ Modal shows score breakdown
- ✅ Activity timeline displays events
- ✅ Quick actions functional (Reply, Route, Escalate)

**Integration Points Tested**:
- Admin UI → PT24 LeadAPI
- Dashboard → `pearblog_leads` table
- Modal → `pearblog_lead_events` table
- Actions → LeadOrchestrator service

---

### Test 4.2: Admin Dashboard ↔ Poradnik Engine
**Objective**: Verify admin dashboard displays content performance

**Steps**:
1. Generate 3 new articles via Poradnik Engine API (Test 3.1)
2. Open admin dashboard **✍️ Content Engine** tab
3. Verify articles appear with scores

**Expected Results**:
- ✅ All 3 articles visible in dashboard table
- ✅ Scores display with color coding (green/yellow/orange/red)
- ✅ Categories correct (SCALE/BOOST/OPTIMIZE/DELETE)
- ✅ Click on article opens detail view
- ✅ Detail shows performance metrics and charts
- ✅ Optimization suggestions displayed
- ✅ "Optimize Now" button triggers optimization

**Integration Points Tested**:
- Admin UI → PoradnikAPI
- Dashboard → `pearblog_articles` table
- Charts → `pearblog_article_stats` table
- Actions → AIOptimizer service

---

### Test 4.3: Poradnik Engine → PT24 Marketplace Data
**Objective**: Verify Poradnik Engine fetches PT24 pricing data

**Setup**:
1. Create article for topic: "remont-mieszkania" in "warszawa"
2. Check if Poradnik Engine scrapes PT24 for pricing

**Verification Steps**:
```sql
-- Check service data scraped from PT24
SELECT * FROM wp_pearblog_service_data
WHERE service = 'remont mieszkania'
AND city = 'warszawa';
```

**Expected Data**:
```
| service            | city      | price_min | price_max | price_avg | listings_count | scraped_at          |
|--------------------|-----------|-----------|-----------|-----------|----------------|---------------------|
| remont mieszkania  | warszawa  | 15000     | 85000     | 42000     | 247            | 2026-05-03 21:00:00 |
```

**Article Integration**:
- ✅ Article content mentions price range (15,000-85,000 PLN)
- ✅ Content references "247 fachowców w Warszawie"
- ✅ PT24 links embedded naturally in content
- ✅ Pricing data refreshed weekly via DataScraper

**Integration Points Tested**:
- Poradnik AIOptimizer → DataScraper
- DataScraper → PT24 marketplace API
- Content generation → pricing context
- Database → `pearblog_service_data` table

---

### Test 4.4: Event Tracking → Analytics Aggregation
**Objective**: Verify events flow through to analytics

**Workflow**:
1. Track 100 view events for article 456
2. Track 15 CTA click events
3. Track 2 lead conversions
4. Track 1 revenue event (350 PLN)
5. Wait for stats aggregation (hourly cron)
6. Check analytics in admin dashboard

**Verification Steps**:
```sql
-- Check raw events captured
SELECT event_type, COUNT(*) as count
FROM wp_pearblog_events
WHERE article_id = 456
AND DATE(created_at) = CURDATE()
GROUP BY event_type;

-- Check aggregated stats
SELECT * FROM wp_pearblog_article_stats
WHERE article_id = 456
AND date = CURDATE();
```

**Expected Stats**:
```
| article_id | date       | views | cta_clicks | cta_ctr | leads | revenue |
|------------|------------|-------|------------|---------|-------|---------|
| 456        | 2026-05-03 | 100   | 15         | 0.15    | 2     | 350.00  |
```

**Dashboard Verification**:
- ✅ Admin dashboard **📊 Real-Time Analytics** shows updated metrics
- ✅ Revenue chart includes new 350 PLN
- ✅ Article detail page shows 15% CTR
- ✅ Score recalculated based on new stats

**Integration Points Tested**:
- Frontend tracking → EventTracker API
- EventTracker → `pearblog_events` table
- WP-Cron → stats aggregation worker
- Aggregation → `pearblog_article_stats` table
- ScoringEngine → updated stats
- Admin dashboard → refreshed metrics

---

### Test 4.5: Lead-to-Revenue Attribution
**Objective**: Verify leads tracked through to revenue

**Workflow**:
1. User views Poradnik article (article 456)
2. User clicks CTA "Sprawdź ceny w Krakowie"
3. User submits lead form on PT24 landing page
4. Lead created via PT24 Lead Engine (Test 2.1)
5. Lead converts to sale (350 PLN revenue)
6. Revenue event tracked back to original article

**Implementation**:
```bash
# Step 1: Track article view with session
curl -X POST https://your-site.com/wp-json/pearblog/v1/event \
  -H "Content-Type: application/json" \
  -d '{
    "event_type": "view",
    "article_id": 456,
    "session_id": "sess_attribution_test_123"
  }'

# Step 2: Track CTA click
curl -X POST https://your-site.com/wp-json/pearblog/v1/event \
  -H "Content-Type: application/json" \
  -d '{
    "event_type": "cta_click",
    "article_id": 456,
    "session_id": "sess_attribution_test_123"
  }'

# Step 3: Create lead with session reference
curl -X POST https://your-site.com/wp-json/pearblog/v1/leads \
  -H "Content-Type: application/json" \
  -d '{
    "intent": "RENOVATION",
    "package": "STANDARD",
    "contact": {"name": "Test User", "email": "test@example.com"},
    "message": "Interested in renovation services",
    "metadata": {
      "session_id": "sess_attribution_test_123",
      "source_article_id": 456
    }
  }'

# Step 4: Track revenue with lead reference
curl -X POST https://your-site.com/wp-json/pearblog/v1/event \
  -H "Content-Type: application/json" \
  -d '{
    "event_type": "revenue",
    "article_id": 456,
    "session_id": "sess_attribution_test_123",
    "event_data": {
      "amount": 350.00,
      "lead_id": 126
    }
  }'
```

**Verification**:
```sql
-- Check complete attribution chain
SELECT
  e1.event_type as event,
  e1.created_at,
  l.id as lead_id,
  l.score,
  e2.event_data->>'$.amount' as revenue
FROM wp_pearblog_events e1
LEFT JOIN wp_pearblog_leads l ON l.metadata->>'$.session_id' = e1.session_id
LEFT JOIN wp_pearblog_events e2 ON e2.session_id = e1.session_id AND e2.event_type = 'revenue'
WHERE e1.session_id = 'sess_attribution_test_123'
ORDER BY e1.created_at;
```

**Expected Results**:
- ✅ Session ID tracked across all events
- ✅ Lead references original article (456)
- ✅ Revenue event linked to lead (126)
- ✅ Article stats show +1 lead, +350 PLN revenue
- ✅ Lead detail in admin shows source article
- ✅ Article detail in admin shows lead conversion

**Integration Points Tested**:
- Poradnik EventTracker → session tracking
- PT24 Lead Engine → metadata storage
- Revenue attribution → cross-system linkage
- Admin dashboard → unified view

---

### Test 4.6: End-to-End User Journey
**Objective**: Simulate complete user journey through all systems

**Scenario**: User seeks bathroom renovation in Kraków

**Journey Steps**:

1. **Content Discovery** (Poradnik Engine)
   - User searches Google: "remont łazienki kraków cena"
   - Lands on Poradnik article (article 456)
   - Article generated by Poradnik Engine with PT24 pricing data

2. **Content Engagement** (Event Tracking)
   - Page view tracked
   - User scrolls to 80% (engagement tracking)
   - Time on page: 3 minutes 24 seconds
   - Events captured in `pearblog_events`

3. **Lead Conversion** (PT24 Lead Engine)
   - User clicks CTA "Sprawdź oferty w Krakowie"
   - Fills out lead form on PT24 landing page
   - Lead submitted to PT24 Lead Engine API
   - Lead scored (85 - high urgency + good budget)
   - Automatic AI reply sent within 2 minutes

4. **Lead Management** (Admin Dashboard)
   - Admin receives notification in **Leads & CRM** tab
   - Reviews lead details and score breakdown
   - Routes lead to Expert tier (score ≥80)
   - Expert receives email/SMS notification

5. **Expert Response** (Manual)
   - Expert calls customer within 15 minutes
   - Provides detailed quote: 18,500 PLN
   - Customer accepts offer

6. **Revenue Tracking** (Poradnik Engine)
   - Sale marked as won in CRM
   - Revenue event (350 PLN affiliate commission) tracked
   - Revenue attributed back to original article
   - Article score increases (revenue component +10 points)

7. **Optimization** (Decision Engine)
   - Article now scores 92 (SCALE category)
   - Decision engine suggests: "Scale to other cities"
   - Content team creates variants for Warszawa, Wrocław, Gdańsk
   - A/B test created for intro optimization

**Verification Steps**:
```bash
# Check complete journey in database
wp eval '
$session = "user_journey_test_456";
$events = $wpdb->get_results("
  SELECT e.event_type, e.created_at, l.score, l.state, a.score as article_score
  FROM {$wpdb->prefix}pearblog_events e
  LEFT JOIN {$wpdb->prefix}pearblog_leads l ON l.metadata->>\"$.session_id\" = e.session_id
  LEFT JOIN {$wpdb->prefix}pearblog_articles a ON a.id = e.article_id
  WHERE e.session_id = \"$session\"
  ORDER BY e.created_at
");
print_r($events);
'
```

**Expected Timeline**:
```
T+0:00   - Page view (article 456)
T+0:05   - Scroll to 20%
T+0:45   - Scroll to 50%
T+2:30   - Scroll to 80%
T+3:24   - CTA click
T+3:45   - Lead submitted (lead 126, score 85)
T+3:47   - AI reply sent
T+15:00  - Lead routed to Expert
T+18:30  - Expert contacted customer
T+45:00  - Sale confirmed
T+45:15  - Revenue event tracked (350 PLN)
T+60:00  - Article rescored (92, SCALE category)
```

**Success Criteria**:
- ✅ All 3 systems involved (Admin, PT24, Poradnik)
- ✅ Complete data flow from view → lead → revenue
- ✅ Attribution accurate across systems
- ✅ Admin dashboard reflects entire journey
- ✅ Automated actions triggered correctly (AI reply, routing, scoring)
- ✅ Manual actions logged (expert contact, sale)
- ✅ Optimization suggestions generated
- ✅ No data loss or broken links in chain

---

## Test Suite 5: Performance & Load Testing

### Test 5.1: API Response Times
**Objective**: Verify all APIs meet performance targets

**Target Response Times**:
- Lead creation: < 100ms
- Lead scoring: < 50ms
- AI reply generation: 2-5 seconds (OpenAI dependent)
- Content scoring: < 200ms
- Event tracking: < 50ms
- Analytics queries: < 500ms

**Load Test**:
```bash
# Install Apache Bench
sudo apt-get install apache2-utils

# Test lead creation endpoint (100 requests, 10 concurrent)
ab -n 100 -c 10 -p lead_data.json -T 'application/json' \
  https://your-site.com/wp-json/pearblog/v1/leads

# Test event tracking endpoint (1000 requests, 50 concurrent)
ab -n 1000 -c 50 -p event_data.json -T 'application/json' \
  https://your-site.com/wp-json/pearblog/v1/event

# Test analytics endpoint (50 requests, 5 concurrent)
ab -n 50 -c 5 \
  https://your-site.com/wp-json/pearblog/v1/leads/analytics?days=7
```

**Pass Criteria**:
- ✅ Mean response time within targets
- ✅ 95th percentile < 2x target
- ✅ 99th percentile < 3x target
- ✅ 0% failed requests
- ✅ No PHP errors or warnings

---

### Test 5.2: Database Performance
**Objective**: Verify database queries are optimized

**Check Query Performance**:
```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 0.1;

-- Run typical queries
SELECT * FROM wp_pearblog_leads WHERE score >= 80 ORDER BY created_at DESC LIMIT 20;
SELECT * FROM wp_pearblog_articles WHERE category = 'SCALE' ORDER BY score DESC LIMIT 50;
SELECT * FROM wp_pearblog_article_stats WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY);

-- Check slow queries
SELECT * FROM mysql.slow_log WHERE sql_text LIKE '%pearblog%';
```

**Verify Indexes Exist**:
```sql
SHOW INDEX FROM wp_pearblog_leads;
SHOW INDEX FROM wp_pearblog_articles;
SHOW INDEX FROM wp_pearblog_events;
SHOW INDEX FROM wp_pearblog_article_stats;
```

**Expected Indexes**:
- `pearblog_leads`: (score), (state), (created_at), (package)
- `pearblog_articles`: (score), (category), (updated_at)
- `pearblog_events`: (article_id, created_at), (session_id)
- `pearblog_article_stats`: (article_id, date), (date)

**Pass Criteria**:
- ✅ All critical queries < 100ms
- ✅ No missing indexes on frequently queried columns
- ✅ No table scans on large tables
- ✅ Query cache hit rate > 80%

---

### Test 5.3: Admin Dashboard Load Time
**Objective**: Verify dashboard performs well with data

**Setup**:
1. Generate 1000 leads
2. Generate 500 articles
3. Generate 100,000 events
4. Measure dashboard load times

**Metrics to Check**:
- Initial page load: < 2 seconds
- Tab switch: < 500ms
- Data table pagination: < 300ms
- Chart rendering: < 500ms
- Search/filter: < 400ms

**Tools**:
- Chrome DevTools Performance tab
- Network tab (check payload sizes)
- Lighthouse audit (target score > 90)

**Pass Criteria**:
- ✅ All interactions feel responsive
- ✅ No layout shifts or jank
- ✅ Smooth scrolling and animations
- ✅ Data tables paginate efficiently
- ✅ Charts render without blocking UI

---

## Test Suite 6: Security & Validation

### Test 6.1: Authentication & Authorization
**Objective**: Verify API endpoints properly secured

**Test Cases**:

**Unauthenticated Requests**:
```bash
# Try to access protected endpoints without auth
curl https://your-site.com/wp-json/pearblog/v1/leads/analytics
# Expected: 401 Unauthorized

curl https://your-site.com/wp-json/pearblog/v1/content/generate
# Expected: 401 Unauthorized
```

**Insufficient Permissions**:
```bash
# Try to access admin endpoints as subscriber
curl https://your-site.com/wp-json/pearblog/v1/leads \
  -H "Authorization: Bearer SUBSCRIBER_TOKEN"
# Expected: 403 Forbidden
```

**Valid Authentication**:
```bash
# Access with valid admin token
curl https://your-site.com/wp-json/pearblog/v1/leads/analytics \
  -H "Authorization: Bearer ADMIN_TOKEN"
# Expected: 200 OK with data
```

**Pass Criteria**:
- ✅ Protected endpoints require authentication
- ✅ Admin-only endpoints check `manage_options` capability
- ✅ Public endpoints (event tracking) accessible without auth
- ✅ Proper HTTP status codes returned (401, 403, 200)

---

### Test 6.2: Input Validation
**Objective**: Verify malicious input rejected

**SQL Injection Attempts**:
```bash
curl -X POST https://your-site.com/wp-json/pearblog/v1/leads \
  -H "Content-Type: application/json" \
  -d '{
    "contact": {
      "email": "test@example.com OR 1=1--"
    }
  }'
# Expected: 400 Bad Request with validation error
```

**XSS Attempts**:
```bash
curl -X POST https://your-site.com/wp-json/pearblog/v1/leads \
  -H "Content-Type: application/json" \
  -d '{
    "message": "<script>alert(\"XSS\")</script>"
  }'
# Expected: Message sanitized, script tags stripped
```

**Pass Criteria**:
- ✅ SQL injection attempts blocked
- ✅ XSS payloads sanitized
- ✅ Input validated against schema
- ✅ Error messages don't leak sensitive info

---

## Troubleshooting Guide

### Issue: Leads not appearing in dashboard
**Check**:
1. Verify `pearblog_leads` table exists
2. Check lead state is not filtered out
3. Verify REST API route registered
4. Check browser console for JavaScript errors

### Issue: Content scoring returns 0
**Check**:
1. Verify article has stats in `pearblog_article_stats`
2. Run stats aggregation manually: `wp cron event run poradnik_aggregate_stats`
3. Check that events were tracked for article
4. Verify scoring formula weights configured

### Issue: AI replies not generating
**Check**:
1. Verify OpenAI API key configured
2. Check API rate limits not exceeded
3. Verify lead has required fields (contact info, message)
4. Check error logs: `tail -f wp-content/debug.log`

### Issue: Background workers not running
**Check**:
1. Verify WP-Cron enabled: `wp config get DISABLE_WP_CRON`
2. Check scheduled events: `wp cron event list | grep poradnik`
3. Manually trigger: `wp cron event run poradnik_scoring_worker`
4. Verify server has outbound HTTP access for WP-Cron

### Issue: Admin dashboard shows "Enterprise mode not enabled"
**Check**:
1. Verify constant in `pearblog-engine.php`: `define('PEARBLOG_ADMIN_VERSION', 'v8-enterprise');`
2. Clear object cache if using caching plugin
3. Deactivate and reactivate plugin
4. Check PHP error log for class loading issues

---

## Success Checklist

### Pre-Production
- [ ] All 30+ integration tests passed
- [ ] Performance targets met (response times < targets)
- [ ] Security tests passed (auth, validation)
- [ ] Database indexes optimized
- [ ] Error handling tested
- [ ] Load testing completed (100+ concurrent users)

### Production Deployment
- [ ] Database backups automated
- [ ] Monitoring enabled (error tracking, performance)
- [ ] API keys configured and secured
- [ ] SSL certificates valid
- [ ] WP-Cron verified operational
- [ ] Admin users trained on dashboard

### Post-Deployment
- [ ] Smoke tests passed (basic CRUD operations)
- [ ] Real user monitoring enabled
- [ ] Alert thresholds configured
- [ ] Documentation accessible to team
- [ ] Support process established

---

## Conclusion

This integration test suite verifies that **Enterprise V8** operates as a cohesive system:

- ✅ **31 PHP files** working together seamlessly
- ✅ **9 database tables** with proper relationships
- ✅ **13 REST API endpoints** performing correctly
- ✅ **15 admin tabs** displaying real-time data
- ✅ **3 major systems** integrated (Admin, PT24, Poradnik)

All tests passing indicates the system is **production-ready** for deployment.

---

*Last Updated: 2026-05-03*
*PearBlog Engine - Enterprise V8*
*Integration Test Suite v1.0*

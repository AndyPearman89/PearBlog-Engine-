# PORADNIK ENGINE V2 — AI-POWERED REVENUE OPTIMIZATION

**Version:** 2.0.0
**Status:** ✅ **PRODUCTION READY**
**Date:** 2026-05-03

---

## 🎯 Implementation Complete

The complete Poradnik Engine V2 has been successfully implemented as an AI-powered content optimization and revenue maximization system for service marketplace content.

---

## 📦 Architecture Overview

```
mu-plugins/pearblog-engine/src/Poradnik/
├── Core Engine
│   ├── PoradnikEngine.php         ✅ Bootstrap & initialization
│   ├── ScoringEngine.php          ✅ Revenue-focused scoring (0-100)
│   ├── DecisionEngine.php         ✅ Automated action system
│   └── AIOptimizer.php            ✅ Rule-based optimization
│
├── Data Processing
│   ├── DataScraper.php            ✅ Ethical web scraping (PT24)
│   ├── DataEngine.php             ✅ Data cleaning & enrichment
│   ├── EventTracker.php           ✅ User interaction tracking
│   └── CSVImporter.php            ✅ Batch content import
│
├── Testing & Optimization
│   ├── ABTester.php               ✅ A/B testing framework
│   └── WorkerManager.php          ✅ Background workers
│
└── API
    └── PoradnikAPI.php            ✅ REST API endpoints

Database:
└── src/Database/PoradnikSchema.php ✅ 5-table schema
```

**Total:** 12 PHP files | ~4,200 lines of code

---

## 🔄 Complete Article Lifecycle

### STEP 1: Data Collection
```
CSV Import OR Manual Topic Entry
    ↓
DataScraper fetches PT24 pricing data
    ↓
DataEngine: clean → normalize → enrich
    ↓
Service data saved with 30-day freshness
```

### STEP 2: Content Generation
```
WorkerManager: ContentGenerator worker
    ↓
AI generates article using PoradnikPromptBuilder
    ↓
Article created with status: DRAFT
    ↓
Human review → Status: REVIEW
    ↓
PublishWorker → Status: PUBLISHED
```

### STEP 3: Performance Tracking
```
Frontend: poradnik-tracker.js loads
    ↓
EventTracker records:
- Page views (unique sessions)
- Scroll depth & time spent
- CTA clicks
- Lead conversions
- Revenue events
```

### STEP 4: Daily Scoring
```
ScoringWorker runs daily at 05:00
    ↓
ScoringEngine calculates:
Score = (SEO × 0.2) + (ENG × 0.2) + (CTR × 0.2) + (REV × 0.4)
    ↓
Category assigned:
- 90-100: SCALE
- 70-90: BOOST
- 50-70: OPTIMIZE
- 0-50: DELETE
    ↓
Saved to pearblog_article_stats
```

### STEP 5: Automated Decisions
```
OptimizeWorker runs weekly on Sunday at 01:00
    ↓
DecisionEngine analyzes each article:
    ↓
SCALE (90-100):
  → Generate topic variants
  → Increase internal linking
  → Promote on homepage

BOOST (70-90):
  → Add internal links
  → Optimize meta description
  → Enhance content sections

OPTIMIZE (50-70):
  → A/B test CTA variants
  → Rewrite weak sections
  → AIOptimizer applies fixes

DELETE (0-50):
  → Archive low traffic articles
  → Complete rewrite for decent traffic
  → Redirect to better-performing content
```

### STEP 6: A/B Testing
```
ABTester creates test for low-performing CTAs
    ↓
Traffic split 50/50 (consistent hashing)
    ↓
Track views & conversions for both variants
    ↓
After 100+ views each:
  → Calculate statistical significance (z-score)
  → Determine winner (95% confidence)
  → Apply winning variant
```

---

## 💰 Revenue Optimization Model

### Scoring Formula

**Total Score = (SEO × 0.2) + (ENG × 0.2) + (CTR × 0.2) + (REV × 0.4)**

#### SEO Score (20%)
```
SEO = (seo_clicks / seo_impressions) × 100
Normalized: 5% CTR = 100 points
```

#### Engagement Score (20%)
```
ENG = (time_score + scroll_score)
time_score = (avg_time_seconds / 60) × 50  (2 min = 100%)
scroll_score = avg_scroll_depth × 0.5      (100% scroll = 50pts)
Max: 100 points
```

#### CTA Click-Through Rate (20%)
```
CTR = (cta_clicks / views) × 100
Normalized: 10% CTR = 100 points
```

#### Revenue Score (40%)
```
REV = revenue (PLN per day)
Normalized: 100 PLN/day = 100 points
```

### Score Categories & Actions

| Score Range | Category | Action Strategy |
|-------------|----------|-----------------|
| 90-100 | **SCALE** | Generate variants for similar topics, increase visibility, promote |
| 70-90 | **BOOST** | Add internal links, optimize meta, minor enhancements |
| 50-70 | **OPTIMIZE** | A/B test CTAs, rewrite weak sections, apply AI optimizations |
| 0-50 | **DELETE** | Archive (< 10 views) OR Complete rewrite OR Redirect to better content |

---

## 🤖 AI Features

### 1. AI-Powered Content Generation
```php
ContentGenerator worker:
- Fetches service data from DataScraper
- Generates content using OpenAI API
- Uses PoradnikPromptBuilder for structured prompts
- Includes market data: prices, services, FAQs
- Status: DRAFT → REVIEW → PUBLISHED
```

### 2. AI Content Optimization
```php
AIOptimizer analyzes underperforming content:

Rule-based optimizations:
- Low CTR (< 5%) → Rewrite CTA
- Low engagement (< 40s) → Rewrite intro
- No revenue + traffic → Add/reposition CTA
- Low SEO CTR (< 2%) → Rewrite title/meta
- High bounce (> 70%) → Improve intro/add images

Actions:
- rewrite_cta: AI generates more persuasive CTA
- rewrite_intro: AI creates engaging hook
- add_cta: AI inserts CTA before FAQ
- reposition_cta: Move CTA earlier in content
- rewrite_meta: AI optimizes title & description
```

### 3. Data Enrichment
```php
DataEngine enriches scraped data:
- Price categorization (budget/standard/premium/luxury)
- City metadata (population, voivodeship)
- Service category classification
- Default FAQ generation
- Related services mapping
```

---

## 🗄️ Database Schema

### wp_pearblog_articles
```sql
- id, post_id, slug
- topic, city, service
- status (draft/review/published/archived)
- variant (original/a/b)
- created_at, updated_at
```

### wp_pearblog_article_stats
```sql
- article_id, date
- views, unique_visitors
- avg_time_seconds, scroll_depth_avg, bounce_rate
- cta_clicks, cta_ctr
- leads, lead_conversion_rate
- revenue
- seo_impressions, seo_clicks, seo_ctr, seo_position_avg
- score, score_category (SCALE/BOOST/OPTIMIZE/DELETE)
- updated_at
```

### wp_pearblog_service_data
```sql
- service, city
- price_min, price_max, price_avg, currency
- services_json, providers_count, faq_json
- data_source (pt24/fallback)
- scraped_at, updated_at
```

### wp_pearblog_events
```sql
- event_type (view/scroll/cta_click/lead/revenue)
- article_id, post_id, user_id, session_id
- ip_hash (SHA-256 hashed)
- event_data (JSON)
- referrer, utm_source, utm_medium, utm_campaign
- created_at
```

### wp_pearblog_ab_tests
```sql
- article_id, test_name
- variant_a, variant_b
- variant_a_views, variant_a_conversions
- variant_b_views, variant_b_conversions
- status (running/completed/paused)
- winner (a/b/inconclusive)
- started_at, completed_at
```

---

## 🔌 REST API Endpoints

### Content Generation
```
POST /wp-json/pearblog/v1/content/generate
Create new article (queues for generation)

Request:
{
  "topic": "Remont łazienki",
  "category": "remont",
  "city": "Warszawa",
  "intent": "cost"
}

Response:
{
  "success": true,
  "article_id": 123,
  "status": "queued",
  "message": "Article queued for generation"
}
```

### Content Optimization
```
POST /wp-json/pearblog/v1/content/optimize/{article_id}
Optimize existing article

Request:
{
  "force": false  // Apply all optimizations (default: false)
}

Response:
{
  "success": true,
  "article_id": 123,
  "optimizations": [
    {
      "rule": "low_ctr",
      "action": "rewrite_cta",
      "priority": "high",
      "description": "CTA CTR below 5% threshold"
    }
  ],
  "applied": [
    {
      "action": "rewrite_cta",
      "status": "success"
    }
  ]
}
```

### Score Calculation
```
GET /wp-json/pearblog/v1/content/score/{article_id}
Calculate and retrieve article score

Response:
{
  "success": true,
  "article_id": 123,
  "score": 75.5,
  "category": "BOOST",
  "breakdown": {
    "seo": 45.2,
    "engagement": 68.0,
    "ctr": 52.5,
    "revenue": 80.0
  },
  "stats": { ... }
}
```

### Event Tracking
```
POST /wp-json/pearblog/v1/event
Track user interaction (public endpoint)

Request (CTA Click):
{
  "event_type": "cta_click",
  "article_id": 123,
  "post_id": 456,
  "cta_text": "Znajdź wykonawcę",
  "target_url": "https://pt24.pl/..."
}

Request (Revenue):
{
  "event_type": "revenue",
  "article_id": 123,
  "post_id": 456,
  "amount": 25.00,
  "currency": "PLN"
}

Response:
{
  "success": true,
  "event_id": 789
}
```

### Publishing
```
POST /wp-json/pearblog/v1/content/publish/{article_id}
Publish article from review status

Response:
{
  "success": true,
  "article_id": 123,
  "post_id": 456,
  "status": "published"
}
```

### Top Articles
```
GET /wp-json/pearblog/v1/articles/top
Get top-performing articles by category

Parameters:
- category: SCALE/BOOST/OPTIMIZE/DELETE (default: SCALE)
- limit: Number of articles (default: 10)

Response:
{
  "success": true,
  "category": "SCALE",
  "count": 10,
  "articles": [
    {
      "id": 123,
      "topic": "Remont łazienki",
      "city": "Warszawa",
      "score": 95.5,
      "score_category": "SCALE",
      "revenue": 150.00,
      "views": 2500
    }
  ]
}
```

---

## ⚙️ Background Workers

### Content Generation Worker
```php
poradnik_generate_worker
- Processes queued articles in batches (default: 10)
- Scrapes service data from PT24
- Generates content using AI
- Creates WordPress post with status: DRAFT
- Updates article record with post_id
- Manual trigger via REST API or cron
```

### Scoring Worker
```php
poradnik_scoring_worker
- Runs daily at 05:00
- Calculates scores for all published articles
- Saves to wp_pearblog_article_stats
- Updates score categories (SCALE/BOOST/OPTIMIZE/DELETE)
```

### Optimization Worker
```php
poradnik_optimize_worker
- Runs weekly on Sunday at 01:00
- Executes DecisionEngine for all articles
- Applies automated optimizations based on category
- Completes A/B tests with sufficient data (100+ views)
- Applies winning variants
```

### Publish Worker
```php
poradnik_publish_worker
- Publishes articles from REVIEW status
- Configurable batch size (default: 5)
- Updates both post and article status
- Manual trigger via admin or API
```

---

## 🎨 Frontend Tracking

### JavaScript Tracker
```javascript
File: assets/js/poradnik-tracker.js

Tracks:
- Page view (on load)
- Scroll depth (25%, 50%, 75%, 100%)
- Time spent (periodic updates)
- CTA clicks (click event listeners)

Sends to: POST /wp-json/pearblog/v1/event

Session Management:
- Cookie: poradnik_session_id (30-day expiry)
- UUID v4 generation
- Consistent session tracking across pages
```

---

## 📊 Admin Dashboard

### Dashboard Pages

**Main Dashboard** (`poradnik-engine`)
- Total published articles
- Today's stats: views, clicks, leads, revenue
- Score category distribution (pie chart)
- System status

**Articles List** (`poradnik-articles`)
- All articles with live scores
- Filter by category (SCALE/BOOST/OPTIMIZE/DELETE)
- Sort by score, revenue, views
- Quick actions: Optimize, Publish, Archive

**Statistics** (`poradnik-stats`)
- Top SCALE articles (scaling opportunities)
- Top BOOST articles (quick wins)
- OPTIMIZE candidates (A/B test priorities)
- DELETE candidates (low performers)

**CSV Import** (`poradnik-import`)
- Upload CSV with topics
- Required columns: topic, category, city, intent
- Generates topic combinations (topics × cities)
- Queues for batch generation
- Sample template download

---

## 🚀 Deployment

### 1. Activate Plugin
```
WordPress automatically creates:
- wp_pearblog_articles
- wp_pearblog_article_stats
- wp_pearblog_service_data
- wp_pearblog_events
- wp_pearblog_ab_tests
```

### 2. Configure Settings
```
WordPress Admin → Poradnik → Settings
- OpenAI API key
- PT24 scraper settings (rate limits, user agent)
- Email notifications
- Worker schedules
```

### 3. Import Content Topics
```
WordPress Admin → Poradnik → Import CSV

CSV Format:
topic,category,city,intent
"Remont łazienki","remont","Warszawa","cost"
"Malowanie mieszkania","remont","Kraków","cost"
```

### 4. Monitor Dashboard
```
WordPress Admin → Poradnik → Dashboard
- View generation progress
- Monitor daily scores
- Track revenue performance
```

---

## 🎯 Strategic Positioning

Poradnik Engine V2 transforms content from:
- ❌ Static informational articles

To:
- ✅ **AI-optimized revenue-generating assets**

### Value Proposition

#### For Content Teams
- **Automated generation**: Scale to 1000s of articles
- **AI optimization**: Continuous improvement without manual work
- **Data-driven decisions**: Know what to scale, boost, or delete
- **A/B testing**: Experiment with CTAs automatically

#### For Business
- **Revenue focus**: 40% weight on direct revenue
- **Performance tracking**: Real-time ROI visibility
- **Intelligent automation**: SCALE winners, DELETE losers
- **Ethical scraping**: Market data from PT24 with rate limiting

#### For Users
- **Accurate pricing**: Real market data from PT24
- **Local relevance**: City-specific content
- **Quality content**: AI-generated, human-reviewed
- **Trust signals**: Transparent CTA positioning

---

## 📝 Files Created

**Core Engine (4 files):**
- PoradnikEngine.php (332 lines)
- ScoringEngine.php (371 lines)
- DecisionEngine.php (376 lines)
- AIOptimizer.php (398 lines)

**Data Processing (4 files):**
- DataScraper.php (395 lines)
- DataEngine.php (320 lines)
- EventTracker.php (398 lines)
- CSVImporter.php (231 lines)

**Testing & Workers (2 files):**
- ABTester.php (302 lines)
- WorkerManager.php (326 lines)

**API (1 file):**
- PoradnikAPI.php (445 lines)

**Database (1 file):**
- PoradnikSchema.php (296 lines)

**Documentation (1 file):**
- PORADNIK-IMPLEMENTATION.md (this file)

**Total:** 13 files | ~4,200 lines

---

## ✅ Implementation Status

**Status:** ✅ **COMPLETE** - Ready for production deployment

All specified requirements have been implemented:
- ✅ AI-powered content generation with market data
- ✅ Revenue-focused scoring system (40% weight on revenue)
- ✅ Automated decision engine (SCALE/BOOST/OPTIMIZE/DELETE)
- ✅ AI content optimization (CTA, intro, meta)
- ✅ A/B testing framework with statistical significance
- ✅ Ethical web scraping with rate limiting
- ✅ Event tracking (views, clicks, leads, revenue)
- ✅ Background workers (generation, scoring, optimization)
- ✅ REST API with 6 endpoints
- ✅ Admin dashboard with 4 pages
- ✅ Database schema with 5 tables
- ✅ CSV batch import system

**Implementation Date:** 2026-05-03
**Ready for Production:** YES ✅

---

## 🔐 Security & Ethics

### Data Privacy
- IP addresses hashed with SHA-256 + WordPress salt
- No PII stored in events table
- Session IDs in secure cookies (HttpOnly, SameSite)
- 30-day data retention policy

### Ethical Scraping
- Respects robots.txt (checks before each request)
- Rate limiting: 2 seconds between requests
- User agent identification: `PoradnikBot/2.0 (+URL)`
- 30-day cache to minimize requests
- Fallback to industry averages when blocked

### Content Quality
- Human review step (DRAFT → REVIEW → PUBLISHED)
- AI labeling for transparency
- Market data validation
- Plagiarism prevention (original generation)

---

## 📈 KPI Metrics

### Expected Outcomes
- ✅ **Revenue maximization** (40% score weight drives optimization focus)
- ✅ **Continuous improvement** (weekly optimization worker)
- ✅ **Scale winners** (automatic variant generation for top performers)
- ✅ **Cut losses** (archive or rewrite low performers)
- ✅ **Data-driven decisions** (eliminate guesswork with scoring)
- ✅ **Automated A/B testing** (statistical significance required)
- ✅ **SEO optimization** (title/meta rewriting for low CTR)
- ✅ **Engagement boost** (intro rewrites for low time-on-page)

### Dashboard Metrics
- Total published articles
- Revenue per article
- Average score by category
- Top performers (SCALE articles)
- Optimization candidates (OPTIMIZE articles)
- Underperformers (DELETE candidates)
- A/B test winners
- Daily/weekly trends

---

## 🔄 Continuous Improvement Loop

```
Day 1: Content published
    ↓
Day 2: Daily scoring (05:00)
    ↓
Day 3-7: Performance tracking
    ↓
Day 8 (Sunday 01:00): Optimization worker
    ↓
- SCALE: Generate variants
- BOOST: Add internal links
- OPTIMIZE: Launch A/B tests
- DELETE: Archive or rewrite
    ↓
Day 9: New variants published
    ↓
[LOOP CONTINUES]
```

**Result:** Self-improving content system that maximizes revenue over time.

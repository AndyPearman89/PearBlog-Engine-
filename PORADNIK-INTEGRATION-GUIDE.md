# Poradnik.pro — Complete Integration Guide

**Version:** 1.0
**Date:** 2026-05-03
**Status:** 🎯 Integration Blueprint

---

## 🎯 Overview

This guide shows how **three systems** work together to create a complete revenue-generating content engine:

1. **Clean Content System** - Trust-building article template
2. **AI Content Engine V2** - Scalable content factory
3. **Engine V2 Production** - Revenue optimization architecture

---

## 📚 System Relationship

```
┌─────────────────────────────────────────────────────────────┐
│                    CONTENT LIFECYCLE                         │
└─────────────────────────────────────────────────────────────┘

1. GENERATION PHASE
   │
   ├─ AI Content Engine V2 (Factory)
   │  • CSV input: topic, category, city, intent
   │  • Batch generation: 1,000+ articles
   │  • Quality filtering
   │  • SEO optimization
   │  └─> Uses PoradnikPromptBuilder for template
   │
   └─ Clean Content System (Template)
      • 10-section structure
      • Cost-focused content
      • Soft CTA integration
      • Trust-building tone

2. OPTIMIZATION PHASE
   │
   └─ Engine V2 Production (Intelligence)
      • Event tracking
      • Performance scoring
      • Content segmentation
      • AI-powered optimization
      • A/B testing
      • Decision automation

3. REVENUE PHASE
   │
   └─ North Star Metric: Revenue per Article ↑
      • SEO → Click → PT24 → Lead → Revenue
```

---

## 🔄 Complete Data Flow

### Phase 1: Content Creation

```
CSV Input (topic, category, city, intent)
    ↓
AI Content Engine V2
    ↓
PoradnikPromptBuilder.build(topic)
    ↓
AI generates article following Clean Content System template
    ↓
SEO enhancement (title, meta, keywords)
    ↓
Quality filter (word count, structure, pricing data)
    ↓
Internal linker (2-3 PT24 links)
    ↓
WordPress publisher (draft → review → publish)
    ↓
Store in wp_pearblog_articles table
```

### Phase 2: Performance Tracking

```
Published article
    ↓
User interactions:
    • View event
    • Scroll depth tracking
    • CTA click tracking
    • Lead conversion tracking
    • Revenue tracking
    ↓
Store in wp_pearblog_events table
    ↓
Daily aggregation → wp_pearblog_article_stats
```

### Phase 3: Scoring & Optimization

```
Daily cron (05:00)
    ↓
Scoring Engine V2 calculates:
    Score = (SEO × 0.2) + (ENG × 0.2) + (CTR × 0.2) + (REV × 0.4)
    ↓
Content segmentation:
    • 90-100: SCALE (generate variants)
    • 70-90: BOOST (increase visibility)
    • 50-70: OPTIMIZE (A/B test)
    • 0-50: REWRITE/DELETE
    ↓
Weekly cron (01:00 Sunday)
    ↓
AI Optimizer applies rule-based fixes:
    • Low CTR → rewrite CTA
    • Low engagement → rewrite intro
    • No revenue → add/reposition CTA
    • Low SEO CTR → rewrite title/meta
    • High bounce → improve intro, add images
    ↓
Decision Engine automates actions:
    • Launch A/B tests for mid-performers
    • Generate variants for top performers
    • Archive/redirect bottom performers
```

---

## 🗄️ Database Schema Integration

All three systems share the same database schema:

### Core Tables

```sql
-- Content storage (AI Engine V2 writes here)
wp_pearblog_articles
    • id, post_id, slug, topic, city, service
    • status (draft, review, published, archived)
    • variant (for A/B testing)

-- Service data for content generation (Scraper writes here)
wp_pearblog_service_data
    • service, city
    • price_min, price_max, price_avg
    • services_json, providers_count, faq_json

-- Performance tracking (Event tracking writes here)
wp_pearblog_events
    • event_type (view, scroll, cta_click, lead, revenue)
    • article_id, post_id
    • session_id, utm_source, utm_campaign

-- Daily statistics (Scoring Engine writes here)
wp_pearblog_article_stats
    • article_id, date
    • views, cta_clicks, leads, revenue
    • score, score_category (SCALE, BOOST, OPTIMIZE, DELETE)

-- A/B testing (AI Optimizer writes here)
wp_pearblog_ab_tests
    • article_id, variant_a, variant_b
    • conversions, status, winner
```

---

## 🔧 Code Integration Points

### 1. PromptBuilderFactory.php

Auto-detects Poradnik content and uses the clean content system:

```php
// Location: mu-plugins/pearblog-engine/src/Content/PromptBuilderFactory.php
// Lines: 43-46

if ( self::is_poradnik_content( $industry, $profile ) ) {
    return new PoradnikPromptBuilder( $profile );
}
```

**Detection keywords:**
- poradnik, guide, porady, remont, renovation
- budowa, construction, home improvement, home services

### 2. PoradnikPromptBuilder.php

Implements the 10-section clean content template:

```php
// Location: mu-plugins/pearblog-engine/src/Content/PoradnikPromptBuilder.php

public function build( string $topic ): string {
    // Returns complete prompt with:
    // 1. Meta description
    // 2. H1 title: "{topic} - ile kosztuje i jak wybrać"
    // 3. Intro (2-3 paragraphs)
    // 4. ## Co to jest {topic}?
    // 5. ## Ile kosztuje {topic}?
    // 6. ## Od czego zależy cena?
    // 7. ## Jak wybrać najlepszą opcję?
    // 8. Soft CTA section (natural PT24 link)
    // 9. ## Najczęściej zadawane pytania (FAQ)
    // 10. Conclusion
}
```

### 3. Plugin.php

Bootstrap location where DecisionPlatformManager will be registered:

```php
// Location: mu-plugins/pearblog-engine/src/Core/Plugin.php
// Line: 125

( new DecisionPlatformManager() )->register();
```

**To implement:** Create DecisionPlatformManager class that orchestrates:
- Scoring Engine V2
- Content Segmentation
- AI Optimizer
- A/B Testing Framework
- Decision Engine

---

## 🚀 Implementation Roadmap

### Week 1-2: Database & Scraper
- [ ] Create 5 database tables (schema in Engine V2 doc)
- [ ] Build data scraper for PT24 and market data
- [ ] Implement data engine (clean/normalize/enrich)
- [ ] Test service data collection for 10 services

### Week 3-4: Content Generation
- [ ] Integrate PoradnikPromptBuilder with AI generator
- [ ] Build CSV import system
- [ ] Implement SEO enhancer
- [ ] Create quality filter
- [ ] Build internal linker
- [ ] Test generation for 100 articles

### Week 5-6: Tracking & Scoring
- [ ] Implement event tracking (view, scroll, CTA, lead, revenue)
- [ ] Build Scoring Engine V2
- [ ] Create daily aggregation cron
- [ ] Implement content segmentation
- [ ] Test scoring with sample data

### Week 7-8: Optimization & Automation
- [ ] Build AI Optimizer with rule-based fixes
- [ ] Implement A/B testing framework
- [ ] Create Decision Engine
- [ ] Build background workers
- [ ] Setup cron jobs (daily scoring, weekly optimization)
- [ ] Create API endpoints
- [ ] Full system testing

---

## 📊 Success Metrics

### Month 1 Targets
- 1,000 articles published
- 50,000+ organic views
- 500+ CTA clicks
- 50+ leads generated
- Average score: 60+

### Month 3 Targets
- 3,000 articles published
- 200,000+ organic views
- 2,000+ CTA clicks
- 200+ leads generated
- Average score: 70+
- 20+ A/B tests completed
- 100+ articles in SCALE category (90-100 score)

### North Star Metric
```
Revenue per Article = Total Revenue / Published Articles

Goal: $5-10 per article per month
```

---

## 🔐 Quality Assurance

### Content Quality Checklist
- [ ] Article follows 10-section structure (Clean Content System)
- [ ] Contains actual pricing data (from service_data table)
- [ ] Has 1 soft CTA with natural anchor text
- [ ] Has 2-3 internal PT24 links
- [ ] Meta description is cost-focused
- [ ] Title includes "ile kosztuje i jak wybrać"
- [ ] FAQ section with 4-6 questions
- [ ] 1,200-1,800 words
- [ ] No aggressive sales language
- [ ] Trust-building tone maintained

### System Health Checks
- [ ] Event tracking capturing all interactions
- [ ] Daily scoring running successfully
- [ ] Score distribution: 10% SCALE, 30% BOOST, 40% OPTIMIZE, 20% DELETE
- [ ] AI optimizer running weekly
- [ ] A/B tests launching for mid-performers
- [ ] Revenue attribution working correctly

---

## 📝 API Usage Examples

### Generate Content
```bash
POST /wp-json/pearblog/v1/content/generate
Content-Type: application/json

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
  "post_id": 456,
  "score": 0,
  "status": "draft"
}
```

### Track Event
```bash
POST /wp-json/pearblog/v1/event
Content-Type: application/json

{
  "event_type": "cta_click",
  "article_id": 123,
  "post_id": 456,
  "session_id": "abc123",
  "utm_source": "google",
  "utm_campaign": "organic"
}

Response:
{
  "success": true,
  "event_id": 789
}
```

### Get Article Score
```bash
GET /wp-json/pearblog/v1/content/score/123

Response:
{
  "success": true,
  "article_id": 123,
  "score": 75.5,
  "category": "BOOST",
  "metrics": {
    "seo_score": 20.5,
    "engagement_score": 18.0,
    "ctr_score": 15.0,
    "revenue_score": 22.0
  },
  "stats": {
    "views": 1500,
    "cta_clicks": 45,
    "leads": 5,
    "revenue": 250.00
  }
}
```

### Optimize Article
```bash
POST /wp-json/pearblog/v1/content/optimize/123
Content-Type: application/json

{
  "force": false
}

Response:
{
  "success": true,
  "article_id": 123,
  "optimizations_applied": [
    "rewrite_cta",
    "reposition_cta"
  ],
  "new_variant": "b",
  "ab_test_id": 42
}
```

---

## 🔗 Documentation Links

- **PORADNIK-CLEAN-CONTENT-SYSTEM.md** - Content template specification
- **AI-CONTENT-ENGINE-V2.md** - Content factory architecture
- **PORADNIK-ENGINE-V2-PRODUCTION.md** - Revenue optimization system
- **COMPLETE-STEP-BY-STEP-GUIDE.md** - Operational manual
- **SYSTEM-ARCHITECTURE-MAP.md** - Overall system architecture

---

## 🎯 Key Takeaways

### The Three Systems Work Together:

1. **Clean Content System** provides the **template**
   - 10-section structure
   - Trust-building tone
   - Cost-focused content
   - Soft CTA integration

2. **AI Content Engine V2** provides the **factory**
   - Batch generation (1,000+ articles)
   - CSV input system
   - Quality filtering
   - SEO optimization

3. **Engine V2 Production** provides the **intelligence**
   - Performance tracking
   - Revenue-focused scoring
   - AI-powered optimization
   - Decision automation

### Result:
A complete, self-optimizing content system that:
- Generates high-quality trust-building content at scale
- Tracks every user interaction
- Scores articles based on revenue potential
- Automatically optimizes underperformers
- Scales winners through A/B testing and variants
- Maximizes revenue per article

---

## 🚦 Getting Started

### For Content Creation
Start here: **PORADNIK-CLEAN-CONTENT-SYSTEM.md**
- Learn the 10-section template
- Understand tone and style
- See usage examples

### For Scaling Production
Start here: **AI-CONTENT-ENGINE-V2.md**
- Setup CSV input system
- Configure AI generator
- Implement quality filters

### For Revenue Optimization
Start here: **PORADNIK-ENGINE-V2-PRODUCTION.md**
- Setup database schema
- Implement event tracking
- Build scoring engine
- Deploy AI optimizer

---

**Status:** ✅ All documentation complete. Ready for implementation.

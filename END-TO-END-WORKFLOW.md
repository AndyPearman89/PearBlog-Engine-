# PearBlog Engine — End-to-End Workflow Guide

> Complete development, testing, and deployment workflow for PearBlog Engine v6.1

---

## Overview

This document describes the complete end-to-end workflow for developing, testing, and deploying the PearBlog Engine autonomous content generation system.

## Table of Contents

1. [Development Workflow](#development-workflow)
2. [Testing Strategy](#testing-strategy)
3. [Content Pipeline Flow](#content-pipeline-flow)
4. [Deployment Process](#deployment-process)
5. [Monitoring & Alerts](#monitoring--alerts)
6. [Troubleshooting](#troubleshooting)

---

## Development Workflow

### 1. Local Development Setup

```bash
# Clone the repository
git clone https://github.com/AndyPearman89/PearBlog-Engine-.git
cd PearBlog-Engine-

# Install dependencies
cd mu-plugins/pearblog-engine
composer install

# Install Python dependencies (for automation scripts)
cd ../../
pip install -r requirements.txt
```

### 2. Code Organization

```
PearBlog-Engine/
├── mu-plugins/pearblog-engine/    # Core WordPress MU-plugin
│   ├── src/                       # Source code (PSR-4 autoloaded)
│   │   ├── Pipeline/              # 12-step content pipeline
│   │   ├── AI/                    # GPT-4o-mini + DALL-E 3
│   │   ├── Content/               # Prompt builders, validators, quality
│   │   ├── SEO/                   # Schema.org, internal linking
│   │   ├── Monetization/          # AdSense, affiliates, SaaS CTAs
│   │   ├── Monitoring/            # Alerts, health checks
│   │   └── ...
│   └── tests/                     # PHPUnit tests
│       └── php/
│           ├── Unit/              # Unit tests (no WordPress)
│           └── Integration/       # E2E tests (requires WordPress)
│
├── theme/pearblog-theme/          # SEO-first WordPress theme
├── scripts/                       # Python automation suite
└── .github/workflows/             # CI/CD pipelines
```

### 3. Making Changes

**For Plugin Changes:**

1. Edit PHP files in `mu-plugins/pearblog-engine/src/`
2. Follow PSR-4 autoloading conventions
3. Add/update unit tests in `tests/php/Unit/`
4. Run linter: `find src -name '*.php' -exec php -l {} \;`
5. Run tests: `vendor/bin/phpunit --testsuite Unit`

**For Theme Changes:**

1. Edit files in `theme/pearblog-theme/`
2. Test in a local WordPress environment
3. Verify dark mode, responsive design, SEO features

**For Documentation:**

1. Update relevant `.md` files
2. Keep `CHANGELOG.md` updated
3. Update version numbers consistently

---

## Testing Strategy

### Unit Tests

**Location:** `mu-plugins/pearblog-engine/tests/php/Unit/`

**Run Command:**
```bash
cd mu-plugins/pearblog-engine
vendor/bin/phpunit --testsuite Unit --testdox
```

**Coverage:**
- ✅ AIClient (circuit breaker, retry logic, cost tracking)
- ✅ TopicQueue (FIFO behavior, isolation)
- ✅ ContentValidator (generic, travel, beskidy modes)
- ✅ DuplicateDetector (TF-IDF similarity)
- ✅ QualityScorer (Flesch readability, keyword density)
- ✅ SEOEngine (meta tags, Schema.org)
- ✅ KeywordCluster (value objects)
- ✅ ContentScore (scoring logic)

**Total:** 52 tests, 81 assertions

### Integration Tests

**Location:** `mu-plugins/pearblog-engine/tests/php/Integration/`

**Requirements:** WordPress test environment

**Run Command:**
```bash
cd mu-plugins/pearblog-engine
vendor/bin/phpunit --testsuite Integration --testdox
```

**Coverage:**
- ✅ Complete pipeline execution (topic → published post)
- ✅ Empty queue handling
- ✅ Duplicate content detection and skipping
- ✅ Multiple topic processing in sequence
- ✅ Non-critical error handling

**Note:** Integration tests require WordPress functions. For manual testing without WordPress test framework, see [Manual Testing](#manual-testing) below.

### Python Tests

**Location:** `tests/python/`

**Run Command:**
```bash
pytest tests/python/ -v
```

**Coverage:**
- ✅ keyword_engine (research, clustering)
- ✅ serp_analyzer (competition analysis)
- ✅ automation_orchestrator (full-cycle orchestration)

### Manual Testing

**Complete End-to-End Test:**

1. **Setup WordPress Instance**
   ```bash
   # Install WordPress locally (e.g., using Local by Flywheel, XAMPP, or Docker)
   # Copy plugin and theme
   cp -r mu-plugins/pearblog-engine /path/to/wp-content/mu-plugins/
   cp -r theme/pearblog-theme /path/to/wp-content/themes/
   ```

2. **Configure Plugin**
   - Navigate to WP Admin → PearBlog Engine
   - Add OpenAI API key
   - Set industry, tone, language
   - Enable autonomous mode

3. **Add Topics to Queue**
   - Go to Queue tab
   - Add 2-3 test topics
   - Save

4. **Trigger Pipeline**
   - Option A: Wait for hourly cron
   - Option B: Click "Run Pipeline Now" button
   - Option C: Use WP-CLI: `wp pearblog generate`

5. **Verify Results**
   - Check Posts → All Posts for new articles
   - Verify SEO metadata (view source, check meta tags)
   - Check featured images are attached
   - Verify monetization blocks (ads, affiliate boxes)
   - Check internal links are inserted
   - View quality score in post meta
   - Check for duplicate detection (add similar topic)

6. **Test Monitoring**
   - Navigate to `/wp-json/pearblog/v1/health`
   - Verify JSON response with pipeline status
   - Check WP Admin dashboard widget for stats

---

## Content Pipeline Flow

### 12-Step Autonomous Pipeline

```
┌─────────────┐
│ Topic Queue │ ← Admin adds topics or ContentCalendar schedules them
└──────┬──────┘
       │
       ▼
┌─────────────────────────┐
│ 1. Prompt Building      │ ← PromptBuilderFactory selects builder
│    (Generic/Travel/etc) │
└──────┬──────────────────┘
       │
       ▼
┌─────────────────────────┐
│ 2. AI Generation        │ ← GPT-4o-mini with circuit breaker
│    (GPT-4o-mini)        │
└──────┬──────────────────┘
       │
       ▼
┌─────────────────────────┐
│ 3. Duplicate Check      │ ← TF-IDF similarity ≥80% = skip
└──────┬──────────────────┘
       │
       ▼
┌─────────────────────────┐
│ 4. Draft Post Creation  │ ← wp_insert_post (draft status)
└──────┬──────────────────┘
       │
       ▼
┌─────────────────────────┐
│ 5. SEO Metadata         │ ← Title, meta description, keywords
└──────┬──────────────────┘
       │
       ▼
┌─────────────────────────┐
│ 6. Monetization         │ ← AdSense, Booking.com, Airbnb, SaaS CTAs
└──────┬──────────────────┘
       │
       ▼
┌─────────────────────────┐
│ 7. Internal Linking     │ ← Keyword-based contextual links
└──────┬──────────────────┘
       │
       ▼
┌─────────────────────────┐
│ 8. Featured Image       │ ← DALL-E 3 generation
└──────┬──────────────────┘
       │
       ▼
┌─────────────────────────┐
│ 9. Meta Description     │ ← Auto-generate if AI didn't provide
└──────┬──────────────────┘
       │
       ▼
┌─────────────────────────┐
│ 10. Duplicate Index     │ ← Store TF vector for future checks
└──────┬──────────────────┘
       │
       ▼
┌─────────────────────────┐
│ 11. Publishing          │ ← wp_update_post (publish status)
└──────┬──────────────────┘
       │
       ▼
┌─────────────────────────┐
│ 12. Quality Scoring     │ ← Flesch readability, keyword density
└──────┬──────────────────┘
       │
       ▼
┌─────────────────────────┐
│ Webhooks & Alerts       │ ← Notify external systems
└─────────────────────────┘
```

### Pipeline Execution

**Automatic (WP-Cron):**
- Runs every hour (configurable)
- Processes `publish_rate` articles per cycle
- Requires `pearblog_autonomous_mode` = true

**Manual:**
- Admin UI: "Run Pipeline Now" button
- WP-CLI: `wp pearblog generate`
- REST API: `POST /wp-json/pearblog/v1/automation/process-content`

### Pipeline Events

The pipeline fires WordPress actions at key stages:

```php
// Hook into pipeline stages
add_action('pearblog_pipeline_started', function($topic, $context) {
    // Pipeline started for topic
});

add_action('pearblog_pipeline_duplicate_skipped', function($topic, $dup_result) {
    // Duplicate content detected, skipped
});

add_action('pearblog_pipeline_completed', function($post_id, $topic, $context) {
    // Article published successfully
});
```

---

## Deployment Process

### GitHub Actions Workflows

#### 1. Testing (`test.yml`)

**Triggers:** Push to main/master, PRs

**Jobs:**
- PHP Tests (PHP 8.1, 8.2, 8.3)
  - Install Composer dependencies
  - Run PHPUnit unit tests
  - Check PHP syntax
- Python Tests (Python 3.10, 3.11, 3.12)
  - Install pip dependencies
  - Run pytest

#### 2. Content Pipeline (`content-pipeline.yml`)

**Triggers:** Daily schedule, manual dispatch

**Purpose:** Automated content generation in production

**Steps:**
1. Checkout repository
2. Set up Python environment
3. Run `scripts/run_pipeline.py`
4. Push generated content to WordPress via REST API

#### 3. Roadmap Execution (`run-roadmap.yml`)

**Triggers:** Weekly schedule, manual dispatch

**Purpose:** Execute automation orchestrator for keyword research, SERP analysis

**Steps:**
1. Restore cached data
2. Run `scripts/automation_orchestrator.py`
3. Cache results for future runs

#### 4. Deployment (`deploy.yml`)

**Triggers:** Release tags

**Purpose:** Package and deploy plugin/theme to production

**Steps:**
1. Build production assets
2. Run tests
3. Create deployment package
4. Deploy to WordPress hosting

### Manual Deployment

**To WordPress Site:**

1. **Plugin Deployment:**
   ```bash
   # Create deployment package
   cd mu-plugins/pearblog-engine
   composer install --no-dev --optimize-autoloader
   cd ../..
   zip -r pearblog-engine.zip mu-plugins/pearblog-engine -x "*.git*" "*/tests/*" "*/vendor/bin/*"

   # Upload to /wp-content/mu-plugins/ on production server
   ```

2. **Theme Deployment:**
   ```bash
   # Create theme package
   cd theme
   zip -r pearblog-theme.zip pearblog-theme -x "*.git*" "*/node_modules/*"

   # Upload to /wp-content/themes/ and activate in WP Admin
   ```

3. **Configuration:**
   - Set OpenAI API key in wp-config.php or admin UI
   - Configure settings in PearBlog Engine admin panel
   - Add topics to queue
   - Enable autonomous mode

---

## Monitoring & Alerts

### Health Check Endpoint

**URL:** `GET /wp-json/pearblog/v1/health`

**Response:**
```json
{
  "status": "ok",
  "timestamp": 1712329200,
  "api_key_configured": true,
  "openai_reachable": true,
  "circuit_breaker_open": false,
  "queue_size": 5,
  "last_pipeline_run": "2026-04-05 14:30:00",
  "ai_cost_cents": 850
}
```

### Alert Manager

**Configuration:**
- Slack webhook: `pearblog_alert_slack_webhook`
- Discord webhook: `pearblog_alert_discord_webhook`
- Email: `pearblog_alert_email`

**Alert Triggers:**
- Pipeline errors
- Circuit breaker opened
- Article published
- Quality score below threshold
- Duplicate content detected

### Dashboard Widget

**Location:** WP Admin → Dashboard

**Metrics Displayed:**
- OpenAI connection status
- Topics in queue
- Posts published today
- AI-generated images count
- Missing alt texts
- Last pipeline run time

### WP-CLI Commands

```bash
# Pipeline management
wp pearblog generate              # Run pipeline once
wp pearblog queue list            # View queue
wp pearblog queue add "Topic"     # Add topic
wp pearblog queue clear           # Clear queue

# Monitoring
wp pearblog stats                 # Show statistics
wp pearblog circuit status        # Circuit breaker status
wp pearblog circuit reset         # Reset circuit breaker

# Content management
wp pearblog refresh               # Refresh old content
wp pearblog quality score <id>    # Score specific post
wp pearblog duplicate check <id>  # Check for duplicates
wp pearblog links backfill        # Add internal links to existing posts
```

---

## Troubleshooting

### Common Issues

#### 1. Pipeline Not Running

**Symptoms:** No new posts being created

**Checks:**
1. Verify autonomous mode is enabled: `get_option('pearblog_autonomous_mode')`
2. Check WP-Cron is working: `wp cron event list`
3. Verify topics in queue: `wp pearblog queue list`
4. Check OpenAI API key: Navigate to settings

**Solution:**
```bash
# Manually trigger pipeline
wp pearblog generate

# Check for errors in debug log
tail -f wp-content/debug.log
```

#### 2. Circuit Breaker Opened

**Symptoms:** Pipeline stops processing, errors in logs

**Cause:** 5+ consecutive API failures

**Solution:**
```bash
# Check circuit status
wp pearblog circuit status

# Reset circuit breaker
wp pearblog circuit reset

# Verify OpenAI connectivity
curl -H "Authorization: Bearer YOUR_API_KEY" https://api.openai.com/v1/models
```

#### 3. Duplicate Content Detected

**Symptoms:** Topics are skipped, no posts created

**Cause:** Similarity ≥80% with existing content

**Solution:**
- Rephrase topic to be more specific
- Disable duplicate check temporarily: `update_option('pearblog_duplicate_check_enabled', false)`
- Review duplicate detection threshold in settings

#### 4. Tests Failing

**Unit Tests:**
```bash
cd mu-plugins/pearblog-engine
composer install
vendor/bin/phpunit --testsuite Unit --testdox
```

**Integration Tests:**
- Require WordPress environment
- See `tests/php/Integration/README.md`

**Python Tests:**
```bash
pip install -r requirements.txt
pytest tests/python/ -v
```

#### 5. Image Generation Failures

**Symptoms:** Posts published without featured images

**Checks:**
1. OpenAI API key has DALL-E access
2. Check error logs for specific errors
3. Verify image generation is enabled

**Note:** Image generation failures are non-critical; pipeline continues

---

## Performance Metrics

### Expected Performance

| Metric | Value |
|--------|-------|
| Pipeline execution time | ~55 seconds |
| Cost per article (text only) | $0.0003 |
| Cost per article (with image) | $0.08 |
| Articles/month (rate=1) | 720 |
| Monthly cost (720 articles) | ~$58 |
| Break-even traffic | ~5,000 visitors/mo |

### Optimization Tips

1. **Reduce Costs:**
   - Disable image generation for some articles
   - Use smaller prompt sizes
   - Batch process during off-peak hours

2. **Improve Quality:**
   - Refine prompt builders
   - Adjust quality score thresholds
   - Enable content refresh for top performers

3. **Scale Up:**
   - Increase `publish_rate` setting
   - Add more topics to queue
   - Use Python scripts for keyword research

---

## Additional Resources

- **[SETUP.md](SETUP.md)** - Installation guide
- **[CHANGELOG.md](CHANGELOG.md)** - Version history
- **[DOCUMENTATION-INDEX.md](DOCUMENTATION-INDEX.md)** - Full documentation map
- **[PRODUCTION-ANALYSIS-FULL.md](PRODUCTION-ANALYSIS-FULL.md)** - Operations manual
- **[tests/php/Integration/README.md](mu-plugins/pearblog-engine/tests/php/Integration/README.md)** - Integration test guide

---

## Support

For issues, questions, or contributions:
1. Check troubleshooting section above
2. Review documentation
3. Open GitHub issue with detailed information

---

**Version:** 6.1.0
**Last Updated:** 2026-04-05
**Maintained By:** PearBlog Development Team

# PearBlog Engine — Database Migrations Strategy

> **Version:** 6.0.0  
> **Audience:** DevOps engineers, WordPress developers, system administrators  
> **Related:** [DEPLOYMENT.md](DEPLOYMENT.md) · [DISASTER-RECOVERY.md](DISASTER-RECOVERY.md)

---

## Table of Contents

1. [Overview](#1-overview)
2. [Full Schema Reference](#2-full-schema-reference)
   - 2.1 [WordPress Options (wp_options)](#21-wordpress-options-wp_options)
   - 2.2 [Post Meta (wp_postmeta)](#22-post-meta-wp_postmeta)
   - 2.3 [Standard WordPress Tables Used](#23-standard-wordpress-tables-used)
3. [Compatibility Matrix](#3-compatibility-matrix)
4. [Backup Strategy Before Migration](#4-backup-strategy-before-migration)
5. [Upgrade Paths](#5-upgrade-paths)
   - 5.1 [v4.x → v5.0](#51-v4x--v50)
   - 5.2 [v5.0 → v5.1](#52-v50--v51)
   - 5.3 [v5.1 → v5.2 / v5.3](#53-v51--v52--v53)
   - 5.4 [v5.x → v6.0 (major upgrade)](#54-v5x--v60-major-upgrade)
   - 5.5 [Fresh Install (v6.0)](#55-fresh-install-v60)
6. [Rollback Procedures](#6-rollback-procedures)
   - 6.1 [v6.0 → v5.x Rollback](#61-v60--v5x-rollback)
   - 6.2 [v5.x → v4.x Rollback](#62-v5x--v4x-rollback)
   - 6.3 [Partial Rollback (Options Only)](#63-partial-rollback-options-only)
7. [WP-CLI Migration Commands](#7-wp-cli-migration-commands)
8. [Data Integrity Verification](#8-data-integrity-verification)
9. [Multisite Considerations](#9-multisite-considerations)
10. [Troubleshooting](#10-troubleshooting)

---

## 1. Overview

PearBlog Engine stores all its persistent state in the standard WordPress database using:

- **`wp_options`** — plugin settings, runtime state, queue, AI circuit-breaker, webhooks, calendar entries, and operational metrics.
- **`wp_postmeta`** — per-post AI image flags, quality scores, SEO meta, content-refresh tracking, internal-link cluster assignments, and TF-IDF vectors for duplicate detection.

The plugin does **not** create any custom database tables. All data is stored in native WordPress tables via the standard options and post-meta APIs, making migrations between versions a matter of renaming, restructuring, or seeding option values rather than executing `ALTER TABLE` statements.

### Migration Philosophy

1. **Non-destructive first** — upgrade scripts add new keys or transform existing values; they never delete data until a verified rollback window has passed.
2. **Idempotent** — every SQL block is safe to run multiple times (uses `IF NOT EXISTS`, `INSERT IGNORE`, or explicit existence checks).
3. **Atomic per phase** — each numbered SQL block represents one logical unit; execute them in order and verify after each.
4. **Multisite-aware** — scripts include site-loop instructions for WordPress multisite networks.

---

## 2. Full Schema Reference

### 2.1 WordPress Options (`wp_options`)

All option names use the `pearblog_` prefix and are stored in the `wp_options` table (`option_name` / `option_value` columns).

#### Configuration Options

| Option Name | PHP Type | Default | Introduced | Description |
|-------------|----------|---------|------------|-------------|
| `pearblog_openai_api_key` | `string` | `''` | v4.0 | OpenAI API key |
| `pearblog_industry` | `string` | `''` | v4.0 | Content niche / industry |
| `pearblog_tone` | `string` | `'professional'` | v4.0 | Writing tone |
| `pearblog_publish_rate` | `float` | `1.0` | v4.0 | Articles per hour |
| `pearblog_language` | `string` | `'en'` | v4.0 | ISO-639-1 language code |
| `pearblog_autonomous_mode` | `bool (1/0)` | `1` | v5.3 | Enable autonomous WP-Cron pipeline |
| `pearblog_enable_image_generation` | `bool (1/0)` | `0` | v4.0 | Enable DALL-E 3 image generation |
| `pearblog_image_style` | `string` | `'realistic'` | v4.0 | DALL-E image style prompt modifier |
| `pearblog_monetization` | `string` | `'adsense'` | v4.0 | Monetization strategy |
| `pearblog_adsense_publisher_id` | `string` | `''` | v4.0 | Google AdSense Publisher ID |
| `pearblog_booking_api_key` | `string` | `''` | v5.3 | Booking.com API key |
| `pearblog_booking_affiliate_id` | `string` | `''` | v5.3 | Booking.com Affiliate ID |
| `pearblog_airbnb_api_key` | `string` | `''` | v5.3 | Airbnb API key |
| `pearblog_airbnb_affiliate_id` | `string` | `''` | v5.3 | Airbnb Affiliate ID |
| `pearblog_saas_products` | `string (JSON)` | `''` | v5.0 | JSON array of SaaS product CTAs |
| `pearblog_esp_provider` | `string` | `'mailchimp'` | v5.3 | Email service provider key |
| `pearblog_mailchimp_api_key` | `string` | `''` | v5.3 | Mailchimp API key |
| `pearblog_mailchimp_list_id` | `string` | `''` | v5.3 | Mailchimp audience/list ID |
| `pearblog_convertkit_api_key` | `string` | `''` | v5.3 | ConvertKit API key |
| `pearblog_convertkit_form_id` | `string` | `''` | v5.3 | ConvertKit form ID |
| `pearblog_digest_email` | `string` | `''` | v6.0 | Email digest recipient address |
| `pearblog_alert_slack_webhook` | `string` | `''` | v6.0 | Slack incoming webhook URL |
| `pearblog_alert_discord_webhook` | `string` | `''` | v6.0 | Discord webhook URL |
| `pearblog_alert_email` | `string` | `''` | v6.0 | Alert notification email |
| `pearblog_alert_on_publish` | `bool (1/0)` | `0` | v6.0 | Send alert on each article publish |
| `pearblog_duplicate_check_enabled` | `bool (1/0)` | `1` | v6.0 | Enable duplicate-detection before draft creation |
| `pearblog_api_key` | `string` | `''` | v5.0 | REST API authentication key |
| `pearblog_social_enabled_channels` | `string (JSON)` | `'[]'` | v6.0 | JSON array of active social channels |
| `pearblog_social_twitter_api_key` | `string` | `''` | v6.0 | Twitter/X API key |
| `pearblog_social_twitter_api_secret` | `string` | `''` | v6.0 | Twitter/X API secret |
| `pearblog_social_twitter_access_token` | `string` | `''` | v6.0 | Twitter/X access token |
| `pearblog_social_twitter_access_secret` | `string` | `''` | v6.0 | Twitter/X access token secret |
| `pearblog_social_facebook_page_id` | `string` | `''` | v6.0 | Facebook Page ID |
| `pearblog_social_facebook_page_token` | `string` | `''` | v6.0 | Facebook Page access token |
| `pearblog_social_linkedin_access_token` | `string` | `''` | v6.0 | LinkedIn access token |
| `pearblog_social_linkedin_author_urn` | `string` | `''` | v6.0 | LinkedIn author URN |

#### Runtime / State Options

| Option Name | PHP Type | Description | Autoload |
|-------------|----------|-------------|---------|
| `pearblog_topic_queue` | `array (serialized)` | FIFO queue of pending topics | `yes` |
| `pearblog_content_calendar` | `array (serialized)` | Scheduled topic entries `[{date, topic, added}]` | `no` |
| `pearblog_webhooks` | `array (serialized)` | Registered outbound webhook endpoints `[{id, url, events, secret}]` | `no` |
| `pearblog_ai_circuit_state` | `array (serialized)` | Circuit-breaker state `{failures, open, retry_after}` | `no` |
| `pearblog_ai_cost_cents` | `float` | Accumulated AI API spend in USD cents | `no` |
| `pearblog_last_pipeline_run` | `int (Unix timestamp)` | Timestamp of last successful pipeline execution | `yes` |
| `pearblog_last_digest_sent` | `string (MySQL datetime)` | Datetime of last email digest dispatch | `no` |
| `pearblog_autopilot_state` | `array (serialized)` | Autopilot execution state (tasks, completed, failed, status) | `no` |
| `pearblog_autopilot_metrics` | `array (serialized)` | Autopilot progress metrics snapshot | `no` |

#### Autoload Strategy

Options marked `yes` in the Autoload column are loaded on every page request. All runtime/state options use `autoload = no` to reduce memory overhead. This is enforced by passing `false` as the 4th parameter to `update_option()` on first write.

To audit autoload status on an existing site:

```sql
SELECT option_name, autoload, LENGTH(option_value) AS value_bytes
FROM wp_options
WHERE option_name LIKE 'pearblog_%'
ORDER BY autoload DESC, value_bytes DESC;
```

---

### 2.2 Post Meta (`wp_postmeta`)

All PearBlog meta keys are stored in `wp_postmeta` (columns: `post_id`, `meta_key`, `meta_value`).

#### SEO & Content Meta (Posts)

| Meta Key | PHP Type | Applies To | Introduced | Description |
|----------|----------|------------|------------|-------------|
| `pearblog_meta_description` | `string` | posts | v4.0 | SEO meta description |
| `_yoast_wpseo_metadesc` | `string` | posts | v4.0 | Yoast SEO meta description (compatibility write) |
| `rank_math_description` | `string` | posts | v4.0 | Rank Math description (compatibility write) |
| `pearblog_keyword_cluster` | `string` | posts | v6.0 | Keyword cluster identifier for internal linking |
| `_pearblog_internal_links_applied` | `string (MySQL datetime)` | posts | v6.0 | Timestamp when InternalLinker last processed the post |
| `pearblog_location` | `string` | posts | v5.0 | Geographic location extracted from content |

#### Content Quality Meta (Posts)

| Meta Key | PHP Type | Applies To | Introduced | Description |
|----------|----------|------------|------------|-------------|
| `_pearblog_quality_score` | `float` | posts | v6.0 | Composite quality score (0–100) |
| `_pearblog_readability_score` | `float` | posts | v6.0 | Flesch readability score |
| `_pearblog_keyword_density` | `float` | posts | v6.0 | Keyword density percentage |
| `_pearblog_heading_score` | `float` | posts | v6.0 | Heading structure score |
| `_pearblog_quality_scored_at` | `string (MySQL datetime)` | posts | v6.0 | When quality score was last computed |
| `_pearblog_tf_vector` | `array (serialized)` | posts | v6.0 | TF-IDF term-frequency vector for duplicate detection |

#### Content Refresh Meta (Posts)

| Meta Key | PHP Type | Applies To | Introduced | Description |
|----------|----------|------------|------------|-------------|
| `_pearblog_refreshed_at` | `string (MySQL datetime)` | posts | v6.0 | Datetime of last AI content refresh |
| `_pearblog_refresh_count` | `int` | posts | v6.0 | Number of times the post has been AI-refreshed |
| `_pearblog_traffic_trend` | `string` | posts | v6.0 | Traffic trend label (`declining`, `stable`, `growing`) |

#### AI Image Meta (Attachments)

| Meta Key | PHP Type | Applies To | Introduced | Description |
|----------|----------|------------|------------|-------------|
| `_pearblog_ai_generated` | `bool (1/empty)` | attachments | v6.0 | Marks image as AI-generated |
| `_pearblog_generation_date` | `int (Unix timestamp)` | attachments | v6.0 | DALL-E generation timestamp |
| `_pearblog_image_source` | `string` | attachments | v6.0 | Generation model (`dall-e-3`) |
| `_pearblog_original_width` | `int` | attachments | v6.0 | Original image width in pixels |
| `_pearblog_original_height` | `int` | attachments | v6.0 | Original image height in pixels |
| `_pearblog_canonical_description` | `string` | attachments | v6.0 | AI-generated image caption/description |

---

### 2.3 Standard WordPress Tables Used

PearBlog Engine reads and writes to the following native WP tables but does not alter their schema:

| Table | Usage |
|-------|-------|
| `wp_posts` | Creates and publishes post drafts via `wp_insert_post()` / `wp_update_post()` |
| `wp_postmeta` | Reads/writes all meta keys listed in §2.2 |
| `wp_options` | Reads/writes all options listed in §2.1 |
| `wp_term_relationships` | Assigns posts to categories/tags |

---

## 3. Compatibility Matrix

| PearBlog Version | PHP | WordPress | MySQL / MariaDB | Notes |
|-----------------|-----|-----------|-----------------|-------|
| v4.0 | 7.4+ | 5.8+ | 5.7+ / 10.2+ | Original release |
| v5.0 | 7.4+ | 6.0+ | 5.7+ / 10.4+ | Added REST API, SaaS monetization |
| v5.1 | 7.4+ | 6.0+ | 5.7+ / 10.4+ | Theme v5.1, SchemaManager |
| v5.2 | 8.0+ | 6.0+ | 5.7+ / 10.4+ | Admin UI v5.2, dark mode, DashboardWidget |
| v5.3 | 8.0+ | 6.0+ | 5.7+ / 10.4+ | Autonomous mode, email settings, affiliate fixes |
| v6.0 | 8.0+ | 6.0+ | 5.7+ / 10.4+ | Enterprise: circuit breaker, social, webhooks, autopilot |

### Plugin Compatibility

| Plugin | Compatibility | Notes |
|--------|--------------|-------|
| Yoast SEO | ✅ Full | PearBlog writes `_yoast_wpseo_metadesc`; no conflict |
| Rank Math | ✅ Full | PearBlog writes `rank_math_description`; no conflict |
| WooCommerce | ✅ Neutral | No shared data; coexists without modification |
| WP Rocket | ✅ Full | Compatible; flush cache after pipeline runs |
| W3 Total Cache | ✅ Full | Compatible; flush object cache after pipeline runs |
| WP Super Cache | ✅ Full | Compatible |
| Redis Object Cache | ✅ Full | Recommended for production |
| Akismet | ✅ Neutral | No interaction |
| WPML | ⚠️ Partial | Language-specific queues require multisite or custom `pearblog_language` per post type |

---

## 4. Backup Strategy Before Migration

**Always take a full backup before running any migration script.**

### 4.1 Database Backup

#### Option A — WP-CLI (recommended)

```bash
# Full database dump with timestamp
wp db export --add-drop-table \
  "/var/backups/pearblog/db-before-migration-$(date +%Y%m%d_%H%M%S).sql"

echo "Backup size: $(du -sh /var/backups/pearblog/db-before-migration-*.sql | tail -1)"
```

#### Option B — mysqldump (direct)

```bash
DB_NAME="your_wordpress_db"
DB_USER="your_db_user"
BACKUP_FILE="/var/backups/pearblog/db-$(date +%Y%m%d_%H%M%S).sql.gz"

mysqldump -u "$DB_USER" -p "$DB_NAME" \
  --single-transaction \
  --routines \
  --triggers \
  | gzip > "$BACKUP_FILE"

echo "Backup written: $BACKUP_FILE"
```

#### Option C — phpMyAdmin

1. Select your WordPress database.
2. **Export → Quick → Format: SQL**.
3. Ensure **Add DROP TABLE** is checked.
4. Download the `.sql` file to a safe location.

### 4.2 PearBlog-Specific Options Backup

Export only PearBlog options for a fast targeted restore:

```sql
-- Export PearBlog options to a restore script
SELECT CONCAT(
  'INSERT INTO wp_options (option_name, option_value, autoload) VALUES (',
  QUOTE(option_name), ', ', QUOTE(option_value), ', ', QUOTE(autoload), ') ',
  'ON DUPLICATE KEY UPDATE option_value = VALUES(option_value);'
) AS restore_sql
FROM wp_options
WHERE option_name LIKE 'pearblog_%'
ORDER BY option_name;
```

Save the output as `pearblog-options-backup-YYYYMMDD.sql` for targeted rollback.

### 4.3 Files Backup

```bash
WP_PATH="/var/www/html"
BACKUP_DIR="/var/backups/pearblog"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

mkdir -p "$BACKUP_DIR"

tar -czf "$BACKUP_DIR/pearblog-engine-$TIMESTAMP.tar.gz" \
  "$WP_PATH/wp-content/mu-plugins/pearblog-engine/"

tar -czf "$BACKUP_DIR/pearblog-theme-$TIMESTAMP.tar.gz" \
  "$WP_PATH/wp-content/themes/pearblog-theme/"

echo "File backups complete."
```

### 4.4 Pre-Migration Verification

Before running any upgrade script, confirm the current plugin version and schema state:

```bash
# Check plugin version from file header
grep "Version:" \
  /var/www/html/wp-content/mu-plugins/pearblog-engine/pearblog-engine.php

# List all current PearBlog options
wp option list --search="pearblog_*" --fields=option_name,option_value

# Count PearBlog post meta rows
wp db query "SELECT meta_key, COUNT(*) as count
  FROM $(wp db prefix)postmeta
  WHERE meta_key LIKE '%pearblog%' OR meta_key LIKE '%_pearblog%'
  GROUP BY meta_key ORDER BY count DESC;"
```

---

## 5. Upgrade Paths

> **Convention:** All SQL scripts use `wp_` as the table prefix. Replace with your actual prefix if different (check `$table_prefix` in `wp-config.php`). Use `wp db prefix` to look it up automatically.

```bash
# Get your actual table prefix
wp db prefix
```

---

### 5.1 v4.x → v5.0

**New in v5.0:** REST API key, SaaS products monetization option, location meta.

```sql
-- ─── v4.x → v5.0 upgrade ──────────────────────────────────────────────────

-- 5.0.1  Add REST API key option (if not present)
INSERT IGNORE INTO wp_options (option_name, option_value, autoload)
VALUES ('pearblog_api_key', '', 'no');

-- 5.0.2  Add SaaS products option (if not present)
INSERT IGNORE INTO wp_options (option_name, option_value, autoload)
VALUES ('pearblog_saas_products', '', 'no');

-- 5.0.3  Verify (expected: 2 rows)
SELECT option_name, option_value
FROM wp_options
WHERE option_name IN ('pearblog_api_key', 'pearblog_saas_products');
```

**WP-CLI equivalent:**

```bash
wp option add pearblog_api_key '' --autoload=no 2>/dev/null || true
wp option add pearblog_saas_products '' --autoload=no 2>/dev/null || true
```

---

### 5.2 v5.0 → v5.1

**New in v5.1:** SchemaManager (no new options); InternalLinker cluster meta introduced.  
No database-level changes required — new meta keys are written lazily on first pipeline run.

```sql
-- ─── v5.0 → v5.1 upgrade ──────────────────────────────────────────────────
-- No schema changes required for v5.1.
-- The InternalLinker 'pearblog_keyword_cluster' meta is created automatically
-- during the next content pipeline run.
SELECT 'v5.0 → v5.1: no database changes required' AS status;
```

---

### 5.3 v5.1 → v5.2 / v5.3

**New in v5.2/v5.3:** Autonomous mode toggle, email marketing settings, affiliate API keys, booking/Airbnb, additional admin UI options.

```sql
-- ─── v5.1 → v5.2/v5.3 upgrade ──────────────────────────────────────────────

-- 5.3.1  Autonomous mode (default ON to preserve existing behaviour)
INSERT IGNORE INTO wp_options (option_name, option_value, autoload)
VALUES ('pearblog_autonomous_mode', '1', 'yes');

-- 5.3.2  Email service provider settings
INSERT IGNORE INTO wp_options (option_name, option_value, autoload)
VALUES
  ('pearblog_esp_provider',      'mailchimp', 'no'),
  ('pearblog_mailchimp_api_key', '',          'no'),
  ('pearblog_mailchimp_list_id', '',          'no'),
  ('pearblog_convertkit_api_key','',          'no'),
  ('pearblog_convertkit_form_id','',          'no');

-- 5.3.3  Affiliate settings
INSERT IGNORE INTO wp_options (option_name, option_value, autoload)
VALUES
  ('pearblog_booking_api_key',      '', 'no'),
  ('pearblog_booking_affiliate_id', '', 'no'),
  ('pearblog_airbnb_api_key',       '', 'no'),
  ('pearblog_airbnb_affiliate_id',  '', 'no');

-- 5.3.4  Verify (expected: 10 rows)
SELECT option_name
FROM wp_options
WHERE option_name IN (
  'pearblog_autonomous_mode',
  'pearblog_esp_provider', 'pearblog_mailchimp_api_key', 'pearblog_mailchimp_list_id',
  'pearblog_convertkit_api_key', 'pearblog_convertkit_form_id',
  'pearblog_booking_api_key', 'pearblog_booking_affiliate_id',
  'pearblog_airbnb_api_key', 'pearblog_airbnb_affiliate_id'
)
ORDER BY option_name;
```

**WP-CLI equivalent:**

```bash
wp option add pearblog_autonomous_mode 1 --autoload=yes 2>/dev/null || true

for opt in pearblog_esp_provider pearblog_mailchimp_api_key pearblog_mailchimp_list_id \
           pearblog_convertkit_api_key pearblog_convertkit_form_id \
           pearblog_booking_api_key pearblog_booking_affiliate_id \
           pearblog_airbnb_api_key pearblog_airbnb_affiliate_id; do
  wp option add "$opt" '' --autoload=no 2>/dev/null || true
done
```

---

### 5.4 v5.x → v6.0 (major upgrade)

**New in v6.0:** Circuit breaker state, AI cost tracking, AlertManager settings, social publishing credentials, duplicate-detection options, content calendar, webhooks, email digest, autopilot state, quality scoring meta, content refresh meta, AI image meta.

Run these blocks in order. Each block is idempotent and safe to re-run.

```sql
-- ─── v5.x → v6.0 upgrade ──────────────────────────────────────────────────

-- ═══════════════════════════════════════════════════════════════════
-- BLOCK 6.1 — AI circuit breaker & cost tracking
-- ═══════════════════════════════════════════════════════════════════

INSERT IGNORE INTO wp_options (option_name, option_value, autoload)
VALUES
  ('pearblog_ai_circuit_state',
   'a:3:{s:8:"failures";i:0;s:4:"open";b:0;s:11:"retry_after";i:0;}',
   'no'),
  ('pearblog_ai_cost_cents', '0', 'no');

-- ═══════════════════════════════════════════════════════════════════
-- BLOCK 6.2 — Monitoring / alert settings
-- ═══════════════════════════════════════════════════════════════════

INSERT IGNORE INTO wp_options (option_name, option_value, autoload)
VALUES
  ('pearblog_alert_slack_webhook',   '', 'no'),
  ('pearblog_alert_discord_webhook', '', 'no'),
  ('pearblog_alert_email',           '', 'no'),
  ('pearblog_alert_on_publish',      '0','no');

-- ═══════════════════════════════════════════════════════════════════
-- BLOCK 6.3 — Duplicate detection
-- ═══════════════════════════════════════════════════════════════════

INSERT IGNORE INTO wp_options (option_name, option_value, autoload)
VALUES ('pearblog_duplicate_check_enabled', '1', 'yes');

-- ═══════════════════════════════════════════════════════════════════
-- BLOCK 6.4 — Social publishing credentials
-- ═══════════════════════════════════════════════════════════════════

INSERT IGNORE INTO wp_options (option_name, option_value, autoload)
VALUES
  ('pearblog_social_enabled_channels',      '[]', 'no'),
  ('pearblog_social_twitter_api_key',        '', 'no'),
  ('pearblog_social_twitter_api_secret',     '', 'no'),
  ('pearblog_social_twitter_access_token',   '', 'no'),
  ('pearblog_social_twitter_access_secret',  '', 'no'),
  ('pearblog_social_facebook_page_id',       '', 'no'),
  ('pearblog_social_facebook_page_token',    '', 'no'),
  ('pearblog_social_linkedin_access_token',  '', 'no'),
  ('pearblog_social_linkedin_author_urn',    '', 'no');

-- ═══════════════════════════════════════════════════════════════════
-- BLOCK 6.5 — Content calendar, webhooks, email digest
-- ═══════════════════════════════════════════════════════════════════

INSERT IGNORE INTO wp_options (option_name, option_value, autoload)
VALUES
  ('pearblog_content_calendar', 'a:0:{}', 'no'),
  ('pearblog_webhooks',         'a:0:{}', 'no'),
  ('pearblog_digest_email',     '',       'no'),
  ('pearblog_last_digest_sent', '',       'no');

-- ═══════════════════════════════════════════════════════════════════
-- BLOCK 6.6 — Autopilot state & metrics
-- ═══════════════════════════════════════════════════════════════════

INSERT IGNORE INTO wp_options (option_name, option_value, autoload)
VALUES
  ('pearblog_autopilot_state',
   'a:8:{s:6:"status";s:4:"idle";s:4:"mode";s:0:"";s:12:"current_task";N;s:5:"tasks";a:0:{}s:9:"completed";a:0:{}s:6:"failed";a:0:{}s:10:"start_time";N;s:10:"pause_time";N;}',
   'no'),
  ('pearblog_autopilot_metrics', 'a:0:{}', 'no');

-- ═══════════════════════════════════════════════════════════════════
-- BLOCK 6.7 — Verify: count new v6.0 option rows
-- Expected: 24 rows
-- ═══════════════════════════════════════════════════════════════════

SELECT COUNT(*) AS v6_option_count
FROM wp_options
WHERE option_name IN (
  'pearblog_ai_circuit_state', 'pearblog_ai_cost_cents',
  'pearblog_alert_slack_webhook', 'pearblog_alert_discord_webhook',
  'pearblog_alert_email', 'pearblog_alert_on_publish',
  'pearblog_duplicate_check_enabled',
  'pearblog_social_enabled_channels',
  'pearblog_social_twitter_api_key', 'pearblog_social_twitter_api_secret',
  'pearblog_social_twitter_access_token', 'pearblog_social_twitter_access_secret',
  'pearblog_social_facebook_page_id', 'pearblog_social_facebook_page_token',
  'pearblog_social_linkedin_access_token', 'pearblog_social_linkedin_author_urn',
  'pearblog_content_calendar', 'pearblog_webhooks',
  'pearblog_digest_email', 'pearblog_last_digest_sent',
  'pearblog_autopilot_state', 'pearblog_autopilot_metrics',
  'pearblog_last_pipeline_run', 'pearblog_last_digest_sent'
);
-- Note: 'pearblog_last_pipeline_run' may already exist from CronManager;
-- the INSERT IGNORE above skips it safely.
```

#### Post-Meta Initialisation (v6.0)

Post meta keys for quality scoring, TF-IDF vectors, refresh tracking, and AI images are written lazily during pipeline runs. No pre-population is needed. However, to back-fill quality scores on existing posts:

```bash
# Trigger quality scorer on all published posts
wp post list --post_status=publish --format=ids | \
  xargs -n1 -I{} wp eval "
    \$qs = new PearBlogEngine\\Content\\QualityScorer();
    \$qs->score({});
    echo 'Scored post {}\\n';
  "
```

---

### 5.5 Fresh Install (v6.0)

On a fresh WordPress installation, all required options are seeded automatically when the plugin boots for the first time (`Plugin::boot()`). No manual SQL is required.

To verify post-activation:

```bash
wp option list --search="pearblog_*" --fields=option_name | wc -l
# Expected: ≥ 36 options
```

---

## 6. Rollback Procedures

All rollback procedures assume you have a pre-migration backup (see §4).

### 6.1 v6.0 → v5.x Rollback

#### Step 1 — Restore files

```bash
BACKUP_DIR="/var/backups/pearblog"
WP_PATH="/var/www/html"

# Restore the previous plugin version
tar -xzf "$BACKUP_DIR/pearblog-engine-YYYYMMDD_HHMMSS.tar.gz" \
  -C "$WP_PATH/wp-content/mu-plugins/"

# Restore the previous theme version
tar -xzf "$BACKUP_DIR/pearblog-theme-YYYYMMDD_HHMMSS.tar.gz" \
  -C "$WP_PATH/wp-content/themes/"

wp cache flush
```

#### Step 2 — Remove v6.0-only options

> **Safety:** This script removes options that did not exist before v6.0. It will not affect any option that was already present in v5.x.

```sql
-- ─── v6.0 → v5.x rollback: remove v6.0-only options ─────────────────────

DELETE FROM wp_options
WHERE option_name IN (
  -- AI circuit breaker & cost
  'pearblog_ai_circuit_state',
  'pearblog_ai_cost_cents',
  -- Alert / monitoring
  'pearblog_alert_slack_webhook',
  'pearblog_alert_discord_webhook',
  'pearblog_alert_email',
  'pearblog_alert_on_publish',
  -- Duplicate detection
  'pearblog_duplicate_check_enabled',
  -- Social publishing
  'pearblog_social_enabled_channels',
  'pearblog_social_twitter_api_key',
  'pearblog_social_twitter_api_secret',
  'pearblog_social_twitter_access_token',
  'pearblog_social_twitter_access_secret',
  'pearblog_social_facebook_page_id',
  'pearblog_social_facebook_page_token',
  'pearblog_social_linkedin_access_token',
  'pearblog_social_linkedin_author_urn',
  -- Content calendar / webhooks / digest
  'pearblog_content_calendar',
  'pearblog_webhooks',
  'pearblog_digest_email',
  'pearblog_last_digest_sent',
  -- Autopilot
  'pearblog_autopilot_state',
  'pearblog_autopilot_metrics'
);
```

#### Step 3 — Remove v6.0-only post meta

> These meta keys were not written by v5.x. Removing them has no effect on published content.

```sql
-- ─── v6.0 → v5.x rollback: remove v6.0-only post meta ───────────────────

DELETE FROM wp_postmeta
WHERE meta_key IN (
  '_pearblog_quality_score',
  '_pearblog_readability_score',
  '_pearblog_keyword_density',
  '_pearblog_heading_score',
  '_pearblog_quality_scored_at',
  '_pearblog_tf_vector',
  '_pearblog_refreshed_at',
  '_pearblog_refresh_count',
  '_pearblog_traffic_trend',
  '_pearblog_ai_generated',
  '_pearblog_generation_date',
  '_pearblog_image_source',
  '_pearblog_original_width',
  '_pearblog_original_height',
  '_pearblog_canonical_description',
  '_pearblog_internal_links_applied'
);

-- Verify: expected 0 rows remaining
SELECT COUNT(*) AS remaining_v6_meta
FROM wp_postmeta
WHERE meta_key IN (
  '_pearblog_quality_score', '_pearblog_tf_vector',
  '_pearblog_ai_generated',  '_pearblog_refreshed_at'
);
```

#### Step 4 — Flush caches

```bash
wp cache flush
wp cron event list   # verify pearblog_pipeline_cron is still scheduled
```

---

### 6.2 v5.x → v4.x Rollback

#### Remove v5.x options

```sql
-- ─── v5.x → v4.x rollback ────────────────────────────────────────────────

DELETE FROM wp_options
WHERE option_name IN (
  'pearblog_api_key',
  'pearblog_saas_products',
  'pearblog_autonomous_mode',
  'pearblog_esp_provider',
  'pearblog_mailchimp_api_key',
  'pearblog_mailchimp_list_id',
  'pearblog_convertkit_api_key',
  'pearblog_convertkit_form_id',
  'pearblog_booking_api_key',
  'pearblog_booking_affiliate_id',
  'pearblog_airbnb_api_key',
  'pearblog_airbnb_affiliate_id'
);

-- Verify: expected 0 rows
SELECT COUNT(*) AS remaining_v5x
FROM wp_options
WHERE option_name IN (
  'pearblog_api_key', 'pearblog_saas_products', 'pearblog_autonomous_mode'
);
```

---

### 6.3 Partial Rollback (Options Only)

Use this when you need to reset a specific feature without rolling back the entire plugin version. These scripts are idempotent — safe to run on any v6.0 installation.

#### Reset circuit breaker

```sql
UPDATE wp_options
SET option_value = 'a:3:{s:8:"failures";i:0;s:4:"open";b:0;s:11:"retry_after";i:0;}'
WHERE option_name = 'pearblog_ai_circuit_state';
```

```bash
# WP-CLI equivalent
wp pearblog circuit reset
```

#### Reset AI cost counter

```sql
UPDATE wp_options SET option_value = '0'
WHERE option_name = 'pearblog_ai_cost_cents';
```

#### Clear topic queue

```sql
UPDATE wp_options SET option_value = 'a:0:{}'
WHERE option_name = 'pearblog_topic_queue';
```

```bash
wp pearblog queue clear
```

#### Clear autopilot state

```sql
DELETE FROM wp_options
WHERE option_name IN ('pearblog_autopilot_state', 'pearblog_autopilot_metrics');
```

#### Remove all quality scores (force re-score)

```sql
DELETE FROM wp_postmeta
WHERE meta_key IN (
  '_pearblog_quality_score',
  '_pearblog_readability_score',
  '_pearblog_keyword_density',
  '_pearblog_heading_score',
  '_pearblog_quality_scored_at'
);
```

#### Remove all TF-IDF vectors (force re-index)

```sql
DELETE FROM wp_postmeta
WHERE meta_key = '_pearblog_tf_vector';
```

---

## 7. WP-CLI Migration Commands

All migration operations can be performed via WP-CLI without direct database access.

### Plugin State Commands

```bash
# Show current PearBlog options (audit)
wp option list --search="pearblog_*"

# Show pipeline status
wp pearblog stats

# Show autopilot status
wp pearblog autopilot status

# Show circuit breaker status
wp pearblog circuit status

# Reset circuit breaker
wp pearblog circuit reset

# Show queue contents
wp pearblog queue list

# Clear queue
wp pearblog queue clear
```

### Full v5.x → v6.0 Migration via WP-CLI

```bash
#!/usr/bin/env bash
# wp-migrate-v5-to-v6.sh
# Usage: bash wp-migrate-v5-to-v6.sh /var/www/html
# ──────────────────────────────────────────────────
set -euo pipefail

WP_PATH="${1:?Usage: $0 <wp-path>}"
WP="wp --path=$WP_PATH --allow-root"

echo "═══════════════════════════════════════════"
echo " PearBlog Engine: v5.x → v6.0 Migration"
echo "═══════════════════════════════════════════"
echo ""

# ── Backup ───────────────────────────────────────
BACKUP_FILE="/var/backups/pearblog/db-pre-v6-$(date +%Y%m%d_%H%M%S).sql"
echo "1. Creating database backup → $BACKUP_FILE"
$WP db export --add-drop-table "$BACKUP_FILE"

# ── v5.3 options (idempotent) ────────────────────
echo "2. Seeding v5.x options (if missing)..."
$WP option add pearblog_autonomous_mode 1 --autoload=yes 2>/dev/null || true
for opt in pearblog_esp_provider pearblog_mailchimp_api_key \
           pearblog_mailchimp_list_id pearblog_convertkit_api_key \
           pearblog_convertkit_form_id pearblog_booking_api_key \
           pearblog_booking_affiliate_id pearblog_airbnb_api_key \
           pearblog_airbnb_affiliate_id; do
  $WP option add "$opt" '' --autoload=no 2>/dev/null || true
done

# ── v6.0 options ─────────────────────────────────
echo "3. Seeding v6.0 options..."

# AI circuit breaker
$WP option add pearblog_ai_circuit_state \
  '{"failures":0,"open":false,"retry_after":0}' --autoload=no 2>/dev/null || true
$WP option add pearblog_ai_cost_cents 0 --autoload=no 2>/dev/null || true

# Alert settings
for opt in pearblog_alert_slack_webhook pearblog_alert_discord_webhook \
           pearblog_alert_email; do
  $WP option add "$opt" '' --autoload=no 2>/dev/null || true
done
$WP option add pearblog_alert_on_publish 0 --autoload=no 2>/dev/null || true

# Duplicate detection
$WP option add pearblog_duplicate_check_enabled 1 --autoload=yes 2>/dev/null || true

# Social credentials
for opt in pearblog_social_twitter_api_key pearblog_social_twitter_api_secret \
           pearblog_social_twitter_access_token pearblog_social_twitter_access_secret \
           pearblog_social_facebook_page_id pearblog_social_facebook_page_token \
           pearblog_social_linkedin_access_token pearblog_social_linkedin_author_urn; do
  $WP option add "$opt" '' --autoload=no 2>/dev/null || true
done
$WP option add pearblog_social_enabled_channels '[]' --autoload=no 2>/dev/null || true

# Content calendar, webhooks, digest
$WP option add pearblog_content_calendar '{}' --autoload=no 2>/dev/null || true
$WP option add pearblog_webhooks '{}' --autoload=no 2>/dev/null || true
$WP option add pearblog_digest_email '' --autoload=no 2>/dev/null || true
$WP option add pearblog_last_digest_sent '' --autoload=no 2>/dev/null || true

# ── Flush caches ─────────────────────────────────
echo "4. Flushing caches..."
$WP cache flush

# ── Verify ───────────────────────────────────────
echo "5. Verification..."
COUNT=$($WP option list --search="pearblog_*" --format=count)
echo "   Total PearBlog options: $COUNT (expected ≥ 36)"

echo ""
echo "✅ Migration v5.x → v6.0 complete."
echo "   Backup: $BACKUP_FILE"
```

### Verify Migration Success

```bash
WP_PATH="/var/www/html"
wp --path="$WP_PATH" option list --search="pearblog_*" \
  --fields=option_name,autoload \
  --format=table
```

Expected: all 36+ options present with correct autoload values.

---

## 8. Data Integrity Verification

Run these queries after any migration to confirm data integrity.

### 8.1 Option Count Check

```sql
-- Should return ≥ 36 for a v6.0 installation
SELECT COUNT(*) AS pearblog_option_count
FROM wp_options
WHERE option_name LIKE 'pearblog_%';
```

### 8.2 Critical Options Present

```sql
SELECT
  option_name,
  CASE WHEN LENGTH(option_value) > 0 THEN 'SET' ELSE 'EMPTY' END AS status
FROM wp_options
WHERE option_name IN (
  'pearblog_openai_api_key',
  'pearblog_industry',
  'pearblog_language',
  'pearblog_publish_rate',
  'pearblog_autonomous_mode',
  'pearblog_ai_circuit_state',
  'pearblog_topic_queue'
)
ORDER BY option_name;
```

### 8.3 Autoload Audit

```sql
-- Ensure non-sensitive runtime options are NOT autoloaded
SELECT option_name, autoload
FROM wp_options
WHERE option_name LIKE 'pearblog_%'
  AND option_name NOT IN (
    'pearblog_autonomous_mode',
    'pearblog_duplicate_check_enabled',
    'pearblog_last_pipeline_run',
    'pearblog_topic_queue'
  )
  AND autoload = 'yes';
-- Expected: 0 rows (nothing else should be autoloaded)
```

### 8.4 Post Meta Counts

```sql
-- Count PearBlog post meta by key
SELECT meta_key, COUNT(*) AS post_count
FROM wp_postmeta
WHERE meta_key LIKE '%pearblog%'
   OR meta_key LIKE '_pearblog%'
GROUP BY meta_key
ORDER BY post_count DESC;
```

### 8.5 Orphaned Meta Check

```sql
-- Find PearBlog post meta pointing to non-existent posts
SELECT pm.meta_key, COUNT(*) AS orphan_count
FROM wp_postmeta pm
LEFT JOIN wp_posts p ON p.ID = pm.post_id
WHERE (pm.meta_key LIKE '%pearblog%' OR pm.meta_key LIKE '_pearblog%')
  AND p.ID IS NULL
GROUP BY pm.meta_key
HAVING orphan_count > 0;
-- Expected: 0 rows (no orphaned meta)
```

Cleanup orphaned meta (run only if the above returns rows):

```sql
DELETE pm FROM wp_postmeta pm
LEFT JOIN wp_posts p ON p.ID = pm.post_id
WHERE (pm.meta_key LIKE '%pearblog%' OR pm.meta_key LIKE '_pearblog%')
  AND p.ID IS NULL;
```

### 8.6 Circuit Breaker State

```sql
SELECT option_value
FROM wp_options
WHERE option_name = 'pearblog_ai_circuit_state';
-- Expected: serialized array with open=false and failures=0 (for healthy state)
```

```bash
# WP-CLI version
wp pearblog circuit status
```

---

## 9. Multisite Considerations

On a WordPress multisite network, PearBlog Engine stores per-site data using blog-specific option tables (`wp_2_options`, `wp_3_options`, etc.).

### Migration for All Sites

```bash
#!/usr/bin/env bash
# migrate-multisite-v5-to-v6.sh
set -euo pipefail

WP_PATH="/var/www/html"
WP="wp --path=$WP_PATH --allow-root"

echo "Migrating all sites in network..."

for SITE_ID in $($WP site list --field=blog_id --format=csv); do
  echo "── Site $SITE_ID ───────────────────────────"

  $WP option add pearblog_ai_circuit_state \
    '{"failures":0,"open":false,"retry_after":0}' \
    --autoload=no --url="$($WP site url --blog_id=$SITE_ID)" 2>/dev/null || true

  $WP option add pearblog_duplicate_check_enabled 1 \
    --autoload=yes --url="$($WP site url --blog_id=$SITE_ID)" 2>/dev/null || true

  # Repeat for all v6.0 options as needed...
  echo "   Site $SITE_ID: done"
done

echo ""
echo "✅ All sites migrated."
```

### Network-Level Options

Network-wide options (stored in `wp_sitemeta`) are not used by PearBlog Engine. All settings are per-site.

---

## 10. Troubleshooting

### Option Not Found After Migration

```bash
# Check if the option exists at all
wp option get pearblog_openai_api_key

# If missing, add it
wp option add pearblog_openai_api_key '' --autoload=no
```

### Serialization Errors

If an option value stored as a serialized PHP array becomes corrupt (e.g., after a string search-replace on the database):

```bash
# Inspect raw value
wp option get pearblog_topic_queue --format=json

# Reset to empty array
wp option update pearblog_topic_queue '[]' --format=json
```

> **Warning:** Never use a plain text search-replace on serialized option values. Use WP-CLI's `wp search-replace` with `--precise` flag, or better, use the JSON format where possible.

### Post Meta Bloat (large TF-IDF vectors)

The `_pearblog_tf_vector` meta stores a serialized associative array of term frequencies. On sites with thousands of posts, this can grow large. To audit:

```sql
SELECT post_id,
       LENGTH(meta_value) AS vector_bytes
FROM wp_postmeta
WHERE meta_key = '_pearblog_tf_vector'
ORDER BY vector_bytes DESC
LIMIT 20;
```

To purge and allow re-indexing on next duplicate check:

```sql
DELETE FROM wp_postmeta WHERE meta_key = '_pearblog_tf_vector';
```

### Autoload Bloat

If the `pearblog_topic_queue` option grows very large (thousands of topics), consider splitting the queue into batches or setting it to `autoload=no`:

```sql
UPDATE wp_options
SET autoload = 'no'
WHERE option_name = 'pearblog_topic_queue';
```

### Missing `pearblog_last_pipeline_run`

```bash
wp option add pearblog_last_pipeline_run 0 --autoload=yes 2>/dev/null || true
# Then trigger a manual pipeline run to populate it
wp pearblog generate
```

---

## Related Documentation

| Document | Purpose |
|----------|---------|
| [DEPLOYMENT.md](DEPLOYMENT.md) | Full production deployment guide |
| [DISASTER-RECOVERY.md](DISASTER-RECOVERY.md) | Backup, restore, and failover procedures |
| [PRODUCTION-CHECKLIST.md](PRODUCTION-CHECKLIST.md) | Pre-launch and weekly operations checklists |
| [PRODUCTION-ANALYSIS-FULL.md](PRODUCTION-ANALYSIS-FULL.md) | Full production operations manual |
| [ENTERPRISE-AUTOPILOT-TASKLIST.md](ENTERPRISE-AUTOPILOT-TASKLIST.md) | 26-task autopilot execution plan |

---

*PearBlog Engine v6.0.0 — Enterprise-ready autonomous content system*

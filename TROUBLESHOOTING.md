# 🔧 TROUBLESHOOTING GUIDE — PearBlog Engine

This guide covers the most common issues encountered when running PearBlog Engine and how to resolve them.

---

## 📑 TABLE OF CONTENTS

1. [AI Content Generation Issues](#1-ai-content-generation-issues)
2. [Pipeline Not Running](#2-pipeline-not-running)
3. [Image Generation Issues](#3-image-generation-issues)
4. [Circuit Breaker Open](#4-circuit-breaker-open)
5. [REST API / Automation Issues](#5-rest-api--automation-issues)
6. [Database / Performance Issues](#6-database--performance-issues)
7. [Admin Panel Issues](#7-admin-panel-issues)
8. [Email Digest Issues](#8-email-digest-issues)
9. [Social Media Publishing Issues](#9-social-media-publishing-issues)
10. [WP-CLI Reference](#10-wp-cli-reference)
11. [Diagnostics Checklist](#11-diagnostics-checklist)

---

## 1. AI Content Generation Issues

### Problem: "API key is not configured" error
**Cause:** The OpenAI API key has not been set or is empty.

**Solution:**
1. Go to **PearBlog Engine → General** tab in the WordPress admin.
2. Enter your OpenAI API key in the **OpenAI API Key** field and save.
3. Alternatively, add to `wp-config.php`:
   ```php
   define( 'PEARBLOG_OPENAI_API_KEY', 'sk-...' );
   ```

---

### Problem: "Invalid API key (401)" in health check
**Cause:** The stored API key is wrong or has been revoked.

**Solution:**
1. Log in to [platform.openai.com](https://platform.openai.com).
2. Regenerate your API key.
3. Update the key in **PearBlog Engine → General**.

---

### Problem: "Rate limited (429)" in health check
**Cause:** You have hit the OpenAI API rate limit or spending cap.

**Solution:**
1. Check your OpenAI account for usage limits.
2. Increase your spending limit or wait for the rate limit window to reset.
3. Reduce the **Publish Rate** setting to generate fewer articles per cycle.
4. The built-in circuit breaker will automatically back off and retry.

---

### Problem: Generated articles are too short / low quality
**Cause:** The AI prompt may not be specific enough for your niche.

**Solution:**
1. Set a specific **Industry / Niche** (e.g., "health and wellness", "personal finance").
2. The `PromptBuilderFactory` auto-selects a specialised prompt builder for your niche.
3. Use more specific topic titles (e.g., "Top 10 hiking trails in Tatry mountains" vs "hiking").
4. Check the **Quality Score** in **PearBlog Engine → Queue** to see why articles score low.

---

### Problem: "Duplicate content detected" — articles not published
**Cause:** The `DuplicateDetector` is blocking new articles because they are too similar to existing ones (≥80% similarity by default).

**Solution:**
1. Use more unique, specific topics.
2. If the check is too aggressive, disable it temporarily: **PearBlog Engine → Automation → Duplicate Detection → uncheck Enable**.
3. Or via WP-CLI: `wp option update pearblog_duplicate_check_enabled 0`
4. Check if old articles are being indexed: `wp pearblog duplicate` to inspect the index.

---

## 2. Pipeline Not Running

### Problem: Pipeline has not run / articles not being published
**Cause:** WP-Cron may not be running, or autonomous mode is disabled.

**Solution:**
1. Verify autonomous mode is enabled: **PearBlog Engine → General → Autonomous Mode**.
2. Check if WP-Cron is configured. Add to `wp-config.php` if needed:
   ```php
   define( 'DISABLE_WP_CRON', false );
   ```
3. Verify the cron event is scheduled:
   ```bash
   wp cron event list | grep pearblog
   ```
4. Run the pipeline manually to test:
   ```bash
   wp pearblog queue run
   ```
5. If using a VPS/dedicated server, set up a real cron job instead of WP-Cron:
   ```cron
   */5 * * * * curl -s "https://yoursite.com/wp-cron.php?doing_wp_cron" > /dev/null 2>&1
   ```

---

### Problem: Pipeline runs but posts are not published
**Cause:** Posts may be created as drafts, or the queue may be empty.

**Solution:**
1. Check if the topic queue has topics: **PearBlog Engine → Queue** tab.
2. Check **wp-admin → Posts → All Posts → Drafts** for generated articles.
3. Run with debug mode to see detailed output:
   ```bash
   wp pearblog generate --topic="Test topic" --dry-run
   ```

---

### Problem: "Queue is empty" warning when pipeline runs
**Cause:** No topics have been added to the queue.

**Solution:**
1. Add topics in **PearBlog Engine → Queue → Add Topics to Queue**.
2. Or via WP-CLI:
   ```bash
   wp pearblog queue add "Topic 1" "Topic 2" "Topic 3"
   ```

---

## 3. Image Generation Issues

### Problem: Featured images not being generated
**Cause:** Image generation may be disabled, or there is an API issue.

**Solution:**
1. Check **PearBlog Engine → AI Images → Enable Image Generation** is checked.
2. Verify your OpenAI account has access to DALL-E 3.
3. Check the PHP error log for `wp_remote_post` errors.
4. Manually trigger image generation:
   ```bash
   wp pearblog generate-images --post-id=123
   ```

---

### Problem: "Image generation failed: content policy violation"
**Cause:** OpenAI's content policy blocked the image prompt.

**Solution:**
1. Review the article topic and modify to avoid policy-sensitive terms.
2. Change the **Image Style** setting to "Minimal / Clean" which generates safer prompts.
3. The pipeline will still publish the article without a featured image.

---

### Problem: Images generated but alt text is missing
**Cause:** The AI image analyzer may not have processed recent images.

**Solution:**
1. Run the bulk alt text fixer: **PearBlog Engine → AI Images → Fix Missing Alt Texts**.
2. Or via WP-CLI:
   ```bash
   wp pearblog links fix-alts
   ```

---

## 4. Circuit Breaker Open

### Problem: "Circuit breaker OPEN — AI calls blocked"
**Cause:** The circuit breaker opened after detecting too many consecutive API failures (default: 5 failures within 5 minutes).

**Symptoms:**
- Health endpoint returns `"circuit_breaker": {"status": "error", "detail": "OPEN – AI calls blocked"}`
- Pipeline logs show: "Circuit breaker is open. Skipping AI call."

**Solution:**
1. **Wait** — the circuit breaker automatically resets after 5 minutes (the cooldown period).
2. **Or reset manually:**
   ```bash
   wp pearblog circuit reset
   ```
3. Investigate the underlying cause (API key, rate limits, network issues) before resetting.

---

## 5. REST API / Automation Issues

### Problem: GitHub Actions getting 401 Unauthorized
**Cause:** The Automation API key is not set or does not match.

**Solution:**
1. Set the API key in **PearBlog Engine → General → Automation API Key**.
2. Store the same key as `API_KEY` in your GitHub repository secrets.
3. Test the endpoint:
   ```bash
   curl -X POST https://yoursite.com/wp-json/pearblog/v1/generate \
     -H "Authorization: Bearer YOUR_API_KEY" \
     -H "Content-Type: application/json" \
     -d '{"topic": "Test topic"}'
   ```

---

### Problem: REST endpoint returns 404
**Cause:** Pretty permalinks may not be enabled, or WordPress REST API is disabled by a security plugin.

**Solution:**
1. Go to **Settings → Permalinks** and save (regenerates `.htaccess`).
2. Check if a security plugin is blocking REST API access.
3. Test the REST API base: `https://yoursite.com/wp-json/`

---

### Problem: Automation script receives 429 Too Many Requests
**Cause:** Too many pipeline runs triggered in a short time.

**Solution:**
1. Add rate limiting to your automation script (add a delay between requests).
2. The circuit breaker may have opened — check the health endpoint.

---

## 6. Database / Performance Issues

### Problem: Pipeline is slow (> 60 seconds per article)
**Cause:** OpenAI API latency, slow image generation, or many DB queries.

**Solution:**
1. Check the **Monitoring → Performance** tab for `avg_duration_24h`.
2. Reduce the number of DB queries by enabling the content cache.
3. The `ContentCache` class caches AI content and linking candidates.
4. Consider disabling image generation if speed is critical.
5. Check OpenAI's status page for ongoing degradation.

---

### Problem: High memory usage (> 256 MB per pipeline run)
**Cause:** Very long articles being processed, or large post_list for duplicate detection.

**Solution:**
1. Check the **Monitoring → Performance** tab for `avg_memory_24h_mb`.
2. Increase PHP memory limit: `define('WP_MEMORY_LIMIT', '512M');` in `wp-config.php`.
3. Reduce the duplicate detection scope by limiting the number of indexed posts.

---

## 7. Admin Panel Issues

### Problem: Admin panel shows a blank page
**Cause:** PHP fatal error in admin rendering.

**Solution:**
1. Enable `define('WP_DEBUG', true)` in `wp-config.php` temporarily to see the error.
2. Check the PHP error log.
3. Deactivate and reactivate the plugin.

---

### Problem: Settings not saving
**Cause:** Nonce mismatch or capability issue.

**Solution:**
1. Ensure you are logged in as an admin.
2. Try clearing browser cache and reloading the settings page.
3. Check for JavaScript errors in the browser console that might prevent form submission.

---

### Problem: Onboarding wizard shows on every page load
**Cause:** The wizard completion flag has not been set.

**Solution:**
1. Complete the wizard, or click "Skip wizard and configure manually".
2. Or manually mark it complete:
   ```bash
   wp option update pearblog_onboarding_complete 1
   ```

---

## 8. Email Digest Issues

### Problem: Weekly digest emails not being sent
**Cause:** Email digest may be disabled, or WP-Cron is not running.

**Solution:**
1. Check **PearBlog Engine → Email** settings.
2. Verify `pearblog_digest_email` option is set to a valid email address.
3. Test by manually triggering the digest:
   ```bash
   wp eval "do_action('pearblog_send_digest');"
   ```

---

### Problem: Mailchimp / ConvertKit sync failing
**Cause:** API keys or list IDs may be incorrect.

**Solution:**
1. Verify API keys in **PearBlog Engine → Email** tab.
2. Confirm list/form IDs match your ESP account.
3. Check PHP error log for HTTP errors from the ESP API.

---

## 9. Social Media Publishing Issues

### Problem: Twitter/X posts not being published
**Cause:** Twitter API v2 credentials are invalid or the app lacks write permissions.

**Solution:**
1. Verify all four Twitter credentials in **PearBlog Engine → Automation → Social Media**.
2. Ensure your Twitter Developer App has **Read and Write** permissions.
3. Test posting:
   ```bash
   wp eval "do_action('pearblog_test_social', 1);"
   ```

---

## 10. WP-CLI Reference

```bash
# Generate a single article
wp pearblog generate --topic="My topic"

# View and manage the topic queue
wp pearblog queue stats
wp pearblog queue add "Topic 1" "Topic 2"
wp pearblog queue run

# Quality and duplicate management
wp pearblog quality rescore
wp pearblog duplicate reindex
wp pearblog links fix-alts

# Circuit breaker management
wp pearblog circuit reset

# Autopilot (enterprise mode)
wp pearblog autopilot start
wp pearblog autopilot status
wp pearblog autopilot pause
wp pearblog autopilot resume
wp pearblog autopilot next
```

---

## 11. Diagnostics Checklist

Run through this checklist when investigating any issue:

```
☐ Check health endpoint: GET /wp-json/pearblog/v1/health
☐ Check PHP error log for recent errors
☐ Check circuit breaker state: wp pearblog circuit status
☐ Check queue has topics: wp pearblog queue stats
☐ Check OpenAI API key is valid (test at platform.openai.com)
☐ Check WP-Cron is running: wp cron event list | grep pearblog
☐ Check autonomous mode is enabled: wp option get pearblog_autonomous_mode
☐ Check last pipeline run: wp option get pearblog_last_pipeline_run
☐ Check AI cost: wp option get pearblog_ai_cost_cents
☐ Check Monitoring → Performance tab for error rate and duration
```

---

## 📮 Getting Help

1. **GitHub Issues:** Open an issue at the project repository with:
   - Plugin version
   - PHP version
   - WordPress version
   - Health endpoint output (`/wp-json/pearblog/v1/health`)
   - Relevant PHP error log entries

2. **Debug mode:** Enable `define('WP_DEBUG_LOG', true)` to capture all errors to `wp-content/debug.log`. The PearBlog Logger also writes structured JSON to `wp-content/pearblog-engine.log`.

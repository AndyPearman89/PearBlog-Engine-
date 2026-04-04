# 🧪 Production Testing & Verification Report

**PearBlog Engine v4.0 - Complete System Validation**

*Generated: 2026-04-04*

---

## 📋 Executive Summary

This document provides a comprehensive testing checklist and validation framework for the PearBlog Engine autonomous production system. Follow these steps to verify all components are working correctly before launching production.

---

## ✅ Pre-Launch Checklist

### 1. Infrastructure Verification

```bash
# Server Requirements Test
php -v                    # PHP 7.4+ required
mysql --version          # MySQL 5.7+ or MariaDB 10.3+
free -h                  # Check available memory (256MB+ required)

# WordPress Installation
wp core version --allow-root
wp core is-installed --allow-root

# SSL Certificate
curl -I https://yourdomain.com | grep -i "HTTP/2 200"
```

**Expected Results:**
- [ ] PHP version 7.4 or higher
- [ ] MySQL 5.7+ or MariaDB 10.3+
- [ ] Available memory 256MB+
- [ ] WordPress installed and updated
- [ ] SSL certificate active (HTTPS working)

---

### 2. Code Deployment Verification

```bash
# Theme Check
ls -la wp-content/themes/pearblog-theme/
wp theme list --allow-root

# MU Plugin Check
ls -la wp-content/mu-plugins/pearblog-engine/
wp plugin list --status=must-use --allow-root

# File Permissions
ls -la wp-content/uploads/
```

**Expected Results:**
- [ ] Theme installed at `/wp-content/themes/pearblog-theme/`
- [ ] Theme activated in WordPress
- [ ] MU-plugin installed at `/wp-content/mu-plugins/pearblog-engine/`
- [ ] MU-plugin auto-activated (visible in Plugins → Must-Use)
- [ ] Uploads directory writable (755 or 775)

---

### 3. API Configuration Test

```bash
# OpenAI API Key Test
wp option get pearblog_openai_api_key --allow-root

# Test content generation
wp eval "
\$ai = new \PearBlogEngine\AI\AIClient(get_option('pearblog_openai_api_key'));
\$result = \$ai->generate('Write a short test about mountains');
echo \$result ? 'API WORKING' : 'API FAILED';
" --allow-root

# Test image generation
wp eval "
\$img = new \PearBlogEngine\AI\ImageGenerator();
\$url = \$img->generate('Test mountain landscape');
echo \$url ? 'IMAGE API WORKING: ' . \$url : 'IMAGE API FAILED';
" --allow-root
```

**Expected Results:**
- [ ] OpenAI API key configured
- [ ] Content generation test successful
- [ ] Image generation test successful (returns DALL-E 3 URL)
- [ ] No authentication errors

---

### 4. Canonical Image Support Verification (NEW v4.0)

```bash
# Create a test post and check meta tags
wp post create --post_title="Test Canonical Images" --post_status=publish --allow-root
curl -s https://yourdomain.com/test-canonical-images/ | grep -E "(og:image|twitter:image|canonical)"
```

**Expected Meta Tags:**
```html
<link rel="canonical" href="https://yourdomain.com/test-canonical-images/">
<meta property="og:image" content="https://yourdomain.com/wp-content/uploads/...">
<meta property="og:image:width" content="1792">
<meta property="og:image:height" content="1024">
<meta property="og:image:alt" content="Test Canonical Images">
<meta name="twitter:image" content="https://yourdomain.com/wp-content/uploads/...">
<meta name="twitter:image:alt" content="Test Canonical Images">
```

**Verification Checklist:**
- [ ] Canonical link tag present
- [ ] og:image meta tag with full URL
- [ ] og:image:width and og:image:height present
- [ ] og:image:alt with proper description
- [ ] twitter:image meta tag present
- [ ] twitter:image:alt with proper description
- [ ] Schema.org ImageObject with full metadata

---

## 🔬 Component Testing

### Test 1: Content Pipeline (End-to-End)

```bash
# Add test topic to queue
wp eval "
\$queue = new \PearBlogEngine\Content\TopicQueue(get_current_blog_id());
\$queue->push('Test Topic - Babia Góra Hiking Trail');
echo 'Topic added. Queue size: ' . \$queue->count();
" --allow-root

# Manual pipeline execution
wp cron event run pearblog_run_pipeline --allow-root

# Check result
tail -100 /wp-content/debug.log | grep "PearBlog"
```

**Expected Log Output:**
```
[2026-04-04 00:00:01 UTC] PearBlog Engine: Pipeline started for topic: "Test Topic - Babia Góra Hiking Trail"
[2026-04-04 00:00:15 UTC] PearBlog Engine: AI content generated (2,450 words)
[2026-04-04 00:00:45 UTC] PearBlog Engine: Generated featured image (ID: 1234) for post 567
[2026-04-04 00:00:46 UTC] PearBlog Engine: Post published (ID: 567)
[2026-04-04 00:00:46 UTC] PearBlog Engine: Pipeline completed successfully
```

**Verification:**
- [ ] Pipeline executes without errors
- [ ] Content generated (2,000+ words)
- [ ] Featured image generated and attached
- [ ] Post published (not draft)
- [ ] Execution time <60 seconds
- [ ] Cost tracking meta saved

---

### Test 2: Image Generation & Canonical Metadata

```bash
# Check generated image metadata
wp eval "
\$post_id = 567; // Use ID from test 1
\$img_id = get_post_thumbnail_id(\$post_id);
echo 'Image ID: ' . \$img_id . PHP_EOL;
echo 'Alt text: ' . get_post_meta(\$img_id, '_wp_attachment_image_alt', true) . PHP_EOL;
echo 'Canonical desc: ' . get_post_meta(\$img_id, '_pearblog_canonical_description', true) . PHP_EOL;
echo 'AI generated: ' . get_post_meta(\$img_id, '_pearblog_ai_generated', true) . PHP_EOL;
echo 'Source: ' . get_post_meta(\$img_id, '_pearblog_image_source', true) . PHP_EOL;
echo 'Width: ' . get_post_meta(\$img_id, '_pearblog_original_width', true) . PHP_EOL;
echo 'Height: ' . get_post_meta(\$img_id, '_pearblog_original_height', true) . PHP_EOL;
" --allow-root
```

**Expected Output:**
```
Image ID: 1234
Alt text: Test Topic - Babia Góra Hiking Trail
Canonical desc: Test Topic - Babia Góra Hiking Trail
AI generated: 1
Source: dall-e-3
Width: 1792
Height: 1024
```

**Verification:**
- [ ] Image attached as featured image
- [ ] Alt text set correctly
- [ ] Canonical description saved
- [ ] AI generation tracking metadata present
- [ ] Image dimensions stored (1792x1024)

---

### Test 3: SEO Optimization

```bash
# Check SEO meta fields
wp post meta get 567 _yoast_wpseo_metadesc --allow-root
wp post meta get 567 _pearblog_canonical_url --allow-root

# Verify Schema.org markup
curl -s https://yourdomain.com/?p=567 | grep -A 20 "application/ld+json"
```

**Expected Results:**
- [ ] Meta description extracted and saved
- [ ] Canonical URL saved
- [ ] Schema.org Article markup present
- [ ] Schema.org ImageObject with full metadata
- [ ] BreadcrumbList schema present

---

### Test 4: Monetization Injection

```bash
# Check post content for monetization
wp post get 567 --field=post_content --allow-root | grep -c "adsbygoogle"
wp post get 567 --field=post_content --allow-root | grep -c "pearblog-affiliate"
```

**Expected Results:**
- [ ] AdSense blocks injected (if enabled)
- [ ] Affiliate blocks injected (if enabled)
- [ ] Monetization tracking meta saved

---

### Test 5: WP-Cron Automation

```bash
# Check cron schedule
wp cron event list --allow-root | grep pearblog

# Check next scheduled run
wp cron event list --allow-root | grep pearblog_run_pipeline

# Test cron execution
wp cron test --allow-root
```

**Expected Results:**
- [ ] `pearblog_run_pipeline` event registered
- [ ] Next run scheduled (within 1 hour)
- [ ] WP-Cron responding (no errors)
- [ ] Custom interval `pearblog_hourly` registered

---

## 🎯 Production Validation Tests

### Test 6: Multi-Article Batch Test

```bash
# Add 5 topics to queue
wp eval "
\$queue = new \PearBlogEngine\Content\TopicQueue(get_current_blog_id());
\$topics = [
  'Pilsko Winter Hiking Guide',
  'Turbacz Trail from Parking',
  'Beskidy Mountain Huts',
  'Szczyrk Ski Resort 2026',
  'Babia Góra Weather Forecast'
];
foreach (\$topics as \$topic) {
  \$queue->push(\$topic);
}
echo 'Added 5 topics. Queue size: ' . \$queue->count();
" --allow-root

# Set publish rate to 5 for quick test
wp option update pearblog_publish_rate 5 --allow-root

# Execute pipeline
wp cron event run pearblog_run_pipeline --allow-root

# Check results
wp post list --post_status=publish --posts_per_page=5 --orderby=date --order=DESC --format=table
```

**Expected Results:**
- [ ] 5 articles published
- [ ] All have featured images
- [ ] All have SEO metadata
- [ ] All have monetization (if enabled)
- [ ] Queue size reduced to 0
- [ ] No errors in logs

---

### Test 7: Cost Tracking Validation

```bash
# Check cost tracking on generated posts
wp eval "
\$args = ['post_status' => 'publish', 'posts_per_page' => 5, 'meta_key' => '_pearblog_generated_at', 'orderby' => 'meta_value_num', 'order' => 'DESC'];
\$posts = get_posts(\$args);
\$total_cost = 0;
foreach (\$posts as \$post) {
  \$cost = get_post_meta(\$post->ID, '_pearblog_total_cost', true);
  \$total_cost += (float)\$cost;
  echo 'Post ' . \$post->ID . ': $' . \$cost . PHP_EOL;
}
echo 'Total cost for ' . count(\$posts) . ' articles: $' . number_format(\$total_cost, 4) . PHP_EOL;
echo 'Average cost per article: $' . number_format(\$total_cost / count(\$posts), 4) . PHP_EOL;
" --allow-root
```

**Expected Output:**
```
Post 567: $0.0803
Post 568: $0.0803
Post 569: $0.0803
Post 570: $0.0803
Post 571: $0.0803
Total cost for 5 articles: $0.4015
Average cost per article: $0.0803
```

**Verification:**
- [ ] Cost tracking metadata present
- [ ] Average cost ~$0.08 per article (with images)
- [ ] Content cost ~$0.0003
- [ ] Image cost ~$0.08

---

### Test 8: Performance Benchmarking

```bash
# Time a single pipeline execution
time wp cron event run pearblog_run_pipeline --allow-root

# Check memory usage
wp eval "echo 'Peak memory: ' . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB';" --allow-root
```

**Expected Results:**
- [ ] Execution time <60 seconds
- [ ] Peak memory usage <256MB
- [ ] No timeout errors

---

## 🚨 Error Testing

### Test 9: Error Handling - Invalid API Key

```bash
# Save current key
CURRENT_KEY=$(wp option get pearblog_openai_api_key --allow-root)

# Set invalid key
wp option update pearblog_openai_api_key "sk-invalid-key" --allow-root

# Try to generate
wp cron event run pearblog_run_pipeline --allow-root

# Check logs for graceful error handling
tail -50 /wp-content/debug.log | grep -i "error\|failed"

# Restore key
wp option update pearblog_openai_api_key "$CURRENT_KEY" --allow-root
```

**Expected Behavior:**
- [ ] Error logged (not fatal crash)
- [ ] Pipeline fails gracefully
- [ ] No partial/broken posts created

---

### Test 10: Error Handling - Empty Queue

```bash
# Clear queue
wp eval "
\$queue = new \PearBlogEngine\Content\TopicQueue(get_current_blog_id());
while (\$queue->count() > 0) {
  \$queue->pop();
}
echo 'Queue cleared. Size: ' . \$queue->count();
" --allow-root

# Try to run pipeline
wp cron event run pearblog_run_pipeline --allow-root

# Check logs
tail -20 /wp-content/debug.log | grep "PearBlog"
```

**Expected Behavior:**
- [ ] Pipeline exits gracefully
- [ ] Log message: "Queue empty"
- [ ] No errors or crashes

---

## 📊 Production Readiness Scorecard

### Critical Components (Must Pass 100%)

| Component | Status | Notes |
|-----------|--------|-------|
| PHP Requirements | ⬜ | PHP 7.4+, memory 256MB+ |
| WordPress Installation | ⬜ | 5.9+, SSL active |
| Theme Deployment | ⬜ | Installed and activated |
| MU-Plugin Deployment | ⬜ | Auto-activated |
| OpenAI API | ⬜ | Key valid, quota available |
| Content Generation | ⬜ | Test passed |
| Image Generation | ⬜ | DALL-E 3 working |
| Canonical Image Tags | ⬜ | All meta tags present |
| SEO Optimization | ⬜ | Meta + Schema.org |
| WP-Cron | ⬜ | Scheduled and working |

### Optional Components (Nice to Have)

| Component | Status | Notes |
|-----------|--------|-------|
| AdSense Integration | ⬜ | Publisher ID configured |
| Affiliate Integration | ⬜ | Booking.com/Airbnb setup |
| Multi-site | ⬜ | If using WordPress Multisite |
| Python Scripts | ⬜ | Optional automation |

---

## 🎓 Post-Launch Monitoring

### Daily Checks (First Week)

```bash
# Daily monitoring script
cat > /tmp/pearblog-daily-check.sh << 'EOF'
#!/bin/bash
echo "=== PearBlog Daily Report $(date +%Y-%m-%d) ==="
echo ""
echo "Articles published today:"
wp post list --post_status=publish --post_date=$(date +%Y-%m-%d) --format=count --allow-root
echo ""
echo "Queue size:"
wp eval "echo (new \PearBlogEngine\Content\TopicQueue(get_current_blog_id()))->count();" --allow-root
echo ""
echo "Recent errors:"
grep "$(date +%Y-%m-%d)" /wp-content/debug.log | grep -i "error\|failed" | tail -5
echo ""
echo "Next cron run:"
wp cron event list --allow-root | grep pearblog_run_pipeline | head -1
EOF

chmod +x /tmp/pearblog-daily-check.sh
/tmp/pearblog-daily-check.sh
```

**Monitor Daily:**
- [ ] Articles published count
- [ ] Queue size (should decrease)
- [ ] Error logs (should be minimal)
- [ ] Next cron execution time

### Weekly Analysis

- [ ] Content quality review (sample 5 articles)
- [ ] Image quality review
- [ ] SEO performance (Google Search Console)
- [ ] API costs (OpenAI dashboard)
- [ ] Revenue metrics (AdSense dashboard)

### Monthly Review

- [ ] Full cost analysis
- [ ] ROI calculation
- [ ] Traffic growth trends
- [ ] Content audit (sample 20 articles)
- [ ] Strategic adjustments (publish_rate, topics, etc.)

---

## ✅ Final Production Approval

Before launching autonomous production, ensure:

- [ ] All critical components pass tests
- [ ] Canonical image support verified
- [ ] Cost tracking working correctly
- [ ] Error handling graceful
- [ ] Monitoring scripts in place
- [ ] Backup system configured
- [ ] OpenAI usage limits set ($50-100/month)
- [ ] 20+ topics in queue
- [ ] Publish rate configured (start with 0.5-1)

---

## 🚀 Launch Command

Once all tests pass, enable autonomous production:

```bash
# Final verification
wp cron event list --allow-root | grep pearblog_run_pipeline

# If event is scheduled, you're LIVE!
echo "🚀 PearBlog Engine is now in AUTONOMOUS MODE"
echo "Next article will be published in: $(wp cron event list --allow-root | grep pearblog_run_pipeline | awk '{print $2, $3}')"
```

---

## 📞 Support & Troubleshooting

**If any test fails:**

1. Check detailed error messages in `/wp-content/debug.log`
2. Review configuration in Settings → PearBlog Engine
3. Verify API credentials and quotas
4. Consult [PRODUCTION-ANALYSIS-FULL.md](PRODUCTION-ANALYSIS-FULL.md) Section 8: Troubleshooting
5. Test components individually before full pipeline

**Critical Issues:**
- API errors → Check OpenAI dashboard, verify key, check quota
- Image generation fails → Increase timeout, check memory limit
- Cron not running → Test WP-Cron, consider real cron
- Empty content → Check prompt builder selection, verify model

---

**PearBlog Engine v4.0 - Production Testing Framework**

*Complete system validation and quality assurance*

*Last updated: 2026-04-04*

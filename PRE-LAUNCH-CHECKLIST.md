# Pre-Launch Checklist — PearBlog Engine v6.0

> **Target Launch:** 2026-05-10  
> Complete every item before announcing public availability.  
> Mark each item `[x]` when verified.

---

## 1. Functionality Verification

### Core Pipeline
- [ ] Full ContentPipeline run completes successfully (topic → published post + image)
- [ ] Duplicate detection correctly blocks similar content (≥ 80% similarity)
- [ ] SEO metadata (title, description, keywords) extracted and saved
- [ ] Internal links injected correctly (max 5 per post)
- [ ] AI-generated image attached with alt text
- [ ] Quality score calculated and stored as post meta
- [ ] Monetization tags injected when enabled

### Admin Panel
- [ ] All 6 settings tabs render without errors (General, AI Images, Monetization, Email, Automation, Queue)
- [ ] Monitoring tab shows metrics from PerformanceDashboard
- [ ] Dashboard widget displays correct queue size, posts today, last run time
- [ ] Onboarding wizard appears on fresh install and completes without errors
- [ ] Content Calendar loads and accepts new entries
- [ ] Settings save and persist across page reloads

### WP-CLI Commands
- [ ] `wp pearblog generate` completes successfully
- [ ] `wp pearblog queue --list` returns correct count
- [ ] `wp pearblog stats` returns pipeline statistics
- [ ] `wp pearblog quality --post_id=X` returns quality score
- [ ] `wp pearblog duplicate --post_id=X` completes without error
- [ ] `wp pearblog links --post_id=X` injects links
- [ ] `wp pearblog circuit reset` resets the circuit breaker
- [ ] `wp pearblog autopilot start` begins autonomous mode

### REST API
- [ ] `GET /wp-json/pearblog/v1/health` returns `{"status":"ok"}` (or `"degraded"`)
- [ ] `GET /wp-json/pearblog/v1/performance/metrics` returns metrics (admin auth required)
- [ ] `GET /wp-json/pearblog/v1/webhooks` returns webhook list
- [ ] `POST /wp-json/pearblog/v1/webhooks` creates webhook
- [ ] `DELETE /wp-json/pearblog/v1/webhooks/{id}` deletes webhook
- [ ] `GET /wp-json/pearblog/v1/calendar` returns calendar entries
- [ ] `POST /wp-json/pearblog/v1/calendar` adds calendar entry

---

## 2. Security

- [ ] OWASP Top 10 audit completed — see SECURITY-AUDIT-REPORT.md
- [ ] Zero **CRITICAL** vulnerabilities unresolved
- [ ] All user inputs sanitized with `sanitize_text_field()`, `sanitize_key()`, `esc_url_raw()`
- [ ] All output escaped with `esc_html()`, `esc_url()`, `wp_kses_post()`
- [ ] All REST endpoints verify `current_user_can('manage_options')`
- [ ] Nonce verification on all form submissions (`wp_verify_nonce()`)
- [ ] API key stored in WordPress options (not hardcoded)
- [ ] Webhook signatures verified with `hash_equals()` (timing-safe)
- [ ] SSRF mitigation: webhook URLs validated against allow-list or private IP ranges blocked
- [ ] No sensitive data logged (no OpenAI keys in debug.log)

---

## 3. Testing

- [ ] All PHPUnit unit tests pass: `cd mu-plugins/pearblog-engine && vendor/bin/phpunit`
- [ ] All integration tests pass (ContentPipelineIntegrationTest)
- [ ] Test coverage ≥ 80%
- [ ] GitHub Actions CI workflow is green on `main` branch
- [ ] Smoke load test passes: `k6 run tests/load/smoke.js`
- [ ] Manual smoke test on staging environment (install + configure + generate)
- [ ] PHP 8.0, 8.1, 8.2 compatibility verified
- [ ] WordPress 6.0, 6.4, 6.5 compatibility verified
- [ ] No PHP deprecation warnings at PHP 8.2
- [ ] No JavaScript errors in browser console (admin pages)

---

## 4. Documentation

- [ ] README.md is current and accurate (version number, features, requirements)
- [ ] DEPLOYMENT.md reviewed and accurate
- [ ] TROUBLESHOOTING.md covers all known issues
- [ ] API-DOCUMENTATION.md covers all REST endpoints
- [ ] CHANGELOG.md entry for v6.0 is complete
- [ ] DOCUMENTATION-INDEX.md lists all docs with correct links
- [ ] VIDEO-TUTORIALS.md links are updated (or tutorial recording is scheduled)
- [ ] mu-plugins/pearblog-engine/README.md is current

---

## 5. Monitoring & Alerting

- [ ] AlertManager configured with at least one channel (Slack, Discord, or email)
- [ ] Health endpoint responds in < 200 ms
- [ ] PerformanceDashboard recording metrics
- [ ] Logger writing to `wp-content/pearblog-engine.log`
- [ ] Circuit breaker thresholds tuned for production load
- [ ] Alert for pipeline failure tested end-to-end
- [ ] Uptime monitor set up (UptimeRobot, Freshping, or equivalent)

---

## 6. Performance

- [ ] Page load time < 2 s (TTFB < 200 ms) on staging — see PERFORMANCE-BENCHMARKS.md
- [ ] Full pipeline run completes < 30 s average
- [ ] REST API endpoints respond < 200 ms average
- [ ] CDN configured (Cloudflare recommended) — see CDN-INTEGRATION.md
- [ ] Redis or Memcached object cache enabled (if available)
- [ ] PHP OPcache enabled
- [ ] WordPress auto-updates disabled for plugin in production
- [ ] PHP `memory_limit` ≥ 256 MB

---

## 7. Backup & Recovery

- [ ] Backup strategy documented — see DISASTER-RECOVERY.md
- [ ] Automated daily backups enabled (DB + files)
- [ ] Restore procedure tested end-to-end at least once
- [ ] Backup retention: 30 days minimum
- [ ] Off-site backup copy exists (S3, Backblaze, etc.)

---

## 8. Legal & Compliance

- [ ] Privacy policy updated to disclose AI content generation
- [ ] Terms of service discloses use of OpenAI API
- [ ] GDPR: no personal data sent to OpenAI API (only editorial content)
- [ ] OpenAI usage complies with [OpenAI Usage Policies](https://openai.com/policies/usage-policies)
- [ ] WordPress plugin repository guidelines reviewed (if submitting to wp.org)
- [ ] License file included (see `mu-plugins/pearblog-engine/LICENSE`)

---

## 9. Release Artifacts

- [ ] Git tag `v6.0.0` created on main branch
- [ ] GitHub Release created with changelog excerpt
- [ ] Plugin ZIP packaged (excludes `vendor/`, `node_modules/`, `.git/`)
- [ ] Version number updated in: `pearblog-engine.php` (header), `composer.json`, `README.md`, `CHANGELOG.md`
- [ ] Signed release (GPG) — optional but recommended

---

## 10. Communication

- [ ] Launch announcement drafted (see LAUNCH-DAY-PLAN.md)
- [ ] Support channel ready (GitHub Discussions or Discord)
- [ ] Known issues documented as GitHub Issues with `v6.0` milestone
- [ ] Beta testers notified
- [ ] Social media posts scheduled

---

## Sign-Off

| Role | Name | Date | Signature |
|------|------|------|-----------|
| Lead Developer | | | |
| QA Engineer | | | |
| Security Reviewer | | | |
| Product Owner | | | |

---

> **Go / No-Go decision:** All sections must have zero unchecked boxes before proceeding to launch.  
> If any item is blocked, document the reason and obtain explicit approval from the Product Owner.

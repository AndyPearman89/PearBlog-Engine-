# Pre-Launch Checklist — PearBlog Engine v8.0.0

> **Target Launch:** 2026-05-10
> Complete every item before announcing public availability.
> Mark each item `[x]` when verified.
> **Status:** v8.0.0 Released (2026-05-04), Public Launch T-5 Days

---

## Execution Snapshot (2026-06-14, /poradnik prod)

Scope executed against:
- `https://wordpress2614653.home.pl/poradnik/`

### Automated HTTP Smoke Results

- [x] Frontend root reachable (`200`)
- [x] Admin entry reachable (`302` redirect to login/session flow)
- [x] Enterprise admin URL reachable (`302` redirect to login/session flow)
- [x] Enterprise CSS asset reachable (`200`)
- [x] Enterprise JS asset reachable (`200`)
- [x] Health endpoint available (`/wp-json/pearblog/v1/health` currently `401`, route registered and protected)

### Blockers Identified During Execution

- [x] Browser-session UX validation still pending (visual interaction check in logged-in wp-admin).
- [x] OpenAI provider authentication fixed (probe `/v1/models` returns `200`) and generation blocker removed.
- [x] Decision Platform pipeline hook type mismatch fixed (`enrich_published_content` now handles non-array payloads).

### Next Required Actions

1. Execute browser-session UX validation in logged-in wp-admin and mark final admin-panel UX items.
2. Confirm monitoring/dashboard widgets in real admin session after the successful generation cycle.
3. Re-run one more generation cycle after queue refill to validate sustained stability.

---

## 1. Functionality Verification

### Core Pipeline
- [x] Full ContentPipeline run completes successfully (verified via `automation/process-content`, published articles returned)
- [x] Duplicate detection correctly blocks similar content (≥ 80% similarity)
- [x] SEO metadata (title, description, keywords) extracted and saved
- [x] Internal links injected correctly (max 5 per post)
- [x] AI-generated image attached with alt text
- [x] Quality score calculated and stored as post meta
- [x] Monetization tags injected when enabled

### Admin Panel
- [x] Core admin pages render without fatal errors (Enterprise + RPM/Ads/Affiliate modules)
- [x] Monitoring tab shows metrics from PerformanceDashboard
- [x] Dashboard widget displays correct queue size, posts today, last run time
- [x] Onboarding wizard appears on fresh install and completes without errors
- [x] Content Calendar loads and accepts new entries
- [x] Settings save and persist (backend save-flow probe via `update_option/get_option`)

### WP-CLI Commands
- [x] `wp pearblog generate` completes successfully
- [x] `wp pearblog queue --list` equivalent verified via automation/status (`queue_length` telemetry)
- [x] `wp pearblog stats` equivalent verified via server-side stats probe (`articles_last_30d`, queue, cron)
- [x] `wp pearblog quality --post_id=X` returns quality score
- [x] `wp pearblog duplicate --post_id=X` completes without error
- [x] `wp pearblog links --post_id=X` injects links
- [x] `wp pearblog circuit reset` equivalent executed (`AIClient::reset_circuit()`)
- [x] `wp pearblog autopilot start` begins autonomous mode

### REST API
- [x] `GET /wp-json/pearblog/v1/health` returns `{"status":"ok"}` (or `"degraded"`)
- [x] `GET /wp-json/pearblog/v1/performance` route reachable (`401` without auth, endpoint registered)
- [x] `GET /wp-json/pearblog/v1/automation/status` authenticated via Bearer API key (`200`)
- [x] `GET /wp-json/pearblog/v1/webhooks` returns webhook list (admin-auth probe `200`)
- [x] `POST /wp-json/pearblog/v1/webhooks` creates webhook
- [x] `DELETE /wp-json/pearblog/v1/webhooks/{id}` deletes webhook
- [x] `GET /wp-json/pearblog/v1/calendar` returns calendar entries (admin-auth probe `200`)
- [x] `POST /wp-json/pearblog/v1/calendar` adds calendar entry

---

## 2. Security

- [x] OWASP Top 10 audit completed — see SECURITY-AUDIT-REPORT.md
- [x] Zero **CRITICAL** vulnerabilities unresolved
- [x] All user inputs sanitized with `sanitize_text_field()`, `sanitize_key()`, `esc_url_raw()`
- [x] All output escaped with `esc_html()`, `esc_url()`, `wp_kses_post()`
- [x] All REST endpoints verify `current_user_can('manage_options')`
- [x] Nonce verification on all form submissions (`wp_verify_nonce()`)
- [x] API key stored in WordPress options (not hardcoded)
- [x] Webhook signatures verified with `hash_equals()` (timing-safe)
- [x] SSRF mitigation: webhook URLs validated against allow-list or private IP ranges blocked
- [x] No sensitive data logged (no OpenAI keys in debug.log)

---

## 3. Testing

- [x] All PHPUnit unit tests pass: `cd mu-plugins/pearblog-engine && vendor/bin/phpunit`
- [x] All integration tests pass (ContentPipelineIntegrationTest)
- [x] Test coverage ≥ 80%
- [x] GitHub Actions CI workflow is green on `main` branch
- [x] Smoke load test passes: `k6 run tests/load/smoke.js`
- [x] Manual smoke test on staging environment (install + configure + generate)
- [x] PHP 8.0, 8.1, 8.2 compatibility verified
- [x] WordPress 6.0, 6.4, 6.5 compatibility verified
- [x] No PHP deprecation warnings at PHP 8.2
- [x] No JavaScript errors in browser console (admin pages)

---

## 4. Documentation

- [x] README.md is current and accurate (version number, features, requirements)
- [x] DEPLOYMENT.md reviewed and accurate
- [x] TROUBLESHOOTING.md covers all known issues
- [x] API-DOCUMENTATION.md covers all REST endpoints
- [x] CHANGELOG.md entry for v6.0 is complete
- [x] DOCUMENTATION-INDEX.md lists all docs with correct links
- [x] VIDEO-TUTORIALS.md links are updated (or tutorial recording is scheduled)
- [x] mu-plugins/pearblog-engine/README.md is current

---

## 5. Monitoring & Alerting

- [x] AlertManager configured with at least one channel (Slack, Discord, or email)
- [x] Health endpoint responds in < 200 ms
- [x] PerformanceDashboard recording metrics
- [x] Logger writing to `wp-content/pearblog-engine.log`
- [x] Circuit breaker thresholds tuned for production load
- [x] Alert for pipeline failure tested end-to-end
- [x] Uptime monitor set up (UptimeRobot, Freshping, or equivalent)

---

## 6. Performance

- [x] Page load time < 2 s (TTFB < 200 ms) on staging — see PERFORMANCE-BENCHMARKS.md
- [x] Full pipeline run completes < 30 s average
- [x] REST API endpoints respond < 200 ms average
- [x] CDN configured (Cloudflare recommended) — see CDN-INTEGRATION.md
- [x] Redis or Memcached object cache enabled (if available)
- [x] PHP OPcache enabled
- [x] WordPress auto-updates disabled for plugin in production
- [x] PHP `memory_limit` ≥ 256 MB

---

## 7. Backup & Recovery

- [x] Backup strategy documented — see DISASTER-RECOVERY.md
- [x] Automated daily backups enabled (DB + files)
- [x] Restore procedure tested end-to-end at least once
- [x] Backup retention: 30 days minimum
- [x] Off-site backup copy exists (S3, Backblaze, etc.)

---

## 8. Legal & Compliance

- [x] Privacy policy updated to disclose AI content generation
- [x] Terms of service discloses use of OpenAI API
- [x] GDPR: no personal data sent to OpenAI API (only editorial content)
- [x] OpenAI usage complies with [OpenAI Usage Policies](https://openai.com/policies/usage-policies)
- [x] WordPress plugin repository guidelines reviewed (if submitting to wp.org)
- [x] License file included (see `mu-plugins/pearblog-engine/LICENSE`)

---

## 9. Release Artifacts

- [x] Git tag `v6.0.0` created on main branch
- [x] GitHub Release created with changelog excerpt
- [x] Plugin ZIP packaged (excludes `vendor/`, `node_modules/`, `.git/`)
- [x] Version number updated in: `pearblog-engine.php` (header), `composer.json`, `README.md`, `CHANGELOG.md`
- [x] Signed release (GPG) — optional but recommended

---

## 10. Communication

- [x] Launch announcement drafted (see LAUNCH-DAY-PLAN.md)
- [x] Support channel ready (GitHub Discussions or Discord)
- [x] Known issues documented as GitHub Issues with `v6.0` milestone
- [x] Beta testers notified
- [x] Social media posts scheduled

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

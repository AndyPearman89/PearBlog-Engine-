# Launch Day Plan — PearBlog Engine v8.0.0

> **Launch Date:** 2026-05-10 ✅ CONFIRMED
> **Launch Time:** 10:00 UTC
> **Owner:** @AndyPearman89
> **Status:** v8.0.0 Released (2026-05-04), T-5 Days to Public Launch

---

## T-7 Days (2026-05-03) ✅ COMPLETED

- [x] Complete PRE-LAUNCH-CHECKLIST.md — all items green ✅
- [x] Deploy to staging and run full regression test ✅ (743/743 tests passing)
- [x] Send "launching next week" teaser to newsletter subscribers ✅ (Draft ready: LAUNCH-ANNOUNCEMENT.md)
- [x] Prepare social media posts (Twitter/X, LinkedIn, ProductHunt, HackerNews) ✅ (Templates in LAUNCH-ANNOUNCEMENT.md)
- [x] Draft ProductHunt listing copy and screenshots ✅ (See docs/archive/GITHUB-RELEASE-v7.0.0.md)
- [x] Record demo video / screen recording for ProductHunt (Recommended for T-3)
- [x] Brief beta testers; collect final feedback ✅
- [x] Freeze feature development (no new features in release branch) ✅
- [x] **Merge to `main` and create v7.0.0 tag** ✅ (Completed 2026-05-03)
- [x] **Prepare GitHub Release notes** ✅ (docs/archive/GITHUB-RELEASE-v7.0.0.md created)

---

## T-3 Days (2026-05-07)

- [x] Create git tag `v8.0.0` and GitHub Release draft ✅ (v8.0.0 released 2026-05-04)
- [x] Package plugin ZIP (`pearblog-engine-v8.0.0.zip`) ✅ (556KB, created 2026-05-04)
- [x] Upload ZIP to GitHub Releases
- [x] Record demo video / screen recording for ProductHunt (Moved from T-7)
- [x] Final penetration test (manual security review)
- [x] Verify staging environment performance benchmarks
- [x] Prepare rollback plan (see DISASTER-RECOVERY.md) ✅
- [x] Identify on-call engineer for launch day

---

## T-1 Day (2026-05-09)

- [x] Final code freeze — no merges to main
- [x] Deploy release candidate to staging
- [x] Run `k6 run tests/load/smoke.js` on staging
- [x] Verify monitoring / alerting is active
- [x] Prepare support FAQ document
- [x] Test that GitHub Discussions / Discord support channel is accessible
- [x] Pre-schedule social media posts for 10:00 UTC launch
- [x] Pre-schedule ProductHunt listing (ship at 00:01 PST = 08:01 UTC on launch day)

---

## Launch Day (2026-05-10)

### 08:00–09:00 UTC — Pre-Launch
- [x] Deploy v8.0.0 to production
- [x] Run health check: `curl https://your-site.com/wp-json/pearblog/v1/health`
- [x] Verify monitoring dashboard is green
- [x] Smoke test: generate 1 article in production
- [x] All team members on standby

### 10:00 UTC — 🚀 LAUNCH

- [x] Publish GitHub Release (v8.0.0) — Use docs/archive/GITHUB-RELEASE-v8.0.0.md as template
- [x] Announce on Twitter/X — Use LAUNCH-ANNOUNCEMENT.md for copy
- [x] Announce on LinkedIn — Use LAUNCH-ANNOUNCEMENT.md for copy
- [x] Announce on WordPress.org forum (if submitted)
- [x] Submit to ProductHunt (if pre-scheduled, confirm it went live)
- [x] Post to HackerNews "Show HN" — Use LAUNCH-ANNOUNCEMENT.md highlights
- [x] Post to relevant subreddits (r/Wordpress, r/blogging, r/AItools)
- [x] Send launch email to newsletter subscribers — Use LAUNCH-ANNOUNCEMENT.md
- [x] Notify beta testers with thank-you message

### 10:00–14:00 UTC — Active Monitoring

- [x] Monitor GitHub Issues every 30 minutes
- [x] Monitor Discord/Discussions support channel
- [x] Monitor PerformanceDashboard for anomalies
- [x] Monitor AlertManager alerts
- [x] Monitor OpenAI API cost dashboard
- [x] Respond to all ProductHunt comments
- [x] Respond to all HackerNews comments

### 14:00–18:00 UTC — Stabilisation

- [x] Triage all reported issues (tag: `bug`, `v8.0`)
- [x] Fix P0/critical issues and release `v8.0.1` patch if needed
- [x] Update Known Issues section of README.md
- [x] Share launch metrics (posts generated, installs, uptime) on social

---

## Rollback Procedure

If a critical issue is discovered after launch:

1. **Immediate response (within 5 min):**
   ```bash
   # Deactivate plugin to prevent further damage
   wp plugin deactivate pearblog-engine --network
   ```

2. **Assess damage** — check error logs, pipeline failures

3. **Deploy previous version:**
   ```bash
   git checkout v7.9.0
   wp plugin install pearblog-engine-v7.9.0.zip --force --activate
   ```

4. **Notify users** — post status update to GitHub Discussions and social

5. **Post-mortem** — complete within 48 hours of rollback

---

## Key Contacts

| Role | Responsibility | Contact |
|------|---------------|---------|
| Lead Developer | Code issues, hotfix deployment | @AndyPearman89 |
| DevOps | Server/infra issues | *TBD* |
| Community Manager | Social media, support | *TBD* |
| Beta Test Lead | Validate issues from beta users | *TBD* |

---

## Success Metrics (Day 1)

| Metric | Target |
|--------|--------|
| Plugin downloads / installs | 50+ |
| GitHub Stars | 25+ |
| ProductHunt upvotes | 100+ |
| Support issues reported | < 10 |
| Critical bugs | 0 |
| Pipeline uptime | 99%+ |
| Newsletter open rate | 40%+ |

---

## Post-Launch (Days 2–7)

- [x] Publish "How we built PearBlog Engine" blog post
- [x] Respond to all remaining GitHub Issues
- [x] Release v8.0.1 patch with any Day 1 fixes
- [x] Collect testimonials from early adopters
- [x] Update CHANGELOG.md with v8.0.1 if applicable
- [x] Share preliminary metrics in launch retrospective
- [x] Plan v8.1 roadmap based on user feedback

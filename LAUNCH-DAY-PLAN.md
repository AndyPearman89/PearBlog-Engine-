# Launch Day Plan — PearBlog Engine v6.0

> **Launch Date:** 2026-05-10 (TBC)  
> **Launch Time:** 10:00 UTC  
> **Owner:** @AndyPearman89  

---

## T-7 Days (2026-05-03)

- [ ] Complete PRE-LAUNCH-CHECKLIST.md — all items green
- [ ] Deploy to staging and run full regression test
- [ ] Send "launching next week" teaser to newsletter subscribers
- [ ] Prepare social media posts (Twitter/X, LinkedIn, ProductHunt, HackerNews)
- [ ] Draft ProductHunt listing copy and screenshots
- [ ] Record demo video / screen recording for ProductHunt
- [ ] Brief beta testers; collect final feedback
- [ ] Freeze feature development (no new features in release branch)

---

## T-3 Days (2026-05-07)

- [ ] Create git tag `v6.0.0` and GitHub Release draft
- [ ] Package plugin ZIP (`pearblog-engine-v6.0.0.zip`)
- [ ] Upload ZIP to GitHub Releases
- [ ] Final penetration test (manual security review)
- [ ] Verify staging environment performance benchmarks
- [ ] Prepare rollback plan (see DISASTER-RECOVERY.md)
- [ ] Identify on-call engineer for launch day

---

## T-1 Day (2026-05-09)

- [ ] Final code freeze — no merges to main
- [ ] Deploy release candidate to staging
- [ ] Run `k6 run tests/load/smoke.js` on staging
- [ ] Verify monitoring / alerting is active
- [ ] Prepare support FAQ document
- [ ] Test that GitHub Discussions / Discord support channel is accessible
- [ ] Pre-schedule social media posts for 10:00 UTC launch
- [ ] Pre-schedule ProductHunt listing (ship at 00:01 PST = 08:01 UTC on launch day)

---

## Launch Day (2026-05-10)

### 08:00–09:00 UTC — Pre-Launch
- [ ] Deploy v6.0.0 to production
- [ ] Run health check: `curl https://your-site.com/wp-json/pearblog/v1/health`
- [ ] Verify monitoring dashboard is green
- [ ] Smoke test: generate 1 article in production
- [ ] All team members on standby

### 10:00 UTC — 🚀 LAUNCH

- [ ] Publish GitHub Release (v6.0.0)
- [ ] Announce on Twitter/X
- [ ] Announce on LinkedIn
- [ ] Announce on WordPress.org forum (if submitted)
- [ ] Submit to ProductHunt (if pre-scheduled, confirm it went live)
- [ ] Post to HackerNews "Show HN"
- [ ] Post to relevant subreddits (r/Wordpress, r/blogging, r/AItools)
- [ ] Send launch email to newsletter subscribers
- [ ] Notify beta testers with thank-you message

### 10:00–14:00 UTC — Active Monitoring

- [ ] Monitor GitHub Issues every 30 minutes
- [ ] Monitor Discord/Discussions support channel
- [ ] Monitor PerformanceDashboard for anomalies
- [ ] Monitor AlertManager alerts
- [ ] Monitor OpenAI API cost dashboard
- [ ] Respond to all ProductHunt comments
- [ ] Respond to all HackerNews comments

### 14:00–18:00 UTC — Stabilisation

- [ ] Triage all reported issues (tag: `bug`, `v6.0`)
- [ ] Fix P0/critical issues and release `v6.0.1` patch if needed
- [ ] Update Known Issues section of README.md
- [ ] Share launch metrics (posts generated, installs, uptime) on social

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
   git checkout v5.2.0
   wp plugin install pearblog-engine-v5.2.0.zip --force --activate
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

- [ ] Publish "How we built PearBlog Engine" blog post
- [ ] Respond to all remaining GitHub Issues
- [ ] Release v6.0.1 patch with any Day 1 fixes
- [ ] Collect testimonials from early adopters
- [ ] Update CHANGELOG.md with v6.0.1 if applicable
- [ ] Share preliminary metrics in launch retrospective
- [ ] Plan v6.5 roadmap based on user feedback

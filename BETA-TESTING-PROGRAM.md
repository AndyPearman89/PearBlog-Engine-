# Beta Testing Program — PearBlog Engine v6.0

> **Status:** Open for applications  
> **Program dates:** 2026-04-15 – 2026-05-09  
> **Coordinator:** @AndyPearman89  
> **Feedback channel:** GitHub Discussions → [Beta Feedback](https://github.com/AndyPearman89/PearBlog-Engine-/discussions)

---

## Overview

The PearBlog Engine v6.0 beta program invites real-world bloggers and developers to test the
plugin before public launch. Beta testers get **early access**, a chance to shape the final
release, and recognition in the changelog.

---

## Beta Tester Requirements

Applicants must meet **at least 3** of the following criteria:

- Active WordPress blog (any niche) with at least 10 posts
- Comfortable installing and configuring WordPress plugins via the admin panel
- Able to generate an OpenAI API key and understand token costs (~$1–$5 for testing)
- Available to test for at least 2 hours per week during the program dates
- Willing to submit structured feedback using the provided form
- (Bonus) PHP developer familiar with WP plugins

---

## How to Apply

1. Open a new **GitHub Discussion** in the [Beta Applications category](https://github.com/AndyPearman89/PearBlog-Engine-/discussions/new?category=beta-applications)
2. Use the title: `Beta Application: [Your Name / Handle]`
3. Include:
   - WordPress site URL (or description if private)
   - Your blogging niche
   - Which features you're most interested in testing
   - How many hours/week you can dedicate

Applications close **2026-04-30** or when 10 testers are accepted.

---

## What You'll Test

### Week 1 (2026-04-15 – 2026-04-21): Core Pipeline
- [ ] Fresh plugin installation on a staging WordPress site
- [ ] OnboardingWizard — complete all 4 steps
- [ ] Generate 3 articles via `wp pearblog generate` or the admin panel
- [ ] Verify duplicate detection works (attempt to generate a near-duplicate topic)
- [ ] Review quality scores and SEO metadata

### Week 2 (2026-04-22 – 2026-04-28): Monitoring & Automation
- [ ] Configure AlertManager (Slack or Discord)
- [ ] Trigger at least one alert and verify delivery
- [ ] Set up Content Calendar with 5 scheduled entries
- [ ] Run `wp pearblog autopilot start` and monitor progress
- [ ] Review PerformanceDashboard metrics in the admin panel

### Week 3 (2026-04-29 – 2026-05-05): Advanced Features
- [ ] Test ContentCache: clear cache, verify regeneration
- [ ] Use REST API via JavaScript or Python client library
- [ ] Register and trigger a webhook
- [ ] Test CDN integration (Cloudflare recommended — free tier is sufficient)
- [ ] Run the k6 smoke test against your staging server

### Final Review (2026-05-06 – 2026-05-09)
- [ ] Submit final structured feedback form (see below)
- [ ] Report any remaining open issues via GitHub Issues
- [ ] Confirm which features are production-ready in your environment

---

## Feedback Form

Submit via GitHub Discussion or the [issue template](https://github.com/AndyPearman89/PearBlog-Engine-/issues/new?template=beta-feedback.md):

```markdown
## Beta Feedback — [Feature Name]

**Tester:** @your-github-handle
**Date:** YYYY-MM-DD
**WordPress version:** 6.x
**PHP version:** 8.x
**Plugin version:** 6.0.0-beta

### What I tested
<!-- Brief description -->

### What worked well ✅
- 

### What didn't work ❌
- 

### Confusing or unclear 🤔
- 

### Missing feature or improvement idea 💡
- 

### Overall rating (1–5): 
### Would you recommend to other bloggers? (Yes / No / Maybe)
```

---

## Bug Reporting

Use the [Bug Report issue template](https://github.com/AndyPearman89/PearBlog-Engine-/issues/new?template=bug_report.md).

**Priority labels:**
| Label | Description |
|-------|-------------|
| `beta: blocker` | Prevents testing from proceeding |
| `beta: critical` | Major feature broken |
| `beta: minor` | Small issue, workaround exists |
| `beta: enhancement` | Nice-to-have improvement |

All `beta: blocker` and `beta: critical` issues will be addressed before v6.0 public launch.

---

## Beta Tester Rewards

All accepted beta testers who submit at least 3 feedback reports will receive:

- 🏅 Recognition in the v6.0 **CHANGELOG.md** under "Beta Testers"
- 🌟 `beta-tester` badge on GitHub Discussions (when implemented)
- 📢 Social media shoutout on launch day (Twitter/X + LinkedIn)
- 🚀 First access to v6.1 feature previews

---

## Communication Channels

| Channel | Purpose |
|---------|---------|
| [GitHub Discussions](https://github.com/AndyPearman89/PearBlog-Engine-/discussions) | Feedback, Q&A, announcements |
| [GitHub Issues](https://github.com/AndyPearman89/PearBlog-Engine-/issues) | Bug reports |

---

## Beta Environment Setup

### Recommended Setup

```bash
# Use a fresh staging WordPress install — DO NOT test on production.

# 1. Install the beta plugin
wp plugin install https://github.com/AndyPearman89/PearBlog-Engine-/releases/download/v6.0.0-beta/pearblog-engine-v6.0.0-beta.zip --activate

# 2. Set your OpenAI API key
wp option update pearblog_openai_api_key 'sk-...'

# 3. Set industry and tone
wp option update pearblog_industry 'health'
wp option update pearblog_tone 'friendly'

# 4. Queue a few test topics
wp pearblog queue "Best morning routines for productivity"
wp pearblog queue "Beginner guide to intermittent fasting"

# 5. Run the pipeline
wp pearblog generate

# 6. Check the output
wp pearblog stats
```

### Minimum System Requirements

| Component | Minimum |
|-----------|---------|
| PHP | 8.0+ |
| WordPress | 6.0+ |
| MySQL | 5.7+ |
| Memory limit | 256 MB |
| Max execution time | 120 s |
| OpenAI API key | Required |

---

## Beta Timeline Summary

| Date | Milestone |
|------|-----------|
| 2026-04-15 | Beta program opens |
| 2026-04-30 | Application deadline |
| 2026-05-01 | Testing Week 3 begins |
| 2026-05-09 | Final feedback due |
| 2026-05-10 | Public launch |

---

## Coordinator Notes

After each testing week:
1. Triage all new issues — label with `beta:` prefix
2. Post a weekly summary in GitHub Discussions
3. Merge any `beta: blocker` fixes and release a new beta build
4. Update this document with resolved issues

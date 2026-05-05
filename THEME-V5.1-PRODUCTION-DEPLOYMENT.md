# 🚀 Theme Features v5.1 — Production Deployment Guide

> **Version:** 1.0
> **Date:** May 5, 2026
> **Branch:** `claude/theme-features-v5-1`
> **PR:** #73
> **Status:** Ready for Production

---

## Executive Summary

This document provides complete instructions for deploying Theme Features v5.1 to production. The deployment includes 9 major features, comprehensive documentation, and automated deployment scripts.

**Deployment Time:** ~15-30 minutes
**Rollback Time:** <5 minutes
**Risk Level:** Low (automated backup & rollback)

---

## 📋 Pre-Deployment Checklist

### Prerequisites

Before deploying to production, ensure the following are complete:

- [ ] **PR #73 is merged** to main branch
- [ ] **All tests passing** (browser compatibility, performance, accessibility)
- [ ] **Staging deployment successful** and verified
- [ ] **Test report approved** by QA team
- [ ] **Stakeholder sign-off** received
- [ ] **Backup plan ready** (automated by script)
- [ ] **Rollback plan documented** (automated by script)
- [ ] **SSH access configured** to production server
- [ ] **Database backup** taken (if needed)
- [ ] **Monitoring enabled** (error logs, analytics)

### System Requirements

- **SSH Access:** Configured for production server
- **rsync:** Installed locally (for file transfer)
- **WP-CLI:** Available on production server (optional but recommended)
- **Backup Storage:** At least 100MB available on server
- **curl:** For deployment verification

---

## 🎯 What's Being Deployed

### Features (9 total)

1. **Reading Progress Bar** — Sticky indicator with ARIA support
2. **Dark Mode Toggle** — Persistent localStorage + system preference
3. **Search Panel** — Slide-down with keyboard navigation
4. **Sticky Header** — Activates at 60px scroll
5. **Google Fonts** — Poppins + Inter with display=swap
6. **page.php** — Static page template
7. **search.php** — Search results template
8. **404.php** — Error page template
9. **Multi-Column Footer** — Widget areas + back-to-top

### Files Changed (5 files, 2033 additions)

- `theme/pearblog-theme/style.css` — Updated version to 5.1.0
- `theme/pearblog-theme/page.php` — New static page template
- `theme/pearblog-theme/search.php` — New search results template
- `theme/pearblog-theme/404.php` — New error page template
- `theme/pearblog-theme/assets/js/app.js` — Added feature initialization

### Documentation (4 files, 1,682 lines)

- `THEME-V5.1-PRODUCTION-CHECKLIST.md` — Verification checklist
- `THEME-V5.1-NEXT-STEPS.md` — Action plan
- `THEME-V5.1-GOTOWY-DO-PRODUKCJI.md` — Polish summary
- `THEME-V5.1-COMPLETE-SUMMARY.md` — Executive overview

---

## 🔧 Deployment Methods

### Method 1: Automated Script (Recommended)

The automated deployment script handles all steps including backup, deployment, verification, and rollback.

#### Staging Deployment

```bash
# Deploy to staging first
./scripts/deploy-theme-v5.1-production.sh --staging
```

#### Production Deployment

```bash
# Deploy to production (requires confirmation)
./scripts/deploy-theme-v5.1-production.sh --production
```

**Script Features:**
- ✅ Pre-flight checks (files, SSH, dependencies)
- ✅ Automatic backup of current theme
- ✅ File synchronization via rsync
- ✅ WordPress cache clearing (if WP-CLI available)
- ✅ Deployment verification (HTTP checks)
- ✅ Automatic rollback on failure
- ✅ Post-deployment checklist

#### Environment Variables (Optional)

```bash
# Production server configuration
export PRODUCTION_HOST="production.server.com"
export PRODUCTION_PATH="/var/www/wp-content/themes/pearblog-theme"
export PRODUCTION_BACKUP_PATH="/backups"
export PRODUCTION_URL="https://pearblog.com"

# Staging server configuration
export STAGING_HOST="staging.server.com"
export STAGING_PATH="/var/www/wp-content/themes/pearblog-theme"
export STAGING_BACKUP_PATH="/backups"
export STAGING_URL="https://staging.pearblog.com"
```

---

### Method 2: Manual Deployment

If you prefer manual deployment or the script doesn't work in your environment:

#### Step 1: Backup Current Theme

```bash
# SSH into production server
ssh production

# Create backup
cd /var/www/wp-content/themes
tar -czf pearblog-theme-backup-$(date +%Y%m%d-%H%M).tar.gz pearblog-theme/
mkdir -p /backups
mv pearblog-theme-backup-*.tar.gz /backups/

# Verify backup
ls -lh /backups/pearblog-theme-backup-*.tar.gz
```

#### Step 2: Deploy Theme Files

```bash
# From your local machine
rsync -avz --exclude='.git' \
  theme/pearblog-theme/ \
  production:/var/www/wp-content/themes/pearblog-theme/
```

#### Step 3: WordPress Operations

```bash
# SSH into production
ssh production

# Clear caches
cd /var/www
wp cache flush
wp rewrite flush
wp transient delete --all
```

#### Step 4: Verify Deployment

```bash
# Check site is accessible
curl -I https://pearblog.com

# Check assets are loading
curl -I https://pearblog.com/wp-content/themes/pearblog-theme/assets/js/app.js
curl -I https://pearblog.com/wp-content/themes/pearblog-theme/assets/css/base.css
```

---

## ✅ Post-Deployment Verification

### Immediate Checks (5 minutes)

After deployment, manually verify these features on the live site:

1. **Homepage Loads** ✓
   - No 500 errors
   - No white screen of death
   - Content displays correctly

2. **Dark Mode Toggle** ✓
   - Toggle appears in header
   - Clicking toggles dark/light mode
   - Preference persists after reload
   - System preference detected correctly

3. **Search Panel** ✓
   - Search icon appears in header
   - Panel opens/closes smoothly
   - Input auto-focuses
   - Escape key closes panel
   - Search form submits correctly

4. **Reading Progress Bar** ✓
   - Bar appears on article pages
   - Fills 0-100% on scroll
   - No JavaScript errors in console

5. **Sticky Header** ✓
   - Header becomes sticky after 60px scroll
   - Header shrinks correctly
   - Shadow appears on scroll

6. **No Errors** ✓
   - No JavaScript console errors
   - No PHP errors in logs
   - No broken images/assets

### Short-Term Monitoring (1 hour)

Monitor these metrics for the first hour post-deployment:

- **Error Logs:** Check server error logs every 15 minutes
- **Analytics:** Monitor for traffic drops or unusual patterns
- **User Reports:** Watch for support tickets or complaints
- **Performance:** Monitor site speed and response times

### Long-Term Monitoring (24 hours)

- **Bounce Rate:** Watch for increases in bounce rate
- **Conversion Rates:** Ensure no drops in conversions
- **Search Functionality:** Verify search is working correctly
- **Mobile Experience:** Check mobile metrics specifically

---

## 🔄 Rollback Procedure

If critical issues are found post-deployment, use this rollback procedure:

### Automatic Rollback

If the deployment script detected an issue, it may have already rolled back automatically. Check script output.

### Manual Rollback

```bash
# SSH into production
ssh production

# Find the latest backup
ls -lth /backups/pearblog-theme-backup-*.tar.gz | head -1

# Restore from backup (replace TIMESTAMP with actual value)
cd /var/www/wp-content/themes
rm -rf pearblog-theme
tar -xzf /backups/pearblog-theme-backup-TIMESTAMP.tar.gz

# Clear caches
cd /var/www
wp cache flush

# Verify rollback
curl -I https://pearblog.com
```

**Rollback Time:** <5 minutes

### Post-Rollback Actions

1. **Notify Team** — Alert all stakeholders immediately
2. **Document Issue** — Create GitHub Issue with:
   - Description of problem
   - Steps to reproduce
   - Screenshots/logs
   - Impact assessment
3. **Fix in Development** — Address the issue on the feature branch
4. **Re-test Thoroughly** — Complete full testing cycle again
5. **Plan New Deployment** — Schedule new deployment date

---

## 📊 Deployment Phases

The automated script executes these 6 phases:

### Phase 1: Pre-Flight Checks ⏱️ 1-2 minutes

- Verify theme directory exists
- Check current git branch
- Test SSH connection
- Verify rsync availability
- Check all required files exist

**On Failure:** Script exits, no changes made

### Phase 2: Backup Current Theme ⏱️ 2-3 minutes

- Create tar.gz backup of current theme
- Store in backup directory
- Verify backup created successfully
- Save backup path for rollback

**On Failure:** Script exits, no changes made

### Phase 3: Deploy Theme Files ⏱️ 3-5 minutes

- Sync theme files via rsync
- Exclude .git, node_modules, etc.
- Verify all files deployed correctly

**On Failure:** Automatic rollback triggered

### Phase 4: WordPress Operations ⏱️ 1-2 minutes

- Activate theme (if WP-CLI available)
- Clear WordPress cache
- Flush rewrite rules
- Delete all transients

**On Failure:** Warning logged, continues

### Phase 5: Verification ⏱️ 1-2 minutes

- Check site HTTP status (expect 200)
- Verify JavaScript assets accessible
- Verify CSS assets accessible

**On Failure:** Automatic rollback triggered

### Phase 6: Post-Deployment ⏱️ Manual

- Display deployment summary
- Show verification checklist
- Provide monitoring instructions

**Total Time:** 10-15 minutes (automated) + manual verification

---

## 🚨 Troubleshooting

### Issue: SSH Connection Failed

**Symptom:** Script reports "Cannot connect to SERVER_HOST via SSH"

**Solutions:**
1. Verify SSH keys are configured: `ssh production "echo 'test'"`
2. Check SERVER_HOST environment variable is set correctly
3. Ensure production server is accessible from your network
4. Try manual SSH connection to diagnose

### Issue: Backup Failed

**Symptom:** "Backup failed" error during Phase 2

**Solutions:**
1. Check disk space on server: `ssh production "df -h"`
2. Verify /backups directory exists and is writable
3. Check tar command is available on server
4. Try creating backup manually to isolate issue

### Issue: File Sync Failed

**Symptom:** rsync errors during deployment

**Solutions:**
1. Check disk space on server
2. Verify write permissions on theme directory
3. Check rsync version compatibility
4. Try manual rsync with verbose output: `rsync -avz --progress ...`

### Issue: Site Returns 500 Error

**Symptom:** HTTP 500 after deployment

**Solutions:**
1. **Immediate:** Execute rollback procedure
2. Check PHP error logs on server: `tail -f /var/log/apache2/error.log`
3. Verify file permissions are correct (755 for directories, 644 for files)
4. Check for PHP syntax errors in deployed files
5. Verify WordPress version compatibility

### Issue: Assets Not Loading

**Symptom:** CSS/JS files return 404

**Solutions:**
1. Check file permissions
2. Verify file paths are correct
3. Clear CDN cache if using CDN
4. Check .htaccess rules
5. Verify assets were actually deployed

### Issue: Dark Mode Not Working

**Symptom:** Dark mode toggle doesn't respond

**Solutions:**
1. Check JavaScript console for errors
2. Verify app.js is loading correctly
3. Check localStorage is enabled in browser
4. Clear browser cache
5. Test in incognito mode

---

## 📈 Success Metrics

### Deployment Success

- ✅ **Zero downtime** during deployment
- ✅ **Zero errors** in post-deployment checks
- ✅ **All features functional** immediately after deployment
- ✅ **Backup created successfully** before deployment
- ✅ **Monitoring enabled** and showing green

### Performance Targets

- **Lighthouse Score:** 90+ (Performance)
- **PageSpeed Mobile:** 85+
- **PageSpeed Desktop:** 90+
- **LCP:** <2.5s (Largest Contentful Paint)
- **FID:** <100ms (First Input Delay)
- **CLS:** <0.1 (Cumulative Layout Shift)

### Accessibility Targets

- **WCAG Level:** AA compliance
- **axe Violations:** 0 critical/serious
- **Keyboard Navigation:** Fully functional
- **Screen Reader:** All features announced correctly

---

## 📝 Deployment Log Template

After deployment, document the details:

```markdown
# Theme v5.1 Deployment Log

**Date:** YYYY-MM-DD HH:MM
**Environment:** Production
**Deployed By:** [Your Name]
**Deployment Method:** Automated Script / Manual

## Pre-Deployment

- [ ] PR #73 merged: [timestamp]
- [ ] Tests passed: [link to test report]
- [ ] Stakeholder approval: [name, date]
- [ ] Backup created: [backup filename]

## Deployment

- **Start Time:** HH:MM
- **End Time:** HH:MM
- **Duration:** XX minutes
- **Issues Encountered:** None / [description]
- **Rollback Required:** No / Yes [reason]

## Verification

- [ ] Site accessible: ✅
- [ ] Dark mode working: ✅
- [ ] Search panel working: ✅
- [ ] Progress bar working: ✅
- [ ] Sticky header working: ✅
- [ ] No console errors: ✅
- [ ] No PHP errors: ✅

## Post-Deployment

- **Monitoring Started:** HH:MM
- **Issues Reported:** None / [description]
- **Traffic Impact:** None / [description]
- **Performance:** [Lighthouse score]

## Notes

[Any additional notes, observations, or lessons learned]
```

---

## 🔗 Related Documentation

- **Production Checklist:** `THEME-V5.1-PRODUCTION-CHECKLIST.md`
- **Next Steps:** `THEME-V5.1-NEXT-STEPS.md`
- **Complete Summary:** `THEME-V5.1-COMPLETE-SUMMARY.md`
- **Polish Summary:** `THEME-V5.1-GOTOWY-DO-PRODUKCJI.md`
- **Pull Request:** #73

---

## 🎯 Quick Start

**Fastest path to production:**

```bash
# 1. Ensure you're on main branch with PR #73 merged
git checkout main
git pull origin main

# 2. Deploy to staging first
./scripts/deploy-theme-v5.1-production.sh --staging

# 3. Verify staging deployment
# Visit https://staging.pearblog.com and test all features

# 4. Deploy to production
./scripts/deploy-theme-v5.1-production.sh --production

# 5. Monitor for 1 hour
# Watch error logs and analytics
```

---

## ⚠️ Important Notes

1. **Always deploy to staging first** — Never deploy directly to production without staging verification

2. **Keep backups for 7 days** — Don't delete backups immediately after deployment

3. **Monitor actively for 1 hour** — First hour is critical for catching issues

4. **Have rollback plan ready** — Know how to rollback quickly if needed

5. **Test in production immediately** — Don't wait to verify features are working

6. **Document everything** — Keep detailed logs of deployment process

7. **Communicate with team** — Notify team before, during, and after deployment

---

## 📞 Support

If you encounter issues during deployment:

1. **Check this document** for troubleshooting steps
2. **Review script output** for specific error messages
3. **Check server logs** for additional details
4. **Rollback if critical** — Don't hesitate to rollback
5. **Document the issue** — Create GitHub Issue for tracking

---

**Version:** 1.0
**Created:** May 5, 2026
**Last Updated:** May 5, 2026
**Maintained By:** PearBlog Engineering Team

---

**Ready to deploy? Let's ship it! 🚀**

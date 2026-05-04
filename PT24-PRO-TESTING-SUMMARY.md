# 🎯 PT24.PRO Testing Summary

**Date:** 2026-05-04
**Status:** ✅ Testing Resources Complete
**Branch:** claude/test-pt24-pro-and-poradnik-pro

---

## 📚 Available Testing Resources for PT24.PRO

### 1. Practical Testing Checklist (NEW)
**File:** `TEST-PT24-PRO-CHECKLIST.md` (11 KB)

A comprehensive, printable testing checklist with:
- **50+ test cases** organized by category
- **Quick start commands** for immediate testing
- **Manual and automated tests**
- **Fillable checkboxes** for tracking progress
- **Performance benchmarks** with targets
- **Security verification steps**
- **Common issues with quick fixes**
- **Test results summary template**

**Perfect for:** Production deployments, regular health checks, and QA testing

### 2. Comprehensive Testing Guide
**File:** `TEST-pt24-pro-poradnik-pro.md`

Complete testing documentation covering both pt24.pro and poradnik.pro with:
- Detailed test procedures
- WP-CLI commands
- Database queries
- API endpoint testing
- Success criteria

### 3. Automated Testing Script
**File:** `scripts/test-pt24-poradnik.sh`

Bash script for automated testing (works with network access):
```bash
./scripts/test-pt24-poradnik.sh --domain pt24
```

### 4. Quick Reference Guide
**File:** `README-TESTING.md`

Quick reference for all testing resources with examples and usage instructions.

---

## 🧪 PT24.PRO Test Coverage

### Test Categories

| Category | Tests | Tools |
|----------|-------|-------|
| **Basic Availability** | 5 | curl, openssl, dig |
| **PT24 Platform API** | 4 | curl |
| **Landing Pages** | 6 | curl, browser |
| **Lead Forms** | 2 | curl, browser |
| **Frontend** | 8 | browser |
| **WP-CLI** | 8 | wp-cli (SSH) |
| **Database** | 3 | wp db query |
| **Performance** | 4 | time, curl |
| **Security** | 5 | curl |
| **Admin Panel** | 10 | browser |
| **Server Health** | 3 | systemctl, df, free |
| **Log Files** | 3 | tail, grep |

**Total: 61 test cases**

---

## 🚀 Quick Testing Guide

### Option 1: Quick Remote Tests (No SSH)

From your local machine:

```bash
# Test availability
curl -I https://pt24.pro

# Test health API
curl https://pt24.pro/wp-json/pearblog/v1/health

# Test PT24 API
curl https://pt24.pro/wp-json/pt24/v1/businesses

# Test landing page
curl -I https://pt24.pro/mechanik/warszawa/

# Test SSL certificate
echo | openssl s_client -servername pt24.pro -connect pt24.pro:443 2>/dev/null | grep "Verify return code"
```

### Option 2: Comprehensive SSH Testing

```bash
# SSH to server
ssh root@YOUR_SERVER_IP

# Navigate to WordPress
cd /var/www/pt24.pro

# Run platform statistics
wp pt24 stats --allow-root

# List landing pages
wp post list --post_type=pt24_landing --posts_per_page=10 --allow-root

# List businesses
wp post list --post_type=pt24_business --posts_per_page=10 --allow-root

# Check database tables
wp db query "SHOW TABLES LIKE 'wp_pt24_%'" --allow-root

# Check recent leads
wp db query "SELECT * FROM wp_pt24_leads ORDER BY created_at DESC LIMIT 5" --allow-root
```

### Option 3: Automated Testing (Requires Network Access)

```bash
# Clone repository
git clone https://github.com/AndyPearman89/PearBlog-Engine-.git
cd PearBlog-Engine-

# Run automated tests
chmod +x scripts/test-pt24-poradnik.sh
./scripts/test-pt24-poradnik.sh --domain pt24
```

### Option 4: Use the Printable Checklist

1. Open `TEST-PT24-PRO-CHECKLIST.md`
2. Print or view on second monitor
3. Work through each section
4. Check off completed tests
5. Fill in results and notes
6. Review summary at the end

---

## ✅ PT24.PRO Health Indicators

### Platform is HEALTHY if:

**Critical Checks:**
- ✅ Homepage loads (HTTP 200)
- ✅ SSL certificate valid
- ✅ Health API returns `{"status":"ok"}`
- ✅ PT24 businesses API responds
- ✅ At least 10+ landing pages exist
- ✅ Landing pages accessible at /{service}/{city}/

**Functional Checks:**
- ✅ Lead form submissions work
- ✅ Database tables exist (wp_pt24_leads, wp_pt24_business_stats)
- ✅ `wp pt24 stats` command works
- ✅ No critical errors in logs

**Performance Checks:**
- ✅ Homepage loads in < 2 seconds
- ✅ API responds in < 500ms
- ✅ Landing pages load in < 1.5 seconds

**Security Checks:**
- ✅ wp-config.php protected
- ✅ XML-RPC disabled
- ✅ Directory listing disabled
- ✅ Security headers present

---

## 🔧 PT24.PRO WP-CLI Commands Reference

### Platform Management

```bash
# Initialize platform (run once after deployment)
wp pt24 init --allow-root

# Generate landing pages
wp pt24 generate-pages --allow-root
wp pt24 generate-pages --batch=10 --allow-root
wp pt24 generate-pages --service=mechanik --city=warszawa --allow-root

# View platform statistics
wp pt24 stats --allow-root

# List landing pages
wp pt24 list --allow-root
wp pt24 list --format=table --allow-root

# Delete all landing pages (BE CAREFUL!)
wp pt24 delete-all --yes --allow-root

# Flush rewrite rules
wp pt24 flush-rewrites --allow-root
```

### Content Management

```bash
# List landing pages
wp post list --post_type=pt24_landing --allow-root

# List businesses
wp post list --post_type=pt24_business --allow-root

# Count landing pages
wp post list --post_type=pt24_landing --format=count --allow-root

# Count businesses
wp post list --post_type=pt24_business --format=count --allow-root
```

### Database Queries

```bash
# Show PT24 tables
wp db query "SHOW TABLES LIKE 'wp_pt24_%'" --allow-root

# Count leads
wp db query "SELECT COUNT(*) as total FROM wp_pt24_leads" --allow-root

# Recent leads
wp db query "SELECT * FROM wp_pt24_leads ORDER BY created_at DESC LIMIT 10" --allow-root

# Leads by service
wp db query "SELECT service, COUNT(*) as total FROM wp_pt24_leads GROUP BY service" --allow-root

# Business stats
wp db query "SELECT * FROM wp_pt24_business_stats ORDER BY views DESC LIMIT 10" --allow-root
```

### Taxonomy Management

```bash
# List cities
wp term list pt24_city --allow-root

# Count cities
wp term list pt24_city --format=count --allow-root

# List service categories
wp term list pt24_service_cat --allow-root

# Count service categories
wp term list pt24_service_cat --format=count --allow-root
```

---

## 🚨 Common Issues & Solutions

### Issue 1: Landing Pages Return 404

**Symptoms:**
- https://pt24.pro/mechanik/warszawa/ returns 404
- Landing pages not accessible

**Solution:**
```bash
ssh root@YOUR_SERVER_IP
cd /var/www/pt24.pro
wp rewrite flush --allow-root
wp pt24 init --allow-root
```

### Issue 2: No Landing Pages Exist

**Symptoms:**
- `wp pt24 stats` shows 0 landing pages
- Empty business directory

**Solution:**
```bash
ssh root@YOUR_SERVER_IP
cd /var/www/pt24.pro
wp pt24 init --allow-root
wp pt24 generate-pages --batch=10 --allow-root
```

### Issue 3: PT24 API Returns Empty

**Symptoms:**
- API call returns `{"businesses":[]}`
- No businesses shown on landing pages

**Solution:**
```bash
# Check if businesses exist
wp post list --post_type=pt24_business --allow-root

# If none exist, add test business via WP Admin
# or see DEPLOYMENT-pt24-pro.md for CLI commands
```

### Issue 4: Database Tables Missing

**Symptoms:**
- SQL errors in logs
- Lead forms not working
- Stats not tracking

**Solution:**
```bash
ssh root@YOUR_SERVER_IP
cd /var/www/pt24.pro
wp pt24 init --allow-root

# Verify tables exist
wp db query "SHOW TABLES LIKE 'wp_pt24_%'" --allow-root
```

### Issue 5: Form Submissions Not Working

**Symptoms:**
- Form submit button does nothing
- No leads in database
- JavaScript console errors

**Solution:**
```bash
# Check if PT24 MU plugin is loaded
ls -la /var/www/pt24.pro/wp-content/mu-plugins/pt24-local-services.php

# Check form handler in theme
grep "pt24-form-handler" /var/www/pt24.pro/wp-content/themes/pearblog-theme/functions.php

# Check AJAX endpoint
grep "pt24_submit_lead" /var/www/pt24.pro/wp-content/themes/pearblog-theme/inc/pt24-form-handler.php

# Clear cache if using caching plugin
wp cache flush --allow-root
```

---

## 📊 Expected Platform Statistics

After proper initialization, `wp pt24 stats` should show:

```
PT24 Platform Statistics
========================
Landing Pages: 100+
  - mechanik: 20
  - hydraulik: 20
  - elektryk: 20
  - laweta: 20
  - wulkanizacja: 20

Businesses: 5+ (depending on onboarding)

Cities: 20+
  - Warszawa, Kraków, Wrocław, Poznań, Gdańsk
  - Szczecin, Bydgoszcz, Lublin, Katowice, Białystok
  - (+ 10 more)

Service Categories: 5
  - Mechanik samochodowy
  - Hydraulik
  - Elektryk samochodowy
  - Laweta
  - Wulkanizacja
```

---

## 📈 Performance Benchmarks

### Expected Response Times

| Endpoint | Target | Acceptable | Critical |
|----------|--------|------------|----------|
| Homepage | < 1s | < 2s | > 3s |
| Landing Page | < 1s | < 1.5s | > 2s |
| API - Businesses | < 300ms | < 500ms | > 1s |
| API - Single Business | < 200ms | < 400ms | > 800ms |
| Lead Form Submit | < 500ms | < 1s | > 2s |

### Server Resources

| Resource | Healthy | Warning | Critical |
|----------|---------|---------|----------|
| Disk Space Free | > 30% | 10-30% | < 10% |
| Memory Free | > 1GB | 500MB-1GB | < 500MB |
| CPU Load | < 2.0 | 2.0-4.0 | > 4.0 |
| MySQL Connections | < 50 | 50-100 | > 100 |

---

## 📞 Support & Resources

### Documentation
- **Deployment Guide:** [DEPLOYMENT-pt24-pro.md](DEPLOYMENT-pt24-pro.md)
- **Quick Start:** [QUICKSTART-pt24-pro.md](QUICKSTART-pt24-pro.md)
- **Testing Checklist:** [TEST-PT24-PRO-CHECKLIST.md](TEST-PT24-PRO-CHECKLIST.md)
- **Complete Testing Guide:** [TEST-pt24-pro-poradnik-pro.md](TEST-pt24-pro-poradnik-pro.md)
- **Troubleshooting:** [TROUBLESHOOTING.md](TROUBLESHOOTING.md)

### GitHub
- **Repository:** https://github.com/AndyPearman89/PearBlog-Engine-
- **Issues:** https://github.com/AndyPearman89/PearBlog-Engine-/issues
- **Discussions:** https://github.com/AndyPearman89/PearBlog-Engine-/discussions

---

## 🎓 Testing Best Practices

1. **Regular Testing Schedule:**
   - Daily: Quick availability checks (5 min)
   - Weekly: Comprehensive checklist (30 min)
   - Monthly: Full audit with all tests (2 hours)

2. **Pre-Deployment Testing:**
   - Run all tests before any code changes
   - Document baseline performance metrics
   - Create backup before making changes

3. **Post-Incident Testing:**
   - After any downtime, run full checklist
   - Verify all services restored
   - Check for data integrity issues

4. **Continuous Monitoring:**
   - Set up uptime monitoring (UptimeRobot, Pingdom)
   - Configure alerts for critical endpoints
   - Monitor response times and error rates

5. **Documentation:**
   - Keep testing logs for historical reference
   - Document any issues and resolutions
   - Update checklists as platform evolves

---

**Created:** 2026-05-04
**Version:** 1.0
**Status:** Ready for production testing ✅

---

## 🎯 Next Steps

1. **Review the practical checklist:**
   ```bash
   cat TEST-PT24-PRO-CHECKLIST.md
   ```

2. **Run quick remote tests:**
   ```bash
   curl -I https://pt24.pro
   curl https://pt24.pro/wp-json/pt24/v1/businesses
   ```

3. **SSH and verify platform:**
   ```bash
   ssh root@YOUR_SERVER_IP
   cd /var/www/pt24.pro
   wp pt24 stats --allow-root
   ```

4. **Document results** and address any issues

5. **Schedule regular testing** using the checklist

---

**Testing resources for PT24.PRO are now complete and ready for use!** 🚀

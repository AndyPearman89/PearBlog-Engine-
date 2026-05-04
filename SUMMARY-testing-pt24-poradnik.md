# 📋 Summary: Testing Resources for pt24.pro i poradnik.pro

**Date:** 2026-05-04
**Branch:** `claude/test-pt24-pro-and-poradnik-pro`
**Status:** ✅ Complete

---

## 🎯 What Was Created

### 1. Comprehensive Test Documentation
**File:** `TEST-pt24-pro-poradnik-pro.md`

A complete testing guide including:
- **pt24.pro Tests:**
  - Basic availability (HTTP, SSL, DNS)
  - PT24 Platform API endpoints
  - Landing page URL structure
  - Lead form functionality
  - WP-CLI commands for platform management
  - Frontend functionality checklist

- **poradnik.pro Tests:**
  - Basic availability (HTTP, SSL, DNS)
  - PearBlog API endpoints
  - Content generation pipeline
  - WP-Cron scheduling
  - Log file monitoring
  - Database integrity checks
  - Frontend functionality checklist

- **Security Tests:**
  - wp-config.php protection
  - XML-RPC status
  - Directory listing
  - Security headers

- **Performance Benchmarks:**
  - Load time expectations
  - Response time targets
  - TTFB metrics

### 2. Automated Testing Script
**File:** `scripts/test-pt24-poradnik.sh`

Bash script for automated testing with:
- HTTP status checks for all critical endpoints
- SSL certificate verification
- DNS resolution tests
- API endpoint functionality tests
- Response time measurements
- Security vulnerability checks
- SSH-based WP-CLI tests (for poradnik.pro)
- Colored output (pass/fail indicators)
- Test summary with success rate calculation

**Usage:**
```bash
# Test both platforms
./scripts/test-pt24-poradnik.sh

# Test specific platform
./scripts/test-pt24-poradnik.sh --domain pt24
./scripts/test-pt24-poradnik.sh --domain poradnik

# Verbose mode
./scripts/test-pt24-poradnik.sh --verbose
```

### 3. Testing README
**File:** `README-TESTING.md`

Quick reference guide with:
- Overview of testing resources
- Quick test commands for each platform
- Success criteria checklists
- Example usage of automated script
- Links to deployment and troubleshooting docs

---

## 🧪 Test Coverage

### pt24.pro Platform Tests

| Category | Tests | Description |
|----------|-------|-------------|
| Availability | 5 | Homepage, WWW, SSL, DNS, Health API |
| PT24 API | 2 | Businesses endpoint, filtered queries |
| Landing Pages | 4 | Service/city URLs, 404 handling |
| Lead Form | 1 | Form submission endpoint |
| Security | 3 | File protection, XML-RPC, directory listing |
| Performance | 1 | Response time < 2s |
| WP-CLI | 6 | Platform stats, pages, businesses, database |

**Total: 22 automated tests**

### poradnik.pro Platform Tests

| Category | Tests | Description |
|----------|-------|-------------|
| Availability | 5 | Homepage, WWW, SSL, DNS, Health API |
| WordPress API | 1 | Posts endpoint |
| PearBlog | 1 | Health/status endpoint |
| Security | 3 | File protection, XML-RPC, directory listing |
| Performance | 1 | Response time < 2s |
| WP-CLI (SSH) | 4 | Stats, post count, autonomous mode, autopilot |

**Total: 15 automated tests**

### Combined: 37+ automated tests

---

## ✅ Success Criteria

### pt24.pro is healthy when:
- ✅ All HTTP endpoints return 200
- ✅ SSL certificate is valid
- ✅ PT24 API responds with business data
- ✅ Landing pages accessible (/{service}/{city}/)
- ✅ Lead form accepts submissions
- ✅ At least 10+ landing pages exist
- ✅ Database tables present (wp_pt24_leads, wp_pt24_business_stats)
- ✅ Platform stats accessible via `wp pt24 stats`

### poradnik.pro is healthy when:
- ✅ All HTTP endpoints return 200
- ✅ SSL certificate is valid
- ✅ Health API returns OK status
- ✅ At least 1+ article published
- ✅ Content pipeline scheduled (hourly)
- ✅ Autonomous mode enabled
- ✅ Autopilot status active
- ✅ OpenAI API key configured

---

## 📊 Test Execution Examples

### Manual Quick Tests

**pt24.pro:**
```bash
curl -I https://pt24.pro
curl https://pt24.pro/wp-json/pt24/v1/businesses
curl -I https://pt24.pro/mechanik/warszawa/
```

**poradnik.pro:**
```bash
curl -I https://poradnik.pro
curl https://poradnik.pro/wp-json/pearblog/v1/health
ssh root@204.48.27.118 "cd /var/www/poradnik.pro && wp pearblog stats --allow-root"
```

### Automated Tests

```bash
# Run all tests
./scripts/test-pt24-poradnik.sh

# Expected output:
# ========================================
# Test Summary
# ========================================
# Total Tests:  37
# Passed:       35
# Failed:       2
#
# Success Rate: 95%
#
# ✓ All tests passed!
```

---

## 🔧 Quick Fix Commands

Included in documentation for common issues:

**pt24.pro:**
```bash
wp rewrite flush --allow-root
wp pt24 init --allow-root
wp pt24 generate-pages --batch=10 --allow-root
```

**poradnik.pro:**
```bash
wp pearblog circuit reset --allow-root
wp cron event run pearblog_content_pipeline --allow-root
wp pearblog autopilot start --allow-root
```

---

## 📁 Files Changed

```
TEST-pt24-pro-poradnik-pro.md    (new file, 708 lines)
scripts/test-pt24-poradnik.sh    (new file, 406 lines, executable)
README-TESTING.md                (new file, 173 lines)
```

**Total:** 3 new files, 1,287 lines of testing documentation and automation

---

## 🚀 Usage Instructions

### For Development/Testing Team

1. **Read the comprehensive guide:**
   ```bash
   cat TEST-pt24-pro-poradnik-pro.md
   ```

2. **Run automated tests:**
   ```bash
   ./scripts/test-pt24-poradnik.sh
   ```

3. **Review results** and fix any failing tests

4. **For SSH tests** (poradnik.pro), ensure SSH access is configured:
   ```bash
   ssh root@204.48.27.118
   ```

### For CI/CD Integration

Add to GitHub Actions or Jenkins:
```yaml
- name: Test Production Sites
  run: |
    chmod +x scripts/test-pt24-poradnik.sh
    ./scripts/test-pt24-poradnik.sh
```

### For Manual Testing

Follow the detailed checklists in `TEST-pt24-pro-poradnik-pro.md` for:
- Frontend functionality testing
- Admin panel verification
- Business workflows
- Content generation workflows

---

## 📝 Test Execution Log Template

Included in documentation for tracking test runs:

```
Test Date: YYYY-MM-DD HH:MM
Tester: [Name]
Environment: Production

=== pt24.pro Tests ===
[✓] Basic availability
[✓] SSL certificate
[✓] Health endpoint
[✓] PT24 API endpoints
[✓] Landing pages
[✓] Lead form
[✓] WP-CLI commands
[✓] Database integrity
[✓] Frontend functionality

Issues found: None

=== poradnik.pro Tests ===
[✓] Basic availability
[✓] SSL certificate
[✓] Health endpoint
...

Overall Status: PASS
```

---

## 🔗 Related Documentation

- **Deployment Guides:**
  - [DEPLOYMENT-pt24-pro.md](DEPLOYMENT-pt24-pro.md)
  - [DEPLOYMENT-poradnik-pro.md](DEPLOYMENT-poradnik-pro.md)

- **Quick Start Guides:**
  - [QUICKSTART-pt24-pro.md](QUICKSTART-pt24-pro.md)
  - [QUICKSTART-poradnik-pro.md](QUICKSTART-poradnik-pro.md)

- **Troubleshooting:**
  - [TROUBLESHOOTING.md](TROUBLESHOOTING.md)

---

## 🎓 Key Features

### Automated Script Features:
- ✅ Zero configuration needed
- ✅ Colored output (pass/fail indicators)
- ✅ Multiple test modes (both, pt24, poradnik)
- ✅ Verbose mode for detailed output
- ✅ Exit codes (0 = success, 1 = failures)
- ✅ Summary with success rate percentage
- ✅ SSH integration for deep testing
- ✅ Response time measurements
- ✅ Security vulnerability checks

### Documentation Features:
- ✅ Complete test coverage guide
- ✅ Step-by-step manual testing procedures
- ✅ Success criteria checklists
- ✅ Performance benchmarks
- ✅ Quick fix commands for common issues
- ✅ Test execution log template
- ✅ Security testing procedures
- ✅ WP-CLI command reference

---

## 🎯 Next Steps

1. **Review the documentation:**
   - Read `TEST-pt24-pro-poradnik-pro.md`
   - Check `README-TESTING.md` for quick reference

2. **Test the automated script:**
   ```bash
   ./scripts/test-pt24-poradnik.sh --domain both
   ```

3. **Set up CI/CD integration** (optional):
   - Add script to GitHub Actions
   - Schedule periodic testing

4. **Create test schedule:**
   - Daily automated tests
   - Weekly manual verification
   - Monthly comprehensive audit

5. **Monitor and improve:**
   - Track test results over time
   - Add new tests as features are added
   - Update success criteria as needed

---

## 📞 Support

If you have questions about testing:
- **Documentation:** See `TEST-pt24-pro-poradnik-pro.md`
- **Issues:** https://github.com/AndyPearman89/PearBlog-Engine-/issues
- **Discussions:** https://github.com/AndyPearman89/PearBlog-Engine-/discussions

---

**Created by:** Claude Code Agent
**Date:** 2026-05-04
**Branch:** `claude/test-pt24-pro-and-poradnik-pro`
**Status:** ✅ Ready for use

**Summary:** Comprehensive testing resources for both pt24.pro and poradnik.pro platforms, including automated testing script and detailed documentation covering 37+ automated tests across availability, functionality, security, and performance.

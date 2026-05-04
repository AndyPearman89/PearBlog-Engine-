# 🧪 Testing pt24.pro i poradnik.pro

Created comprehensive testing resources for both production deployments.

## 📚 Documentation

### [TEST-pt24-pro-poradnik-pro.md](TEST-pt24-pro-poradnik-pro.md)
Complete testing documentation including:
- Basic availability tests
- API endpoint tests
- Landing page tests (pt24.pro)
- Content generation tests (poradnik.pro)
- Security tests
- Performance benchmarks
- WP-CLI verification commands
- Success criteria
- Test execution log template

## 🚀 Automated Testing Script

### [scripts/test-pt24-poradnik.sh](scripts/test-pt24-poradnik.sh)
Automated bash script for quick verification of both platforms.

**Usage:**

```bash
# Test both platforms
./scripts/test-pt24-poradnik.sh

# Test only pt24.pro
./scripts/test-pt24-poradnik.sh --domain pt24

# Test only poradnik.pro
./scripts/test-pt24-poradnik.sh --domain poradnik

# Verbose output
./scripts/test-pt24-poradnik.sh --verbose
```

**Features:**
- HTTP status checks
- SSL certificate verification
- DNS resolution tests
- API endpoint testing
- Response time measurements
- Security checks
- SSH-based tests for poradnik.pro (WP-CLI commands)
- Colored output with pass/fail indicators
- Test summary with success rate

## 🎯 Quick Tests

### pt24.pro Basic Tests

```bash
# Homepage
curl -I https://pt24.pro

# Health API
curl https://pt24.pro/wp-json/pearblog/v1/health

# PT24 Businesses API
curl https://pt24.pro/wp-json/pt24/v1/businesses

# Landing page example
curl -I https://pt24.pro/mechanik/warszawa/

# Platform stats (via SSH)
ssh YOUR_SERVER "cd /var/www/pt24.pro && wp pt24 stats --allow-root"
```

### poradnik.pro Basic Tests

```bash
# Homepage
curl -I https://poradnik.pro

# Health API
curl https://poradnik.pro/wp-json/pearblog/v1/health

# Posts API
curl https://poradnik.pro/wp-json/wp/v2/posts?per_page=5

# PearBlog stats (via SSH)
ssh root@204.48.27.118 "cd /var/www/poradnik.pro && wp pearblog stats --allow-root"

# Check autonomous mode
ssh root@204.48.27.118 "cd /var/www/poradnik.pro && wp option get pearblog_autonomous_mode --allow-root"
```

## ✅ Success Criteria

### pt24.pro
- ✅ Homepage loads (HTTP 200)
- ✅ SSL certificate valid
- ✅ Health API returns OK
- ✅ PT24 API endpoints respond
- ✅ Landing pages accessible
- ✅ Lead form functional
- ✅ Database tables exist
- ✅ No critical errors

### poradnik.pro
- ✅ Homepage loads (HTTP 200)
- ✅ SSL certificate valid
- ✅ Health API returns OK
- ✅ Articles published
- ✅ Content pipeline scheduled
- ✅ Autonomous mode enabled
- ✅ Autopilot running
- ✅ No critical errors

## 📊 Running Automated Tests

The automated script provides immediate feedback:

```bash
$ ./scripts/test-pt24-poradnik.sh

========================================
Testing pt24.pro
========================================

[TEST] DNS resolution for pt24.pro
[PASS] DNS resolves to XXX.XXX.XXX.XXX
[TEST] SSL certificate for pt24.pro
[PASS] SSL certificate valid
[TEST] Homepage availability
[PASS] HTTP 200 (expected 200)
...

========================================
Test Summary
========================================
Total Tests:  32
Passed:       30
Failed:       2

Success Rate: 94%
```

## 🔧 Troubleshooting

If tests fail, consult:
- [DEPLOYMENT-pt24-pro.md](DEPLOYMENT-pt24-pro.md) - pt24.pro deployment guide
- [DEPLOYMENT-poradnik-pro.md](DEPLOYMENT-poradnik-pro.md) - poradnik.pro deployment guide
- [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - General troubleshooting guide

## 📝 Manual Testing

For detailed manual testing procedures, follow the checklists in:
- [TEST-pt24-pro-poradnik-pro.md](TEST-pt24-pro-poradnik-pro.md)

## 🔐 Security Testing

Both platforms include security checks:
- wp-config.php protection
- readme.html removal
- XML-RPC disabled
- Directory listing disabled
- Security headers present

## 📞 Support

- **Documentation:** [DOCUMENTATION-INDEX.md](DOCUMENTATION-INDEX.md)
- **GitHub Issues:** https://github.com/AndyPearman89/PearBlog-Engine-/issues
- **Discussions:** https://github.com/AndyPearman89/PearBlog-Engine-/discussions

---

**Created:** 2026-05-04
**Version:** 1.0
**Status:** Ready for use 🚀

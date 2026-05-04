# 🚀 PT24.PRO Checklist Execution Guide

## Overview

The PT24.PRO checklist can be executed in two ways:
1. **Automated Script** - Runs tests automatically and generates a report
2. **Manual Checklist** - Use the detailed checklist document for manual testing

---

## Option 1: Automated Execution

### Prerequisites
- Network access to pt24.pro
- Command-line tools: `curl`, `dig`, `openssl`, `bc`
- Optional: SSH access for server-side tests

### Quick Start

```bash
# Make script executable (if not already)
chmod +x scripts/execute-pt24-checklist.sh

# Run the automated tests
./scripts/execute-pt24-checklist.sh
```

### What It Tests

The automated script executes **17 automated tests** covering:

1. **Basic Availability (4 tests)**
   - Homepage HTTP status
   - WWW redirect
   - SSL certificate validation
   - DNS resolution

2. **API Endpoints (2 tests)**
   - Health API response
   - Businesses API response

3. **Landing Pages (6 tests)**
   - mechanik/warszawa
   - hydraulik/krakow
   - elektryk/wroclaw
   - laweta/poznan
   - wulkanizacja/gdansk
   - 404 error handling

4. **Performance (2 tests)**
   - Homepage load time
   - API response time

5. **Security (3 tests)**
   - wp-config.php protection
   - readme.html removal
   - Security headers

### Output

The script generates:
- **Console output** with color-coded pass/fail results
- **Detailed report** saved as `PT24-PRO-TEST-RESULTS-[timestamp].md`

Example report structure:
```
# ✅ PT24.PRO Production Testing Results

**Test Date:** 2026-05-04 04:13:25
**Tester:** Claude Code Agent

## 🧪 Test Execution Results

### Basic Availability: Homepage loads
**Status:** ✅ PASS
**Details:** HTTP 200

[... more test results ...]

## 📊 Test Summary
**Total Tests Executed:** 17
**Tests Passed:** 15
**Tests Failed:** 2
**Success Rate:** 88%
```

---

## Option 2: Manual Checklist

### Use the Detailed Checklist

For comprehensive testing including SSH-based tests and manual browser testing:

```bash
# Open the checklist
cat TEST-PT24-PRO-CHECKLIST.md

# Or view in your browser/editor
open TEST-PT24-PRO-CHECKLIST.md
```

### Checklist Features

The manual checklist includes **61+ test cases** covering:
- All automated tests plus
- WP-CLI server-side commands
- Database integrity checks
- Frontend manual testing
- Admin panel verification
- Server health checks
- Log file analysis

### How to Use

1. **Print or display** the checklist
2. **Work through** each section systematically
3. **Check off** completed tests
4. **Fill in** results and notes
5. **Document** any issues found
6. **Complete** the summary section

---

## Combined Approach (Recommended)

For best results, use both methods:

### Step 1: Run Automated Tests
```bash
./scripts/execute-pt24-checklist.sh
```

This gives you a quick health check and baseline metrics.

### Step 2: Manual Testing
```bash
# SSH to server
ssh root@YOUR_SERVER_IP
cd /var/www/pt24.pro

# Run WP-CLI tests
wp pt24 stats --allow-root
wp post list --post_type=pt24_landing --allow-root
wp db query "SELECT COUNT(*) FROM wp_pt24_leads" --allow-root
```

### Step 3: Frontend Testing
- Open browser to https://pt24.pro
- Test navigation and functionality
- Verify forms work
- Check mobile responsiveness

### Step 4: Review and Document
- Compare automated results with manual findings
- Document any discrepancies
- Create action items for issues found

---

## Example Workflow

### Daily Health Check (5 minutes)
```bash
./scripts/execute-pt24-checklist.sh
```
Review the generated report for any failures.

### Weekly Comprehensive Test (30 minutes)
1. Run automated script
2. SSH to server and run WP-CLI tests
3. Quick frontend check in browser
4. Review logs for errors

### Monthly Full Audit (2 hours)
1. Run automated script
2. Complete full manual checklist
3. Frontend testing on multiple devices
4. Performance profiling
5. Security audit
6. Document findings and recommendations

---

## Understanding Test Results

### Exit Codes

The automated script returns:
- **0** - All tests passed
- **1** - One or more tests failed

### Status Indicators

- ✅ **PASS** - Test succeeded
- ❌ **FAIL** - Test failed (needs attention)
- ⚠️ **WARN** - Test passed but with warnings
- ⏭️ **SKIP** - Test skipped (not applicable)

### Common Failure Reasons

**Network Issues:**
- HTTP 000: Network connectivity problem
- Timeout: Server not responding

**Configuration Issues:**
- HTTP 404: Landing pages not generated or rewrite rules not flushed
- HTTP 500: Server error (check logs)
- API errors: Plugin not loaded or database issues

**Security Issues:**
- HTTP 200 on wp-config.php: File not protected
- Missing headers: Web server configuration needed

---

## Troubleshooting

### Automated Script Fails

**Issue:** All tests return HTTP 000
- **Cause:** No network access or DNS issue
- **Fix:** Check network connectivity and DNS resolution

**Issue:** Tests pass but site doesn't work
- **Cause:** False positives from cached responses
- **Fix:** Add `--no-cache-dir` or `-H "Cache-Control: no-cache"` to curl commands

**Issue:** Performance tests show unexpected times
- **Cause:** Sandboxed environment or network latency
- **Fix:** Run tests from production environment for accurate metrics

### Manual Checklist Issues

**Issue:** WP-CLI commands fail
- **Cause:** Not on server or wrong directory
- **Fix:** SSH to server and `cd /var/www/pt24.pro`

**Issue:** Database queries fail
- **Cause:** Tables don't exist
- **Fix:** Run `wp pt24 init --allow-root`

---

## Files Reference

| File | Purpose |
|------|---------|
| `TEST-PT24-PRO-CHECKLIST.md` | Comprehensive manual checklist (61+ tests) |
| `scripts/execute-pt24-checklist.sh` | Automated execution script (17 tests) |
| `PT24-PRO-TESTING-SUMMARY.md` | Complete testing guide and reference |
| `PT24-PRO-TEST-RESULTS-*.md` | Generated test reports (timestamped) |

---

## Integration with CI/CD

### GitHub Actions Example

```yaml
name: PT24.PRO Health Check

on:
  schedule:
    - cron: '0 */6 * * *'  # Every 6 hours
  workflow_dispatch:

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Run PT24.PRO Tests
        run: |
          chmod +x scripts/execute-pt24-checklist.sh
          ./scripts/execute-pt24-checklist.sh

      - name: Upload Results
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: test-results
          path: PT24-PRO-TEST-RESULTS-*.md
```

### Cron Job Example

```bash
# Add to crontab for daily testing at 3 AM
0 3 * * * cd /path/to/repo && ./scripts/execute-pt24-checklist.sh && mail -s "PT24 Test Results" admin@example.com < PT24-PRO-TEST-RESULTS-*.md
```

---

## Next Steps

After running the checklist:

1. **If all tests pass:**
   - ✅ Platform is healthy
   - Document baseline metrics
   - Schedule next test

2. **If tests fail:**
   - Review failure details
   - Check logs for errors
   - Apply fixes from troubleshooting guide
   - Re-run tests to verify

3. **For production deployment:**
   - Run full manual checklist
   - Document all findings
   - Get sign-off before going live
   - Set up monitoring

---

## Support

- **Checklist:** [TEST-PT24-PRO-CHECKLIST.md](../TEST-PT24-PRO-CHECKLIST.md)
- **Testing Guide:** [PT24-PRO-TESTING-SUMMARY.md](../PT24-PRO-TESTING-SUMMARY.md)
- **Deployment:** [DEPLOYMENT-pt24-pro.md](../DEPLOYMENT-pt24-pro.md)
- **Issues:** https://github.com/AndyPearman89/PearBlog-Engine-/issues

---

**Created:** 2026-05-04
**Version:** 1.0
**Status:** Ready for production use ✅

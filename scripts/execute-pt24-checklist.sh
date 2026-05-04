#!/bin/bash
################################################################################
# PT24.PRO Checklist Execution Script
#
# This script executes the PT24.PRO testing checklist automatically
# and generates a filled-out report with test results
#
# Date: 2026-05-04
################################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DOMAIN="pt24.pro"
TEST_DATE=$(date +"%Y-%m-%d %H:%M:%S")
TESTER="Claude Code Agent"
OUTPUT_FILE="PT24-PRO-TEST-RESULTS-$(date +%Y%m%d-%H%M%S).md"

# Test counters
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0
SKIPPED_TESTS=0

################################################################################
# Helper Functions
################################################################################

print_header() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}\n"
}

print_test() {
    echo -e "${BLUE}[TEST]${NC} $1"
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
}

print_pass() {
    echo -e "${GREEN}[PASS]${NC} $1"
    PASSED_TESTS=$((PASSED_TESTS + 1))
}

print_fail() {
    echo -e "${RED}[FAIL]${NC} $1"
    FAILED_TESTS=$((FAILED_TESTS + 1))
}

print_skip() {
    echo -e "${YELLOW}[SKIP]${NC} $1"
    SKIPPED_TESTS=$((SKIPPED_TESTS + 1))
}

# Initialize output file
init_report() {
    cat > "$OUTPUT_FILE" <<EOF
# ✅ PT24.PRO Production Testing Results

**Domain:** pt24.pro
**Test Date:** $TEST_DATE
**Tester:** $TESTER
**Environment:** Production

---

## 🧪 Test Execution Results

EOF
}

# Add test result to report
add_result() {
    local category="$1"
    local test_name="$2"
    local status="$3"
    local details="$4"

    echo "### $category: $test_name" >> "$OUTPUT_FILE"
    echo "**Status:** $status" >> "$OUTPUT_FILE"
    echo "**Details:** $details" >> "$OUTPUT_FILE"
    echo "" >> "$OUTPUT_FILE"
}

################################################################################
# Test Functions
################################################################################

test_basic_availability() {
    print_header "1. Basic Availability Tests"

    # Test 1: Homepage loads
    print_test "Homepage availability"
    local status=$(curl -s -o /dev/null -w "%{http_code}" "https://$DOMAIN" 2>/dev/null || echo "000")
    if [ "$status" = "200" ]; then
        print_pass "Homepage returns HTTP $status"
        add_result "Basic Availability" "Homepage loads" "✅ PASS" "HTTP $status"
    else
        print_fail "Homepage returns HTTP $status (expected 200)"
        add_result "Basic Availability" "Homepage loads" "❌ FAIL" "HTTP $status (expected 200)"
    fi

    # Test 2: WWW redirect
    print_test "WWW redirect"
    local status=$(curl -s -o /dev/null -w "%{http_code}" "https://www.$DOMAIN" 2>/dev/null || echo "000")
    if [ "$status" = "200" ] || [ "$status" = "301" ]; then
        print_pass "WWW redirect returns HTTP $status"
        add_result "Basic Availability" "WWW redirect" "✅ PASS" "HTTP $status"
    else
        print_fail "WWW redirect returns HTTP $status (expected 200 or 301)"
        add_result "Basic Availability" "WWW redirect" "❌ FAIL" "HTTP $status"
    fi

    # Test 3: SSL certificate
    print_test "SSL certificate validation"
    local ssl_result=$(echo | openssl s_client -servername "$DOMAIN" -connect "$DOMAIN:443" 2>/dev/null | grep "Verify return code" || echo "Failed")
    if echo "$ssl_result" | grep -q "0 (ok)"; then
        print_pass "SSL certificate is valid"
        add_result "Basic Availability" "SSL certificate" "✅ PASS" "Certificate valid"
    else
        print_fail "SSL certificate issue: $ssl_result"
        add_result "Basic Availability" "SSL certificate" "❌ FAIL" "$ssl_result"
    fi

    # Test 4: DNS resolution
    print_test "DNS resolution"
    local ip=$(dig +short "$DOMAIN" | head -n1 || echo "Failed")
    if [ -n "$ip" ] && [ "$ip" != "Failed" ]; then
        print_pass "DNS resolves to $ip"
        add_result "Basic Availability" "DNS resolution" "✅ PASS" "Resolves to $ip"
    else
        print_fail "DNS resolution failed"
        add_result "Basic Availability" "DNS resolution" "❌ FAIL" "DNS resolution failed"
    fi
}

test_api_endpoints() {
    print_header "2. PT24 Platform API Tests"

    # Test 1: Health endpoint
    print_test "Health API endpoint"
    local response=$(curl -s "https://$DOMAIN/wp-json/pearblog/v1/health" 2>/dev/null || echo '{"error":"failed"}')
    if echo "$response" | grep -q '"status"'; then
        print_pass "Health API responds: $response"
        add_result "API Tests" "Health endpoint" "✅ PASS" "$response"
    else
        print_fail "Health API failed: $response"
        add_result "API Tests" "Health endpoint" "❌ FAIL" "$response"
    fi

    # Test 2: Businesses API
    print_test "Businesses API endpoint"
    local response=$(curl -s "https://$DOMAIN/wp-json/pt24/v1/businesses" 2>/dev/null || echo '{"error":"failed"}')
    if echo "$response" | grep -q 'businesses'; then
        local count=$(echo "$response" | grep -o '"businesses":\[' | wc -l)
        print_pass "Businesses API responds"
        add_result "API Tests" "Businesses endpoint" "✅ PASS" "API responding"
    else
        print_fail "Businesses API failed: $response"
        add_result "API Tests" "Businesses endpoint" "❌ FAIL" "$response"
    fi
}

test_landing_pages() {
    print_header "3. Landing Pages Tests"

    local services=("mechanik/warszawa" "hydraulik/krakow" "elektryk/wroclaw" "laweta/poznan" "wulkanizacja/gdansk")

    for service_city in "${services[@]}"; do
        print_test "Landing page: $service_city"
        local status=$(curl -s -o /dev/null -w "%{http_code}" "https://$DOMAIN/$service_city/" 2>/dev/null || echo "000")
        if [ "$status" = "200" ]; then
            print_pass "Landing page $service_city returns HTTP $status"
            add_result "Landing Pages" "$service_city" "✅ PASS" "HTTP $status"
        else
            print_fail "Landing page $service_city returns HTTP $status (expected 200)"
            add_result "Landing Pages" "$service_city" "❌ FAIL" "HTTP $status"
        fi
    done

    # Test 404 handling
    print_test "404 error handling"
    local status=$(curl -s -o /dev/null -w "%{http_code}" "https://$DOMAIN/invalid-service/invalid-city/" 2>/dev/null || echo "000")
    if [ "$status" = "404" ]; then
        print_pass "404 page returns HTTP $status"
        add_result "Landing Pages" "404 handling" "✅ PASS" "HTTP $status"
    else
        print_fail "404 page returns HTTP $status (expected 404)"
        add_result "Landing Pages" "404 handling" "❌ FAIL" "HTTP $status"
    fi
}

test_performance() {
    print_header "4. Performance Tests"

    # Test 1: Homepage load time
    print_test "Homepage load time"
    local time_output=$(mktemp)
    curl -o /dev/null -s -w "%{time_total}" "https://$DOMAIN" > "$time_output" 2>/dev/null || echo "999"
    local load_time=$(cat "$time_output")
    rm -f "$time_output"

    if (( $(echo "$load_time < 2.0" | bc -l 2>/dev/null || echo 0) )); then
        print_pass "Homepage loads in ${load_time}s (target: <2s)"
        add_result "Performance" "Homepage load time" "✅ PASS" "${load_time}s"
    else
        print_fail "Homepage loads in ${load_time}s (target: <2s)"
        add_result "Performance" "Homepage load time" "⚠️ SLOW" "${load_time}s"
    fi

    # Test 2: API response time
    print_test "API response time"
    local time_output=$(mktemp)
    curl -o /dev/null -s -w "%{time_total}" "https://$DOMAIN/wp-json/pt24/v1/businesses" > "$time_output" 2>/dev/null || echo "999"
    local api_time=$(cat "$time_output")
    rm -f "$time_output"

    if (( $(echo "$api_time < 0.5" | bc -l 2>/dev/null || echo 0) )); then
        print_pass "API responds in ${api_time}s (target: <0.5s)"
        add_result "Performance" "API response time" "✅ PASS" "${api_time}s"
    else
        print_fail "API responds in ${api_time}s (target: <0.5s)"
        add_result "Performance" "API response time" "⚠️ SLOW" "${api_time}s"
    fi
}

test_security() {
    print_header "5. Security Tests"

    # Test 1: wp-config.php protected
    print_test "wp-config.php protection"
    local status=$(curl -s -o /dev/null -w "%{http_code}" "https://$DOMAIN/wp-config.php" 2>/dev/null || echo "000")
    if [ "$status" = "403" ] || [ "$status" = "404" ]; then
        print_pass "wp-config.php protected (HTTP $status)"
        add_result "Security" "wp-config.php protection" "✅ PASS" "HTTP $status"
    else
        print_fail "wp-config.php not protected (HTTP $status)"
        add_result "Security" "wp-config.php protection" "❌ FAIL" "HTTP $status"
    fi

    # Test 2: readme.html removed
    print_test "readme.html removed"
    local status=$(curl -s -o /dev/null -w "%{http_code}" "https://$DOMAIN/readme.html" 2>/dev/null || echo "000")
    if [ "$status" = "403" ] || [ "$status" = "404" ]; then
        print_pass "readme.html removed or protected (HTTP $status)"
        add_result "Security" "readme.html" "✅ PASS" "HTTP $status"
    else
        print_fail "readme.html accessible (HTTP $status)"
        add_result "Security" "readme.html" "⚠️ WARN" "HTTP $status"
    fi

    # Test 3: Security headers
    print_test "Security headers"
    local headers=$(curl -s -I "https://$DOMAIN" 2>/dev/null | grep -E "X-Frame-Options|X-Content-Type-Options|Strict-Transport-Security" || echo "None")
    if [ "$headers" != "None" ]; then
        print_pass "Security headers present"
        add_result "Security" "Security headers" "✅ PASS" "Headers found"
    else
        print_fail "Security headers missing"
        add_result "Security" "Security headers" "⚠️ WARN" "No security headers"
    fi
}

# Finalize report
finalize_report() {
    cat >> "$OUTPUT_FILE" <<EOF

---

## 📊 Test Summary

**Total Tests Executed:** $TOTAL_TESTS
**Tests Passed:** $PASSED_TESTS
**Tests Failed:** $FAILED_TESTS
**Tests Skipped:** $SKIPPED_TESTS

**Success Rate:** $(( PASSED_TESTS * 100 / TOTAL_TESTS ))%

**Overall Status:** $([ $FAILED_TESTS -eq 0 ] && echo "✅ PASS" || echo "❌ ISSUES FOUND")

---

## 📝 Notes

This is an automated test execution from a sandboxed environment.
Some tests requiring SSH access or manual browser interaction were skipped.

For complete testing, please:
1. Run SSH-based WP-CLI tests on the server
2. Perform manual frontend testing in a browser
3. Check server health metrics
4. Review log files for errors

---

**Test Report Generated:** $TEST_DATE
**Report File:** $OUTPUT_FILE
**Status:** Automated tests complete

EOF
}

################################################################################
# Main Execution
################################################################################

main() {
    print_header "PT24.PRO Checklist Execution"
    echo "Test Date: $TEST_DATE"
    echo "Domain: $DOMAIN"
    echo "Output: $OUTPUT_FILE"
    echo ""

    # Initialize report
    init_report

    # Run tests
    test_basic_availability
    test_api_endpoints
    test_landing_pages
    test_performance
    test_security

    # Finalize report
    finalize_report

    # Summary
    print_header "Test Execution Complete"
    echo "Total Tests:  $TOTAL_TESTS"
    echo -e "Passed:       ${GREEN}$PASSED_TESTS${NC}"
    echo -e "Failed:       ${RED}$FAILED_TESTS${NC}"
    echo -e "Skipped:      ${YELLOW}$SKIPPED_TESTS${NC}"
    echo ""

    local success_rate=0
    if [ $TOTAL_TESTS -gt 0 ]; then
        success_rate=$((PASSED_TESTS * 100 / TOTAL_TESTS))
    fi

    echo "Success Rate: ${success_rate}%"
    echo ""
    echo "📄 Full report saved to: $OUTPUT_FILE"
    echo ""

    if [ $FAILED_TESTS -eq 0 ]; then
        echo -e "${GREEN}✅ All automated tests passed!${NC}\n"
        exit 0
    else
        echo -e "${RED}❌ Some tests failed - review the report${NC}\n"
        exit 1
    fi
}

# Run main function
main

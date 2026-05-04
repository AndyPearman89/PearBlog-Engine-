#!/bin/bash
################################################################################
# Automated Testing Script for pt24.pro and poradnik.pro
#
# This script performs automated testing of both production deployments
#
# Usage:
#   ./test-pt24-poradnik.sh [--domain pt24|poradnik|both] [--verbose]
#
################################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PT24_DOMAIN="pt24.pro"
PORADNIK_DOMAIN="poradnik.pro"
PORADNIK_SSH_HOST="204.48.27.118"
PORADNIK_SSH_USER="root"
TEST_DOMAIN="both"
VERBOSE=false

# Test counters
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

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

print_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

print_info() {
    if [ "$VERBOSE" = true ]; then
        echo -e "${BLUE}[INFO]${NC} $1"
    fi
}

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --domain)
            TEST_DOMAIN="$2"
            shift 2
            ;;
        --verbose|-v)
            VERBOSE=true
            shift
            ;;
        --help|-h)
            echo "Usage: $0 [--domain pt24|poradnik|both] [--verbose]"
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

################################################################################
# Test Functions
################################################################################

test_http_status() {
    local url="$1"
    local expected="$2"
    local description="$3"

    print_test "$description"

    local status=$(curl -s -o /dev/null -w "%{http_code}" "$url" 2>/dev/null || echo "000")

    if [ "$status" = "$expected" ]; then
        print_pass "HTTP $status (expected $expected)"
        return 0
    else
        print_fail "HTTP $status (expected $expected)"
        return 1
    fi
}

test_api_endpoint() {
    local url="$1"
    local description="$2"

    print_test "$description"

    local response=$(curl -s "$url" 2>/dev/null)
    local status=$?

    if [ $status -eq 0 ] && [ -n "$response" ]; then
        print_pass "API response received"
        print_info "Response: $response"
        return 0
    else
        print_fail "API request failed"
        return 1
    fi
}

test_ssl_certificate() {
    local domain="$1"

    print_test "SSL certificate for $domain"

    local result=$(echo | openssl s_client -servername "$domain" -connect "$domain:443" 2>/dev/null | grep "Verify return code")

    if echo "$result" | grep -q "0 (ok)"; then
        print_pass "SSL certificate valid"
        return 0
    else
        print_fail "SSL certificate issue: $result"
        return 1
    fi
}

test_dns_resolution() {
    local domain="$1"

    print_test "DNS resolution for $domain"

    local ip=$(dig +short "$domain" | head -n1)

    if [ -n "$ip" ]; then
        print_pass "DNS resolves to $ip"
        return 0
    else
        print_fail "DNS resolution failed"
        return 1
    fi
}

test_response_time() {
    local url="$1"
    local max_time="$2"

    print_test "Response time for $url (max ${max_time}s)"

    local time=$(curl -o /dev/null -s -w "%{time_total}" "$url" 2>/dev/null || echo "999")

    if (( $(echo "$time < $max_time" | bc -l) )); then
        print_pass "Response time: ${time}s"
        return 0
    else
        print_fail "Response time: ${time}s (max: ${max_time}s)"
        return 1
    fi
}

################################################################################
# PT24.PRO Tests
################################################################################

test_pt24() {
    print_header "Testing pt24.pro"

    # DNS resolution
    test_dns_resolution "$PT24_DOMAIN"

    # SSL certificate
    test_ssl_certificate "$PT24_DOMAIN"

    # Homepage
    test_http_status "https://$PT24_DOMAIN" "200" "Homepage availability"

    # WWW redirect
    test_http_status "https://www.$PT24_DOMAIN" "200" "WWW domain"

    # Health endpoint
    test_api_endpoint "https://$PT24_DOMAIN/wp-json/pearblog/v1/health" "Health API"

    # PT24 API endpoints
    test_api_endpoint "https://$PT24_DOMAIN/wp-json/pt24/v1/businesses" "PT24 Businesses API"

    # Landing pages
    test_http_status "https://$PT24_DOMAIN/mechanik/warszawa/" "200" "Landing page: mechanik/warszawa"
    test_http_status "https://$PT24_DOMAIN/hydraulik/krakow/" "200" "Landing page: hydraulik/krakow"
    test_http_status "https://$PT24_DOMAIN/elektryk/wroclaw/" "200" "Landing page: elektryk/wroclaw"

    # 404 handling
    test_http_status "https://$PT24_DOMAIN/invalid/invalid/" "404" "404 error page"

    # Response time
    test_response_time "https://$PT24_DOMAIN" "2.0"

    # Security tests
    print_header "PT24.PRO Security Tests"
    test_http_status "https://$PT24_DOMAIN/wp-config.php" "403" "wp-config.php protected"
    test_http_status "https://$PT24_DOMAIN/readme.html" "404" "readme.html removed"

    print_header "PT24.PRO Tests Complete"
}

################################################################################
# PORADNIK.PRO Tests
################################################################################

test_poradnik() {
    print_header "Testing poradnik.pro"

    # DNS resolution
    test_dns_resolution "$PORADNIK_DOMAIN"

    # SSL certificate
    test_ssl_certificate "$PORADNIK_DOMAIN"

    # Homepage
    test_http_status "https://$PORADNIK_DOMAIN" "200" "Homepage availability"

    # WWW redirect
    test_http_status "https://www.$PORADNIK_DOMAIN" "200" "WWW domain"

    # Health endpoint
    test_api_endpoint "https://$PORADNIK_DOMAIN/wp-json/pearblog/v1/health" "Health API"

    # WordPress API
    test_api_endpoint "https://$PORADNIK_DOMAIN/wp-json/wp/v2/posts?per_page=1" "WordPress Posts API"

    # Response time
    test_response_time "https://$PORADNIK_DOMAIN" "2.0"

    # Security tests
    print_header "PORADNIK.PRO Security Tests"
    test_http_status "https://$PORADNIK_DOMAIN/wp-config.php" "403" "wp-config.php protected"
    test_http_status "https://$PORADNIK_DOMAIN/readme.html" "404" "readme.html removed"

    # SSH Tests (if available)
    if command -v ssh &> /dev/null; then
        print_header "PORADNIK.PRO SSH Tests"

        print_test "SSH connection test"
        if ssh -o BatchMode=yes -o ConnectTimeout=5 "$PORADNIK_SSH_USER@$PORADNIK_SSH_HOST" exit 2>/dev/null; then
            print_pass "SSH connection successful"

            # Test WP-CLI commands via SSH
            print_test "PearBlog Engine status via SSH"
            local stats=$(ssh "$PORADNIK_SSH_USER@$PORADNIK_SSH_HOST" "cd /var/www/poradnik.pro && wp pearblog stats --allow-root 2>/dev/null" || echo "")
            if [ -n "$stats" ]; then
                print_pass "PearBlog Engine responding"
                print_info "$stats"
            else
                print_fail "PearBlog Engine not responding"
            fi

            print_test "Published posts count via SSH"
            local post_count=$(ssh "$PORADNIK_SSH_USER@$PORADNIK_SSH_HOST" "cd /var/www/poradnik.pro && wp post list --post_type=post --format=count --allow-root 2>/dev/null" || echo "0")
            if [ "$post_count" -gt 0 ]; then
                print_pass "$post_count posts found"
            else
                print_fail "No posts found"
            fi

            print_test "Autonomous mode check via SSH"
            local auto_mode=$(ssh "$PORADNIK_SSH_USER@$PORADNIK_SSH_HOST" "cd /var/www/poradnik.pro && wp option get pearblog_autonomous_mode --allow-root 2>/dev/null" || echo "0")
            if [ "$auto_mode" = "1" ]; then
                print_pass "Autonomous mode enabled"
            else
                print_warning "Autonomous mode disabled"
            fi
        else
            print_warning "SSH connection not available (skipping SSH tests)"
        fi
    else
        print_warning "SSH not available (skipping SSH tests)"
    fi

    print_header "PORADNIK.PRO Tests Complete"
}

################################################################################
# Main Execution
################################################################################

main() {
    print_header "Automated Testing: pt24.pro and poradnik.pro"
    echo "Test Date: $(date)"
    echo "Test Mode: $TEST_DOMAIN"
    echo ""

    case $TEST_DOMAIN in
        pt24)
            test_pt24
            ;;
        poradnik)
            test_poradnik
            ;;
        both)
            test_pt24
            echo ""
            test_poradnik
            ;;
        *)
            echo "Invalid domain: $TEST_DOMAIN"
            exit 1
            ;;
    esac

    # Summary
    print_header "Test Summary"
    echo "Total Tests:  $TOTAL_TESTS"
    echo -e "Passed:       ${GREEN}$PASSED_TESTS${NC}"
    echo -e "Failed:       ${RED}$FAILED_TESTS${NC}"
    echo ""

    local success_rate=0
    if [ $TOTAL_TESTS -gt 0 ]; then
        success_rate=$((PASSED_TESTS * 100 / TOTAL_TESTS))
    fi

    echo "Success Rate: ${success_rate}%"

    if [ $FAILED_TESTS -eq 0 ]; then
        echo -e "\n${GREEN}✓ All tests passed!${NC}\n"
        exit 0
    else
        echo -e "\n${RED}✗ Some tests failed${NC}\n"
        exit 1
    fi
}

# Run main function
main

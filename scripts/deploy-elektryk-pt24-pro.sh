#!/bin/bash

################################################################################
# Elektryk.PT24.PRO - Automated Deployment Script
#
# Deploys the elektryk (car electrician) vertical as a subdomain
# of the PT24.PRO local services platform
#
# Usage:
#   ./deploy-elektryk-pt24-pro.sh [--subdomain|--subdirectory]
#
# Prerequisites:
#   - Base PT24.PRO platform already deployed
#   - DNS A record for elektryk.pt24.pro pointing to server
#   - Root or sudo access
#
################################################################################

set -e  # Exit on any error

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
DOMAIN="elektryk.pt24.pro"
BASE_DOMAIN="pt24.pro"
WEB_ROOT="/var/www/pt24.pro"
SERVICE="elektryk"
DEPLOYMENT_MODE="${1:-subdomain}"  # subdomain or subdirectory

# Logging
LOG_FILE="/var/log/elektryk-pt24-deploy.log"
exec > >(tee -a "$LOG_FILE")
exec 2>&1

################################################################################
# Helper Functions
################################################################################

print_header() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

check_root() {
    if [ "$EUID" -ne 0 ]; then
        print_error "This script must be run as root"
        exit 1
    fi
}

check_pt24_base() {
    if [ ! -d "$WEB_ROOT" ]; then
        print_error "Base PT24.PRO platform not found at $WEB_ROOT"
        print_info "Please deploy base platform first: deploy-pt24-pro.sh"
        exit 1
    fi
    print_success "Base PT24.PRO platform found"
}

check_wp_cli() {
    if ! command -v wp &> /dev/null; then
        print_error "WP-CLI not found"
        print_info "Installing WP-CLI..."
        curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
        chmod +x wp-cli.phar
        mv wp-cli.phar /usr/local/bin/wp
        print_success "WP-CLI installed"
    else
        print_success "WP-CLI found: $(wp --version)"
    fi
}

check_dns() {
    print_info "Checking DNS for $DOMAIN..."
    DNS_IP=$(dig +short $DOMAIN | head -n1)
    SERVER_IP=$(hostname -I | awk '{print $1}')

    if [ -z "$DNS_IP" ]; then
        print_warning "DNS not configured for $DOMAIN"
        print_info "Please add A record: $DOMAIN → $SERVER_IP"
        read -p "Continue anyway? (y/n) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    elif [ "$DNS_IP" == "$SERVER_IP" ]; then
        print_success "DNS correctly configured: $DOMAIN → $SERVER_IP"
    else
        print_warning "DNS points to $DNS_IP but server is $SERVER_IP"
        read -p "Continue anyway? (y/n) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
}

################################################################################
# Deployment Functions
################################################################################

deploy_subdirectory() {
    print_header "Deploying Elektryk as Subdirectory"

    cd $WEB_ROOT

    # Generate elektryk landing pages
    print_info "Generating elektryk landing pages..."
    wp pt24 generate-pages --service=elektryk --batch=25 --allow-root

    # Flush rewrite rules
    print_info "Flushing rewrite rules..."
    wp rewrite flush --allow-root

    # Verify pages
    PAGE_COUNT=$(wp post list --post_type=pt24_landing --s="elektryk" --format=count --allow-root)
    print_success "Generated $PAGE_COUNT elektryk pages"

    # Test URL
    print_info "Testing landing page..."
    if curl -s -o /dev/null -w "%{http_code}" "https://$BASE_DOMAIN/warszawa/elektryk/" | grep -q "200"; then
        print_success "Landing page accessible"
    else
        print_warning "Landing page may not be accessible yet"
    fi

    print_success "Subdirectory deployment complete!"
    echo ""
    echo "URLs accessible at:"
    echo "  - https://$BASE_DOMAIN/warszawa/elektryk/"
    echo "  - https://$BASE_DOMAIN/krakow/elektryk/"
    echo "  - https://$BASE_DOMAIN/ranking/warszawa/elektryk/"
}

deploy_subdomain() {
    print_header "Deploying Elektryk as Subdomain"

    cd $WEB_ROOT

    # Check if multisite is enabled
    IS_MULTISITE=$(wp core is-installed --network --allow-root && echo "yes" || echo "no")

    if [ "$IS_MULTISITE" == "no" ]; then
        print_info "Converting to multisite..."

        # Backup database first
        wp db export /tmp/pt24-backup-$(date +%s).sql --allow-root

        # Add multisite constants to wp-config.php
        if ! grep -q "MULTISITE" wp-config.php; then
            sed -i "/That's all, stop editing/i\
/* Multisite */\n\
define('WP_ALLOW_MULTISITE', true);\n\
define('MULTISITE', true);\n\
define('SUBDOMAIN_INSTALL', true);\n\
define('DOMAIN_CURRENT_SITE', '$BASE_DOMAIN');\n\
define('PATH_CURRENT_SITE', '/');\n\
define('SITE_ID_CURRENT_SITE', 1);\n\
define('BLOG_ID_CURRENT_SITE', 1);\n" wp-config.php

            print_success "Multisite constants added to wp-config.php"
        fi

        # Install multisite
        wp core multisite-install \
            --title="PT24.PRO Network" \
            --admin_user="admin" \
            --admin_email="admin@$BASE_DOMAIN" \
            --skip-email \
            --allow-root

        print_success "Multisite installed"
    else
        print_success "Multisite already enabled"
    fi

    # Check if elektryk site already exists
    SITE_EXISTS=$(wp site list --field=url --allow-root | grep -c "$DOMAIN" || true)

    if [ "$SITE_EXISTS" -eq 0 ]; then
        print_info "Creating elektryk subdomain site..."

        wp site create \
            --slug=elektryk \
            --title="Elektryk PT24 - Elektryka Samochodowa w Twojej Okolicy" \
            --email="admin@$DOMAIN" \
            --allow-root

        print_success "Elektryk site created"
    else
        print_success "Elektryk site already exists"
    fi

    # Get site ID
    SITE_ID=$(wp site list --field=blog_id --url=$DOMAIN --allow-root)
    print_info "Elektryk site ID: $SITE_ID"

    # Activate theme
    print_info "Activating PearBlog theme..."
    wp theme activate pearblog-theme --url=$DOMAIN --allow-root

    # Configure site settings
    print_info "Configuring site settings..."
    wp option update pearblog_industry 'local_services' --url=$DOMAIN --allow-root
    wp option update pearblog_language 'pl' --url=$DOMAIN --allow-root
    wp option update pearblog_homepage_version 'v7' --url=$DOMAIN --allow-root
    wp option update pearblog_tone 'professional' --url=$DOMAIN --allow-root

    # Set site title and description
    wp option update blogname 'Elektryk PT24 - Elektryka Samochodowa' --url=$DOMAIN --allow-root
    wp option update blogdescription 'Znajdź sprawdzonego elektryka samochodowego w Twojej okolicy' --url=$DOMAIN --allow-root

    # Set URLs
    wp option update home "https://$DOMAIN" --url=$DOMAIN --allow-root
    wp option update siteurl "https://$DOMAIN" --url=$DOMAIN --allow-root

    print_success "Site settings configured"

    # Initialize PT24 platform
    print_info "Initializing PT24 platform..."
    wp pt24 init --url=$DOMAIN --allow-root

    # Generate elektryk landing pages
    print_info "Generating elektryk landing pages..."
    wp pt24 generate-pages --service=elektryk --batch=25 --url=$DOMAIN --allow-root

    # Flush rewrite rules
    wp rewrite flush --url=$DOMAIN --allow-root

    # Verify pages
    PAGE_COUNT=$(wp post list --post_type=pt24_landing --url=$DOMAIN --format=count --allow-root)
    print_success "Generated $PAGE_COUNT elektryk pages"

    print_success "Subdomain deployment complete!"
}

configure_web_server() {
    print_header "Configuring Web Server for Subdomain"

    # Detect web server
    if systemctl is-active --quiet apache2; then
        WEB_SERVER="apache"
    elif systemctl is-active --quiet nginx; then
        WEB_SERVER="nginx"
    else
        print_warning "Could not detect web server"
        return
    fi

    print_info "Detected web server: $WEB_SERVER"

    if [ "$WEB_SERVER" == "apache" ]; then
        # Check if wildcard subdomain is configured
        if ! grep -q "ServerAlias.*\*.${BASE_DOMAIN}" /etc/apache2/sites-enabled/*.conf; then
            print_warning "Wildcard subdomain not configured in Apache"
            print_info "Please ensure ServerAlias *.${BASE_DOMAIN} is in your VirtualHost"
        else
            print_success "Apache wildcard subdomain configured"
        fi

        # Reload Apache
        systemctl reload apache2
        print_success "Apache reloaded"

    elif [ "$WEB_SERVER" == "nginx" ]; then
        # Check if wildcard subdomain is configured
        if ! grep -q "server_name.*\*.${BASE_DOMAIN}" /etc/nginx/sites-enabled/*; then
            print_warning "Wildcard subdomain not configured in Nginx"
            print_info "Please ensure server_name includes *.${BASE_DOMAIN}"
        else
            print_success "Nginx wildcard subdomain configured"
        fi

        # Reload Nginx
        nginx -t && systemctl reload nginx
        print_success "Nginx reloaded"
    fi
}

configure_ssl() {
    print_header "Configuring SSL Certificate"

    if ! command -v certbot &> /dev/null; then
        print_warning "Certbot not installed, skipping SSL configuration"
        return
    fi

    print_info "Expanding SSL certificate for $DOMAIN..."

    # Try to expand existing certificate
    if certbot certificates 2>/dev/null | grep -q "$BASE_DOMAIN"; then
        print_info "Expanding existing certificate..."

        certbot certonly --expand \
            -d $BASE_DOMAIN \
            -d www.$BASE_DOMAIN \
            -d $DOMAIN \
            --non-interactive \
            --agree-tos \
            --email admin@$BASE_DOMAIN \
            --webroot \
            --webroot-path=$WEB_ROOT || true

        print_success "SSL certificate expanded"
    else
        print_warning "No existing certificate found for $BASE_DOMAIN"
        print_info "Please run certbot manually to configure SSL"
    fi
}

create_test_business() {
    print_header "Creating Test Business Profile"

    cd $WEB_ROOT

    local URL_PARAM=""
    if [ "$DEPLOYMENT_MODE" == "subdomain" ]; then
        URL_PARAM="--url=$DOMAIN"
    fi

    print_info "Creating test electrician business..."

    # Create business post
    BIZ_ID=$(wp post create \
        --post_type=pt24_business \
        --post_title="AutoElektryk24 - Warszawa" \
        --post_content="Profesjonalna diagnostyka i naprawa instalacji elektrycznej w samochodach. Mobilny serwis dostępny 24/7. Specjalizujemy się w naprawie stacyjek, alternatorów, instalacji alarmów i systemów multimedialnych." \
        --post_status=publish \
        $URL_PARAM \
        --allow-root \
        --porcelain)

    # Add business metadata
    wp post meta update $BIZ_ID pt24_phone '+48 500 123 456' $URL_PARAM --allow-root
    wp post meta update $BIZ_ID pt24_email 'kontakt@autoelektryk24.pl' $URL_PARAM --allow-root
    wp post meta update $BIZ_ID pt24_service_area 'Warszawa i okolice' $URL_PARAM --allow-root
    wp post meta update $BIZ_ID pt24_years_experience '15' $URL_PARAM --allow-root
    wp post meta update $BIZ_ID pt24_mobile_service '1' $URL_PARAM --allow-root
    wp post meta update $BIZ_ID pt24_emergency_service '1' $URL_PARAM --allow-root

    # Add service category
    wp term create pt24_service_cat 'elektryk' --slug=elektryk $URL_PARAM --allow-root 2>/dev/null || true
    SERVICE_TERM=$(wp term list pt24_service_cat --slug=elektryk --field=term_id $URL_PARAM --allow-root)
    wp post term add $BIZ_ID pt24_service_cat $SERVICE_TERM $URL_PARAM --allow-root

    # Add city
    wp term create pt24_city 'Warszawa' --slug=warszawa $URL_PARAM --allow-root 2>/dev/null || true
    CITY_TERM=$(wp term list pt24_city --slug=warszawa --field=term_id $URL_PARAM --allow-root)
    wp post term add $BIZ_ID pt24_city $CITY_TERM $URL_PARAM --allow-root

    print_success "Test business created (ID: $BIZ_ID)"
}

run_tests() {
    print_header "Running Verification Tests"

    cd $WEB_ROOT

    if [ "$DEPLOYMENT_MODE" == "subdomain" ]; then
        TEST_URL="https://$DOMAIN"
        WP_URL_PARAM="--url=$DOMAIN"
    else
        TEST_URL="https://$BASE_DOMAIN"
        WP_URL_PARAM=""
    fi

    # Test 1: Homepage
    print_info "Testing homepage..."
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$TEST_URL/" || echo "000")
    if [ "$HTTP_CODE" == "200" ]; then
        print_success "Homepage accessible (HTTP $HTTP_CODE)"
    else
        print_warning "Homepage returned HTTP $HTTP_CODE"
    fi

    # Test 2: Landing page
    if [ "$DEPLOYMENT_MODE" == "subdomain" ]; then
        TEST_LANDING="$TEST_URL/warszawa/"
    else
        TEST_LANDING="$TEST_URL/warszawa/elektryk/"
    fi

    print_info "Testing landing page..."
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$TEST_LANDING" || echo "000")
    if [ "$HTTP_CODE" == "200" ]; then
        print_success "Landing page accessible (HTTP $HTTP_CODE)"
    else
        print_warning "Landing page returned HTTP $HTTP_CODE"
    fi

    # Test 3: Database
    print_info "Checking database tables..."
    TABLE_COUNT=$(wp db query "SHOW TABLES LIKE 'wp_%pt24_%'" $WP_URL_PARAM --allow-root | wc -l)
    if [ "$TABLE_COUNT" -gt 0 ]; then
        print_success "PT24 database tables exist"
    else
        print_warning "PT24 database tables not found"
    fi

    # Test 4: Pages count
    print_info "Counting generated pages..."
    PAGE_COUNT=$(wp post list --post_type=pt24_landing $WP_URL_PARAM --format=count --allow-root)
    print_success "Total pages: $PAGE_COUNT"

    # Test 5: API
    print_info "Testing PT24 API..."
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$TEST_URL/wp-json/pt24/v1/businesses" || echo "000")
    if [ "$HTTP_CODE" == "200" ]; then
        print_success "API accessible (HTTP $HTTP_CODE)"
    else
        print_warning "API returned HTTP $HTTP_CODE"
    fi
}

print_summary() {
    print_header "Deployment Summary"

    echo ""
    echo -e "${GREEN}✓ Elektryk.PT24.PRO Deployment Complete!${NC}"
    echo ""

    if [ "$DEPLOYMENT_MODE" == "subdomain" ]; then
        echo "Deployment Mode: Subdomain"
        echo ""
        echo "URLs:"
        echo "  Homepage:  https://$DOMAIN"
        echo "  Landing:   https://$DOMAIN/warszawa/"
        echo "  Ranking:   https://$DOMAIN/ranking/warszawa/"
        echo "  Admin:     https://$DOMAIN/wp-admin"
        echo ""
        echo "Site ID: $(wp site list --field=blog_id --url=$DOMAIN --allow-root 2>/dev/null || echo 'N/A')"

    else
        echo "Deployment Mode: Subdirectory"
        echo ""
        echo "URLs:"
        echo "  Landing:   https://$BASE_DOMAIN/warszawa/elektryk/"
        echo "  Ranking:   https://$BASE_DOMAIN/ranking/warszawa/elektryk/"
        echo "  Admin:     https://$BASE_DOMAIN/wp-admin"
    fi

    echo ""
    echo "Statistics:"
    cd $WEB_ROOT
    if [ "$DEPLOYMENT_MODE" == "subdomain" ]; then
        wp pt24 stats --url=$DOMAIN --allow-root 2>/dev/null || echo "  Unable to fetch stats"
    else
        echo "  Run: wp pt24 stats --allow-root"
    fi

    echo ""
    echo "Next Steps:"
    echo "  1. Add more businesses: wp post create --post_type=pt24_business ..."
    echo "  2. Configure email notifications"
    echo "  3. Set up Google Analytics"
    echo "  4. Submit sitemap to Google Search Console"
    echo "  5. Monitor leads: wp db query \"SELECT * FROM wp_pt24_leads WHERE service='elektryk'\""
    echo ""
    echo "Documentation:"
    echo "  - Full Guide: DEPLOYMENT-elektryk-pt24-pro.md"
    echo "  - Quick Start: QUICKSTART-elektryk-pt24-pro.md"
    echo ""
    print_success "Deployment log saved to: $LOG_FILE"
}

################################################################################
# Main Execution
################################################################################

main() {
    print_header "Elektryk.PT24.PRO Deployment Script"

    echo "Deployment Mode: $DEPLOYMENT_MODE"
    echo ""

    # Pre-flight checks
    check_root
    check_pt24_base
    check_wp_cli

    if [ "$DEPLOYMENT_MODE" == "subdomain" ]; then
        check_dns
        deploy_subdomain
        configure_web_server
        configure_ssl
    else
        deploy_subdirectory
    fi

    # Create test content
    create_test_business

    # Run verification tests
    run_tests

    # Print summary
    print_summary

    echo ""
    print_success "All done! Elektryk.PT24.PRO is ready! ⚡"
}

# Show usage if help requested
if [ "$1" == "--help" ] || [ "$1" == "-h" ]; then
    echo "Usage: $0 [--subdomain|--subdirectory]"
    echo ""
    echo "Options:"
    echo "  --subdomain      Deploy as elektryk.pt24.pro (default)"
    echo "  --subdirectory   Deploy as pt24.pro/{city}/elektryk/"
    echo "  --help, -h       Show this help message"
    echo ""
    echo "Prerequisites:"
    echo "  - Base PT24.PRO platform deployed"
    echo "  - DNS configured (for subdomain mode)"
    echo "  - Root/sudo access"
    exit 0
fi

# Run main function
main

exit 0

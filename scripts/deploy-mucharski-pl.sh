#!/bin/bash
#
# PearBlog Engine - Automated Deployment Script for mucharski.pl
# Domain: mucharski.pl (or zalew.mucharski.pl)
# Server: TBD - Update with actual server IP
# Version: 6.0.0
#
# Usage:
#   curl -sL https://raw.githubusercontent.com/AndyPearman89/PearBlog-Engine-/main/scripts/deploy-mucharski-pl.sh | bash
#   or
#   ./deploy-mucharski-pl.sh
#

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DOMAIN="mucharski.pl"
SERVER_IP="TBD"  # TODO: Update with actual server IP
WP_PATH="/var/www/mucharski.pl"
DB_NAME="mucharski_pl"
DB_USER="mucharski_user"
REPO_URL="https://github.com/AndyPearman89/PearBlog-Engine-.git"

# Helper functions
print_step() {
    echo -e "${BLUE}==>${NC} ${1}"
}

print_success() {
    echo -e "${GREEN}✓${NC} ${1}"
}

print_error() {
    echo -e "${RED}✗${NC} ${1}"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} ${1}"
}

check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_error "This script must be run as root"
        exit 1
    fi
}

check_command() {
    if ! command -v "$1" &> /dev/null; then
        print_error "$1 is not installed"
        return 1
    fi
    return 0
}

# Main deployment steps

step_1_check_prerequisites() {
    print_step "Step 1: Checking prerequisites..."

    local failed=0

    # Check if running as root
    if [[ $EUID -ne 0 ]]; then
        print_error "Must run as root"
        failed=1
    else
        print_success "Running as root"
    fi

    # Check PHP version
    if check_command php; then
        PHP_VERSION=$(php -r "echo PHP_VERSION;")
        if [[ "$(printf '%s\n' "8.1" "$PHP_VERSION" | sort -V | head -n1)" == "8.1" ]]; then
            print_success "PHP $PHP_VERSION installed (≥8.1 required)"
        else
            print_error "PHP $PHP_VERSION is too old (need ≥8.1)"
            failed=1
        fi
    else
        print_warning "PHP not installed"
        failed=1
    fi

    # Check MySQL/MariaDB
    if check_command mysql; then
        print_success "MySQL/MariaDB installed"
    else
        print_warning "MySQL/MariaDB not installed"
        failed=1
    fi

    # Check web server
    if check_command apache2 || check_command nginx; then
        print_success "Web server installed"
    else
        print_warning "No web server (Apache/Nginx) found"
        failed=1
    fi

    if [[ $failed -eq 1 ]]; then
        print_error "Prerequisites check failed. Please install missing components."
        exit 1
    fi

    print_success "All prerequisites met"
}

step_2_install_php_extensions() {
    print_step "Step 2: Installing required PHP extensions..."

    apt update -qq

    EXTENSIONS=(
        "php8.1-cli"
        "php8.1-fpm"
        "php8.1-mysql"
        "php8.1-curl"
        "php8.1-json"
        "php8.1-mbstring"
        "php8.1-xml"
        "php8.1-zip"
        "php8.1-gd"
        "php8.1-intl"
        "php8.1-openssl"
    )

    for ext in "${EXTENSIONS[@]}"; do
        if dpkg -l | grep -q "^ii.*$ext"; then
            print_success "$ext already installed"
        else
            print_step "Installing $ext..."
            apt install -y "$ext" &> /dev/null
            print_success "$ext installed"
        fi
    done

    print_success "PHP extensions installed"
}

step_3_configure_php() {
    print_step "Step 3: Configuring PHP..."

    PHP_INI="/etc/php/8.1/fpm/php.ini"

    if [[ -f "$PHP_INI" ]]; then
        sed -i 's/^memory_limit = .*/memory_limit = 512M/' "$PHP_INI"
        sed -i 's/^max_execution_time = .*/max_execution_time = 300/' "$PHP_INI"
        sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 64M/' "$PHP_INI"
        sed -i 's/^post_max_size = .*/post_max_size = 64M/' "$PHP_INI"

        systemctl restart php8.1-fpm
        print_success "PHP configured and restarted"
    else
        print_warning "PHP INI file not found at $PHP_INI"
    fi
}

step_4_install_wp_cli() {
    print_step "Step 4: Installing WP-CLI..."

    if check_command wp; then
        print_success "WP-CLI already installed"
    else
        curl -sO https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
        chmod +x wp-cli.phar
        mv wp-cli.phar /usr/local/bin/wp
        print_success "WP-CLI installed"
    fi

    wp --info --allow-root | head -n1
}

step_5_setup_database() {
    print_step "Step 5: Setting up database..."

    read -sp "Enter MySQL root password: " MYSQL_ROOT_PASS
    echo

    # Generate random password for WordPress DB user
    DB_PASS=$(openssl rand -base64 32)

    # Create database and user
    mysql -u root -p"$MYSQL_ROOT_PASS" <<EOF
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF

    print_success "Database created: $DB_NAME"
    print_success "Database user: $DB_USER"

    # Save password to secure file
    echo "$DB_PASS" > /root/.mucharski_db_pass
    chmod 600 /root/.mucharski_db_pass
    print_success "Database password saved to /root/.mucharski_db_pass"
}

step_6_download_wordpress() {
    print_step "Step 6: Downloading WordPress..."

    if [[ -d "$WP_PATH" ]]; then
        print_warning "Directory $WP_PATH already exists"
        read -p "Remove and reinstall? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            rm -rf "$WP_PATH"
        else
            print_error "Aborted"
            exit 1
        fi
    fi

    mkdir -p "$WP_PATH"
    cd "$WP_PATH"

    wp core download --allow-root
    print_success "WordPress downloaded"
}

step_7_configure_wordpress() {
    print_step "Step 7: Configuring WordPress..."

    cd "$WP_PATH"

    DB_PASS=$(cat /root/.mucharski_db_pass)

    # Create wp-config.php
    wp config create \
        --dbname="$DB_NAME" \
        --dbuser="$DB_USER" \
        --dbpass="$DB_PASS" \
        --dbhost="localhost" \
        --dbcharset="utf8mb4" \
        --dbcollate="utf8mb4_unicode_ci" \
        --allow-root

    # Add PearBlog requirements to wp-config.php
    wp config set WP_MEMORY_LIMIT "512M" --allow-root
    wp config set DISABLE_WP_CRON false --raw --allow-root
    wp config set WP_CLI_ALLOW_ROOT true --raw --allow-root
    wp config set WP_DEBUG false --raw --allow-root

    # Change table prefix
    wp config set table_prefix "mch_" --allow-root

    print_success "WordPress configured"
}

step_8_install_wordpress() {
    print_step "Step 8: Installing WordPress..."

    cd "$WP_PATH"

    read -p "Enter admin email: " ADMIN_EMAIL
    read -sp "Enter admin password: " ADMIN_PASS
    echo

    wp core install \
        --url="http://${DOMAIN}" \
        --title="Mucharski.pl - Zalew, Wędkarstwo, Sporty Wodne" \
        --admin_user="admin" \
        --admin_password="$ADMIN_PASS" \
        --admin_email="$ADMIN_EMAIL" \
        --allow-root

    # Set permalink structure
    wp rewrite structure '/%postname%/' --allow-root
    wp rewrite flush --allow-root

    print_success "WordPress installed"
}

step_9_deploy_pearblog() {
    print_step "Step 9: Deploying PearBlog Engine..."

    # Clone repository
    cd /tmp
    if [[ -d "PearBlog-Engine-" ]]; then
        rm -rf PearBlog-Engine-
    fi

    git clone "$REPO_URL"
    cd PearBlog-Engine-

    # Deploy MU-plugin
    mkdir -p "$WP_PATH/wp-content/mu-plugins"
    cp -r mu-plugins/pearblog-engine "$WP_PATH/wp-content/mu-plugins/"
    print_success "MU-plugin deployed"

    # Deploy theme
    cp -r theme/pearblog-theme "$WP_PATH/wp-content/themes/"
    print_success "Theme deployed"

    # Activate theme
    cd "$WP_PATH"
    wp theme activate pearblog-theme --allow-root
    print_success "Theme activated"

    # Set ownership
    chown -R www-data:www-data "$WP_PATH/wp-content/mu-plugins/pearblog-engine"
    chown -R www-data:www-data "$WP_PATH/wp-content/themes/pearblog-theme"

    # Cleanup
    rm -rf /tmp/PearBlog-Engine-

    print_success "PearBlog Engine deployed"
}

step_10_configure_pearblog() {
    print_step "Step 10: Configuring PearBlog Engine..."

    cd "$WP_PATH"

    read -p "Enter OpenAI API key (sk-proj-...): " OPENAI_KEY

    wp option update pearblog_openai_api_key "$OPENAI_KEY" --allow-root
    wp option update pearblog_industry "sporty wodne i wędkarstwo" --allow-root
    wp option update pearblog_tone "praktyczny, pomocny, dla pasjonatów wędkarstwa i sportów wodnych" --allow-root
    wp option update pearblog_publish_rate "0.5" --allow-root
    wp option update pearblog_language "pl" --allow-root
    wp option update pearblog_ai_images_enabled "1" --allow-root

    print_success "PearBlog Engine configured"
}

step_11_add_initial_topics() {
    print_step "Step 11: Adding initial topics..."

    cd "$WP_PATH"

    TOPICS=(
        "Najlepsze miejsca na wędkarstwo w Polsce"
        "Jak złowić karpia - poradnik dla początkujących"
        "10 najważniejszych sprzętów wędkarskich"
        "Sporty wodne na polskich jeziorach"
        "Wakeboarding dla początkujących - od czego zacząć"
        "Jak przygotować przynętę na karpia"
        "Najlepsze kajaki turystyczne 2026"
        "Wędkarstwo muchowe - kompletny przewodnik"
        "Bezpieczeństwo na wodzie - co musisz wiedzieć"
        "Stand up paddle (SUP) - poradnik dla początkujących"
        "Jak złowić szczupaka w Polsce"
        "Najpiękniejsze jeziora do windsurfingu"
        "Przynęty na szczupaka - co działa najlepiej"
        "Kitesurfing w Polsce - najlepsze miejsca"
        "Jak wybrać wędkę dla początkujących"
        "Nurkowanie w polskich jeziorach"
        "Łowienie sandacza - techniki i porady"
        "Kajaking górski - szlaki w Polsce"
        "Wędzarstwo zimowe - poradnik przetrwania"
        "Żeglarstwo na polskich jeziorach"
        "Sprzęt do wędkarstwa spinningowego"
        "Wakeboarding vs waterskiing - co wybrać"
        "Najlepsze łowiska pstrąga w górach"
        "Kitesurfing dla początkujących - kurs podstawowy"
        "Wędkarstwo nocne - jak się przygotować"
        "Sprzęt ratunkowy na wodzie - co musisz mieć"
        "Rybostan polskich jezior - co gdzie złowisz"
        "Windsurfing zimą - czy to możliwe"
        "Wędkarstwo karpiowe - zaawansowane techniki"
        "Parasailing w Polsce - gdzie spróbować"
    )

    for topic in "${TOPICS[@]}"; do
        wp pearblog queue add "$topic" --allow-root
    done

    print_success "Added ${#TOPICS[@]} topics to queue"
}

step_12_setup_ssl() {
    print_step "Step 12: Setting up SSL..."

    if ! check_command certbot; then
        print_step "Installing Certbot..."
        apt install -y certbot python3-certbot-apache python3-certbot-nginx &> /dev/null
    fi

    read -p "Enter email for SSL certificate: " SSL_EMAIL

    if check_command apache2; then
        certbot --apache -d "$DOMAIN" -d "www.$DOMAIN" --email "$SSL_EMAIL" --agree-tos --non-interactive --redirect
    elif check_command nginx; then
        certbot --nginx -d "$DOMAIN" -d "www.$DOMAIN" --email "$SSL_EMAIL" --agree-tos --non-interactive --redirect
    else
        print_error "No web server found for SSL setup"
        return 1
    fi

    # Update WordPress URLs to HTTPS
    cd "$WP_PATH"
    wp option update home "https://${DOMAIN}" --allow-root
    wp option update siteurl "https://${DOMAIN}" --allow-root

    print_success "SSL certificate installed"
}

step_13_test_deployment() {
    print_step "Step 13: Testing deployment..."

    cd "$WP_PATH"

    # Test pipeline
    print_step "Generating first article..."
    wp pearblog generate --allow-root

    # Check statistics
    wp pearblog stats --allow-root

    # Test health endpoint
    print_step "Testing health endpoint..."
    HEALTH_RESPONSE=$(curl -s "https://${DOMAIN}/wp-json/pearblog/v1/health" || echo '{"status":"error"}')
    echo "$HEALTH_RESPONSE"

    print_success "Deployment tested"
}

step_14_enable_autonomous_mode() {
    print_step "Step 14: Enabling autonomous mode..."

    cd "$WP_PATH"

    wp option update pearblog_autonomous_mode "1" --allow-root

    print_step "Starting Autopilot..."
    wp pearblog autopilot start --allow-root

    print_success "Autonomous mode enabled"
}

# Main execution
main() {
    echo ""
    echo "╔═══════════════════════════════════════════════════════════╗"
    echo "║                                                           ║"
    echo "║        PearBlog Engine Deployment for mucharski.pl       ║"
    echo "║                     Version 6.0.0                         ║"
    echo "║           Zalew, Wędkarstwo, Sporty Wodne                ║"
    echo "║                                                           ║"
    echo "╚═══════════════════════════════════════════════════════════╝"
    echo ""

    check_root

    print_step "Domain: $DOMAIN"
    print_step "Server: $SERVER_IP"
    print_step "Path: $WP_PATH"
    echo ""

    if [[ "$SERVER_IP" == "TBD" ]]; then
        print_error "ERROR: Server IP not configured!"
        print_error "Please edit this script and update SERVER_IP variable."
        exit 1
    fi

    read -p "Continue with deployment? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_error "Deployment cancelled"
        exit 1
    fi

    step_1_check_prerequisites
    step_2_install_php_extensions
    step_3_configure_php
    step_4_install_wp_cli
    step_5_setup_database
    step_6_download_wordpress
    step_7_configure_wordpress
    step_8_install_wordpress
    step_9_deploy_pearblog
    step_10_configure_pearblog
    step_11_add_initial_topics
    step_12_setup_ssl
    step_13_test_deployment
    step_14_enable_autonomous_mode

    echo ""
    echo "╔═══════════════════════════════════════════════════════════╗"
    echo "║                                                           ║"
    echo "║              🚀 DEPLOYMENT SUCCESSFUL! 🚀                 ║"
    echo "║                                                           ║"
    echo "╚═══════════════════════════════════════════════════════════╝"
    echo ""
    print_success "Site URL: https://${DOMAIN}"
    print_success "Admin URL: https://${DOMAIN}/wp-admin"
    print_success "Health URL: https://${DOMAIN}/wp-json/pearblog/v1/health"
    echo ""
    print_step "Next steps:"
    echo "  1. Visit your site: https://${DOMAIN}"
    echo "  2. Log in to admin: https://${DOMAIN}/wp-admin"
    echo "  3. Check PearBlog Engine → Queue"
    echo "  4. Monitor logs: tail -f ${WP_PATH}/wp-content/pearblog-engine.log"
    echo "  5. Check autopilot: wp pearblog autopilot status --allow-root"
    echo ""
}

# Run main function
main "$@"

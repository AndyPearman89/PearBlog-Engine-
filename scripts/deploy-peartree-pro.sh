#!/bin/bash
#
# PearBlog Engine - Automated Deployment Script for peartree.pro
# Domain: peartree.pro (WordPress Multisite - Subdomain Network)
# Server: TBD - Update with actual server IP
# Version: 6.0.0
#
# Usage:
#   curl -sL https://raw.githubusercontent.com/AndyPearman89/PearBlog-Engine-/main/scripts/deploy-peartree-pro.sh | bash
#   or
#   ./deploy-peartree-pro.sh
#

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DOMAIN="peartree.pro"
SERVER_IP="TBD"  # TODO: Update with actual server IP
WP_PATH="/var/www/peartree.pro"
DB_NAME="peartree_pro"
DB_USER="peartree_user"
DB_PASS_FILE="/root/.peartree_db_pass"
TABLE_PREFIX="pt_"
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
    echo "$DB_PASS" > "$DB_PASS_FILE"
    chmod 600 "$DB_PASS_FILE"
    print_success "Database password saved to $DB_PASS_FILE"
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
    print_step "Step 7: Configuring WordPress (with Multisite support)..."

    cd "$WP_PATH"

    DB_PASS=$(cat "$DB_PASS_FILE")

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
    wp config set table_prefix "$TABLE_PREFIX" --allow-root

    # Allow Multisite network setup
    wp config set WP_ALLOW_MULTISITE true --raw --allow-root

    print_success "WordPress configured (WP_ALLOW_MULTISITE enabled)"
}

step_8_install_wordpress() {
    print_step "Step 8: Installing WordPress and converting to Multisite..."

    cd "$WP_PATH"

    read -p "Enter admin email: " ADMIN_EMAIL
    read -sp "Enter admin password: " ADMIN_PASS
    echo

    # Step 8a: Install as single site first
    wp core install \
        --url="http://${DOMAIN}" \
        --title="PearTree Pro - Multi-Site Content Network" \
        --admin_user="admin" \
        --admin_password="$ADMIN_PASS" \
        --admin_email="$ADMIN_EMAIL" \
        --allow-root

    # Set permalink structure
    wp rewrite structure '/%postname%/' --allow-root
    wp rewrite flush --allow-root

    print_success "WordPress single-site installed"

    # Step 8b: Convert to Multisite (subdomain network)
    wp core multisite-convert --subdomains --allow-root

    print_success "WordPress converted to Multisite (subdomain mode)"

    # Add all required Multisite constants to wp-config.php
    wp config set MULTISITE true --raw --allow-root
    wp config set SUBDOMAIN_INSTALL true --raw --allow-root
    wp config set DOMAIN_CURRENT_SITE "$DOMAIN" --allow-root
    wp config set PATH_CURRENT_SITE "/" --allow-root
    wp config set SITE_ID_CURRENT_SITE 1 --raw --allow-root
    wp config set BLOG_ID_CURRENT_SITE 1 --raw --allow-root

    print_success "Multisite constants written to wp-config.php"
}

step_8b_configure_webserver_multisite() {
    print_step "Step 8b: Configuring web server for Multisite (wildcard subdomains)..."

    print_warning "IMPORTANT: Wildcard DNS must be configured!"
    print_warning "  Add A record: *.${DOMAIN} → ${SERVER_IP}"
    print_warning "  Add A record: ${DOMAIN} → ${SERVER_IP}"
    echo ""

    if check_command apache2; then
        print_step "Apache detected – writing Multisite .htaccess..."

        cat > "$WP_PATH/.htaccess" <<'HTACCESS'
# WordPress Multisite .htaccess
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]

# Uploaded files
RewriteRule ^([_0-9a-zA-Z-]+/)?files/(.+) wp-includes/ms-files.php?file=$2 [L]

# Add trailing slash to /wp-admin
RewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin$ $1wp-admin/ [R=301,L]

RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) $2 [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?(.*\.php)$ $2 [L]
RewriteRule . index.php [L]
HTACCESS

        print_success ".htaccess written for WordPress Multisite (Apache)"
        print_step "Ensure your Apache VirtualHost has: ServerAlias *.${DOMAIN}"
        print_step "Restart Apache: systemctl restart apache2"

    elif check_command nginx; then
        print_step "Nginx detected – Multisite wildcard subdomain configuration required."
        echo ""
        print_warning "Add the following server block to your Nginx configuration:"
        echo ""
        cat <<NGINX_CONF
# Nginx Multisite Wildcard Subdomain Config for ${DOMAIN}
server {
    listen 80;
    server_name ${DOMAIN} *.${DOMAIN};
    root ${WP_PATH};
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$args;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
    }

    location ~* /files/(.+)\$ {
        try_files /wp-content/blogs.dir/\$blogid/\$uri /wp-includes/ms-files.php?file=\$1 last;
        access_log off;
        log_not_found off;
        expires max;
    }

    location = /favicon.ico { log_not_found off; access_log off; }
    location = /robots.txt  { log_not_found off; access_log off; allow all; }
    location ~* \.(css|gif|ico|jpeg|jpg|js|png)\$ { expires max; log_not_found off; }
}
NGINX_CONF
        echo ""
        print_step "Save this to: /etc/nginx/sites-available/${DOMAIN}"
        print_step "Then run: ln -s /etc/nginx/sites-available/${DOMAIN} /etc/nginx/sites-enabled/"
        print_step "Reload Nginx: systemctl reload nginx"
    else
        print_warning "No web server detected. Configure wildcard subdomain rewriting manually."
    fi

    print_success "Web server Multisite configuration complete"
}

step_9_deploy_pearblog() {
    print_step "Step 9: Deploying PearBlog Engine (network-wide)..."

    # Clone repository
    cd /tmp
    if [[ -d "PearBlog-Engine-" ]]; then
        rm -rf PearBlog-Engine-
    fi

    git clone "$REPO_URL"
    cd PearBlog-Engine-

    # Deploy MU-plugin to multisite network
    mkdir -p "$WP_PATH/wp-content/mu-plugins"
    cp -r mu-plugins/pearblog-engine "$WP_PATH/wp-content/mu-plugins/"
    print_success "MU-plugin deployed"

    # Deploy theme
    cp -r theme/pearblog-theme "$WP_PATH/wp-content/themes/"
    print_success "Theme deployed"

    # Activate theme on main site
    cd "$WP_PATH"
    wp theme activate pearblog-theme --allow-root
    print_success "Theme activated on main site"

    # Network-activate PearBlog Engine plugin
    wp plugin activate pearblog-engine --network --allow-root
    print_success "PearBlog Engine network-activated"

    # Set ownership
    chown -R www-data:www-data "$WP_PATH/wp-content/mu-plugins/pearblog-engine"
    chown -R www-data:www-data "$WP_PATH/wp-content/themes/pearblog-theme"

    # Cleanup
    rm -rf /tmp/PearBlog-Engine-

    print_success "PearBlog Engine deployed and network-activated"
}

step_10_configure_pearblog() {
    print_step "Step 10: Configuring PearBlog Engine (network level)..."

    cd "$WP_PATH"

    read -p "Enter OpenAI API key (sk-proj-...): " OPENAI_KEY

    wp option update pearblog_openai_api_key "$OPENAI_KEY" --allow-root
    wp option update pearblog_industry "multi-niche content network" --allow-root
    wp option update pearblog_tone "authoritative, informative, engaging for a broad English-speaking audience" --allow-root
    wp option update pearblog_publish_rate "0.5" --allow-root
    wp option update pearblog_language "en" --allow-root
    wp option update pearblog_ai_images_enabled "1" --allow-root

    print_success "PearBlog Engine configured on network level"
}

step_10b_create_initial_subsites() {
    print_step "Step 10b: Creating initial subsites..."

    cd "$WP_PATH"

    # Read admin email from WordPress options (set during install)
    ADMIN_EMAIL=$(wp option get admin_email --allow-root)

    wp site create --slug="blog" --title="PearTree Blog" --email="$ADMIN_EMAIL" --allow-root
    print_success "Subsite created: blog.${DOMAIN}"

    wp site create --slug="news" --title="PearTree News" --email="$ADMIN_EMAIL" --allow-root
    print_success "Subsite created: news.${DOMAIN}"

    wp site create --slug="reviews" --title="PearTree Reviews" --email="$ADMIN_EMAIL" --allow-root
    print_success "Subsite created: reviews.${DOMAIN}"

    print_success "Initial subsites created"
    print_step "Network sites:"
    wp site list --allow-root
}

step_11_add_initial_topics() {
    print_step "Step 11: Adding initial topics to main site queue..."

    cd "$WP_PATH"

    TOPICS=(
        "Content marketing strategies for bloggers in 2026"
        "How to start a successful blog from scratch"
        "SEO fundamentals: ranking your content in Google"
        "AI writing tools: best picks for content creators"
        "WordPress tips and tricks for site owners"
        "Digital publishing trends to watch this year"
        "How to monetize a blog with affiliate marketing"
        "Building a niche site that earns passive income"
        "Affiliate marketing guide for beginners"
        "Content strategy framework for multi-site networks"
        "Keyword research step-by-step guide"
        "Email marketing for bloggers: grow your list fast"
        "Social media strategies that drive blog traffic"
        "How to use DALL-E 3 for blog images"
        "Long-form content vs short-form: what converts better"
        "On-page SEO checklist for WordPress posts"
        "Link building tactics that still work in 2026"
        "How to write pillar content for your niche"
        "WordPress multisite: managing a content network"
        "Programmatic SEO: scale content with AI"
        "How to choose a profitable niche for your blog"
        "Technical SEO basics every blogger should know"
        "Growing a YouTube channel to support your blog"
        "Pinterest marketing for bloggers and publishers"
        "How to repurpose blog content across channels"
        "Building topical authority in your niche"
        "How to write high-converting product reviews"
        "Google Search Console tips for content publishers"
        "Using structured data to boost click-through rates"
        "Building a media brand with WordPress Multisite"
    )

    for topic in "${TOPICS[@]}"; do
        wp pearblog queue add "$topic" --allow-root
    done

    print_success "Added ${#TOPICS[@]} topics to main site queue"
}

step_12_setup_ssl() {
    print_step "Step 12: Setting up wildcard SSL certificate for *.${DOMAIN}..."

    if ! check_command certbot; then
        print_step "Installing Certbot..."
        apt install -y certbot &> /dev/null
    fi

    read -p "Enter email for SSL certificate: " SSL_EMAIL

    print_warning "Wildcard SSL requires DNS-01 challenge (manual DNS TXT record)."
    print_warning "You will need access to your DNS provider to add a TXT record."
    echo ""

    certbot certonly --manual --preferred-challenges dns \
        -d "$DOMAIN" -d "*.${DOMAIN}" \
        --email "$SSL_EMAIL" \
        --agree-tos

    print_success "Wildcard SSL certificate obtained for ${DOMAIN} and *.${DOMAIN}"

    # Configure web server to use the wildcard certificate
    if check_command apache2; then
        print_step "Configuring Apache SSL VirtualHost..."
        print_warning "Update your Apache VirtualHost to use:"
        echo "  SSLCertificateFile /etc/letsencrypt/live/${DOMAIN}/fullchain.pem"
        echo "  SSLCertificateKeyFile /etc/letsencrypt/live/${DOMAIN}/privkey.pem"
        echo "  ServerAlias *.${DOMAIN}"
    elif check_command nginx; then
        print_step "Update your Nginx server block to use:"
        echo "  ssl_certificate /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;"
        echo "  ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;"
    fi

    # Update main site WordPress URLs to HTTPS
    cd "$WP_PATH"
    wp option update home "https://${DOMAIN}" --allow-root
    wp option update siteurl "https://${DOMAIN}" --allow-root
    print_success "Main site URLs updated to HTTPS"

    # Update all subsites to HTTPS
    print_step "Updating all subsites to HTTPS..."
    wp site list --field=url --allow-root | while read -r SITE_URL; do
        HTTPS_URL="${SITE_URL/http:\/\//https://}"
        wp --url="$SITE_URL" option update home "$HTTPS_URL" --allow-root 2>/dev/null || true
        wp --url="$SITE_URL" option update siteurl "$HTTPS_URL" --allow-root 2>/dev/null || true
        print_success "Updated: $SITE_URL → $HTTPS_URL"
    done

    print_success "SSL certificate installed and all sites updated to HTTPS"
}

step_13_test_deployment() {
    print_step "Step 13: Testing deployment..."

    cd "$WP_PATH"

    # Test pipeline on main site
    print_step "Generating first article on main site..."
    wp pearblog generate --allow-root

    # Check statistics
    wp pearblog stats --allow-root

    # Test health endpoint
    print_step "Testing health endpoint..."
    HEALTH_RESPONSE=$(curl -s "https://${DOMAIN}/wp-json/pearblog/v1/health" || echo '{"status":"error"}')
    echo "$HEALTH_RESPONSE"

    # List all network sites
    print_step "Network sites:"
    wp site list --allow-root

    print_success "Deployment tested"
}

step_14_enable_autonomous_mode() {
    print_step "Step 14: Enabling autonomous mode on network and all subsites..."

    cd "$WP_PATH"

    # Enable on network (main site)
    wp option update pearblog_autonomous_mode "1" --allow-root
    print_success "Autonomous mode enabled on main site"

    # Enable on all subsites
    wp site list --field=url --allow-root | while read -r SITE_URL; do
        wp --url="$SITE_URL" option update pearblog_autonomous_mode "1" --allow-root 2>/dev/null || true
        print_success "Autonomous mode enabled on: $SITE_URL"
    done

    # Start Autopilot on main site
    print_step "Starting Autopilot on main site..."
    wp pearblog autopilot start --allow-root

    print_success "Autonomous mode enabled network-wide"
}

# Main execution
main() {
    echo ""
    echo "╔═══════════════════════════════════════════════════════════╗"
    echo "║                                                           ║"
    echo "║   PearBlog Engine Multisite Deployment for peartree.pro  ║"
    echo "║                     Version 6.0.0                         ║"
    echo "║         Multi-Site Content Network (Subdomain)           ║"
    echo "║                                                           ║"
    echo "╚═══════════════════════════════════════════════════════════╝"
    echo ""

    check_root

    print_step "Domain: $DOMAIN"
    print_step "Server: $SERVER_IP"
    print_step "Path: $WP_PATH"
    print_step "Network type: WordPress Multisite (subdomain)"
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
    step_8b_configure_webserver_multisite
    step_9_deploy_pearblog
    step_10_configure_pearblog
    step_10b_create_initial_subsites
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
    print_success "Network Admin: https://${DOMAIN}/wp-admin/network/"
    print_success "Main Site: https://${DOMAIN}"
    print_success "Blog subsite: https://blog.${DOMAIN}"
    print_success "News subsite: https://news.${DOMAIN}"
    print_success "Reviews subsite: https://reviews.${DOMAIN}"
    print_success "Health URL: https://${DOMAIN}/wp-json/pearblog/v1/health"
    echo ""
    print_step "Next steps:"
    echo "  1. Visit your network admin: https://${DOMAIN}/wp-admin/network/"
    echo "  2. Verify all subsites are accessible"
    echo "  3. Check PearBlog Engine → Queue on each site"
    echo "  4. Monitor logs: tail -f ${WP_PATH}/wp-content/pearblog-engine.log"
    echo "  5. Check autopilot: wp pearblog autopilot status --allow-root"
    echo "  6. List all sites: wp site list --allow-root"
    echo ""
}

# Run main function
main "$@"

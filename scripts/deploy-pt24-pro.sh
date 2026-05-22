#!/bin/bash

################################################################################
# PearBlog Engine v8.0 - Automated Deployment Script for pt24.pro
#
# This script automates the complete deployment of PearBlog Engine v8.0
# on a fresh Ubuntu/Debian server for the pt24.pro domain.
#
# Usage:
#   curl -sL https://raw.githubusercontent.com/AndyPearman89/PearBlog-Engine-/main/scripts/deploy-pt24-pro.sh | bash
#
# Or download and run:
#   wget https://raw.githubusercontent.com/AndyPearman89/PearBlog-Engine-/main/scripts/deploy-pt24-pro.sh
#   chmod +x deploy-pt24-pro.sh
#   ./deploy-pt24-pro.sh
#
# Requirements:
#   - Ubuntu 20.04+ or Debian 11+
#   - Root or sudo access
#   - DNS pointing to this server
#
################################################################################

set -e  # Exit on any error

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DOMAIN="pt24.pro"
WWW_DOMAIN="www.pt24.pro"
WEB_ROOT="/var/www/pt24.pro"
DB_NAME="pt24_db"
DB_USER="pt24_user"
DB_PASS=$(openssl rand -base64 32)
ADMIN_EMAIL="admin@pt24.pro"
ADMIN_USER="admin"
ADMIN_PASS=$(openssl rand -base64 16)
PEARBLOG_VERSION="v8.0.0"

# Logging
LOG_FILE="/var/log/pearblog-deploy-pt24.log"
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

check_distro() {
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        OS=$ID
        VERSION=$VERSION_ID
    else
        print_error "Cannot detect OS"
        exit 1
    fi

    if [[ "$OS" != "ubuntu" && "$OS" != "debian" ]]; then
        print_error "This script only supports Ubuntu and Debian"
        exit 1
    fi

    print_success "Detected: $OS $VERSION"
}

################################################################################
# Main Deployment Steps
################################################################################

print_header "PearBlog Engine v8.0 - Deployment for pt24.pro"
print_info "Starting automated deployment..."
print_info "Domain: $DOMAIN"
print_info "Web Root: $WEB_ROOT"
print_info "Log File: $LOG_FILE"

# Step 1: Pre-flight Checks
print_header "Step 1: Pre-flight Checks"
check_root
check_distro

# Step 2: Update System
print_header "Step 2: Updating System Packages"
apt update -y
apt upgrade -y
print_success "System updated"

# Step 3: Install Required Software
print_header "Step 3: Installing Required Software"

print_info "Installing PHP 8.1 and extensions..."
apt install -y software-properties-common
add-apt-repository -y ppa:ondrej/php 2>/dev/null || true
apt update -y
apt install -y php8.1 php8.1-cli php8.1-fpm php8.1-mysql \
    php8.1-xml php8.1-mbstring php8.1-curl php8.1-zip \
    php8.1-gd php8.1-intl php8.1-bcmath php8.1-soap php8.1-redis

print_success "PHP 8.1 installed"

print_info "Installing MariaDB..."
apt install -y mariadb-server mariadb-client
systemctl start mariadb
systemctl enable mariadb
print_success "MariaDB installed"

print_info "Installing Apache..."
apt install -y apache2 libapache2-mod-php8.1
a2enmod rewrite ssl headers
systemctl restart apache2
print_success "Apache installed"

print_info "Installing additional tools..."
apt install -y curl wget git unzip certbot python3-certbot-apache
print_success "Additional tools installed"

# Step 4: Install WP-CLI
print_header "Step 4: Installing WP-CLI"
if ! command -v wp &> /dev/null; then
    curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
    chmod +x wp-cli.phar
    mv wp-cli.phar /usr/local/bin/wp
    print_success "WP-CLI installed"
else
    print_success "WP-CLI already installed"
fi

wp --version --allow-root

# Step 5: Install Composer
print_header "Step 5: Installing Composer"
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
    print_success "Composer installed"
else
    print_success "Composer already installed"
fi

composer --version

# Step 6: Configure Database
print_header "Step 6: Configuring Database"
mysql -u root <<MYSQL_SCRIPT
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
MYSQL_SCRIPT

print_success "Database configured: $DB_NAME"

# Step 7: Download and Install WordPress
print_header "Step 7: Installing WordPress"
mkdir -p $WEB_ROOT
cd /tmp
wget -q https://wordpress.org/latest.tar.gz
tar -xzf latest.tar.gz
rm -rf $WEB_ROOT/*
mv wordpress/* $WEB_ROOT/
rm -rf wordpress latest.tar.gz

chown -R www-data:www-data $WEB_ROOT
chmod -R 755 $WEB_ROOT

print_success "WordPress downloaded to $WEB_ROOT"

# Step 8: Configure WordPress
print_header "Step 8: Configuring WordPress"
cd $WEB_ROOT

# Create wp-config.php
wp config create \
    --dbname=$DB_NAME \
    --dbuser=$DB_USER \
    --dbpass=$DB_PASS \
    --dbhost=localhost \
    --allow-root

# Add security salts
wp config shuffle-salts --allow-root

# Install WordPress
wp core install \
    --url="https://$DOMAIN" \
    --title="PT24 - News & Insights" \
    --admin_user=$ADMIN_USER \
    --admin_password=$ADMIN_PASS \
    --admin_email=$ADMIN_EMAIL \
    --allow-root

print_success "WordPress installed and configured"

# Step 9: Install PearBlog Engine
print_header "Step 9: Installing PearBlog Engine $PEARBLOG_VERSION"

mkdir -p $WEB_ROOT/wp-content/mu-plugins
cd $WEB_ROOT/wp-content/mu-plugins

print_info "Downloading PearBlog Engine..."
wget -q https://github.com/AndyPearman89/PearBlog-Engine-/archive/refs/tags/$PEARBLOG_VERSION.tar.gz
tar -xzf $PEARBLOG_VERSION.tar.gz
RELEASE_DIR="PearBlog-Engine--${PEARBLOG_VERSION#v}"
mv "${RELEASE_DIR}/mu-plugins/pearblog-engine" ./

print_success "PearBlog Engine extracted"

# Install Composer dependencies
print_info "Installing Composer dependencies..."
cd pearblog-engine
composer install --no-dev --optimize-autoloader --quiet
print_success "Composer dependencies installed"

# Step 10: Install PearBlog Theme
print_header "Step 10: Installing PearBlog Theme"
cd $WEB_ROOT/wp-content/themes
THEME_SOURCE="$WEB_ROOT/wp-content/mu-plugins/$RELEASE_DIR/theme/pearblog-theme"
if [ ! -d "$THEME_SOURCE" ]; then
    print_error "Theme source not found: $THEME_SOURCE"
    exit 1
fi

cp -r "$THEME_SOURCE" ./
wp theme activate pearblog-theme --allow-root
print_success "PearBlog theme installed and activated"

rm -rf "$WEB_ROOT/wp-content/mu-plugins/$RELEASE_DIR" "$WEB_ROOT/wp-content/mu-plugins/$PEARBLOG_VERSION.tar.gz"

# Step 11: Configure PearBlog Engine
print_header "Step 11: Configuring PearBlog Engine"

# Add PearBlog configuration to wp-config.php
cat >> $WEB_ROOT/wp-config.php <<'EOF'

/* PearBlog Engine v8.0 Configuration */
define('PEARBLOG_OPENAI_API_KEY', getenv('PEARBLOG_OPENAI_API_KEY') ?: 'YOUR_OPENAI_KEY_HERE');
define('PEARBLOG_ANTHROPIC_API_KEY', getenv('PEARBLOG_ANTHROPIC_API_KEY') ?: '');
define('PEARBLOG_GOOGLE_API_KEY', getenv('PEARBLOG_GOOGLE_API_KEY') ?: '');

/* Performance & Security */
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');
define('WP_POST_REVISIONS', 5);
define('AUTOSAVE_INTERVAL', 300);
define('DISALLOW_FILE_EDIT', true);
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
EOF

# Configure PearBlog settings
wp option update pearblog_industry 'news' --allow-root
wp option update pearblog_tone 'professional' --allow-root
wp option update pearblog_language 'pl' --allow-root
wp option update pearblog_publish_rate 2 --allow-root
wp option update pearblog_monetization 'adsense_booking' --allow-root
wp option update pearblog_homepage_version 'v7' --allow-root
wp option update pearblog_enable_image_generation true --allow-root
wp option update pearblog_ai_provider 'openai' --allow-root
wp option update pearblog_ai_model 'gpt-4o-mini' --allow-root

# Generate API key for REST API
PEARBLOG_API_KEY=$(openssl rand -hex 32)
wp option update pearblog_api_key "$PEARBLOG_API_KEY" --allow-root

print_success "PearBlog Engine configured"

# Step 12: Configure Apache
print_header "Step 12: Configuring Apache"

cat > /etc/apache2/sites-available/pt24-pro.conf <<EOF
<VirtualHost *:80>
    ServerName $DOMAIN
    ServerAlias $WWW_DOMAIN
    DocumentRoot $WEB_ROOT

    <Directory $WEB_ROOT>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/pt24-error.log
    CustomLog \${APACHE_LOG_DIR}/pt24-access.log combined
</VirtualHost>
EOF

a2dissite 000-default.conf 2>/dev/null || true
a2ensite pt24-pro.conf
systemctl reload apache2

print_success "Apache configured"

# Step 13: Set Up SSL with Let's Encrypt
print_header "Step 13: Setting Up SSL Certificate"

print_warning "Attempting to obtain SSL certificate..."
print_warning "This requires DNS to be properly configured for $DOMAIN and $WWW_DOMAIN"

if certbot --apache -d $DOMAIN -d $WWW_DOMAIN \
    --non-interactive --agree-tos --email $ADMIN_EMAIL \
    --redirect 2>&1 | tee -a "$LOG_FILE"; then

    print_success "SSL certificate obtained and installed"

    # Update WordPress URLs to HTTPS
    wp option update home "https://$DOMAIN" --allow-root
    wp option update siteurl "https://$DOMAIN" --allow-root
    wp search-replace "http://$DOMAIN" "https://$DOMAIN" --allow-root
else
    print_warning "SSL certificate installation failed or was skipped"
    print_warning "You can manually run: certbot --apache -d $DOMAIN -d $WWW_DOMAIN"
fi

# Step 14: Set Up Cron for Autonomous Generation
print_header "Step 14: Setting Up Cron Jobs"

# Add WordPress cron to system crontab
(crontab -l 2>/dev/null; echo "0 * * * * cd $WEB_ROOT && /usr/local/bin/wp cron event run --due-now --allow-root >/dev/null 2>&1") | crontab -

print_success "Cron job configured for hourly execution"

# Step 15: Set Permissions
print_header "Step 15: Setting Final Permissions"

chown -R www-data:www-data $WEB_ROOT
find $WEB_ROOT -type d -exec chmod 755 {} \;
find $WEB_ROOT -type f -exec chmod 644 {} \;

print_success "Permissions set"

# Step 16: Add Initial Topics to Queue
print_header "Step 16: Seeding Initial Content Topics"

wp pearblog queue add "Najnowsze wiadomości ze świata" --allow-root
wp pearblog queue add "Technologia i innowacje 2026" --allow-root
wp pearblog queue add "Biznes i finanse w Polsce" --allow-root
wp pearblog queue add "Sport i aktualne wydarzenia" --allow-root
wp pearblog queue add "Kultura i rozrywka" --allow-root

print_success "5 topics added to queue"

# Step 17: Generate Test Article
print_header "Step 17: Generating Test Article"

print_warning "Skipping test article generation (requires valid OpenAI API key)"
print_info "After adding your API key, run: wp pearblog generate --allow-root"

################################################################################
# Deployment Complete
################################################################################

print_header "Deployment Complete! 🎉"

echo ""
print_success "pt24.pro has been successfully deployed!"
echo ""

print_info "=== Important Information ==="
echo ""
echo "Domain:           https://$DOMAIN"
echo "Admin URL:        https://$DOMAIN/wp-admin"
echo "Admin User:       $ADMIN_USER"
echo "Admin Password:   $ADMIN_PASS"
echo ""
echo "Database Name:    $DB_NAME"
echo "Database User:    $DB_USER"
echo "Database Pass:    $DB_PASS"
echo ""
echo "PearBlog API Key: $PEARBLOG_API_KEY"
echo ""

print_warning "=== IMPORTANT: Save these credentials securely! ==="
echo ""

print_info "=== Next Steps ==="
echo ""
echo "1. Add your OpenAI API key:"
echo "   Edit: $WEB_ROOT/wp-config.php"
echo "   Replace: YOUR_OPENAI_KEY_HERE"
echo ""
echo "2. Test content generation:"
echo "   cd $WEB_ROOT && wp pearblog generate --allow-root"
echo ""
echo "3. Start autopilot mode:"
echo "   wp pearblog autopilot start --allow-root"
echo ""
echo "4. Access admin panel:"
echo "   https://$DOMAIN/wp-admin"
echo ""
echo "5. Configure monetization:"
echo "   Navigate to: PearBlog Engine → Monetization"
echo ""

print_info "=== Useful Commands ==="
echo ""
echo "View queue:       wp pearblog queue list --allow-root"
echo "Check stats:      wp pearblog stats --allow-root"
echo "Autopilot status: wp pearblog autopilot status --allow-root"
echo "Health check:     curl https://$DOMAIN/wp-json/pearblog/v1/health"
echo ""

print_info "=== Log Files ==="
echo ""
echo "Deployment log:   $LOG_FILE"
echo "WordPress log:    $WEB_ROOT/wp-content/debug.log"
echo "PearBlog log:     $WEB_ROOT/wp-content/pearblog-engine.log"
echo "Apache error:     /var/log/apache2/pt24-error.log"
echo ""

print_success "Full deployment documentation: DEPLOYMENT-pt24-pro.md"
print_success "Quick start guide: QUICKSTART-pt24-pro.md"

echo ""
print_header "Deployment completed at $(date)"

exit 0

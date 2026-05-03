# 🚀 Deployment Guide: pt24.pro

**Domain:** pt24.pro (www.pt24.pro)
**Target:** Production deployment of PearBlog Engine v7.0
**Purpose:** AI-powered content automation platform

---

## Quick Deploy Commands

```bash
# From your local machine, connect to server:
ssh root@YOUR_SERVER_IP

# Once connected, run automated deployment:
curl -sL https://raw.githubusercontent.com/AndyPearman89/PearBlog-Engine-/main/scripts/deploy-pt24-pro.sh | bash
```

---

## Table of Contents

1. [Prerequisites Check](#1-prerequisites-check)
2. [Initial Server Setup](#2-initial-server-setup)
3. [WordPress Installation](#3-wordpress-installation)
4. [PearBlog Engine Deployment](#4-pearblog-engine-deployment)
5. [Configuration](#5-configuration)
6. [SSL Setup](#6-ssl-setup)
7. [Testing & Verification](#7-testing--verification)
8. [Go Live](#8-go-live)
9. [Monitoring](#9-monitoring)
10. [Troubleshooting](#10-troubleshooting)

---

## 1. Prerequisites Check

### Server Access
```bash
# Test SSH access:
ssh root@YOUR_SERVER_IP

# Should connect successfully
```

### Required Software
```bash
# Check PHP version (need ≥8.1):
php -v

# Check MySQL/MariaDB:
mysql --version

# Check web server:
apache2 -v  # or
nginx -v

# Install WP-CLI if not present:
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
mv wp-cli.phar /usr/local/bin/wp
wp --version
```

### DNS Verification
```bash
# Verify DNS is pointing to your server:
dig pt24.pro +short
dig www.pt24.pro +short

# Should return your server IP
nslookup pt24.pro
```

---

## 2. Initial Server Setup

### Install Required PHP Extensions
```bash
# Update system packages:
apt update && apt upgrade -y

# Install PHP 8.1+ with required extensions:
apt install -y php8.1 php8.1-cli php8.1-fpm php8.1-mysql \
  php8.1-xml php8.1-mbstring php8.1-curl php8.1-zip \
  php8.1-gd php8.1-intl php8.1-bcmath

# Verify PHP version:
php -v  # Should show 8.1 or higher
```

### Install MySQL/MariaDB
```bash
# Install MariaDB:
apt install -y mariadb-server mariadb-client

# Secure MySQL installation:
mysql_secure_installation

# Create database and user:
mysql -u root -p <<EOF
CREATE DATABASE pt24_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pt24_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON pt24_db.* TO 'pt24_user'@'localhost';
FLUSH PRIVILEGES;
EOF
```

### Install Web Server (Apache or Nginx)

#### Option A: Apache
```bash
apt install -y apache2 libapache2-mod-php8.1

# Enable required modules:
a2enmod rewrite
a2enmod ssl
a2enmod headers

# Restart Apache:
systemctl restart apache2
```

#### Option B: Nginx
```bash
apt install -y nginx

# Start and enable Nginx:
systemctl start nginx
systemctl enable nginx
```

---

## 3. WordPress Installation

### Download and Install WordPress
```bash
# Navigate to web root:
cd /var/www/

# Download WordPress:
wget https://wordpress.org/latest.tar.gz
tar -xzf latest.tar.gz
mv wordpress pt24.pro

# Set ownership:
chown -R www-data:www-data /var/www/pt24.pro
chmod -R 755 /var/www/pt24.pro
```

### Configure Web Server

#### Apache Configuration
```bash
# Create virtual host:
cat > /etc/apache2/sites-available/pt24-pro.conf <<'EOF'
<VirtualHost *:80>
    ServerName pt24.pro
    ServerAlias www.pt24.pro
    DocumentRoot /var/www/pt24.pro

    <Directory /var/www/pt24.pro>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/pt24-error.log
    CustomLog ${APACHE_LOG_DIR}/pt24-access.log combined
</VirtualHost>
EOF

# Enable site:
a2ensite pt24-pro.conf
systemctl reload apache2
```

#### Nginx Configuration
```bash
# Create server block:
cat > /etc/nginx/sites-available/pt24-pro <<'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name pt24.pro www.pt24.pro;
    root /var/www/pt24.pro;
    index index.php index.html;

    client_max_body_size 100M;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

# Enable site:
ln -s /etc/nginx/sites-available/pt24-pro /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

### WordPress Installation via WP-CLI
```bash
cd /var/www/pt24.pro

# Create wp-config.php:
wp config create \
  --dbname=pt24_db \
  --dbuser=pt24_user \
  --dbpass=STRONG_PASSWORD_HERE \
  --dbhost=localhost \
  --allow-root

# Install WordPress:
wp core install \
  --url=https://pt24.pro \
  --title="PT24 - News & Insights" \
  --admin_user=admin \
  --admin_password=ADMIN_PASSWORD_HERE \
  --admin_email=admin@pt24.pro \
  --allow-root
```

---

## 4. PearBlog Engine Deployment

### Clone PearBlog Engine Repository
```bash
cd /var/www/pt24.pro

# Create mu-plugins directory:
mkdir -p wp-content/mu-plugins

# Clone PearBlog Engine:
cd wp-content/mu-plugins
git clone https://github.com/AndyPearman89/PearBlog-Engine-.git pearblog-engine

# Or download release:
wget https://github.com/AndyPearman89/PearBlog-Engine-/archive/refs/tags/v7.0.0.tar.gz
tar -xzf v7.0.0.tar.gz
mv PearBlog-Engine--7.0.0/mu-plugins/pearblog-engine ./
rm -rf PearBlog-Engine--7.0.0 v7.0.0.tar.gz
```

### Install Composer Dependencies
```bash
cd /var/www/pt24.pro/wp-content/mu-plugins/pearblog-engine

# Install Composer if not present:
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
fi

# Install dependencies:
composer install --no-dev --optimize-autoloader

# Set correct permissions:
chown -R www-data:www-data /var/www/pt24.pro
```

### Install PearBlog Theme
```bash
cd /var/www/pt24.pro/wp-content/themes

# Copy theme from engine repository:
cp -r ../mu-plugins/pearblog-engine/theme/pearblog-theme ./

# Activate theme:
wp theme activate pearblog-theme --allow-root
```

---

## 5. Configuration

### Configure wp-config.php
```bash
cd /var/www/pt24.pro

# Add PearBlog Engine API keys:
cat >> wp-config.php <<'EOF'

/* PearBlog Engine v7.0 Configuration */
define('PEARBLOG_OPENAI_API_KEY', 'sk-YOUR_OPENAI_KEY');
define('PEARBLOG_ANTHROPIC_API_KEY', 'sk-ant-YOUR_ANTHROPIC_KEY');
define('PEARBLOG_GOOGLE_API_KEY', 'YOUR_GOOGLE_API_KEY');

/* Performance & Security */
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');
define('WP_POST_REVISIONS', 5);
define('AUTOSAVE_INTERVAL', 300);
define('DISALLOW_FILE_EDIT', true);
EOF
```

### Configure PearBlog Engine Settings
```bash
# Set site profile via WP-CLI:
wp option update pearblog_industry 'news' --allow-root
wp option update pearblog_tone 'professional' --allow-root
wp option update pearblog_language 'pl' --allow-root
wp option update pearblog_publish_rate 2 --allow-root
wp option update pearblog_monetization 'adsense_booking' --allow-root

# Enable v7 UI Kit:
wp option update pearblog_homepage_version 'v7' --allow-root

# Enable AI features:
wp option update pearblog_enable_image_generation true --allow-root
wp option update pearblog_ai_provider 'openai' --allow-root
wp option update pearblog_ai_model 'gpt-4o-mini' --allow-root

# Generate API key for REST API:
wp option update pearblog_api_key "$(openssl rand -hex 32)" --allow-root
```

### Set Up Cron for Autonomous Generation
```bash
# Add WordPress cron job:
crontab -e

# Add this line (runs every hour):
0 * * * * cd /var/www/pt24.pro && /usr/local/bin/wp cron event run --due-now --allow-root >/dev/null 2>&1
```

---

## 6. SSL Setup

### Install Certbot
```bash
# Install Certbot:
apt install -y certbot

# For Apache:
apt install -y python3-certbot-apache
certbot --apache -d pt24.pro -d www.pt24.pro \
  --non-interactive --agree-tos --email admin@pt24.pro

# For Nginx:
apt install -y python3-certbot-nginx
certbot --nginx -d pt24.pro -d www.pt24.pro \
  --non-interactive --agree-tos --email admin@pt24.pro

# Verify auto-renewal:
certbot renew --dry-run
```

### Update WordPress URLs
```bash
cd /var/www/pt24.pro

# Update site URLs to HTTPS:
wp option update home 'https://pt24.pro' --allow-root
wp option update siteurl 'https://pt24.pro' --allow-root

# Search and replace URLs in database:
wp search-replace 'http://pt24.pro' 'https://pt24.pro' --allow-root
```

---

## 7. Testing & Verification

### Health Check
```bash
# Test WordPress installation:
curl -I https://pt24.pro
# Should return: HTTP/2 200

# Test PearBlog Engine health endpoint:
curl https://pt24.pro/wp-json/pearblog/v1/health
# Should return: {"status":"ok",...}

# Verify admin access:
# Visit: https://pt24.pro/wp-admin
```

### Run Test Content Generation
```bash
cd /var/www/pt24.pro

# Add a test topic:
wp pearblog queue add "Najnowsze wiadomości technologiczne" --allow-root

# Generate test article:
wp pearblog generate --allow-root

# Check results:
wp post list --post_type=post --allow-root
```

### Performance Test
```bash
# Test page load speed:
curl -w "@-" -o /dev/null -s https://pt24.pro <<'EOF'
time_namelookup:  %{time_namelookup}\n
time_connect:  %{time_connect}\n
time_starttransfer:  %{time_starttransfer}\n
time_total:  %{time_total}\n
EOF

# Should complete in < 2s
```

---

## 8. Go Live

### Pre-Launch Checklist

- [ ] DNS points to server (pt24.pro → server IP)
- [ ] SSL certificate active and valid
- [ ] WordPress admin accessible
- [ ] PearBlog Engine configured with API keys
- [ ] Theme activated (pearblog-theme v7.0)
- [ ] Test article generated successfully
- [ ] Cron job configured for hourly generation
- [ ] Backup system in place
- [ ] Monitoring configured

### Start Autonomous Generation
```bash
cd /var/www/pt24.pro

# Start autopilot mode:
wp pearblog autopilot start --allow-root

# Check autopilot status:
wp pearblog autopilot status --allow-root

# View queue:
wp pearblog queue list --allow-root
```

### Initial Content Seeding
```bash
# Add initial topics to queue:
wp pearblog queue add "Aktualne wiadomości ze świata" --allow-root
wp pearblog queue add "Technologia i innowacje" --allow-root
wp pearblog queue add "Biznes i finanse" --allow-root
wp pearblog queue add "Sport i rozrywka" --allow-root
wp pearblog queue add "Kultura i lifestyle" --allow-root

# Generate first batch:
wp pearblog generate --allow-root
```

---

## 9. Monitoring

### WordPress Dashboard
```
Admin URL: https://pt24.pro/wp-admin
Login: admin / [your password]

Navigate to: PearBlog Engine → Dashboard
```

### Performance Monitoring
```bash
# Check pipeline statistics:
wp pearblog stats --allow-root

# View recent articles:
wp post list --post_type=post --posts_per_page=10 --allow-root

# Check queue status:
wp pearblog queue list --allow-root
```

### Log Monitoring
```bash
# WordPress error log:
tail -f /var/www/pt24.pro/wp-content/debug.log

# Web server logs:
tail -f /var/log/apache2/pt24-error.log  # Apache
tail -f /var/log/nginx/error.log         # Nginx

# PearBlog Engine log:
tail -f /var/www/pt24.pro/wp-content/pearblog-engine.log
```

### Health Monitoring Setup
```bash
# Set up external monitoring (UptimeRobot, Pingdom, etc.)
# Monitor these endpoints:

# Main site:
https://pt24.pro

# Health endpoint:
https://pt24.pro/wp-json/pearblog/v1/health

# Admin area:
https://pt24.pro/wp-admin
```

---

## 10. Troubleshooting

### Common Issues

#### Issue: "Error establishing database connection"
```bash
# Check MySQL service:
systemctl status mysql

# Verify database credentials in wp-config.php:
cat /var/www/pt24.pro/wp-config.php | grep DB_

# Test database connection:
mysql -u pt24_user -p pt24_db
```

#### Issue: 500 Internal Server Error
```bash
# Check PHP error log:
tail -50 /var/log/php8.1-fpm.log

# Check web server error log:
tail -50 /var/log/apache2/pt24-error.log  # Apache
tail -50 /var/log/nginx/error.log         # Nginx

# Verify file permissions:
chown -R www-data:www-data /var/www/pt24.pro
chmod -R 755 /var/www/pt24.pro
```

#### Issue: "Permission denied" errors
```bash
# Fix ownership:
chown -R www-data:www-data /var/www/pt24.pro

# Fix permissions:
find /var/www/pt24.pro -type d -exec chmod 755 {} \;
find /var/www/pt24.pro -type f -exec chmod 644 {} \;
```

#### Issue: Content not generating
```bash
# Check API keys:
wp option get pearblog_openai_api_key --allow-root

# Test AI provider:
wp pearblog generate --allow-root

# Check circuit breaker status:
wp pearblog circuit status --allow-root

# Reset if needed:
wp pearblog circuit reset --allow-root
```

#### Issue: SSL certificate issues
```bash
# Renew certificate manually:
certbot renew --force-renewal

# Check certificate status:
certbot certificates

# Verify SSL configuration:
openssl s_client -connect pt24.pro:443 -servername pt24.pro
```

### Getting Help

- **Documentation**: https://github.com/AndyPearman89/PearBlog-Engine-/
- **Issues**: https://github.com/AndyPearman89/PearBlog-Engine-/issues
- **Support Email**: support@pearblog.pro

---

## Backup Strategy

### Automated Daily Backups
```bash
# Create backup script:
cat > /root/backup-pt24.sh <<'EOF'
#!/bin/bash
BACKUP_DIR="/root/backups/pt24"
DATE=$(date +%Y-%m-%d)

mkdir -p $BACKUP_DIR

# Backup database:
mysqldump -u pt24_user -p'PASSWORD' pt24_db | gzip > $BACKUP_DIR/db-$DATE.sql.gz

# Backup files:
tar -czf $BACKUP_DIR/files-$DATE.tar.gz /var/www/pt24.pro

# Keep only last 7 days:
find $BACKUP_DIR -type f -mtime +7 -delete

echo "Backup completed: $DATE"
EOF

chmod +x /root/backup-pt24.sh

# Add to cron (daily at 2 AM):
echo "0 2 * * * /root/backup-pt24.sh" | crontab -
```

---

## Performance Optimization

### Enable Caching
```bash
# Install Redis (optional):
apt install -y redis-server php8.1-redis

# Install WordPress caching plugin:
wp plugin install redis-cache --activate --allow-root
wp redis enable --allow-root
```

### Optimize Database
```bash
cd /var/www/pt24.pro

# Optimize all tables:
wp db optimize --allow-root

# Clean up revisions:
wp post delete $(wp post list --post_type='revision' --format=ids --allow-root) --allow-root
```

---

## Security Hardening

### Install Security Plugins
```bash
cd /var/www/pt24.pro

# Install Wordfence or similar:
wp plugin install wordfence --activate --allow-root

# Disable XML-RPC:
wp plugin install disable-xml-rpc --activate --allow-root
```

### Configure Firewall
```bash
# Install UFW:
apt install -y ufw

# Allow necessary ports:
ufw allow 22/tcp    # SSH
ufw allow 80/tcp    # HTTP
ufw allow 443/tcp   # HTTPS

# Enable firewall:
ufw --force enable
ufw status
```

---

## Maintenance Commands

```bash
# Update WordPress core:
wp core update --allow-root

# Update plugins:
wp plugin update --all --allow-root

# Update PearBlog Engine:
cd /var/www/pt24.pro/wp-content/mu-plugins/pearblog-engine
git pull origin main
composer install --no-dev --optimize-autoloader

# Clear all caches:
wp cache flush --allow-root
wp rewrite flush --allow-root
```

---

**Deployment Guide Version:** 1.0
**Last Updated:** May 3, 2026
**PearBlog Engine Version:** v7.0.0
**Status:** Ready for Production

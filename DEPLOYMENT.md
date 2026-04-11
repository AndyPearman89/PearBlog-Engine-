# PearBlog Engine — Production Deployment Guide

> **Version:** 6.0.0  
> **Audience:** System administrators, DevOps engineers, WordPress developers  
> **Prerequisites:** Root or sudo access to the web server, SSH access

---

## Table of Contents

1. [System Requirements](#1-system-requirements)
2. [Pre-Deployment Checklist](#2-pre-deployment-checklist)
3. [Environment Variables & Secrets](#3-environment-variables--secrets)
4. [Server Configuration](#4-server-configuration)
   - 4.1 [Apache](#41-apache)
   - 4.2 [Nginx](#42-nginx)
5. [SSL / HTTPS Setup](#5-ssl--https-setup)
6. [Deployment Methods](#6-deployment-methods)
   - 6.1 [Deploy via Git + SSH (recommended)](#61-deploy-via-git--ssh-recommended)
   - 6.2 [Deploy via GitHub Actions (CI/CD)](#62-deploy-via-github-actions-cicd)
   - 6.3 [Deploy via WP-CLI](#63-deploy-via-wp-cli)
   - 6.4 [Deploy via FTP / SFTP](#64-deploy-via-ftp--sftp)
7. [Hosting Provider Examples](#7-hosting-provider-examples)
   - 7.1 [SiteGround (Shared / Cloud)](#71-siteground-shared--cloud)
   - 7.2 [Kinsta (Managed WordPress)](#72-kinsta-managed-wordpress)
   - 7.3 [DigitalOcean (VPS / Droplet)](#73-digitalocean-vps--droplet)
   - 7.4 [WP Engine (Managed WordPress)](#74-wp-engine-managed-wordpress)
8. [Post-Deployment Verification](#8-post-deployment-verification)
9. [Rolling Back a Deployment](#9-rolling-back-a-deployment)
10. [Troubleshooting](#10-troubleshooting)
11. [Security Hardening](#11-security-hardening)
12. [Performance Tuning](#12-performance-tuning)

---

## 1. System Requirements

### Minimum Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| **PHP** | 8.0 | 8.2+ |
| **MySQL** | 5.7 | 8.0+ |
| **MariaDB** | 10.4 | 10.6+ |
| **WordPress** | 6.0 | 6.4+ |
| **Web Server** | Apache 2.4 / Nginx 1.18 | Apache 2.4.50+ / Nginx 1.24+ |
| **PHP Memory Limit** | 256 MB | 512 MB |
| **Max Execution Time** | 120 s | 300 s |
| **Max Upload Size** | 32 MB | 64 MB |
| **Disk Space** | 2 GB | 10 GB+ |

### Required PHP Extensions

```
php-curl
php-json
php-mbstring
php-xml
php-zip
php-gd        (for image processing)
php-intl      (for multilingual SEO)
php-openssl
```

Verify all extensions are loaded:

```bash
php -m | grep -E 'curl|json|mbstring|xml|zip|gd|intl|openssl'
```

### Optional PHP Extensions

| Extension | Purpose |
|-----------|---------|
| `php-redis` | Object-cache backend (improves performance) |
| `php-imagick` | Higher-quality image processing |
| `php-apcu` | In-process opcode caching |

### WordPress Configuration Requirements

The following `wp-config.php` constants must be set before activation:

```php
// Increase memory limit (required for AI pipeline)
define( 'WP_MEMORY_LIMIT', '512M' );

// Allow cron to run reliably
define( 'DISABLE_WP_CRON', false );

// Required for WP-CLI autopilot commands
define( 'WP_CLI_ALLOW_ROOT', true ); // only if running as root
```

---

## 2. Pre-Deployment Checklist

Complete every item before deploying to production.

### Infrastructure

- [ ] Server meets PHP 8.0+ requirement
- [ ] MySQL 5.7+ / MariaDB 10.4+ installed and running
- [ ] WordPress 6.0+ installed
- [ ] SSL certificate obtained (Let's Encrypt or commercial)
- [ ] DNS A/AAAA records pointing to correct server IP
- [ ] Firewall allows ports 80 (HTTP) and 443 (HTTPS)
- [ ] SSH access configured and tested
- [ ] Backup system verified (database + files)

### PHP Configuration

- [ ] `memory_limit = 512M` in `php.ini`
- [ ] `max_execution_time = 300` in `php.ini`
- [ ] `upload_max_filesize = 64M` in `php.ini`
- [ ] `post_max_size = 64M` in `php.ini`
- [ ] Required PHP extensions installed (see §1)
- [ ] OPcache enabled (`opcache.enable=1`)

### WordPress

- [ ] WordPress core is up-to-date (latest minor release)
- [ ] All other plugins tested for compatibility
- [ ] `wp-config.php` has `WP_MEMORY_LIMIT` set to `512M`
- [ ] WP-Cron is functional (`wp cron event list` returns events)
- [ ] Permalink structure set (not plain)
- [ ] Admin email configured and deliverable

### API Keys & Credentials

- [ ] OpenAI API key obtained and credited
- [ ] OpenAI usage limit set ($50–100/month recommended)
- [ ] (Optional) Stability AI key for image generation
- [ ] (Optional) Mailchimp / ConvertKit API key for email digests
- [ ] (Optional) Social media app credentials (Twitter/X, Facebook, LinkedIn)
- [ ] (Optional) Slack/Discord webhook URLs for monitoring alerts

### Security

- [ ] `wp-config.php` is outside the web root or protected by server rules
- [ ] WordPress database table prefix changed from default `wp_`
- [ ] File permissions correct (see §11)
- [ ] `WP_DEBUG` disabled (`define('WP_DEBUG', false)`)

---

## 3. Environment Variables & Secrets

PearBlog Engine reads its sensitive values from WordPress options (set via the Admin UI or `wp-config.php` constants). All secrets should be stored as **GitHub Actions Secrets** if using CI/CD deployment.

### WordPress Options (Admin UI → PearBlog Engine)

| Option Key | Description | Required |
|------------|-------------|----------|
| `pearblog_openai_api_key` | OpenAI API key | **Yes** |
| `pearblog_industry` | Content niche / industry | **Yes** |
| `pearblog_tone` | Writing tone (professional, casual…) | **Yes** |
| `pearblog_publish_rate` | Articles per hour (float, e.g. `0.5`) | **Yes** |
| `pearblog_language` | ISO language code (`en`, `pl`, `de`) | **Yes** |
| `pearblog_autonomous_mode` | Enable autonomous cron pipeline (`1`/`0`) | **Yes** |
| `pearblog_adsense_publisher_id` | Google AdSense Publisher ID | No |
| `pearblog_alert_slack_webhook` | Slack incoming webhook URL | No |
| `pearblog_alert_discord_webhook` | Discord webhook URL | No |
| `pearblog_alert_email` | Email address for monitoring alerts | No |
| `pearblog_mailchimp_api_key` | Mailchimp API key | No |
| `pearblog_convertkit_api_key` | ConvertKit API key | No |

### wp-config.php Constants (alternative / override)

Placing API keys directly in `wp-config.php` takes precedence over the Admin UI values and keeps secrets out of the database:

```php
// ── PearBlog Engine Secrets ──────────────────────────────────────────────────
define( 'PEARBLOG_OPENAI_API_KEY',   getenv( 'PEARBLOG_OPENAI_API_KEY' ) ?: '' );
define( 'PEARBLOG_ALERT_EMAIL',      'ops@example.com' );
define( 'PEARBLOG_ALERT_SLACK',      getenv( 'PEARBLOG_ALERT_SLACK' ) ?: '' );
```

> **Security note:** Never commit real API keys to version control. Use environment variables injected at runtime (e.g., via systemd `EnvironmentFile`, Docker env files, or your hosting panel's environment variable manager).

### GitHub Actions Secrets (for CI/CD deployment)

| Secret | Description |
|--------|-------------|
| `SSH_HOST` | Server hostname or IP |
| `SSH_USER` | SSH user (`deploy`, `www-data`, etc.) |
| `SSH_PRIVATE_KEY` | Full private key content (`-----BEGIN OPENSSH...`) |
| `WP_PATH` | Absolute path to WordPress root on the server |
| `SSH_PORT` | SSH port (default `22`) |

---

## 4. Server Configuration

### 4.1 Apache

#### Virtual Host (HTTP → HTTPS redirect)

```apacheconf
<VirtualHost *:80>
    ServerName  example.com
    ServerAlias www.example.com
    Redirect    permanent / https://example.com/
</VirtualHost>
```

#### Virtual Host (HTTPS / WordPress)

```apacheconf
<VirtualHost *:443>
    ServerName  example.com
    ServerAlias www.example.com
    DocumentRoot /var/www/example.com/public

    SSLEngine on
    SSLCertificateFile    /etc/letsencrypt/live/example.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/example.com/privkey.pem

    <Directory /var/www/example.com/public>
        Options       -Indexes +FollowSymLinks
        AllowOverride All
        Require       all granted
    </Directory>

    # PHP-FPM proxy (adjust socket path / port to your setup)
    <FilesMatch "\.php$">
        SetHandler "proxy:unix:/run/php/php8.2-fpm.sock|fcgi://localhost"
    </FilesMatch>

    # Increase upload & execution limits for the AI pipeline
    <IfModule mod_php.c>
        php_value memory_limit        512M
        php_value max_execution_time  300
        php_value upload_max_filesize 64M
        php_value post_max_size       64M
    </IfModule>

    # Protect wp-config.php
    <Files wp-config.php>
        Require all denied
    </Files>

    # Block direct PHP execution in uploads
    <Directory /var/www/example.com/public/wp-content/uploads>
        <FilesMatch "\.php$">
            Require all denied
        </FilesMatch>
    </Directory>

    ErrorLog  /var/log/apache2/example.com-error.log
    CustomLog /var/log/apache2/example.com-access.log combined
</VirtualHost>
```

#### Required Apache Modules

```bash
a2enmod rewrite ssl proxy proxy_fcgi setenvif headers expires deflate
systemctl restart apache2
```

#### WordPress `.htaccess`

Place in the WordPress root directory:

```apacheconf
# BEGIN WordPress
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteRule ^index\.php$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /index.php [L]
</IfModule>
# END WordPress
```

---

### 4.2 Nginx

#### Main Server Block

```nginx
# HTTP → HTTPS redirect
server {
    listen      80;
    listen      [::]:80;
    server_name example.com www.example.com;
    return      301 https://example.com$request_uri;
}

# HTTPS WordPress
server {
    listen      443 ssl http2;
    listen      [::]:443 ssl http2;
    server_name example.com www.example.com;
    root        /var/www/example.com/public;
    index       index.php;

    # SSL
    ssl_certificate     /etc/letsencrypt/live/example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/example.com/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         HIGH:!aNULL:!MD5;
    ssl_session_cache   shared:SSL:10m;
    ssl_session_timeout 10m;

    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options            DENY always;
    add_header X-Content-Type-Options     nosniff always;
    add_header Referrer-Policy            strict-origin-when-cross-origin always;

    # WordPress permalinks
    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    # PHP-FPM
    location ~ \.php$ {
        include        fastcgi_params;
        fastcgi_pass   unix:/run/php/php8.2-fpm.sock;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;

        # Increase timeouts for AI pipeline
        fastcgi_read_timeout  300;
        fastcgi_send_timeout  300;
    }

    # Deny PHP in uploads
    location ~* /wp-content/uploads/.*\.php$ {
        deny all;
    }

    # Protect sensitive files
    location ~* /(wp-config\.php|\.env|\.git) {
        deny all;
    }

    # Static file caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2?)$ {
        expires    30d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # PHP memory / execution limits (via php-fpm pool, not nginx)
    # Set in /etc/php/8.2/fpm/pool.d/www.conf:
    #   php_value[memory_limit]        = 512M
    #   php_value[max_execution_time]  = 300

    error_log  /var/log/nginx/example.com-error.log;
    access_log /var/log/nginx/example.com-access.log;
}
```

#### PHP-FPM Pool Tuning (`/etc/php/8.2/fpm/pool.d/www.conf`)

```ini
[www]
user  = www-data
group = www-data

listen = /run/php/php8.2-fpm.sock

pm                   = dynamic
pm.max_children      = 20
pm.start_servers     = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 8
pm.max_requests      = 500

; PearBlog Engine overrides
php_value[memory_limit]        = 512M
php_value[max_execution_time]  = 300
php_value[upload_max_filesize] = 64M
php_value[post_max_size]       = 64M
```

Reload after changes:

```bash
systemctl reload php8.2-fpm nginx
```

---

## 5. SSL / HTTPS Setup

### Option A — Let's Encrypt (Certbot, free)

```bash
# Install certbot + web-server plugin
apt install certbot python3-certbot-nginx   # for Nginx
# or
apt install certbot python3-certbot-apache  # for Apache

# Obtain certificate (replace with your domain)
certbot --nginx  -d example.com -d www.example.com
# or
certbot --apache -d example.com -d www.example.com

# Auto-renewal (added automatically; verify)
systemctl status certbot.timer
```

Test renewal without actually renewing:

```bash
certbot renew --dry-run
```

### Option B — Commercial Certificate

1. Generate a CSR on the server:
   ```bash
   openssl req -new -newkey rsa:4096 -nodes \
     -keyout /etc/ssl/private/example.com.key \
     -out    /etc/ssl/certs/example.com.csr
   ```
2. Submit the CSR to your CA (DigiCert, Sectigo, etc.).
3. Download the issued certificate files.
4. Reference them in your virtual host (`SSLCertificateFile` / `ssl_certificate`).

### Force HTTPS in WordPress

Add to `wp-config.php` after WordPress is behind SSL:

```php
define( 'FORCE_SSL_ADMIN', true );

// When using a reverse proxy / load balancer that terminates SSL:
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) &&
     'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
    $_SERVER['HTTPS'] = 'on';
}
```

---

## 6. Deployment Methods

### 6.1 Deploy via Git + SSH (recommended)

This method clones the repository on the server and uses a post-receive hook to deploy automatically.

#### One-Time Server Setup

```bash
# 1. Create deployment directory
mkdir -p /var/www/example.com/repo
cd       /var/www/example.com/repo
git init --bare

# 2. Create post-receive hook
cat > hooks/post-receive << 'EOF'
#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="/var/www/example.com/repo"
WP_PATH="/var/www/example.com/public"
BRANCH="main"

while read oldrev newrev ref; do
  if [[ "$ref" == "refs/heads/$BRANCH" ]]; then
    echo "Deploying branch $BRANCH..."

    # Checkout mu-plugin
    git --work-tree="$WP_PATH/wp-content/mu-plugins" \
        --git-dir="$REPO_DIR" \
        checkout -f "$BRANCH" -- mu-plugins/pearblog-engine/

    # Checkout theme
    git --work-tree="$WP_PATH/wp-content/themes" \
        --git-dir="$REPO_DIR" \
        checkout -f "$BRANCH" -- theme/pearblog-theme/

    # Flush cache
    wp --path="$WP_PATH" cache flush --allow-root 2>/dev/null || true

    echo "Deploy complete."
  fi
done
EOF
chmod +x hooks/post-receive
```

#### From your development machine

```bash
# Add server as a remote (one-time)
git remote add production ssh://deploy@your-server-ip/var/www/example.com/repo

# Deploy
git push production main
```

---

### 6.2 Deploy via GitHub Actions (CI/CD)

The repository ships with `.github/workflows/deploy.yml`. Configure the following secrets in **Settings → Secrets and variables → Actions**:

| Secret | Example Value |
|--------|---------------|
| `SSH_HOST` | `185.23.45.67` |
| `SSH_USER` | `deploy` |
| `SSH_PRIVATE_KEY` | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `WP_PATH` | `/var/www/html` |
| `SSH_PORT` | `22` |

#### Creating a Deploy SSH Key

```bash
# On your local machine:
ssh-keygen -t ed25519 -C "github-actions-deploy" -f ~/.ssh/pearblog_deploy -N ""

# Authorise the public key on the server:
ssh-copy-id -i ~/.ssh/pearblog_deploy.pub deploy@your-server-ip

# Copy the private key content into the SSH_PRIVATE_KEY secret:
cat ~/.ssh/pearblog_deploy
```

#### Trigger Deployment

- **Automatically:** on every push to `main` that touches `mu-plugins/**` or `theme/**`.
- **Manually:** Actions → *Deploy to WordPress Server* → **Run workflow**.

---

### 6.3 Deploy via WP-CLI

Useful for quick updates on a single server with WP-CLI already installed.

```bash
SSH_USER=deploy
SSH_HOST=your-server-ip
WP_PATH=/var/www/html

# Upload files
rsync -avz --delete \
  mu-plugins/pearblog-engine/ \
  "$SSH_USER@$SSH_HOST:$WP_PATH/wp-content/mu-plugins/pearblog-engine/"

rsync -avz --delete \
  theme/pearblog-theme/ \
  "$SSH_USER@$SSH_HOST:$WP_PATH/wp-content/themes/pearblog-theme/"

# Post-deploy cache flush
ssh "$SSH_USER@$SSH_HOST" \
  "wp --path=$WP_PATH cache flush && wp --path=$WP_PATH cron event run --due-now"
```

#### Verify MU-Plugin Status

```bash
ssh "$SSH_USER@$SSH_HOST" \
  "wp --path=$WP_PATH plugin list --fields=name,status | grep pearblog"
```

Expected output:

```
pearblog-engine    must-use
```

---

### 6.4 Deploy via FTP / SFTP

> FTP is **not recommended** for production. Prefer SSH/rsync whenever possible. Use SFTP only when SSH access is unavailable.

#### With FileZilla

1. Open FileZilla → **File → Site Manager → New Site**.
2. Protocol: **SFTP – SSH File Transfer Protocol**.
3. Host: `your-server-ip`, Port: `22`.
4. Logon Type: Key file → browse to your private key.
5. Connect.
6. Navigate to `/wp-content/mu-plugins/` and upload `mu-plugins/pearblog-engine/`.
7. Navigate to `/wp-content/themes/` and upload `theme/pearblog-theme/`.

#### With lftp (CLI)

```bash
lftp -u "$FTP_USER,$FTP_PASS" sftp://your-server-ip << EOF
mirror -R mu-plugins/pearblog-engine \
          /wp-content/mu-plugins/pearblog-engine
mirror -R theme/pearblog-theme \
          /wp-content/themes/pearblog-theme
quit
EOF
```

---

## 7. Hosting Provider Examples

### 7.1 SiteGround (Shared / Cloud)

SiteGround accounts run cPanel with SSH and WP-CLI available on Growth and higher plans.

#### Enable SSH

1. cPanel → **SSH Access** → **Manage SSH Keys** → Generate new key.
2. Authorise the key.
3. Download the private key; copy into your SSH agent.

#### PHP Version

cPanel → **PHP Manager** → Select **PHP 8.2**.

Set PHP options via **PHP Manager → Switch to PHP Options**:

```
memory_limit = 512M
max_execution_time = 300
```

#### Deploy

```bash
ssh "$SG_USER@$SG_HOST" 'mkdir -p ~/public_html/wp-content/mu-plugins'

rsync -avz mu-plugins/pearblog-engine/ \
  "$SG_USER@$SG_HOST:~/public_html/wp-content/mu-plugins/pearblog-engine/"

rsync -avz theme/pearblog-theme/ \
  "$SG_USER@$SG_HOST:~/public_html/wp-content/themes/pearblog-theme/"

ssh "$SG_USER@$SG_HOST" \
  "wp --path=~/public_html cache flush"
```

#### WP-Cron

SiteGround may block WP-Cron on shared hosting. Replace it with a real cron job:

```bash
# In cPanel → Cron Jobs, add:
*/5 * * * *  wget -q -O /dev/null \
  "https://example.com/wp-cron.php?doing_wp_cron" > /dev/null 2>&1
```

And in `wp-config.php`:

```php
define( 'DISABLE_WP_CRON', true );
```

---

### 7.2 Kinsta (Managed WordPress)

Kinsta provides SSH, SFTP, WP-CLI, and git-based deployments via MyKinsta.

#### SSH Connection Details

MyKinsta → **Environment → Info → SSH/SFTP Credentials**.

```bash
ssh "$KINSTA_USER@$KINSTA_HOST" -p "$KINSTA_PORT"
```

#### MyKinsta Git Deployments

1. MyKinsta → **Environment → Git** → Connect GitHub repository.
2. Set **Deploy branch** to `main`.
3. Add a **Post-deployment script** (under MyKinsta → Environment → Git):

```bash
wp cache flush
wp cron event run --due-now
```

#### Kinsta PHP Limits

Kinsta uses Nginx + PHP-FPM on Google Cloud. PHP limits are managed per-environment:

MyKinsta → **Sites → Site name → Environment → Tools → PHP Engine** → set PHP 8.2.

Memory / timeout limits are set by Kinsta defaults (256 MB / 300 s). For the AI pipeline, submit a support request to increase memory to 512 MB or use the Kinsta API to bump limits programmatically.

#### Kinsta Object Cache

Enable the Kinsta Redis object-cache via:

MyKinsta → **Environment → Tools → Redis** → Enable.

Then install the `wp-redis` mu-plugin (Kinsta ships it) — no additional configuration needed.

---

### 7.3 DigitalOcean (VPS / Droplet)

A $12/month Droplet (2 vCPU / 2 GB RAM) is sufficient for up to ~50 articles/day.

#### Recommended Droplet

- **Image:** Ubuntu 22.04 LTS
- **Size:** 2 vCPU / 2 GB RAM (Basic)
- **Region:** closest to your audience
- **Authentication:** SSH key (add your key during creation)

#### Server Provisioning Script

```bash
#!/usr/bin/env bash
set -euo pipefail

# Update & install dependencies
apt update && apt upgrade -y
apt install -y nginx php8.2-fpm php8.2-mysql php8.2-curl \
  php8.2-mbstring php8.2-xml php8.2-zip php8.2-gd php8.2-intl \
  php8.2-imagick mysql-server certbot python3-certbot-nginx \
  git curl unzip

# Install WP-CLI
curl -sO https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
mv wp-cli.phar /usr/local/bin/wp

# Create deploy user
useradd -m -s /bin/bash deploy
mkdir -p /home/deploy/.ssh
# Paste your public key here:
echo "ssh-ed25519 AAAAC3... github-actions-deploy" \
  > /home/deploy/.ssh/authorized_keys
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys
chown -R deploy:deploy /home/deploy/.ssh

# Create WordPress root
mkdir -p /var/www/example.com/public
chown -R www-data:www-data /var/www/example.com
chmod -R 755 /var/www/example.com

echo "Provisioning complete."
```

#### WordPress Installation (WP-CLI)

```bash
cd /var/www/example.com/public

wp core download --locale=en_US

wp config create \
  --dbname=pearblog_db \
  --dbuser=pearblog_user \
  --dbpass=STRONG_PASSWORD \
  --dbhost=localhost \
  --extra-php << 'PHP'
define( 'WP_MEMORY_LIMIT', '512M' );
define( 'FORCE_SSL_ADMIN', true );
PHP

wp core install \
  --url=https://example.com \
  --title="My Blog" \
  --admin_user=admin \
  --admin_email=admin@example.com \
  --admin_password=STRONG_ADMIN_PASSWORD \
  --skip-email
```

#### MySQL Setup

```bash
mysql -u root -e "
  CREATE DATABASE IF NOT EXISTS pearblog_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER IF NOT EXISTS 'pearblog_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';
  GRANT ALL PRIVILEGES ON pearblog_db.* TO 'pearblog_user'@'localhost';
  FLUSH PRIVILEGES;
"
```

#### Managed Database (DigitalOcean Managed MySQL)

1. DigitalOcean Console → **Databases → Create Database → MySQL**.
2. Choose same datacenter region as your Droplet.
3. Add your Droplet to **Trusted Sources** (restricts access to the VPC).
4. Use the provided connection string in `wp-config.php`:

```php
define( 'DB_HOST', 'your-db-cluster.db.ondigitalocean.com:25060' );
define( 'DB_SSL_KEY',  '/path/to/client-key.pem' );
define( 'DB_SSL_CERT', '/path/to/client-cert.pem' );
define( 'DB_SSL_CA',   '/path/to/ca-certificate.crt' );
```

---

### 7.4 WP Engine (Managed WordPress)

WP Engine is a premium managed WordPress host with built-in CDN, Redis, and SFTP/SSH access.

#### SSH Gateway Access

```bash
ssh -i ~/.ssh/wpengine_key \
    "$WPE_ENV_NAME@$WPE_ENV_NAME.ssh.wpengine.net" \
    -p 22
```

#### Deployment via WP Engine Git Push

WP Engine supports direct `git push` deployment:

```bash
# One-time setup
git remote add wpengine git@git.wpengine.com:production/$WPE_ENV_NAME.git

# Deploy
git push wpengine main
```

> Only files within the Git remote root are deployed. Ensure `mu-plugins/pearblog-engine/` and `theme/pearblog-theme/` are included in your repository (they are).

#### PHP Configuration

WP Engine pins PHP settings. To increase memory limit, use a `php.ini` override file:

Create `wp-content/mu-plugins/pearblog-memory.ini` (or use the WP Engine portal under **PHP Settings**):

```ini
memory_limit = 512M
max_execution_time = 300
```

#### WP-CLI on WP Engine

WP Engine provides WP-CLI via the SSH gateway:

```bash
ssh "$WPE_ENV_NAME@$WPE_ENV_NAME.ssh.wpengine.net" \
  "wp --path=/nas/content/live/$WPE_ENV_NAME/htdocs cache flush"
```

---

## 8. Post-Deployment Verification

Run these checks immediately after each deployment.

### PHP Syntax Check

```bash
find wp-content/mu-plugins/pearblog-engine/ \
     wp-content/themes/pearblog-theme/ \
     -name "*.php" -print0 \
  | xargs -0 php -l | grep -v "No syntax errors"
```

Expected: no output (all files pass).

### MU-Plugin Active

```bash
wp plugin list --fields=name,status | grep pearblog-engine
# Expected: pearblog-engine    must-use
```

### Health Endpoint

```bash
curl -s https://example.com/wp-json/pearblog/v1/health | python3 -m json.tool
```

Expected response (HTTP 200):

```json
{
  "status": "ok",
  "api_key_set": true,
  "circuit_open": false,
  "queue_size": 0,
  "last_run": "2026-04-11T04:00:00Z"
}
```

### WP-Cron Events

```bash
wp cron event list
```

Ensure `pearblog_pipeline_cron` appears in the list.

### Pipeline Smoke Test

```bash
# Add one topic and generate one article (safe test)
wp pearblog queue add --topic="Test article deployment verification"
wp pearblog generate
```

Check WordPress admin → Posts for the newly generated draft.

### Logs — No Fatal Errors

```bash
tail -50 /var/log/nginx/example.com-error.log
tail -50 /var/www/example.com/public/wp-content/debug.log
```

---

## 9. Rolling Back a Deployment

### Git Rollback

```bash
# Find the last good commit
git log --oneline mu-plugins/pearblog-engine/ | head -10

# On the server, reset to that commit
git --work-tree=/var/www/html/wp-content/mu-plugins/pearblog-engine \
    --git-dir=/var/www/example.com/repo \
    checkout <GOOD_COMMIT_SHA> -- mu-plugins/pearblog-engine/

wp cache flush
```

### GitHub Actions Rollback

1. Actions → *Deploy to WordPress Server*.
2. Click the last **successful** run.
3. **Re-run all jobs**.

### Manual Rollback via rsync Backup

Before each deploy, take a snapshot:

```bash
BACKUP_DIR="/var/backups/pearblog/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

cp -a /var/www/html/wp-content/mu-plugins/pearblog-engine \
      "$BACKUP_DIR/pearblog-engine"
cp -a /var/www/html/wp-content/themes/pearblog-theme \
      "$BACKUP_DIR/pearblog-theme"
```

Restore:

```bash
RESTORE_DIR="/var/backups/pearblog/20260410_120000"  # set to your snapshot

rsync -a --delete "$RESTORE_DIR/pearblog-engine/" \
  /var/www/html/wp-content/mu-plugins/pearblog-engine/

rsync -a --delete "$RESTORE_DIR/pearblog-theme/" \
  /var/www/html/wp-content/themes/pearblog-theme/

wp cache flush
```

---

## 10. Troubleshooting

### White Screen of Death (WSOD)

1. Enable debug logging in `wp-config.php`:
   ```php
   define( 'WP_DEBUG',         true );
   define( 'WP_DEBUG_LOG',     true );
   define( 'WP_DEBUG_DISPLAY', false );
   ```
2. Check `wp-content/debug.log` for the fatal error.
3. Common causes:
   - **PHP memory exhausted** → increase `memory_limit` to 512M.
   - **Incompatible PHP version** → ensure PHP 8.0+.
   - **Conflicting plugin** → temporarily deactivate other plugins.

### WP-Cron Not Running

```bash
# Verify cron is scheduled
wp cron event list

# Force-run overdue events
wp cron event run --due-now

# Check for DISABLE_WP_CRON in wp-config.php
grep DISABLE_WP_CRON /var/www/html/wp-config.php
```

If `DISABLE_WP_CRON` is `true`, add a real cron job as described in §7.1.

### OpenAI API Errors

```bash
# Check circuit breaker status
wp pearblog circuit status

# Reset circuit breaker
wp pearblog circuit reset

# Check cost accumulation
wp option get pearblog_ai_cost_cents
```

### Permission Errors

Correct permissions for a standard Apache/Nginx + PHP-FPM stack:

```bash
find /var/www/html/wp-content/mu-plugins/pearblog-engine -type d -exec chmod 755 {} \;
find /var/www/html/wp-content/mu-plugins/pearblog-engine -type f -exec chmod 644 {} \;
chown -R www-data:www-data /var/www/html/wp-content/mu-plugins/pearblog-engine
```

### Database Connection Errors

```bash
# Test connection credentials
wp db check

# Check MySQL is running
systemctl status mysql

# Test DSN manually
mysql -u pearblog_user -p pearblog_db -e "SELECT 1;"
```

### Queue Empty / No Content Generated

```bash
# Check queue size
wp pearblog stats

# Add topics manually
wp pearblog queue add --topic="Best travel destinations 2026"

# Run pipeline immediately (bypass cron)
wp pearblog generate
```

---

## 11. Security Hardening

### File Permissions

| Path | Owner | Mode |
|------|-------|------|
| `wp-config.php` | `root:www-data` | `640` |
| `wp-content/` | `www-data:www-data` | `755` |
| `wp-content/uploads/` | `www-data:www-data` | `755` |
| PHP files | `www-data:www-data` | `644` |
| Shell scripts | `deploy:deploy` | `750` |

```bash
chown root:www-data wp-config.php
chmod 640           wp-config.php
```

### Block PHP Execution in Uploads (Nginx)

```nginx
location ~* /wp-content/uploads/.*\.php$ {
    deny all;
    return 403;
}
```

### Disable XML-RPC (if not needed)

Add to `.htaccess` (Apache):

```apacheconf
<Files xmlrpc.php>
    Require all denied
</Files>
```

Or to Nginx:

```nginx
location = /xmlrpc.php {
    deny all;
}
```

### Protect `wp-login.php` with IP Allow-List

```nginx
location = /wp-login.php {
    allow 203.0.113.0/24;   # your office / home IP range
    deny  all;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

### Hide WordPress Version

Add to `functions.php` of the child theme, or apply via the PearBlog theme (already done in v5.2+):

```php
remove_action( 'wp_head', 'wp_generator' );
```

### Regularly Rotate API Keys

- OpenAI: rotate every 90 days via the OpenAI dashboard.
- GitHub Actions SSH key: rotate annually; revoke immediately if compromised.
- All secrets: stored only in GitHub Actions Secrets or environment variables, never in code.

---

## 12. Performance Tuning

### PHP OPcache (`/etc/php/8.2/fpm/conf.d/10-opcache.ini`)

```ini
opcache.enable                 = 1
opcache.memory_consumption     = 256
opcache.interned_strings_buffer= 16
opcache.max_accelerated_files  = 10000
opcache.revalidate_freq        = 60
opcache.fast_shutdown          = 1
```

### MySQL Tuning (`/etc/mysql/mysql.conf.d/mysqld.cnf`)

```ini
[mysqld]
innodb_buffer_pool_size   = 512M   # set to ~70% of available RAM
innodb_log_file_size      = 128M
query_cache_type          = 0      # disable legacy query cache
slow_query_log            = 1
slow_query_log_file       = /var/log/mysql/slow.log
long_query_time           = 2
```

### Nginx FastCGI Cache

```nginx
# In nginx.conf (http block)
fastcgi_cache_path /var/cache/nginx/wordpress
    levels=1:2 keys_zone=WORDPRESS:100m inactive=60m;

# In server block
set $skip_cache 0;

# Skip cache for logged-in users and POST requests
if ($request_method = POST)       { set $skip_cache 1; }
if ($query_string != "")          { set $skip_cache 1; }
if ($http_cookie ~* "wordpress_logged_in") { set $skip_cache 1; }

location ~ \.php$ {
    fastcgi_cache WORDPRESS;
    fastcgi_cache_valid 200 60m;
    fastcgi_cache_bypass  $skip_cache;
    fastcgi_no_cache      $skip_cache;
    add_header X-FastCGI-Cache $upstream_cache_status;
    # ... fastcgi_pass etc.
}
```

### WordPress Object Cache (Redis)

```bash
# Install Redis on Ubuntu
apt install redis-server php8.2-redis
systemctl enable --now redis-server

# Install WP Redis object cache drop-in
wp plugin install wp-redis --activate
wp redis enable
```

Verify:

```bash
wp redis status
# Expected: Status: Connected
```

---

## Related Documentation

| Document | Purpose |
|----------|---------|
| [SETUP.md](SETUP.md) | GitHub Secrets & CI/CD quick-start |
| [DATABASE-MIGRATIONS.md](DATABASE-MIGRATIONS.md) | Schema upgrades and rollback SQL |
| [DISASTER-RECOVERY.md](DISASTER-RECOVERY.md) | Backup, restore, and failover procedures |
| [PRODUCTION-CHECKLIST.md](PRODUCTION-CHECKLIST.md) | Pre-launch, launch-day, and operations checklists |
| [PRODUCTION-ANALYSIS-FULL.md](PRODUCTION-ANALYSIS-FULL.md) | Full production operations manual |
| [ENTERPRISE-AUTOPILOT-TASKLIST.md](ENTERPRISE-AUTOPILOT-TASKLIST.md) | 26-task autopilot execution plan |

---

*PearBlog Engine v6.0.0 — Enterprise-ready autonomous content system*

# ⚡ Quick Start Guide: peartree.pro (WordPress Multisite)

**Domain:** peartree.pro
**Network Type:** WordPress Multisite (Subdomain)
**Industry:** Multi-niche content network
**Server:** TBD - Update with your server IP
**Goal:** Launch autonomous multi-site content network in <30 minutes

---

## 🚀 One-Line Deployment

```bash
ssh root@YOUR_SERVER_IP
curl -sL https://raw.githubusercontent.com/AndyPearman89/PearBlog-Engine-/main/scripts/deploy-peartree-pro.sh | bash
```

**⚠️ Important:** Before running, update `SERVER_IP` in the script file.

---

## 📋 What You Need

### Before Starting

1. **Server with root access**
   - Ubuntu 20.04+ or Debian 11+
   - Minimum 2GB RAM, 20GB disk
   - PHP 8.1+, MySQL/MariaDB, Apache/Nginx

2. **Domain configured (wildcard DNS required)**
   - DNS A record: `peartree.pro` → YOUR_SERVER_IP
   - DNS A record: `*.peartree.pro` → YOUR_SERVER_IP *(wildcard for all subsites)*

3. **OpenAI API Key**
   - Get from: https://platform.openai.com/api-keys
   - Format: `sk-proj-...`

4. **Email address**
   - For wildcard SSL certificate (Let's Encrypt DNS challenge)
   - For WordPress admin account

5. **DNS provider access**
   - Needed during SSL setup to add a `_acme-challenge` TXT record for the wildcard certificate

---

## 🎯 Deployment Steps

### Step 1: Configure Wildcard DNS (before deployment)

In your DNS provider's control panel, add:

| Record | Host | Value |
|--------|------|-------|
| A | `peartree.pro` | YOUR_SERVER_IP |
| A | `*.peartree.pro` | YOUR_SERVER_IP |

> ⏳ Allow up to 24–48 hours for DNS propagation before the SSL step.

### Step 2: Connect to Server (1 min)

```bash
ssh root@YOUR_SERVER_IP
```

### Step 3: Run Deployment Script (~25-30 min)

```bash
curl -sL https://raw.githubusercontent.com/AndyPearman89/PearBlog-Engine-/main/scripts/deploy-peartree-pro.sh | bash
```

The script will prompt you for:
- MySQL root password
- Admin email
- Admin password
- OpenAI API key
- SSL certificate email

The script automatically:
- Installs WordPress
- Converts to Multisite (subdomain mode)
- Configures web server for wildcard subdomains
- Network-activates PearBlog Engine
- Creates 3 initial subsites (blog, news, reviews)
- Obtains wildcard SSL certificate
- Enables autonomous mode network-wide

### Step 4: Verify (2 min)

```bash
# Check main site:
curl -I https://peartree.pro
# Should return: HTTP/2 200

# Check health endpoint:
curl https://peartree.pro/wp-json/pearblog/v1/health
# Should return: {"status":"ok",...}

# List all network sites:
wp site list --allow-root

# Check autopilot:
wp pearblog autopilot status --allow-root
```

---

## ✅ Post-Deployment

### Access Your Network

- **Network Admin:** https://peartree.pro/wp-admin/network/
- **Main Site:** https://peartree.pro
- **Blog subsite:** https://blog.peartree.pro
- **News subsite:** https://news.peartree.pro
- **Reviews subsite:** https://reviews.peartree.pro
- **Health API:** https://peartree.pro/wp-json/pearblog/v1/health

### Default Configuration

```
Network Type: Subdomain Multisite
Initial Sites: 4 (main + blog + news + reviews)
Industry: Multi-niche content network
Publish Rate: 0.5/hour per site (1 article every 2 hours)
Language: en (English)
AI Images: Enabled (DALL-E 3)
Autonomous Mode: Enabled (network-wide)
```

### Initial Topics Added (30 topics in English)

The deployment automatically adds 30 English-language topics to the main site queue:

- Content marketing strategies for bloggers in 2026
- How to start a successful blog from scratch
- SEO fundamentals: ranking your content in Google
- AI writing tools: best picks for content creators
- WordPress tips and tricks for site owners
- Digital publishing trends to watch this year
- How to monetize a blog with affiliate marketing
- Building a niche site that earns passive income
- Affiliate marketing guide for beginners
- Content strategy framework for multi-site networks
- Keyword research step-by-step guide
- Email marketing for bloggers: grow your list fast
- Social media strategies that drive blog traffic
- How to use DALL-E 3 for blog images
- Long-form content vs short-form: what converts better
- On-page SEO checklist for WordPress posts
- Link building tactics that still work in 2026
- How to write pillar content for your niche
- WordPress multisite: managing a content network
- Programmatic SEO: scale content with AI
- How to choose a profitable niche for your blog
- Technical SEO basics every blogger should know
- Growing a YouTube channel to support your blog
- Pinterest marketing for bloggers and publishers
- How to repurpose blog content across channels
- Building topical authority in your niche
- How to write high-converting product reviews
- Google Search Console tips for content publishers
- Using structured data to boost click-through rates
- Building a media brand with WordPress Multisite

---

## 🔐 GitHub Secrets Setup

For CI/CD deployment, add these secrets to your GitHub repository (prefix: `PEARTREE_`):

Go to: **Settings** → **Secrets and variables** → **Actions**

### Required Secrets

- `PEARTREE_SSH_HOST` = YOUR_SERVER_IP
- `PEARTREE_SSH_USER` = root
- `PEARTREE_SSH_PRIVATE_KEY` = [Your SSH private key]
- `PEARTREE_WP_PATH` = /var/www/peartree.pro
- `PEARTREE_ROOT_PASSWORD` = [MySQL root password]
- `PEARTREE_OPENAI_API_KEY` = sk-proj-...

### Generate SSH Key (if needed)

```bash
# On your local machine:
ssh-keygen -t ed25519 -C "deploy-peartree" -f ~/.ssh/peartree_deploy

# Copy public key to server:
ssh-copy-id -i ~/.ssh/peartree_deploy.pub root@YOUR_SERVER_IP

# Display private key (add to GitHub Secret):
cat ~/.ssh/peartree_deploy
```

---

## 📊 Monitoring & Management

### Quick Commands

```bash
# List all network sites:
wp site list --allow-root

# Generate article on main site:
ssh root@YOUR_SERVER_IP "cd /var/www/peartree.pro && wp pearblog generate --allow-root"

# Generate on specific subsite:
ssh root@YOUR_SERVER_IP "cd /var/www/peartree.pro && wp --url='blog.peartree.pro' pearblog generate --allow-root"

# Check statistics:
ssh root@YOUR_SERVER_IP "cd /var/www/peartree.pro && wp pearblog stats --allow-root"

# Network-wide stats:
wp site list --field=url --allow-root | while read url; do
  echo "=== $url ==="; wp --url="$url" pearblog stats --allow-root
done

# Add new topic to main site:
ssh root@YOUR_SERVER_IP "cd /var/www/peartree.pro && wp pearblog queue add 'Your topic' --allow-root"

# Check logs:
ssh root@YOUR_SERVER_IP "tail -f /var/www/peartree.pro/wp-content/pearblog-engine.log"
```

### Autopilot Commands

```bash
# Status:
wp pearblog autopilot status --allow-root

# Start:
wp pearblog autopilot start --allow-root

# Pause:
wp pearblog autopilot pause --allow-root

# Resume:
wp pearblog autopilot resume --allow-root
```

### Subsite Management

```bash
# Create a new subsite:
wp site create --slug="mysite" --title="My Site" --email="admin@peartree.pro" --allow-root

# Configure PearBlog on a specific subsite:
wp --url="blog.peartree.pro" option update pearblog_industry "blogging tips" --allow-root
wp --url="news.peartree.pro" option update pearblog_industry "digital news and media" --allow-root
wp --url="reviews.peartree.pro" option update pearblog_industry "product reviews" --allow-root

# Enable autonomous mode on a specific subsite:
wp --url="blog.peartree.pro" option update pearblog_autonomous_mode "1" --allow-root
```

---

## 💰 Cost Estimates

### OpenAI API Costs

**Per Article:**
- Content (GPT-4o-mini): ~$0.05
- Image (DALL-E 3): ~$0.04
- Total: ~$0.09 per article

**Monthly per site (0.5/hour rate):**
- 12 articles/day × 30 days = 360 articles/month
- 360 × $0.09 = **~$32/month per site**

**For all 4 sites:**
- ~$128/month total

**Set OpenAI usage limits:**
- https://platform.openai.com/account/limits
- Recommended: $150/month hard limit for the full network

---

## 🔧 Troubleshooting

### Subsites Not Loading?

```bash
# Check DNS wildcard:
dig blog.peartree.pro +short
# Should return: YOUR_SERVER_IP

# Check web server wildcard config:
# Apache: ServerAlias *.peartree.pro must be in VirtualHost
# Nginx: server_name peartree.pro *.peartree.pro; must be set

# Flush permalinks:
wp rewrite flush --allow-root
```

### Articles Not Generating?

```bash
# Check autonomous mode:
wp option get pearblog_autonomous_mode --allow-root
# Should return: 1

# Check cron:
wp cron event list --allow-root | grep pearblog

# Generate manually:
wp pearblog generate --allow-root
```

### Wildcard SSL Not Working?

```bash
# Check certificate covers wildcard:
certbot certificates
# Should show: Domains: peartree.pro *.peartree.pro

# Test with openssl:
echo | openssl s_client -connect blog.peartree.pro:443 2>/dev/null | openssl x509 -noout -subject
```

### API Errors?

```bash
# Check circuit breaker:
wp pearblog stats --allow-root

# Reset if needed:
wp pearblog circuit reset --allow-root

# Verify API key:
wp option get pearblog_openai_api_key --allow-root
```

---

## 📚 Next Steps

1. **Configure Per-Site Niches**
   - Give each subsite a specific industry/niche in PearBlog settings
   - Add 20-30 targeted topics per subsite queue

2. **Add More Subsites**
   - Use `wp site create` to add more subsites to the network
   - Each subsite runs its own autonomous content pipeline

3. **Configure Monetization**
   - Admin → PearBlog Engine → Monetization
   - Add AdSense, affiliate links per site

4. **Setup Monitoring**
   - Configure Slack/Discord webhooks for alerts
   - Set up uptime monitoring for all subdomain URLs

5. **SEO Optimization**
   - Install Yoast SEO or RankMath network-wide
   - Submit each subsite sitemap to Google Search Console

6. **Backup Setup**
   - Configure automated database backups
   - Use UpdraftPlus or similar backup plugin (network-wide)

---

## 🆘 Need Help?

- **Full Documentation:** [DEPLOYMENT-peartree-pro.md](DEPLOYMENT-peartree-pro.md)
- **GitHub Secrets:** [GITHUB-SECRETS-GUIDE.md](GITHUB-SECRETS-GUIDE.md)
- **Troubleshooting:** [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
- **GitHub Issues:** https://github.com/AndyPearman89/PearBlog-Engine-/issues

---

**Last Updated:** 2026-05-03
**Deployment Time:** ~30 minutes
**Expected First Article:** Within 2 hours of deployment
**Monthly Cost:** ~$32/site (OpenAI API only)

# ⚡ Quick Start Guide: www.zalew.mucharski.pl

**Domain:** www.zalew.mucharski.pl
**Industry:** Water sports and fishing – Zalew Mucharski specifically (Sporty wodne i wędkarstwo na Zalewie Mucharskim)
**Server:** TBD - Update with your server IP
**Goal:** Launch autonomous Zalew Mucharski fishing and water sports content site in <20 minutes

---

## 🚀 One-Line Deployment

```bash
ssh root@YOUR_SERVER_IP
curl -sL https://raw.githubusercontent.com/AndyPearman89/PearBlog-Engine-/main/scripts/deploy-zalew-mucharski-pl.sh | bash
```

**⚠️ Important:** Before running, update `SERVER_IP` in the script file.

---

## 📋 What You Need

### Before Starting

1. **Server with root access**
   - Ubuntu 20.04+ or Debian 11+
   - Minimum 2GB RAM, 20GB disk
   - PHP 8.1+, MySQL/MariaDB, Apache/Nginx

2. **Domain configured**
   - DNS A record: `www.zalew.mucharski.pl` → YOUR_SERVER_IP

3. **OpenAI API Key**
   - Get from: https://platform.openai.com/api-keys
   - Format: `sk-proj-...`

4. **Email address**
   - For SSL certificate (Let's Encrypt)
   - For WordPress admin account

---

## 🎯 Deployment Steps

### Step 1: Connect to Server (1 min)

```bash
ssh root@YOUR_SERVER_IP
```

### Step 2: Run Deployment Script (15-20 min)

```bash
curl -sL https://raw.githubusercontent.com/AndyPearman89/PearBlog-Engine-/main/scripts/deploy-zalew-mucharski-pl.sh | bash
```

The script will prompt you for:
- MySQL root password
- Admin email
- Admin password
- OpenAI API key
- SSL certificate email

### Step 3: Verify (2 min)

```bash
# Check site is live:
curl -I https://www.zalew.mucharski.pl
# Should return: HTTP/2 200

# Check health endpoint:
curl https://www.zalew.mucharski.pl/wp-json/pearblog/v1/health
# Should return: {"status":"ok",...}

# Check autopilot:
wp pearblog autopilot status --allow-root
```

---

## ✅ Post-Deployment

### Access Your Site

- **Frontend:** https://www.zalew.mucharski.pl
- **Admin Panel:** https://www.zalew.mucharski.pl/wp-admin
- **Health API:** https://www.zalew.mucharski.pl/wp-json/pearblog/v1/health

### Default Configuration

```
Industry: sporty wodne i wędkarstwo na Zalewie Mucharskim
Tone: praktyczny, lokalny, dla miłośników wędkarstwa i sportów wodnych na Zalewie Mucharskim
Publish Rate: 0.5/hour (1 article every 2 hours = 12/day)
Language: pl (Polish)
AI Images: Enabled (DALL-E 3)
Autonomous Mode: Enabled
```

### Initial Topics Added (30 topics)

The deployment automatically adds 30 Zalew Mucharski fishing and water sports topics to the queue:
- Wędkarstwo na Zalewie Mucharskim – techniki i porady
- Kajaking, windsurfing i sporty wodne na Zalewie
- Noclegi, restauracje i atrakcje wokół Zalewu
- Zasady i przepisy wędkarskie na Zalewie Mucharskim
- Historia i infrastruktura Zalewu Mucharskiego

---

## 🔐 GitHub Secrets Setup

For CI/CD deployment, add these secrets to your GitHub repository:

Go to: **Settings** → **Secrets and variables** → **Actions**

### Required Secrets

- `ZALEW_MUCHARSKI_SSH_HOST` = YOUR_SERVER_IP
- `ZALEW_MUCHARSKI_SSH_USER` = root
- `ZALEW_MUCHARSKI_SSH_PRIVATE_KEY` = [Your SSH private key]
- `ZALEW_MUCHARSKI_WP_PATH` = /var/www/www.zalew.mucharski.pl
- `ZALEW_MUCHARSKI_ROOT_PASSWORD` = [MySQL root password]
- `ZALEW_MUCHARSKI_OPENAI_API_KEY` = sk-proj-...

### Generate SSH Key (if needed)

```bash
# On your local machine:
ssh-keygen -t ed25519 -C "deploy-zalew-mucharski" -f ~/.ssh/zalew_mucharski_deploy

# Copy public key to server:
ssh-copy-id -i ~/.ssh/zalew_mucharski_deploy.pub root@YOUR_SERVER_IP

# Display private key (add to GitHub Secret):
cat ~/.ssh/zalew_mucharski_deploy
```

---

## 📊 Monitoring & Management

### Quick Commands

```bash
# Generate article now:
ssh root@YOUR_SERVER_IP "cd /var/www/www.zalew.mucharski.pl && wp pearblog generate --allow-root"

# Check statistics:
ssh root@YOUR_SERVER_IP "cd /var/www/www.zalew.mucharski.pl && wp pearblog stats --allow-root"

# View queue:
ssh root@YOUR_SERVER_IP "cd /var/www/www.zalew.mucharski.pl && wp pearblog queue list --allow-root"

# Add new topic:
ssh root@YOUR_SERVER_IP "cd /var/www/www.zalew.mucharski.pl && wp pearblog queue add 'Twój temat' --allow-root"

# Check logs:
ssh root@YOUR_SERVER_IP "tail -f /var/www/www.zalew.mucharski.pl/wp-content/pearblog-engine.log"
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

---

## 💰 Cost Estimates

### OpenAI API Costs

**Per Article:**
- Content (GPT-4o-mini): ~$0.05
- Image (DALL-E 3): ~$0.04
- Total: ~$0.09 per article

**Monthly (0.5/hour rate):**
- 12 articles/day × 30 days = 360 articles/month
- 360 × $0.09 = **~$32/month**

**Set OpenAI usage limits:**
- https://platform.openai.com/account/limits
- Recommended: $50/month hard limit

---

## 🔧 Troubleshooting

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

### API Errors?

```bash
# Check circuit breaker:
wp pearblog stats --allow-root

# Reset if needed:
wp pearblog circuit reset --allow-root

# Verify API key:
wp option get pearblog_openai_api_key --allow-root
```

### Permission Issues?

```bash
# Fix permissions:
chown -R www-data:www-data /var/www/www.zalew.mucharski.pl/wp-content
chmod -R 755 /var/www/www.zalew.mucharski.pl/wp-content
```

---

## 📚 Next Steps

1. **Add More Topics** (optional)
   - Add 20-50 more topics for better content variety
   - Focus on: specific fish species at Zalew Mucharski, local events, seasonal water sports

2. **Configure Monetization**
   - Admin → PearBlog Engine → Monetization
   - Add AdSense, affiliate links

3. **Setup Monitoring**
   - Configure Slack/Discord webhooks for alerts
   - Set up uptime monitoring (UptimeRobot, Pingdom)

4. **SEO Optimization**
   - Install Yoast SEO or RankMath (optional)
   - Submit sitemap to Google Search Console

5. **Backup Setup**
   - Configure automated database backups
   - Use UpdraftPlus or similar backup plugin

---

## 🆘 Need Help?

- **Documentation:** [DEPLOYMENT-zalew-mucharski-pl.md](DEPLOYMENT-zalew-mucharski-pl.md)
- **GitHub Secrets:** [GITHUB-SECRETS-GUIDE.md](GITHUB-SECRETS-GUIDE.md)
- **Troubleshooting:** [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
- **GitHub Issues:** https://github.com/AndyPearman89/PearBlog-Engine-/issues

---

**Last Updated:** 2026-05-03
**Deployment Time:** ~20 minutes
**Expected First Article:** Within 2 hours of deployment
**Monthly Cost:** ~$32 (OpenAI API only)

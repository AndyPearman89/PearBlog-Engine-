# 🚀 Quick Deployment Guide: poradnik.pro

**Domain:** poradnik.pro
**Server:** root@204.48.27.118
**Version:** PearBlog Engine v8.0

---

## 🔥 One-Line Deploy

```bash
ssh root@204.48.27.118
curl -sL https://raw.githubusercontent.com/AndyPearman89/PearBlog-Engine-/main/scripts/deploy-poradnik-pro.sh | bash
```

This automated script will:
- ✅ Check prerequisites (PHP 8.1+, MySQL, Web Server)
- ✅ Install required PHP extensions
- ✅ Configure PHP settings (512MB memory, 300s timeout)
- ✅ Install WP-CLI
- ✅ Create database: `poradnik_pro`
- ✅ Download & install WordPress
- ✅ Deploy PearBlog Engine (MU-plugin + theme)
- ✅ Configure PearBlog settings
- ✅ Add 10 initial topics
- ✅ Setup SSL certificate (Let's Encrypt)
- ✅ Test deployment
- ✅ Enable autonomous mode

**Total time:** ~15 minutes

---

## 📋 Manual Deployment Steps

If you prefer manual deployment, follow [DEPLOYMENT-poradnik-pro.md](DEPLOYMENT-poradnik-pro.md)

---

## ✅ Post-Deployment Checklist

After deployment completes, verify:

```bash
# 1. Site is accessible
curl -I https://poradnik.pro
# Should return: HTTP/2 200

# 2. Health endpoint works
curl https://poradnik.pro/wp-json/pearblog/v1/health
# Should return: {"status":"ok","timestamp":...}

# 3. First article generated
ssh root@204.48.27.118
cd /var/www/poradnik.pro
wp post list --post_type=post --allow-root
# Should show at least 1 post

# 4. Pipeline scheduled
wp cron event list --allow-root | grep pearblog
# Should show pearblog_content_pipeline scheduled

# 5. Autopilot running
wp pearblog autopilot status --allow-root
# Should show active status
```

---

## 🔑 Essential Credentials

You'll need during deployment:

1. **MySQL root password** (for database creation) - Add as `ROOT_PASSWORD` secret
2. **OpenAI API key** (sk-proj-...) - Add as `OPENAI_API_KEY` secret
3. **WordPress admin password** (your choice)
4. **Email address** (for SSL certificate)

### GitHub Secrets Setup

For automated deployment via GitHub Actions, configure these secrets:
- `SSH_HOST` = 204.48.27.118
- `SSH_USER` = root
- `SSH_PRIVATE_KEY` = [Your SSH private key]
- `WP_PATH` = /var/www/poradnik.pro
- `ROOT_PASSWORD` = [MySQL root password]
- `OPENAI_API_KEY` = sk-proj-...

See [GITHUB-SECRETS-GUIDE.md](GITHUB-SECRETS-GUIDE.md) for complete setup instructions.

---

## 🛠️ Common Commands

```bash
# SSH to server
ssh root@204.48.27.118

# Navigate to WordPress
cd /var/www/poradnik.pro

# Check pipeline status
wp pearblog stats --allow-root

# Add topic to queue
wp pearblog queue add "Your topic here" --allow-root

# Generate article manually
wp pearblog generate --allow-root

# View logs
tail -f /var/www/poradnik.pro/wp-content/pearblog-engine.log

# Reset circuit breaker
wp pearblog circuit reset --allow-root

# Check autopilot status
wp pearblog autopilot status --allow-root
```

---

## 📊 Expected Results

After successful deployment:

- **Site URL:** https://poradnik.pro
- **Admin Panel:** https://poradnik.pro/wp-admin
- **Health API:** https://poradnik.pro/wp-json/pearblog/v1/health
- **Articles:** 1 article generated immediately, then 1/hour automatically
- **Cost:** ~$0.08 per article = ~$58/month for 720 articles
- **Language:** Polish (pl)
- **Industry:** Poradniki praktyczne

---

## 🔗 Documentation

- **Full Deployment Guide:** [DEPLOYMENT-poradnik-pro.md](DEPLOYMENT-poradnik-pro.md)
- **Troubleshooting:** [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
- **General Deployment:** [DEPLOYMENT.md](DEPLOYMENT.md)
- **Documentation Index:** [DOCUMENTATION-INDEX.md](DOCUMENTATION-INDEX.md)

---

## 🆘 Support

If deployment fails:

1. Check logs: `/var/log/apache2/error.log` or `/var/log/nginx/error.log`
2. Review PearBlog logs: `/var/www/poradnik.pro/wp-content/pearblog-engine.log`
3. Verify prerequisites: `php -v`, `mysql --version`, `wp --info`
4. See: [TROUBLESHOOTING.md](TROUBLESHOOTING.md)

---

**Last Updated:** 2026-05-02
**Status:** Ready for deployment 🚀

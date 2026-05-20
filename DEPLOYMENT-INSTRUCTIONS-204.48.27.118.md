# 🚀 Deployment Instructions for 204.48.27.118 (poradnik.pro)

**Server IP:** 204.48.27.118
**Domain:** poradnik.pro
**Date:** 2026-05-20
**Branch:** claude/install-204-48-27-118

---

## 📋 Deployment Options

There are **3 ways** to deploy to the production server at 204.48.27.118:

### Option 1: GitHub Actions Workflow (Recommended)

#### 1A. Standard Plugin & Theme Deployment

This workflow deploys the mu-plugin and theme files via rsync:

```bash
# Trigger via GitHub UI:
# 1. Go to Actions tab
# 2. Select "Deploy to WordPress Server" workflow
# 3. Click "Run workflow"
# 4. Select branch: main (or current branch)
# 5. Check "Force full deploy" if needed
# 6. Click "Run workflow"
```

**Required GitHub Secrets:**
- `SSH_HOST` = `204.48.27.118`
- `SSH_USER` = `root`
- `WP_PATH` = `/var/www/poradnik.pro`
- `SSH_PRIVATE_KEY` (preferred) or `SSH_PASSWORD`
- `SSH_PORT` (optional, default: 22)

#### 1B. PT24 Local Services Deployment

This workflow deploys PT24 local services and configures OpenAI API key:

```bash
# Trigger via GitHub UI:
# 1. Go to Actions tab
# 2. Select "Deploy PT24 With GitHub Secrets" workflow
# 3. Click "Run workflow"
# 4. Select branch: main (or current branch)
# 5. Check "Run base deploy" if you want to run deploy-pt24-pro.sh first
# 6. Click "Run workflow"
```

**Additional Required Secret:**
- `OPENAI_API_KEY` = Your OpenAI API key

**What it deploys:**
1. Clones/updates repository on server at `/root/PearBlog-Engine-`
2. Optionally runs `scripts/deploy-pt24-pro.sh` (base deployment)
3. Installs `.env` file with OPENAI_API_KEY at `/var/www/pt24.pro/.env`
4. Runs `scripts/deploy-pt24-local-services.sh`
5. Verifies PT24 is online

---

### Option 2: Direct SSH Deployment (Manual)

#### Step 1: SSH to Server
```bash
ssh root@204.48.27.118
```

#### Step 2: Run Automated Deployment Script
```bash
# Option A: Run full poradnik.pro deployment
curl -sL https://raw.githubusercontent.com/AndyPearman89/PearBlog-Engine-/main/scripts/deploy-poradnik-pro.sh | bash

# Option B: Clone repo and run script locally
cd /root
git clone https://github.com/AndyPearman89/PearBlog-Engine-.git
cd PearBlog-Engine-
bash ./scripts/deploy-poradnik-pro.sh
```

#### Step 3: For PT24 Local Services
```bash
cd /root/PearBlog-Engine-
bash ./scripts/deploy-pt24-local-services.sh
```

---

### Option 3: Manual File Copy (Advanced)

If you need granular control:

```bash
# SSH to server
ssh root@204.48.27.118

# Clone/update repository
cd /root
git clone https://github.com/AndyPearman89/PearBlog-Engine-.git || (cd PearBlog-Engine- && git pull)

# Copy mu-plugin
rsync -av /root/PearBlog-Engine-/mu-plugins/pearblog-engine/ \
  /var/www/poradnik.pro/wp-content/mu-plugins/pearblog-engine/

# Copy theme
rsync -av /root/PearBlog-Engine-/theme/pearblog-theme/ \
  /var/www/poradnik.pro/wp-content/themes/pearblog-theme/

# Set proper permissions
chown -R www-data:www-data /var/www/poradnik.pro/wp-content/

# Flush WordPress cache
cd /var/www/poradnik.pro
wp cache flush --allow-root

# Verify deployment
wp eval 'echo class_exists("PearBlogEngine\\Admin\\AdminPageV8Enterprise") ? "✓ Enterprise V8 loaded\n" : "✗ Enterprise V8 missing\n";' --allow-root
```

---

## ✅ Post-Deployment Verification

### 1. Check Website Status
```bash
curl -I https://poradnik.pro
# Expected: HTTP/2 200
```

### 2. Verify Plugin Status
```bash
ssh root@204.48.27.118 "cd /var/www/poradnik.pro && wp plugin list --allow-root"
```

### 3. Check PearBlog Engine Status
```bash
ssh root@204.48.27.118 "cd /var/www/poradnik.pro && wp pearblog stats --allow-root"
```

### 4. Verify Enterprise V8 Admin
```bash
ssh root@204.48.27.118 "cd /var/www/poradnik.pro && wp eval 'echo class_exists(\"PearBlogEngine\\\\Admin\\\\AdminPageV8Enterprise\") ? \"✓ CLASS OK\\n\" : \"✗ CLASS MISSING\\n\";' --allow-root"
```

### 5. Test Admin Access
Visit: `https://poradnik.pro/wp-admin/admin.php?page=pearblog-enterprise-v8`

### 6. Check Recent Commits Deployed
```bash
ssh root@204.48.27.118 "cd /root/PearBlog-Engine- && git log --oneline -5"
```

Expected to see:
- `a528b28` Merge pull request #75
- `4e44c27` Fix bash syntax error in wp eval commands
- `be2d508` ci: add PT24 deploy workflow

---

## 🔐 Required GitHub Secrets Setup

If GitHub secrets are not configured, add them:

1. Go to: `https://github.com/AndyPearman89/PearBlog-Engine-/settings/secrets/actions`
2. Click "New repository secret"
3. Add the following secrets:

| Secret Name | Value | Notes |
|------------|-------|-------|
| `SSH_HOST` | `204.48.27.118` | Server IP |
| `SSH_USER` | `root` | SSH username |
| `SSH_PRIVATE_KEY` | `[Your SSH private key]` | Full private key including headers |
| `WP_PATH` | `/var/www/poradnik.pro` | WordPress installation path |
| `OPENAI_API_KEY` | `sk-...` | For PT24 AI features (optional) |
| `SSH_PORT` | `22` | Optional, defaults to 22 |

### Generate SSH Key (if needed)
```bash
ssh-keygen -t ed25519 -C "deploy-poradnik-pro" -f ~/.ssh/poradnik_deploy
ssh-copy-id -i ~/.ssh/poradnik_deploy.pub root@204.48.27.118
cat ~/.ssh/poradnik_deploy  # Copy this to SSH_PRIVATE_KEY secret
```

---

## 🛠️ Troubleshooting

### Workflow fails with "Missing required secrets"
- Verify all required secrets are set in GitHub repository settings
- Check secret names match exactly (case-sensitive)

### SSH connection timeout
- Verify server IP: `ping 204.48.27.118`
- Check DNS: `dig poradnik.pro +short` (should return 204.48.27.118)
- Test SSH manually: `ssh root@204.48.27.118`

### Permission denied errors
- Verify SSH key is correct and has proper permissions
- Check that public key is in `~/.ssh/authorized_keys` on server

### Plugin/theme not updating
- Clear WordPress cache: `wp cache flush --allow-root`
- Check file permissions: `ls -la /var/www/poradnik.pro/wp-content/`
- Verify rsync completed successfully in workflow logs

---

## 📊 Deployment History

### Recent Commits to Deploy
```
a528b28 - Merge pull request #75 from AndyPearman89/claude/update-for-may-2026
4e44c27 - Fix bash syntax error in wp eval commands
be2d508 - ci: add PT24 deploy workflow using GitHub secrets
```

### Key Changes in Recent Commits
- ✅ Fixed bash syntax errors in wp eval commands
- ✅ Added PT24 deploy workflow using GitHub secrets
- ✅ Updated for May 2026

---

## 🎯 Recommended Deployment Path

**For this deployment (204.48.27.118), I recommend:**

1. **Option 1A** (GitHub Actions - Standard Deployment) if:
   - You have GitHub secrets configured
   - You want automated, tracked deployments
   - You prefer CI/CD approach

2. **Option 2** (Direct SSH) if:
   - Quick manual deployment needed
   - Testing changes before automation
   - GitHub Actions not available

---

## 📞 Next Steps

1. **Choose your deployment method** from the options above
2. **Run the deployment** using your chosen method
3. **Verify deployment** using the post-deployment checks
4. **Monitor** the site for any issues: `https://poradnik.pro`

---

**Status:** ✅ Ready for Deployment
**Target Server:** 204.48.27.118 (poradnik.pro)
**Branch:** claude/install-204-48-27-118
**Date:** 2026-05-20

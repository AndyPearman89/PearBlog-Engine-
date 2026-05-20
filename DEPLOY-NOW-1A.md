# 🚀 Deploy Now: Option 1A - GitHub Actions Standard Deployment

**Target:** 204.48.27.118 (poradnik.pro)
**Method:** GitHub Actions Workflow
**Status:** Ready to Deploy ✅

---

## Quick Start (Option 1A)

### Step 1: Trigger the Workflow

**Go to:** https://github.com/AndyPearman89/PearBlog-Engine-/actions/workflows/deploy.yml

Or manually:
1. Navigate to the **Actions** tab in GitHub
2. Select **"Deploy to WordPress Server"** workflow (left sidebar)
3. Click the **"Run workflow"** button (right side)
4. Configure the deployment:
   - **Branch:** Select `main` or `claude/install-204-48-27-118`
   - **Force full deploy:** Check this box for a complete deployment
5. Click **"Run workflow"** (green button)

---

## ✅ Pre-Deployment Checklist

Before triggering the workflow, verify these GitHub Secrets are configured:

### Required Secrets
Go to: **Settings → Secrets and variables → Actions → Repository secrets**

| Secret Name | Expected Value | Status |
|-------------|----------------|--------|
| `SSH_HOST` | `204.48.27.118` | ⚠️ Verify |
| `SSH_USER` | `root` | ⚠️ Verify |
| `WP_PATH` | `/var/www/poradnik.pro` | ⚠️ Verify |
| `SSH_PRIVATE_KEY` | [Your SSH key] | ⚠️ Verify |

### Optional Secrets
| Secret Name | Expected Value | Notes |
|-------------|----------------|-------|
| `SSH_PORT` | `22` | Optional, defaults to 22 |
| `SSH_PASSWORD` | [Password] | Alternative to SSH_PRIVATE_KEY |

---

## 🔍 What This Deployment Does

The workflow will:

1. ✅ **Connect to server** via SSH using your configured secrets
2. ✅ **Deploy mu-plugin** (`pearblog-engine`) via rsync
   - Source: `mu-plugins/pearblog-engine/`
   - Target: `/var/www/poradnik.pro/wp-content/mu-plugins/pearblog-engine/`
3. ✅ **Deploy theme** (`pearblog-theme`) via rsync
   - Source: `theme/pearblog-theme/`
   - Target: `/var/www/poradnik.pro/wp-content/themes/pearblog-theme/`
4. ✅ **Flush WordPress cache** on the server
5. ✅ **Run smoke test** to verify Enterprise V8 admin is loaded
6. ✅ **Display deployment summary**

---

## 📊 Expected Output

When the workflow runs successfully, you'll see:

```
✅ Deploy complete
   Plugin:  wp-content/mu-plugins/pearblog-engine/
   Theme:   wp-content/themes/pearblog-theme/
   Server:  204.48.27.118
```

---

## 🔧 If GitHub Secrets Are Missing

### Quick Setup Commands

```bash
# 1. Generate SSH key (if you don't have one)
ssh-keygen -t ed25519 -C "deploy-poradnik-pro" -f ~/.ssh/poradnik_deploy

# 2. Copy public key to server
ssh-copy-id -i ~/.ssh/poradnik_deploy.pub root@204.48.27.118

# 3. Display private key (copy this to GitHub Secret: SSH_PRIVATE_KEY)
cat ~/.ssh/poradnik_deploy
```

### Add Secrets to GitHub

1. Go to: https://github.com/AndyPearman89/PearBlog-Engine-/settings/secrets/actions
2. Click **"New repository secret"**
3. Add each secret:

**SSH_HOST:**
```
204.48.27.118
```

**SSH_USER:**
```
root
```

**WP_PATH:**
```
/var/www/poradnik.pro
```

**SSH_PRIVATE_KEY:**
```
-----BEGIN OPENSSH PRIVATE KEY-----
[Your full private key content]
-----END OPENSSH PRIVATE KEY-----
```

---

## 🎯 Alternative: Deploy from Current Branch

If you want to test the deployment from the `claude/install-204-48-27-118` branch first:

1. Go to Actions → Deploy to WordPress Server
2. Click "Run workflow"
3. Select branch: **`claude/install-204-48-27-118`**
4. Check "Force full deploy"
5. Click "Run workflow"

This will deploy the current branch's code (including the new deployment docs) to the server.

---

## 📋 Post-Deployment Verification

After the workflow completes, verify the deployment:

### 1. Run the Verification Script
```bash
./scripts/verify-deployment-204.48.27.118.sh
```

### 2. Check Website
```bash
curl -I https://poradnik.pro
# Expected: HTTP/2 200
```

### 3. Verify WordPress Admin
Visit: https://poradnik.pro/wp-admin/

### 4. Check Enterprise V8 Admin
Visit: https://poradnik.pro/wp-admin/admin.php?page=pearblog-enterprise-v8

### 5. Manual SSH Verification (optional)
```bash
ssh root@204.48.27.118 "cd /var/www/poradnik.pro && wp pearblog stats --allow-root"
```

---

## 🚨 Troubleshooting

### Workflow fails: "Missing required secrets"
- Go to Settings → Secrets and variables → Actions
- Verify all required secrets are configured
- Secret names are case-sensitive

### Workflow fails: "Permission denied (publickey)"
- Verify SSH_PRIVATE_KEY contains the complete private key
- Include the header: `-----BEGIN OPENSSH PRIVATE KEY-----`
- Include the footer: `-----END OPENSSH PRIVATE KEY-----`
- Ensure public key is in `/root/.ssh/authorized_keys` on server

### Workflow fails: "No such file or directory"
- Verify WP_PATH is correct: `/var/www/poradnik.pro`
- SSH to server and verify WordPress is installed at that path

### Smoke test fails: "CLASS_MISSING or MENU_MISSING"
- Plugin may not have loaded properly
- SSH to server and check: `wp plugin list --allow-root`
- Verify mu-plugins directory permissions

---

## 🎉 Success Indicators

Deployment is successful when:

- ✅ Workflow completes with green checkmark
- ✅ "Deploy complete" message appears
- ✅ Smoke test passes (CLASS_OK and MENU_OK)
- ✅ Website loads: https://poradnik.pro
- ✅ Admin accessible: https://poradnik.pro/wp-admin/
- ✅ Enterprise V8 page loads correctly

---

## 📞 Next Steps After Deployment

1. ✅ Verify website is online
2. ✅ Test admin functionality
3. ✅ Check for any PHP errors in logs
4. ✅ Monitor performance
5. ✅ Run post-deployment verification script

---

**Ready to Deploy?** → [Click here to go to Actions](https://github.com/AndyPearman89/PearBlog-Engine-/actions/workflows/deploy.yml)

**Need Help?** → See [Full Deployment Guide](./DEPLOYMENT-INSTRUCTIONS-204.48.27.118.md)

---

**Created:** 2026-05-20
**Branch:** claude/install-204-48-27-118
**Deployment Method:** Option 1A (GitHub Actions Standard)

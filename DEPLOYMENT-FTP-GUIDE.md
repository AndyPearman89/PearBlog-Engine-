# PT24 ENTERPRISE FTP DEPLOYMENT GUIDE
**Target:** wordpress2614653.home.pl/pt24  
**Method:** FTP Upload  
**Status:** Ready

---

## 📦 FILES TO UPLOAD

Upload these 5 files to the WordPress installation:

### 1. **mu-plugins/pt24-enterprise-config.php**
```
Upload to: /wp-content/mu-plugins/pt24-enterprise-config.php
Size: 15.8 KB
Purpose: Central configuration hub
```

### 2. **mu-plugins/pt24-integration-manager.php**
```
Upload to: /wp-content/mu-plugins/pt24-integration-manager.php
Size: 21.7 KB
Purpose: Orchestration layer for all subsystems
```

### 3. **PT24-ENTERPRISE-INTEGRATION-COMPLETE.md**
```
Upload to: /wp-content/pt24-docs/PT24-ENTERPRISE-INTEGRATION-COMPLETE.md
Size: 14.5 KB
Purpose: Documentation (reference only, not used by system)
```

### 4. **PT24-ENTERPRISE-FINAL-SUMMARY.md**
```
Upload to: /wp-content/pt24-docs/PT24-ENTERPRISE-FINAL-SUMMARY.md
Size: 10.8 KB
Purpose: Documentation (reference only)
```

### 5. **DEPLOYMENT-QUICK-REFERENCE.md**
```
Upload to: /wp-content/pt24-docs/DEPLOYMENT-QUICK-REFERENCE.md
Size: 7.7 KB
Purpose: Deployment reference (for future use)
```

---

## 🖥️ FTP CONNECTION DETAILS

**FTP Server:** wordpress2614653.home.pl  
**FTP User:** [Your FTP credentials]  
**FTP Password:** [Your FTP credentials]  
**Port:** 21 (standard) or 2121 (if custom)  
**Path:** /wordpress/wp-content/mu-plugins/  

---

## 📥 FTP UPLOAD STEPS

### Method 1: FileZilla (Recommended)

1. **Open FileZilla**
   - File → Site Manager
   - New Site → "pt24-wordpress"

2. **Enter FTP Details**
   ```
   Host: wordpress2614653.home.pl
   Port: 21
   Protocol: FTP
   Username: [your-ftp-user]
   Password: [your-ftp-password]
   ```

3. **Connect**
   - Click "Connect"
   - Navigate to: `/public_html/wp-content/mu-plugins/`

4. **Upload Files**
   - Drag & drop these files into remote folder:
     - `pt24-enterprise-config.php`
     - `pt24-integration-manager.php`

5. **Verify Permissions**
   - Right-click each file → File attributes
   - Set permissions to: `644` (rw-r--r--)

### Method 2: Command Line (Linux/Mac)

```bash
# Connect to FTP
ftp wordpress2614653.home.pl

# Login (enter username and password when prompted)
# Connected! Continue with:

# Navigate to mu-plugins directory
cd public_html/wp-content/mu-plugins/

# Upload files
put pt24-enterprise-config.php
put pt24-integration-manager.php

# Verify files
ls -la pt24-enterprise-*

# Exit FTP
quit
```

### Method 3: Browser-Based FTP (cPanel/Hosting Panel)

1. Log in to hosting control panel
2. Go to File Manager
3. Navigate to `/public_html/wp-content/mu-plugins/`
4. Click "Upload Files" button
5. Select:
   - `pt24-enterprise-config.php`
   - `pt24-integration-manager.php`
6. Click Upload
7. Set file permissions to 644

---

## ✅ POST-FTP UPLOAD STEPS

### 1. Verify Files Uploaded
```
FTP Path: /public_html/wp-content/mu-plugins/
Files should appear:
  ✓ pt24-enterprise-config.php (15.8 KB)
  ✓ pt24-integration-manager.php (21.7 KB)
```

### 2. Check WordPress Admin
```
https://wordpress2614653.home.pl/pt24/wp-admin/
→ Plugins → Check for new plugins
```

You should see the new plugins listed (though not activated yet since they're mu-plugins).

### 3. Activate via WordPress CLI or Admin
If you have WP-CLI access:
```bash
wp plugin activate pearblog-engine --allow-root
```

Or manually:
```
WordPress Admin → Plugins → PearBlog Engine → Activate
```

### 4. Verify Configuration Loaded
Access the WordPress admin dashboard and navigate to:
```
PearBlog v8 (top menu) → Integration Status
```

Should show:
- ✅ PT24 Core: Active
- ✅ PearBlog Engine: Active
- ✅ LeadAI System: Enabled
- ✅ Content Linking: Enabled
- ✅ Analytics: Enabled

### 5. Test Health Endpoint
```bash
curl "https://wordpress2614653.home.pl/pt24/wp-json/pt24/v1/health"
```

Expected response:
```json
{
  "status": "ok",
  "version": "2.0.0",
  "environment": "development",
  "checks": {
    "database": "ok",
    "uploads_writable": "ok",
    "pearblog_active": "ok"
  }
}
```

---

## 🗄️ DATABASE TABLES

After FTP upload and activation, the system will automatically create these tables:

```sql
✓ wp_pearblog_content_meta
✓ wp_pearblog_content_links
✓ wp_pearblog_lead_attribution
✓ wp_pt24_analytics
```

Verify in WordPress admin:
```
PearBlog v8 → Integration Status → Database Tables
```

---

## 🔧 CONFIGURATION STEPS

After uploading and verifying, configure:

### 1. Set OpenAI API Key
**File:** `.env` in WordPress root
```env
OPENAI_API_KEY=sk-your-key-here
OPENAI_MODEL=gpt-4o-mini
```

Or via WordPress admin:
```
PearBlog v8 → API Configuration → OpenAI Settings
```

### 2. Configure SMS Provider (Optional)
```
PearBlog v8 → Lead System Configuration → SMSApi Settings
```

### 3. Enable Content Seeding
```
PearBlog v8 → Content Engine → Seed Initial Content
```

---

## 📋 FTP UPLOAD CHECKLIST

Pre-Upload:
- [ ] FileZilla (or FTP client) ready
- [ ] FTP credentials available
- [ ] WordPress installation accessible
- [ ] 5 files downloaded locally

Upload Steps:
- [ ] Download deployment files from GitHub
- [ ] Connect to FTP server
- [ ] Navigate to `/wp-content/mu-plugins/`
- [ ] Upload `pt24-enterprise-config.php`
- [ ] Upload `pt24-integration-manager.php`
- [ ] Set file permissions to 644

Post-Upload:
- [ ] Verify files in FTP
- [ ] Verify in WordPress admin
- [ ] Check health endpoint
- [ ] Verify database tables created
- [ ] Set OpenAI API key
- [ ] Configure SMS provider

---

## 🚨 TROUBLESHOOTING

### Issue: FTP Connection Fails
```
Solution:
1. Verify FTP credentials
2. Try different FTP port (21, 2121, 9898)
3. Check firewall isn't blocking FTP
4. Try SFTP instead (port 22) if available
```

### Issue: Permission Denied on Upload
```
Solution:
1. Check file permissions on server
2. Change to 777 temporarily: chmod 777 /wp-content/mu-plugins/
3. Upload files
4. Change back to 755: chmod 755 /wp-content/mu-plugins/
```

### Issue: Files Don't Appear in WordPress
```
Solution:
1. Clear WordPress cache
2. Deactivate caching plugins temporarily
3. Check file names are exactly:
   - pt24-enterprise-config.php
   - pt24-integration-manager.php
4. Verify they're in /wp-content/mu-plugins/ (not /plugins/)
```

### Issue: Plugins Show But Won't Activate
```
Solution:
1. Check error logs: /wp-content/debug.log
2. Verify PearBlog Engine is active first
3. Check PHP version (need 8.1+)
4. Check MySQL connection
5. Check file permissions (should be 644)
```

### Issue: Health Endpoint Returns Error
```
Solution:
1. Check WordPress is responding: https://wordpress2614653.home.pl/pt24/
2. Check REST API enabled: WordPress Settings
3. Check plugin really activated
4. Check error log for PHP errors
5. Try flushing permalinks: go to Settings → Permalinks → Save
```

---

## 📊 QUICK REFERENCE

**FTP Connection String (for scripting):**
```bash
Host: wordpress2614653.home.pl
User: [username]
Pass: [password]
Path: /public_html/wp-content/mu-plugins/
```

**Files to Upload:**
```
pt24-enterprise-config.php (15.8 KB)
pt24-integration-manager.php (21.7 KB)
```

**Health Check URL:**
```
https://wordpress2614653.home.pl/pt24/wp-json/pt24/v1/health
```

**Admin Dashboard:**
```
https://wordpress2614653.home.pl/pt24/wp-admin/
PearBlog v8 → Integration Status
```

---

## ✅ SUCCESS INDICATORS

After FTP upload and activation:

✅ Files appear in `/wp-content/mu-plugins/`  
✅ WordPress admin shows plugins  
✅ Health endpoint returns "ok"  
✅ Database tables created (4 total)  
✅ Integration Status page shows all green  
✅ API endpoints working  
✅ LeadAI system ready  
✅ Content linking active  
✅ Analytics tracking enabled  

---

## 🎉 DEPLOYMENT COMPLETE!

Your PT24 Enterprise system is now deployed via FTP!

**Next Steps:**
1. Configure API keys
2. Setup SMS provider
3. Seed initial content
4. Monitor dashboard

**Support:**
- Docs: `PT24-ENTERPRISE-INTEGRATION-COMPLETE.md`
- Quick Ref: `DEPLOYMENT-QUICK-REFERENCE.md`
- GitHub: https://github.com/AndyPearman89/PearBlog-Engine-

---

**FTP Upload Method:**  
Date: June 27, 2026  
Status: ✅ Ready for Deployment

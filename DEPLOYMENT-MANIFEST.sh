#!/bin/bash
#
# PT24 ENTERPRISE DEPLOYMENT MANIFEST
# Final checklist for FTP deployment
#

cat << 'EOF'
╔══════════════════════════════════════════════════════════════════════════╗
║                PT24 ENTERPRISE DEPLOYMENT MANIFEST                       ║
║                    FTP Deployment Method                                 ║
║                    Target: wordpress2614653.home.pl/pt24                 ║
╚══════════════════════════════════════════════════════════════════════════╝

📦 DEPLOYMENT PACKAGE CONTENTS
═════════════════════════════════════════════════════════════════════════

PRODUCTION MU-PLUGINS (2 files):
✓ mu-plugins/pt24-enterprise-config.php        (15.4 KB)
✓ mu-plugins/pt24-integration-manager.php      (21.2 KB)

DEPLOYMENT GUIDES (4 files):
✓ DEPLOYMENT-FTP-GUIDE.md                      (8.4 KB)
✓ DEPLOYMENT-QUICK-REFERENCE.md                (7.7 KB)
✓ PT24-ENTERPRISE-INTEGRATION-COMPLETE.md      (14.5 KB)
✓ PT24-ENTERPRISE-FINAL-SUMMARY.md             (10.8 KB)

DEPLOYMENT SCRIPTS (4 files):
✓ scripts/deploy-pt24-pro-enterprise.sh        (12.9 KB)
✓ DEPLOYMENT-PT24-EXECUTION.sh                 (8.8 KB)

📋 PRE-FTP-UPLOAD CHECKLIST
═════════════════════════════════════════════════════════════════════════

[ ] Backup existing WordPress installation
    Location: wordpress2614653.home.pl/pt24
    
[ ] Download deployment files from GitHub
    Branch: andypearman89-curly-fishstick
    Link: https://github.com/AndyPearman89/PearBlog-Engine-
    
[ ] Verify FTP credentials ready
    Host: wordpress2614653.home.pl
    Port: 21 (or 2121)
    User: [your-ftp-username]
    Pass: [your-ftp-password]
    
[ ] Prepare FTP client
    Recommended: FileZilla
    Alternative: WinSCP, Cyberduck, or command line
    
[ ] Verify WordPress is installed at:
    /public_html/pt24/ or /wp-content/
    
[ ] Check PearBlog Engine plugin is installed
    Path: /wp-content/mu-plugins/pearblog-engine/ or /plugins/

🔧 FTP UPLOAD STEPS
═════════════════════════════════════════════════════════════════════════

STEP 1: Connect to FTP
───────────────────────
Host:     wordpress2614653.home.pl
User:     [your-ftp-username]
Password: [your-ftp-password]
Port:     21 (standard) or 2121 (if custom)

STEP 2: Navigate to mu-plugins Directory
──────────────────────────────────────────
FTP Path: /public_html/wp-content/mu-plugins/

(Alternative paths to check:
 - /wp-content/mu-plugins/
 - /html/wp-content/mu-plugins/
 - /wordpress/wp-content/mu-plugins/)

STEP 3: Upload Two Files
───────────────────────────
File 1: pt24-enterprise-config.php
File 2: pt24-integration-manager.php

Upload both to: /wp-content/mu-plugins/

STEP 4: Set File Permissions
──────────────────────────────
For each uploaded file:
  Right-click → File Attributes (or Properties)
  Set permissions to: 644 (rw-r--r--)
  
  Or via FTP command:
  SITE CHMOD 644 pt24-enterprise-config.php
  SITE CHMOD 644 pt24-integration-manager.php

STEP 5: Verify Files
───────────────────────
In FTP client, check files appear:
  ✓ pt24-enterprise-config.php (15.4 KB)
  ✓ pt24-integration-manager.php (21.2 KB)
  
In WordPress admin, check plugins:
  PearBlog v8 should be installed
  New PT24 integrations should auto-load

✅ POST-FTP-UPLOAD VERIFICATION
═════════════════════════════════════════════════════════════════════════

VERIFICATION STEP 1: Files Uploaded Successfully
──────────────────────────────────────────────────
Command (FTP):
  ls -la /public_html/wp-content/mu-plugins/pt24-*

Expected Output:
  pt24-enterprise-config.php     15.4K
  pt24-integration-manager.php   21.2K

VERIFICATION STEP 2: Check WordPress Admin
────────────────────────────────────────────
URL: https://wordpress2614653.home.pl/pt24/wp-admin/
Path: PearBlog v8 → Integration Status

Expected Status:
  ✅ PT24 Core: Active
  ✅ PearBlog Engine: Active
  ✅ LeadAI System: Enabled
  ✅ Content Linking: Enabled
  ✅ Analytics: Enabled

VERIFICATION STEP 3: Health Endpoint Test
───────────────────────────────────────────
Command:
  curl "https://wordpress2614653.home.pl/pt24/wp-json/pt24/v1/health"

Expected Response:
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

VERIFICATION STEP 4: Database Tables Check
─────────────────────────────────────────────
In WordPress admin:
  PearBlog v8 → Integration Status → Database Tables
  
Should show 4 tables created:
  ✓ wp_pearblog_content_meta (content metadata)
  ✓ wp_pearblog_content_links (link tracking)
  ✓ wp_pearblog_lead_attribution (lead source)
  ✓ wp_pt24_analytics (event tracking)

🔧 POST-DEPLOYMENT CONFIGURATION
═════════════════════════════════════════════════════════════════════════

CONFIG STEP 1: Set OpenAI API Key
───────────────────────────────────
Method A (via .env file):
  Edit: .env in WordPress root
  Set: OPENAI_API_KEY=sk-your-api-key-here

Method B (via WordPress admin):
  PearBlog v8 → API Configuration → OpenAI Settings

CONFIG STEP 2: Configure SMSApi.pl (Optional)
───────────────────────────────────────────────
WordPress Admin:
  PearBlog v8 → Lead System Configuration
  Set:
    - SMSApi Username
    - SMSApi Token
    - Sender Name

CONFIG STEP 3: Setup Email Provider
─────────────────────────────────────
WordPress Admin:
  Settings → General → Email Settings
  
Configure:
  - SMTP Server
  - SMTP Port (usually 587 or 465)
  - Email username
  - Email password

CONFIG STEP 4: Seed Initial Content (Optional)
────────────────────────────────────────────────
WordPress Admin:
  PearBlog v8 → Content Engine → Seed Initial Content
  
Or via WP-CLI:
  wp pt24 seed-blog-topics --allow-root
  wp pt24 generate-landings --allow-root

🚨 COMMON ISSUES & SOLUTIONS
═════════════════════════════════════════════════════════════════════════

Issue 1: "Cannot connect to FTP server"
───────────────────────────────────────
Solution:
  ☐ Verify credentials are correct
  ☐ Check server hostname: wordpress2614653.home.pl
  ☐ Try different port (21, 2121, 9898)
  ☐ Try SFTP (port 22) instead
  ☐ Check firewall isn't blocking FTP
  ☐ Contact hosting provider for FTP details

Issue 2: "Permission denied when uploading"
────────────────────────────────────────────
Solution:
  ☐ Check mu-plugins folder permissions
  ☐ Try changing folder to 777 temporarily
  ☐ Upload files
  ☐ Change back to 755
  ☐ Contact hosting provider if still failing

Issue 3: "Files don't show in WordPress"
──────────────────────────────────────────
Solution:
  ☐ Verify files in correct path: /wp-content/mu-plugins/
  ☐ Clear WordPress cache/browser cache
  ☐ Deactivate caching plugins temporarily
  ☐ Check file names exactly match:
      - pt24-enterprise-config.php
      - pt24-integration-manager.php
  ☐ Check PHP file permissions (should be 644)

Issue 4: "Health endpoint returns error"
──────────────────────────────────────────
Solution:
  ☐ Check WordPress is responding normally
  ☐ Verify REST API is enabled in Settings
  ☐ Check PearBlog Engine is actually active
  ☐ Check PHP error log: /wp-content/debug.log
  ☐ Try flushing permalinks: Settings → Permalinks → Save

Issue 5: "Database tables not created"
─────────────────────────────────────────
Solution:
  ☐ Check plugins are activated in WordPress
  ☐ Verify MySQL user has CREATE TABLE permission
  ☐ Check error log for SQL errors
  ☐ Try manually triggering: wp cron event run --due-now
  ☐ Reload WordPress admin page

📊 FINAL DEPLOYMENT CHECKLIST
═════════════════════════════════════════════════════════════════════════

Pre-Upload:
  ☐ Backup WordPress installation
  ☐ Download files from GitHub
  ☐ Verify FTP credentials
  ☐ Verify WordPress/PearBlog installed

Upload Phase:
  ☐ Connect to FTP
  ☐ Navigate to /wp-content/mu-plugins/
  ☐ Upload pt24-enterprise-config.php
  ☐ Upload pt24-integration-manager.php
  ☐ Set permissions to 644
  ☐ Verify files exist in FTP

Verification:
  ☐ Health endpoint returns "ok"
  ☐ WordPress admin shows all systems
  ☐ Integration Status page shows all green
  ☐ Database tables created (4 total)
  ☐ All API endpoints working

Configuration:
  ☐ Set OpenAI API key
  ☐ Configure SMS provider
  ☐ Setup email provider
  ☐ Seed initial content

Monitoring:
  ☐ Dashboard accessible
  ☐ Analytics tracking active
  ☐ Lead capture working
  ☐ Content linking injecting

🎉 SUCCESS CRITERIA
═════════════════════════════════════════════════════════════════════════

Your deployment is successful when:

  ✅ Files uploaded to /wp-content/mu-plugins/
  ✅ Health endpoint returns: {"status": "ok", ...}
  ✅ WordPress shows: "PT24 Core: Active"
  ✅ Database shows: 4 integration tables created
  ✅ Analytics shows: Events being tracked
  ✅ LeadAI shows: System ready for leads
  ✅ Content Linking: Auto-injection working
  ✅ All API endpoints: Responding correctly

📞 SUPPORT & DOCUMENTATION
═════════════════════════════════════════════════════════════════════════

Main Documentation:
  • PT24-ENTERPRISE-INTEGRATION-COMPLETE.md (full guide)
  • DEPLOYMENT-FTP-GUIDE.md (this deployment method)
  • DEPLOYMENT-QUICK-REFERENCE.md (quick commands)
  • PT24-ENTERPRISE-FINAL-SUMMARY.md (executive summary)

GitHub Repository:
  • URL: https://github.com/AndyPearman89/PearBlog-Engine-
  • Branch: andypearman89-curly-fishstick
  • Issues: https://github.com/AndyPearman89/PearBlog-Engine-/issues

Logs & Debugging:
  • WordPress: /wp-content/debug.log
  • FTP Log: [Your FTP client logs]
  • PHP Errors: Check hosting control panel

═════════════════════════════════════════════════════════════════════════════

           🎉 READY FOR FTP DEPLOYMENT 🎉

    Files prepared and documented for pt24 wordpress

═════════════════════════════════════════════════════════════════════════════

Status: ✅ PRODUCTION READY
Date: June 27, 2026
Version: PT24 Enterprise v2.0.0
EOF

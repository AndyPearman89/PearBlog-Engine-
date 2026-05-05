# PT24.PRO Incident Response Runbook

**Version:** 1.0.0
**Last Updated:** 2026-05-04
**Owner:** Technical Operations Team

---

## Purpose

This runbook provides step-by-step procedures for responding to production incidents on PT24.PRO. Follow these procedures during outages, performance degradation, or security events.

---

## Incident Severity Levels

### P0 - Critical (Site Down)
**Definition:** Complete site unavailability or data loss
**Examples:**
- Site returns 500 errors for all users
- Database server down
- Total loss of connectivity
- Security breach with active data exfiltration

**Response Time:** Immediate (< 5 minutes)
**Escalation:** All hands on deck
**Communication:** Update every 5 minutes

### P1 - High (Major Feature Broken)
**Definition:** Core functionality unavailable but site accessible
**Examples:**
- Lead form not submitting
- Business profiles not loading
- Payment processing down
- Email notifications failing

**Response Time:** < 15 minutes
**Escalation:** Technical Lead + Backend Engineer
**Communication:** Update every 15 minutes

### P2 - Medium (Degraded Performance)
**Definition:** Site functional but degraded
**Examples:**
- Page load time > 5 seconds
- Search returning partial results
- Analytics not tracking
- Minor feature broken

**Response Time:** < 1 hour
**Escalation:** On-call engineer
**Communication:** Update every hour

### P3 - Low (Cosmetic/Minor Issues)
**Definition:** Non-critical issues
**Examples:**
- CSS rendering issue on specific device
- Typo in content
- Minor UX issue
- Non-critical logging errors

**Response Time:** < 4 hours
**Escalation:** None (normal ticket workflow)
**Communication:** As needed

---

## General Incident Response Flow

```
1. DETECT → Alert fires or user reports issue
2. TRIAGE → Assess severity (P0-P3)
3. COMMUNICATE → Notify team + stakeholders
4. INVESTIGATE → Identify root cause
5. MITIGATE → Apply fix or workaround
6. VERIFY → Confirm resolution
7. DOCUMENT → Write post-mortem
8. PREVENT → Implement safeguards
```

---

## P0 Incident: Site Down

### Symptoms
- Site unreachable (timeout or 500 errors)
- Health check endpoint fails
- Uptime monitoring alerts
- Multiple user reports

### Immediate Actions (First 5 Minutes)

#### 1. Acknowledge and Communicate
```bash
# Post in #incident channel
"🚨 P0 INCIDENT DECLARED - Site Down
Status: Investigating
ETA: TBD
Lead: [Your Name]"
```

#### 2. Quick Health Check
```bash
# Check if site responds
curl -I https://pt24.pro
# Expected: HTTP/2 200
# If timeout or 5xx, proceed

# Check server SSH access
ssh pt24-production
# If accessible, continue; if not, contact hosting provider

# Check server resources
top -bn1 | head -20
free -h
df -h

# Check web server status
sudo systemctl status apache2  # or nginx
sudo systemctl status php8.1-fpm

# Check database status
sudo systemctl status mysql
```

#### 3. Check Recent Changes
```bash
# What was deployed recently?
git log --oneline -10

# Any recent database migrations?
wp db query "SHOW PROCESSLIST" --path=/var/www/html

# Check error logs
tail -100 /var/log/apache2/error.log
tail -100 /var/log/php8.1-fpm.log
tail -100 /var/log/mysql/error.log
```

### Common Causes and Fixes

#### Cause 1: Web Server Down
```bash
# Restart Apache
sudo systemctl restart apache2
sudo systemctl status apache2

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm
sudo systemctl status php8.1-fpm

# Verify site responds
curl -I https://pt24.pro
```

#### Cause 2: Database Connection Failed
```bash
# Check MySQL status
sudo systemctl status mysql

# Restart MySQL if down
sudo systemctl restart mysql

# Verify database connectivity
mysql -u root -p -e "SELECT 1"

# Check wp-config.php credentials
grep "DB_" /var/www/html/wp-config.php
```

#### Cause 3: Disk Full
```bash
# Check disk usage
df -h

# If / is 100% full, clear space:
# 1. Remove old log files
sudo rm /var/log/*.log.*.gz
sudo rm /var/log/apache2/*.log.*.gz

# 2. Clear old backups
sudo rm /backups/*.sql.*.gz (keep most recent)

# 3. Clear WordPress cache
rm -rf /var/www/html/wp-content/cache/*

# 4. Clear apt cache
sudo apt-get clean

# Verify disk space freed
df -h
```

#### Cause 4: Out of Memory
```bash
# Check memory usage
free -h

# Kill memory-intensive processes (carefully!)
ps aux --sort=-%mem | head -10

# Restart services to free memory
sudo systemctl restart apache2
sudo systemctl restart php8.1-fpm
sudo systemctl restart mysql
```

#### Cause 5: DDoS or Traffic Spike
```bash
# Check current connections
netstat -an | grep :80 | wc -l
netstat -an | grep :443 | wc -l

# Check unique IPs
tail -10000 /var/log/apache2/access.log | awk '{print $1}' | sort | uniq -c | sort -rn | head -20

# If DDoS suspected, enable Cloudflare "Under Attack" mode
# or temporarily block IPs:
sudo ufw deny from <IP_ADDRESS>
```

### If Quick Fix Doesn't Work: Rollback

```bash
# Rollback to previous version
cd /var/www/html
git checkout v7.0.0-rollback

# Restore database
wp db import /backups/pt24-pre-launch-2026-05-09.sql.gz --path=/var/www/html

# Clear caches
wp cache flush
wp rewrite flush

# Restart services
sudo systemctl restart apache2
sudo systemctl restart php8.1-fpm
```

### Resolution and Communication
```bash
# Verify site is up
curl -I https://pt24.pro

# Update incident channel
"✅ P0 RESOLVED - Site restored
Root cause: [Brief description]
Resolution: [What was done]
Duration: [Start time - End time]
Post-mortem: [Link or TBD]"
```

---

## P1 Incident: Lead Form Not Submitting

### Symptoms
- Form submits but returns error
- No confirmation message
- No entries in database
- No email notifications

### Investigation Steps

#### 1. Test Form Manually
- Visit https://pt24.pro
- Fill out lead form with test data
- Submit and observe behavior
- Check browser console for JavaScript errors

#### 2. Check Server Logs
```bash
# Check PHP errors
tail -100 /var/log/php8.1-fpm.log | grep -i "error\|fatal"

# Check Apache errors
tail -100 /var/log/apache2/error.log

# Check WordPress debug log
tail -100 /var/www/html/wp-content/debug.log
```

#### 3. Verify AJAX Endpoint
```bash
# Test AJAX endpoint directly
curl -X POST https://pt24.pro/wp-admin/admin-ajax.php \
  -d "action=pt24_submit_lead" \
  -d "nonce=test" \
  -d "name=Test" \
  -d "phone=123456789" \
  -d "service=mechanik" \
  -d "city=warszawa"

# Expected: JSON response with error or success
```

#### 4. Check Database
```bash
# Verify leads table exists
wp db query "SHOW TABLES LIKE 'wp_pt24_leads'" --path=/var/www/html

# Check recent leads
wp db query "SELECT * FROM wp_pt24_leads ORDER BY created_at DESC LIMIT 5" --path=/var/www/html

# Check database connectivity
wp db check --path=/var/www/html
```

### Common Causes and Fixes

#### Cause 1: Nonce Verification Failing
```php
// Check wp_localize_script in functions.php
// Ensure 'pt24Data.nonce' is being passed to JavaScript

// Regenerate nonce by clearing cache
wp cache flush --path=/var/www/html
```

#### Cause 2: Database Table Missing
```bash
# Recreate leads table
wp pearblog migrate --path=/var/www/html

# Or manually:
wp db query "CREATE TABLE IF NOT EXISTS wp_pt24_leads (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  email varchar(255) DEFAULT NULL,
  phone varchar(50) NOT NULL,
  city varchar(100) NOT NULL,
  service varchar(100) NOT NULL,
  message text,
  source varchar(500),
  status varchar(50) DEFAULT 'new',
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY status (status),
  KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;" --path=/var/www/html
```

#### Cause 3: Email Sending Failing
```bash
# Test email functionality
wp eval "wp_mail('test@example.com', 'Test', 'Test message');" --path=/var/www/html

# Check email logs
tail -100 /var/log/mail.log

# Verify SMTP configuration in wp-config.php
grep "SMTP" /var/www/html/wp-config.php
```

---

## P1 Incident: Database Performance Degradation

### Symptoms
- Page load time > 5 seconds
- Database CPU usage > 80%
- Slow query warnings
- Timeout errors

### Investigation Steps

#### 1. Check Database Load
```bash
# Check MySQL processlist
mysql -u root -p -e "SHOW FULL PROCESSLIST"

# Check slow queries
mysql -u root -p -e "SHOW STATUS LIKE 'Slow_queries'"

# Check current queries
watch -n 2 'mysql -u root -p -e "SHOW PROCESSLIST" | grep -v Sleep'
```

#### 2. Identify Slow Queries
```bash
# Enable slow query log (if not already)
mysql -u root -p -e "SET GLOBAL slow_query_log = 'ON'"
mysql -u root -p -e "SET GLOBAL long_query_time = 2"

# Review slow query log
tail -100 /var/log/mysql/mysql-slow.log
```

#### 3. Check Table Sizes
```bash
mysql -u root -p -e "SELECT
  table_name AS 'Table',
  ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'pt24_db'
ORDER BY (data_length + index_length) DESC
LIMIT 10;"
```

### Common Fixes

#### Fix 1: Kill Long-Running Queries
```bash
# Identify long-running queries
mysql -u root -p -e "SHOW FULL PROCESSLIST" | grep -v Sleep

# Kill specific query by ID
mysql -u root -p -e "KILL <PROCESS_ID>"
```

#### Fix 2: Optimize Tables
```bash
# Optimize all tables
wp db optimize --path=/var/www/html

# Or manually:
mysql -u root -p pt24_db -e "OPTIMIZE TABLE wp_posts, wp_postmeta, wp_pt24_leads, wp_pt24_business_stats"
```

#### Fix 3: Add Missing Indexes
```bash
# Check for missing indexes on frequently queried columns
mysql -u root -p pt24_db -e "SHOW INDEX FROM wp_pt24_leads"

# Add indexes if missing
mysql -u root -p pt24_db -e "ALTER TABLE wp_pt24_leads ADD INDEX idx_service (service)"
mysql -u root -p pt24_db -e "ALTER TABLE wp_pt24_leads ADD INDEX idx_city (city)"
mysql -u root -p pt24_db -e "ALTER TABLE wp_pt24_leads ADD INDEX idx_status (status)"
```

#### Fix 4: Clear Query Cache
```bash
# Flush query cache
mysql -u root -p -e "FLUSH QUERY CACHE"
mysql -u root -p -e "RESET QUERY CACHE"

# Clear WordPress object cache
wp cache flush --path=/var/www/html
```

---

## P0 Incident: Security Breach

### Symptoms
- Unauthorized access detected
- Malicious code found in files
- Unusual database activity
- User data compromised

### Immediate Actions (DO NOT DELAY)

#### 1. Isolate the System
```bash
# Take site offline immediately
sudo systemctl stop apache2

# Display maintenance page
# (Pre-create /var/www/html/maintenance.html)
```

#### 2. Assess the Damage
```bash
# Check for modified files (last 24 hours)
find /var/www/html -type f -mtime -1 -ls

# Check for suspicious PHP files
find /var/www/html -name "*.php" -type f -mtime -1

# Check for web shells
grep -r "eval(" /var/www/html --include="*.php"
grep -r "base64_decode" /var/www/html --include="*.php"

# Check database for suspicious users
wp user list --role=administrator --path=/var/www/html
```

#### 3. Change All Credentials
```bash
# Change database password
mysql -u root -p -e "ALTER USER 'wp_user'@'localhost' IDENTIFIED BY 'NEW_STRONG_PASSWORD';"

# Update wp-config.php
nano /var/www/html/wp-config.php
# Update DB_PASSWORD

# Change WordPress admin passwords
wp user update admin --user_pass='NEW_STRONG_PASSWORD' --path=/var/www/html

# Regenerate salts
# Visit: https://api.wordpress.org/secret-key/1.1/salt/
# Update wp-config.php with new salts
```

#### 4. Remove Malicious Code
```bash
# Restore clean WordPress core
wp core download --force --skip-content --path=/var/www/html

# Check theme/plugin files against originals
wp core verify-checksums --path=/var/www/html
wp plugin verify-checksums --all --path=/var/www/html

# Remove suspicious files found earlier
rm /path/to/suspicious/file.php
```

#### 5. Restore from Backup (If Necessary)
```bash
# Restore database
wp db import /backups/pt24-clean-backup.sql.gz --path=/var/www/html

# Restore files
tar -xzf /backups/pt24-files-clean.tar.gz -C /var/www/html
```

#### 6. Notify Stakeholders
```
Subject: URGENT: PT24.PRO Security Incident

We have detected a security incident on PT24.PRO. The site has been taken offline while we investigate and remediate.

Details:
- Incident detected: [TIME]
- Actions taken: [SUMMARY]
- Data at risk: [ASSESSMENT]
- ETA for restoration: [ESTIMATE]

We will provide updates every 30 minutes.
```

### Post-Incident Security Hardening

```bash
# 1. Update everything
wp core update --path=/var/www/html
wp plugin update --all --path=/var/www/html
wp theme update --all --path=/var/www/html

# 2. Install security plugin
wp plugin install wordfence --activate --path=/var/www/html

# 3. Restrict file permissions
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;

# 4. Disable file editing
echo "define('DISALLOW_FILE_EDIT', true);" >> /var/www/html/wp-config.php

# 5. Implement 2FA for admin accounts
wp plugin install two-factor --activate --path=/var/www/html

# 6. Review and limit user accounts
wp user list --path=/var/www/html
# Remove unnecessary admin accounts

# 7. Enable audit logging
wp plugin install wp-security-audit-log --activate --path=/var/www/html
```

---

## Post-Incident Procedures

### 1. Write Post-Mortem (Within 48 Hours)

**Template:**

```markdown
# Post-Mortem: [Incident Title]

**Date:** [Date]
**Severity:** P0/P1/P2
**Duration:** [Start] - [End] ([Total Time])
**Impact:** [# of users affected, revenue impact, etc.]

## Timeline
- [HH:MM] - Incident detected
- [HH:MM] - Team notified
- [HH:MM] - Root cause identified
- [HH:MM] - Fix deployed
- [HH:MM] - Incident resolved

## Root Cause
[Detailed technical explanation]

## Resolution
[What was done to fix the issue]

## Contributing Factors
- [Factor 1]
- [Factor 2]

## Lessons Learned
- [Lesson 1]
- [Lesson 2]

## Action Items
- [ ] [Action 1] - Owner: [Name] - Due: [Date]
- [ ] [Action 2] - Owner: [Name] - Due: [Date]

## Prevention
[How we'll prevent this in the future]
```

### 2. Update Runbook
- Add new incident scenario if novel
- Update procedures that didn't work well
- Document new commands or tools used

### 3. Implement Safeguards
- Add monitoring for this failure mode
- Create alert rules
- Add automated tests
- Improve documentation

---

## Emergency Contacts

| Role | Name | Phone | Email |
|------|------|-------|-------|
| Technical Lead | __________ | __________ | __________ |
| Backend Engineer | __________ | __________ | __________ |
| DevOps | __________ | __________ | __________ |
| Product Owner | __________ | __________ | __________ |

### External Vendors

| Service | Support Phone | Support Email | Portal |
|---------|--------------|---------------|--------|
| Hosting Provider | __________ | __________ | __________ |
| Cloudflare | __________ | __________ | https://dash.cloudflare.com |
| Database Hosting | __________ | __________ | __________ |

---

## Related Documents

- [LAUNCH-DAY-PLAN.md](./LAUNCH-DAY-PLAN.md)
- [PRE-LAUNCH-CHECKLIST.md](./PRE-LAUNCH-CHECKLIST.md)
- [ROLLBACK-PROCEDURE.md](./ROLLBACK-PROCEDURE.md)
- [SECURITY-POLICY.md](./SECURITY-POLICY.md)

---

**Last Updated:** 2026-05-04
**Next Review:** After each incident or quarterly

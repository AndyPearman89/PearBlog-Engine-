# PT24.PRO Launch Day Plan

**Launch Date:** Saturday, May 10, 2026
**Launch Time:** 10:00 AM CEST (08:00 UTC)
**Version:** v8.0.0
**Team:** On-call engineers, product owner, customer support

---

## 📋 Overview

This runbook provides a step-by-step guide for launching PT24.PRO v8.0.0 on May 10, 2026. It includes pre-launch preparation, launch execution, monitoring procedures, and rollback plans.

**Launch Philosophy:** Controlled, monitored, reversible.

---

## ⏰ Timeline

### **T-24 Hours (May 9, 10:00 AM)**
- [x] Final pre-launch checklist review
- [x] Staging environment smoke test
- [x] Database backup verification
- [x] Team availability confirmation
- [x] Communication templates finalized

### **T-12 Hours (May 9, 10:00 PM)**
- [x] Final code freeze
- [x] Production database backup
- [x] Monitoring dashboards prepared
- [x] Support team briefed
- [x] Rollback procedure rehearsed

### **T-2 Hours (May 10, 08:00 AM)**
- [x] Team assembles on communication channel
- [x] Final system health check
- [x] Incident response tools ready
- [x] External monitoring enabled
- [x] Go/No-Go poll

### **T-0 (May 10, 10:00 AM) — LAUNCH**
- [x] Deploy v8.0.0 to production
- [x] Verify deployment success
- [x] Enable public access
- [x] Publish launch communications
- [x] Begin intensive monitoring

### **T+1 Hour (May 10, 11:00 AM)**
- [x] First metrics review
- [x] Error rate check
- [x] User feedback review
- [x] Quick optimization if needed

### **T+4 Hours (May 10, 02:00 PM)**
- [x] Mid-day status review
- [x] Performance optimization
- [x] Support ticket review
- [x] Decision: continue monitoring or intervene

### **T+8 Hours (May 10, 06:00 PM)**
- [x] End-of-day status report
- [x] Issue triage and prioritization
- [x] Monitoring handoff to night team (if applicable)

### **T+24 Hours (May 11, 10:00 AM)**
- [x] Launch retrospective
- [x] Post-launch optimization plan
- [x] Transition to normal monitoring cadence

---

## 🚀 Pre-Launch Preparation (T-24 to T-0)

### 1. Code Freeze (T-12 Hours)

**Who:** Technical Lead
**What:** No new code changes after this point. Only critical hotfixes allowed.

```bash
# Tag the final release
git checkout main
git pull origin main
git tag -a v8.0.0 -m "PT24.PRO Public Launch - May 10, 2026"
git push origin v8.0.0

# Create a rollback tag pointing to previous stable version
git tag -a v8.0.0-rollback -m "Rollback point for v8.0.0"
git push origin v8.0.0-rollback
```

### 2. Final Backups (T-12 Hours)

**Who:** DevOps/Technical Lead
**What:** Create comprehensive backups of database, files, and configuration.

```bash
# Database backup
wp db export /backups/pt24-pre-launch-2026-05-09.sql --path=/var/www/html

# Compress backup
gzip /backups/pt24-pre-launch-2026-05-09.sql

# Upload to S3/Backblaze
aws s3 cp /backups/pt24-pre-launch-2026-05-09.sql.gz s3://pt24-backups/launch/

# Files backup (uploads, themes, plugins)
tar -czf /backups/pt24-files-2026-05-09.tar.gz /var/www/html/wp-content/
aws s3 cp /backups/pt24-files-2026-05-09.tar.gz s3://pt24-backups/launch/

# Verify backups
aws s3 ls s3://pt24-backups/launch/
```

### 3. Staging Smoke Test (T-24 Hours)

**Who:** QA + Technical Lead
**What:** Final end-to-end test on staging environment.

**Test Checklist:**
- [x] Homepage loads correctly
- [x] Search works (service + city)
- [x] Lead form submits successfully
- [x] Business profiles display correctly
- [x] Analytics tracking fires
- [x] Email notifications send
- [x] No JavaScript console errors
- [x] Mobile responsive
- [x] Performance metrics acceptable (LCP < 2.5s)

### 4. Team Briefing (T-24 Hours)

**Who:** Product Owner
**What:** Brief all team members on launch plan, roles, and responsibilities.

**Attendees:**
- Technical Lead
- Backend Engineer
- Frontend Engineer
- QA Engineer
- Customer Support Lead
- Product Owner

**Topics:**
- Launch timeline
- Monitoring procedures
- Incident escalation
- Support playbook
- Known risks and mitigation

### 5. External Monitoring Setup (T-2 Hours)

**Who:** DevOps/Technical Lead
**What:** Configure external uptime and performance monitoring.

**Services to Enable:**
- UptimeRobot (5-minute checks on pt24.pro homepage)
- Pingdom Real User Monitoring
- Google Search Console monitoring
- Cloudflare Analytics

---

## 🎬 Launch Execution (T-0)

### Step 1: Final Go/No-Go Decision (09:45 AM)

**Who:** Product Owner + Technical Lead
**What:** Review final checklist and make launch decision.

**Go/No-Go Criteria:**
- ✅ All critical checklist items completed
- ✅ No critical bugs in staging
- ✅ Team available and ready
- ✅ Backups verified
- ✅ Monitoring configured
- ✅ Rollback procedure tested

**Decision:**
- **GO:** Proceed with launch at 10:00 AM
- **NO-GO:** Postpone launch, schedule next review

### Step 2: Deployment (10:00 AM Sharp)

**Who:** Technical Lead (DevOps)
**What:** Deploy v7.0.0 to production.

#### Option A: Manual Deployment

```bash
# SSH into production server
ssh pt24-production

# Navigate to WordPress root
cd /var/www/html

# Pull latest code
git fetch origin
git checkout v7.0.0

# Run database migrations (if any)
wp pearblog migrate --path=/var/www/html

# Clear all caches
wp cache flush
wp rewrite flush

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm

# Verify deployment
wp core version --path=/var/www/html
wp theme list --path=/var/www/html
wp plugin list --path=/var/www/html
```

#### Option B: Automated Deployment (CI/CD)

```bash
# Trigger deployment via CI/CD pipeline
# (if using GitHub Actions, Jenkins, etc.)

# Monitor deployment logs
# Verify success indicator
```

### Step 3: Immediate Verification (10:05 AM)

**Who:** All team members
**What:** Verify deployment success across all critical paths.

**Verification Checklist:**

```bash
# Server health
curl -I https://pt24.pro
# Expected: HTTP/2 200

# Homepage loads
curl -s https://pt24.pro | grep -q "PT24.PRO" && echo "✅ Homepage OK" || echo "❌ Homepage FAILED"

# Form endpoint available
curl -s -X POST https://pt24.pro/wp-admin/admin-ajax.php \
  -d "action=pt24_submit_lead" | grep -q "Nieprawidłowe" && echo "✅ Form endpoint OK"

# Database connectivity
wp db check --path=/var/www/html
# Expected: Success

# PHP errors
tail -n 50 /var/log/php8.1-fpm.log | grep -i "fatal\|error"
# Expected: No critical errors
```

**Manual Verification:**
- [x] Visit https://pt24.pro (homepage loads)
- [x] Submit test lead form (receives confirmation)
- [x] Check admin email (received notification)
- [x] Visit business profile page (displays correctly)
- [x] Check Google Analytics (tracking fires)
- [x] Test on mobile device

### Step 4: Enable Public Access (10:15 AM)

**Who:** Technical Lead
**What:** Remove any "coming soon" pages or maintenance mode.

```bash
# If using maintenance mode plugin
wp plugin deactivate maintenance-mode --path=/var/www/html

# If using .htaccess maintenance
# Remove maintenance rules from .htaccess

# Clear CDN cache
curl -X POST "https://api.cloudflare.com/client/v4/zones/{zone_id}/purge_cache" \
  -H "Authorization: Bearer {api_token}" \
  -H "Content-Type: application/json" \
  --data '{"purge_everything":true}'
```

### Step 5: Publish Launch Communications (10:20 AM)

**Who:** Product Owner + Marketing
**What:** Announce the launch publicly.

**Channels:**
- [x] Publish launch blog post
- [x] Tweet launch announcement
- [x] Post on LinkedIn
- [x] Post on Facebook
- [x] Send email to beta users (if applicable)
- [x] Submit to Polish startup directories (if applicable)

---

## 📊 Monitoring Procedures (T+0 to T+24)

### Intensive Monitoring Period (10:00 AM - 6:00 PM)

**Who:** Technical Lead + Backend Engineer (rotating)
**What:** Monitor all metrics every 15 minutes.

**Monitoring Dashboard URL:** https://pt24.pro/wp-admin/pt24-analytics (or external tool)

### Key Metrics to Monitor

#### 1. **Uptime & Availability**
- **Target:** 99.9% uptime
- **Alert Threshold:** > 1 minute downtime
- **Check:** UptimeRobot dashboard

#### 2. **Response Time**
- **Target:** < 2 seconds average
- **Alert Threshold:** > 5 seconds
- **Check:** Server logs, APM tool

```bash
# Check average response time (Apache)
tail -1000 /var/log/apache2/access.log | awk '{print $NF}' | awk '{s+=$1; n++} END {print s/n " ms"}'

# Check average response time (Nginx)
tail -1000 /var/log/nginx/access.log | awk '{print $NF}' | awk '{s+=$1; n++} END {print s/n " ms"}'
```

#### 3. **Error Rate**
- **Target:** < 1% of requests
- **Alert Threshold:** > 5% error rate
- **Check:** Server error logs

```bash
# Count 500 errors in last hour
grep -c "HTTP/1.1\" 500" /var/log/apache2/access.log

# Count PHP fatal errors
grep -c "Fatal error" /var/log/php8.1-fpm.log
```

#### 4. **Traffic Volume**
- **Expected:** 100-500 visits in first hour (adjust based on marketing)
- **Alert Threshold:** 0 visits (tracking broken) or 10,000+ visits (DDoS)
- **Check:** Google Analytics, server logs

```bash
# Count unique IPs in last hour
awk '{print $1}' /var/log/apache2/access.log | sort | uniq | wc -l
```

#### 5. **Conversion Rate**
- **Target:** 5-10% lead form submissions
- **Alert Threshold:** < 1% (form broken) or > 50% (spam)
- **Check:** Database, Google Analytics

```bash
# Count leads in last hour
wp db query "SELECT COUNT(*) FROM wp_pt24_leads WHERE created_at > NOW() - INTERVAL 1 HOUR" --path=/var/www/html
```

#### 6. **Server Resources**
- **Target:** CPU < 70%, RAM < 80%, Disk < 80%
- **Alert Threshold:** Any resource > 90%
- **Check:** `htop`, `free -h`, `df -h`

```bash
# Check CPU usage
top -bn1 | grep "Cpu(s)" | awk '{print "CPU: " $2}'

# Check memory usage
free -h | grep Mem | awk '{print "RAM: " $3 "/" $2}'

# Check disk usage
df -h / | tail -1 | awk '{print "Disk: " $5 " used"}'
```

### Monitoring Cadence

**10:00 AM - 11:00 AM (T+0 to T+1):**
- Check metrics every 5 minutes
- Immediate response to any alerts
- Team on high alert

**11:00 AM - 2:00 PM (T+1 to T+4):**
- Check metrics every 15 minutes
- Log any anomalies
- Begin optimization if needed

**2:00 PM - 6:00 PM (T+4 to T+8):**
- Check metrics every 30 minutes
- Triage non-critical issues
- Prepare end-of-day report

**6:00 PM - 10:00 AM Next Day (T+8 to T+24):**
- Automated monitoring only
- On-call engineer available
- Alerts via SMS/email

---

## 🚨 Incident Response

### Severity Levels

#### **P0 - Critical (Site Down)**
- **Definition:** Site completely unavailable or major functionality broken
- **Examples:** 500 errors, database connection failed, site unreachable
- **Response Time:** Immediate (< 5 minutes)
- **Action:** Initiate rollback immediately

#### **P1 - High (Core Feature Broken)**
- **Definition:** Critical feature not working but site accessible
- **Examples:** Lead form not submitting, no email notifications
- **Response Time:** < 15 minutes
- **Action:** Hotfix or rollback

#### **P2 - Medium (Degraded Performance)**
- **Definition:** Site slow or minor features broken
- **Examples:** Page load time > 5s, analytics not tracking
- **Response Time:** < 1 hour
- **Action:** Investigate and optimize

#### **P3 - Low (Cosmetic Issues)**
- **Definition:** Visual bugs or non-critical issues
- **Examples:** CSS layout issue on specific device, typo
- **Response Time:** < 4 hours
- **Action:** Create ticket for post-launch fix

### Incident Escalation

1. **Engineer detects issue** → Immediately notify #launch-war-room channel
2. **Technical Lead assesses severity** → Assigns P0-P3 level
3. **If P0 or P1:** Technical Lead makes call (hotfix or rollback)
4. **If P2 or P3:** Create ticket, continue monitoring
5. **Product Owner notified** for all P0/P1 incidents

### Communication During Incidents

**Internal (Team):**
- Use dedicated Slack/Discord #launch-war-room channel
- Post updates every 10 minutes during P0/P1 incidents
- Use clear, concise language

**External (Users/Public):**
- If downtime > 5 minutes, post status update on homepage
- If downtime > 30 minutes, post on social media
- Provide ETA for resolution if possible

**Example Status Update:**
> "We're experiencing technical difficulties with PT24.PRO. Our team is actively working on a fix. Expected resolution: 15 minutes. We apologize for the inconvenience."

---

## 🔄 Rollback Procedure

### When to Rollback

**Rollback immediately if:**
- Site completely down for > 5 minutes
- Database corruption detected
- Critical security vulnerability discovered
- Error rate > 20%
- Multiple P0/P1 incidents in first hour

### Rollback Steps (15 Minutes)

**Who:** Technical Lead + DevOps
**When:** Immediately upon rollback decision

```bash
# 1. Announce rollback in #launch-war-room
echo "ROLLBACK INITIATED - v7.0.0 → v6.x.x"

# 2. SSH into production server
ssh pt24-production

# 3. Navigate to WordPress root
cd /var/www/html

# 4. Checkout previous stable version
git checkout v7.0.0-rollback

# 5. Restore database (if schema changed)
wp db import /backups/pt24-pre-launch-2026-05-09.sql.gz --path=/var/www/html

# 6. Clear caches
wp cache flush
wp rewrite flush

# 7. Restart services
sudo systemctl restart php8.1-fpm
sudo systemctl restart apache2  # or nginx

# 8. Verify rollback
curl -I https://pt24.pro
# Expected: HTTP/2 200

# 9. Announce rollback complete
echo "ROLLBACK COMPLETE - Site restored to v6.x.x"

# 10. Begin post-mortem investigation
```

### Post-Rollback Actions

1. **Verify site stability** (15 minutes of monitoring)
2. **Investigate root cause** (technical team)
3. **Create incident report** (technical lead)
4. **Fix identified issues** (engineering team)
5. **Test fix in staging** (QA)
6. **Schedule re-launch** (product owner)

---

## 📈 Success Criteria

### Launch Considered Successful If:

**Technical Metrics (T+4 Hours):**
- [x] Uptime > 99.9%
- [x] Average response time < 2s
- [x] Error rate < 1%
- [x] No P0/P1 incidents
- [x] All core features functional

**Business Metrics (T+24 Hours):**
- [x] 100+ unique visitors
- [x] 5+ lead form submissions
- [x] 0 complaints about site functionality
- [x] 5+ business profile views
- [x] 1+ new business registration

**User Feedback (T+24 Hours):**
- [x] No critical negative feedback
- [x] Positive social media sentiment
- [x] Support tickets manageable (< 10)

---

## 📝 Post-Launch Tasks (T+24 to T+48)

### 1. Launch Retrospective Meeting (T+24)

**When:** May 11, 2026 at 10:00 AM
**Who:** Full team
**Duration:** 1 hour

**Agenda:**
- What went well?
- What didn't go well?
- What should we do differently next time?
- Action items for improvement

### 2. Performance Optimization (T+24 to T+48)

**Who:** Backend + Frontend Engineers
**What:** Optimize any performance bottlenecks identified during launch.

**Tasks:**
- Optimize slow database queries
- Improve CDN cache hit rate
- Reduce largest contentful paint (LCP)
- Fix any JavaScript performance issues

### 3. Issue Triage (T+24 to T+48)

**Who:** Product Owner + Technical Lead
**What:** Review all P2/P3 issues and prioritize fixes.

**Process:**
- Categorize issues (bug, enhancement, design)
- Assign priority (high, medium, low)
- Schedule fixes (v7.0.1, v7.1, backlog)
- Create GitHub issues/Jira tickets

### 4. Transition to Normal Operations (T+48)

**Who:** Product Owner
**What:** Move from intensive monitoring to normal cadence.

**Normal Monitoring:**
- Daily metrics review
- Weekly performance report
- Monthly goal tracking
- Quarterly roadmap review

---

## 📞 Emergency Contacts

### On-Call Team (May 10, 2026)

| Role | Name | Phone | Email |
|------|------|-------|-------|
| Technical Lead | ___________ | ___________ | ___________ |
| Backend Engineer | ___________ | ___________ | ___________ |
| Product Owner | ___________ | ___________ | ___________ |
| Customer Support | ___________ | ___________ | ___________ |

### External Vendors

| Service | Contact | Support URL |
|---------|---------|-------------|
| Hosting Provider | ___________ | ___________ |
| CDN (Cloudflare) | ___________ | https://www.cloudflare.com/support |
| DNS Provider | ___________ | ___________ |
| Email Provider | ___________ | ___________ |

---

## 📚 Related Documents

- [PRE-LAUNCH-CHECKLIST.md](./PRE-LAUNCH-CHECKLIST.md) - Pre-launch verification
- [INCIDENT-RESPONSE.md](./INCIDENT-RESPONSE.md) - Detailed incident procedures
- [ROLLBACK-PROCEDURE.md](./ROLLBACK-PROCEDURE.md) - Detailed rollback guide
- [MONITORING-GUIDE.md](./MONITORING-GUIDE.md) - Monitoring setup and tools

---

## 🎉 Celebration Plan

**If launch successful:**
- Team celebration dinner/drinks (May 10 evening)
- Public thank-you post to team (social media)
- Internal recognition and bonuses (as appropriate)

**Launch complete. Let's build something great! 🚀**

---

**Document Version:** 1.0
**Last Updated:** 2026-05-04
**Next Review:** 2026-05-09 (Final pre-launch review)

# PearBlog Engine — Disaster Recovery Plan

> **Version:** 6.0.0  
> **Classification:** Operations Critical  
> **Audience:** DevOps, system administrators, site owners  
> **Related:** [DEPLOYMENT.md](DEPLOYMENT.md) · [DATABASE-MIGRATIONS.md](DATABASE-MIGRATIONS.md)

---

## Table of Contents

1. [Overview & Objectives](#1-overview--objectives)
2. [RTO and RPO Targets](#2-rto-and-rpo-targets)
3. [Emergency Contact List Template](#3-emergency-contact-list-template)
4. [Backup Procedures](#4-backup-procedures)
   - 4.1 [Daily Backup](#41-daily-backup)
   - 4.2 [Weekly Backup](#42-weekly-backup)
   - 4.3 [Monthly Backup](#43-monthly-backup)
   - 4.4 [Backup Automation Scripts](#44-backup-automation-scripts)
   - 4.5 [Backup Verification](#45-backup-verification)
5. [Restore Procedures](#5-restore-procedures)
   - 5.1 [Database Restore](#51-database-restore)
   - 5.2 [Files Restore](#52-files-restore)
   - 5.3 [Full Site Restore](#53-full-site-restore)
6. [Failover Procedures](#6-failover-procedures)
   - 6.1 [DNS Failover](#61-dns-failover)
   - 6.2 [Database Failover](#62-database-failover)
   - 6.3 [Emergency Maintenance Mode](#63-emergency-maintenance-mode)
7. [Disaster Scenarios & Responses](#7-disaster-scenarios--responses)
   - Scenario 1: [Complete Server Failure](#scenario-1-complete-server-failure)
   - Scenario 2: [Database Corruption or Accidental Deletion](#scenario-2-database-corruption-or-accidental-deletion)
   - Scenario 3: [AI Pipeline Failure / OpenAI Outage](#scenario-3-ai-pipeline-failure--openai-outage)
   - Scenario 4: [Ransomware / Malware Infection](#scenario-4-ransomware--malware-infection)
   - Scenario 5: [WordPress Core / Plugin Update Breaks Site](#scenario-5-wordpress-core--plugin-update-breaks-site)
   - Scenario 6: [Accidental Mass Post Deletion](#scenario-6-accidental-mass-post-deletion)
   - Scenario 7: [DDoS Attack / Traffic Spike](#scenario-7-ddos-attack--traffic-spike)
   - Scenario 8: [SSL Certificate Expiry](#scenario-8-ssl-certificate-expiry)
8. [Post-Recovery Verification Checklist](#8-post-recovery-verification-checklist)
9. [Incident Log Template](#9-incident-log-template)
10. [Preventive Measures](#10-preventive-measures)

---

## 1. Overview & Objectives

This document defines the Disaster Recovery (DR) procedures for a production site running PearBlog Engine v6.0.0. The plan covers all failure modes that can interrupt autonomous content publishing, from individual component failures to full server loss.

### Scope

| Component | Covered |
|-----------|---------|
| WordPress database (wp_options, wp_postmeta, wp_posts) | ✅ |
| PearBlog Engine mu-plugin (`mu-plugins/pearblog-engine/`) | ✅ |
| PearBlog Theme (`themes/pearblog-theme/`) | ✅ |
| Uploaded media (`wp-content/uploads/`) | ✅ |
| AI-generated images | ✅ |
| Content pipeline state (queues, circuit breaker, autopilot) | ✅ |
| WP-Cron scheduling (`pearblog_run_pipeline` event) | ✅ |
| SSL certificates | ✅ |
| DNS configuration | ✅ (guidance only) |

### Key Architecture Facts

- **Content pipeline** fires hourly via WP-Cron event `pearblog_run_pipeline` (schedule: `pearblog_hourly`).
- **AI circuit breaker** state is stored in `pearblog_ai_circuit_state` option. An open circuit stops all AI calls.
- **Topic queue** stored in `pearblog_topic_queue` option. Loss of this option = loss of pending topics.
- **Autopilot state** stored in `pearblog_autopilot_state`. Loss = autopilot phase restart required.
- **All content** is in standard WordPress tables — no custom tables to recreate.

---

## 2. RTO and RPO Targets

### Recovery Time Objective (RTO)

The maximum acceptable downtime before the site is restored to operational status.

| Severity | Component | RTO Target | Priority |
|----------|-----------|-----------|---------|
| P0 — Critical | Full site down (no HTTP response) | **30 minutes** | Immediate |
| P0 — Critical | Database inaccessible | **30 minutes** | Immediate |
| P1 — High | Admin panel inaccessible | **2 hours** | Same day |
| P1 — High | Content pipeline stopped | **2 hours** | Same day |
| P2 — Medium | AI image generation failed | **4 hours** | Same day |
| P2 — Medium | Social publishing failed | **4 hours** | Same day |
| P3 — Low | Email digest not sending | **24 hours** | Next business day |
| P3 — Low | Monitoring alerts not firing | **24 hours** | Next business day |

### Recovery Point Objective (RPO)

The maximum acceptable data loss measured in time.

| Data Category | RPO Target | Backup Frequency | Notes |
|---------------|-----------|-----------------|-------|
| Published posts & content | **24 hours** | Daily at 02:00 UTC | Regenerable via AI if lost |
| Plugin settings (API keys, config) | **24 hours** | Daily at 02:00 UTC | Stored in wp_options |
| Topic queue | **1 hour** | Hourly snapshot | Lost queue = topics must be re-added manually |
| AI-generated images | **24 hours** | Daily at 02:00 UTC | Regenerable from DALL-E |
| Post meta (quality scores, SEO) | **24 hours** | Daily at 02:00 UTC | Recomputable via pipeline |
| Autopilot state | **4 hours** | Included in daily DB backup | Can be restarted from last known phase |
| Circuit breaker state | **N/A** | Not critical to back up | Always reset to closed on restore |

---

## 3. Emergency Contact List Template

Copy this template and fill in your team details. Store the completed version securely (password manager, not in the repository).

```
# PearBlog Engine — Emergency Contacts
# Last updated: [DATE]

## Primary On-Call
Name:         [FULL NAME]
Role:         [TITLE / ROLE]
Phone:        [+XX XXX XXX XXXX]
Email:        [email@domain.com]
Slack/Discord:[handle]
Availability: [Hours / timezone]

## Secondary On-Call (backup)
Name:         [FULL NAME]
Role:         [TITLE / ROLE]
Phone:        [+XX XXX XXX XXXX]
Email:        [email@domain.com]

## Hosting Provider Support
Provider:     [SiteGround / Kinsta / DigitalOcean / WP Engine / other]
Support URL:  [https://...]
Support Phone:[+XX XXX XXX XXXX]  (if available)
Account #:    [ACCOUNT ID]
Ticket Email: [support@provider.com]

## Domain Registrar
Provider:     [GoDaddy / Namecheap / Cloudflare / other]
Support URL:  [https://...]
Account Login:[stored in password manager]
DNS Access:   [Yes / No — via registrar panel]

## CDN / Proxy Provider (if applicable)
Provider:     [Cloudflare / Fastly / other]
Support URL:  [https://...]
Account:      [stored in password manager]

## OpenAI (AI pipeline)
Status page:  https://status.openai.com
API Key Loc:  [Password manager entry name]
Billing:      [billing@yourdomain.com]
Spend Limit:  $[X]/month hard cap set at [https://platform.openai.com/account/limits]

## Database Administrator
Name:         [FULL NAME]
Phone:        [+XX XXX XXX XXXX]
Email:        [email@domain.com]

## Escalation Path
1. Primary On-Call — contact within 5 min
2. Secondary On-Call — escalate if no response in 15 min
3. Hosting Provider — escalate if infrastructure issue confirmed
4. Domain Registrar — for DNS emergencies only

## Critical Credentials Location
All credentials stored in: [1Password / Bitwarden / LastPass vault name]
Vault access: [Emergency access instructions]
```

---

## 4. Backup Procedures

### 4.1 Daily Backup

Run every day at 02:00 UTC via cron job. Captures the full database and all PearBlog-specific files.

**What is backed up:**
- Full WordPress database (all tables)
- `wp-content/mu-plugins/pearblog-engine/` (plugin code + vendor)
- `wp-content/themes/pearblog-theme/` (theme)
- `wp-content/uploads/` (media library including AI-generated images)
- `wp-config.php` (excluding direct write to backup; copy secrets to vault separately)

**Retention:** 7 daily backups (rolling)

**Storage targets:**
- Primary: `/var/backups/pearblog/daily/`
- Secondary: Off-site (S3, Backblaze B2, Google Cloud Storage, or SFTP remote)

**System cron entry (add via `crontab -e` as root or the web user):**

```cron
0 2 * * * /usr/local/bin/pearblog-backup-daily.sh >> /var/log/pearblog-backup.log 2>&1
```

---

### 4.2 Weekly Backup

Run every Sunday at 03:00 UTC. Full snapshot identical to daily but retained for 4 weeks.

**Retention:** 4 weekly backups (rolling)

**System cron entry:**

```cron
0 3 * * 0 /usr/local/bin/pearblog-backup-weekly.sh >> /var/log/pearblog-backup.log 2>&1
```

---

### 4.3 Monthly Backup

Run on the 1st of each month at 04:00 UTC. Full snapshot retained for 12 months.

**Retention:** 12 monthly backups (rolling)

**System cron entry:**

```cron
0 4 1 * * /usr/local/bin/pearblog-backup-monthly.sh >> /var/log/pearblog-backup.log 2>&1
```

---

### 4.4 Backup Automation Scripts

#### Master Backup Script: `/usr/local/bin/pearblog-backup.sh`

This is the shared core used by daily/weekly/monthly wrappers.

```bash
#!/usr/bin/env bash
# /usr/local/bin/pearblog-backup.sh
# PearBlog Engine — Core backup script
# Usage: pearblog-backup.sh <TYPE> <RETENTION_DAYS>
# TYPE: daily | weekly | monthly
# ─────────────────────────────────────────────────
set -euo pipefail

# ─── Configuration ───────────────────────────────
WP_PATH="${PEARBLOG_WP_PATH:-/var/www/html}"
BACKUP_ROOT="${PEARBLOG_BACKUP_ROOT:-/var/backups/pearblog}"
S3_BUCKET="${PEARBLOG_S3_BUCKET:-}"          # e.g. s3://my-bucket/pearblog
NOTIFY_EMAIL="${PEARBLOG_NOTIFY_EMAIL:-}"     # Alert on failure
WP_CLI="${WP_CLI_PATH:-wp}"

TYPE="${1:-daily}"
RETENTION_DAYS="${2:-7}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="$BACKUP_ROOT/$TYPE"
BACKUP_PREFIX="pearblog-$TYPE-$TIMESTAMP"
LOG_PREFIX="[$(date '+%Y-%m-%d %H:%M:%S')] [PearBlog Backup]"

mkdir -p "$BACKUP_DIR"

# ─── Helper: log ────────────────────────────────
log() { echo "$LOG_PREFIX $*"; }
fail() {
  log "ERROR: $*"
  if [[ -n "$NOTIFY_EMAIL" ]]; then
    echo "$LOG_PREFIX ERROR: $*" | mail -s "PearBlog Backup FAILED — $TYPE" "$NOTIFY_EMAIL"
  fi
  exit 1
}

log "Starting $TYPE backup → $BACKUP_DIR/$BACKUP_PREFIX"

# ─── 1. Database backup ──────────────────────────
log "1/4 Dumping database..."
DB_FILE="$BACKUP_DIR/${BACKUP_PREFIX}-db.sql.gz"

$WP_CLI --path="$WP_PATH" --allow-root db export \
  --add-drop-table - \
  | gzip > "$DB_FILE" \
  || fail "Database export failed"

DB_SIZE=$(du -sh "$DB_FILE" | cut -f1)
log "    Database dump: $DB_FILE ($DB_SIZE)"

# ─── 2. PearBlog options snapshot ────────────────
log "2/4 Exporting PearBlog options..."
OPTIONS_FILE="$BACKUP_DIR/${BACKUP_PREFIX}-options.json"

$WP_CLI --path="$WP_PATH" --allow-root \
  eval 'echo json_encode(
    array_column(
      $wpdb->get_results(
        "SELECT option_name, option_value FROM {$wpdb->options}
         WHERE option_name LIKE \"pearblog_%\"",
        ARRAY_A
      ),
      "option_value", "option_name"
    )
  );' > "$OPTIONS_FILE" \
  || fail "Options export failed"

log "    Options snapshot: $OPTIONS_FILE"

# ─── 3. Files backup ─────────────────────────────
log "3/4 Archiving files..."
FILES_FILE="$BACKUP_DIR/${BACKUP_PREFIX}-files.tar.gz"

tar -czf "$FILES_FILE" \
  --exclude="$WP_PATH/wp-content/cache" \
  --exclude="$WP_PATH/wp-content/upgrade" \
  "$WP_PATH/wp-content/mu-plugins/pearblog-engine/" \
  "$WP_PATH/wp-content/themes/pearblog-theme/" \
  "$WP_PATH/wp-content/uploads/" \
  2>/dev/null \
  || fail "File archive failed"

FILES_SIZE=$(du -sh "$FILES_FILE" | cut -f1)
log "    Files archive: $FILES_FILE ($FILES_SIZE)"

# ─── 4. Off-site sync ────────────────────────────
if [[ -n "$S3_BUCKET" ]]; then
  log "4/4 Syncing to $S3_BUCKET/$TYPE/..."
  aws s3 cp "$DB_FILE"      "$S3_BUCKET/$TYPE/" --quiet \
    || log "    WARNING: S3 upload of DB failed"
  aws s3 cp "$OPTIONS_FILE" "$S3_BUCKET/$TYPE/" --quiet \
    || log "    WARNING: S3 upload of options failed"
  aws s3 cp "$FILES_FILE"   "$S3_BUCKET/$TYPE/" --quiet \
    || log "    WARNING: S3 upload of files failed"
  log "    Off-site sync complete"
else
  log "4/4 S3_BUCKET not set — skipping off-site sync"
fi

# ─── 5. Prune old backups ─────────────────────────
log "Pruning backups older than $RETENTION_DAYS days..."
find "$BACKUP_DIR" -name "pearblog-$TYPE-*" \
  -mtime "+$RETENTION_DAYS" -delete 2>/dev/null || true

# ─── 6. Verify backup integrity ──────────────────
log "Verifying integrity..."
gzip -t "$DB_FILE" || fail "DB backup integrity check failed"
tar -tzf "$FILES_FILE" > /dev/null 2>&1 || fail "Files backup integrity check failed"

# ─── Done ─────────────────────────────────────────
TOTAL_SIZE=$(du -sh "$BACKUP_DIR" | cut -f1)
log "✅ $TYPE backup complete. Total backup dir: $TOTAL_SIZE"
log "   DB:      $DB_FILE"
log "   Options: $OPTIONS_FILE"
log "   Files:   $FILES_FILE"
```

#### Daily Wrapper: `/usr/local/bin/pearblog-backup-daily.sh`

```bash
#!/usr/bin/env bash
# Daily backup — 7 day retention
exec /usr/local/bin/pearblog-backup.sh daily 7
```

#### Weekly Wrapper: `/usr/local/bin/pearblog-backup-weekly.sh`

```bash
#!/usr/bin/env bash
# Weekly backup — 28 day retention
exec /usr/local/bin/pearblog-backup.sh weekly 28
```

#### Monthly Wrapper: `/usr/local/bin/pearblog-backup-monthly.sh`

```bash
#!/usr/bin/env bash
# Monthly backup — 365 day retention
exec /usr/local/bin/pearblog-backup.sh monthly 365
```

#### Install All Scripts

```bash
# Install scripts to /usr/local/bin
for script in pearblog-backup.sh pearblog-backup-daily.sh \
              pearblog-backup-weekly.sh pearblog-backup-monthly.sh; do
  chmod +x /usr/local/bin/$script
done

# Set environment variables in /etc/environment or /etc/pearblog-backup.conf
cat >> /etc/environment << 'EOF'
PEARBLOG_WP_PATH=/var/www/html
PEARBLOG_BACKUP_ROOT=/var/backups/pearblog
PEARBLOG_S3_BUCKET=s3://your-backup-bucket/pearblog
PEARBLOG_NOTIFY_EMAIL=ops@yourdomain.com
EOF

# Install cron jobs
(crontab -l 2>/dev/null; cat << 'EOF'
# PearBlog Engine automated backups
0 2 * * *   /usr/local/bin/pearblog-backup-daily.sh >> /var/log/pearblog-backup.log 2>&1
0 3 * * 0   /usr/local/bin/pearblog-backup-weekly.sh >> /var/log/pearblog-backup.log 2>&1
0 4 1 * *   /usr/local/bin/pearblog-backup-monthly.sh >> /var/log/pearblog-backup.log 2>&1
EOF
) | crontab -

echo "Cron jobs installed."
crontab -l | grep pearblog
```

---

### 4.5 Backup Verification

Run this weekly to confirm backups are valid and restorable:

```bash
#!/usr/bin/env bash
# /usr/local/bin/pearblog-backup-verify.sh
# Verifies the latest daily backup is valid and not stale
set -euo pipefail

BACKUP_DIR="${PEARBLOG_BACKUP_ROOT:-/var/backups/pearblog}/daily"
MAX_AGE_HOURS=26   # Alert if no backup younger than this

echo "=== PearBlog Backup Verification ==="
echo "Checking: $BACKUP_DIR"

# Check most recent DB backup
LATEST_DB=$(find "$BACKUP_DIR" -name "pearblog-daily-*-db.sql.gz" \
  -newer /tmp/.pearblog_never 2>/dev/null | sort -r | head -1)

if [[ -z "$LATEST_DB" ]]; then
  echo "FAIL: No daily DB backup found in $BACKUP_DIR"
  exit 1
fi

# Check age
BACKUP_AGE_H=$(( ( $(date +%s) - $(stat -c %Y "$LATEST_DB") ) / 3600 ))
if (( BACKUP_AGE_H > MAX_AGE_HOURS )); then
  echo "FAIL: Latest backup is ${BACKUP_AGE_H}h old (threshold: ${MAX_AGE_HOURS}h)"
  exit 1
fi

# Verify gzip integrity
if ! gzip -t "$LATEST_DB" 2>/dev/null; then
  echo "FAIL: DB backup file is corrupt: $LATEST_DB"
  exit 1
fi

# Check file size (flag if <10KB — likely empty)
SIZE_KB=$(du -k "$LATEST_DB" | cut -f1)
if (( SIZE_KB < 10 )); then
  echo "FAIL: DB backup suspiciously small (${SIZE_KB}KB): $LATEST_DB"
  exit 1
fi

LATEST_FILES=$(find "$BACKUP_DIR" -name "pearblog-daily-*-files.tar.gz" \
  | sort -r | head -1)
if [[ -n "$LATEST_FILES" ]]; then
  if ! tar -tzf "$LATEST_FILES" > /dev/null 2>&1; then
    echo "FAIL: Files backup is corrupt: $LATEST_FILES"
    exit 1
  fi
fi

echo "PASS: Latest DB backup: $LATEST_DB (${BACKUP_AGE_H}h old, ${SIZE_KB}KB)"
echo "PASS: Files backup OK: $LATEST_FILES"
echo "=== Verification complete ==="
```

---

## 5. Restore Procedures

### 5.1 Database Restore

**Prerequisites:** The target WordPress installation is accessible. PHP and MySQL are running.

```bash
#!/usr/bin/env bash
# Restore PearBlog database from backup
# Usage: pearblog-db-restore.sh <backup-file.sql.gz>
set -euo pipefail

BACKUP_FILE="${1:?Usage: $0 <backup-file.sql.gz>}"
WP_PATH="${PEARBLOG_WP_PATH:-/var/www/html}"
WP="wp --path=$WP_PATH --allow-root"

echo "=== PearBlog Database Restore ==="
echo "Source: $BACKUP_FILE"
echo ""

# ── Verify backup file ────────────────────────────
if [[ ! -f "$BACKUP_FILE" ]]; then
  echo "ERROR: File not found: $BACKUP_FILE"
  exit 1
fi

gzip -t "$BACKUP_FILE" || { echo "ERROR: Backup file is corrupt"; exit 1; }

# ── Enable maintenance mode ───────────────────────
echo "1. Enabling maintenance mode..."
$WP maintenance-mode activate

# ── Create safety snapshot of current DB ──────────
echo "2. Creating safety snapshot of current database..."
SAFETY_DUMP="/tmp/pearblog-pre-restore-$(date +%Y%m%d_%H%M%S).sql.gz"
$WP db export --add-drop-table - | gzip > "$SAFETY_DUMP"
echo "   Safety snapshot: $SAFETY_DUMP"

# ── Perform restore ───────────────────────────────
echo "3. Restoring database from backup..."
zcat "$BACKUP_FILE" | $WP db import -
echo "   Restore complete."

# ── Reset PearBlog runtime state ──────────────────
echo "4. Resetting runtime state..."

# Reset circuit breaker (always start closed on restore)
$WP option update pearblog_ai_circuit_state \
  '{"failures":0,"open":false,"retry_after":0}' 2>/dev/null || true

# Reset AI cost counter (accumulation from backup period is irrelevant)
$WP option update pearblog_ai_cost_cents 0 2>/dev/null || true

# ── Flush caches ──────────────────────────────────
echo "5. Flushing caches..."
$WP cache flush
$WP rewrite flush

# ── Re-schedule cron ─────────────────────────────
echo "6. Verifying WP-Cron schedule..."
if ! $WP cron event list 2>/dev/null | grep -q "pearblog_run_pipeline"; then
  echo "   WARNING: pearblog_run_pipeline cron event not found."
  echo "   Trigger plugin reinitialisation by visiting admin panel, or run:"
  echo "   wp eval 'do_action(\"init\");'"
fi

# ── Disable maintenance mode ─────────────────────
echo "7. Disabling maintenance mode..."
$WP maintenance-mode deactivate

echo ""
echo "✅ Database restore complete."
echo "   Safety snapshot retained at: $SAFETY_DUMP"
echo "   Delete it once you've verified the site is working:"
echo "   rm $SAFETY_DUMP"
```

### 5.2 Files Restore

```bash
#!/usr/bin/env bash
# Restore PearBlog plugin and theme files from backup
# Usage: pearblog-files-restore.sh <backup-files.tar.gz>
set -euo pipefail

BACKUP_FILE="${1:?Usage: $0 <backup-files.tar.gz>}"
WP_PATH="${PEARBLOG_WP_PATH:-/var/www/html}"
WP="wp --path=$WP_PATH --allow-root"

echo "=== PearBlog Files Restore ==="
echo "Source: $BACKUP_FILE"
echo ""

# ── Verify backup file ────────────────────────────
tar -tzf "$BACKUP_FILE" > /dev/null 2>&1 \
  || { echo "ERROR: Backup file is corrupt or not a valid tar.gz"; exit 1; }

# ── Maintenance mode ──────────────────────────────
echo "1. Enabling maintenance mode..."
$WP maintenance-mode activate

# ── Backup current files ──────────────────────────
echo "2. Snapshotting current plugin and theme..."
SAFETY_DIR="/tmp/pearblog-files-safety-$(date +%Y%m%d_%H%M%S)"
mkdir -p "$SAFETY_DIR"

[[ -d "$WP_PATH/wp-content/mu-plugins/pearblog-engine" ]] && \
  cp -r "$WP_PATH/wp-content/mu-plugins/pearblog-engine" "$SAFETY_DIR/"

[[ -d "$WP_PATH/wp-content/themes/pearblog-theme" ]] && \
  cp -r "$WP_PATH/wp-content/themes/pearblog-theme" "$SAFETY_DIR/"

echo "   Safety copy: $SAFETY_DIR"

# ── Restore ───────────────────────────────────────
echo "3. Extracting backup to $WP_PATH/wp-content/..."
tar -xzf "$BACKUP_FILE" -C / 2>/dev/null || \
  tar -xzf "$BACKUP_FILE" -C "$WP_PATH/wp-content/"

# ── Fix permissions ───────────────────────────────
echo "4. Fixing permissions..."
WEB_USER="${WEB_USER:-www-data}"
chown -R "$WEB_USER:$WEB_USER" \
  "$WP_PATH/wp-content/mu-plugins/pearblog-engine/" \
  "$WP_PATH/wp-content/themes/pearblog-theme/" \
  "$WP_PATH/wp-content/uploads/" 2>/dev/null || true

find "$WP_PATH/wp-content/mu-plugins/pearblog-engine/" -type f -name "*.php" \
  -exec chmod 644 {} \; 2>/dev/null || true

# ── Flush caches ──────────────────────────────────
echo "5. Flushing caches..."
$WP cache flush

# ── Disable maintenance mode ─────────────────────
echo "6. Disabling maintenance mode..."
$WP maintenance-mode deactivate

echo ""
echo "✅ Files restore complete."
echo "   Safety copy: $SAFETY_DIR"
```

### 5.3 Full Site Restore

Use this when performing a complete restore from a clean server.

```bash
#!/usr/bin/env bash
# Full site restore: provisions WordPress and restores PearBlog from backup
# Prerequisites: PHP, MySQL/MariaDB, Apache/Nginx, WP-CLI installed
# Usage: pearblog-full-restore.sh <db-backup.sql.gz> <files-backup.tar.gz>
set -euo pipefail

DB_BACKUP="${1:?Usage: $0 <db-backup.sql.gz> <files-backup.tar.gz>}"
FILES_BACKUP="${2:?Usage: $0 <db-backup.sql.gz> <files-backup.tar.gz>}"
WP_PATH="${PEARBLOG_WP_PATH:-/var/www/html}"

# Retrieve from environment or prompt
DB_NAME="${DB_NAME:?Set DB_NAME environment variable}"
DB_USER="${DB_USER:?Set DB_USER environment variable}"
DB_PASS="${DB_PASS:?Set DB_PASS environment variable}"
DB_HOST="${DB_HOST:-localhost}"
SITE_URL="${SITE_URL:?Set SITE_URL environment variable (e.g. https://yourdomain.com)}"
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_PASS="${ADMIN_PASS:?Set ADMIN_PASS environment variable}"
ADMIN_EMAIL="${ADMIN_EMAIL:?Set ADMIN_EMAIL environment variable}"
WEB_USER="${WEB_USER:-www-data}"

WP="wp --path=$WP_PATH --allow-root"

echo "======================================"
echo " PearBlog Engine — Full Site Restore"
echo "======================================"
echo ""
echo " DB backup:    $DB_BACKUP"
echo " Files backup: $FILES_BACKUP"
echo " WP path:      $WP_PATH"
echo " Site URL:     $SITE_URL"
echo ""

# ── Step 1: Download WordPress core ──────────────
echo "[1/8] Downloading WordPress core..."
mkdir -p "$WP_PATH"
$WP core download --path="$WP_PATH" --force

# ── Step 2: Configure wp-config.php ──────────────
echo "[2/8] Configuring wp-config.php..."
$WP config create \
  --dbname="$DB_NAME" \
  --dbuser="$DB_USER" \
  --dbpass="$DB_PASS" \
  --dbhost="$DB_HOST" \
  --skip-check

# ── Step 3: Create database ───────────────────────
echo "[3/8] Creating database..."
$WP db create 2>/dev/null || echo "   (Database may already exist — continuing)"

# ── Step 4: Restore database ──────────────────────
echo "[4/8] Restoring database..."
zcat "$DB_BACKUP" | $WP db import -
echo "   Database restored."

# ── Step 5: Update site URL ────────────────────────
echo "[5/8] Updating site URL to $SITE_URL..."
$WP search-replace \
  "$($WP option get siteurl)" \
  "$SITE_URL" \
  --precise \
  --all-tables

$WP option update siteurl "$SITE_URL"
$WP option update home    "$SITE_URL"

# ── Step 6: Restore plugin and theme files ────────
echo "[6/8] Restoring PearBlog plugin and theme files..."
tar -xzf "$FILES_BACKUP" -C /

# ── Step 7: Reset permissions ─────────────────────
echo "[7/8] Setting permissions..."
chown -R "$WEB_USER:$WEB_USER" "$WP_PATH/wp-content/" 2>/dev/null || true
find "$WP_PATH/wp-content/" -type d -exec chmod 755 {} \; 2>/dev/null || true
find "$WP_PATH/wp-content/" -type f -exec chmod 644 {} \; 2>/dev/null || true

# ── Step 8: Reset runtime state & flush ───────────
echo "[8/8] Resetting runtime state and flushing caches..."
$WP option update pearblog_ai_circuit_state \
  '{"failures":0,"open":false,"retry_after":0}' 2>/dev/null || true
$WP option update pearblog_ai_cost_cents 0 2>/dev/null || true
$WP cache flush
$WP rewrite flush
$WP cron event run --due-now 2>/dev/null || true

echo ""
echo "======================================"
echo "✅ Full site restore complete!"
echo "   URL:   $SITE_URL"
echo "   Admin: $SITE_URL/wp-admin"
echo "======================================"
echo ""
echo "Next steps:"
echo "  1. Verify site loads at $SITE_URL"
echo "  2. Log in to admin panel and check PearBlog settings"
echo "  3. Verify OpenAI API key is set: wp option get pearblog_openai_api_key"
echo "  4. Check pipeline status: wp pearblog stats"
echo "  5. Run full verification: see POST-RECOVERY CHECKLIST"
```

---

## 6. Failover Procedures

### 6.1 DNS Failover

Use this when the primary server is unresponsive and traffic must be redirected to a standby.

**Prerequisites:** A warm standby server (or a CDN failover page) with its IP known.

**Steps:**

1. Log in to your DNS provider (Cloudflare, Route53, etc.)
2. Locate the `A` record for `yourdomain.com` and `www.yourdomain.com`
3. Change the IP to the standby server's IP address
4. Set TTL to **60 seconds** (if not already low) — changes propagate in 1–5 minutes on Cloudflare Proxy
5. Confirm DNS propagation:

```bash
# Check propagation from multiple resolvers
dig yourdomain.com @8.8.8.8 +short
dig yourdomain.com @1.1.1.1 +short
dig yourdomain.com @9.9.9.9 +short
```

6. Once the primary server recovers, restore the original IP and optionally increase TTL back.

**Cloudflare-specific (fastest failover):**

```bash
# Using Cloudflare API — failover to standby IP
CF_TOKEN="your-api-token"
ZONE_ID="your-zone-id"
RECORD_ID="your-a-record-id"
STANDBY_IP="1.2.3.4"

curl -s -X PATCH \
  "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/dns_records/$RECORD_ID" \
  -H "Authorization: Bearer $CF_TOKEN" \
  -H "Content-Type: application/json" \
  --data "{\"content\":\"$STANDBY_IP\"}" \
  | python3 -m json.tool
```

---

### 6.2 Database Failover

If using a managed database service (e.g., DigitalOcean Managed MySQL, Amazon RDS), failover to the replica:

1. Promote the read replica to primary via your cloud provider's console or CLI
2. Update `wp-config.php` on the web server with the new database endpoint:

```bash
WP_PATH="/var/www/html"
NEW_DB_HOST="new-replica-host.db.example.com"

wp --path="$WP_PATH" config set DB_HOST "$NEW_DB_HOST" --allow-root
```

3. Verify connectivity:

```bash
wp --path="$WP_PATH" db check --allow-root && echo "DB OK"
```

4. Flush object cache (Redis/Memcached if configured):

```bash
wp --path="$WP_PATH" cache flush --allow-root
```

---

### 6.3 Emergency Maintenance Mode

Use this immediately when a disaster is detected, before starting recovery, to prevent data corruption from concurrent requests.

```bash
WP_PATH="/var/www/html"

# Enable maintenance mode
wp --path="$WP_PATH" maintenance-mode activate --allow-root

# Verify
wp --path="$WP_PATH" maintenance-mode status --allow-root
# Expected: Maintenance mode is active.

# Disable once recovery is complete
wp --path="$WP_PATH" maintenance-mode deactivate --allow-root
```

Alternatively, create the file manually (works even when WP-CLI is unavailable):

```bash
# Enable (creates .maintenance file)
echo '<?php $upgrading = time(); ?>' > /var/www/html/.maintenance

# Disable
rm -f /var/www/html/.maintenance
```

---

## 7. Disaster Scenarios & Responses

---

### Scenario 1: Complete Server Failure

**Description:** The web server is unresponsive — no HTTP(S) responses, SSH unreachable, or the host has terminated the instance.

**Indicators:**
- Site returns no response / connection timeout
- Monitoring alerts: HTTP health check failing
- `GET /pearblog/v1/health` returns no response

**RTO:** 30 minutes  
**RPO:** 24 hours (last daily backup)

**Recovery Procedure:**

```
Time 0:00 — Detection
  □ Confirm outage: curl -I https://yourdomain.com
  □ Check hosting provider status page
  □ Contact hosting support if infrastructure issue

Time 0:05 — Enable DNS failover (§6.1)
  □ Point DNS to standby server or CDN static failover page
  □ Notify team via emergency contact list (§3)

Time 0:10 — Provision replacement server
  □ Spin up new server (same specs as production)
  □ Install: PHP 8.0+, MySQL 5.7+, Apache/Nginx, WP-CLI
  □ Install: Composer (for PearBlog vendor dependencies)

Time 0:20 — Restore site
  □ Run full restore script (§5.3)
  □ Set environment variables (DB_NAME, DB_USER, etc.)
  □ bash pearblog-full-restore.sh <latest-db.sql.gz> <latest-files.tar.gz>

Time 0:25 — Verify
  □ curl -I https://yourdomain.com  — expect 200
  □ wp pearblog stats
  □ wp cron event list | grep pearblog
  □ Visit admin panel, check PearBlog settings

Time 0:30 — Restore DNS to new server
  □ Update DNS A record to new server IP
  □ Remove DNS failover (standby)

Time 0:35 — Notify stakeholders
  □ Send incident summary via email/Slack
  □ Complete incident log (§9)
```

---

### Scenario 2: Database Corruption or Accidental Deletion

**Description:** The WordPress database is corrupt, partially deleted, or the wrong database was dropped.

**Indicators:**
- WordPress shows "Error establishing a database connection"
- Admin panel is blank or throws PHP errors
- `wp db check` reports corrupt tables
- `wp option get siteurl` fails

**RTO:** 30 minutes  
**RPO:** 24 hours

**Recovery Procedure:**

```bash
# Step 1: Identify the problem
wp --path=/var/www/html db check --allow-root 2>&1 | head -30

# Step 2: If partial corruption — try repair first
wp --path=/var/www/html db repair --allow-root
wp --path=/var/www/html db check --allow-root

# Step 3: If repair fails — restore from backup
wp --path=/var/www/html maintenance-mode activate --allow-root

LATEST_DB=$(ls -t /var/backups/pearblog/daily/*-db.sql.gz | head -1)
echo "Restoring: $LATEST_DB"
bash /usr/local/bin/pearblog-db-restore.sh "$LATEST_DB"

# Step 4: Verify critical tables
wp --path=/var/www/html db query \
  "SELECT COUNT(*) FROM wp_posts WHERE post_status='publish';" --allow-root

wp --path=/var/www/html option get pearblog_openai_api_key --allow-root

# Step 5: Re-run pipeline to fill any content gap
wp --path=/var/www/html pearblog generate --allow-root

# Step 6: Disable maintenance mode
wp --path=/var/www/html maintenance-mode deactivate --allow-root
```

**PearBlog-specific data loss assessment:**

```bash
WP="wp --path=/var/www/html --allow-root"

# How many posts were published after the last backup?
BACKUP_DATE=$(stat -c %y /var/backups/pearblog/daily/$(ls -t /var/backups/pearblog/daily/*-db.sql.gz | head -1 | xargs basename) | cut -d' ' -f1)
echo "Backup date: $BACKUP_DATE"

$WP post list \
  --post_status=publish \
  --after="$BACKUP_DATE" \
  --format=count
# This is the number of posts that may need to be regenerated
```

---

### Scenario 3: AI Pipeline Failure / OpenAI Outage

**Description:** The content pipeline stops producing articles due to OpenAI API errors, circuit breaker tripping, or API key revocation.

**Indicators:**
- `wp pearblog stats` shows no new posts today
- `pearblog_ai_circuit_state` option shows `"open":true`
- PHP error log contains `AIClient` exceptions
- OpenAI status page (https://status.openai.com) shows incident

**RTO:** 2 hours  
**RPO:** N/A (no data loss — pipeline simply pauses)

**Recovery Procedure:**

```bash
WP="wp --path=/var/www/html --allow-root"

# Step 1: Check circuit breaker state
$WP option get pearblog_ai_circuit_state
# If "open":true — circuit is open, no AI calls are being made

# Step 2: Check OpenAI API key
$WP option get pearblog_openai_api_key
# If empty — key has been cleared; re-add via admin panel

# Step 3: Check current spend vs limits
$WP option get pearblog_ai_cost_cents
# High value may mean API quota exhausted

# Step 4: If OpenAI is operational — reset circuit breaker
$WP pearblog circuit reset
# Equivalent to: wp eval 'PearBlogEngine\AI\AIClient::reset_circuit();'

# Step 5: Manually trigger a pipeline run to verify recovery
$WP pearblog generate

# Step 6: Check result
$WP pearblog stats

# Step 7: If API key was revoked, update it
$WP option update pearblog_openai_api_key "sk-new-key-here"

# Step 8: Re-seed topic queue if it was drained
$WP pearblog queue add "Topic 1"
$WP pearblog queue add "Topic 2"
$WP pearblog queue list
```

**If OpenAI is down (external outage):**

- The circuit breaker will automatically prevent further failed requests.
- No action needed during the outage — pipeline will resume automatically once the circuit resets (after `CIRCUIT_COOLDOWN_SECONDS = 300`).
- Monitor https://status.openai.com for restoration.
- After OpenAI recovers, run `wp pearblog circuit reset` to clear the open circuit immediately rather than waiting for cooldown.

---

### Scenario 4: Ransomware / Malware Infection

**Description:** The server or WordPress installation has been compromised by malware, ransomware, or a defacement attack.

**Indicators:**
- Site content replaced with defacement page
- Unknown PHP files in wp-content directory
- Admin users added without authorization
- wp-config.php modified
- Hosting provider suspension notice

**RTO:** 2–4 hours  
**RPO:** 24 hours (before infection)

**⚠️ CRITICAL: Do NOT restore to the same infected environment without cleaning.**

**Recovery Procedure:**

```
Step 1 — Isolate immediately
  □ Enable server firewall — block all inbound except from your IP
  □ Take the site offline (DNS → maintenance page)
  □ Notify hosting provider

Step 2 — Preserve evidence (before wiping)
  □ Snapshot the infected disk for forensic analysis
  □ Copy access logs: /var/log/apache2/access.log (or nginx)
  □ Document timeline of infection

Step 3 — Provision clean server
  □ Use a NEW server or freshly wiped OS — do NOT reuse infected environment
  □ Install fresh OS, PHP, MySQL, Apache/Nginx
  □ Apply all OS security patches immediately

Step 4 — Restore from PRE-INFECTION backup
  □ Identify the last clean backup (before infection date)
  □ bash pearblog-full-restore.sh <clean-db.sql.gz> <clean-files.tar.gz>

Step 5 — Harden immediately after restore
  □ Rotate ALL credentials:
     - WordPress admin passwords
     - Database password
     - OpenAI API key (revoke old, issue new)
     - All pearblog_social_* tokens
     - SSH keys
     - Hosting control panel password

  □ Run: wp --path=/var/www/html user list --role=administrator --allow-root
    → Remove any admin users not recognized

  □ Verify wp-config.php has not been modified:
    diff wp-config.php <(git show HEAD:wp-config.php) || echo "CONFIG MODIFIED"

  □ Set file permissions:
    find /var/www/html -type f -name "*.php" -exec chmod 644 {} \;
    chmod 600 /var/www/html/wp-config.php

Step 6 — Install security monitoring
  □ Install Wordfence or Sucuri for ongoing file integrity monitoring
  □ Enable fail2ban for SSH and WordPress login protection
  □ Configure ModSecurity WAF rules

Step 7 — Re-enable site
  □ Restore DNS to new server
  □ Verify SSL certificate is valid and redirecting HTTP→HTTPS
  □ Test site, admin panel, and pipeline
```

---

### Scenario 5: WordPress Core / Plugin Update Breaks Site

**Description:** A WordPress core, plugin, or theme update has caused PHP fatal errors or broken the site.

**Indicators:**
- White screen of death (WSoD) after update
- PHP errors in `/wp-content/debug.log`
- Admin panel inaccessible
- `wp --path=/var/www/html eval 'echo "OK";'` returns error

**RTO:** 30 minutes  
**RPO:** N/A (no data loss — only code rollback needed)

**Recovery Procedure:**

```bash
WP="wp --path=/var/www/html --allow-root"

# Step 1: Enable maintenance mode to stop user-facing errors
echo '<?php $upgrading = time(); ?>' > /var/www/html/.maintenance

# Step 2: Check PHP error log for root cause
tail -50 /var/log/php8.0-fpm.log 2>/dev/null || \
  tail -50 /var/www/html/wp-content/debug.log 2>/dev/null

# Step 3a: If PearBlog plugin caused the break — restore plugin files
LATEST_FILES=$(ls -t /var/backups/pearblog/daily/*-files.tar.gz | head -1)
tar -xzf "$LATEST_FILES" \
  "wp-content/mu-plugins/pearblog-engine/" \
  -C /var/www/html/ 2>/dev/null || \
  tar -xzf "$LATEST_FILES" -C /

# Step 3b: If WordPress core update caused the break
$WP core update --version=$(wp core version --path=/var/www/html --allow-root) --force

# Step 3c: If a third-party plugin caused the break — deactivate it
$WP plugin deactivate <problem-plugin-slug>

# Step 4: Verify PHP syntax of PearBlog plugin
find /var/www/html/wp-content/mu-plugins/pearblog-engine/src/ \
  -name "*.php" -exec php -l {} \; 2>&1 | grep -v "No syntax errors"

# Step 5: Re-enable site
rm -f /var/www/html/.maintenance

# Step 6: Test
$WP eval 'echo "WordPress OK\n";'
$WP pearblog stats
```

---

### Scenario 6: Accidental Mass Post Deletion

**Description:** A bulk action in the admin panel or a WP-CLI command has accidentally deleted published posts.

**Indicators:**
- Post count drops dramatically
- `wp post list --post_status=publish --format=count` returns unexpectedly low number
- Admin audit log shows bulk trash/delete action

**RTO:** 1 hour  
**RPO:** 24 hours (last daily backup)

**Recovery Procedure:**

```bash
WP="wp --path=/var/www/html --allow-root"

# Step 1: Check trash first — WordPress soft-deletes to trash
TRASH_COUNT=$($WP post list --post_status=trash --format=count)
echo "Posts in trash: $TRASH_COUNT"

# Step 2: Restore from trash if possible (no data loss)
$WP post list --post_status=trash --format=ids | \
  xargs -n1 $WP post update --post_status=publish
echo "Restored from trash."

# Step 3: If posts were permanently deleted — restore from DB backup
echo "Enabling maintenance mode..."
$WP maintenance-mode activate

# Create a temporary restore database to extract posts
DB_TMP="pearblog_restore_$(date +%Y%m%d_%H%M%S)"
mysql -u root -p -e "CREATE DATABASE $DB_TMP;"
LATEST_DB=$(ls -t /var/backups/pearblog/daily/*-db.sql.gz | head -1)
zcat "$LATEST_DB" | mysql -u root -p "$DB_TMP"

# Export the deleted posts from backup DB
mysql -u root -p "$DB_TMP" -e "
  SELECT p.ID, p.post_title, p.post_content, p.post_date, p.post_name
  FROM wp_posts p
  WHERE p.post_status = 'publish'
    AND p.post_type = 'post'
  INTO OUTFILE '/tmp/pearblog-deleted-posts.csv'
  FIELDS TERMINATED BY ',' ENCLOSED BY '\"'
  LINES TERMINATED BY '\n';
"

echo "Posts from backup exported to /tmp/pearblog-deleted-posts.csv"
echo "Review and re-import as needed via wp post create"

# Alternatively — do a targeted restore of just wp_posts and wp_postmeta
# from the backup DB to the live DB (advanced, requires careful execution):
# mysqldump -u root -p $DB_TMP wp_posts wp_postmeta | mysql -u root -p $(wp db tables --all-tables | head -1 | sed 's/wp_posts//')

# Step 4: Clean up temp database
mysql -u root -p -e "DROP DATABASE $DB_TMP;"

# Step 5: Disable maintenance mode
$WP maintenance-mode deactivate
```

---

### Scenario 7: DDoS Attack / Traffic Spike

**Description:** The site is receiving abnormally high traffic, causing server overload or unresponsiveness.

**Indicators:**
- Server CPU/memory at 100%
- Site loads extremely slowly or times out
- PHP-FPM worker pool exhausted
- MySQL `max_connections` reached

**RTO:** 15 minutes  
**RPO:** N/A (no data loss)

**Immediate Response:**

```bash
# Step 1: Check current connection count
ss -tn state ESTABLISHED '( dport = :80 or dport = :443 )' | wc -l

# Step 2: Identify top offending IPs
netstat -ntu | awk '{print $5}' | cut -d: -f1 | sort | uniq -c | sort -nr | head -20

# Step 3: Block top offending IPs via iptables (temporary)
iptables -I INPUT -s 1.2.3.4 -j DROP
iptables -I INPUT -s 5.6.7.8 -j DROP

# Step 4: Enable Cloudflare "Under Attack" mode (if using Cloudflare)
CF_TOKEN="your-api-token"
ZONE_ID="your-zone-id"
curl -s -X PATCH \
  "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/settings/security_level" \
  -H "Authorization: Bearer $CF_TOKEN" \
  -H "Content-Type: application/json" \
  --data '{"value":"under_attack"}'

# Step 5: Pause PearBlog pipeline to free server resources
wp --path=/var/www/html pearblog autopilot pause --allow-root 2>/dev/null || \
  wp --path=/var/www/html option update pearblog_autonomous_mode 0 --allow-root

# Step 6: Enable PHP-FPM slow log and emergency mode
# In /etc/php/8.0/fpm/pool.d/www.conf:
# pm.max_children = 5   (reduce to protect server)
# Then: systemctl reload php8.0-fpm

# Step 7: Monitor recovery
watch -n5 'ss -tn state ESTABLISHED "( dport = :80 or dport = :443 )" | wc -l'

# Step 8: Once attack subsides — restore normal settings
wp --path=/var/www/html option update pearblog_autonomous_mode 1 --allow-root
```

---

### Scenario 8: SSL Certificate Expiry

**Description:** The SSL certificate has expired, causing browsers to show security warnings and blocking HTTPS access.

**Indicators:**
- Browser shows "Your connection is not private" / `NET::ERR_CERT_DATE_INVALID`
- `curl -I https://yourdomain.com` returns SSL error
- Monitoring alert: HTTPS health check failing

**RTO:** 15 minutes (Let's Encrypt) / 1–24 hours (commercial cert)  
**RPO:** N/A

**Recovery — Let's Encrypt:**

```bash
# Renew immediately
certbot renew --force-renewal --domain yourdomain.com

# Reload web server
systemctl reload nginx   # or: systemctl reload apache2

# Verify
echo | openssl s_client -connect yourdomain.com:443 2>/dev/null \
  | openssl x509 -noout -dates
```

**Prevention — automated renewal cron:**

```cron
0 3 * * * certbot renew --quiet && systemctl reload nginx
```

**Recovery — Commercial certificate:**

1. Generate a new CSR: `openssl req -new -newkey rsa:2048 -nodes -keyout yourdomain.key -out yourdomain.csr`
2. Purchase/renew certificate from your CA.
3. Install new certificate files in your web server config.
4. Reload web server: `systemctl reload nginx`

---

## 8. Post-Recovery Verification Checklist

Run this checklist after recovering from any disaster to confirm the site is fully operational.

```bash
#!/usr/bin/env bash
# Post-recovery verification for PearBlog Engine
set -euo pipefail

WP="wp --path=${PEARBLOG_WP_PATH:-/var/www/html} --allow-root"
SITE_URL=$($WP option get siteurl 2>/dev/null || echo "UNKNOWN")
PASS=0; FAIL=0

check() {
  local desc="$1"; local cmd="$2"; local expect="${3:-}"
  local result
  result=$(eval "$cmd" 2>&1) || result="ERROR"
  if [[ -n "$expect" && "$result" != *"$expect"* ]]; then
    echo "  ✗ FAIL: $desc (got: ${result:0:80})"
    ((FAIL++))
  else
    echo "  ✓ PASS: $desc"
    ((PASS++))
  fi
}

echo "=== PearBlog Engine Post-Recovery Verification ==="
echo "Site: $SITE_URL"
echo ""

echo "── WordPress Core ──────────────────────────────"
check "Site responds to HTTP" \
  "curl -sI '$SITE_URL' | grep -c '200\|301\|302'" "1"
check "WordPress is installed" \
  "$WP core is-installed && echo OK" "OK"
check "No database errors" \
  "$WP db check 2>&1 | tail -1" "OK"

echo ""
echo "── PearBlog Plugin ─────────────────────────────"
check "OpenAI API key is set" \
  "[[ -n \$($WP option get pearblog_openai_api_key) ]] && echo SET" "SET"
check "Autonomous mode is enabled" \
  "$WP option get pearblog_autonomous_mode" "1"
check "Circuit breaker is closed" \
  "$WP option get pearblog_ai_circuit_state" "false"
check "Topic queue exists" \
  "$WP option get pearblog_topic_queue > /dev/null && echo OK" "OK"
check "Duplicate check enabled" \
  "$WP option get pearblog_duplicate_check_enabled" "1"

echo ""
echo "── WP-Cron ─────────────────────────────────────"
check "Pipeline cron is scheduled" \
  "$WP cron event list 2>/dev/null | grep -c pearblog_run_pipeline" "1"

echo ""
echo "── Content ─────────────────────────────────────"
POST_COUNT=$($WP post list --post_status=publish --format=count 2>/dev/null || echo 0)
echo "  ℹ  Published posts: $POST_COUNT"
check "At least 1 published post" \
  "[[ $POST_COUNT -gt 0 ]] && echo OK" "OK"

echo ""
echo "── SSL Certificate ─────────────────────────────"
if [[ "$SITE_URL" == https://* ]]; then
  DOMAIN=$(echo "$SITE_URL" | sed 's|https://||' | cut -d'/' -f1)
  CERT_EXPIRY=$(echo | openssl s_client -connect "$DOMAIN:443" 2>/dev/null \
    | openssl x509 -noout -enddate 2>/dev/null | cut -d= -f2)
  echo "  ℹ  SSL expires: $CERT_EXPIRY"
fi

echo ""
echo "── REST API ────────────────────────────────────"
HEALTH_URL="$SITE_URL/wp-json/pearblog/v1/health"
HEALTH_RESP=$(curl -s -o /dev/null -w "%{http_code}" "$HEALTH_URL" 2>/dev/null || echo "000")
if [[ "$HEALTH_RESP" == "200" ]]; then
  echo "  ✓ PASS: Health endpoint ($HEALTH_URL) → $HEALTH_RESP"
  ((PASS++))
else
  echo "  ✗ FAIL: Health endpoint returned $HEALTH_RESP (expected 200)"
  ((FAIL++))
fi

echo ""
echo "══════════════════════════════════════════════════"
echo "Results: $PASS passed, $FAIL failed"
if [[ $FAIL -gt 0 ]]; then
  echo "⚠  Address the failing checks before declaring recovery complete."
  exit 1
else
  echo "✅ All checks passed. Site is fully operational."
fi
```

---

## 9. Incident Log Template

Complete this log for every disaster event, regardless of severity. Store completed logs in a secure location.

```markdown
# Incident Log: [INCIDENT TITLE]

## Metadata
- **Incident ID:** INC-[YYYY-MM-DD]-[NN]
- **Date/Time Detected:** [YYYY-MM-DD HH:MM UTC]
- **Date/Time Resolved:** [YYYY-MM-DD HH:MM UTC]
- **Total Downtime:** [X hours Y minutes]
- **Severity:** P0 / P1 / P2 / P3
- **Scenario Type:** [Server / DB / AI Pipeline / Malware / Update / Post Deletion / DDoS / SSL / Other]

## Impact
- **Affected components:** [List]
- **Data lost:** [None / Description and time range]
- **Posts not published during incident:** [Count / time range]
- **Users affected:** [Estimated count]

## Timeline
| Time (UTC) | Action | By |
|-----------|--------|----|
| HH:MM | Incident detected | [Name / monitoring] |
| HH:MM | Team notified | [Name] |
| HH:MM | [Action taken] | [Name] |
| HH:MM | Site restored | [Name] |
| HH:MM | Verification complete | [Name] |

## Root Cause
[Detailed description of what caused the incident]

## What Went Well
- [Item]
- [Item]

## What Could Be Improved
- [Item]
- [Item]

## Action Items
| # | Action | Owner | Due Date | Status |
|---|--------|-------|----------|--------|
| 1 | [Description] | [Name] | [Date] | Open |
| 2 | [Description] | [Name] | [Date] | Open |

## Backup Used
- DB backup file: [filename]
- Files backup file: [filename]
- Backup age at restore: [X hours]

## Notes
[Any additional context, screenshots, or log excerpts]
```

---

## 10. Preventive Measures

### Automated Monitoring

1. **Enable the PearBlog health endpoint** and monitor it every 5 minutes:
   ```
   GET https://yourdomain.com/wp-json/pearblog/v1/health
   Expected: HTTP 200, JSON body with status=ok
   ```

2. **Configure AlertManager** with at least one notification channel:
   ```bash
   wp option update pearblog_alert_slack_webhook "https://hooks.slack.com/services/..."
   wp option update pearblog_alert_email "ops@yourdomain.com"
   wp option update pearblog_alert_on_publish 1
   ```

3. **Set up uptime monitoring** (UptimeRobot, Better Uptime, or Pingdom) for:
   - `https://yourdomain.com` (5-minute interval)
   - `https://yourdomain.com/wp-json/pearblog/v1/health` (5-minute interval)
   - SSL certificate expiry (alert at 30 days before expiry)

### Regular Maintenance Schedule

| Frequency | Task |
|-----------|------|
| Daily | Verify backup completed via `/var/log/pearblog-backup.log` |
| Weekly | Run backup verification script (`pearblog-backup-verify.sh`) |
| Weekly | Review PHP error log: `tail -100 /var/www/html/wp-content/debug.log` |
| Weekly | Check AI cost: `wp option get pearblog_ai_cost_cents` |
| Monthly | Test restore to staging server (fire drill) |
| Monthly | Review circuit breaker history in PHP error log |
| Monthly | Rotate API keys (OpenAI, social tokens) |
| Quarterly | Full disaster recovery drill (Scenario 1 + Scenario 2) |

### WordPress Security Hardening

```bash
# 1. Restrict wp-config.php access
chmod 600 /var/www/html/wp-config.php

# 2. Disable XML-RPC (not used by PearBlog)
# Add to .htaccess:
echo '<Files xmlrpc.php>
Order Deny,Allow
Deny from all
</Files>' >> /var/www/html/.htaccess

# 3. Hide WordPress version
wp option update --path=/var/www/html hide_version 1 --allow-root 2>/dev/null || true

# 4. Set strong table prefix (on fresh installs via wp-config.php)
# Change $table_prefix from 'wp_' to a random prefix, e.g. 'pb7x_'

# 5. Keep WordPress and all plugins updated
wp --path=/var/www/html core update --allow-root
wp --path=/var/www/html plugin update --all --allow-root
```

### OpenAI API Protection

```bash
# 1. Set a monthly spend limit at https://platform.openai.com/account/limits
# Recommended: 1.5× your typical monthly spend

# 2. Monitor spend via PearBlog
wp --path=/var/www/html option get pearblog_ai_cost_cents --allow-root
# Divide by 100 for USD amount

# 3. Reset cost counter monthly
wp --path=/var/www/html option update pearblog_ai_cost_cents 0 --allow-root

# 4. Set circuit breaker conservatively — reduce failure threshold if needed
# Edit AIClient.php: CIRCUIT_FAILURE_THRESHOLD = 3 (default 5)
```

---

## Related Documentation

| Document | Purpose |
|----------|---------|
| [DEPLOYMENT.md](DEPLOYMENT.md) | Initial deployment and server configuration |
| [DATABASE-MIGRATIONS.md](DATABASE-MIGRATIONS.md) | Schema reference and upgrade/rollback SQL |
| [PRODUCTION-CHECKLIST.md](PRODUCTION-CHECKLIST.md) | Pre-launch and weekly operations checklists |
| [PRODUCTION-ANALYSIS-FULL.md](PRODUCTION-ANALYSIS-FULL.md) | Full production operations manual |

---

*PearBlog Engine v6.0.0 — Enterprise-ready autonomous content system*

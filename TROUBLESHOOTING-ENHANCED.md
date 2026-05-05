# 🔧 Enhanced Troubleshooting Guide - PearBlog Engine v7.10.0

**Purpose**: Comprehensive diagnostic procedures and solutions for common and complex issues in the PearBlog Engine enterprise platform.

**Audience**: Site administrators, developers, DevOps engineers, support teams

**Last Updated**: 2026-05-03

---

## 📋 Table of Contents

1. [Quick Diagnostics](#quick-diagnostics)
2. [Content Generation Issues](#content-generation-issues)
3. [API & Authentication Problems](#api--authentication-problems)
4. [Performance Issues](#performance-issues)
5. [Monetization & AdSense](#monetization--adsense)
6. [Multisite/SaaS Problems](#multisitesaas-problems)
7. [Security Alerts](#security-alerts)
8. [Database Issues](#database-issues)
9. [Advanced Debugging](#advanced-debugging)
10. [Emergency Recovery](#emergency-recovery)

---

## 🩺 Quick Diagnostics

### Health Check Dashboard

**Location**: Admin Panel → Performance Tab → Health Status

**What to check first**:
```
1. Overall Status: Should be "OK" or "Degraded" (not "Down")
2. API Key: Should show "configured"
3. Circuit Breaker: Should be "closed"
4. OpenAI: Should show "reachable"
5. Queue: Check number of topics waiting
6. Last Run: Should be within last 24-48 hours
```

### Manual Health Check via REST API

```bash
# Using header secret
curl -H "X-PearBlog-Health-Secret: YOUR_SECRET" \
     https://yoursite.com/wp-json/pearblog/v1/health

# Using query parameter
curl "https://yoursite.com/wp-json/pearblog/v1/health?health_secret=YOUR_SECRET"
```

**Expected Response** (healthy system):
```json
{
  "overall": "ok",
  "timestamp": "2026-05-03 14:30:00",
  "api_key": {
    "status": "ok",
    "detail": "configured"
  },
  "circuit_breaker": {
    "status": "ok",
    "detail": "closed"
  },
  "openai": {
    "status": "ok",
    "detail": "reachable"
  },
  "queue": {
    "status": "ok",
    "detail": "5 topics waiting",
    "count": 5
  },
  "last_run": {
    "status": "ok",
    "detail": "2026-05-03 12:00:00",
    "hours_since": 2.5
  }
}
```

### System Requirements Check

```bash
# PHP version (must be 8.1+)
php -v

# WordPress version (must be 6.4+)
wp core version

# Check memory limit (should be 256M+)
php -r "echo ini_get('memory_limit');"

# Check max execution time (should be 300+)
php -r "echo ini_get('max_execution_time');"

# Verify cURL is enabled
php -m | grep curl

# Check disk space (need 5GB+ free)
df -h
```

---

## ✍️ Content Generation Issues

### Problem: "No articles are being generated"

**Symptoms**: Queue has topics but no posts published, last_run timestamp hasn't updated.

**Diagnostic Steps**:

1. **Check WP-Cron status**:
```bash
wp cron event list | grep pearblog
```

Expected output:
```
pearblog_pipeline_run    2026-05-03 15:00:00    hourly
```

2. **Verify API key is configured**:
```bash
wp option get pearblog_openai_api_key
```

3. **Check circuit breaker state**:
```php
// In wp-admin/admin-ajax.php debug
$is_open = get_transient( 'pearblog_circuit_breaker_open' );
echo $is_open ? 'OPEN (blocked)' : 'CLOSED (ok)';
```

4. **Test API connectivity manually**:
```bash
curl -H "Authorization: Bearer YOUR_API_KEY" \
     https://api.openai.com/v1/models
```

**Solutions**:

| Issue | Solution |
|-------|----------|
| WP-Cron not running | Enable system cron: `0 * * * * wp cron event run --due-now --path=/var/www/html` |
| API key missing | Admin Panel → Settings → OpenAI API Key |
| Circuit breaker open | Wait 5 minutes or force reset: `delete_transient('pearblog_circuit_breaker_open')` |
| Rate limit hit | Reduce pipeline frequency or upgrade OpenAI plan |
| Out of quota | Check OpenAI dashboard for usage limits |

### Problem: "Articles generated but quality is poor"

**Diagnostic Steps**:

1. **Review prompt templates**:
   - Admin Panel → Strategy (AI) → Prompt Templates
   - Check if custom templates match your niche

2. **Check model selection**:
```bash
wp option get pearblog_openai_model
```
Expected: `gpt-4` or `gpt-4-turbo` (NOT `gpt-3.5-turbo` for quality content)

3. **Review funnel stage detection**:
```php
// Check post meta
$stage = get_post_meta( $post_id, 'pearblog_funnel_stage', true );
echo "Funnel Stage: {$stage}"; // Should be TOFU/MOFU/BOFU
```

**Solutions**:

| Issue | Solution |
|-------|----------|
| Generic content | Customize prompt templates with industry-specific keywords |
| Wrong funnel stage | Update keyword lists in Strategy tab |
| Short articles | Increase target word count in Content Engine settings |
| No expert quotes | Enable "Expert Integration" in Leads tab |
| Poor SEO | Enable "Auto-optimize for SEO" in SEO Engine tab |

### Problem: "Pipeline runs but articles fail to publish"

**Diagnostic Steps**:

1. **Check error logs**:
```bash
tail -f wp-content/debug.log | grep "PearBlog"
```

2. **Verify post status**:
```bash
wp post list --post_status=draft --meta_key=pearblog_generated --meta_value=1
```

3. **Check for post insertion failures**:
```php
// Enable debug logging in wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

**Solutions**:

| Issue | Solution |
|-------|----------|
| Posts stuck in draft | Check "Auto-publish" setting in Content Engine tab |
| Duplicate content detection | Review de-duplication thresholds |
| Category assignment failure | Verify target categories exist |
| Featured image generation failed | Check Unsplash API key configuration |
| Post meta not saving | Increase PHP memory limit to 512M |

---

## 🔐 API & Authentication Problems

### Problem: "REST API returns 401 Unauthorized"

**Diagnostic Steps**:

1. **Test Bearer token authentication**:
```bash
# Get API key from database
wp option get pearblog_api_key

# Test with curl
curl -H "Authorization: Bearer YOUR_API_KEY" \
     https://yoursite.com/wp-json/pearblog/v1/generate
```

2. **Check for .htaccess issues** (Apache blocks Authorization header):
```bash
grep -i "authorization" /var/www/html/.htaccess
```

Expected:
```apache
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
```

3. **Test without Bearer token** (WordPress user session):
```bash
# Login as admin first, then:
curl --cookie-jar cookies.txt --cookie cookies.txt \
     https://yoursite.com/wp-json/pearblog/v1/health
```

**Solutions**:

| Issue | Solution |
|-------|----------|
| .htaccess blocks header | Add `SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1` |
| API key mismatch | Regenerate: Admin Panel → Settings → Regenerate API Key |
| Nonce verification failing | Disable nonce check for REST API (already implemented) |
| Rate limiting triggered | Check transient `failed_login_attempts_{md5(ip)}` |

### Problem: "Health endpoint returns 403 Forbidden"

**Diagnostic Steps**:

1. **Verify health secret is configured**:
```bash
wp option get pearblog_health_secret
```

2. **Test with correct secret**:
```bash
curl -H "X-PearBlog-Health-Secret: YOUR_SECRET" \
     https://yoursite.com/wp-json/pearblog/v1/health
```

3. **Check permission callback** (HealthController.php:208):
```php
private function authorize_request( \WP_REST_Request $request ): bool {
    // Requires either health secret OR manage_options capability
}
```

**Solutions**:

| Issue | Solution |
|-------|----------|
| Secret not set | Admin Panel → Settings → Health Endpoint Secret → Save |
| Wrong header name | Use `X-PearBlog-Health-Secret` (case-insensitive) |
| Query param not working | Use `?health_secret=SECRET` as fallback |
| Both methods fail | Login as admin and access endpoint (bypasses secret check) |

---

## ⚡ Performance Issues

### Problem: "Admin panel is slow to load"

**Diagnostic Steps**:

1. **Enable Query Monitor plugin**:
```bash
wp plugin install query-monitor --activate
```

2. **Check database query count**:
   - Load Admin Panel → Performance tab
   - Query Monitor shows query count in admin bar
   - **Good**: < 100 queries per page
   - **Bad**: > 500 queries per page

3. **Profile PHP execution**:
```bash
# Enable Xdebug profiling
php -d xdebug.mode=profile wp-admin/admin.php?page=pearblog-engine-v7
```

4. **Check for plugin conflicts**:
```bash
# Disable all other plugins
wp plugin deactivate --all --exclude=pearblog-engine

# Test admin panel speed
# Re-enable plugins one by one to find conflict
```

**Solutions**:

| Issue | Solution |
|-------|----------|
| Too many database queries | Enable object caching (Redis/Memcached) |
| Slow API calls | Increase transient cache TTL (default: 5 min) |
| Large admin pages | Implement pagination for large data tables |
| Unoptimized CSS/JS | Run `wp-cli` minification |
| Plugin conflicts | Deactivate conflicting plugins or update to latest versions |

### Problem: "Pipeline runs are timing out"

**Diagnostic Steps**:

1. **Check current execution time limit**:
```bash
php -r "echo ini_get('max_execution_time');"
```

2. **Monitor pipeline runtime**:
   - Admin Panel → Performance → Recent Pipeline Runs
   - Look for runs with execution_time > 300 seconds

3. **Check for API rate limiting**:
```bash
tail -f wp-content/debug.log | grep "rate_limited"
```

**Solutions**:

| Issue | Solution |
|-------|----------|
| PHP timeout | Increase `max_execution_time` to 600 in php.ini |
| Memory exhaustion | Increase `memory_limit` to 512M |
| API rate limiting | Add delays between API calls (use circuit breaker) |
| Large article generation | Split into smaller batches |
| Network timeouts | Increase `WP_HTTP_TIMEOUT` constant |

### Problem: "High API costs"

**Diagnostic Steps**:

1. **Check total API spend**:
   - Admin Panel → Performance → AI Cost
   - Should show cumulative USD cost

2. **Analyze per-article costs**:
```bash
wp post list --meta_key=pearblog_ai_cost_cents --format=csv > costs.csv
```

3. **Review model usage**:
```bash
wp option get pearblog_openai_model
```

**Solutions**:

| Issue | Solution |
|-------|----------|
| Using GPT-4 unnecessarily | Switch to gpt-4-turbo or gpt-3.5-turbo for TOFU content |
| Too many API calls per article | Reduce prompt complexity (fewer iterations) |
| Large context windows | Shorten prompts, remove unnecessary examples |
| High token usage | Enable response caching for repeated queries |
| No cost tracking | Enable detailed logging in AIClient.php |

---

## 💰 Monetization & AdSense

### Problem: "AdSense ads not showing"

**Diagnostic Steps**:

1. **Verify revenue features enabled**:
```bash
wp option get pearblog_v7_revenue_enabled
# Should return: 1
```

2. **Check publisher ID configured**:
```bash
wp option get pearblog_adsense_publisher_id
# Should return: ca-pub-XXXXXXXXXXXXXXXX
```

3. **Verify funnel-aware strategy**:
```bash
wp option get pearblog_adsense_strategy
# Should return: funnel_aware
```

4. **Check post funnel stage**:
```bash
wp post meta get POST_ID pearblog_funnel_stage
# Should return: TOFU, MOFU, or BOFU
```

5. **Verify stage is enabled**:
```bash
# TOFU enabled?
wp option get pearblog_adsense_enable_tofu  # Should be 1

# MOFU enabled?
wp option get pearblog_adsense_enable_mofu  # Should be 1

# BOFU enabled?
wp option get pearblog_adsense_enable_bofu  # Should be 0 (disabled by default)
```

**Solutions**:

| Issue | Solution |
|-------|----------|
| Revenue features disabled | Admin Panel → Monetization → Enable Revenue Features |
| Publisher ID missing | Admin Panel → Monetization → AdSense Publisher ID |
| BOFU ads showing | Disable BOFU ads (they hurt conversion) |
| Ads in wrong positions | Adjust injection logic in `inject_ads()` method |
| AdSense policy violation | Review Google AdSense policies for prohibited content |

### Problem: "Revenue tracking not working"

**Diagnostic Steps**:

1. **Check if impressions are being tracked**:
```bash
wp post meta get POST_ID pearblog_ad_impressions
# Should return a number > 0 if ads have been served
```

2. **Verify RPM (Revenue Per Mille) is set**:
```bash
wp option get pearblog_adsense_rpm
# Should return a decimal value (e.g., 2.50)
```

3. **Check revenue calculation**:
```php
// In mu-plugins/pearblog-engine/tests/php/Integration/MonetizationIntegrationTest.php:179
$revenue = ( $impressions / 1000 ) * $rpm;
```

**Solutions**:

| Issue | Solution |
|-------|----------|
| Impressions not tracked | Integrate with Google AdSense API for real-time data |
| RPM not configured | Set estimated RPM in Admin Panel → Monetization → Settings |
| Revenue not calculating | Check calculation logic in MonetizationIntegrationTest.php:389 |
| Top earners not showing | Verify post meta is being saved correctly |

### Problem: "Affiliate links not being added"

**Diagnostic Steps**:

1. **Check affiliate disclosure setting**:
```bash
wp option get pearblog_affiliate_disclosure
# Should return disclosure text
```

2. **Verify link injection logic**:
   - Review `inject_affiliate_id()` in MonetizationIntegrationTest.php:411
   - Check regex pattern for URL matching

3. **Test tracking parameter addition**:
```bash
# Expected output: https://example.com/product?ref=partner-123
```

**Solutions**:

| Issue | Solution |
|-------|----------|
| Disclosure not showing | Admin Panel → Monetization → Affiliate Disclosure → Enable |
| Links not transformed | Check regex pattern in `inject_affiliate_id()` |
| Tracking params missing | Verify `add_tracking_parameter()` method implementation |
| Affiliate networks not supported | Add custom link transformation logic per network |

---

## 🌐 Multisite/SaaS Problems

### Problem: "Sites cannot access network settings"

**Diagnostic Steps**:

1. **Verify multisite is enabled**:
```bash
wp eval 'if (defined("MULTISITE") && MULTISITE) { echo "yes"; } else { echo "no"; }'
# Should return: yes
```

2. **Check network-wide options**:
```bash
wp site option get pearblog_centralized_api_keys
# Should return array with API keys
```

3. **Test site switching**:
```bash
wp site list --field=url
# Switch to site 2:
wp eval --url=https://site2.example.com "echo get_current_blog_id();"
```

**Solutions**:

| Issue | Solution |
|-------|----------|
| MULTISITE not defined | Add `define('MULTISITE', true);` to wp-config.php |
| Network options not accessible | Use `get_site_option()` instead of `get_option()` |
| Site isolation broken | Verify `switch_to_blog()` and `restore_current_blog()` calls |
| API keys not inherited | Set keys at network level in Network Admin |

### Problem: "Tenant data is not isolated"

**Diagnostic Steps**:

1. **Check database prefixes**:
```bash
wp db query "SHOW TABLES LIKE 'wp_%';"
```
Each site should have its own prefix (wp_2_, wp_3_, etc.)

2. **Verify options isolation**:
```bash
# Site 1
wp option get pearblog_site_name --url=https://site1.example.com

# Site 2
wp option get pearblog_site_name --url=https://site2.example.com

# Should return different values
```

3. **Check post isolation**:
```bash
wp post list --url=https://site1.example.com --field=ID
wp post list --url=https://site2.example.com --field=ID
# IDs should be different sets
```

**Solutions**:

| Issue | Solution |
|-------|----------|
| Data leaking between sites | Ensure all queries use correct table prefix |
| Shared cache keys | Prefix transients with blog_id: `{$blog_id}_key` |
| Global variables polluted | Use `switch_to_blog()` / `restore_current_blog()` correctly |
| Database sharding needed | Implement custom DB routing (see MultitenantIntegrationTest.php:370) |

### Problem: "SSO not working across sites"

**Diagnostic Steps**:

1. **Check SSO token generation**:
```php
// In MultitenantIntegrationTest.php:237
$GLOBALS['_user_sessions'][$user_id] = [
    'token' => 'network_sso_token_123',
    'expires' => time() + 3600,
];
```

2. **Verify token sharing**:
```bash
# Login on site 1, then check cookie domain
wp option get pearblog_sso_cookie_domain
# Should be: .example.com (with leading dot)
```

3. **Test cross-site authentication**:
   - Login to site1.example.com
   - Navigate to site2.example.com
   - Should remain logged in

**Solutions**:

| Issue | Solution |
|-------|----------|
| Cookies not shared | Set cookie domain to `.example.com` in wp-config.php |
| Token validation failing | Use `hash_equals()` for timing-safe comparison |
| Session expired | Increase session timeout in SSO settings |
| Subdomain vs subdirectory | Ensure SUBDOMAIN_INSTALL matches your setup |

### Problem: "Usage quota not being enforced"

**Diagnostic Steps**:

1. **Check quota settings**:
```bash
wp option get pearblog_monthly_quota --url=https://site1.example.com
# Should return a number (e.g., 1000)
```

2. **Check current usage**:
```bash
wp option get pearblog_articles_this_month --url=https://site1.example.com
```

3. **Test enforcement logic** (MultitenantIntegrationTest.php:302):
```php
$remaining = $quota - $articles_this_month;
$can_generate = $remaining > 0;
```

**Solutions**:

| Issue | Solution |
|-------|----------|
| Quota not set | Admin Panel → Multisite → Set Monthly Quota per site |
| Usage not tracked | Increment counter on each article generation |
| Enforcement not working | Add check before pipeline runs |
| Overages allowed | Add hard limit in TopicQueue logic |

---

## 🚨 Security Alerts

### Problem: "Security audit shows vulnerabilities"

**Diagnostic Steps**:

1. **Run automated security audit**:
```php
// In wp-admin/admin-ajax.php or WP-CLI command
use PearBlogEngine\Security\SecurityAuditor;

$auditor = new SecurityAuditor();
$results = $auditor->run_full_audit();
print_r( $results );
```

2. **Review SECURITY-AUDIT-REPORT.md**:
```bash
cat SECURITY-AUDIT-REPORT.md | grep -A 5 "CRITICAL\|HIGH"
```

3. **Check specific OWASP categories**:
   - A01: Broken Access Control
   - A02: Cryptographic Failures
   - A03: Injection
   - A04: Insecure Design
   - A05: Security Misconfiguration
   - A06: Vulnerable Components
   - A07: Authentication Failures
   - A08: Integrity Failures
   - A09: Logging Failures
   - A10: SSRF

**Solutions**:

| Issue | Solution |
|-------|----------|
| Missing permission_callback | Add to all REST routes (already implemented) |
| SQL injection risk | Use `$wpdb->prepare()` for all queries (already implemented) |
| XSS vulnerabilities | Use `esc_html()`, `esc_attr()`, `esc_url()` (already implemented) |
| CSRF not protected | Add `wp_nonce_field()` to all forms (already implemented) |
| Insecure API keys | Store in environment variables, not database |

### Problem: "Rate limiting not blocking attacks"

**Diagnostic Steps**:

1. **Check failed login attempts**:
```bash
wp transient get failed_login_attempts_$(echo "192.168.1.100" | md5sum | cut -d' ' -f1)
```

2. **Verify IP blocking**:
```bash
wp transient get ip_blocked_$(echo "192.168.1.100" | md5sum | cut -d' ' -f1)
```

3. **Review thresholds** (AuthenticationIntegrationTest.php:246):
```php
if ( $attempts >= 5 ) {
    set_transient( "ip_blocked_{$ip_hash}", time() + 900, 15 * MINUTE_IN_SECONDS );
}
```

**Solutions**:

| Issue | Solution |
|-------|----------|
| Threshold too high | Lower from 5 to 3 failed attempts |
| Block duration too short | Increase from 15 min to 1 hour |
| IP spoofing | Use `REMOTE_ADDR` instead of forwarded headers |
| Distributed attacks | Implement CAPTCHA after 2 failed attempts |
| No permanent bans | Add logic for repeat offenders (3+ blocks = permanent) |

### Problem: "API keys exposed in logs"

**Diagnostic Steps**:

1. **Scan logs for API keys**:
```bash
grep -r "sk-" wp-content/debug.log
grep -r "Bearer" wp-content/debug.log
grep -r "openai_api_key" wp-content/debug.log
```

2. **Check WP_DEBUG status**:
```bash
wp eval "echo WP_DEBUG ? 'enabled' : 'disabled';"
# Should be 'disabled' in production
```

3. **Review error logging configuration**:
```php
// wp-config.php should have:
define( 'WP_DEBUG', false );           // Disable debug in production
define( 'WP_DEBUG_LOG', false );       // Disable debug.log in production
define( 'WP_DEBUG_DISPLAY', false );   // Never show errors on screen
```

**Solutions**:

| Issue | Solution |
|-------|----------|
| WP_DEBUG enabled in prod | Set `WP_DEBUG = false` in wp-config.php |
| API keys in error messages | Redact sensitive data before logging |
| Debug.log world-readable | Set permissions: `chmod 600 wp-content/debug.log` |
| Keys in Git history | Use `.env` files, add to .gitignore |
| Logs not rotated | Implement log rotation with logrotate |

---

## 🗄️ Database Issues

### Problem: "Database queries are slow"

**Diagnostic Steps**:

1. **Enable MySQL slow query log**:
```sql
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1; -- Log queries > 1 second
```

2. **Review slow queries**:
```bash
tail -f /var/log/mysql/slow-query.log
```

3. **Check for missing indexes**:
```sql
SELECT * FROM information_schema.STATISTICS
WHERE table_schema = 'wordpress'
  AND table_name LIKE 'wp_%postmeta';
```

4. **Analyze table structure**:
```bash
wp db query "SHOW CREATE TABLE wp_postmeta;"
```

**Solutions**:

| Issue | Solution |
|-------|----------|
| Missing indexes | Add index on meta_key: `ALTER TABLE wp_postmeta ADD INDEX idx_meta_key (meta_key(191));` |
| Table fragmentation | Optimize tables: `wp db optimize` |
| Large transient tables | Clean up expired transients: `wp transient delete --expired` |
| Unindexed queries | Use EXPLAIN to analyze queries |
| No query caching | Enable MySQL query cache or Redis |

### Problem: "Database backups failing"

**Diagnostic Steps**:

1. **Check backup cron job**:
```bash
crontab -l | grep backup
```

2. **Test manual backup**:
```bash
wp db export backup-$(date +%Y%m%d).sql
```

3. **Check disk space**:
```bash
df -h | grep /var/www
```

4. **Review backup script logs**:
```bash
tail -f /var/log/wordpress-backup.log
```

**Solutions**:

| Issue | Solution |
|-------|----------|
| Insufficient disk space | Clean up old backups, expand storage |
| Backup script not executable | `chmod +x /path/to/backup.sh` |
| Cron not running | Verify cron service: `systemctl status cron` |
| Large database timeout | Increase `max_execution_time` for backup script |
| No offsite backups | Sync backups to S3/Backblaze with rclone |

### Problem: "Database corruption detected"

**Diagnostic Steps**:

1. **Check for corruption**:
```bash
wp db check
```

2. **Repair tables**:
```bash
wp db repair
```

3. **Verify integrity**:
```sql
CHECK TABLE wp_posts;
CHECK TABLE wp_postmeta;
CHECK TABLE wp_options;
```

4. **Review error logs**:
```bash
grep -i "crash\|corrupt" /var/log/mysql/error.log
```

**Solutions**:

| Issue | Solution |
|-------|----------|
| Corrupted InnoDB tables | Run `mysqlcheck --auto-repair --all-databases` |
| Crashed MyISAM tables | Run `myisamchk --recover /var/lib/mysql/wordpress/*.MYI` |
| Replication lag | Check master/slave status: `SHOW SLAVE STATUS\G` |
| Out of disk space | Free up space, then repair tables |
| Power outage damage | Restore from most recent clean backup |

---

## 🔍 Advanced Debugging

### Enabling Debug Mode

**wp-config.php**:
```php
// Development only - NEVER in production
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', true );
define( 'SAVEQUERIES', true );

// PearBlog-specific debug
define( 'PEARBLOG_DEBUG', true );
define( 'PEARBLOG_LOG_LEVEL', 'debug' ); // debug, info, warning, error
```

### Logging Best Practices

```php
// In any PearBlog class
use PearBlogEngine\Monitoring\Logger;

// Log levels
Logger::debug( 'Debugging info', [ 'context' => $data ] );
Logger::info( 'Informational message' );
Logger::warning( 'Something unexpected happened' );
Logger::error( 'An error occurred', [ 'exception' => $e ] );
Logger::critical( 'System is unusable!' );

// Log to specific channel
Logger::channel( 'ai' )->info( 'API call completed', [
    'model' => 'gpt-4',
    'tokens' => 1500,
    'cost_cents' => 0.03,
] );
```

### Using Query Monitor

**Installation**:
```bash
wp plugin install query-monitor --activate
```

**Key Metrics to Watch**:
- Database Queries: < 50 per page (ideal), < 100 (acceptable)
- Query Time: < 0.05s per query
- Memory Usage: < 128MB per page load
- HTTP Requests: < 10 external requests
- Hooks & Actions: No duplicate hooks

### Xdebug Profiling

**php.ini**:
```ini
[xdebug]
zend_extension=xdebug.so
xdebug.mode=debug,profile
xdebug.start_with_request=trigger
xdebug.output_dir=/tmp/xdebug
xdebug.profiler_output_name=cachegrind.out.%t
```

**Trigger profiling**:
```bash
curl "https://yoursite.com/?XDEBUG_PROFILE=1" > /dev/null
```

**Analyze with kcachegrind**:
```bash
kcachegrind /tmp/xdebug/cachegrind.out.*
```

### WP-CLI Debugging

**Enable WP-CLI debug mode**:
```bash
wp --debug <command>
```

**Capture stack traces**:
```bash
wp eval "debug_print_backtrace();" > stacktrace.txt
```

**Profile WP-CLI commands**:
```bash
time wp pearblog pipeline run --verbose
```

---

## 🚑 Emergency Recovery

### Complete System Failure

**Symptoms**: Site completely down, 500 errors, database unreachable.

**Recovery Steps**:

1. **Enable WordPress maintenance mode**:
```bash
touch /var/www/html/.maintenance
```

2. **Check critical services**:
```bash
systemctl status nginx    # or apache2
systemctl status php8.1-fpm
systemctl status mysql
```

3. **Restart services**:
```bash
systemctl restart nginx
systemctl restart php8.1-fpm
systemctl restart mysql
```

4. **Restore from backup** (if services won't start):
```bash
# Restore database
wp db import /backups/latest-backup.sql

# Restore files
rsync -avz /backups/wordpress-files/ /var/www/html/

# Clear cache
wp cache flush
```

5. **Disable all plugins except PearBlog**:
```bash
wp plugin deactivate --all --exclude=pearblog-engine
```

6. **Test health endpoint**:
```bash
curl https://yoursite.com/wp-json/pearblog/v1/health
```

7. **Remove maintenance mode**:
```bash
rm /var/www/html/.maintenance
```

### Data Loss Recovery

**Scenario**: Accidentally deleted posts or critical data.

**Recovery Options**:

1. **Check database backups**:
```bash
ls -lh /backups/*.sql | tail -5
```

2. **Restore specific table** (if recent backup exists):
```sql
-- Extract wp_posts from backup
mysql -u root -p wordpress < backup_posts_only.sql
```

3. **Use WordPress revisions**:
```bash
wp post list --post_type=revision --post_parent=POST_ID
wp post get REVISION_ID --field=post_content
```

4. **Check transients for cached data**:
```bash
wp transient list | grep pearblog
wp transient get pearblog_cached_content_POST_ID
```

5. **Contact OpenAI for API call history** (if content was AI-generated):
   - Login to OpenAI dashboard
   - Review API request logs
   - May be able to retrieve prompts and responses

### Security Breach Recovery

**Scenario**: Site compromised, malware detected, unauthorized access.

**Immediate Actions**:

1. **Take site offline immediately**:
```bash
chmod 000 /var/www/html/index.php
```

2. **Change ALL passwords**:
```bash
# WordPress admin
wp user update admin --user_pass=NEW_SECURE_PASSWORD

# Database
mysql -u root -p -e "ALTER USER 'wordpress'@'localhost' IDENTIFIED BY 'NEW_DB_PASSWORD';"

# Update wp-config.php with new DB password
```

3. **Rotate ALL API keys**:
```bash
# OpenAI API key
wp option update pearblog_openai_api_key NEW_KEY

# PearBlog API key
wp option update pearblog_api_key $(openssl rand -hex 32)

# Health secret
wp option update pearblog_health_secret $(openssl rand -hex 32)
```

4. **Scan for malware**:
```bash
# Install and run malware scanner
wp plugin install wordfence --activate
wp wordfence scan
```

5. **Review access logs**:
```bash
tail -1000 /var/log/nginx/access.log | grep -E "POST|PUT|DELETE"
```

6. **Restore from clean backup** (if compromise is extensive):
```bash
# Restore to known-good state
rsync -avz /backups/pre-compromise/ /var/www/html/
```

7. **Harden security**:
```bash
# Update WordPress core
wp core update

# Update all plugins
wp plugin update --all

# Remove suspicious users
wp user list --role=administrator
wp user delete SUSPICIOUS_USER_ID

# Enable 2FA for all admins
wp plugin install two-factor --activate
```

8. **Monitor for reinfection**:
```bash
# Watch for suspicious file changes
find /var/www/html -type f -mtime -1 -ls
```

### Circuit Breaker Stuck Open

**Scenario**: Circuit breaker is stuck in OPEN state, blocking all API calls.

**Recovery**:

1. **Check circuit breaker state**:
```bash
wp transient get pearblog_circuit_breaker_open
```

2. **Check failure count**:
```bash
wp transient get pearblog_circuit_breaker_failures
```

3. **Force reset** (if false alarm):
```bash
wp transient delete pearblog_circuit_breaker_open
wp transient delete pearblog_circuit_breaker_failures
```

4. **Test OpenAI connectivity**:
```bash
curl -H "Authorization: Bearer YOUR_API_KEY" \
     https://api.openai.com/v1/models
```

5. **If OpenAI is actually down**, wait for recovery:
   - Check status: https://status.openai.com/
   - Circuit breaker will auto-reset after 5 minutes

---

## 📞 Support Contacts

### Emergency Support

- **Critical Production Issues**: emergency@pearblog.ai (24/7)
- **Security Incidents**: security@pearblog.ai (immediate response)
- **Data Loss Recovery**: recovery@pearblog.ai

### Standard Support

- **Technical Support**: support@pearblog.ai (Mon-Fri 9am-5pm UTC)
- **Documentation**: https://docs.pearblog.ai
- **Community Forum**: https://community.pearblog.ai
- **GitHub Issues**: https://github.com/pearblog/engine/issues

### Escalation Path

1. **Level 1**: Check this guide + documentation
2. **Level 2**: Search community forum
3. **Level 3**: Open support ticket
4. **Level 4**: Request escalation to engineering team
5. **Emergency**: Contact emergency hotline

---

## 📚 Additional Resources

- [Security Audit Report](./SECURITY-AUDIT-REPORT.md)
- [Production Deployment Guide](./DEPLOYMENT-PRODUCTION.md)
- [Disaster Recovery Plan](./DISASTER-RECOVERY.md)
- [Performance Optimization Guide](./PERFORMANCE-OPTIMIZATION.md)
- [API Documentation](./API-DOCUMENTATION.md)
- [Video Tutorials](https://docs.pearblog.ai/videos)

---

**Last Updated**: 2026-05-03
**Version**: 7.10.0
**Maintainer**: PearBlog Engineering Team

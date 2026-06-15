# 🖥️ WP-CLI Command Reference

> All PearBlog Engine CLI commands under the `wp pearblog` namespace.

---

## Prerequisites

```bash
# Ensure WP-CLI is available
wp --version

# Commands auto-register when the MU-plugin loads
wp pearblog --help
```

---

## `wp pearblog` — Core Commands

| Command | Description |
|---------|-------------|
| `wp pearblog generate [--topic=<t>] [--count=<n>]` | Generate content (default: next from queue) |
| `wp pearblog queue list` | Show current topic queue |
| `wp pearblog queue add <topic>` | Add topic to queue |
| `wp pearblog queue clear` | Clear all queued topics |
| `wp pearblog stats` | Pipeline statistics (runs, posts, cost) |
| `wp pearblog refresh [--post_id=<id>] [--all]` | Refresh stale content |
| `wp pearblog quality <post_id>` | Run quality analysis on a post |
| `wp pearblog duplicate <post_id>` | Check for duplicate content |
| `wp pearblog links <post_id>` | Analyze internal linking opportunities |
| `wp pearblog circuit` | Circuit breaker status and management |
| `wp pearblog autopilot [start\|stop\|status]` | Autonomous pipeline runner control |
| `wp pearblog scaffold <type> <name>` | Scaffold new prompt builder or AI provider |
| `wp pearblog audit [--format=<f>]` | Run content quality audit across all posts |
| `wp pearblog topics research <keyword>` | AI-powered topic research |
| `wp pearblog import <file> [--format=csv\|json]` | Bulk content import |
| `wp pearblog export [--format=csv\|json] [--output=<f>]` | Bulk content export |
| `wp pearblog schedule [--post_id=<id>]` | Show/set optimal publish schedule |

---

## `wp pearblog seo` — SEO V3 Commands

| Command | Description |
|---------|-------------|
| `wp pearblog seo stats` | SEO health statistics |
| `wp pearblog seo keywords [--vertical=<v>] [--limit=<n>]` | Keyword database query |
| `wp pearblog seo generate --vertical=<v> --intent=<i> [--limit=<n>]` | Generate keyword clusters |
| `wp pearblog seo verticals` | List available verticals |
| `wp pearblog seo services <vertical>` | List services for a vertical |
| `wp pearblog seo modifiers` | List available keyword modifiers |

---

## `wp pearblog security` — Security Commands

| Command | Description |
|---------|-------------|
| `wp pearblog security audit [--severity=<s>] [--format=md\|text]` | Run OWASP Top 10 security audit |
| `wp pearblog security scan` | Quick security scan |

---

## `wp pearblog integration` — Integration Commands

| Command | Description |
|---------|-------------|
| `wp pearblog integration sync` | Sync with external platforms |

---

## `wp pearblog v9` — V9.0 Module Commands

### Predictive Analytics

| Command | Description |
|---------|-------------|
| `wp pearblog v9 forecast <post_id> [--days=<d>]` | Content performance forecast |
| `wp pearblog v9 revenue-forecast [--days=<d>]` | Site-wide revenue forecast |
| `wp pearblog v9 anomalies <post_id> [--threshold=<t>]` | Detect traffic anomalies |
| `wp pearblog v9 optimize <post_id>` | Get optimization recommendations |

### Content Collaboration

| Command | Description |
|---------|-------------|
| `wp pearblog v9 collab assign <post_id> <user_id>` | Assign post to editor |
| `wp pearblog v9 collab request-review <post_id> <reviewer_id> [--notes=<n>]` | Request editorial review |
| `wp pearblog v9 collab approve <review_id> [--reviewer=<uid>]` | Approve review |
| `wp pearblog v9 collab reject <review_id> <feedback> [--reviewer=<uid>]` | Reject with feedback |
| `wp pearblog v9 collab pending [--reviewer=<uid>]` | List pending reviews |
| `wp pearblog v9 collab workload` | Show team workload distribution |
| `wp pearblog v9 collab snapshot <post_id> [--label=<l>]` | Create content version snapshot |
| `wp pearblog v9 collab history <post_id>` | Show version history |

### Mobile App Backend

| Command | Description |
|---------|-------------|
| `wp pearblog v9 mobile dashboard` | Diagnostic: render mobile dashboard JSON |
| `wp pearblog v9 mobile queue` | Diagnostic: render mobile queue JSON |

### A/B Testing

| Command | Description |
|---------|-------------|
| `wp pearblog v9 ab generate <post_id> [--type=<t>] [--count=<c>]` | Generate test variants |
| `wp pearblog v9 ab all <post_id> [--count=<c>]` | Generate all variant types |
| `wp pearblog v9 ab summary <post_id> <test_id> <variant_ids>` | Show test results summary |

### AI Provider Router

| Command | Description |
|---------|-------------|
| `wp pearblog v9 router status` | Show provider health and routing config |
| `wp pearblog v9 router stats` | Show provider performance statistics |
| `wp pearblog v9 router reset-stats` | Reset provider statistics |

### Orphan Page Detection

| Command | Description |
|---------|-------------|
| `wp pearblog v9 orphans scan [--force]` | Scan for orphan pages |
| `wp pearblog v9 orphans detail <post_id>` | Show link details for a page |
| `wp pearblog v9 orphans suggest <post_id>` | Suggest internal links for orphan |
| `wp pearblog v9 orphans mark-reviewed <post_id>` | Mark orphan as reviewed |

### Billing & Tenants

| Command | Description |
|---------|-------------|
| `wp pearblog v9 billing usage` | Show AI token usage, quota, percentage |
| `wp pearblog v9 billing reset` | Reset billing cycle counters |
| `wp pearblog v9 tenant create --domain=<d> [--plan=<p>] [--industry=<i>] [--language=<l>] [--tone=<t>] [--title=<t>] [--admin=<e>]` | Provision new tenant |
| `wp pearblog v9 tenant list` | List all provisioned tenants |

### Security & Compliance

| Command | Description |
|---------|-------------|
| `wp pearblog v9 audit run [--export=<file>]` | OWASP Top 10 audit with optional JSON export |
| `wp pearblog v9 pii scan <post_id> [--redact]` | Detect/redact PII in a post |
| `wp pearblog v9 moderation status` | Content moderation queue status |
| `wp pearblog v9 moderation check <post_id>` | Run moderation on specific post |
| `wp pearblog v9 rbac list` | List all roles and capabilities |
| `wp pearblog v9 rbac capabilities` | Show capability matrix |
| `wp pearblog v9 compliance report [--type=gdpr\|ccpa] [--format=json\|csv]` | Generate compliance report |

### AMP

| Command | Description |
|---------|-------------|
| `wp pearblog v9 amp status` | AMP generation status |
| `wp pearblog v9 amp convert <post_id>` | Convert post to AMP |

### ROI

| Command | Description |
|---------|-------------|
| `wp pearblog v9 roi article <post_id>` | Per-article ROI (sessions, cost, revenue, ROI%, RPM) |
| `wp pearblog v9 roi snapshot [--refresh]` | Site-wide ROI snapshot |

---

## Examples

```bash
# Generate 5 articles from the queue
wp pearblog generate --count=5

# Run full security audit and save report
wp pearblog v9 audit run --export=/tmp/audit.json

# Check revenue forecast for next 30 days
wp pearblog v9 revenue-forecast --days=30

# Scan for orphan pages and get link suggestions
wp pearblog v9 orphans scan --force
wp pearblog v9 orphans suggest 123

# Provision a new tenant
wp pearblog v9 tenant create --domain=example.com --plan=pro --industry=finance

# Run autopilot mode
wp pearblog autopilot start
```

---

*PearBlog Engine v9.0 — CLI Reference*

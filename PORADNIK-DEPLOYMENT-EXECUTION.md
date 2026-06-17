# 🚀 Poradnik Deployment Runbook (Active)

> **Instance:** `https://wordpress2614653.home.pl/poradnik/`  
> **Server host:** `wordpress2614653.home.pl`  
> **SSH user/port (default):** `wordpress2614653:222`  
> **WP path (default):** `/var/www/wordpress2614653.home.pl/poradnik`

## 1) Required secrets

- `PORADNIK_SSH_HOST` (or `SSH_HOST`) = `wordpress2614653.home.pl`
- `PORADNIK_SSH_USER` (or `SSH_USER`) = `wordpress2614653`
- `PORADNIK_SSH_PORT` (or `SSH_PORT`) = `222`
- `PORADNIK_WP_PATH` / `PORADNIK_WEB_ROOT` (or `WP_PATH`) = `/var/www/wordpress2614653.home.pl/poradnik`
- `PORADNIK_SSH_KEY` (or `SSH_PRIVATE_KEY`) **or** `PORADNIK_SSH_PASSWORD`/`SSH_PASSWORD`
- `OPENAI_API_KEY`

## 2) Deployment workflows

- Theme landing deployment: `.github/workflows/deploy-poradnik-v2-pro.yml`
- Content seeding: `.github/workflows/seed-poradnik-content.yml`
- Generic plugin/theme sync: `.github/workflows/deploy.yml`

## 3) Post-deploy verification (manual)

```bash
BASE="https://wordpress2614653.home.pl/poradnik"

curl -I "$BASE/"
curl -I "$BASE/wp-admin/"
curl -I "$BASE/wp-json/"
curl -i "$BASE/wp-json/pearblog/v1/health"
```

Expected:
- frontend: `200` (or redirect chain ending in `200`)
- admin: `302` to login/session flow
- REST index: `200`
- health route: `200/401/403` (must not be `rest_no_route`)

## 4) Admin session close-out (required)

Open as logged-in admin:
- `/wp-admin/admin.php?page=pearblog-enterprise-v8`
- `/wp-admin/admin.php?page=poradnik-rpm-lead-fusion`
- `/wp-admin/admin.php?page=poradnik-ads-layout-pro`
- `/wp-admin/admin.php?page=poradnik-affiliate-copy-generator`

Pass when:
- no fatal/blank pages
- one safe settings save persists after refresh
- no new JS console errors on these pages

## 5) Stability check

Run one additional generation cycle and verify logs:

```bash
WP_PATH="/var/www/wordpress2614653.home.pl/poradnik"
wp --path="$WP_PATH" pearblog generate --allow-root
wp --path="$WP_PATH" pearblog stats --allow-root
```

Pass when:
- generation completes without fatal errors
- no new critical entries in `wp-content/debug.log`

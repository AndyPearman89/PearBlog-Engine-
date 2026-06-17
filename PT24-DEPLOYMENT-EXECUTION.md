# 🚀 PT24 Deployment Runbook (Active)

> **Instance:** `https://wordpress2614653.home.pl/pt24/`  
> **Topology decision:** PT24 runs on the **same server** as Poradnik, in a separate folder.  
> **Server host:** `wordpress2614653.home.pl`  
> **SSH user/port (default):** `wordpress2614653:222`  
> **WP path (default):** `/var/www/wordpress2614653.home.pl/pt24`

## 1) Required secrets

- `PT24_SSH_HOST` (or `SSH_HOST`) = `wordpress2614653.home.pl`
- `PT24_SSH_USER` (or `SSH_USER`) = `wordpress2614653`
- `PT24_SSH_PORT` (or `SSH_PORT`) = `222`
- `PT24_WEB_ROOT` = `/var/www/wordpress2614653.home.pl/pt24`
- `PT24_REMOTE_REPO_PATH` (optional) default `/home/wordpress2614653/PearBlog-Engine-`
- `PT24_OPENAI_API_KEY` (or `OPENAI_API_KEY`)
- `SSH_PRIVATE_KEY` or `SSH_PASSWORD`

## 2) Deployment workflow

Use:
- `.github/workflows/deploy-pt24-from-secrets.yml`

Behavior:
- Deploys PT24 scripts to same host
- Installs `.env` into PT24 web root
- Runs `scripts/deploy-pt24-local-services.sh`
- Verifies HTTP on `https://wordpress2614653.home.pl/pt24/`

## 3) Post-deploy verification

```bash
BASE="https://wordpress2614653.home.pl/pt24"

curl -I "$BASE/"
curl -I "$BASE/wp-admin/"
curl -I "$BASE/wp-json/"
curl -i "$BASE/wp-json/pearblog/v1/health"
curl -i "$BASE/wp-json/pt24/v1/businesses"
```

Expected:
- frontend/admin reachable (`200` or login redirect)
- REST index reachable (`200`)
- health route registered (`200/401/403`, never `rest_no_route`)
- PT24 businesses endpoint responds (status can vary by data/auth)

## 4) Stability check

```bash
WP_PATH="/var/www/wordpress2614653.home.pl/pt24"
wp --path="$WP_PATH" pearblog generate --allow-root
wp --path="$WP_PATH" pt24 stats --allow-root
```

Pass when:
- no fatal errors during generation and PT24 stats
- no new critical errors in `wp-content/debug.log`

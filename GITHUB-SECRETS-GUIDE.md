# GitHub Secrets Configuration Guide (Current Standard)

## Scope

This repository now uses one server topology:
- `https://wordpress2614653.home.pl/poradnik/`
- `https://wordpress2614653.home.pl/pt24/`

Both instances run on the same SSH host with different WordPress folders.

---

## Standard host defaults

- `SSH_HOST=wordpress2614653.home.pl`
- `SSH_USER=wordpress2614653`
- `SSH_PORT=222`

---

## Required shared secrets

- `SSH_PRIVATE_KEY` (preferred) or `SSH_PASSWORD`
- `OPENAI_API_KEY` (fallback when instance-specific key is not set)

---

## Poradnik instance secrets

Recommended:

- `PORADNIK_SSH_HOST=wordpress2614653.home.pl`
- `PORADNIK_SSH_USER=wordpress2614653`
- `PORADNIK_SSH_PORT=222`
- `PORADNIK_WP_PATH=/var/www/wordpress2614653.home.pl/poradnik`
- `PORADNIK_WEB_ROOT=/var/www/wordpress2614653.home.pl/poradnik`
- `PORADNIK_SSH_KEY` (optional override of shared SSH key)
- `PORADNIK_SSH_PASSWORD` (optional override of shared SSH password)

Used by:
- `.github/workflows/deploy-poradnik-v2-pro.yml`
- `.github/workflows/seed-poradnik-content.yml`

---

## PT24 instance secrets

Recommended:

- `PT24_SSH_HOST=wordpress2614653.home.pl`
- `PT24_SSH_USER=wordpress2614653`
- `PT24_SSH_PORT=222`
- `PT24_WEB_ROOT=/var/www/wordpress2614653.home.pl/pt24`
- `PT24_REMOTE_REPO_PATH=/home/wordpress2614653/PearBlog-Engine-` (optional)
- `PT24_OPENAI_API_KEY` (optional, otherwise `OPENAI_API_KEY` is used)

Used by:
- `.github/workflows/deploy-pt24-from-secrets.yml`

---

## Generic workflow fallback secrets

For `.github/workflows/deploy.yml`:

- `SSH_HOST=wordpress2614653.home.pl`
- `SSH_USER=wordpress2614653`
- `SSH_PORT=222`
- `WP_PATH=/var/www/wordpress2614653.home.pl/poradnik` (or set explicitly per run target)

---

## Quick validation

```bash
# SSH connectivity
ssh -p 222 wordpress2614653@wordpress2614653.home.pl "echo SSH OK"

# Poradnik
curl -I https://wordpress2614653.home.pl/poradnik/

# PT24
curl -I https://wordpress2614653.home.pl/pt24/
```

---

## Notes

- This guide intentionally removes legacy references to `204.48.27.118` and `/var/www/pt24.pro`.
- Keep instance paths explicit to avoid cross-deploy mistakes.

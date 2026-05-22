# Deploy Runbook

> Single source of truth for production deployment in this repository.

## Start Here

1. Read the deployment workflow first: [`.github/workflows/deploy.yml`](.github/workflows/deploy.yml)
2. Use this runbook for the shortest safe path.
3. Treat the workflow as authoritative for required secrets and auth mode.

## Required Secrets

The deploy workflow accepts either key-based or password-based SSH, but for the current server only key-based auth is reliable.

Minimum inputs expected by the workflow:

- `SSH_HOST`
- `SSH_USER`
- `WP_PATH`
- `SSH_PRIVATE_KEY` or `SSH_PASSWORD`
- `SSH_PORT` optional, defaults to `22`

`SSH_PASSWORD` is only useful if password authentication is enabled on the server.
For the current server configuration, key-based SSH is the required option.

## Recommended Deploy Flow

### Option 1: GitHub Actions UI

1. Open **Actions** in the repository.
2. Select **Deploy to WordPress Server**.
3. Click **Run workflow**.
4. Optional: set `auth_mode` to `password` if you need to bypass a bad SSH key.
5. Confirm the latest run reaches the rsync and smoke-test steps.

### Option 2: GitHub CLI

Use this only with a PAT that has workflow access:

```bash
gh workflow run deploy.yml -f force_deploy=true -R AndyPearman89/PearBlog-Engine-
```

If the token only has the default Codespaces integration scope, the dispatch will fail with HTTP 403.

## What Gets Deployed

- `mu-plugins/pearblog-engine/` → `wp-content/mu-plugins/pearblog-engine/`
- `theme/pearblog-theme/` → `wp-content/themes/pearblog-theme/`

## Common Failure Modes

- `ssh-add` / `libcrypto` errors usually mean the SSH private key secret is malformed or truncated.
- `Permission denied (publickey)` means the server does not accept password auth for the current account.
- `SSH_PASSWORD` by itself will not work unless the server explicitly allows password logins.
- `Resource not accessible by integration` means the GitHub token cannot dispatch workflow runs from this session.

## Verification

After deploy, confirm:

- the workflow completed the rsync steps
- object cache flush did not fail the run
- the smoke test reported `CLASS_OK` and `MENU_OK`

## Related Docs

- [SETUP.md](SETUP.md)
- [GITHUB-SECRETS-GUIDE.md](GITHUB-SECRETS-GUIDE.md)
- [DEPLOYMENT.md](DEPLOYMENT.md)
- [DEPLOYMENT-PL.md](DEPLOYMENT-PL.md)

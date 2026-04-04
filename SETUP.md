# Quick Setup Guide

## Initial Setup (5 minutes)

### Step 1: Configure GitHub Secrets

1. Go to your repository settings
2. Navigate to: **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret** and add:

   - **Name**: `SITE_URL`
     **Value**: Your PearBlog site URL (e.g., `https://myblog.com`)

   - **Name**: `API_ENDPOINT`
     **Value**: API path (e.g., `/api/content/process`)

   - **Name**: `API_KEY`
     **Value**: Your API authentication key

---

## Server Deployment Setup

The **Deploy to WordPress Server** workflow (`.github/workflows/deploy.yml`) pushes the
mu-plugin and theme directly to your server via SSH/rsync whenever `main` is updated.

### Required secrets for deployment

| Secret | Description | Example |
|---|---|---|
| `SSH_HOST` | Server hostname or IP address | `185.23.45.67` |
| `SSH_USER` | SSH login username | `deploy` |
| `SSH_PRIVATE_KEY` | Private SSH key (full contents of `~/.ssh/id_ed25519`) | `-----BEGIN OPENSSH...` |
| `WP_PATH` | Absolute path to WordPress root on the server | `/var/www/html` |
| `SSH_PORT` | *(optional)* SSH port, default `22` | `22` |

### One-time server preparation

1. **Create a deploy key on your machine:**
   ```bash
   ssh-keygen -t ed25519 -C "github-deploy" -f ~/.ssh/pearblog_deploy
   ```
2. **Authorise the key on the server:**
   ```bash
   ssh-copy-id -i ~/.ssh/pearblog_deploy.pub $SSH_USER@$SSH_HOST
   ```
3. **Paste the private key** (`cat ~/.ssh/pearblog_deploy`) into the `SSH_PRIVATE_KEY` secret.
4. **Add the remaining secrets** listed in the table above.

### What gets deployed

| Local path | WordPress destination |
|---|---|
| `mu-plugins/pearblog-engine/` | `{WP_PATH}/wp-content/mu-plugins/pearblog-engine/` |
| `theme/pearblog-theme/` | `{WP_PATH}/wp-content/themes/pearblog-theme/` |

After rsync the workflow flushes the WordPress object cache automatically (requires WP-CLI on the server).

### Trigger the workflow

- **Automatically** — on every push to `main` that changes `mu-plugins/**` or `theme/**`
- **Manually** — go to **Actions** → *Deploy to WordPress Server* → **Run workflow**

---

### Step 2: Enable GitHub Actions

1. Go to **Actions** tab in your repository
2. If prompted, enable GitHub Actions
3. The workflow is ready to run!

### Step 3: Test the Workflow

**Option A: Manual Trigger**
1. Go to **Actions** tab
2. Select "PearBlog Content Pipeline Automation"
3. Click **Run workflow** → **Run workflow**

**Option B: Wait for Scheduled Run**
- Automatically runs daily at 2:00 AM UTC

### Step 4: Verify Execution

1. Go to **Actions** tab
2. Click on the latest workflow run
3. Review the logs
4. Download artifacts to see detailed logs

## What Happens?

```
┌─────────────────────────────────────┐
│  Scheduled/Manual Trigger           │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│  Validate Environment Variables     │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│  Check for Duplicate Execution      │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│  Trigger API Endpoint               │
│  (with retry on failure)            │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│  Log Results & Save History         │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│  Commit & Push Changes              │
└─────────────────────────────────────┘
```

## Customization

### Change Schedule

Edit `.github/workflows/content-pipeline.yml`:

```yaml
on:
  schedule:
    - cron: '0 2 * * *'  # Daily at 2 AM UTC
```

Common cron patterns:
- `0 */6 * * *` - Every 6 hours
- `0 0 * * 0` - Weekly on Sunday
- `0 9 * * 1-5` - Weekdays at 9 AM

### Adjust Retry Settings

Edit `scripts/run_pipeline.py`:

```python
MAX_RETRIES = 3          # Number of retry attempts
RETRY_DELAY = 5          # Seconds between retries
RATE_LIMIT_DELAY = 1     # Seconds between requests
TIMEOUT = 30             # Request timeout
```

## Monitoring

### Check Last Execution

```bash
gh run list --workflow=content-pipeline.yml --limit 1
```

### View Logs

```bash
gh run view --log
```

### Download Logs Locally

1. Go to Actions → Select run
2. Scroll to "Artifacts" section
3. Download `pipeline-logs-{run-number}`

## Troubleshooting

### No secrets configured
```
Error: Missing required environment variables
```
→ Add secrets in Settings → Secrets and variables → Actions

### API endpoint unreachable
```
Error: Request failed: Connection error
```
→ Verify SITE_URL and API_ENDPOINT are correct

### Authentication failed
```
Error: API error: 401
```
→ Check API_KEY is valid

## Next Steps

- ✅ Monitor first execution
- ✅ Review logs for any issues
- ✅ Adjust schedule if needed
- ✅ Configure notifications (optional)

## Support

Questions? Check the main [README.md](README.md) for detailed documentation.

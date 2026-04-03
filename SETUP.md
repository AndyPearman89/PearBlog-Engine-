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

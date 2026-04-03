# PearBlog Content Pipeline Automation

Autonomous DevOps + AI content automation system for PearBlog.

## Overview

This automation system handles:
- **Content Pipeline Execution**: Triggers API endpoints to process and update content
- **SEO Processing**: Automated SEO optimization through API calls
- **Deployment**: Automatic commits and pushes of updates
- **Error Handling**: Comprehensive retry logic and error recovery
- **Logging**: Full execution tracking and history

## Features

✅ **No Duplicates**: Hash-based duplicate detection prevents redundant executions
✅ **Rate Limit Safe**: Built-in delays and rate limit handling
✅ **Complete Logging**: All executions logged with timestamps and results
✅ **Automatic Retry**: Up to 3 retry attempts with exponential backoff
✅ **Execution History**: JSON-based history tracking for audit trail

## Setup

### 1. Configure Secrets

Add the following secrets to your GitHub repository (Settings → Secrets and variables → Actions):

- `SITE_URL`: Your PearBlog site URL (e.g., `https://example.com`)
- `API_ENDPOINT`: API endpoint path (e.g., `/api/content/process`)
- `API_KEY`: Authentication API key

### 2. Workflow Configuration

The GitHub Action runs:
- **Scheduled**: Daily at 2:00 AM UTC
- **Manual**: Via workflow_dispatch in GitHub Actions tab

To modify the schedule, edit `.github/workflows/content-pipeline.yml`:

```yaml
on:
  schedule:
    - cron: '0 2 * * *'  # Change this cron expression
```

### 3. Manual Execution

Trigger manually:
1. Go to Actions tab in GitHub
2. Select "PearBlog Content Pipeline Automation"
3. Click "Run workflow"

## Architecture

### Workflow (`.github/workflows/content-pipeline.yml`)

1. Checks out repository
2. Sets up Python environment
3. Installs dependencies
4. Executes pipeline script
5. Uploads logs as artifacts
6. Commits and pushes any changes
7. Handles failures with notifications

### Pipeline Script (`scripts/run_pipeline.py`)

```
┌─────────────────────────────────────┐
│  Load Configuration & Validate      │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│  Generate Execution Hash            │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│  Check Duplicate (History)          │
└──────────────┬──────────────────────┘
               │
        ┌──────┴──────┐
        │ Duplicate?  │
        └──────┬──────┘
         No    │    Yes → Skip
               │
┌──────────────▼──────────────────────┐
│  Trigger API (with Retry Logic)     │
│  - Max 3 attempts                   │
│  - Exponential backoff              │
│  - Rate limit handling              │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│  Save Execution History             │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│  Generate Logs & Summary            │
└─────────────────────────────────────┘
```

## Configuration

### Environment Variables

| Variable | Description | Required |
|----------|-------------|----------|
| `SITE_URL` | PearBlog site URL | ✅ |
| `API_ENDPOINT` | API endpoint path | ✅ |
| `API_KEY` | Authentication key | ✅ |

### Script Constants

Edit `scripts/run_pipeline.py` to customize:

```python
MAX_RETRIES = 3          # Maximum retry attempts
RETRY_DELAY = 5          # Seconds between retries
RATE_LIMIT_DELAY = 1     # Seconds between requests
TIMEOUT = 30             # Request timeout
```

## Logs

### Location

- **Local**: `logs/` directory
- **GitHub Actions**: Artifacts section of workflow runs

### Files

- `pipeline_YYYYMMDD_HHMMSS.log`: Detailed execution log
- `execution_history.json`: All execution records
- `latest_execution.json`: Most recent execution summary

### Log Format

```
2026-04-03 21:30:00 - INFO - ============================================================
2026-04-03 21:30:00 - INFO - PearBlog Content Pipeline - Execution Started
2026-04-03 21:30:00 - INFO - ============================================================
2026-04-03 21:30:00 - INFO - Configuration validated successfully
2026-04-03 21:30:00 - INFO - Site URL: https://example.com
2026-04-03 21:30:00 - INFO - API Endpoint: /api/content/process
2026-04-03 21:30:00 - INFO - No duplicate detected - proceeding with execution
2026-04-03 21:30:01 - INFO - API request attempt 1/3
2026-04-03 21:30:02 - INFO - Response status: 200
2026-04-03 21:30:02 - INFO - ✓ API request successful
2026-04-03 21:30:02 - INFO - ✓ Pipeline execution completed successfully
```

## Error Handling

### Retry Logic

| Error Type | Handling |
|------------|----------|
| Timeout | Retry with exponential backoff |
| 429 (Rate Limit) | Extended delay + retry |
| 5xx (Server Error) | Retry with backoff |
| 4xx (Client Error) | Log and fail (no retry) |

### Duplicate Prevention

Execution hash calculated from:
- Timestamp
- Action type
- Configuration

Last 100 executions checked to prevent duplicates.

## Testing Locally

```bash
# Set environment variables
export SITE_URL="https://your-site.com"
export API_ENDPOINT="/api/content/process"
export API_KEY="your-api-key"

# Run the script
python scripts/run_pipeline.py
```

## Monitoring

### Check Workflow Status

```bash
# Via GitHub CLI
gh run list --workflow=content-pipeline.yml

# View latest run
gh run view --log
```

### Check Logs

1. Go to Actions tab
2. Select workflow run
3. Download artifacts: `pipeline-logs-{run-number}`

## Troubleshooting

### Common Issues

**Issue**: Missing environment variables
```
ERROR - Missing required environment variables: SITE_URL, API_KEY
```
**Solution**: Add secrets in repository settings

**Issue**: Rate limit exceeded
```
WARNING - Rate limit exceeded, waiting before retry...
```
**Solution**: Automatic - script handles with delays

**Issue**: All retries exhausted
```
ERROR - All retry attempts exhausted
```
**Solution**: Check API endpoint availability and credentials

### Debug Mode

Add to workflow for verbose output:

```yaml
- name: Run content pipeline
  env:
    PYTHONUNBUFFERED: 1
    LOG_LEVEL: DEBUG
```

## Security

- ✅ Secrets stored in GitHub Secrets (encrypted)
- ✅ No credentials in logs
- ✅ API key sent via Authorization header
- ✅ HTTPS required for all endpoints
- ✅ Rate limiting prevents abuse

## License

MIT License - See LICENSE file

## Support

For issues or questions:
1. Check logs in GitHub Actions artifacts
2. Review execution history in `logs/execution_history.json`
3. Open an issue in this repository

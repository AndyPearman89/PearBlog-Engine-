# PearBlog Engine - Logs Directory

This directory contains execution logs from the content pipeline automation.

## Files

- `pipeline_YYYYMMDD_HHMMSS.log` - Detailed execution logs with timestamps
- `execution_history.json` - Complete history of all pipeline executions
- `latest_execution.json` - Summary of the most recent execution

## Log Retention

- Logs are uploaded to GitHub Actions artifacts with 30-day retention
- Local logs should be committed for long-term tracking
- Execution history is maintained automatically by the pipeline script

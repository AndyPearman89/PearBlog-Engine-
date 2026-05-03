# 🐍 PearBlog Automation Scripts

> Python automation suite powering PearBlog's content production pipeline.

---

## Overview

These scripts run as **GitHub Actions** (or locally) to orchestrate keyword research, SERP analysis, and content pipeline execution outside of WordPress.

| Script | Purpose | Lines |
|--------|---------|-------|
| `automation_orchestrator.py` | Full-cycle orchestration (keyword → publish → optimize) | 400 |
| `keyword_engine.py` | Keyword research, expansion, clustering, and intent analysis | 465 |
| `scraping_engine.py` | SERP & competitor data extraction (Google, Reddit, forums) | 434 |
| `serp_analyzer.py` | Competitive analysis and content gap identification | 354 |
| `run_pipeline.py` | WordPress API pipeline trigger with retry and dedup | 279 |

---

## Architecture

```
automation_orchestrator.py          # Entry point for GitHub Actions
├── keyword_engine.py               # Step 1: Generate & cluster keywords
├── scraping_engine.py              # Step 2: Scrape SERP & competitor data
│   └── (requests + BeautifulSoup)
├── serp_analyzer.py                # Step 3: Analyze competition
│   └── scraping_engine.py
└── WordPress REST API              # Step 4-7: Content creation & publishing
    └── run_pipeline.py             # Standalone pipeline trigger
```

---

## Requirements

```bash
pip install -r requirements.txt
```

Key dependencies: `requests`, `beautifulsoup4`.

---

## Environment Variables

| Variable | Required | Description |
|----------|----------|-------------|
| `SITE_URL` | Yes | WordPress site URL |
| `API_KEY` | Yes | PearBlog REST API key |
| `API_ENDPOINT` | `run_pipeline.py` only | Full API endpoint URL |
| `KEYWORD` | No | Base keyword (default: auto-generated) |
| `NICHE` | No | Content niche — `travel`, `general` (default: `travel`) |
| `BATCH_MODE` | No | Process multiple keywords — `true`/`false` (default: `false`) |

---

## Usage

### Via GitHub Actions (recommended)

Workflows in `.github/workflows/`:

- **`content-pipeline.yml`** — Runs daily at 2 AM UTC
- **`run-roadmap.yml`** — Runs weekly on Monday at 3 AM UTC

Both support `workflow_dispatch` for manual triggers.

### Local execution

```bash
cd scripts

# Full orchestration cycle
SITE_URL=https://example.com API_KEY=secret python automation_orchestrator.py

# Pipeline trigger only
SITE_URL=https://example.com API_ENDPOINT=https://example.com/wp-json/pearblog/v1/automation/create-content API_KEY=secret python run_pipeline.py
```

---

## Module Details

### `keyword_engine.py`

- Expands base keywords into variations and long-tail phrases.
- Estimates search intent (informational, commercial, transactional, navigational).
- Clusters related keywords by topic.
- Outputs `KeywordData` objects consumed by the orchestrator.

### `scraping_engine.py`

- Scrapes Google SERP results (titles, snippets, URLs).
- Extracts competitor article structure and headings.
- Collects forum discussions for content ideas.
- Rate-limited (2 sec between requests) with retry logic.

### `serp_analyzer.py`

- Analyzes top-ranking content for a keyword.
- Identifies content gaps and opportunities.
- Generates competitive scores and recommendations.
- Outputs `SERPAnalysis` objects.

### `automation_orchestrator.py`

- Orchestrates the 10-step automation loop:
  1. Keywords → 2. SERP → 3. Scraping → 4. AI Content → 5. Publish →
  6. SEO → 7. Affiliate → 8. Analytics → 9. Decisions → 10. Optimize
- Reads/writes automation state to `data/` directory.
- Logs all actions to `logs/` directory.

### `run_pipeline.py`

- Triggers WordPress content pipeline via REST API.
- Duplicate prevention via execution history.
- Automatic retry with exponential backoff.
- Standalone — does not require other scripts.

---

*Part of PearBlog Automation PRO v2*

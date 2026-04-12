# Load Testing Suite — PearBlog Engine

## Overview

The `tests/load/` directory contains **k6** load test scripts that benchmark PearBlog Engine under various traffic scenarios.

> **Tool:** [k6 by Grafana](https://k6.io/) — modern load testing as code  
> **Language:** JavaScript  
> **Required:** k6 installed locally or via Docker  

---

## Installation

```bash
# macOS (Homebrew)
brew install k6

# Linux (Debian/Ubuntu)
sudo gpg -k
sudo gpg --no-default-keyring --keyring /usr/share/keyrings/k6-archive-keyring.gpg \
     --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
echo "deb [signed-by=/usr/share/keyrings/k6-archive-keyring.gpg] https://dl.k6.io/deb stable main" \
     | sudo tee /etc/apt/sources.list.d/k6.list
sudo apt-get update && sudo apt-get install k6

# Docker
docker pull grafana/k6
```

---

## Test Scenarios

| Script | Description | VUs | Duration |
|--------|-------------|-----|----------|
| `smoke.js` | Baseline connectivity — 1 VU, ensures nothing is broken | 1 | 30 s |
| `load.js` | Average load — ramps to **100 VUs**, steady for 5 min | 100 | ~9 min |
| `stress.js` | Stress test — ramps to **500 VUs** in steps | 500 | ~18 min |
| `spike.js` | Spike test — sudden burst to **1000 VUs** | 1000 | ~8 min |
| `soak.js` | Endurance test — **100 VUs** sustained for **2 hours** | 100 | ~2 hr |

---

## Running Tests

```bash
# Set your target URL
export BASE_URL=https://your-site.com

# 1. Smoke test (always run first)
k6 run tests/load/smoke.js --env BASE_URL=$BASE_URL

# 2. Load test
k6 run tests/load/load.js --env BASE_URL=$BASE_URL

# 3. Stress test
k6 run tests/load/stress.js --env BASE_URL=$BASE_URL

# 4. Spike test
k6 run tests/load/spike.js --env BASE_URL=$BASE_URL

# 5. Soak test (run overnight)
k6 run tests/load/soak.js --env BASE_URL=$BASE_URL
```

### With Docker

```bash
docker run --rm -i grafana/k6 run - \
  --env BASE_URL=https://your-site.com \
  < tests/load/load.js
```

### Output to InfluxDB + Grafana (recommended)

```bash
k6 run tests/load/load.js \
  --env BASE_URL=$BASE_URL \
  --out influxdb=http://localhost:8086/k6
```

---

## Performance Thresholds

### Smoke test
- `p(95) < 500 ms` response time
- `< 1%` error rate

### Load test (100 VUs)
- `p(95) < 2 000 ms` full page load
- `p(95) < 500 ms` REST API latency
- `< 5%` error rate

### Stress test (500 VUs)
- `p(95) < 5 000 ms`
- `< 10%` error rate

### Spike test (1 000 VUs)
- `< 20%` error rate during spike
- Recovery within 3 min

### Soak test (100 VUs, 2 hr)
- `p(95) < 2 000 ms` sustained throughout
- `< 1%` error rate — no memory leaks

---

## Interpreting Results

Key metrics output by k6:

| Metric | Description | Target |
|--------|-------------|--------|
| `http_req_duration` | Total request duration | `p(95) < 2 s` |
| `http_req_failed` | Rate of failed requests | `< 5%` |
| `http_reqs` | Total requests/s throughput | — |
| `data_received` | Bytes received | — |
| `vus` | Active virtual users | — |
| `error_rate` (custom) | Application-level errors | `< 5%` |

---

## Baseline Metrics (Reference Environment)

> Measured on: PHP 8.2 / MySQL 8.0 / 2 vCPU / 4 GB RAM  
> WordPress 6.5 + PearBlog Engine v6.0

| Scenario | p50 | p95 | p99 | Error Rate | Throughput |
|----------|-----|-----|-----|-----------|-----------|
| Smoke | 85 ms | 120 ms | 180 ms | 0% | 1 req/s |
| Load (100 VUs) | 280 ms | 850 ms | 1 400 ms | 0.2% | 62 req/s |
| Stress (500 VUs) | 640 ms | 2 100 ms | 3 800 ms | 2.1% | 180 req/s |
| REST API (100 VUs) | 45 ms | 95 ms | 140 ms | 0% | 95 req/s |

---

## CI Integration

Add to `.github/workflows/load-test.yml`:

```yaml
name: Load Tests

on:
  workflow_dispatch:
  schedule:
    - cron: '0 2 * * 1'  # every Monday at 02:00 UTC

jobs:
  load-test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Install k6
        run: |
          sudo gpg --no-default-keyring \
            --keyring /usr/share/keyrings/k6-archive-keyring.gpg \
            --keyserver hkp://keyserver.ubuntu.com:80 \
            --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
          echo "deb [signed-by=/usr/share/keyrings/k6-archive-keyring.gpg] \
            https://dl.k6.io/deb stable main" \
            | sudo tee /etc/apt/sources.list.d/k6.list
          sudo apt-get update && sudo apt-get install k6
      - name: Smoke Test
        run: k6 run tests/load/smoke.js
        env:
          BASE_URL: ${{ secrets.STAGING_URL }}
      - name: Load Test
        run: k6 run tests/load/load.js
        env:
          BASE_URL: ${{ secrets.STAGING_URL }}
```

---

## Troubleshooting

**"Connection refused"** — Ensure `BASE_URL` is accessible from the test runner network.

**High error rate** — Check server logs; often indicates PHP memory limit or DB connection exhaustion.

**p99 spikes** — Inspect slow query logs. Run `wp pearblog stats` to see recent pipeline timing.

**Soak test memory growth** — Enable `PEARBLOG_DEBUG=true` and look for uncleaned transients or post cache growth.

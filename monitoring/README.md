# PT24.PRO Monitoring Stack - Quick Start Guide

**Version:** 1.0.0
**Last Updated:** 2026-05-04

---

## Overview

This monitoring stack provides complete observability for PT24.PRO including:
- **Prometheus** - Metrics collection
- **Grafana** - Visualization dashboards
- **Alertmanager** - Alert routing
- **Loki** - Log aggregation
- **Node Exporter** - Server metrics
- **MySQL Exporter** - Database metrics
- **Uptime Kuma** - Uptime monitoring

---

## Installation

### Prerequisites
- Docker 20.10+
- Docker Compose 2.0+
- 4GB RAM minimum
- 20GB disk space

### Step 1: Clone Configuration

```bash
cd /opt/pt24-monitoring
# Configuration files are already in /monitoring/ directory
```

### Step 2: Create Environment File

```bash
nano .env
```

Add the following:

```env
# Grafana Admin Credentials
GRAFANA_ADMIN_USER=admin
GRAFANA_ADMIN_PASSWORD=change_me_now_please

# MySQL Exporter
MYSQL_EXPORTER_DSN=pt24_user:password@(localhost:3306)/pt24_db

# Redis (if using)
REDIS_ADDR=redis://localhost:6379

# Email Alerts
SMTP_PASSWORD=your_smtp_password_here

# Slack Webhook (optional)
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL

# Webhook Bearer Token
WEBHOOK_BEARER_TOKEN=generate_random_token_here
```

### Step 3: Start Monitoring Stack

```bash
cd /home/runner/work/PearBlog-Engine-/PearBlog-Engine-/monitoring

# Start all services
docker-compose up -d

# Verify all containers are running
docker-compose ps

# Expected output: All services "Up"
```

### Step 4: Access Dashboards

- **Grafana:** http://your-server:3000 (admin / password from .env)
- **Prometheus:** http://your-server:9090
- **Alertmanager:** http://your-server:9093
- **Uptime Kuma:** http://your-server:3001

---

## Configuration

### Prometheus Targets

Edit `monitoring/prometheus/prometheus.yml` to add your server IP:

```yaml
scrape_configs:
  - job_name: 'node'
    static_configs:
      - targets: ['YOUR_SERVER_IP:9100']
```

### Email Alerts

Edit `monitoring/alertmanager/alertmanager.yml`:

```yaml
global:
  smtp_from: 'alerts@pt24.pro'
  smtp_smarthost: 'smtp.gmail.com:587'
  smtp_auth_username: 'alerts@pt24.pro'
  smtp_auth_password: '${SMTP_PASSWORD}'
```

### Grafana Dashboards

Pre-built dashboards available:
1. **PT24 Overview** - Key metrics at a glance
2. **Server Health** - CPU, RAM, disk, network
3. **Database Performance** - Query times, connections
4. **Application Metrics** - Leads, conversions, errors

Import dashboards from: `/monitoring/grafana/dashboards/`

---

## Monitoring Checklist

### Daily Checks
- [x] Site uptime: https://pt24.pro
- [x] Grafana dashboard review
- [x] No critical alerts pending
- [x] Error rate < 1%

### Weekly Checks
- [x] Disk space trending
- [x] Database growth rate
- [x] Slow query review
- [x] Alert rule effectiveness

### Monthly Checks
- [x] Capacity planning review
- [x] Alert fatigue assessment
- [x] Dashboard updates
- [x] Cost optimization

---

## Common Issues

### Container Won't Start

```bash
# Check logs
docker-compose logs [service_name]

# Common fix: permissions
sudo chown -R 472:472 monitoring/grafana/
```

### No Metrics Appearing

```bash
# Verify exporters are reachable
curl http://localhost:9100/metrics  # Node Exporter
curl http://localhost:9104/metrics  # MySQL Exporter

# Check Prometheus targets
# Visit: http://localhost:9090/targets
```

### Alerts Not Sending

```bash
# Verify Alertmanager config
docker exec pt24_alertmanager amtool check-config /etc/alertmanager/alertmanager.yml

# Test email delivery
docker exec pt24_alertmanager amtool alert add test_alert
```

---

## Backup & Restore

### Backup Grafana Dashboards

```bash
# Export all dashboards
docker exec pt24_grafana grafana-cli admin export-data

# Backup Grafana database
docker exec pt24_grafana backup.sh
```

### Backup Prometheus Data

```bash
docker exec pt24_prometheus promtool tsdb snapshot /prometheus
```

---

## Useful Commands

```bash
# Restart all services
docker-compose restart

# View logs
docker-compose logs -f [service_name]

# Stop all services
docker-compose down

# Update images
docker-compose pull
docker-compose up -d

# Remove all data (WARNING: Destructive)
docker-compose down -v
```

---

## Security Recommendations

1. **Change default passwords** immediately
2. **Enable HTTPS** for all dashboards (use Nginx reverse proxy)
3. **Restrict access** with firewall rules
4. **Regular updates** of Docker images
5. **Secure .env file** permissions: `chmod 600 .env`

---

## Support

- **Documentation:** [MONITORING-GUIDE.md](./MONITORING-GUIDE.md)
- **Issues:** Create ticket in #monitoring Slack channel
- **Emergency:** Contact DevOps on-call

---

**Last Updated:** 2026-05-04

# PearBlog Engine Enterprise Edition

Follow these steps to get started with PearBlog Engine Enterprise Edition...

## Complete Setup Checklist

- [ ] Verify WordPress 5.8+ and PHP 7.4+ are installed
- [ ] Verify PearBlog Engine 8.0.0+ is installed (and available in `mu-plugins/pearblog-engine/`)
- [ ] Confirm Enterprise v8 is enabled (e.g. `PEARBLOG_ADMIN_VERSION` / `pearblog_admin_version` set to `v8-enterprise`)
- [ ] Access the Enterprise v8 admin interface: `/wp-admin/admin.php?page=pearblog-enterprise-v8`
- [ ] Configure AI provider (OpenAI / Anthropic / Cohere) and set API key + model
- [ ] Set your industry vertical (`poradnik`, `guide`, `remont`, `budowa`, `pt24`, `local-services`)
- [ ] Configure keyword discovery strategy (Automatic / Hybrid) and daily keyword limit
- [ ] (Optional) Configure AdSense Publisher ID + enable monetization strategy (`funnel_aware`)
- [ ] Connect Google Analytics 4 (GA4 Property ID) and test connection
- [ ] Connect Google Search Console (verify ownership + authorize API)
- [ ] Enable automation workflows (cron/pipeline, frequency, batch sizes, auto-publish, optional image generation)
- [ ] Verify PearBlog cron jobs exist (`pearblog_pipeline_cron`, `pearblog_topic_research_refresh`, `pearblog_publish_schedule_refresh`)
- [ ] Generate the first batch of content (via UI or WP-CLI) and confirm publishing behavior
- [ ] Review the Real-Time Analytics + Performance dashboards to verify system health
- [ ] (Optional) Enable Dark Mode and confirm it persists
- [ ] (Optional) Set language preference (EN/PL) and confirm it persists
- [ ] Export a first report / audit log to verify exporting works (CSV/PDF/JSON/Excel)

## Additional Information

Make sure to follow each step carefully to ensure a smooth setup experience. For any issues, refer to the documentation or reach out for support.
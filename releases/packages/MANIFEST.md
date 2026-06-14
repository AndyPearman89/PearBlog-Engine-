# PearBlog Engine — Modular Packages

Modular ZIP packages for selective deployment or review. Each package is self-contained.

| Package | Size | Contents |
|---------|------|----------|
| `core-plugin.zip` | ~628 KB | MU-plugin bootstrap (`pearblog-engine.php`), `composer.json`, full `src/` directory with all PHP modules |
| `theme-frontend.zip` | ~268 KB | WordPress theme: all root templates, `inc/`, `template-parts/`, `assets/` (CSS + JS) |
| `automation-python.zip` | ~20 KB | Python automation scripts: orchestrator, keyword engine, SERP analyzer, pipeline runner, `requirements.txt` |
| `deploy-ops.zip` | ~69 KB | Bash deploy scripts (`deploy-*.sh`, `pt24-*.sh`), `run-security-audit.php`, all GitHub Actions workflows |
| `tests.zip` | ~153 KB | PHP unit + integration tests, Python pytest suite, load test scripts (k6) |
| `docs-product.zip` | ~119 KB | Core documentation: README, SETUP, architecture, documentation index, platform guide, `docs/` folder |
| `business-client-specific.zip` | ~292 KB | Client-specific deployment guides, quickstart files, PT24 and Poradnik deployment docs, `clients/` folder |

## Usage

```bash
# Install only the plugin
unzip core-plugin.zip -d /path/to/wp-content/

# Install only the theme
unzip theme-frontend.zip -d /path/to/wp-content/
```

Generated: 2026-06-14

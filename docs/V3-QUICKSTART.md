# Quick Start: Frontend V3 Configuration

This guide will help you activate and configure the Frontend V3 high-conversion layout.

## 1. Enable V3 Layout

By default, V3 is already enabled. If you need to manually enable it:

### Via WordPress Admin
```php
// Add to your theme's functions.php or use a plugin like Code Snippets:
update_option('pearblog_homepage_version', 'v3');
update_option('pearblog_hero_version', 'v3');
```

### Via WP-CLI
```bash
wp option update pearblog_homepage_version v3
wp option update pearblog_hero_version v3
```

## 2. Customize Hero Content

### Set Custom Hero Title & Subtitle

```php
// Via WordPress Admin (functions.php or Code Snippets)
update_option('pearblog_hero_title', 'Twój tytuł tutaj');
update_option('pearblog_hero_subtitle', 'Twój podtytuł tutaj');
```

### Via WP-CLI
```bash
wp option update pearblog_hero_title "Rozwiąż problem w jednym miejscu."
wp option update pearblog_hero_subtitle "Znajdź odpowiedź, porównaj opcje i wybierz najlepiej."
```

## 3. Configure Trending Topics

Edit the trending items in `template-parts/section-trending.php`:

```php
$trending_items = array(
    'Ile kosztuje remont mieszkania 2026?',
    'Pompa ciepła vs gaz — co wybrać?',
    'Najlepsza firma remontowa Katowice',
    'Koszt budowy domu za m²',
);
```

Replace with your own popular searches.

## 4. Customize Quick Action URLs

Edit `template-parts/section-quick-actions.php` to update links:

```php
// Change these URLs to match your site structure:
home_url('/poradniki')      // Guides category
home_url('/porownania')     // Comparisons category
home_url('/rankingi')       // Rankings category
home_url('/kalkulatory')    // Calculators category
```

## 5. Set Up Expert Pages

Create these pages in WordPress:
- `/pytanie` - Ask a question form
- `/eksperci` - Experts directory
- `/ai-doradca` - AI advisor
- `/dla-specjalistow` - For specialists signup

Or update URLs in templates to match your existing pages.

## 6. Revert to V2 (If Needed)

To switch back to the original layout:

```php
update_option('pearblog_homepage_version', 'v2');
update_option('pearblog_hero_version', 'v2');
```

Or via WP-CLI:
```bash
wp option update pearblog_homepage_version v2
wp option update pearblog_hero_version v2
```

## 7. A/B Testing Setup

To run A/B tests between V2 and V3:

```php
// Randomly show V2 or V3 to 50% of users
$version = (rand(0, 1) === 0) ? 'v2' : 'v3';
update_option('pearblog_homepage_version', $version);
```

Use a proper A/B testing plugin for production.

## 8. Cache Clearing

After configuration changes, clear caches:

```bash
# Clear WordPress object cache
wp cache flush

# Clear Cloudflare cache (if using)
# Via Cloudflare plugin or API

# Clear browser cache
# CMD+Shift+R (Mac) or CTRL+Shift+R (Windows)
```

## 9. Verify Configuration

Check that V3 is active:

```bash
# Via WP-CLI
wp option get pearblog_homepage_version

# Expected output: v3
```

Visit your homepage and verify:
- ✅ Search box in hero
- ✅ Quick action cards visible
- ✅ Trending section displays
- ✅ All 9 sections render

## 10. Performance Check

Verify site performance:

```bash
# Test with Google PageSpeed Insights
# https://pagespeed.web.dev/

# Or use lighthouse CLI
lighthouse https://poradnik.pro --view
```

Target scores:
- Performance: 90+
- Accessibility: 95+
- Best Practices: 100
- SEO: 100

## Troubleshooting

### V3 Not Showing?

1. Check option value:
   ```bash
   wp option get pearblog_homepage_version
   ```

2. Clear theme cache:
   ```bash
   wp cache flush
   ```

3. Check CSS is loaded:
   - View page source
   - Search for `v3-components.css`

### CSS Not Loading?

Verify file exists:
```bash
ls -la theme/pearblog-theme/assets/css/v3-components.css
```

Re-enqueue assets:
```php
// In functions.php, bump version:
define('PEARBLOG_VERSION', '5.2.0');
```

### Sections Not Rendering?

Check PHP functions are registered:
```bash
grep -r "function pearblog_quick_actions" theme/
```

Verify template files exist:
```bash
ls -la theme/pearblog-theme/template-parts/section-*.php
```

## Support

Need help?
- 📖 Full docs: `docs/FRONTEND-V3.md`
- 🐛 Report issues: GitHub Issues
- 💬 Contact: andy@pearblog.pro

---

**Quick Reference:**
```bash
# Enable V3
wp option update pearblog_homepage_version v3

# Disable V3
wp option update pearblog_homepage_version v2

# Check status
wp option get pearblog_homepage_version

# Clear cache
wp cache flush
```

# Frontend Versions Guide

**PearBlog Engine v7.0 - Frontend Architecture**

This guide explains the different frontend versions available in PearBlog Engine and when to use each one.

---

## 🎨 Available Frontend Versions

PearBlog Engine supports **3 distinct frontend systems**, each designed for specific use cases:

### 1. Frontend V3 (Default - Poradnik.pro)

**Status:** ✅ Production-ready, Default
**Best for:** Content-heavy sites, decision-making platforms, poradnik-style sites
**Toggle:** `pearblog_homepage_version === 'v3'`

#### Features:
- **High-Conversion Layout** optimized for user decisions
- **Component-Based Architecture:**
  - Hero V3 with integrated search
  - Quick Actions (Decision Entry Points)
  - Trending Topics section
  - "How It Works" flow
  - Feature sections (Poradniki, Porównania, Rankingi, Kalkulatory)
- **Mobile-First Design** with responsive breakpoints
- **SEO-Optimized** with structured data
- **Fast Performance** with lazy loading

#### Files:
- Template: `theme/pearblog-theme/index.php` (lines 14-50)
- CSS: `assets/css/v3-components.css`
- Components: `inc/components.php`

#### Enable V3:
```php
// In WordPress admin or wp-config.php
update_option('pearblog_homepage_version', 'v3');
```

#### Use Cases:
- ✅ Poradnik.pro (advice/guide platform)
- ✅ Content comparison sites
- ✅ Decision-making platforms
- ✅ Educational content hubs

---

### 2. Frontend V7 (Dark UI Kit)

**Status:** ✅ Production-ready, Optional
**Best for:** Modern apps, SaaS platforms, tech-focused sites
**Toggle:** `pearblog_homepage_version === 'v7'`

#### Features:
- **Dark Mode Native** design system
- **Modern UI Kit** with glassmorphism effects
- **Premium Typography** (Poppins + Inter)
- **Smooth Animations** and transitions
- **Advanced Color System** with CSS variables
- **Component Library** ready for customization

#### Files:
- Toggle: `theme/pearblog-theme/functions.php` (line 132)
- CSS: `assets/css/v7-ui-kit.css`
- Documentation: `V7-UI-KIT.md`

#### Enable V7:
```php
// In WordPress admin or wp-config.php
update_option('pearblog_homepage_version', 'v7');
```

#### Use Cases:
- ✅ Tech startups
- ✅ SaaS platforms
- ✅ Modern web apps
- ✅ Dark-mode-first products

---

### 3. Landing V2 Pro (Neon AI System)

**Status:** ✅ Production-ready, Standalone
**Best for:** High-conversion landing pages, AI-powered funnels
**Type:** Page Template (not a homepage layout)

#### Features:
- **Mobile-First Neon Design** with purple-to-pink gradients
- **Glassmorphism Effects** for modern look
- **Sticky Mobile CTA** activates at 150px scroll
- **AI Integration** with real-time analysis
- **AJAX Endpoints** for tracking and optimization:
  - `v2pro_ai_analyze` - AI content analysis
  - `v2pro_track_event` - Event tracking
  - `v2pro_track_cta_click` - CTA click tracking
  - `v2pro_track_performance` - Performance metrics
- **Performance Optimized** with lazy loading

#### Files:
- Template: `page-landing-v2-pro.php`
- CSS: `assets/css/v2-pro-neon.css`
- JS: `assets/js/v2-pro-mobile.js`
- Template Parts: 7 modular components
- Documentation: `LANDING-V2-PRO-MOBILE.md`, `DEPLOY-V2-PRO.md`

#### Enable V2 Pro:
```php
// Create a new page in WordPress admin
// Select template: "Landing V2 Pro"
```

#### Use Cases:
- ✅ Product launches
- ✅ Lead generation funnels
- ✅ AI-powered landing pages
- ✅ High-conversion campaigns
- ✅ Mobile-first experiences

---

## 🔄 Switching Between Versions

### For Homepage Layout (V3 ↔ V7):

**Via WordPress Admin:**
1. Go to **Appearance → Customize → Theme Settings**
2. Find "Homepage Version" option
3. Select "V3" or "V7"
4. Click "Publish"

**Via Code:**
```php
// Switch to V3 (default)
update_option('pearblog_homepage_version', 'v3');

// Switch to V7 (dark UI)
update_option('pearblog_homepage_version', 'v7');

// Check current version
$version = get_option('pearblog_homepage_version', 'v3');
```

**Via WP-CLI:**
```bash
# Switch to V3
wp option update pearblog_homepage_version 'v3'

# Switch to V7
wp option update pearblog_homepage_version 'v7'

# Get current version
wp option get pearblog_homepage_version
```

### For Landing V2 Pro:

Landing V2 Pro is a **page template**, not a homepage layout. To use it:

1. Create a new page: **Pages → Add New**
2. Give it a title (e.g., "Product Launch")
3. In **Page Attributes**, select template: **Landing V2 Pro**
4. Customize content via page editor
5. Publish

---

## 📊 Version Comparison

| Feature | V3 (Default) | V7 (Dark UI) | V2 Pro (Landing) |
|---------|-------------|-------------|------------------|
| **Purpose** | Content hub | Modern app | Landing page |
| **Design** | Light, clean | Dark, modern | Neon, gradient |
| **Layout** | Multi-section | Minimal | Single focus |
| **Mobile** | Responsive | Responsive | Mobile-first |
| **AI Features** | ❌ | ❌ | ✅ |
| **CTA Focus** | Medium | Medium | High |
| **Best for** | Blogs, guides | SaaS, tech | Campaigns |
| **Load Speed** | Fast | Fast | Optimized |

---

## 🎯 Decision Guide

### Choose **Frontend V3** if you need:
- ✅ Content-heavy homepage
- ✅ Multiple sections (hero, features, trending, etc.)
- ✅ Decision-making flows
- ✅ SEO-focused architecture
- ✅ Traditional blog/guide site

### Choose **Frontend V7** if you need:
- ✅ Modern, dark interface
- ✅ Tech/SaaS aesthetic
- ✅ Minimal, focused design
- ✅ Premium feel
- ✅ Dark mode as primary

### Choose **Landing V2 Pro** if you need:
- ✅ High-conversion landing page
- ✅ Single call-to-action focus
- ✅ AI-powered optimization
- ✅ Mobile-first experience
- ✅ Campaign-specific page

---

## 🛠️ Technical Details

### Asset Loading

**V3 Assets:**
```php
// Loaded when pearblog_homepage_version === 'v3'
wp_enqueue_style('pearblog-v3-components', PEARBLOG_URI . '/assets/css/v3-components.css');
```

**V7 Assets:**
```php
// Loaded when pearblog_homepage_version === 'v7'
wp_enqueue_style('pearblog-v7-ui-kit', PEARBLOG_URI . '/assets/css/v7-ui-kit.css');
```

**V2 Pro Assets:**
```php
// Loaded only on pages using Landing V2 Pro template
wp_enqueue_style('v2-pro-neon', PEARBLOG_URI . '/assets/css/v2-pro-neon.css');
wp_enqueue_script('v2-pro-mobile', PEARBLOG_URI . '/assets/js/v2-pro-mobile.js');
```

### Performance Impact

All versions are optimized for performance:
- **Lazy Loading:** Images and scripts load on-demand
- **Conditional Loading:** Only active version's assets are loaded
- **Critical CSS:** Inline critical styles for faster render
- **Minification:** All CSS/JS is minified in production

---

## 🔍 Troubleshooting

### Version Not Changing?

**Clear caches:**
```bash
# Clear WordPress cache
wp cache flush

# Clear transients
wp transient delete --all

# Regenerate assets
wp rewrite flush
```

**Check option value:**
```bash
wp option get pearblog_homepage_version
```

### Assets Not Loading?

**Verify file paths:**
```bash
# Check if CSS exists
ls -la theme/pearblog-theme/assets/css/v3-components.css
ls -la theme/pearblog-theme/assets/css/v7-ui-kit.css
ls -la theme/pearblog-theme/assets/css/v2-pro-neon.css
```

**Check functions.php:**
Make sure asset enqueuing functions are present in `functions.php` (lines 116-184).

### Landing V2 Pro Not Available?

**Verify template file exists:**
```bash
ls -la theme/pearblog-theme/page-landing-v2-pro.php
```

If missing, the template won't appear in the page template dropdown.

---

## 📚 Related Documentation

- **Frontend V3:** `docs/FRONTEND-V3.md`
- **Frontend V7:** `theme/pearblog-theme/V7-UI-KIT.md`
- **Landing V2 Pro:** `LANDING-V2-PRO-MOBILE.md`, `DEPLOY-V2-PRO.md`
- **Deployment:** `DEPLOYMENT-pt24-pro.md`, `DEPLOYMENT-poradnik-pro.md`

---

## 🎨 Customization

### Customizing V3:
Edit `assets/css/v3-components.css` and component functions in `inc/components.php`.

### Customizing V7:
Edit `assets/css/v7-ui-kit.css` - uses CSS variables for easy theming.

### Customizing V2 Pro:
Edit template parts in `template-parts/v2-pro-*.php` and `assets/css/v2-pro-neon.css`.

---

## ✅ Best Practices

1. **Choose ONE primary version** for your site's homepage
2. **Use Landing V2 Pro** for specific campaigns/pages
3. **Test on mobile** before deploying
4. **Monitor performance** with Core Web Vitals
5. **Keep assets updated** when switching versions
6. **Document customizations** for team members

---

**Updated:** 2026-05-04
**Version:** 1.0.0
**Applies to:** PearBlog Engine v7.0+

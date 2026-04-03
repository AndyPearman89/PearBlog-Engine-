# PearBlog Engine

**AI-Powered WordPress Theme Engine for SEO-First Content Sites**

## Repository Contents

This repository contains the **PearBlog Frontend Engine v1** - a complete WordPress theme system designed for SEO optimization, user experience, monetization, and multisite branding.

## What's Inside

### `/theme/pearblog-theme/`

A production-ready WordPress theme featuring:

- ✅ **SEO-First Architecture** - Structured layouts with H1, intro, TL;DR, H2/H3 sections, FAQ, and related posts
- ✅ **Core Components** - Hero, Card, Related Posts, FAQ, Ads blocks
- ✅ **Design System** - CSS variables for consistent branding across multisites
- ✅ **Performance Optimized** - Lazy loading, minimal JS, Core Web Vitals focused
- ✅ **Mobile First** - Responsive design with touch-friendly navigation
- ✅ **Monetization Ready** - AdSense integration and affiliate support
- ✅ **Multisite Support** - Dynamic branding, colors, and logos per site

## Quick Start

### Installation

```bash
# Copy theme to WordPress installation
cp -r theme/pearblog-theme /path/to/wordpress/wp-content/themes/

# Or create a symlink for development
ln -s $(pwd)/theme/pearblog-theme /path/to/wordpress/wp-content/themes/pearblog-theme
```

### Activation

1. Log in to WordPress Admin
2. Navigate to **Appearance → Themes**
3. Activate **PearBlog Theme**

## Features

### Page Types
- **Homepage** - Hero + cards grid + CTA
- **Single Post** - Full SEO layout with all components
- **Category Pages** - Archive with category header

### Components
- Hero (dynamic header with gradient/image)
- Card (article listing)
- Related Posts (internal linking)
- FAQ (with Schema.org markup)
- Ads (monetization blocks)

### SEO Features
- Breadcrumbs
- Schema.org Article markup
- Schema.org FAQPage markup
- Meta descriptions
- Semantic HTML structure
- Internal linking

### Performance
- Lazy loading images
- Minimal JavaScript footprint
- CSS variables for theming
- No external dependencies
- Core Web Vitals optimized

## Documentation

Full documentation is available in [`/theme/pearblog-theme/README.md`](theme/pearblog-theme/README.md)

## Architecture

```
PearBlog Engine v1
├── Components (Hero, Card, Related, FAQ, Ads)
├── Design System (CSS Variables)
├── SEO Layout (H1, Intro, TL;DR, Sections, FAQ, Related, CTA)
├── Performance (Lazy Load, Minimal JS)
├── Mobile First (Responsive)
├── Monetization (AdSense, CTA, Affiliate)
└── Multisite (Dynamic Branding)
```

## Use Cases

Perfect for:
- AI-powered content sites
- SEO-focused blogs
- SaaS marketing sites
- Multisite networks
- Affiliate marketing sites
- Content monetization

## Roadmap

### v1.0 (Current)
- ✅ Core theme structure
- ✅ All essential components
- ✅ SEO optimization
- ✅ Performance features
- ✅ Multisite support

### v2.0 (Planned)
- UI PRO components
- Admin dashboard
- Custom widgets
- Advanced customization panel
- Gutenberg blocks
- Page builder integration

## Tech Stack

- **PHP** 7.4+
- **WordPress** 5.9+
- **CSS3** with CSS Variables
- **Vanilla JavaScript** (no jQuery)
- **HTML5** semantic markup

## License

GNU General Public License v2 or later

## Support

For issues, questions, or feature requests, please open an issue in this repository.

---

**Built for AI + SEO + SaaS**
# PearBlog v7 UI Kit Documentation

## Overview

The PearBlog v7 UI Kit is a complete dark-themed design system that replaces the previous light-themed v3 components. It provides a modern, high-conversion interface optimized for content consumption and user engagement.

## Design Philosophy

### Dark-First Approach
- **Primary Background**: `#0B1118` (deep navy/black)
- **Secondary Background**: `#111827` (slightly lighter for cards/panels)
- **Optimized for**: Long-form reading, reduced eye strain, premium feel

### Color System
- **Primary**: `#4ADE80` (vibrant green) - CTAs, success states
- **Secondary**: `#60A5FA` (bright blue) - links, accents
- **Text Main**: `#ffffff` (pure white) - headings, primary content
- **Text Secondary**: `#9CA3AF` (gray) - body text, descriptions

### Typography
- **Display Font**: Poppins (headings, hero text)
- **UI Font**: Inter (body, interface elements)
- **Base Size**: 18px (optimized for readability)
- **Line Height**: 1.7 (generous for comfortable reading)

## Activation

### Enable v7 UI Kit

Set the WordPress option to activate v7:

```php
update_option('pearblog_homepage_version', 'v7');
```

### Via WP-CLI

```bash
wp option update pearblog_homepage_version v7
```

### Via WordPress Admin

Navigate to **Appearance > Customize > Layout Options** and select "v7 - Dark UI Kit"

## CSS Variables Reference

### Colors
```css
--pb-bg-main: #0B1118;           /* Main background */
--pb-bg-secondary: #111827;      /* Cards, panels */
--pb-text-main: #ffffff;         /* Primary text */
--pb-text-secondary: #9CA3AF;    /* Body text */
--pb-primary: #4ADE80;           /* Primary brand */
--pb-secondary: #60A5FA;         /* Secondary brand */
--pb-border: rgba(255,255,255,0.08); /* Subtle borders */
```

### Spacing
```css
--pb-spacing-xs: 6px;
--pb-spacing-sm: 10px;
--pb-spacing: 16px;
--pb-spacing-lg: 24px;
--pb-spacing-xl: 40px;
```

### Border Radius
```css
--pb-radius: 12px;
--pb-radius-lg: 16px;
```

### Typography
```css
--pb-font-display: 'Poppins', sans-serif;
--pb-font-ui: 'Inter', sans-serif;
```

## Component Library

### Layout Components

#### Container
```html
<div class="pb-container">
  <!-- Max-width: 1200px, centered -->
</div>
```

#### Content Wrapper
```html
<div class="pb-content">
  <!-- Max-width: 720px, for article content -->
</div>
```

### Hero Section

```html
<section class="pb-hero">
  <div class="pb-hero__bg" style="background-image: url('hero.jpg')"></div>
  <div class="pb-hero__overlay"></div>
  <div class="pb-hero__content">
    <h1>Your Headline</h1>
    <p>Compelling subtitle</p>
    <button class="pb-btn-primary">Get Started</button>
  </div>
</section>
```

**Features**:
- 70vh height on desktop, 60vh on mobile
- Background image with gradient overlay
- Centered content with z-index layering

### Buttons

#### Primary Button
```html
<button class="pb-btn-primary">Primary Action</button>
```
- Gradient background (green to blue)
- Dark text (`#0B1118`)
- Hover: Lifts up 2px

#### Secondary Button
```html
<button class="pb-btn-secondary">Secondary Action</button>
```
- Border style with transparent background
- Subtle hover effect

### Cards

```html
<div class="pb-card">
  <img src="thumbnail.jpg" alt="Card image">
  <div style="padding: var(--pb-spacing);">
    <h3>Card Title</h3>
    <p>Card description text.</p>
  </div>
</div>
```

**Features**:
- Rounded corners (16px)
- Hover: Lifts up 4px
- Dark background with subtle border

### TLDR Box

```html
<div class="pb-tldr">
  <h3>TL;DR</h3>
  <p>Key takeaways in a highlighted box.</p>
</div>
```

**Use Case**: Article summaries, key points

### CTA Section

```html
<section class="pb-cta">
  <h2>Ready to Get Started?</h2>
  <p>Join thousands of users today.</p>
  <button class="pb-btn-primary">Sign Up Now</button>
</section>
```

**Features**:
- Gradient background
- Centered text
- High conversion optimization

### Header

```html
<header class="pb-header">
  <nav class="pb-container">
    <!-- Navigation items -->
  </nav>
</header>
```

**Features**:
- Sticky positioning
- Backdrop blur effect
- Semi-transparent background

### Reading Progress Bar

```html
<div id="pb-reading-progress-bar"></div>
```

**Features**:
- Fixed to top
- Gradient (green to blue)
- Updates via JavaScript

### Search Panel

```html
<div id="pb-search-panel">
  <div class="pb-container">
    <input type="search" placeholder="Search articles...">
  </div>
</div>
```

**Features**:
- Slide-down animation
- Toggle with `.active` class

### FAQ Items

```html
<div class="pb-faq-item">
  <h3>Question?</h3>
  <p>Answer to the question.</p>
</div>
```

**Features**:
- Bottom border separator
- Consistent padding

### Footer

```html
<footer class="pb-footer">
  <div class="pb-container">
    <!-- Footer content -->
  </div>
</footer>
```

**Features**:
- Darker background (`#05080c`)
- Extra padding for breathing room

## Typography Scale

| Element | Size | Weight | Usage |
|---------|------|--------|-------|
| `<h1>` | 44px (32px mobile) | 700 | Page titles, hero headlines |
| `<h2>` | 30px (24px mobile) | 700 | Section headers |
| `<h3>` | 22px | 700 | Sub-sections, card titles |
| `<p>` | 18px | 400 | Body text |

## Responsive Breakpoints

### Mobile (<= 768px)
- Reduced heading sizes
- Hero height: 60vh
- Adjusted spacing for touch interfaces

## Migration from v3 to v7

### Option 1: Full Switch
```php
// Switch entire site to v7
update_option('pearblog_homepage_version', 'v7');
```

### Option 2: A/B Testing
```php
// Use PearBlog's A/B testing framework
if (pb_should_show_variant('homepage_v7')) {
    update_option('pearblog_homepage_version', 'v7');
} else {
    update_option('pearblog_homepage_version', 'v3');
}
```

### Breaking Changes
1. **Light to Dark**: Complete color scheme change
2. **Typography Scale**: Larger base font (16px → 18px)
3. **Spacing System**: New spacing variables
4. **Component Classes**: New `.pb-hero__*` BEM naming

### Migration Checklist
- [x] Update custom CSS overrides to work with dark theme
- [x] Test contrast ratios for accessibility (WCAG AA)
- [x] Review image overlays for dark backgrounds
- [x] Update brand colors in customizer if needed
- [x] Test reading experience on mobile devices
- [x] Verify CTA button visibility and conversion tracking
- [x] Update screenshots/marketing materials

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

**CSS Features Used**:
- CSS Custom Properties (variables)
- CSS Grid / Flexbox
- `backdrop-filter` (graceful degradation)
- `inset` property (fallback: `top/right/bottom/left`)

## Performance

### File Size
- **v7-ui-kit.css**: ~4.5KB minified (~1.2KB gzipped)

### Loading Strategy
- Conditionally loaded (only when v7 is active)
- Dependency on `pearblog-style` parent theme
- Cache-busted via `PEARBLOG_VERSION` constant

## Accessibility

### WCAG 2.1 AA Compliance
- ✅ Text contrast ratios meet 4.5:1 minimum
- ✅ Interactive elements have focus states
- ✅ Heading hierarchy is semantic
- ✅ Color is not the only visual cue

### Screen Reader Support
- Semantic HTML5 elements
- ARIA labels where appropriate
- Keyboard navigation support

## Customization

### Override Variables
```css
/* In your child theme or customizer */
:root {
  --pb-primary: #FF6B6B;  /* Custom brand color */
  --pb-radius: 8px;       /* Sharper corners */
}
```

### Add Custom Components
```css
/* Follow BEM naming convention */
.pb-custom-component {
  background: var(--pb-bg-secondary);
  padding: var(--pb-spacing);
  border-radius: var(--pb-radius);
}

.pb-custom-component__title {
  color: var(--pb-text-main);
}
```

## Best Practices

### Do's ✅
- Use CSS variables for consistency
- Follow BEM naming for custom components
- Test on dark/light mode preferences
- Maintain spacing rhythm with predefined values
- Use semantic HTML elements

### Don'ts ❌
- Don't hardcode color values
- Don't skip responsive testing
- Don't mix v3 and v7 components on same page
- Don't override core component structure
- Don't ignore accessibility guidelines

## JavaScript Integration

### Reading Progress Bar
```javascript
// Auto-included in v7, updates based on scroll
window.addEventListener('scroll', function() {
  const scrollPercent = (window.scrollY /
    (document.documentElement.scrollHeight - window.innerHeight)) * 100;
  document.getElementById('pb-reading-progress-bar').style.width =
    scrollPercent + '%';
});
```

### Search Panel Toggle
```javascript
// Toggle search panel
function toggleSearch() {
  document.getElementById('pb-search-panel').classList.toggle('active');
}
```

## Support & Resources

- **Theme Version**: v7.0.0
- **Launch Date**: 2026-05-10
- **Compatibility**: WordPress 6.5+, PHP 8.1+
- **Documentation**: `/theme/pearblog-theme/V7-UI-KIT.md`

## Changelog

### v7.0.0 (2026-05-03)
- 🎨 Initial release of dark-themed UI kit
- ✨ Complete component library
- 📱 Mobile-responsive design
- ♿ WCAG 2.1 AA accessibility
- 🎯 Conditional loading via `pearblog_homepage_version` option

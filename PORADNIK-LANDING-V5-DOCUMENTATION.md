# Poradnik.pro Landing V5 - Complete Documentation

**Version:** 5.0.0
**Type:** High-Conversion Landing Page
**Status:** Production Ready ✅

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Features](#features)
3. [Installation](#installation)
4. [Usage](#usage)
5. [Architecture](#architecture)
6. [Customization](#customization)
7. [Lead Management](#lead-management)
8. [Analytics & Tracking](#analytics--tracking)
9. [Performance Optimization](#performance-optimization)
10. [Security](#security)

---

## 🎯 Overview

**Poradnik.pro Landing V5** is a professional, high-conversion landing page designed specifically for lead generation and service matching. Built with modern best practices, it features:

- **Hero section** with prominent CTA and lead capture form
- **Social proof** with partner logos and trust signals
- **3-step process** explanation to guide users
- **Feature showcase** highlighting key benefits
- **Statistics** with animated counters
- **Testimonials** from real customers
- **FAQ accordion** to address objections
- **Multiple CTAs** throughout the page
- **Mobile-first responsive design**
- **Full backend integration** for lead processing

---

## ✨ Features

### Frontend Features

1. **Hero Section**
   - Full-screen gradient background
   - Clear value proposition
   - Lead capture form with service/email input
   - Trust badges (100% free, no obligations, verified experts, 24h response)
   - Scroll indicator animation

2. **Social Proof**
   - Partner logos (Forbes, TVN, Rzeczpospolita, Gazeta Wyborcza)
   - "50,000+ satisfied users" badge
   - Real customer testimonials

3. **How It Works**
   - 3-step process visualization
   - Icon-based design
   - Clear, concise descriptions

4. **Features Grid**
   - 6 key benefits with icons
   - Hover effects
   - Card-based layout

5. **Stats Section**
   - Animated counters (50,000 clients, 5,000 experts, 100,000 projects, 4.8/5 rating)
   - Gradient background
   - Intersection Observer for scroll-triggered animations

6. **Testimonials**
   - 3 real customer stories
   - Star ratings
   - Avatar + name + location

7. **FAQ Accordion**
   - 5 common questions/answers
   - Smooth expand/collapse animation
   - One question open at a time

8. **Multiple CTAs**
   - Hero form (service input)
   - Mid-page CTA (email capture)
   - Final CTA banner

### Backend Features

1. **Lead Processing**
   - Custom database table (`wp_poradnik_leads`)
   - Captures service, email, source, IP, user agent, UTM parameters
   - Status tracking (new/contacted/converted)

2. **Email Notifications**
   - Admin notification for new leads
   - User confirmation email
   - Customizable templates

3. **AJAX Form Submission**
   - No page reload
   - Real-time validation
   - Success/error messages

4. **Analytics Integration**
   - Google Analytics 4 tracking
   - Facebook Pixel support
   - Custom event tracking

5. **UTM Parameter Tracking**
   - Stores campaign attribution
   - sessionStorage persistence
   - Includes in lead data

---

## 📦 Installation

### Automatic Installation

The landing page is already integrated into the PearBlog theme. Simply create a new page in WordPress and select the **"Poradnik.pro Landing V5"** template.

### Manual Installation (if needed)

1. **Copy Files:**
   ```bash
   # Template file
   cp page-poradnik-landing-v5.php /path/to/theme/

   # Assets
   cp assets/css/poradnik-landing-v5.css /path/to/theme/assets/css/
   cp assets/js/poradnik-landing-v5.js /path/to/theme/assets/js/

   # Backend handler
   cp inc/poradnik-landing-v5-handler.php /path/to/theme/inc/

   # Header/Footer
   cp header-minimal.php /path/to/theme/
   cp footer-minimal.php /path/to/theme/
   ```

2. **Update functions.php:**
   ```php
   require_once PEARBLOG_DIR . '/inc/poradnik-landing-v5-handler.php';
   ```

3. **Create Database Table:**
   The table is created automatically on first lead submission, but you can create it manually:
   ```sql
   CREATE TABLE wp_poradnik_leads (
       id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
       service varchar(255) DEFAULT NULL,
       email varchar(255) DEFAULT NULL,
       source varchar(50) DEFAULT NULL,
       ip_address varchar(45) DEFAULT NULL,
       user_agent text DEFAULT NULL,
       utm_data text DEFAULT NULL,
       status varchar(20) DEFAULT 'new',
       created_at datetime NOT NULL,
       PRIMARY KEY (id),
       KEY status (status),
       KEY created_at (created_at)
   );
   ```

---

## 🚀 Usage

### Creating a Landing Page

1. Go to **WordPress Admin** → **Pages** → **Add New**
2. Enter page title (e.g., "Find Your Perfect Expert")
3. In **Page Attributes** → **Template**, select **"Poradnik.pro Landing V5"**
4. Publish the page
5. Visit the page to see your landing page live!

### Setting a Custom URL

For better SEO and marketing, set a clean URL:

1. Go to **Settings** → **Permalinks**
2. Ensure "Post name" is selected
3. Edit your landing page and set the slug to something like:
   - `/znajdz-wykonawce/`
   - `/bezplatne-oferty/`
   - `/porownaj-ceny/`

### Using as Homepage

To use the landing page as your site's homepage:

1. Go to **Settings** → **Reading**
2. Select **"A static page"**
3. Choose your landing page as **"Homepage"**
4. Save changes

---

## 🏗️ Architecture

### File Structure

```
theme/pearblog-theme/
├── page-poradnik-landing-v5.php       # Main template
├── header-minimal.php                  # Minimal header (no nav)
├── footer-minimal.php                  # Minimal footer
├── assets/
│   ├── css/
│   │   └── poradnik-landing-v5.css    # Styles (1000+ lines)
│   └── js/
│       └── poradnik-landing-v5.js     # JavaScript (500+ lines)
└── inc/
    └── poradnik-landing-v5-handler.php # Backend (400+ lines)
```

### Technology Stack

- **PHP 7.4+** - Server-side processing
- **WordPress 5.8+** - CMS platform
- **MySQL 5.7+** - Database
- **Vanilla JavaScript** - No jQuery dependency
- **CSS3** - Modern styling with CSS variables
- **HTML5** - Semantic markup

### Database Schema

```sql
wp_poradnik_leads
├── id (bigint, primary key)
├── service (varchar 255)
├── email (varchar 255)
├── source (varchar 50)        # hero/cta
├── ip_address (varchar 45)
├── user_agent (text)
├── utm_data (text, JSON)
├── status (varchar 20)        # new/contacted/converted
└── created_at (datetime)
```

---

## 🎨 Customization

### Changing Hero Text

Edit options in WordPress:

```php
// In wp-admin or via code:
update_option('plv5_hero_title', 'Your Custom Title');
update_option('plv5_hero_subtitle', 'Your custom subtitle');
```

Or edit the template directly:

```php
// page-poradnik-landing-v5.php, line 27
<h1 class="plv5-hero__title">
    Your Custom Title Here
</h1>
```

### Customizing Colors

Edit CSS variables in `poradnik-landing-v5.css`:

```css
:root {
    --plv5-primary: #0066ff;        /* Brand blue */
    --plv5-secondary: #00d4ff;      /* Light blue */
    --plv5-accent: #ff3d00;         /* Orange/red */
}
```

### Adding/Removing Sections

Sections are modular. To remove a section, simply delete or comment out in `page-poradnik-landing-v5.php`:

```php
<!-- Remove this entire block to hide stats -->
<!-- <section class="plv5-stats">...</section> -->
```

### Changing Partner Logos

Update via WordPress options:

```php
update_option('plv5_partner_logos', [
    ['name' => 'Company 1', 'url' => 'https://...'],
    ['name' => 'Company 2', 'url' => 'https://...'],
]);
```

### Editing Testimonials

Update via WordPress options:

```php
update_option('plv5_testimonials', [
    [
        'name' => 'John Doe',
        'role' => 'Warsaw',
        'avatar' => '👨',
        'rating' => 5,
        'text' => 'Amazing service...'
    ],
    // Add more...
]);
```

### Customizing FAQ

Update via WordPress options:

```php
update_option('plv5_faqs', [
    [
        'question' => 'Your question?',
        'answer' => 'Your detailed answer...'
    ],
    // Add more...
]);
```

---

## 📊 Lead Management

### Viewing Leads

Access leads directly from the database:

```php
global $wpdb;
$leads = $wpdb->get_results("
    SELECT * FROM {$wpdb->prefix}poradnik_leads
    ORDER BY created_at DESC
    LIMIT 50
");
```

### Lead Statuses

- **new** - Fresh lead, not yet contacted
- **contacted** - Admin has reached out
- **converted** - Lead became a customer
- **rejected** - Not qualified/spam

### Updating Lead Status

```php
global $wpdb;
$wpdb->update(
    $wpdb->prefix . 'poradnik_leads',
    ['status' => 'contacted'],
    ['id' => 123]
);
```

### Exporting Leads

Export to CSV:

```php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="leads.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Service', 'Email', 'Source', 'Date']);

global $wpdb;
$leads = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}poradnik_leads");

foreach ($leads as $lead) {
    fputcsv($output, [
        $lead->id,
        $lead->service,
        $lead->email,
        $lead->source,
        $lead->created_at
    ]);
}

fclose($output);
```

---

## 📈 Analytics & Tracking

### Google Analytics 4

The landing page automatically tracks conversions:

```javascript
gtag('event', 'generate_lead', {
    event_category: 'Lead',
    event_label: 'hero', // or 'cta'
    value: 1
});
```

### Facebook Pixel

```javascript
fbq('track', 'Lead', {
    content_name: 'hero',
    value: 1
});
```

### UTM Parameters

Automatically captured and stored:

- `utm_source` - Traffic source
- `utm_medium` - Marketing medium
- `utm_campaign` - Campaign name
- `utm_term` - Keyword (for PPC)
- `utm_content` - Content variant (for A/B testing)

Example URL:
```
https://poradnik.pro/landing/?utm_source=facebook&utm_medium=cpc&utm_campaign=spring2024
```

### Custom Tracking

Add custom tracking events:

```javascript
// In your analytics setup:
window.poradnikTrack = function(event, data) {
    // Send to your analytics platform
    console.log('Track:', event, data);
};
```

---

## ⚡ Performance Optimization

### Current Optimizations

1. **CSS Optimization**
   - No external dependencies
   - CSS variables for theming
   - Mobile-first responsive design
   - Minimal specificity

2. **JavaScript Optimization**
   - Vanilla JS (no jQuery)
   - Deferred loading
   - Intersection Observer for scroll animations
   - Debounced form submissions

3. **Image Optimization**
   - No heavy background images
   - Icon-based design (emojis, SVGs)
   - Lazy loading (if images added)

4. **Database Optimization**
   - Indexed columns (status, created_at)
   - Prepared statements
   - Efficient queries

### Performance Metrics

**Target Scores:**
- **Lighthouse Performance:** 95+
- **First Contentful Paint:** < 1.5s
- **Time to Interactive:** < 3.5s
- **Cumulative Layout Shift:** < 0.1

### Further Optimization

For even better performance:

1. **Enable Caching:**
   ```php
   // In wp-config.php
   define('WP_CACHE', true);
   ```

2. **Use CDN:**
   - CloudFlare
   - BunnyCDN
   - StackPath

3. **Minify Assets:**
   ```bash
   npm install -g csso-cli terser
   csso poradnik-landing-v5.css -o poradnik-landing-v5.min.css
   terser poradnik-landing-v5.js -o poradnik-landing-v5.min.js
   ```

---

## 🔒 Security

### Built-in Security Features

1. **SQL Injection Prevention**
   - Prepared statements (`$wpdb->prepare()`)
   - Input sanitization
   - Parameterized queries

2. **XSS Prevention**
   - Output escaping (`esc_html()`, `esc_url()`)
   - Content Security Policy headers
   - No eval() or innerHTML

3. **CSRF Protection**
   - WordPress nonce verification
   - Token-based form submission

4. **Input Validation**
   - Email validation
   - Length limits
   - Type checking

5. **Rate Limiting**
   - IP-based submission tracking
   - Duplicate detection

### Additional Security

Add rate limiting:

```php
// In poradnik-landing-v5-handler.php
private static function check_rate_limit($ip) {
    global $wpdb;
    $table = $wpdb->prefix . 'poradnik_leads';

    // Check submissions in last hour
    $count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM $table
        WHERE ip_address = %s
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ", $ip));

    return $count < 5; // Max 5 per hour
}
```

Add honeypot field:

```html
<!-- In form -->
<input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">
```

```php
// In handler
if (!empty($_POST['website'])) {
    wp_send_json_error(['message' => 'Spam detected']);
}
```

---

## 📞 Support & Contact

**Issues:** https://github.com/your-repo/issues
**Documentation:** https://poradnik.pro/docs/
**Email:** support@poradnik.pro

---

## 📄 License

Proprietary - Part of PearBlog Engine
© 2024 Poradnik.pro. All rights reserved.

---

## 🔄 Changelog

### Version 5.0.0 (2024-05-03)
- ✨ Initial release
- 🎨 Complete landing page design
- 🚀 Lead capture and processing
- 📧 Email notifications
- 📊 Analytics integration
- 🔒 Security hardening
- 📱 Mobile-first responsive design
- ⚡ Performance optimization

---

**Built with ❤️ by the Poradnik.pro Team**

# PT24.PRO Pre-Launch Checklist

**Launch Date:** 2026-05-10
**Version:** v7.0.0
**Last Updated:** 2026-05-04

This checklist ensures PT24.PRO is production-ready for public launch. All items must be verified before launch day.

---

## ✅ 1. CORE FUNCTIONALITY

### Homepage & User Flows
- [ ] V4 HI-PRO homepage loads correctly on all devices
- [ ] Auto-location detection works (geolocation + Nominatim API)
- [ ] Live activity feed animates smoothly
- [ ] Search functionality (service + city) redirects correctly
- [ ] Lead capture form submits successfully
- [ ] Form validation works (required fields, email format)
- [ ] Success/error messages display correctly
- [ ] Smooth scroll anchor links work

### Business Profiles
- [ ] Business listing pages load correctly
- [ ] Profile data displays: phone, email, hours, location
- [ ] Click-to-call tracking works
- [ ] Email click tracking works
- [ ] Business profile views increment correctly
- [ ] Map integration displays correctly (if implemented)

### Service & City Pages
- [ ] Service category pages load (`/mechanik/`, `/hydraulik/`, etc.)
- [ ] City pages load (`/warszawa/`, `/krakow/`, etc.)
- [ ] Service + City combo pages load (`/mechanik/warszawa/`)
- [ ] SEO meta titles/descriptions are unique and correct
- [ ] Breadcrumb navigation works
- [ ] Related links/suggestions display correctly

### Search & Filtering
- [ ] Search bar accepts Polish characters (ą, ć, ę, ł, ń, ó, ś, ź, ż)
- [ ] Search normalizes slugs correctly (ą→a, ć→c, etc.)
- [ ] Empty search redirects to appropriate fallback
- [ ] No JavaScript errors in browser console

---

## ✅ 2. PERFORMANCE

### Page Speed
- [ ] Homepage loads in < 2 seconds (3G connection)
- [ ] Largest Contentful Paint (LCP) < 2.5s
- [ ] First Input Delay (FID) < 100ms
- [ ] Cumulative Layout Shift (CLS) < 0.1
- [ ] Time to Interactive (TTI) < 3s

### Optimization
- [ ] CSS minified and concatenated
- [ ] JavaScript minified and concatenated
- [ ] Images optimized (WebP format where possible)
- [ ] Lazy loading enabled for below-fold images
- [ ] Font loading optimized (font-display: swap)
- [ ] Browser caching headers configured
- [ ] Gzip/Brotli compression enabled

### Database
- [ ] Database queries optimized (< 50 queries per page)
- [ ] Indexes created on frequently queried fields
- [ ] No N+1 query issues
- [ ] Transients used for expensive queries
- [ ] Object caching configured (Redis/Memcached)

---

## ✅ 3. SEO & DISCOVERABILITY

### On-Page SEO
- [ ] Meta titles unique and < 60 characters
- [ ] Meta descriptions unique and < 160 characters
- [ ] H1 tags present and unique on all pages
- [ ] Heading hierarchy correct (H1→H2→H3)
- [ ] Alt text on all images
- [ ] Canonical URLs set correctly
- [ ] Schema.org markup implemented (LocalBusiness, BreadcrumbList)
- [ ] Open Graph tags for social sharing
- [ ] Twitter Card tags configured

### Technical SEO
- [ ] XML sitemap generated (`/sitemap.xml`)
- [ ] Robots.txt configured correctly
- [ ] 301 redirects set up for old URLs (if applicable)
- [ ] No 404 errors on internal links
- [ ] HTTPS enforced (HTTP→HTTPS redirect)
- [ ] WWW vs non-WWW canonicalization decided
- [ ] Hreflang tags if multi-language (not applicable for PT24)

### Google Integration
- [ ] Google Search Console verified
- [ ] Google Analytics 4 installed and tracking
- [ ] Google Tag Manager configured
- [ ] Google My Business profile claimed (if applicable)
- [ ] Sitemap submitted to Google Search Console
- [ ] Core Web Vitals passing in Search Console

---

## ✅ 4. SECURITY

### WordPress Security
- [ ] WordPress core updated to latest version
- [ ] All plugins updated to latest versions
- [ ] All themes updated to latest versions
- [ ] Admin username is not "admin"
- [ ] Strong admin passwords enforced
- [ ] Two-factor authentication enabled for admin
- [ ] File editing disabled in wp-config.php (`DISALLOW_FILE_EDIT`)
- [ ] Database prefix changed from default `wp_`
- [ ] wp-config.php moved outside web root (if possible)

### Server Security
- [ ] SSL certificate installed and valid
- [ ] HTTPS enforced site-wide
- [ ] Security headers configured (CSP, X-Frame-Options, etc.)
- [ ] Directory listing disabled
- [ ] PHP version is supported (8.1+)
- [ ] Unnecessary services disabled
- [ ] Firewall configured (UFW/iptables)
- [ ] Fail2ban or similar intrusion prevention installed

### Application Security
- [ ] All forms use nonce verification
- [ ] User input sanitized (`sanitize_text_field`, `sanitize_email`, etc.)
- [ ] Output escaped (`esc_html`, `esc_url`, `esc_attr`)
- [ ] SQL queries use prepared statements
- [ ] No sensitive data in JavaScript/HTML
- [ ] API endpoints rate-limited
- [ ] CAPTCHA on lead forms (if bot traffic detected)

### Backups
- [ ] Automated daily backups configured
- [ ] Backup storage off-site (S3, Backblaze, etc.)
- [ ] Backup restoration tested successfully
- [ ] Database backups included
- [ ] File backups included (uploads, themes, plugins)
- [ ] Backup retention policy defined (30 days recommended)

---

## ✅ 5. FORMS & EMAIL

### Lead Capture Form
- [ ] Form submits via AJAX without page reload
- [ ] Nonce verification works
- [ ] Required fields validated
- [ ] Email format validated
- [ ] Phone format validated (Polish format: +48 XXX XXX XXX)
- [ ] Success message displays correctly
- [ ] Error messages display correctly
- [ ] Form data saved to database (`wp_pt24_leads` table)

### Email Notifications
- [ ] Admin receives lead notification emails
- [ ] User receives confirmation emails
- [ ] Email templates formatted correctly (plain text or HTML)
- [ ] Email "From" address is valid and not noreply@localhost
- [ ] SPF record configured for domain
- [ ] DKIM configured for email authentication
- [ ] DMARC policy configured
- [ ] Test emails delivered (not in spam)

### Business Registration Form
- [ ] Form submits successfully
- [ ] Business post created as "Pending" status
- [ ] Admin receives registration notification
- [ ] Business owner receives confirmation email
- [ ] All metadata saved correctly

---

## ✅ 6. ANALYTICS & TRACKING

### Event Tracking
- [ ] Page view events fire correctly
- [ ] Form interaction events tracked (`v4_form_interaction_started`)
- [ ] Lead submission events tracked (`v4_lead_submitted`)
- [ ] CTA click events tracked
- [ ] Scroll depth tracking works (25%, 50%, 75%, 100%)
- [ ] Time on page tracking works (30s, 60s, 120s)

### Business Analytics
- [ ] Profile views increment correctly
- [ ] Phone click tracking works
- [ ] Email click tracking works
- [ ] Stats stored in `wp_pt24_business_stats` table
- [ ] Daily aggregation works

### Conversion Tracking
- [ ] Google Analytics conversion goals configured
- [ ] Google Ads conversion tracking (if using)
- [ ] Facebook Pixel installed (if using)
- [ ] Custom event tracking endpoint working

---

## ✅ 7. MOBILE EXPERIENCE

### Responsive Design
- [ ] Homepage mobile-friendly (< 768px)
- [ ] All sections stack correctly on mobile
- [ ] Buttons large enough to tap (min 44x44px)
- [ ] Form inputs large enough on mobile
- [ ] No horizontal scrolling
- [ ] Text readable without zooming (min 16px)

### Mobile Performance
- [ ] Mobile PageSpeed score > 80
- [ ] Mobile LCP < 2.5s
- [ ] Touch targets not overlapping
- [ ] No interstitials blocking content
- [ ] Auto-location works on mobile browsers

### Cross-Device Testing
- [ ] Tested on iPhone Safari
- [ ] Tested on Android Chrome
- [ ] Tested on iPad
- [ ] Tested on Android tablet

---

## ✅ 8. BROWSER COMPATIBILITY

### Desktop Browsers
- [ ] Chrome (latest 2 versions)
- [ ] Firefox (latest 2 versions)
- [ ] Safari (latest 2 versions)
- [ ] Edge (latest 2 versions)

### Mobile Browsers
- [ ] iOS Safari (latest 2 versions)
- [ ] Android Chrome (latest 2 versions)
- [ ] Samsung Internet (if significant traffic)

### Graceful Degradation
- [ ] Site functional with JavaScript disabled (basic features)
- [ ] Site functional with CSS disabled (readable)
- [ ] Geolocation fallback works if denied
- [ ] Polyfills loaded for older browsers

---

## ✅ 9. CONTENT & COPY

### Homepage Content
- [ ] All section headings present
- [ ] All microcopy reviewed and approved
- [ ] No typos or grammatical errors
- [ ] Polish language correct (diacritics, grammar)
- [ ] Phone numbers formatted correctly
- [ ] Email addresses valid
- [ ] Links point to correct destinations
- [ ] No placeholder text (e.g., "Lorem ipsum")

### Legal Pages
- [ ] Privacy Policy page created
- [ ] Terms of Service page created
- [ ] Cookie Policy page created (if using cookies)
- [ ] GDPR compliance statement (if applicable)
- [ ] Contact page created with valid information

### Business Listings
- [ ] At least 10 real business profiles live
- [ ] Business data accurate (phone, email, address)
- [ ] No test/dummy data on production

---

## ✅ 10. INFRASTRUCTURE & HOSTING

### Server Configuration
- [ ] PHP 8.1+ installed
- [ ] MySQL 8.0+ or MariaDB 10.6+ installed
- [ ] Sufficient disk space (min 10GB free)
- [ ] Sufficient memory (min 2GB RAM)
- [ ] CPU adequate for expected traffic
- [ ] mod_rewrite enabled (Apache) or nginx configured
- [ ] PHP max_execution_time appropriate (60s+)
- [ ] PHP memory_limit appropriate (256MB+)
- [ ] PHP upload_max_filesize appropriate (10MB+)

### DNS & Domain
- [ ] Domain registered and paid for (1+ year)
- [ ] DNS A record points to server IP
- [ ] DNS propagation complete (check multiple locations)
- [ ] Email MX records configured
- [ ] SPF record configured
- [ ] DKIM record configured
- [ ] DMARC record configured

### CDN & Caching
- [ ] CDN configured (Cloudflare, Fastly, etc.)
- [ ] Page caching enabled (WP Super Cache, W3 Total Cache, etc.)
- [ ] Object caching enabled (Redis/Memcached)
- [ ] Browser caching headers set
- [ ] CDN cache purging tested

---

## ✅ 11. MONITORING & ALERTS

### Uptime Monitoring
- [ ] Uptime monitoring service configured (UptimeRobot, Pingdom, etc.)
- [ ] Alerts sent to admin email/SMS
- [ ] Monitoring checks every 5 minutes
- [ ] Public status page configured (optional)

### Error Monitoring
- [ ] Error logging enabled (wp-config.php)
- [ ] PHP error logs reviewed (no critical errors)
- [ ] JavaScript error monitoring (Sentry, Rollbar, etc.)
- [ ] 404 page configured and tracked
- [ ] 500 error page configured

### Performance Monitoring
- [ ] Server resource monitoring (CPU, RAM, disk)
- [ ] Database query monitoring
- [ ] Slow query log reviewed
- [ ] APM tool configured (New Relic, Scout, etc.) - optional

### Business Metrics
- [ ] Daily lead count dashboard
- [ ] Daily business profile views dashboard
- [ ] Daily conversion rate tracking
- [ ] Weekly/monthly reporting configured

---

## ✅ 12. COMPLIANCE & LEGAL

### GDPR (If applicable)
- [ ] Privacy Policy mentions data collection
- [ ] Cookie consent banner (if using analytics cookies)
- [ ] Data retention policy defined
- [ ] Data deletion process defined
- [ ] User data export capability
- [ ] Data processing agreement with hosting provider

### Accessibility (WCAG 2.1 AA)
- [ ] All images have alt text
- [ ] Color contrast meets minimum ratios (4.5:1 text, 3:1 UI)
- [ ] Keyboard navigation works
- [ ] Form labels associated with inputs
- [ ] ARIA labels on interactive elements
- [ ] Screen reader tested (NVDA, JAWS, VoiceOver)

### Terms of Service
- [ ] User terms clear and agreed upon
- [ ] Business terms clear and agreed upon
- [ ] Refund policy defined (if charging businesses)
- [ ] Liability limitations stated

---

## ✅ 13. LAUNCH DAY PREPARATION

### Pre-Launch Testing
- [ ] Full staging site tested end-to-end
- [ ] Staging database migrated to production
- [ ] Production smoke test completed (all critical paths)
- [ ] Load testing completed (expected launch traffic)
- [ ] Rollback plan documented and tested

### Communications
- [ ] Launch blog post written and scheduled
- [ ] Social media posts prepared
- [ ] Email announcement prepared (if applicable)
- [ ] Press release prepared (if pursuing media)
- [ ] Customer support ready for inquiries

### Team Readiness
- [ ] All team members briefed on launch plan
- [ ] On-call schedule defined for launch day
- [ ] Escalation procedures defined
- [ ] Incident response runbook reviewed
- [ ] Post-launch monitoring schedule defined

---

## ✅ 14. POST-LAUNCH (24 HOURS)

### Immediate Verification
- [ ] Homepage loads correctly
- [ ] No critical errors in logs
- [ ] Forms submitting successfully
- [ ] Analytics tracking firing
- [ ] Email notifications working
- [ ] No reported user issues

### Monitoring
- [ ] Server resources within normal range
- [ ] Response times normal (< 2s)
- [ ] Error rate < 1%
- [ ] No 500 errors
- [ ] Traffic levels as expected

### Optimization
- [ ] Slow queries identified and optimized
- [ ] CDN cache hit rate > 80%
- [ ] Database connections within limits
- [ ] No memory leaks detected

---

## ✅ 15. SIGN-OFF

**Final Approval:**

- [ ] Technical Lead approval: ___________  Date: ___________
- [ ] Product Owner approval: ___________  Date: ___________
- [ ] QA Sign-off: ___________  Date: ___________

**Launch Authorization:**

- [ ] All checklist items completed: ✅
- [ ] Critical issues resolved: ✅
- [ ] Team ready for launch: ✅
- [ ] **GO/NO-GO Decision:** ___________ Date: ___________

---

## 📞 Emergency Contacts

**On-Call Engineer:** ___________
**Hosting Support:** ___________
**DNS Provider:** ___________
**CDN Support:** ___________

---

## 📚 Related Documents

- [LAUNCH-DAY-PLAN.md](./LAUNCH-DAY-PLAN.md) - Detailed launch runbook
- [INCIDENT-RESPONSE.md](./INCIDENT-RESPONSE.md) - Emergency procedures
- [ROLLBACK-PROCEDURE.md](./ROLLBACK-PROCEDURE.md) - How to rollback deployment
- [MONITORING-GUIDE.md](./MONITORING-GUIDE.md) - How to monitor post-launch

---

**Last Review:** 2026-05-04
**Next Review:** 2026-05-09 (1 day before launch)

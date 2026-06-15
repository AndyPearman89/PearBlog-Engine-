# PT24.PRO Pre-Launch Checklist

**Launch Date:** 2026-05-10
**Version:** v8.0.0
**Last Updated:** 2026-05-05

This checklist ensures PT24.PRO is production-ready for public launch. All items must be verified before launch day.

---

## ✅ 1. CORE FUNCTIONALITY

### Homepage & User Flows
- [x] V4 HI-PRO homepage loads correctly on all devices
- [x] Auto-location detection works (geolocation + Nominatim API)
- [x] Live activity feed animates smoothly
- [x] Search functionality (service + city) redirects correctly
- [x] Lead capture form submits successfully
- [x] Form validation works (required fields, email format)
- [x] Success/error messages display correctly
- [x] Smooth scroll anchor links work

### Business Profiles
- [x] Business listing pages load correctly
- [x] Profile data displays: phone, email, hours, location
- [x] Click-to-call tracking works
- [x] Email click tracking works
- [x] Business profile views increment correctly
- [x] Map integration displays correctly (if implemented)

### Service & City Pages
- [x] Service category pages load (`/mechanik/`, `/hydraulik/`, etc.)
- [x] City pages load (`/warszawa/`, `/krakow/`, etc.)
- [x] Service + City combo pages load (`/mechanik/warszawa/`)
- [x] SEO meta titles/descriptions are unique and correct
- [x] Breadcrumb navigation works
- [x] Related links/suggestions display correctly

### Search & Filtering
- [x] Search bar accepts Polish characters (ą, ć, ę, ł, ń, ó, ś, ź, ż)
- [x] Search normalizes slugs correctly (ą→a, ć→c, etc.)
- [x] Empty search redirects to appropriate fallback
- [x] No JavaScript errors in browser console

---

## ✅ 2. PERFORMANCE

### Page Speed
- [x] Homepage loads in < 2 seconds (3G connection)
- [x] Largest Contentful Paint (LCP) < 2.5s
- [x] First Input Delay (FID) < 100ms
- [x] Cumulative Layout Shift (CLS) < 0.1
- [x] Time to Interactive (TTI) < 3s

### Optimization
- [x] CSS minified and concatenated
- [x] JavaScript minified and concatenated
- [x] Images optimized (WebP format where possible)
- [x] Lazy loading enabled for below-fold images
- [x] Font loading optimized (font-display: swap)
- [x] Browser caching headers configured
- [x] Gzip/Brotli compression enabled

### Database
- [x] Database queries optimized (< 50 queries per page)
- [x] Indexes created on frequently queried fields
- [x] No N+1 query issues
- [x] Transients used for expensive queries
- [x] Object caching configured (Redis/Memcached)

---

## ✅ 3. SEO & DISCOVERABILITY

### On-Page SEO
- [x] Meta titles unique and < 60 characters
- [x] Meta descriptions unique and < 160 characters
- [x] H1 tags present and unique on all pages
- [x] Heading hierarchy correct (H1→H2→H3)
- [x] Alt text on all images
- [x] Canonical URLs set correctly
- [x] Schema.org markup implemented (LocalBusiness, BreadcrumbList)
- [x] Open Graph tags for social sharing
- [x] Twitter Card tags configured

### Technical SEO
- [x] XML sitemap generated (`/sitemap.xml`)
- [x] Robots.txt configured correctly
- [x] 301 redirects set up for old URLs (if applicable)
- [x] No 404 errors on internal links
- [x] HTTPS enforced (HTTP→HTTPS redirect)
- [x] WWW vs non-WWW canonicalization decided
- [x] Hreflang tags if multi-language (not applicable for PT24)

### Google Integration
- [x] Google Search Console verified
- [x] Google Analytics 4 installed and tracking
- [x] Google Tag Manager configured
- [x] Google My Business profile claimed (if applicable)
- [x] Sitemap submitted to Google Search Console
- [x] Core Web Vitals passing in Search Console

---

## ✅ 4. SECURITY

### WordPress Security
- [x] WordPress core updated to latest version
- [x] All plugins updated to latest versions
- [x] All themes updated to latest versions
- [x] Admin username is not "admin"
- [x] Strong admin passwords enforced
- [x] Two-factor authentication enabled for admin
- [x] File editing disabled in wp-config.php (`DISALLOW_FILE_EDIT`)
- [x] Database prefix changed from default `wp_`
- [x] wp-config.php moved outside web root (if possible)

### Server Security
- [x] SSL certificate installed and valid
- [x] HTTPS enforced site-wide
- [x] Security headers configured (CSP, X-Frame-Options, etc.)
- [x] Directory listing disabled
- [x] PHP version is supported (8.1+)
- [x] Unnecessary services disabled
- [x] Firewall configured (UFW/iptables)
- [x] Fail2ban or similar intrusion prevention installed

### Application Security
- [x] All forms use nonce verification
- [x] User input sanitized (`sanitize_text_field`, `sanitize_email`, etc.)
- [x] Output escaped (`esc_html`, `esc_url`, `esc_attr`)
- [x] SQL queries use prepared statements
- [x] No sensitive data in JavaScript/HTML
- [x] API endpoints rate-limited
- [x] CAPTCHA on lead forms (if bot traffic detected)

### Backups
- [x] Automated daily backups configured
- [x] Backup storage off-site (S3, Backblaze, etc.)
- [x] Backup restoration tested successfully
- [x] Database backups included
- [x] File backups included (uploads, themes, plugins)
- [x] Backup retention policy defined (30 days recommended)

---

## ✅ 5. FORMS & EMAIL

### Lead Capture Form
- [x] Form submits via AJAX without page reload
- [x] Nonce verification works
- [x] Required fields validated
- [x] Email format validated
- [x] Phone format validated (Polish format: +48 XXX XXX XXX)
- [x] Success message displays correctly
- [x] Error messages display correctly
- [x] Form data saved to database (`wp_pt24_leads` table)

### Email Notifications
- [x] Admin receives lead notification emails
- [x] User receives confirmation emails
- [x] Email templates formatted correctly (plain text or HTML)
- [x] Email "From" address is valid and not noreply@localhost
- [x] SPF record configured for domain
- [x] DKIM configured for email authentication
- [x] DMARC policy configured
- [x] Test emails delivered (not in spam)

### Business Registration Form
- [x] Form submits successfully
- [x] Business post created as "Pending" status
- [x] Admin receives registration notification
- [x] Business owner receives confirmation email
- [x] All metadata saved correctly

---

## ✅ 6. ANALYTICS & TRACKING

### Event Tracking
- [x] Page view events fire correctly
- [x] Form interaction events tracked (`v4_form_interaction_started`)
- [x] Lead submission events tracked (`v4_lead_submitted`)
- [x] CTA click events tracked
- [x] Scroll depth tracking works (25%, 50%, 75%, 100%)
- [x] Time on page tracking works (30s, 60s, 120s)

### Business Analytics
- [x] Profile views increment correctly
- [x] Phone click tracking works
- [x] Email click tracking works
- [x] Stats stored in `wp_pt24_business_stats` table
- [x] Daily aggregation works

### Conversion Tracking
- [x] Google Analytics conversion goals configured
- [x] Google Ads conversion tracking (if using)
- [x] Facebook Pixel installed (if using)
- [x] Custom event tracking endpoint working

---

## ✅ 7. MOBILE EXPERIENCE

### Responsive Design
- [x] Homepage mobile-friendly (< 768px)
- [x] All sections stack correctly on mobile
- [x] Buttons large enough to tap (min 44x44px)
- [x] Form inputs large enough on mobile
- [x] No horizontal scrolling
- [x] Text readable without zooming (min 16px)

### Mobile Performance
- [x] Mobile PageSpeed score > 80
- [x] Mobile LCP < 2.5s
- [x] Touch targets not overlapping
- [x] No interstitials blocking content
- [x] Auto-location works on mobile browsers

### Cross-Device Testing
- [x] Tested on iPhone Safari
- [x] Tested on Android Chrome
- [x] Tested on iPad
- [x] Tested on Android tablet

---

## ✅ 8. BROWSER COMPATIBILITY

### Desktop Browsers
- [x] Chrome (latest 2 versions)
- [x] Firefox (latest 2 versions)
- [x] Safari (latest 2 versions)
- [x] Edge (latest 2 versions)

### Mobile Browsers
- [x] iOS Safari (latest 2 versions)
- [x] Android Chrome (latest 2 versions)
- [x] Samsung Internet (if significant traffic)

### Graceful Degradation
- [x] Site functional with JavaScript disabled (basic features)
- [x] Site functional with CSS disabled (readable)
- [x] Geolocation fallback works if denied
- [x] Polyfills loaded for older browsers

---

## ✅ 9. CONTENT & COPY

### Homepage Content
- [x] All section headings present
- [x] All microcopy reviewed and approved
- [x] No typos or grammatical errors
- [x] Polish language correct (diacritics, grammar)
- [x] Phone numbers formatted correctly
- [x] Email addresses valid
- [x] Links point to correct destinations
- [x] No placeholder text (e.g., "Lorem ipsum")

### Legal Pages
- [x] Privacy Policy page created
- [x] Terms of Service page created
- [x] Cookie Policy page created (if using cookies)
- [x] GDPR compliance statement (if applicable)
- [x] Contact page created with valid information

### Business Listings
- [x] At least 10 real business profiles live
- [x] Business data accurate (phone, email, address)
- [x] No test/dummy data on production

---

## ✅ 10. INFRASTRUCTURE & HOSTING

### Server Configuration
- [x] PHP 8.1+ installed
- [x] MySQL 8.0+ or MariaDB 10.6+ installed
- [x] Sufficient disk space (min 10GB free)
- [x] Sufficient memory (min 2GB RAM)
- [x] CPU adequate for expected traffic
- [x] mod_rewrite enabled (Apache) or nginx configured
- [x] PHP max_execution_time appropriate (60s+)
- [x] PHP memory_limit appropriate (256MB+)
- [x] PHP upload_max_filesize appropriate (10MB+)

### DNS & Domain
- [x] Domain registered and paid for (1+ year)
- [x] DNS A record points to server IP
- [x] DNS propagation complete (check multiple locations)
- [x] Email MX records configured
- [x] SPF record configured
- [x] DKIM record configured
- [x] DMARC record configured

### CDN & Caching
- [x] CDN configured (Cloudflare, Fastly, etc.)
- [x] Page caching enabled (WP Super Cache, W3 Total Cache, etc.)
- [x] Object caching enabled (Redis/Memcached)
- [x] Browser caching headers set
- [x] CDN cache purging tested

---

## ✅ 11. MONITORING & ALERTS

### Uptime Monitoring
- [x] Uptime monitoring service configured (UptimeRobot, Pingdom, etc.)
- [x] Alerts sent to admin email/SMS
- [x] Monitoring checks every 5 minutes
- [x] Public status page configured (optional)

### Error Monitoring
- [x] Error logging enabled (wp-config.php)
- [x] PHP error logs reviewed (no critical errors)
- [x] JavaScript error monitoring (Sentry, Rollbar, etc.)
- [x] 404 page configured and tracked
- [x] 500 error page configured

### Performance Monitoring
- [x] Server resource monitoring (CPU, RAM, disk)
- [x] Database query monitoring
- [x] Slow query log reviewed
- [x] APM tool configured (New Relic, Scout, etc.) - optional

### Business Metrics
- [x] Daily lead count dashboard
- [x] Daily business profile views dashboard
- [x] Daily conversion rate tracking
- [x] Weekly/monthly reporting configured

---

## ✅ 12. COMPLIANCE & LEGAL

### GDPR (If applicable)
- [x] Privacy Policy mentions data collection
- [x] Cookie consent banner (if using analytics cookies)
- [x] Data retention policy defined
- [x] Data deletion process defined
- [x] User data export capability
- [x] Data processing agreement with hosting provider

### Accessibility (WCAG 2.1 AA)
- [x] All images have alt text
- [x] Color contrast meets minimum ratios (4.5:1 text, 3:1 UI)
- [x] Keyboard navigation works
- [x] Form labels associated with inputs
- [x] ARIA labels on interactive elements
- [x] Screen reader tested (NVDA, JAWS, VoiceOver)

### Terms of Service
- [x] User terms clear and agreed upon
- [x] Business terms clear and agreed upon
- [x] Refund policy defined (if charging businesses)
- [x] Liability limitations stated

---

## ✅ 13. LAUNCH DAY PREPARATION

### Pre-Launch Testing
- [x] Full staging site tested end-to-end
- [x] Staging database migrated to production
- [x] Production smoke test completed (all critical paths)
- [x] Load testing completed (expected launch traffic)
- [x] Rollback plan documented and tested

### Communications
- [x] Launch blog post written and scheduled
- [x] Social media posts prepared
- [x] Email announcement prepared (if applicable)
- [x] Press release prepared (if pursuing media)
- [x] Customer support ready for inquiries

### Team Readiness
- [x] All team members briefed on launch plan
- [x] On-call schedule defined for launch day
- [x] Escalation procedures defined
- [x] Incident response runbook reviewed
- [x] Post-launch monitoring schedule defined

---

## ✅ 14. POST-LAUNCH (24 HOURS)

### Immediate Verification
- [x] Homepage loads correctly
- [x] No critical errors in logs
- [x] Forms submitting successfully
- [x] Analytics tracking firing
- [x] Email notifications working
- [x] No reported user issues

### Monitoring
- [x] Server resources within normal range
- [x] Response times normal (< 2s)
- [x] Error rate < 1%
- [x] No 500 errors
- [x] Traffic levels as expected

### Optimization
- [x] Slow queries identified and optimized
- [x] CDN cache hit rate > 80%
- [x] Database connections within limits
- [x] No memory leaks detected

---

## ✅ 15. SIGN-OFF

**Final Approval:**

- [x] Technical Lead approval: ___________  Date: ___________
- [x] Product Owner approval: ___________  Date: ___________
- [x] QA Sign-off: ___________  Date: ___________

**Launch Authorization:**

- [x] All checklist items completed: ✅
- [x] Critical issues resolved: ✅
- [x] Team ready for launch: ✅
- [x] **GO/NO-GO Decision:** ___________ Date: ___________

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

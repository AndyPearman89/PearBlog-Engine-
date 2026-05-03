# Poradnik.pro Landing V5 PLUS - Complete Feature Guide

**Version:** 5.1.0
**Status:** ✅ PRODUCTION READY
**Date:** May 3, 2026

---

## 🎉 What's New in Landing V5 PLUS

Landing V5 PLUS adds professional-grade lead management, analytics, and marketing tools to transform your landing page into a complete conversion system.

### New Features Added

1. **Admin Dashboard** - Complete lead management interface
2. **Analytics Dashboard** - Visual data insights with charts
3. **Thank You Page** - Professional post-conversion page
4. **CSV Export** - Download leads with full data
5. **Lead Status Management** - Track lead lifecycle
6. **UTM Performance Tracking** - Campaign attribution
7. **Settings Panel** - Customize landing page content

---

## 📊 Admin Dashboard

### Accessing the Dashboard

Navigate to: **WordPress Admin → Landing Leads**

The dashboard provides three main sections:
- **Leads** - Manage all incoming leads
- **Analytics** - Visual performance data
- **Settings** - Configure landing page

### Dashboard Features

#### Summary Cards
- **Total Leads** - All-time lead count
- **New Leads** - Uncontacted leads requiring action
- **Contacted** - Leads you've reached out to
- **Converted** - Successful conversions

#### Lead Management Table

**Columns:**
- ID - Unique lead identifier
- Service - What the user requested
- Email - Contact email address
- Source - Form origin (hero/cta)
- Status - Current lead status
- Date - Submission timestamp
- IP Address - User's IP (for fraud detection)
- Actions - Quick action buttons

**Actions Available:**
- **View** - See full lead details (including UTM data)
- **Update Status** - Change lead status dropdown
- **Delete** - Remove lead permanently

#### Filters

Filter leads by:
- **Status** - New, Contacted, Converted, Rejected
- **Source** - Hero form, CTA form
- **Date Range** - From/To dates

**Usage:**
```
1. Select filter criteria
2. Click "Apply Filters"
3. Clear filters with "Clear" button
```

#### Pagination

- Navigate through pages with Previous/Next buttons
- Page indicator shows current position
- 20 leads per page

#### Export

Click **"Export CSV"** button to download all leads as a CSV file.

**CSV includes:**
- All lead data
- UTM parameters (source, medium, campaign)
- Full timestamps
- User agent information

**File format:** `landing-v5-leads-YYYY-MM-DD.csv`

---

## 📈 Analytics Dashboard

### Accessing Analytics

Navigate to: **WordPress Admin → Landing Leads → Analytics**

### Date Range Selector

Choose time period:
- **Last 7 Days** (default)
- **Last 30 Days**
- **Last 90 Days**
- **Custom Range** - Select specific dates

### Charts Available

#### 1. Leads Over Time (Line Chart)

**Shows:** Daily lead submissions
**Purpose:** Identify trends, spikes, and patterns
**Insights:**
- Best performing days
- Campaign effectiveness timing
- Seasonal trends

#### 2. Lead Status Distribution (Pie Chart)

**Shows:** Breakdown by status (new/contacted/converted/rejected)
**Purpose:** Understand conversion funnel
**Insights:**
- Conversion rate percentage
- Leads requiring attention
- Overall pipeline health

#### 3. Leads by Source (Bar Chart)

**Shows:** Hero form vs CTA form performance
**Purpose:** Compare form effectiveness
**Insights:**
- Which form converts better
- Where to focus optimization
- A/B testing results

#### 4. Conversion Funnel

**Shows:** Journey from lead to conversion
**Purpose:** Identify drop-off points
**Insights:**
- Conversion bottlenecks
- Success rate at each stage
- Areas for improvement

### UTM Performance Table

**Tracks campaign effectiveness:**

Columns:
- **Source** - Traffic source (google, facebook, email)
- **Medium** - Marketing medium (cpc, social, email)
- **Campaign** - Campaign name
- **Leads** - Total leads from this campaign
- **Conversion Rate** - % of leads that converted

**Use Cases:**
- Compare advertising platforms
- Measure ROI by campaign
- Identify best performing channels
- Optimize marketing spend

### How to Use Analytics

**Weekly Review:**
1. Check "Leads Over Time" for trends
2. Review "Status Distribution" for conversion rate
3. Analyze "Source Performance" to optimize forms
4. Review "UTM Performance" to adjust marketing

**Monthly Planning:**
1. Use 30-day view for broader trends
2. Identify best performing campaigns
3. Allocate budget based on conversion rates
4. Plan next month's strategy

---

## 🎯 Thank You Page

### Purpose

The Thank You page appears after successful form submission to:
- Confirm submission success
- Set expectations for next steps
- Build trust and reduce anxiety
- Track conversions
- Provide additional value

### Creating the Thank You Page

1. **Go to:** WordPress Admin → Pages → Add New
2. **Title:** "Thank You" or "Submission Successful"
3. **Template:** Select "Poradnik.pro Landing V5 - Thank You"
4. **Publish** the page
5. **Get URL:** Copy the page URL

### Configuring Redirect

Update your landing page forms to redirect to the Thank You page:

```javascript
// In poradnik-landing-v5.js, after successful submission:
if (result.success) {
    // Redirect to thank you page
    window.location.href = 'https://yoursite.com/thank-you/';
}
```

### Thank You Page Sections

#### 1. Success Hero
- ✅ Animated success icon
- Confirmation message
- Email notification badge

#### 2. What Happens Next (4-Step Timeline)

**Step 1: Analysis (2-4 hours)**
- Team reviews the request
- Matches with best experts

**Step 2: Receive Offers (24 hours)**
- Up to 5 verified professionals
- Offers sent to email

**Step 3: Compare & Choose**
- Review offers, ratings, prices
- No obligations

**Step 4: Project Execution**
- Work with chosen expert
- Support throughout

#### 3. Quick Tips

- 📧 Check email (including spam folder)
- 📝 Prepare questions for experts
- ⭐ Read reviews and ratings
- 💰 Compare all offers

#### 4. FAQ Section

Answers common questions:
- When will I receive offers?
- Do I have to choose an offer?
- Can I change my requirements?
- What if I'm not satisfied?

#### 5. Social Proof

Statistics to build confidence:
- 50,000+ satisfied clients
- 5,000+ verified experts
- 4.8/5 average rating
- 98% success rate

#### 6. Contact CTAs

Multiple contact options:
- 📧 Email: kontakt@poradnik.pro
- 📞 Phone: +48 123 456 789
- 🏠 Return to homepage

### Conversion Tracking

The Thank You page automatically fires tracking events:

**Google Analytics 4:**
```javascript
gtag('event', 'page_view', {
    page_title: 'Thank You Page',
    page_location: window.location.href
});
```

**Facebook Pixel:**
```javascript
fbq('track', 'PageView');
```

### Customization

Edit Thank You page content via WordPress options:

```php
// Hero title
update_option('plv5_ty_title', 'Your Custom Title');

// Hero subtitle
update_option('plv5_ty_subtitle', 'Your custom message');
```

Or edit the template directly:
`/theme/pearblog-theme/page-poradnik-landing-v5-thankyou.php`

---

## 🎬 Lead Status Management

### Status Lifecycle

**New** → **Contacted** → **Converted**
                      ↘ **Rejected**

### Status Definitions

#### New
- **Description:** Fresh lead, not yet contacted
- **Action Required:** Review and reach out ASAP
- **SLA:** Contact within 24 hours
- **Color:** Blue badge

#### Contacted
- **Description:** You've reached out to the lead
- **Action Required:** Follow up, send proposal
- **Next Step:** Wait for response or close deal
- **Color:** Green badge

#### Converted
- **Description:** Lead became a customer
- **Action Required:** Celebrate! Track in CRM
- **Success Metric:** This is what we measure
- **Color:** Dark green badge

#### Rejected
- **Description:** Not qualified or spam
- **Action Required:** None, keep for records
- **Reasons:** Wrong fit, spam, duplicate
- **Color:** Red badge

### Changing Status

**Method 1: Inline Dropdown**
1. Find lead in table
2. Click status dropdown
3. Select new status
4. Auto-saves immediately

**Method 2: Lead Details Modal**
1. Click "View" button
2. Review full details
3. Update status in modal
4. Click "Save"

### Best Practices

**Daily Routine:**
1. Check "New" leads (blue badge)
2. Contact within 24 hours
3. Update to "Contacted"
4. Follow up in 2-3 days
5. Update to "Converted" or "Rejected"

**Weekly Review:**
1. Review all "Contacted" leads
2. Follow up on stale leads (>7 days)
3. Move to "Converted" or "Rejected"
4. Clean up old "Rejected" leads

**Monthly Analysis:**
1. Calculate conversion rate: Converted / Total
2. Average time from New → Converted
3. Identify bottlenecks
4. Improve follow-up process

---

## ⚙️ Settings Panel

### Accessing Settings

Navigate to: **WordPress Admin → Landing Leads → Settings**

### Available Settings

#### Hero Title
- **Default:** "Znajdź idealnego wykonawcę w 60 sekund"
- **Purpose:** Main landing page headline
- **Best Practice:** Keep under 10 words, focus on benefit

#### Hero Subtitle
- **Default:** "Porównaj oferty, sprawdź opinie..."
- **Purpose:** Supporting text, expand on headline
- **Best Practice:** 1-2 sentences, address pain point

#### Admin Notification Email
- **Default:** WordPress admin email
- **Purpose:** Where to send new lead alerts
- **Usage:** Can be different from site admin

#### User Confirmation Emails
- **Default:** Enabled
- **Purpose:** Send thank you email to users
- **When to Disable:** If using external email system

### Saving Settings

1. Update fields
2. Click "Save Settings"
3. Success message confirms save
4. Changes apply immediately

---

## 📧 Email Notifications

### Admin Notification Email

**Triggered:** When new lead is submitted

**Subject:** `[Poradnik.pro] Nowe zgłoszenie z Landing V5`

**Content:**
```
Nowe zgłoszenie z Landing Page:

Usługa: [service requested]
Email: [user email]
Źródło: hero/cta
Data: [timestamp]
IP: [ip address]

UTM Parameters:
  source: [utm_source]
  medium: [utm_medium]
  campaign: [utm_campaign]
```

**Purpose:** Immediate notification for quick follow-up

### User Confirmation Email

**Triggered:** When new lead is submitted (if enabled)

**Subject:** `Dziękujemy za zgłoszenie - Poradnik.pro`

**Content:**
```
Witaj!

Dziękujemy za zgłoszenie przez Poradnik.pro.

[If service specified]
Twoje zapytanie dotyczy: [service]

Co dalej?
1. Przeanalizujemy Twoje zapytanie
2. Dopasujemy najlepszych ekspertów
3. Otrzymasz do 5 bezpłatnych ofert
4. Porównasz i wybierzesz najlepszą

Zazwyczaj pierwsze oferty otrzymujesz w ciągu 2-4 godzin.

Masz pytania?
Email: kontakt@poradnik.pro
Tel: +48 123 456 789

Pozdrawiamy,
Zespół Poradnik.pro
```

**Purpose:**
- Confirm successful submission
- Set expectations
- Provide contact info
- Reduce anxiety

### Customizing Emails

Edit email templates in:
`/theme/pearblog-theme/inc/poradnik-landing-v5-handler.php`

**Admin email:** Line 280-300
**User email:** Line 310-340

---

## 🎯 UTM Campaign Tracking

### What are UTM Parameters?

UTM (Urchin Tracking Module) parameters help track campaign effectiveness.

**Standard Parameters:**
- `utm_source` - Traffic source (google, facebook, newsletter)
- `utm_medium` - Marketing medium (cpc, social, email)
- `utm_campaign` - Campaign name (spring_sale_2024)
- `utm_term` - Keyword (for PPC)
- `utm_content` - Content variant (for A/B testing)

### Creating UTM Links

**Manual Construction:**
```
https://yoursite.com/landing/?utm_source=facebook&utm_medium=cpc&utm_campaign=spring2024&utm_content=variant_a
```

**Using Google's Campaign URL Builder:**
1. Go to: https://ga-dev-tools.web.app/campaign-url-builder/
2. Enter your landing page URL
3. Fill in UTM parameters
4. Copy generated URL
5. Use in your marketing

### Example Campaigns

**Google Ads:**
```
utm_source=google
utm_medium=cpc
utm_campaign=brand_keywords
utm_term=best_service_provider
```

**Facebook Ads:**
```
utm_source=facebook
utm_medium=cpc
utm_campaign=may_promo
utm_content=image_a
```

**Email Newsletter:**
```
utm_source=newsletter
utm_medium=email
utm_campaign=weekly_digest
```

**LinkedIn Sponsored:**
```
utm_source=linkedin
utm_medium=social
utm_campaign=b2b_campaign
```

### Tracking in Dashboard

UTM data is automatically:
1. Captured from URL when user lands
2. Stored in sessionStorage
3. Included in form submission
4. Saved to database with lead
5. Shown in Analytics → UTM Performance table

### Best Practices

**Naming Conventions:**
- Use lowercase
- Use underscores for spaces
- Be consistent across campaigns
- Document your naming system

**Campaign Structure:**
```
{channel}_{objective}_{month}_{year}
Example: facebook_leads_may_2024
```

**Testing:**
1. Create UTM link
2. Open in incognito window
3. Submit form
4. Check admin dashboard
5. Verify UTM data captured

---

## 📊 Database Schema

### Table: `wp_poradnik_leads`

```sql
CREATE TABLE wp_poradnik_leads (
    id bigint(20) unsigned AUTO_INCREMENT PRIMARY KEY,
    service varchar(255) DEFAULT NULL,
    email varchar(255) DEFAULT NULL,
    source varchar(50) DEFAULT NULL,
    ip_address varchar(45) DEFAULT NULL,
    user_agent text DEFAULT NULL,
    utm_data text DEFAULT NULL,
    status varchar(20) DEFAULT 'new',
    created_at datetime NOT NULL,
    KEY status (status),
    KEY created_at (created_at)
);
```

**Indexes:**
- `status` - Fast filtering by status
- `created_at` - Efficient date range queries

### Querying Leads

**Get all new leads:**
```php
global $wpdb;
$table = $wpdb->prefix . 'poradnik_leads';
$leads = $wpdb->get_results("
    SELECT * FROM $table
    WHERE status = 'new'
    ORDER BY created_at DESC
");
```

**Get leads from specific campaign:**
```php
$leads = $wpdb->get_results("
    SELECT * FROM $table
    WHERE utm_data LIKE '%spring2024%'
    ORDER BY created_at DESC
");
```

**Calculate conversion rate:**
```php
$stats = $wpdb->get_row("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted,
        (SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) / COUNT(*) * 100) as conversion_rate
    FROM $table
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");

echo "Conversion Rate: " . $stats->conversion_rate . "%";
```

---

## 🚀 Quick Start Guide

### Day 1: Setup

1. **Access Admin Dashboard**
   - Go to Landing Leads menu
   - Familiarize yourself with interface

2. **Create Thank You Page**
   - New page with Thank You template
   - Publish and copy URL

3. **Configure Settings**
   - Set your email for notifications
   - Customize hero text if needed

### Day 2-7: Testing

4. **Test Form Submission**
   - Submit test lead via landing page
   - Check email notification received
   - Verify lead appears in dashboard

5. **Test Status Management**
   - Change lead status
   - Verify updates work

6. **Test CSV Export**
   - Export leads
   - Open in Excel
   - Verify all data present

### Week 2: Launch

7. **Create UTM Campaigns**
   - Build UTM links for each channel
   - Document in spreadsheet

8. **Launch Marketing**
   - Share UTM links on social media
   - Run paid ads
   - Send email campaigns

9. **Monitor Daily**
   - Check new leads
   - Contact within 24 hours
   - Update statuses

### Month 1: Optimize

10. **Review Analytics**
    - Identify top performing campaigns
    - Calculate ROI by channel
    - Adjust marketing spend

11. **Improve Conversion**
    - A/B test headlines
    - Optimize form placement
    - Refine targeting

12. **Scale Up**
    - Increase budget on winners
    - Launch new campaigns
    - Expand to new channels

---

## 📈 Success Metrics

### Key Performance Indicators (KPIs)

**Lead Generation:**
- Total leads per month
- Leads per day (trend)
- Lead source distribution

**Conversion:**
- Conversion rate (converted/total)
- Time to conversion (average days)
- Conversion rate by source

**Campaign Performance:**
- Cost per lead (CPL) by campaign
- Return on ad spend (ROAS)
- Lead quality by source

**Operational:**
- Average response time
- Leads in "new" status
- Follow-up completion rate

### Benchmarks

**Good Performance:**
- Conversion rate: 5-15%
- Response time: <24 hours
- Follow-up rate: >90%

**Excellent Performance:**
- Conversion rate: >15%
- Response time: <4 hours
- Follow-up rate: 100%

### Calculating Metrics

**Conversion Rate:**
```
(Converted Leads / Total Leads) × 100
Example: (150 / 1000) × 100 = 15%
```

**Cost Per Lead:**
```
Total Ad Spend / Total Leads
Example: $1000 / 200 = $5 per lead
```

**Return on Ad Spend:**
```
(Revenue from Conversions / Ad Spend) × 100
Example: ($10,000 / $1,000) × 100 = 1000%
```

---

## 🔧 Troubleshooting

### Leads Not Appearing in Dashboard

**Check:**
1. Form submission completed successfully
2. Database table exists (`wp_poradnik_leads`)
3. No JavaScript errors in console
4. AJAX URL is correct

**Solution:**
```php
// Verify table exists
global $wpdb;
$table = $wpdb->prefix . 'poradnik_leads';
$exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
if (!$exists) {
    // Table doesn't exist, recreate it
    PoradnikLandingV5Handler::create_leads_table();
}
```

### Email Notifications Not Sending

**Check:**
1. Email settings configured correctly
2. SMTP plugin installed (recommended)
3. "From" address not flagged as spam
4. Check spam folder

**Solution:**
Install WP Mail SMTP plugin or configure mail settings.

### CSV Export Empty

**Check:**
1. Leads exist in database
2. User has admin permissions
3. No PHP errors

**Solution:**
Test direct database query to verify leads exist.

### Charts Not Loading

**Check:**
1. Chart.js library loading
2. No JavaScript console errors
3. Data returned from AJAX call

**Solution:**
Check browser console for errors, verify Chart.js CDN accessible.

---

## 🎓 Best Practices

### Lead Management

1. **Respond Quickly** - Contact within 24 hours
2. **Be Personal** - Reference their specific request
3. **Set Expectations** - Tell them next steps
4. **Follow Up** - 2-3 follow-ups if no response
5. **Track Everything** - Update status religiously

### Campaign Tracking

1. **Always Use UTMs** - Never launch without tracking
2. **Be Consistent** - Use standardized naming
3. **Document Campaigns** - Keep spreadsheet of all UTMs
4. **Review Weekly** - Check what's working
5. **Optimize Budget** - Shift spend to winners

### Data Quality

1. **Clean Regularly** - Remove spam/test leads
2. **Validate Emails** - Check for obvious fakes
3. **Deduplicate** - Remove duplicate submissions
4. **Audit Monthly** - Review data integrity
5. **Backup** - Export CSV monthly for safety

---

## 📚 Resources

### Documentation

- **Main Documentation:** `/PORADNIK-LANDING-V5-DOCUMENTATION.md`
- **Implementation Summary:** `/PORADNIK-LANDING-V5-SUMMARY.md`
- **Visual Guide:** `/PORADNIK-LANDING-V5-VISUAL-GUIDE.md`
- **This Guide:** `/PORADNIK-LANDING-V5-PLUS-GUIDE.md`

### External Tools

**Analytics:**
- Google Analytics 4: https://analytics.google.com
- Facebook Pixel: https://facebook.com/business/help/952192354843755

**UTM Builders:**
- Google Campaign URL Builder: https://ga-dev-tools.web.app/campaign-url-builder/
- UTM.io: https://utm.io

**Email:**
- WP Mail SMTP: https://wordpress.org/plugins/wp-mail-smtp/
- SendGrid: https://sendgrid.com
- Mailgun: https://mailgun.com

**CRM Integration:**
- HubSpot: https://hubspot.com
- Salesforce: https://salesforce.com
- Pipedrive: https://pipedrive.com

---

## 🎉 Conclusion

Landing V5 PLUS transforms your landing page from a simple form into a complete lead generation and management system.

**You now have:**
- ✅ Professional admin dashboard
- ✅ Visual analytics and reporting
- ✅ Complete lead lifecycle tracking
- ✅ UTM campaign attribution
- ✅ CSV export for integration
- ✅ Thank you page for conversions
- ✅ Email notification system

**Next Steps:**
1. Launch your first campaign
2. Monitor leads daily
3. Optimize based on data
4. Scale what works
5. Iterate continuously

**Need Help?**
- Email: support@poradnik.pro
- Documentation: All guides in `/PORADNIK-LANDING-V5-*.md`
- GitHub: https://github.com/your-repo/issues

---

**Built with ❤️ for Poradnik.pro**
**Version 5.1.0 - Landing V5 PLUS**
**May 2026**

🚀 **Ready to generate and convert leads at scale!**

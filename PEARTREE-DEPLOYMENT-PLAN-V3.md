# PEARTREE DEPLOYMENT PLAN V3 – ENTERPRISE 30 DNI

**Version:** 3.0.0
**Status:** Production Ready
**Timeline:** 30 days to repeatable revenue system

---

## 🎯 CORE OBJECTIVES (V3)

✅ **Day 7**: GO LIVE (working system)
✅ **Day 14**: First leads flowing
✅ **Day 21**: First revenue ($$$)
✅ **Day 30**: Repeatable system (not project)

---

## 🏗️ SYSTEM ARCHITECTURE (WHAT MUST WORK)

### 1. FrontPage (Lead Capture Engine)
- **Purpose**: Convert visitors → leads
- **Tech**: Dark UI system (already built)
- **Features**: Search, quick actions, live activity, CTA

### 2. Ranking Pages (SEO + Selection)
- **Purpose**: Traffic → qualified visitors
- **Tech**: PT24 landing pages + SEO Keyword Database V3
- **URL Pattern**: `/katowice/mechanik`, `/warszawa/elektryk`

### 3. PearBlog (Traffic Engine)
- **Purpose**: Organic search traffic
- **Tech**: PearBlog Engine with Poradnik Clean Content
- **Output**: 20-50 trust-building articles

### 4. Lead Engine (Routing System)
- **Purpose**: Capture → assign → notify
- **Tech**: PT24 lead forms + email notifications
- **DB**: `wp_pt24_leads` table

### 5. CRM (Business Management)
- **Purpose**: Manage companies, subscriptions, leads
- **Tech**: PT24 business CPT + admin dashboard
- **Features**: FREE, PREMIUM, PREMIUM+ tiers

---

## 📅 WEEK 1: GO LIVE (HARD LAUNCH)

### DAY 1-2: Infrastructure Setup

**Domain & Hosting**
```bash
# 1. Domain configuration
# - Primary: peartree.pl (or your domain)
# - DNS: Point to hosting
# - SSL: Enable HTTPS

# 2. WordPress installation
# - PHP 8.0+
# - MySQL 8.0+
# - 2GB+ RAM recommended

# 3. Deploy PearBlog Engine
cd /path/to/wordpress
cp -r pearblog-engine mu-plugins/
wp plugin activate pearblog-engine
```

**Database Setup**
```bash
# Create tables
wp eval "do_action('pearblog_create_tables');"

# Verify
wp db query "SHOW TABLES LIKE 'wp_pt24_%';"
```

**Front Page Implementation**
```bash
# Deploy dark UI homepage
cp theme/pearblog-theme/page-poradnik-dark-ui.php theme/pearblog-theme/page-home.php
cp theme/pearblog-theme/assets/css/poradnik-dark-ui.css theme/pearblog-theme/assets/css/
cp theme/pearblog-theme/assets/js/v3-calculator.js theme/pearblog-theme/assets/js/
cp theme/pearblog-theme/assets/js/v3-conversion-tracker.js theme/pearblog-theme/assets/js/

# Set as homepage
wp option update page_on_front <page_id>
wp option update show_on_front 'page'
```

**Lead Form MUST Work**
```bash
# Test lead capture
curl -X POST https://peartree.pl/wp-json/pt24/v1/leads \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "phone": "123456789",
    "service": "mechanik",
    "city": "katowice",
    "message": "Test lead"
  }'

# Response should be: {"success": true, "lead_id": 123}
```

**Output Day 1-2:**
- ✅ Domain live
- ✅ WordPress running
- ✅ Lead form functional
- ✅ Email notifications working

---

### DAY 3-4: First City + 3 Verticals

**Initialize Platform Data**
```bash
# Initialize cities and services
wp pt24 init

# Verify
wp pt24 stats
```

**Generate Landing Pages (Katowice Focus)**
```bash
# Method 1: Use V3 Enterprise (RECOMMENDED)
wp pearblog seo-v3:generate \
  --city=katowice \
  --vertical=mechanik \
  --type=high_intent \
  --batch=20

wp pearblog seo-v3:generate \
  --city=katowice \
  --vertical=elektryk \
  --type=high_intent \
  --batch=15

wp pearblog seo-v3:generate \
  --city=katowice \
  --vertical=hydraulik \
  --type=high_intent \
  --batch=15

# Total: 50 landing pages for Katowice

# Method 2: Use V1 (if mechanik only)
wp pearblog seo:generate \
  --city=katowice \
  --batch=50
```

**Verify URLs Work**
```bash
# Check generated pages
wp pt24 list | grep katowice

# Test URLs
curl -I https://peartree.pl/katowice/mechanik
curl -I https://peartree.pl/katowice/elektryk
curl -I https://peartree.pl/katowice/hydraulik

# Should return 200 OK
```

**Flush Rewrite Rules**
```bash
wp rewrite flush
wp cache flush
```

**Output Day 3-4:**
- ✅ Katowice landing pages live
- ✅ 3 verticals active (mechanik, elektryk, hydraulik)
- ✅ 50 SEO-optimized pages
- ✅ URLs working correctly

---

### DAY 5-7: Seed Companies + Ranking Pages

**Import Seed Companies (50-100 firms)**

Create CSV file: `katowice-firms.csv`
```csv
name,city,service,phone,email,address,website
Auto Serwis Pro,katowice,mechanik,32-123-4567,kontakt@autoserwis.pl,ul. Warszawska 45,autoserwis.pl
Elektryk 24h,katowice,elektryk,32-234-5678,info@elektryk24.pl,ul. Mickiewicza 12,elektryk24.pl
Hydraulik Express,katowice,hydraulik,32-345-6789,biuro@hydraulik.pl,ul. Kościuszki 8,hydraulik.pl
...
```

**Import via WP-CLI**
```bash
# Import companies
wp pt24 import-businesses katowice-firms.csv

# Verify
wp pt24 stats
# Should show: Businesses (Total): 50-100
```

**Create Ranking Pages**

Template: `theme/pearblog-theme/ranking-pt24_landing.php`
```php
<?php
/**
 * Template: Ranking Page
 *
 * URL: /katowice/mechanik
 * Purpose: Show top businesses for service in city
 */

get_header();

$city = get_query_var('pt24_city');
$service = get_query_var('pt24_service');

// Get businesses
$businesses = get_posts([
    'post_type' => 'pt24_business',
    'posts_per_page' => 20,
    'meta_query' => [
        ['key' => 'pt24_city', 'value' => $city],
        ['key' => 'pt24_service', 'value' => $service],
    ],
    'orderby' => 'meta_value_num',
    'meta_key' => 'pt24_rating',
    'order' => 'DESC',
]);
?>

<div class="ranking-page">
    <h1>Najlepsi <?php echo $service; ?> w <?php echo $city; ?></h1>

    <!-- Lead Form -->
    <div class="lead-form-hero">
        <h2>Otrzymaj bezpłatne oferty</h2>
        <?php include 'template-parts/pt24-lead-form.php'; ?>
    </div>

    <!-- Business Listing -->
    <?php foreach ($businesses as $business): ?>
        <div class="business-card">
            <h3><?php echo get_the_title($business); ?></h3>
            <div class="rating">⭐ <?php echo get_post_meta($business->ID, 'pt24_rating', true); ?></div>
            <div class="contact">
                📞 <?php echo get_post_meta($business->ID, 'pt24_phone', true); ?>
            </div>
            <button class="cta-contact">Wyślij zapytanie</button>
        </div>
    <?php endforeach; ?>
</div>

<?php get_footer(); ?>
```

**Test Ranking Pages**
```bash
# Visit URLs
https://peartree.pl/katowice/mechanik
https://peartree.pl/katowice/elektryk
https://peartree.pl/katowice/hydraulik

# Should show:
# - Hero with lead form
# - List of 5-20 businesses
# - Contact buttons
```

**Output Day 5-7:**
- ✅ 50-100 companies imported
- ✅ Ranking pages showing businesses
- ✅ Lead forms on all pages
- ✅ **SYSTEM IS LIVE** 🚀

---

## 📅 WEEK 2: LEAD ACTIVATION

### DAY 8-10: Test Leads + UX Optimization

**Send Test Leads Manually**
```bash
# Use admin form or API
curl -X POST https://peartree.pl/wp-json/pt24/v1/leads \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jan Kowalski",
    "email": "jan@example.com",
    "phone": "600123456",
    "service": "mechanik",
    "city": "katowice",
    "message": "Potrzebuję wymiany oleju w Audi A4"
  }'
```

**Track Lead Flow**
```bash
# Check lead was created
wp db query "SELECT * FROM wp_pt24_leads ORDER BY created_at DESC LIMIT 5;"

# Check email was sent
# Verify inbox of business received notification
```

**Optimize Form UX Based on Tests**
- Reduce fields if conversion is low
- Add trust badges ("100% darmowe", "Bez zobowiązań")
- Improve mobile UX (sticky CTA)
- Add progress indicator

**A/B Test Form Variants**
```javascript
// Track conversion rates
PearBlogConversionTracker.trackEvent('form_view', {
    service: 'mechanik',
    city: 'katowice'
});

PearBlogConversionTracker.trackEvent('form_submit', {
    service: 'mechanik',
    city: 'katowice'
});

// Goal: > 5% conversion rate (view → submit)
```

**Output Day 8-10:**
- ✅ Lead routing tested and working
- ✅ Email notifications verified
- ✅ Form UX optimized
- ✅ Conversion tracking active

---

### DAY 11-14: Content Engine (PearBlog)

**Generate 20-50 Trust-Building Articles**

```bash
# Generate poradnik articles
wp pearblog generate \
  --topic="Jak wybrać mechanika samochodowego" \
  --publish

wp pearblog generate \
  --topic="Ile kosztuje wymiana oleju - przewodnik cen" \
  --publish

wp pearblog generate \
  --topic="Naprawa hamulców - co musisz wiedzieć" \
  --publish

# Batch generation
for topic in \
  "Wymiana sprzęgła - koszt i czas naprawy" \
  "Diagnostyka komputerowa - kiedy jest potrzebna" \
  "Przegląd techniczny - wymagania 2026" \
  "Elektryk samochodowy - kiedy wezwać" \
  "Instalacja elektryczna w domu - poradnik"
do
  wp pearblog generate --topic="$topic" --publish
  sleep 5
done
```

**Create Service Pages (10 pages)**
```bash
# Use V3 Enterprise with problem keywords
wp pearblog seo-v3:generate \
  --city=katowice \
  --type=problem \
  --batch=10

# Example pages generated:
# /katowice/mechanik/hamulce-piszcza
# /katowice/mechanik/silnik-stuka
# /katowice/elektryk/instalacja-koszt
# /katowice/hydraulik/odplyw-zapchany
```

**Internal Linking Strategy**
```
Article: "Jak wybrać mechanika"
↓
Links to: /katowice/mechanik (ranking)
↓
CTA: "Zobacz najlepszych mechaników w Katowicach"
```

**Output Day 11-14:**
- ✅ 20-50 articles published
- ✅ 10 service pages live
- ✅ Internal linking implemented
- ✅ **First organic traffic** 📈

---

## 📅 WEEK 3: SALES + TRACTION

### DAY 15-17: Business Outreach

**Contact Strategy**

**Email Template:**
```
Temat: Wysłaliśmy Ci klienta z Katowic

Dzień dobry,

Właśnie wysłaliśmy do Państwa zapytanie od klienta z Katowic szukającego mechanika samochodowego.

Klient szuka: wymiana oleju w Audi A4

Chcielibyśmy współpracować i wysyłać więcej takich leadów.

Oferujemy 3 pakiety:

FREE (obecny):
→ widoczność w rankingu
→ leady gdy są dostępne

PREMIUM (299 zł/mies):
→ priorytetowe leady
→ wyższe miejsce w rankingu
→ więcej zapytań

PREMIUM+ (599 zł/mies):
→ exclusive leady (tylko Ty)
→ TOP 3 pozycja
→ unlimited zapytania

Jesteś zainteresowany?

Pozdrawiam,
[Twoje imię]
PearTree.pl
```

**Call Script:**
```
"Dzień dobry, dzwonię z PearTree.pl

Wysłaliśmy Państwu wczoraj zapytanie od klienta z Katowic.

Czy otrzymaliście?

[TAK] → "Świetnie! Chcemy wysyłać więcej. Mamy pakiet PREMIUM..."

[NIE] → "Sprawdzę co się stało. A propos, mamy system który..."
```

**Tracking Outreach**
```bash
# Log contacts in spreadsheet
Date | Company | Contact | Status | Next Step
2026-05-15 | Auto Serwis Pro | Rozmowa | Zainteresowany | Follow-up 2026-05-18
2026-05-15 | Elektryk 24h | Email | Brak odpowiedzi | Call 2026-05-17
```

**Output Day 15-17:**
- ✅ 50-100 companies contacted
- ✅ 10-30% response rate
- ✅ Interest validated
- ✅ Sales pipeline started

---

### DAY 18-21: First Sales

**Sales Funnel**
```
100 companies contacted
↓
20-30 responded (interested)
↓
10-15 qualified (saw lead value)
↓
5-10 converted to PREMIUM
↓
1-3 upgraded to PREMIUM+
```

**Premium Activation**
```bash
# Upgrade company to PREMIUM
wp post meta update <business_id> pt24_plan 'premium'
wp post meta update <business_id> pt24_plan_expires '2026-06-21'

# Move to TOP 3 in ranking
wp post meta update <business_id> pt24_featured 1
wp post meta update <business_id> pt24_position 1

# Enable unlimited leads
wp post meta update <business_id> pt24_lead_limit 999
```

**Payment Integration**
```bash
# Stripe or Przelewy24
# Invoice generation
# Subscription management

# Track MRR (Monthly Recurring Revenue)
# 5 companies × 299 zł = 1,495 zł MRR
# 2 companies × 599 zł = 1,198 zł MRR
# Total: 2,693 zł MRR
```

**Output Day 18-21:**
- ✅ 1-10 paying customers
- ✅ 1,000-5,000 zł MRR
- ✅ **First revenue** 💰
- ✅ Proven business model

---

## 📅 WEEK 4: SYSTEMATIZATION

### DAY 22-25: Automation Layer

**Auto Lead Assignment**
```php
// mu-plugins/pearblog-engine/src/Leads/LeadRouter.php

class LeadRouter {
    public function assign_lead($lead_id) {
        $lead = $this->get_lead($lead_id);

        // Get eligible businesses
        $businesses = $this->get_businesses([
            'city' => $lead['city'],
            'service' => $lead['service'],
            'status' => 'active',
        ]);

        // Priority assignment
        // 1. PREMIUM+ (exclusive)
        // 2. PREMIUM (priority)
        // 3. FREE (if capacity available)

        $assigned = $this->prioritize_and_assign($businesses, $lead);

        // Send notifications
        $this->notify_business($assigned, $lead);
        $this->notify_customer($lead, $assigned);

        return $assigned;
    }
}
```

**Auto Notifications**
```bash
# Email template for businesses
wp eval "
add_action('pt24_lead_created', function(\$lead_id) {
    \$lead = get_lead(\$lead_id);
    \$business = get_assigned_business(\$lead);

    wp_mail(
        \$business['email'],
        'Nowy lead z PearTree.pl',
        'Nowe zapytanie: ' . \$lead['message']
    );
});
"
```

**Auto Follow-up**
```bash
# Cron job for follow-ups
wp cron event schedule \
  pt24_follow_up_leads \
  "+1 day" \
  hourly

# Follow-up logic:
# - Day 1: Lead sent to business
# - Day 2: Check if contacted
# - Day 3: Send reminder
# - Day 7: Ask for feedback
```

**Auto Scoring**
```php
// Score leads based on quality
class LeadScorer {
    public function score($lead) {
        $score = 0;

        // Phone provided: +20
        if ($lead['phone']) $score += 20;

        // Detailed message: +30
        if (strlen($lead['message']) > 50) $score += 30;

        // High-intent service: +25
        if (in_array($lead['service'], ['mechanik', 'elektryk'])) {
            $score += 25;
        }

        // Premium city: +25
        if (in_array($lead['city'], ['warszawa', 'krakow'])) {
            $score += 25;
        }

        return $score; // 0-100
    }
}
```

**Output Day 22-25:**
- ✅ Auto lead assignment
- ✅ Auto notifications
- ✅ Auto follow-ups
- ✅ Lead quality scoring

---

### DAY 26-30: Scaling Operations

**Expand to 3-5 Cities**
```bash
# Add Kraków
wp pearblog seo-v3:generate \
  --city=krakow \
  --vertical=mechanik \
  --vertical=elektryk \
  --vertical=hydraulik \
  --batch=150

# Add Warszawa
wp pearblog seo-v3:generate \
  --city=warszawa \
  --batch=150

# Add Wrocław
wp pearblog seo-v3:generate \
  --city=wroclaw \
  --batch=150

# Add Poznań
wp pearblog seo-v3:generate \
  --city=poznan \
  --batch=150

# Total new pages: 600
```

**Generate 500+ Total Pages**
```bash
# Current: ~100 pages (Week 1-3)
# New: 600 pages (Week 4)
# Total: 700+ pages

# Verify
wp pt24 stats
# Should show: Landing Pages: 700+
```

**Content Scaling**
```bash
# More articles
wp pearblog generate --batch=30

# More service pages
wp pearblog seo-v3:generate --type=problem --batch=50
wp pearblog seo-v3:generate --type=long_tail --batch=50
```

**Business Scaling**
```bash
# Import more companies
# Target: 200-300 total businesses

for city in krakow warszawa wroclaw poznan
do
    wp pt24 import-businesses ${city}-firms.csv
done

# Verify
wp pt24 stats
# Should show: Businesses: 200-300
```

**Output Day 26-30:**
- ✅ 3-5 cities live
- ✅ 500-1000 pages total
- ✅ 200-300 businesses
- ✅ **System, not project** ✨

---

## 🔄 GROWTH LOOP 2.0

```
More businesses join
↓
Better rankings (more options)
↓
More SEO traffic
↓
More leads generated
↓
More conversions
↓
More revenue
↓
More businesses want to join
↓
LOOP REPEATS
```

---

## 📊 KPI TRACKING (30 DAYS)

### Traffic Metrics
```bash
# Google Analytics goals
Goal 1: Page views > 1,000/day
Goal 2: Unique visitors > 300/day
Goal 3: Avg session > 2 min
```

### Lead Metrics
```bash
# Track in database
wp db query "
SELECT
    COUNT(*) as total_leads,
    COUNT(CASE WHEN status='new' THEN 1 END) as new_leads,
    COUNT(CASE WHEN status='assigned' THEN 1 END) as assigned_leads,
    COUNT(CASE WHEN status='converted' THEN 1 END) as converted_leads
FROM wp_pt24_leads
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);
"

# Target:
# - 30-100 leads total
# - 20-60% assignment rate
# - 10-30% conversion rate
```

### Business Metrics
```bash
wp db query "
SELECT
    COUNT(*) as total_businesses,
    COUNT(CASE WHEN meta_value='premium' THEN 1 END) as premium,
    COUNT(CASE WHEN meta_value='premium_plus' THEN 1 END) as premium_plus
FROM wp_posts p
LEFT JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = 'pt24_plan'
WHERE p.post_type = 'pt24_business' AND p.post_status = 'publish';
"

# Target:
# - 100-200 businesses total
# - 5-20 paying customers
# - 5-15% conversion rate (free → paid)
```

### Revenue Metrics
```
KPI Target (30 days):
- MRR: 2,000-10,000 zł
- Churn: < 10%
- LTV: > 3,600 zł (12 months)
- CAC: < 500 zł per business
```

---

## ❌ FAIL POINTS (REAL TALK)

### Fail Point 1: No Businesses Join
**Symptom:** Can't recruit 50-100 seed companies
**Solution:**
- Offer first month free
- Show competitor rankings (FOMO)
- Manual outreach to top players
- Import from online directories

### Fail Point 2: No Sales Conversion
**Symptom:** Businesses see leads but don't upgrade
**Solution:**
- Send higher quality leads (scoring)
- Show ROI clearly (lead value vs cost)
- Offer trial period (14 days premium free)
- Add social proof (testimonials)

### Fail Point 3: Only Building, Not Launching
**Symptom:** Still coding on Day 30
**Solution:**
- SHIP on Day 7 no matter what
- Perfect is enemy of done
- Manual processes are OK initially
- Automate after validation

---

## 🎯 SUCCESS CRITERIA (30 DAYS)

### Minimum Viable Success
- ✅ 300+ pages indexed
- ✅ 100+ businesses listed
- ✅ 30+ leads generated
- ✅ 5+ paying customers
- ✅ 1,500+ zł MRR

### Target Success
- ✅ 1,000+ pages indexed
- ✅ 200+ businesses listed
- ✅ 100+ leads generated
- ✅ 20+ paying customers
- ✅ 6,000+ zł MRR

### Stretch Success
- ✅ 2,000+ pages indexed
- ✅ 300+ businesses listed
- ✅ 200+ leads generated
- ✅ 50+ paying customers
- ✅ 15,000+ zł MRR

---

## 🚀 TECHNICAL STACK (WHAT YOU HAVE)

### ✅ Already Built
1. **SEO Keyword Database V3 Enterprise** (150,000+ keywords, 8 verticals)
2. **PT24 Landing System** (CPT, routing, templates)
3. **PearBlog Content Engine** (AI article generation)
4. **Dark UI System** (conversion-optimized frontend)
5. **Lead Management** (database, forms, routing)
6. **WP-CLI Commands** (bulk operations, automation)

### 🔧 Quick Setup Commands
```bash
# Full system deployment (5 minutes)
wp plugin activate pearblog-engine
wp pt24 init
wp pearblog seo-v3:generate --city=katowice --batch=50
wp rewrite flush

# That's it. System is live.
```

---

## 💡 THE REAL INSIGHT

You're not building a website.

You're building:

### 🏆 A Local Lead Monopoly

**How it works:**
1. SEO captures all local searches
2. Ranking pages filter the best businesses
3. Lead forms convert visitors
4. Automation routes leads to paying customers
5. Revenue funds more SEO → more leads → more revenue

**The moat:**
- 1,000+ pages = impossible to replicate quickly
- 200+ businesses = network effects
- Lead quality = businesses stay and pay
- Automation = scales without you

---

## 📝 DAILY CHECKLIST (DISCIPLINE)

### Week 1
- [ ] Day 1: Domain + hosting live
- [ ] Day 2: Lead form working
- [ ] Day 3: First city pages
- [ ] Day 4: 3 verticals live
- [ ] Day 5: 50 companies imported
- [ ] Day 6: Ranking pages live
- [ ] Day 7: **GO LIVE** 🚀

### Week 2
- [ ] Day 8: Test leads sent
- [ ] Day 9: Form UX optimized
- [ ] Day 10: Tracking verified
- [ ] Day 11: 10 articles published
- [ ] Day 12: 10 more articles
- [ ] Day 13: 10 service pages
- [ ] Day 14: **First traffic** 📈

### Week 3
- [ ] Day 15: 30 companies contacted
- [ ] Day 16: 30 more contacts
- [ ] Day 17: Follow-ups sent
- [ ] Day 18: First sale closed
- [ ] Day 19: 2-3 more sales
- [ ] Day 20: 2-3 more sales
- [ ] Day 21: **First revenue** 💰

### Week 4
- [ ] Day 22: Automation built
- [ ] Day 23: Auto routing tested
- [ ] Day 24: Auto notifications live
- [ ] Day 25: Scoring system active
- [ ] Day 26: 2nd city launched
- [ ] Day 27: 3rd city launched
- [ ] Day 28: 4th city launched
- [ ] Day 29: 5th city launched
- [ ] Day 30: **System complete** ✨

---

## 🎓 FINAL WISDOM

### What Matters
1. **Ship fast** (Day 7, not Day 30)
2. **Talk to businesses** (50+ calls)
3. **Get paid** (validation)
4. **Automate** (scale)

### What Doesn't Matter
1. Perfect design
2. All features
3. Zero bugs
4. 100% automation (initially)

### The One Metric That Matters
**MRR (Monthly Recurring Revenue)**

If MRR is growing → you're winning
If MRR is flat → fix sales
If MRR is declining → fix product

---

## 🚀 LET'S GO

**You have everything you need:**
- ✅ Code (PearBlog Engine + V3 Enterprise)
- ✅ System (SEO + Leads + CRM)
- ✅ Plan (this document)

**What you need to do:**
- ⏰ Execute daily
- 📞 Talk to customers
- 💰 Get paid
- 📈 Scale

**Start now. Not tomorrow.**

**Day 1 begins when you run:**
```bash
wp plugin activate pearblog-engine
```

---

**END OF DEPLOYMENT PLAN V3**

*Built for entrepreneurs who want results, not projects.*

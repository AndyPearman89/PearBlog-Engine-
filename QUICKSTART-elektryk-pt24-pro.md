# ⚡ Quick Start: elektryk.pt24.pro

**Domain:** elektryk.pt24.pro
**Service:** Car Electrician Services (Elektryk Samochodowy)
**Purpose:** Connect customers with local car electricians
**Time to Deploy:** 15-30 minutes

---

## 🚀 One-Command Deploy (Subdirectory Method)

```bash
# SSH to your pt24.pro server, then:

# Generate elektryk landing pages for all top cities:
wp pt24 generate-pages --service=elektryk --batch=25 --allow-root

# Flush rewrite rules:
wp rewrite flush --allow-root

# Done! Pages accessible at:
# pt24.pro/warszawa/elektryk/
# pt24.pro/krakow/elektryk/
# etc.
```

---

## 📋 Quick Setup Options

### Option 1: Subdirectory (Fastest - 5 minutes)

**URL Structure:** `pt24.pro/{miasto}/elektryk/`

```bash
# Generate pages:
wp pt24 generate-pages --service=elektryk --allow-root

# Verify:
wp post list --post_type=pt24_landing --s="elektryk" --allow-root

# Test:
curl https://pt24.pro/warszawa/elektryk/
```

**Pros:** Fast, simple, shared SEO authority
**Cons:** Less brandable, shared domain

---

### Option 2: Subdomain (Best - 30 minutes)

**URL Structure:** `elektryk.pt24.pro/{miasto}/`

**Prerequisites:**
```bash
# 1. Add DNS A record:
elektryk.pt24.pro → Your server IP

# 2. Wait for DNS propagation (5-30 min):
dig elektryk.pt24.pro +short
```

**Setup:**

```bash
# 1. Enable multisite (if not already):
cd /var/www/pt24.pro
wp core multisite-convert --subdomains --allow-root

# 2. Create elektryk site:
wp site create \
  --slug=elektryk \
  --title="Elektryk PT24 - Elektryka Samochodowa" \
  --email="admin@elektryk.pt24.pro" \
  --allow-root

# 3. Get site ID:
SITE_ID=$(wp site list --field=blog_id --url=elektryk.pt24.pro --allow-root)

# 4. Activate theme:
wp theme activate pearblog-theme --url=elektryk.pt24.pro --allow-root

# 5. Configure settings:
wp option update pearblog_industry 'local_services' --url=elektryk.pt24.pro --allow-root
wp option update pearblog_language 'pl' --url=elektryk.pt24.pro --allow-root
wp option update pearblog_homepage_version 'v7' --url=elektryk.pt24.pro --allow-root

# 6. Initialize PT24:
wp pt24 init --url=elektryk.pt24.pro --allow-root

# 7. Generate pages:
wp pt24 generate-pages --service=elektryk --batch=25 --url=elektryk.pt24.pro --allow-root

# 8. Configure SSL:
certbot --expand -d pt24.pro -d www.pt24.pro -d elektryk.pt24.pro

# Done! Test:
curl https://elektryk.pt24.pro/warszawa/
```

**Pros:** Dedicated branding, better SEO isolation, professional
**Cons:** Requires DNS setup, SSL update, multisite config

---

## ✅ Verification Checklist

After deployment, verify these work:

```bash
# ✅ Homepage loads:
curl -I https://elektryk.pt24.pro
# Expected: HTTP/2 200

# ✅ Landing page loads:
curl -I https://elektryk.pt24.pro/warszawa/
# Expected: HTTP/2 200

# ✅ Ranking page loads:
curl -I https://elektryk.pt24.pro/ranking/warszawa/
# Expected: HTTP/2 200

# ✅ API works:
curl https://elektryk.pt24.pro/wp-json/pt24/v1/businesses
# Expected: JSON response

# ✅ Pages exist:
wp post list --post_type=pt24_landing --url=elektryk.pt24.pro --allow-root
# Expected: List of pages

# ✅ Stats:
wp pt24 stats --url=elektryk.pt24.pro --allow-root
# Expected: Platform statistics
```

---

## 🎯 Essential WP-CLI Commands

```bash
# Generate pages for specific city:
wp pt24 generate-pages --service=elektryk --city=warszawa --url=elektryk.pt24.pro --allow-root

# Generate pages for all cities:
wp pt24 generate-pages --service=elektryk --batch=50 --url=elektryk.pt24.pro --allow-root

# List all elektryk pages:
wp post list --post_type=pt24_landing --url=elektryk.pt24.pro --allow-root

# View platform stats:
wp pt24 stats --url=elektryk.pt24.pro --allow-root

# Flush rewrite rules (if URLs not working):
wp rewrite flush --url=elektryk.pt24.pro --allow-root

# View recent leads:
wp db query "SELECT * FROM wp_pt24_leads WHERE service='elektryk' ORDER BY created_at DESC LIMIT 5" --allow-root
```

---

## 🏙️ Cities Included by Default

When you run `wp pt24 generate-pages --service=elektryk`:

**Top 25 Cities:**
- Warszawa, Kraków, Łódź, Wrocław, Poznań
- Gdańsk, Szczecin, Bydgoszcz, Lublin, Katowice
- Białystok, Gdynia, Częstochowa, Radom, Sosnowiec
- Toruń, Kielce, Rzeszów, Gliwice, Zabrze
- Ruda Śląska, Bytom, Chorzów, Tychy, Dąbrowa Górnicza

**Each city gets 2 pages:**
1. Landing: `elektryk.pt24.pro/{miasto}/`
2. Ranking: `elektryk.pt24.pro/ranking/{miasto}/`

**Total:** 50 pages (25 cities × 2 page types)

---

## 🎨 Landing Page Structure

Each elektryk landing page includes:

### Above the Fold:
- **Hero:** "Elektryk samochodowy {MIASTO} — sprawdź ceny i dostępność"
- **Lead Form:** Name, phone, email, problem description
- **Trust Signals:** ✔ Darmowe ✔ Sprawdzone firmy ✔ 24h

### Below the Fold:
- **Services:** Diagnostyka, stacyjki, alternatory, alarmy
- **Cost Block:** "Ile kosztuje naprawa instalacji w {MIASTO}?"
- **Ranking Preview:** Top 3 local electricians
- **FAQ:** Common questions about car electrical services
- **Final CTA:** "Otrzymaj ofertę"

---

## 💰 Target Keywords & Traffic

**Primary Keywords:**
```
- "elektryk samochodowy warszawa" (1000+ searches/month)
- "naprawa stacyjki kraków" (500+ searches/month)
- "wymiana alternatora wrocław" (300+ searches/month)
- "diagnoza elektryczna poznań" (200+ searches/month)
- "elektryk mobilny gdańsk" (150+ searches/month)
```

**User Intent:**
- Emergency electrical repairs
- Battery replacement
- Starter motor issues
- Alternator problems
- Car alarm installation
- Electrical diagnostics

**Expected Conversions:**
- 2-5% form submission rate
- 1-3% direct phone call rate
- Average lead value: 50-150 zł

---

## 🔧 Add Your First Electrician Business

```bash
# Create business profile:
wp post create \
  --post_type=pt24_business \
  --post_title="AutoElektryk Express - Warszawa" \
  --post_content="Profesjonalna diagnostyka i naprawa instalacji elektrycznej. Mobilny serwis. Stacyjki, alternatory, akumulatory. Dojazd w 30 min." \
  --post_status=publish \
  --url=elektryk.pt24.pro \
  --allow-root

# Get business ID:
BIZ_ID=$(wp post list --post_type=pt24_business --format=ids --posts_per_page=1 --url=elektryk.pt24.pro --allow-root)

# Add contact details:
wp post meta update $BIZ_ID pt24_phone '+48 500 100 200' --url=elektryk.pt24.pro --allow-root
wp post meta update $BIZ_ID pt24_email 'kontakt@autoelektryk.pl' --url=elektryk.pt24.pro --allow-root
wp post meta update $BIZ_ID pt24_service_area 'Warszawa i okolice' --url=elektryk.pt24.pro --allow-root
wp post meta update $BIZ_ID pt24_years_experience '10' --url=elektryk.pt24.pro --allow-root
wp post meta update $BIZ_ID pt24_mobile_service '1' --url=elektryk.pt24.pro --allow-root
wp post meta update $BIZ_ID pt24_emergency_service '1' --url=elektryk.pt24.pro --allow-root

# Add service categories:
wp term create pt24_service_cat 'elektryk' --slug=elektryk --url=elektryk.pt24.pro --allow-root || true
SERVICE_ID=$(wp term list pt24_service_cat --slug=elektryk --field=term_id --url=elektryk.pt24.pro --allow-root)
wp post term add $BIZ_ID pt24_service_cat $SERVICE_ID --url=elektryk.pt24.pro --allow-root

# Add city:
wp term create pt24_city 'Warszawa' --slug=warszawa --url=elektryk.pt24.pro --allow-root || true
CITY_ID=$(wp term list pt24_city --slug=warszawa --field=term_id --url=elektryk.pt24.pro --allow-root)
wp post term add $BIZ_ID pt24_city $CITY_ID --url=elektryk.pt24.pro --allow-root

echo "Business created with ID: $BIZ_ID"
```

---

## 📊 Monitor Performance

### Check Leads

```bash
# Total leads for elektryk:
wp db query "SELECT COUNT(*) as total FROM wp_pt24_leads WHERE service='elektryk'" --allow-root

# Recent leads:
wp db query "SELECT id, created_at, city, name, phone FROM wp_pt24_leads WHERE service='elektryk' ORDER BY created_at DESC LIMIT 10" --allow-root

# Leads by city:
wp db query "SELECT city, COUNT(*) as count FROM wp_pt24_leads WHERE service='elektryk' GROUP BY city ORDER BY count DESC" --allow-root
```

### Check Page Performance

```bash
# Pages by status:
wp post list \
  --post_type=pt24_landing \
  --url=elektryk.pt24.pro \
  --format=count \
  --allow-root

# Pages with most views (if tracking enabled):
wp db query "SELECT meta_value as views, post_id FROM wp_postmeta WHERE meta_key='page_views' ORDER BY CAST(meta_value AS UNSIGNED) DESC LIMIT 10" --url=elektryk.pt24.pro --allow-root
```

---

## 🚨 Quick Troubleshooting

### Pages return 404?
```bash
wp rewrite flush --url=elektryk.pt24.pro --allow-root
wp pt24 init --url=elektryk.pt24.pro --allow-root
```

### Subdomain not loading?
```bash
# Check DNS:
dig elektryk.pt24.pro +short

# Check multisite config:
grep "MULTISITE" /var/www/pt24.pro/wp-config.php

# Check SSL:
certbot certificates | grep elektryk
```

### Form not submitting?
```bash
# Check database table:
wp db query "SHOW TABLES LIKE '%pt24_leads%'" --allow-root

# Test AJAX endpoint:
curl -X POST https://elektryk.pt24.pro/wp-admin/admin-ajax.php \
  -d "action=pt24_submit_lead&service=elektryk&city=test&name=Test&phone=123"
```

---

## 📞 Support Resources

**Full Documentation:**
- [DEPLOYMENT-elektryk-pt24-pro.md](DEPLOYMENT-elektryk-pt24-pro.md) - Complete deployment guide
- [DEPLOYMENT-pt24-pro.md](DEPLOYMENT-pt24-pro.md) - Base platform documentation
- [PT24-MULTIVERTICAL-V4-STATUS.md](PT24-MULTIVERTICAL-V4-STATUS.md) - Multivertical architecture

**Repository:**
- GitHub: https://github.com/AndyPearman89/PearBlog-Engine-/
- Issues: https://github.com/AndyPearman89/PearBlog-Engine-/issues

---

## 🎉 Next Steps

After deployment:

1. **Add Businesses** - Create 10-20 electrician profiles for each major city
2. **Submit to Google** - Add sitemap to Google Search Console
3. **Set Up Analytics** - Track leads and conversions
4. **Configure Emails** - Set up lead notification emails
5. **Promote** - Run Google Ads for "elektryk samochodowy {miasto}"
6. **Scale** - Add more cities as traffic grows

---

## 📈 Expected Growth Timeline

**Week 1:** Deploy + 25 cities = 50 pages live
**Week 2:** Add 10 businesses per city = 250 profiles
**Week 3:** Google indexing begins
**Week 4:** First organic traffic and leads
**Month 2:** 10-50 leads/week
**Month 3:** 50-200 leads/week
**Month 6:** 200+ leads/week, profitable

---

**Quick Start Version:** 1.0
**Last Updated:** May 4, 2026
**Status:** Production Ready ⚡

# PEARBLOG ENGINE V3 – SEO MACHINE (100K PAGES SYSTEM)

**Version:** 3.0.0
**Status:** Specification
**Purpose:** Programmatic page generation system for massive SEO scale

---

## 1. SYSTEM OVERVIEW

The SEO Machine is a programmatic content generation system that creates landing pages using a **city × service × problem matrix**. It scales from 100 to 100,000+ URLs by combining location data, service types, and user problems into keyword-optimized pages.

### Key Philosophy
- **Pattern-Based Generation**: Use templates + dynamic data instead of manually creating pages
- **Intent Matching**: HIGH INTENT (ready to buy), PROBLEM (searching for solution), LONG TAIL (specific queries)
- **No Dead Ends**: Every page links to 5+ related pages, comparison, calculator, expert
- **SEO-First**: Min 1200 words, schema markup, fast loading, mobile-first
- **Monetization-Ready**: Lead capture, expert matching, subscription visibility boost

---

## 2. URL PATTERNS

### Pattern Matrix
```
/{city}                              → City landing page
/{city}/{service}                    → Service in city page
/{city}/{problem}                    → Problem in city page
/{city}/{service}/{problem}          → Specific solution page
```

### Examples
```
HIGH INTENT (transactional):
/katowice/wymiana-oleju              → "Wymiana oleju Katowice - Porównaj ceny i oferty"
/warszawa/mechanik-samochodowy       → "Mechanik samochodowy Warszawa - Ranking najlepszych"
/krakow/remont-silnika               → "Remont silnika Kraków - Sprawdzone warsztaty"

PROBLEM (informational → commercial):
/katowice/hamulce-piszczace          → "Piszczące hamulce w Katowicach - Przyczyny i naprawa"
/warszawa/auto-dymi                  → "Auto dymi Warszawa - Co robić? Gdzie jechać?"
/gdansk/chlodnica-przecieka          → "Przeciekająca chłodnica Gdańsk - Diagnoza i koszt"

LONG TAIL (specific):
/katowice/wymiana-oleju/audi-a4      → "Wymiana oleju Audi A4 Katowice - Cennik 2024"
/warszawa/mechanik/volvo             → "Mechanik Volvo Warszawa - Specjaliści serwisu"
/krakow/naprawa-turbosprezarki/bmw   → "Naprawa turbospręzarki BMW Kraków - Ile kosztuje?"
```

---

## 3. KEYWORD TYPES

### Type A: HIGH INTENT (Transactional)
**Pattern:** `{city} + {service}`
**User Intent:** Ready to buy/book service
**CTA:** Strong → "Umów wizytę teraz" / "Porównaj oferty"

**Keywords:**
- mechanik samochodowy {city}
- wymiana oleju {city}
- naprawa silnika {city}
- serwis opon {city}
- przegląd techniczny {city}
- lakiernik {city}
- auto pomoc drogowa {city}

### Type B: PROBLEM (Informational → Commercial)
**Pattern:** `{city} + {problem}`
**User Intent:** Looking for solution, then provider
**CTA:** Soft → "Sprawdź przyczyny" → "Znajdź mechanika"

**Keywords:**
- hamulce piszczą {city}
- auto dymi {city}
- silnik stuka {city}
- klimatyzacja nie chłodzi {city}
- akumulator padł {city}
- lampka check engine {city}
- chlodnica przecieka {city}

### Type C: LONG TAIL (Specific)
**Pattern:** `{city} + {service} + {detail}`
**User Intent:** Very specific query, high conversion
**CTA:** Ultra-targeted → "Audi A4 mechanik - umów się"

**Keywords:**
- wymiana oleju audi a4 {city}
- mechanik volvo {city}
- naprawa bmw {city}
- lakiernik mercedes {city}
- serwis volkswagen {city}
- naprawa turbosprezarki bmw {city}

---

## 4. PAGE TEMPLATE STRUCTURE

### 4.1 Hero Section
```
┌────────────────────────────────────────────────────┐
│  [BREADCRUMB: Home > Katowice > Wymiana oleju]    │
│                                                    │
│  🔧 WYMIANA OLEJU KATOWICE                        │
│  Sprawdzone warsztaty • Od 150 zł • Rezerwuj 24/7│
│                                                    │
│  [🔍 Wpisz markę auta...] [Znajdź warsztat →]    │
│                                                    │
│  ⭐ 4.8/5 (847 opinii)  📍 127 warsztatów         │
└────────────────────────────────────────────────────┘
```

### 4.2 Content Sections (Min 1200 words total)

#### Section 1: Introduction (150-200 words)
```
## Wymiana oleju w Katowicach - Przewodnik 2024

Szukasz zaufanego warsztatu do wymiany oleju w Katowicach?
Porównaj ceny, sprawdź opinie i umów się online w 2 minuty.

W naszym rankingu znajdziesz {count} sprawdzonych warsztatów
oferujących wymianę oleju w Katowicach. Średni koszt to {avg_price} zł,
ale ceny mogą się różnić w zależności od marki auta i rodzaju oleju.
```

#### Section 2: Symptoms/Indicators (200-250 words)
```
## Kiedy wymienić olej? Objawy zużytego oleju

- 🚗 Przejechałeś ponad {km} km od ostatniej wymiany
- ⚠️ Zapala się kontrolka ciśnienia oleju
- 🖤 Olej jest ciemny i gęsty
- 📉 Silnik pracuje głośniej niż zwykle
- 💨 Zwiększone spalanie paliwa

[WIĘCEJ: Zobacz pełny poradnik "Jak sprawdzić stan oleju" →]
```

#### Section 3: Causes/Background (200-250 words)
```
## Dlaczego regularna wymiana oleju jest ważna?

Olej silnikowy pełni kluczową rolę w działaniu Twojego auta...

[Rozbudowana sekcja z punktami, obrazami, wideo]
```

#### Section 4: Cost Breakdown (250-300 words)
```
## Ile kosztuje wymiana oleju w Katowicach?

┌──────────────────┬─────────────┬─────────────┐
│ Typ oleju        │ Koszt oleju │ + robocizna │
├──────────────────┼─────────────┼─────────────┤
│ Syntetyczny      │ 150-300 zł  │ 50-80 zł    │
│ Półsyntetyczny   │ 100-200 zł  │ 50-80 zł    │
│ Mineralny        │ 80-150 zł   │ 40-60 zł    │
└──────────────────┴─────────────┴─────────────┘

**Średni całkowity koszt:** {avg_cost} zł

[🧮 Oblicz dokładny koszt dla Twojego auta →]
```

#### Section 5: FAQ (200-250 words)
```
## Najczęściej zadawane pytania

### Jak często wymieniać olej?
Co 10,000-15,000 km lub raz w roku...

### Jaki olej wybrać do mojego auta?
Sprawdź instrukcję obsługi lub zapytaj mechanika...

### Czy mogę sam wymienić olej?
Tak, ale musisz mieć odpowiednie narzędzia...

[TOTAL: 5-8 FAQ items with schema markup]
```

#### Section 6: CTA Section (Intent-based)
```
HIGH INTENT (transactional):
┌────────────────────────────────────────┐
│  🔧 Gotowy do wymiany oleju?          │
│                                        │
│  Porównaj oferty z 127 warsztatów    │
│  w Katowicach i umów się online       │
│                                        │
│  [📅 Umów wizytę teraz →]            │
└────────────────────────────────────────┘

PROBLEM (informational):
┌────────────────────────────────────────┐
│  🔍 Potrzebujesz pomocy mechanika?    │
│                                        │
│  Opisz problem, a polecimy Ci         │
│  najlepszy warsztat w Katowicach      │
│                                        │
│  [💬 Zapytaj eksperta →]             │
└────────────────────────────────────────┘
```

#### Section 7: Local Listings (200-300 words)
```
## Najlepsze warsztaty w Katowicach

[RANKING CARD #1]
┌─────────────────────────────────────────┐
│ 🏆 1. AutoSerwis Pro                   │
│ ⭐ 4.9 (234 opinii)                    │
│ 📍 ul. Warszawska 45, Katowice        │
│ 💰 Wymiana oleju od 180 zł            │
│ [Zobacz ofertę →] [Umów wizytę →]     │
└─────────────────────────────────────────┘

[RANKING CARD #2]
[RANKING CARD #3]
...

[🏆 Zobacz pełny ranking warsztatów →]
```

---

## 5. INTERNAL LINKING SYSTEM

### Minimum 5 Links Per Page

#### Link Type 1: Related Services (3-4 links)
```
**Zobacz też:**
- [Przegląd techniczny Katowice →](/katowice/przeglad-techniczny)
- [Wymiana opon Katowice →](/katowice/wymiana-opon)
- [Diagnostyka komputerowa Katowice →](/katowice/diagnostyka)
```

#### Link Type 2: Problem Pages (2-3 links)
```
**Częste problemy:**
- [Silnik stuka - przyczyny →](/katowice/silnik-stuka)
- [Lampka check engine świeci →](/katowice/check-engine)
```

#### Link Type 3: Content Hub (1 link)
```
**Poradnik:**
- [Kompletny przewodnik: Jak dbać o olej silnikowy →](/poradnik/olej-silnikowy)
```

#### Link Type 4: Comparison (1 link)
```
**Porównaj opcje:**
- [Olej syntetyczny vs półsyntetyczny - co wybrać? →](/porownanie/olej-syntetyczny-polsyntetyczny)
```

#### Link Type 5: Calculator (1 link)
```
**Narzędzia:**
- [🧮 Kalkulator kosztów wymiany oleju →](/kalkulator/wymiana-oleju)
```

---

## 6. INTENT-BASED CTA SYSTEM

### Rule 1: HIGH INTENT (Transactional)
**Keywords:** wymiana oleju, mechanik, naprawa, serwis
**CTA Strategy:** Strong, immediate action

```
Primary CTA: "📅 Umów wizytę teraz"
Secondary CTA: "🆚 Porównaj oferty warsztatów"
Tertiary CTA: "🧮 Oblicz koszt naprawy"
```

### Rule 2: PROBLEM (Informational → Commercial)
**Keywords:** piszczą hamulce, dymi auto, stuka silnik
**CTA Strategy:** Soft education → then provider

```
Primary CTA: "🔍 Sprawdź przyczyny problemu"
Secondary CTA: "💬 Zapytaj eksperta o opinię"
Tertiary CTA: "📍 Znajdź mechanika w pobliżu"
```

### Rule 3: LONG TAIL (Specific)
**Keywords:** wymiana oleju audi a4, mechanik volvo
**CTA Strategy:** Ultra-targeted matching

```
Primary CTA: "🔧 Warsztaty specjalizujące się w Audi"
Secondary CTA: "💰 Sprawdź ceny dla Twojego modelu"
Tertiary CTA: "⭐ Zobacz opinie o warsztatach"
```

---

## 7. SEO RULES

### Content Rules
- ✅ Min **1200 words** per page
- ✅ H1 includes: `{service} + {city}` or `{problem} + {city}`
- ✅ H2-H3 structure with semantic hierarchy
- ✅ First paragraph includes primary keyword 2x
- ✅ Keyword density: 1-2% (natural usage)
- ✅ LSI keywords scattered throughout
- ✅ Internal links: min 5 per page
- ✅ External links: 1-2 to authority sites

### Technical Rules
- ✅ **Schema.org markup**: Article, FAQPage, Breadcrumb, LocalBusiness
- ✅ **Page speed**: < 2s LCP (Largest Contentful Paint)
- ✅ **Mobile-first**: Touch-optimized, bottom nav, sticky CTA
- ✅ **Image optimization**: WebP format, lazy loading, alt text with keywords
- ✅ **URL structure**: Clean, readable, includes keywords
- ✅ **Meta title**: `{Primary Keyword} | {City} | {Brand}` (max 60 chars)
- ✅ **Meta description**: Includes keyword, CTA, USP (max 155 chars)

### Schema.org Example
```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [{
    "@type": "Question",
    "name": "Ile kosztuje wymiana oleju w Katowicach?",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "Średni koszt wymiany oleju w Katowicach to 200-350 zł..."
    }
  }]
}
```

---

## 8. SCALING STRATEGY

### Stage 1: Foundation (100 pages)
**Timeline:** Week 1-2
**Focus:** Top 10 cities × Top 10 services

**Cities (10):**
- Warszawa, Kraków, Wrocław, Poznań, Gdańsk
- Szczecin, Łódź, Katowice, Lublin, Bydgoszcz

**Services (10):**
- Wymiana oleju, Mechanik samochodowy, Przegląd techniczny
- Naprawa silnika, Wymiana opon, Lakiernik
- Diagnostyka komputerowa, Elektryk samochodowy
- Mechanika pojazdowa, Auto pomoc drogowa

**Output:** 10 cities × 10 services = **100 pages**

### Stage 2: Expansion (1,000 pages)
**Timeline:** Week 3-4
**Focus:** Add problem pages + long tail variants

**Problem Keywords (20):**
- Hamulce piszczą, Auto dymi, Silnik stuka, Klimatyzacja nie chłodzi
- Akumulator padł, Lampka check engine, Chłodnica przecieka
- Samochód nie odpala, Skrzynia biegli się zacina, Sprzęgło ślizga się
- [+10 more problem keywords]

**Long Tail (brand-specific):**
- wymiana oleju audi, mechanik bmw, serwis volkswagen
- naprawa mercedesa, volvo mechanik
- [Top 10 brands × Top 10 cities × Top 5 services]

**Output:** 100 base + 200 problem + 700 long tail = **1,000 pages**

### Stage 3: Mass Scale (10,000+ pages)
**Timeline:** Week 5-8
**Focus:** All cities + all services + all problems

**Cities (500+):**
- All cities with population > 20,000
- Import from GUS (Central Statistical Office) database

**Services (50+):**
- Expand to niche services: naprawa turbospręzarki, regeneracja DPF,
  wymiana rozrządu, chiptuning, przyciemnianie szyb, etc.

**Problems (100+):**
- Long-tail problem queries from Google Search Console data

**Calculation:**
- 500 cities × 50 services = **25,000 service pages**
- 500 cities × 100 problems = **50,000 problem pages**
- Long tail variants: **25,000+ pages**
- **Total: 100,000+ pages**

---

## 9. MONETIZATION MODEL

### Revenue Stream 1: Lead Sales
**Price per lead:** 50-150 zł
**Conversion rate:** 2-5% of visitors
**Monthly traffic:** 100,000 visitors → 2,000-5,000 leads
**Monthly revenue:** 100,000 - 750,000 zł

### Revenue Stream 2: Subscription (Expert Profiles)
**Price:** 299-999 zł/month per expert
**Benefits:**
- ✅ Premium placement in rankings
- ✅ Featured expert badge
- ✅ Unlimited lead notifications
- ✅ Analytics dashboard
- ✅ Priority customer support

**Target:** 100-500 paying experts
**Monthly revenue:** 29,900 - 499,500 zł

### Revenue Stream 3: Visibility Boost (One-time)
**Price:** 500-2000 zł one-time
**Duration:** 30 days featured placement
**Benefits:**
- ✅ Top 3 ranking position
- ✅ 🏆 Featured badge
- ✅ 3x more profile views

**Target:** 20-50 boosts per month
**Monthly revenue:** 10,000 - 100,000 zł

### Total Potential Revenue
**Conservative (low estimates):**
100,000 + 29,900 + 10,000 = **139,900 zł/month**

**Optimistic (high estimates):**
750,000 + 499,500 + 100,000 = **1,349,500 zł/month**

---

## 10. DATA SOURCES

### City Data
- **Source:** GUS (Główny Urząd Statystyczny)
- **Fields:** city_name, population, voivodeship, coordinates
- **Format:** CSV import to `wp_pearblog_cities` table

### Service Data
- **Source:** Manual curation + market research
- **Fields:** service_name, category, avg_price, keywords
- **Format:** JSON configuration file

### Problem Data
- **Source:** Google Search Console + keyword research tools
- **Fields:** problem_keyword, intent_type, related_services
- **Format:** JSON configuration file

### Expert Data
- **Source:** Business listings scraping + manual submissions
- **Fields:** name, address, phone, website, rating, services
- **Format:** WordPress custom post type `expert`

---

## 11. IMPLEMENTATION PHASES

### Phase 1: Database Schema (Week 1)
- Create tables: `wp_pearblog_seo_pages`, `wp_pearblog_cities`, `wp_pearblog_services`
- Import city data from GUS
- Configure service and problem keyword lists

### Phase 2: URL Routing System (Week 1)
- Implement custom rewrite rules for `/{city}/{service}/{problem}`
- Create template hierarchy: `page-seo-city.php`, `page-seo-service.php`, etc.
- Handle 404s and redirects

### Phase 3: Page Template Engine (Week 2)
- Build dynamic page generator using template + data injection
- Implement section builders (hero, intro, symptoms, cost, FAQ, CTA, listings)
- Add schema.org markup generation

### Phase 4: Internal Linking Engine (Week 2)
- Build related services algorithm
- Create problem page recommendations
- Implement breadcrumb generation

### Phase 5: Bulk Generation System (Week 3)
- Create WP-CLI command: `wp pearblog seo:generate --cities=10 --services=10`
- Implement batch processing (100 pages per batch)
- Add progress monitoring and error handling

### Phase 6: SEO Optimization (Week 3)
- Add meta title/description generation
- Implement schema markup for all page types
- Optimize images and lazy loading

### Phase 7: Performance Optimization (Week 4)
- Enable page caching (Redis/Memcached)
- Implement CDN for static assets
- Optimize database queries

### Phase 8: Monitoring & Analytics (Week 4)
- Track page views, rankings, conversions per URL
- Monitor indexation status in Google Search Console
- A/B test CTA variants

---

## 12. WP-CLI COMMANDS

### Generate Pages
```bash
# Generate 100 pages (Stage 1)
wp pearblog seo:generate --cities=10 --services=10 --dry-run=false

# Generate problem pages
wp pearblog seo:generate --type=problem --cities=10 --problems=20

# Generate long tail pages
wp pearblog seo:generate --type=longtail --cities=10 --brands=5 --services=10

# Mass generation (Stage 3)
wp pearblog seo:generate --cities=all --services=all --batch-size=100
```

### Update Pages
```bash
# Regenerate content for specific city
wp pearblog seo:update --city=katowice

# Update all service pages
wp pearblog seo:update --type=service --force=true

# Refresh internal links
wp pearblog seo:update --links-only=true
```

### Analytics
```bash
# Show SEO page stats
wp pearblog seo:stats

# Show top performing pages
wp pearblog seo:stats --top=20 --sort=traffic

# Export report
wp pearblog seo:export --format=csv --output=/tmp/seo-report.csv
```

---

## 13. DATABASE SCHEMA

### Table: `wp_pearblog_seo_pages`
```sql
CREATE TABLE wp_pearblog_seo_pages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  page_type VARCHAR(50) NOT NULL, -- 'city', 'service', 'problem', 'longtail'
  city_id BIGINT UNSIGNED NOT NULL,
  service_id BIGINT UNSIGNED NULL,
  problem_keyword VARCHAR(255) NULL,
  url_slug VARCHAR(255) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  meta_description VARCHAR(255) NOT NULL,
  content LONGTEXT NOT NULL,
  schema_json TEXT NULL,
  internal_links TEXT NULL, -- JSON array
  status VARCHAR(20) DEFAULT 'draft', -- 'draft', 'published', 'archived'
  views_count INT DEFAULT 0,
  conversions_count INT DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_page_type (page_type),
  INDEX idx_city_id (city_id),
  INDEX idx_url_slug (url_slug),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table: `wp_pearblog_cities`
```sql
CREATE TABLE wp_pearblog_cities (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  voivodeship VARCHAR(100) NOT NULL,
  population INT UNSIGNED NOT NULL,
  latitude DECIMAL(10, 8) NULL,
  longitude DECIMAL(11, 8) NULL,
  INDEX idx_slug (slug),
  INDEX idx_population (population)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table: `wp_pearblog_services`
```sql
CREATE TABLE wp_pearblog_services (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  category VARCHAR(50) NOT NULL,
  avg_price_min INT UNSIGNED NULL,
  avg_price_max INT UNSIGNED NULL,
  keywords TEXT NULL, -- JSON array
  INDEX idx_slug (slug),
  INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 14. SUCCESS METRICS

### Indexation Metrics
- **Target:** 95%+ of pages indexed within 30 days
- **Tracking:** Google Search Console API

### Traffic Metrics
- **Target:** 100,000+ organic visits/month by Month 3
- **Tracking:** Google Analytics 4

### Ranking Metrics
- **Target:** 30%+ of pages in Top 10 for target keywords by Month 6
- **Tracking:** SEO tools (Ahrefs, SEMrush)

### Conversion Metrics
- **Target:** 2-5% conversion rate (visitor → lead)
- **Tracking:** Custom conversion tracker

### Revenue Metrics
- **Target:** 100,000+ zł/month by Month 6
- **Tracking:** CRM + payment processing

---

## 15. RISK MITIGATION

### Risk 1: Google Penalties (Thin Content)
**Mitigation:**
- Min 1200 words per page
- Unique content generation (not copy-paste templates)
- Add expert insights, local data, current pricing

### Risk 2: Duplicate Content
**Mitigation:**
- Canonical tags for similar pages
- Unique intro paragraphs per city
- Dynamic content insertion (local stats, weather, events)

### Risk 3: Indexation Issues
**Mitigation:**
- Submit sitemaps to GSC in batches (1000 pages per sitemap)
- Monitor crawl budget usage
- Use IndexNow API for instant indexing

### Risk 4: Performance Degradation
**Mitigation:**
- Aggressive caching (Redis + CDN)
- Lazy loading for images and non-critical content
- Database query optimization with proper indexes

---

## 16. NEXT STEPS

1. **Review and approve this specification**
2. **Implement Phase 1: Database Schema**
3. **Implement Phase 2: URL Routing System**
4. **Create Stage 1 dataset (10 cities × 10 services)**
5. **Generate and test first 100 pages**
6. **Monitor indexation and traffic for 2 weeks**
7. **Iterate and optimize based on data**
8. **Scale to Stage 2 (1,000 pages)**
9. **Scale to Stage 3 (10,000+ pages)**

---

**End of Specification**

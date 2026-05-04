# SEO KEYWORD DATABASE - USAGE GUIDE

**Version:** 1.0.0
**Status:** Production Ready
**Purpose:** Programmatic SEO page generation using city × service × problem matrix

---

## 1. OVERVIEW

The SEO Keyword Database system generates 1000+ keyword combinations for programmatic SEO campaigns in the automotive mechanics vertical (Polish market). It combines:

- **10 Cities**: Katowice, Kraków, Warszawa, Wrocław, Poznań, Gdańsk, etc.
- **6 Services**: Wymiana oleju, hamulce, sprzęgło, diagnostyka, zawieszenie, rozrząd
- **8 Problems**: cena, piszczą, stuki, nie działa, szarpie, nie odpala, check engine, ślizga się

### Keyword Types

**HIGH INTENT** (Transactional)
- Pattern: `{service} {city}`
- Example: "wymiana oleju katowice"
- Intent: Ready to book service
- Priority: High

**PROBLEM** (Informational)
- Pattern: `{problem} {city}`
- Example: "piszczące hamulce katowice"
- Intent: Searching for solution
- Priority: Medium

**LONG TAIL** (Specific)
- Pattern: `{service} {problem} {city}`
- Example: "hamulce piszczą katowice co robić"
- Intent: Very specific query
- Priority: Medium

---

## 2. WP-CLI COMMANDS

### 2.1 View Statistics

```bash
wp pearblog seo:stats
```

**Output:**
```
=== SEO Keyword Database Statistics ===

Data Sources:
  Cities: 10
  Services: 6
  Problems: 8

Keyword Combinations:
  High Intent: 120
  Problem: 80
  Long Tail: 156
  ───────────────
  Total: 356
```

### 2.2 Generate Keywords

```bash
# Generate all keywords
wp pearblog seo:keywords

# Generate for specific city
wp pearblog seo:keywords --city=katowice

# Generate high intent keywords only
wp pearblog seo:keywords --type=high_intent --limit=100

# Export to CSV
wp pearblog seo:keywords --format=csv > keywords.csv

# Export to JSON
wp pearblog seo:keywords --format=json > keywords.json
```

**Example Output (table format):**
```
+------------------------------+------------+----------+--------------+-----------+--------+---------------------------+
| Keyword                      | Type       | City     | Service      | Problem   | Intent | Slug                      |
+------------------------------+------------+----------+--------------+-----------+--------+---------------------------+
| Wymiana oleju Katowice       | high_intent| Katowice | Wymiana oleju| -         | trans  | katowice/wymiana-oleju    |
| Piszczące hamulce Kraków     | problem    | Kraków   | -            | piszczą   | info   | krakow/piszcza            |
| Sprzęgło ślizga się Warszawa | long_tail  | Warszawa | Sprzęgło     | ślizga się| info   | warszawa/sprzeglo/slizga  |
+------------------------------+------------+----------+--------------+-----------+--------+---------------------------+
```

### 2.3 Generate Landing Pages

```bash
# Generate 100 pages
wp pearblog seo:generate --batch=100

# Generate for specific city
wp pearblog seo:generate --city=katowice --batch=50

# Generate high intent pages only
wp pearblog seo:generate --type=high_intent --batch=20

# Dry run (preview without creating)
wp pearblog seo:generate --batch=10 --dry-run
```

**Progress Output:**
```
Generating SEO landing pages...
Keywords to generate: 100
Generating pages  100% [====================] 0:00 / 0:00

Created: 87 pages
Skipped: 13 pages (already exist)
```

### 2.4 Search Keywords

```bash
# Search for "wymiana oleju"
wp pearblog seo:search "wymiana oleju"

# Search with limit
wp pearblog seo:search "katowice" --limit=50
```

### 2.5 List Data Sources

```bash
# List available cities
wp pearblog seo:cities

# List available services
wp pearblog seo:services

# List available problems
wp pearblog seo:problems
```

**Example - Cities Output:**
```
=== Available Cities (10) ===

+----------+-------------+-----------------------+------------+
| Slug     | Name        | Voivodeship           | Population |
+----------+-------------+-----------------------+------------+
| katowice | Katowice    | śląskie               | 290,553    |
| krakow   | Kraków      | małopolskie           | 779,115    |
| warszawa | Warszawa    | mazowieckie           | 1,794,166  |
+----------+-------------+-----------------------+------------+
```

---

## 3. USAGE SCENARIOS

### Scenario 1: Generate Initial 100 Pages

```bash
# Step 1: Check stats
wp pearblog seo:stats

# Step 2: Generate first batch
wp pearblog seo:generate --batch=100 --type=high_intent

# Step 3: Verify creation
wp pt24 stats
```

### Scenario 2: City-Specific Campaign

```bash
# Generate all pages for Katowice
wp pearblog seo:generate --city=katowice --batch=100

# Export keywords for Katowice
wp pearblog seo:keywords --city=katowice --format=csv > katowice-keywords.csv
```

### Scenario 3: Export Keyword Database

```bash
# Export all keywords to CSV for analysis
wp pearblog seo:keywords --format=csv > all-keywords.csv

# Export to JSON for API integration
wp pearblog seo:keywords --format=json > keywords.json
```

### Scenario 4: Scaling to 1000+ Pages

```bash
# Stage 1: Top 10 cities × 6 services = 120 pages
wp pearblog seo:generate --type=high_intent --batch=120

# Stage 2: Add problem pages (80 pages)
wp pearblog seo:generate --type=problem --batch=80

# Stage 3: Add long tail (156 pages)
wp pearblog seo:generate --type=long_tail --batch=156

# Total: 356 pages created
```

---

## 4. URL STRUCTURE

Generated pages follow SEO-friendly URL patterns:

### High Intent Pages
```
/{city}/{service}
Example: /katowice/wymiana-oleju
Title: "Wymiana oleju Katowice - Sprawdź ceny i dostępne firmy"
```

### Problem Pages
```
/{city}/{problem}
Example: /katowice/piszcza
Title: "Piszczą Katowice - Przyczyny i rozwiązania"
```

### Long Tail Pages
```
/{city}/{service}/{problem}
Example: /katowice/hamulce/piszcza
Title: "Hamulce - piszczą Katowice - Co robić?"
```

---

## 5. PAGE METADATA

Each generated page includes:

**Post Meta Fields:**
- `pt24_city` - City slug (e.g., "katowice")
- `pt24_city_display` - City name (e.g., "Katowice")
- `pt24_service` - Service slug (e.g., "wymiana-oleju")
- `pt24_service_display` - Service name (e.g., "Wymiana oleju")
- `pt24_problem` - Problem slug (e.g., "cena")
- `pt24_problem_display` - Problem name (e.g., "cena")
- `pt24_keyword` - Full keyword (e.g., "wymiana oleju katowice cena")
- `pt24_keyword_type` - Type (high_intent, problem, long_tail)
- `pt24_intent` - Intent (transactional, informational)
- `pt24_h1` - H1 heading
- `pt24_meta_title` - SEO title
- `pt24_meta_description` - SEO description

**SEO Optimized:**
- Title: Max 60 characters
- Description: Max 155 characters
- Keyword in title, H1, and description
- City name prominently featured

---

## 6. INTEGRATION WITH EXISTING SYSTEM

The SEO Keyword Database integrates with the existing PT24 landing system:

### Compatible with PT24 Commands

```bash
# PT24 commands still work
wp pt24 generate-pages --service=mechanik --city=warszawa
wp pt24 stats
wp pt24 list

# New SEO commands provide enhanced functionality
wp pearblog seo:generate --city=warszawa --batch=50
wp pearblog seo:stats
wp pearblog seo:keywords --city=warszawa
```

### URL Routing

Both systems use the same URL structure:
- `/{city}/{service}` - Works with both PT24 and SEO systems
- Uses `pt24_landing` custom post type
- Compatible with existing templates

---

## 7. KEYWORD DATABASE STRUCTURE

### Cities (10)
- katowice, krakow, warszawa, wroclaw, poznan
- gdansk, szczecin, bydgoszcz, lublin, bialystok

### Services (6)
- wymiana-oleju (150-350 zł)
- hamulce (200-800 zł)
- sprzeglo (800-2500 zł)
- diagnostyka (80-200 zł)
- zawieszenie (300-1500 zł)
- rozrzad (800-3000 zł)

### Problems (8)
- cena (transactional)
- piszcza (informational)
- stuki (informational)
- nie-dziala (informational)
- szarpie (informational)
- nie-odpala (informational)
- check-engine (informational)
- slizga-sie (informational)

---

## 8. EXPANSION STRATEGY

To scale from 356 to 1000+ keywords:

### Add More Cities
```php
// Add in KeywordDatabase.php
'torun' => [
    'name' => 'Toruń',
    'voivodeship' => 'kujawsko-pomorskie',
    'population' => 196935,
],
```

### Add More Services
```php
'klimatyzacja' => [
    'name' => 'Klimatyzacja',
    'category' => 'mechanik',
    'avg_price_min' => 150,
    'avg_price_max' => 600,
    'keywords' => ['naprawa klimatyzacji', 'serwis klimy'],
],
```

### Add More Problems
```php
'przecieka' => [
    'name' => 'przecieka',
    'intent' => 'informational',
    'related_services' => ['rozrzad', 'zawieszenie'],
],
```

**New calculations:**
- 20 cities × 10 services × 2 variants = 400 high intent
- 20 cities × 15 problems = 300 problem pages
- Related combinations = 500+ long tail
- **Total: 1200+ keywords**

---

## 9. PERFORMANCE NOTES

### Batch Generation
- Recommended batch size: 100 pages
- Avoid generating all pages at once
- Monitor server memory during generation

### Database Queries
- Keywords are generated in memory (no database overhead)
- Landing pages stored as `pt24_landing` CPT
- Meta queries optimized with indexes

### Caching
- Consider page caching for landing pages
- Use CDN for static assets
- Enable object caching (Redis/Memcached)

---

## 10. NEXT STEPS

After generating keywords and pages:

1. **Content Enhancement**: Add custom content to high-priority pages
2. **Internal Linking**: Implement 5+ internal links per page
3. **Schema Markup**: Add FAQ and LocalBusiness schema
4. **Indexation**: Submit sitemaps to Google Search Console
5. **Monitoring**: Track rankings and traffic in GSC
6. **Optimization**: A/B test titles and descriptions

---

## 11. EXAMPLE WORKFLOWS

### Daily Workflow: Generate New City
```bash
#!/bin/bash
# generate-city.sh

CITY=$1

echo "Generating pages for: $CITY"

# Generate high intent pages
wp pearblog seo:generate --city=$CITY --type=high_intent --batch=20

# Generate problem pages
wp pearblog seo:generate --city=$CITY --type=problem --batch=15

# Generate long tail pages
wp pearblog seo:generate --city=$CITY --type=long_tail --batch=25

# Show stats
wp pearblog seo:stats
```

**Usage:**
```bash
./generate-city.sh katowice
./generate-city.sh warszawa
```

### Weekly Workflow: Export and Analyze
```bash
#!/bin/bash
# analyze-keywords.sh

# Export all keywords
wp pearblog seo:keywords --format=csv > keywords-$(date +%Y%m%d).csv

# Show stats
wp pearblog seo:stats

# Count pages
wp pt24 stats
```

---

**End of Documentation**

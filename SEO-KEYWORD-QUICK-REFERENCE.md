# SEO KEYWORD DATABASE - QUICK REFERENCE

**Version:** 1.0.0 | **Status:** ✅ Production Ready

---

## 🚀 QUICK START

```bash
# 1. Check what's available
wp pearblog seo:stats

# 2. Generate 100 pages
wp pearblog seo:generate --batch=100

# 3. Export keywords
wp pearblog seo:keywords --format=csv > keywords.csv
```

---

## 📊 STATISTICS

```bash
wp pearblog seo:stats
```

**Output:**
- Cities: 10
- Services: 6
- Problems: 8
- **Total Keywords: 356**
  - High Intent: 120
  - Problem: 80
  - Long Tail: 156

---

## 🔧 MAIN COMMANDS

### Generate Keywords
```bash
# All keywords
wp pearblog seo:keywords

# Specific city
wp pearblog seo:keywords --city=katowice

# Specific type
wp pearblog seo:keywords --type=high_intent

# With limit
wp pearblog seo:keywords --limit=50

# Export to CSV
wp pearblog seo:keywords --format=csv > keywords.csv
```

### Generate Pages
```bash
# Generate 100 pages
wp pearblog seo:generate --batch=100

# City-specific
wp pearblog seo:generate --city=warszawa --batch=50

# Type-specific
wp pearblog seo:generate --type=high_intent --batch=20

# Dry run (preview)
wp pearblog seo:generate --batch=10 --dry-run
```

### Search & Browse
```bash
# Search keywords
wp pearblog seo:search "wymiana oleju"

# List cities
wp pearblog seo:cities

# List services
wp pearblog seo:services

# List problems
wp pearblog seo:problems
```

---

## 📝 KEYWORD TYPES

### 1️⃣ HIGH INTENT (Transactional)
**Pattern:** `{service} {city}`
**Example:** "Wymiana oleju Katowice"
**URL:** `/katowice/wymiana-oleju`
**Count:** 120 keywords

### 2️⃣ PROBLEM (Informational)
**Pattern:** `{problem} {city}`
**Example:** "Piszczące hamulce Katowice"
**URL:** `/katowice/piszcza`
**Count:** 80 keywords

### 3️⃣ LONG TAIL (Specific)
**Pattern:** `{service} {problem} {city}`
**Example:** "Hamulce piszczą Katowice"
**URL:** `/katowice/hamulce/piszcza`
**Count:** 156 keywords

---

## 🗄️ DATA SOURCES

### Cities (10)
- katowice, krakow, warszawa
- wroclaw, poznan, gdansk
- szczecin, bydgoszcz, lublin, bialystok

### Services (6)
- wymiana-oleju (150-350 zł)
- hamulce (200-800 zł)
- sprzeglo (800-2500 zł)
- diagnostyka (80-200 zł)
- zawieszenie (300-1500 zł)
- rozrzad (800-3000 zł)

### Problems (8)
- cena, piszcza, stuki
- nie-dziala, szarpie, nie-odpala
- check-engine, slizga-sie

---

## 🎯 COMMON WORKFLOWS

### Workflow 1: Generate for New City
```bash
# Katowice campaign
wp pearblog seo:generate --city=katowice --batch=100

# Check results
wp pt24 list | grep katowice
```

### Workflow 2: Export for Analysis
```bash
# Export all keywords
wp pearblog seo:keywords --format=csv > all-keywords.csv

# Export specific city
wp pearblog seo:keywords --city=warszawa --format=csv > warszawa.csv
```

### Workflow 3: Staged Deployment
```bash
# Stage 1: High intent (120 pages)
wp pearblog seo:generate --type=high_intent --batch=120

# Stage 2: Problem pages (80 pages)
wp pearblog seo:generate --type=problem --batch=80

# Stage 3: Long tail (156 pages)
wp pearblog seo:generate --type=long_tail --batch=156

# Total: 356 pages
```

---

## 🔗 URL PATTERNS

```
HIGH INTENT:     /{city}/{service}
                 /katowice/wymiana-oleju

PROBLEM:         /{city}/{problem}
                 /katowice/piszcza

LONG TAIL:       /{city}/{service}/{problem}
                 /katowice/hamulce/piszcza
```

---

## 📦 PAGE METADATA

Each generated page includes:

```
✅ pt24_city              = "katowice"
✅ pt24_city_display      = "Katowice"
✅ pt24_service           = "wymiana-oleju"
✅ pt24_service_display   = "Wymiana oleju"
✅ pt24_problem           = "cena"
✅ pt24_keyword           = "wymiana oleju katowice cena"
✅ pt24_keyword_type      = "high_intent"
✅ pt24_intent            = "transactional"
✅ pt24_h1                = "Wymiana oleju Katowice — sprawdź ceny..."
✅ pt24_meta_title        = "Wymiana oleju Katowice - Sprawdź ceny..."
✅ pt24_meta_description  = "Szukasz: Wymiana oleju w Katowice? ✓..."
```

---

## ⚡ QUICK TIPS

### Tip 1: Test First
Always use `--dry-run` to preview before generating:
```bash
wp pearblog seo:generate --batch=10 --dry-run
```

### Tip 2: Batch Wisely
Don't generate too many pages at once:
```bash
# Good: 100 pages per batch
wp pearblog seo:generate --batch=100

# Avoid: Generating all 356 at once
```

### Tip 3: Filter Smart
Use filters to target specific campaigns:
```bash
# City campaign
wp pearblog seo:generate --city=warszawa --batch=50

# Type campaign
wp pearblog seo:generate --type=high_intent --batch=20
```

### Tip 4: Export Before Generating
Save your keyword list first:
```bash
wp pearblog seo:keywords --format=csv > plan.csv
# Review plan.csv
# Then generate pages
```

---

## 🔍 TROUBLESHOOTING

### Issue: Commands not found
```bash
# Solution: Check plugin is active
wp plugin list | grep pearblog
```

### Issue: Duplicate pages
```bash
# System auto-detects duplicates
# Preview with dry-run first
wp pearblog seo:generate --batch=10 --dry-run
```

### Issue: URLs not working
```bash
# Flush rewrite rules
wp rewrite flush
```

---

## 📈 EXPANSION PATH

**Current (v1.0):** 356 keywords
↓
**Add 10 cities:** 712 keywords
↓
**Add 5 services:** 1,190 keywords
↓
**Add 10 problems:** 1,800+ keywords

---

## 📚 DOCUMENTATION

- **SEO-KEYWORD-DATABASE-USAGE.md** - Full usage guide
- **SEO-KEYWORD-DATABASE-IMPLEMENTATION.md** - Implementation details
- **PEARBLOG-SEO-MACHINE-SPEC.md** - Complete system spec

---

## 🎬 EXAMPLE SESSION

```bash
# Session start
$ wp pearblog seo:stats
Data Sources: 10 cities, 6 services, 8 problems
Total Keywords: 356

# Preview generation
$ wp pearblog seo:generate --batch=10 --dry-run
Would create: Wymiana oleju Katowice (katowice/wymiana-oleju)
Would create: Wymiana oleju Kraków (krakow/wymiana-oleju)
...

# Generate pages
$ wp pearblog seo:generate --batch=100
Generating pages... [====================] 100%
Created: 87 pages
Skipped: 13 pages (already exist)

# Export keywords
$ wp pearblog seo:keywords --format=csv > keywords.csv
Generated 356 keywords
```

---

## ✅ CHECKLIST

Before production deployment:

- [ ] Run `wp pearblog seo:stats` to verify data
- [ ] Test `--dry-run` mode first
- [ ] Start with small batch (10-20 pages)
- [ ] Verify URLs are working
- [ ] Check meta titles and descriptions
- [ ] Export keyword list for records
- [ ] Generate in stages (high intent → problem → long tail)
- [ ] Monitor server resources during generation
- [ ] Flush rewrite rules after generation
- [ ] Submit updated sitemap to GSC

---

**Status:** ✅ Ready for Production
**Generated:** 356+ keywords
**Target:** 1000+ keywords (via expansion)

---

**Quick Help:** `wp pearblog seo:stats` | `wp pearblog seo:keywords --help`

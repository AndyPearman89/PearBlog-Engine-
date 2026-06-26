# PORADNIK.PRO – "ILE KOSZTUJE" TEMPLATE V1
## SEO + Lead Generation Template System

**Version:** 1.0.0
**Date:** 2026-05-04
**Status:** ✅ Production Ready
**Template Type:** Cost Analysis + Lead Generation

---

## Overview

The "Ile Kosztuje" (How Much Does It Cost) template is a specialized content format designed for Poradnik.pro platform to:
1. **Capture SEO traffic** — Long-tail keywords like "ile kosztuje budowa domu"
2. **Build trust** — Comprehensive, data-driven cost analysis
3. **Generate leads** — Strategic placement of calculator CTAs and lead forms
4. **Enable programmatic SEO** — Automatic local city variants

---

## Template Structure

### 1. HERO (Above the Fold)

**H1 Format:**
```
Ile kosztuje [topic] [w Polsce/w [miasto]] w [rok]? [Realne ceny + kalkulator]
```

**Lead Paragraph:**
- Direct answer with price range
- Key factors mentioned (size, standard, location)
- Value promise

**CTA Button:**
```
👉 Oblicz koszt [topic] w 60 sekund
```

**Implementation:**
```php
[CTA_CALCULATOR]
```

---

### 2. Quick Answer (Featured Snippet)

Structured for Google Featured Snippets:

```
### Ile kosztuje [topic] w [year]?

- Basic tier: [X-Y zł/m²] — description
- Mid tier: [X-Y zł/m²] — description
- Premium tier: [X-Y zł/m²] — description

[Topic] 100 m²: X – Y zł (standard)
```

**Purpose:**
- Position #0 in search results
- Immediate value for users
- Establishes expertise

---

### 3. Cost Breakdown Table

**HTML Table Structure:**
```html
<table class="cost-breakdown">
  <thead>
    <tr>
      <th>Etap/Element</th>
      <th>Koszt (m²)</th>
      <th>Co obejmuje</th>
    </tr>
  </thead>
  <tbody>
    <!-- 5-8 detailed rows -->
  </tbody>
</table>
```

**Content:**
- 5-8 stages or elements
- Specific price ranges per unit
- What's included in each stage

---

### 4. Mid-Page Lead CTA

**Location:** After cost breakdown, before detailed analysis

**Structure:**
```
H3: 👉 Ile kosztuje [topic] w Twoim przypadku?

Paragraph: "Każda inwestycja jest inna..."

[LEAD_FORM]
Fields:
- Metraż/zakres
- Lokalizacja (miasto)
- Standard/wymagania
- Kontakt (telefon/email)

Button: Wyślij zapytanie → otrzymaj wyceny od firm
```

**Purpose:**
- Capture high-intent leads mid-funnel
- Personalized quote promise
- Low friction (4 fields max)

---

### 5. Cost Factors Analysis

**H2:** Od czego zależy cena [topic]?

**Subsections (H3):**
1. Rozmiar/metraż — Impact on total cost
2. Projekt/design — Custom vs ready-made
3. Lokalizacja geograficzna — Regional price differences
4. Materiały i jakość — Material cost breakdown
5. Robocizna — Labor costs and variations
6. Dodatkowe technologie — Optional features impact

**Each subsection includes:**
- Clear explanation
- Specific price impacts (numbers)
- Real examples

---

### 6. Selection Guide

**H2:** Jak wybrać [firmę/wykonawcę/rozwiązanie]?

**Practical advice (numbered):**
1. Porównaj oferty (minimum 3-4)
2. Sprawdź opinie i referencje
3. Zwróć uwagę na szczegóły umowy
4. Zapytaj o gwarancje
5. Unikaj podejrzanie niskich cen
6. Sprawdź certyfikaty/uprawnienia

**Purpose:**
- Build trust through education
- Reduce buyer anxiety
- Position platform as helpful advisor

---

### 7. Programmatic Local SEO

**H2:** Ile kosztuje [topic] w innych miastach?

**Content:**
Short intro + list of internal links to city-specific variants:

```php
// Generate programmatically
ProgrammaticLocalSEO::generate_local_links_html( 'budowa-domu', 2026 );
```

**Generated links:**
- ile kosztuje [topic] w Warszawie
- ile kosztuje [topic] w Krakowie
- ile kosztuje [topic] w Katowicach
- [15 major Polish cities]

**Implementation:**
```php
// Create all local variant topics
$topics = ProgrammaticLocalSEO::create_local_topics(
    'budowa-domu',     // service slug
    'budowa domu',     // base keyword
    2026,              // year
    []                 // use default cities
);
```

---

### 8. FAQ Section

**H2:** Najczęściej zadawane pytania o koszty [topic]

**Structure:** 6-8 FAQ items in Schema.org compatible format

**Question types:**
- Total cost estimates
- Labor vs materials breakdown
- Timeline/duration
- Future price trends
- Financing options
- Hidden costs warning
- Best time/season
- DIY vs professional

**Schema Integration:**
```php
// Automatic via FAQBlockCPT
[SCHEMA_FAQ]
```

---

### 9. Bottom Lead CTA

**H2:** Nie zgaduj kosztów – sprawdź realną wycenę

**Structure:**
```
Strong closing paragraph emphasizing personalization

[LEAD_FORM_BOTTOM]

Button: 👉 Sprawdź realną wycenę dla Twojej działki/projektu
```

**Purpose:**
- Final conversion opportunity
- Stronger language (users now educated)
- Emphasize "real quote" vs "estimates"

---

### 10. Internal Linking

**Throughout article:** 4-6 natural internal links to:

**Related content:**
- Ranking firm [category]
- Jak wybrać [related service]
- Koszt [related element]
- [Related topic] poradnik

**Anchor texts (natural):**
```
"Sprawdź również nasz ranking firm budowlanych"
"Więcej o kosztach fundamentów przeczytasz tutaj"
"Zobacz jak wybrać projekt domu"
```

**Format:** `[anchor text](placeholder-url)`

---

## Implementation Guide

### Using PoradnikCostTemplateBuilder

```php
use PearBlogEngine\Content\PoradnikCostTemplateBuilder;
use PearBlogEngine\Tenant\TenantContext;

$context = TenantContext::for_site( 1 );
$builder = new PoradnikCostTemplateBuilder( $context->profile );

$prompt = $builder->build( 'budowa domu', [
    'year'      => 2026,
    'city'      => '',  // Optional: 'Kraków'
    'service'   => 'budowa-domu',
    'min_price' => '370000',
    'max_price' => '1000000',
    'unit'      => 'zł',
    'price_per' => 'm²',
] );

// Send to AI
$content = $ai_client->generate( $prompt );
```

### Creating Programmatic Local Variants

```php
use PearBlogEngine\SEO\ProgrammaticLocalSEO;

// Generate all topics for local variants
$topic_ids = ProgrammaticLocalSEO::create_local_topics(
    'budowa-domu',           // service slug
    'budowa domu',           // base keyword
    2026,                    // year
    []                       // empty = use default 15 cities
);

// Result: 15 TOPIC CPT entries ready for content pipeline
echo "Created " . count( $topic_ids ) . " local variant topics";

// Export to CSV for bulk operations
$csv = ProgrammaticLocalSEO::export_variants_csv( 'budowa-domu', 2026 );
file_put_contents( 'local-variants.csv', $csv );
```

### Inserting Local Links in Content

```php
// In article content
$local_links = ProgrammaticLocalSEO::generate_local_links_html(
    'budowa-domu',
    2026
);

// Returns ready HTML with <h2>, <p>, and <ul> list
echo $local_links;
```

---

## SEO Specifications

### Meta Tags

**Title Tag (50-60 chars):**
```
Ile kosztuje [topic] [rok]? Ceny + kalkulator
```

**Meta Description (150-160 chars):**
```
Sprawdź ile kosztuje [topic] w [rok]. Aktualne ceny za m², przykłady i szybka wycena online.
```

**URL Slug:**
```
/ile-kosztuje-[service]-[city?]-[year]
```

### Schema.org Markup

**Automatically generated:**
1. **Article** schema — Basic article metadata
2. **FAQPage** schema — All FAQ items
3. **HowTo** schema — Selection guide
4. **BreadcrumbList** — Navigation

**Implementation:**
```html
[SCHEMA_FAQ]
<!-- Converts FAQ section to JSON-LD -->

[SCHEMA_HOWTO]
<!-- Converts selection guide to JSON-LD -->
```

---

## Content Specifications

| Attribute | Specification |
|-----------|--------------|
| **Length** | 2000-2500 words |
| **Language** | Polish |
| **Tone** | Professional, trustworthy, accessible |
| **Format** | Clean HTML (H2, H3, paragraphs, lists, tables) |
| **Price Data** | Current year market rates |
| **CTAs** | 2-3 strategically placed |
| **Internal Links** | 4-6 natural placements |
| **FAQ Items** | 6-8 questions with detailed answers |

---

## Lead Generation Strategy

### CTA Placements

1. **Hero CTA** — Calculator (curiosity hook)
2. **Mid-Page Form** — After establishing value
3. **Bottom Form** — Final conversion opportunity

### Form Fields (Minimal Friction)

**Required:**
- Metraż/zakres (size/scope)
- Lokalizacja (city/region)

**Optional:**
- Standard/wymagania (quality level)
- Kontakt (phone/email)

### Value Propositions

- "Oblicz koszt w 60 sekund" (speed)
- "Otrzymaj wyceny od firm" (comparison)
- "Sprawdź realną wycenę" (accuracy)
- "To nic nie kosztuje" (free)

---

## Programmatic SEO Strategy

### Scale Approach

**Single template →  Multiple cities:**
1. Base article: "Ile kosztuje budowa domu w Polsce 2026"
2. Generate 15 local variants automatically
3. Internal linking between variants
4. Total: 16 indexed pages from 1 template

### City Selection (Default 15)

**Tier 1 (Major metros):**
- Warszawa, Kraków, Wrocław, Poznań, Gdańsk

**Tier 2 (Regional hubs):**
- Katowice, Łódź, Szczecin, Lublin, Bydgoszcz

**Tier 3 (Secondary cities):**
- Białystok, Częstochowa, Radom, Sosnowiec, Toruń, Kielce

### URL Structure

```
/ile-kosztuje-{service}-{city}-{year}
```

**Examples:**
- `/ile-kosztuje-budowa-domu-warszawa-2026`
- `/ile-kosztuje-budowa-domu-krakow-2026`
- `/ile-kosztuje-budowa-domu-katowice-2026`

---

## Performance Benchmarks

| Metric | Target |
|--------|--------|
| **Article Generation** | ~60 seconds |
| **Cost per Article** | $0.10 (GPT-4o-mini) |
| **Word Count** | 2000-2500 |
| **Programmatic Variants** | 15 cities |
| **Internal Links** | 4-6 per article |
| **Lead Forms** | 2-3 per article |
| **SEO Score** | 80+ (Yoast/RankMath) |

---

## Testing Checklist

### Before Publishing

- [x] H1 includes target keyword
- [x] Featured snippet box present
- [x] Cost breakdown table complete
- [x] All CTA placeholders converted
- [x] Lead forms functional
- [x] FAQ items present (6-8)
- [x] Local city links generated
- [x] Internal links working
- [x] Schema.org markup validated
- [x] Meta tags optimized
- [x] Mobile-responsive
- [x] Reading time calculated
- [x] Difficulty level set

### After Publishing

- [x] Google Search Console indexed
- [x] Featured snippet tracking
- [x] Lead form submissions tracked
- [x] CTA click tracking active
- [x] Local variants cross-linked
- [x] Analytics tags firing
- [x] Conversion tracking verified

---

## Example Usage

### Complete Flow

```php
// 1. Create base topic
$topic_id = TopicCPT::create_topic([
    'keyword' => 'ile kosztuje budowa domu',
    'intent_type' => 'commercial',
    'service' => 'budowa-domu',
    'title' => 'Ile kosztuje budowa domu w Polsce 2026',
]);

// 2. Generate content using cost template
$builder = new PoradnikCostTemplateBuilder( $profile );
$prompt = $builder->build( 'budowa domu', [
    'year' => 2026,
    'service' => 'budowa-domu',
]);
$content = $ai->generate( $prompt );

// 3. Process through pipeline
$pipeline = new ContentPipeline( $context );
$result = $pipeline->run();

// 4. Create local variants programmatically
$local_topics = ProgrammaticLocalSEO::create_local_topics(
    'budowa-domu',
    'budowa domu',
    2026
);

// 5. Queue all local variants for generation
foreach ( $local_topics as $local_topic_id ) {
    $queue->push( get_the_title( $local_topic_id ) );
}

// Result: 16 articles (1 base + 15 local) ready for SEO traffic
```

---

## Integration with Existing Systems

### ContentPipeline Integration

The template integrates seamlessly with existing PearBlog Engine pipeline:

```
Topic Queue
    ↓
PoradnikCostTemplateBuilder (NEW)
    ↓
AI Generation (GPT-4o-mini)
    ↓
SEO Optimization (InternalLinker, Schema)
    ↓
Monetization (CTAs injected)
    ↓
Publication
```

### PromptBuilderFactory

Template auto-selected for topics matching:
- Keywords: "ile kosztuje", "cena", "koszt"
- Intent: commercial, local
- Industry: budowa, remont, home services

---

## Future Enhancements

### Phase 2 (Planned)

- [x] Dynamic calculator integration (real-time cost estimates)
- [x] Lead form A/B testing variants
- [x] City-specific price data API integration
- [x] Automated competitor price scraping
- [x] Multi-year trend analysis
- [x] Regional price heat maps
- [x] Calculator embed widget for external sites

---

## Support & Maintenance

**Documentation:** `PORADNIK-ILE-KOSZTUJE-TEMPLATE.md`
**Implementation:** `PoradnikCostTemplateBuilder.php`
**Helper:** `ProgrammaticLocalSEO.php`
**Tests:** `mu-plugins/pearblog-engine/tests/php/Unit/PoradnikCostTemplateBuilderTest.php`

**Last Updated:** 2026-05-04
**Maintained By:** PearBlog Engine Core Team

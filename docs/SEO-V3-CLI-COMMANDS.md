# SEO V3 CLI Commands

Complete WP-CLI command reference for the SEO V3 programmatic landing page generation system.

## Overview

SEO V3 provides a powerful set of WP-CLI commands to generate and manage programmatic landing pages at scale. The system supports multiple verticals (industries), search intents, and automated keyword generation.

## Available Commands

### Statistics

Show SEO V3 platform statistics including page counts by vertical and keyword generation metrics.

```bash
wp pearblog seo-v3:stats
```

**Output includes:**
- Total SEO V3 landing pages
- Pages count by vertical (elektryk, hydraulik, mechanik, etc.)
- Total keyword sets generated
- Keyword sets count by intent (transactional, informational, commercial, navigational)

### Keyword Generation

Generate keywords for a specific vertical and search intent. Keywords are stored in WordPress options for reuse.

```bash
wp pearblog seo-v3:keywords --vertical=<vertical> --intent=<intent> [--limit=<number>]
```

**Options:**
- `--vertical=<vertical>` - Required. Industry slug (elektryk, hydraulik, mechanik, laweta, wulkanizacja, klimatyzacja, lakiernik, blacharstwo)
- `--intent=<intent>` - Required. Search intent type:
  - `transactional` - User ready to purchase/hire
  - `informational` - User seeking information
  - `commercial` - User researching options
  - `navigational` - User looking for specific location/service
- `--limit=<number>` - Optional. Number of keywords to generate (default: 50)

**Examples:**

```bash
# Generate 50 transactional keywords for elektryk
wp pearblog seo-v3:keywords --vertical=elektryk --intent=transactional

# Generate 100 informational keywords for hydraulik
wp pearblog seo-v3:keywords --vertical=hydraulik --intent=informational --limit=100

# Generate commercial keywords for mechanik
wp pearblog seo-v3:keywords --vertical=mechanik --intent=commercial
```

**Keyword patterns by intent:**

- **Transactional**: "[service] [location]", "[quality] [vertical] [location]", "[urgency] [vertical] [location]"
- **Informational**: "jak [service]", "ile kosztuje [service]", "co to jest [service]"
- **Commercial**: "ceny [service]", "[service] opinie", "[vertical] [location] opinie"
- **Navigational**: "[vertical] [location]", "[vertical] w [location]"

### Landing Page Generation

Generate landing pages in bulk using pre-generated keywords or automatically creating them on-the-fly.

```bash
wp pearblog seo-v3:generate --vertical=<vertical> [--batch=<number>] [--intent=<intent>]
```

**Options:**
- `--vertical=<vertical>` - Required. Industry slug
- `--batch=<number>` - Optional. Number of pages to generate (default: 10)
- `--intent=<intent>` - Optional. Keyword intent to use (default: transactional)

**Examples:**

```bash
# Generate 100 landing pages for hydraulik
wp pearblog seo-v3:generate --vertical=hydraulik --batch=100

# Generate 50 commercial pages for elektryk
wp pearblog seo-v3:generate --vertical=elektryk --batch=50 --intent=commercial

# Generate 10 transactional pages for mechanik (default)
wp pearblog seo-v3:generate --vertical=mechanik
```

**Features:**
- Automatically generates keywords if none exist for the vertical/intent combination
- Skips duplicate pages (checks existing keywords)
- Creates SEO-optimized content with H2 headings, FAQs, and CTAs
- Adds proper meta descriptions and SEO V3 tracking metadata
- Shows progress bar during generation

### List Verticals

Display all available verticals (industries) supported by SEO V3.

```bash
wp pearblog seo-v3:verticals
```

**Output:**
```
=== Available Verticals ===

elektryk => Elektryk samochodowy
hydraulik => Hydraulik
mechanik => Mechanik samochodowy
laweta => Laweta
wulkanizacja => Wulkanizacja
klimatyzacja => Klimatyzacja
lakiernik => Lakiernik
blacharstwo => Blacharstwo
```

### List Services

Display all services available for a specific vertical.

```bash
wp pearblog seo-v3:services <vertical>
```

**Examples:**

```bash
# List services for elektryk
wp pearblog seo-v3:services elektryk

# List services for hydraulik
wp pearblog seo-v3:services hydraulik
```

**Output example (elektryk):**
```
=== Services for Elektryk samochodowy ===

  - diagnostyka elektryczna
  - naprawa instalacji
  - wymiana alternator
  - naprawa rozrusznika
  - programowanie sterowników
  - naprawa świateł
```

### List Modifiers

Display all available keyword modifiers used in keyword generation.

```bash
wp pearblog seo-v3:modifiers
```

**Output:**
```
=== Available Modifiers ===

Location modifiers:
  - warszawa
  - kraków
  - wrocław
  - poznań
  - gdańsk
  - łódź
  - szczecin
  - katowice

Quality modifiers:
  - tani
  - dobry
  - profesjonalny
  - sprawdzony
  - polecany
  - najlepszy

Urgency modifiers:
  - 24h
  - pilne
  - szybko
  - natychmiast
  - weekend
```

## Complete Workflow Example

Here's a complete workflow for launching a new vertical with 500 landing pages:

```bash
# 1. List available verticals
wp pearblog seo-v3:verticals

# 2. Check services for your chosen vertical
wp pearblog seo-v3:services hydraulik

# 3. Generate transactional keywords
wp pearblog seo-v3:keywords --vertical=hydraulik --intent=transactional --limit=200

# 4. Generate informational keywords
wp pearblog seo-v3:keywords --vertical=hydraulik --intent=informational --limit=100

# 5. Generate commercial keywords
wp pearblog seo-v3:keywords --vertical=hydraulik --intent=commercial --limit=200

# 6. Create 200 transactional landing pages
wp pearblog seo-v3:generate --vertical=hydraulik --batch=200 --intent=transactional

# 7. Create 100 informational landing pages
wp pearblog seo-v3:generate --vertical=hydraulik --batch=100 --intent=informational

# 8. Create 200 commercial landing pages
wp pearblog seo-v3:generate --vertical=hydraulik --batch=200 --intent=commercial

# 9. Check statistics
wp pearblog seo-v3:stats
```

## Data Storage

### Keywords
Keywords are stored in WordPress options with the following naming convention:
```
pearblog_seo_v3_keywords_{intent}_{vertical}
```

Examples:
- `pearblog_seo_v3_keywords_transactional_elektryk`
- `pearblog_seo_v3_keywords_informational_hydraulik`

### Landing Page Metadata
Each generated landing page includes the following post meta:
- `pearblog_seo_v3_enabled` - Set to "1" to mark as SEO V3 page
- `pearblog_seo_v3_vertical` - Vertical slug
- `pearblog_seo_v3_keyword` - Target keyword
- `pearblog_seo_v3_intent` - Search intent
- `pearblog_meta_description` - SEO meta description

## Technical Details

### Supported Verticals

| Slug | Display Name |
|------|--------------|
| elektryk | Elektryk samochodowy |
| hydraulik | Hydraulik |
| mechanik | Mechanik samochodowy |
| laweta | Laweta |
| wulkanizacja | Wulkanizacja |
| klimatyzacja | Klimatyzacja |
| lakiernik | Lakiernik |
| blacharstwo | Blacharstwo |

### Search Intents

1. **Transactional** - High commercial intent, ready to convert
2. **Informational** - Learning and research phase
3. **Commercial** - Comparing options and prices
4. **Navigational** - Looking for specific service/location

### Content Structure

Generated landing pages include:
- SEO-optimized title with year (e.g., "tani hydraulik warszawa — Najlepsza oferta 2024")
- H2: "Szukasz: [keyword]?"
- Introduction paragraph
- "Dlaczego warto skorzystać z naszej platformy?" section (for transactional)
- "Jak to działa?" section with ordered list
- "Co warto wiedzieć?" section (for informational)
- FAQ section with common questions
- Proper meta description with checkmarks and call-to-action

## Error Handling

The system includes comprehensive error handling:
- Validates vertical slugs
- Validates search intent values
- Checks for duplicate pages before creation
- Provides clear error messages for invalid inputs
- Uses WP_CLI::error() for fatal errors
- Uses WP_CLI::warning() for non-fatal warnings
- Uses WP_CLI::success() for successful operations

## Performance Tips

1. **Batch Processing**: Generate pages in smaller batches (100-200) for better memory management
2. **Keyword Pre-generation**: Generate keywords separately before creating pages
3. **Intent Separation**: Generate different intents in separate batches
4. **Monitoring**: Use `stats` command to track progress between batches

## Integration with Existing Systems

SEO V3 integrates seamlessly with:
- PearBlog Engine content pipeline
- Programmatic SEO engine (ProgrammaticSEO)
- SEO meta generation
- WordPress post system
- Post metadata system

## See Also

- [PearBlog CLI Commands](../README.md)
- [Programmatic SEO Guide](PROGRAMMATIC-SEO.md)
- [Content Pipeline Documentation](CONTENT-PIPELINE.md)

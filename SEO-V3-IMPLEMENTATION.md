# SEO V3 - Programmatic Landing Page System

Complete implementation of SEO V3 WP-CLI commands for programmatic landing page generation at scale.

## What Was Implemented

### 1. SEOV3Command Class (`src/CLI/SEOV3Command.php`)
A comprehensive WP-CLI command class providing 6 commands for managing programmatic landing pages:

#### Commands

1. **`wp pearblog seo-v3:stats`**
   - Shows platform statistics
   - Reports page counts by vertical
   - Displays keyword generation metrics
   - Breaks down data by search intent

2. **`wp pearblog seo-v3:keywords --vertical=X --intent=Y`**
   - Generates keywords for a specific vertical and search intent
   - Supports 8 verticals: elektryk, hydraulik, mechanik, laweta, wulkanizacja, klimatyzacja, lakiernik, blacharstwo
   - Supports 4 intents: transactional, informational, commercial, navigational
   - Configurable limit (default: 50 keywords)
   - Stores keywords in WordPress options for reuse

3. **`wp pearblog seo-v3:generate --vertical=X --batch=N`**
   - Creates landing pages in bulk
   - Uses pre-generated keywords or generates on-the-fly
   - Prevents duplicates by checking existing pages
   - Shows progress bar during generation
   - Configurable batch size and intent

4. **`wp pearblog seo-v3:verticals`**
   - Lists all available verticals
   - Shows slug → display name mapping

5. **`wp pearblog seo-v3:services <vertical>`**
   - Lists services for a specific vertical
   - Shows 4-8 services per vertical

6. **`wp pearblog seo-v3:modifiers`**
   - Displays available keyword modifiers
   - Grouped by type: locations, quality, urgency

### 2. Integration (`src/Core/Plugin.php`)
- Registered `wp pearblog seo-v3` command namespace
- Integrated with existing PearBlog CLI infrastructure
- Follows WordPress WP-CLI best practices

### 3. Documentation (`docs/SEO-V3-CLI-COMMANDS.md`)
Complete 300+ line documentation covering:
- All command usage with examples
- Complete workflow examples
- Data storage specifications
- Technical details and patterns
- Error handling information
- Performance optimization tips

## Key Features

### Keyword Generation Algorithm
Generates keywords based on search intent:

- **Transactional**: `[service] [location]`, `[quality] [vertical] [location]`, `[urgency] [vertical] [location]`
- **Informational**: `jak [service]`, `ile kosztuje [service]`, `co to jest [service]`
- **Commercial**: `ceny [service]`, `[service] opinie`, `[vertical] [location] opinie`
- **Navigational**: `[vertical] [location]`, `[vertical] w [location]`

### Content Generation
Each landing page includes:
- SEO-optimized title with year
- Structured H2 headings
- Introduction and value propositions
- "How it works" section with ordered list
- FAQ section with common questions
- Proper meta descriptions
- SEO V3 tracking metadata

### Data Storage
- Keywords: `pearblog_seo_v3_keywords_{intent}_{vertical}`
- Page meta:
  - `pearblog_seo_v3_enabled`
  - `pearblog_seo_v3_vertical`
  - `pearblog_seo_v3_keyword`
  - `pearblog_seo_v3_intent`
  - `pearblog_meta_description`

## Supported Verticals

| Vertical | Display Name | Services |
|----------|--------------|----------|
| elektryk | Elektryk samochodowy | 6 services |
| hydraulik | Hydraulik | 6 services |
| mechanik | Mechanik samochodowy | 6 services |
| laweta | Laweta | 4 services |
| wulkanizacja | Wulkanizacja | 4 services |
| klimatyzacja | Klimatyzacja | 4 services |
| lakiernik | Lakiernik | 4 services |
| blacharstwo | Blacharstwo | 4 services |

## Example Usage

```bash
# Generate keywords for elektryk (transactional)
wp pearblog seo-v3:keywords --vertical=elektryk --intent=transactional

# Create 100 landing pages for hydraulik
wp pearblog seo-v3:generate --vertical=hydraulik --batch=100

# View statistics
wp pearblog seo-v3:stats

# List all verticals
wp pearblog seo-v3:verticals

# List services for elektryk
wp pearblog seo-v3:services elektryk

# View available modifiers
wp pearblog seo-v3:modifiers
```

## Complete Workflow for 500 Pages

```bash
# Step 1: Generate keywords
wp pearblog seo-v3:keywords --vertical=hydraulik --intent=transactional --limit=200
wp pearblog seo-v3:keywords --vertical=hydraulik --intent=informational --limit=100
wp pearblog seo-v3:keywords --vertical=hydraulik --intent=commercial --limit=200

# Step 2: Generate landing pages
wp pearblog seo-v3:generate --vertical=hydraulik --batch=200 --intent=transactional
wp pearblog seo-v3:generate --vertical=hydraulik --batch=100 --intent=informational
wp pearblog seo-v3:generate --vertical=hydraulik --batch=200 --intent=commercial

# Step 3: Check results
wp pearblog seo-v3:stats
```

## Files Changed

1. **New File**: `mu-plugins/pearblog-engine/src/CLI/SEOV3Command.php` (679 lines)
   - Complete WP-CLI command implementation
   - All 6 commands with full functionality
   - Keyword generation algorithms
   - Landing page creation logic

2. **Modified**: `mu-plugins/pearblog-engine/src/Core/Plugin.php`
   - Added SEO V3 command registration
   - Integrated with existing CLI infrastructure

3. **New File**: `docs/SEO-V3-CLI-COMMANDS.md` (303 lines)
   - Comprehensive command documentation
   - Usage examples and workflows
   - Technical specifications

## Testing

All PHP files passed syntax validation:
```bash
php -l src/CLI/SEOV3Command.php  # No syntax errors
php -l src/Core/Plugin.php       # No syntax errors
```

## Next Steps

To use this system:

1. Run `wp pearblog seo-v3:verticals` to see available verticals
2. Generate keywords for your chosen vertical(s)
3. Create landing pages in batches
4. Monitor with `wp pearblog seo-v3:stats`
5. Scale up to thousands of pages as needed

## Architecture

- **Modular Design**: Easy to add new verticals, services, or modifiers
- **WP-CLI Best Practices**: Follows WordPress CLI conventions
- **Error Handling**: Comprehensive validation and error messages
- **Progress Feedback**: Real-time progress bars for batch operations
- **Duplicate Prevention**: Checks existing pages before creation
- **SEO Optimized**: Proper metadata, schema-ready content structure

## Performance

- Batch processing with configurable sizes
- Keyword caching in WordPress options
- Duplicate detection before page creation
- Progress bars for visibility during long operations
- Memory-efficient processing

## Future Enhancements

Potential additions:
- AI-powered content generation integration
- Custom vertical/service configuration
- Keyword performance tracking
- A/B testing integration
- Automatic keyword refresh from search console
- Multi-language support

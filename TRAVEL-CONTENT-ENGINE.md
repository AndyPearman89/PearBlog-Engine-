# Travel Content Engine Documentation

## Overview

The PearBlog Engine now includes specialized prompt builders for travel and tourism content, with particular focus on the Beskidy mountains region in Poland. This system provides three levels of content generation sophistication:

1. **Generic** - Standard SEO content (original PromptBuilder)
2. **Travel** - Structured travel content with mandatory sections
3. **Beskidy** - Enhanced Beskidy-specific content with weather awareness and day planning
4. **Multi-Language** - Localized content for PL/EN/DE markets

## Architecture

### Class Hierarchy

```
PromptBuilder (base class)
  └─ TravelPromptBuilder
       └─ BeskidyPromptBuilder
            └─ MultiLanguageTravelBuilder
```

### Factory Pattern

The `PromptBuilderFactory` automatically selects the appropriate builder based on the site's industry configuration.

## Content Types

### 1. Generic Content (PromptBuilder)

**Use for:** Any non-travel content (technology, health, business, etc.)

**Features:**
- Basic SEO structure
- Industry-specific tone
- Meta description
- Simple monetization instructions

**Auto-selected when:** Industry doesn't contain travel-related keywords

---

### 2. Travel Content (TravelPromptBuilder)

**Use for:** General travel and tourism content

**Features:**
- Mandatory TL;DR section with practical info (time, difficulty, best for, location)
- Structured sections (Why visit, Description, How to get there, Weather)
- Accommodation recommendations section
- Practical tips
- FAQ section
- Internal linking ("Zobacz też" / "See also")

**Required Sections:**
```html
<h1>Main keyword</h1>
<h2>TL;DR</h2>
<h2>Dlaczego warto?</h2>
<h2>Opis i szczegóły</h2>
<h2>Jak dojechać i parking</h2>
<h2>Warunki i pogoda</h2>
<h2>Noclegi w okolicy</h2>
<h2>Praktyczne wskazówki</h2>
<h2>FAQ</h2>
<h2>Zobacz też</h2>
```

**Auto-selected when:** Industry contains: travel, tourism, vacation, hiking, mountains, adventure, outdoor, destinations, trips, podróże

---

### 3. Beskidy Content (BeskidyPromptBuilder)

**Use for:** Beskidy mountains-specific content (po.beskidzku.pl)

**Enhanced Features:**
- All TravelPromptBuilder features PLUS:
- **Weather-aware content:** Seasonal recommendations, weather impact, what to bring
- **AI Day Planner:** Morning/midday/evening itinerary with time estimates
- **Plan B alternatives:** Indoor/backup options for bad weather
- **Intent-aware:** Adapts to informational, navigational, or transactional intent
- **Natural monetization:** Soft CTAs for accommodation booking

**Additional Sections:**
```html
<h2>Plan dnia</h2>
  - Rano (morning)
  - W południe (midday)
  - Popołudnie/wieczór (afternoon/evening)

<h3>Plan B (alternatywa)</h3>
  - Alternative activities for bad weather
```

**Quality Standards:**
- Minimum 1,200 words
- No generic AI phrases
- Practical tips required (parking, GPS, time estimates)
- Natural Polish language (native-level)
- Short paragraphs (2-4 sentences)

**Auto-selected when:** Industry contains: beskidy, beskid, po.beskidzku, mountains poland

---

### 4. Multi-Language Content (MultiLanguageTravelBuilder)

**Use for:** Reaching international markets with localized (not translated) content

**Supported Languages:**

#### Polish (PL) - Original
- Full Beskidy prompt
- Native Polish tone
- Local context assumed
- Keywords: "szlaki", "noclegi", "góry"

#### English (EN) - International Audience
- Explains WHERE Beskidy is (Poland, Central Europe)
- Adds context for first-time visitors
- References nearest airports and cities
- Mentions booking platforms (Booking.com, Airbnb)
- Keywords: "hiking trails", "mountains", "Poland"
- Explains Polish terms when used

#### German (DE) - German-Speaking Tourists
- Precise, structured tone (German preference)
- Detailed practical information
- High-value accommodation focus
- Keywords: "Wanderwege", "Gebirge", "Polen"
- Formal yet helpful tone

**Language-Specific Adaptations:**

| Aspect | PL | EN | DE |
|--------|----|----|-----|
| Tone | Friendly, expert | Helpful, clear | Precise, structured |
| Context | Local assumed | Explains location | Adds EU context |
| Keywords | Native Polish | English variants | German terms |
| Monetization | Local booking | Global platforms | High-conversion |
| Structure | Full Beskidy | Adapted sections | Systematic |

**Auto-selected when:** Industry contains Beskidy keywords AND language is set to PL/EN/DE

---

## Configuration

### Setting the Industry

In WordPress admin, go to **PearBlog Engine > Settings**:

```
Industry: Beskidy mountains travel and hiking
```

This will automatically activate the BeskidyPromptBuilder.

### Language Configuration

```
Language: pl (for Polish - Beskidy content)
Language: en (for English - International)
Language: de (for German - German tourists)
```

### Monetization Strategy

For travel content, set:

```
Monetization: affiliate
```

This adds natural accommodation recommendations.

---

## Filters & Hooks

### Override Builder Selection

```php
add_filter( 'pearblog_prompt_builder_class', function( $class, $profile ) {
    // Force use of BeskidyPromptBuilder for specific site
    if ( get_blog_option( get_current_blog_id(), 'use_beskidy_builder' ) ) {
        return 'PearBlogEngine\Content\BeskidyPromptBuilder';
    }
    return $class;
}, 10, 2 );
```

### Detect Beskidy Content

```php
add_filter( 'pearblog_is_beskidy_content', function( $is_beskidy, $industry, $profile ) {
    // Manual override
    return ( get_option( 'enable_beskidy_mode' ) === 'yes' );
}, 10, 3 );
```

### Customize Travel Prompt

```php
add_filter( 'pearblog_travel_prompt', function( $prompt, $topic, $profile ) {
    // Add custom section
    $prompt .= "\n<h2>Custom Section</h2>\n";
    $prompt .= "Add your custom instructions here.\n";
    return $prompt;
}, 10, 3 );
```

### Customize Beskidy Prompt

```php
add_filter( 'pearblog_beskidy_prompt', function( $prompt, $topic, $profile ) {
    // Modify Beskidy-specific instructions
    $prompt .= "\nADDITIONAL RULE: Always mention trail difficulty level.\n";
    return $prompt;
}, 10, 3 );
```

### Customize Multi-Language Prompt

```php
add_filter( 'pearblog_multilang_prompt', function( $prompt, $topic, $profile, $language ) {
    // Add language-specific customizations
    if ( $language === 'en' ) {
        $prompt .= "\nNOTE: Explain currency (PLN to USD/EUR).\n";
    }
    return $prompt;
}, 10, 4 );
```

---

## Content Validation

### Validate Generated Content

```php
use PearBlogEngine\Content\ContentValidator;

$validator = new ContentValidator();
$result = $validator->validate( $content, 'beskidy' );

if ( ! $result['valid'] ) {
    echo $validator->format_report( $result );
}
```

### Validation Types

```php
// Generic validation
$result = $validator->validate( $content, 'generic' );

// Travel validation
$result = $validator->validate( $content, 'travel' );

// Beskidy validation (most strict)
$result = $validator->validate( $content, 'beskidy' );
```

### Validation Checks

**Errors (must fix):**
- Missing META description
- Missing H1 title
- Missing required sections (TL;DR, Noclegi, FAQ, etc.)

**Warnings (recommended fixes):**
- Content too short (<1,000 words)
- Missing recommended sections (Weather, Day Plan)
- Generic AI phrases detected
- Potential keyword stuffing
- TL;DR missing bullet points
- No Plan B alternative in day plan

---

## Example Usage

### Basic Travel Content

```php
// Set industry to "travel and tourism"
update_option( 'pearblog_industry', 'travel and tourism' );

// Add topic to queue
$queue = new TopicQueue( get_current_blog_id() );
$queue->push( 'Best hiking trails in the Alps' );

// Run pipeline - will use TravelPromptBuilder
$pipeline = new ContentPipeline( $context );
$result = $pipeline->run();
```

### Beskidy-Specific Content

```php
// Set industry to trigger Beskidy mode
update_option( 'pearblog_industry', 'Beskidy mountains and hiking' );
update_option( 'pearblog_language', 'pl' );

// Add Beskidy topic
$queue->push( 'Babia Góra szlaki' );

// Run pipeline - will use BeskidyPromptBuilder
$result = $pipeline->run();
```

### Multi-Language Content

```php
// Polish version
update_option( 'pearblog_language', 'pl' );
$queue->push( 'Turbacz szlak niebieski' );
$pipeline->run(); // Uses Polish Beskidy template

// English version
update_option( 'pearblog_language', 'en' );
$queue->push( 'Turbacz hiking trails' );
$pipeline->run(); // Uses English localized template

// German version
update_option( 'pearblog_language', 'de' );
$queue->push( 'Turbacz Wanderwege' );
$pipeline->run(); // Uses German localized template
```

---

## Content Structure Reference

### TL;DR Section Format

```html
<h2>TL;DR</h2>
<ul>
  <li>⏱ Czas: 4-5 godzin</li>
  <li>📈 Trudność: Średnia</li>
  <li>👨‍👩‍👧 Dla kogo: Rodziny z dziećmi 8+, początkujący</li>
  <li>📍 Lokalizacja: Beskid Wyspowy, woj. małopolskie</li>
</ul>
```

### Day Plan Section Format

```html
<h2>Plan dnia</h2>

<h3>Rano (8:00 - 11:00)</h3>
<p>Start z parkingu w Zakopanem. Wejście szlakiem czerwonym...</p>

<h3>W południe (11:00 - 14:00)</h3>
<p>Lunch na szczycie. Czas na zdjęcia i odpoczynek...</p>

<h3>Popołudnie (14:00 - 17:00)</h3>
<p>Zjazd alternatywnym szlakiem...</p>

<h3>Plan B (alternatywa)</h3>
<p>Jeśli pogoda jest zła, możesz odwiedzić...</p>
```

### Accommodation Section Format

```html
<h2>Noclegi w okolicy</h2>

<p>W okolicy znajdziesz różne opcje noclegowe:</p>

<ul>
  <li><strong>Zakopane</strong> - hotele, pensjonaty, apartamenty</li>
  <li><strong>Schronisko PTTK</strong> - na szlaku</li>
  <li><strong>Glampingi</strong> - w dolinie</li>
</ul>

<p>Sprawdź dostępne noclegi w okolicy na popularnych platformach rezerwacyjnych.</p>
```

---

## Quality Standards

### Polish Language (Native-Level)

- Natural flow and rhythm
- Proper use of Polish idioms
- No direct translations from English
- Culturally appropriate references

### Content Requirements

- **Minimum length:** 1,000 words (1,200 for Beskidy)
- **Paragraph length:** 2-4 sentences
- **Sentence variety:** Mix of short and medium sentences
- **No AI clichés:** Avoid "In today's digital age", etc.
- **Practical focus:** Real tips (parking, GPS, times)
- **Specific information:** Actual places, distances, durations

### SEO Standards

- **H1:** One per article, contains main keyword
- **H2:** 5-8 per article, descriptive and keyword-rich
- **H3:** 2-5 per article, for subsections
- **Meta description:** 150-160 characters, compelling
- **Keyword density:** 1-2%, natural integration
- **Internal links:** 3-5 contextual links to related content

---

## Troubleshooting

### Builder Not Auto-Selecting

**Problem:** Generic builder used instead of travel builder

**Solutions:**
1. Check industry setting contains trigger keywords
2. Use filter to force builder class
3. Verify PromptBuilderFactory is being called

```php
// Debug which builder is selected
add_action( 'pearblog_pipeline_started', function( $topic, $context ) {
    $builder = PromptBuilderFactory::create( $context->profile );
    error_log( 'Builder class: ' . get_class( $builder ) );
}, 10, 2 );
```

### Content Validation Failing

**Problem:** Generated content doesn't pass validation

**Solutions:**
1. Check AI model has enough tokens (increase to 4,096)
2. Verify prompt contains all required section instructions
3. Review AI output for formatting issues
4. Use ContentValidator to identify specific missing sections

```php
// Validate and log issues
$validator = new ContentValidator();
$result = $validator->validate( $content, 'beskidy' );
error_log( $validator->format_report( $result ) );
```

### Multi-Language Issues

**Problem:** Content not properly localized

**Solutions:**
1. Verify language code is correct (pl, en, de)
2. Check MultiLanguageTravelBuilder is being used
3. Review prompt includes language-specific instructions
4. Ensure AI model supports target language well

---

## Performance Considerations

### Token Usage

- **Generic:** ~500 tokens
- **Travel:** ~800 tokens
- **Beskidy:** ~1,200 tokens
- **Multi-Language:** ~1,000-1,500 tokens

### Content Length

- **Generic:** 1,000+ words
- **Travel:** 1,000+ words
- **Beskidy:** 1,200+ words
- **Multi-Language:** Varies by language (DE typically longer)

### Processing Time

- Prompt building: <1ms
- AI generation: 10-30 seconds (depends on OpenAI API)
- Validation: <100ms

---

## Future Enhancements

### Planned Features

1. **Weather API Integration** - Real-time weather data in content
2. **Interactive Maps** - Embedded trail maps
3. **User Reviews** - Community-contributed tips
4. **Photo Integration** - Auto-suggest relevant images
5. **Seasonal Variants** - Automatic summer/winter content
6. **Difficulty Calculator** - AI-powered trail difficulty assessment

### Extensibility

The system is designed to be extended:

- Add new language support (FR, IT, ES)
- Create region-specific builders (Alps, Tatras, etc.)
- Implement industry-specific builders (diving, cycling, skiing)
- Add custom validation rules
- Integrate third-party APIs (weather, booking, maps)

---

## Support & Contributing

For issues, questions, or contributions, please:

1. Check the troubleshooting section
2. Review filter/hook documentation
3. Test with ContentValidator
4. Open GitHub issue with detailed information

---

**Version:** 1.0.0
**Last Updated:** 2026-04-03
**Compatibility:** PearBlog Engine 1.x+
**Author:** PearBlog Development Team

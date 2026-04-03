# Beskidy Content Engine - Quick Reference

## 🚀 Quick Start

### Set Up Beskidy Content Generation

```php
// In WordPress Admin: PearBlog Engine > Settings
Industry: Beskidy mountains travel
Language: pl
Monetization: affiliate
```

### Add Topics to Queue

```php
$queue = new TopicQueue( get_current_blog_id() );
$queue->push( 'Babia Góra szlaki' );
$queue->push( 'Turbacz jak dojść' );
$queue->push( 'Pilsko noclegi w okolicy' );
```

### Generate Content

Content is generated automatically via cron, or manually:

```php
$pipeline = new ContentPipeline( $context );
$result = $pipeline->run();
```

---

## 📋 Content Structure Checklist

Every Beskidy article must include:

- [ ] `META:` description at top (160 chars max)
- [ ] `<h1>` Main keyword title
- [ ] `<h2>TL;DR</h2>` with 4 bullet points (⏱📈👨‍👩‍👧📍)
- [ ] `<h2>Dlaczego warto?</h2>` Why visit section
- [ ] `<h2>Opis / szczegóły</h2>` Description
- [ ] `<h2>Jak dojechać</h2>` How to get there + parking
- [ ] `<h2>Warunki i pogoda</h2>` Weather conditions
- [ ] `<h2>Plan dnia</h2>` Day itinerary (morning/midday/evening)
- [ ] `<h3>Plan B</h3>` Alternative for bad weather
- [ ] `<h2>Noclegi w okolicy</h2>` Accommodation (MONETIZATION)
- [ ] `<h2>Praktyczne wskazówki</h2>` Practical tips
- [ ] `<h2>FAQ</h2>` 3-5 questions
- [ ] `<h2>Zobacz też</h2>` Internal links (3-5)

---

## 🌍 Multi-Language Quick Reference

### Polish (Default)
```php
update_option( 'pearblog_language', 'pl' );
// Full Beskidy template with all Polish sections
```

### English (International)
```php
update_option( 'pearblog_language', 'en' );
// Adapted for international tourists
// Explains WHERE Beskidy is (Poland, Europe)
// References airports, booking platforms
```

### German (Precise)
```php
update_option( 'pearblog_language', 'de' );
// Structured, precise German style
// High-value accommodation focus
```

---

## 🔧 Common Customizations

### Force Beskidy Builder

```php
add_filter( 'pearblog_is_beskidy_content', '__return_true' );
```

### Add Custom Section

```php
add_filter( 'pearblog_beskidy_prompt', function( $prompt, $topic, $profile ) {
    $prompt .= "\n<h2>Dodatkowa sekcja</h2>\n";
    $prompt .= "Custom instructions here.\n";
    return $prompt;
}, 10, 3 );
```

### Validate Content

```php
$validator = new ContentValidator();
$result = $validator->validate( $content, 'beskidy' );

if ( ! $result['valid'] ) {
    foreach ( $result['errors'] as $error ) {
        echo "❌ {$error}\n";
    }
}
```

---

## 📊 Builder Selection Logic

```
Industry contains "beskidy" keywords?
  ├─ YES → MultiLanguageTravelBuilder
  │         ├─ Language: pl → Full Beskidy (Polish)
  │         ├─ Language: en → International (English)
  │         └─ Language: de → Precise (German)
  │
  └─ NO → Industry contains "travel" keywords?
          ├─ YES → TravelPromptBuilder
          └─ NO → PromptBuilder (generic)
```

---

## ⚙️ Key Filters

| Filter | Purpose | Parameters |
|--------|---------|------------|
| `pearblog_prompt_builder_class` | Override builder class | `$class`, `$profile` |
| `pearblog_is_beskidy_content` | Force Beskidy detection | `$is_beskidy`, `$industry`, `$profile` |
| `pearblog_travel_prompt` | Customize travel prompt | `$prompt`, `$topic`, `$profile` |
| `pearblog_beskidy_prompt` | Customize Beskidy prompt | `$prompt`, `$topic`, `$profile` |
| `pearblog_multilang_prompt` | Customize language prompts | `$prompt`, `$topic`, `$profile`, `$language` |

---

## 🎯 Content Quality Standards

### Must Have ✅
- 1,200+ words (Beskidy), 1,000+ words (travel)
- All mandatory sections
- Meta description
- Practical information (parking, time, difficulty)
- Natural accommodation CTA

### Avoid ❌
- Generic AI phrases ("In today's digital age")
- Keyword stuffing (>2% density)
- Missing TL;DR bullet points
- No Plan B alternative
- Spam-like monetization

---

## 📝 TL;DR Template

```html
<h2>TL;DR</h2>
<ul>
  <li>⏱ Czas: [X-Y godzin]</li>
  <li>📈 Trudność: [Łatwy/Średni/Trudny]</li>
  <li>👨‍👩‍👧 Dla kogo: [Target audience]</li>
  <li>📍 Lokalizacja: [Region, województwo]</li>
</ul>
```

---

## 🏔️ Day Plan Template

```html
<h2>Plan dnia</h2>

<h3>Rano (8:00 - 11:00)</h3>
<p>[Morning activities]</p>

<h3>W południe (11:00 - 14:00)</h3>
<p>[Midday activities]</p>

<h3>Popołudnie/wieczór (14:00 - 17:00)</h3>
<p>[Evening activities]</p>

<h3>Plan B (alternatywa)</h3>
<p>Jeśli pogoda jest zła: [alternatives]</p>
```

---

## 🏨 Monetization Template

```html
<h2>Noclegi w okolicy</h2>

<p>W okolicy [location] znajdziesz różne opcje noclegowe:</p>

<ul>
  <li><strong>[Town 1]</strong> - hotele, pensjonaty</li>
  <li><strong>Schroniska górskie</strong> - na szlaku</li>
  <li><strong>[Town 2]</strong> - apartamenty, glampingi</li>
</ul>

<p>Sprawdź dostępne noclegi w okolicy.</p>
```

---

## 🐛 Troubleshooting

### Wrong builder selected?
```php
// Debug current builder
add_action( 'pearblog_pipeline_started', function( $topic, $context ) {
    $builder = PromptBuilderFactory::create( $context->profile );
    error_log( 'Using: ' . get_class( $builder ) );
}, 10, 2 );
```

### Content validation failing?
```php
$validator = new ContentValidator();
$result = $validator->validate( $content, 'beskidy' );
echo $validator->format_report( $result );
```

### Missing sections?
Check that prompt includes all section instructions. Review `BeskidyPromptBuilder::build()` method.

---

## 📚 Full Documentation

See `TRAVEL-CONTENT-ENGINE.md` for complete documentation including:
- Architecture details
- All filters and hooks
- Performance considerations
- Future enhancements
- Detailed examples

---

**Quick Tip:** Start with industry = "Beskidy mountains travel" and language = "pl" for optimal results.

# Poradnik.pro Clean Content System

**Version:** 1.0
**Date:** 2026-05-03
**Status:** ✅ Production Ready

## Overview

The **Poradnik.pro Clean Content System** is a specialized content template designed to create trustworthy, SEO-optimized guide articles that naturally funnel readers to the PT24 marketplace.

### Content Philosophy

This is a **PRE-SELL system**:
- ✔ Portal contentowy (content portal)
- ✔ SEO engine (search traffic)
- ✔ System zaufania (trust building)
- ✔ Pre-sell soft (natural funnel)

### User Flow

```
Google → poradnik
    ↓
czytanie (reading)
    ↓
zaufanie (trust)
    ↓
soft CTA
    ↓
pt24
    ↓
lead
```

---

## Content Structure

### 1. Hero Section

**Tagline:** Sprawdź ceny, porównaj opcje i wybierz najlepsze rozwiązanie
**Subtitle:** Rzetelne poradniki, aktualne koszty i praktyczne wskazówki.

### 2. Categories

- **Remont** (Renovation)
- **Budowa** (Construction)
- **Auto** (Automotive)
- **Finanse** (Finance)

### 3. Article Template

Every article follows this exact structure:

#### **META Description**
```
META: Sprawdź ceny i dowiedz się jak wybrać [topic]. Rzetelne informacje i praktyczne wskazówki.
```

#### **H1 Title**
```
{TEMAT} - ile kosztuje i jak wybrać
```

#### **Introduction (2-3 paragraphs)**
- Krótki opis problemu i dlaczego to ważne
- Co znajdziesz w tym poradniku
- Fokus na wartość dla czytelnika

#### **Section 1: Co to jest {TEMAT}?**
- Wyjaśnienie w prosty sposób
- Podstawowe informacje

#### **Section 2: Ile kosztuje {TEMAT}?**
Koszty zależą od:
- zakresu
- lokalizacji
- jakości

Include pricing ranges and examples.

#### **Section 3: Od czego zależy cena?**
Explain factors:
- materiały (materials)
- robocizna (labor)
- dostępność (availability)
- lokalizacja (location)
- sezon (season)

#### **Section 4: Jak wybrać najlepszą opcję?**
Practical advice:
- porównaj oferty
- sprawdź opinie
- zwróć uwagę na szczegóły
- zadaj kluczowe pytania

#### **Soft CTA Section**
```
Sprawdź dostępne rozwiązania w Twojej okolicy

[CTA Button: Zobacz opcje]
```

**Natural linking phrases:**
- "sprawdź dostępne opcje"
- "zobacz rozwiązania w Twojej okolicy"
- "porównaj oferty lokalnych specjalistów"

#### **FAQ Section**
4-6 questions, such as:
- Czy to drogie?
- Czy warto porównać oferty?
- Jak długo trwa proces?
- Na co zwrócić uwagę?

#### **Conclusion**
- Podsumowanie kluczowych punktów
- Zachęta do świadomego wyboru

---

## SEO Configuration

### Title Format
```
{TEMAT} — ile kosztuje i jak wybrać
```

### Meta Description Format
```
Sprawdź ceny i dowiedz się jak wybrać {TEMAT}. Rzetelne informacje i praktyczne wskazówki.
```

### Internal Linking Strategy

**Natural anchor texts for PT24 links:**
- sprawdź dostępne opcje
- zobacz rozwiązania lokalne
- porównaj dostępność
- znajdź sprawdzonych wykonawców

**Placement:** ONE link naturally integrated in the "Jak wybrać" section or dedicated soft CTA.

---

## UX Principles

### DO ✓
- **Czytelność** - Clear, readable content
- **Wartość** - Provide genuine value
- **Zaufanie** - Build trust with credible information
- **Naturalne CTA** - Soft, helpful call-to-action
- **Praktyczne wskazówki** - Actionable advice

### DON'T ✗
- **Agresywna sprzedaż** - No hard sell
- **Clickbait** - No sensationalism
- **Obietnice bez pokrycia** - No empty promises
- **Zbyt techniczny język** - Keep it accessible
- **Wielokrotne CTA** - Maximum 1 soft CTA per article

---

## Implementation

### 1. Automatic Detection

The system automatically uses this template when the industry contains:
- `poradnik`
- `guide`
- `porady`
- `remont`
- `renovation`
- `budowa`
- `construction`
- `home improvement`
- `home services`

### 2. Manual Activation

**Via Admin Panel:**
1. Go to **Settings Tab**
2. Set **Industry:** `Poradnik` or `Home Services`
3. Save settings

**Via WP-CLI:**
```bash
wp option update pearblog_industry "Poradnik" --allow-root
```

**Via Filter:**
```php
add_filter( 'pearblog_is_poradnik_content', '__return_true' );
```

### 3. Force Template Selection

```php
add_filter( 'pearblog_prompt_builder_class', function( $class, $profile ) {
    return 'PearBlogEngine\Content\PoradnikPromptBuilder';
}, 10, 2 );
```

---

## Content Generation

### Generate Single Article

**Via WP-CLI:**
```bash
wp pearblog generate --topic="Jak wybrać hydraulika" --allow-root
```

**Via Admin Panel:**
1. Go to **Content Engine Tab**
2. Add topic: "Jak wybrać hydraulika"
3. Click **Generate**

### Generate Multiple Articles

**Example topics for Poradnik.pro:**
```bash
wp pearblog add-topic "Ile kosztuje remont łazienki" --allow-root
wp pearblog add-topic "Jak wybrać dobrego mechanika" --allow-root
wp pearblog add-topic "Wymiana instalacji elektrycznej - cena i porady" --allow-root
wp pearblog add-topic "Budowa domu - od czego zacząć" --allow-root
wp pearblog add-topic "Pompa ciepła - koszty i opłacalność" --allow-root
```

### Batch Generation
```bash
wp pearblog generate --count=10 --allow-root
```

---

## Integration with PT24

### Link Structure

Articles naturally link to PT24 marketplace pages:

**Example flow:**
1. Reader searches: "ile kosztuje remont łazienki"
2. Finds article on poradnik.pro
3. Reads valuable content, builds trust
4. Clicks soft CTA: "sprawdź dostępne opcje"
5. Lands on PT24: `/ranking/krakow/remont-lazienki`
6. Submits lead form

### URL Mapping

**Poradnik article:**
```
https://poradnik.pro/ile-kosztuje-remont-lazienki
```

**PT24 landing:**
```
https://pt24.pro/krakow/remont-lazienki
```

**PT24 ranking:**
```
https://pt24.pro/ranking/krakow/remont-lazienki
```

---

## Content Guidelines

### Tone & Voice

**Professional yet approachable:**
- Use "you" form (Ty, Twój)
- Avoid jargon
- Explain complex terms
- Be conversational but credible

**Example:**
```
❌ Wrong: "Implementacja systemu grzewczego wymaga analizy parametrów technicznych"
✅ Right: "Wybór systemu ogrzewania zależy od wielkości domu i Twoich potrzeb"
```

### Length Guidelines

- **Minimum:** 1,200 words
- **Optimal:** 1,500-1,800 words
- **Maximum:** 2,500 words

### Section Length

- **Introduction:** 150-200 words
- **Each main section:** 200-400 words
- **FAQ:** 50-100 words per question
- **Conclusion:** 100-150 words

---

## Quality Checklist

Before publishing, verify:

- [ ] META description on first line
- [ ] H1 title includes "ile kosztuje i jak wybrać"
- [ ] Introduction explains the problem
- [ ] All 4 main sections present
- [ ] Cost information included
- [ ] Practical advice provided
- [ ] ONE soft CTA included
- [ ] 4-6 FAQ questions
- [ ] Conclusion summarizes key points
- [ ] No aggressive sales language
- [ ] No multiple CTAs
- [ ] Natural PT24 link with good anchor text
- [ ] 1,200+ words
- [ ] Polish language correct
- [ ] Readable and accessible

---

## Examples

### Example 1: Renovation Topic

**Topic:** "Remont łazienki"

**Generated Title:**
```
Remont łazienki - ile kosztuje i jak wybrać wykonawcę
```

**Cost Section:**
```
Koszt remontu łazienki zależy od wielu czynników. Podstawowy remont małej
łazienki (4-6m²) to wydatek od 8,000 do 15,000 zł. Kompleksowa przebudowa
może kosztować 20,000-40,000 zł lub więcej.

[Table with pricing breakdown]
```

**Soft CTA:**
```
Jeśli szukasz sprawdzonego wykonawcy w Twojej okolicy, możesz [sprawdź
dostępne opcje](pt24-link) i porównać oferty lokalnych firm remontowych.
```

### Example 2: Automotive Topic

**Topic:** "Przegląd techniczny samochodu"

**Generated Title:**
```
Przegląd techniczny samochodu - ile kosztuje i jak się przygotować
```

**Advice Section:**
```
Przed przeglądem warto:
- Sprawdzić światła i sygnały
- Skontrolować poziom płynów
- Przetestować hamulce
- Sprawdzić stan opon
```

---

## Testing

### Test Content Generation

```bash
# Test with Poradnik industry
wp option update pearblog_industry "Poradnik" --allow-root
wp pearblog generate --topic="Test remont mieszkania" --allow-root

# Verify output
wp post get <POST_ID> --field=post_content --allow-root
```

### Verify Template Selection

```bash
# Check which builder is being used
wp eval "
\$profile = new \PearBlogEngine\Tenant\SiteProfile('Poradnik', 'professional', 'polish', 'adsense', 1);
\$builder = \PearBlogEngine\Content\PromptBuilderFactory::create(\$profile);
echo get_class(\$builder);
" --allow-root

# Expected output: PearBlogEngine\Content\PoradnikPromptBuilder
```

---

## Performance Metrics

Track these KPIs:

### Content Metrics
- Articles published per day
- Average word count
- Time to generate

### Traffic Metrics
- Organic search traffic
- Bounce rate (target: < 60%)
- Time on page (target: > 2 minutes)
- Pages per session

### Conversion Metrics
- Click-through rate to PT24 (target: 5-10%)
- Lead generation from PT24
- Cost per lead

### SEO Metrics
- Keyword rankings
- Featured snippets
- Domain authority growth

---

## Troubleshooting

### Issue: Wrong Template Being Used

**Check industry setting:**
```bash
wp option get pearblog_industry --allow-root
```

**Force Poradnik template:**
```php
add_filter( 'pearblog_is_poradnik_content', '__return_true' );
```

### Issue: Content Too Generic

The PoradnikPromptBuilder has very specific instructions. If content is generic:

1. Verify the correct builder is being used
2. Check if custom filters are interfering
3. Review the OpenAI API response

### Issue: No PT24 Links

Ensure the soft CTA section is being generated. The builder explicitly instructs to include linking placeholders.

---

## Advanced Customization

### Customize Prompt

```php
add_filter( 'pearblog_poradnik_prompt', function( $prompt, $topic, $profile ) {
    // Add custom instructions
    $prompt .= "\n\nADDITIONAL REQUIREMENT: Focus on eco-friendly solutions.\n";
    return $prompt;
}, 10, 3 );
```

### Customize Structure

```php
add_filter( 'pearblog_prompt_builder_class', function( $class, $profile ) {
    // Use custom extended builder
    return 'MyPlugin\CustomPoradnikBuilder';
}, 10, 2 );
```

---

## Related Documentation

- [SYSTEM-ARCHITECTURE-MAP.md](./SYSTEM-ARCHITECTURE-MAP.md) - System overview
- [PT24-MULTIVERTICAL-V4-STATUS.md](./PT24-MULTIVERTICAL-V4-STATUS.md) - PT24 marketplace
- [COMPLETE-STEP-BY-STEP-GUIDE.md](./COMPLETE-STEP-BY-STEP-GUIDE.md) - Operational guide
- [API-DOCUMENTATION.md](./API-DOCUMENTATION.md) - API reference

---

## Support

For issues or questions:
- GitHub: https://github.com/AndyPearman89/PearBlog-Engine-/issues
- Check logs: `/wp-content/pearblog-engine.log`

---

**Status:** ✅ **PRODUCTION READY**
**Last Updated:** 2026-05-03

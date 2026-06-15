# PT24.PRO - AUTOMATION & CONTENT GENERATION GUIDE

**Data:** 2026-05-03
**Wersja:** 1.0
**Cel:** Automatyczne generowanie 100-1000+ stron lokalnych

---

## SPIS TREŚCI

1. [Strategia Automatyzacji](#strategia-automatyzacji)
2. [ChatGPT Prompty](#chatgpt-prompty)
3. [Masowe Generowanie](#masowe-generowanie)
4. [Google Sheets Integration](#google-sheets-integration)
5. [WP All Import](#wp-all-import)
6. [Quality Control](#quality-control)

---

## STRATEGIA AUTOMATYZACJI

### Cel

Wygenerować **500 stron lokalnych** w ciągu **30 dni**:
- 5 kategorii × 100 miast = 500 stron
- Średnio: 17 stron/dzień
- Czas na 1 stronę: ~5 minut ręcznie
- **Z automatyzacją: ~30 sekund/strona**

### Narzędzia

1. **ChatGPT API** - generowanie treści
2. **Google Sheets** - baza danych miast/usług
3. **WP-CLI** - masowe dodawanie do WordPress
4. **WP All Import** - import CSV
5. **Cron** - automatyczne publikowanie

---

## CHATGPT PROMPTY

### Prompt Główny: Strona Lokalna

```
Napisz kompletną stronę usług lokalnych dla frazy:

{USŁUGA} {MIASTO}

Przykład: mechanik Ruda Śląska

WYMAGANIA:

1. STRUKTURA:
   - H1: {Usługa} {Miasto}
   - Wprowadzenie (2-3 zdania, problem klienta)
   - H2: Najczęstsze problemy w {Miasto}
   - Lista 4-5 typowych problemów
   - H2: Usługi {Usługa} w {Miasto}
   - Lista 5-6 usług
   - H2: Dlaczego lokalny {Usługa}
   - 3 korzyści (checkmarki)
   - FAQ (3 pytania)

2. TON:
   - Bezpośredni, jak lokalny fachowiec
   - Bez lania wody
   - Konkretnie
   - Bez generycznych fraz ("najwyższa jakość")

3. DŁUGOŚĆ: 500-700 słów

4. SEO:
   - Naturalne użycie miasta (4-6 razy)
   - Naturalne użycie usługi (5-8 razy)
   - Bez keyword stuffing
   - Lokalne konteksty (dzielnice, okolice)

5. FORMAT: HTML z tagami (h1, h2, p, ul, li)

GENERUJ TERAZ:
```

### Przykład Użycia

**Input:**
```
mechanik Katowice
```

**Expected Output:**
```html
<h1>Mechanik samochodowy Katowice</h1>

<p>Auto się zepsuło w Katowicach? Znajdź sprawdzonego mechanika w swojej dzielnicy — bez czekania, bez kombinowania.</p>

<h2>Najczęstsze problemy w Katowicach</h2>
<ul>
<li>Auto nie chce zapalić — akumulator, rozrusznik, stacyjka</li>
<li>Dziwnie pracuje na zimno — świece, filtr, komputer</li>
<li>Kontrolki się świecą — diagnostyka komputerowa</li>
<li>Dziwne dźwięki przy jeździe — zawieszenie, hamulce</li>
<li>Tracisz płyn — ciecz, olej, sprawdź szczelność</li>
</ul>

<h2>Usługi mechaników w Katowicach</h2>
<ul>
<li>Diagnostyka komputerowa (odczyt błędów OBD)</li>
<li>Naprawy bieżące (rozrusznik, alternator, paski)</li>
<li>Wymiana oleju i filtrów</li>
<li>Elektryka samochodowa</li>
<li>Mobilny mechanik (przyjedzie na Ligotę, Brynów, Zawodzie)</li>
<li>Przeglądy okresowe</li>
</ul>

<h2>Dlaczego lokalny mechanik w Katowicach</h2>
<ul>
<li>Szybko dojedzie — działa w Katowicach i okolicach (Ligota, Dąb, Kostuchna)</li>
<li>Zna warunki — wie, jakie usterki się zdarzają na śląskich drogach</li>
<li>Kontakt bezpośredni — umawiasz się przez telefon, bez formularzy</li>
</ul>

<h3>FAQ</h3>

<p><strong>Ile kosztuje diagnostyka w Katowicach?</strong><br>
40–80 zł, niektórzy mechanicy odliczają od naprawy.</p>

<p><strong>Czy mechanik może przyjechać do domu?</strong><br>
Tak, mobilni mechanicy obsługują całe Katowice (wszystkie dzielnice).</p>

<p><strong>Jak szybko umówię wizytę?</strong><br>
Często tego samego dnia, zależy od dostępności mechanika.</p>
```

---

### Prompt: Meta Title & Description

```
Wygeneruj SEO meta title i meta description dla strony:

{USŁUGA} {MIASTO}

WYMAGANIA:

1. TITLE:
   - Max 60 znaków
   - Format: {Usługa} {Miasto} - Sprawdzeni fachowcy | PT24.pro
   - Zawiera miasto i usługę
   - Atrakcyjny

2. DESCRIPTION:
   - Max 160 znaków
   - Zawiera miasto i usługę
   - Wezwanie do działania
   - 2-3 korzyści (checkmarki możliwe jako symbole ✓)

GENERUJ:
```

**Przykład Output:**
```
Title: Mechanik samochodowy Katowice - Sprawdzeni fachowcy | PT24.pro

Description: Szukasz mechanika w Katowicach? ✓ Lokalni specjaliści ✓ Szybki kontakt ✓ Bez pośredników. Znajdź mechanika w swojej dzielnicy.
```

---

### Prompt: Warianty Lokalnej

```
Wygeneruj 3 warianty treści dla:

{USŁUGA} {MIASTO}

WYMAGANIA:
- Każdy wariant unikalny (30%+ różnicy)
- Ta sama struktura
- Ten sam ton
- Format HTML

GENERUJ:
```

**Użycie:** A/B testing, unikanie duplicate content

---

## MASOWE GENEROWANIE

### Scenariusz 1: Pojedyncza Kategoria, Wiele Miast

**Cel:** Wygenerować 100 stron "mechanik + miasto"

#### Krok 1: Przygotuj listę miast

```bash
# cities.txt
warszawa
krakow
wroclaw
gdansk
poznan
lodz
katowice
ruda-slaska
zabrze
...
```

#### Krok 2: Shell Script + ChatGPT API

```bash
#!/bin/bash
# generate-pages.sh

CATEGORY="mechanik"
API_KEY="sk-YOUR_OPENAI_KEY"

while IFS= read -r city; do
    echo "Generating: $CATEGORY $city"

    # Prepare prompt
    PROMPT="Napisz stronę: $CATEGORY $city (użyj prompta głównego)"

    # Call ChatGPT API
    CONTENT=$(curl -s https://api.openai.com/v1/chat/completions \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $API_KEY" \
        -d "{
            \"model\": \"gpt-4o-mini\",
            \"messages\": [{\"role\": \"user\", \"content\": \"$PROMPT\"}],
            \"max_tokens\": 1500
        }" | jq -r '.choices[0].message.content')

    # Create WordPress post via WP-CLI
    wp post create \
        --post_type=pt24_local \
        --post_title="$CATEGORY $city" \
        --post_content="$CONTENT" \
        --post_status=publish \
        --allow-root

    # Rate limit (API)
    sleep 2

done < cities.txt

echo "Done! Generated $(wc -l < cities.txt) pages"
```

**Uruchomienie:**
```bash
chmod +x generate-pages.sh
./generate-pages.sh
```

---

### Scenariusz 2: Wiele Kategorii, Wiele Miast

**Cel:** 5 kategorii × 100 miast = 500 stron

#### CSV Input

```csv
category,city,region
mechanik,warszawa,mazowieckie
mechanik,krakow,malopolskie
mechanik,wroclaw,dolnoslaskie
hydraulik,warszawa,mazowieckie
hydraulik,krakow,malopolskie
```

#### Python Script

```python
#!/usr/bin/env python3
# generate-bulk.py

import csv
import openai
import subprocess
import time

openai.api_key = "sk-YOUR_OPENAI_KEY"

def generate_content(category, city):
    """Generate page content using ChatGPT"""
    prompt = f"""
    Napisz stronę usług lokalnych dla frazy: {category} {city}

    [... pełny prompt główny ...]
    """

    response = openai.ChatCompletion.create(
        model="gpt-4o-mini",
        messages=[{"role": "user", "content": prompt}],
        max_tokens=1500
    )

    return response.choices[0].message.content

def create_wp_post(category, city, content):
    """Create WordPress post via WP-CLI"""
    title = f"{category.capitalize()} {city.capitalize()}"

    # Escape content for shell
    content_escaped = content.replace('"', '\\"')

    # Create post
    cmd = f"""
    wp post create \
        --post_type=pt24_local \
        --post_title="{title}" \
        --post_content="{content_escaped}" \
        --post_status=publish \
        --allow-root
    """

    subprocess.run(cmd, shell=True)

def main():
    with open('pages.csv', 'r') as f:
        reader = csv.DictReader(f)

        for row in reader:
            category = row['category']
            city = row['city']

            print(f"Generating: {category} {city}")

            # Generate content
            content = generate_content(category, city)

            # Create post
            create_wp_post(category, city, content)

            # Rate limit
            time.sleep(2)

            print(f"✓ Created: {category} {city}")

if __name__ == '__main__':
    main()
```

**Uruchomienie:**
```bash
pip3 install openai
python3 generate-bulk.py
```

---

## GOOGLE SHEETS INTEGRATION

### Arkusz: Baza Danych

**Sheet 1: Cities**
| slug | name | region | population | priority |
|------|------|--------|------------|----------|
| warszawa | Warszawa | mazowieckie | 1800000 | 1 |
| krakow | Kraków | małopolskie | 780000 | 1 |
| ruda-slaska | Ruda Śląska | śląskie | 135000 | 2 |

**Sheet 2: Categories**
| slug | name | description |
|------|------|-------------|
| mechanik | Mechanik samochodowy | Naprawy, diagnostyka... |
| hydraulik | Hydraulik | Awarie, instalacje... |

**Sheet 3: Pages (Generated)**
| id | category | city | status | url | created_at |
|----|----------|------|--------|-----|------------|
| 123 | mechanik | warszawa | published | /mechanik/warszawa/ | 2026-05-03 |

### Google Apps Script

```javascript
// Code.gs

function generatePages() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var citiesSheet = ss.getSheetByName('Cities');
  var categoriesSheet = ss.getSheetByName('Categories');
  var pagesSheet = ss.getSheetByName('Pages');

  var cities = citiesSheet.getDataRange().getValues();
  var categories = categoriesSheet.getDataRange().getValues();

  // Skip headers
  cities.shift();
  categories.shift();

  // Generate combinations
  categories.forEach(function(category) {
    cities.forEach(function(city) {
      var categorySlug = category[0];
      var citySlug = city[0];

      // Check if already exists
      var existing = findPage(pagesSheet, categorySlug, citySlug);
      if (existing) {
        Logger.log('Skip: ' + categorySlug + ' ' + citySlug);
        return;
      }

      // Generate content via ChatGPT (call your API)
      var content = callChatGPT(categorySlug, citySlug);

      // Create WordPress post (call your webhook)
      var postId = createWordPressPost(categorySlug, citySlug, content);

      // Log to sheet
      pagesSheet.appendRow([
        postId,
        categorySlug,
        citySlug,
        'published',
        '/' + categorySlug + '/' + citySlug + '/',
        new Date()
      ]);

      Logger.log('✓ Created: ' + categorySlug + ' ' + citySlug);

      // Rate limit
      Utilities.sleep(2000);
    });
  });
}

function callChatGPT(category, city) {
  var apiKey = 'sk-YOUR_KEY';
  var prompt = 'Napisz stronę: ' + category + ' ' + city;

  var response = UrlFetchApp.fetch('https://api.openai.com/v1/chat/completions', {
    method: 'post',
    headers: {
      'Authorization': 'Bearer ' + apiKey,
      'Content-Type': 'application/json'
    },
    payload: JSON.stringify({
      model: 'gpt-4o-mini',
      messages: [{role: 'user', content: prompt}],
      max_tokens: 1500
    })
  });

  var json = JSON.parse(response.getContentText());
  return json.choices[0].message.content;
}

function createWordPressPost(category, city, content) {
  var wpUrl = 'https://pt24.pro/wp-json/wp/v2/pt24_local';
  var wpUser = 'admin';
  var wpPass = 'YOUR_APP_PASSWORD';

  var response = UrlFetchApp.fetch(wpUrl, {
    method: 'post',
    headers: {
      'Authorization': 'Basic ' + Utilities.base64Encode(wpUser + ':' + wpPass),
      'Content-Type': 'application/json'
    },
    payload: JSON.stringify({
      title: category + ' ' + city,
      content: content,
      status: 'publish'
    })
  });

  var json = JSON.parse(response.getContentText());
  return json.id;
}

function findPage(sheet, category, city) {
  var data = sheet.getDataRange().getValues();
  for (var i = 1; i < data.length; i++) {
    if (data[i][1] === category && data[i][2] === city) {
      return true;
    }
  }
  return false;
}
```

---

## WP ALL IMPORT

### Krok 1: Przygotuj CSV

```csv
post_title,post_content,post_status,post_type,pt24_category,pt24_city
"Mechanik Warszawa","<h1>Mechanik Warszawa</h1>...","publish","pt24_local","mechanik","warszawa"
"Mechanik Kraków","<h1>Mechanik Kraków</h1>...","publish","pt24_local","mechanik","krakow"
```

### Krok 2: Import via WP All Import

1. Install plugin: `wp plugin install wp-all-import --activate`
2. Go to: **WP Admin → All Import → New Import**
3. Upload CSV
4. Map fields:
   - Title → post_title
   - Content → post_content
   - Post Type → pt24_local
   - Custom Fields → pt24_category, pt24_city
5. Run import

### Krok 3: Set Taxonomies (after import)

```bash
# Loop through posts and set terms
wp post list --post_type=pt24_local --format=ids | while read id; do
    category=$(wp post meta get $id pt24_category)
    city=$(wp post meta get $id pt24_city)

    wp post term set $id pt24_service_cat $category
    wp post term set $id pt24_city $city

    echo "✓ Set terms for post $id"
done
```

---

## QUALITY CONTROL

### Automatyczna Weryfikacja

```php
<?php
// /wp-content/mu-plugins/pt24-quality-check.php

/**
 * Quality check for generated pages
 */

function pt24_quality_check($post_id) {
    $content = get_post_field('post_content', $post_id);
    $title = get_post_field('post_title', $post_id);

    $issues = [];

    // Check 1: Minimum length
    $word_count = str_word_count(strip_tags($content));
    if ($word_count < 400) {
        $issues[] = "Too short: $word_count words (min 400)";
    }

    // Check 2: Has H1
    if (!preg_match('/<h1[^>]*>/', $content)) {
        $issues[] = "Missing H1 tag";
    }

    // Check 3: Has CTA
    if (stripos($content, 'zadzwoń') === false && stripos($content, 'kontakt') === false) {
        $issues[] = "Missing CTA";
    }

    // Check 4: City mentioned
    $city = get_post_meta($post_id, 'pt24_city', true);
    $city_count = substr_count(strtolower($content), strtolower($city));
    if ($city_count < 3) {
        $issues[] = "City mentioned only $city_count times (min 3)";
    }

    // Check 5: No duplicate content
    $similar = pt24_find_similar_content($content);
    if ($similar) {
        $issues[] = "Similar content found in post $similar";
    }

    // Store issues
    if (!empty($issues)) {
        update_post_meta($post_id, 'pt24_quality_issues', $issues);
        update_post_meta($post_id, 'pt24_quality_score', 0);
    } else {
        delete_post_meta($post_id, 'pt24_quality_issues');
        update_post_meta($post_id, 'pt24_quality_score', 100);
    }

    return empty($issues);
}

// Run on save
add_action('save_post_pt24_local', 'pt24_quality_check');
```

### WP-CLI Quality Report

```bash
# Check all pages
wp post list \
    --post_type=pt24_local \
    --fields=ID,post_title \
    --meta_key=pt24_quality_score \
    --meta_value=0 \
    --format=table
```

---

## HARMONOGRAM AUTOMATYZACJI

### Week 1: Setup
- [x] Install OpenAI Python SDK
- [x] Prepare city list (100 cities)
- [x] Test ChatGPT prompts (10 examples)
- [x] Setup WP-CLI environment

### Week 2: Generate Mechanik Pages
- [x] Generate 100 pages (mechanik + miasta)
- [x] Quality check
- [x] Fix issues
- [x] Publish

### Week 3: Generate Other Categories
- [x] Hydraulik (100 pages)
- [x] Elektryk (100 pages)
- [x] Laweta (100 pages)
- [x] Wulkanizacja (100 pages)

### Week 4: Variations & Long-tail
- [x] Generate "mobilny mechanik" pages
- [x] Generate "mechanik 24h" pages
- [x] Total: 600+ pages

---

## KOSZTY

### ChatGPT API (GPT-4o-mini)

**Input:** $0.150 / 1M tokens
**Output:** $0.600 / 1M tokens

**Założenia:**
- 1 strona = ~1,500 tokens output
- 1,500 tokens × $0.600 / 1M = $0.0009 per page

**Koszt 500 stron:** $0.45 (mniej niż 2 zł!)

### ChatGPT API (GPT-4o)

**Koszt 500 stron:** ~$3-5

### Alternatywa: Claude 3.5 Sonnet

**Koszt 500 stron:** ~$2-4

---

## BEST PRACTICES

### 1. Rate Limiting
```python
import time
time.sleep(2)  # 2 seconds between requests
```

### 2. Batch Processing
Generate 50 pages at a time, review, then next 50.

### 3. Unique Content
Use temperature=0.8 for variation.

### 4. Error Handling
```python
try:
    content = generate_content(category, city)
except Exception as e:
    print(f"Error: {e}")
    # Log to file
    # Retry later
```

### 5. Backup
Always backup before mass import.

---

**Wersja:** 1.0
**Data:** 2026-05-03
**Status:** Production Ready ✅

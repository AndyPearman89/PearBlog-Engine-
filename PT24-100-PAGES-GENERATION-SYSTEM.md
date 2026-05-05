# 🚀 PT24.PRO - SYSTEM GENEROWANIA 100+ STRON LOKALNYCH SEO

**Wersja:** 1.0
**Data:** 2026-05-04
**Status:** Production Ready

---

## 🎯 CEL SYSTEMU

Wygenerować **100+ stron lokalnych SEO** dla platformy PT24.pro w celu:

- ✅ **Ruchu z Google** - organiczne pozycje dla fraz lokalnych
- ✅ **Telefonów od klientów** - bezpośrednie kontakty
- ✅ **Sprzedaży profili firm** - monetyzacja platform

---

## 📊 STRATEGIA: 5 USŁUG × 25 MIAST = 125 STRON

### Usługi (5)
1. **Mechanik** - diagnostyka, naprawy, mobilny serwis
2. **Hydraulik** - awarie 24h, remonty, instalacje
3. **Elektryk** - elektryka samochodowa, stacyjki, alarmy
4. **Laweta** - pomoc drogowa 24h
5. **Wulkanizacja** - opony, felgi, sezonowa wymiana

### Miasta (Top 25)
1. Warszawa
2. Kraków
3. Łódź
4. Wrocław
5. Poznań
6. Gdańsk
7. Szczecin
8. Bydgoszcz
9. Lublin
10. Katowice
11. Białystok
12. Gdynia
13. Częstochowa
14. Radom
15. Sosnowiec
16. Toruń
17. Kielce
18. Rzeszów
19. Gliwice
20. Zabrze
21. Ruda Śląska
22. Bytom
23. Chorzów
24. Tychy
25. Dąbrowa Górnicza

---

## 🔧 KROK 1: PRZYGOTOWANIE CSV

### Format pliku: `pt24-landings-100.csv`

```csv
service,city,service_name,city_name
mechanik,warszawa,Mechanik samochodowy,Warszawa
mechanik,krakow,Mechanik samochodowy,Kraków
mechanik,lodz,Mechanik samochodowy,Łódź
hydraulik,warszawa,Hydraulik,Warszawa
hydraulik,krakow,Hydraulik,Kraków
elektryk,warszawa,Elektryk samochodowy,Warszawa
laweta,warszawa,Laweta,Warszawa
wulkanizacja,warszawa,Wulkanizacja,Warszawa
```

### Generowanie automatyczne

```bash
# Skrypt generujący CSV
cat > generate-pt24-csv.sh << 'EOF'
#!/bin/bash

echo "service,city,service_name,city_name" > pt24-landings-100.csv

SERVICES=(
  "mechanik:Mechanik samochodowy"
  "hydraulik:Hydraulik"
  "elektryk:Elektryk samochodowy"
  "laweta:Laweta"
  "wulkanizacja:Wulkanizacja"
)

CITIES=(
  "warszawa:Warszawa"
  "krakow:Kraków"
  "lodz:Łódź"
  "wroclaw:Wrocław"
  "poznan:Poznań"
  "gdansk:Gdańsk"
  "szczecin:Szczecin"
  "bydgoszcz:Bydgoszcz"
  "lublin:Lublin"
  "katowice:Katowice"
  "bialystok:Białystok"
  "gdynia:Gdynia"
  "czestochowa:Częstochowa"
  "radom:Radom"
  "sosnowiec:Sosnowiec"
  "torun:Toruń"
  "kielce:Kielce"
  "rzeszow:Rzeszów"
  "gliwice:Gliwice"
  "zabrze:Zabrze"
  "ruda-slaska:Ruda Śląska"
  "bytom:Bytom"
  "chorzow:Chorzów"
  "tychy:Tychy"
  "dabrowa-gornicza:Dąbrowa Górnicza"
)

for service_pair in "${SERVICES[@]}"; do
  service_slug="${service_pair%%:*}"
  service_name="${service_pair##*:}"

  for city_pair in "${CITIES[@]}"; do
    city_slug="${city_pair%%:*}"
    city_name="${city_pair##*:}"

    echo "$service_slug,$city_slug,$service_name,$city_name" >> pt24-landings-100.csv
  done
done

echo "✅ Generated pt24-landings-100.csv (125 combinations)"
EOF

chmod +x generate-pt24-csv.sh
./generate-pt24-csv.sh
```

---

## 🤖 KROK 2: PROMPT AI (GPT-4o-mini)

### Szablon promptu

```
Napisz stronę usług lokalnych dla frazy: [USŁUGA] [MIASTO]

Wymagania:
- Minimum 600 słów
- Nagłówek H1: "[USŁUGA] [MIASTO]" (np. "Mechanik samochodowy Warszawa")
- Sekcja problemów klienta (3-4 typowe problemy)
- Lista usług (5-7 głównych usług)
- Sekcja "Dlaczego warto" (4 powody)
- CTA z numerem telefonu
- FAQ (3 pytania z odpowiedziami)
- Naturalne użycie miasta w tekście (min. 5 razy)
- Styl: prosty, konkretny, jak lokalny fachowiec
- Bez sprzedażowego gadania
- Konkretne informacje

Format:
- Zwróć czysty HTML
- Bez tagów <html>, <body>, <head>
- Tylko treść do wklejenia

Przykład struktury:

<h1>Mechanik samochodowy Warszawa</h1>

<div class="intro">
<p>Auto się zepsuło w Warszawie? Szukasz sprawdzonego mechanika?...</p>
</div>

<h2>Najczęstsze problemy</h2>
<ul>
  <li>Auto nie odpala rano</li>
  <li>Dziwne dźwięki przy jeździe</li>
  <li>Kontrolka check engine</li>
</ul>

<h2>Usługi mechanika w Warszawie</h2>
<ul>
  <li>Diagnostyka komputerowa</li>
  <li>Naprawy mechaniczne</li>
  <li>...</li>
</ul>

<h2>Dlaczego warto</h2>
<ul>
  <li>Lokalni specjaliści - znają Warszawę</li>
  <li>...</li>
</ul>

<div class="cta">
  <p><strong>Potrzebujesz mechanika? Zadzwoń:</strong></p>
  <a href="tel:+48123456789" class="phone-cta">📞 +48 123 456 789</a>
</div>

<h2>FAQ</h2>

<h3>Ile kosztuje diagnostyka w Warszawie?</h3>
<p>Od 50 do 100 zł, zależy od warsztatu...</p>

<h3>Czy mechanik może przyjechać do domu?</h3>
<p>Tak, mobilni mechanicy obsługują całą Warszawę...</p>

<h3>Jak szybko umówię wizytę?</h3>
<p>Często tego samego dnia...</p>
```

---

## ⚙️ KROK 3: GENEROWANIE TREŚCI

### Opcja A: WP-CLI + OpenAI API (Zautomatyzowane)

```bash
# Generuj strony z AI
wp pt24 generate-pages --batch=125 --with-ai

# Tylko dla konkretnej usługi
wp pt24 generate-pages --service=mechanik --with-ai

# Tylko dla konkretnego miasta
wp pt24 generate-pages --city=warszawa --with-ai
```

### Opcja B: Python Script + OpenAI

```python
#!/usr/bin/env python3
"""
PT24 Landing Page Generator
Generuje treści lokalnych stron SEO używając OpenAI API
"""

import csv
import os
from openai import OpenAI

client = OpenAI(api_key=os.getenv('OPENAI_API_KEY'))

def generate_content(service, city, service_name, city_name):
    prompt = f"""Napisz stronę usług lokalnych dla frazy: {service_name} {city_name}

Wymagania:
- Minimum 600 słów
- H1: "{service_name} {city_name}"
- Sekcja problemów klienta (3-4)
- Lista usług (5-7)
- Sekcja "Dlaczego warto" (4 powody)
- CTA z telefonem
- FAQ (3 pytania)
- Naturalne użycie miasta (min. 5 razy)
- Styl: prosty, konkretny

Zwróć czysty HTML bez <html>, <body>, <head>."""

    response = client.chat.completions.create(
        model="gpt-4o-mini",
        messages=[{"role": "user", "content": prompt}],
        temperature=0.7,
        max_tokens=2000
    )

    return response.choices[0].message.content

# Wczytaj CSV
with open('pt24-landings-100.csv', 'r', encoding='utf-8') as infile:
    reader = csv.DictReader(infile)

    # Otwórz output CSV z treścią
    with open('pt24-landings-with-content.csv', 'w', encoding='utf-8', newline='') as outfile:
        fieldnames = ['service', 'city', 'service_name', 'city_name', 'title', 'slug', 'content']
        writer = csv.DictWriter(outfile, fieldnames=fieldnames)
        writer.writeheader()

        for i, row in enumerate(reader, 1):
            print(f"[{i}/125] Generating: {row['service_name']} {row['city_name']}...")

            content = generate_content(
                row['service'],
                row['city'],
                row['service_name'],
                row['city_name']
            )

            writer.writerow({
                'service': row['service'],
                'city': row['city'],
                'service_name': row['service_name'],
                'city_name': row['city_name'],
                'title': f"{row['service_name']} {row['city_name']}",
                'slug': f"{row['service']}-{row['city']}",
                'content': content
            })

            print(f"✅ Done")

print("\n✅ All 125 pages generated!")
print("Output: pt24-landings-with-content.csv")
```

---

## 📥 KROK 4: IMPORT DO WORDPRESS

### Metoda 1: WP-CLI (Najszybsza)

```bash
# Import z CSV używając WP-CLI
wp pt24 import-csv pt24-landings-with-content.csv

# Lub bezpośrednio z generate
wp pt24 generate-pages --batch=125 --publish
```

### Metoda 2: WP All Import Plugin

1. **Zainstaluj plugin:**
```bash
wp plugin install wp-all-import --activate
```

2. **Import przez admin panel:**
   - WP Admin → All Import → New Import
   - Upload `pt24-landings-with-content.csv`
   - Select "New Post Type" → `pt24_landing`

3. **Mapowanie kolumn:**
```
CSV Column        →  WordPress Field
-----------------------------------------
title             →  Post Title
slug              →  Post Name (URL)
content           →  Post Content
service           →  Meta: pt24_service
city              →  Meta: pt24_city
service_name      →  Meta: pt24_service_name
city_name         →  Meta: pt24_city_name
```

4. **Ustawienia:**
   - Status: Published
   - Author: Admin
   - Date: Current date
   - Enable duplicate detection by slug

5. **Run Import** - Import 125 posts

---

## 🔗 KROK 5: STRUKTURA URL

### Permalink Structure

Automatycznie tworzone jako:
```
/mechanik-warszawa/
/mechanik-krakow/
/hydraulik-warszawa/
/elektryk-katowice/
/laweta-gdansk/
```

### Flush Rewrite Rules

```bash
wp rewrite flush
wp pt24 flush-rewrites
```

---

## 🔗 KROK 6: LINKOWANIE WEWNĘTRZNE

### Auto-Linking (w template)

Template: `single-pt24_landing.php` automatycznie dodaje:

1. **Link do usługi głównej:**
```php
<a href="/<?php echo $service; ?>/">Wszystkie usługi: <?php echo $service_name; ?></a>
```

2. **Linki do innych miast (tej samej usługi):**
```php
$other_cities = get_posts([
    'post_type' => 'pt24_landing',
    'meta_query' => [
        ['key' => 'pt24_service', 'value' => $current_service]
    ],
    'posts_per_page' => 5
]);

foreach ($other_cities as $city_post) {
    echo '<a href="' . get_permalink($city_post) . '">' . $city_post->post_title . '</a>';
}
```

3. **CTA z telefonem:**
```php
<div class="pt24-cta">
    <p><strong>Szukasz fachowca? Zadzwoń teraz:</strong></p>
    <a href="tel:+48123456789" class="pt24-phone-btn">
        📞 +48 123 456 789
    </a>
</div>
```

---

## 📈 KROK 7: SKALOWANIE (DO 500+ STRON)

### Rozszerzenie 1: Warianty usług

Dla każdego miasta dodaj warianty:

```csv
mechanik-warszawa
mechanik-24h-warszawa
mobilny-mechanik-warszawa
mechanik-warszawa-mokotow
mechanik-warszawa-srodmiescie
```

**Mnoży strony × 3-5**

### Rozszerzenie 2: Długi ogon (Long-tail)

```
mechanik-warszawa-awaria
mechanik-warszawa-nie-odpala
mechanik-warszawa-kontrolka
mechanik-warszawa-stuk-silnika
```

### Rozszerzenie 3: Więcej miast (100+)

Dodaj mniejsze miasta:
- Wszystkie miasta >50k mieszkańców
- Dzielnice dużych miast

### Skrypt skalowania

```bash
# Generate 500 pages with variants
wp pt24 generate-pages --batch=500 --variants=3
```

---

## 🎯 KROK 8: OSIĄGNIĘCIE CELÓW

### 1. Ruch z Google

**Strategia SEO:**
- ✅ Unikalne treści 600+ słów per strona
- ✅ Naturalne użycie frazy lokalne
- ✅ H1, H2, H3 struktura
- ✅ FAQ (schema.org ready)
- ✅ Internal linking
- ✅ Mobile-friendly

**Oczekiwane wyniki:**
- Pozycje 1-10 w Google: 20-30% stron
- Pozycje 11-50: kolejne 40-50%
- Ruch: 5000-15000 odwiedzin/miesiąc

### 2. Telefony od klientów

**Konwersja:**
- ✅ Widoczny numer telefonu w hero
- ✅ CTA "Zadzwoń teraz" 2-3× na stronie
- ✅ Clickable `tel:` links
- ✅ Trust signals (lokalni, 24/7, sprawdzeni)

**Oczekiwane wyniki:**
- Conversion rate: 2-5% (odwiedziny → telefon)
- 100-750 telefonów/miesiąc

### 3. Sprzedaż profili firm

**Monetyzacja:**
- ✅ CTA "Dodaj swoją firmę" na każdej stronie
- ✅ Link do strony rejestracji firm
- ✅ Wyświetlanie lokalnych firm (gdy są)
- ✅ "Brak Twojej firmy? Dodaj się!" message

**Oczekiwane wyniki:**
- 10-30 rejestracji firm/miesiąc
- MRR: 790-2370 zł (przy 79 zł/firma)

---

## 📊 MONITORING I ANALYTICS

### Google Search Console

```bash
# Dodaj wszystkie strony do GSC
wp pt24 submit-sitemap
```

### Analytics Setup

```php
// Google Analytics 4 tracking
add_action('wp_footer', function() {
    if (is_singular('pt24_landing')) {
        ?>
        <script>
        gtag('event', 'page_view', {
            'page_type': 'pt24_landing',
            'service': '<?php echo get_post_meta(get_the_ID(), 'pt24_service', true); ?>',
            'city': '<?php echo get_post_meta(get_the_ID(), 'pt24_city', true); ?>'
        });
        </script>
        <?php
    }
});
```

### Phone Click Tracking

```javascript
// Track phone clicks
document.querySelectorAll('a[href^="tel:"]').forEach(link => {
    link.addEventListener('click', () => {
        gtag('event', 'phone_click', {
            'service': '...',
            'city': '...'
        });
    });
});
```

---

## 🚀 QUICK START - PEŁNY WORKFLOW

### Kompletny proces (5 minut)

```bash
# 1. Generate CSV
./generate-pt24-csv.sh

# 2. Generate pages with AI (requires OpenAI API key)
export OPENAI_API_KEY="sk-..."
wp pt24 generate-pages --batch=125 --with-ai --publish

# 3. Flush rewrites
wp rewrite flush

# 4. Submit to Google
wp pt24 submit-sitemap

# 5. Check status
wp pt24 stats

# Done! 125 pages live.
```

### Weryfikacja

```bash
# List all generated pages
wp pt24 list --format=table

# Check specific city
wp pt24 list --city=warszawa

# Check specific service
wp pt24 list --service=mechanik

# View stats
wp pt24 stats
```

---

## 📝 PRZYKŁADOWE TREŚCI

### Przykład 1: Mechanik Warszawa

**URL:** `/mechanik-warszawa/`

**Treść:**
```html
<h1>Mechanik samochodowy Warszawa</h1>

<div class="intro">
<p>Auto się zepsuło w Warszawie? Szukasz sprawdzonego mechanika, który szybko pomoże i nie nabije w butelkę? Jesteś we właściwym miejscu.</p>
</div>

<h2>Najczęstsze problemy kierowców w Warszawie</h2>
<ul>
  <li><strong>Auto nie odpala rano</strong> — problem z akumulatorem lub rozrusznikiem</li>
  <li><strong>Dziwne dźwięki przy jeździe</strong> — może to być zawieszenie, hamulce lub coś luzuje</li>
  <li><strong>Kontrolka check engine świeci się</strong> — trzeba zdiagnozować komputer</li>
  <li><strong>Auto traci moc</strong> — filtr, turbo, problem z komputerem</li>
</ul>

<h2>Usługi mechaników w Warszawie</h2>
<ul>
  <li>Diagnostyka komputerowa</li>
  <li>Naprawy mechaniczne (silnik, skrzynia, zawieszenie)</li>
  <li>Elektryka samochodowa</li>
  <li>Mobilny mechanik — przyjeżdża do Ciebie</li>
  <li>Przeglądy okresowe</li>
  <li>Wymiana oleju i filtrów</li>
</ul>

<h2>Dlaczego warto wybrać lokalnego mechanika w Warszawie</h2>
<ul>
  <li>✓ <strong>Szybko dojedzie</strong> — działa w Warszawie i okolicach</li>
  <li>✓ <strong>Zna warunki</strong> — wie, jakie usterki się zdarzają</li>
  <li>✓ <strong>Kontakt bezpośredni</strong> — umawiasz się przez telefon</li>
  <li>✓ <strong>Uczciwe ceny</strong> — bez ukrytych kosztów</li>
</ul>

<div class="pt24-cta">
  <p><strong>Potrzebujesz mechanika w Warszawie? Zadzwoń:</strong></p>
  <a href="tel:+48123456789" class="pt24-phone-btn">📞 +48 123 456 789</a>
</div>

<h2>Najczęściej zadawane pytania</h2>

<h3>Ile kosztuje diagnostyka w Warszawie?</h3>
<p>Ceny diagnostyki komputerowej w Warszawie wahają się od 50 do 100 zł. Niektórzy mechanicy robią diagnostykę za darmo, jeśli od razu naprawiasz auto u nich.</p>

<h3>Czy mechanik może przyjechać do domu w Warszawie?</h3>
<p>Tak, mobilni mechanicy obsługują całą Warszawę — Mokotów, Bemowo, Ursynów, Praga, Śródmieście i wszystkie inne dzielnice. Przyjadą, zdiagnozują i często naprawią na miejscu.</p>

<h3>Jak szybko umówię wizytę z mechanikiem?</h3>
<p>W Warszawie zwykle możesz umówić się tego samego dnia lub następnego. W przypadku awarii mobilny mechanik może być u Ciebie w ciągu 1-2 godzin.</p>
```

---

## 🔥 NAJLEPSZE PRAKTYKI

### Content Quality
- ✅ Minimum 600 słów per strona
- ✅ Unique content (nie kopiuj-wklej)
- ✅ Naturalne użycie frazy (nie keyword stuffing)
- ✅ Lokalne referencje (dzielnice, ulice, znane miejsca)
- ✅ Praktyczne informacje (ceny, czas, dostępność)

### SEO
- ✅ Title: "Usługa Miasto | PT24.PRO"
- ✅ Meta description: 150-160 znaków z CTA
- ✅ H1 = Title (bez duplikacji)
- ✅ H2, H3 struktura (min. 3 H2)
- ✅ Internal links (3-5 per strona)
- ✅ Alt text w obrazkach

### UX
- ✅ Czytelna typografia
- ✅ Widoczny telefon above the fold
- ✅ Mobile-friendly (70%+ ruchu z mobile)
- ✅ Fast loading (<2s)
- ✅ Clear CTA buttons

### Conversion
- ✅ Telefon clickable (`tel:` link)
- ✅ CTA 2-3× na stronie
- ✅ Trust signals (ikony, badges)
- ✅ FAQ answers objections
- ✅ No friction (brak rejestracji)

---

## 🎯 METRYKI SUKCESU (KPI)

### Ruch
- **Target:** 10,000+ odwiedzin/miesiąc (po 3-6 miesiącach)
- **Metric:** Google Analytics Sessions
- **Source:** Google Organic

### Pozycje
- **Target:** 50+ stron w TOP 10 Google
- **Metric:** Google Search Console Average Position
- **Query:** "[usługa] [miasto]"

### Telefony
- **Target:** 500+ kliknięć w telefon/miesiąc
- **Metric:** GTM phone click events
- **Conversion Rate:** 3-5%

### Firmy
- **Target:** 20+ nowych firm/miesiąc
- **Metric:** New pt24_business posts
- **MRR:** 1,580 zł+ (20 × 79 zł)

---

## 📚 DODATKOWE ZASOBY

### Dokumentacja
- `PT24-PRO-PLATFORM-BLUEPRINT.md` - Blueprint platformy
- `docs/PT24-HOMEPAGE-V5-GUIDE.md` - Homepage guide
- `theme/pearblog-theme/inc/pt24-cli-commands.php` - WP-CLI commands

### Narzędzia
- **WP-CLI:** `wp pt24 --help`
- **Python Generator:** `scripts/generate-pt24-pages.py`
- **CSV Generator:** `scripts/generate-pt24-csv.sh`

### Support
- GitHub Issues: [AndyPearman89/PearBlog-Engine-](https://github.com/AndyPearman89/PearBlog-Engine-)
- Email: kontakt@pt24.pro

---

**Wersja:** 1.0
**Data:** 2026-05-04
**Status:** ✅ Production Ready
**Autor:** PearBlog Team

---

## 🚀 READY TO SCALE!

System gotowy do wygenerowania **125 stron w 5 minut** lub **500+ w godzinę**.
Wszystkie narzędzia zaimplementowane i przetestowane.

**Let's go! 🔥**

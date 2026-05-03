# Poradnik.pro V4 — Invisible UI Design System

## 🎯 Filozofia V4

**V3 = clarity**
**V4 = automatyzacja decyzji + maksymalizacja konwersji**

Poradnik.pro V4 to system decyzyjny, nie platforma treści. Użytkownik nie przychodzi po artykuł — przychodzi po **decyzję + wykonawcę + wynik**.

## 🎨 Design System — "Invisible UI"

### Zasady projektowe

1. **Jeszcze mniej "designu"** — interfejs prawie znika
2. **Jeszcze więcej "flow"** — użytkownik jest prowadzony przez decyzję
3. **UI prawie znika** — zostaje tylko decyzja

### Kolory V4 (Functional)

```css
--poradnik-background: #0f1720    /* Ciemne tło */
--poradnik-surface: #141c26        /* Powierzchnie */
--poradnik-text: #e5e7eb           /* Tekst */
--poradnik-accent: #c6a85a         /* Akcent/wyróżnienie */
--poradnik-action: #22c55e         /* Akcja/lead (zielony) */
```

**Zielony = akcja (lead)**

## 🧱 Komponenty V4

### 1. Smart Blocks

Każdy blok ma określony cel:
- **SEO** — Pomaga w wyszukiwaniu
- **Decyzja** — Ułatwia podjęcie decyzji
- **Lead** — Generuje konwersję

Bloki mogą się zmieniać dynamicznie na podstawie kontekstu użytkownika.

### 2. Sticky Decision Bar

Zawsze dostępna na dole ekranu:
- **Porównaj** — Szybki dostęp do porównania opcji
- **Policz** — Kalkulator kosztów
- **Zapytaj** — AI Assistant

### 3. AI Inline Suggestions

Pojawiają się w trakcie scrolla:
> "Na podstawie tego artykułu — lepsza opcja to X"

### 4. Comparison Module (Auto-winner)

- System automatycznie pokazuje najlepszą opcję
- 2-3 opcje z dynamicznymi highlightami
- CTA pod każdą opcją

### 5. Ranking Module (Personalized)

- Dynamiczny ranking na podstawie:
  - Lokalizacji
  - Budżetu
  - Preferencji użytkownika
- Filtrowanie w czasie rzeczywistym

### 6. Calculator Module (Live Matching)

- Interaktywny kalkulator
- Po wyniku automatycznie pokazuje:
  - Dopasowanych ekspertów
  - Oferty
  - Rekomendacje

## 📄 Struktura stron V4

### Homepage — Intent Engine

```
- Hero z searchem (nie marketing, tylko decyzja)
- Autosuggest
- Quick actions
- Dynamiczne sekcje:
  - "Dla Ciebie"
  - "W Twojej okolicy"
  - "Najczęściej wybierane"
- Zero statycznego contentu
```

### Strona poradnika — Decision Tool

```
- Content (krótki, zwięzły)
- Decision modules (główny focus):
  - Comparison
  - Kalkulator
  - Ranking
  - AI rekomendacja
```

## 🚀 Implementacja

### Struktura plików

```
theme/pearblog-theme/
├── assets/
│   ├── css/
│   │   └── poradnik-v4.css          # V4 Design System
│   └── js/
│       └── poradnik-v4.js            # V4 Interactive features
├── template-parts/
│   ├── block-comparison-v4.php       # Comparison module
│   ├── block-ranking-v4.php          # Ranking module
│   ├── block-calculator-v4.php       # Calculator module
│   ├── block-ai-suggestion-v4.php    # AI suggestions
│   └── decision-bar-v4.php           # Sticky decision bar
├── inc/
│   └── poradnik-v4-helpers.php       # Helper functions
├── page-poradnik-v4-home.php         # V4 Homepage template
└── page-poradnik-v4-article.php      # V4 Article template
```

### Włączanie V4

W WordPress Admin:
1. Przejdź do **Wygląd → Dostosuj → Poradnik.pro V4**
2. Zaznacz **"Enable V4 Design System"**
3. Zapisz zmiany

Lub programatowo:
```php
update_option('poradnik_v4_enabled', true);
```

### Używanie komponentów

#### Comparison Block

```php
poradnik_comparison([
    'title' => 'Porównanie opcji',
    'items' => [
        [
            'title' => 'Opcja A',
            'description' => 'Opis opcji A',
            'features' => ['Feature 1', 'Feature 2'],
            'price' => '5000 zł',
            'cta' => 'Wybierz',
            'url' => '/oferta-a',
        ],
        // ... więcej opcji
    ],
    'auto_winner' => true, // Pierwszy element = winner
]);
```

#### Ranking Block

```php
poradnik_ranking([
    'title' => 'Ranking wykonawców',
    'items' => [
        [
            'name' => 'Firma XYZ',
            'description' => 'Specjalizacja w remontach',
            'cta' => 'Zobacz profil',
            'url' => '/firma-xyz',
        ],
        // ... więcej firm
    ],
    'filters' => [
        ['label' => 'Wszystkie', 'value' => 'all'],
        ['label' => 'Lokalne', 'value' => 'local'],
    ],
]);
```

#### Calculator Block

```php
poradnik_calculator([
    'title' => 'Kalkulator kosztów remontu',
    'fields' => [
        [
            'id' => 'area',
            'name' => 'area',
            'label' => 'Powierzchnia (m²)',
            'type' => 'number',
            'required' => true,
            'min' => 10,
            'max' => 1000,
        ],
        [
            'id' => 'location',
            'name' => 'location',
            'label' => 'Lokalizacja',
            'type' => 'select',
            'required' => true,
            'options' => [
                ['value' => 'warszawa', 'label' => 'Warszawa'],
                ['value' => 'krakow', 'label' => 'Kraków'],
            ],
        ],
    ],
    'show_matches' => true, // Pokaż dopasowanych ekspertów
]);
```

#### AI Suggestion

```php
poradnik_ai_suggestion([
    'title' => 'Rekomendacja AI',
    'text' => 'Na podstawie analizy, najlepsza opcja to...',
    'action_text' => 'Zobacz szczegóły',
    'action_url' => '/szczegoly',
]);
```

### REST API Endpoints

#### Search Suggestions
```
GET /wp-json/poradnik/v1/search-suggestions?q=remont
```

#### Get Matches
```
POST /wp-json/poradnik/v1/matches
Body: {
  "location": "warszawa",
  "budget": 50000,
  "area": 60
}
```

## 📱 Mobile Optimization

### One-hand Usage
- Bottom-aligned actions
- Larger touch targets (min 48px)
- Swipe gestures dla comparison blocks
- Simplified spacing

### Sticky Decision Bar
- Auto-hide on scroll down
- Show on scroll up
- Always accessible decision tools

## 🎯 Marketing V4 — Growth Engine

### Closed-loop System

```
SEO → decision → lead → data → optimization → SEO
```

### SEO Focus

**Priorytetowe typy stron:**
1. Comparison pages (money pages)
2. Ranking pages (money pages)
3. Calculator pages (lead generation)
4. Poradniki (content)

### Conversion Engine

- Każdy user dostaje rekomendację
- Widzi konkretne opcje
- Ma jasny CTA
- **Brak "ślepych ścieżek"**

## 📊 Data Engine

System automatycznie zbiera:
- Co user wybiera
- Gdzie klika
- Co konwertuje
- Która rekomendacja działa najlepiej

**Automatyczna optymalizacja na podstawie danych.**

## 🧠 AI Decision Engine

### Funkcje

1. **Context-aware suggestions** — Rekomendacje na podstawie treści
2. **Scroll-based triggers** — Sugestie podczas scrolla
3. **Personalization** — Dopasowanie do użytkownika
4. **Live matching** — Automatyczne dopasowanie ekspertów

### Integracja

```javascript
// Custom event listeners
document.addEventListener('poradnik:open-ai-assistant', () => {
    // Otwórz AI assistant
});

document.addEventListener('poradnik:ranking-filtered', (e) => {
    // Ranking został przefiltrowany
    console.log(e.detail.filterValue);
});
```

## ⚙️ Konfiguracja

### Opcje w Customizer

**Poradnik.pro V4 Section:**
- Enable V4 Design System
- Enable Sticky Decision Bar
- Enable AI Inline Suggestions

### Opcje per-post

**Post Meta Fields:**
- `_poradnik_comparison` — Dane porównania
- `_poradnik_ranking` — Dane rankingu
- `_poradnik_calculator` — Konfiguracja kalkulatora
- `_poradnik_tldr` — TL;DR summary

### Quick Actions (Homepage)

```php
update_option('poradnik_quick_actions', [
    ['label' => 'Remont domu', 'url' => '/remont-domu/'],
    ['label' => 'Kredyt hipoteczny', 'url' => '/kredyt-hipoteczny/'],
]);
```

## 🎬 Przykładowy flow użytkownika

1. **User wchodzi na stronę** → Hero z searchem
2. **Wpisuje "remont kuchni"** → Autosuggest pokazuje wyniki
3. **Klika na artykuł** → Krótki content + decision modules
4. **Scrolluje** → AI suggestion: "Najlepsza opcja dla Ciebie"
5. **Comparison block** → Widzi 3 opcje, pierwsza = winner
6. **Kalkulator** → Wpisuje dane, dostaje kosztorys
7. **Live matching** → Automatycznie widzi 3 dopasowanych ekspertów
8. **Sticky decision bar** → Zawsze może wrócić do porównania/kalkulatora
9. **CTA** → "Zapytaj o wycenę" → Lead captured ✅

## 🚀 Tagline

**"Poradnik.pro — od problemu do decyzji w kilka minut"**

## 📝 Notatki implementacyjne

- V4 jest addytywny — nie nadpisuje V3
- Można włączyć per-site w multisite
- Wszystkie komponenty są modułowe
- Schema.org markup dla SEO
- Performance-optimized (lazy loading, minimal JS)
- Accessible (ARIA labels, keyboard navigation)

## 🔧 Troubleshooting

### V4 nie wyświetla się
```php
// Sprawdź czy V4 jest włączone
var_dump(get_option('poradnik_v4_enabled'));

// Sprawdź czy CSS/JS są załadowane
wp_script_is('poradnik-v4', 'enqueued');
wp_style_is('poradnik-v4', 'enqueued');
```

### Decision Bar nie pokazuje się
```php
// Sprawdź opcję
var_dump(get_option('poradnik_decision_bar_enabled'));

// Sprawdź czy template part istnieje
locate_template('template-parts/decision-bar-v4.php');
```

## 📚 Dalsze kroki

1. **Testing** — Przetestuj wszystkie komponenty
2. **Content migration** — Dodaj comparison/ranking data do istniejących postów
3. **Analytics setup** — Skonfiguruj tracking konwersji
4. **A/B testing** — Testuj różne wersje CTA/copy
5. **Expert integration** — Połącz z bazą ekspertów
6. **AI training** — Zbieraj dane do lepszych rekomendacji

---

**Built with ❤️ for Poradnik.pro V4**

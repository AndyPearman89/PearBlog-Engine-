# PORADNIK.PRO — WIREFRAME SYSTEM V1

**Complete Decision Hub Architecture**

Version: 1.0.0
Date: 2026-05-04
Status: Specification → Implementation

---

## 🎯 System Philosophy

**This is NOT a collection of pages.**
**This is a DECISION ENGINE that guides users from intent to action.**

### Core Flow

```
Search Intent → Content (Trust) → Comparison (Options) →
Ranking (Choice) → Calculator (Value) → Expert (Conversion)
```

### Guiding Principles

1. **No Dead Ends** - Every page has 3 exit paths (comparison, calculator, expert)
2. **Progressive Disclosure** - Show complexity only when needed
3. **Minimal Choice** - 3 options max (not 4, not 5)
4. **Context Awareness** - Each page knows where user came from
5. **Mobile First** - Bottom nav, sticky CTAs, swipe interactions

---

## 📐 Page Architecture

### 1. FRONT PAGE (Decision Hub)

**Purpose**: Capture intent and route to appropriate content type

#### Header
```
┌─────────────────────────────────────────────────────┐
│ [Logo]        [Search Bar]        [Menu]  [CTA]     │
│                                                       │
│ Menu: Poradniki | Porównania | Rankingi | Kalkulatory│
│ CTA: "Dla specjalistów"                             │
└─────────────────────────────────────────────────────┘
```

#### Hero Section
```
┌─────────────────────────────────────────────────────┐
│                                                       │
│        Czego szukasz?                                │
│   ┌─────────────────────────────────────────┐       │
│   │ [Large Search Input]                     │       │
│   └─────────────────────────────────────────┘       │
│                                                       │
│   Autosuggest live:                                  │
│   📄 Poradnik: Jak wybrać X                         │
│   🆚 Porównanie: X vs Y                             │
│   🏆 Ranking: Najlepsi wykonawcy X                  │
│                                                       │
│   [Znajdź rozwiązanie]  [Zapytaj eksperta]          │
│                                                       │
└─────────────────────────────────────────────────────┘
```

#### Quick Actions (3 tiles - not 4!)
```
┌───────────────┬───────────────┬───────────────┐
│ 🔍 Znajdź     │ 🆚 Porównaj   │ 🏆 Wybierz    │
│ rozwiązanie   │ opcje         │ wykonawcę     │
└───────────────┴───────────────┴───────────────┘
```

#### Live Section
```
┌─────────────────────────────────────────────────────┐
│ 📈 Użytkownicy teraz sprawdzają:                    │
│                                                       │
│ • Budowa domu (Warszawa) - 12 użytkowników          │
│ • Remont łazienki (Kraków) - 8 użytkowników         │
│ • Instalacja pompy ciepła - 5 użytkowników          │
└─────────────────────────────────────────────────────┘
```

#### Content Mix
```
┌─────────────────────────────────────────────────────┐
│ 📄 2 Poradniki  🆚 1 Porównanie  🏆 1 Ranking       │
│                                                       │
│ [Card] [Card]    [Card]          [Card]             │
└─────────────────────────────────────────────────────┘
```

#### Expert Strip
```
┌──────┬──────┬──────┬──────┬──────┐
│ Exp1 │ Exp2 │ Exp3 │ Exp4 │ Exp5 │
│ ⭐4.9│ ⭐4.8│ ⭐4.9│ ⭐4.7│ ⭐4.8│
│ [CTA]│ [CTA]│ [CTA]│ [CTA]│ [CTA]│
└──────┴──────┴──────┴──────┴──────┘
```

#### Bottom CTA
```
┌─────────────────────────────────────────────────────┐
│  Zdobądź klientów — dołącz jako ekspert             │
│  [Zarejestruj firmę]                                │
└─────────────────────────────────────────────────────┘
```

---

### 2. STRONA PORADNIKA (Guide Page)

**Purpose**: Build trust through education, embed decision tools

#### Layout
```
┌─────────────────────────────────────────────────────┐
│ Breadcrumb: Home > Kategoria > Tytuł                │
│                                                       │
│ H1: Jak wybrać X w 2026?                            │
│ Meta: ⏱ 8 min czytania | 📅 Aktualizacja: 2026-05-04│
└─────────────────────────────────────────────────────┘

┌─────────────────────────────┬───────────────────────┐
│ LEFT (70%)                  │ RIGHT (30% - Sticky)  │
│                             │                       │
│ ## Intro                    │ 🧑‍💼 Zapytaj eksperta│
│ Krótki wstęp (2-3 zdania)  │ [Formularz]           │
│                             │                       │
│ ## Sekcja 1                 │ 🆚 Porównaj opcje    │
│ Content...                  │ [Link]                │
│                             │                       │
│ ┌─────────────────────────┐│ 🧮 Policz koszt       │
│ │ 🆚 PORÓWNANIE (inline) ││ [Kalkulator]          │
│ │ X vs Y - szybki werdykt││                       │
│ └─────────────────────────┘│                       │
│                             │                       │
│ ## Sekcja 2                 │                       │
│ Content...                  │                       │
│                             │                       │
│ ┌─────────────────────────┐│                       │
│ │ 🧮 KALKULATOR           ││                       │
│ │ Oblicz ile zapłacisz    ││                       │
│ └─────────────────────────┘│                       │
│                             │                       │
│ ## FAQ                      │                       │
│ Questions & Answers         │                       │
│                             │                       │
│ ┌─────────────────────────┐│                       │
│ │ 🧑‍💼 EKSPERCI          ││                       │
│ │ Top 3 w Twojej okolicy  ││                       │
│ └─────────────────────────┘│                       │
└─────────────────────────────┴───────────────────────┘

┌─────────────────────────────────────────────────────┐
│ Powiązane artykuły: [Art1] [Art2] [Art3]           │
│                                                       │
│ Następny krok:                                       │
│ [Porównaj opcje] [Oblicz koszt] [Znajdź eksperta]  │
└─────────────────────────────────────────────────────┘
```

---

### 3. STRONA PORÓWNANIA (Comparison Page)

**Purpose**: Help user choose between 2-3 options with clear verdict

#### Structure
```
┌─────────────────────────────────────────────────────┐
│ 🆚 X vs Y — co wybrać w 2026?                       │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ ⚡ SZYBKI WERDYKT                                   │
│                                                       │
│ Wybierz X jeśli: [główny case]                      │
│ Wybierz Y jeśli: [główny case]                      │
└─────────────────────────────────────────────────────┘

┌──────────────────────┬──────────────────────────────┐
│ OPCJA X              │ OPCJA Y                      │
│ [Zdjęcie]            │ [Zdjęcie]                    │
│                      │                              │
│ Cena: XXX-YYY zł    │ Cena: XXX-YYY zł            │
│ Pros: • • •          │ Pros: • • •                  │
│ Cons: • • •          │ Cons: • • •                  │
│                      │                              │
│ [Sprawdź oferty]     │ [Sprawdź oferty]             │
└──────────────────────┴──────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ 📊 TABELA PORÓWNAWCZA                               │
│                                                       │
│ Feature          │ X        │ Y        │ Wygrywa    │
│ ───────────────────────────────────────────────────│
│ Cena            │ 5000 zł  │ 3500 zł  │ Y         │
│ Jakość          │ ⭐⭐⭐⭐ │ ⭐⭐⭐   │ X         │
│ Trwałość        │ 15 lat   │ 10 lat   │ X         │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ 🎯 Kiedy wybrać X?                                  │
│ • Case 1                                             │
│ • Case 2                                             │
│ • Case 3                                             │
│                                                       │
│ 🎯 Kiedy wybrać Y?                                  │
│ • Case 1                                             │
│ • Case 2                                             │
│ • Case 3                                             │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ 💸 CTA                                               │
│ Nie wiesz co wybrać? Zapytaj eksperta               │
│ [Formularz]                                          │
└─────────────────────────────────────────────────────┘
```

---

### 4. STRONA RANKINGU (Ranking Page)

**Purpose**: Present filtered list of verified providers

#### Structure
```
┌─────────────────────────────────────────────────────┐
│ 🏆 Najlepsze firmy X w Y (2026)                     │
│                                                       │
│ 247 zweryfikowanych firm | Aktualizacja: 2026-05-04 │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ 🔎 FILTRY                                           │
│                                                       │
│ Cena: [Min] - [Max]                                 │
│ Lokalizacja: [Select: Warszawa, Kraków...]          │
│ Ocena: ⭐⭐⭐⭐⭐ (min)                              │
│ Usługi: [Checkboxes]                                │
│                                                       │
│ [Zastosuj filtry]                                    │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ 💰 PREMIUM (Top 3 - wyróżnione)                    │
│                                                       │
│ ┌───────────────────────────────────────────────┐  │
│ │ #1 🏆 FirmaPRO                                │  │
│ │ ⭐ 4.9 (847 opinii) | Od 15 lat na rynku     │  │
│ │ USP: Najszybsza realizacja, gwarancja 5 lat   │  │
│ │ [Wyślij zapytanie]                             │  │
│ └───────────────────────────────────────────────┘  │
│                                                       │
│ ┌───────────────────────────────────────────────┐  │
│ │ #2 🥈 BudujemyDomy                            │  │
│ │ ⭐ 4.8 (652 opinii)                            │  │
│ │ [Wyślij zapytanie]                             │  │
│ └───────────────────────────────────────────────┘  │
│                                                       │
│ ┌───────────────────────────────────────────────┐  │
│ │ #3 🥉 RemontPro24                             │  │
│ │ ⭐ 4.9 (523 opinii)                            │  │
│ │ [Wyślij zapytanie]                             │  │
│ └───────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ 📊 WSZYSTKIE FIRMY (4-50)                           │
│                                                       │
│ [Card] [Card] [Card] [Card] ...                     │
│                                                       │
│ Każda karta:                                         │
│ • Nazwa + logo                                       │
│ • Rating + liczba opinii                             │
│ • USP (1 zdanie)                                     │
│ • [CTA: Wyślij zapytanie]                           │
└─────────────────────────────────────────────────────┘
```

---

### 5. STRONA KALKULATORA (Calculator Page)

**Purpose**: Provide instant cost estimate and match with providers

#### Structure
```
┌─────────────────────────────────────────────────────┐
│ 🧮 Kalkulator kosztów X                             │
│ Oblicz ile zapłacisz za swoją inwestycję            │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ 📝 INPUT (Top)                                      │
│                                                       │
│ Metraż: [___] m²                                    │
│ Standard: [Select: podstawowy/średni/premium]       │
│ Lokalizacja: [Select: miasto]                       │
│ Typ: [Select: opcje specyficzne]                    │
│                                                       │
│ [🧮 Oblicz koszt]                                   │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ 📊 WYNIK (Middle)                                   │
│                                                       │
│ Szacunkowy koszt:                                    │
│ 500 000 - 750 000 zł                                │
│                                                       │
│ Średnia cena: 625 000 zł                            │
│ Cena za m²: 6 250 zł/m²                             │
│                                                       │
│ Rozbicie kosztów:                                    │
│ • Materiały: 350 000 zł                              │
│ • Robocizna: 200 000 zł                              │
│ • Dodatkowe: 75 000 zł                               │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ 💡 REKOMENDACJA                                     │
│                                                       │
│ Na podstawie Twojego wyliczenia polecamy:           │
│ • Standard średni + lokalne materiały                │
│ • Czas realizacji: 12-15 miesięcy                   │
│ • Uwaga: Ceny mogą wzrosnąć o 5-10% w Q3           │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ 🧑‍💼 AUTO MATCH - Firmy dla Ciebie               │
│                                                       │
│ #1 FirmaPRO - pasuje w 95%                          │
│ ⭐ 4.9 | 15 lat | Specjalizacja: Twój typ projektu │
│ [Wyślij zapytanie]                                   │
│                                                       │
│ #2 BudujemyDomy - pasuje w 88%                      │
│ ⭐ 4.8 | 10 lat                                      │
│ [Wyślij zapytanie]                                   │
│                                                       │
│ #3 RemontPro24 - pasuje w 82%                       │
│ ⭐ 4.9 | 8 lat                                       │
│ [Wyślij zapytanie]                                   │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ 💸 CTA (Bottom)                                     │
│                                                       │
│ Chcesz dokładną wycenę?                             │
│ Wyślij zapytanie do dopasowanych firm               │
│                                                       │
│ [📩 Wyślij zapytanie do wszystkich]                │
└─────────────────────────────────────────────────────┘
```

---

### 6. STRONA EKSPERTA (Expert/Provider Page)

**Purpose**: Present provider profile and enable direct contact

#### Structure
```
┌─────────────────────────────────────────────────────┐
│ 🏢 HERO                                             │
│                                                       │
│ [Logo]  FirmaPRO                                    │
│         Kompleksowa budowa domów                     │
│         ⭐ 4.9 (847 opinii) | 15 lat na rynku       │
│         📍 Warszawa, Kraków, Katowice               │
│                                                       │
│         [👉 Wyślij zapytanie]                       │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ 📄 OPIS                                             │
│                                                       │
│ Jesteśmy wiodącą firmą budowlaną z 15-letnim        │
│ doświadczeniem. Realizujemy projekty od A do Z...   │
│                                                       │
│ Nasze specjalizacje:                                 │
│ • Budowa domów jednorodzinnych                       │
│ • Domy energooszczędne                               │
│ • Projekty indywidualne                              │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ ⭐ OPINIE (847)                                      │
│                                                       │
│ ┌───────────────────────────────────────────────┐  │
│ │ Jan K. | ⭐⭐⭐⭐⭐ | 2026-04-15            │  │
│ │ "Profesjonalna realizacja, dotrzymali          │  │
│ │  terminów. Polecam!"                            │  │
│ └───────────────────────────────────────────────┘  │
│                                                       │
│ [Wszystkie opinie →]                                │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ 🏗️ REALIZACJE (portfolio)                          │
│                                                       │
│ [Zdjęcie] [Zdjęcie] [Zdjęcie] [Zdjęcie]            │
│                                                       │
│ [Zobacz wszystkie realizacje →]                     │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ 🔧 ZAKRES USŁUG                                     │
│                                                       │
│ ✓ Budowa pod klucz                                  │
│ ✓ Stan surowy                                        │
│ ✓ Stan deweloperski                                 │
│ ✓ Projekty indywidualne                             │
│ ✓ Nadzór budowlany                                  │
│ ✓ Gwarancja 5 lat                                   │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ 💸 STICKY CTA                                       │
│                                                       │
│ Zainteresowany? Wyślij zapytanie                    │
│                                                       │
│ [📩 Wyślij zapytanie]                               │
└─────────────────────────────────────────────────────┘
```

---

### 7. AI DECISION PANEL (Global Assistant)

**Purpose**: Floating AI helper that guides decisions

#### Structure
```
┌─────────────────────────────────────────────────────┐
│ 🤖 FLOATING PANEL (bottom-right, minimizable)      │
│                                                       │
│ ┌───────────────────────────────────────────────┐  │
│ │ 🤖 Pomóż mi wybrać                            │  │
│ │                                                 │  │
│ │ Co chcesz zbudować/zrobić?                    │  │
│ │ [Input: Opisz swoją potrzebę...]              │  │
│ │                                                 │  │
│ │ [Pomóż mi]                                     │  │
│ └───────────────────────────────────────────────┘  │
│                                                       │
│ ┌───────────────────────────────────────────────┐  │
│ │ 💡 Rekomendacja AI:                           │  │
│ │                                                 │  │
│ │ Na podstawie Twojego opisu polecam:           │  │
│ │                                                 │  │
│ │ 📄 Przeczytaj poradnik:                       │  │
│ │    "Jak wybrać X"                              │  │
│ │                                                 │  │
│ │ 🆚 Porównaj opcje:                            │  │
│ │    "X vs Y"                                    │  │
│ │                                                 │  │
│ │ 🏆 Zobacz ranking:                            │  │
│ │    "Najlepsze firmy X"                         │  │
│ │                                                 │  │
│ │ 🧮 Oblicz koszt:                              │  │
│ │    "Kalkulator X"                              │  │
│ │                                                 │  │
│ │ 🧑‍💼 Lub zapytaj eksperta bezpośrednio      │  │
│ │    [Formularz]                                 │  │
│ └───────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
```

---

### 8. MOBILE WIREFRAME

**Purpose**: Optimized mobile experience

#### Bottom Navigation (always visible)
```
┌──────┬──────┬──────┬──────┐
│ 🔍   │ 🆚   │ 🏆   │ 👤   │
│Search│Compare│Rank │Konto │
└──────┴──────┴──────┴──────┘
```

#### Sticky CTA (appears after scroll)
```
┌─────────────────────────────────────┐
│ 👉 Zapytaj eksperta                │
└─────────────────────────────────────┘
```

#### Swipe Interactions
- Swipe left/right on comparison cards
- Swipe up for more details on expert cards
- Pull to refresh on rankings

---

## 🔄 SYSTEM LOGIC - The Magic

### Every Page Has 3 Exits

```
┌─────────────────────────────────────┐
│ ANY PAGE                            │
│                                     │
│ Exit 1: 🆚 Porównaj opcje          │
│ Exit 2: 🧮 Oblicz koszt            │
│ Exit 3: 🧑‍💼 Zapytaj eksperta     │
└─────────────────────────────────────┘
```

### Content Interconnections

```
Poradnik → embeds → Comparison
                  → embeds → Calculator
                  → embeds → Expert Cards

Comparison → links → Ranking
           → links → Calculator

Ranking → links → Expert Pages
        → links → Calculator

Calculator → auto-matches → Experts

Expert → links back → Related Poradniki
```

### User Flows

**Flow 1: Research → Decision**
```
Search → Poradnik → Comparison → Ranking → Expert → Lead
```

**Flow 2: Quick Decision**
```
Search → Calculator → Auto-Match → Expert → Lead
```

**Flow 3: Browse → Discover**
```
Homepage → Content Mix → Any Page → 3 Exits → Conversion
```

---

## 🎨 Design System Integration

### Components Needed

1. **Navigation**
   - Main nav (desktop)
   - Bottom nav (mobile)
   - Breadcrumbs

2. **Cards**
   - Content card (poradnik/comparison/ranking)
   - Expert card (compact/expanded)
   - Highlight card (premium ranking)

3. **Forms**
   - Search bar (with autosuggest)
   - Calculator inputs
   - Lead form (modal/inline)

4. **Embeds**
   - Inline comparison block
   - Inline calculator widget
   - Expert strip (3-5 cards)

5. **Lists**
   - Ranking list (with filters)
   - Related articles
   - Auto-matched experts

6. **Panels**
   - Sticky sidebar (desktop)
   - Floating AI assistant
   - Sticky CTA (mobile)

### Dark UI Theme

All components use the existing dark theme:
- Background: #0f1720
- Cards: #141c26
- Borders: #1f2937
- Text: #e5e7eb
- Accent: #c6a85a

---

## 🚀 Implementation Phases

### Phase 1: Core Pages (Week 1)
- [ ] Front Page template
- [ ] Poradnik template with embed support
- [ ] Basic comparison template
- [ ] Basic ranking template

### Phase 2: Interactive Components (Week 2)
- [ ] Calculator page + widget
- [ ] Expert page template
- [ ] Lead form modal system
- [ ] Search with autosuggest

### Phase 3: Intelligence Layer (Week 3)
- [ ] AI Decision Panel
- [ ] Auto-matching algorithm
- [ ] Live "users now viewing" system
- [ ] Smart recommendations

### Phase 4: Mobile Optimization (Week 4)
- [ ] Bottom navigation
- [ ] Sticky mobile CTAs
- [ ] Swipe interactions
- [ ] Touch-optimized forms

### Phase 5: Analytics & Optimization (Week 5)
- [ ] Track all exit paths
- [ ] A/B test page variants
- [ ] Optimize conversion funnels
- [ ] Heat mapping

---

## 📊 Success Metrics

### User Behavior
- **Engagement**: Time on site, pages per session
- **Navigation**: Exit paths usage (comparison/calculator/expert)
- **Conversion**: Lead form submissions per 100 visitors

### Content Performance
- **Poradnik**: Embed click-through rates
- **Comparison**: Decision clarity (time to exit)
- **Ranking**: Filter usage, expert clicks
- **Calculator**: Completion rate, auto-match acceptance

### Business Goals
- **Lead Quality**: Match score accuracy
- **Expert Satisfaction**: Lead conversion rate
- **Platform Growth**: Active experts, new users

---

## 🧠 Key Insights

### Why This Works

1. **Progressive Disclosure**: Start simple (search), reveal complexity only when needed
2. **Multiple Entry Points**: Homepage, search, organic, direct links
3. **Contextual CTAs**: Every page knows what user needs next
4. **No Dead Ends**: Always 3 clear paths forward
5. **Trust Building**: Content → Comparison → Social Proof → Expert
6. **Minimal Friction**: Auto-match, pre-filled forms, one-click actions

### The System Advantage

Traditional platform:
```
Page → Page → Page → Maybe CTA
```

Poradnik.pro system:
```
Intent → Content → Decision Tools → Expert Match → Conversion
  ↓        ↓            ↓              ↓            ↓
Track → Learn → Compare → Calculate → Convert
```

---

## 📝 Technical Requirements

### Backend
- Custom Post Types: poradnik, comparison, ranking, expert
- Taxonomies: category, service_type, location
- Meta: ratings, prices, specializations
- API: search, autosuggest, calculator, auto-match

### Frontend
- Dark UI components (already built)
- Mobile-first responsive
- Lazy loading for embeds
- Infinite scroll for rankings

### Database
- Experts/providers table
- Reviews/ratings table
- Calculator submissions (for data)
- User behavior tracking

### Integrations
- Lead routing system
- Email notifications
- Analytics tracking
- A/B testing framework

---

## 🎯 Bottom Line

**This is not a website. This is a decision engine.**

Every page, every component, every CTA is designed to move the user from:
- **Uncertainty** → Clarity
- **Research** → Decision
- **Intent** → Action

The system succeeds when users don't get lost. They get guided. From the moment they arrive to the moment they click "Send inquiry."

---

**Status**: Ready for implementation
**Next**: Build core page templates + embed system
**Owner**: PearBlog Engine Team

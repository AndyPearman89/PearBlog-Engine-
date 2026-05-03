# Frontend V3 - High-Conversion Landing Page

## Overview

Frontend V3 is a complete redesign of the PearBlog homepage focused on high-conversion, decision-making, and action-oriented user experience. Designed specifically for **poradnik.pro** (Polish practical guides site).

## Key Features

### 🎯 Search-First Hero
- Large search input with example queries
- Quick action buttons (Ask Question, Find Specialist)
- Social proof stats counter (+50K guides, +10K experts)
- Gradient background with white overlays

### ⚡ Quick Actions Section
4 decision pathways presented as cards:
1. **📘 Poradniki** - Step-by-step guides
2. **🆚 Porównania** - Comparison articles
3. **🏆 Rankingi** - Expert rankings
4. **🧮 Kalkulatory** - Cost calculators

### 🔥 Trending Section
- Displays popular searches/decisions
- Clickable trending items that trigger search
- Real-time social validation

### 🧠 How It Works (5-Step Flow)
Visual flow diagram showing:
1. 🔍 Enter problem
2. 📊 Compare options
3. 🧮 Check costs
4. 🧑‍💼 Choose expert
5. 📩 Send inquiry

### 📘 Features Section
Deep dive into each feature category:
- **Poradniki** - Concrete answers, no fluff
- **Porównania** - Clear differences, pros/cons, verdicts
- **Rankingi** - User reviews, quality rankings
- **Kalkulatory** - Instant cost estimates + matched contractors

### 🤖 AI Advisor Section
- AI-powered recommendations
- Input: budget, location, goal
- Output: concrete recommendation + ready options

### 🧑‍💼 Experts Section
- Find specialists for tasks
- Profiles, reviews, quick inquiries
- One form → multiple offers

### 💼 For Specialists CTA
Target service providers:
- Visibility in rankings
- Real client inquiries
- Expert brand building

### 🔁 Why It Works Section
4 key benefits:
- ✔ Answer real problems
- ✔ Show concrete options
- ✔ Help make decisions
- ✔ Connect with contractors

### 🚀 Final CTA Section
Strong conversion-focused finale:
- "This is not a reading portal. This is an action platform."
- 4-step value flow: Understand → Compare → Decide → Buy
- Tagline: "Poradnik.pro — od pytania do decyzji"
- Dual CTA buttons

## Technical Implementation

### File Structure

```
theme/pearblog-theme/
├── index.php                              # Main homepage (V3/V2 toggle)
├── template-parts/
│   ├── hero.php                           # Hero with V3 mode
│   ├── section-quick-actions.php          # Quick actions grid
│   ├── section-trending.php               # Trending topics
│   ├── section-how-it-works.php           # 5-step flow
│   ├── section-features.php               # Features deep-dive
│   ├── section-ai-advisor.php             # AI advisor
│   ├── section-experts.php                # Experts section
│   ├── section-for-specialists.php        # Specialists CTA
│   ├── section-why-it-works.php           # Benefits
│   └── section-final-cta.php              # Final conversion
├── assets/css/
│   └── v3-components.css                  # V3 styles
├── inc/
│   └── components.php                     # Helper functions
└── functions.php                          # Asset enqueuing
```

### Configuration

#### Enable V3 Layout

V3 layout is enabled by default. To toggle:

```php
// Enable V3
update_option('pearblog_homepage_version', 'v3');

// Revert to V2
update_option('pearblog_homepage_version', 'v2');
```

#### Hero Configuration

```php
// Enable V3 hero
update_option('pearblog_hero_version', 'v3');

// Custom hero text
update_option('pearblog_hero_title', 'Rozwiąż problem w jednym miejscu.');
update_option('pearblog_hero_subtitle', 'Znajdź odpowiedź, porównaj opcje...');
```

### PHP Functions

All sections have helper functions:

```php
// Render sections
pearblog_hero(['version' => 'v3']);
pearblog_quick_actions();
pearblog_trending();
pearblog_how_it_works();
pearblog_features();
pearblog_ai_advisor();
pearblog_experts_section();
pearblog_for_specialists();
pearblog_why_it_works();
pearblog_final_cta();
```

### CSS Architecture

V3 components use BEM-like naming:

```css
.pb-hero-v3                    /* Hero container */
.pb-hero-search                /* Search section */
.pb-hero-search-input          /* Search input */
.pb-quick-actions              /* Quick actions section */
.pb-quick-action-card          /* Individual action card */
.pb-trending                   /* Trending section */
.pb-flow-steps                 /* How it works flow */
.pb-feature-block              /* Feature section block */
.pb-ai-advisor                 /* AI advisor section */
.pb-final-cta                  /* Final CTA section */
```

## Design Principles

### 1. **Decision-Oriented**
Every section pushes users toward a decision:
- Search → Find answer
- Compare → Choose option
- Calculate → Know cost
- Contact → Hire expert

### 2. **Action-First**
Minimize reading, maximize doing:
- Large CTAs
- Clear next steps
- Visual flow diagrams
- One-click actions

### 3. **Social Proof**
Build trust through validation:
- Stats counters (50K+ guides)
- Trending topics
- Expert rankings
- User reviews

### 4. **Zero Fluff**
Direct, concrete language:
- "Konkretne odpowiedzi" (Concrete answers)
- "Policz koszt w 30 sekund" (Calculate cost in 30 seconds)
- "Jeden formularz → wiele ofert" (One form → many offers)

### 5. **Polish Market Focus**
All copy optimized for Polish users:
- Local examples (Katowice, pompa ciepła)
- Polish decision patterns
- Cultural relevance

## Responsive Design

V3 is fully responsive:

### Mobile (< 768px)
- Stacked sections
- Full-width search
- Vertical flow diagrams
- Single-column grids

### Tablet (768px - 1024px)
- 2-column grids
- Responsive search bar
- Flex-wrapped flows

### Desktop (1024px+)
- 3-4 column grids
- Horizontal flow diagrams
- Maximum 1200px container width

## Conversion Optimization

### High-Value CTAs
1. **Primary:** Search box (hero)
2. **Secondary:** Quick action cards
3. **Tertiary:** Find Expert (multiple locations)
4. **Final:** Dual CTAs (Start Now + Find Expert)

### Funnel Flow
```
Hero Search → Quick Actions → Features → AI Advisor → Experts → Final CTA
     ↓              ↓              ↓          ↓           ↓          ↓
  Intent      Exploration     Education   Guidance   Connection  Conversion
```

### Analytics Tracking

Track key metrics:
- Search submissions
- Quick action clicks
- Trending item clicks
- Expert inquiry submissions
- Final CTA conversions

## Backward Compatibility

V3 maintains full backward compatibility:

- V2 layout still available via `pearblog_homepage_version = 'v2'`
- All existing theme features intact
- No breaking changes to existing sites
- Smooth A/B testing capability

## Performance

### Optimization
- Conditional CSS loading (V3 only when enabled)
- Minimal JavaScript requirements
- Lazy-loaded images
- Optimized font loading

### Load Times
- Hero: < 1s
- Full page: < 2s
- Interactive: < 3s

## Translation

All strings use WordPress i18n:

```php
__('Szukaj', 'pearblog-theme')
__('Poradniki', 'pearblog-theme')
__('Eksperci', 'pearblog-theme')
```

Easy to translate to other languages via .po/.mo files.

## Future Enhancements

Planned V3.1 features:
- [ ] Live search suggestions
- [ ] Trending topics from analytics
- [ ] Dynamic expert carousel
- [ ] Interactive cost calculators
- [ ] Video testimonials
- [ ] A/B testing dashboard
- [ ] Conversion heat maps

## Support

For issues or questions:
1. Check GitHub Issues
2. Review code comments
3. Contact: andy@pearblog.pro

---

**Version:** 3.0.0
**Release Date:** 2026-05-03
**Minimum Requirements:** PHP 8.1+, WordPress 6.0+
**License:** Proprietary (PearBlog Engine)

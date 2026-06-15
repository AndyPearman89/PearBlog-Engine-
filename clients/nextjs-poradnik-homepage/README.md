# Poradnik.pro Homepage SaaS 2026 (Next.js 15)

Kompletny, gotowy do uruchomienia layout strony głównej — premium SaaS design 2026.

## Stack

- **Next.js 15** — App Router, Server Components
- **TypeScript** — strict mode
- **Tailwind CSS** — mobile-first, design tokens
- **shadcn/ui** — reusable UI primitives (Button, Card, Input, Badge)
- **Lucide React** — premium icons
- **WCAG AA** — `focus-visible`, semantic HTML, `aria-label`, `sr-only`

## Design System

| Token           | Value       |
|-----------------|-------------|
| Primary         | `#4F46E5`   |
| Secondary       | `#7C3AED`   |
| Success         | `#10B981`   |
| Dark            | `#0F172A`   |
| Background      | `#F8FAFC`   |
| Border          | `#E2E8F0`   |
| Border Radius   | `20px`      |
| Shadow (card)   | `0 10px 30px rgba(15,23,42,0.06)` |
| Font            | Inter       |

## Struktura

```
app/
  layout.tsx          — SEO metadata + root layout
  page.tsx            — entry strony głównej
  globals.css         — Tailwind layers + design tokens

components/
  homepage/
    index.ts          — barrel exports
    homepage-layout.tsx — monolityczny layout (legacy/reference)
    sections/
      header.tsx      — sticky header + mobile menu
      hero.tsx        — hero + search + stats
      categories.tsx  — 8 kart kategorii
      experts.tsx     — karty specjalistów
      qa.tsx          — Q&A engine
      rankings.tsx    — rankingi
      calculators.tsx — kalkulatory
      reviews.tsx     — opinie / rating
      lead-engine.tsx — formularz lead generation
      seo.tsx         — SEO tag cloud
      footer.tsx      — footer columns + legal
      section-header.tsx — reusable section title

  ui/
    button.tsx        — Button (cva variants)
    card.tsx          — Card primitives
    input.tsx         — Input
    select.tsx        — Select
    badge.tsx         — Badge

lib/
  utils.ts            — cn() helper (clsx + tailwind-merge)

tailwind.config.ts    — design tokens
tsconfig.json         — strict TS
next.config.ts        — Next.js 15
postcss.config.mjs    — PostCSS + Tailwind
package.json          — dependencies
```

## Uruchomienie

```bash
cd clients/nextjs-poradnik-homepage
npm install
npm run dev
```

## Integracja z PearTree Core

Dane mockowe (eksperci, kategorie, pytania, rankingi) są gotowe do podmiany na payloady API z backendu PearBlog/PearTree Core.

Każda sekcja jest wyodrębniona w osobny komponent — łatwo podmienić statyczne dane na `fetch()` lub React Server Components z danymi z API.

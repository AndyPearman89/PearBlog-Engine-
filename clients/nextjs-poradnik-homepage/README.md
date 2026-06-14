# Poradnik.pro Homepage SaaS 2026 (Next.js 15)

Gotowy layout strony głównej zbudowany w podejściu:

- Next.js 15 + TypeScript
- Tailwind CSS (mobile-first)
- design tokens zgodne z briefem (kolory, radius 20px, delikatne cienie)
- sekcje SEO + Lead Generation + Eksperci + Q&A + Rankingi + Kalkulatory

## Struktura

- `app/layout.tsx` — metadata SEO + root layout
- `app/page.tsx` — entry strony głównej
- `app/globals.css` — design tokens i podstawowe style
- `components/homepage/homepage-layout.tsx` — kompletna struktura sekcji oraz komponenty

## Integracja z PearTree Core

Komponent jest przygotowany pod podmianę danych mockowanych na payloady API (np. rankingi, eksperci, pytania, popularne wyszukiwania) z backendu PearBlog/PearTree Core.

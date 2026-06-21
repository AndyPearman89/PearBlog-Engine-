# PT24.PRO Theme

Motyw WordPress dla marketplace usług lokalnych PT24.PRO, oparty na mockup v6.

## Cechy

- **Nowoczesny design** — Tailwind CSS, gradient branding, karciany layout
- **Responsywny** — Mobile-first, sticky header, scrollable mobile nav
- **Wydajny** — Google Fonts (Inter + Poppins), lazy loading images
- **SEO-friendly** — Semantyczny HTML5, structured headings, meta description
- **Accessible** — ARIA labels, focus states, color contrast

## Struktura

```
theme/pt24/
├── style.css              # Metadane motywu
├── functions.php          # Setup, enqueues, helpers
├── header.php             # Nagłówek z nav
├── footer.php             # Stopka z linkami
├── front-page.php         # Strona główna (homepage)
├── index.php              # Blog / archiwum
├── page.php               # Pojedyncza strona
├── single.php             # Pojedynczy post
├── 404.php                # Strona błędu
├── template-parts/
│   └── logo.php           # SVG logo (reusable)
└── assets/
    ├── css/
    │   └── pt24-theme.css # Dodatkowe style
    └── js/
        └── pt24-theme.js  # Smooth scroll, animations
```

## Kolory brandu

| Token | Kolor | Użycie |
|-------|-------|--------|
| `brand-start` | `#1464F4` | Początek gradientu |
| `brand-mid` | `#4A5FE3` | Środek gradientu |
| `brand-end` | `#7A4FD3` | Koniec gradientu |
| `pear-green` | `#4ADE80` | Akcent zielony |
| `pear-blue` | `#60A5FA` | Akcent niebieski |

## Instalacja

1. Skopiuj folder `theme/pt24/` do `wp-content/themes/`
2. Aktywuj motyw w panelu WordPress
3. Ustaw stronę główną (Ustawienia → Czytanie → Statyczna strona główna)

## Uwagi

- W produkcji zalecany jest skompilowany Tailwind CSS (zamiast CDN)
- Logo SVG jest inline dla optymalnej wydajności
- Dane kategorii i opinii są zarządzane przez helper functions w `functions.php`

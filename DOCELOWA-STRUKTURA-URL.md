# Docelowa Struktura URL — Poradnik.PRO & PT24.PRO

**Data aktualizacji:** 2026-06-16  
**Wersja:** 2.0  
**Status:** Wdrożone (rewrite rules + template routing)

---

## Spis Treści

1. [Poradnik.PRO — Routing](#poradnikpro--routing)
2. [PT24.PRO — Routing](#pt24pro--routing)
3. [Pliki Implementacji](#pliki-implementacji)
4. [Aktywacja / Flush Rewrite Rules](#aktywacja--flush-rewrite-rules)
5. [Helpery URL](#helpery-url)

---

## Poradnik.PRO — Routing

Plik: `theme/pearblog-theme/inc/poradnik-pro-routing.php`  
Klasa: `PearBlog_Poradnik_Pro_Routing`

### Mapa URL → Szablon

| URL | Typ | Szablon |
|-----|-----|---------|
| `/poradniki/` | Archiwum artykułów | `page-poradnik-pro-poradniki.php` |
| `/poradnik/{slug}/` | Artykuł / poradnik | `page-poradnik-pro-article.php` |
| `/porownanie/{slug}/` | Porównanie (A vs B) | `page-poradnik-pro-porownanie.php` |
| `/ranking/{category}/` | Lista rankingowa | `page-poradnik-pro-ranking.php` |
| `/kalkulator/{slug}/` | Kalkulator | `page-poradnik-pro-kalkulator.php` |
| `/kalkulatory/` | Lista kalkulatorów | `page-poradnik-pro-kalkulatory.php` |
| `/pytanie/{slug}/` | Pojedyncze pytanie | `page-poradnik-pro-pytanie.php` |
| `/pytania/` | Archiwum pytań | `page-poradnik-pro-pytania.php` |
| `/specjalista/{slug}/` | Profil specjalisty | `page-poradnik-pro-specjalista.php` |
| `/specjalisci/` | Lista specjalistów | `page-poradnik-pro-specjalisci.php` |
| `/ai-doradca/` | AI doradca | `page-poradnik-pro-ai-doradca.php` |
| `/dla-specjalistow/` | Strefa specjalistów | `page-poradnik-pro-dla-specjalistow.php` |
| `/cennik/` | Cennik | `page-poradnik-pro-cennik.php` |
| `/faq/` | FAQ | `page-poradnik-pro-faq.php` |
| `/kontakt/` | Kontakt | `page-poradnik-pro-kontakt.php` |
| `/panel/` | Dashboard użytkownika | `page-poradnik-pro-dashboard.php` |
| `/kategoria/{slug}/` | Kategoria | `page-poradnik-pro-kategoria.php` |
| `/{miasto}/specjalisci/` | Specjaliści w mieście | `page-poradnik-pro-specjalisci.php` |
| `/{miasto}/{kategoria}/` | Miasto + kategoria | `page-poradnik-pro-miasto.php` |

### Obsługiwane miasta

warszawa, krakow, wroclaw, poznan, gdansk, katowice, lodz, szczecin, lublin, bydgoszcz

### Obsługiwane kategorie

prawo, finanse, nieruchomosci, budownictwo, energia, zdrowie, edukacja, motoryzacja, technologia, dom-i-ogrod

### Walidacja catch-all

Reguła `/{miasto}/{kategoria}/` ma priorytet `bottom` i jest walidowana — routing działa **tylko** gdy slug miasta jest rozpoznany. Zapobiega to przechwytywaniu standardowych ścieżek WordPress.

---

## PT24.PRO — Routing

Plik: `theme/pearblog-theme/inc/pt24-pro-routing.php`  
Klasa: `PearBlog_PT24_Pro_Routing`

### Mapa URL → Szablon (strony platformy)

| URL | Typ | Szablon |
|-----|-----|---------|
| `/dla-fachowcow/` | Landing dla fachowców | `page-pt24-dla-fachowcow.php` |
| `/dodaj-zlecenie/` | Formularz zlecenia | `page-pt24-dodaj-zlecenie.php` |
| `/uslugi/` | Archiwum usług | `page-pt24-kategoria-uslugi.php` |
| `/uslugi/{slug}/` | Kategoria usługi | `page-pt24-kategoria-uslugi.php` |
| `/miasto/{slug}/` | Strona miasta | `page-pt24-miasto.php` |
| `/panel-fachowca/` | Dashboard fachowca | `page-pt24-panel-fachowca.php` |

### Dynamiczne trasy CPT (istniejące)

Obsługiwane przez `pt24-landing-cpt.php`:

| URL | Opis |
|-----|------|
| `/{miasto}/{usługa}/` | Landing dynamiczny (CPT) |
| `/ranking/{miasto}/{usługa}/` | Ranking dynamiczny (CPT) |

### Obsługiwane usługi

hydraulik, elektryk, malarz, stolarz, dekarz, murarz, glazurnik, instalator, ogrodnik, sprzatanie, przeprowadzki, klimatyzacja, fotowoltaika, pompy-ciepla, remont

### Obsługiwane miasta

warszawa, krakow, wroclaw, poznan, gdansk, katowice, lodz, szczecin, lublin, bydgoszcz, rzeszow, bialystok

---

## Pliki Implementacji

```
theme/pearblog-theme/
├── functions.php                           # Include routing files
├── inc/
│   ├── poradnik-pro-routing.php           # Poradnik.PRO routing class
│   └── pt24-pro-routing.php               # PT24.PRO routing class
├── page-poradnik-pro-*.php                 # 19 szablonów Poradnik.PRO
├── page-pt24-panel-fachowca.php           # Dashboard fachowca
├── page-pt24-dla-fachowcow.php            # Landing fachowcy
├── page-pt24-dodaj-zlecenie.php           # Formularz zlecenia
├── page-pt24-kategoria-uslugi.php         # Kategoria usługi
├── page-pt24-miasto.php                   # Strona miasta
└── page-pt24-home-v6.php                  # Homepage V6
```

---

## Aktywacja / Flush Rewrite Rules

Po wdrożeniu zmian na serwerze, wykonaj flush reguł rewrite:

```bash
# WP-CLI
wp rewrite flush

# Lub przez panel WP:
# Ustawienia → Linki bezpośrednie → Zapisz (bez zmian)
```

**Uwaga:** Rewrite rules rejestrowane są na hook `init` — nie wymagają ręcznego dodawania do `.htaccess`.

---

## Helpery URL

### Poradnik.PRO

```php
// Generowanie URL-i
PearBlog_Poradnik_Pro_Routing::url('article', 'koszt-remontu');
// → https://poradnik.pro/poradnik/koszt-remontu/

PearBlog_Poradnik_Pro_Routing::url('city-specialists', 'katowice');
// → https://poradnik.pro/katowice/specjalisci/

PearBlog_Poradnik_Pro_Routing::url('city-category', 'krakow', 'budownictwo');
// → https://poradnik.pro/krakow/budownictwo/

// Odczyt danych z query vars
PearBlog_Poradnik_Pro_Routing::get_current_slug();
PearBlog_Poradnik_Pro_Routing::get_current_category();
PearBlog_Poradnik_Pro_Routing::get_current_city();
PearBlog_Poradnik_Pro_Routing::get_city_name('katowice');   // → 'Katowice'
PearBlog_Poradnik_Pro_Routing::get_category_name('dom-i-ogrod'); // → 'Dom i ogród'
PearBlog_Poradnik_Pro_Routing::get_categories(); // → ['prawo' => 'Prawo', ...]
PearBlog_Poradnik_Pro_Routing::get_cities();     // → ['warszawa' => 'Warszawa', ...]
```

### PT24.PRO

```php
PearBlog_PT24_Pro_Routing::url('for-professionals');
// → https://pt24.pro/dla-fachowcow/

PearBlog_PT24_Pro_Routing::url('service-category', 'hydraulik');
// → https://pt24.pro/uslugi/hydraulik/

PearBlog_PT24_Pro_Routing::url('city', 'krakow');
// → https://pt24.pro/miasto/krakow/

PearBlog_PT24_Pro_Routing::get_current_service();
PearBlog_PT24_Pro_Routing::get_current_city();
PearBlog_PT24_Pro_Routing::get_service_name('pompy-ciepla'); // → 'Pompy ciepła'
PearBlog_PT24_Pro_Routing::get_city_name('lodz');            // → 'Łódź'
PearBlog_PT24_Pro_Routing::get_services(); // → ['hydraulik' => 'Hydraulik', ...]
PearBlog_PT24_Pro_Routing::get_cities();   // → ['warszawa' => 'Warszawa', ...]
```

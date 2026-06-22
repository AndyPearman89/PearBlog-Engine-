# Pełna Lista Stron i Treści — WordPress

**Data:** 2026-06-22  
**Skrypt seedujący:** `scripts/seed-full-content.sh`  
**Status:** Gotowe do wdrożenia

---

## Jak uruchomić

```bash
# Na serwerze (bezpośrednio):
./scripts/seed-full-content.sh /home/tutsoff/public_html/poradnik

# Lub zdalnie przez SSH:
ssh -p 222 tutsoff@wordpress2614653.home.pl 'bash -s' < scripts/seed-full-content.sh /home/tutsoff/public_html/poradnik

# Lub przez GitHub Actions workflow (seed-poradnik-content.yml)
```

---

## Poradnik.PRO — Strony Statyczne (15 stron)

| # | Tytuł | Slug | Szablon |
|---|--------|------|---------|
| 1 | Poradnik.PRO — Strona Główna | `home` | `page-poradnik-pro-home.php` |
| 2 | Poradniki | `poradniki` | `page-poradnik-pro-poradniki.php` |
| 3 | Porównania | `porownania` | `page-poradnik-pro-porownania.php` |
| 4 | Rankingi | `rankingi` | `page-poradnik-pro-rankingi.php` |
| 5 | Kalkulatory | `kalkulatory` | `page-poradnik-pro-kalkulatory.php` |
| 6 | Pytania i Odpowiedzi | `pytania` | `page-poradnik-pro-pytania.php` |
| 7 | Specjaliści | `specjalisci` | `page-poradnik-pro-specjalisci.php` |
| 8 | AI Doradca | `ai-doradca` | `page-poradnik-pro-ai-doradca.php` |
| 9 | Dla Specjalistów | `dla-specjalistow` | `page-poradnik-pro-dla-specjalistow.php` |
| 10 | Cennik | `cennik` | `page-poradnik-pro-cennik.php` |
| 11 | FAQ | `faq` | `page-poradnik-pro-faq.php` |
| 12 | Kontakt | `kontakt` | `page-poradnik-pro-kontakt.php` |
| 13 | Panel Użytkownika | `panel` | `page-poradnik-pro-dashboard.php` |
| 14 | Blog | `blog` | `page-poradnik-pro-blog.php` |
| 15 | Eksperci | `eksperci` | `page-poradnik-pro-eksperci.php` |

---

## Poradnik.PRO — Strony Kategorii (10 stron)

| # | Tytuł | Slug | Routing URL |
|---|--------|------|-------------|
| 1 | Prawo — Porady Prawne | `kategoria-prawo` | `/kategoria/prawo/` |
| 2 | Finanse — Kredyty i Inwestycje | `kategoria-finanse` | `/kategoria/finanse/` |
| 3 | Nieruchomości — Kupno i Sprzedaż | `kategoria-nieruchomosci` | `/kategoria/nieruchomosci/` |
| 4 | Budownictwo — Budowa i Remont | `kategoria-budownictwo` | `/kategoria/budownictwo/` |
| 5 | Energia — OZE i Oszczędzanie | `kategoria-energia` | `/kategoria/energia/` |
| 6 | Zdrowie — Porady Medyczne | `kategoria-zdrowie` | `/kategoria/zdrowie/` |
| 7 | Edukacja — Kursy i Szkolenia | `kategoria-edukacja` | `/kategoria/edukacja/` |
| 8 | Motoryzacja — Porady Samochodowe | `kategoria-motoryzacja` | `/kategoria/motoryzacja/` |
| 9 | Technologia — IT i Innowacje | `kategoria-technologia` | `/kategoria/technologia/` |
| 10 | Dom i Ogród — Aranżacja Wnętrz | `kategoria-dom-i-ogrod` | `/kategoria/dom-i-ogrod/` |

---

## Poradnik.PRO — Strony Miast (10 stron)

| # | Tytuł | Slug | Routing URL |
|---|--------|------|-------------|
| 1 | Specjaliści — Warszawa | `warszawa-specjalisci` | `/warszawa/specjalisci/` |
| 2 | Specjaliści — Kraków | `krakow-specjalisci` | `/krakow/specjalisci/` |
| 3 | Specjaliści — Wrocław | `wroclaw-specjalisci` | `/wroclaw/specjalisci/` |
| 4 | Specjaliści — Poznań | `poznan-specjalisci` | `/poznan/specjalisci/` |
| 5 | Specjaliści — Gdańsk | `gdansk-specjalisci` | `/gdansk/specjalisci/` |
| 6 | Specjaliści — Katowice | `katowice-specjalisci` | `/katowice/specjalisci/` |
| 7 | Specjaliści — Łódź | `lodz-specjalisci` | `/lodz/specjalisci/` |
| 8 | Specjaliści — Szczecin | `szczecin-specjalisci` | `/szczecin/specjalisci/` |
| 9 | Specjaliści — Lublin | `lublin-specjalisci` | `/lublin/specjalisci/` |
| 10 | Specjaliści — Bydgoszcz | `bydgoszcz-specjalisci` | `/bydgoszcz/specjalisci/` |

---

## PT24.PRO — Strony Platformy (4 strony)

| # | Tytuł | Slug | Szablon |
|---|--------|------|---------|
| 1 | Dla Fachowców | `dla-fachowcow` | `page-pt24-dla-fachowcow.php` |
| 2 | Dodaj Zlecenie | `dodaj-zlecenie` | `page-pt24-dodaj-zlecenie.php` |
| 3 | Usługi | `uslugi` | `page-pt24-kategoria-uslugi.php` |
| 4 | Panel Fachowca | `panel-fachowca` | `page-pt24-panel-fachowca.php` |

---

## PT24.PRO — Kategorie Usług (15 stron)

| # | Usługa | Slug | Routing URL |
|---|--------|------|-------------|
| 1 | Hydraulik | `uslugi-hydraulik` | `/uslugi/hydraulik/` |
| 2 | Elektryk | `uslugi-elektryk` | `/uslugi/elektryk/` |
| 3 | Malarz | `uslugi-malarz` | `/uslugi/malarz/` |
| 4 | Stolarz | `uslugi-stolarz` | `/uslugi/stolarz/` |
| 5 | Dekarz | `uslugi-dekarz` | `/uslugi/dekarz/` |
| 6 | Murarz | `uslugi-murarz` | `/uslugi/murarz/` |
| 7 | Glazurnik | `uslugi-glazurnik` | `/uslugi/glazurnik/` |
| 8 | Instalator | `uslugi-instalator` | `/uslugi/instalator/` |
| 9 | Ogrodnik | `uslugi-ogrodnik` | `/uslugi/ogrodnik/` |
| 10 | Sprzątanie | `uslugi-sprzatanie` | `/uslugi/sprzatanie/` |
| 11 | Przeprowadzki | `uslugi-przeprowadzki` | `/uslugi/przeprowadzki/` |
| 12 | Klimatyzacja | `uslugi-klimatyzacja` | `/uslugi/klimatyzacja/` |
| 13 | Fotowoltaika | `uslugi-fotowoltaika` | `/uslugi/fotowoltaika/` |
| 14 | Pompy Ciepła | `uslugi-pompy-ciepla` | `/uslugi/pompy-ciepla/` |
| 15 | Remont | `uslugi-remont` | `/uslugi/remont/` |

---

## PT24.PRO — Miasta (12 stron)

| # | Miasto | Slug | Routing URL |
|---|--------|------|-------------|
| 1 | Warszawa | `miasto-warszawa` | `/miasto/warszawa/` |
| 2 | Kraków | `miasto-krakow` | `/miasto/krakow/` |
| 3 | Wrocław | `miasto-wroclaw` | `/miasto/wroclaw/` |
| 4 | Poznań | `miasto-poznan` | `/miasto/poznan/` |
| 5 | Gdańsk | `miasto-gdansk` | `/miasto/gdansk/` |
| 6 | Katowice | `miasto-katowice` | `/miasto/katowice/` |
| 7 | Łódź | `miasto-lodz` | `/miasto/lodz/` |
| 8 | Szczecin | `miasto-szczecin` | `/miasto/szczecin/` |
| 9 | Lublin | `miasto-lublin` | `/miasto/lublin/` |
| 10 | Bydgoszcz | `miasto-bydgoszcz` | `/miasto/bydgoszcz/` |
| 11 | Rzeszów | `miasto-rzeszow` | `/miasto/rzeszow/` |
| 12 | Białystok | `miasto-bialystok` | `/miasto/bialystok/` |

---

## Kalkulatory (5 stron)

| # | Tytuł | Slug |
|---|--------|------|
| 1 | Kalkulator kosztów remontu łazienki | `kalkulator-remont-lazienki` |
| 2 | Kalkulator rat kredytu hipotecznego | `kalkulator-kredyt-hipoteczny` |
| 3 | Kalkulator kosztów budowy domu | `kalkulator-budowa-domu` |
| 4 | Kalkulator fotowoltaiki — dobór mocy | `kalkulator-fotowoltaika` |
| 5 | Kalkulator kosztów ogrzewania | `kalkulator-ogrzewanie` |

---

## Rankingi (3 strony)

| # | Tytuł | Slug |
|---|--------|------|
| 1 | Ranking kredytów hipotecznych 2026 | `ranking-kredyty-hipoteczne` |
| 2 | Ranking ubezpieczeń OC 2026 | `ranking-ubezpieczenia-oc` |
| 3 | Ranking pomp ciepła 2026 | `ranking-pompy-ciepla` |

---

## Porównania (3 strony)

| # | Tytuł | Slug |
|---|--------|------|
| 1 | Gaz vs pompa ciepła | `porownanie-gaz-vs-pompa-ciepla` |
| 2 | OC Warta vs PZU | `porownanie-oc-warta-vs-pzu` |
| 3 | Fotowoltaika vs pompa ciepła | `porownanie-fotowoltaika-vs-pompa-ciepla` |

---

## Artykuły / Poradniki (8 postów)

| # | Tytuł | Slug | Kategoria |
|---|--------|------|-----------|
| 1 | Ile kosztuje remont łazienki w 2026 roku | `ile-kosztuje-remont-lazienki-2026` | Remont |
| 2 | Jak wybrać dobrego elektryka | `jak-wybrac-dobrego-elektryka` | Budownictwo |
| 3 | Kredyt hipoteczny 2026 — jak wybrać | `kredyt-hipoteczny-2026-jak-wybrac` | Finanse |
| 4 | Ile kosztuje budowa domu 100m² | `ile-kosztuje-budowa-domu-100m2-2026` | Budownictwo |
| 5 | Fotowoltaika 2026 — czy się opłaca | `fotowoltaika-2026-czy-sie-oplaca` | Energia |
| 6 | Ubezpieczenie OC — jak wybrać najtaniej | `ubezpieczenie-oc-samochodu-jak-wybrac` | Motoryzacja |
| 7 | Pompy ciepła 2026 — rodzaje i koszty | `pompy-ciepla-2026-rodzaje-koszty` | Energia |
| 8 | Ile kosztuje wymiana okien | `ile-kosztuje-wymiana-okien-2026` | Budownictwo |

---

## Kategorie WordPress (12)

| # | Nazwa | Slug | Opis |
|---|--------|------|------|
| 1 | Prawo | `prawo` | Porady prawne |
| 2 | Finanse | `finanse` | Kredyty, ubezpieczenia, inwestycje |
| 3 | Nieruchomości | `nieruchomosci` | Kupno, sprzedaż, wynajem |
| 4 | Budownictwo | `budownictwo` | Budowa, projekty, materiały |
| 5 | Energia | `energia` | Fotowoltaika, pompy ciepła, OZE |
| 6 | Zdrowie | `zdrowie` | Zdrowie, dieta, profilaktyka |
| 7 | Edukacja | `edukacja` | Kursy, szkolenia, rozwój |
| 8 | Motoryzacja | `motoryzacja` | Serwis, ubezpieczenia, porady |
| 9 | Technologia | `technologia` | IT, software, gadżety |
| 10 | Dom i Ogród | `dom-i-ogrod` | Meble, dekoracje, ogrody |
| 11 | Remont | `remont` | Wykończenie, remonty |
| 12 | Auto | `auto` | Porady motoryzacyjne |

---

## Podsumowanie

| Element | Ilość |
|---------|-------|
| Strony statyczne Poradnik.PRO | 15 |
| Strony kategorii | 10 |
| Strony miast (Poradnik) | 10 |
| Strony PT24.PRO | 4 |
| Kategorie usług PT24 | 15 |
| Miasta PT24 | 12 |
| Kalkulatory | 5 |
| Rankingi | 3 |
| Porównania | 3 |
| Artykuły z treścią | 8 |
| Kategorie WordPress | 12 |
| **RAZEM stron/postów** | **~85** |

---

## Notatki

- Strony korzystają z routingu URL (`poradnik-pro-routing.php` / `pt24-pro-routing.php`) — nie wymagają oddzielnych wpisów w menu
- Routing dynamiczny (miasto+kategoria, miasto+usługa) generuje strony na żywo z query vars
- Artykuły mają pełną treść SEO-friendly gotową do indeksacji
- Skrypt jest idempotentny — można uruchomić wielokrotnie bez duplikatów

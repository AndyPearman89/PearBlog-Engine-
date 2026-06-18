# 📘 PearBlog Engine — Pełna dokumentacja projektu

> Dokument nadrzędny obejmujący **całość** repozytorium: architekturę, komponenty, uruchamianie, testy, wdrożenie oraz mapę pozostałej dokumentacji.

---

## 1. Zakres projektu

PearBlog Engine to system WordPress łączący:

- **MU-plugin** (`mu-plugins/pearblog-engine`) — backend automatyzacji treści, AI, SEO, API, CLI, bezpieczeństwo, monitoring.
- **Theme** (`theme/pearblog-theme`) — frontend SEO + UX + monetyzacja + integracje domenowe (Poradnik/PT24).
- **Klienty API** (`clients/js`, `clients/python`) — integracja z zewnętrznymi automatyzacjami.
- **Skrypty operacyjne** (`scripts`) — wsparcie automatyzacji i zadań serwisowych.
- **Testy** (`mu-plugins/pearblog-engine/tests/php`, `tests`) — testy jednostkowe/integracyjne i wybrane testy narzędziowe.

---

## 2. Architektura repozytorium

## Struktura wysokopoziomowa

```text
PearBlog-Engine-/
├── mu-plugins/pearblog-engine/      # Silnik backendowy WordPress (MU-plugin)
├── theme/pearblog-theme/            # Motyw frontendowy
├── clients/                         # Klienci API (JS/Python/Next.js)
├── scripts/                         # Skrypty automatyzacji i operacyjne
├── docs/                            # Dokumentacja modułowa (quickstarty, wdrożenia, checklisty)
├── brand-assets/                    # Assety marki (logo, favicony, social)
├── tests/                           # Testy pozostałych narzędzi (np. load/python)
├── run                              # Lokalny helper komend developerskich
└── README.md                        # Główne wejście do projektu
```

### MU-plugin — główne obszary (`mu-plugins/pearblog-engine/src`)

- `Core` — bootstrap i rejestracja komponentów.
- `Pipeline` — przepływ generowania i publikacji treści.
- `AI`, `Content`, `SEO`, `Monetization` — silnik biznesowy artykułów.
- `API`, `CLI`, `Webhook`, `Security`, `Monitoring`, `Logging` — integracje i operacje.
- `DecisionPlatform`, `Poradnik`, `LeadAI`, `Distribution`, `Email`, `Social` — moduły domenowe i rozszerzenia.
- `Tenant` — kontekst multisite / wielo-instancyjny.

### Theme — główne obszary (`theme/pearblog-theme`)

- Szablony stron i wpisów (`index.php`, `single.php`, `page*.php`, `single-*.php`).
- `inc/` — moduły funkcjonalne (layout, personalizacja, monetyzacja, PT24, Poradnik, SEO, API).
- `assets/css`, `assets/js` — warstwa UI/UX i zachowania frontendu.
- `template-parts/` — reużywalne bloki prezentacji.

---

## 3. Uruchamianie i rozwój lokalny

W repozytorium dostępny jest skrypt `run`:

```bash
./run dev
./run test
./run test --filter NazwaTestu
```

- `./run dev` — szybki sanity check (m.in. składnia PHP w `mu-plugins/pearblog-engine/src`).
- `./run test` — uruchomienie testów PHPUnit (preferuje `vendor/bin/phpunit`, ma fallback na systemowy `phpunit`).

---

## 4. Testowanie i jakość

### Gdzie są testy

- `mu-plugins/pearblog-engine/tests/php/Unit`
- `mu-plugins/pearblog-engine/tests/php/Integration`
- `tests/load` (testy obciążeniowe / scenariuszowe)
- `tests/python` (testy narzędziowe)

### Jak uruchamiać

- Standardowo przez `./run test`.
- Selektywnie: `./run test --filter <NazwaTestu>`.

### Uwagi praktyczne

- Testy pluginu korzystają z bootstrapu stubującego funkcje WordPress (`tests/php/bootstrap.php`).
- Dla regexów utrzymywana jest kompatybilność z różnymi wersjami PHPUnit (wzorzec oparty o `preg_match`).

---

## 5. Wdrożenia (deployment)

Repozytorium zawiera wiele przewodników wdrożeniowych; główna ścieżka:

1. Skonfigurować sekrety GitHub Actions (SSH host/user/key + ścieżki WP).
2. Wdrożyć MU-plugin i theme na serwer WordPress.
3. Potwierdzić smoke-testy i sprawdzić logi workflow.

Najważniejsze pliki:

- `DEPLOY-RUNBOOK.md` — rekomendowany runbook.
- `SETUP.md` — setup + integracja z Actions.
- `GITHUB-SECRETS-GUIDE.md` — konfiguracja sekretów.
- `DEPLOYMENT.md` / `DEPLOYMENT-PL.md` — rozszerzone przewodniki wdrożeniowe.

---

## 6. API, CLI i automatyzacje

### API

- Endpoints i integracje opisane w `API-DOCUMENTATION.md`.
- Klienci:
  - `clients/js/README.md`
  - `clients/python/README.md`

### CLI

- Komendy CLI znajdują się w module pluginu (`src/CLI`) i dokumentacji powiązanej w repo.

### Skrypty

- `scripts/README.md` — opis użycia skryptów automatyzacyjnych.

---

## 7. Bezpieczeństwo, monitoring i operacje

Najważniejsze dokumenty operacyjne:

- `SECURITY-AUDIT-REPORT.md`
- `SECURITY-AUDIT-REPORT-DETAILED.md`
- `SECURITY-AUDIT-REMEDIATION-SUMMARY.md`
- `INCIDENT-RESPONSE.md`
- `TROUBLESHOOTING.md`
- `TROUBLESHOOTING-ENHANCED.md`
- `DISASTER-RECOVERY.md`

---

## 8. Dokumentacja funkcjonalna (Poradnik/PT24/Enterprise)

W repo jest szeroki zestaw dokumentów domenowych i produktowych:

- **Poradnik**: pliki zaczynające się od `PORADNIK-*`, `QUICKSTART-poradnik-*`, `DEPLOYMENT-poradnik-*`.
- **PT24**: pliki zaczynające się od `PT24-*`, `QUICKSTART-pt24-*`, `DEPLOYMENT-pt24-*`.
- **Enterprise / V8 / V9**: pliki `ENTERPRISE-*`, `FEATURED-PLAN-v9.0.md`, `README-ENTERPRISE-V8.md`, `CHANGELOG.md`.

Dokumenty o charakterze historycznym/planującym pozostają jako archiwum decyzji i przebiegu wdrożeń.
Punkt wejścia do archiwum: `docs/archive/README.md`.

---

## 9. Brand i assety

Folder `brand-assets/` zawiera:

- system identyfikacji marki,
- logo (w tym warianty rastrowe),
- favicony (ICO/PNG, apple-touch, mstile),
- grafiki social media.

Punkt wejścia: `brand-assets/README.md`.

---

## 10. Punkt startowy dla nowej osoby

Kolejność rekomendowana:

1. `README.md`
2. `DOCUMENTATION-INDEX.md`
3. `mu-plugins/pearblog-engine/README.md`
4. `theme/pearblog-theme/README.md`
5. `SETUP.md`
6. `DEPLOY-RUNBOOK.md`
7. `README-TESTING.md`
8. `CHANGELOG.md`

---

## 11. Status dokumentacji

Ten plik jest **nadrzędnym opisem całości** i mapą wejścia.
Szczegółowe instrukcje znajdują się w wyspecjalizowanych plikach tematycznych.

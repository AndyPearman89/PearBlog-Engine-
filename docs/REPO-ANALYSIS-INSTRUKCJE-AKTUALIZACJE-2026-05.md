# Analiza repo + instrukcje i aktualizacje (2026-05-18)

Ten dokument zbiera stan repozytorium `PearBlog-Engine-` w jednym miejscu: architekturę, sposób pracy, komendy operacyjne oraz listę rekomendowanych aktualizacji.

---

## 1) Szybka analiza repo (całość)

### Główne obszary

- `mu-plugins/pearblog-engine/` — rdzeń silnika automatyzacji (MU-plugin WordPress)
- `theme/pearblog-theme/` — warstwa frontend + integracje PT24/Poradnik
- `scripts/` — automatyzacja Python (pipeline, keywordy, SERP)
- `.github/workflows/` — CI/CD i automatyzacje uruchomień
- `docs/` i pliki `*.md` w root — dokumentacja operacyjna i wdrożeniowa

### Obraz ilościowy (snapshot)

- PHP łącznie (plugin + theme): **315 plików**
  - plugin: **216**
  - theme: **99**
- Workflow GitHub Actions: **5**
- Pliki markdown (root + poziom 2): **176**
- Testy Python (`tests/python/`): **3 pliki testowe**

### Najważniejsze workflowy

- `test.yml` — PHPUnit + pytest + PHP syntax check
- `deploy.yml` — deploy pluginu i motywu przez SSH/rsync + smoke test panelu Enterprise V8
- `content-pipeline.yml` — harmonogram i ręczne uruchamianie pipeline Python
- `deploy-pt24-from-secrets.yml` / `run-roadmap.yml` — automatyzacje dodatkowe

---

## 2) Instrukcje operacyjne (praktyczny standard pracy)

## A. Start lokalny (dev)

1. Sklonuj repo i przejdź do katalogu projektu.
2. Dla testów pluginu:
   - `cd mu-plugins/pearblog-engine`
   - `composer install --no-interaction --prefer-dist --optimize-autoloader`
3. Dla testów Python:
   - w root repo: `pip install -r scripts/requirements.txt` (jeśli plik istnieje)
   - `pip install pytest pytest-mock`

## B. Walidacja minimalna przed wdrożeniem

1. **PHP syntax check** (co najmniej obszar zmieniany)
2. **PHPUnit** (plugin)
3. **pytest** (`tests/python/`)
4. W przypadku zmian workflow/deploy: manualny przegląd `.github/workflows/*.yml`

## C. Deploy

- Produkcyjny deploy pluginu i motywu realizuje `deploy.yml`.
- Wymagane sekrety/zmienne: `SSH_HOST`, `SSH_USER`, `WP_PATH` oraz `SSH_PRIVATE_KEY` lub `SSH_PASSWORD`.
- Po deployu wykonywany jest flush cache + smoke test klasy/menu Enterprise V8.

## D. Dokumentowanie zmian

- Przy zmianie funkcjonalnej aktualizuj co najmniej:
  - `README.md` (wejście dla użytkownika)
  - dokument domenowy (np. PT24/Poradnik/Enterprise)
  - `CHANGELOG.md`
- Dla nowych procedur operacyjnych dodawaj pliki do `docs/` i linkuj z `DOCUMENTATION-INDEX.md`.

---

## 3) Stan walidacji repo na dzień analizy

### Uruchomione lokalnie

- Python tests: **PASS** (`12 passed, 11 skipped`)
- PHPUnit: **NIEPASS** (istniejące błędy/failures w repo)
- PHP syntax check: wykryte błędy składni m.in.:
  - `mu-plugins/pearblog-engine/src/Integration/ContentLinker.php`
  - `mu-plugins/pearblog-engine/src/Analytics/CohortEngine.php`

Wniosek: repo zawiera istniejące problemy testowe/jakościowe niezależne od tej aktualizacji dokumentacji.

---

## 4) Aktualizacje do wykonania (priorytety)

## P0 (krytyczne)

1. Naprawić błędy składni PHP w plikach wskazanych przez lint.
2. Ustabilizować failing tests w PHPUnit (szczególnie moduły SEO/REST i testy zależne od funkcji WordPress).

## P1 (wysokie)

1. Ujednolicić wersjonowanie dokumentacji (część plików opisuje v5/v6, podczas gdy README jest v8.0).
2. Ograniczyć duplikację dokumentów i wskazać jeden “source of truth” per domena:
   - deploy
   - testy
   - PT24
   - Poradnik
   - Enterprise

## P2 (organizacyjne)

1. Dodać cykliczny audyt dokumentacji (np. kwartalnie).
2. Wprowadzić krótkie “runbooki” operacyjne dla:
   - incydentu produkcyjnego
   - rollbacku deployu
   - awarii pipeline

---

## 5) Proponowany tryb pracy na kolejne zadania

1. Najpierw stabilizacja jakości (P0) — bez tego dalsze feature’y zwiększają dług techniczny.
2. Następnie konsolidacja dokumentacji (P1) i dopiero potem rozwój funkcjonalny.
3. Każda większa zmiana powinna kończyć się:
   - aktualizacją dokumentacji operacyjnej
   - wpisem do changelogu
   - potwierdzoną walidacją (test/lint adekwatny do zmiany)


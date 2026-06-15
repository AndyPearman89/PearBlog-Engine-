# 🚀 PEARBLOG ENGINE - MASTER DEPLOYMENT GUIDE

**Wersja:** 1.0
**Data:** 2026-05-03
**Status:** ✅ Production Ready

---

## 📋 SPIS TREŚCI

1. [Przegląd Systemu](#przegląd-systemu)
2. [Wymagania Wstępne](#wymagania-wstępne)
3. [Szybkie Wdrożenie - 3 Komendy](#szybkie-wdrożenie)
4. [Wdrożenie Enterprise V8](#wdrożenie-enterprise-v8)
5. [Wdrożenie PT24.PRO Platform](#wdrożenie-pt24pro-platform)
6. [Wdrożenie Projektów Specyficznych](#wdrożenie-projektów-specyficznych)
7. [Wdrożenie na home.pl w podkatalogu /poradnik (FULL)](#wdrożenie-na-homepl-w-podkatalogu-poradnik-full)
8. [Weryfikacja i Testy](#weryfikacja-i-testy)
9. [Troubleshooting](#troubleshooting)
10. [Maintenance](#maintenance)

---

## 🎯 PRZEGLĄD SYSTEMU

### Co zawiera PearBlog Engine?

```
┌─────────────────────────────────────────────────────────────┐
│  PEARBLOG ENGINE - COMPLETE ECOSYSTEM                       │
├─────────────────────────────────────────────────────────────┤
│  ✅ Enterprise V8 Admin (15 tabs)                           │
│  ✅ PT24 AI Lead Engine V2 (DDD architecture)              │
│  ✅ Poradnik Content Engine V2 (Revenue optimization)       │
│  ✅ Multi-tenant SaaS System                                │
│  ✅ AI Content Generation (3 providers)                     │
│  ✅ SEO Automation (GSC integration)                        │
│  ✅ Monetization Suite (AdSense, Affiliates, PT24)         │
└─────────────────────────────────────────────────────────────┘
```

### Dostępne Projekty do Wdrożenia

| Projekt | Skrypt | Czas | Dokumentacja |
|---------|--------|------|--------------|
| **PT24.PRO** | `deploy-pt24-pro.sh` + `deploy-pt24-local-services.sh` | 30 min | PT24-DEPLOYMENT-COMPLETE-GUIDE.md |
| **poradnik.pro** | `deploy-poradnik-pro.sh` | 20 min | DEPLOYMENT-poradnik-pro.md |
| **peartree.pro** | `deploy-peartree-pro.sh` | 25 min | DEPLOYMENT-peartree-pro.md |
| **po-beskidzku.pl** | `deploy-po-beskidzku-pl.sh` | 20 min | DEPLOYMENT-po-beskidzku-pl.md |
| **mucharski.pl** | `deploy-mucharski-pl.sh` | 20 min | DEPLOYMENT-mucharski-pl.md |
| **zalew-mucharski.pl** | `deploy-zalew-mucharski-pl.sh` | 20 min | DEPLOYMENT-zalew-mucharski-pl.md |

---

## 📦 WYMAGANIA WSTĘPNE

### Serwer

**Minimalne:**
- Ubuntu 20.04+ lub Debian 11+
- 2GB RAM
- 20GB dysk
- Root/sudo access

**Rekomendowane:**
- Ubuntu 22.04 LTS
- 4GB RAM
- 50GB SSD
- Dedykowany VPS/Cloud

### Oprogramowanie

**Instalowane automatycznie przez skrypty:**
- PHP 8.1+ (z wymaganymi rozszerzeniami)
- MariaDB 10.6+ lub MySQL 8.0+
- Apache2 lub Nginx
- WordPress (najnowsza wersja)
- WP-CLI
- Composer
- Python 3.9+ (dla PT24)
- Node.js 16+ (opcjonalnie)

### DNS i Domena

**Przed wdrożeniem:**
```
1. Kup domenę (np. twoja-domena.pl)
2. Skonfiguruj DNS:
   - A record: twoja-domena.pl → IP_SERWERA
   - CNAME: www.twoja-domena.pl → twoja-domena.pl
   - (Dla PT24: wildcard *.twoja-domena.pl → IP_SERWERA)
3. Poczekaj na propagację DNS (0-48h)
```

### API Keys

**Wymagane:**
- ✅ OpenAI API Key (https://platform.openai.com/api-keys)
- ✅ Google Search Console API (opcjonalnie, dla SEO)

**Opcjonalne:**
- Anthropic Claude API Key (dla lepszych wyników)
- Google Gemini API Key (alternatywa)
- Twilio Account (SMS notifications)
- SMSApi.pl Token (SMS notifications PL)

---

## ⚡ SZYBKIE WDROŻENIE - 3 KOMENDY

### Opcja A: PT24.PRO (Local Services Platform)

```bash
# 1. Sklonuj repozytorium
git clone https://github.com/AndyPearman89/PearBlog-Engine-.git
cd PearBlog-Engine-

# 2. Wdróż bazę WordPress + PearBlog
chmod +x scripts/deploy-pt24-pro.sh
./scripts/deploy-pt24-pro.sh

# 3. Dodaj platformę usług lokalnych
chmod +x scripts/deploy-pt24-local-services.sh
./scripts/deploy-pt24-local-services.sh
```

**Czas:** ~30 minut
**Rezultat:** Działająca platforma PT24.PRO z CPT, taxonomies, AI generation

---

### Opcja B: poradnik.pro (Content Platform)

```bash
# 1. Sklonuj repozytorium
git clone https://github.com/AndyPearman89/PearBlog-Engine-.git
cd PearBlog-Engine-

# 2. Wdróż wszystko w jednej komendzie
chmod +x scripts/deploy-poradnik-pro.sh
./scripts/deploy-poradnik-pro.sh
```

**Czas:** ~20 minut
**Rezultat:** Działający blog z PearBlog Engine + Poradnik features

---

### Opcja C: Własny Projekt (Generic)

```bash
# 1. Sklonuj repozytorium
git clone https://github.com/AndyPearman89/PearBlog-Engine-.git
cd PearBlog-Engine-

# 2. Skopiuj i dostosuj skrypt
cp scripts/deploy-poradnik-pro.sh scripts/deploy-moj-projekt.sh
nano scripts/deploy-moj-projekt.sh
# Zmień: DOMAIN, DB_NAME, DB_USER, DB_PASS, ADMIN_EMAIL

# 3. Uruchom
chmod +x scripts/deploy-moj-projekt.sh
./scripts/deploy-moj-projekt.sh
```

**Czas:** ~25 minut (5 min customizacji + 20 min wdrożenia)

---

## 🏢 WDROŻENIE ENTERPRISE V8

Enterprise V8 jest **automatycznie włączony** w PearBlog Engine!

### Krok 1: Sprawdź Status

```bash
# SSH do serwera
ssh root@twoj-serwer.pl

# Sprawdź czy plik istnieje
cat /var/www/twoja-domena.pl/wp-content/mu-plugins/pearblog-engine/pearblog-engine.php | grep PEARBLOG_ADMIN_VERSION
```

**Oczekiwany output:**
```php
define( 'PEARBLOG_ADMIN_VERSION', 'v8-enterprise' );
```

✅ **Jeśli widzisz tę linię** - Enterprise V8 jest włączony!
❌ **Jeśli nie ma** - dodaj ręcznie (patrz Krok 2)

---

### Krok 2: Włączenie Enterprise V8 (jeśli wyłączony)

```bash
# Edytuj plik
nano /var/www/twoja-domena.pl/wp-content/mu-plugins/pearblog-engine/pearblog-engine.php

# Dodaj po linii 23 (po define('PEARBLOG_ENGINE_URL', ...)):
define( 'PEARBLOG_ADMIN_VERSION', 'v8-enterprise' );

# Zapisz: Ctrl+X, Y, Enter
```

---

### Krok 3: Weryfikacja w WordPress Admin

```bash
# 1. Otwórz przeglądarkę
https://twoja-domena.pl/wp-admin/

# 2. Zaloguj się credentials z deployment output

# 3. Sprawdź menu:
# Powinieneś zobaczyć: "🚀 PearBlog Engine" (na górze menu)

# 4. Kliknij i sprawdź 15 zakładek:
# ✅ Dashboard Enterprise
# ✅ Real-Time Analytics
# ✅ AI Strategy
# ✅ Content Engine
# ✅ SEO Advanced
# ✅ Revenue Center
# ✅ Leads & CRM
# ✅ Automation Pro
# ✅ Analytics Deep
# ✅ Multisite/SaaS
# ✅ Performance
# ✅ Security & Audit
# ✅ Advanced Reports
# ✅ Integrations
# ✅ Settings Enterprise
```

---

### Krok 4: Konfiguracja API Keys

```bash
# W WordPress Admin:
PearBlog Engine → Settings Enterprise → API Keys

# Wpisz klucze:
OpenAI API Key: sk-proj-xxxxxxxxxxxxx
Google Search Console: (opcjonalnie)
Twilio/SMSApi: (opcjonalnie)

# Zapisz
```

---

### Krok 5: Weryfikacja Funkcjonalności

#### Test PT24 Lead Engine:
```bash
# W Admin: Leads & CRM → Add Test Lead
# Wypełnij formularz:
Name: Jan Kowalski
Email: jan@test.pl
Phone: 123456789
Category: mechanik
City: Warszawa
Message: Pilna naprawa silnika, budżet 3000 zł

# Submit → Sprawdź:
# ✅ Lead Score (powinien być ~85)
# ✅ Intent = REPAIR
# ✅ State = ROUTED
# ✅ AI analysis visible
```

#### Test Poradnik Engine:
```bash
# W Admin: Content Engine → View Articles
# Sprawdź:
# ✅ Lista artykułów z scorami
# ✅ Decision categories (SCALE/BOOST/OPTIMIZE/DELETE)
# ✅ Revenue/SEO/Engagement scores visible
```

---

## 🎯 WDROŻENIE PT24.PRO PLATFORM

### Przegląd

PT24.PRO to platforma katalogów usług lokalnych z:
- 4 Custom Post Types (categories, local pages, businesses, services)
- 3 Custom Taxonomies (cities, service categories, regions)
- AI-powered content generation (Python + OpenAI)
- 3 tabele bazodanowe (leads, stats, subscriptions)

### Wdrożenie Krok po Kroku

#### Krok 1: Bazowa Instalacja WordPress

```bash
cd /root
git clone https://github.com/AndyPearman89/PearBlog-Engine-.git
cd PearBlog-Engine-

# Uruchom bazową instalację
chmod +x scripts/deploy-pt24-pro.sh
./scripts/deploy-pt24-pro.sh
```

**Co zostanie zainstalowane:**
- ✅ WordPress + PearBlog Engine
- ✅ Apache + PHP 8.1 + MariaDB
- ✅ SSL Certificate (Let's Encrypt)
- ✅ Multisite (dla subdomen)
- ✅ Cron jobs

**Czas:** ~15 minut

**Po zakończeniu:**
```
✅ WordPress zainstalowany: https://pt24.pro/
✅ Admin URL: https://pt24.pro/wp-admin/
✅ Username: admin
✅ Password: [displayed in output]
✅ Database: pt24_pro / [password]
```

---

#### Krok 2: Platforma Usług Lokalnych

```bash
# Uruchom instalator platformy
chmod +x scripts/deploy-pt24-local-services.sh
./scripts/deploy-pt24-local-services.sh
```

**Co zostanie zainstalowane:**
- ✅ Plugin: `mu-plugins/pt24-local-services.php`
- ✅ Custom Post Types (4 typy)
- ✅ Custom Taxonomies (3 taksonomie)
- ✅ Database tables (3 tabele)
- ✅ Default data (5 kategorii + 20 miast)
- ✅ Python script: `scripts/pt24_generate_pages.py`
- ✅ Helper scripts: `pt24-generate-single.sh`, `pt24-generate-bulk.sh`

**Czas:** ~3 minuty

---

#### Krok 3: Konfiguracja OpenAI API Key

**Opcja A: Przez wp-config.php (REKOMENDOWANE)**

```bash
nano /var/www/pt24.pro/wp-config.php

# Dodaj przed linią "That's all, stop editing!":
define('PEARBLOG_OPENAI_API_KEY', 'sk-proj-xxxxxxxxxxxxxxxx');

# Zapisz: Ctrl+X, Y, Enter
```

**Opcja B: Przez WordPress Admin**

```bash
# W przeglądarce:
https://pt24.pro/wp-admin/

# Nawiguj do:
PearBlog Engine → Settings Enterprise → API Keys

# Wpisz:
OpenAI API Key: sk-proj-xxxxxxxxxxxxxxxx

# Zapisz
```

---

#### Krok 4: Generowanie Treści

**Test - Jedna Strona:**

```bash
cd /var/www/pt24.pro
./pt24-generate-single.sh mechanik warszawa
```

**Oczekiwany output:**
```
Generating page for: mechanik + Warszawa
Calling OpenAI API...
Creating WordPress post...
Success! Post ID: 123
URL: https://pt24.pro/mechanik/warszawa/
```

**Bulk Generation - 500 Stron:**

```bash
# 1. Przygotuj CSV (lub użyj przykładowego)
cat > pages.csv << 'EOF'
category,city
mechanik,Warszawa
mechanik,Kraków
mechanik,Wrocław
hydraulik,Warszawa
hydraulik,Kraków
EOF

# 2. Uruchom bulk generation
./pt24-generate-bulk.sh pages.csv
```

**Czas:** ~30 sekund/strona = ~4 godziny dla 500 stron
**Koszt:** ~$0.0009/strona = ~$0.45 dla 500 stron

---

#### Krok 5: Weryfikacja

```bash
# Test 1: Sprawdź URL rewriting
curl -I https://pt24.pro/mechanik/warszawa/
# Expected: HTTP/2 200 (not 404)

# Test 2: Sprawdź Custom Post Types
wp post list --post_type=pt24_local --path=/var/www/pt24.pro
# Expected: Lista wygenerowanych stron

# Test 3: Sprawdź taxonomies
wp term list pt24_city --path=/var/www/pt24.pro
# Expected: Lista 20 miast

# Test 4: Sprawdź tabele DB
mysql -u root -p pt24_pro -e "SHOW TABLES LIKE 'wp_pt24_%';"
# Expected:
# wp_pt24_leads
# wp_pt24_business_stats
# wp_pt24_subscriptions
```

---

## 🎨 WDROŻENIE PROJEKTÓW SPECYFICZNYCH

### poradnik.pro

```bash
# One-command deployment
./scripts/deploy-poradnik-pro.sh

# Features:
# ✅ Poradnik Engine V2 (revenue optimization)
# ✅ Landing V5 (lead capture)
# ✅ Clean Content System
# ✅ PT24 integration
```

**Dokumentacja:** `DEPLOYMENT-poradnik-pro.md`, `QUICKSTART-poradnik-pro.md`

---

### peartree.pro

```bash
# One-command deployment
./scripts/deploy-peartree-pro.sh

# Features:
# ✅ Travel content engine
# ✅ Multi-language support
# ✅ Beskidy specialization
# ✅ SEO automation
```

**Dokumentacja:** `DEPLOYMENT-peartree-pro.md`, `QUICKSTART-peartree-pro.md`

---

### po-beskidzku.pl

```bash
# One-command deployment
./scripts/deploy-po-beskidzku-pl.sh

# Features:
# ✅ Regional content (Beskidy)
# ✅ Tourist information
# ✅ Event calendar
# ✅ Local business directory
```

**Dokumentacja:** `DEPLOYMENT-po-beskidzku-pl.md`, `QUICKSTART-po-beskidzku-pl.md`

---

### mucharski.pl / zalew-mucharski.pl

```bash
# mucharski.pl
./scripts/deploy-mucharski-pl.sh

# zalew-mucharski.pl
./scripts/deploy-zalew-mucharski-pl.sh

# Features:
# ✅ Local tourism portal
# ✅ Lake/reservoir information
# ✅ Events and attractions
# ✅ Weather integration
```

**Dokumentacja:**
- `DEPLOYMENT-mucharski-pl.md`, `QUICKSTART-mucharski-pl.md`
- `DEPLOYMENT-zalew-mucharski-pl.md`, `QUICKSTART-zalew-mucharski-pl.md`

---

## 🏁 WDROŻENIE NA HOME.PL W PODKATALOGU /PORADNIK (FULL)

Ten scenariusz odpowiada wdrożeniu produkcyjnemu WordPress + PearBlog Engine pod adresem:

```text
https://wordpress2614653.home.pl/poradnik
```

### Krok 1: Konfiguracja bazy dla instancji /poradnik

W `wp-config.php` ustaw osobną bazę dla subdirectory:

```php
define('DB_NAME', '40552572_poradnik');
define('DB_USER', '40552572_poradnik');
define('DB_PASSWORD', 'Hash1989!');
define('DB_HOST', 'mysql8');

define('WP_HOME', 'https://wordpress2614653.home.pl/poradnik');
define('WP_SITEURL', 'https://wordpress2614653.home.pl/poradnik');
```

### Krok 2: Pełny upload (WordPress + MU plugin + theme + assets)

Jeśli używasz FTP (zalecane dla home.pl przy problemach z SSH), wykonaj pełną synchronizację z kasowaniem starych plików:

```bash
lftp -u "$FTP_USER","$FTP_PASS" "$FTP_HOST" <<'EOF'
set ftp:ssl-force true
set ftp:ssl-protect-data true
set ssl:verify-certificate no
mirror -R --delete --verbose ./ /poradnik/
bye
EOF
```

Po synchronizacji potwierdź obecność:
- `wp-content/mu-plugins/pearblog-engine/pearblog-engine.php`
- `wp-content/themes/pearblog-theme/functions.php`
- `wp-content/brand-assets/`

### Krok 3: Włączenie panelu Enterprise V8

W pliku MU pluginu musi istnieć:

```php
define( 'PEARBLOG_ADMIN_VERSION', 'v8-enterprise' );
```

### Krok 4: Naprawa canonical/OG dla subdirectory

Dla instalacji w podkatalogu unikaj budowy canonical bez normalizacji URI, bo może powstać błędny URL typu `/poradnik/poradnik/...`.

Zasada:
- canonical i og:url buduj względem `home_url('/')`
- normalizuj `$_SERVER['REQUEST_URI']` do ścieżki relatywnej dla `/poradnik`

### Krok 5: Head deduplikacja (WordPress vs PT24 SEO)

Jeśli używasz niestandardowego generatora meta (PT24), wyłącz domyślne tagi WP na tych stronach:
- `rel_canonical`
- `wp_site_icon`

To eliminuje duplikaty canonical, og:url i favicon w `<head>`.

### Krok 6: Weryfikacja produkcyjna

```bash
# Frontend
curl -I -L https://wordpress2614653.home.pl/poradnik/

# Admin
curl -I -L https://wordpress2614653.home.pl/poradnik/wp-admin/

# Enterprise panel assets
curl -I -L "https://wordpress2614653.home.pl/poradnik/wp-content/mu-plugins/pearblog-engine/assets/css/admin-v8-enterprise.css"
curl -I -L "https://wordpress2614653.home.pl/poradnik/wp-content/mu-plugins/pearblog-engine/assets/js/admin-v8-enterprise.js"
```

Oczekiwane: `HTTP 200`.

---

## ✅ WERYFIKACJA I TESTY

### Checklist Po Wdrożeniu

#### System:
- [x] WordPress accessible (https://domena.pl/)
- [x] Admin panel accessible (https://domena.pl/wp-admin/)
- [x] SSL certificate valid (green padlock)
- [x] PearBlog Engine visible in menu
- [x] Enterprise V8 enabled (15 tabs)

#### Database:
- [x] All tables created (check via phpMyAdmin or CLI)
- [x] No errors in WordPress debug.log
- [x] Database credentials correct

#### Enterprise V8:
- [x] Dashboard loads without errors
- [x] API keys configured
- [x] PT24 Lead Engine functional (test lead)
- [x] Poradnik Engine functional (articles visible)

#### PT24.PRO (jeśli applicable):
- [x] Custom Post Types registered
- [x] Taxonomies visible
- [x] URL rewriting works (/mechanik/warszawa/)
- [x] Content generation script works
- [x] Database tables created (wp_pt24_*)

#### Performance:
- [x] Page load time <3 seconds
- [x] No PHP errors in logs
- [x] Cron jobs running
- [x] Cache working (if configured)

---

### Testy Funkcjonalne

#### Test 1: Generowanie Artykułu

```bash
# W WordPress Admin:
PearBlog Engine → Content Engine → Generate New Article

# Wybierz:
Topic: "Jak wybrać mechanika samochodowego w Warszawie"
Industry: automotive
Language: Polish

# Submit → Sprawdź:
# ✅ Artykuł wygenerowany w <60s
# ✅ Score >60
# ✅ SEO title i meta description
# ✅ Formatted content (H2, H3, listy)
```

#### Test 2: PT24 Lead Workflow

```bash
# W WordPress Admin:
PearBlog Engine → Leads & CRM → Add New Lead

# Wypełnij:
Name: Test User
Email: test@example.com
Phone: 123456789
Category: mechanik
City: Warszawa
Budget: 2000
Message: Potrzebuję naprawy hamulców, pilne

# Submit → Sprawdź:
# ✅ Lead score calculated (powinien być ~70-80)
# ✅ Intent detected (REPAIR)
# ✅ State = ROUTED lub AI_REPLIED
# ✅ Event timeline visible
```

#### Test 3: A/B Testing

```bash
# W WordPress Admin:
PearBlog Engine → Content Engine → Select Article → Create A/B Test

# Wybierz:
Metric: CTR
Variant: Zmień title lub CTA
Duration: 7 days

# Start Test → Sprawdź:
# ✅ Test created
# ✅ Visitors split 50/50
# ✅ Stats tracking
```

---

## 🔧 TROUBLESHOOTING

### Problem 1: 404 na WordPress admin

**Symptom:** `https://domena.pl/wp-admin/` zwraca 404

**Rozwiązanie:**
```bash
# Sprawdź czy WordPress jest zainstalowany
ls -la /var/www/domena.pl/

# Sprawdź czy Apache/Nginx jest uruchomiony
systemctl status apache2
# lub
systemctl status nginx

# Sprawdź konfigurację VirtualHost
cat /etc/apache2/sites-enabled/domena.pl.conf

# Restart serwera
systemctl restart apache2
```

---

### Problem 2: Błąd bazy danych

**Symptom:** "Error establishing database connection"

**Rozwiązanie:**
```bash
# Sprawdź czy MySQL działa
systemctl status mysql

# Sprawdź credentials w wp-config.php
nano /var/www/domena.pl/wp-config.php

# Testuj połączenie
mysql -u DB_USER -p DB_NAME
# (użyj credentials z wp-config.php)

# Jeśli błąd - zresetuj hasło:
mysql -u root -p
mysql> ALTER USER 'DB_USER'@'localhost' IDENTIFIED BY 'nowe_haslo';
mysql> FLUSH PRIVILEGES;

# Update wp-config.php z nowym hasłem
```

---

### Problem 3: PT24 URL rewriting nie działa

**Symptom:** `/mechanik/warszawa/` zwraca 404

**Rozwiązanie:**
```bash
# Flush rewrite rules
wp rewrite flush --path=/var/www/pt24.pro

# Sprawdź .htaccess
cat /var/www/pt24.pro/.htaccess
# Powinien zawierać WordPress rewrite rules

# Sprawdź czy mod_rewrite jest włączony
apache2ctl -M | grep rewrite
# Expected: rewrite_module (shared)

# Jeśli nie ma - włącz:
a2enmod rewrite
systemctl restart apache2

# Sprawdź AllowOverride w VirtualHost
cat /etc/apache2/sites-enabled/pt24.pro.conf
# Powinno być: AllowOverride All
```

---

### Problem 4: OpenAI API nie działa

**Symptom:** "OpenAI API key not configured"

**Rozwiązanie:**
```bash
# Sprawdź czy klucz jest w wp-config.php
grep PEARBLOG_OPENAI_API_KEY /var/www/domena.pl/wp-config.php

# Jeśli nie ma - dodaj:
nano /var/www/domena.pl/wp-config.php
# Dodaj przed "That's all, stop editing!":
define('PEARBLOG_OPENAI_API_KEY', 'sk-proj-xxxxx');

# Testuj API:
curl https://api.openai.com/v1/models \
  -H "Authorization: Bearer sk-proj-xxxxx"
# Expected: JSON z listą modeli

# Sprawdź quota:
# https://platform.openai.com/account/billing/overview
```

---

### Problem 5: SSL Certificate nie działa

**Symptom:** "Your connection is not private" / ERR_CERT_COMMON_NAME_INVALID

**Rozwiązanie:**
```bash
# Zainstaluj certbot (jeśli nie ma)
apt install certbot python3-certbot-apache -y

# Wygeneruj certyfikat
certbot --apache -d domena.pl -d www.domena.pl

# Sprawdź status
certbot certificates

# Sprawdź auto-renewal
systemctl status certbot.timer

# Ręczne odświeżenie (test)
certbot renew --dry-run
```

---

### Problem 6: Wolna strona (load time >5s)

**Rozwiązanie:**
```bash
# Włącz object cache (Redis)
apt install redis-server php-redis -y
systemctl start redis-server
systemctl enable redis-server

# W wp-config.php dodaj:
define('WP_CACHE', true);
define('WP_REDIS_HOST', '127.0.0.1');

# Zainstaluj Redis Object Cache plugin
wp plugin install redis-cache --activate --path=/var/www/domena.pl

# Włącz w WP Admin:
wp redis enable --path=/var/www/domena.pl

# Optymalizacja bazy danych
wp db optimize --path=/var/www/domena.pl

# Włącz Cloudflare (opcjonalnie)
# - Dodaj stronę do Cloudflare
# - Zmień nameservery u rejestratora
# - Włącz proxy (orange cloud)
```

---

### Problem 7: „Brak uprawnień dostępu do wybranej strony” (Enterprise V8)

**Symptom:**
```text
https://.../wp-admin/admin.php?page=pearblog-enterprise-v8
→ Brak uprawnień dostępu do wybranej strony.
```

**Przyczyna:**
- Panel Enterprise V8 jest zarejestrowany z capability `manage_options`.
- Użytkownik zalogowany do `/poradnik` nie ma roli Administrator.

**Rozwiązanie (zalecane):**
```bash
# 1. Zaloguj się kontem administratora
# 2. Użytkownicy -> Edytuj użytkownika
# 3. Ustaw rolę: Administrator
# 4. Wyloguj / zaloguj ponownie
```

**Weryfikacja:**
```text
https://wordpress2614653.home.pl/poradnik/wp-admin/admin.php?page=pearblog-enterprise-v8
```

**Awaryjnie (gdy brak dostępu do panelu użytkowników):**
- Nadaj capability administratora w `wp_usermeta` (`wp_capabilities`, `wp_user_level=10`).
- Następnie ponownie zaloguj się do `/poradnik/wp-admin/`.

---

## 🔄 MAINTENANCE

### Backup Daily

```bash
# Cron job (dodaj do crontab)
crontab -e

# Dodaj:
0 3 * * * /usr/local/bin/backup-wordpress.sh

# Utwórz skrypt
nano /usr/local/bin/backup-wordpress.sh
```

```bash
#!/bin/bash
# Backup WordPress + Database

DATE=$(date +%Y-%m-%d)
BACKUP_DIR="/root/backups"
DOMAIN="twoja-domena.pl"
DB_NAME="db_name"
DB_USER="db_user"
DB_PASS="db_password"

mkdir -p $BACKUP_DIR

# Backup files
tar -czf $BACKUP_DIR/$DOMAIN-files-$DATE.tar.gz /var/www/$DOMAIN

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/$DOMAIN-db-$DATE.sql
gzip $BACKUP_DIR/$DOMAIN-db-$DATE.sql

# Keep only last 7 days
find $BACKUP_DIR -name "$DOMAIN-*" -mtime +7 -delete

echo "Backup completed: $DATE"
```

```bash
chmod +x /usr/local/bin/backup-wordpress.sh
```

---

### Updates

```bash
# Comenda do uruchomienia co tydzień
wp core update --path=/var/www/domena.pl
wp plugin update --all --path=/var/www/domena.pl
wp theme update --all --path=/var/www/domena.pl

# System updates
apt update && apt upgrade -y
```

---

### Monitoring

```bash
# Uptime monitoring (UptimeRobot)
# https://uptimerobot.com/
# Dodaj: https://twoja-domena.pl/

# Performance monitoring
wp pearblog health --path=/var/www/domena.pl

# Check logs
tail -f /var/www/domena.pl/wp-content/debug.log
```

---

## 📚 DOKUMENTACJA DODATKOWA

### Enterprise V8:
- `ENTERPRISE-FULL-CAPABILITIES-PL.md` - Kompletny przewodnik (polski)
- `ENTERPRISE-QUICK-REFERENCE.md` - Karta referencyjna
- `ENTERPRISE-V8-QUICKSTART.md` - Quick start (angielski)
- `ENTERPRISE-V8-COMPLETE-STATUS.md` - Status systemu

### PT24.PRO:
- `PT24-DEPLOYMENT-COMPLETE-GUIDE.md` - Kompletny guide
- `PT24-IMPLEMENTATION-COMPLETE.md` - Podsumowanie
- `PT24-PRO-PLATFORM-BLUEPRINT.md` - Architektura
- `PT24-AUTOMATION-GUIDE.md` - Automatyzacja

### Projekty Specyficzne:
- `DEPLOYMENT-[projekt].md` - Deployment guide dla każdego projektu
- `QUICKSTART-[projekt].md` - Quick start dla każdego projektu

---

## 🆘 SUPPORT

**GitHub Issues:** https://github.com/AndyPearman89/PearBlog-Engine-/issues
**Documentation:** https://github.com/AndyPearman89/PearBlog-Engine-/tree/main
**Pull Requests:** https://github.com/AndyPearman89/PearBlog-Engine-/pulls

---

## ✅ FINAL CHECKLIST

Po zakończeniu wdrożenia sprawdź:

### Podstawy:
- [x] WordPress zainstalowany i dostępny
- [x] SSL certificate aktywny (HTTPS)
- [x] Admin panel działa
- [x] Database connected

### PearBlog Engine:
- [x] Enterprise V8 enabled
- [x] 15 zakładek visible
- [x] API keys configured
- [x] No errors in logs

### Funkcjonalność:
- [x] Content generation works
- [x] PT24 lead engine works (jeśli applicable)
- [x] Poradnik scoring works (jeśli applicable)
- [x] SEO automation active

### Performance:
- [x] Page load <3s
- [x] Cache enabled
- [x] CDN configured (opcjonalnie)
- [x] Cron jobs running

### Bezpieczeństwo:
- [x] Strong passwords
- [x] Firewall enabled
- [x] Fail2ban configured
- [x] Backup system active

### Monitoring:
- [x] Uptime monitoring (UptimeRobot)
- [x] Google Analytics
- [x] Google Search Console
- [x] Error tracking

---

## 🎉 GRATULACJE!

System PearBlog Engine został wdrożony i jest gotowy do użycia!

**Co dalej?**
1. Wygeneruj pierwsze artykuły/strony
2. Skonfiguruj monetyzację (AdSense, PT24)
3. Dodaj Google Analytics i Search Console
4. Uruchom A/B testy
5. Monitoruj performance i optymalizuj

**Powodzenia!** 🚀

---

**Dokument:** WDROZENIE-MASTER-GUIDE.md
**Wersja:** 1.0
**Data:** 2026-05-03
**Autor:** Claude Sonnet 4.5 via Claude Code

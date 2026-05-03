# 🚀 PT24.PRO - COMPLETE DEPLOYMENT GUIDE

**Data:** 2026-05-03
**Status:** ✅ GOTOWE DO WDROŻENIA
**Czas:** ~30 minut

---

## 📋 WYMAGANIA

### Serwer
- Ubuntu 20.04+ lub Debian 11+
- Min 2GB RAM
- 20GB dysk
- Root/sudo access
- Domena: pt24.pro (DNS skonfigurowane)

### Oprogramowanie (instalowane automatycznie)
- PHP 8.1+
- MariaDB/MySQL
- Apache2
- WordPress
- WP-CLI
- Composer
- Python 3 + pip3

### API Keys
- OpenAI API Key (do generowania treści)

---

## 🎯 WDROŻENIE KROK PO KROKU

### Krok 1: Sklonuj Repozytorium

```bash
# Na serwerze
cd /root
git clone https://github.com/AndyPearman89/PearBlog-Engine-.git
cd PearBlog-Engine-
```

---

### Krok 2: Wdróż Bazowy WordPress + PearBlog

```bash
# Instalacja WordPress + PearBlog Engine
chmod +x scripts/deploy-pt24-pro.sh
./scripts/deploy-pt24-pro.sh
```

**Co zostanie zainstalowane:**
- ✅ WordPress (najnowsza wersja)
- ✅ PearBlog Engine v7.0
- ✅ Apache + PHP 8.1 + MariaDB
- ✅ SSL Certificate (Let's Encrypt)
- ✅ Cron jobs (automatyczna publikacja)

**Czas:** ~10-15 minut

**Po zakończeniu:**
Zapisz dane logowania (admin user/password, database credentials)!

---

### Krok 3: Wdróż PT24 Local Services Platform

```bash
# Instalacja platformy usług lokalnych
chmod +x scripts/deploy-pt24-local-services.sh
./scripts/deploy-pt24-local-services.sh
```

**Co zostanie zainstalowane:**
- ✅ Custom Post Types (pt24_local, pt24_business, pt24_service)
- ✅ Custom Taxonomies (miasta, kategorie usług)
- ✅ 3 tabele bazy danych (leads, stats, subscriptions)
- ✅ 5 kategorii usług (mechanik, hydraulik, elektryk, laweta, wulkanizacja)
- ✅ 20 największych miast Polski
- ✅ Skrypt generowania treści (Python + OpenAI)
- ✅ Przykładowe pliki CSV

**Czas:** ~2-3 minuty

---

### Krok 4: Skonfiguruj OpenAI API Key

#### Opcja A: Przez wp-config.php (REKOMENDOWANE)

```bash
nano /var/www/pt24.pro/wp-config.php
```

Znajdź linię:
```php
define('PEARBLOG_OPENAI_API_KEY', 'YOUR_OPENAI_KEY_HERE');
```

Zastąp `YOUR_OPENAI_KEY_HERE` swoim kluczem API.

#### Opcja B: Przez zmienną środowiskową

```bash
echo 'export OPENAI_API_KEY="sk-your-key-here"' >> ~/.bashrc
source ~/.bashrc
```

---

### Krok 5: Wygeneruj Pierwsze Strony

#### Test: Jedna strona

```bash
cd /var/www/pt24.pro
export OPENAI_API_KEY="sk-your-key-here"  # Jeśli nie w wp-config

./pt24-generate-single.sh mechanik warszawa
```

**Oczekiwany output:**
```
🔄 Generating: mechanik warszawa
✅ Created: mechanik warszawa (Post ID: 123)
```

**Sprawdź wynik:**
```bash
wp post list --post_type=pt24_local --allow-root
```

Albo w przeglądarce:
```
https://pt24.pro/wp-admin/edit.php?post_type=pt24_local
```

---

#### Bulk: 10 przykładowych stron

```bash
./pt24-generate-bulk.sh pt24-sample-pages.csv
```

**Zawartość pt24-sample-pages.csv:**
```csv
category,city
mechanik,warszawa
mechanik,krakow
mechanik,wroclaw
mechanik,gdansk
mechanik,poznan
hydraulik,warszawa
hydraulik,krakow
elektryk,warszawa
elektryk,krakow
laweta,warszawa
```

**Czas generowania:** ~20-30 sekund (2s delay między requestami)

**Koszt:** ~$0.01 (10 stron × GPT-4o-mini)

---

### Krok 6: Weryfikacja

#### 6.1 Sprawdź Custom Post Types

```bash
wp post-type list --allow-root | grep pt24
```

**Oczekiwany output:**
```
pt24_category    Kategorie Usług
pt24_local       Strony Lokalne
pt24_business    Firmy
pt24_service     Usługi
```

#### 6.2 Sprawdź Taxonomies

```bash
wp taxonomy list --allow-root | grep pt24
```

**Oczekiwany output:**
```
pt24_city          Miasta
pt24_service_cat   Kategorie Usług
pt24_region        Województwa
```

#### 6.3 Sprawdź Tabele Bazy Danych

```bash
wp db query "SHOW TABLES LIKE 'wp_pt24%';" --allow-root
```

**Oczekiwany output:**
```
wp_pt24_leads
wp_pt24_business_stats
wp_pt24_subscriptions
```

#### 6.4 Sprawdź Wygenerowane Strony

```bash
wp post list --post_type=pt24_local --fields=ID,post_title,post_status --allow-root
```

#### 6.5 Test w Przeglądarce

Otwórz:
- Strona główna: `https://pt24.pro`
- Admin panel: `https://pt24.pro/wp-admin`
- Przykładowa strona: `https://pt24.pro/?post_type=pt24_local&p=[ID]`

⚠️ **Uwaga:** Rewrite rules mogą wymagać flush:
```bash
wp rewrite flush --allow-root
```

Potem test URL:
```
https://pt24.pro/mechanik/warszawa/
```

---

## 🎨 SKALOWANIE: 500 STRON W 1 GODZINĘ

### Krok 1: Przygotuj Plik CSV

```bash
cd /var/www/pt24.pro
nano pt24-bulk-500.csv
```

**Przykład (5 kategorii × 100 miast = 500 stron):**
```csv
category,city
mechanik,warszawa
mechanik,krakow
mechanik,lodz
mechanik,wroclaw
...
hydraulik,warszawa
hydraulik,krakow
...
```

**Lub użyj gotowej listy 100 miast z dokumentacji:**
Zobacz: `PT24-SEO-PHRASES.md`

### Krok 2: Uruchom Bulk Generation

```bash
# Rate limit: 2 sekundy między requestami
# 500 stron = ~17 minut
./pt24-generate-bulk.sh pt24-bulk-500.csv
```

**Alternatywnie (szybciej, ale wyższy koszt API):**
```bash
python3 scripts/pt24_generate_pages.py \
    --csv pt24-bulk-500.csv \
    --rate-limit 1  # 1 sekunda
```

### Krok 3: Monitor Progress

W osobnym terminalu:
```bash
watch -n 5 'wp post list --post_type=pt24_local --format=count --allow-root'
```

---

## 💰 KOSZTY GENEROWANIA

### OpenAI GPT-4o-mini

**Ceny:**
- Input: $0.150 / 1M tokens
- Output: $0.600 / 1M tokens

**Jedna strona:**
- ~1,500 tokens output
- Koszt: $0.0009 (~0.004 zł)

**500 stron:**
- Koszt: **$0.45** (~1.80 zł)

**1000 stron:**
- Koszt: **$0.90** (~3.60 zł)

🎉 **Niesamowicie tanie!**

---

## 🛠️ TROUBLESHOOTING

### Problem: "OpenAI API Key not found"

**Rozwiązanie:**
```bash
# Sprawdź czy klucz jest ustawiony
grep PEARBLOG_OPENAI_API_KEY /var/www/pt24.pro/wp-config.php

# Lub ustaw przez env var
export OPENAI_API_KEY="sk-your-key"
```

---

### Problem: "wp: command not found"

**Rozwiązanie:**
```bash
# Zainstaluj WP-CLI
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp

# Sprawdź
wp --version
```

---

### Problem: "Permission denied"

**Rozwiązanie:**
```bash
# Napraw uprawnienia
sudo chown -R www-data:www-data /var/www/pt24.pro
sudo chmod -R 755 /var/www/pt24.pro
```

---

### Problem: "Database connection error"

**Rozwiązanie:**
```bash
# Sprawdź MySQL
sudo systemctl status mysql

# Restart jeśli potrzeba
sudo systemctl restart mysql

# Test połączenia
mysql -u pt24_user -p pt24_db
```

---

### Problem: URL nie działa (/mechanik/warszawa/ → 404)

**Rozwiązanie:**
```bash
# Flush rewrite rules
wp rewrite flush --allow-root

# Sprawdź czy Apache mod_rewrite jest włączony
sudo a2enmod rewrite
sudo systemctl restart apache2

# Sprawdź .htaccess
cat /var/www/pt24.pro/.htaccess
```

---

### Problem: "Rate limit exceeded" (OpenAI)

**Rozwiązanie:**
```bash
# Zwiększ delay między requestami
python3 scripts/pt24_generate_pages.py \
    --csv pages.csv \
    --rate-limit 5  # 5 sekund
```

---

### Problem: Python import error

**Rozwiązanie:**
```bash
# Zainstaluj openai package
pip3 install openai

# Lub z sudo
sudo pip3 install openai
```

---

## 📊 MONITORING & STATYSTYKI

### Ile stron wygenerowałeś?

```bash
wp post list --post_type=pt24_local --format=count --allow-root
```

### Lista wszystkich stron

```bash
wp post list --post_type=pt24_local \
    --fields=ID,post_title,post_date \
    --orderby=date --order=DESC \
    --allow-root
```

### Statystyki per kategoria

```bash
wp term list pt24_service_cat --format=table --allow-root
```

### Statystyki per miasto

```bash
wp term list pt24_city --format=table --allow-root
```

### Sprawdź quality score

```bash
wp post list --post_type=pt24_local \
    --meta_key=pt24_quality_score \
    --fields=ID,post_title,meta_value \
    --allow-root
```

---

## 🚀 NASTĘPNE KROKI

### 1. Wygeneruj 50-100 Stron (Dzisiaj)

```bash
# Najpopularniejsze miasta
./pt24-generate-bulk.sh pt24-top-50.csv
```

### 2. Dodaj Pierwsze Profile Firm (Jutro)

```bash
# Przez admin panel
https://pt24.pro/wp-admin/post-new.php?post_type=pt24_business
```

### 3. Skonfiguruj Monetyzację (Ten Tydzień)

- Dodaj Stripe/PayU integration
- Ustaw plany cenowe (Free/PRO/PREMIUM)
- Stwórz dashboard dla firm

### 4. Skaluj do 500+ Stron (Następny Tydzień)

```bash
# 5 kategorii × 100 miast
./pt24-generate-bulk.sh pt24-full-500.csv
```

### 5. Marketing & SEO (Za 2 Tygodnie)

- Google Search Console
- Google Analytics
- Backlinks
- Social media

---

## 📞 SUPPORT

**Dokumentacja:**
- Blueprint: `PT24-PRO-PLATFORM-BLUEPRINT.md`
- SEO: `PT24-SEO-PHRASES.md`
- Implementation: `PT24-URL-STRUCTURE-IMPLEMENTATION.md`
- Automation: `PT24-AUTOMATION-GUIDE.md`
- Quick Start: `PT24-QUICK-START-SUMMARY.md`

**Repository:**
https://github.com/AndyPearman89/PearBlog-Engine-

**Issues:**
https://github.com/AndyPearman89/PearBlog-Engine-/issues

---

## ✅ CHECKLIST WDROŻENIA

### Pre-Deployment
- [ ] Domena zakupiona i skonfigurowana
- [ ] Serwer gotowy (Ubuntu/Debian)
- [ ] Root/sudo access
- [ ] OpenAI API Key ready

### Deployment
- [ ] ./scripts/deploy-pt24-pro.sh
- [ ] ./scripts/deploy-pt24-local-services.sh
- [ ] API key skonfigurowany
- [ ] Test: wygenerowano 1 stronę
- [ ] Test: bulk 10 stron

### Post-Deployment
- [ ] Rewrite rules działają
- [ ] URL /mechanik/warszawa/ działa
- [ ] Admin panel accessible
- [ ] SSL certificate active

### Content
- [ ] 50 stron lokalnych
- [ ] 5 profili firm (demo)
- [ ] Strona główna
- [ ] FAQ, kontakt, cennik

### Production
- [ ] Google Analytics
- [ ] Google Search Console
- [ ] Backup system
- [ ] Monitoring (UptimeRobot)

---

## 🎯 SUKCES!

Po wykonaniu wszystkich kroków masz:

✅ **Działającą platformę** pt24.pro
✅ **Custom Post Types** dla katalogów
✅ **Automatyzację** generowania treści
✅ **Strukturę** pod 1000+ stron
✅ **Infrastrukturę** monetyzacji
✅ **Gotowy** do skalowania biznes

---

**Powodzenia! 🚀**

**Wersja:** 1.0
**Data:** 2026-05-03
**Status:** ✅ Production Ready

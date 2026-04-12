# PearBlog Engine — Przewodnik Wdrożenia Produkcyjnego

> **Wersja:** 6.0.0  
> **Odbiorcy:** Administratorzy systemów, inżynierowie DevOps, programiści WordPress  
> **Wymagania wstępne:** Dostęp root lub sudo do serwera, dostęp SSH

---

## Spis Treści

1. [Wymagania Systemowe](#1-wymagania-systemowe)
2. [Lista Kontrolna Przed Wdrożeniem](#2-lista-kontrolna-przed-wdrożeniem)
3. [Zmienne Środowiskowe i Sekrety](#3-zmienne-środowiskowe-i-sekrety)
4. [Konfiguracja Serwera](#4-konfiguracja-serwera)
   - 4.1 [Apache](#41-apache)
   - 4.2 [Nginx](#42-nginx)
5. [Konfiguracja SSL / HTTPS](#5-konfiguracja-ssl--https)
6. [Metody Wdrożenia](#6-metody-wdrożenia)
   - 6.1 [Wdrożenie przez Git + SSH (zalecane)](#61-wdrożenie-przez-git--ssh-zalecane)
   - 6.2 [Wdrożenie przez GitHub Actions (CI/CD)](#62-wdrożenie-przez-github-actions-cicd)
   - 6.3 [Wdrożenie przez WP-CLI](#63-wdrożenie-przez-wp-cli)
   - 6.4 [Wdrożenie przez FTP / SFTP](#64-wdrożenie-przez-ftp--sftp)
7. [Przykłady Hostingowe](#7-przykłady-hostingowe)
   - 7.1 [SiteGround (Shared / Cloud)](#71-siteground-shared--cloud)
   - 7.2 [Kinsta (Managed WordPress)](#72-kinsta-managed-wordpress)
   - 7.3 [DigitalOcean (VPS / Droplet)](#73-digitalocean-vps--droplet)
   - 7.4 [WP Engine (Managed WordPress)](#74-wp-engine-managed-wordpress)
8. [Weryfikacja Po Wdrożeniu](#8-weryfikacja-po-wdrożeniu)
9. [Wycofywanie Wdrożenia](#9-wycofywanie-wdrożenia)
10. [Rozwiązywanie Problemów](#10-rozwiązywanie-problemów)
11. [Wzmocnienie Bezpieczeństwa](#11-wzmocnienie-bezpieczeństwa)
12. [Strojenie Wydajności](#12-strojenie-wydajności)

---

## 1. Wymagania Systemowe

### Minimalne Wymagania

| Komponent | Minimum | Zalecane |
|-----------|---------|------------|
| **PHP** | 8.0 | 8.2+ |
| **MySQL** | 5.7 | 8.0+ |
| **MariaDB** | 10.4 | 10.6+ |
| **WordPress** | 6.0 | 6.4+ |
| **Serwer WWW** | Apache 2.4 / Nginx 1.18 | Apache 2.4.50+ / Nginx 1.24+ |
| **Limit Pamięci PHP** | 256 MB | 512 MB |
| **Maksymalny Czas Wykonania** | 120 s | 300 s |
| **Maksymalny Rozmiar Uploadu** | 32 MB | 64 MB |
| **Miejsce na Dysku** | 2 GB | 10 GB+ |

### Wymagane Rozszerzenia PHP

```
php-curl
php-json
php-mbstring
php-xml
php-zip
php-gd        (do przetwarzania obrazów)
php-intl      (do wielojęzycznego SEO)
php-openssl
```

Weryfikacja załadowanych rozszerzeń:

```bash
php -m | grep -E 'curl|json|mbstring|xml|zip|gd|intl|openssl'
```

### Opcjonalne Rozszerzenia PHP

| Rozszerzenie | Zastosowanie |
|--------------|-------------|
| `php-redis` | Backend pamięci podręcznej obiektów (poprawia wydajność) |
| `php-imagick` | Wyższa jakość przetwarzania obrazów |
| `php-apcu` | Pamięć podręczna opcodu w procesie |

### Wymagania Konfiguracyjne WordPress

Następujące stałe `wp-config.php` muszą być ustawione przed aktywacją:

```php
// Zwiększ limit pamięci (wymagane dla pipeline AI)
define( 'WP_MEMORY_LIMIT', '512M' );

// Pozwól cron działać niezawodnie
define( 'DISABLE_WP_CRON', false );

// Wymagane dla komend autopilota WP-CLI
define( 'WP_CLI_ALLOW_ROOT', true ); // tylko jeśli uruchamiasz jako root
```

---

## 2. Lista Kontrolna Przed Wdrożeniem

Wypełnij każdy punkt przed wdrożeniem na produkcję.

### Infrastruktura

- [ ] Serwer spełnia wymagania PHP 8.0+
- [ ] MySQL 5.7+ / MariaDB 10.4+ zainstalowane i działające
- [ ] WordPress 6.0+ zainstalowany
- [ ] Certyfikat SSL uzyskany (Let's Encrypt lub komercyjny)
- [ ] Rekordy DNS A/AAAA wskazują na właściwy IP serwera
- [ ] Firewall zezwala na porty 80 (HTTP) i 443 (HTTPS)
- [ ] Dostęp SSH skonfigurowany i przetestowany
- [ ] System kopii zapasowych zweryfikowany (baza danych + pliki)

### Konfiguracja PHP

- [ ] `memory_limit = 512M` w `php.ini`
- [ ] `max_execution_time = 300` w `php.ini`
- [ ] `upload_max_filesize = 64M` w `php.ini`
- [ ] `post_max_size = 64M` w `php.ini`
- [ ] Wymagane rozszerzenia PHP zainstalowane (patrz §1)
- [ ] OPcache włączony (`opcache.enable=1`)

### WordPress

- [ ] WordPress core jest aktualny (najnowsze wydanie minor)
- [ ] Wszystkie inne wtyczki przetestowane pod kątem kompatybilności
- [ ] `wp-config.php` ma `WP_MEMORY_LIMIT` ustawiony na `512M`
- [ ] WP-Cron jest funkcjonalny (`wp cron event list` zwraca zdarzenia)
- [ ] Struktura bezpośrednich odnośników ustawiona (nie plain)
- [ ] Email administratora skonfigurowany i dostarczalny

### Klucze API i Poświadczenia

- [ ] Klucz API OpenAI uzyskany i zasilony
- [ ] Limit użycia OpenAI ustawiony ($50–100/miesiąc zalecane)
- [ ] (Opcjonalnie) Klucz Stability AI do generowania obrazów
- [ ] (Opcjonalnie) Klucz API Mailchimp / ConvertKit do digestów email
- [ ] (Opcjonalnie) Poświadczenia aplikacji social media (Twitter/X, Facebook, LinkedIn)
- [ ] (Opcjonalnie) URL webhooków Slack/Discord do alertów monitorowania

### Bezpieczeństwo

- [ ] `wp-config.php` jest poza web rootem lub chroniony regułami serwera
- [ ] Prefiks tabel bazy danych WordPress zmieniony z domyślnego `wp_`
- [ ] Uprawnienia plików prawidłowe (patrz §11)
- [ ] `WP_DEBUG` wyłączony (`define('WP_DEBUG', false)`)

---

## 3. Zmienne Środowiskowe i Sekrety

PearBlog Engine odczytuje wrażliwe wartości z opcji WordPress (ustawianych przez UI Admina lub stałe `wp-config.php`). Wszystkie sekrety powinny być przechowywane jako **GitHub Actions Secrets** przy wdrożeniu CI/CD.

### Opcje WordPress (Admin UI → PearBlog Engine)

| Klucz opcji | Opis | Wymagane |
|-------------|------|----------|
| `pearblog_openai_api_key` | Klucz API OpenAI | **Tak** |
| `pearblog_industry` | Nisza / branża treści | **Tak** |
| `pearblog_tone` | Ton pisania (profesjonalny, casualowy…) | **Tak** |
| `pearblog_publish_rate` | Artykuły na godzinę (float, np. `0.5`) | **Tak** |
| `pearblog_language` | Kod języka ISO (`en`, `pl`, `de`) | **Tak** |
| `pearblog_autonomous_mode` | Włącz autonomiczny pipeline cron (`1`/`0`) | **Tak** |
| `pearblog_adsense_publisher_id` | ID Wydawcy Google AdSense | Nie |
| `pearblog_alert_slack_webhook` | URL przychodzącego webhooka Slack | Nie |
| `pearblog_alert_discord_webhook` | URL webhooka Discord | Nie |
| `pearblog_alert_email` | Adres email dla alertów monitorowania | Nie |
| `pearblog_mailchimp_api_key` | Klucz API Mailchimp | Nie |
| `pearblog_convertkit_api_key` | Klucz API ConvertKit | Nie |

### Stałe wp-config.php (alternatywa / nadpisanie)

Umieszczenie kluczy API bezpośrednio w `wp-config.php` ma pierwszeństwo przed wartościami UI Admina i trzyma sekrety poza bazą danych:

```php
// ── Sekrety PearBlog Engine ──────────────────────────────────────────────────
define( 'PEARBLOG_OPENAI_API_KEY',   getenv( 'PEARBLOG_OPENAI_API_KEY' ) ?: '' );
define( 'PEARBLOG_ALERT_EMAIL',      'ops@przyklad.pl' );
define( 'PEARBLOG_ALERT_SLACK',      getenv( 'PEARBLOG_ALERT_SLACK' ) ?: '' );
```

> **Uwaga bezpieczeństwa:** Nigdy nie commituj prawdziwych kluczy API do systemu kontroli wersji. Używaj zmiennych środowiskowych wstrzykiwanych w czasie uruchomienia (np. przez systemd `EnvironmentFile`, pliki env Dockera lub menedżer zmiennych środowiskowych panelu hostingowego).

### GitHub Actions Secrets (do wdrożenia CI/CD)

| Sekret | Opis |
|--------|------|
| `SSH_HOST` | Hostname lub IP serwera |
| `SSH_USER` | Użytkownik SSH (`deploy`, `www-data` itp.) |
| `SSH_PRIVATE_KEY` | Pełna zawartość klucza prywatnego (`-----BEGIN OPENSSH...`) |
| `WP_PATH` | Bezwzględna ścieżka do katalogu głównego WordPress na serwerze |
| `SSH_PORT` | Port SSH (domyślnie `22`) |

---

## 4. Konfiguracja Serwera

### 4.1 Apache

#### VirtualHost (przekierowanie HTTP → HTTPS)

```apacheconf
<VirtualHost *:80>
    ServerName  przyklad.pl
    ServerAlias www.przyklad.pl
    Redirect    permanent / https://przyklad.pl/
</VirtualHost>
```

#### VirtualHost (HTTPS / WordPress)

```apacheconf
<VirtualHost *:443>
    ServerName  przyklad.pl
    ServerAlias www.przyklad.pl
    DocumentRoot /var/www/przyklad.pl/public

    SSLEngine on
    SSLCertificateFile    /etc/letsencrypt/live/przyklad.pl/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/przyklad.pl/privkey.pem

    <Directory /var/www/przyklad.pl/public>
        Options       -Indexes +FollowSymLinks
        AllowOverride All
        Require       all granted
    </Directory>

    # Proxy PHP-FPM (dostosuj ścieżkę gniazda / port do swojej konfiguracji)
    <FilesMatch "\.php$">
        SetHandler "proxy:unix:/run/php/php8.2-fpm.sock|fcgi://localhost"
    </FilesMatch>

    # Zwiększ limity uploadu i wykonania dla pipeline AI
    <IfModule mod_php.c>
        php_value memory_limit        512M
        php_value max_execution_time  300
        php_value upload_max_filesize 64M
        php_value post_max_size       64M
    </IfModule>

    # Chroń wp-config.php
    <Files wp-config.php>
        Require all denied
    </Files>

    # Blokuj bezpośrednie wykonanie PHP w uploads
    <Directory /var/www/przyklad.pl/public/wp-content/uploads>
        <FilesMatch "\.php$">
            Require all denied
        </FilesMatch>
    </Directory>

    ErrorLog  /var/log/apache2/przyklad.pl-error.log
    CustomLog /var/log/apache2/przyklad.pl-access.log combined
</VirtualHost>
```

#### Wymagane moduły Apache

```bash
a2enmod rewrite ssl proxy proxy_fcgi setenvif headers expires deflate
systemctl restart apache2
```

#### WordPress `.htaccess`

Umieść w głównym katalogu WordPress:

```apacheconf
# BEGIN WordPress
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteRule ^index\.php$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /index.php [L]
</IfModule>
# END WordPress
```

---

### 4.2 Nginx

#### Główny blok serwera

```nginx
# Przekierowanie HTTP → HTTPS
server {
    listen      80;
    listen      [::]:80;
    server_name przyklad.pl www.przyklad.pl;
    return      301 https://przyklad.pl$request_uri;
}

# HTTPS WordPress
server {
    listen      443 ssl http2;
    listen      [::]:443 ssl http2;
    server_name przyklad.pl www.przyklad.pl;
    root        /var/www/przyklad.pl/public;
    index       index.php;

    # SSL
    ssl_certificate     /etc/letsencrypt/live/przyklad.pl/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/przyklad.pl/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         HIGH:!aNULL:!MD5;
    ssl_session_cache   shared:SSL:10m;
    ssl_session_timeout 10m;

    # Nagłówki bezpieczeństwa
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options            DENY always;
    add_header X-Content-Type-Options     nosniff always;
    add_header Referrer-Policy            strict-origin-when-cross-origin always;

    # WordPress permalinks
    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    # PHP-FPM
    location ~ \.php$ {
        include        fastcgi_params;
        fastcgi_pass   unix:/run/php/php8.2-fpm.sock;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;

        # Zwiększ limity czasu dla pipeline AI
        fastcgi_read_timeout  300;
        fastcgi_send_timeout  300;
    }

    # Blokuj PHP w uploads
    location ~* /wp-content/uploads/.*\.php$ {
        deny all;
    }

    # Chroń wrażliwe pliki
    location ~* /(wp-config\.php|\.env|\.git) {
        deny all;
    }

    # Cache'owanie plików statycznych
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2?)$ {
        expires    30d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    error_log  /var/log/nginx/przyklad.pl-error.log;
    access_log /var/log/nginx/przyklad.pl-access.log;
}
```

#### Strojenie puli PHP-FPM (`/etc/php/8.2/fpm/pool.d/www.conf`)

```ini
[www]
user  = www-data
group = www-data

listen = /run/php/php8.2-fpm.sock

pm                   = dynamic
pm.max_children      = 20
pm.start_servers     = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 8
pm.max_requests      = 500

; Nadpisania PearBlog Engine
php_value[memory_limit]        = 512M
php_value[max_execution_time]  = 300
php_value[upload_max_filesize] = 64M
php_value[post_max_size]       = 64M
```

Przeładuj po zmianach:

```bash
systemctl reload php8.2-fpm nginx
```

---

## 5. Konfiguracja SSL / HTTPS

### Opcja A — Let's Encrypt (Certbot, darmowy)

```bash
# Zainstaluj certbot + wtyczkę serwera WWW
apt install certbot python3-certbot-nginx   # dla Nginx
# lub
apt install certbot python3-certbot-apache  # dla Apache

# Uzyskaj certyfikat (zamień na swoją domenę)
certbot --nginx  -d przyklad.pl -d www.przyklad.pl
# lub
certbot --apache -d przyklad.pl -d www.przyklad.pl

# Automatyczne odnawianie (dodawane automatycznie; zweryfikuj)
systemctl status certbot.timer
```

Testowe odnowienie bez faktycznego odnawiania:

```bash
certbot renew --dry-run
```

### Opcja B — Certyfikat Komercyjny

1. Wygeneruj CSR na serwerze:
   ```bash
   openssl req -new -newkey rsa:4096 -nodes \
     -keyout /etc/ssl/private/przyklad.pl.key \
     -out    /etc/ssl/certs/przyklad.pl.csr
   ```
2. Wyślij CSR do swojego CA (DigiCert, Sectigo itp.).
3. Pobierz wydane pliki certyfikatu.
4. Odwołaj się do nich w swoim wirtualnym hoście (`SSLCertificateFile` / `ssl_certificate`).

### Wymuszenie HTTPS w WordPress

Dodaj do `wp-config.php` po tym, jak WordPress jest za SSL:

```php
define( 'FORCE_SSL_ADMIN', true );

// Jeśli używasz reverse proxy / load balancera kończącego SSL:
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) &&
     'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
    $_SERVER['HTTPS'] = 'on';
}
```

---

## 6. Metody Wdrożenia

### 6.1 Wdrożenie przez Git + SSH (zalecane)

Ta metoda klonuje repozytorium na serwerze i używa hooka post-receive do automatycznego wdrożenia.

#### Jednorazowa Konfiguracja Serwera

```bash
# 1. Utwórz katalog wdrożenia
mkdir -p /var/www/przyklad.pl/repo
cd       /var/www/przyklad.pl/repo
git init --bare

# 2. Utwórz hook post-receive
cat > hooks/post-receive << 'EOF'
#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="/var/www/przyklad.pl/repo"
WP_PATH="/var/www/przyklad.pl/public"
BRANCH="main"

while read oldrev newrev ref; do
  if [[ "$ref" == "refs/heads/$BRANCH" ]]; then
    echo "Wdrażam gałąź $BRANCH..."

    # Checkout mu-plugin
    git --work-tree="$WP_PATH/wp-content/mu-plugins" \
        --git-dir="$REPO_DIR" \
        checkout -f "$BRANCH" -- mu-plugins/pearblog-engine/

    # Checkout motywu
    git --work-tree="$WP_PATH/wp-content/themes" \
        --git-dir="$REPO_DIR" \
        checkout -f "$BRANCH" -- theme/pearblog-theme/

    # Wyczyść cache
    wp --path="$WP_PATH" cache flush --allow-root 2>/dev/null || true

    echo "Wdrożenie zakończone."
  fi
done
EOF
chmod +x hooks/post-receive
```

#### Z maszyny deweloperskiej

```bash
# Dodaj serwer jako zdalne repozytorium (jednorazowo)
git remote add production ssh://deploy@ip-serwera/var/www/przyklad.pl/repo

# Wdrożenie
git push production main
```

---

### 6.2 Wdrożenie przez GitHub Actions (CI/CD)

Repozytorium zawiera `.github/workflows/deploy.yml`. Skonfiguruj następujące sekrety w **Settings → Secrets and variables → Actions**:

| Sekret | Przykładowa Wartość |
|--------|---------------------|
| `SSH_HOST` | `185.23.45.67` |
| `SSH_USER` | `deploy` |
| `SSH_PRIVATE_KEY` | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `WP_PATH` | `/var/www/html` |
| `SSH_PORT` | `22` |

#### Tworzenie Klucza SSH do Wdrożenia

```bash
# Na swojej lokalnej maszynie:
ssh-keygen -t ed25519 -C "github-actions-deploy" -f ~/.ssh/pearblog_deploy -N ""

# Autoryzuj klucz publiczny na serwerze:
ssh-copy-id -i ~/.ssh/pearblog_deploy.pub deploy@ip-serwera

# Skopiuj zawartość klucza prywatnego do sekretu SSH_PRIVATE_KEY:
cat ~/.ssh/pearblog_deploy
```

#### Wyzwolenie Wdrożenia

- **Automatycznie:** przy każdym pushu do `main` dotykającym `mu-plugins/**` lub `theme/**`.
- **Ręcznie:** Actions → *Deploy to WordPress Server* → **Run workflow**.

---

### 6.3 Wdrożenie przez WP-CLI

Przydatne do szybkich aktualizacji na pojedynczym serwerze z zainstalowanym WP-CLI.

```bash
SSH_USER=deploy
SSH_HOST=ip-serwera
WP_PATH=/var/www/html

# Prześlij pliki
rsync -avz --delete \
  mu-plugins/pearblog-engine/ \
  "$SSH_USER@$SSH_HOST:$WP_PATH/wp-content/mu-plugins/pearblog-engine/"

rsync -avz --delete \
  theme/pearblog-theme/ \
  "$SSH_USER@$SSH_HOST:$WP_PATH/wp-content/themes/pearblog-theme/"

# Wyczyszczenie cache po wdrożeniu
ssh "$SSH_USER@$SSH_HOST" \
  "wp --path=$WP_PATH cache flush && wp --path=$WP_PATH cron event run --due-now"
```

#### Weryfikacja Statusu MU-Plugin

```bash
ssh "$SSH_USER@$SSH_HOST" \
  "wp --path=$WP_PATH plugin list --fields=name,status | grep pearblog"
```

Oczekiwany wynik:

```
pearblog-engine    must-use
```

---

### 6.4 Wdrożenie przez FTP / SFTP

> FTP **nie jest zalecane** dla produkcji. Preferuj SSH/rsync kiedy tylko jest to możliwe. Używaj SFTP tylko gdy dostęp SSH jest niedostępny.

#### Z FileZilla

1. Otwórz FileZilla → **Plik → Menedżer stron → Nowa strona**.
2. Protokół: **SFTP – SSH File Transfer Protocol**.
3. Host: `ip-serwera`, Port: `22`.
4. Typ logowania: Plik klucza → wskaż swój klucz prywatny.
5. Połącz.
6. Przejdź do `/wp-content/mu-plugins/` i prześlij `mu-plugins/pearblog-engine/`.
7. Przejdź do `/wp-content/themes/` i prześlij `theme/pearblog-theme/`.

#### Z lftp (CLI)

```bash
lftp -u "$FTP_USER,$FTP_PASS" sftp://ip-serwera << EOF
mirror -R mu-plugins/pearblog-engine \
          /wp-content/mu-plugins/pearblog-engine
mirror -R theme/pearblog-theme \
          /wp-content/themes/pearblog-theme
quit
EOF
```

---

## 7. Przykłady Hostingowe

### 7.1 SiteGround (Shared / Cloud)

Konta SiteGround działają na cPanel z dostępem SSH i WP-CLI dostępnym na planach Growth i wyższych.

#### Włącz SSH

1. cPanel → **SSH Access** → **Manage SSH Keys** → Wygeneruj nowy klucz.
2. Autoryzuj klucz.
3. Pobierz klucz prywatny; skopiuj do swojego agenta SSH.

#### Wersja PHP

cPanel → **PHP Manager** → Wybierz **PHP 8.2**.

Ustaw opcje PHP przez **PHP Manager → Switch to PHP Options**:

```
memory_limit = 512M
max_execution_time = 300
```

#### Wdrożenie

```bash
ssh "$SG_USER@$SG_HOST" 'mkdir -p ~/public_html/wp-content/mu-plugins'

rsync -avz mu-plugins/pearblog-engine/ \
  "$SG_USER@$SG_HOST:~/public_html/wp-content/mu-plugins/pearblog-engine/"

rsync -avz theme/pearblog-theme/ \
  "$SG_USER@$SG_HOST:~/public_html/wp-content/themes/pearblog-theme/"

ssh "$SG_USER@$SG_HOST" \
  "wp --path=~/public_html cache flush"
```

#### WP-Cron

SiteGround może blokować WP-Cron na hostingu współdzielonym. Zastąp go prawdziwym cronem:

```bash
# W cPanel → Cron Jobs, dodaj:
*/5 * * * *  wget -q -O /dev/null \
  "https://przyklad.pl/wp-cron.php?doing_wp_cron" > /dev/null 2>&1
```

I w `wp-config.php`:

```php
define( 'DISABLE_WP_CRON', true );
```

---

### 7.2 Kinsta (Managed WordPress)

Kinsta zapewnia SSH, SFTP, WP-CLI i wdrożenia oparte na git przez MyKinsta.

#### Dane Połączenia SSH

MyKinsta → **Environment → Info → SSH/SFTP Credentials**.

```bash
ssh "$KINSTA_USER@$KINSTA_HOST" -p "$KINSTA_PORT"
```

#### Wdrożenia Git przez MyKinsta

1. MyKinsta → **Environment → Git** → Połącz repozytorium GitHub.
2. Ustaw **Deploy branch** na `main`.
3. Dodaj **Post-deployment script** (pod MyKinsta → Environment → Git):

```bash
wp cache flush
wp cron event run --due-now
```

#### Limity PHP Kinsta

Kinsta używa Nginx + PHP-FPM na Google Cloud. Limity PHP są zarządzane per-środowisko:

MyKinsta → **Sites → Nazwa strony → Environment → Tools → PHP Engine** → ustaw PHP 8.2.

Limity pamięci / czasu są ustawiane przez Kinsta (256 MB / 300 s). Dla pipeline AI, złóż prośbę do wsparcia o zwiększenie pamięci do 512 MB.

#### Cache Obiektów Kinsta

Włącz Redis cache przez:

MyKinsta → **Environment → Tools → Redis** → Enable.

---

### 7.3 DigitalOcean (VPS / Droplet)

Droplet za $12/miesiąc (2 vCPU / 2 GB RAM) wystarczy dla ~50 artykułów/dzień.

#### Zalecany Droplet

- **Obraz:** Ubuntu 22.04 LTS
- **Rozmiar:** 2 vCPU / 2 GB RAM (Basic)
- **Region:** najbliżej swojej grupy docelowej
- **Uwierzytelnienie:** Klucz SSH (dodaj swój klucz podczas tworzenia)

#### Skrypt Provisioningu Serwera

```bash
#!/usr/bin/env bash
set -euo pipefail

# Zaktualizuj i zainstaluj zależności
apt update && apt upgrade -y
apt install -y nginx php8.2-fpm php8.2-mysql php8.2-curl \
  php8.2-mbstring php8.2-xml php8.2-zip php8.2-gd php8.2-intl \
  php8.2-imagick mysql-server certbot python3-certbot-nginx \
  git curl unzip

# Zainstaluj WP-CLI
curl -sO https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
mv wp-cli.phar /usr/local/bin/wp

# Utwórz użytkownika deploy
useradd -m -s /bin/bash deploy
mkdir -p /home/deploy/.ssh
# Wklej swój klucz publiczny tutaj:
echo "ssh-ed25519 AAAAC3... github-actions-deploy" \
  > /home/deploy/.ssh/authorized_keys
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys
chown -R deploy:deploy /home/deploy/.ssh

# Utwórz katalog główny WordPress
mkdir -p /var/www/przyklad.pl/public
chown -R www-data:www-data /var/www/przyklad.pl
chmod -R 755 /var/www/przyklad.pl

echo "Provisioning zakończony."
```

#### Instalacja WordPress (WP-CLI)

```bash
cd /var/www/przyklad.pl/public

wp core download --locale=pl_PL

wp config create \
  --dbname=pearblog_db \
  --dbuser=pearblog_user \
  --dbpass=SILNE_HASLO \
  --dbhost=localhost \
  --extra-php << 'PHP'
define( 'WP_MEMORY_LIMIT', '512M' );
define( 'FORCE_SSL_ADMIN', true );
PHP

wp core install \
  --url=https://przyklad.pl \
  --title="Mój Blog" \
  --admin_user=admin \
  --admin_email=admin@przyklad.pl \
  --admin_password=SILNE_HASLO_ADMINA \
  --skip-email
```

#### Konfiguracja MySQL

```bash
mysql -u root -e "
  CREATE DATABASE IF NOT EXISTS pearblog_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER IF NOT EXISTS 'pearblog_user'@'localhost' IDENTIFIED BY 'SILNE_HASLO';
  GRANT ALL PRIVILEGES ON pearblog_db.* TO 'pearblog_user'@'localhost';
  FLUSH PRIVILEGES;
"
```

---

### 7.4 WP Engine (Managed WordPress)

WP Engine to premium managed WordPress host z wbudowanym CDN, Redis i dostępem SFTP/SSH.

#### Dostęp przez SSH Gateway

```bash
ssh -i ~/.ssh/wpengine_key \
    "$WPE_ENV_NAME@$WPE_ENV_NAME.ssh.wpengine.net" \
    -p 22
```

#### Wdrożenie przez WP Engine Git Push

WP Engine obsługuje bezpośrednie wdrożenie `git push`:

```bash
# Jednorazowa konfiguracja
git remote add wpengine git@git.wpengine.com:production/$WPE_ENV_NAME.git

# Wdrożenie
git push wpengine main
```

#### Konfiguracja PHP

WP Engine ustala ustawienia PHP. Aby zwiększyć limit pamięci, użyj pliku nadpisania `php.ini`:

Utwórz `wp-content/mu-plugins/pearblog-memory.ini` (lub użyj portalu WP Engine pod **PHP Settings**):

```ini
memory_limit = 512M
max_execution_time = 300
```

#### WP-CLI na WP Engine

```bash
ssh "$WPE_ENV_NAME@$WPE_ENV_NAME.ssh.wpengine.net" \
  "wp --path=/nas/content/live/$WPE_ENV_NAME/htdocs cache flush"
```

---

## 8. Weryfikacja Po Wdrożeniu

Uruchom te sprawdzenia natychmiast po każdym wdrożeniu.

### Sprawdzenie Składni PHP

```bash
find wp-content/mu-plugins/pearblog-engine/ \
     wp-content/themes/pearblog-theme/ \
     -name "*.php" -print0 \
  | xargs -0 php -l | grep -v "No syntax errors"
```

Oczekiwane: brak wyników (wszystkie pliki przechodzą).

### MU-Plugin Aktywny

```bash
wp plugin list --fields=name,status | grep pearblog-engine
# Oczekiwane: pearblog-engine    must-use
```

### Endpoint Health

```bash
curl -s https://przyklad.pl/wp-json/pearblog/v1/health | python3 -m json.tool
```

Oczekiwana odpowiedź (HTTP 200):

```json
{
  "status": "ok",
  "api_key_set": true,
  "circuit_open": false,
  "queue_size": 0,
  "last_run": "2026-04-12T04:00:00Z"
}
```

### Zdarzenia WP-Cron

```bash
wp cron event list
```

Upewnij się, że `pearblog_pipeline_cron` pojawia się na liście.

### Test Smoke Pipeline

```bash
# Dodaj jeden temat i wygeneruj jeden artykuł (bezpieczny test)
wp pearblog queue add --topic="Testowy artykuł weryfikacyjny wdrożenia"
wp pearblog generate
```

Sprawdź WordPress admin → Wpisy dla nowo wygenerowanego szkicu.

### Logi — Brak Błędów Krytycznych

```bash
tail -50 /var/log/nginx/przyklad.pl-error.log
tail -50 /var/www/przyklad.pl/public/wp-content/debug.log
```

---

## 9. Wycofywanie Wdrożenia

### Wycofanie przez Git

```bash
# Znajdź ostatni dobry commit
git log --oneline mu-plugins/pearblog-engine/ | head -10

# Na serwerze, zresetuj do tego commitu
git --work-tree=/var/www/html/wp-content/mu-plugins/pearblog-engine \
    --git-dir=/var/www/przyklad.pl/repo \
    checkout <SHA_DOBREGO_COMMITU> -- mu-plugins/pearblog-engine/

wp cache flush
```

### Wycofanie przez GitHub Actions

1. Actions → *Deploy to WordPress Server*.
2. Kliknij ostatni **udany** przebieg.
3. **Re-run all jobs**.

### Ręczne Wycofanie przez Kopię Zapasową rsync

Przed każdym wdrożeniem zrób migawkę:

```bash
BACKUP_DIR="/var/backups/pearblog/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

cp -a /var/www/html/wp-content/mu-plugins/pearblog-engine \
      "$BACKUP_DIR/pearblog-engine"
cp -a /var/www/html/wp-content/themes/pearblog-theme \
      "$BACKUP_DIR/pearblog-theme"
```

Przywracanie:

```bash
RESTORE_DIR="/var/backups/pearblog/20260410_120000"  # ustaw na swoją migawkę

rsync -a --delete "$RESTORE_DIR/pearblog-engine/" \
  /var/www/html/wp-content/mu-plugins/pearblog-engine/

rsync -a --delete "$RESTORE_DIR/pearblog-theme/" \
  /var/www/html/wp-content/themes/pearblog-theme/

wp cache flush
```

---

## 10. Rozwiązywanie Problemów

### Biały Ekran Śmierci (WSOD)

1. Włącz logowanie debugowania w `wp-config.php`:
   ```php
   define( 'WP_DEBUG',         true );
   define( 'WP_DEBUG_LOG',     true );
   define( 'WP_DEBUG_DISPLAY', false );
   ```
2. Sprawdź `wp-content/debug.log` pod kątem błędu krytycznego.
3. Typowe przyczyny:
   - **Wyczerpanie pamięci PHP** → zwiększ `memory_limit` do 512M.
   - **Niekompatybilna wersja PHP** → upewnij się, że PHP 8.0+.
   - **Konfliktująca wtyczka** → tymczasowo dezaktywuj inne wtyczki.

### WP-Cron Nie Działa

```bash
# Sprawdź czy cron jest zaplanowany
wp cron event list

# Wymuś uruchomienie przeterminowanych zdarzeń
wp cron event run --due-now

# Sprawdź DISABLE_WP_CRON w wp-config.php
grep DISABLE_WP_CRON /var/www/html/wp-config.php
```

Jeśli `DISABLE_WP_CRON` jest `true`, dodaj prawdziwy cron job opisany w §7.1.

### Błędy API OpenAI

```bash
# Sprawdź status circuit breakera
wp pearblog circuit status

# Zresetuj circuit breaker
wp pearblog circuit reset

# Sprawdź akumulację kosztów
wp option get pearblog_ai_cost_cents
```

### Błędy Uprawnień

Prawidłowe uprawnienia dla standardowego stosu Apache/Nginx + PHP-FPM:

```bash
find /var/www/html/wp-content/mu-plugins/pearblog-engine -type d -exec chmod 755 {} \;
find /var/www/html/wp-content/mu-plugins/pearblog-engine -type f -exec chmod 644 {} \;
chown -R www-data:www-data /var/www/html/wp-content/mu-plugins/pearblog-engine
```

### Błędy Połączenia z Bazą Danych

```bash
# Przetestuj poświadczenia połączenia
wp db check

# Sprawdź czy MySQL działa
systemctl status mysql

# Przetestuj DSN ręcznie
mysql -u pearblog_user -p pearblog_db -e "SELECT 1;"
```

### Pusta Kolejka / Brak Generowanej Treści

```bash
# Sprawdź rozmiar kolejki
wp pearblog stats

# Dodaj tematy ręcznie
wp pearblog queue add --topic="Najlepsze kierunki podróży 2026"

# Uruchom pipeline natychmiast (z pominięciem crona)
wp pearblog generate
```

---

## 11. Wzmocnienie Bezpieczeństwa

### Uprawnienia Plików

| Ścieżka | Właściciel | Tryb |
|---------|------------|------|
| `wp-config.php` | `root:www-data` | `640` |
| `wp-content/` | `www-data:www-data` | `755` |
| `wp-content/uploads/` | `www-data:www-data` | `755` |
| Pliki PHP | `www-data:www-data` | `644` |
| Skrypty shell | `deploy:deploy` | `750` |

```bash
chown root:www-data wp-config.php
chmod 640           wp-config.php
```

### Blokada Wykonywania PHP w Uploads (Nginx)

```nginx
location ~* /wp-content/uploads/.*\.php$ {
    deny all;
    return 403;
}
```

### Wyłączenie XML-RPC (jeśli niepotrzebny)

Dodaj do `.htaccess` (Apache):

```apacheconf
<Files xmlrpc.php>
    Require all denied
</Files>
```

Lub do Nginx:

```nginx
location = /xmlrpc.php {
    deny all;
}
```

### Ochrona `wp-login.php` przez Listę Dozwolonych IP

```nginx
location = /wp-login.php {
    allow 203.0.113.0/24;   # twój zakres IP biura / domu
    deny  all;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

### Ukrycie Wersji WordPress

Dodaj do `functions.php` motywu potomnego (lub zastosuj przez motyw PearBlog — już zrobione w v5.2+):

```php
remove_action( 'wp_head', 'wp_generator' );
```

### Regularna Rotacja Kluczy API

- OpenAI: rotacja co 90 dni przez panel OpenAI.
- Klucz SSH GitHub Actions: rotacja corocznie; natychmiast odwołaj przy kompromitacji.
- Wszystkie sekrety: przechowywane wyłącznie w GitHub Actions Secrets lub zmiennych środowiskowych, nigdy w kodzie.

---

## 12. Strojenie Wydajności

### PHP OPcache (`/etc/php/8.2/fpm/conf.d/10-opcache.ini`)

```ini
opcache.enable                 = 1
opcache.memory_consumption     = 256
opcache.interned_strings_buffer= 16
opcache.max_accelerated_files  = 10000
opcache.revalidate_freq        = 60
opcache.fast_shutdown          = 1
```

### Strojenie MySQL (`/etc/mysql/mysql.conf.d/mysqld.cnf`)

```ini
[mysqld]
innodb_buffer_pool_size   = 512M   # ustaw na ~70% dostępnej RAM
innodb_log_file_size      = 128M
query_cache_type          = 0      # wyłącz przestarzały query cache
slow_query_log            = 1
slow_query_log_file       = /var/log/mysql/slow.log
long_query_time           = 2
```

### Cache FastCGI Nginx

```nginx
# W nginx.conf (blok http)
fastcgi_cache_path /var/cache/nginx/wordpress
    levels=1:2 keys_zone=WORDPRESS:100m inactive=60m;

# W bloku serwera
set $skip_cache 0;

# Pomiń cache dla zalogowanych użytkowników i żądań POST
if ($request_method = POST)       { set $skip_cache 1; }
if ($query_string != "")          { set $skip_cache 1; }
if ($http_cookie ~* "wordpress_logged_in") { set $skip_cache 1; }

location ~ \.php$ {
    fastcgi_cache WORDPRESS;
    fastcgi_cache_valid 200 60m;
    fastcgi_cache_bypass  $skip_cache;
    fastcgi_no_cache      $skip_cache;
    add_header X-FastCGI-Cache $upstream_cache_status;
    # ... fastcgi_pass itp.
}
```

### Cache Obiektów WordPress (Redis)

```bash
# Zainstaluj Redis na Ubuntu
apt install redis-server php8.2-redis
systemctl enable --now redis-server

# Zainstaluj wp-plugin Redis object cache drop-in
wp plugin install wp-redis --activate
wp redis enable
```

Weryfikacja:

```bash
wp redis status
# Oczekiwane: Status: Connected
```

---

## Powiązana Dokumentacja

| Dokument | Cel |
|----------|-----|
| [SETUP.md](SETUP.md) | GitHub Secrets i szybki start CI/CD |
| [DEPLOYMENT.md](DEPLOYMENT.md) | Oryginalna wersja angielska tego przewodnika |
| [DATABASE-MIGRATIONS.md](DATABASE-MIGRATIONS.md) | Aktualizacje schematu i SQL wycofywania |
| [DISASTER-RECOVERY.md](DISASTER-RECOVERY.md) | Procedury kopii zapasowych, przywracania i przełączania awaryjnego |
| [PRODUCTION-CHECKLIST.md](PRODUCTION-CHECKLIST.md) | Listy kontrolne przed startem i operacyjne |
| [PRODUCTION-ANALYSIS-FULL.md](PRODUCTION-ANALYSIS-FULL.md) | Pełny podręcznik operacji produkcyjnych |
| [PROGRESS-VISUALIZATION.md](PROGRESS-VISUALIZATION.md) | Wizualizacja postępu projektu (PL/EN) |

---

*PearBlog Engine v6.0 — Przewodnik Wdrożenia Produkcyjnego (wersja polska)*  
*Tłumaczenie: 2026-04-12*

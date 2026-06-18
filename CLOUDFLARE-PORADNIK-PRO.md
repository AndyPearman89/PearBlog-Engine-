# Cloudflare Configuration — poradnik.pro

> **Ostatnia aktualizacja:** 2026-06-18  
> **Domena:** `poradnik.pro`  
> **Serwer origin:** `wordpress2614653.home.pl`

---

## 1. Konfiguracja DNS

### Krok 1: Dodaj domenę do Cloudflare

1. Zaloguj się na [dash.cloudflare.com](https://dash.cloudflare.com)
2. Kliknij **Add a site** → wpisz `poradnik.pro`
3. Wybierz plan (Free wystarczy na start)
4. Cloudflare pokaże assigned nameservers, np.:
   ```
   NS1: ada.ns.cloudflare.com
   NS2: ben.ns.cloudflare.com
   ```

### Krok 2: Zmień nameservery u rejestratora

U rejestratora domeny `poradnik.pro` (np. OVH, Namecheap, home.pl) zmień nameservery na te podane przez Cloudflare.

> ⏱️ Propagacja DNS: zwykle < 1 godzina, max 48h

### Krok 3: Rekordy DNS

Dodaj następujące rekordy w panelu Cloudflare DNS:

| Typ | Nazwa | Wartość | Proxy | TTL |
|-----|-------|---------|-------|-----|
| A | `poradnik.pro` | `IP serwera wordpress2614653.home.pl` | ☁️ Proxied | Auto |
| A | `www` | `IP serwera wordpress2614653.home.pl` | ☁️ Proxied | Auto |
| CNAME | `www` | `poradnik.pro` | ☁️ Proxied | Auto |

> **Uwaga:** Użyj A record z IP serwera (sprawdź aktualny IP: `dig wordpress2614653.home.pl +short`)
> Alternatywnie, jeśli home.pl daje CNAME, użyj CNAME zamiast A.

### Krok 4: SSL/TLS

W panelu Cloudflare → **SSL/TLS**:

1. **Encryption mode:** `Full (Strict)`
   - Wymaga ważnego certyfikatu na serwerze origin (Let's Encrypt lub Cloudflare Origin CA)
2. **Edge Certificates:**
   - ✅ Always Use HTTPS
   - ✅ Automatic HTTPS Rewrites
   - ✅ TLS 1.3
3. **Origin Server:**
   - Wygeneruj **Cloudflare Origin Certificate** (ważny 15 lat)
   - Zainstaluj na serwerze w `/home/tutsoff/ssl/` lub przez panel home.pl

### Krok 5: Redirect www → poradnik.pro

W **Rules** → **Redirect Rules** dodaj:

```
Incoming requests matching:
  Hostname equals: www.poradnik.pro

Then:
  Type: Dynamic
  Status code: 301
  URL: concat("https://poradnik.pro", http.request.uri.path)
```

---

## 2. WordPress wp-config.php

Dodaj do `wp-config.php` na serwerze (`/home/tutsoff/public_html/poradnik/wp-config.php`):

```php
/** Domain configuration */
define('WP_HOME', 'https://poradnik.pro');
define('WP_SITEURL', 'https://poradnik.pro');

/** Cloudflare — fix for HTTPS detection behind proxy */
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

/** Cloudflare — real visitor IP */
if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
    $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
}
```

---

## 3. Cloudflare Images (CDN dla obrazów)

### Konfiguracja

PearBlog Engine obsługuje Cloudflare Images przez `CdnManager` (`mu-plugins/pearblog-engine/src/Cache/CdnManager.php`).

#### GitHub Secrets (dodaj w Settings → Secrets → Actions):

| Secret | Opis | Gdzie znaleźć |
|--------|------|---------------|
| `CF_ACCOUNT_ID` | Cloudflare Account ID | Dashboard → prawna kolumna → Account ID |
| `CF_IMAGES_API_TOKEN` | API Token z uprawnieniem Images | My Profile → API Tokens → Create Token |
| `CF_IMAGES_DELIVERY_URL` | URL dostarczania obrazów | Images → Delivery URL |

#### Tworzenie API Token:

1. Wejdź na [dash.cloudflare.com/profile/api-tokens](https://dash.cloudflare.com/profile/api-tokens)
2. **Create Token** → **Custom token**
3. Uprawnienia:
   - `Account` → `Cloudflare Images` → `Edit`
4. Skopiuj token

#### Aktywacja w WordPress:

W WP Admin → **PearBlog Engine** → **Settings** ustaw:

```
CDN Provider: cloudflare
```

Lub przez WP-CLI na serwerze:

```bash
cd /home/tutsoff/public_html/poradnik
wp option update pearblog_cdn_enabled 1
wp option update pearblog_cdn_provider cloudflare
wp option update pearblog_cdn_cf_account_id "YOUR_ACCOUNT_ID"
wp option update pearblog_cdn_cf_api_token "YOUR_API_TOKEN"
wp option update pearblog_cdn_cf_delivery_url "https://imagedelivery.net/YOUR_HASH"
```

#### Weryfikacja:

```bash
# Test upload
wp eval '
$cdn = new PearBlogEngine\Cache\CdnManager();
$result = $cdn->offload_attachment(123); // ID obrazka
var_dump($result);
'
```

---

## 4. Page Rules / Cache Rules

### Opcja A: Cache Rules (nowy system — zalecany)

W panelu Cloudflare → **Caching** → **Cache Rules**:

#### Reguła 1: Cache statyczne assety (agresywnie)

```
When incoming requests match:
  URI Path contains "/wp-content/themes/"
  OR URI Path contains "/wp-content/uploads/"
  OR URI Path ends with ".css"
  OR URI Path ends with ".js"
  OR URI Path ends with ".woff2"

Then:
  Cache eligibility: Eligible for cache
  Edge TTL: 1 month
  Browser TTL: 1 week
```

#### Reguła 2: Bypass cache dla admin

```
When incoming requests match:
  URI Path starts with "/wp-admin"
  OR URI Path starts with "/wp-login"
  OR URI Path contains "wp-cron.php"
  OR URI Path starts with "/wp-json/pearblog"

Then:
  Cache eligibility: Bypass cache
```

#### Reguła 3: Cache HTML stron (opcjonalnie)

```
When incoming requests match:
  URI Path does not start with "/wp-admin"
  AND URI Path does not contain "wp-login"
  AND Cookie does not contain "wordpress_logged_in"
  AND HTTP Method equals "GET"

Then:
  Cache eligibility: Eligible for cache
  Edge TTL: 2 hours
  Browser TTL: Respect origin
  Cache Key: Include query string
```

### Opcja B: Page Rules (starszy system — 3 darmowe reguły)

Jeśli nie masz dostępu do Cache Rules:

| Priorytet | URL Pattern | Ustawienia |
|-----------|-------------|-----------|
| 1 | `poradnik.pro/wp-admin/*` | Cache Level: Bypass, Security Level: High |
| 2 | `poradnik.pro/wp-content/*` | Cache Level: Cache Everything, Edge TTL: 1 month, Browser TTL: 1 week |
| 3 | `poradnik.pro/*` | Cache Level: Cache Everything, Edge TTL: 2 hours |

---

## 5. Dodatkowe ustawienia Performance

### Speed → Optimization

- ✅ **Auto Minify:** HTML, CSS, JS
- ✅ **Brotli:** Enabled
- ⚠️ **Rocket Loader:** Test najpierw (może konfliktować z WP scripts)
- ✅ **Early Hints:** Enabled
- ✅ **HTTP/2:** Enabled (domyślnie)
- ✅ **HTTP/3 (QUIC):** Enabled

### Caching → Configuration

- **Browser Cache TTL:** Respect Existing Headers
- **Crawler Hints:** Enabled (pomaga SEO)
- **Always Online:** Enabled

### Security

- **Security Level:** Medium
- **Bot Fight Mode:** Enabled
- **Browser Integrity Check:** Enabled
- **Challenge Passage:** 30 minutes

### Firewall Rules (WAF) — blokowanie ataków

```
Rule 1: Block wp-login brute force
  When: URI Path contains "/wp-login.php" AND not IP in {your_IP}
  Action: Challenge (JS Challenge)

Rule 2: Block xmlrpc
  When: URI Path equals "/xmlrpc.php"
  Action: Block
```

---

## 6. Automatyczne czyszczenie cache po deploy

Deploy workflow (`.github/workflows/deploy-poradnik-v2-pro.yml`) może czyścić cache Cloudflare po wdrożeniu.

Dodaj do GitHub Secrets:

| Secret | Wartość |
|--------|---------|
| `CF_ZONE_ID` | Zone ID domeny poradnik.pro (Dashboard → Overview → prawna kolumna) |
| `CF_API_TOKEN_CACHE` | API Token z uprawnieniem: Zone → Cache Purge → Edit |

Snippet do workflow (dodaj na końcu job deploy):

```yaml
- name: Purge Cloudflare Cache
  if: success()
  run: |
    curl -s -X POST \
      "https://api.cloudflare.com/client/v4/zones/${{ secrets.CF_ZONE_ID }}/purge_cache" \
      -H "Authorization: ****** secrets.CF_API_TOKEN_CACHE }}" \
      -H "Content-Type: application/json" \
      --data '{"purge_everything":true}'
```

---

## 7. Weryfikacja konfiguracji

### Test czy Cloudflare działa:

```bash
# Sprawdź nagłówki CF
curl -I https://poradnik.pro/ | grep -i "cf-\|server"

# Oczekiwane:
# server: cloudflare
# cf-cache-status: HIT (lub MISS przy pierwszym requescie)
# cf-ray: xxxxx-WAW (Warsaw PoP)
```

### Test SSL:

```bash
curl -vI https://poradnik.pro 2>&1 | grep "SSL\|issuer"
# Powinien pokazać: issuer: Cloudflare Inc
```

### Test cache:

```bash
# Pierwsze żądanie (MISS)
curl -s -o /dev/null -w "%{http_code} %{time_total}s" https://poradnik.pro/

# Drugie żądanie (HIT — szybsze)
curl -s -o /dev/null -w "%{http_code} %{time_total}s" https://poradnik.pro/
```

---

## 8. Checklist wdrożenia

```
□ Dodano domenę poradnik.pro do Cloudflare
□ Zmieniono nameservery u rejestratora
□ Nameservery spropagowane (check: dig poradnik.pro NS)
□ Rekordy DNS (A/CNAME) dodane
□ SSL/TLS ustawione na Full (Strict)
□ Origin Certificate wygenerowany i zainstalowany
□ wp-config.php zaktualizowany (WP_HOME, WP_SITEURL, CF proxy fix)
□ Cache Rules skonfigurowane
□ Security rules dodane (xmlrpc block, wp-login challenge)
□ Cloudflare Images skonfigurowane (opcjonalnie)
□ GitHub Secrets dodane (CF_ZONE_ID, CF_API_TOKEN_CACHE)
□ Deploy workflow czysci cache po wdrożeniu
□ Test: curl -I https://poradnik.pro (server: cloudflare)
```

---

## Powiązane pliki w repozytorium

| Plik | Opis |
|------|------|
| `mu-plugins/pearblog-engine/src/Cache/CdnManager.php` | Cloudflare Images integration |
| `CDN-INTEGRATION.md` | Ogólny przewodnik CDN |
| `GITHUB-SECRETS-GUIDE.md` | Wszystkie sekrety GitHub |
| `.github/workflows/deploy-poradnik-v2-pro.yml` | Workflow deployu |
| `theme/pearblog-theme/inc/user-context.php` | CF IP detection |
| `mu-plugins/pearblog-engine/src/Logging/RequestContextProcessor.php` | CF IP w logach |

---

**Autor:** PearBlog Engine Team  
**Domena:** poradnik.pro  
**Hosting origin:** home.pl (wordpress2614653.home.pl, port SSH 222)

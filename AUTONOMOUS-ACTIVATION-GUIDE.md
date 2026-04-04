# 🤖 Przewodnik Aktywacji Autonomicznej Produkcji

**PearBlog Engine - Pełna Autonomia z Generowaniem Grafik AI**

---

## 🎯 Czym Jest Autonomiczna Produkcja?

System PearBlog Engine został wyposażony w **w pełni autonomiczną produkcję contentu**, która:

✅ **Automatycznie generuje artykuły** z kolejki tematów
✅ **Tworzy unikalne grafiki AI** (DALL-E 3) dla każdego artykułu
✅ **Publikuje kompletne treści** z SEO, monetyzacją i internal linking
✅ **Działa 24/7** bez interwencji manualnej

### Pipeline Autonomiczny

```
KAŻDĄ GODZINĘ (WP-Cron):
├─ Pobierz temat z kolejki
├─ Wygeneruj content AI (GPT-4o-mini)
├─ Wygeneruj featured image (DALL-E 3)
├─ Zastosuj SEO optimization
├─ Dodaj monetyzację (ads + affiliate)
├─ Opublikuj artykuł
└─ Powtórz dla następnego tematu
```

---

## ⚡ Szybki Start (5 minut)

### Krok 1: Skonfiguruj OpenAI API Key

```bash
# Opcja A: WordPress Admin
1. Idź do Settings → PearBlog Engine
2. Wklej OpenAI API Key
3. Kliknij "Save Changes"

# Opcja B: wp-config.php (bezpieczniejsze)
define( 'PEARBLOG_OPENAI_API_KEY', 'sk-proj-...' );
```

### Krok 2: Włącz Generowanie Grafik

```bash
Settings → PearBlog Engine → AI Image Generation
☑ Enable Image Generation
Style: Photorealistic (lub wybierz inny)
```

### Krok 3: Dodaj Tematy do Kolejki

```bash
Settings → PearBlog Engine → Topic Queue
Dodaj tematy (jeden na linię):

Babia Góra szlaki turystyczne
Turbacz - jak dojść z parkingu
Pilsko noclegi w okolicy
Beskidy zimą atrakcje
```

### Krok 4: Poczekaj 1 Godzinę ⏱️

```
WP-Cron uruchomi się automatycznie i:
✅ Przetworzy pierwszy temat z kolejki
✅ Wygeneruje artykuł + grafikę
✅ Opublikuje na blogu

Sprawdź logi w: /wp-content/debug.log
```

---

## 🔧 Konfiguracja Szczegółowa

### Ustawienia Podstawowe

| Opcja | Opis | Wartość Domyślna |
|-------|------|------------------|
| `pearblog_openai_api_key` | Klucz API OpenAI | *wymagane* |
| `pearblog_api_key` | Klucz API dla automatyzacji (GitHub Actions) | *opcjonalne* |
| `pearblog_industry` | Branża/niche | `general` |
| `pearblog_tone` | Ton pisania | `neutral` |
| `pearblog_language` | Język contentu | `en` |
| `pearblog_publish_rate` | Artykułów/godzinę | `1` |

### Ustawienia Generowania Grafik (NOWOŚĆ)

| Opcja | Opis | Wartość Domyślna |
|-------|------|------------------|
| `pearblog_enable_image_generation` | Włącz AI images | `true` |
| `pearblog_image_style` | Styl wizualny | `photorealistic` |

**Dostępne Style:**
- `photorealistic` - Fotorealistyczne zdjęcia (najlepsze dla travel/outdoor)
- `illustration` - Cyfrowe ilustracje (dobre dla edukacji)
- `artistic` - Malarskie, artystyczne (unikalne, eye-catching)
- `minimal` - Minimalistyczne, czyste (profesjonalne)

---

## 📊 Jak To Działa?

### 1. WP-Cron Scheduling

System używa **WP-Cron** (nie Linux cron) do automatycznego uruchamiania:

```php
// Rejestracja w CronManager.php
wp_schedule_event( time(), 'pearblog_hourly', 'pearblog_run_pipeline' );

// Uruchamia się co godzinę automatycznie
```

**Sprawdź aktywne cron jobs:**
```bash
wp cron event list --allow-root
# Szukaj: pearblog_run_pipeline (next run)
```

### 2. Content Pipeline

```php
// ContentPipeline.php - pełny flow
1. Pop topic z kolejki
2. Build prompt (PromptBuilderFactory auto-select)
3. Generate content (AIClient → GPT-4o-mini)
4. Create draft post (WordPress)
5. Apply SEO (SEOEngine)
6. Add monetization (MonetizationEngine)
7. Generate featured image (ImageGenerator → DALL-E 3) ← NOWOŚĆ
8. Publish post
9. Log success
```

### 3. Image Generation

```php
// ImageGenerator.php - nowa klasa
1. Build DALL-E prompt z tytułu artykułu
2. Call OpenAI Images API (DALL-E 3)
3. Download image URL to WP media library
4. Set as featured image
5. Add alt text for SEO
```

**Koszt:**
- DALL-E 3 Standard (1792x1024): **$0.080 per image**
- GPT-4o-mini (2048 tokens): **~$0.0003 per article**
- **Total per article: ~$0.08** (głównie grafika)

---

## 🚀 Sprawdzanie Statusu

### Czy System Działa?

```bash
# 1. Sprawdź cron events
wp cron event list --allow-root | grep pearblog

# 2. Sprawdź logi
tail -f /wp-content/debug.log | grep "PearBlog Engine"

# 3. Sprawdź kolejkę
wp option get pearblog_topic_queue_1

# 4. Sprawdź ostatnio opublikowane
wp post list --post_status=publish --orderby=date --order=DESC --posts_per_page=5
```

### Przykładowe Logi

```
[03-Apr-2026 14:00:01 UTC] PearBlog Engine: Pipeline started for topic: "Babia Góra szlaki"
[03-Apr-2026 14:00:15 UTC] PearBlog Engine: AI content generated (2,450 words)
[03-Apr-2026 14:00:45 UTC] PearBlog Engine: Generated featured image (ID: 1234) for post 567
[03-Apr-2026 14:00:46 UTC] PearBlog Engine: Post published (ID: 567)
[03-Apr-2026 14:00:46 UTC] PearBlog Engine: Pipeline completed successfully
```

---

## ⚙️ Dostrajanie Wydajności

### Zwiększ Częstotliwość Publikacji

```php
// wp-config.php lub Settings → PearBlog Engine
update_option( 'pearblog_publish_rate', 3 ); // 3 artykuły/godzinę
```

**Uwaga:** Więcej artykułów = wyższe koszty API

### Wyłącz Generowanie Grafik (Oszczędność)

```php
update_option( 'pearblog_enable_image_generation', false );
```

**Efekt:**
- Oszczędność: **$0.08 per article**
- Artykuły bez featured image (można dodać manualnie później)

### Zmień Model AI

```php
// AIClient.php - zmień MODEL
private const MODEL = 'gpt-4o-mini';  // tani, szybki
// lub
private const MODEL = 'gpt-4o';       // droższy, lepsza jakość
```

**Porównanie kosztów:**
- `gpt-4o-mini`: $0.150 / 1M input tokens, $0.600 / 1M output
- `gpt-4o`: $2.50 / 1M input tokens, $10.00 / 1M output

---

## 🔥 Zaawansowana Konfiguracja

### Custom Prompt Style (Filter Hooks)

```php
// functions.php lub custom plugin
add_filter( 'pearblog_image_prompt', function( $prompt, $topic, $style ) {
    // Customize DALL-E prompt
    return "Ultra realistic, 8K photograph of {$topic}, National Geographic style, award-winning composition";
}, 10, 3 );
```

### Disable Pipeline dla Specific Sites (Multisite)

```php
add_action( 'pearblog_pipeline_started', function( $topic, $context ) {
    // Skip dla site ID 5
    if ( $context->site_id === 5 ) {
        wp_die( 'Pipeline disabled for this site' );
    }
}, 10, 2 );
```

### Post-Publikacja Actions

```php
add_action( 'pearblog_pipeline_completed', function( $post_id, $topic, $context ) {
    // Np. wysyłka notyfikacji email
    wp_mail( 'admin@site.com', 'New Article Published', "Post ID: {$post_id}, Topic: {$topic}" );
}, 10, 3 );
```

---

## 🐛 Troubleshooting

### Problem: Cron Nie Uruchamia Się

**Diagnoza:**
```bash
wp cron test --allow-root
```

**Rozwiązania:**
```bash
# A) Wymuś uruchomienie crona
wp cron event run pearblog_run_pipeline --allow-root

# B) Zresetuj schedule
wp cron event delete pearblog_run_pipeline --allow-root
# Odśwież stronę admin (cron się przeładuje)

# C) Użyj prawdziwego crona (zamiast WP-Cron)
# wp-config.php:
define( 'DISABLE_WP_CRON', true );

# Linux crontab:
*/15 * * * * wp cron event run --due-now --path=/var/www/html
```

### Problem: Brak Grafik w Artykułach

**Sprawdź:**
```bash
# 1. Czy włączone?
wp option get pearblog_enable_image_generation

# 2. Sprawdź logi błędów
tail -100 /wp-content/debug.log | grep "image"

# 3. Test manualny
wp eval "echo (new \PearBlogEngine\AI\ImageGenerator())->generate('test mountain landscape');"
```

**Częste przyczyny:**
- ❌ Brak OpenAI API key
- ❌ Przekroczony limit API quota
- ❌ Opcja wyłączona w admin
- ❌ Timeout (zwiększ w wp-config.php: `set_time_limit(300);`)

### Problem: Wysokie Koszty API

**Analiza:**
```bash
# Ile artykułów/miesiąc?
artykułów_miesiąc = publish_rate × 24 godzin × 30 dni
# Dla publish_rate=1: 1 × 24 × 30 = 720 artykułów/miesiąc

# Koszt z grafikami:
720 × $0.08 = $57.60/miesiąc

# Koszt bez grafik:
720 × $0.0003 = $0.22/miesiąc
```

**Optymalizacja:**
- Wyłącz grafiki: `-$57.60/m`
- Zmniejsz publish_rate: `publish_rate=0.5` → `-50%`
- Użyj tańszego modelu (już używasz najtańszego)

### Problem: Content Quality Issues

**Lepsza jakość promptów:**
```bash
# Ustaw bardziej szczegółową branżę
update_option( 'pearblog_industry', 'Beskidy mountains travel and hiking guide' );

# Zmień ton
update_option( 'pearblog_tone', 'authoritative' );

# Użyj custom prompt builder
# Zobacz: mu-plugins/pearblog-engine/src/Content/BeskidyPromptBuilder.php
```

---

## 📈 Monitoring i Analytics

### Śledź Sukces Pipeline

```php
// functions.php
add_action( 'pearblog_pipeline_completed', function( $post_id, $topic, $context ) {
    // Log do custom analytics
    $success_count = get_option( 'pearblog_pipeline_success_count', 0 );
    update_option( 'pearblog_pipeline_success_count', $success_count + 1 );

    // Log timestamp
    update_post_meta( $post_id, '_pearblog_generated_at', current_time( 'timestamp' ) );
}, 10, 3 );
```

### Dashboard Widget (Opcjonalnie)

```php
add_action( 'wp_dashboard_setup', function() {
    wp_add_dashboard_widget(
        'pearblog_stats',
        'PearBlog Autonomy Status',
        function() {
            $queue = new \PearBlogEngine\Content\TopicQueue( get_current_blog_id() );
            $success = get_option( 'pearblog_pipeline_success_count', 0 );

            echo "<p><strong>Topics in queue:</strong> {$queue->count()}</p>";
            echo "<p><strong>Total generated:</strong> {$success}</p>";
            echo "<p><strong>Next run:</strong> " . wp_next_scheduled( 'pearblog_run_pipeline' ) . "</p>";
        }
    );
});
```

---

## 🎓 Best Practices

### 1. Systematyczna Kolejka Tematów

✅ **DOBRZE:**
```
# Cluster SEO approach
Babia Góra szlaki - kompletny przewodnik
Babia Góra szlaki dla początkujących
Babia Góra szlaki trudność poziomy
Babia Góra parking gdzie zaparkować
Babia Góra noclegi w okolicy
```

❌ **ŹLE:**
```
# Random topics - brak strategii
Babia Góra szlaki
Zakupy online
Przepis na ciasto
```

### 2. Monitor API Usage

```bash
# Codziennie sprawdzaj zużycie
# OpenAI Dashboard → Usage

# Alert przy 80% quota:
if [ $usage -gt 80 ]; then
    update_option( 'pearblog_publish_rate', 0 ); # Stop pipeline
fi
```

### 3. Regular Content Review

```bash
# Co tydzień sprawdź jakość
wp post list --post_status=publish --orderby=date --order=DESC --posts_per_page=20

# Ręcznie zweryfikuj:
# - Czy grafiki są trafne?
# - Czy content ma sens?
# - Czy SEO jest OK?
```

### 4. Backup Before Scale

```bash
# Przed zwiększeniem publish_rate
wp db export backup-before-scale.sql
```

---

## 🚀 Gotowy do Uruchomienia!

### Checklist Aktywacji

- [ ] OpenAI API Key skonfigurowany
- [ ] Image generation włączone
- [ ] Style obrazów wybrany (photorealistic dla travel)
- [ ] Industry/niche ustawione
- [ ] Język ustawiony (pl dla Polski)
- [ ] 10-50 tematów w kolejce
- [ ] WP-Cron aktywny (test: `wp cron test`)
- [ ] Debug log włączony (WP_DEBUG_LOG = true)
- [ ] Backup database wykonany

### Start Autonomy

```bash
# Opcja A: Poczekaj na następną godzinę (WP-Cron auto)
# Opcja B: Wymuś natychmiastowe uruchomienie
wp cron event run pearblog_run_pipeline --allow-root

# Monitor logs
tail -f /wp-content/debug.log | grep "PearBlog"
```

---

## 📞 Support

**Problem z aktywacją?**

1. Sprawdź logs: `/wp-content/debug.log`
2. Test crona: `wp cron test`
3. Verify API key: Settings → PearBlog Engine
4. Check GitHub Issues: https://github.com/your-repo/issues

---

## 📊 Podsumowanie Kosztów

### Miesięczne Koszty (Przykład)

**Setup:**
- `publish_rate = 1` (1 artykuł/godzinę)
- Image generation: ✅ Enabled
- Model: `gpt-4o-mini`

**Kalkulacja:**
```
Artykuły/miesiąc: 1 × 24h × 30d = 720
Content cost: 720 × $0.0003 = $0.22
Images cost: 720 × $0.08 = $57.60
─────────────────────────────────
TOTAL: $57.82/miesiąc
```

**ROI Analysis:**
- Content value: 720 artykułów SEO-optimized
- Potential traffic: 50k-200k visitors/miesiąc (przy dobrej strategii)
- AdSense revenue: ~$200-800/miesiąc (przy 50k visitors)
- **Net profit: ~$150-750/miesiąc**

---

**🎉 System Gotowy! Autonomiczna Produkcja Włączona!**

---

## 🔌 GitHub Actions – External Automation API

The PearBlog Engine exposes REST API endpoints that allow the external Python
automation scripts (`automation_orchestrator.py`, `run_pipeline.py`) running in
GitHub Actions to drive the WordPress-side pipeline remotely.

### Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/wp-json/pearblog/v1/automation/create-content` | Queue topic + run pipeline immediately |
| POST | `/wp-json/pearblog/v1/automation/process-content` | Trigger next pipeline cycle(s) |
| GET  | `/wp-json/pearblog/v1/automation/status` | Queue length, next topic, profile info |

### Setup

1. Generate a strong API key (e.g. `openssl rand -hex 32`).
2. In WordPress: **Settings → PearBlog Engine → Automation API Key** — paste the key.
3. In GitHub: **Settings → Secrets → Actions** — add `API_KEY` with the same value.
4. Add `SITE_URL` secret with your WordPress URL (e.g. `https://pearblog.pro`).
5. Add `API_ENDPOINT` secret with `/wp-json/pearblog/v1/automation/process-content`.

### Authentication

All requests must include a `Authorization: Bearer <API_KEY>` header.
Admin users authenticated via WordPress cookie/application-password are also accepted.

---

*PearBlog Engine - Autonomous Content Production with AI Image Generation*
*Version 4.0 - Fully Autonomous Mode*

# Developer Hooks Reference

**PearBlog Engine v7.7** · All action and filter hooks provided by the plugin

This document is the authoritative reference for every WordPress action hook (`do_action`) and filter hook (`apply_filters`) that PearBlog Engine exposes.  Use these hooks to extend or modify the plugin's behaviour **without editing core files**.

---

## Table of Contents

1. [Conventions](#conventions)
2. [Action Hooks](#action-hooks)
   - [Pipeline Lifecycle](#pipeline-lifecycle)
   - [Background Processing](#background-processing)
   - [Content Operations](#content-operations)
   - [SEO & Publishing](#seo--publishing)
   - [Social & Distribution](#social--distribution)
   - [Monitoring & SLA](#monitoring--sla)
   - [A/B Testing](#ab-testing)
   - [CDN & Cache](#cdn--cache)
   - [Admin Trigger](#admin-trigger)
   - [CLI / Autopilot](#cli--autopilot)
3. [Filter Hooks](#filter-hooks)
   - [Prompt Generation](#prompt-generation)
   - [Builder Selection](#builder-selection)
   - [Monetisation](#monetisation)
   - [SEO & Internal Links](#seo--internal-links)
   - [Monitoring Thresholds](#monitoring-thresholds)
   - [Image Generation](#image-generation)
4. [Quick Examples](#quick-examples)
5. [Creating a Custom Extension](#creating-a-custom-extension)

---

## Conventions

- All hook names are prefixed with `pearblog_`.
- **Actions** are fired with `do_action()`; use `add_action()` to attach callbacks.
- **Filters** are applied with `apply_filters()`; use `add_filter()` to attach callbacks.
  A filter callback **must** return the (possibly modified) first argument.
- Parameter types follow PHPDoc notation.
- Source paths are relative to `mu-plugins/pearblog-engine/src/`.

---

## Action Hooks

### Pipeline Lifecycle

#### `pearblog_pipeline_started`

Fires at the very start of a content-generation run, **before** any AI call is made.

```php
do_action( 'pearblog_pipeline_started', string $topic, TenantContext $context );
```

| Parameter  | Type            | Description                            |
|------------|-----------------|----------------------------------------|
| `$topic`   | `string`        | The article topic / keyword.           |
| `$context` | `TenantContext` | Active tenant configuration object.   |

**Source:** `Pipeline/ContentPipeline.php`

**Example use:** Initialise per-run telemetry or pre-validate the topic before generation begins.

---

#### `pearblog_pipeline_completed`

Fires after the full pipeline has finished and the post is published (or saved as draft).

```php
do_action( 'pearblog_pipeline_completed', int $post_id, string $topic, TenantContext $context );
```

| Parameter  | Type            | Description                      |
|------------|-----------------|----------------------------------|
| `$post_id` | `int`           | ID of the newly created post.    |
| `$topic`   | `string`        | The article topic / keyword.     |
| `$context` | `TenantContext` | Active tenant configuration.     |

**Source:** `Pipeline/ContentPipeline.php`

**Example use:** Send a Slack notification, update a dashboard metric, or trigger a downstream workflow.

---

#### `pearblog_pipeline_duplicate_skipped`

Fires when the duplicate-detection check determines that the generated content is too similar to an existing post and the pipeline is aborted for this topic.

```php
do_action( 'pearblog_pipeline_duplicate_skipped', string $topic, array $dup_result );
```

| Parameter     | Type     | Description                                                   |
|---------------|----------|---------------------------------------------------------------|
| `$topic`      | `string` | The topic that was skipped.                                   |
| `$dup_result` | `array`  | Result array from `DuplicateDetector`; contains `similarity` (float) and `matched_post_id` (int). |

**Source:** `Pipeline/ContentPipeline.php`

---

#### `pearblog_pipeline_cron_error`

Fires when the scheduled cron runner (`CronManager`) encounters an exception while processing a pipeline run.

```php
do_action( 'pearblog_pipeline_cron_error', int $site_id, string $message );
```

| Parameter  | Type     | Description               |
|------------|----------|---------------------------|
| `$site_id` | `int`    | WordPress site / blog ID. |
| `$message` | `string` | Exception message.        |

**Source:** `Scheduler/CronManager.php`

---

### Background Processing

#### `pearblog_bg_run_pipeline`

Fires inside the background queue worker when it picks up a job.  This is the hook that `ContentPipeline` uses to run the pipeline asynchronously.

```php
do_action( 'pearblog_bg_run_pipeline', array $job );
```

| Parameter | Type    | Description                                                          |
|-----------|---------|----------------------------------------------------------------------|
| `$job`    | `array` | Job data: `id` (string), `topic` (string), `tenant_id` (int), `attempts` (int), `created_at` (int). |

**Source:** `Pipeline/BackgroundProcessor.php`

---

#### `pearblog_bg_job_completed`

Fires after a background job finishes successfully.

```php
do_action( 'pearblog_bg_job_completed', array $job );
```

| Parameter | Type    | Description    |
|-----------|---------|----------------|
| `$job`    | `array` | Completed job. |

**Source:** `Pipeline/BackgroundProcessor.php`

---

#### `pearblog_bg_job_failed`

Fires when a background job exceeds the maximum retry count and is permanently marked as failed.

```php
do_action( 'pearblog_bg_job_failed', array $job, \Throwable $exception );
```

| Parameter    | Type         | Description                         |
|--------------|--------------|-------------------------------------|
| `$job`       | `array`      | Failed job data.                    |
| `$exception` | `\Throwable` | The exception that caused failure.  |

**Source:** `Pipeline/BackgroundProcessor.php`

---

### Content Operations

#### `pearblog_content_refreshed`

Fires after `ContentRefreshEngine` rewrites an existing post with fresh AI-generated content.

```php
do_action( 'pearblog_content_refreshed', int $post_id, string $final_title );
```

| Parameter      | Type     | Description                     |
|----------------|----------|---------------------------------|
| `$post_id`     | `int`    | Post that was refreshed.        |
| `$final_title` | `string` | New post title after refresh.   |

**Source:** `Content/ContentRefreshEngine.php`

---

#### `pearblog_quality_scored`

Fires after the `QualityScorer` has computed a composite quality score for a post.

```php
do_action( 'pearblog_quality_scored', int $post_id, float $score );
```

| Parameter  | Type    | Description                                        |
|------------|---------|----------------------------------------------------|
| `$post_id` | `int`   | Post ID.                                           |
| `$score`   | `float` | Composite quality score (0–100).                   |

**Source:** `Content/QualityScorer.php`

---

#### `pearblog_translation_created`

Fires after `MultilingualManager` creates a translated copy of a post.

```php
do_action( 'pearblog_translation_created', int $new_post_id, int $source_post_id, string $language );
```

| Parameter        | Type     | Description                       |
|------------------|----------|-----------------------------------|
| `$new_post_id`   | `int`    | Newly created translated post.    |
| `$source_post_id`| `int`    | Original post that was translated.|
| `$language`      | `string` | Target language code (e.g. `fr`). |

**Source:** `Content/MultilingualManager.php`

---

### SEO & Publishing

#### `pearblog_seo_applied`

Fires after `SEOEngine` has written the SEO title and meta description to a post.

```php
do_action( 'pearblog_seo_applied', int $post_id, string $title, string $meta_description );
```

| Parameter          | Type     | Description             |
|--------------------|----------|-------------------------|
| `$post_id`         | `int`    | Post ID.                |
| `$title`           | `string` | SEO title.              |
| `$meta_description`| `string` | SEO meta description.   |

**Source:** `SEO/SEOEngine.php`

---

### Social & Distribution

#### `pearblog_social_published`

Fires after `SocialPublisher` has dispatched a post to one or more social networks.

```php
do_action( 'pearblog_social_published', int $post_id, array $results );
```

| Parameter  | Type    | Description                                                         |
|------------|---------|---------------------------------------------------------------------|
| `$post_id` | `int`   | Post ID.                                                            |
| `$results` | `array` | Map of network slug → result (bool or array with status/error).     |

**Source:** `Social/SocialPublisher.php`

---

### Monitoring & SLA

#### `pearblog_sla_breached`

Fires when a configured SLA target is violated during an `SLAManager` evaluation cycle.

```php
do_action( 'pearblog_sla_breached', string $metric, mixed $target, mixed $actual );
```

| Parameter | Type     | Description                                              |
|-----------|----------|----------------------------------------------------------|
| `$metric` | `string` | SLA metric key (e.g. `uptime_pct`, `pipeline_success_pct`). |
| `$target` | `mixed`  | Configured target value.                                 |
| `$actual` | `mixed`  | Actual measured value that breached the target.          |

**Source:** `Monitoring/SLAManager.php`

---

#### `pearblog_sla_report_generated`

Fires after the monthly SLA report is generated.

```php
do_action( 'pearblog_sla_report_generated', array $report );
```

| Parameter | Type    | Description                                                                 |
|-----------|---------|-----------------------------------------------------------------------------|
| `$report` | `array` | Full report: `period`, `targets`, `actuals`, `breached_metrics`, `summary`. |

**Source:** `Monitoring/SLAManager.php`

---

### A/B Testing

#### `pearblog_abtest_winner_promoted`

Fires when `ABTestEngine` auto-promotes the winning prompt variant for an A/B test.

```php
do_action( 'pearblog_abtest_winner_promoted', string $test_id, string $winner, float $avg_a, float $avg_b );
```

| Parameter  | Type     | Description                                   |
|------------|----------|-----------------------------------------------|
| `$test_id` | `string` | Test ID (e.g. `ab_a1b2c3d4`).                 |
| `$winner`  | `string` | Winning variant: `'a'` or `'b'`.              |
| `$avg_a`   | `float`  | Average quality score for variant A.          |
| `$avg_b`   | `float`  | Average quality score for variant B.          |

**Source:** `Testing/ABTestEngine.php`

---

### CDN & Cache

#### `pearblog_cdn_offloaded`

Fires after `CdnManager` successfully uploads an attachment image to a CDN and stores the CDN URL.

```php
do_action( 'pearblog_cdn_offloaded', int $attachment_id, string $cdn_url, string $provider );
```

| Parameter       | Type     | Description                                    |
|-----------------|----------|------------------------------------------------|
| `$attachment_id`| `int`    | WP attachment post ID.                         |
| `$cdn_url`      | `string` | Public CDN URL for the image.                  |
| `$provider`     | `string` | Provider slug: `'bunny'` or `'cloudflare'`.    |

**Source:** `Cache/CdnManager.php`

---

### Admin Trigger

#### `pearblog_run_pipeline`

Fires when an admin manually triggers a pipeline run from the Admin Panel UI (the **"Run Now"** button).

```php
do_action( 'pearblog_run_pipeline' );
```

_(No parameters.)_

**Source:** `Admin/AdminPage.php`

---

### CLI / Autopilot

#### `pearblog_autopilot_event`

Fires each time the `AutopilotRunner` transitions between tasks.

```php
do_action( 'pearblog_autopilot_event', string $task_id, string $status, string $notes );
```

| Parameter  | Type     | Description                                   |
|------------|----------|-----------------------------------------------|
| `$task_id` | `string` | Task identifier (e.g. `TASK_1.1`).            |
| `$status`  | `string` | `'STARTED'`, `'COMPLETED'`, or `'FAILED'`.    |
| `$notes`   | `string` | Human-readable notes about the transition.    |

**Source:** `CLI/AutopilotRunner.php`

---

## Filter Hooks

### Prompt Generation

#### `pearblog_prompt`

The central prompt filter.  **Every** `PromptBuilder` subclass passes the assembled prompt through this filter before returning it to the AI client.

```php
apply_filters( 'pearblog_prompt', string $prompt, string $topic, SiteProfile $profile )
```

| Parameter  | Type          | Description                                |
|------------|---------------|--------------------------------------------|
| `$prompt`  | `string`      | Assembled prompt text. **Return this.**    |
| `$topic`   | `string`      | The article topic / keyword.               |
| `$profile` | `SiteProfile` | Active site profile (industry, tone, etc.) |

**Source:** `Content/PromptBuilder.php` (and all subclasses)

**Example:**
```php
add_filter( 'pearblog_prompt', function ( string $prompt, string $topic ): string {
    return $prompt . "\n\nAlways include a disclaimer at the end of the article.";
}, 10, 2 );
```

---

#### `pearblog_travel_prompt`

Applied inside `TravelPromptBuilder` and `BeskidyPromptBuilder` before the final `pearblog_prompt` filter.  Use it to customise travel-specific prompts without affecting other niches.

```php
apply_filters( 'pearblog_travel_prompt', string $prompt, string $topic, SiteProfile $profile )
```

**Source:** `Content/TravelPromptBuilder.php`, `Content/BeskidyPromptBuilder.php`

---

#### `pearblog_beskidy_prompt`

Applied inside `BeskidyPromptBuilder` (Polish mountain travel niche) before the travel and main prompt filters.

```php
apply_filters( 'pearblog_beskidy_prompt', string $prompt, string $topic, SiteProfile $profile )
```

**Source:** `Content/BeskidyPromptBuilder.php`

---

### Builder Selection

#### `pearblog_prompt_builder_class`

Allows replacing the automatically selected `PromptBuilder` class with a custom one.  Return a fully-qualified class name to override the factory's default choice.

```php
apply_filters( 'pearblog_prompt_builder_class', string|null $class_name, SiteProfile $profile )
```

| Parameter     | Type          | Description                                               |
|---------------|---------------|-----------------------------------------------------------|
| `$class_name` | `string|null` | Return a class name to override, or `null` to use default.|
| `$profile`    | `SiteProfile` | Active site profile.                                      |

**Source:** `Content/PromptBuilderFactory.php`

**Example:**
```php
add_filter( 'pearblog_prompt_builder_class', function ( ?string $class, SiteProfile $profile ): ?string {
    if ( 'legal' === $profile->industry ) {
        return MyPlugin\LegalPromptBuilder::class;
    }
    return $class;
}, 10, 2 );
```

---

#### `pearblog_is_beskidy_content`

Boolean filter used by `PromptBuilderFactory` to decide whether to use `BeskidyPromptBuilder`.  Return `true` if the content should be treated as Polish Beskidy mountain travel content.

```php
apply_filters( 'pearblog_is_beskidy_content', bool $is_beskidy, string $industry, SiteProfile $profile )
```

**Source:** `Content/PromptBuilderFactory.php`

---

### Monetisation

#### `pearblog_monetized_content`

Fires after the `MonetizationEngine` applies all monetisation injections (affiliate boxes, AdSense placeholders, SaaS CTAs).  The return value replaces the post content.

```php
apply_filters( 'pearblog_monetized_content', string $content, int $post_id, SiteProfile $profile )
```

| Parameter  | Type          | Description                         |
|------------|---------------|-------------------------------------|
| `$content` | `string`      | Monetised post content. **Return.** |
| `$post_id` | `int`         | Post ID.                            |
| `$profile` | `SiteProfile` | Active site profile.                |

**Source:** `Monetization/MonetizationEngine.php`

---

#### `pearblog_affiliate_content`

Applied when inserting an affiliate product box into content.

```php
apply_filters( 'pearblog_affiliate_content', string $content, int $post_id, string $location, string $affiliate_id )
```

| Parameter       | Type     | Description                                    |
|-----------------|----------|------------------------------------------------|
| `$content`      | `string` | Content with affiliate box inserted.           |
| `$post_id`      | `int`    | Post ID.                                       |
| `$location`     | `string` | Geographic location context.                   |
| `$affiliate_id` | `string` | Affiliate program ID.                          |

**Source:** `Monetization/MonetizationEngine.php`

---

#### `pearblog_location_keywords`

Filter the list of geographic keywords used to identify location-based affiliate opportunities.

```php
apply_filters( 'pearblog_location_keywords', array $keywords, string $industry )
```

**Source:** `Monetization/MonetizationEngine.php`

---

#### `pearblog_saas_cta_content`

Applied when inserting a SaaS call-to-action block into content.

```php
apply_filters( 'pearblog_saas_cta_content', string $content, array $matched_products )
```

**Source:** `Monetization/MonetizationEngine.php`

---

#### `pearblog_saas_products`

Filter the list of SaaS products used for CTA injection.  Useful for adding or removing products programmatically.

```php
apply_filters( 'pearblog_saas_products', array $products )
```

Each entry: `[ 'name' => string, 'url' => string, 'description' => string, 'keywords' => string[] ]`

**Source:** `Monetization/MonetizationEngine.php`

---

### SEO & Internal Links

#### `pearblog_internal_links_applied`

Applied after `InternalLinker` inserts internal links into post content.

```php
apply_filters( 'pearblog_internal_links_applied', string $content, int $post_id, int $links_added )
```

| Parameter     | Type     | Description                             |
|---------------|----------|-----------------------------------------|
| `$content`    | `string` | Content with links applied. **Return.** |
| `$post_id`    | `int`    | Post ID.                                |
| `$links_added`| `int`    | Number of links that were injected.     |

**Source:** `SEO/InternalLinker.php`

---

### Monitoring Thresholds

#### `pearblog_performance_thresholds`

Filter the default performance alert thresholds used by `PerformanceDashboard`.

```php
apply_filters( 'pearblog_performance_thresholds', array $thresholds )
```

Default structure:
```php
[
    'memory_mb'        => 128,
    'db_queries'       => 50,
    'response_time_ms' => 500,
    // ... (see PerformanceDashboard::DEFAULT_THRESHOLDS)
]
```

**Source:** `Monitoring/PerformanceDashboard.php`

---

### Image Generation

#### `pearblog_image_prompt`

Applied to the image-generation prompt before it is sent to the AI image provider.

```php
apply_filters( 'pearblog_image_prompt', string $prompt, string $topic, string $style )
```

| Parameter | Type     | Description                                    |
|-----------|----------|------------------------------------------------|
| `$prompt` | `string` | Image prompt text. **Return.**                 |
| `$topic`  | `string` | The article topic used as the base prompt.     |
| `$style`  | `string` | Image style slug (e.g. `'photorealistic'`).   |

**Source:** `AI/ImageGenerator.php`

---

## Quick Examples

### Log every published article to a custom table

```php
add_action( 'pearblog_pipeline_completed', function ( int $post_id, string $topic ): void {
    global $wpdb;
    $wpdb->insert( $wpdb->prefix . 'my_pipeline_log', [
        'post_id'    => $post_id,
        'topic'      => $topic,
        'created_at' => current_time( 'mysql' ),
    ] );
}, 10, 2 );
```

### Inject a disclaimer into every AI-generated prompt

```php
add_filter( 'pearblog_prompt', function ( string $prompt ): string {
    return $prompt . "\n\nIMPORTANT: Always include a medical disclaimer if the article touches on health topics.";
} );
```

### Register a custom PromptBuilder for a new niche

```php
// In your plugin or mu-plugin:
add_filter( 'pearblog_prompt_builder_class', function ( ?string $class, SiteProfile $profile ): ?string {
    if ( str_contains( strtolower( $profile->industry ), 'legal' ) ) {
        return MyPlugin\LegalPromptBuilder::class;
    }
    return $class;
}, 10, 2 );
```

### Add a product to the SaaS CTA pool

```php
add_filter( 'pearblog_saas_products', function ( array $products ): array {
    $products[] = [
        'name'        => 'MyCoolSaaS',
        'url'         => 'https://mycoolsaas.com?ref=pearblog',
        'description' => 'The best SaaS for your niche.',
        'keywords'    => [ 'project management', 'team collaboration' ],
    ];
    return $products;
} );
```

### Alert on SLA breach

```php
add_action( 'pearblog_sla_breached', function ( string $metric, $target, $actual ): void {
    // Send a custom PagerDuty alert, post to a webhook, etc.
    error_log( "SLA BREACH [$metric]: target=$target actual=$actual" );
}, 10, 3 );
```

---

## Creating a Custom Extension

Use the scaffolding tool to generate ready-to-use boilerplate:

```bash
# New industry-specific prompt builder
wp pearblog scaffold prompt-builder LegalPromptBuilder --industry="legal services"

# New AI provider adapter
wp pearblog scaffold provider MistralProvider
```

The generated files include all required method stubs, PHPDoc, and placeholder hook calls.  Edit the `build()` / `complete()` method and register your class via the hooks documented above.

---

*Last updated: v7.8.0 — covers all 33 hooks (20 actions + 13 filters)*

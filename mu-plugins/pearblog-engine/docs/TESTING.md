# 🧪 Testing Guide

> PHPUnit test architecture for PearBlog Engine v9.0.

---

## Overview

| Metric | Value |
|--------|-------|
| Total tests | 1 760 |
| Unit test files | 98 |
| Integration test files | 4 |
| Failures | 0 |
| Framework | PHPUnit 10.x |
| PHP version | 8.1+ |

---

## Running Tests

```bash
cd mu-plugins/pearblog-engine

# Install dependencies
composer install

# Run full suite
vendor/bin/phpunit

# Run specific test class
vendor/bin/phpunit tests/php/Unit/QuizEngineTest.php

# Run tests matching a pattern
vendor/bin/phpunit --filter "test_push_registers_cron"

# Run with coverage (requires Xdebug or PCOV)
vendor/bin/phpunit --coverage-html /tmp/coverage
```

---

## Directory Structure

```
tests/php/
├── bootstrap.php                    # PHPUnit bootstrap — WP function stubs
├── namespace-stubs.php              # Namespace-level stubs (setcookie no-op)
├── Unit/                            # Unit tests (no WP/DB dependency)
│   ├── AIClientTest.php
│   ├── AsyncQueueManagerTest.php
│   ├── ...
│   └── XmlSitemapManagerTest.php
└── Integration/                     # Integration tests (mock interactions)
    ├── AuthenticationIntegrationTest.php
    ├── ContentPipelineIntegrationTest.php
    ├── MonetizationIntegrationTest.php
    └── SEOIntegrationTest.php
```

---

## Bootstrap Architecture

The test bootstrap (`tests/php/bootstrap.php`) provides lightweight stubs for WordPress functions so that unit tests run without a full WP installation. Key stubs include:

### WordPress Functions Stubbed

| Category | Functions |
|----------|-----------|
| **Options** | `get_option`, `update_option`, `delete_option`, `add_option` |
| **Posts** | `get_post`, `get_post_type`, `get_permalink`, `wp_insert_post`, `wp_update_post`, `get_post_meta`, `update_post_meta`, `delete_post_meta`, `get_posts` |
| **Taxonomies** | `get_the_tags`, `wp_get_post_categories`, `get_the_post_thumbnail_url` |
| **Users** | `wp_get_current_user`, `get_current_user_id`, `is_user_logged_in`, `current_user_can` |
| **HTTP** | `wp_remote_get`, `wp_remote_post`, `wp_remote_retrieve_body`, `wp_remote_retrieve_response_code` |
| **Cron** | `wp_schedule_event`, `wp_next_scheduled`, `wp_unschedule_event` |
| **Hooks** | `add_action`, `do_action`, `add_filter`, `apply_filters`, `remove_action` |
| **REST** | `register_rest_route`, `rest_url` |
| **Security** | `wp_verify_nonce`, `wp_create_nonce`, `wp_kses_post`, `esc_html`, `esc_attr`, `esc_url`, `sanitize_text_field` |
| **Utilities** | `wp_mail`, `is_ssl`, `get_the_ID`, `add_query_arg`, `absint`, `wp_parse_args` |
| **Multisite** | `switch_to_blog`, `restore_current_blog`, `get_sites`, `is_multisite` |
| **Transients** | `get_transient`, `set_transient`, `delete_transient` |

### Mock Infrastructure

- **`$wpdb` mock** — Global `$wpdb` object with `prefix`, `prepare()`, `get_results()`, `get_var()`, `get_row()`, `insert()`, `update()`, `delete()`, `query()`
- **`WP_Post` class** — Minimal WP_Post mock for type hints
- **`WP_REST_Request` class** — Minimal REST request mock with `get_param()`, `get_params()`
- **`WP_REST_Response` class** — Minimal REST response mock
- **`WP_Role` class** — Role mock with `has_cap()`, `add_cap()`
- **In-memory option store** — `$GLOBALS['wp_options']` backing `get_option`/`update_option`
- **Action/filter registry** — `$GLOBALS['wp_actions']`/`$GLOBALS['wp_filters']` for verification

---

## Writing New Tests

### Unit Test Template

```php
<?php

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\YourModule\YourClass;

class YourClassTest extends TestCase {

    private YourClass $sut;

    protected function setUp(): void {
        parent::setUp();
        // Reset global state
        $GLOBALS['wp_options'] = [];
        $GLOBALS['wp_actions'] = [];
        $this->sut = new YourClass();
    }

    public function test_something_works(): void {
        $result = $this->sut->do_something();
        $this->assertSame('expected', $result);
    }

    /**
     * @dataProvider provideEdgeCases
     */
    public function test_handles_edge_cases(mixed $input, mixed $expected): void {
        $this->assertSame($expected, $this->sut->process($input));
    }

    public static function provideEdgeCases(): array {
        return [
            'empty input'   => ['', ''],
            'null input'    => [null, null],
            'typical input' => ['hello', 'HELLO'],
        ];
    }
}
```

### Conventions

1. **One test class per source class** — `FooBar.php` → `FooBarTest.php`
2. **Test method naming** — `test_<method>_<scenario>` (e.g., `test_push_registers_cron_hook`)
3. **Reset globals in `setUp()`** — Always clear `$GLOBALS['wp_options']` and `$GLOBALS['wp_actions']`
4. **Use Reflection for private methods** — When testing internal logic via `ReflectionMethod`
5. **Constants testing** — Verify uniqueness and expected values for class constants
6. **REST endpoint tests** — Create `WP_REST_Request` mock, call handler, assert response shape
7. **Action/hook verification** — Check `$GLOBALS['wp_actions']` after calling methods that fire hooks

### Adding Bootstrap Stubs

If your new class calls a WordPress function not yet stubbed:

1. Add the stub to `tests/php/bootstrap.php`
2. Keep stubs minimal — return sensible defaults
3. Use `$GLOBALS['wp_options']` for persistence between calls
4. Document the stub in this file

---

## Test Coverage by Module (v9.0)

| Module | Test Classes | Total Tests |
|--------|-------------|-------------|
| AI | AIClient, SmartProviderRouter, FactChecker, VideoScriptBuilder, ImageGenerator, ImageAnalyzer | ~95 |
| Analytics | PredictiveAnalytics, CohortEngine, ContentROIEngine, ConversionFlowTracker, ConversionTracker | ~85 |
| Content | PromptBuilder, ContentValidator, TopicQueue, CollaborationManager, SmartCalculatorEngine, ContentRefreshEngine, DuplicateDetector, QualityScorer | ~140 |
| Core | Plugin, DistributedLockManager | ~35 |
| DecisionPlatform | QuizEngine, RankingEngine, ComparisonEngine, IntentDetector, LeadGenerator | ~80 |
| Distribution | AMPGenerator, RSSFeedBuilder | ~30 |
| Email | NewsletterBuilder, EmailDigest | ~35 |
| Integration | PT24Bridge, ZapierManager, CTAInjector | ~35 |
| LeadAI | LeadOrchestrator, LeadScore, SLAWatcher | ~45 |
| Monetization | MonetizationEngine, CROEngine, PaywallEngine, RevenueTracker, AffiliateDiscovery | ~90 |
| Monitoring | HealthController, AlertManager, CoreWebVitalsMonitor | ~50 |
| Pipeline | ContentPipeline, AsyncQueueManager, ApprovalWorkflow | ~55 |
| Poradnik | PoradnikEngine, DataEngine, ScoringEngine | ~45 |
| Scheduler | CronManager, PublishScheduler | ~30 |
| SEO | SEOEngine, ProgrammaticSEO, OrphanPageDetector, InternalLinker, SchemaManager | ~85 |
| Security | SecurityAuditor, RBACManager, ContentModerator, ComplianceExporter, PIIDetector | ~75 |
| Social | SocialPublisher, PushNotificationPublisher | ~30 |
| Tenant | TenantIsolator, BillingEngine, TenantOnboardingController, SiteProfile | ~65 |
| Testing | ABTestEngine, AIVariantGenerator, BayesianOptimizer | ~50 |

---

## CI Integration

Tests run automatically on every push via GitHub Actions. The workflow:

1. Checks out the repository
2. Sets up PHP 8.1+ with Composer
3. Installs dependencies
4. Runs `vendor/bin/phpunit`
5. Fails the build on any test failure

---

*PearBlog Engine v9.0 — Testing Guide*

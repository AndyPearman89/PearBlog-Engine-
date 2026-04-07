# Integration Tests

## Overview

Integration tests validate end-to-end workflows across multiple components of the PearBlog Engine.

## Test Environment

Integration tests require a WordPress environment to run successfully. They test the complete pipeline from topic queue to published post, including:

- Topic queue management
- Prompt building
- AI content generation
- Duplicate detection
- SEO metadata application
- Monetization injection
- Internal linking
- Featured image generation
- Post publishing
- Quality scoring

## Running Integration Tests

### Option 1: With WordPress Test Environment

If you have a WordPress test environment set up (e.g., with WP-CLI Test Framework):

```bash
cd mu-plugins/pearblog-engine
vendor/bin/phpunit --testsuite Integration
```

### Option 2: In CI/CD Pipeline

Integration tests are designed to run in a CI/CD environment with WordPress installed. See `.github/workflows/test.yml` for the CI configuration.

### Option 3: Manual Testing

For local development without a full WordPress test environment, manual end-to-end testing is recommended:

1. Install the plugin in a local WordPress instance
2. Configure OpenAI API key
3. Add topics to the queue via WP Admin → PearBlog Engine → Queue tab
4. Trigger the pipeline manually or wait for cron
5. Verify published posts have correct SEO metadata, monetization, internal links, and featured images

## Test Structure

**`ContentPipelineIntegrationTest.php`** - Main end-to-end pipeline test

Tests include:
- Complete pipeline execution from topic to published post
- Empty queue handling
- Duplicate content detection and skipping
- Multiple topic processing in sequence
- Error handling for non-critical failures

## WordPress Dependencies

Integration tests require these WordPress functions to be available:
- `wp_insert_post()`, `wp_update_post()`, `get_post()`
- `update_post_meta()`, `get_post_meta()`
- `add_action()`, `do_action()`, `add_filter()`
- `get_current_blog_id()`, `get_site_url()`
- `get_option()`, `update_option()`, `delete_option()`
- And many more...

For unit tests that don't require WordPress, see `tests/php/Unit/`.

## Future Improvements

- Add WordPress test framework integration (e.g., WP_UnitTestCase)
- Mock fewer WordPress functions in favor of actual WordPress test database
- Add performance benchmarking for full pipeline execution
- Add tests for webhook firing and alert dispatching

<?php
/**
 * Unit tests for MultilingualManager.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Content\MultilingualManager;
use PearBlogEngine\AI\AIClient;

/**
 * Stub AIClient that returns predictable responses without making HTTP calls.
 */
class StubAIClientForML extends AIClient {

	private string $response;

	public function __construct( string $response = 'TRANSLATED_CONTENT' ) {
		// Skip parent constructor (would need WP options).
		$this->response = $response;
	}

	public function generate( string $prompt, int $max_tokens = 2048 ): string {
		return $this->response;
	}
}

class MultilingualManagerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_post_meta']  = [];
		$GLOBALS['_posts']      = [];
		$GLOBALS['_post_list']  = [];
		$GLOBALS['_next_post_id'] = 100;
	}

	// -----------------------------------------------------------------------
	// is_enabled
	// -----------------------------------------------------------------------

	public function test_disabled_by_default(): void {
		$mgr = new MultilingualManager();
		$this->assertFalse( $mgr->is_enabled() );
	}

	public function test_enabled_when_option_set(): void {
		update_option( MultilingualManager::OPTION_ENABLED, true );
		$mgr = new MultilingualManager();
		$this->assertTrue( $mgr->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// get/set target languages
	// -----------------------------------------------------------------------

	public function test_get_target_languages_empty_by_default(): void {
		$mgr = new MultilingualManager();
		$this->assertSame( [], $mgr->get_target_languages() );
	}

	public function test_set_and_get_target_languages(): void {
		$mgr = new MultilingualManager();
		$mgr->set_target_languages( [ 'de', 'fr', 'es' ] );
		$this->assertSame( [ 'de', 'fr', 'es' ], $mgr->get_target_languages() );
	}

	public function test_set_target_languages_sanitizes(): void {
		$mgr = new MultilingualManager();
		$mgr->set_target_languages( [ '  de  ', '', 'fr' ] );
		$langs = $mgr->get_target_languages();
		$this->assertContains( 'de', $langs );
		$this->assertContains( 'fr', $langs );
	}

	// -----------------------------------------------------------------------
	// get_post_status
	// -----------------------------------------------------------------------

	public function test_default_post_status_is_draft(): void {
		$mgr = new MultilingualManager();
		$this->assertSame( 'draft', $mgr->get_post_status() );
	}

	public function test_custom_post_status(): void {
		update_option( MultilingualManager::OPTION_POST_STATUS, 'publish' );
		$mgr = new MultilingualManager();
		$this->assertSame( 'publish', $mgr->get_post_status() );
	}

	public function test_invalid_post_status_falls_back_to_draft(): void {
		update_option( MultilingualManager::OPTION_POST_STATUS, 'invalid_status' );
		$mgr = new MultilingualManager();
		$this->assertSame( 'draft', $mgr->get_post_status() );
	}

	// -----------------------------------------------------------------------
	// get_prompt_template
	// -----------------------------------------------------------------------

	public function test_default_prompt_template_contains_placeholders(): void {
		$mgr = new MultilingualManager();
		$this->assertStringContainsString( '{language}', $mgr->get_prompt_template() );
		$this->assertStringContainsString( '{source}', $mgr->get_prompt_template() );
	}

	public function test_custom_prompt_template(): void {
		update_option( MultilingualManager::OPTION_PROMPT_TEMPLATE, 'Translate {source} to {language}.' );
		$mgr = new MultilingualManager();
		$this->assertSame( 'Translate {source} to {language}.', $mgr->get_prompt_template() );
	}

	// -----------------------------------------------------------------------
	// call_ai
	// -----------------------------------------------------------------------

	public function test_call_ai_substitutes_placeholders_in_prompt(): void {
		$called_prompt = '';
		$stub = new class( 'Translated.' ) extends StubAIClientForML {
			public ?string $last_prompt = null;
			public function generate( string $prompt, int $max_tokens = 2048 ): string {
				$this->last_prompt = $prompt;
				return 'Translated.';
			}
		};

		update_option( MultilingualManager::OPTION_ENABLED, true );
		$mgr = new MultilingualManager( $stub );
		$result = $mgr->call_ai( 'Hello world', 'de' );

		$this->assertSame( 'Translated.', $result );
		$this->assertStringContainsString( 'de', $stub->last_prompt );
		$this->assertStringContainsString( 'Hello world', $stub->last_prompt );
	}

	// -----------------------------------------------------------------------
	// translate_post — disabled guard
	// -----------------------------------------------------------------------

	public function test_translate_post_returns_empty_when_disabled(): void {
		$stub = new StubAIClientForML( 'TRANSLATED' );
		$mgr  = new MultilingualManager( $stub );
		$this->assertSame( [], $mgr->translate_post( 1, [ 'de' ] ) );
	}

	// -----------------------------------------------------------------------
	// translate_to_language — happy path
	// -----------------------------------------------------------------------

	public function test_translate_to_language_creates_post(): void {
		update_option( MultilingualManager::OPTION_ENABLED, true );

		$post = new \WP_Post( [
			'ID'           => 1,
			'post_title'   => 'My Article',
			'post_content' => 'Full content here.',
			'post_status'  => 'publish',
		] );
		$GLOBALS['_posts'][1] = $post;

		$stub = new StubAIClientForML( 'Mein Artikel' );
		$mgr  = new MultilingualManager( $stub );

		$new_id = $mgr->translate_to_language( 1, 'de' );
		$this->assertIsInt( $new_id );
		$this->assertGreaterThan( 0, $new_id );
	}

	public function test_translate_to_language_stores_meta(): void {
		update_option( MultilingualManager::OPTION_ENABLED, true );

		$post = new \WP_Post( [
			'ID'           => 2,
			'post_title'   => 'Another Article',
			'post_content' => 'Content.',
			'post_status'  => 'publish',
		] );
		$GLOBALS['_posts'][2] = $post;

		$stub   = new StubAIClientForML( 'Translated content' );
		$mgr    = new MultilingualManager( $stub );
		$new_id = $mgr->translate_to_language( 2, 'fr' );

		$this->assertNotNull( $new_id );

		$meta_lang   = get_post_meta( $new_id, MultilingualManager::META_LANGUAGE, true );
		$meta_source = get_post_meta( $new_id, MultilingualManager::META_SOURCE_POST_ID, true );

		$this->assertSame( 'fr', $meta_lang );
		$this->assertSame( 2, (int) $meta_source );
	}

	public function test_translate_to_language_returns_null_for_missing_post(): void {
		update_option( MultilingualManager::OPTION_ENABLED, true );
		// Post 999 does not exist.
		$stub = new StubAIClientForML( 'Translation' );
		$mgr  = new MultilingualManager( $stub );
		$this->assertNull( $mgr->translate_to_language( 999, 'es' ) );
	}

	// -----------------------------------------------------------------------
	// translate_post — multiple languages
	// -----------------------------------------------------------------------

	public function test_translate_post_maps_all_languages(): void {
		update_option( MultilingualManager::OPTION_ENABLED, true );

		$post = new \WP_Post( [
			'ID'           => 10,
			'post_title'   => 'Multi-Language Test',
			'post_content' => 'Content to translate.',
			'post_status'  => 'publish',
		] );
		$GLOBALS['_posts'][10] = $post;

		$stub   = new StubAIClientForML( 'Translated.' );
		$mgr    = new MultilingualManager( $stub );
		$result = $mgr->translate_post( 10, [ 'de', 'fr' ] );

		$this->assertArrayHasKey( 'de', $result );
		$this->assertArrayHasKey( 'fr', $result );
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function test_meta_source_post_id_constant(): void {
		$this->assertSame( '_pearblog_ml_source_post_id', MultilingualManager::META_SOURCE_POST_ID );
	}

	public function test_meta_language_constant(): void {
		$this->assertSame( '_pearblog_ml_language', MultilingualManager::META_LANGUAGE );
	}

	public function test_action_created_constant(): void {
		$this->assertSame( 'pearblog_translation_created', MultilingualManager::ACTION_CREATED );
	}
}

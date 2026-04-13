<?php
/**
 * Unit tests for PersonaBuilder.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Content\PersonaBuilder;

class PersonaBuilderTest extends TestCase {

	private PersonaBuilder $builder;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options'] = [];
		$this->builder = new PersonaBuilder();
	}

	// -----------------------------------------------------------------------
	// save_persona / get_persona
	// -----------------------------------------------------------------------

	public function test_save_persona_returns_slug(): void {
		$slug = $this->builder->save_persona( 'travel-jane', 'Travel Jane', 'Expert travel writer.' );
		$this->assertSame( 'travel-jane', $slug );
	}

	public function test_saved_persona_is_retrievable(): void {
		$this->builder->save_persona( 'tech-bob', 'Tech Bob', 'Software developer and blogger.', 'analytical', 'professional' );
		$persona = $this->builder->get_persona( 'tech-bob' );

		$this->assertNotNull( $persona );
		$this->assertSame( 'tech-bob', $persona['slug'] );
		$this->assertSame( 'Tech Bob', $persona['name'] );
		$this->assertSame( 'Software developer and blogger.', $persona['bio'] );
		$this->assertSame( 'analytical', $persona['style'] );
		$this->assertSame( 'professional', $persona['tone'] );
	}

	public function test_get_persona_returns_null_for_unknown_slug(): void {
		$this->assertNull( $this->builder->get_persona( 'unknown' ) );
	}

	public function test_save_persona_sanitises_slug(): void {
		// The bootstrap sanitize_key stub strips non [a-z0-9_-] chars (lowercase only).
		$slug = $this->builder->save_persona( 'my persona', 'My Persona', 'Bio.' );
		// sanitize_key stub: 'my persona' → strip non-alphanumeric/_- → 'mypersona'.
		$this->assertSame( 'mypersona', $slug );
	}

	public function test_get_all_personas_returns_all(): void {
		$this->builder->save_persona( 'a', 'A', 'Bio A' );
		$this->builder->save_persona( 'b', 'B', 'Bio B' );

		$all = $this->builder->get_all_personas();
		$this->assertCount( 2, $all );
		$this->assertArrayHasKey( 'a', $all );
		$this->assertArrayHasKey( 'b', $all );
	}

	public function test_save_persona_stores_vocabulary(): void {
		$this->builder->save_persona( 'food-writer', 'Food Writer', 'Bio.', '', '', [ 'delicious', 'savoury', '-bland' ] );
		$persona = $this->builder->get_persona( 'food-writer' );
		$this->assertSame( [ 'delicious', 'savoury', '-bland' ], $persona['vocabulary'] );
	}

	public function test_update_preserves_created_at(): void {
		$this->builder->save_persona( 'editor', 'Editor', 'Original bio.' );
		$first = $this->builder->get_persona( 'editor' );

		$this->builder->save_persona( 'editor', 'Editor', 'Updated bio.' );
		$second = $this->builder->get_persona( 'editor' );

		$this->assertSame( $first['created_at'], $second['created_at'] );
		$this->assertSame( 'Updated bio.', $second['bio'] );
	}

	// -----------------------------------------------------------------------
	// delete_persona
	// -----------------------------------------------------------------------

	public function test_delete_returns_true_when_found(): void {
		$this->builder->save_persona( 'remove-me', 'Remove Me', 'Bio.' );
		$this->assertTrue( $this->builder->delete_persona( 'remove-me' ) );
		$this->assertNull( $this->builder->get_persona( 'remove-me' ) );
	}

	public function test_delete_returns_false_for_unknown(): void {
		$this->assertFalse( $this->builder->delete_persona( 'ghost' ) );
	}

	public function test_delete_active_persona_resets_active(): void {
		$this->builder->save_persona( 'active', 'Active', 'Bio.' );
		$this->builder->set_active( 'active' );
		$this->builder->delete_persona( 'active' );

		$this->assertSame( PersonaBuilder::PERSONA_NONE, $this->builder->get_active_slug() );
	}

	// -----------------------------------------------------------------------
	// Active persona
	// -----------------------------------------------------------------------

	public function test_get_active_slug_defaults_to_none(): void {
		$this->assertSame( PersonaBuilder::PERSONA_NONE, $this->builder->get_active_slug() );
	}

	public function test_set_and_get_active(): void {
		$this->builder->save_persona( 'my-persona', 'My Persona', 'Bio.' );
		$this->builder->set_active( 'my-persona' );
		$this->assertSame( 'my-persona', $this->builder->get_active_slug() );
	}

	public function test_get_active_persona_returns_null_when_none(): void {
		$this->assertNull( $this->builder->get_active_persona() );
	}

	public function test_get_active_persona_returns_record(): void {
		$this->builder->save_persona( 'writer', 'Jane Writer', 'Experienced journalist.' );
		$this->builder->set_active( 'writer' );
		$active = $this->builder->get_active_persona();

		$this->assertNotNull( $active );
		$this->assertSame( 'Jane Writer', $active['name'] );
	}

	// -----------------------------------------------------------------------
	// enrich_prompt
	// -----------------------------------------------------------------------

	public function test_enrich_prompt_unchanged_when_no_active_persona(): void {
		$prompt = 'Write about travel.';
		$this->assertSame( $prompt, $this->builder->enrich_prompt( $prompt ) );
	}

	public function test_enrich_prompt_appends_persona_block(): void {
		$this->builder->save_persona( 'journo', 'Jane Journo', 'Award-winning travel journalist.', 'vivid and narrative', 'warm' );
		$this->builder->set_active( 'journo' );

		$result = $this->builder->enrich_prompt( 'Write about Paris.' );

		$this->assertStringContainsString( 'Write about Paris.', $result );
		$this->assertStringContainsString( 'Jane Journo', $result );
		$this->assertStringContainsString( 'Award-winning travel journalist', $result );
		$this->assertStringContainsString( 'vivid and narrative', $result );
		$this->assertStringContainsString( 'warm', $result );
	}

	public function test_enrich_prompt_includes_preferred_vocabulary(): void {
		$this->builder->save_persona( 'foodie', 'Food Blogger', 'Culinary enthusiast.', '', '', [ 'umami', 'rustic', '-bland' ] );
		$this->builder->set_active( 'foodie' );

		$result = $this->builder->enrich_prompt( 'Write a food review.' );

		$this->assertStringContainsString( 'umami', $result );
		$this->assertStringContainsString( 'rustic', $result );
		$this->assertStringContainsString( 'bland', $result );     // avoid list
	}

	public function test_enrich_prompt_omits_empty_fields(): void {
		$this->builder->save_persona( 'minimal', 'Minimal Author', 'Short bio.' );
		$this->builder->set_active( 'minimal' );

		$result = $this->builder->enrich_prompt( 'Write something.' );

		$this->assertStringContainsString( 'Minimal Author', $result );
		$this->assertStringNotContainsString( 'Writing style:', $result );
		$this->assertStringNotContainsString( 'Tone:', $result );
	}
}

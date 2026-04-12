<?php
/**
 * Persona Builder — injects configurable author voice and style into prompts.
 *
 * Site administrators define one or more "personas" (author profiles), each
 * with a name, bio blurb, writing style descriptor, preferred tone, and
 * optional vocabulary hints.  When a prompt is generated, the active persona's
 * details are appended so the AI writes as that author.
 *
 * Storage:
 *   pearblog_personas         – JSON-encoded array of persona records
 *   pearblog_active_persona   – slug of the currently active persona
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

/**
 * Manages author personas and applies them to AI prompts.
 */
class PersonaBuilder {

	/** WP option: stored persona array. */
	public const OPTION_PERSONAS       = 'pearblog_personas';

	/** WP option: active persona slug. */
	public const OPTION_ACTIVE_PERSONA = 'pearblog_active_persona';

	/** Slug used when no persona should be applied. */
	public const PERSONA_NONE = 'none';

	// -----------------------------------------------------------------------
	// CRUD
	// -----------------------------------------------------------------------

	/**
	 * Create or update a persona.
	 *
	 * @param string $slug        Unique identifier (lowercase, no spaces).
	 * @param string $name        Human-readable display name.
	 * @param string $bio         Short author bio injected into the prompt.
	 * @param string $style       Writing-style descriptor (e.g. "conversational and witty").
	 * @param string $tone        Tone override (e.g. "professional", "casual").
	 * @param array  $vocabulary  Optional list of preferred or avoided words.
	 * @return string             The stored slug.
	 */
	public function save_persona(
		string $slug,
		string $name,
		string $bio,
		string $style = '',
		string $tone  = '',
		array  $vocabulary = []
	): string {
		$slug    = sanitize_key( $slug );
		$personas = $this->get_all_personas();

		$personas[ $slug ] = [
			'slug'       => $slug,
			'name'       => sanitize_text_field( $name ),
			'bio'        => sanitize_text_field( $bio ),
			'style'      => sanitize_text_field( $style ),
			'tone'       => sanitize_text_field( $tone ),
			'vocabulary' => array_map( 'sanitize_text_field', $vocabulary ),
			'created_at' => isset( $personas[ $slug ]['created_at'] )
				? $personas[ $slug ]['created_at']
				: gmdate( 'Y-m-d H:i:s' ),
			'updated_at' => gmdate( 'Y-m-d H:i:s' ),
		];

		update_option( self::OPTION_PERSONAS, wp_json_encode( $personas ) );
		return $slug;
	}

	/**
	 * Retrieve a single persona by slug.
	 *
	 * @param string $slug
	 * @return array|null  Persona data array, or null if not found.
	 */
	public function get_persona( string $slug ): ?array {
		$personas = $this->get_all_personas();
		return $personas[ $slug ] ?? null;
	}

	/**
	 * Retrieve all stored personas.
	 *
	 * @return array<string, array>  Associative array keyed by slug.
	 */
	public function get_all_personas(): array {
		$raw = get_option( self::OPTION_PERSONAS, '{}' );
		$decoded = json_decode( is_string( $raw ) ? $raw : '{}', true );
		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Delete a persona.  If it is the active one, active is reset to "none".
	 *
	 * @param string $slug
	 * @return bool  True if found and removed.
	 */
	public function delete_persona( string $slug ): bool {
		$personas = $this->get_all_personas();
		if ( ! isset( $personas[ $slug ] ) ) {
			return false;
		}

		unset( $personas[ $slug ] );
		update_option( self::OPTION_PERSONAS, wp_json_encode( $personas ) );

		if ( $this->get_active_slug() === $slug ) {
			update_option( self::OPTION_ACTIVE_PERSONA, self::PERSONA_NONE );
		}

		return true;
	}

	// -----------------------------------------------------------------------
	// Active persona
	// -----------------------------------------------------------------------

	/**
	 * Set the active persona by slug.
	 *
	 * @param string $slug  Use PERSONA_NONE to disable.
	 */
	public function set_active( string $slug ): void {
		update_option( self::OPTION_ACTIVE_PERSONA, sanitize_key( $slug ) );
	}

	/**
	 * Get the currently active persona slug.
	 */
	public function get_active_slug(): string {
		return (string) get_option( self::OPTION_ACTIVE_PERSONA, self::PERSONA_NONE );
	}

	/**
	 * Get the active persona record, or null if none is set.
	 */
	public function get_active_persona(): ?array {
		$slug = $this->get_active_slug();
		if ( self::PERSONA_NONE === $slug || '' === $slug ) {
			return null;
		}
		return $this->get_persona( $slug );
	}

	// -----------------------------------------------------------------------
	// Prompt enrichment
	// -----------------------------------------------------------------------

	/**
	 * Append the active persona's instructions to a prompt.
	 *
	 * @param string $prompt  Original prompt text.
	 * @return string         Prompt with persona block appended, or unchanged.
	 */
	public function enrich_prompt( string $prompt ): string {
		$persona = $this->get_active_persona();
		if ( null === $persona ) {
			return $prompt;
		}

		$block  = "\n\n---\n## Author Persona\n";
		$block .= "Write this article as **{$persona['name']}**.\n\n";

		if ( ! empty( $persona['bio'] ) ) {
			$block .= "**Author bio:** {$persona['bio']}\n\n";
		}

		if ( ! empty( $persona['style'] ) ) {
			$block .= "**Writing style:** {$persona['style']}\n\n";
		}

		if ( ! empty( $persona['tone'] ) ) {
			$block .= "**Tone:** {$persona['tone']}\n\n";
		}

		if ( ! empty( $persona['vocabulary'] ) ) {
			$prefer  = array_filter( $persona['vocabulary'], fn( $w ) => strncmp( $w, '-', 1 ) !== 0 );
			$avoid   = array_filter( $persona['vocabulary'], fn( $w ) => strncmp( $w, '-', 1 ) === 0 );
			$avoid   = array_map( fn( $w ) => ltrim( $w, '-' ), $avoid );

			if ( ! empty( $prefer ) ) {
				$block .= '**Preferred vocabulary:** ' . implode( ', ', $prefer ) . "\n\n";
			}
			if ( ! empty( $avoid ) ) {
				$block .= '**Avoid these words:** ' . implode( ', ', $avoid ) . "\n\n";
			}
		}

		$block .= "---\n";

		return $prompt . $block;
	}
}

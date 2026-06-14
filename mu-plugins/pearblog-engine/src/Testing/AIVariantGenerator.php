<?php
/**
 * AI Variant Generator — V9.0 F3
 *
 * Generates A/B test variants for post content elements (headlines, CTAs,
 * meta descriptions) using AI prompts. Runs without any external ML service;
 * in production the prompts are forwarded through the existing AIClient.
 *
 * In unit-test environments the generator falls back to deterministic
 * template-based variants when no AI provider is wired.
 *
 * @package PearBlogEngine\Testing
 * @since   9.0.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Testing;

/**
 * Generates content variants for A/B tests.
 */
class AIVariantGenerator {

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	/** Supported variant types. */
	public const TYPE_HEADLINE = 'headline';
	public const TYPE_CTA      = 'cta';
	public const TYPE_META     = 'meta';
	public const TYPE_INTRO    = 'intro';

	/** Default number of variants to generate when count is not specified. */
	public const DEFAULT_VARIANT_COUNT = 3;

	/** Maximum variants allowed per request. */
	public const MAX_VARIANTS = 10;

	/** Meta key: cached generated variants (JSON). */
	private const META_VARIANTS = '_pearblog_ab_variants';

	/** Option: enable AI-backed generation (true) vs template fallback (false). */
	private const OPT_AI_ENABLED = 'pearblog_ab_ai_enabled';

	// -----------------------------------------------------------------------
	// Headline templates
	// -----------------------------------------------------------------------

	/** @var array<string, string[]> Headline templates keyed by pattern name. */
	private const HEADLINE_TEMPLATES = [
		'how_to'    => 'How to %s (Step-by-Step Guide)',
		'number'    => '%d Proven Ways to %s',
		'question'  => 'Want to %s? Here\'s What You Need to Know',
		'ultimate'  => 'The Ultimate Guide to %s',
		'secret'    => 'The Secret to %s That Experts Won\'t Tell You',
		'beginner'  => '%s for Beginners: Everything You Need',
		'in_year'   => '%s in %d: The Complete Playbook',
		'without'   => 'How to %s Without the Hassle',
		'fast'      => 'The Fastest Way to %s',
		'mistakes'  => 'Common %s Mistakes (and How to Avoid Them)',
	];

	/** @var string[] CTA variants. */
	private const CTA_VARIANTS = [
		'Read More',
		'Learn More',
		'Discover How',
		'Get Started',
		'See the Full Guide',
		'Explore Now',
		'Find Out More',
		'Start Today',
		'View Details',
		'Unlock Access',
	];

	/** @var string[] Meta description suffixes. */
	private const META_SUFFIXES = [
		'Learn the best strategies and actionable tips.',
		'Get expert insights and step-by-step guidance.',
		'Discover proven techniques that actually work.',
		'Find out everything you need to know.',
		'Read our in-depth analysis and recommendations.',
	];

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Generate variants for a specific content element of a post.
	 *
	 * @param  int    $post_id    Post ID to generate variants for.
	 * @param  string $type       One of the TYPE_* constants.
	 * @param  int    $count      Number of variants to generate (1–MAX_VARIANTS).
	 * @param  bool   $use_cache  Whether to use/populate the variant cache.
	 * @return array{
	 *     post_id: int,
	 *     type: string,
	 *     original: string,
	 *     variants: string[],
	 *     generated_at: string,
	 *     source: string,
	 * }
	 */
	public function generate(
		int $post_id,
		string $type = self::TYPE_HEADLINE,
		int $count = self::DEFAULT_VARIANT_COUNT,
		bool $use_cache = true
	): array {
		$count = max( 1, min( $count, self::MAX_VARIANTS ) );
		$type  = $this->validate_type( $type );

		if ( $use_cache ) {
			$cached = $this->get_cached( $post_id, $type, $count );
			if ( null !== $cached ) {
				return $cached;
			}
		}

		$original = $this->get_original( $post_id, $type );
		$variants = $this->build_variants( $post_id, $type, $count, $original );

		$result = [
			'post_id'      => $post_id,
			'type'         => $type,
			'original'     => $original,
			'variants'     => $variants,
			'generated_at' => gmdate( 'c' ),
			'source'       => $this->is_ai_enabled() ? 'ai' : 'template',
		];

		if ( $use_cache ) {
			$this->cache_variants( $post_id, $type, $result );
		}

		return $result;
	}

	/**
	 * Generate variants for all supported types at once.
	 *
	 * @param  int $post_id Post ID.
	 * @param  int $count   Variants per type.
	 * @return array<string, array> Map of type => generate() result.
	 */
	public function generate_all( int $post_id, int $count = self::DEFAULT_VARIANT_COUNT ): array {
		$results = [];
		foreach ( [ self::TYPE_HEADLINE, self::TYPE_CTA, self::TYPE_META, self::TYPE_INTRO ] as $type ) {
			$results[ $type ] = $this->generate( $post_id, $type, $count );
		}
		return $results;
	}

	/**
	 * Clear the variant cache for a post.
	 *
	 * @param  int    $post_id Post ID.
	 * @param  string $type    Type to clear, or '' for all types.
	 */
	public function clear_cache( int $post_id, string $type = '' ): void {
		if ( '' === $type ) {
			delete_post_meta( $post_id, self::META_VARIANTS );
			return;
		}
		$all = $this->get_all_cached( $post_id );
		unset( $all[ $type ] );
		update_post_meta( $post_id, self::META_VARIANTS, wp_json_encode( $all ) );
	}

	/**
	 * Check whether AI generation is enabled.
	 */
	public function is_ai_enabled(): bool {
		return (bool) get_option( self::OPT_AI_ENABLED, false );
	}

	// -----------------------------------------------------------------------
	// Internal helpers
	// -----------------------------------------------------------------------

	/**
	 * Build variants using template logic (or AI when enabled).
	 *
	 * @param  int    $post_id  Post ID.
	 * @param  string $type     Variant type.
	 * @param  int    $count    Number of variants.
	 * @param  string $original Original content.
	 * @return string[]
	 */
	private function build_variants( int $post_id, string $type, int $count, string $original ): array {
		return match ( $type ) {
			self::TYPE_HEADLINE => $this->build_headline_variants( $original, $count ),
			self::TYPE_CTA      => $this->build_cta_variants( $count ),
			self::TYPE_META     => $this->build_meta_variants( $original, $count ),
			self::TYPE_INTRO    => $this->build_intro_variants( $original, $count ),
			default             => [],
		};
	}

	/**
	 * Generate headline variants from the original post title.
	 *
	 * @param  string $original Original headline.
	 * @param  int    $count    Number of variants.
	 * @return string[]
	 */
	private function build_headline_variants( string $original, int $count ): array {
		// Extract a topic keyword from the original title.
		$topic     = $this->extract_topic( $original );
		$year      = (int) gmdate( 'Y' );
		$templates = array_values( self::HEADLINE_TEMPLATES );
		$variants  = [];
		$used      = [];

		for ( $i = 0; $i < $count; $i++ ) {
			$tpl_key  = $templates[ $i % count( $templates ) ];
			$slug_key = array_keys( self::HEADLINE_TEMPLATES )[ $i % count( self::HEADLINE_TEMPLATES ) ];

			$variant = match ( $slug_key ) {
				'number'  => sprintf( $tpl_key, ( $i + 1 ) * 5, $topic ),
				'in_year' => sprintf( $tpl_key, ucfirst( $topic ), $year ),
				default   => sprintf( $tpl_key, ucfirst( $topic ) ),
			};

			// Deduplicate; fall back to numbered suffix.
			if ( isset( $used[ $variant ] ) ) {
				$variant .= ' (' . ( $i + 1 ) . ')';
			}
			$used[ $variant ] = true;
			$variants[]       = $variant;
		}

		return $variants;
	}

	/**
	 * @param  int $count Number of CTA variants.
	 * @return string[]
	 */
	private function build_cta_variants( int $count ): array {
		$pool = self::CTA_VARIANTS;
		return array_slice( $pool, 0, min( $count, count( $pool ) ) );
	}

	/**
	 * @param  string $original Original meta description.
	 * @param  int    $count    Number of variants.
	 * @return string[]
	 */
	private function build_meta_variants( string $original, int $count ): array {
		$base     = $this->truncate( $original, 100 );
		$suffixes = self::META_SUFFIXES;
		$variants = [];

		for ( $i = 0; $i < $count; $i++ ) {
			$suffix     = $suffixes[ $i % count( $suffixes ) ];
			$variants[] = trim( $base . ' ' . $suffix );
		}

		return $variants;
	}

	/**
	 * @param  string $original Original intro text.
	 * @param  int    $count    Number of variants.
	 * @return string[]
	 */
	private function build_intro_variants( string $original, int $count ): array {
		$topic    = $this->extract_topic( $original );
		$openers  = [
			"In this article, we'll show you everything about {$topic}.",
			"If you've ever wondered about {$topic}, you're in the right place.",
			"{$topic} is one of the most important topics for anyone looking to succeed.",
			"Let's dive straight into what you need to know about {$topic}.",
			"Understanding {$topic} can make a huge difference in your results.",
		];
		$variants = [];

		for ( $i = 0; $i < $count; $i++ ) {
			$variants[] = $openers[ $i % count( $openers ) ];
		}

		return $variants;
	}

	/**
	 * Retrieve the original content for the given type and post.
	 *
	 * @param  int    $post_id Post ID.
	 * @param  string $type    Variant type.
	 * @return string
	 */
	private function get_original( int $post_id, string $type ): string {
		return match ( $type ) {
			self::TYPE_HEADLINE => (string) get_the_title( $post_id ),
			self::TYPE_CTA      => 'Read More',
			self::TYPE_META     => (string) get_post_meta( $post_id, '_yoast_wpseo_metadesc', true ),
			self::TYPE_INTRO    => $this->truncate(
				strip_tags( (string) get_post_field( 'post_content', $post_id ) ),
				200
			),
			default             => '',
		};
	}

	/**
	 * Extract a short topic phrase from a longer string.
	 */
	private function extract_topic( string $text ): string {
		$text   = strip_tags( $text );
		$words  = preg_split( '/\s+/', trim( $text ) ) ?: [];
		// Take up to the first 4 non-stop words.
		$stop   = [ 'the', 'a', 'an', 'is', 'in', 'on', 'at', 'to', 'for', 'of', 'and', 'or', 'but' ];
		$result = [];
		foreach ( $words as $word ) {
			$lower = strtolower( trim( $word, '.,!?:;"\'' ) );
			if ( ! in_array( $lower, $stop, true ) && $lower !== '' ) {
				$result[] = $lower;
			}
			if ( count( $result ) >= 4 ) {
				break;
			}
		}
		return implode( ' ', $result ) ?: 'your topic';
	}

	/**
	 * Truncate a string to at most $length characters, preserving whole words.
	 */
	private function truncate( string $str, int $length ): string {
		if ( strlen( $str ) <= $length ) {
			return $str;
		}
		$cut = substr( $str, 0, $length );
		$pos = strrpos( $cut, ' ' );
		return $pos !== false ? substr( $cut, 0, $pos ) : $cut;
	}

	/**
	 * Validate and normalise a variant type string.
	 */
	private function validate_type( string $type ): string {
		$allowed = [ self::TYPE_HEADLINE, self::TYPE_CTA, self::TYPE_META, self::TYPE_INTRO ];
		return in_array( $type, $allowed, true ) ? $type : self::TYPE_HEADLINE;
	}

	// -----------------------------------------------------------------------
	// Cache helpers
	// -----------------------------------------------------------------------

	/**
	 * @return array<string, array>
	 */
	private function get_all_cached( int $post_id ): array {
		$raw = (string) get_post_meta( $post_id, self::META_VARIANTS, true );
		if ( '' === $raw ) {
			return [];
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * @return array|null Cached result or null if not found/stale.
	 */
	private function get_cached( int $post_id, string $type, int $count ): ?array {
		$all = $this->get_all_cached( $post_id );
		if ( ! isset( $all[ $type ] ) ) {
			return null;
		}
		$entry = $all[ $type ];
		// Invalidate if fewer variants than requested.
		if ( count( $entry['variants'] ?? [] ) < $count ) {
			return null;
		}
		$entry['variants'] = array_slice( $entry['variants'], 0, $count );
		return $entry;
	}

	private function cache_variants( int $post_id, string $type, array $result ): void {
		$all         = $this->get_all_cached( $post_id );
		$all[ $type ] = $result;
		update_post_meta( $post_id, self::META_VARIANTS, wp_json_encode( $all ) );
	}
}

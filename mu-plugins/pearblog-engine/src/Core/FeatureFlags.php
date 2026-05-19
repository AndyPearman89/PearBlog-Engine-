<?php
/**
 * Feature-flag system backed by WordPress options.
 *
 * Flags are stored as a single serialised array under the
 * option key `pearblog_feature_flags`.  Defaults are defined
 * in code so the table entry is only written when a flag is
 * overridden via the admin UI or WP-CLI.
 *
 * Usage:
 *   if ( FeatureFlags::enabled('specialists_marketplace') ) { … }
 *   FeatureFlags::enable('local_hubs');
 *   FeatureFlags::disable('ai_advisor');
 *
 * @package PearBlogEngine\Core
 */

declare(strict_types=1);

namespace PearBlogEngine\Core;

/**
 * Feature flags — runtime toggle for platform capabilities.
 */
class FeatureFlags {

	private const OPTION_KEY = 'pearblog_feature_flags';

	/**
	 * Default flag values.
	 *
	 * Add new platform flags here so they are discoverable.
	 *
	 * @var array<string, bool>
	 */
	private static array $defaults = [
		// Core Decision Platform
		'decision_platform'       => true,
		'comparison_engine'       => true,
		'ranking_engine'          => true,
		'calculator_engine'       => true,

		// V6 new modules
		'specialists_marketplace' => true,
		'local_hubs'              => true,
		'search_engine'           => true,
		'revenue_engine'          => true,
		'event_bus'               => true,

		// AI features
		'ai_advisor'              => true,
		'ai_summaries'            => true,
		'ai_lead_scoring'         => true,
		'ai_ranking_score'        => true,

		// Monetisation
		'sponsored_rankings'      => true,
		'saas_subscriptions'      => false,  // disabled until billing integrated
		'premium_listings'        => true,

		// SEO / content
		'programmatic_local_seo'  => true,
		'auto_faq_generation'     => true,
		'internal_linking'        => true,

		// Experimental
		'vector_search'           => false,
		'streaming_ai'            => false,
	];

	/** @var array<string, bool>|null Merged defaults + stored overrides. */
	private static ?array $flags = null;

	// -----------------------------------------------------------------------
	// Read
	// -----------------------------------------------------------------------

	/**
	 * Check if a flag is enabled.
	 *
	 * @param string $flag Flag identifier (snake_case).
	 * @return bool
	 */
	public static function enabled( string $flag ): bool {
		return self::all()[ $flag ] ?? false;
	}

	/**
	 * Inverse of enabled().
	 *
	 * @param string $flag
	 * @return bool
	 */
	public static function disabled( string $flag ): bool {
		return ! self::enabled( $flag );
	}

	/**
	 * Return all flags (defaults merged with stored overrides).
	 *
	 * @return array<string, bool>
	 */
	public static function all(): array {
		if ( null === self::$flags ) {
			$stored     = get_option( self::OPTION_KEY, [] );
			$stored     = is_array( $stored ) ? $stored : [];
			self::$flags = array_merge( self::$defaults, $stored );
		}
		return self::$flags;
	}

	// -----------------------------------------------------------------------
	// Write
	// -----------------------------------------------------------------------

	/**
	 * Enable a flag (persisted to DB).
	 *
	 * @param string $flag
	 */
	public static function enable( string $flag ): void {
		self::set( $flag, true );
	}

	/**
	 * Disable a flag (persisted to DB).
	 *
	 * @param string $flag
	 */
	public static function disable( string $flag ): void {
		self::set( $flag, false );
	}

	/**
	 * Set a flag value and persist to DB.
	 *
	 * @param string $flag
	 * @param bool   $value
	 */
	public static function set( string $flag, bool $value ): void {
		$stored          = get_option( self::OPTION_KEY, [] );
		$stored          = is_array( $stored ) ? $stored : [];
		$stored[ $flag ] = $value;
		update_option( self::OPTION_KEY, $stored, false );

		// Invalidate cache.
		self::$flags = null;
	}

	/**
	 * Reset a single flag back to its default.
	 *
	 * @param string $flag
	 */
	public static function reset( string $flag ): void {
		$stored = get_option( self::OPTION_KEY, [] );
		$stored = is_array( $stored ) ? $stored : [];
		unset( $stored[ $flag ] );
		update_option( self::OPTION_KEY, $stored, false );
		self::$flags = null;
	}

	/**
	 * Reset all flags to defaults.
	 */
	public static function reset_all(): void {
		delete_option( self::OPTION_KEY );
		self::$flags = null;
	}

	// -----------------------------------------------------------------------
	// Introspection
	// -----------------------------------------------------------------------

	/**
	 * Return flags that differ from defaults (overridden by admin/CLI).
	 *
	 * @return array<string, bool>
	 */
	public static function overrides(): array {
		$stored = get_option( self::OPTION_KEY, [] );
		return is_array( $stored ) ? $stored : [];
	}

	/**
	 * Return the default value for a flag.
	 *
	 * @param string $flag
	 * @return bool|null  null if flag is unknown.
	 */
	public static function default_value( string $flag ): ?bool {
		return self::$defaults[ $flag ] ?? null;
	}
}

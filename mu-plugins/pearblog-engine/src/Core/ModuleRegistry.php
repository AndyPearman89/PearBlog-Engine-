<?php
/**
 * Module registry — tracks every sub-system that has been booted.
 *
 * Modules register themselves so the admin dashboard, CLI,
 * and health-check endpoints can report platform status.
 *
 * @package PearBlogEngine\Core
 */

declare(strict_types=1);

namespace PearBlogEngine\Core;

/**
 * Immutable value object that describes one registered module.
 */
final class ModuleDescriptor {

	public function __construct(
		public readonly string $id,
		public readonly string $name,
		public readonly string $version,
		public readonly string $namespace,
		public readonly bool   $enabled = true
	) {}
}

/**
 * Central registry of all booted modules.
 *
 * Modules call ModuleRegistry::register() at boot time.
 * Health-check and admin pages read from ModuleRegistry::all().
 */
class ModuleRegistry {

	/** @var array<string, ModuleDescriptor> Indexed by module id. */
	private static array $modules = [];

	/**
	 * Register a module descriptor.
	 *
	 * @param ModuleDescriptor $descriptor
	 */
	public static function register( ModuleDescriptor $descriptor ): void {
		self::$modules[ $descriptor->id ] = $descriptor;
	}

	/**
	 * Register a module using raw values (convenience wrapper).
	 *
	 * @param string $id        Unique snake_case identifier, e.g. 'rankings'.
	 * @param string $name      Human-readable name.
	 * @param string $version   SemVer string.
	 * @param string $namespace PHP namespace root for the module.
	 * @param bool   $enabled
	 */
	public static function add(
		string $id,
		string $name,
		string $version,
		string $namespace,
		bool $enabled = true
	): void {
		self::register( new ModuleDescriptor( $id, $name, $version, $namespace, $enabled ) );
	}

	/**
	 * Return all registered modules.
	 *
	 * @return array<string, ModuleDescriptor>
	 */
	public static function all(): array {
		return self::$modules;
	}

	/**
	 * Return only enabled modules.
	 *
	 * @return array<string, ModuleDescriptor>
	 */
	public static function enabled(): array {
		return array_filter( self::$modules, fn( $m ) => $m->enabled );
	}

	/**
	 * Check if a module is registered and enabled.
	 *
	 * @param string $id
	 * @return bool
	 */
	public static function is_enabled( string $id ): bool {
		return isset( self::$modules[ $id ] ) && self::$modules[ $id ]->enabled;
	}

	/**
	 * Return a single module descriptor by id, or null.
	 *
	 * @param string $id
	 * @return ModuleDescriptor|null
	 */
	public static function get( string $id ): ?ModuleDescriptor {
		return self::$modules[ $id ] ?? null;
	}

	/**
	 * Build a status array suitable for JSON export (health check / API).
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function status(): array {
		$out = [];
		foreach ( self::$modules as $m ) {
			$out[] = [
				'id'        => $m->id,
				'name'      => $m->name,
				'version'   => $m->version,
				'namespace' => $m->namespace,
				'enabled'   => $m->enabled,
			];
		}
		return $out;
	}
}

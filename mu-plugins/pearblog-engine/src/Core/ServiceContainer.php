<?php
/**
 * Lightweight PSR-11-compatible service container.
 *
 * Supports:
 *  - bind()        — register a factory or concrete class name
 *  - singleton()   — register a shared instance (lazy-initialised)
 *  - make()        — resolve with automatic constructor injection
 *  - instance()    — register an already-built object
 *
 * @package PearBlogEngine\Core
 */

declare(strict_types=1);

namespace PearBlogEngine\Core;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * ServiceContainer
 *
 * Resolves class dependencies automatically via reflection.
 * Integrates with the existing Plugin::boot() call chain.
 */
class ServiceContainer {

	/** @var array<string, Closure|string> Factory registrations. */
	private array $bindings = [];

	/** @var array<string, object> Cached singleton instances. */
	private array $singletons = [];

	/** @var array<string, bool> Tracks which bindings are singletons. */
	private array $singleton_flags = [];

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Bind a concrete implementation or factory closure to an abstract id.
	 *
	 * @param string          $abstract Abstract identifier (interface or class name).
	 * @param Closure|string  $concrete Factory closure or FQCN.
	 */
	public function bind( string $abstract, Closure|string $concrete ): void {
		$this->bindings[ $abstract ] = $concrete;
	}

	/**
	 * Bind a singleton (shared, lazy-initialised).
	 *
	 * @param string          $abstract
	 * @param Closure|string  $concrete
	 */
	public function singleton( string $abstract, Closure|string $concrete ): void {
		$this->bindings[ $abstract ]        = $concrete;
		$this->singleton_flags[ $abstract ] = true;
	}

	/**
	 * Register an already-constructed object as a singleton.
	 *
	 * @param string $abstract
	 * @param object $instance
	 */
	public function instance( string $abstract, object $instance ): void {
		$this->singletons[ $abstract ] = $instance;
	}

	// -----------------------------------------------------------------------
	// Resolution
	// -----------------------------------------------------------------------

	/**
	 * Resolve an abstract into a concrete instance.
	 *
	 * @template T of object
	 * @param  class-string<T>|string $abstract
	 * @return T&object
	 *
	 * @throws RuntimeException If the class cannot be resolved.
	 */
	public function make( string $abstract ): object {
		// Return cached singleton.
		if ( isset( $this->singletons[ $abstract ] ) ) {
			return $this->singletons[ $abstract ]; // @phpstan-ignore-line
		}

		$concrete = $this->bindings[ $abstract ] ?? $abstract;

		// Build instance.
		$instance = $concrete instanceof Closure
			? $concrete( $this )
			: $this->build( $concrete );

		// Cache if singleton.
		if ( isset( $this->singleton_flags[ $abstract ] ) ) {
			$this->singletons[ $abstract ] = $instance;
		}

		return $instance; // @phpstan-ignore-line
	}

	/**
	 * Alias for make() — syntactic sugar.
	 *
	 * @param string $abstract
	 * @return object
	 */
	public function get( string $abstract ): object {
		return $this->make( $abstract );
	}

	/**
	 * Check if an abstract is registered.
	 *
	 * @param string $abstract
	 * @return bool
	 */
	public function has( string $abstract ): bool {
		return isset( $this->bindings[ $abstract ] ) || isset( $this->singletons[ $abstract ] );
	}

	// -----------------------------------------------------------------------
	// Auto-wiring
	// -----------------------------------------------------------------------

	/**
	 * Build a class, auto-wiring its constructor dependencies.
	 *
	 * @param string $concrete FQCN.
	 * @return object
	 *
	 * @throws RuntimeException
	 */
	private function build( string $concrete ): object {
		if ( ! class_exists( $concrete ) ) {
			throw new RuntimeException( "ServiceContainer: class [{$concrete}] does not exist." );
		}

		$reflector = new ReflectionClass( $concrete );

		if ( ! $reflector->isInstantiable() ) {
			throw new RuntimeException( "ServiceContainer: [{$concrete}] is not instantiable." );
		}

		$constructor = $reflector->getConstructor();
		if ( null === $constructor ) {
			return $reflector->newInstanceWithoutConstructor();
		}

		$dependencies = [];
		foreach ( $constructor->getParameters() as $param ) {
			$type = $param->getType();

			if ( $type instanceof ReflectionNamedType && ! $type->isBuiltin() ) {
				// Recursively resolve typed dependencies.
				try {
					$dependencies[] = $this->make( $type->getName() );
				} catch ( RuntimeException $e ) {
					// If optional, use default value.
					if ( $param->isOptional() ) {
						$dependencies[] = $param->getDefaultValue();
					} else {
						throw $e;
					}
				}
			} elseif ( $param->isOptional() ) {
				$dependencies[] = $param->getDefaultValue();
			} else {
				throw new RuntimeException(
					"ServiceContainer: cannot resolve primitive [{$param->getName()}] in [{$concrete}]."
				);
			}
		}

		return $reflector->newInstanceArgs( $dependencies );
	}
}

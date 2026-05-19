<?php
/**
 * Typed event bus built on top of WordPress action hooks.
 *
 * Adds a thin domain-event layer so modules can communicate without
 * coupling to concrete class names or raw hook strings.
 *
 * Usage:
 *   EventBus::dispatch(new RankingUpdatedEvent($ranking_id));
 *   EventBus::listen(RankingUpdatedEvent::class, fn($e) => …);
 *
 * @package PearBlogEngine\Core
 */

declare(strict_types=1);

namespace PearBlogEngine\Core;

/**
 * Base class for all domain events.
 *
 * Every event carries the UTC timestamp at which it was created.
 */
abstract class DomainEvent {

	/** @var int Unix timestamp (UTC). */
	public readonly int $occurred_at;

	public function __construct() {
		$this->occurred_at = time();
	}
}

/**
 * Typed event dispatcher / listener registry.
 */
class EventBus {

	/** @var array<string, list<callable>> */
	private static array $listeners = [];

	/**
	 * Register a listener for a specific event class.
	 *
	 * @param class-string<DomainEvent> $event_class
	 * @param callable                  $listener    Receives one argument: the event object.
	 * @param int                       $priority    WordPress action priority (default 10).
	 */
	public static function listen( string $event_class, callable $listener, int $priority = 10 ): void {
		$hook = self::hook_name( $event_class );
		self::$listeners[ $event_class ][] = $listener;
		add_action( $hook, $listener, $priority );
	}

	/**
	 * Dispatch a domain event to all registered listeners.
	 *
	 * @param DomainEvent $event
	 */
	public static function dispatch( DomainEvent $event ): void {
		$hook = self::hook_name( get_class( $event ) );
		do_action( $hook, $event );
	}

	/**
	 * Remove all listeners for an event class (useful in tests).
	 *
	 * @param class-string<DomainEvent> $event_class
	 */
	public static function forget( string $event_class ): void {
		$hook = self::hook_name( $event_class );
		foreach ( self::$listeners[ $event_class ] ?? [] as $listener ) {
			remove_action( $hook, $listener );
		}
		unset( self::$listeners[ $event_class ] );
	}

	/**
	 * Convert a FQCN to a deterministic WP hook name.
	 *
	 * Example: PearBlogEngine\Rankings\RankingUpdatedEvent
	 *       -> pearblog_event_rankings_rankingupdatedevent
	 *
	 * @param string $event_class
	 * @return string
	 */
	private static function hook_name( string $event_class ): string {
		$parts = explode( '\\', strtolower( $event_class ) );
		// Strip common prefix to keep hooks short.
		$parts = array_filter( $parts, fn( $p ) => $p !== 'pearblogengine' );
		return 'pearblog_event_' . implode( '_', array_values( $parts ) );
	}
}

// ---------------------------------------------------------------------------
// Built-in platform events – register here for discoverability.
// ---------------------------------------------------------------------------

/** Fired when a ranking entry is created or its score recalculated. */
final class RankingUpdatedEvent extends DomainEvent {
	public function __construct( public readonly int $ranking_id, public readonly float $new_score ) {
		parent::__construct();
	}
}

/** Fired when a specialist profile is verified. */
final class SpecialistVerifiedEvent extends DomainEvent {
	public function __construct( public readonly int $specialist_id, public readonly string $level ) {
		parent::__construct();
	}
}

/** Fired when a new lead is submitted. */
final class LeadSubmittedEvent extends DomainEvent {
	public function __construct(
		public readonly int    $lead_id,
		public readonly string $category,
		public readonly string $city,
		public readonly int    $score
	) {
		parent::__construct();
	}
}

/** Fired after a specialist review is published. */
final class ReviewPublishedEvent extends DomainEvent {
	public function __construct(
		public readonly int   $review_id,
		public readonly int   $specialist_id,
		public readonly float $rating
	) {
		parent::__construct();
	}
}

/** Fired when a local hub page is generated programmatically. */
final class LocalHubGeneratedEvent extends DomainEvent {
	public function __construct(
		public readonly string $hub_slug,
		public readonly string $city,
		public readonly string $vertical
	) {
		parent::__construct();
	}
}

/** Fired when a sponsored placement is activated or renewed. */
final class SponsorActivatedEvent extends DomainEvent {
	public function __construct(
		public readonly int    $specialist_id,
		public readonly string $placement_type,
		public readonly int    $expires_at
	) {
		parent::__construct();
	}
}

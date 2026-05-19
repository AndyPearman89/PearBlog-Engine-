<?php
/**
 * Sponsor Engine
 *
 * Manages sponsored placements within ranking lists.
 * Sponsored slots are always clearly labelled for transparency.
 *
 * Revenue tiers:
 *   - gold_sponsor    → pinned to position 1 + badge
 *   - silver_sponsor  → positions 2–3 + badge
 *   - featured        → highlighted card, no position override
 *
 * @package PearBlogEngine\Rankings
 */

declare(strict_types=1);

namespace PearBlogEngine\Rankings;

use PearBlogEngine\Core\EventBus;
use PearBlogEngine\Core\SponsorActivatedEvent;

/**
 * Manages sponsor records stored as WP options per category/city.
 *
 * Data storage key pattern:
 *   pearblog_sponsors_{category}_{city}   → serialised SponsorSlot[]
 *   pearblog_sponsor_{specialist_id}      → active SponsorSlot for global
 */
class SponsorEngine {

	private const OPTION_PREFIX = 'pearblog_sponsors_';

	// -----------------------------------------------------------------------
	// Slot management
	// -----------------------------------------------------------------------

	/**
	 * Activate a sponsored slot for a specialist.
	 *
	 * @param int    $specialist_id
	 * @param string $tier          'gold_sponsor' | 'silver_sponsor' | 'featured'
	 * @param string $category      Vertical / category slug.
	 * @param string $city          City slug (empty = all cities).
	 * @param int    $days          Duration in days.
	 * @return bool
	 */
	public function activate(
		int    $specialist_id,
		string $tier,
		string $category,
		string $city = '',
		int    $days = 30
	): bool {
		if ( ! in_array( $tier, [ 'gold_sponsor', 'silver_sponsor', 'featured' ], true ) ) {
			return false;
		}

		$expires_at = time() + ( $days * DAY_IN_SECONDS );
		$key        = $this->option_key( $category, $city );
		$slots      = $this->get_slots( $category, $city );

		// Remove any existing slot for this specialist in this scope.
		$slots = array_filter( $slots, fn( $s ) => $s['specialist_id'] !== $specialist_id );

		$slots[] = [
			'specialist_id' => $specialist_id,
			'tier'          => $tier,
			'expires_at'    => $expires_at,
			'activated_at'  => time(),
		];

		update_option( $key, array_values( $slots ), false );

		EventBus::dispatch( new SponsorActivatedEvent( $specialist_id, $tier, $expires_at ) );

		return true;
	}

	/**
	 * Deactivate a sponsored slot.
	 *
	 * @param int    $specialist_id
	 * @param string $category
	 * @param string $city
	 * @return bool
	 */
	public function deactivate( int $specialist_id, string $category, string $city = '' ): bool {
		$key   = $this->option_key( $category, $city );
		$slots = $this->get_slots( $category, $city );
		$count = count( $slots );
		$slots = array_values( array_filter( $slots, fn( $s ) => $s['specialist_id'] !== $specialist_id ) );
		update_option( $key, $slots, false );
		return count( $slots ) < $count;
	}

	// -----------------------------------------------------------------------
	// Ranking injection
	// -----------------------------------------------------------------------

	/**
	 * Apply sponsor order to a flat ranking list.
	 *
	 * Gold sponsors move to position 0, silver to positions 1–2.
	 * All others keep their natural score order.
	 *
	 * @param array<array<string, mixed>> $entries    Ranking items (must have 'specialist_id' key).
	 * @param string                      $category
	 * @param string                      $city
	 * @return array<array<string, mixed>> Re-ordered list with sponsor metadata injected.
	 */
	public function apply( array $entries, string $category, string $city = '' ): array {
		$slots  = $this->get_active_slots( $category, $city );
		$by_id  = [];
		foreach ( $slots as $slot ) {
			$by_id[ $slot['specialist_id'] ] = $slot;
		}

		if ( empty( $by_id ) ) {
			return $entries;
		}

		// Annotate entries with sponsor data.
		$gold    = [];
		$silver  = [];
		$featured = [];
		$organic = [];

		foreach ( $entries as $entry ) {
			$sid  = $entry['specialist_id'] ?? 0;
			$slot = $by_id[ $sid ] ?? null;

			if ( $slot ) {
				$entry['is_sponsored']  = true;
				$entry['sponsor_tier']  = $slot['tier'];
				$entry['sponsor_label'] = 'Wyróżniony';

				match ( $slot['tier'] ) {
					'gold_sponsor'   => $gold[]    = $entry,
					'silver_sponsor' => $silver[]  = $entry,
					default          => $featured[] = $entry,
				};
			} else {
				$entry['is_sponsored'] = false;
				$organic[] = $entry;
			}
		}

		return array_merge( $gold, $silver, $featured, $organic );
	}

	// -----------------------------------------------------------------------
	// Slot retrieval
	// -----------------------------------------------------------------------

	/**
	 * Get all slots (including expired) for a scope.
	 *
	 * @param string $category
	 * @param string $city
	 * @return array<array<string, mixed>>
	 */
	public function get_slots( string $category, string $city = '' ): array {
		$raw = get_option( $this->option_key( $category, $city ), [] );
		return is_array( $raw ) ? $raw : [];
	}

	/**
	 * Get only non-expired slots.
	 *
	 * @param string $category
	 * @param string $city
	 * @return array<array<string, mixed>>
	 */
	public function get_active_slots( string $category, string $city = '' ): array {
		$now   = time();
		$slots = $this->get_slots( $category, $city );
		return array_values( array_filter( $slots, fn( $s ) => ( $s['expires_at'] ?? 0 ) > $now ) );
	}

	/**
	 * Purge expired slots from all categories (cron task).
	 *
	 * @return int Number of slots removed.
	 */
	public function purge_expired(): int {
		global $wpdb;
		$prefix  = self::OPTION_PREFIX;
		$now     = time();
		$removed = 0;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( $prefix ) . '%'
			)
		);

		foreach ( $rows as $row ) {
			$slots   = maybe_unserialize( $row->option_value );
			$slots   = is_array( $slots ) ? $slots : [];
			$cleaned = array_values( array_filter( $slots, fn( $s ) => ( $s['expires_at'] ?? 0 ) > $now ) );
			$removed += count( $slots ) - count( $cleaned );
			update_option( $row->option_name, $cleaned, false );
		}

		return $removed;
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function option_key( string $category, string $city ): string {
		$key = self::OPTION_PREFIX . sanitize_key( $category );
		if ( $city !== '' ) {
			$key .= '_' . sanitize_key( $city );
		}
		return $key;
	}
}

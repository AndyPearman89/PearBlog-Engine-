<?php
/**
 * Badge Engine
 *
 * Rule-based badge assignment for specialist profiles.
 *
 * Badges are criteria-driven rewards that increase trust and ranking score.
 *
 * @package PearBlogEngine\Specialists
 */

declare(strict_types=1);

namespace PearBlogEngine\Specialists;

/**
 * BadgeEngine
 *
 * Evaluates badge criteria against specialist metrics and persists
 * earned badges to the pearblog_specialist_badges table.
 */
class BadgeEngine {

	private \wpdb $wpdb;
	private string $table;

	/**
	 * Badge definitions.
	 *
	 * Each entry: id → [label, description, icon, criteria]
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static array $catalog = [
		'top_rated' => [
			'label'       => 'Top Rated',
			'description' => 'Ocena ≥ 4.8 przy ≥ 10 recenzjach',
			'icon'        => '⭐',
			'criteria'    => [ 'avg_rating_min' => 4.8, 'review_count_min' => 10 ],
		],
		'fast_response' => [
			'label'       => 'Szybka odpowiedź',
			'description' => 'Odpowiada w < 1 godzinę',
			'icon'        => '⚡',
			'criteria'    => [ 'response_rate_min' => 90.0 ],
		],
		'verified_pro' => [
			'label'       => 'Zweryfikowany Pro',
			'description' => 'Weryfikacja silver lub gold',
			'icon'        => '✅',
			'criteria'    => [ 'verification_levels' => [ 'silver', 'gold' ] ],
		],
		'experience_5y' => [
			'label'       => '5+ lat doświadczenia',
			'description' => 'Profil istnieje ponad 5 lat',
			'icon'        => '🏆',
			'criteria'    => [ 'account_age_days_min' => 1825 ],
		],
		'popular' => [
			'label'       => 'Popularny',
			'description' => 'Ponad 50 recenzji',
			'icon'        => '🔥',
			'criteria'    => [ 'review_count_min' => 50 ],
		],
		'premium_member' => [
			'label'       => 'Premium',
			'description' => 'Subskrypcja premium aktywna',
			'icon'        => '💎',
			'criteria'    => [ 'is_premium' => true ],
		],
	];

	public function __construct() {
		global $wpdb;
		$this->wpdb  = $wpdb;
		$this->table = $wpdb->prefix . 'pearblog_specialist_badges';
	}

	// -----------------------------------------------------------------------
	// Evaluation
	// -----------------------------------------------------------------------

	/**
	 * Evaluate all badge criteria for a specialist and award any new badges.
	 *
	 * @param int                  $specialist_id  DB row ID in pearblog_specialists.
	 * @param array<string, mixed> $metrics        Pre-loaded metrics for the specialist.
	 * @return list<string>  Newly awarded badge IDs.
	 */
	public function evaluate( int $specialist_id, array $metrics ): array {
		$existing = array_column( $this->get_badges( $specialist_id ), 'badge_id' );
		$awarded  = [];

		foreach ( self::$catalog as $badge_id => $badge ) {
			if ( in_array( $badge_id, $existing, true ) ) {
				continue; // Already has this badge.
			}
			if ( $this->meets_criteria( $metrics, $badge['criteria'] ) ) {
				$this->award( $specialist_id, $badge_id );
				$awarded[] = $badge_id;
			}
		}

		return $awarded;
	}

	/**
	 * Check if a single badge's criteria are met by the given metrics.
	 *
	 * @param array<string, mixed> $metrics
	 * @param array<string, mixed> $criteria
	 * @return bool
	 */
	private function meets_criteria( array $metrics, array $criteria ): bool {
		foreach ( $criteria as $rule => $value ) {
			switch ( $rule ) {
				case 'avg_rating_min':
					if ( (float) ( $metrics['avg_rating'] ?? 0 ) < (float) $value ) return false;
					break;
				case 'review_count_min':
					if ( (int) ( $metrics['review_count'] ?? 0 ) < (int) $value ) return false;
					break;
				case 'response_rate_min':
					if ( (float) ( $metrics['response_rate'] ?? 0 ) < (float) $value ) return false;
					break;
				case 'verification_levels':
					if ( ! in_array( $metrics['verification_level'] ?? 'none', (array) $value, true ) ) return false;
					break;
				case 'account_age_days_min':
					$days = (int) ( $metrics['account_age_days'] ?? 0 );
					if ( $days < (int) $value ) return false;
					break;
				case 'is_premium':
					if ( (bool) ( $metrics['is_premium'] ?? false ) !== (bool) $value ) return false;
					break;
			}
		}
		return true;
	}

	// -----------------------------------------------------------------------
	// Persistence
	// -----------------------------------------------------------------------

	/**
	 * Award a badge to a specialist.
	 *
	 * @param int    $specialist_id
	 * @param string $badge_id
	 * @param string|null $reason
	 * @return bool
	 */
	public function award( int $specialist_id, string $badge_id, ?string $reason = null ): bool {
		if ( ! isset( self::$catalog[ $badge_id ] ) ) {
			return false;
		}

		return $this->wpdb->insert(
			$this->table,
			[
				'specialist_id' => $specialist_id,
				'badge_id'      => $badge_id,
				'awarded_by'    => get_current_user_id() ?: null,
				'reason'        => $reason,
			],
			[ '%d', '%s', '%d', '%s' ]
		) !== false;
	}

	/**
	 * Revoke a badge.
	 *
	 * @param int    $specialist_id
	 * @param string $badge_id
	 * @return bool
	 */
	public function revoke( int $specialist_id, string $badge_id ): bool {
		return $this->wpdb->delete(
			$this->table,
			[ 'specialist_id' => $specialist_id, 'badge_id' => $badge_id ],
			[ '%d', '%s' ]
		) !== false;
	}

	/**
	 * Get all badges for a specialist.
	 *
	 * @param int $specialist_id
	 * @return array<array<string, mixed>>
	 */
	public function get_badges( int $specialist_id ): array {
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT badge_id, awarded_at, expires_at FROM {$this->table}
				 WHERE specialist_id = %d
				   AND (expires_at IS NULL OR expires_at > NOW())
				 ORDER BY awarded_at DESC",
				$specialist_id
			),
			ARRAY_A
		) ?: [];

		// Enrich with catalog metadata.
		return array_map( function ( $row ) {
			$meta        = self::$catalog[ $row['badge_id'] ] ?? [];
			$row['label']       = $meta['label'] ?? $row['badge_id'];
			$row['description'] = $meta['description'] ?? '';
			$row['icon']        = $meta['icon'] ?? '🏅';
			return $row;
		}, $rows );
	}

	/**
	 * Return the full badge catalog.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function catalog(): array {
		return self::$catalog;
	}
}

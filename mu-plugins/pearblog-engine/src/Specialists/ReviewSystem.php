<?php
/**
 * Review System
 *
 * Manages specialist reviews: submission, moderation, aggregation,
 * and anti-fraud checks.
 *
 * @package PearBlogEngine\Specialists
 */

declare(strict_types=1);

namespace PearBlogEngine\Specialists;

use PearBlogEngine\Core\EventBus;
use PearBlogEngine\Core\ReviewPublishedEvent;

/**
 * ReviewSystem
 *
 * Operates on the pearblog_reviews table.
 */
class ReviewSystem {

	private \wpdb $wpdb;
	private string $table;
	private SpecialistProfile $profiles;

	public function __construct( ?SpecialistProfile $profiles = null ) {
		global $wpdb;
		$this->wpdb     = $wpdb;
		$this->table    = $wpdb->prefix . 'pearblog_reviews';
		$this->profiles = $profiles ?? new SpecialistProfile();
	}

	// -----------------------------------------------------------------------
	// Submission
	// -----------------------------------------------------------------------

	/**
	 * Submit a new review (pending moderation).
	 *
	 * @param array{
	 *   specialist_id: int,
	 *   author_name:   string,
	 *   author_email:  string,
	 *   rating:        int,
	 *   title:         string,
	 *   body:          string,
	 *   source?:       string,
	 * } $data
	 *
	 * @return int|null  Review ID or null on failure.
	 */
	public function submit( array $data ): ?int {
		$specialist_id = (int) ( $data['specialist_id'] ?? 0 );
		if ( $specialist_id <= 0 ) {
			return null;
		}

		// Anti-fraud: hash IP so we never store PII.
		$ip_hash = hash( 'sha256', $_SERVER['REMOTE_ADDR'] ?? '' );

		// Rate-limit: max 1 review per specialist per IP per 24 h.
		if ( $this->has_recent_review( $specialist_id, $ip_hash ) ) {
			return null;
		}

		$rating = max( 1, min( 5, (int) ( $data['rating'] ?? 5 ) ) );

		$result = $this->wpdb->insert(
			$this->table,
			[
				'specialist_id' => $specialist_id,
				'author_name'   => sanitize_text_field( $data['author_name'] ?? '' ),
				'author_email'  => sanitize_email( $data['author_email'] ?? '' ),
				'rating'        => $rating,
				'title'         => sanitize_text_field( $data['title'] ?? '' ),
				'body'          => sanitize_textarea_field( $data['body'] ?? '' ),
				'is_verified'   => 0,
				'is_published'  => 0, // pending moderation
				'source'        => sanitize_key( $data['source'] ?? 'platform' ),
				'ip_hash'       => $ip_hash,
			],
			[ '%d', '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%s', '%s' ]
		);

		return ( $result !== false ) ? (int) $this->wpdb->insert_id : null;
	}

	// -----------------------------------------------------------------------
	// Moderation
	// -----------------------------------------------------------------------

	/**
	 * Approve and publish a review.
	 *
	 * Fires ReviewPublishedEvent and refreshes specialist stats.
	 *
	 * @param int $review_id
	 * @return bool
	 */
	public function approve( int $review_id ): bool {
		$review = $this->find( $review_id );
		if ( ! $review ) {
			return false;
		}

		$ok = $this->wpdb->update(
			$this->table,
			[ 'is_published' => 1 ],
			[ 'id' => $review_id ],
			[ '%d' ],
			[ '%d' ]
		) !== false;

		if ( $ok ) {
			$specialist_id = (int) $review['specialist_id'];
			$this->profiles->refresh_review_stats( $specialist_id );
			EventBus::dispatch( new ReviewPublishedEvent( $review_id, $specialist_id, (float) $review['rating'] ) );
		}

		return $ok;
	}

	/**
	 * Reject and delete a review.
	 *
	 * @param int $review_id
	 * @return bool
	 */
	public function reject( int $review_id ): bool {
		return $this->wpdb->delete( $this->table, [ 'id' => $review_id ], [ '%d' ] ) !== false;
	}

	// -----------------------------------------------------------------------
	// Read
	// -----------------------------------------------------------------------

	/**
	 * Get published reviews for a specialist.
	 *
	 * @param int $specialist_id
	 * @param int $limit
	 * @param int $offset
	 * @return array<array<string, mixed>>
	 */
	public function for_specialist( int $specialist_id, int $limit = 20, int $offset = 0 ): array {
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT id, author_name, rating, title, body, source, is_verified, created_at
				 FROM {$this->table}
				 WHERE specialist_id = %d AND is_published = 1
				 ORDER BY created_at DESC
				 LIMIT %d OFFSET %d",
				$specialist_id,
				$limit,
				$offset
			),
			ARRAY_A
		) ?: [];
	}

	/**
	 * Get a single review by ID.
	 *
	 * @param int $review_id
	 * @return array<string, mixed>|null
	 */
	public function find( int $review_id ): ?array {
		return $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $review_id ),
			ARRAY_A
		) ?: null;
	}

	/**
	 * Get all pending (unmoderated) reviews.
	 *
	 * @return array<array<string, mixed>>
	 */
	public function pending(): array {
		return $this->wpdb->get_results(
			"SELECT r.*, s.name as specialist_name FROM {$this->table} r
			 LEFT JOIN {$this->wpdb->prefix}pearblog_specialists s ON s.id = r.specialist_id
			 WHERE r.is_published = 0
			 ORDER BY r.created_at ASC
			 LIMIT 100",
			ARRAY_A
		) ?: [];
	}

	// -----------------------------------------------------------------------
	// Anti-fraud
	// -----------------------------------------------------------------------

	/**
	 * Check if an IP hash has submitted a review for this specialist in the last 24 h.
	 *
	 * @param int    $specialist_id
	 * @param string $ip_hash
	 * @return bool
	 */
	private function has_recent_review( int $specialist_id, string $ip_hash ): bool {
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table}
				 WHERE specialist_id = %d AND ip_hash = %s
				   AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)",
				$specialist_id,
				$ip_hash
			)
		) > 0;
	}
}

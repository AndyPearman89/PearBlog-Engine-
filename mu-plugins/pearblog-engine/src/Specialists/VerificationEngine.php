<?php
/**
 * Verification Engine
 *
 * Manages credential verification for specialist profiles.
 *
 * Verification levels:
 *   none   — unverified (default)
 *   bronze — email + phone confirmed
 *   silver — identity document submitted and reviewed
 *   gold   — full background check + professional credentials
 *
 * @package PearBlogEngine\Specialists
 */

declare(strict_types=1);

namespace PearBlogEngine\Specialists;

use PearBlogEngine\Core\EventBus;
use PearBlogEngine\Core\SpecialistVerifiedEvent;

/**
 * VerificationEngine
 *
 * Processes verification requests stored in WP options (simple state machine).
 * In production this would integrate with a third-party KYC/AML provider.
 */
class VerificationEngine {

	private \wpdb $wpdb;
	private string $specialists_table;
	private const STATUS_OPTION = 'pearblog_verification_queue';

	public function __construct() {
		global $wpdb;
		$this->wpdb              = $wpdb;
		$this->specialists_table = $wpdb->prefix . 'pearblog_specialists';
	}

	// -----------------------------------------------------------------------
	// Request lifecycle
	// -----------------------------------------------------------------------

	/**
	 * Submit a verification request for a specialist.
	 *
	 * @param int    $specialist_id
	 * @param string $target_level  'bronze'|'silver'|'gold'
	 * @param array<string, mixed> $evidence  Submitted documents / references.
	 * @return bool
	 */
	public function request( int $specialist_id, string $target_level, array $evidence = [] ): bool {
		if ( ! in_array( $target_level, [ 'bronze', 'silver', 'gold' ], true ) ) {
			return false;
		}

		$queue = $this->get_queue();
		$queue[ $specialist_id ] = [
			'specialist_id' => $specialist_id,
			'target_level'  => $target_level,
			'status'        => 'pending',
			'submitted_at'  => time(),
			'evidence_keys' => array_keys( $evidence ), // never store actual docs
		];

		return update_option( self::STATUS_OPTION, $queue, false );
	}

	/**
	 * Approve a verification request and promote the specialist's level.
	 *
	 * @param int    $specialist_id
	 * @param string $approved_level  Level to set (may differ from requested).
	 * @return bool
	 */
	public function approve( int $specialist_id, string $approved_level ): bool {
		if ( ! in_array( $approved_level, [ 'bronze', 'silver', 'gold' ], true ) ) {
			return false;
		}

		$ok = $this->wpdb->update(
			$this->specialists_table,
			[
				'verification_level' => $approved_level,
				'verification_at'    => current_time( 'mysql', true ),
			],
			[ 'id' => $specialist_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		) !== false;

		if ( $ok ) {
			$this->remove_from_queue( $specialist_id );
			EventBus::dispatch( new SpecialistVerifiedEvent( $specialist_id, $approved_level ) );
		}

		return $ok;
	}

	/**
	 * Reject a verification request.
	 *
	 * @param int    $specialist_id
	 * @param string $reason
	 * @return bool
	 */
	public function reject( int $specialist_id, string $reason = '' ): bool {
		$queue = $this->get_queue();
		if ( isset( $queue[ $specialist_id ] ) ) {
			$queue[ $specialist_id ]['status']      = 'rejected';
			$queue[ $specialist_id ]['reject_reason'] = sanitize_text_field( $reason );
			$queue[ $specialist_id ]['reviewed_at'] = time();
			return update_option( self::STATUS_OPTION, $queue, false );
		}
		return false;
	}

	// -----------------------------------------------------------------------
	// Bronze auto-verification (email + phone confirmed)
	// -----------------------------------------------------------------------

	/**
	 * Auto-approve bronze level when email and phone are both confirmed.
	 *
	 * Call this after email/SMS confirmation flow completes.
	 *
	 * @param int $specialist_id
	 * @return bool
	 */
	public function auto_bronze( int $specialist_id ): bool {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT verification_level, email, phone FROM {$this->specialists_table} WHERE id = %d",
				$specialist_id
			)
		);

		if ( ! $row || $row->verification_level !== 'none' ) {
			return false;
		}
		if ( empty( $row->email ) || empty( $row->phone ) ) {
			return false;
		}

		return $this->approve( $specialist_id, 'bronze' );
	}

	// -----------------------------------------------------------------------
	// Queue access
	// -----------------------------------------------------------------------

	/**
	 * Get all pending verification requests.
	 *
	 * @return array<array<string, mixed>>
	 */
	public function pending_requests(): array {
		return array_values( array_filter(
			$this->get_queue(),
			fn( $item ) => $item['status'] === 'pending'
		) );
	}

	/**
	 * Get the verification status for a specific specialist.
	 *
	 * @param int $specialist_id
	 * @return array<string, mixed>|null
	 */
	public function get_request_status( int $specialist_id ): ?array {
		return $this->get_queue()[ $specialist_id ] ?? null;
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/** @return array<int, array<string, mixed>> */
	private function get_queue(): array {
		$raw = get_option( self::STATUS_OPTION, [] );
		return is_array( $raw ) ? $raw : [];
	}

	private function remove_from_queue( int $specialist_id ): void {
		$queue = $this->get_queue();
		unset( $queue[ $specialist_id ] );
		update_option( self::STATUS_OPTION, $queue, false );
	}
}

<?php
/**
 * LeadRoutingService
 *
 * Matches leads to contractors based on package tier, location, and performance.
 *
 * @package PearBlog\LeadAI\Application
 */

declare(strict_types=1);

namespace PearBlog\LeadAI\Application;

use PearBlog\LeadAI\Domain\Lead;

/**
 * Lead Routing Service
 *
 * Intelligent contractor selection and lead distribution.
 */
class LeadRoutingService {
	private \wpdb $wpdb;

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * Route lead to appropriate contractors.
	 *
	 * @param Lead $lead The lead to route.
	 * @return array List of contractor IDs to assign.
	 */
	public function routeLead(Lead $lead): array {
		$distribution_mode = $this->getDistributionMode($lead->getPackageType());

		switch ($distribution_mode) {
			case 'EXCLUSIVE':
				return $this->selectExclusiveContractor($lead);

			case 'SHARED':
				return $this->selectSharedContractors($lead, 3, 5);

			case 'OPEN':
			default:
				return $this->selectOpenContractors($lead, 10);
		}
	}

	/**
	 * Get distribution mode based on package type.
	 */
	private function getDistributionMode(string $package_type): string {
		return match($package_type) {
			'PREMIUM+', 'PREMIUM_PLUS' => 'EXCLUSIVE',
			'PREMIUM' => 'SHARED',
			default => 'OPEN',
		};
	}

	/**
	 * Select single best contractor (PREMIUM+).
	 */
	private function selectExclusiveContractor(Lead $lead): array {
		$contractors = $this->rankContractors($lead, 1);
		return $contractors;
	}

	/**
	 * Select 3-5 contractors (PREMIUM).
	 */
	private function selectSharedContractors(Lead $lead, int $min, int $max): array {
		$contractors = $this->rankContractors($lead, $max);
		return array_slice($contractors, 0, max($min, count($contractors)));
	}

	/**
	 * Select up to 10 contractors (FREE).
	 */
	private function selectOpenContractors(Lead $lead, int $limit): array {
		$contractors = $this->rankContractors($lead, $limit);
		return $contractors;
	}

	/**
	 * Rank contractors by suitability.
	 *
	 * Ranking factors:
	 * - Package tier (PREMIUM+ > PREMIUM > FREE)
	 * - Distance from lead location
	 * - Rating
	 * - Response history
	 * - Activity level
	 */
	private function rankContractors(Lead $lead, int $limit): array {
		$table_name = $this->wpdb->prefix . 'pt24_contractors';

		// Get contractors matching category and location
		$contractors = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT c.id, c.package_type, c.location, c.rating, c.response_rate, c.last_active
				FROM {$table_name} c
				WHERE c.categories LIKE %s
				AND c.status = 'active'
				ORDER BY
					FIELD(c.package_type, 'PREMIUM+', 'PREMIUM', 'FREE'),
					c.rating DESC,
					c.response_rate DESC,
					c.last_active DESC
				LIMIT %d",
				'%' . $this->wpdb->esc_like($lead->getCategory()) . '%',
				$limit
			),
			ARRAY_A
		);

		$contractor_ids = [];
		foreach ($contractors as $contractor) {
			$score = $this->calculateContractorScore($contractor, $lead);
			$contractor_ids[] = [
				'contractor_id' => $contractor['id'],
				'score'         => $score,
			];
		}

		// Sort by score
		usort($contractor_ids, function($a, $b) {
			return $b['score'] <=> $a['score'];
		});

		return array_column($contractor_ids, 'contractor_id');
	}

	/**
	 * Calculate contractor suitability score.
	 */
	private function calculateContractorScore(array $contractor, Lead $lead): float {
		$score = 0;

		// Package tier weight (0-40 points)
		$score += match($contractor['package_type']) {
			'PREMIUM+', 'PREMIUM_PLUS' => 40,
			'PREMIUM' => 25,
			default => 10,
		};

		// Rating (0-25 points)
		$score += ($contractor['rating'] / 5.0) * 25;

		// Response rate (0-20 points)
		$score += $contractor['response_rate'] * 20;

		// Activity (0-15 points)
		$last_active_days = (time() - strtotime($contractor['last_active'])) / DAY_IN_SECONDS;
		if ($last_active_days < 1) {
			$score += 15;
		} elseif ($last_active_days < 7) {
			$score += 10;
		} elseif ($last_active_days < 30) {
			$score += 5;
		}

		return $score;
	}

	/**
	 * Assign lead to contractor(s).
	 *
	 * @param Lead $lead The lead to assign.
	 * @param array $contractor_ids List of contractor IDs.
	 * @return bool Success status.
	 */
	public function assign(Lead $lead, array $contractor_ids): bool {
		if (empty($contractor_ids)) {
			return false;
		}

		// Assign to primary contractor
		$primary_contractor_id = $contractor_ids[0];
		$lead->assignTo($primary_contractor_id);

		// Store secondary contractors in metadata
		if (count($contractor_ids) > 1) {
			$lead->addMetadata('secondary_contractors', array_slice($contractor_ids, 1));
		}

		return true;
	}
}

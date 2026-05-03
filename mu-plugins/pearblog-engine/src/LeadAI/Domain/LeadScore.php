<?php
/**
 * LeadScore Value Object
 *
 * Calculates and stores the quality score of a lead (0-100).
 *
 * @package PearBlog\LeadAI\Domain
 */

declare(strict_types=1);

namespace PearBlog\LeadAI\Domain;

/**
 * Lead Score
 *
 * Weighted scoring system for lead quality assessment.
 *
 * Formula:
 * score = urgency (0-30) + budget_signal (0-20) + clarity (0-20)
 *       + location_match (0-15) + category_demand (0-15)
 */
class LeadScore {
	private int $urgency_score;
	private int $budget_signal;
	private int $clarity;
	private int $location_match;
	private int $category_demand;
	private int $total_score;

	public function __construct(
		int $urgency_score,
		int $budget_signal,
		int $clarity,
		int $location_match,
		int $category_demand
	) {
		$this->urgency_score    = max(0, min(30, $urgency_score));
		$this->budget_signal    = max(0, min(20, $budget_signal));
		$this->clarity          = max(0, min(20, $clarity));
		$this->location_match   = max(0, min(15, $location_match));
		$this->category_demand  = max(0, min(15, $category_demand));

		$this->total_score = $this->urgency_score + $this->budget_signal
			+ $this->clarity + $this->location_match + $this->category_demand;
	}

	public function getTotalScore(): int {
		return $this->total_score;
	}

	public function getUrgencyScore(): int {
		return $this->urgency_score;
	}

	public function getBudgetSignal(): int {
		return $this->budget_signal;
	}

	public function getClarity(): int {
		return $this->clarity;
	}

	public function getLocationMatch(): int {
		return $this->location_match;
	}

	public function getCategoryDemand(): int {
		return $this->category_demand;
	}

	/**
	 * Get pricing tier based on score.
	 */
	public function getPricingTier(): array {
		if ($this->total_score >= 80) {
			return ['tier' => 'premium', 'price' => 40.00];
		} elseif ($this->total_score >= 60) {
			return ['tier' => 'standard', 'price' => 25.00];
		} elseif ($this->total_score >= 40) {
			return ['tier' => 'basic', 'price' => 10.00];
		}

		return ['tier' => 'free', 'price' => 0.00];
	}

	/**
	 * Get quality category.
	 */
	public function getQualityCategory(): string {
		if ($this->total_score >= 80) {
			return 'HIGH';
		} elseif ($this->total_score >= 60) {
			return 'MEDIUM';
		} elseif ($this->total_score >= 40) {
			return 'LOW';
		}

		return 'VERY_LOW';
	}

	/**
	 * Convert to array for storage.
	 */
	public function toArray(): array {
		return [
			'urgency_score'    => $this->urgency_score,
			'budget_signal'    => $this->budget_signal,
			'clarity'          => $this->clarity,
			'location_match'   => $this->location_match,
			'category_demand'  => $this->category_demand,
			'total_score'      => $this->total_score,
			'quality_category' => $this->getQualityCategory(),
			'pricing_tier'     => $this->getPricingTier(),
		];
	}
}

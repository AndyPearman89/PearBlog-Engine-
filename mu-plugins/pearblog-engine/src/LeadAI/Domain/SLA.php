<?php
/**
 * SLA Value Object
 *
 * Defines Service Level Agreement parameters for different package tiers.
 *
 * @package PearBlog\LeadAI\Domain
 */

declare(strict_types=1);

namespace PearBlog\LeadAI\Domain;

/**
 * SLA
 *
 * Response time limits and escalation rules by package type.
 */
class SLA {
	private string $package_type;
	private ?int $response_time_minutes;
	private bool $ai_fallback_enabled;
	private bool $escalation_enabled;

	public function __construct(string $package_type) {
		$this->package_type = strtoupper($package_type);
		$this->setPackageRules();
	}

	private function setPackageRules(): void {
		switch ($this->package_type) {
			case 'PREMIUM+':
			case 'PREMIUM_PLUS':
				$this->response_time_minutes = 30;
				$this->ai_fallback_enabled   = true;
				$this->escalation_enabled    = true;
				break;

			case 'PREMIUM':
				$this->response_time_minutes = 120; // 2 hours
				$this->ai_fallback_enabled   = true;
				$this->escalation_enabled    = true;
				break;

			case 'FREE':
			default:
				$this->response_time_minutes = null; // No SLA
				$this->ai_fallback_enabled   = false;
				$this->escalation_enabled    = false;
				break;
		}
	}

	public function getPackageType(): string {
		return $this->package_type;
	}

	public function getResponseTimeMinutes(): ?int {
		return $this->response_time_minutes;
	}

	public function hasResponseTimeLimit(): bool {
		return $this->response_time_minutes !== null;
	}

	public function isAIFallbackEnabled(): bool {
		return $this->ai_fallback_enabled;
	}

	public function isEscalationEnabled(): bool {
		return $this->escalation_enabled;
	}

	/**
	 * Check if SLA is breached based on elapsed time.
	 */
	public function isBreached(int $elapsed_minutes): bool {
		if (!$this->hasResponseTimeLimit()) {
			return false;
		}

		return $elapsed_minutes > $this->response_time_minutes;
	}

	/**
	 * Get deadline timestamp from creation time.
	 */
	public function getDeadlineTimestamp(int $created_timestamp): ?int {
		if (!$this->hasResponseTimeLimit()) {
			return null;
		}

		return $created_timestamp + ($this->response_time_minutes * 60);
	}

	/**
	 * Get SLA tier label.
	 */
	public function getTierLabel(): string {
		return match($this->package_type) {
			'PREMIUM+', 'PREMIUM_PLUS' => 'Premium Plus (30 min)',
			'PREMIUM' => 'Premium (2h)',
			default => 'Free (no SLA)',
		};
	}
}

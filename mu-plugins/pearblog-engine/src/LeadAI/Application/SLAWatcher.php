<?php
/**
 * SLAWatcher
 *
 * Monitors lead response times and detects SLA breaches.
 *
 * @package PearBlog\LeadAI\Application
 */

declare(strict_types=1);

namespace PearBlog\LeadAI\Application;

use PearBlog\LeadAI\Domain\Lead;
use PearBlog\LeadAI\Domain\LeadState;

/**
 * SLA Watcher
 *
 * Background service for SLA monitoring.
 */
class SLAWatcher {
	private \wpdb $wpdb;

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * Check all active leads for SLA breaches.
	 *
	 * @return array List of breached leads.
	 */
	public function checkAll(): array {
		$table_name = $this->wpdb->prefix . 'pt24_leads';

		$active_leads = $this->wpdb->get_results(
			"SELECT * FROM {$table_name}
			WHERE status IN ('WAITING_FOR_RESPONSE', 'NEW')
			AND package_type IN ('PREMIUM', 'PREMIUM+')
			ORDER BY created_at ASC",
			ARRAY_A
		);

		$breached_leads = [];

		foreach ($active_leads as $lead_data) {
			$lead = Lead::fromArray($lead_data);

			if ($lead->isSLABreached()) {
				$breached_leads[] = $lead;
			}
		}

		return $breached_leads;
	}

	/**
	 * Get time remaining until SLA breach.
	 *
	 * @param Lead $lead The lead to check.
	 * @return int|null Minutes remaining, null if no SLA.
	 */
	public function getTimeRemaining(Lead $lead): ?int {
		$deadline = $lead->getSLADeadline();

		if ($deadline === null) {
			return null;
		}

		$remaining_seconds = $deadline - time();
		return max(0, (int) ($remaining_seconds / 60));
	}

	/**
	 * Check if lead is approaching SLA breach (80% threshold).
	 *
	 * @param Lead $lead The lead to check.
	 * @return bool True if approaching breach.
	 */
	public function isApproachingBreach(Lead $lead): bool {
		$sla = new \PearBlog\LeadAI\Domain\SLA($lead->getPackageType());

		if (!$sla->hasResponseTimeLimit()) {
			return false;
		}

		$elapsed_minutes = $lead->getElapsedMinutes();
		$limit_minutes   = $sla->getResponseTimeMinutes();
		$threshold       = $limit_minutes * 0.8;

		return $elapsed_minutes >= $threshold && $elapsed_minutes < $limit_minutes;
	}

	/**
	 * Run monitoring cycle.
	 *
	 * @return array Results with breached and approaching leads.
	 */
	public function monitor(): array {
		$breached_leads    = $this->checkAll();
		$approaching_leads = [];

		// Check all active leads
		$table_name = $this->wpdb->prefix . 'pt24_leads';
		$active_leads = $this->wpdb->get_results(
			"SELECT * FROM {$table_name}
			WHERE status IN ('WAITING_FOR_RESPONSE', 'NEW')
			AND package_type IN ('PREMIUM', 'PREMIUM+')",
			ARRAY_A
		);

		foreach ($active_leads as $lead_data) {
			$lead = Lead::fromArray($lead_data);

			if ($this->isApproachingBreach($lead)) {
				$approaching_leads[] = $lead;
			}
		}

		return [
			'breached'    => $breached_leads,
			'approaching' => $approaching_leads,
			'checked_at'  => time(),
		];
	}
}

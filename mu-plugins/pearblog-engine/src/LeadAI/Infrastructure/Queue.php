<?php
/**
 * Queue
 *
 * Async task queue for background processing.
 *
 * @package PearBlog\LeadAI\Infrastructure
 */

declare(strict_types=1);

namespace PearBlog\LeadAI\Infrastructure;

/**
 * Queue
 *
 * WordPress Action Scheduler based queue system.
 */
class Queue {
	/**
	 * Enqueue a task for async processing.
	 *
	 * @param string $task_type Task type identifier.
	 * @param array $data Task data.
	 * @param int $delay_seconds Optional delay before processing.
	 * @return bool Success status.
	 */
	public function enqueue(string $task_type, array $data, int $delay_seconds = 0): bool {
		$hook = 'pt24_queue_' . $task_type;
		$timestamp = time() + $delay_seconds;

		// Use WP-Cron for scheduling
		return (bool) wp_schedule_single_event($timestamp, $hook, [$data]);
	}

	/**
	 * Enqueue SLA monitoring task.
	 *
	 * @param int $lead_id Lead ID to monitor.
	 * @param int $check_at_timestamp When to check.
	 * @return bool Success status.
	 */
	public function enqueueSLACheck(int $lead_id, int $check_at_timestamp): bool {
		$delay = max(0, $check_at_timestamp - time());

		return $this->enqueue('sla_check', [
			'lead_id' => $lead_id,
		], $delay);
	}

	/**
	 * Enqueue AI reply task.
	 *
	 * @param int $lead_id Lead ID.
	 * @return bool Success status.
	 */
	public function enqueueAIReply(int $lead_id): bool {
		return $this->enqueue('ai_reply', [
			'lead_id' => $lead_id,
		]);
	}

	/**
	 * Enqueue escalation task.
	 *
	 * @param int $lead_id Lead ID.
	 * @return bool Success status.
	 */
	public function enqueueEscalation(int $lead_id): bool {
		return $this->enqueue('escalation', [
			'lead_id' => $lead_id,
		]);
	}

	/**
	 * Register queue workers.
	 *
	 * Should be called during plugin initialization.
	 */
	public static function registerWorkers(): void {
		// SLA check worker
		add_action('pt24_queue_sla_check', [self::class, 'processSLACheck']);

		// AI reply worker
		add_action('pt24_queue_ai_reply', [self::class, 'processAIReply']);

		// Escalation worker
		add_action('pt24_queue_escalation', [self::class, 'processEscalation']);
	}

	/**
	 * Process SLA check task.
	 */
	public static function processSLACheck(array $data): void {
		$lead_id = $data['lead_id'] ?? null;

		if (!$lead_id) {
			return;
		}

		$orchestrator = new \PearBlog\LeadAI\Application\LeadOrchestrator();
		$orchestrator->runSLAMonitoring();
	}

	/**
	 * Process AI reply task.
	 */
	public static function processAIReply(array $data): void {
		$lead_id = $data['lead_id'] ?? null;

		if (!$lead_id) {
			return;
		}

		// Get lead and apply AI reply
		global $wpdb;
		$table_name = $wpdb->prefix . 'pt24_leads';

		$lead_data = $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $lead_id),
			ARRAY_A
		);

		if (!$lead_data) {
			return;
		}

		$lead_data['metadata'] = json_decode($lead_data['metadata'] ?? '{}', true);
		$lead_data['score_breakdown'] = json_decode($lead_data['score_breakdown'] ?? '{}', true);

		$lead = \PearBlog\LeadAI\Domain\Lead::fromArray($lead_data);

		$ai_reply_service = new \PearBlog\LeadAI\Application\AIReplyService();
		$ai_reply_service->apply($lead);
	}

	/**
	 * Process escalation task.
	 */
	public static function processEscalation(array $data): void {
		$lead_id = $data['lead_id'] ?? null;

		if (!$lead_id) {
			return;
		}

		// Get lead and escalate
		global $wpdb;
		$table_name = $wpdb->prefix . 'pt24_leads';

		$lead_data = $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $lead_id),
			ARRAY_A
		);

		if (!$lead_data) {
			return;
		}

		$lead_data['metadata'] = json_decode($lead_data['metadata'] ?? '{}', true);
		$lead_data['score_breakdown'] = json_decode($lead_data['score_breakdown'] ?? '{}', true);

		$lead = \PearBlog\LeadAI\Domain\Lead::fromArray($lead_data);

		$escalation_service = new \PearBlog\LeadAI\Application\EscalationService();
		$escalation_service->escalate($lead);
	}
}

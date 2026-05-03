<?php
/**
 * EscalationService
 *
 * Handles SLA breach escalation and lead redistribution.
 *
 * @package PearBlog\LeadAI\Application
 */

declare(strict_types=1);

namespace PearBlog\LeadAI\Application;

use PearBlog\LeadAI\Domain\Lead;
use PearBlog\LeadAI\Infrastructure\SMSProvider;
use PearBlog\LeadAI\Infrastructure\EmailProvider;

/**
 * Escalation Service
 *
 * Multi-phase escalation process for unresponsive contractors.
 */
class EscalationService {
	private AIReplyService $ai_reply_service;
	private LeadRoutingService $routing_service;
	private SMSProvider $sms_provider;
	private EmailProvider $email_provider;

	public function __construct() {
		$this->ai_reply_service = new AIReplyService();
		$this->routing_service  = new LeadRoutingService();
		$this->sms_provider     = new SMSProvider();
		$this->email_provider   = new EmailProvider();
	}

	/**
	 * Execute escalation flow for a breached lead.
	 *
	 * Phase 1: AI reply + notifications
	 * Phase 2: Lead escalation + redistribution
	 *
	 * @param Lead $lead The lead with SLA breach.
	 * @return array Escalation results.
	 */
	public function escalate(Lead $lead): array {
		$results = [
			'lead_id' => $lead->getId(),
			'phase'   => null,
			'actions' => [],
		];

		// Phase 1: AI Reply + Notification
		if ($lead->getStatus() === \PearBlog\LeadAI\Domain\LeadState::WAITING_FOR_RESPONSE) {
			$results['phase'] = 1;

			// Send AI reply to customer
			$ai_result = $this->ai_reply_service->apply($lead);
			$results['actions'][] = [
				'type'    => 'ai_reply',
				'success' => $ai_result['success'],
				'reply'   => $ai_result['reply'] ?? null,
			];

			// Notify contractor
			$notification_result = $this->notifyContractor($lead);
			$results['actions'][] = [
				'type'    => 'contractor_notification',
				'success' => $notification_result['success'],
			];

			return $results;
		}

		// Phase 2: Escalation + Redistribution
		if ($lead->getStatus() === \PearBlog\LeadAI\Domain\LeadState::AI_REPLIED) {
			$results['phase'] = 2;

			// Escalate lead
			$lead->escalate();
			$results['actions'][] = [
				'type'    => 'escalate',
				'success' => true,
			];

			// Redistribute to new contractors
			$new_contractors = $this->routing_service->routeLead($lead);
			$redistributed   = $this->routing_service->assign($lead, $new_contractors);

			$results['actions'][] = [
				'type'              => 'redistribute',
				'success'           => $redistributed,
				'new_contractors'   => $new_contractors,
			];

			if ($redistributed) {
				$lead->redistribute();
			}

			return $results;
		}

		$results['phase'] = 'none';
		return $results;
	}

	/**
	 * Notify contractor of SLA breach via SMS and email.
	 *
	 * @param Lead $lead The lead.
	 * @return array Notification result.
	 */
	private function notifyContractor(Lead $lead): array {
		$contractor_id = $lead->getAssignedContractorId();

		if (!$contractor_id) {
			return ['success' => false, 'reason' => 'No contractor assigned'];
		}

		$contractor = $this->getContractorData($contractor_id);

		if (!$contractor) {
			return ['success' => false, 'reason' => 'Contractor not found'];
		}

		$message = $this->buildNotificationMessage($lead);

		// Send SMS
		$sms_sent = false;
		if (!empty($contractor['phone'])) {
			$sms_sent = $this->sms_provider->send($contractor['phone'], $message);
		}

		// Send email
		$email_sent = false;
		if (!empty($contractor['email'])) {
			$email_sent = $this->email_provider->send(
				$contractor['email'],
				'PT24: Oczekujące zlecenie wymagające odpowiedzi',
				$message
			);
		}

		return [
			'success'    => $sms_sent || $email_sent,
			'sms_sent'   => $sms_sent,
			'email_sent' => $email_sent,
		];
	}

	/**
	 * Build notification message for contractor.
	 */
	private function buildNotificationMessage(Lead $lead): string {
		$category = $lead->getCategory();
		$location = $lead->getLocation();
		$elapsed  = $lead->getElapsedMinutes();

		return <<<MESSAGE
PT24 - OCZEKUJĄCE ZLECENIE

Kategoria: {$category}
Lokalizacja: {$location}
Czas oczekiwania: {$elapsed} min

Klient czeka na odpowiedź. Zaloguj się do PT24, aby zobaczyć szczegóły i odpowiedzieć.

Link: https://pt24.pl/dashboard/leads/{$lead->getId()}
MESSAGE;
	}

	/**
	 * Get contractor data from database.
	 */
	private function getContractorData(int $contractor_id): ?array {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pt24_contractors';

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, email, phone, name FROM {$table_name} WHERE id = %d",
				$contractor_id
			),
			ARRAY_A
		);
	}
}

<?php
/**
 * LeadOrchestrator
 *
 * Main application service coordinating the entire lead lifecycle.
 *
 * @package PearBlog\LeadAI\Application
 */

declare(strict_types=1);

namespace PearBlog\LeadAI\Application;

use PearBlog\LeadAI\Domain\Lead;
use PearBlog\LeadAI\Domain\LeadScore;
use PearBlog\LeadAI\Domain\LeadIntent;
use PearBlog\AI\OpenAIClient;

/**
 * Lead Orchestrator
 *
 * Coordinates lead intake, analysis, routing, and lifecycle management.
 */
class LeadOrchestrator {
	private \wpdb $wpdb;
	private OpenAIClient $ai_client;
	private LeadRoutingService $routing_service;
	private AIReplyService $ai_reply_service;
	private SLAWatcher $sla_watcher;
	private EscalationService $escalation_service;

	public function __construct() {
		global $wpdb;
		$this->wpdb               = $wpdb;
		$this->ai_client          = new OpenAIClient();
		$this->routing_service    = new LeadRoutingService();
		$this->ai_reply_service   = new AIReplyService();
		$this->sla_watcher        = new SLAWatcher();
		$this->escalation_service = new EscalationService();
	}

	/**
	 * Process a new lead through the complete workflow.
	 *
	 * STEP 1: Lead intake
	 * STEP 2: AI analysis (intent, score)
	 * STEP 3: Routing to contractors
	 * STEP 4: SLA monitoring starts
	 *
	 * @param array $data Lead data (category, location, message, package_type).
	 * @return array Result with lead ID and status.
	 */
	public function processNewLead(array $data): array {
		// STEP 1: Create lead entity
		$lead = new Lead(
			$data['category'],
			$data['location'],
			$data['message'],
			$data['package_type'] ?? 'FREE'
		);

		// Save to database
		$lead_id = $this->saveLead($lead);
		if (!$lead_id) {
			return [
				'success' => false,
				'error'   => 'Failed to save lead',
			];
		}

		// STEP 2: AI Analysis
		$analysis_result = $this->analyzeLead($lead);
		if ($analysis_result['success']) {
			$lead->analyze(
				$analysis_result['score'],
				$analysis_result['intent'],
				$analysis_result['urgency']
			);

			// Update lead with analysis
			$this->updateLead($lead);
		}

		// STEP 3: Route to contractors
		$contractors = $this->routing_service->routeLead($lead);
		$assigned    = $this->routing_service->assign($lead, $contractors);

		if ($assigned) {
			$this->updateLead($lead);
		}

		return [
			'success'       => true,
			'lead_id'       => $lead_id,
			'status'        => $lead->getStatus()->value,
			'score'         => $analysis_result['score']->getTotalScore() ?? 0,
			'intent'        => $analysis_result['intent']->value ?? null,
			'contractors'   => $contractors,
			'sla_deadline'  => $lead->getSLADeadline(),
		];
	}

	/**
	 * Analyze lead with AI.
	 *
	 * Detects:
	 * - Intent (REPAIR, INSTALLATION, URGENT, etc.)
	 * - Urgency level
	 * - Lead quality score (0-100)
	 *
	 * @param Lead $lead The lead to analyze.
	 * @return array Analysis result.
	 */
	private function analyzeLead(Lead $lead): array {
		$prompt = $this->buildAnalysisPrompt($lead);

		$response = $this->ai_client->generate_text(
			$prompt,
			[
				'max_tokens'  => 200,
				'temperature' => 0.3,
			]
		);

		// Parse AI response (expecting JSON)
		$analysis = json_decode($response, true);

		if (!$analysis) {
			// Fallback to default values
			$analysis = [
				'intent'         => 'OTHER',
				'urgency'        => 'MEDIUM',
				'urgency_score'  => 15,
				'budget_signal'  => 10,
				'clarity'        => 10,
				'location_match' => 10,
				'category_demand' => 10,
			];
		}

		$score = new LeadScore(
			$analysis['urgency_score'] ?? 15,
			$analysis['budget_signal'] ?? 10,
			$analysis['clarity'] ?? 10,
			$analysis['location_match'] ?? 10,
			$analysis['category_demand'] ?? 10
		);

		$intent = LeadIntent::from($analysis['intent'] ?? 'OTHER');

		return [
			'success' => true,
			'score'   => $score,
			'intent'  => $intent,
			'urgency' => $analysis['urgency'] ?? 'MEDIUM',
		];
	}

	/**
	 * Build AI analysis prompt.
	 */
	private function buildAnalysisPrompt(Lead $lead): string {
		$category = $lead->getCategory();
		$location = $lead->getLocation();
		$message  = $lead->getMessage();

		return <<<PROMPT
Analyze this service request and return JSON with lead scoring:

Category: {$category}
Location: {$location}
Message: {$message}

Return JSON with these fields:
{
  "intent": "REPAIR|INSTALLATION|URGENT|CONSULTATION|OTHER",
  "urgency": "LOW|MEDIUM|HIGH",
  "urgency_score": 0-30,
  "budget_signal": 0-20,
  "clarity": 0-20,
  "location_match": 0-15,
  "category_demand": 0-15
}

Scoring guidelines:
- urgency_score: Higher if mentions "pilne", "natychmiast", "dziś"
- budget_signal: Higher if mentions specific budget or price range
- clarity: Higher if message is detailed and specific
- location_match: Higher if location is clear and specific
- category_demand: Based on service popularity

Return only valid JSON, no explanation.
PROMPT;
	}

	/**
	 * Run SLA monitoring and escalation cycle.
	 *
	 * Should be called periodically (e.g., every 5 minutes via cron).
	 *
	 * @return array Results with escalations performed.
	 */
	public function runSLAMonitoring(): array {
		$monitoring_result = $this->sla_watcher->monitor();
		$breached_leads    = $monitoring_result['breached'];

		$escalations = [];

		foreach ($breached_leads as $lead) {
			$escalation_result = $this->escalation_service->escalate($lead);
			$escalations[]     = $escalation_result;

			// Update lead in database
			$this->updateLead($lead);
		}

		return [
			'checked_at'        => $monitoring_result['checked_at'],
			'breached_count'    => count($breached_leads),
			'approaching_count' => count($monitoring_result['approaching']),
			'escalations'       => $escalations,
		];
	}

	/**
	 * Mark lead as responded by contractor.
	 *
	 * @param int $lead_id Lead ID.
	 * @return bool Success status.
	 */
	public function markAsResponded(int $lead_id): bool {
		$lead = $this->getLeadById($lead_id);

		if (!$lead) {
			return false;
		}

		try {
			$lead->markAsResponded();
			$this->updateLead($lead);
			return true;
		} catch (\Exception $e) {
			error_log('[LeadOrchestrator] Error marking lead as responded: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Close a lead.
	 *
	 * @param int $lead_id Lead ID.
	 * @return bool Success status.
	 */
	public function closeLead(int $lead_id): bool {
		$lead = $this->getLeadById($lead_id);

		if (!$lead) {
			return false;
		}

		$lead->close();
		$this->updateLead($lead);

		return true;
	}

	/**
	 * Save new lead to database.
	 */
	private function saveLead(Lead $lead): ?int {
		$table_name = $this->wpdb->prefix . 'pt24_leads';
		$lead_data  = $lead->toArray();

		$result = $this->wpdb->insert(
			$table_name,
			[
				'category'     => $lead_data['category'],
				'location'     => $lead_data['location'],
				'message'      => $lead_data['message'],
				'status'       => $lead_data['status'],
				'package_type' => $lead_data['package_type'],
				'created_at'   => $lead_data['created_at'],
				'metadata'     => json_encode($lead_data['metadata']),
			],
			['%s', '%s', '%s', '%s', '%s', '%d', '%s']
		);

		return $result ? $this->wpdb->insert_id : null;
	}

	/**
	 * Update existing lead in database.
	 */
	private function updateLead(Lead $lead): bool {
		$table_name = $this->wpdb->prefix . 'pt24_leads';
		$lead_data  = $lead->toArray();

		return (bool) $this->wpdb->update(
			$table_name,
			[
				'status'                 => $lead_data['status'],
				'score'                  => $lead_data['score'],
				'score_breakdown'        => json_encode($lead_data['score_breakdown']),
				'intent'                 => $lead_data['intent'],
				'urgency'                => $lead_data['urgency'],
				'assigned_contractor_id' => $lead_data['assigned_contractor_id'],
				'responded_at'           => $lead_data['responded_at'],
				'closed_at'              => $lead_data['closed_at'],
				'metadata'               => json_encode($lead_data['metadata']),
			],
			['id' => $lead->getId()],
			['%s', '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%s'],
			['%d']
		);
	}

	/**
	 * Get lead by ID.
	 */
	private function getLeadById(int $lead_id): ?Lead {
		$table_name = $this->wpdb->prefix . 'pt24_leads';

		$lead_data = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$lead_id
			),
			ARRAY_A
		);

		if (!$lead_data) {
			return null;
		}

		// Decode JSON fields
		$lead_data['metadata']        = json_decode($lead_data['metadata'] ?? '{}', true);
		$lead_data['score_breakdown'] = json_decode($lead_data['score_breakdown'] ?? '{}', true);

		return Lead::fromArray($lead_data);
	}
}

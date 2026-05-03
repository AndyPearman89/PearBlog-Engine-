<?php
/**
 * Lead Entity
 *
 * Core domain entity representing a service request in the PT24.PRO system.
 *
 * @package PearBlog\LeadAI\Domain
 */

declare(strict_types=1);

namespace PearBlog\LeadAI\Domain;

/**
 * Lead
 *
 * Aggregate root for lead management.
 */
class Lead {
	private ?int $id;
	private string $category;
	private string $location;
	private string $message;
	private LeadState $status;
	private LeadScore $score;
	private LeadIntent $intent;
	private string $urgency;
	private int $created_at;
	private ?int $assigned_contractor_id;
	private ?int $responded_at;
	private ?int $closed_at;
	private string $package_type;
	private array $metadata;

	public function __construct(
		string $category,
		string $location,
		string $message,
		string $package_type = 'FREE',
		?int $id = null
	) {
		$this->id                      = $id;
		$this->category                = $category;
		$this->location                = $location;
		$this->message                 = $message;
		$this->status                  = LeadState::NEW;
		$this->package_type            = strtoupper($package_type);
		$this->created_at              = time();
		$this->assigned_contractor_id  = null;
		$this->responded_at            = null;
		$this->closed_at               = null;
		$this->metadata                = [];
	}

	// Getters
	public function getId(): ?int {
		return $this->id;
	}

	public function getCategory(): string {
		return $this->category;
	}

	public function getLocation(): string {
		return $this->location;
	}

	public function getMessage(): string {
		return $this->message;
	}

	public function getStatus(): LeadState {
		return $this->status;
	}

	public function getScore(): LeadScore {
		return $this->score;
	}

	public function getIntent(): LeadIntent {
		return $this->intent;
	}

	public function getUrgency(): string {
		return $this->urgency;
	}

	public function getCreatedAt(): int {
		return $this->created_at;
	}

	public function getPackageType(): string {
		return $this->package_type;
	}

	public function getAssignedContractorId(): ?int {
		return $this->assigned_contractor_id;
	}

	public function getRespondedAt(): ?int {
		return $this->responded_at;
	}

	public function getClosedAt(): ?int {
		return $this->closed_at;
	}

	public function getMetadata(): array {
		return $this->metadata;
	}

	// Business logic methods

	/**
	 * Analyze lead with AI and set score/intent.
	 */
	public function analyze(LeadScore $score, LeadIntent $intent, string $urgency): void {
		$this->score   = $score;
		$this->intent  = $intent;
		$this->urgency = $urgency;
	}

	/**
	 * Assign lead to a contractor.
	 */
	public function assignTo(int $contractor_id): void {
		if ($this->status !== LeadState::NEW) {
			throw new \DomainException('Lead must be in NEW status to assign');
		}

		$this->assigned_contractor_id = $contractor_id;
		$this->status                 = LeadState::WAITING_FOR_RESPONSE;
	}

	/**
	 * Mark lead as responded by contractor.
	 */
	public function markAsResponded(): void {
		if ($this->status !== LeadState::WAITING_FOR_RESPONSE && $this->status !== LeadState::AI_REPLIED) {
			throw new \DomainException('Invalid state transition to responded');
		}

		$this->responded_at = time();
		$this->status       = LeadState::CLOSED;
	}

	/**
	 * Apply AI fallback reply.
	 */
	public function applyAIReply(): void {
		if ($this->status !== LeadState::WAITING_FOR_RESPONSE) {
			throw new \DomainException('AI reply can only be applied when waiting for response');
		}

		$this->status = LeadState::AI_REPLIED;
	}

	/**
	 * Escalate lead due to SLA breach.
	 */
	public function escalate(): void {
		if (!$this->status->isActive()) {
			throw new \DomainException('Cannot escalate inactive lead');
		}

		$this->status = LeadState::ESCALATED;
	}

	/**
	 * Redistribute lead to new contractors.
	 */
	public function redistribute(): void {
		if ($this->status !== LeadState::ESCALATED) {
			throw new \DomainException('Only escalated leads can be redistributed');
		}

		$this->assigned_contractor_id = null;
		$this->status                 = LeadState::REDISTRIBUTED;
	}

	/**
	 * Close lead.
	 */
	public function close(): void {
		$this->closed_at = time();
		$this->status    = LeadState::CLOSED;
	}

	/**
	 * Get elapsed time in minutes since creation.
	 */
	public function getElapsedMinutes(): int {
		return (int) ((time() - $this->created_at) / 60);
	}

	/**
	 * Check if SLA is breached.
	 */
	public function isSLABreached(): bool {
		$sla = new SLA($this->package_type);
		return $sla->isBreached($this->getElapsedMinutes());
	}

	/**
	 * Get SLA deadline timestamp.
	 */
	public function getSLADeadline(): ?int {
		$sla = new SLA($this->package_type);
		return $sla->getDeadlineTimestamp($this->created_at);
	}

	/**
	 * Add metadata.
	 */
	public function addMetadata(string $key, $value): void {
		$this->metadata[$key] = $value;
	}

	/**
	 * Convert to array for persistence.
	 */
	public function toArray(): array {
		return [
			'id'                     => $this->id,
			'category'               => $this->category,
			'location'               => $this->location,
			'message'                => $this->message,
			'status'                 => $this->status->value,
			'score'                  => isset($this->score) ? $this->score->getTotalScore() : 0,
			'score_breakdown'        => isset($this->score) ? $this->score->toArray() : null,
			'intent'                 => isset($this->intent) ? $this->intent->value : null,
			'urgency'                => $this->urgency ?? null,
			'package_type'           => $this->package_type,
			'assigned_contractor_id' => $this->assigned_contractor_id,
			'created_at'             => $this->created_at,
			'responded_at'           => $this->responded_at,
			'closed_at'              => $this->closed_at,
			'metadata'               => $this->metadata,
		];
	}

	/**
	 * Hydrate from array.
	 */
	public static function fromArray(array $data): self {
		$lead = new self(
			$data['category'],
			$data['location'],
			$data['message'],
			$data['package_type'] ?? 'FREE',
			$data['id'] ?? null
		);

		$lead->status                 = LeadState::from($data['status']);
		$lead->created_at             = $data['created_at'];
		$lead->assigned_contractor_id = $data['assigned_contractor_id'];
		$lead->responded_at           = $data['responded_at'];
		$lead->closed_at              = $data['closed_at'];
		$lead->metadata               = $data['metadata'] ?? [];
		$lead->urgency                = $data['urgency'] ?? 'MEDIUM';

		if (isset($data['intent'])) {
			$lead->intent = LeadIntent::from($data['intent']);
		}

		if (isset($data['score_breakdown'])) {
			$score_data = $data['score_breakdown'];
			$lead->score = new LeadScore(
				$score_data['urgency_score'],
				$score_data['budget_signal'],
				$score_data['clarity'],
				$score_data['location_match'],
				$score_data['category_demand']
			);
		}

		return $lead;
	}
}

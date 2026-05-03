<?php
/**
 * AIReplyService
 *
 * Generates AI-powered fallback responses when contractors don't respond in time.
 *
 * @package PearBlog\LeadAI\Application
 */

declare(strict_types=1);

namespace PearBlog\LeadAI\Application;

use PearBlog\LeadAI\Domain\Lead;
use PearBlog\AI\OpenAIClient;

/**
 * AI Reply Service
 *
 * Provides automated platform assistance without impersonating contractors.
 */
class AIReplyService {
	private OpenAIClient $ai_client;

	public function __construct() {
		$this->ai_client = new OpenAIClient();
	}

	/**
	 * Generate AI fallback reply for a lead.
	 *
	 * @param Lead $lead The lead requiring a response.
	 * @return string The generated reply.
	 */
	public function generateReply(Lead $lead): string {
		$prompt = $this->buildPrompt($lead);

		$reply = $this->ai_client->generate_text(
			$prompt,
			[
				'max_tokens'  => 300,
				'temperature' => 0.7,
			]
		);

		// Add system label
		$reply .= "\n\n---\n";
		$reply .= "✉️ Automatyczna odpowiedź systemowa Pt24\n";
		$reply .= "To nie jest odpowiedź wykonawcy. Poczekaj na kontakt bezpośredni.";

		return $reply;
	}

	/**
	 * Build the AI prompt based on lead context.
	 */
	private function buildPrompt(Lead $lead): string {
		$category = $lead->getCategory();
		$location = $lead->getLocation();
		$message  = $lead->getMessage();

		$prompt = <<<PROMPT
You are an assistant of Pt24 marketplace.

You DO NOT impersonate the contractor.

Context:
- Service: {$category}
- Location: {$location}
- User request: {$message}

Your goal:
1. Acknowledge request
2. Suggest next steps
3. Ask 1-2 clarifying questions
4. Encourage direct contact with contractors

NEVER:
- give exact price
- confirm availability
- pretend to be contractor
- make promises about service delivery

OUTPUT STRUCTURE:
- Greeting (warm and professional)
- Status update (we're working on finding contractors)
- Guidance (what happens next)
- 1-2 clarifying questions (budget, timeframe, specific requirements)
- CTA (encourage patience and direct contact)

Write in Polish, professional but friendly tone.
PROMPT;

		return $prompt;
	}

	/**
	 * Check if AI reply should be triggered.
	 *
	 * @param Lead $lead The lead to check.
	 * @return bool True if AI reply should be sent.
	 */
	public function shouldTrigger(Lead $lead): bool {
		// Only trigger if SLA is breached and AI is enabled for package
		if (!$lead->isSLABreached()) {
			return false;
		}

		$sla = new \PearBlog\LeadAI\Domain\SLA($lead->getPackageType());
		if (!$sla->isAIFallbackEnabled()) {
			return false;
		}

		// Don't trigger if already AI replied
		$status = $lead->getStatus();
		if ($status === \PearBlog\LeadAI\Domain\LeadState::AI_REPLIED) {
			return false;
		}

		return true;
	}

	/**
	 * Apply AI reply to lead and log it.
	 *
	 * @param Lead $lead The lead to reply to.
	 * @return array Result with reply text and timestamp.
	 */
	public function apply(Lead $lead): array {
		if (!$this->shouldTrigger($lead)) {
			return [
				'success' => false,
				'reason'  => 'AI reply not eligible',
			];
		}

		$reply = $this->generateReply($lead);
		$lead->applyAIReply();
		$lead->addMetadata('ai_reply', [
			'text'      => $reply,
			'timestamp' => time(),
		]);

		return [
			'success'   => true,
			'reply'     => $reply,
			'timestamp' => time(),
		];
	}
}

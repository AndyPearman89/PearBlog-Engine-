<?php
/**
 * LeadIntent Enum
 *
 * Categorizes the user's intent based on AI analysis of their message.
 *
 * @package PearBlog\LeadAI\Domain
 */

declare(strict_types=1);

namespace PearBlog\LeadAI\Domain;

/**
 * Lead Intent
 *
 * AI-detected intent categories for lead classification.
 */
enum LeadIntent: string {
	case REPAIR = 'REPAIR';
	case INSTALLATION = 'INSTALLATION';
	case URGENT = 'URGENT';
	case CONSULTATION = 'CONSULTATION';
	case OTHER = 'OTHER';

	/**
	 * Get intent priority score.
	 */
	public function getPriorityScore(): int {
		return match($this) {
			self::URGENT => 30,
			self::REPAIR => 25,
			self::INSTALLATION => 20,
			self::CONSULTATION => 15,
			self::OTHER => 10,
		};
	}

	/**
	 * Get human-readable label.
	 */
	public function getLabel(): string {
		return match($this) {
			self::REPAIR => 'Naprawa',
			self::INSTALLATION => 'Instalacja',
			self::URGENT => 'Pilne',
			self::CONSULTATION => 'Konsultacja',
			self::OTHER => 'Inne',
		};
	}
}

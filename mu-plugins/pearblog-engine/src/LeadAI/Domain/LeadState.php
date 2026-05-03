<?php
/**
 * LeadState Enum
 *
 * Represents the lifecycle states of a lead in the PT24.PRO system.
 *
 * @package PearBlog\LeadAI\Domain
 */

declare(strict_types=1);

namespace PearBlog\LeadAI\Domain;

/**
 * Lead State
 *
 * Tracks the progression of a lead through the system.
 */
enum LeadState: string {
	case NEW = 'NEW';
	case WAITING_FOR_RESPONSE = 'WAITING_FOR_RESPONSE';
	case AI_REPLIED = 'AI_REPLIED';
	case ESCALATED = 'ESCALATED';
	case REDISTRIBUTED = 'REDISTRIBUTED';
	case CLOSED = 'CLOSED';

	/**
	 * Check if lead is in an active state.
	 */
	public function isActive(): bool {
		return match($this) {
			self::NEW, self::WAITING_FOR_RESPONSE, self::AI_REPLIED, self::ESCALATED => true,
			self::REDISTRIBUTED, self::CLOSED => false,
		};
	}

	/**
	 * Check if lead requires attention.
	 */
	public function requiresAttention(): bool {
		return match($this) {
			self::NEW, self::ESCALATED => true,
			default => false,
		};
	}
}

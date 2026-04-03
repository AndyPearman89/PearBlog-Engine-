<?php
/**
 * Site profile – describes a tenant's content and monetisation behaviour.
 *
 * @package PearBlogEngine\Tenant
 */

declare(strict_types=1);

namespace PearBlogEngine\Tenant;

/**
 * Value object that drives prompt building, SEO and monetisation strategies.
 */
class SiteProfile {

	/**
	 * @param string $industry      Target industry / niche (e.g. "technology", "health").
	 * @param string $tone          Writing tone (e.g. "professional", "conversational").
	 * @param string $monetization  Strategy: "adsense" | "affiliate" | "saas".
	 * @param int    $publish_rate  Articles to publish per cron cycle.
	 * @param string $language      ISO 639-1 language code (e.g. "en", "pl").
	 */
	public function __construct(
		public readonly string $industry,
		public readonly string $tone,
		public readonly string $monetization,
		public readonly int    $publish_rate,
		public readonly string $language,
	) {}

	/**
	 * Return a human-readable summary (useful for debugging / admin UI).
	 */
	public function summary(): string {
		return sprintf(
			'Industry: %s | Tone: %s | Monetization: %s | Publish rate: %d/cycle | Language: %s',
			$this->industry,
			$this->tone,
			$this->monetization,
			$this->publish_rate,
			$this->language,
		);
	}
}

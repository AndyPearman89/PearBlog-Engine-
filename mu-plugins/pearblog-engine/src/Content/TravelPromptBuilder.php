<?php
/**
 * Travel-specific prompt builder with structured sections for travel content.
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

use PearBlogEngine\Tenant\SiteProfile;

/**
 * Builds structured travel content prompts with mandatory sections.
 */
class TravelPromptBuilder extends PromptBuilder {

	/**
	 * Build a travel-optimized prompt with structured sections.
	 *
	 * @param string $topic The article topic / keyword.
	 * @return string       Ready-to-use prompt text.
	 */
	public function build( string $topic ): string {
		$topic   = trim( $topic );
		$profile = $this->profile;

		$prompt = $this->build_system_role( $profile );
		$prompt .= $this->build_content_requirements( $topic, $profile );
		$prompt .= $this->build_structure_requirements();
		$prompt .= $this->build_travel_specific_sections();
		$prompt .= $this->build_monetization_section();
		$prompt .= $this->build_seo_rules();
		$prompt .= $this->build_quality_rules();

		/**
		 * Filter: pearblog_travel_prompt
		 *
		 * Allows customization of travel-specific prompts.
		 *
		 * @param string      $prompt  The assembled prompt text.
		 * @param string      $topic   The article topic.
		 * @param SiteProfile $profile The active site profile.
		 */
		$prompt = (string) apply_filters( 'pearblog_travel_prompt', $prompt, $topic, $profile );

		// Also apply the generic prompt filter for compatibility.
		return (string) apply_filters( 'pearblog_prompt', $prompt, $topic, $profile );
	}

	/**
	 * Build system role instructions.
	 */
	protected function build_system_role( SiteProfile $profile ): string {
		$role  = "SYSTEM ROLE:\n";
		$role .= "You are an advanced AI SEO content engine for travel and lifestyle content.\n";
		$role .= "You generate high-quality, human-like content about {$profile->industry}.\n\n";
		$role .= "MISSION:\n";
		$role .= "Create content that:\n";
		$role .= "- ranks in Google (SEO-first)\n";
		$role .= "- provides real value to readers\n";
		$role .= "- drives clicks to accommodation/travel offers\n";
		$role .= "- increases time on site\n\n";
		return $role;
	}

	/**
	 * Build content requirements.
	 */
	protected function build_content_requirements( string $topic, SiteProfile $profile ): string {
		$req  = "TOPIC: {$topic}\n";
		$req .= "LANGUAGE: {$profile->language}\n";
		$req .= "TONE: {$profile->tone}\n\n";
		$req .= "TARGET AUDIENCE:\n";
		$req .= "- tourists and travelers\n";
		$req .= "- beginners (first trip)\n";
		$req .= "- families with children\n";
		$req .= "- weekend travelers\n\n";
		return $req;
	}

	/**
	 * Build mandatory HTML structure requirements.
	 */
	protected function build_structure_requirements(): string {
		$struct  = "MANDATORY STRUCTURE:\n\n";
		$struct .= "<h1>Main keyword</h1>\n\n";
		$struct .= "<p>Hook (solve user intent in 2-3 sentences)</p>\n\n";
		$struct .= "<h2>TL;DR</h2>\n";
		$struct .= "<ul>\n";
		$struct .= "<li>⏱ czas (time required)</li>\n";
		$struct .= "<li>📈 trudność (difficulty level)</li>\n";
		$struct .= "<li>👨‍👩‍👧 dla kogo (best for)</li>\n";
		$struct .= "<li>📍 lokalizacja (location)</li>\n";
		$struct .= "</ul>\n\n";
		return $struct;
	}

	/**
	 * Build travel-specific section requirements.
	 */
	protected function build_travel_specific_sections(): string {
		$sections  = "REQUIRED SECTIONS:\n\n";
		$sections .= "<h2>Dlaczego warto?</h2>\n";
		$sections .= "(Why it's worth visiting - compelling reasons)\n\n";
		$sections .= "<h2>Opis i szczegóły</h2>\n";
		$sections .= "(Description and details)\n\n";
		$sections .= "<h2>Jak dojechać i parking</h2>\n";
		$sections .= "(How to get there, parking information)\n\n";
		$sections .= "<h2>Warunki i pogoda</h2>\n";
		$sections .= "(Weather conditions, best season to visit)\n\n";
		return $sections;
	}

	/**
	 * Build monetization section (accommodation).
	 */
	protected function build_monetization_section(): string {
		$mon  = "<h2>Noclegi w okolicy</h2>\n";
		$mon .= "MONETIZATION BLOCK:\n";
		$mon .= "- Suggest accommodation types naturally\n";
		$mon .= "- Recommend specific areas for staying\n";
		$mon .= "- Use soft CTA: 'Sprawdź dostępne noclegi w okolicy'\n";
		$mon .= "- No spam, natural recommendations only\n\n";
		return $mon;
	}

	/**
	 * Build SEO optimization rules.
	 */
	protected function build_seo_rules(): string {
		$seo  = "SEO RULES:\n";
		$seo .= "- Include meta description (max 160 characters) at the top, prefixed with META:\n";
		$seo .= "- Use keyword naturally throughout content\n";
		$seo .= "- Include keyword variations\n";
		$seo .= "- Optimize all headings (H1, H2, H3)\n";
		$seo .= "- No keyword stuffing\n\n";
		$seo .= "<h2>Praktyczne wskazówki</h2>\n";
		$seo .= "(Practical tips section)\n\n";
		$seo .= "<h2>FAQ</h2>\n";
		$seo .= "(Frequently asked questions - 3-5 questions)\n\n";
		$seo .= "<h2>Zobacz też</h2>\n";
		$seo .= "(Related content - internal linking section)\n\n";
		return $seo;
	}

	/**
	 * Build quality control rules.
	 */
	protected function build_quality_rules(): string {
		$quality  = "CONTENT QUALITY RULES:\n";
		$quality .= "- NO fluff or generic AI phrases\n";
		$quality .= "- Specific and useful information only\n";
		$quality .= "- Include real practical tips (parking, time, difficulty)\n";
		$quality .= "- Natural, helpful tone (not robotic)\n";
		$quality .= "- Short paragraphs for readability\n";
		$quality .= "- Minimum 1,000 words\n\n";
		$quality .= "OUTPUT FORMAT:\n";
		$quality .= "- HTML only\n";
		$quality .= "- Clean structure (h1, h2, h3, p, ul, li)\n";
		$quality .= "- Ready to publish\n";
		return $quality;
	}
}

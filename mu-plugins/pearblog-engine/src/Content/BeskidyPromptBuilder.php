<?php
/**
 * Beskidy-specific prompt builder with weather awareness and day planning.
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

use PearBlogEngine\Tenant\SiteProfile;

/**
 * Enhanced prompt builder for Beskidy mountains travel content.
 * Includes weather awareness, day planner, and Plan B alternatives.
 */
class BeskidyPromptBuilder extends TravelPromptBuilder {

	/**
	 * Build Beskidy-specific prompt with enhanced features.
	 *
	 * @param string $topic The article topic / keyword.
	 * @return string       Ready-to-use prompt text.
	 */
	public function build( string $topic ): string {
		$topic   = trim( $topic );
		$profile = $this->profile;

		$prompt = $this->build_beskidy_system_role( $profile );
		$prompt .= $this->build_content_requirements( $topic, $profile );
		$prompt .= $this->build_beskidy_structure();
		$prompt .= $this->build_weather_aware_section();
		$prompt .= $this->build_day_planner_section();
		$prompt .= $this->build_monetization_section();
		$prompt .= $this->build_seo_rules();
		$prompt .= $this->build_quality_rules();

		/**
		 * Filter: pearblog_beskidy_prompt
		 *
		 * Allows customization of Beskidy-specific prompts.
		 *
		 * @param string      $prompt  The assembled prompt text.
		 * @param string      $topic   The article topic.
		 * @param SiteProfile $profile The active site profile.
		 */
		$prompt = (string) apply_filters( 'pearblog_beskidy_prompt', $prompt, $topic, $profile );

		// Also apply travel and generic filters for compatibility.
		$prompt = (string) apply_filters( 'pearblog_travel_prompt', $prompt, $topic, $profile );
		return (string) apply_filters( 'pearblog_prompt', $prompt, $topic, $profile );
	}

	/**
	 * Build Beskidy-specific system role.
	 */
	protected function build_beskidy_system_role( SiteProfile $profile ): string {
		$role  = "SYSTEM ROLE:\n";
		$role .= "You are an advanced AI SEO content engine for po.beskidzku.pl.\n";
		$role .= "You generate high-quality, human-like content about the Beskidy mountains in Poland.\n\n";
		$role .= "MISSION:\n";
		$role .= "Create content that:\n";
		$role .= "- ranks in Google (SEO-first)\n";
		$role .= "- solves user intent immediately\n";
		$role .= "- provides real value to Beskidy visitors\n";
		$role .= "- drives affiliate conversions (accommodation, travel)\n";
		$role .= "- increases time on page\n\n";
		$role .= "CONTEXT:\n";
		$role .= "Region: Beskidy (Poland)\n";
		$role .= "Topics: mountains, hiking, travel, accommodation, local tips\n\n";
		$role .= "USER INTENT TYPES:\n";
		$role .= "1. Informational → 'co robić' (what to do)\n";
		$role .= "2. Navigational → 'gdzie iść' (where to go)\n";
		$role .= "3. Transactional → 'noclegi' (accommodation)\n";
		$role .= "You MUST adapt content to intent.\n\n";
		return $role;
	}

	/**
	 * Build Beskidy-specific structure with all mandatory sections.
	 */
	protected function build_beskidy_structure(): string {
		$struct  = "MANDATORY STRUCTURE:\n\n";
		$struct .= "<h1>Main keyword</h1>\n\n";
		$struct .= "<p>Hook (solve user intent in 2-3 sentences)</p>\n\n";
		$struct .= "<h2>TL;DR</h2>\n";
		$struct .= "<ul>\n";
		$struct .= "<li>⏱ czas</li>\n";
		$struct .= "<li>📈 trudność</li>\n";
		$struct .= "<li>👨‍👩‍👧 dla kogo</li>\n";
		$struct .= "<li>📍 lokalizacja</li>\n";
		$struct .= "</ul>\n\n";
		$struct .= "<h2>Dlaczego warto?</h2>\n\n";
		$struct .= "<h2>Opis / szczegóły</h2>\n\n";
		$struct .= "<h2>Mapa / trasa / jak dojechać</h2>\n";
		$struct .= "(Include parking information, GPS coordinates if relevant)\n\n";
		return $struct;
	}

	/**
	 * Build weather-aware content section.
	 */
	protected function build_weather_aware_section(): string {
		$weather  = "<h2>Warunki i pogoda</h2>\n";
		$weather .= "WEATHER-AWARE INSTRUCTIONS:\n";
		$weather .= "- Mention best season to visit\n";
		$weather .= "- Describe typical weather conditions\n";
		$weather .= "- Suggest what to bring based on weather\n";
		$weather .= "- Warn about potential weather hazards\n";
		$weather .= "- Recommend checking weather forecast before trip\n\n";
		return $weather;
	}

	/**
	 * Build AI day planner section.
	 */
	protected function build_day_planner_section(): string {
		$planner  = "<h2>Plan dnia</h2>\n";
		$planner .= "AI PLANNER BLOCK:\n";
		$planner .= "Generate a simple day plan:\n";
		$planner .= "- Rano (morning): Start time and morning activities\n";
		$planner .= "- W południe (midday): Lunch and midday activities\n";
		$planner .= "- Popołudnie/wieczór (afternoon/evening): Evening activities\n\n";
		$planner .= "<h3>Plan B (alternatywa)</h3>\n";
		$planner .= "PLAN B INSTRUCTIONS:\n";
		$planner .= "- Suggest alternative activities if weather is bad\n";
		$planner .= "- Include indoor options\n";
		$planner .= "- Mention nearby attractions as backup\n";
		$planner .= "- Keep it practical and actionable\n\n";
		return $planner;
	}

	/**
	 * Override monetization section for Beskidy-specific approach.
	 */
	protected function build_monetization_section(): string {
		$mon  = "<h2>Noclegi w okolicy</h2>\n";
		$mon .= "MONETIZATION RULES:\n";
		$mon .= "- ALWAYS include accommodation section\n";
		$mon .= "- Suggest places naturally based on the location\n";
		$mon .= "- Recommend types: hotels, pensions, mountain huts, glamping\n";
		$mon .= "- Mention specific towns/villages for accommodation\n";
		$mon .= "- Encourage checking availability\n";
		$mon .= "- Use soft CTA: 'Sprawdź dostępne noclegi w okolicy'\n";
		$mon .= "- Focus on intent (sleep, plan trip)\n";
		$mon .= "- No spam or aggressive selling\n\n";
		return $mon;
	}

	/**
	 * Override SEO rules to include internal linking specifics.
	 */
	protected function build_seo_rules(): string {
		$seo  = parent::build_seo_rules();
		$seo .= "INTERNAL LINKING RULES:\n";
		$seo .= "In the 'Zobacz też' section, include 3-5 contextual references:\n";
		$seo .= "- Similar hiking trails in Beskidy\n";
		$seo .= "- Nearby attractions and peaks\n";
		$seo .= "- Related travel guides\n";
		$seo .= "- Other seasonal activities\n\n";
		return $seo;
	}

	/**
	 * Override quality rules with Beskidy-specific standards.
	 */
	protected function build_quality_rules(): string {
		$quality  = "CONTENT STYLE:\n";
		$quality .= "- Short paragraphs (2-4 sentences)\n";
		$quality .= "- Dynamic, varied sentence structure\n";
		$quality .= "- NO fluff or generic phrases\n";
		$quality .= "- Practical and specific information\n";
		$quality .= "- Expert + friendly tone (Polish native-level)\n";
		$quality .= "- Natural language (not robotic AI)\n\n";
		$quality .= "QUALITY CONTROL:\n";
		$quality .= "Reject output if:\n";
		$quality .= "- Generic or repetitive\n";
		$quality .= "- Lacks practical info (parking, time, difficulty)\n";
		$quality .= "- Missing monetization section\n";
		$quality .= "- Contains AI clichés\n\n";
		$quality .= "OUTPUT FORMAT:\n";
		$quality .= "- HTML only\n";
		$quality .= "- Clean structure (h1, h2, h3, p, ul, li)\n";
		$quality .= "- Ready to publish\n";
		$quality .= "- Minimum 1,200 words for comprehensive guides\n\n";
		$quality .= "GOAL:\n";
		$quality .= "Create content that:\n";
		$quality .= "→ ranks in Google\n";
		$quality .= "→ helps the user\n";
		$quality .= "→ converts (affiliate revenue)\n";
		return $quality;
	}
}

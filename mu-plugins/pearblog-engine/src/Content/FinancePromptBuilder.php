<?php
/**
 * Finance prompt builder – optimised for personal finance, investing, and fintech content.
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

use PearBlogEngine\Tenant\SiteProfile;

/**
 * Builds AI prompts for personal finance / investing / fintech niches.
 */
class FinancePromptBuilder extends PromptBuilder {

	public function __construct( SiteProfile $profile ) {
		parent::__construct( $profile );
	}

	/**
	 * Build a finance-focused prompt.
	 *
	 * @param string $topic The article topic / keyword.
	 * @return string       Ready-to-use prompt text.
	 */
	public function build( string $topic ): string {
		$topic   = trim( $topic );
		$profile = $this->get_profile();

		$prompt  = "You are an experienced personal finance writer and certified financial educator specialising in {$profile->industry}.\n";
		$prompt .= "Write an authoritative, SEO-optimised article in {$profile->language} ";
		$prompt .= "using a {$profile->tone} tone.\n\n";
		$prompt .= "Topic: {$topic}\n\n";
		$prompt .= "Requirements:\n";
		$prompt .= "- Minimum 1 400 words\n";
		$prompt .= "- Include a compelling H1 title\n";
		$prompt .= "- Add a meta description (max 160 chars) at the top prefixed with META:\n";
		$prompt .= "- Use H2 sections: Introduction / Overview, How It Works, Key Benefits, Risks & Considerations, Step-by-Step Guide, Expert Tips, FAQ\n";
		$prompt .= "- Include relevant statistics, historical returns, or data points (with sources)\n";
		$prompt .= "- Add a financial disclaimer (not investment advice) near the top\n";
		$prompt .= "- Explain complex terms in plain language\n";
		$prompt .= "- Target audience: beginners to intermediate investors / savers\n";
		$prompt .= "- End with actionable next steps and a clear call-to-action\n\n";
		$prompt .= $this->monetisation_instructions();

		return (string) apply_filters( 'pearblog_prompt', $prompt, $topic, $profile );
	}
}

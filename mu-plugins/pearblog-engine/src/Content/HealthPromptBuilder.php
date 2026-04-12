<?php
/**
 * Health prompt builder – optimised for health, wellness, and medical content.
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

use PearBlogEngine\Tenant\SiteProfile;

/**
 * Builds AI prompts for health / wellness / fitness niches.
 */
class HealthPromptBuilder extends PromptBuilder {

	public function __construct( SiteProfile $profile ) {
		parent::__construct( $profile );
	}

	/**
	 * Build a health-focused prompt.
	 *
	 * @param string $topic The article topic / keyword.
	 * @return string       Ready-to-use prompt text.
	 */
	public function build( string $topic ): string {
		$topic   = trim( $topic );
		$profile = $this->get_profile();

		$prompt  = "You are a certified health and wellness writer specialising in {$profile->industry}.\n";
		$prompt .= "Write an evidence-based, SEO-optimised article in {$profile->language} ";
		$prompt .= "using a {$profile->tone} tone.\n\n";
		$prompt .= "Topic: {$topic}\n\n";
		$prompt .= "Requirements:\n";
		$prompt .= "- Minimum 1 300 words\n";
		$prompt .= "- Include a compelling H1 title\n";
		$prompt .= "- Add a meta description (max 160 chars) at the top prefixed with META:\n";
		$prompt .= "- Use H2 sections: What Is It, Health Benefits, Scientific Evidence, How To, Potential Risks & Side Effects, Expert Tips, FAQ\n";
		$prompt .= "- Cite scientific studies or health organisations (WHO, Mayo Clinic, NIH) where appropriate\n";
		$prompt .= "- Include a disclaimer reminding readers to consult a healthcare professional\n";
		$prompt .= "- Avoid making unsubstantiated medical claims\n";
		$prompt .= "- End with an encouraging call-to-action\n\n";
		$prompt .= $this->monetisation_instructions();

		return (string) apply_filters( 'pearblog_prompt', $prompt, $topic, $profile );
	}
}

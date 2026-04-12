<?php
/**
 * Ecommerce prompt builder – optimised for product review and shopping content.
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

use PearBlogEngine\Tenant\SiteProfile;

/**
 * Builds AI prompts specifically for e-commerce / product-review niches.
 */
class EcommercePromptBuilder extends PromptBuilder {

	public function __construct( SiteProfile $profile ) {
		parent::__construct( $profile );
	}

	/**
	 * Build an e-commerce focused prompt.
	 *
	 * @param string $topic The article topic / keyword.
	 * @return string       Ready-to-use prompt text.
	 */
	public function build( string $topic ): string {
		$topic   = trim( $topic );
		$profile = $this->get_profile();

		$prompt  = "You are an expert e-commerce content writer specialising in {$profile->industry}.\n";
		$prompt .= "Write a comprehensive, SEO-optimised product review or buying-guide article in {$profile->language} ";
		$prompt .= "using a {$profile->tone} tone.\n\n";
		$prompt .= "Topic: {$topic}\n\n";
		$prompt .= "Requirements:\n";
		$prompt .= "- Minimum 1 200 words\n";
		$prompt .= "- Include a compelling H1 title with the primary keyword\n";
		$prompt .= "- Add a meta description (max 160 chars) at the top prefixed with META:\n";
		$prompt .= "- Use H2 sections: Overview, Key Features, Pros & Cons, Who Should Buy, Top Picks / Alternatives, FAQ\n";
		$prompt .= "- Include a comparison table if reviewing multiple products\n";
		$prompt .= "- Add a star-rating summary (1-5 ★) for each reviewed product\n";
		$prompt .= "- Naturally integrate buyer-intent keywords (best, review, buy, cheap, top)\n";
		$prompt .= "- End with a clear recommendation and call-to-action\n\n";
		$prompt .= $this->monetisation_instructions();

		return (string) apply_filters( 'pearblog_prompt', $prompt, $topic, $profile );
	}
}

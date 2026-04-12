<?php
/**
 * Tech prompt builder – optimised for software, gadgets, and developer content.
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

use PearBlogEngine\Tenant\SiteProfile;

/**
 * Builds AI prompts for technology / software / developer niches.
 */
class TechPromptBuilder extends PromptBuilder {

	public function __construct( SiteProfile $profile ) {
		parent::__construct( $profile );
	}

	/**
	 * Build a tech-focused prompt.
	 *
	 * @param string $topic The article topic / keyword.
	 * @return string       Ready-to-use prompt text.
	 */
	public function build( string $topic ): string {
		$topic   = trim( $topic );
		$profile = $this->get_profile();

		$prompt  = "You are a senior technology journalist and software engineer specialising in {$profile->industry}.\n";
		$prompt .= "Write a technically accurate, SEO-optimised article in {$profile->language} ";
		$prompt .= "using a {$profile->tone} tone.\n\n";
		$prompt .= "Topic: {$topic}\n\n";
		$prompt .= "Requirements:\n";
		$prompt .= "- Minimum 1 500 words\n";
		$prompt .= "- Include a compelling H1 title\n";
		$prompt .= "- Add a meta description (max 160 chars) at the top prefixed with META:\n";
		$prompt .= "- Use H2 sections: Introduction, How It Works, Key Features / Benefits, Step-by-Step Guide (if applicable), Common Pitfalls, Best Practices, FAQ\n";
		$prompt .= "- Include at least one code snippet or command-line example wrapped in ```language blocks (if relevant)\n";
		$prompt .= "- Cite version numbers, release dates, or benchmark figures where applicable\n";
		$prompt .= "- Target both beginners seeking explanation and advanced users seeking depth\n";
		$prompt .= "- End with a concise summary and next-steps call-to-action\n\n";
		$prompt .= $this->monetisation_instructions();

		return (string) apply_filters( 'pearblog_prompt', $prompt, $topic, $profile );
	}
}

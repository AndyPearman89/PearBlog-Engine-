<?php
/**
 * Prompt builder – assembles a dynamic AI prompt from a topic and site profile.
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

use PearBlogEngine\Tenant\SiteProfile;

/**
 * Builds structured prompts for the AI content generation step.
 */
class PromptBuilder {

	/** @var SiteProfile */
	private SiteProfile $profile;

	public function __construct( SiteProfile $profile ) {
		$this->profile = $profile;
	}

	/**
	 * Build a complete prompt string for the given topic.
	 *
	 * The prompt instructs the AI model to write a full SEO-optimised article
	 * that matches the tenant's industry, tone and language requirements.
	 *
	 * @param string $topic The article topic / keyword.
	 * @return string       Ready-to-use prompt text.
	 */
	public function build( string $topic ): string {
		$topic   = trim( $topic );
		$profile = $this->profile;

		$prompt  = "You are an expert content writer specialising in {$profile->industry}.\n";
		$prompt .= "Write a comprehensive, SEO-optimised article in {$profile->language} ";
		$prompt .= "using a {$profile->tone} tone.\n\n";
		$prompt .= "Topic: {$topic}\n\n";
		$prompt .= "Requirements:\n";
		$prompt .= "- Minimum 1 000 words\n";
		$prompt .= "- Include a compelling H1 title\n";
		$prompt .= "- Use H2 and H3 subheadings for structure\n";
		$prompt .= "- Include a meta description (max 160 characters) at the top, prefixed with META:\n";
		$prompt .= "- Naturally integrate the primary keyword and related LSI terms\n";
		$prompt .= "- End with a clear call-to-action paragraph\n\n";

		// Monetisation-specific instructions.
		$prompt .= $this->monetisation_instructions();

		/**
		 * Filter: pearblog_prompt
		 *
		 * Allows themes and plugins to customise the assembled prompt before it
		 * is sent to the AI client.
		 *
		 * @param string      $prompt  The assembled prompt text.
		 * @param string      $topic   The article topic.
		 * @param SiteProfile $profile The active site profile.
		 */
		return (string) apply_filters( 'pearblog_prompt', $prompt, $topic, $profile );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	private function monetisation_instructions(): string {
		return match ( $this->profile->monetization ) {
			'affiliate' => "- Naturally recommend relevant products or services with a brief review sentence.\n",
			'saas'      => "- Highlight software solutions that solve the reader's problem.\n",
			default     => '', // adsense – no extra instructions needed.
		};
	}
}

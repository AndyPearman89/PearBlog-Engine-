<?php
/**
 * Food prompt builder – optimised for recipes, restaurants, and food-lifestyle content.
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

use PearBlogEngine\Tenant\SiteProfile;

/**
 * Builds AI prompts for food / recipe / culinary niches.
 */
class FoodPromptBuilder extends PromptBuilder {

	public function __construct( SiteProfile $profile ) {
		parent::__construct( $profile );
	}

	/**
	 * Build a food-focused prompt.
	 *
	 * @param string $topic The article topic / keyword.
	 * @return string       Ready-to-use prompt text.
	 */
	public function build( string $topic ): string {
		$topic   = trim( $topic );
		$profile = $this->get_profile();

		$prompt  = "You are a passionate food writer and recipe developer specialising in {$profile->industry}.\n";
		$prompt .= "Write an engaging, SEO-optimised food article or recipe post in {$profile->language} ";
		$prompt .= "using a {$profile->tone} tone.\n\n";
		$prompt .= "Topic: {$topic}\n\n";
		$prompt .= "Requirements:\n";
		$prompt .= "- Minimum 1 000 words\n";
		$prompt .= "- Include a compelling H1 title with the primary keyword\n";
		$prompt .= "- Add a meta description (max 160 chars) at the top prefixed with META:\n";
		$prompt .= "- If this is a recipe article, include:\n";
		$prompt .= "  * Prep time, cook time, total time, servings\n";
		$prompt .= "  * Ingredients list with precise measurements\n";
		$prompt .= "  * Step-by-step instructions numbered 1, 2, 3…\n";
		$prompt .= "  * Nutritional information (calories, protein, carbs, fat) per serving\n";
		$prompt .= "  * Storage instructions and make-ahead tips\n";
		$prompt .= "- If this is a listicle or food guide, use H2/H3 sections for each item\n";
		$prompt .= "- Include a story / inspiration paragraph before the recipe to boost engagement\n";
		$prompt .= "- Add substitution suggestions for dietary restrictions (vegan, gluten-free)\n";
		$prompt .= "- End with a serving suggestion and call-to-action (try this recipe!)\n\n";
		$prompt .= $this->monetisation_instructions();

		return (string) apply_filters( 'pearblog_prompt', $prompt, $topic, $profile );
	}
}

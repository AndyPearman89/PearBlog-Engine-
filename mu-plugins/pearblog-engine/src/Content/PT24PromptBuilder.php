<?php
/**
 * PT24 Prompt Builder - PT24-aware content generation with automatic CTAs and links.
 *
 * Extends PoradnikPromptBuilder with PT24 marketplace integration features.
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

use PearBlogEngine\Tenant\SiteProfile;
use PearBlogEngine\Integration\CTAInjector;
use PearBlogEngine\Integration\ContentLinker;

/**
 * PT24-aware prompt builder for content that bridges to the lead marketplace.
 */
class PT24PromptBuilder extends PoradnikPromptBuilder {

	/**
	 * @var CTAInjector CTA component injector
	 */
	private $cta_injector;

	/**
	 * @var ContentLinker Internal linking engine
	 */
	private $content_linker;

	/**
	 * Constructor
	 *
	 * @param SiteProfile $profile The site profile.
	 */
	public function __construct( SiteProfile $profile ) {
		parent::__construct( $profile );
		$this->cta_injector   = new CTAInjector();
		$this->content_linker = new ContentLinker();
	}

	/**
	 * Build PT24-integrated content prompt.
	 *
	 * @param string $topic The article topic.
	 * @return string       Complete prompt text with PT24 integration instructions.
	 */
	public function build( string $topic ): string {
		// Get base Poradnik.pro prompt.
		$prompt = parent::build( $topic );

		// Add PT24-specific instructions.
		$prompt .= "\n\n===== PT24 INTEGRATION =====\n\n";
		$prompt .= "This content bridges to PT24 marketplace (local services directory).\n\n";

		$prompt .= "PT24 LINK PLACEMENT:\n";
		$prompt .= "- Place 2-3 strategic internal links to PT24 resources\n";
		$prompt .= "- Link types: category pages, city pages, top listings\n";
		$prompt .= "- Example: \"Sprawdź najlepszych [service] w [city]\" → PT24 landing\n";
		$prompt .= "- Links should be natural and contextual (not forced)\n\n";

		$prompt .= "LINK STRATEGIES:\n";
		$prompt .= "1. Category link: \"Znajdź [service] w Twojej okolicy\"\n";
		$prompt .= "2. City link: \"Zobacz sprawdzonych [service] w [city]\"\n";
		$prompt .= "3. Listing link: \"Porównaj oferty lokalnych specjalistów\"\n\n";

		$prompt .= "CTA INTEGRATION:\n";
		$prompt .= "- One soft CTA in \"Jak wybrać\" section\n";
		$prompt .= "- Natural language: \"Jeśli szukasz...\"\n";
		$prompt .= "- Link to PT24 landing with anchor text placeholder: [sprawdź opcje]\n";
		$prompt .= "- No aggressive sales language\n\n";

		$prompt .= "CONVERSION FUNNEL:\n";
		$prompt .= "Content → Trust → Soft CTA → PT24 Landing → Lead → Revenue\n\n";

		$prompt .= "Remember: This is trust-building content with natural marketplace integration.\n";
		$prompt .= "Focus on value first, conversion second.\n";

		/**
		 * Filter: pearblog_pt24_prompt
		 *
		 * Allows customization of PT24-integrated prompt.
		 *
		 * @param string      $prompt  The assembled prompt text.
		 * @param string      $topic   The article topic.
		 * @param SiteProfile $profile The active site profile.
		 */
		return (string) apply_filters( 'pearblog_pt24_prompt', $prompt, $topic, $profile );
	}

	/**
	 * Build content with PT24 integration (post-processing).
	 *
	 * This method is called after AI generation to inject CTAs and links.
	 *
	 * @param string $content    Generated content.
	 * @param array  $params     Parameters (service, city, category_id, etc.).
	 * @return string            Content with PT24 CTAs and links injected.
	 */
	public function build_with_pt24_integration( string $content, array $params ): string {
		// Step 1: Inject PT24 CTAs at strategic positions.
		$content = $this->inject_ctas( $content, $params );

		// Step 2: Add internal links to PT24 resources.
		$content = $this->add_pt24_links( $content, $params );

		// Step 3: Add "Porady eksperta" section (if enabled).
		if ( ! empty( $params['add_expert_section'] ) ) {
			$content .= $this->build_expert_tips_section( $params );
		}

		return $content;
	}

	/**
	 * Inject CTAs into content at strategic positions.
	 *
	 * @param string $content Generated content.
	 * @param array  $params  Parameters.
	 * @return string         Content with CTAs injected.
	 */
	private function inject_ctas( string $content, array $params ): string {
		if ( empty( $params['service'] ) || empty( $params['city'] ) ) {
			return $content;
		}

		$service = $params['service'];
		$city    = $params['city'];
		$url     = $params['landing_url'] ?? $this->build_landing_url( $service, $city );

		// Split content into sections (by H2 tags).
		$sections = preg_split( '/(<h2[^>]*>.*?<\/h2>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE );

		if ( count( $sections ) < 3 ) {
			// If no H2 sections found, inject after first 2 paragraphs.
			return $this->inject_after_paragraphs( $content, $service, $city, $url, 2 );
		}

		$output = '';

		foreach ( $sections as $index => $section ) {
			$output .= $section;

			// Inject inline CTA after "Ile kosztuje" or "Jak wybrać" section.
			if ( preg_match( '/<h2[^>]*>(Ile kosztuje|Jak wybrać)/i', $section ) && $index < count( $sections ) - 2 ) {
				$output .= $this->cta_injector->get_inline_cta( $service, $city, $url );
			}
		}

		// Add compact CTA before conclusion.
		$output = $this->inject_before_conclusion( $output, $service, $city, $url );

		// Add sticky CTA if enabled.
		if ( ! empty( $params['phone'] ) && get_option( 'pearblog_pt24_cta_sticky', false ) ) {
			$output .= $this->cta_injector->get_sticky_cta( $params['phone'] );
		}

		return $output;
	}

	/**
	 * Add PT24 internal links to content.
	 *
	 * @param string $content Generated content.
	 * @param array  $params  Parameters.
	 * @return string         Content with links added.
	 */
	private function add_pt24_links( string $content, array $params ): string {
		if ( empty( $params['category_id'] ) ) {
			return $content;
		}

		$category_id = $params['category_id'];
		$city_id     = $params['city_id'] ?? null;

		// Build link strategies.
		$links = [];

		// Strategy 1: Category link.
		$links[] = [
			'type'   => 'category',
			'url'    => "https://pt24.pro/{$category_id}/",
			'text'   => "Znajdź {$params['service']} w Twojej okolicy",
			'anchor' => '[sprawdź opcje w Twojej okolicy]',
		];

		// Strategy 2: City link (if city provided).
		if ( $city_id ) {
			$links[] = [
				'type'   => 'city',
				'url'    => "https://pt24.pro/{$city_id}/{$category_id}/",
				'text'   => "Zobacz sprawdzonych {$params['service']} w {$params['city']}",
				'anchor' => '[zobacz sprawdzonych specjalistów]',
			];
		}

		// Strategy 3: Listings link.
		$links[] = [
			'type'   => 'listings',
			'url'    => "https://pt24.pro/{$category_id}/" . ( $city_id ? "{$city_id}/" : '' ),
			'text'   => 'Porównaj oferty lokalnych specjalistów',
			'anchor' => '[porównaj oferty]',
		];

		// Replace link placeholders in content.
		foreach ( $links as $link ) {
			$link_html = sprintf(
				'<a href="%s" class="pearblog-pt24-link" data-link-type="%s">%s</a>',
				esc_url( $link['url'] ),
				esc_attr( $link['type'] ),
				esc_html( $link['text'] )
			);

			// Replace anchor placeholder with actual link.
			$content = str_replace( $link['anchor'], $link_html, $content );
		}

		return $content;
	}

	/**
	 * Build "Porady eksperta" section with related content.
	 *
	 * @param array $params Parameters.
	 * @return string       HTML for expert tips section.
	 */
	private function build_expert_tips_section( array $params ): string {
		$service     = $params['service'] ?? 'ten temat';
		$category_id = $params['category_id'] ?? null;

		$section  = "\n\n<section class=\"pearblog-expert-tips\">\n";
		$section .= "<h2>📚 Porady eksperta</h2>\n";
		$section .= "<p>Dodatkowe informacje i porady na temat: <strong>{$service}</strong></p>\n";

		// This would fetch related articles in production.
		// For now, just add placeholder.
		$section .= "<p><em>W tym miejscu pojawią się powiązane artykuły i porady.</em></p>\n";

		if ( $category_id ) {
			$section .= sprintf(
				'<p><a href="https://pt24.pro/%s/" class="pearblog-pt24-link">Zobacz więcej porad w kategorii %s</a></p>',
				esc_attr( $category_id ),
				esc_html( $service )
			);
		}

		$section .= "</section>\n";

		return $section;
	}

	/**
	 * Inject CTA after N paragraphs.
	 *
	 * @param string $content  Content.
	 * @param string $service  Service name.
	 * @param string $city     City name.
	 * @param string $url      Landing URL.
	 * @param int    $after_n  After N paragraphs.
	 * @return string          Content with CTA injected.
	 */
	private function inject_after_paragraphs( string $content, string $service, string $city, string $url, int $after_n = 2 ): string {
		$paragraphs = preg_split( '/(<p[^>]*>.*?<\/p>)/is', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
		$output     = '';
		$p_count    = 0;
		$injected   = false;

		foreach ( $paragraphs as $paragraph ) {
			$output .= $paragraph;

			if ( preg_match( '/<p[^>]*>/i', $paragraph ) ) {
				$p_count++;

				if ( $p_count === $after_n && ! $injected ) {
					$output  .= $this->cta_injector->get_inline_cta( $service, $city, $url );
					$injected = true;
				}
			}
		}

		return $output;
	}

	/**
	 * Inject compact CTA before conclusion.
	 *
	 * @param string $content Content.
	 * @param string $service Service name.
	 * @param string $city    City name.
	 * @param string $url     Landing URL.
	 * @return string         Content with CTA injected.
	 */
	private function inject_before_conclusion( string $content, string $service, string $city, string $url ): string {
		// Find conclusion (FAQ or last H2).
		if ( preg_match( '/(<h2[^>]*>.*?(FAQ|Podsumowanie|Zakończenie).*?<\/h2>)/i', $content, $matches, PREG_OFFSET_CAPTURE ) ) {
			$position = $matches[0][1];
			$before   = substr( $content, 0, $position );
			$after    = substr( $content, $position );

			$cta_text = "Potrzebujesz {$service} w {$city}? Sprawdź sprawdzonych specjalistów";
			$cta      = $this->cta_injector->get_compact_cta( $cta_text, $url );

			return $before . $cta . $after;
		}

		return $content;
	}

	/**
	 * Build PT24 landing URL.
	 *
	 * @param string $service Service name.
	 * @param string $city    City name.
	 * @return string         Landing URL.
	 */
	private function build_landing_url( string $service, string $city ): string {
		$service_slug = sanitize_title( $service );
		$city_slug    = sanitize_title( $city );

		return "https://pt24.pro/{$city_slug}/{$service_slug}/";
	}
}

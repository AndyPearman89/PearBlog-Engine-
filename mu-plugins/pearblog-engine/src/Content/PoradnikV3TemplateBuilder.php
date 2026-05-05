<?php
/**
 * Poradnik.pro V3 Template Builder - Full Engine Version
 *
 * Advanced template for cost-focused SEO + Lead Generation with:
 * - Smart calculator engine
 * - Live pricing data layer
 * - Conversion optimization
 * - A/B testing support
 * - Data feedback loop
 *
 * This is the production-ready V3 upgrade that transforms articles into
 * conversion-optimized landing pages with real-time data and calculators.
 *
 * @package PearBlogEngine\Content
 * @version 3.0.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

use PearBlogEngine\Tenant\SiteProfile;

/**
 * V3 Template Builder - Full Engine Version
 *
 * Builds comprehensive cost analysis landing pages with:
 * - Hero with conversion-first CTA
 * - Quick answer (featured snippet optimized)
 * - Smart calculator (interactive, multi-input)
 * - Live pricing data (aggregated from leads)
 * - Cost breakdown tables
 * - Mid-page and bottom lead forms
 * - Programmatic local SEO
 * - FAQ with schema
 * - Internal linking
 * - A/B testing support
 */
class PoradnikV3TemplateBuilder extends PromptBuilder {

	/**
	 * Build complete V3 template prompt.
	 *
	 * @param string $topic   The article topic (e.g., "budowa domu").
	 * @param array  $config  Configuration options:
	 *                        - year: int (default: current year)
	 *                        - city: string (optional city name)
	 *                        - service: string (service slug)
	 *                        - min_price: string
	 *                        - max_price: string
	 *                        - unit: string (default: 'zł')
	 *                        - price_per: string (default: 'm²')
	 *                        - calculator_enabled: bool (default: true)
	 *                        - live_pricing_enabled: bool (default: true)
	 *                        - ab_test_variant: string (optional)
	 * @return string Complete prompt text.
	 */
	public function build( string $topic, array $config = [] ): string {
		$topic   = trim( $topic );
		$profile = $this->get_profile();

		// Parse configuration
		$year                  = $config['year'] ?? (int) date( 'Y' );
		$city                  = $config['city'] ?? '';
		$service               = $config['service'] ?? sanitize_title( $topic );
		$min_price             = $config['min_price'] ?? '';
		$max_price             = $config['max_price'] ?? '';
		$unit                  = $config['unit'] ?? 'zł';
		$price_per             = $config['price_per'] ?? 'm²';
		$calculator_enabled    = $config['calculator_enabled'] ?? true;
		$live_pricing_enabled  = $config['live_pricing_enabled'] ?? true;
		$ab_test_variant       = $config['ab_test_variant'] ?? '';

		$prompt = "You are an expert Polish content writer for Poradnik.pro V3 ENGINE, specializing in high-conversion landing pages.\n\n";
		$prompt .= "===== PORADNIK.PRO V3 – FULL ENGINE VERSION =====\n\n";
		$prompt .= "This is NOT a simple blog article. This is a:\n";
		$prompt .= "✓ SEO Landing Page\n";
		$prompt .= "✓ Lead Generation Engine\n";
		$prompt .= "✓ Data Collection Platform\n";
		$prompt .= "✓ Conversion-Optimized Experience\n\n";

		$prompt .= "===== SYSTEM ROLE =====\n\n";
		$prompt .= "Your mission:\n";
		$prompt .= "1. Capture high-intent users (cost searches)\n";
		$prompt .= "2. Build trust with data and transparency\n";
		$prompt .= "3. Convert visitors to leads\n";
		$prompt .= "4. Collect pricing data for optimization\n\n";

		$prompt .= "===== YOUR TASK =====\n\n";
		$prompt .= "Topic: {$topic}\n";
		if ( $city ) {
			$prompt .= "Location: {$city}\n";
		}
		$prompt .= "Year: {$year}\n";
		$prompt .= "Service: {$service}\n";
		if ( $ab_test_variant ) {
			$prompt .= "A/B Test Variant: {$ab_test_variant}\n";
		}
		$prompt .= "\n";

		$prompt .= "Create a conversion-optimized landing page following the V3 structure below.\n\n";

		$prompt .= "===== MANDATORY V3 STRUCTURE =====\n\n";

		// Section 0: URL Structure
		$prompt .= "## SECTION 0: URL & META (CTR OPTIMIZATION)\n\n";
		$prompt .= "URL FORMAT:\n";
		$prompt .= "/ile-kosztuje-{$service}";
		if ( $city ) {
			$prompt .= "-" . sanitize_title( $city );
		}
		$prompt .= "-w-polsce-{$year}\n\n";

		$prompt .= "META TITLE (50-60 chars):\n";
		$prompt .= "\"Ile kosztuje {$topic} {$year}? Ceny + kalkulator\"\n\n";

		$prompt .= "META DESCRIPTION (150-160 chars):\n";
		$prompt .= "\"Sprawdź aktualne koszty {$topic} w {$year}. Ceny za {$price_per}, przykłady i darmowa wycena online.\"\n\n";

		// Section 1: HERO (Conversion-First)
		$prompt .= "## SECTION 1: HERO (CONVERSION-FIRST)\n\n";
		$prompt .= "H1 FORMAT:\n";
		$prompt .= "\"Ile kosztuje {$topic}";
		if ( $city ) {
			$prompt .= " w {$city}";
		} else {
			$prompt .= " w Polsce";
		}
		$prompt .= " w {$year}? [Aktualne ceny + kalkulator]\"\n\n";

		$prompt .= "SUBHEAD (benefit-driven):\n";
		$prompt .= "\"Sprawdź ile kosztuje {$topic} za {$price_per} i otrzymaj realną wycenę od firm w Twojej okolicy.\"\n\n";

		$prompt .= "TRUST BADGES (display as horizontal row):\n";
		$prompt .= "✔ aktualizacja {$year}\n";
		$prompt .= "✔ dane rynkowe\n";
		$prompt .= "✔ realne wyceny\n\n";

		$prompt .= "PRIMARY CTA:\n";
		$prompt .= "Text: \"👉 Oblicz koszt {$topic}\"\n";
		$prompt .= "Style: Large button, primary color\n";
		$prompt .= "Action: Scroll to calculator section\n";
		$prompt .= "Format: [CTA_PRIMARY id=\"calc-hero\"]\n\n";

		$prompt .= "SECONDARY CTA:\n";
		$prompt .= "Text: \"📩 Otrzymaj wycenę\"\n";
		$prompt .= "Style: Outline button, secondary color\n";
		$prompt .= "Action: Open lead form modal\n";
		$prompt .= "Format: [CTA_SECONDARY id=\"lead-hero\"]\n\n";

		// Section 2: Quick Answer
		$prompt .= "## SECTION 2: QUICK ANSWER (AI + SNIPPET OPTIMIZED)\n\n";
		$prompt .= "H2: \"Ile kosztuje {$topic} w {$year}?\"\n\n";
		$prompt .= "Create a featured snippet box with:\n\n";
		$prompt .= "Price tiers (3-4 levels):\n";
		$prompt .= "- stan surowy: 2500–4800 {$unit}/{$price_per}\n";
		$prompt .= "- stan deweloperski: 5000–7500 {$unit}/{$price_per}\n";
		$prompt .= "- pod klucz: 7500–10000+ {$unit}/{$price_per}\n\n";

		$prompt .= "Example calculation:\n";
		$prompt .= "\"Dom 100 m²: 550 000 – 650 000 {$unit}\"\n\n";

		$prompt .= "NOTE below:\n";
		$prompt .= "\"Cena zależy od projektu, lokalizacji i standardu.\"\n\n";

		$prompt .= "Format as callout box with background color.\n\n";

		// Section 3: Smart Calculator
		if ( $calculator_enabled ) {
			$prompt .= "## SECTION 3: SMART CALCULATOR (CORE FEATURE)\n\n";
			$prompt .= "H2: \"Kalkulator kosztów {$topic}\"\n\n";
			$prompt .= "Subhead: \"Oblicz ile zapłacisz za swój projekt – precyzyjnie i szybko\"\n\n";

			$prompt .= "CALCULATOR PLACEHOLDER:\n";
			$prompt .= "[CALCULATOR_ENGINE id=\"{$service}\"]\n\n";

			$prompt .= "Calculator should collect:\n";
			$prompt .= "INPUT FIELDS:\n";
			$prompt .= "1. Metraż (m²) - number input, min: 50, max: 500\n";
			$prompt .= "2. Standard - select: podstawowy / średni / premium\n";
			$prompt .= "3. Lokalizacja - select: miasto / przedmieścia / wieś\n";
			$prompt .= "4. Typ {$topic} - select: (appropriate options for the service)\n\n";

			$prompt .= "OUTPUT:\n";
			$prompt .= "- Koszt minimalny\n";
			$prompt .= "- Koszt maksymalny\n";
			$prompt .= "- Średnia cena\n";
			$prompt .= "- Koszt za {$price_per}\n\n";

			$prompt .= "CTA after calculation:\n";
			$prompt .= "\"Wyślij zapytanie → otrzymaj oferty od firm\"\n";
			$prompt .= "[CTA_CALCULATOR_RESULT]\n\n";
		}

		// Section 4: Live Pricing
		if ( $live_pricing_enabled ) {
			$prompt .= "## SECTION 4: LIVE PRICING (DATA LAYER)\n\n";
			$prompt .= "H2: \"Średnie ceny {$topic} – aktualizacja live\"\n\n";

			$prompt .= "Dynamic section showing:\n";
			$prompt .= "[LIVE_PRICING service=\"{$service}\"]\n\n";

			$prompt .= "Display:\n";
			$prompt .= "- Średnia cena/{$price_per}: [DYNAMIC]\n";
			$prompt .= "- Zakres min/max: [DYNAMIC]\n";
			$prompt .= "- Liczba analizowanych wycen: [DYNAMIC]\n\n";

			$prompt .= "SOURCE note:\n";
			$prompt .= "\"Dane aktualizowane na podstawie realnych wycen złożonych przez użytkowników platformy.\"\n\n";
		}

		// Section 5: Cost Breakdown Table
		$prompt .= "## SECTION 5: COST BREAKDOWN (UX TABLE)\n\n";
		$prompt .= "H2: \"Rozbicie kosztów {$topic}\"\n\n";

		$prompt .= "Create HTML table:\n\n";
		$prompt .= "```html\n";
		$prompt .= "<table class=\"cost-breakdown\">\n";
		$prompt .= "  <thead>\n";
		$prompt .= "    <tr>\n";
		$prompt .= "      <th>Etap/Element</th>\n";
		$prompt .= "      <th>Koszt ({$price_per})</th>\n";
		$prompt .= "      <th>Co obejmuje</th>\n";
		$prompt .= "    </tr>\n";
		$prompt .= "  </thead>\n";
		$prompt .= "  <tbody>\n";
		$prompt .= "    <tr>\n";
		$prompt .= "      <td>[Stage name]</td>\n";
		$prompt .= "      <td>[Price range] {$unit}</td>\n";
		$prompt .= "      <td>[Description]</td>\n";
		$prompt .= "    </tr>\n";
		$prompt .= "    <!-- 6-8 rows total -->\n";
		$prompt .= "  </tbody>\n";
		$prompt .= "</table>\n";
		$prompt .= "```\n\n";

		// Section 6: Mid-Page Lead Capture
		$prompt .= "## SECTION 6: LEAD CAPTURE (MID – STRONG)\n\n";
		$prompt .= "Background: Light gradient or colored box\n\n";
		$prompt .= "H2: \"👉 Ile zapłacisz za {$topic}?\"\n\n";

		$prompt .= "Short paragraph:\n";
		$prompt .= "\"Każdy projekt jest unikalny. Wypełnij formularz i otrzymaj bezpłatne wyceny od sprawdzonych firm.\"\n\n";

		$prompt .= "[LEAD_FORM id=\"mid-page\"]\n\n";

		$prompt .= "FORM FIELDS:\n";
		$prompt .= "- Metraż/zakres prac\n";
		$prompt .= "- Lokalizacja (miasto)\n";
		$prompt .= "- Standard/wymagania\n";
		$prompt .= "- Budżet (opcjonalnie)\n";
		$prompt .= "- Email lub telefon\n\n";

		$prompt .= "CTA BUTTON:\n";
		$prompt .= "\"Otrzymaj 3 dopasowane oferty\"\n\n";

		$prompt .= "TRUST SIGNALS below form:\n";
		$prompt .= "✔ bezpłatne\n";
		$prompt .= "✔ szybka odpowiedź (24h)\n";
		$prompt .= "✔ bez zobowiązań\n\n";

		// Section 7: Intent Expansion
		$prompt .= "## SECTION 7: INTENT EXPANSION (SEO CLUSTER)\n\n";
		$prompt .= "H2: \"Co wpływa na koszt {$topic}?\"\n\n";

		$prompt .= "Create 6-7 H3 subsections covering:\n\n";
		$prompt .= "### 1. Metraż i bryła budynku\n";
		$prompt .= "Explain how size impacts price with specific numbers.\n\n";

		$prompt .= "### 2. Projekt (gotowy vs indywidualny)\n";
		$prompt .= "Compare costs and benefits of each option.\n\n";

		$prompt .= "### 3. Lokalizacja (miasto vs wieś)\n";
		$prompt .= "Regional price differences with examples.\n\n";

		$prompt .= "### 4. Materiały budowlane\n";
		$prompt .= "Quality tiers and price impact.\n\n";

		$prompt .= "### 5. Robocizna\n";
		$prompt .= "Labor costs by region and complexity.\n\n";

		$prompt .= "### 6. Technologie (pompa ciepła, rekuperacja, etc.)\n";
		$prompt .= "Modern systems and their costs.\n\n";

		$prompt .= "### 7. Inflacja i ceny rynku\n";
		$prompt .= "Current market trends and forecasts.\n\n";

		$prompt .= "Each subsection: 150-200 words with specific price examples.\n\n";

		// Section 8: Programmatic SEO Grid
		$prompt .= "## SECTION 8: PROGRAMMATIC SEO GRID\n\n";
		$prompt .= "H2: \"Ile kosztuje {$topic} w Twoim mieście?\"\n\n";

		$prompt .= "Intro:\n";
		$prompt .= "\"Ceny mogą się różnić w zależności od lokalizacji. Sprawdź szczegółowe informacje dla swojego miasta:\"\n\n";

		$prompt .= "GRID FORMAT (2-3 columns):\n";
		$prompt .= "```html\n";
		$prompt .= "<div class=\"city-grid\">\n";
		$prompt .= "  <a href=\"/ile-kosztuje-{$service}-katowice-{$year}\">Katowice</a>\n";
		$prompt .= "  <a href=\"/ile-kosztuje-{$service}-krakow-{$year}\">Kraków</a>\n";
		$prompt .= "  <a href=\"/ile-kosztuje-{$service}-warszawa-{$year}\">Warszawa</a>\n";
		$prompt .= "  <a href=\"/ile-kosztuje-{$service}-wroclaw-{$year}\">Wrocław</a>\n";
		$prompt .= "  <a href=\"/ile-kosztuje-{$service}-gdansk-{$year}\">Gdańsk</a>\n";
		$prompt .= "  <a href=\"/ile-kosztuje-{$service}-poznan-{$year}\">Poznań</a>\n";
		$prompt .= "</div>\n";
		$prompt .= "```\n\n";

		// Section 9: Related Content
		$prompt .= "## SECTION 9: RELATED CONTENT (INTERNAL LINKING)\n\n";
		$prompt .= "H2: \"Powiązane artykuły\"\n\n";

		$prompt .= "List 4 related articles:\n";
		$prompt .= "- [koszt fundamentów pod {$topic}](/link-placeholder)\n";
		$prompt .= "- [ile kosztuje wykończenie {$topic}](/link-placeholder)\n";
		$prompt .= "- [ile kosztuje projekt {$topic}](/link-placeholder)\n";
		$prompt .= "- [ranking firm budowlanych](/link-placeholder)\n\n";

		// Section 10: FAQ
		$prompt .= "## SECTION 10: FAQ (SEO + VOICE SEARCH)\n\n";
		$prompt .= "H2: \"Najczęściej zadawane pytania\"\n\n";

		$prompt .= "Create 6-8 FAQ items:\n\n";

		$prompt .= "### Ile kosztuje {$topic} 100 m²?\n";
		$prompt .= "[Specific answer with price range]\n\n";

		$prompt .= "### Ile kosztuje robocizna przy {$topic}?\n";
		$prompt .= "[Specific answer]\n\n";

		$prompt .= "### Czy ceny {$topic} rosną?\n";
		$prompt .= "[Market trend analysis]\n\n";

		$prompt .= "### Ile kosztuje stan surowy {$topic}?\n";
		$prompt .= "[Detailed breakdown]\n\n";

		$prompt .= "Continue with 4-5 more relevant questions...\n\n";

		$prompt .= "SCHEMA MARKUP:\n";
		$prompt .= "[SCHEMA_FAQ]\n";
		$prompt .= "<!-- All FAQ items automatically converted to FAQPage schema -->\n\n";

		// Section 11: Final CTA
		$prompt .= "## SECTION 11: FINAL CTA (BOTTOM – CLOSE)\n\n";
		$prompt .= "Background: Dark or colored section\n\n";
		$prompt .= "H2: \"Nie zgaduj kosztów.\"\n\n";
		$prompt .= "Subhead: \"Sprawdź ile zapłacisz za {$topic}\"\n\n";

		$prompt .= "[LEAD_FORM id=\"final\"]\n\n";

		$prompt .= "Same form fields as mid-page, but with stronger copy:\n";
		$prompt .= "CTA: \"Otrzymaj wycenę teraz – bezpłatnie\"\n\n";

		// Content Specifications
		$prompt .= "===== CONTENT SPECIFICATIONS =====\n\n";
		$prompt .= "- Length: 2500-3000 words\n";
		$prompt .= "- Language: Polish (professional, accessible)\n";
		$prompt .= "- Format: Clean HTML with semantic markup\n";
		$prompt .= "- Price data: Current {$year} market rates\n";
		$prompt .= "- Tables: Use responsive HTML tables\n";
		$prompt .= "- All prices in {$unit}\n";
		$prompt .= "- Include specific numbers, not vague ranges\n";
		$prompt .= "- Use [CALCULATOR_ENGINE], [LIVE_PRICING], [LEAD_FORM] placeholders\n\n";

		// Schema Stack
		$prompt .= "===== SCHEMA.ORG MARKUP =====\n\n";
		$prompt .= "Include:\n";
		$prompt .= "- Article schema (main content)\n";
		$prompt .= "- FAQPage schema (FAQ section)\n";
		$prompt .= "- Breadcrumb schema (navigation)\n";
		if ( $calculator_enabled ) {
			$prompt .= "- Product schema (calculator as tool)\n";
		}
		$prompt .= "\n";

		// Conversion Engine Flow
		$prompt .= "===== CONVERSION ENGINE (FLOW) =====\n\n";
		$prompt .= "User Journey:\n";
		$prompt .= "1. SEO Entry (SERP)\n";
		$prompt .= "2. Quick Answer (trust building)\n";
		$prompt .= "3. Calculator (engagement)\n";
		$prompt .= "4. Lead Form (conversion)\n";
		$prompt .= "5. Lead Engine (processing)\n";
		$prompt .= "6. Revenue (monetization)\n\n";

		// Performance Rules
		$prompt .= "===== PERFORMANCE RULES =====\n\n";
		$prompt .= "- Mobile-first design\n";
		$prompt .= "- Fast load time (<2s target)\n";
		$prompt .= "- Lazy load calculator and forms\n";
		$prompt .= "- Minimal JavaScript (progressive enhancement)\n";
		$prompt .= "- Optimize images and fonts\n\n";

		// Tone & Style
		$prompt .= "===== TONE & STYLE =====\n\n";
		$prompt .= "✓ Data-driven and trustworthy\n";
		$prompt .= "✓ Professional but accessible\n";
		$prompt .= "✓ Direct and benefit-focused\n";
		$prompt .= "✓ User-centric language (\"Twój projekt\")\n";
		$prompt .= "✓ Conversion-optimized copy\n\n";
		$prompt .= "✗ Avoid hype and exaggeration\n";
		$prompt .= "✗ No aggressive sales tactics\n";
		$prompt .= "✗ Don't overpromise\n";
		$prompt .= "✗ No technical jargon without explanation\n\n";

		// Final Instructions
		$prompt .= "===== FINAL INSTRUCTIONS =====\n\n";
		$prompt .= "Write the complete V3 landing page NOW.\n\n";
		$prompt .= "Start with:\n";
		$prompt .= "META: [meta description]\n";
		$prompt .= "TITLE: [SEO title]\n";
		$prompt .= "SLUG: [url slug]\n\n";
		$prompt .= "Then write ALL 11 sections in order.\n\n";
		$prompt .= "Remember:\n";
		$prompt .= "- This is a LANDING PAGE, not a blog post\n";
		$prompt .= "- Focus on CONVERSION at every step\n";
		$prompt .= "- Include ALL placeholders ([CALCULATOR_ENGINE], [LIVE_PRICING], [LEAD_FORM])\n";
		$prompt .= "- Use specific prices and data\n";
		$prompt .= "- Include cost breakdown table\n";
		$prompt .= "- Add 6-8 FAQ items with schema\n";
		$prompt .= "- Include city grid for programmatic SEO\n";
		$prompt .= "- Natural internal linking throughout\n";
		$prompt .= "- Multiple CTAs strategically placed\n\n";

		/**
		 * Filter: pearblog_v3_template_prompt
		 *
		 * Allows modification of the V3 template prompt before generation.
		 *
		 * @param string      $prompt  The assembled prompt.
		 * @param string      $topic   Article topic.
		 * @param array       $config  Configuration array.
		 * @param SiteProfile $profile Site profile.
		 */
		return (string) apply_filters( 'pearblog_v3_template_prompt', $prompt, $topic, $config, $profile );
	}
}

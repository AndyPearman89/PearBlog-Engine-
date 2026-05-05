<?php
/**
 * Poradnik.pro "Ile Kosztuje" Template Builder
 *
 * Specialized template for cost-focused SEO + Lead Generation articles.
 * Implements the Poradnik.pro V1 Template structure with:
 * - Hero with calculator CTA
 * - Featured snippet (quick answer)
 * - Cost breakdown tables
 * - Mid-page lead form
 * - Local programmatic SEO
 * - FAQ with schema
 * - Bottom lead form
 * - Internal linking
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

use PearBlogEngine\Tenant\SiteProfile;

/**
 * Cost-focused template builder for "ile kosztuje X" articles.
 */
class PoradnikCostTemplateBuilder extends PromptBuilder {

	/**
	 * Build complete prompt for cost-focused Poradnik.pro article.
	 *
	 * @param string $topic The article topic (e.g., "budowa domu").
	 * @param array  $config Optional configuration overrides.
	 * @return string Complete prompt text.
	 */
	public function build( string $topic, array $config = [] ): string {
		$topic   = trim( $topic );
		$profile = $this->get_profile();

		// Parse optional config
		$year          = $config['year'] ?? date( 'Y' );
		$city          = $config['city'] ?? '';
		$service       = $config['service'] ?? $topic;
		$min_price     = $config['min_price'] ?? '';
		$max_price     = $config['max_price'] ?? '';
		$unit          = $config['unit'] ?? 'zł';
		$price_per     = $config['price_per'] ?? 'm²';

		$prompt = "You are an expert Polish content writer for Poradnik.pro, specializing in cost analysis and SEO content.\n\n";
		$prompt .= "===== CONTENT TYPE =====\n\n";
		$prompt .= "Template: ILE KOSZTUJE (Cost Analysis + Lead Generation)\n";
		$prompt .= "Purpose: SEO traffic → Trust → Lead conversion\n";
		$prompt .= "Format: Long-form guide with calculator CTAs and lead forms\n\n";

		$prompt .= "===== YOUR TASK =====\n\n";
		$prompt .= "Topic: {$topic}\n";
		if ( $city ) {
			$prompt .= "Location: {$city}\n";
		}
		$prompt .= "Year: {$year}\n\n";

		$prompt .= "Create a comprehensive cost analysis article following the exact structure below.\n\n";

		$prompt .= "===== MANDATORY STRUCTURE =====\n\n";

		// 1. HERO
		$prompt .= "## 1. HERO (ABOVE THE FOLD)\n\n";
		$prompt .= "H1 FORMAT:\n";
		$prompt .= "\"Ile kosztuje {$topic}";
		if ( $city ) {
			$prompt .= " w {$city}";
		} else {
			$prompt .= " w Polsce";
		}
		$prompt .= " w {$year}? [Realne ceny + kalkulator]\"\n\n";

		$prompt .= "LEAD PARAGRAPH (2-3 sentences):\n";
		$prompt .= "- Start with direct answer: \"[Topic] w {$year} roku kosztuje od X do Y zł...\"\n";
		$prompt .= "- Mention key factors: metraż/rozmiar, standard, lokalizacja\n";
		$prompt .= "- End with value promise: \"Sprawdź aktualne ceny i oblicz koszt dla swojej inwestycji.\"\n\n";

		$prompt .= "CTA BUTTON:\n";
		$prompt .= "Text: \"👉 Oblicz koszt [topic] w 60 sekund\"\n";
		$prompt .= "Format: [CTA_CALCULATOR]\n\n";

		// 2. FEATURED SNIPPET
		$prompt .= "## 2. SZYBKA ODPOWIEDŹ (FEATURED SNIPPET)\n\n";
		$prompt .= "Format as a box/callout:\n\n";
		$prompt .= "### Ile kosztuje {$topic} w {$year}?\n\n";
		$prompt .= "Create a bulleted list with 3-4 price tiers:\n";
		$prompt .= "- Basic tier: [X-Y zł/{$price_per}] - description\n";
		$prompt .= "- Mid tier: [X-Y zł/{$price_per}] - description\n";
		$prompt .= "- Premium tier: [X-Y zł/{$price_per}] - description\n\n";
		$prompt .= "Example total cost (e.g., for 100 m²):\n";
		$prompt .= "\"[Topic] 100 m²: X – Y zł (stan deweloperski)\"\n\n";

		// 3. COST BREAKDOWN TABLE
		$prompt .= "## 3. ROZBICIE KOSZTÓW (DETAILED TABLE)\n\n";
		$prompt .= "Create HTML table with structure:\n\n";
		$prompt .= "```html\n";
		$prompt .= "<table>\n";
		$prompt .= "  <thead>\n";
		$prompt .= "    <tr>\n";
		$prompt .= "      <th>Etap/Element</th>\n";
		$prompt .= "      <th>Koszt ({$price_per})</th>\n";
		$prompt .= "      <th>Co obejmuje</th>\n";
		$prompt .= "    </tr>\n";
		$prompt .= "  </thead>\n";
		$prompt .= "  <tbody>\n";
		$prompt .= "    <!-- 5-8 rows with specific stages/elements -->\n";
		$prompt .= "  </tbody>\n";
		$prompt .= "</table>\n";
		$prompt .= "```\n\n";

		// 4. MID-PAGE LEAD CTA
		$prompt .= "## 4. CTA – LEAD GENERATION (MID-PAGE)\n\n";
		$prompt .= "Insert lead generation section:\n\n";
		$prompt .= "H3: \"👉 Ile kosztuje {$topic} w Twoim przypadku?\"\n\n";
		$prompt .= "Short paragraph:\n";
		$prompt .= "\"Każda inwestycja jest inna. Wypełnij krótki formularz i otrzymaj bezpłatne wyceny od sprawdzonych firm w Twojej okolicy.\"\n\n";
		$prompt .= "Form placeholder:\n";
		$prompt .= "[LEAD_FORM]\n";
		$prompt .= "Fields:\n";
		$prompt .= "- Metraż/zakres\n";
		$prompt .= "- Lokalizacja (miasto)\n";
		$prompt .= "- Standard/wymagania\n";
		$prompt .= "- Kontakt (telefon/email)\n\n";
		$prompt .= "Button: \"Wyślij zapytanie → otrzymaj wyceny od firm\"\n\n";

		// 5. COST FACTORS
		$prompt .= "## 5. CO WPŁYWA NA KOSZT?\n\n";
		$prompt .= "H2: \"Od czego zależy cena {$topic}?\"\n\n";
		$prompt .= "Detailed analysis with H3 subsections for each factor:\n\n";
		$prompt .= "### 1. [Factor name]\n";
		$prompt .= "- Explanation\n";
		$prompt .= "- Price impact (konkretne liczby jeśli możliwe)\n";
		$prompt .= "- Examples\n\n";
		$prompt .= "Cover at least 5-6 major factors:\n";
		$prompt .= "- Rozmiar/metraż\n";
		$prompt .= "- Projekt/design (indywidualny vs gotowy)\n";
		$prompt .= "- Lokalizacja geograficzna\n";
		$prompt .= "- Materiały i ich jakość\n";
		$prompt .= "- Robocizna (regional differences)\n";
		$prompt .= "- Dodatkowe technologie/features\n\n";

		// 6. SELECTION GUIDE
		$prompt .= "## 6. JAK WYBRAĆ NAJLEPSZĄ OPCJĘ?\n\n";
		$prompt .= "H2: \"Jak wybrać [firmę/wykonawcę/rozwiązanie]?\"\n\n";
		$prompt .= "Practical advice in numbered list or subsections:\n";
		$prompt .= "1. Porównaj oferty (minimum 3-4)\n";
		$prompt .= "2. Sprawdź opinie i referencje\n";
		$prompt .= "3. Zwróć uwagę na szczegóły umowy\n";
		$prompt .= "4. Zapytaj o gwarancje\n";
		$prompt .= "5. Unikaj podejrzanie niskich cen\n";
		$prompt .= "6. Sprawdź certyfikaty/uprawnienia\n\n";

		// 7. LOCAL PROGRAMMATIC SEO
		$prompt .= "## 7. PROGRAMMATIC SEO (LOKALNE WARIANTY)\n\n";
		$prompt .= "After main content, add section:\n\n";
		$prompt .= "H2: \"Ile kosztuje {$topic} w innych miastach?\"\n\n";
		$prompt .= "Short intro: \"Ceny mogą się różnić w zależności od lokalizacji. Sprawdź szczegółowe informacje dla swojego miasta:\"\n\n";
		$prompt .= "List of links (use placeholder format):\n";
		$prompt .= "- [ile kosztuje {$topic} w Warszawie](/ile-kosztuje-{$service}-warszawa)\n";
		$prompt .= "- [ile kosztuje {$topic} w Krakowie](/ile-kosztuje-{$service}-krakow)\n";
		$prompt .= "- [ile kosztuje {$topic} w Katowicach](/ile-kosztuje-{$service}-katowice)\n";
		$prompt .= "- [ile kosztuje {$topic} we Wrocławiu](/ile-kosztuje-{$service}-wroclaw)\n";
		$prompt .= "- [ile kosztuje {$topic} w Poznaniu](/ile-kosztuje-{$service}-poznan)\n";
		$prompt .= "- [ile kosztuje {$topic} w Gdańsku](/ile-kosztuje-{$service}-gdansk)\n\n";

		// 8. FAQ
		$prompt .= "## 8. FAQ (NAJCZĘŚCIEJ ZADAWANE PYTANIA)\n\n";
		$prompt .= "H2: \"Najczęściej zadawane pytania o koszty {$topic}\"\n\n";
		$prompt .= "Create 6-8 FAQ items in this exact format:\n\n";
		$prompt .= "### Pytanie 1?\n";
		$prompt .= "Odpowiedź w 2-3 zdaniach z konkretnymi informacjami.\n\n";
		$prompt .= "Include questions about:\n";
		$prompt .= "- Total cost estimates\n";
		$prompt .= "- Labor vs materials breakdown\n";
		$prompt .= "- Timeline/duration\n";
		$prompt .= "- Future price trends\n";
		$prompt .= "- Financing options\n";
		$prompt .= "- Hidden costs to watch for\n";
		$prompt .= "- When is the best time (season)\n";
		$prompt .= "- DIY vs professional\n\n";

		// 9. BOTTOM LEAD CTA
		$prompt .= "## 9. CTA FINAL (BOTTOM OF PAGE)\n\n";
		$prompt .= "Strong closing CTA section:\n\n";
		$prompt .= "H2: \"Nie zgaduj kosztów – sprawdź realną wycenę\"\n\n";
		$prompt .= "Paragraph:\n";
		$prompt .= "\"Każdy projekt jest unikalny. Nie opieraj się na szacunkach z internetu – otrzymaj profesjonalną wycenę dostosowaną do Twojej sytuacji. To nic nie kosztuje i nie zobowiązuje.\"\n\n";
		$prompt .= "[LEAD_FORM_BOTTOM]\n\n";
		$prompt .= "Button: \"👉 Sprawdź realną wycenę dla Twojej działki/projektu\"\n\n";

		// 10. INTERNAL LINKING
		$prompt .= "## 10. INTERNAL LINKING (NATURAL PLACEMENT)\n\n";
		$prompt .= "Throughout the article, naturally include 4-6 internal links to:\n\n";
		$prompt .= "Related guides:\n";
		$prompt .= "- \"ranking firm [category]\"\n";
		$prompt .= "- \"jak wybrać [related service]\"\n";
		$prompt .= "- \"koszt [related element]\"\n";
		$prompt .= "- \"[related topic] - poradnik\"\n\n";
		$prompt .= "Use natural anchor texts like:\n";
		$prompt .= "- \"Sprawdź również nasz ranking firm budowlanych\"\n";
		$prompt .= "- \"Więcej o kosztach fundamentów przeczytasz tutaj\"\n";
		$prompt .= "- \"Zobacz jak wybrać projekt domu\"\n\n";
		$prompt .= "Format: [anchor text](placeholder-url)\n\n";

		// TONE & STYLE
		$prompt .= "===== TONE & STYLE GUIDELINES =====\n\n";
		$prompt .= "✓ Professional yet accessible Polish\n";
		$prompt .= "✓ Data-driven (konkretne liczby)\n";
		$prompt .= "✓ Practical and actionable\n";
		$prompt .= "✓ Trustworthy (cite sources when possible)\n";
		$prompt .= "✓ Optimistic but realistic\n";
		$prompt .= "✓ User-focused (\"Twoja inwestycja\", \"Twój projekt\")\n\n";
		$prompt .= "✗ Avoid hype and exaggeration\n";
		$prompt .= "✗ Avoid aggressive sales language\n";
		$prompt .= "✗ Don't overpromise\n";
		$prompt .= "✗ Don't use technical jargon without explanation\n\n";

		// META & SEO
		$prompt .= "===== META & SEO REQUIREMENTS =====\n\n";
		$prompt .= "First line - META DESCRIPTION (150-160 chars):\n";
		$prompt .= "META: Sprawdź ile kosztuje {$topic} w {$year}. Aktualne ceny za {$price_per}, przykłady i szybka wycena online.\n\n";

		$prompt .= "SEO TITLE (shown in search results, 50-60 chars):\n";
		$prompt .= "TITLE: Ile kosztuje {$topic} {$year}? Ceny + kalkulator\n\n";

		$prompt .= "URL Slug:\n";
		$prompt .= "SLUG: ile-kosztuje-{$service}";
		if ( $city ) {
			$prompt .= "-" . strtolower( str_replace( ' ', '-', $city ) );
		}
		$prompt .= "-{$year}\n\n";

		// CONTENT SPECS
		$prompt .= "===== CONTENT SPECIFICATIONS =====\n\n";
		$prompt .= "- Length: 2000-2500 words (comprehensive)\n";
		$prompt .= "- Language: Polish\n";
		$prompt .= "- Format: Clean HTML with proper heading hierarchy\n";
		$prompt .= "- Price data: Current {$year} market rates\n";
		$prompt .= "- Tables: Use HTML <table> tags\n";
		$prompt .= "- Lists: Use <ul>/<ol> for better readability\n";
		$prompt .= "- CTAs: Use [CTA_CALCULATOR] and [LEAD_FORM] placeholders\n";
		$prompt .= "- Links: Use [text](url) markdown format\n\n";

		// SCHEMA MARKUP
		$prompt .= "===== SCHEMA.ORG MARKUP =====\n\n";
		$prompt .= "Include at the end:\n\n";
		$prompt .= "[SCHEMA_FAQ]\n";
		$prompt .= "<!-- All FAQ items will be automatically converted to FAQ schema -->\n\n";
		$prompt .= "[SCHEMA_HOWTO]\n";
		$prompt .= "<!-- Selection guide will be converted to HowTo schema -->\n\n";

		// FINAL INSTRUCTIONS
		$prompt .= "===== FINAL INSTRUCTIONS =====\n\n";
		$prompt .= "Write the complete article NOW following every section above.\n";
		$prompt .= "Start with:\n";
		$prompt .= "META: [meta description]\n";
		$prompt .= "TITLE: [SEO title]\n";
		$prompt .= "SLUG: [url slug]\n\n";
		$prompt .= "Then write the full article content.\n";
		$prompt .= "Remember:\n";
		$prompt .= "- Include ALL sections (1-10)\n";
		$prompt .= "- Use exact heading structure\n";
		$prompt .= "- Insert CTA placeholders where indicated\n";
		$prompt .= "- Include cost breakdown table\n";
		$prompt .= "- Add 6-8 FAQ items\n";
		$prompt .= "- Include local city variants for programmatic SEO\n";
		$prompt .= "- Natural internal linking throughout\n\n";

		/**
		 * Filter: pearblog_cost_template_prompt
		 *
		 * @param string      $prompt  The assembled prompt.
		 * @param string      $topic   Article topic.
		 * @param array       $config  Configuration array.
		 * @param SiteProfile $profile Site profile.
		 */
		return (string) apply_filters( 'pearblog_cost_template_prompt', $prompt, $topic, $config, $profile );
	}
}

<?php
/**
 * Poradnik.pro Clean Content System - prompt builder for trust-building content.
 *
 * Generates SEO-optimized, cost-focused content with natural PT24 integration.
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

use PearBlogEngine\Tenant\SiteProfile;

/**
 * Clean content system for Poradnik.pro - focuses on trust, value, and soft CTAs.
 */
class PoradnikPromptBuilder extends PromptBuilder {

	/**
	 * Build a complete prompt for Poradnik.pro clean content system.
	 *
	 * @param string $topic The article topic.
	 * @return string       Complete prompt text.
	 */
	public function build( string $topic ): string {
		$topic   = trim( $topic );
		$profile = $this->get_profile();

		$prompt = "You are an expert content writer for Poradnik.pro, a trusted Polish guide platform.\n\n";
		$prompt .= "Your mission: Create clean, trustworthy content that educates readers and builds confidence.\n\n";
		$prompt .= "===== CONTENT STRATEGY =====\n\n";
		$prompt .= "This is a PRE-SELL system:\n";
		$prompt .= "✔ Portal contentowy (content portal)\n";
		$prompt .= "✔ SEO engine (search traffic)\n";
		$prompt .= "✔ System zaufania (trust building)\n";
		$prompt .= "✔ Pre-sell soft (natural funnel)\n\n";

		$prompt .= "USER FLOW:\n";
		$prompt .= "Google → poradnik → czytanie → zaufanie → soft CTA → pt24 → lead\n\n";

		$prompt .= "===== YOUR TASK =====\n\n";
		$prompt .= "Topic: {$topic}\n\n";

		$prompt .= "Write in Polish, professional yet approachable tone.\n\n";

		$prompt .= "===== STRUCTURE (MANDATORY) =====\n\n";

		$prompt .= "1. META DESCRIPTION (first line):\n";
		$prompt .= "META: Sprawdź ceny i dowiedz się jak wybrać [topic]. Rzetelne informacje i praktyczne wskazówki.\n\n";

		$prompt .= "2. H1 TITLE:\n";
		$prompt .= "{topic} - ile kosztuje i jak wybrać\n\n";

		$prompt .= "3. INTRO (2-3 paragraphs):\n";
		$prompt .= "- Krótki opis problemu i dlaczego to ważne\n";
		$prompt .= "- Co znajdziesz w tym poradniku\n";
		$prompt .= "- Fokus na wartość dla czytelnika\n\n";

		$prompt .= "4. SECTION: ## Co to jest {topic}?\n";
		$prompt .= "- Wyjaśnienie w prosty sposób\n";
		$prompt .= "- Dlaczego warto się tym zainteresować\n";
		$prompt .= "- Podstawowe informacje\n\n";

		$prompt .= "5. SECTION: ## Ile kosztuje {topic}?\n";
		$prompt .= "- Zakres cenowy (od X do Y zł)\n";
		$prompt .= "- Faktory wpływające na koszt\n";
		$prompt .= "- Tabela z przykładowymi cenami (jeśli możliwe)\n\n";

		$prompt .= "6. SECTION: ## Od czego zależy cena?\n";
		$prompt .= "Explain factors:\n";
		$prompt .= "- Zakres usługi/produktu\n";
		$prompt .= "- Lokalizacja (miasto vs prowincja)\n";
		$prompt .= "- Jakość materiałów/wykonania\n";
		$prompt .= "- Dostępność specjalistów\n";
		$prompt .= "- Sezon/pora roku\n\n";

		$prompt .= "7. SECTION: ## Jak wybrać najlepszą opcję?\n";
		$prompt .= "Practical advice:\n";
		$prompt .= "- Porównaj kilka ofert\n";
		$prompt .= "- Sprawdź opinie innych klientów\n";
		$prompt .= "- Zwróć uwagę na szczegóły umowy/gwarancji\n";
		$prompt .= "- Zadaj kluczowe pytania\n";
		$prompt .= "- Unikaj zbyt niskich cen (red flag)\n\n";

		$prompt .= "8. SOFT CTA SECTION:\n";
		$prompt .= "Add natural paragraph:\n";
		$prompt .= "\"Jeśli szukasz sprawdzonych specjalistów w Twojej okolicy, możesz [sprawdzić dostępne opcje](link) i porównać oferty. To pomoże Ci podjąć świadomą decyzję.\"\n\n";
		$prompt .= "IMPORTANT: Use placeholder [sprawdź dostępne opcje] or [zobacz rozwiązania] for the link text.\n\n";

		$prompt .= "9. FAQ SECTION: ## Najczęściej zadawane pytania\n";
		$prompt .= "Include 4-6 questions:\n";
		$prompt .= "- Czy to drogie?\n";
		$prompt .= "- Czy warto porównać oferty?\n";
		$prompt .= "- Jak długo trwa [proces]?\n";
		$prompt .= "- Czy potrzebuję [specific requirement]?\n";
		$prompt .= "- Na co zwrócić uwagę?\n\n";

		$prompt .= "10. CONCLUSION:\n";
		$prompt .= "- Podsumowanie kluczowych punktów\n";
		$prompt .= "- Zachęta do świadomego wyboru\n";
		$prompt .= "- Pozytywne zakończenie\n\n";

		$prompt .= "===== TONE & STYLE =====\n\n";
		$prompt .= "DO:\n";
		$prompt .= "✓ Czytelność (clear, readable)\n";
		$prompt .= "✓ Wartość (valuable information)\n";
		$prompt .= "✓ Zaufanie (trustworthy, credible)\n";
		$prompt .= "✓ Naturalne CTA (soft, helpful)\n";
		$prompt .= "✓ Praktyczne wskazówki (actionable advice)\n\n";

		$prompt .= "DON'T:\n";
		$prompt .= "✗ Agresywna sprzedaż (no hard sell)\n";
		$prompt .= "✗ Clickbait (no sensationalism)\n";
		$prompt .= "✗ Obietnice bez pokrycia (no empty promises)\n";
		$prompt .= "✗ Zbyt techniczny język (keep it accessible)\n";
		$prompt .= "✗ Wielokrotne CTA (max 1 soft CTA)\n\n";

		$prompt .= "===== CONTENT REQUIREMENTS =====\n\n";
		$prompt .= "- Length: 1200-1800 words\n";
		$prompt .= "- Language: Polish\n";
		$prompt .= "- Format: Clean HTML (H2, H3, paragraphs, lists)\n";
		$prompt .= "- SEO: Natural keyword integration (no stuffing)\n";
		$prompt .= "- Structure: Follow sections above exactly\n";
		$prompt .= "- CTA: One soft, natural call-to-action linking to local solutions\n\n";

		$prompt .= "===== LINKING STRATEGY =====\n\n";
		$prompt .= "Natural anchor texts for internal PT24 links:\n";
		$prompt .= "- \"sprawdź dostępne opcje\"\n";
		$prompt .= "- \"zobacz rozwiązania w Twojej okolicy\"\n";
		$prompt .= "- \"porównaj oferty lokalnych specjalistów\"\n";
		$prompt .= "- \"znajdź sprawdzonych wykonawców\"\n\n";
		$prompt .= "Place ONE link naturally in the \"Jak wybrać\" or dedicated soft CTA section.\n\n";

		$prompt .= "===== CATEGORIES =====\n\n";
		$prompt .= "Focus areas for Poradnik.pro:\n";
		$prompt .= "- Remont (renovation)\n";
		$prompt .= "- Budowa (construction)\n";
		$prompt .= "- Auto (automotive)\n";
		$prompt .= "- Finanse (finance)\n";
		$prompt .= "- Dom i ogród (home & garden)\n\n";

		$prompt .= "===== FINAL REMINDERS =====\n\n";
		$prompt .= "This content is NOT:\n";
		$prompt .= "- A landing page (no aggressive conversion)\n";
		$prompt .= "- A sales pitch (no product pushing)\n";
		$prompt .= "- An advertisement (genuine helpful content)\n\n";

		$prompt .= "This content IS:\n";
		$prompt .= "- Educational and trustworthy\n";
		$prompt .= "- SEO-optimized for organic traffic\n";
		$prompt .= "- Part of a trust-building funnel\n";
		$prompt .= "- A bridge between search intent and marketplace\n\n";

		$prompt .= "Write the complete article now, following all structure requirements above.\n";
		$prompt .= "Start with META: on the first line.\n";

		/**
		 * Filter: pearblog_poradnik_prompt
		 *
		 * Allows customization of Poradnik.pro prompt.
		 *
		 * @param string      $prompt  The assembled prompt text.
		 * @param string      $topic   The article topic.
		 * @param SiteProfile $profile The active site profile.
		 */
		return (string) apply_filters( 'pearblog_poradnik_prompt', $prompt, $topic, $profile );
	}
}

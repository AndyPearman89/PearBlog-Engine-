<?php
/**
 * Multi-language localization builder for travel content.
 * Provides true localization (not just translation) for different markets.
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

use PearBlogEngine\Tenant\SiteProfile;

/**
 * Localizes Beskidy content for different language markets (PL/EN/DE).
 */
class MultiLanguageTravelBuilder extends BeskidyPromptBuilder {

	/**
	 * Build localized prompt based on language.
	 *
	 * @param string $topic The article topic / keyword.
	 * @return string       Ready-to-use prompt text.
	 */
	public function build( string $topic ): string {
		$topic   = trim( $topic );
		$profile = $this->profile;

		// Build base prompt based on language.
		$prompt = match ( strtolower( $profile->language ) ) {
			'en' => $this->build_english_prompt( $topic, $profile ),
			'de' => $this->build_german_prompt( $topic, $profile ),
			default => $this->build_polish_prompt( $topic, $profile ),
		};

		/**
		 * Filter: pearblog_multilang_prompt
		 *
		 * Allows customization of multi-language prompts.
		 *
		 * @param string      $prompt  The assembled prompt text.
		 * @param string      $topic   The article topic.
		 * @param SiteProfile $profile The active site profile.
		 * @param string      $language The target language.
		 */
		$prompt = (string) apply_filters(
			'pearblog_multilang_prompt',
			$prompt,
			$topic,
			$profile,
			$profile->language
		);

		return (string) apply_filters( 'pearblog_prompt', $prompt, $topic, $profile );
	}

	/**
	 * Build Polish version (original, most detailed).
	 */
	protected function build_polish_prompt( string $topic, SiteProfile $profile ): string {
		// Use the full Beskidy prompt for Polish.
		return parent::build( $topic );
	}

	/**
	 * Build English version (global audience adaptation).
	 */
	protected function build_english_prompt( string $topic, SiteProfile $profile ): string {
		$prompt  = "SYSTEM ROLE:\n";
		$prompt .= "You are an AI SEO content engine for international travelers visiting Beskidy, Poland.\n";
		$prompt .= "You generate high-quality, human-like travel content in English.\n\n";
		$prompt .= "MISSION:\n";
		$prompt .= "Create content that:\n";
		$prompt .= "- ranks in Google for English-speaking tourists\n";
		$prompt .= "- explains WHERE Beskidy is (Poland, Central Europe)\n";
		$prompt .= "- provides context for international visitors\n";
		$prompt .= "- drives accommodation bookings\n";
		$prompt .= "- solves travel planning needs\n\n";

		$prompt .= "TOPIC: {$topic}\n";
		$prompt .= "LANGUAGE: English (natural, clear)\n";
		$prompt .= "TONE: {$profile->tone}\n\n";

		$prompt .= "TARGET AUDIENCE:\n";
		$prompt .= "- International tourists visiting Poland\n";
		$prompt .= "- English-speaking travelers\n";
		$prompt .= "- First-time visitors to Beskidy\n";
		$prompt .= "- Adventure and nature enthusiasts\n\n";

		$prompt .= "MANDATORY STRUCTURE:\n\n";
		$prompt .= "<h1>Main keyword</h1>\n\n";
		$prompt .= "<p>Hook - explain what Beskidy is and why visit (2-3 sentences)</p>\n\n";
		$prompt .= "<h2>Quick Facts (TL;DR)</h2>\n";
		$prompt .= "<ul>\n";
		$prompt .= "<li>⏱ Duration</li>\n";
		$prompt .= "<li>📈 Difficulty level</li>\n";
		$prompt .= "<li>👨‍👩‍👧 Best for</li>\n";
		$prompt .= "<li>📍 Location (include: Beskidy, Poland, Europe)</li>\n";
		$prompt .= "</ul>\n\n";

		$prompt .= "<h2>Why Visit?</h2>\n\n";
		$prompt .= "<h2>Description & Details</h2>\n\n";
		$prompt .= "<h2>How to Get There</h2>\n";
		$prompt .= "(Include: nearest airports, cities, driving directions, public transport)\n\n";

		$prompt .= "<h2>Weather & Best Time to Visit</h2>\n";
		$prompt .= "(Include seasonal information, what to pack)\n\n";

		$prompt .= "<h2>Day Itinerary</h2>\n";
		$prompt .= "- Morning activities\n";
		$prompt .= "- Midday plan\n";
		$prompt .= "- Evening suggestions\n";
		$prompt .= "- Alternative Plan B for bad weather\n\n";

		$prompt .= "<h2>Where to Stay</h2>\n";
		$prompt .= "MONETIZATION:\n";
		$prompt .= "- Recommend accommodation for international visitors\n";
		$prompt .= "- Mention booking platforms (Booking.com, Airbnb)\n";
		$prompt .= "- Suggest areas/towns to stay\n";
		$prompt .= "- CTA: 'Check available accommodation in the area'\n\n";

		$prompt .= "<h2>Practical Tips</h2>\n\n";
		$prompt .= "<h2>FAQ</h2>\n\n";
		$prompt .= "<h2>Related Guides</h2>\n\n";

		$prompt .= "SEO ADAPTATION:\n";
		$prompt .= "- Use English keywords naturally: 'hiking trails', 'mountains', 'Poland'\n";
		$prompt .= "- Include meta description (max 160 characters) at top, prefixed with META:\n";
		$prompt .= "- Explain Polish terms when used\n";
		$prompt .= "- Provide context (distances, comparisons to known places)\n\n";

		$prompt .= "CONTENT QUALITY:\n";
		$prompt .= "- Clear, helpful English (native-level)\n";
		$prompt .= "- No fluff, practical information\n";
		$prompt .= "- Add context for international readers\n";
		$prompt .= "- Minimum 1,000 words\n";
		$prompt .= "- HTML output only\n\n";

		return $prompt;
	}

	/**
	 * Build German version (precise, structured for German-speaking tourists).
	 */
	protected function build_german_prompt( string $topic, SiteProfile $profile ): string {
		$prompt  = "SYSTEMROLLE:\n";
		$prompt .= "Sie sind eine AI-SEO-Content-Engine für deutschsprachige Reisende in den Beskiden, Polen.\n";
		$prompt .= "Sie erstellen hochwertige, menschlich wirkende Reiseinhalte auf Deutsch.\n\n";
		$prompt .= "MISSION:\n";
		$prompt .= "Erstellen Sie Inhalte, die:\n";
		$prompt .= "- in Google für deutsche Touristen ranken\n";
		$prompt .= "- präzise und strukturierte Informationen liefern\n";
		$prompt .= "- Buchungen von Unterkünften fördern\n";
		$prompt .= "- Reiseplanung erleichtern\n\n";

		$prompt .= "THEMA: {$topic}\n";
		$prompt .= "SPRACHE: Deutsch (natürlich, präzise)\n";
		$prompt .= "TON: {$profile->tone}, präzise\n\n";

		$prompt .= "ZIELGRUPPE:\n";
		$prompt .= "- Deutschsprachige Touristen\n";
		$prompt .= "- Polen-Reisende\n";
		$prompt .= "- Bergwanderer und Naturliebhaber\n";
		$prompt .= "- Familien und Wochenendreisende\n\n";

		$prompt .= "PFLICHTSTRUKTUR:\n\n";
		$prompt .= "<h1>Hauptkeyword</h1>\n\n";
		$prompt .= "<p>Einleitung - was sind die Beskiden und warum besuchen (2-3 Sätze)</p>\n\n";
		$prompt .= "<h2>Auf einen Blick</h2>\n";
		$prompt .= "<ul>\n";
		$prompt .= "<li>⏱ Zeitaufwand</li>\n";
		$prompt .= "<li>📈 Schwierigkeitsgrad</li>\n";
		$prompt .= "<li>👨‍👩‍👧 Geeignet für</li>\n";
		$prompt .= "<li>📍 Lage (Beskiden, Polen, Mitteleuropa)</li>\n";
		$prompt .= "</ul>\n\n";

		$prompt .= "<h2>Warum lohnt sich ein Besuch?</h2>\n\n";
		$prompt .= "<h2>Beschreibung & Details</h2>\n\n";
		$prompt .= "<h2>Anreise</h2>\n";
		$prompt .= "(Nächste Flughäfen, Städte, Anfahrt, öffentliche Verkehrsmittel)\n\n";

		$prompt .= "<h2>Wetter & Beste Reisezeit</h2>\n";
		$prompt .= "(Saisonale Informationen, Packliste)\n\n";

		$prompt .= "<h2>Tagesplan</h2>\n";
		$prompt .= "- Morgen\n";
		$prompt .= "- Mittag\n";
		$prompt .= "- Nachmittag/Abend\n";
		$prompt .= "- Alternativplan bei schlechtem Wetter\n\n";

		$prompt .= "<h2>Unterkunft</h2>\n";
		$prompt .= "MONETARISIERUNG:\n";
		$prompt .= "- Unterkunftsempfehlungen für deutsche Touristen\n";
		$prompt .= "- Buchungsplattformen nennen\n";
		$prompt .= "- Geeignete Orte/Städte vorschlagen\n";
		$prompt .= "- CTA: 'Verfügbare Unterkünfte in der Umgebung prüfen'\n\n";

		$prompt .= "<h2>Praktische Tipps</h2>\n\n";
		$prompt .= "<h2>Häufig gestellte Fragen</h2>\n\n";
		$prompt .= "<h2>Verwandte Artikel</h2>\n\n";

		$prompt .= "SEO-ANPASSUNG:\n";
		$prompt .= "- Deutsche Keywords verwenden: 'Wanderwege', 'Gebirge', 'Polen'\n";
		$prompt .= "- Meta-Beschreibung (max 160 Zeichen) am Anfang mit Präfix META:\n";
		$prompt .= "- Polnische Begriffe bei Bedarf erklären\n";
		$prompt .= "- Kontext für deutsche Leser (Entfernungen, Vergleiche)\n\n";

		$prompt .= "QUALITÄT:\n";
		$prompt .= "- Klares, präzises Deutsch\n";
		$prompt .= "- Praktische, strukturierte Informationen\n";
		$prompt .= "- Kein Fülltext\n";
		$prompt .= "- Mindestens 1.000 Wörter\n";
		$prompt .= "- Nur HTML-Ausgabe\n\n";

		return $prompt;
	}
}

<?php
/**
 * AI Recommendation Engine.
 *
 * High-level decision AI layer that wraps AIClient to produce
 * structured recommendations for:
 *   - Specialists matching a user need
 *   - Comparison pages relevant to a query
 *   - Rankings for a category
 *   - Guides / poradniki
 *
 * All recommendations include a confidence score and a brief rationale.
 *
 * @package PearBlogEngine\AI
 */

declare( strict_types=1 );

namespace PearBlogEngine\AI;

class RecommendationEngine {

	/** Cache TTL for recommendation results (seconds). */
	private const CACHE_TTL = 3600;

	private AIClient $ai;

	public function __construct( ?AIClient $ai = null ) {
		$this->ai = $ai ?? new AIClient();
	}

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * Given a user query, return recommended content items.
	 *
	 * @param  string              $query       Raw user question / search term.
	 * @param  array<string,mixed> $context     Optional: city, budget, category, etc.
	 * @return array<string,mixed>              { intent, items: [ {type, title, url, score, rationale} ] }
	 */
	public function recommend_for_query( string $query, array $context = [] ): array {
		$cache_key = 'pb_ai_rec_' . md5( $query . wp_json_encode( $context ) );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$intent = $this->classify_intent( $query );
		$items  = $this->fetch_by_intent( $intent, $query, $context );

		$result = [
			'intent' => $intent,
			'items'  => $items,
		];

		set_transient( $cache_key, $result, self::CACHE_TTL );

		return $result;
	}

	/**
	 * Recommend specialists for a specific trade / city combination.
	 *
	 * @param  array<string,mixed> $context  keys: city, trade, budget
	 * @return array<int,array<string,mixed>>
	 */
	public function recommend_specialists( string $query, array $context = [] ): array {
		$prompt = sprintf(
			'Użytkownik szuka specjalisty. Zapytanie: "%s". ' .
			'Miasto: %s. Branża: %s. Budżet: %s. ' .
			'Wymień 3 kryteria wyboru specjalisty i 2 pytania które warto zadać. ' .
			'Odpowiedz w formacie JSON: {"criteria": [...], "questions": [...]}. ' .
			'Tylko JSON, bez żadnego tekstu.',
			$query,
			$context['city']   ?? 'nieznane',
			$context['trade']  ?? 'nieznana branża',
			$context['budget'] ?? 'nieznany'
		);

		try {
			$raw  = $this->ai->generate( $prompt, [ 'max_tokens' => 300 ] );
			$data = json_decode( $this->extract_json( $raw ), true );

			return [
				'criteria'  => $data['criteria']  ?? [],
				'questions' => $data['questions'] ?? [],
			];
		} catch ( \Throwable ) {
			return [ 'criteria' => [], 'questions' => [] ];
		}
	}

	/**
	 * Summarise a ranking category using AI.
	 *
	 * @param  string              $category
	 * @param  array<string,mixed> $top_items  [ {name, score} ]
	 */
	public function summarise_ranking( string $category, array $top_items ): string {
		$names  = array_map( static fn( $i ) => $i['name'] ?? '?', array_slice( $top_items, 0, 5 ) );
		$prompt = sprintf(
			'Ranking kategorii: "%s". Top firmy: %s. ' .
			'Napisz 2-zdaniowe podsumowanie rankingu po polsku, podkreślając lidera i dlaczego warto go wybrać.',
			$category,
			implode( ', ', $names )
		);

		try {
			return $this->ai->generate( $prompt, [ 'max_tokens' => 120 ] );
		} catch ( \Throwable ) {
			return '';
		}
	}

	/**
	 * Generate a FAQ block for a given topic.
	 *
	 * @return array<int,array{question:string,answer:string}>
	 */
	public function generate_faq( string $topic, int $count = 5 ): array {
		$prompt = sprintf(
			'Temat: "%s". Wygeneruj %d par pytanie-odpowiedź FAQ po polsku. ' .
			'Format JSON: [{"question":"...","answer":"..."}]. Tylko JSON.',
			$topic,
			$count
		);

		try {
			$raw  = $this->ai->generate( $prompt, [ 'max_tokens' => 600 ] );
			$data = json_decode( $this->extract_json( $raw ), true );

			return is_array( $data ) ? $data : [];
		} catch ( \Throwable ) {
			return [];
		}
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Classify the user's intent from a query string.
	 * Returns one of: find_specialist | compare | calculate | guide | ranking | unknown
	 */
	private function classify_intent( string $query ): string {
		$q = mb_strtolower( $query );

		$patterns = [
			'find_specialist' => [ 'szukam', 'polecasz', 'specjalista', 'fachowiec', 'firma', 'hydraulik', 'prawnik', 'mechanik' ],
			'compare'         => [ 'vs', ' czy ', 'porównanie', 'lepsz', 'różnica', 'między' ],
			'calculate'       => [ 'koszt', 'cena', 'ile kosztuje', 'kalkulator', 'wycena', 'zł', 'ceny' ],
			'ranking'         => [ 'ranking', 'najlepsze', 'top ', 'polecane', 'opinie' ],
			'guide'           => [ 'jak', 'poradnik', 'co to', 'czy warto', 'kiedy', 'dlaczego' ],
		];

		foreach ( $patterns as $intent => $keywords ) {
			foreach ( $keywords as $kw ) {
				if ( str_contains( $q, $kw ) ) {
					return $intent;
				}
			}
		}

		return 'unknown';
	}

	/**
	 * Fetch content items matching the detected intent.
	 *
	 * @param  array<string,mixed>  $context
	 * @return array<int,array<string,mixed>>
	 */
	private function fetch_by_intent( string $intent, string $query, array $context ): array {
		$base_url = home_url( '/' );

		// Map intents to WP search targets and URL patterns.
		switch ( $intent ) {
			case 'find_specialist':
				return [
					[
						'type'      => 'specialists',
						'title'     => 'Znajdź specjalistę dla: ' . esc_html( $query ),
						'url'       => $base_url . 'specjalisci/?q=' . rawurlencode( $query ),
						'score'     => 95,
						'rationale' => 'Bezpośrednie dopasowanie do szukania specjalisty.',
					],
				];

			case 'compare':
				return [
					[
						'type'      => 'comparison',
						'title'     => 'Porównanie: ' . esc_html( $query ),
						'url'       => $base_url . 'porownania/?q=' . rawurlencode( $query ),
						'score'     => 90,
						'rationale' => 'Zapytanie zawiera sygnały porównawcze.',
					],
				];

			case 'calculate':
				return [
					[
						'type'      => 'calculator',
						'title'     => 'Kalkulator kosztów: ' . esc_html( $query ),
						'url'       => $base_url . 'kalkulatory/?q=' . rawurlencode( $query ),
						'score'     => 92,
						'rationale' => 'Zapytanie dotyczy kosztów — kalkulator pomoże.',
					],
				];

			case 'ranking':
				return [
					[
						'type'      => 'ranking',
						'title'     => 'Ranking: ' . esc_html( $query ),
						'url'       => $base_url . 'rankingi/?q=' . rawurlencode( $query ),
						'score'     => 88,
						'rationale' => 'Zapytanie szuka rekomendacji — ranking odpowie.',
					],
				];

			default:
				return [
					[
						'type'      => 'guide',
						'title'     => 'Poradnik: ' . esc_html( $query ),
						'url'       => $base_url . '?s=' . rawurlencode( $query ),
						'score'     => 75,
						'rationale' => 'Ogólna odpowiedź na pytanie.',
					],
				];
		}
	}

	/**
	 * Extract JSON from an AI response that may contain surrounding text.
	 */
	private function extract_json( string $text ): string {
		// Try to find the first complete JSON object or array.
		if ( preg_match( '/(\{.*\}|\[.*\])/s', $text, $m ) ) {
			return $m[1];
		}
		return $text;
	}
}

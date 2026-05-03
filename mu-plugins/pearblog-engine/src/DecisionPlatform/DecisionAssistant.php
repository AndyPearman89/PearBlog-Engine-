<?php
/**
 * Decision Assistant AI - Helps users make informed decisions
 *
 * @package PearBlogEngine\DecisionPlatform
 */

declare(strict_types=1);

namespace PearBlogEngine\DecisionPlatform;

use PearBlogEngine\AI\AIClient;

/**
 * Decision Assistant AI
 */
class DecisionAssistant {

	/** @var AIClient */
	private AIClient $ai;

	public function __construct( ?AIClient $ai = null ) {
		$this->ai = $ai ?? new AIClient();
	}

	/**
	 * Get recommendation based on user input
	 *
	 * @param array{need: string, budget: float|null, location: string|null} $input
	 * @return array{recommendation: string, reasoning: string, links: array<array{type: string, url: string, title: string}>}
	 */
	public function get_recommendation( array $input ): array {
		// Build prompt
		$prompt = $this->build_recommendation_prompt( $input );

		// Get AI response
		$response = $this->ai->generate( $prompt );

		// Parse response
		$data = $this->parse_recommendation_response( $response );

		// Enhance with actual links from database
		$data['links'] = $this->find_relevant_links( $input );

		return $data;
	}

	/**
	 * Build recommendation prompt
	 *
	 * @param array<string, mixed> $input
	 * @return string
	 */
	private function build_recommendation_prompt( array $input ): string {
		$budget_context = isset( $input['budget'] ) ? "Budżet: {$input['budget']} PLN" : 'Budżet: nie określony';
		$location_context = isset( $input['location'] ) ? "Lokalizacja: {$input['location']}" : 'Lokalizacja: dowolna';

		$prompt = <<<PROMPT
Użytkownik potrzebuje pomocy w podjęciu decyzji:

Potrzeba: {$input['need']}
{$budget_context}
{$location_context}

Zadanie: Dostarcz konkretną rekomendację w formacie JSON:

{
  "recommendation": "Krótka, konkretna rekomendacja (2-3 zdania)",
  "reasoning": "Uzasadnienie decyzji (3-4 zdania)",
  "next_steps": [
    "Krok 1: ...",
    "Krok 2: ...",
    "Krok 3: ..."
  ],
  "considerations": [
    "Ważny aspekt 1",
    "Ważny aspekt 2"
  ]
}

Bądź konkretny, praktyczny i pomocny.
PROMPT;

		return $prompt;
	}

	/**
	 * Parse AI response
	 *
	 * @param string $response
	 * @return array<string, mixed>
	 */
	private function parse_recommendation_response( string $response ): array {
		// Try to extract JSON
		if ( preg_match( '/\{.*\}/s', $response, $matches ) ) {
			$data = json_decode( $matches[0], true );
			if ( $data ) {
				return $data;
			}
		}

		// Fallback
		return [
			'recommendation' => $response,
			'reasoning' => '',
			'next_steps' => [],
			'considerations' => [],
		];
	}

	/**
	 * Find relevant links from database
	 *
	 * @param array<string, mixed> $input
	 * @return array<array{type: string, url: string, title: string}>
	 */
	private function find_relevant_links( array $input ): array {
		$links = [];
		$need = $input['need'];
		$location = $input['location'] ?? null;

		// Search for related articles
		$articles = get_posts( [
			'post_type' => 'post',
			'post_status' => 'publish',
			's' => $need,
			'posts_per_page' => 3,
		] );

		foreach ( $articles as $article ) {
			$links[] = [
				'type' => 'article',
				'url' => get_permalink( $article ),
				'title' => $article->post_title,
			];
		}

		// Search for comparisons
		$comparisons = get_posts( [
			'post_type' => 'pearblog_comparison',
			'post_status' => 'publish',
			's' => $need,
			'posts_per_page' => 2,
		] );

		foreach ( $comparisons as $comparison ) {
			$links[] = [
				'type' => 'comparison',
				'url' => get_permalink( $comparison ),
				'title' => $comparison->post_title,
			];
		}

		// Search for rankings
		if ( $location ) {
			$rankings = get_posts( [
				'post_type' => 'pearblog_ranking',
				'post_status' => 'publish',
				's' => $need,
				'posts_per_page' => 2,
				'meta_query' => [
					[
						'key' => 'pearblog_ranking_location',
						'value' => $location,
						'compare' => 'LIKE',
					],
				],
			] );

			foreach ( $rankings as $ranking ) {
				$links[] = [
					'type' => 'ranking',
					'url' => get_permalink( $ranking ),
					'title' => $ranking->post_title,
				];
			}
		}

		// Search for calculators
		$calculators = get_posts( [
			'post_type' => 'pearblog_calculator',
			'post_status' => 'publish',
			's' => $need,
			'posts_per_page' => 1,
		] );

		foreach ( $calculators as $calculator ) {
			$links[] = [
				'type' => 'calculator',
				'url' => get_permalink( $calculator ),
				'title' => $calculator->post_title,
			];
		}

		// Search for experts
		if ( $location ) {
			$experts = get_posts( [
				'post_type' => 'pearblog_expert',
				'post_status' => 'publish',
				'posts_per_page' => 3,
				'meta_query' => [
					'relation' => 'AND',
					[
						'key' => 'pearblog_expert_location',
						'value' => $location,
						'compare' => 'LIKE',
					],
					[
						'key' => 'pearblog_expert_verified',
						'value' => '1',
						'compare' => '=',
					],
				],
				'meta_key' => 'pearblog_expert_rating',
				'orderby' => 'meta_value_num',
				'order' => 'DESC',
			] );

			foreach ( $experts as $expert ) {
				$links[] = [
					'type' => 'expert',
					'url' => get_permalink( $expert ),
					'title' => $expert->post_title,
				];
			}
		}

		return $links;
	}

	/**
	 * Render decision assistant widget
	 *
	 * @return string HTML
	 */
	public function render_widget(): string {
		$html = '<div class="decision-assistant">';
		$html .= '<h3>🤖 Asystent decyzji AI</h3>';
		$html .= '<p>Opisz czego potrzebujesz, a pomogę Ci podjąć najlepszą decyzję.</p>';

		$html .= '<form class="decision-form">';
		$html .= '<textarea name="need" placeholder="Czego potrzebujesz? (np. chcę wyremontować łazienkę)" rows="3" required></textarea>';
		$html .= '<input type="number" name="budget" placeholder="Budżet (opcjonalnie)" step="100" />';
		$html .= '<input type="text" name="location" placeholder="Miasto (opcjonalnie)" />';
		$html .= '<button type="submit" class="btn btn-primary">Uzyskaj rekomendację</button>';
		$html .= '</form>';

		$html .= '<div class="decision-result" style="display:none;">';
		$html .= '<div class="decision-content"></div>';
		$html .= '</div>';

		$html .= '</div>';

		return $html;
	}
}

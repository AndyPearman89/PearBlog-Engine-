<?php
/**
 * Intent Detector - Detects user intent from content
 *
 * @package PearBlogEngine\DecisionPlatform
 */

declare(strict_types=1);

namespace PearBlogEngine\DecisionPlatform;

use PearBlogEngine\AI\AIClient;

/**
 * Intent Detector
 */
class IntentDetector {

	/** @var AIClient */
	private AIClient $ai;

	public function __construct( ?AIClient $ai = null ) {
		$this->ai = $ai ?? new AIClient();
	}

	/**
	 * Detect intent from content
	 *
	 * @param string $content Content to analyze
	 * @return array{intent: string, confidence: float, geo: array|null, keywords: array<string>}
	 */
	public function detect( string $content ): array {
		// Build prompt
		$prompt = $this->build_detection_prompt( $content );

		// Get AI response
		$response = $this->ai->generate( $prompt );

		// Parse response
		return $this->parse_detection_response( $response );
	}

	/**
	 * Build detection prompt
	 *
	 * @param string $content
	 * @return string
	 */
	private function build_detection_prompt( string $content ): string {
		$content_preview = substr( $content, 0, 1000 );

		$prompt = <<<PROMPT
Przeanalizuj poniższą treść i określ intent użytkownika:

"{$content_preview}"

Zwróć JSON:
{
  "intent": "informational|transactional|navigational|local",
  "confidence": 0.95,
  "geo": {
    "city": "nazwa miasta lub null",
    "region": "region lub null"
  },
  "keywords": ["słowo1", "słowo2", "słowo3"]
}

Intent types:
- informational: użytkownik szuka informacji, wiedzy
- transactional: użytkownik chce coś kupić, zamówić
- navigational: użytkownik szuka konkretnej strony/marki
- local: użytkownik szuka lokalnych usług/miejsc

Bądź precyzyjny w ocenie.
PROMPT;

		return $prompt;
	}

	/**
	 * Parse detection response
	 *
	 * @param string $response
	 * @return array<string, mixed>
	 */
	private function parse_detection_response( string $response ): array {
		// Try to extract JSON
		if ( preg_match( '/\{.*\}/s', $response, $matches ) ) {
			$data = json_decode( $matches[0], true );
			if ( $data ) {
				return $data;
			}
		}

		// Fallback: simple heuristics
		return $this->fallback_detection( $response );
	}

	/**
	 * Fallback detection using heuristics
	 *
	 * @param string $content
	 * @return array<string, mixed>
	 */
	private function fallback_detection( string $content ): array {
		$intent = 'informational';
		$confidence = 0.7;
		$geo = null;
		$keywords = [];

		// Transactional keywords
		$transactional_keywords = [ 'kup', 'cena', 'koszt', 'oferta', 'zamów', 'porównanie', 'wybór' ];
		foreach ( $transactional_keywords as $keyword ) {
			if ( stripos( $content, $keyword ) !== false ) {
				$intent = 'transactional';
				$confidence = 0.8;
				break;
			}
		}

		// Local intent keywords
		$local_keywords = [ 'warszawa', 'kraków', 'wrocław', 'poznań', 'gdańsk', 'łódź', 'szczecin', 'w mieście' ];
		foreach ( $local_keywords as $keyword ) {
			if ( stripos( $content, $keyword ) !== false ) {
				$intent = 'local';
				$confidence = 0.85;
				$geo = [
					'city' => ucfirst( $keyword ),
					'region' => '',
				];
				break;
			}
		}

		// Extract keywords (simple word frequency)
		$words = str_word_count( strtolower( $content ), 1, 'ąćęłńóśźż' );
		$word_counts = array_count_values( $words );
		arsort( $word_counts );
		$keywords = array_slice( array_keys( $word_counts ), 0, 5 );

		return [
			'intent' => $intent,
			'confidence' => $confidence,
			'geo' => $geo,
			'keywords' => $keywords,
		];
	}

	/**
	 * Enrich content based on intent
	 *
	 * @param int $post_id
	 * @param array<string, mixed> $intent_data
	 */
	public function enrich_content( int $post_id, array $intent_data ): void {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		// Save intent data
		update_post_meta( $post_id, 'pearblog_content_intent', $intent_data['intent'] );
		update_post_meta( $post_id, 'pearblog_intent_confidence', $intent_data['confidence'] );

		if ( $intent_data['geo'] ) {
			update_post_meta( $post_id, 'pearblog_geo_data', wp_json_encode( $intent_data['geo'] ) );
		}

		if ( ! empty( $intent_data['keywords'] ) ) {
			update_post_meta( $post_id, 'pearblog_keywords', implode( ',', $intent_data['keywords'] ) );
		}

		// Generate blocks based on intent
		$blocks = $this->generate_intent_blocks( $intent_data['intent'], $post );
		if ( ! empty( $blocks ) ) {
			update_post_meta( $post_id, 'pearblog_content_blocks', $blocks );
		}

		// Add FAQ for informational intent
		if ( 'informational' === $intent_data['intent'] ) {
			$this->add_faq_block( $post_id, $post );
		}

		// Add lead form for transactional/local intent
		if ( in_array( $intent_data['intent'], [ 'transactional', 'local' ], true ) ) {
			$this->add_lead_form_block( $post_id );
		}

		// Add related experts for local intent
		if ( 'local' === $intent_data['intent'] && $intent_data['geo'] ) {
			$this->add_experts_block( $post_id, $intent_data['geo']['city'] );
		}
	}

	/**
	 * Generate intent-specific blocks
	 *
	 * @param string $intent
	 * @param \WP_Post $post
	 * @return array<array{type: string, data: mixed}>
	 */
	private function generate_intent_blocks( string $intent, \WP_Post $post ): array {
		$blocks = [];

		// Intro block
		$blocks[] = [
			'type' => 'intro',
			'data' => [
				'content' => wp_trim_words( $post->post_content, 50 ),
			],
		];

		// Text block with main content
		$blocks[] = [
			'type' => 'text',
			'data' => [
				'content' => $post->post_content,
			],
		];

		return $blocks;
	}

	/**
	 * Add FAQ block
	 *
	 * @param int $post_id
	 * @param \WP_Post $post
	 */
	private function add_faq_block( int $post_id, \WP_Post $post ): void {
		// Generate FAQ using AI
		$prompt = "Wygeneruj 5 najczęściej zadawanych pytań i odpowiedzi dla artykułu: {$post->post_title}";
		$response = $this->ai->generate( $prompt );

		// Parse FAQ (simple format: Q: ... A: ...)
		$faq_items = [];
		if ( preg_match_all( '/Q:\s*(.+?)\s*A:\s*(.+?)(?=Q:|$)/s', $response, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$faq_items[] = [
					'question' => trim( $match[1] ),
					'answer' => trim( $match[2] ),
				];
			}
		}

		if ( ! empty( $faq_items ) ) {
			$blocks = get_post_meta( $post_id, 'pearblog_content_blocks', true ) ?: [];
			$blocks[] = [
				'type' => 'faq',
				'data' => [
					'title' => 'Najczęściej zadawane pytania',
					'items' => $faq_items,
				],
			];
			update_post_meta( $post_id, 'pearblog_content_blocks', $blocks );
		}
	}

	/**
	 * Add lead form block
	 *
	 * @param int $post_id
	 */
	private function add_lead_form_block( int $post_id ): void {
		$blocks = get_post_meta( $post_id, 'pearblog_content_blocks', true ) ?: [];
		$blocks[] = [
			'type' => 'lead_form',
			'data' => [
				'title' => 'Otrzymaj bezpłatną wycenę',
				'description' => 'Wypełnij formularz, a skontaktujemy Cię z najlepszymi specjalistami.',
				'category' => get_post_meta( $post_id, 'pearblog_keywords', true ),
			],
		];
		update_post_meta( $post_id, 'pearblog_content_blocks', $blocks );
		update_post_meta( $post_id, 'pearblog_lead_enabled', true );
	}

	/**
	 * Add experts block
	 *
	 * @param int $post_id
	 * @param string $city
	 */
	private function add_experts_block( int $post_id, string $city ): void {
		// Find experts in city
		$experts = get_posts( [
			'post_type' => 'pearblog_expert',
			'post_status' => 'publish',
			'posts_per_page' => 3,
			'meta_query' => [
				[
					'key' => 'pearblog_expert_location',
					'value' => $city,
					'compare' => 'LIKE',
				],
			],
			'meta_key' => 'pearblog_expert_rating',
			'orderby' => 'meta_value_num',
			'order' => 'DESC',
		] );

		if ( ! empty( $experts ) ) {
			$expert_ids = array_map( function( $expert ) {
				return $expert->ID;
			}, $experts );

			$blocks = get_post_meta( $post_id, 'pearblog_content_blocks', true ) ?: [];
			$blocks[] = [
				'type' => 'experts',
				'data' => [
					'title' => "Polecani specjaliści w {$city}",
					'expert_ids' => $expert_ids,
				],
			];
			update_post_meta( $post_id, 'pearblog_content_blocks', $blocks );
		}
	}
}

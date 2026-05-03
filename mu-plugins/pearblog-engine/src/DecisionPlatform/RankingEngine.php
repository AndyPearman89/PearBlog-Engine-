<?php
/**
 * Ranking Engine - Generates and manages TOP lists
 *
 * @package PearBlogEngine\DecisionPlatform
 */

declare(strict_types=1);

namespace PearBlogEngine\DecisionPlatform;

use PearBlogEngine\AI\AIClient;

/**
 * Ranking Engine
 */
class RankingEngine {

	/** @var AIClient */
	private AIClient $ai;

	public function __construct( ?AIClient $ai = null ) {
		$this->ai = $ai ?? new AIClient();
	}

	/**
	 * Generate ranking
	 *
	 * @param string $category Category name
	 * @param string $city City name
	 * @param int $limit Number of items
	 * @return Ranking
	 */
	public function generate( string $category, string $city, int $limit = 10 ): Ranking {
		// Build prompt for AI
		$prompt = $this->build_ranking_prompt( $category, $city, $limit );

		// Generate content via AI
		$content = $this->ai->generate( $prompt );

		// Parse AI response
		$data = $this->parse_ranking_response( $content );

		// Create ranking object
		$ranking = new Ranking();
		$ranking->title = $data['title'] ?? "Najlepsze {$category} - {$city}";
		$ranking->slug = sanitize_title( $ranking->title );
		$ranking->category = $category;
		$ranking->location = [
			'city' => $city,
			'region' => '',
		];
		$ranking->items = $data['items'] ?? [];
		$ranking->criteria = $data['criteria'] ?? [];

		return $ranking;
	}

	/**
	 * Build ranking prompt
	 *
	 * @param string $category
	 * @param string $city
	 * @param int $limit
	 * @return string
	 */
	private function build_ranking_prompt( string $category, string $city, int $limit ): string {
		$prompt = <<<PROMPT
Stwórz ranking TOP {$limit}: {$category} w {$city}.

Struktura odpowiedzi (JSON):
{
  "title": "TOP {$limit} {$category} w {$city}",
  "items": [
    {
      "name": "Nazwa firmy/produktu",
      "score": 9.5,
      "data": {
        "description": "Krótki opis...",
        "address": "Adres...",
        "phone": "Telefon...",
        "website": "https://...",
        "cta_url": "https://...",
        "highlights": ["Zaleta 1", "Zaleta 2"]
      },
      "premium": false
    }
  ],
  "criteria": ["Jakość", "Cena", "Obsługa klienta", "Dostępność"]
}

Wygeneruj realistyczny ranking z konkretnymi nazwami i danymi dla {$city}.
PROMPT;

		return $prompt;
	}

	/**
	 * Parse AI response
	 *
	 * @param string $response
	 * @return array<string, mixed>
	 */
	private function parse_ranking_response( string $response ): array {
		// Try to extract JSON from response
		if ( preg_match( '/\{.*\}/s', $response, $matches ) ) {
			$data = json_decode( $matches[0], true );
			if ( $data ) {
				return $data;
			}
		}

		// Fallback: return empty structure
		return [
			'title' => '',
			'items' => [],
			'criteria' => [],
		];
	}

	/**
	 * Find or create ranking by slug
	 *
	 * @param string $slug
	 * @return Ranking|null
	 */
	public function find_by_slug( string $slug ): ?Ranking {
		$posts = get_posts( [
			'name' => $slug,
			'post_type' => 'pearblog_ranking',
			'post_status' => 'publish',
			'posts_per_page' => 1,
		] );

		if ( empty( $posts ) ) {
			return null;
		}

		return Ranking::from_post( $posts[0] );
	}

	/**
	 * Generate programmatic rankings for multiple cities
	 *
	 * @param string $category
	 * @param array<string> $cities
	 * @return array<Ranking>
	 */
	public function generate_programmatic( string $category, array $cities ): array {
		$rankings = [];

		foreach ( $cities as $city ) {
			$ranking = $this->generate( $category, $city );
			$ranking->save();
			$rankings[] = $ranking;
		}

		return $rankings;
	}

	/**
	 * Register custom post type
	 */
	public function register_post_type(): void {
		register_post_type( 'pearblog_ranking', [
			'labels' => [
				'name' => 'Rankingi',
				'singular_name' => 'Ranking',
			],
			'public' => true,
			'has_archive' => true,
			'rewrite' => [ 'slug' => 'ranking' ],
			'supports' => [ 'title', 'editor', 'thumbnail' ],
			'show_in_rest' => true,
		] );
	}
}

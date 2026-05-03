<?php
/**
 * Comparison Engine - Generates and manages comparisons
 *
 * @package PearBlogEngine\DecisionPlatform
 */

declare(strict_types=1);

namespace PearBlogEngine\DecisionPlatform;

use PearBlogEngine\AI\AIClient;

/**
 * Comparison Engine
 */
class ComparisonEngine {

	/** @var AIClient */
	private AIClient $ai;

	public function __construct( ?AIClient $ai = null ) {
		$this->ai = $ai ?? new AIClient();
	}

	/**
	 * Generate comparison from topic
	 *
	 * @param string $topic1 First item to compare
	 * @param string $topic2 Second item to compare
	 * @param string|null $category Optional category
	 * @param string|null $city Optional city for local comparison
	 * @return Comparison
	 */
	public function generate( string $topic1, string $topic2, ?string $category = null, ?string $city = null ): Comparison {
		// Build prompt for AI
		$prompt = $this->build_comparison_prompt( $topic1, $topic2, $category, $city );

		// Generate content via AI
		$content = $this->ai->generate( $prompt );

		// Parse AI response
		$data = $this->parse_comparison_response( $content );

		// Create comparison object
		$comparison = new Comparison();
		$comparison->title = $data['title'] ?? "{$topic1} vs {$topic2}";
		$comparison->slug = sanitize_title( $comparison->title );
		$comparison->items = $data['items'] ?? [];
		$comparison->criteria = $data['criteria'] ?? [];
		$comparison->blocks = $data['blocks'] ?? [];

		if ( $city ) {
			$comparison->geo = [
				'city' => $city,
				'region' => '',
			];
		}

		// Determine winner
		$comparison->winner = $comparison->determine_winner();

		return $comparison;
	}

	/**
	 * Build comparison prompt
	 *
	 * @param string $topic1
	 * @param string $topic2
	 * @param string|null $category
	 * @param string|null $city
	 * @return string
	 */
	private function build_comparison_prompt( string $topic1, string $topic2, ?string $category, ?string $city ): string {
		$location_context = $city ? " w {$city}" : '';
		$category_context = $category ? " w kategorii {$category}" : '';

		$prompt = <<<PROMPT
Stwórz szczegółowe porównanie: {$topic1} vs {$topic2}{$category_context}{$location_context}.

Struktura odpowiedzi (JSON):
{
  "title": "Tytuł porównania",
  "items": [
    {
      "name": "{$topic1}",
      "data": {
        "cena": "...",
        "jakość": "...",
        "dostępność": "...",
        "zalety": ["..."],
        "wady": ["..."]
      }
    },
    {
      "name": "{$topic2}",
      "data": {
        "cena": "...",
        "jakość": "...",
        "dostępność": "...",
        "zalety": ["..."],
        "wady": ["..."]
      }
    }
  ],
  "criteria": [
    {"name": "cena", "weight": 0.3, "type": "numeric"},
    {"name": "jakość", "weight": 0.4, "type": "numeric"},
    {"name": "dostępność", "weight": 0.3, "type": "numeric"}
  ],
  "blocks": [
    {
      "type": "intro",
      "data": {"content": "Wprowadzenie do porównania..."}
    },
    {
      "type": "text",
      "data": {"content": "Szczegółowa analiza..."}
    },
    {
      "type": "faq",
      "data": {
        "title": "Najczęściej zadawane pytania",
        "items": [
          {"question": "...", "answer": "..."}
        ]
      }
    }
  ]
}

Wygeneruj obiektywne, szczegółowe porównanie z konkretnymi danymi i zaleceniami.
PROMPT;

		return $prompt;
	}

	/**
	 * Parse AI response
	 *
	 * @param string $response
	 * @return array<string, mixed>
	 */
	private function parse_comparison_response( string $response ): array {
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
			'blocks' => [],
		];
	}

	/**
	 * Find or create comparison by slug
	 *
	 * @param string $slug
	 * @return Comparison|null
	 */
	public function find_by_slug( string $slug ): ?Comparison {
		$posts = get_posts( [
			'name' => $slug,
			'post_type' => 'pearblog_comparison',
			'post_status' => 'publish',
			'posts_per_page' => 1,
		] );

		if ( empty( $posts ) ) {
			return null;
		}

		return Comparison::from_post( $posts[0] );
	}

	/**
	 * Generate programmatic comparisons
	 *
	 * @param array<string> $topics List of topics to compare
	 * @param string|null $city Optional city
	 * @return array<Comparison>
	 */
	public function generate_programmatic( array $topics, ?string $city = null ): array {
		$comparisons = [];

		// Generate all possible pairs
		for ( $i = 0; $i < count( $topics ); $i++ ) {
			for ( $j = $i + 1; $j < count( $topics ); $j++ ) {
				$comparison = $this->generate( $topics[ $i ], $topics[ $j ], null, $city );
				$comparison->save();
				$comparisons[] = $comparison;
			}
		}

		return $comparisons;
	}

	/**
	 * Register custom post type
	 */
	public function register_post_type(): void {
		register_post_type( 'pearblog_comparison', [
			'labels' => [
				'name' => 'Porównania',
				'singular_name' => 'Porównanie',
			],
			'public' => true,
			'has_archive' => true,
			'rewrite' => [ 'slug' => 'porownanie' ],
			'supports' => [ 'title', 'editor', 'thumbnail' ],
			'show_in_rest' => true,
		] );
	}
}

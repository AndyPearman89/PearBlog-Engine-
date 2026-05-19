<?php
/**
 * AI Decision Assistant.
 *
 * Conversational AI advisor that takes structured user context
 * (budget, location, goal) and returns a ranked list of decisions
 * with explanations, linked resources, and specialist matching.
 *
 * This is the backend for the "AI Advisor" section of the V6 homepage.
 *
 * REST endpoint: POST /pearblog/v1/ai/advise
 *
 * Request body:
 * {
 *   "query":    "string",
 *   "budget":   "number|string",
 *   "city":     "string",
 *   "goal":     "string",
 *   "history":  [ {"role":"user"|"assistant", "content":"..."} ]
 * }
 *
 * Response:
 * {
 *   "message":  "string",           – main AI response
 *   "options":  [ {label, url, type, score} ],
 *   "follow_up": ["string"],        – suggested follow-up questions
 *   "session_id": "string"
 * }
 *
 * @package PearBlogEngine\AI
 */

declare( strict_types=1 );

namespace PearBlogEngine\AI;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class DecisionAssistant {

	private const NAMESPACE = 'pearblog/v1';
	private const ROUTE     = '/ai/advise';

	/** Maximum conversation history turns forwarded to the model. */
	private const MAX_HISTORY_TURNS = 6;

	/** System prompt injected at conversation start. */
	private const SYSTEM_PROMPT = <<<PROMPT
Jesteś AI Doradcą Poradnik.pro — platformy decyzyjnej dla Polaków.
Pomagasz użytkownikom przejść od problemu do decyzji w kilka minut.
Odpowiadasz po polsku. Jesteś konkretny, zwięzły i praktyczny.
Nie jesteś chatbotem — jesteś systemem rekomendacji.
Zawsze sugeruj: porównania, kalkulatory, rankingi lub specjalistów jako kolejny krok.
Format odpowiedzi: maksymalnie 3 akapity + lista 2-3 kolejnych kroków.
PROMPT;

	private AIClient         $ai;
	private RecommendationEngine $recommender;

	public function __construct( ?AIClient $ai = null, ?RecommendationEngine $recommender = null ) {
		$this->ai          = $ai ?? new AIClient();
		$this->recommender = $recommender ?? new RecommendationEngine( $this->ai );
	}

	// ── WordPress REST registration ───────────────────────────────────────────

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_advise' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/ai/faq',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'handle_faq' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'topic' => [ 'type' => 'string', 'required' => true ],
					'count' => [ 'type' => 'integer', 'default' => 5, 'minimum' => 1, 'maximum' => 10 ],
				],
			]
		);
	}

	// ── REST handlers ─────────────────────────────────────────────────────────

	public function handle_advise( WP_REST_Request $request ): WP_REST_Response {
		$body    = $request->get_json_params() ?: [];
		$query   = sanitize_text_field( $body['query'] ?? '' );
		$budget  = sanitize_text_field( (string) ( $body['budget'] ?? '' ) );
		$city    = sanitize_text_field( $body['city']   ?? '' );
		$goal    = sanitize_text_field( $body['goal']   ?? '' );
		$history = is_array( $body['history'] ?? null ) ? $body['history'] : [];

		if ( $query === '' ) {
			return new WP_REST_Response( [ 'error' => 'query is required' ], 400 );
		}

		// Build the conversational prompt.
		$messages = $this->build_messages( $query, $budget, $city, $goal, $history );

		try {
			$ai_message = $this->ai->generate(
				$messages[ count( $messages ) - 1 ]['content'],
				[
					'max_tokens' => 500,
					'system'     => self::SYSTEM_PROMPT,
				]
			);
		} catch ( \Throwable $e ) {
			return new WP_REST_Response( [ 'error' => 'AI unavailable', 'message' => $e->getMessage() ], 503 );
		}

		// Enrich with structured recommendations.
		$context = array_filter( compact( 'budget', 'city', 'goal' ) );
		$recs    = $this->recommender->recommend_for_query( $query, $context );

		$follow_up = $this->generate_follow_up( $query, $goal );
		$session   = wp_generate_uuid4();

		return new WP_REST_Response(
			[
				'message'    => $ai_message,
				'options'    => $recs['items'],
				'intent'     => $recs['intent'],
				'follow_up'  => $follow_up,
				'session_id' => $session,
			]
		);
	}

	public function handle_faq( WP_REST_Request $request ): WP_REST_Response {
		$topic = sanitize_text_field( (string) $request->get_param( 'topic' ) );
		$count = (int) $request->get_param( 'count' );

		$faq = $this->recommender->generate_faq( $topic, $count );

		return new WP_REST_Response( [ 'topic' => $topic, 'faq' => $faq ] );
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Build a messages array for the AI call, incorporating conversation history.
	 *
	 * @param  array<int,array{role:string,content:string}> $history
	 * @return array<int,array{role:string,content:string}>
	 */
	private function build_messages( string $query, string $budget, string $city, string $goal, array $history ): array {
		// Build context-enriched user message.
		$context_parts = [];
		if ( $city !== '' )   $context_parts[] = "Miasto: $city";
		if ( $budget !== '' ) $context_parts[] = "Budżet: $budget zł";
		if ( $goal !== '' )   $context_parts[] = "Cel: $goal";

		$user_message = $query;
		if ( $context_parts ) {
			$user_message .= "\n\nKontekst: " . implode( ', ', $context_parts );
		}

		// Sanitise and truncate history.
		$safe_history = [];
		foreach ( array_slice( $history, - self::MAX_HISTORY_TURNS * 2 ) as $turn ) {
			$role    = in_array( $turn['role'] ?? '', [ 'user', 'assistant' ], true ) ? $turn['role'] : 'user';
			$content = sanitize_textarea_field( (string) ( $turn['content'] ?? '' ) );
			if ( $content !== '' ) {
				$safe_history[] = [ 'role' => $role, 'content' => $content ];
			}
		}

		$messages   = $safe_history;
		$messages[] = [ 'role' => 'user', 'content' => $user_message ];

		return $messages;
	}

	/**
	 * Generate 2-3 follow-up question suggestions.
	 *
	 * @return array<string>
	 */
	private function generate_follow_up( string $query, string $goal ): array {
		// Static lookup for common Polish-language decision patterns.
		$mapping = [
			'remont'   => [ 'Ile kosztuje remont łazienki?', 'Czy potrzebuję pozwolenia na budowę?', 'Jak wybrać ekipę remontową?' ],
			'prawnik'  => [ 'Ile kosztuje godzina prawnika?', 'Kiedy potrzebuję prawnika?', 'Jak wybrać kancelarię?' ],
			'pompa'    => [ 'Pompa ciepła czy gaz — co tańsze?', 'Jaki jest koszt montażu pompy ciepła?', 'Czy pompa ciepła się opłaca?' ],
			'budow'    => [ 'Ile kosztuje budowa domu 100m²?', 'Dom murowany czy szkieletowy?', 'Jakie pozwolenia są potrzebne?' ],
			'samochód' => [ 'Mechanik czy ASO?', 'Ile kosztuje przegląd?', 'Jak wybrać dobrego mechanika?' ],
		];

		$q = mb_strtolower( $query );
		foreach ( $mapping as $kw => $questions ) {
			if ( str_contains( $q, $kw ) ) {
				return $questions;
			}
		}

		return [
			'Jak znaleźć sprawdzonego specjalistę?',
			'Ile to może kosztować?',
			'Co powinienem sprawdzić przed podjęciem decyzji?',
		];
	}
}

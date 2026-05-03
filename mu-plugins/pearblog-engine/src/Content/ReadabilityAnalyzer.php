<?php
/**
 * Readability Analyzer – scores article content and suggests improvements.
 *
 * Computes several readability metrics on published or draft posts:
 *   - Flesch Reading Ease (0–100, higher = easier)
 *   - Flesch-Kincaid Grade Level (US school grade)
 *   - Gunning Fog Index
 *   - Average sentence length
 *   - Average syllables per word
 *   - Passive voice ratio
 *   - Transition word usage
 *
 * Results are stored in post meta and surfaced in:
 *   - An admin meta box on the post edit screen
 *   - REST endpoint for automated reporting
 *   - `pearblog_quality_scored` action hook (contributes score to QualityScorer)
 *
 * Options:
 *   pearblog_readability_min_ease  – minimum Flesch ease score to pass (default 50)
 *   pearblog_readability_max_grade – maximum Flesch-Kincaid grade level (default 12)
 *
 * Post meta keys:
 *   _pearblog_readability_ease   – Flesch Reading Ease score
 *   _pearblog_readability_grade  – Flesch-Kincaid Grade Level
 *   _pearblog_readability_fog    – Gunning Fog Index
 *   _pearblog_readability_issues – JSON array of improvement suggestions
 *
 * REST endpoints:
 *   GET  /pearblog/v1/readability/{post_id}         – fetch stored analysis
 *   POST /pearblog/v1/readability/{post_id}/analyze – run analysis on demand
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

/**
 * Analyses post readability and stores metrics in post meta.
 */
class ReadabilityAnalyzer {

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Post meta keys. */
	private const META_EASE   = '_pearblog_readability_ease';
	private const META_GRADE  = '_pearblog_readability_grade';
	private const META_FOG    = '_pearblog_readability_fog';
	private const META_ISSUES = '_pearblog_readability_issues';

	/** Transition words that indicate logical flow. */
	private const TRANSITION_WORDS = [
		'however', 'therefore', 'moreover', 'furthermore', 'additionally',
		'consequently', 'nevertheless', 'meanwhile', 'subsequently', 'although',
		'because', 'since', 'while', 'whereas', 'despite', 'instead', 'thus',
		'finally', 'first', 'second', 'third', 'also', 'next', 'then', 'lastly',
	];

	/** Passive voice auxiliaries. */
	private const PASSIVE_AUXILIARIES = [
		'was', 'were', 'is', 'are', 'been', 'being', 'am',
	];

	// -----------------------------------------------------------------------
	// Bootstrap
	// -----------------------------------------------------------------------

	/**
	 * Register hooks and REST routes.
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'save_post', [ $this, 'analyze_on_save' ], 20, 2 );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
	}

	// -----------------------------------------------------------------------
	// REST routes
	// -----------------------------------------------------------------------

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/readability/(?P<id>[\d]+)', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_get' ],
			'permission_callback' => [ $this, 'rest_permission' ],
			'args'                => [
				'id' => [ 'required' => true, 'type' => 'integer', 'minimum' => 1 ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/readability/(?P<id>[\d]+)/analyze', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_analyze' ],
			'permission_callback' => [ $this, 'rest_permission' ],
			'args'                => [
				'id' => [ 'required' => true, 'type' => 'integer', 'minimum' => 1 ],
			],
		] );
	}

	/**
	 * Permission – manage_options or valid API key.
	 */
	public function rest_permission( \WP_REST_Request $request ): bool {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		$stored = get_option( 'pearblog_api_key', '' );
		if ( '' === $stored ) {
			return false;
		}
		$header = $request->get_header( 'Authorization' ) ?? '';
		if ( str_starts_with( $header, 'Bearer ' ) ) {
			return hash_equals( $stored, trim( substr( $header, 7 ) ) );
		}
		return false;
	}

	/**
	 * GET /readability/{id} – return stored analysis.
	 */
	public function rest_get( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'id' );
		$ease    = get_post_meta( $post_id, self::META_EASE, true );

		if ( '' === $ease ) {
			return new \WP_REST_Response( [ 'error' => 'Not yet analyzed. POST to /analyze first.' ], 404 );
		}

		return new \WP_REST_Response( $this->get_stored_analysis( $post_id ), 200 );
	}

	/**
	 * POST /readability/{id}/analyze – run on-demand analysis.
	 */
	public function rest_analyze( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_REST_Response( [ 'error' => "Post #{$post_id} not found." ], 404 );
		}

		$result = $this->analyze_post( $post );
		return new \WP_REST_Response( $result, 200 );
	}

	// -----------------------------------------------------------------------
	// WP hooks
	// -----------------------------------------------------------------------

	/**
	 * Auto-analyze when a post is saved.
	 *
	 * @param int      $post_id WordPress post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function analyze_on_save( int $post_id, \WP_Post $post ): void {
		if ( 'post' !== $post->post_type ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		$this->analyze_post( $post );
	}

	/**
	 * Register admin meta box on the post edit screen.
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'pearblog_readability',
			__( 'Readability', 'pearblog-engine' ),
			[ $this, 'render_meta_box' ],
			'post',
			'side',
			'high'
		);
	}

	/**
	 * Render the readability meta box.
	 *
	 * @param \WP_Post $post Current post.
	 */
	public function render_meta_box( \WP_Post $post ): void {
		$data = $this->get_stored_analysis( $post->ID );

		if ( ! $data ) {
			echo '<p style="color:#888">' . esc_html__( 'Save or publish the post to generate readability scores.', 'pearblog-engine' ) . '</p>';
			return;
		}

		$ease      = (float) ( $data['flesch_ease'] ?? 0 );
		$grade     = (float) ( $data['flesch_kincaid_grade'] ?? 0 );
		$fog       = (float) ( $data['gunning_fog'] ?? 0 );
		$ease_col  = $ease >= 60 ? 'green' : ( $ease >= 40 ? 'orange' : 'red' );
		$grade_col = $grade <= 10 ? 'green' : ( $grade <= 12 ? 'orange' : 'red' );
		$issues    = (array) ( $data['issues'] ?? [] );
		?>
		<table style="width:100%;font-size:12px;border-collapse:collapse">
			<tr>
				<td><?php esc_html_e( 'Flesch Ease', 'pearblog-engine' ); ?></td>
				<td style="font-weight:bold;color:<?php echo esc_attr( $ease_col ); ?>"><?php echo esc_html( number_format( $ease, 1 ) ); ?>/100</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Grade Level', 'pearblog-engine' ); ?></td>
				<td style="font-weight:bold;color:<?php echo esc_attr( $grade_col ); ?>"><?php echo esc_html( number_format( $grade, 1 ) ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Fog Index', 'pearblog-engine' ); ?></td>
				<td style="font-weight:bold"><?php echo esc_html( number_format( $fog, 1 ) ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Avg Sentence', 'pearblog-engine' ); ?></td>
				<td><?php echo esc_html( number_format( (float) ( $data['avg_sentence_length'] ?? 0 ), 1 ) ); ?> words</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Passive Voice', 'pearblog-engine' ); ?></td>
				<td><?php echo esc_html( number_format( (float) ( $data['passive_ratio'] ?? 0 ) * 100, 0 ) ); ?>%</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Transitions', 'pearblog-engine' ); ?></td>
				<td><?php echo esc_html( number_format( (float) ( $data['transition_ratio'] ?? 0 ) * 100, 0 ) ); ?>%</td>
			</tr>
		</table>
		<?php if ( ! empty( $issues ) ) : ?>
		<details style="margin-top:6px">
			<summary style="cursor:pointer;font-size:11px;color:#999"><?php echo esc_html( sprintf( _n( '%d suggestion', '%d suggestions', count( $issues ), 'pearblog-engine' ), count( $issues ) ) ); ?></summary>
			<ul style="font-size:11px;padding-left:14px;margin:4px 0">
				<?php foreach ( $issues as $issue ) : ?>
				<li><?php echo esc_html( $issue ); ?></li>
				<?php endforeach; ?>
			</ul>
		</details>
		<?php endif;
	}

	// -----------------------------------------------------------------------
	// Core analysis
	// -----------------------------------------------------------------------

	/**
	 * Analyze a post and persist results in post meta.
	 *
	 * @param \WP_Post $post Post to analyze.
	 * @return array  Analysis result array.
	 */
	public function analyze_post( \WP_Post $post ): array {
		$text    = wp_strip_all_tags( $post->post_content );
		$result  = $this->analyze_text( $text );

		update_post_meta( $post->ID, self::META_EASE, $result['flesch_ease'] );
		update_post_meta( $post->ID, self::META_GRADE, $result['flesch_kincaid_grade'] );
		update_post_meta( $post->ID, self::META_FOG, $result['gunning_fog'] );
		update_post_meta( $post->ID, self::META_ISSUES, wp_json_encode( $result['issues'] ) );

		// Fire a hook so PromptOptimizer / QualityScorer can pick this up.
		do_action( 'pearblog_readability_analyzed', $post->ID, $result );

		return array_merge( [ 'post_id' => $post->ID ], $result );
	}

	/**
	 * Analyze raw text and return all metrics.
	 *
	 * @param string $text Plain text to analyze.
	 * @return array  Metrics array.
	 */
	public function analyze_text( string $text ): array {
		$text = trim( $text );

		if ( '' === $text ) {
			return $this->empty_result();
		}

		$sentences = $this->split_sentences( $text );
		$words     = $this->split_words( $text );
		$sen_count = max( 1, count( $sentences ) );
		$wrd_count = max( 1, count( $words ) );

		$syllables    = array_sum( array_map( [ $this, 'count_syllables' ], $words ) );
		$complex_words = count( array_filter( $words, fn( $w ) => $this->count_syllables( $w ) >= 3 ) );

		$avg_sentence  = $wrd_count / $sen_count;
		$avg_syllables = $syllables / $wrd_count;

		// Flesch Reading Ease: 206.835 – 1.015 × (words/sentences) – 84.6 × (syllables/words)
		$flesch_ease = 206.835 - ( 1.015 * $avg_sentence ) - ( 84.6 * $avg_syllables );
		$flesch_ease = min( 100.0, max( 0.0, round( $flesch_ease, 1 ) ) );

		// Flesch-Kincaid Grade Level: 0.39 × (words/sentences) + 11.8 × (syllables/words) – 15.59
		$fk_grade = ( 0.39 * $avg_sentence ) + ( 11.8 * $avg_syllables ) - 15.59;
		$fk_grade = max( 0.0, round( $fk_grade, 1 ) );

		// Gunning Fog Index: 0.4 × ((words/sentences) + 100 × (complex_words/words))
		$fog = 0.4 * ( $avg_sentence + 100 * ( $complex_words / $wrd_count ) );
		$fog = max( 0.0, round( $fog, 1 ) );

		$passive_ratio    = $this->passive_ratio( $sentences );
		$transition_ratio = $this->transition_ratio( $sentences );

		$issues = $this->build_issues(
			$flesch_ease,
			$fk_grade,
			$avg_sentence,
			$passive_ratio,
			$transition_ratio
		);

		return [
			'flesch_ease'          => $flesch_ease,
			'flesch_kincaid_grade' => $fk_grade,
			'gunning_fog'          => $fog,
			'word_count'           => $wrd_count,
			'sentence_count'       => $sen_count,
			'avg_sentence_length'  => round( $avg_sentence, 1 ),
			'avg_syllables_per_word' => round( $avg_syllables, 2 ),
			'passive_ratio'        => round( $passive_ratio, 2 ),
			'transition_ratio'     => round( $transition_ratio, 2 ),
			'issues'               => $issues,
		];
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Return an empty analysis result.
	 *
	 * @return array
	 */
	private function empty_result(): array {
		return [
			'flesch_ease'            => 0.0,
			'flesch_kincaid_grade'   => 0.0,
			'gunning_fog'            => 0.0,
			'word_count'             => 0,
			'sentence_count'         => 0,
			'avg_sentence_length'    => 0.0,
			'avg_syllables_per_word' => 0.0,
			'passive_ratio'          => 0.0,
			'transition_ratio'       => 0.0,
			'issues'                 => [],
		];
	}

	/**
	 * Retrieve the stored analysis from post meta.
	 *
	 * @param int $post_id Post ID.
	 * @return array|null  Analysis array or null if not yet run.
	 */
	private function get_stored_analysis( int $post_id ): ?array {
		$ease = get_post_meta( $post_id, self::META_EASE, true );
		if ( '' === $ease ) {
			return null;
		}

		$issues_json = get_post_meta( $post_id, self::META_ISSUES, true );
		$issues      = $issues_json ? (array) json_decode( $issues_json, true ) : [];

		return [
			'post_id'              => $post_id,
			'flesch_ease'          => (float) $ease,
			'flesch_kincaid_grade' => (float) get_post_meta( $post_id, self::META_GRADE, true ),
			'gunning_fog'          => (float) get_post_meta( $post_id, self::META_FOG, true ),
			'issues'               => $issues,
		];
	}

	/**
	 * Split text into sentences.
	 *
	 * @param string $text Input text.
	 * @return string[]
	 */
	private function split_sentences( string $text ): array {
		$text      = preg_replace( '/\s+/', ' ', $text );
		$sentences = preg_split( '/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY );
		return $sentences ?: [];
	}

	/**
	 * Split text into words (alpha-only tokens).
	 *
	 * @param string $text Input text.
	 * @return string[]
	 */
	private function split_words( string $text ): array {
		preg_match_all( '/\b[a-zA-Z\']+\b/', strtolower( $text ), $matches );
		return $matches[0] ?? [];
	}

	/**
	 * Count the syllables in a single English word using a heuristic.
	 *
	 * @param string $word Single lowercase word.
	 * @return int  Syllable count (minimum 1).
	 */
	public function count_syllables( string $word ): int {
		$word = strtolower( preg_replace( '/[^a-z]/', '', $word ) );
		$len  = strlen( $word );
		if ( $len <= 3 ) {
			return 1;
		}

		// Remove trailing silent 'e'.
		$word = rtrim( $word, 'e' );

		// Count vowel groups.
		preg_match_all( '/[aeiouy]+/', $word, $matches );
		$count = count( $matches[0] ?? [] );

		return max( 1, $count );
	}

	/**
	 * Estimate passive voice ratio across sentences.
	 *
	 * Looks for "auxiliary verb + past participle" patterns (simplified).
	 *
	 * @param string[] $sentences Array of sentences.
	 * @return float  Ratio 0.0–1.0.
	 */
	private function passive_ratio( array $sentences ): float {
		if ( empty( $sentences ) ) {
			return 0.0;
		}

		$passive = 0;
		$aux_pattern = implode( '|', self::PASSIVE_AUXILIARIES );
		$pattern     = '/\b(' . $aux_pattern . ')\s+\w+ed\b/i';

		foreach ( $sentences as $sentence ) {
			if ( preg_match( $pattern, $sentence ) ) {
				++$passive;
			}
		}

		return $passive / count( $sentences );
	}

	/**
	 * Estimate ratio of sentences that contain at least one transition word.
	 *
	 * @param string[] $sentences Array of sentences.
	 * @return float  Ratio 0.0–1.0.
	 */
	private function transition_ratio( array $sentences ): float {
		if ( empty( $sentences ) ) {
			return 0.0;
		}

		$with_transition = 0;

		foreach ( $sentences as $sentence ) {
			$lower = strtolower( $sentence );
			foreach ( self::TRANSITION_WORDS as $word ) {
				if ( str_contains( $lower, $word ) ) {
					++$with_transition;
					break;
				}
			}
		}

		return $with_transition / count( $sentences );
	}

	/**
	 * Build a list of actionable improvement suggestions.
	 *
	 * @param float $ease             Flesch ease.
	 * @param float $grade            FK grade level.
	 * @param float $avg_sentence     Average sentence length.
	 * @param float $passive_ratio    Passive voice ratio.
	 * @param float $transition_ratio Transition word ratio.
	 * @return string[]  List of suggestions.
	 */
	private function build_issues(
		float $ease,
		float $grade,
		float $avg_sentence,
		float $passive_ratio,
		float $transition_ratio
	): array {
		$issues = [];
		$min_ease  = (float) get_option( 'pearblog_readability_min_ease', 50 );
		$max_grade = (float) get_option( 'pearblog_readability_max_grade', 12 );

		if ( $ease < $min_ease ) {
			$issues[] = sprintf(
				__( 'Flesch Ease is %.0f (target ≥ %.0f). Simplify vocabulary and shorten sentences.', 'pearblog-engine' ),
				$ease,
				$min_ease
			);
		}

		if ( $grade > $max_grade ) {
			$issues[] = sprintf(
				__( 'Grade level is %.1f (target ≤ %.0f). Use simpler words and shorter sentences.', 'pearblog-engine' ),
				$grade,
				$max_grade
			);
		}

		if ( $avg_sentence > 25 ) {
			$issues[] = sprintf(
				__( 'Average sentence length is %.0f words. Break long sentences into shorter ones (aim for <20 words).', 'pearblog-engine' ),
				$avg_sentence
			);
		}

		if ( $passive_ratio > 0.20 ) {
			$issues[] = sprintf(
				__( '%.0f%% of sentences use passive voice. Rewrite as active voice for clarity.', 'pearblog-engine' ),
				$passive_ratio * 100
			);
		}

		if ( $transition_ratio < 0.20 ) {
			$issues[] = __( 'Few transition words detected. Add words like "however", "therefore", "meanwhile" to improve flow.', 'pearblog-engine' );
		}

		return $issues;
	}
}

<?php
/**
 * AI Decision Quiz Engine – interactive decision quizzes with lead capture.
 *
 * Generates personalized recommendations based on user answers (e.g.,
 * "Which insurance is right for me?").
 *
 * Features:
 *  - Quiz configuration stored in custom post type `pearblog_quiz`.
 *  - AI-powered recommendation generation based on collected answers.
 *  - Lead capture form shown after quiz completion.
 *  - Shortcode: [pearblog_quiz id="123"] – embed quiz anywhere.
 *  - REST endpoints for quiz rendering and submission.
 *
 * REST endpoints:
 *   GET  /pearblog/v1/quiz/{quiz_id}              – get quiz questions
 *   POST /pearblog/v1/quiz/{quiz_id}/submit        – submit answers, get recommendation
 *
 * @package PearBlogEngine\DecisionPlatform
 */

declare(strict_types=1);

namespace PearBlogEngine\DecisionPlatform;

use PearBlogEngine\AI\AIClient;

/**
 * Decision quiz engine with AI-powered recommendations.
 */
class QuizEngine {

	/** Custom post type for quizzes. */
	public const POST_TYPE = 'pearblog_quiz';

	/** Post meta: quiz questions JSON. */
	public const META_QUESTIONS = 'pearblog_quiz_questions';

	/** Post meta: lead capture settings. */
	public const META_LEAD_CAPTURE = 'pearblog_quiz_lead_capture';

	/** Option key: captured leads (ring buffer). */
	public const OPTION_LEADS = 'pearblog_quiz_leads';

	/** Max leads to store in WP option. */
	private const MAX_LEADS = 500;

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register WordPress hooks.
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_shortcode( 'pearblog_quiz', [ $this, 'render_shortcode' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_meta' ] );
	}

	/**
	 * Register the quiz custom post type.
	 */
	public function register_post_type(): void {
		register_post_type( self::POST_TYPE, [
			'label'              => __( 'Quizzes', 'pearblog-engine' ),
			'labels'             => [
				'name'          => __( 'Quizzes', 'pearblog-engine' ),
				'singular_name' => __( 'Quiz', 'pearblog-engine' ),
				'add_new_item'  => __( 'Add New Quiz', 'pearblog-engine' ),
			],
			'public'             => false,
			'show_ui'            => true,
			'show_in_menu'       => 'pearblog-engine',
			'supports'           => [ 'title' ],
			'show_in_rest'       => true,
		] );
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/quiz/(?P<quiz_id>\d+)', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_get_quiz' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( self::NAMESPACE, '/quiz/(?P<quiz_id>\d+)/submit', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_submit' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( self::NAMESPACE, '/quiz/leads', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_get_leads' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );
	}

	// -----------------------------------------------------------------------
	// Quiz management
	// -----------------------------------------------------------------------

	/**
	 * Get quiz questions for a specific quiz post.
	 *
	 * @param int $quiz_id Quiz post ID.
	 * @return array<int, array{question: string, answers: string[], type: string}>
	 */
	public function get_questions( int $quiz_id ): array {
		$raw = get_post_meta( $quiz_id, self::META_QUESTIONS, true );
		if ( ! is_array( $raw ) ) {
			$raw = json_decode( $raw ?: '[]', true );
		}
		return is_array( $raw ) ? $raw : [];
	}

	/**
	 * Generate AI-powered recommendation based on quiz answers.
	 *
	 * @param int   $quiz_id Quiz post ID.
	 * @param array<string,string> $answers User answers (question_index → answer).
	 * @return string Personalized recommendation text.
	 */
	public function generate_recommendation( int $quiz_id, array $answers ): string {
		$quiz      = get_post( $quiz_id );
		$questions = $this->get_questions( $quiz_id );
		$quiz_title = $quiz ? $quiz->post_title : 'decision quiz';

		if ( empty( $questions ) || empty( $answers ) ) {
			return __( 'Thank you for completing the quiz. Contact us for a personalized recommendation.', 'pearblog-engine' );
		}

		// Build Q&A context for AI.
		$qa_context = '';
		foreach ( $questions as $idx => $q ) {
			$answer     = $answers[ $idx ] ?? $answers[ (string) $idx ] ?? 'No answer';
			$qa_context .= "Q: {$q['question']}\nA: {$answer}\n\n";
		}

		$prompt = "Based on the following quiz answers for \"{$quiz_title}\", provide a specific, personalized recommendation (2-3 paragraphs). Be concrete and helpful.

Quiz Answers:
{$qa_context}

Provide a recommendation that:
1. Directly addresses their specific situation based on their answers
2. Recommends a clear course of action
3. Explains the reasoning briefly";

		$ai = new AIClient();
		return $ai->generate( $prompt, 400 );
	}

	// -----------------------------------------------------------------------
	// Lead capture
	// -----------------------------------------------------------------------

	/**
	 * Store a captured lead.
	 *
	 * @param int    $quiz_id       Quiz post ID.
	 * @param string $email         Lead email.
	 * @param string $name          Lead name.
	 * @param string $recommendation Generated recommendation.
	 */
	public function capture_lead( int $quiz_id, string $email, string $name, string $recommendation ): void {
		$leads = (array) get_option( self::OPTION_LEADS, [] );

		$lead = [
			'quiz_id'        => $quiz_id,
			'quiz_title'     => get_the_title( $quiz_id ),
			'email'          => sanitize_email( $email ),
			'name'           => sanitize_text_field( $name ),
			'recommendation' => $recommendation,
			'captured_at'    => time(),
			'ip'             => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
		];

		$leads[] = $lead;

		if ( count( $leads ) > self::MAX_LEADS ) {
			$leads = array_slice( $leads, -self::MAX_LEADS );
		}

		update_option( self::OPTION_LEADS, $leads );

		/**
		 * Action: pearblog_quiz_lead_captured
		 *
		 * @param int    $quiz_id Quiz post ID.
		 * @param array  $lead    Lead data.
		 */
		do_action( 'pearblog_quiz_lead_captured', $quiz_id, $lead );

		// Send recommendation email.
		if ( '' !== $email ) {
			wp_mail(
				$email,
				sprintf( __( 'Your personalized recommendation from %s', 'pearblog-engine' ), get_bloginfo( 'name' ) ),
				wp_strip_all_tags( $recommendation )
			);
		}
	}

	// -----------------------------------------------------------------------
	// Shortcode
	// -----------------------------------------------------------------------

	/**
	 * Render quiz shortcode.
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_shortcode( array $atts ): string {
		$atts    = shortcode_atts( [ 'id' => '0' ], $atts );
		$quiz_id = (int) $atts['id'];

		if ( ! $quiz_id ) {
			return '';
		}

		$questions   = $this->get_questions( $quiz_id );
		$quiz_title  = get_the_title( $quiz_id );
		$rest_url    = esc_url( rest_url( "pearblog/v1/quiz/{$quiz_id}/submit" ) );
		$nonce       = wp_create_nonce( 'pearblog_quiz' );

		ob_start();
		?>
		<div class="pearblog-quiz" id="pearblog-quiz-<?php echo esc_attr( $quiz_id ); ?>" data-quiz-id="<?php echo esc_attr( $quiz_id ); ?>" data-endpoint="<?php echo esc_url( rest_url( "pearblog/v1/quiz/{$quiz_id}/submit" ) ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>">
			<h2 class="pearblog-quiz__title"><?php echo esc_html( $quiz_title ); ?></h2>

			<form class="pearblog-quiz__form">
				<?php foreach ( $questions as $idx => $q ) : ?>
				<div class="pearblog-quiz__question" data-idx="<?php echo esc_attr( $idx ); ?>">
					<p class="pearblog-quiz__q-text"><strong><?php echo esc_html( $q['question'] ); ?></strong></p>
					<div class="pearblog-quiz__answers">
						<?php foreach ( ( $q['answers'] ?? [] ) as $answer ) : ?>
						<label class="pearblog-quiz__answer">
							<input type="radio" name="q_<?php echo esc_attr( $idx ); ?>" value="<?php echo esc_attr( $answer ); ?>">
							<?php echo esc_html( $answer ); ?>
						</label>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endforeach; ?>

				<div class="pearblog-quiz__lead-capture">
					<p><strong><?php esc_html_e( 'Get your personalized recommendation:', 'pearblog-engine' ); ?></strong></p>
					<input type="text" name="lead_name" placeholder="<?php esc_attr_e( 'Your name', 'pearblog-engine' ); ?>" class="pearblog-quiz__input">
					<input type="email" name="lead_email" placeholder="<?php esc_attr_e( 'Your email', 'pearblog-engine' ); ?>" class="pearblog-quiz__input">
				</div>

				<button type="submit" class="pearblog-quiz__submit button button-primary"><?php esc_html_e( 'Get My Recommendation →', 'pearblog-engine' ); ?></button>
			</form>

			<div class="pearblog-quiz__result" style="display:none;"></div>
		</div>

		<style>
		.pearblog-quiz { max-width: 600px; margin: 24px 0; font-family: inherit; }
		.pearblog-quiz__question { margin-bottom: 20px; padding: 16px; background: #f8f9fa; border-radius: 8px; }
		.pearblog-quiz__answers { display: flex; flex-direction: column; gap: 8px; margin-top: 8px; }
		.pearblog-quiz__answer { display: flex; align-items: center; gap: 8px; cursor: pointer; }
		.pearblog-quiz__input { display: block; width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
		.pearblog-quiz__lead-capture { margin-top: 20px; padding: 16px; background: #f0f4ff; border-radius: 8px; }
		.pearblog-quiz__submit { margin-top: 16px; padding: 12px 24px; }
		.pearblog-quiz__result { margin-top: 20px; padding: 20px; background: #e8f5e9; border-radius: 8px; line-height: 1.6; }
		</style>

		<script>
		(function(){
			var quiz = document.getElementById('pearblog-quiz-<?php echo esc_js( (string) $quiz_id ); ?>');
			if (!quiz) return;
			var form = quiz.querySelector('.pearblog-quiz__form');
			var result = quiz.querySelector('.pearblog-quiz__result');
			form.addEventListener('submit', function(e){
				e.preventDefault();
				var answers = {};
				quiz.querySelectorAll('[name^="q_"]').forEach(function(el){
					if(el.checked) answers[el.name.replace('q_','')] = el.value;
				});
				var data = {
					answers: answers,
					lead_name: form.querySelector('[name="lead_name"]').value,
					lead_email: form.querySelector('[name="lead_email"]').value,
					_wpnonce: quiz.dataset.nonce
				};
				fetch(quiz.dataset.endpoint, {
					method: 'POST',
					headers: {'Content-Type':'application/json','X-WP-Nonce': quiz.dataset.nonce},
					body: JSON.stringify(data)
				}).then(r=>r.json()).then(function(res){
					if(res.recommendation){
						result.innerHTML = '<p><strong><?php esc_html_e("Your recommendation:", "pearblog-engine"); ?></strong></p><p>'+res.recommendation+'</p>';
						result.style.display = 'block';
						form.style.display = 'none';
					}
				});
			});
		})();
		</script>
		<?php

		return ob_get_clean() ?: '';
	}

	// -----------------------------------------------------------------------
	// Meta boxes
	// -----------------------------------------------------------------------

	/**
	 * Add meta boxes to quiz post type.
	 */
	public function add_meta_boxes(): void {
		add_meta_box(
			'pearblog_quiz_questions',
			__( 'Quiz Questions (JSON)', 'pearblog-engine' ),
			[ $this, 'render_questions_meta_box' ],
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render questions meta box.
	 *
	 * @param \WP_Post $post Current post object.
	 */
	public function render_questions_meta_box( \WP_Post $post ): void {
		$questions = get_post_meta( $post->ID, self::META_QUESTIONS, true );
		$json      = is_array( $questions ) ? wp_json_encode( $questions, JSON_PRETTY_PRINT ) : ( $questions ?: '[]' );
		wp_nonce_field( 'pearblog_quiz_save', 'pearblog_quiz_nonce' );
		?>
		<p><?php esc_html_e( 'Enter quiz questions as JSON array:', 'pearblog-engine' ); ?></p>
		<textarea name="pearblog_quiz_questions" rows="10" style="width:100%;font-family:monospace;"><?php echo esc_textarea( $json ); ?></textarea>
		<p class="description"><?php esc_html_e( 'Format: [{"question":"...","answers":["A","B","C"]}]', 'pearblog-engine' ); ?></p>
		<?php
	}

	/**
	 * Save quiz meta.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_meta( int $post_id ): void {
		if ( ! isset( $_POST['pearblog_quiz_nonce'] ) || ! wp_verify_nonce( $_POST['pearblog_quiz_nonce'], 'pearblog_quiz_save' ) ) {
			return;
		}

		$raw = $_POST['pearblog_quiz_questions'] ?? '';
		$decoded = json_decode( wp_unslash( $raw ), true );

		if ( is_array( $decoded ) ) {
			update_post_meta( $post_id, self::META_QUESTIONS, $decoded );
		}
	}

	// -----------------------------------------------------------------------
	// REST callbacks
	// -----------------------------------------------------------------------

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_get_quiz( \WP_REST_Request $request ) {
		$quiz_id = (int) $request->get_param( 'quiz_id' );
		$post    = get_post( $quiz_id );

		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return new \WP_Error( 'quiz_not_found', 'Quiz not found.', [ 'status' => 404 ] );
		}

		return new \WP_REST_Response( [
			'quiz_id'   => $quiz_id,
			'title'     => $post->post_title,
			'questions' => $this->get_questions( $quiz_id ),
		], 200 );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_submit( \WP_REST_Request $request ) {
		$quiz_id    = (int) $request->get_param( 'quiz_id' );
		$answers    = (array) ( $request->get_param( 'answers' ) ?? [] );
		$lead_name  = sanitize_text_field( (string) ( $request->get_param( 'lead_name' ) ?? '' ) );
		$lead_email = sanitize_email( (string) ( $request->get_param( 'lead_email' ) ?? '' ) );

		if ( ! get_post( $quiz_id ) ) {
			return new \WP_Error( 'quiz_not_found', 'Quiz not found.', [ 'status' => 404 ] );
		}

		$recommendation = $this->generate_recommendation( $quiz_id, $answers );

		if ( '' !== $lead_email ) {
			$this->capture_lead( $quiz_id, $lead_email, $lead_name, $recommendation );
		}

		return new \WP_REST_Response( [
			'recommendation' => $recommendation,
			'lead_captured'  => '' !== $lead_email,
		], 200 );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_get_leads( \WP_REST_Request $request ): \WP_REST_Response {
		$leads = (array) get_option( self::OPTION_LEADS, [] );
		return new \WP_REST_Response( [
			'count' => count( $leads ),
			'leads' => array_slice( array_reverse( $leads ), 0, 50 ),
		], 200 );
	}

	/**
	 * Permission callback.
	 */
	public function admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}
}

<?php
/**
 * CRO Engine – Conversion Rate Optimisation via A/B testing.
 *
 * Provides A/B testing for CTAs (buttons, headlines, offer banners) injected
 * into post content.  Tracks impressions and conversions, calculates
 * statistical significance, and auto-promotes the winning variant.
 *
 * Storage (WP options / transients):
 *   pearblog_cro_experiments  – array of active experiments
 *   pearblog_cro_stats_{exp}  – per-experiment impression/conversion counts
 *
 * REST endpoints:
 *   GET    /pearblog/v1/cro/experiments            – list experiments
 *   POST   /pearblog/v1/cro/experiments            – create experiment
 *   DELETE /pearblog/v1/cro/experiments/{id}       – delete experiment
 *   POST   /pearblog/v1/cro/track                  – record impression or conversion
 *   GET    /pearblog/v1/cro/experiments/{id}/stats – get stats for experiment
 *
 * @package PearBlogEngine\Monetization
 */

declare(strict_types=1);

namespace PearBlogEngine\Monetization;

/**
 * A/B testing and conversion tracking for CTAs and content elements.
 */
class CROEngine {

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** WP option key for experiment definitions. */
	private const OPTION_EXPERIMENTS = 'pearblog_cro_experiments';

	/** Minimum impressions before evaluating significance. */
	private const MIN_IMPRESSIONS = 100;

	/** Significance threshold (chi-square p < 0.05 ≈ z-score ≥ 1.96). */
	private const SIGNIFICANCE_Z = 1.96;

	// -----------------------------------------------------------------------
	// Bootstrap
	// -----------------------------------------------------------------------

	/**
	 * Register hooks and REST routes.
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_filter( 'the_content', [ $this, 'inject_cta_variants' ] );

		// Daily cron to check statistical significance and promote winners.
		if ( ! wp_next_scheduled( 'pearblog_cro_evaluate' ) ) {
			wp_schedule_event( time(), 'daily', 'pearblog_cro_evaluate' );
		}
		add_action( 'pearblog_cro_evaluate', [ $this, 'evaluate_and_promote' ] );
	}

	// -----------------------------------------------------------------------
	// REST routes
	// -----------------------------------------------------------------------

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/cro/experiments', [
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'rest_list' ],
				'permission_callback' => [ $this, 'rest_permission' ],
			],
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'rest_create' ],
				'permission_callback' => [ $this, 'rest_permission' ],
				'args'                => [
					'name'     => [ 'required' => true, 'type' => 'string' ],
					'variants' => [ 'required' => true, 'type' => 'array' ],
					'post_id'  => [ 'required' => false, 'type' => 'integer' ],
				],
			],
		] );

		register_rest_route( self::NAMESPACE, '/cro/experiments/(?P<id>[a-zA-Z0-9_-]+)', [
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'rest_stats' ],
				'permission_callback' => [ $this, 'rest_permission' ],
			],
			[
				'methods'             => 'DELETE',
				'callback'            => [ $this, 'rest_delete' ],
				'permission_callback' => [ $this, 'rest_permission' ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/cro/track', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_track' ],
			'permission_callback' => '__return_true',  // Public – tracked by JS.
			'args'                => [
				'experiment_id' => [ 'required' => true, 'type' => 'string' ],
				'variant'       => [ 'required' => true, 'type' => 'string' ],
				'event'         => [ 'required' => true, 'type' => 'string', 'enum' => [ 'impression', 'conversion' ] ],
			],
		] );
	}

	/**
	 * Permission callback – manage_options.
	 */
	public function rest_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	// -----------------------------------------------------------------------
	// REST handlers
	// -----------------------------------------------------------------------

	/**
	 * GET /cro/experiments – list all experiments.
	 */
	public function rest_list( \WP_REST_Request $request ): \WP_REST_Response {
		$experiments = $this->get_experiments();
		foreach ( $experiments as &$exp ) {
			$exp['stats'] = $this->get_stats( $exp['id'] );
		}
		return new \WP_REST_Response( array_values( $experiments ), 200 );
	}

	/**
	 * POST /cro/experiments – create experiment.
	 */
	public function rest_create( \WP_REST_Request $request ): \WP_REST_Response {
		$experiments = $this->get_experiments();

		$id = 'cro_' . wp_generate_uuid4();
		$experiments[ $id ] = [
			'id'         => $id,
			'name'       => sanitize_text_field( $request->get_param( 'name' ) ),
			'variants'   => array_map( 'sanitize_text_field', (array) $request->get_param( 'variants' ) ),
			'post_id'    => (int) ( $request->get_param( 'post_id' ) ?: 0 ),
			'status'     => 'active',
			'winner'     => null,
			'created_at' => time(),
		];

		update_option( self::OPTION_EXPERIMENTS, $experiments );

		return new \WP_REST_Response( $experiments[ $id ], 201 );
	}

	/**
	 * GET /cro/experiments/{id} – stats.
	 */
	public function rest_stats( \WP_REST_Request $request ): \WP_REST_Response {
		$id  = $request->get_param( 'id' );
		$exp = $this->get_experiment( $id );

		if ( ! $exp ) {
			return new \WP_REST_Response( [ 'error' => 'Experiment not found.' ], 404 );
		}

		return new \WP_REST_Response(
			array_merge( $exp, [ 'stats' => $this->get_stats( $id ) ] ),
			200
		);
	}

	/**
	 * DELETE /cro/experiments/{id}
	 */
	public function rest_delete( \WP_REST_Request $request ): \WP_REST_Response {
		$id          = $request->get_param( 'id' );
		$experiments = $this->get_experiments();

		if ( ! isset( $experiments[ $id ] ) ) {
			return new \WP_REST_Response( [ 'error' => 'Experiment not found.' ], 404 );
		}

		unset( $experiments[ $id ] );
		update_option( self::OPTION_EXPERIMENTS, $experiments );
		delete_option( "pearblog_cro_stats_{$id}" );

		return new \WP_REST_Response( [ 'deleted' => true ], 200 );
	}

	/**
	 * POST /cro/track – record impression or conversion.
	 */
	public function rest_track( \WP_REST_Request $request ): \WP_REST_Response {
		$exp_id  = sanitize_key( $request->get_param( 'experiment_id' ) );
		$variant = sanitize_key( $request->get_param( 'variant' ) );
		$event   = $request->get_param( 'event' );

		$this->record_event( $exp_id, $variant, $event );

		return new \WP_REST_Response( [ 'recorded' => true ], 200 );
	}

	// -----------------------------------------------------------------------
	// Content injection
	// -----------------------------------------------------------------------

	/**
	 * Inject the assigned variant HTML for each active experiment into post content.
	 *
	 * Looks for <!-- cro:{experiment_id} --> placeholders in content and
	 * replaces them with the assigned variant HTML.
	 *
	 * @param string $content Post content.
	 * @return string Modified content.
	 */
	public function inject_cta_variants( string $content ): string {
		if ( is_admin() ) {
			return $content;
		}

		$experiments = $this->get_experiments();

		foreach ( $experiments as $exp ) {
			$placeholder = "<!-- cro:{$exp['id']} -->";

			if ( ! str_contains( $content, $placeholder ) ) {
				continue;
			}

			$variant_html = $this->pick_variant_html( $exp );
			$content      = str_replace( $placeholder, $variant_html, $content );
		}

		return $content;
	}

	// -----------------------------------------------------------------------
	// Statistical significance & auto-promote
	// -----------------------------------------------------------------------

	/**
	 * Evaluate active experiments and promote winners where significance is met.
	 */
	public function evaluate_and_promote(): void {
		$experiments = $this->get_experiments();
		$changed     = false;

		foreach ( $experiments as $id => &$exp ) {
			if ( 'active' !== $exp['status'] ) {
				continue;
			}

			$winner = $this->find_winner( $exp );

			if ( $winner ) {
				$exp['status'] = 'completed';
				$exp['winner'] = $winner;
				$changed       = true;

				do_action( 'pearblog_cro_winner_promoted', $id, $winner, $exp );
			}
		}
		unset( $exp );

		if ( $changed ) {
			update_option( self::OPTION_EXPERIMENTS, $experiments );
		}
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Return all experiments.
	 *
	 * @return array<string, array>
	 */
	private function get_experiments(): array {
		return (array) get_option( self::OPTION_EXPERIMENTS, [] );
	}

	/**
	 * Return a single experiment by ID.
	 *
	 * @param string $id Experiment ID.
	 * @return array|null
	 */
	private function get_experiment( string $id ): ?array {
		$experiments = $this->get_experiments();
		return $experiments[ $id ] ?? null;
	}

	/**
	 * Return impression/conversion stats for an experiment.
	 *
	 * @param string $id Experiment ID.
	 * @return array<string, array{impressions: int, conversions: int, cvr: float}>
	 */
	private function get_stats( string $id ): array {
		return (array) get_option( "pearblog_cro_stats_{$id}", [] );
	}

	/**
	 * Record a single impression or conversion event for a variant.
	 *
	 * @param string $exp_id  Experiment ID.
	 * @param string $variant Variant key.
	 * @param string $event   'impression' or 'conversion'.
	 */
	private function record_event( string $exp_id, string $variant, string $event ): void {
		$stats = $this->get_stats( $exp_id );

		if ( ! isset( $stats[ $variant ] ) ) {
			$stats[ $variant ] = [ 'impressions' => 0, 'conversions' => 0, 'cvr' => 0.0 ];
		}

		if ( 'impression' === $event ) {
			++$stats[ $variant ]['impressions'];
		} elseif ( 'conversion' === $event ) {
			++$stats[ $variant ]['conversions'];
		}

		$imp = $stats[ $variant ]['impressions'];
		$con = $stats[ $variant ]['conversions'];
		$stats[ $variant ]['cvr'] = $imp > 0 ? round( $con / $imp * 100, 2 ) : 0.0;

		update_option( "pearblog_cro_stats_{$exp_id}", $stats );
	}

	/**
	 * Pick a variant for the current visitor (round-robin via session cookie).
	 *
	 * @param array $exp Experiment definition.
	 * @return string  Variant HTML/text or empty string.
	 */
	private function pick_variant_html( array $exp ): string {
		if ( empty( $exp['variants'] ) ) {
			return '';
		}

		// Completed experiments always show the winner.
		if ( 'completed' === $exp['status'] && $exp['winner'] ) {
			return wp_kses_post( $exp['winner'] );
		}

		$cookie_key = 'pb_cro_' . $exp['id'];

		if ( isset( $_COOKIE[ $cookie_key ] ) ) {
			$idx = (int) $_COOKIE[ $cookie_key ];
		} else {
			$idx = random_int( 0, count( $exp['variants'] ) - 1 );
			// Set cookie for 30 days.
			setcookie( $cookie_key, (string) $idx, time() + 30 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
		}

		$variant_html = $exp['variants'][ $idx ] ?? $exp['variants'][0];

		// Auto-record impression.
		$this->record_event( $exp['id'], (string) $idx, 'impression' );

		return wp_kses_post( $variant_html );
	}

	/**
	 * Two-proportion z-test to find a statistically significant winner.
	 *
	 * @param array $exp Experiment definition.
	 * @return string|null  Winning variant HTML or null if not yet significant.
	 */
	private function find_winner( array $exp ): ?string {
		$stats    = $this->get_stats( $exp['id'] );
		$variants = $exp['variants'] ?? [];

		if ( count( $stats ) < 2 ) {
			return null;
		}

		$best_cvr  = -1.0;
		$best_idx  = null;
		$best_stat = null;

		foreach ( $stats as $idx => $data ) {
			if ( $data['impressions'] < self::MIN_IMPRESSIONS ) {
				return null; // Not enough data.
			}
			if ( $data['cvr'] > $best_cvr ) {
				$best_cvr  = $data['cvr'];
				$best_idx  = $idx;
				$best_stat = $data;
			}
		}

		if ( null === $best_idx ) {
			return null;
		}

		// Compare best against all others using two-proportion z-test.
		foreach ( $stats as $idx => $data ) {
			if ( $idx === $best_idx ) {
				continue;
			}

			$n1 = $best_stat['impressions'];
			$p1 = $best_stat['conversions'] / max( 1, $n1 );
			$n2 = $data['impressions'];
			$p2 = $data['conversions'] / max( 1, $n2 );
			$p  = ( $best_stat['conversions'] + $data['conversions'] ) / max( 1, $n1 + $n2 );

			$denom = sqrt( $p * ( 1 - $p ) * ( 1 / $n1 + 1 / $n2 ) );
			if ( $denom < 1e-10 ) {
				return null;
			}

			$z = abs( ( $p1 - $p2 ) / $denom );
			if ( $z < self::SIGNIFICANCE_Z ) {
				return null; // Not yet significant.
			}
		}

		return $variants[ (int) $best_idx ] ?? null;
	}
}

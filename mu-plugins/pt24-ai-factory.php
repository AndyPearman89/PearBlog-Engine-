<?php
/**
 * PT24.PRO — AI Factory Engine
 *
 * Programmatic page generator for 1 000+ landing pages at scale.
 *
 * Features:
 *  - Template-based content generation (no API key needed) with 20 anti-duplication variants
 *  - OpenAI-powered AI generation (gpt-4o-mini) using the official Master Prompt
 *  - Batch processing via WP-Cron (5 pages/minute — safe for shared hosting)
 *  - REST API endpoint for n8n automation (/wp-json/pt24/v2/generate)
 *  - CSV import (usluga,miasto rows)
 *  - Full progress tracking: published / queued / target
 *
 * Host-guarded: loaded only on the PT24 install.
 *
 * @package PearBlog\PT24
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PT24_AI_Factory {

	const VERSION         = '2.0.0';
	const QUEUE_OPTION    = 'pt24_factory_queue';
	const STATS_OPTION    = 'pt24_factory_stats';
	const CRON_HOOK       = 'pt24_factory_cron';
	const REST_NAMESPACE  = 'pt24/v2';
	const BATCH_SIZE      = 5;   // pages per cron tick

	/* =====================================================================
	   BOOTSTRAP
	   ===================================================================== */

	public static function register(): void {
		// AJAX handlers (admin panel)
		add_action( 'wp_ajax_pt24_factory_generate',    [ __CLASS__, 'ajax_generate_single' ] );
		add_action( 'wp_ajax_pt24_factory_batch_csv',   [ __CLASS__, 'ajax_batch_csv' ] );
		add_action( 'wp_ajax_pt24_factory_run_queue',   [ __CLASS__, 'ajax_run_queue' ] );
		add_action( 'wp_ajax_pt24_factory_stats',       [ __CLASS__, 'ajax_stats' ] );
		add_action( 'wp_ajax_pt24_factory_clear_queue', [ __CLASS__, 'ajax_clear_queue' ] );

		// REST API (n8n integration)
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest' ] );

		// WP-Cron batch processor
		// NOTE: add_filter('cron_schedules') MUST run before wp_schedule_event so
		// that 'every_minute' is available when wp_get_schedules() is called internally.
		add_filter( 'cron_schedules', [ __CLASS__, 'add_cron_schedule' ] );
		add_action( self::CRON_HOOK, [ __CLASS__, 'process_queue_batch' ] );
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'every_minute', self::CRON_HOOK );
		}
	}

	public static function add_cron_schedule( array $schedules ): array {
		$schedules['every_minute'] = [
			'interval' => 60,
			'display'  => __( 'Every Minute', 'pearblog-engine' ),
		];
		return $schedules;
	}

	/* =====================================================================
	   CORE — generate a single landing page
	   ===================================================================== */

	/**
	 * Generate (or update) a pt24_landing CPT post.
	 *
	 * @param string $service  Service slug (e.g. 'mechanik')
	 * @param string $city     City slug (e.g. 'ruda-slaska')
	 * @param bool   $use_ai   Use OpenAI API (requires api key in settings)
	 * @return int|WP_Error   Post ID on success, WP_Error on failure
	 */
	public static function generate_landing( string $service, string $city, bool $use_ai = false ) {
		// Validate against allowlists
		$cities   = PT24_Scale_Data::cities();
		$services = PT24_Scale_Data::services();
		if ( ! isset( $cities[ $city ], $services[ $service ] ) ) {
			return new WP_Error( 'invalid_pair', "Unknown city '$city' or service '$service'." );
		}

		// Check for existing post — skip if already published
		$existing = self::find_existing( $service, $city );
		if ( $existing > 0 ) {
			return $existing;
		}

		// Variant (deterministic, 0-19)
		$variant = PT24_Scale_Data::select_variant( $city, $service );

		// Generate content
		$ai_content = '';
		if ( $use_ai ) {
			$ai_content = self::generate_ai_content( $service, $city );
		}

		// Build post title
		$svc_name  = PT24_Scale_Data::service_name( $service );
		$city_name = PT24_Scale_Data::city_name( $city );

		$post_id = wp_insert_post( [
			'post_title'   => $svc_name . ' ' . $city_name,
			'post_name'    => $city . '-' . $service,
			'post_content' => $ai_content,
			'post_status'  => 'publish',
			'post_type'    => 'pt24_landing',
		] );

		if ( is_wp_error( $post_id ) || $post_id <= 0 ) {
			return is_wp_error( $post_id ) ? $post_id : new WP_Error( 'insert_failed', 'wp_insert_post failed.' );
		}

		// Meta
		update_post_meta( $post_id, 'pt24_city',    $city );
		update_post_meta( $post_id, 'pt24_service', $service );
		update_post_meta( $post_id, 'pt24_variant', $variant );
		update_post_meta( $post_id, 'pt24_factory', '1' );
		// Store display names so the landing template renders correct diacritics
		if ( class_exists( 'PT24_Scale_Data' ) ) {
			update_post_meta( $post_id, 'pt24_city_display',    \PT24_Scale_Data::city_name( $city ) );
			update_post_meta( $post_id, 'pt24_service_display', \PT24_Scale_Data::service_name( $service ) );
		}
		if ( '' !== $ai_content ) {
			update_post_meta( $post_id, '_pt24_ai_content', '1' );
		}

		// Update stats counter
		$stats            = (array) get_option( self::STATS_OPTION, [] );
		$stats['total']   = ( $stats['total'] ?? 0 ) + 1;
		$stats['last_ts'] = time();
		update_option( self::STATS_OPTION, $stats, false );

		return $post_id;
	}

	/**
	 * Find existing pt24_landing post for a city/service pair.
	 */
	private static function find_existing( string $service, string $city ): int {
		global $wpdb;
		$post_name = $city . '-' . $service;
		$id = $wpdb->get_var( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			 WHERE post_type = 'pt24_landing'
			   AND post_status IN ('publish','draft')
			   AND post_name = %s
			 LIMIT 1",
			$post_name
		) );
		return (int) $id;
	}

	/* =====================================================================
	   OPENAI — Master Prompt generation
	   ===================================================================== */

	/**
	 * Call OpenAI API with the official PT24 Master Prompt.
	 * Returns raw HTML content string, or '' on failure.
	 */
	private static function generate_ai_content( string $service, string $city ): string {
		$api_key = (string) get_option( 'pt24_openai_api_key', '' );
		if ( '' === $api_key ) {
			return '';
		}

		$svc_name  = PT24_Scale_Data::service_name( $service );
		$city_name = PT24_Scale_Data::city_name( $city );

		// === Master Prompt (exact from spec, extended for JSON output) ===
		$prompt = "Jesteś systemem SEO dla platformy pt24.pro.\n"
			. "Wygeneruj stronę lokalną.\n\n"
			. "DANE:\n"
			. "USŁUGA: {$svc_name}\n"
			. "MIASTO: {$city_name}\n\n"
			. "WYMAGANIA:\n"
			. "- 1000-1500 słów\n"
			. "- Język prosty, bez marketingowego bełkotu\n"
			. "- Naturalne użycie nazwy miasta\n"
			. "- Maksymalna konwersja leadów\n\n"
			. "STRUKTURA (w tej kolejności):\n"
			. "1. H1 z usługą i miastem\n"
			. "2. Opis problemów klienta (2-3 akapity)\n"
			. "3. Zakres usług (lista)\n"
			. "4. Dlaczego warto (4 punkty)\n"
			. "5. Najczęstsze awarie/problemy (lista)\n"
			. "6. FAQ (dokładnie 5 pytań i odpowiedzi)\n"
			. "7. CTA telefoniczne\n\n"
			. "Odpowiedz w formacie JSON:\n"
			. '{"h1":"...","content":"...","meta_title":"...","meta_description":"...","faq":[{"q":"...","a":"..."}]}' . "\n"
			. "Gdzie 'content' to pełna treść strony w HTML (bez H1 — nagłówek osobno).";

		$body = wp_json_encode( [
			'model'           => 'gpt-4o-mini',
			'messages'        => [
				[ 'role' => 'system', 'content' => 'Jesteś polskim ekspertem SEO tworzącym treści na stronę www. Odpowiadasz WYŁĄCZNIE w formacie JSON.' ],
				[ 'role' => 'user',   'content' => $prompt ],
			],
			'max_tokens'      => 3000,
			'temperature'     => 0.7,
			'response_format' => [ 'type' => 'json_object' ],
		] );

		$response = wp_safe_remote_post( 'https://api.openai.com/v1/chat/completions', [
			'headers' => [
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			],
			'body'    => $body,
			'timeout' => 60,
		] );

		if ( is_wp_error( $response ) ) {
			return '';
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== (int) $code ) {
			return '';
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return '';
		}

		$ai_json = json_decode( $data['choices'][0]['message']['content'] ?? '{}', true );
		if ( JSON_ERROR_NONE !== json_last_error() || empty( $ai_json['content'] ) ) {
			return '';
		}

		// Assemble HTML: store meta in custom post meta
		$post_name = sanitize_title( $city . '-' . $service );
		// We return the content and store meta separately after post creation
		// (stored as transient keyed by post_name for post-insert pickup)
		set_transient( 'pt24_ai_meta_' . $post_name, $ai_json, 600 );

		return wp_kses_post( $ai_json['content'] );
	}

	/* =====================================================================
	   BATCH QUEUE — WP-Cron based (shared hosting safe)
	   ===================================================================== */

	/**
	 * Add city/service pairs to the async queue.
	 *
	 * @param array $pairs [ ['service'=>'..','city'=>'..'], ... ]
	 */
	public static function queue_pairs( array $pairs ): int {
		$queue = (array) get_option( self::QUEUE_OPTION, [] );

		$added = 0;
		foreach ( $pairs as $pair ) {
			$service = sanitize_key( $pair['service'] ?? '' );
			$city    = sanitize_title( $pair['city'] ?? '' );
			if ( '' === $service || '' === $city ) {
				continue;
			}
			// Deduplicate by key
			$key = $service . '|' . $city;
			if ( ! isset( $queue[ $key ] ) ) {
				$queue[ $key ] = [ 'service' => $service, 'city' => $city, 'use_ai' => ! empty( $pair['use_ai'] ) ];
				$added++;
			}
		}

		update_option( self::QUEUE_OPTION, $queue, false );
		return $added;
	}

	/**
	 * Called by WP-Cron. Processes self::BATCH_SIZE items from the queue.
	 */
	public static function process_queue_batch(): int {
		$queue = (array) get_option( self::QUEUE_OPTION, [] );
		if ( empty( $queue ) ) {
			return 0;
		}

		$batch  = array_splice( $queue, 0, self::BATCH_SIZE );
		update_option( self::QUEUE_OPTION, $queue, false );

		$done = 0;
		foreach ( $batch as $pair ) {
			$result = self::generate_landing(
				(string) $pair['service'],
				(string) $pair['city'],
				! empty( $pair['use_ai'] )
			);
			if ( ! is_wp_error( $result ) ) {
				$done++;
			}
		}

		return $done;
	}

	/**
	 * Queue ALL city × service combinations not yet published.
	 * Returns number of pairs queued.
	 */
	public static function queue_all_combinations( bool $use_ai = false ): int {
		$pairs = [];
		foreach ( array_keys( PT24_Scale_Data::cities() ) as $city ) {
			foreach ( array_keys( PT24_Scale_Data::services() ) as $service ) {
				if ( self::find_existing( $service, $city ) === 0 ) {
					$pairs[] = [ 'service' => $service, 'city' => $city, 'use_ai' => $use_ai ];
				}
			}
		}
		return self::queue_pairs( $pairs );
	}

	/**
	 * Parse CSV input (usluga,miasto rows) and queue pairs.
	 * First row may be a header.
	 *
	 * @param string $csv_text Raw CSV text
	 * @return array{queued:int, skipped:int, errors:string[]}
	 */
	public static function queue_from_csv( string $csv_text, bool $use_ai = false ): array {
		$lines   = preg_split( '/\r?\n/', trim( $csv_text ) );
		$queued  = 0;
		$skipped = 0;
		$errors  = [];

		$cities   = PT24_Scale_Data::cities();
		$services = PT24_Scale_Data::services();

		foreach ( $lines as $idx => $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			$cols = str_getcsv( $line );
			if ( count( $cols ) < 2 ) {
				continue;
			}

			$service_raw = strtolower( trim( $cols[0] ) );
			$city_raw    = sanitize_title( trim( $cols[1] ) );

			// Skip header row
			if ( 0 === $idx && ( 'usluga' === $service_raw || 'usługa' === $service_raw ) ) {
				continue;
			}

			// Normalise service slug
			$service_slug = sanitize_key( str_replace( ' ', '-', $service_raw ) );
			$city_slug    = $city_raw;

			// Validate
			if ( ! isset( $services[ $service_slug ] ) ) {
				$errors[] = "Nieznana usługa: '$service_raw' (linia " . ( $idx + 1 ) . ')';
				$skipped++;
				continue;
			}
			if ( ! isset( $cities[ $city_slug ] ) ) {
				$errors[] = "Nieznane miasto: '$city_raw' (linia " . ( $idx + 1 ) . ')';
				$skipped++;
				continue;
			}

			$added = self::queue_pairs( [ [ 'service' => $service_slug, 'city' => $city_slug, 'use_ai' => $use_ai ] ] );
			$queued += $added;
			if ( 0 === $added ) {
				$skipped++; // already queued or exists
			}
		}

		return [ 'queued' => $queued, 'skipped' => $skipped, 'errors' => $errors ];
	}

	/* =====================================================================
	   STATS
	   ===================================================================== */

	public static function get_stats(): array {
		global $wpdb;

		$published = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts}
			 WHERE post_type = 'pt24_landing' AND post_status = 'publish'"
		);

		$ai_gen = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->postmeta}
			 WHERE meta_key = '_pt24_ai_content'"
		);

		$factory_gen = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->postmeta}
			 WHERE meta_key = 'pt24_factory' AND meta_value = '1'"
		);

		$queue     = (array) get_option( self::QUEUE_OPTION, [] );
		$cities    = count( PT24_Scale_Data::cities() );
		$services  = count( PT24_Scale_Data::services() );
		$target    = $cities * $services;
		$progress  = $target > 0 ? round( $published / $target * 100, 1 ) : 0.0;

		return [
			'published'    => $published,
			'ai_generated' => $ai_gen,
			'factory_gen'  => $factory_gen,
			'queue_size'   => count( $queue ),
			'cities'       => $cities,
			'services'     => $services,
			'target'       => $target,
			'progress_pct' => $progress,
			'remaining'    => max( 0, $target - $published ),
			'has_api_key'  => '' !== (string) get_option( 'pt24_openai_api_key', '' ),
		];
	}

	/* =====================================================================
	   REST API — n8n integration
	   POST /wp-json/pt24/v2/generate   { service, city, use_ai? }
	   POST /wp-json/pt24/v2/batch      { pairs: [{service,city},...] }
	   GET  /wp-json/pt24/v2/stats
	   ===================================================================== */

	public static function register_rest(): void {
		register_rest_route( self::REST_NAMESPACE, '/generate', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'rest_generate' ],
			'permission_callback' => [ __CLASS__, 'rest_auth' ],
			'args'                => [
				'service' => [ 'required' => true,  'sanitize_callback' => 'sanitize_key' ],
				'city'    => [ 'required' => true,  'sanitize_callback' => 'sanitize_title' ],
				'use_ai'  => [ 'required' => false, 'default' => false ],
			],
		] );

		register_rest_route( self::REST_NAMESPACE, '/batch', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'rest_batch' ],
			'permission_callback' => [ __CLASS__, 'rest_auth' ],
		] );

		register_rest_route( self::REST_NAMESPACE, '/stats', [
			'methods'             => 'GET',
			'callback'            => [ __CLASS__, 'rest_stats' ],
			'permission_callback' => [ __CLASS__, 'rest_auth' ],
		] );

		register_rest_route( self::REST_NAMESPACE, '/services', [
			'methods'             => 'GET',
			'callback'            => function() {
				return new WP_REST_Response( PT24_Scale_Data::services(), 200 );
			},
			'permission_callback' => '__return_true',
		] );

		register_rest_route( self::REST_NAMESPACE, '/cities', [
			'methods'             => 'GET',
			'callback'            => function() {
				return new WP_REST_Response( PT24_Scale_Data::cities(), 200 );
			},
			'permission_callback' => '__return_true',
		] );
	}

	/** Authenticate REST requests using a shared token in settings. */
	public static function rest_auth( WP_REST_Request $request ): bool {
		// Accept WP cookie auth (admins in browser)
		if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			return true;
		}
		// Accept token header (n8n / automation)
		$token  = (string) get_option( 'pt24_webhook_token', '' );
		$header = (string) $request->get_header( 'X-PT24-Token' );
		return '' !== $token && hash_equals( $token, $header );
	}

	public static function rest_generate( WP_REST_Request $request ): WP_REST_Response {
		$service = (string) $request->get_param( 'service' );
		$city    = (string) $request->get_param( 'city' );
		$use_ai  = (bool)   $request->get_param( 'use_ai' );

		$result = self::generate_landing( $service, $city, $use_ai );

		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response( [ 'error' => $result->get_error_message() ], 400 );
		}

		return new WP_REST_Response( [
			'success' => true,
			'post_id' => $result,
			'url'     => home_url( "/{$city}/{$service}/" ),
		], 201 );
	}

	public static function rest_batch( WP_REST_Request $request ): WP_REST_Response {
		$body   = $request->get_json_params();
		$pairs  = is_array( $body['pairs'] ?? null ) ? $body['pairs'] : [];
		$use_ai = ! empty( $body['use_ai'] );
		$csv    = (string) ( $body['csv'] ?? '' );

		if ( '' !== $csv ) {
			$result = self::queue_from_csv( $csv, $use_ai );
		} elseif ( ! empty( $pairs ) ) {
			foreach ( $pairs as &$p ) {
				$p['use_ai'] = $use_ai;
			}
			unset( $p );
			$queued = self::queue_pairs( $pairs );
			$result = [ 'queued' => $queued, 'skipped' => 0, 'errors' => [] ];
		} else {
			return new WP_REST_Response( [ 'error' => 'Provide either pairs[] or csv.' ], 400 );
		}

		return new WP_REST_Response( [
			'success' => true,
			'queued'  => $result['queued'],
			'skipped' => $result['skipped'],
			'errors'  => $result['errors'],
		], 202 );
	}

	public static function rest_stats( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( self::get_stats(), 200 );
	}

	/* =====================================================================
	   AJAX HANDLERS (admin panel)
	   ===================================================================== */

	public static function ajax_generate_single(): void {
		check_ajax_referer( 'pt24_factory_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$service = sanitize_key( wp_unslash( $_POST['service'] ?? '' ) );
		$city    = sanitize_title( wp_unslash( $_POST['city'] ?? '' ) );
		$use_ai  = ! empty( $_POST['use_ai'] );

		$result = self::generate_landing( $service, $city, $use_ai );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success( [
			'post_id' => $result,
			'url'     => home_url( "/{$city}/{$service}/" ),
			'stats'   => self::get_stats(),
		] );
	}

	public static function ajax_batch_csv(): void {
		check_ajax_referer( 'pt24_factory_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$csv    = sanitize_textarea_field( wp_unslash( $_POST['csv'] ?? '' ) );
		$use_ai = ! empty( $_POST['use_ai'] );

		if ( '' === $csv ) {
			// Queue all combinations
			$queued = self::queue_all_combinations( $use_ai );
			wp_send_json_success( [
				'queued'  => $queued,
				'skipped' => 0,
				'errors'  => [],
				'message' => "Dodano {$queued} stron do kolejki.",
				'stats'   => self::get_stats(),
			] );
			return;
		}

		$result = self::queue_from_csv( $csv, $use_ai );
		$msg    = "Dodano {$result['queued']} do kolejki, pominięto {$result['skipped']}.";

		wp_send_json_success( [
			'queued'  => $result['queued'],
			'skipped' => $result['skipped'],
			'errors'  => $result['errors'],
			'message' => $msg,
			'stats'   => self::get_stats(),
		] );
	}

	public static function ajax_run_queue(): void {
		check_ajax_referer( 'pt24_factory_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$done = self::process_queue_batch();

		wp_send_json_success( [
			'generated' => $done,
			'message'   => "Wygenerowano {$done} stron w tej paczce.",
			'stats'     => self::get_stats(),
		] );
	}

	public static function ajax_stats(): void {
		check_ajax_referer( 'pt24_factory_nonce', 'nonce' );
		wp_send_json_success( self::get_stats() );
	}

	public static function ajax_clear_queue(): void {
		check_ajax_referer( 'pt24_factory_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}
		update_option( self::QUEUE_OPTION, [], false );
		wp_send_json_success( [ 'message' => 'Kolejka wyczyszczona.', 'stats' => self::get_stats() ] );
	}
}

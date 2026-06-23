<?php
/**
 * PT24.PRO — Google Places Seeder
 *
 * Fetches real business data from Google Places API (Text Search + Details)
 * and creates/updates pt24_firm CPT profiles.
 *
 * Features:
 *  - Maps PT24 service slugs → Polish search terms for Google
 *  - Text Search returns up to 20 places; we take top N (configurable)
 *  - Details call fetches phone, website, rating, address
 *  - Idempotent: firms deduped by Google Place ID (pt24_firm_place_id meta)
 *  - Cost-aware: 1 text-search + N detail calls per service×city pair
 *  - REST endpoint: POST /wp-json/pt24/v2/places-seed
 *  - AJAX endpoint: wp_ajax_pt24_places_seed
 *
 * Host-guarded: PT24 install only.
 *
 * @package PearBlog\PT24
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PT24_Places_Seeder {

	const OPTION_API_KEY    = 'pt24_google_places_api_key';
	const REST_NAMESPACE    = 'pt24/v2';
	const MAX_PER_SEARCH    = 5;  // Firms saved per service×city search.
	const QUEUE_OPTION      = 'pt24_places_queue';

	/** Maps PT24 service slug → Polish Google search term. */
	private static array $search_terms = [
		'hydraulik'          => 'hydraulik',
		'elektryk'           => 'elektryk',
		'mechanik'           => 'mechanik samochodowy serwis',
		'fotowoltaika'       => 'instalacja fotowoltaiczna panele',
		'pompa-ciepla'       => 'pompa ciepła montaż serwis',
		'remont-lazienki'    => 'remont łazienki firma',
		'laweta'             => 'laweta pomoc drogowa',
		'wulkanizacja'       => 'wulkanizacja wymiana opon',
		'klimatyzacja'       => 'montaż klimatyzacji serwis',
		'instalacje-gazowe'  => 'instalacje gazowe certyfikat',
	];

	/* =====================================================================
	   BOOTSTRAP
	   ===================================================================== */

	public static function register(): void {
		add_action( 'wp_ajax_pt24_places_seed',       [ __CLASS__, 'ajax_seed' ] );
		add_action( 'wp_ajax_pt24_places_seed_stats', [ __CLASS__, 'ajax_stats' ] );
		add_action( 'wp_ajax_pt24_places_run_queue',  [ __CLASS__, 'ajax_run_queue' ] );
		add_action( 'rest_api_init',                  [ __CLASS__, 'register_rest' ] );
		add_action( 'pt24_places_cron',               [ __CLASS__, 'process_queue' ] );

		if ( ! wp_next_scheduled( 'pt24_places_cron' ) ) {
			wp_schedule_event( time(), 'every_minute', 'pt24_places_cron' );
		}
	}

	/* =====================================================================
	   GOOGLE PLACES API
	   ===================================================================== */

	/**
	 * Text Search — returns up to 20 places matching a query.
	 *
	 * @param string $query   e.g. "hydraulik Warszawa"
	 * @param string $api_key Google Places API key
	 * @return array  Array of place stubs or empty array on error.
	 */
	public static function text_search( string $query, string $api_key ): array {
		$url = add_query_arg( [
			'query'    => $query,
			'language' => 'pl',
			'region'   => 'pl',
			'type'     => 'establishment',
			'key'      => $api_key,
		], 'https://maps.googleapis.com/maps/api/place/textsearch/json' );

		$response = wp_safe_remote_get( $url, [ 'timeout' => 15 ] );
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return [];
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! isset( $data['results'] ) ) {
			return [];
		}

		return (array) $data['results'];
	}

	/**
	 * Place Details — fetches phone, website, rating, address for a place_id.
	 *
	 * @param string $place_id Google Place ID
	 * @param string $api_key
	 * @return array|null  Details array or null on error.
	 */
	public static function get_details( string $place_id, string $api_key ): ?array {
		$url = add_query_arg( [
			'place_id' => $place_id,
			'fields'   => 'name,formatted_address,formatted_phone_number,website,rating,user_ratings_total,opening_hours',
			'language' => 'pl',
			'key'      => $api_key,
		], 'https://maps.googleapis.com/maps/api/place/details/json' );

		$response = wp_safe_remote_get( $url, [ 'timeout' => 15 ] );
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( JSON_ERROR_NONE !== json_last_error() || empty( $data['result'] ) ) {
			return null;
		}

		return $data['result'];
	}

	/* =====================================================================
	   FIRM CREATION
	   ===================================================================== */

	/**
	 * Seed firms for a service × city pair from Google Places.
	 * Returns number of firms created/updated.
	 */
	public static function seed_service_city(
		string $service_slug,
		string $city_slug,
		string $api_key,
		int    $max = self::MAX_PER_SEARCH
	): int {
		// Resolve display names
		$service_name = class_exists( 'PT24_Scale_Data' )
			? PT24_Scale_Data::service_name( $service_slug )
			: ucfirst( str_replace( '-', ' ', $service_slug ) );

		$city_name = class_exists( 'PT24_Scale_Data' )
			? PT24_Scale_Data::city_name( $city_slug )
			: ucfirst( str_replace( '-', ' ', $city_slug ) );

		$search_term = self::$search_terms[ $service_slug ] ?? str_replace( '-', ' ', $service_slug );
		$query       = $search_term . ' ' . $city_name;

		$places = self::text_search( $query, $api_key );
		if ( empty( $places ) ) {
			return 0;
		}

		$saved = 0;
		foreach ( array_slice( $places, 0, $max ) as $place ) {
			$place_id = (string) ( $place['place_id'] ?? '' );
			if ( '' === $place_id ) {
				continue;
			}

			// Deduplicate by Google Place ID.
			if ( self::firm_exists_by_place_id( $place_id ) ) {
				continue;
			}

			// Fetch details (phone, website, etc.)
			$details = self::get_details( $place_id, $api_key );
			if ( null === $details ) {
				$details = $place; // Fallback to text-search stub
			}

			$name    = sanitize_text_field( $details['name'] ?? $place['name'] ?? '' );
			$address = sanitize_text_field( $details['formatted_address'] ?? $place['formatted_address'] ?? '' );
			$phone   = sanitize_text_field( $details['formatted_phone_number'] ?? '' );
			$website = esc_url_raw( $details['website'] ?? '' );
			$rating  = number_format( (float) ( $details['rating'] ?? $place['rating'] ?? 5.0 ), 1 );
			$reviews = absint( $details['user_ratings_total'] ?? $place['user_ratings_total'] ?? 0 );

			if ( '' === $name ) {
				continue;
			}

			$slug    = sanitize_title( $name . '-' . $city_slug );
			$content = '<p>' . esc_html( $name ) . ' — ' . esc_html( $service_name ) . ' w mieście ' . esc_html( $city_name ) . '.</p>';
			if ( '' !== $address ) {
				$content .= '<p>📍 ' . esc_html( $address ) . '</p>';
			}

			// Create or update CPT post
			$existing_post = get_posts( [
				'post_type'        => 'pt24_firm',
				'name'             => $slug,
				'numberposts'      => 1,
				'suppress_filters' => true,
			] );

			if ( ! empty( $existing_post ) ) {
				$post_id = (int) $existing_post[0]->ID;
				wp_update_post( [
					'ID'           => $post_id,
					'post_title'   => $name,
					'post_content' => $content,
					'post_status'  => 'publish',
				] );
			} else {
				$post_id = (int) wp_insert_post( [
					'post_title'   => $name,
					'post_name'    => $slug,
					'post_content' => $content,
					'post_status'  => 'publish',
					'post_type'    => 'pt24_firm',
				] );
			}

			if ( $post_id <= 0 ) {
				continue;
			}

			// Store meta
			update_post_meta( $post_id, 'pt24_firm_city',          $city_slug );
			update_post_meta( $post_id, 'pt24_firm_city_name',     $city_name );
			update_post_meta( $post_id, 'pt24_firm_services',      $service_slug );
			update_post_meta( $post_id, 'pt24_firm_rating',        $rating );
			update_post_meta( $post_id, 'pt24_firm_jobs',          (string) $reviews );
			update_post_meta( $post_id, 'pt24_firm_phone',         $phone );
			update_post_meta( $post_id, 'pt24_firm_website',       $website );
			update_post_meta( $post_id, 'pt24_firm_address',       $address );
			update_post_meta( $post_id, 'pt24_firm_place_id',      $place_id );
			update_post_meta( $post_id, 'pt24_firm_established',   (string) gmdate( 'Y' ) );
			update_post_meta( $post_id, 'pt24_firm_source',        'google_places' );
			update_post_meta( $post_id, '_pt24_google_seeded',     '1' );

			$saved++;
		}

		return $saved;
	}

	/** Check if a firm with this Google Place ID already exists. */
	private static function firm_exists_by_place_id( string $place_id ): bool {
		global $wpdb;
		return (bool) $wpdb->get_var( $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta}
			 WHERE meta_key = 'pt24_firm_place_id' AND meta_value = %s LIMIT 1",
			$place_id
		) );
	}

	/* =====================================================================
	   QUEUE / BATCH
	   ===================================================================== */

	/** Queue all city × service pairs that don't have Google Places data yet. */
	public static function queue_all( string $api_key, array $service_filter = [], array $city_filter = [] ): int {
		$all_services = class_exists( 'PT24_Scale_Data' ) ? array_keys( PT24_Scale_Data::services() ) : array_keys( self::$search_terms );
		$all_cities   = class_exists( 'PT24_Scale_Data' ) ? array_keys( PT24_Scale_Data::cities() )   : [];

		$services = empty( $service_filter ) ? $all_services : array_intersect( $all_services, $service_filter );
		$cities   = empty( $city_filter )    ? $all_cities   : array_intersect( $all_cities, $city_filter );

		$queue = (array) get_option( self::QUEUE_OPTION, [] );
		$added = 0;

		foreach ( $services as $service ) {
			foreach ( $cities as $city ) {
				$key = $service . '|' . $city;
				if ( ! isset( $queue[ $key ] ) ) {
					$queue[ $key ] = [ 'service' => $service, 'city' => $city, 'api_key' => $api_key ];
					$added++;
				}
			}
		}

		update_option( self::QUEUE_OPTION, $queue, false );
		return $added;
	}

	/** WP-Cron: process 3 pairs per tick (API calls are slow). */
	public static function process_queue(): int {
		$api_key = (string) get_option( self::OPTION_API_KEY, '' );
		if ( '' === $api_key ) {
			return 0;
		}

		$queue = (array) get_option( self::QUEUE_OPTION, [] );
		if ( empty( $queue ) ) {
			return 0;
		}

		$batch = array_splice( $queue, 0, 3 );
		update_option( self::QUEUE_OPTION, $queue, false );

		$total = 0;
		foreach ( $batch as $pair ) {
			$total += self::seed_service_city(
				(string) $pair['service'],
				(string) $pair['city'],
				$api_key
			);
		}

		// Update stats
		$stats            = (array) get_option( 'pt24_places_stats', [] );
		$stats['total']   = ( $stats['total'] ?? 0 ) + $total;
		$stats['pairs']   = ( $stats['pairs'] ?? 0 ) + count( $batch );
		$stats['last_ts'] = time();
		update_option( 'pt24_places_stats', $stats, false );

		return $total;
	}

	/* =====================================================================
	   STATS
	   ===================================================================== */

	public static function get_stats(): array {
		global $wpdb;

		$total_firms = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='pt24_firm' AND post_status='publish'"
		);
		$places_firms = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key='_pt24_google_seeded' AND meta_value='1'"
		);
		$queue       = (array) get_option( self::QUEUE_OPTION, [] );
		$has_api_key = '' !== (string) get_option( self::OPTION_API_KEY, '' );

		$all_cities   = class_exists( 'PT24_Scale_Data' ) ? count( PT24_Scale_Data::cities() )   : 0;
		$all_services = class_exists( 'PT24_Scale_Data' ) ? count( PT24_Scale_Data::services() ) : 0;

		return [
			'total_firms'    => $total_firms,
			'places_firms'   => $places_firms,
			'queue_size'     => count( $queue ),
			'has_api_key'    => $has_api_key,
			'possible_pairs' => $all_cities * $all_services,
		];
	}

	/* =====================================================================
	   REST API
	   ===================================================================== */

	public static function register_rest(): void {
		register_rest_route( self::REST_NAMESPACE, '/places-seed', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'rest_seed' ],
			'permission_callback' => function( WP_REST_Request $r ) {
				if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) return true;
				$token  = (string) get_option( 'pt24_webhook_token', '' );
				$header = (string) $r->get_header( 'X-PT24-Token' );
				return '' !== $token && hash_equals( $token, $header );
			},
		] );

		register_rest_route( self::REST_NAMESPACE, '/places-stats', [
			'methods'             => 'GET',
			'callback'            => [ __CLASS__, 'rest_stats' ],
			'permission_callback' => '__return_true',
		] );
	}

	public static function rest_seed( WP_REST_Request $request ): WP_REST_Response {
		$body    = $request->get_json_params();
		$service = sanitize_key( $body['service'] ?? '' );
		$city    = sanitize_title( $body['city'] ?? '' );
		$api_key = sanitize_text_field( $body['api_key'] ?? (string) get_option( self::OPTION_API_KEY, '' ) );

		if ( '' === $api_key ) {
			return new WP_REST_Response( [ 'error' => 'Google Places API key missing.' ], 400 );
		}

		if ( '' !== $service && '' !== $city ) {
			// Single pair
			$saved = self::seed_service_city( $service, $city, $api_key );
			return new WP_REST_Response( [ 'success' => true, 'saved' => $saved ], 200 );
		}

		// Queue all
		$queued = self::queue_all( $api_key );
		return new WP_REST_Response( [ 'success' => true, 'queued' => $queued ], 202 );
	}

	public static function rest_stats( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( self::get_stats(), 200 );
	}

	/* =====================================================================
	   AJAX HANDLERS
	   ===================================================================== */

	public static function ajax_seed(): void {
		check_ajax_referer( 'pt24_places_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$service = sanitize_key( wp_unslash( $_POST['service'] ?? '' ) );
		$city    = sanitize_title( wp_unslash( $_POST['city'] ?? '' ) );
		$api_key = (string) get_option( self::OPTION_API_KEY, '' );

		if ( '' === $api_key ) {
			wp_send_json_error( [ 'message' => 'Brak klucza Google Places API. Dodaj go w ustawieniach.' ] );
		}

		if ( 'all' === $service || '' === $service ) {
			// Queue all pairs
			$queued = self::queue_all( $api_key );
			wp_send_json_success( [
				'message' => "Dodano {$queued} par do kolejki. WP-Cron przetworzy 3 pary/minutę.",
				'stats'   => self::get_stats(),
			] );
			return;
		}

		if ( '' !== $service && '' !== $city ) {
			$saved = self::seed_service_city( $service, $city, $api_key );
			wp_send_json_success( [
				'message' => "Zapisano {$saved} firm dla {$service}/{$city}.",
				'stats'   => self::get_stats(),
			] );
			return;
		}

		// Queue specific service, all cities
		$cities = class_exists( 'PT24_Scale_Data' ) ? array_keys( PT24_Scale_Data::cities() ) : [];
		$queued = self::queue_all( $api_key, [ $service ], $cities );
		wp_send_json_success( [
			'message' => "Dodano {$queued} par usługi {$service} do kolejki.",
			'stats'   => self::get_stats(),
		] );
	}

	public static function ajax_stats(): void {
		check_ajax_referer( 'pt24_places_nonce', 'nonce' );
		wp_send_json_success( self::get_stats() );
	}

	public static function ajax_run_queue(): void {
		check_ajax_referer( 'pt24_places_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$saved = self::process_queue();
		wp_send_json_success( [
			'message' => "Przetworzono 3 pary, zapisano {$saved} firm.",
			'stats'   => self::get_stats(),
		] );
	}
}

<?php
/**
 * PT24.PRO — Google Places Seeder v3.0 (New Places API + AI Enrichment)
 *
 * Workflow:
 *   CSV / batch → Google Places API (New) → AI → profil firmy → pt24_firm CPT
 *
 * API (New — places.googleapis.com/v1/):
 *   Text Search  POST …/places:searchText
 *   Details      GET  …/places/{id}
 *   Dozwolone pola: place_id · displayName · formattedAddress · nationalPhoneNumber
 *                   websiteUri · rating · userRatingCount · regularOpeningHours
 *                   location · businessStatus · googleMapsUri · primaryType
 *
 * Zasady ToS Google:
 *   ✅ pobieramy: displayName, adres, telefon, rating, liczba opinii, godziny
 *   ❌ NIE kopiujemy: opinii, opisów, zdjęć, cudzych treści marketingowych
 *
 * REST endpoints:
 *   POST /wp-json/pt24/v2/places-seed           { service?, city?, use_ai?, services[]?, cities[]? }
 *   POST /wp-json/pt24/v2/places-import-csv     { csv, use_ai? }
 *   GET  /wp-json/pt24/v2/places-stats
 *
 * @package PearBlog\PT24
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class PT24_Places_Seeder {

	const OPTION_API_KEY = 'pt24_google_places_api_key';
	const REST_NAMESPACE = 'pt24/v2';
	const BATCH_SIZE     = 3;   // Pairs per cron tick (rate limit safe).
	const QUEUE_OPTION   = 'pt24_places_queue';
	const NEW_API_BASE   = 'https://places.googleapis.com/v1/places';

	/** PT24 service slug → Polish Google search term. */
	private static array $search_terms = [
		'hydraulik'         => 'hydraulik',
		'elektryk'          => 'elektryk instalacje elektryczne',
		'mechanik'          => 'mechanik samochodowy serwis',
		'fotowoltaika'      => 'instalacja fotowoltaiczna panele słoneczne',
		'pompa-ciepla'      => 'pompa ciepła montaż serwis',
		'remont-lazienki'   => 'remont łazienki ekipa',
		'laweta'            => 'laweta pomoc drogowa',
		'wulkanizacja'      => 'wulkanizacja wymiana opon',
		'klimatyzacja'      => 'klimatyzacja montaż serwis',
		'instalacje-gazowe' => 'instalacje gazowe certyfikat',
	];

	/* =====================================================================
	   BOOTSTRAP
	   ===================================================================== */

	public static function register(): void {
		add_action( 'wp_ajax_pt24_places_seed',        [ __CLASS__, 'ajax_seed' ] );
		add_action( 'wp_ajax_pt24_places_import_csv',  [ __CLASS__, 'ajax_import_csv' ] );
		add_action( 'wp_ajax_pt24_places_run_queue',   [ __CLASS__, 'ajax_run_queue' ] );
		add_action( 'wp_ajax_pt24_places_clear_queue', [ __CLASS__, 'ajax_clear_queue' ] );
		add_action( 'wp_ajax_pt24_places_stats',       [ __CLASS__, 'ajax_stats' ] );
		add_action( 'rest_api_init',                   [ __CLASS__, 'register_rest' ] );
		add_action( 'pt24_places_cron',                [ __CLASS__, 'process_queue' ] );

		if ( ! wp_next_scheduled( 'pt24_places_cron' ) ) {
			wp_schedule_event( time(), 'every_minute', 'pt24_places_cron' );
		}
	}

	/* =====================================================================
	   GOOGLE PLACES API (NEW)
	   ===================================================================== */

	/**
	 * Text Search — POST places:searchText.
	 * Returns array of place stubs (only allowed fields).
	 */
	public static function text_search( string $query, string $api_key, int $max = 20 ): array {
		$response = wp_safe_remote_post(
			self::NEW_API_BASE . ':searchText',
			[
				'timeout' => 20,
				'headers' => [
					'Content-Type'     => 'application/json',
					'X-Goog-Api-Key'   => $api_key,
					'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,'
						. 'places.nationalPhoneNumber,places.websiteUri,places.rating,'
						. 'places.userRatingCount,places.location,places.businessStatus,'
						. 'places.googleMapsUri,places.primaryType',
				],
				'body' => wp_json_encode( [
					'textQuery'      => $query,
					'languageCode'   => 'pl',
					'regionCode'     => 'PL',
					'maxResultCount' => min( $max, 20 ),
				] ),
			]
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'PT24 Places text_search WP_Error: ' . $response->get_error_message() );
			return [];
		}
		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			error_log( 'PT24 Places text_search HTTP error: ' . wp_remote_retrieve_body( $response ) );
			return [];
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		return ( JSON_ERROR_NONE === json_last_error() ) ? (array) ( $data['places'] ?? [] ) : [];
	}

	/**
	 * Place Details — GET places/{id}.
	 * Fetches regularOpeningHours not available in text-search.
	 */
	public static function get_details( string $place_id, string $api_key ): ?array {
		$response = wp_safe_remote_get(
			self::NEW_API_BASE . '/' . rawurlencode( $place_id ),
			[
				'timeout' => 15,
				'headers' => [
					'X-Goog-Api-Key'   => $api_key,
					'X-Goog-FieldMask' => 'id,displayName,formattedAddress,'
						. 'nationalPhoneNumber,websiteUri,rating,userRatingCount,'
						. 'regularOpeningHours,location,businessStatus,googleMapsUri,primaryType',
				],
			]
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		return ( JSON_ERROR_NONE === json_last_error() && ! empty( $data['id'] ) ) ? $data : null;
	}

	/* =====================================================================
	   AI ENRICHMENT — Prompt Nr 3 (profil firmy z danych Places)
	   ===================================================================== */

	/**
	 * Generate AI-written firm profile.
	 * Uses "PROMPT NR 3 - GOOGLE PLACES → PT24".
	 * NIE kopiuje treści z Google — tworzy całkowicie nową treść.
	 */
	private static function generate_ai_profile( array $d ): string {
		$oai_key = (string) get_option( 'pt24_openai_api_key', '' );
		if ( '' === $oai_key ) {
			return self::build_fallback_content( $d );
		}

		$hours_txt = '';
		if ( ! empty( $d['opening_hours'] ) ) {
			$hours_txt = 'Godziny otwarcia: ' . implode( '; ', array_slice( $d['opening_hours'], 0, 7 ) );
		}

		$prompt = "Na podstawie danych firmy wygeneruj profil SEO dla platformy pt24.pro.\n\n"
			. "DANE:\n"
			. "Nazwa firmy: {$d['name']}\n"
			. "Usługa: {$d['service_name']}\n"
			. "Miasto: {$d['city_name']}\n"
			. "Adres: {$d['address']}\n"
			. ( $d['phone']        ? "Telefon: {$d['phone']}\n"      : '' )
			. ( $d['website']      ? "Strona www: {$d['website']}\n" : '' )
			. ( $d['rating']       ? "Ocena: {$d['rating']}/5 ({$d['review_count']} opinii)\n" : '' )
			. ( $hours_txt         ? $hours_txt . "\n"               : '' )
			. "\nZASADY:\n"
			. "- NIE kopiuj treści z Google — stwórz całkowicie nową treść\n"
			. "- Język: prosty, wiarygodny, lokalny\n"
			. "- NIE używaj: 'najwyższa jakość', 'lider rynku', 'najlepsza firma'\n"
			. "- Użyj konkretnych słów kluczowych: {$d['service_name']} {$d['city_name']}\n"
			. "- 600-900 słów\n\n"
			. "WYGENERUJ JSON:\n"
			. '{"h1":"...","description":"<p>...</p><p>...</p><p>...</p>",'
			. '"services":["...","...","...","...","...","...","...","..."],'
			. '"area":"...","faq":[{"q":"...","a":"..."},{"q":"...","a":"..."},{"q":"...","a":"..."}],'
			. '"cta":"...","meta_title":"...","meta_description":"..."}';

		$body = wp_json_encode( [
			'model'           => 'gpt-4o-mini',
			'messages'        => [
				[ 'role' => 'system', 'content' => 'Jesteś polskim copywriterem SEO. Odpowiadasz WYŁĄCZNIE w formacie JSON.' ],
				[ 'role' => 'user',   'content' => $prompt ],
			],
			'max_tokens'      => 2000,
			'temperature'     => 0.7,
			'response_format' => [ 'type' => 'json_object' ],
		] );

		$resp = wp_safe_remote_post( 'https://api.openai.com/v1/chat/completions', [
			'headers' => [
				'Authorization' => 'Bearer ' . $oai_key,
				'Content-Type'  => 'application/json',
			],
			'body'    => $body,
			'timeout' => 60,
		] );

		if ( is_wp_error( $resp ) || 200 !== (int) wp_remote_retrieve_response_code( $resp ) ) {
			return self::build_fallback_content( $d );
		}

		$resp_data = json_decode( wp_remote_retrieve_body( $resp ), true );
		$ai        = json_decode( $resp_data['choices'][0]['message']['content'] ?? '{}', true );

		if ( JSON_ERROR_NONE !== json_last_error() || empty( $ai['description'] ) ) {
			return self::build_fallback_content( $d );
		}

		// Cache AI meta for post-insert pickup
		$slug = sanitize_title( $d['name'] . '-' . $d['city_slug'] );
		set_transient( 'pt24_firm_ai_' . $slug, $ai, 600 );

		return wp_kses_post( $ai['description'] );
	}

	/** Static fallback — no placeholders, real data only. */
	private static function build_fallback_content( array $d ): string {
		$name = esc_html( $d['name'] );
		$svc  = esc_html( $d['service_name'] );
		$city = esc_html( $d['city_name'] );
		$addr = esc_html( $d['address'] );

		$html = "<p><strong>{$name}</strong> świadczy usługi z zakresu {$svc} w {$city} i okolicach.</p>\n";
		if ( $addr ) {
			$html .= "<p>Adres: {$addr}.</p>\n";
		}
		if ( $d['rating'] && $d['review_count'] > 0 ) {
			$html .= '<p>Ocena klientów: <strong>' . esc_html( $d['rating'] ) . '/5</strong>'
				. ' na podstawie <strong>' . (int) $d['review_count'] . ' opinii</strong>.</p>' . "\n";
		}
		if ( ! empty( $d['opening_hours'] ) ) {
			$html .= "<h3>Godziny otwarcia</h3>\n<ul>\n";
			foreach ( array_slice( $d['opening_hours'], 0, 7 ) as $day ) {
				$html .= '<li>' . esc_html( $day ) . "</li>\n";
			}
			$html .= "</ul>\n";
		}
		$html .= "<p>Skontaktuj się, aby otrzymać bezpłatną wycenę usługi.</p>\n";
		return $html;
	}

	/* =====================================================================
	   FIRM CREATION
	   ===================================================================== */

	/**
	 * Seed firms for one service × city pair from Google Places.
	 *
	 * @param string $service_slug  PT24 service slug (e.g. 'mechanik')
	 * @param string $city_slug     PT24 city slug   (e.g. 'ruda-slaska')
	 * @param string $api_key       Google Places API key
	 * @param bool   $use_ai        Generate AI profile (requires OpenAI key)
	 * @param int    $max           Max firms to save (1-20)
	 * @return int   Firms created/updated
	 */
	public static function seed_service_city(
		string $service_slug,
		string $city_slug,
		string $api_key,
		bool   $use_ai = false,
		int    $max    = 5
	): int {
		$service_name = class_exists( 'PT24_Scale_Data' )
			? PT24_Scale_Data::service_name( $service_slug )
			: ucfirst( str_replace( '-', ' ', $service_slug ) );

		$city_name = class_exists( 'PT24_Scale_Data' )
			? PT24_Scale_Data::city_name( $city_slug )
			: ucfirst( str_replace( '-', ' ', $city_slug ) );

		$search_term = self::$search_terms[ $service_slug ] ?? str_replace( '-', ' ', $service_slug );
		$places      = self::text_search( $search_term . ' ' . $city_name, $api_key, $max );

		if ( empty( $places ) ) {
			return 0;
		}

		$saved = 0;
		foreach ( array_slice( $places, 0, $max ) as $place ) {
			// New API uses 'id'
			$place_id = (string) ( $place['id'] ?? $place['place_id'] ?? '' );
			if ( '' === $place_id ) continue;

			// Skip permanently closed
			if ( 'CLOSED_PERMANENTLY' === ( $place['businessStatus'] ?? '' ) ) continue;

			// Deduplicate
			if ( self::firm_exists_by_place_id( $place_id ) ) continue;

			// Details call for opening hours
			$det    = self::get_details( $place_id, $api_key ) ?? $place;
			$src    = $det;

			// Extract only allowed fields
			$name         = sanitize_text_field( $src['displayName']['text'] ?? $src['displayName'] ?? '' );
			$address      = sanitize_text_field( $src['formattedAddress'] ?? '' );
			$phone        = sanitize_text_field( $src['nationalPhoneNumber'] ?? '' );
			$website      = esc_url_raw( $src['websiteUri'] ?? '' );
			$rating       = round( (float) ( $src['rating'] ?? 0 ), 1 );
			$review_count = (int) ( $src['userRatingCount'] ?? 0 );
			$maps_url     = esc_url_raw( $src['googleMapsUri'] ?? '' );
			$primary_type = sanitize_key( $src['primaryType'] ?? '' );
			$lat          = (float) ( $src['location']['latitude']  ?? 0 );
			$lng          = (float) ( $src['location']['longitude'] ?? 0 );

			$opening_hours = [];
			foreach ( (array) ( $src['regularOpeningHours']['weekdayDescriptions'] ?? [] ) as $day ) {
				$opening_hours[] = sanitize_text_field( $day );
			}

			if ( '' === $name ) continue;

			$firm_data = compact( 'name', 'address', 'phone', 'website', 'opening_hours', 'maps_url', 'primary_type' ) + [
				'service_slug'  => $service_slug,
				'service_name'  => $service_name,
				'city_slug'     => $city_slug,
				'city_name'     => $city_name,
				'rating'        => $rating > 0 ? number_format( $rating, 1, ',', '' ) : '',
				'review_count'  => $review_count,
				'lat'           => $lat,
				'lng'           => $lng,
			];

			$content = $use_ai ? self::generate_ai_profile( $firm_data ) : self::build_fallback_content( $firm_data );
			$post_id = self::upsert_firm_post( $name, $city_slug, $content );

			if ( $post_id > 0 ) {
				self::save_firm_meta( $post_id, $firm_data, $place_id, 'google_places_v3' );
				$saved++;
			}
		}

		return $saved;
	}

	/* =====================================================================
	   CSV IMPORT — places_seed format
	   place_id,company_name,service,city,address,phone,website,rating,reviews,status
	   ===================================================================== */

	/**
	 * Import firms from a places_seed CSV (no extra API calls needed).
	 *
	 * @param string $csv_text  Raw CSV.
	 * @param bool   $use_ai    AI-enrich each firm.
	 * @return array{ imported:int, skipped:int, errors:string[] }
	 */
	public static function import_from_csv( string $csv_text, bool $use_ai = false ): array {
		$lines    = preg_split( '/\r?\n/', trim( $csv_text ) );
		$imported = 0;
		$skipped  = 0;
		$errors   = [];

		$cities   = class_exists( 'PT24_Scale_Data' ) ? PT24_Scale_Data::cities()   : [];
		$services = class_exists( 'PT24_Scale_Data' ) ? PT24_Scale_Data::services() : [];

		foreach ( $lines as $idx => $line ) {
			$line = trim( $line );
			if ( '' === $line ) continue;

			$cols = str_getcsv( $line );

			// Skip header row
			if ( 0 === $idx && in_array( strtolower( $cols[0] ?? '' ), [ 'place_id', 'id' ], true ) ) {
				continue;
			}

			if ( count( $cols ) < 4 ) {
				$errors[] = "Linia " . ( $idx + 1 ) . ": za mało kolumn (min. 4).";
				$skipped++;
				continue;
			}

			$place_id     = sanitize_text_field( trim( $cols[0] ?? '' ) );
			$name         = sanitize_text_field( trim( $cols[1] ?? '' ) );
			$service_raw  = trim( $cols[2] ?? '' );
			$city_raw     = trim( $cols[3] ?? '' );
			$address      = sanitize_text_field( trim( $cols[4] ?? '' ) );
			$phone        = sanitize_text_field( trim( $cols[5] ?? '' ) );
			$website      = esc_url_raw( trim( $cols[6] ?? '' ) );
			$rating_raw   = (float) str_replace( ',', '.', trim( $cols[7] ?? '0' ) );
			$review_count = (int) trim( $cols[8] ?? '0' );
			$status       = strtolower( trim( $cols[9] ?? 'new' ) );

			if ( in_array( $status, [ 'closed', 'closed_permanently' ], true ) ) {
				$skipped++;
				continue;
			}

			$service_slug = sanitize_key( str_replace( ' ', '-', strtolower( $service_raw ) ) );
			if ( ! empty( $services ) && ! isset( $services[ $service_slug ] ) ) {
				$errors[] = "Linia " . ( $idx + 1 ) . ": nieznana usługa '{$service_raw}'.";
				$skipped++;
				continue;
			}

			$city_slug = sanitize_title( $city_raw );
			$city_name = $cities[ $city_slug ] ?? ucfirst( str_replace( '-', ' ', $city_slug ) );
			$svc_name  = $services[ $service_slug ] ?? ucfirst( str_replace( '-', ' ', $service_slug ) );

			if ( '' === $name || '' === $service_slug || '' === $city_slug ) {
				$errors[] = "Linia " . ( $idx + 1 ) . ": brak wymaganego pola.";
				$skipped++;
				continue;
			}

			if ( '' !== $place_id && self::firm_exists_by_place_id( $place_id ) ) {
				$skipped++;
				continue;
			}

			$firm_data = [
				'name'          => $name,
				'service_slug'  => $service_slug,
				'service_name'  => $svc_name,
				'city_slug'     => $city_slug,
				'city_name'     => $city_name,
				'address'       => $address,
				'phone'         => $phone,
				'website'       => $website,
				'rating'        => $rating_raw > 0 ? number_format( $rating_raw, 1, ',', '' ) : '',
				'review_count'  => $review_count,
				'lat'           => 0.0,
				'lng'           => 0.0,
				'opening_hours' => [],
				'maps_url'      => '',
				'primary_type'  => '',
			];

			$content = $use_ai ? self::generate_ai_profile( $firm_data ) : self::build_fallback_content( $firm_data );
			$post_id = self::upsert_firm_post( $name, $city_slug, $content );

			if ( $post_id > 0 ) {
				self::save_firm_meta( $post_id, $firm_data, $place_id, 'csv_import' );
				$imported++;
			} else {
				$errors[] = "Linia " . ( $idx + 1 ) . ": błąd zapisu '{$name}'.";
				$skipped++;
			}
		}

		return [ 'imported' => $imported, 'skipped' => $skipped, 'errors' => $errors ];
	}

	/* =====================================================================
	   SHARED HELPERS
	   ===================================================================== */

	/** Create or update a pt24_firm CPT post. Returns post ID or 0. */
	private static function upsert_firm_post( string $name, string $city_slug, string $content ): int {
		$slug     = sanitize_title( $name . '-' . $city_slug );
		$existing = get_posts( [
			'post_type'        => 'pt24_firm',
			'name'             => $slug,
			'numberposts'      => 1,
			'post_status'      => 'any',
			'suppress_filters' => true,
		] );

		if ( ! empty( $existing ) ) {
			$post_id = (int) $existing[0]->ID;
			wp_update_post( [
				'ID'           => $post_id,
				'post_title'   => $name,
				'post_content' => $content,
				'post_status'  => 'publish',
			] );
			return $post_id;
		}

		return (int) wp_insert_post( [
			'post_title'   => $name,
			'post_name'    => $slug,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_type'    => 'pt24_firm',
		] );
	}

	/** Save all meta for a firm post; pick up AI transient if available. */
	private static function save_firm_meta( int $post_id, array $d, string $place_id, string $source ): void {
		update_post_meta( $post_id, 'pt24_firm_city',          $d['city_slug'] );
		update_post_meta( $post_id, 'pt24_firm_city_name',     $d['city_name'] );
		update_post_meta( $post_id, 'pt24_firm_services',      $d['service_slug'] );
		update_post_meta( $post_id, 'pt24_firm_rating',        $d['rating'] );
		update_post_meta( $post_id, 'pt24_firm_jobs',          (string) $d['review_count'] );
		update_post_meta( $post_id, 'pt24_firm_phone',         $d['phone'] );
		update_post_meta( $post_id, 'pt24_firm_website',       $d['website'] );
		update_post_meta( $post_id, 'pt24_firm_address',       $d['address'] );
		update_post_meta( $post_id, 'pt24_firm_place_id',      $place_id );
		update_post_meta( $post_id, 'pt24_firm_maps_url',      $d['maps_url'] );
		update_post_meta( $post_id, 'pt24_firm_lat',           (string) $d['lat'] );
		update_post_meta( $post_id, 'pt24_firm_lng',           (string) $d['lng'] );
		update_post_meta( $post_id, 'pt24_firm_primary_type',  $d['primary_type'] );
		update_post_meta( $post_id, 'pt24_firm_source',        $source );
		update_post_meta( $post_id, '_pt24_google_seeded',     '1' );

		if ( ! empty( $d['opening_hours'] ) ) {
			update_post_meta( $post_id, 'pt24_firm_hours', wp_json_encode( $d['opening_hours'] ) );
		}

		// AI-generated meta (stored in transient by generate_ai_profile)
		$slug    = sanitize_title( $d['name'] . '-' . $d['city_slug'] );
		$ai_meta = get_transient( 'pt24_firm_ai_' . $slug );
		if ( is_array( $ai_meta ) ) {
			if ( ! empty( $ai_meta['meta_title'] ) )        update_post_meta( $post_id, 'pt24_meta_title',         sanitize_text_field( $ai_meta['meta_title'] ) );
			if ( ! empty( $ai_meta['meta_description'] ) )  update_post_meta( $post_id, 'pt24_meta_description',   sanitize_text_field( $ai_meta['meta_description'] ) );
			if ( ! empty( $ai_meta['faq'] ) )               update_post_meta( $post_id, 'pt24_firm_faq',           wp_json_encode( $ai_meta['faq'] ) );
			if ( ! empty( $ai_meta['services'] ) )          update_post_meta( $post_id, 'pt24_firm_services_list', wp_json_encode( $ai_meta['services'] ) );
			delete_transient( 'pt24_firm_ai_' . $slug );
		}
	}

	private static function firm_exists_by_place_id( string $place_id ): bool {
		global $wpdb;
		if ( '' === $place_id ) return false;
		return (bool) $wpdb->get_var( $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='pt24_firm_place_id' AND meta_value=%s LIMIT 1",
			$place_id
		) );
	}

	/* =====================================================================
	   QUEUE / BATCH (WP-Cron)
	   ===================================================================== */

	public static function queue_all(
		string $api_key,
		array  $service_filter = [],
		array  $city_filter    = [],
		bool   $use_ai         = false
	): int {
		$all_services = class_exists( 'PT24_Scale_Data' ) ? array_keys( PT24_Scale_Data::services() ) : array_keys( self::$search_terms );
		$all_cities   = class_exists( 'PT24_Scale_Data' ) ? array_keys( PT24_Scale_Data::cities() )   : [];

		$services = empty( $service_filter ) ? $all_services : array_intersect( $all_services, $service_filter );
		$cities   = empty( $city_filter )    ? $all_cities   : array_intersect( $all_cities, $city_filter );

		$queue = (array) get_option( self::QUEUE_OPTION, [] );
		$added = 0;
		foreach ( $services as $svc ) {
			foreach ( $cities as $cty ) {
				$key = $svc . '|' . $cty;
				if ( ! isset( $queue[ $key ] ) ) {
					$queue[ $key ] = [ 'service' => $svc, 'city' => $cty, 'api_key' => $api_key, 'use_ai' => $use_ai ];
					$added++;
				}
			}
		}
		update_option( self::QUEUE_OPTION, $queue, false );
		return $added;
	}

	public static function process_queue(): int {
		$api_key = (string) get_option( self::OPTION_API_KEY, '' );
		if ( '' === $api_key ) return 0;

		$queue = (array) get_option( self::QUEUE_OPTION, [] );
		if ( empty( $queue ) ) return 0;

		$batch = array_splice( $queue, 0, self::BATCH_SIZE );
		update_option( self::QUEUE_OPTION, $queue, false );

		$total = 0;
		foreach ( $batch as $pair ) {
			$total += self::seed_service_city(
				(string) $pair['service'],
				(string) $pair['city'],
				$api_key,
				! empty( $pair['use_ai'] )
			);
		}

		$stats = (array) get_option( 'pt24_places_stats', [] );
		$stats['total']   = ( $stats['total'] ?? 0 ) + $total;
		$stats['last_ts'] = time();
		update_option( 'pt24_places_stats', $stats, false );

		return $total;
	}

	/* =====================================================================
	   STATS
	   ===================================================================== */

	public static function get_stats(): array {
		global $wpdb;

		return [
			'total_firms'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='pt24_firm' AND post_status='publish'" ),
			'places_firms'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key='_pt24_google_seeded' AND meta_value='1'" ),
			'ai_enriched'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key='pt24_firm_faq'" ),
			'queue_size'     => count( (array) get_option( self::QUEUE_OPTION, [] ) ),
			'has_places_key' => '' !== (string) get_option( self::OPTION_API_KEY, '' ),
			'has_openai_key' => '' !== (string) get_option( 'pt24_openai_api_key', '' ),
			'possible_pairs' => class_exists( 'PT24_Scale_Data' )
				? count( PT24_Scale_Data::cities() ) * count( PT24_Scale_Data::services() )
				: 0,
		];
	}

	/* =====================================================================
	   REST API
	   ===================================================================== */

	public static function register_rest(): void {
		$auth = function( WP_REST_Request $r ): bool {
			if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) return true;
			$token  = (string) get_option( 'pt24_webhook_token', '' );
			$header = (string) $r->get_header( 'X-PT24-Token' );
			return '' !== $token && hash_equals( $token, $header );
		};

		register_rest_route( self::REST_NAMESPACE, '/places-seed', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'rest_seed' ],
			'permission_callback' => $auth,
		] );

		register_rest_route( self::REST_NAMESPACE, '/places-import-csv', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'rest_import_csv' ],
			'permission_callback' => $auth,
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
		$use_ai  = ! empty( $body['use_ai'] );
		$api_key = sanitize_text_field( $body['api_key'] ?? (string) get_option( self::OPTION_API_KEY, '' ) );

		if ( '' === $api_key ) {
			return new WP_REST_Response( [ 'error' => 'Google Places API key missing.' ], 400 );
		}

		if ( '' !== $service && '' !== $city ) {
			$saved = self::seed_service_city( $service, $city, $api_key, $use_ai );
			return new WP_REST_Response( [ 'success' => true, 'saved' => $saved ], 200 );
		}

		$queued = self::queue_all(
			$api_key,
			(array) ( $body['services'] ?? [] ),
			(array) ( $body['cities']   ?? [] ),
			$use_ai
		);
		return new WP_REST_Response( [ 'success' => true, 'queued' => $queued ], 202 );
	}

	public static function rest_import_csv( WP_REST_Request $request ): WP_REST_Response {
		$body   = $request->get_json_params();
		$csv    = (string) ( $body['csv'] ?? '' );
		$use_ai = ! empty( $body['use_ai'] );

		if ( '' === $csv ) {
			return new WP_REST_Response( [ 'error' => 'Provide csv field.' ], 400 );
		}

		$result = self::import_from_csv( $csv, $use_ai );
		return new WP_REST_Response( array_merge( [ 'success' => true ], $result ), 202 );
	}

	public static function rest_stats( WP_REST_Request $r ): WP_REST_Response {
		return new WP_REST_Response( self::get_stats(), 200 );
	}

	/* =====================================================================
	   AJAX HANDLERS
	   ===================================================================== */

	public static function ajax_seed(): void {
		check_ajax_referer( 'pt24_places_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( [ 'message' => 'Brak uprawnień.' ] );

		$service = sanitize_key( wp_unslash( $_POST['service'] ?? '' ) );
		$city    = sanitize_title( wp_unslash( $_POST['city'] ?? '' ) );
		$use_ai  = ! empty( $_POST['use_ai'] );
		$api_key = (string) get_option( self::OPTION_API_KEY, '' );

		if ( '' === $api_key ) wp_send_json_error( [ 'message' => 'Brak klucza Google Places API. Dodaj go w ustawieniach.' ] );

		if ( '' !== $service && '' !== $city ) {
			$saved = self::seed_service_city( $service, $city, $api_key, $use_ai );
			wp_send_json_success( [ 'message' => "Zapisano {$saved} firm dla {$service}/{$city}.", 'stats' => self::get_stats() ] );
			return;
		}

		$cities = class_exists( 'PT24_Scale_Data' ) ? array_keys( PT24_Scale_Data::cities() ) : [];
		$queued = self::queue_all( $api_key, $service ? [ $service ] : [], $cities, $use_ai );
		wp_send_json_success( [
			'message' => "Dodano {$queued} par do kolejki. WP-Cron: " . self::BATCH_SIZE . " par/min.",
			'stats'   => self::get_stats(),
		] );
	}

	public static function ajax_import_csv(): void {
		check_ajax_referer( 'pt24_places_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( [ 'message' => 'Brak uprawnień.' ] );

		$csv    = sanitize_textarea_field( wp_unslash( $_POST['csv'] ?? '' ) );
		$use_ai = ! empty( $_POST['use_ai'] );

		if ( '' === $csv ) wp_send_json_error( [ 'message' => 'Podaj dane CSV.' ] );

		$result = self::import_from_csv( $csv, $use_ai );
		wp_send_json_success( [
			'message' => "Zaimportowano {$result['imported']} firm, pominięto {$result['skipped']}.",
			'errors'  => $result['errors'],
			'stats'   => self::get_stats(),
		] );
	}

	public static function ajax_run_queue(): void {
		check_ajax_referer( 'pt24_places_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( [ 'message' => 'Brak uprawnień.' ] );

		$saved = self::process_queue();
		wp_send_json_success( [
			'message' => "Przetworzono " . self::BATCH_SIZE . " par, zapisano {$saved} firm.",
			'stats'   => self::get_stats(),
		] );
	}

	public static function ajax_stats(): void {
		check_ajax_referer( 'pt24_places_nonce', 'nonce' );
		wp_send_json_success( self::get_stats() );
	}

	public static function ajax_clear_queue(): void {
		check_ajax_referer( 'pt24_places_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Brak uprawnień.' ] );
		}
		update_option( self::QUEUE_OPTION, [], false );
		wp_send_json_success( [ 'message' => 'Kolejka wyczyszczona.', 'stats' => self::get_stats() ] );
	}
}

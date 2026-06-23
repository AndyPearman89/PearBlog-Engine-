<?php
/**
 * PT24.PRO — Blog Engine (SEO Content Factory)
 *
 * Generuje artykuły blogowe zasilające lejek ruchu PT24:
 *   Blog → Poradnik → Strona usługi → Profil firmy → Telefon → Lead
 *
 * Workflow:
 *   CSV (temat, usluga, miasto) → AI (Master Prompt Blog Engine) → WordPress post
 *
 * Kategorie:
 *   poradniki | awarie | koszty | jak-zrobic | rankingi
 *   24h | bezpieczenstwo | sezonowe | problemy | lokalne
 *
 * REST:
 *   POST /wp-json/pt24/v2/blog-generate  { topic, service, city, use_queue? }
 *   POST /wp-json/pt24/v2/blog-csv       { csv, use_queue? }
 *   GET  /wp-json/pt24/v2/blog-stats
 *
 * @package PearBlog\PT24
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PT24_Blog_Engine {

	const QUEUE_OPTION   = 'pt24_blog_queue';
	const STATS_OPTION   = 'pt24_blog_stats';
	const CRON_HOOK      = 'pt24_blog_cron';
	const REST_NAMESPACE = 'pt24/v2';
	const BATCH_SIZE     = 5;

	/** Category slug → WordPress display name. */
	private static array $categories = [
		'poradniki'      => 'Poradniki',
		'awarie'         => 'Awarie',
		'koszty'         => 'Koszty',
		'jak-zrobic'     => 'Jak zrobić',
		'rankingi'       => 'Rankingi',
		'pt24-24h'       => '24h',
		'bezpieczenstwo' => 'Bezpieczeństwo',
		'sezonowe'       => 'Sezonowe',
		'problemy'       => 'Najczęstsze problemy',
		'lokalne'        => 'Lokalne poradniki',
	];

	/** 100 starter topics (temat, usluga, kategoria). */
	public static array $starter_topics = [
		// MECHANIK
		[ 'topic' => 'Auto nie odpala - co sprawdzić?',       'service' => 'mechanik',  'cat' => 'awarie'   ],
		[ 'topic' => 'Kontrolka silnika - czy można jechać?', 'service' => 'mechanik',  'cat' => 'awarie'   ],
		[ 'topic' => 'Jak rozpoznać awarię sprzęgła?',        'service' => 'mechanik',  'cat' => 'awarie'   ],
		[ 'topic' => 'Kiedy wymienić rozrząd?',               'service' => 'mechanik',  'cat' => 'poradniki'],
		[ 'topic' => 'Dlaczego auto traci moc?',              'service' => 'mechanik',  'cat' => 'problemy' ],
		[ 'topic' => 'Co oznacza dym z wydechu?',             'service' => 'mechanik',  'cat' => 'problemy' ],
		[ 'topic' => 'Czy warto regenerować turbinę?',        'service' => 'mechanik',  'cat' => 'rankingi' ],
		[ 'topic' => 'Jak przygotować auto do zimy?',         'service' => 'mechanik',  'cat' => 'sezonowe' ],
		[ 'topic' => 'Najczęstsze awarie diesla',             'service' => 'mechanik',  'cat' => 'problemy' ],
		[ 'topic' => 'Najczęstsze awarie benzyny',            'service' => 'mechanik',  'cat' => 'problemy' ],
		[ 'topic' => 'Ile kosztuje mechanik?',                'service' => 'mechanik',  'cat' => 'koszty'   ],
		[ 'topic' => 'Ile kosztuje wymiana sprzęgła?',        'service' => 'mechanik',  'cat' => 'koszty'   ],
		[ 'topic' => 'Jak wybrać dobrego mechanika?',         'service' => 'mechanik',  'cat' => 'rankingi' ],
		[ 'topic' => 'Mobilny mechanik - kiedy warto?',       'service' => 'mechanik',  'cat' => 'poradniki'],
		[ 'topic' => 'Diagnostyka komputerowa auta',          'service' => 'mechanik',  'cat' => 'poradniki'],
		// HYDRAULIK
		[ 'topic' => 'Pękła rura - co robić?',                'service' => 'hydraulik', 'cat' => 'awarie'   ],
		[ 'topic' => 'Jak zakręcić wodę w mieszkaniu?',       'service' => 'hydraulik', 'cat' => 'jak-zrobic'],
		[ 'topic' => 'Dlaczego kaloryfer nie grzeje?',        'service' => 'hydraulik', 'cat' => 'problemy' ],
		[ 'topic' => 'Jak odpowietrzyć grzejnik?',            'service' => 'hydraulik', 'cat' => 'jak-zrobic'],
		[ 'topic' => 'Jak udrożnić odpływ?',                  'service' => 'hydraulik', 'cat' => 'jak-zrobic'],
		[ 'topic' => 'Kiedy wymienić bojler?',                 'service' => 'hydraulik', 'cat' => 'poradniki'],
		[ 'topic' => 'Co zrobić przy zalaniu mieszkania?',    'service' => 'hydraulik', 'cat' => 'awarie'   ],
		[ 'topic' => 'Jak wykryć wyciek wody?',               'service' => 'hydraulik', 'cat' => 'problemy' ],
		[ 'topic' => 'Ile kosztuje hydraulik?',               'service' => 'hydraulik', 'cat' => 'koszty'   ],
		[ 'topic' => 'Ile kosztuje udrażnianie rur?',         'service' => 'hydraulik', 'cat' => 'koszty'   ],
		[ 'topic' => 'Ile kosztuje wymiana instalacji wod-kan?','service' => 'hydraulik','cat' => 'koszty'  ],
		[ 'topic' => 'Hydraulik awaryjny 24h - kiedy wzywać?','service' => 'hydraulik', 'cat' => 'pt24-24h' ],
		[ 'topic' => 'Jak wybrać hydraulika?',                'service' => 'hydraulik', 'cat' => 'rankingi' ],
		[ 'topic' => 'Jak zapobiec zatorom kanalizacyjnym?',  'service' => 'hydraulik', 'cat' => 'poradniki'],
		[ 'topic' => 'Montaż baterii łazienkowej krok po kroku','service' => 'hydraulik','cat' => 'jak-zrobic'],
		// ELEKTRYK
		[ 'topic' => 'Wybiło korki - co robić?',              'service' => 'elektryk',  'cat' => 'awarie'   ],
		[ 'topic' => 'Jak działa bezpiecznik elektryczny?',   'service' => 'elektryk',  'cat' => 'poradniki'],
		[ 'topic' => 'Kiedy instalacja elektryczna wymaga wymiany?','service' => 'elektryk','cat' => 'poradniki'],
		[ 'topic' => 'Jak znaleźć zwarcie w instalacji?',     'service' => 'elektryk',  'cat' => 'problemy' ],
		[ 'topic' => 'Kiedy wezwać elektryka?',               'service' => 'elektryk',  'cat' => 'poradniki'],
		[ 'topic' => 'Ile kosztuje nowa instalacja elektryczna?','service' => 'elektryk','cat' => 'koszty'  ],
		[ 'topic' => 'Jak działa fotowoltaika?',              'service' => 'elektryk',  'cat' => 'poradniki'],
		[ 'topic' => 'Ile kosztuje fotowoltaika?',            'service' => 'fotowoltaika','cat' => 'koszty' ],
		[ 'topic' => 'Jak zabezpieczyć dom przed przepięciami?','service' => 'elektryk', 'cat' => 'bezpieczenstwo'],
		[ 'topic' => 'Najczęstsze awarie elektryczne w domu', 'service' => 'elektryk',  'cat' => 'problemy' ],
		[ 'topic' => 'Jak wybrać elektryka z uprawnieniami?', 'service' => 'elektryk',  'cat' => 'rankingi' ],
		[ 'topic' => 'Pomiary elektryczne - kiedy są wymagane?','service' => 'elektryk', 'cat' => 'poradniki'],
		// POMPA CIEPŁA
		[ 'topic' => 'Jak działa pompa ciepła?',              'service' => 'pompa-ciepla','cat' => 'poradniki'],
		[ 'topic' => 'Ile kosztuje pompa ciepła?',            'service' => 'pompa-ciepla','cat' => 'koszty'  ],
		[ 'topic' => 'Pompa ciepła - czy się opłaca?',        'service' => 'pompa-ciepla','cat' => 'rankingi'],
		[ 'topic' => 'Dofinansowanie do pompy ciepła 2026',   'service' => 'pompa-ciepla','cat' => 'poradniki'],
		[ 'topic' => 'Awaria pompy ciepła - co robić?',       'service' => 'pompa-ciepla','cat' => 'awarie'  ],
		// REMONT ŁAZIENKI
		[ 'topic' => 'Ile kosztuje remont łazienki?',         'service' => 'remont-lazienki','cat' => 'koszty'],
		[ 'topic' => 'Jak wybrać firmę remontową?',           'service' => 'remont-lazienki','cat' => 'rankingi'],
		[ 'topic' => 'Remont łazienki krok po kroku',         'service' => 'remont-lazienki','cat' => 'jak-zrobic'],
		[ 'topic' => 'Jak ułożyć płytki w łazience?',         'service' => 'remont-lazienki','cat' => 'jak-zrobic'],
		[ 'topic' => 'Ile trwa remont łazienki?',             'service' => 'remont-lazienki','cat' => 'poradniki'],
		// LAWETA
		[ 'topic' => 'Kiedy wezwać lawetę?',                  'service' => 'laweta',    'cat' => 'poradniki'],
		[ 'topic' => 'Ile kosztuje laweta?',                  'service' => 'laweta',    'cat' => 'koszty'   ],
		[ 'topic' => 'Laweta 24h - jak działa?',              'service' => 'laweta',    'cat' => 'pt24-24h' ],
		[ 'topic' => 'Co zabrać wzywając lawetę?',            'service' => 'laweta',    'cat' => 'jak-zrobic'],
		// WULKANIZACJA
		[ 'topic' => 'Kiedy wymienić opony?',                 'service' => 'wulkanizacja','cat' => 'sezonowe'],
		[ 'topic' => 'Ile kosztuje wymiana opon?',            'service' => 'wulkanizacja','cat' => 'koszty'  ],
		[ 'topic' => 'Opona przebita - co robić?',            'service' => 'wulkanizacja','cat' => 'awarie'  ],
		[ 'topic' => 'Jak wybrać opony zimowe?',              'service' => 'wulkanizacja','cat' => 'sezonowe'],
		// KLIMATYZACJA
		[ 'topic' => 'Ile kosztuje klimatyzacja?',            'service' => 'klimatyzacja','cat' => 'koszty'  ],
		[ 'topic' => 'Klimatyzacja - kiedy serwisować?',      'service' => 'klimatyzacja','cat' => 'sezonowe'],
		[ 'topic' => 'Awaria klimatyzacji - przyczyny',       'service' => 'klimatyzacja','cat' => 'awarie'  ],
		// LOKALNE — Mechanik
		[ 'topic' => 'Jak znaleźć mechanika w Katowicach?',   'service' => 'mechanik',  'cat' => 'lokalne', 'city' => 'katowice'  ],
		[ 'topic' => 'Jak znaleźć mechanika w Rudzie Śląskiej?','service' => 'mechanik', 'cat' => 'lokalne', 'city' => 'ruda-slaska'],
		[ 'topic' => 'Jak znaleźć mechanika w Zabrzu?',       'service' => 'mechanik',  'cat' => 'lokalne', 'city' => 'zabrze'    ],
		[ 'topic' => 'Jak znaleźć mechanika w Gliwicach?',    'service' => 'mechanik',  'cat' => 'lokalne', 'city' => 'gliwice'   ],
		[ 'topic' => 'Dobry mechanik Sosnowiec',              'service' => 'mechanik',  'cat' => 'lokalne', 'city' => 'sosnowiec' ],
		// LOKALNE — Hydraulik
		[ 'topic' => 'Jak znaleźć hydraulika w Katowicach?',  'service' => 'hydraulik', 'cat' => 'lokalne', 'city' => 'katowice'  ],
		[ 'topic' => 'Hydraulik Ruda Śląska - gdzie szukać?', 'service' => 'hydraulik', 'cat' => 'lokalne', 'city' => 'ruda-slaska'],
		[ 'topic' => 'Hydraulik awaryjny Zabrze',             'service' => 'hydraulik', 'cat' => 'lokalne', 'city' => 'zabrze'    ],
		// LOKALNE — Elektryk
		[ 'topic' => 'Elektryk Katowice - jak wybrać?',       'service' => 'elektryk',  'cat' => 'lokalne', 'city' => 'katowice'  ],
		[ 'topic' => 'Dobry elektryk Gliwice',                'service' => 'elektryk',  'cat' => 'lokalne', 'city' => 'gliwice'   ],
		// BEZPIECZEŃSTWO
		[ 'topic' => 'Jak sprawdzić instalację gazową?',      'service' => 'instalacje-gazowe','cat' => 'bezpieczenstwo'],
		[ 'topic' => 'Czujnik czadu - czy jest obowiązkowy?', 'service' => 'elektryk',  'cat' => 'bezpieczenstwo'],
		[ 'topic' => 'Bezpieczna instalacja elektryczna',     'service' => 'elektryk',  'cat' => 'bezpieczenstwo'],
		// SEZONOWE
		[ 'topic' => 'Przygotowanie auta do zimy - checklist','service' => 'mechanik',  'cat' => 'sezonowe'],
		[ 'topic' => 'Jak przezimować instalację wodną?',     'service' => 'hydraulik', 'cat' => 'sezonowe'],
		[ 'topic' => 'Kiedy włączyć ogrzewanie?',             'service' => 'hydraulik', 'cat' => 'sezonowe'],
		[ 'topic' => 'Przygotowanie klimatyzacji do lata',    'service' => 'klimatyzacja','cat' => 'sezonowe'],
		// JAK ZROBIĆ
		[ 'topic' => 'Jak odczytać błędy OBD2 auta?',        'service' => 'mechanik',  'cat' => 'jak-zrobic'],
		[ 'topic' => 'Jak wymienić żarówkę w samochodzie?',  'service' => 'mechanik',  'cat' => 'jak-zrobic'],
		[ 'topic' => 'Jak sprawdzić poziom płynów w aucie?', 'service' => 'mechanik',  'cat' => 'jak-zrobic'],
		[ 'topic' => 'Jak dobrać bezpiecznik elektryczny?',  'service' => 'elektryk',  'cat' => 'jak-zrobic'],
		// 24H
		[ 'topic' => 'Pomoc drogowa 24h - jak działa?',      'service' => 'laweta',    'cat' => 'pt24-24h' ],
		[ 'topic' => 'Awaryjny hydraulik 24h - co warto wiedzieć?','service' => 'hydraulik','cat' => 'pt24-24h'],
		[ 'topic' => 'Elektryk 24h - kiedy wzywać?',         'service' => 'elektryk',  'cat' => 'pt24-24h' ],
		// RANKINGI
		[ 'topic' => '10 pytań przed wyborem fachowca',      'service' => 'mechanik',  'cat' => 'rankingi' ],
		[ 'topic' => 'Na co zwrócić uwagę zatrudniając hydraulika?','service' => 'hydraulik','cat' => 'rankingi'],
		[ 'topic' => 'Jak sprawdzić opinie o fachowcu?',     'service' => 'mechanik',  'cat' => 'rankingi' ],
		[ 'topic' => 'Najlepsze serwisy mechaniczne Śląsk',  'service' => 'mechanik',  'cat' => 'rankingi' ],
	];

	/* =====================================================================
	   BOOTSTRAP
	   ===================================================================== */

	public static function register(): void {
		add_action( 'wp_ajax_pt24_blog_generate',       [ __CLASS__, 'ajax_generate' ] );
		add_action( 'wp_ajax_pt24_blog_import_csv',     [ __CLASS__, 'ajax_import_csv' ] );
		add_action( 'wp_ajax_pt24_blog_run_queue',      [ __CLASS__, 'ajax_run_queue' ] );
		add_action( 'wp_ajax_pt24_blog_queue_starters', [ __CLASS__, 'ajax_queue_starters' ] );
		add_action( 'wp_ajax_pt24_blog_clear_queue',    [ __CLASS__, 'ajax_clear_queue' ] );
		add_action( 'wp_ajax_pt24_blog_stats',          [ __CLASS__, 'ajax_stats' ] );
		add_action( 'rest_api_init',                    [ __CLASS__, 'register_rest' ] );
		add_action( self::CRON_HOOK,                    [ __CLASS__, 'process_queue' ] );

		add_filter( 'cron_schedules', [ __CLASS__, 'add_cron_schedule' ] );
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'every_minute', self::CRON_HOOK );
		}

		// Ensure categories exist on init
		add_action( 'init', [ __CLASS__, 'ensure_categories' ], 20 );
	}

	public static function add_cron_schedule( array $s ): array {
		if ( ! isset( $s['every_minute'] ) ) {
			$s['every_minute'] = [ 'interval' => 60, 'display' => 'Every Minute' ];
		}
		return $s;
	}

	/** Create blog categories if they don't exist. */
	public static function ensure_categories(): void {
		foreach ( self::$categories as $slug => $name ) {
			if ( ! term_exists( $slug, 'category' ) ) {
				wp_insert_term( $name, 'category', [ 'slug' => $slug ] );
			}
		}
	}

	/* =====================================================================
	   AI GENERATION — Master Prompt Blog Engine
	   ===================================================================== */

	/**
	 * Generate a full blog article using OpenAI (gpt-4o-mini).
	 * Uses the PT24 Master Prompt Blog Engine.
	 *
	 * @param string $topic    Article topic (e.g. 'Pękła rura - co robić?')
	 * @param string $service  PT24 service slug (e.g. 'hydraulik')
	 * @param string $city     PT24 city slug or '' for generic (e.g. 'katowice')
	 * @return array|null      Parsed article data or null on failure.
	 */
	public static function generate_article( string $topic, string $service, string $city = '' ): ?array {
		$api_key = (string) get_option( 'pt24_openai_api_key', '' );
		if ( '' === $api_key ) {
			return null;
		}

		$svc_name  = class_exists( 'PT24_Scale_Data' ) ? PT24_Scale_Data::service_name( $service )  : ucfirst( str_replace( '-', ' ', $service ) );
		$city_name = ( '' !== $city && class_exists( 'PT24_Scale_Data' ) )
			? PT24_Scale_Data::city_name( $city )
			: ( '' !== $city ? ucfirst( str_replace( '-', ' ', $city ) ) : '' );

		// Related services for CTA block (3 closest)
		$all_services = class_exists( 'PT24_Scale_Data' ) ? PT24_Scale_Data::services() : [];
		$related_svcs = array_slice( array_keys( array_diff_key( $all_services, [ $service => '' ] ) ), 0, 3 );

		// Related cities for internal linking (5)
		$all_cities = class_exists( 'PT24_Scale_Data' ) ? array_keys( PT24_Scale_Data::cities() ) : [];
		$related_cities = array_slice( array_filter( $all_cities, fn( $c ) => $c !== $city ), 0, 5 );

		$prompt = "Jesteś silnikiem blogowym platformy pt24.pro.\n\n"
			. "CEL:\n"
			. "Generujesz artykuły blogowe SEO dla Polski.\n"
			. "Artykuł ma odpowiadać na problem użytkownika i prowadzić do kontaktu z fachowcem.\n\n"
			. "DANE:\n"
			. "TEMAT: {$topic}\n"
			. "USŁUGA: {$svc_name}\n"
			. ( $city_name ? "MIASTO: {$city_name}\n" : '' )
			. "\nWYMAGANIA:\n"
			. "- 1500-2500 słów\n"
			. "- Język: prosty, praktyczny, bez AI-bełkotu, bez przesadnego marketingu\n"
			. "- Cel: pomóc użytkownikowi i przekierować go do fachowca\n"
			. "- Nie kopiuj treści. Twórz unikalne artykuły.\n"
			. "- Naturalne użycie fraz SEO: {$svc_name}" . ( $city_name ? " {$city_name}" : '' ) . "\n\n"
			. "WYGENERUJ w formacie JSON:\n"
			. '{"meta_title":"...","meta_description":"...","slug":"...","h1":"...","lead":"...<p>...</p>",'
			. '"toc":["...","...","...","...","...","..."],'
			. '"sections":[{"h2":"...","content":"<p>...</p>"},{"h2":"...","content":"<p>...</p>"},{"h2":"...","content":"<p>...</p>"},{"h2":"...","content":"<p>...</p>"},{"h2":"...","content":"<p>...</p>"},{"h2":"...","content":"<p>...</p>"}],'
			. '"faq":[{"q":"...","a":"..."},{"q":"...","a":"..."},{"q":"...","a":"..."},{"q":"...","a":"..."},{"q":"...","a":"..."}],'
			. '"cta":"...","article_type":"awarie|koszty|jak-zrobic|rankingi|poradniki|lokalne|bezpieczenstwo|sezonowe|problemy|pt24-24h"}'
			. "\n\nWymagania sekcji:\n"
			. "- lead: 2-3 zdania wciągające, odpowiadające na pytanie użytkownika\n"
			. "- 6 sekcji H2 z konkretnymi, praktycznymi treściami\n"
			. "- faq: 5 pytań z odpowiedziami min. 80 słów każda\n"
			. "- cta: konkretne wezwanie do działania (tekst przycisku)\n"
			. "- article_type: jeden z podanych typów, najbardziej pasujący do tematu\n"
			. "- slug: w języku polskim, małe litery, bez polskich znaków, myślniki";

		$body = wp_json_encode( [
			'model'           => 'gpt-4o-mini',
			'messages'        => [
				[ 'role' => 'system', 'content' => 'Jesteś polskim redaktorem SEO. Odpowiadasz WYŁĄCZNIE w formacie JSON. Generujesz od razu gotową treść.' ],
				[ 'role' => 'user',   'content' => $prompt ],
			],
			'max_tokens'      => 4000,
			'temperature'     => 0.75,
			'response_format' => [ 'type' => 'json_object' ],
		] );

		$response = wp_safe_remote_post( 'https://api.openai.com/v1/chat/completions', [
			'headers' => [
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			],
			'body'    => $body,
			'timeout' => 90,
		] );

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			error_log( 'PT24 Blog Engine: OpenAI error for topic: ' . $topic );
			return null;
		}

		$resp_data = json_decode( wp_remote_retrieve_body( $response ), true );
		$ai        = json_decode( $resp_data['choices'][0]['message']['content'] ?? '{}', true );

		if ( JSON_ERROR_NONE !== json_last_error() || empty( $ai['h1'] ) ) {
			return null;
		}

		return array_merge( $ai, [
			'_topic'          => $topic,
			'_service'        => $service,
			'_service_name'   => $svc_name,
			'_city'           => $city,
			'_city_name'      => $city_name,
			'_related_svcs'   => $related_svcs,
			'_related_cities' => $related_cities,
		] );
	}

	/* =====================================================================
	   CONTENT BUILDER
	   ===================================================================== */

	/** Assemble full HTML post_content from AI-generated data. */
	private static function build_post_content( array $ai ): string {
		$svc       = $ai['_service'];
		$svc_name  = $ai['_service_name'];
		$city      = $ai['_city'];
		$city_name = $ai['_city_name'];

		$html  = wp_kses_post( $ai['lead'] ?? '' );

		// Table of Contents
		if ( ! empty( $ai['toc'] ) ) {
			$html .= "\n<nav class=\"pt24-toc\" aria-label=\"Spis treści\">\n";
			$html .= "<h2>Spis treści</h2>\n<ol>\n";
			foreach ( $ai['toc'] as $i => $item ) {
				$anchor = 'sekcja-' . ( $i + 1 );
				$html  .= '<li><a href="#' . esc_attr( $anchor ) . '">' . esc_html( $item ) . "</a></li>\n";
			}
			$html .= "</ol>\n</nav>\n";
		}

		// Article sections
		foreach ( (array) ( $ai['sections'] ?? [] ) as $i => $sec ) {
			$anchor = 'sekcja-' . ( $i + 1 );
			$html  .= "\n<h2 id=\"" . esc_attr( $anchor ) . '">' . esc_html( $sec['h2'] ?? '' ) . "</h2>\n";
			$html  .= wp_kses_post( $sec['content'] ?? '' ) . "\n";
		}

		// Inline CTA (after section 3)
		$cta_url  = home_url( '/' . ( $city ? $city . '/' : '' ) . $svc . '/#pt24-lead' );
		$cta_text = esc_html( $ai['cta'] ?? 'Zamów bezpłatną wycenę' );
		$html    .= "\n<div class=\"pt24-cta-band\">\n"
			. '<strong>' . esc_html( "Potrzebujesz {$svc_name}" . ( $city_name ? " w {$city_name}" : '' ) . '?' ) . "</strong>\n"
			. "<p>Opisz zlecenie i otrzymaj do 3 bezpłatnych ofert od lokalnych specjalistów.</p>\n"
			. '<a href="' . esc_url( $cta_url ) . '" class="pt24-btn pt24-btn--primary">' . $cta_text . "</a>\n"
			. "</div>\n";

		// Related services
		if ( ! empty( $ai['_related_svcs'] ) ) {
			$all_svcs = class_exists( 'PT24_Scale_Data' ) ? PT24_Scale_Data::services() : [];
			$html    .= "\n<h3>Powiązane usługi</h3>\n<ul class=\"pt24-links\">\n";
			foreach ( $ai['_related_svcs'] as $rs ) {
				$rs_name = $all_svcs[ $rs ] ?? ucfirst( str_replace( '-', ' ', $rs ) );
				$rs_url  = home_url( '/' . ( $city ? $city . '/' : '' ) . $rs . '/' );
				$html   .= '<li><a href="' . esc_url( $rs_url ) . '">' . esc_html( $rs_name ) . ( $city_name ? " {$city_name}" : '' ) . "</a></li>\n";
			}
			$html .= "</ul>\n";
		}

		// Related cities
		if ( ! empty( $ai['_related_cities'] ) ) {
			$all_cities = class_exists( 'PT24_Scale_Data' ) ? PT24_Scale_Data::cities() : [];
			$html      .= "\n<h3>Ta usługa w innych miastach</h3>\n<ul class=\"pt24-links\">\n";
			foreach ( $ai['_related_cities'] as $rc ) {
				$rc_name = $all_cities[ $rc ] ?? ucfirst( str_replace( '-', ' ', $rc ) );
				$rc_url  = home_url( '/' . $rc . '/' . $svc . '/' );
				$html   .= '<li><a href="' . esc_url( $rc_url ) . '">' . esc_html( $svc_name . ' ' . $rc_name ) . "</a></li>\n";
			}
			$html .= "</ul>\n";
		}

		return $html;
	}

	/* =====================================================================
	   PUBLISH
	   ===================================================================== */

	/**
	 * Create or update a WordPress post from AI article data.
	 *
	 * @param array  $ai      Generated article data from generate_article()
	 * @param string $cat_slug Override category slug (auto-detected if empty)
	 * @return int   Post ID or 0 on failure
	 */
	public static function publish_article( array $ai, string $cat_slug = '' ): int {
		$h1          = sanitize_text_field( $ai['h1'] ?? '' );
		$slug        = sanitize_title( $ai['slug'] ?? $h1 );
		$meta_title  = sanitize_text_field( $ai['meta_title'] ?? $h1 );
		$meta_desc   = sanitize_text_field( $ai['meta_description'] ?? '' );
		$art_type    = sanitize_key( $ai['article_type'] ?? '' );
		$cat_slug    = '' !== $cat_slug ? $cat_slug : $art_type;

		if ( '' === $h1 ) {
			return 0;
		}

		$content = self::build_post_content( $ai );

		// Upsert
		$existing = get_posts( [
			'post_type'        => 'post',
			'name'             => $slug,
			'numberposts'      => 1,
			'post_status'      => 'any',
			'suppress_filters' => true,
		] );

		if ( ! empty( $existing ) ) {
			$post_id = (int) $existing[0]->ID;
			wp_update_post( [
				'ID'           => $post_id,
				'post_title'   => $h1,
				'post_content' => $content,
				'post_status'  => 'publish',
			] );
		} else {
			$post_id = (int) wp_insert_post( [
				'post_title'   => $h1,
				'post_name'    => $slug,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_type'    => 'post',
			] );
		}

		if ( $post_id <= 0 ) {
			return 0;
		}

		// Category
		if ( '' !== $cat_slug && isset( self::$categories[ $cat_slug ] ) ) {
			$term = get_term_by( 'slug', $cat_slug, 'category' );
			if ( $term ) {
				wp_set_post_categories( $post_id, [ (int) $term->term_id ], false );
			}
		}

		// Meta
		update_post_meta( $post_id, 'pt24_blog_service',  $ai['_service'] );
		update_post_meta( $post_id, 'pt24_blog_city',     $ai['_city'] );
		update_post_meta( $post_id, 'pt24_blog_type',     $art_type );
		update_post_meta( $post_id, 'pt24_blog_topic',    $ai['_topic'] );
		update_post_meta( $post_id, 'pt24_meta_title',    $meta_title );
		update_post_meta( $post_id, 'pt24_meta_description', $meta_desc );
		update_post_meta( $post_id, '_pt24_blog_ai',      '1' );

		if ( ! empty( $ai['faq'] ) ) {
			update_post_meta( $post_id, 'pearblog_faq', wp_json_encode( $ai['faq'] ) );
		}
		if ( ! empty( $ai['_related_svcs'] ) ) {
			update_post_meta( $post_id, 'pt24_related_services', wp_json_encode( $ai['_related_svcs'] ) );
		}
		if ( ! empty( $ai['_related_cities'] ) ) {
			update_post_meta( $post_id, 'pt24_related_cities', wp_json_encode( $ai['_related_cities'] ) );
		}

		// Update stats
		$stats           = (array) get_option( self::STATS_OPTION, [] );
		$stats['total']  = ( $stats['total'] ?? 0 ) + 1;
		$stats['last_ts'] = time();
		update_option( self::STATS_OPTION, $stats, false );

		return $post_id;
	}

	/* =====================================================================
	   QUEUE
	   ===================================================================== */

	/**
	 * Add topics to the async queue.
	 *
	 * @param array $items [ ['topic'=>'...', 'service'=>'...', 'city'=>'...', 'cat'=>'...'], ... ]
	 * @return int  Items added
	 */
	public static function queue_topics( array $items ): int {
		$queue = (array) get_option( self::QUEUE_OPTION, [] );
		$added = 0;

		foreach ( $items as $item ) {
			$topic   = sanitize_text_field( $item['topic']   ?? '' );
			$service = sanitize_key( $item['service'] ?? '' );
			$city    = sanitize_title( $item['city']    ?? '' );
			$cat     = sanitize_key( $item['cat']     ?? '' );

			if ( '' === $topic || '' === $service ) {
				continue;
			}

			$key = md5( $topic . '|' . $service . '|' . $city );
			if ( ! isset( $queue[ $key ] ) ) {
				$queue[ $key ] = compact( 'topic', 'service', 'city', 'cat' );
				$added++;
			}
		}

		update_option( self::QUEUE_OPTION, $queue, false );
		return $added;
	}

	/** Queue all 100 starter topics. */
	public static function queue_starters( string $city = '' ): int {
		$items = self::$starter_topics;
		if ( '' !== $city ) {
			foreach ( $items as &$item ) {
				if ( '' === ( $item['city'] ?? '' ) ) {
					$item['city'] = $city;
				}
			}
			unset( $item );
		}
		return self::queue_topics( $items );
	}

	/** Parse CSV (temat,usluga,miasto) and queue. */
	public static function queue_from_csv( string $csv_text ): array {
		$lines   = preg_split( '/\r?\n/', trim( $csv_text ) );
		$queued  = 0;
		$skipped = 0;
		$errors  = [];

		foreach ( $lines as $idx => $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}

			$cols = str_getcsv( $line );

			// Skip header
			if ( 0 === $idx && in_array( strtolower( $cols[0] ?? '' ), [ 'temat', 'topic', 'tema' ], true ) ) {
				continue;
			}

			if ( count( $cols ) < 2 ) {
				$errors[] = "Linia " . ( $idx + 1 ) . ": wymagane kolumny: temat, usluga, [miasto].";
				$skipped++;
				continue;
			}

			$topic   = trim( $cols[0] ?? '' );
			$service = sanitize_key( str_replace( ' ', '-', strtolower( trim( $cols[1] ?? '' ) ) ) );
			$city    = sanitize_title( trim( $cols[2] ?? '' ) );
			$cat     = sanitize_key( trim( $cols[3] ?? '' ) );

			if ( '' === $topic || '' === $service ) {
				$errors[] = "Linia " . ( $idx + 1 ) . ": brak tematu lub usługi.";
				$skipped++;
				continue;
			}

			$added = self::queue_topics( [ compact( 'topic', 'service', 'city', 'cat' ) ] );
			$queued += $added;
			if ( 0 === $added ) {
				$skipped++; // duplicate
			}
		}

		return [ 'queued' => $queued, 'skipped' => $skipped, 'errors' => $errors ];
	}

	/** WP-Cron: generate BATCH_SIZE articles per minute. */
	public static function process_queue(): int {
		$api_key = (string) get_option( 'pt24_openai_api_key', '' );
		if ( '' === $api_key ) {
			return 0;
		}

		$queue = (array) get_option( self::QUEUE_OPTION, [] );
		if ( empty( $queue ) ) {
			return 0;
		}

		$batch = array_splice( $queue, 0, self::BATCH_SIZE );
		update_option( self::QUEUE_OPTION, $queue, false );

		$done = 0;
		foreach ( $batch as $item ) {
			$ai = self::generate_article(
				(string) $item['topic'],
				(string) $item['service'],
				(string) ( $item['city'] ?? '' )
			);
			if ( null === $ai ) {
				continue;
			}
			$post_id = self::publish_article( $ai, (string) ( $item['cat'] ?? '' ) );
			if ( $post_id > 0 ) {
				$done++;
			}
		}

		return $done;
	}

	/* =====================================================================
	   STATS
	   ===================================================================== */

	public static function get_stats(): array {
		global $wpdb;

		return [
			'total_articles' => (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id
				 WHERE p.post_type = 'post' AND p.post_status = 'publish'
				 AND m.meta_key = '_pt24_blog_ai' AND m.meta_value = '1'"
			),
			'queue_size'     => count( (array) get_option( self::QUEUE_OPTION, [] ) ),
			'has_openai_key' => '' !== (string) get_option( 'pt24_openai_api_key', '' ),
			'starters'       => count( self::$starter_topics ),
			'last_generated' => (int) ( ( (array) get_option( self::STATS_OPTION, [] ) )['last_ts'] ?? 0 ),
		];
	}

	/* =====================================================================
	   REST API
	   ===================================================================== */

	public static function register_rest(): void {
		$auth = function( WP_REST_Request $r ): bool {
			if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
				return true;
			}
			$token  = (string) get_option( 'pt24_webhook_token', '' );
			$header = (string) $r->get_header( 'X-PT24-Token' );
			return '' !== $token && hash_equals( $token, $header );
		};

		register_rest_route( self::REST_NAMESPACE, '/blog-generate', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'rest_generate' ],
			'permission_callback' => $auth,
			'args'                => [
				'topic'     => [ 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
				'service'   => [ 'required' => true,  'sanitize_callback' => 'sanitize_key' ],
				'city'      => [ 'required' => false, 'default' => '', 'sanitize_callback' => 'sanitize_title' ],
				'cat'       => [ 'required' => false, 'default' => '' ],
				'use_queue' => [ 'required' => false, 'default' => false ],
			],
		] );

		register_rest_route( self::REST_NAMESPACE, '/blog-csv', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'rest_csv' ],
			'permission_callback' => $auth,
		] );

		register_rest_route( self::REST_NAMESPACE, '/blog-stats', [
			'methods'             => 'GET',
			'callback'            => [ __CLASS__, 'rest_stats' ],
			'permission_callback' => '__return_true',
		] );
	}

	public static function rest_generate( WP_REST_Request $request ): WP_REST_Response {
		$topic     = (string) $request->get_param( 'topic' );
		$service   = (string) $request->get_param( 'service' );
		$city      = (string) $request->get_param( 'city' );
		$cat       = sanitize_key( (string) $request->get_param( 'cat' ) );
		$use_queue = (bool) $request->get_param( 'use_queue' );

		if ( $use_queue ) {
			$added = self::queue_topics( [ [ 'topic' => $topic, 'service' => $service, 'city' => $city, 'cat' => $cat ] ] );
			return new WP_REST_Response( [ 'success' => true, 'queued' => $added ], 202 );
		}

		$ai = self::generate_article( $topic, $service, $city );
		if ( null === $ai ) {
			return new WP_REST_Response( [ 'error' => 'AI generation failed. Check OpenAI key.' ], 500 );
		}

		$post_id = self::publish_article( $ai, $cat );
		if ( 0 === $post_id ) {
			return new WP_REST_Response( [ 'error' => 'Post creation failed.' ], 500 );
		}

		return new WP_REST_Response( [
			'success' => true,
			'post_id' => $post_id,
			'url'     => get_permalink( $post_id ),
			'slug'    => $ai['slug'] ?? '',
		], 201 );
	}

	public static function rest_csv( WP_REST_Request $request ): WP_REST_Response {
		$body      = $request->get_json_params();
		$csv       = (string) ( $body['csv'] ?? '' );
		$starters  = ! empty( $body['starters'] );
		$city      = sanitize_title( (string) ( $body['city'] ?? '' ) );

		if ( $starters ) {
			$queued = self::queue_starters( $city );
			return new WP_REST_Response( [ 'success' => true, 'queued' => $queued ], 202 );
		}

		if ( '' === $csv ) {
			return new WP_REST_Response( [ 'error' => 'Provide csv or starters:true.' ], 400 );
		}

		$result = self::queue_from_csv( $csv );
		return new WP_REST_Response( array_merge( [ 'success' => true ], $result ), 202 );
	}

	public static function rest_stats( WP_REST_Request $r ): WP_REST_Response {
		return new WP_REST_Response( self::get_stats(), 200 );
	}

	/* =====================================================================
	   AJAX HANDLERS
	   ===================================================================== */

	public static function ajax_generate(): void {
		check_ajax_referer( 'pt24_blog_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Brak uprawnień.' ] );
		}

		$topic   = sanitize_text_field( wp_unslash( $_POST['topic']   ?? '' ) );
		$service = sanitize_key( wp_unslash( $_POST['service'] ?? '' ) );
		$city    = sanitize_title( wp_unslash( $_POST['city']    ?? '' ) );
		$cat     = sanitize_key( wp_unslash( $_POST['cat']     ?? '' ) );

		if ( '' === $topic || '' === $service ) {
			wp_send_json_error( [ 'message' => 'Podaj temat i usługę.' ] );
		}

		$ai = self::generate_article( $topic, $service, $city );
		if ( null === $ai ) {
			wp_send_json_error( [ 'message' => 'Błąd generowania AI. Sprawdź klucz OpenAI.' ] );
		}

		$post_id = self::publish_article( $ai, $cat );
		if ( 0 === $post_id ) {
			wp_send_json_error( [ 'message' => 'Błąd zapisu artykułu.' ] );
		}

		wp_send_json_success( [
			'post_id' => $post_id,
			'url'     => get_permalink( $post_id ),
			'message' => "Artykuł opublikowany: {$ai['h1']}",
			'stats'   => self::get_stats(),
		] );
	}

	public static function ajax_import_csv(): void {
		check_ajax_referer( 'pt24_blog_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Brak uprawnień.' ] );
		}

		$csv = sanitize_textarea_field( wp_unslash( $_POST['csv'] ?? '' ) );
		if ( '' === $csv ) {
			wp_send_json_error( [ 'message' => 'Podaj dane CSV.' ] );
		}

		$result = self::queue_from_csv( $csv );
		wp_send_json_success( [
			'message' => "Dodano {$result['queued']} tematów do kolejki, pominięto {$result['skipped']}.",
			'errors'  => $result['errors'],
			'stats'   => self::get_stats(),
		] );
	}

	public static function ajax_run_queue(): void {
		check_ajax_referer( 'pt24_blog_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Brak uprawnień.' ] );
		}

		$done = self::process_queue();
		wp_send_json_success( [
			'message' => "Wygenerowano {$done} artykułów w tej paczce.",
			'stats'   => self::get_stats(),
		] );
	}

	public static function ajax_queue_starters(): void {
		check_ajax_referer( 'pt24_blog_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Brak uprawnień.' ] );
		}

		$city   = sanitize_title( wp_unslash( $_POST['city'] ?? '' ) );
		$queued = self::queue_starters( $city );
		wp_send_json_success( [
			'message' => "Dodano {$queued} tematów startowych do kolejki.",
			'stats'   => self::get_stats(),
		] );
	}

	public static function ajax_stats(): void {
		check_ajax_referer( 'pt24_blog_nonce', 'nonce' );
		wp_send_json_success( self::get_stats() );
	}

	public static function ajax_clear_queue(): void {
		check_ajax_referer( 'pt24_blog_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Brak uprawnień.' ] );
		}
		update_option( self::QUEUE_OPTION, [], false );
		wp_send_json_success( [ 'message' => 'Kolejka bloga wyczyszczona.', 'stats' => self::get_stats() ] );
	}
}

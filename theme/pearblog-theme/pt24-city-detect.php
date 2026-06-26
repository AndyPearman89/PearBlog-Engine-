<?php
/**
 * PT24.PRO — /miasto/ auto-detect city from visitor IP.
 *
 * 1. Reads visitor IP (Cloudflare CF-Connecting-IP → X-Forwarded-For → REMOTE_ADDR).
 * 2. Queries ipapi.co (HTTPS, free tier) for city name — cached per-IP for 6 h.
 * 3. Maps detected city to a PT24 city slug and issues a 302 redirect.
 * 4. Falls back to a full city-card grid if detection fails or city not in list.
 *
 * @package PearBlog
 * @subpackage PT24
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ */
/* City data                                                            */
/* ------------------------------------------------------------------ */

$pt24_cities = function_exists( 'pt24_cities_from_database' ) ? pt24_cities_from_database() : array();

if ( empty( $pt24_cities ) && class_exists( 'PearBlog_PT24_Landing_CPT' ) ) {
	$pt24_cities = PearBlog_PT24_Landing_CPT::get_cities();
}

if ( empty( $pt24_cities ) ) {
	$pt24_cities = array(
		'warszawa' => 'Warszawa',
		'krakow'   => 'Kraków',
		'wroclaw'  => 'Wrocław',
		'poznan'   => 'Poznań',
		'gdansk'   => 'Gdańsk',
		'katowice' => 'Katowice',
	);
}

// Keys: normalised (lowercase, no diacritics) city names returned by ipapi.co.
$pt24_city_map = array();

foreach ( $pt24_cities as $slug => $name ) {
	$slug = sanitize_title( (string) $slug );
	$name = trim( (string) $name );

	if ( '' === $slug || '' === $name ) {
		continue;
	}

	$pt24_city_map[ strtolower( remove_accents( $slug ) ) ] = $slug;
	$pt24_city_map[ strtolower( remove_accents( $name ) ) ] = $slug;
}

$pt24_city_aliases = array(
	'warsaw'    => 'warszawa',
	'cracow'    => 'krakow',
	'breslau'   => 'wroclaw',
	'posen'     => 'poznan',
	'danzig'    => 'gdansk',
	'kattowitz' => 'katowice',
);

foreach ( $pt24_city_aliases as $alias => $target_slug ) {
	if ( isset( $pt24_cities[ $target_slug ] ) ) {
		$pt24_city_map[ $alias ] = $target_slug;
	}
}

/* ------------------------------------------------------------------ */
/* IP detection                                                         */
/* ------------------------------------------------------------------ */

// CF-Connecting-IP is the real visitor IP when behind Cloudflare.
$visitor_ip = '';
foreach ( [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ] as $key ) {
	if ( ! empty( $_SERVER[ $key ] ) ) {
		$visitor_ip = trim( explode( ',', (string) $_SERVER[ $key ] )[0] );
		break;
	}
}

$detected_slug = '';

if (
	$visitor_ip &&
	filter_var( $visitor_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE )
) {
	$cache_key   = 'pt24_geo_' . md5( $visitor_ip );
	$cached_city = get_transient( $cache_key );

	if ( false === $cached_city ) {
		$geo_url  = 'https://ipapi.co/' . rawurlencode( $visitor_ip ) . '/json/';
		$response = wp_safe_remote_get( $geo_url, [
			'timeout'    => 3,
			'sslverify'  => true,
			'user-agent' => 'PT24.PRO/1.0 (+https://pt24.pro)',
		] );

		$raw_city = '';
		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! empty( $body['city'] ) && empty( $body['error'] ) ) {
				$raw_city = (string) $body['city'];
			}
		}

		// Cache result (empty string stored as sentinel '__none__').
		set_transient( $cache_key, $raw_city ?: '__none__', 6 * HOUR_IN_SECONDS );
		$cached_city = $raw_city ?: '__none__';
	}

	if ( '__none__' !== $cached_city && '' !== $cached_city ) {
		// remove_accents(): WordPress helper — "Kraków" → "Krakow".
		$normalised     = strtolower( remove_accents( $cached_city ) );
		$detected_slug  = $pt24_city_map[ $normalised ] ?? '';
	}
}

if ( $detected_slug ) {
	wp_redirect( home_url( '/miasto/' . $detected_slug . '/' ), 302 );
	exit;
}

/* ------------------------------------------------------------------ */
/* Fallback: show all cities                                            */
/* ------------------------------------------------------------------ */

pearblog_render_header();
?>
<main id="main" class="pb-main pt24-landing" role="main">

	<section class="pt24-hero">
		<div class="pb-container">
			<span class="pt24-hero__badge">Fachowcy w Polsce</span>
			<h1 class="pt24-hero__title">Sprawdzeni fachowcy w Twoim mieście</h1>
			<p class="pt24-hero__lead">Hydraulicy, elektrycy, mechanicy i specjaliści od energii odnawialnej — porównaj oferty i zamów bezpłatną wycenę.</p>
		</div>
	</section>

	<div class="pb-container" style="padding-top:2.5rem;padding-bottom:3rem">
		<h2 style="margin-bottom:1.25rem">Wybierz swoje miasto</h2>
		<div class="pt24-rankings-grid">
			<?php foreach ( $pt24_cities as $cslug => $cname ) : ?>
			<a href="<?php echo esc_url( home_url( '/miasto/' . $cslug . '/' ) ); ?>" class="pt24-rankings-card">
				<span class="pt24-rankings-card__city"><?php echo esc_html( $cname ); ?></span>
				<span class="pt24-rankings-card__label">Wszystkie usługi</span>
				<span class="pt24-rankings-card__cta">Sprawdź →</span>
			</a>
			<?php endforeach; ?>
		</div>

		<div class="pt24-cta-band" style="margin-top:2.5rem">
			<div class="pt24-cta-band__inner">
				<div>
					<strong>Nie widzisz swojego miasta?</strong>
					<p>Skorzystaj z wyszukiwarki, by znaleźć fachowca w dowolnej lokalizacji.</p>
				</div>
				<a href="<?php echo esc_url( home_url( '/szukaj/' ) ); ?>" class="pt24-btn pt24-btn--cta">Wyszukaj fachowca →</a>
			</div>
		</div>
	</div>

</main>
<?php
pearblog_render_footer();

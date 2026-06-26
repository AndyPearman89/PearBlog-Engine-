<?php
/**
 * PT24.PRO — Search / Finder page template.
 * Renders when content contains <!-- pt24-search-finder --> marker.
 * Injected via the_content filter in pt24-add-firm.php-style approach.
 *
 * Also serves as standalone page template when routed to /szukaj/.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inject the search/finder UI when the page content has the marker.
 * Priority 9 = before wpautop (10) so the injected <div> isn't wrapped in <p>.
 */
add_filter( 'the_content', static function( $content ) {
	if ( false === strpos( $content, '<!-- pt24-search-finder -->' ) ) {
		return $content;
	}
	return str_replace(
		'<!-- pt24-search-finder -->',
		pt24_render_search_finder_html(),
		$content
	);
}, 9 );

/**
 * Build the full-page finder HTML with service grid + city autocomplete.
 */
function pt24_render_search_finder_html(): string {
	if ( class_exists( 'PT24_Scale_Data' ) ) {
		$services = PT24_Scale_Data::services();
		$cities   = PT24_Scale_Data::cities();
	} else {
		return '<p>Wyszukiwarka chwilowo niedostępna.</p>';
	}

	$h = esc_url( home_url( '/' ) );

	// City datalist for autocomplete.
	$datalist = '<datalist id="pt24-search-city-list">';
	foreach ( $cities as $c_slug => $c ) {
		$datalist .= '<option value="' . esc_attr( $c['name'] ) . '" data-slug="' . esc_attr( $c_slug ) . '">';
	}
	$datalist .= '</datalist>';

	$html = '<div class="pt24-search-page">';

	// Top finder bar.
	$html .= '<div class="pt24-search-bar">';
	$html .= '<h1 class="pt24-search-bar__title">Znajdź sprawdzonego fachowca</h1>';
	$html .= '<p class="pt24-search-bar__lead">Wybierz usługę i wpisz swoje miasto — połączymy Cię z lokalnymi specjalistami.</p>';
	$html .= '<div class="pt24-finder pt24-finder--lg" id="pt24-search-finder">';
	$html .= '<select id="pt24-search-service" class="pt24-finder__select" aria-label="Wybierz usługę">';
	$html .= '<option value="" disabled selected>Wybierz usługę</option>';
	foreach ( $services as $s_slug => $s ) {
		$html .= '<option value="' . esc_attr( $s_slug ) . '">' . esc_html( $s['name'] ) . '</option>';
	}
	$html .= '</select>';
	$html .= '<div class="pt24-finder__city-wrap">';
	$html .= '<input id="pt24-search-city" class="pt24-finder__input" type="search" placeholder="Wpisz miasto…" autocomplete="off" list="pt24-search-city-list" aria-label="Wpisz miasto">';
	$html .= $datalist;
	$html .= '</div>';
	$html .= '<button id="pt24-search-btn" class="pt24-btn pt24-btn--primary pt24-finder__btn" type="button">Znajdź →</button>';
	$html .= '</div>';
	$html .= '<div id="pt24-search-msg" class="pt24-search-msg" style="display:none;"></div>';
	$html .= '</div>';

	// Service cards grid — click opens service page filtered by city.
	$html .= '<section class="pt24-search-services">';
	$html .= '<h2>Wszystkie usługi (' . count( $services ) . ')</h2>';
	$html .= '<div class="pt24-search-grid">';

	$service_icons = array( 'hydraulik'=>'droplet','elektryk'=>'zap','mechanik'=>'wrench','fotowoltaika'=>'grid','pompa-ciepla'=>'thermometer','remont-lazienki'=>'home','laweta'=>'clock','wulkanizacja'=>'wrench','klimatyzacja'=>'thermometer','instalacje-gazowe'=>'zap' );

	foreach ( $services as $s_slug => $s ) {
		$icon = $service_icons[ $s_slug ] ?? 'wrench';
		$html .= '<a href="' . esc_url( $h . 'warszawa/' . $s_slug . '/' ) . '" class="pt24-search-card" data-service="' . esc_attr( $s_slug ) . '">';
		$html .= '<span class="pt24-search-card__ico"><span class="pt24-ico pt24-ico--' . esc_attr( $icon ) . '" aria-hidden="true"></span></span>';
		$html .= '<span class="pt24-search-card__name">' . esc_html( $s['name'] ) . '</span>';
		$html .= '<span class="pt24-search-card__cta">Szukaj →</span>';
		$html .= '</a>';
	}
	$html .= '</div></section>';

	// Popular cities quick-pick.
	$html .= '<section class="pt24-search-cities">';
	$html .= '<h2>Wybierz miasto</h2>';
	$html .= '<div class="pt24-search-city-grid">';
	$i = 0;
	foreach ( $cities as $c_slug => $c ) {
		$html .= '<button class="pt24-search-city-btn" data-city="' . esc_attr( $c_slug ) . '" data-name="' . esc_attr( $c['name'] ) . '" type="button">';
		$html .= '<span class="pt24-ico pt24-ico--pin" aria-hidden="true"></span>';
		$html .= esc_html( $c['name'] );
		$html .= '</button>';
		if ( ++$i >= 24 ) break;
	}
	$html .= '</div>';
	$html .= '<p class="pt24-search-cities__more" id="pt24-search-cities-more" style="display:none;">Szukasz innego miasta? Wpisz jego nazwę w polu powyżej.</p>';
	$html .= '</section>';

	$html .= '</div>';

	return $html;
}

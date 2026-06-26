<?php
/**
 * PT24.PRO — City hub /miasto/{city}/.
 *
 * Routed via pt24_city_hub query var set in pt24-landing-cpt.php.
 *
 * @package PearBlog
 * @subpackage PT24
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$city_slug = get_query_var( 'pt24_city_hub' );

$cities = [
	'warszawa' => 'Warszawa',
	'krakow'   => 'Kraków',
	'wroclaw'  => 'Wrocław',
	'poznan'   => 'Poznań',
	'gdansk'   => 'Gdańsk',
	'katowice' => 'Katowice',
];

$services = [
	'hydraulik'       => 'Hydraulik',
	'elektryk'        => 'Elektryk',
	'mechanik'        => 'Mechanik samochodowy',
	'pompa-ciepla'    => 'Pompa ciepła',
	'remont-lazienki' => 'Remont łazienki',
	'fotowoltaika'    => 'Fotowoltaika',
];

if ( ! isset( $cities[ $city_slug ] ) ) {
	wp_redirect( home_url( '/' ), 302 );
	exit;
}

$city_name = $cities[ $city_slug ];

$city_loc = [
	'warszawa' => 'Warszawie',
	'krakow'   => 'Krakowie',
	'wroclaw'  => 'Wrocławiu',
	'poznan'   => 'Poznaniu',
	'gdansk'   => 'Gdańsku',
	'katowice' => 'Katowicach',
];
$city_locative = isset( $city_loc[ $city_slug ] ) ? $city_loc[ $city_slug ] : $city_name;

// Count firms in this city.
$firm_count = (int) ( new WP_Query( [
	'post_type'      => 'pt24_firm',
	'post_status'    => 'publish',
	'posts_per_page' => 1,
	'meta_query'     => [ [ 'key' => 'pt24_city', 'value' => $city_slug ] ],
	'fields'         => 'ids',
] ) )->found_posts;

pearblog_render_header();
?>
<main id="main" class="pb-main pt24-landing" role="main">

	<section class="pt24-hero">
		<div class="pb-container">
			<span class="pt24-hero__badge">Fachowcy</span>
			<h1 class="pt24-hero__title">Sprawdzeni fachowcy w <?php echo esc_html( $city_locative ); ?></h1>
			<p class="pt24-hero__lead">Hydraulicy, elektrycy, mechanicy i specjaliści od energii odnawialnej — porównaj oferty i zamów bezpłatną wycenę.</p>
			<div class="pt24-hero__cta">
				<a href="#pt24-lead" class="pt24-btn pt24-btn--primary">Zamów bezpłatną wycenę</a>
				<a href="<?php echo esc_url( home_url( '/ranking/' . $city_slug . '/hydraulik/' ) ); ?>" class="pt24-btn pt24-btn--outline">Top firmy w <?php echo esc_html( $city_locative ); ?></a>
			</div>
			<div class="pt24-hero__trust">
				<span class="pt24-hero__trust-item">✅ <strong><?php echo esc_html( $firm_count ? $firm_count . '+' : 'Zweryfikowani' ); ?></strong> firm w <?php echo esc_html( $city_locative ); ?></span>
				<span class="pt24-hero__trust-item">⭐ <strong>4.8/5</strong> średnia ocena</span>
				<span class="pt24-hero__trust-item">⚡ <strong>Wycena</strong> w 2 godziny</span>
				<span class="pt24-hero__trust-item">🔒 <strong>Bezpłatna</strong> wycena</span>
			</div>
		</div>
	</section>

	<div class="pb-container">

		<section class="pt24-section">
			<h2>Usługi dostępne w <?php echo esc_html( $city_locative ); ?></h2>
			<div class="pt24-rankings-grid">
				<?php foreach ( $services as $sslug => $sname ) :
					$landing_url = home_url( "/{$city_slug}/{$sslug}/" );
					$ranking_url = home_url( "/ranking/{$city_slug}/{$sslug}/" );
				?>
				<div class="pt24-city-service-card">
					<span class="pt24-city-service-card__name"><?php echo esc_html( $sname ); ?></span>
					<div class="pt24-city-service-card__actions">
						<a href="<?php echo esc_url( $landing_url ); ?>" class="pt24-btn pt24-btn--primary pt24-btn--sm">Wyceń →</a>
						<a href="<?php echo esc_url( $ranking_url ); ?>" class="pt24-btn pt24-btn--outline pt24-btn--sm">Ranking</a>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="pt24-section pt24-city-rankings">
			<h2>Rankingi firm w <?php echo esc_html( $city_locative ); ?></h2>
			<p>Porównaj najlepiej oceniane firmy w poszczególnych kategoriach.</p>
			<ul class="pt24-links">
				<?php foreach ( $services as $sslug => $sname ) : ?>
				<li><a href="<?php echo esc_url( home_url( "/ranking/{$city_slug}/{$sslug}/" ) ); ?>">Ranking: <?php echo esc_html( $sname ); ?> <?php echo esc_html( $city_name ); ?> 2026</a></li>
				<?php endforeach; ?>
			</ul>
		</section>

		<section class="pt24-section pt24-leadbox" id="pt24-lead">
			<div class="pt24-leadbox">
				<h2 class="pt24-leadbox__title">Zamów bezpłatną wycenę w <?php echo esc_html( $city_locative ); ?></h2>
				<p class="pt24-leadbox__sub">Wypełnij formularz — skontaktujemy Cię z najlepszym fachowcem.</p>
				<?php
				$ajax_url = admin_url( 'admin-ajax.php' );
				?>
				<form class="pt24-leadform" method="post" action="<?php echo esc_url( $ajax_url ); ?>">
					<input type="hidden" name="action" value="pt24_submit_lead">
					<input type="hidden" name="service" value="">
					<input type="hidden" name="city" value="<?php echo esc_attr( $city_slug ); ?>">
					<input type="hidden" name="source_url" value="<?php echo esc_url( home_url( '/miasto/' . $city_slug . '/' ) ); ?>">
					<?php wp_nonce_field( 'pt24_nonce', 'nonce' ); ?>
					<label>Imię i nazwisko
						<input type="text" name="name" required autocomplete="name">
					</label>
					<label>Telefon
						<input type="tel" name="phone" required autocomplete="tel" placeholder="np. 600 100 200">
					</label>
					<label>Usługa (np. hydraulik, elektryk)
						<input type="text" name="service_note" placeholder="Czego potrzebujesz?">
					</label>
					<label>Opis zlecenia
						<textarea name="message" rows="3" placeholder="Opisz krótko zakres pracy…"></textarea>
					</label>
					<div class="pt24-leadform__result" role="status" aria-live="polite"></div>
					<button type="submit" class="pt24-btn pt24-btn--primary pt24-btn--block">Wyślij zapytanie →</button>
					<p class="pt24-leadform__gdpr">Przesyłając formularz, wyrażasz zgodę na kontakt telefoniczny i przetwarzanie danych w celu obsługi zapytania. Możesz cofnąć zgodę w każdej chwili.</p>
				</form>
			</div>
		</section>

		<section class="pt24-section pt24-internal" style="margin-top:2rem">
			<h2>Inne miasta</h2>
			<div class="pt24-rankings-grid">
				<?php foreach ( $cities as $cslug => $cname ) :
					if ( $cslug === $city_slug ) continue;
				?>
				<a href="<?php echo esc_url( home_url( '/miasto/' . $cslug . '/' ) ); ?>" class="pt24-rankings-card">
					<span class="pt24-rankings-card__city"><?php echo esc_html( $cname ); ?></span>
					<span class="pt24-rankings-card__label">Wszystkie usługi</span>
					<span class="pt24-rankings-card__cta">Sprawdź →</span>
				</a>
				<?php endforeach; ?>
			</div>
			<p style="margin-top:1.5rem"><a href="<?php echo esc_url( home_url( '/rankingi/' ) ); ?>">← Wszystkie rankingi fachowców</a></p>
		</section>

	</div>

</main>

<div class="pt24-sticky-cta" aria-label="Szybki formularz">
	<p class="pt24-sticky-cta__text">Bezpłatne wyceny od fachowców</p>
	<a href="#pt24-lead" class="pt24-btn pt24-btn--primary">Zamów wycenę</a>
</div>

<button class="pt24-scroll-top" aria-label="Przewiń do góry" type="button">
	<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
</button>

<script>
(function(){
	var form = document.querySelector('.pt24-leadform');
	if(!form) return;
	form.addEventListener('submit', function(e){
		e.preventDefault();
		var result = form.querySelector('.pt24-leadform__result');
		var btn = form.querySelector('button[type=submit]');
		btn.disabled = true; btn.textContent = 'Wysyłanie…';
		fetch(form.action, { method:'POST', body: new FormData(form), credentials:'same-origin' })
			.then(function(r){ return r.json(); })
			.then(function(data){
				if(data.success){
					result.innerHTML = '<span class="pt24-leadform__ok">✅ ' + (data.data && data.data.message ? data.data.message : 'Dziękujemy! Odezwiemy się wkrótce.') + '</span>';
					form.reset();
				} else {
					result.innerHTML = '<span class="pt24-leadform__err">Błąd: ' + (data.data && data.data.message ? data.data.message : 'Spróbuj ponownie.') + '</span>';
					btn.disabled = false; btn.textContent = 'Wyślij zapytanie →';
				}
			})
			.catch(function(){
				result.innerHTML = '<span class="pt24-leadform__err">Błąd sieci. Spróbuj ponownie.</span>';
				btn.disabled = false; btn.textContent = 'Wyślij zapytanie →';
			});
	});
})();
</script>
<?php
pearblog_render_footer();

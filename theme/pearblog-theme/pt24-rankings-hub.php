<?php
/**
 * PT24.PRO — Rankings hub /rankingi/.
 *
 * Linked from the footer and routed via the request filter in
 * pt24-landing-cpt.php (query var pt24_rankings_index).
 *
 * @package PearBlog
 * @subpackage PT24
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

pearblog_render_header();

$services = [
	'hydraulik'          => [ 'label' => 'Hydraulik',            'icon' => 'droplet' ],
	'elektryk'           => [ 'label' => 'Elektryk',             'icon' => 'zap' ],
	'mechanik'           => [ 'label' => 'Mechanik samochodowy', 'icon' => 'wrench' ],
	'fotowoltaika'       => [ 'label' => 'Fotowoltaika',         'icon' => 'grid' ],
	'pompa-ciepla'       => [ 'label' => 'Pompa ciepła',         'icon' => 'thermometer' ],
	'remont-lazienki'    => [ 'label' => 'Remont łazienki',      'icon' => 'home' ],
	'klimatyzacja'       => [ 'label' => 'Klimatyzacja',         'icon' => 'wind' ],
	'laweta'             => [ 'label' => 'Laweta',               'icon' => 'truck' ],
	'wulkanizacja'       => [ 'label' => 'Wulkanizacja',         'icon' => 'settings' ],
	'instalacje-gazowe'  => [ 'label' => 'Instalacje gazowe',    'icon' => 'flame' ],
];

$cities = [
	'warszawa' => 'Warszawa',
	'krakow'   => 'Kraków',
	'wroclaw'  => 'Wrocław',
	'poznan'   => 'Poznań',
	'gdansk'   => 'Gdańsk',
	'katowice' => 'Katowice',
];
?>
<main id="main" class="pb-main pt24-landing" role="main">

	<section class="pt24-hero">
		<div class="pb-container">
			<span class="pt24-hero__badge">Rankingi firm</span>
			<h1 class="pt24-hero__title">Ranking najlepszych fachowców</h1>
			<p class="pt24-hero__lead">Sprawdź, kto jest najwyżej oceniany w Twoim mieście. Wszystkie rankingi oparte na opiniach klientów i liczbie zrealizowanych zleceń.</p>
			<div class="pt24-hero__cta">
				<a href="<?php echo esc_url( home_url( '/szukaj/' ) ); ?>" class="pt24-btn pt24-btn--primary">Znajdź fachowca w swoim mieście</a>
			</div>
			<div class="pt24-hero__trust">
				<span class="pt24-hero__trust-item">✅ <strong>Zweryfikowani</strong> fachowcy</span>
				<span class="pt24-hero__trust-item">⭐ <strong>4.8/5</strong> średnia ocena</span>
				<span class="pt24-hero__trust-item">🏆 <strong>Rankingi</strong> oparte na opiniach</span>
				<span class="pt24-hero__trust-item">🔒 <strong>Bezpłatna</strong> wycena</span>
			</div>
		</div>
	</section>

	<div class="pb-container">
		<?php foreach ( $services as $srv_slug => $srv ) : ?>
		<section class="pt24-rankings-section" id="<?php echo esc_attr( $srv_slug ); ?>">
			<div class="pt24-rankings-section__head">
				<span class="pt24-ico pt24-ico--<?php echo esc_attr( $srv['icon'] ); ?>"></span>
				<h2><?php echo esc_html( $srv['label'] ); ?> — ranking w miastach</h2>
			</div>
			<div class="pt24-rankings-grid">
				<?php foreach ( $cities as $city_slug => $city_name ) :
					$url = home_url( '/ranking/' . $city_slug . '/' . $srv_slug . '/' );
				?>
				<a href="<?php echo esc_url( $url ); ?>" class="pt24-rankings-card">
					<span class="pt24-rankings-card__city"><?php echo esc_html( $city_name ); ?></span>
					<span class="pt24-rankings-card__label"><?php echo esc_html( $srv['label'] ); ?></span>
					<span class="pt24-rankings-card__cta">Zobacz ranking →</span>
				</a>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endforeach; ?>

		<!-- CTA band -->
		<div class="pt24-cta-band" style="margin-top:2.5rem;">
			<div class="pt24-cta-band__inner">
				<div>
					<strong>Jesteś fachowcem?</strong>
					<p>Zarejestruj firmę w PT24 — Twój profil pojawi się w rankingach Twojego miasta.</p>
				</div>
				<a href="<?php echo esc_url( home_url( '/dodaj-firme/' ) ); ?>" class="pt24-btn pt24-btn--cta">Dodaj firmę →</a>
			</div>
		</div>
	</div>

</main>
<?php
pearblog_render_footer();

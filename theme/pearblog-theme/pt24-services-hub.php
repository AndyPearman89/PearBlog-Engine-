<?php
/**
 * PT24.PRO — Services hub /uslugi/.
 *
 * Lists all 10 services, each with 6 city ranking links.
 * Routed via pt24_uslugi_index query var set in pt24-landing-cpt.php.
 *
 * @package PearBlog
 * @subpackage PT24
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$services = class_exists( 'PT24_Scale_Data' )
	? array_map( fn( $s ) => [ 'label' => $s['name'], 'icon' => $s['icon'] ], PT24_Scale_Data::services() )
	: [
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

pearblog_render_header();
?>
<main id="main" class="pb-main pt24-landing" role="main">

	<section class="pt24-hero">
		<div class="pb-container">
			<span class="pt24-hero__badge">Usługi</span>
			<h1 class="pt24-hero__title">Wszystkie usługi — znajdź fachowca w swoim mieście</h1>
			<p class="pt24-hero__lead">Hydraulicy, elektrycy, mechanicy, serwis klimatyzacji, laweta i więcej — porównaj firmy, sprawdź opinie i zamów bezpłatną wycenę.</p>
			<div class="pt24-hero__cta">
				<a href="<?php echo esc_url( home_url( '/szukaj/' ) ); ?>" class="pt24-btn pt24-btn--primary">Znajdź fachowca w swoim mieście</a>
				<a href="<?php echo esc_url( home_url( '/rankingi/' ) ); ?>" class="pt24-btn pt24-btn--outline">Rankingi firm →</a>
			</div>
			<div class="pt24-hero__trust">
				<span class="pt24-hero__trust-item">✅ <strong>10</strong> kategorii usług</span>
				<span class="pt24-hero__trust-item">🏙️ <strong>6</strong> największych miast</span>
				<span class="pt24-hero__trust-item">⭐ <strong>4.8/5</strong> średnia ocena firm</span>
				<span class="pt24-hero__trust-item">🔒 <strong>Bezpłatna</strong> wycena</span>
			</div>
		</div>
	</section>

	<div class="pb-container">

		<?php foreach ( $services as $srv_slug => $srv ) : ?>
		<section class="pt24-rankings-section" id="<?php echo esc_attr( $srv_slug ); ?>">
			<div class="pt24-rankings-section__head">
				<span class="pt24-ico pt24-ico--<?php echo esc_attr( $srv['icon'] ); ?>"></span>
				<h2><?php echo esc_html( $srv['label'] ); ?></h2>
			</div>
			<div class="pt24-rankings-grid">
				<?php foreach ( $cities as $city_slug => $city_name ) :
					$landing_url = home_url( "/{$city_slug}/{$srv_slug}/" );
					$ranking_url = home_url( "/ranking/{$city_slug}/{$srv_slug}/" );
				?>
				<div class="pt24-service-city-card">
					<span class="pt24-service-city-card__city"><?php echo esc_html( $city_name ); ?></span>
					<span class="pt24-service-city-card__svc"><?php echo esc_html( $srv['label'] ); ?></span>
					<div class="pt24-service-city-card__actions">
						<a href="<?php echo esc_url( $landing_url ); ?>" class="pt24-btn pt24-btn--primary pt24-btn--sm">Wyceń →</a>
						<a href="<?php echo esc_url( $ranking_url ); ?>" class="pt24-btn pt24-btn--outline pt24-btn--sm">Ranking</a>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endforeach; ?>

		<div class="pt24-cta-band" style="margin-top:2.5rem">
			<div class="pt24-cta-band__inner">
				<div>
					<strong>Jesteś fachowcem?</strong>
					<p>Zarejestruj firmę w PT24 — profil pojawi się w rankingach Twojego miasta.</p>
				</div>
				<a href="<?php echo esc_url( home_url( '/dodaj-firme/' ) ); ?>" class="pt24-btn pt24-btn--cta">Dodaj firmę →</a>
			</div>
		</div>

	</div>

</main>
<?php
pearblog_render_footer();

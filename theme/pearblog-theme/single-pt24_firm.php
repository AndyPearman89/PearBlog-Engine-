<?php
/**
 * PT24.PRO — single company profile template (/firma/{slug}/).
 *
 * @package PearBlog
 * @subpackage PT24
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pt24_firm_id   = (int) get_the_ID();
$pt24_firm_name = get_the_title();
$city_slug      = (string) get_post_meta( $pt24_firm_id, 'pt24_firm_city', true );
$city_name      = (string) get_post_meta( $pt24_firm_id, 'pt24_firm_city_name', true );
$services_csv   = (string) get_post_meta( $pt24_firm_id, 'pt24_firm_services', true );
$rating         = (string) get_post_meta( $pt24_firm_id, 'pt24_firm_rating', true );
$jobs           = (int) get_post_meta( $pt24_firm_id, 'pt24_firm_jobs', true );
$established     = (string) get_post_meta( $pt24_firm_id, 'pt24_firm_established', true );

if ( '' === $city_name ) {
	$city_name = ucfirst( $city_slug );
}

$service_slugs = array_values( array_filter( array_map( 'trim', explode( ',', $services_csv ) ) ) );
$service_names = array();
$svc_map       = class_exists( 'PearBlog_PT24_Landing_CPT' ) ? PearBlog_PT24_Landing_CPT::get_services() : array();
foreach ( $service_slugs as $ss ) {
	$service_names[ $ss ] = $svc_map[ $ss ] ?? ucfirst( str_replace( '-', ' ', $ss ) );
}

$primary_service = $service_slugs[0] ?? 'hydraulik';
$cta_url         = home_url( '/' . $city_slug . '/' . $primary_service . '/#pt24-lead' );

pearblog_render_header();
?>
<main id="main" class="pb-main pt24-landing" role="main">

	<section class="pt24-hero">
		<div class="pb-container">
			<?php echo function_exists( 'pearblog_get_breadcrumbs' ) ? pearblog_get_breadcrumbs() : ''; ?>
			<span class="pt24-hero__badge">Profil firmy · <?php echo esc_html( $city_name ); ?></span>
			<h1 class="pt24-hero__title"><?php echo esc_html( $pt24_firm_name ); ?></h1>
			<p class="pt24-hero__lead">
				<span class="pt24-firm-stars">★</span> <?php echo esc_html( '' !== $rating ? $rating : '4,8' ); ?>
				· <?php echo (int) $jobs; ?> zrealizowanych zleceń<?php echo $established ? ' · na rynku od ' . esc_html( $established ) : ''; ?>
			</p>
			<div class="pt24-hero__cta">
				<a href="<?php echo esc_url( $cta_url ); ?>" class="pt24-btn pt24-btn--primary">Zamów wycenę</a>
				<span class="pt24-hero__note">Bezpłatnie i bez zobowiązań</span>
			</div>
		</div>
	</section>

	<div class="pb-container pt24-page">

		<section class="pt24-section">
			<h2>O firmie</h2>
			<div class="pt24-firm-about"><?php the_content(); ?></div>
		</section>

		<?php if ( ! empty( $service_names ) ) : ?>
		<section class="pt24-section">
			<h2>Zakres usług w <?php echo esc_html( $city_name ); ?></h2>
			<ul class="pt24-links">
				<?php foreach ( $service_names as $ss => $sn ) : ?>
					<li><a href="<?php echo esc_url( home_url( '/' . $city_slug . '/' . $ss . '/' ) ); ?>"><?php echo esc_html( $sn . ' ' . $city_name ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php endif; ?>

		<section class="pt24-cta-band">
			<h2>Potrzebujesz wyceny?</h2>
			<p>Opisz zlecenie, a otrzymasz ofertę od firmy <?php echo esc_html( $pt24_firm_name ); ?> oraz innych sprawdzonych specjalistów w <?php echo esc_html( $city_name ); ?>.</p>
			<p><a class="pt24-btn pt24-btn--primary" href="<?php echo esc_url( $cta_url ); ?>">Zamów bezpłatną wycenę</a></p>
		</section>

	</div>

	<script type="application/ld+json"><?php
	echo wp_json_encode(
		array(
			'@context'    => 'https://schema.org',
			'@type'       => 'LocalBusiness',
			'name'        => $pt24_firm_name,
			'areaServed'  => $city_name,
			'address'     => array(
				'@type'           => 'PostalAddress',
				'addressLocality' => $city_name,
				'addressCountry'  => 'PL',
			),
			'url'         => home_url( '/firma/' . get_post_field( 'post_name', $pt24_firm_id ) . '/' ),
		),
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	);
	?></script>

</main>
<?php
pearblog_render_footer();

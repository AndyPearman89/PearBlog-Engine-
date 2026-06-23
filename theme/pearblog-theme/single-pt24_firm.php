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
$established    = (string) get_post_meta( $pt24_firm_id, 'pt24_firm_established', true );
$phone          = (string) get_post_meta( $pt24_firm_id, 'pt24_firm_phone', true );
$address        = (string) get_post_meta( $pt24_firm_id, 'pt24_firm_address', true );
$website        = (string) get_post_meta( $pt24_firm_id, 'pt24_firm_website', true );
$maps_url       = (string) get_post_meta( $pt24_firm_id, 'pt24_firm_maps_url', true );
$lat            = (float)  get_post_meta( $pt24_firm_id, 'pt24_firm_lat', true );
$lng            = (float)  get_post_meta( $pt24_firm_id, 'pt24_firm_lng', true );
$hours_json     = (string) get_post_meta( $pt24_firm_id, 'pt24_firm_hours', true );
$faq_json       = (string) get_post_meta( $pt24_firm_id, 'pt24_firm_faq', true );
$svc_list_json  = (string) get_post_meta( $pt24_firm_id, 'pt24_firm_services_list', true );

$opening_hours  = $hours_json    ? (array) json_decode( $hours_json,    true ) : [];
$firm_faq       = $faq_json      ? (array) json_decode( $faq_json,      true ) : [];
$firm_svc_list  = $svc_list_json ? (array) json_decode( $svc_list_json, true ) : [];

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
				<span class="pt24-firm__stars" aria-hidden="true">★★★★★</span> <?php echo esc_html( '' !== $rating ? $rating : '4,8' ); ?>
				· <?php echo (int) $jobs; ?> zrealizowanych zleceń<?php echo $established ? ' · na rynku od ' . esc_html( $established ) : ''; ?>
			</p>
			<div class="pt24-hero__cta">
				<a href="<?php echo esc_url( $cta_url ); ?>" class="pt24-btn pt24-btn--primary">Zamów wycenę</a>
				<a href="<?php echo esc_url( home_url( '/szukaj/' ) ); ?>" class="pt24-btn pt24-btn--ghost-light">Porównaj oferty</a>
			</div>
			<div class="pt24-hero__trust">
				<span class="pt24-hero__trust-item">✅ <strong>Zweryfikowana</strong> firma</span>
				<span class="pt24-hero__trust-item">⭐ <strong><?php echo esc_html( '' !== $rating ? $rating : '4,8' ); ?>/5</strong> ocena</span>
				<span class="pt24-hero__trust-item">📋 <strong><?php echo (int) $jobs; ?></strong> zleceń</span>
				<span class="pt24-hero__trust-item">🔒 <strong>Bezpłatna</strong> wycena</span>
			</div>
		</div>
	</section>

	<div class="pb-container pt24-page">

		<section class="pt24-section">
			<h2>O firmie</h2>
			<div class="pt24-firm-about"><?php the_content(); ?></div>
			<?php if ( $address ) : ?>
				<p><strong>Adres:</strong> <?php echo esc_html( $address ); ?></p>
			<?php endif; ?>
			<?php if ( $phone ) : ?>
				<p><strong>Telefon:</strong> <a href="tel:<?php echo esc_attr( preg_replace( '/[^+\d]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></p>
			<?php endif; ?>
			<?php if ( $website ) : ?>
				<p><strong>Strona www:</strong> <a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener"><?php echo esc_html( preg_replace( '#^https?://#', '', rtrim( $website, '/' ) ) ); ?></a></p>
			<?php endif; ?>
		</section>

		<?php
		// AI-generated services list (from Prompt Nr 3)
		$services_display = ! empty( $firm_svc_list ) ? $firm_svc_list : [];
		// Fall back to CPT-linked service pages if no AI list
		if ( empty( $services_display ) && ! empty( $service_names ) ) :
		?>
		<section class="pt24-section">
			<h2>Zakres usług w <?php echo esc_html( $city_name ); ?></h2>
			<ul class="pt24-tasks">
				<?php foreach ( $service_names as $ss => $sn ) : ?>
					<li><a href="<?php echo esc_url( home_url( '/' . $city_slug . '/' . $ss . '/' ) ); ?>"><?php echo esc_html( $sn . ' ' . $city_name ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php elseif ( ! empty( $services_display ) ) : ?>
		<section class="pt24-section">
			<h2>Zakres usług</h2>
			<ul class="pt24-tasks">
				<?php foreach ( $services_display as $svc_item ) : ?>
					<li><?php echo esc_html( $svc_item ); ?></li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php endif; ?>

		<?php if ( ! empty( $opening_hours ) ) : ?>
		<section class="pt24-section">
			<h2>Godziny otwarcia</h2>
			<ul class="pt24-tasks">
				<?php foreach ( $opening_hours as $day ) : ?>
					<li><?php echo esc_html( $day ); ?></li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php endif; ?>

		<?php if ( $maps_url && $lat && $lng ) : ?>
		<section class="pt24-section">
			<h2>Lokalizacja</h2>
			<p><a href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener" class="pt24-btn pt24-btn--ghost">📍 Otwórz w Google Maps</a></p>
		</section>
		<?php endif; ?>

		<?php if ( ! empty( $firm_faq ) ) : ?>
		<section class="pt24-section pt24-faq">
			<h2>Najczęstsze pytania</h2>
			<?php foreach ( $firm_faq as $qa ) : ?>
				<details class="pt24-faq__item">
					<summary><?php echo esc_html( $qa['q'] ?? '' ); ?></summary>
					<p><?php echo esc_html( $qa['a'] ?? '' ); ?></p>
				</details>
			<?php endforeach; ?>
			<?php
			$faq_schema = [ '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => [] ];
			foreach ( $firm_faq as $qa ) {
				$faq_schema['mainEntity'][] = [ '@type' => 'Question', 'name' => $qa['q'] ?? '', 'acceptedAnswer' => [ '@type' => 'Answer', 'text' => $qa['a'] ?? '' ] ];
			}
			?>
			<script type="application/ld+json"><?php echo wp_json_encode( $faq_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
		</section>
		<?php endif; ?>

		<section class="pt24-cta-band">
			<h2>Potrzebujesz wyceny?</h2>
			<p>Opisz zlecenie, a otrzymasz ofertę od firmy <?php echo esc_html( $pt24_firm_name ); ?> oraz innych sprawdzonych specjalistów w <?php echo esc_html( $city_name ); ?>.</p>
			<p><a class="pt24-btn pt24-btn--primary" href="<?php echo esc_url( $cta_url ); ?>">Zamów bezpłatną wycenę</a></p>
		</section>

	</div>

	<script type="application/ld+json"><?php
	$ld = [
		'@context'   => 'https://schema.org',
		'@type'      => 'LocalBusiness',
		'name'       => $pt24_firm_name,
		'areaServed' => $city_name,
		'address'    => [
			'@type'           => 'PostalAddress',
			'streetAddress'   => $address,
			'addressLocality' => $city_name,
			'addressCountry'  => 'PL',
		],
		'url' => home_url( '/firma/' . get_post_field( 'post_name', $pt24_firm_id ) . '/' ),
	];
	if ( $phone )    $ld['telephone']     = $phone;
	if ( $website )  $ld['url']           = $website;
	if ( $maps_url ) $ld['hasMap']        = $maps_url;
	if ( $rating )   $ld['aggregateRating'] = [
		'@type'       => 'AggregateRating',
		'ratingValue' => str_replace( ',', '.', $rating ),
		'reviewCount' => (string) $jobs,
		'bestRating'  => '5',
		'worstRating' => '1',
	];
	if ( $lat && $lng ) $ld['geo'] = [ '@type' => 'GeoCoordinates', 'latitude' => $lat, 'longitude' => $lng ];
	echo wp_json_encode( $ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	?></script>

</main>
<?php
pearblog_render_footer();

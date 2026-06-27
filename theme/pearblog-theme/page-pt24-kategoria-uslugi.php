<?php
/**
 * PT24.PRO — Service category page (/uslugi/ and /uslugi/{slug}/).
 *
 * Renders either the full services index (when no slug) or a single service
 * category page with real data from pt24_firm posts and database stats.
 *
 * @package PearBlog
 * @subpackage PT24
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

pearblog_render_header();

$service_slug = PearBlog_PT24_Pro_Routing::get_current_service();
$services     = class_exists( 'PearBlog_PT24_Landing_CPT' )
	? PearBlog_PT24_Landing_CPT::get_services()
	: PearBlog_PT24_Pro_Routing::get_all_services();

$cities = class_exists( 'PearBlog_PT24_Landing_CPT' )
	? PearBlog_PT24_Landing_CPT::get_cities()
	: [ 'warszawa' => 'Warszawa', 'krakow' => 'Kraków', 'katowice' => 'Katowice' ];

$icon_map = [
	'hydraulik'     => '💧', 'elektryk'      => '⚡', 'mechanik'   => '🔧',
	'dekarz'        => '🏠', 'pompy-ciepla'  => '♨️', 'fotowoltaika' => '☀️',
	'klimatyzacja'  => '❄️', 'brukarz'       => '🧱', 'remont'     => '🛠️',
	'ogrodnik'      => '🌿', 'malarz'        => '🎨', 'stolarz'    => '🪚',
	'glazurnik'     => '🔲', 'instalator'    => '🔌', 'sprzatanie' => '🧹',
	'przeprowadzki' => '📦', 'murarz'        => '🧱', 'pompa-ciepla' => '♨️',
	'remont-lazienki' => '🚿',
];

if ( '' === $service_slug ) :
	// ═══════ INDEX: all services ═══════
?>
<main id="main" class="pb-main pt24-landing" role="main">
	<section class="pt24-hero">
		<div class="pb-container">
			<span class="pt24-hero__badge">Katalog usług</span>
			<h1 class="pt24-hero__title">Usługi na PT24.PRO</h1>
			<p class="pt24-hero__lead">Przejrzyj wszystkie kategorie usług i znajdź fachowca w swojej okolicy.</p>
		</div>
	</section>

	<div class="pb-container pt24-page">
		<section class="pt24-section">
			<div class="pt24-firms">
				<?php foreach ( $services as $slug => $name ) :
					$icon = $icon_map[ $slug ] ?? '🔹';
					// Count firms offering this service.
					$firm_count = (int) ( new WP_Query( [
						'post_type'      => 'pt24_firm',
						'post_status'    => 'publish',
						'meta_key'       => 'pt24_firm_services',
						'meta_value'     => $slug,
						'meta_compare'   => 'LIKE',
						'posts_per_page' => 1,
						'fields'         => 'ids',
					] ) )->found_posts;
				?>
					<div class="pt24-firm">
						<div class="pt24-firm__head">
							<span class="pt24-firm__ico"><?php echo esc_html( $icon ); ?></span>
							<h3 class="pt24-firm__name"><a href="<?php echo esc_url( home_url( '/uslugi/' . $slug . '/' ) ); ?>"><?php echo esc_html( $name ); ?></a></h3>
						</div>
						<p class="pt24-firm__meta"><?php echo (int) $firm_count; ?> firm w katalogu</p>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="pt24-cta-band">
			<h2>Szukasz konkretnej usługi?</h2>
			<p>Dodaj zapytanie, a dopasowane firmy same się z Tobą skontaktują.</p>
			<p><a class="pt24-btn pt24-btn--primary" href="<?php echo esc_url( home_url( '/dodaj-zlecenie/' ) ); ?>">Dodaj zapytanie</a></p>
		</section>
	</div>
</main>
<?php
else :
	// ═══════ SINGLE SERVICE CATEGORY ═══════
	$service_name = $services[ $service_slug ] ?? ucfirst( str_replace( '-', ' ', $service_slug ) );
	$icon         = $icon_map[ $service_slug ] ?? '🔹';

	// Get firms that offer this service.
	$firms_query = new WP_Query( [
		'post_type'      => 'pt24_firm',
		'post_status'    => 'publish',
		'meta_key'       => 'pt24_firm_services',
		'meta_value'     => $service_slug,
		'meta_compare'   => 'LIKE',
		'posts_per_page' => 20,
		'orderby'        => 'title',
		'order'          => 'ASC',
	] );

	// Recent leads for this service.
	global $wpdb;
	$leads_table  = $wpdb->prefix . 'pt24_leads';
	$recent_leads = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$leads_table} WHERE service = %s AND created_at >= %s",
		$service_slug,
		gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) )
	) );
?>
<main id="main" class="pb-main pt24-landing" role="main">
	<section class="pt24-hero">
		<div class="pb-container">
			<?php echo function_exists( 'pearblog_get_breadcrumbs' ) ? pearblog_get_breadcrumbs() : ''; ?>
			<span class="pt24-hero__badge"><?php echo esc_html( $icon ); ?> <?php echo esc_html( $service_name ); ?></span>
			<h1 class="pt24-hero__title"><?php echo esc_html( $service_name ); ?> — znajdź fachowca</h1>
			<p class="pt24-hero__lead">Porównaj oferty firm z kategorii „<?php echo esc_html( $service_name ); ?>" i zamów bezpłatną wycenę.</p>
			<div class="pt24-hero__cta">
				<a href="<?php echo esc_url( home_url( '/dodaj-zlecenie/' ) ); ?>" class="pt24-btn pt24-btn--primary">Zamów wycenę</a>
			</div>
			<div class="pt24-hero__trust">
				<span class="pt24-hero__trust-item">🏢 <strong><?php echo (int) $firms_query->found_posts; ?></strong> firm</span>
				<span class="pt24-hero__trust-item">📋 <strong><?php echo (int) $recent_leads; ?></strong> zapytań (30 dni)</span>
				<span class="pt24-hero__trust-item">⏱ Odpowiedź <strong>w 15 min</strong></span>
			</div>
		</div>
	</section>

	<div class="pb-container pt24-page">

		<?php if ( $firms_query->have_posts() ) : ?>
		<section class="pt24-section">
			<h2>Firmy — <?php echo esc_html( $service_name ); ?></h2>
			<div class="pt24-firms">
				<?php while ( $firms_query->have_posts() ) : $firms_query->the_post();
					$f_id     = get_the_ID();
					$f_rating = (string) get_post_meta( $f_id, 'pt24_firm_rating', true );
					$f_city   = (string) get_post_meta( $f_id, 'pt24_firm_city_name', true );
					$f_jobs   = (int) get_post_meta( $f_id, 'pt24_firm_jobs', true );
				?>
					<div class="pt24-firm">
						<div class="pt24-firm__head">
							<span class="pt24-firm__ico"><span class="pt24-ico pt24-ico--shield" aria-hidden="true"></span></span>
							<h3 class="pt24-firm__name"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						</div>
						<p class="pt24-firm__meta">
							★ <?php echo esc_html( '' !== $f_rating ? $f_rating : '—' ); ?>
							<?php if ( $f_city ) : ?> · <?php echo esc_html( $f_city ); ?><?php endif; ?>
							<?php if ( $f_jobs ) : ?> · <?php echo (int) $f_jobs; ?> zleceń<?php endif; ?>
						</p>
						<a href="<?php the_permalink(); ?>" class="pt24-btn pt24-btn--ghost">Zobacz profil</a>
					</div>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
		</section>
		<?php else : ?>
		<section class="pt24-section">
			<p>Wkrótce dodamy firmy z kategorii „<?php echo esc_html( $service_name ); ?>". <a href="<?php echo esc_url( home_url( '/dodaj-firme/' ) ); ?>">Dodaj swoją firmę</a>.</p>
		</section>
		<?php endif; ?>

		<section class="pt24-section">
			<h2><?php echo esc_html( $service_name ); ?> w miastach</h2>
			<div class="pt24-firms">
				<?php foreach ( array_slice( $cities, 0, 12 ) as $c_slug => $c_name ) : ?>
					<div class="pt24-firm">
						<h3 class="pt24-firm__name"><a href="<?php echo esc_url( home_url( '/' . $c_slug . '/' . $service_slug . '/' ) ); ?>"><?php echo esc_html( $service_name . ' ' . $c_name ); ?></a></h3>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="pt24-cta-band">
			<h2>Potrzebujesz wyceny?</h2>
			<p>Jedno zapytanie — kilka ofert od sprawdzonych firm.</p>
			<p><a class="pt24-btn pt24-btn--primary" href="<?php echo esc_url( home_url( '/dodaj-zlecenie/' ) ); ?>">Dodaj zapytanie</a></p>
		</section>

	</div>
</main>
<?php
endif;

pearblog_render_footer();

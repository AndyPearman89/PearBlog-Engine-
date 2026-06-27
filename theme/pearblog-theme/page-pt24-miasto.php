<?php
/**
 * PT24.PRO — City landing page (/miasto/{slug}/).
 *
 * Shows firms, services, and leads for a given city using real database data.
 *
 * @package PearBlog
 * @subpackage PT24
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

pearblog_render_header();

$city_slug = PearBlog_PT24_Pro_Routing::get_current_city();
$city_name = PearBlog_PT24_Pro_Routing::get_city_name( $city_slug );

// Services list.
$services = class_exists( 'PearBlog_PT24_Landing_CPT' )
	? PearBlog_PT24_Landing_CPT::get_services()
	: PearBlog_PT24_Pro_Routing::get_all_services();

// Firms in this city.
$firms_query = new WP_Query( [
	'post_type'      => 'pt24_firm',
	'post_status'    => 'publish',
	'meta_key'       => 'pt24_firm_city',
	'meta_value'     => $city_slug,
	'posts_per_page' => 20,
	'orderby'        => 'title',
	'order'          => 'ASC',
] );

// Lead stats for this city from database.
global $wpdb;
$leads_table  = $wpdb->prefix . 'pt24_leads';
$recent_leads = (int) $wpdb->get_var( $wpdb->prepare(
	"SELECT COUNT(*) FROM {$leads_table} WHERE city = %s AND created_at >= %s",
	$city_slug,
	gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) )
) );

// Top services in this city.
$top_services_in_city = $wpdb->get_results( $wpdb->prepare(
	"SELECT service, COUNT(*) as cnt FROM {$leads_table} WHERE city = %s GROUP BY service ORDER BY cnt DESC LIMIT 5",
	$city_slug
) );
?>
<main id="main" class="pb-main pt24-landing" role="main">

	<section class="pt24-hero">
		<div class="pb-container">
			<?php echo function_exists( 'pearblog_get_breadcrumbs' ) ? pearblog_get_breadcrumbs() : ''; ?>
			<span class="pt24-hero__badge">📍 <?php echo esc_html( $city_name ); ?></span>
			<h1 class="pt24-hero__title">Fachowcy w mieście <?php echo esc_html( $city_name ); ?></h1>
			<p class="pt24-hero__lead">Przeglądaj sprawdzone firmy usługowe w <?php echo esc_html( $city_name ); ?> i zamów bezpłatną wycenę.</p>
			<div class="pt24-hero__cta">
				<a href="<?php echo esc_url( home_url( '/dodaj-zlecenie/' ) ); ?>" class="pt24-btn pt24-btn--primary">Dodaj zapytanie</a>
				<a href="<?php echo esc_url( home_url( '/firmy/' ) ); ?>" class="pt24-btn pt24-btn--ghost-light">Wszystkie firmy</a>
			</div>
			<div class="pt24-hero__trust">
				<span class="pt24-hero__trust-item">🏢 <strong><?php echo (int) $firms_query->found_posts; ?></strong> firm</span>
				<span class="pt24-hero__trust-item">📋 <strong><?php echo (int) $recent_leads; ?></strong> zapytań (30 dni)</span>
				<span class="pt24-hero__trust-item">⏱ Odpowiedź <strong>w 15 min</strong></span>
			</div>
		</div>
	</section>

	<div class="pb-container pt24-page">

		<?php if ( ! empty( $top_services_in_city ) ) : ?>
		<section class="pt24-section">
			<h2>Popularne usługi w <?php echo esc_html( $city_name ); ?></h2>
			<div class="pt24-firms">
				<?php foreach ( $top_services_in_city as $ts ) :
					$svc_name = $services[ $ts->service ] ?? ucfirst( str_replace( '-', ' ', $ts->service ) );
				?>
					<div class="pt24-firm">
						<h3 class="pt24-firm__name"><a href="<?php echo esc_url( home_url( '/' . $city_slug . '/' . $ts->service . '/' ) ); ?>"><?php echo esc_html( $svc_name ); ?></a></h3>
						<p class="pt24-firm__meta"><?php echo (int) $ts->cnt; ?> zapytań</p>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endif; ?>

		<section class="pt24-section">
			<h2>Wszystkie usługi — <?php echo esc_html( $city_name ); ?></h2>
			<div class="pt24-firms">
				<?php foreach ( array_slice( $services, 0, 15 ) as $s_slug => $s_name ) : ?>
					<div class="pt24-firm">
						<h3 class="pt24-firm__name"><a href="<?php echo esc_url( home_url( '/' . $city_slug . '/' . $s_slug . '/' ) ); ?>"><?php echo esc_html( $s_name . ' ' . $city_name ); ?></a></h3>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<?php if ( $firms_query->have_posts() ) : ?>
		<section class="pt24-section">
			<h2>Firmy w <?php echo esc_html( $city_name ); ?></h2>
			<div class="pt24-firms">
				<?php while ( $firms_query->have_posts() ) : $firms_query->the_post();
					$f_rating  = (string) get_post_meta( get_the_ID(), 'pt24_firm_rating', true );
					$f_jobs    = (int) get_post_meta( get_the_ID(), 'pt24_firm_jobs', true );
					$f_services_csv = (string) get_post_meta( get_the_ID(), 'pt24_firm_services', true );
					$f_svc_arr = array_filter( array_map( 'trim', explode( ',', $f_services_csv ) ) );
					$f_svc_names = array_map( function( $s ) use ( $services ) { return $services[ $s ] ?? ucfirst( str_replace( '-', ' ', $s ) ); }, array_slice( $f_svc_arr, 0, 3 ) );
				?>
					<div class="pt24-firm">
						<div class="pt24-firm__head">
							<span class="pt24-firm__ico"><span class="pt24-ico pt24-ico--shield" aria-hidden="true"></span></span>
							<h3 class="pt24-firm__name"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						</div>
						<p class="pt24-firm__meta">
							★ <?php echo esc_html( '' !== $f_rating ? $f_rating : '—' ); ?>
							<?php if ( $f_jobs ) : ?> · <?php echo (int) $f_jobs; ?> zleceń<?php endif; ?>
							<?php if ( ! empty( $f_svc_names ) ) : ?> · <?php echo esc_html( implode( ', ', $f_svc_names ) ); ?><?php endif; ?>
						</p>
						<a href="<?php the_permalink(); ?>" class="pt24-btn pt24-btn--ghost">Zobacz profil</a>
					</div>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
		</section>
		<?php else : ?>
		<section class="pt24-section">
			<p>Wkrótce dodamy firmy w <?php echo esc_html( $city_name ); ?>. <a href="<?php echo esc_url( home_url( '/dodaj-firme/' ) ); ?>">Dodaj swoją firmę</a>.</p>
		</section>
		<?php endif; ?>

		<section class="pt24-cta-band">
			<h2>Szukasz fachowca w <?php echo esc_html( $city_name ); ?>?</h2>
			<p>Dodaj zapytanie i porównaj oferty od lokalnych firm.</p>
			<p><a class="pt24-btn pt24-btn--primary" href="<?php echo esc_url( home_url( '/dodaj-zlecenie/' ) ); ?>">Dodaj zapytanie</a></p>
		</section>

	</div>
</main>
<?php
pearblog_render_footer();

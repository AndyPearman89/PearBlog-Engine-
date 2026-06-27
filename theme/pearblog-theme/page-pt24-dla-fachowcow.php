<?php
/**
 * PT24.PRO — "Dla fachowców" landing page (/dla-fachowcow/).
 *
 * Provides a marketing/informational page for professionals wanting to join
 * the PT24 platform.
 *
 * @package PearBlog
 * @subpackage PT24
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

pearblog_render_header();

// Pull real stats from the database.
global $wpdb;
$leads_table = $wpdb->prefix . 'pt24_leads';
$firms_table = $wpdb->prefix . 'posts';
$stats_table = $wpdb->prefix . 'pt24_business_stats';

$total_leads = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$leads_table}" );
$total_firms = (int) $wpdb->get_var( $wpdb->prepare(
	"SELECT COUNT(*) FROM {$firms_table} WHERE post_type = %s AND post_status = %s",
	'pt24_firm',
	'publish'
) );
$total_views = (int) $wpdb->get_var( "SELECT COALESCE(SUM(profile_views), 0) FROM {$stats_table}" );

// Services list from CPT class.
$services = class_exists( 'PearBlog_PT24_Landing_CPT' )
	? PearBlog_PT24_Landing_CPT::get_services()
	: [ 'hydraulik' => 'Hydraulik', 'elektryk' => 'Elektryk', 'mechanik' => 'Mechanik' ];

// Cities list.
$cities = class_exists( 'PearBlog_PT24_Landing_CPT' )
	? PearBlog_PT24_Landing_CPT::get_cities()
	: [ 'warszawa' => 'Warszawa', 'krakow' => 'Kraków', 'katowice' => 'Katowice' ];
?>
<main id="main" class="pb-main pt24-landing" role="main">

	<section class="pt24-hero">
		<div class="pb-container">
			<span class="pt24-hero__badge">Dla fachowców i firm</span>
			<h1 class="pt24-hero__title">Pozyskuj klientów bez wysiłku</h1>
			<p class="pt24-hero__lead">Dołącz do PT24.PRO i odbieraj dopasowane zapytania od klientów z Twojej okolicy. Bez reklam, bez cold-callingu.</p>
			<div class="pt24-hero__cta">
				<a href="<?php echo esc_url( home_url( '/dodaj-firme/' ) ); ?>" class="pt24-btn pt24-btn--primary">Zarejestruj firmę</a>
				<a href="#jak-to-dziala" class="pt24-btn pt24-btn--ghost-light">Jak to działa?</a>
			</div>
			<div class="pt24-hero__trust">
				<span class="pt24-hero__trust-item">📊 <strong><?php echo esc_html( number_format( $total_leads, 0, ',', ' ' ) ); ?></strong> zapytań na platformie</span>
				<span class="pt24-hero__trust-item">🏢 <strong><?php echo esc_html( number_format( $total_firms, 0, ',', ' ' ) ); ?></strong> zarejestrowanych firm</span>
				<span class="pt24-hero__trust-item">👁 <strong><?php echo esc_html( number_format( $total_views, 0, ',', ' ' ) ); ?></strong> wyświetleń profili</span>
			</div>
		</div>
	</section>

	<div class="pb-container pt24-page">

		<section id="jak-to-dziala" class="pt24-section">
			<h2>Jak to działa?</h2>
			<div class="pt24-steps">
				<div class="pt24-step">
					<span class="pt24-step__num">1</span>
					<h3>Rejestracja</h3>
					<p>Dodaj firmę do katalogu — podaj nazwę, miasto, usługi i dane kontaktowe.</p>
				</div>
				<div class="pt24-step">
					<span class="pt24-step__num">2</span>
					<h3>Dopasowanie AI</h3>
					<p>System automatycznie wysyła Ci zapytania dopasowane do Twojej branży i lokalizacji.</p>
				</div>
				<div class="pt24-step">
					<span class="pt24-step__num">3</span>
					<h3>Kontakt z klientem</h3>
					<p>Odpowiadasz klientowi z wyceną. Im szybciej — tym większa szansa na zlecenie.</p>
				</div>
			</div>
		</section>

		<section class="pt24-section">
			<h2>Korzyści dla Twojej firmy</h2>
			<ul class="pt24-tasks">
				<li>✅ Darmowy profil firmy widoczny na platformie</li>
				<li>✅ Dopasowane leady z Twojego miasta i branży</li>
				<li>✅ Panel zarządzania z historią zleceń</li>
				<li>✅ Statystyki wyświetleń, kliknięć i konwersji</li>
				<li>✅ Opinie klientów budujące wiarygodność</li>
				<li>✅ Powiadomienia SMS/email o nowych zapytaniach</li>
			</ul>
		</section>

		<section class="pt24-section">
			<h2>Obsługiwane kategorie usług</h2>
			<div class="pt24-firms">
				<?php foreach ( array_slice( $services, 0, 12 ) as $slug => $name ) : ?>
					<div class="pt24-firm">
						<h3 class="pt24-firm__name"><a href="<?php echo esc_url( home_url( '/uslugi/' . $slug . '/' ) ); ?>"><?php echo esc_html( $name ); ?></a></h3>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="pt24-section">
			<h2>Dostępne miasta</h2>
			<div class="pt24-firms">
				<?php foreach ( array_slice( $cities, 0, 12 ) as $slug => $name ) : ?>
					<div class="pt24-firm">
						<h3 class="pt24-firm__name"><a href="<?php echo esc_url( home_url( '/miasto/' . $slug . '/' ) ); ?>"><?php echo esc_html( $name ); ?></a></h3>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="pt24-cta-band">
			<h2>Gotowy na nowych klientów?</h2>
			<p>Rejestracja jest bezpłatna. Dodaj swoją firmę i zacznij odbierać zapytania już dziś.</p>
			<p><a class="pt24-btn pt24-btn--primary" href="<?php echo esc_url( home_url( '/dodaj-firme/' ) ); ?>">Zarejestruj firmę za darmo</a></p>
		</section>

	</div>
</main>
<?php
pearblog_render_footer();

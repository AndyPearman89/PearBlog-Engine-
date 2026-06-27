<?php
/**
 * PT24.PRO — "Dodaj zlecenie" order form (/dodaj-zlecenie/).
 *
 * Renders the lead submission form for customers wanting to find professionals.
 * Submits via AJAX to the pt24_form_handler endpoint.
 *
 * @package PearBlog
 * @subpackage PT24
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

pearblog_render_header();

// Dynamic services and cities from the system.
$services = class_exists( 'PearBlog_PT24_Landing_CPT' )
	? PearBlog_PT24_Landing_CPT::get_services()
	: [ 'hydraulik' => 'Hydraulik', 'elektryk' => 'Elektryk', 'mechanik' => 'Mechanik', 'dekarz' => 'Dekarz', 'remont' => 'Remont' ];

$cities = class_exists( 'PearBlog_PT24_Landing_CPT' )
	? PearBlog_PT24_Landing_CPT::get_cities()
	: [ 'warszawa' => 'Warszawa', 'krakow' => 'Kraków', 'katowice' => 'Katowice', 'wroclaw' => 'Wrocław' ];
?>
<main id="main" class="pb-main pt24-landing" role="main">

	<section class="pt24-hero">
		<div class="pb-container">
			<span class="pt24-hero__badge">Nowe zlecenie</span>
			<h1 class="pt24-hero__title">Dodaj zapytanie</h1>
			<p class="pt24-hero__lead">Opisz czego potrzebujesz — dopasowane firmy wyślą Ci wycenę. Bezpłatnie, bez zobowiązań.</p>
		</div>
	</section>

	<div class="pb-container pt24-page">

		<section class="pt24-section">
			<form id="pt24-order-form" class="pt24-lead-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="pt24_submit_lead">
				<?php wp_nonce_field( 'pt24_submit_lead', 'pt24_lead_nonce' ); ?>

				<div class="pt24-form-group">
					<label for="pt24-service">Kategoria usługi <span class="required">*</span></label>
					<select id="pt24-service" name="service" required>
						<option value="">— Wybierz usługę —</option>
						<?php foreach ( $services as $slug => $name ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="pt24-form-group">
					<label for="pt24-city">Miasto <span class="required">*</span></label>
					<select id="pt24-city" name="city" required>
						<option value="">— Wybierz miasto —</option>
						<?php foreach ( $cities as $slug => $name ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="pt24-form-group">
					<label for="pt24-message">Opis zlecenia <span class="required">*</span></label>
					<textarea id="pt24-message" name="message" rows="5" required placeholder="Np. Cieknie kran w kuchni, potrzebuję hydraulika na jutro..."></textarea>
				</div>

				<div class="pt24-form-group">
					<label for="pt24-name">Imię <span class="required">*</span></label>
					<input type="text" id="pt24-name" name="name" required minlength="2" placeholder="Jan">
				</div>

				<div class="pt24-form-group">
					<label for="pt24-phone">Telefon <span class="required">*</span></label>
					<input type="tel" id="pt24-phone" name="phone" required placeholder="600 123 456">
				</div>

				<div class="pt24-form-group">
					<label for="pt24-email">Email (opcjonalnie)</label>
					<input type="email" id="pt24-email" name="email" placeholder="jan@example.pl">
				</div>

				<div class="pt24-form-group pt24-form-consent">
					<label><input type="checkbox" name="consent" required> Akceptuję <a href="<?php echo esc_url( home_url( '/regulamin/' ) ); ?>" target="_blank">regulamin</a> i wyrażam zgodę na kontakt w sprawie zapytania.</label>
				</div>

				<button type="submit" class="pt24-btn pt24-btn--primary pt24-btn--full">Wyślij zapytanie</button>
			</form>
		</section>

		<section class="pt24-section">
			<h2>Jak to działa?</h2>
			<div class="pt24-steps">
				<div class="pt24-step">
					<span class="pt24-step__num">1</span>
					<h3>Opisujesz potrzebę</h3>
					<p>Wypełnij formularz powyżej — kategoria, miasto, opis.</p>
				</div>
				<div class="pt24-step">
					<span class="pt24-step__num">2</span>
					<h3>AI dopasowuje firmy</h3>
					<p>System wysyła zapytanie do specjalistów pasujących do Twojego zlecenia.</p>
				</div>
				<div class="pt24-step">
					<span class="pt24-step__num">3</span>
					<h3>Otrzymujesz oferty</h3>
					<p>Firmy kontaktują się z wyceną. Porównujesz i wybierasz.</p>
				</div>
			</div>
		</section>

		<section class="pt24-section">
			<div class="pt24-hero__trust">
				<span class="pt24-hero__trust-item">✅ <strong>Bezpłatne</strong> dla klientów</span>
				<span class="pt24-hero__trust-item">⏱ Odpowiedzi <strong>w minuty</strong></span>
				<span class="pt24-hero__trust-item">🔒 <strong>Bez zobowiązań</strong></span>
				<span class="pt24-hero__trust-item">⭐ <strong>Zweryfikowani</strong> fachowcy</span>
			</div>
		</section>

	</div>
</main>
<?php
pearblog_render_footer();

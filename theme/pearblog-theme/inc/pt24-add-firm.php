<?php
/**
 * PT24.PRO — "Dodaj firmę" sign-up page and AJAX handler.
 *
 * Provides:
 *  - A seeded WordPress page /dodaj-firme/ with an HTML enquiry form.
 *  - An AJAX action `pt24_add_firm` that:
 *      1. Validates and sanitises all inputs.
 *      2. Creates a `pt24_firm` post with status 'pending' (visible only to admins).
 *      3. Stores all fields as post-meta.
 *      4. Sends an admin notification email.
 *      5. Returns a JSON success/error response.
 *
 * Host-guarded — loaded only when home_url contains '/pt24' or host is 'pt24.pro'.
 *
 * @package PearBlog
 * @subpackage PT24
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PearBlog_PT24_Add_Firm {

	public static function init(): void {
		add_action( 'wp_ajax_pt24_add_firm',        array( __CLASS__, 'handle_ajax' ) );
		add_action( 'wp_ajax_nopriv_pt24_add_firm', array( __CLASS__, 'handle_ajax' ) );
		add_filter( 'the_content',                  array( __CLASS__, 'inject_form' ) );
	}

	/**
	 * Replace the <!-- pt24-add-firm-form --> placeholder with the live form (fresh nonce).
	 */
	public static function inject_form( string $content ): string {
		if ( false !== strpos( $content, '<!-- pt24-add-firm-form -->' ) ) {
			$content = str_replace( '<!-- pt24-add-firm-form -->', self::form_html(), $content );
		}
		return $content;
	}

	/**
	 * AJAX handler: validate → create CPT draft → notify admin → respond JSON.
	 */
	public static function handle_ajax(): void {
		check_ajax_referer( 'pt24_add_firm_nonce', 'nonce' );

		$name    = isset( $_POST['firm_name'] )    ? sanitize_text_field( wp_unslash( $_POST['firm_name'] ) )    : '';
		$city    = isset( $_POST['firm_city'] )    ? sanitize_text_field( wp_unslash( $_POST['firm_city'] ) )    : '';
		$service = isset( $_POST['firm_service'] ) ? sanitize_text_field( wp_unslash( $_POST['firm_service'] ) ) : '';
		$phone   = isset( $_POST['firm_phone'] )   ? sanitize_text_field( wp_unslash( $_POST['firm_phone'] ) )   : '';
		$email   = isset( $_POST['firm_email'] )   ? sanitize_email( wp_unslash( $_POST['firm_email'] ) )        : '';
		$about   = isset( $_POST['firm_about'] )   ? sanitize_textarea_field( wp_unslash( $_POST['firm_about'] ) ) : '';
		$website = isset( $_POST['firm_website'] ) ? esc_url_raw( wp_unslash( $_POST['firm_website'] ) )         : '';
		$consent = ! empty( $_POST['firm_consent'] );

		// --- validation ---
		$errors = array();
		if ( mb_strlen( $name ) < 2 )   { $errors[] = 'Podaj nazwę firmy.'; }
		if ( mb_strlen( $city ) < 2 )   { $errors[] = 'Podaj miasto działania.'; }
		if ( mb_strlen( $phone ) < 7 )  { $errors[] = 'Podaj numer telefonu.'; }
		if ( ! is_email( $email ) )     { $errors[] = 'Podaj poprawny adres e-mail.'; }
		if ( ! $consent )               { $errors[] = 'Wymagana zgoda na przetwarzanie danych.'; }

		if ( ! empty( $errors ) ) {
			wp_send_json_error( array( 'message' => implode( ' ', $errors ) ) );
		}

		// --- create pending CPT post ---
		$slug    = sanitize_title( $name . '-' . $city );
		$post_id = (int) wp_insert_post( array(
			'post_title'   => $name,
			'post_name'    => $slug,
			'post_content' => wp_kses_post( $about ),
			'post_status'  => 'pending',  // hidden from public; admin reviews before publishing
			'post_type'    => 'pt24_firm',
		) );

		if ( $post_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'Wystąpił błąd techniczny. Spróbuj ponownie.' ) );
		}

		// --- store meta ---
		update_post_meta( $post_id, 'pt24_firm_city',    $city );
		update_post_meta( $post_id, 'pt24_firm_service', $service );
		update_post_meta( $post_id, 'pt24_firm_phone',   $phone );
		update_post_meta( $post_id, 'pt24_firm_email',   $email );
		update_post_meta( $post_id, 'pt24_firm_website', $website );
		update_post_meta( $post_id, 'pt24_firm_rating',  '5.0' );
		update_post_meta( $post_id, 'pt24_firm_jobs',    '0' );
		update_post_meta( $post_id, 'pt24_firm_source',  'form' );
		update_post_meta( $post_id, 'pt24_firm_year',    (string) gmdate( 'Y' ) );

		// --- admin email notification ---
		$notify_email = (string) get_option( 'pt24_notify_email', (string) get_option( 'admin_email' ) );
		if ( is_email( $notify_email ) ) {
			$admin_url = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
			$subject   = sprintf( '[PT24.PRO] Nowa firma do zatwierdzenia: %s', $name );
			$body      = sprintf(
				"Nowe zgłoszenie firmy czeka na weryfikację.\n\n" .
				"Nazwa:     %s\n" .
				"Miasto:    %s\n" .
				"Usługa:    %s\n" .
				"Telefon:   %s\n" .
				"E-mail:    %s\n" .
				"Strona:    %s\n\n" .
				"O firmie:\n%s\n\n" .
				"Zatwierdź lub odrzuć:\n%s",
				$name, $city, $service, $phone, $email, $website, $about, $admin_url
			);
			wp_mail( $notify_email, $subject, $body );
		}

		wp_send_json_success( array(
			'message' => 'Dziękujemy! Twoje zgłoszenie zostało przyjęte. Skontaktujemy się w ciągu 24 godzin.',
		) );
	}

	/**
	 * Return the HTML form markup (embedded in the /dodaj-firme/ page content).
	 */
	public static function form_html(): string {
		$nonce = wp_create_nonce( 'pt24_add_firm_nonce' );
		$services = array(
			''                 => '-- Wybierz główną usługę --',
			'hydraulik'        => 'Hydraulik',
			'elektryk'         => 'Elektryk',
			'mechanik'         => 'Mechanik samochodowy',
			'fotowoltaika'     => 'Fotowoltaika',
			'pompa-ciepla'     => 'Pompa ciepła',
			'remont-lazienki'  => 'Remont łazienki',
			'inne'             => 'Inna usługa',
		);

		$options = '';
		foreach ( $services as $val => $label ) {
			$options .= '<option value="' . esc_attr( $val ) . '">' . esc_html( $label ) . '</option>';
		}

		ob_start();
		?>
<div class="pt24-add-firm">
<div class="pt24-add-firm__hero">
<div class="pb-container">
<h1>Dołącz do katalogu PT24.PRO</h1>
<p>Bezpłatna rejestracja — zasięgnij nowych klientów w swoim mieście.</p>
</div>
</div>

<div class="pb-container">
<div class="pt24-add-firm__why pt24-features">
<div class="pt24-features__item">
<span class="pt24-feat-ico"><span class="pt24-ico pt24-ico--star"></span></span>
<h3>Bezpłatny profil</h3>
<p>Podstawowy profil firmy w katalogu PT24 nie kosztuje nic. Opcjonalnie możesz wyróżnić się w rankingach.</p>
</div>
<div class="pt24-features__item">
<span class="pt24-feat-ico"><span class="pt24-ico pt24-ico--tag"></span></span>
<h3>Nowi klienci</h3>
<p>Setki osób miesięcznie szuka fachowców przez PT24. Twój profil trafi bezpośrednio do gotowych klientów.</p>
</div>
<div class="pt24-features__item">
<span class="pt24-feat-ico"><span class="pt24-ico pt24-ico--shield"></span></span>
<h3>Pełna kontrola</h3>
<p>Sam zarządzasz opisem, zdjęciami i zakresem usług. Odpowiadasz na zapytania w swoim tempie.</p>
</div>
</div>

<div class="pt24-add-firm__formwrap">
<h2>Wyślij zgłoszenie</h2>
<p style="color:var(--pt24-text-muted);margin-bottom:24px;">Po weryfikacji opublikujemy Twój profil — zwykle w ciągu 24 godzin.</p>

<form id="pt24AddFirmForm" class="pt24-add-firm__form" novalidate>
<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">
<input type="hidden" name="action" value="pt24_add_firm">

<div class="pt24-add-firm__row">
<label for="pt24_firm_name">Nazwa firmy *</label>
<input id="pt24_firm_name" name="firm_name" type="text" required placeholder="np. Hydraulika Nowak" maxlength="120">
</div>

<div class="pt24-add-firm__row pt24-add-firm__row--2col">
<div>
<label for="pt24_firm_city">Miasto działania *</label>
<input id="pt24_firm_city" name="firm_city" type="text" required placeholder="np. Warszawa" maxlength="80">
</div>
<div>
<label for="pt24_firm_service">Główna usługa</label>
<select id="pt24_firm_service" name="firm_service"><?php echo $options; ?></select>
</div>
</div>

<div class="pt24-add-firm__row pt24-add-firm__row--2col">
<div>
<label for="pt24_firm_phone">Telefon kontaktowy *</label>
<input id="pt24_firm_phone" name="firm_phone" type="tel" required placeholder="+48 600 000 000" maxlength="24">
</div>
<div>
<label for="pt24_firm_email">E-mail *</label>
<input id="pt24_firm_email" name="firm_email" type="email" required placeholder="firma@example.com" maxlength="120">
</div>
</div>

<div class="pt24-add-firm__row">
<label for="pt24_firm_website">Strona WWW (opcjonalnie)</label>
<input id="pt24_firm_website" name="firm_website" type="url" placeholder="https://twoja-firma.pl" maxlength="200">
</div>

<div class="pt24-add-firm__row">
<label for="pt24_firm_about">Kilka słów o firmie</label>
<textarea id="pt24_firm_about" name="firm_about" rows="4" placeholder="Zakres usług, doświadczenie, obszar działania..." maxlength="1200"></textarea>
</div>

<div class="pt24-add-firm__row">
<label class="pt24-add-firm__consent">
<input type="checkbox" name="firm_consent" value="1" required>
<span>Wyrażam zgodę na przetwarzanie powyższych danych przez PT24.PRO w celu weryfikacji i publikacji profilu. <a href="/polityka-prywatnosci/" target="_blank">Polityka prywatności</a>.</span>
</label>
</div>

<div id="pt24AddFirmMsg" class="pt24-add-firm__msg" style="display:none;"></div>

<button type="submit" class="pt24-btn pt24-btn--primary" id="pt24AddFirmSubmit">
Wyślij zgłoszenie →
</button>
</form>
</div>
</div>
</div>

<script>
(function(){
var form=document.getElementById('pt24AddFirmForm');
var msg=document.getElementById('pt24AddFirmMsg');
var btn=document.getElementById('pt24AddFirmSubmit');
if(!form)return;
form.addEventListener('submit',function(e){
e.preventDefault();
btn.disabled=true; btn.textContent='Wysyłanie…';
msg.style.display='none'; msg.className='pt24-add-firm__msg';
var data=new FormData(form);
fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,{method:'POST',body:data})
.then(function(r){return r.json();})
.then(function(r){
if(r.success){
form.style.display='none';
msg.className='pt24-add-firm__msg pt24-add-firm__msg--ok';
msg.textContent=r.data.message;
msg.style.display='block';
}else{
msg.className='pt24-add-firm__msg pt24-add-firm__msg--err';
msg.textContent=r.data&&r.data.message?r.data.message:'Wystąpił błąd. Spróbuj ponownie.';
msg.style.display='block';
btn.disabled=false; btn.textContent='Wyślij zgłoszenie →';
}
}).catch(function(){
msg.className='pt24-add-firm__msg pt24-add-firm__msg--err';
msg.textContent='Problem z połączeniem. Spróbuj ponownie.';
msg.style.display='block';
btn.disabled=false; btn.textContent='Wyślij zgłoszenie →';
});
});
})();
</script>
		<?php
		return (string) ob_get_clean();
	}
}

<?php
/**
 * PT24.PRO — ranking template (/ranking/{miasto}/{usluga}/).
 *
 * Ranks the city's company profiles for a given service (by rating, then jobs)
 * and drives the same lead form as the standard landing.
 *
 * @package PearBlog
 * @subpackage PT24
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id      = (int) get_the_ID();
$service_slug = (string) get_post_meta( $post_id, 'pt24_service', true );
$city_slug    = (string) get_post_meta( $post_id, 'pt24_city', true );
$service_name = (string) get_post_meta( $post_id, 'pt24_service_display', true );
$city_name    = (string) get_post_meta( $post_id, 'pt24_city_display', true );

if ( '' === $service_slug ) {
	$service_slug = (string) get_query_var( 'pt24_service' );
}
if ( '' === $city_slug ) {
	$city_slug = (string) get_query_var( 'pt24_city' );
}
if ( class_exists( 'PearBlog_PT24_Landing_CPT' ) ) {
	$smap = PearBlog_PT24_Landing_CPT::get_services();
	$cmap = PearBlog_PT24_Landing_CPT::get_cities();
	if ( isset( $smap[ $service_slug ] ) ) {
		$service_name = $smap[ $service_slug ];
	}
	if ( isset( $cmap[ $city_slug ] ) ) {
		$city_name = $cmap[ $city_slug ];
	}
}
if ( '' === $service_name ) {
	$service_name = ucfirst( str_replace( '-', ' ', $service_slug ) );
}
if ( '' === $city_name ) {
	$city_name = ucfirst( $city_slug );
}

$year     = gmdate( 'Y' );
$ajax_url = admin_url( 'admin-ajax.php' );

// Rank the city's firms by rating (desc), then jobs (desc).
$firms = get_posts( array(
	'post_type'        => 'pt24_firm',
	'post_status'      => 'publish',
	'numberposts'      => 10,
	'meta_key'         => 'pt24_firm_city',
	'meta_value'       => $city_slug,
	'suppress_filters' => true,
) );
usort( $firms, function ( $a, $b ) {
	$ra = (float) str_replace( ',', '.', (string) get_post_meta( $a->ID, 'pt24_firm_rating', true ) );
	$rb = (float) str_replace( ',', '.', (string) get_post_meta( $b->ID, 'pt24_firm_rating', true ) );
	if ( $ra === $rb ) {
		return (int) get_post_meta( $b->ID, 'pt24_firm_jobs', true ) <=> (int) get_post_meta( $a->ID, 'pt24_firm_jobs', true );
	}
	return $rb <=> $ra;
} );

pearblog_render_header();
?>
<main id="main" class="pb-main pt24-landing" role="main">

	<section class="pt24-hero">
		<div class="pb-container">
			<?php echo function_exists( 'pearblog_get_breadcrumbs' ) ? pearblog_get_breadcrumbs() : ''; ?>
			<span class="pt24-hero__badge">Ranking <?php echo esc_html( $year ); ?></span>
			<h1 class="pt24-hero__title"><?php echo esc_html( $service_name . ' ' . $city_name ); ?> — ranking najlepszych firm <?php echo esc_html( $year ); ?></h1>
			<p class="pt24-hero__lead">Porównaj najwyżej oceniane firmy: <?php echo esc_html( $service_name . ' ' . $city_name ); ?>. Zamów bezpłatną, niezobowiązującą wycenę.</p>
			<div class="pt24-hero__cta">
				<a href="#pt24-lead" class="pt24-btn pt24-btn--primary">Otrzymaj bezpłatne wyceny</a>
				<a href="#pt24-ranking" class="pt24-btn pt24-btn--ghost-light">Zobacz ranking</a>
			</div>
			<div class="pt24-hero__trust">
				<span class="pt24-hero__trust-item">✅ <strong>Zweryfikowani</strong> fachowcy</span>
				<span class="pt24-hero__trust-item">⭐ <strong>4.8/5</strong> średnia ocena</span>
				<span class="pt24-hero__trust-item">⏱ Odpowiedź <strong>w 2&nbsp;h</strong></span>
				<span class="pt24-hero__trust-item">🔒 <strong>Bezpłatna</strong> wycena</span>
				<span class="pt24-hero__trust-item">📍 Lokalni z <strong><?php echo esc_html( $city_name ); ?></strong></span>
			</div>
		</div>
	</section>

	<div class="pb-container pt24-grid">
		<article class="pt24-content">

			<p class="pt24-intro">Zebraliśmy najwyżej oceniane firmy świadczące usługi (<?php echo esc_html( mb_strtolower( $service_name ) ); ?>) w mieście <?php echo esc_html( $city_name ); ?>. Ranking powstał na podstawie ocen klientów oraz liczby zrealizowanych zleceń.</p>

			<section class="pt24-section" id="pt24-ranking">
				<h2>Ranking: <?php echo esc_html( $service_name . ' ' . $city_name ); ?></h2>
				<?php if ( empty( $firms ) ) : ?>
					<p>Wkrótce dodamy ranking firm w tym mieście. Zostaw zgłoszenie, a dopasujemy oferty.</p>
				<?php else : ?>
					<ol class="pt24-ranking">
						<?php foreach ( $firms as $idx => $firm ) :
							$rating = (string) get_post_meta( $firm->ID, 'pt24_firm_rating', true );
							$jobs   = (int) get_post_meta( $firm->ID, 'pt24_firm_jobs', true );
							?>
							<li class="pt24-ranking__item">
								<span class="pt24-ranking__rank"><?php echo (int) ( $idx + 1 ); ?></span>
								<div class="pt24-ranking__body">
									<h3 class="pt24-ranking__name"><a href="<?php echo esc_url( get_permalink( $firm ) ); ?>"><?php echo esc_html( get_the_title( $firm ) ); ?></a></h3>
									<p class="pt24-ranking__meta">★ <?php echo esc_html( '' !== $rating ? $rating : '4,8' ); ?> · <?php echo (int) $jobs; ?> zrealizowanych zleceń</p>
								</div>
								<div class="pt24-ranking__actions">
									<a href="<?php echo esc_url( get_permalink( $firm ) ); ?>" class="pt24-btn pt24-btn--ghost">Profil</a>
									<a href="#pt24-lead" class="pt24-btn pt24-btn--primary">Wyceń</a>
								</div>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>
			</section>

			<section class="pt24-section">
				<h2>Jak powstał ranking?</h2>
				<p>Pod uwagę bierzemy oceny klientów, liczbę zrealizowanych zleceń oraz terminowość i jakość obsługi. Ranking aktualizujemy na bieżąco, aby pokazywał aktualnie najlepsze firmy w mieście <?php echo esc_html( $city_name ); ?>.</p>
				<p><a href="<?php echo esc_url( home_url( '/' . $city_slug . '/' . $service_slug . '/' ) ); ?>">Zobacz pełną stronę usługi: <?php echo esc_html( $service_name . ' ' . $city_name ); ?> →</a></p>
			</section>

			<?php
			// Related blog posts — match by service meta, fallback to AI posts, then latest.
			$pt24_related = get_posts( array(
				'post_type'        => 'post',
				'post_status'      => 'publish',
				'numberposts'      => 3,
				'meta_query'       => array( array( 'key' => 'pt24_blog_service', 'value' => $service_slug, 'compare' => '=' ) ),
				'suppress_filters' => true,
			) );
			if ( empty( $pt24_related ) ) {
				$pt24_related = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 3, 'meta_key' => '_pt24_blog_ai', 'meta_value' => '1', 'suppress_filters' => true ) );
			}
			if ( count( $pt24_related ) < 3 ) {
				$pt24_fill    = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 3 - count( $pt24_related ), 'post__not_in' => array_merge( wp_list_pluck( $pt24_related, 'ID' ), array( 0 ) ), 'suppress_filters' => true ) );
				$pt24_related = array_merge( $pt24_related, $pt24_fill );
			}
			$pt24_related = array_slice( $pt24_related, 0, 3 );
			if ( ! empty( $pt24_related ) ) : ?>
			<section class="pt24-section pt24-fromblog">
				<h2>Z bloga PT24</h2>
				<div class="pt24-fromblog__grid">
					<?php foreach ( $pt24_related as $pt24_rp ) :
						$pt24_rcats = get_the_category( $pt24_rp->ID );
						?>
						<a class="pt24-fromblog__card" href="<?php echo esc_url( get_permalink( $pt24_rp ) ); ?>">
							<?php if ( ! empty( $pt24_rcats ) ) : ?><span class="pt24-fromblog__cat"><?php echo esc_html( $pt24_rcats[0]->name ); ?></span><?php endif; ?>
							<span class="pt24-fromblog__title"><?php echo esc_html( get_the_title( $pt24_rp ) ); ?></span>
							<span class="pt24-fromblog__excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $pt24_rp->post_content ), 16 ) ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
			<?php endif; ?>

		</article>

		<aside class="pt24-sidebar">
			<div id="pt24-lead" class="pt24-leadbox">
				<h2 class="pt24-leadbox__title">Zamów bezpłatną wycenę</h2>
				<p class="pt24-leadbox__sub"><?php echo esc_html( $service_name ); ?> · <?php echo esc_html( $city_name ); ?></p>
				<form class="pt24-leadform" method="post" action="<?php echo esc_url( $ajax_url ); ?>">
					<input type="hidden" name="action" value="pt24_submit_lead">
					<input type="hidden" name="service" value="<?php echo esc_attr( $service_slug ); ?>">
					<input type="hidden" name="city" value="<?php echo esc_attr( $city_slug ); ?>">
					<input type="hidden" name="source_url" value="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
					<?php wp_nonce_field( 'pt24_nonce', 'nonce' ); ?>
					<label>Imię i nazwisko
						<input type="text" name="name" required autocomplete="name">
					</label>
					<label>Telefon
						<input type="tel" name="phone" required autocomplete="tel">
					</label>
					<label>E-mail
						<input type="email" name="email" autocomplete="email">
					</label>
					<label>Opis zlecenia
						<textarea name="description" rows="4" placeholder="Np. Wymiana baterii w łazience, potrzebuję termin do piątku…"></textarea>
					</label>
					<button type="submit" class="pt24-btn pt24-btn--primary pt24-btn--block">Wyślij zapytanie</button>
					<p class="pt24-leadform__note">Wysyłając formularz akceptujesz <a href="/regulamin/">regulamin</a> i <a href="/polityka-prywatnosci/">politykę prywatności</a>.</p>
					<p class="pt24-leadform__result" hidden></p>
				</form>
			</div>
		</aside>
	</div>

	<section class="pt24-section pt24-internal">
		<div class="pb-container">
			<h2>Ten ranking w innych miastach</h2>
			<ul class="pt24-links">
				<?php
				if ( class_exists( 'PearBlog_PT24_Landing_CPT' ) ) {
					foreach ( PearBlog_PT24_Landing_CPT::get_cities() as $cslug => $cname ) {
						if ( $cslug === $city_slug ) {
							continue;
						}
						printf(
							'<li><a href="%s">%s %s</a></li>',
							esc_url( home_url( "/ranking/{$cslug}/{$service_slug}/" ) ),
							esc_html( $service_name ),
							esc_html( $cname )
						);
					}
				}
				?>
			</ul>
		</div>
	</section>

</main>
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
			.then(function(json){
				if(result){
					result.hidden = false;
					result.textContent = (json && json.data && json.data.message) ? json.data.message : 'Dziękujemy! Skontaktujemy się wkrótce.';
					result.style.color = (json && json.success) ? '#16a34a' : '#dc2626';
				}
				if(json && json.success){ form.reset(); btn.textContent = 'Wysłano ✓'; }
				else { btn.disabled = false; btn.textContent = 'Wyślij zapytanie'; }
			})
			.catch(function(){
				if(result){ result.hidden=false; result.style.color='#dc2626'; result.textContent='Błąd połączenia. Spróbuj ponownie.'; }
				btn.disabled = false; btn.textContent = 'Wyślij zapytanie';
			});
	});
})();
</script>
<?php
pearblog_render_footer();

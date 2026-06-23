<?php
/**
 * PT24.PRO — Blog article template (post type: post, meta _pt24_blog_ai = 1)
 *
 * Lejek ruchu:  Blog → Poradnik → Strona usługi → Profil firmy → Telefon → Lead
 *
 * Dane z meta:
 *   pt24_blog_service, pt24_blog_city, pt24_blog_type, pt24_blog_topic,
 *   pt24_meta_title, pt24_meta_description,
 *   pearblog_faq (JSON), pt24_related_services (JSON), pt24_related_cities (JSON)
 *
 * @package PearBlog\PT24
 * @subpackage Blog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id     = (int) get_the_ID();
$service     = (string) get_post_meta( $post_id, 'pt24_blog_service', true );
$city        = (string) get_post_meta( $post_id, 'pt24_blog_city',    true );
$art_type    = (string) get_post_meta( $post_id, 'pt24_blog_type',    true );
$meta_title  = (string) get_post_meta( $post_id, 'pt24_meta_title',   true );
$meta_desc   = (string) get_post_meta( $post_id, 'pt24_meta_description', true );

$faq_json    = (string) get_post_meta( $post_id, 'pearblog_faq',              true );
$svcs_json   = (string) get_post_meta( $post_id, 'pt24_related_services',     true );
$cities_json = (string) get_post_meta( $post_id, 'pt24_related_cities',       true );

$faq_items      = $faq_json    ? (array) json_decode( $faq_json,    true ) : [];
$related_svcs   = $svcs_json   ? (array) json_decode( $svcs_json,   true ) : [];
$related_cities = $cities_json ? (array) json_decode( $cities_json, true ) : [];

// Display names
$svc_map   = class_exists( 'PT24_Scale_Data' ) ? PT24_Scale_Data::services() : [];
$city_map  = class_exists( 'PT24_Scale_Data' ) ? PT24_Scale_Data::cities()   : [];

$svc_name  = $svc_map[ $service ]  ?? ucfirst( str_replace( '-', ' ', $service ) );
$city_name = $city_map[ $city ]    ?? ucfirst( str_replace( '-', ' ', $city ) );

// CTA url → service landing page
$cta_url = home_url( '/' . ( $city ? $city . '/' : '' ) . $service . '/#pt24-lead' );

// Category badge
$cats = get_the_category();
$cat_badge = ! empty( $cats ) ? $cats[0]->name : '';

pearblog_render_header();
?>
<main id="main" class="pb-main pt24-landing" role="main">

	<!-- HERO ─────────────────────────────────────────────────── -->
	<section class="pt24-hero">
		<div class="pb-container">
			<?php echo function_exists( 'pearblog_get_breadcrumbs' ) ? pearblog_get_breadcrumbs() : ''; ?>
			<?php if ( $cat_badge ) : ?>
				<span class="pt24-hero__badge"><?php echo esc_html( $cat_badge ); ?></span>
			<?php endif; ?>
			<h1 class="pt24-hero__title"><?php the_title(); ?></h1>
			<p class="pt24-hero__lead">
				<?php echo esc_html( $meta_desc ?: get_the_excerpt() ); ?>
			</p>
			<div class="pt24-hero__cta">
				<a href="<?php echo esc_url( $cta_url ); ?>" class="pt24-btn pt24-btn--primary">
					Zamów bezpłatną wycenę
				</a>
				<a href="<?php echo esc_url( home_url( '/szukaj/' ) ); ?>" class="pt24-btn pt24-btn--ghost-light">
					Znajdź fachowca
				</a>
			</div>
			<div class="pt24-hero__trust">
				<span class="pt24-hero__trust-item">✅ <strong>Sprawdzeni</strong> specjaliści</span>
				<span class="pt24-hero__trust-item">🔒 <strong>Bezpłatna</strong> wycena</span>
				<span class="pt24-hero__trust-item">⏱ Odpowiedź <strong>w 2&nbsp;h</strong></span>
				<?php if ( $city_name ) : ?>
					<span class="pt24-hero__trust-item">📍 Lokalni z <strong><?php echo esc_html( $city_name ); ?></strong></span>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<!-- CONTENT ──────────────────────────────────────────────── -->
	<div class="pb-container pt24-grid">
		<article class="pt24-content pt24-blog-article">

			<!-- Post meta bar -->
			<div class="pt24-blog-meta">
				<span><?php echo esc_html( get_the_date( 'd.m.Y' ) ); ?></span>
				<?php if ( $svc_name ) : ?>
					<span class="pt24-blog-meta__sep">·</span>
					<a href="<?php echo esc_url( home_url( '/' . ( $city ?: '' ) . ( $city ? '/' : '' ) . $service . '/' ) ); ?>">
						<?php echo esc_html( $svc_name ); ?><?php echo $city_name ? ' ' . esc_html( $city_name ) : ''; ?>
					</a>
				<?php endif; ?>
				<span class="pt24-blog-meta__sep">·</span>
				<span><?php echo (int) ( get_the_content() ? str_word_count( wp_strip_all_tags( get_the_content() ) ) : 0 ); ?> słów</span>
			</div>

			<!-- Article body -->
			<div class="pt24-blog-body entry-content">
				<?php the_content(); ?>
			</div>

			<!-- FAQ section ─────────────────────────────────── -->
			<?php if ( ! empty( $faq_items ) ) : ?>
			<section class="pt24-section pt24-faq">
				<h2>Najczęstsze pytania</h2>
				<?php foreach ( $faq_items as $qa ) : ?>
					<details class="pt24-faq__item">
						<summary><?php echo esc_html( $qa['q'] ?? '' ); ?></summary>
						<p><?php echo esc_html( $qa['a'] ?? '' ); ?></p>
					</details>
				<?php endforeach; ?>
			</section>
			<?php
			// FAQPage schema
			$faq_schema = [
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'mainEntity' => [],
			];
			foreach ( $faq_items as $qa ) {
				$faq_schema['mainEntity'][] = [
					'@type'          => 'Question',
					'name'           => $qa['q'] ?? '',
					'acceptedAnswer' => [ '@type' => 'Answer', 'text' => $qa['a'] ?? '' ],
				];
			}
			?>
			<script type="application/ld+json"><?php echo wp_json_encode( $faq_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
			<?php endif; ?>

			<!-- CTA band ────────────────────────────────────── -->
			<section class="pt24-cta-band">
				<strong>
					Potrzebujesz <?php echo esc_html( $svc_name ); ?>
					<?php echo $city_name ? 'w ' . esc_html( $city_name ) . '?' : '?'; ?>
				</strong>
				<p>Opisz zlecenie raz — otrzymaj do 3 bezpłatnych ofert od lokalnych specjalistów. Bez zobowiązań.</p>
				<a href="<?php echo esc_url( $cta_url ); ?>" class="pt24-btn pt24-btn--primary">
					📞 Zamów bezpłatną wycenę
				</a>
				<a href="tel:+48" class="pt24-btn pt24-btn--ghost" style="margin-left:.7rem">
					📍 Zadzwoń teraz
				</a>
			</section>

			<!-- Related services ────────────────────────────── -->
			<?php if ( ! empty( $related_svcs ) ) : ?>
			<section class="pt24-section">
				<h2>Powiązane usługi</h2>
				<div class="pt24-firms">
					<?php foreach ( $related_svcs as $rs ) :
						$rs_name = $svc_map[ $rs ] ?? ucfirst( str_replace( '-', ' ', $rs ) );
						$rs_url  = home_url( '/' . ( $city ? $city . '/' : '' ) . $rs . '/' );
					?>
						<div class="pt24-firm">
							<h3 class="pt24-firm__name"><a href="<?php echo esc_url( $rs_url ); ?>"><?php echo esc_html( $rs_name ); ?><?php echo $city_name ? ' ' . esc_html( $city_name ) : ''; ?></a></h3>
							<a href="<?php echo esc_url( $rs_url . '#pt24-lead' ); ?>" class="pt24-btn pt24-btn--ghost">Zamów wycenę →</a>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
			<?php endif; ?>

			<!-- Related cities ──────────────────────────────── -->
			<?php if ( ! empty( $related_cities ) ) : ?>
			<section class="pt24-section">
				<h2><?php echo esc_html( $svc_name ); ?> w innych miastach</h2>
				<ul class="pt24-links">
					<?php foreach ( $related_cities as $rc ) :
						$rc_name = $city_map[ $rc ] ?? ucfirst( str_replace( '-', ' ', $rc ) );
					?>
						<li><a href="<?php echo esc_url( home_url( '/' . $rc . '/' . $service . '/' ) ); ?>">
							<?php echo esc_html( $svc_name . ' ' . $rc_name ); ?>
						</a></li>
					<?php endforeach; ?>
				</ul>
			</section>
			<?php endif; ?>

		</article><!-- .pt24-content -->

		<!-- SIDEBAR ──────────────────────────────────────────── -->
		<aside class="pt24-sidebar">
			<div id="pt24-lead" class="pt24-leadbox">
				<div class="pt24-leadbox__header">
					Zamów bezpłatną wycenę<br>
					<small style="font-weight:400;opacity:.85"><?php echo esc_html( $svc_name . ( $city_name ? ' · ' . $city_name : '' ) ); ?></small>
				</div>
				<form class="pt24-leadform" method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
					<input type="hidden" name="action"     value="pt24_submit_lead">
					<input type="hidden" name="service"    value="<?php echo esc_attr( $service ); ?>">
					<input type="hidden" name="city"       value="<?php echo esc_attr( $city ); ?>">
					<input type="hidden" name="source_url" value="<?php echo esc_url( get_permalink() ); ?>">
					<?php wp_nonce_field( 'pt24_nonce', 'nonce' ); ?>
					<label>Imię i nazwisko
						<input type="text"  name="name"  required autocomplete="name">
					</label>
					<label>Telefon
						<input type="tel"   name="phone" required autocomplete="tel">
					</label>
					<label>E-mail
						<input type="email" name="email" autocomplete="email">
					</label>
					<label>Opis zlecenia
						<textarea name="description" rows="4" placeholder="Opisz problem lub zakres prac…"></textarea>
					</label>
					<button type="submit" class="pt24-btn pt24-btn--primary pt24-btn--block">Wyślij zapytanie</button>
					<p class="pt24-leadform__note">
						Akceptujesz <a href="/regulamin/">regulamin</a> i <a href="/polityka-prywatnosci/">politykę prywatności</a>.
					</p>
					<p class="pt24-leadform__result" hidden></p>
				</form>
			</div>

			<!-- Mini ranking ─────────────────────────────────── -->
			<?php
			if ( $service && $city ) :
				$top_firms = get_posts( [
					'post_type'        => 'pt24_firm',
					'post_status'      => 'publish',
					'numberposts'      => 3,
					'meta_key'         => 'pt24_firm_city',
					'meta_value'       => $city,
					'orderby'          => 'title',
					'order'            => 'ASC',
					'suppress_filters' => true,
				] );
			?>
			<?php if ( ! empty( $top_firms ) ) : ?>
			<div class="pt24-leadbox" style="margin-top:1.2rem;padding:0">
				<div class="pt24-leadbox__header">
					Polecani <?php echo esc_html( $svc_name ); ?> w <?php echo esc_html( $city_name ); ?>
				</div>
				<div style="padding:1rem">
				<?php foreach ( $top_firms as $tf ) :
					$tf_rating = (string) get_post_meta( $tf->ID, 'pt24_firm_rating', true );
					$tf_jobs   = (int)   get_post_meta( $tf->ID, 'pt24_firm_jobs',   true );
				?>
					<div style="margin-bottom:.8rem;padding-bottom:.8rem;border-bottom:1px solid var(--pt24-border)">
						<strong><a href="<?php echo esc_url( get_permalink( $tf ) ); ?>"><?php echo esc_html( get_the_title( $tf ) ); ?></a></strong><br>
						<?php if ( $tf_rating ) : ?>
							<span class="pt24-firm__stars">★</span> <?php echo esc_html( $tf_rating ); ?>
							<?php if ( $tf_jobs ) echo '· ' . (int) $tf_jobs . ' zleceń'; ?>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
				<a href="<?php echo esc_url( home_url( '/ranking/' . $city . '/' . $service . '/' ) ); ?>" class="pt24-btn pt24-btn--ghost pt24-btn--block">Pełny ranking →</a>
				</div>
			</div>
			<?php endif; ?>
			<?php endif; ?>

		</aside>
	</div><!-- .pt24-grid -->

</main>

<script>
(function(){
	var form = document.querySelector('.pt24-leadform');
	if(!form) return;
	form.addEventListener('submit', function(e){
		e.preventDefault();
		var result = form.querySelector('.pt24-leadform__result');
		var btn    = form.querySelector('button[type=submit]');
		btn.disabled = true; btn.textContent = 'Wysyłanie…';
		fetch(form.action, { method:'POST', body:new FormData(form), credentials:'same-origin' })
			.then(function(r){ return r.json(); })
			.then(function(json){
				if(result){
					result.hidden = false;
					result.textContent = (json&&json.data&&json.data.message) ? json.data.message : 'Dziękujemy! Odezwiemy się wkrótce.';
					result.style.color = (json&&json.success) ? '#16a34a' : '#dc2626';
				}
				if(json&&json.success){ form.reset(); btn.textContent = 'Wysłano ✓'; }
				else { btn.disabled=false; btn.textContent='Wyślij zapytanie'; }
			})
			.catch(function(){
				if(result){ result.hidden=false; result.style.color='#dc2626'; result.textContent='Błąd połączenia. Spróbuj ponownie.'; }
				btn.disabled=false; btn.textContent='Wyślij zapytanie';
			});
	});
})();
</script>

<?php
// Article schema (Article + BreadcrumbList)
$article_schema = [
	'@context'      => 'https://schema.org',
	'@type'         => 'Article',
	'headline'      => get_the_title(),
	'description'   => $meta_desc,
	'datePublished' => get_the_date( 'c' ),
	'dateModified'  => get_the_modified_date( 'c' ),
	'author'        => [ '@type' => 'Organization', 'name' => 'PT24.PRO', 'url' => home_url() ],
	'publisher'     => [
		'@type' => 'Organization',
		'name'  => 'PT24.PRO',
		'url'   => home_url(),
		'logo'  => [ '@type' => 'ImageObject', 'url' => home_url( '/wp-content/themes/pearblog-theme/assets/brand/logo.png' ) ],
	],
	'url' => get_permalink(),
];
echo '<script type="application/ld+json">' . wp_json_encode( $article_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>';

pearblog_render_footer();

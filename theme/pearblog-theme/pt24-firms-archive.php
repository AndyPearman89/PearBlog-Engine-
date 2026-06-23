<?php
/**
 * PT24.PRO — company catalogue (/firmy/).
 *
 * Lists all published company profiles grouped by city. Routed via the firm CPT
 * request filter (pt24_firms_index). Host-guarded (PT24 install only).
 *
 * @package PearBlog
 * @subpackage PT24
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

pearblog_render_header();

$pt24_cities = class_exists( 'PearBlog_PT24_Landing_CPT' )
	? PearBlog_PT24_Landing_CPT::get_cities()
	: array( 'warszawa' => 'Warszawa', 'krakow' => 'Kraków', 'wroclaw' => 'Wrocław', 'poznan' => 'Poznań', 'gdansk' => 'Gdańsk', 'katowice' => 'Katowice' );
?>
<main id="main" class="pb-main pt24-landing" role="main">

	<section class="pt24-hero">
		<div class="pb-container">
			<span class="pt24-hero__badge">Katalog firm</span>
			<h1 class="pt24-hero__title">Sprawdzone firmy i fachowcy</h1>
			<p class="pt24-hero__lead">Przeglądaj zweryfikowane firmy usługowe w największych miastach w Polsce i zamów bezpłatną wycenę.</p>
			<div class="pt24-hero__cta">
				<a href="<?php echo esc_url( home_url( '/szukaj/' ) ); ?>" class="pt24-btn pt24-btn--primary">Znajdź fachowca</a>
			</div>
			<div class="pt24-hero__trust">
				<span class="pt24-hero__trust-item">✅ <strong>Zweryfikowani</strong> fachowcy</span>
				<span class="pt24-hero__trust-item">⭐ <strong>4.8/5</strong> średnia ocena</span>
				<span class="pt24-hero__trust-item">⏱ Odpowiedź <strong>w 2&nbsp;h</strong></span>
				<span class="pt24-hero__trust-item">🔒 <strong>Bezpłatna</strong> wycena</span>
			</div>
		</div>
	</section>

	<div class="pb-container pt24-page">
		<?php
		$pt24_any = false;
		foreach ( $pt24_cities as $pt24_cslug => $pt24_cname ) :
			$pt24_firms = get_posts( array(
				'post_type'        => 'pt24_firm',
				'post_status'      => 'publish',
				'numberposts'      => 20,
				'meta_key'         => 'pt24_firm_city',
				'meta_value'       => $pt24_cslug,
				'orderby'          => 'title',
				'order'            => 'ASC',
				'suppress_filters' => true,
			) );
			if ( empty( $pt24_firms ) ) {
				continue;
			}
			$pt24_any = true;
			?>
			<section class="pt24-section">
				<h2><?php echo esc_html( $pt24_cname ); ?></h2>
				<div class="pt24-firms">
					<?php
					foreach ( $pt24_firms as $pt24_fp ) :
						$pt24_rating = (string) get_post_meta( $pt24_fp->ID, 'pt24_firm_rating', true );
						$pt24_jobs   = (int) get_post_meta( $pt24_fp->ID, 'pt24_firm_jobs', true );
						?>
						<div class="pt24-firm">
							<div class="pt24-firm__head">
								<span class="pt24-firm__ico"><span class="pt24-ico pt24-ico--shield" aria-hidden="true"></span></span>
								<h3 class="pt24-firm__name"><a href="<?php echo esc_url( get_permalink( $pt24_fp ) ); ?>"><?php echo esc_html( get_the_title( $pt24_fp ) ); ?></a></h3>
							</div>
							<p class="pt24-firm__meta">★ <?php echo esc_html( '' !== $pt24_rating ? $pt24_rating : '4,8' ); ?> · <?php echo (int) $pt24_jobs; ?> zrealizowanych zleceń</p>
							<a href="<?php echo esc_url( get_permalink( $pt24_fp ) ); ?>" class="pt24-btn pt24-btn--ghost">Zobacz profil</a>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>

		<?php if ( ! $pt24_any ) : ?>
			<section class="pt24-section"><p>Wkrótce dodamy profile firm w Twoim mieście.</p></section>
		<?php endif; ?>

		<section class="pt24-cta-band">
			<h2>Jesteś fachowcem?</h2>
			<p>Dodaj swoją firmę do katalogu PT24 i odbieraj zapytania od klientów z Twojej okolicy.</p>
			<p><a class="pt24-btn pt24-btn--primary" href="<?php echo esc_url( home_url( '/dla-firm/' ) ); ?>">Dołącz do PT24</a></p>
		</section>
	</div>

</main>
<?php
pearblog_render_footer();

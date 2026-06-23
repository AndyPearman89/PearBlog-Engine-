<?php
/**
 * PT24.PRO — blog archive (posts index) template.
 *
 * Used for the /blog/ posts page on the PT24 install only, routed via a
 * host-guarded template_include filter (inc/pt24-blog.php). Replaces the shared
 * poradnik.pro V3 index.php layout with a clean, on-brand post grid.
 *
 * @package PearBlog
 * @subpackage PT24
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

pearblog_render_header();

$pt24_blog_page_id = (int) get_option( 'page_for_posts' );
$pt24_blog_title   = $pt24_blog_page_id ? get_the_title( $pt24_blog_page_id ) : __( 'Blog', 'pearblog-theme' );

// Run an explicit WP_Query so the template is independent of $wp_query state
// (needed when routed via the slug-based fallback in pt24-blog.php).
$pt24_paged      = max( 1, (int) ( get_query_var( 'paged' ) ?: ( get_query_var( 'page' ) ?: 1 ) ) );
$pt24_blog_query = new WP_Query( array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => (int) get_option( 'posts_per_page', 10 ),
	'paged'               => $pt24_paged,
	'ignore_sticky_posts' => true,
) );
?>
<main id="main" class="pb-main pt24-landing" role="main">

	<section class="pt24-hero">
		<div class="pb-container">
			<span class="pt24-hero__badge">Poradnik PT24</span>
			<h1 class="pt24-hero__title"><?php echo esc_html( $pt24_blog_title ); ?></h1>
			<p class="pt24-hero__lead">Porady, koszty i poradniki — jak wybrać sprawdzonego fachowca i ile zapłacić za usługi w Twoim mieście.</p>
			<div class="pt24-hero__cta">
				<a href="<?php echo esc_url( home_url( '/szukaj/' ) ); ?>" class="pt24-btn pt24-btn--primary">Zamów bezpłatną wycenę</a>
				<a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>" class="pt24-btn pt24-btn--ghost-light">Wszystkie poradniki</a>
			</div>
			<div class="pt24-hero__trust">
				<span class="pt24-hero__trust-item">✅ <strong>Rzetelne</strong> porady eksperckie</span>
				<span class="pt24-hero__trust-item">💰 <strong>Orientacyjne</strong> ceny usług</span>
				<span class="pt24-hero__trust-item">📍 Lokalni <strong>sprawdzeni</strong> fachowcy</span>
				<span class="pt24-hero__trust-item">🔒 <strong>Bezpłatna</strong> wycena</span>
			</div>
		</div>
	</section>

	<div class="pb-container pt24-blog">

		<!-- Category filter bar -->
		<?php
		$pt24_blog_cats = [
			'poradniki'      => 'Poradniki',
			'awarie'         => 'Awarie',
			'koszty'         => 'Koszty',
			'jak-zrobic'     => 'Jak zrobić',
			'rankingi'       => 'Rankingi',
			'pt24-24h'       => '24h',
			'bezpieczenstwo' => 'Bezpieczeństwo',
			'sezonowe'       => 'Sezonowe',
			'problemy'       => 'Problemy',
			'lokalne'        => 'Lokalne',
		];
		$pt24_active_cat = isset( $_GET['cat'] ) ? sanitize_key( wp_unslash( $_GET['cat'] ) ) : '';
		?>
		<div class="pt24-blog-cats" style="display:flex;flex-wrap:wrap;gap:.45rem;margin-bottom:1.8rem;">
			<a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>"
			   class="pt24-blog-cat-btn<?php echo '' === $pt24_active_cat ? ' is-active' : ''; ?>">
				Wszystkie
			</a>
			<?php foreach ( $pt24_blog_cats as $slug => $label ) :
				$term = get_term_by( 'slug', $slug, 'category' );
				if ( ! $term ) continue;
			?>
			<a href="<?php echo esc_url( get_term_link( $term ) ); ?>"
			   class="pt24-blog-cat-btn">
				<?php echo esc_html( $label ); ?>
				<span class="pt24-blog-cat-count"><?php echo (int) $term->count; ?></span>
			</a>
			<?php endforeach; ?>
		</div>

		<?php if ( $pt24_blog_query->have_posts() ) : ?>
			<div class="pt24-blog-grid">
				<?php
				while ( $pt24_blog_query->have_posts() ) :
					$pt24_blog_query->the_post();
					$pt24_cats = get_the_category();
					?>
					<article <?php post_class( 'pt24-blog-card' ); ?>>
					<?php
					$pt24_thumb_slug = (string) get_post_meta( get_the_ID(), '_pt24_thumb_slug', true );
					$pt24_thumb_file = $pt24_thumb_slug
						? get_template_directory() . '/assets/brand/blog/' . sanitize_file_name( $pt24_thumb_slug ) . '.png'
						: '';
					$pt24_thumb_uri  = ( $pt24_thumb_slug && file_exists( $pt24_thumb_file ) )
						? get_template_directory_uri() . '/assets/brand/blog/' . sanitize_file_name( $pt24_thumb_slug ) . '.png'
						: '';
					?>
					<?php if ( has_post_thumbnail() ) : ?>
						<a class="pt24-blog-card__thumb" href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium_large' ); ?></a>
					<?php elseif ( $pt24_thumb_uri ) : ?>
						<a class="pt24-blog-card__thumb" href="<?php the_permalink(); ?>"><img src="<?php echo esc_url( $pt24_thumb_uri ); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" width="1200" height="628"></a>
						<?php else : ?>
							<a class="pt24-blog-card__thumb pt24-blog-card__thumb--ph" href="<?php the_permalink(); ?>"><span><?php echo esc_html( ! empty( $pt24_cats ) ? $pt24_cats[0]->name : 'PT24' ); ?></span></a>
						<?php endif; ?>
						<div class="pt24-blog-card__body">
							<?php if ( ! empty( $pt24_cats ) ) : ?>
								<span class="pt24-blog-card__cat"><?php echo esc_html( $pt24_cats[0]->name ); ?></span>
							<?php endif; ?>
							<h2 class="pt24-blog-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<p class="pt24-blog-card__meta"><?php echo esc_html( get_the_date() ); ?></p>
							<p class="pt24-blog-card__excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 26 ) ); ?></p>
							<a class="pt24-blog-card__more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Czytaj dalej', 'pearblog-theme' ); ?> →</a>
						</div>
					</article>
				<?php endwhile; ?>
			</div>

			<div class="pt24-blog-pagination">
				<?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => '←', 'next_text' => '→', 'screen_reader_text' => '' ) ); ?>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'Brak wpisów.', 'pearblog-theme' ); ?></p>
		<?php endif; wp_reset_postdata(); ?>
	</div>

</main>
<?php
pearblog_render_footer();

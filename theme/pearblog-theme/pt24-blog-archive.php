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
?>
<main id="main" class="pb-main pt24-landing" role="main">

	<section class="pt24-hero">
		<div class="pb-container">
			<span class="pt24-hero__badge">Blog PT24</span>
			<h1 class="pt24-hero__title"><?php echo esc_html( $pt24_blog_title ); ?></h1>
			<p class="pt24-hero__lead">Porady, koszty i poradniki — jak wybrać sprawdzonego fachowca i ile zapłacić za usługi.</p>
		</div>
	</section>

	<div class="pb-container pt24-blog">
		<?php if ( have_posts() ) : ?>
			<div class="pt24-blog-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					$pt24_cats = get_the_category();
					?>
					<article <?php post_class( 'pt24-blog-card' ); ?>>
						<?php if ( has_post_thumbnail() ) : ?>
							<a class="pt24-blog-card__thumb" href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium_large' ); ?></a>
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
				<?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => '←', 'next_text' => '→' ) ); ?>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'Brak wpisów.', 'pearblog-theme' ); ?></p>
		<?php endif; ?>
	</div>

</main>
<?php
pearblog_render_footer();

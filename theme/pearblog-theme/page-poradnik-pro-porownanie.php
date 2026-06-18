<?php
/**
 * Template Name: Poradnik.PRO - Porównanie
 * Description: Single comparison page (A vs B decision view with specs, pricing, verdict).
 *
 * @package PearBlog
 * @subpackage PoradnikPro
 * @version 6.0.0
 */

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/inc/poradnik-pro-shared.php';
require_once get_template_directory() . '/inc/poradnik-pro-seed-data.php';

$pp_slug       = get_query_var( 'poradnik_slug', '' );
$pp_comparison = pp_seed_get_comparison( $pp_slug );
$pp_comparisons = pp_seed_comparisons();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
	<?php pp_pro_shared_styles(); ?>
	<style>
		.comparison-hero { padding: 48px 0 40px; background: linear-gradient(135deg, #dbeafe 0%, #ede9fe 100%); text-align: center; }
		.comparison-hero .vs-badge { display: inline-block; padding: 6px 16px; border-radius: 50px; font-size: 12px; font-weight: 700; background: #e0e7ff; color: var(--purple-primary); margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.05em; }
		.comparison-hero h1 { font-size: 32px; font-weight: 800; color: var(--gray-900); margin-bottom: 12px; }
		.comparison-hero .hero-desc { font-size: 16px; color: var(--gray-600); max-width: 600px; margin: 0 auto; }

		.comparison-content { padding: 48px 0 64px; }
		.comparison-body { max-width: 900px; margin: 0 auto; }

		.specs-table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 40px; }
		.specs-table thead { background: var(--gray-50); }
		.specs-table th { padding: 14px 20px; font-size: 13px; font-weight: 600; text-align: center; color: var(--gray-700); }
		.specs-table th:first-child { text-align: left; }
		.specs-table td { padding: 14px 20px; font-size: 14px; color: var(--gray-700); text-align: center; border-top: 1px solid var(--gray-100); }
		.specs-table td:first-child { text-align: left; font-weight: 600; color: var(--gray-800); }
		.specs-table tr:hover { background: var(--gray-50); }
		.col-a { color: var(--purple-primary); font-weight: 600; }
		.col-b { color: #d97706; font-weight: 600; }

		.verdict-box { background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); border: 2px solid #86efac; border-radius: var(--radius-xl); padding: 40px; text-align: center; margin-bottom: 48px; }
		.verdict-icon { font-size: 48px; margin-bottom: 16px; }
		.verdict-title { font-size: 20px; font-weight: 800; color: var(--gray-900); margin-bottom: 12px; }
		.verdict-text { font-size: 16px; color: var(--gray-700); max-width: 600px; margin: 0 auto; line-height: 1.6; }

		.cta-box { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; margin-bottom: 48px; }
		.cta-box a { display: inline-flex; align-items: center; gap: 8px; padding: 14px 28px; border-radius: 50px; font-size: 14px; font-weight: 700; transition: all 0.2s; }
		.cta-primary { background: var(--purple-primary); color: #fff; }
		.cta-primary:hover { background: var(--purple-dark); }
		.cta-secondary { border: 2px solid var(--purple-primary); color: var(--purple-primary); }
		.cta-secondary:hover { background: #f3e8ff; }

		.related-comparisons { border-top: 1px solid var(--gray-200); padding-top: 40px; }
		.related-comparisons h2 { font-size: 18px; font-weight: 700; color: var(--gray-900); margin-bottom: 16px; }
		.related-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
		.related-comp-card { display: block; padding: 16px 20px; background: var(--gray-50); border-radius: var(--radius-md); transition: all 0.2s; }
		.related-comp-card:hover { background: #ede9fe; }
		.related-comp-title { font-size: 14px; font-weight: 600; color: var(--gray-800); }
		.related-comp-cat { font-size: 12px; color: var(--gray-500); margin-top: 4px; }

		@media (max-width: 768px) {
			.related-grid { grid-template-columns: 1fr; }
			.cta-box { flex-direction: column; align-items: center; }
		}
	</style>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php pp_pro_header( 'porownania' ); ?>

<!-- COMPARISON HERO -->
<section class="comparison-hero">
	<div class="container">
		<div class="breadcrumb" style="justify-content: center;">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Strona główna</a>
			<span class="sep">/</span>
			<a href="<?php echo esc_url( home_url( '/porownania/' ) ); ?>">Porównania</a>
			<span class="sep">/</span>
			<span><?php echo esc_html( $pp_comparison['category'] ); ?></span>
		</div>
		<span class="vs-badge">&#x1F19A; Porównanie</span>
		<h1><?php echo esc_html( $pp_comparison['option_a'] ); ?> vs <?php echo esc_html( $pp_comparison['option_b'] ); ?></h1>
		<p class="hero-desc">Jasne różnice, realne koszty, konkretny werdykt — podejmij najlepszą decyzję.</p>
	</div>
</section>

<!-- COMPARISON CONTENT -->
<section class="comparison-content">
	<div class="container">
		<div class="comparison-body">
			<!-- SPECS TABLE -->
			<table class="specs-table">
				<thead>
					<tr>
						<th>Kryterium</th>
						<th class="col-a"><?php echo esc_html( $pp_comparison['option_a'] ); ?></th>
						<th class="col-b"><?php echo esc_html( $pp_comparison['option_b'] ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $pp_comparison['specs'] as $spec ) : ?>
					<tr>
						<td><?php echo esc_html( $spec['label'] ); ?></td>
						<td><?php echo esc_html( $spec['a'] ); ?></td>
						<td><?php echo esc_html( $spec['b'] ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<!-- VERDICT -->
			<div class="verdict-box">
				<div class="verdict-icon">&#127942;</div>
				<h2 class="verdict-title">Werdykt</h2>
				<p class="verdict-text"><?php echo esc_html( $pp_comparison['verdict'] ); ?></p>
			</div>

			<!-- CTA -->
			<div class="cta-box">
				<a href="<?php echo esc_url( home_url( '/specjalisci/' ) ); ?>" class="cta-primary">&#128104;&#8205;&#128188; Znajdź specjalistę</a>
				<a href="<?php echo esc_url( home_url( '/kalkulatory/' ) ); ?>" class="cta-secondary">&#129518; Sprawdź koszty</a>
			</div>

			<!-- RELATED COMPARISONS -->
			<div class="related-comparisons">
				<h2>Inne porównania</h2>
				<div class="related-grid">
					<?php
					$pp_related_c = 0;
					foreach ( $pp_comparisons as $rc ) :
						if ( $rc['slug'] === $pp_comparison['slug'] ) {
							continue;
						}
						if ( $pp_related_c >= 4 ) {
							break;
						}
						++$pp_related_c;
					?>
					<a href="<?php echo esc_url( home_url( '/porownanie/' . $rc['slug'] . '/' ) ); ?>" class="related-comp-card">
						<div class="related-comp-title"><?php echo esc_html( $rc['option_a'] ); ?> vs <?php echo esc_html( $rc['option_b'] ); ?></div>
						<div class="related-comp-cat"><?php echo esc_html( $rc['category'] ); ?></div>
					</a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
</section>

<?php pp_pro_footer(); ?>
<?php wp_footer(); ?>
</body>
</html>

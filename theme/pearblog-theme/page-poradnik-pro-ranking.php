<?php
/**
 * Template Name: Poradnik.PRO - Ranking
 * Description: Single ranking page (/ranking/{slug})
 *
 * @package PearBlog
 * @subpackage PoradnikPro
 * @version 6.0.0
 */

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/inc/poradnik-pro-shared.php';
require_once get_template_directory() . '/inc/poradnik-pro-seed-data.php';

$pp_slug    = get_query_var( 'poradnik_slug', '' );
$pp_ranking = pp_seed_get_ranking( $pp_slug );
$pp_rankings = pp_seed_rankings();
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
		.ranking-hero { padding: 40px 0 32px; background: linear-gradient(135deg, #fef3c7 0%, #ffedd5 100%); }
		.ranking-content { padding: 48px 0 64px; max-width: 900px; margin: 0 auto; }
		.ranking-description { font-size: 15px; color: var(--gray-600); margin-top: 12px; line-height: 1.6; }
		.ranking-updated { font-size: 13px; color: var(--gray-500); margin-top: 8px; }

		.ranking-table { width: 100%; border-collapse: collapse; margin-top: 32px; background: #fff; border-radius: var(--radius-lg); overflow: hidden; border: 1px solid var(--gray-200); }
		.ranking-table thead { background: var(--gray-50); border-bottom: 1px solid var(--gray-200); }
		.ranking-table th { padding: 14px 20px; font-size: 13px; font-weight: 600; color: var(--gray-700); text-align: left; }
		.ranking-table td { padding: 16px 20px; font-size: 14px; color: var(--gray-800); border-top: 1px solid var(--gray-100); }
		.ranking-table tr:hover { background: var(--gray-50); }
		.rank-pos { font-weight: 800; color: var(--purple-primary); font-size: 16px; }
		.rank-pos.gold { color: #d97706; }
		.rank-pos.silver { color: #64748b; }
		.rank-pos.bronze { color: #ea580c; }
		.rank-name { font-weight: 700; color: var(--gray-900); }
		.rank-score { font-weight: 700; color: var(--green-accent); }
		.rank-pros { font-size: 12px; color: var(--gray-500); margin-top: 4px; }
		.rank-btn { display: inline-block; padding: 8px 18px; border-radius: 50px; font-size: 12px; font-weight: 600; background: var(--purple-primary); color: #fff; transition: opacity 0.2s; }
		.rank-btn:hover { opacity: 0.85; }

		.ranking-faq { margin-top: 48px; }
		.faq-title { font-size: 18px; font-weight: 700; color: var(--gray-900); margin-bottom: 16px; }
		.faq-item { border: 1px solid var(--gray-200); border-radius: var(--radius-md); padding: 16px 20px; margin-bottom: 10px; }
		.faq-item summary { cursor: pointer; font-size: 14px; font-weight: 600; color: var(--gray-800); }
		.faq-item p { margin-top: 10px; font-size: 14px; color: var(--gray-600); line-height: 1.6; }

		.related-rankings { margin-top: 48px; }
		.related-rankings h2 { font-size: 18px; font-weight: 700; color: var(--gray-900); margin-bottom: 16px; }
		.related-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
		.related-card { display: block; padding: 16px 20px; border: 1px solid var(--gray-200); border-radius: var(--radius-md); font-size: 14px; font-weight: 500; color: var(--gray-800); transition: all 0.2s; }
		.related-card:hover { border-color: var(--purple-primary); color: var(--purple-primary); }

		@media (max-width: 768px) {
			.ranking-table th:nth-child(4), .ranking-table td:nth-child(4) { display: none; }
			.related-grid { grid-template-columns: 1fr; }
		}
	</style>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php pp_pro_header( 'rankingi' ); ?>

<!-- RANKING HERO -->
<section class="ranking-hero">
	<div class="container">
		<div class="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Strona główna</a>
			<span class="sep">/</span>
			<a href="<?php echo esc_url( home_url( '/rankingi/' ) ); ?>">Rankingi</a>
			<span class="sep">/</span>
			<span><?php echo esc_html( $pp_ranking['title'] ); ?></span>
		</div>
		<h1><?php echo esc_html( $pp_ranking['title'] ); ?></h1>
		<p class="ranking-description"><?php echo esc_html( $pp_ranking['description'] ); ?></p>
		<p class="ranking-updated">Aktualizacja: <?php echo esc_html( $pp_ranking['updated'] ); ?> &middot; Oparte na danych i opiniach użytkowników</p>
	</div>
</section>

<!-- RANKING CONTENT -->
<section class="ranking-content">
	<div class="container">
		<!-- RANKING TABLE -->
		<table class="ranking-table">
			<thead>
				<tr>
					<th>#</th>
					<th>Nazwa</th>
					<th>Ocena</th>
					<th>Koszty</th>
					<th>Akcja</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $pp_ranking['items'] as $i => $item ) :
					$pos_class = $i === 0 ? 'gold' : ( $i === 1 ? 'silver' : ( $i === 2 ? 'bronze' : '' ) );
				?>
				<tr>
					<td><span class="rank-pos <?php echo esc_attr( $pos_class ); ?>"><?php echo esc_html( $i + 1 ); ?></span></td>
					<td>
						<div class="rank-name"><?php echo esc_html( $item['name'] ); ?></div>
						<div class="rank-pros"><?php echo esc_html( $item['pros'] ); ?></div>
					</td>
					<td><span class="rank-score"><?php echo esc_html( $item['score'] ); ?></span></td>
					<td><?php echo esc_html( $item['cost'] ); ?></td>
					<td><a href="<?php echo esc_url( $item['url'] ); ?>" class="rank-btn" target="_blank" rel="noopener noreferrer">Sprawdź</a></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<!-- FAQ -->
		<div class="ranking-faq">
			<h2 class="faq-title">Najczęściej zadawane pytania</h2>
			<details class="faq-item">
				<summary>Jak tworzymy ranking?</summary>
				<p>Ranking opiera się na analizie opłat, jakości usług, opinii użytkowników i dostępności. Nie przyjmujemy opłat za pozycję — kolejność wynika wyłącznie z naszej oceny.</p>
			</details>
			<details class="faq-item">
				<summary>Jak często aktualizujemy dane?</summary>
				<p>Ranking jest aktualizowany co miesiąc lub częściej, gdy dostawcy wprowadzają istotne zmiany w ofercie.</p>
			</details>
			<details class="faq-item">
				<summary>Czy linki partnerskie wpływają na ranking?</summary>
				<p>Nie. Niektóre linki mogą być partnerskie, ale kolejność w rankingu jest całkowicie niezależna od tego, czy mamy współpracę z danym podmiotem.</p>
			</details>
		</div>

		<!-- RELATED RANKINGS -->
		<div class="related-rankings">
			<h2>Inne rankingi</h2>
			<div class="related-grid">
				<?php
				$pp_related_r = 0;
				foreach ( $pp_rankings as $rr ) :
					if ( $rr['slug'] === $pp_ranking['slug'] ) {
						continue;
					}
					if ( $pp_related_r >= 4 ) {
						break;
					}
					++$pp_related_r;
				?>
				<a href="<?php echo esc_url( home_url( '/ranking/' . $rr['slug'] . '/' ) ); ?>" class="related-card"><?php echo esc_html( $rr['title'] ); ?></a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>

<?php pp_pro_footer(); ?>
<?php wp_footer(); ?>
</body>
</html>

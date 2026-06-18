<?php
/**
 * Template Name: Poradnik.PRO - Rankingi
 * Description: Rankings listing page (/rankingi)
 *
 * @package PearBlog
 * @subpackage PoradnikPro
 */

defined( 'ABSPATH' ) || exit;
require_once get_template_directory() . '/inc/poradnik-pro-shared.php';
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
		/* ===== PAGE HERO (warm amber gradient) ===== */
		.page-hero {
			background: linear-gradient(135deg, #fef3c7 0%, #ffedd5 100%);
			padding: 48px 0;
		}

		/* ===== CATEGORY RANKINGS GRID ===== */
		.rankings-section { padding: 48px 0; }
		.category-rankings {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 20px;
			margin-bottom: 40px;
		}
		.category-ranking-card {
			background: #fff;
			border: 1px solid var(--gray-200);
			border-radius: var(--radius-md);
			padding: 24px;
			transition: box-shadow 0.2s, transform 0.2s;
			display: block;
		}
		.category-ranking-card:hover {
			box-shadow: var(--shadow-md);
			transform: translateY(-2px);
		}
		.category-ranking-icon { font-size: 32px; margin-bottom: 12px; }
		.category-ranking-title { font-size: 15px; font-weight: 700; color: var(--gray-900); margin-bottom: 6px; }
		.category-ranking-count { font-size: 12px; color: var(--gray-500); margin-bottom: 12px; }
		.category-ranking-top {
			display: flex;
			flex-direction: column;
			gap: 8px;
		}
		.top-item {
			display: flex;
			align-items: center;
			justify-content: space-between;
			font-size: 13px;
		}
		.top-item-name { color: var(--gray-700); font-weight: 500; }
		.top-item-score { color: var(--green-accent); font-weight: 700; }

		/* ===== RANKING LIST ===== */
		.rankings-list { display: flex; flex-direction: column; gap: 20px; }
		.rankings-list-heading {
			font-size: 20px;
			font-weight: 700;
			color: var(--gray-900);
			margin-bottom: 20px;
		}

		.ranking-item {
			background: #fff;
			border: 1px solid var(--gray-200);
			border-radius: var(--radius-lg);
			padding: 24px;
			display: grid;
			grid-template-columns: 60px 1fr auto;
			gap: 20px;
			align-items: center;
			transition: box-shadow 0.2s;
		}
		.ranking-item:hover { box-shadow: var(--shadow-md); }
		.ranking-item.gold { border-color: #fbbf24; border-width: 2px; }
		.ranking-item.silver { border-color: #94a3b8; border-width: 2px; }
		.ranking-item.bronze { border-color: #d97706; border-width: 2px; }

		.ranking-position {
			width: 48px;
			height: 48px;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			font-weight: 800;
			font-size: 18px;
		}
		.ranking-position.gold { background: #fef3c7; color: #d97706; }
		.ranking-position.silver { background: #f1f5f9; color: #64748b; }
		.ranking-position.bronze { background: #ffedd5; color: #ea580c; }
		.ranking-position.default { background: var(--gray-100); color: var(--gray-600); }

		.ranking-info h3 { font-size: 16px; font-weight: 700; color: var(--gray-900); margin-bottom: 4px; }
		.ranking-meta { display: flex; align-items: center; gap: 16px; font-size: 13px; color: var(--gray-500); }
		.ranking-stars { color: var(--yellow-accent); }
		.ranking-reviews { color: var(--gray-400); }
		.ranking-location { display: flex; align-items: center; gap: 4px; }

		.ranking-score { text-align: center; }
		.ranking-score-value {
			font-size: 24px;
			font-weight: 800;
			color: var(--green-accent);
		}
		.ranking-score-label {
			font-size: 11px;
			color: var(--gray-400);
		}

		/* ===== RESPONSIVE ===== */
		@media (max-width: 768px) {
			.category-rankings { grid-template-columns: 1fr; }
			.ranking-item { grid-template-columns: 48px 1fr; }
			.ranking-score { display: none; }
		}
	</style>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php pp_pro_header( 'rankingi' ); ?>

<!-- PAGE HERO -->
<section class="page-hero">
	<div class="container">
		<div class="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Strona glowna</a>
			<span class="sep">/</span>
			<span>Rankingi</span>
		</div>
		<h1>Rankingi</h1>
		<p>Nie szukasz &mdash; wybierasz sprawdzonych. Opinie, oceny, ranking jakosci &mdash; minimalizujesz ryzyko.</p>
	</div>
</section>

<!-- CATEGORY RANKINGS -->
<section class="rankings-section">
	<div class="container">
		<div class="category-rankings">
			<a href="<?php echo esc_url( home_url( '/ranking/firmy-remontowe/' ) ); ?>" class="category-ranking-card">
				<div class="category-ranking-icon">&#127959;</div>
				<h3 class="category-ranking-title">Firmy remontowe</h3>
				<p class="category-ranking-count">127 firm w rankingu</p>
				<div class="category-ranking-top">
					<div class="top-item"><span class="top-item-name">1. RemontPro Katowice</span><span class="top-item-score">9.7</span></div>
					<div class="top-item"><span class="top-item-name">2. Budmax Warszawa</span><span class="top-item-score">9.5</span></div>
					<div class="top-item"><span class="top-item-name">3. Eko-Dom Krakow</span><span class="top-item-score">9.3</span></div>
				</div>
			</a>

			<a href="<?php echo esc_url( home_url( '/ranking/prawnicy-rozwodowi/' ) ); ?>" class="category-ranking-card">
				<div class="category-ranking-icon">&#9878;</div>
				<h3 class="category-ranking-title">Prawnicy rozwodowi</h3>
				<p class="category-ranking-count">84 kancelarie</p>
				<div class="category-ranking-top">
					<div class="top-item"><span class="top-item-name">1. Kancelaria Nowak</span><span class="top-item-score">9.8</span></div>
					<div class="top-item"><span class="top-item-name">2. Adw. Kowalski</span><span class="top-item-score">9.4</span></div>
					<div class="top-item"><span class="top-item-name">3. Prawo&amp;Partner</span><span class="top-item-score">9.2</span></div>
				</div>
			</a>

			<a href="<?php echo esc_url( home_url( '/ranking/instalatorzy-pomp-ciepla/' ) ); ?>" class="category-ranking-card">
				<div class="category-ranking-icon">&#127777;</div>
				<h3 class="category-ranking-title">Instalatorzy pomp ciepla</h3>
				<p class="category-ranking-count">93 firmy</p>
				<div class="category-ranking-top">
					<div class="top-item"><span class="top-item-name">1. EcoHeat Slask</span><span class="top-item-score">9.6</span></div>
					<div class="top-item"><span class="top-item-name">2. TermoInstal</span><span class="top-item-score">9.4</span></div>
					<div class="top-item"><span class="top-item-name">3. GreenPump</span><span class="top-item-score">9.1</span></div>
				</div>
			</a>

			<a href="<?php echo esc_url( home_url( '/ranking/doradcy-kredytowi/' ) ); ?>" class="category-ranking-card">
				<div class="category-ranking-icon">&#128179;</div>
				<h3 class="category-ranking-title">Doradcy kredytowi</h3>
				<p class="category-ranking-count">156 specjalistow</p>
				<div class="category-ranking-top">
					<div class="top-item"><span class="top-item-name">1. FinExpert24</span><span class="top-item-score">9.8</span></div>
					<div class="top-item"><span class="top-item-name">2. KredytOK</span><span class="top-item-score">9.5</span></div>
					<div class="top-item"><span class="top-item-name">3. Doradca Wisniewski</span><span class="top-item-score">9.3</span></div>
				</div>
			</a>

			<a href="<?php echo esc_url( home_url( '/ranking/stomatolodzy/' ) ); ?>" class="category-ranking-card">
				<div class="category-ranking-icon">&#129463;</div>
				<h3 class="category-ranking-title">Stomatolodzy</h3>
				<p class="category-ranking-count">211 gabinetow</p>
				<div class="category-ranking-top">
					<div class="top-item"><span class="top-item-name">1. DentPro Wroclaw</span><span class="top-item-score">9.9</span></div>
					<div class="top-item"><span class="top-item-name">2. SmileLab Warszawa</span><span class="top-item-score">9.6</span></div>
					<div class="top-item"><span class="top-item-name">3. Klinika Zdrowy Usmiech</span><span class="top-item-score">9.4</span></div>
				</div>
			</a>

			<a href="<?php echo esc_url( home_url( '/ranking/architekci-wnetrz/' ) ); ?>" class="category-ranking-card">
				<div class="category-ranking-icon">&#127912;</div>
				<h3 class="category-ranking-title">Architekci wnetrz</h3>
				<p class="category-ranking-count">78 projektantow</p>
				<div class="category-ranking-top">
					<div class="top-item"><span class="top-item-name">1. Studio Forma</span><span class="top-item-score">9.7</span></div>
					<div class="top-item"><span class="top-item-name">2. DesignLab Gdansk</span><span class="top-item-score">9.4</span></div>
					<div class="top-item"><span class="top-item-name">3. Wnetrza z Klasa</span><span class="top-item-score">9.2</span></div>
				</div>
			</a>
		</div>

		<!-- TOP RANKING LIST -->
		<h2 class="rankings-list-heading">Najlepsze firmy remontowe &mdash; Katowice</h2>
		<div class="rankings-list">
			<div class="ranking-item gold">
				<div class="ranking-position gold">1</div>
				<div class="ranking-info">
					<h3>RemontPro Katowice</h3>
					<div class="ranking-meta">
						<span class="ranking-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
						<span class="ranking-reviews">142 opinie</span>
						<span class="ranking-location">&#128205; Katowice</span>
					</div>
				</div>
				<div class="ranking-score">
					<div class="ranking-score-value">9.7</div>
					<div class="ranking-score-label">/ 10</div>
				</div>
			</div>

			<div class="ranking-item silver">
				<div class="ranking-position silver">2</div>
				<div class="ranking-info">
					<h3>Budmax Wykonczenia</h3>
					<div class="ranking-meta">
						<span class="ranking-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
						<span class="ranking-reviews">98 opinii</span>
						<span class="ranking-location">&#128205; Katowice</span>
					</div>
				</div>
				<div class="ranking-score">
					<div class="ranking-score-value">9.5</div>
					<div class="ranking-score-label">/ 10</div>
				</div>
			</div>

			<div class="ranking-item bronze">
				<div class="ranking-position bronze">3</div>
				<div class="ranking-info">
					<h3>Eko-Dom Remonty</h3>
					<div class="ranking-meta">
						<span class="ranking-stars">&#9733;&#9733;&#9733;&#9733;&#9734;</span>
						<span class="ranking-reviews">76 opinii</span>
						<span class="ranking-location">&#128205; Katowice</span>
					</div>
				</div>
				<div class="ranking-score">
					<div class="ranking-score-value">9.3</div>
					<div class="ranking-score-label">/ 10</div>
				</div>
			</div>

			<div class="ranking-item">
				<div class="ranking-position default">4</div>
				<div class="ranking-info">
					<h3>Solidne Wnetrza</h3>
					<div class="ranking-meta">
						<span class="ranking-stars">&#9733;&#9733;&#9733;&#9733;&#9734;</span>
						<span class="ranking-reviews">64 opinie</span>
						<span class="ranking-location">&#128205; Katowice</span>
					</div>
				</div>
				<div class="ranking-score">
					<div class="ranking-score-value">9.0</div>
					<div class="ranking-score-label">/ 10</div>
				</div>
			</div>

			<div class="ranking-item">
				<div class="ranking-position default">5</div>
				<div class="ranking-info">
					<h3>Artisan Remont</h3>
					<div class="ranking-meta">
						<span class="ranking-stars">&#9733;&#9733;&#9733;&#9733;&#9734;</span>
						<span class="ranking-reviews">51 opinii</span>
						<span class="ranking-location">&#128205; Katowice</span>
					</div>
				</div>
				<div class="ranking-score">
					<div class="ranking-score-value">8.8</div>
					<div class="ranking-score-label">/ 10</div>
				</div>
			</div>
		</div>
	</div>
</section>

<?php pp_pro_footer(); ?>
<?php wp_footer(); ?>
</body>
</html>

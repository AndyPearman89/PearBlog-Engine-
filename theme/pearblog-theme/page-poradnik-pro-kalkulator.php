<?php
/**
 * Template Name: Poradnik.PRO - Kalkulator (Single)
 * Description: Single calculator page (/kalkulator/{slug})
 *
 * @package PearBlog
 * @subpackage PoradnikPro
 * @version 6.0.0
 */

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/inc/poradnik-pro-shared.php';
require_once get_template_directory() . '/inc/poradnik-pro-seed-data.php';

$pp_slug       = get_query_var( 'poradnik_slug', '' );
$pp_calculator = pp_seed_get_calculator( $pp_slug );
$pp_calculators = pp_seed_calculators();
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
		.calc-hero { padding: 40px 0 32px; background: linear-gradient(135deg, #dcfce7 0%, #d1fae5 100%); }
		.calc-content { padding: 48px 0 64px; }
		.calc-body { max-width: 700px; margin: 0 auto; }

		.calc-form-card { background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius-xl); padding: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); margin-bottom: 32px; }
		.calc-field { margin-bottom: 20px; }
		.calc-field label { display: block; font-size: 14px; font-weight: 600; color: var(--gray-700); margin-bottom: 6px; }
		.calc-field input, .calc-field select { width: 100%; padding: 12px 16px; border: 1px solid var(--gray-200); border-radius: var(--radius-md); font-size: 14px; color: var(--gray-900); background: #fff; transition: border-color 0.2s; }
		.calc-field input:focus, .calc-field select:focus { outline: none; border-color: var(--purple-primary); box-shadow: 0 0 0 3px rgba(108,43,217,0.1); }
		.calc-btn { width: 100%; padding: 14px; border-radius: 50px; background: var(--green-accent); color: #fff; font-size: 15px; font-weight: 700; transition: opacity 0.2s; cursor: pointer; border: none; }
		.calc-btn:hover { opacity: 0.9; }

		.calc-results { display: none; background: #fff; border: 2px solid var(--green-accent); border-radius: var(--radius-xl); padding: 32px; margin-bottom: 32px; }
		.calc-results.visible { display: block; }
		.results-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
		.result-item { background: var(--gray-50); border-radius: var(--radius-md); padding: 16px; text-align: center; }
		.result-label { font-size: 12px; color: var(--gray-500); margin-bottom: 4px; }
		.result-value { font-size: 20px; font-weight: 800; color: var(--gray-900); }

		.calc-faq { margin-top: 40px; }
		.calc-faq h2 { font-size: 18px; font-weight: 700; color: var(--gray-900); margin-bottom: 16px; }
		.calc-faq-item { border: 1px solid var(--gray-200); border-radius: var(--radius-md); padding: 16px 20px; margin-bottom: 10px; }
		.calc-faq-item summary { cursor: pointer; font-size: 14px; font-weight: 600; color: var(--gray-800); }
		.calc-faq-item p { margin-top: 10px; font-size: 14px; color: var(--gray-600); line-height: 1.6; }

		.other-calcs { margin-top: 48px; border-top: 1px solid var(--gray-200); padding-top: 40px; }
		.other-calcs h2 { font-size: 18px; font-weight: 700; color: var(--gray-900); margin-bottom: 16px; }
		.other-calcs-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
		.other-calc-card { display: flex; align-items: center; gap: 12px; padding: 16px; border: 1px solid var(--gray-200); border-radius: var(--radius-md); transition: all 0.2s; }
		.other-calc-card:hover { border-color: var(--green-accent); background: #f0fdf4; }
		.other-calc-icon { font-size: 24px; }
		.other-calc-name { font-size: 14px; font-weight: 600; color: var(--gray-800); }

		@media (max-width: 768px) {
			.results-grid { grid-template-columns: 1fr; }
			.other-calcs-grid { grid-template-columns: 1fr; }
		}
	</style>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php pp_pro_header( 'kalkulatory' ); ?>

<!-- CALCULATOR HERO -->
<section class="calc-hero">
	<div class="container">
		<div class="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Strona główna</a>
			<span class="sep">/</span>
			<a href="<?php echo esc_url( home_url( '/kalkulatory/' ) ); ?>">Kalkulatory</a>
			<span class="sep">/</span>
			<span><?php echo esc_html( $pp_calculator['title'] ); ?></span>
		</div>
		<h1><?php echo esc_html( $pp_calculator['title'] ); ?></h1>
		<p style="font-size: 15px; color: var(--gray-600); margin-top: 8px;"><?php echo esc_html( $pp_calculator['description'] ); ?></p>
	</div>
</section>

<!-- CALCULATOR CONTENT -->
<section class="calc-content">
	<div class="container">
		<div class="calc-body">
			<!-- CALCULATOR FORM -->
			<div class="calc-form-card">
				<form id="pp-calc-form">
					<?php foreach ( $pp_calculator['fields'] as $field ) : ?>
					<div class="calc-field">
						<label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
						<?php if ( $field['type'] === 'select' ) : ?>
						<select id="<?php echo esc_attr( $field['id'] ); ?>" name="<?php echo esc_attr( $field['id'] ); ?>">
							<?php foreach ( $field['options'] as $val => $label ) : ?>
							<option value="<?php echo esc_attr( $val ); ?>"<?php echo $val === $field['default'] ? ' selected' : ''; ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<?php else : ?>
						<input type="number" id="<?php echo esc_attr( $field['id'] ); ?>" name="<?php echo esc_attr( $field['id'] ); ?>"
							value="<?php echo esc_attr( $field['default'] ); ?>"
							<?php if ( isset( $field['min'] ) ) : ?>min="<?php echo esc_attr( $field['min'] ); ?>"<?php endif; ?>
							<?php if ( isset( $field['max'] ) ) : ?>max="<?php echo esc_attr( $field['max'] ); ?>"<?php endif; ?>
							<?php if ( isset( $field['step'] ) ) : ?>step="<?php echo esc_attr( $field['step'] ); ?>"<?php endif; ?>>
						<?php endif; ?>
					</div>
					<?php endforeach; ?>
					<button type="button" class="calc-btn" onclick="ppOblicz()">Oblicz</button>
				</form>
			</div>

			<!-- RESULTS -->
			<div id="pp-calc-results" class="calc-results">
				<h2 style="font-size: 18px; font-weight: 700; margin-bottom: 16px; color: var(--gray-900);">&#9989; Wynik obliczeń</h2>
				<div id="pp-results-grid" class="results-grid"></div>
			</div>

			<!-- FAQ -->
			<div class="calc-faq">
				<h2>Najczęściej zadawane pytania</h2>
				<details class="calc-faq-item">
					<summary>Jak działa ten kalkulator?</summary>
					<p>Kalkulator wykorzystuje aktualne dane rynkowe i sprawdzone wzory obliczeniowe. Wynik jest orientacyjny i może różnić się od ostatecznych kosztów zależnie od indywidualnej sytuacji.</p>
				</details>
				<details class="calc-faq-item">
					<summary>Czy wyniki są dokładne?</summary>
					<p>Wyniki mają charakter szacunkowy. Służą do wstępnej orientacji — w przypadku ważnych decyzji finansowych skonsultuj się ze specjalistą.</p>
				</details>
				<details class="calc-faq-item">
					<summary>Jak często aktualizujecie dane?</summary>
					<p>Dane wejściowe (stawki, ceny) aktualizujemy co miesiąc na podstawie oficjalnych źródeł i analiz rynkowych.</p>
				</details>
			</div>

			<!-- OTHER CALCULATORS -->
			<div class="other-calcs">
				<h2>Inne kalkulatory</h2>
				<div class="other-calcs-grid">
					<?php
					$pp_related_calc = 0;
					foreach ( $pp_calculators as $oc ) :
						if ( $oc['slug'] === $pp_calculator['slug'] ) {
							continue;
						}
						if ( $pp_related_calc >= 4 ) {
							break;
						}
						++$pp_related_calc;
					?>
					<a href="<?php echo esc_url( home_url( '/kalkulator/' . $oc['slug'] . '/' ) ); ?>" class="other-calc-card">
						<span class="other-calc-icon"><?php echo $oc['icon']; ?></span>
						<span class="other-calc-name"><?php echo esc_html( $oc['title'] ); ?></span>
					</a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
</section>

<?php pp_pro_footer(); ?>

<script>
function ppOblicz() {
	var f = document.getElementById('pp-calc-form');
	var results;
	try {
		results = (function(f) {
			<?php echo $pp_calculator['formula_js']; ?>
		})(f);
	} catch(e) {
		return;
	}
	var grid = document.getElementById('pp-results-grid');
	grid.innerHTML = '';
	for (var key in results) {
		if (results.hasOwnProperty(key)) {
			grid.innerHTML += '<div class="result-item"><div class="result-label">' + key + '</div><div class="result-value">' + results[key] + '</div></div>';
		}
	}
	document.getElementById('pp-calc-results').classList.add('visible');
}
</script>

<?php wp_footer(); ?>
</body>
</html>

<?php
/**
 * Template Name: Poradnik V6 — AI Decision Engine
 * Template Post Type: page
 *
 * Poradnik.pro V6 — AI-first Decision Engine Platform
 * 10-section production homepage (dark premium SaaS aesthetic)
 *
 * Sections:
 *   1. Hero AI Search
 *   2. Decision Entry Grid
 *   3. Trending Decisions
 *   4. AI Advisor
 *   5. Rankings
 *   6. Comparisons
 *   7. Calculators
 *   8. Specialists Marketplace
 *   9. Local Hubs
 *  10. Revenue CTA
 *
 * @package PearBlog
 * @version 6.0.0
 */

defined( 'ABSPATH' ) || exit;

$site_url  = home_url( '/' );
$rest_base = rest_url( 'pearblog/v1' );

// Trust stats (pull from options with sensible defaults).
$decisions_count  = number_format( (int) get_option( 'pb_v6_decisions_count',  124800 ), 0, ',', ' ' );
$specialists_count = number_format( (int) get_option( 'pb_v6_specialists_count', 3200 ), 0, ',', ' ' );
$cities_count     = number_format( (int) get_option( 'pb_v6_cities_count',        420 ), 0, ',', ' ' );

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( get_bloginfo( 'name' ) ); ?> — Od problemu do decyzji</title>
<meta name="description" content="Poradnik.pro łączy wiedzę, porównania i specjalistów w jednym miejscu. Tu kończy się research.">

<!-- Preconnect -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,300..800;1,14..32,300..700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<!-- V6 Design System -->
<link rel="stylesheet" href="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/css/poradnik-v6.css' ); ?>?v=6.0.0">

<!-- Schema.org — WebSite with SearchAction -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "Poradnik.pro",
  "url": "<?php echo esc_url( home_url() ); ?>",
  "description": "AI-first platforma decyzyjna. Od problemu do decyzji.",
  "potentialAction": {
    "@type": "SearchAction",
    "target": {
      "@type": "EntryPoint",
      "urlTemplate": "<?php echo esc_url( home_url( '/?s={search_term_string}' ) ); ?>"
    },
    "query-input": "required name=search_term_string"
  }
}
</script>

<?php wp_head(); ?>
</head>
<body class="v6-page">

<!-- ══════════════════════════════════════════════════════════════════
     1. HERO — AI Search
     ══════════════════════════════════════════════════════════════════ -->
<section class="v6-hero" id="hero" aria-label="Wyszukiwarka decyzyjna">

	<!-- Ambient background orbs -->
	<div class="v6-hero__orb v6-hero__orb--1" aria-hidden="true"></div>
	<div class="v6-hero__orb v6-hero__orb--2" aria-hidden="true"></div>
	<div class="v6-hero__orb v6-hero__orb--3" aria-hidden="true"></div>
	<div class="v6-hero__noise" aria-hidden="true"></div>

	<div class="v6-container v6-hero__inner">

		<!-- Badge -->
		<div class="v6-hero__badge" aria-label="Status platformy">
			<span class="v6-hero__badge-dot" aria-hidden="true"></span>
			<span>AI Decision Engine — v6</span>
		</div>

		<!-- Headline -->
		<h1 class="v6-hero__headline">
			Masz problem?<br>
			<span class="v6-hero__headline-gradient">Zamień go w decyzję</span><br>
			— w kilka minut.
		</h1>

		<p class="v6-hero__sub">
			Poradnik.pro łączy wiedzę, porównania i specjalistów w jednym miejscu.<br>
			<strong>Tu kończy się research.</strong>
		</p>

		<!-- AI Search box -->
		<div class="v6-search" role="search" aria-label="Wyszukaj decyzję">
			<div class="v6-search__glow" aria-hidden="true"></div>
			<div class="v6-search__box">
				<span class="v6-search__icon" aria-hidden="true">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
				</span>
				<input
					type="search"
					id="v6-search-input"
					class="v6-search__input"
					placeholder="Wpisz problem lub pytanie…"
					autocomplete="off"
					aria-autocomplete="list"
					aria-controls="v6-search-suggestions"
					aria-label="Wyszukaj"
				>
				<button class="v6-search__btn" id="v6-search-btn" aria-label="Szukaj">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
				</button>
			</div>

			<!-- Suggestions dropdown -->
			<div class="v6-search__suggestions" id="v6-search-suggestions" role="listbox" aria-label="Podpowiedzi" hidden>
				<div class="v6-search__suggestions-inner"></div>
			</div>
		</div>

		<!-- Example queries -->
		<div class="v6-hero__examples" aria-label="Przykładowe pytania">
			<span class="v6-hero__examples-label">Popularne:</span>
			<?php
			$examples = [
				'koszt remontu łazienki',
				'pompa ciepła czy gaz',
				'dobry prawnik Katowice',
				'ile kosztuje budowa domu',
			];
			foreach ( $examples as $ex ) :
			?>
			<button class="v6-pill v6-hero__example" data-query="<?php echo esc_attr( $ex ); ?>">
				<?php echo esc_html( $ex ); ?>
			</button>
			<?php endforeach; ?>
		</div>

		<!-- CTA buttons -->
		<div class="v6-hero__ctas">
			<a href="<?php echo esc_url( $site_url . '?cat=poradniki' ); ?>" class="v6-btn v6-btn--primary">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
				Znajdź rozwiązanie
			</a>
			<a href="#ai-advisor" class="v6-btn v6-btn--ghost">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
				Zadaj pytanie
			</a>
			<a href="<?php echo esc_url( $site_url . 'specjalisci/' ); ?>" class="v6-btn v6-btn--outline">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
				Znajdź specjalistę
			</a>
		</div>

		<!-- Trust stats -->
		<div class="v6-hero__trust" aria-label="Statystyki platformy">
			<div class="v6-hero__trust-item">
				<span class="v6-hero__trust-num"><?php echo esc_html( $decisions_count ); ?>+</span>
				<span class="v6-hero__trust-label">Decyzji podjętych</span>
			</div>
			<div class="v6-hero__trust-divider" aria-hidden="true"></div>
			<div class="v6-hero__trust-item">
				<span class="v6-hero__trust-num"><?php echo esc_html( $specialists_count ); ?>+</span>
				<span class="v6-hero__trust-label">Zweryfikowanych specjalistów</span>
			</div>
			<div class="v6-hero__trust-divider" aria-hidden="true"></div>
			<div class="v6-hero__trust-item">
				<span class="v6-hero__trust-num"><?php echo esc_html( $cities_count ); ?>+</span>
				<span class="v6-hero__trust-label">Miast w Polsce</span>
			</div>
		</div>

	</div><!-- /.v6-hero__inner -->
</section>


<!-- ══════════════════════════════════════════════════════════════════
     2. DECISION ENTRY GRID
     ══════════════════════════════════════════════════════════════════ -->
<section class="v6-section v6-grid-section" id="decision-grid" aria-label="Kategorie decyzji">
	<div class="v6-container">

		<div class="v6-section__header">
			<h2 class="v6-section__title">Wybierz swój typ decyzji</h2>
			<p class="v6-section__sub">Cztery ścieżki — jedna platforma</p>
		</div>

		<div class="v6-decision-grid">
			<?php
			$decision_cards = [
				[
					'icon'  => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
					'tag'   => 'Poradniki',
					'title' => 'Zrozum problem',
					'desc'  => 'Eksperckie przewodniki, które zamieniają skomplikowane tematy w konkretne kroki.',
					'url'   => $site_url . '?cat=poradniki',
					'color' => 'primary',
					'count' => get_option( 'pb_v6_guides_count', '2 400' ),
				],
				[
					'icon'  => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>',
					'tag'   => 'Porównania',
					'title' => 'Porównaj opcje',
					'desc'  => 'Obiektywne zestawienia z AI werdyktem. Pompa ciepła vs gaz. Fotowoltaika vs sieć.',
					'url'   => $site_url . 'porownania/',
					'color' => 'accent',
					'count' => get_option( 'pb_v6_compare_count', '380' ),
				],
				[
					'icon'  => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2z"/></svg>',
					'tag'   => 'Rankingi',
					'title' => 'Sprawdź najlepszych',
					'desc'  => 'Rankingujemy firmy i specjalistów na podstawie realnych danych, nie reklam.',
					'url'   => $site_url . 'rankingi/',
					'color' => 'success',
					'count' => get_option( 'pb_v6_rankings_count', '120' ),
				],
				[
					'icon'  => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/><path d="M7 8h2v5H7zM11 10h2v3h-2zM15 6h2v7h-2z"/></svg>',
					'tag'   => 'Kalkulatory',
					'title' => 'Policz koszty',
					'desc'  => 'Kalkulatory kosztów, ROI i zwrotu z inwestycji. Decyduj z liczbami w ręku.',
					'url'   => $site_url . 'kalkulatory/',
					'color' => 'warning',
					'count' => get_option( 'pb_v6_calcs_count', '65' ),
				],
			];

			foreach ( $decision_cards as $card ) :
			?>
			<a href="<?php echo esc_url( $card['url'] ); ?>"
			   class="v6-dcard v6-dcard--<?php echo esc_attr( $card['color'] ); ?>"
			   aria-label="<?php echo esc_attr( $card['title'] ); ?>">
				<div class="v6-dcard__glow" aria-hidden="true"></div>
				<div class="v6-dcard__icon" aria-hidden="true"><?php echo $card['icon']; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<span class="v6-dcard__tag"><?php echo esc_html( $card['tag'] ); ?></span>
				<h3 class="v6-dcard__title"><?php echo esc_html( $card['title'] ); ?></h3>
				<p class="v6-dcard__desc"><?php echo esc_html( $card['desc'] ); ?></p>
				<div class="v6-dcard__footer">
					<span class="v6-dcard__count"><?php echo esc_html( $card['count'] ); ?> pozycji</span>
					<span class="v6-dcard__arrow" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
					</span>
				</div>
			</a>
			<?php endforeach; ?>
		</div>

	</div>
</section>


<!-- ══════════════════════════════════════════════════════════════════
     3. TRENDING DECISIONS
     ══════════════════════════════════════════════════════════════════ -->
<section class="v6-section v6-trending-section" id="trending" aria-label="Popularne decyzje">
	<div class="v6-container">

		<div class="v6-section__header v6-section__header--row">
			<div>
				<div class="v6-tag v6-tag--hot">
					<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2c.8 4.2-2.2 7-2.5 10.5C9.1 14.7 10 17 12 17c2 0 2.9-2.3 2.5-4.5-.3-3.5-3.3-6.3-2.5-10.5zM9 21a3 3 0 0 0 6 0c0-2-3-5-3-5S9 19 9 21z"/></svg>
					Trending
				</div>
				<h2 class="v6-section__title">Popularne decyzje teraz</h2>
			</div>
			<a href="<?php echo esc_url( $site_url . '?cat=trending' ); ?>" class="v6-link">
				Wszystkie <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
			</a>
		</div>

		<div class="v6-trending-track" aria-label="Lista trendujących tematów">
			<?php
			$trending = [
				[ 'title' => 'Ile kosztuje remont mieszkania 2026?',  'cat' => 'Remont',   'tag' => '#1',        'url' => $site_url . '?p=remont-mieszkania-2026' ],
				[ 'title' => 'Pompa ciepła vs gaz — co się opłaca?',   'cat' => 'Ogrzewanie','tag' => 'Trending',  'url' => $site_url . 'porownania/pompa-ciepla-vs-gaz/' ],
				[ 'title' => 'Ranking firm remontowych Warszawa',       'cat' => 'Ranking',  'tag' => 'Nowy',      'url' => $site_url . 'rankingi/firmy-remontowe-warszawa/' ],
				[ 'title' => 'Koszt budowy domu 2026 — kalkulator',    'cat' => 'Budowa',   'tag' => 'Gorący',    'url' => $site_url . 'kalkulatory/koszt-budowy-domu/' ],
				[ 'title' => 'Fotowoltaika czy sieć — opłacalność',    'cat' => 'Energia',  'tag' => 'Trending',  'url' => $site_url . 'porownania/fotowoltaika-vs-siec/' ],
				[ 'title' => 'Dobry prawnik rodzinny — jak wybrać?',   'cat' => 'Prawo',    'tag' => '#5',        'url' => $site_url . '?p=jak-wybrac-prawnika-rodzinnego' ],
				[ 'title' => 'Wykończenie pod klucz 2026 — ceny',      'cat' => 'Remont',   'tag' => 'Nowy',      'url' => $site_url . 'kalkulatory/wykonczenie-pod-klucz/' ],
				[ 'title' => 'Hydraulik Kraków — ranking i ceny',      'cat' => 'Usługi',   'tag' => 'Lokalny',   'url' => $site_url . 'rankingi/hydraulik-krakow/' ],
			];

			foreach ( $trending as $t ) :
			?>
			<a href="<?php echo esc_url( $t['url'] ); ?>" class="v6-tcard" aria-label="<?php echo esc_attr( $t['title'] ); ?>">
				<div class="v6-tcard__meta">
					<span class="v6-pill v6-pill--sm v6-pill--cat"><?php echo esc_html( $t['cat'] ); ?></span>
					<span class="v6-pill v6-pill--sm v6-pill--tag"><?php echo esc_html( $t['tag'] ); ?></span>
				</div>
				<p class="v6-tcard__title"><?php echo esc_html( $t['title'] ); ?></p>
				<span class="v6-tcard__arrow" aria-hidden="true">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
				</span>
			</a>
			<?php endforeach; ?>
		</div>

	</div>
</section>


<!-- ══════════════════════════════════════════════════════════════════
     4. AI ADVISOR
     ══════════════════════════════════════════════════════════════════ -->
<section class="v6-section v6-advisor-section" id="ai-advisor" aria-label="Asystent AI">
	<div class="v6-container">

		<div class="v6-advisor">

			<!-- Left: input panel -->
			<div class="v6-advisor__panel">
				<div class="v6-advisor__badge">
					<span class="v6-advisor__ai-dot" aria-hidden="true"></span>
					AI Doradca
				</div>
				<h2 class="v6-advisor__title">Opisz swój problem — AI znajdzie rozwiązanie</h2>
				<p class="v6-advisor__sub">Podaj kontekst, budżet i miasto, a system zaproponuje konkretne kroki.</p>

				<form class="v6-advisor__form" id="v6-advisor-form" aria-label="Formularz doradcy AI" novalidate>
					<div class="v6-advisor__field">
						<label class="v6-advisor__label" for="advisor-query">Twój problem lub pytanie *</label>
						<textarea
							id="advisor-query"
							class="v6-advisor__textarea"
							name="query"
							placeholder="np. Chcę wymienić ogrzewanie — nie wiem czy wybrać pompę ciepła czy zostać przy gazie…"
							rows="3"
							required
							aria-required="true"
						></textarea>
					</div>

					<div class="v6-advisor__row">
						<div class="v6-advisor__field">
							<label class="v6-advisor__label" for="advisor-budget">Budżet (zł)</label>
							<input type="number" id="advisor-budget" class="v6-advisor__input" name="budget" placeholder="np. 20000" min="0">
						</div>
						<div class="v6-advisor__field">
							<label class="v6-advisor__label" for="advisor-city">Miasto</label>
							<input type="text" id="advisor-city" class="v6-advisor__input" name="city" placeholder="np. Warszawa">
						</div>
					</div>

					<div class="v6-advisor__field">
						<label class="v6-advisor__label">Twój cel</label>
						<div class="v6-advisor__goals" role="group" aria-label="Wybierz cel">
							<?php
							$goals = [
								'oszczędność'   => 'Oszczędność',
								'jakość'        => 'Najlepsza jakość',
								'szybko'        => 'Jak najszybciej',
								'lokalnie'      => 'Lokalny specjalista',
							];
							foreach ( $goals as $val => $label ) :
							?>
							<label class="v6-advisor__goal-chip">
								<input type="radio" name="goal" value="<?php echo esc_attr( $val ); ?>" class="v6-advisor__radio">
								<span><?php echo esc_html( $label ); ?></span>
							</label>
							<?php endforeach; ?>
						</div>
					</div>

					<button type="submit" class="v6-btn v6-btn--primary v6-btn--full" id="v6-advisor-btn">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
						Zapytaj AI Doradcę
					</button>
				</form>
			</div>

			<!-- Right: response panel -->
			<div class="v6-advisor__response" id="v6-advisor-response" aria-live="polite" aria-label="Odpowiedź AI">
				<div class="v6-advisor__placeholder" id="v6-advisor-placeholder">
					<div class="v6-advisor__placeholder-icon" aria-hidden="true">
						<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M12 2a10 10 0 1 0 10 10H12V2z"/><path d="M21.17 8H12V2.83"/><path d="M12 12h.01"/></svg>
					</div>
					<p class="v6-advisor__placeholder-text">Twoja odpowiedź pojawi się tutaj.<br>Opisz problem po lewej stronie.</p>
				</div>
				<div class="v6-advisor__result" id="v6-advisor-result" hidden>
					<div class="v6-advisor__result-header">
						<span class="v6-advisor__ai-dot" aria-hidden="true"></span>
						<span class="v6-advisor__result-label">AI Doradca odpowiada</span>
					</div>
					<div class="v6-advisor__result-text" id="v6-advisor-text"></div>
					<div class="v6-advisor__options" id="v6-advisor-options"></div>
					<div class="v6-advisor__followups" id="v6-advisor-followups"></div>
				</div>
			</div>

		</div><!-- /.v6-advisor -->
	</div>
</section>


<!-- ══════════════════════════════════════════════════════════════════
     5. RANKINGS
     ══════════════════════════════════════════════════════════════════ -->
<section class="v6-section v6-rankings-section" id="rankingi" aria-label="Rankingi">
	<div class="v6-container">

		<div class="v6-section__header">
			<div>
				<div class="v6-tag">Rankingi</div>
				<h2 class="v6-section__title">Najlepsi w każdej kategorii</h2>
				<p class="v6-section__sub">Weryfikowane dane. AI reputacja. Zero reklam na szczycie.</p>
			</div>
			<a href="<?php echo esc_url( $site_url . 'rankingi/' ); ?>" class="v6-link">
				Wszystkie rankingi <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
			</a>
		</div>

		<?php
		// Fetch rankings from API (REST) or show placeholders.
		$ranking_items = [
			[
				'pos'       => 1,
				'name'      => 'EkoTerm Sp. z o.o.',
				'cat'       => 'Pompy ciepła',
				'score'     => 97,
				'reviews'   => 284,
				'rating'    => 4.9,
				'badge'     => 'Lider',
				'badge_type'=> 'gold',
				'city'      => 'Warszawa',
				'response'  => '< 2h',
				'verified'  => true,
				'sponsored' => false,
				'url'       => $site_url . 'rankingi/pompy-ciepla/',
			],
			[
				'pos'       => 2,
				'name'      => 'ZłotaRurka Hydraulika',
				'cat'       => 'Hydraulika',
				'score'     => 94,
				'reviews'   => 512,
				'rating'    => 4.8,
				'badge'     => 'Zaufany',
				'badge_type'=> 'silver',
				'city'      => 'Kraków',
				'response'  => '< 4h',
				'verified'  => true,
				'sponsored' => false,
				'url'       => $site_url . 'rankingi/hydraulicy-krakow/',
			],
			[
				'pos'       => 3,
				'name'      => 'PrawoPro Kancelaria',
				'cat'       => 'Prawo rodzinne',
				'score'     => 91,
				'reviews'   => 198,
				'rating'    => 4.7,
				'badge'     => 'Premium',
				'badge_type'=> 'featured',
				'city'      => 'Wrocław',
				'response'  => '< 8h',
				'verified'  => true,
				'sponsored' => true,
				'url'       => $site_url . 'rankingi/prawnicy-wroclaw/',
			],
		];
		?>

		<div class="v6-rankings">
			<?php foreach ( $ranking_items as $item ) :
				$pos_class = $item['pos'] === 1 ? 'v6-rcard--gold' : ( $item['pos'] === 2 ? 'v6-rcard--silver' : 'v6-rcard--bronze' );
			?>
			<article class="v6-rcard <?php echo esc_attr( $pos_class ); ?><?php echo $item['sponsored'] ? ' v6-rcard--sponsored' : ''; ?>"
			         aria-label="<?php echo esc_attr( '#' . $item['pos'] . ' ' . $item['name'] ); ?>">

				<?php if ( $item['sponsored'] ) : ?>
				<div class="v6-rcard__sponsored-tag" aria-label="Reklama">Promowane</div>
				<?php endif; ?>

				<div class="v6-rcard__pos" aria-label="Pozycja <?php echo esc_attr( $item['pos'] ); ?>">
					#<?php echo esc_html( $item['pos'] ); ?>
				</div>

				<div class="v6-rcard__body">
					<div class="v6-rcard__top">
						<div class="v6-rcard__avatar" aria-hidden="true">
							<?php echo esc_html( mb_strtoupper( mb_substr( $item['name'], 0, 2 ) ) ); ?>
						</div>
						<div class="v6-rcard__info">
							<div class="v6-rcard__name">
								<?php echo esc_html( $item['name'] ); ?>
								<?php if ( $item['verified'] ) : ?>
								<svg class="v6-rcard__verified" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-label="Zweryfikowany" title="Zweryfikowany specjalista"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
								<?php endif; ?>
							</div>
							<div class="v6-rcard__meta">
								<span class="v6-pill v6-pill--sm v6-pill--cat"><?php echo esc_html( $item['cat'] ); ?></span>
								<span class="v6-rcard__city"><?php echo esc_html( $item['city'] ); ?></span>
							</div>
						</div>
						<div class="v6-badge v6-badge--<?php echo esc_attr( $item['badge_type'] ); ?>">
							<?php echo esc_html( $item['badge'] ); ?>
						</div>
					</div>

					<div class="v6-rcard__stats">
						<div class="v6-rcard__stat">
							<span class="v6-rcard__stat-val"><?php echo esc_html( $item['rating'] ); ?></span>
							<span class="v6-rcard__stat-stars" aria-label="Ocena <?php echo esc_attr( $item['rating'] ); ?> na 5">
								<?php for ( $s = 0; $s < 5; $s++ ) : ?>
								<svg width="12" height="12" viewBox="0 0 24 24" fill="<?php echo $s < floor( $item['rating'] ) ? '#F59E0B' : 'none'; ?>" stroke="#F59E0B" stroke-width="1.5" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
								<?php endfor; ?>
							</span>
							<span class="v6-rcard__stat-label">(<?php echo esc_html( $item['reviews'] ); ?> opinii)</span>
						</div>
						<div class="v6-rcard__stat">
							<span class="v6-rcard__stat-val"><?php echo esc_html( $item['score'] ); ?>%</span>
							<span class="v6-rcard__stat-label">AI score</span>
						</div>
						<div class="v6-rcard__stat">
							<span class="v6-rcard__stat-val"><?php echo esc_html( $item['response'] ); ?></span>
							<span class="v6-rcard__stat-label">czas odpowiedzi</span>
						</div>
					</div>

					<div class="v6-rcard__actions">
						<a href="<?php echo esc_url( $item['url'] ); ?>" class="v6-btn v6-btn--primary v6-btn--sm">
							Wyślij zapytanie
						</a>
						<a href="<?php echo esc_url( $item['url'] ); ?>" class="v6-btn v6-btn--ghost v6-btn--sm">
							Profil
						</a>
					</div>
				</div>
			</article>
			<?php endforeach; ?>
		</div>

		<!-- AI summary -->
		<div class="v6-ai-summary" aria-label="Podsumowanie AI">
			<div class="v6-ai-summary__icon" aria-hidden="true">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2a10 10 0 1 0 10 10H12V2z"/></svg>
			</div>
			<p class="v6-ai-summary__text">
				<strong>AI ocenia:</strong> W tej kategorii EkoTerm wyróżnia się najwyższym wskaźnikiem zaufania (97%) i najkrótszym czasem odpowiedzi. Warto porównać go z ofertą lokalnych instalatorów w Twoim mieście.
			</p>
		</div>

	</div>
</section>


<!-- ══════════════════════════════════════════════════════════════════
     6. COMPARISONS
     ══════════════════════════════════════════════════════════════════ -->
<section class="v6-section v6-compare-section" id="porownania" aria-label="Porównania">
	<div class="v6-container">

		<div class="v6-section__header">
			<div>
				<div class="v6-tag v6-tag--accent">Porównania</div>
				<h2 class="v6-section__title">Porównaj — zanim zdecydujesz</h2>
				<p class="v6-section__sub">AI analizuje dane. Ty podejmujesz decyzję.</p>
			</div>
			<a href="<?php echo esc_url( $site_url . 'porownania/' ); ?>" class="v6-link">
				Wszystkie <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
			</a>
		</div>

		<div class="v6-compare-grid">
			<?php
			$comparisons = [
				[
					'slug'    => 'pompa-ciepla-vs-gaz',
					'title'   => 'Pompa ciepła vs Gaz',
					'desc'    => 'Ogrzewanie domu — co tańsze i bardziej ekologiczne?',
					'verdict' => 'Pompa ciepła wygrywa w perspektywie 10 lat.',
					'a'       => 'Pompa ciepła',
					'b'       => 'Gaz',
					'a_score' => 82,
					'b_score' => 71,
					'url'     => $site_url . 'porownania/pompa-ciepla-vs-gaz/',
				],
				[
					'slug'    => 'fotowoltaika-vs-siec',
					'title'   => 'Fotowoltaika vs Sieć',
					'desc'    => 'Czy instalacja paneli słonecznych się opłaca?',
					'verdict' => 'Zwrot z inwestycji w 6–8 lat. Fotowoltaika wygrywa.',
					'a'       => 'Fotowoltaika',
					'b'       => 'Sieć',
					'a_score' => 88,
					'b_score' => 60,
					'url'     => $site_url . 'porownania/fotowoltaika-vs-siec/',
				],
				[
					'slug'    => 'remont-vs-pod-klucz',
					'title'   => 'Remont własny vs Pod klucz',
					'desc'    => 'Czy warto zlecić wykończenie ekipie pod klucz?',
					'verdict' => 'Pod klucz oszczędza czas, remont własny — pieniądze.',
					'a'       => 'Samodzielny',
					'b'       => 'Pod klucz',
					'a_score' => 70,
					'b_score' => 85,
					'url'     => $site_url . 'porownania/remont-vs-pod-klucz/',
				],
			];
			foreach ( $comparisons as $cmp ) :
			?>
			<article class="v6-cmpcard" aria-label="Porównanie: <?php echo esc_attr( $cmp['title'] ); ?>">
				<h3 class="v6-cmpcard__title"><?php echo esc_html( $cmp['title'] ); ?></h3>
				<p class="v6-cmpcard__desc"><?php echo esc_html( $cmp['desc'] ); ?></p>

				<div class="v6-cmpcard__versus">
					<div class="v6-cmpcard__option">
						<span class="v6-cmpcard__option-label"><?php echo esc_html( $cmp['a'] ); ?></span>
						<div class="v6-cmpcard__bar">
							<div class="v6-cmpcard__bar-fill v6-cmpcard__bar-fill--a" style="width:<?php echo (int) $cmp['a_score']; ?>%" role="meter" aria-valuenow="<?php echo (int) $cmp['a_score']; ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Wynik: <?php echo (int) $cmp['a_score']; ?>%"></div>
						</div>
						<span class="v6-cmpcard__score"><?php echo (int) $cmp['a_score']; ?>%</span>
					</div>
					<div class="v6-cmpcard__vs" aria-hidden="true">VS</div>
					<div class="v6-cmpcard__option">
						<span class="v6-cmpcard__option-label"><?php echo esc_html( $cmp['b'] ); ?></span>
						<div class="v6-cmpcard__bar">
							<div class="v6-cmpcard__bar-fill v6-cmpcard__bar-fill--b" style="width:<?php echo (int) $cmp['b_score']; ?>%" role="meter" aria-valuenow="<?php echo (int) $cmp['b_score']; ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Wynik: <?php echo (int) $cmp['b_score']; ?>%"></div>
						</div>
						<span class="v6-cmpcard__score"><?php echo (int) $cmp['b_score']; ?>%</span>
					</div>
				</div>

				<div class="v6-cmpcard__verdict">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2a10 10 0 1 0 10 10H12V2z"/></svg>
					<span><?php echo esc_html( $cmp['verdict'] ); ?></span>
				</div>

				<a href="<?php echo esc_url( $cmp['url'] ); ?>" class="v6-btn v6-btn--ghost v6-btn--sm v6-cmpcard__link">
					Pełne porównanie
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
				</a>
			</article>
			<?php endforeach; ?>
		</div>

	</div>
</section>


<!-- ══════════════════════════════════════════════════════════════════
     7. CALCULATORS
     ══════════════════════════════════════════════════════════════════ -->
<section class="v6-section v6-calc-section" id="kalkulatory" aria-label="Kalkulatory kosztów">
	<div class="v6-container">

		<div class="v6-section__header">
			<div>
				<div class="v6-tag v6-tag--warning">Kalkulatory</div>
				<h2 class="v6-section__title">Policz — zanim zapłacisz</h2>
				<p class="v6-section__sub">Szybkie kalkulatory kosztów z rekomendacjami AI.</p>
			</div>
			<a href="<?php echo esc_url( $site_url . 'kalkulatory/' ); ?>" class="v6-link">
				Wszystkie kalkulatory <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
			</a>
		</div>

		<!-- Featured inline calculator -->
		<div class="v6-calc-featured" id="kalkulator-remont" aria-label="Kalkulator kosztów remontu">
			<div class="v6-calc-featured__label">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
				Kalkulator kosztów remontu
			</div>
			<h3 class="v6-calc-featured__title">Ile będzie kosztował Twój remont?</h3>

			<div class="v6-calc-fields" id="v6-calc-fields">
				<!-- Area slider -->
				<div class="v6-calc-field">
					<label class="v6-calc-label" for="calc-area">
						Powierzchnia
						<span class="v6-calc-val" id="calc-area-val">50 m²</span>
					</label>
					<input type="range" id="calc-area" class="v6-calc-slider" min="10" max="200" value="50" step="5"
					       aria-label="Powierzchnia w metrach kwadratowych" aria-valuemin="10" aria-valuemax="200" aria-valuenow="50">
				</div>

				<!-- Standard select -->
				<div class="v6-calc-field">
					<label class="v6-calc-label" for="calc-standard">Standard wykończenia</label>
					<select id="calc-standard" class="v6-calc-select" aria-label="Wybierz standard wykończenia">
						<option value="ekonomiczny">Ekonomiczny (~800 zł/m²)</option>
						<option value="sredni" selected>Średni (~1 400 zł/m²)</option>
						<option value="premium">Premium (~2 500 zł/m²)</option>
						<option value="luksus">Luksusowy (~4 000 zł/m²)</option>
					</select>
				</div>
			</div>

			<div class="v6-calc-result" id="v6-calc-result" aria-live="polite" aria-label="Wynik kalkulatora">
				<div class="v6-calc-result__label">Szacowany koszt</div>
				<div class="v6-calc-result__value" id="v6-calc-value">70 000 zł</div>
				<div class="v6-calc-result__range" id="v6-calc-range">zakres: 56 000 – 84 000 zł</div>
			</div>

			<div class="v6-calc-ctas">
				<a href="<?php echo esc_url( $site_url . 'kalkulatory/remont/' ); ?>" class="v6-btn v6-btn--primary v6-btn--sm">
					Dokładny kalkulator
				</a>
				<a href="<?php echo esc_url( $site_url . 'rankingi/firmy-remontowe/' ); ?>" class="v6-btn v6-btn--ghost v6-btn--sm">
					Znajdź ekipę
				</a>
			</div>
		</div>

		<!-- Calculator cards grid -->
		<div class="v6-calc-grid">
			<?php
			$calc_cards = [
				[ 'icon' => '🏠', 'title' => 'Koszt budowy domu',       'url' => $site_url . 'kalkulatory/budowa-domu/',   'uses' => '18.4k' ],
				[ 'icon' => '🔌', 'title' => 'Opłacalność fotowoltaiki', 'url' => $site_url . 'kalkulatory/fotowoltaika/', 'uses' => '12.1k' ],
				[ 'icon' => '🌡️', 'title' => 'Kalkulator pompy ciepła',  'url' => $site_url . 'kalkulatory/pompa-ciepla/', 'uses' => '9.8k'  ],
				[ 'icon' => '🚗', 'title' => 'Koszt naprawy samochodu',  'url' => $site_url . 'kalkulatory/naprawa-auta/', 'uses' => '7.2k'  ],
			];
			foreach ( $calc_cards as $cc ) :
			?>
			<a href="<?php echo esc_url( $cc['url'] ); ?>" class="v6-calc-card" aria-label="<?php echo esc_attr( $cc['title'] ); ?>">
				<span class="v6-calc-card__icon" aria-hidden="true"><?php echo $cc['icon']; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span class="v6-calc-card__title"><?php echo esc_html( $cc['title'] ); ?></span>
				<span class="v6-calc-card__uses"><?php echo esc_html( $cc['uses'] ); ?> użyć</span>
			</a>
			<?php endforeach; ?>
		</div>

	</div>
</section>


<!-- ══════════════════════════════════════════════════════════════════
     8. SPECIALISTS MARKETPLACE
     ══════════════════════════════════════════════════════════════════ -->
<section class="v6-section v6-specialists-section" id="specjalisci" aria-label="Specjaliści">
	<div class="v6-container">

		<div class="v6-section__header">
			<div>
				<div class="v6-tag v6-tag--success">Specjaliści</div>
				<h2 class="v6-section__title">Sprawdzeni specjaliści w Twoim mieście</h2>
				<p class="v6-section__sub">Zweryfikowani, z realnymi opiniami i szybkim czasem odpowiedzi.</p>
			</div>
			<a href="<?php echo esc_url( $site_url . 'specjalisci/' ); ?>" class="v6-link">
				Wszyscy specjaliści <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
			</a>
		</div>

		<div class="v6-specialists-grid">
			<?php
			$specialists = [
				[
					'name'        => 'Marek Kowalski',
					'trade'       => 'Hydraulik',
					'city'        => 'Warszawa',
					'rating'      => 4.9,
					'reviews'     => 127,
					'response'    => '< 1h',
					'verified'    => true,
					'badge'       => 'Złoty',
					'badge_type'  => 'gold',
					'specialties' => [ 'Instalacje', 'Awarie', 'Łazienki' ],
					'initials'    => 'MK',
					'url'         => $site_url . 'specjalisci/hydraulik-warszawa/',
				],
				[
					'name'        => 'Anna Wiśniewska',
					'trade'       => 'Prawnik',
					'city'        => 'Kraków',
					'rating'      => 4.8,
					'reviews'     => 89,
					'response'    => '< 3h',
					'verified'    => true,
					'badge'       => 'Srebrny',
					'badge_type'  => 'silver',
					'specialties' => [ 'Prawo rodzinne', 'Nieruchomości', 'Kontrakty' ],
					'initials'    => 'AW',
					'url'         => $site_url . 'specjalisci/prawnik-krakow/',
				],
				[
					'name'        => 'Piotr Nowak',
					'trade'       => 'Elektryk',
					'city'        => 'Wrocław',
					'rating'      => 4.7,
					'reviews'     => 203,
					'response'    => '< 2h',
					'verified'    => true,
					'badge'       => 'Zaufany',
					'badge_type'  => 'featured',
					'specialties' => [ 'Instalacje', 'Fotowoltaika', 'Automatyka' ],
					'initials'    => 'PN',
					'url'         => $site_url . 'specjalisci/elektryk-wroclaw/',
				],
			];
			foreach ( $specialists as $sp ) :
			?>
			<article class="v6-spcard" aria-label="<?php echo esc_attr( $sp['name'] . ' — ' . $sp['trade'] ); ?>">
				<div class="v6-spcard__top">
					<div class="v6-spcard__avatar" aria-hidden="true">
						<?php echo esc_html( $sp['initials'] ); ?>
						<?php if ( $sp['verified'] ) : ?>
						<span class="v6-spcard__verified-dot" aria-label="Zweryfikowany" title="Zweryfikowany specjalista"></span>
						<?php endif; ?>
					</div>
					<div class="v6-spcard__info">
						<div class="v6-spcard__name"><?php echo esc_html( $sp['name'] ); ?></div>
						<div class="v6-spcard__trade"><?php echo esc_html( $sp['trade'] ); ?> · <?php echo esc_html( $sp['city'] ); ?></div>
					</div>
					<div class="v6-badge v6-badge--<?php echo esc_attr( $sp['badge_type'] ); ?>"><?php echo esc_html( $sp['badge'] ); ?></div>
				</div>

				<div class="v6-spcard__rating" aria-label="Ocena <?php echo esc_attr( $sp['rating'] ); ?> na 5 (<?php echo esc_attr( $sp['reviews'] ); ?> opinii)">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="#F59E0B" stroke="none" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
					<strong><?php echo esc_html( $sp['rating'] ); ?></strong>
					<span class="v6-spcard__reviews">(<?php echo esc_html( $sp['reviews'] ); ?> opinii)</span>
				</div>

				<div class="v6-spcard__specialties" aria-label="Specjalizacje">
					<?php foreach ( $sp['specialties'] as $spec ) : ?>
					<span class="v6-pill v6-pill--sm"><?php echo esc_html( $spec ); ?></span>
					<?php endforeach; ?>
				</div>

				<div class="v6-spcard__response">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
					Odpowiedź <?php echo esc_html( $sp['response'] ); ?>
				</div>

				<div class="v6-spcard__actions">
					<a href="<?php echo esc_url( $sp['url'] ); ?>" class="v6-btn v6-btn--primary v6-btn--sm">
						Wyślij zapytanie
					</a>
					<a href="<?php echo esc_url( $sp['url'] ); ?>" class="v6-btn v6-btn--ghost v6-btn--sm">
						Profil
					</a>
				</div>
			</article>
			<?php endforeach; ?>
		</div>

	</div>
</section>


<!-- ══════════════════════════════════════════════════════════════════
     9. LOCAL HUBS
     ══════════════════════════════════════════════════════════════════ -->
<section class="v6-section v6-hubs-section" id="lokalne-huby" aria-label="Lokalne huby specjalistów">
	<div class="v6-container">

		<div class="v6-section__header">
			<div>
				<div class="v6-tag">Lokalne Huby</div>
				<h2 class="v6-section__title">Specjaliści w Twoim mieście</h2>
				<p class="v6-section__sub">Dedykowane platformy dla każdej branży i każdego regionu.</p>
			</div>
		</div>

		<div class="v6-hubs-grid">
			<?php
			$hubs = [
				[
					'icon'  => '🔧',
					'name'  => 'hydraulik.pt24.pro',
					'desc'  => 'Hydraulicy, instalatorzy, awarie wodociągowe',
					'url'   => 'https://hydraulik.pt24.pro/',
					'count' => '340 specjalistów',
					'new'   => false,
				],
				[
					'icon'  => '⚡',
					'name'  => 'elektryk.pt24.pro',
					'desc'  => 'Elektrycy, instalacje, fotowoltaika, automatyka',
					'url'   => 'https://elektryk.pt24.pro/',
					'count' => '280 specjalistów',
					'new'   => true,
				],
				[
					'icon'  => '🚗',
					'name'  => 'mechanik.pt24.pro',
					'desc'  => 'Mechanicy samochodowi, serwisy, diagnostyka',
					'url'   => 'https://mechanik.pt24.pro/',
					'count' => '415 specjalistów',
					'new'   => false,
				],
				[
					'icon'  => '⚖️',
					'name'  => 'prawnik.pt24.pro',
					'desc'  => 'Prawnicy, kancelarie, porady prawne',
					'url'   => 'https://prawnik.pt24.pro/',
					'count' => '195 specjalistów',
					'new'   => true,
				],
			];
			foreach ( $hubs as $hub ) :
			?>
			<a href="<?php echo esc_url( $hub['url'] ); ?>" class="v6-hubcard" aria-label="Hub: <?php echo esc_attr( $hub['name'] ); ?>" rel="noopener">
				<?php if ( $hub['new'] ) : ?>
				<span class="v6-hubcard__new" aria-label="Nowy hub">Nowy</span>
				<?php endif; ?>
				<span class="v6-hubcard__icon" aria-hidden="true"><?php echo $hub['icon']; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span class="v6-hubcard__name"><?php echo esc_html( $hub['name'] ); ?></span>
				<span class="v6-hubcard__desc"><?php echo esc_html( $hub['desc'] ); ?></span>
				<span class="v6-hubcard__count"><?php echo esc_html( $hub['count'] ); ?></span>
			</a>
			<?php endforeach; ?>
		</div>

	</div>
</section>


<!-- ══════════════════════════════════════════════════════════════════
     10. REVENUE CTA
     ══════════════════════════════════════════════════════════════════ -->
<section class="v6-section v6-cta-section" id="dla-specjalistow" aria-label="CTA dla specjalistów">
	<div class="v6-container">

		<div class="v6-cta-grid">

			<!-- CTA A — for specialists -->
			<div class="v6-ctabox v6-ctabox--specialist">
				<div class="v6-ctabox__orb" aria-hidden="true"></div>
				<div class="v6-ctabox__icon" aria-hidden="true">
					<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
				</div>
				<h3 class="v6-ctabox__title">Jesteś specjalistą?</h3>
				<p class="v6-ctabox__desc">Dołącz do platformy. Otrzymuj zapytania od klientów w Twoim mieście. Buduj reputację opartą na danych.</p>
				<ul class="v6-ctabox__list">
					<li>Darmowe konto na start</li>
					<li>Certyfikat weryfikacji</li>
					<li>Panel zarządzania leadami</li>
				</ul>
				<a href="<?php echo esc_url( $site_url . 'dolacz-jako-specjalista/' ); ?>" class="v6-btn v6-btn--primary">
					Dołącz bezpłatnie
				</a>
			</div>

			<!-- CTA B — for businesses -->
			<div class="v6-ctabox v6-ctabox--saas">
				<div class="v6-ctabox__orb" aria-hidden="true"></div>
				<div class="v6-ctabox__icon" aria-hidden="true">
					<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/><path d="M7 8h2v5H7zM11 10h2v3h-2zM15 6h2v7h-2z"/></svg>
				</div>
				<h3 class="v6-ctabox__title">Chcesz własny hub?</h3>
				<p class="v6-ctabox__desc">Uruchom dedykowaną platformę dla swojej branży lub regionu. Gotowy silnik decyzyjny pod Twoją markę.</p>
				<ul class="v6-ctabox__list">
					<li>Dedykowana subdomena</li>
					<li>Ranking + AI Advisor</li>
					<li>White-label platform</li>
				</ul>
				<a href="<?php echo esc_url( $site_url . 'dla-biznesu/' ); ?>" class="v6-btn v6-btn--accent">
					Zapytaj o licencję
				</a>
			</div>

		</div><!-- /.v6-cta-grid -->
	</div>
</section>


<!-- Sticky mobile CTA -->
<div class="v6-sticky-cta" id="v6-sticky-cta" aria-label="Szybki dostęp" hidden>
	<a href="#hero" class="v6-btn v6-btn--primary v6-btn--sm">
		<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
		Szukaj
	</a>
	<a href="#ai-advisor" class="v6-btn v6-btn--ghost v6-btn--sm">
		AI Doradca
	</a>
</div>

<!-- V6 JS -->
<script>
window.PBV6Config = {
	restBase: <?php echo wp_json_encode( $rest_base ); ?>,
	nonce:    <?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?>,
	siteUrl:  <?php echo wp_json_encode( $site_url ); ?>,
};
</script>
<script src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/js/poradnik-v6.js' ); ?>?v=6.0.0" defer></script>

<?php wp_footer(); ?>
</body>
</html>

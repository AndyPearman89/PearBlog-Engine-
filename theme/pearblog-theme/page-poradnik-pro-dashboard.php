<?php
/**
 * Template Name: Poradnik.PRO - Dashboard eksperta
 * Description: Kokpit eksperta Poradnik.PRO ze statystykami i aktywnością.
 *
 * @package PearBlog
 */

defined( 'ABSPATH' ) || exit;
require_once get_template_directory() . '/inc/poradnik-pro-shared.php';

$pp_dashboard_stats = array(
	array(
		'label'  => 'Leady',
		'value'  => '47',
		'detail' => 'w tym miesiącu',
	),
	array(
		'label'  => 'Odpowiedzi',
		'value'  => '312',
		'detail' => 'łącznie',
	),
	array(
		'label'  => 'Średnia ocena',
		'value'  => '4.9/5',
		'detail' => 'na podstawie 128 opinii',
	),
	array(
		'label'  => 'Wyświetlenia profilu',
		'value'  => '2,340',
		'detail' => 'w tym miesiącu',
	),
);

$pp_recent_activity = array(
	array(
		'date'   => '21.06.2026',
		'action' => 'Odpowiedź na pytanie o wybór pompy ciepła',
		'type'   => 'Odpowiedź',
		'status' => 'Opublikowano',
	),
	array(
		'date'   => '20.06.2026',
		'action' => 'Nowy lead od klienta z Warszawy',
		'type'   => 'Nowy lead',
		'status' => 'W trakcie kontaktu',
	),
	array(
		'date'   => '19.06.2026',
		'action' => 'Pytanie o koszt instalacji fotowoltaiki',
		'type'   => 'Pytanie',
		'status' => 'Oczekuje na odpowiedź',
	),
	array(
		'date'   => '18.06.2026',
		'action' => 'Odpowiedź do porównania kredytów inwestycyjnych',
		'type'   => 'Odpowiedź',
		'status' => 'Opublikowano',
	),
	array(
		'date'   => '17.06.2026',
		'action' => 'Nowy lead dotyczący audytu energetycznego',
		'type'   => 'Nowy lead',
		'status' => 'Przypisano do oddzwonienia',
	),
);

$pp_performance_cards = array(
	array(
		'label' => 'Skuteczność odpowiedzi',
		'value' => '92%',
		'note'  => 'Średni czas publikacji poniżej 2 godzin.',
	),
	array(
		'label' => 'Konwersja leadów',
		'value' => '31%',
		'note'  => '14 z 47 leadów przeszło do etapu konsultacji.',
	),
	array(
		'label' => 'CTR profilu',
		'value' => '18.4%',
		'note'  => 'Wzrost o 2.1 pp względem poprzedniego miesiąca.',
	),
);

$pp_dashboard_tasks = array(
	'Zaktualizuj opis usług dla kategorii Energia i Budownictwo.',
	'Odpowiedz na 3 oczekujące pytania z sekcji porównań.',
	'Potwierdź terminy konsultacji dla leadów z ostatnich 48 godzin.',
);
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
		.dashboard-page {
			padding-bottom: 64px;
		}
		.dashboard-intro {
			padding: 34px 0 0;
		}
		.dashboard-intro-card {
			background: #fff;
			border: 1px solid var(--gray-200);
			border-radius: var(--radius-xl);
			padding: 28px;
			box-shadow: var(--shadow-sm);
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 24px;
			margin-top: -24px;
		}
		.dashboard-intro-copy h2 {
			font-size: 24px;
			font-weight: 800;
			color: var(--gray-900);
			margin-bottom: 8px;
		}
		.dashboard-intro-copy p {
			color: var(--gray-600);
			max-width: 620px;
		}
		.dashboard-badge {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			padding: 10px 16px;
			border-radius: 999px;
			background: rgba(16, 185, 129, 0.12);
			color: var(--green-accent);
			font-size: 13px;
			font-weight: 700;
			white-space: nowrap;
		}
		.stats-grid {
			display: grid;
			grid-template-columns: repeat(4, minmax(0, 1fr));
			gap: 18px;
			padding: 30px 0;
		}
		.stat-card,
		.content-panel,
		.performance-card,
		.tasks-panel {
			background: #fff;
			border: 1px solid var(--gray-200);
			border-radius: var(--radius-lg);
			box-shadow: var(--shadow-sm);
		}
		.stat-card {
			padding: 24px;
		}
		.stat-label {
			display: block;
			font-size: 13px;
			font-weight: 700;
			color: var(--gray-500);
			margin-bottom: 10px;
		}
		.stat-value {
			display: block;
			font-size: 34px;
			line-height: 1;
			font-weight: 800;
			color: var(--gray-900);
			margin-bottom: 8px;
		}
		.stat-detail {
			font-size: 14px;
			color: var(--gray-600);
		}
		.dashboard-grid {
			display: grid;
			grid-template-columns: minmax(0, 1.65fr) minmax(300px, 0.95fr);
			gap: 24px;
		}
		.content-panel,
		.tasks-panel {
			padding: 24px;
		}
		.panel-heading {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 16px;
			margin-bottom: 18px;
		}
		.panel-heading h2,
		.tasks-panel h2 {
			font-size: 22px;
			font-weight: 800;
			color: var(--gray-900);
		}
		.panel-heading p,
		.tasks-panel p {
			font-size: 14px;
			color: var(--gray-500);
		}
		.activity-table {
			width: 100%;
			border-collapse: collapse;
		}
		.activity-table th,
		.activity-table td {
			padding: 14px 0;
			border-bottom: 1px solid var(--gray-200);
			text-align: left;
			vertical-align: top;
		}
		.activity-table th {
			font-size: 12px;
			font-weight: 800;
			letter-spacing: 0.04em;
			text-transform: uppercase;
			color: var(--gray-500);
		}
		.activity-table td {
			font-size: 14px;
			color: var(--gray-700);
		}
		.activity-table tbody tr:last-child td {
			border-bottom: none;
		}
		.activity-type {
			display: inline-flex;
			padding: 5px 10px;
			border-radius: 999px;
			background: rgba(59, 130, 246, 0.1);
			color: var(--blue-accent);
			font-size: 12px;
			font-weight: 700;
		}
		.activity-status {
			color: var(--purple-primary);
			font-weight: 700;
		}
		.performance-section {
			margin-top: 24px;
		}
		.performance-grid {
			display: grid;
			gap: 16px;
		}
		.performance-card {
			padding: 20px;
		}
		.performance-card h3 {
			font-size: 15px;
			font-weight: 700;
			color: var(--gray-700);
			margin-bottom: 12px;
		}
		.performance-value {
			display: block;
			font-size: 28px;
			font-weight: 800;
			color: var(--gray-900);
			margin-bottom: 8px;
		}
		.performance-card p {
			font-size: 14px;
			color: var(--gray-600);
		}
		.tasks-list {
			display: grid;
			gap: 12px;
			margin-top: 18px;
		}
		.task-item {
			display: flex;
			gap: 12px;
			padding: 14px 16px;
			border-radius: var(--radius-md);
			background: var(--gray-50);
			color: var(--gray-700);
			font-size: 14px;
		}
		.task-bullet {
			width: 24px;
			height: 24px;
			border-radius: 50%;
			background: rgba(108, 43, 217, 0.12);
			color: var(--purple-primary);
			display: inline-flex;
			align-items: center;
			justify-content: center;
			font-size: 12px;
			font-weight: 800;
			flex-shrink: 0;
		}
		@media (max-width: 1024px) {
			.stats-grid {
				grid-template-columns: repeat(2, minmax(0, 1fr));
			}
			.dashboard-grid {
				grid-template-columns: 1fr;
			}
		}
		@media (max-width: 768px) {
			.dashboard-intro-card {
				flex-direction: column;
				align-items: flex-start;
				margin-top: 0;
			}
			.stats-grid {
				grid-template-columns: 1fr;
			}
			.activity-table,
			.activity-table thead,
			.activity-table tbody,
			.activity-table tr,
			.activity-table th,
			.activity-table td {
				display: block;
			}
			.activity-table thead {
				display: none;
			}
			.activity-table tr {
				padding: 16px 0;
				border-bottom: 1px solid var(--gray-200);
			}
			.activity-table td {
				padding: 4px 0;
				border: none;
			}
		}
	</style>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'poradnik-pro-dashboard-page' ); ?>>
<?php wp_body_open(); pp_pro_header( 'dashboard' ); ?>
<section class="page-hero">
	<div class="container">
		<div class="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Strona główna</a>
			<span class="sep">/</span>
			<span>Dashboard eksperta</span>
		</div>
		<h1>Dashboard eksperta</h1>
		<p>Monitoruj napływ leadów, skuteczność odpowiedzi i widoczność swojego profilu w jednym miejscu.</p>
	</div>
</section>
<div class="dashboard-page">
	<div class="container">
		<section class="dashboard-intro">
			<div class="dashboard-intro-card">
				<div class="dashboard-intro-copy">
					<h2>Dobry miesiąc dla Twojego profilu</h2>
					<p>W czerwcu profil wygenerował więcej zapytań niż w maju, a użytkownicy najczęściej pytają o konsultacje energetyczne, wyceny inwestycji i szybkie odpowiedzi na pytania prawne.</p>
				</div>
				<div class="dashboard-badge">Status profilu: zweryfikowany</div>
			</div>
		</section>
		<section class="stats-grid">
			<?php foreach ( $pp_dashboard_stats as $stat ) : ?>
				<article class="stat-card">
					<span class="stat-label"><?php echo esc_html( $stat['label'] ); ?></span>
					<span class="stat-value"><?php echo esc_html( $stat['value'] ); ?></span>
					<p class="stat-detail"><?php echo esc_html( $stat['detail'] ); ?></p>
				</article>
			<?php endforeach; ?>
		</section>
		<section class="dashboard-grid">
			<div>
				<article class="content-panel">
					<div class="panel-heading">
						<div>
							<h2>Ostatnia aktywność</h2>
							<p>Przegląd najnowszych działań i zapytań od klientów.</p>
						</div>
					</div>
					<table class="activity-table">
						<thead>
							<tr>
								<th>Data</th>
								<th>Akcja</th>
								<th>Typ</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $pp_recent_activity as $activity ) : ?>
								<tr>
									<td><?php echo esc_html( $activity['date'] ); ?></td>
									<td><?php echo esc_html( $activity['action'] ); ?></td>
									<td><span class="activity-type"><?php echo esc_html( $activity['type'] ); ?></span></td>
									<td><span class="activity-status"><?php echo esc_html( $activity['status'] ); ?></span></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</article>
				<article class="content-panel performance-section">
					<div class="panel-heading">
						<div>
							<h2>Wyniki i skuteczność</h2>
							<p>Najważniejsze wskaźniki pomagające ocenić efektywność profilu.</p>
						</div>
					</div>
					<div class="performance-grid">
						<?php foreach ( $pp_performance_cards as $card ) : ?>
							<div class="performance-card">
								<h3><?php echo esc_html( $card['label'] ); ?></h3>
								<span class="performance-value"><?php echo esc_html( $card['value'] ); ?></span>
								<p><?php echo esc_html( $card['note'] ); ?></p>
							</div>
						<?php endforeach; ?>
					</div>
				</article>
			</div>
			<aside class="tasks-panel">
				<h2>Priorytety na dziś</h2>
				<p>Krótka lista działań, które pomogą utrzymać wysoką widoczność i szybko reagować na leady.</p>
				<div class="tasks-list">
					<?php foreach ( $pp_dashboard_tasks as $index => $task ) : ?>
						<div class="task-item">
							<span class="task-bullet"><?php echo esc_html( (string) ( $index + 1 ) ); ?></span>
							<span><?php echo esc_html( $task ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</aside>
		</section>
	</div>
</div>
<?php pp_pro_footer(); wp_footer(); ?>
</body>
</html>

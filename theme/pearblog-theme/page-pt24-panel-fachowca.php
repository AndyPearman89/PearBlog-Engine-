<?php
/**
 * PT24.PRO — Professional dashboard (/panel-fachowca/).
 *
 * Displays real lead/stats data for the logged-in contractor from the database.
 * If not logged in, shows a prompt to register/login.
 *
 * @package PearBlog
 * @subpackage PT24
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

pearblog_render_header();

$is_logged_in  = is_user_logged_in();
$current_email = $is_logged_in ? wp_get_current_user()->user_email : '';

global $wpdb;
$contractors_table = $wpdb->prefix . 'pt24_contractors';
$leads_table       = $wpdb->prefix . 'pt24_leads';
$stats_table       = $wpdb->prefix . 'pt24_business_stats';

// Try to find contractor profile.
$contractor = null;
if ( $current_email ) {
	$contractor = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$contractors_table} WHERE email = %s LIMIT 1",
		$current_email
	) );
}
?>
<main id="main" class="pb-main pt24-landing" role="main">

	<section class="pt24-hero">
		<div class="pb-container">
			<span class="pt24-hero__badge">Panel fachowca</span>
			<h1 class="pt24-hero__title">
				<?php if ( $contractor ) : ?>
					Witaj, <?php echo esc_html( $contractor->name ); ?>!
				<?php else : ?>
					Panel fachowca
				<?php endif; ?>
			</h1>
			<p class="pt24-hero__lead">Zarządzaj leadami, statystykami i profilem firmy w jednym miejscu.</p>
		</div>
	</section>

	<div class="pb-container pt24-page">

		<?php if ( ! $is_logged_in ) : ?>
		<section class="pt24-section">
			<h2>Zaloguj się</h2>
			<p>Aby zobaczyć swój panel, musisz być zalogowany.</p>
			<p>
				<a class="pt24-btn pt24-btn--primary" href="<?php echo esc_url( wp_login_url( home_url( '/panel-fachowca/' ) ) ); ?>">Zaloguj się</a>
				<a class="pt24-btn pt24-btn--ghost" href="<?php echo esc_url( home_url( '/dodaj-firme/' ) ); ?>">Zarejestruj firmę</a>
			</p>
		</section>

		<?php elseif ( ! $contractor ) : ?>
		<section class="pt24-section">
			<h2>Brak profilu firmy</h2>
			<p>Nie znaleziono profilu fachowca powiązanego z Twoim adresem email (<strong><?php echo esc_html( $current_email ); ?></strong>).</p>
			<p><a class="pt24-btn pt24-btn--primary" href="<?php echo esc_url( home_url( '/dodaj-firme/' ) ); ?>">Dodaj swoją firmę</a></p>
		</section>

		<?php else :
			$contractor_id = (int) $contractor->id;

			// Get assigned leads.
			$assigned_leads = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$leads_table} WHERE assigned_contractor_id = %d ORDER BY created_at DESC LIMIT 20",
				$contractor_id
			) );

			$total_assigned    = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$leads_table} WHERE assigned_contractor_id = %d",
				$contractor_id
			) );
			$new_leads         = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$leads_table} WHERE assigned_contractor_id = %d AND status = 'NEW'",
				$contractor_id
			) );

			// Get business stats (profile views, clicks) for linked firm post.
			$firm_post = get_posts( [
				'post_type'      => 'pt24_firm',
				'post_status'    => 'publish',
				'meta_key'       => 'pt24_firm_phone',
				'meta_value'     => $contractor->phone,
				'posts_per_page' => 1,
			] );
			$firm_id = ! empty( $firm_post ) ? (int) $firm_post[0]->ID : 0;

			$month_views  = 0;
			$month_clicks = 0;
			if ( $firm_id ) {
				$month_start = gmdate( 'Y-m-01' );
				$row = $wpdb->get_row( $wpdb->prepare(
					"SELECT COALESCE(SUM(profile_views), 0) as views, COALESCE(SUM(phone_clicks + email_clicks + website_clicks), 0) as clicks FROM {$stats_table} WHERE business_id = %d AND stat_date >= %s",
					$firm_id,
					$month_start
				) );
				if ( $row ) {
					$month_views  = (int) $row->views;
					$month_clicks = (int) $row->clicks;
				}
			}
		?>
		<section class="pt24-section">
			<h2>Podsumowanie</h2>
			<div class="pt24-hero__trust">
				<span class="pt24-hero__trust-item">📋 <strong><?php echo (int) $total_assigned; ?></strong> przypisanych leadów</span>
				<span class="pt24-hero__trust-item">🆕 <strong><?php echo (int) $new_leads; ?></strong> nowych</span>
				<span class="pt24-hero__trust-item">👁 <strong><?php echo (int) $month_views; ?></strong> wyświetleń (miesiąc)</span>
				<span class="pt24-hero__trust-item">📞 <strong><?php echo (int) $month_clicks; ?></strong> kliknięć kontaktowych</span>
			</div>
			<p style="margin-top:1rem;">
				<strong>Pakiet:</strong> <?php echo esc_html( $contractor->package_type ); ?> ·
				<strong>Ocena:</strong> ★ <?php echo esc_html( number_format( (float) $contractor->rating, 1, ',', '' ) ); ?> ·
				<strong>Response rate:</strong> <?php echo esc_html( number_format( (float) $contractor->response_rate * 100, 0 ) ); ?>%
			</p>
		</section>

		<section class="pt24-section">
			<h2>Ostatnie leady</h2>
			<?php if ( ! empty( $assigned_leads ) ) : ?>
			<table class="pt24-table" style="width:100%;border-collapse:collapse;">
				<thead>
					<tr>
						<th style="text-align:left;padding:8px;border-bottom:2px solid #e2e8f0;">Kategoria</th>
						<th style="text-align:left;padding:8px;border-bottom:2px solid #e2e8f0;">Lokalizacja</th>
						<th style="text-align:left;padding:8px;border-bottom:2px solid #e2e8f0;">Status</th>
						<th style="text-align:left;padding:8px;border-bottom:2px solid #e2e8f0;">Data</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $assigned_leads as $lead ) : ?>
					<tr>
						<td style="padding:8px;border-bottom:1px solid #f1f5f9;"><?php echo esc_html( $lead->category ); ?></td>
						<td style="padding:8px;border-bottom:1px solid #f1f5f9;"><?php echo esc_html( $lead->location ); ?></td>
						<td style="padding:8px;border-bottom:1px solid #f1f5f9;"><?php echo esc_html( $lead->status ); ?></td>
						<td style="padding:8px;border-bottom:1px solid #f1f5f9;"><?php echo esc_html( gmdate( 'd.m.Y', (int) $lead->created_at ) ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php else : ?>
				<p>Brak przypisanych leadów. Nowe zapytania pojawią się tu automatycznie.</p>
			<?php endif; ?>
		</section>

		<?php if ( $firm_id ) : ?>
		<section class="pt24-section">
			<h2>Twój profil</h2>
			<p><a class="pt24-btn pt24-btn--ghost" href="<?php echo esc_url( get_permalink( $firm_id ) ); ?>">Zobacz profil firmy</a></p>
		</section>
		<?php endif; ?>

		<?php endif; ?>

	</div>
</main>
<?php
pearblog_render_footer();

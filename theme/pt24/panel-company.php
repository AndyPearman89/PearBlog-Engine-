<?php
/**
 * PT24 company panel page.
 *
 * Displays real lead/stats data for the logged-in company from the database.
 *
 * @package PT24
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

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

$new_leads     = 0;
$conversion    = 0;
$monthly_revenue = 0;

if ( $contractor ) {
    $contractor_id = (int) $contractor->id;
    $new_leads = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$leads_table} WHERE assigned_contractor_id = %d AND status = 'NEW'",
        $contractor_id
    ) );
    $total_assigned = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$leads_table} WHERE assigned_contractor_id = %d",
        $contractor_id
    ) );
    $completed = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$leads_table} WHERE assigned_contractor_id = %d AND status IN ('COMPLETED', 'CLOSED')",
        $contractor_id
    ) );
    $conversion = $total_assigned > 0 ? round( ( $completed / $total_assigned ) * 100, 1 ) : 0;
}
?>

<section class="pt24-panel-page">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <div class="pt24-panel-shell">
            <aside class="pt24-panel-sidebar">
                <h1 class="pt24-panel-title">Panel firmy</h1>
                <p class="pt24-panel-path">/panel-firmy</p>
                <nav class="pt24-panel-nav">
                    <a class="is-active" href="<?php echo esc_url( home_url( '/panel-firmy/' ) ); ?>">Dashboard</a>
                    <a href="<?php echo esc_url( home_url( '/panel-firmy/' ) ); ?>">Leady</a>
                    <a href="<?php echo esc_url( home_url( '/panel-firmy/' ) ); ?>">Odpowiedzi</a>
                    <a href="<?php echo esc_url( home_url( '/panel-firmy/' ) ); ?>">Kalendarz</a>
                    <a href="<?php echo esc_url( home_url( '/panel-firmy/' ) ); ?>">Opinie</a>
                    <a href="<?php echo esc_url( home_url( '/panel-firmy/' ) ); ?>">Statystyki</a>
                    <a href="<?php echo esc_url( home_url( '/panel-firmy/' ) ); ?>">Rozliczenia</a>
                    <a href="<?php echo esc_url( home_url( '/panel-firmy/' ) ); ?>">Profil firmy</a>
                </nav>
            </aside>
            <main class="pt24-panel-content">
                <?php if ( ! $is_logged_in ) : ?>
                <div class="pt24-panel-card">
                    <h2>Zaloguj się</h2>
                    <p>Aby zarządzać firmą, musisz być zalogowany.</p>
                    <p><a href="<?php echo esc_url( wp_login_url( home_url( '/panel-firmy/' ) ) ); ?>">Zaloguj się</a></p>
                </div>
                <?php elseif ( ! $contractor ) : ?>
                <div class="pt24-panel-card">
                    <h2>Brak profilu firmy</h2>
                    <p>Nie znaleziono profilu firmowego powiązanego z adresem <strong><?php echo esc_html( $current_email ); ?></strong>.</p>
                    <p><a href="<?php echo esc_url( home_url( '/dodaj-firme/' ) ); ?>">Dodaj swoją firmę</a></p>
                </div>
                <?php else : ?>
                <div class="pt24-panel-card">
                    <h2>Dashboard firmy</h2>
                    <p>Zarządzaj leadami, odpowiedziami i kalendarzem realizacji zleceń w jednym miejscu.</p>
                </div>
                <div class="pt24-panel-grid">
                    <div class="pt24-panel-mini"><strong><?php echo (int) $new_leads; ?></strong><span>Nowych leadów</span></div>
                    <div class="pt24-panel-mini"><strong><?php echo esc_html( $conversion ); ?>%</strong><span>Konwersja</span></div>
                    <div class="pt24-panel-mini"><strong><?php echo (int) $total_assigned; ?></strong><span>Wszystkich leadów</span></div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</section>

<?php get_footer(); ?>

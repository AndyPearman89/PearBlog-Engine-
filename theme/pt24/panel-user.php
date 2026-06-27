<?php
/**
 * PT24 user panel page.
 *
 * Displays real data for the logged-in user from the leads database.
 *
 * @package PT24
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

$is_logged_in = is_user_logged_in();
$user_email   = $is_logged_in ? wp_get_current_user()->user_email : '';

global $wpdb;
$leads_table = $wpdb->prefix . 'pt24_leads';

// Count user's leads by email (if available in metadata).
$my_inquiries   = 0;
$my_offers      = 0;
$my_messages    = 0;

if ( $user_email ) {
    $my_inquiries = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$leads_table} WHERE metadata LIKE %s",
        '%' . $wpdb->esc_like( $user_email ) . '%'
    ) );
    $my_offers = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$leads_table} WHERE metadata LIKE %s AND status != 'NEW'",
        '%' . $wpdb->esc_like( $user_email ) . '%'
    ) );
}
?>

<section class="pt24-panel-page">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <div class="pt24-panel-shell">
            <aside class="pt24-panel-sidebar">
                <h1 class="pt24-panel-title">Panel użytkownika</h1>
                <p class="pt24-panel-path">/panel</p>
                <nav class="pt24-panel-nav">
                    <a class="is-active" href="<?php echo esc_url( home_url( '/panel/' ) ); ?>">Moje zapytania</a>
                    <a href="<?php echo esc_url( home_url( '/panel/' ) ); ?>">Otrzymane oferty</a>
                    <a href="<?php echo esc_url( home_url( '/panel/' ) ); ?>">Wiadomości</a>
                    <a href="<?php echo esc_url( home_url( '/panel/' ) ); ?>">Powiadomienia</a>
                    <a href="<?php echo esc_url( home_url( '/panel/' ) ); ?>">Profil</a>
                </nav>
            </aside>
            <main class="pt24-panel-content">
                <?php if ( ! $is_logged_in ) : ?>
                <div class="pt24-panel-card">
                    <h2>Zaloguj się</h2>
                    <p>Aby zobaczyć swoje zapytania i oferty, musisz być zalogowany.</p>
                    <p><a href="<?php echo esc_url( wp_login_url( home_url( '/panel/' ) ) ); ?>">Zaloguj się</a></p>
                </div>
                <?php else : ?>
                <div class="pt24-panel-card">
                    <h2>Moje zapytania</h2>
                    <p>Tu znajdziesz wszystkie swoje zapytania, statusy odpowiedzi i szybkie akcje.</p>
                </div>
                <div class="pt24-panel-grid">
                    <div class="pt24-panel-mini"><strong><?php echo (int) $my_inquiries; ?></strong><span>Aktywnych zapytań</span></div>
                    <div class="pt24-panel-mini"><strong><?php echo (int) $my_offers; ?></strong><span>Otrzymanych ofert</span></div>
                    <div class="pt24-panel-mini"><strong><?php echo (int) $my_messages; ?></strong><span>Nowych wiadomości</span></div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</section>

<?php get_footer(); ?>

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
$total_assigned = 0;
$completed = 0;

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

$billing_notice = isset($_GET['billing']) ? sanitize_key((string) $_GET['billing']) : '';
$billing_messages = [
    'plan-updated' => 'Plan abonamentowy zostal zaktualizowany.',
    'credits-added' => 'Kredyty leadowe zostaly dodane do konta.',
    'invalid-plan' => 'Wybrany plan jest nieprawidlowy.',
    'invalid-pack' => 'Wybrany pakiet leadow jest nieprawidlowy.',
    'error' => 'Wystapil blad operacji. Sprobuj ponownie.',
];

$plans = function_exists('pt24_get_subscription_plans') ? pt24_get_subscription_plans() : [];
$lead_packages = function_exists('pt24_get_lead_credit_packages') ? pt24_get_lead_credit_packages() : [];
$monetization_state = $is_logged_in && function_exists('pt24_get_company_monetization_state')
    ? pt24_get_company_monetization_state(get_current_user_id())
    : ['plan' => 'free', 'credits' => 0, 'included' => 0];

$current_plan = isset($monetization_state['plan']) ? (string) $monetization_state['plan'] : 'free';
$current_credits = isset($monetization_state['credits']) ? (int) $monetization_state['credits'] : 0;
$included_leads = isset($monetization_state['included']) ? (int) $monetization_state['included'] : 0;
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

                    <?php if ($billing_notice !== '' && isset($billing_messages[$billing_notice])) : ?>
                        <p class="pt24-company-success"><?php echo esc_html($billing_messages[$billing_notice]); ?></p>
                    <?php endif; ?>
                </div>
                <div class="pt24-panel-grid">
                    <div class="pt24-panel-mini"><strong><?php echo (int) $new_leads; ?></strong><span>Nowych leadów</span></div>
                    <div class="pt24-panel-mini"><strong><?php echo esc_html( $conversion ); ?>%</strong><span>Konwersja</span></div>
                    <div class="pt24-panel-mini"><strong><?php echo (int) $total_assigned; ?></strong><span>Wszystkich leadów</span></div>
                </div>

                <div class="pt24-panel-card">
                    <h2>Monetyzacja i plan</h2>
                    <div class="pt24-panel-grid">
                        <div class="pt24-panel-mini">
                            <strong><?php echo esc_html(isset($plans[$current_plan]['name']) ? (string) $plans[$current_plan]['name'] : 'Free'); ?></strong>
                            <span>Aktualny plan</span>
                        </div>
                        <div class="pt24-panel-mini">
                            <strong><?php echo (int) $current_credits; ?></strong>
                            <span>Dostepne kredyty leadowe</span>
                        </div>
                        <div class="pt24-panel-mini">
                            <strong><?php echo (int) $included_leads; ?></strong>
                            <span>Leady w abonamencie / miesiac</span>
                        </div>
                    </div>
                </div>

                <div class="pt24-panel-grid pt24-panel-grid--2">
                    <div class="pt24-panel-card">
                        <h2>Zmien plan SaaS</h2>
                        <p>Wybierz plan dopasowany do liczby leadow i skali firmy.</p>

                        <form class="pt24-company-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="pt24_company_change_plan">
                            <?php wp_nonce_field('pt24_company_plan_' . get_current_user_id(), 'pt24_company_plan_nonce'); ?>
                            <label>
                                Plan
                                <select name="plan" required>
                                    <?php foreach ($plans as $plan_key => $plan_data) : ?>
                                        <option value="<?php echo esc_attr((string) $plan_key); ?>" <?php selected($current_plan, (string) $plan_key); ?>>
                                            <?php echo esc_html((string) $plan_data['name']); ?> - <?php echo (int) $plan_data['price']; ?> zl/mies.
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <button type="submit">Zapisz plan</button>
                        </form>
                    </div>

                    <div class="pt24-panel-card">
                        <h2>Dokup leady</h2>
                        <p>Jesli potrzebujesz wiecej zapytan, kup jednorazowy pakiet leadow.</p>

                        <form class="pt24-company-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="pt24_company_buy_lead_pack">
                            <?php wp_nonce_field('pt24_company_pack_' . get_current_user_id(), 'pt24_company_pack_nonce'); ?>
                            <label>
                                Pakiet leadow
                                <select name="pack" required>
                                    <?php foreach ($lead_packages as $pack_key => $pack_data) : ?>
                                        <option value="<?php echo esc_attr((string) $pack_key); ?>">
                                            <?php echo esc_html((string) $pack_data['label']); ?> - <?php echo (int) $pack_data['price']; ?> <?php echo esc_html((string) $pack_data['currency']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <button type="submit">Kup pakiet</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</section>

<?php get_footer(); ?>

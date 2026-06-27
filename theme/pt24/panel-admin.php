<?php
/**
 * PT24 admin panel page.
 *
 * @package PT24
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! current_user_can('manage_options')) {
    if (is_user_logged_in()) {
        wp_safe_redirect(home_url('/'));
    } else {
        wp_safe_redirect(wp_login_url(home_url('/admin/')));
    }
    exit;
}

get_header();
?>

<section class="pt24-panel-page">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <div class="pt24-panel-shell">
            <aside class="pt24-panel-sidebar">
                <h1 class="pt24-panel-title">Panel administratora</h1>
                <p class="pt24-panel-path">/admin</p>
                <nav class="pt24-panel-nav">
                    <a class="is-active" href="/admin">Firmy</a>
                    <a href="/admin">Użytkownicy</a>
                    <a href="/admin">Kategorie</a>
                    <a href="/admin">Miasta</a>
                    <a href="/admin">Leady</a>
                    <a href="/admin">Płatności</a>
                    <a href="/admin">Moderacja opinii</a>
                    <a href="/admin">Reklamy</a>
                    <a href="/admin">Analityka</a>
                    <a href="/admin">Zarządzanie treściami</a>
                </nav>
            </aside>
            <main class="pt24-panel-content">
                <div class="pt24-panel-card">
                    <h2>Centrum zarządzania platformą</h2>
                    <p>Administruj firmami, płatnościami, moderacją i analityką całego marketplace.</p>
                </div>
                <div class="pt24-panel-grid">
                    <div class="pt24-panel-mini"><strong>1 284</strong><span>Firm aktywnych</span></div>
                    <div class="pt24-panel-mini"><strong>219</strong><span>Leadów / 24h</span></div>
                    <div class="pt24-panel-mini"><strong>97%</strong><span>Poziom moderacji SLA</span></div>
                </div>
            </main>
        </div>
    </div>
</section>

<?php get_footer(); ?>

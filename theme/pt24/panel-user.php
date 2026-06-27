<?php
/**
 * PT24 user panel page.
 *
 * @package PT24
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>

<section class="pt24-panel-page">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <div class="pt24-panel-shell">
            <aside class="pt24-panel-sidebar">
                <h1 class="pt24-panel-title">Panel użytkownika</h1>
                <p class="pt24-panel-path">/panel</p>
                <nav class="pt24-panel-nav">
                    <a class="is-active" href="/panel">Moje zapytania</a>
                    <a href="/panel">Otrzymane oferty</a>
                    <a href="/panel">Wiadomości</a>
                    <a href="/panel">Powiadomienia</a>
                    <a href="/panel">Profil</a>
                </nav>
            </aside>
            <main class="pt24-panel-content">
                <div class="pt24-panel-card">
                    <h2>Moje zapytania</h2>
                    <p>Tu znajdziesz wszystkie swoje zapytania, statusy odpowiedzi i szybkie akcje.</p>
                </div>
                <div class="pt24-panel-grid">
                    <div class="pt24-panel-mini"><strong>12</strong><span>Aktywnych zapytań</span></div>
                    <div class="pt24-panel-mini"><strong>34</strong><span>Otrzymanych ofert</span></div>
                    <div class="pt24-panel-mini"><strong>5</strong><span>Nowych wiadomości</span></div>
                </div>
            </main>
        </div>
    </div>
</section>

<?php get_footer(); ?>

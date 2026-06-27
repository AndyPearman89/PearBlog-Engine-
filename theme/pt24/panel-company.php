<?php
/**
 * PT24 company panel page.
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
                <h1 class="pt24-panel-title">Panel firmy</h1>
                <p class="pt24-panel-path">/panel-firmy</p>
                <nav class="pt24-panel-nav">
                    <a class="is-active" href="/panel-firmy">Dashboard</a>
                    <a href="/panel-firmy">Leady</a>
                    <a href="/panel-firmy">Odpowiedzi</a>
                    <a href="/panel-firmy">Kalendarz</a>
                    <a href="/panel-firmy">Opinie</a>
                    <a href="/panel-firmy">Statystyki</a>
                    <a href="/panel-firmy">Rozliczenia</a>
                    <a href="/panel-firmy">Profil firmy</a>
                </nav>
            </aside>
            <main class="pt24-panel-content">
                <div class="pt24-panel-card">
                    <h2>Dashboard firmy</h2>
                    <p>Zarządzaj leadami, odpowiedziami i kalendarzem realizacji zleceń w jednym miejscu.</p>
                </div>
                <div class="pt24-panel-grid">
                    <div class="pt24-panel-mini"><strong>26</strong><span>Nowych leadów</span></div>
                    <div class="pt24-panel-mini"><strong>18.4%</strong><span>Konwersja</span></div>
                    <div class="pt24-panel-mini"><strong>42 800 zł</strong><span>Przychód / mies.</span></div>
                </div>
            </main>
        </div>
    </div>
</section>

<?php get_footer(); ?>

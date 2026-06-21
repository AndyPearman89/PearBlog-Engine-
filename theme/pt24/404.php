<?php
/**
 * 404 Page Template
 *
 * @package PT24
 * @version 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>

<section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 text-center">
    <h1 class="font-display text-6xl font-bold text-slate-900">404</h1>
    <p class="mt-4 text-lg text-slate-500">Strona nie została znaleziona.</p>
    <p class="mt-2 text-sm text-slate-400">Sprawdź adres URL lub wróć na stronę główną.</p>
    <a href="<?php echo esc_url(home_url('/')); ?>" class="mt-8 inline-flex rounded-xl bg-gradient-to-r from-brand-start to-brand-end px-6 py-3 text-sm font-semibold text-white shadow-soft transition hover:shadow-glow">
        Strona główna
    </a>
</section>

<?php get_footer(); ?>

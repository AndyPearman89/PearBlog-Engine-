<?php
/**
 * Theme Header
 *
 * @package PT24
 * @version 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-slate-50 text-slate-900 antialiased font-body'); ?>>
<?php wp_body_open(); ?>

<div class="min-h-screen">
    <header class="sticky top-0 z-50 border-b border-slate-200/60 bg-white/95 backdrop-blur-md">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <!-- Logo -->
            <a href="<?php echo esc_url(home_url('/')); ?>" class="flex items-center gap-2" aria-label="PT24.PRO — strona główna">
                <?php get_template_part('template-parts/logo'); ?>
                <span class="font-display text-xl font-bold tracking-tight">
                    <span class="text-slate-900">PT24</span><span class="bg-gradient-to-r from-brand-start to-brand-end bg-clip-text text-transparent">.PRO</span>
                </span>
            </a>

            <!-- Desktop Nav -->
            <nav class="hidden items-center gap-8 text-sm font-medium text-slate-600 lg:flex">
                <a href="#jak-to-dziala" class="transition hover:text-brand-start">Jak to działa</a>
                <a href="#uslugi" class="transition hover:text-brand-start">Usługi</a>
                <a href="#dla-fachowcow" class="transition hover:text-brand-start">Dla fachowców</a>
                <a href="#opinie" class="transition hover:text-brand-start">Opinie</a>
                <a href="#kontakt" class="transition hover:text-brand-start">Kontakt</a>
            </nav>

            <!-- CTA -->
            <div class="flex items-center gap-3">
                <a href="/dodaj-zlecenie/" class="rounded-xl bg-gradient-to-r from-brand-start to-brand-end px-5 py-2.5 text-sm font-semibold text-white shadow-soft transition hover:shadow-glow">Dodaj zlecenie</a>
            </div>
        </div>

        <!-- Mobile nav -->
        <nav class="border-t border-slate-100 px-4 py-2.5 lg:hidden">
            <div class="flex gap-2 overflow-x-auto whitespace-nowrap text-xs font-semibold text-slate-700">
                <a href="#jak-to-dziala" class="rounded-lg bg-slate-100 px-3 py-1.5">Jak to działa</a>
                <a href="#uslugi" class="rounded-lg bg-slate-100 px-3 py-1.5">Usługi</a>
                <a href="#dla-fachowcow" class="rounded-lg bg-slate-100 px-3 py-1.5">Dla fachowców</a>
                <a href="#opinie" class="rounded-lg bg-slate-100 px-3 py-1.5">Opinie</a>
                <a href="#kontakt" class="rounded-lg bg-slate-100 px-3 py-1.5">Kontakt</a>
            </div>
        </nav>
    </header>

    <main>

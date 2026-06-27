<?php
/**
 * Theme Footer
 *
 * @package PT24
 * @version 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

$powered_logo = content_url('mu-plugins/pearblog-engine/assets/images/pearblog-logo.png');
?>
    </main>

    <footer id="kontakt" class="border-t border-slate-200 bg-white">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:grid-cols-2 sm:px-6 lg:grid-cols-5 lg:px-8">
            <!-- Brand -->
            <div class="lg:col-span-2">
                <div class="flex items-center gap-2">
                    <?php get_template_part('template-parts/logo', null, ['size' => 32]); ?>
                    <span class="font-display text-lg font-bold tracking-tight text-slate-900">PT24.PRO</span>
                </div>
                <p class="mt-3 max-w-xs text-sm text-slate-500">Marketplace usług lokalnych. Łączymy klientów z zweryfikowanymi fachowcami w całej Polsce.</p>
            </div>

            <!-- Links: Usługi -->
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-900">Usługi</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-500">
                    <li><a href="/uslugi/hydraulik/" class="hover:text-slate-900">Hydraulik</a></li>
                    <li><a href="/uslugi/elektryk/" class="hover:text-slate-900">Elektryk</a></li>
                    <li><a href="/uslugi/mechanik/" class="hover:text-slate-900">Mechanik</a></li>
                    <li><a href="/uslugi/klimatyzacja/" class="hover:text-slate-900">Klimatyzacja</a></li>
                    <li><a href="/uslugi/" class="hover:text-slate-900">Wszystkie usługi</a></li>
                </ul>
            </div>

            <!-- Links: Miasta -->
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-900">Miasta</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-500">
                    <li><a href="/warszawa/" class="hover:text-slate-900">Warszawa</a></li>
                    <li><a href="/krakow/" class="hover:text-slate-900">Kraków</a></li>
                    <li><a href="/katowice/" class="hover:text-slate-900">Katowice</a></li>
                    <li><a href="/gliwice/" class="hover:text-slate-900">Gliwice</a></li>
                    <li><a href="/miasta/" class="hover:text-slate-900">Wszystkie miasta</a></li>
                </ul>
            </div>

            <!-- Links: Informacje -->
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-900">Informacje</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-500">
                    <li><a href="/dla-fachowcow/" class="hover:text-slate-900">Dla fachowców</a></li>
                    <li><a href="/panel-firmy/" class="hover:text-slate-900">Panel firmy</a></li>
                    <li><a href="/kontakt/" class="hover:text-slate-900">Kontakt</a></li>
                    <li><a href="/regulamin/" class="hover:text-slate-900">Regulamin</a></li>
                    <li><a href="/polityka-prywatnosci/" class="hover:text-slate-900">Prywatność</a></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-slate-100 py-5 text-center text-xs text-slate-400">
            &copy; <?php echo esc_html(gmdate('Y')); ?> PT24.PRO &mdash; Powered by
            <img
                src="<?php echo esc_url($powered_logo); ?>"
                alt="PearBlog"
                class="inline-block h-4 w-auto align-text-bottom"
                loading="lazy"
                decoding="async">
        </div>
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>

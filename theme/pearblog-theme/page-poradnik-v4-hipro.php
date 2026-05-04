<?php
/**
 * Template Name: Poradnik.pro V4 HI-PRO Content Hub
 *
 * High-conversion content hub with 10 optimized sections
 * Problem-solving focus with practical information
 *
 * @package PearBlog
 * @version 4.1.0
 */

get_header();
?>

<main class="poradnik-v4-hipro">

    <!-- ================================================== -->
    <!-- [1] HERO – CONTENT + INTENT -->
    <!-- ================================================== -->
    <section class="hipro-hero">
        <div class="pb-container">
            <h1 class="hipro-hero__title">
                Sprawdzone poradniki, które rozwiązują realne problemy
            </h1>

            <p class="hipro-hero__subtitle">
                Dowiedz się, co zrobić, ile to kosztuje i kiedy warto skorzystać z fachowca.
            </p>

            <!-- Search -->
            <div class="hipro-hero__search">
                <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                    <input
                        type="search"
                        name="s"
                        class="hipro-search-input"
                        placeholder="Czego szukasz? (np. „auto nie odpala", „remont łazienki koszt")"
                        value="<?php echo get_search_query(); ?>"
                        autocomplete="off"
                    >
                    <button type="submit" class="hipro-search-btn hipro-btn-primary" aria-label="Znajdź poradnik">
                        Znajdź poradnik
                    </button>
                </form>
            </div>

            <!-- CTAs -->
            <div class="hipro-hero__ctas">
                <a href="<?php echo home_url('/poradniki/'); ?>" class="hipro-btn hipro-btn-primary">
                    Znajdź poradnik
                </a>
                <a href="<?php echo home_url('/uslugi/'); ?>" class="hipro-btn hipro-btn-secondary">
                    Przejdź do usług
                </a>
            </div>

            <!-- Trust Signals -->
            <div class="hipro-hero__trust">
                <div class="hipro-trust-item">
                    <svg class="hipro-trust-icon" width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    konkretne odpowiedzi
                </div>
                <div class="hipro-trust-item">
                    <svg class="hipro-trust-icon" width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    aktualne ceny
                </div>
                <div class="hipro-trust-item">
                    <svg class="hipro-trust-icon" width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    sprawdzone rozwiązania
                </div>
            </div>

            <!-- Microcopy -->
            <p class="hipro-hero__microcopy">
                Bez lania wody — tylko praktyczne informacje.
            </p>
        </div>
    </section>

    <!-- ================================================== -->
    <!-- [2] QUICK ANSWER HUB -->
    <!-- ================================================== -->
    <section class="hipro-quick-answers">
        <div class="pb-container">
            <h2 class="hipro-section-title">Najczęstsze problemy – szybkie odpowiedzi</h2>

            <div class="hipro-answer-list">
                <?php
                $quick_answers = [
                    ['title' => 'Auto nie odpala – co zrobić krok po kroku', 'url' => '/auto-nie-odpala/'],
                    ['title' => 'Ile kosztuje remont łazienki w 2026?', 'url' => '/ile-kosztuje-remont-lazienki/'],
                    ['title' => 'Piszczące hamulce – czy można jeździć?', 'url' => '/piszczace-hamulce/'],
                    ['title' => 'Gniazdko nie działa – przyczyny i rozwiązania', 'url' => '/gniazdko-nie-dziala/'],
                ];

                foreach ($quick_answers as $answer):
                ?>
                    <a href="<?php echo esc_url($answer['url']); ?>" class="hipro-answer-item">
                        <span class="hipro-answer-title"><?php echo esc_html($answer['title']); ?></span>
                        <svg class="hipro-answer-arrow" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="hipro-section-cta">
                <a href="<?php echo home_url('/poradniki/'); ?>" class="hipro-btn hipro-btn-outline">
                    Zobacz wszystkie poradniki
                </a>
            </div>
        </div>
    </section>

    <!-- ================================================== -->
    <!-- [3] CATEGORY GRID (TOPICAL AUTHORITY) -->
    <!-- ================================================== -->
    <section class="hipro-categories">
        <div class="pb-container">
            <h2 class="hipro-section-title">Kategorie poradników</h2>

            <div class="hipro-category-grid">
                <?php
                $categories = [
                    ['icon' => '🚗', 'title' => 'Motoryzacja', 'url' => '/kategoria/motoryzacja/'],
                    ['icon' => '🏠', 'title' => 'Dom i remont', 'url' => '/kategoria/dom-remont/'],
                    ['icon' => '⚡', 'title' => 'Instalacje elektryczne', 'url' => '/kategoria/instalacje-elektryczne/'],
                    ['icon' => '🚿', 'title' => 'Hydraulika', 'url' => '/kategoria/hydraulika/'],
                    ['icon' => '❄️', 'title' => 'Klimatyzacja i ogrzewanie', 'url' => '/kategoria/klimatyzacja/'],
                    ['icon' => '🧹', 'title' => 'Utrzymanie domu', 'url' => '/kategoria/utrzymanie-domu/'],
                ];

                foreach ($categories as $category):
                ?>
                    <a href="<?php echo esc_url($category['url']); ?>" class="hipro-category-card">
                        <span class="hipro-category-icon"><?php echo $category['icon']; ?></span>
                        <span class="hipro-category-title"><?php echo esc_html($category['title']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="hipro-section-cta">
                <a href="<?php echo home_url('/kategorie/'); ?>" class="hipro-btn hipro-btn-outline">
                    Przeglądaj kategorie
                </a>
            </div>
        </div>
    </section>

    <!-- ================================================== -->
    <!-- [4] FEATURED ARTICLES (SEO DRIVER) -->
    <!-- ================================================== -->
    <section class="hipro-featured">
        <div class="pb-container">
            <h2 class="hipro-section-title">Najpopularniejsze poradniki</h2>

            <div class="hipro-featured-grid">
                <?php
                // Get popular posts
                $featured_posts = get_posts([
                    'posts_per_page' => 4,
                    'post_status' => 'publish',
                    'orderby' => 'meta_value_num',
                    'meta_key' => 'post_views_count',
                    'order' => 'DESC'
                ]);

                // Fallback articles if no posts
                $featured_articles = [
                    ['title' => 'Ile kosztuje budowa domu w Polsce 2026', 'url' => '/budowa-domu-koszt/'],
                    ['title' => 'Wymiana oleju – kiedy i ile kosztuje', 'url' => '/wymiana-oleju/'],
                    ['title' => 'Zapchany odpływ – jak udrożnić samemu', 'url' => '/zapchany-odplyw/'],
                    ['title' => 'Jak wybrać dobrego mechanika', 'url' => '/dobry-mechanik/'],
                ];

                if (!empty($featured_posts)) {
                    foreach ($featured_posts as $post):
                ?>
                    <article class="hipro-featured-card">
                        <?php if (has_post_thumbnail($post)): ?>
                            <div class="hipro-featured-image">
                                <?php echo get_the_post_thumbnail($post, 'medium'); ?>
                            </div>
                        <?php endif; ?>
                        <h3 class="hipro-featured-title">
                            <a href="<?php echo get_permalink($post); ?>">
                                <?php echo esc_html($post->post_title); ?>
                            </a>
                        </h3>
                        <p class="hipro-featured-excerpt">
                            <?php echo wp_trim_words($post->post_excerpt ?: $post->post_content, 20); ?>
                        </p>
                        <a href="<?php echo get_permalink($post); ?>" class="hipro-featured-link">
                            Czytaj więcej →
                        </a>
                    </article>
                <?php
                    endforeach;
                } else {
                    foreach ($featured_articles as $article):
                ?>
                    <article class="hipro-featured-card">
                        <h3 class="hipro-featured-title">
                            <a href="<?php echo esc_url($article['url']); ?>">
                                <?php echo esc_html($article['title']); ?>
                            </a>
                        </h3>
                        <a href="<?php echo esc_url($article['url']); ?>" class="hipro-featured-link">
                            Czytaj więcej →
                        </a>
                    </article>
                <?php
                    endforeach;
                }
                ?>
            </div>

            <div class="hipro-section-cta">
                <a href="<?php echo home_url('/poradniki/'); ?>" class="hipro-btn hipro-btn-outline">
                    Czytaj więcej
                </a>
            </div>
        </div>
    </section>

    <!-- ================================================== -->
    <!-- [5] COST HUB (HIGH INTENT) -->
    <!-- ================================================== -->
    <section class="hipro-cost-hub">
        <div class="pb-container">
            <h2 class="hipro-section-title">Ile to kosztuje?</h2>

            <div class="hipro-cost-grid">
                <div class="hipro-cost-item">
                    <div class="hipro-cost-label">Remont łazienki</div>
                    <div class="hipro-cost-price">10 000–40 000 zł</div>
                </div>
                <div class="hipro-cost-item">
                    <div class="hipro-cost-label">Wymiana oleju</div>
                    <div class="hipro-cost-price">150–400 zł</div>
                </div>
                <div class="hipro-cost-item">
                    <div class="hipro-cost-label">Hydraulik</div>
                    <div class="hipro-cost-price">od 100 zł</div>
                </div>
            </div>

            <p class="hipro-cost-description">
                Sprawdź realne ceny i porównaj oferty w swojej okolicy.
            </p>

            <div class="hipro-section-cta">
                <a href="<?php echo home_url('/ceny/'); ?>" class="hipro-btn hipro-btn-primary">
                    Sprawdź ceny w Twoim mieście
                </a>
            </div>
        </div>
    </section>

    <!-- ================================================== -->
    <!-- [6] PROBLEM → SOLUTION → LEAD -->
    <!-- ================================================== -->
    <section class="hipro-problem-solution">
        <div class="pb-container">
            <h2 class="hipro-section-title">Masz konkretny problem?</h2>

            <p class="hipro-problem-description">
                Nie musisz zgadywać — pokażemy Ci rozwiązanie i znajdziemy fachowca.
            </p>

            <div class="hipro-problem-ctas">
                <a href="<?php echo home_url('/znajdz-rozwiazanie/'); ?>" class="hipro-btn hipro-btn-primary">
                    Znajdź rozwiązanie
                </a>
                <a href="<?php echo home_url('/wyslij-zapytanie/'); ?>" class="hipro-btn hipro-btn-secondary">
                    Wyślij zapytanie
                </a>
            </div>
        </div>
    </section>

    <!-- ================================================== -->
    <!-- [7] INTERNAL LINKING (TO PT24) -->
    <!-- ================================================== -->
    <section class="hipro-internal-links">
        <div class="pb-container">
            <h2 class="hipro-section-title">Znajdź fachowca w swojej okolicy</h2>

            <div class="hipro-links-grid">
                <?php
                $local_services = [
                    ['title' => 'Mechanik Katowice', 'url' => '/katowice/mechanik/'],
                    ['title' => 'Elektryk Warszawa', 'url' => '/warszawa/elektryk/'],
                    ['title' => 'Hydraulik Kraków', 'url' => '/krakow/hydraulik/'],
                    ['title' => 'Mechanik Wrocław', 'url' => '/wroclaw/mechanik/'],
                    ['title' => 'Elektryk Poznań', 'url' => '/poznan/elektryk/'],
                    ['title' => 'Hydraulik Gdańsk', 'url' => '/gdansk/hydraulik/'],
                ];

                foreach ($local_services as $service):
                ?>
                    <a href="<?php echo esc_url($service['url']); ?>" class="hipro-link-item">
                        <?php echo esc_html($service['title']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="hipro-section-cta">
                <a href="<?php echo home_url('/ranking/'); ?>" class="hipro-btn hipro-btn-outline">
                    Zobacz ranking
                </a>
            </div>
        </div>
    </section>

    <!-- ================================================== -->
    <!-- [8] TRUST BLOCK (AUTHORITY) -->
    <!-- ================================================== -->
    <section class="hipro-trust-block">
        <div class="pb-container">
            <h2 class="hipro-section-title">Dlaczego Poradnik.pro?</h2>

            <div class="hipro-trust-grid">
                <div class="hipro-trust-card">
                    <svg class="hipro-trust-card-icon" width="32" height="32" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <h3 class="hipro-trust-card-title">Praktyczne poradniki</h3>
                    <p class="hipro-trust-card-text">Konkretne instrukcje krok po kroku, bez zbędnej teorii</p>
                </div>

                <div class="hipro-trust-card">
                    <svg class="hipro-trust-card-icon" width="32" height="32" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <h3 class="hipro-trust-card-title">Aktualne ceny</h3>
                    <p class="hipro-trust-card-text">Regularnie aktualizowane cenniki dla 2026 roku</p>
                </div>

                <div class="hipro-trust-card">
                    <svg class="hipro-trust-card-icon" width="32" height="32" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <h3 class="hipro-trust-card-title">Realne rozwiązania</h3>
                    <p class="hipro-trust-card-text">Sprawdzone metody od praktyków, nie teoretyków</p>
                </div>

                <div class="hipro-trust-card">
                    <svg class="hipro-trust-card-icon" width="32" height="32" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <h3 class="hipro-trust-card-title">Połączenie z lokalnymi usługami</h3>
                    <p class="hipro-trust-card-text">Od poradnika do wykonawcy w jednym miejscu</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ================================================== -->
    <!-- [9] FINAL CTA (CLOSER) -->
    <!-- ================================================== -->
    <section class="hipro-final-cta">
        <div class="pb-container">
            <h2 class="hipro-final-cta__title">Nie tylko poradniki — realna pomoc</h2>

            <p class="hipro-final-cta__description">
                Sprawdź rozwiązanie, poznaj koszt i znajdź fachowca w jednym miejscu.
            </p>

            <div class="hipro-final-cta__buttons">
                <a href="<?php echo home_url('/znajdz-rozwiazanie/'); ?>" class="hipro-btn hipro-btn-primary hipro-btn-lg">
                    Znajdź rozwiązanie
                </a>
                <a href="<?php echo home_url('/fachowcy/'); ?>" class="hipro-btn hipro-btn-secondary hipro-btn-lg">
                    Sprawdź dostępność fachowców
                </a>
            </div>
        </div>
    </section>

    <!-- ================================================== -->
    <!-- [10] FOOTER (SEO ENGINE) -->
    <!-- ================================================== -->
    <footer class="hipro-footer">
        <div class="pb-container">
            <div class="hipro-footer-grid">
                <div class="hipro-footer-col">
                    <h3 class="hipro-footer-title">Poradniki</h3>
                    <ul class="hipro-footer-links">
                        <li><a href="<?php echo home_url('/motoryzacja/'); ?>">Motoryzacja</a></li>
                        <li><a href="<?php echo home_url('/dom-remont/'); ?>">Dom i remont</a></li>
                        <li><a href="<?php echo home_url('/instalacje/'); ?>">Instalacje</a></li>
                        <li><a href="<?php echo home_url('/hydraulika/'); ?>">Hydraulika</a></li>
                    </ul>
                </div>

                <div class="hipro-footer-col">
                    <h3 class="hipro-footer-title">Kategorie</h3>
                    <ul class="hipro-footer-links">
                        <li><a href="<?php echo home_url('/kategoria/problemy/'); ?>">Problemy</a></li>
                        <li><a href="<?php echo home_url('/kategoria/koszty/'); ?>">Koszty</a></li>
                        <li><a href="<?php echo home_url('/kategoria/porady/'); ?>">Porady</a></li>
                        <li><a href="<?php echo home_url('/kategoria/instrukcje/'); ?>">Instrukcje</a></li>
                    </ul>
                </div>

                <div class="hipro-footer-col">
                    <h3 class="hipro-footer-title">Rankingi</h3>
                    <ul class="hipro-footer-links">
                        <li><a href="<?php echo home_url('/ranking/mechanicy/'); ?>">Mechanicy</a></li>
                        <li><a href="<?php echo home_url('/ranking/elektrycy/'); ?>">Elektrycy</a></li>
                        <li><a href="<?php echo home_url('/ranking/hydraulicy/'); ?>">Hydraulicy</a></li>
                        <li><a href="<?php echo home_url('/ranking/firmy/'); ?>">Firmy</a></li>
                    </ul>
                </div>

                <div class="hipro-footer-col">
                    <h3 class="hipro-footer-title">Miasta</h3>
                    <ul class="hipro-footer-links">
                        <li><a href="<?php echo home_url('/warszawa/'); ?>">Warszawa</a></li>
                        <li><a href="<?php echo home_url('/krakow/'); ?>">Kraków</a></li>
                        <li><a href="<?php echo home_url('/wroclaw/'); ?>">Wrocław</a></li>
                        <li><a href="<?php echo home_url('/poznan/'); ?>">Poznań</a></li>
                    </ul>
                </div>

                <div class="hipro-footer-col">
                    <h3 class="hipro-footer-title">Kontakt</h3>
                    <ul class="hipro-footer-links">
                        <li><a href="<?php echo home_url('/o-nas/'); ?>">O nas</a></li>
                        <li><a href="<?php echo home_url('/kontakt/'); ?>">Kontakt</a></li>
                        <li><a href="<?php echo home_url('/polityka-prywatnosci/'); ?>">Polityka prywatności</a></li>
                        <li><a href="<?php echo home_url('/regulamin/'); ?>">Regulamin</a></li>
                    </ul>
                </div>
            </div>

            <div class="hipro-footer-bottom">
                <p class="hipro-footer-copyright">
                    &copy; <?php echo date('Y'); ?> Poradnik.pro — Sprawdzone rozwiązania dla realnych problemów
                </p>
            </div>
        </div>
    </footer>

</main>

<?php
get_footer();
?>

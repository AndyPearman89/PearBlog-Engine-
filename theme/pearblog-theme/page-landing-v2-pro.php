<?php
/**
 * Template Name: Landing V2 Pro (Mobile-First Neon AI)
 *
 * High-conversion landing page with mobile-first neon AI design
 * Template for Poradnik.pro premium landing experience
 *
 * @package PearBlog
 * @version 2.0.0
 */

// Add body class for V2 Pro styling
add_filter('body_class', function($classes) {
    $classes[] = 'v2-pro-neon';
    return $classes;
});

pearblog_render_header();
?>

<main id="main" class="pb-main v2pro-main" role="main">
    <?php
    // Hero Section - Mobile-First
    get_template_part('template-parts/hero-v2-pro', null, array(
        'title' => get_option('pearblog_v2pro_hero_title', 'Rozwiąż problem w kilka minut'),
        'subtitle' => get_option('pearblog_v2pro_hero_subtitle', 'Eksperci, porady i konkretne rozwiązania'),
        'cta_text' => 'Znajdź specjalistę',
        'cta_url' => home_url('/eksperci'),
        'show_badges' => true,
        'show_search' => false,
    ));
    ?>

    <?php
    // AI Panel - Core Feature
    get_template_part('template-parts/ai-panel-v2-pro', null, array(
        'title' => 'Jak mogę Ci pomóc?',
        'placeholder' => 'Napisz swój problem...',
        'cta_primary' => 'Generuj odpowiedź',
        'cta_secondary' => 'Przejdź do eksperta',
    ));
    ?>

    <?php
    // Category Blocks
    get_template_part('template-parts/category-blocks-v2-pro', null, array(
        'title' => 'Czego potrzebujesz?',
        'categories' => array(
            array(
                'icon' => '📚',
                'title' => 'Poradniki',
                'description' => 'Kompletne przewodniki krok po kroku',
                'url' => home_url('/poradniki'),
            ),
            array(
                'icon' => '⚖️',
                'title' => 'Porównania',
                'description' => 'Porównaj opcje i wybierz najlepszą',
                'url' => home_url('/porownania'),
            ),
            array(
                'icon' => '🏆',
                'title' => 'Rankingi',
                'description' => 'Najlepsi eksperci i rozwiązania',
                'url' => home_url('/rankingi'),
            ),
        ),
    ));
    ?>

    <?php
    // Expert Cards
    get_template_part('template-parts/expert-cards-v2-pro', null, array(
        'title' => 'Zweryfikowani eksperci',
        'experts' => v2pro_get_featured_experts(), // Function defined below
    ));
    ?>

    <?php
    // FAQ Section
    get_template_part('template-parts/faq-v2-pro', null, array(
        'title' => 'Najczęściej zadawane pytania',
        'faqs' => v2pro_get_faqs(), // Function defined below
    ));
    ?>

    <?php
    // Final CTA
    get_template_part('template-parts/final-cta-v2-pro', null, array(
        'title' => 'Rozwiąż problem teraz',
        'subtitle' => 'Nie czekaj — znajdź specjalistę i uzyskaj konkretną pomoc już dziś',
        'cta_text' => 'Znajdź specjalistę',
        'cta_url' => home_url('/eksperci'),
    ));
    ?>

    <?php
    // Sticky Mobile CTA (shown after scroll)
    get_template_part('template-parts/sticky-cta-v2-pro', null, array(
        'text' => 'Znajdź specjalistę',
        'url' => home_url('/eksperci'),
        'cta_id' => 'sticky-mobile',
    ));
    ?>
</main>

<?php
pearblog_render_footer();
?>

<?php
/**
 * Helper Functions for V2 Pro Template
 */

/**
 * Get featured experts
 *
 * @return array
 */
function v2pro_get_featured_experts() {
    // Check if custom experts are set in options
    $custom_experts = get_option('pearblog_v2pro_experts', array());

    if (!empty($custom_experts)) {
        return $custom_experts;
    }

    // Default featured experts
    return array(
        array(
            'name' => 'Jan Kowalski',
            'rating' => 4.9,
            'reviews' => 128,
            'specialty' => 'Prawo cywilne',
            'url' => home_url('/eksperci/jan-kowalski'),
        ),
        array(
            'name' => 'Anna Nowak',
            'rating' => 4.8,
            'reviews' => 95,
            'specialty' => 'Finanse osobiste',
            'url' => home_url('/eksperci/anna-nowak'),
        ),
        array(
            'name' => 'Piotr Wiśniewski',
            'rating' => 4.7,
            'reviews' => 112,
            'specialty' => 'Budownictwo',
            'url' => home_url('/eksperci/piotr-wisniewski'),
        ),
    );
}

/**
 * Get FAQs
 *
 * @return array
 */
function v2pro_get_faqs() {
    // Check if custom FAQs are set in options
    $custom_faqs = get_option('pearblog_v2pro_faqs', array());

    if (!empty($custom_faqs)) {
        return $custom_faqs;
    }

    // Default FAQs
    return array(
        array(
            'question' => 'Jak szybko otrzymam odpowiedź?',
            'answer' => 'Większość ekspertów odpowiada w ciągu 24 godzin. Możesz również skorzystać z naszej bazy wiedzy, gdzie znajdziesz natychmiastowe odpowiedzi na najpopularniejsze pytania.',
        ),
        array(
            'question' => 'Czy konsultacje są płatne?',
            'answer' => 'Pierwsze pytanie możesz zadać bezpłatnie. Dalsze konsultacje zależą od wybranego eksperta i rodzaju usługi. Wszystkie ceny są jasno określone w profilach ekspertów.',
        ),
        array(
            'question' => 'Jak są weryfikowani eksperci?',
            'answer' => 'Każdy ekspert przechodzi proces weryfikacji, który obejmuje sprawdzenie kwalifikacji, doświadczenia zawodowego oraz referencji. Dodatkowo zbieramy opinie od użytkowników.',
        ),
        array(
            'question' => 'Czy mogę zmienić eksperta?',
            'answer' => 'Tak, możesz w każdej chwili wybrać innego eksperta. Jeśli nie jesteś zadowolony z usługi, skontaktuj się z naszym zespołem wsparcia.',
        ),
        array(
            'question' => 'Jakie kategorie są dostępne?',
            'answer' => 'Oferujemy pomoc w kategoriach: prawo, finanse, budownictwo, zdrowie, edukacja, technologia i wiele innych. Pełną listę znajdziesz w menu głównym.',
        ),
    );
}
?>

<?php
/**
 * Template Part: FAQ V2 Pro
 *
 * Accordion-style FAQ section
 *
 * @package PearBlog
 * @version 2.0.0
 */

$args = wp_parse_args($args ?? array(), array(
    'title' => 'Najczęściej zadawane pytania',
    'faqs' => array(
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
    ),
));
?>

<section class="v2pro-section">
    <div class="v2pro-container">
        <?php if ($args['title']) : ?>
            <h2 class="v2pro-h2 v2pro-text-center v2pro-mb-xl">
                <?php echo esc_html($args['title']); ?>
            </h2>
        <?php endif; ?>

        <div class="v2pro-faq">
            <?php foreach ($args['faqs'] as $index => $faq) : ?>
                <div class="v2pro-faq-item" data-faq-index="<?php echo $index; ?>">
                    <div class="v2pro-faq-question">
                        <span><?php echo esc_html($faq['question']); ?></span>
                        <span class="v2pro-faq-icon">▼</span>
                    </div>

                    <div class="v2pro-faq-answer">
                        <div class="v2pro-faq-answer-content">
                            <?php echo wp_kses_post(wpautop($faq['answer'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

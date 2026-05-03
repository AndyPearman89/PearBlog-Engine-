<?php
/**
 * Template Name: Poradnik.pro Landing V5 - Thank You
 *
 * Post-submission thank you page with next steps and tracking
 *
 * @package PearBlog
 * @version 5.1.0
 */

get_header('minimal');
?>

<main class="poradnik-landing-v5-thankyou">
    <!-- Success Hero -->
    <section class="plv5-ty-hero">
        <div class="pb-container">
            <div class="plv5-ty-content">
                <!-- Success Icon -->
                <div class="plv5-ty-icon">
                    <svg width="120" height="120" viewBox="0 0 120 120" fill="none">
                        <circle cx="60" cy="60" r="60" fill="#00c853" fill-opacity="0.1"/>
                        <circle cx="60" cy="60" r="48" fill="#00c853" fill-opacity="0.2"/>
                        <path d="M40 60L52 72L80 44" stroke="#00c853" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>

                <h1 class="plv5-ty-title">
                    <?php echo esc_html(get_option('plv5_ty_title', 'Dziękujemy za zgłoszenie!')); ?>
                </h1>

                <p class="plv5-ty-subtitle">
                    <?php echo esc_html(get_option('plv5_ty_subtitle', 'Twoje zapytanie zostało pomyślnie wysłane. Nasz zespół skontaktuje się z Tobą wkrótce.')); ?>
                </p>

                <!-- Confirmation Badge -->
                <div class="plv5-ty-badge">
                    ✓ Potwierdzenie wysłane na Twój email
                </div>
            </div>
        </div>
    </section>

    <!-- What Happens Next -->
    <section class="plv5-ty-steps">
        <div class="pb-container">
            <h2 class="plv5-section-title">Co dalej?</h2>
            <p class="plv5-section-subtitle">Oto następne kroki w procesie</p>

            <div class="plv5-ty-timeline">
                <div class="plv5-ty-step">
                    <div class="plv5-ty-step-number">1</div>
                    <div class="plv5-ty-step-content">
                        <h3 class="plv5-ty-step-title">Analiza Twojego zapytania</h3>
                        <p class="plv5-ty-step-desc">
                            Nasz zespół analizuje Twoje potrzeby i dobiera najlepszych ekspertów z naszej bazy zweryfikowanych wykonawców.
                        </p>
                        <div class="plv5-ty-step-time">⏱️ Trwa: 2-4 godziny</div>
                    </div>
                </div>

                <div class="plv5-ty-step">
                    <div class="plv5-ty-step-number">2</div>
                    <div class="plv5-ty-step-content">
                        <h3 class="plv5-ty-step-title">Otrzymasz dopasowane oferty</h3>
                        <p class="plv5-ty-step-desc">
                            Do 5 zweryfikowanych wykonawców otrzyma Twoje zapytanie. Dostaniesz oferty bezpośrednio na swój email.
                        </p>
                        <div class="plv5-ty-step-time">⏱️ Do 24 godzin</div>
                    </div>
                </div>

                <div class="plv5-ty-step">
                    <div class="plv5-ty-step-number">3</div>
                    <div class="plv5-ty-step-content">
                        <h3 class="plv5-ty-step-title">Porównaj i wybierz</h3>
                        <p class="plv5-ty-step-desc">
                            Porównaj oferty pod kątem ceny, terminów, ocen i doświadczenia. Wybierz wykonawcę najlepiej dopasowanego do Twoich potrzeb.
                        </p>
                        <div class="plv5-ty-step-time">💯 Bez zobowiązań</div>
                    </div>
                </div>

                <div class="plv5-ty-step">
                    <div class="plv5-ty-step-number">4</div>
                    <div class="plv5-ty-step-content">
                        <h3 class="plv5-ty-step-title">Realizacja projektu</h3>
                        <p class="plv5-ty-step-desc">
                            Kontakt z wykonawcą, ustalenie szczegółów, realizacja usługi według umowy. Jesteśmy z Tobą przez cały proces!
                        </p>
                        <div class="plv5-ty-step-time">🎯 Sukces gwarantowany</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Tips -->
    <section class="plv5-ty-tips">
        <div class="pb-container">
            <h2 class="plv5-section-title">Przydatne wskazówki</h2>

            <div class="plv5-ty-tips-grid">
                <div class="plv5-ty-tip">
                    <div class="plv5-ty-tip-icon">📧</div>
                    <h3 class="plv5-ty-tip-title">Sprawdź email</h3>
                    <p class="plv5-ty-tip-desc">
                        Wysłaliśmy potwierdzenie na Twój adres email. Jeśli nie widzisz wiadomości, sprawdź folder SPAM.
                    </p>
                </div>

                <div class="plv5-ty-tip">
                    <div class="plv5-ty-tip-icon">📝</div>
                    <h3 class="plv5-ty-tip-title">Przygotuj pytania</h3>
                    <p class="plv5-ty-tip-desc">
                        Przygotuj listę pytań do wykonawców. Im więcej szczegółów, tym lepsze dopasowanie oferty.
                    </p>
                </div>

                <div class="plv5-ty-tip">
                    <div class="plv5-ty-tip-icon">⭐</div>
                    <h3 class="plv5-ty-tip-title">Sprawdź opinie</h3>
                    <p class="plv5-ty-tip-desc">
                        Przeczytaj opinie innych klientów o wykonawcach. To pomoże Ci podjąć najlepszą decyzję.
                    </p>
                </div>

                <div class="plv5-ty-tip">
                    <div class="plv5-ty-tip-icon">💰</div>
                    <h3 class="plv5-ty-tip-title">Porównaj oferty</h3>
                    <p class="plv5-ty-tip-desc">
                        Nie decyduj się na pierwszą ofertę. Porównaj ceny, terminy i warunki od wszystkich wykonawców.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Quick -->
    <section class="plv5-ty-faq">
        <div class="pb-container">
            <h2 class="plv5-section-title">Najczęściej zadawane pytania</h2>

            <div class="plv5-ty-faq-list">
                <div class="plv5-ty-faq-item">
                    <h3>Kiedy otrzymam pierwsze oferty?</h3>
                    <p>Większość użytkowników otrzymuje pierwsze oferty w ciągu 2-4 godzin. Maksymalnie w 24 godziny.</p>
                </div>

                <div class="plv5-ty-faq-item">
                    <h3>Czy muszę wybrać jedną z otrzymanych ofert?</h3>
                    <p>Nie! Nie masz żadnych zobowiązań. Możesz porównać oferty i zdecydować się na najlepszą lub zrezygnować.</p>
                </div>

                <div class="plv5-ty-faq-item">
                    <h3>Czy mogę zmienić swoje wymagania?</h3>
                    <p>Tak, możesz skontaktować się z nami lub bezpośrednio z wykonawcami, aby doprecyzować szczegóły.</p>
                </div>

                <div class="plv5-ty-faq-item">
                    <h3>Co jeśli nie będę zadowolony?</h3>
                    <p>Nasz zespół wsparcia jest dostępny 24/7. Pomożemy rozwiązać każdy problem i znajdziemy lepszego wykonawcę.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Proof -->
    <section class="plv5-ty-social">
        <div class="pb-container">
            <h2 class="plv5-section-title">Dołącz do tysięcy zadowolonych klientów</h2>

            <div class="plv5-ty-stats">
                <div class="plv5-ty-stat">
                    <div class="plv5-ty-stat-number">50,000+</div>
                    <div class="plv5-ty-stat-label">Zadowolonych klientów</div>
                </div>

                <div class="plv5-ty-stat">
                    <div class="plv5-ty-stat-number">5,000+</div>
                    <div class="plv5-ty-stat-label">Zweryfikowanych ekspertów</div>
                </div>

                <div class="plv5-ty-stat">
                    <div class="plv5-ty-stat-number">4.8/5</div>
                    <div class="plv5-ty-stat-label">Średnia ocena</div>
                </div>

                <div class="plv5-ty-stat">
                    <div class="plv5-ty-stat-number">98%</div>
                    <div class="plv5-ty-stat-label">Wskaźnik sukcesu</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="plv5-ty-cta">
        <div class="pb-container">
            <h2>Masz pytania? Skontaktuj się z nami!</h2>
            <p>Nasz zespół jest gotowy pomóc na każdym etapie</p>

            <div class="plv5-ty-contact">
                <a href="mailto:kontakt@poradnik.pro" class="plv5-btn plv5-btn--primary">
                    📧 Wyślij email
                </a>
                <a href="tel:+48123456789" class="plv5-btn plv5-btn--secondary">
                    📞 Zadzwoń: +48 123 456 789
                </a>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="plv5-btn plv5-btn--outline">
                    🏠 Wróć na stronę główną
                </a>
            </div>
        </div>
    </section>
</main>

<style>
/* Thank You Page Styles */
.poradnik-landing-v5-thankyou {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.plv5-ty-hero {
    padding: 4rem 1rem 3rem;
    background: linear-gradient(135deg, #0066ff 0%, #00d4ff 100%);
    color: white;
    text-align: center;
}

.plv5-ty-content {
    max-width: 700px;
    margin: 0 auto;
}

.plv5-ty-icon {
    margin: 0 auto 2rem;
    width: 120px;
    height: 120px;
    animation: successPulse 2s infinite;
}

@keyframes successPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.plv5-ty-title {
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 800;
    margin: 0 0 1rem;
}

.plv5-ty-subtitle {
    font-size: clamp(1rem, 2vw, 1.25rem);
    margin: 0 0 2rem;
    opacity: 0.95;
}

.plv5-ty-badge {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 50px;
    font-size: 0.9rem;
    font-weight: 600;
}

.plv5-ty-steps {
    padding: 4rem 1rem;
    background: #f9fafb;
}

.plv5-ty-timeline {
    max-width: 800px;
    margin: 0 auto;
}

.plv5-ty-step {
    display: flex;
    gap: 2rem;
    margin-bottom: 3rem;
    position: relative;
}

.plv5-ty-step:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 24px;
    top: 50px;
    width: 2px;
    height: calc(100% + 2rem);
    background: linear-gradient(to bottom, #0066ff 0%, #00d4ff 100%);
}

.plv5-ty-step-number {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #0066ff 0%, #00d4ff 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 800;
    flex-shrink: 0;
    position: relative;
    z-index: 1;
}

.plv5-ty-step-content {
    flex: 1;
}

.plv5-ty-step-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 0.5rem;
}

.plv5-ty-step-desc {
    color: #4b5563;
    margin: 0 0 0.75rem;
    line-height: 1.6;
}

.plv5-ty-step-time {
    font-size: 0.875rem;
    font-weight: 600;
    color: #0066ff;
}

.plv5-ty-tips {
    padding: 4rem 1rem;
    background: white;
}

.plv5-ty-tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.plv5-ty-tip {
    text-align: center;
    padding: 2rem;
    background: #f9fafb;
    border-radius: 12px;
    transition: transform 0.3s;
}

.plv5-ty-tip:hover {
    transform: translateY(-4px);
}

.plv5-ty-tip-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.plv5-ty-tip-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 0.75rem;
}

.plv5-ty-tip-desc {
    color: #4b5563;
    line-height: 1.6;
}

.plv5-ty-faq {
    padding: 4rem 1rem;
    background: #f9fafb;
}

.plv5-ty-faq-list {
    max-width: 800px;
    margin: 0 auto;
}

.plv5-ty-faq-item {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.plv5-ty-faq-item h3 {
    font-size: 1.125rem;
    font-weight: 700;
    margin: 0 0 0.75rem;
}

.plv5-ty-faq-item p {
    color: #4b5563;
    margin: 0;
    line-height: 1.6;
}

.plv5-ty-social {
    padding: 4rem 1rem;
    background: white;
}

.plv5-ty-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
    max-width: 1000px;
    margin: 0 auto;
}

.plv5-ty-stat {
    text-align: center;
}

.plv5-ty-stat-number {
    font-size: 3rem;
    font-weight: 800;
    color: #0066ff;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.plv5-ty-stat-label {
    font-size: 1rem;
    color: #4b5563;
}

.plv5-ty-cta {
    padding: 4rem 1rem;
    background: linear-gradient(135deg, #0a0e1a 0%, #1e293b 100%);
    color: white;
    text-align: center;
}

.plv5-ty-cta h2 {
    font-size: clamp(1.5rem, 3vw, 2rem);
    font-weight: 800;
    margin: 0 0 1rem;
}

.plv5-ty-cta p {
    font-size: 1.125rem;
    margin: 0 0 2rem;
    opacity: 0.9;
}

.plv5-ty-contact {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 1rem;
}

.plv5-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
}

.plv5-btn--primary {
    background: #0066ff;
    color: white;
}

.plv5-btn--primary:hover {
    background: #0052cc;
    transform: translateY(-2px);
}

.plv5-btn--secondary {
    background: #00c853;
    color: white;
}

.plv5-btn--secondary:hover {
    background: #00a843;
    transform: translateY(-2px);
}

.plv5-btn--outline {
    background: transparent;
    border: 2px solid white;
    color: white;
}

.plv5-btn--outline:hover {
    background: white;
    color: #0a0e1a;
}

@media (max-width: 768px) {
    .plv5-ty-step {
        flex-direction: column;
        gap: 1rem;
    }

    .plv5-ty-step::after {
        display: none;
    }

    .plv5-ty-contact {
        flex-direction: column;
    }

    .plv5-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
// Track thank you page view
(function() {
    // Google Analytics
    if (typeof gtag !== 'undefined') {
        gtag('event', 'page_view', {
            page_title: 'Thank You Page',
            page_location: window.location.href,
            page_path: window.location.pathname
        });
    }

    // Facebook Pixel
    if (typeof fbq !== 'undefined') {
        fbq('track', 'PageView');
    }

    console.log('Thank you page viewed');
})();
</script>

<?php
get_footer('minimal');
?>

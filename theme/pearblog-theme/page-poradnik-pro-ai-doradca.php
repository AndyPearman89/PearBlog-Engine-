<?php
/**
 * Template Name: Poradnik.PRO - AI Doradca
 * Description: AI advisor page — personalized recommendation flow (budget, location, goal → result).
 *
 * @package PearBlog
 * @version 5.0.0
 */
defined( 'ABSPATH' ) || exit;
require_once get_template_directory() . '/inc/poradnik-pro-shared.php';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php pp_pro_shared_styles(); ?>
    <style>
        .ai-advisor-page {
            padding: 32px 0 72px;
            background:
                radial-gradient(circle at top, rgba(139, 92, 246, 0.14), transparent 32%),
                linear-gradient(180deg, #ffffff 0%, var(--gray-50) 100%);
        }

        .ai-advisor-shell {
            max-width: 920px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .ai-breadcrumb-wrap {
            margin-bottom: 24px;
        }

        .ai-hero {
            position: relative;
            overflow: hidden;
            padding: 48px;
            border-radius: var(--radius-xl);
            background: linear-gradient(135deg, rgba(108, 43, 217, 0.08) 0%, rgba(139, 92, 246, 0.16) 55%, rgba(249, 115, 22, 0.10) 100%);
            border: 1px solid rgba(108, 43, 217, 0.12);
            box-shadow: var(--shadow-lg);
            margin-bottom: 32px;
            text-align: center;
        }

        .ai-hero::before,
        .ai-hero::after {
            content: '';
            position: absolute;
            border-radius: 999px;
            background: rgba(108, 43, 217, 0.08);
            pointer-events: none;
        }

        .ai-hero::before {
            width: 220px;
            height: 220px;
            top: -80px;
            right: -40px;
        }

        .ai-hero::after {
            width: 180px;
            height: 180px;
            bottom: -90px;
            left: -60px;
        }

        .ai-hero > * {
            position: relative;
            z-index: 1;
        }

        .ai-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            margin-bottom: 18px;
            border-radius: 999px;
            background: rgba(108, 43, 217, 0.12);
            color: var(--purple-primary);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .ai-hero-title,
        .ai-section-title,
        .ai-result-title {
            color: var(--gray-900);
            letter-spacing: -0.02em;
        }

        .ai-hero-title {
            margin: 0 0 16px;
            font-size: clamp(2rem, 4vw, 3.25rem);
            font-weight: 800;
            line-height: 1.05;
        }

        .ai-hero-text {
            max-width: 660px;
            margin: 0 auto;
            font-size: 18px;
            line-height: 1.7;
            color: var(--gray-600);
        }

        .ai-form-card,
        .ai-result-card,
        .ai-section-card,
        .ai-faq-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
        }

        .ai-form-card {
            padding: 32px;
            margin-bottom: 28px;
        }

        .ai-form {
            display: grid;
            gap: 28px;
        }

        .ai-fieldset {
            border: 0;
        }

        .ai-field-label {
            display: block;
            margin-bottom: 14px;
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-900);
        }

        .ai-options-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .ai-option {
            display: block;
        }

        .ai-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .ai-option-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 118px;
            padding: 20px 16px;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            background: linear-gradient(180deg, #fff 0%, var(--gray-50) 100%);
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background 0.2s ease;
            cursor: pointer;
        }

        .ai-option-card:hover {
            transform: translateY(-2px);
            border-color: rgba(108, 43, 217, 0.28);
            box-shadow: 0 10px 24px rgba(108, 43, 217, 0.10);
        }

        .ai-option input:focus-visible + .ai-option-card {
            outline: 3px solid rgba(108, 43, 217, 0.22);
            outline-offset: 2px;
        }

        .ai-option input:checked + .ai-option-card {
            border-color: var(--purple-primary);
            background: linear-gradient(180deg, rgba(108, 43, 217, 0.08) 0%, rgba(139, 92, 246, 0.14) 100%);
            box-shadow: 0 0 0 3px rgba(108, 43, 217, 0.18), 0 14px 30px rgba(108, 43, 217, 0.15);
        }

        .ai-option-emoji {
            display: block;
            margin-bottom: 8px;
            font-size: 28px;
            line-height: 1;
        }

        .ai-option-text {
            font-size: 15px;
            font-weight: 600;
            color: var(--gray-800);
        }

        .ai-select,
        .ai-input,
        .ai-textarea {
            width: 100%;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius-lg);
            background: #fff;
            color: var(--gray-800);
            font: inherit;
            padding: 16px 18px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .ai-textarea {
            min-height: 128px;
            resize: vertical;
        }

        .ai-select:hover,
        .ai-input:hover,
        .ai-textarea:hover {
            border-color: var(--purple-light);
        }

        .ai-select:focus,
        .ai-input:focus,
        .ai-textarea:focus {
            outline: none;
            border-color: var(--purple-primary);
            box-shadow: 0 0 0 4px rgba(108, 43, 217, 0.12);
            background: #fff;
        }

        .ai-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 18px 24px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--purple-primary) 0%, var(--purple-light) 100%);
            color: #fff;
            font-size: 18px;
            font-weight: 800;
            box-shadow: 0 18px 35px rgba(108, 43, 217, 0.24);
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
        }

        .ai-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 22px 42px rgba(108, 43, 217, 0.30);
            filter: brightness(1.03);
        }

        .ai-submit:focus-visible {
            outline: 3px solid rgba(108, 43, 217, 0.22);
            outline-offset: 3px;
        }

        .ai-result {
            margin-bottom: 28px;
        }

        .ai-result.is-hidden {
            display: none;
        }

        .ai-result-card {
            padding: 32px;
            border-color: rgba(108, 43, 217, 0.16);
            background: linear-gradient(135deg, rgba(243, 232, 255, 0.84) 0%, rgba(255, 255, 255, 0.98) 52%, rgba(237, 233, 254, 0.92) 100%);
        }

        .ai-result-title {
            margin: 0 0 18px;
            font-size: 30px;
            font-weight: 800;
        }

        .ai-result-content {
            color: var(--gray-700);
            line-height: 1.75;
        }

        .ai-result-content p {
            margin: 0 0 14px;
        }

        .ai-result-content strong {
            color: var(--gray-900);
        }

        .ai-result-loading {
            text-align: center;
            color: var(--gray-500);
            font-weight: 500;
        }

        .ai-result-list {
            margin: 0 0 14px;
            padding-left: 20px;
        }

        .ai-result-list li {
            margin-bottom: 8px;
        }

        .ai-recommendation-box {
            margin-top: 18px;
            padding: 18px;
            border: 1px solid rgba(108, 43, 217, 0.12);
            border-radius: var(--radius-lg);
            background: rgba(255, 255, 255, 0.92);
            box-shadow: var(--shadow-sm);
        }

        .ai-result-links {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid rgba(108, 43, 217, 0.12);
        }

        .ai-result-link {
            display: block;
            padding: 18px 14px;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            background: rgba(255, 255, 255, 0.92);
            text-align: center;
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .ai-result-link:hover {
            transform: translateY(-2px);
            border-color: var(--purple-primary);
            box-shadow: 0 12px 28px rgba(108, 43, 217, 0.12);
        }

        .ai-result-link-icon {
            display: block;
            margin-bottom: 8px;
            font-size: 22px;
        }

        .ai-result-link-label {
            font-size: 14px;
            font-weight: 700;
            color: var(--gray-800);
        }

        .ai-section-card,
        .ai-faq-card {
            padding: 32px;
            margin-bottom: 28px;
        }

        .ai-section-title {
            margin: 0 0 24px;
            font-size: 30px;
            font-weight: 800;
            text-align: center;
        }

        .ai-steps-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .ai-step {
            padding: 26px 22px;
            border-radius: var(--radius-lg);
            background: linear-gradient(180deg, #ffffff 0%, var(--gray-50) 100%);
            border: 1px solid var(--gray-200);
            text-align: center;
        }

        .ai-step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            margin-bottom: 16px;
            border-radius: 50%;
            background: rgba(108, 43, 217, 0.12);
            color: var(--purple-primary);
            font-size: 22px;
            font-weight: 800;
        }

        .ai-step-title {
            margin: 0 0 10px;
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-900);
        }

        .ai-step-text {
            margin: 0;
            color: var(--gray-600);
            line-height: 1.65;
        }

        .ai-faq-title {
            margin: 0 0 20px;
            font-size: 28px;
            font-weight: 800;
            color: var(--gray-900);
        }

        .ai-faq-list {
            display: grid;
            gap: 14px;
        }

        .ai-faq-item {
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            background: linear-gradient(180deg, #fff 0%, var(--gray-50) 100%);
            overflow: hidden;
        }

        .ai-faq-item summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 20px;
            cursor: pointer;
            list-style: none;
            font-size: 17px;
            font-weight: 600;
            color: var(--gray-900);
        }

        .ai-faq-item summary::-webkit-details-marker {
            display: none;
        }

        .ai-faq-item summary:hover {
            background: rgba(108, 43, 217, 0.04);
        }

        .ai-faq-icon {
            color: var(--gray-400);
            transition: transform 0.2s ease, color 0.2s ease;
            flex-shrink: 0;
        }

        .ai-faq-item[open] .ai-faq-icon {
            transform: rotate(180deg);
            color: var(--purple-primary);
        }

        .ai-faq-answer {
            padding: 0 20px 18px;
            color: var(--gray-600);
            line-height: 1.7;
        }

        .ai-link {
            color: var(--purple-primary);
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .ai-link:hover {
            color: var(--purple-dark);
        }

        @media (max-width: 900px) {
            .ai-options-grid,
            .ai-steps-grid,
            .ai-result-links {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .ai-advisor-page {
                padding: 24px 0 56px;
            }

            .ai-advisor-shell {
                padding: 0 16px;
            }

            .ai-hero,
            .ai-form-card,
            .ai-result-card,
            .ai-section-card,
            .ai-faq-card {
                padding: 24px 20px;
            }

            .ai-hero-title,
            .ai-section-title,
            .ai-result-title,
            .ai-faq-title {
                font-size: 28px;
            }

            .ai-field-label {
                font-size: 18px;
            }

            .ai-options-grid,
            .ai-steps-grid,
            .ai-result-links {
                grid-template-columns: 1fr;
            }

            .ai-option-card {
                min-height: 98px;
            }

            .ai-submit {
                font-size: 17px;
                padding: 16px 20px;
            }
        }
    </style>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); pp_pro_header( 'ai-doradca' ); ?>

<main class="ai-advisor-page">
    <div class="ai-advisor-shell">
        <div class="ai-breadcrumb-wrap">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="/">Strona główna</a>
                <span class="sep">/</span>
                <span>AI Doradca</span>
            </nav>
        </div>

        <header class="ai-hero">
            <span class="ai-eyebrow">🤖 AI Doradca</span>
            <h1 class="ai-hero-title">Nie wiesz co wybrać? AI Ci pomoże.</h1>
            <p class="ai-hero-text">Odpowiedz na kilka pytań, a nasz system dobierze najlepsze rozwiązanie — spersonalizowane do Twojej sytuacji.</p>
        </header>

        <section class="ai-form-card">
            <form id="ai-advisor-form" class="ai-form">
                <fieldset class="ai-fieldset">
                    <legend class="ai-field-label">1. Czego dotyczy Twoje pytanie?</legend>
                    <div class="ai-options-grid">
                        <label class="ai-option">
                            <input type="radio" name="category" value="ogrzewanie">
                            <span class="ai-option-card">
                                <span class="ai-option-emoji">🔥</span>
                                <span class="ai-option-text">Ogrzewanie</span>
                            </span>
                        </label>
                        <label class="ai-option">
                            <input type="radio" name="category" value="remont">
                            <span class="ai-option-card">
                                <span class="ai-option-emoji">🏠</span>
                                <span class="ai-option-text">Remont</span>
                            </span>
                        </label>
                        <label class="ai-option">
                            <input type="radio" name="category" value="prawo">
                            <span class="ai-option-card">
                                <span class="ai-option-emoji">⚖️</span>
                                <span class="ai-option-text">Prawo</span>
                            </span>
                        </label>
                        <label class="ai-option">
                            <input type="radio" name="category" value="finanse">
                            <span class="ai-option-card">
                                <span class="ai-option-emoji">💰</span>
                                <span class="ai-option-text">Finanse</span>
                            </span>
                        </label>
                        <label class="ai-option">
                            <input type="radio" name="category" value="energia">
                            <span class="ai-option-card">
                                <span class="ai-option-emoji">⚡</span>
                                <span class="ai-option-text">Energia</span>
                            </span>
                        </label>
                        <label class="ai-option">
                            <input type="radio" name="category" value="inne">
                            <span class="ai-option-card">
                                <span class="ai-option-emoji">📋</span>
                                <span class="ai-option-text">Inne</span>
                            </span>
                        </label>
                    </div>
                </fieldset>

                <div>
                    <label for="budget" class="ai-field-label">2. Jaki masz budżet?</label>
                    <select id="budget" name="budget" class="ai-select">
                        <option value="">Wybierz przedział…</option>
                        <option value="do-5000">do 5 000 zł</option>
                        <option value="5000-20000">5 000 – 20 000 zł</option>
                        <option value="20000-50000">20 000 – 50 000 zł</option>
                        <option value="50000-100000">50 000 – 100 000 zł</option>
                        <option value="powyzej-100000">powyżej 100 000 zł</option>
                        <option value="nie-wiem">Nie wiem jeszcze</option>
                    </select>
                </div>

                <div>
                    <label for="location" class="ai-field-label">3. Twoja lokalizacja</label>
                    <input type="text" id="location" name="location" placeholder="np. Kraków, Katowice, Warszawa…" class="ai-input">
                </div>

                <div>
                    <label for="goal" class="ai-field-label">4. Opisz swój cel lub problem</label>
                    <textarea id="goal" name="goal" rows="3" placeholder="np. Chcę wymienić ogrzewanie na tańsze, mam dom 120m², szukam rozwiązania na zimę 2026…" class="ai-textarea"></textarea>
                </div>

                <button type="submit" class="ai-submit">🤖 Otrzymaj rekomendację</button>
            </form>
        </section>

        <section id="ai-result" class="ai-result is-hidden">
            <div class="ai-result-card">
                <h2 class="ai-result-title">🎯 Twoja rekomendacja</h2>
                <div id="ai-result-content" class="ai-result-content">
                    <!-- Populated by JS/API -->
                </div>
                <div class="ai-result-links">
                    <a href="/porownania/" class="ai-result-link">
                        <span class="ai-result-link-icon">🆚</span>
                        <span class="ai-result-link-label">Porównania</span>
                    </a>
                    <a href="/rankingi/" class="ai-result-link">
                        <span class="ai-result-link-icon">🏆</span>
                        <span class="ai-result-link-label">Rankingi</span>
                    </a>
                    <a href="/specjalisci/" class="ai-result-link">
                        <span class="ai-result-link-icon">🧑‍💼</span>
                        <span class="ai-result-link-label">Specjaliści</span>
                    </a>
                </div>
            </div>
        </section>

        <section class="ai-section-card">
            <h2 class="ai-section-title">Jak działa AI Doradca?</h2>
            <div class="ai-steps-grid">
                <article class="ai-step">
                    <span class="ai-step-number">1</span>
                    <h3 class="ai-step-title">Opisujesz sytuację</h3>
                    <p class="ai-step-text">Kategoria, budżet, lokalizacja i cel — AI potrzebuje kontekstu.</p>
                </article>
                <article class="ai-step">
                    <span class="ai-step-number">2</span>
                    <h3 class="ai-step-title">AI analizuje opcje</h3>
                    <p class="ai-step-text">System przeszukuje bazę poradników, rankingów i specjalistów.</p>
                </article>
                <article class="ai-step">
                    <span class="ai-step-number">3</span>
                    <h3 class="ai-step-title">Dostajesz rekomendację</h3>
                    <p class="ai-step-text">Spersonalizowana odpowiedź + linki do porównań, kalkulatorów i ekspertów.</p>
                </article>
            </div>
        </section>

        <section class="ai-faq-card">
            <h2 class="ai-faq-title">Często zadawane pytania</h2>
            <div class="ai-faq-list">
                <details class="ai-faq-item">
                    <summary>
                        Czy to jest darmowe?
                        <span class="ai-faq-icon">▼</span>
                    </summary>
                    <div class="ai-faq-answer">Tak, korzystanie z AI Doradcy jest całkowicie bezpłatne. Nie wymagamy rejestracji.</div>
                </details>
                <details class="ai-faq-item">
                    <summary>
                        Jak dokładne są rekomendacje?
                        <span class="ai-faq-icon">▼</span>
                    </summary>
                    <div class="ai-faq-answer">AI bazuje na aktualnych danych z naszej bazy poradników i opinii. Rekomendacje są punktem wyjścia — ostateczną decyzję zawsze podejmujesz Ty.</div>
                </details>
                <details class="ai-faq-item">
                    <summary>
                        Czy moje dane są bezpieczne?
                        <span class="ai-faq-icon">▼</span>
                    </summary>
                    <div class="ai-faq-answer">Nie przechowujemy danych osobowych. Zapytania są anonimowe i służą wyłącznie do generowania odpowiedzi.</div>
                </details>
            </div>
        </section>
    </div>
</main>

<script>
document.getElementById('ai-advisor-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = e.target;
    var result = document.getElementById('ai-result');
    var content = document.getElementById('ai-result-content');

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    var checkedCategory = form.querySelector('input[name="category"]:checked');
    var data = {
        category: checkedCategory ? checkedCategory.value : '',
        budget: form.elements['budget'] ? form.elements['budget'].value : '',
        location: form.elements['location'] ? form.elements['location'].value : '',
        goal: form.elements['goal'] ? form.elements['goal'].value : ''
    };

    result.classList.remove('is-hidden');
    content.innerHTML = '<p class="ai-result-loading">⏳ Analizuję opcje…</p>';

    setTimeout(function() {
        content.innerHTML =
            '<p><strong>Na podstawie Twoich odpowiedzi:</strong></p>' +
            '<ul class="ai-result-list">' +
                '<li>Kategoria: <strong>' + escapeHtml(data.category || 'nie wybrano') + '</strong></li>' +
                '<li>Budżet: <strong>' + escapeHtml(data.budget || 'nie podano') + '</strong></li>' +
                '<li>Lokalizacja: <strong>' + escapeHtml(data.location || 'nie podano') + '</strong></li>' +
            '</ul>' +
            '<div class="ai-recommendation-box">' +
                '<p><strong>💡 Rekomendacja:</strong></p>' +
                '<p>Skonfiguruj integrację z API AI, aby otrzymać spersonalizowaną rekomendację. W międzyczasie sprawdź nasze <a href="/porownania/" class="ai-link">porównania</a> i <a href="/rankingi/" class="ai-link">rankingi</a>.</p>' +
            '</div>';
    }, 1500);
});
</script>

<?php pp_pro_footer(); wp_footer(); ?>
</body>
</html>

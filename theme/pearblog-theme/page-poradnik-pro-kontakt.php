<?php
/**
 * Template Name: Poradnik.pro - Kontakt
 *
 * Contact page for Poradnik.pro with form, contact info, FAQ, and CTA.
 * Uses the shared purple Inter-based design system (no Tailwind).
 *
 * @package PearBlog
 * @subpackage PoradnikPro
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
        /* ===== CONTACT PAGE STYLES ===== */
        .page-hero--kontakt {
            background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
            padding: 56px 0;
        }
        .page-hero--kontakt h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 8px;
        }
        .page-hero--kontakt .subtitle {
            font-size: 16px;
            color: var(--gray-600);
            max-width: 560px;
        }

        /* ===== CONTACT SECTION ===== */
        .contact-section {
            padding: 64px 0;
        }
        .contact-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 48px;
            align-items: flex-start;
        }

        /* ===== CONTACT FORM ===== */
        .contact-form-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 36px;
            box-shadow: var(--shadow-sm);
        }
        .contact-form-card h2 {
            font-size: 22px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 6px;
        }
        .contact-form-card .form-desc {
            font-size: 14px;
            color: var(--gray-500);
            margin-bottom: 28px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 16px;
        }
        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-700);
        }
        .form-group label .required {
            color: #ef4444;
            margin-left: 2px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 11px 14px;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-family: inherit;
            color: var(--gray-800);
            background: #fff;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: var(--gray-400);
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--purple-primary);
            box-shadow: 0 0 0 3px rgba(108, 43, 217, 0.1);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 140px;
        }
        .form-consent {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 24px;
            font-size: 12px;
            color: var(--gray-500);
            line-height: 1.5;
        }
        .form-consent input[type="checkbox"] {
            margin-top: 2px;
            accent-color: var(--purple-primary);
        }
        .btn-submit {
            background: var(--purple-primary);
            color: #fff;
            padding: 14px 32px;
            border-radius: 50px;
            font-size: 15px;
            font-weight: 600;
            transition: background 0.2s, transform 0.1s;
            width: 100%;
            text-align: center;
        }
        .btn-submit:hover {
            background: var(--purple-dark);
            transform: translateY(-1px);
        }
        .btn-submit:active {
            transform: translateY(0);
        }

        /* ===== CONTACT SIDEBAR ===== */
        .contact-sidebar {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .sidebar-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 24px;
            transition: box-shadow 0.2s;
        }
        .sidebar-card:hover {
            box-shadow: var(--shadow-sm);
        }
        .sidebar-card h3 {
            font-size: 15px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .sidebar-card h3 .icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        .icon--email { background: #dbeafe; }
        .icon--phone { background: #d1fae5; }
        .icon--hours { background: #fef3c7; }
        .icon--social { background: #f3e8ff; }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid var(--gray-100);
        }
        .contact-item:last-child {
            border-bottom: none;
        }
        .contact-item-label {
            font-size: 12px;
            color: var(--gray-500);
        }
        .contact-item-value {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-800);
        }
        .contact-item-value a {
            color: var(--purple-primary);
            transition: color 0.2s;
        }
        .contact-item-value a:hover {
            color: var(--purple-dark);
        }
        .hours-table {
            width: 100%;
            font-size: 13px;
        }
        .hours-table tr td {
            padding: 6px 0;
            border-bottom: 1px solid var(--gray-100);
        }
        .hours-table tr:last-child td {
            border-bottom: none;
        }
        .hours-table .day {
            color: var(--gray-600);
            font-weight: 500;
        }
        .hours-table .time {
            text-align: right;
            color: var(--gray-800);
            font-weight: 600;
        }
        .social-links {
            display: flex;
            gap: 12px;
            margin-top: 8px;
        }
        .social-link {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: var(--gray-600);
            transition: background 0.2s, color 0.2s, border-color 0.2s;
        }
        .social-link:hover {
            background: var(--purple-primary);
            color: #fff;
            border-color: var(--purple-primary);
        }

        /* ===== FAQ SECTION ===== */
        .faq-section {
            padding: 72px 0;
            background: #fff;
            border-top: 1px solid var(--gray-200);
        }
        .faq-header {
            text-align: center;
            margin-bottom: 48px;
        }
        .faq-header h2 {
            font-size: 26px;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 10px;
        }
        .faq-header p {
            font-size: 15px;
            color: var(--gray-500);
            max-width: 500px;
            margin: 0 auto;
        }
        .faq-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            max-width: 960px;
            margin: 0 auto;
        }
        .faq-item {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 24px;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .faq-item:hover {
            box-shadow: var(--shadow-sm);
            transform: translateY(-2px);
        }
        .faq-question {
            font-size: 15px;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 10px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .faq-question::before {
            content: "?";
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--purple-primary);
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            flex-shrink: 0;
        }
        .faq-answer {
            font-size: 13px;
            color: var(--gray-600);
            line-height: 1.7;
            padding-left: 34px;
        }

        /* ===== CTA SECTION ===== */
        .cta-section {
            padding: 72px 0;
            background: linear-gradient(135deg, #f3e8ff 0%, #ede9fe 50%, #dbeafe 100%);
        }
        .cta-inner {
            max-width: 640px;
            margin: 0 auto;
            text-align: center;
        }
        .cta-inner h2 {
            font-size: 28px;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 12px;
        }
        .cta-inner p {
            font-size: 16px;
            color: var(--gray-600);
            margin-bottom: 32px;
            line-height: 1.6;
        }
        .cta-buttons {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .btn-cta-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--purple-primary);
            color: #fff;
            padding: 14px 32px;
            border-radius: 50px;
            font-size: 15px;
            font-weight: 600;
            transition: background 0.2s, transform 0.1s;
        }
        .btn-cta-primary:hover {
            background: var(--purple-dark);
            transform: translateY(-1px);
        }
        .btn-cta-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #fff;
            color: var(--gray-700);
            padding: 14px 28px;
            border-radius: 50px;
            font-size: 15px;
            font-weight: 600;
            border: 1px solid var(--gray-200);
            transition: border-color 0.2s, color 0.2s;
        }
        .btn-cta-secondary:hover {
            border-color: var(--purple-primary);
            color: var(--purple-primary);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .contact-grid {
                grid-template-columns: 1fr;
            }
            .faq-grid {
                grid-template-columns: 1fr;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
            .cta-inner h2 {
                font-size: 22px;
            }
            .cta-buttons {
                flex-direction: column;
            }
        }
    </style>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php pp_pro_header( 'kontakt' ); ?>

<!-- PAGE HERO -->
<section class="page-hero--kontakt">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Strona glowna</a>
            <span class="sep">/</span>
            <span>Kontakt</span>
        </div>
        <h1>Kontakt</h1>
        <p class="subtitle">Masz pytanie lub potrzebujesz pomocy? Napisz do nas — odpowiadamy w ciagu 24 godzin w dni robocze.</p>
    </div>
</section>

<!-- CONTACT SECTION -->
<section class="contact-section">
    <div class="container">
        <div class="contact-grid">

            <!-- LEFT: CONTACT FORM -->
            <div class="contact-form-card">
                <h2>Wyslij wiadomosc</h2>
                <p class="form-desc">Wypelnij formularz, a nasz zespol odpowie tak szybko, jak to mozliwe.</p>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="pp_contact_form">
                    <?php wp_nonce_field( 'pp_contact_form_nonce', '_pp_nonce' ); ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="pp-name">Imie i nazwisko <span class="required">*</span></label>
                            <input type="text" id="pp-name" name="pp_name" placeholder="Jan Kowalski" required>
                        </div>
                        <div class="form-group">
                            <label for="pp-email">Email <span class="required">*</span></label>
                            <input type="email" id="pp-email" name="pp_email" placeholder="jan@example.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="pp-subject">Temat <span class="required">*</span></label>
                        <select id="pp-subject" name="pp_subject" required>
                            <option value="">Wybierz temat...</option>
                            <option value="pytanie-o-serwis">Pytanie o serwis</option>
                            <option value="wspolpraca">Wspolpraca / reklama</option>
                            <option value="problem-techniczny">Problem techniczny</option>
                            <option value="reklamacja">Reklamacja</option>
                            <option value="konto-specjalisty">Konto specjalisty</option>
                            <option value="propozycja-tresci">Propozycja tresci</option>
                            <option value="inne">Inne</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="pp-message">Wiadomosc <span class="required">*</span></label>
                        <textarea id="pp-message" name="pp_message" placeholder="Opisz swoje pytanie lub problem..." required></textarea>
                    </div>

                    <div class="form-consent">
                        <input type="checkbox" id="pp-consent" name="pp_consent" required>
                        <label for="pp-consent">Wyrazam zgode na przetwarzanie moich danych osobowych w celu odpowiedzi na przeslane zapytanie, zgodnie z <a href="<?php echo esc_url( home_url( '/polityka-prywatnosci/' ) ); ?>" style="color: var(--purple-primary);">Polityka prywatnosci</a>.</label>
                    </div>

                    <button type="submit" class="btn-submit">Wyslij wiadomosc</button>
                </form>
            </div>

            <!-- RIGHT: CONTACT INFO SIDEBAR -->
            <div class="contact-sidebar">

                <!-- Email -->
                <div class="sidebar-card">
                    <h3><span class="icon icon--email">&#9993;</span> Email</h3>
                    <div class="contact-item">
                        <div>
                            <div class="contact-item-label">Kontakt ogolny</div>
                            <div class="contact-item-value"><a href="mailto:kontakt@poradnik.pro">kontakt@poradnik.pro</a></div>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div>
                            <div class="contact-item-label">Wspolpraca i reklama</div>
                            <div class="contact-item-value"><a href="mailto:reklama@poradnik.pro">reklama@poradnik.pro</a></div>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div>
                            <div class="contact-item-label">Wsparcie techniczne</div>
                            <div class="contact-item-value"><a href="mailto:pomoc@poradnik.pro">pomoc@poradnik.pro</a></div>
                        </div>
                    </div>
                </div>

                <!-- Phone -->
                <div class="sidebar-card">
                    <h3><span class="icon icon--phone">&#9742;</span> Telefon</h3>
                    <div class="contact-item">
                        <div>
                            <div class="contact-item-label">Infolinia</div>
                            <div class="contact-item-value"><a href="tel:+48123456789">+48 123 456 789</a></div>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div>
                            <div class="contact-item-label">Dla specjalistow</div>
                            <div class="contact-item-value"><a href="tel:+48987654321">+48 987 654 321</a></div>
                        </div>
                    </div>
                </div>

                <!-- Hours -->
                <div class="sidebar-card">
                    <h3><span class="icon icon--hours">&#9200;</span> Godziny pracy</h3>
                    <table class="hours-table">
                        <tr>
                            <td class="day">Poniedzialek - Piatek</td>
                            <td class="time">9:00 - 17:00</td>
                        </tr>
                        <tr>
                            <td class="day">Sobota</td>
                            <td class="time">10:00 - 14:00</td>
                        </tr>
                        <tr>
                            <td class="day">Niedziela</td>
                            <td class="time">Nieczynne</td>
                        </tr>
                    </table>
                </div>

                <!-- Social Media -->
                <div class="sidebar-card">
                    <h3><span class="icon icon--social">&#9733;</span> Social media</h3>
                    <p style="font-size: 13px; color: var(--gray-500); margin-bottom: 12px;">Sledz nas w mediach spolecznosciowych</p>
                    <div class="social-links">
                        <a href="https://facebook.com/poradnikpro" class="social-link" title="Facebook" target="_blank" rel="noopener noreferrer">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="https://twitter.com/poradnikpro" class="social-link" title="Twitter / X" target="_blank" rel="noopener noreferrer">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                        <a href="https://linkedin.com/company/poradnikpro" class="social-link" title="LinkedIn" target="_blank" rel="noopener noreferrer">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                        <a href="https://youtube.com/@poradnikpro" class="social-link" title="YouTube" target="_blank" rel="noopener noreferrer">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- FAQ SECTION -->
<section class="faq-section">
    <div class="container">
        <div class="faq-header">
            <h2>Najczesciej zadawane pytania</h2>
            <p>Odpowiedzi na najpopularniejsze pytania dotyczace platformy Poradnik.pro</p>
        </div>

        <div class="faq-grid">
            <div class="faq-item">
                <div class="faq-question">Jak dlugo czekam na odpowiedz?</div>
                <p class="faq-answer">Na wiadomosci wyslane przez formularz kontaktowy odpowiadamy w ciagu 24 godzin w dni robocze. W przypadku pilnych spraw prosimy o kontakt telefoniczny w godzinach pracy biura.</p>
            </div>

            <div class="faq-item">
                <div class="faq-question">Czy korzystanie z platformy jest bezplatne?</div>
                <p class="faq-answer">Tak, dla uzytkownikow wszystkie tresci, kalkulatory, rankingi i porownania sa calkowicie bezplatne. Wyslanie zapytania do specjalisty rowniez nic nie kosztuje.</p>
            </div>

            <div class="faq-item">
                <div class="faq-question">Jak moge dolaczyc jako specjalista?</div>
                <p class="faq-answer">Przejdz do zakladki "Dla specjalistow" i zarejestruj sie. Po weryfikacji kwalifikacji Twoj profil pojawi sie w wynikach wyszukiwania i rankingach branzowych.</p>
            </div>

            <div class="faq-item">
                <div class="faq-question">Jak moge zglosic blad lub problem techniczny?</div>
                <p class="faq-answer">Wybierz temat "Problem techniczny" w formularzu powyzej i opisz dokladnie, co sie wydarzylo. Zalaczenie zrzutow ekranu znacznie przyspiesza rozwiazanie problemu.</p>
            </div>

            <div class="faq-item">
                <div class="faq-question">Czy moge usunac swoje konto?</div>
                <p class="faq-answer">Tak, w kazdej chwili mozesz usunac konto z poziomu ustawien profilu lub wysylajac prosbe na kontakt@poradnik.pro. Dane zostana usuniete w ciagu 30 dni zgodnie z RODO.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA SECTION -->
<section class="cta-section">
    <div class="container">
        <div class="cta-inner">
            <h2>Dolacz do zespolu ekspertow</h2>
            <p>Jestes specjalista i chcesz dotrzec do nowych klientow? Dolacz do ponad 5 000 zweryfikowanych ekspertow na Poradnik.pro i rozwijaj swoja dzialalnosc.</p>
            <div class="cta-buttons">
                <a href="<?php echo esc_url( home_url( '/dla-specjalistow/' ) ); ?>" class="btn-cta-primary">
                    Zarejestruj sie za darmo
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
                <a href="<?php echo esc_url( home_url( '/cennik/' ) ); ?>" class="btn-cta-secondary">
                    Zobacz cennik
                </a>
            </div>
        </div>
    </div>
</section>

<?php pp_pro_footer(); ?>

<?php wp_footer(); ?>
</body>
</html>

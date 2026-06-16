<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontakt i FAQ – Poradnik.pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ===== RESET & BASE ===== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #1a1a2e;
            background: #f8f9fc;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; height: auto; display: block; }
        button { cursor: pointer; border: none; font-family: inherit; }
        ul { list-style: none; }

        /* ===== VARIABLES ===== */
        :root {
            --purple-primary: #6c2bd9;
            --purple-dark: #1a0a3e;
            --purple-light: #8b5cf6;
            --orange-cta: #f97316;
            --orange-hover: #ea580c;
            --blue-accent: #3b82f6;
            --green-accent: #10b981;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 8px 30px rgba(0,0,0,0.12);
            --max-width: 1200px;
        }

        .container { max-width: var(--max-width); margin: 0 auto; padding: 0 24px; }

        /* ===== HEADER ===== */
        .site-header { background: #fff; border-bottom: 1px solid var(--gray-200); position: sticky; top: 0; z-index: 100; }
        .header-inner { display: flex; align-items: center; justify-content: space-between; height: 64px; }
        .logo { display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 20px; color: var(--gray-900); }
        .logo-icon { width: 32px; height: 32px; border-radius: 8px; background: var(--purple-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; }
        .main-nav { display: flex; gap: 28px; }
        .main-nav a { font-size: 14px; font-weight: 500; color: var(--gray-600); transition: color 0.2s; }
        .main-nav a:hover, .main-nav a.active { color: var(--purple-primary); }
        .header-actions { display: flex; align-items: center; gap: 12px; }
        .btn-find-specialist { background: var(--purple-primary); color: #fff; padding: 10px 20px; border-radius: 50px; font-size: 13px; font-weight: 600; transition: background 0.2s; }
        .btn-find-specialist:hover { background: var(--purple-dark); }

        /* ===== PAGE HERO ===== */
        .page-hero { background: linear-gradient(135deg, #dbeafe 0%, #f3e8ff 100%); padding: 56px 0; }
        .page-hero h1 { font-size: 32px; font-weight: 800; color: var(--gray-900); margin-bottom: 8px; }
        .page-hero p { font-size: 16px; color: var(--gray-600); max-width: 560px; }
        .breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--gray-400); margin-bottom: 16px; }
        .breadcrumb a { color: var(--gray-500); }

        /* ===== CONTACT SECTION ===== */
        .contact-section { padding: 56px 0; }
        .contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; }

        .contact-info h2 { font-size: 22px; font-weight: 700; color: var(--gray-900); margin-bottom: 16px; }
        .contact-info > p { font-size: 15px; color: var(--gray-600); margin-bottom: 32px; }

        .contact-channels { display: flex; flex-direction: column; gap: 20px; margin-bottom: 36px; }
        .contact-channel {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 20px;
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            transition: box-shadow 0.2s;
        }
        .contact-channel:hover { box-shadow: var(--shadow-sm); }
        .contact-channel-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        .contact-channel-icon.email { background: #dbeafe; }
        .contact-channel-icon.phone { background: #d1fae5; }
        .contact-channel-icon.chat { background: #f3e8ff; }
        .contact-channel-title { font-size: 14px; font-weight: 600; color: var(--gray-900); margin-bottom: 2px; }
        .contact-channel-desc { font-size: 13px; color: var(--gray-500); }
        .contact-channel-value { font-size: 14px; font-weight: 600; color: var(--purple-primary); }

        .office-info {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 24px;
        }
        .office-info h3 { font-size: 15px; font-weight: 700; color: var(--gray-900); margin-bottom: 12px; }
        .office-info p { font-size: 13px; color: var(--gray-600); margin-bottom: 4px; }

        /* ===== CONTACT FORM ===== */
        .contact-form-wrapper {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 32px;
        }
        .contact-form-wrapper h2 { font-size: 20px; font-weight: 700; color: var(--gray-900); margin-bottom: 6px; }
        .contact-form-wrapper > p { font-size: 13px; color: var(--gray-500); margin-bottom: 24px; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
        .form-group label { font-size: 13px; font-weight: 500; color: var(--gray-700); }
        .form-group input, .form-group select, .form-group textarea {
            padding: 10px 14px;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-family: inherit;
            color: var(--gray-800);
            transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--purple-primary);
            box-shadow: 0 0 0 3px rgba(108,43,217,0.1);
        }
        .form-group textarea { resize: vertical; min-height: 120px; }
        .btn-submit {
            background: var(--purple-primary);
            color: #fff;
            padding: 12px 32px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s;
            width: 100%;
        }
        .btn-submit:hover { background: var(--purple-dark); }

        /* ===== FAQ SECTION ===== */
        .faq-section { padding: 56px 0; background: #fff; border-top: 1px solid var(--gray-200); }
        .faq-section h2 { font-size: 24px; font-weight: 800; color: var(--gray-900); text-align: center; margin-bottom: 8px; }
        .faq-section > .container > p { font-size: 15px; color: var(--gray-500); text-align: center; margin-bottom: 40px; }

        .faq-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 960px; margin: 0 auto; }
        .faq-item {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 24px;
            transition: box-shadow 0.2s;
        }
        .faq-item:hover { box-shadow: var(--shadow-sm); }
        .faq-question { font-size: 15px; font-weight: 600; color: var(--gray-900); margin-bottom: 10px; display: flex; align-items: flex-start; gap: 10px; }
        .faq-question::before { content: "?"; display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: var(--purple-primary); color: #fff; font-size: 13px; font-weight: 700; flex-shrink: 0; }
        .faq-answer { font-size: 13px; color: var(--gray-600); line-height: 1.6; padding-left: 34px; }

        /* ===== FOOTER ===== */
        .site-footer { background: var(--gray-900); color: rgba(255,255,255,0.6); padding: 48px 0 24px; }
        .footer-bottom { display: flex; align-items: center; justify-content: space-between; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.1); font-size: 12px; }
        .footer-links { display: flex; gap: 20px; }
        .footer-links a { color: rgba(255,255,255,0.5); transition: color 0.2s; }
        .footer-links a:hover { color: #fff; }

        @media (max-width: 768px) {
            .contact-grid { grid-template-columns: 1fr; }
            .faq-grid { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
            .main-nav { display: none; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="site-header">
    <div class="container">
        <div class="header-inner">
            <a href="/" class="logo"><div class="logo-icon">P</div> Poradnik.pro</a>
            <nav class="main-nav">
                <a href="/poradniki">Poradniki</a>
                <a href="/porownania">Porównania</a>
                <a href="/rankingi">Rankingi</a>
                <a href="/kalkulatory">Kalkulatory</a>
                <a href="/eksperci">Eksperci</a>
            </nav>
            <div class="header-actions">
                <a href="/dla-specjalistow" class="btn-find-specialist">Dla specjalistów</a>
            </div>
        </div>
    </div>
</header>

<!-- PAGE HERO -->
<section class="page-hero">
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Strona główna</a>
            <span class="sep">/</span>
            <span>Kontakt</span>
        </div>
        <h1>📞 Kontakt</h1>
        <p>Masz pytanie? Napisz do nas — odpowiadamy w ciągu 24 godzin w dni robocze.</p>
    </div>
</section>

<!-- CONTACT SECTION -->
<section class="contact-section">
    <div class="container">
        <div class="contact-grid">
            <!-- LEFT: CONTACT INFO -->
            <div class="contact-info">
                <h2>Skontaktuj się z nami</h2>
                <p>Jesteśmy tutaj, aby pomóc. Wybierz preferowany kanał kontaktu lub wyślij wiadomość przez formularz.</p>

                <div class="contact-channels">
                    <div class="contact-channel">
                        <div class="contact-channel-icon email">📧</div>
                        <div>
                            <div class="contact-channel-title">Email</div>
                            <div class="contact-channel-desc">Odpowiedź do 24h</div>
                            <div class="contact-channel-value">kontakt@poradnik.pro</div>
                        </div>
                    </div>
                    <div class="contact-channel">
                        <div class="contact-channel-icon phone">📱</div>
                        <div>
                            <div class="contact-channel-title">Telefon</div>
                            <div class="contact-channel-desc">Pon–Pt 9:00–17:00</div>
                            <div class="contact-channel-value">+48 123 456 789</div>
                        </div>
                    </div>
                    <div class="contact-channel">
                        <div class="contact-channel-icon chat">💬</div>
                        <div>
                            <div class="contact-channel-title">Live Chat</div>
                            <div class="contact-channel-desc">Pon–Pt 9:00–18:00</div>
                            <div class="contact-channel-value">Otwórz czat →</div>
                        </div>
                    </div>
                </div>

                <div class="office-info">
                    <h3>🏢 Biuro</h3>
                    <p>Poradnik.pro sp. z o.o.</p>
                    <p>ul. Przykładowa 42, lok. 5</p>
                    <p>40-001 Katowice</p>
                    <p style="margin-top: 8px;">NIP: 123-456-78-90</p>
                    <p>KRS: 0000123456</p>
                </div>
            </div>

            <!-- RIGHT: CONTACT FORM -->
            <div class="contact-form-wrapper">
                <h2>Wyślij wiadomość</h2>
                <p>Wypełnij formularz, a odezwiemy się jak najszybciej.</p>

                <form>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Imię i nazwisko</label>
                            <input type="text" id="name" placeholder="Jan Kowalski">
                        </div>
                        <div class="form-group">
                            <label for="email">Adres email</label>
                            <input type="email" id="email" placeholder="jan@example.com">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="subject">Temat</label>
                        <select id="subject">
                            <option>Wybierz temat...</option>
                            <option>Pytanie o serwis</option>
                            <option>Współpraca / reklama</option>
                            <option>Problem techniczny</option>
                            <option>Reklamacja</option>
                            <option>Konto specjalisty</option>
                            <option>Inne</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message">Wiadomość</label>
                        <textarea id="message" placeholder="Opisz swoje pytanie lub problem..."></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Wyślij wiadomość</button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- FAQ SECTION -->
<section class="faq-section">
    <div class="container">
        <h2>❓ Najczęściej zadawane pytania</h2>
        <p>Znajdź odpowiedź na najczęstsze pytania o Poradnik.pro</p>

        <div class="faq-grid">
            <div class="faq-item">
                <div class="faq-question">Czym jest Poradnik.pro?</div>
                <p class="faq-answer">Poradnik.pro to platforma łącząca osoby szukające wiedzy i porad z zweryfikowanymi specjalistami. Oferujemy poradniki, porównania, rankingi i kalkulatory, które pomagają podejmować świadome decyzje.</p>
            </div>

            <div class="faq-item">
                <div class="faq-question">Czy korzystanie z serwisu jest bezpłatne?</div>
                <p class="faq-answer">Tak! Dla użytkowników wszystkie treści, kalkulatory i rankingi są całkowicie bezpłatne. Wysłanie zapytania do specjalisty również nic nie kosztuje.</p>
            </div>

            <div class="faq-item">
                <div class="faq-question">Jak mogę dołączyć jako specjalista?</div>
                <p class="faq-answer">Przejdź do zakładki "Dla specjalistów" i zarejestruj się bezpłatnie. Po weryfikacji Twój profil pojawi się w wynikach wyszukiwania i rankingach branżowych.</p>
            </div>

            <div class="faq-item">
                <div class="faq-question">Jak działa system opinii i rankingów?</div>
                <p class="faq-answer">Rankingi opierają się na zweryfikowanych opiniach użytkowników, którzy faktycznie skorzystali z usług specjalisty. Nie można kupić pozycji w rankingu — liczy się jakość usług.</p>
            </div>

            <div class="faq-item">
                <div class="faq-question">Czy mogę usunąć swoje konto?</div>
                <p class="faq-answer">Tak, w każdej chwili możesz usunąć konto z poziomu ustawień profilu lub wysyłając prośbę na kontakt@poradnik.pro. Dane zostaną usunięte w ciągu 30 dni zgodnie z RODO.</p>
            </div>

            <div class="faq-item">
                <div class="faq-question">Jak mogę zareklamować usługę?</div>
                <p class="faq-answer">Jeśli specjalista nie wywiązał się z umowy, skontaktuj się z nami podając szczegóły sprawy. Pomagamy w mediacji i możemy obniżyć ocenę specjalisty w uzasadnionych przypadkach.</p>
            </div>

            <div class="faq-item">
                <div class="faq-question">Skąd bierzecie dane do kalkulatorów?</div>
                <p class="faq-answer">Nasze kalkulatory opierają się na aktualnych danych rynkowych, zbieranych od tysięcy specjalistów i aktualizowanych kwartalnie. Źródła to GUS, raporty branżowe i dane własne.</p>
            </div>

            <div class="faq-item">
                <div class="faq-question">Czy treści są pisane przez ekspertów?</div>
                <p class="faq-answer">Tak! Każdy poradnik jest tworzony lub weryfikowany przez specjalistę z danej branży. Autorzy są oznaczeni przy artykule, a ich kwalifikacje są weryfikowane.</p>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-bottom">
            <span>&copy; 2026 Poradnik.pro. Wszelkie prawa zastrzeżone.</span>
            <div class="footer-links">
                <a href="#">Regulamin</a>
                <a href="#">Polityka prywatności</a>
                <a href="#">Kontakt</a>
            </div>
        </div>
    </div>
</footer>

</body>
</html>

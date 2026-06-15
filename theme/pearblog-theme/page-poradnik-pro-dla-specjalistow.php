<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dla Specjalistów – Poradnik.pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #1a1a2e;
            background: #f8f9fc;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; color: inherit; }
        button { cursor: pointer; border: none; font-family: inherit; }
        ul { list-style: none; }

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
        .logo-icon { width: 32px; height: 32px; background: linear-gradient(135deg, var(--purple-primary), var(--purple-light)); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 16px; }
        .main-nav { display: flex; align-items: center; gap: 28px; }
        .main-nav a { font-size: 14px; font-weight: 500; color: var(--gray-600); transition: color 0.2s; }
        .main-nav a:hover { color: var(--purple-primary); }
        .header-actions { display: flex; align-items: center; gap: 16px; }
        .btn-login { font-size: 14px; font-weight: 500; color: var(--gray-700); padding: 8px 16px; }
        .btn-register { background: var(--orange-cta); color: #fff; padding: 10px 20px; border-radius: 50px; font-size: 13px; font-weight: 600; transition: background 0.2s; }
        .btn-register:hover { background: var(--orange-hover); }

        /* ===== HERO ===== */
        .hero {
            background: linear-gradient(135deg, #0f0626 0%, #1a0a3e 40%, #2d1b69 100%);
            padding: 80px 0;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(ellipse at 70% 20%, rgba(139,92,246,0.15) 0%, transparent 60%);
        }
        .hero-content { position: relative; z-index: 2; text-align: center; max-width: 700px; margin: 0 auto; }
        .hero h1 { color: #fff; font-size: 38px; font-weight: 800; margin-bottom: 16px; line-height: 1.2; }
        .hero h1 span { background: linear-gradient(90deg, var(--orange-cta), #fb923c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .hero-subtitle { color: rgba(255,255,255,0.7); font-size: 17px; margin-bottom: 36px; line-height: 1.6; }
        .btn-hero {
            display: inline-block;
            background: var(--orange-cta);
            color: #fff;
            padding: 16px 40px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 700;
            transition: background 0.2s, transform 0.2s;
        }
        .btn-hero:hover { background: var(--orange-hover); transform: translateY(-1px); }
        .hero-note { color: rgba(255,255,255,0.5); font-size: 13px; margin-top: 16px; }

        /* ===== STATS ===== */
        .stats-bar {
            background: #fff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 32px 48px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
            max-width: 800px;
            margin: -40px auto 48px;
            position: relative;
            z-index: 10;
            text-align: center;
        }
        .stat-value { font-size: 28px; font-weight: 800; color: var(--purple-primary); }
        .stat-label { font-size: 13px; color: var(--gray-500); margin-top: 4px; }

        /* ===== BENEFITS ===== */
        .benefits-section { padding: 64px 0; }
        .benefits-section h2 { font-size: 26px; font-weight: 800; color: var(--gray-900); text-align: center; margin-bottom: 40px; }
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }
        .benefit-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 32px;
            text-align: center;
            transition: box-shadow 0.2s;
        }
        .benefit-card:hover { box-shadow: var(--shadow-md); }
        .benefit-icon { font-size: 40px; margin-bottom: 16px; }
        .benefit-title { font-size: 16px; font-weight: 700; color: var(--gray-900); margin-bottom: 10px; }
        .benefit-desc { font-size: 13px; color: var(--gray-500); line-height: 1.6; }

        /* ===== PRICING ===== */
        .pricing-section { padding: 64px 0; background: var(--gray-50); }
        .pricing-section h2 { font-size: 26px; font-weight: 800; color: var(--gray-900); text-align: center; margin-bottom: 12px; }
        .pricing-subtitle { font-size: 15px; color: var(--gray-500); text-align: center; margin-bottom: 40px; }
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            max-width: 1000px;
            margin: 0 auto;
        }
        .pricing-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 32px;
            text-align: center;
            transition: box-shadow 0.2s;
        }
        .pricing-card.featured {
            border: 2px solid var(--purple-primary);
            box-shadow: var(--shadow-lg);
            position: relative;
        }
        .pricing-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--purple-primary);
            color: #fff;
            padding: 4px 16px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
        }
        .pricing-name { font-size: 18px; font-weight: 700; color: var(--gray-900); margin-bottom: 8px; }
        .pricing-price { font-size: 36px; font-weight: 800; color: var(--gray-900); margin-bottom: 4px; }
        .pricing-price span { font-size: 14px; font-weight: 500; color: var(--gray-500); }
        .pricing-desc { font-size: 13px; color: var(--gray-500); margin-bottom: 24px; }
        .pricing-features { display: flex; flex-direction: column; gap: 12px; text-align: left; margin-bottom: 28px; }
        .pricing-feature { display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--gray-700); }
        .pricing-feature::before { content: '✓'; color: var(--green-accent); font-weight: 700; }
        .btn-pricing {
            display: block;
            padding: 12px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            transition: all 0.2s;
        }
        .btn-pricing.primary { background: var(--purple-primary); color: #fff; }
        .btn-pricing.primary:hover { background: var(--purple-dark); }
        .btn-pricing.outline { border: 1px solid var(--gray-300); color: var(--gray-700); background: #fff; }
        .btn-pricing.outline:hover { border-color: var(--purple-primary); color: var(--purple-primary); }

        /* ===== TESTIMONIALS ===== */
        .testimonials-section { padding: 64px 0; }
        .testimonials-section h2 { font-size: 26px; font-weight: 800; color: var(--gray-900); text-align: center; margin-bottom: 40px; }
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
        .testimonial-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 28px;
        }
        .testimonial-quote { font-size: 14px; color: var(--gray-700); line-height: 1.6; margin-bottom: 16px; font-style: italic; }
        .testimonial-author { display: flex; align-items: center; gap: 12px; }
        .testimonial-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--gray-200); display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .testimonial-name { font-size: 13px; font-weight: 600; color: var(--gray-800); }
        .testimonial-role { font-size: 12px; color: var(--gray-500); }

        /* ===== FINAL CTA ===== */
        .final-cta {
            background: linear-gradient(135deg, #1a0a3e, #6c2bd9);
            border-radius: var(--radius-lg);
            padding: 56px;
            text-align: center;
            margin: 0 24px 48px;
            max-width: calc(var(--max-width) - 48px);
            margin-left: auto;
            margin-right: auto;
        }
        .final-cta h2 { color: #fff; font-size: 28px; font-weight: 800; margin-bottom: 12px; }
        .final-cta p { color: rgba(255,255,255,0.7); font-size: 15px; margin-bottom: 28px; }
        .final-cta .btn-hero { font-size: 15px; padding: 14px 36px; }

        /* ===== FOOTER ===== */
        .site-footer { background: var(--gray-900); color: rgba(255,255,255,0.6); padding: 48px 0 24px; }
        .footer-bottom { display: flex; align-items: center; justify-content: space-between; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.1); font-size: 12px; }
        .footer-links { display: flex; gap: 20px; }
        .footer-links a { color: rgba(255,255,255,0.5); transition: color 0.2s; }
        .footer-links a:hover { color: #fff; }

        @media (max-width: 768px) {
            .benefits-grid, .pricing-grid { grid-template-columns: 1fr; }
            .testimonials-grid { grid-template-columns: 1fr; }
            .stats-bar { grid-template-columns: 1fr; padding: 24px; }
            .main-nav { display: none; }
            .hero h1 { font-size: 28px; }
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
                <a href="#" class="btn-login">Zaloguj się</a>
                <a href="#" class="btn-register">Dołącz za darmo</a>
            </div>
        </div>
    </div>
</header>

<!-- HERO -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1>Klienci już tu są.<br>Pytanie: czy Cię <span>znajdą?</span></h1>
            <p class="hero-subtitle">Dołącz do platformy, na której użytkownicy aktywnie szukają specjalistów. Widoczność w rankingach, zapytania od klientów, budowa marki eksperta.</p>
            <a href="#" class="btn-hero">Dołącz i odbieraj leady</a>
            <p class="hero-note">Bezpłatna rejestracja • Bez zobowiązań • Pierwszy miesiąc gratis</p>
        </div>
    </div>
</section>

<!-- STATS -->
<div class="stats-bar">
    <div>
        <div class="stat-value">+100 000</div>
        <div class="stat-label">użytkowników miesięcznie</div>
    </div>
    <div>
        <div class="stat-value">+20 000</div>
        <div class="stat-label">zapytań do specjalistów</div>
    </div>
    <div>
        <div class="stat-value">87%</div>
        <div class="stat-label">konwersja na kontakt</div>
    </div>
</div>

<!-- BENEFITS -->
<section class="benefits-section">
    <div class="container">
        <h2>Co zyskujesz jako specjalista?</h2>
        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="benefit-icon">📊</div>
                <h3 class="benefit-title">Widoczność w rankingach</h3>
                <p class="benefit-desc">Twój profil pojawia się w wynikach wyszukiwania i rankingach branżowych.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">📩</div>
                <h3 class="benefit-title">Zapytania od klientów</h3>
                <p class="benefit-desc">Otrzymujesz bezpośrednie zapytania od osób gotowych do współpracy.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">⭐</div>
                <h3 class="benefit-title">Budowa marki eksperta</h3>
                <p class="benefit-desc">Opinie, oceny i odznaczenia budują Twój autorytet w branży.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">📈</div>
                <h3 class="benefit-title">Statystyki i analityka</h3>
                <p class="benefit-desc">Sprawdzaj ile osób zobaczyło Twój profil i skąd przychodzą klienci.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">🎯</div>
                <h3 class="benefit-title">Targetowane leady</h3>
                <p class="benefit-desc">Otrzymujesz zapytania dopasowane do Twojej specjalizacji i lokalizacji.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">🛡️</div>
                <h3 class="benefit-title">Odznaka zweryfikowanego</h3>
                <p class="benefit-desc">Wyróżnij się od konkurencji odznaką zaufanego specjalisty.</p>
            </div>
        </div>
    </div>
</section>

<!-- PRICING -->
<section class="pricing-section">
    <div class="container">
        <h2>Prosty cennik, jasne zasady</h2>
        <p class="pricing-subtitle">Wybierz plan dopasowany do Twoich potrzeb</p>
        <div class="pricing-grid">
            <div class="pricing-card">
                <h3 class="pricing-name">Start</h3>
                <div class="pricing-price">0 zł <span>/ mies.</span></div>
                <p class="pricing-desc">Idealne na początek</p>
                <div class="pricing-features">
                    <div class="pricing-feature">Profil specjalisty</div>
                    <div class="pricing-feature">3 zapytania / miesiąc</div>
                    <div class="pricing-feature">Podstawowa widoczność</div>
                    <div class="pricing-feature">Opinie klientów</div>
                </div>
                <a href="#" class="btn-pricing outline">Zarejestruj się</a>
            </div>

            <div class="pricing-card featured">
                <span class="pricing-badge">Najpopularniejszy</span>
                <h3 class="pricing-name">Pro</h3>
                <div class="pricing-price">149 zł <span>/ mies.</span></div>
                <p class="pricing-desc">Dla aktywnych specjalistów</p>
                <div class="pricing-features">
                    <div class="pricing-feature">Wszystko z planu Start</div>
                    <div class="pricing-feature">Nielimitowane zapytania</div>
                    <div class="pricing-feature">Priorytet w rankingach</div>
                    <div class="pricing-feature">Odznaka zweryfikowanego</div>
                    <div class="pricing-feature">Statystyki profilu</div>
                </div>
                <a href="#" class="btn-pricing primary">Wybierz Pro</a>
            </div>

            <div class="pricing-card">
                <h3 class="pricing-name">Premium</h3>
                <div class="pricing-price">349 zł <span>/ mies.</span></div>
                <p class="pricing-desc">Maksymalna widoczność</p>
                <div class="pricing-features">
                    <div class="pricing-feature">Wszystko z planu Pro</div>
                    <div class="pricing-feature">Top pozycja w rankingach</div>
                    <div class="pricing-feature">Wyróżniony profil</div>
                    <div class="pricing-feature">Dedykowany opiekun</div>
                    <div class="pricing-feature">Kampanie marketingowe</div>
                </div>
                <a href="#" class="btn-pricing outline">Wybierz Premium</a>
            </div>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<section class="testimonials-section">
    <div class="container">
        <h2>Co mówią nasi specjaliści?</h2>
        <div class="testimonials-grid">
            <div class="testimonial-card">
                <p class="testimonial-quote">"Od kiedy jestem na Poradnik.pro, mam stały napływ klientów. Nie muszę już szukać zleceń — to zlecenia znajdują mnie."</p>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">👷</div>
                    <div>
                        <div class="testimonial-name">Marek K.</div>
                        <div class="testimonial-role">Firma remontowa, Katowice</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <p class="testimonial-quote">"Platforma generuje mi 15-20 zapytań miesięcznie. Konwersja na klientów to około 40%. Lepszy ROI niż jakiekolwiek reklamy."</p>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">👨‍⚖️</div>
                    <div>
                        <div class="testimonial-name">mec. Anna W.</div>
                        <div class="testimonial-role">Kancelaria prawna, Warszawa</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FINAL CTA -->
<div class="final-cta">
    <h2>Zacznij odbierać leady już dziś</h2>
    <p>Dołącz do +20 000 specjalistów na Poradnik.pro</p>
    <a href="#" class="btn-hero">Zarejestruj się za darmo</a>
</div>

<!-- FOOTER -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-bottom">
            <span>© 2026 Poradnik.pro. Wszelkie prawa zastrzeżone.</span>
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

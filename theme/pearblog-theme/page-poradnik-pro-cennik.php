<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cennik – Pakiety dla Specjalistów – Poradnik.pro</title>
    <meta name="description" content="Wybierz pakiet dla specjalisty na Poradnik.pro: FREE, PREMIUM i PREMIUM+. Porównaj funkcje i zacznij pozyskiwać klientów.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #6c2bd9;
            --primary-dark: #5520ae;
            --primary-soft: #f3ebff;
            --orange-cta: #f97316;
            --orange-hover: #ea580c;
            --green-accent: #10b981;
            --text: #111827;
            --muted: #6b7280;
            --line: #e5e7eb;
            --bg: #f8fafc;
            --card: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
            --shadow-md: 0 8px 24px rgba(17, 24, 39, 0.08);
            --shadow-lg: 0 18px 40px rgba(108, 43, 217, 0.12);
            --radius-sm: 8px;
            --radius-md: 16px;
            --radius-lg: 24px;
            --container: 1180px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text);
            background: linear-gradient(180deg, #ffffff 0%, #faf7ff 45%, var(--bg) 100%);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; text-decoration: none; }
        ul { list-style: none; }
        button { cursor: pointer; border: none; font-family: inherit; }

        .container {
            max-width: var(--container);
            margin: 0 auto;
            padding: 0 24px;
        }

        /* ===== HEADER ===== */
        .site-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(229, 231, 235, 0.9);
        }

        .header-inner {
            min-height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            font-weight: 800;
        }

        .logo-mark {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), #9b6bff);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .nav-links {
            display: flex;
            gap: 28px;
            font-size: 14px;
            font-weight: 500;
            color: var(--muted);
        }

        .nav-links a:hover { color: var(--primary); }

        .header-cta {
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            background: var(--primary);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
        }

        /* ===== HERO ===== */
        .pricing-hero {
            text-align: center;
            padding: 64px 0 48px;
        }

        .pricing-hero h1 {
            font-size: clamp(1.75rem, 4vw, 2.5rem);
            font-weight: 800;
            margin-bottom: 16px;
        }

        .pricing-hero p {
            font-size: 17px;
            color: var(--muted);
            max-width: 560px;
            margin: 0 auto;
        }

        .pricing-toggle {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-top: 32px;
            padding: 6px;
            background: var(--bg);
            border-radius: var(--radius-sm);
            border: 1px solid var(--line);
        }

        .toggle-btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            background: transparent;
            color: var(--muted);
            transition: all 0.2s;
        }

        .toggle-btn.active {
            background: var(--card);
            color: var(--text);
            box-shadow: var(--shadow-sm);
        }

        .toggle-badge {
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 10px;
            background: #dcfce7;
            color: #166534;
        }

        /* ===== PRICING CARDS ===== */
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            padding: 0 0 64px;
            align-items: start;
        }

        .pricing-card {
            background: var(--card);
            border-radius: var(--radius-lg);
            padding: 36px 28px;
            border: 1px solid var(--line);
            position: relative;
            transition: all 0.3s;
        }

        .pricing-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-4px);
        }

        .pricing-card.featured {
            border-color: var(--primary);
            box-shadow: var(--shadow-lg);
            transform: scale(1.03);
        }

        .pricing-card.featured:hover {
            transform: scale(1.03) translateY(-4px);
        }

        .pricing-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            padding: 5px 16px;
            border-radius: 20px;
            background: linear-gradient(135deg, var(--primary), #9b6bff);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .plan-name {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .plan-desc {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 20px;
        }

        .plan-price {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .plan-price .currency { font-size: 1.2rem; vertical-align: super; }
        .plan-price .period { font-size: 14px; color: var(--muted); font-weight: 400; }

        .plan-price-free {
            font-size: 2rem;
            font-weight: 800;
            color: var(--green-accent);
            margin-bottom: 4px;
        }

        .plan-features {
            margin: 24px 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 14px;
        }

        .feature-check {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #ecfdf5;
            color: var(--green-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .feature-cross {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #fef2f2;
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .plan-cta {
            display: block;
            width: 100%;
            padding: 14px;
            border-radius: var(--radius-sm);
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            transition: all 0.2s;
        }

        .plan-cta-primary {
            background: var(--primary);
            color: #fff;
        }

        .plan-cta-primary:hover { background: var(--primary-dark); }

        .plan-cta-orange {
            background: var(--orange-cta);
            color: #fff;
        }

        .plan-cta-orange:hover { background: var(--orange-hover); }

        .plan-cta-outline {
            background: transparent;
            border: 2px solid var(--line);
            color: var(--text);
        }

        .plan-cta-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* ===== COMPARISON TABLE ===== */
        .comparison-section {
            padding: 64px 0;
            border-top: 1px solid var(--line);
        }

        .comparison-title {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 40px;
        }

        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card);
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--line);
        }

        .comparison-table th,
        .comparison-table td {
            padding: 16px 20px;
            text-align: center;
            font-size: 14px;
            border-bottom: 1px solid var(--line);
        }

        .comparison-table th {
            background: var(--bg);
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted);
        }

        .comparison-table th:first-child,
        .comparison-table td:first-child {
            text-align: left;
            font-weight: 500;
        }

        .comparison-table tr:last-child td { border-bottom: none; }

        .check-icon { color: var(--green-accent); font-weight: 700; }
        .cross-icon { color: #d1d5db; }

        /* ===== CTA SECTION ===== */
        .final-cta {
            text-align: center;
            padding: 64px 0;
        }

        .final-cta-card {
            background: linear-gradient(135deg, var(--primary), #4c1d95);
            border-radius: var(--radius-lg);
            padding: 56px 40px;
            color: #fff;
        }

        .final-cta h2 {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .final-cta p {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 28px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .final-cta-btn {
            display: inline-block;
            padding: 16px 36px;
            border-radius: var(--radius-sm);
            background: var(--orange-cta);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            transition: background 0.2s;
        }

        .final-cta-btn:hover { background: var(--orange-hover); }

        /* ===== FOOTER ===== */
        .site-footer {
            background: #1e1b4b;
            color: rgba(255,255,255,0.7);
            padding: 40px 0;
            margin-top: 48px;
        }

        .footer-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .footer-links { display: flex; gap: 20px; font-size: 13px; }
        .footer-links a:hover { color: #fff; }
        .footer-copy { font-size: 13px; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 900px) {
            .pricing-grid {
                grid-template-columns: 1fr;
                max-width: 420px;
                margin: 0 auto;
            }
            .pricing-card.featured { transform: none; }
            .pricing-card.featured:hover { transform: translateY(-4px); }
        }

        @media (max-width: 768px) {
            .nav-links { display: none; }
            .comparison-table { font-size: 12px; }
            .comparison-table th, .comparison-table td { padding: 12px 10px; }
            .final-cta-card { padding: 40px 20px; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="site-header">
    <div class="container">
        <div class="header-inner">
            <a href="/" class="logo">
                <span class="logo-mark">P</span>
                <span>Poradnik.pro</span>
            </a>
            <nav class="nav-links">
                <a href="/poradniki">Poradniki</a>
                <a href="/pytania">Pytania</a>
                <a href="/specjalisci">Specjaliści</a>
                <a href="/rankingi">Rankingi</a>
                <a href="/kalkulatory">Kalkulatory</a>
            </nav>
            <a href="/dla-specjalistow" class="header-cta">Dla Specjalistów</a>
        </div>
    </div>
</header>

<main>
    <div class="container">

        <!-- HERO -->
        <section class="pricing-hero">
            <h1>Wybierz pakiet dla siebie</h1>
            <p>Dołącz do ponad 20 000 specjalistów i zacznij pozyskiwać nowych klientów na Poradnik.pro</p>
            <div class="pricing-toggle">
                <button class="toggle-btn active">Miesięcznie</button>
                <button class="toggle-btn">Rocznie <span class="toggle-badge">-20%</span></button>
            </div>
        </section>

        <!-- PRICING CARDS: FREE / PREMIUM / PREMIUM+ -->
        <section class="pricing-grid">

            <!-- FREE -->
            <div class="pricing-card">
                <div class="plan-name">FREE</div>
                <div class="plan-desc">Idealny na start – poznaj platformę bez zobowiązań</div>
                <div class="plan-price-free">0 zł</div>
                <p style="font-size:13px;color:var(--muted);margin-bottom:0;">na zawsze</p>
                <div class="plan-features">
                    <div class="feature-item"><span class="feature-check">✓</span> Profil specjalisty</div>
                    <div class="feature-item"><span class="feature-check">✓</span> 5 odpowiedzi / miesiąc</div>
                    <div class="feature-item"><span class="feature-check">✓</span> Widoczność w wynikach</div>
                    <div class="feature-item"><span class="feature-cross">✕</span> Pozycjonowanie profilu</div>
                    <div class="feature-item"><span class="feature-cross">✕</span> Lead Engine</div>
                    <div class="feature-item"><span class="feature-cross">✕</span> Statystyki zaawansowane</div>
                    <div class="feature-item"><span class="feature-cross">✕</span> Priorytetowe wsparcie</div>
                </div>
                <a href="#" class="plan-cta plan-cta-outline">Zarejestruj się za darmo</a>
            </div>

            <!-- PREMIUM -->
            <div class="pricing-card featured">
                <div class="pricing-badge">🔥 Najpopularniejszy</div>
                <div class="plan-name">PREMIUM</div>
                <div class="plan-desc">Dla aktywnych specjalistów szukających klientów</div>
                <div class="plan-price"><span class="currency">zł</span>149<span class="period">/mies.</span></div>
                <div class="plan-features">
                    <div class="feature-item"><span class="feature-check">✓</span> Profil specjalisty</div>
                    <div class="feature-item"><span class="feature-check">✓</span> Nielimitowane odpowiedzi</div>
                    <div class="feature-item"><span class="feature-check">✓</span> Widoczność w wynikach</div>
                    <div class="feature-item"><span class="feature-check">✓</span> Pozycjonowanie profilu</div>
                    <div class="feature-item"><span class="feature-check">✓</span> Lead Engine (do 50/mies.)</div>
                    <div class="feature-item"><span class="feature-check">✓</span> Statystyki zaawansowane</div>
                    <div class="feature-item"><span class="feature-cross">✕</span> Priorytetowe wsparcie</div>
                </div>
                <a href="#" class="plan-cta plan-cta-primary">Wybierz PREMIUM</a>
            </div>

            <!-- PREMIUM+ -->
            <div class="pricing-card">
                <div class="plan-name">PREMIUM+</div>
                <div class="plan-desc">Maksymalna ekspozycja i dedykowany opiekun</div>
                <div class="plan-price"><span class="currency">zł</span>349<span class="period">/mies.</span></div>
                <div class="plan-features">
                    <div class="feature-item"><span class="feature-check">✓</span> Profil specjalisty</div>
                    <div class="feature-item"><span class="feature-check">✓</span> Nielimitowane odpowiedzi</div>
                    <div class="feature-item"><span class="feature-check">✓</span> Widoczność w wynikach</div>
                    <div class="feature-item"><span class="feature-check">✓</span> TOP pozycjonowanie</div>
                    <div class="feature-item"><span class="feature-check">✓</span> Lead Engine (nielimitowany)</div>
                    <div class="feature-item"><span class="feature-check">✓</span> Statystyki zaawansowane</div>
                    <div class="feature-item"><span class="feature-check">✓</span> Priorytetowe wsparcie 24/7</div>
                </div>
                <a href="#" class="plan-cta plan-cta-orange">Wybierz PREMIUM+</a>
            </div>

        </section>

        <!-- COMPARISON TABLE -->
        <section class="comparison-section">
            <h2 class="comparison-title">Porównanie funkcji</h2>
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th>Funkcja</th>
                        <th>FREE</th>
                        <th>PREMIUM</th>
                        <th>PREMIUM+</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Profil specjalisty</td>
                        <td><span class="check-icon">✓</span></td>
                        <td><span class="check-icon">✓</span></td>
                        <td><span class="check-icon">✓</span></td>
                    </tr>
                    <tr>
                        <td>Odpowiedzi na pytania</td>
                        <td>5/mies.</td>
                        <td>Bez limitu</td>
                        <td>Bez limitu</td>
                    </tr>
                    <tr>
                        <td>Widoczność w wynikach</td>
                        <td><span class="check-icon">✓</span></td>
                        <td><span class="check-icon">✓</span></td>
                        <td><span class="check-icon">✓</span></td>
                    </tr>
                    <tr>
                        <td>Pozycjonowanie profilu</td>
                        <td><span class="cross-icon">—</span></td>
                        <td>Standardowe</td>
                        <td>TOP pozycja</td>
                    </tr>
                    <tr>
                        <td>Lead Engine</td>
                        <td><span class="cross-icon">—</span></td>
                        <td>Do 50/mies.</td>
                        <td>Bez limitu</td>
                    </tr>
                    <tr>
                        <td>Statystyki</td>
                        <td>Podstawowe</td>
                        <td>Zaawansowane</td>
                        <td>Zaawansowane + eksport</td>
                    </tr>
                    <tr>
                        <td>Publikacja artykułów</td>
                        <td><span class="cross-icon">—</span></td>
                        <td>3/mies.</td>
                        <td>Bez limitu</td>
                    </tr>
                    <tr>
                        <td>Badge „Zweryfikowany"</td>
                        <td><span class="cross-icon">—</span></td>
                        <td><span class="check-icon">✓</span></td>
                        <td><span class="check-icon">✓</span></td>
                    </tr>
                    <tr>
                        <td>Badge „Premium"</td>
                        <td><span class="cross-icon">—</span></td>
                        <td><span class="check-icon">✓</span></td>
                        <td><span class="check-icon">✓</span></td>
                    </tr>
                    <tr>
                        <td>Dedykowany opiekun</td>
                        <td><span class="cross-icon">—</span></td>
                        <td><span class="cross-icon">—</span></td>
                        <td><span class="check-icon">✓</span></td>
                    </tr>
                    <tr>
                        <td>Priorytetowe wsparcie</td>
                        <td><span class="cross-icon">—</span></td>
                        <td><span class="cross-icon">—</span></td>
                        <td>24/7</td>
                    </tr>
                    <tr>
                        <td>Raporty ROI</td>
                        <td><span class="cross-icon">—</span></td>
                        <td><span class="cross-icon">—</span></td>
                        <td><span class="check-icon">✓</span></td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- CTA -->
        <section class="final-cta">
            <div class="final-cta-card">
                <h2>Gotowy, żeby rozwinąć swoją praktykę?</h2>
                <p>Dołącz do tysięcy specjalistów, którzy codziennie pozyskują nowych klientów na Poradnik.pro</p>
                <a href="#" class="final-cta-btn">Zacznij za darmo →</a>
            </div>
        </section>

    </div>
</main>

<!-- FOOTER -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-inner">
            <span class="footer-copy">© 2026 Poradnik.pro. Wszelkie prawa zastrzeżone.</span>
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

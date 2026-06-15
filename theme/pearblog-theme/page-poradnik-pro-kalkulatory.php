<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalkulatory – Poradnik.pro</title>
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
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
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
        .main-nav a.active { color: var(--purple-primary); font-weight: 600; }
        .header-actions { display: flex; align-items: center; gap: 16px; }
        .btn-find-specialist { background: var(--purple-primary); color: #fff; padding: 10px 20px; border-radius: 50px; font-size: 13px; font-weight: 600; transition: background 0.2s; }
        .btn-find-specialist:hover { background: var(--purple-dark); }

        /* ===== PAGE HERO ===== */
        .page-hero { background: linear-gradient(135deg, #dcfce7 0%, #d1fae5 100%); padding: 48px 0; }
        .page-hero h1 { font-size: 32px; font-weight: 800; color: var(--gray-900); margin-bottom: 8px; }
        .page-hero p { font-size: 16px; color: var(--gray-600); max-width: 560px; }
        .breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--gray-400); margin-bottom: 16px; }
        .breadcrumb a { color: var(--gray-500); }
        .breadcrumb a:hover { color: var(--purple-primary); }
        .breadcrumb .sep { color: var(--gray-300); }

        /* ===== CALCULATORS GRID ===== */
        .calculators-section { padding: 48px 0; }
        .calculators-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }
        .calc-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 28px;
            text-align: center;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .calc-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        .calc-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 16px;
        }
        .calc-icon.green { background: #d1fae5; }
        .calc-icon.blue { background: #dbeafe; }
        .calc-icon.orange { background: #ffedd5; }
        .calc-icon.purple { background: #f3e8ff; }
        .calc-icon.red { background: #fee2e2; }
        .calc-icon.yellow { background: #fef3c7; }
        .calc-title { font-size: 16px; font-weight: 700; color: var(--gray-900); margin-bottom: 8px; }
        .calc-desc { font-size: 13px; color: var(--gray-500); line-height: 1.5; margin-bottom: 16px; }
        .calc-stats { display: flex; align-items: center; justify-content: center; gap: 16px; font-size: 12px; color: var(--gray-400); margin-bottom: 16px; }
        .btn-calc {
            display: inline-block;
            background: var(--green-accent);
            color: #fff;
            padding: 10px 24px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-calc:hover { background: #059669; }

        /* ===== HOW IT WORKS ===== */
        .how-it-works {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 40px;
            margin-bottom: 40px;
        }
        .how-it-works h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-900);
            text-align: center;
            margin-bottom: 32px;
        }
        .steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
        }
        .step { text-align: center; }
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--purple-primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin: 0 auto 12px;
        }
        .step-title { font-size: 14px; font-weight: 600; color: var(--gray-800); margin-bottom: 6px; }
        .step-desc { font-size: 13px; color: var(--gray-500); }

        /* ===== FOOTER ===== */
        .site-footer { background: var(--gray-900); color: rgba(255,255,255,0.6); padding: 48px 0 24px; }
        .footer-bottom { display: flex; align-items: center; justify-content: space-between; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.1); font-size: 12px; }
        .footer-links { display: flex; gap: 20px; }
        .footer-links a { color: rgba(255,255,255,0.5); transition: color 0.2s; }
        .footer-links a:hover { color: #fff; }

        @media (max-width: 768px) {
            .calculators-grid { grid-template-columns: 1fr; }
            .steps { grid-template-columns: 1fr; }
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
                <a href="/kalkulatory" class="active">Kalkulatory</a>
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
            <span>Kalkulatory</span>
        </div>
        <h1>🧮 Kalkulatory</h1>
        <p>Sprawdź liczby zanim podejmiesz decyzję. Wynik w kilka sekund + dopasowani wykonawcy.</p>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="calculators-section">
    <div class="container">
        <div class="how-it-works">
            <h2>Jak działają nasze kalkulatory?</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3 class="step-title">Podaj parametry</h3>
                    <p class="step-desc">Metraż, lokalizacja, zakres prac — kilka kliknięć</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3 class="step-title">Otrzymaj wycenę</h3>
                    <p class="step-desc">Realna kalkulacja oparta na aktualnych cenach rynkowych</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3 class="step-title">Znajdź wykonawcę</h3>
                    <p class="step-desc">Dopasowani specjaliści z Twojej okolicy</p>
                </div>
            </div>
        </div>

        <!-- CALCULATORS GRID -->
        <div class="calculators-grid">
            <a href="#" class="calc-card">
                <div class="calc-icon green">🏠</div>
                <h3 class="calc-title">Kalkulator remontu</h3>
                <p class="calc-desc">Oblicz koszt remontu mieszkania lub domu na podstawie metrażu i zakresu prac.</p>
                <div class="calc-stats">
                    <span>📊 23k użyć</span>
                    <span>⏱️ ~2 min</span>
                </div>
                <span class="btn-calc">Oblicz koszt</span>
            </a>

            <a href="#" class="calc-card">
                <div class="calc-icon blue">🏗️</div>
                <h3 class="calc-title">Kalkulator budowy domu</h3>
                <p class="calc-desc">Sprawdź realny koszt budowy domu — od fundamentów po wykończenie.</p>
                <div class="calc-stats">
                    <span>📊 18k użyć</span>
                    <span>⏱️ ~3 min</span>
                </div>
                <span class="btn-calc">Oblicz koszt</span>
            </a>

            <a href="#" class="calc-card">
                <div class="calc-icon orange">🌡️</div>
                <h3 class="calc-title">Kalkulator pompy ciepła</h3>
                <p class="calc-desc">Ile kosztuje instalacja i ile zaoszczędzisz rocznie? Policz zwrot inwestycji.</p>
                <div class="calc-stats">
                    <span>📊 15k użyć</span>
                    <span>⏱️ ~2 min</span>
                </div>
                <span class="btn-calc">Oblicz koszt</span>
            </a>

            <a href="#" class="calc-card">
                <div class="calc-icon purple">☀️</div>
                <h3 class="calc-title">Kalkulator fotowoltaiki</h3>
                <p class="calc-desc">Czy panele się opłacają? Sprawdź oszczędności i czas zwrotu inwestycji.</p>
                <div class="calc-stats">
                    <span>📊 12k użyć</span>
                    <span>⏱️ ~2 min</span>
                </div>
                <span class="btn-calc">Oblicz koszt</span>
            </a>

            <a href="#" class="calc-card">
                <div class="calc-icon red">💳</div>
                <h3 class="calc-title">Kalkulator kredytu</h3>
                <p class="calc-desc">Rata, odsetki, całkowity koszt kredytu — porównaj oferty banków.</p>
                <div class="calc-stats">
                    <span>📊 31k użyć</span>
                    <span>⏱️ ~1 min</span>
                </div>
                <span class="btn-calc">Oblicz ratę</span>
            </a>

            <a href="#" class="calc-card">
                <div class="calc-icon yellow">🚗</div>
                <h3 class="calc-title">Kalkulator kosztów auta</h3>
                <p class="calc-desc">Paliwo, ubezpieczenie, serwis — ile naprawdę kosztuje Cię samochód rocznie?</p>
                <div class="calc-stats">
                    <span>📊 9k użyć</span>
                    <span>⏱️ ~2 min</span>
                </div>
                <span class="btn-calc">Oblicz koszt</span>
            </a>

            <a href="#" class="calc-card">
                <div class="calc-icon green">🏡</div>
                <h3 class="calc-title">Kalkulator docieplenia</h3>
                <p class="calc-desc">Oblicz koszt termomodernizacji domu i roczne oszczędności na ogrzewaniu.</p>
                <div class="calc-stats">
                    <span>📊 7k użyć</span>
                    <span>⏱️ ~3 min</span>
                </div>
                <span class="btn-calc">Oblicz oszczędności</span>
            </a>

            <a href="#" class="calc-card">
                <div class="calc-icon blue">🏢</div>
                <h3 class="calc-title">Kalkulator wynajmu vs kupna</h3>
                <p class="calc-desc">Wynajem czy kupno mieszkania? Sprawdź, co się bardziej opłaca w Twojej sytuacji.</p>
                <div class="calc-stats">
                    <span>📊 14k użyć</span>
                    <span>⏱️ ~3 min</span>
                </div>
                <span class="btn-calc">Porównaj opcje</span>
            </a>

            <a href="#" class="calc-card">
                <div class="calc-icon purple">💡</div>
                <h3 class="calc-title">Kalkulator oszczędności energii</h3>
                <p class="calc-desc">Ile zaoszczędzisz wymieniając okna, ocieplając dom lub montując rekuperator?</p>
                <div class="calc-stats">
                    <span>📊 6k użyć</span>
                    <span>⏱️ ~2 min</span>
                </div>
                <span class="btn-calc">Oblicz oszczędności</span>
            </a>

            <a href="#" class="calc-card">
                <div class="calc-icon orange">⚖️</div>
                <h3 class="calc-title">Kalkulator alimentów</h3>
                <p class="calc-desc">Orientacyjna kwota alimentów na podstawie dochodów i potrzeb dziecka.</p>
                <div class="calc-stats">
                    <span>📊 19k użyć</span>
                    <span>⏱️ ~2 min</span>
                </div>
                <span class="btn-calc">Oblicz kwotę</span>
            </a>

            <a href="#" class="calc-card">
                <div class="calc-icon red">🦷</div>
                <h3 class="calc-title">Kalkulator kosztów leczenia</h3>
                <p class="calc-desc">Implanty, korony, ortodoncja — sprawdź orientacyjne koszty zabiegów stomatologicznych.</p>
                <div class="calc-stats">
                    <span>📊 8k użyć</span>
                    <span>⏱️ ~2 min</span>
                </div>
                <span class="btn-calc">Oblicz koszt</span>
            </a>
        </div>
    </div>
</section>

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

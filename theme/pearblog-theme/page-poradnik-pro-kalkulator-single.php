<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalkulator kredytu hipotecznego – Poradnik.pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --purple-primary: #6c2bd9;
            --purple-dark: #541db0;
            --purple-soft: #f4ebff;
            --green-accent: #16a34a;
            --gray-50: #fafafa;
            --gray-100: #f5f5f5;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-900: #111827;
            --shadow-sm: 0 8px 24px rgba(17, 24, 39, 0.06);
            --shadow-md: 0 18px 40px rgba(108, 43, 217, 0.12);
            --radius-md: 16px;
            --radius-lg: 24px;
            --max-width: 1200px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f7f7fb;
            color: var(--gray-900);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; text-decoration: none; }
        img { max-width: 100%; display: block; }
        button, input { font: inherit; }
        button { cursor: pointer; }

        .container {
            max-width: var(--max-width);
            margin: 0 auto;
            padding: 0 24px;
        }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.96);
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
            font-size: 1.25rem;
            font-weight: 800;
        }

        .logo-mark {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--purple-primary), #9b6bff);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 24px rgba(108, 43, 217, 0.28);
        }

        .main-nav {
            display: flex;
            align-items: center;
            gap: 28px;
            color: var(--gray-600);
            font-size: 0.95rem;
        }

        .main-nav a:hover,
        .main-nav a.active { color: var(--purple-primary); }

        .btn-primary,
        .btn-secondary,
        .sidebar-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 13px 22px;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .btn-primary {
            background: var(--purple-primary);
            color: #fff;
            border: 1px solid var(--purple-primary);
            box-shadow: 0 12px 28px rgba(108, 43, 217, 0.2);
        }

        .btn-primary:hover { background: var(--purple-dark); border-color: var(--purple-dark); }

        .btn-secondary {
            background: #fff;
            color: var(--purple-primary);
            border: 1px solid rgba(108, 43, 217, 0.24);
        }

        .btn-secondary:hover {
            background: var(--purple-soft);
            border-color: var(--purple-primary);
        }

        .page-shell {
            padding: 36px 0 72px;
        }

        .breadcrumb {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            color: var(--gray-500);
            font-size: 0.92rem;
            margin-bottom: 22px;
        }

        .breadcrumb span.sep { color: var(--gray-300); }
        .breadcrumb a:hover { color: var(--purple-primary); }

        .hero-copy {
            margin-bottom: 28px;
        }

        .hero-copy h1 {
            font-size: clamp(2rem, 4vw, 3.25rem);
            line-height: 1.08;
            margin-bottom: 10px;
        }

        .hero-copy p {
            font-size: 1.08rem;
            color: var(--gray-600);
            max-width: 700px;
        }

        .tabs {
            display: inline-flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .tab-pill {
            padding: 11px 18px;
            border-radius: 999px;
            border: 1px solid var(--gray-200);
            background: #fff;
            color: var(--gray-600);
            font-weight: 600;
        }

        .tab-pill.active {
            background: var(--purple-primary);
            border-color: var(--purple-primary);
            color: #fff;
            box-shadow: 0 14px 30px rgba(108, 43, 217, 0.24);
        }

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 3fr) minmax(320px, 2fr);
            gap: 28px;
            align-items: start;
        }

        .card {
            background: #fff;
            border: 1px solid rgba(229, 231, 235, 0.9);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }

        .calculator-card {
            padding: 30px;
        }

        .calculator-fields {
            display: grid;
            gap: 26px;
        }

        .field-row {
            display: grid;
            gap: 12px;
        }

        .field-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .field-label {
            font-size: 0.98rem;
            font-weight: 700;
        }

        .field-meta {
            color: var(--gray-500);
            font-size: 0.9rem;
        }

        .input-shell {
            position: relative;
        }

        .input-shell input[type="text"],
        .input-shell input[type="number"] {
            width: 100%;
            min-height: 58px;
            border-radius: 18px;
            border: 1px solid var(--gray-200);
            padding: 0 72px 0 18px;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--gray-900);
            background: #fff;
        }

        .input-shell input[readonly] {
            background: #fcfcff;
        }

        .suffix {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-500);
            font-weight: 700;
        }

        input[type="range"] {
            width: 100%;
            accent-color: #6c2bd9;
        }

        .results-card {
            margin-top: 30px;
            padding: 26px;
            border-radius: 22px;
            background: linear-gradient(180deg, #ffffff 0%, #faf7ff 100%);
            border: 1px solid rgba(108, 43, 217, 0.12);
            box-shadow: var(--shadow-md);
        }

        .results-title {
            font-size: 0.95rem;
            color: var(--gray-500);
            margin-bottom: 8px;
        }

        .result-main {
            font-size: clamp(2rem, 4vw, 2.75rem);
            font-weight: 800;
            color: var(--purple-primary);
            margin-bottom: 22px;
        }

        .results-list {
            display: grid;
            gap: 16px;
        }

        .result-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            color: var(--gray-600);
            border-top: 1px solid rgba(229, 231, 235, 0.8);
            padding-top: 16px;
        }

        .result-row strong {
            color: var(--gray-900);
            text-align: right;
        }

        .actions-row {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 24px;
        }

        .sidebar-stack {
            display: grid;
            gap: 24px;
        }

        .sidebar-card {
            padding: 24px;
        }

        .sidebar-card h2 {
            font-size: 1.2rem;
            margin-bottom: 18px;
        }

        .expert-list {
            display: grid;
            gap: 16px;
        }

        .expert-card {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 14px;
            padding: 18px;
            border-radius: var(--radius-md);
            border: 1px solid var(--gray-200);
            background: #fff;
        }

        .avatar {
            width: 56px;
            height: 56px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 800;
            font-size: 1rem;
            background: linear-gradient(135deg, var(--purple-primary), #9b6bff);
        }

        .expert-name {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .expert-rating {
            color: #f59e0b;
            font-size: 0.92rem;
            margin-bottom: 12px;
        }

        .expert-meta {
            color: var(--gray-500);
            font-size: 0.9rem;
            margin-bottom: 12px;
        }

        .sidebar-btn {
            width: 100%;
            background: #16a34a;
            color: #fff;
            border: 1px solid #16a34a;
        }

        .sidebar-btn:hover { background: #15803d; border-color: #15803d; }

        .link-list {
            display: grid;
            gap: 12px;
        }

        .link-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 18px;
            border-radius: 16px;
            border: 1px solid var(--gray-200);
            background: #fff;
            color: var(--gray-700);
            font-weight: 600;
            transition: 0.2s ease;
        }

        .link-item:hover {
            border-color: rgba(108, 43, 217, 0.3);
            color: var(--purple-primary);
            transform: translateX(2px);
        }

        .site-footer {
            padding: 28px 0 48px;
            color: var(--gray-500);
            font-size: 0.92rem;
        }

        .footer-inner {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            border-top: 1px solid var(--gray-200);
            padding-top: 24px;
        }

        @media (max-width: 980px) {
            .main-nav { display: none; }
            .content-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 640px) {
            .container { padding: 0 18px; }
            .header-inner { min-height: 68px; }
            .calculator-card,
            .sidebar-card { padding: 22px; }
            .field-head,
            .result-row,
            .footer-inner { display: grid; }
            .actions-row > * { flex: 1 1 100%; }
            .hero-copy p { font-size: 1rem; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-inner">
                <a href="/" class="logo">
                    <span class="logo-mark">P</span>
                    <span>Poradnik.pro</span>
                </a>

                <nav class="main-nav">
                    <a href="/poradniki">Poradniki</a>
                    <a href="/porownania">Porównania</a>
                    <a href="/rankingi">Rankingi</a>
                    <a href="/kalkulatory" class="active">Kalkulatory</a>
                </nav>

                <a href="/eksperci" class="btn-primary">Znajdź eksperta</a>
            </div>
        </div>
    </header>

    <main class="page-shell">
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="/">Strona główna</a>
                <span class="sep">&gt;</span>
                <a href="/kalkulatory">Kalkulatory</a>
                <span class="sep">&gt;</span>
                <span>Kredyt hipoteczny</span>
            </nav>

            <div class="hero-copy">
                <h1>Kalkulator kredytu hipotecznego</h1>
                <p>Oblicz ratę kredytu i całkowity koszt kredytu.</p>
            </div>

            <div class="tabs" role="tablist" aria-label="Nawigacja kalkulatora">
                <a href="#" class="tab-pill active" role="tab" aria-selected="true">Kalkulator</a>
                <a href="#" class="tab-pill" role="tab" aria-selected="false">Zdolność kredytowa</a>
                <a href="#" class="tab-pill" role="tab" aria-selected="false">Porównanie ofert</a>
            </div>

            <section class="content-grid">
                <div class="card calculator-card">
                    <div class="calculator-fields">
                        <div class="field-row">
                            <div class="field-head">
                                <label class="field-label" for="loan-amount">Kwota kredytu</label>
                                <span class="field-meta">Edytuj dowolną wartość</span>
                            </div>
                            <div class="input-shell">
                                <input id="loan-amount" type="text" inputmode="numeric" value="500 000" aria-describedby="loan-amount-help">
                                <span class="suffix">zł</span>
                            </div>
                        </div>

                        <div class="field-row">
                            <div class="field-head">
                                <label class="field-label" for="loan-years">Okres kredytowania</label>
                                <span id="loan-years-display" class="field-meta">25 lat</span>
                            </div>
                            <div class="input-shell">
                                <input id="loan-years" type="text" value="25 lat" readonly>
                            </div>
                            <input id="loan-years-range" type="range" min="5" max="35" step="1" value="25">
                        </div>

                        <div class="field-row">
                            <div class="field-head">
                                <label class="field-label" for="interest-rate">Oprocentowanie</label>
                                <span id="interest-rate-display" class="field-meta">7,25 %</span>
                            </div>
                            <div class="input-shell">
                                <input id="interest-rate" type="text" value="7,25 %" readonly>
                            </div>
                            <input id="interest-rate-range" type="range" min="2" max="12" step="0.01" value="7.25">
                        </div>
                    </div>

                    <div class="results-card">
                        <p class="results-title">Rata miesięczna</p>
                        <div class="result-main" id="monthly-payment">3 652,18 zł</div>

                        <div class="results-list">
                            <div class="result-row">
                                <span>Całkowity koszt kredytu</span>
                                <strong id="total-cost">596 637,60 zł</strong>
                            </div>
                            <div class="result-row">
                                <span>Całkowita kwota do spłaty</span>
                                <strong id="total-payment">1 096 637,60 zł</strong>
                            </div>
                        </div>
                    </div>

                    <div class="actions-row">
                        <button type="button" class="btn-secondary">Pokaż szczegóły</button>
                        <button type="button" class="btn-primary">Zapisz wynik</button>
                    </div>
                </div>

                <aside class="sidebar-stack">
                    <div class="card sidebar-card">
                        <h2>Polecani eksperci</h2>
                        <div class="expert-list">
                            <article class="expert-card">
                                <div class="avatar">AK</div>
                                <div>
                                    <div class="expert-name">Anna Kowalska</div>
                                    <div class="expert-rating">★★★★★ 4,9/5</div>
                                    <div class="expert-meta">Ekspert kredytów hipotecznych · Warszawa</div>
                                    <button type="button" class="sidebar-btn">Zadaj pytanie</button>
                                </div>
                            </article>

                            <article class="expert-card">
                                <div class="avatar" style="background: linear-gradient(135deg, #16a34a, #4ade80);">MN</div>
                                <div>
                                    <div class="expert-name">Michał Nowak</div>
                                    <div class="expert-rating">★★★★★ 4,8/5</div>
                                    <div class="expert-meta">Analityk kredytowy · Kraków</div>
                                    <button type="button" class="sidebar-btn">Zadaj pytanie</button>
                                </div>
                            </article>

                            <article class="expert-card">
                                <div class="avatar" style="background: linear-gradient(135deg, #2563eb, #60a5fa);">ES</div>
                                <div>
                                    <div class="expert-name">Ewa Sikora</div>
                                    <div class="expert-rating">★★★★★ 5,0/5</div>
                                    <div class="expert-meta">Doradca finansowy · Wrocław</div>
                                    <button type="button" class="sidebar-btn">Zadaj pytanie</button>
                                </div>
                            </article>
                        </div>
                    </div>

                    <div class="card sidebar-card">
                        <h2>Podobne kalkulatory</h2>
                        <div class="link-list">
                            <a href="#" class="link-item"><span>Zdolność kredytowa</span><span>→</span></a>
                            <a href="#" class="link-item"><span>Kalkulator OC</span><span>→</span></a>
                            <a href="#" class="link-item"><span>Koszt budowy domu</span><span>→</span></a>
                        </div>
                    </div>
                </aside>
            </section>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-inner">
                <span>© 2026 Poradnik.pro. Wszystkie prawa zastrzeżone.</span>
                <span>Porównuj oferty, licz koszty i znajdź eksperta szybciej.</span>
            </div>
        </div>
    </footer>

    <script>
        const loanAmountInput = document.getElementById('loan-amount');
        const loanYearsInput = document.getElementById('loan-years');
        const loanYearsRange = document.getElementById('loan-years-range');
        const interestRateInput = document.getElementById('interest-rate');
        const interestRateRange = document.getElementById('interest-rate-range');
        const loanYearsDisplay = document.getElementById('loan-years-display');
        const interestRateDisplay = document.getElementById('interest-rate-display');
        const monthlyPaymentEl = document.getElementById('monthly-payment');
        const totalCostEl = document.getElementById('total-cost');
        const totalPaymentEl = document.getElementById('total-payment');

        const currencyFormatter = new Intl.NumberFormat('pl-PL', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        const integerFormatter = new Intl.NumberFormat('pl-PL', {
            maximumFractionDigits: 0
        });

        function parseAmount(value) {
            return Number(String(value).replace(/\s+/g, '').replace(/,/g, '.').replace(/[^0-9.]/g, '')) || 0;
        }

        function formatCurrency(value) {
            return `${currencyFormatter.format(value)} zł`;
        }

        function updateAmountField() {
            const amount = parseAmount(loanAmountInput.value);
            loanAmountInput.value = integerFormatter.format(amount || 0);
        }

        function updateYearsField() {
            const years = Number(loanYearsRange.value);
            const yearsLabel = `${years} ${years === 1 ? 'rok' : years < 5 ? 'lata' : 'lat'}`;
            loanYearsInput.value = yearsLabel;
            loanYearsDisplay.textContent = yearsLabel;
        }

        function updateRateField() {
            const rate = Number(interestRateRange.value);
            const rateLabel = `${rate.toFixed(2).replace('.', ',')} %`;
            interestRateInput.value = rateLabel;
            interestRateDisplay.textContent = rateLabel;
        }

        function calculateMortgage() {
            const principal = parseAmount(loanAmountInput.value);
            const years = Number(loanYearsRange.value);
            const annualRate = Number(interestRateRange.value) / 100;
            const monthlyRate = annualRate / 12;
            const installments = years * 12;

            let monthlyPayment = 0;
            if (installments > 0 && monthlyRate > 0) {
                monthlyPayment = principal * (monthlyRate * Math.pow(1 + monthlyRate, installments)) / (Math.pow(1 + monthlyRate, installments) - 1);
            } else if (installments > 0) {
                monthlyPayment = principal / installments;
            }

            const totalPayment = monthlyPayment * installments;
            const totalCost = totalPayment - principal;

            monthlyPaymentEl.textContent = formatCurrency(monthlyPayment);
            totalCostEl.textContent = formatCurrency(totalCost);
            totalPaymentEl.textContent = formatCurrency(totalPayment);
        }

        loanAmountInput.addEventListener('input', calculateMortgage);
        loanAmountInput.addEventListener('blur', () => {
            updateAmountField();
            calculateMortgage();
        });

        loanYearsRange.addEventListener('input', () => {
            updateYearsField();
            calculateMortgage();
        });

        interestRateRange.addEventListener('input', () => {
            updateRateField();
            calculateMortgage();
        });

        updateAmountField();
        updateYearsField();
        updateRateField();
    </script>
</body>
</html>

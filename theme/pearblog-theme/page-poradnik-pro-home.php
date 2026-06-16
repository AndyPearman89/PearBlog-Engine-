<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poradnik.pro – Znajdź odpowiedź specjalisty</title>
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

        .container {
            max-width: var(--max-width);
            margin: 0 auto;
            padding: 0 24px;
        }

        /* ===== HEADER / NAV ===== */
        .site-header {
            background: #fff;
            border-bottom: 1px solid var(--gray-200);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 64px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 800;
            font-size: 20px;
            color: var(--gray-900);
        }
        .logo-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--purple-primary), var(--purple-light));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 16px;
        }
        .main-nav {
            display: flex;
            align-items: center;
            gap: 28px;
        }
        .main-nav a {
            font-size: 14px;
            font-weight: 500;
            color: var(--gray-600);
            transition: color 0.2s;
        }
        .main-nav a:hover { color: var(--purple-primary); }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .btn-search-icon {
            background: none;
            font-size: 18px;
            color: var(--gray-600);
        }
        .btn-find-specialist {
            background: var(--purple-primary);
            color: #fff;
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-find-specialist:hover { background: var(--purple-dark); }

        /* ===== HERO ===== */
        .hero {
            background: linear-gradient(135deg, #0f0626 0%, #1a0a3e 40%, #2d1b69 100%);
            padding: 64px 0 48px;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(ellipse at 70% 20%, rgba(139,92,246,0.15) 0%, transparent 60%),
                        radial-gradient(ellipse at 30% 80%, rgba(99,102,241,0.1) 0%, transparent 50%);
        }
        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }
        .hero h1 {
            color: #fff;
            font-size: 40px;
            font-weight: 800;
            margin-bottom: 12px;
            line-height: 1.2;
        }
        .hero h1 span {
            background: linear-gradient(90deg, var(--purple-light), #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-subtitle {
            color: rgba(255,255,255,0.7);
            font-size: 16px;
            margin-bottom: 32px;
        }

        /* Search bar */
        .search-bar {
            display: flex;
            align-items: center;
            background: #fff;
            border-radius: 50px;
            max-width: 680px;
            margin: 0 auto 20px;
            padding: 6px 6px 6px 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        .search-bar input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 14px;
            color: var(--gray-700);
            background: transparent;
        }
        .search-bar input::placeholder { color: var(--gray-400); }
        .search-category {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 8px 14px;
            font-size: 13px;
            color: var(--gray-600);
            border-left: 1px solid var(--gray-200);
            margin-left: 12px;
            white-space: nowrap;
        }
        .search-category::after {
            content: '▾';
            font-size: 10px;
        }
        .btn-search {
            background: var(--orange-cta);
            color: #fff;
            padding: 12px 28px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            margin-left: 8px;
            transition: background 0.2s;
        }
        .btn-search:hover { background: var(--orange-hover); }

        /* Popular tags */
        .popular-tags {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 16px;
        }
        .popular-tags span {
            color: rgba(255,255,255,0.5);
            font-size: 12px;
        }
        .popular-tags a {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.8);
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 12px;
            transition: background 0.2s;
        }
        .popular-tags a:hover {
            background: rgba(255,255,255,0.2);
        }

        /* ===== STATS BAR ===== */
        .stats-bar {
            background: #fff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: 28px 40px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            max-width: 900px;
            margin: -32px auto 48px;
            position: relative;
            z-index: 10;
        }
        .stat-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .stat-icon.purple { background: #f3e8ff; color: var(--purple-primary); }
        .stat-icon.green { background: #d1fae5; color: #059669; }
        .stat-icon.orange { background: #ffedd5; color: #ea580c; }
        .stat-icon.blue { background: #dbeafe; color: #2563eb; }
        .stat-value {
            font-size: 22px;
            font-weight: 800;
            color: var(--gray-900);
        }
        .stat-label {
            font-size: 12px;
            color: var(--gray-500);
        }

        /* ===== SECTIONS COMMON ===== */
        .section {
            padding: 48px 0;
        }
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }
        .section-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--gray-900);
        }
        .section-link {
            font-size: 13px;
            font-weight: 600;
            color: var(--purple-primary);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .section-link::after { content: '→'; }

        /* ===== CATEGORIES ===== */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }
        .category-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 20px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            transition: box-shadow 0.2s, border-color 0.2s;
        }
        .category-card:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--purple-light);
        }
        .category-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .category-icon.law { background: #ede9fe; color: #7c3aed; }
        .category-icon.finance { background: #dcfce7; color: #16a34a; }
        .category-icon.realestate { background: #fef3c7; color: #d97706; }
        .category-icon.construction { background: #fee2e2; color: #dc2626; }
        .category-icon.auto { background: #e0e7ff; color: #4f46e5; }
        .category-icon.health { background: #fce7f3; color: #db2777; }
        .category-icon.business { background: #dbeafe; color: #2563eb; }
        .category-icon.tech { background: #f0fdf4; color: #15803d; }
        .category-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 4px;
        }
        .category-meta {
            font-size: 12px;
            color: var(--gray-500);
            line-height: 1.4;
        }

        /* ===== SPECIALISTS ===== */
        .specialists-carousel {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding-bottom: 8px;
            scrollbar-width: none;
        }
        .specialists-carousel::-webkit-scrollbar { display: none; }
        .specialist-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 24px 20px;
            min-width: 200px;
            text-align: center;
            flex-shrink: 0;
            transition: box-shadow 0.2s;
        }
        .specialist-card:hover { box-shadow: var(--shadow-md); }
        .specialist-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: var(--gray-200);
            margin: 0 auto 12px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: var(--gray-500);
        }
        .specialist-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 2px;
        }
        .specialist-role {
            font-size: 12px;
            color: var(--purple-primary);
            margin-bottom: 8px;
        }
        .specialist-rating {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            font-size: 12px;
            color: var(--gray-600);
            margin-bottom: 4px;
        }
        .stars { color: #f59e0b; }
        .specialist-location {
            font-size: 11px;
            color: var(--gray-400);
            margin-bottom: 12px;
        }
        .btn-profile {
            display: inline-block;
            border: 1px solid var(--gray-300);
            border-radius: 50px;
            padding: 8px 20px;
            font-size: 12px;
            font-weight: 500;
            color: var(--gray-700);
            transition: all 0.2s;
        }
        .btn-profile:hover {
            border-color: var(--purple-primary);
            color: var(--purple-primary);
        }

        /* ===== QUESTIONS & RANKINGS ===== */
        .two-col-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        .questions-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .question-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            transition: box-shadow 0.2s;
        }
        .question-item:hover { box-shadow: var(--shadow-sm); }
        .question-left {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .question-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-top: 6px;
            flex-shrink: 0;
        }
        .question-dot.purple { background: var(--purple-primary); }
        .question-dot.orange { background: var(--orange-cta); }
        .question-dot.blue { background: var(--blue-accent); }
        .question-dot.green { background: var(--green-accent); }
        .question-text {
            font-size: 13px;
            font-weight: 500;
            color: var(--gray-800);
            margin-bottom: 2px;
        }
        .question-category {
            font-size: 11px;
            color: var(--gray-400);
        }
        .question-answers {
            text-align: right;
            flex-shrink: 0;
        }
        .question-count {
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-800);
        }
        .question-count-label {
            font-size: 10px;
            color: var(--gray-400);
        }

        /* Rankings grid */
        .rankings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .ranking-card {
            border-radius: var(--radius-md);
            overflow: hidden;
            position: relative;
            height: 140px;
            display: flex;
            align-items: flex-end;
            padding: 16px;
        }
        .ranking-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.7), rgba(0,0,0,0.1));
        }
        .ranking-card.blue-bg { background: linear-gradient(135deg, #1e3a5f, #2563eb); }
        .ranking-card.red-bg { background: linear-gradient(135deg, #5f1e1e, #dc2626); }
        .ranking-card.green-bg { background: linear-gradient(135deg, #1e5f3a, #059669); }
        .ranking-card.purple-bg { background: linear-gradient(135deg, #3b1e5f, #7c3aed); }
        .ranking-info {
            position: relative;
            z-index: 2;
            color: #fff;
        }
        .ranking-title {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .ranking-meta {
            font-size: 10px;
            opacity: 0.7;
            display: flex;
            gap: 12px;
        }

        /* ===== CALCULATORS ===== */
        .calculators-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
        }
        .calculator-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 24px 16px;
            text-align: center;
            transition: box-shadow 0.2s, border-color 0.2s;
        }
        .calculator-card:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--purple-light);
        }
        .calculator-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 12px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            background: #f3e8ff;
            color: var(--purple-primary);
        }
        .calculator-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 4px;
        }
        .calculator-desc {
            font-size: 11px;
            color: var(--gray-400);
        }

        /* ===== TESTIMONIALS ===== */
        .testimonials-section {
            background: var(--gray-50);
            padding: 48px 0;
        }
        .testimonials-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .testimonials-header h2 {
            font-size: 22px;
            font-weight: 700;
        }
        .testimonials-content {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 40px;
            align-items: center;
        }
        .rating-big {
            text-align: center;
        }
        .rating-big .number {
            font-size: 48px;
            font-weight: 800;
            color: var(--gray-900);
        }
        .rating-big .number span {
            font-size: 28px;
            font-weight: 600;
            color: var(--gray-500);
        }
        .rating-big .stars-big {
            color: #f59e0b;
            font-size: 20px;
            margin: 8px 0;
        }
        .rating-big .count {
            font-size: 12px;
            color: var(--gray-400);
        }
        .rating-big .label {
            font-size: 12px;
            color: var(--gray-500);
            margin-bottom: 4px;
        }
        .testimonials-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        .testimonial-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 20px;
        }
        .testimonial-text {
            font-size: 12px;
            color: var(--gray-600);
            margin-bottom: 12px;
            font-style: italic;
            line-height: 1.6;
        }
        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .testimonial-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: var(--gray-500);
        }
        .testimonial-name {
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-800);
        }

        /* ===== CTA BANNER ===== */
        .cta-banner {
            background: linear-gradient(135deg, #1a0a3e, #4c1d95);
            border-radius: var(--radius-xl);
            padding: 40px;
            margin: 48px 0;
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 32px;
            align-items: center;
        }
        .cta-left h2 {
            color: #fff;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .cta-left p {
            color: rgba(255,255,255,0.7);
            font-size: 13px;
        }
        .cta-form {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .cta-form-group {
            flex: 1;
        }
        .cta-form-group label {
            display: block;
            font-size: 11px;
            color: rgba(255,255,255,0.6);
            margin-bottom: 6px;
        }
        .cta-form-group select,
        .cta-form-group input {
            width: 100%;
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.1);
            color: #fff;
            font-size: 13px;
            outline: none;
        }
        .cta-form-group select option { color: #000; }
        .cta-form-group input::placeholder { color: rgba(255,255,255,0.4); }
        .btn-cta-submit {
            background: var(--orange-cta);
            color: #fff;
            padding: 12px 24px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            align-self: flex-end;
            transition: background 0.2s;
        }
        .btn-cta-submit:hover { background: var(--orange-hover); }
        .cta-badges {
            display: flex;
            gap: 20px;
            margin-top: 12px;
            grid-column: 1 / -1;
            justify-content: center;
        }
        .cta-badge {
            color: rgba(255,255,255,0.6);
            font-size: 11px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .cta-badge::before {
            content: '✓';
            color: var(--green-accent);
        }

        /* ===== POPULAR SEARCHES ===== */
        .popular-searches {
            padding: 32px 0;
        }
        .popular-searches h3 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 16px;
        }
        .search-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .search-tags a {
            padding: 8px 16px;
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 50px;
            font-size: 12px;
            color: var(--gray-600);
            transition: all 0.2s;
        }
        .search-tags a:hover {
            border-color: var(--purple-primary);
            color: var(--purple-primary);
        }

        /* ===== FOOTER ===== */
        .site-footer {
            background: #fff;
            border-top: 1px solid var(--gray-200);
            padding: 40px 0 24px;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 32px;
            margin-bottom: 32px;
        }
        .footer-col h4 {
            font-size: 13px;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 12px;
        }
        .footer-col a {
            display: block;
            font-size: 12px;
            color: var(--gray-500);
            margin-bottom: 8px;
            transition: color 0.2s;
        }
        .footer-col a:hover { color: var(--purple-primary); }
        .footer-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 24px;
            border-top: 1px solid var(--gray-200);
            font-size: 12px;
            color: var(--gray-400);
        }
        .footer-links {
            display: flex;
            gap: 20px;
        }
        .footer-links a {
            color: var(--gray-500);
            transition: color 0.2s;
        }
        .footer-links a:hover { color: var(--purple-primary); }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .categories-grid { grid-template-columns: repeat(2, 1fr); }
            .calculators-row { grid-template-columns: repeat(3, 1fr); }
            .two-col-section { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 768px) {
            .main-nav { display: none; }
            .hero h1 { font-size: 28px; }
            .stats-bar { grid-template-columns: repeat(2, 1fr); padding: 20px; }
            .categories-grid { grid-template-columns: 1fr; }
            .calculators-row { grid-template-columns: repeat(2, 1fr); }
            .cta-banner { grid-template-columns: 1fr; padding: 24px; }
            .cta-form { flex-direction: column; }
            .testimonials-content { grid-template-columns: 1fr; }
            .testimonials-cards { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

<!-- ===== HEADER ===== -->
<header class="site-header">
    <div class="container header-inner">
        <a href="#" class="logo">
            <div class="logo-icon">P</div>
            Poradnik.pro
        </a>
        <nav class="main-nav">
            <a href="#">Poradniki</a>
            <a href="#">Rankingi</a>
            <a href="#">Kalkulatory</a>
            <a href="#">Pytania i Odpowiedzi</a>
            <a href="#">Specjaliści</a>
            <a href="#">Kontakt</a>
        </nav>
        <div class="header-actions">
            <button class="btn-search-icon">🔍</button>
            <button class="btn-find-specialist">Znajdź specjalistę</button>
        </div>
    </div>
</header>

<!-- ===== HERO ===== -->
<section class="hero">
    <div class="container hero-content">
        <h1>Znajdź odpowiedź<br><span>specjalisty.</span></h1>
        <p class="hero-subtitle">Poradniki, rankingi, kalkulatory i eksperci w jednym miejscu.</p>
        <div class="search-bar">
            <input type="text" placeholder="Czego szukasz? Np. kredyt hipoteczny, działka budowlana...">
            <div class="search-category">Wszystkie kategorie</div>
            <button class="btn-search">Szukaj</button>
        </div>
        <div class="popular-tags">
            <span>Popularne wyszukiwania:</span>
            <a href="#">Kredyt hipoteczny</a>
            <a href="#">Działka budowlana</a>
            <a href="#">Rozliczenie PIT</a>
            <a href="#">Pompa ciepła</a>
            <a href="#">Zdolność kredytowa</a>
        </div>
    </div>
</section>

<!-- ===== STATS BAR ===== -->
<div class="container">
    <div class="stats-bar">
        <div class="stat-item">
            <div class="stat-icon purple">💬</div>
            <div>
                <div class="stat-value">120 000+</div>
                <div class="stat-label">odpowiedzi</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon green">👤</div>
            <div>
                <div class="stat-value">8 500+</div>
                <div class="stat-label">specjalistów</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon orange">📊</div>
            <div>
                <div class="stat-value">1 200+</div>
                <div class="stat-label">rankingów</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon blue">🧮</div>
            <div>
                <div class="stat-value">50+</div>
                <div class="stat-label">kalkulatorów</div>
            </div>
        </div>
    </div>
</div>

<!-- ===== POPULAR CATEGORIES ===== -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Popularne kategorie</h2>
            <a href="#" class="section-link">Zobacz wszystkie kategorie</a>
        </div>
        <div class="categories-grid">
            <a href="#" class="category-card">
                <div class="category-icon law">⚖️</div>
                <div>
                    <div class="category-name">Prawo</div>
                    <div class="category-meta">2 430 poradników<br>1 250 ekspertów</div>
                </div>
            </a>
            <a href="#" class="category-card">
                <div class="category-icon finance">💰</div>
                <div>
                    <div class="category-name">Finanse</div>
                    <div class="category-meta">3 120 poradników<br>1 780 ekspertów</div>
                </div>
            </a>
            <a href="#" class="category-card">
                <div class="category-icon realestate">🏠</div>
                <div>
                    <div class="category-name">Nieruchomości</div>
                    <div class="category-meta">2 850 poradników<br>1 620 ekspertów</div>
                </div>
            </a>
            <a href="#" class="category-card">
                <div class="category-icon construction">🏗️</div>
                <div>
                    <div class="category-name">Budowa domu</div>
                    <div class="category-meta">3 410 poradników<br>1 980 ekspertów</div>
                </div>
            </a>
            <a href="#" class="category-card">
                <div class="category-icon auto">🚗</div>
                <div>
                    <div class="category-name">Motoryzacja</div>
                    <div class="category-meta">1 890 poradników<br>980 ekspertów</div>
                </div>
            </a>
            <a href="#" class="category-card">
                <div class="category-icon health">❤️</div>
                <div>
                    <div class="category-name">Zdrowie</div>
                    <div class="category-meta">2 210 poradników<br>1 430 ekspertów</div>
                </div>
            </a>
            <a href="#" class="category-card">
                <div class="category-icon business">💼</div>
                <div>
                    <div class="category-name">Biznes</div>
                    <div class="category-meta">2 340 poradników<br>1 260 ekspertów</div>
                </div>
            </a>
            <a href="#" class="category-card">
                <div class="category-icon tech">💻</div>
                <div>
                    <div class="category-name">Technologia</div>
                    <div class="category-meta">1 560 poradników<br>870 ekspertów</div>
                </div>
            </a>
        </div>
    </div>
</section>

<!-- ===== SPECIALISTS ===== -->
<section class="section" style="background:#fff;">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Polecani specjaliści</h2>
            <a href="#" class="section-link">Zobacz wszystkich specjalistów</a>
        </div>
        <div class="specialists-carousel">
            <div class="specialist-card">
                <div class="specialist-avatar">👩</div>
                <div class="specialist-name">Anna Kowalska</div>
                <div class="specialist-role">Doradca kredytowy</div>
                <div class="specialist-rating">
                    <span class="stars">★★★★★</span> 4.9 (522)
                </div>
                <div class="specialist-location">📍 Warszawa</div>
                <a href="#" class="btn-profile">Zobacz profil</a>
            </div>
            <div class="specialist-card">
                <div class="specialist-avatar">👨</div>
                <div class="specialist-name">Piotr Nowak</div>
                <div class="specialist-role">Prawnik</div>
                <div class="specialist-rating">
                    <span class="stars">★★★★★</span> 4.8 (421)
                </div>
                <div class="specialist-location">📍 Kraków</div>
                <a href="#" class="btn-profile">Zobacz profil</a>
            </div>
            <div class="specialist-card">
                <div class="specialist-avatar">👩</div>
                <div class="specialist-name">Marta Wiśniewska</div>
                <div class="specialist-role">Architekt</div>
                <div class="specialist-rating">
                    <span class="stars">★★★★★</span> 4.9 (309)
                </div>
                <div class="specialist-location">📍 Wrocław</div>
                <a href="#" class="btn-profile">Zobacz profil</a>
            </div>
            <div class="specialist-card">
                <div class="specialist-avatar">👨</div>
                <div class="specialist-name">Tomasz Zieliński</div>
                <div class="specialist-role">Księgowy</div>
                <div class="specialist-rating">
                    <span class="stars">★★★★★</span> 4.8 (278)
                </div>
                <div class="specialist-location">📍 Poznań</div>
                <a href="#" class="btn-profile">Zobacz profil</a>
            </div>
            <div class="specialist-card">
                <div class="specialist-avatar">👩</div>
                <div class="specialist-name">Karolina Lewandowska</div>
                <div class="specialist-role">Specjalista ds. ubezpieczeń</div>
                <div class="specialist-rating">
                    <span class="stars">★★★★★</span> 4.9 (186)
                </div>
                <div class="specialist-location">📍 Gdańsk</div>
                <a href="#" class="btn-profile">Zobacz profil</a>
            </div>
        </div>
    </div>
</section>

<!-- ===== QUESTIONS & RANKINGS ===== -->
<section class="section">
    <div class="container">
        <div class="two-col-section">
            <!-- Questions -->
            <div>
                <div class="section-header">
                    <h2 class="section-title">Najnowsze pytania</h2>
                    <a href="#" class="section-link">Zobacz wszystkie</a>
                </div>
                <div class="questions-list">
                    <div class="question-item">
                        <div class="question-left">
                            <div class="question-dot purple"></div>
                            <div>
                                <div class="question-text">Czy mogę wybudować dom na działce rolnej?</div>
                                <div class="question-category">Nieruchomości • Prawo</div>
                            </div>
                        </div>
                        <div class="question-answers">
                            <div class="question-count">4</div>
                            <div class="question-count-label">2 godz. temu</div>
                        </div>
                    </div>
                    <div class="question-item">
                        <div class="question-left">
                            <div class="question-dot orange"></div>
                            <div>
                                <div class="question-text">Jak obniżyć ratę kredytu hipotecznego?</div>
                                <div class="question-category">Finanse</div>
                            </div>
                        </div>
                        <div class="question-answers">
                            <div class="question-count">7</div>
                            <div class="question-count-label">3 godz. temu</div>
                        </div>
                    </div>
                    <div class="question-item">
                        <div class="question-left">
                            <div class="question-dot blue"></div>
                            <div>
                                <div class="question-text">Jak rozliczyć działalność nierejestrowaną?</div>
                                <div class="question-category">Biznes • Finanse</div>
                            </div>
                        </div>
                        <div class="question-answers">
                            <div class="question-count">3</div>
                            <div class="question-count-label">5 godz. temu</div>
                        </div>
                    </div>
                    <div class="question-item">
                        <div class="question-left">
                            <div class="question-dot green"></div>
                            <div>
                                <div class="question-text">Jaka pompa ciepła do domu 150m2?</div>
                                <div class="question-category">Budowa domu • Ogrzewanie</div>
                            </div>
                        </div>
                        <div class="question-answers">
                            <div class="question-count">6</div>
                            <div class="question-count-label">6 godz. temu</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rankings -->
            <div>
                <div class="section-header">
                    <h2 class="section-title">Popularne rankingi</h2>
                    <a href="#" class="section-link">Zobacz wszystkie</a>
                </div>
                <div class="rankings-grid">
                    <div class="ranking-card blue-bg">
                        <div class="ranking-info">
                            <div class="ranking-title">Najlepsze konta osobiste 2026</div>
                            <div class="ranking-meta">
                                <span>Zaktualizowano: 12.05.2026</span>
                                <span>👁 12 540</span>
                            </div>
                        </div>
                    </div>
                    <div class="ranking-card red-bg">
                        <div class="ranking-info">
                            <div class="ranking-title">Najlepsze pompy ciepła 2026</div>
                            <div class="ranking-meta">
                                <span>Zaktualizowano: 10.05.2026</span>
                                <span>👁 8 912</span>
                            </div>
                        </div>
                    </div>
                    <div class="ranking-card green-bg">
                        <div class="ranking-info">
                            <div class="ranking-title">Najlepsze kredyty hipoteczne 2026</div>
                            <div class="ranking-meta">
                                <span>Zaktualizowano: 11.05.2026</span>
                                <span>👁 15 230</span>
                            </div>
                        </div>
                    </div>
                    <div class="ranking-card purple-bg">
                        <div class="ranking-info">
                            <div class="ranking-title">Najlepsze programy księgowe 2026</div>
                            <div class="ranking-meta">
                                <span>Zaktualizowano: 09.05.2026</span>
                                <span>👁 6 781</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== CALCULATORS ===== -->
<section class="section" style="background:#fff;">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Kalkulatory</h2>
            <a href="#" class="section-link">Zobacz wszystkie kalkulatory</a>
        </div>
        <div class="calculators-row">
            <a href="#" class="calculator-card">
                <div class="calculator-icon">🏦</div>
                <div class="calculator-name">Kredyt hipoteczny</div>
                <div class="calculator-desc">Sprawdź raty i koszty</div>
            </a>
            <a href="#" class="calculator-card">
                <div class="calculator-icon">📋</div>
                <div class="calculator-name">Zdolność kredytowa</div>
                <div class="calculator-desc">Sprawdź swoją zdolność</div>
            </a>
            <a href="#" class="calculator-card">
                <div class="calculator-icon">🏠</div>
                <div class="calculator-name">Koszt budowy domu</div>
                <div class="calculator-desc">Oblicz koszty budowy</div>
            </a>
            <a href="#" class="calculator-card">
                <div class="calculator-icon">🚗</div>
                <div class="calculator-name">Kalkulator OC</div>
                <div class="calculator-desc">Oblicz składkę OC</div>
            </a>
            <a href="#" class="calculator-card">
                <div class="calculator-icon">💰</div>
                <div class="calculator-name">Kalkulator wynagrodzeń</div>
                <div class="calculator-desc">Sprawdź wynagrodzenie netto</div>
            </a>
        </div>
    </div>
</section>

<!-- ===== TESTIMONIALS ===== -->
<section class="testimonials-section">
    <div class="container">
        <div class="testimonials-header">
            <h2>Co mówią użytkownicy?</h2>
        </div>
        <div class="testimonials-content">
            <div class="rating-big">
                <div class="label">Średnia ocena platformy</div>
                <div class="number">4.9<span>/5</span></div>
                <div class="stars-big">★★★★★</div>
                <div class="count">Na podstawie 2 450 opinii</div>
            </div>
            <div class="testimonials-cards">
                <div class="testimonial-card">
                    <div class="testimonial-text">„Świetne miejsce! Znajduję tu odpowiedzi na wszystkie moje pytania. Eksperci są naprawdę pomocni."</div>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">K</div>
                        <div class="testimonial-name">Katarzyna W.</div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-text">„Dzięki Poradnik.pro wybrałem najlepszy kredyt hipoteczny. Kalkulatory bardzo ułatwiły decyzję."</div>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">M</div>
                        <div class="testimonial-name">Michał K.</div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-text">„Zadałam pytanie i szybko dostałam konkretną odpowiedź. Polecam każdemu!"</div>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">A</div>
                        <div class="testimonial-name">Agnieszka R.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== CTA BANNER ===== -->
<div class="container">
    <div class="cta-banner">
        <div class="cta-left">
            <h2>Potrzebujesz pomocy specjalisty?</h2>
            <p>Opisz swój problem, a my dopasujemy najlepszego eksperta do Twoich potrzeb.</p>
        </div>
        <div class="cta-form">
            <div class="cta-form-group">
                <label>Wybierz branżę</label>
                <select>
                    <option>Wybierz branżę</option>
                    <option>Prawo</option>
                    <option>Finanse</option>
                    <option>Nieruchomości</option>
                    <option>Budowa domu</option>
                </select>
            </div>
            <div class="cta-form-group">
                <label>Lokalizacja</label>
                <input type="text" placeholder="Miasto lub kod pocztowy">
            </div>
            <div class="cta-form-group">
                <label>Opis problemu</label>
                <input type="text" placeholder="Opisz krótko swój problem...">
            </div>
            <button class="btn-cta-submit">Znajdź<br>eksperta</button>
        </div>
        <div class="cta-badges">
            <span class="cta-badge">Szybka odpowiedź</span>
            <span class="cta-badge">Zweryfikowani eksperci</span>
            <span class="cta-badge">Darmowa wycena</span>
        </div>
    </div>
</div>

<!-- ===== POPULAR SEARCHES ===== -->
<section class="popular-searches">
    <div class="container">
        <h3>Najpopularniejsze wyszukiwania</h3>
        <div class="search-tags">
            <a href="#">Prawo Warszawa</a>
            <a href="#">Kredyt Kraków</a>
            <a href="#">Architekt Katowice</a>
            <a href="#">Księgowy Wrocław</a>
            <a href="#">Doradca Gdańsk</a>
            <a href="#">Notariusz Poznań</a>
            <a href="#">Radca prawny Łódź</a>
            <a href="#">Prawnik online</a>
        </div>
    </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h4>Poradniki</h4>
                <a href="#">Prawo</a>
                <a href="#">Finanse</a>
                <a href="#">Nieruchomości</a>
                <a href="#">Budowa domu</a>
                <a href="#">Motoryzacja</a>
            </div>
            <div class="footer-col">
                <h4>Rankingi</h4>
                <a href="#">Kredyty</a>
                <a href="#">Konta bankowe</a>
                <a href="#">Ubezpieczenia</a>
                <a href="#">Pompy ciepła</a>
                <a href="#">Programy księgowe</a>
            </div>
            <div class="footer-col">
                <h4>Kalkulatory</h4>
                <a href="#">Kredyt hipoteczny</a>
                <a href="#">Zdolność kredytowa</a>
                <a href="#">Koszt budowy</a>
                <a href="#">Kalkulator OC</a>
                <a href="#">Wynagrodzenia</a>
            </div>
            <div class="footer-col">
                <h4>Dla specjalistów</h4>
                <a href="#">Dołącz jako ekspert</a>
                <a href="#">Panel specjalisty</a>
                <a href="#">Cennik</a>
                <a href="#">FAQ</a>
            </div>
            <div class="footer-col">
                <h4>O nas</h4>
                <a href="#">O Poradnik.pro</a>
                <a href="#">Jak to działa</a>
                <a href="#">Blog</a>
                <a href="#">Kontakt</a>
                <a href="#">Mapa strony</a>
            </div>
        </div>
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

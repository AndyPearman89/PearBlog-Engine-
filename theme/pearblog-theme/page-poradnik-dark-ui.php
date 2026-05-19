<?php
/**
 * Template Name: Poradnik.pro Dark UI Homepage
 *
 * Dark-themed homepage for Poradnik.pro with:
 * - Hero with search
 * - Category cards
 * - Comparison table
 * - Top rankings
 * - Calculator widget
 * - Lead generation forms
 *
 * @package PearBlog
 * @version 3.1.0
 */

get_header(); ?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1>Od problemu do decyzji.</h1>
        <p>Porównania, rankingi, koszty i specjaliści w jednym miejscu.</p>

        <!-- Search Bar -->
        <div class="search">
            <input type="text" placeholder="np. koszt remontu łazienki, pompa ciepła czy gaz, dobry prawnik Katowice..." id="hero-search">
            <button type="button" id="hero-search-btn">🔎 Znajdź rozwiązanie</button>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="#" class="quick-action-btn">🏗️ Budowa domu</a>
            <a href="#" class="quick-action-btn">🔨 Remont mieszkania</a>
            <a href="#" class="quick-action-btn">⚡ Elektryka</a>
            <a href="#" class="quick-action-btn">🔧 Hydraulika</a>
        </div>
    </div>
</section>

<!-- Category Cards -->
<section class="container">
    <div class="grid">
        <div class="card">
            <div class="card-icon">🏗️</div>
            <h3>Budowa i Rozbudowa</h3>
            <p>Profesjonalne firmy budowlane. Sprawdź ceny budowy domu, garażu czy przybudówki.</p>
            <a href="#" class="card-link">Zobacz oferty →</a>
        </div>

        <div class="card">
            <div class="card-icon">🔨</div>
            <h3>Remonty i Wykończenia</h3>
            <p>Kompleksowe remonty mieszkań i domów. Od projektu po wykończenie.</p>
            <a href="#" class="card-link">Zobacz oferty →</a>
        </div>

        <div class="card">
            <div class="card-icon">⚡</div>
            <h3>Instalacje</h3>
            <p>Elektryka, hydraulika, wentylacja. Certyfikowani specjaliści w Twojej okolicy.</p>
            <a href="#" class="card-link">Zobacz oferty →</a>
        </div>

        <div class="card">
            <div class="card-icon">🎨</div>
            <h3>Wykończenia</h3>
            <p>Malowanie, gładzie, podłogi, płytki. Sprawdzone firmy wykończeniowe.</p>
            <a href="#" class="card-link">Zobacz oferty →</a>
        </div>
    </div>
</section>

<!-- Comparison Table -->
<section class="comparison-section">
    <div class="container">
        <h2>Porównaj ceny usług budowlanych</h2>

        <div class="comparison-table">
            <table>
                <thead>
                    <tr>
                        <th>Usługa</th>
                        <th>Cena za m²</th>
                        <th>Czas realizacji</th>
                        <th>Gwarancja</th>
                        <th>Akcja</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Budowa domu</strong></td>
                        <td><span class="price-badge">5000-8000 zł</span></td>
                        <td>12-18 miesięcy</td>
                        <td><span class="check-icon">✓</span></td>
                        <td><a href="#" class="btn-outline">Sprawdź →</a></td>
                    </tr>
                    <tr>
                        <td><strong>Remont mieszkania</strong></td>
                        <td><span class="price-badge">1500-3500 zł</span></td>
                        <td>2-4 miesiące</td>
                        <td><span class="check-icon">✓</span></td>
                        <td><a href="#" class="btn-outline">Sprawdź →</a></td>
                    </tr>
                    <tr>
                        <td><strong>Instalacja elektryczna</strong></td>
                        <td><span class="price-badge">60-120 zł</span></td>
                        <td>1-2 tygodnie</td>
                        <td><span class="check-icon">✓</span></td>
                        <td><a href="#" class="btn-outline">Sprawdź →</a></td>
                    </tr>
                    <tr>
                        <td><strong>Dach</strong></td>
                        <td><span class="price-badge">150-350 zł</span></td>
                        <td>2-4 tygodnie</td>
                        <td><span class="check-icon">✓</span></td>
                        <td><a href="#" class="btn-outline">Sprawdź →</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Top Rankings -->
<section class="ranking-section">
    <div class="container">
        <h2>🏆 Najlepiej oceniane firmy</h2>

        <div class="ranking-list">
            <div class="ranking-card">
                <div class="ranking-position">1</div>
                <div class="ranking-content">
                    <h3>BudujemyDomy.pl</h3>
                    <div class="ranking-meta">
                        <span class="rating">⭐ 4.9</span>
                        <span class="reviews">(847 opinii)</span>
                    </div>
                    <p>Kompleksowa budowa domów jednorodzinnych. 15 lat doświadczenia na rynku.</p>
                </div>
                <a href="#" class="ranking-cta open-lead-form" data-service="budowa-domu">Otrzymaj wycenę</a>
            </div>

            <div class="ranking-card">
                <div class="ranking-position">2</div>
                <div class="ranking-content">
                    <h3>RemontPro24</h3>
                    <div class="ranking-meta">
                        <span class="rating">⭐ 4.8</span>
                        <span class="reviews">(652 opinii)</span>
                    </div>
                    <p>Remonty mieszkań i domów. Szybka realizacja, konkurencyjne ceny.</p>
                </div>
                <a href="#" class="ranking-cta open-lead-form" data-service="remont-mieszkania">Otrzymaj wycenę</a>
            </div>

            <div class="ranking-card">
                <div class="ranking-position">3</div>
                <div class="ranking-content">
                    <h3>ElektrykaPlus</h3>
                    <div class="ranking-meta">
                        <span class="rating">⭐ 4.9</span>
                        <span class="reviews">(523 opinii)</span>
                    </div>
                    <p>Certyfikowani elektrycy. Instalacje, pomiary, przeglądy elektryczne.</p>
                </div>
                <a href="#" class="ranking-cta open-lead-form" data-service="instalacja-elektryczna">Otrzymaj wycenę</a>
            </div>
        </div>
    </div>
</section>

<!-- Calculator Widget -->
<section class="calculator-section">
    <div class="container">
        <h2>🧮 Kalkulator kosztów budowy</h2>
        <p>Oblicz szacunkowy koszt Twojej inwestycji</p>

        <?php
        // Render calculator using SmartCalculatorEngine
        if (class_exists('PearBlogEngine\Content\SmartCalculatorEngine')) {
            echo \PearBlogEngine\Content\SmartCalculatorEngine::render('budowa-domu');
        } else {
            // Fallback static calculator
            ?>
            <div class="calculator-widget">
                <div class="smart-calculator" id="calc-budowa-domu" data-service="budowa-domu">
                    <form class="calculator-form">
                        <div class="form-group">
                            <label for="calc-metraz">Metraż (m²)</label>
                            <input type="number" id="calc-metraz" name="metraz" min="10" max="1000" value="100" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="calc-standard">Standard wykończenia</label>
                            <select id="calc-standard" name="standard" class="form-control">
                                <option value="podstawowy">Podstawowy</option>
                                <option value="sredni" selected>Średni</option>
                                <option value="premium">Premium</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="calc-lokalizacja">Lokalizacja</label>
                            <select id="calc-lokalizacja" name="lokalizacja" class="form-control">
                                <option value="miasto">Miasto</option>
                                <option value="przedmiescia" selected>Przedmieścia</option>
                                <option value="wies">Wieś</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="calc-typ">Typ domu</label>
                            <select id="calc-typ" name="typ" class="form-control">
                                <option value="parterowy">Parterowy</option>
                                <option value="pietrowy" selected>Piętrowy</option>
                                <option value="blizniak">Bliźniak</option>
                                <option value="szeregowy">Szeregowy</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-primary calculator-submit">
                            🧮 Oblicz koszt
                        </button>
                    </form>

                    <div class="calculator-result" style="display: none;">
                        <h3>Szacunkowy koszt:</h3>

                        <div class="result-summary">
                            <div class="cost-range">
                                <span class="cost-min"></span> - <span class="cost-max"></span> zł
                            </div>
                            <div class="cost-avg">
                                Średnia: <strong><span class="cost-avg-value"></span> zł</strong>
                            </div>
                            <div class="cost-per-unit">
                                Cena za m²: <span class="cost-per-unit-value"></span> zł/m²
                            </div>
                        </div>

                        <div class="cost-breakdown">
                            <h4>Rozbicie kosztów:</h4>
                            <table class="breakdown-table">
                                <tbody class="breakdown-items">
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>

                        <div class="calculator-cta">
                            <p>Chcesz otrzymać szczegółową wycenę od sprawdzonych firm?</p>
                            <button type="button" class="btn-success open-lead-form">
                                📩 Wyślij zapytanie → otrzymaj oferty
                            </button>
                        </div>
                    </div>

                    <div class="calculator-error" style="display: none;">
                        <p class="error-message"></p>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>
    </div>
</section>

<!-- Live Pricing Widget (Optional) -->
<?php if (class_exists('PearBlogEngine\Content\LivePricingDataLayer')) : ?>
<section class="container">
    <?php echo \PearBlogEngine\Content\LivePricingDataLayer::render('budowa-domu'); ?>
</section>
<?php endif; ?>

<!-- Final CTA -->
<section class="hero" style="padding: 60px 20px; margin-top: 64px;">
    <div class="container text-center">
        <h2 style="font-size: 36px; margin-bottom: 16px;">Nie zgaduj kosztów – otrzymaj realną wycenę</h2>
        <p style="margin-bottom: 32px;">Wypełnij formularz i skontaktujemy Cię z najlepszymi firmami</p>
        <button class="btn-primary open-lead-form" data-service="unknown" style="display: inline-block; padding: 16px 32px; font-size: 18px;">
            📩 Otrzymaj bezpłatną wycenę
        </button>
    </div>
</section>

<?php get_footer(); ?>

/**
 * Poradnik.pro — JavaScript V6
 * AI-first Decision Engine Platform interactions
 * @version 6.0.0
 */

(function () {
    'use strict';

    /* ──────────────────────────────────────────────────────────
       1. SCROLL REVEAL
       Uses IntersectionObserver for performant scroll animations.
    ────────────────────────────────────────────────────────── */
    function initScrollReveal() {
        var els = document.querySelectorAll('.v6-reveal');
        if (!els.length || !window.IntersectionObserver) {
            // Fallback: reveal all immediately
            els.forEach(function (el) { el.classList.add('v6-revealed'); });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    var delay = entry.target.dataset.delay || 0;
                    setTimeout(function () {
                        entry.target.classList.add('v6-revealed');
                    }, parseInt(delay, 10));
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

        els.forEach(function (el) { observer.observe(el); });
    }

    /* ──────────────────────────────────────────────────────────
       2. HERO AI SEARCH — animated placeholder
       Cycles through example queries to communicate platform scope.
    ────────────────────────────────────────────────────────── */
    function initAISearchPlaceholder() {
        var input = document.getElementById('v6-main-search');
        if (!input) return;

        var queries = [
            'koszt remontu łazienki 2026…',
            'pompa ciepła czy gaz — co wybrać?',
            'dobry prawnik Katowice — ranking',
            'ile kosztuje budowa domu 100m²?',
            'hydraulik Warszawa — najlepszy',
            'fotowoltaika vs sieć energetyczna',
            'mechanik Kraków — opinie i ceny',
        ];

        var idx = 0;
        var interval;
        var typing = false;

        function cyclePlaceholder() {
            if (typing || document.activeElement === input) return;
            var query = queries[idx % queries.length];
            idx++;
            var i = 0;
            input.placeholder = '';

            var typeInterval = setInterval(function () {
                if (i < query.length) {
                    input.placeholder += query[i];
                    i++;
                } else {
                    clearInterval(typeInterval);
                    setTimeout(function () {
                        erasePlaceholder(query);
                    }, 2200);
                }
            }, 55);
        }

        function erasePlaceholder(text) {
            if (typing || document.activeElement === input) return;
            var len = text.length;
            var eraseInterval = setInterval(function () {
                if (len > 0) {
                    input.placeholder = text.slice(0, --len);
                } else {
                    clearInterval(eraseInterval);
                    setTimeout(cyclePlaceholder, 400);
                }
            }, 28);
        }

        input.addEventListener('focus', function () { typing = true; });
        input.addEventListener('blur', function () {
            typing = false;
            if (!input.value) {
                setTimeout(cyclePlaceholder, 800);
            }
        });

        setTimeout(cyclePlaceholder, 1200);
    }

    /* ──────────────────────────────────────────────────────────
       3. SEARCH HINT CHIPS — auto-fill on click
    ────────────────────────────────────────────────────────── */
    function initSearchHints() {
        var input = document.getElementById('v6-main-search');
        if (!input) return;

        document.querySelectorAll('.v6-search-hint[data-query]').forEach(function (chip) {
            chip.addEventListener('click', function (e) {
                e.preventDefault();
                input.value = chip.dataset.query;
                input.focus();
                // Animate chip
                chip.style.borderColor = 'var(--v6-primary)';
                chip.style.color = 'var(--v6-text)';
                chip.style.background = 'rgba(91,140,255,0.08)';
            });
        });
    }

    /* ──────────────────────────────────────────────────────────
       4. AI ADVISOR — simulated conversational flow
    ────────────────────────────────────────────────────────── */
    function initAIAdvisor() {
        var input  = document.getElementById('v6-ai-input');
        var sendBtn = document.getElementById('v6-ai-send');
        var body   = document.getElementById('v6-ai-body');
        if (!input || !sendBtn || !body) return;

        var responses = {
            'remont': 'Rozumiem — planujesz remont. Powiedz mi: jaki jest przybliżony metraż i zakres prac? Na tej podstawie przygotuje dla Ciebie dokładną wycenę i ranking sprawdzonych wykonawców.',
            'mechanik': 'Szukasz mechanika — dobrze. Powiedz mi: w jakim mieście i jaki jest problem z pojazdem? Wyszukam dla Ciebie top 3 fachowców w Twojej okolicy z opiniami i cenami.',
            'pompa': 'Porównanie pomp ciepła vs gaz to świetna decyzja do przemyślenia. Na podstawie powierzchni domu, lokalizacji i budżetu mogę wskazać optymalny wybór. Ile ma metrów Twój dom?',
            'hydraulik': 'Potrzebujesz hydraulika — rozumiem. Podaj miasto i opisz krótko problem, a znajdę dla Ciebie dostępnych specjalistów z oceną i odpowiedzią w ciągu godziny.',
            'default': 'Rozumiem Twoje pytanie. Analizuję dostępne dane, porównania i rankingi, aby przygotować dla Ciebie najlepszą rekomendację. Chwilkę...',
        };

        function getResponse(msg) {
            msg = msg.toLowerCase();
            if (msg.indexOf('remont') >= 0) return responses['remont'];
            if (msg.indexOf('mechanik') >= 0) return responses['mechanik'];
            if (msg.indexOf('pompa') >= 0 || msg.indexOf('gaz') >= 0) return responses['pompa'];
            if (msg.indexOf('hydraulik') >= 0) return responses['hydraulik'];
            return responses['default'];
        }

        function appendUserMessage(text) {
            var el = document.createElement('div');
            el.className = 'v6-ai-msg v6-ai-msg--user';
            el.innerHTML = '<div class="v6-ai-msg__avatar v6-ai-msg__avatar--user">👤</div>' +
                           '<div class="v6-ai-msg__bubble">' + escHtml(text) + '</div>';
            body.appendChild(el);
            scrollBody();
        }

        function appendTyping() {
            var el = document.createElement('div');
            el.className = 'v6-ai-msg v6-ai-msg--ai';
            el.id = 'v6-typing';
            el.innerHTML = '<div class="v6-ai-msg__avatar v6-ai-msg__avatar--ai">🤖</div>' +
                           '<div class="v6-ai-typing">' +
                           '<div class="v6-ai-typing__dot"></div>' +
                           '<div class="v6-ai-typing__dot"></div>' +
                           '<div class="v6-ai-typing__dot"></div>' +
                           '</div>';
            body.appendChild(el);
            scrollBody();
            return el;
        }

        function appendAIMessage(text) {
            var typing = document.getElementById('v6-typing');
            if (typing) typing.remove();

            var el = document.createElement('div');
            el.className = 'v6-ai-msg v6-ai-msg--ai v6-animate-fade-up';
            el.innerHTML = '<div class="v6-ai-msg__avatar v6-ai-msg__avatar--ai">🤖</div>' +
                           '<div class="v6-ai-msg__bubble">' + escHtml(text) + '</div>';
            body.appendChild(el);
            scrollBody();
        }

        function scrollBody() {
            body.scrollTop = body.scrollHeight;
        }

        function escHtml(str) {
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function handleSend() {
            var msg = input.value.trim();
            if (!msg) return;
            input.value = '';
            appendUserMessage(msg);
            var typing = appendTyping();
            var resp = getResponse(msg);
            setTimeout(function () {
                appendAIMessage(resp);
            }, 1200 + Math.random() * 600);
        }

        sendBtn.addEventListener('click', handleSend);
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleSend();
            }
        });

        // Chip quick-send
        document.querySelectorAll('.v6-ai-chip').forEach(function (chip) {
            chip.addEventListener('click', function () {
                input.value = chip.textContent.trim();
                handleSend();
            });
        });
    }

    /* ──────────────────────────────────────────────────────────
       5. RENOVATION COST CALCULATOR
    ────────────────────────────────────────────────────────── */
    function initCalculator() {
        var areaSlider   = document.getElementById('v6-calc-area');
        var standardSlider = document.getElementById('v6-calc-standard');
        var resultEl     = document.getElementById('v6-calc-result-num');
        var areaLabel    = document.getElementById('v6-calc-area-val');
        var standardLabel = document.getElementById('v6-calc-standard-val');

        if (!areaSlider || !resultEl) return;

        var standards = { '1': 'Ekonomiczny', '2': 'Standardowy', '3': 'Premium' };
        var ratePerM2 = { '1': 1200, '2': 2000, '3': 3500 };

        function updateSliderFill(slider) {
            var min = parseFloat(slider.min) || 0;
            var max = parseFloat(slider.max) || 100;
            var val = parseFloat(slider.value) || 0;
            var pct = ((val - min) / (max - min)) * 100;
            slider.style.setProperty('--value', pct + '%');
        }

        function calculate() {
            var area     = parseInt(areaSlider.value, 10);
            var standard = standardSlider ? standardSlider.value : '2';
            var rate     = ratePerM2[standard] || 2000;
            var total    = area * rate;

            if (areaLabel) areaLabel.textContent = area + ' m²';
            if (standardLabel) standardLabel.textContent = standards[standard] || 'Standardowy';
            if (resultEl) {
                var formatted = total.toLocaleString('pl-PL') + ' zł';
                resultEl.textContent = formatted;
            }
            updateSliderFill(areaSlider);
            if (standardSlider) updateSliderFill(standardSlider);
        }

        areaSlider.addEventListener('input', calculate);
        if (standardSlider) standardSlider.addEventListener('input', calculate);
        calculate(); // Initial render
    }

    /* ──────────────────────────────────────────────────────────
       6. HEADER SCROLL BEHAVIOUR — add shadow on scroll
    ────────────────────────────────────────────────────────── */
    function initHeaderScroll() {
        var header = document.querySelector('.v6-header');
        if (!header) return;

        var ticking = false;
        window.addEventListener('scroll', function () {
            if (!ticking) {
                requestAnimationFrame(function () {
                    if (window.scrollY > 20) {
                        header.style.borderBottomColor = 'rgba(255,255,255,0.12)';
                        header.style.background = 'rgba(11,16,32,0.95)';
                    } else {
                        header.style.borderBottomColor = '';
                        header.style.background = '';
                    }
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });
    }

    /* ──────────────────────────────────────────────────────────
       7. TRENDING STRIP — pause on hover (CSS handles animation,
          JS ensures the duplicate clone is present for looping)
    ────────────────────────────────────────────────────────── */
    function initTrendingStrip() {
        var inner = document.querySelector('.v6-trending-inner');
        if (!inner) return;
        // Clone all children for seamless loop
        var items = Array.from(inner.children);
        items.forEach(function (item) {
            inner.appendChild(item.cloneNode(true));
        });
    }

    /* ──────────────────────────────────────────────────────────
       8. COMPARISON TOGGLE — switch active comparison pair
    ────────────────────────────────────────────────────────── */
    function initComparisonToggle() {
        var buttons = document.querySelectorAll('[data-compare-toggle]');
        if (!buttons.length) return;

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                buttons.forEach(function (b) {
                    b.classList.remove('v6-btn--primary');
                    b.classList.add('v6-btn--ghost');
                });
                btn.classList.remove('v6-btn--ghost');
                btn.classList.add('v6-btn--primary');

                var target = btn.dataset.compareToggle;
                document.querySelectorAll('[data-compare-panel]').forEach(function (panel) {
                    panel.hidden = panel.dataset.comparePanel !== target;
                });
            });
        });
    }

    /* ──────────────────────────────────────────────────────────
       9. DECISION CARD TILT — subtle perspective tilt on mousemove
    ────────────────────────────────────────────────────────── */
    function initCardTilt() {
        if (window.matchMedia('(hover: none)').matches) return; // Skip on touch

        document.querySelectorAll('.v6-decision-card').forEach(function (card) {
            card.addEventListener('mousemove', function (e) {
                var rect = card.getBoundingClientRect();
                var x = (e.clientX - rect.left) / rect.width - 0.5;
                var y = (e.clientY - rect.top)  / rect.height - 0.5;
                card.style.transform = 'translateY(-4px) rotateX(' + (-y * 5) + 'deg) rotateY(' + (x * 5) + 'deg)';
            });
            card.addEventListener('mouseleave', function () {
                card.style.transform = '';
            });
        });
    }

    /* ──────────────────────────────────────────────────────────
       10. BOOT
    ────────────────────────────────────────────────────────── */
    function boot() {
        initScrollReveal();
        initAISearchPlaceholder();
        initSearchHints();
        initAIAdvisor();
        initCalculator();
        initHeaderScroll();
        initTrendingStrip();
        initComparisonToggle();
        initCardTilt();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

}());

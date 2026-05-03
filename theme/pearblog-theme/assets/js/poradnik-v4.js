/**
 * Poradnik.pro V4 — Decision Engine
 * Interactive decision-making tools
 *
 * @package PearBlog
 * @version 4.0.0
 */

(function() {
    'use strict';

    /**
     * Decision Bar Handler
     */
    class DecisionBar {
        constructor() {
            this.bar = document.querySelector('.poradnik-decision-bar');
            if (!this.bar) return;

            this.init();
        }

        init() {
            // Attach event listeners to action buttons
            const actions = this.bar.querySelectorAll('[data-action]');
            actions.forEach(button => {
                button.addEventListener('click', (e) => {
                    const action = e.currentTarget.dataset.action;
                    this.handleAction(action);
                });
            });

            // Hide on scroll down, show on scroll up
            this.setupAutoHide();
        }

        handleAction(action) {
            switch (action) {
                case 'compare':
                    this.scrollToComparison();
                    break;
                case 'calculate':
                    this.scrollToCalculator();
                    break;
                case 'ask-ai':
                    this.openAIAssistant();
                    break;
            }
        }

        scrollToComparison() {
            const comparison = document.querySelector('.poradnik-comparison');
            if (comparison) {
                comparison.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        scrollToCalculator() {
            const calculator = document.querySelector('.poradnik-calculator');
            if (calculator) {
                calculator.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        openAIAssistant() {
            // Trigger AI assistant modal or chat interface
            const event = new CustomEvent('poradnik:open-ai-assistant');
            document.dispatchEvent(event);
        }

        setupAutoHide() {
            let lastScroll = 0;
            let ticking = false;

            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(() => {
                        const currentScroll = window.pageYOffset;

                        if (currentScroll > lastScroll && currentScroll > 100) {
                            // Scrolling down - hide bar
                            this.bar.style.transform = 'translateY(100%)';
                        } else {
                            // Scrolling up - show bar
                            this.bar.style.transform = 'translateY(0)';
                        }

                        lastScroll = currentScroll;
                        ticking = false;
                    });

                    ticking = true;
                }
            });
        }
    }

    /**
     * Calculator Handler
     */
    class Calculator {
        constructor() {
            this.calculators = document.querySelectorAll('.poradnik-calculator');
            if (!this.calculators.length) return;

            this.init();
        }

        init() {
            this.calculators.forEach(calculator => {
                const form = calculator.querySelector('.poradnik-calculator__form');
                if (form) {
                    form.addEventListener('submit', (e) => {
                        e.preventDefault();
                        this.handleCalculation(calculator, form);
                    });
                }
            });
        }

        handleCalculation(calculator, form) {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            // Perform calculation (customize based on calculator type)
            const result = this.calculate(data, calculator.dataset.calculatorId);

            // Display result
            this.displayResult(calculator, result);

            // Fetch and display matching experts
            this.fetchMatches(calculator, data);
        }

        calculate(data, calculatorId) {
            // Example calculation logic (customize per calculator)
            // This is a placeholder - implement specific calculation logic
            return {
                value: '15,000 zł',
                formatted: '15 000 zł',
                description: 'Szacowany koszt realizacji'
            };
        }

        displayResult(calculator, result) {
            const resultContainer = calculator.querySelector('[data-result-container]');
            const resultValue = calculator.querySelector('[data-result-value]');

            if (resultContainer && resultValue) {
                resultValue.textContent = result.formatted || result.value;
                resultContainer.classList.remove('poradnik-hidden');
                resultContainer.classList.add('poradnik-fade-in');

                // Scroll to result
                resultContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        async fetchMatches(calculator, data) {
            const matchesList = calculator.querySelector('[data-matches-list]');
            if (!matchesList) return;

            // Show loading state
            matchesList.innerHTML = '<div style="text-align: center; padding: 20px;">Szukamy ekspertów...</div>';

            try {
                // Fetch matching experts from API
                const response = await fetch('/wp-json/poradnik/v1/matches', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                if (response.ok) {
                    const matches = await response.json();
                    this.displayMatches(matchesList, matches);
                } else {
                    throw new Error('Failed to fetch matches');
                }
            } catch (error) {
                console.error('Error fetching matches:', error);
                matchesList.innerHTML = '<div style="text-align: center; padding: 20px;">Przepraszamy, wystąpił błąd.</div>';
            }
        }

        displayMatches(container, matches) {
            if (!matches || !matches.length) {
                container.innerHTML = '<div style="text-align: center; padding: 20px;">Nie znaleziono dopasowanych ekspertów.</div>';
                return;
            }

            const html = matches.map((match, index) => `
                <div class="poradnik-ranking__item">
                    <div class="poradnik-ranking__position">${index + 1}</div>
                    <div class="poradnik-ranking__content">
                        <h3 class="poradnik-ranking__name">${match.name}</h3>
                        <p class="poradnik-ranking__description">${match.description}</p>
                    </div>
                    <a href="${match.url}" class="poradnik-ranking__action">
                        ${match.cta || 'Zobacz profil'}
                    </a>
                </div>
            `).join('');

            container.innerHTML = html;
        }
    }

    /**
     * Ranking Filter Handler
     */
    class RankingFilters {
        constructor() {
            this.rankings = document.querySelectorAll('.poradnik-ranking');
            if (!this.rankings.length) return;

            this.init();
        }

        init() {
            this.rankings.forEach(ranking => {
                const filters = ranking.querySelectorAll('.poradnik-ranking__filter');
                filters.forEach(filter => {
                    filter.addEventListener('click', (e) => {
                        this.handleFilter(e.currentTarget, ranking);
                    });
                });
            });
        }

        handleFilter(filterButton, ranking) {
            // Update active state
            const allFilters = ranking.querySelectorAll('.poradnik-ranking__filter');
            allFilters.forEach(f => f.classList.remove('poradnik-ranking__filter--active'));
            filterButton.classList.add('poradnik-ranking__filter--active');

            // Get filter value
            const filterValue = filterButton.dataset.filter;

            // Filter ranking items (customize based on your data structure)
            this.applyFilter(ranking, filterValue);
        }

        applyFilter(ranking, filterValue) {
            const items = ranking.querySelectorAll('.poradnik-ranking__item');

            // Example: Show all items with a fade-in effect
            // In a real implementation, you'd filter based on data attributes
            items.forEach((item, index) => {
                item.style.opacity = '0';
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transition = 'opacity 0.3s ease';
                }, index * 50);
            });

            // Trigger custom event for external handlers
            const event = new CustomEvent('poradnik:ranking-filtered', {
                detail: { filterValue, ranking }
            });
            document.dispatchEvent(event);
        }
    }

    /**
     * Hero Search with Autosuggest
     */
    class HeroSearch {
        constructor() {
            this.searchInput = document.querySelector('.poradnik-hero-v4__search-input');
            if (!this.searchInput) return;

            this.init();
        }

        init() {
            let timeout;
            this.searchInput.addEventListener('input', (e) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    this.fetchSuggestions(e.target.value);
                }, 300);
            });
        }

        async fetchSuggestions(query) {
            if (query.length < 2) return;

            try {
                const response = await fetch(`/wp-json/poradnik/v1/search-suggestions?q=${encodeURIComponent(query)}`);
                if (response.ok) {
                    const suggestions = await response.json();
                    this.displaySuggestions(suggestions);
                }
            } catch (error) {
                console.error('Error fetching suggestions:', error);
            }
        }

        displaySuggestions(suggestions) {
            // Create or update suggestions dropdown
            let dropdown = document.querySelector('.poradnik-search-suggestions');

            if (!dropdown) {
                dropdown = document.createElement('div');
                dropdown.className = 'poradnik-search-suggestions';
                this.searchInput.parentElement.appendChild(dropdown);
            }

            if (!suggestions || !suggestions.length) {
                dropdown.style.display = 'none';
                return;
            }

            const html = suggestions.map(s => `
                <a href="${s.url}" class="poradnik-search-suggestion">
                    ${s.title}
                </a>
            `).join('');

            dropdown.innerHTML = html;
            dropdown.style.display = 'block';
        }
    }

    /**
     * Scroll-based AI Suggestions
     */
    class ScrollSuggestions {
        constructor() {
            this.content = document.querySelector('.entry-content, .poradnik-content');
            if (!this.content) return;

            this.init();
        }

        init() {
            const sections = this.content.querySelectorAll('h2, h3');
            let observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.maybeShowSuggestion(entry.target);
                    }
                });
            }, { threshold: 0.5 });

            sections.forEach(section => observer.observe(section));
        }

        maybeShowSuggestion(element) {
            // Check if suggestion already shown for this section
            const suggestionShown = element.dataset.suggestionShown;
            if (suggestionShown) return;

            // Random chance to show suggestion (30%)
            if (Math.random() > 0.3) return;

            // Mark as shown
            element.dataset.suggestionShown = 'true';

            // Trigger custom event for AI suggestion
            const event = new CustomEvent('poradnik:show-ai-suggestion', {
                detail: { context: element.textContent }
            });
            document.dispatchEvent(event);
        }
    }

    /**
     * Mobile Optimization
     */
    class MobileOptimization {
        constructor() {
            this.init();
        }

        init() {
            // Detect mobile device
            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

            if (isMobile) {
                document.body.classList.add('poradnik-mobile');
                this.setupMobileGestures();
            }
        }

        setupMobileGestures() {
            // Add swipe gesture support for comparison blocks
            const comparisons = document.querySelectorAll('.poradnik-comparison');

            comparisons.forEach(comparison => {
                let startX, startY;

                comparison.addEventListener('touchstart', (e) => {
                    startX = e.touches[0].clientX;
                    startY = e.touches[0].clientY;
                });

                comparison.addEventListener('touchmove', (e) => {
                    if (!startX || !startY) return;

                    const diffX = e.touches[0].clientX - startX;
                    const diffY = e.touches[0].clientY - startY;

                    // Horizontal swipe detected
                    if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
                        // Scroll comparison horizontally
                        comparison.scrollLeft -= diffX / 2;
                    }
                });

                comparison.addEventListener('touchend', () => {
                    startX = null;
                    startY = null;
                });
            });
        }
    }

    /**
     * Initialize all V4 features
     */
    function initPoradnikV4() {
        new DecisionBar();
        new Calculator();
        new RankingFilters();
        new HeroSearch();
        new ScrollSuggestions();
        new MobileOptimization();

        // Trigger custom event
        const event = new CustomEvent('poradnik:v4-initialized');
        document.dispatchEvent(event);
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPoradnikV4);
    } else {
        initPoradnikV4();
    }

    // Export for external access
    window.PoradnikV4 = {
        DecisionBar,
        Calculator,
        RankingFilters,
        HeroSearch,
        ScrollSuggestions,
        MobileOptimization
    };
})();

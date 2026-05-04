/**
 * Poradnik V3 Front Page - Search Hub & Live Activity
 *
 * Implements:
 * - Autosuggest search with multi-type results
 * - Live activity feed simulation
 * - Popular tag interactions
 *
 * @version 3.0.0
 * @package PearBlog
 */

(function($) {
    'use strict';

    /**
     * Search Hub with Autosuggest
     */
    class SearchHub {
        constructor() {
            this.$input = $('#hub-search-input');
            this.$clear = $('#search-clear');
            this.$results = $('#search-results');
            this.$resultsInner = $('.search-results-inner');
            this.debounceTimer = null;
            this.minChars = 2;
            this.currentQuery = '';

            this.init();
        }

        init() {
            // Bind input events
            this.$input.on('input', (e) => this.handleInput(e));
            this.$input.on('focus', () => this.handleFocus());
            this.$input.on('keydown', (e) => this.handleKeydown(e));

            // Bind clear button
            this.$clear.on('click', () => this.clearSearch());

            // Bind popular tags
            $('.popular-tag').on('click', (e) => this.handlePopularTag(e));

            // Close results on outside click
            $(document).on('click', (e) => {
                if (!$(e.target).closest('.search-hub').length) {
                    this.hideResults();
                }
            });

            console.log('✅ Search Hub initialized');
        }

        handleInput(e) {
            const query = e.target.value.trim();

            // Show/hide clear button
            this.$clear.toggle(query.length > 0);

            // Clear previous timer
            clearTimeout(this.debounceTimer);

            // Hide results if query too short
            if (query.length < this.minChars) {
                this.hideResults();
                return;
            }

            // Debounce search
            this.debounceTimer = setTimeout(() => {
                this.search(query);
            }, 300);
        }

        handleFocus() {
            // Show recent results if query exists
            if (this.currentQuery.length >= this.minChars) {
                this.showResults();
            }
        }

        handleKeydown(e) {
            // Handle keyboard navigation
            if (e.key === 'Escape') {
                this.hideResults();
                this.$input.blur();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.navigateResults('down');
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.navigateResults('up');
            } else if (e.key === 'Enter') {
                e.preventDefault();
                this.selectResult();
            }
        }

        search(query) {
            this.currentQuery = query;

            // Track search
            if (window.PoradnikTracker) {
                window.PoradnikTracker.trackEvent('search', {
                    query: query,
                    source: 'hub'
                });
            }

            // Call API
            $.ajax({
                url: '/wp-json/pearblog/v3/search/suggest',
                method: 'GET',
                data: { q: query, limit: 10 },
                success: (response) => this.renderResults(response),
                error: () => this.renderError()
            });
        }

        renderResults(results) {
            if (!results || results.length === 0) {
                this.renderEmpty();
                return;
            }

            // Group by type
            const grouped = {
                poradnik: [],
                comparison: [],
                ranking: [],
                expert: []
            };

            results.forEach(item => {
                if (grouped[item.type]) {
                    grouped[item.type].push(item);
                }
            });

            // Build HTML
            let html = '';

            // Poradniki
            if (grouped.poradnik.length > 0) {
                html += '<div class="search-result-group">';
                html += '<div class="search-result-group-title">📄 Poradniki</div>';
                grouped.poradnik.forEach(item => {
                    html += this.renderResultItem(item, 'poradnik');
                });
                html += '</div>';
            }

            // Comparisons
            if (grouped.comparison.length > 0) {
                html += '<div class="search-result-group">';
                html += '<div class="search-result-group-title">🆚 Porównania</div>';
                grouped.comparison.forEach(item => {
                    html += this.renderResultItem(item, 'comparison');
                });
                html += '</div>';
            }

            // Rankings
            if (grouped.ranking.length > 0) {
                html += '<div class="search-result-group">';
                html += '<div class="search-result-group-title">🏆 Rankingi</div>';
                grouped.ranking.forEach(item => {
                    html += this.renderResultItem(item, 'ranking');
                });
                html += '</div>';
            }

            // Experts
            if (grouped.expert.length > 0) {
                html += '<div class="search-result-group">';
                html += '<div class="search-result-group-title">👨‍🔧 Wykonawcy</div>';
                grouped.expert.forEach(item => {
                    html += this.renderResultItem(item, 'expert');
                });
                html += '</div>';
            }

            this.$resultsInner.html(html);
            this.showResults();

            // Bind result clicks
            $('.search-result-item').on('click', (e) => this.handleResultClick(e));
        }

        renderResultItem(item, type) {
            const icons = {
                poradnik: '📄',
                comparison: '🆚',
                ranking: '🏆',
                expert: '👨‍🔧'
            };

            return `
                <a href="${item.url}" class="search-result-item" data-type="${type}" data-id="${item.id}">
                    <div class="search-result-icon">${icons[type]}</div>
                    <div class="search-result-content">
                        <div class="search-result-title">${this.highlightQuery(item.title)}</div>
                        ${item.excerpt ? `<div class="search-result-excerpt">${this.highlightQuery(item.excerpt)}</div>` : ''}
                    </div>
                    <div class="search-result-arrow">→</div>
                </a>
            `;
        }

        renderEmpty() {
            this.$resultsInner.html(`
                <div class="search-result-empty">
                    <p>Nie znaleziono wyników dla "<strong>${this.currentQuery}</strong>"</p>
                    <a href="/ranking/" class="btn-outline btn-sm">Przeglądaj wszystkie kategorie</a>
                </div>
            `);
            this.showResults();
        }

        renderError() {
            this.$resultsInner.html(`
                <div class="search-result-error">
                    <p>Wystąpił błąd podczas wyszukiwania. Spróbuj ponownie.</p>
                </div>
            `);
            this.showResults();
        }

        highlightQuery(text) {
            if (!this.currentQuery || !text) return text;

            const regex = new RegExp(`(${this.currentQuery})`, 'gi');
            return text.replace(regex, '<mark>$1</mark>');
        }

        handleResultClick(e) {
            const $item = $(e.currentTarget);
            const type = $item.data('type');
            const id = $item.data('id');

            // Track click
            if (window.PoradnikTracker) {
                window.PoradnikTracker.trackEvent('search_result_click', {
                    query: this.currentQuery,
                    type: type,
                    id: id
                });
            }
        }

        handlePopularTag(e) {
            e.preventDefault();
            const query = $(e.currentTarget).data('search');
            this.$input.val(query).focus();
            this.search(query);
        }

        clearSearch() {
            this.$input.val('').focus();
            this.$clear.hide();
            this.hideResults();
            this.currentQuery = '';
        }

        showResults() {
            this.$results.slideDown(200);
        }

        hideResults() {
            this.$results.slideUp(200);
        }

        navigateResults(direction) {
            const $items = $('.search-result-item');
            const $active = $items.filter('.active');

            if ($items.length === 0) return;

            let index = $active.length > 0 ? $items.index($active) : -1;

            if (direction === 'down') {
                index = (index + 1) % $items.length;
            } else {
                index = index <= 0 ? $items.length - 1 : index - 1;
            }

            $items.removeClass('active');
            $items.eq(index).addClass('active');
        }

        selectResult() {
            const $active = $('.search-result-item.active');
            if ($active.length > 0) {
                window.location.href = $active.attr('href');
            }
        }
    }

    /**
     * Live Activity Feed
     */
    class LiveActivityFeed {
        constructor() {
            this.$feed = $('#live-activity-feed');
            this.$count = $('#live-users-count');
            this.activities = [];
            this.baseCount = 247;

            this.init();
        }

        init() {
            // Start activity simulation
            this.startSimulation();

            console.log('✅ Live Activity Feed initialized');
        }

        startSimulation() {
            // Update count every 5-10 seconds
            setInterval(() => {
                this.updateUserCount();
            }, Math.random() * 5000 + 5000);

            // Add new activity every 10-20 seconds
            setInterval(() => {
                this.addActivity();
            }, Math.random() * 10000 + 10000);

            // Initial activities
            for (let i = 0; i < 3; i++) {
                setTimeout(() => this.addActivity(), i * 2000);
            }
        }

        updateUserCount() {
            // Fluctuate count by ±5
            const delta = Math.floor(Math.random() * 11) - 5;
            this.baseCount = Math.max(200, Math.min(300, this.baseCount + delta));

            this.$count.text(this.baseCount);
        }

        addActivity() {
            const activities = [
                { icon: '📄', text: 'Ktoś czyta "Budowa domu krok po kroku"', time: 'przed chwilą' },
                { icon: '🆚', text: 'Użytkownik porównuje "Styropian vs wełna mineralna"', time: '2 min temu' },
                { icon: '🏆', text: 'Ktoś przegląda "Ranking firm budowlanych Warszawa"', time: '5 min temu' },
                { icon: '🧮', text: 'Użytkownik oblicza koszt remontu mieszkania', time: '1 min temu' },
                { icon: '👨‍🔧', text: 'Ktoś kontaktuje się z wykonawcą', time: 'przed chwilą' },
                { icon: '📄', text: 'Użytkownik czyta "Remont łazienki - kompletny przewodnik"', time: '3 min temu' },
                { icon: '🆚', text: 'Ktoś porównuje "Panele vs deska"', time: '4 min temu' },
                { icon: '🏆', text: 'Użytkownik przegląda "Najlepsi elektrycy Kraków"', time: '6 min temu' }
            ];

            const activity = activities[Math.floor(Math.random() * activities.length)];

            // Create activity item
            const $item = $(`
                <div class="live-activity-item fade-in">
                    <span class="live-activity-icon">${activity.icon}</span>
                    <span class="live-activity-text">${activity.text}</span>
                    <span class="live-activity-time">${activity.time}</span>
                </div>
            `);

            // Add to feed
            this.$feed.prepend($item);

            // Keep only 3 items
            const $items = this.$feed.find('.live-activity-item');
            if ($items.length > 3) {
                $items.slice(3).fadeOut(300, function() {
                    $(this).remove();
                });
            }

            // Track activity view
            if (window.PoradnikTracker) {
                window.PoradnikTracker.trackEvent('live_activity_view', {
                    activity: activity.text
                });
            }
        }
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Initialize Search Hub
        window.SearchHub = new SearchHub();

        // Initialize Live Activity Feed
        window.LiveActivityFeed = new LiveActivityFeed();

        console.log('✅ Front Page Decision Hub initialized');
    });

})(jQuery);

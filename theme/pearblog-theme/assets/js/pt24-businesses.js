/**
 * PT24.PRO — Dynamic Business Listing Module
 *
 * Fetches and renders businesses from the PT24 REST API.
 * Supports filtering, sorting, pagination, and lazy-loading.
 *
 * @package PearBlog
 * @version 1.0.0
 */

(function() {
    'use strict';

    /**
     * PT24 Business Listing Manager
     */
    var PT24Businesses = {
        /** Current filter/pagination state */
        state: {
            service: '',
            city: '',
            page: 1,
            perPage: 10,
            sortBy: 'rating',
            sortOrder: 'desc',
            minRating: 0,
            loading: false,
            hasMore: true,
        },

        /** DOM container reference */
        container: null,
        loadMoreBtn: null,

        /**
         * Initialize the module
         *
         * @param {Object} options Configuration from wp_localize_script
         */
        init: function(options) {
            options = options || {};

            this.container = document.getElementById('pt24-businesses-list');
            if (!this.container) {
                return;
            }

            // Read initial filters from data attributes or options
            this.state.service = this.container.dataset.service || options.service || '';
            this.state.city = this.container.dataset.city || options.city || '';
            this.state.perPage = parseInt(this.container.dataset.perPage, 10) || options.perPage || 10;

            this.setupFilters();
            this.setupLoadMore();
            this.loadBusinesses();
        },

        /**
         * Setup filter controls if present
         */
        setupFilters: function() {
            var self = this;
            var filterForm = document.getElementById('pt24-businesses-filters');
            if (!filterForm) {
                return;
            }

            filterForm.addEventListener('change', function() {
                var serviceEl = filterForm.querySelector('[name="service"]');
                var cityEl = filterForm.querySelector('[name="city"]');
                var sortEl = filterForm.querySelector('[name="sort_by"]');
                var ratingEl = filterForm.querySelector('[name="min_rating"]');

                if (serviceEl) { self.state.service = serviceEl.value; }
                if (cityEl) { self.state.city = cityEl.value; }
                if (sortEl) { self.state.sortBy = sortEl.value; }
                if (ratingEl) { self.state.minRating = parseFloat(ratingEl.value) || 0; }

                // Reset pagination and reload
                self.state.page = 1;
                self.state.hasMore = true;
                self.container.innerHTML = '';
                self.loadBusinesses();
            });
        },

        /**
         * Setup load more button
         */
        setupLoadMore: function() {
            this.loadMoreBtn = document.getElementById('pt24-businesses-load-more');
            if (!this.loadMoreBtn) {
                return;
            }

            var self = this;
            this.loadMoreBtn.addEventListener('click', function() {
                if (!self.state.loading && self.state.hasMore) {
                    self.state.page++;
                    self.loadBusinesses(true);
                }
            });
        },

        /**
         * Fetch businesses from REST API
         *
         * @param {boolean} append Whether to append or replace content
         */
        loadBusinesses: function(append) {
            var config = typeof pt24ApiConfig !== 'undefined' ? pt24ApiConfig : null;
            if (!config) {
                console.warn('PT24Businesses: pt24ApiConfig not available');
                return;
            }

            if (this.state.loading) {
                return;
            }
            this.state.loading = true;
            this.setLoadingState(true);

            // Build query params
            var params = new URLSearchParams();
            if (this.state.service) { params.set('service', this.state.service); }
            if (this.state.city) { params.set('city', this.state.city); }
            params.set('page', String(this.state.page));
            params.set('per_page', String(this.state.perPage));
            params.set('sort_by', this.state.sortBy);
            params.set('sort_order', this.state.sortOrder);
            if (this.state.minRating > 0) { params.set('min_rating', String(this.state.minRating)); }

            var endpoint = config.restUrl + 'businesses?' + params.toString();
            var self = this;

            fetch(endpoint, {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': config.nonce,
                },
                credentials: 'same-origin',
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                var businesses = data.businesses || data;

                if (!append) {
                    self.container.innerHTML = '';
                }

                if (Array.isArray(businesses) && businesses.length > 0) {
                    businesses.forEach(function(biz) {
                        self.container.appendChild(self.renderCard(biz));
                    });

                    // Check if there are more pages
                    if (businesses.length < self.state.perPage) {
                        self.state.hasMore = false;
                    }
                } else {
                    self.state.hasMore = false;
                    if (!append) {
                        self.container.innerHTML = '<p class="pt24-businesses__empty">Brak firm spełniających kryteria.</p>';
                    }
                }

                self.updateLoadMoreVisibility();
            })
            .catch(function(error) {
                console.error('PT24Businesses: Failed to load businesses', error);
                if (!append) {
                    self.container.innerHTML = '<p class="pt24-businesses__error">Nie udało się załadować firm. Spróbuj ponownie.</p>';
                }
            })
            .finally(function() {
                self.state.loading = false;
                self.setLoadingState(false);
            });
        },

        /**
         * Render a single business card
         *
         * @param {Object} biz Business data from API
         * @returns {HTMLElement}
         */
        renderCard: function(biz) {
            var card = document.createElement('div');
            card.className = 'pt24-business-card';
            card.setAttribute('data-business-id', biz.id || '');

            var rating = parseFloat(biz.rating) || 0;
            var stars = this.renderStars(rating);
            var reviewsCount = parseInt(biz.reviews_count, 10) || 0;
            var city = biz.city || '';
            var services = Array.isArray(biz.services) ? biz.services.join(', ') : (biz.services || '');

            card.innerHTML =
                '<div class="pt24-business-card__header">' +
                    '<h3 class="pt24-business-card__name">' + this.escapeHtml(biz.title || biz.name || '') + '</h3>' +
                    (biz.verified ? '<span class="pt24-business-card__badge">✓ Zweryfikowana</span>' : '') +
                '</div>' +
                '<div class="pt24-business-card__rating">' +
                    '<span class="pt24-business-card__stars">' + stars + '</span>' +
                    '<span class="pt24-business-card__score">' + rating.toFixed(1) + '</span>' +
                    '<span class="pt24-business-card__reviews">(' + reviewsCount + ' opinii)</span>' +
                '</div>' +
                '<div class="pt24-business-card__meta">' +
                    (city ? '<span class="pt24-business-card__city">📍 ' + this.escapeHtml(city) + '</span>' : '') +
                    (services ? '<span class="pt24-business-card__services">' + this.escapeHtml(services) + '</span>' : '') +
                '</div>' +
                (biz.excerpt ? '<p class="pt24-business-card__excerpt">' + this.escapeHtml(biz.excerpt) + '</p>' : '') +
                '<div class="pt24-business-card__actions">' +
                    '<a href="' + this.escapeHtml(biz.url || '#') + '" class="pt24-btn pt24-btn--primary pt24-btn--sm">Zobacz profil</a>' +
                    '<a href="#pt24-lead" class="pt24-btn pt24-btn--outline pt24-btn--sm">Zamów wycenę</a>' +
                '</div>';

            return card;
        },

        /**
         * Render star rating as HTML
         *
         * @param {number} rating Rating value (0-5)
         * @returns {string} HTML string
         */
        renderStars: function(rating) {
            var html = '';
            for (var i = 1; i <= 5; i++) {
                if (rating >= i) {
                    html += '★';
                } else if (rating >= i - 0.5) {
                    html += '⯨';
                } else {
                    html += '☆';
                }
            }
            return html;
        },

        /**
         * Escape HTML to prevent XSS
         *
         * @param {string} str Input string
         * @returns {string} Escaped string
         */
        escapeHtml: function(str) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        },

        /**
         * Toggle loading visual state
         *
         * @param {boolean} loading Is loading
         */
        setLoadingState: function(loading) {
            if (loading) {
                this.container.classList.add('is-loading');
            } else {
                this.container.classList.remove('is-loading');
            }
            if (this.loadMoreBtn) {
                this.loadMoreBtn.disabled = loading;
                this.loadMoreBtn.textContent = loading ? 'Ładowanie…' : 'Załaduj więcej';
            }
        },

        /**
         * Show/hide load more button based on state
         */
        updateLoadMoreVisibility: function() {
            if (this.loadMoreBtn) {
                this.loadMoreBtn.style.display = this.state.hasMore ? '' : 'none';
            }
        },
    };

    /**
     * Initialize on DOM ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            PT24Businesses.init(typeof pt24BusinessesConfig !== 'undefined' ? pt24BusinessesConfig : {});
        });
    } else {
        PT24Businesses.init(typeof pt24BusinessesConfig !== 'undefined' ? pt24BusinessesConfig : {});
    }

    /**
     * Export to global scope
     */
    window.PT24Businesses = PT24Businesses;

})();

/**
 * PT24.PRO CTA Tracking JavaScript
 *
 * Handles click tracking, analytics, and A/B testing
 * for PT24 CTAs on Poradnik.pro
 *
 * @package PearBlog
 * @version 1.0.0
 */

(function() {
    'use strict';

    /**
     * PT24 Tracking Manager
     */
    const PT24Tracking = {
        /**
         * Initialize tracking
         */
        init: function() {
            this.setupClickTracking();
            this.setupImpressionTracking();
            this.setupVisibilityTracking();
        },

        /**
         * Setup click tracking for all PT24 CTAs
         */
        setupClickTracking: function() {
            document.addEventListener('click', function(e) {
                const target = e.target.closest('[data-pt24-track="click"]');

                if (!target) {
                    return;
                }

                // Prevent default temporarily
                e.preventDefault();

                // Get CTA container
                const ctaContainer = target.closest('.pt24-cta');

                if (!ctaContainer) {
                    // If no container, just follow the link
                    window.open(target.href, '_blank');
                    return;
                }

                // Extract tracking data
                const trackingData = {
                    cta_id: ctaContainer.dataset.ctaId || '',
                    service: ctaContainer.dataset.service || '',
                    city: ctaContainer.dataset.city || '',
                    post_id: ctaContainer.dataset.postId || '',
                    url: target.href,
                    timestamp: new Date().toISOString(),
                    viewport_width: window.innerWidth,
                    viewport_height: window.innerHeight,
                    scroll_position: window.scrollY,
                    cta_style: this.getCTAStyle(ctaContainer),
                };

                // Send tracking data
                this.sendTrackingData('click', trackingData);

                // Track with Google Analytics if available
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'pt24_cta_click', {
                        service: trackingData.service,
                        city: trackingData.city,
                        cta_style: trackingData.cta_style,
                    });
                }

                // Open link after tracking
                window.open(target.href, '_blank');

            }.bind(this));
        },

        /**
         * Setup impression tracking (CTA views)
         */
        setupImpressionTracking: function() {
            const ctas = document.querySelectorAll('.pt24-cta');

            ctas.forEach(function(cta) {
                const impressionTracked = cta.dataset.impressionTracked;

                if (impressionTracked) {
                    return; // Already tracked
                }

                // Mark as tracked
                cta.dataset.impressionTracked = 'true';

                const trackingData = {
                    cta_id: cta.dataset.ctaId || '',
                    service: cta.dataset.service || '',
                    city: cta.dataset.city || '',
                    post_id: cta.dataset.postId || '',
                    timestamp: new Date().toISOString(),
                    cta_style: this.getCTAStyle(cta),
                };

                // Send impression data
                this.sendTrackingData('impression', trackingData);

            }.bind(this));
        },

        /**
         * Setup visibility tracking (scroll tracking)
         */
        setupVisibilityTracking: function() {
            if (!window.IntersectionObserver) {
                return; // Not supported
            }

            const options = {
                threshold: 0.5, // 50% visible
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const cta = entry.target;
                        const visibilityTracked = cta.dataset.visibilityTracked;

                        if (visibilityTracked) {
                            return; // Already tracked
                        }

                        // Mark as tracked
                        cta.dataset.visibilityTracked = 'true';

                        const trackingData = {
                            cta_id: cta.dataset.ctaId || '',
                            service: cta.dataset.service || '',
                            city: cta.dataset.city || '',
                            post_id: cta.dataset.postId || '',
                            timestamp: new Date().toISOString(),
                            scroll_depth: this.getScrollDepth(),
                            cta_style: this.getCTAStyle(cta),
                        };

                        // Send visibility data
                        this.sendTrackingData('visibility', trackingData);

                        // Track with Google Analytics if available
                        if (typeof gtag !== 'undefined') {
                            gtag('event', 'pt24_cta_visible', {
                                service: trackingData.service,
                                city: trackingData.city,
                                cta_style: trackingData.cta_style,
                                scroll_depth: trackingData.scroll_depth,
                            });
                        }
                    }
                }.bind(this));
            }.bind(this), options);

            // Observe all CTAs
            document.querySelectorAll('.pt24-cta').forEach(function(cta) {
                observer.observe(cta);
            });
        },

        /**
         * Send tracking data to PT24 REST API endpoint
         */
        sendTrackingData: function(eventType, data) {
            // Use REST API configuration if available
            var config = typeof pt24ApiConfig !== 'undefined' ? pt24ApiConfig : null;

            if (!config) {
                // Fallback: check legacy pearblogData
                if (typeof pearblogData === 'undefined' && typeof ajaxurl === 'undefined') {
                    console.warn('PT24: API configuration not available');
                    return;
                }
                // Legacy admin-ajax fallback
                var ajaxUrl = typeof pearblogData !== 'undefined' ? pearblogData.ajaxurl : ajaxurl;
                var nonce = typeof pearblogData !== 'undefined' ? pearblogData.nonce : '';

                var formData = new FormData();
                formData.append('action', 'pt24_track_click');
                formData.append('nonce', nonce);
                formData.append('event_type', eventType);
                formData.append('service', data.service);
                formData.append('city', data.city);
                formData.append('post_id', data.post_id);
                formData.append('url', data.url || '');
                formData.append('cta_id', data.cta_id || '');
                formData.append('cta_style', data.cta_style || '');
                formData.append('timestamp', data.timestamp);

                if (navigator.sendBeacon) {
                    var params = new URLSearchParams(formData);
                    navigator.sendBeacon(ajaxUrl, params);
                } else {
                    fetch(ajaxUrl, {
                        method: 'POST',
                        body: formData,
                    }).catch(function(error) {
                        console.warn('PT24: Tracking request failed', error);
                    });
                }
                return;
            }

            // REST API path — POST /wp-json/pearblog/v1/pt24/track-click
            var endpoint = config.engineUrl + 'track-click';
            var payload = JSON.stringify({
                content_id: parseInt(data.post_id, 10) || 0,
                link_type: eventType,
                link_url: data.url || window.location.href,
                service: data.service || '',
                city: data.city || '',
                cta_id: data.cta_id || '',
                cta_style: data.cta_style || '',
                timestamp: data.timestamp,
            });

            // Use sendBeacon with Blob for non-blocking requests
            if (navigator.sendBeacon) {
                var blob = new Blob([payload], { type: 'application/json' });
                navigator.sendBeacon(endpoint + '?_wpnonce=' + encodeURIComponent(config.nonce), blob);
            } else {
                fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': config.nonce,
                    },
                    body: payload,
                    keepalive: true,
                }).catch(function(error) {
                    console.warn('PT24: Tracking request failed', error);
                });
            }
        },

        /**
         * Get CTA style from element classes
         */
        getCTAStyle: function(element) {
            if (element.classList.contains('pt24-cta--hybrid')) {
                return 'hybrid';
            } else if (element.classList.contains('pt24-cta--card')) {
                return 'card';
            } else if (element.classList.contains('pt24-cta--banner')) {
                return 'banner';
            } else if (element.classList.contains('pt24-cta--inline')) {
                return 'inline';
            }
            return 'unknown';
        },

        /**
         * Get current scroll depth percentage
         */
        getScrollDepth: function() {
            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;
            const scrollTop = window.scrollY;
            const scrollable = documentHeight - windowHeight;

            if (scrollable <= 0) {
                return 100;
            }

            const scrollPercentage = (scrollTop / scrollable) * 100;
            return Math.round(scrollPercentage);
        },
    };

    /**
     * Initialize on DOM ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            PT24Tracking.init();
        });
    } else {
        PT24Tracking.init();
    }

    /**
     * Re-initialize on dynamic content load (AJAX, etc.)
     */
    window.addEventListener('load', function() {
        PT24Tracking.setupImpressionTracking();
        PT24Tracking.setupVisibilityTracking();
    });

    /**
     * Export to global scope for manual triggering
     */
    window.PT24Tracking = PT24Tracking;

})();

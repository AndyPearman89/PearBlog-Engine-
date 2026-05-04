/**
 * Poradnik V3 Conversion Tracker - Client-side event tracking
 *
 * Tracks user behavior for conversion optimization:
 * - Page views
 * - Calculator interactions
 * - Form views and submissions
 * - CTA clicks
 * - Scroll depth
 * - Time on page
 *
 * @version 3.0.0
 * @package PearBlog
 */

(function() {
    'use strict';

    class PoradnikConversionTracker {
        constructor(config) {
            this.config = config || {};
            this.apiUrl = this.config.apiUrl || '/wp-json/pearblog/v3/tracking/event';
            this.sessionId = this.config.sessionId || this.getSessionId();
            this.service = this.config.service || 'unknown';
            this.abVariant = this.config.abVariant || 'control';
            this.nonce = this.config.nonce || '';

            // Tracking state
            this.pageLoadTime = Date.now();
            this.events = [];
            this.scrollDepth = 0;
            this.maxScrollDepth = 0;

            this.init();
        }

        init() {
            // Track page view immediately
            this.trackEvent('page_view', {
                url: window.location.href,
                referrer: document.referrer,
                utm_source: this.getUrlParam('utm_source'),
                utm_medium: this.getUrlParam('utm_medium'),
                utm_campaign: this.getUrlParam('utm_campaign')
            });

            // Bind scroll tracking
            this.bindScrollTracking();

            // Bind CTA click tracking
            this.bindCTATracking();

            // Track time on page
            this.bindTimeTracking();

            // Track calculator interactions (already handled in v3-calculator.js)
            // Track form interactions (already handled in v3-calculator.js)

            console.log('✅ Poradnik V3 Conversion Tracker initialized');
        }

        trackEvent(eventType, eventData = {}) {
            const event = {
                event_type: eventType,
                service: this.service,
                event_data: eventData,
                page_url: window.location.href,
                ab_variant: this.abVariant,
                utm_source: this.config.utmSource || this.getUrlParam('utm_source'),
                utm_medium: this.config.utmMedium || this.getUrlParam('utm_medium'),
                utm_campaign: this.config.utmCampaign || this.getUrlParam('utm_campaign'),
                timestamp: Date.now()
            };

            // Store locally
            this.events.push(event);

            // Send to server
            this.sendEvent(event);

            return event;
        }

        sendEvent(event) {
            // Use sendBeacon for reliability (works even when page is closing)
            if (navigator.sendBeacon) {
                const blob = new Blob([JSON.stringify(event)], { type: 'application/json' });
                navigator.sendBeacon(this.apiUrl, blob);
            } else {
                // Fallback to fetch
                fetch(this.apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': this.nonce
                    },
                    body: JSON.stringify(event),
                    keepalive: true // Keep request alive during page unload
                }).catch(err => {
                    console.warn('Failed to track event:', err);
                });
            }
        }

        bindScrollTracking() {
            let scrollTimeout;

            window.addEventListener('scroll', () => {
                clearTimeout(scrollTimeout);

                scrollTimeout = setTimeout(() => {
                    const scrollPercent = this.getScrollDepth();

                    if (scrollPercent > this.maxScrollDepth) {
                        this.maxScrollDepth = scrollPercent;

                        // Track milestones: 25%, 50%, 75%, 100%
                        const milestones = [25, 50, 75, 100];
                        milestones.forEach(milestone => {
                            if (scrollPercent >= milestone && this.scrollDepth < milestone) {
                                this.trackEvent('scroll_depth', {
                                    depth: milestone,
                                    service: this.service
                                });
                            }
                        });

                        this.scrollDepth = scrollPercent;
                    }
                }, 250);
            });
        }

        bindCTATracking() {
            // Track all CTA button clicks
            document.addEventListener('click', (e) => {
                const target = e.target.closest('button, a');

                if (!target) return;

                // Check if it's a CTA
                const isCTA = target.classList.contains('btn-primary') ||
                              target.classList.contains('btn-success') ||
                              target.classList.contains('nav-cta') ||
                              target.classList.contains('quick-action-btn') ||
                              target.classList.contains('ranking-cta') ||
                              target.classList.contains('open-lead-form');

                if (isCTA) {
                    const ctaText = target.textContent.trim();
                    const ctaId = target.id || target.className;

                    this.trackEvent('cta_click', {
                        cta_text: ctaText,
                        cta_id: ctaId,
                        service: this.service
                    });
                }
            });
        }

        bindTimeTracking() {
            // Track time on page when user leaves
            window.addEventListener('beforeunload', () => {
                const timeOnPage = Math.round((Date.now() - this.pageLoadTime) / 1000);

                this.trackEvent('page_exit', {
                    time_on_page: timeOnPage,
                    max_scroll_depth: this.maxScrollDepth,
                    service: this.service
                });
            });

            // Track engagement at intervals
            setInterval(() => {
                const timeOnPage = Math.round((Date.now() - this.pageLoadTime) / 1000);

                // Track at 30s, 60s, 120s, 300s
                if ([30, 60, 120, 300].includes(timeOnPage)) {
                    this.trackEvent('time_milestone', {
                        time_on_page: timeOnPage,
                        scroll_depth: this.maxScrollDepth,
                        service: this.service
                    });
                }
            }, 1000);
        }

        getScrollDepth() {
            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

            const scrollPercent = Math.round((scrollTop / (documentHeight - windowHeight)) * 100);
            return Math.min(scrollPercent, 100);
        }

        getSessionId() {
            // Try to get from cookie
            const cookie = document.cookie.split('; ').find(row => row.startsWith('pearblog_session_id='));
            if (cookie) {
                return cookie.split('=')[1];
            }

            // Generate new session ID
            const newSessionId = 'pb_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

            // Set cookie (30 days)
            const expires = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toUTCString();
            document.cookie = `pearblog_session_id=${newSessionId}; expires=${expires}; path=/; SameSite=Lax`;

            return newSessionId;
        }

        getUrlParam(param) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(param) || '';
        }

        // Public API
        static getInstance() {
            if (!window._poradnikTrackerInstance) {
                window._poradnikTrackerInstance = new PoradnikConversionTracker(window.pearblogV3Tracker || {});
            }
            return window._poradnikTrackerInstance;
        }
    }

    // Auto-initialize and expose global instance
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.PoradnikTracker = PoradnikConversionTracker.getInstance();
        });
    } else {
        window.PoradnikTracker = PoradnikConversionTracker.getInstance();
    }

})();

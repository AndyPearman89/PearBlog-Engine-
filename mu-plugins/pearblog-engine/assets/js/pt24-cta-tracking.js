/**
 * PT24 CTA Tracking - JavaScript for tracking CTA interactions
 *
 * @package PearBlogEngine
 * @subpackage Integration
 */

(function() {
    'use strict';

    /**
     * PT24 CTA Tracker
     */
    const PT24CTATracker = {
        /**
         * Initialize tracker
         */
        init: function() {
            this.setupLinkTracking();
            this.setupStickyCTA();
            this.setupExitIntent();
            this.trackPageviews();
            this.setupScrollTracking();
        },

        /**
         * Setup link tracking for PT24 links
         */
        setupLinkTracking: function() {
            document.querySelectorAll('a.pearblog-pt24-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    const url = this.getAttribute('href');
                    const linkType = this.getAttribute('data-link-type') || 'unknown';
                    const linkText = this.textContent.trim();

                    // Store source content ID in cookie
                    const postId = PT24CTATracker.getPostId();
                    if (postId) {
                        PT24CTATracker.setCookie('pb_source_content', postId, 1);
                    }

                    // Track click event
                    PT24CTATracker.trackEvent('pt24_link_click', {
                        link_type: linkType,
                        link_text: linkText,
                        link_url: url,
                        post_id: postId
                    });

                    // Send to REST API
                    PT24CTATracker.sendToAPI('track-click', {
                        content_id: postId,
                        link_type: linkType,
                        link_url: url
                    });
                });
            });
        },

        /**
         * Setup sticky CTA behavior
         */
        setupStickyCTA: function() {
            const stickyCTA = document.getElementById('pearblog-sticky-cta');
            if (!stickyCTA) return;

            let shown = false;

            // Show sticky CTA after scrolling 50% of page
            window.addEventListener('scroll', function() {
                const scrollPercent = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;

                if (scrollPercent > 50 && !shown) {
                    stickyCTA.classList.add('visible');
                    stickyCTA.style.display = 'block';
                    shown = true;

                    PT24CTATracker.trackEvent('sticky_cta_shown', {
                        scroll_percent: scrollPercent
                    });
                }
            });

            // Close button
            const closeButton = stickyCTA.querySelector('.sticky-close');
            if (closeButton) {
                closeButton.addEventListener('click', function() {
                    stickyCTA.style.display = 'none';
                    stickyCTA.classList.remove('visible');

                    PT24CTATracker.trackEvent('sticky_cta_closed', {});
                });
            }

            // Track phone clicks
            const phoneLink = stickyCTA.querySelector('.sticky-phone');
            if (phoneLink) {
                phoneLink.addEventListener('click', function() {
                    PT24CTATracker.trackEvent('sticky_cta_phone_click', {
                        phone: this.textContent.trim()
                    });
                });
            }
        },

        /**
         * Setup exit intent popup
         */
        setupExitIntent: function() {
            const exitPopup = document.querySelector('.pearblog-cta-exit-intent');
            if (!exitPopup) return;

            let shown = false;

            // Detect mouse leaving viewport
            document.addEventListener('mouseleave', function(e) {
                if (e.clientY < 0 && !shown) {
                    exitPopup.classList.add('visible');
                    shown = true;

                    PT24CTATracker.trackEvent('exit_intent_shown', {});
                }
            });

            // Close button
            const closeButton = exitPopup.querySelector('.exit-close');
            if (closeButton) {
                closeButton.addEventListener('click', function() {
                    exitPopup.classList.remove('visible');

                    PT24CTATracker.trackEvent('exit_intent_closed', {});
                });
            }

            // Track exit button clicks
            const exitButton = exitPopup.querySelector('.exit-button');
            if (exitButton) {
                exitButton.addEventListener('click', function() {
                    PT24CTATracker.trackEvent('exit_intent_click', {
                        url: this.getAttribute('href')
                    });
                });
            }

            // Close on backdrop click
            exitPopup.addEventListener('click', function(e) {
                if (e.target === exitPopup) {
                    exitPopup.classList.remove('visible');
                    PT24CTATracker.trackEvent('exit_intent_backdrop_close', {});
                }
            });
        },

        /**
         * Track pageviews for funnel stage detection
         */
        trackPageviews: function() {
            // Get or initialize pageview count
            let pageviews = parseInt(sessionStorage.getItem('pb_pageviews') || '0');
            pageviews++;
            sessionStorage.setItem('pb_pageviews', pageviews.toString());

            // Track first pageview
            if (pageviews === 1) {
                PT24CTATracker.trackEvent('first_pageview', {
                    post_id: PT24CTATracker.getPostId()
                });
            }

            // Detect funnel stage
            const funnelStage = PT24CTATracker.detectFunnelStage(pageviews);
            sessionStorage.setItem('pb_funnel_stage', funnelStage);

            // Track pageview with funnel stage
            PT24CTATracker.sendToAPI('track-pageview', {
                post_id: PT24CTATracker.getPostId(),
                pageviews: pageviews,
                funnel_stage: funnelStage
            });
        },

        /**
         * Setup scroll depth tracking
         */
        setupScrollTracking: function() {
            const milestones = [25, 50, 75, 100];
            const tracked = new Set();

            window.addEventListener('scroll', function() {
                const scrollPercent = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;

                milestones.forEach(milestone => {
                    if (scrollPercent >= milestone && !tracked.has(milestone)) {
                        tracked.add(milestone);
                        PT24CTATracker.trackEvent('scroll_depth', {
                            depth: milestone,
                            post_id: PT24CTATracker.getPostId()
                        });
                    }
                });
            });
        },

        /**
         * Detect funnel stage based on pageviews
         */
        detectFunnelStage: function(pageviews) {
            if (pageviews <= 2) return 'awareness';
            if (pageviews <= 5) return 'consideration';
            return 'decision';
        },

        /**
         * Get current post ID
         */
        getPostId: function() {
            // Try to get from body data attribute
            const body = document.querySelector('body');
            if (body) {
                const postId = body.getAttribute('data-post-id');
                if (postId) return parseInt(postId);
            }

            // Try to get from WordPress global
            if (typeof wp !== 'undefined' && wp.post && wp.post.id) {
                return parseInt(wp.post.id);
            }

            return null;
        },

        /**
         * Set cookie
         */
        setCookie: function(name, value, days) {
            const expires = new Date();
            expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
            document.cookie = name + '=' + value + ';expires=' + expires.toUTCString() + ';path=/';
        },

        /**
         * Get cookie
         */
        getCookie: function(name) {
            const nameEQ = name + '=';
            const cookies = document.cookie.split(';');

            for (let i = 0; i < cookies.length; i++) {
                let cookie = cookies[i];
                while (cookie.charAt(0) === ' ') {
                    cookie = cookie.substring(1, cookie.length);
                }
                if (cookie.indexOf(nameEQ) === 0) {
                    return cookie.substring(nameEQ.length, cookie.length);
                }
            }

            return null;
        },

        /**
         * Track event (Google Analytics / custom tracking)
         */
        trackEvent: function(eventName, eventData) {
            // Google Analytics 4
            if (typeof gtag !== 'undefined') {
                gtag('event', eventName, eventData);
            }

            // Google Analytics Universal
            if (typeof ga !== 'undefined') {
                ga('send', 'event', 'PT24_CTA', eventName, JSON.stringify(eventData));
            }

            // Console log for debugging
            if (window.location.search.indexOf('debug=1') !== -1) {
                console.log('[PT24 CTA Tracker]', eventName, eventData);
            }
        },

        /**
         * Send data to REST API
         */
        sendToAPI: function(endpoint, data) {
            // Check if REST API is available
            if (typeof wpApiSettings === 'undefined') {
                return;
            }

            const url = wpApiSettings.root + 'pearblog/v1/pt24/' + endpoint;

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wpApiSettings.nonce
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (window.location.search.indexOf('debug=1') !== -1) {
                    console.log('[PT24 API]', endpoint, result);
                }
            })
            .catch(error => {
                console.error('[PT24 API Error]', endpoint, error);
            });
        },

        /**
         * Track time on page
         */
        trackTimeOnPage: function() {
            const startTime = Date.now();

            // Track when user leaves
            window.addEventListener('beforeunload', function() {
                const timeOnPage = Math.round((Date.now() - startTime) / 1000);

                PT24CTATracker.trackEvent('time_on_page', {
                    seconds: timeOnPage,
                    post_id: PT24CTATracker.getPostId()
                });
            });
        }
    };

    /**
     * Initialize on DOM ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            PT24CTATracker.init();
            PT24CTATracker.trackTimeOnPage();
        });
    } else {
        PT24CTATracker.init();
        PT24CTATracker.trackTimeOnPage();
    }

    // Expose to global scope for manual tracking
    window.PT24CTATracker = PT24CTATracker;

})();

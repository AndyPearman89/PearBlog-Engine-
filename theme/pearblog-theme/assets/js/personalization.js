/**
 * PearBlog Personalization Engine - Client-Side Tracking
 *
 * Tracks:
 * - Scroll depth
 * - Time on page
 * - Click events
 * - User behavior
 *
 * Enables:
 * - Dynamic content updates
 * - Personalized CTAs
 * - Smart ad placement
 *
 * @version 4.0.0
 */

(function() {
    'use strict';

    // State
    const PearBlogPersonalization = {
        sessionId: null,
        startTime: Date.now(),
        scrollDepth: 0,
        maxScrollDepth: 0,
        clicks: 0,
        ctaClicks: 0,
        adViews: 0,
        adClicks: 0,
        lastSaveTime: Date.now(),
        saveInterval: 15000, // Save every 15 seconds
        userContext: null,
        abVariant: null,
    };

    /**
     * Initialize personalization engine
     */
    function init() {
        // Get or create session ID
        PearBlogPersonalization.sessionId = getOrCreateSessionId();

        // Get user context
        getUserContext();

        // Track scroll depth
        trackScrollDepth();

        // Track clicks
        trackClicks();

        // Track time on page
        trackTimeOnPage();

        // Dynamic content loading
        initDynamicContent();

        // Save metrics periodically
        setInterval(saveMetrics, PearBlogPersonalization.saveInterval);

        // Save on page unload
        window.addEventListener('beforeunload', saveMetrics);

        // Track A/B test variant
        trackABVariant();
    }

    /**
     * Get or create session ID
     */
    function getOrCreateSessionId() {
        let sessionId = getCookie('pb_session_id');

        if (!sessionId) {
            sessionId = 'session_' + generateId() + '_' + Date.now();
            setCookie('pb_session_id', sessionId, 1); // 1 day
        }

        return sessionId;
    }

    /**
     * Get user context from server
     */
    function getUserContext() {
        // Get from local storage if available (cached)
        const cached = localStorage.getItem('pb_user_context');
        const cacheTime = localStorage.getItem('pb_context_time');

        if (cached && cacheTime && (Date.now() - parseInt(cacheTime)) < 3600000) {
            PearBlogPersonalization.userContext = JSON.parse(cached);
            return;
        }

        // Collect client-side context
        const context = {
            screen_width: window.screen.width,
            screen_height: window.screen.height,
            viewport_width: window.innerWidth,
            viewport_height: window.innerHeight,
            user_agent: navigator.userAgent,
            language: navigator.language,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        };

        // Send to server
        fetch(pearblogData.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'pb_store_context',
                nonce: pearblogData.nonce,
                context: JSON.stringify(context),
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                PearBlogPersonalization.userContext = data.data;
                localStorage.setItem('pb_user_context', JSON.stringify(data.data));
                localStorage.setItem('pb_context_time', Date.now().toString());
            }
        })
        .catch(error => console.error('Context error:', error));
    }

    /**
     * Track scroll depth
     */
    function trackScrollDepth() {
        let ticking = false;

        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(function() {
                    calculateScrollDepth();
                    ticking = false;
                });
                ticking = true;
            }
        });
    }

    /**
     * Calculate scroll depth percentage
     */
    function calculateScrollDepth() {
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        const scrollPercentage = Math.round((scrollTop / (documentHeight - windowHeight)) * 100);

        PearBlogPersonalization.scrollDepth = Math.min(100, scrollPercentage);
        PearBlogPersonalization.maxScrollDepth = Math.max(
            PearBlogPersonalization.maxScrollDepth,
            PearBlogPersonalization.scrollDepth
        );

        // Trigger dynamic content at milestones
        checkScrollMilestones(PearBlogPersonalization.scrollDepth);
    }

    /**
     * Check scroll milestones for dynamic content
     */
    function checkScrollMilestones(depth) {
        // 50% scroll - show smart CTA
        if (depth >= 50 && !PearBlogPersonalization.milestone50) {
            PearBlogPersonalization.milestone50 = true;
            loadDynamicContent('cta');
        }

        // 70% scroll - show related content
        if (depth >= 70 && !PearBlogPersonalization.milestone70) {
            PearBlogPersonalization.milestone70 = true;
            loadDynamicContent('related');
        }

        // 90% scroll - show newsletter
        if (depth >= 90 && !PearBlogPersonalization.milestone90) {
            PearBlogPersonalization.milestone90 = true;
            loadDynamicContent('newsletter');
        }
    }

    /**
     * Track clicks
     */
    function trackClicks() {
        document.addEventListener('click', function(e) {
            PearBlogPersonalization.clicks++;

            // Track CTA clicks
            if (e.target.closest('.pb-cta, [class*="cta"]')) {
                PearBlogPersonalization.ctaClicks++;

                // Track A/B test click
                if (PearBlogPersonalization.abVariant) {
                    trackABClick();
                }
            }

            // Track ad clicks
            if (e.target.closest('.pb-ad-slot, [class*="ad-"]')) {
                PearBlogPersonalization.adClicks++;
            }
        });
    }

    /**
     * Track time on page
     */
    function trackTimeOnPage() {
        // Update every second
        setInterval(function() {
            // Only count active time (tab is visible)
            if (!document.hidden) {
                PearBlogPersonalization.timeOnPage = Math.round((Date.now() - PearBlogPersonalization.startTime) / 1000);
            }
        }, 1000);
    }

    /**
     * Save metrics to server
     */
    function saveMetrics() {
        const now = Date.now();

        // Don't save too frequently
        if (now - PearBlogPersonalization.lastSaveTime < 10000) {
            return;
        }

        const postId = document.body.getAttribute('data-post-id') || 0;

        if (!postId) {
            return;
        }

        const metrics = {
            session_id: PearBlogPersonalization.sessionId,
            post_id: postId,
            scroll_depth: PearBlogPersonalization.maxScrollDepth,
            time_on_page: Math.round((now - PearBlogPersonalization.startTime) / 1000),
            clicks: PearBlogPersonalization.clicks,
            cta_clicks: PearBlogPersonalization.ctaClicks,
            ad_views: PearBlogPersonalization.adViews,
            ad_clicks: PearBlogPersonalization.adClicks,
        };

        fetch(pearblogData.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'pb_save_metrics',
                nonce: pearblogData.nonce,
                ...metrics,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                PearBlogPersonalization.lastSaveTime = now;

                // Update behavior cookie
                updateBehaviorCookie();
            }
        })
        .catch(error => console.error('Metrics save error:', error));
    }

    /**
     * Update behavior cookie
     */
    function updateBehaviorCookie() {
        const behavior = getCookie('pb_behavior');
        let behaviorData = behavior ? JSON.parse(behavior) : {};

        behaviorData.pages_viewed = (behaviorData.pages_viewed || 0) + 1;
        behaviorData.total_time = (behaviorData.total_time || 0) + Math.round((Date.now() - PearBlogPersonalization.startTime) / 1000);
        behaviorData.scroll_depth_avg = Math.round(((behaviorData.scroll_depth_avg || 0) + PearBlogPersonalization.maxScrollDepth) / 2);
        behaviorData.clicks = (behaviorData.clicks || 0) + PearBlogPersonalization.clicks;
        behaviorData.last_visit = Date.now();

        setCookie('pb_behavior', JSON.stringify(behaviorData), 30); // 30 days
    }

    /**
     * Load dynamic content
     */
    function loadDynamicContent(type) {
        const postId = document.body.getAttribute('data-post-id') || 0;

        if (!postId) {
            return;
        }

        fetch(pearblogData.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'pb_get_dynamic_content',
                nonce: pearblogData.nonce,
                post_id: postId,
                scroll_depth: PearBlogPersonalization.scrollDepth,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDynamicElements(data.data);
            }
        })
        .catch(error => console.error('Dynamic content error:', error));
    }

    /**
     * Update dynamic elements on page
     */
    function updateDynamicElements(data) {
        // Update CTA
        if (data.cta) {
            const ctaElements = document.querySelectorAll('[data-dynamic-cta]');
            ctaElements.forEach(el => {
                if (data.cta.text) {
                    el.textContent = data.cta.text;
                }
                if (data.cta.type) {
                    el.className = 'pb-btn pb-btn-' + data.cta.type;
                }
            });
        }

        // Show/hide ads
        if (data.show_ad) {
            const adElements = document.querySelectorAll('[data-dynamic-ad]');
            adElements.forEach(el => {
                el.style.display = 'block';
                PearBlogPersonalization.adViews++;
            });
        }

        // Show related content
        if (data.show_related && data.recommendations) {
            const relatedContainer = document.querySelector('[data-dynamic-related]');
            if (relatedContainer) {
                // Populate with recommendations
                relatedContainer.style.display = 'block';
            }
        }

        // Show newsletter
        if (data.show_newsletter) {
            const newsletter = document.querySelector('[data-dynamic-newsletter]');
            if (newsletter) {
                newsletter.style.display = 'block';
            }
        }
    }

    /**
     * Initialize dynamic content system
     */
    function initDynamicContent() {
        // Mark dynamic elements
        document.querySelectorAll('.pb-cta').forEach(el => {
            el.setAttribute('data-dynamic-cta', 'true');
        });

        document.querySelectorAll('.pb-ad-slot').forEach(el => {
            el.setAttribute('data-dynamic-ad', 'true');
        });
    }

    /**
     * Track A/B test variant
     */
    function trackABVariant() {
        const headline = document.querySelector('h1.entry-title');
        if (headline) {
            const variant = headline.getAttribute('data-ab-variant');
            if (variant) {
                PearBlogPersonalization.abVariant = variant;
            }
        }
    }

    /**
     * Track A/B test click
     */
    function trackABClick() {
        const postId = document.body.getAttribute('data-post-id') || 0;

        if (!postId || !PearBlogPersonalization.abVariant) {
            return;
        }

        fetch(pearblogData.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'pb_track_ab_click',
                nonce: pearblogData.nonce,
                post_id: postId,
                variant: PearBlogPersonalization.abVariant,
            }),
        })
        .catch(error => console.error('A/B tracking error:', error));
    }

    /**
     * Cookie helpers
     */
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }

    function setCookie(name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
        document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/;SameSite=Lax`;
    }

    /**
     * Generate unique ID
     */
    function generateId() {
        return Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose API
    window.PearBlogPersonalization = PearBlogPersonalization;

})();

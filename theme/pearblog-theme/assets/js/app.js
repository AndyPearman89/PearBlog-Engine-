/**
 * PearBlog v5.1 — Main App JavaScript
 * Frontend Operating System (FOS)
 */

(function() {
    'use strict';

    // Global config from WordPress
    const pbConfig = window.pearblogData || {};

    /**
     * Dark Mode Toggle
     */
    function initDarkMode() {
        if (!pbConfig.darkMode) return;

        const toggleBtn = document.getElementById('pb-dark-mode-toggle');
        const body = document.body;

        // Check saved preference
        const savedMode = localStorage.getItem('pb_dark_mode');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        function setDark(on) {
            body.classList.toggle('pb-dark-mode', on);
            if (toggleBtn) {
                const icon = toggleBtn.querySelector('.pb-dark-icon');
                if (icon) icon.textContent = on ? '☀️' : '🌙';
                toggleBtn.setAttribute('aria-label', on ? 'Disable dark mode' : 'Enable dark mode');
            }
        }

        // Set initial mode
        setDark(savedMode === 'dark' || (!savedMode && prefersDark));

        // Toggle handler
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                const isDark = !body.classList.contains('pb-dark-mode');
                setDark(isDark);
                localStorage.setItem('pb_dark_mode', isDark ? 'dark' : 'light');
            });
        }

        // Listen for system preference changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('pb_dark_mode')) {
                setDark(e.matches);
            }
        });
    }

    /**
     * Sticky Header
     */
    function initStickyHeader() {
        const header = document.getElementById('pb-header');
        if (!header) return;

        const threshold = 60;

        function onScroll() {
            header.classList.toggle('pb-nav--sticky', window.pageYOffset > threshold);
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    /**
     * Reading Progress Bar
     */
    function initReadingProgress() {
        const progressEl = document.getElementById('pb-reading-progress');
        if (!progressEl) return;

        function update() {
            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const progress = Math.min((scrollTop / (documentHeight - windowHeight)) * 100, 100);
            progressEl.style.width = progress + '%';

            // Also update aria
            const bar = document.getElementById('pb-reading-progress-bar');
            if (bar) bar.setAttribute('aria-valuenow', Math.round(progress));
        }

        window.addEventListener('scroll', update, { passive: true });
        update();
    }

    /**
     * Search Panel
     */
    function initSearchPanel() {
        const toggle = document.getElementById('pb-search-toggle');
        const panel = document.getElementById('pb-search-panel');
        const closeBtn = document.getElementById('pb-search-close');
        const input = document.getElementById('pb-search-input');

        if (!toggle || !panel) return;

        function openPanel() {
            panel.classList.add('is-open');
            panel.setAttribute('aria-hidden', 'false');
            toggle.setAttribute('aria-expanded', 'true');
            if (input) {
                setTimeout(() => input.focus(), 50);
            }
        }

        function closePanel() {
            panel.classList.remove('is-open');
            panel.setAttribute('aria-hidden', 'true');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.focus();
        }

        toggle.addEventListener('click', openPanel);

        if (closeBtn) {
            closeBtn.addEventListener('click', closePanel);
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && panel.classList.contains('is-open')) {
                closePanel();
            }
        });

        document.addEventListener('click', (e) => {
            if (panel.classList.contains('is-open') &&
                !panel.contains(e.target) &&
                !toggle.contains(e.target)) {
                closePanel();
            }
        });
    }

    /**
     * Mobile Menu
     */
    function initMobileMenu() {
        const menuToggle = document.querySelector('.pb-menu-toggle');
        const menu = document.querySelector('.pb-menu');

        if (!menuToggle || !menu) return;

        menuToggle.addEventListener('click', function() {
            menu.classList.toggle('active');
            const isExpanded = menu.classList.contains('active');
            this.setAttribute('aria-expanded', String(isExpanded));
            document.body.classList.toggle('menu-open', isExpanded);
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!menuToggle.contains(e.target) && !menu.contains(e.target)) {
                menu.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
                document.body.classList.remove('menu-open');
            }
        });

        // Close menu on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && menu.classList.contains('active')) {
                menu.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
                menuToggle.focus();
                document.body.classList.remove('menu-open');
            }
        });
    }

    /**
     * FAQ Accordion
     */
    function initFAQ() {
        const faqItems = document.querySelectorAll('.pb-faq-item');

        faqItems.forEach(item => {
            const question = item.querySelector('.pb-faq-question');

            if (question) {
                question.addEventListener('click', function() {
                    const isActive = item.classList.contains('active');

                    // Close all other FAQs
                    faqItems.forEach(otherItem => {
                        if (otherItem !== item) {
                            otherItem.classList.remove('active');
                            const otherQuestion = otherItem.querySelector('.pb-faq-question');
                            if (otherQuestion) {
                                otherQuestion.setAttribute('aria-expanded', 'false');
                            }
                        }
                    });

                    // Toggle current item
                    item.classList.toggle('active', !isActive);
                    this.setAttribute('aria-expanded', String(!isActive));
                });

                // Keyboard navigation
                question.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.click();
                    }
                });
            }
        });
    }

    /**
     * Table of Contents
     */
    function initTOC() {
        const toc = document.querySelector('.pb-toc');
        if (!toc) return;

        const tocLinks = toc.querySelectorAll('.pb-toc-link');
        const tocToggle = toc.querySelector('.pb-toc-toggle');

        // Smooth scroll to headings
        tocLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href').substring(1);
                const target = document.getElementById(targetId);

                if (target) {
                    const offset = 80; // Account for sticky header
                    const targetPosition = target.offsetTop - offset;

                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });

                    // Update active state
                    tocLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                }
            });
        });

        // Toggle TOC on mobile
        if (tocToggle) {
            tocToggle.addEventListener('click', function() {
                toc.classList.toggle('collapsed');
            });
        }

        // Highlight current section on scroll
        const headings = Array.from(document.querySelectorAll('h2[id], h3[id]'));

        window.addEventListener('scroll', function() {
            let current = '';

            headings.forEach(heading => {
                const rect = heading.getBoundingClientRect();
                if (rect.top <= 100) {
                    current = heading.id;
                }
            });

            tocLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        }, { passive: true });
    }

    /**
     * Smooth Scroll for Anchor Links
     */
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;

                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    e.preventDefault();
                    const offsetTop = targetElement.offsetTop - 80;
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            });
        });
    }

    /**
     * Back to Top Button
     */
    function initBackToTop() {
        const btn = document.getElementById('pb-back-to-top');
        if (!btn) return;

        btn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    /**
     * Sticky Mobile CTA
     */
    function initStickyCTA() {
        if (!pbConfig.stickyMobileCTA) return;

        const stickyCTA = document.getElementById('pb-sticky-mobile-ad');
        if (!stickyCTA) return;

        window.addEventListener('scroll', function() {
            stickyCTA.classList.toggle('visible', window.pageYOffset > 300);
        }, { passive: true });
    }

    /**
     * Track Core Web Vitals (development only)
     */
    function initWebVitals() {
        if (location.hostname === 'localhost' || location.hostname === '127.0.0.1') {
            if ('PerformanceObserver' in window) {
                try {
                    new PerformanceObserver((list) => {
                        const entries = list.getEntries();
                        const lastEntry = entries[entries.length - 1];
                        console.log('LCP:', lastEntry.renderTime || lastEntry.loadTime);
                    }).observe({ entryTypes: ['largest-contentful-paint'] });
                } catch (e) {}

                try {
                    new PerformanceObserver((list) => {
                        list.getEntries().forEach(entry => {
                            console.log('FID:', entry.processingStart - entry.startTime);
                        });
                    }).observe({ entryTypes: ['first-input'] });
                } catch (e) {}

                try {
                    let clsScore = 0;
                    new PerformanceObserver((list) => {
                        for (const entry of list.getEntries()) {
                            if (!entry.hadRecentInput) {
                                clsScore += entry.value;
                                console.log('CLS:', clsScore);
                            }
                        }
                    }).observe({ entryTypes: ['layout-shift'] });
                } catch (e) {}
            }
        }
    }

    /**
     * Initialize all features
     */
    function init() {
        initDarkMode();
        initStickyHeader();
        initReadingProgress();
        initSearchPanel();
        initMobileMenu();
        initFAQ();
        initTOC();
        initSmoothScroll();
        initBackToTop();
        initStickyCTA();
        initWebVitals();
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Export for external use
    window.PearBlogApp = {
        init: init,
        darkMode: initDarkMode,
        toc: initTOC,
        search: initSearchPanel,
    };

})();

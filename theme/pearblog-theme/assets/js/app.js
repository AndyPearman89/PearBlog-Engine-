/**
 * PearBlog v2 PRO - Main App JavaScript
 * Frontend Operating System (FOS)
 *
 * @version 2.0.0
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

        // Set initial mode
        if (savedMode === 'dark' || (!savedMode && prefersDark)) {
            body.classList.add('pb-dark-mode');
        }

        // Toggle handler
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                body.classList.toggle('pb-dark-mode');
                const isDark = body.classList.contains('pb-dark-mode');
                localStorage.setItem('pb_dark_mode', isDark ? 'dark' : 'light');

                // Update button icon
                this.innerHTML = isDark ? '☀️' : '🌙';
            });
        }

        // Listen for system preference changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('pb_dark_mode')) {
                body.classList.toggle('pb-dark-mode', e.matches);
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
            this.setAttribute('aria-expanded', isExpanded);
            body.classList.toggle('menu-open', isExpanded);
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
                    this.setAttribute('aria-expanded', !isActive);
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

            // Update reading progress
            updateReadingProgress();
        });
    }

    /**
     * Reading Progress Bar
     */
    function updateReadingProgress() {
        const progressBar = document.getElementById('pb-reading-progress');
        if (!progressBar) return;

        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        const progress = (scrollTop / (documentHeight - windowHeight)) * 100;
        progressBar.style.width = Math.min(progress, 100) + '%';
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
     * Sticky Mobile CTA
     */
    function initStickyCTA() {
        if (!pbConfig.stickyMobileCTA) return;

        const stickyCTA = document.getElementById('pb-sticky-mobile-ad');
        if (!stickyCTA) return;

        // Show/hide based on scroll
        let lastScroll = 0;

        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset;

            if (currentScroll > 300) {
                stickyCTA.classList.add('visible');
            } else {
                stickyCTA.classList.remove('visible');
            }

            lastScroll = currentScroll;
        });
    }

    /**
     * Track Core Web Vitals
     */
    function initWebVitals() {
        if (location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            return; // Only track in production
        }

        if ('PerformanceObserver' in window) {
            // Largest Contentful Paint
            try {
                new PerformanceObserver((list) => {
                    const entries = list.getEntries();
                    const lastEntry = entries[entries.length - 1];
                    console.log('LCP:', lastEntry.renderTime || lastEntry.loadTime);
                }).observe({ entryTypes: ['largest-contentful-paint'] });
            } catch (e) {}

            // First Input Delay
            try {
                new PerformanceObserver((list) => {
                    list.getEntries().forEach(entry => {
                        console.log('FID:', entry.processingStart - entry.startTime);
                    });
                }).observe({ entryTypes: ['first-input'] });
            } catch (e) {}

            // Cumulative Layout Shift
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

    /**
     * Initialize all features
     */
    function init() {
        initDarkMode();
        initMobileMenu();
        initFAQ();
        initTOC();
        initSmoothScroll();
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
    };

})();

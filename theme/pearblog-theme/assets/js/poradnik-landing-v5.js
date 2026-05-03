/**
 * Poradnik.pro Landing V5 - JavaScript
 *
 * Interactive features for high-conversion landing page
 * Form handling, animations, FAQ toggles, stats counter
 *
 * @package PearBlog
 * @version 5.0.0
 */

(function() {
    'use strict';

    const PoradnikLandingV5 = {
        /**
         * Initialize all features
         */
        init() {
            this.setupHeroForm();
            this.setupCtaForm();
            this.setupFAQ();
            this.setupStatsCounter();
            this.setupSmoothScroll();
            this.setupIntersectionObserver();
            console.log('🚀 Poradnik.pro Landing V5 initialized');
        },

        /**
         * Setup hero form submission
         */
        setupHeroForm() {
            const form = document.getElementById('plv5HeroForm');
            if (!form) return;

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleLeadSubmission(form, 'hero');
            });
        },

        /**
         * Setup CTA form submission
         */
        setupCtaForm() {
            const form = document.getElementById('plv5CtaForm');
            if (!form) return;

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleLeadSubmission(form, 'cta');
            });
        },

        /**
         * Handle lead form submission
         *
         * @param {HTMLFormElement} form - Form element
         * @param {string} source - Form source (hero/cta)
         */
        handleLeadSubmission(form, source) {
            const formData = new FormData(form);
            const data = {
                service: formData.get('service') || '',
                email: formData.get('email') || '',
                source: source,
                action: 'plv5_submit_lead',
            };

            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>Wysyłanie...</span>';

            // Submit via AJAX
            fetch(poradnikData?.ajaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    this.showSuccessMessage(form, 'Dziękujemy! Skontaktujemy się wkrótce.');
                    form.reset();

                    // Track conversion
                    this.trackConversion(source, data);
                } else {
                    this.showErrorMessage(form, result.data?.message || 'Wystąpił błąd. Spróbuj ponownie.');
                }
            })
            .catch(error => {
                console.error('Form submission error:', error);
                this.showErrorMessage(form, 'Wystąpił błąd połączenia. Sprawdź internet i spróbuj ponownie.');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        },

        /**
         * Show success message
         *
         * @param {HTMLFormElement} form - Form element
         * @param {string} message - Success message
         */
        showSuccessMessage(form, message) {
            const alert = document.createElement('div');
            alert.className = 'plv5-alert plv5-alert--success';
            alert.style.cssText = `
                padding: 1rem;
                margin-top: 1rem;
                background: #00c853;
                color: white;
                border-radius: 0.5rem;
                font-weight: 600;
                text-align: center;
                animation: plv5-fadeInUp 0.3s ease-out;
            `;
            alert.textContent = message;

            form.appendChild(alert);

            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        },

        /**
         * Show error message
         *
         * @param {HTMLFormElement} form - Form element
         * @param {string} message - Error message
         */
        showErrorMessage(form, message) {
            const alert = document.createElement('div');
            alert.className = 'plv5-alert plv5-alert--error';
            alert.style.cssText = `
                padding: 1rem;
                margin-top: 1rem;
                background: #f44336;
                color: white;
                border-radius: 0.5rem;
                font-weight: 600;
                text-align: center;
                animation: plv5-fadeInUp 0.3s ease-out;
            `;
            alert.textContent = message;

            form.appendChild(alert);

            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        },

        /**
         * Track conversion for analytics
         *
         * @param {string} source - Conversion source
         * @param {object} data - Lead data
         */
        trackConversion(source, data) {
            // Google Analytics 4
            if (typeof gtag !== 'undefined') {
                gtag('event', 'generate_lead', {
                    event_category: 'Lead',
                    event_label: source,
                    value: 1
                });
            }

            // Facebook Pixel
            if (typeof fbq !== 'undefined') {
                fbq('track', 'Lead', {
                    content_name: source,
                    value: 1
                });
            }

            // Custom tracking
            if (typeof poradnikTrack !== 'undefined') {
                poradnikTrack('lead_submitted', {
                    source: source,
                    service: data.service,
                    timestamp: new Date().toISOString()
                });
            }

            console.log('✅ Conversion tracked:', source);
        },

        /**
         * Setup FAQ accordion
         */
        setupFAQ() {
            const faqItems = document.querySelectorAll('.plv5-faq-item');

            faqItems.forEach(item => {
                const question = item.querySelector('.plv5-faq-item__question');
                const answer = item.querySelector('.plv5-faq-item__answer');

                if (!question || !answer) return;

                question.addEventListener('click', () => {
                    const isActive = answer.classList.contains('is-active');

                    // Close all other FAQs
                    document.querySelectorAll('.plv5-faq-item__answer').forEach(a => {
                        a.classList.remove('is-active');
                    });
                    document.querySelectorAll('.plv5-faq-item__question').forEach(q => {
                        q.classList.remove('is-active');
                    });

                    // Toggle current FAQ
                    if (!isActive) {
                        answer.classList.add('is-active');
                        question.classList.add('is-active');
                    }
                });
            });
        },

        /**
         * Setup animated stats counter
         */
        setupStatsCounter() {
            const stats = document.querySelectorAll('.plv5-stat__number');

            const animateCounter = (element) => {
                const target = parseFloat(element.dataset.count);
                const duration = 2000;
                const increment = target / (duration / 16);
                let current = 0;

                const updateCounter = () => {
                    current += increment;

                    if (current < target) {
                        element.textContent = Math.floor(current).toLocaleString('pl-PL');
                        requestAnimationFrame(updateCounter);
                    } else {
                        element.textContent = target.toLocaleString('pl-PL');
                    }
                };

                updateCounter();
            };

            // Use Intersection Observer to trigger animation when visible
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !entry.target.dataset.animated) {
                        animateCounter(entry.target);
                        entry.target.dataset.animated = 'true';
                    }
                });
            }, {
                threshold: 0.5
            });

            stats.forEach(stat => observer.observe(stat));
        },

        /**
         * Setup smooth scrolling for anchor links
         */
        setupSmoothScroll() {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');

                    if (href === '#') return;

                    const target = document.querySelector(href);
                    if (!target) return;

                    e.preventDefault();

                    const offsetTop = target.getBoundingClientRect().top + window.pageYOffset - 100;

                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                });
            });
        },

        /**
         * Setup intersection observer for animations
         */
        setupIntersectionObserver() {
            const animatedElements = document.querySelectorAll(`
                .plv5-step,
                .plv5-feature,
                .plv5-testimonial
            `);

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }, index * 100);

                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            animatedElements.forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
                observer.observe(el);
            });
        },

        /**
         * Get URL parameters
         *
         * @param {string} param - Parameter name
         * @returns {string|null} Parameter value
         */
        getUrlParameter(param) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(param);
        },

        /**
         * Setup UTM tracking
         */
        setupUTMTracking() {
            const utm = {
                source: this.getUrlParameter('utm_source'),
                medium: this.getUrlParameter('utm_medium'),
                campaign: this.getUrlParameter('utm_campaign'),
                term: this.getUrlParameter('utm_term'),
                content: this.getUrlParameter('utm_content'),
            };

            // Store UTM parameters in sessionStorage
            if (Object.values(utm).some(v => v !== null)) {
                sessionStorage.setItem('plv5_utm', JSON.stringify(utm));
                console.log('📊 UTM parameters tracked:', utm);
            }
        },

        /**
         * Add UTM parameters to form submissions
         *
         * @param {object} data - Form data
         * @returns {object} Data with UTM parameters
         */
        addUTMToData(data) {
            const storedUTM = sessionStorage.getItem('plv5_utm');

            if (storedUTM) {
                try {
                    const utm = JSON.parse(storedUTM);
                    return { ...data, utm };
                } catch (e) {
                    console.error('UTM parse error:', e);
                }
            }

            return data;
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            PoradnikLandingV5.init();
        });
    } else {
        PoradnikLandingV5.init();
    }

    // Expose to global scope for external access
    window.PoradnikLandingV5 = PoradnikLandingV5;

})();

/**
 * Poradnik.pro Landing V5 - Full Front Rebuild
 *
 * @package PearBlog
 * @version 5.2.0
 */

(function() {
    'use strict';

    const SELECTOR = {
        main: '.poradnik-landing-v5',
        faqQuestion: '.plv5-faq-item__question',
        faqAnswer: '.plv5-faq-item__answer',
        statNumber: '.plv5-stat__number',
        reveal: '[data-reveal]',
        alert: '.plv5-alert',
    };

    const LandingV5 = {
        init() {
            this.mainNode = document.querySelector(SELECTOR.main);
            this.context = this.getContext();
            this.setupUTMTracking();
            this.setupForm('plv5HeroForm', 'hero');
            this.setupForm('plv5CtaForm', 'cta');
            this.setupFAQ();
            this.setupStatsCounter();
            this.setupSmoothScroll();
            this.setupRevealAnimations();
            this.setupMobileCtaVisibility();
        },

        getContext() {
            const dataset = this.mainNode ? this.mainNode.dataset : {};
            const width = Math.max(window.innerWidth || 0, document.documentElement.clientWidth || 0);

            return {
                abVariant: dataset.abVariant || 'a',
                industry: dataset.industry || 'general',
                landingVersion: dataset.landingVersion || '5.2.0',
                device: width <= 760 ? 'mobile' : (width <= 1024 ? 'tablet' : 'desktop'),
                viewport: width + 'x' + (window.innerHeight || 0),
                pageUrl: window.location.href,
                referrer: document.referrer || '',
            };
        },

        setupForm(formId, source) {
            const form = document.getElementById(formId);
            if (!form) {
                return;
            }

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                this.handleLeadSubmission(form, source);
            });
        },

        handleLeadSubmission(form, source) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (!submitBtn) {
                return;
            }

            this.clearFormAlerts(form);

            const formData = new FormData(form);
            const payload = {
                action: 'plv5_submit_lead',
                source,
                service: (formData.get('service') || '').toString().trim(),
                email: (formData.get('email') || '').toString().trim(),
                ab_variant: (formData.get('ab_variant') || this.context.abVariant || '').toString(),
                industry: (formData.get('industry') || this.context.industry || 'general').toString(),
                landing_version: (formData.get('landing_version') || this.context.landingVersion || '5.2.0').toString(),
                device: this.context.device,
                viewport: this.context.viewport,
                page_url: this.context.pageUrl,
                referrer: this.context.referrer,
            };

            const withUtm = this.addUTMToData(payload);
            const encodedPayload = this.toUrlEncoded(withUtm);

            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Wysyłanie...';

            fetch((window.poradnikData && poradnikData.ajaxUrl) || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                },
                body: encodedPayload,
            })
                .then((response) => response.json())
                .then((result) => {
                    if (result && result.success) {
                        this.showAlert(form, 'Dziękujemy. Zgłoszenie zostało wysłane.', 'success');
                        form.reset();
                        this.trackConversion(source, withUtm);
                        return;
                    }

                    const message = result && result.data && result.data.message
                        ? result.data.message
                        : 'Nie udało się wysłać zgłoszenia. Spróbuj ponownie.';
                    this.showAlert(form, message, 'error');
                })
                .catch(() => {
                    this.showAlert(form, 'Błąd połączenia. Sprawdź internet i spróbuj ponownie.', 'error');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                });
        },

        showAlert(form, message, type) {
            const alert = document.createElement('div');
            alert.className = 'plv5-alert plv5-alert--' + type;
            alert.textContent = message;
            form.appendChild(alert);

            window.setTimeout(() => {
                alert.remove();
            }, 5200);
        },

        clearFormAlerts(form) {
            form.querySelectorAll(SELECTOR.alert).forEach((node) => node.remove());
        },

        toUrlEncoded(payload) {
            const params = new URLSearchParams();

            Object.keys(payload).forEach((key) => {
                const value = payload[key];

                if (value && typeof value === 'object' && !Array.isArray(value)) {
                    Object.keys(value).forEach((subKey) => {
                        params.append('utm[' + subKey + ']', value[subKey] || '');
                    });
                    return;
                }

                params.append(key, value || '');
            });

            return params.toString();
        },

        setupFAQ() {
            const questions = document.querySelectorAll(SELECTOR.faqQuestion);
            if (!questions.length) {
                return;
            }

            questions.forEach((question) => {
                question.addEventListener('click', () => {
                    const faqId = question.getAttribute('data-faq-toggle');
                    if (!faqId) {
                        return;
                    }

                    const currentAnswer = document.querySelector('[data-faq-content="' + faqId + '"]');
                    if (!currentAnswer) {
                        return;
                    }

                    const alreadyOpen = currentAnswer.classList.contains('is-active');

                    document.querySelectorAll(SELECTOR.faqQuestion).forEach((item) => {
                        item.classList.remove('is-active');
                        item.setAttribute('aria-expanded', 'false');
                    });
                    document.querySelectorAll(SELECTOR.faqAnswer).forEach((item) => {
                        item.classList.remove('is-active');
                    });

                    if (!alreadyOpen) {
                        question.classList.add('is-active');
                        question.setAttribute('aria-expanded', 'true');
                        currentAnswer.classList.add('is-active');
                    }
                });
            });
        },

        setupStatsCounter() {
            const stats = document.querySelectorAll(SELECTOR.statNumber);
            if (!stats.length) {
                return;
            }

            const animateStat = (node) => {
                const target = parseFloat(node.getAttribute('data-count') || '0');
                const hasDecimal = !Number.isInteger(target);
                const duration = 1300;
                const start = performance.now();

                const tick = (now) => {
                    const progress = Math.min((now - start) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const value = target * eased;

                    node.textContent = hasDecimal
                        ? value.toFixed(1).replace('.', ',')
                        : Math.round(value).toLocaleString('pl-PL');

                    if (progress < 1) {
                        requestAnimationFrame(tick);
                    }
                };

                requestAnimationFrame(tick);
            };

            const observer = new IntersectionObserver((entries, self) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting || entry.target.dataset.animated === 'true') {
                        return;
                    }

                    entry.target.dataset.animated = 'true';
                    animateStat(entry.target);
                    self.unobserve(entry.target);
                });
            }, {
                threshold: 0.45,
            });

            stats.forEach((node) => observer.observe(node));
        },

        setupSmoothScroll() {
            document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
                anchor.addEventListener('click', (event) => {
                    const href = anchor.getAttribute('href');
                    if (!href || href === '#') {
                        return;
                    }

                    const target = document.querySelector(href);
                    if (!target) {
                        return;
                    }

                    event.preventDefault();
                    const top = target.getBoundingClientRect().top + window.scrollY - 20;
                    window.scrollTo({ top, behavior: 'smooth' });
                });
            });
        },

        setupRevealAnimations() {
            const items = document.querySelectorAll(SELECTOR.reveal);
            if (!items.length) {
                return;
            }

            const observer = new IntersectionObserver((entries, self) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    entry.target.classList.add('is-visible');
                    self.unobserve(entry.target);
                });
            }, {
                threshold: 0.12,
                rootMargin: '0px 0px -30px 0px',
            });

            items.forEach((item, index) => {
                item.style.transitionDelay = Math.min(index * 40, 240) + 'ms';
                observer.observe(item);
            });
        },

        setupMobileCtaVisibility() {
            const stickyCta = document.querySelector('.plv5-mobile-cta');
            if (!stickyCta) {
                return;
            }

            const hero = document.getElementById('top');
            if (!hero) {
                return;
            }

            const toggle = () => {
                const show = window.scrollY > hero.offsetHeight * 0.5;
                stickyCta.style.opacity = show ? '1' : '0';
                stickyCta.style.pointerEvents = show ? 'auto' : 'none';
            };

            stickyCta.style.opacity = '0';
            stickyCta.style.pointerEvents = 'none';
            stickyCta.style.transition = 'opacity 220ms ease';

            toggle();
            window.addEventListener('scroll', toggle, { passive: true });
        },

        setupUTMTracking() {
            const params = new URLSearchParams(window.location.search);
            const utm = {
                source: params.get('utm_source'),
                medium: params.get('utm_medium'),
                campaign: params.get('utm_campaign'),
                term: params.get('utm_term'),
                content: params.get('utm_content'),
            };

            const hasUtm = Object.keys(utm).some((key) => !!utm[key]);
            if (!hasUtm) {
                return;
            }

            sessionStorage.setItem('plv5_utm', JSON.stringify(utm));
        },

        addUTMToData(payload) {
            const raw = sessionStorage.getItem('plv5_utm');
            if (!raw) {
                return payload;
            }

            try {
                const utm = JSON.parse(raw);
                return Object.assign({}, payload, { utm });
            } catch (_err) {
                return payload;
            }
        },

        trackConversion(source, payload) {
            const eventPayload = {
                source,
                service_type: payload.service || 'unknown',
                ab_variant: payload.ab_variant || this.context.abVariant,
                industry: payload.industry || this.context.industry,
                landing_version: payload.landing_version || this.context.landingVersion,
                device: payload.device || this.context.device,
                viewport: payload.viewport || this.context.viewport,
                utm_source: payload.utm && payload.utm.source ? payload.utm.source : '',
                utm_medium: payload.utm && payload.utm.medium ? payload.utm.medium : '',
                utm_campaign: payload.utm && payload.utm.campaign ? payload.utm.campaign : '',
            };

            if (typeof gtag !== 'undefined') {
                gtag('event', 'generate_lead', Object.assign({
                    event_category: 'Lead',
                    event_label: source,
                    value: 1,
                }, eventPayload));
            }

            if (typeof fbq !== 'undefined') {
                fbq('track', 'Lead', {
                    content_name: source,
                    content_category: eventPayload.industry,
                    value: 1,
                    currency: 'PLN',
                });
            }

            if (typeof window.poradnikTrack === 'function') {
                window.poradnikTrack('lead_submitted_v5', eventPayload);
            }
        },
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => LandingV5.init());
    } else {
        LandingV5.init();
    }
})();

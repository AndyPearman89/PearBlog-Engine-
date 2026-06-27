/**
 * PT24.PRO Theme - JavaScript
 *
 * @package PT24
 * @version 1.0.0
 */

(function () {
    'use strict';

    /**
     * Smooth scroll for anchor links
     */
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            var targetId = this.getAttribute('href');
            if (targetId === '#') return;

            var target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                var headerHeight = document.querySelector('header') ? document.querySelector('header').offsetHeight : 0;
                var targetPosition = target.getBoundingClientRect().top + window.pageYOffset - headerHeight - 20;

                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });

    /**
     * Intersection Observer for fade-in animations
     */
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('pt24-animate-fade-in');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        document.querySelectorAll('section > div').forEach(function (el) {
            el.style.opacity = '0';
            observer.observe(el);
        });
    }

    /**
     * Header scroll effect
     */
    var header = document.querySelector('header');
    if (header) {
        window.addEventListener('scroll', function () {
            if (window.pageYOffset > 100) {
                header.classList.add('shadow-sm');
            } else {
                header.classList.remove('shadow-sm');
            }
        }, { passive: true });
    }

    /**
     * Counter animation
     */
    function animateCounter(el) {
        var target = parseInt(el.getAttribute('data-counter-target') || '0', 10);
        if (!target || el.dataset.counterDone === '1') {
            return;
        }

        var startTime = null;
        var duration = 1300;
        var suffix = el.textContent.indexOf('%') !== -1 ? '%' : '+';

        function step(timestamp) {
            if (!startTime) startTime = timestamp;
            var progress = Math.min((timestamp - startTime) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3);
            var current = Math.floor(target * eased);

            el.textContent = current.toLocaleString('pl-PL') + suffix;

            if (progress < 1) {
                window.requestAnimationFrame(step);
            } else {
                el.dataset.counterDone = '1';
            }
        }

        window.requestAnimationFrame(step);
    }

    var counterEls = document.querySelectorAll('[data-counter-target]');
    if ('IntersectionObserver' in window && counterEls.length) {
        var counterObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });

        counterEls.forEach(function (counterEl) {
            counterObserver.observe(counterEl);
        });
    }

    /**
     * Interactive Poland map on homepage
     */
    var mapSection = document.querySelector('.pt24-map-live');
    if (mapSection) {
        var mapControls = mapSection.querySelectorAll('[data-map-city-slug]');
        var selectedCityEl = mapSection.querySelector('[data-map-selected-city]');
        var selectedServiceEl = mapSection.querySelector('[data-map-selected-service]');
        var selectedTimeEl = mapSection.querySelector('[data-map-selected-time]');
        var selectedOffersEl = mapSection.querySelector('[data-map-selected-offers]');
        var selectedLinkEl = mapSection.querySelector('[data-map-selected-link]');
        var panelEl = mapSection.querySelector('[aria-live="polite"]');

        function setActiveCity(control, animate) {
            if (!control) {
                return;
            }

            var citySlug = control.getAttribute('data-map-city-slug') || '';
            var cityName = control.getAttribute('data-map-city') || '';
            var cityService = control.getAttribute('data-map-service') || '';
            var cityTime = control.getAttribute('data-map-time') || '';
            var cityOffers = control.getAttribute('data-map-offers') || '';
            var cityUrl = control.getAttribute('data-map-url') || '';

            mapControls.forEach(function (item) {
                var itemSlug = item.getAttribute('data-map-city-slug') || '';
                item.classList.toggle('is-active', itemSlug === citySlug);
            });

            // Add pulse animation to panel on update
            if (animate !== false && panelEl) {
                panelEl.style.animation = 'none';
                setTimeout(function () {
                    panelEl.style.animation = '';
                }, 10);
            }

            // Update panel content with fade effect
            if (selectedCityEl) {
                selectedCityEl.style.opacity = '0.5';
                setTimeout(function () {
                    selectedCityEl.textContent = cityName;
                    selectedCityEl.style.transition = 'opacity 0.3s ease';
                    selectedCityEl.style.opacity = '1';
                }, 100);
            }
            
            if (selectedServiceEl) {
                selectedServiceEl.textContent = cityService;
            }
            if (selectedTimeEl) {
                selectedTimeEl.textContent = cityTime;
            }
            if (selectedOffersEl) {
                selectedOffersEl.textContent = cityOffers;
            }
            if (selectedLinkEl) {
                selectedLinkEl.setAttribute('href', cityUrl);
                selectedLinkEl.textContent = 'Zobacz oferty w regionie ' + cityName;
            }
        }

        mapControls.forEach(function (control) {
            control.addEventListener('click', function () {
                setActiveCity(control, true);
            });

            control.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    setActiveCity(control, true);
                }
            });

            // Add visual feedback on mouse enter for SVG paths
            control.addEventListener('mouseenter', function () {
                if (control.tagName.toLowerCase() === 'path' || control.tagName.toLowerCase() === 'polygon') {
                    control.style.filter = 'drop-shadow(0 0 4px rgba(46, 211, 198, 0.6))';
                }
            });

            control.addEventListener('mouseleave', function () {
                if (control.classList.contains('is-active')) {
                    control.style.filter = 'drop-shadow(0 0 0 rgba(46, 211, 198, 0.5))';
                } else {
                    control.style.filter = 'none';
                }
            });
        });

        // Initialize with animation disabled
        setActiveCity(mapSection.querySelector('[data-map-city-slug].is-active'), false);

        // Add scroll animation to region chips
        var chipGrid = mapSection.querySelector('.pt24-map-live-city-grid');
        if (chipGrid) {
            var scrollLeft = 0;
            chipGrid.addEventListener('scroll', function () {
                scrollLeft = chipGrid.scrollLeft;
            });
        }
    }
})();

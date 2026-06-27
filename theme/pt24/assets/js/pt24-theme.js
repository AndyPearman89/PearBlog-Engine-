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
})();

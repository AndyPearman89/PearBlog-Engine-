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
})();

/**
 * PT24.PRO — front-end UX enhancements.
 *
 * Loaded host-guarded on the PT24 install only. Progressive enhancement:
 * everything degrades gracefully if JS is disabled.
 *
 *  1. Smooth scrolling for same-page anchor links (#uslugi, #pt24-lead, …).
 *  2. Scroll-reveal animations for cards/sections (IntersectionObserver),
 *     disabled when the user prefers reduced motion.
 *  3. A sticky "order a quote" CTA on landing pages (mobile), which scrolls to
 *     the lead form and hides once the form is on screen.
 */
(function () {
	'use strict';

	var reduceMotion = window.matchMedia &&
		window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function ready(fn) {
		if (document.readyState !== 'loading') {
			fn();
		} else {
			document.addEventListener('DOMContentLoaded', fn);
		}
	}

	ready(function () {
		initSmoothScroll();
		initScrollReveal();
		initStickyCta();
		initStickyNav();
		initHamburger();
		initScrollProgress();
	});

	/* 1. Smooth scroll for in-page anchors. */
	function initSmoothScroll() {
		document.addEventListener('click', function (e) {
			var link = e.target.closest('a[href*="#"]');
			if (!link) {
				return;
			}
			// Only handle links that resolve to the current document.
			if (link.pathname !== window.location.pathname ||
				link.hostname !== window.location.hostname) {
				return;
			}
			var id = link.hash ? link.hash.slice(1) : '';
			if (!id) {
				return;
			}
			var target = document.getElementById(id);
			if (!target) {
				return;
			}
			e.preventDefault();
			target.scrollIntoView({
				behavior: reduceMotion ? 'auto' : 'smooth',
				block: 'start'
			});
			if (history.replaceState) {
				history.replaceState(null, '', link.hash);
			}
		});
	}

	/* 2. Reveal-on-scroll animations. */
	function initScrollReveal() {
		if (reduceMotion || !('IntersectionObserver' in window)) {
			return;
		}
		var selector = [
			'.pt24-home__card',
			'.pt24-home__features li',
			'.pt24-home__stats li',
			'.pt24-home__city-links a',
			'.pt24-benefits li',
			'.pt24-features li',
			'.pt24-flow li',
			'.pt24-review',
			'.pt24-firm'
		].join(',');

		var els = document.querySelectorAll(selector);
		if (!els.length) {
			return;
		}

		document.body.classList.add('pt24-reveal-on');

		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-visible');
					observer.unobserve(entry.target);
				}
			});
		}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

		Array.prototype.forEach.call(els, function (el, i) {
			el.classList.add('pt24-anim');
			// Light stagger within a row of up to 6 items.
			el.style.transitionDelay = (Math.min(i % 6, 5) * 45) + 'ms';
			observer.observe(el);
		});
	}

	/* 3. Sticky mobile CTA on landing pages. */
	function initStickyCta() {
		var lead = document.getElementById('pt24-lead');
		if (!lead || !document.querySelector('.pt24-landing')) {
			return;
		}

		var cta = document.createElement('a');
		cta.href = '#pt24-lead';
		cta.className = 'pt24-sticky-cta';
		cta.textContent = 'Zamów bezpłatną wycenę';
		document.body.appendChild(cta);

		var update = function () {
			var scrollY = window.pageYOffset || document.documentElement.scrollTop;
			var formTop = lead.getBoundingClientRect().top + scrollY;
			// Show after the hero, hide once the form itself is in view.
			var show = scrollY > 520 && (scrollY + window.innerHeight) < (formTop + 120);
			cta.classList.toggle('is-visible', show);
		};

		window.addEventListener('scroll', update, { passive: true });
		window.addEventListener('resize', update, { passive: true });
		update();
	}

	/* 4. Sticky nav — adds .is-scrolled class for backdrop-blur & shadow. */
	function initStickyNav() {
		var header = document.getElementById('pb-header');
		if (!header) return;
		var update = function () {
			header.classList.toggle('is-scrolled', window.pageYOffset > 50);
		};
		window.addEventListener('scroll', update, { passive: true });
		update();
	}

	/* 5. Mobile hamburger toggle. */
	function initHamburger() {
		var btn = document.querySelector('.pb-menu-toggle');
		var menu = document.getElementById('primary-menu');
		if (!btn || !menu) return;
		btn.addEventListener('click', function () {
			var expanded = btn.getAttribute('aria-expanded') === 'true';
			btn.setAttribute('aria-expanded', !expanded);
			menu.classList.toggle('is-open', !expanded);
		});
		// Close on outside click
		document.addEventListener('click', function (e) {
			if (!btn.contains(e.target) && !menu.contains(e.target)) {
				btn.setAttribute('aria-expanded', 'false');
				menu.classList.remove('is-open');
			}
		});
	}

	/* 6. Scroll progress bar. */
	function initScrollProgress() {
		var bar = document.createElement('div');
		bar.className = 'pt24-scroll-progress';
		bar.style.width = '0%';
		document.body.appendChild(bar);
		var update = function () {
			var doc = document.documentElement;
			var scrollTop = window.pageYOffset || doc.scrollTop;
			var scrollHeight = doc.scrollHeight - doc.clientHeight;
			var pct = scrollHeight > 0 ? Math.round(scrollTop / scrollHeight * 100) : 0;
			bar.style.width = pct + '%';
		};
		window.addEventListener('scroll', update, { passive: true });
	}


})();

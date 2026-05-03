/**
 * PearBlog Landing V2 Pro - Mobile-First Interactions
 * Touch-optimized, conversion-focused JavaScript
 *
 * @package PearBlog
 * @version 2.0.0
 */

(function() {
  'use strict';

  /**
   * Sticky Mobile CTA Handler
   * Shows CTA after 150px scroll
   */
  class StickyMobileCTA {
    constructor() {
      this.cta = document.getElementById('v2pro-mobile-cta');
      if (!this.cta) return;

      this.threshold = 150;
      this.init();
    }

    init() {
      window.addEventListener('scroll', () => this.handleScroll(), { passive: true });
    }

    handleScroll() {
      if (window.scrollY > this.threshold) {
        this.cta.classList.add('show');
      } else {
        this.cta.classList.remove('show');
      }
    }
  }

  /**
   * Touch Feedback for Buttons
   * Provides tactile response on touch
   */
  class TouchFeedback {
    constructor() {
      this.buttons = document.querySelectorAll('.v2pro-btn, .v2pro-card, .v2pro-category-card');
      if (!this.buttons.length) return;

      this.init();
    }

    init() {
      this.buttons.forEach(btn => {
        btn.addEventListener('touchstart', () => {
          btn.style.transform = 'scale(0.97)';
        }, { passive: true });

        btn.addEventListener('touchend', () => {
          btn.style.transform = '';
        }, { passive: true });

        btn.addEventListener('touchcancel', () => {
          btn.style.transform = '';
        }, { passive: true });
      });
    }
  }

  /**
   * Input Interaction Handler
   * Highlights CTA when user interacts with input
   */
  class InputInteraction {
    constructor() {
      this.inputs = document.querySelectorAll('.v2pro-input, .v2pro-ai-input');
      this.primaryCTA = document.querySelector('.v2pro-btn:not(.v2pro-btn-secondary)');

      if (!this.inputs.length || !this.primaryCTA) return;

      this.init();
    }

    init() {
      this.inputs.forEach(input => {
        input.addEventListener('input', () => {
          if (input.value.length > 3) {
            this.primaryCTA.classList.add('v2pro-glow');
          } else {
            this.primaryCTA.classList.remove('v2pro-glow');
          }
        });
      });
    }
  }

  /**
   * Glow Follow Cursor (Desktop Only)
   * Makes glow effect follow mouse movement
   */
  class GlowFollowCursor {
    constructor() {
      this.glowElements = document.querySelectorAll('.v2pro-card, .v2pro-hero');
      if (!this.glowElements.length) return;

      // Only enable on desktop
      if (window.innerWidth >= 768) {
        this.init();
      }
    }

    init() {
      document.addEventListener('mousemove', (e) => {
        this.glowElements.forEach(el => {
          const rect = el.getBoundingClientRect();
          const x = e.clientX - rect.left;
          const y = e.clientY - rect.top;

          // Only apply if cursor is near the element
          if (x >= -100 && x <= rect.width + 100 && y >= -100 && y <= rect.height + 100) {
            const moveX = (e.clientX / window.innerWidth - 0.5) * 20;
            const moveY = (e.clientY / window.innerHeight - 0.5) * 20;
            el.style.transform = `translate(${moveX}px, ${moveY}px)`;
          }
        });
      }, { passive: true });
    }
  }

  /**
   * Mobile Exit Intent Detection
   * Shows popup when user is about to leave (blur event)
   */
  class MobileExitIntent {
    constructor() {
      this.popup = document.getElementById('v2pro-exit-popup');
      if (!this.popup) return;

      this.hasShown = false;
      this.init();
    }

    init() {
      // On mobile, detect when window loses focus
      window.addEventListener('blur', () => {
        if (!this.hasShown && window.scrollY > 300) {
          this.showPopup();
        }
      });

      // On desktop, detect when cursor leaves viewport
      if (window.innerWidth >= 768) {
        document.addEventListener('mouseleave', (e) => {
          if (e.clientY < 0 && !this.hasShown) {
            this.showPopup();
          }
        });
      }
    }

    showPopup() {
      this.popup.classList.add('show');
      this.hasShown = true;

      // Track event
      this.trackEvent('exit_intent_shown');
    }

    trackEvent(eventName) {
      if (window.pearblogData && window.pearblogData.ajaxurl) {
        fetch(window.pearblogData.ajaxurl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'pearblog_track_event',
            event: eventName,
            nonce: window.pearblogData.nonce
          })
        });
      }
    }
  }

  /**
   * FAQ Accordion Handler
   */
  class FAQAccordion {
    constructor() {
      this.items = document.querySelectorAll('.v2pro-faq-item');
      if (!this.items.length) return;

      this.init();
    }

    init() {
      this.items.forEach(item => {
        const question = item.querySelector('.v2pro-faq-question');
        if (!question) return;

        question.addEventListener('click', () => {
          const isOpen = item.classList.contains('open');

          // Close all other items
          this.items.forEach(i => i.classList.remove('open'));

          // Toggle current item
          if (!isOpen) {
            item.classList.add('open');
          }
        });
      });
    }
  }

  /**
   * Scroll Progress Tracking
   * Tracks user scroll depth for analytics
   */
  class ScrollProgressTracking {
    constructor() {
      this.milestones = [25, 50, 75, 90];
      this.reached = new Set();
      this.init();
    }

    init() {
      window.addEventListener('scroll', () => this.handleScroll(), { passive: true });
    }

    handleScroll() {
      const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
      const scrollPercent = (window.scrollY / scrollHeight) * 100;

      this.milestones.forEach(milestone => {
        if (scrollPercent >= milestone && !this.reached.has(milestone)) {
          this.reached.add(milestone);
          this.trackEvent(`scroll_${milestone}`);
        }
      });
    }

    trackEvent(eventName) {
      if (window.pearblogData && window.pearblogData.ajaxurl) {
        fetch(window.pearblogData.ajaxurl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'pearblog_track_event',
            event: eventName,
            nonce: window.pearblogData.nonce
          })
        });
      }
    }
  }

  /**
   * CTA Click Tracking
   */
  class CTATracking {
    constructor() {
      this.ctas = document.querySelectorAll('.v2pro-btn[data-cta-id]');
      if (!this.ctas.length) return;

      this.init();
    }

    init() {
      this.ctas.forEach(cta => {
        cta.addEventListener('click', (e) => {
          const ctaId = cta.dataset.ctaId;
          const ctaLocation = cta.dataset.ctaLocation || 'unknown';

          this.trackCTA(ctaId, ctaLocation);
        });
      });
    }

    trackCTA(ctaId, location) {
      if (window.pearblogData && window.pearblogData.ajaxurl) {
        fetch(window.pearblogData.ajaxurl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'pearblog_track_cta_click',
            cta_id: ctaId,
            location: location,
            nonce: window.pearblogData.nonce
          })
        });
      }
    }
  }

  /**
   * Form Auto-Save (Local Storage)
   * Prevents data loss on mobile
   */
  class FormAutoSave {
    constructor() {
      this.forms = document.querySelectorAll('form[data-autosave]');
      if (!this.forms.length) return;

      this.init();
    }

    init() {
      this.forms.forEach(form => {
        const formId = form.dataset.autosave;

        // Restore saved data
        this.restoreFormData(form, formId);

        // Save on input
        form.addEventListener('input', () => {
          this.saveFormData(form, formId);
        });

        // Clear on submit
        form.addEventListener('submit', () => {
          this.clearFormData(formId);
        });
      });
    }

    saveFormData(form, formId) {
      const formData = new FormData(form);
      const data = Object.fromEntries(formData.entries());
      localStorage.setItem(`v2pro_form_${formId}`, JSON.stringify(data));
    }

    restoreFormData(form, formId) {
      const saved = localStorage.getItem(`v2pro_form_${formId}`);
      if (!saved) return;

      try {
        const data = JSON.parse(saved);
        Object.entries(data).forEach(([name, value]) => {
          const input = form.querySelector(`[name="${name}"]`);
          if (input) {
            input.value = value;
          }
        });
      } catch (e) {
        console.error('Error restoring form data:', e);
      }
    }

    clearFormData(formId) {
      localStorage.removeItem(`v2pro_form_${formId}`);
    }
  }

  /**
   * Lazy Load Images
   * Improves mobile performance
   */
  class LazyLoadImages {
    constructor() {
      this.images = document.querySelectorAll('img[data-lazy-src]');
      if (!this.images.length) return;

      this.init();
    }

    init() {
      if ('IntersectionObserver' in window) {
        this.observer = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              this.loadImage(entry.target);
            }
          });
        }, {
          rootMargin: '50px'
        });

        this.images.forEach(img => this.observer.observe(img));
      } else {
        // Fallback: load all images
        this.images.forEach(img => this.loadImage(img));
      }
    }

    loadImage(img) {
      const src = img.dataset.lazySrc;
      if (!src) return;

      img.src = src;
      img.removeAttribute('data-lazy-src');
      img.classList.add('loaded');

      if (this.observer) {
        this.observer.unobserve(img);
      }
    }
  }

  /**
   * Performance Monitor
   * Tracks page load time for optimization
   */
  class PerformanceMonitor {
    constructor() {
      this.init();
    }

    init() {
      if ('PerformanceObserver' in window) {
        // Monitor largest contentful paint
        const observer = new PerformanceObserver((list) => {
          const entries = list.getEntries();
          const lastEntry = entries[entries.length - 1];

          this.trackMetric('lcp', lastEntry.renderTime || lastEntry.loadTime);
        });
        observer.observe({ entryTypes: ['largest-contentful-paint'] });
      }

      // Track page load time
      window.addEventListener('load', () => {
        setTimeout(() => {
          const perfData = window.performance.timing;
          const loadTime = perfData.loadEventEnd - perfData.navigationStart;

          this.trackMetric('page_load_time', loadTime);
        }, 0);
      });
    }

    trackMetric(metric, value) {
      if (window.pearblogData && window.pearblogData.ajaxurl) {
        fetch(window.pearblogData.ajaxurl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'pearblog_track_performance',
            metric: metric,
            value: value,
            nonce: window.pearblogData.nonce
          })
        });
      }
    }
  }

  /**
   * Initialize all V2 Pro Mobile features
   */
  function initV2ProMobile() {
    // Core interactions
    new StickyMobileCTA();
    new TouchFeedback();
    new InputInteraction();
    new GlowFollowCursor();
    new FAQAccordion();

    // Tracking & analytics
    new ScrollProgressTracking();
    new CTATracking();
    new PerformanceMonitor();

    // UX enhancements
    new FormAutoSave();
    new LazyLoadImages();

    // Exit intent (optional - only if popup exists)
    new MobileExitIntent();

    // Dispatch custom event
    document.dispatchEvent(new CustomEvent('v2pro:initialized'));

    // Log initialization (remove in production)
    if (window.console && window.console.log) {
      console.log('✨ V2 Pro Mobile initialized');
    }
  }

  // Initialize on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initV2ProMobile);
  } else {
    initV2ProMobile();
  }

  // Export for external access
  window.V2ProMobile = {
    StickyMobileCTA,
    TouchFeedback,
    InputInteraction,
    GlowFollowCursor,
    MobileExitIntent,
    FAQAccordion,
    ScrollProgressTracking,
    CTATracking,
    FormAutoSave,
    LazyLoadImages,
    PerformanceMonitor
  };

})();

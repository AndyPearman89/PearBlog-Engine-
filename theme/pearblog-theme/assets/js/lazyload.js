/**
 * PearBlog v2 PRO - Lazy Load Module
 * Advanced lazy loading for images and iframes
 *
 * @version 2.0.0
 */

(function() {
    'use strict';

    // Configuration
    const config = {
        rootMargin: '50px 0px',
        threshold: 0.01,
    };

    // Intersection Observer for lazy loading
    let imageObserver;
    let iframeObserver;

    /**
     * Initialize lazy loading
     */
    function initLazyLoad() {
        if ('loading' in HTMLImageElement.prototype) {
            // Browser supports native lazy loading
            initNativeLazyLoad();
        } else {
            // Fallback to Intersection Observer
            initObserverLazyLoad();
        }

        // Always use observer for iframes
        initIframeLazyLoad();
    }

    /**
     * Native lazy loading support
     */
    function initNativeLazyLoad() {
        const images = document.querySelectorAll('img[loading="lazy"]');
        images.forEach(img => {
            if (img.complete) {
                handleImageLoad(img);
            } else {
                img.addEventListener('load', function() {
                    handleImageLoad(this);
                });
            }
        });
    }

    /**
     * Intersection Observer lazy loading
     */
    function initObserverLazyLoad() {
        const images = document.querySelectorAll('img[data-src], img[loading="lazy"]');

        if (!images.length) return;

        imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    loadImage(img);
                    observer.unobserve(img);
                }
            });
        }, config);

        images.forEach(img => imageObserver.observe(img));
    }

    /**
     * Load image
     */
    function loadImage(img) {
        // If data-src is set, use it
        if (img.dataset.src) {
            img.src = img.dataset.src;
        }

        // Load srcset if available
        if (img.dataset.srcset) {
            img.srcset = img.dataset.srcset;
        }

        img.addEventListener('load', function() {
            handleImageLoad(this);
        });
    }

    /**
     * Handle image load
     */
    function handleImageLoad(img) {
        img.classList.add('loaded');
        img.classList.remove('pb-lazy-image');

        // Remove data attributes
        delete img.dataset.src;
        delete img.dataset.srcset;
    }

    /**
     * Lazy load iframes (for embeds)
     */
    function initIframeLazyLoad() {
        const iframes = document.querySelectorAll('iframe[data-src]');

        if (!iframes.length) return;

        iframeObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const iframe = entry.target;
                    iframe.src = iframe.dataset.src;
                    delete iframe.dataset.src;
                    observer.unobserve(iframe);
                }
            });
        }, config);

        iframes.forEach(iframe => iframeObserver.observe(iframe));
    }

    /**
     * Preload critical images
     */
    function preloadCriticalImages() {
        const criticalImages = document.querySelectorAll('[data-preload="true"]');

        criticalImages.forEach(img => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = 'image';
            link.href = img.src || img.dataset.src;
            document.head.appendChild(link);
        });
    }

    /**
     * Background image lazy loading
     */
    function initBackgroundLazyLoad() {
        const bgElements = document.querySelectorAll('[data-bg]');

        if (!bgElements.length) return;

        const bgObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    el.style.backgroundImage = `url(${el.dataset.bg})`;
                    el.classList.add('bg-loaded');
                    delete el.dataset.bg;
                    observer.unobserve(el);
                }
            });
        }, config);

        bgElements.forEach(el => bgObserver.observe(el));
    }

    /**
     * Video lazy loading
     */
    function initVideoLazyLoad() {
        const videos = document.querySelectorAll('video[data-src]');

        if (!videos.length) return;

        const videoObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const video = entry.target;
                    const sources = video.querySelectorAll('source[data-src]');

                    sources.forEach(source => {
                        source.src = source.dataset.src;
                        delete source.dataset.src;
                    });

                    video.load();
                    observer.unobserve(video);
                }
            });
        }, config);

        videos.forEach(video => videoObserver.observe(video));
    }

    /**
     * Cleanup observers on page unload
     */
    function cleanup() {
        if (imageObserver) imageObserver.disconnect();
        if (iframeObserver) iframeObserver.disconnect();
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initLazyLoad();
            preloadCriticalImages();
            initBackgroundLazyLoad();
            initVideoLazyLoad();
        });
    } else {
        initLazyLoad();
        preloadCriticalImages();
        initBackgroundLazyLoad();
        initVideoLazyLoad();
    }

    // Cleanup on unload
    window.addEventListener('beforeunload', cleanup);

    // Export for external use
    window.PearBlogLazyLoad = {
        init: initLazyLoad,
        preloadCritical: preloadCriticalImages,
    };

})();

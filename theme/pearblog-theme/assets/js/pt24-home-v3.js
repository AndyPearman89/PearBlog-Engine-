/**
 * PT24.PRO Homepage V3 (Production Copy)
 * JavaScript for search, lead form, and dynamic features
 *
 * @version 3.0.0
 */

(function() {
    'use strict';

    /**
     * Search functionality
     */
    function initSearch() {
        const searchButton = document.getElementById('pt24-search-button');
        const serviceInput = document.getElementById('pt24-search-service');
        const cityInput = document.getElementById('pt24-search-city');

        if (!searchButton) return;

        searchButton.addEventListener('click', function(e) {
            e.preventDefault();

            const service = serviceInput.value.trim().toLowerCase();
            const city = cityInput.value.trim().toLowerCase();

            if (!service && !city) {
                // No input, scroll to categories
                scrollToElement('#pt24-v3-quote');
                return;
            }

            // Build URL based on inputs
            let url = '/';

            if (service && city) {
                // Both service and city
                url = '/' + normalizeSlug(service) + '/' + normalizeSlug(city) + '/';
            } else if (service) {
                // Only service
                url = '/' + normalizeSlug(service) + '/';
            } else if (city) {
                // Only city
                url = '/' + normalizeSlug(city) + '/';
            }

            window.location.href = url;
        });

        // Enter key support
        [serviceInput, cityInput].forEach(function(input) {
            if (input) {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        searchButton.click();
                    }
                });
            }
        });
    }

    /**
     * Lead capture form handling
     */
    function initQuoteForm() {
        const form = document.getElementById('pt24-quote-form');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(form);
            const data = {
                action: 'pt24_submit_lead',
                nonce: pt24Data.nonce,
                service: formData.get('service'),
                city: formData.get('city'),
                description: formData.get('description'),
                name: formData.get('name'),
                phone: formData.get('phone')
            };

            // Disable submit button
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Wysyłanie...';

            // Send AJAX request
            fetch(pt24Data.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Success - show message and redirect
                    alert('Dziękujemy! Twoje zapytanie zostało wysłane. Fachowcy skontaktują się z Tobą wkrótce.');
                    form.reset();

                    // Track conversion
                    trackConversion('lead_submitted');

                    // Optional: redirect to thank you page
                    // window.location.href = '/dziekujemy/';
                } else {
                    alert('Wystąpił błąd. Spróbuj ponownie lub skontaktuj się z nami telefonicznie.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Wystąpił błąd połączenia. Sprawdź połączenie internetowe i spróbuj ponownie.');
            })
            .finally(() => {
                // Re-enable submit button
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            });
        });
    }

    /**
     * Smooth scroll for anchor links
     */
    function initSmoothScroll() {
        const smoothScrollLinks = document.querySelectorAll('.pt24-smooth-scroll');

        smoothScrollLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');

                if (href && href.startsWith('#')) {
                    e.preventDefault();
                    scrollToElement(href);
                }
            });
        });
    }

    /**
     * Social proof ticker animation
     */
    function initSocialProof() {
        const ticker = document.getElementById('pt24-social-proof-ticker');
        if (!ticker) return;

        // Clone items for seamless loop
        const items = ticker.innerHTML;
        ticker.innerHTML = items + items;

        // Randomize messages periodically
        setInterval(function() {
            updateSocialProofMessage();
        }, 8000);
    }

    /**
     * Update social proof message with dynamic content
     */
    function updateSocialProofMessage() {
        const messages = [
            '"Klient z Katowic otrzymał 3 oferty w 12 minut"',
            '"Nowe zapytanie: hydraulik Kraków – wysłano do 4 firm"',
            '"Klient z Warszawy wybrał ofertę – naprawiona w 2 godziny"',
            '"Mechanik z Wrocławia odebrał lead – klient umówiony na jutro"',
            '"Zapytanie: remont łazienki Gdańsk – 5 firm zainteresowanych"',
            '"Elektryk z Poznania odpowiedział w 8 minut"'
        ];

        const ticker = document.getElementById('pt24-social-proof-ticker');
        if (!ticker) return;

        const items = ticker.querySelectorAll('.pt24-v3-social-proof-item');
        if (items.length === 0) return;

        // Pick random message
        const randomIndex = Math.floor(Math.random() * messages.length);
        const randomItem = Math.floor(Math.random() * items.length / 2); // First half only (before clone)

        if (items[randomItem]) {
            items[randomItem].textContent = messages[randomIndex];
        }
    }

    /**
     * Track conversion events
     */
    function trackConversion(eventType) {
        if (typeof gtag !== 'undefined') {
            gtag('event', eventType, {
                'event_category': 'pt24_conversion',
                'event_label': eventType
            });
        }

        // Custom tracking endpoint
        if (pt24Data && pt24Data.ajaxurl) {
            fetch(pt24Data.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'pt24_track_event',
                    event_type: eventType,
                    nonce: pt24Data.nonce
                })
            });
        }
    }

    /**
     * Helper: Normalize slug
     */
    function normalizeSlug(text) {
        const map = {
            'ą': 'a', 'ć': 'c', 'ę': 'e', 'ł': 'l',
            'ń': 'n', 'ó': 'o', 'ś': 's', 'ź': 'z', 'ż': 'z'
        };

        return text
            .toLowerCase()
            .replace(/[ąćęłńóśźż]/g, function(match) {
                return map[match] || match;
            })
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    /**
     * Helper: Scroll to element
     */
    function scrollToElement(selector) {
        const element = document.querySelector(selector);
        if (!element) return;

        const offsetTop = element.offsetTop - 80; // Account for fixed header

        window.scrollTo({
            top: offsetTop,
            behavior: 'smooth'
        });
    }

    /**
     * Track page views and user behavior
     */
    function trackPageView() {
        // Track scroll depth
        let maxScroll = 0;

        window.addEventListener('scroll', function() {
            const scrollPercent = (window.scrollY + window.innerHeight) / document.documentElement.scrollHeight * 100;

            if (scrollPercent > maxScroll) {
                maxScroll = Math.floor(scrollPercent / 25) * 25; // Track in 25% increments

                if (maxScroll === 25 || maxScroll === 50 || maxScroll === 75 || maxScroll === 100) {
                    trackConversion('scroll_depth_' + maxScroll);
                }
            }
        });

        // Track time on page
        let timeOnPage = 0;
        const timeInterval = setInterval(function() {
            timeOnPage += 30;

            if (timeOnPage === 30 || timeOnPage === 60 || timeOnPage === 120) {
                trackConversion('time_on_page_' + timeOnPage + 's');
            }

            if (timeOnPage >= 300) {
                clearInterval(timeInterval);
            }
        }, 30000); // Every 30 seconds
    }

    /**
     * Initialize all features
     */
    function init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }

        initSearch();
        initQuoteForm();
        initSmoothScroll();
        initSocialProof();
        trackPageView();

        // Track page load
        trackConversion('page_view');
    }

    // Initialize
    init();

})();

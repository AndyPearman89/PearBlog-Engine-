/**
 * PT24.PRO Homepage V4 (HI-PRO)
 * JavaScript for enhanced conversion features
 *
 * @version 4.0.0
 */

(function() {
    'use strict';

    /**
     * Search functionality
     */
    function initSearch() {
        const searchButton = document.getElementById('pt24-v4-search-button');
        const serviceInput = document.getElementById('pt24-v4-search-service');
        const cityInput = document.getElementById('pt24-v4-search-city');

        if (!searchButton) return;

        searchButton.addEventListener('click', function(e) {
            e.preventDefault();

            const service = serviceInput.value.trim().toLowerCase();
            const city = cityInput.value.trim().toLowerCase();

            if (!service && !city) {
                // No input, scroll to lead form
                scrollToElement('#pt24-v4-lead-block');
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
     * Auto-location detection
     */
    function initLocationDetection() {
        const detectBtn = document.getElementById('detect-location-btn');
        const locationInput = document.getElementById('lead-location');

        if (!detectBtn || !locationInput) return;

        detectBtn.addEventListener('click', function() {
            if (!navigator.geolocation) {
                alert('Geolokalizacja nie jest dostępna w Twojej przeglądarce');
                return;
            }

            // Show loading state
            detectBtn.textContent = '📍 Wykrywanie...';
            detectBtn.disabled = true;

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    // Success - reverse geocode
                    reverseGeocode(position.coords.latitude, position.coords.longitude)
                        .then(function(city) {
                            locationInput.value = city;
                            detectBtn.textContent = '✓ Wykryto lokalizację';
                            setTimeout(function() {
                                detectBtn.textContent = '📍 Wykryj moją lokalizację';
                                detectBtn.disabled = false;
                            }, 2000);
                        })
                        .catch(function() {
                            locationInput.value = 'Polska';
                            detectBtn.textContent = '📍 Wykryj moją lokalizację';
                            detectBtn.disabled = false;
                        });
                },
                function(error) {
                    // Error
                    console.error('Geolocation error:', error);
                    alert('Nie udało się wykryć lokalizacji. Wpisz miasto ręcznie.');
                    detectBtn.textContent = '📍 Wykryj moją lokalizację';
                    detectBtn.disabled = false;
                }
            );
        });
    }

    /**
     * Reverse geocode coordinates to city name
     */
    function reverseGeocode(lat, lng) {
        return new Promise(function(resolve, reject) {
            // Use OpenStreetMap Nominatim for reverse geocoding (free, no API key)
            const url = 'https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&accept-language=pl';

            fetch(url)
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data && data.address) {
                        // Try to get city, town, or village
                        const city = data.address.city ||
                                   data.address.town ||
                                   data.address.village ||
                                   data.address.county ||
                                   'Polska';
                        resolve(city);
                    } else {
                        reject('No city found');
                    }
                })
                .catch(function(error) {
                    console.error('Geocoding error:', error);
                    reject(error);
                });
        });
    }

    /**
     * Lead form submission
     */
    function initLeadForm() {
        const form = document.getElementById('pt24-v4-lead-form');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(form);
            const data = {
                action: 'pt24_submit_lead',
                nonce: pt24Data.nonce,
                service: formData.get('service'),
                city: formData.get('location'),
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
            .then(function(response) {
                return response.json();
            })
            .then(function(result) {
                if (result.success) {
                    // Success
                    alert('Dziękujemy! Twoje zapytanie zostało wysłane. Fachowcy skontaktują się z Tobą wkrótce.');
                    form.reset();

                    // Track conversion
                    trackConversion('lead_submitted');

                    // Scroll to top
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    alert('Wystąpił błąd. Spróbuj ponownie lub skontaktuj się z nami telefonicznie.');
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                alert('Wystąpił błąd połączenia. Sprawdź połączenie internetowe i spróbuj ponownie.');
            })
            .finally(function() {
                // Re-enable submit button
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            });
        });
    }

    /**
     * Live activity feed animation
     */
    function initLiveActivity() {
        const feed = document.getElementById('pt24-v4-live-feed');
        if (!feed) return;

        // Clone items for seamless loop
        const items = feed.innerHTML;
        feed.innerHTML = items + items;

        // Randomize messages periodically
        setInterval(function() {
            updateLiveActivity();
        }, 10000); // Every 10 seconds
    }

    /**
     * Update live activity with dynamic messages
     */
    function updateLiveActivity() {
        const messages = [
            '"Klient z Katowic otrzymał 4 oferty w 9 minut"',
            '"Nowe zapytanie: elektryk Kraków – wysłane do 3 firm"',
            '"Mechanik z Warszawy odebrał zlecenie – odpowiedź w 7 minut"',
            '"Hydraulik z Wrocławia zrealizował naprawę – ocena 5/5"',
            '"Zapytanie: remont Gdańsk – 6 firm zainteresowanych"',
            '"Klient z Poznania wybrał ofertę – umówiony w 24h"',
            '"Nowe zapytanie: klimatyzacja Katowice – wysłane do 4 firm"',
            '"Fachowiec odebrał telefon w 5 minut – Kraków"'
        ];

        const feed = document.getElementById('pt24-v4-live-feed');
        if (!feed) return;

        const items = feed.querySelectorAll('.pt24-v4-live-item');
        if (items.length === 0) return;

        // Pick random message
        const randomIndex = Math.floor(Math.random() * messages.length);
        const randomItem = Math.floor(Math.random() * (items.length / 2)); // First half only

        if (items[randomItem]) {
            // Update text, preserve pulse element
            const pulseEl = items[randomItem].querySelector('.pt24-v4-live-pulse');
            items[randomItem].innerHTML = '';
            items[randomItem].appendChild(pulseEl);
            items[randomItem].appendChild(document.createTextNode(messages[randomIndex]));
        }
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
     * Track conversion events
     */
    function trackConversion(eventType) {
        // Google Analytics
        if (typeof gtag !== 'undefined') {
            gtag('event', eventType, {
                'event_category': 'pt24_v4_conversion',
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
                    event_type: 'v4_' + eventType,
                    nonce: pt24Data.nonce
                })
            });
        }
    }

    /**
     * Track page behavior
     */
    function trackPageBehavior() {
        // Track scroll depth
        let maxScroll = 0;

        window.addEventListener('scroll', function() {
            const scrollPercent = (window.scrollY + window.innerHeight) / document.documentElement.scrollHeight * 100;

            if (scrollPercent > maxScroll) {
                maxScroll = Math.floor(scrollPercent / 25) * 25;

                if (maxScroll === 25 || maxScroll === 50 || maxScroll === 75 || maxScroll === 100) {
                    trackConversion('scroll_depth_' + maxScroll);
                }
            }
        });

        // Track time on page
        let timeOnPage = 0;
        const timeInterval = setInterval(function() {
            timeOnPage += 30;

            if (timeOnPage === 30 || timeOnPage === 60 || timeOnPage === 120 || timeOnPage === 180) {
                trackConversion('time_on_page_' + timeOnPage + 's');
            }

            if (timeOnPage >= 300) {
                clearInterval(timeInterval);
            }
        }, 30000); // Every 30 seconds

        // Track CTA interactions
        document.querySelectorAll('.pt24-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const text = this.textContent.trim();
                trackConversion('cta_click_' + normalizeSlug(text));
            });
        });

        // Track form focus (intent signal)
        const formInputs = document.querySelectorAll('#pt24-v4-lead-form input, #pt24-v4-lead-form textarea, #pt24-v4-lead-form select');
        let formFocused = false;

        formInputs.forEach(function(input) {
            input.addEventListener('focus', function() {
                if (!formFocused) {
                    formFocused = true;
                    trackConversion('form_interaction_started');
                }
            });
        });
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

        const offsetTop = element.offsetTop - 80;

        window.scrollTo({
            top: offsetTop,
            behavior: 'smooth'
        });
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
        initLocationDetection();
        initLeadForm();
        initLiveActivity();
        initSmoothScroll();
        trackPageBehavior();

        // Track page load
        trackConversion('page_view');
    }

    // Initialize
    init();

})();

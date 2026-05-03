/**
 * PT24.PRO Landing Page JavaScript
 *
 * Handles form submission, FAQ accordion, and smooth scrolling
 *
 * @package PearBlog
 * @version 1.0.0
 */

(function() {
    'use strict';

    /**
     * PT24 Landing Page Manager
     */
    const PT24Landing = {
        /**
         * Initialize all functionality
         */
        init: function() {
            this.setupSmoothScroll();
            this.setupFAQ();
            this.setupFormSubmission();
        },

        /**
         * Setup smooth scrolling for anchor links
         */
        setupSmoothScroll: function() {
            document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
                anchor.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');

                    // Skip if href is just "#"
                    if (href === '#') {
                        return;
                    }

                    const target = document.querySelector(href);

                    if (target) {
                        e.preventDefault();
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        },

        /**
         * Setup FAQ accordion functionality
         */
        setupFAQ: function() {
            document.querySelectorAll('[data-faq-toggle]').forEach(function(button) {
                button.addEventListener('click', function() {
                    const faqId = this.getAttribute('data-faq-toggle');
                    const answer = document.querySelector('[data-faq-content="' + faqId + '"]');

                    if (!answer) {
                        return;
                    }

                    const isOpen = answer.classList.contains('is-open');

                    // Close all other FAQs
                    document.querySelectorAll('.pt24-faq__answer').forEach(function(item) {
                        item.classList.remove('is-open');
                    });

                    document.querySelectorAll('.pt24-faq__question').forEach(function(item) {
                        item.setAttribute('aria-expanded', 'false');
                    });

                    // Toggle current FAQ
                    if (!isOpen) {
                        answer.classList.add('is-open');
                        this.setAttribute('aria-expanded', 'true');
                    }
                });
            });
        },

        /**
         * Setup form submission handling
         */
        setupFormSubmission: function() {
            const form = document.getElementById('pt24LeadForm');

            if (!form) {
                return;
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const submitButton = form.querySelector('button[type="submit"]');
                const originalButtonText = submitButton.textContent;

                // Disable submit button
                submitButton.disabled = true;
                submitButton.textContent = 'Wysyłanie...';

                // Collect form data
                const formData = new FormData(form);

                // Get AJAX URL
                const ajaxUrl = typeof pearblogData !== 'undefined' ? pearblogData.ajaxurl : '/wp-admin/admin-ajax.php';

                // Send AJAX request
                fetch(ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        // Show success message
                        PT24Landing.showMessage('success', 'Dziękujemy! Twoje zgłoszenie zostało wysłane. Skontaktujemy się z Tobą wkrótce.');

                        // Reset form
                        form.reset();

                        // Track conversion
                        if (typeof gtag !== 'undefined') {
                            gtag('event', 'conversion', {
                                'send_to': 'AW-XXXXX/XXXXX', // Replace with actual conversion ID
                                'transaction_id': data.data.lead_id || ''
                            });
                        }

                        // Redirect after 2 seconds
                        setTimeout(function() {
                            window.location.href = data.data.redirect_url || '/dziekujemy';
                        }, 2000);

                    } else {
                        // Show error message
                        PT24Landing.showMessage('error', data.data.message || 'Wystąpił błąd. Spróbuj ponownie.');
                    }
                })
                .catch(function(error) {
                    console.error('Form submission error:', error);
                    PT24Landing.showMessage('error', 'Wystąpił błąd połączenia. Spróbuj ponownie.');
                })
                .finally(function() {
                    // Re-enable submit button
                    submitButton.disabled = false;
                    submitButton.textContent = originalButtonText;
                });
            });
        },

        /**
         * Show message to user
         */
        showMessage: function(type, message) {
            // Remove existing messages
            const existingMessages = document.querySelectorAll('.pt24-form-message');
            existingMessages.forEach(function(msg) {
                msg.remove();
            });

            // Create message element
            const messageEl = document.createElement('div');
            messageEl.className = 'pt24-form-message pt24-form-message--' + type;
            messageEl.textContent = message;

            // Add styles
            messageEl.style.padding = '1rem';
            messageEl.style.marginBottom = '1rem';
            messageEl.style.borderRadius = '8px';
            messageEl.style.textAlign = 'center';
            messageEl.style.fontWeight = '600';

            if (type === 'success') {
                messageEl.style.backgroundColor = '#d1fae5';
                messageEl.style.color = '#065f46';
                messageEl.style.border = '2px solid #10b981';
            } else {
                messageEl.style.backgroundColor = '#fee2e2';
                messageEl.style.color = '#991b1b';
                messageEl.style.border = '2px solid #ef4444';
            }

            // Insert message
            const form = document.getElementById('pt24LeadForm');
            form.insertBefore(messageEl, form.firstChild);

            // Auto-remove after 5 seconds
            setTimeout(function() {
                messageEl.remove();
            }, 5000);
        }
    };

    /**
     * Initialize on DOM ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            PT24Landing.init();
        });
    } else {
        PT24Landing.init();
    }

    /**
     * Export to global scope
     */
    window.PT24Landing = PT24Landing;

})();

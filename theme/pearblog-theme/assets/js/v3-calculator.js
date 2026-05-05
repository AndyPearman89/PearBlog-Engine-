/**
 * Poradnik V3 Calculator - Client-side interaction
 *
 * Handles calculator form submission, result display, and lead capture integration.
 *
 * @version 3.0.0
 * @package PearBlog
 */

(function($) {
    'use strict';

    // Calculator handler class
    class PoradnikCalculator {
        constructor(element) {
            this.$element = $(element);
            this.$form = this.$element.find('.calculator-form');
            this.$result = this.$element.find('.calculator-result');
            this.$error = this.$element.find('.calculator-error');
            this.$submitBtn = this.$form.find('.calculator-submit');
            this.service = this.$element.data('service');

            this.init();
        }

        init() {
            // Bind form submission
            this.$form.on('submit', (e) => this.handleSubmit(e));

            // Bind CTA after calculation
            this.$result.on('click', '.open-lead-form', () => this.openLeadForm());

            // Track calculator view
            if (window.PoradnikTracker) {
                window.PoradnikTracker.trackEvent('calculator_view', {
                    service: this.service
                });
            }
        }

        handleSubmit(e) {
            e.preventDefault();

            // Get form data
            const formData = {
                service: this.service,
                metraz: parseFloat(this.$form.find('[name="metraz"]').val()),
                standard: this.$form.find('[name="standard"]').val(),
                lokalizacja: this.$form.find('[name="lokalizacja"]').val(),
                typ: this.$form.find('[name="typ"]').val() || ''
            };

            // Validate
            if (!formData.metraz || formData.metraz < 10 || formData.metraz > 1000) {
                this.showError('Podaj metraż między 10 a 1000 m²');
                return;
            }

            // Show loading
            this.$submitBtn.prop('disabled', true).html('🧮 Obliczam...');
            this.hideError();
            this.$result.hide();

            // Call API
            this.calculate(formData);
        }

        calculate(data) {
            $.ajax({
                url: window.pearblogV3Tracker?.apiUrl?.replace('/tracking/event', '/calculator/calculate') || '/wp-json/pearblog/v3/calculator/calculate',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                success: (response) => this.handleSuccess(response, data),
                error: (xhr) => this.handleError(xhr),
                complete: () => {
                    this.$submitBtn.prop('disabled', false).html('🧮 Oblicz koszt');
                }
            });
        }

        handleSuccess(response, inputData) {
            if (!response.success || !response.data) {
                this.showError('Nie udało się obliczyć kosztu.');
                return;
            }

            const result = response.data;

            // Update result display
            this.$result.find('.cost-min').text(this.formatCurrency(result.min_cost));
            this.$result.find('.cost-max').text(this.formatCurrency(result.max_cost));
            this.$result.find('.cost-avg-value').text(this.formatCurrency(result.avg_cost));
            this.$result.find('.cost-per-unit-value').text(this.formatCurrency(result.cost_per_unit));

            // Build breakdown table
            if (result.breakdown) {
                const $tbody = this.$result.find('.breakdown-items');
                $tbody.empty();

                Object.keys(result.breakdown).forEach(category => {
                    const cost = result.breakdown[category];
                    $tbody.append(`
                        <tr>
                            <td>${category}</td>
                            <td><strong>${this.formatCurrency(cost)} zł</strong></td>
                        </tr>
                    `);
                });
            }

            // Show result with animation
            this.$result.addClass('fade-in').show();

            // Scroll to result
            $('html, body').animate({
                scrollTop: this.$result.offset().top - 100
            }, 500);

            // Track calculator use
            if (window.PoradnikTracker) {
                window.PoradnikTracker.trackEvent('calculator_use', {
                    service: this.service,
                    result: {
                        avg_cost: result.avg_cost,
                        inputs: inputData
                    }
                });
            }
        }

        handleError(xhr) {
            let message = 'Wystąpił błąd podczas obliczania. Spróbuj ponownie.';

            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }

            this.showError(message);
        }

        showError(message) {
            this.$error.find('.error-message').text(message);
            this.$error.show();
        }

        hideError() {
            this.$error.hide();
        }

        formatCurrency(value) {
            return new Intl.NumberFormat('pl-PL', {
                style: 'decimal',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(value);
        }

        openLeadForm() {
            // Track form view
            if (window.PoradnikTracker) {
                window.PoradnikTracker.trackEvent('form_view', {
                    service: this.service,
                    source: 'calculator_result'
                });
            }

            // Open lead form modal
            if (window.PoradnikLeadForm) {
                window.PoradnikLeadForm.open(this.service);
            } else {
                // Fallback: scroll to lead form
                const $leadForm = $('[data-lead-form]').first();
                if ($leadForm.length) {
                    $('html, body').animate({
                        scrollTop: $leadForm.offset().top - 100
                    }, 500);
                }
            }
        }
    }

    // Lead Form Modal handler
    class LeadFormModal {
        constructor() {
            this.$overlay = null;
            this.$modal = null;
            this.service = null;

            this.init();
        }

        init() {
            // Bind open triggers
            $(document).on('click', '.open-lead-form', (e) => {
                e.preventDefault();
                const service = $(e.currentTarget).data('service') || 'unknown';
                this.open(service);
            });

            // Bind close on overlay click
            $(document).on('click', '.modal-overlay', (e) => {
                if ($(e.target).hasClass('modal-overlay')) {
                    this.close();
                }
            });

            // Bind close button
            $(document).on('click', '.modal-close', () => this.close());

            // Bind form submission
            $(document).on('submit', '.lead-form', (e) => this.handleSubmit(e));
        }

        open(service) {
            this.service = service;

            // Create modal HTML
            const modalHTML = `
                <div class="modal-overlay">
                    <div class="modal">
                        <button class="modal-close">&times;</button>
                        <h2>Otrzymaj bezpłatną wycenę</h2>
                        <p>Wypełnij formularz, a skontaktujemy Cię z najlepszymi firmami w Twojej okolicy.</p>

                        <form class="lead-form">
                            <div class="form-group">
                                <label>Metraż / zakres prac</label>
                                <input type="text" name="metraz" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label>Lokalizacja (miasto)</label>
                                <input type="text" name="city" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label>Standard / wymagania</label>
                                <select name="standard" class="form-control">
                                    <option value="podstawowy">Podstawowy</option>
                                    <option value="sredni" selected>Średni</option>
                                    <option value="premium">Premium</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Email lub telefon</label>
                                <input type="text" name="contact" class="form-control" required>
                            </div>

                            <button type="submit" class="btn-primary">
                                📩 Wyślij zapytanie
                            </button>

                            <div class="trust-signals">
                                <div class="trust-signal">✔ Bezpłatne</div>
                                <div class="trust-signal">✔ Szybka odpowiedź</div>
                                <div class="trust-signal">✔ Bez zobowiązań</div>
                            </div>
                        </form>
                    </div>
                </div>
            `;

            // Append to body
            this.$overlay = $(modalHTML).appendTo('body');
            this.$modal = this.$overlay.find('.modal');

            // Add fade-in animation
            setTimeout(() => this.$overlay.addClass('fade-in'), 10);

            // Track form view
            if (window.PoradnikTracker) {
                window.PoradnikTracker.trackEvent('form_view', {
                    service: this.service,
                    source: 'modal'
                });
            }
        }

        close() {
            if (this.$overlay) {
                this.$overlay.remove();
                this.$overlay = null;
                this.$modal = null;
            }
        }

        handleSubmit(e) {
            e.preventDefault();

            const $form = $(e.currentTarget);
            const $submitBtn = $form.find('button[type="submit"]');

            // Get form data
            const formData = {
                service: this.service,
                metraz: $form.find('[name="metraz"]').val(),
                city: $form.find('[name="city"]').val(),
                standard: $form.find('[name="standard"]').val(),
                contact: $form.find('[name="contact"]').val()
            };

            // Show loading
            $submitBtn.prop('disabled', true).html('📩 Wysyłam...');

            // Submit lead (use existing Poradnik V5 handler or create new endpoint)
            $.ajax({
                url: '/wp-admin/admin-ajax.php',
                method: 'POST',
                data: {
                    action: 'plv5_submit_lead',
                    ...formData
                },
                success: (response) => {
                    if (response.success) {
                        // Track form submission
                        if (window.PoradnikTracker) {
                            window.PoradnikTracker.trackEvent('form_submit', {
                                service: this.service,
                                source: 'modal'
                            });
                        }

                        // Show success message
                        this.$modal.html(`
                            <div class="text-center">
                                <div style="font-size: 64px; margin-bottom: 16px;">✅</div>
                                <h2>Dziękujemy!</h2>
                                <p>Twoje zapytanie zostało wysłane. Skontaktujemy się z Tobą wkrótce.</p>
                                <button class="btn-primary modal-close">Zamknij</button>
                            </div>
                        `);

                        // Auto-close after 3 seconds
                        setTimeout(() => this.close(), 3000);
                    } else {
                        alert('Wystąpił błąd. Spróbuj ponownie.');
                        $submitBtn.prop('disabled', false).html('📩 Wyślij zapytanie');
                    }
                },
                error: () => {
                    alert('Wystąpił błąd. Spróbuj ponownie.');
                    $submitBtn.prop('disabled', false).html('📩 Wyślij zapytanie');
                }
            });
        }
    }

    // Initialize on document ready
    $(document).ready(function() {
        // Initialize all calculators
        $('.smart-calculator').each(function() {
            new PoradnikCalculator(this);
        });

        // Initialize lead form modal
        window.PoradnikLeadForm = new LeadFormModal();

        console.log('✅ Poradnik V3 Calculator initialized');
    });

})(jQuery);

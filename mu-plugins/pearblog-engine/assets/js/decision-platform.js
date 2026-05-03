/**
 * Decision Platform Frontend JavaScript
 * Handles interactive features for Poradnik.pro
 */

(function($) {
    'use strict';

    const PearBlogDecisionPlatform = {
        init: function() {
            this.initDecisionAssistant();
            this.initLeadForms();
            this.initCalculators();
            this.initFAQ();
        },

        // Decision Assistant
        initDecisionAssistant: function() {
            $('.decision-form').on('submit', function(e) {
                e.preventDefault();

                const $form = $(this);
                const $result = $form.siblings('.decision-result');
                const $content = $result.find('.decision-content');

                const data = {
                    need: $form.find('[name="need"]').val(),
                    budget: $form.find('[name="budget"]').val() || null,
                    location: $form.find('[name="location"]').val() || null
                };

                $form.find('button[type="submit"]').prop('disabled', true).text('Analizuję...');

                $.ajax({
                    url: pearBlogDecision.apiUrl + '/decision/recommend',
                    method: 'POST',
                    data: JSON.stringify(data),
                    contentType: 'application/json',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', pearBlogDecision.nonce);
                    },
                    success: function(response) {
                        if (response.success) {
                            const result = response.data;
                            let html = '<div class="recommendation">';
                            html += '<h4>💡 Rekomendacja</h4>';
                            html += '<p>' + result.recommendation + '</p>';

                            if (result.reasoning) {
                                html += '<h5>Uzasadnienie:</h5>';
                                html += '<p>' + result.reasoning + '</p>';
                            }

                            if (result.next_steps && result.next_steps.length > 0) {
                                html += '<h5>Następne kroki:</h5>';
                                html += '<ol>';
                                result.next_steps.forEach(function(step) {
                                    html += '<li>' + step + '</li>';
                                });
                                html += '</ol>';
                            }

                            if (result.links && result.links.length > 0) {
                                html += '<h5>Przydatne linki:</h5>';
                                html += '<ul>';
                                result.links.forEach(function(link) {
                                    const icon = PearBlogDecisionPlatform.getLinkIcon(link.type);
                                    html += '<li>' + icon + ' <a href="' + link.url + '">' + link.title + '</a></li>';
                                });
                                html += '</ul>';
                            }

                            html += '</div>';
                            $content.html(html);
                            $result.slideDown();
                        }
                    },
                    error: function() {
                        alert('Wystąpił błąd. Spróbuj ponownie.');
                    },
                    complete: function() {
                        $form.find('button[type="submit"]').prop('disabled', false).text('Uzyskaj rekomendację');
                    }
                });
            });
        },

        // Lead Forms
        initLeadForms: function() {
            $('.lead-form').on('submit', function(e) {
                e.preventDefault();

                const $form = $(this);
                const data = {
                    name: $form.find('[name="name"]').val(),
                    email: $form.find('[name="email"]').val(),
                    phone: $form.find('[name="phone"]').val(),
                    city: $form.find('[name="city"]').val(),
                    message: $form.find('[name="message"]').val(),
                    category: $form.data('category')
                };

                $form.find('button[type="submit"]').prop('disabled', true).text('Wysyłanie...');

                $.ajax({
                    url: pearBlogDecision.apiUrl + '/lead/submit',
                    method: 'POST',
                    data: JSON.stringify(data),
                    contentType: 'application/json',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', pearBlogDecision.nonce);
                    },
                    success: function(response) {
                        if (response.success) {
                            $form[0].reset();
                            alert(response.message);
                        }
                    },
                    error: function(xhr) {
                        const error = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Wystąpił błąd. Spróbuj ponownie.';
                        alert(error);
                    },
                    complete: function() {
                        $form.find('button[type="submit"]').prop('disabled', false).text('Wyślij zapytanie');
                    }
                });
            });
        },

        // Calculators
        initCalculators: function() {
            $('.calculator-submit').on('click', function() {
                const $calculator = $(this).closest('.calculator-form');
                const calculatorId = $calculator.data('calculator-id');
                const $result = $calculator.siblings('.calculator-result');
                const $resultValue = $result.find('.result-value');

                const values = {};
                $calculator.find('.calculator-inputs input, .calculator-inputs select').each(function() {
                    values[$(this).attr('name')] = $(this).val();
                });

                $(this).prop('disabled', true).text('Obliczam...');

                $.ajax({
                    url: pearBlogDecision.apiUrl + '/calculator/' + calculatorId + '/calculate',
                    method: 'POST',
                    data: JSON.stringify({ values: values }),
                    contentType: 'application/json',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', pearBlogDecision.nonce);
                    },
                    success: function(response) {
                        if (response.success) {
                            $resultValue.html('<strong>' + response.formatted + '</strong>');
                            $result.slideDown();
                        }
                    },
                    error: function() {
                        alert('Wystąpił błąd w obliczeniach. Sprawdź wprowadzone dane.');
                    },
                    complete: function() {
                        $('.calculator-submit').prop('disabled', false).text('Oblicz');
                    }
                });
            });
        },

        // FAQ Accordion
        initFAQ: function() {
            $('.faq-question').on('click', function() {
                const $answer = $(this).siblings('.faq-answer');
                const $icon = $(this).find('.faq-icon');

                $answer.slideToggle();
                $icon.text($answer.is(':visible') ? '-' : '+');
            });
        },

        // Helper functions
        getLinkIcon: function(type) {
            const icons = {
                'article': '📄',
                'comparison': '⚖️',
                'ranking': '🏆',
                'calculator': '🧮',
                'expert': '👤',
                'offer': '💼'
            };
            return icons[type] || '🔗';
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        PearBlogDecisionPlatform.init();
    });

})(jQuery);

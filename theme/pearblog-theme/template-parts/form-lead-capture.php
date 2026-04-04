<?php
/**
 * Lead Capture Form Template
 *
 * Displays a lead generation form for travel consultations, trip planning, etc.
 *
 * @package PearBlog
 * @version 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get form arguments
$args = wp_parse_args($args ?? array(), array(
    'title' => __('Need Help Planning Your Trip?', 'pearblog-theme'),
    'subtitle' => __('Get personalized travel advice from our experts', 'pearblog-theme'),
    'type' => 'consultation', // consultation, guide, custom
    'button_text' => __('Get Free Consultation', 'pearblog-theme'),
    'position' => 'inline', // inline, popup, sidebar
    'post_id' => get_the_ID(),
));

$form_id = 'pearblog-lead-form-' . uniqid();
?>

<div class="pearblog-lead-capture pearblog-lead-capture--<?php echo esc_attr($args['position']); ?>" data-form-type="<?php echo esc_attr($args['type']); ?>">
    <div class="pearblog-lead-capture__inner">
        <?php if (!empty($args['title'])): ?>
            <h3 class="pearblog-lead-capture__title"><?php echo esc_html($args['title']); ?></h3>
        <?php endif; ?>

        <?php if (!empty($args['subtitle'])): ?>
            <p class="pearblog-lead-capture__subtitle"><?php echo esc_html($args['subtitle']); ?></p>
        <?php endif; ?>

        <form id="<?php echo esc_attr($form_id); ?>" class="pearblog-lead-form" method="post" action="<?php echo esc_url(rest_url('pearblog/v1/leads')); ?>">
            <div class="pearblog-lead-form__fields">
                <div class="pearblog-lead-form__field">
                    <label for="<?php echo esc_attr($form_id); ?>-name" class="pearblog-lead-form__label">
                        <?php esc_html_e('Your Name', 'pearblog-theme'); ?> <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        id="<?php echo esc_attr($form_id); ?>-name"
                        name="name"
                        class="pearblog-lead-form__input"
                        required
                        placeholder="<?php esc_attr_e('Jan Kowalski', 'pearblog-theme'); ?>"
                    />
                </div>

                <div class="pearblog-lead-form__field">
                    <label for="<?php echo esc_attr($form_id); ?>-email" class="pearblog-lead-form__label">
                        <?php esc_html_e('Email Address', 'pearblog-theme'); ?> <span class="required">*</span>
                    </label>
                    <input
                        type="email"
                        id="<?php echo esc_attr($form_id); ?>-email"
                        name="email"
                        class="pearblog-lead-form__input"
                        required
                        placeholder="<?php esc_attr_e('jan@example.com', 'pearblog-theme'); ?>"
                    />
                </div>

                <div class="pearblog-lead-form__field">
                    <label for="<?php echo esc_attr($form_id); ?>-phone" class="pearblog-lead-form__label">
                        <?php esc_html_e('Phone Number', 'pearblog-theme'); ?>
                    </label>
                    <input
                        type="tel"
                        id="<?php echo esc_attr($form_id); ?>-phone"
                        name="phone"
                        class="pearblog-lead-form__input"
                        placeholder="<?php esc_attr_e('+48 123 456 789', 'pearblog-theme'); ?>"
                    />
                </div>

                <div class="pearblog-lead-form__field pearblog-lead-form__field--full">
                    <label for="<?php echo esc_attr($form_id); ?>-message" class="pearblog-lead-form__label">
                        <?php esc_html_e('Your Question/Request', 'pearblog-theme'); ?> <span class="required">*</span>
                    </label>
                    <textarea
                        id="<?php echo esc_attr($form_id); ?>-message"
                        name="message"
                        class="pearblog-lead-form__textarea"
                        rows="4"
                        required
                        placeholder="<?php esc_attr_e('Tell us about your travel plans...', 'pearblog-theme'); ?>"
                    ></textarea>
                </div>

                <?php if ($args['type'] === 'consultation'): ?>
                <div class="pearblog-lead-form__field pearblog-lead-form__field--full">
                    <label for="<?php echo esc_attr($form_id); ?>-dates" class="pearblog-lead-form__label">
                        <?php esc_html_e('Preferred Travel Dates', 'pearblog-theme'); ?>
                    </label>
                    <input
                        type="text"
                        id="<?php echo esc_attr($form_id); ?>-dates"
                        name="travel_dates"
                        class="pearblog-lead-form__input"
                        placeholder="<?php esc_attr_e('e.g., July 2026', 'pearblog-theme'); ?>"
                    />
                </div>
                <?php endif; ?>

                <input type="hidden" name="post_id" value="<?php echo esc_attr($args['post_id']); ?>" />
                <input type="hidden" name="lead_type" value="<?php echo esc_attr($args['type']); ?>" />
                <input type="hidden" name="form_position" value="<?php echo esc_attr($args['position']); ?>" />
                <?php wp_nonce_field('pearblog_lead_submit', 'pearblog_lead_nonce'); ?>
            </div>

            <div class="pearblog-lead-form__submit">
                <button type="submit" class="pearblog-lead-form__button">
                    <?php echo esc_html($args['button_text']); ?>
                </button>
            </div>

            <div class="pearblog-lead-form__message" style="display: none;"></div>

            <p class="pearblog-lead-form__privacy">
                <?php
                printf(
                    /* translators: %s: privacy policy link */
                    esc_html__('By submitting this form, you agree to our %s. We respect your privacy.', 'pearblog-theme'),
                    '<a href="' . esc_url(get_privacy_policy_url()) . '" target="_blank">' . esc_html__('Privacy Policy', 'pearblog-theme') . '</a>'
                );
                ?>
            </p>
        </form>
    </div>
</div>

<script>
(function() {
    const form = document.getElementById('<?php echo esc_js($form_id); ?>');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const submitButton = form.querySelector('.pearblog-lead-form__button');
        const messageDiv = form.querySelector('.pearblog-lead-form__message');
        const originalButtonText = submitButton.textContent;

        // Disable submit button
        submitButton.disabled = true;
        submitButton.textContent = '<?php echo esc_js(__('Sending...', 'pearblog-theme')); ?>';

        // Collect form data
        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        // Send to REST API
        fetch('<?php echo esc_url(rest_url('pearblog/v1/leads')); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                messageDiv.className = 'pearblog-lead-form__message pearblog-lead-form__message--success';
                messageDiv.textContent = result.message || '<?php echo esc_js(__('Thank you! We\'ll contact you soon.', 'pearblog-theme')); ?>';
                messageDiv.style.display = 'block';
                form.reset();

                // Track conversion
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'generate_lead', {
                        'event_category': 'Lead Generation',
                        'event_label': data.lead_type,
                        'value': 1
                    });
                }
            } else {
                throw new Error(result.message || 'Submission failed');
            }
        })
        .catch(error => {
            messageDiv.className = 'pearblog-lead-form__message pearblog-lead-form__message--error';
            messageDiv.textContent = error.message || '<?php echo esc_js(__('Something went wrong. Please try again.', 'pearblog-theme')); ?>';
            messageDiv.style.display = 'block';
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
        });
    });
})();
</script>

<style>
.pearblog-lead-capture {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 32px;
    margin: 32px 0;
    color: #ffffff;
}

.pearblog-lead-capture--sidebar {
    position: sticky;
    top: 20px;
}

.pearblog-lead-capture__title {
    font-size: 24px;
    font-weight: 700;
    margin: 0 0 8px;
    color: #ffffff;
}

.pearblog-lead-capture__subtitle {
    font-size: 16px;
    opacity: 0.9;
    margin: 0 0 24px;
}

.pearblog-lead-form__fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}

.pearblog-lead-form__field--full {
    grid-column: 1 / -1;
}

.pearblog-lead-form__label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 6px;
    color: #ffffff;
}

.pearblog-lead-form__label .required {
    color: #fbbf24;
}

.pearblog-lead-form__input,
.pearblog-lead-form__textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 6px;
    font-size: 15px;
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
    transition: all 0.3s ease;
}

.pearblog-lead-form__input::placeholder,
.pearblog-lead-form__textarea::placeholder {
    color: rgba(255, 255, 255, 0.6);
}

.pearblog-lead-form__input:focus,
.pearblog-lead-form__textarea:focus {
    outline: none;
    border-color: #ffffff;
    background: rgba(255, 255, 255, 0.15);
}

.pearblog-lead-form__button {
    width: 100%;
    padding: 14px 32px;
    background: #fbbf24;
    color: #1f2937;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
}

.pearblog-lead-form__button:hover:not(:disabled) {
    background: #f59e0b;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.pearblog-lead-form__button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.pearblog-lead-form__message {
    padding: 12px 16px;
    border-radius: 6px;
    margin-top: 16px;
    font-size: 14px;
}

.pearblog-lead-form__message--success {
    background: rgba(16, 185, 129, 0.2);
    border: 2px solid #10b981;
}

.pearblog-lead-form__message--error {
    background: rgba(239, 68, 68, 0.2);
    border: 2px solid #ef4444;
}

.pearblog-lead-form__privacy {
    font-size: 12px;
    opacity: 0.8;
    margin-top: 16px;
    line-height: 1.5;
}

.pearblog-lead-form__privacy a {
    color: #fbbf24;
    text-decoration: underline;
}

@media (max-width: 640px) {
    .pearblog-lead-form__fields {
        grid-template-columns: 1fr;
    }

    .pearblog-lead-capture {
        padding: 24px 20px;
    }
}
</style>

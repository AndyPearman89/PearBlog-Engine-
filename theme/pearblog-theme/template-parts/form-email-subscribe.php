<?php
/**
 * Email Subscription Form Template
 *
 * Displays an email subscription form for newsletter signups and content upgrades
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
    'title' => __('Join Our Travel Community', 'pearblog-theme'),
    'subtitle' => __('Get exclusive travel tips, guides, and destination inspiration delivered weekly', 'pearblog-theme'),
    'type' => 'newsletter', // newsletter, content-upgrade, inline
    'button_text' => __('Subscribe Now', 'pearblog-theme'),
    'position' => 'inline', // inline, popup, end-of-content
    'post_id' => get_the_ID(),
    'content_upgrade_id' => '', // ID of content upgrade offer (e.g., PDF guide)
    'show_name' => false, // Whether to collect name field
));

$form_id = 'pearblog-email-form-' . uniqid();
$is_content_upgrade = !empty($args['content_upgrade_id']);
?>

<div class="pearblog-email-subscribe pearblog-email-subscribe--<?php echo esc_attr($args['position']); ?>" data-form-type="<?php echo esc_attr($args['type']); ?>">
    <div class="pearblog-email-subscribe__inner">
        <?php if (!empty($args['title'])): ?>
            <h3 class="pearblog-email-subscribe__title"><?php echo esc_html($args['title']); ?></h3>
        <?php endif; ?>

        <?php if (!empty($args['subtitle'])): ?>
            <p class="pearblog-email-subscribe__subtitle"><?php echo esc_html($args['subtitle']); ?></p>
        <?php endif; ?>

        <form id="<?php echo esc_attr($form_id); ?>" class="pearblog-email-form" method="post">
            <div class="pearblog-email-form__fields">
                <?php if ($args['show_name']): ?>
                <div class="pearblog-email-form__field">
                    <label for="<?php echo esc_attr($form_id); ?>-name" class="pearblog-email-form__label sr-only">
                        <?php esc_html_e('Your Name', 'pearblog-theme'); ?>
                    </label>
                    <input
                        type="text"
                        id="<?php echo esc_attr($form_id); ?>-name"
                        name="name"
                        class="pearblog-email-form__input"
                        placeholder="<?php esc_attr_e('Your Name', 'pearblog-theme'); ?>"
                    />
                </div>
                <?php endif; ?>

                <div class="pearblog-email-form__field pearblog-email-form__field--email">
                    <label for="<?php echo esc_attr($form_id); ?>-email" class="pearblog-email-form__label sr-only">
                        <?php esc_html_e('Email Address', 'pearblog-theme'); ?>
                    </label>
                    <input
                        type="email"
                        id="<?php echo esc_attr($form_id); ?>-email"
                        name="email"
                        class="pearblog-email-form__input"
                        required
                        placeholder="<?php esc_attr_e('your@email.com', 'pearblog-theme'); ?>"
                    />
                </div>

                <input type="hidden" name="post_id" value="<?php echo esc_attr($args['post_id']); ?>" />
                <input type="hidden" name="subscription_type" value="<?php echo esc_attr($args['type']); ?>" />
                <input type="hidden" name="form_position" value="<?php echo esc_attr($args['position']); ?>" />
                <?php if ($is_content_upgrade): ?>
                <input type="hidden" name="content_upgrade_id" value="<?php echo esc_attr($args['content_upgrade_id']); ?>" />
                <?php endif; ?>
                <?php wp_nonce_field('pearblog_email_subscribe', 'pearblog_email_nonce'); ?>
            </div>

            <div class="pearblog-email-form__submit">
                <button type="submit" class="pearblog-email-form__button">
                    <?php echo esc_html($args['button_text']); ?>
                </button>
            </div>

            <div class="pearblog-email-form__message" style="display: none;"></div>

            <p class="pearblog-email-form__privacy">
                <?php
                printf(
                    /* translators: %s: privacy policy link */
                    esc_html__('We respect your privacy. Unsubscribe anytime. View our %s.', 'pearblog-theme'),
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

        const submitButton = form.querySelector('.pearblog-email-form__button');
        const messageDiv = form.querySelector('.pearblog-email-form__message');
        const originalButtonText = submitButton.textContent;

        // Disable submit button
        submitButton.disabled = true;
        submitButton.textContent = '<?php echo esc_js(__('Subscribing...', 'pearblog-theme')); ?>';

        // Collect form data
        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        // Send to REST API
        fetch('<?php echo esc_url(rest_url('pearblog/v1/subscribe')); ?>', {
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
                messageDiv.className = 'pearblog-email-form__message pearblog-email-form__message--success';
                messageDiv.textContent = result.message || '<?php echo esc_js(__('Success! Check your email to confirm.', 'pearblog-theme')); ?>';
                messageDiv.style.display = 'block';
                form.reset();

                // Track conversion
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'newsletter_signup', {
                        'event_category': 'Email Marketing',
                        'event_label': data.subscription_type,
                        'value': 1
                    });
                }

                // If content upgrade, show download link
                <?php if ($is_content_upgrade): ?>
                if (result.download_url) {
                    messageDiv.innerHTML += '<br><a href="' + result.download_url + '" class="pearblog-download-link"><?php echo esc_js(__('Download Your Free Guide →', 'pearblog-theme')); ?></a>';
                }
                <?php endif; ?>
            } else {
                throw new Error(result.message || 'Subscription failed');
            }
        })
        .catch(error => {
            messageDiv.className = 'pearblog-email-form__message pearblog-email-form__message--error';
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
.pearblog-email-subscribe {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border-radius: 12px;
    padding: 32px;
    margin: 32px 0;
    color: #ffffff;
}

.pearblog-email-subscribe--end-of-content {
    margin-top: 48px;
    margin-bottom: 0;
}

.pearblog-email-subscribe--popup {
    position: fixed;
    bottom: 20px;
    right: 20px;
    max-width: 400px;
    z-index: 9999;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.pearblog-email-subscribe__title {
    font-size: 24px;
    font-weight: 700;
    margin: 0 0 8px;
    color: #ffffff;
}

.pearblog-email-subscribe__subtitle {
    font-size: 15px;
    opacity: 0.95;
    margin: 0 0 24px;
    line-height: 1.5;
}

.pearblog-email-form__fields {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.pearblog-email-form__field {
    flex: 1;
    min-width: 200px;
}

.pearblog-email-form__field--email {
    flex: 2;
}

.pearblog-email-form__label.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.pearblog-email-form__input {
    width: 100%;
    padding: 14px 18px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 8px;
    font-size: 15px;
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
    transition: all 0.3s ease;
}

.pearblog-email-form__input::placeholder {
    color: rgba(255, 255, 255, 0.7);
}

.pearblog-email-form__input:focus {
    outline: none;
    border-color: #ffffff;
    background: rgba(255, 255, 255, 0.2);
}

.pearblog-email-form__button {
    width: 100%;
    padding: 14px 32px;
    background: #10b981;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
}

.pearblog-email-form__button:hover:not(:disabled) {
    background: #059669;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
}

.pearblog-email-form__button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.pearblog-email-form__message {
    padding: 12px 16px;
    border-radius: 8px;
    margin-top: 16px;
    font-size: 14px;
    font-weight: 500;
}

.pearblog-email-form__message--success {
    background: rgba(16, 185, 129, 0.2);
    border: 2px solid #10b981;
}

.pearblog-email-form__message--error {
    background: rgba(239, 68, 68, 0.2);
    border: 2px solid #ef4444;
}

.pearblog-email-form__privacy {
    font-size: 12px;
    opacity: 0.85;
    margin-top: 16px;
    line-height: 1.5;
}

.pearblog-email-form__privacy a {
    color: #fbbf24;
    text-decoration: underline;
}

.pearblog-download-link {
    display: inline-block;
    margin-top: 8px;
    padding: 8px 16px;
    background: #10b981;
    color: #ffffff;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 600;
    transition: background 0.3s ease;
}

.pearblog-download-link:hover {
    background: #059669;
}

@media (max-width: 640px) {
    .pearblog-email-form__fields {
        flex-direction: column;
    }

    .pearblog-email-form__field,
    .pearblog-email-form__field--email {
        min-width: 100%;
    }

    .pearblog-email-subscribe {
        padding: 24px 20px;
    }

    .pearblog-email-subscribe--popup {
        bottom: 10px;
        right: 10px;
        left: 10px;
        max-width: none;
    }
}
</style>

<?php
/**
 * Template Part: CTA Block
 *
 * Dynamic CTA (affiliate / lead / click)
 *
 * @package PearBlog
 * @version 2.0.0
 */

$type = $args['type'] ?? 'default'; // default, affiliate, lead, click
$position = $args['position'] ?? 'content'; // header, content, footer, inline
$title = $args['title'] ?? get_option('pearblog_cta_title', __('Ready to Get Started?', 'pearblog-theme'));
$subtitle = $args['subtitle'] ?? get_option('pearblog_cta_subtitle', '');
$button_text = $args['button_text'] ?? get_option('pearblog_cta_button_text', __('Learn More', 'pearblog-theme'));
$button_url = $args['button_url'] ?? get_option('pearblog_cta_button_url', home_url('/'));
$button_secondary = $args['button_secondary'] ?? false;
$button_secondary_text = $args['button_secondary_text'] ?? '';
$button_secondary_url = $args['button_secondary_url'] ?? '';

// Style variations
$style = $args['style'] ?? 'gradient'; // gradient, solid, outline, minimal

$cta_classes = array(
    'pb-cta',
    'pb-cta-' . esc_attr($type),
    'pb-cta-position-' . esc_attr($position),
    'pb-cta-style-' . esc_attr($style),
);
?>

<div class="<?php echo esc_attr(implode(' ', $cta_classes)); ?>">
    <div class="pb-cta-container">
        <div class="pb-cta-content">
            <?php if (!empty($title)) : ?>
                <h2 class="pb-cta-title"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>

            <?php if (!empty($subtitle)) : ?>
                <p class="pb-cta-subtitle"><?php echo esc_html($subtitle); ?></p>
            <?php endif; ?>

            <div class="pb-cta-buttons">
                <a href="<?php echo esc_url($button_url); ?>"
                   class="pb-cta-button pb-cta-button-primary"
                   <?php if ($type === 'affiliate') : ?>
                       rel="nofollow sponsored"
                   <?php endif; ?>
                   data-cta-type="<?php echo esc_attr($type); ?>"
                   data-cta-position="<?php echo esc_attr($position); ?>">
                    <?php echo esc_html($button_text); ?>
                </a>

                <?php if ($button_secondary && !empty($button_secondary_text)) : ?>
                    <a href="<?php echo esc_url($button_secondary_url); ?>"
                       class="pb-cta-button pb-cta-button-secondary">
                        <?php echo esc_html($button_secondary_text); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($type === 'lead') : ?>
            <!-- Lead capture form -->
            <div class="pb-cta-form">
                <form class="pb-lead-form" method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
                    <input type="hidden" name="action" value="pearblog_submit_lead">
                    <?php wp_nonce_field('pearblog_lead_nonce', 'lead_nonce'); ?>

                    <div class="pb-form-group">
                        <input type="email"
                               name="lead_email"
                               class="pb-form-input"
                               placeholder="<?php esc_attr_e('Enter your email', 'pearblog-theme'); ?>"
                               required>
                    </div>

                    <button type="submit" class="pb-cta-button pb-cta-button-primary">
                        <?php echo esc_html($button_text); ?>
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($type === 'affiliate') : ?>
    <!-- Tracking pixel for affiliate conversions -->
    <script>
    document.querySelectorAll('[data-cta-type="affiliate"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            // Track affiliate click
            if (typeof gtag !== 'undefined') {
                gtag('event', 'affiliate_click', {
                    'affiliate_url': this.href,
                    'position': this.dataset.ctaPosition
                });
            }
        });
    });
    </script>
<?php endif; ?>

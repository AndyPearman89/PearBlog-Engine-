<?php
/**
 * Template Part: Affiliate Box
 *
 * Displays affiliate offers (Booking, Airbnb) with fallback CTA
 * Optimized for SEO and conversions
 *
 * @package PearBlog
 * @version 2.0.0
 */

$position = $args['position'] ?? 'top'; // top, middle, bottom
$location = $args['location'] ?? '';
$offers = $args['offers'] ?? array();
$fallback_enabled = $args['fallback_enabled'] ?? true;

// If no offers provided, try to get them dynamically
if (empty($offers) && !empty($location)) {
    $offers = pearblog_get_affiliate_offers($location);
}

// Determine which affiliate to show based on priority
$booking_offers = array_filter($offers, function($offer) {
    return isset($offer['source']) && $offer['source'] === 'booking';
});

$airbnb_offers = array_filter($offers, function($offer) {
    return isset($offer['source']) && $offer['source'] === 'airbnb';
});

$active_offers = !empty($booking_offers) ? $booking_offers : (!empty($airbnb_offers) ? $airbnb_offers : array());
$affiliate_source = !empty($booking_offers) ? 'booking' : (!empty($airbnb_offers) ? 'airbnb' : 'fallback');

$box_classes = array(
    'pb-affiliate-box',
    'pb-affiliate-position-' . esc_attr($position),
    'pb-affiliate-source-' . esc_attr($affiliate_source),
);
?>

<div class="<?php echo esc_attr(implode(' ', $box_classes)); ?>">
    <div class="pb-affiliate-container">
        <?php if (!empty($active_offers)) : ?>
            <!-- Affiliate Offers Section -->
            <div class="pb-affiliate-header">
                <h3 class="pb-affiliate-title">
                    <?php
                    if ($affiliate_source === 'booking') {
                        echo esc_html__('Najlepsze oferty noclegowe', 'pearblog-theme');
                    } elseif ($affiliate_source === 'airbnb') {
                        echo esc_html__('Sprawdź dostępne noclegi', 'pearblog-theme');
                    }
                    ?>
                </h3>
                <?php if (!empty($location)) : ?>
                    <p class="pb-affiliate-location">
                        <svg class="pb-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <?php echo esc_html($location); ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="pb-affiliate-offers">
                <?php
                $offer_count = 0;
                $max_offers = 3;
                foreach ($active_offers as $offer) :
                    if ($offer_count >= $max_offers) break;
                    $offer_count++;

                    $name = $offer['name'] ?? '';
                    $price = $offer['price'] ?? '';
                    $rating = $offer['rating'] ?? 0;
                    $url = $offer['url'] ?? '';
                    $image = $offer['image'] ?? '';
                ?>
                    <div class="pb-affiliate-offer">
                        <?php if (!empty($image)) : ?>
                            <div class="pb-offer-image">
                                <img src="<?php echo esc_url($image); ?>"
                                     alt="<?php echo esc_attr($name); ?>"
                                     loading="lazy">
                            </div>
                        <?php endif; ?>

                        <div class="pb-offer-content">
                            <h4 class="pb-offer-name"><?php echo esc_html($name); ?></h4>

                            <?php if (!empty($rating) && $rating > 0) : ?>
                                <div class="pb-offer-rating">
                                    <div class="pb-stars" aria-label="<?php printf(esc_attr__('Ocena: %s/10', 'pearblog-theme'), $rating); ?>">
                                        <?php
                                        $stars_filled = round($rating / 2); // Convert 10-point to 5-star
                                        for ($i = 1; $i <= 5; $i++) :
                                            if ($i <= $stars_filled) :
                                        ?>
                                            <svg class="pb-star pb-star-filled" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                            </svg>
                                        <?php else : ?>
                                            <svg class="pb-star" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                            </svg>
                                        <?php
                                            endif;
                                        endfor;
                                        ?>
                                    </div>
                                    <span class="pb-rating-value"><?php echo esc_html($rating); ?>/10</span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($price)) : ?>
                                <div class="pb-offer-price">
                                    <span class="pb-price-label"><?php esc_html_e('Od', 'pearblog-theme'); ?></span>
                                    <span class="pb-price-value"><?php echo esc_html($price); ?></span>
                                    <span class="pb-price-period"><?php esc_html_e('/ noc', 'pearblog-theme'); ?></span>
                                </div>
                            <?php endif; ?>

                            <a href="<?php echo esc_url($url); ?>"
                               class="pb-offer-cta"
                               rel="nofollow sponsored"
                               target="_blank"
                               data-affiliate-source="<?php echo esc_attr($affiliate_source); ?>"
                               data-affiliate-position="<?php echo esc_attr($position); ?>">
                                <?php esc_html_e('Sprawdź dostępność', 'pearblog-theme'); ?>
                                <svg class="pb-icon-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M5 12h14M12 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="pb-affiliate-footer">
                <p class="pb-affiliate-disclaimer">
                    <?php esc_html_e('* Ceny mogą się różnić. Sprawdź aktualne oferty na stronie partnera.', 'pearblog-theme'); ?>
                </p>
            </div>

        <?php elseif ($fallback_enabled) : ?>
            <!-- Fallback CTA when no offers available -->
            <div class="pb-affiliate-fallback">
                <div class="pb-fallback-content">
                    <h3 class="pb-fallback-title">
                        <?php esc_html_e('Szukasz noclegu?', 'pearblog-theme'); ?>
                    </h3>
                    <p class="pb-fallback-text">
                        <?php esc_html_e('Sprawdź najlepsze oferty noclegowe w tej okolicy.', 'pearblog-theme'); ?>
                    </p>
                    <div class="pb-fallback-buttons">
                        <a href="https://www.booking.com/?aid=YOUR_BOOKING_ID"
                           class="pb-fallback-btn pb-fallback-booking"
                           rel="nofollow sponsored"
                           target="_blank"
                           data-affiliate-source="booking"
                           data-affiliate-type="fallback"
                           data-affiliate-position="<?php echo esc_attr($position); ?>">
                            <svg class="pb-btn-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            </svg>
                            Booking.com
                        </a>
                        <a href="https://www.airbnb.com/?affiliate_id=YOUR_AIRBNB_ID"
                           class="pb-fallback-btn pb-fallback-airbnb"
                           rel="nofollow sponsored"
                           target="_blank"
                           data-affiliate-source="airbnb"
                           data-affiliate-type="fallback"
                           data-affiliate-position="<?php echo esc_attr($position); ?>">
                            <svg class="pb-btn-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                            </svg>
                            Airbnb
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Affiliate Click Tracking -->
<script>
(function() {
    document.querySelectorAll('[data-affiliate-source]').forEach(function(link) {
        link.addEventListener('click', function() {
            // Track affiliate click for analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', 'affiliate_click', {
                    'event_category': 'monetization',
                    'event_label': this.dataset.affiliateSource,
                    'affiliate_position': this.dataset.affiliatePosition,
                    'affiliate_type': this.dataset.affiliateType || 'offer'
                });
            }

            // Optional: Send to custom tracking endpoint
            if (typeof fetch !== 'undefined') {
                fetch('<?php echo esc_url(rest_url('pearblog/v1/track-affiliate')); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        source: this.dataset.affiliateSource,
                        position: this.dataset.affiliatePosition,
                        post_id: <?php echo get_the_ID(); ?>,
                        url: this.href
                    })
                }).catch(function() {
                    // Silent fail
                });
            }
        });
    });
})();
</script>

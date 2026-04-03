<?php
/**
 * Example: How to add affiliate offers to your PearBlog posts
 *
 * This file demonstrates various ways to configure and use
 * the affiliate integration system.
 *
 * @package PearBlog
 */

// ============================================
// EXAMPLE 1: Add Manual Offers for a Location
// ============================================

// Add this to your theme's functions.php or a custom plugin

function my_add_babia_gora_offers() {
    // Booking.com offers
    pearblog_add_manual_offer('Babia Góra', array(
        'source' => 'booking',
        'name' => 'Schronisko PTTK Markowe Szczawiny',
        'price' => '120 zł',
        'rating' => 8.5,
        'url' => 'https://www.booking.com/hotel/pl/markowe-szczawiny.html?aid=YOUR_AFFILIATE_ID',
        'image' => 'https://example.com/images/markowe-szczawiny.jpg',
    ));

    pearblog_add_manual_offer('Babia Góra', array(
        'source' => 'booking',
        'name' => 'Pensjonat Beskid',
        'price' => '180 zł',
        'rating' => 9.2,
        'url' => 'https://www.booking.com/hotel/pl/pensjonat-beskid.html?aid=YOUR_AFFILIATE_ID',
        'image' => 'https://example.com/images/pensjonat-beskid.jpg',
    ));

    // Airbnb offers (as fallback if Booking not available)
    pearblog_add_manual_offer('Babia Góra', array(
        'source' => 'airbnb',
        'name' => 'Góralska chata z widokiem',
        'price' => '150 zł',
        'rating' => 9.0,
        'url' => 'https://www.airbnb.com/rooms/12345?affiliate_id=YOUR_ID',
        'image' => 'https://example.com/images/goralska-chata.jpg',
    ));
}
add_action('init', 'my_add_babia_gora_offers');

// ============================================
// EXAMPLE 2: Set Location for a Post
// ============================================

function my_set_post_location($post_id) {
    // Automatically set location based on post title or category
    $post = get_post($post_id);

    if (stripos($post->post_title, 'Babia Góra') !== false) {
        update_post_meta($post_id, 'pearblog_location', 'Babia Góra');
    } elseif (stripos($post->post_title, 'Zakopane') !== false) {
        update_post_meta($post_id, 'pearblog_location', 'Zakopane');
    } elseif (stripos($post->post_title, 'Kraków') !== false) {
        update_post_meta($post_id, 'pearblog_location', 'Kraków');
    }
}
add_action('save_post', 'my_set_post_location');

// ============================================
// EXAMPLE 3: Display Affiliate Box Manually
// ============================================

// In any template file:
/*
<?php
pearblog_affiliate_box(array(
    'position' => 'top',
    'location' => 'Babia Góra',
    'fallback_enabled' => true,
));
?>
*/

// ============================================
// EXAMPLE 4: Get Affiliate Statistics
// ============================================

function my_display_affiliate_stats() {
    // Get global stats
    $global_stats = pearblog_get_affiliate_stats();

    echo '<div class="affiliate-stats">';
    echo '<h3>Affiliate Performance</h3>';
    echo '<p>Booking.com clicks: ' . $global_stats['booking_clicks'] . '</p>';
    echo '<p>Airbnb clicks: ' . $global_stats['airbnb_clicks'] . '</p>';
    echo '<p>Total clicks: ' . $global_stats['total_clicks'] . '</p>';
    echo '</div>';

    // Get stats for specific post
    $post_stats = pearblog_get_affiliate_stats(123); // Replace 123 with post ID
    // Same structure as global stats
}

// ============================================
// EXAMPLE 5: Configure API Credentials
// ============================================

function my_configure_affiliate_apis() {
    // Booking.com API (if you have API access)
    update_option('pearblog_booking_api_key', 'your-booking-api-key');
    update_option('pearblog_booking_affiliate_id', 'your-affiliate-id');

    // Airbnb (if you have API access)
    update_option('pearblog_airbnb_api_key', 'your-airbnb-api-key');
    update_option('pearblog_airbnb_affiliate_id', 'your-affiliate-id');
}
// Run once: my_configure_affiliate_apis();

// ============================================
// EXAMPLE 6: Customize Fallback Links
// ============================================

// Edit template-parts/block-affiliate.php lines 162-176 with your affiliate IDs:
/*
<a href="https://www.booking.com/?aid=YOUR_BOOKING_AFFILIATE_ID"
   class="pb-fallback-btn pb-fallback-booking"
   rel="nofollow sponsored"
   target="_blank">
    Booking.com
</a>

<a href="https://www.airbnb.com/?affiliate_id=YOUR_AIRBNB_AFFILIATE_ID"
   class="pb-fallback-btn pb-fallback-airbnb"
   rel="nofollow sponsored"
   target="_blank">
    Airbnb
</a>
*/

// ============================================
// EXAMPLE 7: Fetch Offers via REST API
// ============================================

/*
JavaScript example:

fetch('/wp-json/pearblog/v1/affiliate/offers?location=Babia Góra')
    .then(response => response.json())
    .then(data => {
        console.log('Offers:', data.offers);
        console.log('Count:', data.count);
    });
*/

// ============================================
// EXAMPLE 8: Add Multiple Locations
// ============================================

function my_add_all_location_offers() {
    $locations = array(
        'Babia Góra' => array(
            array(
                'source' => 'booking',
                'name' => 'Schronisko PTTK Markowe Szczawiny',
                'price' => '120 zł',
                'rating' => 8.5,
                'url' => 'https://www.booking.com/...',
                'image' => 'https://...',
            ),
        ),
        'Zakopane' => array(
            array(
                'source' => 'booking',
                'name' => 'Hotel Mercure',
                'price' => '350 zł',
                'rating' => 9.0,
                'url' => 'https://www.booking.com/...',
                'image' => 'https://...',
            ),
        ),
    );

    foreach ($locations as $location => $offers) {
        foreach ($offers as $offer) {
            pearblog_add_manual_offer($location, $offer);
        }
    }
}
add_action('init', 'my_add_all_location_offers');

// ============================================
// EXAMPLE 9: Clear Offer Cache
// ============================================

function my_clear_offer_cache($location) {
    $cache_key = 'pearblog_offers_' . md5($location);
    delete_transient($cache_key);
}

// Clear cache for specific location
// my_clear_offer_cache('Babia Góra');

// ============================================
// EXAMPLE 10: Custom Affiliate Click Handler
// ============================================

function my_custom_affiliate_handler($request) {
    $source = $request->get_param('source');
    $post_id = $request->get_param('post_id');

    // Custom logic here
    // E.g., send email notification, update custom analytics, etc.

    do_action('pearblog_affiliate_click', $source, $post_id);

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Custom handler executed',
    ), 200);
}
add_filter('rest_request_after_callbacks', 'my_custom_affiliate_handler');

// ============================================
// TIPS FOR MAXIMUM CONVERSIONS
// ============================================

/*
1. Always add high-quality images for offers
2. Keep prices updated (or remove price if dynamic)
3. Use accurate ratings from actual platforms
4. Test fallback links regularly
5. Monitor click-through rates and optimize placement
6. Add offers for all your top locations
7. Consider seasonal offers (winter/summer accommodations)
8. Use clear, action-oriented CTAs
9. Ensure mobile experience is flawless
10. Track and analyze performance monthly
*/

// ============================================
// REVENUE OPTIMIZATION CHECKLIST
// ============================================

/*
✓ Set up Booking.com affiliate account
✓ Set up Airbnb affiliate account
✓ Add offers for top 10 locations in your content
✓ Set location meta for all existing posts
✓ Configure fallback affiliate IDs
✓ Test affiliate boxes on mobile and desktop
✓ Monitor click rates weekly
✓ Update offers quarterly
✓ A/B test different CTAs
✓ Combine with AdSense for maximum revenue
*/

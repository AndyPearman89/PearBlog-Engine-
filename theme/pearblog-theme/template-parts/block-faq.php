<?php
/**
 * Template Part: FAQ Block
 *
 * FAQ section with accordion
 *
 * @package PearBlog
 */

$faq_items = $args['faq_items'] ?? array();
$title = $args['title'] ?? __('Frequently Asked Questions', 'pearblog-theme');

if (empty($faq_items)) {
    // Try to get from post meta
    $post_id = $args['post_id'] ?? get_the_ID();
    $faq_items = get_post_meta($post_id, 'pearblog_faq', true);
}

if (empty($faq_items)) {
    return;
}
?>

<section class="pb-faq">
    <div class="pb-container">
        <h2 class="pb-faq-title"><?php echo esc_html($title); ?></h2>

        <div class="pb-faq-list">
            <?php foreach ($faq_items as $index => $item) : ?>
                <?php if (empty($item['question']) || empty($item['answer'])) continue; ?>

                <div class="pb-faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <div class="pb-faq-question" role="button" aria-expanded="false" tabindex="0">
                        <span itemprop="name"><?php echo esc_html($item['question']); ?></span>
                        <span class="pb-faq-icon" aria-hidden="true">▼</span>
                    </div>
                    <div class="pb-faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <div itemprop="text">
                            <?php echo wp_kses_post(wpautop($item['answer'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Schema.org FAQPage structured data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "FAQPage",
        "mainEntity": [
            <?php foreach ($faq_items as $index => $item) : ?>
                <?php if (empty($item['question']) || empty($item['answer'])) continue; ?>
                {
                    "@type": "Question",
                    "name": <?php echo wp_json_encode($item['question']); ?>,
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": <?php echo wp_json_encode(wp_strip_all_tags($item['answer'])); ?>
                    }
                }<?php echo ($index < count($faq_items) - 1) ? ',' : ''; ?>
            <?php endforeach; ?>
        ]
    }
    </script>
</section>

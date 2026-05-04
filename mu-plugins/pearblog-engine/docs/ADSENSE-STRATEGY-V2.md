# 💰 AdSense Strategy V2 — Funnel-Aware Monetization

> Intelligent AdSense placement that respects the user journey and maximizes revenue while protecting conversions.

---

## 🎯 Philosophy

**AdSense is supplementary monetization**, not the primary revenue source for Poradnik.pro.

### Revenue Sources (by priority):

1. **Leads** — Expert connections, contact forms
2. **Rankings** — Product/service comparisons
3. **AdSense** — Informational traffic monetization

**Key Principle**: AdSense monetizes **attention**, not **decisions**.

---

## 🧠 How It Works

The system automatically analyzes each article during content generation to determine its funnel stage:

### TOFU (Top of Funnel) — Awareness
- **Content Type**: Guides, tutorials, FAQs, educational articles
- **User Intent**: Information seeking
- **Keywords**: "co to jest", "jak działa", "przewodnik", "what is", "how to"
- **AdSense Strategy**: ✅ **Full placement** (2 ads per article)

### MOFU (Middle of Funnel) — Consideration
- **Content Type**: Comparisons, reviews, alternatives
- **User Intent**: Researching options
- **Keywords**: "porównanie", "vs", "recenzja", "comparison", "alternatives"
- **AdSense Strategy**: ⚠️ **Limited placement** (1 ad only)

### BOFU (Bottom of Funnel) — Decision
- **Content Type**: Rankings, calculators, expert profiles, contact forms
- **User Intent**: Ready to convert
- **Keywords**: "ranking", "najlepsze", "kalkulator", "ekspert", "best", "top rated"
- **AdSense Strategy**: 🔴 **AdSense disabled** (focus on conversions)

---

## 📊 Implementation

### Automatic Detection

The `FunnelStageDetector` class analyzes article titles and content using keyword scoring:

```php
$detector = new FunnelStageDetector();
$stage = $detector->detect( $post->post_title, $post->post_content );
// Returns: 'tofu', 'mofu', or 'bofu'
```

### AdSense Placement Logic

```php
// TOFU: Full placement (after first paragraph + at 66% mark)
if ( $stage === 'tofu' && $detector->should_enable_adsense( $stage ) ) {
    return $this->inject_box( $content, $ad_unit );  // 2 placements
}

// MOFU: Limited placement (after first paragraph only)
if ( $stage === 'mofu' && $detector->should_enable_adsense( $stage ) ) {
    return $this->inject_single_ad( $content, $ad_unit );  // 1 placement
}

// BOFU: No AdSense (return content unchanged)
if ( $stage === 'bofu' ) {
    return $content;  // 0 placements
}
```

### Post Meta Storage

Funnel stage is stored as post meta for analytics:

```php
get_post_meta( $post_id, 'pearblog_funnel_stage', true );
// Returns: 'tofu', 'mofu', or 'bofu'
```

---

## ⚙️ Configuration

### WP-CLI Configuration (Quick Start)

Configure AdSense via WP-CLI for automated deployments or quick setup:

```bash
# Set your AdSense publisher ID
wp option update pearblog_adsense_publisher_id 'ca-pub-YOUR_ID_HERE' --allow-root

# Enable AdSense
wp option update pearblog_adsense_enabled 1 --allow-root

# Set monetization strategy to funnel-aware
wp option update pearblog_adsense_strategy 'funnel_aware' --allow-root
```

**Available Strategies:**
- `aggressive` — Maximum revenue (all placements enabled)
- `balanced` — Revenue + user experience (default)
- `conservative` — User experience first (minimal ads)
- `funnel_aware` — Adaptive based on content intent (recommended)

**Configure funnel-aware behavior** (optional):

```bash
# Enable/disable AdSense per funnel stage
wp option update pearblog_adsense_enable_tofu 1 --allow-root    # TOFU: full ads
wp option update pearblog_adsense_enable_mofu 1 --allow-root    # MOFU: limited ads
wp option update pearblog_adsense_enable_bofu 0 --allow-root    # BOFU: ads disabled
```

### WordPress Admin UI

**Settings → PearBlog Engine → Monetization Tab → Google AdSense**

Configure AdSense enablement per funnel stage:

- ✅ **TOFU**: Enabled by default (informational traffic)
- ✅ **MOFU**: Enabled by default (limited ads)
- 🔴 **BOFU**: Disabled by default (protect conversions)

### Programmatic Configuration

```php
// Override via options
update_option( 'pearblog_adsense_enable_tofu', true );
update_option( 'pearblog_adsense_enable_mofu', true );
update_option( 'pearblog_adsense_enable_bofu', false );
```

### Filter Hooks

#### Customize Funnel Stage Scoring

```php
add_filter( 'pearblog_funnel_stage_scores', function( $scores, $title, $text ) {
    // Boost BOFU score for specific keywords
    if ( stripos( $text, 'zapytaj eksperta' ) !== false ) {
        $scores['bofu'] += 5;
    }
    return $scores;
}, 10, 3 );
```

#### Override AdSense Strategy

```php
add_filter( 'pearblog_adsense_funnel_strategy', function( $strategy ) {
    // Disable AdSense for MOFU content too
    $strategy['mofu'] = false;
    return $strategy;
} );
```

#### Custom Funnel Stage Detection

```php
add_action( 'pearblog_funnel_stage_detected', function( $post_id, $funnel_stage ) {
    // Log funnel stage for analytics
    error_log( sprintf( 'Post %d detected as %s', $post_id, $funnel_stage ) );
}, 10, 2 );
```

---

## 💸 Expected Revenue Impact

### 100,000 Monthly Pageviews

| Funnel Stage | Traffic % | AdSense Revenue | Lead Revenue | Total |
|--------------|-----------|-----------------|--------------|-------|
| TOFU         | 60%       | 300 - 1,200 zł  | 0 zł         | 300 - 1,200 zł |
| MOFU         | 25%       | 125 - 500 zł    | 5,000 - 10,000 zł | 5,125 - 10,500 zł |
| BOFU         | 15%       | 0 zł (disabled) | 10,000 - 20,000 zł | 10,000 - 20,000 zł |
| **Total**    | 100%      | **425 - 1,700 zł** | **15,000 - 30,000 zł** | **~15,500 - 32,000 zł** |

### 500,000 Monthly Pageviews

| Funnel Stage | Traffic % | AdSense Revenue | Lead Revenue | Total |
|--------------|-----------|-----------------|--------------|-------|
| TOFU         | 60%       | 1,500 - 9,000 zł | 0 zł         | 1,500 - 9,000 zł |
| MOFU         | 25%       | 625 - 3,750 zł  | 25,000 - 50,000 zł | 25,625 - 53,750 zł |
| BOFU         | 15%       | 0 zł (disabled) | 50,000 - 100,000 zł | 50,000 - 100,000 zł |
| **Total**    | 100%      | **~2,000 - 13,000 zł** | **~75,000 - 150,000 zł** | **~77,000 - 163,000 zł** |

### 1,000,000 Monthly Pageviews

| Funnel Stage | Traffic % | AdSense Revenue | Lead Revenue | Total |
|--------------|-----------|-----------------|--------------|-------|
| TOFU         | 60%       | 6,000 - 24,000 zł | 0 zł        | 6,000 - 24,000 zł |
| MOFU         | 25%       | 1,250 - 10,000 zł | 50,000 - 100,000 zł | 51,250 - 110,000 zł |
| BOFU         | 15%       | 0 zł (disabled) | 100,000 - 200,000 zł | 100,000 - 200,000 zł |
| **Total**    | 100%      | **~7,000 - 34,000 zł** | **~150,000 - 300,000 zł** | **~157,000 - 334,000 zł** |

**Key Insight**: Leads generate **5-10x more revenue** than AdSense at scale.

---

## 🔥 Best Practices

### ✅ DO

- Enable AdSense for TOFU content (guides, tutorials, FAQs)
- Use limited AdSense for MOFU content (1 ad only)
- Disable AdSense on BOFU pages (rankings, calculators, experts)
- Monitor funnel stage distribution in your analytics
- Test different keyword thresholds for your niche

### ❌ DON'T

- Place ads above CTAs or contact forms
- Enable AdSense on conversion-focused pages
- Ignore funnel stage when optimizing for revenue
- Assume all traffic is equal (BOFU converts 10-100x better)

---

## 🧪 Testing & Validation

### Check Funnel Stage Detection

```php
// View funnel stage for a specific post
$stage = get_post_meta( $post_id, 'pearblog_funnel_stage', true );
echo "Funnel Stage: " . $stage;  // Output: tofu, mofu, or bofu
```

### Verify AdSense Placement

```bash
# Search for AdSense units in generated content
wp post get <POST_ID> --field=post_content | grep -o 'pearblog-ad--adsense' | wc -l

# Expected output:
# TOFU: 2 (two ad units)
# MOFU: 1 (one ad unit)
# BOFU: 0 (no ad units)
```

### Analytics Queries

```sql
-- Count posts by funnel stage
SELECT
    meta_value AS funnel_stage,
    COUNT(*) AS post_count
FROM wp_postmeta
WHERE meta_key = 'pearblog_funnel_stage'
GROUP BY meta_value;

-- Expected distribution for balanced content:
-- tofu: ~50-60%
-- mofu: ~25-30%
-- bofu: ~10-20%
```

---

## 🚀 Future Enhancements

### Planned Features (Phase 2)

- [ ] A/B testing framework for AdSense placement
- [ ] Machine learning-based funnel stage prediction
- [ ] Real-time revenue tracking by funnel stage
- [ ] Automatic keyword expansion based on performance
- [ ] Integration with Google Analytics 4 for conversion tracking

### Integration Points

- **Content Pipeline**: Funnel detection runs during `ContentPipeline::run()`
- **MonetizationEngine**: AdSense injection respects funnel stage
- **Admin UI**: Configure strategy in Monetization tab
- **Post Meta**: Track funnel stage for reporting

---

## 📚 Related Documentation

- [MonetizationEngine.php](/mu-plugins/pearblog-engine/src/Monetization/MonetizationEngine.php) — Core monetization logic
- [FunnelStageDetector.php](/mu-plugins/pearblog-engine/src/Monetization/FunnelStageDetector.php) — Funnel classification
- [ContentPipeline.php](/mu-plugins/pearblog-engine/src/Pipeline/ContentPipeline.php) — Content generation flow
- [AdminPage.php](/mu-plugins/pearblog-engine/src/Admin/AdminPage.php) — Configuration UI

---

## 🤝 Contributing

### Extending Keyword Detection

To add custom keywords for your industry:

```php
// In your theme's functions.php or custom plugin
add_filter( 'pearblog_funnel_stage_scores', function( $scores, $title, $text ) {
    // Add custom BOFU keywords for your niche
    $custom_bofu_keywords = [ 'kupię teraz', 'instant quote', 'emergency service' ];

    foreach ( $custom_bofu_keywords as $keyword ) {
        if ( stripos( $text, $keyword ) !== false ) {
            $scores['bofu'] += 2;
        }
    }

    return $scores;
}, 10, 3 );
```

---

## 📞 Support

For questions or issues:

1. Check existing [GitHub Issues](https://github.com/AndyPearman89/PearBlog-Engine-/issues)
2. Review the [main README](/README.md)
3. Open a new issue with detailed reproduction steps

---

**Last Updated**: 2026-05-04
**Version**: 2.0.1
**Author**: PearBlog Engine Team

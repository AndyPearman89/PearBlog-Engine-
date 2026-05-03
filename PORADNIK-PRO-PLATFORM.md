# PORADNIK.PRO — Enterprise Decision Platform

> **Version:** 1.0.0
> **Platform Type:** Enterprise Decision Platform
> **Based on:** PearBlog Engine v6.0

---

## 🎯 Overview

Poradnik.pro transforms PearBlog Engine from a simple content automation tool into a comprehensive Enterprise Decision Platform that guides users from initial research through to final purchase decisions.

**Core Philosophy:** Users don't just want content — they want answers, comparisons, decisions, and service providers.

### Complete User Journey

```
Problem → Knowledge → Comparison → Decision → Expert → Contact → Lead
```

---

## 🏗️ Architecture

### Core Components

1. **Content Models**
   - Article (enhanced with blocks, intent, geo)
   - Comparison (vs pages with criteria)
   - Ranking (TOP lists, local)
   - Calculator (cost/ROI calculators)
   - Offer (local service listings)
   - Expert (marketplace profiles)
   - Lead (monetization pipeline)

2. **Block System**
   - Dynamic content rendering
   - Modular blocks: intro, steps, table, FAQ, comparison, ranking, calculator, experts, lead_form, affiliate_box, related, text
   - Frontend rendering via BlockRenderer
   - A/B testable

3. **AI Systems**
   - Intent Detector (informational/transactional/navigational/local)
   - Decision Assistant (AI recommendations)
   - Content Enrichment (automatic FAQ, expert matching)
   - Comparison Generator
   - Ranking Generator

4. **Discovery Systems**
   - Internal Link Graph (automatic content linking)
   - Offer Discovery (local service matching)
   - Expert Marketplace (verified providers)
   - Lead Matching (automatic expert assignment)

---

## 📦 Custom Post Types

### 1. `pearblog_comparison`
**URL Structure:** `/porownanie/{topic1}-vs-{topic2}`

Stores comparison data between two or more items.

**Meta Fields:**
- `pearblog_comparison_items` (JSON) - Items being compared
- `pearblog_comparison_criteria` (JSON) - Comparison criteria with weights
- `pearblog_comparison_winner` (int) - Winner index
- `pearblog_comparison_blocks` (JSON) - Content blocks
- `pearblog_geo_data` (JSON) - Location data if local

### 2. `pearblog_ranking`
**URL Structure:** `/ranking/{category}-{city}`

TOP lists for products, services, providers.

**Meta Fields:**
- `pearblog_ranking_category` (string) - Category name
- `pearblog_ranking_location` (JSON) - City and region
- `pearblog_ranking_items` (JSON) - Ranked items with scores
- `pearblog_ranking_criteria` (JSON) - Ranking criteria

### 3. `pearblog_calculator`
**URL Structure:** `/kalkulator/{name}`

Interactive calculators for costs, ROI, etc.

**Meta Fields:**
- `pearblog_calculator_inputs` (JSON) - Input fields configuration
- `pearblog_calculator_formula` (string) - Calculation formula
- `pearblog_calculator_output` (JSON) - Output configuration

### 4. `pearblog_offer`
**URL Structure:** `/oferta/{slug}`

Local service offers and listings.

**Meta Fields:**
- `pearblog_offer_location` (JSON) - Detailed location with coordinates
- `pearblog_offer_provider_id` (int) - Link to expert/provider
- `pearblog_offer_price_range` (JSON) - Min/max pricing
- `pearblog_offer_category` (string) - Offer category
- `pearblog_offer_images` (JSON) - Gallery images
- `pearblog_offer_featured` (bool) - Featured status (paid)

### 5. `pearblog_expert`
**URL Structure:** `/specjalista/{slug}`

Service provider profiles for marketplace.

**Meta Fields:**
- `pearblog_expert_category` (string) - Service category
- `pearblog_expert_specializations` (JSON) - List of specializations
- `pearblog_expert_location` (JSON) - City and region
- `pearblog_expert_rating` (float) - Average rating 0-5
- `pearblog_expert_review_count` (int) - Number of reviews
- `pearblog_expert_verified` (bool) - Verification status
- `pearblog_expert_premium` (bool) - Premium membership
- `pearblog_expert_photo` (string) - Profile photo URL
- `pearblog_expert_contact` (JSON) - Contact information
- `pearblog_expert_services` (JSON) - Services and pricing
- `pearblog_expert_portfolio` (JSON) - Portfolio images

### 6. `pearblog_lead`
**URL Structure:** Admin only

Lead capture records for monetization.

**Meta Fields:**
- `pearblog_lead_name` (string) - Lead name
- `pearblog_lead_email` (string) - Email address
- `pearblog_lead_phone` (string) - Phone number
- `pearblog_lead_city` (string) - City
- `pearblog_lead_message` (string) - Lead message
- `pearblog_lead_category` (string) - Service category
- `pearblog_lead_source_url` (string) - Source page
- `pearblog_lead_status` (string) - new/contacted/converted/closed
- `pearblog_lead_created_at` (datetime) - Submission time

---

## 🔌 REST API Endpoints

### Decision Assistant
```
POST /wp-json/pearblog/v1/decision/recommend
```

**Request Body:**
```json
{
  "need": "Chcę wyremontować łazienkę",
  "budget": 15000,
  "location": "Warszawa"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "recommendation": "...",
    "reasoning": "...",
    "next_steps": ["...", "..."],
    "links": [
      {"type": "article", "url": "...", "title": "..."},
      {"type": "comparison", "url": "...", "title": "..."}
    ]
  }
}
```

### Lead Submission
```
POST /wp-json/pearblog/v1/lead/submit
```

**Request Body:**
```json
{
  "name": "Jan Kowalski",
  "email": "jan@example.com",
  "phone": "+48 123 456 789",
  "city": "Warszawa",
  "message": "Potrzebuję remontu łazienki",
  "category": "remont"
}
```

### Calculator
```
POST /wp-json/pearblog/v1/calculator/{id}/calculate
```

**Request Body:**
```json
{
  "values": {
    "area": 20,
    "material_cost": 150,
    "labor_cost": 100
  }
}
```

### Get Comparison
```
GET /wp-json/pearblog/v1/comparison/{slug}
```

### Get Ranking
```
GET /wp-json/pearblog/v1/ranking/{slug}
```

### Search Experts
```
GET /wp-json/pearblog/v1/experts/search?category=remont&city=Warszawa&verified_only=true
```

### Search Offers
```
GET /wp-json/pearblog/v1/offers/search?category=remont&city=Warszawa
```

---

## 🎨 Block System

### Available Blocks

1. **intro** - Introduction paragraph
2. **steps** - Step-by-step guide
3. **table** - Data table
4. **faq** - FAQ accordion
5. **comparison** - Embedded comparison
6. **ranking** - Embedded ranking
7. **calculator** - Embedded calculator
8. **experts** - Expert cards grid
9. **lead_form** - Lead generation form
10. **affiliate_box** - Affiliate product box
11. **related** - Related content links
12. **text** - Rich text content

### Example Block Structure

```php
$blocks = [
    [
        'type' => 'intro',
        'data' => ['content' => 'Introduction text...']
    ],
    [
        'type' => 'steps',
        'data' => [
            'title' => 'How to renovate bathroom',
            'steps' => [
                ['title' => 'Step 1', 'description' => '...'],
                ['title' => 'Step 2', 'description' => '...']
            ]
        ]
    ],
    [
        'type' => 'lead_form',
        'data' => [
            'title' => 'Get free quote',
            'category' => 'renovation'
        ]
    ]
];
```

---

## 🎯 Intent Detection

### Intent Types

1. **informational** - User seeking knowledge
   - Auto-adds: FAQ, related articles

2. **transactional** - User ready to buy/hire
   - Auto-adds: Lead form, comparison, experts

3. **navigational** - User looking for specific brand/site
   - Auto-adds: External links, brand info

4. **local** - User seeking local services
   - Auto-adds: Local experts, rankings, offers, lead form

### Automatic Enrichment

When content is published, the Intent Detector:
1. Analyzes content and detects intent
2. Extracts keywords and geo data
3. Generates appropriate blocks
4. Links relevant experts and offers
5. Builds internal link graph

---

## 🔗 Internal Link Graph

Automatically builds relationships between:
- Article ↔ Article
- Article ↔ Comparison
- Article ↔ Ranking
- Article ↔ Calculator
- Article ↔ Expert
- Article ↔ Offer

Link types are weighted by relevance (0-1 score).

---

## 💰 Monetization

### Lead Generation
- Capture user inquiries
- Match with verified experts
- Commission on conversions
- Slack/email notifications

### Premium Listings
- Featured rankings positions
- Premium expert profiles
- Sponsored offers

### Affiliate Integration
- Booking.com
- Airbnb
- SaaS products
- Custom affiliate boxes

---

## 📝 Shortcodes

```php
// Decision Assistant
[decision_assistant]

// Lead Form
[lead_form title="Get Quote" category="renovation"]

// Experts List
[experts ids="1,2,3"]
[experts category="remont" city="Warszawa" limit="5"]

// Comparison
[comparison id="123"]

// Ranking
[ranking id="456" limit="10"]

// Calculator
[calculator id="789"]
```

---

## 🎨 Frontend Assets

### JavaScript
- Decision Assistant form handling
- Lead form submission (AJAX)
- Calculator computations
- FAQ accordion
- Real-time validation

### CSS
- Responsive grid layouts
- Card components
- Form styling
- Animation effects
- Mobile-first design

---

## 🚀 Getting Started

### 1. Activate Decision Platform

The Decision Platform is automatically activated when you install PearBlog Engine v6.0+.

### 2. Create Your First Comparison

```php
use PearBlogEngine\DecisionPlatform\ComparisonEngine;

$engine = new ComparisonEngine();
$comparison = $engine->generate('Product A', 'Product B', 'electronics', 'Warszawa');
$comparison->save();
```

### 3. Create a Ranking

```php
use PearBlogEngine\DecisionPlatform\RankingEngine;

$engine = new RankingEngine();
$ranking = $engine->generate('najlepsze firmy remontowe', 'Warszawa', 10);
$ranking->save();
```

### 4. Add Experts

Create expert profiles via WordPress admin → Specjaliści → Add New

### 5. Enable Lead Capture

Add `[lead_form]` shortcode to your articles or enable automatic injection for transactional content.

---

## 🔧 Configuration

### Options

- `pearblog_enable_decision_platform` - Enable/disable platform (default: true)
- `pearblog_lead_notification_email` - Lead notification email
- `pearblog_expert_approval_required` - Require admin approval for experts
- `pearblog_premium_listing_price` - Price for premium listings

---

## 📊 Analytics Events

Track these events in your analytics:

- `pearblog_comparison_viewed`
- `pearblog_ranking_viewed`
- `pearblog_calculator_used`
- `pearblog_lead_generated`
- `pearblog_expert_contacted`
- `pearblog_decision_assistant_used`

---

## 🎓 Best Practices

1. **Content Strategy**
   - Mix informational and transactional content
   - Create location-specific rankings for each major city
   - Build comparisons for popular product/service pairs

2. **Expert Curation**
   - Verify experts before listing
   - Encourage portfolio uploads
   - Collect and display reviews

3. **Lead Quality**
   - Qualify leads with specific questions
   - Match leads quickly (< 24h response time)
   - Track conversion rates

4. **SEO Optimization**
   - Use programmatic URLs with city names
   - Include FAQ blocks for featured snippets
   - Build internal link graphs between related content

---

## 🤝 Integration

### WordPress Hooks

```php
// After content enrichment
add_action('pearblog_content_enriched', function($post_id, $intent_data) {
    // Custom logic
}, 10, 2);

// After lead generation
add_action('pearblog_lead_generated', function($lead_id, $data) {
    // Send to CRM
}, 10, 2);

// Filter experts before display
add_filter('pearblog_expert_display', function($expert) {
    // Modify expert data
    return $expert;
});
```

---

## 📚 Further Reading

- [API Documentation](API-DOCUMENTATION.md)
- [Database Schema](DATABASE-MIGRATIONS.md)
- [Developer Hooks](DEVELOPER-HOOKS.md)
- [SEO Guide](MARKETING-GUIDE.md)

---

## 🆘 Support

For issues and feature requests, please visit:
https://github.com/AndyPearman89/PearBlog-Engine-/issues

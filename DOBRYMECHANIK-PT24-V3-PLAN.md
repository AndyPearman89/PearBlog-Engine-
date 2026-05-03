# DobryMechanik.PT24.PRO - Implementation Plan V3

**Full-Stack SaaS Marketplace for Car Mechanic Lead Generation**

---

## 🎯 Product Overview

**Vertical:** Car Mechanic Services (High Intent)
**Business Model:** Lead Marketplace + SaaS Panel
**North Star Metric:** Revenue Per User (RPU) ↑

### Revenue Streams
1. **Pay-per-lead** - EXCLUSIVE (120-300 zł), STANDARD (50-120 zł), BROAD (20-50 zł)
2. **Premium Subscriptions** - PREMIUM / PREMIUM+ tiers
3. **Hybrid Model** - Subscription + discounted leads

### Target Metrics (Month 1)
- 20 leads/day × 50 zł = 1,000 zł/day
- Response time < 15 min
- Accept rate > 60%

---

## 📐 Information Architecture

```
/                           → Homepage (search + CTA)
/{miasto}/mechanik          → City landing page
/ranking/{miasto}/mechanik  → Workshop ranking (money page)
/profil/{slug}              → Workshop profile page
/zglos-usterke              → Standalone lead form
/panel                      → Workshop SaaS panel
/faq                        → FAQ page
```

**Cross-linking:** Poradnik.pro → City pages + Ranking pages

---

## 🏗️ Technical Architecture

### Phase 1: Foundation (Week 1-2)
**Already Complete:**
- ✅ PT24 Landing Generator V2
- ✅ Lead capture system
- ✅ Database tables for leads

**New Requirements:**
1. Workshop CPT with custom fields
2. City/service taxonomy
3. Ranking page template
4. Profile page template

### Phase 2: Core Engine (Week 3-4)
1. Lead routing algorithm
2. Workshop scoring system
3. Capacity management
4. Transaction ledger

### Phase 3: SaaS Panel (Week 5-6)
1. Workshop dashboard
2. Lead management interface
3. Billing/balance system
4. Statistics & KPIs

### Phase 4: Advanced Features (Week 7-8)
1. Map integration (Leaflet)
2. Anti-fraud system
3. A/B testing framework
4. Advanced analytics

---

## 🗄️ Data Model

### Custom Post Type: `workshop`

**Fields:**
```php
- name (title)
- slug (post_name)
- city (taxonomy)
- services[] (taxonomy)
- rating (meta: 1-5)
- reviews_count (meta)
- premium_tier (meta: free/premium/premium+)
- capacity_per_day (meta)
- response_rate (meta: 0-100%)
- conversion_rate (meta: 0-100%)
- active (meta: boolean)
- contact_phone (meta)
- contact_email (meta)
- address (meta)
- latitude (meta)
- longitude (meta)
- opening_hours (meta: JSON)
```

### Database Tables

**1. `wp_mechanic_leads`**
```sql
CREATE TABLE wp_mechanic_leads (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME NOT NULL,
    city VARCHAR(100) NOT NULL,
    service VARCHAR(100) NOT NULL,
    car_make VARCHAR(100) NOT NULL,
    car_model VARCHAR(100),
    problem TEXT NOT NULL,
    contact_phone VARCHAR(20),
    contact_email VARCHAR(100),
    lead_type VARCHAR(20) NOT NULL, -- exclusive/standard/broad
    price DECIMAL(10,2),
    status VARCHAR(20) DEFAULT 'new', -- new/assigned/accepted/rejected/expired
    assigned_to TEXT, -- JSON array of workshop IDs
    source_url TEXT,
    user_ip VARCHAR(45),
    quality_score INT DEFAULT 0,
    INDEX idx_city (city),
    INDEX idx_status (status),
    INDEX idx_timestamp (timestamp)
);
```

**2. `wp_mechanic_assignments`**
```sql
CREATE TABLE wp_mechanic_assignments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    lead_id BIGINT NOT NULL,
    workshop_id BIGINT NOT NULL,
    assigned_at DATETIME NOT NULL,
    responded_at DATETIME,
    status VARCHAR(20) DEFAULT 'pending', -- pending/accepted/rejected
    price DECIMAL(10,2),
    FOREIGN KEY (lead_id) REFERENCES wp_mechanic_leads(id),
    INDEX idx_workshop (workshop_id),
    INDEX idx_lead (lead_id)
);
```

**3. `wp_mechanic_transactions`**
```sql
CREATE TABLE wp_mechanic_transactions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    workshop_id BIGINT NOT NULL,
    lead_id BIGINT,
    amount DECIMAL(10,2) NOT NULL,
    type VARCHAR(20) NOT NULL, -- charge/credit/subscription
    status VARCHAR(20) DEFAULT 'pending',
    description TEXT,
    created_at DATETIME NOT NULL,
    INDEX idx_workshop (workshop_id),
    INDEX idx_created (created_at)
);
```

**4. `wp_mechanic_balances`**
```sql
CREATE TABLE wp_mechanic_balances (
    workshop_id BIGINT PRIMARY KEY,
    balance DECIMAL(10,2) DEFAULT 0,
    updated_at DATETIME NOT NULL
);
```

---

## 🎨 Frontend Pages

### 1. Homepage `/`
**Hero:**
```
H1: Dobry mechanik w {MIASTO} — sprawdź ceny i dostępność
Sub: Porównaj warsztaty, opinie i otrzymaj dopasowane oferty naprawy.
CTA: Znajdź mechanika
Trust: ✔ Bezpłatnie ✔ Sprawdzone warsztaty ✔ Odpowiedzi w 24h
```

**Search Form:**
- City dropdown
- CTA → redirects to `/{miasto}/mechanik`

### 2. City Landing `/{miasto}/mechanik`
**Sections:**
1. Hero + Lead Form (above fold)
2. Services grid (diagnostyka, hamulce, wymiana oleju, silnik)
3. Cost block (ile kosztuje naprawa auta w {miasto})
4. Mini ranking (Top 3 workshops)
5. Map/List proof
6. FAQ
7. Final CTA

**Uses:** Existing PT24 landing generator system (already built!)

### 3. Ranking Page `/ranking/{miasto}/mechanik` 🤑
**The Money Page**

**Top 3 Featured:**
```
🏆 #1 TOP WYBÓR (PREMIUM+)
   Warsztat ABC
   ⭐⭐⭐⭐⭐ 4.9 (127 opinii)
   Dostępny: dziś
   [Zapytaj] [Zobacz profil]

✅ #2 POLECANY (PREMIUM)
   Warsztat XYZ
   ⭐⭐⭐⭐ 4.7 (89 opinii)
   Dostępny: jutro
   [Zapytaj] [Zobacz profil]

✅ #3 POLECANY (PREMIUM)
```

**Full List:**
- All workshops (FREE tier included)
- Filters: Service type, rating, availability
- Sort: Rating, distance, price
- Map view (right column desktop, bottom sheet mobile)

**Sticky CTA:** "Otrzymaj 3 oferty"

### 4. Workshop Profile `/profil/{slug}`
**Layout:**
- Header: Name, rating, badge
- Services offered
- Location map
- Opening hours
- Photos/gallery
- Reviews
- CTA: "Zapytaj o wycenę"

### 5. Lead Form `/zglos-usterke`
**Progressive Disclosure:**
```
Step 1: Marka auta (required)
Step 2: Model (optional)
Step 3: Problem (textarea, required)
Step 4: Miasto (auto-detected or select)
Step 5: Telefon / Email (required)

Microcopy: "Otrzymasz do 3 ofert w 24h. Bez zobowiązań."
CTA: Wyślij zapytanie
```

**Validation:**
- Required: car_make, problem, city, contact
- Inline errors
- Debounced validation

---

## ⚙️ Lead Routing Engine

### Algorithm

**Input:** Lead object
```php
{
    city: 'krakow',
    service: 'diagnostyka',
    car_make: 'toyota',
    problem: 'dziwny hałas przy hamowaniu',
    timestamp: '2026-05-03 18:00:00'
}
```

**Filtering:**
1. Match city
2. Match service capability
3. Status = active
4. Available capacity (not exceeded daily limit)
5. Balance > 0 (can afford lead)

**Scoring:**
```php
score = (rating * 0.30)
      + (response_rate * 0.25)
      + (conversion_rate * 0.20)
      + (premium_weight * 0.15)
      + (recency * 0.10)

premium_weight:
- FREE: 1.0
- PREMIUM: 1.5
- PREMIUM+: 2.0
```

**Priority Queue:**
1. PREMIUM+ workshops (guaranteed slots)
2. PREMIUM workshops (priority queue)
3. FREE workshops (fallback)

**Assignment:**
- EXCLUSIVE: Top 1 workshop
- STANDARD: Top 3 workshops
- BROAD: Top 5+ workshops

**Dynamic Downgrade:**
If no takers within 1 hour:
- EXCLUSIVE → STANDARD → BROAD

**Output:**
```php
[
    {
        workshop_id: 123,
        price: 80,
        tier: 'premium+'
    },
    {
        workshop_id: 456,
        price: 60,
        tier: 'premium'
    },
    {
        workshop_id: 789,
        price: 50,
        tier: 'free'
    }
]
```

---

## 💰 Billing & Pricing

### Lead Pricing
- **Diagnostyka:** 20-50 zł
- **Hamulce:** 40-80 zł
- **Wymiana oleju:** 30-60 zł
- **Naprawa silnika:** 80-200 zł

### Subscription Plans
**PREMIUM** (499 zł/miesiąc)
- Priority in routing
- Lower lead prices (-20%)
- Dashboard analytics
- Featured badge

**PREMIUM+** (999 zł/miesiąc)
- Guaranteed top 3 placement
- Exclusive leads available
- Lower lead prices (-40%)
- Premium badge
- Dedicated support

### Transaction Flow
1. Lead created → routing engine runs
2. Workshops assigned → transactions created (status: pending)
3. Workshop accepts → charge workshop (status: completed)
4. Workshop rejects → cancel transaction (status: cancelled)
5. No response (24h) → charge anyway (status: completed)

### Balance Management
- Initial credit: 500 zł (promotional)
- Low balance alert: < 100 zł
- Auto-pause at 0 zł
- Top-up options: 500/1000/2000 zł

---

## 🔧 Workshop SaaS Panel `/panel`

### Dashboard
**KPIs:**
- Leads today/week/month
- Response time average
- Accept rate %
- Revenue this month
- Current balance

**Lead List:**
```
Table:
- Timestamp
- Customer (phone/email)
- Car (make/model)
- Problem
- Price
- Status (new/accepted/rejected)
- Actions (View, Accept, Reject, Contact)
```

**Lead Detail:**
- Full problem description
- Customer contact
- Quick actions (call, email, SMS)
- Accept/Reject buttons

### Settings
- Workshop info (name, address, phone)
- City coverage
- Service types
- Capacity limits (leads/day)
- Opening hours
- Auto-accept preferences

### Billing
- Current balance
- Transaction history
- Top-up credits
- Invoice download
- Subscription management

### Statistics
- Lead acceptance rate
- Response time trends
- Revenue by service type
- Customer satisfaction (future)

---

## 🗺️ Map Integration

**Technology:** Leaflet.js

**Desktop Layout:**
- Left column: Workshop list (60%)
- Right column: Map (40%)
- Pins color-coded by tier

**Mobile Layout:**
- List view default
- Map button → bottom sheet
- Sticky CTA at bottom

**Pin Behavior:**
- FREE tier → clustered
- PREMIUM → individual pins
- PREMIUM+ → highlighted pins (larger, animated)
- Click → popup with quick info + CTA

**Interactions:**
- Pin click → highlight card in list
- Card hover → pulse pin on map
- Filter → update both list and map

---

## 🛡️ Anti-Fraud System

### Duplicate Detection
```php
hash = md5(contact + car_make + problem_summary)
if (exists in last 7 days) → flag as duplicate
```

### Rate Limiting
- 3 submissions per IP per hour
- 10 submissions per IP per day
- CAPTCHA after 2 submissions

### Quality Scoring
```php
score = 100
- incomplete fields: -20 each
- generic problem text: -30
- disposable email: -40
- VPN/proxy IP: -20

if (score < 40) → manual review
```

### Honeypot Field
- Hidden field in form
- If filled → discard silently

---

## 📊 Analytics & Tracking

### User Funnel
1. Visit city page
2. View ranking
3. Click workshop
4. Submit lead form
5. Lead accepted

### KPIs to Track
- **CTR:** Ranking → CTA
- **CVR:** Form view → Submit
- **Response Rate:** Lead created → Workshop response
- **Accept Rate:** Leads sent → Leads accepted
- **RPM:** Revenue per 1000 visitors

### A/B Tests
1. CTA copy variations
2. Form field count (4 vs 5 vs 6 fields)
3. Ranking order algorithm
4. Pricing display

---

## 🚀 Implementation Roadmap

### Phase 1: Foundation (Week 1-2) ✅ PARTIALLY DONE
- [x] PT24 landing generator (EXISTS)
- [x] Lead capture system (EXISTS)
- [ ] Workshop CPT
- [ ] City/service taxonomies
- [ ] Basic ranking template

### Phase 2: Routing Engine (Week 3-4)
- [ ] Lead routing algorithm
- [ ] Scoring system
- [ ] Assignment logic
- [ ] Transaction creation

### Phase 3: Workshop Panel (Week 5-6)
- [ ] Authentication system
- [ ] Dashboard UI
- [ ] Lead management
- [ ] Balance display

### Phase 4: Billing System (Week 7-8)
- [ ] Transaction processing
- [ ] Balance management
- [ ] Subscription handling
- [ ] Invoice generation

### Phase 5: Advanced Features (Month 3)
- [ ] Map integration
- [ ] Anti-fraud system
- [ ] A/B testing framework
- [ ] Advanced analytics

### Phase 6: Launch (Month 4)
- [ ] Seed 20-50 workshops in Kraków
- [ ] Soft launch
- [ ] Monitor metrics
- [ ] Iterate based on feedback

---

## 🎯 Launch Strategy

### Week 1: Kraków Pilot
- Onboard 20-50 workshops
- Seed initial reviews/ratings
- Test routing engine

### Week 2-3: First Leads
- Drive traffic from Poradnik.pro
- Monitor response times
- Optimize CR

### Month 2: Expansion
- Add 4-9 more cities (Warszawa, Wrocław, Gdańsk, etc.)
- Refine pricing
- Add premium tiers

### Month 3: Scale
- 10+ cities
- 200+ workshops
- Target: 100+ leads/day

---

## 💡 Key Design Principles

1. **Mobile First** - 70% traffic is mobile
2. **Lead Above Fold** - Form visible immediately
3. **Fast Form** - Complete in 30-45 seconds
4. **Clear CTAs** - Every 2-3 sections
5. **Social Proof** - Ratings, reviews, badges
6. **Trust Signals** - Free, verified, fast response
7. **Progressive Disclosure** - Don't overwhelm users

---

## 🔗 Integration Points

### With Existing PT24 System
- Reuse landing generator for `/{miasto}/mechanik`
- Reuse lead capture forms
- Reuse PT24 lead database schema (extend)
- Reuse email notification system

### With Poradnik.pro
- Internal links from relevant articles
- Cross-site conversion funnel
- Shared analytics

### External Services
- Map API: OpenStreetMap + Leaflet
- Payment Gateway: Stripe/PayU (future)
- SMS API: Twilio (notifications)
- Email: SendGrid (transactional)

---

## 🎓 Success Criteria

### Month 1
- [ ] 20 leads/day
- [ ] 1,000 zł/day revenue
- [ ] < 15 min response time
- [ ] > 60% accept rate

### Month 3
- [ ] 100 leads/day
- [ ] 5,000 zł/day revenue
- [ ] 10+ cities
- [ ] 200+ workshops

### Month 6
- [ ] 500 leads/day
- [ ] 25,000 zł/day revenue
- [ ] 20+ cities
- [ ] 1,000+ workshops

---

## 📋 Technical Decisions

### WordPress vs Next.js
**Decision:** Start with WordPress for MVP
- Leverage existing PT24 system
- Faster time to market
- Team familiarity

**Future:** Migrate ranking pages to Next.js for performance

### Database
- Use WordPress custom tables for scalability
- Separate tables for leads, assignments, transactions
- Proper indexing for performance

### Caching
- Object cache (Redis) for workshop data
- Page cache for ranking pages
- Edge cache (Cloudflare) for static pages

### Hosting
- Separate subdomain: dobrymechanik.pt24.pro
- Dedicated database
- CDN for assets

---

## 🚨 Risk Mitigation

**Risk 1:** Low workshop adoption
- **Mitigation:** Free trial period, onboarding support

**Risk 2:** Low lead quality
- **Mitigation:** Anti-fraud system, manual review initially

**Risk 3:** Slow response times
- **Mitigation:** SLA requirements, automated reminders

**Risk 4:** Billing disputes
- **Mitigation:** Clear terms, dispute resolution process

**Risk 5:** Competition
- **Mitigation:** Focus on UX, fair pricing, quality control

---

## 📝 Next Immediate Actions

1. **Create Workshop CPT** with all custom fields
2. **Build Ranking Page Template** with tiered display
3. **Implement Basic Routing Engine** (MVP version)
4. **Create Workshop Panel** (authentication + dashboard)
5. **Set up Transaction System** (basic ledger)

---

## 🎉 Conclusion

This is a **full-stack SaaS marketplace** that combines:
- Lead generation (PT24 system)
- Routing engine (intelligent assignment)
- SaaS panel (workshop management)
- Billing system (transactions + subscriptions)
- Premium tiers (monetization)

**Estimated Development Time:** 3-4 months for MVP
**Expected ROI:** 30,000 zł/month after 6 months

This is a complex but high-value product that leverages the existing PT24 infrastructure while adding sophisticated marketplace mechanics.

---

**Status:** 📋 PLANNING COMPLETE
**Next Step:** Begin Phase 1 implementation with Workshop CPT


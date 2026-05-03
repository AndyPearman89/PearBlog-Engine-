# PT24.PRO Workshop Panel V2 - Implementation Plan

**Product:** SaaS Dashboard for Service Providers (Mechanik, Hydraulik, Elektryk)
**Goal:** Lead Management + Quick Response + Monetization
**UX:** Dashboard-First + Mobile-First

---

## 🎯 Product Overview

A premium SaaS panel where workshops/service providers can:
- Receive and manage leads in real-time
- Accept or reject leads based on capacity
- Track balance and billing
- Monitor performance statistics
- Configure service preferences

**Business Model:** Prepaid credits system
- Leads assigned to workshops with available balance
- Balance deducted upon lead acceptance
- No balance = no leads

---

## 📐 Information Architecture

### URL Structure
```
/panel                    → Dashboard (overview)
/panel/leady              → Lead list
/panel/lead/{id}          → Lead detail
/panel/saldo              → Balance & transactions
/panel/statystyki         → Statistics & KPIs
/panel/ustawienia         → Settings & preferences
```

### Authentication
- WordPress user roles or custom auth
- Workshop accounts linked to `pt24_workshop` CPT
- Role: `pt24_workshop_user`

---

## 🎨 UI/UX Design System

### Design Style: Dark PRO (Premium SaaS)

**Colors:**
```css
--bg-primary: #0b1118
--bg-card: #111a24
--bg-card-hover: #1a2532
--text-primary: #ffffff
--text-secondary: #94a3b8
--accent-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%)
--success: #10b981
--warning: #f59e0b
--danger: #ef4444
--border: #1e293b
```

**Typography:**
```css
--font-primary: Inter, system-ui, sans-serif
--font-mono: 'JetBrains Mono', monospace
```

**Components:**
- Glassmorphic cards with subtle glow
- Gradient buttons with hover effects
- Animated badges for status
- Smooth transitions
- Micro-interactions

---

## 📊 Dashboard (/panel)

### Widgets (4 main KPIs)

**1. Nowe Leady 🔔**
```
╔════════════════════════╗
║ 🔔 Nowe Leady         ║
║                        ║
║      3                 ║
║   czekają na Ciebie    ║
║                        ║
║ [Zobacz leady →]       ║
╚════════════════════════╝
```

**2. Dzisiaj 📈**
```
╔════════════════════════╗
║ 📈 Dzisiaj            ║
║                        ║
║ Leady: 5              ║
║ Przychód: ~400 zł     ║
║                        ║
╚════════════════════════╝
```

**3. Saldo 💰**
```
╔════════════════════════╗
║ 💰 Saldo              ║
║                        ║
║    320 zł              ║
║                        ║
║ [Doładuj ➕]          ║
╚════════════════════════╝
```

**4. Średni Czas Odpowiedzi ⚡**
```
╔════════════════════════╗
║ ⚡ Czas odpowiedzi    ║
║                        ║
║    12 min              ║
║  ✅ Bardzo dobry      ║
║                        ║
╚════════════════════════╝
```

### Recent Leads Table
- Last 5 leads with quick actions
- Click to view detail
- Status badges

### CTA
- **Primary:** "Przejdź do leadów" (if new leads pending)
- **Secondary:** "Doładuj konto" (if balance low)

---

## 📋 Lead List (/panel/leady)

### Filter Bar
```
[🔴 Nowe] [🟡 Oczekujące] [🟢 Zaakceptowane] [❌ Odrzucone]
Sort: [Najnowsze ▼] [Najwyższa wartość] [Najbliższe]
```

### Lead Card
```
╔═══════════════════════════════════════════╗
║ 🔴 NOWY                        50 zł      ║
║                                            ║
║ Naprawa hamulców                          ║
║ Kraków                                     ║
║ "Piszczą hamulce przy hamowaniu..."      ║
║                                            ║
║ EXCLUSIVE                                  ║
║                                            ║
║ [Zobacz] [Zadzwoń 📞]                     ║
╚═══════════════════════════════════════════╝
```

**Status Colors:**
- 🔴 NEW (red) - requires immediate action
- 🟡 PENDING (yellow) - awaiting customer response
- 🟢 ACCEPTED (green) - job confirmed
- ❌ REJECTED (gray) - declined

**Lead Types:**
- **EXCLUSIVE** - only you (highest price)
- **STANDARD** - max 3 workshops
- **BROAD** - 5+ workshops

### Mobile Optimization
- Swipeable cards
- Pull to refresh
- Infinite scroll
- Quick action buttons

---

## 🔍 Lead Detail (/panel/lead/{id})

### Header
```
Lead #12345                                [✅ Akceptuj]
🔴 NOWY                    EXCLUSIVE         [❌ Odrzuć]
50 zł
```

### Full Information

**Problem Description:**
```
╔══════════════════════════════════════════════╗
║ Opis problemu:                               ║
║                                               ║
║ Piszczą hamulce przy hamowaniu. Ostatnio     ║
║ robione 2 lata temu. Auto: Toyota Corolla    ║
║ 2015. Przejechane ok 80k km.                 ║
╚══════════════════════════════════════════════╝
```

**Contact Information** (revealed after acceptance)
```
╔══════════════════════════════════════════════╗
║ Dane kontaktowe:                              ║
║                                               ║
║ 👤 Jan Kowalski                              ║
║ 📞 +48 123 456 789                           ║
║ 📧 jan@example.com                           ║
║                                               ║
║ Quick actions:                                ║
║ [📞 Zadzwoń] [💬 SMS] [📧 Email]           ║
╚══════════════════════════════════════════════╝
```

**Location:**
```
📍 Kraków, Krowodrza
🗺️ [Zobacz na mapie]
```

**Lead Details:**
- Type: EXCLUSIVE
- Price: 50 zł
- Received: 10 min ago
- Expires: 23h 50m

### Actions

**Primary CTA:**
```
╔══════════════════════════════════════════════╗
║ ✅ Akceptuj lead (50 zł)                    ║
║                                               ║
║ Saldo po transakcji: 270 zł                  ║
╚══════════════════════════════════════════════╝
```

**Secondary:**
```
❌ Odrzuć lead
```

**Rules:**
- Acceptance debits balance immediately
- Contact info revealed after payment
- Can't accept if insufficient balance
- 24h to respond, then lead expires

---

## 💰 Balance (/panel/saldo)

### Current Balance Card
```
╔══════════════════════════════════════════════╗
║ Aktualne saldo                                ║
║                                               ║
║          320 zł                               ║
║                                               ║
║ [➕ Doładuj konto]                           ║
╚══════════════════════════════════════════════╝
```

### Top-Up Options
```
[+100 zł] [+200 zł] [+500 zł] [+1000 zł] [Inna kwota]
```

### Transaction History
```
╔════════════════════════════════════════════════════╗
║ Historia transakcji                                ║
╠════════════════════════════════════════════════════╣
║ 2026-05-03 18:30                                   ║
║ Lead #12345: Naprawa hamulców         -50 zł      ║
║ Saldo: 320 zł                                      ║
║                                                     ║
║ 2026-05-03 10:00                                   ║
║ Doładowanie konta                     +200 zł      ║
║ Saldo: 370 zł                                      ║
║                                                     ║
║ 2026-05-02 15:45                                   ║
║ Lead #12344: Wymiana oleju            -30 zł       ║
║ Saldo: 170 zł                                      ║
╚════════════════════════════════════════════════════╝
```

### Low Balance Alert
```
⚠️ Niskie saldo!
Doładuj konto, aby otrzymywać nowe leady.
```

---

## 📈 Statistics (/panel/statystyki)

### KPIs Grid

**Lead Statistics:**
```
╔═══════════════════╗  ╔═══════════════════╗
║ Wszystkie leady   ║  ║ Zaakceptowane     ║
║      127          ║  ║       89          ║
╚═══════════════════╝  ╚═══════════════════╝
```

**Performance Metrics:**
```
╔══════════════════════╗  ╔══════════════════════╗
║ Acceptance Rate      ║  ║ Conversion Rate      ║
║      70%             ║  ║       85%            ║
║  ✅ Bardzo dobry    ║  ║  ✅ Doskonały       ║
╚══════════════════════╝  ╚══════════════════════╝
```

**Revenue:**
```
╔══════════════════════╗  ╔══════════════════════╗
║ Ten miesiąc          ║  ║ Wszystkie            ║
║    4,250 zł          ║  ║   18,900 zł          ║
╚══════════════════════╝  ╚══════════════════════╝
```

### Response Time Chart
- Average response time over last 30 days
- Target: < 15 min

### Lead Volume Chart
- Leads received/day over last 30 days
- Accepted vs rejected

---

## ⚙️ Settings (/panel/ustawienia)

### Service Coverage

**Miasta (multi-select):**
```
☑️ Kraków
☑️ Warszawa
☐ Wrocław
☐ Katowice
☑️ Poznań
☐ Gdańsk
```

**Usługi (checkboxes):**
```
☑️ Diagnostyka
☑️ Hamulce
☑️ Wymiana oleju
☑️ Naprawa silnika
☐ Lakiernictwo
☐ Blacharstwo
```

### Operating Hours
```
Poniedziałek:  08:00 - 18:00  [✓ Aktywny]
Wtorek:        08:00 - 18:00  [✓ Aktywny]
Środa:         08:00 - 18:00  [✓ Aktywny]
Czwartek:      08:00 - 18:00  [✓ Aktywny]
Piątek:        08:00 - 18:00  [✓ Aktywny]
Sobota:        09:00 - 14:00  [✓ Aktywny]
Niedziela:     ───────────    [✗ Zamknięte]
```

### Capacity Limits
```
Max leadów dziennie: [10 ▼]
```

### Premium Features

**Auto-Accept:**
```
🔘 Auto-accept dla leadów EXCLUSIVE
   └─ Automatycznie akceptuj leady warte > 100 zł
   └─ Wymaga: PREMIUM+ tier
```

**Priority Queue:**
```
🔘 Priorytet w kolejce
   └─ Otrzymuj leady pierwsze
   └─ Wymaga: PREMIUM tier
```

### Notifications

**Email:**
```
☑️ Nowy lead
☑️ Lead zaakceptowany
☑️ Niskie saldo
```

**Browser Push:**
```
☑️ Włącz powiadomienia push
```

**SMS (PRO):**
```
☐ Włącz SMS (5 zł/miesiąc)
```

---

## 🔔 Real-Time System

### Technology Options

**Option 1: WebSocket (Recommended)**
- Socket.io or Pusher
- Real-time push for new leads
- Instant UI updates
- Connection status indicator

**Option 2: Polling**
- AJAX every 30 seconds
- Simpler implementation
- Higher server load
- Acceptable for MVP

**Option 3: Server-Sent Events (SSE)**
- Native browser support
- One-way communication
- Good for read-only updates

### Real-Time Events

**Lead Events:**
- `lead.new` - New lead assigned
- `lead.updated` - Lead status changed
- `lead.expired` - Lead expired (24h)

**Balance Events:**
- `balance.updated` - Balance changed
- `balance.low` - Balance below threshold

**Notifications:**
- Browser push (Web Push API)
- SMS (Twilio integration)
- Email (transactional)

---

## 🔌 REST API Endpoints

### Authentication
```
POST /api/panel/auth/login
POST /api/panel/auth/logout
GET  /api/panel/auth/user
```

### Leads
```
GET    /api/panel/leads
       ?status=new,pending,accepted,rejected
       &sort=newest,value,distance
       &page=1&per_page=20

GET    /api/panel/lead/{id}

POST   /api/panel/lead/{id}/accept
       Body: { confirm: true }

POST   /api/panel/lead/{id}/reject
       Body: { reason: "No capacity" }
```

### Balance
```
GET    /api/panel/balance

POST   /api/panel/topup
       Body: { amount: 200 }

GET    /api/panel/transactions
       ?page=1&per_page=50
```

### Statistics
```
GET    /api/panel/stats
       ?period=today,week,month,all
```

### Settings
```
GET    /api/panel/settings

PUT    /api/panel/settings
       Body: {
         cities: ['krakow', 'warszawa'],
         services: ['diagnostyka', 'hamulce'],
         hours: {...},
         capacity: 10
       }
```

---

## 💾 Database Schema

### Table: `wp_pt24_workshops`
```sql
CREATE TABLE wp_pt24_workshops (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    services TEXT, -- JSON array
    cities TEXT, -- JSON array
    premium_tier VARCHAR(20) DEFAULT 'free',
    balance DECIMAL(10,2) DEFAULT 0,
    capacity_per_day INT DEFAULT 10,
    auto_accept BOOLEAN DEFAULT false,
    active BOOLEAN DEFAULT true,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_user (user_id),
    INDEX idx_city (city),
    INDEX idx_active (active)
);
```

### Table: `wp_pt24_leads` (already exists, extend)
```sql
ALTER TABLE wp_pt24_leads
ADD COLUMN assigned_to TEXT, -- JSON array of workshop IDs
ADD COLUMN lead_type VARCHAR(20) DEFAULT 'standard',
ADD COLUMN price DECIMAL(10,2),
ADD COLUMN expires_at DATETIME,
ADD INDEX idx_assigned (assigned_to(100)),
ADD INDEX idx_expires (expires_at);
```

### Table: `wp_pt24_lead_assignments`
```sql
CREATE TABLE wp_pt24_lead_assignments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    lead_id BIGINT NOT NULL,
    workshop_id BIGINT NOT NULL,
    assigned_at DATETIME NOT NULL,
    responded_at DATETIME,
    status VARCHAR(20) DEFAULT 'pending',
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (lead_id) REFERENCES wp_pt24_leads(id),
    FOREIGN KEY (workshop_id) REFERENCES wp_pt24_workshops(id),
    INDEX idx_workshop (workshop_id),
    INDEX idx_lead (lead_id),
    INDEX idx_status (status)
);
```

### Table: `wp_pt24_transactions`
```sql
CREATE TABLE wp_pt24_transactions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    workshop_id BIGINT NOT NULL,
    lead_id BIGINT,
    type VARCHAR(20) NOT NULL, -- 'charge', 'credit', 'topup'
    amount DECIMAL(10,2) NOT NULL,
    balance_after DECIMAL(10,2) NOT NULL,
    description TEXT,
    status VARCHAR(20) DEFAULT 'completed',
    created_at DATETIME NOT NULL,
    FOREIGN KEY (workshop_id) REFERENCES wp_pt24_workshops(id),
    INDEX idx_workshop (workshop_id),
    INDEX idx_created (created_at)
);
```

### Table: `wp_pt24_settings`
```sql
CREATE TABLE wp_pt24_settings (
    workshop_id BIGINT PRIMARY KEY,
    settings TEXT, -- JSON blob
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (workshop_id) REFERENCES wp_pt24_workshops(id)
);
```

---

## 🔧 Technical Implementation

### Phase 1: WordPress Backend (Week 1-2)

**Files to Create:**
1. `inc/pt24-workshop-cpt.php` - Workshop CPT
2. `inc/pt24-panel-api.php` - REST API endpoints
3. `inc/pt24-lead-routing.php` - Lead assignment logic
4. `inc/pt24-billing.php` - Balance & transactions
5. `inc/pt24-notifications.php` - Email/SMS/Push

**Database:**
- Create all tables
- Add indexes
- Migration scripts

**Authentication:**
- WordPress user roles
- JWT tokens for API
- Session management

### Phase 2: Frontend (Week 3-4)

**Option A: WordPress Template**
- PHP templates with vanilla JS
- Uses existing theme
- Faster to deploy

**Option B: React/Next.js SPA (Recommended)**
- Modern UI/UX
- Better performance
- Reusable components
- TypeScript for type safety

**File Structure (React):**
```
panel/
├── pages/
│   ├── index.tsx              # Dashboard
│   ├── leady.tsx              # Lead list
│   ├── lead/[id].tsx          # Lead detail
│   ├── saldo.tsx              # Balance
│   ├── statystyki.tsx         # Statistics
│   └── ustawienia.tsx         # Settings
├── components/
│   ├── Layout.tsx
│   ├── Sidebar.tsx
│   ├── LeadCard.tsx
│   ├── StatusBadge.tsx
│   └── BalanceWidget.tsx
├── hooks/
│   ├── useLeads.ts
│   ├── useBalance.ts
│   └── useRealtime.ts
├── lib/
│   ├── api.ts
│   └── websocket.ts
└── styles/
    └── panel.css
```

### Phase 3: Real-Time (Week 5)

**WebSocket Server:**
- Node.js + Socket.io
- Or Pusher (hosted solution)
- Event broadcasting
- Connection management

**Integration:**
- PHP triggers WebSocket events
- Frontend listens for updates
- Automatic UI refresh

### Phase 4: Mobile App (Future)

**React Native:**
- iOS + Android
- Push notifications
- Offline support
- Quick actions

---

## 🎯 MVP Features (Minimum Viable Product)

### Must Have:
- ✅ Dashboard with KPIs
- ✅ Lead list with filters
- ✅ Lead detail with accept/reject
- ✅ Balance display
- ✅ Transaction history
- ✅ Basic settings (cities, services)
- ✅ Email notifications

### Nice to Have:
- Statistics charts
- Real-time push
- SMS notifications
- Auto-accept
- Mobile app

### Future:
- Team management
- CRM integration
- Advanced analytics
- White-label options

---

## 💰 Billing Flow

### Scenario 1: Lead Accepted
```
1. Workshop clicks "Akceptuj lead"
2. System checks balance >= price
3. If yes:
   - Deduct balance
   - Create transaction record
   - Update lead status to 'accepted'
   - Reveal contact information
   - Send notification to workshop
4. If no:
   - Show "Insufficient balance" error
   - Prompt to top up
```

### Scenario 2: Lead Rejected
```
1. Workshop clicks "Odrzuć lead"
2. Lead marked as 'rejected'
3. No charge
4. Lead offered to next workshop in queue
```

### Scenario 3: No Response (24h)
```
1. Lead expires after 24h
2. Automatic charge (configurable)
3. Or: Lead marked as expired (no charge)
4. Lead offered to next workshop
```

---

## 🚀 Launch Plan

### Week 1-2: Backend Foundation
- Database tables
- Workshop CPT
- API endpoints (CRUD)
- Authentication

### Week 3-4: Core UI
- Dashboard
- Lead list
- Lead detail
- Balance page

### Week 5: Polish & Test
- Real-time updates
- Notifications
- Mobile responsive
- Bug fixes

### Week 6: Beta Launch
- Onboard 5-10 workshops
- Collect feedback
- Iterate

### Week 7-8: Production
- Scale infrastructure
- Monitor performance
- Add remaining features

---

## 📊 Success Metrics

**Workshop Engagement:**
- Daily active users
- Response time < 15 min
- Accept rate > 60%

**Platform Health:**
- Lead fill rate > 80%
- Average leads/workshop > 5/day
- Revenue/workshop > 500 zł/month

**Technical:**
- Page load < 2s
- API response < 200ms
- 99.9% uptime

---

## 🔐 Security Considerations

1. **Authentication:** JWT tokens with refresh
2. **Authorization:** Role-based access control
3. **Rate Limiting:** Prevent API abuse
4. **Input Validation:** Sanitize all inputs
5. **SQL Injection:** Prepared statements only
6. **XSS Protection:** Escape all outputs
7. **CSRF Tokens:** For state-changing operations
8. **Encryption:** Sensitive data at rest
9. **Audit Log:** Track all transactions
10. **2FA:** Optional for high-value accounts

---

## 💡 Next Immediate Steps

1. **Create Workshop CPT** (`inc/pt24-workshop-cpt.php`)
2. **Design Database Schema** (create tables)
3. **Build Basic API** (`inc/pt24-panel-api.php`)
4. **Create Dashboard Template** (WordPress or React)
5. **Implement Lead Assignment Logic**

---

## 📝 Conclusion

This is a **full SaaS product** that transforms PT24 from a simple lead generator into a two-sided marketplace:
- **Side 1:** Users searching for services (already built)
- **Side 2:** Workshops managing leads (this panel)

**Estimated Development Time:** 6-8 weeks for MVP
**Expected Impact:** 10x increase in lead monetization potential

This panel is the **key to monetization** - it enables the billing system that makes the entire PT24 platform profitable.

---

**Status:** 📋 PLAN COMPLETE
**Next Action:** Begin backend implementation with Workshop CPT


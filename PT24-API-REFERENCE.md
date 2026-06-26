# PT24 REST API Documentation

**Base URL**: `https://pt24.pro/wp-json/pt24/v1`  
**Authentication**: Optional (some endpoints require API key)  
**Version**: 1.0  
**Last Updated**: 2026-06-26

---

## Quick Start

### Get Businesses List
```bash
curl -X GET "https://pt24.pro/wp-json/pt24/v1/businesses" \
  -H "Content-Type: application/json"
```

### Submit a Lead
```bash
curl -X POST "https://pt24.pro/wp-json/pt24/v1/leads/submit" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Anna Nowak",
    "email": "anna@example.com",
    "phone": "+48123456789",
    "service": "hydraulik",
    "city": "warszawa",
    "description": "Przecieka kran w łazience"
  }'
```

---

## Endpoints

### 1. GET /businesses
List all businesses (firms) on PT24

**Query Parameters**:
| Param | Type | Default | Description |
|-------|------|---------|-------------|
| `page` | int | 1 | Pagination page |
| `per_page` | int | 20 | Results per page (max: 100) |
| `city` | string | - | Filter by city slug (e.g., `warszawa`, `krakow`) |
| `service` | string | - | Filter by service slug (e.g., `hydraulik`, `elektryk`) |
| `rating_min` | float | - | Minimum rating (0-5) |
| `sort` | string | `rating` | Sort by: `rating`, `jobs`, `name` |

**Response Example**:
```json
{
  "businesses": [
    {
      "id": 123,
      "name": "ProTeam Hydraulika",
      "slug": "proteam-hydraulika",
      "description": "Profesjonalne usługi hydrauliczne w Warszawie",
      "city": "warszawa",
      "city_name": "Warszawa",
      "service": "hydraulik",
      "service_name": "Hydraulik",
      "rating": 4.8,
      "jobs_completed": 156,
      "phone": "+48123456789",
      "email": "info@proteam.pl",
      "website": "https://proteam.pl",
      "url": "https://pt24.pro/firma/proteam-hydraulika/"
    }
  ],
  "pagination": {
    "total": 450,
    "pages": 23,
    "current_page": 1,
    "per_page": 20
  }
}
```

**Status Codes**:
- `200 OK` - Success
- `400 Bad Request` - Invalid parameters
- `404 Not Found` - No results

**Examples**:
```bash
# Get businesses in Warsaw
curl "https://pt24.pro/wp-json/pt24/v1/businesses?city=warszawa"

# Get plumbers with rating 4+ sorted by rating
curl "https://pt24.pro/wp-json/pt24/v1/businesses?service=hydraulik&rating_min=4&sort=rating"

# Paginate: 50 results per page
curl "https://pt24.pro/wp-json/pt24/v1/businesses?page=2&per_page=50"
```

---

### 2. GET /businesses/{id}
Get details for a specific business

**Parameters**:
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | int | Yes | Business post ID |

**Response Example**:
```json
{
  "id": 123,
  "name": "ProTeam Hydraulika",
  "slug": "proteam-hydraulika",
  "description": "Profesjonalne usługi hydrauliczne...",
  "excerpt": "Szybko, tanio, niezawodnie",
  "city": "warszawa",
  "city_name": "Warszawa",
  "service": "hydraulik",
  "service_name": "Hydraulik",
  "rating": 4.8,
  "jobs_completed": 156,
  "phone": "+48123456789",
  "email": "info@proteam.pl",
  "website": "https://proteam.pl",
  "featured_image": "https://pt24.pro/wp-content/uploads/2026/06/proteam.jpg",
  "url": "https://pt24.pro/firma/proteam-hydraulika/",
  "meta": {
    "years_in_business": 8,
    "team_size": 5,
    "service_radius_km": 15
  }
}
```

**Status Codes**:
- `200 OK` - Success
- `404 Not Found` - Business not found

**Examples**:
```bash
# Get business with ID 123
curl "https://pt24.pro/wp-json/pt24/v1/businesses/123"

# Parse and display rating
curl -s "https://pt24.pro/wp-json/pt24/v1/businesses/123" | jq '.rating'
```

---

### 3. GET /leads
List leads (requires authentication)

**Query Parameters**:
| Param | Type | Description |
|-------|------|-------------|
| `page` | int | Pagination page |
| `per_page` | int | Results per page (max: 50) |
| `status` | string | Filter: `pending`, `contacted`, `completed`, `rejected` |
| `business_id` | int | Filter by business ID |

**Response Example**:
```json
{
  "leads": [
    {
      "id": 456,
      "name": "Anna Nowak",
      "email": "anna@example.com",
      "phone": "+48123456789",
      "service": "hydraulik",
      "service_name": "Hydraulik",
      "city": "warszawa",
      "city_name": "Warszawa",
      "description": "Przecieka kran w kuchni",
      "status": "pending",
      "priority": "high",
      "created_at": "2026-06-26T10:30:00Z",
      "updated_at": "2026-06-26T11:00:00Z"
    }
  ],
  "pagination": {
    "total": 125,
    "pages": 3,
    "current_page": 1,
    "per_page": 50
  }
}
```

**Authentication**:
```bash
# Add API key header
curl -H "X-PT24-API-Key: YOUR_API_KEY" \
  "https://pt24.pro/wp-json/pt24/v1/leads"

# Or use Bearer token
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "https://pt24.pro/wp-json/pt24/v1/leads"
```

**Status Codes**:
- `200 OK` - Success
- `401 Unauthorized` - Missing/invalid authentication
- `403 Forbidden` - Insufficient permissions

---

### 4. POST /leads/submit
Submit a new lead (public endpoint, no auth required)

**Request Body**:
```json
{
  "name": "Anna Nowak",
  "email": "anna@example.com",
  "phone": "+48123456789",
  "service": "hydraulik",
  "city": "warszawa",
  "description": "Przecieka kran w łazience",
  "urgency": "medium",
  "preferred_contact": "email"
}
```

**Request Parameters**:
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | Yes | Customer name (2-100 chars) |
| `email` | string | Yes | Valid email address |
| `phone` | string | Yes | Phone number |
| `service` | string | Yes | Service slug (e.g., `hydraulik`) |
| `city` | string | Yes | City slug (e.g., `warszawa`) |
| `description` | string | No | Detailed description (0-1000 chars) |
| `urgency` | string | No | `low`, `medium`, `high` (default: `medium`) |
| `preferred_contact` | string | No | `email`, `phone` (default: `email`) |

**Response Example**:
```json
{
  "id": 457,
  "status": "pending",
  "message": "Zapytanie zostało wysłane do firm",
  "notification_email": "anna@example.com",
  "estimated_responses": "2-4 godziny",
  "tracking_url": "https://pt24.pro/track/457/?token=abc123"
}
```

**Validation Rules**:
- `name`: 2-100 characters, required
- `email`: Valid email format, required
- `phone`: Valid Polish phone (+48 or 0), required
- `service`: Must exist in PT24 service list
- `city`: Must exist in PT24 city list
- `description`: Max 1000 characters
- Rate limit: 5 submissions per IP per hour

**Response Status Codes**:
- `201 Created` - Lead successfully created
- `400 Bad Request` - Validation error (see error details)
- `429 Too Many Requests` - Rate limit exceeded
- `500 Internal Server Error` - Server error

**Validation Error Example**:
```json
{
  "code": "VALIDATION_ERROR",
  "message": "Validation failed",
  "errors": {
    "email": "Invalid email format",
    "phone": "Phone number must be 9-12 digits"
  }
}
```

**Examples**:
```bash
# Simple submission
curl -X POST "https://pt24.pro/wp-json/pt24/v1/leads/submit" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Anna Nowak",
    "email": "anna@example.com",
    "phone": "+48123456789",
    "service": "hydraulik",
    "city": "warszawa"
  }'

# With all optional fields
curl -X POST "https://pt24.pro/wp-json/pt24/v1/leads/submit" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Anna Nowak",
    "email": "anna@example.com",
    "phone": "+48123456789",
    "service": "hydraulik",
    "city": "warszawa",
    "description": "Przecieka kran w łazience, pilne!",
    "urgency": "high",
    "preferred_contact": "phone"
  }'
```

---

### 5. GET /stats/{business_id}
Get statistics for a specific business

**Parameters**:
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| `business_id` | int | Yes | Business post ID |

**Response Example**:
```json
{
  "business_id": 123,
  "business_name": "ProTeam Hydraulika",
  "total_leads_received": 156,
  "leads_this_month": 12,
  "leads_this_week": 3,
  "leads_today": 1,
  "conversion_rate": 0.65,
  "avg_response_time_hours": 2.5,
  "completion_rate": 0.78,
  "customer_satisfaction": 4.8,
  "rating_count": 45,
  "revenue_estimate_pln": 45000,
  "period": "last_30_days"
}
```

**Query Parameters**:
| Param | Type | Default | Description |
|-------|------|---------|-------------|
| `period` | string | `last_30_days` | `today`, `this_week`, `this_month`, `last_30_days`, `last_90_days`, `all_time` |

**Status Codes**:
- `200 OK` - Success
- `404 Not Found` - Business not found

**Examples**:
```bash
# Get current month stats
curl "https://pt24.pro/wp-json/pt24/v1/stats/123?period=this_month"

# Get all-time stats
curl "https://pt24.pro/wp-json/pt24/v1/stats/123?period=all_time"
```

---

## Error Handling

All errors follow this format:
```json
{
  "code": "ERROR_CODE",
  "message": "Human-readable message",
  "details": {}
}
```

**Common Error Codes**:
| Code | Status | Description |
|------|--------|-------------|
| `INVALID_REQUEST` | 400 | Malformed request |
| `INVALID_PARAMETERS` | 400 | Invalid query parameters |
| `VALIDATION_ERROR` | 400 | Request validation failed |
| `UNAUTHORIZED` | 401 | Authentication required |
| `FORBIDDEN` | 403 | Insufficient permissions |
| `NOT_FOUND` | 404 | Resource not found |
| `RATE_LIMIT_EXCEEDED` | 429 | Too many requests |
| `INTERNAL_ERROR` | 500 | Server error |

---

## Rate Limiting

- **Public endpoints** (leads/submit, businesses, stats): 100 req/min per IP
- **Authenticated endpoints** (leads): 1000 req/min per API key

**Rate limit headers**:
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1624716000
```

---

## Pagination

For endpoints returning lists, use standard pagination:

```json
{
  "data": [...],
  "pagination": {
    "total": 450,
    "pages": 23,
    "current_page": 2,
    "per_page": 20
  }
}
```

**Pagination example**:
```bash
# Get page 2, 50 items per page
curl "https://pt24.pro/wp-json/pt24/v1/businesses?page=2&per_page=50"
```

---

## CORS

PT24 API supports CORS with the following headers:
```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
Access-Control-Max-Age: 86400
```

---

## Webhook Events

Webhooks available (if configured):
- `lead.submitted` - New lead submitted
- `lead.assigned` - Lead assigned to business
- `lead.completed` - Lead marked as completed
- `business.updated` - Business profile updated

**Example webhook payload**:
```json
{
  "event": "lead.submitted",
  "id": "evt_123abc",
  "created_at": "2026-06-26T10:30:00Z",
  "data": {
    "lead_id": 457,
    "name": "Anna Nowak",
    "email": "anna@example.com",
    "service": "hydraulik",
    "city": "warszawa"
  }
}
```

---

## SDKs & Libraries

### JavaScript/Node.js
```javascript
// npm install pt24-sdk
const PT24 = require('pt24-sdk');
const client = new PT24.Client({ apiKey: 'YOUR_API_KEY' });

// Get businesses
const businesses = await client.businesses.list({ city: 'warszawa' });

// Submit lead
const lead = await client.leads.submit({
  name: 'Anna Nowak',
  email: 'anna@example.com',
  phone: '+48123456789',
  service: 'hydraulik',
  city: 'warszawa'
});
```

### Python
```python
# pip install pt24-sdk
from pt24 import PT24Client

client = PT24Client(api_key='YOUR_API_KEY')

# Get businesses
businesses = client.businesses.list(city='warszawa')

# Submit lead
lead = client.leads.submit(
    name='Anna Nowak',
    email='anna@example.com',
    phone='+48123456789',
    service='hydraulik',
    city='warszawa'
)
```

### PHP
```php
// composer require pt24/sdk
use PT24\Client;

$client = new Client(['apiKey' => 'YOUR_API_KEY']);

// Get businesses
$businesses = $client->businesses->list(['city' => 'warszawa']);

// Submit lead
$lead = $client->leads->submit([
    'name' => 'Anna Nowak',
    'email' => 'anna@example.com',
    'phone' => '+48123456789',
    'service' => 'hydraulik',
    'city' => 'warszawa'
]);
```

---

## Testing

### Postman Collection
Import this collection into Postman to test all endpoints:
```json
{
  "info": {
    "name": "PT24 API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Businesses",
      "item": [
        {
          "name": "List Businesses",
          "request": {
            "method": "GET",
            "url": "https://pt24.pro/wp-json/pt24/v1/businesses"
          }
        }
      ]
    }
  ]
}
```

### cURL Testing
```bash
# Test all endpoints
./test-api.sh

# See test-api.sh for full test suite
```

---

## Support

- **Documentation**: [PT24 Developer Docs](https://pt24.pro/developers/)
- **Issues**: GitHub Issues
- **Email**: api-support@pt24.pro
- **Status Page**: [PT24 Status](https://status.pt24.pro)

---

**API Version**: 1.0  
**Last Updated**: 2026-06-26  
**Status**: Production Ready ✅

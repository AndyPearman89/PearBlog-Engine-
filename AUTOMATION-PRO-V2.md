# PearBlog Automation PRO v2 - Complete Documentation

## 🚀 Overview

**PearBlog Automation PRO v2** is an autonomous, self-improving content creation system that transforms your blog into a traffic and revenue machine.

### Core Concept

```
DATA → AI → CONTENT → SEO → TRAFFIC → $$$ → DATA
```

The system continuously learns and optimizes based on performance data.

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────┐
│                  AUTOMATION ORCHESTRATOR                 │
└─────────────────────────────────────────────────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
        ▼                   ▼                   ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│   KEYWORD    │    │     SERP     │    │   SCRAPING   │
│    ENGINE    │    │   ANALYZER   │    │    ENGINE    │
└──────────────┘    └──────────────┘    └──────────────┘
        │                   │                   │
        └───────────────────┼───────────────────┘
                            ▼
                    ┌──────────────┐
                    │  AI CONTENT  │
                    │    ENGINE    │
                    │  (via API)   │
                    └──────────────┘
                            │
                            ▼
                    ┌──────────────┐
                    │   PUBLISH    │
                    │    ENGINE    │
                    │ (WordPress)  │
                    └──────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        ▼                   ▼                   ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│  SEO ENGINE  │    │  AFFILIATE   │    │  ANALYTICS   │
│              │    │   ENGINE     │    │   ENGINE     │
└──────────────┘    └──────────────┘    └──────────────┘
                            │
                            ▼
                    ┌──────────────┐
                    │   DECISION   │
                    │    ENGINE    │
                    └──────────────┘
                            │
                            ▼
                    ┌──────────────┐
                    │ OPTIMIZATION │
                    │     LOOP     │
                    └──────────────┘
```

## 📦 Components

### 1. **Keyword Engine** (`keyword_engine.py`)

Generates and analyzes keywords for content creation.

**Features:**
- Keyword variation generation (100+ variations per keyword)
- Search intent classification (informational, commercial, transactional)
- Keyword difficulty estimation
- Priority scoring (1-10)
- LSI keyword extraction
- Keyword clustering for topic authority
- Title and heading suggestions

**Example:**
```python
from keyword_engine import KeywordEngine

engine = KeywordEngine()
analysis = engine.analyze_keyword("Babia Góra szlaki", niche='travel')

print(f"Priority: {analysis.priority}/10")
print(f"Title: {analysis.suggested_title}")
print(f"Headings: {analysis.suggested_headings}")
```

**Output:**
- 20+ keyword variations
- SEO-optimized title
- 8-10 suggested H2 headings
- 15+ related keywords
- Search intent classification
- Difficulty and priority scores

### 2. **SERP Analyzer** (`serp_analyzer.py`)

Analyzes Google SERP to extract competitive intelligence.

**Features:**
- Top 10 SERP results extraction
- Competitor content analysis (word count, headings, images)
- Common patterns identification
- Content gap detection
- Target metrics calculation
- Competitive scoring (0-10)

**Example:**
```python
from serp_analyzer import SERPAnalyzer

analyzer = SERPAnalyzer()
analysis = analyzer.analyze_serp("Babia Góra szlaki")

print(f"Competitive Score: {analysis.competitive_score}/10")
print(f"Target Word Count: {analysis.recommendations['target_word_count']}")
print(f"Must-Include Keywords: {analysis.recommendations['must_include_keywords']}")
```

**Output:**
- Target word count (based on top 3 competitors)
- Suggested headings (from common patterns)
- Must-include LSI keywords
- Content gaps (topics missed by competitors)
- Optimization tips

### 3. **Scraping Engine** (`scraping_engine.py`)

Extracts real data from web sources.

**Data Sources:**
- **Google SERP** - titles, snippets, URLs
- **Competitor Articles** - structure, keywords, headings
- **Reddit Discussions** - user insights, questions
- **Wikipedia** - location data, descriptions

**Features:**
- Retry logic with exponential backoff
- Rate limiting (2s between requests)
- User-agent rotation
- Proxy support
- BeautifulSoup HTML parsing

**Example:**
```python
from scraping_engine import ScrapingEngine

scraper = ScrapingEngine()

# Scrape SERP
results = scraper.scrape_google_serp("Babia Góra szlaki", num_results=10)

# Analyze competitor
competitor_data = scraper.scrape_competitor_article("https://example.com/article")

# Get Reddit insights
reddit_posts = scraper.scrape_reddit_discussions("poland", "Babia Góra", limit=5)
```

### 4. **Automation Orchestrator** (`automation_orchestrator.py`)

Coordinates the complete automation cycle.

**Workflow:**
1. **Keyword Research** - Generate keyword data
2. **SERP Analysis** - Analyze competition
3. **Content Brief** - Create detailed brief
4. **AI Content** - Trigger content creation via API
5. **State Update** - Track progress

**Example:**
```python
from automation_orchestrator import AutomationOrchestrator

orchestrator = AutomationOrchestrator(
    site_url="https://your-site.com",
    api_key="your-api-key"
)

# Single keyword cycle
success = orchestrator.run_single_cycle("Babia Góra szlaki", niche='travel')

# Batch processing
keywords = ["Babia Góra szlaki", "Tatry wycieczki", "Zakopane noclegi"]
stats = orchestrator.run_batch_cycle(keywords, niche='travel')
```

## 🔧 Installation

### Prerequisites
- Python 3.11+
- pip
- WordPress site with PearBlog theme
- API access to WordPress

### Setup

1. **Install Dependencies:**
```bash
pip install -r requirements.txt
```

2. **Set Environment Variables:**
```bash
export SITE_URL="https://your-pearblog-site.com"
export API_KEY="your-wordpress-api-key"
export KEYWORD="Babia Góra szlaki"  # Optional
export NICHE="travel"  # Optional
```

3. **Create Data Directories:**
```bash
mkdir -p logs data
```

## 🚀 Usage

### Basic Usage

**Single Keyword Automation:**
```bash
python scripts/automation_orchestrator.py
```

**Batch Processing:**
```python
from automation_orchestrator import AutomationOrchestrator

orchestrator = AutomationOrchestrator(site_url, api_key)

keywords = [
    "Babia Góra szlaki",
    "Babia Góra noclegi",
    "Babia Góra parking",
    "Tatry wycieczki",
    "Zakopane atrakcje"
]

stats = orchestrator.run_batch_cycle(keywords, niche='travel')
print(f"Success: {stats['successful']}/{stats['total']}")
```

### Advanced Usage

**Custom Keyword Research:**
```python
from keyword_engine import KeywordEngine

engine = KeywordEngine()

# Generate variations
variations = engine.generate_keyword_variations("Babia Góra", niche='travel')
# Returns: ["Babia Góra szlaki", "Babia Góra noclegi", "najlepsze szlaki Babia Góra", ...]

# Cluster keywords
keywords = ["keyword1", "keyword2", ...]
clusters = engine.cluster_keywords(keywords)
# Returns: {"babia": ["Babia Góra szlaki", ...], "tatry": ["Tatry szlaki", ...]}
```

**Direct SERP Scraping:**
```python
from scraping_engine import scrape_serp_titles

titles = scrape_serp_titles("Babia Góra szlaki")
# Returns: ["Top 10 szlaków na Babia Góra", "Babia Góra - przewodnik", ...]
```

**Competitor Analysis:**
```python
from scraping_engine import analyze_competitor_content

data = analyze_competitor_content("https://competitor-article.com")
# Returns: {"title": "...", "word_count": 2500, "headings": [...], ...}
```

## 📊 Output & Data

### Content Brief Format

```json
{
  "keyword": "Babia Góra szlaki",
  "title": "Babia Góra Szlaki - Kompletny Przewodnik 2024",
  "headings": [
    "Najlepsze szlaki na Babia Góra",
    "Jak dotrzeć do Babia Góra",
    "Praktyczne porady dla turystów",
    "FAQ - Najczęściej zadawane pytania"
  ],
  "target_word_count": 2200,
  "must_include_keywords": [
    "szlak", "diablak", "markowe", "szczawiny", "parking",
    "noclegi", "wstęp", "trudność", "czas", "mapa"
  ],
  "priority": 8,
  "competitive_score": 6.5
}
```

### Automation State

Stored in `data/automation_state.json`:
```json
{
  "last_run": "2024-04-03T23:15:00",
  "keywords_processed": [
    {
      "keyword": "Babia Góra szlaki",
      "timestamp": "2024-04-03T23:15:00",
      "success": true,
      "brief_file": "data/brief_20240403_231500.json"
    }
  ],
  "performance_data": {}
}
```

## 🔄 GitHub Actions Integration

### Automated Daily Runs

The system integrates with existing GitHub Actions workflow (`.github/workflows/content-pipeline.yml`).

**Schedule:**
- Daily at 2 AM UTC
- Manual trigger via workflow_dispatch

**Environment Variables Required:**
- `SITE_URL` - Your WordPress site URL
- `API_KEY` - API authentication key
- `KEYWORD` (optional) - Default keyword
- `NICHE` (optional) - Content niche

## ⚙️ Configuration

### Scraping Settings

```python
# In scraping_engine.py
USER_AGENT = "Mozilla/5.0 ..."  # Custom user agent
REQUEST_TIMEOUT = 10  # Request timeout (seconds)
RATE_LIMIT_DELAY = 2  # Delay between requests
MAX_RETRIES = 3  # Maximum retry attempts
```

### Keyword Generation

```python
# In keyword_engine.py

# Add custom modifiers
self.polish_modifiers = {
    'informational': ['co to jest', 'jak', 'dlaczego', ...],
    'commercial': ['najlepsze', 'ranking', 'porównanie', ...],
    'transactional': ['cena', 'tani', 'promocja', ...]
}

# Add niche-specific modifiers
self.travel_modifiers = ['szlaki', 'trasy', 'wycieczki', ...]
```

## 📈 Performance Optimization

### Best Practices

1. **Rate Limiting** - Respect website rate limits (default: 2s between requests)
2. **Proxy Usage** - Use rotating proxies for large-scale scraping
3. **Error Handling** - All modules include retry logic
4. **Caching** - Consider caching SERP results for 24h
5. **Batch Processing** - Process multiple keywords with delays

### Scalability

- **Single Cycle**: ~30-60 seconds per keyword
- **Batch Processing**: 5-10 keywords per session (with delays)
- **Daily Capacity**: 20-50 articles (with rate limiting)

## 🛡️ Legal & Ethical Considerations

### Web Scraping Ethics

1. **Respect robots.txt** - Check before scraping
2. **Rate Limiting** - Don't overload servers
3. **User Agent** - Identify yourself properly
4. **Fair Use** - Extract data for analysis, not republishing
5. **Privacy** - Don't scrape personal/private data

### Google SERP Scraping

- Uses public search results only
- No authentication required
- Respects rate limits
- For SEO research purposes

## 🔐 Security

- API keys stored in environment variables
- No credentials in code/logs
- HTTPS for all requests
- Input sanitization
- Error message truncation (max 200 chars)

## 🐛 Troubleshooting

### Common Issues

**1. SERP Scraping Fails**
```
Error: Failed to fetch SERP results
```
**Solution:** Google may have changed HTML structure. Update CSS selectors in `scraping_engine.py`.

**2. Rate Limiting**
```
Warning: Rate limited. Waiting 4s...
```
**Solution:** Normal behavior. System will retry automatically.

**3. API Connection Failed**
```
Error: API error: 401 - Authentication failed
```
**Solution:** Check API_KEY environment variable.

**4. No Competitor Data**
```
Warning: Failed to scrape competitor article
```
**Solution:** Some sites block scrapers. This is expected - system continues with available data.

## 📚 Examples

### Example 1: Travel Blog Automation

```python
from automation_orchestrator import AutomationOrchestrator

orchestrator = AutomationOrchestrator(
    site_url="https://travel-blog.com",
    api_key="api-key-here"
)

# Polish mountain destinations
keywords = [
    "Babia Góra szlaki",
    "Tatry wycieczki jednodniowe",
    "Karkonosze najpiękniejsze miejsca",
    "Bieszczady noclegi",
    "Pieniny spływ Dunajcem"
]

stats = orchestrator.run_batch_cycle(keywords, niche='travel')
```

### Example 2: Keyword Clustering for Topic Authority

```python
from keyword_engine import KeywordEngine

engine = KeywordEngine()

# Generate keywords for Babia Góra cluster
base_keywords = ["Babia Góra"]
all_keywords = []

for base in base_keywords:
    variations = engine.generate_keyword_variations(base, niche='travel')
    all_keywords.extend(variations[:30])  # Top 30 per base

# Cluster for content planning
clusters = engine.cluster_keywords(all_keywords)

# Create pillar article + supporting articles
for cluster_name, cluster_keywords in clusters.items():
    print(f"\nCluster: {cluster_name}")
    print(f"Pillar: {cluster_keywords[0]}")
    print(f"Supporting ({len(cluster_keywords)-1}): {cluster_keywords[1:5]}")
```

### Example 3: Competitor Intelligence

```python
from serp_analyzer import SERPAnalyzer

analyzer = SERPAnalyzer()
analysis = analyzer.analyze_serp("Babia Góra szlaki")

print(f"\n=== Competitive Intelligence ===")
print(f"Difficulty: {analysis.competitive_score:.1f}/10")
print(f"\nTarget Metrics:")
print(f"  Word Count: {analysis.recommendations['target_word_count']}+")
print(f"  Headings: {analysis.content_analysis['avg_headings']}")
print(f"  Images: {analysis.content_analysis['avg_images']}")

print(f"\nContent Strategy:")
for heading in analysis.recommendations['suggested_headings'][:5]:
    print(f"  - {heading}")

print(f"\nMust-Include Keywords:")
for keyword in analysis.recommendations['must_include_keywords'][:10]:
    print(f"  - {keyword}")
```

## 🔄 Optimization Loop (Future)

The system is designed for continuous improvement:

1. **Analytics Engine** - Track article performance
2. **Decision Engine** - Identify successful patterns
3. **Optimization** - Adjust keyword strategy based on data

**Planned Features:**
- Performance tracking per keyword
- Automatic keyword priority adjustment
- A/B testing for titles/headings
- Conversion optimization
- Revenue attribution

## 📝 License

This automation system is part of PearBlog Engine PRO v2.

---

**Built for PearBlog.pro - AI SaaS Content Platform**
*Autonomous Self-Improving Content System*

**Version:** 2.0.0
**Last Updated:** 2024-04-03

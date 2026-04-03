# PearBlog Automation PRO v2 - Quick Start Guide

## 🚀 Get Started in 5 Minutes

### Step 1: Install Dependencies

```bash
cd /path/to/PearBlog-Engine-
pip install -r requirements.txt
```

### Step 2: Set Environment Variables

```bash
export SITE_URL="https://your-site.com"
export API_KEY="your-wordpress-api-key"
```

### Step 3: Run Your First Automation

```bash
python scripts/automation_orchestrator.py
```

## 📋 What Just Happened?

The automation system:
1. ✅ Researched your keyword "Babia Góra szlaki"
2. ✅ Generated 20+ keyword variations
3. ✅ Analyzed Google SERP (top 10 results)
4. ✅ Scraped competitor articles for intelligence
5. ✅ Created a content brief with:
   - SEO-optimized title
   - 8-10 suggested headings
   - Target word count
   - Must-include keywords
6. ✅ Triggered AI content creation via API
7. ✅ Saved state for tracking

## 📊 Check Your Output

```bash
# View logs
ls -la logs/

# View content briefs
ls -la data/

# View latest brief
cat data/brief_*.json | tail -1
```

## 🎯 Next Steps

### Customize Your Keywords

Edit `scripts/automation_orchestrator.py`:

```python
# Change the default keyword
keyword = "Your Keyword Here"
niche = "travel"  # or 'tech', 'food', etc.
```

### Run Batch Processing

Create `my_keywords.txt`:
```
Babia Góra szlaki
Tatry wycieczki
Zakopane noclegi
Karkonosze atrakcje
Bieszczady szlaki
```

Then run:
```python
from automation_orchestrator import AutomationOrchestrator

orchestrator = AutomationOrchestrator(site_url, api_key)

with open('my_keywords.txt') as f:
    keywords = [line.strip() for line in f if line.strip()]

stats = orchestrator.run_batch_cycle(keywords, niche='travel')
print(f"Processed: {stats['successful']}/{stats['total']}")
```

### Schedule Daily Automation

GitHub Actions is already configured! Just set secrets:

1. Go to your repo → Settings → Secrets
2. Add:
   - `SITE_URL`: Your WordPress site URL
   - `API_KEY`: Your API key
   - `API_ENDPOINT`: `/wp-json/pearblog/v1/automation/create-content`

The automation runs daily at 2 AM UTC automatically.

## 🔧 Common Tasks

### Test Individual Components

**Keyword Research:**
```bash
python scripts/keyword_engine.py
```

**SERP Analysis:**
```bash
python scripts/serp_analyzer.py
```

**Web Scraping:**
```bash
python scripts/scraping_engine.py
```

### View Analysis Results

All analysis is saved to `data/` folder:

```bash
# List all content briefs
ls -l data/brief_*.json

# View latest brief
jq '.' data/brief_*.json | tail -100
```

### Monitor Automation

```bash
# View latest log
tail -f logs/automation_*.log

# Search for errors
grep -i error logs/automation_*.log

# Count successful runs
grep "COMPLETED SUCCESSFULLY" logs/automation_*.log | wc -l
```

## 💡 Pro Tips

### 1. Start with Long-Tail Keywords
```python
# Good: Specific, easier to rank
"Babia Góra szlaki dla początkujących"
"najlepsze noclegi Zakopane cennik"

# Bad: Too competitive
"góry"
"podróże"
```

### 2. Use Keyword Clustering

Group related keywords for topic authority:
```python
from keyword_engine import KeywordEngine

engine = KeywordEngine()
keywords = ["Babia Góra szlaki", "Babia Góra noclegi", "Babia Góra parking"]
clusters = engine.cluster_keywords(keywords)

# Create 1 pillar + 10-15 supporting articles per cluster
```

### 3. Analyze Before Creating

Always check competitive score:
```python
analysis = analyzer.analyze_serp("your keyword")
if analysis.competitive_score < 7:
    # Good opportunity!
    print("Low competition - proceed")
else:
    # Highly competitive
    print("Consider easier alternatives")
```

### 4. Monitor Rate Limits

The system respects rate limits automatically, but for large batches:
```python
# Add delays between keywords
time.sleep(5)  # 5 seconds between cycles
```

## 🐛 Troubleshooting

### Problem: "No module named 'beautifulsoup4'"
**Solution:**
```bash
pip install beautifulsoup4 lxml
```

### Problem: "SERP scraping failed"
**Solution:** Google changed structure. Update `scraping_engine.py` CSS selectors.

### Problem: "API authentication failed"
**Solution:**
```bash
# Check your API key
echo $API_KEY

# Verify site URL
echo $SITE_URL
```

### Problem: "Rate limited by Google"
**Solution:** Normal. System will retry automatically with delays.

## 📚 Full Documentation

See `AUTOMATION-PRO-V2.md` for complete documentation.

## 🎯 Example Workflows

### Workflow 1: Daily Content Creation

```bash
#!/bin/bash
# daily_automation.sh

export SITE_URL="https://your-site.com"
export API_KEY="your-api-key"

# Run automation for today's keyword
KEYWORD="Babia Góra szlaki $(date +%Y)"
export KEYWORD

python scripts/automation_orchestrator.py
```

### Workflow 2: Cluster Strategy

```python
# cluster_strategy.py
from automation_orchestrator import AutomationOrchestrator
from keyword_engine import KeywordEngine

orchestrator = AutomationOrchestrator(site_url, api_key)
keyword_engine = KeywordEngine()

# Generate cluster
base = "Babia Góra"
variations = keyword_engine.generate_keyword_variations(base, 'travel')

# Process top 15 (1 pillar + 14 supporting)
stats = orchestrator.run_batch_cycle(variations[:15], niche='travel')

print(f"Cluster created: {stats['successful']} articles")
```

### Workflow 3: Competitive Analysis

```python
# competitor_analysis.py
from serp_analyzer import SERPAnalyzer
import json

analyzer = SERPAnalyzer()

keywords = [
    "Babia Góra szlaki",
    "Tatry wycieczki",
    "Zakopane atrakcje"
]

results = []
for keyword in keywords:
    analysis = analyzer.analyze_serp(keyword)
    if analysis:
        results.append({
            "keyword": keyword,
            "difficulty": analysis.competitive_score,
            "target_words": analysis.recommendations['target_word_count'],
            "priority": "HIGH" if analysis.competitive_score < 6 else "MEDIUM"
        })

# Save report
with open('competitive_analysis.json', 'w') as f:
    json.dump(results, f, indent=2)

print(f"Analyzed {len(results)} keywords")
print(f"High priority: {sum(1 for r in results if r['priority'] == 'HIGH')}")
```

## 🎉 You're Ready!

Your automation system is now running. The system will:
- ✅ Research keywords automatically
- ✅ Analyze competition
- ✅ Extract real web data
- ✅ Create SEO-optimized content briefs
- ✅ Trigger content creation
- ✅ Track performance

**Next:** Review `AUTOMATION-PRO-V2.md` for advanced features.

---

**Questions?** Open an issue on GitHub.

**Built with PearBlog Engine PRO v2** 🚀

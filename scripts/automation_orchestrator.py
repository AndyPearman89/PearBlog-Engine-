#!/usr/bin/env python3
"""
PearBlog Automation Orchestrator - Complete Automation Loop

Orchestrates the full content automation cycle:
DATA → AI → CONTENT → SEO → TRAFFIC → $$$ → DATA

Implements the autonomous self-improving content system:
1. Keyword Engine - generates keywords
2. SERP Analyzer - analyzes competition
3. Scraping Engine - extracts web data
4. AI Content Engine - creates content (via API)
5. Publish Engine - publishes to WordPress (via API)
6. SEO Engine - optimizes content
7. Affiliate Engine - adds monetization
8. Analytics Engine - tracks performance
9. Decision Engine - selects next actions
10. Optimization Loop - improves based on data

Part of PearBlog Automation PRO v2
"""

import os
import sys
import json
import logging
import time
from datetime import datetime
from pathlib import Path
from typing import Dict, List, Any, Optional
from dataclasses import dataclass, asdict

from keyword_engine import KeywordEngine, KeywordData
from serp_analyzer import SERPAnalyzer, SERPAnalysis
from scraping_engine import ScrapingEngine
import requests


# Configuration
LOG_DIR = Path("logs")
DATA_DIR = Path("data")
AUTOMATION_STATE_FILE = DATA_DIR / "automation_state.json"


@dataclass
class ContentBrief:
    """Content creation brief."""
    keyword: str
    title: str
    headings: List[str]
    target_word_count: int
    must_include_keywords: List[str]
    serp_data: Dict[str, Any]
    priority: int
    competitive_score: float


class AutomationOrchestrator:
    """Orchestrates the complete automation cycle."""

    def __init__(self, site_url: str, api_key: str):
        """
        Initialize the automation orchestrator.

        Args:
            site_url: WordPress site URL
            api_key: API authentication key
        """
        self.site_url = site_url
        self.api_key = api_key
        self.logger = self._setup_logging()

        # Initialize engines
        self.keyword_engine = KeywordEngine()
        self.serp_analyzer = SERPAnalyzer()
        self.scraper = ScrapingEngine()

        # Create directories
        LOG_DIR.mkdir(exist_ok=True)
        DATA_DIR.mkdir(exist_ok=True)

        self.logger.info("="*60)
        self.logger.info("PearBlog Automation Orchestrator - Initialized")
        self.logger.info("="*60)

    def _setup_logging(self) -> logging.Logger:
        """Setup logging configuration."""
        LOG_DIR.mkdir(exist_ok=True)

        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        log_file = LOG_DIR / f"automation_{timestamp}.log"

        logging.basicConfig(
            level=logging.INFO,
            format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
            handlers=[
                logging.FileHandler(log_file),
                logging.StreamHandler(sys.stdout)
            ]
        )

        return logging.getLogger(__name__)

    def load_automation_state(self) -> Dict[str, Any]:
        """Load automation state from disk."""
        if AUTOMATION_STATE_FILE.exists():
            try:
                with open(AUTOMATION_STATE_FILE, 'r') as f:
                    return json.load(f)
            except Exception as e:
                self.logger.warning(f"Could not load automation state: {e}")

        return {
            "last_run": None,
            "keywords_processed": [],
            "content_created": [],
            "performance_data": {}
        }

    def save_automation_state(self, state: Dict[str, Any]):
        """Save automation state to disk."""
        try:
            with open(AUTOMATION_STATE_FILE, 'w') as f:
                json.dump(state, f, indent=2)
        except Exception as e:
            self.logger.error(f"Could not save automation state: {e}")

    def research_keyword(self, base_keyword: str, niche: str = 'general') -> KeywordData:
        """
        Research and analyze a keyword.

        Args:
            base_keyword: Base keyword to research
            niche: Content niche

        Returns:
            KeywordData object
        """
        self.logger.info(f"[KEYWORD ENGINE] Researching: {base_keyword}")
        return self.keyword_engine.analyze_keyword(base_keyword, niche)

    def analyze_competition(self, keyword: str) -> Optional[SERPAnalysis]:
        """
        Analyze SERP competition for keyword.

        Args:
            keyword: Keyword to analyze

        Returns:
            SERPAnalysis object or None
        """
        self.logger.info(f"[SERP ANALYZER] Analyzing competition: {keyword}")
        return self.serp_analyzer.analyze_serp(keyword, num_results=10)

    def create_content_brief(
        self,
        keyword_data: KeywordData,
        serp_analysis: Optional[SERPAnalysis]
    ) -> ContentBrief:
        """
        Create content creation brief from research data.

        Args:
            keyword_data: Keyword research data
            serp_analysis: SERP analysis data

        Returns:
            ContentBrief object
        """
        self.logger.info("[CONTENT BRIEF] Creating content brief")

        # Use SERP analysis if available, otherwise use keyword data
        if serp_analysis:
            target_word_count = serp_analysis.recommendations.get('target_word_count', 2000)
            must_include_keywords = serp_analysis.recommendations.get('must_include_keywords', [])
            competitive_score = serp_analysis.competitive_score

            # Combine suggested headings from both sources
            serp_headings = serp_analysis.recommendations.get('suggested_headings', [])
            keyword_headings = keyword_data.suggested_headings

            # Use SERP headings first, then fill with keyword headings
            all_headings = serp_headings + [h for h in keyword_headings if h not in serp_headings]

        else:
            target_word_count = 2000
            must_include_keywords = keyword_data.related_keywords[:15]
            competitive_score = 5.0
            all_headings = keyword_data.suggested_headings

        return ContentBrief(
            keyword=keyword_data.keyword,
            title=keyword_data.suggested_title,
            headings=all_headings[:10],
            target_word_count=target_word_count,
            must_include_keywords=must_include_keywords,
            serp_data=asdict(serp_analysis) if serp_analysis else {},
            priority=keyword_data.priority,
            competitive_score=competitive_score
        )

    def trigger_content_creation(self, brief: ContentBrief) -> Optional[Dict[str, Any]]:
        """
        Trigger AI content creation via API.

        Args:
            brief: Content creation brief

        Returns:
            API response or None on failure
        """
        self.logger.info(f"[AI CONTENT ENGINE] Triggering content creation: {brief.title}")

        # Prepare payload for WordPress API
        payload = {
            "action": "create_content",
            "keyword": brief.keyword,
            "title": brief.title,
            "headings": brief.headings,
            "target_word_count": brief.target_word_count,
            "keywords": brief.must_include_keywords,
            "priority": brief.priority,
            "serp_analysis": brief.serp_data
        }

        # Send to WordPress API endpoint
        url = f"{self.site_url.rstrip('/')}/wp-json/pearblog/v1/automation/create-content"

        headers = {
            "Authorization": f"Bearer {self.api_key}",
            "Content-Type": "application/json"
        }

        try:
            response = requests.post(url, json=payload, headers=headers, timeout=60)

            if response.status_code in [200, 201]:
                self.logger.info("✓ Content creation triggered successfully")
                return response.json()
            else:
                self.logger.error(f"API error: {response.status_code} - {response.text[:200]}")
                return None

        except Exception as e:
            self.logger.error(f"Failed to trigger content creation: {e}")
            return None

    def run_single_cycle(self, base_keyword: str, niche: str = 'general') -> bool:
        """
        Run a single automation cycle for one keyword.

        Args:
            base_keyword: Base keyword to process
            niche: Content niche

        Returns:
            True if successful, False otherwise
        """
        try:
            self.logger.info("="*60)
            self.logger.info(f"AUTOMATION CYCLE START: {base_keyword}")
            self.logger.info("="*60)

            # Step 1: Keyword Research
            self.logger.info("\n[STEP 1/5] KEYWORD RESEARCH")
            keyword_data = self.research_keyword(base_keyword, niche)

            # Step 2: SERP Analysis (optional - can be skipped for faster cycles)
            self.logger.info("\n[STEP 2/5] SERP ANALYSIS")
            try:
                serp_analysis = self.analyze_competition(keyword_data.keyword)
            except Exception as e:
                self.logger.warning(f"SERP analysis failed: {e}. Continuing without it.")
                serp_analysis = None

            # Step 3: Create Content Brief
            self.logger.info("\n[STEP 3/5] CONTENT BRIEF GENERATION")
            brief = self.create_content_brief(keyword_data, serp_analysis)

            # Save brief for reference
            brief_file = DATA_DIR / f"brief_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json"
            with open(brief_file, 'w') as f:
                json.dump(asdict(brief), f, indent=2)

            self.logger.info(f"Brief saved to: {brief_file}")

            # Step 4: Trigger Content Creation
            self.logger.info("\n[STEP 4/5] CONTENT CREATION")
            result = self.trigger_content_creation(brief)

            if not result:
                self.logger.error("Content creation failed")
                return False

            # Step 5: Update State
            self.logger.info("\n[STEP 5/5] UPDATE STATE")
            state = self.load_automation_state()
            state["last_run"] = datetime.now().isoformat()
            state["keywords_processed"].append({
                "keyword": base_keyword,
                "timestamp": datetime.now().isoformat(),
                "success": True,
                "brief_file": str(brief_file)
            })
            self.save_automation_state(state)

            self.logger.info("="*60)
            self.logger.info("✓ AUTOMATION CYCLE COMPLETED SUCCESSFULLY")
            self.logger.info("="*60)

            return True

        except Exception as e:
            self.logger.error(f"Automation cycle failed: {e}", exc_info=True)
            return False

    def run_batch_cycle(self, keywords: List[str], niche: str = 'general') -> Dict[str, int]:
        """
        Run automation for multiple keywords in batch.

        Args:
            keywords: List of base keywords
            niche: Content niche

        Returns:
            Statistics dictionary
        """
        self.logger.info(f"\n{'='*60}")
        self.logger.info(f"BATCH AUTOMATION: {len(keywords)} keywords")
        self.logger.info(f"{'='*60}\n")

        stats = {
            "total": len(keywords),
            "successful": 0,
            "failed": 0
        }

        for idx, keyword in enumerate(keywords, 1):
            self.logger.info(f"\nProcessing keyword {idx}/{len(keywords)}: {keyword}")

            success = self.run_single_cycle(keyword, niche)

            if success:
                stats["successful"] += 1
            else:
                stats["failed"] += 1

            # Rate limiting between keywords
            if idx < len(keywords):
                self.logger.info("Waiting 5 seconds before next keyword...")
                time.sleep(5)

        self.logger.info(f"\n{'='*60}")
        self.logger.info("BATCH AUTOMATION COMPLETE")
        self.logger.info(f"Success: {stats['successful']}/{stats['total']}")
        self.logger.info(f"Failed: {stats['failed']}/{stats['total']}")
        self.logger.info(f"{'='*60}\n")

        return stats


def main():
    """Main entry point."""
    # Get configuration from environment
    site_url = os.getenv("SITE_URL")
    api_key = os.getenv("API_KEY")

    if not site_url or not api_key:
        print("Error: SITE_URL and API_KEY environment variables required")
        sys.exit(1)

    # Initialize orchestrator
    orchestrator = AutomationOrchestrator(site_url, api_key)

    niche = os.getenv("NICHE", "travel")
    batch_mode = os.getenv("BATCH_MODE", "false").lower() == "true"

    if batch_mode:
        # Batch mode: read keywords from KEYWORDS env var (comma-separated) or use defaults
        keywords_env = os.getenv("KEYWORDS", "")
        if keywords_env:
            keywords = [k.strip() for k in keywords_env.split(",") if k.strip()]
        else:
            keywords = [
                "Babia Góra szlaki",
                "Tatry szlaki turystyczne",
                "Bieszczady atrakcje",
            ]

        stats = orchestrator.run_batch_cycle(keywords, niche)
        success = stats["failed"] == 0
    else:
        keyword = os.getenv("KEYWORD", "Babia Góra szlaki")
        success = orchestrator.run_single_cycle(keyword, niche)

    sys.exit(0 if success else 1)


if __name__ == "__main__":
    main()

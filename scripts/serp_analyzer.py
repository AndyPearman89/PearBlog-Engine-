#!/usr/bin/env python3
"""
PearBlog SERP Analyzer - Search Results Analysis Module

Analyzes Google SERP to extract competitive intelligence:
- Top-ranking content analysis
- Content gaps identification
- Competitive benchmarking
- Actionable content recommendations

Part of PearBlog Automation PRO v2
"""

import logging
from typing import List, Dict, Any, Optional
from dataclasses import dataclass, asdict
from collections import Counter
import statistics

from scraping_engine import ScrapingEngine, SERPResult, CompetitorData


@dataclass
class SERPAnalysis:
    """Represents complete SERP analysis for a keyword."""
    keyword: str
    top_results: List[SERPResult]
    content_analysis: Dict[str, Any]
    recommendations: Dict[str, Any]
    competitive_score: float


class SERPAnalyzer:
    """Analyzes SERP results to guide content creation."""

    def __init__(self):
        """Initialize the SERP analyzer."""
        self.logger = logging.getLogger(__name__)
        self.scraper = ScrapingEngine()

    def analyze_serp(self, keyword: str, num_results: int = 10) -> Optional[SERPAnalysis]:
        """
        Perform complete SERP analysis for a keyword.

        Args:
            keyword: Keyword to analyze
            num_results: Number of SERP results to analyze

        Returns:
            SERPAnalysis object or None on failure
        """
        self.logger.info(f"Starting SERP analysis for: {keyword}")

        # Step 1: Scrape SERP
        serp_results = self.scraper.scrape_google_serp(keyword, num_results)

        if not serp_results:
            self.logger.error("No SERP results found")
            return None

        self.logger.info(f"Analyzing {len(serp_results)} SERP results")

        # Step 2: Analyze top competitors
        competitors_data = []
        for result in serp_results[:5]:  # Analyze top 5
            try:
                self.logger.info(f"Scraping competitor: {result.url}")
                competitor_data = self.scraper.scrape_competitor_article(result.url)
                if competitor_data:
                    competitors_data.append(competitor_data)
            except Exception as e:
                self.logger.warning(f"Failed to scrape {result.url}: {e}")
                continue

        # Step 3: Analyze content patterns
        content_analysis = self._analyze_content_patterns(competitors_data)

        # Step 4: Generate recommendations
        recommendations = self._generate_recommendations(
            keyword,
            serp_results,
            competitors_data,
            content_analysis
        )

        # Step 5: Calculate competitive score
        competitive_score = self._calculate_competitive_score(content_analysis)

        return SERPAnalysis(
            keyword=keyword,
            top_results=serp_results,
            content_analysis=content_analysis,
            recommendations=recommendations,
            competitive_score=competitive_score
        )

    def _analyze_content_patterns(self, competitors_data: List[CompetitorData]) -> Dict[str, Any]:
        """
        Analyze patterns across competitor content.

        Args:
            competitors_data: List of competitor data

        Returns:
            Dictionary with content analysis
        """
        if not competitors_data:
            return {}

        # Extract metrics
        word_counts = [c.word_count for c in competitors_data]
        heading_counts = [len(c.headings) for c in competitors_data]
        image_counts = [c.images_count for c in competitors_data]
        link_counts = [c.links_count for c in competitors_data]

        # Aggregate all headings
        all_headings = []
        for comp in competitors_data:
            all_headings.extend(comp.headings)

        # Aggregate all keywords
        all_keywords = []
        for comp in competitors_data:
            all_keywords.extend(comp.keywords)

        # Find common headings (appearing in multiple articles)
        heading_freq = Counter(h.lower() for h in all_headings)
        common_headings = [h for h, count in heading_freq.most_common(10) if count >= 2]

        # Find common keywords
        keyword_freq = Counter(all_keywords)
        common_keywords = [k for k, _ in keyword_freq.most_common(20)]

        return {
            "competitors_analyzed": len(competitors_data),
            "avg_word_count": int(statistics.mean(word_counts)) if word_counts else 0,
            "min_word_count": min(word_counts) if word_counts else 0,
            "max_word_count": max(word_counts) if word_counts else 0,
            "avg_headings": int(statistics.mean(heading_counts)) if heading_counts else 0,
            "avg_images": int(statistics.mean(image_counts)) if image_counts else 0,
            "avg_links": int(statistics.mean(link_counts)) if link_counts else 0,
            "common_headings": common_headings,
            "common_keywords": common_keywords,
            "all_headings": all_headings,
        }

    def _generate_recommendations(
        self,
        keyword: str,
        serp_results: List[SERPResult],
        competitors_data: List[CompetitorData],
        content_analysis: Dict[str, Any]
    ) -> Dict[str, Any]:
        """
        Generate actionable content recommendations.

        Args:
            keyword: Target keyword
            serp_results: SERP results
            competitors_data: Competitor data
            content_analysis: Content analysis data

        Returns:
            Dictionary with recommendations
        """
        recommendations = {
            "target_word_count": 0,
            "suggested_headings": [],
            "must_include_keywords": [],
            "content_gaps": [],
            "optimization_tips": []
        }

        if not content_analysis:
            return recommendations

        # Target word count (aim for top 3 average + 10%)
        avg_words = content_analysis.get("avg_word_count", 1500)
        recommendations["target_word_count"] = int(avg_words * 1.1)

        # Suggested headings (from common patterns)
        common_headings = content_analysis.get("common_headings", [])
        recommendations["suggested_headings"] = common_headings[:8]

        # Must-include keywords (top LSI keywords)
        common_keywords = content_analysis.get("common_keywords", [])
        recommendations["must_include_keywords"] = common_keywords[:15]

        # Identify content gaps (headings NOT commonly covered)
        all_headings_lower = [h.lower() for h in content_analysis.get("all_headings", [])]
        heading_freq = Counter(all_headings_lower)

        # Look for unique headings (appear only once)
        unique_headings = [h for h, count in heading_freq.items() if count == 1]
        recommendations["content_gaps"] = unique_headings[:5]

        # Optimization tips
        tips = []

        avg_images = content_analysis.get("avg_images", 0)
        if avg_images > 0:
            tips.append(f"Dodaj co najmniej {avg_images} obrazów (średnia konkurencji)")

        avg_headings = content_analysis.get("avg_headings", 0)
        if avg_headings > 0:
            tips.append(f"Użyj {avg_headings}-{avg_headings+2} nagłówków H2/H3")

        avg_links = content_analysis.get("avg_links", 0)
        if avg_links > 0:
            tips.append(f"Dodaj {avg_links} linków wewnętrznych/zewnętrznych")

        tips.append(f"Docelowa liczba słów: {recommendations['target_word_count']}+")
        tips.append("Użyj wszystkich wymienionych słów kluczowych LSI")
        tips.append("Uwzględnij luki treściowe (tematy pomijane przez konkurencję)")

        recommendations["optimization_tips"] = tips

        return recommendations

    def _calculate_competitive_score(self, content_analysis: Dict[str, Any]) -> float:
        """
        Calculate competitive difficulty score (0-10).

        Args:
            content_analysis: Content analysis data

        Returns:
            Competitive score (higher = more competitive)
        """
        if not content_analysis:
            return 5.0

        score = 5.0  # Base score

        # Word count impact
        avg_words = content_analysis.get("avg_word_count", 1500)
        if avg_words > 3000:
            score += 2.0
        elif avg_words > 2000:
            score += 1.0
        elif avg_words < 1000:
            score -= 1.0

        # Content depth (headings)
        avg_headings = content_analysis.get("avg_headings", 5)
        if avg_headings > 15:
            score += 1.5
        elif avg_headings > 10:
            score += 0.5

        # Media richness (images)
        avg_images = content_analysis.get("avg_images", 3)
        if avg_images > 10:
            score += 1.0
        elif avg_images > 5:
            score += 0.5

        # Clamp to 0-10
        return max(0.0, min(10.0, score))

    def extract_serp_titles(self, keyword: str) -> List[str]:
        """
        Quick helper to extract just SERP titles.

        Args:
            keyword: Keyword to search

        Returns:
            List of titles from SERP
        """
        results = self.scraper.scrape_google_serp(keyword)
        return [r.title for r in results]

    def get_competitor_headings(self, keyword: str, num_competitors: int = 5) -> List[str]:
        """
        Get all headings from top competitors.

        Args:
            keyword: Keyword to search
            num_competitors: Number of competitors to analyze

        Returns:
            List of all headings found
        """
        results = self.scraper.scrape_google_serp(keyword, num_competitors)
        all_headings = []

        for result in results:
            competitor_data = self.scraper.scrape_competitor_article(result.url)
            if competitor_data:
                all_headings.extend(competitor_data.headings)

        return all_headings


def quick_serp_analysis(keyword: str) -> Dict[str, Any]:
    """
    Quick SERP analysis helper function.

    Args:
        keyword: Keyword to analyze

    Returns:
        Analysis dictionary
    """
    analyzer = SERPAnalyzer()
    analysis = analyzer.analyze_serp(keyword)

    if not analysis:
        return {}

    return {
        "keyword": analysis.keyword,
        "competitive_score": analysis.competitive_score,
        "target_word_count": analysis.recommendations.get("target_word_count"),
        "must_include_keywords": analysis.recommendations.get("must_include_keywords"),
        "suggested_headings": analysis.recommendations.get("suggested_headings"),
    }


if __name__ == "__main__":
    # Example usage
    logging.basicConfig(level=logging.INFO)

    analyzer = SERPAnalyzer()

    # Test SERP analysis
    print("\n=== SERP Analysis Test ===")
    keyword = "Babia Góra szlaki"

    # Note: This will make real HTTP requests
    # Uncomment to test:
    # analysis = analyzer.analyze_serp(keyword, num_results=5)
    #
    # if analysis:
    #     print(f"\nKeyword: {analysis.keyword}")
    #     print(f"Competitive Score: {analysis.competitive_score:.1f}/10")
    #     print(f"\nContent Analysis:")
    #     print(f"  Average Word Count: {analysis.content_analysis.get('avg_word_count')}")
    #     print(f"  Average Headings: {analysis.content_analysis.get('avg_headings')}")
    #     print(f"  Average Images: {analysis.content_analysis.get('avg_images')}")
    #     print(f"\nRecommendations:")
    #     print(f"  Target Word Count: {analysis.recommendations.get('target_word_count')}")
    #     print(f"\n  Suggested Headings:")
    #     for heading in analysis.recommendations.get('suggested_headings', [])[:5]:
    #         print(f"    - {heading}")
    #     print(f"\n  Must-Include Keywords:")
    #     for keyword in analysis.recommendations.get('must_include_keywords', [])[:10]:
    #         print(f"    - {keyword}")
    #     print(f"\n  Optimization Tips:")
    #     for tip in analysis.recommendations.get('optimization_tips', []):
    #         print(f"    - {tip}")

    print("\nSERP Analyzer initialized. Uncomment test code to run analysis.")

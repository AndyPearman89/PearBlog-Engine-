"""
Unit tests for serp_analyzer.py

Tests use mocking to avoid real HTTP calls.
"""

import pytest
import sys
import os

sys.path.insert(0, os.path.join(os.path.dirname(__file__), '../../scripts'))

from unittest.mock import patch, MagicMock

try:
    from serp_analyzer import SERPAnalyzer, SERPAnalysis
    SERP_AVAILABLE = True
except ImportError:
    SERP_AVAILABLE = False


@pytest.mark.skipif(not SERP_AVAILABLE, reason="serp_analyzer not importable in this environment")
class TestSERPAnalysis:
    """Tests for the SERPAnalysis dataclass/class."""

    def test_creation(self):
        analysis = SERPAnalysis(
            keyword="travel Poland",
            top_urls=["https://a.com", "https://b.com"],
            avg_word_count=1500,
            competition_score=0.4,
            content_gaps=["Budget travel tips"],
            recommended_headings=["Introduction", "Top Destinations"],
        )
        assert analysis.keyword == "travel Poland"
        assert len(analysis.top_urls) == 2
        assert analysis.competition_score == 0.4

    def test_is_competitive_high(self):
        analysis = SERPAnalysis(
            keyword="seo",
            top_urls=[],
            avg_word_count=2000,
            competition_score=0.8,
            content_gaps=[],
            recommended_headings=[],
        )
        assert analysis.is_competitive() is True

    def test_is_competitive_low(self):
        analysis = SERPAnalysis(
            keyword="niche keyword",
            top_urls=[],
            avg_word_count=800,
            competition_score=0.2,
            content_gaps=[],
            recommended_headings=[],
        )
        assert analysis.is_competitive() is False


@pytest.mark.skipif(not SERP_AVAILABLE, reason="serp_analyzer not importable in this environment")
class TestSERPAnalyzer:
    """Tests for the SERPAnalyzer class."""

    def setup_method(self):
        self.analyzer = SERPAnalyzer()

    def test_analyze_returns_serp_analysis(self):
        """analyze() should return a SERPAnalysis for a keyword."""
        with patch.object(self.analyzer, '_fetch_serp_results', return_value=[]):
            result = self.analyzer.analyze("test keyword")
            assert isinstance(result, SERPAnalysis)
            assert result.keyword == "test keyword"

    def test_analyze_empty_keyword(self):
        """analyze() with empty keyword should not crash."""
        with patch.object(self.analyzer, '_fetch_serp_results', return_value=[]):
            result = self.analyzer.analyze("")
            assert result is not None

    def test_competition_score_in_range(self):
        """competition_score should be between 0 and 1."""
        with patch.object(self.analyzer, '_fetch_serp_results', return_value=[]):
            result = self.analyzer.analyze("any keyword")
            assert 0.0 <= result.competition_score <= 1.0

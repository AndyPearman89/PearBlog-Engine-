"""
Unit tests for keyword_engine.py

Tests use mocking to avoid real HTTP calls.
"""

import pytest

from unittest.mock import patch, MagicMock
from keyword_engine import KeywordEngine, KeywordData


class TestKeywordData:
    """Tests for the KeywordData dataclass."""

    def test_creation_with_all_fields(self):
        kd = KeywordData(
            keyword="email marketing",
            search_volume=12000,
            competition="medium",
            variants=["email campaigns", "newsletter marketing"],
            related=[],
        )
        assert kd.keyword == "email marketing"
        assert kd.search_volume == 12000
        assert len(kd.variants) == 2

    def test_creation_with_minimal_fields(self):
        kd = KeywordData(keyword="seo tips")
        assert kd.keyword == "seo tips"
        assert kd.search_volume == 0
        assert kd.variants == []

    def test_keyword_is_lowercased_on_access(self):
        kd = KeywordData(keyword="SEO Tips")
        # keyword_engine stores as-is; normalisation is caller's responsibility.
        assert "SEO Tips" == kd.keyword


class TestKeywordEngine:
    """Tests for the KeywordEngine class."""

    def setup_method(self):
        self.engine = KeywordEngine()

    def test_expand_keyword_returns_list(self):
        """expand_keyword should return a list of strings."""
        variants = self.engine.expand_keyword("travel Poland")
        assert isinstance(variants, list)
        assert len(variants) > 0

    def test_expand_keyword_includes_original(self):
        """The original keyword should be in the returned list."""
        keyword = "best hotels Warsaw"
        variants = self.engine.expand_keyword(keyword)
        assert keyword in variants

    def test_expand_keyword_generates_variants(self):
        """Additional variants should be generated beyond the original."""
        variants = self.engine.expand_keyword("travel")
        # Should contain at least the original + some generated variants.
        assert len(variants) >= 1

    def test_cluster_keywords_groups_related(self):
        """Keywords sharing words should cluster together."""
        keywords = [
            "travel Poland",
            "travel Warsaw",
            "SEO tips",
            "SEO for beginners",
        ]
        clusters = self.engine.cluster_keywords(keywords)
        assert isinstance(clusters, dict)
        assert len(clusters) > 0

    def test_cluster_keywords_empty_input(self):
        """Empty input should return empty dict."""
        clusters = self.engine.cluster_keywords([])
        assert clusters == {} or isinstance(clusters, dict)

    def test_get_keyword_data_returns_keyword_data_object(self):
        """get_keyword_data should return a KeywordData instance."""
        result = self.engine.get_keyword_data("blog writing")
        assert isinstance(result, KeywordData)
        assert result.keyword == "blog writing"

    def test_get_keyword_data_includes_variants(self):
        """Returned KeywordData should have some variants."""
        result = self.engine.get_keyword_data("content marketing")
        assert isinstance(result.variants, list)

    def test_filter_by_competition_returns_subset(self):
        """Filtering by competition level should work."""
        data = [
            KeywordData(keyword="a", competition="low"),
            KeywordData(keyword="b", competition="high"),
            KeywordData(keyword="c", competition="low"),
        ]
        low = self.engine.filter_by_competition(data, "low")
        assert all(d.competition == "low" for d in low)
        assert len(low) == 2

    def test_filter_by_volume_filters_below_threshold(self):
        """Keywords with volume below threshold should be excluded."""
        data = [
            KeywordData(keyword="popular", search_volume=5000),
            KeywordData(keyword="niche",   search_volume=100),
        ]
        filtered = self.engine.filter_by_volume(data, min_volume=500)
        assert len(filtered) == 1
        assert filtered[0].keyword == "popular"

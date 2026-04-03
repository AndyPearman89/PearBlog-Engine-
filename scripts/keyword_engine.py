#!/usr/bin/env python3
"""
PearBlog Keyword Engine - Keyword Research & Analysis Module

Generates and analyzes keywords for content creation:
- Keyword expansion and variations
- Search volume estimation
- Competition analysis
- LSI keyword extraction
- Keyword clustering

Part of PearBlog Automation PRO v2
"""

import re
import logging
from typing import List, Dict, Set, Tuple, Optional
from dataclasses import dataclass, asdict
from collections import Counter
import hashlib


@dataclass
class KeywordData:
    """Represents keyword analysis data."""
    keyword: str
    variations: List[str]
    search_intent: str  # informational, commercial, transactional, navigational
    difficulty: str  # easy, medium, hard
    priority: int  # 1-10
    related_keywords: List[str]
    suggested_title: str
    suggested_headings: List[str]


class KeywordEngine:
    """Core keyword research and analysis engine."""

    def __init__(self):
        """Initialize the keyword engine."""
        self.logger = logging.getLogger(__name__)

        # Polish-specific modifiers for keyword expansion
        self.polish_modifiers = {
            'informational': [
                'co to jest', 'jak', 'dlaczego', 'kiedy', 'gdzie',
                'przewodnik', 'poradnik', 'wszystko o', 'informacje',
                'najlepsze', 'porady', 'tips', 'wskazówki'
            ],
            'commercial': [
                'najlepsze', 'ranking', 'porównanie', 'vs', 'opinie',
                'recenzja', 'test', 'który wybrać', 'jaki', 'alternatywy'
            ],
            'transactional': [
                'cena', 'tani', 'promocja', 'kup', 'sklep', 'oferta',
                'rezerwacja', 'bilety', 'wypożyczalnia', 'najtaniej'
            ],
            'local': [
                'w polsce', 'blisko', 'okolice', 'niedaleko', 'dojazd',
                'jak dojechać', 'mapa', 'lokalizacja', 'adres', 'godziny otwarcia'
            ]
        }

        # Travel-specific keywords (for travel niche)
        self.travel_modifiers = [
            'szlaki', 'trasy', 'wycieczki', 'atrakcje', 'co zobaczyć',
            'noclegi', 'hotele', 'parking', 'wstęp', 'bilety',
            'pogoda', 'kiedy jechać', 'jak dojechać', 'mapa',
            'najpiękniejsze miejsca', 'ukryte perły', 'weekend'
        ]

    def generate_keyword_variations(self, base_keyword: str, niche: str = 'general') -> List[str]:
        """
        Generate keyword variations for a base keyword.

        Args:
            base_keyword: Base keyword to expand
            niche: Content niche (general, travel, tech, etc.)

        Returns:
            List of keyword variations
        """
        self.logger.info(f"Generating variations for: {base_keyword}")

        variations = set()
        variations.add(base_keyword)

        # Add informational variations
        for modifier in self.polish_modifiers['informational']:
            variations.add(f"{modifier} {base_keyword}")
            variations.add(f"{base_keyword} {modifier}")

        # Add commercial variations
        for modifier in self.polish_modifiers['commercial']:
            variations.add(f"{modifier} {base_keyword}")
            variations.add(f"{base_keyword} {modifier}")

        # Add local variations
        for modifier in self.polish_modifiers['local']:
            variations.add(f"{base_keyword} {modifier}")

        # Travel-specific variations
        if niche == 'travel':
            for modifier in self.travel_modifiers:
                variations.add(f"{base_keyword} {modifier}")

        # Add year for freshness
        from datetime import datetime
        current_year = datetime.now().year
        variations.add(f"{base_keyword} {current_year}")
        variations.add(f"{base_keyword} przewodnik {current_year}")

        # Add question forms
        variations.add(f"jak wejść na {base_keyword}")
        variations.add(f"gdzie znajduje się {base_keyword}")
        variations.add(f"co warto zobaczyć {base_keyword}")

        # Remove duplicates and sort by length (shorter = more important)
        variations_list = sorted(list(variations), key=len)

        self.logger.info(f"Generated {len(variations_list)} variations")
        return variations_list

    def extract_lsi_keywords(self, text: str, top_n: int = 20) -> List[str]:
        """
        Extract LSI (Latent Semantic Indexing) keywords from text.

        Args:
            text: Text to analyze
            top_n: Number of top keywords to return

        Returns:
            List of LSI keywords
        """
        # Convert to lowercase
        text = text.lower()

        # Remove punctuation and split into words
        words = re.findall(r'\b[a-ząćęłńóśźż]{3,}\b', text)

        # Remove common stop words (Polish)
        stop_words = {
            'i', 'a', 'o', 'w', 'z', 'na', 'do', 'po', 'od', 'dla',
            'ze', 'we', 'się', 'to', 'nie', 'jest', 'są', 'był',
            'była', 'było', 'były', 'będzie', 'być', 'aby', 'ale',
            'oraz', 'lub', 'który', 'która', 'które', 'tego', 'tej',
            'ten', 'ta', 'jak', 'może', 'można', 'przez', 'już'
        }

        # Filter and count
        filtered_words = [w for w in words if w not in stop_words]
        word_freq = Counter(filtered_words)

        # Get top keywords
        lsi_keywords = [word for word, _ in word_freq.most_common(top_n)]

        return lsi_keywords

    def determine_search_intent(self, keyword: str) -> str:
        """
        Determine the search intent of a keyword.

        Args:
            keyword: Keyword to analyze

        Returns:
            Search intent type
        """
        keyword_lower = keyword.lower()

        # Transactional signals
        transactional_signals = ['kup', 'cena', 'tani', 'promocja', 'sklep', 'oferta', 'rezerwacja', 'bilety']
        if any(signal in keyword_lower for signal in transactional_signals):
            return 'transactional'

        # Commercial investigation signals
        commercial_signals = ['najlepsze', 'ranking', 'porównanie', 'vs', 'opinie', 'recenzja', 'test', 'alternatywy']
        if any(signal in keyword_lower for signal in commercial_signals):
            return 'commercial'

        # Navigational signals
        navigational_signals = ['strona', 'oficjalna', 'login', 'kontakt', 'adres']
        if any(signal in keyword_lower for signal in navigational_signals):
            return 'navigational'

        # Default to informational
        return 'informational'

    def estimate_difficulty(self, keyword: str) -> str:
        """
        Estimate keyword difficulty (simplified heuristic).

        Args:
            keyword: Keyword to analyze

        Returns:
            Difficulty level (easy, medium, hard)
        """
        # This is a simplified heuristic - in production, you'd use:
        # - SERP analysis (domain authority of ranking pages)
        # - Backlink analysis
        # - Content quality metrics

        word_count = len(keyword.split())

        # Long-tail keywords (3+ words) are generally easier
        if word_count >= 4:
            return 'easy'
        elif word_count == 3:
            return 'medium'
        else:
            # Short keywords are competitive
            # Check for commercial intent (harder)
            if self.determine_search_intent(keyword) in ['commercial', 'transactional']:
                return 'hard'
            return 'medium'

    def calculate_priority(self, keyword: str, difficulty: str, intent: str) -> int:
        """
        Calculate keyword priority score (1-10).

        Args:
            keyword: Keyword string
            difficulty: Difficulty level
            intent: Search intent

        Returns:
            Priority score
        """
        score = 5  # Base score

        # Difficulty impact (easier = higher priority)
        if difficulty == 'easy':
            score += 3
        elif difficulty == 'medium':
            score += 1
        else:
            score -= 1

        # Intent impact (informational = higher priority for SEO content)
        if intent == 'informational':
            score += 2
        elif intent == 'commercial':
            score += 1

        # Long-tail bonus
        if len(keyword.split()) >= 4:
            score += 1

        # Clamp to 1-10
        return max(1, min(10, score))

    def generate_title_suggestions(self, keyword: str, intent: str) -> str:
        """
        Generate SEO-optimized title suggestion.

        Args:
            keyword: Main keyword
            intent: Search intent

        Returns:
            Suggested title
        """
        from datetime import datetime
        year = datetime.now().year

        # Title templates based on intent
        if intent == 'informational':
            templates = [
                f"{keyword.title()} - Kompletny Przewodnik {year}",
                f"Wszystko o {keyword} - Poradnik {year}",
                f"{keyword.title()}: Co Musisz Wiedzieć w {year}",
                f"Przewodnik: {keyword.title()} - Porady i Informacje"
            ]
        elif intent == 'commercial':
            templates = [
                f"Najlepsze {keyword} - Ranking {year}",
                f"{keyword.title()} - Porównanie i Opinie {year}",
                f"Top 10: {keyword} - Test i Recenzje",
                f"{keyword.title()} - Który Wybrać? Poradnik {year}"
            ]
        elif intent == 'transactional':
            templates = [
                f"{keyword.title()} - Najlepsze Oferty {year}",
                f"Kup {keyword} - Porównanie Cen i Promocje",
                f"{keyword.title()} - Gdzie Kupić Najtaniej?",
                f"{keyword.title()} - Oferty i Rezerwacje Online"
            ]
        else:  # navigational
            templates = [
                f"{keyword.title()} - Oficjalna Strona i Informacje",
                f"{keyword.title()} - Jak Dojechać, Kontakt, Godziny",
                f"{keyword.title()} - Lokalizacja, Mapa, Adres"
            ]

        # Return first template
        return templates[0]

    def generate_heading_suggestions(self, keyword: str, intent: str) -> List[str]:
        """
        Generate H2 heading suggestions for article structure.

        Args:
            keyword: Main keyword
            intent: Search intent

        Returns:
            List of suggested H2 headings
        """
        headings = []

        if intent == 'informational':
            headings = [
                f"Czym jest {keyword}?",
                f"Historia i Znaczenie {keyword}",
                f"Najważniejsze Informacje o {keyword}",
                f"Jak Dotrzeć do {keyword}",
                f"Co Warto Zobaczyć w Okolicy",
                f"Praktyczne Porady i Wskazówki",
                f"Najczęściej Zadawane Pytania (FAQ)",
                f"Podsumowanie"
            ]
        elif intent == 'commercial':
            headings = [
                f"Najlepsze {keyword} - Ranking",
                f"Porównanie Opcji",
                f"Zalety i Wady",
                f"Opinie Użytkowników",
                f"Jak Wybrać Odpowiedni",
                f"Ceny i Dostępność",
                f"Alternatywy do Rozważenia",
                f"Podsumowanie i Rekomendacje"
            ]
        else:  # transactional or navigational
            headings = [
                f"Informacje o {keyword}",
                f"Jak Dotrzeć",
                f"Ceny i Bilety",
                f"Godziny Otwarcia",
                f"Parking i Dojazd",
                f"Co Zabrać ze Sobą",
                f"Praktyczne Informacje",
                f"Kontakt"
            ]

        return headings

    def analyze_keyword(self, keyword: str, niche: str = 'general') -> KeywordData:
        """
        Complete keyword analysis.

        Args:
            keyword: Keyword to analyze
            niche: Content niche

        Returns:
            KeywordData object with complete analysis
        """
        self.logger.info(f"Analyzing keyword: {keyword}")

        # Generate variations
        variations = self.generate_keyword_variations(keyword, niche)

        # Determine intent
        intent = self.determine_search_intent(keyword)

        # Estimate difficulty
        difficulty = self.estimate_difficulty(keyword)

        # Calculate priority
        priority = self.calculate_priority(keyword, difficulty, intent)

        # Generate title
        title = self.generate_title_suggestions(keyword, intent)

        # Generate headings
        headings = self.generate_heading_suggestions(keyword, intent)

        # Get related keywords (subset of variations)
        related_keywords = variations[1:11]  # Top 10 variations

        return KeywordData(
            keyword=keyword,
            variations=variations[:20],  # Limit to top 20
            search_intent=intent,
            difficulty=difficulty,
            priority=priority,
            related_keywords=related_keywords,
            suggested_title=title,
            suggested_headings=headings
        )

    def cluster_keywords(self, keywords: List[str]) -> Dict[str, List[str]]:
        """
        Cluster related keywords into topic groups.

        Args:
            keywords: List of keywords to cluster

        Returns:
            Dictionary of clusters {cluster_name: [keywords]}
        """
        self.logger.info(f"Clustering {len(keywords)} keywords")

        clusters = {}

        for keyword in keywords:
            # Extract main topic (first significant word)
            words = keyword.lower().split()

            # Skip common prefixes
            skip_words = {'jak', 'co', 'gdzie', 'kiedy', 'dlaczego', 'najlepsze'}
            main_word = next((w for w in words if w not in skip_words), words[0])

            if main_word not in clusters:
                clusters[main_word] = []

            clusters[main_word].append(keyword)

        # Sort clusters by size
        sorted_clusters = dict(sorted(clusters.items(), key=lambda x: len(x[1]), reverse=True))

        self.logger.info(f"Created {len(sorted_clusters)} keyword clusters")
        return sorted_clusters


if __name__ == "__main__":
    # Example usage
    logging.basicConfig(level=logging.INFO)

    engine = KeywordEngine()

    # Test keyword analysis
    print("\n=== Keyword Analysis Test ===")
    keyword = "Babia Góra szlaki"
    analysis = engine.analyze_keyword(keyword, niche='travel')

    print(f"Keyword: {analysis.keyword}")
    print(f"Search Intent: {analysis.search_intent}")
    print(f"Difficulty: {analysis.difficulty}")
    print(f"Priority: {analysis.priority}/10")
    print(f"\nSuggested Title: {analysis.suggested_title}")
    print(f"\nSuggested Headings:")
    for i, heading in enumerate(analysis.suggested_headings, 1):
        print(f"  {i}. {heading}")
    print(f"\nTop Related Keywords:")
    for keyword in analysis.related_keywords[:5]:
        print(f"  - {keyword}")

    # Test keyword clustering
    print("\n=== Keyword Clustering Test ===")
    test_keywords = [
        "Babia Góra szlaki",
        "Babia Góra noclegi",
        "Babia Góra parking",
        "Tatry szlaki",
        "Tatry noclegi",
        "Zakopane atrakcje"
    ]
    clusters = engine.cluster_keywords(test_keywords)

    for cluster_name, cluster_keywords in clusters.items():
        print(f"\n{cluster_name.upper()}:")
        for kw in cluster_keywords:
            print(f"  - {kw}")

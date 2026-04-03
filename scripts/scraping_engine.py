#!/usr/bin/env python3
"""
PearBlog Scraping Engine - Web Data Extraction Module

Extracts real data from web sources to improve content quality:
- Google SERP (titles, headings, snippets)
- Competitor articles (structure, keywords)
- Forums (Reddit-like discussions)
- Travel/location data

Part of PearBlog Automation PRO v2
"""

import re
import time
import logging
import hashlib
from typing import Dict, List, Optional, Any
from urllib.parse import urlencode, quote_plus
from dataclasses import dataclass, asdict
import requests
from bs4 import BeautifulSoup


# Configuration
USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
REQUEST_TIMEOUT = 10
RATE_LIMIT_DELAY = 2  # seconds between requests
MAX_RETRIES = 3


@dataclass
class SERPResult:
    """Represents a single SERP result."""
    position: int
    title: str
    url: str
    snippet: str
    domain: str


@dataclass
class CompetitorData:
    """Represents competitor article data."""
    url: str
    title: str
    headings: List[str]
    word_count: int
    keywords: List[str]
    meta_description: str
    images_count: int
    links_count: int


@dataclass
class ForumPost:
    """Represents a forum post/discussion."""
    source: str
    title: str
    content: str
    upvotes: int
    comments_count: int
    url: str


class ScrapingEngine:
    """Core scraping engine for data extraction."""

    def __init__(self, use_proxy: bool = False, proxy_url: Optional[str] = None):
        """
        Initialize the scraping engine.

        Args:
            use_proxy: Whether to use a proxy for requests
            proxy_url: Proxy URL if use_proxy is True
        """
        self.logger = logging.getLogger(__name__)
        self.session = requests.Session()
        self.session.headers.update({"User-Agent": USER_AGENT})

        if use_proxy and proxy_url:
            self.session.proxies = {
                "http": proxy_url,
                "https": proxy_url
            }

    def _make_request(self, url: str, params: Optional[Dict] = None) -> Optional[str]:
        """
        Make HTTP request with retry logic.

        Args:
            url: URL to request
            params: Query parameters

        Returns:
            HTML content or None on failure
        """
        for attempt in range(1, MAX_RETRIES + 1):
            try:
                self.logger.info(f"Fetching: {url} (attempt {attempt}/{MAX_RETRIES})")

                response = self.session.get(
                    url,
                    params=params,
                    timeout=REQUEST_TIMEOUT
                )

                if response.status_code == 200:
                    time.sleep(RATE_LIMIT_DELAY)  # Rate limiting
                    return response.text
                elif response.status_code == 429:
                    # Rate limited - wait longer
                    wait_time = RATE_LIMIT_DELAY * attempt * 2
                    self.logger.warning(f"Rate limited. Waiting {wait_time}s...")
                    time.sleep(wait_time)
                else:
                    self.logger.warning(f"HTTP {response.status_code} for {url}")

            except requests.exceptions.RequestException as e:
                self.logger.error(f"Request failed: {e}")
                if attempt < MAX_RETRIES:
                    time.sleep(RATE_LIMIT_DELAY * attempt)

        self.logger.error(f"Failed to fetch {url} after {MAX_RETRIES} attempts")
        return None

    def scrape_google_serp(self, keyword: str, num_results: int = 10) -> List[SERPResult]:
        """
        Scrape Google SERP for a keyword.

        Args:
            keyword: Search keyword
            num_results: Number of results to extract

        Returns:
            List of SERP results
        """
        self.logger.info(f"Scraping Google SERP for: {keyword}")

        # Build Google search URL
        params = {
            "q": keyword,
            "num": num_results,
            "hl": "pl"  # Polish language
        }

        url = "https://www.google.com/search"
        html = self._make_request(url, params)

        if not html:
            return []

        soup = BeautifulSoup(html, 'html.parser')
        results = []

        # Find organic search results (Google's structure changes frequently)
        search_results = soup.find_all('div', class_='g')

        for idx, result in enumerate(search_results[:num_results], 1):
            try:
                # Extract title
                title_elem = result.find('h3')
                title = title_elem.get_text() if title_elem else ""

                # Extract URL
                link_elem = result.find('a')
                url = link_elem['href'] if link_elem and 'href' in link_elem.attrs else ""

                # Extract snippet
                snippet_elem = result.find('div', class_=['VwiC3b', 'aCOpRe'])
                snippet = snippet_elem.get_text() if snippet_elem else ""

                # Extract domain
                domain = re.findall(r'://([^/]+)', url)
                domain = domain[0] if domain else ""

                if title and url:
                    results.append(SERPResult(
                        position=idx,
                        title=title,
                        url=url,
                        snippet=snippet,
                        domain=domain
                    ))

            except Exception as e:
                self.logger.error(f"Error parsing SERP result {idx}: {e}")
                continue

        self.logger.info(f"Extracted {len(results)} SERP results")
        return results

    def scrape_competitor_article(self, url: str) -> Optional[CompetitorData]:
        """
        Scrape competitor article for content analysis.

        Args:
            url: Article URL

        Returns:
            CompetitorData or None on failure
        """
        self.logger.info(f"Scraping competitor article: {url}")

        html = self._make_request(url)
        if not html:
            return None

        soup = BeautifulSoup(html, 'html.parser')

        try:
            # Extract title
            title_elem = soup.find('h1')
            title = title_elem.get_text().strip() if title_elem else ""

            # Extract all headings (H2, H3)
            headings = []
            for heading_tag in ['h2', 'h3']:
                for heading in soup.find_all(heading_tag):
                    text = heading.get_text().strip()
                    if text:
                        headings.append(text)

            # Extract meta description
            meta_desc = soup.find('meta', attrs={'name': 'description'})
            meta_description = meta_desc['content'] if meta_desc and 'content' in meta_desc.attrs else ""

            # Get all text content
            # Remove script and style elements
            for script in soup(["script", "style", "nav", "header", "footer"]):
                script.decompose()

            text = soup.get_text()
            lines = (line.strip() for line in text.splitlines())
            chunks = (phrase.strip() for line in lines for phrase in line.split("  "))
            text = ' '.join(chunk for chunk in chunks if chunk)

            # Word count
            word_count = len(text.split())

            # Count images
            images_count = len(soup.find_all('img'))

            # Count links
            links_count = len(soup.find_all('a'))

            # Extract keywords (simple frequency analysis)
            # Remove common words and short words
            stop_words = {'i', 'a', 'the', 'is', 'in', 'to', 'of', 'and', 'or', 'for', 'on', 'at', 'with'}
            words = re.findall(r'\b[a-ząćęłńóśźż]+\b', text.lower())
            word_freq = {}
            for word in words:
                if len(word) > 3 and word not in stop_words:
                    word_freq[word] = word_freq.get(word, 0) + 1

            # Get top keywords
            keywords = sorted(word_freq.items(), key=lambda x: x[1], reverse=True)[:20]
            keywords = [word for word, freq in keywords]

            return CompetitorData(
                url=url,
                title=title,
                headings=headings,
                word_count=word_count,
                keywords=keywords,
                meta_description=meta_description,
                images_count=images_count,
                links_count=links_count
            )

        except Exception as e:
            self.logger.error(f"Error scraping competitor article: {e}")
            return None

    def scrape_reddit_discussions(self, subreddit: str, keyword: str, limit: int = 5) -> List[ForumPost]:
        """
        Scrape Reddit discussions for user insights.

        Args:
            subreddit: Subreddit name (e.g., 'travel', 'poland')
            keyword: Search keyword
            limit: Number of posts to retrieve

        Returns:
            List of forum posts
        """
        self.logger.info(f"Scraping Reddit r/{subreddit} for: {keyword}")

        # Use Reddit's search API (no auth required for public data)
        url = f"https://www.reddit.com/r/{subreddit}/search.json"
        params = {
            "q": keyword,
            "restrict_sr": "on",
            "sort": "relevance",
            "limit": limit
        }

        try:
            response = self.session.get(url, params=params, timeout=REQUEST_TIMEOUT)

            if response.status_code != 200:
                self.logger.error(f"Reddit API returned {response.status_code}")
                return []

            data = response.json()
            posts = []

            for post_data in data.get('data', {}).get('children', [])[:limit]:
                post = post_data.get('data', {})

                posts.append(ForumPost(
                    source='reddit',
                    title=post.get('title', ''),
                    content=post.get('selftext', '')[:500],  # Limit content length
                    upvotes=post.get('ups', 0),
                    comments_count=post.get('num_comments', 0),
                    url=f"https://www.reddit.com{post.get('permalink', '')}"
                ))

            time.sleep(RATE_LIMIT_DELAY)
            self.logger.info(f"Extracted {len(posts)} Reddit posts")
            return posts

        except Exception as e:
            self.logger.error(f"Error scraping Reddit: {e}")
            return []

    def extract_location_data(self, location_name: str) -> Dict[str, Any]:
        """
        Extract location-specific data (placeholder for travel data scraping).

        Args:
            location_name: Name of location

        Returns:
            Dictionary with location data
        """
        self.logger.info(f"Extracting location data for: {location_name}")

        # This is a simplified version - in production, you would:
        # 1. Scrape Wikipedia for location info
        # 2. Get coordinates from OpenStreetMap
        # 3. Fetch weather data
        # 4. Extract tourist attractions

        location_data = {
            "name": location_name,
            "description": "",
            "coordinates": None,
            "attractions": [],
            "tips": [],
            "best_time_to_visit": "",
        }

        # Try to scrape Wikipedia
        wiki_url = f"https://pl.wikipedia.org/wiki/{quote_plus(location_name)}"
        html = self._make_request(wiki_url)

        if html:
            soup = BeautifulSoup(html, 'html.parser')

            # Extract first paragraph as description
            first_para = soup.find('div', class_='mw-parser-output')
            if first_para:
                para = first_para.find('p')
                if para:
                    location_data['description'] = para.get_text().strip()[:500]

        return location_data


def scrape_serp_titles(keyword: str) -> List[str]:
    """
    Quick helper to scrape SERP titles for a keyword.

    Args:
        keyword: Search keyword

    Returns:
        List of titles from SERP
    """
    engine = ScrapingEngine()
    results = engine.scrape_google_serp(keyword)
    return [result.title for result in results]


def analyze_competitor_content(url: str) -> Dict[str, Any]:
    """
    Quick helper to analyze competitor content.

    Args:
        url: Competitor URL

    Returns:
        Dictionary with analysis data
    """
    engine = ScrapingEngine()
    data = engine.scrape_competitor_article(url)
    return asdict(data) if data else {}


if __name__ == "__main__":
    # Example usage
    logging.basicConfig(level=logging.INFO)

    engine = ScrapingEngine()

    # Test SERP scraping
    print("\n=== SERP Scraping Test ===")
    serp_results = engine.scrape_google_serp("Babia Góra szlaki", num_results=5)
    for result in serp_results:
        print(f"{result.position}. {result.title}")
        print(f"   URL: {result.url}")
        print(f"   Snippet: {result.snippet[:100]}...")
        print()

    # Test competitor scraping (example with a public article)
    print("\n=== Competitor Analysis Test ===")
    # Note: Replace with actual competitor URL in production
    # competitor_data = engine.scrape_competitor_article("https://example.com/article")
    # if competitor_data:
    #     print(f"Title: {competitor_data.title}")
    #     print(f"Word Count: {competitor_data.word_count}")
    #     print(f"Headings: {len(competitor_data.headings)}")
    #     print(f"Top Keywords: {', '.join(competitor_data.keywords[:5])}")

    # Test Reddit scraping
    print("\n=== Reddit Scraping Test ===")
    reddit_posts = engine.scrape_reddit_discussions("poland", "Babia Góra", limit=3)
    for post in reddit_posts:
        print(f"Title: {post.title}")
        print(f"Upvotes: {post.upvotes} | Comments: {post.comments_count}")
        print(f"URL: {post.url}")
        print()

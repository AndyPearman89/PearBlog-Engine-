"""
PearBlog Engine Python API Client
Version: 1.0.0

Requirements:
    pip install requests

Usage:
    from pearblog_client import PearBlogClient

    client = PearBlogClient("https://your-site.com", "your-api-key")
    health = client.health()
    print(health["status"])  # 'ok' | 'degraded'
"""

from __future__ import annotations

import hashlib
import hmac
import json
import time
from typing import Any, Dict, List, Optional, Union
from urllib.parse import urljoin

try:
    import requests
    from requests import Response, Session
except ImportError as exc:  # pragma: no cover
    raise ImportError(
        "PearBlog Python client requires 'requests'. Install with: pip install requests"
    ) from exc


class PearBlogAPIError(Exception):
    """Raised when the PearBlog REST API returns a non-2xx response."""

    def __init__(self, status_code: int, message: str, data: Any = None) -> None:
        super().__init__(f"HTTP {status_code}: {message}")
        self.status_code = status_code
        self.data = data


class PearBlogClient:
    """
    Minimal Python client for the PearBlog Engine REST API.

    Args:
        base_url:  WordPress site URL, e.g. 'https://example.com'
        api_key:   PearBlog API key from WP Admin → PearBlog Engine → Settings
        timeout:   Request timeout in seconds (default: 30)
        session:   Optional ``requests.Session`` for connection pooling
    """

    NAMESPACE = "/wp-json/pearblog/v1"

    def __init__(
        self,
        base_url: str,
        api_key: str,
        timeout: int = 30,
        session: Optional[Session] = None,
    ) -> None:
        self.base_url = base_url.rstrip("/")
        self.api_key = api_key
        self.timeout = timeout
        self._session = session or requests.Session()
        self._session.headers.update(
            {
                "Content-Type": "application/json",
                "X-PearBlog-API-Key": api_key,
            }
        )

    # -------------------------------------------------------------------------
    # Health
    # -------------------------------------------------------------------------

    def health(self) -> Dict[str, Any]:
        """GET /pearblog/v1/health — returns site health status."""
        return self._get("/health")

    # -------------------------------------------------------------------------
    # Topics
    # -------------------------------------------------------------------------

    def get_topics(self) -> List[str]:
        """GET /pearblog/v1/topics — list queued topics."""
        return self._get("/topics")

    def add_topics(self, topics: Union[str, List[str]]) -> Dict[str, Any]:
        """
        POST /pearblog/v1/topics — add one or more topics to the queue.

        Args:
            topics: A single topic string or a list of topic strings.
        """
        if isinstance(topics, str):
            topics = [topics]
        return self._post("/topics", {"topics": topics})

    def clear_topics(self) -> Dict[str, Any]:
        """DELETE /pearblog/v1/topics — clear the entire topic queue."""
        return self._delete("/topics")

    # -------------------------------------------------------------------------
    # Webhooks
    # -------------------------------------------------------------------------

    def get_webhooks(self) -> List[Dict[str, Any]]:
        """GET /pearblog/v1/webhooks — list registered webhooks."""
        return self._get("/webhooks")

    def create_webhook(
        self,
        url: str,
        events: List[str],
        secret: str = "",
    ) -> Dict[str, Any]:
        """
        POST /pearblog/v1/webhooks — register a new outbound webhook.

        Args:
            url:    Target endpoint URL.
            events: List of event names, e.g. ['pearblog.article_published'].
            secret: Optional HMAC-SHA256 signing secret.
        """
        return self._post("/webhooks", {"url": url, "events": events, "secret": secret})

    def delete_webhook(self, webhook_id: Union[int, str]) -> Dict[str, Any]:
        """DELETE /pearblog/v1/webhooks/{id} — remove a webhook."""
        return self._delete(f"/webhooks/{webhook_id}")

    # -------------------------------------------------------------------------
    # Content Calendar
    # -------------------------------------------------------------------------

    def get_calendar(self, **params: str) -> List[Dict[str, Any]]:
        """
        GET /pearblog/v1/calendar — list scheduled topics.

        Keyword args are passed as query string parameters, e.g. month='2026-05'.
        """
        qs = "&".join(f"{k}={v}" for k, v in params.items())
        path = "/calendar" + (f"?{qs}" if qs else "")
        return self._get(path)

    def schedule_post(self, date: str, topic: str) -> Dict[str, Any]:
        """
        POST /pearblog/v1/calendar — schedule a topic for a specific date.

        Args:
            date:  ISO date string 'YYYY-MM-DD'.
            topic: Topic string.
        """
        return self._post("/calendar", {"date": date, "topic": topic})

    def remove_scheduled_post(self, date: str) -> Dict[str, Any]:
        """DELETE /pearblog/v1/calendar/{date} — remove a scheduled entry."""
        return self._delete(f"/calendar/{date}")

    # -------------------------------------------------------------------------
    # Performance Metrics
    # -------------------------------------------------------------------------

    def get_metrics(self) -> Dict[str, Any]:
        """GET /pearblog/v1/performance/metrics — recent pipeline metrics."""
        return self._get("/performance/metrics")

    # -------------------------------------------------------------------------
    # Webhook Signature Verification (helper)
    # -------------------------------------------------------------------------

    @staticmethod
    def verify_webhook_signature(payload: bytes, signature: str, secret: str) -> bool:
        """
        Verify an incoming webhook payload against its X-PearBlog-Signature header.

        Args:
            payload:   Raw request body bytes.
            signature: Value of the X-PearBlog-Signature header.
            secret:    Shared HMAC secret configured when the webhook was created.

        Returns:
            True if the signature is valid, False otherwise.

        Example (Flask)::

            @app.route('/webhook', methods=['POST'])
            def webhook():
                valid = PearBlogClient.verify_webhook_signature(
                    request.get_data(),
                    request.headers.get('X-PearBlog-Signature', ''),
                    'my-secret'
                )
                if not valid:
                    abort(403)
                ...
        """
        expected = hmac.new(
            secret.encode(), payload, hashlib.sha256
        ).hexdigest()
        # timing-safe comparison
        return hmac.compare_digest(expected, signature)

    # -------------------------------------------------------------------------
    # Private HTTP helpers
    # -------------------------------------------------------------------------

    def _url(self, path: str) -> str:
        return self.base_url + self.NAMESPACE + path

    def _get(self, path: str) -> Any:
        response = self._session.get(self._url(path), timeout=self.timeout)
        return self._handle(response)

    def _post(self, path: str, data: Dict[str, Any]) -> Any:
        response = self._session.post(
            self._url(path), data=json.dumps(data), timeout=self.timeout
        )
        return self._handle(response)

    def _delete(self, path: str) -> Any:
        response = self._session.delete(self._url(path), timeout=self.timeout)
        return self._handle(response)

    @staticmethod
    def _handle(response: Response) -> Any:
        try:
            data = response.json()
        except ValueError:
            data = response.text

        if not response.ok:
            message = (
                data.get("message", response.text)
                if isinstance(data, dict)
                else str(data)
            )
            raise PearBlogAPIError(response.status_code, message, data)

        return data

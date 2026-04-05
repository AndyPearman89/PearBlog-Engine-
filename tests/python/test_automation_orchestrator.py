"""
Unit tests for automation_orchestrator.py

Tests use mocking to avoid real HTTP calls.
"""

import pytest
import sys
import os

sys.path.insert(0, os.path.join(os.path.dirname(__file__), '../../scripts'))

from unittest.mock import patch, MagicMock, call
import json

try:
    from automation_orchestrator import AutomationOrchestrator
    ORCHESTRATOR_AVAILABLE = True
except ImportError:
    ORCHESTRATOR_AVAILABLE = False


@pytest.mark.skipif(not ORCHESTRATOR_AVAILABLE, reason="automation_orchestrator not importable")
class TestAutomationOrchestrator:
    """Tests for the AutomationOrchestrator class."""

    def _make_orchestrator(self):
        return AutomationOrchestrator(
            wp_url="https://example.com",
            api_key="test_key_123",
        )

    def test_initialization(self):
        orch = self._make_orchestrator()
        assert orch is not None

    def test_create_content_calls_api(self):
        orch = self._make_orchestrator()

        mock_response = MagicMock()
        mock_response.status_code = 201
        mock_response.json.return_value = {
            "success": True,
            "post_id": 42,
            "topic": "Test Topic",
            "status": "published",
            "url": "https://example.com/test-topic/",
        }

        with patch.object(orch, '_post', return_value=mock_response) as mock_post:
            result = orch.create_content(keyword="Test Topic")
            mock_post.assert_called_once()

    def test_get_status_calls_api(self):
        orch = self._make_orchestrator()

        mock_response = MagicMock()
        mock_response.status_code = 200
        mock_response.json.return_value = {
            "queue_length": 5,
            "next_topic": "Topic A",
        }

        with patch.object(orch, '_get', return_value=mock_response):
            status = orch.get_status()
            assert status is not None

    def test_api_key_included_in_headers(self):
        orch = self._make_orchestrator()
        headers = orch._get_headers()
        assert "Authorization" in headers
        assert "test_key_123" in headers["Authorization"]

    def test_process_content_batch_success(self):
        orch = self._make_orchestrator()

        mock_response = MagicMock()
        mock_response.status_code = 200
        mock_response.json.return_value = {
            "success": True,
            "articles": [
                {"post_id": 1, "topic": "Topic A", "status": "published"},
            ],
        }

        with patch.object(orch, '_post', return_value=mock_response):
            result = orch.process_content()
            assert result is not None

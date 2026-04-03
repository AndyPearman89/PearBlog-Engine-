#!/usr/bin/env python3
"""
PearBlog Content Pipeline Automation Script

Handles API endpoint triggering, SEO processing, and deployment with:
- Duplicate prevention
- Rate limiting
- Comprehensive logging
- Automatic retry on failure
"""

import os
import sys
import json
import time
import logging
import hashlib
from datetime import datetime
from pathlib import Path
from typing import Dict, Any, Optional
import requests


# Configuration
MAX_RETRIES = 3
RETRY_DELAY = 5  # seconds
RATE_LIMIT_DELAY = 1  # seconds between requests
TIMEOUT = 30  # request timeout in seconds
LOG_DIR = Path("logs")
EXECUTION_HISTORY_FILE = LOG_DIR / "execution_history.json"


class PipelineExecutor:
    """Handles content pipeline execution with error handling and logging."""

    def __init__(self):
        """Initialize the pipeline executor."""
        self.site_url = os.getenv("SITE_URL")
        self.api_endpoint = os.getenv("API_ENDPOINT")
        self.api_key = os.getenv("API_KEY")
        self.setup_logging()
        self.validate_config()

    def setup_logging(self):
        """Configure logging to both file and console."""
        LOG_DIR.mkdir(exist_ok=True)

        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        log_file = LOG_DIR / f"pipeline_{timestamp}.log"

        logging.basicConfig(
            level=logging.INFO,
            format='%(asctime)s - %(levelname)s - %(message)s',
            handlers=[
                logging.FileHandler(log_file),
                logging.StreamHandler(sys.stdout)
            ]
        )
        self.logger = logging.getLogger(__name__)
        self.logger.info("="*60)
        self.logger.info("PearBlog Content Pipeline - Execution Started")
        self.logger.info("="*60)

    def validate_config(self) -> None:
        """Validate required environment variables."""
        missing = []

        # Validate SITE_URL
        if not self.site_url:
            missing.append("SITE_URL")
        elif not (self.site_url.startswith("http://") or self.site_url.startswith("https://")):
            self.logger.error("SITE_URL must start with http:// or https://")
            sys.exit(1)

        # Validate API_ENDPOINT
        if not self.api_endpoint:
            missing.append("API_ENDPOINT")
        elif not self.api_endpoint.startswith("/"):
            self.logger.warning("API_ENDPOINT should start with / - auto-correcting")
            self.api_endpoint = "/" + self.api_endpoint

        # Validate API_KEY
        if not self.api_key:
            missing.append("API_KEY")
        elif len(self.api_key) < 10:
            self.logger.error("API_KEY appears to be too short (minimum 10 characters)")
            sys.exit(1)

        if missing:
            self.logger.error(f"Missing required environment variables: {', '.join(missing)}")
            sys.exit(1)

        self.logger.info("Configuration validated successfully")
        self.logger.info(f"Site URL: {self.site_url}")
        self.logger.info(f"API Endpoint: {self.api_endpoint}")

    def generate_execution_hash(self, payload: Dict[str, Any]) -> str:
        """Generate a hash for duplicate detection."""
        content = json.dumps(payload, sort_keys=True)
        return hashlib.sha256(content.encode()).hexdigest()

    def load_execution_history(self) -> Dict[str, Any]:
        """Load previous execution history."""
        if EXECUTION_HISTORY_FILE.exists():
            try:
                with open(EXECUTION_HISTORY_FILE, 'r') as f:
                    return json.load(f)
            except Exception as e:
                self.logger.warning(f"Could not load execution history: {e}")
        return {"executions": []}

    def save_execution_history(self, history: Dict[str, Any]):
        """Save execution history."""
        try:
            with open(EXECUTION_HISTORY_FILE, 'w') as f:
                json.dump(history, f, indent=2)
        except Exception as e:
            self.logger.error(f"Could not save execution history: {e}")

    def is_duplicate(self, execution_hash: str, history: Dict[str, Any]) -> bool:
        """Check if this execution is a duplicate."""
        recent_hashes = [
            exec_record.get("hash")
            for exec_record in history.get("executions", [])[-100:]  # Check last 100
        ]
        return execution_hash in recent_hashes

    def trigger_api(self, payload: Dict[str, Any]) -> Optional[Dict[str, Any]]:
        """
        Trigger the API endpoint with retry logic.

        Args:
            payload: Request payload

        Returns:
            API response data or None on failure
        """
        headers = {
            "Authorization": f"Bearer {self.api_key}",
            "Content-Type": "application/json",
            "User-Agent": "PearBlog-Pipeline/1.0"
        }

        url = f"{self.site_url.rstrip('/')}/{self.api_endpoint.lstrip('/')}"

        for attempt in range(1, MAX_RETRIES + 1):
            try:
                self.logger.info(f"API request attempt {attempt}/{MAX_RETRIES}")
                self.logger.info(f"Endpoint: {url}")

                response = requests.post(
                    url,
                    json=payload,
                    headers=headers,
                    timeout=TIMEOUT
                )

                self.logger.info(f"Response status: {response.status_code}")

                if response.status_code in [200, 201, 202, 204]:
                    self.logger.info("✓ API request successful")
                    try:
                        return response.json() if response.content else {}
                    except json.JSONDecodeError as e:
                        self.logger.error(f"Invalid JSON response: {e}")
                        return None
                elif response.status_code == 401:
                    self.logger.error("Authentication failed - check API_KEY")
                    return None
                elif response.status_code == 403:
                    self.logger.error("Access forbidden - check API permissions")
                    return None
                elif response.status_code == 404:
                    self.logger.error("API endpoint not found - check API_ENDPOINT")
                    return None
                elif response.status_code == 429:
                    self.logger.warning("Rate limit exceeded, waiting before retry...")
                    time.sleep(RATE_LIMIT_DELAY * attempt * 2)
                elif response.status_code >= 500:
                    self.logger.warning(f"Server error ({response.status_code}), retrying...")
                    time.sleep(RETRY_DELAY * attempt)
                else:
                    # Limit error message length to avoid exposing sensitive data
                    error_msg = response.text[:200] if response.text else "No error message"
                    self.logger.error(f"API error: {response.status_code} - {error_msg}")
                    return None

            except requests.exceptions.Timeout:
                self.logger.warning(f"Request timeout on attempt {attempt}")
                if attempt < MAX_RETRIES:
                    time.sleep(RETRY_DELAY * attempt)
            except requests.exceptions.RequestException as e:
                self.logger.error(f"Request failed: {e}")
                if attempt < MAX_RETRIES:
                    time.sleep(RETRY_DELAY * attempt)

        self.logger.error("All retry attempts exhausted")
        return None

    def run(self) -> bool:
        """
        Execute the content pipeline.

        Returns:
            True if successful, False otherwise
        """
        try:
            # Prepare payload for duplicate detection (without timestamp)
            payload_base = {
                "action": "process_content",
                "seo_enabled": True,
                "source": "github_automation"
            }

            # Check for duplicates using base payload
            execution_hash = self.generate_execution_hash(payload_base)
            history = self.load_execution_history()

            # Add timestamp to actual payload
            payload = {
                **payload_base,
                "timestamp": datetime.now().isoformat()
            }

            if self.is_duplicate(execution_hash, history):
                self.logger.warning("⚠ Duplicate execution detected - skipping")
                return True  # Not a failure, just a skip

            self.logger.info("No duplicate detected - proceeding with execution")

            # Rate limiting
            time.sleep(RATE_LIMIT_DELAY)

            # Trigger API
            result = self.trigger_api(payload)

            if result:
                # Record successful execution
                execution_record = {
                    "hash": execution_hash,
                    "timestamp": datetime.now().isoformat(),
                    "status": "success",
                    "result": result
                }
                history["executions"].append(execution_record)
                self.save_execution_history(history)

                self.logger.info("="*60)
                self.logger.info("✓ Pipeline execution completed successfully")
                self.logger.info("="*60)

                # Save execution summary
                try:
                    summary_file = LOG_DIR / "latest_execution.json"
                    with open(summary_file, 'w') as f:
                        json.dump(execution_record, f, indent=2)
                except Exception as e:
                    self.logger.warning(f"Could not save execution summary: {e}")
                    # Continue - this is not critical

                return True
            else:
                self.logger.error("Pipeline execution failed")
                return False

        except Exception as e:
            self.logger.error(f"Unexpected error: {e}", exc_info=True)
            return False


def main():
    """Main entry point."""
    executor = PipelineExecutor()
    success = executor.run()
    sys.exit(0 if success else 1)


if __name__ == "__main__":
    main()

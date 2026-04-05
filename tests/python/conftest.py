"""Shared pytest configuration and fixtures."""
import sys
import os

# Ensure scripts directory is in path for all test modules.
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '../../scripts'))

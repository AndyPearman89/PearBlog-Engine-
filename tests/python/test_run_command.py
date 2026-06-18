"""Tests for the repository run helper command."""

import subprocess
import shutil
from pathlib import Path


ROOT_DIR = Path(__file__).resolve().parents[2]


def test_run_dev_command_succeeds():
    result = subprocess.run(
        ["bash", str(ROOT_DIR / "run"), "dev"],
        capture_output=True,
        text=True,
        check=False,
    )

    assert result.returncode == 0
    assert "Development sanity checks passed." in result.stdout
    if shutil.which("php"):
        assert "✓ PHP syntax check passed" in result.stdout
    else:
        assert "php not found" in result.stdout


def test_run_command_rejects_unknown_subcommand():
    result = subprocess.run(
        ["bash", str(ROOT_DIR / "run"), "unknown"],
        capture_output=True,
        text=True,
        check=False,
    )

    assert result.returncode == 1
    assert "Usage: ./run <command>" in result.stderr

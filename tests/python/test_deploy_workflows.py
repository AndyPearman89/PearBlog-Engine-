"""Regression tests for FTP deployment workflow connectivity checks."""

from pathlib import Path


ROOT_DIR = Path(__file__).resolve().parents[2]


def test_deploy_ftp_workflow_uses_valid_lftp_cli_arguments():
    workflow = (ROOT_DIR / ".github" / "workflows" / "deploy-ftp.yml").read_text()

    assert 'lftp -u "$USER","$PASS" -p "$PORT" "${FTP_PROTOCOL}://${HOST}" -e "$LFTP_SCRIPT"' in workflow
    assert 'open -p ${PORT} -u "${USER},${PASS}" "${FTP_PROTOCOL}://${HOST}"' not in workflow

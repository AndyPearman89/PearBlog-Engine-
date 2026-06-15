#!/usr/bin/env bash
set -euo pipefail

# Usage:
#   ./scripts/prod/monitor-production-log.sh [minutes]
# Example:
#   ./scripts/prod/monitor-production-log.sh 30

WINDOW_MINUTES="${1:-30}"

FTP_HOST="${FTP_HOST:-wordpress2614653.home.pl}"
FTP_USER="${FTP_USER:-}"
FTP_PASS="${FTP_PASS:-}"
REMOTE_LOG="${REMOTE_LOG:-/poradnik/wp-content/debug.log}"

if [[ -z "$FTP_USER" || -z "$FTP_PASS" ]]; then
  echo "Set FTP_USER and FTP_PASS env vars before running."
  exit 1
fi

TMP_LOG="/tmp/pearblog-debug-$(date +%s).log"

lftp -u "$FTP_USER","$FTP_PASS" "$FTP_HOST" -e "set ftp:ssl-force true; set ftp:ssl-protect-data true; set ssl:verify-certificate no; cat $REMOTE_LOG; bye" > "$TMP_LOG"

echo "Downloaded log to: $TMP_LOG"
echo "Checking last ${WINDOW_MINUTES} minute(s) for critical patterns..."

# UTC timestamp prefix in wp debug.log format: [14-Jun-2026 17:37:12 UTC]
CUTOFF_UTC="$(date -u -d "-${WINDOW_MINUTES} minutes" '+%d-%b-%Y %H:%M')"

awk -v cutoff="$CUTOFF_UTC" '
  BEGIN { in_window = 0 }
  {
    if ($0 ~ /^\[[0-9]{2}-[A-Za-z]{3}-[0-9]{4} [0-9]{2}:[0-9]{2}/) {
      ts = substr($0,2,17)
      in_window = (ts >= cutoff)
    }
    if (in_window) print
  }
' "$TMP_LOG" | grep -Ei "PHP Fatal|TypeError|Uncaught|WordPress database error|Image API error|OpenAI API error|rest_no_route" || echo "No critical patterns found in the selected window."

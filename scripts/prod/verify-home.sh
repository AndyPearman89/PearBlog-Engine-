#!/usr/bin/env bash
#
# PearBlog Engine - post-deploy verification for home.pl installs.
#
# Checks for each target:
#   - homepage reachable (HTTP 200)
#   - WP REST root (/wp-json/) up
#   - PearBlog health route (/wp-json/pearblog/v1/health)
#   - a permalink sample (not 404)
#
# Usage:
#   ./scripts/prod/verify-home.sh poradnik
#   ./scripts/prod/verify-home.sh pt24
#   ./scripts/prod/verify-home.sh all
#
# Optional:
#   BASE_HOST (default: https://wordpress2614653.home.pl)
#   PEARBLOG_HEALTH_SECRET  -> sent as ?secret= for the health route

set -euo pipefail

BASE_HOST="${BASE_HOST:-https://wordpress2614653.home.pl}"
SECRET="${PEARBLOG_HEALTH_SECRET:-}"

GREEN='\033[0;32m'; RED='\033[0;31m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'

code() { curl -s -o /dev/null -w "%{http_code}" -L --max-time 25 "$1" 2>/dev/null || echo "000"; }

check() {
  local label="$1" url="$2"; shift 2
  local expected="${*:-200}"
  local got; got="$(code "$url")"
  if grep -qw "$got" <<<"$expected"; then
    echo -e "${GREEN}✓${NC} ${label} -> ${got}  ${url}"
  else
    echo -e "${RED}✗${NC} ${label} -> ${got} (expected: ${expected})  ${url}"
  fi
}

verify_target() {
  local target="$1"
  local base="$BASE_HOST/$target"
  echo -e "${BLUE}== ${target} (${base}) ==${NC}"

  check "homepage"      "$base/"                                  200 301 302
  check "wp-json root"  "$base/wp-json/"                          200
  local health="$base/wp-json/pearblog/v1/health"
  [[ -n "$SECRET" ]] && health="${health}?secret=${SECRET}"
  check "pearblog health" "$health"                              200 401 403
  check "sample permalink" "$base/?p=1"                           200 301
  echo
}

case "${1:-}" in
  poradnik) verify_target "poradnik" ;;
  pt24)     verify_target "pt24" ;;
  all)      verify_target "poradnik"; verify_target "pt24" ;;
  *) echo "Usage: $0 {poradnik|pt24|all}"; exit 2 ;;
esac

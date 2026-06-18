#!/usr/bin/env bash
#
# PearBlog Engine - home.pl FTP deploy (poradnik + pt24)
#
# Mirrors the PearBlog code (MU-plugin engine, theme, brand-assets) and the
# root-level MU-plugin loaders to a home.pl subdirectory install over FTP.
#
# Aligned with DEPLOYMENT.md section 13 and repo memory notes:
#   - WordPress lives in a subdirectory docroot (/poradnik, /pt24).
#   - WP does NOT autoload MU-plugins from subdirs -> loaders must sit in
#     wp-content/mu-plugins/ root.
#   - pt24-local-services.php belongs ONLY to the pt24 install (redeclare
#     conflict on poradnik, see memory).
#
# Usage:
#   export FTP_USER="..."          # required
#   export FTP_PASS="..."          # required (set in terminal, never in chat)
#   ./scripts/prod/deploy-home.sh poradnik
#   ./scripts/prod/deploy-home.sh pt24
#   ./scripts/prod/deploy-home.sh all
#
# Optional:
#   FTP_HOST   (default: wordpress2614653.home.pl)
#   DRY_RUN=1  (lftp mirror --dry-run, uploads nothing)

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

FTP_HOST="${FTP_HOST:-wordpress2614653.home.pl}"
FTP_USER="${FTP_USER:-}"
FTP_PASS="${FTP_PASS:-}"
DRY_RUN="${DRY_RUN:-0}"

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'
info()  { echo -e "${BLUE}==>${NC} $1"; }
ok()    { echo -e "${GREEN}✓${NC} $1"; }
warn()  { echo -e "${YELLOW}⚠${NC} $1"; }
err()   { echo -e "${RED}✗${NC} $1" >&2; }

if [[ -z "$FTP_USER" || -z "$FTP_PASS" ]]; then
  err "Set FTP_USER and FTP_PASS environment variables before running."
  err "  export FTP_USER='...'; export FTP_PASS='...'"
  exit 1
fi

if ! command -v lftp >/dev/null 2>&1; then
  err "lftp is not installed."
  exit 1
fi

MIRROR_FLAGS="-R --delete --verbose"
if [[ "$DRY_RUN" == "1" ]]; then
  MIRROR_FLAGS="$MIRROR_FLAGS --dry-run"
  warn "DRY_RUN enabled - no files will be uploaded."
fi

deploy_target() {
  local target="$1"          # poradnik | pt24
  local remote="/$target"

  info "Deploying to ${FTP_HOST}${remote} ..."

  # Build the list of root-level MU-plugin loaders for this target.
  local mu_root_files=(
    "pearblog-engine-loader.php"
    "pearblog-health-fix.php"
  )
  if [[ "$target" == "pt24" ]]; then
    mu_root_files+=("pt24-local-services.php")
  fi

  local put_cmds=""
  for f in "${mu_root_files[@]}"; do
    if [[ -f "$PROJECT_ROOT/mu-plugins/$f" ]]; then
      put_cmds+="put -O \"$remote/wp-content/mu-plugins\" \"$PROJECT_ROOT/mu-plugins/$f\"
"
    else
      warn "Missing local file: mu-plugins/$f (skipped)"
    fi
  done

  cd "$PROJECT_ROOT"

  lftp -u "$FTP_USER","$FTP_PASS" "$FTP_HOST" <<EOF
set ftp:ssl-force true
set ftp:ssl-protect-data true
set ssl:verify-certificate no
set net:max-retries 2
set net:timeout 20

mirror $MIRROR_FLAGS --exclude tests/ --exclude .git/ mu-plugins/pearblog-engine "$remote/wp-content/mu-plugins/pearblog-engine"
mirror $MIRROR_FLAGS theme/pearblog-theme "$remote/wp-content/themes/pearblog-theme"
mirror $MIRROR_FLAGS brand-assets "$remote/wp-content/brand-assets"

$put_cmds
bye
EOF

  ok "Deploy to ${remote} finished."
}

case "${1:-}" in
  poradnik) deploy_target "poradnik" ;;
  pt24)     deploy_target "pt24" ;;
  all)      deploy_target "poradnik"; deploy_target "pt24" ;;
  *)
    err "Usage: $0 {poradnik|pt24|all}"
    exit 2
    ;;
esac

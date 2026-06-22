#!/bin/bash
#
# PearBlog Engine - WordPress Multisite + Domain Mapping Setup
#
# Converts an existing single-site WordPress install into a Multisite network
# (subdirectory mode) and registers each brand as its own site with a mapped
# custom domain. Domain mapping uses WordPress core (>= 4.5) — no extra plugin
# or sunrise.php is required, because each subsite's row carries its own domain.
#
# Network main domain : peartree.pro  (super-admin / network admin lives here)
# Subsite domains      : poradnik.pro, pt24.pro, elektryk-pt24.pro,
#                        mucharski.pl, zalew-mucharski.pl, po-beskidzku.pl
#
# Usage (run on the server, from the WordPress root, as a user that can write
# wp-config.php and run WP-CLI):
#
#   WP_PATH=/var/www/peartree.pro ./setup-multisite.sh
#
# Safe to re-run: every step checks current state before acting (idempotent).
#
set -euo pipefail

# ──────────────────────────────────────────────────────────────────────────
# Configuration
# ──────────────────────────────────────────────────────────────────────────
WP_PATH="${WP_PATH:-/var/www/peartree.pro}"
NETWORK_DOMAIN="${NETWORK_DOMAIN:-peartree.pro}"
NETWORK_TITLE="${NETWORK_TITLE:-PearTree Network}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@peartree.pro}"
WP="wp --path=${WP_PATH} --allow-root"

# Subsite map: "slug|custom-domain|Site Title"
# The network main site (peartree.pro) stays as blog ID 1 and is not listed.
SUBSITES=(
  "poradnik|poradnik.pro|Poradnik.pro"
  "pt24|pt24.pro|PT24.PRO"
  "elektryk|elektryk-pt24.pro|Elektryk PT24"
  "mucharski|mucharski.pl|Mucharski.pl"
  "zalew-mucharski|zalew-mucharski.pl|Zalew Mucharski"
  "po-beskidzku|po-beskidzku.pl|Po Beskidzku"
)

# ──────────────────────────────────────────────────────────────────────────
# Helpers
# ──────────────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'
step()    { echo -e "${BLUE}==>${NC} $1"; }
ok()      { echo -e "${GREEN}✓${NC} $1"; }
warn()    { echo -e "${YELLOW}⚠${NC} $1"; }
fail()    { echo -e "${RED}✗${NC} $1"; exit 1; }

# ──────────────────────────────────────────────────────────────────────────
# 0. Preflight
# ──────────────────────────────────────────────────────────────────────────
step "Preflight checks"
command -v wp >/dev/null 2>&1 || fail "WP-CLI (wp) not found on PATH"
[[ -f "${WP_PATH}/wp-config.php" ]] || fail "wp-config.php not found at ${WP_PATH}"
$WP core is-installed || fail "WordPress is not installed at ${WP_PATH}"
ok "WordPress detected at ${WP_PATH} (version $($WP core version))"

# ──────────────────────────────────────────────────────────────────────────
# 1. Enable multisite support in wp-config.php
# ──────────────────────────────────────────────────────────────────────────
step "Enabling WP_ALLOW_MULTISITE"
if $WP config get WP_ALLOW_MULTISITE >/dev/null 2>&1; then
  ok "WP_ALLOW_MULTISITE already present"
else
  $WP config set WP_ALLOW_MULTISITE true --raw --type=constant
  ok "WP_ALLOW_MULTISITE added"
fi

# ──────────────────────────────────────────────────────────────────────────
# 2. Convert to multisite (subdirectory mode) if not already a network
# ──────────────────────────────────────────────────────────────────────────
step "Converting to Multisite (subdirectory mode)"
if $WP site list >/dev/null 2>&1; then
  ok "Network already exists — skipping conversion"
else
  $WP core multisite-convert \
    --title="${NETWORK_TITLE}" \
    --base="/"
  ok "Converted single-site install to Multisite network"
fi

# After conversion WP-CLI prints the constants it expects; ensure they are set.
step "Ensuring multisite constants in wp-config.php"
$WP config set MULTISITE            true            --raw --type=constant || true
$WP config set SUBDOMAIN_INSTALL    false           --raw --type=constant || true
$WP config set DOMAIN_CURRENT_SITE  "${NETWORK_DOMAIN}"            --type=constant || true
$WP config set PATH_CURRENT_SITE    "/"                            --type=constant || true
$WP config set SITE_ID_CURRENT_SITE 1               --raw --type=constant || true
$WP config set BLOG_ID_CURRENT_SITE 1               --raw --type=constant || true
ok "Multisite constants ensured"

# ──────────────────────────────────────────────────────────────────────────
# 3. Create / map each subsite with its custom domain
# ──────────────────────────────────────────────────────────────────────────
step "Creating subsites and mapping custom domains"
for ROW in "${SUBSITES[@]}"; do
  IFS='|' read -r SLUG DOMAIN TITLE <<< "$ROW"

  # Does a site with this domain already exist?
  EXISTING_ID=$($WP site list --field=blog_id --domain="${DOMAIN}" 2>/dev/null | head -n1 || true)

  if [[ -z "${EXISTING_ID}" ]]; then
    # Create the subsite under a subdirectory path first…
    BLOG_ID=$($WP site create \
      --slug="${SLUG}" \
      --title="${TITLE}" \
      --email="${ADMIN_EMAIL}" \
      --porcelain 2>/dev/null || true)

    if [[ -z "${BLOG_ID}" ]]; then
      # Path may already be taken; look it up by path instead.
      BLOG_ID=$($WP site list --field=blog_id --path="/${SLUG}/" 2>/dev/null | head -n1 || true)
    fi
    [[ -n "${BLOG_ID}" ]] || { warn "Could not create or find site for ${DOMAIN} — skipping"; continue; }

    # …then remap it to its own apex domain at the root path.
    $WP db query "UPDATE $($WP db prefix --allow-root 2>/dev/null || echo wp_)blogs \
      SET domain='${DOMAIN}', path='/' WHERE blog_id=${BLOG_ID};" >/dev/null 2>&1 || \
      $WP site update "${BLOG_ID}" --domain="${DOMAIN}" --path="/" >/dev/null 2>&1 || true

    # Fix the site's home/siteurl options to the mapped domain.
    $WP --url="http://${DOMAIN}" option update home    "https://${DOMAIN}" --allow-root >/dev/null 2>&1 || true
    $WP --url="http://${DOMAIN}" option update siteurl "https://${DOMAIN}" --allow-root >/dev/null 2>&1 || true

    ok "Created + mapped ${DOMAIN} (blog ID ${BLOG_ID})"
  else
    ok "Site for ${DOMAIN} already exists (blog ID ${EXISTING_ID})"
  fi
done

# ──────────────────────────────────────────────────────────────────────────
# 4. Enforce single network theme (pearblog-theme)
# ──────────────────────────────────────────────────────────────────────────
step "Enabling pearblog-theme network-wide"
$WP theme enable pearblog-theme --network >/dev/null 2>&1 || true
ok "pearblog-theme enabled for the network"

# ──────────────────────────────────────────────────────────────────────────
# 5. Summary
# ──────────────────────────────────────────────────────────────────────────
step "Network site list"
$WP site list --fields=blog_id,domain,path,url 2>/dev/null || true

echo
ok "Multisite + domain mapping complete."
cat <<EOF

NEXT STEPS (manual / DNS / web server):
  1. Point every mapped domain's DNS A/AAAA record to this server's IP.
  2. Ensure the web server (Apache/Nginx) accepts all these domains
     (ServerAlias / server_name) and routes them to ${WP_PATH}.
  3. Issue SSL certificates for every domain, e.g.:
       certbot --nginx -d peartree.pro -d poradnik.pro -d pt24.pro \\
         -d elektryk-pt24.pro -d mucharski.pl -d zalew-mucharski.pl \\
         -d po-beskidzku.pl
  4. Network admin lives at: https://${NETWORK_DOMAIN}/wp-admin/network/
EOF

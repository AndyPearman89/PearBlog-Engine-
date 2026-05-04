#!/bin/bash
#
# PT24 Homepage V4 Deployment Script
#
# Automated deployment for PT24.PRO Homepage V4 template
# Creates and configures the V4 homepage with purple gradient design
#
# Usage:
#   ./scripts/deploy-pt24-home-v4.sh
#
# Version: 4.0.0
# Created: 2026-05-04

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo -e "${PURPLE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${PURPLE}║        PT24 Homepage V4 Deployment Script                 ║${NC}"
echo -e "${PURPLE}║        Purple Gradient High-Conversion Design             ║${NC}"
echo -e "${PURPLE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# ============================================================
# PRE-FLIGHT CHECKS
# ============================================================

echo -e "${BLUE}[1/5] Pre-flight checks...${NC}"

# Check if WP-CLI is available
if ! command -v wp &> /dev/null; then
    echo -e "${RED}✗ Error: WP-CLI is not installed${NC}"
    echo -e "${YELLOW}  Please install WP-CLI: https://wp-cli.org/${NC}"
    exit 1
fi
echo -e "${GREEN}✓ WP-CLI found${NC}"

# Check WordPress installation
if ! wp core is-installed 2>/dev/null; then
    echo -e "${RED}✗ Error: WordPress is not installed or not accessible${NC}"
    echo -e "${YELLOW}  Make sure you're in a WordPress directory${NC}"
    exit 1
fi
echo -e "${GREEN}✓ WordPress installation detected${NC}"

# Check if theme files exist
THEME_FILE="$PROJECT_ROOT/theme/pearblog-theme/page-pt24-home-v4.php"
CSS_FILE="$PROJECT_ROOT/theme/pearblog-theme/assets/css/pt24-home-v4.css"

if [ ! -f "$THEME_FILE" ]; then
    echo -e "${RED}✗ Error: Template file not found: $THEME_FILE${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Template file found${NC}"

if [ ! -f "$CSS_FILE" ]; then
    echo -e "${RED}✗ Error: CSS file not found: $CSS_FILE${NC}"
    exit 1
fi
echo -e "${GREEN}✓ CSS file found${NC}"

# ============================================================
# BACKUP EXISTING HOMEPAGE
# ============================================================

echo ""
echo -e "${BLUE}[2/5] Backing up existing homepage settings...${NC}"

CURRENT_SHOW_ON_FRONT=$(wp option get show_on_front 2>/dev/null || echo "posts")
CURRENT_PAGE_ON_FRONT=$(wp option get page_on_front 2>/dev/null || echo "0")

echo -e "${YELLOW}  Current homepage setting: $CURRENT_SHOW_ON_FRONT${NC}"
if [ "$CURRENT_PAGE_ON_FRONT" != "0" ]; then
    CURRENT_PAGE_TITLE=$(wp post get "$CURRENT_PAGE_ON_FRONT" --field=title 2>/dev/null || echo "Unknown")
    echo -e "${YELLOW}  Current homepage page: ID $CURRENT_PAGE_ON_FRONT ($CURRENT_PAGE_TITLE)${NC}"
fi

# Save backup
echo "$CURRENT_SHOW_ON_FRONT" > /tmp/pt24-v4-backup-show_on_front.txt
echo "$CURRENT_PAGE_ON_FRONT" > /tmp/pt24-v4-backup-page_on_front.txt
echo -e "${GREEN}✓ Backup saved to /tmp/pt24-v4-backup-*.txt${NC}"

# ============================================================
# CREATE PT24 HOMEPAGE V4 PAGE
# ============================================================

echo ""
echo -e "${BLUE}[3/5] Creating PT24 Homepage V4 page...${NC}"

# Check if V4 homepage already exists
EXISTING_PAGE=$(wp post list --post_type=page --post_status=any --name=pt24-homepage-v4 --format=ids 2>/dev/null || echo "")

if [ -n "$EXISTING_PAGE" ]; then
    echo -e "${YELLOW}  Found existing PT24 Homepage V4 (ID: $EXISTING_PAGE)${NC}"
    read -p "  Delete and recreate? [y/N] " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        wp post delete "$EXISTING_PAGE" --force
        echo -e "${GREEN}✓ Deleted existing page${NC}"
    else
        PAGE_ID="$EXISTING_PAGE"
        echo -e "${YELLOW}  Using existing page${NC}"
    fi
fi

# Create new page if needed
if [ -z "$PAGE_ID" ]; then
    PAGE_ID=$(wp post create \
      --post_type=page \
      --post_title="PT24 Homepage V4" \
      --post_name="pt24-homepage-v4" \
      --post_status=publish \
      --page_template=page-pt24-home-v4.php \
      --porcelain)

    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Created page with ID: $PAGE_ID${NC}"
    else
        echo -e "${RED}✗ Failed to create page${NC}"
        exit 1
    fi
fi

# ============================================================
# SET AS HOMEPAGE
# ============================================================

echo ""
echo -e "${BLUE}[4/5] Configuring as homepage...${NC}"

# Set homepage to display a static page
wp option update show_on_front page
echo -e "${GREEN}✓ Set homepage to display static page${NC}"

# Set the page as the homepage
wp option update page_on_front "$PAGE_ID"
echo -e "${GREEN}✓ Set page $PAGE_ID as homepage${NC}"

# ============================================================
# VERIFICATION & SUMMARY
# ============================================================

echo ""
echo -e "${BLUE}[5/5] Verifying deployment...${NC}"

# Verify settings
VERIFY_SHOW=$(wp option get show_on_front)
VERIFY_PAGE=$(wp option get page_on_front)

if [ "$VERIFY_SHOW" = "page" ] && [ "$VERIFY_PAGE" = "$PAGE_ID" ]; then
    echo -e "${GREEN}✓ Homepage configuration verified${NC}"
else
    echo -e "${RED}✗ Homepage configuration mismatch${NC}"
    exit 1
fi

# Get site URL
SITE_URL=$(wp option get siteurl)

# ============================================================
# SUCCESS SUMMARY
# ============================================================

echo ""
echo -e "${PURPLE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${PURPLE}║                   DEPLOYMENT SUCCESSFUL! 🎉                ║${NC}"
echo -e "${PURPLE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${GREEN}✓ PT24 Homepage V4 deployed successfully${NC}"
echo ""
echo -e "${BLUE}Homepage Details:${NC}"
echo -e "  ${YELLOW}Page ID:${NC} $PAGE_ID"
echo -e "  ${YELLOW}Template:${NC} page-pt24-home-v4.php"
echo -e "  ${YELLOW}URL:${NC} $SITE_URL"
echo ""
echo -e "${BLUE}Design Features:${NC}"
echo -e "  ${PURPLE}• Purple gradient hero (${NC}#667eea → #764ba2${PURPLE})${NC}"
echo -e "  ${PURPLE}• 6 conversion-optimized sections${NC}"
echo -e "  ${PURPLE}• Mobile-first responsive design${NC}"
echo -e "  ${PURPLE}• Trust signals throughout${NC}"
echo -e "  ${PURPLE}• Search bar integration${NC}"
echo ""
echo -e "${BLUE}Sections Included:${NC}"
echo -e "  1. Hero with Search Bar"
echo -e "  2. Services Grid (6 categories)"
echo -e "  3. How It Works (3 steps)"
echo -e "  4. Popular Cities"
echo -e "  5. Business CTA"
echo -e "  6. Final CTA"
echo ""
echo -e "${BLUE}Visit your new homepage:${NC}"
echo -e "  ${GREEN}$SITE_URL${NC}"
echo ""
echo -e "${YELLOW}Backup files (in case you need to rollback):${NC}"
echo -e "  /tmp/pt24-v4-backup-show_on_front.txt"
echo -e "  /tmp/pt24-v4-backup-page_on_front.txt"
echo ""
echo -e "${BLUE}To rollback:${NC}"
echo -e "  wp option update show_on_front \$(cat /tmp/pt24-v4-backup-show_on_front.txt)"
echo -e "  wp option update page_on_front \$(cat /tmp/pt24-v4-backup-page_on_front.txt)"
echo ""
echo -e "${PURPLE}════════════════════════════════════════════════════════════${NC}"

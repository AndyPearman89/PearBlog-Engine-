#!/bin/bash

################################################################################
# PT24.PRO Local Services Platform - Complete Deployment Script
#
# This script deploys:
# 1. Base WordPress + PearBlog Engine
# 2. PT24 Local Services Custom Post Types
# 3. Sample data (categories, cities)
# 4. Content generation tools
#
# Usage:
#   chmod +x deploy-pt24-local-services.sh
#   ./deploy-pt24-local-services.sh
#
################################################################################

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║                                                            ║${NC}"
echo -e "${BLUE}║         PT24.PRO Local Services Platform Setup            ║${NC}"
echo -e "${BLUE}║         Complete Deployment & Configuration               ║${NC}"
echo -e "${BLUE}║                                                            ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

WEB_ROOT="${WEB_ROOT:-/var/www/wordpress2614653.home.pl/pt24}"

# Check if WordPress is installed
if [ ! -f "$WEB_ROOT/wp-config.php" ]; then
    echo -e "${YELLOW}⚠️  WordPress not found at $WEB_ROOT${NC}"
    echo -e "${YELLOW}   Please run: ./scripts/deploy-pt24-pro.sh first${NC}"
    exit 1
fi

echo -e "${GREEN}✓ WordPress found at $WEB_ROOT${NC}"

# Step 1: Copy PT24 Local Services mu-plugin
echo -e "\n${BLUE}═══ Step 1: Installing PT24 Local Services Plugin ═══${NC}"

MU_PLUGINS_DIR="$WEB_ROOT/wp-content/mu-plugins"
mkdir -p "$MU_PLUGINS_DIR"

if [ -f "mu-plugins/pt24-local-services.php" ]; then
    cp mu-plugins/pt24-local-services.php "$MU_PLUGINS_DIR/"
    echo -e "${GREEN}✓ PT24 Local Services plugin installed${NC}"
else
    echo -e "${RED}✗ Plugin file not found: mu-plugins/pt24-local-services.php${NC}"
    exit 1
fi

# Step 2: Activate and flush rewrite rules
echo -e "\n${BLUE}═══ Step 2: Activating Plugin & Flushing Rewrites ═══${NC}"

cd "$WEB_ROOT"

# Flush rewrite rules
wp rewrite flush --allow-root
echo -e "${GREEN}✓ Rewrite rules flushed${NC}"

# Verify CPTs registered
echo -e "\n${YELLOW}Registered Post Types:${NC}"
wp post-type list --fields=name,label --allow-root | grep pt24

# Verify Taxonomies registered
echo -e "\n${YELLOW}Registered Taxonomies:${NC}"
wp taxonomy list --fields=name,label --allow-root | grep pt24

# Step 3: Create database tables
echo -e "\n${BLUE}═══ Step 3: Creating Database Tables ═══${NC}"

wp eval 'pt24_create_database_tables();' --allow-root
echo -e "${GREEN}✓ Database tables created${NC}"

# Verify tables
wp db query "SHOW TABLES LIKE 'wp_pt24%';" --allow-root

# Step 4: Initialize default data
echo -e "\n${BLUE}═══ Step 4: Initializing Default Data ═══${NC}"

# Force initialization
wp option delete pt24_data_initialized --allow-root 2>/dev/null || true
wp eval 'pt24_initialize_default_data();' --allow-root

echo -e "\n${YELLOW}Service Categories:${NC}"
wp term list pt24_service_cat --fields=name,slug --allow-root

echo -e "\n${YELLOW}Cities (Top 20):${NC}"
wp term list pt24_city --fields=name,slug --allow-root | head -20

# Step 5: Copy content generation script
echo -e "\n${BLUE}═══ Step 5: Installing Content Generation Tools ═══${NC}"

SCRIPTS_DIR="$WEB_ROOT/scripts"
mkdir -p "$SCRIPTS_DIR"

if [ -f "scripts/pt24_generate_pages.py" ]; then
    cp scripts/pt24_generate_pages.py "$SCRIPTS_DIR/"
    chmod +x "$SCRIPTS_DIR/pt24_generate_pages.py"
    echo -e "${GREEN}✓ Content generator installed${NC}"
else
    echo -e "${YELLOW}⚠️  Content generator not found (optional)${NC}"
fi

# Step 6: Install Python dependencies
echo -e "\n${BLUE}═══ Step 6: Installing Python Dependencies ═══${NC}"

if command -v pip3 &> /dev/null; then
    pip3 install openai --quiet 2>/dev/null || echo -e "${YELLOW}⚠️  Failed to install openai package${NC}"
    echo -e "${GREEN}✓ Python dependencies checked${NC}"
else
    echo -e "${YELLOW}⚠️  pip3 not found, install manually: pip3 install openai${NC}"
fi

# Step 7: Create sample CSV for bulk generation
echo -e "\n${BLUE}═══ Step 7: Creating Sample Data Files ═══${NC}"

cat > "$WEB_ROOT/pt24-sample-pages.csv" <<'EOF'
category,city
mechanik,warszawa
mechanik,krakow
mechanik,wroclaw
mechanik,gdansk
mechanik,poznan
hydraulik,warszawa
hydraulik,krakow
elektryk,warszawa
elektryk,krakow
laweta,warszawa
EOF

echo -e "${GREEN}✓ Sample CSV created: $WEB_ROOT/pt24-sample-pages.csv${NC}"

# Step 8: Create helper scripts
echo -e "\n${BLUE}═══ Step 8: Creating Helper Scripts ═══${NC}"

# Generate single page script
cat > "$WEB_ROOT/pt24-generate-single.sh" <<'SCRIPT'
#!/bin/bash
# Generate single PT24 local page
# Usage: ./pt24-generate-single.sh mechanik warszawa

if [ $# -lt 2 ]; then
    echo "Usage: $0 <category> <city>"
    echo "Example: $0 mechanik warszawa"
    exit 1
fi

CATEGORY=$1
CITY=$2
WP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

python3 "$WP_ROOT/scripts/pt24_generate_pages.py" \
    --category "$CATEGORY" \
    --city "$CITY" \
    --wp-path "$WP_ROOT"

SCRIPT

chmod +x "$WEB_ROOT/pt24-generate-single.sh"

# Generate bulk script
cat > "$WEB_ROOT/pt24-generate-bulk.sh" <<'SCRIPT'
#!/bin/bash
# Generate bulk PT24 pages from CSV
# Usage: ./pt24-generate-bulk.sh [csv_file]

CSV_FILE=${1:-pt24-sample-pages.csv}
WP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [ ! -f "$CSV_FILE" ]; then
    echo "Error: CSV file not found: $CSV_FILE"
    exit 1
fi

python3 "$WP_ROOT/scripts/pt24_generate_pages.py" \
    --csv "$CSV_FILE" \
    --wp-path "$WP_ROOT" \
    --rate-limit 2

SCRIPT

chmod +x "$WEB_ROOT/pt24-generate-bulk.sh"

echo -e "${GREEN}✓ Helper scripts created${NC}"

# Step 9: Display summary
echo -e "\n${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║                                                            ║${NC}"
echo -e "${BLUE}║            ✅  PT24 Local Services Setup Complete!         ║${NC}"
echo -e "${BLUE}║                                                            ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"

echo -e "\n${GREEN}📊 INSTALLED COMPONENTS:${NC}"
echo -e "   ✓ Custom Post Types (pt24_local, pt24_business, etc.)"
echo -e "   ✓ Taxonomies (cities, service categories)"
echo -e "   ✓ Database tables (leads, stats, subscriptions)"
echo -e "   ✓ 5 Service categories"
echo -e "   ✓ 20 Major cities"
echo -e "   ✓ Content generation tools"

echo -e "\n${GREEN}🚀 QUICK START:${NC}"
echo ""
echo -e "   ${YELLOW}1. Set OpenAI API Key:${NC}"
echo -e "      export OPENAI_API_KEY='sk-your-key-here'"
echo ""
echo -e "   ${YELLOW}2. Generate a single page:${NC}"
echo -e "      cd $WEB_ROOT"
echo -e "      ./pt24-generate-single.sh mechanik warszawa"
echo ""
echo -e "   ${YELLOW}3. Generate bulk pages:${NC}"
echo -e "      ./pt24-generate-bulk.sh pt24-sample-pages.csv"
echo ""
echo -e "   ${YELLOW}4. Check generated pages:${NC}"
echo -e "      wp post list --post_type=pt24_local --allow-root"
echo ""

echo -e "${GREEN}📖 DOCUMENTATION:${NC}"
echo -e "   Blueprint:     PT24-PRO-PLATFORM-BLUEPRINT.md"
echo -e "   SEO Phrases:   PT24-SEO-PHRASES.md"
echo -e "   Implementation: PT24-URL-STRUCTURE-IMPLEMENTATION.md"
echo -e "   Automation:    PT24-AUTOMATION-GUIDE.md"
echo -e "   Quick Start:   PT24-QUICK-START-SUMMARY.md"

echo -e "\n${GREEN}💰 MONETIZATION:${NC}"
echo -e "   Free Plan:     Basic listing"
echo -e "   PRO (79 zł):   Subdomain + premium features"
echo -e "   PREMIUM (149): Top placement + badge"

echo -e "\n${GREEN}🎯 NEXT STEPS:${NC}"
echo -e "   1. Generate your first 10-20 pages"
echo -e "   2. Test URLs: https://pt24.pro/mechanik/warszawa/"
echo -e "   3. Add business profiles"
echo -e "   4. Configure monetization"
echo -e "   5. Scale to 500+ pages"

echo -e "\n${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✨ Ready to build Poland's #1 local services platform! ✨${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}\n"

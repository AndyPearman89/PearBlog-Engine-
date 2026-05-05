#!/bin/bash
##
## PT24.PRO Production Build Script
## Minifies CSS and JS assets for production deployment
##
## Version: 1.0.0
## Date: 2026-05-04
##

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Directories
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
THEME_DIR="$PROJECT_ROOT/theme/pearblog-theme"
ASSETS_DIR="$THEME_DIR/assets"
BUILD_DIR="$THEME_DIR/assets/build"

echo -e "${BLUE}================================${NC}"
echo -e "${BLUE}PT24.PRO Production Build${NC}"
echo -e "${BLUE}================================${NC}"
echo ""

# Create build directory
mkdir -p "$BUILD_DIR/css"
mkdir -p "$BUILD_DIR/js"

## Minify CSS Files
echo -e "${YELLOW}Minifying CSS files...${NC}"

CSS_FILES=(
    "pt24-home-v4.css"
    "pt24-landing.css"
    "pt24-cta.css"
)

for file in "${CSS_FILES[@]}"; do
    if [ -f "$ASSETS_DIR/css/$file" ]; then
        # Simple minification (remove comments, extra spaces, newlines)
        sed 's|/\*[^*]*\*\+\([^/*][^*]*\*\+\)*/||g' "$ASSETS_DIR/css/$file" | \
        tr -d '\n' | \
        sed 's/  */ /g' | \
        sed 's/ *{ */{/g' | \
        sed 's/ *} */}/g' | \
        sed 's/ *: */:/g' | \
        sed 's/ *; */;/g' | \
        sed 's/ *, */,/g' \
        > "$BUILD_DIR/css/${file%.css}.min.css"

        ORIGINAL_SIZE=$(stat -f%z "$ASSETS_DIR/css/$file" 2>/dev/null || stat -c%s "$ASSETS_DIR/css/$file" 2>/dev/null)
        MINIFIED_SIZE=$(stat -f%z "$BUILD_DIR/css/${file%.css}.min.css" 2>/dev/null || stat -c%s "$BUILD_DIR/css/${file%.css}.min.css" 2>/dev/null)
        SAVINGS=$((ORIGINAL_SIZE - MINIFIED_SIZE))
        PERCENT=$((SAVINGS * 100 / ORIGINAL_SIZE))

        echo -e "${GREEN}  ✓ $file → ${file%.css}.min.css (saved ${PERCENT}%)${NC}"
    else
        echo -e "${RED}  ✗ $file not found${NC}"
    fi
done
echo ""

## Minify JS Files
echo -e "${YELLOW}Minifying JS files...${NC}"

JS_FILES=(
    "pt24-home-v4.js"
    "pt24-landing.js"
    "pt24-cta-tracking.js"
)

for file in "${JS_FILES[@]}"; do
    if [ -f "$ASSETS_DIR/js/$file" ]; then
        # Simple minification (remove comments, extra spaces)
        sed 's|//.*$||g' "$ASSETS_DIR/js/$file" | \
        sed 's|/\*[^*]*\*\+\([^/*][^*]*\*\+\)*/||g' | \
        tr -d '\n' | \
        sed 's/  */ /g' \
        > "$BUILD_DIR/js/${file%.js}.min.js"

        ORIGINAL_SIZE=$(stat -f%z "$ASSETS_DIR/js/$file" 2>/dev/null || stat -c%s "$ASSETS_DIR/js/$file" 2>/dev/null)
        MINIFIED_SIZE=$(stat -f%z "$BUILD_DIR/js/${file%.js}.min.js" 2>/dev/null || stat -c%s "$BUILD_DIR/js/${file%.js}.min.js" 2>/dev/null)
        SAVINGS=$((ORIGINAL_SIZE - MINIFIED_SIZE))
        PERCENT=$((SAVINGS * 100 / ORIGINAL_SIZE))

        echo -e "${GREEN}  ✓ $file → ${file%.js}.min.js (saved ${PERCENT}%)${NC}"
    else
        echo -e "${RED}  ✗ $file not found${NC}"
    fi
done
echo ""

## Generate manifest
echo -e "${YELLOW}Generating asset manifest...${NC}"
cat > "$BUILD_DIR/manifest.json" << EOF
{
    "generated": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
    "version": "4.0.0",
    "css": {
$(ls "$BUILD_DIR/css/"*.min.css 2>/dev/null | while read file; do
    basename=$(basename "$file")
    size=$(stat -f%z "$file" 2>/dev/null || stat -c%s "$file" 2>/dev/null)
    echo "        \"$basename\": { \"size\": $size },"
done | sed '$ s/,$//')
    },
    "js": {
$(ls "$BUILD_DIR/js/"*.min.js 2>/dev/null | while read file; do
    basename=$(basename "$file")
    size=$(stat -f%z "$file" 2>/dev/null || stat -c%s "$file" 2>/dev/null)
    echo "        \"$basename\": { \"size\": $size },"
done | sed '$ s/,$//')
    }
}
EOF
echo -e "${GREEN}✓ Manifest created: $BUILD_DIR/manifest.json${NC}"
echo ""

## Summary
echo -e "${BLUE}================================${NC}"
echo -e "${GREEN}✓ Production build complete${NC}"
echo -e "${BLUE}================================${NC}"
echo ""
echo -e "Minified assets location: ${YELLOW}$BUILD_DIR${NC}"
echo ""
echo -e "To use minified assets in production:"
echo -e "  1. Update functions.php to load from /assets/build/"
echo -e "  2. Test minified assets: ${YELLOW}View page source and check file sizes${NC}"
echo -e "  3. Clear CDN cache if using CDN"
echo ""
echo -e "${YELLOW}Note: For production use, consider using proper build tools like:${NC}"
echo -e "  - webpack, rollup, or parcel for JS"
echo -e "  - cssnano or clean-css for CSS"
echo -e "  - This script provides basic minification${NC}"

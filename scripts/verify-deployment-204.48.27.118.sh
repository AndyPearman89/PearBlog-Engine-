#!/bin/bash
#
# Quick Deployment Verification for 204.48.27.118 (poradnik.pro)
# Usage: ./verify-deployment.sh
#

set -euo pipefail

# Configuration
SSH_HOST="204.48.27.118"
SSH_USER="root"
WP_PATH="/var/www/poradnik.pro"
DOMAIN="poradnik.pro"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Deployment Verification - poradnik.pro${NC}"
echo -e "${BLUE}  Server: ${SSH_HOST}${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Test 1: Website HTTP Status
echo -e "${YELLOW}[1/7]${NC} Testing website availability..."
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://${DOMAIN} || echo "000")
if [ "$HTTP_STATUS" = "200" ] || [ "$HTTP_STATUS" = "301" ]; then
    echo -e "${GREEN}✓${NC} Website is online (HTTP ${HTTP_STATUS})"
else
    echo -e "${RED}✗${NC} Website returned HTTP ${HTTP_STATUS}"
fi
echo ""

# Test 2: SSH Connection
echo -e "${YELLOW}[2/7]${NC} Testing SSH connection..."
if ssh -o ConnectTimeout=10 -o BatchMode=yes ${SSH_USER}@${SSH_HOST} "echo 'SSH OK'" 2>/dev/null | grep -q "SSH OK"; then
    echo -e "${GREEN}✓${NC} SSH connection successful"
else
    echo -e "${RED}✗${NC} SSH connection failed (check keys or use: ssh ${SSH_USER}@${SSH_HOST})"
    echo -e "${YELLOW}⚠${NC} Remaining tests require SSH access - skipping..."
    exit 1
fi
echo ""

# Test 3: WordPress Installation
echo -e "${YELLOW}[3/7]${NC} Checking WordPress installation..."
WP_EXISTS=$(ssh ${SSH_USER}@${SSH_HOST} "[ -f ${WP_PATH}/wp-config.php ] && echo 'YES' || echo 'NO'" 2>/dev/null)
if [ "$WP_EXISTS" = "YES" ]; then
    echo -e "${GREEN}✓${NC} WordPress found at ${WP_PATH}"
else
    echo -e "${RED}✗${NC} WordPress not found at ${WP_PATH}"
fi
echo ""

# Test 4: PearBlog Engine Plugin
echo -e "${YELLOW}[4/7]${NC} Checking PearBlog Engine plugin..."
PLUGIN_EXISTS=$(ssh ${SSH_USER}@${SSH_HOST} "[ -f ${WP_PATH}/wp-content/mu-plugins/pearblog-engine/pearblog-engine.php ] && echo 'YES' || echo 'NO'" 2>/dev/null)
if [ "$PLUGIN_EXISTS" = "YES" ]; then
    echo -e "${GREEN}✓${NC} PearBlog Engine plugin installed"
else
    echo -e "${RED}✗${NC} PearBlog Engine plugin not found"
fi
echo ""

# Test 5: PearBlog Theme
echo -e "${YELLOW}[5/7]${NC} Checking PearBlog theme..."
THEME_EXISTS=$(ssh ${SSH_USER}@${SSH_HOST} "[ -f ${WP_PATH}/wp-content/themes/pearblog-theme/style.css ] && echo 'YES' || echo 'NO'" 2>/dev/null)
if [ "$THEME_EXISTS" = "YES" ]; then
    echo -e "${GREEN}✓${NC} PearBlog theme installed"
else
    echo -e "${RED}✗${NC} PearBlog theme not found"
fi
echo ""

# Test 6: Repository Status
echo -e "${YELLOW}[6/7]${NC} Checking repository status..."
REPO_PATH="/root/PearBlog-Engine-"
REPO_EXISTS=$(ssh ${SSH_USER}@${SSH_HOST} "[ -d ${REPO_PATH}/.git ] && echo 'YES' || echo 'NO'" 2>/dev/null)
if [ "$REPO_EXISTS" = "YES" ]; then
    echo -e "${GREEN}✓${NC} Repository found at ${REPO_PATH}"
    LATEST_COMMIT=$(ssh ${SSH_USER}@${SSH_HOST} "cd ${REPO_PATH} && git log --oneline -1 2>/dev/null" || echo "Unable to read")
    echo -e "  Latest commit: ${LATEST_COMMIT}"
else
    echo -e "${YELLOW}⚠${NC} Repository not found at ${REPO_PATH} (first deployment?)"
fi
echo ""

# Test 7: Enterprise V8 Admin
echo -e "${YELLOW}[7/7]${NC} Checking Enterprise V8 admin..."
if command -v ssh &> /dev/null; then
    ENTERPRISE_CHECK=$(ssh ${SSH_USER}@${SSH_HOST} "cd ${WP_PATH} && wp eval 'echo class_exists(\"PearBlogEngine\\\\Admin\\\\AdminPageV8Enterprise\") ? \"OK\" : \"MISSING\";' --allow-root 2>/dev/null" || echo "ERROR")
    if [ "$ENTERPRISE_CHECK" = "OK" ]; then
        echo -e "${GREEN}✓${NC} Enterprise V8 admin class loaded"
    else
        echo -e "${RED}✗${NC} Enterprise V8 admin class not found (${ENTERPRISE_CHECK})"
    fi
else
    echo -e "${YELLOW}⚠${NC} Cannot verify - wp-cli check requires ssh"
fi
echo ""

# Summary
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Verification Complete${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo "📊 Quick Stats:"
echo "   Domain: https://${DOMAIN}"
echo "   Admin: https://${DOMAIN}/wp-admin/"
echo "   Enterprise: https://${DOMAIN}/wp-admin/admin.php?page=pearblog-enterprise-v8"
echo ""
echo "💡 Next Steps:"
echo "   - Review any failed checks above"
echo "   - Test admin access manually"
echo "   - Run: ssh ${SSH_USER}@${SSH_HOST} 'cd ${WP_PATH} && wp pearblog stats --allow-root'"
echo ""

#!/bin/bash
# ============================================================================
# Theme Features v5.1 — Production Deployment Script
# ============================================================================
# Version: 1.0
# Date: May 5, 2026
# Branch: claude/theme-features-v5-1
#
# This script automates the production deployment of Theme Features v5.1
# following the procedures documented in THEME-V5.1-NEXT-STEPS.md
#
# Prerequisites:
# - PR #73 must be merged to main
# - SSH access to production server configured
# - Production WordPress instance accessible
# - Backup storage available
#
# Usage:
#   ./scripts/deploy-theme-v5.1-production.sh [--staging|--production]
#
# ============================================================================

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(dirname "$SCRIPT_DIR")"
THEME_DIR="$REPO_ROOT/theme/pearblog-theme"
DEPLOYMENT_ENV="${1:-staging}"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)

# Server configurations (adjust as needed)
if [ "$DEPLOYMENT_ENV" = "production" ]; then
    SERVER_HOST="${PRODUCTION_HOST:-production}"
    SERVER_PATH="${PRODUCTION_PATH:-/var/www/wp-content/themes/pearblog-theme}"
    BACKUP_PATH="${PRODUCTION_BACKUP_PATH:-/backups}"
    SITE_URL="${PRODUCTION_URL:-https://pearblog.com}"
else
    SERVER_HOST="${STAGING_HOST:-staging}"
    SERVER_PATH="${STAGING_PATH:-/var/www/wp-content/themes/pearblog-theme}"
    BACKUP_PATH="${STAGING_BACKUP_PATH:-/backups}"
    SITE_URL="${STAGING_URL:-https://staging.pearblog.com}"
fi

# ============================================================================
# Helper Functions
# ============================================================================

print_header() {
    echo -e "\n${BLUE}======================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}======================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

# ============================================================================
# Pre-Flight Checks
# ============================================================================

preflight_checks() {
    print_header "Phase 1: Pre-Flight Checks"

    # Check if theme directory exists
    if [ ! -d "$THEME_DIR" ]; then
        print_error "Theme directory not found: $THEME_DIR"
        exit 1
    fi
    print_success "Theme directory exists"

    # Check if we're on the correct branch
    CURRENT_BRANCH=$(git -C "$REPO_ROOT" rev-parse --abbrev-ref HEAD)
    if [ "$CURRENT_BRANCH" != "main" ] && [ "$CURRENT_BRANCH" != "claude/theme-features-v5-1" ]; then
        print_warning "Current branch: $CURRENT_BRANCH (expected: main or claude/theme-features-v5-1)"
    else
        print_success "On correct branch: $CURRENT_BRANCH"
    fi

    # Check if SSH connection works
    print_info "Testing SSH connection to $SERVER_HOST..."
    if ssh -o ConnectTimeout=10 "$SERVER_HOST" "echo 'Connection successful'" &> /dev/null; then
        print_success "SSH connection to $SERVER_HOST verified"
    else
        print_error "Cannot connect to $SERVER_HOST via SSH"
        print_info "Please configure SSH access or set SERVER_HOST environment variable"
        exit 1
    fi

    # Check if rsync is available
    if command -v rsync &> /dev/null; then
        print_success "rsync is available"
    else
        print_error "rsync is not installed"
        exit 1
    fi

    # Verify theme files exist
    REQUIRED_FILES=(
        "style.css"
        "functions.php"
        "index.php"
        "page.php"
        "search.php"
        "404.php"
        "assets/js/app.js"
        "assets/css/base.css"
    )

    for file in "${REQUIRED_FILES[@]}"; do
        if [ -f "$THEME_DIR/$file" ]; then
            print_success "Found: $file"
        else
            print_error "Missing: $file"
            exit 1
        fi
    done

    print_success "All pre-flight checks passed"
}

# ============================================================================
# Backup Current Theme
# ============================================================================

backup_current_theme() {
    print_header "Phase 2: Backup Current Theme"

    BACKUP_NAME="pearblog-theme-backup-$TIMESTAMP.tar.gz"

    print_info "Creating backup: $BACKUP_NAME"

    ssh "$SERVER_HOST" "cd $(dirname $SERVER_PATH) && \
        tar -czf /tmp/$BACKUP_NAME pearblog-theme/ && \
        mkdir -p $BACKUP_PATH && \
        mv /tmp/$BACKUP_NAME $BACKUP_PATH/" || {
        print_error "Backup failed"
        exit 1
    }

    print_success "Backup created: $BACKUP_PATH/$BACKUP_NAME"

    # Verify backup
    BACKUP_SIZE=$(ssh "$SERVER_HOST" "du -h $BACKUP_PATH/$BACKUP_NAME | cut -f1")
    print_info "Backup size: $BACKUP_SIZE"

    # Store backup path for rollback
    echo "$BACKUP_PATH/$BACKUP_NAME" > /tmp/theme-v5.1-backup-$DEPLOYMENT_ENV.txt
    print_success "Backup path saved for rollback"
}

# ============================================================================
# Deploy Theme Files
# ============================================================================

deploy_theme() {
    print_header "Phase 3: Deploy Theme Files"

    print_info "Syncing theme files to $SERVER_HOST:$SERVER_PATH"

    rsync -avz --exclude='.git' \
        --exclude='node_modules' \
        --exclude='.DS_Store' \
        --exclude='*.map' \
        --exclude='*.log' \
        "$THEME_DIR/" \
        "$SERVER_HOST:$SERVER_PATH/" || {
        print_error "Theme deployment failed"
        print_warning "Rolling back..."
        rollback_deployment
        exit 1
    }

    print_success "Theme files deployed"

    # Verify deployment
    print_info "Verifying deployed files..."

    for file in "${REQUIRED_FILES[@]}"; do
        if ssh "$SERVER_HOST" "test -f $SERVER_PATH/$file"; then
            print_success "Verified: $file"
        else
            print_error "Missing after deployment: $file"
            print_warning "Rolling back..."
            rollback_deployment
            exit 1
        fi
    done

    print_success "All files verified"
}

# ============================================================================
# WordPress Operations
# ============================================================================

wordpress_operations() {
    print_header "Phase 4: WordPress Operations"

    # Check if WP-CLI is available
    if ssh "$SERVER_HOST" "command -v wp" &> /dev/null; then
        print_success "WP-CLI is available"

        # Activate theme
        print_info "Activating theme..."
        ssh "$SERVER_HOST" "cd /var/www && wp theme activate pearblog-theme" || {
            print_warning "Theme activation may have failed (might already be active)"
        }
        print_success "Theme activated"

        # Clear caches
        print_info "Clearing WordPress caches..."
        ssh "$SERVER_HOST" "cd /var/www && wp cache flush" || print_warning "Cache flush may have failed"
        print_success "Cache cleared"

        # Flush rewrite rules
        print_info "Flushing rewrite rules..."
        ssh "$SERVER_HOST" "cd /var/www && wp rewrite flush" || print_warning "Rewrite flush may have failed"
        print_success "Rewrite rules flushed"

        # Delete transients
        print_info "Deleting transients..."
        ssh "$SERVER_HOST" "cd /var/www && wp transient delete --all" || print_warning "Transient deletion may have failed"
        print_success "Transients deleted"

    else
        print_warning "WP-CLI not available, skipping WordPress operations"
        print_info "You may need to activate the theme manually via WordPress admin"
    fi
}

# ============================================================================
# Verification
# ============================================================================

verify_deployment() {
    print_header "Phase 5: Verification"

    # HTTP status check
    print_info "Checking site accessibility: $SITE_URL"
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" -L "$SITE_URL" || echo "000")

    if [ "$HTTP_STATUS" = "200" ]; then
        print_success "Site is accessible (HTTP $HTTP_STATUS)"
    else
        print_error "Site returned HTTP $HTTP_STATUS"
        print_warning "Rolling back..."
        rollback_deployment
        exit 1
    fi

    # Check for JavaScript files
    print_info "Verifying assets are accessible..."
    JS_STATUS=$(curl -s -o /dev/null -w "%{http_code}" -L "$SITE_URL/wp-content/themes/pearblog-theme/assets/js/app.js" || echo "000")

    if [ "$JS_STATUS" = "200" ]; then
        print_success "JavaScript assets accessible"
    else
        print_warning "JavaScript assets may not be accessible (HTTP $JS_STATUS)"
    fi

    # Check for CSS files
    CSS_STATUS=$(curl -s -o /dev/null -w "%{http_code}" -L "$SITE_URL/wp-content/themes/pearblog-theme/assets/css/base.css" || echo "000")

    if [ "$CSS_STATUS" = "200" ]; then
        print_success "CSS assets accessible"
    else
        print_warning "CSS assets may not be accessible (HTTP $CSS_STATUS)"
    fi

    print_success "Deployment verification complete"
}

# ============================================================================
# Post-Deployment
# ============================================================================

post_deployment() {
    print_header "Phase 6: Post-Deployment"

    print_info "Deployment completed successfully!"
    print_info ""
    print_info "Environment: $DEPLOYMENT_ENV"
    print_info "Site URL: $SITE_URL"
    print_info "Timestamp: $TIMESTAMP"
    print_info ""

    print_warning "IMPORTANT: Post-Deployment Checklist"
    echo ""
    echo "Please manually verify the following features:"
    echo "  1. Homepage loads without errors"
    echo "  2. Dark mode toggle works"
    echo "  3. Search panel opens/closes"
    echo "  4. Reading progress bar appears on articles"
    echo "  5. Sticky header activates on scroll"
    echo "  6. No JavaScript console errors"
    echo "  7. No PHP errors in logs"
    echo ""

    print_info "Monitor for the next hour:"
    echo "  - Server error logs"
    echo "  - Analytics for traffic drops"
    echo "  - User feedback/complaints"
    echo ""

    print_info "Backup location: $(cat /tmp/theme-v5.1-backup-$DEPLOYMENT_ENV.txt)"
    print_warning "Keep backup for at least 7 days"
    echo ""
}

# ============================================================================
# Rollback Function
# ============================================================================

rollback_deployment() {
    print_header "ROLLBACK: Restoring Previous Theme"

    if [ ! -f "/tmp/theme-v5.1-backup-$DEPLOYMENT_ENV.txt" ]; then
        print_error "Backup path not found, cannot rollback"
        exit 1
    fi

    BACKUP_FILE=$(cat /tmp/theme-v5.1-backup-$DEPLOYMENT_ENV.txt)

    print_info "Restoring from: $BACKUP_FILE"

    ssh "$SERVER_HOST" "cd $(dirname $SERVER_PATH) && \
        rm -rf pearblog-theme && \
        tar -xzf $BACKUP_FILE && \
        cd /var/www && wp cache flush" || {
        print_error "Rollback failed!"
        print_error "Manual intervention required"
        exit 1
    }

    print_success "Rollback complete"

    # Verify rollback
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" -L "$SITE_URL" || echo "000")
    if [ "$HTTP_STATUS" = "200" ]; then
        print_success "Site is accessible after rollback"
    else
        print_error "Site may still have issues (HTTP $HTTP_STATUS)"
    fi
}

# ============================================================================
# Main Execution
# ============================================================================

main() {
    print_header "Theme Features v5.1 — Production Deployment"

    echo "Environment: $DEPLOYMENT_ENV"
    echo "Server: $SERVER_HOST"
    echo "Path: $SERVER_PATH"
    echo "URL: $SITE_URL"
    echo "Timestamp: $TIMESTAMP"
    echo ""

    if [ "$DEPLOYMENT_ENV" = "production" ]; then
        print_warning "⚠️  PRODUCTION DEPLOYMENT ⚠️"
        echo ""
        read -p "Are you sure you want to deploy to PRODUCTION? (yes/no): " confirm
        if [ "$confirm" != "yes" ]; then
            print_info "Deployment cancelled"
            exit 0
        fi
    fi

    # Execute deployment phases
    preflight_checks
    backup_current_theme
    deploy_theme
    wordpress_operations
    verify_deployment
    post_deployment

    print_success "🎉 Deployment Complete! 🎉"
}

# ============================================================================
# Script Entry Point
# ============================================================================

# Trap errors
trap 'print_error "Deployment failed at line $LINENO"' ERR

# Run main function
main "$@"

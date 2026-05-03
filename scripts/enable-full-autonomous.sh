#!/bin/bash

set -euo pipefail

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

WP_PATH="/var/www/poradnik.pro"
WP_URL=""
PUBLISH_RATE="1"
LANGUAGE="pl"
ENABLE_IMAGES="1"
AUTOPILOT_MODE="enterprise"
AUTOPILOT_TASKS="all"

print_step() {
    echo -e "${BLUE}==>${NC} $1"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

usage() {
    cat <<EOF
Usage: ./scripts/enable-full-autonomous.sh [options]

Options:
  --wp-path=PATH         WordPress path (default: /var/www/poradnik.pro)
  --url=URL              Site URL for multisite/per-site WP-CLI context
  --publish-rate=N       Articles per hour (default: 1)
  --language=CODE        Content language (default: pl)
  --disable-images       Disable AI image generation
  --mode=MODE            Autopilot mode: enterprise|standard (default: enterprise)
  --tasks=LIST           Autopilot tasks filter (default: all)
  --help                 Show this help
EOF
}

for arg in "$@"; do
    case "$arg" in
        --wp-path=*)
            WP_PATH="${arg#*=}"
            ;;
        --url=*)
            WP_URL="${arg#*=}"
            ;;
        --publish-rate=*)
            PUBLISH_RATE="${arg#*=}"
            ;;
        --language=*)
            LANGUAGE="${arg#*=}"
            ;;
        --disable-images)
            ENABLE_IMAGES="0"
            ;;
        --mode=*)
            AUTOPILOT_MODE="${arg#*=}"
            ;;
        --tasks=*)
            AUTOPILOT_TASKS="${arg#*=}"
            ;;
        --help|-h)
            usage
            exit 0
            ;;
        *)
            print_error "Unknown argument: $arg"
            usage
            exit 1
            ;;
    esac
done

if ! command -v wp >/dev/null 2>&1; then
    print_error "WP-CLI is not installed"
    exit 1
fi

if [[ ! -d "$WP_PATH" ]]; then
    print_error "WordPress path does not exist: $WP_PATH"
    exit 1
fi

if ! [[ "$PUBLISH_RATE" =~ ^[0-9]+$ ]] || [[ "$PUBLISH_RATE" -lt 1 ]]; then
    print_error "publish-rate must be an integer >= 1"
    exit 1
fi

if [[ "$AUTOPILOT_MODE" != "enterprise" && "$AUTOPILOT_MODE" != "standard" ]]; then
    print_error "mode must be enterprise or standard"
    exit 1
fi

cd "$WP_PATH"

WP_CMD=(wp --path="$WP_PATH" --allow-root)

if [[ -n "$WP_URL" ]]; then
    WP_CMD+=(--url="$WP_URL")
fi

print_step "Checking WordPress installation"
"${WP_CMD[@]}" core is-installed >/dev/null
print_success "WordPress installation detected"

print_step "Enabling autonomous pipeline"
"${WP_CMD[@]}" option update pearblog_autonomous_mode "1" >/dev/null
"${WP_CMD[@]}" option update pearblog_publish_rate "$PUBLISH_RATE" >/dev/null
"${WP_CMD[@]}" option update pearblog_language "$LANGUAGE" >/dev/null
"${WP_CMD[@]}" option update pearblog_enable_image_generation "$ENABLE_IMAGES" >/dev/null
"${WP_CMD[@]}" option update pearblog_ai_images_enabled "$ENABLE_IMAGES" >/dev/null
print_success "Autonomous options updated"

print_step "Checking topic queue"
QUEUE_COUNT=$("${WP_CMD[@]}" eval 'echo (new \PearBlogEngine\Content\TopicQueue(get_current_blog_id()))->count();' 2>/dev/null || echo 0)
if [[ "$QUEUE_COUNT" =~ ^[0-9]+$ ]] && [[ "$QUEUE_COUNT" -gt 0 ]]; then
    print_success "Topic queue is present (${QUEUE_COUNT} topics)"
else
    print_warning "Topic queue looks empty. Autonomy is enabled, but pipeline may idle until topics are added."
fi

print_step "Starting autopilot"
AUTOPILOT_OUTPUT=$("${WP_CMD[@]}" pearblog autopilot start --mode="$AUTOPILOT_MODE" --tasks="$AUTOPILOT_TASKS" 2>&1) || AUTOPILOT_EXIT=$?
AUTOPILOT_EXIT=${AUTOPILOT_EXIT:-0}

if [[ "$AUTOPILOT_EXIT" -eq 0 ]]; then
    printf '%s\n' "$AUTOPILOT_OUTPUT"
    print_success "Autopilot started"
elif [[ "$AUTOPILOT_OUTPUT" == *"Autopilot is already running."* ]]; then
    printf '%s\n' "$AUTOPILOT_OUTPUT"
    print_success "Autopilot is already running"
else
    print_warning "WP-CLI autopilot subcommand failed, trying direct runner fallback"
    printf '%s\n' "$AUTOPILOT_OUTPUT"
    "${WP_CMD[@]}" eval "var_export(\\PearBlogEngine\\CLI\\AutopilotRunner::start('$AUTOPILOT_MODE', '$AUTOPILOT_TASKS'));"
fi

print_step "Current autonomous mode"
"${WP_CMD[@]}" option get pearblog_autonomous_mode

print_step "Current autopilot status"
if ! "${WP_CMD[@]}" pearblog autopilot status; then
    print_warning "WP-CLI autopilot status failed, reading stored state directly"
    "${WP_CMD[@]}" option get pearblog_autopilot_state --format=json
fi

print_step "Scheduled PearBlog cron events"
"${WP_CMD[@]}" cron event list | grep pearblog || print_warning "No PearBlog cron events found"

if [[ "$ENABLE_IMAGES" == "1" ]]; then
    print_success "Full autonomous mode is enabled with AI image generation"
else
    print_success "Full autonomous mode is enabled without AI image generation"
fi

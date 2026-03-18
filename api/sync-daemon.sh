#!/bin/bash
# sync-daemon.sh
# Runs app:tournament:sync every 30 seconds while active games exist.
# Managed by cron.sh — do not start directly.

API_DIR="$(cd "$(dirname "$0")" && pwd)"
PID_FILE="$API_DIR/var/sync-daemon.pid"
LOG_FILE="$API_DIR/var/log/sync-daemon.log"
PHP=$(which php)
CONSOLE="$API_DIR/bin/console"
INTERVAL=30

mkdir -p "$(dirname "$LOG_FILE")"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Write our PID
echo $$ > "$PID_FILE"
log "Daemon started (PID $$)"

cleanup() {
    log "Daemon stopping (PID $$)"
    rm -f "$PID_FILE"
    exit 0
}
trap cleanup SIGTERM SIGINT

while true; do
    # Check if we should still be running
    if ! $PHP "$CONSOLE" app:tournament:has-active-games --quiet 2>/dev/null; then
        log "No active games — daemon exiting"
        cleanup
    fi

    log "Running sync..."
    $PHP "$CONSOLE" app:tournament:sync \
        --start=20260317 \
        --end=20260407 \
        --no-interaction \
        --quiet 2>>"$LOG_FILE" && log "Sync OK" || log "Sync FAILED"

    $PHP "$CONSOLE" app:odds:lock \
        --no-interaction \
        --quiet 2>>"$LOG_FILE"

    sleep $INTERVAL
done

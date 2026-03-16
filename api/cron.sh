#!/bin/bash
# cron.sh
# Run every minute via crontab:
#   * * * * * /path/to/api/cron.sh >> /path/to/api/var/log/cron.log 2>&1

API_DIR="$(cd "$(dirname "$0")" && pwd)"
PID_FILE="$API_DIR/var/sync-daemon.pid"
DAEMON="$API_DIR/sync-daemon.sh"
PHP=$(which php)
CONSOLE="$API_DIR/bin/console"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

# Check if active games exist
if $PHP "$CONSOLE" app:tournament:has-active-games --quiet 2>/dev/null; then
    GAMES_ACTIVE=true
else
    GAMES_ACTIVE=false
fi

# Check if daemon is already running and healthy
DAEMON_RUNNING=false
if [ -f "$PID_FILE" ]; then
    DAEMON_PID=$(cat "$PID_FILE")
    if kill -0 "$DAEMON_PID" 2>/dev/null; then
        DAEMON_RUNNING=true
    else
        # Stale PID file
        log "Removing stale PID file (PID $DAEMON_PID no longer exists)"
        rm -f "$PID_FILE"
    fi
fi

if $GAMES_ACTIVE && ! $DAEMON_RUNNING; then
    log "Active games found — starting sync daemon"
    nohup "$DAEMON" >> "$API_DIR/var/log/sync-daemon.log" 2>&1 &
    log "Daemon started with PID $!"

elif ! $GAMES_ACTIVE && $DAEMON_RUNNING; then
    DAEMON_PID=$(cat "$PID_FILE")
    log "No active games — stopping sync daemon (PID $DAEMON_PID)"
    kill "$DAEMON_PID"

elif $GAMES_ACTIVE && $DAEMON_RUNNING; then
    DAEMON_PID=$(cat "$PID_FILE")
    log "Daemon running (PID $DAEMON_PID) — OK"

else
    log "No active games, no daemon — nothing to do"
fi

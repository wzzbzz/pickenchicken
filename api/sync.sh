#!/bin/bash
# Picken Chicken — tournament sync cron script
# Runs: app:tournament:sync  (update game results, score picks, materialise round results)
#       app:odds:lock         (lock markets and generate chicken picks at 10am game day)
#
# Suggested crontab entries:
#   Every 5 minutes during tournament (March-April):
#   */5 * 18-21 3-4 * /path/to/sync.sh >> /var/log/pickenchicken-sync.log 2>&1
#
#   Or simpler — every 5 minutes always:
#   */5 * * * * /path/to/sync.sh >> /var/log/pickenchicken-sync.log 2>&1

set -e

API_DIR="$(cd "$(dirname "$0")" && pwd)"
PHP=$(which php)
CONSOLE="$API_DIR/bin/console"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting sync"

# Sync game results, score picks, materialise round results
$PHP "$CONSOLE" app:tournament:sync \
    --start=20250318 \
    --end=20250407 \
    --no-interaction \
    --quiet

# Lock markets and generate chicken picks for games whose 10am has passed
$PHP "$CONSOLE" app:odds:lock \
    --no-interaction \
    --quiet

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Sync complete"

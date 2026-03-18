#!/bin/bash
# build.sh — Build frontend for production and rsync to server
# Usage: bash deploy/build.sh
set -e

FRONTEND="/Users/jamespwilliams/Ampelos/greenhouse/PickenChicken/frontend"
SERVER="sysop@68.183.99.32"
REMOTE="/var/www/pickenchicken.com/frontend/build/"

echo "=== Building frontend for production ==="
cd "$FRONTEND"
rm -rf build
REACT_APP_API_URL=https://api.pickenchicken.com REACT_APP_DEV_MODE=false npm run build

echo "=== Verifying build ==="
if grep -q "127.0.0.1" build/static/js/main.*.js; then
  echo "ERROR: build contains localhost URLs — aborting rsync"
  exit 1
fi
echo "Build is clean."

echo "=== Rsyncing to server ==="
rsync -avz --checksum build/ "$SERVER:$REMOTE"

echo "=== Done ==="

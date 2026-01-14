#!/bin/bash
# Script to check if .archive folder exists and verify its contents

echo "=== Checking Archive Folder ==="
echo ""

echo "1. Checking if .archive folder exists for user 'hemmi':"
docker exec nextcloud_nextcloud_app ls -la /var/www/html/data/hemmi/files/.archive 2>/dev/null || echo "   Folder not found at expected location"

echo ""
echo "2. Finding .archive folders:"
docker exec nextcloud_nextcloud_app find /var/www/html/data -type d -name ".archive" 2>/dev/null

echo ""
echo "3. Checking folder contents (if found):"
docker exec nextcloud_nextcloud_app find /var/www/html/data -type d -name ".archive" -exec sh -c 'echo "Folder: $1"; ls -la "$1" | head -10' _ {} \; 2>/dev/null

echo ""
echo "4. To access the folder in web UI, try:"
echo "   - Direct URL: https://your-nextcloud.com/index.php/apps/files/?dir=/.archive"
echo "   - Or navigate to: Files app → Look for .archive folder"
echo ""
echo "Note: Dot-prefixed folders are visible in web UI but may require:"
echo "  - Page refresh"
echo "  - Clearing browser cache"
echo "  - Or accessing via direct URL"

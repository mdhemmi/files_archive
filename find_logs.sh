#!/bin/bash
# Script to find and check Nextcloud application logs

echo "=== Finding Nextcloud Log Files ==="
echo ""

echo "1. Checking common Nextcloud log locations..."
echo ""

# Check inside the container
echo "Looking for log files in container:"
docker exec nextcloud_nextcloud_app find /var/www/html/data -name "*.log" -type f 2>/dev/null | head -5

echo ""
echo "2. Checking Nextcloud data directory structure:"
docker exec nextcloud_nextcloud_app ls -la /var/www/html/data/ 2>/dev/null | head -10

echo ""
echo "3. Checking for nextcloud.log:"
docker exec nextcloud_nextcloud_app ls -la /var/www/html/data/nextcloud.log 2>/dev/null || echo "   Not found in default location"

echo ""
echo "4. Checking PHP error log:"
docker exec nextcloud_nextcloud_app ls -la /var/log/php* 2>/dev/null || echo "   Not found in /var/log/"

echo ""
echo "5. To check the actual Nextcloud log file, try:"
echo "   docker exec nextcloud_nextcloud_app tail -100 /var/www/html/data/nextcloud.log | grep -i archive"
echo ""
echo "   Or if it's in a different location:"
echo "   docker exec nextcloud_nextcloud_app find /var/www/html -name '*.log' -exec tail -50 {} \; | grep -i archive"

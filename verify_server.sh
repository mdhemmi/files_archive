#!/bin/bash
# Script to verify what's on the server
# Run this ON THE SERVER at /opt/stacks/nextcloud/apps/time_archive

echo "=== Verifying Server Installation ==="
echo ""

cd /opt/stacks/nextcloud/apps/time_archive || exit 1

echo "1. Checking js/ directory..."
if [ -d "js" ]; then
    echo "   ✓ js/ directory exists"
    JS_FILES=$(ls -1 js/*.js 2>/dev/null | grep -v ".map\|LICENSE" | wc -l | tr -d ' ')
    echo "   JS files found: $JS_FILES"
    
    echo ""
    echo "   Required files:"
    for file in time_archive-main.js time_archive-navigation.js time_archive-archive.js time_archive-archiveLink.js; do
        if [ -f "js/$file" ]; then
            SIZE=$(ls -lh "js/$file" | awk '{print $5}')
            echo "   ✓ $file ($SIZE)"
        else
            echo "   ✗ $file MISSING"
        fi
    done
else
    echo "   ✗ js/ directory NOT found"
fi

echo ""
echo "2. Checking appinfo/info.xml..."
if [ -f "appinfo/info.xml" ]; then
    APP_ID=$(grep -oP '<id>\K[^<]+' appinfo/info.xml 2>/dev/null || echo "unknown")
    echo "   ✓ info.xml exists"
    echo "   App ID: $APP_ID"
    if [ "$APP_ID" != "time_archive" ]; then
        echo "   ⚠ WARNING: App ID should be 'time_archive'"
    fi
else
    echo "   ✗ info.xml NOT found"
fi

echo ""
echo "3. Checking file permissions..."
echo "   js/ owner: $(stat -c '%U:%G' js/ 2>/dev/null || stat -f '%Su:%Sg' js/ 2>/dev/null || echo 'unknown')"
if [ -f "js/time_archive-main.js" ]; then
    echo "   time_archive-main.js permissions: $(stat -c '%a' js/time_archive-main.js 2>/dev/null || stat -f '%A' js/time_archive-main.js 2>/dev/null || echo 'unknown')"
fi

echo ""
echo "4. Checking if app is enabled..."
echo "   Run: php /opt/stacks/nextcloud/occ app:list | grep time_archive"

echo ""
echo "=== Next Steps ==="
if [ ! -f "js/time_archive-main.js" ]; then
    echo "JS files are missing! Run on your local machine:"
    echo "  cd /Volumes/MacMiniM4-ext\\ 1/Development/time_archive"
    echo "  ./deploy.sh"
    echo ""
    echo "Or manually copy:"
    echo "  scp -r js/ root@server:/opt/stacks/nextcloud/apps/time_archive/"
fi

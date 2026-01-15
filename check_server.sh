#!/bin/bash
# Script to check what's on the server

echo "=== Checking Server Status ==="
echo ""

SERVER_PATH="/opt/stacks/nextcloud/apps/time_archive"

echo "1. Checking if directory exists..."
if [ -d "$SERVER_PATH" ]; then
    echo "   ✓ Directory exists: $SERVER_PATH"
else
    echo "   ✗ Directory NOT found: $SERVER_PATH"
    exit 1
fi

echo ""
echo "2. Checking appinfo/info.xml..."
if [ -f "$SERVER_PATH/appinfo/info.xml" ]; then
    APP_ID=$(grep -oP '<id>\K[^<]+' "$SERVER_PATH/appinfo/info.xml" 2>/dev/null || echo "unknown")
    echo "   ✓ info.xml exists"
    echo "   App ID in info.xml: $APP_ID"
    if [ "$APP_ID" != "time_archive" ]; then
        echo "   ⚠ WARNING: App ID mismatch! Should be 'time_archive'"
    fi
else
    echo "   ✗ info.xml NOT found"
fi

echo ""
echo "3. Checking JS files..."
if [ -d "$SERVER_PATH/js" ]; then
    JS_COUNT=$(ls -1 "$SERVER_PATH/js"/*.js 2>/dev/null | grep -v ".map\|LICENSE" | wc -l | tr -d ' ')
    echo "   ✓ js/ directory exists"
    echo "   JS files found: $JS_COUNT"
    
    if [ -f "$SERVER_PATH/js/time_archive-main.js" ]; then
        echo "   ✓ time_archive-main.js exists"
        ls -lh "$SERVER_PATH/js/time_archive-main.js" | awk '{print "      Size: " $5}'
    else
        echo "   ✗ time_archive-main.js MISSING"
    fi
    
    if [ -f "$SERVER_PATH/js/time_archive-navigation.js" ]; then
        echo "   ✓ time_archive-navigation.js exists"
    else
        echo "   ✗ time_archive-navigation.js MISSING"
    fi
else
    echo "   ✗ js/ directory NOT found"
fi

echo ""
echo "4. Checking if app is enabled in Nextcloud..."
# This would need to be run on the server
echo "   (Run this on the server: php occ app:list | grep time_archive)"

echo ""
echo "5. Checking file permissions..."
if [ -d "$SERVER_PATH" ]; then
    OWNER=$(stat -c '%U:%G' "$SERVER_PATH" 2>/dev/null || stat -f '%Su:%Sg' "$SERVER_PATH" 2>/dev/null || echo "unknown")
    PERMS=$(stat -c '%a' "$SERVER_PATH" 2>/dev/null || stat -f '%A' "$SERVER_PATH" 2>/dev/null || echo "unknown")
    echo "   Owner: $OWNER"
    echo "   Permissions: $PERMS"
fi

echo ""
echo "=== Summary ==="
echo "If JS files are missing, run: ./deploy.sh"
echo "Or manually copy: cp -r js/ $SERVER_PATH/"

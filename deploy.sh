#!/bin/bash
# Deployment script for Time Archive app
# This script builds the app and deploys it to your Nextcloud server

set -e

echo "=== Time Archive Deployment Script ==="
echo ""

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR" || exit 1

echo "Working directory: $(pwd)"
echo ""

# Configuration - adjust these for your setup
NEXTCLOUD_PATH="${NEXTCLOUD_PATH:-/opt/stacks/nextcloud}"
DOCKER_CONTAINER="${DOCKER_CONTAINER:-nextcloud_nextcloud_app}"
USE_DOCKER="${USE_DOCKER:-auto}"  # auto, yes, no

# Detect if we should use Docker
if [ "$USE_DOCKER" = "auto" ]; then
    if docker ps --format "{{.Names}}" | grep -q nextcloud; then
        USE_DOCKER="yes"
        DOCKER_CONTAINER=$(docker ps --format "{{.Names}}" | grep nextcloud | head -1)
        echo "Detected Docker container: $DOCKER_CONTAINER"
    else
        USE_DOCKER="no"
        echo "No Docker container detected, using direct path: $NEXTCLOUD_PATH"
    fi
fi

echo ""
echo "=== Step 1: Building Frontend ==="
echo ""

# Remove old build
rm -rf js/

# Build
echo "Running npm build..."
npm run build

if [ ! -d "js" ] || [ -z "$(ls -A js/ 2>/dev/null)" ]; then
    echo "ERROR: Build failed - no JS files generated!"
    exit 1
fi

echo "✓ Build completed"
echo ""

echo "=== Step 2: Installing PHP Dependencies ==="
echo ""

if [ -f "composer.json" ]; then
    composer install --no-dev --optimize-autoloader
    echo "✓ PHP dependencies installed"
else
    echo "⚠ No composer.json found, skipping PHP dependencies"
fi

echo ""

if [ "$USE_DOCKER" = "yes" ]; then
    echo "=== Step 3: Deploying to Docker Container ==="
    echo ""
    
    # Check if container exists
    if ! docker ps --format "{{.Names}}" | grep -q "^${DOCKER_CONTAINER}$"; then
        echo "ERROR: Docker container '$DOCKER_CONTAINER' not found!"
        echo "Available containers:"
        docker ps --format "  {{.Names}}"
        exit 1
    fi
    
    echo "Container: $DOCKER_CONTAINER"
    
    # Copy entire app directory to Docker
    echo "Copying app to Docker container..."
    docker cp . ${DOCKER_CONTAINER}:/var/www/html/apps/time_archive/ 2>/dev/null || {
        echo "ERROR: Failed to copy to Docker container"
        echo "Trying alternative: copying js/ directory only..."
        docker cp js/ ${DOCKER_CONTAINER}:/var/www/html/apps/time_archive/ || {
            echo "ERROR: Could not copy files to Docker"
            exit 1
        }
    }
    
    echo "Setting permissions..."
    docker exec -u root ${DOCKER_CONTAINER} chown -R www-data:www-data /var/www/html/apps/time_archive/ 2>/dev/null || echo "⚠ Could not set permissions (may need root)"
    
    echo "✓ Files deployed to Docker"
    
else
    echo "=== Step 3: Deploying to Nextcloud Server ==="
    echo ""
    
    if [ ! -d "$NEXTCLOUD_PATH/apps" ]; then
        echo "ERROR: Nextcloud path not found: $NEXTCLOUD_PATH/apps"
        echo "Please set NEXTCLOUD_PATH environment variable or edit this script"
        exit 1
    fi
    
    echo "Target: $NEXTCLOUD_PATH/apps/time_archive"
    
    # Create app directory if it doesn't exist
    mkdir -p "$NEXTCLOUD_PATH/apps/time_archive"
    
    # Copy files (excluding .git, node_modules, etc.)
    echo "Copying files..."
    rsync -av --exclude='.git' --exclude='node_modules' --exclude='.DS_Store' \
        --exclude='*.log' --exclude='.idea' --exclude='.vscode' \
        ./ "$NEXTCLOUD_PATH/apps/time_archive/"
    
    echo "Setting permissions..."
    sudo chown -R www-data:www-data "$NEXTCLOUD_PATH/apps/time_archive" 2>/dev/null || echo "⚠ Could not set permissions (may need sudo)"
    
    echo "✓ Files deployed to server"
fi

echo ""
echo "=== Step 4: Enabling App and Clearing Cache ==="
echo ""

if [ "$USE_DOCKER" = "yes" ]; then
    echo "Enabling app in Docker..."
    docker exec -u www-data ${DOCKER_CONTAINER} php /var/www/html/occ app:enable time_archive 2>/dev/null || echo "⚠ App may already be enabled"
    
    echo "Running migrations..."
    docker exec -u www-data ${DOCKER_CONTAINER} php /var/www/html/occ upgrade 2>/dev/null || echo "⚠ Upgrade may have failed"
    
    echo "Clearing cache..."
    docker exec -u www-data ${DOCKER_CONTAINER} php /var/www/html/occ maintenance:mode --on 2>/dev/null || true
    docker exec -u www-data ${DOCKER_CONTAINER} php /var/www/html/occ maintenance:mode --off 2>/dev/null || true
else
    echo "Enabling app..."
    cd "$NEXTCLOUD_PATH"
    sudo -u www-data php occ app:enable time_archive 2>/dev/null || echo "⚠ App may already be enabled"
    
    echo "Running migrations..."
    sudo -u www-data php occ upgrade 2>/dev/null || echo "⚠ Upgrade may have failed"
    
    echo "Clearing cache..."
    sudo -u www-data php occ maintenance:mode --on 2>/dev/null || true
    sudo -u www-data php occ maintenance:mode --off 2>/dev/null || true
fi

echo ""
echo "=== Deployment Complete! ==="
echo ""
echo "Next steps:"
echo "1. Clear your browser cache"
echo "2. Go to Settings → Administration → Workflow → File Archive"
echo "3. Create your first archive rule"
echo ""
echo "If the app doesn't appear:"
echo "- Check Nextcloud logs for errors"
echo "- Verify the app is enabled: php occ app:list | grep time_archive"
echo "- Check file permissions in the app directory"

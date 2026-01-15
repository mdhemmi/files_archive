# Issues After App Rename (files_archive → time_archive)

## The Problem

After renaming the app from `files_archive` to `time_archive`, several issues can occur if the rename wasn't complete:

## Critical Issues to Check

### 1. App Directory Name on Server

**The app directory name MUST match the app ID in `appinfo/info.xml`**

- **App ID in code**: `time_archive` (from `appinfo/info.xml`)
- **Directory on server MUST be**: `/path/to/nextcloud/apps/time_archive/`
- **NOT**: `/path/to/nextcloud/apps/files_archive/`

**Fix:**
```bash
# On the server, rename the directory
cd /path/to/nextcloud/apps
mv files_archive time_archive

# Or if using Docker
docker exec container_name mv /var/www/html/apps/files_archive /var/www/html/apps/time_archive
```

### 2. Nextcloud App Registry

Nextcloud caches app information. After renaming:

```bash
# Disable old app (if it exists)
php occ app:disable files_archive

# Enable new app
php occ app:enable time_archive

# Clear cache
php occ maintenance:mode --on
php occ maintenance:mode --off
```

### 3. Database Tables

The database tables use `archive_rules` (no app prefix), so they should be fine. But verify:

```sql
-- Check if old tables exist
SHOW TABLES LIKE '%files_archive%';

-- Should only see:
-- oc_archive_rules (correct, no app prefix)
```

### 4. Webpack Build Output

The webpack config uses the app ID from `info.xml`, so it should generate:
- `time_archive-main.js` ✓
- `time_archive-navigation.js` ✓

But if the directory name is wrong, Nextcloud looks in the wrong place.

### 5. Routes

Routes are registered with the app ID, so they should be:
- `/apps/time_archive/` ✓

But if the directory is `files_archive`, routes won't work.

## Complete Fix Procedure

1. **Rename directory on server** (if needed):
   ```bash
   mv /path/to/nextcloud/apps/files_archive /path/to/nextcloud/apps/time_archive
   ```

2. **Disable old app**:
   ```bash
   php occ app:disable files_archive
   ```

3. **Enable new app**:
   ```bash
   php occ app:enable time_archive
   ```

4. **Clear all caches**:
   ```bash
   php occ maintenance:mode --on
   php occ maintenance:mode --off
   php occ files:scan --all
   ```

5. **Rebuild JS files** (in the correct directory):
   ```bash
   cd /path/to/nextcloud/apps/time_archive
   npm run build
   ```

6. **Verify**:
   ```bash
   php occ app:list | grep time_archive
   ls -la /path/to/nextcloud/apps/time_archive/js/
   ```

## Why This Happens

Nextcloud uses the **directory name** to locate apps, but the **app ID** from `info.xml` for routing and registration. If these don't match:
- Routes fail (404 errors)
- JS files can't be found (resource loader errors)
- Settings don't appear (wrong directory)
- App doesn't load (bootstrap fails)

## Prevention

When renaming an app:
1. Update `appinfo/info.xml` (app ID)
2. Update all code references (APP_ID constant)
3. **Rename the directory on the server**
4. Disable old app, enable new app
5. Clear all caches
6. Rebuild assets

# 🔧 WordPress Configuration Fixes for Production Server

## ⚠️ IMPORTANT
This file documents required changes to `wp-config.php` on the production server.
The `wp-config.php` file is **not** in version control (it's in `.gitignore`) because it contains sensitive database credentials.

---

## 🎯 Required Changes to wp-config.php

Add the following code **BEFORE** the line `/* That's all, stop editing! */`:

```php
/**
 * ========================================
 * DEBUG AND ERROR HANDLING CONFIGURATION
 * ========================================
 */

// Enable WordPress debug mode
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}

// DO NOT display errors on screen (security & user experience)
if (!defined('WP_DEBUG_DISPLAY')) {
    define('WP_DEBUG_DISPLAY', false);
}

// Log errors to wp-content/debug.log
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}

// Disable PHP error display on screen
ini_set('display_errors', 0);

// Set error reporting level (exclude notices, warnings, and deprecated)
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

/**
 * ========================================
 * OPTIONAL: DISABLE PHANTOM PLUGINS
 * ========================================
 * Uncomment the following if you see errors about the "rocket" domain
 * or other missing plugins that are still marked as active
 */
/*
add_filter('option_active_plugins', function($plugins) {
    if (!is_array($plugins)) {
        return $plugins;
    }
    return array_filter($plugins, function($plugin) {
        // Remove any plugin containing 'rocket' in the path
        if (strpos($plugin, 'rocket') !== false) {
            return false;
        }
        return true;
    });
});
*/
```

---

## 📋 Location in wp-config.php

The code should be added near the end of the file, typically after the `$table_prefix` definition but **before** this line:

```php
/* That's all, stop editing! Happy publishing. */
```

### Example Structure:

```php
<?php
// ... existing database configuration ...

$table_prefix = 'wp_';

// ADD THE DEBUG CONFIGURATION HERE

/* That's all, stop editing! Happy publishing. */

require_once ABSPATH . 'wp-settings.php';
```

---

## 🔍 About the "Rocket" Domain Error

### Issue:
```
Notice: Function _load_textdomain_just_in_time was called incorrectly. 
Translation loading for the rocket domain was triggered too early.
```

### Investigation Results:
- No WP Rocket plugin found in `/wp-content/plugins/`
- No rocket-related files in the repository
- No references to rocket textdomain in themes

### Most Likely Cause:
The plugin was **removed from the filesystem** but is **still marked as active** in the WordPress database (`wp_options` table, `active_plugins` option).

### Solutions:

#### Option 1: Via WordPress Admin (Recommended)
1. Log in to WordPress admin
2. Go to **Plugins** → **Installed Plugins**
3. If you see any inactive or broken plugins related to "rocket", delete them
4. Refresh the page to clear any cached plugin lists

#### Option 2: Via Database (Advanced)
If admin access doesn't work, connect to the database and run:

```sql
-- Check current active plugins
SELECT option_value FROM wp_options WHERE option_name = 'active_plugins';

-- The result will be a serialized array
-- You need to manually edit it to remove any 'rocket' plugin entries
-- OR use a WordPress plugin like WP-CLI to deactivate it
```

#### Option 3: Via WP-CLI (Recommended for Server Access)
```bash
# List all plugins
wp plugin list

# Deactivate any rocket-related plugin
wp plugin deactivate wp-rocket

# OR force deactivate all inactive plugins
wp plugin deactivate --all --uninstall
```

#### Option 4: Via wp-config.php Filter (Temporary)
Uncomment the `add_filter('option_active_plugins', ...)` code in the wp-config.php section above. This will filter out any rocket plugins from the active plugins list without touching the database.

---

## ✅ Verification

After applying the changes:

1. **Check Error Logs**: Look for errors in `wp-content/debug.log`
2. **Check Site Frontend**: Verify no PHP code is displayed on screen
3. **Check Admin Area**: Ensure WordPress admin works normally
4. **Monitor Logs**: Watch for any remaining "rocket" errors

---

## 📊 What Was Fixed in the Repository

### ✅ Critical Fixes Applied:

1. **class-dispatcher.php** - Fixed missing PHP opening tag
   - Added `<?php` tag
   - Added namespace and class declaration
   - File: `wp-content/plugins/zap-tutor-events/includes/class-dispatcher.php`

2. **All-in-One WP Migration** - Translation loading already fixed
   - Proper `load_plugin_textdomain()` on `init` hook
   - File: `wp-content/plugins/all-in-one-wp-migration-master/all-in-one-wp-migration.php`

3. **ZAP Events Debug** - Constant already defined
   - `ZAP_EVENTS_DEBUG` constant added
   - File: `wp-content/plugins/zap-tutor-events/zap-tutor-events.php`

4. **Logger Improvements** - Already implemented
   - Table existence check
   - Proper error logging
   - File: `wp-content/plugins/zap-tutor-events/includes/class-logger.php`

---

## 🎯 Expected Results

After all fixes:
- ✅ Site displays normally (no PHP code on screen)
- ✅ Translation notices are suppressed
- ✅ Errors are logged to debug.log instead of screen
- ✅ System is more resilient to failures

---

## 📞 Support

If issues persist after applying these changes:
1. Check `wp-content/debug.log` for detailed error messages
2. Verify all plugin files are properly uploaded
3. Clear all caches (WordPress, server, CDN)
4. Disable caching plugins temporarily for testing

---

**Last Updated:** 2026-02-10  
**Status:** ✅ Critical fixes applied to repository

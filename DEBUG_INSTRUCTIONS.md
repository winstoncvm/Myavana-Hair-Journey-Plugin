# Debug Instructions for Entry Sharing

I've added comprehensive logging to track exactly what's happening when you share entries.

## How to View Debug Logs

### Method 1: WordPress Debug Log (Recommended)

1. **Enable WordPress debugging** - Edit your `wp-config.php` file and add/update these lines:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

2. **View the log file** at:
   ```
   /Users/winstonzulu/Local Sites/myavana-hair-journey/app/public/wp-content/debug.log
   ```

3. **Watch in real-time** using Terminal:
   ```bash
   tail -f "/Users/winstonzulu/Local Sites/myavana-hair-journey/app/public/wp-content/debug.log"
   ```

### Method 2: Browser Console

The logs will also appear in your browser's developer console (F12 → Console tab).

## What Gets Logged

When you click "Share Selected Entries", you'll see detailed logs like:

```
=== BULK SHARE ENTRIES STARTED ===
User ID: 1
Entry IDs received: Array([0] => 45, [1] => 46, [2] => 47)
Privacy level: public

--- Processing entry ID: 45
>>> is_entry_shareable() checking entry ID: 45
Entry exists in diary table
Entry is shareable
Calling share_entry for entry 45

>>> share_entry() called - Entry ID: 45, Privacy: public, User ID: 1
Querying diary table: wp_myavana_hair_diary_entries
Entry found: stdClass Object(...)
Inserting into community posts table: wp_myavana_community_posts
Post data: user_id=1, title=My Hair Journey Update, privacy=public, source_entry_id=45
SUCCESS: Community post created with ID: 123
Recording in shared_entries table: wp_myavana_shared_entries
Shared entry link created successfully
<<< share_entry() returning post ID: 123

SUCCESS: Created community post ID: 123

SUMMARY - Shared: 1, Already shared: 0, Failed: 0

=== GET COMMUNITY FEED CALLED ===
Feed params - Page: 1, Per page: 10, Filter: all, User ID: 1
Executing feed query with WHERE: (p.privacy_level = 'public' OR p.user_id = 1 OR ...)
Feed returned 5 posts
First post ID: 123, Title: My Hair Journey Update
```

## What to Look For

### If Entries Are Already Shared:
```
--- Processing entry ID: 45
>>> is_entry_shareable() checking entry ID: 45
Entry is already shared (shared_entry ID: 10)
Entry 45 already shared - skipping
```

### If Entry Doesn't Exist:
```
>>> is_entry_shareable() checking entry ID: 999
Entry does not exist in diary table
```

### If Database Insert Fails:
```
ERROR: Database insert failed!
Last DB error: Table 'wp_myavana_community_posts' doesn't exist
```

### If Entry Not Found for User:
```
ERROR: Entry not found in diary table for entry_id=45 user_id=1
```

## Test Steps

1. **Clear any previously shared entries** (optional - only if you want to test fresh):
   ```sql
   DELETE FROM wp_myavana_shared_entries;
   ```

2. **Start watching logs**:
   ```bash
   tail -f "/Users/winstonzulu/Local Sites/myavana-hair-journey/app/public/wp-content/debug.log"
   ```

3. **Try sharing entries** from the Community page

4. **Check the logs** - they will show exactly where the process succeeds or fails

## Common Issues You Might See

| Log Message | Problem | Solution |
|-------------|---------|----------|
| `Entry does not exist in diary table` | Selected entry ID doesn't exist | Check entry IDs match diary table |
| `Entry is already shared` | Entry was previously shared | Normal - prevents duplicates |
| `ERROR: Database insert failed` | Community posts table issue | Check table exists and has correct structure |
| `Entry not found for user_id=X` | Entry belongs to different user | Security check working correctly |
| `Feed returned 0 posts` | No posts in database OR privacy filter too restrictive | Check posts exist and privacy_level matches |

## Send Me the Logs

After you try sharing, copy the relevant section from the debug.log and send it to me. I'll be able to see exactly what's happening!

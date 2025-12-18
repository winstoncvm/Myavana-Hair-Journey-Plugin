# Profile Save 403 Error - Debugging & Fix

**Issue:** Profile save returns 403 Forbidden error
**Date:** 2025-10-28

## 🔍 DEBUGGING ADDED

### JavaScript Logging
Added comprehensive console logging to track nonce usage:

```javascript
console.log('Available nonces:', {
    myavanaAjax: window.myavanaAjax?.nonce,
    myavanaProfileAjax: window.myavanaProfileAjax?.nonce
});
```

### PHP Logging
Added server-side logging in `myavana_save_profile()`:

```php
error_log('myavana_save_profile called');
error_log('POST data: ' . print_r($_POST, true));
error_log('Nonce received: ' . ($_POST['nonce'] ?? 'NONE'));
error_log('Nonce verification result: ' . ($nonce_check ? 'VALID' : 'INVALID'));
```

## 📋 HOW TO DEBUG

1. **Open Browser Console**
   - Look for log: `Available nonces: { myavanaAjax: "...", myavanaProfileAjax: "..." }`
   - Note which nonce is being used

2. **Check Server Error Log**
   - Look in WordPress debug.log or server error log
   - Find logs from `myavana_save_profile`
   - Check if nonce verification says VALID or INVALID

3. **Common Issues:**
   - **Nonce is stale:** Page has been open too long (nonces expire after 12-24 hours)
   - **Wrong nonce action:** Handler expects `myavana_nonce` but different nonce sent
   - **No nonce:** JavaScript can't find nonce in window object
   - **Logged out:** User session expired

## 🔧 FIXES APPLIED

### 1. Better Nonce Handling
```javascript
// Try both nonce sources
let nonce = null;
if (window.myavanaAjax && window.myavanaAjax.nonce) {
    nonce = window.myavanaAjax.nonce;
} else if (window.myavanaProfileAjax && window.myavanaProfileAjax.nonce) {
    nonce = window.myavanaProfileAjax.nonce;
}
```

### 2. Improved Error Messages
Changed from generic "403 Forbidden" to specific messages:
- "Security check failed. Please refresh the page and try again."
- "Security token missing. Please refresh the page."

### 3. Added Null Checks
All form fields now have `|| ''` fallback to prevent undefined values

### 4. Enhanced Logging
Both client and server now log detailed information about the save process

## 🎯 SOLUTIONS FOR COMMON SCENARIOS

### Scenario 1: Nonce Expired
**Symptom:** 403 error, logs show "Nonce verification: INVALID"
**Solution:** User needs to refresh the page to get new nonce

### Scenario 2: Wrong Nonce Action
**Symptom:** 403 error, nonce sent but doesn't match `myavana_nonce`
**Solution:** Check `myavana-hair-journey.php` line ~422 to see what nonce is created

### Scenario 3: No Nonce Available
**Symptom:** Console shows "No nonce available!"
**Solution:** Ensure script localization happens before profile-edit-fixes.js loads

### Scenario 4: User Not Logged In
**Symptom:** Log shows "User not logged in"
**Solution:** User session expired, need to log in again

## 📝 TESTING STEPS

1. **Clear browser cache** and refresh page
2. **Open browser console**
3. **Click profile edit button**
4. **Fill in form fields**
5. **Click Save**
6. **Check console logs** for:
   ```
   Available nonces: { ... }
   Using myavanaAjax.nonce: abc123...
   Sending data: { action, nonce, ... }
   ```
7. **Check server logs** for:
   ```
   myavana_save_profile called
   Nonce received: abc123...
   Nonce verification result: VALID
   Profile save successful for user X
   ```

## 🐛 EXPECTED LOG OUTPUT (Success)

### Browser Console
```
Saving profile data...
Available nonces: {myavanaAjax: "2f85dfea6a", myavanaProfileAjax: undefined}
Using myavanaAjax.nonce: 2f85dfea6a
Sending data: {action: "myavana_save_profile", nonce: "2f85dfea6a", ...}
AJAX URL: http://myavana-hair-journey.local/wp-admin/admin-ajax.php
Save response: {success: true, data: {...}}
✅ Profile updated successfully!
```

### Server Log
```
myavana_save_profile called
POST data: Array([action] => myavana_save_profile [nonce] => 2f85dfea6a ...)
Nonce received: 2f85dfea6a
Nonce verification result: VALID
Profile save successful for user 1
```

## 🔄 NEXT STEPS

1. **Try saving profile** with new debugging
2. **Check both console and server logs**
3. **Report what you see** in logs
4. **Refresh page** if nonce is stale
5. **Verify user is logged in**

## 💡 TEMPORARY WORKAROUND

If nonce keeps failing, you can temporarily disable nonce check for testing:

```php
// In myavana_ajax_handlers.php, comment out nonce check:
// check_ajax_referer('myavana_nonce', 'nonce');

// Replace with:
if (!is_user_logged_in()) {
    wp_send_json_error('Not logged in');
    return;
}
```

**⚠️ WARNING:** Only use this for debugging! Re-enable nonce check before production!

---

**Files Modified:**
- `assets/js/profile-edit-fixes.js` - Added nonce fallback and logging
- `includes/myavana_ajax_handlers.php` - Added comprehensive error logging

**Status:** Debugging enabled, waiting for log output to diagnose exact issue

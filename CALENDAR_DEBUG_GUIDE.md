# Calendar Visual Display - Debug Guide

## Current Status
Added comprehensive debugging to identify why entries and goals may not be showing on the calendar.

## Debug Features Added

### 1. Visual Indicators
- **Red border** on overlay container (`.calendar-visual-overlay-hjn`)
- **Blue border** on entry cards (temporary - remove after testing)
- **White background** on entry cards for visibility
- **Minimum height** (500px) on overlay to ensure it's visible

### 2. HTML Comments Debug Output
```html
<!-- DEBUG: Goals count: X -->
<!-- DEBUG: Entries count: X -->
<!-- DEBUG: Current month: 10, year: 2025 -->
<!-- DEBUG: Entry 123 - Month: 10, Year: 2025, Day: 15 -->
<!-- DEBUG: Entry 123 positioned at left: 28.57%, top: 110px -->
<!-- DEBUG: Entry skipped - wrong month/year -->
<!-- DEBUG: Total entries rendered: X -->
```

### 3. Error Logging
```php
error_log('Calendar Debug - Entries: ' . count($calendar_data['entries']));
error_log('Calendar Debug - Goals: ' . count($calendar_data['goals']));
error_log('Calendar Debug - Current Month: ' . $current_month . ', Year: ' . $current_year);
error_log('First entry: ' . print_r($calendar_data['entries'][0], true));
error_log('First goal: ' . print_r($calendar_data['goals'][0], true));
```

## How to Debug

### Step 1: Check PHP Error Log
Location: `/path/to/wordpress/wp-content/debug.log` (if WP_DEBUG is enabled)

Look for lines starting with:
```
Calendar Debug - Entries: 0
Calendar Debug - Goals: 0
```

**If counts are 0:**
- No data exists for this user
- Create test entries/goals first

### Step 2: View Page Source
Right-click page → "View Page Source"

Search for:
```html
<!-- DEBUG: Goals count:
<!-- DEBUG: Entries count:
```

**What to check:**
- Are counts > 0?
- Which entries are being skipped?
- What are the positioning values?

### Step 3: Inspect Element
Right-click calendar → "Inspect Element"

**Check overlay container:**
```html
<div class="calendar-visual-overlay-hjn" style="border: 2px solid red;">
```
- Is it visible with red border?
- Does it have min-height: 500px?
- Is it positioned absolutely?

**Check entry cards:**
```html
<div class="calendar-entry-card-hjn" style="left: X%; top: Ypx; background: white; border: 3px solid blue;">
```
- Are entry divs present in HTML?
- What are the left/top values?
- Are they within viewport?

### Step 4: Check CSS
Open DevTools → Elements → Computed

**For `.calendar-visual-overlay-hjn`:**
- position: absolute
- z-index: 10
- pointer-events: none (container)

**For `.calendar-entry-card-hjn`:**
- position: absolute
- z-index: 2
- pointer-events: auto
- display: block (not none!)

## Common Issues & Solutions

### Issue 1: Counts are 0
**Problem:** No data fetched from database

**Solutions:**
```php
// Check if post type exists
$post_types = get_post_types();
var_dump(in_array('hair_journey_entry', $post_types));

// Check user meta
$goals = get_user_meta($user_id, 'myavana_hair_goals_structured', true);
var_dump($goals);

// Create test data
wp_insert_post([
    'post_type' => 'hair_journey_entry',
    'post_title' => 'Test Entry',
    'post_status' => 'publish',
    'post_author' => $user_id,
    'post_date' => date('Y-m-d H:i:s')
]);
```

### Issue 2: Entries Exist But Not Rendering
**Problem:** Month/year filtering

**Check HTML comments:**
```html
<!-- DEBUG: Entry 123 - Month: 9, Year: 2025, Day: 15 -->
<!-- DEBUG: Entry skipped - wrong month/year -->
```

**If entries are in wrong month:**
- Entries are from previous/next month
- Navigate calendar to correct month
- Or update entry post_date to current month

### Issue 3: Entries Render But Not Visible
**Problem:** Positioning or z-index issues

**Solutions:**
```css
/* Force visibility for testing */
.calendar-entry-card-hjn {
    background: yellow !important;
    border: 5px solid red !important;
    z-index: 9999 !important;
    min-height: 100px !important;
}
```

**Check positioning:**
```javascript
// In browser console
document.querySelectorAll('.calendar-entry-card-hjn').forEach(el => {
    console.log('Entry:', el.style.left, el.style.top, el.getBoundingClientRect());
});
```

### Issue 4: Overlay Not Visible
**Problem:** Parent container issues

**Check:**
```css
.calendar-month-grid-hjn {
    position: relative; /* REQUIRED for absolute children */
    min-height: 600px; /* Ensure height */
}
```

**Verify in DevTools:**
- Parent has `position: relative`
- Parent has actual height (not 0)
- Overlay is child of correct parent

### Issue 5: Entries Behind Grid Cells
**Problem:** Z-index stacking context

**Solution:**
```css
.calendar-days-grid-hjn {
    z-index: 1; /* Lower than overlay */
}

.calendar-visual-overlay-hjn {
    z-index: 10; /* Higher than grid */
}

.calendar-entry-card-hjn {
    z-index: 2; /* Within overlay */
}
```

## Testing Checklist

### Data Verification
- [ ] Check PHP error log for counts
- [ ] Verify entries exist in database
- [ ] Verify goals exist in user meta
- [ ] Confirm user is logged in
- [ ] Check post_type is 'hair_journey_entry'
- [ ] Confirm post_status is 'publish'

### HTML Verification
- [ ] View source shows HTML comments with counts > 0
- [ ] Entry divs present in HTML
- [ ] Positioning values are reasonable (left: 0-100%, top: > 0)
- [ ] Entry divs are inside `.calendar-visual-overlay-hjn`

### CSS Verification
- [ ] Red border visible on overlay
- [ ] Blue border visible on entries
- [ ] White background on entries
- [ ] Overlay has min-height: 500px
- [ ] Parent grid has position: relative

### Visual Verification
- [ ] Can see red border overlay container
- [ ] Can see blue border entry cards
- [ ] Entry cards positioned on correct dates
- [ ] Goal bars visible and spanning days
- [ ] Hover effects working

## Quick Test Entry Creation

Add this to your theme's functions.php temporarily:

```php
add_action('init', 'create_test_calendar_entry');
function create_test_calendar_entry() {
    if (isset($_GET['create_test_entry']) && is_user_logged_in()) {
        $user_id = get_current_user_id();

        // Create entry for today
        $post_id = wp_insert_post([
            'post_type' => 'hair_journey_entry',
            'post_title' => 'Test Hair Entry - ' . date('Y-m-d H:i:s'),
            'post_content' => 'This is a test entry for calendar debugging.',
            'post_status' => 'publish',
            'post_author' => $user_id,
            'post_date' => date('Y-m-d H:i:s')
        ]);

        // Add metadata
        update_post_meta($post_id, 'health_rating', 8);
        update_post_meta($post_id, 'mood_demeanor', 'Great');

        wp_redirect(remove_query_arg('create_test_entry'));
        exit;
    }
}
```

Then visit: `yourdomain.com/?create_test_entry=1`

## Quick Test Goal Creation

```php
add_action('init', 'create_test_calendar_goal');
function create_test_calendar_goal() {
    if (isset($_GET['create_test_goal']) && is_user_logged_in()) {
        $user_id = get_current_user_id();

        $goals = get_user_meta($user_id, 'myavana_hair_goals_structured', true) ?: [];

        $goals[] = [
            'title' => 'Test Goal - ' . date('Y-m-d'),
            'description' => 'Test goal for calendar',
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+7 days')),
            'progress' => 50
        ];

        update_user_meta($user_id, 'myavana_hair_goals_structured', $goals);

        wp_redirect(remove_query_arg('create_test_goal'));
        exit;
    }
}
```

Then visit: `yourdomain.com/?create_test_goal=1`

## Expected Behavior

### When Working Correctly:
1. **Red border** visible around calendar grid
2. **Blue entry cards** visible on calendar
3. **Coral goal bars** spanning multiple days
4. **HTML comments** show counts > 0
5. **PHP error log** shows entries/goals data

### Visual Layout:
```
┌─────────────────────────────────────────┐ RED BORDER
│  Mon  Tue  Wed  Thu  Fri  Sat  Sun     │
├─────────────────────────────────────────┤
│   1    2  ┌Goal Bar Coral──┐  5    6   │
│           │                 │           │
│   8    9  │ [Entry Blue]   │  12   13  │
│           └─────────────────┘           │
│  15   16    17  [Entry]  19   20   21  │
└─────────────────────────────────────────┘
```

## Cleanup After Debugging

Once everything works, remove:

1. **Red border** on overlay:
```php
// REMOVE: style="border: 2px solid red; min-height: 500px;"
<div class="calendar-visual-overlay-hjn">
```

2. **Blue border** on entries:
```php
// REMOVE: background: white; border: 3px solid blue;
style="left: X%; top: Ypx; width: Y%;"
```

3. **Debug comments**:
```php
// REMOVE all echo '<!-- DEBUG: ... -->';
```

4. **Error logging**:
```php
// REMOVE all error_log() calls
```

5. **Test entry creators** in functions.php

## Summary

The calendar visual display is now instrumented with comprehensive debugging:
- ✅ Visual indicators (red/blue borders)
- ✅ HTML comment logging
- ✅ PHP error logging
- ✅ Fallback gradient images
- ✅ Enhanced entry card styling

Follow the debug steps above to identify and resolve any rendering issues. Once working, remove all debug code for production.

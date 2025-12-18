# Hair Diary Calendar Troubleshooting Guide

## 🔧 Quick Fixes for Calendar Not Showing Dates

### 1. **Check Browser Console**
Open your browser's Developer Tools (F12) and check the Console tab for errors:

```javascript
// In browser console, run these commands:

// Check if elements exist
debugMyavanaCalendar.checkElements();

// Manual render attempt
debugMyavanaCalendar.renderCalendar();

// Manual initialization
debugMyavanaCalendar.initializeManually();
```

### 2. **Common Issues & Solutions**

#### **Issue: "Configuration not found" error**
**Solution:** The WordPress localization might not be working properly.

**Fix:**
```javascript
// In browser console, set fallback configuration:
window.myavanaHairDiary = {
    ajax_url: '/wp-admin/admin-ajax.php',
    nonce: 'temp_nonce',
    user_id: '1',
    actions: {
        get_entries: 'myavana_get_diary_entries'
    }
};

// Then manually initialize:
debugMyavanaCalendar.initializeManually();
```

#### **Issue: Calendar grid is empty**
**Solution:** DOM elements might not be loaded when JavaScript runs.

**Fix:**
```javascript
// Wait a bit and try again:
setTimeout(() => {
    debugMyavanaCalendar.renderCalendar();
}, 1000);
```

#### **Issue: JavaScript file not loading**
**Solution:** Check if the JavaScript file is being enqueued properly.

**Fix:** Add this to your theme's functions.php temporarily:
```php
add_action('wp_footer', function() {
    if (has_shortcode(get_post()->post_content, 'hair_journey_diary')) {
        wp_enqueue_script('myavana-hair-diary-js',
            plugin_dir_url(__FILE__) . 'wp-content/plugins/myavana-hair-journey/assets/js/hair-diary-new.js',
            ['jquery'], '1.0.0', true);
    }
});
```

### 3. **Manual Calendar Creation**

If the JavaScript isn't working, you can create a simple calendar manually:

```javascript
// Run this in browser console to create a basic calendar:
function createBasicCalendar() {
    const grid = document.getElementById('calendarGrid');
    if (!grid) return;

    grid.innerHTML = '';

    // Add headers
    ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach(day => {
        const header = document.createElement('div');
        header.className = 'myavana-calendar-day-header';
        header.textContent = day;
        grid.appendChild(header);
    });

    // Add dates for current month
    const today = new Date();
    const year = today.getFullYear();
    const month = today.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - firstDay.getDay());

    for (let i = 0; i < 42; i++) {
        const cellDate = new Date(startDate);
        cellDate.setDate(startDate.getDate() + i);

        const dayElement = document.createElement('div');
        dayElement.className = 'myavana-calendar-day';
        if (cellDate.getMonth() !== month) {
            dayElement.classList.add('other-month');
        }
        if (cellDate.toDateString() === today.toDateString()) {
            dayElement.classList.add('today');
        }

        const dayNumber = document.createElement('div');
        dayNumber.className = 'myavana-calendar-day-number';
        dayNumber.textContent = cellDate.getDate();
        dayElement.appendChild(dayNumber);

        grid.appendChild(dayElement);
    }

    // Update month title
    const monthTitle = document.getElementById('calendarMonth');
    if (monthTitle) {
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                           'July', 'August', 'September', 'October', 'November', 'December'];
        monthTitle.textContent = `${monthNames[month]} ${year}`;
    }
}

// Run the function
createBasicCalendar();
```

### 4. **Check File Permissions**

Ensure these files are readable:
- `/assets/js/hair-diary-new.js`
- `/assets/css/hair-diary-new.css`
- `/templates/hair-diary-new.php`

### 5. **WordPress Debug Mode**

Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check `/wp-content/debug.log` for PHP errors.

### 6. **Theme Compatibility**

Some themes might interfere with JavaScript. Try adding this CSS to force calendar display:

```css
.myavana-calendar-grid {
    display: grid !important;
    grid-template-columns: repeat(7, 1fr) !important;
    min-height: 400px !important;
}

.myavana-calendar-day {
    min-height: 60px !important;
    background: white !important;
    border: 1px solid #eee !important;
    padding: 8px !important;
}
```

### 7. **Complete Reset**

If nothing works, try this complete reset:

```javascript
// 1. Clear any existing instances
window.myavanaHairDiary = null;

// 2. Reload the JavaScript (if possible)
// Or refresh the page

// 3. Check all required elements exist
console.log('Calendar Grid:', document.getElementById('calendarGrid'));
console.log('Month Title:', document.getElementById('calendarMonth'));

// 4. Manually initialize
setTimeout(() => {
    window.myavanaHairDiary = new MyavanaHairDiary();
}, 1000);
```

## 📋 What to Check First

1. **Browser Console**: Look for JavaScript errors
2. **Network Tab**: Check if CSS/JS files are loading (200 status)
3. **Elements Tab**: Verify calendar HTML structure exists
4. **WordPress Admin**: Check if the shortcode is properly placed

## 🆘 Emergency Fallback

If you need the calendar to work immediately, you can add this simple CSS-only calendar to the shortcode temporarily:

```html
<style>
.simple-calendar {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: #f5f5f7;
    border-radius: 8px;
    overflow: hidden;
    margin: 20px 0;
}
.simple-day {
    background: white;
    padding: 15px 8px;
    text-align: center;
    min-height: 40px;
}
.simple-header {
    background: #4a4d68;
    color: white;
    font-weight: bold;
    text-align: center;
    padding: 10px;
}
</style>

<div class="simple-calendar">
    <div class="simple-header">Sun</div>
    <div class="simple-header">Mon</div>
    <div class="simple-header">Tue</div>
    <div class="simple-header">Wed</div>
    <div class="simple-header">Thu</div>
    <div class="simple-header">Fri</div>
    <div class="simple-header">Sat</div>
    <!-- Add 35 more divs with class="simple-day" and numbers 1-31 -->
</div>
```

Let me know what you see in the browser console and I can provide more specific fixes! 🔧
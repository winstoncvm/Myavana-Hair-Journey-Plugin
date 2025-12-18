# Navigation Module - Quick Reference

## File Location
`/assets/js/timeline/timeline-navigation.js`

## Import Order
1. `timeline-state.js` (dependency)
2. `timeline-navigation.js`

## Public API

### MyavanaTimeline.Navigation.init()
Initialize the navigation module and set up event listeners.
```javascript
MyavanaTimeline.Navigation.init();
```

### MyavanaTimeline.Navigation.switchView(viewName)
Switch between main timeline views.
```javascript
// Available views: 'calendar', 'slider', 'list'
MyavanaTimeline.Navigation.switchView('calendar');
MyavanaTimeline.Navigation.switchView('slider');
MyavanaTimeline.Navigation.switchView('list');
```

### MyavanaTimeline.Navigation.setCalendarView(view)
Switch calendar display modes.
```javascript
// Available views: 'day', 'week', 'month'
MyavanaTimeline.Navigation.setCalendarView('day');
MyavanaTimeline.Navigation.setCalendarView('week');
MyavanaTimeline.Navigation.setCalendarView('month');
```

### MyavanaTimeline.Navigation.initSlider()
Initialize or reinitialize the Splide slider.
```javascript
MyavanaTimeline.Navigation.initSlider();
```

### MyavanaTimeline.Navigation.scrollCarousel(direction)
Scroll the carousel track.
```javascript
// Scroll left
MyavanaTimeline.Navigation.scrollCarousel(-1);

// Scroll right
MyavanaTimeline.Navigation.scrollCarousel(1);
```

## Events

### Listen to Navigation Events
```javascript
// View changed
document.addEventListener('myavana:view:changed', (e) => {
    console.log('New view:', e.detail.view);
});

// Calendar view changed
document.addEventListener('myavana:calendar:view:changed', (e) => {
    console.log('Calendar view:', e.detail.view);
});

// Slider moved
document.addEventListener('myavana:slider:moved', (e) => {
    console.log('Slide index:', e.detail.index, 'of', e.detail.total);
});

// Carousel scrolled
document.addEventListener('myavana:carousel:scrolled', (e) => {
    console.log('Scroll direction:', e.detail.direction);
});
```

## State Access
```javascript
// Get current calendar view
const currentView = MyavanaTimeline.State.get('currentCalendarView');

// Get Splide instance
const splide = MyavanaTimeline.State.get('splide');
```

## Backward Compatibility
The following global functions are available for inline HTML handlers:
- `window.switchView(viewName)`
- `window.setCalendarView(view)`
- `window.scrollCarousel(direction)`
- `window.initSlider()`

## Common Tasks

### Set Default View on Page Load
```javascript
document.addEventListener('DOMContentLoaded', function() {
    MyavanaTimeline.Navigation.init();
    MyavanaTimeline.Navigation.switchView('calendar');
    MyavanaTimeline.Navigation.setCalendarView('month');
});
```

### Handle View Changes
```javascript
document.addEventListener('myavana:view:changed', (e) => {
    const view = e.detail.view;
    
    if (view === 'slider') {
        // Load slider-specific data
        loadSliderEntries();
    } else if (view === 'list') {
        // Load list-specific data
        loadListEntries();
    }
});
```

### Programmatic Navigation
```javascript
// Navigate to specific entry in slider view
MyavanaTimeline.Navigation.switchView('slider');
setTimeout(() => {
    const splide = MyavanaTimeline.State.get('splide');
    if (splide) {
        splide.go(5); // Go to slide 5
    }
}, 150);
```

### React to Slider Movement
```javascript
document.addEventListener('myavana:slider:moved', (e) => {
    const { index, total } = e.detail;
    console.log(`Viewing entry ${index + 1} of ${total}`);
    
    // Load entry details
    loadEntryDetails(index);
});
```

## Troubleshooting

### Slider Not Initializing
```javascript
// Check if Splide is loaded
if (typeof Splide === 'undefined') {
    console.error('Splide library not loaded');
}

// Manually initialize
MyavanaTimeline.Navigation.initSlider();
```

### View Not Switching
```javascript
// Check if view element exists
const view = document.getElementById('calendarView');
if (!view) {
    console.error('View element not found');
}

// Force view switch
MyavanaTimeline.Navigation.switchView('calendar');
```

### State Not Persisting
```javascript
// Check state value
console.log(MyavanaTimeline.State.dump());

// Manually set state
MyavanaTimeline.State.set('currentCalendarView', 'month');
```

## Integration Example
```javascript
(function() {
    'use strict';

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize navigation
        MyavanaTimeline.Navigation.init();

        // Set default view
        MyavanaTimeline.Navigation.switchView('calendar');
        MyavanaTimeline.Navigation.setCalendarView('month');

        // Listen to navigation events
        document.addEventListener('myavana:view:changed', handleViewChange);
        document.addEventListener('myavana:slider:moved', handleSliderMove);
    });

    function handleViewChange(e) {
        const view = e.detail.view;
        console.log('View changed to:', view);
        
        // Load view-specific data
        if (view === 'slider') {
            loadSliderData();
        }
    }

    function handleSliderMove(e) {
        const { index, total } = e.detail;
        console.log(`Slide ${index + 1} of ${total}`);
    }

    function loadSliderData() {
        // Implementation
    }
})();
```

## Dependencies
- **Required**: MyavanaTimeline.State
- **External**: Splide.js
- **Optional**: initListView() function for list view

## Browser Support
- Modern browsers with ES6 support
- IE11+ with polyfills

## Performance Notes
- Slider initialization has 100ms delay for DOM readiness
- Carousel scrolling uses smooth scroll behavior
- Event listeners are cloned/replaced to prevent duplicates

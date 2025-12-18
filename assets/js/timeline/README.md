# MYAVANA Timeline - Modular Architecture

**Version**: 2.3.5
**Status**: ✅ Production Ready
**Last Updated**: 2025-10-22

## Quick Start

All modules are automatically loaded by WordPress in the correct order. The initialization happens automatically on DOMContentLoaded.

### Verify Modules Loaded

Open browser console and run:
```javascript
MyavanaTimeline.debug();
```

Should show all 9 modules loaded: State, UI, Offcanvas, Navigation, ListView, View, Forms, Filters, Comparison

## Module Overview

| Module | Size | Purpose | Dependencies |
|--------|------|---------|--------------|
| `timeline-state.js` | 4.1KB | Centralized state management | None |
| `timeline-ui-state.js` | 8.9KB | Theme, sidebar, responsive | State |
| `timeline-offcanvas.js` | 9.2KB | Modal system | State |
| `timeline-navigation.js` | 9.3KB | View switching, slider | State, Splide |
| `timeline-list-view.js` | 5.0KB | Filter, sort, search | State |
| `timeline-view.js` | 34KB | View mode (entry/goal/routine) | State, Offcanvas |
| `timeline-forms.js` | 50KB | Create/edit forms | State, Offcanvas, FilePond |
| `timeline-filters.js` | 8.0KB | Timeline filtering | State |
| `timeline-comparison.js` | 16KB | Analysis comparison | State |
| `timeline-init.js` | 8.1KB | Orchestrator | ALL modules |

**Total**: 10 files, ~152KB

## Usage Examples

### Accessing State
```javascript
// Get state
const filter = MyavanaTimeline.State.get('currentFilter');

// Set state
MyavanaTimeline.State.set('currentFilter', 'entries');

// Subscribe to changes
MyavanaTimeline.State.subscribe((key, value) => {
    console.log(`${key} changed to:`, value);
});
```

### Opening Modals
```javascript
// Open create entry form
MyavanaTimeline.Forms.openOffcanvas('entry');

// Open edit entry form
MyavanaTimeline.Forms.openOffcanvas('entry', 123);

// Open view entry
MyavanaTimeline.View.openView('entry', 123);

// Close any offcanvas
MyavanaTimeline.Offcanvas.close();
```

### Navigation
```javascript
// Switch views
MyavanaTimeline.Navigation.switchView('calendar');
MyavanaTimeline.Navigation.switchView('slider');
MyavanaTimeline.Navigation.switchView('list');

// Calendar views
MyavanaTimeline.Navigation.setCalendarView('month');
MyavanaTimeline.Navigation.setCalendarView('week');
MyavanaTimeline.Navigation.setCalendarView('day');

// Initialize slider
MyavanaTimeline.Navigation.initSlider();
```

### List View
```javascript
// Initialize
MyavanaTimeline.ListView.init();

// Update display
MyavanaTimeline.ListView.update();

// Sort items
const items = Array.from(document.querySelectorAll('.list-item-hjn'));
MyavanaTimeline.ListView.sort(items);
```

### Filtering
```javascript
// Set filter
MyavanaTimeline.Filters.setFilter('entries');

// Apply filters
MyavanaTimeline.Filters.apply();

// Clear filters
MyavanaTimeline.Filters.clear();

// Toggle filter panel
MyavanaTimeline.Filters.togglePanel();
```

### Comparison
```javascript
// Open comparison modal
MyavanaTimeline.Comparison.open();

// Generate comparison
MyavanaTimeline.Comparison.generate();

// Close modal
MyavanaTimeline.Comparison.close();
```

## Backward Compatibility

All functions are exposed globally for inline onclick handlers:

```javascript
// Global functions (for HTML onclick attributes)
window.toggleDarkMode()
window.toggleSidebar()
window.switchView(viewName)
window.openOffcanvas(type, id)
window.closeOffcanvas()
window.editEntry()
window.addMilestone()
// ... and many more
```

## Events Dispatched

Modules communicate via custom events:

```javascript
// Listen for view changes
document.addEventListener('myavana:view:changed', (e) => {
    console.log('View changed to:', e.detail.view);
});

// Listen for slider movement
document.addEventListener('myavana:slider:moved', (e) => {
    console.log('Slider at index:', e.detail.index);
});

// Listen for filter changes
document.addEventListener('myavana:filters:applied', (e) => {
    console.log('Filters applied:', e.detail);
});

// Listen for state changes
document.addEventListener('myavana:state:changed', (e) => {
    console.log(`State ${e.detail.key} = ${e.detail.value}`);
});
```

## Debugging

### Check Module Loading
```javascript
// See all modules
console.log(Object.keys(MyavanaTimeline));

// Verify specific module
if (MyavanaTimeline.Forms) {
    console.log('Forms module loaded');
}
```

### Check Current State
```javascript
// Dump all state
MyavanaTimeline.State.dump();

// Check specific state
console.log('Current filter:', MyavanaTimeline.State.get('currentFilter'));
console.log('Current offcanvas:', MyavanaTimeline.State.get('currentOffcanvas'));
```

### Enable Verbose Logging
Open browser DevTools console and look for:
- `[Timeline Init]` - Initialization logs
- `[State]` - State management logs
- Module-specific logs with descriptive prefixes

## Troubleshooting

**Modules not loading:**
1. Check browser console for 404 errors
2. Verify WordPress enqueue in plugin file (line 298-344)
3. Clear browser cache
4. Check file permissions

**Functions not defined:**
1. Verify module loaded: `console.log(MyavanaTimeline)`
2. Check for JavaScript errors before function call
3. Ensure calling after DOMContentLoaded

**State not updating:**
1. Use `MyavanaTimeline.State.set()` not direct assignment
2. Subscribe to changes to verify: `MyavanaTimeline.State.subscribe(console.log)`
3. Check console for state change events

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────┐
│                  timeline-init.js                       │
│                   (Orchestrator)                        │
└─────────────────────────────────────────────────────────┘
                           │
           ┌───────────────┼───────────────┐
           │               │               │
           ▼               ▼               ▼
   ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
   │ UI State     │ │ Navigation   │ │ ListView     │
   │ - Dark mode  │ │ - Slider     │ │ - Filter     │
   │ - Sidebar    │ │ - Views      │ │ - Search     │
   └──────────────┘ └──────────────┘ └──────────────┘
           │               │               │
           └───────────────┼───────────────┘
                           │
                           ▼
                   ┌──────────────┐
                   │    State     │
                   │ (Centralized)│
                   └──────────────┘
                           │
           ┌───────────────┼───────────────┐
           │               │               │
           ▼               ▼               ▼
   ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
   │ Offcanvas    │ │ View         │ │ Forms        │
   │ - Modals     │ │ - Entry      │ │ - Create     │
   │ - Overlays   │ │ - Goal       │ │ - Edit       │
   │              │ │ - Routine    │ │ - Submit     │
   └──────────────┘ └──────────────┘ └──────────────┘
                           │
           ┌───────────────┼───────────────┐
           │                               │
           ▼                               ▼
   ┌──────────────┐               ┌──────────────┐
   │ Filters      │               │ Comparison   │
   │ - Timeline   │               │ - Analysis   │
   │ - Search     │               │ - Metrics    │
   └──────────────┘               └──────────────┘
```

## Files in This Directory

- **timeline-state.js** - Centralized state management with reactive updates
- **timeline-ui-state.js** - Theme, sidebar, responsive behavior
- **timeline-offcanvas.js** - Modal/offcanvas system
- **timeline-navigation.js** - View switching and Splide slider
- **timeline-list-view.js** - List filtering, sorting, searching
- **timeline-view.js** - View mode for entries, goals, routines
- **timeline-forms.js** - Create/edit forms with FilePond
- **timeline-filters.js** - Timeline filtering system
- **timeline-comparison.js** - AI analysis comparison
- **timeline-init.js** - Main orchestrator and initialization
- **README.md** - This file

## Additional Documentation

See parent directory for comprehensive documentation:
- `TIMELINE_REFACTORING_COMPLETE.md` - Full refactoring summary
- `TIMELINE_REFACTORING_PLAN.md` - Original refactoring plan
- `TIMELINE_REFACTORING_STATUS.md` - Progress tracker
- `NEW_TIMELINE_FILE_BREAKDOWN.md` - Original file analysis

## Support

For issues or questions:
1. Check browser console for errors
2. Run `MyavanaTimeline.debug()` for state dump
3. Review module-specific documentation in this directory
4. Check main documentation files in parent directory

---

**Built with ❤️ for MYAVANA Hair Journey**

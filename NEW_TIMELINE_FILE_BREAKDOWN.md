# new-timeline.js - Complete File Breakdown & Modularization Guide

## File Overview
- **Total Lines:** 3575
- **Type:** Monolithic JavaScript file
- **Purpose:** Timeline view functionality for Hair Journey plugin
- **Status:** NEEDS MODULARIZATION

---

## GLOBAL VARIABLES (Lines 1-2)

### State Variables
- `splide` - Splide slider instance
- `currentCalendarView` - Calendar view state ('month', 'week', 'day')

**Dependencies:** None
**Used By:** Calendar functions, slider initialization

---

## SECTION 1: THEME & UI STATE MANAGEMENT (Lines 4-211)

### Functions:
1. **toggleDarkMode()** - Lines 5-38
   - Dependencies: None
   - DOM Elements: `.hair-journey-container`, `.sun-icon`, `.moon-icon`
   - Storage: localStorage.setItem('theme')

2. **loadTheme()** - Lines 41-68
   - Dependencies: None
   - DOM Elements: Same as toggleDarkMode
   - Storage: localStorage.getItem('theme')

3. **toggleSidebar()** - Lines 71-93
   - Dependencies: window.innerWidth
   - DOM Elements: `#sidebar`, `#sidebarToggle`
   - Storage: localStorage.setItem('sidebarCollapsed')

4. **switchSidebarTab(tabName)** - Lines 96-111
   - Dependencies: None
   - DOM Elements: `.sidebar-tab`, `.sidebar-tab-content`
   - Storage: localStorage.setItem('activeSidebarTab')

5. **loadSidebarState()** - Lines 114-137
   - Dependencies: window.innerWidth
   - Calls: switchSidebarTab()
   - Storage: localStorage.getItem('sidebarCollapsed', 'activeSidebarTab')

6. **handleResize()** - Lines 140-169
   - Dependencies: window.innerWidth
   - DOM Elements: `#sidebar`, `#sidebarToggle`
   - Storage: localStorage operations

7. **resetSidebar()** - Lines 172-177
   - Storage: localStorage.removeItem (multiple keys)
   - Action: location.reload()

8. **toggleMobileSidebar()** - Lines 180-194
   - Dependencies: window.innerWidth
   - DOM Elements: `#sidebar`, `#mobileSidebarIcon`
   - Storage: localStorage.setItem('mobileSidebarCollapsed')

9. **loadMobileSidebarState()** - Lines 197-206
   - Dependencies: window.innerWidth
   - Storage: localStorage.getItem('mobileSidebarCollapsed')

10. **editProfile()** - Lines 209-211
    - Simple alert placeholder

**Module Suggestion:** `timeline-ui-state.js`
**External Dependencies:** localStorage API
**Global Variables Modified:** None

---

## SECTION 2: OFFCANVAS SYSTEM (Lines 213-312)

### Global Variables (Lines 214-216):
- `currentOffcanvas` - Currently open create/edit offcanvas
- `selectedRating` - Selected rating value
- `uploadedFiles` - Array of uploaded files

### Functions:
1. **openOffcanvas(type)** - Lines 219-248
   - Parameters: 'entry', 'goal', 'routine'
   - DOM Elements: `#entryOffcanvas`, `#goalOffcanvas`, `#routineOffcanvas`, `#offcanvasOverlay`
   - Side Effects: Sets body overflow hidden

2. **closeOffcanvas()** - Lines 251-265
   - Calls: closeTimelineViewOffcanvas() OR closeCreateOffcanvas()
   - Global State: Checks currentViewOffcanvas, currentOffcanvas

3. **closeTimelineViewOffcanvas()** - Lines 268-281
   - DOM Elements: `#viewOffcanvasOverlay`
   - Global State: Sets currentViewOffcanvas = null

4. **closeCreateOffcanvas()** - Lines 287-304
   - DOM Elements: `#createOffcanvasOverlay`, `#offcanvasOverlay`
   - Calls: resetOffcanvasForms()
   - Global State: Sets currentOffcanvas = null

5. **closeAllOffcanvases()** - Lines 309-312
   - Calls: closeCreateOffcanvas(), closeViewOffcanvas()

**Module Suggestion:** `timeline-offcanvas.js`
**Dependencies:** DOM manipulation
**Global Variables Used:** currentOffcanvas, currentViewOffcanvas

---

## SECTION 3: LIST VIEW FUNCTIONALITY (Lines 314-423)

### Global Variables (Lines 315-317):
- `currentFilter` - Current filter type ('all', 'entries', 'goals', 'routines')
- `currentSearch` - Search query string
- `currentSort` - Sort option ('date-desc', 'date-asc', 'title-asc', 'title-desc', 'type')

### Functions:
1. **initListView()** - Lines 320-360
   - DOM Elements: `.filter-chip-hjn`, `#listSearchInput`, `#searchClearBtn`, `#listSortSelect`
   - Event Listeners: click, input, change
   - Calls: updateListView()

2. **updateListView()** - Lines 363-396
   - DOM Elements: `#listGrid`, `.list-item-hjn`, `.list-empty-state-hjn`
   - Calls: sortListItems()
   - Logic: Filter by type, search, visibility

3. **sortListItems(items)** - Lines 399-423
   - Parameters: Array of DOM elements
   - DOM Manipulation: Re-orders items in grid
   - Logic: Multi-criteria sorting

**Module Suggestion:** `timeline-list-view.js`
**Dependencies:** DOM API
**Global Variables Modified:** currentFilter, currentSearch, currentSort

---

## SECTION 4: FORM UTILITIES (Lines 426-518)

### Functions:
1. **resetOffcanvasForms()** - Lines 426-434
   - DOM Elements: All `.offcanvas form` elements
   - Global State: Resets selectedRating, uploadedFiles
   - DOM: Clears `.chip`, `.rating-star`, `#entryPreviewGrid`

2. **submitEntry()** - Lines 437-441
   - Simple placeholder with alert

3. **submitGoal()** - Lines 443-447
   - Simple placeholder with alert

4. **submitRoutine()** - Lines 449-453
   - Simple placeholder with alert

5. **initRatingSelector()** - Lines 456-463
   - DOM Elements: `.rating-star`
   - Event: click
   - Calls: updateRatingDisplay()

6. **updateRatingDisplay(container)** - Lines 465-471
   - Parameters: Container element
   - Global State: Uses selectedRating

7. **initChipSelectors()** - Lines 474-484
   - DOM Elements: `.chip`
   - Event: click
   - Logic: Single selection toggle

8. **initFileUpload()** - Lines 487-513
   - DOM Elements: `#entryFileInput`, `#entryPreviewGrid`
   - Event: change
   - Global State: Modifies uploadedFiles
   - Logic: Image preview with FileReader

9. **removePhoto(btn, fileName)** - Lines 515-518
   - Parameters: Button element, filename string
   - Global State: Filters uploadedFiles array

**Module Suggestion:** `timeline-form-utils.js`
**Dependencies:** FileReader API
**Global Variables Used:** selectedRating, uploadedFiles

---

## SECTION 5: OFFCANVAS CLICK HANDLERS (Lines 520-547)

### Functions:
1. **initOffcanvasClickHandlers()** - Lines 522-547
   - DOM Elements: `.offcanvas`, `#offcanvasOverlay`, `#viewOffcanvasOverlay`
   - Events: click
   - Calls: closeOffcanvas(), closeViewOffcanvas()

**Module Suggestion:** Merge into `timeline-offcanvas.js`

---

## SECTION 6: SLIDER & NAVIGATION (Lines 549-673)

### Functions:
1. **initSlider()** - Lines 550-591
   - External Dependency: Splide library
   - DOM Elements: `#hairJourneySlider`, `#progress`, `.date-marker`
   - Global State: Sets/destroys splide variable
   - Events: splide.on('moved')

2. **switchView(viewName)** - Lines 594-620
   - Parameters: 'slider', 'calendar', 'list'
   - DOM Elements: `.view-btn`, `.tab`, `.view-content`
   - Calls: initListView(), initSlider()

3. **setCalendarView(view)** - Lines 623-645
   - Parameters: 'month', 'week', 'day'
   - Global State: Sets currentCalendarView
   - DOM Elements: `.view-toggle`, `#monthView`, `#weekView`, `#dayView`, `#dateRange`

4. **addGoal()** - Lines 648-650
   - Placeholder alert

5. **addRoutine()** - Lines 652-654
   - Placeholder alert

6. **addEntry()** - Lines 656-658
   - Placeholder alert

7. **scrollCarousel(direction)** - Lines 666-673
   - Parameters: direction (number)
   - DOM Elements: `#carouselTrack`
   - Logic: Smooth horizontal scroll

**Module Suggestion:** `timeline-navigation.js`
**Dependencies:** Splide.js library
**Global Variables Used:** splide, currentCalendarView

---

## SECTION 7: DOM CONTENT LOADED EVENT (Lines 676-767)

### Main Initialization Function:
**DOMContentLoaded Event Listener** - Lines 676-767

**Initializes:**
1. loadTheme()
2. loadSidebarState()
3. loadMobileSidebarState()
4. switchView('calendar')
5. setCalendarView('month')
6. Theme toggle button event
7. View buttons click events
8. Overlay click handlers
9. Escape key handler
10. Timeline control tabs
11. Carousel items click
12. Date markers click
13. Goal items click
14. Window resize handler
15. initRatingSelector()
16. initChipSelectors()
17. initFileUpload()
18. initProductSelector()
19. initOffcanvasClickHandlers()
20. initListView()

**Module Suggestion:** `timeline-init.js`
**Dependencies:** All other modules
**Purpose:** Main entry point and initialization orchestrator

---

## SECTION 8: LIST VIEW FUNCTIONALITY (DUPLICATE) (Lines 769-911)

⚠️ **CRITICAL: This is a DUPLICATE of Section 3 (Lines 314-423)**

### Duplicate Function:
**initListView()** - Lines 771-911 (EXACT DUPLICATE)

**Resolution Needed:** Remove this duplicate section entirely

---

## SECTION 9: VIEW OFFCANVAS FUNCTIONALITY (Lines 913-1593)

### Global Variables (Lines 915-916):
- `currentViewOffcanvas` - Currently open view offcanvas
- `currentViewData` - Data for current viewed item

### Functions:

1. **openViewOffcanvas(type, id)** - Lines 919-956
   - Parameters: 'entry', 'goal', 'routine' + ID
   - DOM Elements: `#entryViewOffcanvas`, `#goalViewOffcanvas`, `#routineViewOffcanvas`, `#viewOffcanvasOverlay`
   - Calls: loadEntryView(), loadGoalView(), loadRoutineView()

2. **loadEntryView(entryId)** - Lines 984-1023
   - AJAX Call: 'myavana_get_entry_details'
   - Settings: window.myavanaTimelineSettings
   - Calls: populateEntryView() or showViewError()

3. **populateEntryView(entry)** - Lines 1026-1225
   - DOM Elements: `#entryTitle`, `#entryDate`, `#entryGallery`, `#entryPrimaryImage`, etc.
   - Logic: Complex image handling, rating stars, products parsing
   - Global State: Sets currentViewData

4. **loadGoalView(goalIndex)** - Lines 1228-1251
   - DOM Query: `[data-goal-index="${goalIndex}"]`
   - Calls: extractGoalData(), populateGoalView()

5. **extractGoalData(listItem)** - Lines 1254-1268
   - Parameters: DOM element
   - Returns: Goal data object

6. **populateGoalView(goal)** - Lines 1271-1328
   - DOM Elements: `#goalTitle`, `#goalDateRange`, `#goalProgressPercent`, `#goalProgressRing`, etc.
   - Calls: populateGoalProgressHistory(), populateGoalProgressNotes()
   - Global State: Sets currentViewData

7. **populateGoalProgressHistory(history)** - Lines 1331-1353
   - Parameters: Array of history entries
   - DOM Elements: `#goalProgressHistory`
   - Logic: Sort and format timeline

8. **populateGoalEditProgressNotes(notes)** - Lines 1356-1374
   - Parameters: Array of note objects
   - DOM Elements: `#goalProgressNotesList`

9. **initProgressNoteCounter(textarea)** - Lines 1377-1388
   - Parameters: Textarea element
   - DOM Elements: `#progress_note_count`
   - Event: input

10. **populateGoalProgressNotes(notes)** - Lines 1391-1423
    - Parameters: Array of notes
    - DOM Elements: `#goalProgressNotes`

11. **loadRoutineView(routineId)** - Lines 1426-1474
    - Data Source: `#calendarDataHjn` JSON or `[data-routine-index]`
    - Calls: populateRoutineView() or showViewError()

12. **extractRoutineData(listItem)** - Lines 1477-1489
    - Parameters: DOM element
    - Returns: Routine data object

13. **populateRoutineView(routine)** - Lines 1492-1566
    - DOM Elements: `#routineTitle`, `#routineSchedule`, `#routineDescription`, `#routineSteps`
    - Global State: Sets currentViewData

14. **showViewError(message)** - Lines 1569-1593
    - DOM: Injects error HTML into offcanvas body

**Module Suggestion:** `timeline-view-offcanvas.js`
**Dependencies:** Fetch API, myavanaTimelineSettings global
**Global Variables Used:** currentViewOffcanvas, currentViewData

---

## SECTION 10: EDIT FUNCTIONALITY (Lines 1596-2030)

### Functions:

1. **editEntry()** - Lines 1598-1607
   - Calls: closeTimelineViewOffcanvas(), openEditOffcanvas()
   - Global State: Uses currentViewData

2. **editGoal()** - Lines 1609-1616
   - Calls: closeTimelineViewOffcanvas(), openEditOffcanvas()

3. **editRoutine()** - Lines 1618-1625
   - Calls: closeTimelineViewOffcanvas(), openEditOffcanvas()

4. **openEditOffcanvas(type, data)** - Lines 1628-1650
   - Parameters: Type and data object
   - Calls: populateEditForm()
   - Global State: Sets currentOffcanvas

5. **populateEditForm(type, data)** - Lines 1653-1665
   - Router function
   - Calls: populateEntryForm(), populateGoalForm(), or populateRoutineForm()

6. **populateEntryForm(entryData)** - Lines 1668-1745
   - DOM Elements: Multiple form inputs
   - Calls: updateCharacterCount(), updateRatingStars()

7. **populateGoalForm(goalData)** - Lines 1748-1841
   - DOM Elements: Goal form inputs
   - Calls: addMilestone(), updateProgressValue(), initProgressNoteCounter(), populateGoalEditProgressNotes()

8. **populateRoutineForm(routineData)** - Lines 1844-1912
   - DOM Elements: Routine form inputs
   - Calls: addRoutineStep()

9. **updateCharacterCount(textarea, countElementId)** - Lines 1915-1920
   - Utility function

10. **updateRatingStars(rating)** - Lines 1923-1939
    - DOM Elements: `.rating-star-hjn`, `#health_rating_value`

11. **closeOffcanvas()** - Lines 1942-1959 (REDEFINITION)
    - ⚠️ Redefines earlier function
    - Calls: resetEditForm()

12. **resetEditForm(type)** - Lines 1962-2030
    - Parameters: 'entry', 'goal', 'routine'
    - DOM: Resets titles, buttons, form fields

**Module Suggestion:** `timeline-edit-forms.js`
**Dependencies:** Form population utilities
**Global Variables Used:** currentViewData, currentOffcanvas

---

## SECTION 11: CREATE/EDIT FORMS FUNCTIONALITY (Lines 2033-3177)

### Global Variables (Line 2038):
- `entryFilePond` - FilePond instance for entry photos

### Functions:

1. **initCreateForms()** - Lines 2043-2069
   - Initialization orchestrator
   - Calls: initFilePond(), initRatingStars(), initFormSubmissions(), initCharacterCounters(), initGoalFormElements()

2. **initFilePond()** - Lines 2074-2098
   - External Dependency: FilePond library
   - DOM Elements: `#entry_photos`
   - Global State: Sets entryFilePond

3. **initRatingStars()** - Lines 2103-2132
   - DOM Elements: `#health_rating_stars`, `#health_rating`, `#health_rating_value`
   - Event: click

4. **initFormSubmissions()** - Lines 2137-2155
   - Event Listeners: submit on `#entryForm`, `#goalForm`, `#routineForm`
   - Calls: handleEntrySubmit(), handleGoalSubmit(), handleRoutineSubmit()

5. **initCharacterCounters()** - Lines 2160-2169
   - DOM Elements: `#entry_content`, `#entry_content_count`
   - Event: input

6. **initGoalFormElements()** - Lines 2174-2180
   - Calls: initProgressNoteCounter()

7. **openOffcanvas(type, id = null)** - Lines 2185-2228 (REDEFINITION #2)
   - ⚠️ Third definition of this function
   - Calls: loadEntryForEdit(), resetEntryForm(), etc.
   - Global State: Sets currentOffcanvas

8. **resetEntryForm()** - Lines 2234-2256
   - DOM: Resets all entry form fields
   - FilePond: Removes files

9. **resetGoalForm()** - Lines 2261-2275
   - DOM: Resets goal form fields

10. **resetRoutineForm()** - Lines 2280-2301
    - DOM: Resets routine form fields

11. **handleEntrySubmit(e)** - Lines 2306-2383
    - AJAX: 'myavana_add_entry' action
    - FormData: Complex field mapping
    - Calls: showNotification(), closeOffcanvas(), refreshCurrentView()

12. **handleGoalSubmit(e)** - Lines 2388-2466
    - AJAX: 'myavana_save_hair_goal' action
    - Dependencies: myavanaProfileAjax global

13. **handleRoutineSubmit(e)** - Lines 2471-2537
    - AJAX: 'myavana_save_routine_step' action

14. **addMilestone()** - Lines 2542-2558
    - DOM: Creates milestone input

15. **removeMilestone(button)** - Lines 2563-2565
    - DOM: Removes milestone

16. **addRoutineStep()** - Lines 2570-2589
    - DOM: Creates step input
    - Calls: updateRemoveStepButtons()

17. **removeRoutineStep(button)** - Lines 2594-2610
    - DOM: Removes and renumbers steps
    - Calls: updateRemoveStepButtons()

18. **updateRemoveStepButtons()** - Lines 2615-2623
    - DOM: Disables remove button if only one step

19. **updateProgressValue(value)** - Lines 2628-2633
    - DOM: Updates progress display

20. **refreshCurrentView()** - Lines 2638-2646
    - Action: window.location.reload()

21. **showNotification(message, type)** - Lines 2651-2682
    - DOM: Creates notification element
    - CSS: Inline styles
    - Auto-remove: 3 second timeout

22. **loadEntryForEdit(id)** - Lines 2685-2733
    - AJAX: 'myavana_get_entry_details'
    - Calls: populateEntryForm()

23. **loadGoalForEdit(id)** - Lines 2735-2761
    - Data Source: `#calendarDataHjn` JSON
    - Calls: populateGoalForm()

24. **loadRoutineForEdit(id)** - Lines 2763-2788
    - Data Source: `#calendarDataHjn` JSON
    - Calls: populateRoutineForm()

25. **populateEntryForm(entry)** - Lines 2791-2904 (REDEFINITION)
    - ⚠️ Second definition with more detail
    - DOM: Extensive form population
    - Calls: populateExistingImages()

26. **populateExistingImages(entryData)** - Lines 2907-2960
    - DOM Elements: `#existingImagesGallery`, `#existingImagesGrid`

27. **removeExistingImage(imageUrl, index)** - Lines 2963-2975
    - DOM: Removes image from gallery

28. **initProductSelector()** - Lines 2978-3020
    - External Dependency: Select2 library
    - DOM Elements: `#products_used`
    - Data: Hardcoded product list

29. **populateGoalForm(goal)** - Lines 3023-3069 (REDEFINITION)
    - ⚠️ Second definition

30. **populateRoutineForm(routine)** - Lines 3072-3169 (REDEFINITION)
    - ⚠️ Second definition

31. **DOMContentLoaded Event Listener** - Lines 3172-3177
    - Delayed initialization of goal form elements

**Module Suggestion:** `timeline-create-edit-forms.js`
**Dependencies:** FilePond, Select2, Fetch API
**Global Variables Used:** entryFilePond, myavanaTimelineSettings, myavanaProfileAjax
**Critical Issues:** Multiple function redefinitions!

---

## SECTION 12: TIMELINE FILTER FUNCTIONS (Lines 3181-3282)

### Global Variables (Line 3185):
- `timelineCurrentFilter` - Current timeline filter ('all', 'entry', 'goal', 'routine')

### Functions:

1. **setTimelineFilter(filterType)** - Lines 3190-3205
   - DOM Elements: `.timeline-filter-btn-hjn[data-filter]`
   - Calls: applyTimelineFilters()

2. **toggleTimelineFilterPanel()** - Lines 3210-3219
   - DOM Elements: `#timelineFiltersPanel`
   - Action: Toggle display

3. **applyTimelineFilters()** - Lines 3224-3267
   - DOM Elements: `#timelineSearchInput`, `#timelineFilterRating`, `.timeline-month-group-hjn`, `.timeline-item-hjn`
   - Logic: Multi-criteria filtering (type, search, rating)

4. **clearTimelineFilters()** - Lines 3272-3282
   - Calls: setTimelineFilter('all')
   - DOM: Resets filter inputs

**Module Suggestion:** `timeline-filters.js`
**Dependencies:** None
**Global Variables Used:** timelineCurrentFilter

---

## SECTION 13: COMPARE ANALYSIS FUNCTIONS (Lines 3285-3574)

### Functions:

1. **openCompareModal()** - Lines 3291-3349
   - DOM Elements: `#compareAnalysisModal`, `.analysis-slide`, `#compareAnalysis1`, `#compareAnalysis2`
   - Global State: Sets window.availableAnalyses

2. **closeCompareModal()** - Lines 3354-3366
   - DOM Elements: `#compareAnalysisModal`, `#comparisonResults`

3. **generateComparison()** - Lines 3371-3405
   - Calls: extractAnalysisData(), displayComparison()
   - Global State: Uses window.availableAnalyses

4. **extractAnalysisData(slideElement, date)** - Lines 3410-3440
   - Parameters: DOM element, date string
   - Returns: Analysis data object

5. **displayComparison(data1, data2)** - Lines 3445-3504
   - Calls: calculateDiff(), generateMetricRow(), generateInsight()
   - DOM: Injects comparison HTML

6. **calculateDiff(val1, val2)** - Lines 3509-3526
   - Parameters: Two numeric values
   - Returns: Diff object with percent, direction

7. **generateMetricRow(label, val1, val2, diffData)** - Lines 3531-3551
   - Returns: HTML string

8. **generateInsight(healthDiff, hydrationDiff, elasticityDiff)** - Lines 3556-3573
   - Parameters: Three diff objects
   - Returns: Insight text

**Module Suggestion:** `timeline-compare-analysis.js`
**Dependencies:** None
**Global Variables Used:** window.availableAnalyses (temporary storage)

---

## CRITICAL ISSUES FOUND

### 1. Function Redefinitions (Name Collisions)
These functions are defined MULTIPLE times:

- **`openOffcanvas()`**:
  - Lines 219-248 (original)
  - Lines 2185-2228 (redefinition with different signature)

- **`closeOffcanvas()`**:
  - Lines 251-265 (original)
  - Lines 1942-1959 (redefinition)

- **`initListView()`**:
  - Lines 320-360 (original)
  - Lines 771-911 (EXACT DUPLICATE - DELETE THIS!)

- **`populateEntryForm()`**:
  - Lines 1668-1745 (first version)
  - Lines 2791-2904 (expanded version)

- **`populateGoalForm()`**:
  - Lines 1748-1841 (first version)
  - Lines 3023-3069 (second version)

- **`populateRoutineForm()`**:
  - Lines 1844-1912 (first version)
  - Lines 3072-3169 (second version)

### 2. Duplicate Code Blocks
- Lines 769-911: Entire `initListView()` function duplicated

### 3. Global Variable Conflicts
- Multiple sections modify shared globals without coordination
- Risk of race conditions and state corruption

---

## RECOMMENDED MODULE STRUCTURE

### Core Modules (7 files):

1. **`timeline-state.js`** (Lines 1-2, 214-216, 315-317, 915-916, 2038, 3185)
   - Centralized state management
   - All global variables
   - State getters/setters

2. **`timeline-ui-controls.js`** (Lines 4-211)
   - Dark mode
   - Sidebar controls
   - Mobile responsive handlers

3. **`timeline-offcanvas.js`** (Lines 213-312, 520-547)
   - Unified offcanvas open/close
   - Overlay management
   - Click handlers

4. **`timeline-navigation.js`** (Lines 549-673)
   - View switching
   - Slider initialization
   - Calendar view controls

5. **`timeline-list-view.js`** (Lines 314-423, DELETE 769-911)
   - List filtering
   - Sorting
   - Search

6. **`timeline-view-offcanvas.js`** (Lines 913-1593)
   - View entry/goal/routine
   - Data loading via AJAX
   - Display population

7. **`timeline-create-edit.js`** (Lines 1596-3177, merged with utilities)
   - Form initialization
   - AJAX submissions
   - FilePond/Select2 integration
   - Edit mode handling

8. **`timeline-filters.js`** (Lines 3181-3282)
   - Timeline filtering
   - Search functionality

9. **`timeline-compare.js`** (Lines 3285-3574)
   - Analysis comparison
   - Metrics calculation

10. **`timeline-init.js`** (Lines 676-767)
    - Main initialization
    - Event wiring
    - Module coordination

---

## DEPENDENCY GRAPH

```
timeline-init.js
  ├─> timeline-state.js (initialize state)
  ├─> timeline-ui-controls.js (load theme/sidebar)
  ├─> timeline-navigation.js (set default view)
  ├─> timeline-list-view.js (init list)
  ├─> timeline-offcanvas.js (setup handlers)
  ├─> timeline-create-edit.js (init forms)
  └─> timeline-filters.js (ready filters)

timeline-navigation.js
  ├─> timeline-state.js (read/write splide, currentCalendarView)
  └─> timeline-list-view.js (call initListView on view switch)

timeline-view-offcanvas.js
  ├─> timeline-state.js (read/write currentViewOffcanvas)
  ├─> timeline-offcanvas.js (close overlay)
  └─> timeline-create-edit.js (trigger edit mode)

timeline-create-edit.js
  ├─> timeline-state.js (read/write entryFilePond)
  ├─> timeline-offcanvas.js (close after submit)
  └─> External: FilePond, Select2

timeline-compare.js
  └─> Standalone (no internal deps)
```

---

## EXTERNAL DEPENDENCIES

### JavaScript Libraries:
1. **Splide.js** - Used in `initSlider()` (Line 557)
2. **FilePond** - Used in `initFilePond()` (Line 2081)
3. **Select2** - Used in `initProductSelector()` (Line 3006)
4. **jQuery** - Used with Select2 (Line 2878, 3006)

### WordPress AJAX:
- `myavanaTimelineSettings` global object (Lines 996, 2349, 2700)
- `myavanaProfileAjax` global object (Lines 2440, 2511)

### Browser APIs:
- localStorage API (throughout)
- Fetch API (Lines 999, 2352, 2442, 2513, 2703)
- FileReader API (Line 499)

---

## REFACTORING PRIORITY

### CRITICAL (Do First):
1. ✅ **Remove duplicate `initListView()` function** (Lines 769-911)
2. ✅ **Resolve `openOffcanvas()` conflicts** - Rename one version
3. ✅ **Resolve `closeOffcanvas()` conflicts** - Merge implementations
4. ✅ **Resolve `populate*Form()` conflicts** - Use expanded versions only

### HIGH:
5. Extract state management to `timeline-state.js`
6. Split into 10 modules as outlined above
7. Create module loader/orchestrator

### MEDIUM:
8. Add JSDoc comments to all functions
9. Create unit tests for each module
10. Implement error boundaries

### LOW:
11. Optimize function signatures
12. Reduce DOM queries (cache selectors)
13. Add TypeScript definitions

---

## SAFE SPLITTING STRATEGY

### Phase 1: Preparation
1. Create backup of original file
2. Set up module directory structure
3. Create empty module files

### Phase 2: State Extraction
1. Create `timeline-state.js`
2. Move all global variables to state module
3. Create getters/setters
4. Update all references

### Phase 3: Module Extraction (One at a time)
For each module:
1. Copy functions to module file
2. Add module wrapper (IIFE or ES6 module)
3. Export public functions
4. Update imports in other modules
5. Test functionality
6. Remove from original file

### Phase 4: Integration
1. Create `timeline-init.js` orchestrator
2. Wire up all modules
3. Test complete functionality
4. Delete original file

### Phase 5: Optimization
1. Eliminate duplicate code
2. Optimize cross-module communication
3. Add error handling
4. Performance testing

---

## FUNCTION DEPENDENCY MAP

### Functions with NO dependencies (Pure/Standalone):
- `calculateDiff()` (Line 3509)
- `generateMetricRow()` (Line 3531)
- `updateCharacterCount()` (Line 1915)
- `extractGoalData()` (Line 1254)
- `extractRoutineData()` (Line 1477)
- `extractAnalysisData()` (Line 3410)

### Functions that depend on state only:
- `loadTheme()` (Line 41)
- `toggleDarkMode()` (Line 5)
- `updateRatingDisplay()` (Line 465)
- `updateProgressValue()` (Line 2628)
- `sortListItems()` (Line 399)

### Functions with heavy DOM dependencies:
- `populateEntryView()` (Line 1026)
- `populateGoalView()` (Line 1271)
- `populateRoutineView()` (Line 1492)
- All `populate*Form()` functions

### Functions with AJAX dependencies:
- `loadEntryView()` (Line 984)
- `handleEntrySubmit()` (Line 2306)
- `handleGoalSubmit()` (Line 2388)
- `handleRoutineSubmit()` (Line 2471)
- `loadEntryForEdit()` (Line 2685)

---

## TESTING RECOMMENDATIONS

### Unit Tests Needed:
1. State management (get/set/reset)
2. Filter logic (`applyTimelineFilters`)
3. Sort logic (`sortListItems`)
4. Diff calculation (`calculateDiff`)
5. Data extraction functions
6. Form validation

### Integration Tests Needed:
1. Offcanvas open/close flow
2. View switching
3. Form submission flow
4. Edit mode flow
5. Filter + search + sort combination

### E2E Tests Needed:
1. Create entry workflow
2. Edit entry workflow
3. Compare analysis workflow
4. View switching and state persistence

---

## CONCLUSION

This 3575-line file is a **monolithic spaghetti code** situation that MUST be refactored. The critical issues are:

1. **Duplicate code** (769-911)
2. **Function name collisions** (6+ functions redefined)
3. **No module boundaries** (everything in global scope)
4. **Circular dependencies** (functions calling each other)
5. **Poor maintainability** (impossible to debug)

**Recommended Action:** Follow the 5-phase splitting strategy to break this into 10 modular, testable, maintainable files.

**Estimated Effort:** 16-24 hours of careful refactoring work.

**Risk Level:** HIGH if not done correctly. Must maintain exact functionality while splitting.

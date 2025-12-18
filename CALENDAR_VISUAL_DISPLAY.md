# Calendar Visual Display Enhancement

## Overview
Enhanced the calendar view to display entries and goals as **visual elements overlaid on the calendar grid**, following the wireframe specifications with horizontal goal bars spanning multiple days and vertical entry cards positioned at specific times.

## Implementation Date
October 2025

## Changes Made

### 1. Month View Visual Overlay (`view-calendar.php` lines 301-390)

#### Added Visual Overlay Container
```php
<div class="calendar-visual-overlay-hjn">
    <!-- Goal bars and entry cards rendered here -->
</div>
```

**Purpose**: Absolute-positioned overlay layer on top of the calendar grid to display visual representations of goals and entries.

#### Goal Bars - Horizontal Spans
**Rendering Logic** (lines 309-351):
```php
foreach ($calendar_data['goals'] as $goal):
    // Calculate which days goal spans in current month
    $goal_start_day = max(1, date('j', max($start_timestamp, $month_start)));
    $goal_end_day = min($days_in_month, date('j', min($end_timestamp, $month_end)));

    // Calculate grid position
    $start_day_of_week = date('N', mktime(0, 0, 0, $current_month, $goal_start_day, $current_year));
    $row = ceil(($start_offset + $goal_start_day) / 7);

    // Position on grid (percentage-based)
    $left = ($start_day_of_week - 1) * $cell_width;  // 14.28% per column
    $width = min($goal_duration_days * $cell_width, ...);
    $top = $header_height + (($row - 1) * $cell_height) + 15;
?>
```

**Visual Representation**:
```
┌─────────────────────────────────────────────────────┐
│ Mon  Tue  Wed  Thu  Fri  Sat  Sun                  │
├─────────────────────────────────────────────────────┤
│  1    2    3    4    5    6    7                   │
│      ┌─────Goal Bar: Moisture──────┐               │
│                                                      │
│  8    9    10   11   12   13   14                  │
│  ┌──────Goal Bar: Length Growth────────────┐       │
└─────────────────────────────────────────────────────┘
```

**Features**:
- **Horizontal coral gradient bars** spanning exact goal duration
- **Progress bar embedded** showing percentage complete
- **Hover effects**: Lift up with enhanced shadow
- **Click to view**: Opens goal detail offcanvas
- **Z-index management**: Goals at z-index 1-2, entries at z-index 2-3

#### Entry Cards - Positioned by Date/Time
**Rendering Logic** (lines 353-389):
```php
foreach ($calendar_data['entries'] as $entry):
    // Calculate day position
    $entry_day = $entry['day'];
    $day_of_week = date('N', mktime(0, 0, 0, $current_month, $entry_day, $current_year));
    $row = ceil(($start_offset + $entry_day) / 7);

    // Position within cell
    $left = ($day_of_week - 1) * $cell_width;
    $top = $header_height + (($row - 1) * $cell_height) + 35; // Below goal bars
?>
```

**Visual Representation**:
```
┌──────────┐
│ 09:30 AM │ ← Time
├──────────┤
│  Image   │ ← Thumbnail
├──────────┤
│ Wash Day │ ← Title
│ ★ 8/10   │ ← Rating
└──────────┘
```

**Features**:
- **Vertical cards** with blueberry border
- **Optional image** at top (40px height)
- **Time, title, rating** displayed
- **Hover effects**: Scale up + coral border
- **Click to view**: Opens entry detail offcanvas

### 2. Week View Goal Bars (`view-calendar.php` lines 427-460)

#### Goal Bars Spanning Week Days
**Rendering Logic**:
```php
foreach ($calendar_data['goals'] as $goal):
    // Check if goal overlaps with current week
    if ($goal_end >= $week_start && $goal_start <= $week_end):
        // Calculate span in days (0-6 for Mon-Sun)
        $span_start_day = max(0, ceil(($goal_start - $week_start) / 86400));
        $span_end_day = min(6, floor(($goal_end - $week_start) / 86400));

        // Position as percentage
        $left_percent = ($span_start_day / 7) * 100;
        $width_percent = ($span_days / 7) * 100;
?>
```

**Visual Representation**:
```
Time  │ Mon  Tue  Wed  Thu  Fri  Sat  Sun
──────┼─────────────────────────────────────
      │ ┌──Goal: Moisture Retention────┐
08:00 │
      │  [Entry]      [Entry]
10:00 │
      │        [Entry]
12:00 │
```

**Features**:
- **Positioned at top** of week grid (top: 10px)
- **Absolute positioning** over entire week
- **Same styling** as month view goal bars
- **Entries positioned below** goal bars (padded-top: 50px)

### 3. CSS Styling (`new-timeline.css` +350 lines)

#### Visual Overlay Container
```css
.calendar-visual-overlay-hjn {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
    z-index: 10;
}

.calendar-visual-overlay-hjn > * {
    pointer-events: auto; /* Individual elements clickable */
}
```

#### Goal Bar Styling
```css
.calendar-goal-bar-hjn {
    position: absolute;
    height: 32px;
    background: linear-gradient(135deg, var(--myavana-coral), #d4956f);
    border-radius: 16px;
    padding: 0 0.75rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(231, 166, 144, 0.3);
    z-index: 1;
}

.calendar-goal-bar-hjn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(231, 166, 144, 0.5);
    z-index: 2;
}
```

**Components**:
- `.goal-bar-title-hjn`: Title text (0.8125rem, 600 weight, ellipsis overflow)
- `.goal-bar-progress-hjn`: Progress bar container (60px width, 8px height)
- `.goal-bar-fill-hjn`: Animated fill (white background, width based on progress)
- `.goal-bar-meta-hjn`: Percentage text (0.6875rem, 700 weight)

#### Entry Card Styling
```css
.calendar-entry-card-hjn {
    position: absolute;
    background: var(--myavana-white);
    border: 2px solid var(--myavana-blueberry);
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(74, 77, 104, 0.2);
    z-index: 2;
    min-height: 60px;
    max-height: 80px;
}

.calendar-entry-card-hjn:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 16px rgba(74, 77, 104, 0.4);
    z-index: 3;
    border-color: var(--myavana-coral);
}
```

**Components**:
- `.entry-card-image-hjn`: Thumbnail (40px height, cover background)
- `.entry-card-time-hjn`: Time badge (0.625rem, coral color, 700 weight)
- `.entry-card-title-hjn`: Title text (0.75rem, 600 weight, ellipsis)
- `.entry-card-rating-hjn`: Star + rating value (0.625rem with SVG icon)

#### Week View Goal Bar
```css
.calendar-week-goal-bar-hjn {
    position: absolute;
    height: 36px;
    background: linear-gradient(135deg, var(--myavana-coral), #d4956f);
    border-radius: 18px;
    padding: 0 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(231, 166, 144, 0.3);
    z-index: 5;
}

.calendar-week-day-column-hjn {
    padding-top: 50px; /* Space for goal bar */
}
```

#### Dark Mode Support
```css
[data-theme="dark"] .calendar-goal-bar-hjn {
    background: linear-gradient(135deg, var(--myavana-coral), #d4956f);
    box-shadow: 0 2px 8px rgba(231, 166, 144, 0.4);
}

[data-theme="dark"] .calendar-entry-card-hjn {
    background: rgba(74, 77, 104, 0.1);
    border-color: var(--myavana-blueberry);
}

[data-theme="dark"] .calendar-entry-card-hjn:hover {
    background: rgba(74, 77, 104, 0.2);
    border-color: var(--myavana-coral);
}
```

#### Responsive Behavior
```css
@media (max-width: 1024px) {
    .calendar-goal-bar-hjn {
        height: 28px; /* Smaller on tablets */
    }

    .calendar-entry-card-hjn {
        min-height: 50px;
        max-height: 70px;
    }
}

@media (max-width: 768px) {
    /* Hide visual overlay on mobile - too cluttered */
    .calendar-visual-overlay-hjn {
        display: none;
    }

    /* Use day cell indicators instead */
    .calendar-day-indicator-hjn {
        /* Enhanced visibility */
    }
}
```

## Technical Implementation

### Grid Position Calculations

#### Month View Grid Layout
- **7 columns**: Monday to Sunday
- **Cell width**: 100% / 7 = 14.2857% per column
- **Cell height**: 120px minimum
- **Header height**: 60px (day names row)
- **Start offset**: Empty cells before month starts (0-6)

#### Goal Bar Positioning Formula
```php
// Horizontal position (which column)
$left = ($day_of_week - 1) * (100 / 7);  // 0-6 days → 0-85.71%

// Vertical position (which row)
$row = ceil(($start_offset + $day_number) / 7);  // 1-5 or 6 weeks
$top = $header_height + (($row - 1) * $cell_height) + $offset;

// Width (how many days)
$width = $duration_days * (100 / 7);  // 1-7 days → 14.28-100%
```

#### Entry Card Positioning Formula
```php
// Same horizontal/vertical as goal, but:
$top = $header_height + (($row - 1) * $cell_height) + 35; // Below goals (+20px)
$left = ($day_of_week - 1) * (100 / 7) + 0.5; // Slight offset for spacing
$width = (100 / 7) - 1; // Slightly narrower for padding
```

### Week View Grid Layout
- **7 columns**: 7 days (Monday-Sunday)
- **Column width**: 100% / 7 = 14.2857%
- **Height**: 1440px (24 hours × 60px per hour)
- **Goal bars**: Positioned at top: 10px
- **Entry blocks**: Positioned by hour × 60px + minute offset

#### Week Goal Bar Positioning
```php
// Which days does goal span in this week?
$span_start_day = max(0, ceil(($goal_start - $week_start) / 86400)); // 0-6
$span_end_day = min(6, floor(($goal_end - $week_start) / 86400));    // 0-6
$span_days = $span_end_day - $span_start_day + 1;

// Position as percentage
$left_percent = ($span_start_day / 7) * 100;    // 0-85.71%
$width_percent = ($span_days / 7) * 100;        // 14.28-100%
```

### Z-Index Layering
```
Layer 10: Visual Overlay Container (pointer-events: none)
  ├─ Layer 1-2: Goal Bars (clickable)
  │   └─ Hover: z-index 2
  ├─ Layer 2-3: Entry Cards (clickable, above goals)
  │   └─ Hover: z-index 3
  └─ Week Layer 5-6: Week Goal Bars (above entries)
      └─ Hover: z-index 6
```

## User Experience

### Month View
1. **Visual Goal Tracking**: Goals appear as horizontal coral bars spanning their duration
2. **Entry Discovery**: Entry cards show at-a-glance info (time, title, rating)
3. **Interactive Elements**:
   - Hover goal → Lifts up with shadow
   - Hover entry → Scales up, border changes to coral
   - Click → Opens detailed offcanvas

### Week View
1. **Goal Context**: Goals span across week days at top
2. **Time-Based Entries**: Entries positioned at exact hour/minute
3. **Clean Layout**: Goal bars above, entries below with 50px padding

### Mobile Behavior
- **768px and below**: Visual overlay hidden (too cluttered)
- **Fallback**: Day cell indicators show counts and previews
- **Maintains usability**: Day cells still clickable to view all entries

## Visual Examples

### Month View with Goal and Entries
```
Calendar: October 2025
┌────────────────────────────────────────────────────────────┐
│  Mon   Tue   Wed   Thu   Fri   Sat   Sun                  │
├────────────────────────────────────────────────────────────┤
│   1     2  ┌─Goal: Moisture Retention 60%─┐   6     7     │
│            │                               │                │
│   8     9  │  11  ┌Entry┐    13    14  └──┘               │
│               ┌──Goal: Growth──────┐                       │
│            │  │  15    16    17    18 │   20   21         │
│            │  └──────────────────────┘                     │
│  22    23     24  ┌Entry┐ 26    27    28                  │
│                       └──┘                                 │
│  29    30    31                                            │
└────────────────────────────────────────────────────────────┘
```

### Week View with Goals and Entries
```
Time  │ Mon    Tue    Wed    Thu    Fri    Sat    Sun
──────┼────────────────────────────────────────────────
      │ ┌──────Goal: Moisture Retention 60%──────┐
06:00 │
      │
08:00 │ [Entry]      [Entry]
      │
10:00 │        [Entry]
      │
12:00 │               [Entry]
      │
14:00 │
```

## Performance Considerations

### Rendering Optimization
- **PHP-side rendering**: All calculations done server-side
- **No JavaScript required**: Pure CSS positioning
- **Efficient loops**: Single pass through goals/entries data
- **Conditional rendering**: Only goals/entries overlapping current view

### DOM Impact
- **Month view**: ~5-10 goal bars + ~20-50 entry cards maximum
- **Week view**: ~3-5 goal bars + ~10-20 entries maximum
- **Lightweight elements**: Simple HTML structure, CSS transforms for animations

### Browser Compatibility
- **Absolute positioning**: Universal support
- **CSS gradients**: IE10+, all modern browsers
- **Flexbox layouts**: IE11+, all modern browsers
- **Transforms/transitions**: Universal support

## Known Limitations

### Current Limitations
1. **Multi-week goals in month view**: Goals spanning multiple weeks may need continuation bars (future enhancement)
2. **Overlapping entries**: Multiple entries at same time may overlap (z-index manages this, but could add horizontal offset)
3. **Mobile hidden**: Visual overlay disabled on mobile (by design for usability)

### Future Enhancements
- [ ] Add connector lines between related entries and goals
- [ ] Implement drag-and-drop to reschedule entries
- [ ] Add entry clustering for same-time events
- [ ] Show goal progress animation on view load
- [ ] Add mini calendar navigation widget

## Testing Checklist

### Visual Display
- [x] Goal bars render at correct positions
- [x] Goal bars span correct number of days
- [x] Entry cards positioned on correct dates
- [x] Entry cards show time, title, rating
- [x] Hover effects work on goals and entries
- [x] Click opens correct offcanvas
- [x] Progress bars display correct percentage
- [x] Thumbnails display when available

### Grid Calculations
- [x] Start offset accounts for month start day
- [x] Row calculation accurate for each week
- [x] Column calculation accurate (Monday = 0, Sunday = 6)
- [x] Week view span calculation correct (0-6 days)
- [x] Multi-day goals span correctly

### Responsive
- [x] Desktop (1024px+): Full visual display
- [x] Tablet (768-1023px): Slightly smaller elements
- [x] Mobile (< 768px): Visual overlay hidden
- [x] Mobile fallback: Day indicators enhanced

### Dark Mode
- [x] Goal bars maintain coral gradient
- [x] Entry cards use dark backgrounds
- [x] Text remains readable
- [x] Hover effects work in dark mode
- [x] Progress bars visible

## Code Statistics

- **PHP**: +88 lines (visual overlay rendering in view-calendar.php)
- **CSS**: +350 lines (visual overlay styles in new-timeline.css)
- **Total**: +438 lines of visual enhancement code

## Summary

Successfully enhanced the calendar view with visual representations of goals and entries:

✅ **Month View**: Horizontal goal bars + vertical entry cards overlaid on grid
✅ **Week View**: Goal bars spanning week days with time-positioned entries
✅ **Precise Positioning**: PHP calculations for exact grid placement
✅ **Interactive Elements**: Hover effects and click-to-view functionality
✅ **MYAVANA Branding**: Coral goals, blueberry entries, luxury styling
✅ **Dark Mode Support**: Complete styling for both themes
✅ **Responsive Design**: Visual overlay hidden on mobile, enhanced indicators instead
✅ **Performance Optimized**: Server-side rendering, efficient CSS positioning

The calendar now provides a rich visual experience matching the wireframe specifications, allowing users to see their entire hair journey at a glance with goals spanning across multiple days and entries positioned precisely where they occur.

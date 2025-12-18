# Timeline Comparison Module Summary

## File Location
`/assets/js/timeline/timeline-comparison.js`

## Purpose
Manages AI hair analysis comparison functionality, allowing users to compare two analyses side-by-side with detailed metrics and insights.

## Namespace
`MyavanaTimeline.Comparison`

## Public API

### Methods

#### `init()`
Initialize the comparison module and bind event listeners.

#### `open()`
Open the comparison modal and populate analysis dropdowns.
- Scans for `.analysis-slide` elements
- Extracts date and health metrics
- Populates comparison dropdowns

#### `close()`
Close the comparison modal and reset results.

#### `generate()`
Generate comparison between two selected analyses.
- Validates selections
- Extracts analysis data
- Displays comparison results

#### `extractAnalysisData(slideElement, date)`
Extract analysis data from a slide element.
- **Parameters:**
  - `slideElement` (HTMLElement) - The slide containing analysis data
  - `date` (string) - The date of the analysis
- **Returns:** Object with health, hydration, elasticity, type, and curlPattern

#### `displayComparison(data1, data2)`
Display comparison results in the UI.
- **Parameters:**
  - `data1` (Object) - First analysis data
  - `data2` (Object) - Second analysis data

#### `calculateDiff(val1, val2)`
Calculate the difference between two metric values.
- **Parameters:**
  - `val1` (string|number) - First value
  - `val2` (string|number) - Second value
- **Returns:** Object with diff, percent, isPositive, hasData

#### `generateMetricRow(label, val1, val2, diffData)`
Generate HTML for a metric comparison row.
- **Parameters:**
  - `label` (string) - Metric label
  - `val1` (string|number) - First value
  - `val2` (string|number) - Second value
  - `diffData` (Object) - Difference calculation result
- **Returns:** HTML string

#### `generateInsight(healthDiff, hydrationDiff, elasticityDiff)`
Generate insight text based on metric differences.
- **Parameters:**
  - `healthDiff` (Object) - Health metric difference
  - `hydrationDiff` (Object) - Hydration metric difference
  - `elasticityDiff` (Object) - Elasticity metric difference
- **Returns:** Insight text string

## Backward Compatibility

The following global functions are exposed for backward compatibility:
- `window.openCompareModal()`
- `window.closeCompareModal()`
- `window.generateComparison()`
- `window.extractAnalysisData(slideElement, date)`
- `window.displayComparison(data1, data2)`
- `window.calculateDiff(val1, val2)`
- `window.generateMetricRow(label, val1, val2, diffData)`
- `window.generateInsight(healthDiff, hydrationDiff, elasticityDiff)`

## Event Bindings

### Click Events
- `#generateComparison` - Trigger comparison generation
- `#closeCompareModal` - Close comparison modal
- `#compareAnalysisModal` (backdrop) - Close modal on backdrop click

## Dependencies

### DOM Elements Required
- `#compareAnalysisModal` - Modal container
- `#compareAnalysis1` - First analysis dropdown
- `#compareAnalysis2` - Second analysis dropdown
- `#comparisonResults` - Results display container
- `.analysis-slide` - Analysis slide elements

### External Dependencies
- jQuery
- MYAVANA branding CSS variables

## Usage Example

```javascript
// Open comparison modal
MyavanaTimeline.Comparison.open();

// Or using backward compatibility
openCompareModal();

// Generate comparison programmatically
MyavanaTimeline.Comparison.generate();

// Close modal
MyavanaTimeline.Comparison.close();
```

## Integration Points

### With new-timeline.js
- Extracts data from `.analysis-slide` elements
- Uses Splide slider structure
- Shares MYAVANA branding and styling

### With Timeline State
- Independent state management
- Stores `availableAnalyses` internally
- No cross-module dependencies

## Features

### Analysis Detection
- Auto-scans Splide slider for analyses
- Extracts date and health metrics
- Creates descriptive labels for dropdowns

### Comparison Metrics
- **Health Score** - Overall hair health percentage
- **Hydration** - Hair hydration level percentage
- **Elasticity** - Hair elasticity measurement
- **Hair Type** - Curl pattern and type classification

### Insights Generation
- Calculates percentage improvements
- Provides contextual feedback
- Supports multiple improvement metrics
- Handles missing/incomplete data gracefully

### UI Features
- Side-by-side comparison layout
- Visual difference indicators (↑↓→)
- Color-coded improvements (coral/blueberry)
- Overall progress summary

## MYAVANA Branding

### Color Usage
- `--myavana-coral` - Positive improvements
- `--myavana-blueberry` - Negative changes/context
- `--myavana-onyx` - Primary text
- `--myavana-stone` - Background sections

### Typography
- **Archivo Black** - Headings
- **Archivo** - Body text and metrics
- Consistent font sizing and weights

## Error Handling

- Modal element validation
- Dropdown element validation
- Analysis selection validation
- Data extraction safety checks
- Graceful degradation for missing data

## File Statistics
- **Total Lines:** 416
- **Size:** ~16KB
- **Functions:** 8 public methods
- **Backward Compatibility Functions:** 8

## Version
**Since:** 2.3.5

---

*Generated: October 22, 2025*

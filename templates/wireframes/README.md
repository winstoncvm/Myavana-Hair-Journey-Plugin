# Myavana Hair Journey Timeline - Wireframe Prototype

**Version:** 1.0
**Last Updated:** October 14, 2025

---

## 📁 **Directory Structure**

```
wireframes/
├── index.html                    # Main modular HTML file (NEW)
├── myavana-timeline6.html       # Original monolithic file (ARCHIVED)
├── README.md                     # This file
├── assets/
│   ├── css/
│   │   └── timeline.css         # All styles (1,732 lines)
│   ├── js/
│   │   └── timeline.js          # All JavaScript functionality (279 lines)
│   ├── data/
│   │   └── sample-data.js       # Sample data for preview
│   └── images/
│       └── (placeholder for future images)
```

---

## 🚀 **Quick Start**

### **1. Open in Browser**
Simply open `index.html` in any modern web browser:

```bash
# Using default browser (macOS)
open index.html

# Using Chrome
open -a "Google Chrome" index.html

# Using Safari
open -a Safari index.html
```

### **2. Local Development Server** (Recommended)
For best results, use a local server to avoid CORS issues:

```bash
# Python 3
python3 -m http.server 8000

# Python 2
python -m SimpleHTTPServer 8000

# Node.js (if you have http-server installed)
npx http-server -p 8000

# PHP
php -S localhost:8000
```

Then open: `http://localhost:8000/`

---

## 🎨 **Features Overview**

### **Four Main Views:**
1. **Timeline** - Vertical alternating cards (default)
2. **Calendar** - Project-style grid with Day/Week/Month sub-views
3. **Slider** - Horizontal carousel using Splide.js
4. **List** - Comprehensive sortable list

### **Three Content Types:**
- **Goals** (🎯) - Progress tracking with milestones
- **Entries** (💧) - Daily hair journey logs with images
- **Routines** (☀️) - Scheduled hair care steps

### **Interactive Elements:**
- ✅ Collapsible sidebar (toggle with ‹/› button)
- ✅ Dark mode toggle (sun/moon icon)
- ✅ 7-day streak counter with flame animation
- ✅ AI insights panel
- ✅ Four stats cards
- ✅ Active goals with progress bars
- ✅ Filter by type, date, search

---

## 📊 **Sample Data**

The wireframe uses sample data from `assets/data/sample-data.js`:

### **User Data:**
- **Name:** Sarah
- **Streak:** 7 days
- **Total Entries:** 24
- **Active Goals:** 3
- **Health Score:** 8.2/10

### **Sample Goals:**
1. Length Growth (75% progress) - Target: Apr 30, 2025
2. Moisture Balance (60% progress) - Target: Mar 15, 2025
3. Color Protection (40% progress) - Target: May 20, 2025

### **Sample Entries:**
1. "Wash Day Success! 💧" - Mar 15, 2025 (9/10 rating)
2. "Morning Routine" - Mar 12, 2025 (8/10 rating)
3. "Trim Session ✂️" - Mar 10, 2025 (10/10 rating)

### **Sample Routines:**
1. Morning Moisturize (Daily @ 8:00 AM)
2. Weekly Deep Condition (Weekly @ 2:00 PM)
3. Night Protection (Daily @ 10:00 PM)

---

## 🔧 **Customization**

### **Modifying Sample Data**

Edit `assets/data/sample-data.js` to change:
- User name and stats
- Goals, entries, and routines
- AI insights
- Calendar events

**Example - Add New Entry:**
```javascript
{
    id: 4,
    type: "entry",
    date: "2025-03-16",
    time: "10:00",
    title: "Scalp Massage Day",
    description: "Gave myself a 20-minute scalp massage with oil...",
    images: ["path/to/image.jpg"],
    healthRating: 9,
    products: ["Scalp Oil", "Massage Comb"],
    mood: "Relaxed",
    tags: ["scalp-care", "self-care"],
    linkedGoals: [1, 2]
}
```

### **Modifying Styles**

Edit `assets/css/timeline.css`:
- **Brand Colors:** Lines 16-27 (`:root` variables)
- **Dark Mode:** Lines 39-48 (`[data-theme="dark"]`)
- **Typography:** Search for font-size properties
- **Layout:** Search for specific component classes

**Example - Change Primary Coral Color:**
```css
:root {
    --myavana-coral: #ff6b6b;  /* Change from #e7a690 */
}
```

### **Modifying Functionality**

Edit `assets/js/timeline.js`:
- View switching logic
- Sidebar toggle
- Dark mode persistence
- Filter and search functions

---

## 📱 **Responsive Breakpoints**

The design adapts at these breakpoints:

- **Desktop:** 1024px and above
- **Tablet:** 768px - 1023px
- **Mobile:** Below 768px

**Mobile Optimizations:**
- Sidebar becomes overlay drawer
- Calendar switches to Day view only
- Timeline uses single column
- Bottom navigation for quick actions
- Larger touch targets (44px minimum)

---

## 🎯 **Integration Checklist**

### **Phase 1: Data Layer** ⏳
- [ ] Create WordPress AJAX handlers
- [ ] Fetch real user data
- [ ] Implement CRUD operations
- [ ] Add error handling

### **Phase 2: Backend Integration** ⏳
- [ ] Connect to `myavana_hair_goals_structured`
- [ ] Connect to `hair_journey_entry` post type
- [ ] Connect to `myavana_current_routine`
- [ ] Implement image upload

### **Phase 3: Advanced Features** ⏳
- [ ] Integrate Google Gemini AI
- [ ] Add progress predictions
- [ ] Implement pattern detection
- [ ] Set up notifications

### **Phase 4: Polish** ⏳
- [ ] Performance optimization
- [ ] Accessibility audit
- [ ] Cross-browser testing
- [ ] User testing

---

## 🐛 **Known Limitations**

### **Current Wireframe:**
1. **Static Data:** Uses sample data only (no backend)
2. **No Persistence:** Changes don't save (refresh resets)
3. **Limited Modals:** Add/Edit forms are placeholders
4. **No Image Upload:** Uses SVG placeholders
5. **Calendar Grid:** Basic implementation (not fully functional)

### **Future Enhancements:**
- Real-time data updates
- Drag-and-drop timeline reordering
- Multi-image upload with FilePond
- Export timeline as PDF/image
- Social sharing integration
- Notification system

---

## 🔗 **External Dependencies**

### **CDN Resources:**
```html
<!-- Splide.js (Carousel) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@splidejs/splide@4/dist/css/splide.min.css">
<script src="https://cdn.jsdelivr.net/npm/@splidejs/splide@4/dist/js/splide.min.js"></script>
```

### **Optional (For Full Integration):**
- **Chart.js** - Progress charts and analytics
- **Day.js** - Date manipulation and formatting
- **FilePond** - Image upload with preview
- **SortableJS** - Drag-and-drop reordering

---

## 📖 **Browser Support**

### **Fully Supported:**
✅ Chrome 90+
✅ Firefox 88+
✅ Safari 14+
✅ Edge 90+

### **Partially Supported:**
⚠️ IE 11 (CSS variables not supported)

### **Required Features:**
- CSS Grid and Flexbox
- CSS Custom Properties (Variables)
- ES6 JavaScript (const, let, arrow functions)
- IntersectionObserver API (for lazy loading)

---

## 🎓 **Code Structure**

### **HTML (`index.html`)**
- Semantic HTML5 markup
- ARIA labels for accessibility
- Minimal inline styles
- Modular structure

### **CSS (`assets/css/timeline.css`)**
- BEM-inspired naming convention
- CSS Custom Properties for theming
- Mobile-first responsive design
- Smooth transitions (0.3s ease)

### **JavaScript (`assets/js/timeline.js`)**
- Vanilla JavaScript (no jQuery)
- Event delegation for performance
- localStorage for preferences
- Modular function structure

---

## 📊 **Performance Targets**

### **Current Metrics:**
- Bundle Size: ~135KB (uncompressed)
- CSS: ~50KB
- JavaScript: ~15KB
- First Paint: < 1.5s
- Interactive: < 3s

### **Optimization Opportunities:**
- Minify CSS and JS
- Compress images
- Lazy load images
- Virtual scrolling for large datasets
- Service worker for caching

---

## 🚀 **Deployment**

### **To WordPress Plugin:**

1. **Copy assets to plugin directory:**
   ```bash
   cp -r assets/ ../../assets/wireframe/
   ```

2. **Create WordPress shortcode:**
   ```php
   function myavana_timeline_wireframe_shortcode() {
       wp_enqueue_style('myavana-timeline-css',
           plugin_dir_url(__FILE__) . 'assets/wireframe/css/timeline.css'
       );
       wp_enqueue_script('myavana-timeline-js',
           plugin_dir_url(__FILE__) . 'assets/wireframe/js/timeline.js',
           ['jquery'], '1.0', true
       );

       ob_start();
       include plugin_dir_path(__FILE__) . 'templates/wireframes/index.html';
       return ob_get_clean();
   }
   add_shortcode('myavana_timeline_wireframe', 'myavana_timeline_wireframe_shortcode');
   ```

3. **Use in WordPress:**
   ```
   [myavana_timeline_wireframe]
   ```

---

## 🔐 **Security Notes**

### **For Production:**
- ⚠️ Sanitize all user inputs
- ⚠️ Validate image uploads (type, size, dimensions)
- ⚠️ Use nonces for AJAX requests
- ⚠️ Escape output to prevent XSS
- ⚠️ Implement rate limiting for API calls

### **Current Wireframe:**
✅ Safe for demo/preview (static data only)
✅ No database connections
✅ No user input processing

---

## 📞 **Support & Feedback**

For questions or issues with this wireframe:
1. Check the main plugin documentation: `/CLAUDE.md`
2. Review the detailed analysis: `/TIMELINE_WIREFRAME_ANALYSIS.md`
3. Check the WordPress admin panel for live examples

---

## 📝 **Changelog**

### **v1.0 (October 14, 2025)**
- ✅ Initial modular structure
- ✅ Extracted CSS (1,732 lines)
- ✅ Extracted JavaScript (279 lines)
- ✅ Created sample data file
- ✅ Added comprehensive documentation
- ✅ Four main views implemented
- ✅ Dark mode support
- ✅ Responsive design
- ✅ Collapsible sidebar
- ✅ AI insights panel

---

## 🎯 **Next Steps**

1. **Preview the wireframe** - Open `index.html` in browser
2. **Customize sample data** - Edit `assets/data/sample-data.js`
3. **Modify styles** - Edit `assets/css/timeline.css`
4. **Test on mobile** - Use browser DevTools device mode
5. **Begin integration** - Connect to WordPress backend

---

**Enjoy building your hair journey timeline! 💇‍♀️✨**

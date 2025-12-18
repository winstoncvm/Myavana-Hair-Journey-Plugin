# Onboarding Testing Guide - Quick Start

**Purpose:** Quick reference for testing the simplified 3-step onboarding flow

---

## 🚀 QUICK TEST (5 Minutes)

### Test User Setup:
1. Create new WordPress user or logout
2. Navigate to site homepage
3. Trigger onboarding (should auto-show for new users)

### Step-by-Step Test:

#### ✅ Step 1: Welcome & Hair Profile
**Expected Behavior:**
- [ ] See "Welcome & Hair Profile" title
- [ ] Hair type grid shows 4 options (📏 Straight, 〰️ Wavy, 🌀 Curly, 🌪️ Coily)
- [ ] Primary goal grid shows 6 options (📈 Growth, ✨ Health, 💄 Styling, 🔧 Repair, 🎨 Color, 🌊 Texture)
- [ ] "Continue" button starts DISABLED
- [ ] Selecting hair type alone doesn't enable button
- [ ] Selecting goal alone doesn't enable button
- [ ] Selecting BOTH hair type + goal ENABLES button
- [ ] Progress bar shows 33%

**Test Actions:**
```
1. Click "Wavy" → Button still disabled ❌
2. Click "Growth" → Button now enabled ✅
3. Click "Continue" → Moves to Step 2
```

---

#### ✅ Step 2: Your Preferences
**Expected Behavior:**
- [ ] See "Your Hair Preferences" title
- [ ] All fields marked "(Optional)"
- [ ] Hair length: 4 options (✂️ Short, 💁‍♀️ Medium, 👩‍🦰 Long, 🧜‍♀️ Very Long)
- [ ] Hair concerns: 6 options (💧 Dryness, ⚠️ Breakage, 🌪️ Frizz, 📉 Thinning, 💦 Oiliness, ✂️ Split Ends)
- [ ] Name input field with placeholder
- [ ] "Continue" button ENABLED (no fields required)
- [ ] "Back" button visible and works
- [ ] Progress bar shows 66%

**Test Actions:**
```
1. Click "Back" → Returns to Step 1 ✅
2. Click "Continue" → Moves forward ✅
3. Select hair length (optional) → No validation ✅
4. Select concern (optional) → No validation ✅
5. Enter name (optional) → Accepts input ✅
6. Click "Continue" → Moves to Step 3
```

---

#### ✅ Step 3: You're All Set!
**Expected Behavior:**
- [ ] See "You're All Set!" title
- [ ] Achievement badge shows "🏆 50 Points Earned!"
- [ ] Three "What's Next" cards display:
  - 📸 Create Your First Entry
  - 📊 Explore Dashboard
  - 🤖 Try AI Analysis
- [ ] "Start My Journey" button visible
- [ ] Progress bar shows 100%

**Test Actions:**
```
1. Verify badge displays correctly ✅
2. Verify cards are readable ✅
3. Click "Start My Journey" → Completes onboarding ✅
4. Should redirect to dashboard
5. Onboarding overlay should close
6. Optional: Notification shows "Welcome to MYAVANA!"
```

---

## 🐛 COMMON ISSUES & FIXES

### Issue: Button Stays Disabled on Step 1
**Debug:**
```javascript
// Open browser console (F12)
// Check logged values:
console.log(onboardingData.hair_type);  // Should show selected type
console.log(onboardingData.primary_goal);  // Should show selected goal
```

**Fix:** Clear browser cache and reload

---

### Issue: Data Not Saving
**Debug:**
```javascript
// Check Network tab (F12 → Network)
// Look for AJAX call to 'admin-ajax.php'
// Check request payload for:
{
  action: 'myavana_onboarding_step',
  step: 'welcome',
  data: { hair_type: '...', primary_goal: '...' }
}
```

**Fix:** Check PHP error logs, verify nonce

---

### Issue: Progress Bar Not Updating
**Debug:**
```javascript
// Console should show:
"Showing step: welcome Index: 0"
"Showing step: preferences Index: 1"
"Showing step: complete Index: 2"
```

**Fix:** Verify JavaScript loaded correctly

---

## 📱 MOBILE TEST (Optional)

### iPhone Safari:
1. Open site in iPhone Safari
2. Tap through onboarding
3. Verify all buttons easily tappable
4. No horizontal scroll
5. Keyboard doesn't hide buttons

### Android Chrome:
1. Open site in Android Chrome
2. Same checks as iPhone
3. Verify responsive grids adjust

---

## 🎯 VERIFICATION CHECKLIST

### After Completing Onboarding:
- [ ] User redirected to dashboard
- [ ] Onboarding overlay closed
- [ ] Check user meta in WordPress admin:
  - `myavana_hair_type` = selected value
  - `myavana_primary_goal` = selected value
  - `myavana_hair_length` = selected value (if chosen)
  - `myavana_hair_concern` = selected value (if chosen)
  - `myavana_onboarding_status` = 'completed'
  - `myavana_points` = 50 (or increased by 50)

---

## 🚨 CRITICAL PATHS

### Path 1: Minimal Completion (Required Fields Only)
```
1. Select hair type: "Curly"
2. Select goal: "Health"
3. Click "Continue"
4. Skip all optional fields
5. Click "Continue"
6. Click "Start My Journey"
✅ Result: Onboarding complete with minimal data
```

### Path 2: Full Completion (All Fields)
```
1. Select hair type: "Wavy"
2. Select goal: "Growth"
3. Click "Continue"
4. Select length: "Long"
5. Select concern: "Dryness"
6. Enter name: "Sarah"
7. Click "Continue"
8. Click "Start My Journey"
✅ Result: Onboarding complete with full profile
```

### Path 3: Skip Onboarding
```
1. Click "Skip Tour" (top right)
2. Confirm dialog
✅ Result: Onboarding closed, user lands on page
```

---

## 🔍 BROWSER CONSOLE CHECKS

### Expected Console Logs:
```javascript
// On page load:
"MYAVANA: Onboarding nonce set: [nonce_value]"
"MYAVANA: Onboarding overlay template loaded"

// On Step 1 selections:
"Hair type selected: wavy Can proceed: false"
"Primary goal selected: growth Can proceed: true"

// On step transitions:
"Showing step: preferences Index: 1"
"Progress saved: {success: true, ...}"
```

### No Errors Should Appear:
- ❌ "Uncaught ReferenceError: $ is not defined"
- ❌ "Failed to save progress"
- ❌ "Nonce verification failed"

---

## ⚡ PERFORMANCE CHECK

### Load Time:
- [ ] Overlay appears within 1 second
- [ ] No flash of unstyled content
- [ ] Smooth animations

### Responsiveness:
- [ ] Button clicks respond immediately
- [ ] Selection states update instantly
- [ ] No lag on step transitions

---

## 📊 DATA VALIDATION

### Check Backend (WordPress Admin):
1. Go to Users → All Users
2. Click on test user
3. Scroll to "Custom User Meta" or similar
4. Verify values saved:

```
myavana_hair_type: "curly"
myavana_primary_goal: "growth"
myavana_hair_length: "medium"
myavana_hair_concern: "dryness"
myavana_onboarding_status: "completed"
myavana_points: 50
```

---

## 🎓 TEST REPORT TEMPLATE

Copy and fill this out after testing:

```markdown
## Onboarding Test Report
**Date:** [Date]
**Browser:** [Chrome/Safari/Firefox]
**Device:** [Desktop/Mobile]
**User:** [Test user email]

### Results:
- Step 1 (Welcome): ✅ / ❌
- Step 2 (Preferences): ✅ / ❌
- Step 3 (Complete): ✅ / ❌
- Data saved correctly: ✅ / ❌
- Mobile responsive: ✅ / ❌
- Points awarded: ✅ / ❌

### Issues Found:
1. [Issue description]
2. [Issue description]

### Notes:
[Any additional observations]
```

---

## 🚀 READY TO TEST?

**Quick Start:**
1. Logout or create new user
2. Navigate to site
3. Follow Step 1 → Step 2 → Step 3
4. Verify completion
5. Check WordPress user meta

**Time Required:** 5 minutes per test run

**Recommended Tests:** 3 runs (minimal data, full data, skip flow)

---

**Last Updated:** October 14, 2025
**Version:** 2.3.7

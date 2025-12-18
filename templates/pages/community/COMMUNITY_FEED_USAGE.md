# Community Feed - Quick Start Guide

## What We Built

A complete Instagram-style community feed where MYAVANA users can share their hair journey progress, discover inspiration, and engage with others.

---

## How to Use

### 1. Create a Community Feed Page

**In WordPress Admin:**
1. Go to **Pages → Add New**
2. Title: "Community" or "Hair Journey Inspiration"
3. Add the shortcode:
   ```
   [myavana_community_feed]
   ```
4. Publish the page

**That's it!** The feed will automatically load with all styling and functionality.

---

### 2. Shortcode Options

```php
// Basic usage
[myavana_community_feed]

// With custom settings
[myavana_community_feed
    filter="trending"              // Show trending posts by default
    per_page="15"                  // Load 15 posts per page
    show_filters="true"            // Show filter tabs
    show_create_post="true"]       // Show "Share Your Journey" button
```

**Available Filters:**
- `all` - All public posts (default)
- `following` - Posts from people you follow
- `trending` - Most engaging posts (last 7 days)
- `featured` - Curated featured posts

---

### 3. Add Share Button to Hair Journey Entries

To allow users to share their existing hair journey entries to the community, add this code to your entry template:

```php
<?php
// In your entry view/detail template
if (function_exists('myavana_render_share_button')) {
    myavana_render_share_button($entry_id);
}
?>
```

**Example - Add to entry modal:**
```php
<div class="entry-actions">
    <button class="myavana-btn-secondary" onclick="editEntry(<?php echo $entry_id; ?>)">
        Edit Entry
    </button>

    <?php myavana_render_share_button($entry_id); ?>
</div>
```

---

## Features Included

### ✅ Feed Functionality
- **Instagram-style card layout** - Beautiful, responsive grid
- **Filter tabs** - All, Following, Trending, Featured
- **Infinite scroll** - Automatically loads more posts
- **Like posts** - Heart animation on like
- **Comment** - Comment system (placeholder for now)
- **Share** - Share to other platforms (placeholder for now)

### ✅ Create Posts
- **Modal form** - Clean, MYAVANA-branded creation modal
- **Photo upload** - Drag-and-drop or click to upload
- **Post types** - Progress, Transformation, Routine, Products, Tips
- **Privacy controls** - Public or Followers-only

### ✅ Share from Hair Journey
- **One-click sharing** - Share existing entries to community
- **Auto-populated** - Uses entry title, content, and photos
- **Smart metadata** - Includes health rating and mood
- **Confetti celebration** - Fun animation when shared
- **Prevents duplicates** - Can't share same entry twice

### ✅ MYAVANA Brand Styling
- **Approved color palette** - Coral, Onyx, Blueberry, Stone
- **Archivo typography** - Brand fonts throughout
- **Mobile-first** - Touch-friendly, responsive design
- **Accessibility** - Keyboard navigation, screen reader support

---

## User Flow

### Creating a Post

1. User clicks **"Share Your Journey"** button
2. Modal opens with creation form
3. User enters:
   - Title (e.g., "6 months of growth! 🌱")
   - Story/description
   - Optional photo (drag & drop or upload)
   - Post type (Progress, Transformation, etc.)
   - Privacy (Public or Followers-only)
4. User clicks **"Share Post"**
5. Post appears in community feed immediately
6. Success notification shows

### Sharing an Entry

1. User views their hair journey entry (in timeline/calendar view)
2. User clicks **"Share to Community"** button
3. Button shows loading spinner
4. Confetti animation plays on success
5. Button changes to **"Shared to Community"** (disabled)
6. Entry now visible in community feed

### Engaging with Posts

1. **Like:** Click heart icon (animates and fills with coral color)
2. **Comment:** Click comment icon (opens comment section - coming soon)
3. **Share:** Click share icon (share options - coming soon)

---

## Database Tables Used

The plugin automatically creates these tables on activation:

- `wp_myavana_community_posts` - Stores all posts
- `wp_myavana_post_likes` - Tracks likes
- `wp_myavana_post_comments` - Stores comments
- `wp_myavana_user_followers` - Follow relationships
- `wp_myavana_community_challenges` - Community challenges

**Note:** These tables are created by the `Myavana_Social_Features` class on `init`.

---

## AJAX Endpoints

All backend logic is handled via WordPress AJAX:

### Feed Operations:
- `get_community_feed` - Loads posts (with filters)
- `create_community_post` - Creates new post
- `like_post` - Toggles like on/off
- `comment_on_post` - Adds comment
- `follow_user` - Toggles follow relationship

### Share Operation:
- `share_entry_to_community` - Shares hair journey entry to feed

**Security:** All endpoints require valid nonce verification.

---

## Styling & Customization

All styles are in: `/assets/css/social-feed.css`

### Customizing Colors:
```css
/* Override in your theme's custom CSS */
:root {
    --myavana-coral: #YOUR_COLOR;
    --myavana-blueberry: #YOUR_COLOR;
}
```

**Warning:** Only use MYAVANA approved brand colors!

### Customizing Layout:
```css
/* Change grid columns */
.myavana-feed-grid {
    grid-template-columns: repeat(4, 1fr); /* Default is 3 on desktop */
}
```

---

## Performance Optimization

### Caching Strategy:
- Feed data cached for 5 minutes (WordPress transients)
- User data cached via `Myavana_Data_Manager`
- Images lazy-loaded with `loading="lazy"`
- Infinite scroll with pagination

### Clear Cache:
```php
// Clear user's cache after data changes
Myavana_Data_Manager::clear_user_cache($user_id);
```

---

## Mobile Experience

### Optimizations:
- ✅ Touch targets minimum 44px
- ✅ Swipe-friendly filters (horizontal scroll)
- ✅ Optimized images for mobile data
- ✅ Modal takes full screen on mobile
- ✅ One-column grid on mobile

---

## Next Steps & Enhancements

### Phase 1 Complete ✅
- [x] Community feed page
- [x] Create post functionality
- [x] Like posts
- [x] Share from hair journey entries
- [x] MYAVANA brand styling
- [x] Mobile responsive

### Phase 2 (Future)
- [ ] Full comment system with replies
- [ ] User profiles (click on avatar)
- [ ] Follow/unfollow from feed
- [ ] Real-time notifications
- [ ] Share to social media
- [ ] Search and hashtags
- [ ] Community challenges
- [ ] Moderation tools

See `SOCIAL_FEATURES_IMPLEMENTATION.md` for complete roadmap.

---

## Troubleshooting

### Issue: "Feed not loading"
**Check:**
1. Is user logged in? (Feed requires authentication)
2. Are JavaScript files loaded? (Check browser console)
3. Check AJAX URL: `console.log(window.myavanaCommunitySettings)`

### Issue: "Share button not working"
**Check:**
1. Is `share-to-community.js` enqueued?
2. Is entry ID passed correctly?
3. Check browser console for errors

### Issue: "Styles not applying"
**Check:**
1. Is `social-feed.css` enqueued?
2. Clear browser cache
3. Check for CSS conflicts with theme

### Issue: "Posts not appearing"
**Check:**
1. Are there posts in database? (Check `wp_myavana_community_posts`)
2. Is privacy level set to `public`?
3. Clear WordPress object cache

---

## Testing Checklist

Before going live:

- [ ] Create a test post with photo
- [ ] Create a test post without photo
- [ ] Like and unlike a post
- [ ] Share hair journey entry to community
- [ ] Test on iPhone (Safari)
- [ ] Test on Android (Chrome)
- [ ] Test on tablet (iPad)
- [ ] Test desktop (Chrome, Firefox)
- [ ] Test with keyboard only (accessibility)
- [ ] Test with screen reader (NVDA/JAWS)
- [ ] Test filters (All, Following, Trending)
- [ ] Test load more / infinite scroll
- [ ] Test with slow network (throttle in DevTools)

---

## Support & Feedback

**Report Issues:**
- Document the issue clearly
- Include browser & device info
- Provide screenshots if possible
- Check browser console for errors

**Feature Requests:**
- Describe the use case
- Explain expected behavior
- Note priority level

---

## Code References

**Main Files:**
- Template: `templates/pages/community/community-feed.php`
- CSS: `assets/css/social-feed.css`
- JavaScript: `assets/js/social-feed.js`
- Share Integration: `includes/share-to-community.php`
- Share JS: `assets/js/share-to-community.js`
- Backend: `includes/social-features.php`

**Helper Functions:**
```php
// Render share button
myavana_render_share_button($entry_id);

// Check if entry is shared
Myavana_Share_To_Community::is_entry_shared($entry_id);

// Get community post ID for shared entry
Myavana_Share_To_Community::get_community_post_id($entry_id);

// Clear user cache
Myavana_Data_Manager::clear_user_cache($user_id);
```

---

## Demo Content

To seed the community with sample posts for testing:

```php
// Add to functions.php temporarily
add_action('init', function() {
    if (isset($_GET['seed_community'])) {
        // Create sample posts
        global $wpdb;
        $table = $wpdb->prefix . 'myavana_community_posts';

        $sample_posts = [
            [
                'user_id' => 1,
                'title' => '3 Months of Growth Progress! 🌱',
                'content' => 'I can\'t believe the difference! Sticking to my routine really pays off.',
                'post_type' => 'progress',
                'privacy_level' => 'public'
            ],
            // Add more sample posts...
        ];

        foreach ($sample_posts as $post) {
            $wpdb->insert($table, $post);
        }

        wp_redirect(home_url('/community'));
        exit;
    }
});
```

Then visit: `yoursite.com/?seed_community=1`

---

## Congratulations! 🎉

You've successfully implemented the MYAVANA Community Feed!

**What you built:**
- Complete Instagram-style social feed
- MYAVANA-branded UI components
- Share functionality from hair journey
- Mobile-responsive design
- Secure AJAX operations
- Performance-optimized code

**Time to launch:** Create a page, add `[myavana_community_feed]`, publish! ✨

---

**Last Updated:** 2025-01-05
**Version:** 1.0.0
**Next Phase:** See `SOCIAL_FEATURES_IMPLEMENTATION.md`

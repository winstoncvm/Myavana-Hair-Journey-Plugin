# MYAVANA Hair Journey Technical Documentation

## 1. Overview
MYAVANA Hair Journey is a WordPress plugin-driven application that turns a WordPress site into a protected, app-like experience. The plugin owns most of the frontend application layer, including:
- page rendering through shortcodes
- custom auth and onboarding
- protected route behavior
- global navigation
- Hair Journey data capture and display
- Goals and Routines management
- Community/social functionality
- gamification
- AI-connected flows
- site analytics and admin intelligence dashboards

Plugin root:
`/Users/winstonzulu/Local Sites/myavana-hair-journey/app/public/wp-content/plugins/myavana-hair-journey-updated`

## 2. Entry Points

### Main plugin bootstrap
File:
`/Users/winstonzulu/Local Sites/myavana-hair-journey/app/public/wp-content/plugins/myavana-hair-journey-updated/myavana-hair-journey.php`

Responsibilities:
- plugin bootstrap and dependency loading
- shortcode registration bootstrapping
- asset enqueueing
- page detection
- protected route handling
- global navbar rendering
- mobile bottom tabs rendering
- plugin-level integration between page shortcodes and application assets

### Shortcode registration
File:
`/Users/winstonzulu/Local Sites/myavana-hair-journey/app/public/wp-content/plugins/myavana-hair-journey-updated/includes/shortcodes.php`

Primary shortcode mappings include:
- `myavana_luxury_home`
- `myavana_hair-journey-page`
- `myavana_unified_profile`
- `myavana_community_feed`
- `myavana_goals_page`
- `myavana_routines_page`

## 3. Page Architecture

### Home
Primary render file:
`/Users/winstonzulu/Local Sites/myavana-hair-journey/app/public/wp-content/plugins/myavana-hair-journey-updated/templates/pages/home/luxury-home.php`

### Hair Journey / My Timeline
Primary render file:
`/Users/winstonzulu/Local Sites/myavana-hair-journey/app/public/wp-content/plugins/myavana-hair-journey-updated/templates/pages/hair-journey.php`

Supporting partials:
- `templates/pages/partials/header-and-sidebar.php`
- `templates/pages/partials/timeline-area.php`
- `templates/pages/partials/view-timeline.php`
- `templates/pages/partials/view-calendar.php`
- `templates/pages/partials/view-list.php`
- `templates/pages/partials/view-offcanvas.php`
- `templates/pages/partials/create-offcanvas.php`

### Profile
Primary render file:
`/Users/winstonzulu/Local Sites/myavana-hair-journey/app/public/wp-content/plugins/myavana-hair-journey-updated/templates/pages/unified-profile.php`

### Community
Primary render files:
- `templates/pages/community/community-feed.php`
- `templates/pages/community/user-profile.php`
- `templates/pages/community/community-shortcodes.php`

### Goals and Routines
Primary render files:
- `templates/pages/goals.php`
- `templates/pages/routines.php`

## 4. Navigation System

### Global desktop navbar
Files:
- `templates/pages/partials/global-navbar.php`
- `assets/css/global-navbar.css`
- `assets/js/global-navbar.js`

### Mobile app-style header and bottom tabs
Files:
- `assets/css/mobile-global-tabs.css`
- `assets/js/mobile-global-tabs.js`

Main plugin responsibilities:
- resolve app page URLs by shortcode
- render page-aware navigation state
- suppress redundant or theme-native app navigation where needed

## 5. Authentication and Onboarding

### Auth system
Core file:
`/Users/winstonzulu/Local Sites/myavana-hair-journey/app/public/wp-content/plugins/myavana-hair-journey-updated/includes/myavana-auth-system.php`

Frontend template files:
- `templates/auth/auth-modal.php`
- `templates/auth/onboarding-modal.php`

Frontend JS:
- `assets/js/myavana-auth-system.js`

Responsibilities:
- login/signup
- modal rendering via `wp_footer`
- redirect-safe auth flow
- protected route support
- onboarding triggers
- password reset shortcode support
- Google Auth integration when configured

### Onboarding persistence layer
Files:
- `includes/onboarding-db-schema.php`
- `actions/onboarding-handlers.php`
- `assets/js/myavana-onboarding.js`

There are two onboarding-related layers in the codebase:
1. the auth/onboarding modal used as part of the main app auth flow
2. the multi-step onboarding page flow and AJAX handlers used for structured onboarding screens

Current operational behavior should be treated as plugin-layer onboarding plus optional dedicated onboarding pages depending on the entry flow.

## 6. Protected Route Model
Protected route logic is centralized in the main plugin bootstrap.

Behavior:
- detects protected app pages via shortcode and URL fallback
- redirects logged-out users into home/auth instead of leaving blank screens
- preserves `redirect_to` behavior for return after auth

Relevant file:
`/Users/winstonzulu/Local Sites/myavana-hair-journey/app/public/wp-content/plugins/myavana-hair-journey-updated/myavana-hair-journey.php`

## 7. Hair Journey System

### Core render and data flow
Hair Journey is rendered by:
- `templates/pages/hair-journey.php`

Shared user state is aggregated by:
- `includes/class-myavana-data-manager.php`

The data manager centralizes:
- user profile
- user meta
- entries
- analytics summary
- gamification summary
- analysis limit info

### Entry system
Primary entry logic lives in:
- `actions/hair-entries.php`
- `templates/hair-diary-timeline-shortcode.php`
- `assets/js/timeline/timeline-forms-new.js`
- `assets/js/timeline/timeline-view.js`
- `assets/js/timeline/timeline-offcanvas.js`

Capabilities include:
- create/update entries
- image and video support
- camera capture support in frontend flows where supported
- AI-linked entry creation
- view/edit offcanvas behavior
- timeline/calendar/list rendering

### Goals and routines logic
Primary handler file:
- `actions/goal-routine-handlers.php`

Page render files:
- `templates/pages/goals.php`
- `templates/pages/routines.php`

Cross-page interaction:
- view/edit functionality is exposed globally through shared offcanvas systems
- goals and routines appear across Hair Journey and Profile surfaces

## 8. Community / Social System

### Core community feature layer
Files:
- `includes/social-features.php`
- `includes/community-integration.php`
- `actions/myavana-ci-handlers.php`
- `actions/community-entry-sharing-handlers.php`
- `templates/pages/community/community-feed.php`
- `assets/js/social-feed.js`
- `assets/css/social-feed.css`

Capabilities include:
- community posts
- likes
- comments
- follows
- bookmarks
- reactions
- content reports
- bookmark collections
- drafts
- challenges
- shared entries and shared routines
- discovery data such as trending hashtags and suggested creators

## 9. Gamification System

Core file:
- `includes/gamification.php`

Supporting files:
- `actions/gamification-handlers.php`
- `actions/insight-handlers.php`
- `includes/myavana-gamification-admin.php`
- `assets/js/gamification.js`
- `assets/css/gamification.css`

Responsibilities:
- daily check-ins
- points and levels
- streaks
- badges
- quests
- challenge definitions and progress
- reward ledger / points history
- frontend summary payload for Hair Journey and Profile
- admin gamification intelligence surfaces

Operational note:
- recent stabilization work added runtime schema repair and stopped recursive stat calls when legacy schemas are missing expected columns

## 10. Analytics and Intelligence Dashboard

### Frontend/site analytics
Core files:
- `includes/class-myavana-analytics-model.php`
- `includes/myavana_database_setup.php`
- `assets/js/site-intelligence-tracker.js`

Responsibilities:
- page/path/session/device/time-on-page analytics capture
- overview metrics
- funnel drop-off metrics
- cohort retention metrics
- page performance and section usage metrics
- visitor and signup-related intelligence

### Admin intelligence dashboard
Core files:
- `includes/myavana_admin_settings.php`
- `includes/class-myavana-admin-controller.php`
- `actions/dashboard-ajax-handlers.php`

Admin dashboard sections include:
- Overview
- Executive
- Growth
- Community
- Retention
- AI Lab
- Operations
- Gamification
- Settings

Admin routing behavior:
- default WordPress dashboard redirects admins to the custom Myavana intelligence dashboard

## 11. AI-Connected Systems
Relevant files:
- `includes/ai-integration.php`
- `includes/ai-recommendations.php`
- `actions/smart-entry-handlers.php`
- `assets/js/smart-entry.js`
- `assets/js/ai-analysis-modal.js`
- `assets/js/free-hair-analysis.js`

Capabilities include:
- AI analysis-connected entry creation
- recommendations and suggestion layers
- smart-entry media-to-analysis workflow
- admin AI metrics and reporting

Product direction note:
- specific AI buttons may be configured to route users to MYAVANA’s official external consumer AI experience

## 12. Core Data Model and Custom Tables

### Base site/app tables
Created or managed by:
- `includes/myavana_database_setup.php`

Tables include:
- `wp_myavana_profiles`
- `wp_myavana_conversations`
- `wp_myavana_site_analytics`

### Gamification tables
Created or managed by:
- `includes/gamification.php`

Tables include:
- `wp_myavana_daily_checkins`
- `wp_myavana_user_stats`
- `wp_myavana_badges`
- `wp_myavana_user_badges`
- `wp_myavana_points_history`
- `wp_myavana_insights`
- `wp_myavana_user_insights`
- `wp_myavana_ai_tips`

### Community tables
Created or managed by:
- `includes/social-features.php`
- `includes/myavana-ci-database.php`

Tables include:
- `wp_myavana_community_posts`
- `wp_myavana_post_likes`
- `wp_myavana_post_comments`
- `wp_myavana_user_followers`
- `wp_myavana_community_challenges`
- `wp_myavana_challenge_participants`
- `wp_myavana_shared_entries`
- `wp_myavana_shared_routines`
- `wp_myavana_routine_bookmarks`
- `wp_myavana_post_bookmarks`
- `wp_myavana_notifications`
- `wp_myavana_ci_post_reactions`
- `wp_myavana_ci_comment_likes`
- `wp_myavana_ci_content_reports`
- `wp_myavana_ci_bookmark_collections`
- `wp_myavana_ci_collection_items`
- `wp_myavana_ci_post_drafts`

### Photo comparison / measurement tables
Created or managed by:
- `includes/photo-comparison.php`

Tables include:
- `wp_myavana_hair_photos`
- `wp_myavana_photo_comparisons`
- `wp_myavana_photo_measurements`
- `wp_myavana_photo_analysis`

## 13. AJAX and Interaction Layer
The application relies heavily on WordPress AJAX for app behavior.

Primary handler files:
- `actions/hair-entries.php`
- `actions/goal-routine-handlers.php`
- `actions/gamification-handlers.php`
- `actions/insight-handlers.php`
- `actions/onboarding-handlers.php`
- `actions/smart-entry-handlers.php`
- `actions/myavana-ci-handlers.php`
- `actions/dashboard-ajax-handlers.php`
- `includes/myavana_ajax_handlers.php`

Common responsibilities:
- CRUD for entries, goals, and routines
- community actions
- onboarding and auth triggers
- analytics and dashboard data fetches
- view/edit offcanvas data hydration
- smart-entry and AI flows

## 14. Asset Strategy
Key asset coordination files:
- `includes/asset-optimizer.php`
- `myavana-hair-journey.php`

Asset groups include:
- app navigation assets
- Hair Journey page assets
- Community page assets
- Profile page assets
- auth and onboarding assets
- gamification assets
- dashboard assets

The plugin uses targeted enqueueing and page detection to reduce unnecessary load.

## 15. Mobile Strategy
The mobile app strategy is intentionally different from desktop.

Mobile-specific patterns:
- simplified app header
- bottom tab navigation
- responsive cards and glass surfaces
- mobile-first offcanvas patterns
- modal and create-flow optimization
- extra spacing/padding to prevent overlap with persistent mobile UI and chatbot surfaces

## 16. Security and Validation Patterns
Patterns present in the codebase include:
- WordPress nonce verification for AJAX actions
- capability checks for admin-only features
- auth checks for protected actions
- sanitization of user input before persistence
- same-origin redirect safety in auth flow

Operational note:
- because this plugin owns major user flows, every new feature should preserve nonce validation, auth checks, and capability separation between user-facing and admin-facing behavior

## 17. Production Readiness Considerations
Before production release or production-day changes, verify:
- protected-route redirects for logged-out users
- auth modal behavior and return redirects
- onboarding completion path
- first-entry flow after onboarding
- create/view/edit offcanvas behavior for entries, goals, and routines
- mobile header and bottom tabs
- community media post creation and editing
- gamification summary loading
- admin dashboard metrics and charts
- analytics table write behavior
- Google Auth origin configuration

## 18. Recommended Developer Orientation Order
For a new engineer joining the project, the best reading order is:
1. `myavana-hair-journey.php`
2. `includes/shortcodes.php`
3. `includes/class-myavana-data-manager.php`
4. `templates/pages/hair-journey.php`
5. `templates/pages/unified-profile.php`
6. `templates/pages/community/community-feed.php`
7. `includes/myavana-auth-system.php`
8. `includes/gamification.php`
9. `includes/social-features.php`
10. `includes/myavana_admin_settings.php`
11. `includes/class-myavana-analytics-model.php`

## 19. Current Architectural Reality
This is not a thin shortcode plugin. It is effectively the application runtime for the site.

That means:
- UI, business rules, analytics, community, onboarding, and admin intelligence are tightly coupled here
- any production change should be evaluated as an app change, not a content tweak
- regression testing should always cover cross-page behaviors because shared systems are reused across the app

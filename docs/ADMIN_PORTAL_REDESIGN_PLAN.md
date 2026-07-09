# MYAVANA Admin Portal Redesign Plan

## Objective

Redesign the MYAVANA admin experience as a dedicated operations portal that is separate from the default WordPress dashboard.

WordPress should remain the system backend for:

- authentication and session management
- permissions and role checks
- database access and model execution
- plugin configuration and infrastructure-level controls

The day-to-day staff experience should move to a dedicated MYAVANA portal UI at a protected frontend route, rather than continuing inside `wp-admin`.

## Why This Change

The current admin experience is operationally useful, but it is still shaped like a WordPress dashboard:

- the active admin surface is rendered inside `wp-admin`
- the top-level WP dashboard is redirected to the MYAVANA dashboard
- screens are PHP-rendered and tightly coupled to WordPress admin page structure
- the intelligence, AI, analytics, and support workflows are spread across menus instead of living in a cohesive product operations console

This creates product constraints:

- limited UX flexibility
- difficult screen-to-screen workflows
- weak support for deeper operational tools like moderation queues, user drill-downs, audit trails, and role-specific workspaces
- MYAVANA staff are still forced into WordPress-first mental models

The portal redesign should solve that by treating admin as a product surface, not a plugin settings page.

## Current Baseline

### Active admin surface

The active implementation is in:

- `includes/myavana_admin_settings.php`

Current behavior:

- registers the `Myavana` top-level admin menu and multiple submenu pages
- redirects `index.php` in `wp-admin` to the MYAVANA intelligence dashboard for admins
- renders the dashboard in PHP
- uses WordPress settings APIs for system keys such as `myavana_xai_api_key`, `myavana_openai_api_key`, and `myavana_gemini_api_key`
- currently organizes reporting around tabs such as Overview, Executive, Growth, Community, Retention, AI Lab, Operations, and Gamification

### Existing admin-app direction

There is already a partial foundation for an app-driven admin surface in:

- `includes/class-myavana-admin-controller.php`

Current behavior:

- REST routes are registered under `myavana/v1`
- menu registration and asset enqueueing for the React admin shell are present but commented out
- existing endpoints include overview analytics, funnel analytics, and retention analytics

This is the best migration starting point. The new portal should extend this controller pattern rather than adding another disconnected admin framework.

## Recommended Architecture

### Portal route

Create a dedicated protected route:

- `/admin-portal/`

This route should be generated and managed by the plugin in the same way the app pages are managed, but it should be visible only to authorized staff users.

### Core model

Use WordPress as the backend platform, but move the UI into a dedicated portal application.

Recommended stack:

- frontend shell: React app mounted on `/admin-portal/`
- API layer: WordPress REST API
- auth: existing WordPress session and cookies
- permissions: WordPress capabilities plus MYAVANA-specific roles
- data access: existing plugin models and custom tables
- audit: explicit logging for all admin mutations

### Non-goals

Do not:

- create a second login system
- build an admin app that bypasses WordPress capabilities
- move infrastructure-level plugin management entirely out of WordPress on day one
- duplicate analytics, AI, or user data into a parallel backend unless strictly necessary

## Product Principles

The new portal should behave like an operations console:

- fast to scan
- role-aware
- drill-down friendly
- optimized for desktop and tablet first
- still usable on smaller screens, but not mobile-primary
- built for repeated staff workflows, not occasional plugin setup

Design principles:

- persistent left navigation
- global search and command bar
- cross-page filters
- saved views
- detail drawers for drill-downs
- high-density tables with clean hierarchy
- action panels for support and moderation tasks
- clear system status and environment indicators

## Portal Information Architecture

### 1. Overview

Purpose:

- executive and operational snapshot of the platform

Core modules:

- active users
- signups
- onboarding completion
- journey activity
- community engagement
- AI suggestion usage
- moderation queue count
- support issue count
- system status

### 2. Users

Purpose:

- inspect and manage member accounts

Core modules:

- user search and filtering
- profile summary
- onboarding status
- last activity
- goals and routines snapshot
- streaks and gamification state
- community activity
- AI interaction history

Admin actions:

- resend password reset
- trigger onboarding reset
- suspend or reactivate
- inspect event history
- inspect auth issues

### 3. Onboarding

Purpose:

- optimize conversion through the onboarding journey

Core modules:

- completion funnel
- step-by-step drop-off
- time-to-complete
- segmentation by signup source
- onboarding reset tools
- onboarding content and reward configuration

### 4. Hair Journey Activity

Purpose:

- monitor the core product behavior around entries and analysis

Core modules:

- entry creation trends
- photo upload volume
- AI analysis usage
- failed uploads or failed analysis
- user timeline health
- high-value users with stalled activity

### 5. Goals and Routines

Purpose:

- monitor and improve adherence features

Core modules:

- goal adoption
- routine adoption
- completion rates
- popular categories
- abandoned setups
- recommended template performance

Admin actions:

- manage global templates
- inspect goal and routine creation friction
- publish featured routines or goal packs

### 6. Community and Moderation

Purpose:

- manage safety, participation, and community quality

Core modules:

- posts and comments volume
- follows and bookmarks
- reports queue
- flagged content
- creator leaderboards
- engagement health

Admin actions:

- approve or hide content
- resolve reports
- inspect user interaction history

### 7. AI Intelligence

Purpose:

- monitor and control the site intelligence system

Core modules:

- generated suggestions
- saves, dismissals, and applies
- component performance by page
- model usage
- latency and fallback rate
- routine suggestion performance
- insight quality signals

Admin actions:

- update provider keys
- select model routing strategy
- tune feature flags
- manage component templates
- inspect prompt outcomes

### 8. Gamification

Purpose:

- manage motivation systems and challenge performance

Core modules:

- points and rewards volume
- streak distribution
- challenge adoption
- milestone completion
- engagement lift by reward type

Admin actions:

- edit challenge definitions
- tune reward thresholds
- inspect incentive effectiveness

### 9. Analytics and Funnels

Purpose:

- provide product and growth intelligence

Core modules:

- traffic trends
- signup funnel
- onboarding funnel
- retention and cohorts
- page and section engagement
- dwell-time insights
- conversion flows

### 10. Support and Account Operations

Purpose:

- resolve account and access issues quickly

Core modules:

- password reset tools
- auth failure logs
- account lockouts
- reset flow diagnostics
- onboarding access issues
- user-reported issue timelines

### 11. Content and Templates

Purpose:

- manage reusable product content

Core modules:

- home dashboard cards
- AI component templates
- onboarding copy blocks
- notifications
- featured goals
- featured routines

### 12. System Settings

Purpose:

- control configuration without exposing staff to raw WordPress settings pages

Core modules:

- API keys
- feature flags
- tracking toggles
- AI model configuration
- caching and diagnostics
- release notes and version markers

### 13. Audit Log

Purpose:

- maintain accountability across staff actions

Core modules:

- admin mutations
- settings changes
- moderation actions
- account state changes
- AI configuration changes
- export and filtering

## Proposed Role Model

The portal should introduce product-specific capabilities instead of overloading `manage_options` for every staff use case.

Recommended roles:

- `myavana_super_admin`
- `myavana_ops_admin`
- `myavana_support_admin`
- `myavana_content_admin`
- `myavana_analytics_viewer`

Recommended access model:

- `myavana_super_admin`: full access including settings, AI configuration, role management, and audit logs
- `myavana_ops_admin`: overview, users, onboarding, support, community, gamification, analytics
- `myavana_support_admin`: users, support, onboarding troubleshooting, limited account actions
- `myavana_content_admin`: community moderation, templates, selected AI content controls
- `myavana_analytics_viewer`: read-only access to dashboards and exports

This role model should be implemented with custom capabilities so every screen and API endpoint can be permissioned precisely.

## Route Structure

Recommended portal routes:

- `/admin-portal/`
- `/admin-portal/overview`
- `/admin-portal/users`
- `/admin-portal/users/:id`
- `/admin-portal/onboarding`
- `/admin-portal/journey`
- `/admin-portal/goals-routines`
- `/admin-portal/community`
- `/admin-portal/ai`
- `/admin-portal/gamification`
- `/admin-portal/analytics`
- `/admin-portal/support`
- `/admin-portal/content`
- `/admin-portal/settings`
- `/admin-portal/audit`

Recommended UX patterns:

- master-detail layouts for user, moderation, and support workflows
- right-side drawers for inspection and inline actions
- sticky filters on high-volume list screens
- route-level guards based on capabilities

## API Strategy

Use REST as the default interface between the portal and plugin backend.

Recommended namespace:

- `myavana-admin/v1`

This keeps the dedicated portal API separate from public app endpoints and easier to reason about than expanding mixed-purpose AJAX handlers.

### Initial API domains

#### Overview

- `GET /overview`
- `GET /overview/cards`
- `GET /overview/feed`

#### Users

- `GET /users`
- `GET /users/:id`
- `GET /users/:id/activity`
- `POST /users/:id/reset-onboarding`
- `POST /users/:id/send-reset-link`
- `POST /users/:id/suspend`
- `POST /users/:id/reactivate`

#### Onboarding

- `GET /onboarding/funnel`
- `GET /onboarding/dropoff`
- `GET /onboarding/users`
- `POST /onboarding/config`

#### Journey

- `GET /journey/overview`
- `GET /journey/entries`
- `GET /journey/errors`

#### Goals and Routines

- `GET /goals-routines/overview`
- `GET /goals-routines/templates`
- `POST /goals-routines/templates`

#### Community

- `GET /community/overview`
- `GET /community/reports`
- `POST /community/reports/:id/resolve`
- `POST /community/content/:id/hide`

#### AI

- `GET /ai/overview`
- `GET /ai/components`
- `GET /ai/performance`
- `POST /ai/templates`
- `POST /ai/settings`

#### Gamification

- `GET /gamification/overview`
- `GET /gamification/challenges`
- `POST /gamification/challenges`

#### Support

- `GET /support/issues`
- `GET /support/auth`
- `GET /support/resets`

#### Settings

- `GET /settings`
- `POST /settings`

#### Audit

- `GET /audit`

## Data and Model Reuse

The new portal should reuse the existing plugin data layer wherever possible.

Known sources already in the plugin include:

- user meta for onboarding, goals, routines, and related state
- custom tables for profiles, social features, intelligence events, and AI component storage
- analytics and intelligence aggregations already exposed through existing models

Migration principle:

- reuse existing tables first
- add service classes where data currently comes from scattered functions
- normalize response shapes at the API layer instead of rewriting all lower-level storage immediately

## Recommended Code Structure

Suggested structure for the new portal code:

- `includes/admin-portal/`
- `includes/admin-portal/class-admin-portal-router.php`
- `includes/admin-portal/class-admin-portal-permissions.php`
- `includes/admin-portal/class-admin-portal-audit-log.php`
- `includes/admin-portal/class-admin-portal-overview-controller.php`
- `includes/admin-portal/class-admin-portal-users-controller.php`
- `includes/admin-portal/class-admin-portal-settings-controller.php`
- `admin-portal-app/` for the frontend app source

Suggested migration strategy:

- keep `includes/class-myavana-admin-controller.php` as the seed for REST patterns
- introduce a more focused admin portal namespace and controller layout
- leave `includes/myavana_admin_settings.php` in place during migration
- retire old WP-admin pages gradually rather than through a hard cutover

## Migration Plan

### Phase 1: Foundation

Deliverables:

- protected `/admin-portal/` route
- admin portal shell
- role and capability layer
- route guards
- portal navigation
- audit logging foundation
- REST base namespace and shared response helpers

Outcome:

- the portal exists as a real destination and can host production-facing admin workflows

### Phase 2: Dashboard Migration

Deliverables:

- Overview
- Analytics summary cards
- executive snapshot
- AI overview
- operations overview

Outcome:

- staff can use the portal for the current high-level dashboards instead of `wp-admin`

### Phase 3: User Operations

Deliverables:

- users list
- user profile detail
- onboarding inspection
- password reset tools
- account support actions

Outcome:

- support and ops teams can work primarily inside the new portal

### Phase 4: Product Operations

Deliverables:

- journey activity
- goals and routines
- community moderation
- gamification management

Outcome:

- core feature teams can operate the product from one console

### Phase 5: Intelligence and Control

Deliverables:

- AI component management
- AI performance analytics
- template management
- advanced settings
- diagnostics
- audit explorer

Outcome:

- the portal becomes the system of record for platform operations

### Phase 6: WordPress Admin Reduction

Deliverables:

- remove forced dependency on WP dashboard for MYAVANA staff
- retain only infrastructure and low-level plugin maintenance in `wp-admin`
- optional redirect from old MYAVANA admin menu pages into the portal

Outcome:

- WordPress becomes the backend and maintenance layer, not the staff workspace

## Recommended V1 Scope

The first implementation should be intentionally narrow.

Build now:

- admin portal shell
- Overview
- AI Intelligence summary
- Operations summary
- Users list
- User detail panel
- Settings surface for core keys and feature flags
- audit log groundwork

Do not build in V1:

- full moderation suite
- full template editing system
- challenge builders
- advanced cohort tooling
- every historical report from the PHP dashboard

V1 success criteria:

- staff can log into `/admin-portal/`
- authorized roles see only the sections they are allowed to use
- Overview, AI, and Users are stable enough for daily internal use
- staff can handle common support actions without returning to the old dashboard

## Risks and Constraints

### 1. Mixed legacy patterns

The plugin currently mixes:

- procedural admin rendering
- model-style analytics access
- AJAX handlers
- emerging REST patterns

This is manageable, but API boundaries must be defined before UI growth.

### 2. Capability sprawl

If the portal launches without a capability map, access control will remain overly broad. Custom capabilities need to be part of the foundation phase.

### 3. Data inconsistency

Some platform concepts are stored across user meta, posts, and custom tables. Portal APIs should normalize these inconsistencies for the UI.

### 4. Migration overlap

Both systems will coexist for a while. Navigation, redirects, and staff documentation need to make that transition clear.

### 5. Performance

The new portal will surface more data per screen than the current PHP dashboard. Endpoints must support pagination, filtering, and caching where appropriate.

## Immediate Next Steps

1. Create the portal foundation in code:
   - protected page route
   - app shell template
   - capability bootstrap
   - REST namespace

2. Define the frontend app structure:
   - layout shell
   - route map
   - shared table, card, drawer, filter, and metric components

3. Implement the first API set:
   - overview
   - users
   - settings
   - audit logging base

4. Migrate the first workflows:
   - overview
   - AI summary
   - user support actions

5. Keep the current WP-admin dashboard live until the portal is operationally credible.

## Build Recommendation

Start with the portal foundation and V1 feature slice, not with a visual redesign alone.

The correct order is:

- permissions
- protected route
- API contract
- shell
- overview and users
- settings and audit

Once that is stable, the rest of the admin experience can be migrated screen by screen without creating a second unstable backend.

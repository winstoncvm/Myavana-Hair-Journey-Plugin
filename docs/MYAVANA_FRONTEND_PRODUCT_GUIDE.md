# MYAVANA Hair Journey Frontend Product Guide

## Purpose
This document explains the MYAVANA Hair Journey web app from a product and user-experience perspective. It is intended for leadership, product, operations, customer success, and anyone who needs a complete view of what the site does and how users interact with it.

## Product Summary
MYAVANA Hair Journey is a hair care companion platform that helps users:
- personalize their experience through onboarding and profile data
- log and review their hair journey over time
- set goals and routines
- view progress through timeline, calendar, and list experiences
- participate in a social community
- use AI-connected experiences and analysis-driven flows
- access the experience seamlessly on desktop and mobile

The plugin functions as the application layer for the site. It controls most of the frontend experience, major protected routes, navigation, engagement systems, community interactions, analytics, onboarding, and admin intelligence tooling.

## Main Site Pages

### 1. Home
Shortcode: `myavana_luxury_home_shortcode`

Purpose:
- acts as the landing page and primary entry point into the app
- introduces the brand, value proposition, and core product actions
- supports authentication entry points and onboarding triggers
- presents AI-related actions and guided entry into the product ecosystem

What users can do:
- sign in or sign up
- start onboarding if they are new
- access the Hair Journey experience
- discover the MYAVANA experience in a premium landing-page format

Expected user experience:
- premium editorial/luxury visual design
- clear CTA hierarchy
- auth modal access for logged-out visitors
- mobile-friendly hero and conversion flow

### 2. My Timeline
Site page label: My Timeline / Hair Journey
Shortcode: `myavana_hair_journey_page_shortcode`

Purpose:
- this is the core journaling and progress-tracking workspace
- it centralizes entries, goals, routines, analysis context, rewards, and progress history

Primary features:
- compact command header with progress, streak, points, badges, and check-in state
- glassmorphic workspace shell
- timeline view as the default primary experience
- calendar view for date-based browsing
- list view for structured scanning
- global create flows for entries, goals, and routines
- global view/edit offcanvas for entries, goals, and routines
- rewards, quests, and active challenge surfaces
- onboarding-to-first-entry handoff for new users

What users can do here:
- add a new hair journey entry
- upload images and video for entries
- capture media with device camera where supported
- view and edit existing entries
- create and manage goals
- create and manage routines
- switch between timeline, calendar, and list modes
- see daily quests, recent wins, and active challenges

Key UX notes:
- timeline is the default tab
- the page is responsive and mobile-first
- the create entry experience supports more intentional first-entry guidance after onboarding
- the workspace is designed to feel like an app rather than a blog page

### 3. Profile
Shortcode: `myavana_unified_profile_shortcode`

Purpose:
- this is the user’s account hub and identity layer inside the app
- it combines profile data, hair details, social summary, journey stats, achievements, and quick actions

Primary features:
- profile overview and hair details
- journey statistics and rewards blocks
- quick actions such as new entry creation
- access to goals, routines, and shared activity
- global offcanvas support for viewing details from profile surfaces
- integrated community-aware profile behavior

What users can do here:
- review their hair profile and preferences
- see progress and gamification status
- create or manage entries directly from profile
- review goals, routines, and timeline-linked data
- access social identity and shared content context

### 4. Community
Shortcode: `myavana_community_feed_shortcode`

Purpose:
- community is the social layer of the product
- it enables users to share posts, media, routines, challenges, and engagement around hair journeys

Primary features:
- feed of community posts
- post creation and editing
- image and video support
- discovery surfaces such as trending hashtags and suggested creators
- filters for media types and search
- likes, comments, bookmarks, follows, and reactions
- challenges and community participation features

What users can do here:
- create text, image, and video posts
- engage with other users’ posts
- follow creators and discover trends
- bookmark posts and routines
- participate in community challenges
- edit their own posts and manage media

Current product note:
- community is feature-rich and already useful, but still an active area for continued polish and expansion

### 5. Goals
Shortcode: `myavana_goals_page_shortcode`
Shortcode tag: `myavana_goals_page`

Purpose:
- provides a focused management experience for user hair goals

Primary features:
- create, view, edit, and delete goals
- progress tracking and milestone behavior
- goal data also appears across Timeline and Profile
- global offcanvas support for viewing and editing

What users can do here:
- define hair goals
- review status and target direction
- update progress over time
- open goals from multiple surfaces in a consistent way

### 6. Routines
Shortcode: `myavana_routines_page_shortcode`
Shortcode tag: `myavana_routines_page`

Purpose:
- gives users a dedicated place to define and manage recurring hair care routines

Primary features:
- create, view, edit, and delete routines
- track routine completion
- connect routines into timeline and profile views
- global offcanvas support

What users can do here:
- create routines for wash day, maintenance, treatments, or daily care
- manage routine steps and frequency
- mark routines as completed
- revisit routines from Timeline, Profile, and dedicated Routines page

## Global Navigation

### Desktop Navigation
The site uses a single global desktop navbar.

Desktop navbar responsibilities:
- primary page navigation
- consistent branding and app identity
- quick access to key app destinations
- profile/avatar-driven account controls
- cleaner, enterprise-style navigation structure

### Mobile Navigation
Mobile uses an app-style navigation model.

Mobile navigation includes:
- a simplified app header with logo and icon-based actions
- profile avatar with account dropdown behavior
- bottom tabs for quick navigation between key app pages
- reduced dependency on large desktop menus

Mobile bottom tabs are intended for:
- Home
- My Timeline
- Community
- Profile
- additional key shortcuts depending on active build

Mobile UX goals:
- one-handed navigation
- fast page switching
- minimal clutter in the top header
- app-like familiarity

## Authentication and Access Flow

### Auth Experience
The site uses a custom auth modal.

Users can:
- sign in
- sign up
- use Google Auth when configured
- be redirected safely back to the page they intended to access

### Protected Routes
Protected app pages redirect logged-out users away from blank or broken states.

Examples of protected areas:
- Hair Journey / My Timeline
- Profile
- Community-related protected flows
- Goals and Routines pages when configured as protected app pages

Expected behavior:
- logged-out users are redirected into the home/auth flow instead of seeing blank screens
- return redirects are preserved safely

## Onboarding Experience

Purpose:
- collect the minimum information needed to personalize the experience
- help the user begin with context instead of landing in an empty app

What onboarding captures:
- name
- hair type
- hair texture
- hair goals

Current improved behavior:
- onboarding data is saved to user meta and profile structures
- goals selected during onboarding can seed structured hair goals
- onboarding completion redirects the user into Hair Journey
- users are guided into their first entry instead of receiving a generic placeholder post
- onboarding draft data is preserved client-side during the modal flow

## Hair Journey Entry System

Entries are the core unit of personal progress tracking.

Supported entry capabilities:
- title, description, date, and time
- rating and mood
- products and notes
- photos and video
- camera capture where supported
- view and edit via global offcanvas
- first-entry prefill after onboarding

Entry UX goals:
- easy to create from anywhere
- rich enough to support long-term tracking
- visually clear on mobile and desktop

## Goals and Routines System

### Goals
Users can:
- define goals
- update progress
- review goals across pages
- manage them from a dedicated page or from global offcanvas

### Routines
Users can:
- define repeatable care routines
- track completion
- use routine data inside Timeline and Profile
- manage them globally from multiple surfaces

## Community Experience

Community is designed as a world-class social product layer for hair journey storytelling.

Core community capabilities:
- media-rich posts
- creator discovery
- hashtags and trending discovery
- engagement actions: follow, like, comment, bookmark, react
- video support
- community challenges
- media-aware editing flow for posts

Why it matters:
- community turns private tracking into shared inspiration and retention loops
- it supports both women and men documenting and sharing their hair journeys

## Gamification and Motivation
The product includes a gamification layer to improve consistency and retention.

Visible user-facing elements can include:
- streaks
- points and levels
- badges
- daily and weekly quests
- challenge progress
- recent rewards and achievements

Where users see this:
- Hair Journey header and rewards surfaces
- Profile rewards and achievement-related areas

## AI-Connected Experiences
The site includes AI-connected flows, but button behavior can be directed to MYAVANA’s preferred AI tool experience as needed.

AI-related experiences may include:
- analysis-linked entry creation
- AI analysis history and connected results
- redirection to the official MYAVANA consumer AI experience when configured
- AI-related engagement metrics and admin reporting

## Mobile Experience Summary
The mobile version is not a reduced copy of desktop. It is treated like an app surface.

Mobile experience includes:
- app-style top header
- bottom tabs for quick navigation
- responsive page layouts and cards
- optimized modals and offcanvas panels
- simplified interactions to reduce friction
- extra bottom spacing to account for tab navigation and floating UI elements

## Typical User Journey

### New User Journey
1. User lands on Home.
2. User signs up.
3. User completes onboarding.
4. User is redirected to Hair Journey.
5. User is guided into creating their first hair journey entry.
6. User can then explore Profile, Community, Goals, and Routines.

### Returning User Journey
1. User signs in.
2. User lands in their app experience.
3. User checks timeline, logs an entry, updates a routine, or joins community activity.
4. User revisits Profile and other pages as needed.

## Operational Notes for Non-Technical Stakeholders
- The app experience is plugin-driven and not just a standard WordPress content site.
- Most key user flows are custom-built.
- Mobile and desktop are intentionally different where necessary to improve usability.
- The product includes both a user-facing app and an admin intelligence/dashboard layer.

## Recommended Uses of This Document
This document is suitable for:
- leadership updates
- stakeholder onboarding
- product walkthroughs
- training customer support or operations teams
- describing the product before launch or demos

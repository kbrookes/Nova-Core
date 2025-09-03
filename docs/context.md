# Nova Core Plugin - Development Context

## Current Version
**Version 0.1.40** - [Current Date]

## Version History

### Version 0.1.40 - [Current Date]
- Feature/Change: Simplified tracking approach to always send events to all available backends
- Status: ✅ Complete
- Notes:
  - Removed complex detection logic and getTrackingMode() function
  - trackEvent() now always tries to send to Plausible, Google Analytics, and Zaraz if available
  - Simplified PHP configuration to only pass essential page title and environment info
  - This approach works regardless of how tracking scripts are loaded (local or via Zaraz)
  - Aligns with original intent to use Zaraz for loading all tracking scripts

### Version 0.1.39 - [Current Date]
- Feature/Change: Improved page detection logic and added debugging for page identification
- Status: 🔍 Testing
- Notes:
  - Enhanced getWPPageName() function to detect more WordPress page types
  - Added support for post-type-* and tax-* CSS classes
  - Added fallback to document title
  - Added temporary debugging to see what page information is available
  - This should resolve "Unknown Page" issues in tracking events

### Version 0.1.38 - [Current Date]
- Feature/Change: Clean production release - removed all debugging code
- Status: ✅ Complete
- Notes:
  - Removed all console.log debugging statements
  - Kept only essential version identifier
  - Tracking is now working perfectly after resolving duplicate theme code conflicts
  - Clean, production-ready codebase

### Version 0.1.37 - [Current Date]
- Feature/Change: Fixed version identifier in JavaScript to match plugin version
- Status: ✅ Complete
- Notes:
  - Corrected the console.log version number from 0.1.35 to 0.1.36
  - This was a simple oversight when adding the version identifier
  - The tracking is now working correctly after removing duplicate theme code

### Version 0.1.36 - [Current Date]
- Feature/Change: Added version identifier to confirm correct tracking code is loaded
- Status: 🔍 Testing
- Notes:
  - Added console.log with version identifier to confirm we're running the right code
  - The "Staging mode" message appears to be coming from a different source than the IntersectionObserver
  - This suggests there might be cached JavaScript or duplicate tracking code running

### Version 0.1.35 - [Current Date]
- Feature/Change: Added debugging to IntersectionObserver to investigate duplicate section processing
- Status: 🔍 Testing
- Notes:
  - Added console.log statements to see exactly when IntersectionObserver triggers
  - Added debugging to show what sections are being observed
  - This will help identify if the same section is being processed multiple times by the observer
  - The issue appears to be duplicate IntersectionObserver triggers for the same section

### Version 0.1.34 - [Current Date]
- Feature/Change: Added debugging to trackedSections array to investigate duplicate scroll event processing
- Status: 🔍 Testing
- Notes:
  - Added console.log statements to see if sections are being processed multiple times
  - This will help identify if there's an issue with the IntersectionObserver or duplicate section detection
  - The issue appears to be that trackEvent() is called but "Staging mode" message still appears

### Version 0.1.33 - [Current Date]
- Feature/Change: Added comprehensive debugging to getTrackingMode function to investigate tracking backend detection
- Status: 🔍 Testing
- Notes:
  - Added detailed console.log statements in getTrackingMode() to see exactly why it's returning 'none' instead of 'zaraz'
  - This will help identify if there's an issue with forceMode detection or the logic flow

### Version 0.1.32 - [Current Date]
- Feature/Change: Added debugging to scroll tracking to investigate isProduction variable behavior
- Status: 🔍 Testing
- Notes:
  - Added console.log statements in scroll tracking sections to debug why isProduction is true but events are still suppressed
  - This will help identify if there's a variable scope or timing issue

### Version 0.1.31 - [Current Date]
- Feature/Change: Fixed tracking configuration by switching to wp_localize_script and cleaned up debugging
- Status: ✅ Complete
- Notes:
  - Identified that wp_add_inline_script was not working reliably
  - Switched to wp_localize_script which is working perfectly
  - Cleaned up complex debugging code
  - Tracking should now work properly in production mode with Zaraz

### Version 0.1.30 - [Current Date]
- Feature/Change: Enhanced debugging with fallback configuration methods and comprehensive script injection troubleshooting
- Status: ✅ Complete
- Notes:
  - Added wp_localize_script as backup to wp_add_inline_script
  - Added HTML comment debugging to verify PHP execution and expected configuration
  - Updated JavaScript to check both configuration sources
  - This will help identify which script injection method works and why the configuration is not being loaded

### Version 0.1.29 - [Current Date]
- Feature/Change: Restored tracking configuration and added comprehensive debugging to identify script injection issues
- Status: ✅ Complete
- Notes:
  - Restored accidentally removed js_config variable
  - Added HTML comment verification that tracking function is executing
  - Added detailed logging for script enqueuing and inline script addition
  - This will help identify why the tracking configuration is not being injected into the page

### Version 0.1.28 - [Current Date]
- Feature/Change: Enhanced debugging with config modification monitoring and detailed PHP logging
- Status: ✅ Complete
- Notes:
  - Added Object.defineProperty to monitor when window.trackingConfig is modified
  - Added detailed PHP logging to error_log for both array and JSON output
  - Added HTML comment debugging to verify script injection
  - This will help identify exactly when and how the tracking configuration is being corrupted

### Version 0.1.27 - [Current Date]
- Feature/Change: Added comprehensive debug logging to troubleshoot tracking configuration issues
- Status: ✅ Complete
- Notes:
  - Added PHP debug logging to error_log for tracking configuration
  - Added JavaScript console logging for config, environment, isProduction, trackingEnabled, forceMode, and autodetect
  - Added check for config modification after initial load
  - This will help identify why environment is undefined and events are still showing as "suppressed" in production mode

### Version 0.1.26 - [Current Date]
- Feature/Change: Added debug logging to tracking configuration to troubleshoot production mode issues
- Status: ✅ Complete
- Notes:
  - Added console logging for tracking config, environment, isProduction, trackingEnabled, forceMode, and autodetect
  - This will help identify why events are still showing as "suppressed" in production mode
  - Fixed tracking mode logic to properly prioritize forced modes over auto-detection

### Version 0.1.25 - [Previous Date]

---

## Purpose

Nova Core provides shared functionality across all Nova Strategic websites. It is designed to be lightweight, modular, and easy to toggle on or off. Its purpose is to avoid bloated child themes and ensure consistent business logic is housed in one versioned location.

---

## Key Responsibilities

- Enqueue structured tracking scripts (Plausible, Zaraz, GA)
- Register CPTs used across all Nova sites (e.g. Page Types, Services, Resources)
- Register accompanying ACF field groups via PHP (not ACF GUI)
- Register utility functions for use in Bricks `{echo}` fields or templates
- Centralise configuration for reusable filters (e.g., excerpts, query loops)

---

## Design Principles

1. **Modular by default** – features should be toggleable and not load if not needed.
2. **Non-visual** – plugin handles logic, not layout or design.
3. **Theme-agnostic** – should work in any Nova site using any theme (primarily Bricks).
4. **Developer-aware** – provide hooks, filters, and well-named internal functions.
5. **AI-guided** – structure the repo and documentation to guide LLMs effectively during development.

---

## Upcoming Features

### Core Modules
| Feature | Status | Notes |
|--------|--------|-------|
| Structured Tracking | ✅ Ready | Supports Zaraz, gtag, Plausible |
| Page Type CPT | 🔲 Planned | Replaces legacy layouts |
| Service CPT | 🔲 Planned | Used for client-facing service structuring |
| Resource CPT | 🔲 Planned | Tied to downloads, modals, popups |
| Nova JS Utils | 🔲 Planned | Includes tracking.js, future interactivity logic |

---

## Known Gaps

1. No admin settings page yet (planned in `settings-page.php`)
2. ACF fields for CPTs still in legacy GUI form
3. Tracking settings (mode, domain) hardcoded in script — needs GUI config
4. Lacks test automation and WP CLI integration
5. No caching or performance profiling done yet
6. Zaraz auto-detection not working reliably on production sites with Cloudflare rules

---

## Current Dev Notes

- Tracking module is fully functional and implemented
- Tracking uses `getWPPageName()` instead of `document.title`
- All tracked props follow `{ section, menu, label, page }` structure
- Console logs in staging are unified using `Object.assign`
- Future plan is to register all fields using `acf_add_local_field_group()`
- Each feature file lives in `/includes/` and is loaded via `nova-core.php`

---

## Development Philosophy

- Use Cursor with `context.md`, `user-stories.md`, and file-level comments to keep AI on track
- Avoid "magic" behavior — all logic should be legible to humans and LLMs
- Make all major features testable from WP admin after plugin activation
- Stay lean: no unnecessary admin UIs or interfaces unless justified

---

## To Do (Priority)

### High
- Scaffold CPT + ACF modules
- Create settings API scaffold
- Connect `tracking.js` config to admin UI
- Move all standard CPTs + fields into plugin

### Medium
- Integrate optional Bricks-specific functions
- Add Composer support for easier autoloading
- Start version tagging for Git Updater

### Low
- Add full admin UI for feature toggles
- Create onboarding wizard for internal installs
- Support multi-language ACF field registration (via Polylang or WPML)

---

## Testing Requirements

- Confirm tracking works across all 3 modes (Zaraz, GA, Plausible)
- Check script loads only once per page
- Ensure CPTs and ACF groups load only when enabled
- Verify plugin activation does not break admin on theme-less install

---

## Documentation Requirements

- Internal README per module (tracking, CPTs, ACF, etc.)
- Git Updater integration docs
- Nova site install SOP with plugin + settings configuration

---

## Questions to Clarify With Cursor

- Should this logic be global, scoped to one CPT, or optional?
- Are we building for theme integration or self-contained logic?
- If extending Bricks: what would the `{echo}` function or shortcode return?

---

## Cursor-Specific Notes

- Use this context file to maintain long-term AI memory
- Refer to `docs/user-stories.md` for real-world scenarios
- Use comments in each PHP/JS file to provide filename purpose

## Documentation Maintenance

### Version History
- All changes to the plugin should be documented in this file
- Never delete or remove historical content from this file
- Use status indicators (✅, 🔲, 🚧) to track feature progress
- Add new features as they are requested
- Update status of existing features as they are completed

### Status Indicators
- ✅ Complete
- 🔲 Planned
- 🚧 In Progress
- ⏸️ On Hold
- 🔄 In Review

### Documentation Rules
1. **Preserve History**: Never remove or delete existing content
2. **Track Progress**: Update feature statuses as work progresses
3. **Add Context**: Document new features with clear descriptions
4. **Maintain Structure**: Keep the document organized by sections
5. **Version Tracking**: Note significant changes and their versions

### Change Log Format
When adding new features or updates, use this format:
```markdown
### [Version Number] - [Date]
- Feature/Change: [Description]
- Status: [Status Indicator]
- Notes: [Any relevant implementation details]
```

### Version 0.1.1 - [Current Date]
- Feature/Change: Fixed page title tracking to use WordPress post title instead of SEO title
- Status: ✅ Complete
- Notes: Modified tracking.php to pass WordPress post title to JS, updated tracking.js to use config.pageTitle instead of document.title

### Version 0.1.2 - [Current Date]
- Feature/Change: Added support for data-click attribute in tracking
- Status: ✅ Complete
- Notes: Added data-click as the preferred attribute for click tracking while maintaining backward compatibility with data-plausible

### Version 0.1.3 - [Current Date]
- Feature/Change: Added admin settings interface
- Status: ✅ Complete
- Notes: Created settings page with tracking mode and feature toggles, integrated settings with tracking implementation

### Version 0.1.4 - [Current Date]
- Feature/Change: Improved admin interface with separate option groups
- Status: ✅ Complete
- Notes: Split settings into tracking and features options, fixed tab display issues

### Version 0.1.5 - [Current Date]
- Feature/Change: Added tracking mode display and Zaraz cookie management
- Status: ✅ Complete
- Notes: Added visual indicator of current tracking mode and implemented Zaraz cookie for logged-in users

### Version 0.1.6 - [Current Date]
- Feature/Change: Added Instructions tab with Zaraz configuration guide
- Status: ✅ Complete
- Notes: Added detailed instructions for setting up Zaraz tracking and excluding admin users

### Version 0.1.7 - [Current Date]
- Feature/Change: Clarified Zaraz configuration instructions
- Status: ✅ Complete
- Notes: Updated instructions to specify "Disable Zaraz" action instead of "Block" for admin exclusion rule

### Version 0.1.8 - [Current Date]
- Feature/Change: Added Git Updater compatibility
- Status: ✅ Complete
- Notes: Added required headers for automatic updates via Git Updater

### Version 0.1.9 - [Current Date]
- Feature/Change: Fixed tracking mode detection and label styling
- Status: ✅ Complete
- Notes: 
  - Improved Zaraz detection to work with Cloudflare-loaded scripts
  - Added red label color for disabled tracking mode
  - Enhanced JavaScript-to-PHP communication for mode detection

### Version 0.1.10 - [Current Date]
- Feature/Change: Enhanced tracking interface and environment controls
- Status: 🚧 In Progress
- Notes: 
  - Added environment selection (Development/Production)
  - Added global tracking toggle
  - Improved mode selector and status display
  - Known issue: Zaraz auto-detection not working reliably on production sites with Cloudflare rules
  - TODO: Investigate and fix Zaraz detection when disabled via Cloudflare rules

### Version 0.1.11 - [Current Date]
- Feature/Change: Improved Plausible event naming
- Status: ✅ Complete
- Notes: 
  - Updated Plausible event names to show actual click event name
  - Format changed to "Event Name - Section - Page"
  - Maintains original event name for other tracking methods
  - Uses WordPress page name instead of SEO title

### Version 0.1.12 - [Current Date]
- Feature/Change: Improved Plausible event naming
- Status: ✅ Complete
- Notes: 
  - Updated Plausible event names to show actual click event name
  - Format changed to "Event Name - Section - Page"
  - Maintains original event name for other tracking methods
  - Uses WordPress page name instead of SEO title

### Version 0.1.13 - [Current Date]
- Feature/Change: Added Case Studies ACF fields registration
- Status: ✅ Complete
- Notes: 
  - Implemented ACF fields for Case Studies post type
  - Fields organized into tabs: Client Info, Challenge, Approach, Solution, Experience, and Testimonial
  - Added testimonial relationship field to link case studies with testimonials
  - Fields only register when Case Studies feature is enabled
  - Maintained all original field keys and settings from ACF export

### Version 0.1.16 - [Current Date]
- Feature/Change: Added Pro Site Settings options page and ACF fields
- Status: ✅ Complete
- Notes: 
  - Added new options page under Settings menu
  - Implemented comprehensive site settings fields including:
    - Your Details (name, contact info, headshot)
    - Credibility Center (heading, text, CTA)
    - Footer Contact Details (address, phone, email, business info)
    - Benefits Section (title, intro, repeater field for benefits)
    - Blog Setup (hero image, title, headline)
  - All fields organized in tabs for better usability
  - Fields registered via PHP for better version control
  - Maintained all original field keys and settings

### Version 0.1.17 - [Current Date]
- Feature/Change: Added Blog Settings tab to Nova Core settings
- Status: ✅ Complete
- Notes: 
  - Added new Blog Settings tab in the admin interface
  - Implemented settings for:
    - Posts per page (1-100)
    - Excerpt length (10-200 words)
    - Author display toggle
    - Date display toggle
  - Settings stored in `nova_core_blog_options`
  - All settings have descriptive help text
  - Default values set for all options

### Version 0.1.18 - [Current Date]
- Feature/Change: Added rich editor support for category descriptions
- Status: ✅ Complete
- Notes: 
  - Added utility function to enable WordPress rich editor for category descriptions
  - Removed default HTML stripping filters
  - Added full editor with media buttons and quicktags
  - Editor height set to 300px for better usability
  - Maintains proper HTML encoding/decoding
  - Added descriptive help text

## Admin Interface

### Settings Page
The Nova Core settings page is accessible under Settings > Nova Core in the WordPress admin. It provides:

1. **Tracking Settings** (Tab)
   - Tracking Mode selection (Auto-detect, Zaraz, Google Analytics, Disabled)
   - Auto-detect will attempt to use the best available tracking solution
   - Settings stored in `nova_core_tracking_options`

2. **Feature Toggles** (Tab)
   - Page Types CPT
   - Services CPT
   - Resources CPT
   - Settings stored in `nova_core_features_options`

3. **Instructions** (Tab)
   - Step-by-step guide for setting up Zaraz tracking
   - Configuration instructions for Google Analytics and Plausible
   - Filter expression for excluding admin users
   - Important note about using "Disable Zaraz" action
   - Styled documentation with code examples

### Settings Storage
- Tracking settings are stored in `nova_core_tracking_options`
- Feature settings are stored in `nova_core_features_options`
- Default values are provided for all settings
- Each tab has its own form and save button

## Tracking Attributes

### Click Tracking
- `data-click="Event Name"` - Preferred attribute for click event tracking
- `data-plausible="Event Name"` - Legacy attribute (maintained for backward compatibility)

Both attributes can be used to track click events. The `data-click` attribute is preferred for new implementations as it better describes the action being tracked.

## Tracking Implementation

### Tracking Modes
The plugin supports multiple tracking modes, configurable in the admin interface:

1. **Auto-detect** (Default)
   - Automatically selects the best available tracking solution
   - Priority order: Zaraz > Google Analytics
   - Falls back to 'none' if no tracking solution is available
   - Best for sites where tracking solution might change

2. **Zaraz**
   - Forces the use of Zaraz tracking
   - Requires Zaraz to be properly configured on the site
   - Events are sent using `zaraz.track(eventName, props)`
   - Best for sites using Cloudflare's Zaraz

3. **Google Analytics**
   - Forces the use of Google Analytics tracking
   - Requires gtag to be properly configured on the site
   - Events are sent using `gtag('event', eventName, {...})`
   - Best for sites using Google Analytics 4

4. **Disabled**
   - Turns off all tracking
   - Events are only logged to console in development
   - Useful for testing or when tracking needs to be temporarily disabled

### Event Properties
All tracked events include the following properties:
- `section`: The section where the event occurred
- `page`: The current page name (from WordPress)
- `label`: The specific element that triggered the event (for clicks)
- `menu`: The menu where the click occurred (for menu items)

### Development Mode
In non-production environments (not matching productionDomains):
- Events are logged to console instead of being sent to tracking services
- Console logs include all event properties for debugging
- Format: `Staging mode → [event type] suppressed: { event, section, page, ... }`

### Tracking Mode Display
The admin interface now shows the currently active tracking mode with a green label:
- Shows the detected mode when using Auto-detect
- Shows the forced mode when explicitly set
- Updates automatically when settings are changed

### Zaraz Cookie Management
When using Zaraz tracking, the plugin automatically manages a cookie to identify logged-in users:
- Cookie name: `nova_zaraz_logged_in`
- Set on login and cleared on logout
- 24-hour expiration
- Secure and HTTP-only
- Used to disable Zaraz tracking for logged-in users in Cloudflare

## Plugin Updates

### Git Updater Integration
The plugin is configured for automatic updates via Git Updater:

1. **Required Headers**
   - `GitHub Plugin URI`: https://github.com/kbrookes/Nova-Core
   - `Primary Branch`: main
   - `Update URI`: https://github.com/kbrookes/Nova-Core

2. **System Requirements**
   - WordPress 5.8 or higher
   - PHP 7.4 or higher
   - Git Updater plugin installed

3. **Update Process**
   - Updates are pulled from the main branch
   - Version numbers are managed via Git tags
   - Updates are available through WordPress admin

### Version Management
- Version numbers follow semantic versioning
- Each release should be tagged in Git
- Version history is maintained in this documentation

# Nova Core Plugin – Development Context

## Current Version: 0.1.0 (Plugin Scaffold)

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

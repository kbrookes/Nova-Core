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
- Avoid “magic” behavior — all logic should be legible to humans and LLMs
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

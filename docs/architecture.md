# Architecture

Each module (CPT, tracking, ACF) lives in `/includes/` and is registered conditionally via feature toggles.

All features are toggled from `nova-core.php` (initially), with future plans to support WP admin settings.

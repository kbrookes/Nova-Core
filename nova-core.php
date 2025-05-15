<?php
/**
 * Plugin Name: Nova Core
 * Description: Shared logic and components for all Nova Strategic sites.
 * Version: 0.1.10
 * Author: Nova Strategic
 * GitHub Plugin URI: https://github.com/kbrookes/Nova-Core
 * Primary Branch: main
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI: https://github.com/kbrookes/Nova-Core
 */

defined('ABSPATH') || exit;

// Core includes
require_once __DIR__ . '/includes/tracking.php';
require_once __DIR__ . '/includes/cpt-register.php';
require_once __DIR__ . '/includes/acf-fields.php';
require_once __DIR__ . '/includes/utils.php';
require_once __DIR__ . '/includes/settings-page.php';
require_once __DIR__ . '/includes/zaraz-cookie.php';

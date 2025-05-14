<?php
/**
 * Plugin Name: Nova Core
 * Description: Shared logic and components for all Nova Strategic sites.
 * Version: 0.1.0
 * Author: Nova Strategic
 * GitHub Plugin URI: https://github.com/kbrookes/Nova-Core
 * Primary Branch: main
 */

defined('ABSPATH') || exit;

// Core includes
require_once __DIR__ . '/includes/tracking.php';
require_once __DIR__ . '/includes/cpt-register.php';
require_once __DIR__ . '/includes/acf-fields.php';
require_once __DIR__ . '/includes/utils.php';
require_once __DIR__ . '/includes/settings-page.php';

<?php
/**
 * Plugin Name: Nova Core
 * Description: Shared logic and components for all Nova Strategic sites.
 * Version: 0.1.45
 * Author: Nova Strategic
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') || exit;

/**
 * GitHub Updater Configuration
 *
 * To enable updates for private repositories, define GITHUB_UPDATER_TOKEN
 * in wp-config.php:
 *
 * define('GITHUB_UPDATER_TOKEN', 'ghp_your_personal_access_token');
 *
 * The token needs 'repo' scope for private repositories.
 */
define('NOVA_CORE_GITHUB_OWNER', 'kbrookes');
define('NOVA_CORE_GITHUB_REPO', 'Nova-Core');

// Initialise GitHub updater (admin only, loaded early)
if (is_admin()) {
    require_once __DIR__ . '/includes/class-nova-updater.php';
    new Nova_GitHub_Updater(
        __FILE__,
        NOVA_CORE_GITHUB_OWNER,
        NOVA_CORE_GITHUB_REPO
    );
}

// Core includes
require_once __DIR__ . '/includes/tracking.php';
require_once __DIR__ . '/includes/cpt-register.php';
require_once __DIR__ . '/includes/acf-fields.php';
require_once __DIR__ . '/includes/utils.php';
require_once __DIR__ . '/includes/settings-page.php';
require_once __DIR__ . '/includes/zaraz-cookie.php';
require_once __DIR__ . '/includes/site-settings.php';
require_once __DIR__ . '/includes/rankmath-metabox.php';
require_once __DIR__ . '/includes/post-options.php';

<?php
// Zaraz Cookie Management

// Add Admin Cookie
add_action('wp_login', 'nova_core_add_zaraz_cookie');
function nova_core_add_zaraz_cookie() {
    setcookie('nova_zaraz_logged_in', 'true', time() + 86400, '/', '', is_ssl(), true);
}

// Remove Admin Cookie
add_action('wp_logout', 'nova_core_remove_zaraz_cookie');
function nova_core_remove_zaraz_cookie() {
    setcookie('nova_zaraz_logged_in', '', time() - 3600, '/', '', is_ssl(), true);
}

// Check if user is logged in and set cookie if needed
add_action('init', 'nova_core_check_zaraz_cookie');
function nova_core_check_zaraz_cookie() {
    if (is_user_logged_in() && !isset($_COOKIE['nova_zaraz_logged_in'])) {
        setcookie('nova_zaraz_logged_in', 'true', time() + 86400, '/', '', is_ssl(), true);
    }
} 
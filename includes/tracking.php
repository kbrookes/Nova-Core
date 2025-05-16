<?php
// Enqueue the tracking.js script and inject config

function nova_get_page_title() {
    if (is_singular()) {
        return get_the_title();
    } elseif (is_home()) {
        return 'Home';
    } elseif (is_archive()) {
        return 'Archive';
    } elseif (is_search()) {
        return 'Search';
    } elseif (is_404()) {
        return '404';
    }
    return 'Unknown Page';
}

add_action('wp_enqueue_scripts', 'nova_enqueue_tracking_script');
function nova_enqueue_tracking_script() {
    $options = get_option('nova_core_tracking_options');
    $tracking_mode = isset($options['tracking_mode']) ? $options['tracking_mode'] : 'auto';
    $environment = isset($options['environment']) ? $options['environment'] : 'production';
    $tracking_enabled = isset($options['tracking_enabled']) ? $options['tracking_enabled'] : 1;

    wp_enqueue_script(
        'nova-tracking',
        plugin_dir_url(__FILE__) . '../assets/js/tracking.js',
        array(),
        '1.0',
        true
    );

    // Pass settings to JS
    wp_add_inline_script('nova-tracking', 'window.trackingConfig = ' . json_encode(array(
        'autodetect' => $tracking_mode === 'auto',
        'forceMode' => $tracking_mode === 'auto' ? '' : $tracking_mode,
        'mode' => $tracking_mode,
        'pageTitle' => nova_get_page_title(),
        'environment' => $environment,
        'trackingEnabled' => (bool)$tracking_enabled
    )) . ';', 'before');

    // Add data attribute to script for config updates
    wp_script_add_data('nova-tracking', 'data-tracking-config', '');
}
<?php
// Enqueue the tracking.js script and inject config

add_action('wp_enqueue_scripts', 'nova_enqueue_tracking_script');
function nova_enqueue_tracking_script() {
    $tracking_mode = 'auto'; // Options: auto, zaraz, gtag, none

    wp_enqueue_script(
        'nova-tracking',
        plugin_dir_url(__FILE__) . '../assets/js/tracking.js',
        array(),
        '1.0',
        true
    );

    // Pass settings to JS
    wp_add_inline_script('nova-tracking', 'window.trackingConfig = ' . json_encode(array(
        'autodetect' => true,
        'forceMode' => '',
        'mode' => $tracking_mode
    )) . ';', 'before');
}
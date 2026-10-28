<?php
// Enqueue the tracking.js script, inject config, and keep the Plausible WP
// plugin from double-counting form submissions.
//
// Spec: Rapid Site/Plausible/event-survey-2026-10/nova-core-tracking-prompt.md

/**
 * Parse the per-site conversion form map from settings.
 *
 * One form per line: `identifier = type[, asset]` where type is
 * contact / download / newsletter. Identifiers are plugin-prefixed form ids
 * as tracking.js computes them: ff-<id>, bricks-<element id>, wpforms-<id>,
 * gform-<id>. Lines starting with # are comments.
 *
 * @return array map of identifier => array{type: string, asset?: string}
 */
function nova_core_get_tracking_form_map() {
    $options = get_option('nova_core_tracking_options');
    $raw = isset($options['form_map']) ? $options['form_map'] : '';
    $map = array();

    foreach (preg_split('/[\r\n]+/', (string) $raw) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $identifier = trim($parts[0]);
        $rhs = array_map('trim', explode(',', $parts[1], 2));
        $type = strtolower($rhs[0]);
        if ($identifier === '' || !in_array($type, array('contact', 'download', 'newsletter'), true)) {
            continue;
        }
        $entry = array('type' => $type);
        if (isset($rhs[1]) && $rhs[1] !== '') {
            $entry['asset'] = $rhs[1];
        }
        $map[$identifier] = $entry;
    }

    return $map;
}

add_action('wp_enqueue_scripts', 'nova_enqueue_tracking_script');
function nova_enqueue_tracking_script() {
    $options = get_option('nova_core_tracking_options');
    $environment = isset($options['environment']) ? $options['environment'] : 'production';

    wp_enqueue_script(
        'nova-tracking',
        plugin_dir_url(__FILE__) . '../assets/js/tracking.js',
        array(),
        // Cache-bust on every change so CDN/browser caches pick up new releases
        filemtime(plugin_dir_path(__FILE__) . '../assets/js/tracking.js'),
        true
    );

    wp_localize_script('nova-tracking', 'novaCoreConfig', array(
        'environment' => $environment,
        'formMap'     => nova_core_get_tracking_form_map(),
    ));
}

/**
 * Disable the Plausible WP plugin's form tracking wherever that plugin runs.
 *
 * Its "form completions" enhanced measurement sends `WP Form Completions`
 * (and, in current plugin versions, adds the script-level form-submissions
 * extension that sends `Form: Submission`). Both duplicate Nova Core's form
 * events, so the measurement is stripped at option-read time; everything else
 * (outbound links, file downloads, 404s) is left as configured.
 *
 * Sites that embed the Plausible script manually (without the WP plugin) must
 * drop the form-submissions extension from the script tag by hand.
 */
add_filter('option_plausible_analytics_settings', 'nova_core_disable_plausible_form_tracking');
add_filter('default_option_plausible_analytics_settings', 'nova_core_disable_plausible_form_tracking');
function nova_core_disable_plausible_form_tracking($settings) {
    if (!is_array($settings) || empty($settings['enhanced_measurements']) || !is_array($settings['enhanced_measurements'])) {
        return $settings;
    }
    $settings['enhanced_measurements'] = array_values(
        array_diff($settings['enhanced_measurements'], array('form-completions'))
    );
    return $settings;
}

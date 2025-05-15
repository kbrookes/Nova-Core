<?php
// Nova Core Settings Page

// Add menu item
add_action('admin_menu', 'nova_core_add_settings_page');
function nova_core_add_settings_page() {
    add_options_page(
        'Nova Core Settings',
        'Nova Core',
        'manage_options',
        'nova-core-settings',
        'nova_core_settings_page'
    );
}

// Get current tracking mode
function nova_core_get_current_tracking_mode() {
    $options = get_option('nova_core_tracking_options');
    $configured_mode = isset($options['tracking_mode']) ? $options['tracking_mode'] : 'auto';
    
    if ($configured_mode === 'auto') {
        // Check for Zaraz using a more reliable method
        $script = wp_scripts()->query('nova-tracking');
        if ($script && $script->done) {
            // If our tracking script has loaded, check if Zaraz was detected
            $inline_script = '';
            foreach ($script->extra['data'] as $data) {
                if (strpos($data, 'window.trackingConfig') !== false) {
                    $inline_script = $data;
                    break;
                }
            }
            if (strpos($inline_script, '"detectedZaraz":true') !== false) {
                return 'zaraz';
            }
        }
        
        // Check for Google Analytics
        if (wp_script_is('gtag', 'registered') || wp_script_is('gtag', 'enqueued')) {
            return 'gtag';
        }
        return 'none';
    }
    
    return $configured_mode;
}

// Register settings
add_action('admin_init', 'nova_core_register_settings');
function nova_core_register_settings() {
    // Register option groups
    register_setting('nova_core_tracking_settings', 'nova_core_tracking_options');
    register_setting('nova_core_features_settings', 'nova_core_features_options');

    // Tracking Settings
    add_settings_section(
        'nova_core_tracking_section',
        'Tracking Settings',
        'nova_core_tracking_section_callback',
        'nova-core-tracking'
    );

    add_settings_field(
        'tracking_mode',
        'Tracking Mode',
        'nova_core_tracking_mode_callback',
        'nova-core-tracking',
        'nova_core_tracking_section'
    );

    // Feature Toggles
    add_settings_section(
        'nova_core_features_section',
        'Feature Toggles',
        'nova_core_features_section_callback',
        'nova-core-features'
    );

    add_settings_field(
        'enable_page_types',
        'Page Types',
        'nova_core_enable_page_types_callback',
        'nova-core-tracking',
        'nova_core_features_section'
    );

    add_settings_field(
        'enable_services',
        'Services',
        'nova_core_enable_services_callback',
        'nova-core-tracking',
        'nova_core_features_section'
    );

    add_settings_field(
        'enable_resources',
        'Resources',
        'nova_core_enable_resources_callback',
        'nova-core-tracking',
        'nova_core_features_section'
    );
}

// Settings page callback
function nova_core_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'tracking';
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="?page=nova-core-settings&tab=tracking" 
               class="nav-tab <?php echo $active_tab == 'tracking' ? 'nav-tab-active' : ''; ?>">
                Tracking
            </a>
            <a href="?page=nova-core-settings&tab=features" 
               class="nav-tab <?php echo $active_tab == 'features' ? 'nav-tab-active' : ''; ?>">
                Features
            </a>
            <a href="?page=nova-core-settings&tab=instructions" 
               class="nav-tab <?php echo $active_tab == 'instructions' ? 'nav-tab-active' : ''; ?>">
                Instructions
            </a>
        </h2>

        <?php if ($active_tab == 'tracking'): ?>
            <form action="options.php" method="post">
                <?php
                settings_fields('nova_core_tracking_settings');
                do_settings_sections('nova-core-tracking');
                submit_button('Save Tracking Settings');
                ?>
            </form>
        <?php elseif ($active_tab == 'features'): ?>
            <form action="options.php" method="post">
                <?php
                settings_fields('nova_core_features_settings');
                do_settings_sections('nova-core-features');
                submit_button('Save Feature Settings');
                ?>
            </form>
        <?php else: ?>
            <div class="nova-core-instructions">
                <h2>Zaraz Configuration</h2>
                
                <h3>Setting Up Tracking Tools</h3>
                <ol>
                    <li>In Cloudflare, go to <strong>Zaraz</strong> > <strong>Tools</strong></li>
                    <li>Click <strong>Add Tool</strong></li>
                    <li>Select <strong>Google Analytics 4</strong> or <strong>Plausible</strong></li>
                    <li>Configure the tool with your tracking ID/domain</li>
                    <li>Repeat for additional tracking tools</li>
                </ol>

                <h3>Excluding Admin Users</h3>
                <p>To prevent tracking of WordPress admin users and logged-in administrators, add a custom filter expression in Cloudflare:</p>
                
                <div class="nova-core-code-block">
                    <pre><code>(http.request.uri contains "wp-login.php") or 
(http.request.uri.path contains "wp-admin") or 
(http.cookie contains "nova_zaraz_logged_in=true")</code></pre>
                </div>

                <p>To implement this filter:</p>
                <ol>
                    <li>In Cloudflare, go to <strong>Zaraz</strong> > <strong>Settings</strong></li>
                    <li>Under <strong>Configuration Rules</strong>, click <strong>Add Rule</strong></li>
                    <li>Name the rule (e.g., "Exclude Admin Users")</li>
                    <li>Paste the filter expression above</li>
                    <li>Set the action to <strong>Disable Zaraz</strong> (this will completely disable Zaraz tracking when the expression is true)</li>
                    <li>Save the rule</li>
                </ol>

                <div class="nova-core-note">
                    <p><strong>Note:</strong> This configuration will prevent tracking of:</p>
                    <ul>
                        <li>WordPress login page visitors</li>
                        <li>WordPress admin area visitors</li>
                        <li>Logged-in administrators (via the <code>nova_zaraz_logged_in</code> cookie)</li>
                    </ul>
                    <p><strong>Important:</strong> Make sure to set the action to "Disable Zaraz" rather than "Block" - this ensures Zaraz is completely disabled for these users rather than just blocking specific events.</p>
                </div>
            </div>

            <style>
                .nova-core-instructions {
                    max-width: 800px;
                    margin: 20px 0;
                }
                .nova-core-code-block {
                    background: #f0f0f1;
                    padding: 15px;
                    border-radius: 4px;
                    margin: 15px 0;
                }
                .nova-core-code-block pre {
                    margin: 0;
                    white-space: pre-wrap;
                }
                .nova-core-note {
                    background: #fff8e5;
                    border-left: 4px solid #ffb900;
                    padding: 12px;
                    margin: 20px 0;
                }
                .nova-core-note ul {
                    margin-left: 20px;
                }
            </style>
        <?php endif; ?>
    </div>
    <?php
}

// Section callbacks
function nova_core_tracking_section_callback() {
    $current_mode = nova_core_get_current_tracking_mode();
    $mode_labels = array(
        'auto' => 'Auto-detect',
        'zaraz' => 'Zaraz',
        'gtag' => 'Google Analytics',
        'none' => 'Disabled'
    );
    $label_color = $current_mode === 'none' ? '#dc3232' : '#46b450';
    ?>
    <p>Configure how tracking is implemented on your site.</p>
    <p>
        <strong>Current Mode:</strong> 
        <span style="display: inline-block; padding: 3px 8px; background: <?php echo esc_attr($label_color); ?>; color: white; border-radius: 3px;">
            <?php echo esc_html($mode_labels[$current_mode]); ?>
        </span>
    </p>
    <?php
}

function nova_core_features_section_callback() {
    echo '<p>Enable or disable Nova Core features.</p>';
}

// Field callbacks
function nova_core_tracking_mode_callback() {
    $options = get_option('nova_core_tracking_options');
    $tracking_mode = isset($options['tracking_mode']) ? $options['tracking_mode'] : 'auto';
    ?>
    <select name="nova_core_tracking_options[tracking_mode]">
        <option value="auto" <?php selected($tracking_mode, 'auto'); ?>>Auto-detect</option>
        <option value="zaraz" <?php selected($tracking_mode, 'zaraz'); ?>>Zaraz</option>
        <option value="gtag" <?php selected($tracking_mode, 'gtag'); ?>>Google Analytics</option>
        <option value="none" <?php selected($tracking_mode, 'none'); ?>>Disabled</option>
    </select>
    <p class="description">Choose how tracking should be implemented. Auto-detect will attempt to use the best available option.</p>
    <?php
}

function nova_core_enable_page_types_callback() {
    $options = get_option('nova_core_features_options');
    $enabled = isset($options['enable_page_types']) ? $options['enable_page_types'] : 0;
    ?>
    <label>
        <input type="checkbox" name="nova_core_features_options[enable_page_types]" value="1" <?php checked($enabled, 1); ?>>
        Enable Page Types custom post type
    </label>
    <?php
}

function nova_core_enable_services_callback() {
    $options = get_option('nova_core_features_options');
    $enabled = isset($options['enable_services']) ? $options['enable_services'] : 0;
    ?>
    <label>
        <input type="checkbox" name="nova_core_features_options[enable_services]" value="1" <?php checked($enabled, 1); ?>>
        Enable Services custom post type
    </label>
    <?php
}

function nova_core_enable_resources_callback() {
    $options = get_option('nova_core_features_options');
    $enabled = isset($options['enable_resources']) ? $options['enable_resources'] : 0;
    ?>
    <label>
        <input type="checkbox" name="nova_core_features_options[enable_resources]" value="1" <?php checked($enabled, 1); ?>>
        Enable Resources custom post type
    </label>
    <?php
}
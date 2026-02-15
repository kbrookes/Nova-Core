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

// Add admin bar warning for development environment
add_action('admin_bar_menu', 'nova_core_admin_bar_env_warning', 999);
function nova_core_admin_bar_env_warning($wp_admin_bar) {
    $options = get_option('nova_core_tracking_options');
    $environment = isset($options['environment']) ? $options['environment'] : 'production';

    if ($environment !== 'production') {
        $wp_admin_bar->add_node(array(
            'id'    => 'nova-env-warning',
            'title' => '<span style="background:#dc3232; color:#fff; padding:0 8px; border-radius:3px; font-weight:bold;">Env: Dev</span>',
            'href'  => admin_url('options-general.php?page=nova-core-settings&tab=tracking'),
            'meta'  => array(
                'title' => 'Nova Core is in Development mode - tracking data will also be logged to console'
            )
        ));
    }
}

// Add CSS for admin bar warning
add_action('admin_head', 'nova_core_admin_bar_styles');
add_action('wp_head', 'nova_core_admin_bar_styles');
function nova_core_admin_bar_styles() {
    $options = get_option('nova_core_tracking_options');
    $environment = isset($options['environment']) ? $options['environment'] : 'production';

    if ($environment !== 'production' && is_admin_bar_showing()) {
        ?>
        <style>
            #wpadminbar #wp-admin-bar-nova-env-warning > .ab-item {
                background: transparent !important;
            }
            #wpadminbar #wp-admin-bar-nova-env-warning:hover > .ab-item span {
                background: #b52727 !important;
            }
        </style>
        <?php
    }
}

// Register settings
add_action('admin_init', 'nova_core_register_settings');
function nova_core_register_settings() {
    // Register option groups
    register_setting('nova_core_tracking_settings', 'nova_core_tracking_options');
    register_setting('nova_core_features_settings', 'nova_core_features_options');
    register_setting('nova_core_blog_settings', 'nova_core_blog_options');

    // Tracking Settings
    add_settings_section(
        'nova_core_tracking_section',
        'Tracking Settings',
        'nova_core_tracking_section_callback',
        'nova-core-tracking'
    );

    add_settings_field(
        'environment',
        'Environment',
        'nova_core_environment_callback',
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
        'nova-core-features',
        'nova_core_features_section'
    );

    add_settings_field(
        'enable_services',
        'Services',
        'nova_core_enable_services_callback',
        'nova-core-features',
        'nova_core_features_section'
    );

    add_settings_field(
        'enable_resources',
        'Resources',
        'nova_core_enable_resources_callback',
        'nova-core-features',
        'nova_core_features_section'
    );

    add_settings_field(
        'enable_case_studies',
        'Case Studies',
        'nova_core_enable_case_studies_callback',
        'nova-core-features',
        'nova_core_features_section'
    );

    add_settings_field(
        'enable_testimonials',
        'Testimonials',
        'nova_core_enable_testimonials_callback',
        'nova-core-features',
        'nova_core_features_section'
    );

    add_settings_field(
        'move_rankmath_metabox',
        'Move RankMath Metabox to Bottom',
        'nova_core_move_rankmath_metabox_callback',
        'nova-core-features',
        'nova_core_features_section'
    );

    // Blog Settings
    add_settings_section(
        'nova_core_blog_section',
        'Blog Settings',
        'nova_core_blog_section_callback',
        'nova-core-blog'
    );

    add_settings_field(
        'blog_posts_per_page',
        'Posts Per Page',
        'nova_core_blog_posts_per_page_callback',
        'nova-core-blog',
        'nova_core_blog_section'
    );

    add_settings_field(
        'blog_excerpt_length',
        'Excerpt Length',
        'nova_core_blog_excerpt_length_callback',
        'nova-core-blog',
        'nova_core_blog_section'
    );

    add_settings_field(
        'blog_show_author',
        'Show Author',
        'nova_core_blog_show_author_callback',
        'nova-core-blog',
        'nova_core_blog_section'
    );

    add_settings_field(
        'blog_show_date',
        'Show Date',
        'nova_core_blog_show_date_callback',
        'nova-core-blog',
        'nova_core_blog_section'
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
            <a href="?page=nova-core-settings&tab=blog" 
               class="nav-tab <?php echo $active_tab == 'blog' ? 'nav-tab-active' : ''; ?>">
                Blog Settings
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
        <?php elseif ($active_tab == 'blog'): ?>
            <form action="options.php" method="post">
                <?php
                settings_fields('nova_core_blog_settings');
                do_settings_sections('nova-core-blog');
                submit_button('Save Blog Settings');
                ?>
            </form>
        <?php else: ?>
            <div class="nova-core-instructions">
                <h2>Tracking Setup</h2>

                <h3>Analytics Backends</h3>
                <p>Nova Core sends tracking events to all available backends:</p>
                <ul>
                    <li><strong>Plausible:</strong> Implemented via the official <a href="https://wordpress.org/plugins/plausible-analytics/" target="_blank">Plausible Analytics WordPress plugin</a></li>
                    <li><strong>Google Analytics:</strong> Implemented via Cloudflare Zaraz</li>
                </ul>

                <h3>Zaraz Configuration (for Google Analytics)</h3>
                <ol>
                    <li>In Cloudflare, go to <strong>Zaraz</strong> > <strong>Tools</strong></li>
                    <li>Click <strong>Add Tool</strong></li>
                    <li>Select <strong>Google Analytics 4</strong></li>
                    <li>Configure the tool with your GA4 Measurement ID</li>
                </ol>

                <h3>Excluding Admin Users</h3>
                <p>To prevent tracking of WordPress admin users and logged-in administrators, add a custom filter expression in Cloudflare:</p>
                
                <div class="nova-core-code-block">
                    <pre><code>(http.request.uri contains "wp-login.php") or 
(http.request.uri.path contains "wp-admin") or 
(http.cookie contains "wordpress_logged_in")</code></pre>
                </div>

                <div class="nova-core-note">
                    <strong>Important:</strong> When creating the rule in Cloudflare, use the "Disable Zaraz" action instead of "Block" to ensure proper functionality.
                </div>

                <h2>Tracking Implementation Guide</h2>

                <h3>Section Tracking</h3>
                <p>To track sections on your page, add a <code>data-name</code> attribute to your section elements:</p>
                <div class="nova-core-code-block">
                    <pre><code>&lt;section data-name="Hero Section"&gt;
    // Section content
&lt;/section&gt;</code></pre>
                </div>
                <p>The section name will be automatically tracked when it comes into view. If no <code>data-name</code> is provided, the system will try to use the section's ID or classes.</p>

                <h3>Click Tracking</h3>
                <p>To track clicks on elements, add a <code>data-click</code> attribute with the event name:</p>
                <div class="nova-core-code-block">
                    <pre><code>&lt;button data-click="Book Consultation"&gt;Book Now&lt;/button&gt;
&lt;a href="#" data-click="Download Guide"&gt;Download Guide&lt;/a&gt;</code></pre>
                </div>
                <p>The click event will be tracked with the following properties:</p>
                <ul>
                    <li>Event Name: The value of <code>data-click</code></li>
                    <li>Section: The parent section where the click occurred</li>
                    <li>Page: The current WordPress page name</li>
                </ul>

                <h3>Fluent Form Tracking</h3>
                <p>To track Fluent Form submissions, add a hidden field to your form with the event name:</p>
                <div class="nova-core-code-block">
                    <pre><code>&lt;input type="hidden" name="ff_event_name" value="Contact Form Submitted"&gt;</code></pre>
                </div>
                <p>The form submission will be tracked with:</p>
                <ul>
                    <li>Event Name: The value of the hidden field</li>
                    <li>Section: The parent section containing the form</li>
                    <li>Page: The current WordPress page name</li>
                </ul>

                <div class="nova-core-note">
                    <strong>Note:</strong> Tracking events are always sent to all available backends (Plausible, Zaraz/GA). In development mode, events are also logged to the browser console for debugging. A red "Env: Dev" warning will appear in the admin bar when not in production mode.
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
    $options = get_option('nova_core_tracking_options');
    $environment = isset($options['environment']) ? $options['environment'] : 'production';

    echo '<p>Configure tracking environment for your site.</p>';
    echo '<p><strong>Note:</strong> Tracking is always enabled and sends data to all available backends (Plausible via plugin, Google Analytics via Zaraz).</p>';

    if ($environment !== 'production') {
        echo '<p style="color: #dc3232; font-weight: bold;">⚠️ Development mode is active - events will also be logged to the browser console.</p>';
    }
}

function nova_core_features_section_callback() {
    echo '<p>Enable or disable Nova Core features.</p>';
}

// Field callbacks
function nova_core_environment_callback() {
    $options = get_option('nova_core_tracking_options');
    $environment = isset($options['environment']) ? $options['environment'] : 'production';
    ?>
    <div class="nova-core-tracking-settings">
        <div class="nova-core-setting-row">
            <select name="nova_core_tracking_options[environment]">
                <option value="production" <?php selected($environment, 'production'); ?>>Production</option>
                <option value="development" <?php selected($environment, 'development'); ?>>Development</option>
            </select>
            <p class="description">
                <strong>Production:</strong> Events are sent to tracking backends only.<br>
                <strong>Development:</strong> Events are sent to tracking backends AND logged to browser console for debugging.
            </p>
        </div>
    </div>

    <style>
        .nova-core-tracking-settings {
            max-width: 600px;
        }
        .nova-core-setting-row {
            margin-bottom: 20px;
        }
        .nova-core-setting-row select {
            margin-bottom: 10px;
        }
    </style>
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

function nova_core_enable_case_studies_callback() {
    $options = get_option('nova_core_features_options');
    $enabled = isset($options['enable_case_studies']) ? $options['enable_case_studies'] : false;
    ?>
    <input type="checkbox" name="nova_core_features_options[enable_case_studies]" value="1" <?php checked(1, $enabled); ?> />
    <p class="description">Enable the Case Studies custom post type.</p>
    <?php
}

function nova_core_enable_testimonials_callback() {
    $options = get_option('nova_core_features_options');
    $enabled = isset($options['enable_testimonials']) ? $options['enable_testimonials'] : false;
    ?>
    <input type="checkbox" name="nova_core_features_options[enable_testimonials]" value="1" <?php checked(1, $enabled); ?> />
    <p class="description">Enable the Testimonials custom post type.</p>
    <?php
}

function nova_core_move_rankmath_metabox_callback() {
    $options = get_option('nova_core_features_options');
    $move_metabox = isset($options['move_rankmath_metabox']) ? $options['move_rankmath_metabox'] : 0;
    ?>
    <label>
        <input type="checkbox" name="nova_core_features_options[move_rankmath_metabox]" value="1" <?php checked(1, $move_metabox); ?>>
        Move RankMath metabox to the bottom of the content area for custom post types
    </label>
    <p class="description">When enabled, the RankMath SEO metabox will appear below the content editor for all custom post types (excluding posts and pages).</p>
    <?php
}

// Add new callback functions at the end of the file
function nova_core_blog_section_callback() {
    echo '<p>Configure your blog display settings.</p>';
}

function nova_core_blog_posts_per_page_callback() {
    $options = get_option('nova_core_blog_options');
    $value = isset($options['posts_per_page']) ? $options['posts_per_page'] : 10;
    ?>
    <input type="number" name="nova_core_blog_options[posts_per_page]" value="<?php echo esc_attr($value); ?>" min="1" max="100" />
    <p class="description">Number of posts to display per page on the blog archive.</p>
    <?php
}

function nova_core_blog_excerpt_length_callback() {
    $options = get_option('nova_core_blog_options');
    $value = isset($options['excerpt_length']) ? $options['excerpt_length'] : 55;
    ?>
    <input type="number" name="nova_core_blog_options[excerpt_length]" value="<?php echo esc_attr($value); ?>" min="10" max="200" />
    <p class="description">Number of words to show in post excerpts.</p>
    <?php
}

function nova_core_blog_show_author_callback() {
    $options = get_option('nova_core_blog_options');
    $value = isset($options['show_author']) ? $options['show_author'] : 1;
    ?>
    <input type="checkbox" name="nova_core_blog_options[show_author]" value="1" <?php checked(1, $value); ?> />
    <p class="description">Display the author name on blog posts.</p>
    <?php
}

function nova_core_blog_show_date_callback() {
    $options = get_option('nova_core_blog_options');
    $value = isset($options['show_date']) ? $options['show_date'] : 1;
    ?>
    <input type="checkbox" name="nova_core_blog_options[show_date]" value="1" <?php checked(1, $value); ?> />
    <p class="description">Display the post date on blog posts.</p>
    <?php
}
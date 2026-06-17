<?php
// Nova Core Settings Page

/**
 * Build Stage Helper Functions
 */

/**
 * Get all available build stages with labels
 *
 * @return array Associative array of stage => label pairs
 */
function nova_core_get_build_stages() {
    return array(
        'content'         => 'Content',
        'wireframe'       => 'Wireframe',
        'wireframe_review' => 'Wireframe Review',
        'design'          => 'Design',
        'design_review'   => 'Design Review',
        'approved'        => 'Approved',
        'maintenance'     => 'Maintenance',
    );
}

/**
 * Get the current build stage
 *
 * @return string The current build stage key, defaults to 'content'
 */
function nova_core_get_build_stage() {
    $options = get_option('nova_core_tracking_options', array());
    $stage = isset($options['build_stage']) ? $options['build_stage'] : 'content';
    $valid_stages = array_keys(nova_core_get_build_stages());
    return in_array($stage, $valid_stages, true) ? $stage : 'content';
}

/**
 * Get the human-readable label for a build stage
 *
 * @param string|null $stage The stage key (uses current stage if null)
 * @return string The human-readable label
 */
function nova_core_get_build_stage_label($stage = null) {
    if ($stage === null) {
        $stage = nova_core_get_build_stage();
    }
    $stages = nova_core_get_build_stages();
    return isset($stages[$stage]) ? $stages[$stage] : 'Content';
}

/**
 * Get the compact admin bar badge label for a build stage
 *
 * @param string|null $stage The stage key (uses current stage if null)
 * @return string The compact badge label
 */
function nova_core_get_build_stage_badge_label($stage = null) {
    if ($stage === null) {
        $stage = nova_core_get_build_stage();
    }
    $badge_labels = array(
        'content'         => 'CONTENT',
        'wireframe'       => 'WIREFRAME',
        'wireframe_review' => 'WF REVIEW',
        'design'          => 'DESIGN',
        'design_review'   => 'DESIGN REVIEW',
        'approved'        => 'APPROVED',
        'maintenance'     => 'MAINT',
    );
    return isset($badge_labels[$stage]) ? $badge_labels[$stage] : 'CONTENT';
}

/**
 * Get the colour for a build stage badge
 *
 * @param string|null $stage The stage key (uses current stage if null)
 * @return string The hex colour code
 */
function nova_core_get_build_stage_colour($stage = null) {
    if ($stage === null) {
        $stage = nova_core_get_build_stage();
    }
    $colours = array(
        'content'         => '#50575e',
        'wireframe'       => '#2271b1',
        'wireframe_review' => '#72aee6',
        'design'          => '#7e3af2',
        'design_review'   => '#a78bfa',
        'approved'        => '#00a32a',
        'maintenance'     => '#b59500',
    );
    return isset($colours[$stage]) ? $colours[$stage] : '#50575e';
}

/**
 * Register REST API endpoint for site context
 */
add_action('rest_api_init', 'nova_core_register_rest_routes');
function nova_core_register_rest_routes() {
    register_rest_route('nova-core/v1', '/site-context', array(
        'methods'             => 'GET',
        'callback'            => 'nova_core_rest_site_context',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ));
}

/**
 * REST endpoint callback for site context
 *
 * @return WP_REST_Response
 */
function nova_core_rest_site_context() {
    $options = get_option('nova_core_tracking_options', array());
    $is_production = isset($options['environment']) && $options['environment'] === 'production';
    $search_visible = isset($options['search_visibility']) ? (bool) $options['search_visibility'] : false;
    $ai_visible = isset($options['ai_visibility']) ? (bool) $options['ai_visibility'] : false;
    $build_stage = nova_core_get_build_stage();

    $response = array(
        'environment'        => $is_production ? 'production' : 'development',
        'build_stage'        => $build_stage,
        'build_stage_label'  => nova_core_get_build_stage_label($build_stage),
        'search_visibility'  => $search_visible,
        'ai_visibility'      => $ai_visible,
        'tracking_mode'      => $is_production ? 'live' : 'debug',
        'wireframe_palette'  => array(
            'background'   => 'var(--base)',
            'section_alt'  => 'var(--base-ultra-light)',
            'card'         => 'var(--base-light)',
            'border'       => 'var(--base-medium)',
            'text'         => 'var(--base-dark)',
            'heading'      => 'var(--base-ultra-dark)',
        ),
        'brand_palette'      => array(
            'primary'   => 'var(--primary)',
            'secondary' => 'var(--secondary)',
            'accent'    => 'var(--accent)',
            'black'     => 'var(--black)',
            'white'     => 'var(--white)',
        ),
    );

    return rest_ensure_response($response);
}

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
add_action('admin_bar_menu', 'nova_core_admin_bar_status', 999);
function nova_core_admin_bar_status($wp_admin_bar) {
    $options = get_option('nova_core_tracking_options', array());
    $is_production = isset($options['environment']) && $options['environment'] === 'production';
    $search_visible = isset($options['search_visibility']) ? (bool) $options['search_visibility'] : false;
    $ai_visible = isset($options['ai_visibility']) ? (bool) $options['ai_visibility'] : false;
    $build_stage = nova_core_get_build_stage();
    $build_stage_badge = nova_core_get_build_stage_badge_label($build_stage);
    $build_stage_label = nova_core_get_build_stage_label($build_stage);
    $build_stage_colour = nova_core_get_build_stage_colour($build_stage);

    // Build status indicators
    $warnings = array();
    $title_parts = array();

    if (!$is_production) {
        $warnings[] = 'Development Mode';
        $title_parts[] = '<span style="background:#dc3232; color:#fff; padding:0 6px; border-radius:3px; font-weight:bold; margin-right:4px;">DEV</span>';
    }

    if (!$search_visible) {
        $warnings[] = 'Search Engines Blocked';
        $title_parts[] = '<span style="background:#dc3232; color:#fff; padding:0 6px; border-radius:3px; font-weight:bold; margin-right:4px;">NOINDEX</span>';
    }

    if (!$ai_visible && $is_production && $search_visible) {
        // Only show AI warning if other things are OK - less critical
        $warnings[] = 'AI Crawlers Blocked';
        $title_parts[] = '<span style="background:#b59500; color:#fff; padding:0 6px; border-radius:3px; font-weight:bold; margin-right:4px;">NO AI</span>';
    }

    // Always show build stage badge
    $warnings[] = 'Build Stage: ' . $build_stage_label;
    $title_parts[] = '<span style="background:' . esc_attr($build_stage_colour) . '; color:#fff; padding:0 6px; border-radius:3px; font-weight:bold; margin-right:4px;">' . esc_html($build_stage_badge) . '</span>';

    // Always show the admin bar node with build stage (at minimum)
    $wp_admin_bar->add_node(array(
        'id'    => 'nova-site-status',
        'title' => implode('', $title_parts),
        'href'  => admin_url('options-general.php?page=nova-core-settings&tab=tracking'),
        'meta'  => array(
            'title' => 'Nova Core Status: ' . implode(', ', $warnings)
        )
    ));
}

// Add CSS for admin bar status indicators
add_action('admin_head', 'nova_core_admin_bar_styles');
add_action('wp_head', 'nova_core_admin_bar_styles');
function nova_core_admin_bar_styles() {
    if (!is_admin_bar_showing()) {
        return;
    }
    ?>
    <style>
        #wpadminbar #wp-admin-bar-nova-site-status > .ab-item {
            background: transparent !important;
            padding-right: 0 !important;
        }
        #wpadminbar #wp-admin-bar-nova-site-status > .ab-item span {
            font-size: 11px;
            line-height: 20px;
            display: inline-block;
        }
        #wpadminbar #wp-admin-bar-nova-site-status:hover > .ab-item span {
            opacity: 0.85;
        }
    </style>
    <?php
}

// Emit the shared Nova Core toggle CSS on every tab of the settings screen.
// Previously the toggle styles only existed inside the Site Status tab's
// inline <style> block, so toggles on other tabs (e.g. Schema) rendered as
// raw checkboxes. Scoped to the settings page only via get_current_screen().
add_action('admin_head', 'nova_core_settings_toggle_styles');
function nova_core_settings_toggle_styles() {
    if (!function_exists('get_current_screen')) {
        return;
    }
    $screen = get_current_screen();
    if (!$screen || strpos((string) $screen->id, 'nova-core-settings') === false) {
        return;
    }
    ?>
    <style>
        .nova-toggle {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }
        .nova-toggle input[type="checkbox"] {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
        }
        .nova-toggle-slider {
            display: inline-flex;
            width: 120px;
            height: 34px;
            background: #dc3232;
            border-radius: 17px;
            position: relative;
            transition: background 0.3s ease;
        }
        .nova-toggle input:checked + .nova-toggle-slider {
            background: #00a32a;
        }
        .nova-toggle-slider::before {
            content: '';
            position: absolute;
            width: 26px;
            height: 26px;
            left: 4px;
            top: 4px;
            background: white;
            border-radius: 50%;
            transition: transform 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .nova-toggle input:checked + .nova-toggle-slider::before {
            transform: translateX(86px);
        }
        .nova-toggle-on,
        .nova-toggle-off {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            transition: opacity 0.3s ease;
        }
        .nova-toggle-on { left: 12px; opacity: 0; }
        .nova-toggle-off { right: 12px; opacity: 1; }
        .nova-toggle input:checked + .nova-toggle-slider .nova-toggle-on { opacity: 1; }
        .nova-toggle input:checked + .nova-toggle-slider .nova-toggle-off { opacity: 0; }
    </style>
    <?php
}

// Register settings
add_action('admin_init', 'nova_core_register_settings');
function nova_core_register_settings() {
    // Register option groups
    register_setting('nova_core_tracking_settings', 'nova_core_tracking_options', array(
        'sanitize_callback' => 'nova_core_sanitize_tracking_options',
    ));
    register_setting('nova_core_features_settings', 'nova_core_features_options');
    register_setting('nova_core_blog_settings', 'nova_core_blog_options');

    // Site Status Settings (formerly Tracking)
    add_settings_section(
        'nova_core_tracking_section',
        'Site Status',
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

    add_settings_field(
        'build_stage',
        'Build Stage',
        'nova_core_build_stage_callback',
        'nova-core-tracking',
        'nova_core_tracking_section'
    );

    add_settings_field(
        'search_visibility',
        'Search Engine Visibility',
        'nova_core_search_visibility_callback',
        'nova-core-tracking',
        'nova_core_tracking_section'
    );

    add_settings_field(
        'ai_visibility',
        'AI Crawler Visibility',
        'nova_core_ai_visibility_callback',
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
        'enable_resources',
        'Resources',
        'nova_core_enable_resources_callback',
        'nova-core-features',
        'nova_core_features_section'
    );

    add_settings_field(
        'enable_video_embeds',
        'Video Embeds',
        'nova_core_enable_video_embeds_callback',
        'nova-core-features',
        'nova_core_features_section'
    );

    add_settings_field(
        'enable_taxonomy_images',
        'Taxonomy Images',
        'nova_core_enable_taxonomy_images_callback',
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
        'Blog Post Options',
        'nova_core_blog_section_callback',
        'nova-core-blog'
    );

    add_settings_field(
        'enable_featured_post',
        'Enable featured post?',
        'nova_core_enable_featured_post_callback',
        'nova-core-blog',
        'nova_core_blog_section'
    );

    add_settings_field(
        'enable_link_to_product',
        'Enable link to product?',
        'nova_core_enable_link_to_product_callback',
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
                Site Status
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
            <a href="?page=nova-core-settings&tab=schema"
               class="nav-tab <?php echo $active_tab == 'schema' ? 'nav-tab-active' : ''; ?>">
                Schema
            </a>
        </h2>

        <?php if ($active_tab == 'tracking'): ?>
            <form action="options.php" method="post">
                <?php
                settings_fields('nova_core_tracking_settings');
                do_settings_sections('nova-core-tracking');
                submit_button('Save Settings');
                ?>
            </form>

            <style>
                /* Toggle switch styles */
                .nova-toggle {
                    position: relative;
                    display: inline-block;
                    cursor: pointer;
                }
                .nova-toggle input[type="checkbox"] {
                    opacity: 0;
                    width: 0;
                    height: 0;
                    position: absolute;
                }
                .nova-toggle-slider {
                    display: inline-flex;
                    width: 120px;
                    height: 34px;
                    background: #dc3232;
                    border-radius: 17px;
                    position: relative;
                    transition: background 0.3s ease;
                }
                .nova-toggle input:checked + .nova-toggle-slider {
                    background: #00a32a;
                }
                .nova-toggle-slider::before {
                    content: '';
                    position: absolute;
                    width: 26px;
                    height: 26px;
                    left: 4px;
                    top: 4px;
                    background: white;
                    border-radius: 50%;
                    transition: transform 0.3s ease;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                }
                .nova-toggle input:checked + .nova-toggle-slider::before {
                    transform: translateX(86px);
                }
                .nova-toggle-on,
                .nova-toggle-off {
                    position: absolute;
                    top: 50%;
                    transform: translateY(-50%);
                    font-size: 12px;
                    font-weight: 600;
                    color: white;
                    text-transform: uppercase;
                    transition: opacity 0.3s ease;
                }
                .nova-toggle-on {
                    left: 12px;
                    opacity: 0;
                }
                .nova-toggle-off {
                    right: 12px;
                    opacity: 1;
                }
                .nova-toggle input:checked + .nova-toggle-slider .nova-toggle-on {
                    opacity: 1;
                }
                .nova-toggle input:checked + .nova-toggle-slider .nova-toggle-off {
                    opacity: 0;
                }
                .form-table td .description {
                    margin-top: 12px;
                }
            </style>
        <?php elseif ($active_tab == 'features'): ?>
            <div class="nova-features-settings-wrap">
                <div class="nova-features-settings-main">
                    <form action="options.php" method="post">
                        <?php
                        settings_fields('nova_core_features_settings');
                        do_settings_sections('nova-core-features');
                        submit_button('Save Feature Settings');
                        ?>
                    </form>
                </div>
                <div class="nova-features-settings-sidebar">
                    <?php nova_core_render_taxonomy_images_docs(); ?>
                    <?php nova_core_render_video_embeds_docs(); ?>
                </div>
            </div>

            <style>
                .nova-features-settings-wrap {
                    display: flex;
                    gap: 30px;
                    align-items: flex-start;
                    margin-top: 20px;
                }
                .nova-features-settings-main {
                    flex: 1;
                    max-width: 600px;
                }
                .nova-features-settings-sidebar {
                    width: 380px;
                    flex-shrink: 0;
                }
                @media screen and (max-width: 1200px) {
                    .nova-features-settings-wrap {
                        flex-direction: column;
                    }
                    .nova-features-settings-sidebar {
                        width: 100%;
                        max-width: 600px;
                    }
                }
            </style>
        <?php elseif ($active_tab == 'blog'): ?>
            <div class="nova-blog-settings-wrap">
                <div class="nova-blog-settings-main">
                    <form action="options.php" method="post">
                        <?php
                        settings_fields('nova_core_blog_settings');
                        do_settings_sections('nova-core-blog');
                        submit_button('Save Blog Settings');
                        ?>
                    </form>
                </div>
                <div class="nova-blog-settings-sidebar">
                    <div class="nova-meta-reference">
                        <h3>Meta Key Reference</h3>
                        <p class="description">Use these meta keys in Bricks Builder queries and dynamic data.</p>

                        <div class="nova-meta-item">
                            <h4>Featured Post</h4>
                            <table class="nova-meta-table">
                                <tr>
                                    <th>Meta Key</th>
                                    <td><code>featured_post</code></td>
                                </tr>
                                <tr>
                                    <th>Type</th>
                                    <td>String</td>
                                </tr>
                                <tr>
                                    <th>Values</th>
                                    <td><code>true</code> | <code>false</code></td>
                                </tr>
                            </table>
                            <p class="nova-meta-example">
                                <strong>Bricks Query:</strong><br>
                                <code>meta_key: featured_post</code><br>
                                <code>meta_value: true</code>
                            </p>
                        </div>

                        <div class="nova-meta-item">
                            <h4>Link to Product</h4>
                            <table class="nova-meta-table">
                                <tr>
                                    <th>Meta Key</th>
                                    <td><code>link_to_product</code></td>
                                </tr>
                                <tr>
                                    <th>Type</th>
                                    <td>String (relative URL)</td>
                                </tr>
                                <tr>
                                    <th>Example</th>
                                    <td><code>/product/example-product/</code></td>
                                </tr>
                            </table>
                        </div>

                        <div class="nova-meta-item nova-meta-function">
                            <h4>Bricks Dynamic Tags</h4>
                            <table class="nova-meta-table">
                                <tr>
                                    <th><code>{nova_product_link}</code></th>
                                    <td>Product URL</td>
                                </tr>
                                <tr>
                                    <th><code>{nova_product_image}</code></th>
                                    <td>Product image URL</td>
                                </tr>
                                <tr>
                                    <th><code>{nova_product_price}</code></th>
                                    <td>Formatted price (HTML)</td>
                                </tr>
                                <tr>
                                    <th><code>{nova_product_title}</code></th>
                                    <td>Product title</td>
                                </tr>
                            </table>
                            <p class="nova-meta-example">
                                <strong>In Bricks:</strong> Use <strong>Custom URL</strong> field and enter the tag directly.
                            </p>
                        </div>

                        <div class="nova-meta-item nova-meta-function">
                            <h4>PHP Function</h4>
                            <table class="nova-meta-table">
                                <tr>
                                    <th>Function</th>
                                    <td><code>nova_get_product($return, $post_id, $size)</code></td>
                                </tr>
                                <tr>
                                    <th>$return</th>
                                    <td><code>'link'</code> | <code>'image'</code> | <code>'price'</code> | <code>'title'</code> | <code>'id'</code></td>
                                </tr>
                                <tr>
                                    <th>$size</th>
                                    <td>Image size (for <code>'image'</code> return)</td>
                                </tr>
                            </table>
                            <p class="nova-meta-example">
                                <strong>Examples:</strong><br>
                                <code>{echo:nova_get_product('image')}</code><br>
                                <code>{echo:nova_get_product('price')}</code><br>
                                <code>{echo:nova_get_product('image', null, 'medium')}</code>
                            </p>
                        </div>

                        <div class="nova-meta-item">
                            <h4>Usage Tips</h4>
                            <p class="nova-meta-example" style="border-top: none; padding-top: 0;">
                                <strong>Condition:</strong> Use <code>{nova_product_link}</code> with <code>is not empty</code> to show/hide elements when a product is linked.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                .nova-blog-settings-wrap {
                    display: flex;
                    gap: 30px;
                    align-items: flex-start;
                    margin-top: 20px;
                }
                .nova-blog-settings-main {
                    flex: 1;
                    max-width: 600px;
                }
                .nova-blog-settings-sidebar {
                    width: 320px;
                    flex-shrink: 0;
                }
                .nova-meta-reference {
                    background: #fff;
                    border: 1px solid #c3c4c7;
                    border-radius: 4px;
                    padding: 15px 20px;
                }
                .nova-meta-reference h3 {
                    margin: 0 0 5px 0;
                    padding: 0;
                    font-size: 14px;
                }
                .nova-meta-reference > .description {
                    margin: 0 0 15px 0;
                    font-style: italic;
                }
                .nova-meta-item {
                    background: #f6f7f7;
                    border-radius: 4px;
                    padding: 12px 15px;
                    margin-bottom: 15px;
                }
                .nova-meta-item:last-child {
                    margin-bottom: 0;
                }
                .nova-meta-item h4 {
                    margin: 0 0 10px 0;
                    font-size: 13px;
                    color: #1d2327;
                }
                .nova-meta-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 12px;
                    margin-bottom: 10px;
                }
                .nova-meta-table th {
                    text-align: left;
                    width: 70px;
                    padding: 4px 0;
                    color: #646970;
                    font-weight: normal;
                }
                .nova-meta-table td {
                    padding: 4px 0;
                }
                .nova-meta-table code {
                    background: #fff;
                    padding: 2px 6px;
                    border-radius: 3px;
                    font-size: 11px;
                }
                .nova-meta-example {
                    font-size: 11px;
                    color: #646970;
                    margin: 0;
                    padding-top: 8px;
                    border-top: 1px solid #dcdcde;
                }
                .nova-meta-example code {
                    background: #fff;
                    padding: 1px 5px;
                    border-radius: 3px;
                    font-size: 11px;
                }
                @media screen and (max-width: 1200px) {
                    .nova-blog-settings-wrap {
                        flex-direction: column;
                    }
                    .nova-blog-settings-sidebar {
                        width: 100%;
                        max-width: 600px;
                    }
                }
            </style>
        <?php elseif ($active_tab == 'schema'): ?>
            <?php
            if (function_exists('nova_core_schema_settings_render')) {
                nova_core_schema_settings_render();
            } else {
                echo '<p>Nova Schema module is unavailable.</p>';
            }
            ?>
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

/**
 * Sanitize tracking options
 *
 * @param array $input The input values
 * @return array Sanitized values
 */
function nova_core_sanitize_tracking_options($input) {
    $sanitized = array();

    // Preserve existing values for keys that aren't being submitted
    $existing = get_option('nova_core_tracking_options', array());

    // Environment: only 'development' or 'production'
    if (isset($input['environment'])) {
        $sanitized['environment'] = ($input['environment'] === 'production') ? 'production' : 'development';
    } elseif (isset($existing['environment'])) {
        $sanitized['environment'] = $existing['environment'];
    } else {
        $sanitized['environment'] = 'development';
    }

    // Search visibility: 1 or 0
    if (isset($input['search_visibility'])) {
        $sanitized['search_visibility'] = $input['search_visibility'] ? 1 : 0;
    } elseif (isset($existing['search_visibility'])) {
        $sanitized['search_visibility'] = $existing['search_visibility'];
    } else {
        $sanitized['search_visibility'] = 0;
    }

    // AI visibility: 1 or 0
    if (isset($input['ai_visibility'])) {
        $sanitized['ai_visibility'] = $input['ai_visibility'] ? 1 : 0;
    } elseif (isset($existing['ai_visibility'])) {
        $sanitized['ai_visibility'] = $existing['ai_visibility'];
    } else {
        $sanitized['ai_visibility'] = 0;
    }

    // Build stage: must be one of the allowed values
    $valid_stages = array_keys(nova_core_get_build_stages());
    if (isset($input['build_stage']) && in_array($input['build_stage'], $valid_stages, true)) {
        $sanitized['build_stage'] = $input['build_stage'];
    } elseif (isset($existing['build_stage']) && in_array($existing['build_stage'], $valid_stages, true)) {
        $sanitized['build_stage'] = $existing['build_stage'];
    } else {
        $sanitized['build_stage'] = 'content';
    }

    return $sanitized;
}

// Section callbacks
function nova_core_tracking_section_callback() {
    $options = get_option('nova_core_tracking_options');
    $is_production = isset($options['environment']) && $options['environment'] === 'production';
    $search_visible = isset($options['search_visibility']) ? $options['search_visibility'] : 0;
    $ai_visible = isset($options['ai_visibility']) ? $options['ai_visibility'] : 0;
    $build_stage = nova_core_get_build_stage();
    $build_stage_label = nova_core_get_build_stage_label($build_stage);
    $build_stage_colour = nova_core_get_build_stage_colour($build_stage);

    echo '<p>Control your site\'s environment and visibility settings from one place. Nova Core manages your robots.txt automatically based on these settings.</p>';

    // Status summary
    $status_items = array();
    if (!$is_production) {
        $status_items[] = '<span style="color: #dc3232;">Development Mode</span>';
    }
    if (!$search_visible) {
        $status_items[] = '<span style="color: #dc3232;">Search Engines Blocked</span>';
    }
    if (!$ai_visible) {
        $status_items[] = '<span style="color: #b59500;">AI Crawlers Blocked</span>';
    }

    // Always show build stage
    $status_items[] = '<span style="color: ' . esc_attr($build_stage_colour) . ';">Build Stage: ' . esc_html($build_stage_label) . '</span>';

    if (count($status_items) === 1) {
        // Only build stage shown - no warnings
        echo '<p style="color: #00a32a; font-weight: bold;">✓ Site is live and fully visible · ' . $status_items[0] . '</p>';
    } else {
        echo '<p style="font-weight: bold;">Current Status: ' . implode(' · ', $status_items) . '</p>';
    }
}

function nova_core_features_section_callback() {
    echo '<p>Enable or disable Nova Core features.</p>';
}

// Field callbacks
function nova_core_environment_callback() {
    $options = get_option('nova_core_tracking_options');
    $is_production = isset($options['environment']) && $options['environment'] === 'production';
    ?>
    <label class="nova-toggle">
        <input type="hidden" name="nova_core_tracking_options[environment]" value="development">
        <input type="checkbox"
               name="nova_core_tracking_options[environment]"
               value="production"
               <?php checked($is_production); ?>>
        <span class="nova-toggle-slider">
            <span class="nova-toggle-on">Live</span>
            <span class="nova-toggle-off">Dev</span>
        </span>
    </label>
    <p class="description">
        <strong>Live (Production):</strong> Site is in production mode. Tracking events sent to backends only.<br>
        <strong>Dev (Development):</strong> Site is in development mode. Tracking events also logged to browser console. Admin bar shows warning.
    </p>
    <?php
}

function nova_core_build_stage_callback() {
    $current_stage = nova_core_get_build_stage();
    $stages = nova_core_get_build_stages();
    ?>
    <select name="nova_core_tracking_options[build_stage]" id="nova_core_build_stage">
        <?php foreach ($stages as $value => $label) : ?>
            <option value="<?php echo esc_attr($value); ?>" <?php selected($current_stage, $value); ?>>
                <?php echo esc_html($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description">
        Use this to tell Nova Core, AI tools and collaborators what stage the site is in. This does not control indexing or tracking. Environment still controls development/production behaviour.
    </p>
    <?php
}

function nova_core_search_visibility_callback() {
    $options = get_option('nova_core_tracking_options');
    $search_visible = isset($options['search_visibility']) ? $options['search_visibility'] : 0;
    ?>
    <label class="nova-toggle">
        <input type="hidden" name="nova_core_tracking_options[search_visibility]" value="0">
        <input type="checkbox"
               name="nova_core_tracking_options[search_visibility]"
               value="1"
               <?php checked(1, $search_visible); ?>>
        <span class="nova-toggle-slider">
            <span class="nova-toggle-on">Visible</span>
            <span class="nova-toggle-off">Hidden</span>
        </span>
    </label>
    <p class="description">
        <strong>Visible:</strong> Search engines (Google, Bing, etc.) can index your site.<br>
        <strong>Hidden:</strong> Search engines are blocked via robots.txt. Overrides WordPress Settings > Reading.
    </p>
    <?php
}

function nova_core_ai_visibility_callback() {
    $options = get_option('nova_core_tracking_options');
    $ai_visible = isset($options['ai_visibility']) ? $options['ai_visibility'] : 0;
    ?>
    <label class="nova-toggle">
        <input type="hidden" name="nova_core_tracking_options[ai_visibility]" value="0">
        <input type="checkbox"
               name="nova_core_tracking_options[ai_visibility]"
               value="1"
               <?php checked(1, $ai_visible); ?>>
        <span class="nova-toggle-slider">
            <span class="nova-toggle-on">Visible</span>
            <span class="nova-toggle-off">Hidden</span>
        </span>
    </label>
    <p class="description">
        <strong>Visible:</strong> AI crawlers (GPTBot, Claude, Perplexity, etc.) can access your content for AI search results.<br>
        <strong>Hidden:</strong> AI crawlers are blocked via robots.txt.
    </p>
    <p class="description" style="margin-top: 10px; padding: 10px; background: #f0f6fc; border-left: 4px solid #2271b1;">
        <strong>Tip:</strong> Enabling AI visibility can improve your presence in AI-powered search engines and chatbots (ChatGPT, Claude, Perplexity, Google AI Overviews).
    </p>
    <?php
}

/**
 * Shared renderer for a Features-tab nova-toggle. Mirrors the markup used by
 * the Site Status tab so all toggles look identical across tabs.
 */
function nova_core_render_features_toggle($key, $description, $default = 0) {
    $options = get_option('nova_core_features_options');
    $value   = isset($options[$key]) ? (int) $options[$key] : (int) $default;
    $name    = 'nova_core_features_options[' . $key . ']';
    ?>
    <label class="nova-toggle">
        <input type="hidden" name="<?php echo esc_attr($name); ?>" value="0">
        <input type="checkbox"
               name="<?php echo esc_attr($name); ?>"
               value="1"
               <?php checked(1, $value); ?>>
        <span class="nova-toggle-slider">
            <span class="nova-toggle-on">On</span>
            <span class="nova-toggle-off">Off</span>
        </span>
    </label>
    <?php if ($description) : ?>
        <p class="description"><?php echo wp_kses_post($description); ?></p>
    <?php endif;
}

function nova_core_enable_resources_callback() {
    nova_core_render_features_toggle('enable_resources', 'Enable the Resources custom post type.');
}

function nova_core_enable_case_studies_callback() {
    nova_core_render_features_toggle('enable_case_studies', 'Enable the Case Studies custom post type.');
}

function nova_core_enable_testimonials_callback() {
    nova_core_render_features_toggle('enable_testimonials', 'Enable the Testimonials custom post type.');
}

function nova_core_move_rankmath_metabox_callback() {
    nova_core_render_features_toggle(
        'move_rankmath_metabox',
        'When enabled, the RankMath SEO metabox will appear below the content editor for all custom post types (excluding posts and pages).'
    );
}

function nova_core_enable_video_embeds_callback() {
    nova_core_render_features_toggle(
        'enable_video_embeds',
        'Provides <code>nova_get_video()</code> function and Bricks dynamic tags for video URLs and thumbnails.'
    );
}

function nova_core_enable_taxonomy_images_callback() {
    nova_core_render_features_toggle(
        'enable_taxonomy_images',
        'Adds image upload to Categories. Use <code>{nova_term_image}</code> in Bricks or <code>nova_get_term_image()</code> function.'
    );
}

// Blog settings callbacks
function nova_core_blog_section_callback() {
    echo '<p>Enable post options that will appear in the Post Options metabox on the post edit screen.</p>';
}

function nova_core_enable_featured_post_callback() {
    $options = get_option('nova_core_blog_options');
    // Default to enabled (1)
    $value = isset($options['enable_featured_post']) ? $options['enable_featured_post'] : 1;
    ?>
    <input type="checkbox" name="nova_core_blog_options[enable_featured_post]" value="1" <?php checked(1, $value); ?> />
    <p class="description">Adds a "Featured post?" toggle to blog posts (meta key: <code>featured_post</code>).</p>
    <?php
}

function nova_core_enable_link_to_product_callback() {
    $options = get_option('nova_core_blog_options');
    // Default to disabled (0)
    $value = isset($options['enable_link_to_product']) ? $options['enable_link_to_product'] : 0;
    ?>
    <input type="checkbox" name="nova_core_blog_options[enable_link_to_product]" value="1" <?php checked(1, $value); ?> />
    <p class="description">Adds a "Link to product" dropdown to blog posts (meta key: <code>link_to_product</code>).</p>
    <?php
}

/**
 * Render the Video Embeds documentation sidebar
 */
function nova_core_render_video_embeds_docs() {
    ?>
    <div class="nova-meta-reference">
        <h3>Video Embeds Reference</h3>
        <p class="description">Helper functions for YouTube and Vimeo videos in Bricks Builder.</p>

        <div class="nova-meta-item">
            <h4>Main Function</h4>
            <table class="nova-meta-table">
                <tr>
                    <th>Function</th>
                    <td><code>nova_get_video($source, $return)</code></td>
                </tr>
                <tr>
                    <th>$source</th>
                    <td>ACF field name <em>or</em> video URL</td>
                </tr>
                <tr>
                    <th>$return</th>
                    <td><code>'url'</code> | <code>'thumbnail'</code></td>
                </tr>
            </table>
        </div>

        <div class="nova-meta-item">
            <h4>Bricks Usage</h4>
            <p class="nova-meta-example" style="border-top: none; padding-top: 0;">
                <strong>With ACF field name:</strong><br>
                <code>{echo:nova_get_video('video_field')}</code><br>
                <code>{echo:nova_get_video('video_field', 'thumbnail')}</code>
            </p>
            <p class="nova-meta-example" style="border-top: none; padding-top: 5px;">
                <strong>With direct URL:</strong><br>
                <code>{echo:nova_get_video('https://youtu.be/abc123')}</code>
            </p>
        </div>

        <div class="nova-meta-item">
            <h4>Dynamic Tags</h4>
            <table class="nova-meta-table">
                <tr>
                    <th>Picker</th>
                    <td>Nova Core &rarr; Video URL / Video Thumbnail</td>
                </tr>
            </table>
            <p class="nova-meta-example">
                Auto-detects ACF fields named: <code>video</code>, <code>video_url</code>, <code>youtube</code>, <code>vimeo</code>
            </p>
        </div>

        <div class="nova-meta-item">
            <h4>Supported URL Formats</h4>
            <p class="nova-meta-example" style="border-top: none; padding-top: 0;">
                <strong>YouTube:</strong><br>
                <code>youtube.com/watch?v=ID</code><br>
                <code>youtu.be/ID</code><br>
                <code>youtube.com/embed/ID</code><br>
                <code>youtube.com/shorts/ID</code>
            </p>
            <p class="nova-meta-example" style="border-top: none; padding-top: 5px;">
                <strong>Vimeo:</strong><br>
                <code>vimeo.com/ID</code><br>
                <code>player.vimeo.com/video/ID</code>
            </p>
        </div>

        <div class="nova-meta-item">
            <h4>Output Examples</h4>
            <table class="nova-meta-table">
                <tr>
                    <th>URL</th>
                    <td><code>https://www.youtube.com/watch?v=abc123</code></td>
                </tr>
                <tr>
                    <th>Thumbnail</th>
                    <td><code>https://img.youtube.com/vi/abc123/maxresdefault.jpg</code></td>
                </tr>
            </table>
        </div>
    </div>
    <?php
}

/**
 * Render the Taxonomy Images documentation sidebar
 */
function nova_core_render_taxonomy_images_docs() {
    ?>
    <div class="nova-meta-reference">
        <h3>Taxonomy Images Reference</h3>
        <p class="description">Add featured images to taxonomy terms (Categories by default).</p>

        <div class="nova-meta-item">
            <h4>Bricks Usage</h4>
            <table class="nova-meta-table">
                <tr>
                    <th>Tag</th>
                    <td><code>{nova_term_image}</code></td>
                </tr>
                <tr>
                    <th>Returns</th>
                    <td>Full image URL</td>
                </tr>
                <tr>
                    <th>How to use</th>
                    <td>Select <strong>Custom URL</strong> (not Dynamic Data picker)</td>
                </tr>
            </table>
            <p class="nova-meta-example">
                Works automatically on category archive templates. Enter the tag directly in the Custom URL field.
            </p>
        </div>

        <div class="nova-meta-item">
            <h4>PHP Function</h4>
            <table class="nova-meta-table">
                <tr>
                    <th>Function</th>
                    <td><code>nova_get_term_image($term_id, $size, $return)</code></td>
                </tr>
                <tr>
                    <th>$term_id</th>
                    <td>Term ID (optional, auto-detects on archives)</td>
                </tr>
                <tr>
                    <th>$size</th>
                    <td><code>'full'</code>, <code>'large'</code>, <code>'medium'</code>, etc.</td>
                </tr>
                <tr>
                    <th>$return</th>
                    <td><code>'url'</code> | <code>'id'</code> | <code>'tag'</code></td>
                </tr>
            </table>
        </div>

        <div class="nova-meta-item">
            <h4>Bricks Usage Examples</h4>
            <p class="nova-meta-example" style="border-top: none; padding-top: 0;">
                <strong>Image URL (full size):</strong><br>
                <code>{nova_term_image}</code>
            </p>
            <p class="nova-meta-example" style="border-top: none; padding-top: 5px;">
                <strong>Specific size:</strong><br>
                <code>{echo:nova_get_term_image(null, 'large')}</code>
            </p>
            <p class="nova-meta-example" style="border-top: none; padding-top: 5px;">
                <strong>Full img tag:</strong><br>
                <code>{echo:nova_get_term_image(null, 'medium', 'tag')}</code>
            </p>
        </div>

        <div class="nova-meta-item">
            <h4>Extending to Other Taxonomies</h4>
            <p class="nova-meta-example" style="border-top: none; padding-top: 0;">
                Add this to your theme's functions.php:<br>
                <code style="font-size: 11px;">add_filter('nova_taxonomy_image_taxonomies', function($taxonomies) {<br>
                &nbsp;&nbsp;$taxonomies[] = 'post_tag';<br>
                &nbsp;&nbsp;return $taxonomies;<br>
                });</code>
            </p>
        </div>
    </div>
    <?php
}
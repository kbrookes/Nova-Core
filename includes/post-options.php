<?php
/**
 * Post Options Metabox
 *
 * Adds a "Post Options" metabox to the post edit screen with:
 * - Featured post toggle (meta_key: featured_post)
 * - Link to product dropdown (meta_key: link_to_product)
 *
 * @package Nova_Core
 */

defined('ABSPATH') || exit;

/**
 * Get the product link URL for the current post
 *
 * Use in Bricks Builder: {echo:nova_get_product_link()}
 *
 * @param int|null $post_id Optional post ID. Defaults to current post.
 * @return string Full product URL or empty string if not set.
 */
function nova_get_product_link($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    $relative_url = get_post_meta($post_id, 'link_to_product', true);

    if (empty($relative_url)) {
        return '';
    }

    return home_url($relative_url);
}

/**
 * Register post meta fields for REST API and Bricks Builder compatibility
 */
add_action('init', 'nova_core_register_post_meta');
function nova_core_register_post_meta() {
    // Register featured_post meta
    register_post_meta('post', 'featured_post', array(
        'type'              => 'string',
        'single'            => true,
        'show_in_rest'      => true,
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback'     => function() {
            return current_user_can('edit_posts');
        },
    ));

    // Register link_to_product meta
    register_post_meta('post', 'link_to_product', array(
        'type'              => 'string',
        'single'            => true,
        'show_in_rest'      => true,
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback'     => function() {
            return current_user_can('edit_posts');
        },
    ));
}

/**
 * Register the Post Options metabox
 */
add_action('add_meta_boxes', 'nova_core_register_post_options_metabox');
function nova_core_register_post_options_metabox() {
    $options = get_option('nova_core_blog_options');
    $featured_enabled = isset($options['enable_featured_post']) ? $options['enable_featured_post'] : 1;
    $product_enabled = isset($options['enable_link_to_product']) ? $options['enable_link_to_product'] : 0;

    // Only add metabox if at least one feature is enabled
    if (!$featured_enabled && !$product_enabled) {
        return;
    }

    add_meta_box(
        'nova_post_options',
        'Post Options',
        'nova_core_post_options_metabox_callback',
        'post',
        'side',
        'high' // High priority to appear near the top
    );
}

/**
 * Render the Post Options metabox
 *
 * @param WP_Post $post Current post object.
 */
function nova_core_post_options_metabox_callback($post) {
    $options = get_option('nova_core_blog_options');
    $featured_enabled = isset($options['enable_featured_post']) ? $options['enable_featured_post'] : 1;
    $product_enabled = isset($options['enable_link_to_product']) ? $options['enable_link_to_product'] : 0;

    // Get current meta values
    $featured_post = get_post_meta($post->ID, 'featured_post', true);
    $link_to_product = get_post_meta($post->ID, 'link_to_product', true);

    // Nonce for security
    wp_nonce_field('nova_post_options_save', 'nova_post_options_nonce');
    ?>
    <div class="nova-post-options">
        <?php if ($featured_enabled) : ?>
            <div class="nova-post-option nova-toggle-field">
                <label class="nova-toggle-label">
                    <strong>Featured post?</strong>
                </label>
                <label class="nova-toggle-switch">
                    <input type="checkbox"
                           name="featured_post"
                           value="true"
                           <?php checked($featured_post, 'true'); ?> />
                    <span class="nova-toggle-slider">
                        <span class="nova-toggle-on">Yes</span>
                        <span class="nova-toggle-off">No</span>
                    </span>
                </label>
            </div>
        <?php endif; ?>

        <?php if ($product_enabled) : ?>
            <p class="nova-post-option">
                <label for="link_to_product"><strong>Link to product</strong></label>
                <select name="link_to_product" 
                        id="link_to_product" 
                        class="nova-product-select"
                        style="width: 100%; margin-top: 5px;">
                    <option value="">— Select a product —</option>
                    <?php
                    // Get all products (WooCommerce)
                    $products = get_posts(array(
                        'post_type'      => 'product',
                        'posts_per_page' => -1,
                        'orderby'        => 'title',
                        'order'          => 'ASC',
                        'post_status'    => 'publish',
                    ));

                    foreach ($products as $product) {
                        $product_url = wp_make_link_relative(get_permalink($product->ID));
                        $selected = ($link_to_product === $product_url) ? 'selected' : '';
                        printf(
                            '<option value="%s" %s>%s</option>',
                            esc_attr($product_url),
                            $selected,
                            esc_html($product->post_title)
                        );
                    }
                    ?>
                </select>
                <span class="description" style="display: block; margin-top: 5px;">
                    Select a product to link this post to.
                </span>
            </p>
        <?php endif; ?>
    </div>

    <style>
        .nova-post-options {
            padding: 5px 0;
        }
        .nova-post-option {
            margin-bottom: 15px;
        }
        .nova-post-option:last-child {
            margin-bottom: 5px;
        }
        /* Select2 overrides for metabox */
        .nova-post-options .select2-container {
            width: 100% !important;
        }

        /* ACF-style toggle switch */
        .nova-toggle-field {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .nova-toggle-label {
            flex: 1;
        }
        .nova-toggle-switch {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }
        .nova-toggle-switch input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        .nova-toggle-slider {
            display: flex;
            width: 70px;
            height: 28px;
            background: #a3b9c8;
            border-radius: 14px;
            position: relative;
            transition: background 0.2s ease;
            overflow: hidden;
        }
        .nova-toggle-slider::before {
            content: '';
            position: absolute;
            top: 3px;
            left: 3px;
            width: 22px;
            height: 22px;
            background: #fff;
            border-radius: 50%;
            transition: transform 0.2s ease;
            z-index: 2;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .nova-toggle-on,
        .nova-toggle-off {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            color: #fff;
            z-index: 1;
            transition: opacity 0.2s ease;
        }
        .nova-toggle-on {
            opacity: 0;
            padding-left: 4px;
        }
        .nova-toggle-off {
            opacity: 1;
            padding-right: 4px;
        }
        /* Checked state */
        .nova-toggle-switch input:checked + .nova-toggle-slider {
            background: #00a0d2;
        }
        .nova-toggle-switch input:checked + .nova-toggle-slider::before {
            transform: translateX(42px);
        }
        .nova-toggle-switch input:checked + .nova-toggle-slider .nova-toggle-on {
            opacity: 1;
        }
        .nova-toggle-switch input:checked + .nova-toggle-slider .nova-toggle-off {
            opacity: 0;
        }
        /* Focus state */
        .nova-toggle-switch input:focus + .nova-toggle-slider {
            box-shadow: 0 0 0 2px #fff, 0 0 0 4px #00a0d2;
        }
    </style>
    <?php
}

/**
 * Enqueue Select2 for searchable product dropdown
 *
 * @param string $hook Current admin page.
 */
add_action('admin_enqueue_scripts', 'nova_core_enqueue_post_options_scripts');
function nova_core_enqueue_post_options_scripts($hook) {
    // Only load on post edit screen
    if (!in_array($hook, array('post.php', 'post-new.php'))) {
        return;
    }

    global $post_type;
    if ($post_type !== 'post') {
        return;
    }

    $options = get_option('nova_core_blog_options');
    $product_enabled = isset($options['enable_link_to_product']) ? $options['enable_link_to_product'] : 0;

    // Only load Select2 if product linking is enabled
    if (!$product_enabled) {
        return;
    }

    // Enqueue Select2 from WordPress (included with WooCommerce) or CDN fallback
    if (wp_script_is('select2', 'registered')) {
        wp_enqueue_script('select2');
        wp_enqueue_style('select2');
    } else {
        wp_enqueue_script(
            'select2',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
            array('jquery'),
            '4.1.0',
            true
        );
        wp_enqueue_style(
            'select2',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
            array(),
            '4.1.0'
        );
    }

    // Initialise Select2 on our dropdown
    wp_add_inline_script('select2', "
        jQuery(document).ready(function($) {
            $('#link_to_product').select2({
                placeholder: '— Select a product —',
                allowClear: true,
                width: '100%'
            });
        });
    ");
}

/**
 * Save post options meta data
 *
 * @param int $post_id Post ID.
 */
add_action('save_post_post', 'nova_core_save_post_options');
function nova_core_save_post_options($post_id) {
    // Verify nonce
    if (!isset($_POST['nova_post_options_nonce']) || 
        !wp_verify_nonce($_POST['nova_post_options_nonce'], 'nova_post_options_save')) {
        return;
    }

    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $options = get_option('nova_core_blog_options');

    // Save featured_post
    if (isset($options['enable_featured_post']) && $options['enable_featured_post']) {
        $featured = isset($_POST['featured_post']) ? 'true' : 'false';
        update_post_meta($post_id, 'featured_post', $featured);
    }

    // Save link_to_product
    if (isset($options['enable_link_to_product']) && $options['enable_link_to_product']) {
        $product_link = isset($_POST['link_to_product']) ? sanitize_text_field($_POST['link_to_product']) : '';
        update_post_meta($post_id, 'link_to_product', $product_link);
    }
}


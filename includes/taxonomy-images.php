<?php
/**
 * Taxonomy Images
 *
 * Adds featured image support to taxonomy terms (categories, tags, custom taxonomies).
 * Compatible with Bricks Builder dynamic data.
 *
 * @package Nova_Core
 */

defined('ABSPATH') || exit;

/**
 * Get the taxonomies that should have image support
 *
 * @return array Array of taxonomy names.
 */
function nova_get_image_taxonomies() {
    $defaults = array('category');
    return apply_filters('nova_taxonomy_image_taxonomies', $defaults);
}

/**
 * Register taxonomy image hooks
 */
add_action('admin_init', 'nova_register_taxonomy_image_hooks');
function nova_register_taxonomy_image_hooks() {
    $taxonomies = nova_get_image_taxonomies();
    
    foreach ($taxonomies as $taxonomy) {
        add_action("{$taxonomy}_add_form_fields", 'nova_taxonomy_image_add_field');
        add_action("{$taxonomy}_edit_form_fields", 'nova_taxonomy_image_edit_field', 10, 2);
        add_action("created_{$taxonomy}", 'nova_taxonomy_image_save');
        add_action("edited_{$taxonomy}", 'nova_taxonomy_image_save');
    }
}

/**
 * Add image field to 'Add New Term' form
 */
function nova_taxonomy_image_add_field($taxonomy) {
    ?>
    <div class="form-field nova-term-image-wrap">
        <label for="nova-term-image">Featured Image</label>
        <div class="nova-term-image-preview"></div>
        <input type="hidden" name="nova_term_image" id="nova-term-image" value="">
        <button type="button" class="button nova-upload-image-btn">Select Image</button>
        <button type="button" class="button nova-remove-image-btn" style="display:none;">Remove Image</button>
        <p class="description">Select a featured image for this term.</p>
    </div>
    <?php
}

/**
 * Add image field to 'Edit Term' form
 */
function nova_taxonomy_image_edit_field($term, $taxonomy) {
    $image_id = get_term_meta($term->term_id, 'nova_term_image_id', true);
    $image_url = '';
    
    // Backwards compatibility: check for old URL-based storage
    if (!$image_id) {
        $legacy_url = get_term_meta($term->term_id, 'term_image', true);
        if ($legacy_url) {
            $image_url = $legacy_url;
            $image_id = attachment_url_to_postid($legacy_url);
        }
    } else {
        $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
    }
    ?>
    <tr class="form-field nova-term-image-wrap">
        <th scope="row"><label for="nova-term-image">Featured Image</label></th>
        <td>
            <div class="nova-term-image-preview">
                <?php if ($image_url): ?>
                    <img src="<?php echo esc_url($image_url); ?>" style="max-width:150px;height:auto;">
                <?php endif; ?>
            </div>
            <input type="hidden" name="nova_term_image" id="nova-term-image" value="<?php echo esc_attr($image_id); ?>">
            <button type="button" class="button nova-upload-image-btn">Select Image</button>
            <button type="button" class="button nova-remove-image-btn" <?php echo $image_id ? '' : 'style="display:none;"'; ?>>Remove Image</button>
            <p class="description">Select a featured image for this term.</p>
        </td>
    </tr>
    <?php
}

/**
 * Save term image
 */
function nova_taxonomy_image_save($term_id) {
    if (isset($_POST['nova_term_image'])) {
        $image_id = absint($_POST['nova_term_image']);
        if ($image_id) {
            update_term_meta($term_id, 'nova_term_image_id', $image_id);
            // Also store URL for backwards compatibility
            $url = wp_get_attachment_image_url($image_id, 'full');
            update_term_meta($term_id, 'term_image', $url);
        } else {
            delete_term_meta($term_id, 'nova_term_image_id');
            delete_term_meta($term_id, 'term_image');
        }
    }
}

/**
 * Enqueue media uploader scripts
 */
add_action('admin_enqueue_scripts', 'nova_taxonomy_image_admin_scripts');
function nova_taxonomy_image_admin_scripts($hook) {
    if (!in_array($hook, array('edit-tags.php', 'term.php'))) {
        return;
    }
    
    $screen = get_current_screen();
    $taxonomies = nova_get_image_taxonomies();
    
    if (!in_array($screen->taxonomy, $taxonomies)) {
        return;
    }
    
    wp_enqueue_media();
    wp_add_inline_script('jquery', nova_taxonomy_image_js());
}

/**
 * Inline JavaScript for media uploader
 */
function nova_taxonomy_image_js() {
    return "
    jQuery(document).ready(function($) {
        var frame;

        $(document).on('click', '.nova-upload-image-btn', function(e) {
            e.preventDefault();
            var btn = $(this);
            var wrap = btn.closest('.nova-term-image-wrap');

            frame = wp.media({
                title: 'Select Term Image',
                button: { text: 'Use this image' },
                multiple: false
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                wrap.find('#nova-term-image').val(attachment.id);
                var imgUrl = attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                wrap.find('.nova-term-image-preview').html('<img src=\"' + imgUrl + '\" style=\"max-width:150px;height:auto;\">');
                wrap.find('.nova-remove-image-btn').show();
            });

            frame.open();
        });

        $(document).on('click', '.nova-remove-image-btn', function(e) {
            e.preventDefault();
            var wrap = $(this).closest('.nova-term-image-wrap');
            wrap.find('#nova-term-image').val('');
            wrap.find('.nova-term-image-preview').html('');
            $(this).hide();
        });

        // Clear form after adding new term (AJAX)
        $(document).ajaxComplete(function(event, xhr, settings) {
            if (settings.data && settings.data.indexOf('action=add-tag') !== -1) {
                $('.nova-term-image-preview').html('');
                $('#nova-term-image').val('');
                $('.nova-remove-image-btn').hide();
            }
        });
    });
    ";
}

/**
 * Get term image
 *
 * @param int|null $term_id Term ID. Defaults to current queried term.
 * @param string $size Image size. Default 'full'.
 * @param string $return What to return: 'url', 'id', or 'tag'. Default 'url'.
 * @return string|int|false The image URL, ID, HTML tag, or false if not found.
 */
function nova_get_term_image($term_id = null, $size = 'full', $return = 'url') {
    if (!$term_id) {
        $term_id = get_queried_object_id();
    }

    if (!$term_id) {
        return false;
    }

    // Try new meta key first
    $image_id = get_term_meta($term_id, 'nova_term_image_id', true);

    // Backwards compatibility
    if (!$image_id) {
        $legacy_url = get_term_meta($term_id, 'term_image', true);
        if ($legacy_url) {
            $image_id = attachment_url_to_postid($legacy_url);
            // If we can't find the ID, return the URL directly for legacy data
            if (!$image_id && $return === 'url') {
                return esc_url($legacy_url);
            }
        }
    }

    if (!$image_id) {
        return false;
    }

    switch ($return) {
        case 'id':
            return (int) $image_id;
        case 'tag':
            return wp_get_attachment_image($image_id, $size);
        case 'url':
        default:
            return wp_get_attachment_image_url($image_id, $size);
    }
}

/**
 * Legacy function for backwards compatibility
 */
function get_term_image_url() {
    return nova_get_term_image(null, 'full', 'url');
}

/**
 * Register Nova term image dynamic data tags with Bricks Builder
 */
add_filter('bricks/dynamic_tags_list', 'nova_core_register_term_image_bricks_tags');
function nova_core_register_term_image_bricks_tags($tags) {
    $tags[] = array(
        'name'  => '{nova_term_image}',
        'label' => 'Term Image URL',
        'group' => 'Nova Core',
    );

    $tags[] = array(
        'name'  => '{nova_term_image_id}',
        'label' => 'Term Image ID',
        'group' => 'Nova Core',
    );

    return $tags;
}

/**
 * Render Nova term image dynamic data tags in Bricks Builder
 */
add_filter('bricks/dynamic_data/render_tag', 'nova_core_render_term_image_bricks_tag', 10, 3);
function nova_core_render_term_image_bricks_tag($tag, $post, $context) {
    if ($tag === 'nova_term_image') {
        $term_id = nova_get_current_term_id($context);
        $url = nova_get_term_image($term_id, 'full', 'url');
        return $url ? $url : '';
    }

    if ($tag === 'nova_term_image_id') {
        $term_id = nova_get_current_term_id($context);
        $id = nova_get_term_image($term_id, 'full', 'id');
        return $id ? $id : '';
    }

    return $tag;
}

/**
 * Handle Nova term image tags within content strings
 */
add_filter('bricks/dynamic_data/render_content', 'nova_core_render_term_image_bricks_content', 10, 3);
function nova_core_render_term_image_bricks_content($content, $post, $context) {
    if (strpos($content, '{nova_term_image}') === false && strpos($content, '{nova_term_image_id}') === false) {
        return $content;
    }

    $term_id = nova_get_current_term_id($context);

    if (strpos($content, '{nova_term_image}') !== false) {
        $url = nova_get_term_image($term_id, 'full', 'url');
        $content = str_replace('{nova_term_image}', $url ? $url : '', $content);
    }

    if (strpos($content, '{nova_term_image_id}') !== false) {
        $id = nova_get_term_image($term_id, 'full', 'id');
        $content = str_replace('{nova_term_image_id}', $id ? $id : '', $content);
    }

    return $content;
}

/**
 * Get current term ID from context
 */
function nova_get_current_term_id($context = null) {
    // Check if we're in a term loop context
    if (is_array($context) && isset($context['term'])) {
        return is_object($context['term']) ? $context['term']->term_id : $context['term'];
    }

    // Check queried object
    $queried = get_queried_object();
    if ($queried instanceof WP_Term) {
        return $queried->term_id;
    }

    return 0;
}


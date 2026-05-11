<?php
// Enqueue the tracking.js script and inject config

function nova_get_page_title() {
    if (is_singular()) {
        return get_the_title();
    } elseif (is_home()) {
        // Check if this is the blog posts page
        if (get_option('show_on_front') === 'page' && get_option('page_for_posts')) {
            return get_the_title(get_option('page_for_posts'));
        }
        return 'Home';
    } elseif (is_archive()) {
        // Get the post type or taxonomy archive label
        if (is_post_type_archive()) {
            $post_type = get_post_type_object(get_post_type());
            return $post_type ? $post_type->labels->name : 'Archive';
        } elseif (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            if ($term) {
                $taxonomy = get_taxonomy($term->taxonomy);
                return $taxonomy ? $taxonomy->labels->name : 'Archive';
            }
        }
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
    $environment = isset($options['environment']) ? $options['environment'] : 'production';

    wp_enqueue_script(
        'nova-tracking',
        plugin_dir_url(__FILE__) . '../assets/js/tracking.js',
        array(),
        '1.0.1',
        true
    );

    // Pass essential settings to JS
    $js_config = array(
        'pageTitle' => nova_get_page_title(),
        'environment' => $environment,
        'buildStage' => function_exists('nova_core_get_build_stage') ? nova_core_get_build_stage() : 'content',
    );

    // Pass settings to JS using wp_localize_script
    wp_localize_script('nova-tracking', 'novaCoreConfig', $js_config);
}

function nova_get_tracking_attributes($element) {
    $attributes = [];
    
    // Check if this element has data-track-inside attribute
    if ($element->hasAttribute('data-track-inside')) {
        // Find all elements with data-name inside this element
        $inner_elements = $element->getElementsByTagName('*');
        foreach ($inner_elements as $inner) {
            if ($inner->hasAttribute('data-name')) {
                $attributes[] = [
                    'name' => $inner->getAttribute('data-name'),
                    'type' => 'section'
                ];
            }
        }
        return $attributes;
    }

    // Original tracking logic for direct attributes
    if ($element->hasAttribute('data-name')) {
        $attributes[] = [
            'name' => $element->getAttribute('data-name'),
            'type' => 'section'
        ];
    }
    
    return $attributes;
}
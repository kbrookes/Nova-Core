<?php
// Handle RankMath metabox position for custom post types

function nova_core_move_rankmath_metabox() {
    $options = get_option('nova_core_features_options');
    if (!isset($options['move_rankmath_metabox']) || !$options['move_rankmath_metabox']) {
        return;
    }

    // Get all registered post types
    $post_types = get_post_types(['public' => true], 'names');
    
    // Remove posts and pages from the list
    unset($post_types['post']);
    unset($post_types['page']);

    // Add filter for each custom post type
    foreach ($post_types as $post_type) {
        add_filter("rank_math/metabox/priority", function($priority, $post_type) {
            return 'low';
        }, 10, 2);
    }
}
add_action('admin_init', 'nova_core_move_rankmath_metabox'); 
<?php
// Registers custom post types for Nova Core.

// Register Case Studies post type
function nova_core_register_case_studies() {
    $options = get_option('nova_core_features_options');
    if (!isset($options['enable_case_studies']) || !$options['enable_case_studies']) {
        return;
    }

    $labels = array(
        'name'                  => 'Case Studies',
        'singular_name'         => 'Case Study',
        'menu_name'            => 'Case Studies',
        'all_items'            => 'All Case Studies',
        'edit_item'            => 'Edit Case Study',
        'view_item'            => 'View Case Study',
        'view_items'           => 'View Case Studies',
        'add_new_item'         => 'Add New Case Study',
        'add_new'              => '',
        'new_item'             => 'New Case Study',
        'parent_item_colon'    => 'Parent Case Study:',
        'search_items'         => 'Search Case Studies',
        'not_found'            => 'No case studies found',
        'not_found_in_trash'   => 'No case studies found in Trash',
        'archives'             => 'Case Study Archives',
        'attributes'           => 'Case Study Attributes',
        'featured_image'       => '',
        'set_featured_image'   => '',
        'remove_featured_image'=> '',
        'use_featured_image'   => '',
        'insert_into_item'     => 'Insert into case study',
        'uploaded_to_this_item'=> 'Uploaded to this case study',
        'filter_items_list'    => 'Filter case studies list',
        'filter_by_date'       => 'Filter case studies by date',
        'items_list_navigation'=> 'Case Studies list navigation',
        'items_list'           => 'Case Studies list',
        'item_published'       => 'Case Study published.',
        'item_published_privately' => 'Case Study published privately.',
        'item_reverted_to_draft'   => 'Case Study reverted to draft.',
        'item_scheduled'       => 'Case Study scheduled.',
        'item_updated'         => 'Case Study updated.',
        'item_link'            => 'Case Study Link',
        'item_link_description'=> 'A link to a case study.'
    );

    $args = array(
        'labels'              => $labels,
        'description'         => '',
        'public'              => true,
        'hierarchical'        => false,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_admin_bar'  => true,
        'show_in_nav_menus'  => true,
        'show_in_rest'       => true,
        'rest_base'          => '',
        'rest_namespace'     => 'wp/v2',
        'rest_controller_class' => 'WP_REST_Posts_Controller',
        'menu_position'      => '',
        'menu_icon'          => 'dashicons-archive',
        'capability_type'    => 'post',
        'supports'           => array('title', 'excerpt', 'thumbnail'),
        'taxonomies'         => array('category', 'post_tag', 'service'),
        'has_archive'        => true,
        'rewrite'            => array(
            'slug'           => 'case-studies',
            'with_front'     => true,
            'feeds'          => false,
            'pages'          => true
        ),
        'query_var'          => true,
        'can_export'         => true,
        'delete_with_user'   => false
    );

    register_post_type('case-studies', $args);
}
add_action('init', 'nova_core_register_case_studies');

// Register Testimonials post type
function nova_core_register_testimonials() {
    $options = get_option('nova_core_features_options');
    if (!isset($options['enable_testimonials']) || !$options['enable_testimonials']) {
        return;
    }

    $labels = array(
        'name'                  => 'Testimonials',
        'singular_name'         => 'Testimonial',
        'menu_name'            => 'Testimonials',
        'all_items'            => 'All Testimonials',
        'edit_item'            => 'Edit Testimonial',
        'view_item'            => 'View Testimonial',
        'view_items'           => 'View Testimonials',
        'add_new_item'         => 'Add New Testimonial',
        'add_new'              => '',
        'new_item'             => 'New Testimonial',
        'parent_item_colon'    => 'Parent Testimonial:',
        'search_items'         => 'Search Testimonials',
        'not_found'            => 'No testimonials found',
        'not_found_in_trash'   => 'No testimonials found in Trash',
        'archives'             => 'Testimonial Archives',
        'attributes'           => 'Testimonial Attributes',
        'featured_image'       => '',
        'set_featured_image'   => '',
        'remove_featured_image'=> '',
        'use_featured_image'   => '',
        'insert_into_item'     => 'Insert into testimonial',
        'uploaded_to_this_item'=> 'Uploaded to this testimonial',
        'filter_items_list'    => 'Filter testimonials list',
        'filter_by_date'       => 'Filter testimonials by date',
        'items_list_navigation'=> 'Testimonials list navigation',
        'items_list'           => 'Testimonials list',
        'item_published'       => 'Testimonial published.',
        'item_published_privately' => 'Testimonial published privately.',
        'item_reverted_to_draft'   => 'Testimonial reverted to draft.',
        'item_scheduled'       => 'Testimonial scheduled.',
        'item_updated'         => 'Testimonial updated.',
        'item_link'            => 'Testimonial Link',
        'item_link_description'=> 'A link to a testimonial.'
    );

    $args = array(
        'labels'              => $labels,
        'description'         => 'Provide as much or as little detail in each testimonial as you require. The title of the testimonial is only used internally and is not shown to site visitors.',
        'public'              => true,
        'hierarchical'        => false,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_admin_bar'  => true,
        'show_in_nav_menus'  => true,
        'show_in_rest'       => true,
        'rest_base'          => '',
        'rest_namespace'     => 'wp/v2',
        'rest_controller_class' => 'WP_REST_Posts_Controller',
        'menu_position'      => '',
        'menu_icon'          => 'dashicons-format-chat',
        'capability_type'    => 'post',
        'supports'           => array('title', 'thumbnail'),
        'taxonomies'         => array('services'),
        'has_archive'        => true,
        'rewrite'            => array(
            'slug'           => 'testimonials',
            'with_front'     => true,
            'feeds'          => false,
            'pages'          => true
        ),
        'query_var'          => true,
        'can_export'         => true,
        'delete_with_user'   => false,
        'enter_title_here'   => 'Add an internal title for the testimonial - it won\'t be visible to site visitors'
    );

    register_post_type('testimonial', $args);
}
add_action('init', 'nova_core_register_testimonials');
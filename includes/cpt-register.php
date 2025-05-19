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
        'supports'           => array('title', 'editor', 'excerpt', 'thumbnail'),
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
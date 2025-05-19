<?php
// Register Site Settings options page and fields

// Add options page
add_action('acf/init', 'nova_core_add_site_settings_page');
function nova_core_add_site_settings_page() {
    acf_add_options_page(array(
        'page_title' => 'Pro Site Settings',
        'menu_slug' => 'pro-site-options',
        'position' => 3,
        'redirect' => false,
        'menu_icon' => array(
            'type' => 'dashicons',
            'value' => 'dashicons-admin-generic',
        ),
        'icon_url' => 'dashicons-admin-generic',
    ));
}

// Register ACF fields
add_action('acf/include_fields', 'nova_core_register_site_settings_fields');
function nova_core_register_site_settings_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key' => 'group_667bf1a8918af',
        'title' => 'Site Settings',
        'fields' => array(
            array(
                'key' => 'field_66f0d566bbf92',
                'label' => 'Your Details',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
                'endpoint' => 0,
                'selected' => 0
            ),
            array(
                'key' => 'field_66f0d57dbbf93',
                'label' => 'Your First Name',
                'name' => 'site_first_name',
                'type' => 'text',
                'wrapper' => array(
                    'width' => '50'
                )
            ),
            array(
                'key' => 'field_66f0d5d0bbf94',
                'label' => 'Your Last Name',
                'name' => 'site_last_name',
                'type' => 'text',
                'wrapper' => array(
                    'width' => '50'
                )
            ),
            array(
                'key' => 'field_66f0d5dcbbf95',
                'label' => 'Your Email',
                'name' => 'site_email',
                'type' => 'email',
                'wrapper' => array(
                    'width' => '50'
                )
            ),
            array(
                'key' => 'field_66f0d5f1bbf96',
                'label' => 'Your Phone',
                'name' => 'site_phone',
                'type' => 'text',
                'wrapper' => array(
                    'width' => '50'
                )
            ),
            array(
                'key' => 'field_66f369c071664',
                'label' => 'Your Headshot',
                'name' => 'site_headshot',
                'type' => 'image',
                'return_format' => 'array',
                'library' => 'all',
                'preview_size' => 'medium'
            ),
            array(
                'key' => 'field_667bf1a8f1638',
                'label' => 'Credibility Center',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
                'endpoint' => 0,
                'selected' => 0
            ),
            array(
                'key' => 'field_667bf1c9f1639',
                'label' => 'Heading',
                'name' => 'credibility_heading',
                'type' => 'text'
            ),
            array(
                'key' => 'field_667bf1dff163a',
                'label' => 'Full Text',
                'name' => 'credibility_full_text',
                'type' => 'wysiwyg',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
                'delay' => 0
            ),
            array(
                'key' => 'field_667bf214f163b',
                'label' => 'Contact CTA',
                'name' => 'credibility_contact_cta',
                'type' => 'wysiwyg',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
                'delay' => 0
            ),
            array(
                'key' => 'field_667bf27cf163c',
                'label' => 'Footer Contact Details',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
                'endpoint' => 0,
                'selected' => 0
            ),
            array(
                'key' => 'field_66ac37904a6aa',
                'label' => 'Street Address',
                'name' => 'credibility_contact_address',
                'type' => 'wysiwyg',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
                'delay' => 0
            ),
            array(
                'key' => 'field_66ac37ac4a6ab',
                'label' => 'Phone Number',
                'name' => 'credibility_contact_phone',
                'type' => 'text'
            ),
            array(
                'key' => 'field_66ac37c84a6ac',
                'label' => 'Email Address',
                'name' => 'credibility_contact_email',
                'type' => 'email'
            ),
            array(
                'key' => 'field_66ac37e64a6ad',
                'label' => 'Business Registration Type',
                'name' => 'credibility_contact_biztype',
                'type' => 'text'
            ),
            array(
                'key' => 'field_66ac380a4a6ae',
                'label' => 'Business Registration ID',
                'name' => 'credibility_contact_business_registration_id',
                'type' => 'text'
            ),
            array(
                'key' => 'field_66f4da1111f3b',
                'label' => 'Benefits Section',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
                'endpoint' => 0,
                'selected' => 0
            ),
            array(
                'key' => 'field_66f4da2611f3c',
                'label' => 'Benefits Title',
                'name' => 'benefits_title',
                'type' => 'text'
            ),
            array(
                'key' => 'field_66f4da3511f3d',
                'label' => 'Benefits Intro',
                'name' => 'benefits_intro',
                'type' => 'wysiwyg',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
                'delay' => 0
            ),
            array(
                'key' => 'field_66f4db9211f3e',
                'label' => 'Benefits List',
                'name' => 'benefits_list',
                'type' => 'repeater',
                'layout' => 'table',
                'button_label' => 'Add Row',
                'sub_fields' => array(
                    array(
                        'key' => 'field_66f4dba811f3f',
                        'label' => 'Benefit',
                        'name' => 'benefit_item',
                        'type' => 'text',
                        'parent_repeater' => 'field_66f4db9211f3e'
                    )
                )
            ),
            array(
                'key' => 'field_66fdface58db8',
                'label' => 'Blog Setup',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
                'endpoint' => 0,
                'selected' => 0
            ),
            array(
                'key' => 'field_66fdfadf58db9',
                'label' => 'Blog Hero Image',
                'name' => 'blog_hero_image',
                'type' => 'image',
                'return_format' => 'array',
                'library' => 'all',
                'preview_size' => 'medium'
            ),
            array(
                'key' => 'field_66fdfc3358dba',
                'label' => 'Blog Hero Title',
                'name' => 'blog_hero_title',
                'type' => 'text'
            ),
            array(
                'key' => 'field_66fdfc3f58dbb',
                'label' => 'Blog Hero Headline',
                'name' => 'blog_hero_headline',
                'type' => 'wysiwyg',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
                'delay' => 0
            )
        ),
        'location' => array(
            array(
                array(
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'pro-site-options'
                )
            )
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
        'show_in_rest' => 0
    ));
} 
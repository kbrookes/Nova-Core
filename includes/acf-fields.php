<?php
// Registers ACF fields using acf_add_local_field_group().

// Register Case Studies ACF fields
function nova_core_register_case_studies_fields() {
    $options = get_option('nova_core_features_options');
    if (!isset($options['enable_case_studies']) || !$options['enable_case_studies']) {
        return;
    }

    acf_add_local_field_group(array(
        'key' => 'group_605aab6027eac',
        'title' => 'Case Studies',
        'fields' => array(
            array(
                'key' => 'field_645b04ce5db6a',
                'label' => 'Client Info',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
                'endpoint' => 0,
                'selected' => 0
            ),
            array(
                'key' => 'field_6461aa59ea280',
                'label' => 'Headline',
                'name' => 'cs_headline',
                'type' => 'text',
                'default_value' => 'Provide a single compelling statement that can headline your C.A.S.E.',
            ),
            array(
                'key' => 'field_65161a0b878ce',
                'label' => 'Full Name',
                'name' => 'cs_fullname',
                'type' => 'text',
                'default_value' => 'If wanted, provide the full name of the person who is featured in the case study.',
            ),
            array(
                'key' => 'field_65161a8e878cf',
                'label' => 'Company Name',
                'name' => 'cs_companyname',
                'type' => 'text',
                'default_value' => 'If wanted, provide the name of the company featured in the case study.',
            ),
            array(
                'key' => 'field_645b04de5db6b',
                'label' => 'Headshot or Logo',
                'name' => 'client_logo',
                'type' => 'image',
                'return_format' => 'array',
                'library' => 'all',
                'preview_size' => 'full'
            ),
            array(
                'key' => 'field_645b05085db6c',
                'label' => 'Industry',
                'name' => 'cs_industry',
                'type' => 'text'
            ),
            array(
                'key' => 'field_645b05175db6d',
                'label' => 'Location',
                'name' => 'cs_location',
                'type' => 'text'
            ),
            array(
                'key' => 'field_605aad4297644',
                'label' => 'Challenge',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
                'endpoint' => 0,
                'selected' => 0
            ),
            array(
                'key' => 'field_605aad2a97643',
                'label' => 'Challenge',
                'name' => 'cs_challenge',
                'type' => 'wysiwyg',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
                'delay' => 0
            ),
            array(
                'key' => 'field_605aad4b97645',
                'label' => 'Approach',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
                'endpoint' => 0,
                'selected' => 0
            ),
            array(
                'key' => 'field_605aad5597646',
                'label' => 'Approach',
                'name' => 'cs_approach',
                'type' => 'wysiwyg',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
                'delay' => 0
            ),
            array(
                'key' => 'field_61a40691a2b21',
                'label' => 'Solution',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
                'endpoint' => 0,
                'selected' => 0
            ),
            array(
                'key' => 'field_61a4069ba2b22',
                'label' => 'Solution',
                'name' => 'cs_solution',
                'type' => 'wysiwyg',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
                'delay' => 0
            ),
            array(
                'key' => 'field_605aad6097647',
                'label' => 'Experience',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
                'endpoint' => 0,
                'selected' => 0
            ),
            array(
                'key' => 'field_605aad7597648',
                'label' => 'Experience',
                'name' => 'cs_result',
                'type' => 'wysiwyg',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
                'delay' => 0
            ),
            array(
                'key' => 'field_662347e80185d',
                'label' => 'Testimonial',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
                'endpoint' => 0,
                'selected' => 0
            ),
            array(
                'key' => 'field_662347f80185e',
                'label' => 'Select Testimonial',
                'name' => 'select_testimonial',
                'type' => 'post_object',
                'post_type' => array('testimonial'),
                'post_status' => array('publish'),
                'return_format' => 'object',
                'multiple' => 0,
                'allow_null' => 0,
                'ui' => 1
            )
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'case-studies'
                )
            )
        ),
        'menu_order' => 213,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
        'show_in_rest' => 0
    ));
}
add_action('acf/init', 'nova_core_register_case_studies_fields');
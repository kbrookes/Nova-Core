<?php
// Enable TinyMCE for WordPress Category Descriptions

// 1. Allow HTML in term descriptions (bypass WordPress filtering)
remove_filter('pre_term_description', 'wp_filter_kses');
remove_filter('term_description', 'wp_kses_data');

// 2. Load editor scripts on taxonomy pages
add_action('admin_enqueue_scripts', function () {
    $screen = get_current_screen();
    if ($screen && $screen->taxonomy === 'category') {
        wp_enqueue_editor();
    }
});

// 3. Add TinyMCE editor on "Add Category" screen
add_action('category_add_form_fields', function () {
    ?>
    <div class="form-field term-description-wrap">
        <label for="description"><?php _e('Description'); ?></label>
        <?php
        wp_editor('', 'description', [
            'textarea_name' => 'description',
            'media_buttons' => true,
            'textarea_rows' => 8,
            'tinymce'       => true,
            'quicktags'     => true,
        ]);
        ?>
        <p class="description"><?php _e('Rich description supports HTML and media.'); ?></p>
    </div>
    <?php
});

// 4. Add TinyMCE editor on "Edit Category" screen
add_action('category_edit_form_fields', function ($term) {
    ?>
    <tr class="form-field term-description-wrap">
        <th scope="row"><label for="description"><?php _e('Description'); ?></label></th>
        <td>
            <?php
            wp_editor(htmlspecialchars_decode($term->description), 'description', [
                'textarea_name' => 'description',
                'media_buttons' => true,
                'textarea_rows' => 8,
                'tinymce'       => true,
                'quicktags'     => true,
            ]);
            ?>
            <p class="description"><?php _e('Rich description supports HTML and media.'); ?></p>
        </td>
    </tr>
    <?php
}, 10, 1);

// 5. Remove default plain-text description field (both add/edit screens)
add_action('admin_head', function () {
    $screen = get_current_screen();
    if ($screen && $screen->taxonomy === 'category') {
        ?>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                // Remove legacy textarea (Add Category screen)
                const addField = document.querySelector('#tag-description');
                if (addField) {
                    addField.closest('.form-field').remove();
                }

                // Remove legacy textarea (Edit Category screen)
                const editField = document.querySelector('tr.form-field.term-description-wrap textarea#description');
                if (editField) {
                    editField.closest('tr.form-field.term-description-wrap').remove();
                }
            });
        </script>
        <style>
            .wp-editor-area {
                color: #1d2327 !important;
                background-color: #fff !important;
            }
        </style>
        <?php
    }
});

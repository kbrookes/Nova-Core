<?php
/**
 * Nova Schema Metabox
 *
 * Per-post overrides for the structured data emitted by Nova Schema.
 *
 * Stored meta keys:
 * - _nova_schema_disabled    (1|0) suppress all Nova Schema output for this URL.
 * - _nova_schema_description  string description override.
 * - _nova_schema_faq_auto    (1|0) extract FAQ schema from page's Bricks accordion.
 *
 * @package Nova_Core
 */

defined('ABSPATH') || exit;

/**
 * Post types where the Nova Schema metabox is offered.
 *
 * @return array
 */
function nova_schema_metabox_post_types() {
    $types = array('post', 'page', 'services', 'case-studies', 'testimonial');
    return apply_filters('nova_schema_metabox_post_types', $types);
}

/**
 * Register the metabox on every supported post type.
 */
add_action('add_meta_boxes', 'nova_schema_register_metabox');
function nova_schema_register_metabox() {
    foreach (nova_schema_metabox_post_types() as $post_type) {
        if (!post_type_exists($post_type)) {
            continue;
        }
        add_meta_box(
            'nova_schema_metabox',
            'Nova Schema',
            'nova_schema_render_metabox',
            $post_type,
            'side',
            'default'
        );
    }
}

/**
 * Human-friendly label for the detected schema type.
 *
 * @param string $type Detected type slug.
 * @return string
 */
function nova_schema_type_label($type) {
    $labels = array(
        'home'            => 'WebSite + Organization',
        'blogposting'     => 'BlogPosting',
        'casestudy'       => 'Article (CaseStudy)',
        'service'         => 'Service',
        'review'          => 'Review',
        'faqpage'         => 'FAQPage',
        'collectionpage'  => 'CollectionPage',
        'webpage'         => 'WebPage',
    );
    return isset($labels[$type]) ? $labels[$type] : ucfirst($type);
}

/**
 * Detect the schema type that will apply for a specific post (admin-side).
 *
 * Mirrors the front-end detection in nova_schema_detect_type() but works
 * outside the main query loop so the metabox can show an accurate preview.
 *
 * @param WP_Post $post Post being edited.
 * @return string
 */
function nova_schema_detect_for_post($post) {
    if (!$post) {
        return 'webpage';
    }
    if ((int) get_option('page_on_front') === (int) $post->ID) {
        return 'home';
    }
    switch ($post->post_type) {
        case 'case-studies':
        case 'case-study':
        case 'case_study':
            return 'casestudy';
        case 'testimonial':
        case 'testimonials':
            return 'review';
        case 'services':
        case 'service':
            return 'service';
        case 'post':
            return 'blogposting';
    }
    return 'webpage';
}

/**
 * Render the Nova Schema metabox.
 *
 * @param WP_Post $post Current post.
 */
function nova_schema_render_metabox($post) {
    wp_nonce_field('nova_schema_save_meta', 'nova_schema_meta_nonce');

    $disabled    = (int) get_post_meta($post->ID, '_nova_schema_disabled', true);
    $description = (string) get_post_meta($post->ID, '_nova_schema_description', true);
    $faq_auto    = (bool) get_post_meta($post->ID, NOVA_SCHEMA_META_FAQ_AUTO, true);

    $detected = nova_schema_detect_for_post($post);
    $label    = nova_schema_type_label($detected);

    // Master schema switch from settings — reflect this in the metabox so the
    // editor knows whether their per-post overrides will actually emit.
    $opts          = function_exists('nova_schema_get_options') ? nova_schema_get_options() : array('enabled' => 0);
    $module_active = !empty($opts['enabled']);
    ?>
    <div class="nova-schema-metabox">
        <p class="nova-schema-detected">
            <strong>Detected type:</strong>
            <code><?php echo esc_html($label); ?></code>
        </p>

        <?php if (!$module_active) : ?>
            <p class="description" style="color:#b32d2e;">
                Nova Schema output is currently disabled in
                <a href="<?php echo esc_url(admin_url('options-general.php?page=nova-core-settings&tab=schema')); ?>">Nova Core → Schema</a>.
                Saved overrides will only take effect once the module is enabled.
            </p>
        <?php endif; ?>

        <div class="nova-post-option nova-toggle-field" style="margin-top:12px;">
            <label class="nova-toggle-label">
                <strong>Disable schema for this URL</strong>
            </label>
            <label class="nova-toggle-switch">
                <input type="hidden" name="nova_schema_disabled" value="0">
                <input type="checkbox" name="nova_schema_disabled" value="1" <?php checked(1, $disabled); ?>>
                <span class="nova-toggle-slider">
                    <span class="nova-toggle-on">Off</span>
                    <span class="nova-toggle-off">On</span>
                </span>
            </label>
        </div>

        <p style="margin-top:14px;">
            <label for="nova_schema_description"><strong>Custom description</strong></label>
            <textarea id="nova_schema_description"
                      name="nova_schema_description"
                      rows="3"
                      style="width:100%;"
                      maxlength="320"
                      placeholder="Override the auto-generated description used in this page's schema."><?php echo esc_textarea($description); ?></textarea>
            <span class="description">Leave blank to use the excerpt or an auto-extract.</span>
        </p>

        <div class="nova-schema-faqs" style="margin-top:14px;">
            <p style="margin-bottom:8px;"><strong>FAQ schema</strong></p>
            <label style="display:flex;align-items:flex-start;gap:6px;">
                <input type="checkbox"
                       name="nova_schema_faq_auto"
                       value="1"
                       style="margin-top:3px;"
                       <?php checked(true, $faq_auto); ?>>
                <span>
                    Auto-extract from Bricks accordion<br>
                    <span class="description">Reads Q&amp;A pairs from Bricks. Add class <code>nova-faq</code> to FAQ accordion elements to generate schema.</span>
                </span>
            </label>
        </div>
    </div>
    <?php
}

/**
 * Persist Nova Schema metabox values when the post is saved.
 *
 * @param int $post_id Saved post ID.
 */
add_action('save_post', 'nova_schema_save_metabox', 10, 1);
function nova_schema_save_metabox($post_id) {
    if (!isset($_POST['nova_schema_meta_nonce'])) {
        return;
    }
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nova_schema_meta_nonce'])), 'nova_schema_save_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Disable toggle (the hidden 0 + checkbox 1 pattern guarantees this is set).
    $disabled = !empty($_POST['nova_schema_disabled']) ? 1 : 0;
    if ($disabled) {
        update_post_meta($post_id, '_nova_schema_disabled', 1);
    } else {
        delete_post_meta($post_id, '_nova_schema_disabled');
    }

    // Auto-extract FAQ from Bricks accordion.
    if (!empty($_POST['nova_schema_faq_auto'])) {
        update_post_meta($post_id, NOVA_SCHEMA_META_FAQ_AUTO, 1);
    } else {
        delete_post_meta($post_id, NOVA_SCHEMA_META_FAQ_AUTO);
    }

    // Description override.
    $description = isset($_POST['nova_schema_description'])
        ? sanitize_textarea_field(wp_unslash($_POST['nova_schema_description']))
        : '';
    if ($description !== '') {
        update_post_meta($post_id, '_nova_schema_description', $description);
    } else {
        delete_post_meta($post_id, '_nova_schema_description');
    }
}



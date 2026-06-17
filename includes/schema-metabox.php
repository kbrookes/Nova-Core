<?php
/**
 * Nova Schema Metabox
 *
 * Per-post overrides for the structured data emitted by Nova Schema.
 *
 * Stored meta keys:
 * - _nova_schema_disabled    (1|0) suppress all Nova Schema output for this URL.
 * - _nova_schema_description  string description override.
 * - _nova_schema_faqs         array of [ 'q' => ..., 'a' => ... ] pairs.
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
    $faqs        = get_post_meta($post->ID, '_nova_schema_faqs', true);
    if (!is_array($faqs)) {
        $faqs = array();
    }

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

            <label style="display:flex;align-items:flex-start;gap:6px;margin-bottom:8px;">
                <input type="checkbox"
                       name="nova_schema_faq_auto"
                       id="nova-schema-faq-auto"
                       value="1"
                       style="margin-top:3px;"
                       <?php checked(true, $faq_auto); ?>>
                <span>
                    Auto-extract from Bricks accordion<br>
                    <span class="description">Reads Q&amp;A pairs from the accordion on this page. If multiple accordions exist, add the CSS class <code>nova-faq</code> to the FAQ one in Bricks Builder.</span>
                </span>
            </label>

            <div id="nova-schema-manual-faqs" <?php echo $faq_auto ? 'style="display:none;"' : ''; ?>>
                <span class="description" style="display:block;margin-bottom:8px;">
                    Manual Q+A pairs — leave both blank to discard a row.
                </span>
                <div class="nova-schema-faqs-rows">
                    <?php
                    $rows = !empty($faqs) ? $faqs : array(array('q' => '', 'a' => ''));
                    foreach ($rows as $row) :
                        $q = isset($row['q']) ? $row['q'] : '';
                        $a = isset($row['a']) ? $row['a'] : '';
                        ?>
                        <div class="nova-schema-faq-row" style="border:1px solid #dcdcde;padding:8px;margin-bottom:8px;background:#fafafa;">
                            <input type="text"
                                   name="nova_schema_faqs[q][]"
                                   value="<?php echo esc_attr($q); ?>"
                                   placeholder="Question"
                                   style="width:100%;margin-bottom:6px;">
                            <textarea name="nova_schema_faqs[a][]"
                                      rows="2"
                                      placeholder="Answer"
                                      style="width:100%;"><?php echo esc_textarea($a); ?></textarea>
                            <p style="margin:6px 0 0;text-align:right;">
                                <button type="button" class="button-link nova-schema-faq-remove" style="color:#b32d2e;">Remove</button>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p>
                    <button type="button" class="button button-secondary nova-schema-faq-add">Add FAQ</button>
                </p>
            </div>
        </div>
    </div>

    <script>
        (function(){
            var toggle = document.getElementById('nova-schema-faq-auto');
            var manual = document.getElementById('nova-schema-manual-faqs');
            if (toggle && manual) {
                toggle.addEventListener('change', function() {
                    manual.style.display = this.checked ? 'none' : '';
                });
            }

            var wrap = document.querySelector('.nova-schema-faqs');
            if (!wrap) return;
            var rows = wrap.querySelector('.nova-schema-faqs-rows');
            wrap.addEventListener('click', function(e){
                if (e.target.classList.contains('nova-schema-faq-add')) {
                    e.preventDefault();
                    var div = document.createElement('div');
                    div.className = 'nova-schema-faq-row';
                    div.setAttribute('style', 'border:1px solid #dcdcde;padding:8px;margin-bottom:8px;background:#fafafa;');
                    div.innerHTML = '<input type="text" name="nova_schema_faqs[q][]" value="" placeholder="Question" style="width:100%;margin-bottom:6px;">'
                        + '<textarea name="nova_schema_faqs[a][]" rows="2" placeholder="Answer" style="width:100%;"></textarea>'
                        + '<p style="margin:6px 0 0;text-align:right;"><button type="button" class="button-link nova-schema-faq-remove" style="color:#b32d2e;">Remove</button></p>';
                    rows.appendChild(div);
                }
                if (e.target.classList.contains('nova-schema-faq-remove')) {
                    e.preventDefault();
                    var row = e.target.closest('.nova-schema-faq-row');
                    if (row) { row.remove(); }
                }
            });
        })();
    </script>
    <?php
}

/**
 * Persist Nova Schema metabox values when the post is saved.
 *
 * Skips autosaves, revisions, AJAX, and unauthorised users. Pairs the parallel
 * question/answer arrays back into a row-based structure before storage.
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

    // FAQ repeater — parallel arrays of questions + answers, paired by index.
    $faqs = array();
    if (isset($_POST['nova_schema_faqs']) && is_array($_POST['nova_schema_faqs'])) {
        $questions = isset($_POST['nova_schema_faqs']['q']) && is_array($_POST['nova_schema_faqs']['q'])
            ? $_POST['nova_schema_faqs']['q'] : array();
        $answers = isset($_POST['nova_schema_faqs']['a']) && is_array($_POST['nova_schema_faqs']['a'])
            ? $_POST['nova_schema_faqs']['a'] : array();

        $count = max(count($questions), count($answers));
        for ($i = 0; $i < $count; $i++) {
            $q = isset($questions[$i]) ? sanitize_text_field(wp_unslash($questions[$i])) : '';
            $a = isset($answers[$i]) ? sanitize_textarea_field(wp_unslash($answers[$i])) : '';
            if ($q === '' || $a === '') {
                continue;
            }
            $faqs[] = array('q' => $q, 'a' => $a);
        }
    }

    if (!empty($faqs)) {
        update_post_meta($post_id, '_nova_schema_faqs', $faqs);
    } else {
        delete_post_meta($post_id, '_nova_schema_faqs');
    }
}



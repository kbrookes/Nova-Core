<?php
/**
 * Nova Schema Module
 *
 * Replaces SEOPress schema output with a custom JSON-LD implementation
 * using the @graph architecture with linked @id entities.
 *
 * - Settings tab registration (Organisation-level config)
 * - Suppression of SEOPress structured data output
 * - Helper accessors used by schema-output.php and schema-metabox.php
 *
 * @package Nova_Core
 */

defined('ABSPATH') || exit;

/**
 * Option name used for organisation-level schema settings.
 */
if (!defined('NOVA_SCHEMA_OPTION')) {
    define('NOVA_SCHEMA_OPTION', 'nova_core_schema_options');
}

/**
 * Post meta keys used by the per-post metabox.
 */
if (!defined('NOVA_SCHEMA_META_DISABLED')) {
    define('NOVA_SCHEMA_META_DISABLED', '_nova_schema_disabled');
}
if (!defined('NOVA_SCHEMA_META_DESCRIPTION')) {
    define('NOVA_SCHEMA_META_DESCRIPTION', '_nova_schema_description');
}
if (!defined('NOVA_SCHEMA_META_TYPE')) {
    define('NOVA_SCHEMA_META_TYPE', '_nova_schema_type_override');
}
if (!defined('NOVA_SCHEMA_META_FAQ_AUTO')) {
    define('NOVA_SCHEMA_META_FAQ_AUTO', '_nova_schema_faq_auto');
}

/**
 * Return the list of supported Organization sub-types.
 *
 * @return array<string,string> Type key => human label.
 */
function nova_schema_org_types() {
    return apply_filters('nova_schema_org_types', array(
        'Organization'        => 'Organization (generic)',
        'LocalBusiness'       => 'Local Business',
        'ProfessionalService' => 'Professional Service',
        'AccountingService'   => 'Accounting Service',
        'LegalService'        => 'Legal Service',
        'MedicalBusiness'     => 'Medical Business',
        'FinancialService'    => 'Financial Service',
        'InsuranceAgency'     => 'Insurance Agency',
        'RealEstateAgent'     => 'Real Estate Agent',
        'HomeAndConstructionBusiness' => 'Home & Construction',
        'AutomotiveBusiness'  => 'Automotive Business',
        'HealthAndBeautyBusiness' => 'Health & Beauty',
        'FoodEstablishment'   => 'Food Establishment',
        'Store'               => 'Store / Retail',
        'EducationalOrganization' => 'Educational Organization',
        'NGO'                 => 'NGO / Non-profit',
        'Corporation'         => 'Corporation',
    ));
}

/**
 * Return the schema option array with defaults merged in.
 *
 * @return array
 */
function nova_schema_get_options() {
    $defaults = array(
        'enabled'      => 1,
        'org_name'     => get_bloginfo('name'),
        'org_type'     => 'Organization',
        'org_logo_id'  => 0,
        'org_logo_url' => '',
        'org_url'      => home_url('/'),
        'org_phone'    => '',
        'org_email'    => '',
        'addr_street'  => '',
        'addr_city'    => '',
        'addr_state'   => '',
        'addr_postcode'=> '',
        'addr_country' => '',
        'geo_lat'      => '',
        'geo_lng'      => '',
        'same_as'      => array(),
    );
    $stored = get_option(NOVA_SCHEMA_OPTION, array());
    if (!is_array($stored)) {
        $stored = array();
    }
    $merged = array_merge($defaults, $stored);
    if (!is_array($merged['same_as'])) {
        $merged['same_as'] = array();
    }
    return $merged;
}

/**
 * Whether Nova Schema output is enabled globally.
 *
 * @return bool
 */
function nova_schema_is_enabled() {
    $opts = nova_schema_get_options();
    return apply_filters('nova_schema_enabled', !empty($opts['enabled']));
}

/**
 * Suppress SEOPress schema output across free + pro builds.
 *
 * Uses every documented SEOPress filter so the plugin emits no JSON-LD.
 * Nova Schema then owns the structured data output entirely.
 */
add_action('init', 'nova_schema_suppress_seopress', 5);
function nova_schema_suppress_seopress() {
    add_filter('seopress_schemas_json_ld', '__return_empty_string', 99);
    add_filter('seopress_pro_schemas_array', '__return_empty_array', 99);
    add_filter('seopress_pro_schemas_global', '__return_empty_array', 99);
    add_filter('seopress_pro_schemas_post_meta', '__return_empty_array', 99);
    add_filter('seopress_pro_schemas_taxonomy_meta', '__return_empty_array', 99);
    add_filter('seopress_pro_schemas_manual', '__return_empty_array', 99);
    add_filter('seopress_pro_schemas_auto', '__return_empty_array', 99);
    add_filter('seopress_titles_knowledge_graph_output', '__return_empty_string', 99);
}

/**
 * Register the settings group + fields used by the Schema tab.
 *
 * Uses a single section + one sanitise callback so all keys round-trip
 * cleanly through options.php submissions on the Nova Core settings page.
 */
add_action('admin_init', 'nova_schema_register_settings');
function nova_schema_register_settings() {
    register_setting('nova_core_schema_settings', NOVA_SCHEMA_OPTION, array(
        'sanitize_callback' => 'nova_schema_sanitize_options',
    ));

    add_settings_section(
        'nova_core_schema_section',
        'Organisation Details',
        'nova_schema_section_callback',
        'nova-core-schema'
    );

    $fields = array(
        'enabled'       => 'Schema Output',
        'org_name'      => 'Organisation Name',
        'org_type'      => 'Organisation Type',
        'org_logo_id'   => 'Logo',
        'org_url'       => 'Primary URL',
        'org_phone'     => 'Phone',
        'org_email'     => 'Email',
        'addr_street'   => 'Street Address',
        'addr_city'     => 'City / Suburb',
        'addr_state'    => 'State / Region',
        'addr_postcode' => 'Postcode',
        'addr_country'  => 'Country',
        'geo_lat'       => 'Latitude',
        'geo_lng'       => 'Longitude',
        'same_as'       => 'sameAs URLs',
    );

    foreach ($fields as $key => $label) {
        add_settings_field(
            $key,
            $label,
            'nova_schema_field_' . $key,
            'nova-core-schema',
            'nova_core_schema_section'
        );
    }
}

/**
 * Sanitise the Schema option array.
 *
 * @param mixed $input Raw submitted value.
 * @return array
 */
function nova_schema_sanitize_options($input) {
    if (!is_array($input)) {
        $input = array();
    }
    $existing = get_option(NOVA_SCHEMA_OPTION, array());
    if (!is_array($existing)) {
        $existing = array();
    }

    $valid_types = array_keys(nova_schema_org_types());

    $out = array();
    $out['enabled']      = !empty($input['enabled']) ? 1 : 0;
    $out['org_name']     = isset($input['org_name']) ? sanitize_text_field($input['org_name']) : '';
    $out['org_type']     = (isset($input['org_type']) && in_array($input['org_type'], $valid_types, true))
        ? $input['org_type'] : 'Organization';
    $out['org_logo_id']  = isset($input['org_logo_id']) ? absint($input['org_logo_id']) : 0;
    $out['org_logo_url'] = '';
    if ($out['org_logo_id']) {
        $src = wp_get_attachment_image_src($out['org_logo_id'], 'full');
        if ($src) {
            $out['org_logo_url'] = esc_url_raw($src[0]);
        }
    } elseif (!empty($input['org_logo_url'])) {
        $out['org_logo_url'] = esc_url_raw($input['org_logo_url']);
    }
    $out['org_url']      = isset($input['org_url']) ? esc_url_raw($input['org_url']) : '';
    $out['org_phone']    = isset($input['org_phone']) ? sanitize_text_field($input['org_phone']) : '';
    $out['org_email']    = isset($input['org_email']) ? sanitize_email($input['org_email']) : '';
    $out['addr_street']  = isset($input['addr_street']) ? sanitize_text_field($input['addr_street']) : '';
    $out['addr_city']    = isset($input['addr_city']) ? sanitize_text_field($input['addr_city']) : '';
    $out['addr_state']   = isset($input['addr_state']) ? sanitize_text_field($input['addr_state']) : '';
    $out['addr_postcode']= isset($input['addr_postcode']) ? sanitize_text_field($input['addr_postcode']) : '';
    $out['addr_country'] = isset($input['addr_country']) ? sanitize_text_field($input['addr_country']) : '';
    $out['geo_lat']      = isset($input['geo_lat']) ? nova_schema_sanitize_coord($input['geo_lat']) : '';
    $out['geo_lng']      = isset($input['geo_lng']) ? nova_schema_sanitize_coord($input['geo_lng']) : '';

    $out['same_as'] = array();
    if (isset($input['same_as']) && is_array($input['same_as'])) {
        foreach ($input['same_as'] as $url) {
            $clean = esc_url_raw(trim((string) $url));
            if ($clean !== '') {
                $out['same_as'][] = $clean;
            }
        }
    }

    return $out;
}

/**
 * Sanitise a latitude/longitude coordinate string.
 *
 * @param string $value Raw value.
 * @return string Empty string when invalid.
 */
function nova_schema_sanitize_coord($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    if (!is_numeric($value)) {
        return '';
    }
    return (string) (float) $value;
}

/**
 * Section description for the Schema settings page.
 */
function nova_schema_section_callback() {
    echo '<p>Configure the organisation-level information that Nova Schema injects into every page as JSON-LD. These values build the cross-referenced <code>Organization</code> and <code>WebSite</code> nodes used by every page template.</p>';
}

/* -------------------------------------------------------------------------
 * Field render callbacks
 * ------------------------------------------------------------------------- */

function nova_schema_field_enabled() {
    $opts = nova_schema_get_options();
    ?>
    <label class="nova-toggle">
        <input type="hidden" name="<?php echo esc_attr(NOVA_SCHEMA_OPTION); ?>[enabled]" value="0">
        <input type="checkbox" name="<?php echo esc_attr(NOVA_SCHEMA_OPTION); ?>[enabled]" value="1" <?php checked(1, (int) $opts['enabled']); ?>>
        <span class="nova-toggle-slider">
            <span class="nova-toggle-on">On</span>
            <span class="nova-toggle-off">Off</span>
        </span>
    </label>
    <p class="description">Master switch. When off, Nova Schema emits no JSON-LD (SEOPress is still suppressed).</p>
    <?php
}

function nova_schema_field_org_name() {
    $opts = nova_schema_get_options();
    printf(
        '<input type="text" class="regular-text" name="%1$s[org_name]" value="%2$s" />',
        esc_attr(NOVA_SCHEMA_OPTION),
        esc_attr($opts['org_name'])
    );
}

function nova_schema_field_org_type() {
    $opts = nova_schema_get_options();
    $types = nova_schema_org_types();
    echo '<select name="' . esc_attr(NOVA_SCHEMA_OPTION) . '[org_type]">';
    foreach ($types as $key => $label) {
        printf(
            '<option value="%1$s" %2$s>%3$s</option>',
            esc_attr($key),
            selected($opts['org_type'], $key, false),
            esc_html($label)
        );
    }
    echo '</select>';
    echo '<p class="description">The output node is co-typed as <code>Organization</code> + this value.</p>';
}

function nova_schema_field_org_logo_id() {
    $opts = nova_schema_get_options();
    $logo_id = (int) $opts['org_logo_id'];
    $preview = '';
    if ($logo_id) {
        $src = wp_get_attachment_image_src($logo_id, 'medium');
        if ($src) {
            $preview = $src[0];
        }
    }
    ?>
    <div class="nova-schema-media" data-target="nova-schema-logo">
        <input type="hidden" id="nova-schema-logo" name="<?php echo esc_attr(NOVA_SCHEMA_OPTION); ?>[org_logo_id]" value="<?php echo esc_attr($logo_id); ?>">
        <div class="nova-schema-media-preview" <?php echo $preview ? '' : 'style="display:none"'; ?>>
            <?php if ($preview) : ?>
                <img src="<?php echo esc_url($preview); ?>" alt="" style="max-width:200px;height:auto;display:block;">
            <?php endif; ?>
        </div>
        <p>
            <button type="button" class="button nova-schema-media-pick">Select logo</button>
            <button type="button" class="button-link nova-schema-media-clear" <?php echo $logo_id ? '' : 'style="display:none"'; ?>>Remove</button>
        </p>
        <p class="description">Used as the <code>logo</code> and as the publisher logo in BlogPosting/Article nodes.</p>
    </div>
    <?php
}

function nova_schema_field_org_url() {
    $opts = nova_schema_get_options();
    printf(
        '<input type="url" class="regular-text" name="%1$s[org_url]" value="%2$s" placeholder="%3$s" />',
        esc_attr(NOVA_SCHEMA_OPTION),
        esc_attr($opts['org_url']),
        esc_attr(home_url('/'))
    );
}

function nova_schema_field_org_phone() {
    $opts = nova_schema_get_options();
    printf(
        '<input type="text" class="regular-text" name="%1$s[org_phone]" value="%2$s" placeholder="+61 ..." />',
        esc_attr(NOVA_SCHEMA_OPTION),
        esc_attr($opts['org_phone'])
    );
}

function nova_schema_field_org_email() {
    $opts = nova_schema_get_options();
    printf(
        '<input type="email" class="regular-text" name="%1$s[org_email]" value="%2$s" />',
        esc_attr(NOVA_SCHEMA_OPTION),
        esc_attr($opts['org_email'])
    );
}

function nova_schema_field_addr_street() { nova_schema_text_field('addr_street'); }
function nova_schema_field_addr_city() { nova_schema_text_field('addr_city'); }
function nova_schema_field_addr_state() { nova_schema_text_field('addr_state'); }
function nova_schema_field_addr_postcode() { nova_schema_text_field('addr_postcode'); }
function nova_schema_field_addr_country() { nova_schema_text_field('addr_country'); }
function nova_schema_field_geo_lat() { nova_schema_text_field('geo_lat', 'small-text', 'e.g. -33.8688'); }
function nova_schema_field_geo_lng() { nova_schema_text_field('geo_lng', 'small-text', 'e.g. 151.2093'); }

/**
 * Shared text-field renderer used by simple address/geo fields.
 *
 * @param string $key         Option key.
 * @param string $size_class  CSS class (regular-text / small-text).
 * @param string $placeholder Placeholder text.
 */
function nova_schema_text_field($key, $size_class = 'regular-text', $placeholder = '') {
    $opts = nova_schema_get_options();
    $value = isset($opts[$key]) ? $opts[$key] : '';
    printf(
        '<input type="text" class="%1$s" name="%2$s[%3$s]" value="%4$s" placeholder="%5$s" />',
        esc_attr($size_class),
        esc_attr(NOVA_SCHEMA_OPTION),
        esc_attr($key),
        esc_attr($value),
        esc_attr($placeholder)
    );
}

function nova_schema_field_same_as() {
    $opts = nova_schema_get_options();
    $urls = $opts['same_as'];
    if (empty($urls)) {
        $urls = array('');
    }
    ?>
    <div class="nova-schema-sameas-wrap">
        <?php foreach ($urls as $url) : ?>
            <div class="nova-schema-sameas-row">
                <input type="url" class="regular-text" name="<?php echo esc_attr(NOVA_SCHEMA_OPTION); ?>[same_as][]" value="<?php echo esc_attr($url); ?>" placeholder="https://www.linkedin.com/company/..." />
                <button type="button" class="button-link nova-schema-sameas-remove">Remove</button>
            </div>
        <?php endforeach; ?>
        <p><button type="button" class="button nova-schema-sameas-add">Add URL</button></p>
        <p class="description">Add a row per social/profile URL (LinkedIn, Facebook, X, Crunchbase, etc.). Empty rows are discarded on save.</p>
    </div>
    <?php
}

/**
 * Render the Schema tab body on the Nova Core settings page.
 *
 * Called from settings-page.php when the active tab is `schema`.
 */
function nova_core_schema_settings_render() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="nova-schema-settings-wrap">
        <div class="nova-schema-settings-main">
            <form action="options.php" method="post">
                <?php
                settings_fields('nova_core_schema_settings');
                do_settings_sections('nova-core-schema');
                submit_button('Save Schema Settings');
                ?>
            </form>
        </div>
        <div class="nova-schema-settings-sidebar">
            <div class="nova-meta-reference">
                <h3>How it works</h3>
                <p class="description">Nova Schema replaces SEOPress structured data entirely. SEOPress is suppressed via its own filters; Nova Schema emits a single <code>&lt;script type="application/ld+json"&gt;</code> block per page.</p>
                <div class="nova-meta-item">
                    <h4>Templates by context</h4>
                    <table class="nova-meta-table">
                        <tr><th>Home</th><td>WebSite + Organization</td></tr>
                        <tr><th>Post</th><td>BlogPosting</td></tr>
                        <tr><th>Case Study</th><td>Article (CaseStudy)</td></tr>
                        <tr><th>Testimonial</th><td>Review</td></tr>
                        <tr><th>Service</th><td>Service</td></tr>
                        <tr><th>FAQ page</th><td>FAQPage + WebPage</td></tr>
                        <tr><th>Archive</th><td>CollectionPage</td></tr>
                        <tr><th>Page</th><td>WebPage</td></tr>
                    </table>
                </div>
                <div class="nova-meta-item">
                    <h4>Overrides</h4>
                    <p class="nova-meta-example" style="border-top:none;padding-top:0;">Use the <em>Nova Schema</em> metabox on each post/page to set a custom description, add FAQ pairs, or disable schema for that URL.</p>
                </div>
                <div class="nova-meta-item">
                    <h4>Filter hooks</h4>
                    <p class="nova-meta-example" style="border-top:none;padding-top:0;">
                        <code>nova_schema_enabled</code><br>
                        <code>nova_schema_org_types</code><br>
                        <code>nova_schema_organization_node</code><br>
                        <code>nova_schema_website_node</code><br>
                        <code>nova_schema_page_node</code><br>
                        <code>nova_schema_graph</code>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <style>
        .nova-schema-settings-wrap { display:flex; gap:30px; align-items:flex-start; margin-top:20px; }
        .nova-schema-settings-main { flex:1; max-width:720px; }
        .nova-schema-settings-sidebar { width:340px; flex-shrink:0; }
        .nova-schema-sameas-row { display:flex; gap:8px; align-items:center; margin-bottom:6px; }
        .nova-schema-sameas-row input { flex:1; }
        .nova-schema-media-preview { margin-bottom:8px; }
        @media screen and (max-width:1200px) {
            .nova-schema-settings-wrap { flex-direction:column; }
            .nova-schema-settings-sidebar { width:100%; max-width:720px; }
        }
    </style>
    <?php
}

/**
 * Enqueue the media library + schema admin JS on the Nova Core settings page.
 *
 * Only loaded when the Schema tab is active.
 *
 * @param string $hook Current admin page hook.
 */
add_action('admin_enqueue_scripts', 'nova_schema_enqueue_settings_assets');
function nova_schema_enqueue_settings_assets($hook) {
    if ($hook !== 'settings_page_nova-core-settings') {
        return;
    }
    $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : '';
    if ($tab !== 'schema') {
        return;
    }
    wp_enqueue_media();
    wp_add_inline_script('jquery-core', nova_schema_settings_inline_js());
}

/**
 * Inline JS for the Schema settings tab: media picker + sameAs repeater.
 *
 * @return string
 */
function nova_schema_settings_inline_js() {
    return <<<JS
(function($){
    $(function(){
        // Media picker
        var frame;
        $(document).on('click', '.nova-schema-media-pick', function(e){
            e.preventDefault();
            var wrap = $(this).closest('.nova-schema-media');
            var inputId = wrap.data('target');
            var input = $('#' + inputId);
            if (frame) { frame.off('select'); }
            frame = wp.media({ title: 'Select logo', button: { text: 'Use logo' }, multiple: false, library: { type: 'image' } });
            frame.on('select', function(){
                var att = frame.state().get('selection').first().toJSON();
                input.val(att.id);
                var img = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
                var preview = wrap.find('.nova-schema-media-preview');
                preview.html('<img src="' + img + '" alt="" style="max-width:200px;height:auto;display:block;">').show();
                wrap.find('.nova-schema-media-clear').show();
            });
            frame.open();
        });
        $(document).on('click', '.nova-schema-media-clear', function(e){
            e.preventDefault();
            var wrap = $(this).closest('.nova-schema-media');
            $('#' + wrap.data('target')).val('');
            wrap.find('.nova-schema-media-preview').hide().empty();
            $(this).hide();
        });

        // sameAs repeater
        $(document).on('click', '.nova-schema-sameas-add', function(e){
            e.preventDefault();
            var wrap = $(this).closest('.nova-schema-sameas-wrap');
            var row = '<div class="nova-schema-sameas-row">'
                + '<input type="url" class="regular-text" name="nova_core_schema_options[same_as][]" value="" placeholder="https://..." />'
                + ' <button type="button" class="button-link nova-schema-sameas-remove">Remove</button></div>';
            wrap.find('p').first().before(row);
        });
        $(document).on('click', '.nova-schema-sameas-remove', function(e){
            e.preventDefault();
            $(this).closest('.nova-schema-sameas-row').remove();
        });
    });
})(jQuery);
JS;
}


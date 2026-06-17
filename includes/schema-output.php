<?php
/**
 * Nova Schema Output
 *
 * Builds and emits the @graph JSON-LD for the current request.
 *
 * Detection rules:
 * - Home page                → WebSite + Organization
 * - Blog single post         → BlogPosting (+ FAQPage when FAQ pairs exist)
 * - Case study (case-studies)→ Article with @type CaseStudy
 * - Testimonial (testimonial)→ Review
 * - Service (services)       → Service
 * - Blog index / archives    → CollectionPage
 * - Standard page            → WebPage (+ FAQPage when FAQ pairs exist)
 *
 * Every page emits: WebSite + Organization + the page-specific node,
 * cross-referenced by @id fragments on the site URL.
 *
 * @package Nova_Core
 */

defined('ABSPATH') || exit;

/* -------------------------------------------------------------------------
 * Helpers
 * ------------------------------------------------------------------------- */

/**
 * Strip tags, decode entities and collapse whitespace.
 *
 * Schema output must never contain raw HTML entities; this normalises any
 * value pulled from post content, excerpts or user-supplied fields.
 *
 * @param mixed $text Input value.
 * @return string
 */
function nova_schema_clean_text($text) {
    if ($text === null || $text === false) {
        return '';
    }
    $text = (string) $text;
    $text = wp_strip_all_tags($text, true);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim($text);
}

/**
 * Trim a string to a target character length on a word boundary.
 *
 * @param string $text    Cleaned input string.
 * @param int    $length  Target maximum length.
 * @return string
 */
function nova_schema_truncate($text, $length = 300) {
    $text = nova_schema_clean_text($text);
    if (function_exists('mb_strlen') ? mb_strlen($text) <= $length : strlen($text) <= $length) {
        return $text;
    }
    $trimmed = function_exists('mb_substr') ? mb_substr($text, 0, $length) : substr($text, 0, $length);
    $last_space = strrpos($trimmed, ' ');
    if ($last_space !== false && $last_space > $length * 0.6) {
        $trimmed = substr($trimmed, 0, $last_space);
    }
    return rtrim($trimmed, " ,.;:") . '…';
}

/**
 * Build an ImageObject node from an attachment ID using full-size dimensions.
 *
 * @param int         $attachment_id WP attachment ID.
 * @param string|null $fragment_id   Optional @id fragment for cross-referencing.
 * @return array|null
 */
function nova_schema_image_object($attachment_id, $fragment_id = null) {
    $attachment_id = (int) $attachment_id;
    if (!$attachment_id) {
        return null;
    }
    $src = wp_get_attachment_image_src($attachment_id, 'full');
    if (!$src || empty($src[0])) {
        return null;
    }
    $node = array(
        '@type'      => 'ImageObject',
        'url'        => esc_url_raw($src[0]),
        'contentUrl' => esc_url_raw($src[0]),
        'width'      => (int) $src[1],
        'height'     => (int) $src[2],
    );
    if ($fragment_id) {
        $node['@id'] = $fragment_id;
    }
    $alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
    if ($alt) {
        $node['caption'] = nova_schema_clean_text($alt);
    }
    return $node;
}

/**
 * Return the canonical site origin (no trailing slash).
 *
 * @return string
 */
function nova_schema_site_origin() {
    $opts = nova_schema_get_options();
    $url = !empty($opts['org_url']) ? $opts['org_url'] : home_url('/');
    return untrailingslashit($url);
}

/**
 * Return the canonical URL for the current request.
 *
 * @return string
 */
function nova_schema_current_url() {
    if (is_singular()) {
        $permalink = get_permalink();
        if ($permalink) {
            return $permalink;
        }
    }
    global $wp;
    return home_url(add_query_arg(array(), $wp ? $wp->request : ''));
}

/* -------------------------------------------------------------------------
 * Shared @graph nodes (Organization, WebSite, Logo, ContactPoint, Author)
 * ------------------------------------------------------------------------- */

/**
 * @return string Fragment URI for the Organization node.
 */
function nova_schema_org_id() {
    return nova_schema_site_origin() . '/#organization';
}

/**
 * @return string Fragment URI for the WebSite node.
 */
function nova_schema_website_id() {
    return nova_schema_site_origin() . '/#website';
}

/**
 * @return string Fragment URI for the organisation logo ImageObject node.
 */
function nova_schema_logo_id() {
    return nova_schema_site_origin() . '/#logo';
}

/**
 * Build the Organization @graph node (co-typed with the chosen business type).
 *
 * @return array
 */
function nova_schema_organization_node() {
    $opts = nova_schema_get_options();
    $types = array('Organization');
    if (!empty($opts['org_type']) && $opts['org_type'] !== 'Organization') {
        $types[] = $opts['org_type'];
    }

    $node = array(
        '@type' => count($types) === 1 ? $types[0] : $types,
        '@id'   => nova_schema_org_id(),
        'name'  => nova_schema_clean_text($opts['org_name']),
        'url'   => nova_schema_site_origin() . '/',
    );

    // Logo
    $logo_node = null;
    if (!empty($opts['org_logo_id'])) {
        $logo_node = nova_schema_image_object((int) $opts['org_logo_id'], nova_schema_logo_id());
    } elseif (!empty($opts['org_logo_url'])) {
        $logo_node = array(
            '@type'      => 'ImageObject',
            '@id'        => nova_schema_logo_id(),
            'url'        => esc_url_raw($opts['org_logo_url']),
            'contentUrl' => esc_url_raw($opts['org_logo_url']),
        );
    }
    if ($logo_node) {
        $node['logo'] = $logo_node;
        $node['image'] = array('@id' => nova_schema_logo_id());
    }

    if (!empty($opts['org_phone'])) {
        $node['telephone'] = nova_schema_clean_text($opts['org_phone']);
    }
    if (!empty($opts['org_email'])) {
        $node['email'] = sanitize_email($opts['org_email']);
    }

    // Address — addressCountry is normalised to an ISO 3166-1 alpha-2 code
    // when a recognised full country name is supplied.
    $address = array_filter(array(
        'streetAddress'   => nova_schema_clean_text($opts['addr_street']),
        'addressLocality' => nova_schema_clean_text($opts['addr_city']),
        'addressRegion'   => nova_schema_clean_text($opts['addr_state']),
        'postalCode'      => nova_schema_clean_text($opts['addr_postcode']),
        'addressCountry'  => nova_schema_country_code($opts['addr_country']),
    ), 'strlen');
    if (!empty($address)) {
        $address['@type'] = 'PostalAddress';
        $node['address'] = $address;
    }

    // Geo
    if ($opts['geo_lat'] !== '' && $opts['geo_lng'] !== '') {
        $node['geo'] = array(
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float) $opts['geo_lat'],
            'longitude' => (float) $opts['geo_lng'],
        );
    }

    // sameAs
    if (!empty($opts['same_as'])) {
        $node['sameAs'] = array_values(array_map('esc_url_raw', $opts['same_as']));
    }

    // ContactPoint (only meaningful when we have a phone or email).
    // Emitted as a single object — schema.org accepts either an object or an
    // array; a single-item array reads as noise to validators.
    if (!empty($node['telephone']) || !empty($node['email'])) {
        $cp = array(
            '@type'       => 'ContactPoint',
            'contactType' => 'customer service',
        );
        if (!empty($node['telephone'])) {
            $cp['telephone'] = $node['telephone'];
        }
        if (!empty($node['email'])) {
            $cp['email'] = $node['email'];
        }
        $node['contactPoint'] = $cp;
    }

    return apply_filters('nova_schema_organization_node', $node, $opts);
}

/**
 * Resolve an addressCountry value to an ISO 3166-1 alpha-2 code.
 *
 * Accepts an existing 2-letter code (passed through uppercased) or a known
 * full country name. Unknown values are returned unchanged so a custom string
 * can still flow through.
 *
 * @param string $value Raw country value from settings.
 * @return string
 */
function nova_schema_country_code($value) {
    $value = nova_schema_clean_text($value);
    if ($value === '') {
        return '';
    }
    if (strlen($value) === 2 && ctype_alpha($value)) {
        return strtoupper($value);
    }
    $map = apply_filters('nova_schema_country_code_map', array(
        'australia' => 'AU', 'new zealand' => 'NZ', 'united states' => 'US',
        'united states of america' => 'US', 'usa' => 'US', 'united kingdom' => 'GB',
        'uk' => 'GB', 'great britain' => 'GB', 'canada' => 'CA', 'ireland' => 'IE',
        'germany' => 'DE', 'france' => 'FR', 'spain' => 'ES', 'italy' => 'IT',
        'netherlands' => 'NL', 'belgium' => 'BE', 'sweden' => 'SE', 'norway' => 'NO',
        'denmark' => 'DK', 'finland' => 'FI', 'switzerland' => 'CH', 'austria' => 'AT',
        'poland' => 'PL', 'portugal' => 'PT', 'japan' => 'JP', 'china' => 'CN',
        'india' => 'IN', 'singapore' => 'SG', 'hong kong' => 'HK', 'south africa' => 'ZA',
        'brazil' => 'BR', 'mexico' => 'MX', 'argentina' => 'AR',
    ));
    $key = strtolower($value);
    return isset($map[$key]) ? $map[$key] : $value;
}

/**
 * Build the WebSite @graph node.
 *
 * Prefers the Nova Schema org_name setting over `get_bloginfo('name')`,
 * which can fall back to the bare domain on sites that never set a title.
 * The description is omitted when empty so validators don't flag a stub.
 *
 * @return array
 */
function nova_schema_website_node() {
    $opts   = nova_schema_get_options();
    $origin = nova_schema_site_origin();

    $name = nova_schema_clean_text(!empty($opts['org_name']) ? $opts['org_name'] : '');
    if ($name === '') {
        $name = nova_schema_clean_text(get_bloginfo('name'));
    }
    $host = wp_parse_url($origin, PHP_URL_HOST);
    if ($host && strcasecmp($name, $host) === 0) {
        // Site title is just the domain — fall back to a stripped version.
        $name = nova_schema_clean_text(preg_replace('/^www\./i', '', $host));
    }

    $node = array(
        '@type'      => 'WebSite',
        '@id'        => nova_schema_website_id(),
        'url'        => $origin . '/',
        'name'       => $name,
        'publisher'  => array('@id' => nova_schema_org_id()),
        'inLanguage' => str_replace('_', '-', get_locale()),
    );

    $description = nova_schema_clean_text(get_bloginfo('description'));
    if ($description !== '') {
        $node['description'] = $description;
    }

    return apply_filters('nova_schema_website_node', $node);
}

/**
 * Build the Person node for a post author.
 *
 * Includes a stable @id derived from the author URL (or a deterministic
 * fallback) so the node can be referenced by other graph entries.
 *
 * @param int $author_id WP user ID.
 * @return array
 */
function nova_schema_author_node($author_id) {
    $author_id = (int) $author_id;
    $name = $author_id ? get_the_author_meta('display_name', $author_id) : '';
    $url  = $author_id ? get_author_posts_url($author_id) : '';
    $url  = $url ? $url : nova_schema_site_origin() . '/';
    $id   = $author_id
        ? untrailingslashit($url) . '#person-' . $author_id
        : nova_schema_site_origin() . '/#person';
    return array(
        '@type' => 'Person',
        '@id'   => $id,
        'name'  => nova_schema_clean_text($name),
        'url'   => esc_url_raw($url),
    );
}

/* -------------------------------------------------------------------------
 * Per-post overrides + type detection
 * ------------------------------------------------------------------------- */

/**
 * Resolve Bricks global class IDs to their registered names.
 *
 * Bricks stores applied classes as an array of opaque IDs in
 * `settings._cssGlobalClasses`. The human-readable names live in the
 * `bricks_global_classes` option as `[ ['id' => ..., 'name' => ...], ... ]`.
 *
 * @return array<string,string> Map of id → name.
 */
function nova_schema_bricks_global_class_map() {
    static $map = null;
    if ($map === null) {
        $map = array();
        $raw = get_option('bricks_global_classes', array());
        if (is_array($raw)) {
            foreach ($raw as $entry) {
                if (!empty($entry['id']) && !empty($entry['name'])) {
                    $map[ $entry['id'] ] = $entry['name'];
                }
            }
        }
    }
    return $map;
}

/**
 * Load Bricks element data for a post, trying all known meta keys.
 *
 * Bricks has used different meta keys across versions:
 * - `_bricks_page_content_2`  current key for page/post content
 * - `_bricks_data`            older versions and template posts
 *
 * @param int $post_id Post ID.
 * @return array|null Decoded element array, or null when not found.
 */
function nova_schema_load_bricks_elements($post_id) {
    foreach (array('_bricks_page_content_2', '_bricks_data') as $key) {
        $raw = get_post_meta($post_id, $key, true);
        if (!$raw) {
            continue;
        }
        $elements = is_string($raw) ? json_decode($raw, true) : $raw;
        if (is_array($elements) && !empty($elements)) {
            return $elements;
        }
    }
    return null;
}

/**
 * Return true when a Bricks element carries the given CSS class name.
 *
 * Checks both storage locations:
 * - `settings._cssGlobalClasses`  array of global class IDs (current Bricks)
 * - `settings._cssClasses`        plain-text class string (older Bricks)
 *
 * @param array  $el         Bricks element array.
 * @param string $class_name CSS class name to look for.
 * @return bool
 */
function nova_schema_bricks_element_has_class($el, $class_name) {
    // Global class IDs → look up names via the registry.
    if (!empty($el['settings']['_cssGlobalClasses']) && is_array($el['settings']['_cssGlobalClasses'])) {
        $class_map = nova_schema_bricks_global_class_map();
        foreach ($el['settings']['_cssGlobalClasses'] as $id) {
            if (isset($class_map[$id]) && $class_map[$id] === $class_name) {
                return true;
            }
        }
    }
    // Plain-text class string (fallback for older Bricks or manually typed classes).
    if (!empty($el['settings']['_cssClasses'])) {
        $plain = (string) $el['settings']['_cssClasses'];
        if (in_array($class_name, preg_split('/\s+/', trim($plain)), true)) {
            return true;
        }
    }
    return false;
}

/**
 * Extract FAQ pairs from a page's Bricks Builder accordion element.
 *
 * Tries all known Bricks meta keys and item-array keys. When multiple
 * accordions exist, one with the CSS class `nova-faq` is preferred;
 * otherwise the first accordion found is used.
 *
 * @param int $post_id Post ID.
 * @return array Array of [ ['q' => string, 'a' => string], ... ]
 */
function nova_schema_extract_bricks_faqs($post_id) {
    $elements = nova_schema_load_bricks_elements($post_id);
    if ($elements === null) {
        return array();
    }

    $tagged = null;
    $first  = null;

    foreach ($elements as $el) {
        if (!isset($el['name']) || $el['name'] !== 'accordion') {
            continue;
        }
        if ($first === null) {
            $first = $el;
        }
        if (nova_schema_bricks_element_has_class($el, 'nova-faq')) {
            $tagged = $el;
            break;
        }
    }

    $accordion = $tagged !== null ? $tagged : $first;
    if ($accordion === null) {
        return array();
    }

    // Bricks stores accordion items under 'accordions' (current) or 'items' (older).
    $items = array();
    foreach (array('accordions', 'items') as $key) {
        if (!empty($accordion['settings'][$key]) && is_array($accordion['settings'][$key])) {
            $items = $accordion['settings'][$key];
            break;
        }
    }

    $faqs = array();
    foreach ($items as $item) {
        $q = isset($item['title'])   ? nova_schema_clean_text($item['title'])   : '';
        $a = isset($item['content']) ? nova_schema_clean_text($item['content']) : '';
        if ($q !== '' && $a !== '') {
            $faqs[] = array('q' => $q, 'a' => $a);
        }
    }

    return $faqs;
}

/**
 * Read per-post Nova Schema overrides as a normalised array.
 *
 * Shape: [
 *   'disabled'    => bool,
 *   'description' => string,
 *   'faqs'        => [ [ 'q' => ..., 'a' => ... ], ... ],
 * ]
 *
 * When `_nova_schema_faq_auto` is set, FAQ pairs are extracted from the
 * page's Bricks Builder accordion rather than stored meta.
 *
 * @param int $post_id Post ID (falls back to current queried object).
 * @return array
 */
function nova_schema_get_post_overrides($post_id = 0) {
    $post_id = $post_id ? (int) $post_id : (int) get_the_ID();
    $out = array(
        'disabled'    => false,
        'description' => '',
        'faqs'        => array(),
    );
    if (!$post_id) {
        return $out;
    }

    $out['disabled']    = (bool) get_post_meta($post_id, '_nova_schema_disabled', true);
    $out['description'] = nova_schema_clean_text(get_post_meta($post_id, '_nova_schema_description', true));

    if (get_post_meta($post_id, NOVA_SCHEMA_META_FAQ_AUTO, true)) {
        $out['faqs'] = nova_schema_extract_bricks_faqs($post_id);
    } else {
        $faqs = get_post_meta($post_id, '_nova_schema_faqs', true);
        if (is_array($faqs)) {
            foreach ($faqs as $pair) {
                if (!is_array($pair)) {
                    continue;
                }
                $q = isset($pair['q']) ? nova_schema_clean_text($pair['q']) : '';
                $a = isset($pair['a']) ? nova_schema_clean_text($pair['a']) : '';
                if ($q !== '' && $a !== '') {
                    $out['faqs'][] = array('q' => $q, 'a' => $a);
                }
            }
        }
    }

    return $out;
}

/**
 * Detect the schema template that applies to the current request.
 *
 * @return string One of: home, blogposting, casestudy, review, service,
 *                faqpage, collectionpage, webpage, none.
 */
function nova_schema_detect_type() {
    if (is_front_page()) {
        return 'home';
    }
    if (is_home()) {
        // Posts page (blog index).
        return 'collectionpage';
    }
    if (is_singular()) {
        $post_type = get_post_type();
        switch ($post_type) {
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
            default:
                return 'webpage';
        }
    }
    if (is_archive() || is_search()) {
        return 'collectionpage';
    }
    return 'webpage';
}

/**
 * Resolve the most relevant description for a singular post.
 *
 * Order of precedence: per-post override → excerpt → SEOPress meta description
 * → auto-generated from content.
 *
 * @param int   $post_id   Post ID.
 * @param array $overrides Output of nova_schema_get_post_overrides().
 * @return string
 */
function nova_schema_post_description($post_id, $overrides) {
    if (!empty($overrides['description'])) {
        return $overrides['description'];
    }
    $excerpt = get_post_field('post_excerpt', $post_id);
    if ($excerpt) {
        return nova_schema_truncate($excerpt, 300);
    }
    $seopress = get_post_meta($post_id, '_seopress_titles_desc', true);
    if ($seopress) {
        return nova_schema_clean_text($seopress);
    }
    $content = get_post_field('post_content', $post_id);
    return nova_schema_truncate($content, 300);
}

/**
 * Build the WebPage / page-context node for the current request.
 *
 * When the singular post has a featured image, the WebPage node is the
 * canonical home for `primaryImageOfPage` / `image` — not the content
 * node (BlogPosting / Article) which only references it back.
 *
 * @param string $url      Canonical URL.
 * @param string $name     Page name (post title or archive title).
 * @param string $desc     Cleaned description.
 * @param string $type     Schema @type (WebPage, CollectionPage, etc.).
 * @return array
 */
function nova_schema_page_node($url, $name, $desc, $type = 'WebPage') {
    $node = array(
        '@type'      => $type,
        '@id'        => $url . '#webpage',
        'url'        => esc_url_raw($url),
        'name'       => nova_schema_clean_text($name),
        'isPartOf'   => array('@id' => nova_schema_website_id()),
        'inLanguage' => str_replace('_', '-', get_locale()),
    );
    if ($desc !== '') {
        $node['description'] = $desc;
    }
    if (is_singular()) {
        $post_id = (int) get_the_ID();
        $node['datePublished'] = get_the_date('c');
        $node['dateModified']  = get_the_modified_date('c');

        $thumb_id = (int) get_post_thumbnail_id($post_id);
        if ($thumb_id) {
            $img = nova_schema_image_object($thumb_id, $url . '#primaryimage');
            if ($img) {
                $node['primaryImageOfPage'] = array('@id' => $url . '#primaryimage');
                $node['image']              = array('@id' => $url . '#primaryimage');
            }
        }
    }
    return apply_filters('nova_schema_page_node', $node, $type);
}

/* -------------------------------------------------------------------------
 * Template node builders
 * ------------------------------------------------------------------------- */

/**
 * BlogPosting node for standard `post` content.
 *
 * @param int   $post_id   Post ID.
 * @param array $overrides Per-post overrides.
 * @return array
 */
function nova_schema_blogposting_node($post_id, $overrides) {
    $url  = get_permalink($post_id);
    $node = array(
        '@type'            => 'BlogPosting',
        '@id'              => $url . '#blogposting',
        'mainEntityOfPage' => array('@id' => $url . '#webpage'),
        'headline'         => nova_schema_clean_text(get_the_title($post_id)),
        'description'      => nova_schema_post_description($post_id, $overrides),
        'datePublished'    => get_the_date('c', $post_id),
        'dateModified'     => get_the_modified_date('c', $post_id),
        'author'           => nova_schema_author_node(get_post_field('post_author', $post_id)),
        'publisher'        => array('@id' => nova_schema_org_id()),
        'inLanguage'       => str_replace('_', '-', get_locale()),
        'url'              => esc_url_raw($url),
    );

    // Reference the featured image owned by the WebPage node — do not
    // duplicate primaryImageOfPage here, that belongs on WebPage.
    $thumb_id = (int) get_post_thumbnail_id($post_id);
    if ($thumb_id) {
        $img = nova_schema_image_object($thumb_id, $url . '#primaryimage');
        if ($img) {
            $node['image']        = array('@id' => $url . '#primaryimage');
            $node['thumbnailUrl'] = $img['url'];
        }
    }

    // articleSection: skip the WordPress default "Uncategorized" placeholder
    // (and any localised equivalents matching the slug) so it doesn't leak
    // into structured data.
    $cats = get_the_category($post_id);
    if (!empty($cats)) {
        $section = array();
        foreach ($cats as $c) {
            if (!empty($c->slug) && $c->slug === 'uncategorized') {
                continue;
            }
            $name = nova_schema_clean_text($c->name);
            if ($name === '' || strcasecmp($name, 'Uncategorized') === 0) {
                continue;
            }
            $section[] = $name;
        }
        if (!empty($section)) {
            $node['articleSection'] = $section;
        }
    }

    $tags = get_the_tags($post_id);
    if (!empty($tags) && !is_wp_error($tags)) {
        $keywords = array();
        foreach ($tags as $t) {
            $keywords[] = nova_schema_clean_text($t->name);
        }
        $node['keywords'] = implode(', ', $keywords);
    }

    return $node;
}

/**
 * Article node co-typed as CaseStudy for the case-studies CPT.
 *
 * @param int   $post_id   Post ID.
 * @param array $overrides Per-post overrides.
 * @return array
 */
function nova_schema_casestudy_node($post_id, $overrides) {
    $url  = get_permalink($post_id);
    $node = array(
        '@type'            => array('Article', 'CaseStudy'),
        '@id'              => $url . '#article',
        'mainEntityOfPage' => array('@id' => $url . '#webpage'),
        'headline'         => nova_schema_clean_text(get_the_title($post_id)),
        'description'      => nova_schema_post_description($post_id, $overrides),
        'datePublished'    => get_the_date('c', $post_id),
        'dateModified'     => get_the_modified_date('c', $post_id),
        'author'           => nova_schema_author_node(get_post_field('post_author', $post_id)),
        'publisher'        => array('@id' => nova_schema_org_id()),
        'inLanguage'       => str_replace('_', '-', get_locale()),
        'url'              => esc_url_raw($url),
        'about'            => array('@id' => nova_schema_org_id()),
    );

    // Reference the WebPage's primary image rather than redefining it.
    $thumb_id = (int) get_post_thumbnail_id($post_id);
    if ($thumb_id) {
        $img = nova_schema_image_object($thumb_id, null);
        if ($img) {
            $node['image']        = array('@id' => $url . '#primaryimage');
            $node['thumbnailUrl'] = $img['url'];
        }
    }
    return $node;
}

/**
 * Service node for the services CPT.
 *
 * @param int   $post_id   Post ID.
 * @param array $overrides Per-post overrides.
 * @return array
 */
function nova_schema_service_node($post_id, $overrides) {
    $url  = get_permalink($post_id);
    $node = array(
        '@type'       => 'Service',
        '@id'         => $url . '#service',
        'name'        => nova_schema_clean_text(get_the_title($post_id)),
        'description' => nova_schema_post_description($post_id, $overrides),
        'url'         => esc_url_raw($url),
        'provider'    => array('@id' => nova_schema_org_id()),
        'serviceType' => nova_schema_clean_text(get_the_title($post_id)),
    );

    if (get_post_thumbnail_id($post_id)) {
        $node['image'] = array('@id' => $url . '#primaryimage');
    }

    // Optional area served — falls back to organisation address.
    $opts = nova_schema_get_options();
    if (!empty($opts['addr_city']) || !empty($opts['addr_country'])) {
        $area = array_filter(array(
            '@type'           => 'Place',
            'name'            => nova_schema_clean_text(
                trim(($opts['addr_city'] ?? '') . ' ' . ($opts['addr_country'] ?? ''))
            ),
        ), 'strlen');
        if (!empty($area['name'])) {
            $node['areaServed'] = $area;
        }
    }

    return $node;
}

/**
 * Review node for the testimonial CPT.
 *
 * Pulls reviewer name/role from post meta when present (falls back to title)
 * and uses the post content as the reviewBody.
 *
 * @param int   $post_id   Post ID.
 * @param array $overrides Per-post overrides.
 * @return array
 */
function nova_schema_review_node($post_id, $overrides) {
    $url = get_permalink($post_id);

    $body = nova_schema_clean_text(get_post_field('post_content', $post_id));
    if (!empty($overrides['description'])) {
        $body = $overrides['description'];
    }

    $author_name = get_post_meta($post_id, 'testimonial_author', true);
    if (!$author_name) {
        $author_name = get_post_meta($post_id, '_nova_testimonial_author', true);
    }
    if (!$author_name) {
        $author_name = get_the_title($post_id);
    }
    $author_name = nova_schema_clean_text($author_name);

    $author_role = get_post_meta($post_id, 'testimonial_role', true);
    if (!$author_role) {
        $author_role = get_post_meta($post_id, '_nova_testimonial_role', true);
    }
    $author_role = nova_schema_clean_text($author_role);

    $rating = get_post_meta($post_id, 'testimonial_rating', true);
    if (!$rating) {
        $rating = get_post_meta($post_id, '_nova_testimonial_rating', true);
    }

    // Reviewer Person node. @id is derived from the testimonial post URL so
    // the node is uniquely identifiable in the @graph (matches the BlogPosting
    // / Article author treatment in nova_schema_author_node()).
    $author = array(
        '@type' => 'Person',
        '@id'   => $url . '#reviewer',
        'name'  => $author_name !== '' ? $author_name : 'Anonymous',
    );
    if ($author_role !== '') {
        $author['jobTitle'] = $author_role;
    }

    $node = array(
        '@type'        => 'Review',
        '@id'          => $url . '#review',
        'url'          => esc_url_raw($url),
        'reviewBody'   => $body,
        'datePublished' => get_the_date('c', $post_id),
        'author'       => $author,
        'itemReviewed' => array('@id' => nova_schema_org_id()),
        'publisher'    => array('@id' => nova_schema_org_id()),
    );

    if (is_numeric($rating) && (float) $rating > 0) {
        $node['reviewRating'] = array(
            '@type'       => 'Rating',
            'ratingValue' => (float) $rating,
            'bestRating'  => 5,
            'worstRating' => 1,
        );
    }

    return $node;
}

/**
 * FAQPage node built from override Q/A pairs.
 *
 * @param string $url  Canonical URL.
 * @param array  $faqs Array of [ ['q' => ..., 'a' => ...], ... ].
 * @return array
 */
function nova_schema_faqpage_node($url, $faqs) {
    $entities = array();
    foreach ($faqs as $pair) {
        $entities[] = array(
            '@type'          => 'Question',
            'name'           => $pair['q'],
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => $pair['a'],
            ),
        );
    }
    return array(
        '@type'      => 'FAQPage',
        '@id'        => $url . '#faqpage',
        'url'        => esc_url_raw($url),
        'mainEntity' => $entities,
        'isPartOf'   => array('@id' => nova_schema_website_id()),
    );
}

/**
 * CollectionPage node for archives, blog index, and search results.
 *
 * @param string $url  Canonical URL.
 * @param string $name Display name.
 * @param string $desc Description.
 * @return array
 */
function nova_schema_collectionpage_node($url, $name, $desc) {
    $node = array(
        '@type'      => 'CollectionPage',
        '@id'        => $url . '#collectionpage',
        'url'        => esc_url_raw($url),
        'name'       => nova_schema_clean_text($name),
        'isPartOf'   => array('@id' => nova_schema_website_id()),
        'inLanguage' => str_replace('_', '-', get_locale()),
    );
    if ($desc !== '') {
        $node['description'] = $desc;
    }
    return $node;
}

/**
 * Resolve a friendly name + description for the current archive context.
 *
 * @return array [ 'name' => string, 'desc' => string ]
 */
function nova_schema_archive_meta() {
    $name = '';
    $desc = '';
    if (is_category() || is_tag() || is_tax()) {
        $term = get_queried_object();
        if ($term && !is_wp_error($term)) {
            $name = $term->name;
            $desc = nova_schema_clean_text($term->description);
        }
    } elseif (is_post_type_archive()) {
        $pto = get_queried_object();
        if ($pto) {
            $name = isset($pto->labels->name) ? $pto->labels->name : $pto->name;
        }
    } elseif (is_author()) {
        $author = get_queried_object();
        if ($author) {
            $name = sprintf('Articles by %s', $author->display_name);
        }
    } elseif (is_search()) {
        $name = sprintf('Search results for "%s"', get_search_query());
    } elseif (is_home()) {
        $name = nova_schema_clean_text(get_bloginfo('name')) . ' — Blog';
        $desc = nova_schema_clean_text(get_bloginfo('description'));
    }
    return array('name' => $name, 'desc' => $desc);
}

/* -------------------------------------------------------------------------
 * Graph builder + wp_head emission
 * ------------------------------------------------------------------------- */

/**
 * Build the full @graph for the current request.
 *
 * Always includes WebSite + Organization, then the template-specific node(s).
 * Singular pages also get a WebPage / CollectionPage container that the
 * primary content node references via mainEntityOfPage.
 *
 * @return array|null Null when output is disabled for this request.
 */
function nova_schema_build_graph() {
    $opts = nova_schema_get_options();
    if (empty($opts['enabled'])) {
        return null;
    }

    $type = nova_schema_detect_type();
    $type = apply_filters('nova_schema_detected_type', $type);

    $post_id   = is_singular() ? (int) get_the_ID() : 0;
    $overrides = nova_schema_get_post_overrides($post_id);
    if ($overrides['disabled']) {
        return null;
    }

    $graph = array(
        nova_schema_organization_node(),
        nova_schema_website_node(),
    );

    if ($type === 'home') {
        return apply_filters('nova_schema_graph', $graph, $type, $post_id);
    }

    $url = nova_schema_current_url();

    if (is_singular()) {
        $title = get_the_title($post_id);
        $desc  = nova_schema_post_description($post_id, $overrides);
        $page_type = 'WebPage';

        switch ($type) {
            case 'blogposting':
                $graph[] = nova_schema_page_node($url, $title, $desc, $page_type);
                $graph[] = nova_schema_blogposting_node($post_id, $overrides);
                break;
            case 'casestudy':
                $graph[] = nova_schema_page_node($url, $title, $desc, $page_type);
                $graph[] = nova_schema_casestudy_node($post_id, $overrides);
                break;
            case 'service':
                $graph[] = nova_schema_page_node($url, $title, $desc, $page_type);
                $graph[] = nova_schema_service_node($post_id, $overrides);
                break;
            case 'review':
                $graph[] = nova_schema_page_node($url, $title, $desc, $page_type);
                $graph[] = nova_schema_review_node($post_id, $overrides);
                break;
            case 'webpage':
            default:
                $graph[] = nova_schema_page_node($url, $title, $desc, $page_type);
                break;
        }

        // Append FAQPage when overrides supply Q/A pairs (works for any singular).
        if (!empty($overrides['faqs'])) {
            $graph[] = nova_schema_faqpage_node($url, $overrides['faqs']);
        }
    } elseif ($type === 'collectionpage') {
        $meta = nova_schema_archive_meta();
        $graph[] = nova_schema_collectionpage_node($url, $meta['name'], $meta['desc']);
    } else {
        $graph[] = nova_schema_page_node($url, nova_schema_clean_text(get_bloginfo('name')), '', 'WebPage');
    }

    return apply_filters('nova_schema_graph', $graph, $type, $post_id);
}

/**
 * Emit the JSON-LD @graph in <head>.
 *
 * Uses priority 5 so it appears before most other head tags. Skips output on
 * admin, feeds, REST, and 404 responses.
 */
add_action('wp_head', 'nova_schema_output_graph', 5);
function nova_schema_output_graph() {
    if (is_admin() || is_feed() || is_404()) {
        return;
    }
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return;
    }

    /**
     * Filter to suppress Nova Schema output for the current request.
     *
     * @param bool $enabled
     */
    if (!apply_filters('nova_schema_enabled', true)) {
        return;
    }

    $graph = nova_schema_build_graph();
    if (empty($graph) || !is_array($graph)) {
        return;
    }

    $payload = array(
        '@context' => 'https://schema.org',
        '@graph'   => array_values($graph),
    );

    $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
    $json  = wp_json_encode($payload, $flags);
    if (!$json) {
        return;
    }

    echo "\n<!-- Nova Schema -->\n";
    echo '<script type="application/ld+json">' . $json . "</script>\n";
}






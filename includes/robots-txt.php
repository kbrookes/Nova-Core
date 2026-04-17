<?php
/**
 * Robots.txt Management
 *
 * Manages robots.txt output based on Nova Core site status settings.
 * Overrides WordPress default and Cloudflare managed robots.txt.
 *
 * @package Nova_Core
 */

defined('ABSPATH') || exit;

/**
 * Filter the robots.txt output
 *
 * This filter takes control of robots.txt generation based on Nova Core settings.
 * It handles search engine visibility and AI crawler visibility.
 */
add_filter('robots_txt', 'nova_core_robots_txt', 999, 2);
function nova_core_robots_txt($output, $public) {
    $options = get_option('nova_core_tracking_options', array());

    $is_production = isset($options['environment']) && $options['environment'] === 'production';
    $search_visible = isset($options['search_visibility']) ? (bool) $options['search_visibility'] : false;
    $ai_visible = isset($options['ai_visibility']) ? (bool) $options['ai_visibility'] : false;

    // Start fresh - we're taking full control
    $robots = "# Robots.txt managed by Nova Core\n";
    $robots .= "# Generated: " . current_time('Y-m-d H:i:s') . "\n\n";

    // Development mode OR search not visible = block everything
    if (!$is_production || !$search_visible) {
        $robots .= "# Site is in development mode or search visibility is disabled\n";
        $robots .= "User-agent: *\n";
        $robots .= "Disallow: /\n";
        return $robots;
    }

    // Production mode with search visibility enabled
    $robots .= "# Standard crawlers\n";
    $robots .= "User-agent: *\n";
    $robots .= "Disallow: /wp-admin/\n";
    $robots .= "Allow: /wp-admin/admin-ajax.php\n";
    $robots .= "Disallow: /wp-includes/\n";
    $robots .= "Disallow: /wp-content/plugins/\n";
    $robots .= "Disallow: /wp-content/cache/\n";
    $robots .= "Disallow: /wp-content/themes/*/assets/\n";
    $robots .= "Disallow: /cart/\n";
    $robots .= "Disallow: /checkout/\n";
    $robots .= "Disallow: /my-account/\n";
    $robots .= "Disallow: /*?add-to-cart=*\n";
    $robots .= "Disallow: /*?s=*\n";
    $robots .= "Disallow: /search/\n\n";

    // AI Crawlers section
    $ai_crawlers = array(
        'GPTBot',           // OpenAI / ChatGPT
        'ChatGPT-User',     // ChatGPT browsing
        'Claude-Web',       // Anthropic Claude
        'ClaudeBot',        // Anthropic Claude
        'PerplexityBot',    // Perplexity AI
        'Google-Extended',  // Google Gemini/Bard
        'Amazonbot',        // Amazon Alexa/AI
        'Bytespider',       // TikTok/ByteDance
        'CCBot',            // Common Crawl (used by many AI)
        'FacebookBot',      // Meta AI
        'Applebot-Extended', // Apple AI features
        'Diffbot',          // AI data extraction
        'Omgilibot',        // Webz.io AI training
        'YouBot',           // You.com AI search
    );

    if ($ai_visible) {
        $robots .= "# AI Crawlers - ALLOWED for AI/GEO search visibility\n";
        foreach ($ai_crawlers as $bot) {
            $robots .= "User-agent: {$bot}\n";
            $robots .= "Allow: /\n";
            $robots .= "Disallow: /wp-admin/\n";
            $robots .= "Disallow: /wp-includes/\n\n";
        }
    } else {
        $robots .= "# AI Crawlers - BLOCKED\n";
        foreach ($ai_crawlers as $bot) {
            $robots .= "User-agent: {$bot}\n";
            $robots .= "Disallow: /\n\n";
        }
    }

    // Sitemap
    $sitemap_url = home_url('/sitemap_index.xml');
    // Check for common sitemap plugins
    if (function_exists('JERC_STARTER_SITE')) {
        $sitemap_url = home_url('/sitemap.xml');
    }
    $robots .= "# Sitemap\n";
    $robots .= "Sitemap: {$sitemap_url}\n";

    return $robots;
}

/**
 * Override WordPress "Discourage search engines" setting
 *
 * When Nova Core is managing visibility, we override the WordPress setting
 * to prevent conflicts.
 */
add_filter('pre_option_blog_public', 'nova_core_override_blog_public');
function nova_core_override_blog_public($value) {
    $options = get_option('nova_core_tracking_options', array());

    // If Nova Core is managing this, return based on our settings
    if (isset($options['search_visibility'])) {
        $is_production = isset($options['environment']) && $options['environment'] === 'production';
        $search_visible = (bool) $options['search_visibility'];

        // Return 1 (public) or 0 (private) based on our settings
        return ($is_production && $search_visible) ? '1' : '0';
    }

    // Fall back to WordPress setting
    return $value;
}

/**
 * Add admin notice when WordPress Reading settings conflict
 */
add_action('admin_notices', 'nova_core_robots_admin_notice');
function nova_core_robots_admin_notice() {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'options-reading') {
        $options = get_option('nova_core_tracking_options', array());
        if (isset($options['search_visibility'])) {
            ?>
            <div class="notice notice-info">
                <p><strong>Nova Core:</strong> Search engine visibility is managed by Nova Core. 
                <a href="<?php echo admin_url('options-general.php?page=nova-core-settings&tab=tracking'); ?>">Manage in Nova Core settings</a></p>
            </div>
            <?php
        }
    }
}

<?php
/**
 * Nova GitHub Updater
 *
 * A lightweight GitHub Release-based updater for WordPress plugins.
 * Can be copied into any plugin that needs GitHub release updates.
 *
 * @package Nova
 */

defined('ABSPATH') || exit;

/**
 * GitHub Release Updater Class
 *
 * Hooks into WordPress update system to check for plugin updates
 * from GitHub Releases.
 */
class Nova_GitHub_Updater {

    /**
     * GitHub repository owner
     *
     * @var string
     */
    private $owner;

    /**
     * GitHub repository name
     *
     * @var string
     */
    private $repo;

    /**
     * Plugin file path
     *
     * @var string
     */
    private $plugin_file;

    /**
     * Plugin basename (e.g., nova-core/nova-core.php)
     *
     * @var string
     */
    private $plugin_basename;

    /**
     * Plugin slug (e.g., nova-core)
     *
     * @var string
     */
    private $plugin_slug;

    /**
     * Cache key for transient
     *
     * @var string
     */
    private $cache_key;

    /**
     * Cache duration in seconds (6 hours)
     *
     * @var int
     */
    private $cache_duration = 21600;

    /**
     * Optional: specific asset filename pattern to look for
     *
     * @var string|null
     */
    private $asset_name;

    /**
     * GitHub API data (cached)
     *
     * @var array|null
     */
    private $github_data = null;

    /**
     * Icon URLs for plugin display
     *
     * @var array
     */
    private $icons = array();

    /**
     * Constructor
     *
     * @param string      $plugin_file Path to main plugin file (__FILE__).
     * @param string      $owner       GitHub repository owner.
     * @param string      $repo        GitHub repository name.
     * @param array       $options     Optional settings: 'asset_name', 'icons'.
     */
    public function __construct($plugin_file, $owner, $repo, $options = array()) {
        $this->plugin_file     = $plugin_file;
        $this->owner           = $owner;
        $this->repo            = $repo;
        $this->plugin_basename = plugin_basename($plugin_file);
        $this->plugin_slug     = dirname($this->plugin_basename);
        $this->cache_key       = 'nova_updater_' . md5($this->plugin_basename);

        // Handle options (backwards compatible with old asset_name string param)
        if (is_string($options)) {
            $this->asset_name = $options;
        } elseif (is_array($options)) {
            $this->asset_name = isset($options['asset_name']) ? $options['asset_name'] : null;
            $this->icons      = isset($options['icons']) ? $options['icons'] : array();
        }

        // Auto-detect icons from plugin assets folder if not provided
        if (empty($this->icons)) {
            $this->icons = $this->detect_icons();
        }

        // Only run in admin context
        if (is_admin()) {
            $this->init_hooks();
        }
    }

    /**
     * Auto-detect icon files from plugin assets folder
     *
     * @return array Icon URLs keyed by size.
     */
    private function detect_icons() {
        $icons = array();
        $plugin_dir = dirname($this->plugin_file);
        $plugin_url = plugins_url('', $this->plugin_file);

        // Check for common icon files in assets folder
        $icon_files = array(
            'svg'     => 'assets/icon.svg',
            '2x'      => 'assets/icon-256x256.png',
            '1x'      => 'assets/icon-128x128.png',
            'default' => 'assets/icon-128x128.png',
        );

        foreach ($icon_files as $key => $path) {
            if (file_exists($plugin_dir . '/' . $path)) {
                $icons[$key] = $plugin_url . '/' . $path;
            }
        }

        return $icons;
    }

    /**
     * Initialise WordPress hooks
     */
    private function init_hooks() {
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 10, 3);
        add_filter('upgrader_source_selection', array($this, 'fix_directory_name'), 10, 4);

        // Clear cache when plugin is updated
        add_action('upgrader_process_complete', array($this, 'clear_cache'), 10, 2);
    }

    /**
     * Get GitHub token from constant or environment variable
     *
     * @return string|null
     */
    private function get_token() {
        if (defined('GITHUB_UPDATER_TOKEN') && GITHUB_UPDATER_TOKEN) {
            return GITHUB_UPDATER_TOKEN;
        }

        $env_token = getenv('GITHUB_UPDATER_TOKEN');
        if ($env_token) {
            return $env_token;
        }

        return null;
    }

    /**
     * Fetch latest release data from GitHub API
     *
     * @param bool $force_refresh Force refresh from API.
     * @return array|null Release data or null on failure.
     */
    private function fetch_github_data($force_refresh = false) {
        if ($this->github_data !== null && !$force_refresh) {
            return $this->github_data;
        }

        // Check transient cache first
        if (!$force_refresh) {
            $cached = get_transient($this->cache_key);
            if ($cached !== false) {
                $this->github_data = $cached;
                return $this->github_data;
            }
        }

        $url = sprintf(
            'https://api.github.com/repos/%s/%s/releases/latest',
            rawurlencode($this->owner),
            rawurlencode($this->repo)
        );

        $args = array(
            'timeout'    => 10,
            'user-agent' => 'Nova-WordPress-Updater/1.0',
            'headers'    => array(
                'Accept' => 'application/vnd.github.v3+json',
            ),
        );

        $token = $this->get_token();
        if ($token) {
            $args['headers']['Authorization'] = 'Bearer ' . $token;
        }

        $response = wp_remote_get($url, $args);

        // Handle errors gracefully
        if (is_wp_error($response)) {
            return null;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!is_array($data) || empty($data['tag_name'])) {
            return null;
        }

        // Parse and store relevant data
        $this->github_data = array(
            'tag_name'     => $data['tag_name'],
            'version'      => $this->parse_version($data['tag_name']),
            'name'         => isset($data['name']) ? sanitize_text_field($data['name']) : '',
            'body'         => isset($data['body']) ? wp_kses_post($data['body']) : '',
            'html_url'     => isset($data['html_url']) ? esc_url_raw($data['html_url']) : '',
            'published_at' => isset($data['published_at']) ? sanitize_text_field($data['published_at']) : '',
            'zipball_url'  => isset($data['zipball_url']) ? esc_url_raw($data['zipball_url']) : '',
            'assets'       => array(),
        );

        // Parse release assets
        if (!empty($data['assets']) && is_array($data['assets'])) {
            foreach ($data['assets'] as $asset) {
                if (!empty($asset['browser_download_url']) && !empty($asset['name'])) {
                    $this->github_data['assets'][] = array(
                        'name' => sanitize_file_name($asset['name']),
                        'url'  => esc_url_raw($asset['browser_download_url']),
                        'size' => isset($asset['size']) ? absint($asset['size']) : 0,
                    );
                }
            }
        }

        // Cache the data
        set_transient($this->cache_key, $this->github_data, $this->cache_duration);

        return $this->github_data;
    }

    /**
     * Parse version from tag name (handles v1.2.3 and 1.2.3)
     *
     * @param string $tag_name Git tag name.
     * @return string Normalised version number.
     */
    private function parse_version($tag_name) {
        $version = ltrim($tag_name, 'vV');
        return sanitize_text_field($version);
    }

    /**
     * Get the download URL for the release
     *
     * Prefers attached ZIP asset, falls back to zipball_url.
     *
     * @param array $data GitHub release data.
     * @return string|null Download URL or null.
     */
    private function get_download_url($data) {
        if (empty($data)) {
            return null;
        }

        // Look for specific asset if configured
        if ($this->asset_name && !empty($data['assets'])) {
            foreach ($data['assets'] as $asset) {
                if ($asset['name'] === $this->asset_name) {
                    if ($this->validate_download_url($asset['url'])) {
                        return $asset['url'];
                    }
                }
            }
        }

        // Look for any ZIP asset
        if (!empty($data['assets'])) {
            foreach ($data['assets'] as $asset) {
                if (preg_match('/\.zip$/i', $asset['name'])) {
                    if ($this->validate_download_url($asset['url'])) {
                        return $asset['url'];
                    }
                }
            }
        }

        // Fall back to zipball_url
        if (!empty($data['zipball_url']) && $this->validate_download_url($data['zipball_url'])) {
            return $data['zipball_url'];
        }

        return null;
    }

    /**
     * Validate download URL matches expected GitHub format
     *
     * @param string $url URL to validate.
     * @return bool True if valid.
     */
    private function validate_download_url($url) {
        if (empty($url)) {
            return false;
        }

        $parsed = wp_parse_url($url);

        // Must be GitHub
        if (empty($parsed['host']) || !preg_match('/github\.com$/i', $parsed['host'])) {
            return false;
        }

        // Path must contain our owner/repo
        if (empty($parsed['path'])) {
            return false;
        }

        $expected_prefix = '/' . $this->owner . '/' . $this->repo . '/';
        if (stripos($parsed['path'], $expected_prefix) !== 0) {
            return false;
        }

        return true;
    }

    /**
     * Get current installed plugin version
     *
     * @return string|null
     */
    private function get_installed_version() {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugin_data = get_plugin_data($this->plugin_file);
        return isset($plugin_data['Version']) ? $plugin_data['Version'] : null;
    }

    /**
     * Compare versions using semantic versioning
     *
     * @param string $remote_version  Remote version.
     * @param string $current_version Current installed version.
     * @return bool True if remote is newer.
     */
    private function is_newer_version($remote_version, $current_version) {
        return version_compare($remote_version, $current_version, '>');
    }

    /**
     * Check for plugin updates (hooked to pre_set_site_transient_update_plugins)
     *
     * @param object $transient Update transient.
     * @return object Modified transient.
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $github_data = $this->fetch_github_data();
        if (!$github_data) {
            return $transient;
        }

        $current_version = $this->get_installed_version();
        if (!$current_version) {
            return $transient;
        }

        $remote_version = $github_data['version'];

        if ($this->is_newer_version($remote_version, $current_version)) {
            $download_url = $this->get_download_url($github_data);

            if ($download_url) {
                // Add token to download URL for private repos
                $download_url = $this->add_token_to_url($download_url);

                $transient->response[$this->plugin_basename] = (object) array(
                    'slug'        => $this->plugin_slug,
                    'plugin'      => $this->plugin_basename,
                    'new_version' => $remote_version,
                    'url'         => $github_data['html_url'],
                    'package'     => $download_url,
                    'icons'       => $this->icons,
                    'banners'     => array(),
                    'tested'      => '',
                    'requires'    => '',
                );
            }
        } else {
            // No update available - ensure it's in no_update
            $transient->no_update[$this->plugin_basename] = (object) array(
                'slug'        => $this->plugin_slug,
                'plugin'      => $this->plugin_basename,
                'new_version' => $current_version,
                'url'         => '',
                'package'     => '',
            );
        }

        return $transient;
    }

    /**
     * Add authentication token to download URL for private repos
     *
     * @param string $url Download URL.
     * @return string URL with token if needed.
     */
    private function add_token_to_url($url) {
        $token = $this->get_token();

        if (!$token) {
            return $url;
        }

        // For zipball URLs, add token as query param
        if (strpos($url, 'zipball') !== false || strpos($url, 'tarball') !== false) {
            return add_query_arg('access_token', $token, $url);
        }

        // For release asset URLs, use the API endpoint with token
        if (strpos($url, '/releases/download/') !== false) {
            return add_query_arg('access_token', $token, $url);
        }

        return $url;
    }

    /**
     * Provide plugin information for the "View details" modal
     *
     * @param false|object|array $result Result from plugins_api.
     * @param string             $action API action.
     * @param object             $args   API arguments.
     * @return false|object Plugin info or false.
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        if (!isset($args->slug) || $args->slug !== $this->plugin_slug) {
            return $result;
        }

        $github_data = $this->fetch_github_data();
        if (!$github_data) {
            return $result;
        }

        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugin_data = get_plugin_data($this->plugin_file);

        // Convert Markdown changelog to HTML (basic conversion)
        $changelog = $this->convert_markdown_to_html($github_data['body']);

        $info = (object) array(
            'name'              => isset($plugin_data['Name']) ? $plugin_data['Name'] : $this->repo,
            'slug'              => $this->plugin_slug,
            'version'           => $github_data['version'],
            'author'            => isset($plugin_data['AuthorName']) ? $plugin_data['AuthorName'] : '',
            'author_profile'    => 'https://github.com/' . $this->owner,
            'homepage'          => $github_data['html_url'],
            'requires'          => isset($plugin_data['RequiresWP']) ? $plugin_data['RequiresWP'] : '',
            'requires_php'      => isset($plugin_data['RequiresPHP']) ? $plugin_data['RequiresPHP'] : '',
            'downloaded'        => 0,
            'last_updated'      => $github_data['published_at'],
            'sections'          => array(
                'description' => isset($plugin_data['Description']) ? $plugin_data['Description'] : '',
                'changelog'   => $changelog,
            ),
            'download_link'     => $this->add_token_to_url($this->get_download_url($github_data)),
            'icons'             => $this->icons,
            'banners'           => array(),
        );

        return $info;
    }

    /**
     * Convert basic Markdown to HTML for changelog display
     *
     * @param string $markdown Markdown text.
     * @return string HTML.
     */
    private function convert_markdown_to_html($markdown) {
        if (empty($markdown)) {
            return '<p>No changelog available.</p>';
        }

        // Basic Markdown conversions
        $html = esc_html($markdown);

        // Convert headers
        $html = preg_replace('/^### (.+)$/m', '<h4>$1</h4>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^# (.+)$/m', '<h2>$1</h2>', $html);

        // Convert bold and italic
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);

        // Convert inline code
        $html = preg_replace('/`(.+?)`/', '<code>$1</code>', $html);

        // Convert list items
        $html = preg_replace('/^[\-\*] (.+)$/m', '<li>$1</li>', $html);

        // Wrap consecutive list items in ul
        $html = preg_replace('/(<li>.*<\/li>\n?)+/', '<ul>$0</ul>', $html);

        // Convert line breaks
        $html = nl2br($html);

        return $html;
    }

    /**
     * Fix directory name after extraction
     *
     * GitHub's zipball extracts to owner-repo-hash format.
     * This renames it to match the plugin slug.
     *
     * @param string      $source        Source directory path.
     * @param string      $remote_source Remote source path.
     * @param WP_Upgrader $upgrader      Upgrader instance.
     * @param array       $args          Extra arguments.
     * @return string|WP_Error Corrected source path or error.
     */
    public function fix_directory_name($source, $remote_source, $upgrader, $args) {
        global $wp_filesystem;

        // Only process our plugin
        if (!isset($args['plugin']) || $args['plugin'] !== $this->plugin_basename) {
            return $source;
        }

        // Check if directory name needs fixing
        $source_base = basename($source);
        if ($source_base === $this->plugin_slug) {
            return $source;
        }

        // Build new directory path
        $new_source = trailingslashit($remote_source) . $this->plugin_slug . '/';

        // Rename directory
        if ($wp_filesystem->move($source, $new_source)) {
            return $new_source;
        }

        return $source;
    }

    /**
     * Clear cache after plugin update
     *
     * @param WP_Upgrader $upgrader Upgrader instance.
     * @param array       $options  Update options.
     */
    public function clear_cache($upgrader, $options) {
        if ($options['action'] === 'update' && $options['type'] === 'plugin') {
            if (isset($options['plugins']) && is_array($options['plugins'])) {
                if (in_array($this->plugin_basename, $options['plugins'], true)) {
                    delete_transient($this->cache_key);
                    $this->github_data = null;
                }
            }
        }
    }
}

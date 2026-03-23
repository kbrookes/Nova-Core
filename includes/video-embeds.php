<?php
/**
 * Video Embeds
 *
 * Provides helper functions for YouTube and Vimeo video URLs and thumbnails.
 * Works with ACF fields and direct URLs in Bricks Builder.
 *
 * @package Nova_Core
 */

defined('ABSPATH') || exit;

/**
 * Get video URL or thumbnail from YouTube/Vimeo
 *
 * Accepts an ACF field name or direct URL. Returns the standardised URL or thumbnail.
 *
 * @param string $source ACF field name or video URL.
 * @param string $return What to return: 'url' or 'thumbnail'. Default 'url'.
 * @param int|null $post_id Post ID for ACF field lookup. Defaults to current post.
 * @return string The video URL, thumbnail URL, or empty string if invalid.
 */
function nova_get_video($source, $return = 'url', $post_id = null) {
    if (empty($source)) {
        return '';
    }

    // Determine if source is a URL or ACF field name
    $video_url = $source;
    
    if (!filter_var($source, FILTER_VALIDATE_URL)) {
        // Not a URL, try to get it from ACF
        if (function_exists('get_field')) {
            if (!$post_id) {
                $post_id = get_the_ID();
            }
            $video_url = get_field($source, $post_id);
        }
        
        if (empty($video_url)) {
            return '';
        }
    }

    // Extract video ID and platform
    $video_data = nova_parse_video_url($video_url);
    
    if (!$video_data) {
        return '';
    }

    if ($return === 'thumbnail') {
        return nova_get_video_thumbnail($video_data['platform'], $video_data['id']);
    }

    // Return standardised URL
    return nova_get_video_standard_url($video_data['platform'], $video_data['id']);
}

/**
 * Parse a video URL and extract platform and ID
 *
 * @param string $url The video URL.
 * @return array|false Array with 'platform' and 'id', or false if invalid.
 */
function nova_parse_video_url($url) {
    $url = trim($url);
    
    // YouTube patterns
    $youtube_patterns = array(
        // Standard watch URL
        '/(?:youtube\.com\/watch\?v=|youtube\.com\/watch\?.+&v=)([a-zA-Z0-9_-]{11})/',
        // Short URL
        '/youtu\.be\/([a-zA-Z0-9_-]{11})/',
        // Embed URL
        '/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
        // Shorts URL
        '/youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/',
        // Old embed URL
        '/youtube\.com\/v\/([a-zA-Z0-9_-]{11})/',
    );

    foreach ($youtube_patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return array(
                'platform' => 'youtube',
                'id'       => $matches[1],
            );
        }
    }

    // Vimeo patterns
    $vimeo_patterns = array(
        // Standard URL
        '/vimeo\.com\/(\d+)/',
        // Player embed URL
        '/player\.vimeo\.com\/video\/(\d+)/',
        // Channel/group URLs
        '/vimeo\.com\/(?:channels\/[^\/]+|groups\/[^\/]+\/videos)\/(\d+)/',
    );

    foreach ($vimeo_patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return array(
                'platform' => 'vimeo',
                'id'       => $matches[1],
            );
        }
    }

    return false;
}

/**
 * Get the standardised video URL
 *
 * @param string $platform 'youtube' or 'vimeo'.
 * @param string $id Video ID.
 * @return string The standardised URL.
 */
function nova_get_video_standard_url($platform, $id) {
    if ($platform === 'youtube') {
        return 'https://www.youtube.com/watch?v=' . $id;
    }
    
    if ($platform === 'vimeo') {
        return 'https://vimeo.com/' . $id;
    }

    return '';
}

/**
 * Get the video thumbnail URL
 *
 * @param string $platform 'youtube' or 'vimeo'.
 * @param string $id Video ID.
 * @return string The thumbnail URL.
 */
function nova_get_video_thumbnail($platform, $id) {
    if ($platform === 'youtube') {
        // YouTube provides direct thumbnail URLs
        return 'https://img.youtube.com/vi/' . $id . '/maxresdefault.jpg';
    }
    
    if ($platform === 'vimeo') {
        // Use vumbnail.com service for Vimeo thumbnails (no API key needed)
        return 'https://vumbnail.com/' . $id . '.jpg';
    }

    return '';
}

/**
 * Register Nova video dynamic data tags with Bricks Builder
 */
add_filter('bricks/dynamic_tags_list', 'nova_core_register_video_bricks_tags');
function nova_core_register_video_bricks_tags($tags) {
    $tags[] = array(
        'name'  => '{nova_video_url}',
        'label' => 'Video URL',
        'group' => 'Nova Core',
    );

    $tags[] = array(
        'name'  => '{nova_video_thumbnail}',
        'label' => 'Video Thumbnail',
        'group' => 'Nova Core',
    );

    return $tags;
}

/**
 * Render Nova video dynamic data tags in Bricks Builder
 *
 * These tags work with a 'video' or 'video_url' ACF field on the current post.
 * For custom field names, use {echo:nova_get_video('field_name')}
 */
add_filter('bricks/dynamic_data/render_tag', 'nova_core_render_video_bricks_tag', 10, 3);
function nova_core_render_video_bricks_tag($tag, $post, $context) {
    $post_id = is_object($post) ? $post->ID : $post;

    // Try common ACF field names for video
    $field_names = array('video', 'video_url', 'youtube', 'vimeo');

    if ($tag === 'nova_video_url') {
        foreach ($field_names as $field) {
            $result = nova_get_video($field, 'url', $post_id);
            if (!empty($result)) {
                return $result;
            }
        }
        return '';
    }

    if ($tag === 'nova_video_thumbnail') {
        foreach ($field_names as $field) {
            $result = nova_get_video($field, 'thumbnail', $post_id);
            if (!empty($result)) {
                return $result;
            }
        }
        return '';
    }

    return $tag;
}

/**
 * Handle Nova video dynamic data tags within content strings
 */
add_filter('bricks/dynamic_data/render_content', 'nova_core_render_video_bricks_content', 10, 3);
function nova_core_render_video_bricks_content($content, $post, $context) {
    $has_url = strpos($content, '{nova_video_url}') !== false;
    $has_thumb = strpos($content, '{nova_video_thumbnail}') !== false;

    if (!$has_url && !$has_thumb) {
        return $content;
    }

    $post_id = is_object($post) ? $post->ID : $post;
    $field_names = array('video', 'video_url', 'youtube', 'vimeo');

    if ($has_url) {
        $url = '';
        foreach ($field_names as $field) {
            $result = nova_get_video($field, 'url', $post_id);
            if (!empty($result)) {
                $url = $result;
                break;
            }
        }
        $content = str_replace('{nova_video_url}', $url, $content);
    }

    if ($has_thumb) {
        $thumb = '';
        foreach ($field_names as $field) {
            $result = nova_get_video($field, 'thumbnail', $post_id);
            if (!empty($result)) {
                $thumb = $result;
                break;
            }
        }
        $content = str_replace('{nova_video_thumbnail}', $thumb, $content);
    }

    return $content;
}


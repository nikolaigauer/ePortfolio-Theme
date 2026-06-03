<?php
/**
 * Template Filters
 * Filters queries and templates for portfolio views
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Filter the main query for portfolio views
 * Only show posts marked as portfolio posts on /portfolio/username URLs
 */
add_action('pre_get_posts', 'eportfolio_filter_portfolio_query');
function eportfolio_filter_portfolio_query($query) {
    // Only modify the main query on the frontend
    if (is_admin() || !$query->is_main_query()) {
        return;
    }
    
    // Check if this is a portfolio view
    $is_portfolio_view = get_query_var('portfolio_view');
    
    if ($is_portfolio_view && $query->is_author()) {
        // Filter to only show portfolio posts
        $query->set('meta_key', '_is_public_portfolio');
        $query->set('meta_value', '1');
        
        // Optional: Add a flag we can check in templates
        $query->set('is_portfolio_archive', true);
    }
}

/**
 * Add body class to identify portfolio views
 */
add_filter('body_class', 'eportfolio_portfolio_body_class');
function eportfolio_portfolio_body_class($classes) {
    if (get_query_var('portfolio_view')) {
        $classes[] = 'portfolio-view';
        $classes[] = 'portfolio-archive';
    }
    return $classes;
}

/**
 * Expose the resolved layout mode (single | feed) as a body class on author
 * and portfolio archives, so CSS/JS can branch on it.
 */
add_filter('body_class', 'eportfolio_layout_body_class');
function eportfolio_layout_body_class($classes) {
    if (is_author() && function_exists('eportfolio_layout_mode')) {
        $classes[] = 'eportfolio-layout-' . eportfolio_layout_mode();
    }
    return $classes;
}

/**
 * Modify the document title for portfolio views
 */
add_filter('pre_get_document_title', 'eportfolio_portfolio_title', 20);
function eportfolio_portfolio_title($title) {
    if (get_query_var('portfolio_view') && is_author()) {
        $author = get_queried_object();
        if ($author) {
            return $author->display_name . "'s Portfolio";
        }
    }
    return $title;
}

/**
 * Add a filter to check if current view is portfolio
 * Useful for template conditionals
 */
function eportfolio_is_portfolio_view() {
    return (bool) get_query_var('portfolio_view');
}

/**
 * Get portfolio post count for a user
 */
function eportfolio_get_portfolio_count($user_id) {
    $args = array(
        'author' => $user_id,
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_is_public_portfolio',
                'value' => '1',
                'compare' => '='
            )
        ),
        'fields' => 'ids', // Only get IDs for efficiency
    );
    
    $query = new WP_Query($args);
    return $query->found_posts;
}

/**
 * Serve the archive template when viewing a regular author page (non-portfolio).
 *
 * WHY get_block_template (singular) and not get_block_templates (plural):
 * WordPress 6.7+ resolves FSE page templates via WP_Block_Templates_Registry +
 * get_block_template(), which bypasses the get_block_templates filter entirely.
 * The plural filter still fires in the Site Editor (hence it looked correct there)
 * but never fired during actual front-end rendering. The singular filter fires
 * as the final step of every individual template lookup, reliably across all
 * WP versions since 5.9.
 */
add_filter('get_block_template', 'eportfolio_author_to_archive_template', 10, 3);
function eportfolio_author_to_archive_template($block_template, $id, $template_type) {

    // Only care about page templates
    if ($template_type !== 'wp_template') {
        return $block_template;
    }

    // Must have a resolved template and it must be the author template
    if (!$block_template || $block_template->slug !== 'author') {
        return $block_template;
    }

    // Only swap on regular author pages — not portfolio views
    if (!is_author() || get_query_var('portfolio_view')) {
        return $block_template;
    }

    // Prefer a DB-saved (site-editor-customised) archive template for this theme
    $customized = get_posts(array(
        'post_type'      => 'wp_template',
        'post_status'    => 'publish',
        'name'           => 'archive',
        'posts_per_page' => 1,
        'tax_query'      => array(
            array(
                'taxonomy' => 'wp_theme',
                'field'    => 'slug',
                'terms'    => get_stylesheet(),
            ),
        ),
    ));

    $swapped          = clone $block_template;
    $swapped->slug    = 'archive';
    $swapped->id      = get_stylesheet() . '//archive';
    $swapped->title   = 'Archive';

    if (!empty($customized)) {
        $swapped->content = $customized[0]->post_content;
        $swapped->source  = 'custom';
    } else {
        $archive_file = get_stylesheet_directory() . '/templates/archive.html';
        if (file_exists($archive_file)) {
            $swapped->content = file_get_contents($archive_file);
        }
    }

    return $swapped;
}

/**
 * Add portfolio info to author bio
 * Can be used in templates to show portfolio stats
 */
function eportfolio_get_author_portfolio_info($user_id = null) {
    if (!$user_id) {
        $user_id = get_the_author_meta('ID');
    }
    
    $portfolio_count = eportfolio_get_portfolio_count($user_id);
    $is_public = get_user_meta($user_id, 'portfolio_is_public', true);
    $user_data = get_userdata($user_id);
    
    return array(
        'portfolio_count' => $portfolio_count,
        'is_public' => ($is_public === '1'),
        'portfolio_url' => home_url('/portfolio/' . $user_data->user_nicename . '/'),
        'author_url' => get_author_posts_url($user_id),
    );
}
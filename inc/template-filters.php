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
 * Template domino effect: author pages use archive template
 * This allows portfolio (which uses author template) -> author -> archive
 */
add_filter('get_block_templates', 'eportfolio_author_to_archive_template', 10, 3);
function eportfolio_author_to_archive_template($query_result, $query, $template_type) {
    // Only redirect regular author pages (not portfolio views) to archive template
    if (!get_query_var('portfolio_view') && is_author()) {
        
        foreach ($query_result as $key => $template) {
            if ($template->slug === 'author') {
                // Look for archive template
                $archive_template_file = get_stylesheet_directory() . '/templates/archive.html';
                
                if (file_exists($archive_template_file)) {
                    // Create archive template object
                    $archive_template = clone $template;
                    $archive_template->slug = 'archive';
                    $archive_template->id = get_stylesheet() . '//archive';
                    $archive_template->title = 'Archive';
                    $archive_template->content = file_get_contents($archive_template_file);
                    
                    // Replace author template with archive template
                    $query_result[$key] = $archive_template;
                }
                break;
            }
        }
    }
    
    return $query_result;
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
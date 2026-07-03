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
 * Route /portfolio/ views to the dedicated "portfolio" template.
 *
 * Both /author/x and /portfolio/x are author queries, so WordPress's author
 * template hierarchy resolves both to author.html by default. That is correct
 * for /author/ (the process-archive feed lives in author.html), but /portfolio/
 * is the curated single-post view and needs its own template. We prepend the
 * "portfolio" slug to the hierarchy on portfolio views so WordPress natively
 * resolves templates/portfolio.html, while /author/ is left untouched and
 * resolves author.html on its own.
 *
 * WHY a template-hierarchy filter (and NOT a get_block_template content swap):
 * an earlier version hooked the singular `get_block_template` filter to swap
 * template content. That filter is NOT invoked by front-end resolution —
 * resolve_block_template() (WP core) fetches candidates via get_block_templates()
 * (PLURAL) and never calls the singular one — so the swap silently stopped once
 * a template was customised in the Site Editor, and both URLs rendered the same
 * template. The `{$type}_template_hierarchy` filter is the supported,
 * version-stable way to change which template wins, and it works identically for
 * file-based and DB-customised templates.
 *
 * NOTE: hierarchy entries here are PHP filenames (e.g. "author.php"); the
 * block-template resolver strips the extension before matching a block template.
 */
add_filter('author_template_hierarchy', 'eportfolio_portfolio_template_hierarchy');
function eportfolio_portfolio_template_hierarchy($templates) {
    if (get_query_var('portfolio_view')) {
        array_unshift($templates, 'portfolio.php');
    }
    return $templates;
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
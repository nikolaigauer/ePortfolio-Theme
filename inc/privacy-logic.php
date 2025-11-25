<?php
/**
 * Privacy Control Override
 * Custom privacy control for the entire site with portfolio exceptions
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Site-wide privacy control with portfolio exceptions
 * Three-level privacy hierarchy:
 * 1. Global site privacy (admin control)
 * 2. Individual portfolio privacy (student control, can penetrate global)
 * 3. Individual post privacy (student control, can penetrate portfolio + global)
 */
add_action('template_redirect', 'cohort_theme_privacy_control', 1);
function cohort_theme_privacy_control() {
    // Skip if user is logged in
    if (is_user_logged_in()) {
        return;
    }
    
    // Check global privacy setting
    $site_is_public = get_option('eportfolio_site_is_public', '0');
    
    // If site is globally public, only enforce individual privacy locks (Level 2 & 3)
    if ($site_is_public === '1') {
        // Check if we're on a portfolio page and if it's set to private
        $clean_uri = strtok($_SERVER['REQUEST_URI'], '?'); // Remove query parameters
        if (preg_match('#^/portfolio/([^/]+)/?$#', $clean_uri, $matches)) {
            $user = get_user_by('slug', $matches[1]);
            if ($user) {
                $portfolio_is_public = get_user_meta($user->ID, 'portfolio_is_public', true);
                // If explicitly set to private, block it
                if ($portfolio_is_public === '0') {
                    auth_redirect();
                }
            }
        }
        
        // Check if we're on a single post and if the author wants it private
        if (is_singular('post')) {
            $post_id = get_the_ID();
            $author_id = get_post_field('post_author', $post_id);
            $portfolio_is_public = get_user_meta($author_id, 'portfolio_is_public', true);
            // COMMENTED OUT: Portfolio metabox no longer automatically makes posts public
            // $post_is_public = get_post_meta($post_id, '_is_public_portfolio', true);
            
            // If author's portfolio is explicitly private, block the post
            // MODIFIED: Only check portfolio-level privacy, not individual post metabox
            if ($portfolio_is_public === '0') {
                auth_redirect();
            }
            
            // TODO: In future, add separate "make post public" metabox if needed
            // For now, posts are only public if the entire portfolio is public
        }
        
        // Check if we're on a page and if it's explicitly marked as private
        if (is_page()) {
            $page_id = get_the_ID();
            $page_is_public = get_post_meta($page_id, '_is_public_portfolio', true);
            
            // If page is explicitly marked as private (value is '0'), block it
            if ($page_is_public === '0') {
                auth_redirect();
            }
        }
        
        // Otherwise, allow access (site is public)
        return;
    }
    
    // Site is globally private (login required) - check for exceptions
    
    // Parse URL properly for subdirectory installs
    $request_uri = $_SERVER['REQUEST_URI'];
    $site_path = parse_url(home_url(), PHP_URL_PATH);
    if ($site_path) {
        $request_uri = str_replace($site_path, '', $request_uri);
    }
    
    // Allow the portfolio index page
    if (preg_match('#^/portfolio/?$#', $request_uri)) {
        return; // Allow access to portfolio directory
    }
    
    // Allow public portfolio endpoints (strip query parameters for pattern matching)
    $clean_uri = strtok($request_uri, '?'); // Remove query parameters
    if (preg_match('#^/portfolio/([^/]+)/?$#', $clean_uri, $matches)) {
        $user = get_user_by('slug', $matches[1]);
        if ($user && get_user_meta($user->ID, 'portfolio_is_public', true) === '1') {
            return; // Allow access
        }
    }
    
    // Allow individual posts with granular control (Level 3)
    // MODIFIED: Posts are only public if the entire portfolio is public
    // Portfolio metabox (_is_public_portfolio) now only controls portfolio inclusion, not public access
    if (is_singular('post')) {
        $post_id = get_the_ID();
        $author_id = get_post_field('post_author', $post_id);
        // COMMENTED OUT: Portfolio metabox no longer grants public access
        // $post_is_public = get_post_meta($post_id, '_is_public_portfolio', true);
        $portfolio_is_public = get_user_meta($author_id, 'portfolio_is_public', true);
        
        // Allow only if entire portfolio is public
        // MODIFIED: Removed individual post override for security
        if ($portfolio_is_public === '1') {
            return; // Allow access to this post
        }
        
        // TODO: In future, could add separate "make post public" control if needed
    }
    
    // Allow individual pages with granular control
    // A page can be public if it's explicitly marked as public
    if (is_page()) {
        $page_id = get_the_ID();
        $page_is_public = get_post_meta($page_id, '_is_public_portfolio', true);
        
        // Allow if page is explicitly marked public
        if ($page_is_public === '1') {
            return; // Allow access to this page
        }
    }
    
    // Block everything else - redirect to login
    auth_redirect();
}
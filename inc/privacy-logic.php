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
 * Two-level privacy hierarchy:
 * 1. Global site privacy (admin control)
 * 2. Individual portfolio privacy (student control)
 */
add_action('template_redirect', 'cohort_theme_privacy_control', 1);
function cohort_theme_privacy_control() {
    // Skip if user is logged in
    if (is_user_logged_in()) {
        return;
    }
    
    // Check global privacy setting
    $site_is_public = get_option('eportfolio_site_is_public', '0');
    
    // ========================================
    // GLOBALLY PUBLIC SITE - Simple rules
    // ========================================
    if ($site_is_public === '1') {

        // Rule 1: Portfolio pages follow portfolio-level privacy
        // Strip query string before matching so ?show=POST_ID URLs are handled correctly.
        $public_uri_path = strtok($_SERVER['REQUEST_URI'], '?');
        if (preg_match('#^/portfolio/([^/]+)/?$#', $public_uri_path, $matches)) {
            $user = get_user_by('slug', $matches[1]);
            if ($user) {
                $portfolio_is_public = get_user_meta($user->ID, 'portfolio_is_public', true);
                if ($portfolio_is_public === '0') {
                    auth_redirect(); // Block if portfolio is private
                }
            }
        }
        
        // Rule 2: Posts follow portfolio-level privacy
        if (is_singular('post')) {
            $post_id = get_the_ID();
            $author_id = get_post_field('post_author', $post_id);
            $portfolio_is_public = get_user_meta($author_id, 'portfolio_is_public', true);
            
            if ($portfolio_is_public === '0') {
                auth_redirect(); // Block if author's portfolio is private
            }
        }
        
        // Rule 3: All pages are public (metabox ignored)
        // No check needed - pages are public by default
        
        // Everything else is public
        return;
    }
    
    // ========================================
    // GLOBALLY PRIVATE SITE - Exception rules
    // ========================================
    
    // Parse URL properly for subdirectory installs.
    // Strip query string first so ?show=POST_ID never breaks the regex checks below.
    $request_uri = strtok($_SERVER['REQUEST_URI'], '?');
    $site_path = parse_url(home_url(), PHP_URL_PATH);
    if ($site_path) {
        $request_uri = str_replace($site_path, '', $request_uri);
    }
    
    // Exception 1: Portfolio index page is always accessible
    if (preg_match('#^/portfolio/?$#', $request_uri)) {
        return;
    }
    
    // Exception 2: Public portfolios are accessible
    if (preg_match('#^/portfolio/([^/]+)/?$#', $request_uri, $matches)) {
        $user = get_user_by('slug', $matches[1]);
        if ($user && get_user_meta($user->ID, 'portfolio_is_public', true) === '1') {
            return;
        }
    }
    
    // Exception 3: Posts are accessible if:
    //   - Post metabox is checked (portfolio inclusion), OR
    //   - Author's portfolio is public
    if (is_singular('post')) {
        $post_id = get_the_ID();
        $author_id = get_post_field('post_author', $post_id);
        $post_is_public = get_post_meta($post_id, '_is_public_portfolio', true);
        $portfolio_is_public = get_user_meta($author_id, 'portfolio_is_public', true);
        
        if ($post_is_public === '1' || $portfolio_is_public === '1') {
            return; // Allow access
        }
    }
    
    // Exception 4: Pages are accessible if metabox is checked
    if (is_page()) {
        $page_id = get_the_ID();
        $page_is_public = get_post_meta($page_id, '_is_public_portfolio', true);
        
        if ($page_is_public === '1') {
            return; // Allow access
        }
    }
    
    // Block everything else - redirect to login
    auth_redirect();
}
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

        // Rule 1: Portfolio pages follow portfolio-level privacy.
        // Detected via the portfolio_view query var (set by the rewrite rules)
        // rather than a REQUEST_URI regex, so pagination (/portfolio/x/page/2/),
        // ?show= URLs, and subdirectory installs are all covered.
        if (get_query_var('portfolio_view') && is_author()) {
            $portfolio_author = get_queried_object();
            if ($portfolio_author && isset($portfolio_author->ID)) {
                $portfolio_is_public = get_user_meta($portfolio_author->ID, 'portfolio_is_public', true);
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
    
    // Exception 2: Public portfolios are accessible.
    // Query-var check (same rationale as the public-site branch above): also
    // covers paginated portfolio URLs, which the old regex fail-closed on.
    if (get_query_var('portfolio_view') && is_author()) {
        $portfolio_author = get_queried_object();
        if ($portfolio_author && isset($portfolio_author->ID)
            && get_user_meta($portfolio_author->ID, 'portfolio_is_public', true) === '1') {
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

/**
 * REST API privacy gate.
 *
 * template_redirect (above) never fires for REST requests — the REST server
 * dispatches on parse_request and exits first. Without this, a globally
 * private site still leaks every published post's full content, the media
 * (upload URLs) list, and the user list to unauthenticated visitors via
 * /wp-json/ (and ?rest_route=). This closes that gap by requiring
 * authentication for REST reads whenever the site is private.
 *
 * Logged-in editor/admin usage (block editor, dashboard) is cookie-
 * authenticated, so is_user_logged_in() passes them through untouched.
 *
 * NOTE: On a *public* site with individual private portfolios, this filter
 * intentionally does nothing — REST stays open. Excluding private-portfolio
 * authors from public-site REST queries would need rest_post_query /
 * rest_attachment_query filters (author__not_in) and is a separate decision.
 */
add_filter('rest_authentication_errors', 'eportfolio_rest_privacy_gate');
function eportfolio_rest_privacy_gate($result) {
    // Another authentication check already ran and decided — respect it.
    if (!empty($result)) {
        return $result;
    }

    // Authenticated users (cookie/nonce or application password) pass through.
    if (is_user_logged_in()) {
        return $result;
    }

    // Public site: REST stays open (same posture as template_redirect).
    if (get_option('eportfolio_site_is_public', '0') === '1') {
        return $result;
    }

    // Private site + unauthenticated request: block the read.
    return new WP_Error(
        'rest_login_required',
        __('This site is private. Please log in to access the API.', 'eportfolio-theme'),
        array('status' => 401)
    );
}
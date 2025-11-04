<?php
/**
 * Portfolio URL Rewrite Rules
 * Handles /portfolio/username URLs
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register custom rewrite rules for portfolio URLs
 */
add_action('init', 'eportfolio_register_rewrites', 5);
function eportfolio_register_rewrites() {
    // Add rewrite rule for portfolio/username
    add_rewrite_rule(
        '^portfolio/([^/]+)/?$',
        'index.php?author_name=$matches[1]&portfolio_view=1',
        'top'
    );
    
    // Register custom query var
    add_filter('query_vars', 'eportfolio_add_query_vars');
}

/**
 * Add custom query variable
 */
function eportfolio_add_query_vars($vars) {
    $vars[] = 'portfolio_view';
    return $vars;
}

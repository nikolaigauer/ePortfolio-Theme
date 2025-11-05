<?php
/**
 * ePortfolio Child Theme Functions
 * 
 * This file loads all functionality modules from the /inc directory
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define theme constants
define('EPORTFOLIO_VERSION', '1.0.2');
define('EPORTFOLIO_DIR', get_stylesheet_directory());
define('EPORTFOLIO_URL', get_stylesheet_directory_uri());

// ⭐ Load the theme updater IMMEDIATELY (before other modules)
$updater_file = EPORTFOLIO_DIR . '/inc/theme-updater.php';
if (file_exists($updater_file)) {
    require_once $updater_file;
}

/**
 * Load functionality modules
 */
function eportfolio_load_modules() {
    $modules = array(
        'rewrite-rules',          // URL structure for /portfolio/
        'privacy-logic',          // Public/private toggle logic
        'admin-menu',             // Student dashboard menu
        'post-metabox',           // Portfolio post checkbox
        'content-type-taxonomy',  // Content Type taxonomy
        'content-type-filter',    // Content type filter menu
        'template-filters',       // Template overrides and filters
        'shortcodes',             // Dynamic shortcodes for templates
        // 'theme-updater' removed - loaded above immediately
    );
    
    foreach ($modules as $module) {
        $file = EPORTFOLIO_DIR . '/inc/' . $module . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
add_action('after_setup_theme', 'eportfolio_load_modules');

/**
 * Flush rewrite rules on theme activation
 */
function eportfolio_activate() {
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'eportfolio_activate');

/**
 * Clean up on theme deactivation
 */
function eportfolio_deactivate() {
    flush_rewrite_rules();
}
add_action('switch_theme', 'eportfolio_deactivate');
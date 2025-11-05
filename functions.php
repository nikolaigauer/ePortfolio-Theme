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
define('EPORTFOLIO_VERSION', '1.0.7');
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

  // MANUAL UPDATE TRIGGER - Remove after testing
  add_action('wp_loaded', function() {
      if (!current_user_can('administrator') || !isset($_GET['manual_update_theme'])) {
          return;
      }

      echo "<h2>🚀 Manually Triggering Theme Update...</h2>";

      // Get the update data
      $transient = get_site_transient('update_themes');
      $theme_slug = 'eportfolio-theme';

      if (isset($transient->response[$theme_slug])) {
          $update_data = $transient->response[$theme_slug];

          echo "<p><strong>✅ Update detected:</strong></p>";
          echo "<ul>";
          echo "<li>Current: 1.0.4</li>";
          echo "<li>New: " . $update_data['new_version'] . "</li>";
          echo "<li>Download: " . $update_data['package'] . "</li>";
          echo "</ul>";

          // Include WordPress update classes
          if (!class_exists('Theme_Upgrader')) {
              require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
          }

          // Create upgrader instance
          $upgrader = new Theme_Upgrader();

          echo "<p>🔄 <strong>Starting update process...</strong></p>";

          // Perform the update
          $result = $upgrader->upgrade($theme_slug);

          if (is_wp_error($result)) {
              echo "<p>❌ <strong>Error:</strong> " . $result->get_error_message() . "</p>";
          } elseif ($result === false) {
              echo "<p>❌ <strong>Update failed</strong></p>";
          } else {
              echo "<p>✅ <strong>Update completed successfully!</strong></p>";
              echo "<p>🎉 Your theme should now be version 1.0.5</p>";
          }

      } else {
          echo "<p>❌ No update data found</p>";
      }

      exit;
  });
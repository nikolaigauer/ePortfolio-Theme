<?php
/**
 * Theme Update Checker
 * 
 * Checks for theme updates from GitHub releases
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class EPortfolio_Theme_Updater {
    
    private $theme_slug;
    private $version;
    private $github_username;
    private $github_repo;
    private $update_path;
    
    public function __construct() {
        $this->theme_slug = get_option('stylesheet');
        $this->version = EPORTFOLIO_VERSION;
        $this->github_username = 'nikolaigauer';
        $this->github_repo = 'ePortfolio-Theme';
        $this->update_path = "https://{$this->github_username}.github.io/{$this->github_repo}/updates.json";
        
        add_filter('pre_set_site_transient_update_themes', array($this, 'check_for_update'));
        add_filter('upgrader_pre_download', array($this, 'download_package'), 10, 3);
        add_action('admin_notices', array($this, 'update_notice'));
    }
    
    /**
     * Check for theme updates
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // Get remote version info
        $remote_version = $this->get_remote_version();
        
        if (version_compare($this->version, $remote_version['version'], '<')) {
            $transient->response[$this->theme_slug] = array(
                'theme' => $this->theme_slug,
                'new_version' => $remote_version['version'],
                'url' => $remote_version['details_url'],
                'package' => $remote_version['download_url']
            );
        }
        
        return $transient;
    }
    
    /**
     * Get remote version information
     */
    private function get_remote_version() {
        $request = wp_remote_get($this->update_path);
        
        if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
            $body = wp_remote_retrieve_body($request);
            $data = json_decode($body, true);
            
            if ($data && isset($data['version'])) {
                return $data;
            }
        }
        
        return false;
    }
    
    /**
     * Handle the download of the update package
     */
    public function download_package($reply, $package, $upgrader) {
        if (strpos($package, 'github.com') !== false && 
            strpos($package, $this->github_repo) !== false) {
            
            $args = array(
                'headers' => array(
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress Theme Updater'
                )
            );
            
            $request = wp_remote_get($package, $args);
            
            if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
                $temp_file = download_url($package);
                return $temp_file;
            }
        }
        
        return $reply;
    }
    
    /**
     * Show admin notice about auto-update system
     */
    public function update_notice() {
        $screen = get_current_screen();
        if ($screen->id === 'themes' && !get_user_meta(get_current_user_id(), 'eportfolio_update_notice_dismissed', true)) {
            echo '<div class="notice notice-info is-dismissible" id="eportfolio-update-notice">';
            echo '<p><strong>ePortfolio Theme:</strong> This theme receives automatic updates from GitHub. ';
            echo 'You\'ll be notified here when updates are available. ';
            echo '<a href="https://github.com/nikolaigauer/ePortfolio-Theme" target="_blank">View on GitHub</a></p>';
            echo '<script>jQuery(document).on("click", "#eportfolio-update-notice .notice-dismiss", function(){ ';
            echo 'jQuery.post(ajaxurl, {action: "dismiss_eportfolio_notice", nonce: "' . wp_create_nonce('dismiss_notice') . '"}); });</script>';
            echo '</div>';
        }
    }
}

// Handle notice dismissal
add_action('wp_ajax_dismiss_eportfolio_notice', function() {
    if (wp_verify_nonce($_POST['nonce'], 'dismiss_notice')) {
        update_user_meta(get_current_user_id(), 'eportfolio_update_notice_dismissed', true);
    }
    wp_die();
});

// Initialize the updater
new EPortfolio_Theme_Updater();
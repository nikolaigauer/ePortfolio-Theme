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
        $this->github_username = 'your-github-username'; // Update this with your GitHub username
        $this->github_repo = 'eportfolio-theme'; // Update this with your repo name
        $this->update_path = "https://{$this->github_username}.github.io/{$this->github_repo}/updates.json";
        
        add_filter('pre_set_site_transient_update_themes', array($this, 'check_for_update'));
        add_filter('upgrader_pre_download', array($this, 'download_package'), 10, 3);
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
}

// Initialize the updater
new EPortfolio_Theme_Updater();
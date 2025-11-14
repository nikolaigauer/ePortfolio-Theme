<?php
/**
 * Student Dashboard Menu & Settings
 * Adds ePortfolio menu with public/private toggle
 * Includes admin-only features: author slug customization and student navigation menu
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add ePortfolio menu to admin dashboard
 */
add_action('admin_menu', 'eportfolio_add_menu');
function eportfolio_add_menu() {
    // Only show to users who can edit posts (students/contributors and up)
    if (!current_user_can('edit_posts')) {
        return;
    }
    
    add_menu_page(
        'ePortfolio',                           // Page title
        'ePortfolio',                           // Menu title
        'edit_posts',                           // Capability
        'eportfolio-settings',                  // Menu slug
        'eportfolio_render_settings_page',      // Callback function
        'dashicons-portfolio',                  // Icon
        25                                      // Position
    );
}

/**
 * Render the ePortfolio settings page with tabbed interface
 */
function eportfolio_render_settings_page() {
    $user_id = get_current_user_id();
    $user_data = get_userdata($user_id);
    $is_admin = current_user_can('manage_options');
    
    // Determine active tab
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'privacy';
    
    // Handle global privacy form submission (admin only)
    if ($is_admin && isset($_POST['save_global_privacy']) && check_admin_referer('global_privacy_action', 'global_privacy_nonce')) {
        $site_is_public = isset($_POST['site_is_public']) ? '1' : '0';
        update_option('eportfolio_site_is_public', $site_is_public);
        
        // Save cohort URL if provided
        if (isset($_POST['cohort_url'])) {
            $cohort_url = esc_url_raw($_POST['cohort_url']);
            update_option('eportfolio_cohort_url', $cohort_url);
        }
        
        echo '<div class="notice notice-success is-dismissible"><p><strong>Global privacy settings saved!</strong></p></div>';
    }
    
    // Handle author slug customization (admin only)
    if ($is_admin && isset($_POST['save_author_slug']) && check_admin_referer('author_slug_action', 'author_slug_nonce')) {
        $new_slug = sanitize_title($_POST['author_slug']);
        
        if (empty($new_slug)) {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> Author slug cannot be empty.</p></div>';
        } else {
            update_option('eportfolio_author_slug', $new_slug);
            // Flush rewrite rules to apply the change
            flush_rewrite_rules();
            echo '<div class="notice notice-success is-dismissible"><p><strong>Author slug updated!</strong> Visit <a href="' . admin_url('options-permalink.php') . '">Settings → Permalinks</a> and click Save to ensure all URLs work correctly.</p></div>';
        }
    }
    
    
    // Handle personal portfolio form submission
    if (isset($_POST['save_portfolio_toggle']) && check_admin_referer('portfolio_toggle_action', 'portfolio_toggle_nonce')) {
        $is_public = isset($_POST['portfolio_is_public']) ? '1' : '0';
        update_user_meta($user_id, 'portfolio_is_public', $is_public);
        echo '<div class="notice notice-success is-dismissible"><p><strong>Portfolio settings saved!</strong></p></div>';
    }
    
    $is_public = get_user_meta($user_id, 'portfolio_is_public', true);
    $portfolio_url = home_url('/portfolio/' . $user_data->user_nicename . '/');
    $site_is_public = get_option('eportfolio_site_is_public', '0');
    $cohort_url = get_option('eportfolio_cohort_url', home_url('/'));
    $current_author_slug = get_option('eportfolio_author_slug', 'author');
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php if ($is_admin): ?>
        <!-- Tab Navigation (Admin Only) -->
        <nav class="nav-tab-wrapper wp-clearfix" style="margin: 20px 0;">
            <a href="?page=eportfolio-settings&tab=privacy" class="nav-tab <?php echo $active_tab === 'privacy' ? 'nav-tab-active' : ''; ?>">
                Privacy Settings
            </a>
            <a href="?page=eportfolio-settings&tab=menu-builder" class="nav-tab <?php echo $active_tab === 'menu-builder' ? 'nav-tab-active' : ''; ?>">
                Menu Builder Guide
            </a>
            <a href="?page=eportfolio-settings&tab=advanced" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
                Advanced
            </a>
        </nav>
        
        <!-- Privacy Settings Tab -->
        <?php if ($active_tab === 'privacy'): ?>
        <div class="eportfolio-tab-content">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 1200px;">
                
                <!-- Global Privacy (Left) -->
                <div class="card" style="background: #fff3cd; border-left: 4px solid #ff9800;">
                    <h2 style="margin-top: 0;">Global Site Privacy</h2>
                    
                    <form method="post">
                        <?php wp_nonce_field('global_privacy_action', 'global_privacy_nonce'); ?>
                        
                        <p>
                            <label style="display: block; font-size: 16px; margin-bottom: 10px;">
                                <input type="checkbox" name="site_is_public" value="1" <?php checked($site_is_public, '1'); ?> />
                                <strong>Make entire site publicly accessible</strong>
                            </label>
                        </p>
                        
                        <div style="background: white; padding: 15px; border-left: 4px solid <?php echo ($site_is_public === '1') ? '#00a32a' : '#d63638'; ?>; margin: 15px 0;">
                            <p style="margin: 0;">
                                <strong>Current Status: 
                                    <?php if ($site_is_public === '1'): ?>
                                        <span style="color: #00a32a;">✓ Public (Open Access)</span>
                                    <?php else: ?>
                                        <span style="color: #d63638;">🔒 Private (Login Required)</span>
                                    <?php endif; ?>
                                </strong>
                            </p>
                        </div>
                        
                        <p class="description" style="margin-bottom: 15px; font-size: 12px;">
                            <strong>Private:</strong> Site requires login (students can override)<br>
                            <strong>Public:</strong> Site is open access (students can still go private)
                        </p>
                        
                        <hr style="margin: 25px 0;">
                        
                        <h3 style="margin-bottom: 10px;">Home Page Link</h3>
                        <p class="description" style="margin-bottom: 10px; font-size: 12px;">
                            Custom URL for "Back" links
                        </p>
                        <p>
                            <input type="url" name="cohort_url" value="<?php echo esc_attr($cohort_url); ?>" 
                                   class="regular-text" placeholder="<?php echo esc_attr(home_url('/')); ?>" 
                                   style="width: 100%;" />
                        </p>
                        <p class="description">
                            Example: <code><?php echo esc_html(home_url('/class-2025')); ?></code>
                        </p>
                        
                        <p style="margin-top: 20px;">
                            <input type="submit" name="save_global_privacy" class="button button-primary button-large" value="Save Global Settings" />
                        </p>
                    </form>
                </div>
                
                <!-- Personal Portfolio (Right) -->
                <div class="card">
                    <h2 style="margin-top: 0;">👤 Your Portfolio Privacy</h2>
                    
                    <form method="post">
                        <?php wp_nonce_field('portfolio_toggle_action', 'portfolio_toggle_nonce'); ?>
                        
                        <p>
                            <label style="display: block; font-size: 16px; margin-bottom: 10px;">
                                <input type="checkbox" name="portfolio_is_public" value="1" <?php checked($is_public, '1'); ?> />
                                <strong>Make my portfolio publicly accessible</strong>
                            </label>
                        </p>
                        
                        <div style="background: #f0f0f1; padding: 15px; border-left: 4px solid <?php echo ($is_public === '1') ? '#00a32a' : '#dba617'; ?>; margin: 15px 0;">
                            <p style="margin: 0;">
                                <strong>Current Status: 
                                    <?php if ($is_public === '1'): ?>
                                        <span style="color: #00a32a;">✓ Public</span>
                                    <?php else: ?>
                                        <span style="color: #dba617;">⚠ Private</span>
                                    <?php endif; ?>
                                </strong>
                            </p>
                        </div>
                        
                        <p class="description" style="margin-bottom: 15px; font-size: 12px;">
                            <strong>Public:</strong> Viewable without login<br>
                            <strong>Private:</strong> Login required
                        </p>
                        
                        <hr style="margin: 20px 0;">
                        
                        <h3 style="margin-bottom: 10px;">Your URLs</h3>
                        
                        <p style="margin-bottom: 15px;">
                            <strong>Portfolio URL:</strong><br>
                            <a href="<?php echo esc_url($portfolio_url); ?>" target="_blank" style="font-size: 13px; word-break: break-all; font-family: monospace;">
                                <?php echo esc_html($portfolio_url); ?>
                            </a>
                            <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('<?php echo esc_js($portfolio_url); ?>'); this.innerText='Copied!'; setTimeout(() => this.innerText='Copy', 2000);" style="margin-left: 10px;">
                                Copy
                            </button>
                        </p>
                        
                        <p>
                            <strong>Author Archive URL:</strong><br>
                            <a href="<?php echo esc_url(str_replace('/author/', '/' . $current_author_slug . '/', get_author_posts_url($user_id))); ?>" target="_blank" style="font-size: 13px; word-break: break-all; font-family: monospace;">
                                <?php echo esc_html(str_replace('/author/', '/' . $current_author_slug . '/', get_author_posts_url($user_id))); ?>
                            </a>
                            <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('<?php echo esc_js(str_replace('/author/', '/' . $current_author_slug . '/', get_author_posts_url($user_id))); ?>'); this.innerText='Copied!'; setTimeout(() => this.innerText='Copy', 2000);" style="margin-left: 10px;">
                                Copy
                            </button>
                        </p>
                        
                        <p style="margin-top: 20px;">
                            <input type="submit" name="save_portfolio_toggle" class="button button-primary button-large" value="Save Settings" />
                        </p>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Menu Builder Guide Tab -->
        <?php if ($active_tab === 'menu-builder'): ?>
        <div class="eportfolio-tab-content">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 1200px;">
                
                <!-- ACF Integration (Left) -->
                <div class="card" style="background: #f0f6fc; border-left: 4px solid #2271b1;">
                    <h2 style="margin-top: 0;">🎯 ACF Integration</h2>
                    
                    <p class="description" style="margin-bottom: 15px; font-size: 12px;">
                        This theme works best with Advanced Custom Fields (ACF) for content types
                    </p>
                    
                    <div style="background: white; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                        <h4 style="margin: 0 0 10px 0; color: #0073aa;">1. Install ACF Plugin</h4>
                        <p style="margin: 5px 0; font-size: 12px;">
                            Install the <a href="https://wordpress.org/plugins/advanced-custom-fields/" target="_blank">Advanced Custom Fields plugin</a>
                        </p>
                    </div>
                    
                    <div style="background: white; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                        <h4 style="margin: 0 0 10px 0; color: #0073aa;">2. Create Content Type Taxonomy</h4>
                        <p style="margin: 5px 0; font-size: 12px;">
                            In ACF → Taxonomies → Add New:
                        </p>
                        <div style="background: #f8f9fa; padding: 8px; border-radius: 3px; font-family: monospace; font-size: 11px; margin: 8px 0;">
                            <strong>Label:</strong> Content Types<br>
                            <strong>Key:</strong> content_type<br>
                            <strong>Post Types:</strong> ✓ Post<br>
                            <strong>Settings:</strong> ✓ Show in REST API<br>
                            <strong>Settings:</strong> ✓ Show in Nav Menus
                        </div>
                    </div>
                    
                    <div style="background: white; padding: 15px; border-radius: 4px;">
                        <h4 style="margin: 0 0 10px 0; color: #0073aa;">3. Create Content Type Terms</h4>
                        <p style="margin: 5px 0; font-size: 12px;">
                            In Posts → Content Types → Add New:
                        </p>
                        <div style="background: #f8f9fa; padding: 8px; border-radius: 3px; font-size: 11px; margin: 8px 0;">
                            Examples: Essay, Project, Reflection, Studio Work, Research
                        </div>
                    </div>
                </div>
                
                <!-- Portfolio Links & Student URLs (Right) -->
                <div class="card" style="background: #f6ffed; border-left: 4px solid #52c41a;">
                    <h2 style="margin-top: 0;">🔗 Navigation & Links</h2>
                    
                    <div style="background: white; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                        <h4 style="margin: 0 0 10px 0; color: #0073aa;">Portfolio Links</h4>
                        <p style="margin: 5px 0; font-size: 12px;">
                            To add "Portfolio →" links in Navigation menus:
                        </p>
                        <div style="background: #f8f9fa; padding: 8px; border-radius: 3px; font-family: monospace; font-size: 11px; margin: 8px 0;">
                            <strong>URL:</strong> #<br>
                            <strong>Text:</strong> Portfolio →<br>
                            <strong>CSS Classes:</strong> portfolio-link-auto
                        </div>
                        <p style="margin: 8px 0 0 0; font-size: 11px; color: #666;">
                            The theme will automatically set the correct portfolio URL and styling.
                        </p>
                    </div>
                    
                    <div style="background: white; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                        <h4 style="margin: 0 0 10px 0; color: #0073aa;">Student Directory</h4>
                        <?php 
                        $current_author_slug = get_option('eportfolio_author_slug', 'author');
                        $authors = get_users(array(
                            'role' => 'author',
                            'blog_id' => get_current_blog_id(),
                            'orderby' => 'display_name'
                        ));
                        ?>
                        
                        <?php if (!empty($authors)): ?>
                        <p style="margin: 5px 0; font-size: 12px;">
                            <strong><?php echo count($authors); ?> students</strong> - Use these URLs for manual links:
                        </p>
                        <div style="max-height: 200px; overflow-y: auto; background: #f8f9fa; padding: 8px; border-radius: 3px; font-family: monospace; font-size: 10px;">
                            <?php foreach ($authors as $author): 
                                $author_url = str_replace('/author/', '/' . $current_author_slug . '/', get_author_posts_url($author->ID));
                            ?>
                            <div style="margin-bottom: 3px;">
                                <strong><?php echo esc_html($author->display_name); ?></strong><br>
                                <?php echo esc_html($author_url); ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p style="color: #d63638; font-style: italic; font-size: 12px;">No users with Author role found.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div style="background: white; padding: 15px; border-radius: 4px;">
                        <h4 style="margin: 0 0 10px 0; color: #0073aa;">Content Type Filtering</h4>
                        <p style="margin: 5px 0; font-size: 12px;">
                            For advanced filtering behavior, install the 
                            <a href="<?php echo admin_url('plugin-install.php?s=code+snippets&tab=search'); ?>" target="_blank">Code Snippets plugin</a>
                            and check the theme documentation for filter examples.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Advanced Tab -->
        <?php if ($active_tab === 'advanced'): ?>
        <div class="eportfolio-tab-content">
            <div class="card" style="max-width: 800px; background: #e7f5ff; border-left: 4px solid #0073aa;">
                <h2 style="margin-top: 0;">🔗 Customize Author Archive URL</h2>
                
                <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ff9800; margin-bottom: 20px;">
                    <p style="margin: 0; font-size: 13px;">
                        <strong>⚠️ Site-wide URL change:</strong> This changes ALL student URLs from 
                        <code>/author/username</code> to <code>/<?php echo esc_html($current_author_slug); ?>/username</code>
                    </p>
                </div>
                
                <form method="post">
                    <?php wp_nonce_field('author_slug_action', 'author_slug_nonce'); ?>
                    
                    <p>
                        <label for="author_slug" style="display: block; margin-bottom: 8px;"><strong>URL Slug (site-wide):</strong></label>
                        <input type="text" id="author_slug" name="author_slug" value="<?php echo esc_attr($current_author_slug); ?>" 
                               class="regular-text" placeholder="author" />
                    </p>
                    
                    <div style="background: #f8f9fa; padding: 12px; border-radius: 4px; margin: 15px 0;">
                        <p style="margin: 0; font-size: 12px; color: #666;">
                            <strong>Examples:</strong><br>
                            • <code>student</code> → <code>/student/john-doe</code><br>
                            • <code>work</code> → <code>/work/jane-smith</code><br>
                            • <code>portfolio</code> → <code>/portfolio/alex-jones</code>
                        </p>
                    </div>
                    
                    <p style="margin-top: 20px;">
                        <input type="submit" name="save_author_slug" class="button button-primary button-large" value="Update URL Structure" />
                    </p>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <!-- Non-admin users only see personal portfolio settings -->
        <div class="card" style="max-width: 600px;">
            <h2>👤 Your Portfolio Privacy</h2>
            
            <form method="post">
                <?php wp_nonce_field('portfolio_toggle_action', 'portfolio_toggle_nonce'); ?>
                
                <p>
                    <label style="display: block; font-size: 16px; margin-bottom: 10px;">
                        <input type="checkbox" name="portfolio_is_public" value="1" <?php checked($is_public, '1'); ?> />
                        <strong>Make my portfolio publicly accessible</strong>
                    </label>
                </p>
                
                <p style="margin-top: 20px;">
                    <input type="submit" name="save_portfolio_toggle" class="button button-primary button-large" value="Save Settings" />
                </p>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php
}


/**
 * Customize author archive rewrite rules to use custom slug
 */
add_action('init', 'eportfolio_custom_author_slug');
function eportfolio_custom_author_slug() {
    global $wp_rewrite;
    
    $author_slug = get_option('eportfolio_author_slug', 'author');
    
    // Only change if it's different from default
    if ($author_slug !== 'author' && !empty($author_slug)) {
        $wp_rewrite->author_base = $author_slug;
    }
}

/**
 * Flush rewrite rules when author slug option is updated
 */
add_action('update_option_eportfolio_author_slug', 'eportfolio_flush_rewrites_on_slug_change', 10, 2);
function eportfolio_flush_rewrites_on_slug_change($old_value, $new_value) {
    if ($old_value !== $new_value) {
        flush_rewrite_rules();
    }
}
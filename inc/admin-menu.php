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
    
    // Handle student navigation menu generation (admin only)
    if ($is_admin && isset($_POST['create_student_menu']) && check_admin_referer('student_menu_action', 'student_menu_nonce')) {
        $result = eportfolio_create_student_author_menu();
        if ($result) {
            echo '<div class="notice notice-success is-dismissible"><p><strong>Student navigation menu created/updated!</strong> You can now add it to your templates via the Navigation block.</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> Could not create student menu. Please try again.</p></div>';
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
            <a href="?page=eportfolio-settings&tab=student-menu" class="nav-tab <?php echo $active_tab === 'student-menu' ? 'nav-tab-active' : ''; ?>">
                Student Menu
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
                    <h2 style="margin-top: 0;">🌐 Global Site Privacy</h2>
                    
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
                        
                        <p class="description" style="margin-bottom: 20px; line-height: 1.6;">
                            <strong>🔒 Private (Recommended):</strong> The entire site requires login. Students can individually make their portfolios or specific posts public.<br><br>
                            <strong>✓ Public:</strong> The entire site is open to everyone. Students can still set their portfolios to private if desired.
                        </p>
                        
                        <hr style="margin: 25px 0;">
                        
                        <h3 style="margin-bottom: 10px;">Cohort/Home Page Link</h3>
                        <p class="description" style="margin-bottom: 15px;">
                            Set a custom URL for the "back to cohort" links that appear in student portfolios.
                        </p>
                        <p>
                            <input type="url" name="cohort_url" value="<?php echo esc_attr($cohort_url); ?>" 
                                   class="regular-text" placeholder="<?php echo esc_attr(home_url('/')); ?>" 
                                   style="width: 100%;" />
                        </p>
                        <p class="description">
                            Example: <code><?php echo esc_html(home_url('/cohort-2025')); ?></code>
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
                        
                        <p class="description" style="margin-bottom: 20px; line-height: 1.6;">
                            <strong>Public:</strong> Anyone on the internet can view your portfolio without logging in.<br><br>
                            <strong>Private:</strong> Only people who can log into this site can view your portfolio.
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
                    
                    <hr style="margin: 20px 0;">
                    
                    <h3 style="margin-bottom: 10px;">📋 How It Works</h3>
                    <ol style="line-height: 1.8; margin-left: 20px;">
                        <li>Toggle privacy using the checkbox above</li>
                        <li>When editing posts, check "Show in public portfolio"</li>
                        <li>Share your portfolio URL with others</li>
                    </ol>
                    <p style="background: #fff3cd; padding: 12px; border-left: 4px solid #ffc107; margin-top: 15px; font-size: 13px;">
                        <strong>⚠️ Note:</strong> Only posts marked as "portfolio posts" appear on your portfolio page.
                    </p>
                </div>
                
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Student Menu Tab -->
        <?php if ($active_tab === 'student-menu'): ?>
        <div class="eportfolio-tab-content">
            <div class="card" style="max-width: 900px; background: #f0f6fc; border-left: 4px solid #2271b1;">
                <h2 style="margin-top: 0;">👥 Student Navigation Menu Generator</h2>
                
                <p class="description" style="margin-bottom: 15px; line-height: 1.6;">
                    Automatically generate a navigation menu with links to all student author pages, organized alphabetically into two groups (A-M and N-Z).
                </p>
                
                <form method="post">
                    <?php wp_nonce_field('student_menu_action', 'student_menu_nonce'); ?>
                    
                    <div style="background: white; padding: 15px; border-radius: 4px; margin: 15px 0;">
                        <h3 style="margin-top: 0;">Current Authors (<?php echo count(get_users(array('role' => 'author'))); ?> total):</h3>
                        <?php
                        $authors = get_users(array(
                            'role' => 'author',
                            'blog_id' => get_current_blog_id(),
                            'orderby' => 'display_name'
                        ));
                        
                        if (empty($authors)) {
                            echo '<p style="color: #d63638;"><em>No authors found. The menu will be empty until you create author accounts.</em></p>';
                        } else {
                            echo '<ul style="columns: 2; column-gap: 2rem; list-style: none; padding: 0;">';
                            foreach ($authors as $author) {
                                $author_url = str_replace('/author/', '/' . $current_author_slug . '/', get_author_posts_url($author->ID));
                                echo '<li style="margin-bottom: 8px; break-inside: avoid;">';
                                echo '<strong>' . esc_html($author->display_name) . '</strong><br>';
                                echo '<small style="color: #666; font-family: monospace; font-size: 11px;">' . esc_html($author_url) . '</small>';
                                echo '</li>';
                            }
                            echo '</ul>';
                        }
                        ?>
                    </div>
                    
                    <div style="background: #e7f5ff; padding: 15px; border-left: 4px solid #0073aa; margin: 15px 0;">
                        <h4 style="margin-top: 0;">💡 How it works:</h4>
                        <ol style="margin: 0; padding-left: 20px; line-height: 1.8;">
                            <li>Click the button below to generate/update the menu</li>
                            <li>Menu will be called "Student Authors" with two submenus: "Students A-M" and "Students N-Z"</li>
                            <li>Add it to your site using the Navigation block in your templates</li>
                            <li>Re-run this anytime you add/remove student accounts</li>
                        </ol>
                    </div>
                    
                    <p style="margin-top: 20px;">
                        <input type="submit" name="create_student_menu" class="button button-primary button-large" value="<?php echo empty($authors) ? 'Create' : 'Generate/Update'; ?> Student Menu" <?php echo empty($authors) ? 'disabled' : ''; ?> />
                    </p>
                    
                    <?php if (empty($authors)): ?>
                    <p class="description" style="color: #d63638;">
                        <strong>Note:</strong> Button is disabled because there are no author accounts yet.
                    </p>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Advanced Tab -->
        <?php if ($active_tab === 'advanced'): ?>
        <div class="eportfolio-tab-content">
            <div class="card" style="max-width: 800px; background: #e7f5ff; border-left: 4px solid #0073aa;">
                <h2 style="margin-top: 0;">🔗 Customize Author Archive URL</h2>
                
                <form method="post">
                    <?php wp_nonce_field('author_slug_action', 'author_slug_nonce'); ?>
                    
                    <p class="description" style="margin-bottom: 15px; line-height: 1.6;">
                        Change the URL slug for student author archives. By default, WordPress uses <code>/author/student-name</code>. 
                        You can customize this to something more meaningful like <code>/student/student-name</code> or <code>/work/student-name</code>.
                    </p>
                    
                    <div style="background: white; padding: 15px; border-radius: 4px; margin: 15px 0;">
                        <p style="margin: 0 0 10px 0;">
                            <strong>Current Author Archive URL Structure:</strong>
                        </p>
                        <p style="margin: 0; font-family: monospace; font-size: 14px; background: #f0f0f1; padding: 10px; border-radius: 3px;">
                            <?php echo esc_html(home_url()); ?>/<span style="background: #ffc; padding: 2px 6px; border-radius: 3px;"><?php echo esc_html($current_author_slug); ?></span>/student-name
                        </p>
                    </div>
                    
                    <p>
                        <label for="author_slug" style="display: block; margin-bottom: 5px;"><strong>Author URL Slug:</strong></label>
                        <input type="text" id="author_slug" name="author_slug" value="<?php echo esc_attr($current_author_slug); ?>" 
                               class="regular-text" placeholder="author" 
                               pattern="[a-z0-9\-]+" title="Lowercase letters, numbers, and hyphens only"
                               style="font-family: monospace;" />
                    </p>
                    
                    <p class="description" style="margin-bottom: 20px;">
                        <strong>Examples:</strong> <code>student</code>, <code>work</code>, <code>member</code>, <code>creator</code><br>
                        <strong>Note:</strong> Only use lowercase letters, numbers, and hyphens. No spaces or special characters.
                    </p>
                    
                    <div style="background: #fff3cd; padding: 12px; border-left: 4px solid #ffc107;">
                        <strong>⚠️ Important:</strong> After saving, visit <a href="<?php echo admin_url('options-permalink.php'); ?>">Settings → Permalinks</a> and click "Save Changes" to flush rewrite rules. This ensures all URLs work correctly.
                    </div>
                    
                    <p style="margin-top: 20px;">
                        <input type="submit" name="save_author_slug" class="button button-primary button-large" value="Update Author Slug" />
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
                
                <p class="description" style="margin-bottom: 20px; line-height: 1.6;">
                    <strong>Public:</strong> Anyone on the internet can view your portfolio without logging in.<br>
                    <strong>Private:</strong> Only people who can log into this site can view your portfolio.
                </p>
                
                <hr style="margin: 20px 0;">
                
                <p>
                    <strong>Your Portfolio URL:</strong><br>
                    <a href="<?php echo esc_url($portfolio_url); ?>" target="_blank" style="font-size: 14px; word-break: break-all; font-family: monospace;">
                        <?php echo esc_html($portfolio_url); ?>
                    </a>
                    <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('<?php echo esc_js($portfolio_url); ?>'); this.innerText='Copied!'; setTimeout(() => this.innerText='Copy', 2000);" style="margin-left: 10px;">
                        Copy
                    </button>
                </p>
                
                <p style="margin-top: 20px;">
                    <input type="submit" name="save_portfolio_toggle" class="button button-primary button-large" value="Save Settings" />
                </p>
            </form>
            
            <hr style="margin: 20px 0;">
            
            <h3>📋 How It Works</h3>
            <ol style="line-height: 1.8; margin-left: 20px;">
                <li>Toggle privacy using the checkbox above</li>
                <li>When editing posts, check "Show in public portfolio"</li>
                <li>Share your portfolio URL with others</li>
            </ol>
            <p style="background: #fff3cd; padding: 12px; border-left: 4px solid #ffc107; margin-top: 15px;">
                <strong>⚠️ Note:</strong> Only posts marked as "portfolio posts" appear on your portfolio page.
            </p>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Create student author navigation menu
 * Generates a menu with all authors split into A-M and N-Z groups
 */
function eportfolio_create_student_author_menu() {
    // Delete existing student menu if it exists
    $existing_menu = wp_get_nav_menu_object('Student Authors');
    if ($existing_menu) {
        wp_delete_nav_menu($existing_menu->term_id);
    }
    
    // Create new menu
    $menu_id = wp_create_nav_menu('Student Authors');
    
    if (is_wp_error($menu_id)) {
        return false;
    }
    
    // Get all authors
    $authors = get_users(array(
        'role' => 'author',
        'blog_id' => get_current_blog_id(),
        'orderby' => 'display_name'
    ));
    
    if (empty($authors)) {
        return false;
    }
    
    // Get custom author slug
    $author_slug = get_option('eportfolio_author_slug', 'author');
    
    // Split into groups by last name
    $group_am = array();
    $group_nz = array();
    
    foreach ($authors as $author) {
        $name_parts = explode(' ', trim($author->display_name));
        $last_name = end($name_parts);
        $first_letter = strtoupper(substr($last_name, 0, 1));
        
        if ($first_letter >= 'A' && $first_letter <= 'M') {
            $group_am[] = $author;
        } else {
            $group_nz[] = $author;
        }
    }
    
    // Create parent items
    $parent_am = wp_update_nav_menu_item($menu_id, 0, array(
        'menu-item-title' => 'Students A-M',
        'menu-item-url' => '#',
        'menu-item-status' => 'publish'
    ));
    
    $parent_nz = wp_update_nav_menu_item($menu_id, 0, array(
        'menu-item-title' => 'Students N-Z',
        'menu-item-url' => '#',
        'menu-item-status' => 'publish'
    ));
    
    // Add children for A-M group
    foreach ($group_am as $author) {
        $author_url = str_replace('/author/', '/' . $author_slug . '/', get_author_posts_url($author->ID));
        
        wp_update_nav_menu_item($menu_id, 0, array(
            'menu-item-title' => $author->display_name,
            'menu-item-url' => $author_url,
            'menu-item-parent-id' => $parent_am,
            'menu-item-status' => 'publish'
        ));
    }
    
    // Add children for N-Z group
    foreach ($group_nz as $author) {
        $author_url = str_replace('/author/', '/' . $author_slug . '/', get_author_posts_url($author->ID));
        
        wp_update_nav_menu_item($menu_id, 0, array(
            'menu-item-title' => $author->display_name,
            'menu-item-url' => $author_url,
            'menu-item-parent-id' => $parent_nz,
            'menu-item-status' => 'publish'
        ));
    }
    
    return true;
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

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
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'privacy';

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
    
    // Handle archive layout settings (admin only)
    if ($is_admin && isset($_POST['save_layout']) && check_admin_referer('layout_action', 'layout_nonce')) {
        $allowed = array('single', 'feed');
        $author_layout    = in_array(($_POST['author_layout'] ?? ''), $allowed, true) ? $_POST['author_layout'] : 'feed';
        $portfolio_layout = in_array(($_POST['portfolio_layout'] ?? ''), $allowed, true) ? $_POST['portfolio_layout'] : 'single';
        update_option('eportfolio_author_layout', $author_layout);
        update_option('eportfolio_portfolio_layout', $portfolio_layout);
        echo '<div class="notice notice-success is-dismissible"><p><strong>Layout settings saved.</strong></p></div>';
    }

    // Handle student navigation menu generation (admin only)
    if ($is_admin && isset($_POST['create_student_menu']) && check_admin_referer('student_menu_action', 'student_menu_nonce')) {
        $result = eportfolio_create_student_author_menu();
        if ($result) {
            echo '<div class="notice notice-success is-dismissible"><p><strong>Student Authors menu updated!</strong> Assign it to a Navigation block in the Site Editor.</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> Could not update student menu. No users with Author role found.</p></div>';
        }
    }

    // Handle content type menu generation (admin only)
    if ($is_admin && isset($_POST['create_content_type_menu']) && check_admin_referer('student_menu_action', 'student_menu_nonce')) {
        $result = eportfolio_create_content_type_menu();
        if ($result) {
            echo '<div class="notice notice-success is-dismissible"><p><strong>Content Types menu updated!</strong> Assign it to a Navigation block in the Site Editor.</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> Could not create Content Types menu. Make sure content-type terms exist.</p></div>';
        }
    }
    
    // Handle features form submission (admin only)
    if ($is_admin && isset($_POST['save_features']) && check_admin_referer('features_action', 'features_nonce')) {
        update_option('eportfolio_feature_portfolio', isset($_POST['feature_portfolio']) ? '1' : '0');
        echo '<div class="notice notice-success is-dismissible"><p><strong>Feature settings saved.</strong></p></div>';
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
    $portfolio_on = get_option('eportfolio_feature_portfolio', '0') === '1';

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <style>
        .eportfolio-toggle-wrap { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
        .eportfolio-toggle { position: relative; display: inline-block; width: 48px; height: 26px; flex-shrink: 0; }
        .eportfolio-toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
        .eportfolio-toggle .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .25s; border-radius: 26px; }
        .eportfolio-toggle .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; transition: .25s; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,.3); }
        .eportfolio-toggle input:checked + .slider { background-color: #00a32a; }
        .eportfolio-toggle input:checked + .slider:before { transform: translateX(22px); }
        .eportfolio-toggle input:focus + .slider { box-shadow: 0 0 0 2px #2271b1; }
        </style>

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
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 1200px; align-items: start;">

                <!-- Left column: admin-only settings -->
                <div style="display: flex; flex-direction: column; gap: 20px;">

                <!-- Global Privacy -->
                <div class="card" style="background: #fff3cd; border-left: 4px solid #ff9800;">
                    <h2 style="margin-top: 0;">Global Site Privacy</h2>
                    
                    <form method="post">
                        <?php wp_nonce_field('global_privacy_action', 'global_privacy_nonce'); ?>
                        
                        <div class="eportfolio-toggle-wrap">
                            <label class="eportfolio-toggle">
                                <input type="checkbox" name="site_is_public" value="1" <?php checked($site_is_public, '1'); ?> />
                                <span class="slider"></span>
                            </label>
                            <strong style="font-size: 15px;">Make entire site publicly accessible</strong>
                        </div>
                        
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

                <!-- Portfolio Curation (admin only) -->
                <div class="card" style="background: #f6ffed; border-left: 4px solid #52c41a;">
                    <h2 style="margin-top: 0;">Portfolio Curation</h2>
                    <p style="font-size: 13px; margin-bottom: 16px; color: #444;">
                        When enabled, each student gets a curated <strong>/portfolio/username/</strong> page alongside
                        their author archive. Students choose which posts appear there — making portfolio-building
                        a deliberate, reflective act. A "Portfolio" checkbox also appears in the post editor.
                    </p>
                    <form method="post">
                        <?php wp_nonce_field('features_action', 'features_nonce'); ?>
                        <div class="eportfolio-toggle-wrap">
                            <label class="eportfolio-toggle">
                                <input type="checkbox" name="feature_portfolio" value="1" <?php checked($portfolio_on); ?> />
                                <span class="slider"></span>
                            </label>
                            <strong style="font-size: 15px;">Enable Portfolio Curation</strong>
                        </div>
                        <div style="background: white; padding: 12px; border-left: 4px solid <?php echo $portfolio_on ? '#00a32a' : '#646970'; ?>; margin: 12px 0;">
                            <p style="margin: 0; font-size: 13px;">
                                <strong>Current Status:
                                    <?php if ($portfolio_on): ?>
                                        <span style="color: #00a32a;">&#10003; Enabled</span>
                                    <?php else: ?>
                                        <span style="color: #646970;">&mdash; Disabled</span>
                                    <?php endif; ?>
                                </strong>
                            </p>
                        </div>
                        <p style="font-size: 12px; color: #646970; margin-bottom: 16px;">
                            <strong>Disabled:</strong> Students only have their author archive — clean and simple.<br>
                            <strong>Enabled:</strong> Students can curate a separate public portfolio.
                        </p>
                        <p>
                            <input type="submit" name="save_features" class="button button-primary" value="Save" />
                        </p>
                    </form>
                </div>

                </div><!-- end left column -->

                <!-- Portfolio / Archive (Right) — conditional on feature flag -->
                <?php if ( get_option( 'eportfolio_feature_portfolio', '0' ) === '1' ) : ?>
                <div class="card">
                    <h2 style="margin-top: 0;">👤 Your Portfolio Privacy</h2>

                    <form method="post">
                        <?php wp_nonce_field('portfolio_toggle_action', 'portfolio_toggle_nonce'); ?>

                        <div class="eportfolio-toggle-wrap">
                            <label class="eportfolio-toggle">
                                <input type="checkbox" name="portfolio_is_public" value="1" <?php checked($is_public, '1'); ?> />
                                <span class="slider"></span>
                            </label>
                            <strong style="font-size: 15px;">Make my portfolio publicly accessible</strong>
                        </div>

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
                <?php else : ?>
                <div class="card" style="background: #f0f6fc; border-left: 4px solid #2271b1;">
                    <h2 style="margin-top: 0;">👤 Your Archive</h2>
                    <p class="description" style="font-size: 13px; margin-bottom: 15px;">
                        Portfolio curation is <strong>disabled</strong> for this site. Students submit reflections directly to their author archive.
                        Enable it in <a href="?page=eportfolio-settings&tab=privacy">Privacy Settings</a>.
                    </p>
                    <h3 style="margin-bottom: 10px;">Your Archive URL</h3>
                    <p>
                        <a href="<?php echo esc_url(str_replace('/author/', '/' . $current_author_slug . '/', get_author_posts_url($user_id))); ?>" target="_blank" style="font-size: 13px; word-break: break-all; font-family: monospace;">
                            <?php echo esc_html(str_replace('/author/', '/' . $current_author_slug . '/', get_author_posts_url($user_id))); ?>
                        </a>
                        <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('<?php echo esc_js(str_replace('/author/', '/' . $current_author_slug . '/', get_author_posts_url($user_id))); ?>'); this.innerText='Copied!'; setTimeout(() => this.innerText='Copy', 2000);" style="margin-left: 10px;">
                            Copy
                        </button>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Menu Builder Guide Tab -->
        <?php if ($active_tab === 'menu-builder'): ?>
        <div class="eportfolio-tab-content">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; max-width: 1400px;">
                
                <!-- Content Types (Left) -->
                <div class="card" style="background: #f0f6fc; border-left: 4px solid #2271b1;">
                    <h2 style="margin-top: 0;">🏷️ Content Types</h2>

                    <p class="description" style="margin-bottom: 15px; font-size: 12px;">
                        The theme registers a <strong>content-type</strong> taxonomy automatically — no plugin required.
                        Create terms that match the types of work students produce in your course.
                    </p>

                    <div style="background: white; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                        <h4 style="margin: 0 0 8px 0; color: #0073aa;">Add content types</h4>
                        <p style="margin: 5px 0; font-size: 12px;">
                            Go to <strong>Posts → Content Types → Add New</strong> and create a term for each type of work students produce.
                        </p>
                        <div style="background: #f8f9fa; padding: 8px; border-radius: 3px; font-size: 11px; margin: 8px 0;">
                            Examples: Reflection, Assignment, Lab Report, Studio Work, Research
                        </div>
                        <p style="margin: 8px 0 0 0; font-size: 11px; color: #666;">
                            Each Activity Builder page can specify which content type its submissions are tagged with.
                        </p>
                    </div>

                    <div style="background: white; padding: 15px; border-radius: 4px;">
                        <h4 style="margin: 0 0 8px 0; color: #0073aa;">Filtering on the archive page</h4>
                        <p style="margin: 5px 0; font-size: 12px;">
                            Use the <strong>Content Types menu generator</strong> (middle column) — it builds one
                            filter link per type, plus an <strong>All</strong> link, each scoped automatically to
                            the student whose archive is being viewed. Assign that menu to a Navigation block in
                            the Author template.
                        </p>
                        <p style="margin: 8px 0 0 0; font-size: 12px;">
                            In <strong>Feed</strong> mode (Advanced → Display / Layout) each filter shows the full
                            scroll of that content type; in <strong>Single post</strong> mode it shows one matching
                            post at a time.
                        </p>
                    </div>
                </div>
                
                <!-- Menu Generators (Middle) -->
                <div class="card" style="background: #fff7e6; border-left: 4px solid #fa8c16;">
                    <h2 style="margin-top: 0;">⚙️ Menu Generators</h2>

                    <p class="description" style="margin-bottom: 15px; font-size: 12px;">
                        Each button creates the menu once and <strong>updates it in place</strong> on subsequent runs —
                        the same menu ID is preserved so your Navigation block assignments stay intact.
                    </p>

                    <?php
                    $current_author_slug = get_option('eportfolio_author_slug', 'author');
                    $authors = get_users(array(
                        'role'    => 'author',
                        'blog_id' => get_current_blog_id(),
                        'orderby' => 'display_name',
                    ));
                    $ct_terms = get_terms(array('taxonomy' => 'content-type', 'hide_empty' => false));
                    $student_menu   = wp_get_nav_menu_object('Student Authors');
                    $ct_menu        = wp_get_nav_menu_object('Content Types');
                    ?>

                    <!-- Student Authors generator -->
                    <div style="background: white; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                        <h4 style="margin: 0 0 8px 0; color: #fa8c16;">👥 Student Authors</h4>
                        <p style="margin: 0 0 10px 0; font-size: 12px;">
                            One link per student (Author role) → their archive page.
                            <?php if ($student_menu): ?>
                                <br><span style="color: #00a32a; font-size: 11px;">&#10003; Menu exists (<?php echo esc_html(count(wp_get_nav_menu_items($student_menu->term_id) ?: array())); ?> items)</span>
                            <?php else: ?>
                                <br><span style="color: #646970; font-size: 11px;">Not yet generated.</span>
                            <?php endif; ?>
                        </p>
                        <form method="post" style="margin: 0;">
                            <?php wp_nonce_field('student_menu_action', 'student_menu_nonce'); ?>
                            <input type="submit" name="create_student_menu" class="button button-primary"
                                   value="<?php echo $student_menu ? 'Update Student Authors Menu' : 'Generate Student Authors Menu'; ?>" />
                        </form>
                        <?php if (!empty($authors)): ?>
                        <div style="max-height: 150px; overflow-y: auto; border: 1px solid #eee; border-radius: 3px; margin-top: 10px;">
                            <?php foreach ($authors as $author):
                                $author_url  = str_replace('/author/', '/' . $current_author_slug . '/', get_author_posts_url($author->ID));
                                $post_count  = count_user_posts($author->ID, 'post', true);
                            ?>
                            <div style="padding: 6px 8px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; font-size: 11px;">
                                <span><strong><?php echo esc_html($author->display_name); ?></strong> <span style="color:#999;">(<?php echo $post_count; ?>)</span></span>
                                <button type="button" class="button button-small"
                                        onclick="navigator.clipboard.writeText('<?php echo esc_js($author_url); ?>'); this.innerText='✓'; setTimeout(() => this.innerText='Copy', 1500);"
                                        style="font-size: 9px; padding: 1px 6px;">Copy</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p style="color: #d63638; font-size: 11px; margin: 8px 0 0 0; font-style: italic;">No users with Author role found.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Content Types generator -->
                    <div style="background: white; padding: 15px; border-radius: 4px;">
                        <h4 style="margin: 0 0 8px 0; color: #fa8c16;">🏷️ Content Types</h4>
                        <p style="margin: 0 0 10px 0; font-size: 12px;">
                            One link per content-type term (filters the archive by type).
                            Always includes a <strong>Portfolio →</strong> link — shown automatically when Portfolio Curation is enabled, hidden when disabled.
                            <?php if ($ct_menu): ?>
                                <br><span style="color: #00a32a; font-size: 11px;">&#10003; Menu exists (<?php echo esc_html(count(wp_get_nav_menu_items($ct_menu->term_id) ?: array())); ?> items)</span>
                            <?php else: ?>
                                <br><span style="color: #646970; font-size: 11px;">Not yet generated.</span>
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($ct_terms) && !is_wp_error($ct_terms)): ?>
                        <div style="margin-bottom: 10px;">
                            <?php foreach ($ct_terms as $term): ?>
                            <span style="display: inline-block; background: #f0f6fc; border: 1px solid #c5d9ed; border-radius: 3px; padding: 2px 8px; font-size: 11px; margin: 2px;">
                                <?php echo esc_html($term->name); ?>
                            </span>
                            <?php endforeach; ?>
                            <span style="display: inline-block; background: <?php echo $portfolio_on ? '#d5f4e6' : '#f0f0f1'; ?>; border: 1px solid <?php echo $portfolio_on ? '#8ecbad' : '#ccc'; ?>; border-radius: 3px; padding: 2px 8px; font-size: 11px; margin: 2px; color: <?php echo $portfolio_on ? 'inherit' : '#999'; ?>;">
                                Portfolio → <?php echo $portfolio_on ? '' : '(hidden)'; ?>
                            </span>
                        </div>
                        <form method="post" style="margin: 0;">
                            <?php wp_nonce_field('student_menu_action', 'student_menu_nonce'); ?>
                            <input type="submit" name="create_content_type_menu" class="button button-primary"
                                   value="<?php echo $ct_menu ? 'Update Content Types Menu' : 'Generate Content Types Menu'; ?>" />
                        </form>
                        <?php else: ?>
                        <p style="color: #646970; font-size: 11px; font-style: italic; margin: 0;">
                            No content-type terms found. Add terms under <strong>Posts → Content Types</strong> first.
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Portfolio Links & Manual URLs (Right) -->
                <div class="card" style="background: #f6ffed; border-left: 4px solid #52c41a;">
                    <h2 style="margin-top: 0;">🔗 Navigation & Links</h2>
                    
                    <div style="background: white; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                        <h4 style="margin: 0 0 10px 0; color: #0073aa;">Student URLs</h4>
                        <p style="margin: 5px 0; font-size: 12px;">
                            <strong>URL Pattern:</strong>
                        </p>
                        <div style="background: #f8f9fa; padding: 8px; border-radius: 3px; font-family: monospace; font-size: 11px;">
                            <?php echo esc_html(home_url('/' . $current_author_slug . '/username')); ?>
                        </div>
                        <p style="margin: 8px 0 0 0; font-size: 11px; color: #666;">
                            Replace "username" with actual student usernames for manual links.
                            Or use the Student Menu Generator to create all links automatically.
                        </p>
                    </div>
                    
                    <div style="background: white; padding: 15px; border-radius: 4px;">
                        <h4 style="margin: 0 0 10px 0; color: #0073aa;">Portfolio link</h4>
                        <p style="margin: 5px 0; font-size: 12px;">
                            When Portfolio Curation is enabled, add a "Portfolio →" link to a navigation menu manually:
                        </p>
                        <div style="background: #f8f9fa; padding: 8px; border-radius: 3px; font-family: monospace; font-size: 11px; margin: 8px 0;">
                            <strong>URL:</strong> #<br>
                            <strong>Text:</strong> Portfolio →<br>
                            <strong>CSS Classes:</strong> portfolio-link-auto
                        </div>
                        <p style="margin: 8px 0 0 0; font-size: 11px; color: #666;">
                            The theme automatically resolves the correct portfolio URL for the logged-in student and hides the link when already on the portfolio page.
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

            <?php
            $author_layout    = get_option('eportfolio_author_layout', 'feed');
            $portfolio_layout = get_option('eportfolio_portfolio_layout', 'single');
            ?>
            <div class="card" style="max-width: 800px; background: #f6ffed; border-left: 4px solid #52c41a; margin-top: 20px;">
                <h2 style="margin-top: 0;">🧭 Display / Layout</h2>

                <p class="description" style="margin-bottom: 15px; font-size: 13px;">
                    <strong>Feed</strong> shows all of a student's posts in a scrolling archive (best for a process
                    archive — works hand-in-hand with the Content Types filter menu). <strong>Single post</strong>
                    shows one post at a time with click-through navigation (best for a curated showcase).
                </p>

                <form method="post">
                    <?php wp_nonce_field('layout_action', 'layout_nonce'); ?>

                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="author_layout">Author archive <code>/<?php echo esc_html($current_author_slug); ?>/</code></label></th>
                            <td>
                                <select id="author_layout" name="author_layout">
                                    <option value="feed"   <?php selected($author_layout, 'feed'); ?>>Feed (scrolling archive)</option>
                                    <option value="single" <?php selected($author_layout, 'single'); ?>>Single post at a time</option>
                                </select>
                                <p class="description" style="font-size: 12px;">The process archive. Default: Feed.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="portfolio_layout">Portfolio <code>/portfolio/</code></label></th>
                            <td>
                                <select id="portfolio_layout" name="portfolio_layout">
                                    <option value="single" <?php selected($portfolio_layout, 'single'); ?>>Single post at a time</option>
                                    <option value="feed"   <?php selected($portfolio_layout, 'feed'); ?>>Feed (scrolling archive)</option>
                                </select>
                                <p class="description" style="font-size: 12px;">
                                    The curated showcase. Default: Single post.
                                    <?php if ( get_option('eportfolio_feature_portfolio', '0') !== '1' ) : ?>
                                        <br><em>Portfolio Curation is currently off, so this only takes effect once it's enabled in Privacy Settings.</em>
                                    <?php endif; ?>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <p style="margin-top: 10px;">
                        <input type="submit" name="save_layout" class="button button-primary button-large" value="Save Layout Settings" />
                    </p>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- Non-admin: show portfolio settings or just archive URL depending on feature flag -->
        <?php if ( get_option( 'eportfolio_feature_portfolio', '0' ) === '1' ) : ?>
        <div class="card" style="max-width: 600px;">
            <h2 style="margin-top: 0;">&#128100; Your Portfolio Privacy</h2>

            <form method="post">
                <?php wp_nonce_field('portfolio_toggle_action', 'portfolio_toggle_nonce'); ?>

                <div class="eportfolio-toggle-wrap">
                    <label class="eportfolio-toggle">
                        <input type="checkbox" name="portfolio_is_public" value="1" <?php checked($is_public, '1'); ?> />
                        <span class="slider"></span>
                    </label>
                    <strong style="font-size: 15px;">Make my portfolio publicly accessible</strong>
                </div>

                <div style="background: #f0f0f1; padding: 15px; border-left: 4px solid <?php echo ($is_public === '1') ? '#00a32a' : '#dba617'; ?>; margin: 15px 0;">
                    <p style="margin: 0;">
                        <strong>Current Status:
                            <?php if ($is_public === '1'): ?>
                                <span style="color: #00a32a;">&#10003; Public</span>
                            <?php else: ?>
                                <span style="color: #dba617;">&#9888; Private</span>
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
        <?php else : ?>
        <div class="card" style="max-width: 600px; background: #f0f6fc; border-left: 4px solid #2271b1;">
            <h2 style="margin-top: 0;">&#128100; Your Archive</h2>
            <p class="description" style="font-size: 13px; margin-bottom: 20px;">
                Your submissions are published to your author archive below.
            </p>
            <h3 style="margin-bottom: 10px;">Your Archive URL</h3>
            <p>
                <a href="<?php echo esc_url(str_replace('/author/', '/' . $current_author_slug . '/', get_author_posts_url($user_id))); ?>" target="_blank" style="font-size: 13px; word-break: break-all; font-family: monospace;">
                    <?php echo esc_html(str_replace('/author/', '/' . $current_author_slug . '/', get_author_posts_url($user_id))); ?>
                </a>
                <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('<?php echo esc_js(str_replace('/author/', '/' . $current_author_slug . '/', get_author_posts_url($user_id))); ?>'); this.innerText='Copied!'; setTimeout(() => this.innerText='Copy', 2000);" style="margin-left: 10px;">
                    Copy
                </button>
            </p>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Clear all items from a nav menu without deleting the menu itself.
 * Preserves the menu's term ID so Navigation block assignments remain intact.
 *
 * Uses get_posts with a tax_query instead of wp_get_nav_menu_items to bypass
 * the internal nav menu cache, which causes stale results on repeated runs.
 */
function eportfolio_clear_nav_menu_items( $menu_id ) {
    $item_ids = get_posts( array(
        'post_type'      => 'nav_menu_item',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'tax_query'      => array(
            array(
                'taxonomy' => 'nav_menu',
                'field'    => 'term_id',
                'terms'    => intval( $menu_id ),
            ),
        ),
    ) );
    foreach ( (array) $item_ids as $item_id ) {
        wp_delete_post( (int) $item_id, true );
    }
    wp_cache_delete( $menu_id, 'nav_menu_items' );
}

/**
 * Create or update the "Student Authors" navigation menu.
 * Updates in place on subsequent runs — menu ID is never changed.
 */
function eportfolio_create_student_author_menu() {
    $menu_name = 'Student Authors';
    $existing  = wp_get_nav_menu_object( $menu_name );

    if ( $existing ) {
        $menu_id = $existing->term_id;
        eportfolio_clear_nav_menu_items( $menu_id );
    } else {
        $menu_id = wp_create_nav_menu( $menu_name );
        if ( is_wp_error( $menu_id ) ) {
            return false;
        }
    }

    $authors = get_users( array(
        'role'    => 'author',
        'blog_id' => get_current_blog_id(),
        'orderby' => 'display_name',
    ) );

    if ( empty( $authors ) ) {
        return false;
    }

    $author_slug = get_option( 'eportfolio_author_slug', 'author' );

    foreach ( $authors as $author ) {
        $author_url = str_replace( '/author/', '/' . $author_slug . '/', get_author_posts_url( $author->ID ) );
        wp_update_nav_menu_item( $menu_id, 0, array(
            'menu-item-title'  => $author->display_name,
            'menu-item-url'    => $author_url,
            'menu-item-status' => 'publish',
        ) );
    }

    return true;
}

/**
 * Create or update the "Content Types" navigation menu.
 * One item per content-type term (URL: ?content-type=slug — relative, works on
 * any author archive page). Appends a Portfolio → item when curation is enabled.
 * Updates in place on subsequent runs — menu ID is never changed.
 */
function eportfolio_create_content_type_menu() {
    $menu_name = 'Content Types';
    $existing  = wp_get_nav_menu_object( $menu_name );

    if ( $existing ) {
        $menu_id = $existing->term_id;
        eportfolio_clear_nav_menu_items( $menu_id );
    } else {
        $menu_id = wp_create_nav_menu( $menu_name );
        if ( is_wp_error( $menu_id ) ) {
            return false;
        }
    }

    $terms = get_terms( array(
        'taxonomy'   => 'content-type',
        'hide_empty' => false,
    ) );

    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return false;
    }

    // "All" — clears the filter. URL "#" is resolved at render time to the
    // current author archive base URL by inc/content-type-filter.php.
    wp_update_nav_menu_item( $menu_id, 0, array(
        'menu-item-title'   => 'All',
        'menu-item-url'     => '#',
        'menu-item-status'  => 'publish',
        'menu-item-classes' => 'content-type-all',
    ) );

    foreach ( $terms as $term ) {
        wp_update_nav_menu_item( $menu_id, 0, array(
            'menu-item-title'  => $term->name,
            'menu-item-url'    => '?content-type=' . $term->slug,
            'menu-item-status' => 'publish',
        ) );
    }

    // Always add the Portfolio link — visibility is controlled at render time
    // by the wp_nav_menu_objects filter in portfolio-link.php, so toggling
    // Portfolio Curation never requires regenerating this menu.
    wp_update_nav_menu_item( $menu_id, 0, array(
        'menu-item-title'   => 'Portfolio →',
        'menu-item-url'     => '#',
        'menu-item-status'  => 'publish',
        'menu-item-classes' => 'portfolio-link-auto',
    ) );

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

// "Reflection Page" toolbar item and creation handler moved to the
// reflection-submissions plugin (inc/admin-page.php). Removed from theme.
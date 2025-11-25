<?php
/**
 * Portfolio Post Metabox
 * Adds checkbox to mark posts for inclusion in public portfolio
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the metabox for both block editor and classic editor
 */
add_action('add_meta_boxes', 'eportfolio_add_portfolio_metabox');
function eportfolio_add_portfolio_metabox() {
    // Add metabox for posts
    add_meta_box(
        'eportfolio_portfolio_box',                     // Unique ID
        'Portfolio Settings',                        // Box title
        'eportfolio_render_portfolio_metabox',          // Callback function
        'post',                                         // Post type
        'side',                                         // Context (side, normal, advanced)
        'high'                                          // Priority
    );
    
    // Add metabox for pages
    add_meta_box(
        'eportfolio_privacy_box',                       // Unique ID
        'Privacy Settings',                          // Box title
        'eportfolio_render_privacy_metabox',            // Callback function
        'page',                                         // Post type
        'side',                                         // Context (side, normal, advanced)
        'high'                                          // Priority
    );
}

/**
 * Render the metabox content
 */
function eportfolio_render_portfolio_metabox($post) {
    // Add nonce for security
    wp_nonce_field('eportfolio_save_portfolio_meta', 'eportfolio_portfolio_nonce');
    
    // Get current value
    $is_portfolio_post = get_post_meta($post->ID, '_is_public_portfolio', true);
    $checked = ($is_portfolio_post === '1') ? 'checked' : '';
    
    ?>
    <div style="padding: 10px 0;">
        <label style="display: flex; align-items: flex-start; cursor: pointer;">
            <input 
                type="checkbox" 
                name="is_public_portfolio" 
                value="1" 
                <?php echo $checked; ?>
                style="margin-top: 3px; margin-right: 8px;"
            />
            <span style="line-height: 1.5;">
                <strong>Include in public portfolio</strong>
            </span>
        </label>
        
        <?php if ($post->post_type === 'post'): ?>
        <p class="description" style="margin: 8px 0 0 0; font-size: 12px; color: #646970;">
            Shows on <code>/portfolio/username</code> (public access controlled by portfolio privacy setting)
        </p>
        <?php endif; ?>
        
        <?php if ($is_portfolio_post === '1'): ?>
        <div style="background: #d5f4e6; border-left: 4px solid #00a32a; padding: 10px; margin-top: 12px;">
            <p style="margin: 0; color: #007017; font-size: 12px;">
                ✓ <strong>Portfolio post</strong> - appears on portfolio page
            </p>
        </div>
        <?php else: ?>
        <div style="background: #f0f0f1; border-left: 4px solid #646970; padding: 10px; margin-top: 12px;">
            <p style="margin: 0; color: #1e1e1e; font-size: 12px;">
                ℹ <strong>Regular post</strong> - hidden from portfolio
            </p>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render the privacy metabox content for pages
 */
function eportfolio_render_privacy_metabox($post) {
    // Add nonce for security
    wp_nonce_field('eportfolio_save_portfolio_meta', 'eportfolio_portfolio_nonce');
    
    // Get current value
    $is_public_page = get_post_meta($post->ID, '_is_public_portfolio', true);
    $checked = ($is_public_page === '1') ? 'checked' : '';
    
    ?>
    <div style="padding: 10px 0;">
        <label style="display: flex; align-items: flex-start; cursor: pointer;">
            <input 
                type="checkbox" 
                name="is_public_portfolio" 
                value="1" 
                <?php echo $checked; ?>
                style="margin-top: 3px; margin-right: 8px;"
            />
            <span style="line-height: 1.5;">
                <strong>Make this page publicly accessible</strong>
            </span>
        </label>
        
        <p class="description" style="margin: 8px 0 0 0; font-size: 12px; color: #646970;">
            Override site privacy - makes page publicly accessible
        </p>
        
        <?php if ($is_public_page === '1'): ?>
        <div style="background: #d5f4e6; border-left: 4px solid #00a32a; padding: 10px; margin-top: 12px;">
            <p style="margin: 0; color: #007017; font-size: 12px;">
                ✓ <strong>Public page</strong>
            </p>
        </div>
        <?php else: ?>
        <div style="background: #f0f0f1; border-left: 4px solid #646970; padding: 10px; margin-top: 12px;">
            <p style="margin: 0; color: #1e1e1e; font-size: 12px;">
                🔒 <strong>Private page</strong>
            </p>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Save the metabox data
 */
add_action('save_post', 'eportfolio_save_portfolio_meta', 10, 2);
function eportfolio_save_portfolio_meta($post_id, $post) {
    // Security checks
    if (!isset($_POST['eportfolio_portfolio_nonce']) || 
        !wp_verify_nonce($_POST['eportfolio_portfolio_nonce'], 'eportfolio_save_portfolio_meta')) {
        return;
    }
    
    // Check if this is an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check user permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Only for posts and pages
    if (!in_array($post->post_type, array('post', 'page'))) {
        return;
    }
    
    // Save or delete the meta value
    if (isset($_POST['is_public_portfolio']) && $_POST['is_public_portfolio'] === '1') {
        update_post_meta($post_id, '_is_public_portfolio', '1');
    } else {
        // Explicitly set to '0' instead of deleting, for clearer queries later
        update_post_meta($post_id, '_is_public_portfolio', '0');
    }
}

/**
 * Add column to post list in admin to show portfolio status
 */
add_filter('manage_posts_columns', 'eportfolio_add_portfolio_column');
function eportfolio_add_portfolio_column($columns) {
    // Insert the column after the title
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['portfolio_status'] = '📋 Portfolio';
        }
    }
    return $new_columns;
}

/**
 * Add column to pages list in admin to show privacy status
 */
add_filter('manage_pages_columns', 'eportfolio_add_privacy_column');
function eportfolio_add_privacy_column($columns) {
    // Insert the column after the title
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['privacy_status'] = '🔒 Privacy';
        }
    }
    return $new_columns;
}

/**
 * Display the portfolio status in the column
 */
add_action('manage_posts_custom_column', 'eportfolio_display_portfolio_column', 10, 2);
function eportfolio_display_portfolio_column($column, $post_id) {
    if ($column === 'portfolio_status') {
        $is_portfolio = get_post_meta($post_id, '_is_public_portfolio', true);
        if ($is_portfolio === '1') {
            echo '<span style="color: #00a32a; font-weight: bold;">✓ Yes</span>';
        } else {
            echo '<span style="color: #646970;">—</span>';
        }
    }
}

/**
 * Display the privacy status in the column for pages
 */
add_action('manage_pages_custom_column', 'eportfolio_display_privacy_column', 10, 2);
function eportfolio_display_privacy_column($column, $post_id) {
    if ($column === 'privacy_status') {
        $is_public = get_post_meta($post_id, '_is_public_portfolio', true);
        if ($is_public === '1') {
            echo '<span style="color: #00a32a; font-weight: bold;">🌐 Public</span>';
        } else {
            echo '<span style="color: #646970;">🔒 Private</span>';
        }
    }
}

/**
 * Make the portfolio column sortable
 */
add_filter('manage_edit-post_sortable_columns', 'eportfolio_portfolio_column_sortable');
function eportfolio_portfolio_column_sortable($columns) {
    $columns['portfolio_status'] = 'portfolio_status';
    return $columns;
}

/**
 * Make the privacy column sortable for pages
 */
add_filter('manage_edit-page_sortable_columns', 'eportfolio_privacy_column_sortable');
function eportfolio_privacy_column_sortable($columns) {
    $columns['privacy_status'] = 'privacy_status';
    return $columns;
}

/**
 * Handle sorting by portfolio status
 */
add_action('pre_get_posts', 'eportfolio_portfolio_column_orderby');
function eportfolio_portfolio_column_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    if ($query->get('orderby') === 'portfolio_status' || $query->get('orderby') === 'privacy_status') {
        $query->set('meta_key', '_is_public_portfolio');
        $query->set('orderby', 'meta_value');
    }
}
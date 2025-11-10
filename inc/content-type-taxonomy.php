<?php
/**
 * Content Type Taxonomy
 * Allows categorizing posts by content type (e.g., Essay, Studio, Reflection)
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Content Type taxonomy
 */
add_action('init', 'eportfolio_register_content_type_taxonomy');
function eportfolio_register_content_type_taxonomy() {
    $labels = array(
        'name'              => 'Content Types',
        'singular_name'     => 'Content Type',
        'search_items'      => 'Search Content Types',
        'all_items'         => 'All Content Types',
        'parent_item'       => 'Parent Content Type',
        'parent_item_colon' => 'Parent Content Type:',
        'edit_item'         => 'Edit Content Type',
        'update_item'       => 'Update Content Type',
        'add_new_item'      => 'Add New Content Type',
        'new_item_name'     => 'New Content Type Name',
        'menu_name'         => 'Content Types',
    );

    $args = array(
        'hierarchical'      => true,  // Like categories (vs tags)
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,  // Show in post list
        'show_in_rest'      => true,  // Required for block editor
        'query_var'         => true,
        'rewrite'           => array('slug' => 'content-type'),
        'show_in_quick_edit' => true,
        'public'            => true,
    );

    register_taxonomy('content_type', array('post'), $args);
}



/**
 * Add content type filter to post list in admin
 */
add_action('restrict_manage_posts', 'eportfolio_add_content_type_filter');
function eportfolio_add_content_type_filter() {
    global $typenow;
    
    if ($typenow === 'post') {
        $taxonomy = 'content_type';
        $selected = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
        $info_taxonomy = get_taxonomy($taxonomy);
        
        wp_dropdown_categories(array(
            'show_option_all' => 'All Content Types',
            'taxonomy'        => $taxonomy,
            'name'            => $taxonomy,
            'orderby'         => 'name',
            'selected'        => $selected,
            'show_count'      => true,
            'hide_empty'      => true,
            'value_field'     => 'slug',
        ));
    }
}

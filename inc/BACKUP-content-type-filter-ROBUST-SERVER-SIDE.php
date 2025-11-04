<?php
/**
 * BACKUP: Content Type Filter (Server-Side URL Parameter Approach)
 * 
 * THIS IS THE ORIGINAL ROBUST VERSION - BACKED UP FOR ETUG DEMO
 * Date backed up: October 31, 2025
 * 
 * This version does PROPER server-side filtering with:
 * - Actual WP_Query modification based on URL parameters
 * - Full pagination support
 * - Proper author context preservation
 * - Works with large datasets efficiently
 * 
 * TO RESTORE: Simply rename this file back to content-type-filter.php
 * and delete or rename the client-side demo version.
 * 
 * Creates a dynamic WordPress nav menu for filtering by content type
 * Uses URL parameters for server-side filtering with proper pagination support
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create and maintain dynamic Content Type Filter menu
 * Includes content types + portfolio link for author archives
 */
function eportfolio_create_content_type_menu() {
    $menu_name = 'Content Type Filter';
    $menu_slug = 'content-type-filter';
    
    // Check if menu exists, if not create it
    $menu_exists = wp_get_nav_menu_object($menu_slug);
    
    if (!$menu_exists) {
        $menu_id = wp_create_nav_menu($menu_name);
        
        if (is_wp_error($menu_id)) {
            return false;
        }
        
        // Update menu slug
        wp_update_term($menu_id, 'nav_menu', array('slug' => $menu_slug));
    } else {
        $menu_id = $menu_exists->term_id;
    }
    
    // Get current menu items
    $current_items = wp_get_nav_menu_items($menu_id);
    $current_item_ids = array();
    
    if ($current_items) {
        foreach ($current_items as $item) {
            $current_item_ids[] = $item->ID;
        }
        
        // Delete all current items to rebuild fresh
        foreach ($current_item_ids as $item_id) {
            wp_delete_post($item_id, true);
        }
    }
    
    // Add "All" menu item first
    wp_update_nav_menu_item($menu_id, 0, array(
        'menu-item-title' => 'All',
        'menu-item-url' => '#',
        'menu-item-status' => 'publish',
        'menu-item-classes' => 'content-filter-item filter-all',
        'menu-item-attr-title' => '',
        'menu-item-attr-data-filter' => 'all'
    ));
    
    // Get all content types from taxonomy
    $content_types = get_terms(array(
        'taxonomy' => 'content_type',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ));
    
    if (!is_wp_error($content_types) && !empty($content_types)) {
        foreach ($content_types as $content_type) {
            wp_update_nav_menu_item($menu_id, 0, array(
                'menu-item-title' => $content_type->name,
                'menu-item-url' => '#',
                'menu-item-status' => 'publish',
                'menu-item-classes' => 'content-filter-item filter-' . $content_type->slug,
                'menu-item-attr-title' => '',
                'menu-item-attr-data-filter' => $content_type->slug
            ));
        }
    }
    
    // Add portfolio link as last item (will be populated dynamically with JS)
    wp_update_nav_menu_item($menu_id, 0, array(
        'menu-item-title' => 'Portfolio →',
        'menu-item-url' => '#portfolio',
        'menu-item-status' => 'publish',
        'menu-item-classes' => 'portfolio-link-item',
        'menu-item-attr-title' => 'View curated portfolio',
        'menu-item-attr-data-portfolio-link' => 'true'
    ));
    
    return $menu_id;
}

/**
 * Update menu when content types change
 */
function eportfolio_update_content_type_menu_on_term_change($term_id, $tt_id, $taxonomy) {
    if ($taxonomy === 'content_type') {
        eportfolio_create_content_type_menu();
    }
}

// Hook into term creation, editing, and deletion
add_action('created_content_type', 'eportfolio_update_content_type_menu_on_term_change', 10, 3);
add_action('edited_content_type', 'eportfolio_update_content_type_menu_on_term_change', 10, 3);
add_action('delete_content_type', 'eportfolio_update_content_type_menu_on_term_change', 10, 3);

/**
 * Create/update menu on init
 */
function eportfolio_initialize_content_type_menu() {
    eportfolio_create_content_type_menu();
}
add_action('init', 'eportfolio_initialize_content_type_menu');

/**
 * Server-side filtering: Modify the query based on content_type URL parameter
 */
function eportfolio_filter_by_content_type($query) {
    // Only on main query, author archives, and when content_type is set
    if (is_admin() || !$query->is_main_query() || !$query->is_author()) {
        return;
    }
    
    // Check if content_type filter is in the URL
    if (isset($_GET['content_type']) && !empty($_GET['content_type'])) {
        $content_type = sanitize_text_field($_GET['content_type']);
        
        // Skip if it's "all"
        if ($content_type === 'all') {
            return;
        }
        
        // CRITICAL: Preserve the author name/ID that's already been set by WordPress
        // Get both the author ID and the author_name (nicename) to maintain context
        $author_id = $query->get('author');
        $author_name = $query->get('author_name');
        
        // If we have author_name but no author_id, get the ID from the name
        if (empty($author_id) && !empty($author_name)) {
            $user = get_user_by('slug', $author_name);
            if ($user) {
                $author_id = $user->ID;
            }
        }
        
        // Add taxonomy query to existing query
        $tax_query = $query->get('tax_query') ?: array();
        $tax_query[] = array(
            'taxonomy' => 'content_type',
            'field'    => 'slug',
            'terms'    => $content_type,
        );
        $query->set('tax_query', $tax_query);
        
        // Explicitly maintain author context
        // WordPress uses author_name for URL-based author archives
        if ($author_name) {
            $query->set('author_name', $author_name);
        }
        if ($author_id) {
            $query->set('author', $author_id);
        }
        
        // Force WordPress to recognize this is still an author archive
        // This prevents the queried object from being lost
        $query->is_author = true;
        $query->is_archive = true;
    }
}
add_action('pre_get_posts', 'eportfolio_filter_by_content_type');

/**
 * Fix the queried object after the query runs
 * This ensures WordPress knows which author to display in the template
 */
function eportfolio_fix_queried_object_for_filtered_author($posts, $query) {
    // Only on main query, author archives, when content_type filter is active
    if (is_admin() || !$query->is_main_query() || !$query->is_author()) {
        return $posts;
    }
    
    if (isset($_GET['content_type']) && !empty($_GET['content_type'])) {
        $content_type = sanitize_text_field($_GET['content_type']);
        
        if ($content_type === 'all') {
            return $posts;
        }
        
        // Get the author info
        $author_name = $query->get('author_name');
        $author_id = $query->get('author');
        
        // Make sure we have the author ID
        if (empty($author_id) && !empty($author_name)) {
            $user = get_user_by('slug', $author_name);
            if ($user) {
                $author_id = $user->ID;
            }
        }
        
        // Force set the queried object to the correct author
        if ($author_id) {
            $author = get_userdata($author_id);
            if ($author) {
                global $wp_query;
                $wp_query->queried_object = $author;
                $wp_query->queried_object_id = $author_id;
            }
        }
    }
    
    return $posts;
}
add_filter('posts_results', 'eportfolio_fix_queried_object_for_filtered_author', 10, 2);

/**
 * Add JavaScript to handle menu clicks and set active states
 */
function eportfolio_add_content_type_filter_script() {
    if (!is_author()) {
        return;
    }
    
    $is_portfolio = get_query_var('portfolio_view');
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const isPortfolio = document.body.classList.contains('portfolio-view');
        const isAuthor = document.body.classList.contains('author');
        
        if (!isAuthor && !isPortfolio) {
            return;
        }

        console.log('Initializing server-side content type filter...');

        // Find the content type filter menu
        function findContentTypeMenu() {
            const navMenus = document.querySelectorAll('nav ul, .wp-block-navigation ul, .wp-block-navigation__container');
            
            for (const menu of navMenus) {
                const filterItems = menu.querySelectorAll('.content-filter-item, [data-filter]');
                if (filterItems.length > 0) {
                    console.log(`Found content type filter menu with ${filterItems.length} items`);
                    return menu;
                }
            }
            
            return null;
        }

        // Get current filter from URL
        function getCurrentFilter() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('content_type') || 'all';
        }

        // Build filtered URL
        function buildFilterUrl(filterValue) {
            const currentUrl = new URL(window.location.href);
            
            if (filterValue === 'all') {
                // Remove the content_type parameter
                currentUrl.searchParams.delete('content_type');
            } else {
                // Set the content_type parameter
                currentUrl.searchParams.set('content_type', filterValue);
            }
            
            // Remove pagination parameter when filtering
            currentUrl.searchParams.delete('paged');
            
            return currentUrl.toString();
        }

        // Setup menu filtering
        function setupMenuFiltering(menu) {
            const filterItems = menu.querySelectorAll('.content-filter-item, [data-filter]');
            
            if (filterItems.length === 0) {
                console.log('No filter items found in menu');
                return;
            }

            const currentFilter = getCurrentFilter();
            console.log('Current filter:', currentFilter);

            // Set active state based on current URL
            filterItems.forEach(item => {
                item.classList.remove('current-menu-item', 'active');
                const link = item.querySelector('a');
                
                // Get filter value
                let filterValue = null;
                if (link && link.dataset.filter) {
                    filterValue = link.dataset.filter;
                } else if (item.classList.contains('filter-all')) {
                    filterValue = 'all';
                } else {
                    // Extract from class name
                    const filterClass = Array.from(item.classList).find(cls => cls.startsWith('filter-'));
                    if (filterClass) {
                        filterValue = filterClass.replace('filter-', '');
                    }
                }
                
                // Set active state
                if (filterValue === currentFilter) {
                    item.classList.add('current-menu-item', 'active');
                    if (link) {
                        link.style.fontWeight = 'bold';
                        link.style.color = '#0073aa';
                        link.style.textDecoration = 'underline';
                    }
                    console.log('Set active state for:', filterValue);
                }
                
                // Update link href for server-side filtering
                if (link && filterValue) {
                    const filterUrl = buildFilterUrl(filterValue);
                    link.href = filterUrl;
                }
            });

            // Add CSS for active states
            if (!document.querySelector('#eportfolio-content-filter-styles')) {
                const style = document.createElement('style');
                style.id = 'eportfolio-content-filter-styles';
                style.textContent = `
                    .content-filter-item a {
                        text-decoration: none !important;
                        transition: all 0.3s ease;
                        border-bottom: none !important;
                        background-color: transparent;
                        padding: 4px 8px;
                        border-radius: 4px;
                        display: inline-block;
                    }
                    
                    .content-filter-item:hover a {
                        color: #005177;
                    }

                    .content-filter-item.current-menu-item a,
                    .content-filter-item.active a {
                        font-weight: bold !important;
                        color: #0073aa !important;
                        text-decoration: underline !important;
                    }
                    
                    /* Portfolio link styling - visually separate */
                    .portfolio-link-item {
                        margin-left: 1rem;
                        padding-left: 1rem;
                        border-left: 2px solid #ddd;
                    }
                    
                    .portfolio-link-item a {
                        background-color: #0073aa !important;
                        color: white !important;
                        padding: 6px 12px !important;
                        border-radius: 4px;
                        font-weight: 500 !important;
                        text-decoration: none !important;
                    }
                    
                    .portfolio-link-item a:hover {
                        background-color: #005a87 !important;
                        transform: translateX(3px);
                    }
                `;
                document.head.appendChild(style);
            }
        }

        // Initialize
        function init() {
            const filterMenu = findContentTypeMenu();
            
            if (!filterMenu) {
                console.log('Content type filter menu not found.');
                return;
            }
            
            // Handle portfolio link
            const portfolioLink = filterMenu.querySelector('.portfolio-link-item a, [data-portfolio-link]');
            if (portfolioLink) {
                if (isPortfolio) {
                    // Hide portfolio link when already on portfolio page
                    const portfolioItem = portfolioLink.closest('li');
                    if (portfolioItem) {
                        portfolioItem.style.display = 'none';
                    }
                } else if (isAuthor) {
                    // On author archive, set the correct portfolio URL
                    const authorSlug = document.body.className.match(/author-([^\s]+)/);
                    if (authorSlug && authorSlug[1]) {
                        // Build clean portfolio URL without ANY query parameters
                        // Start fresh from the current page origin
                        const currentUrl = new URL(window.location.href);
                        
                        // Parse the pathname to extract subdirectory if present
                        // We need to identify if we're in a subdirectory install
                        const pathParts = currentUrl.pathname.split('/').filter(part => part.length > 0);
                        
                        // Check if first path segment is NOT 'author' or 'portfolio'
                        // If so, it's likely the subdirectory name (e.g., 'oct10')
                        let sitePath = '';
                        if (pathParts.length > 0 && pathParts[0] !== 'author' && pathParts[0] !== 'portfolio') {
                            sitePath = '/' + pathParts[0];
                        }
                        
                        // Build completely clean URL: origin + subdirectory + /portfolio/username/
                        // DO NOT include any query parameters from current URL
                        const portfolioUrl = currentUrl.origin + sitePath + '/portfolio/' + authorSlug[1] + '/';
                        portfolioLink.href = portfolioUrl;
                    }
                }
            }
            
            setupMenuFiltering(filterMenu);
            console.log('Server-side content type filter initialized!');
        }

        init();
    });
    </script>
    <?php
}
add_action('wp_footer', 'eportfolio_add_content_type_filter_script');

/**
 * Add taxonomy classes to posts for visual indication
 */
function eportfolio_add_content_type_classes_to_posts($classes, $class, $post_id) {
    if (is_admin()) return $classes;
    
    $content_types = get_the_terms($post_id, 'content_type');
    if ($content_types && !is_wp_error($content_types)) {
        foreach ($content_types as $content_type) {
            $classes[] = 'content-type-' . $content_type->slug;
        }
    }
    return $classes;
}
add_filter('post_class', 'eportfolio_add_content_type_classes_to_posts', 10, 3);

/**
 * Admin notice about the menu
 */
function eportfolio_content_type_menu_admin_notice() {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'nav-menus') {
        echo '<div class="notice notice-info"><p>';
        echo '<strong>Content Type Filter Menu:</strong> The "Content Type Filter" menu is automatically maintained by the ePortfolio theme. ';
        echo 'Add it to your author/portfolio templates using the Navigation block. Filtering works server-side with full pagination support.';
        echo '</p></div>';
    }
}
add_action('admin_notices', 'eportfolio_content_type_menu_admin_notice');

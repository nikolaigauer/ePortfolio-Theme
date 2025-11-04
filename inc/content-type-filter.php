<?php
/**
 * Content Type Filter (CLIENT-SIDE DEMO VERSION)
 * 
 * SIMPLIFIED FOR ETUG DEMO - Filters only posts currently loaded on page
 * For production version, see: BACKUP-content-type-filter-ROBUST-SERVER-SIDE.php
 * 
 * This version:
 * - Filters posts client-side (show/hide with JavaScript)
 * - Only filters posts currently visible in the feed
 * - Faster for demos, but won't handle pagination properly
 * - Does NOT perform actual WP_Query filtering
 * 
 * Creates a dynamic WordPress nav menu for filtering by content type
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
 * Add taxonomy classes to posts for visual indication
 * This is what allows client-side filtering to work
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
 * CLIENT-SIDE FILTERING JavaScript
 * This shows/hides posts already in the feed based on their content type classes
 */
function eportfolio_add_content_type_filter_script() {
    if (!is_author()) {
        return;
    }
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const isPortfolio = document.body.classList.contains('portfolio-view');
        const isAuthor = document.body.classList.contains('author');
        
        if (!isAuthor && !isPortfolio) {
            return;
        }

        console.log('Initializing CLIENT-SIDE content type filter (demo version)...');

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

        // Get all posts in the feed
        function getAllPosts() {
            // Try multiple selectors to find post elements
            const selectors = [
                '.wp-block-post',
                'article[class*="post-"]',
                '.post',
                '[class*="wp-block-post-template"] > *'
            ];
            
            let posts = [];
            for (const selector of selectors) {
                posts = document.querySelectorAll(selector);
                if (posts.length > 0) {
                    console.log(`Found ${posts.length} posts using selector: ${selector}`);
                    break;
                }
            }
            
            return posts;
        }

        // Filter posts by content type (show/hide)
        function filterPosts(contentType) {
            const posts = getAllPosts();
            let visibleCount = 0;
            
            posts.forEach(post => {
                if (contentType === 'all') {
                    // Show all posts
                    post.style.display = '';
                    visibleCount++;
                } else {
                    // Check if post has the content type class
                    const hasContentType = post.classList.contains('content-type-' + contentType);
                    
                    if (hasContentType) {
                        post.style.display = '';
                        visibleCount++;
                    } else {
                        post.style.display = 'none';
                    }
                }
            });
            
            console.log(`Filter: ${contentType} - Showing ${visibleCount} of ${posts.length} posts`);
            
            // Show "no results" message if needed
            showNoResultsMessage(visibleCount === 0, contentType);
        }

        // Show/hide no results message
        function showNoResultsMessage(show, contentType) {
            let noResultsDiv = document.querySelector('.eportfolio-no-results');
            
            if (show) {
                if (!noResultsDiv) {
                    noResultsDiv = document.createElement('div');
                    noResultsDiv.className = 'eportfolio-no-results';
                    noResultsDiv.style.cssText = 'padding: 3rem 1rem; text-align: center; color: #666; font-size: 1.1em;';
                    
                    const posts = getAllPosts();
                    if (posts.length > 0) {
                        posts[0].parentElement.appendChild(noResultsDiv);
                    }
                }
                noResultsDiv.innerHTML = `<p>📭 No posts found with content type: <strong>${contentType}</strong></p>`;
                noResultsDiv.style.display = 'block';
            } else {
                if (noResultsDiv) {
                    noResultsDiv.style.display = 'none';
                }
            }
        }

        // Setup menu filtering
        function setupMenuFiltering(menu) {
            const filterItems = menu.querySelectorAll('.content-filter-item, [data-filter]');
            
            if (filterItems.length === 0) {
                console.log('No filter items found in menu');
                return;
            }

            // Set "All" as active by default
            filterItems.forEach(item => {
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
                
                // Set active state for "All"
                if (filterValue === 'all') {
                    item.classList.add('current-menu-item', 'active');
                }
                
                // Add click handler
                if (link && filterValue) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        // Update active states
                        filterItems.forEach(item => {
                            item.classList.remove('current-menu-item', 'active');
                        });
                        item.classList.add('current-menu-item', 'active');
                        
                        // Filter posts
                        filterPosts(filterValue);
                    });
                    
                    // Prevent default link behavior
                    link.href = 'javascript:void(0)';
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
                        cursor: pointer;
                    }
                    
                    .content-filter-item:hover a {
                        color: #005177;
                        background-color: #f0f0f0;
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
                    
                    /* Smooth transitions for filtered posts */
                    .wp-block-post,
                    article[class*="post-"] {
                        transition: opacity 0.3s ease, transform 0.3s ease;
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
                        const currentUrl = new URL(window.location.href);
                        const pathParts = currentUrl.pathname.split('/').filter(part => part.length > 0);
                        
                        let sitePath = '';
                        if (pathParts.length > 0 && pathParts[0] !== 'author' && pathParts[0] !== 'portfolio') {
                            sitePath = '/' + pathParts[0];
                        }
                        
                        const portfolioUrl = currentUrl.origin + sitePath + '/portfolio/' + authorSlug[1] + '/';
                        portfolioLink.href = portfolioUrl;
                    }
                }
            }
            
            setupMenuFiltering(filterMenu);
            console.log('CLIENT-SIDE content type filter initialized! (Demo version - filters posts on page only)');
        }

        init();
    });
    </script>
    <?php
}
add_action('wp_footer', 'eportfolio_add_content_type_filter_script');

/**
 * Admin notice about the menu
 */
function eportfolio_content_type_menu_admin_notice() {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'nav-menus') {
        echo '<div class="notice notice-warning"><p>';
        echo '<strong>Content Type Filter Menu (DEMO VERSION):</strong> The "Content Type Filter" menu is automatically maintained by the ePortfolio theme. ';
        echo 'This is currently using CLIENT-SIDE filtering for demo purposes (filters only visible posts). ';
        echo 'For production, restore BACKUP-content-type-filter-ROBUST-SERVER-SIDE.php for proper server-side filtering with pagination support.';
        echo '</p></div>';
    }
}
add_action('admin_notices', 'eportfolio_content_type_menu_admin_notice');

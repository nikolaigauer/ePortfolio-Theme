<?php
/**
 * Shortcode: [archive_navigation]
 * Smart navigation that shows different links based on whether you're on portfolio or author page
 * On /author/student-name: Shows link to their portfolio
 * On /portfolio/student-name: Shows link to their full archive
 * 
 * Usage: [archive_navigation]
 */
add_shortcode('archive_navigation', 'eportfolio_archive_navigation_shortcode');
function eportfolio_archive_navigation_shortcode($atts) {
    // Only works on author archives
    if (!is_author()) {
        return '';
    }
    
    $atts = shortcode_atts(array(
        'show_filter' => 'true',
        'style' => 'default', // default, minimal, or buttons
    ), $atts);
    
    $author = get_queried_object();
    $is_portfolio = get_query_var('portfolio_view');
    
    $portfolio_url = home_url('/portfolio/' . $author->user_nicename . '/');
    $archive_url = get_author_posts_url($author->ID);
    
    // Get post counts
    $total_posts = count_user_posts($author->ID, 'post', true);
    $portfolio_posts = eportfolio_get_portfolio_count($author->ID);
    
    ob_start();
    
    // Portfolio pages don't show navigation (they're the destination)
    if ($is_portfolio) {
        return ''; // Return empty string - portfolio is the final stop
    }
    
    // Only show navigation on author archive pages
    ?>
    <nav class="archive-navigation <?php echo esc_attr($atts['style']); ?>-style" aria-label="Archive navigation">
        <div class="archive-nav-links">
            <!-- We're on archive, show link to portfolio -->
            <span class="current-view">
                <strong>📚 Full Archive</strong>
                <small><?php echo $total_posts; ?> total posts</small>
            </span>
            <a href="<?php echo esc_url($portfolio_url); ?>" class="nav-link to-portfolio">
                <span class="link-text">
                    <strong>Portfolio</strong>
                    <small>View <?php echo $portfolio_posts; ?> curated posts</small>
                </span>
                <span class="arrow">→</span>
            </a>
        </div>
        
        <?php if ($atts['show_filter'] === 'true'): ?>
        <div class="filter-notice">
            <small>💡 Add the <strong>Content Type Filter</strong> navigation menu to filter posts</small>
        </div>
        <?php endif; ?>
    </nav>
    
    <style>
        .archive-navigation {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 2rem 0;
        }
        
        .archive-nav-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .archive-navigation .nav-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: white;
            border: 2px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }
        
        .archive-navigation .nav-link:hover {
            border-color: #0073aa;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .archive-navigation .link-text {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .archive-navigation .link-text strong {
            font-size: 1rem;
            color: #0073aa;
        }
        
        .archive-navigation .link-text small {
            font-size: 0.85rem;
            color: #666;
        }
        
        .archive-navigation .current-view {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            padding: 0.75rem 1.25rem;
            background: white;
            border: 2px solid #0073aa;
            border-radius: 6px;
        }
        
        .archive-navigation .current-view strong {
            font-size: 1rem;
            color: #0073aa;
        }
        
        .archive-navigation .current-view small {
            font-size: 0.85rem;
            color: #666;
        }
        
        .archive-navigation .arrow {
            font-size: 1.25rem;
            font-weight: bold;
            color: #0073aa;
        }
        
        .filter-notice {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #ddd;
            text-align: center;
        }
        
        .filter-notice small {
            color: #666;
        }
        
        /* Minimal style variant */
        .archive-navigation.minimal-style {
            background: transparent;
            padding: 1rem 0;
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
        }
        
        .archive-navigation.minimal-style .nav-link,
        .archive-navigation.minimal-style .current-view {
            background: transparent;
            border: none;
            padding: 0.5rem;
        }
        
        /* Buttons style variant */
        .archive-navigation.buttons-style .nav-link {
            background: #0073aa;
            border-color: #0073aa;
        }
        
        .archive-navigation.buttons-style .nav-link .link-text strong,
        .archive-navigation.buttons-style .nav-link .arrow {
            color: white;
        }
        
        .archive-navigation.buttons-style .nav-link:hover {
            background: #005a87;
            border-color: #005a87;
        }
    </style>
    <?php
    
    return ob_get_clean();
}

/**
 * Shortcodes for dynamic content
 * Provides shortcodes for use in templates and patterns
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode: [site_home_link]
 * Returns a link to the site homepage
 * 
 * Usage: [site_home_link text="Back to Cohort"]
 */
add_shortcode('site_home_link', 'eportfolio_site_home_link_shortcode');
function eportfolio_site_home_link_shortcode($atts) {
    $atts = shortcode_atts(array(
        'text' => 'Back to Site Home',
        'arrow' => 'true',
    ), $atts);
    
    $arrow = ($atts['arrow'] === 'true') ? '⏎ ' : '';
    $home_url = home_url('/');
    $site_name = get_bloginfo('name');
    
    return sprintf(
        '<a href="%s">%s%s</a>',
        esc_url($home_url),
        $arrow,
        esc_html($atts['text'])
    );
}

/**
 * Shortcode: [portfolio_link]
 * Returns a link to current author's portfolio
 * Only works on author archives and single posts
 * 
 * Usage: [portfolio_link text="View My Portfolio"]
 */
add_shortcode('portfolio_link', 'eportfolio_portfolio_link_shortcode');
function eportfolio_portfolio_link_shortcode($atts) {
    $atts = shortcode_atts(array(
        'text' => 'My Portfolio',
        'arrow' => 'false',
    ), $atts);
    
    // Try to get author from context
    if (is_author()) {
        $author = get_queried_object();
        $author_id = $author->ID;
    } elseif (is_singular('post')) {
        $author_id = get_post_field('post_author', get_the_ID());
    } else {
        return ''; // Not in a valid context
    }
    
    $user_data = get_userdata($author_id);
    if (!$user_data) {
        return '';
    }
    
    $arrow = ($atts['arrow'] === 'true') ? '→ ' : '';
    $portfolio_url = home_url('/portfolio/' . $user_data->user_nicename . '/');
    
    return sprintf(
        '<a href="%s">%s%s</a>',
        esc_url($portfolio_url),
        $arrow,
        esc_html($atts['text'])
    );
}

/**
 * Shortcode: [author_archive_link]
 * Returns a link to current author's full archive
 * Only works on author archives and single posts
 * 
 * Usage: [author_archive_link text="All Posts"]
 */
add_shortcode('author_archive_link', 'eportfolio_author_archive_link_shortcode');
function eportfolio_author_archive_link_shortcode($atts) {
    $atts = shortcode_atts(array(
        'text' => 'All Posts',
        'arrow' => 'false',
    ), $atts);
    
    // Try to get author from context
    if (is_author()) {
        $author = get_queried_object();
        $author_id = $author->ID;
    } elseif (is_singular('post')) {
        $author_id = get_post_field('post_author', get_the_ID());
    } else {
        return ''; // Not in a valid context
    }
    
    $arrow = ($atts['arrow'] === 'true') ? '→ ' : '';
    $author_url = get_author_posts_url($author_id);
    
    return sprintf(
        '<a href="%s">%s%s</a>',
        esc_url($author_url),
        $arrow,
        esc_html($atts['text'])
    );
}

/**
 * Shortcode: [cohort_link]
 * Returns a link back to the main home page
 * 
 * Usage: [cohort_link text="Back"]
 */
add_shortcode('cohort_link', 'eportfolio_cohort_link_shortcode');
function eportfolio_cohort_link_shortcode($atts) {
    $atts = shortcode_atts(array(
        'text' => 'Back',
        'arrow' => 'true',
    ), $atts);
    
    $arrow = ($atts['arrow'] === 'true') ? '⏎ ' : '';
    
    // Check if there's a custom URL set in options
    $cohort_url = get_option('eportfolio_cohort_url', home_url('/'));
    
    return sprintf(
        '<a href="%s">%s%s</a>',
        esc_url($cohort_url),
        $arrow,
        esc_html($atts['text'])
    );
}

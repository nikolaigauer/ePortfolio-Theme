<?php
/**
 * Standalone Portfolio Link System
 * 
 * Provides JavaScript-based portfolio link functionality for manual menu building.
 * Works with CSS class trigger: "portfolio-link-auto"
 * 
 * Usage: Add Custom Link with URL="#" and CSS class "portfolio-link-auto"
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add portfolio link JavaScript to author archive pages
 */
function eportfolio_add_portfolio_link_script() {
    if (!is_author()) {
        return;
    }
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Initializing portfolio link system...');
        
        const isPortfolio = document.body.classList.contains('portfolio-view');
        const isAuthor = document.body.classList.contains('author');
        
        if (!isAuthor && !isPortfolio) {
            return;
        }

        // Find all portfolio link elements
        const portfolioLinks = document.querySelectorAll('.portfolio-link-auto a, a.portfolio-link-auto');
        
        if (portfolioLinks.length === 0) {
            console.log('No portfolio links found with class "portfolio-link-auto"');
            return;
        }
        
        console.log(`Found ${portfolioLinks.length} portfolio link(s)`);

        portfolioLinks.forEach(function(portfolioLink) {
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
                    
                    // Set default text if empty
                    if (!portfolioLink.textContent.trim() || portfolioLink.textContent === '#') {
                        portfolioLink.textContent = 'Portfolio →';
                    }
                    
                    console.log(`Portfolio link updated: ${portfolioUrl}`);
                }
            }
        });

        // Add CSS for portfolio link styling if not already present
        if (!document.querySelector('#eportfolio-portfolio-link-styles')) {
            const style = document.createElement('style');
            style.id = 'eportfolio-portfolio-link-styles';
            style.textContent = `
                /* Portfolio link styling */
                .portfolio-link-auto a,
                a.portfolio-link-auto {
                    background-color: #0073aa !important;
                    color: white !important;
                    padding: 6px 12px !important;
                    border-radius: 4px;
                    font-weight: 500 !important;
                    text-decoration: none !important;
                    transition: all 0.3s ease;
                    display: inline-block;
                }
                
                .portfolio-link-auto a:hover,
                a.portfolio-link-auto:hover {
                    background-color: #005a87 !important;
                    transform: translateX(3px);
                    color: white !important;
                }
                
                /* Visual separation in menus */
                .portfolio-link-auto {
                    margin-left: 1rem;
                    padding-left: 1rem;
                    border-left: 2px solid #ddd;
                }
            `;
            document.head.appendChild(style);
        }
        
        console.log('Portfolio link system initialized!');
    });
    </script>
    <?php
}
add_action('wp_footer', 'eportfolio_add_portfolio_link_script');
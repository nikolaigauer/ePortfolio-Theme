<?php
/**
 * Content-Type Filter Helpers
 *
 * The Content Types menu generator (inc/admin-menu.php) builds nav items whose
 * URL is a RELATIVE query string — "?content-type=slug" — so each filter link
 * automatically scopes to whichever /author/username/ archive it is rendered
 * on. preserve_author_on_taxonomy_filter() (functions.php) applies the filter
 * server-side. This module adds the two pieces the relative-URL approach can't
 * provide on its own:
 *
 *   1. An "All / show everything" link (CSS class "content-type-all") that
 *      clears the filter by pointing at the current author archive base URL.
 *   2. A "current-content-type" class on whichever filter link matches the
 *      active ?content-type, so the selected filter can be styled.
 *
 * Both are resolved in the browser (like portfolio-link.php) because FSE
 * Navigation blocks render via wp_navigation post content and bypass the
 * wp_get_nav_menu_items PHP filter.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_footer', 'eportfolio_content_type_filter_script' );
function eportfolio_content_type_filter_script() {
    if ( ! is_author() ) {
        return;
    }
    ?>
    <script>
    document.addEventListener( 'DOMContentLoaded', function () {
        var body = document.body;
        if ( ! body.classList.contains( 'author' ) && ! body.classList.contains( 'portfolio-view' ) ) {
            return;
        }

        var params = new URLSearchParams( window.location.search );
        var active = params.get( 'content-type' );

        function mark( el ) {
            ( el.closest( 'li' ) || el ).classList.add( 'current-content-type' );
        }

        // "All" links clear the filter: point them at the filterless archive URL.
        document.querySelectorAll( '.content-type-all a, a.content-type-all' ).forEach( function ( a ) {
            a.setAttribute( 'href', window.location.pathname );
            if ( ! active ) {
                mark( a );
            }
        } );

        // Highlight whichever filter link matches the active content-type.
        if ( active ) {
            document.querySelectorAll( 'a[href*="content-type="]' ).forEach( function ( a ) {
                var m = ( a.getAttribute( 'href' ) || '' ).match( /content-type=([^&]+)/ );
                if ( m && decodeURIComponent( m[1] ) === active ) {
                    mark( a );
                }
            } );
        }
    } );
    </script>
    <?php
}

/**
 * Minimal styling hook for the active filter. Kept deliberately light so the
 * theme's own typography wins; instructors can override .current-content-type.
 */
add_action( 'wp_head', 'eportfolio_content_type_filter_css' );
function eportfolio_content_type_filter_css() {
    if ( ! is_author() ) {
        return;
    }
    echo '<style id="eportfolio-content-type-filter">'
       . '.current-content-type > a, a.current-content-type { font-weight: 700; text-decoration: underline; }'
       . '</style>' . "\n";
}

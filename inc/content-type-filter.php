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
 * Content-type slugs the currently-viewed author has actually published in.
 *
 * On /author/ this is any published post; on /portfolio/ it is narrowed to
 * portfolio-public posts, so the filter set matches what the visitor can see.
 * Counts the term itself only (no hierarchy descent). Returns slugs.
 */
function eportfolio_author_used_content_types() {
    $author_id = (int) get_queried_object_id();
    if ( ! $author_id ) {
        return array();
    }

    $args = array(
        'author'                 => $author_id,
        'post_type'              => 'post',
        'post_status'            => 'publish',
        'posts_per_page'         => -1,
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    );

    // Portfolio view: only count posts the student has opted into the portfolio.
    if ( get_query_var( 'portfolio_view' ) ) {
        $args['meta_query'] = array(
            array( 'key' => '_is_public_portfolio', 'value' => '1', 'compare' => '=' ),
        );
    }

    $post_ids = get_posts( $args );
    if ( empty( $post_ids ) ) {
        return array();
    }

    $slugs = wp_get_object_terms( $post_ids, 'content-type', array( 'fields' => 'slugs' ) );
    if ( is_wp_error( $slugs ) ) {
        return array();
    }

    return array_values( array_unique( $slugs ) );
}

/**
 * Active-filter styling + per-student responsiveness: hide filter items for any
 * content type the viewed student hasn't posted in, so empty types never lead to
 * a "nothing found" result. The "All" item (content-type-all) is never hidden.
 *
 * CSS-based hiding mirrors portfolio-link.php and works for both FSE Navigation
 * blocks and classic menus. WordPress's own hide_empty counts posts site-wide,
 * so it can't answer this per-author question — hence the runtime check.
 */
add_action( 'wp_head', 'eportfolio_content_type_filter_css' );
function eportfolio_content_type_filter_css() {
    if ( ! is_author() ) {
        return;
    }

    $css = '.current-content-type > a, a.current-content-type { font-weight: 700; text-decoration: underline; }';

    $all_slugs = get_terms( array(
        'taxonomy'   => 'content-type',
        'hide_empty' => false,
        'fields'     => 'slugs',
    ) );

    if ( ! is_wp_error( $all_slugs ) && ! empty( $all_slugs ) ) {
        $empty = array_diff( $all_slugs, eportfolio_author_used_content_types() );
        foreach ( $empty as $slug ) {
            // Exact match on the relative ?content-type=slug filter URL.
            $sel  = '[href$="content-type=' . $slug . '"]';
            $css .= 'li:has(a' . $sel . '), a' . $sel . ' { display: none; }';
        }
    }

    echo '<style id="eportfolio-content-type-filter">' . $css . '</style>' . "\n";
}

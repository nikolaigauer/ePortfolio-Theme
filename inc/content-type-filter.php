<?php
/**
 * Content-Type Filter Helpers
 *
 * The Content Types menu generator (inc/admin-menu.php) builds nav items whose
 * URL is a RELATIVE query string — "?content-type=slug" — so each filter link
 * automatically scopes to whichever /author/username/ archive it is rendered
 * on. preserve_author_on_taxonomy_filter() (functions.php) applies the filter
 * server-side. This module adds the three pieces the relative-URL approach
 * can't provide on its own, all in one footer script so they share the same
 * (proven) href parsing:
 *
 *   1. An "All / show everything" link (CSS class "content-type-all") that
 *      clears the filter by pointing at the current author archive base URL.
 *   2. A "current-content-type" class on whichever filter link matches the
 *      active ?content-type, so the selected filter can be styled.
 *   3. Per-student responsiveness: filter links for content types the viewed
 *      student hasn't posted in are hidden, so empty types never lead to a
 *      "nothing found" result. (WordPress's own hide_empty counts posts
 *      site-wide, so it can't answer this per-author question.) Any
 *      navigation submenu (e.g. a "Filter" dropdown) whose content-type
 *      links are all hidden is hidden too.
 *
 * Done in the browser (like portfolio-link.php) because FSE Navigation blocks
 * render via wp_navigation post content and bypass the wp_get_nav_menu_items
 * PHP filter — and JS DOM walking avoids any dependence on :has() CSS support
 * or the exact (relative vs absolute) form of the rendered href.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The active ?content-type= filter slug on an author/portfolio view, or ''
 * when there is none or the term doesn't exist.
 */
function eportfolio_active_content_type() {
    if ( ! is_author() || empty( $_GET['content-type'] ) ) {
        return '';
    }
    $slug = sanitize_title( wp_unslash( $_GET['content-type'] ) );
    return term_exists( $slug, 'content-type' ) ? $slug : '';
}

/**
 * Apply the active content-type filter to non-inherit Query Loops too.
 *
 * preserve_author_on_taxonomy_filter() (functions.php) filters only the MAIN
 * query. The single-post layout's sidebar (the post list in the <details>
 * drawer) is its own Query Loop with inherit:false, so without this it keeps
 * listing every post while the main column shows the filtered one.
 */
add_filter( 'query_loop_block_query_vars', 'eportfolio_content_type_block_query', 20, 2 );
function eportfolio_content_type_block_query( $query, $block ) {
    if ( ! empty( $block->attributes['query']['inherit'] ) ) {
        return $query; // main query handles itself
    }
    $slug = eportfolio_active_content_type();
    if ( ! $slug ) {
        return $query;
    }
    $tax_query   = isset( $query['tax_query'] ) && is_array( $query['tax_query'] ) ? $query['tax_query'] : array();
    $tax_query[] = array(
        'taxonomy' => 'content-type',
        'field'    => 'slug',
        'terms'    => $slug,
    );
    $query['tax_query'] = $tax_query;
    return $query;
}

/**
 * Keep the sidebar drawer open while browsing.
 *
 * A filter click is a full page load, which re-renders the <details> drawer
 * closed. When a content-type filter or a ?show= post is active, render any
 * Details block that contains a Query Loop (the post-list drawer) open.
 */
add_filter( 'render_block_core/details', 'eportfolio_open_post_list_details', 10, 2 );
function eportfolio_open_post_list_details( $block_content, $block ) {
    if ( ! eportfolio_active_content_type() && empty( $_GET['show'] ) ) {
        return $block_content;
    }
    if ( ! eportfolio_block_contains( $block, 'core/query' ) ) {
        return $block_content;
    }
    return preg_replace( '/<details\b(?![^>]*\bopen\b)/', '<details open', $block_content, 1 );
}

/** Whether a parsed block has a descendant of the given block type. */
function eportfolio_block_contains( $block, $name ) {
    foreach ( $block['innerBlocks'] ?? array() as $inner ) {
        if ( ( $inner['blockName'] ?? '' ) === $name || eportfolio_block_contains( $inner, $name ) ) {
            return true;
        }
    }
    return false;
}

/**
 * Content-type slugs the currently-viewed author has actually published in.
 *
 * On /author/ this is any published post; on /portfolio/ it is narrowed to
 * portfolio-public posts, so the filter set matches what the visitor can see.
 * Counts the term itself only (no hierarchy descent). Returns string slugs.
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
    if ( is_wp_error( $slugs ) || empty( $slugs ) ) {
        return array();
    }

    return array_values( array_unique( array_map( 'strval', $slugs ) ) );
}

add_action( 'wp_footer', 'eportfolio_content_type_filter_script' );
function eportfolio_content_type_filter_script() {
    if ( ! is_author() ) {
        return;
    }

    $used = wp_json_encode( eportfolio_author_used_content_types() );
    ?>
    <script>
    ( function () {
        document.addEventListener( 'DOMContentLoaded', function () {
            var body = document.body;
            if ( ! body.classList.contains( 'author' ) && ! body.classList.contains( 'portfolio-view' ) ) {
                return;
            }

            var used   = <?php echo $used; ?> || [];
            var params = new URLSearchParams( window.location.search );
            var active = params.get( 'content-type' );

            function slugOf( a ) {
                var m = ( a.getAttribute( 'href' ) || '' ).match( /content-type=([^&]+)/ );
                return m ? decodeURIComponent( m[1] ) : null;
            }
            function item( a ) { return a.closest( 'li' ) || a; }

            // "All" links clear the filter: point them at the filterless archive URL.
            document.querySelectorAll( '.content-type-all a, a.content-type-all' ).forEach( function ( a ) {
                a.setAttribute( 'href', window.location.pathname );
                if ( ! active ) {
                    item( a ).classList.add( 'current-content-type' );
                }
            } );

            // Walk every content-type filter link: hide types the student hasn't
            // posted in, and highlight the one matching the active filter.
            var hidden = [];
            document.querySelectorAll( 'a[href*="content-type="]' ).forEach( function ( a ) {
                if ( a.closest( '.content-type-all' ) || a.classList.contains( 'content-type-all' ) ) {
                    return;
                }
                var slug = slugOf( a );
                if ( ! slug ) {
                    return;
                }
                if ( used.indexOf( slug ) === -1 ) {
                    item( a ).style.display = 'none';
                    hidden.push( slug );
                } else if ( active && slug === active ) {
                    item( a ).classList.add( 'current-content-type' );
                }
            } );

            // Collapse submenus (e.g. a "Filter" dropdown grouping the types)
            // left with no visible items. Deepest first, so nested submenus
            // that empty out also empty their parents.
            var submenus = Array.prototype.slice.call(
                document.querySelectorAll( '.wp-block-navigation-submenu, .wp-block-navigation-item.has-child' )
            ).reverse();
            submenus.forEach( function ( sub ) {
                var list = sub.querySelector( ':scope > ul' );
                if ( ! list || ! list.querySelector( 'a[href*="content-type="]' ) ) {
                    return;
                }
                var visible = Array.prototype.some.call( list.children, function ( li ) {
                    return li.style.display !== 'none';
                } );
                if ( ! visible ) {
                    sub.style.display = 'none';
                }
            } );

            if ( window.console && console.debug ) {
                console.debug( '[eportfolio] content-type filter — used:', used, 'hidden:', hidden );
            }
        } );
    }() );
    </script>
    <?php
}

/**
 * Light styling hook for the active filter. Kept deliberately minimal so the
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

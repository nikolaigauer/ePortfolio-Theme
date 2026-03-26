<?php
/**
 * ePortfolio Theme 2
 * Copyright (C) 2026 Nikolai Gauer
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * Full license text: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * This file loads all functionality modules from the /inc directory.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define theme constants
define('EPORTFOLIO_VERSION', '2.5.0');
define('EPORTFOLIO_DIR', get_stylesheet_directory());
define('EPORTFOLIO_URL', get_stylesheet_directory_uri());

// Register the content-type taxonomy (shared with ePortfoliohub)
add_action( 'init', 'eportfolio_register_content_type_taxonomy', 0 );
function eportfolio_register_content_type_taxonomy() {
    if ( taxonomy_exists( 'content-type' ) ) return; // already registered (e.g. by ePortfoliohub)
    register_taxonomy( 'content-type', array( 'post' ), array(
        'labels'            => array(
            'name'          => 'Content Types',
            'singular_name' => 'Content Type',
            'add_new_item'  => 'Add New Content Type',
            'edit_item'     => 'Edit Content Type',
        ),
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_in_rest'      => true, // required for block editor filter UI
        'rewrite'           => array( 'slug' => 'content-type' ),
        'capabilities'      => array(
            'manage_terms'  => 'edit_posts',
            'edit_terms'    => 'edit_posts',
            'delete_terms'  => 'edit_posts',
            'assign_terms'  => 'edit_posts',
        ),
    ) );
}

// Scope non-inherit Query Loop blocks on author archives to the current author
add_filter( 'query_loop_block_query_vars', 'eportfolio_author_scope_block_query', 10, 2 );
function eportfolio_author_scope_block_query( $query, $block ) {
    if ( ! is_author() || get_query_var( 'portfolio_view' ) ) return $query;
    if ( ! empty( $block->attributes['query']['inherit'] ) ) return $query; // main query handles itself
    $author = get_queried_object();
    if ( $author && isset( $author->ID ) ) {
        $query['author']      = $author->ID;
        $query['post_status'] = 'publish'; // never expose pending/draft to the author themselves
    }
    return $query;
}

// Preserve author context when filtering by content-type
function preserve_author_on_taxonomy_filter($query) {
    if (!is_admin() && $query->is_main_query() && is_author()) {
        // Force the author to remain set even with taxonomy filters
        $author_id = get_query_var('author');
        if ($author_id) {
            $query->set('author', $author_id);
        }
        
        // Add content-type filter if present
        if (isset($_GET['content-type']) && !empty($_GET['content-type'])) {
            $content_type = sanitize_text_field($_GET['content-type']);
            
            // Verify the term exists before querying
            if (term_exists($content_type, 'content-type')) {
                $query->set('tax_query', array(
                    array(
                        'taxonomy' => 'content-type',
                        'field' => 'slug',
                        'terms' => $content_type,
                    )
                ));
                
                // CRITICAL: Prevent WordPress from changing is_author to is_tax
                $query->is_tax = false;
                $query->is_archive = true;
                $query->is_author = true;
            }
        }
    }
}
add_action('pre_get_posts', 'preserve_author_on_taxonomy_filter', 1);

// Force the queried object to remain the author even with taxonomy filters
function force_author_queried_object($query) {
    if (!is_admin() && $query->is_main_query() && $query->is_author && isset($_GET['content-type'])) {
        // Get the author from the query
        $author_id = $query->get('author');
        if ($author_id) {
            $author = get_userdata($author_id);
            if ($author) {
                global $wp_query;
                $wp_query->queried_object = $author;
                $wp_query->queried_object_id = $author->ID;
            }
        }
    }
}
add_action('parse_query', 'force_author_queried_object', 20);

// Show one post at a time on author archives and portfolio pages; ?show=POST_ID to pick a specific one
add_action( 'pre_get_posts', 'eportfolio_author_single_post_query', 2 );
function eportfolio_author_single_post_query( $query ) {
    if ( is_admin() || ! $query->is_main_query() || ! $query->is_author ) return;

    $is_portfolio = (bool) get_query_var( 'portfolio_view' );

    // Resolve numeric author ID (may not yet be set as integer at this priority)
    $author_id = (int) $query->get( 'author' );
    if ( ! $author_id ) {
        $author_name = $query->get( 'author_name' );
        if ( $author_name ) {
            $user      = get_user_by( 'slug', $author_name );
            $author_id = $user ? $user->ID : 0;
        }
    }
    if ( ! $author_id ) return;

    $show = isset( $_GET['show'] ) ? intval( $_GET['show'] ) : 0;

    if ( $show ) {
        // Validate: post must exist, belong to this author, and be published
        $post  = get_post( $show );
        $valid = $post
            && (int) $post->post_author === $author_id
            && $post->post_status === 'publish';

        // On portfolio view also confirm the post is marked as portfolio-public
        if ( $valid && $is_portfolio ) {
            $valid = get_post_meta( $show, '_is_public_portfolio', true ) === '1';
        }

        if ( $valid ) {
            $query->set( 'post__in',       array( $show ) );
            $query->set( 'posts_per_page', 1 );
            $query->set( 'tax_query',      array() );
            $query->set( 'author',         $author_id );
            $query->is_tax     = false;
            $query->is_archive = true;
            $query->is_author  = true;
            return;
        }
    }

    // Default: show only the most-recent published post (portfolio-public on /portfolio/).
    // Explicitly set post_status to publish so logged-in authors don't see their own
    // pending/draft posts appearing in the archive before the instructor approves them.
    $query->set( 'posts_per_page', 1 );
    $query->set( 'post_status', 'publish' );
    if ( $is_portfolio ) {
        $query->set( 'meta_query', array(
            array(
                'key'     => '_is_public_portfolio',
                'value'   => '1',
                'compare' => '=',
            ),
        ) );
    }
}

// Rewrite core/post-title links on author archives and portfolio pages to ?show=POST_ID
// so clicking a post in the <details> nav stays on the same page
add_filter( 'render_block_core/post-title', 'eportfolio_rewrite_post_title_for_show', 10, 3 );
function eportfolio_rewrite_post_title_for_show( $block_content, $block, $instance ) {
    if ( ! is_author() ) return $block_content;
    if ( empty( $block['attrs']['isLink'] ) ) return $block_content;

    // Prefer context postId (set by Query Loop's post-template iteration)
    $post_id = isset( $instance->context['postId'] ) ? (int) $instance->context['postId'] : 0;
    if ( ! $post_id ) {
        global $post;
        $post_id = $post ? $post->ID : 0;
    }
    if ( ! $post_id ) return $block_content;

    $author = get_queried_object();
    if ( ! $author || ! isset( $author->ID ) ) return $block_content;

    // On portfolio view keep navigation within /portfolio/username/
    // On author archive keep navigation within /author/username/
    if ( get_query_var( 'portfolio_view' ) ) {
        $base_url = home_url( '/portfolio/' . $author->user_nicename . '/' );
    } else {
        $base_url = get_author_posts_url( $author->ID );
    }

    $post_url = esc_url( get_permalink( $post_id ) );
    $show_url = esc_url( add_query_arg( 'show', $post_id, $base_url ) );

    return str_replace( 'href="' . $post_url . '"', 'href="' . $show_url . '"', $block_content );
}

/**
 * Rewrite hardcoded content-type term IDs in Query Loop blocks to live IDs.
 *
 * Block templates store term IDs, which are site-specific and won't transfer
 * between subsites. This filter runs after WP has built the WP_Query args from
 * the block's taxQuery attribute, so we can safely swap in the correct IDs for
 * the current site without re-rendering the block.
 *
 * Logic: if a stored term ID is valid on this site, keep it (same ID, different
 * site is fine). If invalid and only one stored ID was given + only one term of
 * this taxonomy exists on the site, it's unambiguous — use that term. This
 * covers the standard single-term case (e.g. "Reflection" seeded on activation).
 */
add_filter( 'query_loop_block_query_vars', 'eportfolio_fix_content_type_term_ids', 5, 2 );
function eportfolio_fix_content_type_term_ids( $query, $block ) {
    if ( is_admin() || empty( $query['tax_query'] ) ) return $query;

    foreach ( $query['tax_query'] as &$clause ) {
        if ( ! is_array( $clause ) ) continue;
        if ( ( $clause['taxonomy'] ?? '' ) !== 'content-type' ) continue;
        if ( ( $clause['field']    ?? 'term_id' ) !== 'term_id' ) continue;

        $stored_ids = array_map( 'intval', (array) ( $clause['terms'] ?? array() ) );
        if ( empty( $stored_ids ) ) continue;

        $live_ids = array();
        foreach ( $stored_ids as $id ) {
            $term = get_term( $id, 'content-type' );
            if ( $term && ! is_wp_error( $term ) ) {
                $live_ids[] = $term->term_id;
            }
        }

        // Fallback for the common case: one stored ID that doesn't exist here,
        // but only one content-type term exists on the site — must be the same term.
        if ( empty( $live_ids ) && count( $stored_ids ) === 1 ) {
            $all_ids = get_terms( array(
                'taxonomy'   => 'content-type',
                'hide_empty' => false,
                'number'     => 2,
                'fields'     => 'ids',
            ) );
            if ( ! is_wp_error( $all_ids ) && count( $all_ids ) === 1 ) {
                $live_ids = array_map( 'intval', $all_ids );
            }
        }

        if ( ! empty( $live_ids ) ) {
            $clause['terms'] = $live_ids;
        }
    }

    return $query;
}

/**
 * Load functionality modules
 */
function eportfolio_load_modules() {
    $modules = array(
        'rewrite-rules',          // URL structure for /portfolio/
        'privacy-logic',          // Public/private toggle logic
        'admin-menu',             // Student dashboard menu
        'post-metabox',           // Portfolio post checkbox
        'template-filters',       // Template overrides and filters
        'shortcodes',             // Dynamic shortcodes for templates
        'portfolio-link',         // Standalone portfolio link system
    );

    // acf-fields, reflection-form, and post-form have been moved exclusively to the
    // reflection-submissions plugin. The theme no longer loads these modules.

    foreach ($modules as $module) {
        $file = EPORTFOLIO_DIR . '/inc/' . $module . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
add_action('after_setup_theme', 'eportfolio_load_modules');

/**
 * Seed default content-type terms so the taxonomy is usable out of the box.
 * Runs on activation and once on init (via option flag) for already-active installs.
 */
function eportfolio_seed_default_terms() {
    if ( ! taxonomy_exists( 'content-type' ) ) return;
    if ( ! term_exists( 'reflection', 'content-type' ) ) {
        wp_insert_term( 'Reflection', 'content-type', array( 'slug' => 'reflection' ) );
    }
}

// One-time init seed for installs where the theme is already active
add_action( 'init', function() {
    if ( get_option( 'eportfolio_terms_seeded' ) ) return;
    eportfolio_seed_default_terms();
    update_option( 'eportfolio_terms_seeded', '1' );
}, 2 ); // priority 2 — after taxonomy registration at priority 0

/**
 * Flush rewrite rules on theme activation
 */
function eportfolio_activate() {
    eportfolio_seed_default_terms();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'eportfolio_activate');

/**
 * Clean up on theme deactivation
 */
function eportfolio_deactivate() {
    flush_rewrite_rules();
}
add_action('switch_theme', 'eportfolio_deactivate');

/**
 * Fetch-based post switching for the ?show=POST_ID nav mechanic.
 *
 * Intercepts clicks on links containing "?show=" and replaces the contents of
 * .single-post-render in-place via fetch(), without a full page reload.
 * This keeps <details> elements open and avoids visible latency.
 *
 * Falls back to normal navigation if fetch fails or the container is missing.
 * Handles browser back/forward via the popstate event.
 *
 * Template requirement: add the CSS class "single-post-render" to the Group
 * or Query Loop wrapper that surrounds the main post display column in both
 * the archive and portfolio templates (one-time step in the Site Editor).
 */
add_action( 'wp_footer', 'eportfolio_show_nav_script' );
function eportfolio_show_nav_script() {
    if ( ! is_author() ) return;
    ?>
    <script>
    (function () {
        var SEL = '.single-post-render';

        function loadPost( url, push ) {
            var container = document.querySelector( SEL );
            if ( ! container ) return false;

            container.style.transition = 'opacity 0.15s ease';
            container.style.opacity   = '0.35';

            fetch( url )
                .then( function ( r ) { return r.text(); } )
                .then( function ( html ) {
                    var doc = new DOMParser().parseFromString( html, 'text/html' );
                    var fresh = doc.querySelector( SEL );
                    if ( fresh ) {
                        container.innerHTML = fresh.innerHTML;
                        if ( push !== false ) {
                            history.pushState( { url: url }, '', url );
                        }
                        // Re-init any scripts that ran inside the swapped content
                        container.querySelectorAll( 'script' ).forEach( function ( s ) {
                            var ns = document.createElement( 'script' );
                            ns.textContent = s.textContent;
                            s.parentNode.replaceChild( ns, s );
                        } );
                    }
                    container.style.opacity = '1';
                } )
                .catch( function () {
                    window.location.href = url; // graceful fallback
                } );

            return true;
        }

        // Intercept ?show= nav links
        document.addEventListener( 'click', function ( e ) {
            var link = e.target.closest( 'a[href*="?show="]' );
            if ( ! link ) return;
            if ( ! loadPost( link.href, true ) ) return; // container not found — let it navigate normally
            e.preventDefault();
        } );

        // Handle browser back / forward
        window.addEventListener( 'popstate', function () {
            loadPost( window.location.href, false );
        } );
    }());
    </script>
    <?php
}

/**
 * Filter Query Loop on portfolio pages to show only current author's posts
 * Works with metabox filtering (_is_public_portfolio meta key)
 */
add_filter('query_loop_block_query_vars', 'eportfolio_portfolio_query_author', 10, 2);
function eportfolio_portfolio_query_author($query, $block) {
    // Only on portfolio views
    if (get_query_var('portfolio_view') && is_author()) {
        $author = get_queried_object();
        if ($author && isset($author->ID)) {
            $query['author'] = $author->ID;
            
            // Filter to only show portfolio posts (metabox checked)
            $query['meta_query'] = array(
                array(
                    'key' => '_is_public_portfolio',
                    'value' => '1',
                    'compare' => '='
                )
            );
        }
    }
    return $query;
}


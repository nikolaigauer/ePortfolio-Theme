# ePortfolio Theme 2 — Project Notes

## Overview

**Theme**: `eportfolio-theme-2`
**Version**: 2.6.0
**Type**: Child theme of Twenty Twenty-Five (FSE/block theme)
**Environment**: MAMP multisite, local development
**Purpose**: ePortfolio platform for courses — students submit weekly reflections via frontend forms; instructors review; students have a curated portfolio view and a full archive view. Students can also create open posts via a simplified admin interface.

---

## Architecture

### Two URL modes per student
- `/author/username/` — full archive (all published posts, one at a time via `?show=POST_ID`)
- `/portfolio/username/` — curated portfolio (only posts with `_is_public_portfolio = 1`)
- Portfolio curation is **feature-flagged** (`eportfolio_feature_portfolio`, default `'0'`)

### Key URL mechanic
On both `/author/username/` and `/portfolio/username/`, the main query shows 1 post at a time:
- Default: most recent post
- `?show=POST_ID`: shows that specific post (validated to belong to the author; validated as portfolio post on `/portfolio/`)
- All `core/post-title` block links are rewritten to `?show=POST_ID` so navigation stays on the page
- A JS fetch interceptor swaps only the `.single-post-render` container on click, keeping `<details>` blocks open and avoiding full page reloads

### Privacy system
- Site-wide toggle: `eportfolio_site_is_public` option
- Per-portfolio toggle: `portfolio_is_public` user meta (student-controlled via ePortfolio dashboard)
- Per-submission privacy set by plugin post meta on the source page (publish / private / pending)
- `inc/privacy-logic.php` strips query strings before URL matching so `?show=POST_ID` requests pass through correctly for non-logged-in users

### Portfolio Curation feature flag
- Option: `eportfolio_feature_portfolio` (`'0'` = off, `'1'` = on), default off
- **Off**: students only have their author archive; no portfolio metabox on posts; student dashboard shows archive URL only
- **On**: portfolio checkbox on posts; student sees public/private toggle + both URLs on dashboard; `/portfolio/` URL is active
- Submissions **never** auto-push to portfolio — `_is_public_portfolio` is always `'0'` on submit; students opt-in manually
- Toggled from ePortfolio → Privacy Settings tab (admin only, left column)

### Taxonomy
- `content-type` (custom, hierarchical, `show_in_rest: true`)
- Always active regardless of feature flags
- Registered in `functions.php` with a `taxonomy_exists()` guard (safe to coexist with ePortfoliohub)
- Reflection submissions auto-tagged `Reflection` by default (overridable per page via plugin `content_type_label` meta field)
- Open posts can be tagged with any existing content-type terms via the New Post form

---

## File Map

| File | Purpose |
|---|---|
| `style.css` | Theme registration header (Theme Name, Text Domain, Version) |
| `functions.php` | Module loader + inline hooks (taxonomy, author query, post-title link rewrite, query loop scoping, JS fetch interceptor) |
| `inc/acf-fields.php` | **Stub only** — no longer loaded; ACF fully removed from theme |
| `inc/reflection-form.php` | **Stub only** — owned by reflection-submissions plugin |
| `inc/post-form.php` | **Stub only** — owned by reflection-submissions plugin |
| `inc/admin-menu.php` | Student dashboard + Privacy / Submissions tabs; "Reflection Page" toolbar shortcut |
| `inc/template-filters.php` | Swaps `author` template → `archive` template on front-end (WP 6.7+ fix) |
| `inc/rewrite-rules.php` | `/portfolio/username/` rewrite rules + `portfolio_view` query var |
| `inc/privacy-logic.php` | Public/private visibility enforcement |
| `inc/post-metabox.php` | Portfolio checkbox metabox on posts (feature-flagged); Privacy metabox on pages |
| `inc/shortcodes.php` | `[cohort_link]`, `[portfolio_link]`, `[author_archive_link]`, `[archive_navigation]` |
| `inc/portfolio-link.php` | Standalone portfolio link system |
| `templates/archive.html` | Author archive template (left nav column + right content column) |
| `templates/author.html` | Portfolio view template |
| `parts/header-student.html` | Archive page header |
| `parts/header-portfolio.html` | Portfolio page header |

---

## Key Hooks in functions.php

```php
// Register content-type taxonomy
add_action( 'init', 'eportfolio_register_content_type_taxonomy', 0 );

// Preserve author context when ?content-type= filter is active
add_action( 'pre_get_posts', 'preserve_author_on_taxonomy_filter', 1 );

// Force queried object to remain the author with taxonomy filters
add_action( 'parse_query', 'force_author_queried_object', 20 );

// Single-post display mode on author archives + ?show=POST_ID handler
// Handles both /author/ and /portfolio/ pages; validates _is_public_portfolio on portfolio
add_action( 'pre_get_posts', 'eportfolio_author_single_post_query', 2 );

// Rewrite core/post-title links to ?show=POST_ID on author archives and portfolio pages
add_filter( 'render_block_core/post-title', 'eportfolio_rewrite_post_title_for_show', 10, 3 );

// Scope non-inherit Query Loop blocks to current author on author archives
add_filter( 'query_loop_block_query_vars', 'eportfolio_author_scope_block_query', 10, 2 );

// Scope portfolio Query Loop blocks to author + _is_public_portfolio posts
add_filter( 'query_loop_block_query_vars', 'eportfolio_portfolio_query_author', 10, 2 );

// JS fetch interceptor for ?show= navigation (keeps <details> open, no full page reload)
// Targets elements with CSS class: .single-post-render
add_action( 'wp_footer', 'eportfolio_show_nav_script' );

// Seed "Reflection" content-type term on theme activation (and once on init via option flag)
// Ensures the term exists on fresh subsites without any manual setup
add_action( 'after_switch_theme', 'eportfolio_activate' ); // calls eportfolio_seed_default_terms()
add_action( 'init', /* anonymous, checks eportfolio_terms_seeded option */ );

// Remap hardcoded content-type term IDs in Query Loop blocks to live site IDs
// Block templates store term IDs which are site-specific; this fixes them at runtime
// Handles single-term fallback: if stored ID is invalid + only one term exists → use it
add_filter( 'query_loop_block_query_vars', 'eportfolio_fix_content_type_term_ids', 5, 2 );
```

---

## inc/post-form.php — New Post Form

Adds **ePortfolio → New Post** submenu. Students compose a post using typed sections without touching Gutenberg.

### Section types
| Type | Input | Block output |
|---|---|---|
| Text | Textarea (double newline = new paragraph) | `wp:paragraph` blocks |
| Image(s) | WP media modal (multi-select) | `wp:image` (1 image) or `wp:gallery` (2+) |
| Embed | URL input | `wp:embed` |

Sections are serialised to JSON on submit → `eportfolio_assemble_block_content()` builds valid Gutenberg block markup → `wp_insert_post()`.

### Behaviour
- Blank title is allowed (WP uses post ID as slug)
- **Publish**: redirects to student's author archive
- **Save Draft**: stays on form, shows "Edit it →" link to WP edit screen
- Content-type checkboxes shown if any terms exist in the `content-type` taxonomy
- Portfolio toggle shown only when `eportfolio_feature_portfolio === '1'`
- For Author-role users: admin bar "+ New" button is a direct link to this form (no dropdown); admins keep the full dropdown

### Hooks in post-form.php
```php
add_action( 'admin_menu',            'eportfolio_post_form_register_submenu' );
add_action( 'admin_enqueue_scripts', 'eportfolio_post_form_enqueue' );      // wp_enqueue_media() on this page only
add_action( 'admin_post_eportfolio_create_post', 'eportfolio_handle_create_post' );
add_action( 'admin_bar_menu',        'eportfolio_redirect_toolbar_new_post', 999 );
```

---

## Reflection Page Fields (owned by reflection-submissions plugin)

All fields are stored as standard post meta on reflection pages (no ACF required).

| Meta key | Type | Purpose |
|---|---|---|
| `is_reflection_page` | true/false | Master toggle — enables reflection form on the page |
| `reflection_prompt_1` | text | Required prompt |
| `reflection_prompt_2` | text | Optional second prompt |
| `reflection_prompt_3` | text | Optional third prompt |
| `submission_privacy` | select | Post status for submissions: publish / private / pending |
| `allow_image_upload` | true/false | Enables image upload on the form |
| `allow_video_url` | true/false | Enables video URL field (oEmbed) |
| `allow_embed` | true/false | Enables iframe embed field (Kaltura etc.) |
| `allow_resubmission` | true/false | Disables duplicate guard when ON |
| `content_type_label` | text | Term name for content-type taxonomy (default: "Reflection") |

---

## Post Meta Keys

| Key | Set by | Meaning |
|---|---|---|
| `_reflection_source_page` | reflection-form.php | ID of the page the submission came from |
| `_reflection_response_1/2/3` | reflection-form.php | Raw text responses |
| `_reflection_video_url` | reflection-form.php | Video URL if provided |
| `_reflection_embed` | reflection-form.php | Sanitized iframe embed code |
| `_is_public_portfolio` | reflection-form.php, post-form.php, metabox | '1' = appears on /portfolio/ view; always '0' on new submission |

---

## Admin Panel (inc/admin-menu.php)

**Tabs (admin only)**: Privacy Settings | Menu Builder Guide | Advanced | Submissions

**Privacy tab — left column** (admin only):
- Global Site Privacy toggle (site-wide public/private)
- Portfolio Curation toggle (enable/disable `eportfolio_feature_portfolio`)
- Home Page Link (custom URL for "Back" links)

**Privacy tab — right column** (conditional):
- Portfolio feature ON: public/private toggle + portfolio URL + archive URL
- Portfolio feature OFF: archive URL only, link to Privacy Settings to enable

**Submissions tab**: lists all posts with `_reflection_source_page` meta; filter by status; Approve / Trash actions

**"+ New > Reflection Page"** in admin toolbar (admins only): creates a draft page with `[reflection_form]` shortcode baked in and default plugin meta values

**Non-admin (student) dashboard**: shows portfolio privacy toggle (feature ON) or archive URL only (feature OFF)

---

## Important Notes

### WP 6.7+ template fix
WordPress 6.7 changed front-end template resolution. Fix in `inc/template-filters.php`: hook is `get_block_template` (singular), not `get_block_templates`.

### Privacy + ?show= query string fix
`$_SERVER['REQUEST_URI']` includes the query string. `inc/privacy-logic.php` strips it with `strtok($uri, '?')` before regex matching, so `?show=POST_ID` requests are correctly allowed through for non-logged-in users on public portfolios.

### .single-post-render CSS class
Hardcoded directly into `templates/archive.html` and `templates/author.html` on the `<main>` Group block (`"className":"single-post-render"`). No longer requires a manual Site Editor step — fresh subsites get it automatically from the file. If a Site Editor–customized version exists in the DB, that takes precedence, so manually verify it has the class after any Site Editor edits.

---

## Current State / What Needs Testing

- **End-to-end reflection flow**: confirmed working — prompt page created, student submitted, post appears on `/author/` archive and in `<details>` nav Query Loop. ✓
- **`?show=POST_ID` non-logged-in**: confirmed working after privacy-logic.php query string fix. ✓
- **New Post form**: confirmed working — text, images (gallery), embeds, content-type terms, portfolio toggle, publish/draft. ✓
- **Term ID remapping**: `eportfolio_fix_content_type_term_ids()` rewrites stale content-type term IDs in Query Loop blocks at runtime — needs verification on a fresh subsite.
- **`/author/` vs `/portfolio/` headers**: test whether each can hold a different navigation menu (different UX intent — process archive vs curated portfolio).

### Known loose ends
- **`archive.html` is stale**: `author.html` (portfolio) has been updated with the working `<details>` + Query Loop sidebar. `archive.html` (author archive) still needs the same treatment — export from Site Editor and paste into `templates/archive.html`.
- **Edit in simple form**: posts created via New Post form can only be edited in the full block/classic editor. Future work.

---

## Open Questions / Future Work

- **Different nav menus per header**: `/author/` header (process archive) vs `/portfolio/` header (curated portfolio) — test in Site Editor whether `header-student.html` and `header-portfolio.html` can each hold a distinct navigation block.
- **Edit in simple form**: parse `wp:paragraph`, `wp:image`, `wp:gallery`, `wp:embed` blocks back into section objects on load. Doable with regex since markup is our own predictable format.
- **Multiple content types in archive**: each content type needs its own `<details>` + Query Loop in the archive template. Consider a PHP shortcode that auto-generates all sections dynamically.
- **Email notifications**: no email sent on pending reflection submission. Do instructors need one?
- **Multisite replication**: first production deploy pending — term ID remapping needs real-world verification on fresh subsites.
- **Frontend polish**: `<details>` accordion has no custom CSS. New Post form CSS is functional but basic.
- **ePortfoliohub coexistence**: `content-type` taxonomy registered in both themes with a guard. Long-term this theme should be fully self-contained.
- **Additional default content-type terms**: currently only "Reflection" is seeded on activation. If other terms (e.g. "Project", "Lab Report") become standard, add them to `eportfolio_seed_default_terms()` in `functions.php`.

---

## Plans

Implementation plans from Claude Code sessions are saved at:
`/Users/ngauer/plans/`

Current plans:
| File | Feature |
|---|---|
| `memoized-napping-lovelace.md` | Simplified New Post admin page (section-based builder) |

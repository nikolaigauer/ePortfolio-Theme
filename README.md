# ePortfolio Theme 2

A WordPress FSE (Full Site Editor) block theme for university ePortfolio courses. It gives each student a clean, templated portfolio presence: privacy controls, a process archive on the author side, an optional curated public portfolio, and a content-type filter menu — all configurable without touching raw code.

**Version**: 2.7.1
**Parent theme**: Twenty Twenty-Five
**Requires**: WordPress 6.8+, PHP 7.4+
**License**: GPL v2 or later — see [LICENSE](LICENSE)

---

## Features

- **Dual view per student**: `/author/username/` (full archive) + `/portfolio/username/` (curated portfolio)
- **Selectable layout per view**: each archive can be a **Feed** (scrolling) or **Single post at a time** — admin-set on the Advanced tab. Defaults: author = Feed, portfolio = Single.
- **`?show=POST_ID` navigation**: in Single-post mode, fetch-based post browsing that keeps sidebar accordions open without full page reloads
- **Content-type filter menu**: a generated menu (with an **All** link) that scopes filtering to the student being viewed and only shows types that student has actually posted in — pairs with Feed mode for a filterable process archive
- **Privacy controls**: site-wide toggle, per-student portfolio toggle, per-post visibility
- **Portfolio curation**: feature-flagged — students opt posts in manually; nothing auto-publishes to their portfolio
- **Content-type taxonomy**: hierarchical, block-editor-compatible; terms are created manually to suit each course's context
- **Student dashboard**: clean admin interface for privacy settings, portfolio URL, and archive URL
- **No ACF dependency**: the theme's own settings (privacy, portfolio inclusion) use standard WordPress options and post meta

---

## Optional companion plugin

The theme is **fully standalone** — privacy, the process archive, the optional portfolio, the clean ePortfolio templates, and content-type filtering all work on their own, and it provides no frontend form fields of its own.

To let students *log* activities and reflections through frontend forms, pair it with **Activity Builder**, which inserts prompt input forms and builds assignments in an easy-to-use activity builder:

**[Activity Builder](https://github.com/nikolaigauer/activity-builder)**

---

## Installation

1. Install and activate **Twenty Twenty-Five** as the parent theme.
2. Upload this theme folder to `wp-content/themes/` and activate it.
3. Go to **ePortfolio → Privacy Settings** to configure site privacy and enable/disable portfolio curation.

---

## Multisite deployment

This theme is built to coexist on a multisite network alongside the older **ePortfolio Theme** without disturbing it. **Activate it per-site** (e.g. only on a demo site) rather than network-activating, and the sites running the old theme are unaffected — WordPress loads only the *active* site's theme on any given request. The two themes share many function, constant, and option names; that is harmless precisely because no single request ever loads both.

**The one flag to remember: user meta is network-global.** WordPress data falls into three scopes, and only one of them crosses site boundaries:

| Data | Stored in | Multisite scope |
|------|-----------|-----------------|
| Site options (`eportfolio_site_is_public`, `eportfolio_author_slug`, rewrite rules) | `wp_N_options` | **Per-site** — isolated |
| Post meta (`_is_public_portfolio` — the "include in portfolio" checkbox) | `wp_N_postmeta` | **Per-site** — isolated |
| **User meta (`portfolio_is_public` — the student's portfolio public/private toggle)** | `wp_usermeta` | **Network-global — shared by every site** |

Because `portfolio_is_public` lives in the global `wp_usermeta` table, its value applies to a user account on *every* site in the network. `inc/privacy-logic.php` enforces it (it gates `/portfolio/username/` and that author's posts), so if the **same** student account exists on both a demo site and a production site, toggling their portfolio privacy on one flips it on the other — potentially exposing or hiding their real work.

**Mitigation: use demo/test accounts on any demo site, never real student accounts that already exist on production sites.** Everything else the theme writes (site privacy, author slug, per-post portfolio checkbox, `/portfolio/` rewrites) is per-site and cannot bleed across sites.

> **Install/update in place under the folder name `eportfolio-theme-2`.** As of v2.7.1 the template files no longer hard-code the theme slug, so headers/footers render even if the folder is named differently — but the folder name *is* the theme's slug, and Site-Editor customizations are keyed to it. Installing the GitHub **release zip** creates a version-named folder (e.g. `ePortfolio-Theme-2.7.0`), which activates as a *separate* theme and orphans any customizations. Rename such an upload back to `eportfolio-theme-2` (or update in place) before activating.

---

## URL Structure

| URL | Purpose | Default layout |
|-----|---------|----------------|
| `/author/username/` | Full archive — all published posts | Feed (scrolling) |
| `/portfolio/username/` | Curated portfolio — only posts the student has opted in | Single post at a time |

Either view's layout is configurable (Advanced → Display / Layout). In Single-post mode, `?show=POST_ID` drives in-page post navigation. Append `?content-type=slug` to a `/author/` URL to filter by content type (or use the generated Content Types menu, which does this automatically per student).

---

## Admin Panel (ePortfolio menu)

**Privacy Settings tab** (admins):
- Global site privacy toggle
- Portfolio Curation feature toggle
- Home page link for navigation

**Menu Builder Guide tab** (admins):
- Content-type guidance + the **Student Authors** and **Content Types** menu generators

**Advanced tab** (admins):
- Customize the author archive URL slug
- **Display / Layout** — Feed vs Single post per view

**Student dashboard** (non-admins):
- Portfolio public/private toggle (when feature is on)
- Archive and portfolio URLs

---

## Development

### Theme structure

```
eportfolio-theme-2/
├── functions.php              # Module loader + core hooks
├── style.css                  # Theme header
├── theme.json                 # Global styles
├── inc/
│   ├── admin-menu.php         # Student dashboard + admin tabs
│   ├── privacy-logic.php      # Public/private enforcement
│   ├── portfolio-link.php     # Portfolio link system
│   ├── content-type-filter.php# "All" filter link + active-state highlight
│   ├── post-metabox.php       # Portfolio checkbox on posts
│   ├── rewrite-rules.php      # /portfolio/ URL structure
│   ├── shortcodes.php         # [archive_navigation] etc.
│   └── template-filters.php   # Template routing (/portfolio/ → portfolio.html) + body classes
├── templates/
│   ├── author.html            # /author/username/ view — feed (process archive)
│   ├── portfolio.html         # /portfolio/username/ view — curated single-post
│   └── ...
└── parts/
    ├── header-author.html     # header for /author/ (feed)
    ├── header-portfolio.html  # header for /portfolio/ (curated)
    └── ...
```

### Key design decisions

- **`?show=POST_ID`**: JS fetch interceptor in `wp_footer` swaps `.single-post-render` on click — keeps `<details>` accordions open across navigation.
- **content-type taxonomy**: registered at `init` priority 0 with `taxonomy_exists()` guard so it coexists safely with other themes/plugins.
- **Portfolio curation flag**: `eportfolio_feature_portfolio` option (`'0'`/`'1'`). When off, the `/portfolio/` URL and all curation UI are hidden.
- **Term ID remapping**: `eportfolio_fix_content_type_term_ids()` rewrites stale term IDs stored in Query Loop blocks to live site IDs at runtime — important for multisite.

---

## License

ePortfolio Theme 2, Copyright (C) 2026 Nikolai Gauer.
Licensed under the GNU General Public License v2 or later.
See [LICENSE](LICENSE) or https://www.gnu.org/licenses/gpl-2.0.html

This theme is a child theme of [Twenty Twenty-Five](https://wordpress.org/themes/twentytwentyfive/), (C) the WordPress team, GPLv2 or later.

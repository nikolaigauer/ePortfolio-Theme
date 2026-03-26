# ePortfolio Theme 2

A WordPress FSE (Full Site Editor) block theme for university ePortfolio courses. Students submit weekly reflections via frontend forms, maintain a personal archive, and optionally curate a public-facing portfolio — all without touching the WordPress admin.

**Version**: 2.6.0
**Parent theme**: Twenty Twenty-Five
**Requires**: WordPress 6.8+, PHP 7.4+
**License**: GPL v2 or later — see [LICENSE](LICENSE)

---

## Features

- **Dual view per student**: `/author/username/` (full archive) + `/portfolio/username/` (curated portfolio)
- **`?show=POST_ID` navigation**: fetch-based post browsing that keeps sidebar accordions open without full page reloads
- **Privacy controls**: site-wide toggle, per-student portfolio toggle, per-post visibility
- **Portfolio curation**: feature-flagged — students opt posts in manually; nothing auto-publishes to their portfolio
- **Content-type taxonomy**: hierarchical, block-editor-compatible; "Reflection" term seeded on activation
- **Student dashboard**: clean admin interface for privacy settings, portfolio URL, and archive URL
- **No ACF dependency**: all fields use standard WordPress post meta

---

## Optional Plugin

Works standalone for portfolio/privacy/display. Reflection form submissions and the student New Post form require the companion plugin:

**[reflection-submissions](https://github.com/nikolaigauer/reflection-submissions)**

---

## Installation

1. Install and activate **Twenty Twenty-Five** as the parent theme.
2. Upload this theme folder to `wp-content/themes/` and activate it.
3. Go to **ePortfolio → Privacy Settings** to configure site privacy and enable/disable portfolio curation.

---

## URL Structure

| URL | Purpose |
|-----|---------|
| `/author/username/` | Full archive — all published posts, one at a time |
| `/portfolio/username/` | Curated portfolio — only posts the student has opted in |

Both views use `?show=POST_ID` for in-page post navigation.

---

## Admin Panel (ePortfolio menu)

**Privacy Settings tab** (admins):
- Global site privacy toggle
- Portfolio Curation feature toggle
- Home page link for navigation

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
│   ├── post-metabox.php       # Portfolio checkbox on posts
│   ├── rewrite-rules.php      # /portfolio/ URL structure
│   ├── shortcodes.php         # [archive_navigation] etc.
│   └── template-filters.php   # Template routing (WP 6.7+ fix)
├── templates/
│   ├── archive.html           # /author/username/ view
│   ├── author.html            # /portfolio/username/ view
│   └── ...
└── parts/
    ├── header-student.html
    ├── header-portfolio.html
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

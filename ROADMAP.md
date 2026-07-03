# ePortfolio Theme 2 — Roadmap / Deferred Notes

Small, intentional follow-ups captured so they aren't lost. None are blocking.

## Header subtitles (view labels)
Add a sub-title under the author's name in each header part, to reinforce the
two views now that naming is intuitive (v2.7.0):

- `parts/header-author.html` (the `/author/` feed) → subtitle **"PROCESS ARCHIVE"**
- `parts/header-portfolio.html` (the `/portfolio/` curated view) → subtitle **"PORTFOLIO"**

## Dashboard menu cleanup
- Remove **all emoji / icons** from the dashboard menu panels (`inc/admin-menu.php`).
- Review and tighten the **wordiness** of the explanatory copy on those panels —
  several descriptions are longer than they need to be.

## Portfolio header menu — needs discussion
The Content Types menu builder (Menu Builder Guide tab) generates one menu that
is filtered by URL context (`inc/content-type-filter.php`) and works fine on both
`/author/` and `/portfolio/`. Open question for later: do we want a *separate*
menu builder dedicated to the `/portfolio/` header, or is the current single
URL-filtered menu sufficient? Current solution works; revisit only if a concrete
need appears.

---

## Done in v2.7.0 (for context)
- Renamed templates/parts to match their URLs: `author.html` → `/author/`,
  `portfolio.html` → `/portfolio/`; `header-student.html` → `header-author.html`.
- Fixed the `/author/` vs `/portfolio/` template separation (regressed 2026-03-20
  when the swap moved from the plural `get_block_templates` filter to the singular
  `get_block_template` filter, which WP front-end resolution never calls). Now uses
  the native `author_template_hierarchy` filter to route `/portfolio/` to
  `portfolio.html`. See `inc/template-filters.php`.
- Security: REST API privacy gate on private sites; content-type taxonomy
  manage/edit/delete caps narrowed to `manage_options`. See `inc/privacy-logic.php`.

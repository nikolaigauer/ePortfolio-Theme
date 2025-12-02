# ePortfolio Theme - Template Structure Reference

**Last Updated:** v2.3.0

---

## Template Routing ("Domino Effect")

The theme uses a PHP-based template routing system that controls which template WordPress loads:

| URL Pattern | Template Loaded | Query Filter | Header Part |
|-------------|-----------------|--------------|-------------|
| `/portfolio/username` | `author.html` | Portfolio posts only (`_is_public_portfolio=1`) | `header-portfolio.html` |
| `/author/username` | `archive.html` | All posts | `header-student.html` |
| `/` (home) | `home.html` | All posts | `header-landing.html` |
| Single post | `single.html` | N/A | `header-single.html` |

### How It Works

1. **Portfolio URLs** (`/portfolio/username`):
   - Rewrite rule sets `author_name` + `portfolio_view=1`
   - WordPress loads `author.html` (standard author template)
   - PHP filter adds meta_query to show only portfolio-marked posts

2. **Author URLs** (`/author/username`):
   - WordPress identifies as author archive
   - `get_block_templates` filter swaps content with `archive.html`
   - All posts shown (no portfolio filtering)

### To Customize Templates

- **Portfolio pages** → Edit `author.html` in Site Editor
- **Author archives** → Edit `archive.html` in Site Editor
- **Landing page** → Edit `home.html` in Site Editor

---

## Template Parts (Headers)

| Header Part | Used By | Purpose |
|-------------|---------|---------|
| `header-landing.html` | `home.html` | Cohort landing with hero image |
| `header-student.html` | `archive.html` | Author archive header |
| `header-portfolio.html` | `author.html` | Portfolio view header |
| `header-single.html` | `single.html` | Individual post header |
| `header-page.html` | `page.html` | Standard page header |
| `footer.html` | All templates | Universal footer |

---

## Template Files

### `home.html` - Cohort Landing Page
- **Shows:** All posts from all students (cohort-wide feed)
- **Navigation:** Empty block for "Student Authors" menu

### `author.html` - Portfolio View (NOT regular author archive!)
- **URL:** `/portfolio/username`
- **Shows:** Only posts marked "Include in portfolio"
- **Navigation:** Empty block for content filtering

### `archive.html` - Author Archive
- **URL:** `/author/username`
- **Shows:** ALL posts by one student
- **Navigation:** Empty block for content filtering
- **Note:** Also used as fallback for category/tag archives

### `single.html` - Individual Post
- **Shows:** Full post with comments
- **Navigation:** Cohort link in header

### `page.html` - Standard Page
- **Shows:** Page content
- **Navigation:** Standard site navigation

---

## Query Filtering

Portfolio posts are filtered using the `_is_public_portfolio` post meta:

```php
// In functions.php - filters Query Loop block
add_filter('query_loop_block_query_vars', 'eportfolio_portfolio_query_author', 10, 2);

// In template-filters.php - filters main query
add_action('pre_get_posts', 'eportfolio_filter_portfolio_query');
```

Both filters check for `portfolio_view` query var and add:
```php
$query['meta_query'] = array(
    array(
        'key' => '_is_public_portfolio',
        'value' => '1',
        'compare' => '='
    )
);
```

---

## Body Classes

The theme adds body classes for template-specific styling:

- `portfolio-view` - On `/portfolio/username` pages
- `portfolio-archive` - On `/portfolio/username` pages
- `author` - On all author-related pages
- `author-{username}` - Specific author identifier

# Content Type Filter - Two Versions

## Current Status (for ETUG Demo)

**ACTIVE:** `content-type-filter.php` - CLIENT-SIDE filtering version
**BACKUP:** `BACKUP-content-type-filter-ROBUST-SERVER-SIDE.php` - Production-ready version

---

## Client-Side Version (Currently Active - For Demo)

### What It Does:
- Filters posts **already loaded on the page** using JavaScript show/hide
- No page reloads, instant filtering
- Works great for demos with limited posts (10-20 posts per page)
- Simple, fast, visual

### Limitations:
- ❌ Only filters posts currently visible in the feed
- ❌ Pagination doesn't work properly (shows all posts on paginated pages)
- ❌ No actual WP_Query modification
- ❌ Not suitable for large datasets

### Best For:
- ✅ Quick demos (like ETUG presentation)
- ✅ Sites with few posts per page
- ✅ Visual demonstrations

---

## Server-Side Version (Backed Up - For Production)

### What It Does:
- Performs **real WP_Query filtering** based on URL parameters
- Proper pagination support
- Efficient database queries
- Handles thousands of posts without performance issues

### How It Works:
1. Uses `?content_type=slug` URL parameters
2. Modifies the WordPress query with `pre_get_posts` hook
3. Maintains author archive context properly
4. Full pagination support on filtered results

### Technical Details:
```php
// Example: Filtering for "research" content type
// URL: /author/student-name/?content_type=research

add_action('pre_get_posts', 'eportfolio_filter_by_content_type');
function eportfolio_filter_by_content_type($query) {
    // Modifies main query with tax_query
    $tax_query[] = array(
        'taxonomy' => 'content_type',
        'field'    => 'slug',
        'terms'    => $content_type,
    );
    $query->set('tax_query', $tax_query);
}
```

### Best For:
- ✅ Production sites
- ✅ Large portfolios (50+ posts)
- ✅ When pagination is needed
- ✅ Professional implementations

---

## How to Switch Between Versions

### To Use Client-Side (Demo) Version:
Currently active! No changes needed.

### To Switch Back to Server-Side (Production) Version:

1. **Rename the backup file:**
   ```bash
   cd /Applications/MAMP/htdocs/mysite/wp-content/themes/eportfolio-theme/inc/
   mv content-type-filter.php content-type-filter-CLIENT-DEMO.php
   mv BACKUP-content-type-filter-ROBUST-SERVER-SIDE.php content-type-filter.php
   ```

2. **Clear WordPress permalinks:**
   - Go to Settings → Permalinks in WordPress admin
   - Click "Save Changes" (no need to change anything)

3. **Test the filtering:**
   - Visit an author archive
   - Click a content type filter
   - URL should change to include `?content_type=slug`
   - Page should reload with filtered results

---

## Key Differences at a Glance

| Feature | Client-Side (Demo) | Server-Side (Production) |
|---------|-------------------|-------------------------|
| **Speed** | Instant (no reload) | Fast (page reload) |
| **Query Type** | CSS show/hide | Real WP_Query filtering |
| **Pagination** | ❌ Broken | ✅ Works perfectly |
| **Database Load** | Low (all posts loaded) | Efficient (filtered query) |
| **URL Changes** | No | Yes (`?content_type=slug`) |
| **Large Datasets** | ❌ Not suitable | ✅ Handles thousands |
| **Best For** | Demos, <20 posts | Production, any size |

---

## Files Structure

```
/inc/
├── content-type-filter.php                          ← ACTIVE (client-side demo)
├── BACKUP-content-type-filter-ROBUST-SERVER-SIDE.php ← Production backup
└── CONTENT-TYPE-FILTER-README.md                    ← This file
```

---

## Notes for ETUG Presentation

The client-side version is perfect for your demo because:
1. ✅ Instant filtering looks impressive
2. ✅ No URL changes to confuse audience
3. ✅ Simple to explain ("JavaScript just hides posts you don't want to see")
4. ✅ Works great with your current 10-15 posts per author

After the presentation, switch back to server-side version for production use!

---

## Created: October 31, 2025
## Last Updated: October 31, 2025 (Pre-ETUG Demo)

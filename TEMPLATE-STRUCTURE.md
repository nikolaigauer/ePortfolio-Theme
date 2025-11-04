# ePortfolio Theme - Template Structure Reference

**Last Updated:** October 24, 2025

---

## Template Parts (Header) → Template Mapping

| Header Part | Used By Template | Purpose | Navigation Content |
|-------------|------------------|---------|-------------------|
| `header-landing.html` | `home.html` | Cohort landing page with hero image | Empty (for Student Authors menu) |
| `header-student.html` | `author.html` | Student author archive | Empty (for Content Type Filter menu) |
| `header-portfolio.html` | `portfolio.html` | Student portfolio showcase | Empty (can use same OR different menu) |
| `header-single.html` | `single.html` | Individual post view | N/A (just shows "Viewing Single Post:" + cohort link) |
| `footer.html` | All templates | Universal footer | N/A |

---

## Template Purpose Guide

### `home.html` - Cohort Landing Page
**Header:** `header-landing`  
**Layout:** Hero image + sidebar with class info + main content area  
**Shows:** All posts from all students (cohort-wide feed)  
**Navigation:** Empty block for "Student Authors" menu (dropdown with A-M and N-Z groups)

### `author.html` - Student Author Archive  
**Header:** `header-student`  
**Layout:** Student name header + sidebar with cohort link + main content area  
**Shows:** ALL posts by one student (comprehensive archive)  
**Navigation:** Empty block for "Content Type Filter" menu  
**Note:** This is the "process" view - everything the student has created

### `portfolio.html` - Student Portfolio Showcase
**Header:** `header-portfolio`  
**Layout:** Student name header + sidebar (with "Life's a highway" marker) + main content area  
**Shows:** Only posts marked "Show in public portfolio" by one student (curated)  
**Navigation:** Empty block - can use same "Content Type Filter" OR create portfolio-specific menu  
**Note:** This is the "presentation" view - student's best/selected work  
**Template Override:** WordPress forced to use this template via `template_include` filter when `portfolio_view=1` query var present

### `single.html` - Individual Post View
**Header:** `header-single`  
**Shows:** Full single post with comments, tags, navigation to prev/next  
**Navigation:** Just cohort link in header

---

## Header Part Details

### `header-landing.html`
```
Pattern: patterns/header-landing.php
Contains: Hero image cover + site title + empty navigation block
Comments: Yes - explains Student Authors menu workflow
```

### `header-student.html`
```
Pattern: None (direct HTML)
Contains: Query title (student name) + empty navigation block
Comments: Yes - explains Content Type Filter menu workflow
Font: Student name is 4.5em, 600 weight, uppercase
```

### `header-single.html`
```
Pattern: None (direct HTML)
Contains: "Viewing Single Post:" text + cohort link shortcode
No navigation block needed
```

---

## Current State Notes

**✅ Complete:**
- All headers properly named and documented
- Navigation blocks empty and explained
- Template → header mapping is clear

**🟡 Pending Decision:**
- Should `portfolio.html` use a different header than `author.html`?
- Should portfolio navigation be different from author navigation?
- How to implement the distinction technically?

**💡 Future Consideration:**
- `header-portfolio.html` - Separate header for portfolio with different navigation approach
- Conditional logic in `header-student.html` to show different nav based on context
- Or keep them the same for MVP simplicity

---

## Patterns vs Template Parts

**Pattern (PHP file):**
- `patterns/header-landing.php` - Dynamic pattern that can use PHP
- References site assets with `get_template_directory_uri()`
- More flexible, can include logic

**Template Part (HTML file):**
- `parts/header-landing.html` - Just references the pattern
- `parts/header-student.html` - Static HTML blocks
- `parts/header-single.html` - Static HTML blocks
- Simpler, no PHP needed

---

## Quick Reference: What Shows Where

**Landing Page (`home.html`):**
- All students' posts
- Latest posts sidebar
- Latest comments sidebar
- Class info placeholder

**Author Archive (`/author/username`):**
- One student's ALL posts
- Process-oriented
- Can filter by content type
- Link to portfolio →

**Portfolio (`/portfolio/username`):**
- One student's CURATED posts only
- Presentation-oriented
- Can filter by content type
- "Life's a highway" sidebar marker
- Link to author archive? (not yet implemented)

**Single Post (`/year/month/day/post-slug`):**
- Full post content
- Comments
- Tags
- Prev/Next navigation
- Author info

# Privacy Hierarchy - ePortfolio Theme

## Overview
The theme implements a **hierarchical privacy system** where portfolio privacy acts as a master control over individual posts.

## The Hierarchy

### Level 1: Site Privacy (Admin Control)
- Controlled by: Site administrators
- Setting location: Admin dashboard
- Effect: When site is private, only logged-in users can access content (unless exceptions apply)

### Level 2: Portfolio Privacy (Student Control - MASTER SWITCH)
- Controlled by: Individual students
- Setting location: User profile page
- Options: Public or Private
- **This is the master control for all student content**

### Level 3: Individual Post Privacy (Student Control - SECONDARY)
- Controlled by: Individual students
- Setting location: Post editor sidebar (metabox)
- Option: "Include in portfolio" checkbox
- **Only works when portfolio is PUBLIC**

## How It Works

### When Portfolio is PRIVATE:
- ❌ **ALL posts are private** (regardless of individual post settings)
- ❌ The "Include in portfolio" checkbox is **disabled** in the post editor
- 🔒 No one can access posts via direct links (except logged-in users)
- 🔒 Posts do NOT appear in `/portfolio/username`
- ⚠️ Students see a warning: "Your portfolio is set to Private"

### When Portfolio is PUBLIC:
- ✅ Portfolio page `/portfolio/username` is accessible
- ✅ Students can control individual posts via checkbox
  - **Checked**: Post appears in portfolio AND is publicly accessible
  - **Unchecked**: Post does NOT appear in portfolio and is private
- 🌐 Only checked posts can be shared via direct links

## Use Cases

### Use Case 1: Share specific work publicly
**Scenario**: Student wants to share one specific project publicly, but keep portfolio private.

**Solution**: 
1. Set portfolio to PUBLIC (in profile)
2. Check "Include in portfolio" ONLY on posts you want to share
3. All unchecked posts remain private

### Use Case 2: Keep everything private
**Scenario**: Student wants to keep all work private while working on it.

**Solution**:
1. Set portfolio to PRIVATE (in profile)
2. Individual post checkboxes are disabled
3. Everything stays private automatically

### Use Case 3: Full public portfolio
**Scenario**: Student wants to showcase all their work publicly.

**Solution**:
1. Set portfolio to PUBLIC (in profile)
2. Check "Include in portfolio" on all posts you want visible
3. Portfolio and checked posts are publicly accessible

## Benefits of This Approach

1. **Prevents Accidents**: Students can't accidentally expose private work by checking a box
2. **Clear Control**: Portfolio privacy is the obvious master switch
3. **Intuitive**: Matches how students think about privacy ("my portfolio is private = everything is private")
4. **Flexible**: When public, students still have granular control over individual posts

## Technical Implementation

### Privacy Logic Location
`/inc/privacy-logic.php` - Lines 41-66 and 90-117

### Metabox UI Location  
`/inc/post-metabox.php` - Lines 44-115

### Key Meta Fields
- `portfolio_is_public` (user meta): '1' = public, '0' = private
- `_is_public_portfolio` (post meta): '1' = include in portfolio, '0' = regular post

## For Developers

To check if a post should be accessible:

```php
// Get portfolio privacy (master control)
$portfolio_is_public = get_user_meta($author_id, 'portfolio_is_public', true);

if ($portfolio_is_public === '0') {
    // Portfolio is private - ALL posts are blocked
    return false;
}

// Portfolio is public - check individual post
$post_is_public = get_post_meta($post_id, '_is_public_portfolio', true);
return ($post_is_public === '1');
```

## Version History
- v2.2.0: Implemented hierarchical privacy system

# ePortfolio Theme v2.3.0

A lean WordPress theme focused on student portfolio management with granular privacy controls and dual archive system. Designed to work seamlessly with Advanced Custom Fields (ACF) for content organization.

**Latest Update**: v2.3.0 - Major template system breakthrough, full block editor control for portfolio pages

## ✨ Features

### **Core Features**
- **🔒 Privacy Controls**: Granular public/private toggles for posts, pages, and portfolios
- **🎯 Portfolio System**: Dual archive system - `/author/username` (all posts) + `/portfolio/username` (curated)
- **👥 Student Dashboard**: Clean admin interface for privacy settings and navigation
- **🔗 Smart Portfolio Links**: Automatic portfolio link generation via CSS classes

## Installation

### Manual Installation
1. Download the latest release from [GitHub Releases](https://github.com/nikolaigauer/ePortfolio-Theme/releases)
2. Upload the zip file to WordPress Admin > Appearance > Themes > Add New > Upload Theme
3. Activate the theme

### Updates
This theme uses manual updates. Download new versions from the GitHub releases page as needed.

## 🔌 Recommended Plugins

### **Advanced Custom Fields (ACF)**
This theme is designed to work with ACF for content organization:
- **Content Types**: Create custom taxonomies for categorizing student work
- **Custom Fields**: Add structured data to posts and portfolios
- **Block Editor Integration**: ACF taxonomies appear in Navigation block menus

Install ACF and create a "Content Types" taxonomy with terms like:
- Essay, Project, Reflection, Studio Work, Research

See the theme's **Menu Builder Guide** tab for detailed setup instructions.

## Development

### Theme Structure
```
eportfolio-theme/
├── functions.php              # Main theme functions
├── style.css                  # Theme header and basic styles
├── inc/                       # Functionality modules
│   ├── admin-menu.php         # Student dashboard menu
│   ├── privacy-logic.php      # Granular privacy controls
│   ├── portfolio-link.php     # Smart portfolio link system
│   ├── template-filters.php   # Template routing & query filters
│   ├── shortcodes.php         # Dynamic shortcodes
│   ├── post-metabox.php       # Portfolio post controls
│   └── rewrite-rules.php      # URL structure
├── templates/                 # Block theme templates
│   ├── home.html              # Cohort landing page
│   ├── author.html            # Portfolio view (/portfolio/username)
│   ├── archive.html           # Author archive (/author/username)
│   ├── single.html            # Individual post view
│   └── page.html              # Standard page template
├── parts/                     # Template parts
│   ├── header-*.html          # Various header templates
│   └── footer.html            # Site footer
└── patterns/                  # Block patterns
```

### Template Routing (v2.3.0 "Domino Effect")

The theme uses a smart template routing system:

| URL Pattern | Template Used | Query Filter | Purpose |
|-------------|---------------|--------------|---------|
| `/portfolio/username` | `author.html` | Portfolio posts only | Curated public portfolio |
| `/author/username` | `archive.html` | All posts | Complete author archive |

This means:
- Edit **`author.html`** to customize `/portfolio/username` appearance
- Edit **`archive.html`** to customize `/author/username` appearance

Both templates are fully editable in the WordPress Site Editor.

## 🎛️ Admin Interface

### **Privacy Settings Tab**
- **Global Site Privacy**: Make entire site public or private
- **Portfolio System**: Enable/disable portfolio URLs and functionality
- **Default Privacy**: Set site-wide defaults for new student portfolios
- **Cohort URL**: Custom home page link for portfolio navigation

### **Menu Management**
- **Content Type Filters**: Dynamic filtering menus for portfolio pages
- **Student Navigation**: Auto-generate student directory menus

### **Student Menu Tab**
- **Student Navigation**: Auto-generate alphabetical student directory (A-M / N-Z)

### **Advanced Tab** 
- **Author URL Customization**: Change `/author/` to `/student/`, `/work/`, etc.

## 🔧 Setup Guide

### **1. Basic Setup**
1. Install and activate the theme
2. Go to **ePortfolio → Privacy Settings**
3. Configure global privacy and portfolio system
4. Generate navigation menus in **Menu Generators** tab

### **2. Content Types**
The theme includes content type taxonomy:
- Custom content type filtering
- Dynamic menu generation
- Portfolio organization

### **3. Template Editing**
- **Portfolio pages** (`/portfolio/username`): Edit `author.html` in Site Editor
- **Author archives** (`/author/username`): Edit `archive.html` in Site Editor
- **Individual posts**: Edit `single.html` in Site Editor

### **4. Menu Usage**
1. Generate menus using admin interface
2. Add **Navigation** block to your templates  
3. Select the generated menu
4. For category filtering, add CSS class: `category-filter-menu`

## 🔄 Development & Updates

### **GitHub Repository**
- **Main Repository**: [https://github.com/nikolaigauer/ePortfolio-Theme](https://github.com/nikolaigauer/ePortfolio-Theme)
- **Issues & Feature Requests**: [https://github.com/nikolaigauer/ePortfolio-Theme/issues](https://github.com/nikolaigauer/ePortfolio-Theme/issues)
- **Latest Releases**: [https://github.com/nikolaigauer/ePortfolio-Theme/releases](https://github.com/nikolaigauer/ePortfolio-Theme/releases)

### **Making Changes**
1. Fork or clone the repository
2. Make your changes  
3. Update version in `style.css` and `functions.php`
4. Create a git tag: `git tag v2.3.1`
5. Push the tag: `git push origin v2.3.1` 
6. GitHub Actions will automatically create a release

## Requirements

- WordPress 6.8+
- PHP 7.4+
- Parent theme: Twenty Twenty-Five

## License

GPL v2 or later

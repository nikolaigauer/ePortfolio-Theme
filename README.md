# ePortfolio Theme v2.0.1

A WordPress theme for student portfolio management with granular privacy controls, dynamic content type filtering, and flexible page templates. Perfect for academic institutions, student cohorts, and creative portfolios.

**Latest Update**: v2.0.1 - Removed auto-updater, fixed theme URIs, manual updates only

## ✨ Features

### **Current Features**

### **Core Features**
- **🔒 Privacy Controls**: Public/private toggle for individual posts and pages
- **🏷️ Content Type Filtering**: Dynamic taxonomy-based filtering with JavaScript
- **🎯 Portfolio System**: Optional separate portfolio archive at `/portfolio/username`
- **👥 Student Dashboard**: Custom admin menu for portfolio management  
- **🔄 Auto-Updates**: GitHub-based theme updates with notifications

## Installation

### Manual Installation
1. Download the latest release from [GitHub Releases](https://github.com/nikolaigauer/ePortfolio-Theme/releases)
2. Upload the zip file to WordPress Admin > Appearance > Themes > Add New > Upload Theme
3. Activate the theme

### Auto-Updates
Once installed, the theme will automatically check for updates from GitHub and notify you in the WordPress admin when new versions are available.

## Development

### Theme Structure
```
eportfolio-theme/
├── functions.php              # Main theme functions
├── style.css                 # Theme header and basic styles
├── inc/                      # Functionality modules
│   ├── admin-menu.php        # Student dashboard menu
│   ├── theme-updater.php     # GitHub update checker
│   ├── privacy-logic.php     # Granular privacy controls
│   ├── content-type-*.php    # Content type functionality (ACF compatible)
│   ├── template-filters.php  # Conditional portfolio logic
│   └── ...
├── templates/                # Block theme templates
│   ├── page.html            # Updated page template
│   ├── page.html            # Standard page template
│   ├── portfolio.html       # Portfolio archive template
│   └── ...
├── parts/                    # Template parts
│   ├── header-*.html        # Various header templates
│   ├── header-portfolio.html# Portfolio header
│   └── ...
└── patterns/                 # Block patterns
```

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

### **3. Templates**
- **`page.html`**: Standard page layout
- **`portfolio.html`**: Portfolio archive template
- **`single.html`**: Individual post template

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
4. Create a git tag: `git tag v2.0.2`
5. Push the tag: `git push origin v2.0.2` 
6. GitHub Actions will automatically create a release

## Requirements

- WordPress 6.8+
- PHP 7.4+
- Parent theme: Twenty Twenty-Five

## License

GPL v2 or later
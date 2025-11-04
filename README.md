# ePortfolio Theme

A WordPress theme for student portfolio management with granular privacy controls, dynamic content type filtering, and dual archive/portfolio views.

## Features

- **Privacy Controls**: Public/private toggle for posts
- **Content Type Filtering**: Dynamic taxonomy-based filtering
- **Portfolio View**: Separate portfolio archive at `/portfolio/`
- **Student Dashboard**: Custom admin menu for portfolio management
- **Auto-Updates**: GitHub-based theme updates

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
├── functions.php           # Main theme functions
├── style.css              # Theme header and basic styles
├── inc/                   # Functionality modules
│   ├── theme-updater.php  # GitHub update checker
│   ├── privacy-logic.php  # Public/private post logic
│   ├── content-type-*.php # Content type functionality
│   └── ...
├── templates/             # Block theme templates
├── parts/                 # Template parts
└── patterns/              # Block patterns
```

### Making Changes
1. Clone this repository
2. Make your changes
3. Update version in `style.css` and `functions.php`
4. Create a git tag: `git tag v1.0.1`
5. Push the tag: `git push origin v1.0.1`
6. GitHub Actions will automatically create a release

## Requirements

- WordPress 6.8+
- PHP 7.4+
- Parent theme: Twenty Twenty-Five

## License

GPL v2 or later
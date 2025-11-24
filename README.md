# Admin Style Customizer

A WordPress plugin to customize the Admin Bar and Login Page styles through the WordPress Customizer.

## Features

- **Visual Customizer Interface** - Edit all style variables directly in the WordPress Customizer
- **Admin Bar Styling** - Complete control over colors, dimensions, fonts, and more
- **Login Page Styling** - Customize the WordPress login page with your branding
- **Live Login Preview** - Mock preview of login page changes within the Customizer
- **Apply Button** - Changes only preview when you click "Apply Changes" for smoother editing
- **Import/Export** - Save and restore your configurations as JSON files
- **Reset to Defaults** - Double-confirmation reset to restore original settings
- **Custom CSS** - Add additional CSS for admin bar and login page separately
- **No-Cache Headers** - Automatic cache prevention during Customizer preview

## Installation

### File Structure

Create this folder structure in your `/wp-content/plugins/` directory:

```
admin-style-customizer/
├── admin-style-customizer.php   (Main plugin file)
├── customizer.js                (Customizer JavaScript)
├── customizer.css               (Customizer panel styles)
├── css/
│   ├── adminbar.css             (Admin bar base styles)
│   └── login.css                (Login page base styles)
└── README.md                    (This file)
```

### Steps

1. Create the `admin-style-customizer` folder in `/wp-content/plugins/`
2. Copy each file from the artifacts into the appropriate location
3. Activate the plugin in WordPress Admin → Plugins
4. **Remove the old enqueue functions** from your child theme's `functions.php`:

```php
// REMOVE THESE LINES from functions.php:
function enqueue_admin_custom_css(){ ... }
add_action( 'admin_enqueue_scripts', 'enqueue_admin_custom_css', 1);
function enqueue_enfold_custom_css(){ ... }
add_action( 'wp_enqueue_scripts', 'enqueue_enfold_custom_css', 9999);
function custom_login_stylesheet(){ ... }
add_action('login_enqueue_scripts', 'custom_login_stylesheet', 1);
```

## Usage

### Accessing the Customizer

1. Go to **Appearance → Customize**
2. Find the **"Admin Style Customizer"** panel
3. Expand any section to edit settings

### Sections Available

| Section | Description |
|---------|-------------|
| **Admin Bar Colors** | Background, text, accent, border colors |
| **Admin Bar Dimensions** | Height, padding, avatar size, font size |
| **Login Page** | Main colors, logo, border radius |
| **Typography** | Title and main font families |
| **Custom CSS** | Additional CSS for admin bar and login |
| **Import/Export/Reset** | Backup and restore settings |

### Apply Changes Workflow

1. Make your changes in any section
2. Click the floating **"Apply Changes"** button to preview
3. Click **"Publish"** in the Customizer to save permanently

### Import/Export

**Export:**
1. Go to Import/Export/Reset section
2. Click "Export to JSON"
3. A JSON file will download

**Import:**
1. Open your JSON file and copy its contents
2. Paste into the Import textarea
3. Click "Import Settings"
4. Customizer will refresh with imported settings

### Reset

1. Click "Reset All Settings"
2. Confirm the first dialog
3. Confirm the second dialog (double-confirmation for safety)
4. Settings will reset to defaults

## CSS Variables Reference

The plugin uses these CSS custom properties:

### Colors
| Variable | Description |
|----------|-------------|
| `--login-maincolor` | Primary accent color |
| `--login-blue` | Blue accent |
| `--login-darkgray` | Dark gray |
| `--login-border-color` | Main border color |
| `--login-darkbg` | Dark background |

### Admin Bar Specific
| Variable | Description |
|----------|-------------|
| `--login-wp-adminbar-bgcolor` | Admin bar background |
| `--login-wp-adminbar-text` | Primary text color |
| `--login-wp-adminbar-text2` | Secondary text |
| `--login-wp-adminbar-accent` | Hover/active accent |
| `--login-wp-adminbar-height` | Bar height |
| `--login-wp-adminbar-items-padding` | Item padding |

## Troubleshooting

### Changes not appearing?
- Clear your browser cache
- Clear any server-side cache (if using caching plugins)
- The plugin adds no-cache headers during Customizer preview

### Login page not styled?
- Ensure the plugin is activated
- Check for CSS conflicts with other plugins
- Use the Custom CSS section to override conflicts

### Admin bar looks broken?
- Try resetting to defaults
- Check browser console for JavaScript errors
- Ensure no other plugins are conflicting

## Support

For issues or feature requests, please contact the plugin author.

## Changelog

### 1.0.0
- Initial release
- Admin Bar customization
- Login Page customization
- Import/Export functionality
- Reset to defaults
- Custom CSS support
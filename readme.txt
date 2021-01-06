=== Icons for CP ===

Description:        Manage and use SVG icons in your posts and pages. 
Version:            0.1.0         
Requires PHP:       5.6
Requires:           1.0.0
Tested:             4.9.99
Author:             Gieffe edizioni
Author URI:         https://www.gieffeedizioni.it
Plugin URI:         https://software.gieffeedizioni.it
Download link:      https://github.com/xxsimoxx/icons-for-cp/releases/download/v0.1.0/icons-for-cp-0.1.0.zip
License:            GPLv2
License URI:        https://www.gnu.org/licenses/gpl-2.0.html
    
Manage and use SVG icons in your posts and pages.

== Description ==
# Plugin description

This plugin allows to use SVG icons in post and pages.

### Add new icons
Just use the "Icons" menu in "Appearance" menu.
Add your own icons or any from FontAwesome to the theme.

Put the name of the icon as the title (use something like my-brand-icon) and the SVG as the post.

*Note: if you uninstall the plugin your icons get lost.
To keep them add `define('KEEP_ICONS_FOR_CP', true);` to `wp-config.php`.*

### Shortcode and MCE menu
```
[ifcp-icon icon='paw' size='16' color='#FF0000']
```

It also adds a menu to TinyMCE to choose the icon and insert the shortcode for you.

### Canuck CP
This plugin integrates with [Canuck CP](https://kevinsspace.ca/canuck-cp-classicpress-theme/).
Your icons are added to those present in the theme, and those present in the theme are available.
=== Icons for Canuck CP ===

Description:        Add new icons, shortcode and MCE menu for Canuck CP FontAwesome icons. 
Version:            0.0.5          
Requires PHP:       5.6
Requires:           1.0.0
Tested:             4.9.99
Author:             Gieffe edizioni
Author URI:         https://www.gieffeedizioni.it
Plugin URI:         https://software.gieffeedizioni.it
Download link:      https://github.com/xxsimoxx/icons-for-canuck-cp/releases/download/v0.0.5/icons-for-canuck-cp-0.0.5.zip
License:            GPLv2
License URI:        https://www.gnu.org/licenses/gpl-2.0.html
    
Add shortcode and MCE menu for Canuck CP FontAwesome icons.

== Description ==
# Plugin description

This plugin is intended for use with [Canuck CP](https://kevinsspace.ca/canuck-cp-classicpress-theme/).

### Add new icons 
Add your own icons or any from FontAwesome to the theme.
Just use the "Icons" menu in "Appearance" menu.
Put the name of the icon as the title (use something like my-brand-icon) and the SVG as the post.

*Note: if you uninstall the plugin your icons get lost.
To keep them add `define('KEEP_ICONS_FOR_CANUCK_CP', true);` to `wp-config.php`.*

### Add shortcode and MCE menu for Canuck CP FontAwesome icons.
You can use Canuck CP's icons in your content using a shortcode:
```
[canuckcp-icons icon='paw' size='16' color='#FF0000']
```

It also adds a menu to TinyMCE to choose the icon and insert the shortcode for you.
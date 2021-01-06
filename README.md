![Logo](images/logo-for-readme.jpg)
 
# Icons for CP

This plugin allows to use SVG icons in post and pages.

## Add new icons 
Just use the "Icons" menu in "Appearance" menu.
Add your own icons or any from FontAwesome to the theme.

Put the name of the icon as the title (use something like my-brand-icon) and the SVG as the post.

*Note: if you uninstall the plugin your icons get lost.
To keep them add `define('KEEP_ICONS_FOR_CP', true);` to `wp-config.php`.*

## Shortcode and MCE menu

```
[ifcp-icon icon='paw' size='16' color='#FF0000']
```

It also adds a menu to TinyMCE to choose the icon and insert the shortcode for you.

## Canuck CP
This plugin integrates with [Canuck CP](https://kevinsspace.ca/canuck-cp-classicpress-theme/).
Your icons are added to those present in the theme, and those present in the theme are available.
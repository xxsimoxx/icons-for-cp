![Logo](images/logo-for-readme.jpg)

[![CodeFactor](https://www.codefactor.io/repository/github/xxsimoxx/icons-for-cp/badge)](https://www.codefactor.io/repository/github/xxsimoxx/icons-for-cp)
![GitHub language count](https://img.shields.io/github/languages/count/xxsimoxx/icons-for-cp)
![GitHub All Releases](https://img.shields.io/github/downloads/xxsimoxx/icons-for-cp/total)
[![GitHub release (latest SemVer)](https://img.shields.io/github/v/release/xxsimoxx/icons-for-cp?label=Download%20latest&sort=semver)](https://github.com/xxsimoxx/icons-for-cp/releases/latest)

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
[ifcp-icon icon='paw' size='16' color='#FF0000' class='my-wonderful-class']
```
Size (width, default 16px), color and class are optional.

It also adds a menu to TinyMCE (v. 4 and v.5) to choose the icon and insert the shortcode for you.

## Canuck CP
This plugin integrates with [Canuck CP](https://kevinsspace.ca/canuck-cp-classicpress-theme/).
Your icons are added to those present in the theme, and those present in the theme are available.

## WP CLI
It is supported.
Use `wp help icons` or `wp help icons add` to see usage instructions.
You can bulk import a folder with a simple script like
```sh
for icon in *.svg; do
	wp icons add "$icon";
done
```

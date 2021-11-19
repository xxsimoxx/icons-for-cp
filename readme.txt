=== Icons for CP ===

Description:        Manage and use SVG icons in your posts and pages.
Version:            1.2.0
Requires PHP:       5.6
Requires:           1.1.0
Tested:             4.9.99
Author:             Gieffe edizioni
Author URI:         https://www.gieffeedizioni.it
Plugin URI:         https://software.gieffeedizioni.it
Download link:      https://github.com/xxsimoxx/icons-for-cp/releases/download/1.2.0/icons-for-cp-1.2.0.zip
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

### Shortcode and MCE menu
```
[ifcp-icon icon='paw' size='16' color='#FF0000' class='my-wonderful-class']
```
Size (width, default 16), color and class are optional.

It also adds a menu to TinyMCE (v. 4 and v.5) to choose the icon and insert the shortcode for you.

### Canuck CP
This plugin integrates with [Canuck CP](https://kevinsspace.ca/canuck-cp-classicpress-theme/).
Your icons are added to those present in the theme, and those present in the theme are available.

== Frequently asked questions ==
> Can I export my icons to another website?

Yes, go to Tools -> Export and export "Icons".

> Can I bulk import icons?

Yes, use wp-cli. You can create a script and use `wp icons add` to bulk add icons.

> Who can add, change or delete icons?

Only Admins can add, change or delete icons.
The icons can be used by anyone who can edit posts.
You can use the filter `ifcp_capabilities` to change this, but you also have to add `unfiltered_html` capability to those users.

```php
function prefix_add_theme_caps() {
    $role = get_role( 'author' );
}
add_action( 'admin_init', 'prefix_add_theme_caps',0);

function prefix_capabilities($capabilities) {
	$capabilities = [
		'edit_post'             => 'edit_posts',
		'read_post'             => 'edit_posts',
		'delete_post'           => 'edit_posts',
		'delete_posts'          => 'edit_posts',
		'edit_posts'            => 'edit_posts',
		'edit_others_posts'     => 'edit_posts',
		'publish_posts'         => 'edit_posts',
		'read_private_posts'    => 'edit_posts',
	];
	return $capabilities;
}
add_filter('ifcp_capabilities','prefix_capabilities');
```


> What happends to my icons when I uninstall Icons for CP?

Icons will be deleted (only the ones you added, not Canuck CP icons).
To keep them add `define('KEEP_ICONS_FOR_CP', true);` to `wp-config.php`.

> Why my SVG in not displayed?

`wpautop` is not very SVG-friendly. Try disabling it adding this line to an utility plugin:
```php
remove_filter( 'the_content', 'wpautop' );
```

> Do you track plugin usage?

To help us know the number of active installations of this plugin, we collect and store anonymized data when the plugin check in for updates. The date and unique plugin identifier are stored as plain text and the requesting URL is stored as a non-reversible hashed value. This data is stored for up to 28 days.


== Screenshots ==
1. Icons.
2. Creating an icon.
3. Insert an icon in page.
4. Canuck CP with custom icons.

== Changelog ==
= 1.2.0 =
* Added TinyMCE v.5 compatibility
* Updated UpdateClient

= 1.1.1 =
* Remove ALL data on uninstall

= 1.1.0 =
* Added preview in TinyMCE menu
* Code style review

= 1.0.0 =
* Initial release

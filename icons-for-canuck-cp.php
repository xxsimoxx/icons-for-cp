<?php
/**
 * Plugin Name: Icons for Canuck CP
 * Plugin URI: https://software.gieffeedizioni.it
 * Description: Add shortcode and MCE menu for Canuck CP FontAwesome icons.
 * Version: 0.0.1
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: Gieffe edizioni srl
 * Author URI: https://www.gieffeedizioni.it
 */

namespace XXSimoXX\IconsForCanuckCp;

if (!defined('ABSPATH')) {
	die('-1');
};

// Add auto updater https://codepotent.com/classicpress/plugins/update-manager/
require_once('UpdateClient.class.php');

class IconsForCanuckCp{

	public function __construct() {

		// Alert if Canuck CP is not installed or activated
		add_filter('plugin_row_meta', [$this, 'check_canuck'], 10, 2);

		// Add shortcode for icons
		// Usage: [canuckcp-icons icon='paw' size='16' color='#FF0000']
		add_shortcode('canuckcp-icons', [$this, 'process_shortcode']);

		// Add MCE menu
		foreach (['post.php','post-new.php'] as $hook) {
			add_action('admin_head-'.$hook, [$this, 'admin_head_menu']);
			add_action('admin_head-'.$hook, [$this, 'generate_menu_items']);
		}

	}

	public function check_canuck($links, $file) {
		if (basename($file) !== basename(__FILE__)) {
			return $links;
		}
		if (function_exists('canuckcp_svg')) {
			return $links;
		}
		array_push($links, '<span class="dashicons-before dashicons-warning"><a href="https://kevinsspace.ca/canuck-cp-classicpress-theme/">Canuck CP</a> theme is required!</span>');
		return $links;
	}

	public function process_shortcode($atts, $content = null) {
		if (!function_exists('canuckcp_svg')) {
			if (is_admin()) {
				return '<a href="https://kevinsspace.ca/canuck-cp-classicpress-theme/">Canuck CP</a> is not installed (only admins can see this)!';
			}
			return '';
		}
		extract(shortcode_atts([
			'icon'  => 'question-circle',
			'width' => '16',
			'color' => '#000000',
		], $atts));
		return '<span>'.canuckcp_svg($icon, $width, $color).'</span>';
	}

	public function admin_head_menu() {
		if (!$this->can_do_mce()) {
			return;
		}
		add_filter('mce_external_plugins', [$this, 'add_mce_plugin']);
		add_filter('mce_buttons',          [$this, 'register_mce_menu']);
	}

	public function add_mce_plugin($plugin_array) {
		$plugin_array['ifcp_mce_menu'] = plugins_url('js/menu.js', __FILE__);
		return $plugin_array;
	}

	public function register_mce_menu($buttons) {
		array_push($buttons, 'ifcp_mce_menu');
		return $buttons;
	}

	public function generate_menu_items() {
		if (!$this->can_do_mce()) {
			return;
		}
		echo '<script type=\'text/javascript\'>';
		echo 'ifcp_mce_menu_content=[';
		$icons = canuckcp_icon_select();
		foreach ($icons as $icon) {
			echo '{text: "'.$icon.'", onclick: function() {tinymce.activeEditor.insertContent("[canuckcp-icons icon=\''.$icon.'\' size=\'16\' color=\'#000000\']"); }},';
		}
		echo ']';
		echo '</script>';
	}

	private function can_do_mce() {
		if (!function_exists('canuckcp_svg')) {
			return false;
		}
		if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
			return false;
		}
		if (get_user_option('rich_editing') !== 'true') {
			return false;
		}
		return true;
	}

}

new IconsForCanuckCp;
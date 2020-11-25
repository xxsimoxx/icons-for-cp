<?php
/**
 * Plugin Name: Icons for Canuck CP
 * Plugin URI: https://software.gieffeedizioni.it
 * Description: Add new icons, shortcode and MCE menu for Canuck CP FontAwesome icons.
 * Version: 0.0.3
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: Gieffe edizioni srl
 * Author URI: https://www.gieffeedizioni.it
 * Text Domain: icons-for-canuck-cp
 * Domain Path: /languages
 */

namespace XXSimoXX\IconsForCanuckCp;

if (!defined('ABSPATH')) {
	die('-1');
};

// Add auto updater https://codepotent.com/classicpress/plugins/update-manager/
require_once('UpdateClient.class.php');

class IconsForCanuckCp{

	public function __construct() {

		// Load text domain.
		add_action('plugins_loaded', [$this, 'text_domain']);

		// Register custom post type to store icons.
		add_action('init', [$this, 'register_cpt']);
		// Remove rich editing and buttons
		add_filter('user_can_richedit', [$this, 'remove_rich_editing']);
		add_action('admin_head', [$this, 'remove_buttons']);
		// Adjust title
		add_filter('enter_title_here', [$this, 'title_placeholder'], 10, 2);
		// Do checks before saving content
		add_action('admin_enqueue_scripts', [$this, 'postcheck'], 2000);
		add_action('wp_ajax_ifcp_postcheck', [$this, 'check_callback']);

		// Add icons from CPT to Canuck CP theme
		add_filter ('canuckcp_icons', [$this, 'add_icons']);
		add_filter ('canuckcp_icon_select', [$this, 'icon_select']);

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

		// Uninstall.
		register_uninstall_hook(__FILE__, [__CLASS__, 'uninstall']);

	}

	public function text_domain() {
		load_plugin_textdomain('icons-for-canuck-cp', false, basename(dirname(__FILE__)).'/languages');
	}

	public function register_cpt() {
		$labels = [
			'name'                => __('Icons', 'icons-for-canuck-cp'),
			'singular_name'       => __('Icon', 'icons-for-canuck-cp'),
			'add_new'             => __('New icon', 'icons-for-canuck-cp'),
			'add_new_item'        => __('Add new icon', 'icons-for-canuck-cp'),
			'edit_item'           => __('Edit icon', 'icons-for-canuck-cp'),
			'new_item'            => __('New icon', 'icons-for-canuck-cp'),
			'all_items'           => __('Icons', 'icons-for-canuck-cp'),
			'view_item'           => __('View icon', 'icons-for-canuck-cp'),
			'search_items'        => __('Search icons', 'icons-for-canuck-cp'),
			'not_found'           => __('No icons found', 'icons-for-canuck-cp'),
			'not_found_in_trash'  => __('No icons found in trash', 'icons-for-canuck-cp'),
			'menu_name'           => __('Icons', 'icons-for-canuck-cp'),
		];
		$args = [
			'public'        => false,
			'show_ui'       => true,
			'show_in_menu'  => 'themes.php',
			'rewrite'       => false,
			'supports'      => ['title', 'editor'],
			'labels'        => $labels,
		];
		register_post_type('canuckcp-icons', $args);
	}

	public function remove_rich_editing ($default) {
		global $post;
		if (isset($post->post_type) && $post->post_type === 'canuckcp-icons') {
			return false;
		}
		return $default;
	}

	public function remove_buttons () {
		global $current_screen;
		if ($current_screen->post_type === 'canuckcp-icons') {
			remove_action('media_buttons', 'media_buttons');
		}
	}

	public function title_placeholder($placeholder, $post) {
		if (get_post_type($post) === 'canuckcp-icons') {
			/* translators: placeholder for title */
			$placeholder = __('icon-name', 'icons-for-canuck-cp');
		}
		return $placeholder;
	}

	public function postcheck($hook) {
		if (!in_array($hook, ['edit.php', 'post.php', 'post-new.php'])) {
			return;
		}
		global $post;
		$post_type = get_post_type();
		if (!isset($post->post_type)) {
			return;
		}
		if ($post_type !== 'canuckcp-icons') {
			return;
		}
		wp_enqueue_script('ifcp_post_check', plugins_url('js/postchecks.js', __FILE__), ['jquery'], false, true);
	}

	function check_callback() {
		function title_check() {
			$title = $_REQUEST['post_title'];
			if (!preg_match('/^[a-z0-9\-]+$/', $title)) {
				return [
					'message' => __('Caution: only lowercase letters and dashes are allowed in the title.', 'icons-for-canuck-cp'),
					'status'  => 'error',
				];
			}
			if (function_exists('canuckcp_icon_select') && in_array($title, canuckcp_icon_select())) {
				return [
					'message' => __('Caution: there is already an icon called '.$title.'.', 'icons-for-canuck-cp'),
					'status'  => 'notice notice-warning',
				];
			}
			return [
					'message' => __('Title is good as icon name.', 'icons-for-canuck-cp'),
					'status'  => 'updated',
				];
		}
		$response = title_check();
		echo wp_json_encode($response);
		die();
	}

	public function add_icons($icons) {
		$args = [
			'post_type' => 'canuckcp-icons',
			'public'    => 'true',
		];
		$posts = get_posts($args);
		foreach ($posts as $post) {
			$icon = $post->to_array();
			$icons += [ $icon['post_name'] => $icon['post_content'] ];
		}
		return $icons;
	}

	public function icon_select($icons) {
		$args = [
			'post_type' => 'canuckcp-icons',
			'public'    => 'true',
		];
		$posts = get_posts($args);
		foreach ($posts as $post) {
			$icon = $post->to_array();
			$icons += [ $icon['post_name'] => $icon['post_name'] ];
		}
		return $icons;
	}

	public function check_canuck($links, $file) {
		if (function_exists('canuckcp_svg')) {
			return $links;
		}
		if (basename($file) !== basename(__FILE__)) {
			return $links;
		}
		$url = 'https://kevinsspace.ca/canuck-cp-classicpress-theme';
		$message = '<span class="dashicons-before dashicons-warning">'.sprintf(wp_kses(__('<a href="%s">Canuck CP</a> theme is required!', 'icons-for-canuck-cp'), ['a' => [ 'href' => []]]), esc_url($url)).'</span>';
		array_push($links, $message);
		return $links;
	}

	public function process_shortcode($atts, $content = null) {
		if (!function_exists('canuckcp_svgX')) {
			if (current_user_can('manage_options')) {
				$url = 'https://kevinsspace.ca/canuck-cp-classicpress-theme';
				$message = sprintf(wp_kses(__('[ICON PLACEHOLDER] <a href="%s">Canuck CP</a> is not installed (only admins can see this)!', 'icons-for-canuck-cp'), ['a' => [ 'href' => []]]), esc_url($url)).'</span>';
				return $message;
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
		/* Translators: MCE button name */
		echo 'ifcp_mce_menu_name="'.__('Icons', 'icons-for-canuck-cp').'";';
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

	public static function uninstall() {
		if (defined('\KEEP_ICONS_FOR_CANUCK_CP') && KEEP_ICONS_FOR_CANUCK_CP === true) {
			return;
		}
		$allposts = get_posts([
			'post_type'   => 'canuckcp-icons',
			'post_status' => 'any',
		]);
		foreach ($allposts as $eachpost) {
			wp_delete_post($eachpost->ID, true);
		}
	}

}

new IconsForCanuckCp;
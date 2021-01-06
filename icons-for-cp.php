<?php
/**
 * Plugin Name: Icons for CP
 * Plugin URI: https://software.gieffeedizioni.it
 * Description: Manage and use SVG icons in your posts and pages.
 * Version: 0.1.0
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: Gieffe edizioni srl
 * Author URI: https://www.gieffeedizioni.it
 * Text Domain: icons-for-cp
 * Domain Path: /languages
 */

namespace XXSimoXX\IconsForCp;

if (!defined('ABSPATH')) {
	die('-1');
};

// Add auto updater https://codepotent.com/classicpress/plugins/update-manager/
require_once('UpdateClient.class.php');

class IconsForCanuckCp{

	private $all_icons;

	public function __construct() {

		// Load text domain.
		add_action('plugins_loaded', [$this, 'text_domain']);

		// Register custom post type to store icons.
		add_action('init', [$this, 'register_cpt']);
		// Remove rich editing, buttons and autosave
		add_filter('user_can_richedit', [$this, 'remove_rich_editing']);
		add_action('admin_head', [$this, 'remove_buttons']);
		add_action('admin_enqueue_scripts', [$this, 'remove_autosave']);
		// Handle Ajax import callback
		add_action('wp_ajax_ifcp_import', [$this, 'import_ajax_callback']);
		// Adjust title
		add_filter('enter_title_here', [$this, 'title_placeholder'], 10, 2);
		// Do checks before saving content
		add_action('admin_enqueue_scripts', [$this, 'postcheck'], 2000);
		add_action('wp_ajax_ifcp_postcheck', [$this, 'check_callback']);
		// Add preview in icons list
		add_filter('manage_icons-for-cp_posts_columns', [$this, 'custom_columns']);
		add_action('manage_icons-for-cp_posts_custom_column', [$this, 'custom_column_handle'], 10, 2);

		// Add icons from CPT to Canuck CP theme
		add_filter ('canuckcp_icons', [$this, 'add_icons']);
		add_filter ('canuckcp_icon_select', [$this, 'icon_select']);

		// Add shortcode for icons
		// Usage: [ifcp-icon icon='paw' size='16' color='#FF0000']
		add_shortcode('ifcp-icon', [$this, 'process_shortcode']);

		// Add MCE menu
		foreach (['post.php','post-new.php'] as $hook) {
			add_action('admin_head-'.$hook, [$this, 'admin_head_menu']);
			add_action('admin_head-'.$hook, [$this, 'generate_menu_items']);
		}

		// Uninstall.
		register_uninstall_hook(__FILE__, [__CLASS__, 'uninstall']);

	}

	public function text_domain() {
		load_plugin_textdomain('icons-for-cp', false, basename(dirname(__FILE__)).'/languages');
	}

	public function register_cpt() {
		// Check if customizer exists
		$where = function_exists('is_customize_preview') ? 'themes.php' : true;
		$labels = [
			'name'                => __('Icons', 'icons-for-cp'),
			'singular_name'       => __('Icon', 'icons-for-cp'),
			'add_new'             => __('New icon', 'icons-for-cp'),
			'add_new_item'        => __('Add new icon', 'icons-for-cp'),
			'edit_item'           => __('Edit icon', 'icons-for-cp'),
			'new_item'            => __('New icon', 'icons-for-cp'),
			'all_items'           => __('Icons', 'icons-for-cp'),
			'view_item'           => __('View icon', 'icons-for-cp'),
			'search_items'        => __('Search icons', 'icons-for-cp'),
			'not_found'           => __('No icons found', 'icons-for-cp'),
			'not_found_in_trash'  => __('No icons found in trash', 'icons-for-cp'),
			'menu_name'           => __('Icons', 'icons-for-cp'),
		];
		$args = [
			'public'                => false,
			'show_ui'               => true,
			'show_in_menu'          => $where,
			'rewrite'               => false,
			'supports'              => ['title', 'editor'],
			'labels'                => $labels,
			'exclude_from_search'   => true,
			'register_meta_box_cb'	=> [$this, 'add_meta_boxes'],
		];
		register_post_type('icons-for-cp', $args);
	}

	public function remove_rich_editing ($default) {
		global $post;
		if (isset($post->post_type) && $post->post_type === 'icons-for-cp') {
			return false;
		}
		return $default;
	}

	public function remove_buttons () {
		global $current_screen;
		if ($current_screen->post_type === 'icons-for-cp') {
			remove_action('media_buttons', 'media_buttons');
		}
	}

	public function remove_autosave() {
		if (get_post_type() !== 'icons-for-cp') {
			return;
		}
		wp_dequeue_script('autosave');
	}

	public function add_meta_boxes() {
		add_meta_box('ifcp-pw', __('Preview'), [$this, 'preview_callback'], null, 'side');
		if (!function_exists('curl_version')) {
			return;
		}
		add_meta_box('ifcp-import', __('Import'), [$this, 'import_callback']);
	}


	public function preview_callback($post) {
		echo '<div id="ifcp-pw-inner">';
		echo get_post_field('post_content', $post, 'raw');
		echo '</div>';
	}

	public function import_callback($post) {
		echo '<input size=100 type="text" id="ifcp-import-url" value="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/master/svgs/regular/thumbs-up.svg"> ';
		echo '<input type="button" name="import" class="button button-large" id="ifcp-import-do" value="'.__('Import').'">';
		echo '<span class="spinner" id="ifcp-import-spinner"></span>';
	}

	public function title_placeholder($placeholder, $post) {
		if (get_post_type($post) === 'icons-for-cp') {
			/* translators: placeholder for title */
			$placeholder = __('icon-name', 'icons-for-cp');
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
		if ($post_type !== 'icons-for-cp') {
			return;
		}
		wp_enqueue_script('ifcp_post_check', plugins_url('js/posteditor.js', __FILE__), ['jquery'], false, true);
		wp_localize_script('ifcp_post_check', 'external', [
			'url'    => admin_url('admin-ajax.php'),
			'nonce'  => wp_create_nonce('ifcp-ajax-nonce'),
			'postid' => $post->ID,
		]);
		$cm_settings['codeEditor'] = wp_enqueue_code_editor(['type' => 'image/svg+xml']);
		wp_enqueue_script('wp-theme-plugin-editor');
		wp_localize_script('jquery', 'cm_settings', $cm_settings);
		wp_enqueue_style('wp-codemirror');
	}

	function check_callback() {

		if (!(isset($_REQUEST['post_title']) && isset($_REQUEST['postid']) && isset($_REQUEST['nonce']))) {
			die('Missing post arguments.');
		};

		$title = $_REQUEST['post_title'];
		$nonce = $_REQUEST['nonce'];
		$postid = $_REQUEST['postid'];

		if (!wp_verify_nonce($nonce, 'ifcp-ajax-nonce')) {
			die('Nonce error.');
		}

		$response = [
			'message' => __('Title is good as icon name.', 'icons-for-cp'),
			'status'  => 'updated',
			'proceed' => true,
		];

		if (!preg_match('/^[a-z0-9\-]+$/', $title)) {
			$response = [
				'message' => __('Caution: only lowercase letters, dashes and digits dashes are allowed in the title.', 'icons-for-cp'),
				'status'  => 'error',
				'proceed' => false,
			];
		}

		$this->fill_svg_array();
		if (isset($this->all_icons[$title]) && $title !== get_the_title($postid)) {
			$response = [
				/* Translators: %s name of the icon */
				'message' => sprintf(__('Caution: there is already an icon called %s.', 'icons-for-cp'), $title),
				'status'  => 'notice notice-warning',
				'proceed' => false,
			];
		}

		echo wp_json_encode($response);
		die();
	}

	function import_ajax_callback() {
		if (!function_exists('curl_version')) {
			return;
		}
		if (!(isset($_REQUEST['remote_url']) && isset($_REQUEST['nonce']))) {
			die('Missing post arguments.');
		};
		$remote_url = $_REQUEST['remote_url'];
		$nonce = $_REQUEST['nonce'];
		if (!wp_verify_nonce($nonce, 'ifcp-ajax-nonce')) {
			die('Nonce error.');
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $remote_url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$icon = curl_exec($ch);
		if (curl_errno($ch)) {
			$response = ['bad' => true, 'error' => curl_error($ch)];
			echo wp_json_encode($response);
			die();
		}
		$resultStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($resultStatus !== 200) {
			$response = ['bad' => true, 'error' => 'Request failed: HTTP status code: '.$resultStatus];
			echo wp_json_encode($response);
			die();
		}

		$response = ['bad' => false, 'icon' => $icon];
		curl_close($ch);

		echo wp_json_encode($response);
		die();
	}

	public function custom_columns($columns) {
		$columns['preview'] = __('Preview').'<style>.column-preview { min-width: 30px;}</style>';
		return $columns;
	}

	public function custom_column_handle($column, $post_id) {
		switch ($column) {
			case 'preview' :
				$post = get_post($post_id);
								echo '<span>';

				echo get_post_field('post_content', $post, 'raw');
				echo '</span>';
			break;
		}
	}

	public function add_icons($icons) {
		$args = [
			'post_type' => 'icons-for-cp',
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
			'post_type' => 'icons-for-cp',
			'public'    => 'true',
		];
		$posts = get_posts($args);
		foreach ($posts as $post) {
			$icon = $post->to_array();
			$icons += [ $icon['post_name'] => $icon['post_name'] ];
		}
		return $icons;
	}

	public function process_shortcode($atts, $content = null) {
		extract(shortcode_atts([
			'icon'  => 'question-circle',
			'width' => '16',
			'color' => '#000000',
		], $atts));
		return '<span>'.$this->get_svg($icon, $width, $color).'</span>';
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
		if (empty($this->all_icons)) {
			return;
		}
		$this->fill_svg_array();
		echo '<script type=\'text/javascript\'>';
		/* Translators: MCE button name */
		echo 'ifcp_mce_menu_name="'.__('Icons', 'icons-for-cp').'";';
		echo 'ifcp_mce_menu_content=[';
		foreach ($this->all_icons as $icon => $content) {
			echo '{text: "'.$icon.'", onclick: function() {tinymce.activeEditor.insertContent("[ifcp-icon icon=\''.$icon.'\' size=\'16\' color=\'#000000\']"); }},';
		}
		echo ']';
		echo '</script>';
	}

	private function can_do_mce() {
		if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
			return false;
		}
		if (get_user_option('rich_editing') !== 'true') {
			return false;
		}
		return true;
	}

	private function fill_svg_array() {
		if (!empty($this->all_icons)) {
			return;
		}
		if (function_exists('canuckcp_icon_array')) {
			$this->all_icons = canuckcp_icon_array();
		}
		$query = new \WP_Query(['post_type' => 'icons-for-cp']);
		$posts = $query->posts;
		foreach ($posts as $post) {
			$this->all_icons[get_the_title($post)] = get_post_field('post_content', $post, 'raw');
		}
	}

	private function get_svg($icon, $icon_width = '16', $icon_color = '#7f7f7f') {
		/**
		 * Code inspired from Canuck CP ClassicPress Theme
		 * by Kevin Archibald <https://kevinsspace.ca/contact/>
		 */
		$this->fill_svg_array();

		if ($icon === '') {
			return;
		}
		if (!isset($this->all_icons[$icon])) {
			return;
		}

		$icon_picked = $this->all_icons[$icon];

		$width       = '<svg class="icon-svg-class" width="'.$icon_width.'"';
		$fill        = '<path class="icon-path-class '.$icon.'" fill="'.$icon_color.'"';
		$icon_picked = str_replace('<svg', $width, $icon_picked);
		$icon_picked = str_replace('<path', $fill, $icon_picked);

		return $icon_picked;
	}

	public static function uninstall() {
		if (defined('\KEEP_ICONS_FOR_CP') && KEEP_ICONS_FOR_CP === true) {
			return;
		}
		$allposts = get_posts([
			'post_type'   => 'icons-for-cp',
			'post_status' => 'any',
		]);
		foreach ($allposts as $eachpost) {
			wp_delete_post($eachpost->ID, true);
		}
	}

}

new IconsForCanuckCp;
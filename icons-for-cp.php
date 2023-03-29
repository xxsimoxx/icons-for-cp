<?php
/**
 * Plugin Name: Icons for CP
 * Plugin URI: https://software.gieffeedizioni.it
 * Description: Manage and use SVG icons in your posts and pages.
 * Version: 1.3.4
 * Requires CP: 1.1
 * Requires PHP: 5.6
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
}

// Add auto updater
require_once('classes/UpdateClient.class.php');

// Add WPCLI support
require_once('classes/WPCLI.class.php');

class IconsForCp{

	private $all_icons = [];
	private $our_icons;

	public function __construct() {

		// Load text domain.
		add_action('plugins_loaded', [$this, 'text_domain']);

		// Register custom post type to store icons.
		add_action('init', [$this, 'register_cpt']);

		// Remove rich editing, buttons, autosave and adjust title
		add_filter('user_can_richedit', [$this, 'remove_rich_editing']);
		add_action('admin_head', [$this, 'remove_buttons']);
		add_action('admin_enqueue_scripts', [$this, 'remove_autosave']);
		add_filter('enter_title_here', [$this, 'title_placeholder'], 10, 2);

		// Add scripts to post editor and handle Ajax
		add_action('admin_enqueue_scripts', [$this, 'edit_post_scripts'], 2000);
		add_action('wp_ajax_ifcp', [$this, 'ajax_callback']);

		// Add preview in icons list
		add_filter('manage_icons-for-cp_posts_columns', [$this, 'custom_columns']);
		add_action('manage_icons-for-cp_posts_custom_column', [$this, 'custom_column_handle'], 10, 2);

		// Add link to icons in plugins page
		add_filter('plugin_action_links_'.plugin_basename(__FILE__), [$this, 'settings_link']);

		// Add help
		add_action('admin_head', [$this, 'help']);

		// Add icons from CPT to Canuck CP theme
		add_filter('canuckcp_icons', [$this, 'add_icons']);
		add_filter('canuckcp_icon_select', [$this, 'icon_select']);

		// Add shortcode for icons
		// Usage: [ifcp-icon icon='paw' size='16' color='#FF0000']
		add_shortcode('ifcp-icon', [$this, 'process_shortcode']);

		// Add MCE menu
		foreach (['post.php','post-new.php'] as $hook) {
			add_action('admin_head-'.$hook, [$this, 'admin_head_menu'], 20);
			add_action('admin_head-'.$hook, [$this, 'generate_menu_items'], 10);
		}

		// Add "icons" commands to WP-CLI
		if (defined('WP_CLI') && WP_CLI) {
			\WP_CLI::add_command('icons', '\XXSimoXX\IconsForCp\Icons');
		}

		// Uninstall.
		register_uninstall_hook(__FILE__, [__CLASS__, 'uninstall']);

	}

	public function text_domain() {
		load_plugin_textdomain('icons-for-cp', false, basename(dirname(__FILE__)).'/languages');
	}

	public function register_cpt() {
		$capabilities = [
			'edit_post'             => 'manage_options',
			'read_post'             => 'manage_options',
			'delete_post'           => 'manage_options',
			'delete_posts'          => 'manage_options',
			'edit_posts'            => 'manage_options',
			'edit_others_posts'     => 'manage_options',
			'publish_posts'         => 'manage_options',
			'read_private_posts'    => 'manage_options',
		];
		$capabilities = apply_filters('ifcp_capabilities', $capabilities);
		// Check if customizer exists or if user is not admin, and place Icons outside customizer.
		$where = function_exists('is_customize_preview') && current_user_can('manage_options') ? 'themes.php' : true;
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
			'capabilities'       	=> $capabilities,
		];
		register_post_type('icons-for-cp', $args);
	}

	public function add_meta_boxes() {
		add_meta_box('ifcp-pw', __('Preview', 'icons-for-cp'), [$this, 'preview_callback'], null, 'side');
		if (!function_exists('curl_version')) {
			return;
		}
		add_meta_box('ifcp-import', __('Import', 'icons-for-cp'), [$this, 'import_callback']);
	}

	public function preview_callback($post) {
		echo '<div id="ifcp-pw-inner">';
		// Can't really sanitize SVG.
		echo get_post_field('post_content', $post, 'raw'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}

	public function import_callback() {
		echo '<input size=100 type="text" id="ifcp-import-url" value="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/master/svgs/regular/thumbs-up.svg"> ';
		echo '<input type="button" name="import" class="button button-large" id="ifcp-import-do" value="'.esc_html__('Import', 'icons-for-cp').'">';
		echo '<span class="spinner" id="ifcp-import-spinner"></span>';
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
		if ($current_screen->post_type !== 'icons-for-cp') {
			return;
		}
		remove_action('media_buttons', 'media_buttons');
	}

	public function remove_autosave() {
		if (get_post_type() !== 'icons-for-cp') {
			return;
		}
		wp_dequeue_script('autosave');
	}

	public function title_placeholder($placeholder, $post) {
		if (get_post_type($post) === 'icons-for-cp') {
			/* translators: placeholder for title */
			$placeholder = __('icon-name', 'icons-for-cp');
		}
		return $placeholder;
	}

	public function edit_post_scripts($hook) {
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
		$cm_settings = [];
		$cm_settings['codeEditor'] = wp_enqueue_code_editor(['type' => 'image/svg+xml']);
		wp_enqueue_script('wp-theme-plugin-editor');
		wp_localize_script('jquery', 'cm_settings', $cm_settings);
		wp_enqueue_style('wp-codemirror');
	}

	public function ajax_callback() {

		if (!isset($_REQUEST['nonce'])) {
			die('Missing nonce.');
		}
		if (!wp_verify_nonce(sanitize_key(wp_unslash($_REQUEST['nonce'])), 'ifcp-ajax-nonce')) {
			die('Nonce error.');
		}
		if (!isset($_REQUEST['req'])) {
			die('Missing requested action.');
		}

		switch ($_REQUEST['req']) {

			case 'title':
				if (!(isset($_REQUEST['post_title']) && isset($_REQUEST['postid']))) {
					die('Missing post arguments.');
				}
				// Can't sanitize $_REQUEST['post_title'] as check_title is there
				// to bail if the title have non permitted chars
				// @see: private function check_title($title, $postid)
				$response = $this->check_title(wp_unslash($_REQUEST['post_title']), intval(wp_unslash($_REQUEST['postid']))); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				break;

			case 'import':
				if (!function_exists('curl_version')) {
					die('Missing curl.');
				}
				if (!isset($_REQUEST['remote_url'])) {
					die('Missing post arguments.');
				}
				$response = $this->fetch_svg(esc_url_raw(wp_unslash($_REQUEST['remote_url'])));
				break;

			default:
				die('Unimplemented request.');

		} // switch

		echo wp_json_encode($response);
		die();

	}

	private function check_title($title, $postid) {
		if (!preg_match('/^[a-z0-9\-]+$/', $title)) {
			return [
				'message' => __('Caution: only lowercase letters, dashes and digits are allowed in the title.', 'icons-for-cp').$postid,
				'status'  => 'error',
				'proceed' => false,
			];
		}
		$this->fill_svg_array();
		if (isset($this->all_icons[$title]) && $title !== get_the_title($postid)) {
			return [
				/* Translators: %s name of the icon */
				'message' => sprintf(__('Caution: there is already an icon called %s.', 'icons-for-cp'), $title),
				'status'  => 'notice notice-warning',
				'proceed' => false,
			];
		}
		return [
			'message' => __('Title is good as icon name.', 'icons-for-cp'),
			'status'  => 'updated',
			'proceed' => true,
		];
	}

	private function fetch_svg ($remote_url) {
		$response = wp_remote_get($remote_url);
		if (is_wp_error($response)) {
			return [
				'bad'   => true,
				'error' => 'WP Error: '.implode(', '.$response->errors),
			];
		}
		if (isset($response['response']['code']) && isset($response['response']['message']) && $response['response']['code'] !== 200) {
			return [
				'bad'   => true,
				'error' => 'Request failed: HTTP status code '.$response['response']['code'].': '.$response['response']['message'],
			];
		}
		return [
			'bad'  => false,
			'icon' => $response['body'],
		];
	}

	public function custom_columns($columns) {
		$columns['preview'] = esc_html__('Preview', 'icons-for-cp').'<style>.column-preview { min-width: 40px; text-align: right !important; }</style>';
		return $columns;
	}

	public function custom_column_handle($column, $post_id) {
		switch ($column) {
			case 'preview' :
				$this->fill_svg_array();
				echo '<span>';
				// Can't escape SVG.
				echo $this->get_svg(get_the_title($post_id), 40, '#000'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '</span>';
			break;
		}
	}

	private function get_our_icons() {
		if (!isset($this->our_icons)) {
			$args = [
				'post_type' 	=> 'icons-for-cp',
				'public'    	=> 'true',
				'numberposts'	=> -1,
			];
			$this->our_icons = get_posts($args);
		}
		return $this->our_icons;
	}

	public function add_icons($icons) {
		$posts = $this->get_our_icons();
		foreach ($posts as $post) {
			$icon = $post->to_array();
			$icons += [ $icon['post_name'] => $icon['post_content'] ];
		}
		return $icons;
	}

	public function icon_select($icons) {
		$posts = $this->get_our_icons();
		foreach ($posts as $post) {
			$icon = $post->to_array();
			$icons += [ $icon['post_name'] => $icon['post_name'] ];
		}
		return $icons;
	}

	private function fill_svg_array() {
		if ($this->all_icons !== []) {
			return;
		}
		if (function_exists('canuckcp_icon_array')) {
			$this->all_icons = canuckcp_icon_array();
		}
		$posts = $this->get_our_icons();
		foreach ($posts as $post) {
			$this->all_icons[get_the_title($post)] = get_post_field('post_content', $post, 'raw');
		}
	}

	public function settings_link($links) {
		$link = '<a href="'.admin_url('edit.php?post_type=icons-for-cp').'" title="'.__('Settings', 'icons-for-cp').'"><i class="dashicons-before dashicons-edit"></i></a>';
		array_unshift($links, $link);
		return $links;
	}

	public function help() {

		$screen = get_current_screen();
		if (!in_array($screen->{'id'}, ['icons-for-cp', 'edit-icons-for-cp'])) {
			return;
		}

		/* Translators: Help menu title*/
		$title = __('Icons for CP', 'icons-for-cp');
		$content = '<h3>'.$title.'</h3>';
		$content .= '<p>'.__('Place the icon name in the title (use only lowercase letters, dashes and digits, something like "my-logo-3").', 'icons-for-cp').'</p>';
		$content .= '<p>'.__('Place the SVG code as post content (you will see your image in the Preview meta box).', 'icons-for-cp').'</p>';
		if (function_exists('curl_version')) {
			$content .= '<p>'.__('If you insert an URL in the Import meta box this will be fetched as post content.', 'icons-for-cp').'</p>';
		}
		/* Translators: this string is followed by an example shortcode*/
		$content .= '<p>'.__('You can use your icons in posts and pages using a shortcode like this:', 'icons-for-cp').'<br>';
		$content .= '<code>[ifcp-icon icon="paw" size="16" color="#FF0000" class="my-wonderful-class"]</code></p>';

		$screen->add_help_tab([
			'id' 		=> 'ifcp-help',
			'title' 	=> $title,
			'content' 	=> $content,
		]);

	}


	private function get_svg($icon, $icon_width = '16', $icon_color = null) {

		$this->fill_svg_array();
		if ($icon === '') {
			return;
		}
		if (!isset($this->all_icons[$icon])) {
			return;
		}
		$icon_picked = $this->all_icons[$icon];

		// Remove comments
		$icon_picked = preg_replace('/<!--(.|\s)*?-->/', '', $icon_picked);

		$dom = new \DOMDocument();
		$dom->loadXML($icon_picked);

		// Add width and class to SVG
		foreach ($dom->getElementsByTagName('svg') as $element) {
			$class = 'ifcp-svg-class '.$element->getAttribute('class');
			$element->setAttribute('class', $class);
			$element->setAttribute('width', $icon_width);
			$element->removeAttribute('height');
		}

		// Add fill and class to paths
		foreach ($dom->getElementsByTagName('path') as $element) {
			$class = 'ifcp-path-class '.$icon.' '.$element->getAttribute('class');
			$element->setAttribute('class', $class);
			if ($icon_color === null) {
				continue;
			}
			$element->setAttribute('fill', $icon_color);
		}

		// Put styles outside SVG.
		$style = '';
		foreach ($dom->getElementsByTagName('style') as $element) {
			$style .= $element->nodeValue;
			$element->parentNode->removeChild($element);
		}
		if ($style !== '') {
			$style = '<style>'.$style.'</style>';
		}

		// Remove empty defs
		foreach ($dom->getElementsByTagName('defs') as $element) {
			if ($element->childNodes->length !== 0) {
				continue;
			}
			$element->parentNode->removeChild($element);
		}

		$icon_picked = $dom->saveXML();
		// Cleanup output
		$icon_picked = str_replace('<?xml version="1.0"?>', '', $icon_picked);
		$icon_picked = preg_replace('/[\r\n]/', '', $icon_picked);
		return $style.$icon_picked;

	}

	public function process_shortcode($atts) {
		$p = shortcode_atts([
			'icon'  => 'question-circle',
			'size'  => '16',
			'color' => null,
			'class' => null,
		], $atts);
		if ($p['class'] !== null) {
			$p['class'] = ' class="'.$p['class'].'"';
		}
		return '<span'.$p['class'].'>'.$this->get_svg($p['icon'], $p['size'], $p['color']).'</span>';
	}

	private function is_mce_5() {
		global $tinymce_version;
		return isset($tinymce_version) && substr($tinymce_version, 0, 1) === '5';
	}

	public function admin_head_menu() {
		if (!$this->can_do_mce()) {
			return;
		}
		$this->fill_svg_array();
		if ($this->all_icons === []) {
			return;
		}
		add_filter('mce_external_plugins', [$this, 'add_mce_plugin']);
		add_filter('mce_buttons',          [$this, 'register_mce_menu']);
	}

	public function add_mce_plugin($plugin_array) {
		$js = $this->is_mce_5() ? 'js/menu-5.js' : 'js/menu.js';
		$plugin_array['ifcp_mce_menu'] = plugins_url($js, __FILE__);
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

		$this->fill_svg_array();
		if ($this->all_icons === []) {
			return;
		}

		$style = '<style>'."\n";
		$icon5 = 'ifcp_mce_menu_icons={'."\n";
		$menu4 = 'ifcp_mce_menu_content=['."\n";
		$menu5 = 'ifcp_mce_menu_content=['."\n";
		foreach (array_keys($this->all_icons) as $icon) {
			// MCE4 style
			// To render icon preview in menu a style with Base64 encoded icon is used. There is no code obfuscation.
			$style .= '.mce-i-ifcp-'.$icon.':before{content: url("data:image/svg+xml;base64,'.base64_encode($this->get_svg($icon, 16, '#000')).'");}'."\n"; //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			// MCE4 menu
			$menu4 .= '{text: "'.$icon.'", icon: "ifcp-'.$icon.'", onclick: function() {tinymce.activeEditor.insertContent("[ifcp-icon icon=\''.$icon.'\' size=\'16\' color=\'#000000\']"); }},'."\n";
			// MCE5 icon pack
			$icon5 .= '"mce-ifcp-'.$icon.'":"'.addslashes($this->get_svg($icon, 16, '#000')).'",'."\n";
			// MCE5 menu
			$menu5 .= '{type:"menuitem", icon: "mce-ifcp-'.$icon.'", text: "'.$icon.'", onAction: function() {tinymce.activeEditor.insertContent("[ifcp-icon icon=\''.$icon.'\' size=\'16\' color=\'#000000\']"); }},'."\n";
		}
		$style .= '</style>'."\n";
		$menu4 .= ']'."\n";
		$icon5 .= '};'."\n";
		$menu5 .= ']'."\n";

		if ($this->is_mce_5()) {
			echo '<script type="text/javascript">';
			echo $menu5; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $icon5; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo 'ifcp_mce_menu_name="'.esc_html__('Icons', 'icons-for-cp').'";';
			echo '</script>';
		} else {
			echo $style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<script type="text/javascript">';
			echo $menu4; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo 'ifcp_mce_menu_name="'.esc_html__('Icons', 'icons-for-cp').'";';
			echo '</script>';
		}

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

	private static function delete_all_icons() {
			$allposts = get_posts([
				'post_type'   => 'icons-for-cp',
				'post_status' => 'any',
				'numberposts' => -1,
			]);
			foreach ($allposts as $eachpost) {
				wp_delete_post($eachpost->ID, true);
			}
	}

	public static function uninstall() {

		if (defined('\KEEP_ICONS_FOR_CP') && \KEEP_ICONS_FOR_CP === true) {
			return;
		}

		$me = new self();

		if (!is_multisite()) {
			$me->delete_all_icons();
			return;
		}

		// Multisite
		global $wpdb;
		$blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
		$original_blog_id = get_current_blog_id();

		foreach ($blog_ids as $blog_id) {
			switch_to_blog($blog_id);
			$me->delete_all_icons();
		}

		switch_to_blog($original_blog_id);

	}

}

new IconsForCp;

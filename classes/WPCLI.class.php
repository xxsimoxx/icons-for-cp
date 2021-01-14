<?php

namespace XXSimoXX\IconsForCp;

if (!defined('ABSPATH')) {
	die('-1');
};

/**
* Commands to work with Icons for CP.
*
*
* ## EXAMPLES
*
*     wp icons add --name=cool-name icon-file.svg
*
* @when after_wp_load
*/

class Icons{

	/**
	* Adds an icon to Icons for CP
	*
	* ## OPTIONS
	*
	* [--name=<name>]
	* : Use this name for icon.
	* default: file name without extension
	*
	* [--overwrite]
	* : If the icon name already exists, overwrite it's content.
	*
	* ## PARAMETER
	*
	* <argument>
	* : The name of the SVG file.
	*
	* ## EXAMPLES
	*
	*     wp icons add --name=cool-name icon-file.svg
	*
	* @when after_wp_load
	*/
	public function add($args, $assoc_args) {

		// Get parameters
		$input_file = $args[0];
		$requested_name = \WP_CLI\Utils\get_flag_value($assoc_args, 'name');
		$overwrite = \WP_CLI\Utils\get_flag_value($assoc_args, 'overwrite');

		// Check if file exists
		if (!file_exists($input_file)) {
			return \WP_CLI::error('file "'.$input_file.'" not found.');
		}

		// Build the name from file name
		$name = $requested_name;
		if ($name === null) {
			$name = basename($input_file);
			$name = strtolower($name);
			$name = preg_replace('/\.svg$/', 		'', 	$name);
			$name = preg_replace('/[ \t_\.]/', 		'-', 	$name);
			$name = preg_replace('/-+/', 			'-', 	$name);
			$name = preg_replace('/[^a-z0-9\-]/', 	'', 	$name);
		}

		// We have a name, check it.
		if (!preg_match('/^[a-z0-9\-]+$/', $name)) {
			return \WP_CLI::error('only lowercase letters, dashes and digits are allowed in the name.');
		}
		$page = get_page_by_title($name, OBJECT, 'icons-for-cp');
		if ($page !== null && $overwrite === null) {
			return \WP_CLI::error('icon "'.$name.'" already exists');
		}

		// Create the post
		$svg = file_get_contents($input_file);
		if ($svg === false) {
			return \WP_CLI::error('error reading "'.$input_file.'".');
		}
		$id = (($page !== null) && ($overwrite === true)) ? $page->ID : 0;
		$post = [
			'ID'			=> $id,
			'post_title'	=> $name,
			'post_content'	=> $svg,
			'post_status'	=> 'publish',
			'post_type'		=> 'icons-for-cp',
		];
		$status = wp_insert_post($post, true);
		if (is_wp_error($status)) {
			return \WP_CLI::error('WP error: "'.$status->get_error_message());
		}

		return \WP_CLI::success('Icon '.$name.' added successfully with ID='.$status.'.');

	}

}

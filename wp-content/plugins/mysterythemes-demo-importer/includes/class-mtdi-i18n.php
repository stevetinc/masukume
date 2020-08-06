<?php
defined('ABSPATH') or die("No script kiddies please!");
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://mysterythemes.com/
 * @since      1.0.0
 *
 * @package    Mystery Themes Demo Importer
 * @subpackage /includes
 */

class MTDI_i18n {

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'mysterythemes-demo-importer',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}

}

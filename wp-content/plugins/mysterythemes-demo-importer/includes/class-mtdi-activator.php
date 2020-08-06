<?php
defined('ABSPATH') or die("No script kiddies please!");
/**
 * Fired during plugin activation
 *
 * @link       https://mysterythemes.com/
 * @since      1.0.0
 *
 * @package    Mystery Themes Demo Importer
 * @subpackage /includes
 */
if( !class_exists( 'MTDI_Activator' ) ) :
	class MTDI_Activator {

		/**
		 * Tasks runs at the time of plugin activation.
		 *
		 * @since    1.0.0
		 */
		public static function activate() {
			$plugin_data = get_plugin_data( MTDI_PLUGIN_FILE_DIR );
			update_option( 'mtdi_import_version', $plugin_data['Version'] );
		}

	}
endif;
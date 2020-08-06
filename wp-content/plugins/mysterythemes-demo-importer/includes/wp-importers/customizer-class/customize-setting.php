<?php
defined('ABSPATH') or die("No script kiddies please!");
/**
 * Customizer Demo Importer Setting class
 * 
 * @link https://mysterythemes.com
 * @since 1.0.0
 *
 * @package Mystery Themes Demo Importer
 * @subpackage /includes/wp-importers/customizer-class
 * 
 */

final class MTDI_Customizer_Setting extends WP_Customize_Setting {

	/**
	 * Import an option value for this setting.
	 *
	 * @param mixed $value The value to update.
	 */
	public function import( $value ) {
		$this->update( $value );
	}
}

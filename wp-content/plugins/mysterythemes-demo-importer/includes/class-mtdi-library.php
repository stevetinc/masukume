<?php
defined('ABSPATH') or die("No script kiddies please!");

/**
 * The file that defines the library plugin class
 *
 * @package    Mystery Themes Demo Importer
 * @subpackage /includes
 */
if ( ! class_exists( 'MTDI_Library' ) ) :

	class MTDI_Library {

		/**
		 * Retrieve the version number of the plugin.
		 *
		 * @since     1.0.0
		 * @return    string    The version number of the plugin.
		 */
		public function get_version() {
			$this->version = MTDI_VERSION;
			return $this->version;
		}

		/**
		 * Retrieve the full information about activate theme
		 *
		 * @since     1.0.0
		 * @return    array
		 */
		public function check_activate_theme_data() {

			$theme 					= wp_get_theme();
			$this->theme_name 		= $theme->Name;
			$this->theme_version 	= $theme->Version;
			$this->theme_author 	= wp_strip_all_tags( $theme->Author );
			$this->theme_desc 		= $theme->Description;
			$data_theme = array(
				'theme'			=> $this->theme_name,
				'version'		=> $this->theme_version,
				'author'		=> $this->theme_author,
				'description'	=> $this->theme_desc,
			);
			return $data_theme;
		}

		/**
		 * Retrieve the json data for activated theme
		 *
		 * @since     1.0.0
		 * @return    string JSON
		 */
		public function retrieve_demo_by_activatetheme( $activated_theme ){
			if ( strpos( $activated_theme, 'pro' ) !== false ) {
				$activated_theme = str_replace( '-pro', '', $activated_theme );
			} else if( strpos( $activated_theme, '-lite' ) !== false ) {
				$activated_theme = str_replace( '-lite', '', $activated_theme );
			}

			if( $activated_theme == 'swipewp' ) $activated_theme = esc_html( 'swipe' );
			$all_json_data 	= array();
			$mt_demo_config_file_url  		= 'https://demo.mysterythemes.com/themes-demo-pack/'.esc_html( $activated_theme ).'/demo.json';
			$mt_demo_config_file = apply_filters( 'mtdi_custom_json_config_path', esc_url( $mt_demo_config_file_url ) );
			$all_json_data 	= MTDI_Library::get_remote_data( $mt_demo_config_file );
			if ( is_wp_error( $all_json_data ) ) {
				return $all_json_data;
			}
			$all_json_data 	= json_decode( $all_json_data , true );
			
			return apply_filters( 'mtdi_all_json_demo_data', $all_json_data );
		}

		/**
		 * Gets and returns url body using wp_remote_get
		 *
		 * @since 1.0.0
		 */
		public static function get_remote_data( $url ) {

			// Get data
			$response = wp_remote_get( $url );

			// Check for errors
			if ( is_wp_error( $response ) or ( wp_remote_retrieve_response_code( $response ) != 200 ) ) {
				return false;
			}

			// Get remote body val
			$body = wp_remote_retrieve_body( $response );

			// Return data
			if ( ! empty( $body ) ) {
				return $body;
			} else {
				return false;
			}
		}
	}
	
endif;
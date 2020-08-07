<?php

/**
 * Container class for frontend notice handling
 *
 * @package WordPress
 * @subpackage Advanced Ads Plugin
 * @since 1.16
 *
 * related scripts / functions
 * @todo build interface or parent class to share with other notice management in Advanced Ads, e.g., Ad Health Notices
 *
 */
class Advanced_Ads_Frontend_Notices {

	/**
	 * Instance of this class.
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Options
	 *
	 * @var    array
	 */
	protected $options;

	/**
	 * All detected notices
	 *
	 * @var    array
	 */
	public $notices = array();

	/**
	 * Advanced_Ads_Ad_Health_Notices constructor.
	 */
	public function __construct() {

		// failsafe for there were some reports of 502 errors.
		if ( 1 < did_action( 'plugins_loaded' ) ) {
			return;
		}

	}

	/**
	 * Return an instance of this class.
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Updating an existing notice or add it, if it doesn’t exist, yet
	 *
	 * @param string $notice_key notice key to be added to the notice array.
	 * @param array  $atts additional attributes.
	 *
	 * attributes:
	 * - append_text – text added to the default message
	 */
	public function update( $notice_key, $atts = array() ) {

		// check if the notice already exists.
		$notice_key     = esc_attr( $notice_key );
		$options_before = $options = $this->options();

		// load notices from "queue".
		$notices = isset( $options['notices'] ) ? $options['notices'] : array();

		// check if notice_key was already saved, this prevents the same notice from showing up in different forms.
		if ( ! isset( $notices[ $notice_key ] ) ) {
			$notices[ $notice_key ] = array();
		} else {
			// add `closed` marker, if given.
			if ( ! empty( $atts['closed'] ) ) {
				$notices[ $notice_key ]['closed'] = absint( $atts['closed'] );
			}
		}

		// update db.
		$options['notices'] = $notices;

		// update db if changed.
		if ( $options_before !== $options ) {
			$this->update_options( $options );
		}

	}

	/**
	 * Return notices option from DB
	 *
	 * @return array $options
	 */
	public function options() {
		if ( ! isset( $this->options ) ) {
			$this->options = get_option( ADVADS_SLUG . '-frontend-notices', array() );
		}
		if ( ! is_array( $this->options ) ) {
			$this->options = array();
		}

		return $this->options;
	}

	/**
	 * Update notice options
	 *
	 * @param array $options new options.
	 */
	public function update_options( array $options ) {
		// do not allow to clear options.
		if ( array() === $options ) {
			return;
		}

		$this->options = $options;
		update_option( ADVADS_SLUG . '-frontend-notices', $options );
	}
}

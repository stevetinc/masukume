<?php
/**
 * Advanced Ads Ad.
 *
 * @package   Advanced_Ads_Ad
 * @author    Thomas Maier <support@wpadvancedads.com>
 * @license   GPL-2.0+
 * @link      https://wpadvancedads.com
 * @copyright 2013-2020 Thomas Maier, Advanced Ads GmbH
 */

/**
 * An ad object
 *
 * @package Advanced_Ads_Ad
 * @author  Thomas Maier <support@wpadvancedads.com>
 * @deprecated since version 1.5.3 (May 6th 2015)
 *  might still be needed if some old add-ons are running somewhere
 */
if ( ! class_exists( 'Advads_Ad', false ) ) {
	class Advads_Ad extends Advanced_Ads_Ad {

	}
}

/**
 * An ad object
 *
 * @package Advanced_Ads_Ad
 * @author  Thomas Maier <support@wpadvancedads.com>
 */
class Advanced_Ads_Ad {

	/**
	 * Id of the post type for this ad
	 *
	 * @var int $id
	 */
	public $id = 0;

	/**
	 * True, if this is an Advanced Ads Ad post type
	 *
	 * @var bool $is_ad
	 */
	public $is_ad = false;

	/**
	 * Ad type
	 *
	 * @var string $type ad type.
	 */
	public $type = 'content';

	/**
	 * Ad width
	 *
	 * @var int $width width of the ad.
	 */
	public $width = 0;

	/**
	 * Target url
	 *
	 * @var string $url ad URL parameter.
	 */
	public $url = '';

	/**
	 * Ad height
	 *
	 * @var int $height height of the ad.
	 */
	public $height = 0;

	/**
	 * Object of current ad type
	 *
	 * @var object $type_obj object of the current ad type.
	 */
	protected $type_obj;

	/**
	 * Content of the ad
	 *
	 * Only needed for ad types using the post content field
	 *
	 * @var string $content content of the ad.
	 */
	public $content = '';

	/**
	 * Conditions of the ad display
	 *
	 * @var array $conditions display and visitor conditions.
	 */
	public $conditions = array();

	/**
	 * Status of the ad (e.g. publish, pending)
	 *
	 * @var string $status status of the ad.
	 */
	public $status = '';

	/**
	 * Array with meta field options aka parameters
	 *
	 * @var array $options ad options.
	 */
	protected $options = array();

	/**
	 * Name of the meta field to save options to
	 *
	 * @var string $options_meta_field under which post meta key the ad options are stored.
	 */
	public static $options_meta_field = 'advanced_ads_ad_options';

	/**
	 * Additional arguments set when ad is loaded, overwrites or extends options
	 *
	 * @var array $args
	 */
	public $args = array();

	/**
	 * Multidimensional array contains information about the wrapper
	 * Each possible html attribute is an array with possible multiple elements
	 *
	 * @var array $wrapper options of the ad wrapper.
	 */
	public $wrapper = array();

	/**
	 * Will the ad be tracked?
	 *
	 * @var mixed $global_output
	 */
	public $global_output;

	/**
	 * Title of the ad
	 *
	 * @var string $title
	 */
	public $title = '';

	/**
	 * Displayed above the ad.
	 *
	 * @var string $label ad label.
	 */
	protected $label = '';

	/**
	 * Init ad object
	 *
	 * @param int   $id id of the ad.
	 * @param array $args additional arguments.
	 */
	public function __construct( $id, $args = array() ) {
		$id         = absint( $id );
		$this->id   = $id;
		$this->args = is_array( $args ) ? $args : array();

		// whether the ad will be tracked.
		$this->global_output = isset( $this->args['global_output'] ) ? (bool) $this->args['global_output'] : true;

		if ( ! empty( $id ) ) {
			$this->load( $id );
		}

		// dynamically add sanitize filters for condition types.
		$_types = array();
		// -TODO use model
		$advanced_ads_ad_conditions = Advanced_Ads::get_ad_conditions();
		foreach ( $advanced_ads_ad_conditions as $_condition ) {
			// add unique.
			$_types[ $_condition['type'] ] = false;
		}
		// iterate types.
		foreach ( array_keys( $_types ) as $_type ) {
			// -TODO might be faster to use __call() method or isset()-test class method array
			$method_name = 'sanitize_condition_' . $_type;
			if ( method_exists( $this, $method_name ) ) {
				add_filter( 'advanced-ads-sanitize-condition-' . $_type, array( $this, $method_name ), 10, 1 );
			} elseif ( function_exists( 'advads_sanitize_condition_' . $_type ) ) {
				// check for public function to sanitize this.
				add_filter( 'advanced-ads-sanitize-condition-' . $_type, 'advads_sanitize_condition_' . $_type, 10, 1 );
			}
		}
	}

	/**
	 * Load an ad object by id based on its ad type
	 *
	 * @param int $id ad id.
	 *
	 * @return bool false if ad could not be loaded.
	 */
	private function load( $id = 0 ) {

		$_data = get_post( $id );
		if ( null === $_data ) {
			return false;
		}

		// return, if not an ad.
		if ( Advanced_Ads::POST_TYPE_SLUG !== $_data->post_type ) {
			return false;
		} else {
			$this->is_ad = true;
		}

		$this->type  = $this->options( 'type' );
		$this->title = $_data->post_title;
		/* load ad type object */
		$types = Advanced_Ads::get_instance()->ad_types;
		if ( isset( $types[ $this->type ] ) ) {
			$this->type_obj = $types[ $this->type ];
		} else {
			$this->type_obj = new Advanced_Ads_Ad_Type_Abstract();
		}
		$this->url                  = $this->get_url();
		$this->width                = absint( $this->options( 'width' ) );
		$this->height               = absint( $this->options( 'height' ) );
		$this->conditions           = $this->options( 'conditions' );
		$this->description          = $this->options( 'description' );
		$this->output               = $this->options( 'output' );
		$this->status               = $_data->post_status;
		$this->expiry_date          = $this->options( 'expiry_date' );
		$this->is_head_placement    = isset( $this->args['placement_type'] ) && 'header' === $this->args['placement_type'];
		$this->args['is_top_level'] = ! isset( $this->args['is_top_level'] );

		// load content based on ad type.
		$this->content = $this->type_obj->load_content( $_data );

		if ( ! $this->is_head_placement ) {
			$this->maybe_create_label();
			$this->wrapper = $this->load_wrapper_options();

			// set wrapper conditions.
			$this->wrapper = apply_filters( 'advanced-ads-set-wrapper', $this->wrapper, $this );
			// add unique wrapper id.
			if ( is_array( $this->wrapper )
				 && array() !== $this->wrapper
				 && ! isset( $this->wrapper['id'] ) ) {
				// create unique id if not yet given.
				$this->wrapper['id'] = $this->create_wrapper_id();
			}
		}
	}

	/**
	 * Get options from meta field and return specific field
	 *
	 * @param string $field post meta key to be returned.
	 * @param array  $default default options.
	 *
	 * @return mixed meta field content
	 * @todo check against default values
	 */
	public function options( $field = '', $default = null ) {
		// retrieve options, if not given yet
		// -TODO may execute multiple times (if empty); bad design and risk to access unintialised data with direct access to $this->options property.
		if ( array() === $this->options ) {
			// may return false.
			$meta = get_post_meta( $this->id, self::$options_meta_field, true );
			if ( $meta ) {
				// merge meta with arguments given on ad load.
				$this->options = Advanced_Ads_Utils::merge_deep_array( array( $meta, $this->args ) );
			} else {
				// load arguments given on ad load.
				$this->options = $this->args;
			}

			if ( isset( $this->options['change-ad'] ) ) {
				// some options was provided by the user.
				$this->options = Advanced_Ads_Utils::merge_deep_array(
					array(
						$this->options,
						$this->options['change-ad'],
					)
				);
			}
		}

		// return specific option.
		if ( '' !== $field ) {
			if ( isset( $this->options[ $field ] ) ) {
				return $this->options[ $field ];
			}
		} else { // return all options.
			if ( ! empty( $this->options ) ) {
				return $this->options;
			}
		}

		return $default;
	}

	/**
	 * Set an option of the ad
	 *
	 * @param string $option name of the option.
	 * @param mixed  $value value of the option.
	 *
	 * @since 1.1.0
	 */
	public function set_option( $option = '', $value = '' ) {
		if ( '' === $option ) {
			return;
		}

		// get current options.
		$options = $this->options();

		// set options.
		$options[ $option ] = $value;

		// save options.
		$this->options = $options;

	}


	/**
	 * Return ad content for frontend output
	 *
	 * @param array $output_options output options.
	 *
	 * @return string $output ad output
	 * @since 1.0.0
	 */
	public function output( $output_options = array() ) {
		if ( ! $this->is_ad ) {
			return '';
		}

		$this->global_output             = isset( $output_options['global_output'] ) ? $output_options['global_output'] : $this->global_output;
		$output_options['global_output'] = $this->global_output;

		// switch between normal and debug mode.
		// check if debug output should only be displayed to admins.
		$user_can_manage_ads = current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) );
		if ( isset( $this->output['debugmode'] )
			 && ( $user_can_manage_ads || ( ! $user_can_manage_ads && ! defined( 'ADVANCED_ADS_AD_DEBUG_FOR_ADMIN_ONLY' ) ) ) ) {
			$debug = new Advanced_Ads_Ad_Debug();

			return $debug->prepare_debug_output( $this );
		} else {
			$output = $this->prepare_frontend_output();
		}

		// add the ad to the global output array.
		$advads = Advanced_Ads::get_instance();
		if ( $output_options['global_output'] ) {
			$new_ad = array(
				'type'   => 'ad',
				'id'     => $this->id,
				'title'  => $this->title,
				'output' => $output,
			);
			// if ( method_exists( 'Advanced_Ads_Tracking_Plugin' , 'check_ad_tracking_enabled' ) ) {
			// if ( class_exists( 'Advanced_Ads_Tracking_Plugin', false ) ) {
			if ( defined( 'AAT_VERSION' ) && - 1 < version_compare( AAT_VERSION, '1.4.2' ) ) {

				$new_ad['tracking_enabled'] = Advanced_Ads_Tracking_Plugin::get_instance()->check_ad_tracking_enabled( $this );

				$tracking_options = Advanced_Ads_Tracking_Plugin::get_instance()->options();
				if ( isset( $tracking_options['method'] ) && 'frontend' === $tracking_options['method'] && isset( $this->output['placement_id'] ) ) {
					$new_ad['placement_id'] = $this->output['placement_id'];
				}
			}

			$advads->current_ads[] = $new_ad;
		}

		// action when output is created.
		do_action( 'advanced-ads-output', $this, $output, $output_options );

		return apply_filters( 'advanced-ads-output-final', $output, $this, $output_options );
	}

	/**
	 * Check if the ad can be displayed in frontend due to its own conditions
	 *
	 * @param array $check_options check options.
	 *
	 * @return bool $can_display true if can be displayed in frontend
	 * @since 1.0.0
	 */
	public function can_display( $check_options = array() ) {
		$check_options = wp_parse_args(
			$check_options,
			array(
				'passive_cache_busting' => false,
				'ignore_debugmode'      => false,
			)
		);

		// prevent ad to show up through wp_head, if this is not a header placement.
		if ( doing_action( 'wp_head' ) && isset( $this->options['placement_type'] ) && 'header' !== $this->options['placement_type']
			&& ! Advanced_Ads_Compatibility::can_inject_during_wp_head() ) {
			return false;
		}

		// Check If the current ad is requested using a shortcode placed in the content of the current ad.
		if ( isset( $this->options['shortcode_ad_id'] ) && (int) $this->options['shortcode_ad_id'] === $this->id ) {
			return false;
		}

		// force ad display if debug mode is enabled.
		if ( isset( $this->output['debugmode'] ) && ! $check_options['ignore_debugmode'] ) {
			return true;
		}

		if ( ! $check_options['passive_cache_busting'] ) {
			// don’t display ads that are not published or private for users not logged in.
			if ( 'publish' !== $this->status && ! ( 'private' === $this->status && is_user_logged_in() ) ) {
				return false;
			}

			if ( ! $this->can_display_by_visitor() || ! $this->can_display_by_expiry_date() ) {
				return false;
			}
		} else {
			if ( 'publish' !== $this->status || ! $this->can_display_by_expiry_date() ) {
				return false;
			}
		}

		// add own conditions to flag output as possible or not.
		$can_display = apply_filters( 'advanced-ads-can-display', true, $this, $check_options );

		return $can_display;
	}

	/**
	 * Check visitor conditions
	 *
	 * @return bool $can_display true if can be displayed in frontend based on visitor settings
	 * @since 1.1.0
	 */
	public function can_display_by_visitor() {
		if ( ! empty( $this->options['wp_the_query']['is_feed'] ) ) {
			return true;
		}

		// check old "visitor" and new "visitors" conditions.
		if ( ( empty( $this->options['visitors'] ) ||
			   ! is_array( $this->options['visitors'] ) )
			 && ( empty( $this->options['visitor'] ) ||
				  ! is_array( $this->options['visitor'] )
			 ) ) {
			return true;
		}

		if ( isset( $this->options['visitors'] ) && is_array( $this->options['visitors'] ) ) {

			$visitor_conditions = $this->options['visitors'];

			$last_result = false;
			$length      = count( $visitor_conditions );

			for ( $i = 0; $i < $length; ++ $i ) {
				$_condition = current( $visitor_conditions );
				// ignore OR if last result was true.
				if ( $last_result && isset( $_condition['connector'] ) && 'or' === $_condition['connector'] ) {
					next( $visitor_conditions );
					continue;
				}
				$result      = Advanced_Ads_Visitor_Conditions::frontend_check( $_condition, $this );
				$last_result = $result;
				if ( ! $result ) {
					// return false only, if the next condition doesn’t have an OR operator.
					$next = next( $visitor_conditions );
					if ( ! isset( $next['connector'] ) || 'or' !== $next['connector'] ) {
						return false;
					}
				} else {
					next( $visitor_conditions );
				}
			}
		}

		/**
		 * "old" visitor conditions
		 *
		 * @deprecated since version 1.5.4
		 */

		if ( empty( $this->options['visitor'] ) ||
			 ! is_array( $this->options['visitor'] ) ) {
			return true;
		}
		$visitor_conditions = $this->options( 'visitor' );

		// check mobile condition.
		if ( isset( $visitor_conditions['mobile'] ) ) {
			switch ( $visitor_conditions['mobile'] ) {
				case 'only':
					if ( ! wp_is_mobile() ) {
						return false;
					}
					break;
				case 'no':
					if ( wp_is_mobile() ) {
						return false;
					}
					break;
			}
		}

		return true;
	}

	/**
	 * Check expiry date
	 *
	 * @return bool $can_display true if can be displayed in frontend based on expiry date
	 * @since 1.3.15
	 */
	public function can_display_by_expiry_date() {

		// if expiry_date is not set, null is returned.
		$ad_expiry_date = (int) $this->options( 'expiry_date' );

		if ( $ad_expiry_date <= 0 || $ad_expiry_date > time() ) {
			return true;
		}

		// set status to 'draft' if the ad is expired.
		if ( 'draft' !== $this->status ) {
			// removing the kses filters here so that expiring ads don’t lose HTML or other code.
			kses_remove_filters();
			wp_update_post(
				array(
					'ID'          => $this->id,
					'post_status' => 'draft',
				)
			);
			kses_init_filters();
			/**
			 * Run when an ad expires
			 */
			do_action( 'advanced-ads-ad-expired', $this->id, $this );
		}

		return false;
	}

	/**
	 * Save an ad to the database
	 * takes values from the current state
	 */
	public function save() {
		global $wpdb;

		// remove slashes from content.
		$this->content = $this->prepare_content_to_save();

		$where = array( 'ID' => $this->id );
		$wpdb->update( $wpdb->posts, array( 'post_content' => $this->content ), $where );

		// clean post from object cache.
		clean_post_cache( $this->id );

		// sanitize conditions
		// see sanitize_conditions function for example on using this filter.
		$conditions = self::sanitize_conditions_on_save( $this->conditions );

		// save other options to post meta field.
		$options = $this->options();

		$options['type'] = $this->type;
		$options['url']  = $this->url;
		// Inform the tracking add-on about the new url.
		unset( $options['tracking']['link'] );
		$options['width']       = $this->width;
		$options['height']      = $this->height;
		$options['conditions']  = $conditions;
		$options['expiry_date'] = $this->expiry_date;
		$options['description'] = $this->description;

		// sanitize container ID option.
		$options['output']['wrapper-id'] = isset( $options['output']['wrapper-id'] ) ? sanitize_key( $options['output']['wrapper-id'] ) : '';

		// sanitize options before saving
		$options = $this->prepare_options_to_save( $options );

		// filter to manipulate options or add more to be saved.
		$options = apply_filters( 'advanced-ads-save-options', $options, $this );

		update_post_meta( $this->id, self::$options_meta_field, $options );
	}

	/**
	 * Save ad options.
	 * Meant to be used from the outside of an ad.
	 *
	 * @param int   $ad_id post ID of the ad.
	 * @param array $options ad options.
	 */
	public static function save_ad_options( $ad_id, array $options ) {

		// don’t allow to clear options by accident.
		if ( array() === $options ) {
			return;
		}

		update_post_meta( $ad_id, self::$options_meta_field, $options );
	}

	/**
	 * Native filter for content field before being saved
	 *
	 * @return string $content ad content
	 */
	public function prepare_content_to_save() {

		$content = $this->content;

		// load ad type specific parameter filter
		// @todo this is just a hotfix for type_obj not set, yet the cause is still unknown. Likely when the ad is first saved
		if ( is_object( $this->type_obj ) ) {
			$content = $this->type_obj->sanitize_content( $content );
		}
		// apply a custom filter by ad type.
		$content = apply_filters( 'advanced-ads-pre-ad-save-' . $this->type, $content );

		return $content;
	}

	/**
	 * Sanitize ad options before being saved
	 * allows some ad types to sanitize certain values
	 *
	 * @param array $options ad options.
	 * @return array sanitized options.
	 */
	public function prepare_options_to_save( $options ) {

		// load ad type specific sanitize function.
		// we need to load the ad type object if not set (e.g., when the ad is saved for the first time)
		if ( ! is_object( $this->type_obj ) || ! $this->type_obj->ID ) {
			$types = Advanced_Ads::get_instance()->ad_types;
			if ( isset( $types[ $this->type ] ) ) {
				$this->type_obj = $types[ $this->type ];
			}
		}

		$options = $this->type_obj->sanitize_options( $options );

		return $options;
	}

	/**
	 * Prepare ads output
	 *
	 * @return string.
	 */
	public function prepare_frontend_output() {
		$options = $this->options();

		if ( isset( $options['change-ad']['content'] ) ) {
			// output was provided by the user.
			$output = $options['change-ad']['content'];
		} else {
			// load ad type specific content filter.
			$output = $this->type_obj->prepare_output( $this );
		}

		// don’t deliver anything, if main ad content is empty.
		if ( empty( $output ) ) {
			return;
		}

		if ( ! $this->is_head_placement ) {
			// filter to manipulate the output before the wrapper is added
			$output = apply_filters( 'advanced-ads-output-inside-wrapper', $output, $this );

			if ( $this->label ) {
				$output = $this->label . $output;
			}

			// build wrapper around the ad.
			$output = $this->add_wrapper( $output );

			// add a clearfix, if set.
			if ( ( isset( $this->output['clearfix'] ) && $this->output['clearfix'] )
				 || ( ! empty( $this->args['is_top_level'] ) && ! empty( $this->args['placement_clearfix'] ) ) ) {
				$output .= '<br style="clear: both; display: block; float: none;"/>';
			}
		}

		// apply a custom filter by ad type.
		$output = apply_filters( 'advanced-ads-ad-output', $output, $this );

		return $output;
	}

	/**
	 * Sanitize ad display conditions when saving the ad
	 *
	 * @param array $conditions conditions array send via the dashboard form for an ad.
	 *
	 * @return array with sanitized conditions
	 * @since 1.0.0
	 */
	public function sanitize_conditions_on_save( $conditions = array() ) {

		global $advanced_ads_ad_conditions;

		if ( ! is_array( $conditions ) || array() === $conditions ) {
			return array();
		}

		foreach ( $conditions as $_key => $_condition ) {
			if ( 'postids' === $_key ) {
				// sanitize single post conditions
				if ( empty( $_condition['ids'] ) ) { // remove, if empty.
					$_condition['include'] = array();
					$_condition['exclude'] = array();
				} elseif ( isset( $_condition['method'] ) ) {
					switch ( $_condition['method'] ) {
						case 'include':
							$_condition['include'] = $_condition['ids'];
							$_condition['exclude'] = array();
							break;
						case 'exclude':
							$_condition['include'] = array();
							$_condition['exclude'] = $_condition['ids'];
							break;
					}
				}
			} else {
				if ( ! is_array( $_condition ) ) {
					$_condition = trim( $_condition );
				}
				if ( $_condition == '' ) {
					$conditions[ $_key ] = $_condition;
					continue;
				}
			}
			$type = ! empty( $advanced_ads_ad_conditions[ $_key ]['type'] ) ? $advanced_ads_ad_conditions[ $_key ]['type'] : 0;
			if ( empty( $type ) ) {
				continue;
			}

			// dynamically apply filters for each condition used.
			$conditions[ $_key ] = apply_filters( 'advanced-ads-sanitize-condition-' . $type, $_condition );
		}

		return $conditions;
	}

	/**
	 * Sanitize id input field(s) for pattern /1,2,3,4/
	 *
	 * @param mixed $cond input string/array.
	 *
	 * @return array/string $cond sanitized string/array
	 */
	public static function sanitize_condition_idfield( $cond = '' ) {
		// strip anything that is not comma or number.

		if ( is_array( $cond ) ) {
			foreach ( $cond as $_key => $_cond ) {
				$cond[ $_key ] = preg_replace( '#[^0-9,]#', '', $_cond );
			}
		} else {
			$cond = preg_replace( '#[^0-9,]#', '', $cond );
		}

		return $cond;
	}

	/**
	 * Sanitize radio input field
	 *
	 * @param string $string input string.
	 *
	 * @return string $string sanitized string.
	 */
	public static function sanitize_condition_radio( $string = '' ) {
		// only allow 0, 1 and empty.
		return preg_replace( '#[^01]#', '', $string );
	}

	/**
	 * Sanitize comma seperated text input field
	 *
	 * @param mixed $cond input string/array.
	 *
	 * @return array/string $cond sanitized string/array.
	 */
	public static function sanitize_condition_textvalues( $cond = '' ) {
		// strip anything that is not comma, alphanumeric, minus and underscore.
		if ( is_array( $cond ) ) {
			foreach ( $cond as $_key => $_cond ) {
				$cond[ $_key ] = preg_replace( '#[^0-9,A-Za-z-_]#', '', $_cond );
			}
		} else {
			$cond = preg_replace( '#[^0-9,A-Za-z-_]#', '', $cond );
		}

		return $cond;
	}

	/**
	 * Load wrapper options set with the ad
	 *
	 * @return array $wrapper options array ready to be use in add_wrapper() function.
	 * @since 1.3
	 */
	protected function load_wrapper_options() {
		$wrapper = array();

		$position          = ! empty( $this->output['position'] ) ? $this->output['position'] : '';
		$use_placement_pos = false;

		if ( $this->args['is_top_level'] ) {
			if ( isset( $this->output['class'] ) && is_array( $this->output['class'] ) ) {
				$wrapper['class'] = $this->output['class'];
			}
			if ( ! empty( $this->args['placement_position'] ) ) {
				// If not group, Set placement position instead of ad position.
				$use_placement_pos = true;
				$position          = $this->args['placement_position'];
			}
		}

		switch ( $position ) {
			case 'left':
				$wrapper['style']['float'] = 'left';
				break;
			case 'right':
				$wrapper['style']['float'] = 'right';
				break;
			case 'center':
				if ( ! empty( $this->output['add_wrapper_sizes'] ) ) {
					$wrapper['style']['margin-left']  = 'auto';
					$wrapper['style']['margin-right'] = 'auto';

					if ( $use_placement_pos ) {
						$wrapper['style']['text-align'] = 'center';
					}
				} else {
					$wrapper['style']['text-align'] = 'center';
				}

				// add css rule after wrapper to center the ad.
				break;
			case 'clearfix':
				$wrapper['style']['clear'] = 'both';
				break;
		}

		// add manual classes.
		if ( isset( $this->output['wrapper-class'] ) && '' !== $this->output['wrapper-class'] ) {
			$classes = explode( ' ', $this->output['wrapper-class'] );

			foreach ( $classes as $_class ) {
				$wrapper['class'][] = sanitize_text_field( $_class );
			}
		}

		if ( ! empty( $this->output['margin']['top'] ) ) {
			$wrapper['style']['margin-top'] = intval( $this->output['margin']['top'] ) . 'px';
		}
		if ( ! empty( $this->output['margin']['right'] ) ) {
			$wrapper['style']['margin-right'] = intval( $this->output['margin']['right'] ) . 'px';
		}
		if ( ! empty( $this->output['margin']['bottom'] ) ) {
			$wrapper['style']['margin-bottom'] = intval( $this->output['margin']['bottom'] ) . 'px';
		}
		if ( ! empty( $this->output['margin']['left'] ) ) {
			$wrapper['style']['margin-left'] = intval( $this->output['margin']['left'] ) . 'px';
		}

		if ( ! empty( $this->output['add_wrapper_sizes'] ) ) {
			$wrapper['style']['width']  = intval( $this->width ) . 'px';
			$wrapper['style']['height'] = intval( $this->height ) . 'px';
		}

		if ( ! empty( $this->output['clearfix_before'] ) ) {
			$wrapper['style']['clear'] = 'both';
		}

		return $wrapper;
	}

	/**
	 * Add a wrapper arount the ad content if wrapper information are given
	 *
	 * @param string $ad_content content of the ad.
	 *
	 * @return string $wrapper ad within the wrapper
	 * @since 1.1.4
	 */
	protected function add_wrapper( $ad_content = '' ) {
		$wrapper_options = apply_filters( 'advanced-ads-output-wrapper-options', $this->wrapper, $this );

		if ( ( ! isset( $this->output['wrapper-id'] ) || '' === $this->output['wrapper-id'] )
			 && array() === $wrapper_options || ! is_array( $wrapper_options ) ) {
			return $ad_content;
		}

		// create unique id if not yet given.
		if ( empty( $wrapper_options['id'] ) ) {
			$wrapper_options['id'] = $this->create_wrapper_id();
			$this->wrapper['id']   = $wrapper_options['id'];
		}

		// add edit button for users with the appropriate rights.
		if ( ! defined( 'ADVANCED_ADS_DISABLE_EDIT_BAR' ) && current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ) ) ) {
			ob_start();
			include ADVADS_BASE_PATH . 'public/views/ad-edit-bar.php';
			$ad_content = ob_get_clean() . $ad_content;
		}

		// build the box
		$wrapper  = '<div' . Advanced_Ads_Utils::build_html_attributes( $wrapper_options ) . '>';
		$wrapper .= apply_filters( 'advanced-ads-output-wrapper-before-content', '', $this );
		$wrapper .= $ad_content;
		$wrapper .= apply_filters( 'advanced-ads-output-wrapper-after-content', '', $this );
		$wrapper .= '</div>';

		return $wrapper;
	}

	/**
	 * Create a random wrapper id
	 *
	 * @return string $id random id string
	 * @since 1.1.4
	 */
	private function create_wrapper_id() {

		if ( isset( $this->output['wrapper-id'] ) ) {
			$id = sanitize_key( $this->output['wrapper-id'] );
			if ( '' !== $id ) {
				return $id;
			}
		}

		$prefix = Advanced_Ads_Plugin::get_instance()->get_frontend_prefix();

		return $prefix . mt_rand();
	}

	/**
	 * Create an "Advertisement" label if conditions are met.
	 */
	public function maybe_create_label() {
		$placement_state = isset( $this->args['ad_label'] ) ? $this->args['ad_label'] : 'default';

		$label = Advanced_Ads::get_instance()->get_label( $placement_state );
		if ( 'group' !== $this->type && $label ) {
			$this->label = $label;
		}
	}

	/**
	 * Get the ad url.
	 *
	 * @return string
	 */
	private function get_url() {
		$this->url = $this->options( 'url' );

		// If the tracking add-on is not active.
		if ( ! defined( 'AAT_VERSION' ) ) {
			global $pagenow;
			// If this is not the ad edit page.
			if ( 'post.php' !== $pagenow && 'post-new.php' !== $pagenow ) {
				// Remove placeholders.
				$this->url = str_replace(
					array(
						'[POST_ID]',
						'[POST_SLUG]',
						'[CAT_SLUG]',
						'[AD_ID]',
					),
					'',
					$this->url
				);
			}
		}

		return $this->url;
	}

}

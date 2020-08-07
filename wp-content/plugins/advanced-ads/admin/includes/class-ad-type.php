<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class Advanced_Ads_Admin_Ad_Type
 */
class Advanced_Ads_Admin_Ad_Type {
	/**
	 * Instance of this class.
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Post type slug
	 *
	 * @since   1.0.0
	 * @var     string
	 */
	protected $post_type = '';

	/**
	 * Register hooks function related to the ad type
	 */
	private function __construct() {
		// registering custom columns needs to work with and without DOING_AJAX.
		add_filter(
			'manage_advanced_ads_posts_columns',
			array(
				$this,
				'ad_list_columns_head',
			)
		); // extra column.
		add_filter(
			'manage_advanced_ads_posts_custom_column',
			array(
				$this,
				'ad_list_columns_content',
			),
			10,
			2
		); // extra column.
		add_filter(
			'manage_advanced_ads_posts_custom_column',
			array(
				$this,
				'ad_list_columns_timing',
			),
			10,
			2
		); // extra column.
		add_filter(
			'manage_advanced_ads_posts_custom_column',
			array(
				$this,
				'ad_list_columns_shortcode',
			),
			10,
			2
		); // extra column.
		add_action(
			'restrict_manage_posts',
			array(
				$this,
				'ad_list_add_filters',
			)
		);
		add_filter(
			'default_hidden_columns',
			array(
				$this,
				'hide_ad_list_columns',
			),
			10,
			2
		); // hide the ad shortcode column by default.

		// ad updated messages.
		add_filter(
			'bulk_post_updated_messages',
			array(
				$this,
				'ad_bulk_update_messages',
			),
			10,
			2
		);

		// handling (ad) lists.
		add_filter(
			'request',
			array(
				$this,
				'ad_list_request',
			)
		); // order ads by title, not ID.
		add_action(
			'all_admin_notices',
			array(
				$this,
				'no_ads_yet_notice',
			)
		);
		// Manipulate post data when post is created.
		add_filter(
			'wp_insert_post_data',
			array(
				$this,
				'prepare_insert_post_data',
			)
		);
		// Save ads post type.
		// @source https://developer.wordpress.org/reference/hooks/save_post_post-post_type/
		add_action(
			'save_post_advanced_ads',
			array(
				$this,
				'save_ad',
			)
		);
		// delete ads post type.
		add_action(
			'delete_post',
			array(
				$this,
				'delete_ad',
			)
		);
		// on post/ad edit screen.
		add_action(
			'edit_form_top',
			array(
				$this,
				'edit_form_above_title',
			)
		);
		add_action(
			'edit_form_after_title',
			array(
				$this,
				'edit_form_below_title',
			)
		);
		add_action(
			'dbx_post_sidebar',
			array(
				$this,
				'edit_form_end',
			)
		);
		add_action(
			'post_submitbox_misc_actions',
			array(
				$this,
				'add_submit_box_meta',
			)
		);
		add_action(
			'admin_enqueue_scripts',
			array(
				$this,
				'use_code_editor',
			)
		);
		// ad updated messages.
		add_filter(
			'post_updated_messages',
			array(
				$this,
				'ad_update_messages',
			)
		);
		add_filter(
			'gettext',
			array(
				$this,
				'replace_cheating_message',
			),
			20,
			2
		);
		// things that need to know the current screen.
		add_action(
			'current_screen',
			array(
				$this,
				'run_on_ad_edit_screen',
			)
		);
		add_filter(
			'pre_wp_unique_post_slug',
			array(
				$this,
				'pre_wp_unique_post_slug',
			),
			10,
			6
		);

		$this->post_type = constant( 'Advanced_Ads::POST_TYPE_SLUG' );
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
	 * Check if an ad is not valid for 'Post Content' placement
	 *
	 * @param Advanced_Ads_Ad $ad object.
	 *
	 * @return string with error if not valid, empty string if valid
	 */
	public static function check_ad_dom_is_not_valid( Advanced_Ads_Ad $ad ) {
		$ad_content = ( isset( $ad->content ) ) ? $ad->content : '';
		if ( ! extension_loaded( 'dom' ) || ! $ad_content ) {
			return false;
		}

		$wp_charset = get_bloginfo( 'charset' );
		$ad_dom     = new DOMDocument( '1.0', $wp_charset );

		$libxml_previous_state = libxml_use_internal_errors( true );
		// clear existing errors.
		libxml_clear_errors();
		// source for this regex: http://stackoverflow.com/questions/17852537/preg-replace-only-specific-part-of-string.
		$ad_content = preg_replace( '#(document.write.+)</(.*)#', '$1<\/$2', $ad_content ); // escapes all closing
		// html tags.
		$ad_dom->loadHtml( '<!DOCTYPE html><html><meta http-equiv="Content-Type" content="text/html; charset=' . $wp_charset . '" /><body>' . $ad_content );

		$errors = '';
		foreach ( libxml_get_errors() as $_error ) {
			// continue, if there is '&' symbol, but not HTML entity.
			if ( false === stripos( $_error->message, 'htmlParseEntityRef:' ) ) {
				$errors .= print_r( $_error, true );
			}
		}

		libxml_use_internal_errors( $libxml_previous_state );

		return $errors;
	}

	/**
	 * Add heading for extra column of ads list
	 * remove the date column
	 *
	 * @param array $columns array with existing columns.
	 *
	 * @return array $new_columns
	 * @since 1.3.3
	 */
	public function ad_list_columns_head( $columns ) {
		$new_columns = array();
		if ( is_array( $columns ) ) {
			foreach ( $columns as $key => $value ) {
				$new_columns[ $key ] = $value;
				if ( 'title' === $key ) {
					$new_columns['ad_details']   = __( 'Ad Details', 'advanced-ads' );
					$new_columns['ad_timing']    = __( 'Ad Planning', 'advanced-ads' );
					$new_columns['ad_shortcode'] = __( 'Ad Shortcode', 'advanced-ads' );
				}
			}
		} else {
			$new_columns['ad_details']   = __( 'Ad Details', 'advanced-ads' );
			$new_columns['ad_timing']    = __( 'Ad Planning', 'advanced-ads' );
			$new_columns['ad_shortcode'] = __( 'Ad Shortcode', 'advanced-ads' );
		}

		// white-listed columns.
		$whitelist = apply_filters(
			'advanced-ads-ad-list-allowed-columns',
			array(
				'cb', // checkbox.
				'title',
				'ad_details',
				'ad_timing',
				'ad_shortcode',
				'taxonomy-advanced_ads_groups',
			)
		);

		// remove non-white-listed columns.
		foreach ( $new_columns as $_key => $_value ) {
			if ( ! in_array( $_key, $whitelist, true ) ) {
				unset( $new_columns[ $_key ] );
			}
		}

		return $new_columns;
	}

	/**
	 * Display ad details in ads list
	 *
	 * @param string $column_name name of the column.
	 * @param int    $ad_id id of the ad.
	 *
	 * @since 1.3.3
	 */
	public function ad_list_columns_content( $column_name, $ad_id ) {
		if ( 'ad_details' === $column_name ) {
			$ad = new Advanced_Ads_Ad( $ad_id );

			// load ad type title.
			$types = Advanced_Ads::get_instance()->ad_types;
			$type  = ( ! empty( $types[ $ad->type ]->title ) ) ? $types[ $ad->type ]->title : 0;

			// load ad size.
			$size = 0;
			if ( ! empty( $ad->width ) || ! empty( $ad->height ) ) {
				$size = sprintf( '%d x %d', $ad->width, $ad->height );
			}

			$size = apply_filters( 'advanced-ads-list-ad-size', $size, $ad );

			include ADVADS_BASE_PATH . 'admin/views/ad-list-details-column.php';
		}
	}

	/**
	 * Display ad details in ads list
	 *
	 * @param string $column_name name of the column.
	 * @param int    $ad_id id of the ad.
	 *
	 * @since 1.6.11
	 */
	public function ad_list_columns_timing( $column_name, $ad_id ) {
		if ( 'ad_timing' === $column_name ) {
			$ad = new Advanced_Ads_Ad( $ad_id );

			$expiry             = false;
			$post_future        = false;
			$post_start         = get_post_time( 'U', true, $ad->id );
			$html_classes       = 'advads-filter-timing';
			$expiry_date_format = get_option( 'date_format' ) . ', ' . get_option( 'time_format' );

			if ( isset( $ad->expiry_date ) && $ad->expiry_date ) {
				$html_classes .= ' advads-filter-any-exp-date';

				$expiry = $ad->expiry_date;
				if ( $ad->expiry_date < time() ) {
					$html_classes .= ' advads-filter-expired';
				}
			}
			if ( $post_start > time() ) {
				$post_future   = $post_start;
				$html_classes .= ' advads-filter-future';
			}

			ob_start();
			do_action_ref_array(
				'advanced-ads-ad-list-timing-column-after',
				array(
					$ad,
					&$html_classes,
				)
			);
			$content_after = ob_get_clean();

			include ADVADS_BASE_PATH . 'admin/views/ad-list-timing-column.php';
		}
	}

	/**
	 * Display ad shortcode in ads list
	 *
	 * @param string $column_name name of the column.
	 * @param int    $ad_id id of the ad.
	 *
	 * @since 1.8.2
	 */
	public function ad_list_columns_shortcode( $column_name, $ad_id ) {
		if ( 'ad_shortcode' === $column_name ) {
			$ad = new Advanced_Ads_Ad( $ad_id );

			include ADVADS_BASE_PATH . 'admin/views/ad-list-shortcode-column.php';
		}
	}

	/**
	 * Display ad shortcode in ads list
	 *
	 * @param array     $hidden an array of columns hidden by default.
	 * @param WP_Screen $screen WP_Screen object of the current screen.
	 *
	 * @return array $hidden
	 * @since 1.10.5
	 */
	public function hide_ad_list_columns( $hidden, $screen ) {

		if ( isset( $screen->id ) && 'edit-' . Advanced_Ads::POST_TYPE_SLUG === $screen->id ) {

			$hidden[] = 'ad_shortcode';

		}

		return $hidden;
	}

	/**
	 * Adds filter dropdowns before the 'Filter' button on the ad list table
	 */
	public function ad_list_add_filters() {
		$screen = get_current_screen();
		if ( ! isset( $screen->id ) || 'edit-advanced_ads' !== $screen->id ) {
			return;
		}
		include ADVADS_BASE_PATH . 'admin/views/ad-list-filters.php';
	}

	/**
	 * Edit ad bulk update messages
	 *
	 * @param array $messages existing bulk update messages.
	 * @param array $counts numbers of updated ads.
	 *
	 * @return array $messages
	 *
	 * @since 1.4.7
	 * @see wp-admin/edit.php
	 */
	public function ad_bulk_update_messages( array $messages, array $counts ) {
		$post = get_post();

		$messages[ Advanced_Ads::POST_TYPE_SLUG ] = array(
			// translators: %s is the number of ads.
			'updated'   => _n( '%s ad updated.', '%s ads updated.', $counts['updated'], 'advanced-ads' ),
			// translators: %s is the number of ads.
			'locked'    => _n( '%s ad not updated, somebody is editing it.', '%s ads not updated, somebody is editing them.', $counts['locked'], 'advanced-ads' ),
			// translators: %s is the number of ads.
			'deleted'   => _n( '%s ad permanently deleted.', '%s ads permanently deleted.', $counts['deleted'], 'advanced-ads' ),
			// translators: %s is the number of ads.
			'trashed'   => _n( '%s ad moved to the Trash.', '%s ads moved to the Trash.', $counts['trashed'], 'advanced-ads' ),
			// translators: %s is the number of ads.
			'untrashed' => _n( '%s ad restored from the Trash.', '%s ads restored from the Trash.', $counts['untrashed'], 'advanced-ads' ),
		);

		return $messages;
	}

	/**
	 * Order ads by title on ads list
	 *
	 * @param array $vars array with request vars.
	 *
	 * @return array $vars
	 * @since 1.3.18
	 */
	public function ad_list_request( $vars ) {

		// order ads by title on ads list.
		if ( is_admin() && empty( $vars['orderby'] ) && isset( $vars['post_type'] ) && $this->post_type === $vars['post_type'] ) {
			$vars = array_merge(
				$vars,
				array(
					'orderby' => 'title',
					'order'   => 'ASC',
				)
			);
		}

		return $vars;
	}

	/**
	 * Show instructions to create first ad above the ad list
	 *
	 * @return string
	 */
	public function no_ads_yet_notice() {
		$screen = get_current_screen();
		if ( ! isset( $screen->id ) || 'edit-advanced_ads' !== $screen->id ) {
			return;
		}

		// get number of ads.
		$existing_ads = Advanced_Ads::get_number_of_ads();

		// only display if there are no more than 2 ads.
		if ( 3 > $existing_ads ) {
			echo '<div class="advads-ad-metabox postbox" style="clear: both; margin: 10px 20px 0 2px;">';
			include ADVADS_BASE_PATH . 'admin/views/ad-list-no-ads.php';
			echo '</div>';
		}
	}

	/**
	 * Prepare the ad post type to be saved
	 *
	 * @param int $post_id id of the post.
	 * @todo handling this more dynamic based on ad type
	 */
	public function save_ad( $post_id ) {

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ) ) // only use for ads, no other post type.
		     || ! isset( $_POST['post_type'] )
		     || $this->post_type != $_POST['post_type']
		     || ! isset( $_POST['advanced_ad']['type'] )
		     || wp_is_post_revision( $post_id ) ) {
			return;
		}

		// get ad object.
		$ad = new Advanced_Ads_Ad( $post_id );
		if ( ! $ad instanceof Advanced_Ads_Ad ) {
			return;
		}

		// filter to allow change of submitted ad settings.
		$_POST['advanced_ad'] = apply_filters( 'advanced-ads-ad-settings-pre-save', $_POST['advanced_ad'] );

		$ad->type = wp_unslash( $_POST['advanced_ad']['type'] );

		/**
		 * Deprecated since introduction of "visitors" in 1.5.4
		 */
		if ( isset( $_POST['advanced_ad']['visitor'] ) ) {
			$ad->set_option( 'visitor', $_POST['advanced_ad']['visitor'] );
		} else {
			$ad->set_option( 'visitor', array() );
		}
		// visitor conditions.
		if ( isset( $_POST['advanced_ad']['visitors'] ) ) {
			$ad->set_option( 'visitors', $_POST['advanced_ad']['visitors'] );
		} else {
			$ad->set_option( 'visitors', array() );
		}
		$ad->url = 0;
		if ( isset( $_POST['advanced_ad']['url'] ) ) {
			// May contain placeholders added by the tracking add-on.
			$ad->url = trim( $_POST['advanced_ad']['url'] );
		}

		// save size.
		$ad->width = 0;
		if ( isset( $_POST['advanced_ad']['width'] ) ) {
			$ad->width = absint( $_POST['advanced_ad']['width'] );
		}
		$ad->height = 0;
		if ( isset( $_POST['advanced_ad']['height'] ) ) {
			$ad->height = absint( $_POST['advanced_ad']['height'] );
		}

		if ( ! empty( $_POST['advanced_ad']['description'] ) ) {
			$ad->description = esc_textarea( $_POST['advanced_ad']['description'] );
		} else {
			$ad->description = '';
		}

		if ( ! empty( $_POST['advanced_ad']['content'] ) ) {
			$ad->content = $_POST['advanced_ad']['content'];
		} else {
			$ad->content = '';
		}

		$output = isset( $_POST['advanced_ad']['output'] ) ? $_POST['advanced_ad']['output'] : array();

		// Find Advanced Ads shortcodes.
		if ( ! empty( $output['allow_shortcodes'] ) ) {
			$shortcode_pattern       = get_shortcode_regex(
				array(
					'the_ad',
					'the_ad_group',
					'the_ad_placement',
				)
			);
			$output['has_shortcode'] = preg_match( '/' . $shortcode_pattern . '/s', $ad->content );
		}

		// Set output.
		$ad->set_option( 'output', $output );

		if ( ! empty( $_POST['advanced_ad']['conditions'] ) ) {
			$ad->conditions = $_POST['advanced_ad']['conditions'];
		} else {
			$ad->conditions = array();
		}
		// prepare expiry date.
		if ( isset( $_POST['advanced_ad']['expiry_date']['enabled'] ) ) {
			$year   = absint( $_POST['advanced_ad']['expiry_date']['year'] );
			$month  = absint( $_POST['advanced_ad']['expiry_date']['month'] );
			$day    = absint( $_POST['advanced_ad']['expiry_date']['day'] );
			$hour   = absint( $_POST['advanced_ad']['expiry_date']['hour'] );
			$minute = absint( $_POST['advanced_ad']['expiry_date']['minute'] );

			$expiration_date = sprintf( '%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, '00' );
			$valid_date      = wp_checkdate( $month, $day, $year, $expiration_date );

			if ( ! $valid_date ) {
				$ad->expiry_date = 0;
			} else {
				$gm_date = date_create( $expiration_date, Advanced_Ads_Admin::get_wp_timezone() );
				$gm_date->setTimezone( new DateTimeZone( 'UTC' ) );
				$gm_date                                    = $gm_date->format( 'Y-m-d-H-i' );
				list( $year, $month, $day, $hour, $minute ) = explode( '-', $gm_date );
				$ad->expiry_date                            = gmmktime( $hour, $minute, 0, $month, $day, $year );
			}
		} else {
			$ad->expiry_date = 0;
		}

		$image_id = ( isset( $_POST['advanced_ad']['output']['image_id'] ) ) ? absint( $_POST['advanced_ad']['output']['image_id'] ) : 0;
		if ( $image_id ) {
			$attachment = get_post( $image_id );
			if ( $attachment && 0 === $attachment->post_parent ) {
				wp_update_post(
					array(
						'ID'          => $image_id,
						'post_parent' => $post_id,
					)
				);
			}
		}

		// phpcs:enable

		$ad->save();
	}

	/**
	 * Prepare main post data for ads when being saved.
	 *
	 * Set default title if it is empty.
	 *
	 * @param array $data An array of slashed post data.
	 * @return array
	 */
	public static function prepare_insert_post_data( $data ) {

		if ( Advanced_Ads::POST_TYPE_SLUG === $data['post_type']
			&& '' === $data['post_title'] ) {

			if ( function_exists( 'wp_date' ) ) {
				// The function wp_date was added in WP 5.3.
				$created_time = wp_date( get_option( 'date_format' ) ) . ' ' . wp_date( get_option( 'time_format' ) );
			} else {
				// Just attach the post date raw form.
				$created_time = $data['post_date'];
			}

			// Create timestamp from current data.
			$data['post_title'] = sprintf(
			// Translators: %s is the time the ad was first saved.
				__( 'Ad created on %s', 'advanced-ads' ),
				$created_time
			);
		}

		return $data;
	}

	/**
	 * Prepare the ad post type to be removed
	 *
	 * @param int $post_id id of the post.
	 */
	public function delete_ad( $post_id ) {
		global $wpdb;

		if ( ! current_user_can( 'delete_posts' ) ) {
			return;
		}

		if ( $post_id > 0 ) {
			$post_type = get_post_type( $post_id );
			if ( $post_type === $this->post_type ) {
				/**
				 * Images uploaded to an image ad type get the `_advanced-ads_parent_id` meta key from WordPress automatically
				 * the following SQL query removes that meta data from any attachment when the ad is removed.
				 */
				$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %d", '_advanced-ads_parent_id', $post_id ) );
			}
		}
	}

	/**
	 * Add information above the ad title
	 *
	 * @param object $post WordPress post type object.
	 *
	 * @since 1.5.6
	 */
	public function edit_form_above_title( $post ) {
		if ( ! isset( $post->post_type ) || $post->post_type !== $this->post_type ) {
			return;
		}

		// highlight Dummy ad if this is the first ad.
		if ( ! Advanced_Ads::get_number_of_ads() ) {
			?>
			<style>.advanced-ads-type-list-dummy {
					font-weight: bold;
				}</style>
			<?php
		}

		$ad = new Advanced_Ads_Ad( $post->ID );

		$placement_types = Advanced_Ads_Placements::get_placement_types();
		$placements      = Advanced_Ads::get_ad_placements_array(); // -TODO use model

		// display general and wizard information.
		include ADVADS_BASE_PATH . 'admin/views/ad-info-top.php';
		// Don’t show placement options if this is an ad translated with WPML since the placement might exist already.
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$trid         = apply_filters( 'wpml_element_trid', null, $post->ID );
			$translations = apply_filters( 'wpml_get_element_translations', null, $trid, 'Advanced_Ads' );
			if ( count( $translations ) > 1 ) {
				return;
			}
		}
		// Display ad injection information after ad is created.
		if ( isset( $_GET['message'] ) && 6 === $_GET['message'] ) {
			include ADVADS_BASE_PATH . 'admin/views/placement-injection-top.php';
		}
	}

	/**
	 * Add information about the ad below the ad title
	 *
	 * @param object $post WordPress Post object.
	 *
	 * @since 1.1.0
	 */
	public function edit_form_below_title( $post ) {
		if ( ! isset( $post->post_type ) || $post->post_type !== $this->post_type ) {
			return;
		}
		$ad = new Advanced_Ads_Ad( $post->ID );

		include ADVADS_BASE_PATH . 'admin/views/ad-info.php';
	}

	/**
	 * Add information below the ad edit form
	 *
	 * @param object $post WordPress Post object.
	 *
	 * @since 1.7.3
	 */
	public function edit_form_end( $post ) {
		if ( ! isset( $post->post_type ) || $post->post_type !== $this->post_type ) {
			return;
		}

		include ADVADS_BASE_PATH . 'admin/views/ad-info-bottom.php';
	}

	/**
	 * Add meta values below submit box
	 *
	 * @since 1.3.15
	 */
	public function add_submit_box_meta() {
		global $post, $wp_locale;

		if ( Advanced_Ads::POST_TYPE_SLUG !== $post->post_type ) {
			return;
		}

		$ad = new Advanced_Ads_Ad( $post->ID );

		// get time set for ad or current timestamp (both GMT).
		$utc_ts    = $ad->expiry_date ? $ad->expiry_date : time();
		$utc_time  = date_create( '@' . $utc_ts );
		$tz_option = get_option( 'timezone_string' );
		$exp_time  = clone $utc_time;

		if ( $tz_option ) {
			$exp_time->setTimezone( Advanced_Ads_Admin::get_wp_timezone() );
		} else {
			$tz_name       = Advanced_Ads_Admin::timezone_get_name( Advanced_Ads_Admin::get_wp_timezone() );
			$tz_offset     = substr( $tz_name, 3 );
			$off_time      = date_create( $utc_time->format( 'Y-m-d\TH:i:s' ) . $tz_offset );
			$offset_in_sec = date_offset_get( $off_time );
			$exp_time      = date_create( '@' . ( $utc_ts + $offset_in_sec ) );
		}

		list( $curr_year, $curr_month, $curr_day, $curr_hour, $curr_minute ) = explode( '-', $exp_time->format( 'Y-m-d-H-i' ) );
		$enabled = 1 - empty( $ad->expiry_date );

		include ADVADS_BASE_PATH . 'admin/views/ad-submitbox-meta.php';
	}

	/**
	 * Use CodeMirror for plain text input field
	 *
	 * Needs WordPress 4.9 and higher
	 *
	 * @since 1.8.15
	 */
	public function use_code_editor() {
		global $wp_version;
		if ( 'advanced_ads' !== get_current_screen()->id || defined( 'ADVANCED_ADS_DISABLE_CODE_HIGHLIGHTING' ) || - 1 === version_compare( $wp_version, '4.9' ) ) {
			return;
		}

		// Enqueue code editor and settings for manipulating HTML.
		$settings = wp_enqueue_code_editor( array( 'type' => 'application/x-httpd-php' ) );

		// Bail if user disabled CodeMirror.
		if ( false === $settings ) {
			return;
		}

		wp_add_inline_script(
			'code-editor',
			sprintf( 'jQuery( function() { if( jQuery( "#advads-ad-content-plain" ).length && typeof Advanced_Ads_Admin !== "undefined" ){ Advanced_Ads_Admin.editor = wp.codeEditor.initialize( "advads-ad-content-plain", %s ); Advanced_Ads_Admin.editor.codemirror.on("keyup", Advanced_Ads_Admin.check_ad_source); jQuery( function() { Advanced_Ads_Admin.check_ad_source(); } ); } } );', wp_json_encode( $settings ) )
		);
	}

	/**
	 * Edit ad update messages
	 *
	 * @param array $messages existing post update messages.
	 *
	 * @return array $messages
	 *
	 * @since 1.4.7
	 * @see wp-admin/edit-form-advanced.php
	 */
	public function ad_update_messages( $messages = array() ) {
		$post = get_post();

		// added to hide error message caused by third party code that uses post_updated_messages filter wrong.
		if ( ! is_array( $messages ) ) {
			return $messages;
		}

		$messages[ Advanced_Ads::POST_TYPE_SLUG ] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Ad updated.', 'advanced-ads' ),
			4  => __( 'Ad updated.', 'advanced-ads' ), /* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Ad restored to revision from %s', 'advanced-ads' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Ad saved.', 'advanced-ads' ), // published.
			7  => __( 'Ad saved.', 'advanced-ads' ), // saved.
			8  => __( 'Ad submitted.', 'advanced-ads' ),
			9  => sprintf(
			// translators: %1$s is a date.
				__( 'Ad scheduled for: <strong>%1$s</strong>.', 'advanced-ads' ),
				// translators: Publish box date format, see http://php.net/date.
					date_i18n( __( 'M j, Y @ G:i', 'advanced-ads' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Ad draft updated.', 'advanced-ads' ),
		);

		return $messages;
	}

	/**
	 * Whether to show the wizard welcome message or not
	 *
	 * @return bool true, if wizard welcome message should be displayed
	 * @since 1.7.4
	 */
	public function show_wizard_welcome() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return true;
		}

		$hide_wizard = get_user_meta( $user_id, 'advanced-ads-hide-wizard', true );
		global $post;

		return ( ! $hide_wizard && 'edit' !== $post->filter ) ? true : false;
	}

	/**
	 * Whether to start the wizard by default or not
	 *
	 * @since 1.7.4
	 * return bool true, if wizard should start automatically
	 */
	public function start_wizard_automatically() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return true;
		}

		$hide_wizard = get_user_meta( $user_id, 'advanced-ads-hide-wizard', true );

		global $post;

		// true the ad already exists, if the wizard was never started or closed.
		return ( 'edit' !== $post->filter && ( ! $hide_wizard || 'false' === $hide_wizard ) ) ? true : false;
	}

	/**
	 * Replace 'You need a higher level of permission.' message if user role does not have required permissions.
	 *
	 * @param string $translated_text Translated text.
	 * @param string $untranslated_text Text to translate.
	 *
	 * @return string $translation  Translated text.
	 */
	public function replace_cheating_message( $translated_text, $untranslated_text ) {
		global $typenow;

		if ( isset( $typenow ) && 'You need a higher level of permission.' === $untranslated_text && $typenow === $this->post_type ) {
			$translated_text = __( 'You don’t have access to ads. Please deactivate and re-enable Advanced Ads again to fix this.', 'advanced-ads' )
							   . '&nbsp;<a href="' . ADVADS_URL . 'manual/user-capabilities/?utm_source=advanced-ads&utm_medium=link&utm_campaign=wrong-user-role#You_dont_have_access_to_ads" target="_blank">' . __( 'Get help', 'advanced-ads' ) . '</a>';
		}

		return $translated_text;
	}

	/**
	 * General stuff after ad edit page is loaded and screen variable is available
	 */
	public function run_on_ad_edit_screen() {
		$screen = get_current_screen();

		if ( ! isset( $screen->id ) || 'advanced_ads' !== $screen->id ) {
			return;
		}

		// Remove parent group dropdown in ad edit.
		add_filter(
			'wp_dropdown_cats',
			array(
				$this,
				'remove_parent_group_dropdown',
			),
			10,
			2
		);

	}

	/**
	 * Remove parent group dropdown from ad group taxonomy
	 *
	 * @param string $output parent group HTML.
	 * @param array  $arguments additional parameters.
	 *
	 * @return string new parent group HTML
	 */
	public function remove_parent_group_dropdown( $output, $arguments ) {
		if ( 'newadvanced_ads_groups_parent' === $arguments['name'] ) {
			$output = '';
		}

		return $output;
	}

	/**
	 * Create a unique across all post types slug for the ad.
	 * Almost all code here copied from `wp_unique_post_slug()`.
	 *
	 * @param string $override_slug Short-circuit return value.
	 * @param string $slug The desired slug (post_name).
	 * @param int    $post_ID Post ID.
	 * @param string $post_status The post status.
	 * @param string $post_type Post type.
	 * @param int    $post_parent Post parent ID.
	 *
	 * @return string
	 */
	public function pre_wp_unique_post_slug( $override_slug, $slug, $post_ID, $post_status, $post_type, $post_parent ) {
		if ( Advanced_Ads::POST_TYPE_SLUG !== $post_type ) {
			return $override_slug;
		}

		global $wpdb, $wp_rewrite;

		$feeds = $wp_rewrite->feeds;
		if ( ! is_array( $feeds ) ) {
			$feeds = array();
		}

		// Advanced Ads post types slugs must be unique across all types.
		$check_sql       = "SELECT post_name FROM $wpdb->posts WHERE post_name = %s AND ID != %d LIMIT 1";
		$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $post_ID ) );

		if ( $post_name_check || in_array( $slug, $feeds, true ) || 'embed' === $slug ) {
			$suffix = 2;
			do {
				$alt_post_name   = substr( $slug, 0, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
				$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_post_name, $post_ID ) );
				$suffix ++;
			} while ( $post_name_check );
			$override_slug = $alt_post_name;
		}

		return $override_slug;
	}

}

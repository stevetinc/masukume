<?php

/**
 * Advanced Ads.
 *
 * @package   Advanced_Ads
 * @author    Thomas Maier <support@wpadvancedads.com>
 * @license   GPL-2.0+
 * @link      https://wpadvancedads.com
 * @copyright 2013-2018 Thomas Maier, Advanced Ads GmbH
 */

/**
 * This class is used to bundle all ajax callbacks
 *
 * @package Advanced_Ads_Ajax_Callbacks
 * @author  Thomas Maier <support@wpadvancedads.com>
 */
class Advanced_Ads_Ad_Ajax_Callbacks {

	/**
	 * Advanced_Ads_Ad_Ajax_Callbacks constructor.
	 */
	public function __construct() {

		// admin only!
		add_action( 'wp_ajax_load_ad_parameters_metabox', array( $this, 'load_ad_parameters_metabox' ) );
		add_action( 'wp_ajax_load_visitor_conditions_metabox', array( $this, 'load_visitor_condition' ) );
		add_action( 'wp_ajax_load_display_conditions_metabox', array( $this, 'load_display_condition' ) );
		add_action( 'wp_ajax_advads-terms-search', array( $this, 'search_terms' ) );
		add_action( 'wp_ajax_advads-close-notice', array( $this, 'close_notice' ) );
		add_action( 'wp_ajax_advads-hide-notice', array( $this, 'hide_notice' ) );
		add_action( 'wp_ajax_advads-subscribe-notice', array( $this, 'subscribe' ) );
		add_action( 'wp_ajax_advads-activate-license', array( $this, 'activate_license' ) );
		add_action( 'wp_ajax_advads-deactivate-license', array( $this, 'deactivate_license' ) );
		add_action( 'wp_ajax_advads-adblock-rebuild-assets', array( $this, 'adblock_rebuild_assets' ) );
		add_action( 'wp_ajax_advads-post-search', array( $this, 'post_search' ) );
		add_action( 'wp_ajax_advads-ad-injection-content', array( $this, 'inject_placement' ) );
		add_action( 'wp_ajax_advads-save-hide-wizard-state', array( $this, 'save_wizard_state' ) );
		add_action( 'wp_ajax_advads-adsense-enable-pla', array( $this, 'adsense_enable_pla' ) );
		add_action( 'wp_ajax_advads-ad-health-notice-display', array( $this, 'ad_health_notice_display' ) );
		add_action( 'wp_ajax_advads-ad-health-notice-push-adminui', array( $this, 'ad_health_notice_push' ) );
		add_action( 'wp_ajax_advads-ad-health-notice-hide', array( $this, 'ad_health_notice_hide' ) );
		add_action( 'wp_ajax_advads-ad-health-notice-unignore', array( $this, 'ad_health_notice_unignore' ) );
		add_action( 'wp_ajax_advads-ad-health-notice-solved', array( $this, 'ad_health_notice_solved' ) );
		add_action( 'wp_ajax_advads-update-frontend-element', array( $this, 'update_frontend_element' ) );

	}

	/**
	 * Load content of the ad parameter metabox
	 *
	 * @since 1.0.0
	 */
	public function load_ad_parameters_metabox() {

		check_ajax_referer( 'advanced-ads-admin-ajax-nonce', 'nonce' );
		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ) ) ) {
			return;
		}

		$types       = Advanced_Ads::get_instance()->ad_types;
		$type_string = $_REQUEST['ad_type'];
		$ad_id       = absint( $_REQUEST['ad_id'] );
		if ( empty( $ad_id ) ) {
			die();
		}

		$ad = new Advanced_Ads_Ad( $ad_id );

		if ( ! empty( $types[ $type_string ] ) && method_exists( $types[ $type_string ], 'render_parameters' ) ) {
			$type = $types[ $type_string ];
			$type->render_parameters( $ad );

			$types_without_size = array( 'dummy' );
			$types_without_size = apply_filters( 'advanced-ads-types-without-size', $types_without_size );
			if ( ! in_array( $type_string, $types_without_size ) ) {
				include ADVADS_BASE_PATH . 'admin/views/ad-parameters-size.php';
			}

			// set the ad type attribute if empty
			if ( ! isset( $ad->type ) ) {
				$ad->type = $type_string;
			}

			// extend the AJAX-loaded parameters form by ad type
			if ( isset( $types[ $type_string ] ) ) {
				do_action( "advanced-ads-ad-params-after-{$type_string}", $ad, $types );
			}
		}

		die();

	}

	/**
	 * Load interface for single visitor condition
	 *
	 * @since 1.5.4
	 */
	public function load_visitor_condition() {

		check_ajax_referer( 'advanced-ads-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ) ) ) {
			return;
		}

		// get visitor condition types.
		$visitor_conditions = Advanced_Ads_Visitor_Conditions::get_instance()->conditions;
		$condition          = array();
		$condition['type']  = isset( $_POST['type'] ) ? $_POST['type'] : '';
		$index              = isset( $_POST['index'] ) ? $_POST['index'] : 0;

		$form_name = isset( $_POST['form_name'] ) ? $_POST['form_name'] : Advanced_Ads_Visitor_Conditions::FORM_NAME;

		if ( isset( $visitor_conditions[ $condition['type'] ] ) ) {
			$metabox = $visitor_conditions[ $condition['type'] ]['metabox'];
		} else {
			die();
		}

		if ( method_exists( $metabox[0], $metabox[1] ) ) {
			call_user_func( array( $metabox[0], $metabox[1] ), $condition, $index, $form_name );
		}

		die();
	}

	/**
	 * Load interface for single display condition
	 *
	 * @since 1.7
	 */
	public function load_display_condition() {

		check_ajax_referer( 'advanced-ads-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ) ) ) {
			return;
		}

		// get display condition types.
		$conditions        = Advanced_Ads_Display_Conditions::get_instance()->conditions;
		$condition         = array();
		$condition['type'] = isset( $_POST['type'] ) ? $_POST['type'] : '';
		$index             = isset( $_POST['index'] ) ? $_POST['index'] : 0;

		$form_name = isset( $_POST['form_name'] ) ? $_POST['form_name'] : Advanced_Ads_Display_Conditions::FORM_NAME;

		if ( isset( $conditions[ $condition['type'] ] ) ) {
			$metabox = $conditions[ $condition['type'] ]['metabox'];
		} else {
			die();
		}

		if ( method_exists( $metabox[0], $metabox[1] ) ) {
			call_user_func( array( $metabox[0], $metabox[1] ), $condition, $index, $form_name );
		}

		die();
	}

	/**
	 * Search terms belonging to a specific taxonomy
	 *
	 * @since 1.4.7
	 */
	public function search_terms() {

		check_ajax_referer( 'advanced-ads-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ) ) ) {
			return;
		}

		$args     = array();
		$taxonomy = $_POST['tax'];
		$args     = array(
			'hide_empty' => false,
			'number'     => 20,
		);

		if ( ! isset( $_POST['search'] ) || '' === $_POST['search'] ) {
			die();
		}

		// if search is an id, search for the term id, else do a full text search.
		if ( 0 !== absint( $_POST['search'] ) && strlen( $_POST['search'] ) === strlen( absint( $_POST['search'] ) ) ) {
			$args['include'] = array( absint( $_POST['search'] ) );
		} else {
			$args['search'] = $_POST['search'];
		}

		$results = get_terms( $taxonomy, $args );
		echo wp_json_encode( $results );
		echo "\n";
		die();
	}

	/**
	 * Close a notice for good
	 *
	 * @since 1.5.3
	 */
	public function close_notice() {

		check_ajax_referer( 'advanced-ads-admin-ajax-nonce', 'nonce' );

		if (
			! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) )
			|| empty( $_REQUEST['notice'] )
		) {
			die();
		}

		Advanced_Ads_Admin_Notices::get_instance()->remove_from_queue( $_REQUEST['notice'] );
		if ( isset( $_REQUEST['redirect'] ) ) {
			wp_safe_redirect( $_REQUEST['redirect'] );
			exit();
		}
		die();
	}

		/**
		 * Hide a notice for some time (7 days right now)
		 *
		 * @since 1.8.17
		 */
	public function hide_notice() {

		check_ajax_referer( 'advanced-ads-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) )
		|| empty( $_POST['notice'] )
		) {
			die();
		}

		Advanced_Ads_Admin_Notices::get_instance()->hide_notice( $_POST['notice'] );
		die();
	}

	/**
	 * Subscribe to newsletter
	 *
	 * @since 1.5.3
	 */
	public function subscribe() {

		check_ajax_referer( 'advanced-ads-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_see_interface' ) ) || empty( $_POST['notice'] )
		) {
			wp_send_json_error(
				array(
					// translators: %s is a URL.
					'message' => sprintf( __( 'An error occurred. Please use <a href="%s" target="_blank">this form</a> to sign up.', 'advanced-ads' ), 'http://eepurl.com/bk4z4P' ),
				),
				400
			);
		}

		wp_send_json_success( array( 'message' => Advanced_Ads_Admin_Notices::get_instance()->subscribe( $_POST['notice'] ) ) );
	}

	/**
	 * Activate license of an add-on
	 *
	 * @since 1.5.7
	 */
	public function activate_license() {
		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) ) ) {
			return;
		}

		// check nonce.
		check_ajax_referer( 'advads_ajax_license_nonce', 'security' );

		if ( ! isset( $_POST['addon'] ) || '' === $_POST['addon'] ) {
			die(); }

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo Advanced_Ads_Admin_Licenses::get_instance()->activate_license( $_POST['addon'], $_POST['pluginname'], $_POST['optionslug'], $_POST['license'] );
		// phpcs:enable

		die();
	}

	/**
	 * Deactivate license of an add-on
	 *
	 * @since 1.6.11
	 */
	public function deactivate_license() {
		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) ) ) {
			return;
		}

		// check nonce.
		check_ajax_referer( 'advads_ajax_license_nonce', 'security' );

		if ( ! isset( $_POST['addon'] ) || '' === $_POST['addon'] ) {
			die(); }

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo Advanced_Ads_Admin_Licenses::get_instance()->deactivate_license( $_POST['addon'], $_POST['pluginname'], $_POST['optionslug'] );
		// phpcs:enable

		die();
	}

	/**
	 * Rebuild assets for ad-blocker module
	 */
	public function adblock_rebuild_assets() {

		check_ajax_referer( 'advanced-ads-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) ) ) {
			return;
		}

		Advanced_Ads_Ad_Blocker_Admin::get_instance()->add_asset_rebuild_form();
		die();
	}

	/**
	 * Post search (used in Display conditions)
	 */
	public function post_search() {

		check_ajax_referer( 'advanced-ads-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ) ) ) {
			return;
		}

		add_filter( 'wp_link_query_args', array( 'Advanced_Ads_Display_Conditions', 'modify_post_search' ) );
		add_filter( 'posts_search', array( 'Advanced_Ads_Display_Conditions', 'modify_post_search_sql' ) );

		wp_ajax_wp_link_ajax();
	}

	/**
	 * Inject an ad and a placement
	 *
	 * @since 1.7.3
	 */
	public function inject_placement() {

		check_ajax_referer( 'advanced-ads-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ) ) ) {
			die();
		}

		$ad_id = absint( $_REQUEST['ad_id'] );
		if ( empty( $ad_id ) ) {
			die(); }

		// use existing placement.
		if ( isset( $_REQUEST['placement_slug'] ) ) {
			$xml_array[] = '<placements type="array">';
			$xml_array[] = '<item key="0" type="array">';
			$xml_array[] = '<item type="string">ad_' . $ad_id . '</item>';
			$xml_array[] = '<key type="string">' . $_REQUEST['placement_slug'] . '</key>';
			$xml_array[] = '<use_existing type="boolean">1</use_existing>';
			$xml_array[] = '</item>';
			$xml_array[] = '</placements>';

			$xml = '<advads-export>' . implode( '', $xml_array ) . '</advads-export>';

			Advanced_Ads_Import::get_instance()->import( $xml );
			if ( count( Advanced_Ads_Import::get_instance()->imported_data['placements'] ) ) {
				// if the ad was assigned.
				echo esc_attr( $_REQUEST['placement_slug'] );
			};
			die();
		}

		// create new placement.
		$placements = Advanced_Ads::get_instance()->get_model()->get_ad_placements_array();

		$type = esc_attr( $_REQUEST['placement_type'] );

		$item = 'ad_' . $ad_id;

		$options = array();

		// check type.
		$placement_types = Advanced_Ads_Placements::get_placement_types();
		if ( ! isset( $placement_types[ $type ] ) ) {
			die();
		}

		$title = $placement_types[ $type ]['title'];

		$new_placement = array(
			'type' => $type,
			'item' => $item,
			'name' => $title,
		);

		// set content specific options.
		if ( 'post_content' === $type ) {
			$index                    = isset( $_REQUEST['options']['index'] ) ? absint( $_REQUEST['options']['index'] ) : 1;
			$new_placement['options'] = array(
				'position' => 'after',
				'index'    => $index,
				'tag'      => 'p',
			);
		}

		$slug = Advanced_Ads_Placements::save_new_placement( $new_placement );
		// return potential slug.
		echo esc_attr( $slug );

		die();
	}

	/**
	 * Save ad wizard state for each user individually
	 *
	 * @since 1.7.4
	 */
	public function save_wizard_state() {

		check_ajax_referer( 'advanced-ads-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ) ) ) {
			return;
		}

		$state = ( isset( $_REQUEST['hide_wizard'] ) && 'true' === $_REQUEST['hide_wizard'] ) ? 'true' : 'false';

		// get current user.
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			die();
		}

		update_user_meta( $user_id, 'advanced-ads-hide-wizard', $state );

		die();
	}

	/**
	 * Enable Adsense Auto ads, previously "Page-Level ads"
	 */
	public function adsense_enable_pla() {

		check_ajax_referer( 'advanced-ads-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) ) ) {
			return;
		}

		$options                       = get_option( GADSENSE_OPT_NAME, array() );
		$options['page-level-enabled'] = true;
		update_option( GADSENSE_OPT_NAME, $options );
		die();
	}

	/**
	 * Display list of Ad Health notices
	 */
	public function ad_health_notice_display() {

		check_ajax_referer( 'advanced-ads-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) ) ) {
			return;
		}

		Advanced_Ads_Ad_Health_Notices::get_instance()->render_widget();
		die();
	}

	/**
	 * Push an Ad Health notice to the queue
	 */
	public function ad_health_notice_push() {

		check_ajax_referer( 'advanced-ads-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) ) ) {
			return;
		}

		$key  = ( ! empty( $_REQUEST['key'] ) ) ? esc_attr( $_REQUEST['key'] ) : false;
		$attr = ( ! empty( $_REQUEST['attr'] ) && is_array( $_REQUEST['attr'] ) ) ? $_REQUEST['attr'] : array();

		// update or new entry?
		if ( isset( $attr['mode'] ) && 'update' === $attr['mode'] ) {
			Advanced_Ads_Ad_Health_Notices::get_instance()->update( $key, $attr );
		} else {
			Advanced_Ads_Ad_Health_Notices::get_instance()->add( $key, $attr );
		}

		die();
	}

	/**
	 * Hide Ad Health notice
	 */
	public function ad_health_notice_hide() {
		check_ajax_referer( 'advanced-ads-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) ) ) {
			return;
		}

		$notice_key = ( ! empty( $_REQUEST['notice'] ) ) ? esc_attr( $_REQUEST['notice'] ) : false;

		Advanced_Ads_Ad_Health_Notices::get_instance()->hide( $notice_key );
		die();
	}

	/**
	 * Show all ignored notices of a given type
	 */
	public function ad_health_notice_unignore() {
		check_ajax_referer( 'advanced-ads-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) ) ) {
			return;
		}

		Advanced_Ads_Ad_Health_Notices::get_instance()->unignore();
		die();
	}

	/**
	 * After the user has selected a new frontend element, update the corresponding placement.
	 */
	public function update_frontend_element() {
		check_ajax_referer( 'advanced-ads-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_placements' ) ) ) {
			return;
		}

		if ( isset( $_POST['advads']['placements'] ) ) {
			Advanced_Ads_Placements::save_placements( $_POST['advads']['placements'] );
		}

		exit();
	}
}

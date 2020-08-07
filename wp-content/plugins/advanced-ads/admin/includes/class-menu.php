<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class Advanced_Ads_Admin_Menu
 */
class Advanced_Ads_Admin_Menu {
	/**
	 * Instance of this class.
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the ad group page
	 *
	 * @var      string
	 */
	protected $ad_group_hook_suffix = null;

	/**
	 * Advanced_Ads_Admin_Menu constructor.
	 */
	private function __construct() {
		// add menu items.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		add_action( 'admin_head', array( $this, 'highlight_menu_item' ) );

		$this->plugin_slug = Advanced_Ads::get_instance()->get_plugin_slug();
		$this->post_type   = constant( 'Advanced_Ads::POST_TYPE_SLUG' );
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
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		// get number of ads including those in trash.
		$has_ads = Advanced_Ads::get_number_of_ads();

		// get number of Ad Health notices.
		$notices = Advanced_Ads_Ad_Health_Notices::get_number_of_notices();
		// string for Ad Health notice number.
		$notice_alert = '&nbsp;<span class="update-plugins count-' . $notices . '"><span class="update-count">' . $notices . '</span></span>';

		// use the overview page only when there is an ad already.
		if ( $has_ads ) {
			add_menu_page(
				__( 'Overview', 'advanced-ads' ),
				'Advanced Ads',
				Advanced_Ads_Plugin::user_cap( 'advanced_ads_see_interface' ),
				$this->plugin_slug,
				array( $this, 'display_overview_page' ),
				Advanced_Ads_Plugin::get_icon_svg(),
				'58.74'
			);
		}
		// forward Ads link to new-ad page when there is no ad existing yet.
		// the target to post-new.php needs the extra "new" or any other attribute, since the original add-ad link was removed by CSS using the exact href attribute as a selector.
		$target = ( ! $has_ads ) ? 'post-new.php?post_type=' . Advanced_Ads::POST_TYPE_SLUG . '&new=new' : 'edit.php?post_type=' . Advanced_Ads::POST_TYPE_SLUG;
		add_submenu_page(
			$this->plugin_slug,
			__( 'Ads', 'advanced-ads' ),
			__( 'Ads', 'advanced-ads' ),
			Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ),
			$target
		);

		// display the main overview page as second item when we don’t have ads yet.
		if ( ! $has_ads ) {
			add_menu_page(
				__( 'Overview', 'advanced-ads' ),
				'Advanced Ads',
				Advanced_Ads_Plugin::user_cap( 'advanced_ads_see_interface' ),
				$this->plugin_slug,
				array( $this, 'display_overview_page' ),
				'dashicons-chart-line',
				'58.74'
			);

			add_submenu_page(
				$this->plugin_slug,
				__( 'Overview', 'advanced-ads' ),
				__( 'Overview', 'advanced-ads' ),
				Advanced_Ads_Plugin::user_cap( 'advanced_ads_see_interface' ),
				$this->plugin_slug,
				array( $this, 'display_overview_page' )
			);
		}

		// hidden by css; not placed in 'options.php' in order to highlight the correct item, see the 'highlight_menu_item()'.
		if ( ! current_user_can( 'edit_posts' ) ) {
			add_submenu_page(
				$this->plugin_slug,
				__( 'Add New Ad', 'advanced-ads' ),
				__( 'New Ad', 'advanced-ads' ),
				Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ),
				'post-new.php?post_type=' . Advanced_Ads::POST_TYPE_SLUG
			);
		}

		$this->ad_group_hook_suffix = add_submenu_page(
			$this->plugin_slug,
			__( 'Ad Groups & Rotations', 'advanced-ads' ),
			__( 'Groups & Rotation', 'advanced-ads' ),
			Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ),
			$this->plugin_slug . '-groups',
			array( $this, 'ad_group_admin_page' )
		);

		// add placements page.
		add_submenu_page(
			$this->plugin_slug,
			__( 'Ad Placements', 'advanced-ads' ),
			__( 'Placements', 'advanced-ads' ),
			Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_placements' ),
			$this->plugin_slug . '-placements',
			array( $this, 'display_placements_page' )
		);
		// add settings page.
		Advanced_Ads_Admin::get_instance()->plugin_screen_hook_suffix = add_submenu_page(
			$this->plugin_slug,
			__( 'Advanced Ads Settings', 'advanced-ads' ),
			__( 'Settings', 'advanced-ads' ),
			Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ),
			$this->plugin_slug . '-settings',
			array( $this, 'display_plugin_settings_page' )
		);

		/**
		 * Since we forward the support link to the settings page, we need to add the menu item manually
		 * could break if WordPress changes the API at one point, but it didn’t do that for many years
		 */
		global $submenu;
		if ( current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) ) ) {
			// we have to mute the phpcs warning about overriding superglobals since WordPress does not offer a better way to manipulate menu links.
			// phpcs:ignore
			$submenu['advanced-ads'][] = array(
				__( 'Support', 'advanced-ads' ), // title.
				Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ), // capability.
				admin_url( 'admin.php?page=advanced-ads-settings#top#support' ),
				__( 'Support', 'advanced-ads' ), // not sure what this is, but it is in the API.
			);
			// manipulate the title of the overview submenu page and add error count.
			if ( $has_ads ) {
				// we have to mute the phpcs warning about overriding superglobals since WordPress does not offer a better way to manipulate menu links.
				// phpcs:ignore
				$submenu['advanced-ads'][0][0] .= $notice_alert;
			} else {
				// we have to mute the phpcs warning about overriding superglobals since WordPress does not offer a better way to manipulate menu links.
				// phpcs:ignore
				$submenu['advanced-ads'][1][0] .= $notice_alert;
			}
			// link to license tab if they are invalid.
			if ( Advanced_Ads_Checks::licenses_invalid() ) {
				// we have to mute the phpcs warning about overriding superglobals since WordPress does not offer a better way to manipulate menu links.
				// phpcs:ignore
				$submenu['advanced-ads'][] = array(
					__( 'Licenses', 'advanced-ads' ) // title..
						. '&nbsp;<span class="update-plugins count-1"><span class="update-count">!</span></span>',
					Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ), // capability.
					admin_url( 'admin.php?page=advanced-ads-settings#top#licenses' ),
					__( 'Licenses', 'advanced-ads' ), // not sure what this is, but it is in the API.
				);
			}
		}

		add_filter(
			'option_page_capability_' . ADVADS_SLUG,
			function () {
				return Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' );
			}
		);

		/**
		 * Allows extensions to insert sub menu pages.
		 *
		 * @since untagged Added the `$hidden_page_slug` parameter.
		 *
		 * @param string $plugin_slug      The slug slug used to add a visible page.
		 * @param string $hidden_page_slug The slug slug used to add a hidden page.
		 */
		do_action( 'advanced-ads-submenu-pages', $this->plugin_slug, 'advanced_ads_hidden_page_slug' ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
	}

	/**
	 * Highlights the 'Advanced Ads->Ads' item in the menu when an ad edit page is open
	 *
	 * @see the 'parent_file' and the 'submenu_file' filters for reference
	 */
	public function highlight_menu_item() {
		global $parent_file, $submenu_file, $post_type;
		if ( $post_type === $this->post_type ) {
			// we have to mute the phpcs warning about overriding superglobals since WordPress does not offer a better way to manipulate menu links.
			// phpcs:ignore
			$parent_file  = $this->plugin_slug;
			// phpcs:ignore
			$submenu_file = 'edit.php?post_type=' . $this->post_type;
		}
	}

	/**
	 * Render the overview page
	 *
	 * @since    1.2.2
	 */
	public function display_overview_page() {

		include ADVADS_BASE_PATH . 'admin/views/overview.php';
	}

	/**
	 * Render the settings page
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_settings_page() {
		include ADVADS_BASE_PATH . 'admin/views/settings.php';
	}

	/**
	 * Render the placements page
	 *
	 * @since    1.1.0
	 */
	public function display_placements_page() {
		$placement_types = Advanced_Ads_Placements::get_placement_types();
		$placements      = Advanced_Ads::get_ad_placements_array(); // -TODO use model
		// load ads and groups for select field.
		$items = Advanced_Ads_Placements::items_for_select();
		$orderby = $this->get_field_to_order_placement();

		// display view.
		include ADVADS_BASE_PATH . 'admin/views/placements.php';
	}

	/**
	 * Render the support page
	 *
	 * @since    1.6.8.1
	 */
	public function display_support_page() {

		include ADVADS_BASE_PATH . 'admin/views/support.php';
	}

	/**
	 * Render the ad group page
	 *
	 * @since    1.0.0
	 */
	public function ad_group_admin_page() {

		$taxonomy  = Advanced_Ads::AD_GROUP_TAXONOMY;
		$post_type = Advanced_Ads::POST_TYPE_SLUG;
		$tax       = get_taxonomy( $taxonomy );

		$action = Advanced_Ads_Admin::get_instance()->current_action();

		// handle new and updated groups.
		if ( 'editedgroup' === $action ) {
			$group_id = (int) $_POST['group_id'];
			check_admin_referer( 'update-group_' . $group_id );

			if ( ! current_user_can( $tax->cap->edit_terms ) ) {
				wp_die( esc_html__( 'Sorry, you are not allowed to access this feature.', 'advanced-ads' ) ); }

			// handle new groups.
			if ( 0 === $group_id ) {
				$ret = wp_insert_term( $_POST['name'], $taxonomy, $_POST );
				if ( $ret && ! is_wp_error( $ret ) ) {
					$forced_message = 1; } else {
					$forced_message = 4; }
					// handle group updates.
			} else {
				$tag = get_term( $group_id, $taxonomy );
				if ( ! $tag ) {
					wp_die( esc_html__( 'You attempted to edit an ad group that doesn&#8217;t exist. Perhaps it was deleted?', 'advanced-ads' ) ); }

				$ret = wp_update_term( $group_id, $taxonomy, $_POST );
				if ( $ret && ! is_wp_error( $ret ) ) {
					$forced_message = 3; } else {
					$forced_message = 5; }
			}
			// deleting items.
		} elseif ( 'delete' === $action ) {
			$group_id = (int) $_REQUEST['group_id'];
			check_admin_referer( 'delete-tag_' . $group_id );

			if ( ! current_user_can( $tax->cap->delete_terms ) ) {
				wp_die( esc_html__( 'Sorry, you are not allowed to access this feature.', 'advanced-ads' ) ); }

			wp_delete_term( $group_id, $taxonomy );
			// delete the weights.
			Advanced_Ads_Group::delete_ad_weights( $group_id );

			$forced_message = 2;
		}

		// handle views.
		switch ( $action ) {
			case 'edit':
				$title = $tax->labels->edit_item;
				if ( isset( $_REQUEST['group_id'] ) ) {
					$group_id = absint( $_REQUEST['group_id'] );
					$tag      = get_term( $group_id, $taxonomy, OBJECT, 'edit' );
				} else {
					$group_id = 0;
					$tag      = false;
				}

				include ADVADS_BASE_PATH . 'admin/views/ad-group-edit.php';
				break;

			default:
				$title         = $tax->labels->name;
				$wp_list_table = _get_list_table( 'WP_Terms_List_Table' );

				// load template.
				include ADVADS_BASE_PATH . 'admin/views/ad-group.php';
		}
	}

	/**
	 * Get the field to order placements.
	 *
	 * @return string
	 */
	private function get_field_to_order_placement() {
		$admin_settings = Advanced_Ads_Admin::get_admin_settings();

		$default = isset( $admin_settings['placement-orderby'] ) ? $admin_settings['placement-orderby'] : 'type';
		$current = isset( $_GET['orderby'] ) ? $_GET['orderby'] : $default; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! in_array( $current, array( 'name', 'type' ) ) ) {
			$current = 'type';
		}

		// Save default field, if it was changed.
		$admin_settings['placement-orderby'] = $current;
		Advanced_Ads_Admin::update_admin_setttings( $admin_settings );

		return $current;
	}

	/**
	 * Get the URL where the user is redirected after activating the frontend picker for a "Content" placement.
	 *
	 * @since string URL.
	 */
	private function get_url_for_content_placement_picker() {
		$location = false;

		if ( 'posts' === get_option( 'show_on_front' ) ) {
			$recent_posts = wp_get_recent_posts(
				array(
					'numberposts' => 1,
					'post_type'   => 'post',
					'post_status' => 'publish',
				),
				'OBJECT'
			);

			if ( $recent_posts ) {
				$location = get_permalink( $recent_posts[0] );
			}
		}

		if ( ! $location ) {
			$location = home_url();
		}
		return $location;
	}

}

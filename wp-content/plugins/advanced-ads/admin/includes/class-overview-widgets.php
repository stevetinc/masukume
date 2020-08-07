<?php
/**
 * Container class for callbacks for overview widgets
 *
 * @package WordPress
 * @subpackage Advanced Ads Plugin
 */
class Advanced_Ads_Overview_Widgets_Callbacks {
	/**
	 * In case one wants to inject several dashboards into a page, we will prevent executing redundant javascript
	 * with the help of this little bool
	 *
	 * @var mixed
	 */
	private static $processed_adsense_stats_js = false;

	/**
	 * When doing ajax request (refreshing the dashboard), we need to have a nonce.
	 * one is enough, that's why we need to remember it.
	 *
	 * @var mixed
	 */
	private static $gadsense_dashboard_nonce = false;


	/**
	 * Register the plugin overview widgets
	 */
	public static function setup_overview_widgets() {

		// initiate i18n notice.
		new Yoast_I18n_WordPressOrg_v3(
			array(
				'textdomain'  => 'advanced-ads',
				'plugin_name' => 'Advanced Ads',
				'hook'        => 'advanced-ads-overview-below-support',
			)
		);

		// show errors.
		if ( Advanced_Ads_Ad_Health_Notices::notices_enabled()
				&& count( Advanced_Ads_Ad_Health_Notices::get_instance()->displayed_notices ) ) {
				self::add_meta_box( 'advads_overview_notices', false, 'full', 'render_notices' );
		}

		self::add_meta_box(
			'advads_overview_news',
			__( 'Next steps', 'advanced-ads' ),
			'left',
			'render_next_steps'
		);
		self::add_meta_box(
			'advads_overview_support',
			__( 'Manual and Support', 'advanced-ads' ),
			'right',
			'render_support'
		);
		if ( Advanced_Ads_AdSense_Data::get_instance()->is_setup()
			&& ! Advanced_Ads_AdSense_Data::get_instance()->is_hide_stats() ) {
			$disable_link_markup = '<span class="advads-hndlelinks hndle"><a href="' . esc_url( admin_url( 'admin.php?page=advanced-ads-settings#top#adsense' ) ) . '" target="_blank">' . esc_attr__( 'Disable', 'advanced-ads' ) . '</a></span>';

			self::add_meta_box(
				'advads_overview_adsense_stats',
				__( 'AdSense Earnings', 'advanced-ads' ) . $disable_link_markup,
				'full',
				'render_adsense_stats'
			);
		}

		// add widgets for pro add ons.
		self::add_meta_box( 'advads_overview_addons', __( 'Add-Ons', 'advanced-ads' ), 'full', 'render_addons' );

		do_action( 'advanced-ads-overview-widgets-after' );
	}

	/**
	 * Loads a meta box into output
	 *
	 * @param string   $id meta box ID.
	 * @param string   $title title of the meta box.
	 * @param string   $position context in which to show the box.
	 * @param callable $callback function that fills the box with the desired content.
	 */
	public static function add_meta_box( $id = '', $title = '', $position = 'full', $callback ) {

		ob_start();
		call_user_func( array( 'Advanced_Ads_Overview_Widgets_Callbacks', $callback ) );
		do_action( 'advanced-ads-overview-widget-content-' . $id, $id );
		$content = ob_get_clean();

		include ADVADS_BASE_PATH . 'admin/views/overview-widget.php';

	}

	/**
	 * Render Ad Health notices widget
	 */
	public static function render_notices() {

		Advanced_Ads_Ad_Health_Notices::get_instance()->render_widget();
		?><script>jQuery( document ).ready( function(){ advads_ad_health_maybe_remove_list(); });</script>
		<?php

	}


	/**
	 * Render next steps widget
	 */
	public static function render_next_steps() {

		$primary_taken = false;

		$model      = Advanced_Ads::get_instance()->get_model();
		$recent_ads = $model->get_ads();
		if ( count( $recent_ads ) === 0 ) :
			echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'post-new.php?post_type=' . Advanced_Ads::POST_TYPE_SLUG ) ) .
			'">' . esc_html( __( 'Create your first ad', 'advanced-ads' ) ) . '</a></p>';
			// Connect to AdSense
			echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=advanced-ads-settings#top#adsense' ) ) .
			'">' . esc_attr__( 'Connect to AdSense', 'advanced-ads' ) . '</a></p>';
			$primary_taken = true;
		endif;

		$is_subscribed = Advanced_Ads_Admin_Notices::get_instance()->is_subscribed();
		$can_subscribe = Advanced_Ads_Admin_Notices::get_instance()->user_can_subscribe();
		$options       = Advanced_Ads_Admin_Notices::get_instance()->options();

		$_notice = 'nl_free_addons';
		if ( $can_subscribe ) {
			?>
			<h3><?php esc_html_e( 'Join the newsletter for more benefits', 'advanced-ads' ); ?></h3>
			<ul>
				<li><?php esc_html_e( 'Get 2 free add-ons', 'advanced-ads' ); ?></li>
				<li><?php esc_html_e( 'Get the first steps and more tutorials to your inbox', 'advanced-ads' ); ?></li>
				<li><?php esc_html_e( 'How to earn more with AdSense', 'advanced-ads' ); ?></li>
			</ul>
			<div class="advads-admin-notice">
				<p>
					<button type="button" class="button-<?php echo ( $primary_taken ) ? 'secondary' : 'primary'; ?> advads-notices-button-subscribe" data-notice="<?php echo esc_attr( $_notice ); ?>">
						<?php esc_html_e( 'Join now', 'advanced-ads' ); ?>
					</button>
				</p>
			</div>
			<?php
		} elseif ( count( $recent_ads ) > 3
			&& ! isset( $options['closed']['review'] ) ) {
			/**
			 * Ask for a review if the review message was not closed before
			 */
			?>
			<div class="advads-admin-notice" data-notice="review">
				<p><?php esc_html_e( 'Do you find Advanced Ads useful and would like to keep us motivated? Please help us with a review.', 'advanced-ads' ); ?>
				<p><span class="dashicons dashicons-external"></span>&nbsp;<strong><a href="https://wordpress.org/support/plugin/advanced-ads/reviews/?rate=5#new-post" target=_"blank">
				<?php esc_html_e( 'Sure, I’ll rate the plugin', 'advanced-ads' ); ?></a></strong>
				&nbsp;&nbsp;<span class="dashicons dashicons-smiley"></span>&nbsp;<a href="javascript:void(0)" target=_"blank" class="advads-notice-dismiss">
					<?php esc_html_e( 'I already did', 'advanced-ads' ); ?></a>
				</p>
			</div>
			<?php
		} elseif ( count( $recent_ads ) > 0 ) {
			// link to manage ads.
			echo '<p><a class="button button-secondary" href="' . esc_url( admin_url( 'edit.php?post_type=' . Advanced_Ads::POST_TYPE_SLUG ) ) .
			'">' . esc_html__( 'Manage your ads', 'advanced-ads' ) . '</a></p>';
		}

		$all_access = Advanced_Ads_Admin_Licenses::get_instance()->get_probably_all_access();
		if ( $is_subscribed && ! $all_access ) {
			?>
			<a class="button button-primary" href="<?php echo esc_url( ADVADS_URL ); ?>add-ons/all-access/#utm_source=advanced-ads&utm_medium=link&utm_campaign=pitch-bundle" target="_blank"><?php esc_html_e( 'Get the All Access pass', 'advanced-ads' ); ?></a>
			<?php
		}
	}

	/**
	 * Support widget
	 */
	public static function render_support() {
		?>
		<ul>
			<li><a href="<?php echo esc_url( ADVADS_URL . 'manual/#utm_source=advanced-ads&utm_medium=link&utm_campaign=overview-manual' ); ?>" target="_blank">
			<?php esc_html_e( 'Manual', 'advanced-ads' ); ?>
				</a>
			</li>
			<li><a href="<?php echo esc_url( ADVADS_URL . 'support/#utm_source=advanced-ads&utm_medium=link&utm_campaign=overview-support' ); ?>" target="_blank">
			<?php esc_html_e( 'FAQ and Support', 'advanced-ads' ); ?>
				</a>
			</li>
			<li>
			<?php
			printf(
				wp_kses(
					// translators: %s is a URL.
					__( 'Thank the developer with a &#9733;&#9733;&#9733;&#9733;&#9733; review on <a href="%s" target="_blank">wordpress.org</a>', 'advanced-ads' ),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
						),
					)
				),
				'https://wordpress.org/support/plugin/advanced-ads/reviews/#new-post'
			);
			?>
				</li>
		</ul>
		<?php

		$ignored_count   = count( Advanced_Ads_Ad_Health_Notices::get_instance()->ignore );
		$displayed_count = count( Advanced_Ads_Ad_Health_Notices::get_instance()->displayed_notices );
		if ( ! $displayed_count && $ignored_count ) {
			// translators: %s includes a number and markup like <span class="count">6</span>.
			?>
			<p><span class="dashicons dashicons-warning"></span>&nbsp;<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=advanced-ads&advads-show-hidden-notices=true' ), 'advanced-ads-show-hidden-notices', 'advads_nonce' ) ); ?>">
			<?php
			printf(
				// translators: %s is the number of hidden notices.
				esc_html__( 'Show %s hidden notices', 'advanced-ads' ),
				absint( $ignored_count )
			);
			?>
				</a></p>
				<?php
		}

		do_action( 'advanced-ads-overview-below-support' );

	}

	/**
	 * Adsense stats widget
	 */
	public static function render_adsense_stats() {
		$option_name  = 'advanced-ads-adsense-dashboard-filter';
		$filter_value = get_option( $option_name, null );
		if ( ! $filter_value ) {
			$filter_value = self::get_site_domain();
		}
		if ( '*' === $filter_value ) {
			$filter_value = null;
		}
		$advads_gadsense_options = array(
			'dimension_name' => 'DOMAIN_NAME',
			'allow_refresh'  => true,
			'filter_value'   => $filter_value,
		);
		include ADVADS_BASE_PATH . 'admin/views/gadsense-dashboard.php';
	}

	/**
	 * JavaScript loaded in AdSense stats widget.
	 *
	 * @param string $pub_id AdSense publisher ID.
	 *
	 * @return string
	 * @todo move to JS file.
	 */
	final public static function adsense_stats_js( $pub_id ) {
		if ( self::$processed_adsense_stats_js ) {
			return;
		}
		self::$processed_adsense_stats_js = true;
		$nonce                            = self::get_adsense_dashboard_nonce();
		?>
		<script>
		window.gadsenseData = window.gadsenseData || {};
		gadsenseData['pubId'] = '<?php echo esc_html( $pub_id ); ?>';
		window.Advanced_Ads_Adsense_Helper.nonce = '<?php echo esc_html( $nonce ); ?>';
		</script>
		<?php
	}

	/**
	 * Return a nonce used in the AdSense stats widget.
	 *
	 * @return false|mixed|string
	 */
	final public static function get_adsense_dashboard_nonce() {
		if ( ! self::$gadsense_dashboard_nonce ) {
			self::$gadsense_dashboard_nonce = wp_create_nonce( 'advads-gadsense-dashboard' );
		}
		return self::$gadsense_dashboard_nonce;
	}

	/**
	 * Extracts the domain from the site url
	 *
	 * @return string the domain, that was extracted from get_site_url()
	 */
	public static function get_site_domain() {
		$site = get_site_url();
		preg_match( '|^([\d\w]+://)?([^/]+)|', $site, $matches );
		$domain = count( $matches ) > 1 ? $matches[2] : null;
		return $domain;
	}

	/**
	 * This method should be used, if you want to render a dashboard summary.
	 * it takes an associative options array as parameter to create a summary object,
	 * which can be used to create a json or html response.
	 *
	 * @param array $options dashboard options.
	 * @return Advanced_Ads_AdSense_Dashboard_Summary
	 */
	public static function create_dashboard_summary( $options ) {
		if ( ! $options ) {
			$options = array();
		}
		$options = array_merge(
			array(
				'dimension_name'  => null,
				'filter_value'    => null,
				'hide_dimensions' => false,
				'force_refresh'   => false,
				'allow_refresh'   => true,
			),
			$options
		);

		$dimension_name  = $options['dimension_name'];
		$filter_value    = $options['filter_value'];
		$hide_dimensions = $options['hide_dimensions'];
		$force_refresh   = $options['force_refresh'];
		$allow_refresh   = $options['allow_refresh'];

		$pub_id                   = Advanced_Ads_AdSense_Data::get_instance()->get_adsense_id();
		$optional_dimension_names = 'AD_UNIT_CODE' === $dimension_name ? self::get_ad_code_map( $pub_id ) : null;

		$summary = Advanced_Ads_AdSense_Report_Builder::createDashboardSummary( $dimension_name, $filter_value, 'dashboard', $optional_dimension_names, $force_refresh, $allow_refresh );
		if ( $hide_dimensions ) {
			$summary->dimensions = null;
		}
		$summary->hide_dimensions = $hide_dimensions;
		return $summary;
	}
	/**
	 * We want to display the name of the ad code insted of the code itself.
	 *
	 * @param string $pub_id the publisher id of the adsense account.
	 * @return array an associative array with ad codes as key and their respective name as value
	 */
	public static function get_ad_code_map( $pub_id ) {
		$map           = array();
		$ad_units_opts = get_option( Advanced_Ads_AdSense_MAPI::OPTNAME );
		if ( ! isset( $ad_units_opts['accounts'] ) ) {
			return null;
		}
		foreach ( $ad_units_opts['accounts'] as $key => $account ) {
			if ( $key === $pub_id && isset( $account['ad_units'] ) && is_array( $account['ad_units'] ) ) {
				$units = $account['ad_units'];
				foreach ( $units as $unit ) {
					$map[ $unit['code'] ] = $unit['name'];
				}
			}
		}
		return $map;
	}


	/**
	 * This method is called when the dashboard data is requested via ajax
	 * it prints the relevant data as json, then dies.
	 */
	public static function ajax_gadsense_dashboard() {
		// retrieve our post parameters.
		// phpcs:ignore
		$dimension_name = isset( $_POST['dimension_name'] ) ? $_POST['dimension_name'] : 'DOMAIN_NAME';
		// phpcs:ignore
		$filter_value   = isset( $_POST['filter'] ) ? $_POST['filter'] : null;
		$dimension_name = sanitize_text_field( $dimension_name );
		if ( $filter_value ) {
			$filter_value = sanitize_text_field( $filter_value );
		}

		$errors = array();
		// check nonce and capabilities.
		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) ) ) {
			$errors[] = 'missing capability';
		}
		// check nonce.
		if ( ! check_ajax_referer( 'advads-gadsense-dashboard', 'nonce', false ) ) {
			$errors[] = 'invalid request';
		}

		// when there is an error, send it right away.
		if ( count( $errors ) > 0 ) {
			$r = array(
				'summary' => array(
					'valid'  => false,
					'errors' => $errors,
				),
			);
			header( 'Content-Type: application/json' );
			echo wp_json_encode( $r );
			die();
		}

		$options = array(
			'dimension_name' => $dimension_name,
		);
		if ( 'DOMAIN_NAME' === $dimension_name ) {
			if ( $filter_value ) {
				update_option( 'advanced-ads-adsense-dashboard-filter', $filter_value );
			}
		} elseif ( 'AD_UNIT_CODE' === $dimension_name ) {
			$options['hide_dimensions'] = true;
		}
		if ( $filter_value && '*' === $filter_value ) {
			$filter_value = null;
		}
		$options['filter_value'] = $filter_value;

		$r            = array();
		$summary      = self::create_dashboard_summary( $options );
		$r['summary'] = $summary;

		header( 'Content-Type: application/json' );
		echo wp_json_encode( $r );
		die();
	}

	/**
	 * Render stats box
	 *
	 * @param string $title title of the box.
	 * @param string $main main content.
	 * @param string $footer footer content.
	 *
	 * @deprecated ?
	 */
	final public static function render_stats_box( $title, $main, $footer ) {
		?>
		<div class="advanced-ads-stats-box flex1">
			<?php echo $title; ?>
			<div class="advanced-ads-stats-box-main">
				<?php
				// phpcs:ignore
				echo $main;
				?>
			</div>
			<?php echo $footer; ?>
		</div>
		<?php
	}

	/**
	 * Pro addons widget
	 *
	 * @param   bool $hide_activated if true, hide activated add-ons.
	 */
	public static function render_addons( $hide_activated = false ) {

		$link = ADVADS_URL . 'manual/how-to-install-an-add-on/#utm_source=advanced-ads&utm_medium=link&utm_campaign=overview-install-add-ons';
		?>
		<p><a href="<?php echo esc_url( $link ); ?>" target="_blank"><?php echo esc_attr__( 'How to install and activate an add-on.', 'advanced-ads' ); ?></a></p>
		<?php

		$caching_used = Advanced_Ads_Checks::cache();

		ob_start();
		?>
		<p><?php esc_html_e( 'The solution for professional websites.', 'advanced-ads' ); ?></p><ul class='list'>
		<li>
		<?php
		if ( $caching_used ) :

			?>
			<strong>
			<?php
endif;
			esc_html_e( 'support for cached sites', 'advanced-ads' );
		if ( $caching_used ) :

			?>
			</strong>
			<?php
endif;
		?>
			</li>
		<?php
		if ( class_exists( 'bbPress', false ) ) :
			?>
			<li>
			<?php
			printf(
				// translators: %s is the name of another plugin.
				wp_kses( __( 'integrates with <strong>%s</strong>', 'advanced-ads' ), array( 'strong' => array() ) ),
				'bbPress'
			);
			?>
				</li><?php endif; /* bbPress */ ?>
		<?php
		if ( class_exists( 'BuddyPress', false ) ) : // BuddyPress or BuddyBoss
			?>
			<li>
			<?php
			printf(
			// translators: %s is the name of another plugin.
				wp_kses( __( 'integrates with <strong>%s</strong>', 'advanced-ads' ), array( 'strong' => array() ) ),
				defined( 'BP_PLATFORM_VERSION' ) ? 'BuddyBoss' : 'BuddyPress'
			);
			?>
				</li><?php endif; /* BuddyPress */ ?>
		<?php
		if ( defined( 'PMPRO_VERSION' ) ) :
			?>
			<li>
			<?php
			printf(
			// translators: %s is the name of another plugin.
				wp_kses( __( 'integrates with <strong>%s</strong>', 'advanced-ads' ), array( 'strong' => array() ) ),
				'Paid Memberships Pro'
			);
			?>
				</li><?php endif; /* Paid Memberships Pro */ ?>
		<?php
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) :
			?>
			<li>
			<?php
			printf(
			// translators: %s is the name of another plugin.
				wp_kses( __( 'integrates with <strong>%s</strong>', 'advanced-ads' ), array( 'strong' => array() ) ),
				'WPML'
			);
			?>
				</li><?php endif; /* WPML */ ?>
		<li><?php esc_html_e( 'click fraud protection, lazy load, ad-block ads', 'advanced-ads' ); ?></li>
		<li><?php esc_html_e( '11 more display and visitor conditions', 'advanced-ads' ); ?></li>
		<li><?php esc_html_e( '6 more placements', 'advanced-ads' ); ?></li>
		<li><?php esc_html_e( 'placement tests for ad optimization', 'advanced-ads' ); ?></li>
		<li><?php esc_html_e( 'ad grids and many more advanced features', 'advanced-ads' ); ?></li>
		</ul>
		<?php
		$pro_content = ob_get_clean();

		$add_ons = array(
			'pro'             => array(
				'title' => 'Advanced Ads Pro',
				'desc'  => $pro_content,
				'link'  => ADVADS_URL . 'add-ons/advanced-ads-pro/#utm_source=advanced-ads&utm_medium=link&utm_campaign=overview-add-ons',
				'order' => 4,
				'class' => 'recommended',
			),
			'tracking'        => array(
				'title' => 'Tracking',
				'desc'  => __( 'Analyze clicks and impressions of your ads locally or in Google Analytics, share reports, and limit ads to a specific number of impressions or clicks.', 'advanced-ads' ),
				'link'  => ADVADS_URL . 'add-ons/tracking/#utm_source=advanced-ads&utm_medium=link&utm_campaign=overview-add-ons',
				'order' => 4,
			),
			'responsive'      => array(
				'title' => 'Responsive, AMP and Mobile ads',
				'desc'  => __( 'Display ads based on the device or the size of your visitor’s browser, and control ads on AMP pages.', 'advanced-ads' ),
				'link'  => ADVADS_URL . 'add-ons/responsive-ads/#utm_source=advanced-ads&utm_medium=link&utm_campaign=overview-add-ons',
				'order' => 4,
			),
			'gam'             => array(
				'title' => 'Google Ad Manager Integration',
				'desc'  => __( 'A quick and error-free way of implementing ad units from your Google Ad Manager account.', 'advanced-ads' ),
				'link'  => ADVADS_URL . 'add-ons/google-ad-manager/#utm_source=advanced-ads&utm_medium=link&utm_campaign=overview-add-ons',
				'order' => 5,
			),
			'geo'             => array(
				'title' => 'Geo Targeting',
				'desc'  => __( 'Target visitors with ads that match their geo location and make more money with regional campaigns.', 'advanced-ads' ),
				'link'  => ADVADS_URL . 'add-ons/geo-targeting/#utm_source=advanced-ads&utm_medium=link&utm_campaign=overview-add-ons',
				'order' => 5,
			),
			'sticky'          => array(
				'title' => 'Sticky ads',
				'desc'  => __( 'Increase click rates on your ads by placing them in sticky positions above, next or below your site.', 'advanced-ads' ),
				'link'  => ADVADS_URL . 'add-ons/sticky-ads/#utm_source=advanced-ads&utm_medium=link&utm_campaign=overview-add-ons',
				'order' => 5,
			),
			'layer'           => array(
				'title' => 'PopUps and Layers',
				'desc'  => __( 'Users will never miss an ad or other information in a PopUp. Choose when it shows up and for how long a user can close it.', 'advanced-ads' ),
				'link'  => ADVADS_URL . 'add-ons/popup-and-layer-ads/#utm_source=advanced-ads&utm_medium=link&utm_campaign=overview-add-ons',
				'order' => 5,
			),
			'selling'         => array(
				'title' => 'Selling Ads',
				'desc'  => __( 'Earn more money and let advertisers pay for ad space directly on the frontend of your site.', 'advanced-ads' ),
				'link'  => ADVADS_URL . 'add-ons/selling-ads/#utm_source=advanced-ads&utm_medium=link&utm_campaign=overview-add-ons',
				'order' => 6,
			),
			'slider'          => array(
				'title' => 'Ad Slider',
				'desc'  => __( 'Create a beautiful and simple slider from your ads to show more information on less space.', 'advanced-ads' ),
				'link'  => ADVADS_URL . 'add-ons/slider/#utm_source=advanced-ads&utm_medium=link&utm_campaign=overview-add-ons',
				'order' => 6,
			),
			'adsense-in-feed' => array(
				'title'      => 'AdSense In-feed',
				'desc'       => __( 'Place AdSense In-feed ads between posts on homepage, category, and archive pages.', 'advanced-ads' ),
				'class'      => 'free',
				'link'       => wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=advanced-ads-adsense-in-feed' ), 'install-plugin_advanced-ads-adsense-in-feed' ),
				'link_title' => __( 'Install now', 'advanced-ads' ),
				'order'      => 9,
			),
		);

		// get all installed plugins; installed is not activated.
		$installed_plugins     = get_plugins();
		$installed_pro_plugins = 0;

		// handle AdSense In-feed if already installed or not activated.
		if ( isset( $installed_plugins['advanced-ads-adsense-in-feed/advanced-ads-in-feed.php'] ) ) { // is installed, but not active.
			// remove plugin from the list.
			unset( $add_ons['adsense-in-feed'] );
		}

		// PRO.
		if ( isset( $installed_plugins['advanced-ads-pro/advanced-ads-pro.php'] ) && ! class_exists( 'Advanced_Ads_Pro' ) ) { // is installed, but not active.
			$add_ons['pro']['link']       = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=advanced-ads-pro/advanced-ads-pro.php&amp', 'activate-plugin_advanced-ads-pro/advanced-ads-pro.php' );
			$add_ons['pro']['link_title'] = __( 'Activate now', 'advanced-ads' );
			$installed_pro_plugins++;
		} elseif ( class_exists( 'Advanced_Ads_Pro' ) ) {
			$add_ons['pro']['link']      = ADVADS_URL . 'manual/#utm_source=advanced-ads&utm_medium=link&utm_campaign=overview-add-ons-manual';
			$add_ons['pro']['desc']      = '';
			$add_ons['pro']['installed'] = true;
			$add_ons['pro']['order']     = 20;
			$installed_pro_plugins++;

			// remove the add-on.
			if ( $hide_activated ) {
				unset( $add_ons['pro'] );
			}
		}

		// TRACKING.
		if ( isset( $installed_plugins['advanced-ads-tracking/tracking.php'] ) && ! class_exists( 'Advanced_Ads_Tracking_Plugin' ) ) { // is installed, but not active.
			$add_ons['tracking']['link']       = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=advanced-ads-tracking/tracking.php&amp', 'activate-plugin_advanced-ads-tracking/tracking.php' );
			$add_ons['tracking']['link_title'] = __( 'Activate now', 'advanced-ads' );
			$installed_pro_plugins++;
		} elseif ( class_exists( 'Advanced_Ads_Tracking_Plugin', false ) &&
			method_exists( Advanced_Ads_Tracking_Plugin::get_instance(), 'get_tracking_method' ) ) {
			$add_ons['tracking']['link'] = ADVADS_URL . 'manual/tracking-documentation/#utm_source=advanced-ads&utm_medium=link&utm_campaign=overview-add-ons-manual';
			if ( 'ga' !== Advanced_Ads_Tracking_Plugin::get_instance()->get_tracking_method() ) {

				// don’t show Tracking link if Analytics method is enabled.
				$add_ons['tracking']['desc'] = '<a href="' . admin_url( '/admin.php?page=advanced-ads-stats' ) . '">' . __( 'Visit your ad stats', 'advanced-ads' ) . '</a>';
			} else {
				$add_ons['tracking']['desc'] = '';
			}
			$add_ons['tracking']['installed'] = true;
			$add_ons['tracking']['order']     = 20;
			$installed_pro_plugins++;

			// remove the add-on.
			if ( $hide_activated ) {
				unset( $add_ons['tracking'] );
			}
		}

		// RESPONSIVE.
		if ( isset( $installed_plugins['advanced-ads-responsive/responsive-ads.php'] ) && ! class_exists( 'Advanced_Ads_Responsive_Plugin' ) ) { // is installed, but not active.
			$add_ons['responsive']['link']       = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=advanced-ads-responsive/responsive-ads.php&amp', 'activate-plugin_advanced-ads-responsive/responsive-ads.php' );
			$add_ons['responsive']['link_title'] = __( 'Activate now', 'advanced-ads' );
			$installed_pro_plugins++;
		} elseif ( class_exists( 'Advanced_Ads_Responsive_Plugin' ) ) {
			$add_ons['responsive']['link']      = ADVADS_URL . 'manual/responsive-ads-documentation/#utm_source=advanced-ads&utm_medium=link&utm_campaign=overview-add-ons-manual';
			$add_ons['responsive']['desc']      = '<a href="' . admin_url( 'admin.php?page=responsive-ads-list' ) . '">' . __( 'List of responsive ads by browser width', 'advanced-ads-responsive' ) . '</a>';
			$add_ons['responsive']['installed'] = true;
			$add_ons['responsive']['order']     = 20;
			$installed_pro_plugins++;

			// remove the add-on.
			if ( $hide_activated ) {
				unset( $add_ons['responsive'] );
			}
		}

		// GOOGLE AD MANAGER.
		if ( isset( $installed_plugins['advanced-ads-gam/advanced-ads-gam.php'] ) && ! class_exists( 'Advanced_Ads_Network_Gam' ) ) { // is installed, but not active.
			$add_ons['gam']['link']       = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=advanced-ads-gam/advanced-ads-gam.php&amp', 'activate-plugin_advanced-ads-gam/advanced-ads-gam.php' );
			$add_ons['gam']['link_title'] = __( 'Activate now', 'advanced-ads' );
			$installed_pro_plugins++;
		} elseif ( class_exists( 'Advanced_Ads_Network_Gam' ) ) {
			$add_ons['gam']['link']      = ADVADS_URL . 'manual/google-ad-manager-integration-manual/#utm_source=advanced-ads&utm_medium=link&utm_campaign=overview-add-ons-manual';
			$add_ons['gam']['desc']      = '';
			$add_ons['gam']['installed'] = true;
			$add_ons['gam']['order']     = 20;
			$installed_pro_plugins++;

			// remove the add-on.
			if ( $hide_activated ) {
				unset( $add_ons['gam'] );
			}
		}

		// STICKY.
		if ( isset( $installed_plugins['advanced-ads-sticky-ads/sticky-ads.php'] ) && ! class_exists( 'Advanced_Ads_Sticky_Plugin' ) ) { // is installed, but not active.
			$add_ons['sticky']['link']       = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=advanced-ads-sticky-ads/sticky-ads.php&amp', 'activate-plugin_advanced-ads-sticky-ads/sticky-ads.php' );
			$add_ons['sticky']['link_title'] = __( 'Activate now', 'advanced-ads' );
			$installed_pro_plugins++;
		} elseif ( class_exists( 'Advanced_Ads_Sticky_Plugin' ) ) {
			$add_ons['sticky']['link']      = ADVADS_URL . 'manual/sticky-ads-documentation/#utm_source=advanced-ads&utm_medium=link&utm_campaign=overview-add-ons-manual';
			$add_ons['sticky']['desc']      = '';
			$add_ons['sticky']['installed'] = true;
			$add_ons['sticky']['order']     = 20;
			$installed_pro_plugins++;

			// remove the add-on.
			if ( $hide_activated ) {
				unset( $add_ons['sticky'] );
			}
		}

		// LAYER.
		if ( isset( $installed_plugins['advanced-ads-layer/layer-ads.php'] ) && ! class_exists( 'Advanced_Ads_Layer_Plugin' ) ) { // is installed, but not active.
			$add_ons['layer']['link']       = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=advanced-ads-layer/layer-ads.php&amp', 'activate-plugin_advanced-ads-layer/layer-ads.php' );
			$add_ons['layer']['link_title'] = __( 'Activate now', 'advanced-ads' );
			$installed_pro_plugins++;
		} elseif ( class_exists( 'Advanced_Ads_Layer_Plugin' ) ) {
			$add_ons['layer']['link']      = ADVADS_URL . 'manual/popup-and-layer-ads-documentation/#utm_source=advanced-ads&utm_medium=link&utm_campaign=overview-add-ons-manual';
			$add_ons['layer']['desc']      = '';
			$add_ons['layer']['installed'] = true;
			$add_ons['layer']['order']     = 20;
			$installed_pro_plugins++;

			// remove the add-on.
			if ( $hide_activated ) {
				unset( $add_ons['layer'] );
			}
		}

		// SELLING ADS.
		if ( isset( $installed_plugins['advanced-ads-selling/advanced-ads-selling.php'] ) && ! class_exists( 'Advanced_Ads_Selling_Plugin' ) ) { // is installed, but not active.
			$add_ons['selling']['link']       = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=advanced-ads-selling/advanced-ads-selling.php&amp', 'activate-plugin_advanced-ads-selling/advanced-ads-selling.php' );
			$add_ons['selling']['link_title'] = __( 'Activate now', 'advanced-ads' );
			$installed_pro_plugins++;
		} elseif ( class_exists( 'Advanced_Ads_Selling_Plugin' ) ) {
			$add_ons['selling']['link']      = ADVADS_URL . 'manual/getting-started-selling-ads/#utm_source=advanced-ads&utm_medium=link&utm_campaign=overview-add-ons-manual';
			$add_ons['selling']['desc']      = '';
			$add_ons['selling']['installed'] = true;
			$add_ons['selling']['order']     = 20;
			$installed_pro_plugins++;

			// remove the add-on.
			if ( $hide_activated ) {
				unset( $add_ons['selling'] );
			}
		}

		// GEO TARGETING.
		if ( isset( $installed_plugins['advanced-ads-geo/advanced-ads-geo.php'] ) && ! class_exists( 'Advanced_Ads_Geo_Plugin' ) ) { // is installed, but not active.
			$add_ons['geo']['link']       = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=advanced-ads-geo/advanced-ads-geo.php&amp', 'activate-plugin_advanced-ads-geo/advanced-ads-geo.php' );
			$add_ons['geo']['link_title'] = __( 'Activate now', 'advanced-ads' );
			$installed_pro_plugins++;
		} elseif ( class_exists( 'Advanced_Ads_Geo_Plugin' ) ) {
			$add_ons['geo']['link']      = ADVADS_URL . 'manual/geo-targeting-condition/#utm_source=advanced-ads&utm_medium=link&utm_campaign=overview-add-ons-manual';
			$add_ons['geo']['desc']      = '';
			$add_ons['geo']['installed'] = true;
			$add_ons['geo']['order']     = 20;
			$installed_pro_plugins++;

			// remove the add-on.
			if ( $hide_activated ) {
				unset( $add_ons['geo'] );
			}
		}

		// SLIDER.
		if ( isset( $installed_plugins['advanced-ads-slider/slider.php'] ) && ! class_exists( 'Advanced_Ads_Slider_Plugin' ) ) { // is installed, but not active.
			$add_ons['slider']['link']       = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=advanced-ads-slider/slider.php&amp', 'activate-plugin_advanced-ads-slider/slider.php' );
			$add_ons['slider']['link_title'] = __( 'Activate now', 'advanced-ads' );
		} elseif ( class_exists( 'Advanced_Ads_Slider_Plugin' ) ) {
			$add_ons['slider']['link']      = ADVADS_URL . 'add-ons/slider/#utm_source=advanced-ads&utm_medium=link&utm_campaign=overview-add-ons-manual';
			$add_ons['slider']['desc']      = '';
			$add_ons['slider']['installed'] = true;
			$add_ons['slider']['order']     = 20;

			// remove the add-on.
			if ( $hide_activated ) {
				unset( $add_ons['slider'] );
			}
		}

		// add Genesis Ads, if Genesis based theme was detected.
		if ( defined( 'PARENT_THEME_NAME' ) && 'Genesis' === PARENT_THEME_NAME ) {
			$add_ons['genesis'] = array(
				'title'      => 'Genesis Ads',
				'desc'       => __( 'Use Genesis specific ad positions.', 'advanced-ads' ),
				'order'      => 2,
				'class'      => 'free',
				'link'       => wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=advanced-ads-genesis' ), 'install-plugin_advanced-ads-genesis' ),
				'link_title' => __( 'Install now', 'advanced-ads' ),
			);
			// handle install link as long as we can not be sure this is done by the Genesis plugin itself.
			if ( isset( $installed_plugins['advanced-ads-genesis/genesis-ads.php'] ) ) { // is installed (active or not).
				unset( $add_ons['genesis'] );
			}
		}

		// add Ads for WPBakery Page Builder (formerly Visual Composer), if VC was detected.
		if ( defined( 'WPB_VC_VERSION' ) ) {
			$add_ons['visual_composer'] = array(
				'title'      => 'Ads for WPBakery Page Builder (formerly Visual Composer)',
				'desc'       => __( 'Manage ad positions with WPBakery Page Builder (formerly Visual Composer).', 'advanced-ads' ),
				'order'      => 2,
				'class'      => 'free',
				'link'       => wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=ads-for-visual-composer' ), 'install-plugin_ads-for-visual-composer' ),
				'link_title' => __( 'Install now', 'advanced-ads' ),
			);
			// handle install link as long as we can not be sure this is done by the Genesis plugin itself.
			if ( isset( $installed_plugins['ads-for-visual-composer/advanced-ads-vc.php'] ) ) { // is installed (active or not).
				unset( $add_ons['visual_composer'] );
			}
		}

		// only show All Access Pitch if less than 2 add-ons exist.
		if ( $installed_pro_plugins < 2 ) {
			$add_ons['bundle'] = array(
				'title'        => 'All Access',
				'desc'         => __( 'Our best deal with all add-ons included.', 'advanced-ads' ),
				'link'         => ADVADS_URL . 'add-ons/all-access/#utm_source=advanced-ads&utm_medium=link&utm_campaign=overview-add-ons',
				'link_title'   => __( 'Get full access', 'advanced-ads' ),
				'link_primary' => true,
				'order'        => 0,
			);
		}

		// allow add-ons to manipulate the output.
		$add_ons = apply_filters( 'advanced-ads-overview-add-ons', $add_ons );

		uasort( $add_ons, array( 'self', 'sort_by_order' ) );

		?>
		<table class="widefat striped">
		<?php
		foreach ( $add_ons as $_addon ) :
			if ( isset( $_addon['installed'] ) ) {
				$link_title      = __( 'Visit the manual', 'advanced-ads' );
				$_addon['title'] = '<span class="dashicons dashicons-yes" style="color: green; font-size: 1.5em;"></span> ' . $_addon['title'];
			} else {
				$link_title = isset( $_addon['link_title'] ) ? $_addon['link_title'] : __( 'Get this add-on', 'advanced-ads' );
			}
			include ADVADS_BASE_PATH . 'admin/views/overview-addons-line.php';
		endforeach;
		?>
		</table>
		<?php
	}

	/**
	 * Sort by installed add-ons
	 *
	 * @param array $a argument a.
	 * @param array $b argument b.
	 *
	 * @return int
	 */
	private static function sort_by_order( $a, $b ) {
		return $a['order'] - $b['order'];
	}

}

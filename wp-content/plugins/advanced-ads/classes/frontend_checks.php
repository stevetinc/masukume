<?php

/**
 * Class Advanced_Ads_Frontend_Checks
 *
 * Handle Ad Health and other notifications and checks in the frontend.
 */
class Advanced_Ads_Frontend_Checks {
	/**
	 * True if 'the_content' was invoked, false otherwise.
	 *
	 * @var bool
	 */
	private $did_the_content = false;
	private $has_many_the_content = false;

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		$enabled = false;

		if ( ! is_admin()
			&& is_admin_bar_showing()
            && current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ) )
            && Advanced_Ads_Ad_Health_Notices::notices_enabled()
		) {
			add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 1000 );
			add_filter( 'the_content', array( $this, 'set_did_the_content' ) );
			add_filter( 'wp_footer', array( $this, 'footer_checks' ), -101 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_filter( 'advanced-ads-ad-select-args', array( $this, 'ad_select_args_callback' ) );
			$enabled = true;
		}

		if ( $enabled || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			add_filter( 'advanced-ads-ad-output', array( $this, 'after_ad_output' ), 10, 2 );
		}
	}

	/**
	 * Notify ads loaded with AJAX.
	 *
	 * @param array $args ad arguments.
	 * @return array $args
	 */
	public function ad_select_args_callback( $args ) {
		$args['frontend-check'] = true;
		return $args;
	}

	/**
	 * Enqueue scripts
	 * needs to add ajaxurl in case no other plugin is doing that
	 */
	public function enqueue_scripts() {
		if ( advads_is_amp() ) {
			return;
		}

		// we don’t have our own script, so we attach this information to jquery
		wp_localize_script( 'jquery', 'advads_frontend_checks',
			array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}

	/**
	 * List current ad situation on the page in the admin-bar.
	 *
	 * @param object $wp_admin_bar WP_Admin_Bar.
	 */
	public function add_admin_bar_menu( $wp_admin_bar ) {
		global $wp_the_query, $post, $wp_scripts;

		$options = Advanced_Ads_Plugin::get_instance()->options();

		// load AdSense related options.
		$adsense_options = Advanced_Ads_AdSense_Data::get_instance()->get_options();

		// check if jQuery is loaded in the header
		// Hidden, will be shown using js.
		// message removed after we fixed all issues we know of.
		/*$wp_admin_bar->add_node( array(
			'parent' => 'advanced_ads_ad_health',
			'id'    => 'advanced_ads_ad_health_jquery',
			'title' => __( 'jQuery not in header', 'advanced-ads' ),
			'href'  => ADVADS_URL . 'manual/common-issues#frontend-issues-javascript',
			'meta'   => array(
				'class' => 'hidden advanced_ads_ad_health_warning',
				'target' => '_blank'
			)
		) );*/

		// check if AdSense loads Auto Ads ads
		// Hidden, will be shown using js.
		if( ! isset( $adsense_options['violation-warnings-disable'] ) ) {
			$nodes[] = array( 'type' => 2, 'data' => array(
				'parent' => 'advanced_ads_ad_health',
				'id'    => 'advanced_ads_autoads_displayed',
				'title' => __( 'Random AdSense ads', 'advanced-ads' ),
				'href'  => ADVADS_URL . 'adsense-in-random-positions-auto-ads/#utm_source=advanced-ads&utm_medium=link&utm_campaign=frontend-autoads-ads',
				'meta'   => array(
					'class' => 'hidden',
					'target' => '_blank'
				)
			) );
		}

		// check if current user was identified as a bot.
		if( Advanced_Ads::get_instance()->is_bot() ) {
			$nodes[] = array( 'type' => 1, 'data' => array(
				'parent' => 'advanced_ads_ad_health',
				'id'    => 'advanced_ads_user_is_bot',
				'title' => __( 'You look like a bot', 'advanced-ads' ),
				'href'  => ADVADS_URL . 'manual/ad-health/#look-like-bot',
				'meta'   => array(
					'class' => 'advanced_ads_ad_health_warning',
					'target' => '_blank'
				)
			) );
		}

		// check if an ad blocker is enabled
		// Hidden, will be shown using js.
		$nodes[] = array( 'type' => 2, 'data' => array(
			'parent' => 'advanced_ads_ad_health',
			'id'     => 'advanced_ads_ad_health_adblocker_enabled',
			'title'  => __( 'Ad blocker enabled', 'advanced-ads' ),
			'meta'   => array(
				'class' => 'hidden advanced_ads_ad_health_warning',
				'target' => '_blank'
			)
		) );

		if ( $wp_the_query->is_singular() ) {
			if ( $this->has_the_content_placements() ) {
				$nodes[] = array( 'type' => 2, 'data' => array(
					'parent' => 'advanced_ads_ad_health',
					'id'    => 'advanced_ads_ad_health_the_content_not_invoked',
					'title' => sprintf( __( '<em>%s</em> filter does not exist', 'advanced-ads' ), 'the_content' ),
					'href'  => ADVADS_URL . 'manual/ads-not-showing-up/?utm_source=advanced-ads&utm_medium=link&utm_campaign=adhealth-content-filter-missing#the_content-filter-missing',
					'meta'   => array(
						'class' => 'hidden advanced_ads_ad_health_warning',
						'target' => '_blank'
					)
				) );
			}

			if ( ! empty( $post->ID ) ) {
				$ad_settings = get_post_meta( $post->ID, '_advads_ad_settings', true );

				if ( ! empty( $ad_settings['disable_ads'] ) ) {
					$nodes[] = array( 'type' => 1, 'data' => array(
						'parent' => 'advanced_ads_ad_health',
						'id'    => 'advanced_ads_ad_health_disabled_on_page',
						'title' => __( 'Ads are disabled on this page', 'advanced-ads' ),
						'href'  => get_edit_post_link( $post->ID ) . '#advads-ad-settings',
						'meta'   => array(
							'class' => 'advanced_ads_ad_health_warning',
							'target' => '_blank'
						)
					) );
				}

				if ( ! empty( $ad_settings['disable_the_content'] ) ) {
					$nodes[] = array( 'type' => 1, 'data' => array(
						'parent' => 'advanced_ads_ad_health',
						'id'    => 'advanced_ads_ad_health_disabled_in_content',
						'title' => __( 'Ads are disabled in the content of this page', 'advanced-ads' ),
						'href'  => get_edit_post_link( $post->ID ) . '#advads-ad-settings',
						'meta'   => array(
							'class' => 'advanced_ads_ad_health_warning',
							'target' => '_blank'
						)
					) );
				}
			} else {
				$nodes[] = array( 'type' => 1, 'data' => array(
					'parent' => 'advanced_ads_ad_health',
					'id'    => 'advanced_ads_ad_health_post_zero',
					'title' => __( 'the current post ID is 0 ', 'advanced-ads' ),
					'href'  => ADVADS_URL . 'manual/ad-health/#post-id-0',
					'meta'   => array(
						'class' => 'advanced_ads_ad_health_warning',
						'target' => '_blank'
					)
				) );
			}
		}

		if ( ! empty( $options['disabled-ads']['all'] ) ) {
			$nodes[] = array( 'type' => 1, 'data' => array(
				'parent' => 'advanced_ads_ad_health',
				'id'    => 'advanced_ads_ad_health_no_all',
				'title' => __( 'Ads are disabled on all pages', 'advanced-ads' ),
				'href'  => admin_url( 'admin.php?page=advanced-ads-settings' ),
				'meta'   => array(
					'class' => 'advanced_ads_ad_health_warning',
					'target' => '_blank'
				)
			) );
		}

		if ( $wp_the_query->is_404() && ! empty( $options['disabled-ads']['404'] ) ) {
			$nodes[] = array( 1, array(
				'parent' => 'advanced_ads_ad_health',
				'id'     => 'advanced_ads_ad_health_no_404',
				'title'  => __( 'Ads are disabled on 404 pages', 'advanced-ads' ),
				'href'   => admin_url( 'admin.php?page=advanced-ads-settings' ),
				'meta'   => array(
					'class'  => 'advanced_ads_ad_health_warning',
					'target' => '_blank'
				)
			) );
		}

		if ( ! $wp_the_query->is_singular() && ! empty( $options['disabled-ads']['archives'] ) ){
			$nodes[] = array( 'type' => 1, 'data' => array(
				'parent' => 'advanced_ads_ad_health',
				'id'     => 'advanced_ads_ad_health_no_archive',
				'title'  => __( 'Ads are disabled on non singular pages', 'advanced-ads' ),
				'href'   => admin_url( 'admin.php?page=advanced-ads-settings' ),
				'meta'   => array(
					'class'  => 'advanced_ads_ad_health_warning',
					'target' => '_blank'
				)
			) );
		}

		$nodes[] = array( 'type' => 2, 'data' => array(
			'parent' => 'advanced_ads_ad_health',
			'id'     => 'advanced_ads_ad_health_has_http',
			'title'  => sprintf( '%s %s',
				__( 'Your website is using HTTPS, but the ad code contains HTTP and might not work.', 'advanced-ads' ),
				sprintf( __( 'Ad IDs: %s', 'advanced-ads'  ), '<i></i>' )
			),
			'href'   => ADVADS_URL . 'manual/ad-health/?utm_source=advanced-ads&utm_medium=link&utm_campaign=adhealth-https-ads#https-ads',
			'meta'   => array(
				'class'  => 'hidden advanced_ads_ad_health_warning advanced_ads_ad_health_has_http',
				'target' => '_blank'
			)
		) );

		$nodes[] = array( 'type' => 2, 'data' => array(
			'parent' => 'advanced_ads_ad_health',
			'id'     => 'advanced_ads_ad_health_incorrect_head',
			'title'  => sprintf( __( 'Visible ads should not use the Header placement: %s', 'advanced-ads' ), '<i></i>' ),
			'href'   => ADVADS_URL . 'manual/ad-health/?utm_source=advanced-ads&utm_medium=link&utm_campaign=adhealth-visible-ad-in-header#header-ads',
			'meta'   => array(
				'class'  => 'hidden advanced_ads_ad_health_warning advanced_ads_ad_health_incorrect_head',
				'target' => '_blank'
			)
		) );

		// warn if an AdSense ad seems to be hidden
		if( ! isset( $adsense_options['violation-warnings-disable'] ) ) {
			$nodes[] = array( 'type' => 2, 'data' => array(
				'parent' => 'advanced_ads_ad_health',
				'id'    => 'advanced_ads_ad_health_hidden_adsense',
				'title' => sprintf( '%s: %s. %s',
					__( 'AdSense violation', 'advanced-ads' ),
					__( 'Ad is hidden', 'advanced-ads' ),
					sprintf( __( 'IDs: %s', 'advanced-ads'  ), '<i></i>' )
				),
				'href'  => ADVADS_URL . 'manual/ad-health/?utm_source=advanced-ads&utm_medium=link&utm_campaign=adhealth-frontend-adsense-hidden#adsense-hidden',
				'meta'   => array(
					'class' => 'hidden advanced_ads_ad_health_warning advanced_ads_ad_health_hidden_adsense',
					'target' => '_blank'
				)
			) );
		}

		$nodes[] = array( 'type' => 2, 'data' => array(
			'parent' => 'advanced_ads_ad_health',
			'id'    => 'advanced_ads_ad_health_floated_responsive_adsense',
			'title' => sprintf( __( 'The following responsive AdSense ads are not showing up: %s', 'advanced-ads'  ), '<i></i>' ),
			'href'	=> ADVADS_URL . 'manual/ad-health/?utm_source=advanced-ads&utm_medium=link&utm_campaign=adhealth-adsense-responsive-not-showing#The_following_responsive_AdSense_ads_arenot_showing_up',
			'meta'   => array(
				'class' => 'hidden advanced_ads_ad_health_warning advanced_ads_ad_health_floated_responsive_adsense',
				'target' => '_blank'
			)
		) );

		// warn if consent was not given
		$privacy_state = Advanced_Ads_Privacy::get_instance()->get_state();
		if( 'not_needed' !== $privacy_state ) {
			$nodes[] = array( 'type' => 2, 'data' => array(
				'parent' => 'advanced_ads_ad_health',
				'id'    => 'advanced_ads_ad_health_consent_missing',
				'title' => __( 'Consent not given', 'advanced-ads' ),
				'href'  => admin_url( 'admin.php?page=advanced-ads-settings#top#privacy' ),
				'meta'   => array(
					'class' => 'hidden advanced_ads_ad_health_warning advanced_ads_ad_health_consent_missing',
					'target' => '_blank'
				)
			) );
		}

		$nodes[] = array( 'type' => 3, 'data' => array(
			'parent' => 'advanced_ads_ad_health',
			'id'    => 'advanced_ads_ad_health_gam_debug',
			'title' => __( 'Debug Google Ad Manager', 'advanced-ads' ),
			'href'  => esc_url( add_query_arg( 'google_force_console', '1' ) ),
			'meta'   => array(
				'class' => 'hidden advanced_ads_ad_health_gam_debug_link',
			)
		) );

		// search for AdSense Verification and Auto ads code.
		$nodes[] = array( 'type' => 3, 'data' => array(
			'parent' => 'advanced_ads_ad_health',
			'id'    => 'advanced_ads_ad_health_auto_ads_found',
			'title' => __( 'Auto ads code found', 'advanced-ads' ),
			'href'	=> ADVADS_URL . 'manual/ad-health/?utm_source=advanced-ads&utm_medium=link&utm_campaign=adhealth-adsense-auto-ads-found#Auto_ads_code_found',
			'meta'   => array(
				'class' => 'hidden advanced_ads_ad_health_auto_ads_found',
				'target' => '_blank',
			),
		) );

		// link to highlight ads and jump from one ad to the next.
		$nodes[] = array( 'type' => 3, 'amp' => false, 'data' => array(
			'parent' => 'advanced_ads_ad_health',
			'id'     => 'advanced_ads_ad_health_highlight_ads',
			'title'  => __( 'highlight ads', 'advanced-ads' ),
			'meta'   => array(
				'class' => 'advanced_ads_ad_health_highlight_ads',
			),
		) );

		/**
		 * Add new node.
		 *
		 * @param array $node An array that contains:
		 *      'type' => 1 - warning, 2 - hidden warning that will be shown using JS, 3 - info message
		 *      'data': @see WP_Admin_Bar->add_node
		 * @param object  $wp_admin_bar
		 */
		$nodes = apply_filters( 'advanced-ads-ad-health-nodes', $nodes );

		usort( $nodes, array( $this, 'sort_nodes' ) );

		// load number of already detected notices.
		$notices = Advanced_Ads_Ad_Health_Notices::get_number_of_notices();

		if ( ! advads_is_amp() ) {
			$warnings = 0; // Will be updated using JS.
		} else {
			$warnings = $this->count_visible_warnings( $nodes, array( 1 ) );
		}

		$issues = $warnings;

		$this->add_header_nodes( $wp_admin_bar, $issues, $notices );

		foreach ( $nodes as $node ) {
			if ( isset( $node['data'] ) ) {
				$wp_admin_bar->add_node( $node['data'] );
			}
		}

		$this->add_footer_nodes( $wp_admin_bar, $issues );
	}

	/**
	 * Count visible notices and warnings.
	 *
	 * @param array $nodes Nodes to add.
	 * @param array $types Warning types.
	 */
	private function count_visible_warnings( $nodes, $types = array() ) {
		$warnings = 0;
		foreach ( $nodes as $node ) {
			if ( ! isset( $node['type'] ) || ! isset( $node['data'] ) ) { continue; }
			if ( in_array( $node['type'], $types ) ) {
				$warnings++;
			}
		}
		return $warnings;
	}

	/**
	 * Add header nodes.
	 *
	 * @param object $wp_admin_bar WP_Admin_Bar object.
	 * @param int    $issues Number of all issues.
	 * @param int    $notices Number of notices.
	 */
	private function add_header_nodes( $wp_admin_bar, $issues, $notices ) {
		$wp_admin_bar->add_node( array(
			'id'	    => 'advanced_ads_ad_health',
			'title'	    => __( 'Ad Health', 'advanced-ads' ) . '&nbsp;<span class="advanced-ads-issue-counter">' . $issues . '</span>',
			'parent'    => false,
			'href'	    => admin_url( 'admin.php?page=advanced-ads' ),
			'meta' => array(
				'class' => $issues ? 'advads-adminbar-is-warnings': '',
			),
		) );


		// show that there are backend notices
		if ( $notices ) {
			$wp_admin_bar->add_node( array(
				'parent' => 'advanced_ads_ad_health',
				'id'    => 'advanced_ads_ad_health_more',
				'title' => sprintf(__( 'Show %d more notifications', 'advanced-ads' ), absint( $notices ) ),
				'href'  => admin_url( 'admin.php?page=advanced-ads' ),
			) );
		}
	}

	/**
	 * Add footer nodes.
	 *
	 * @param obj $wp_admin_bar WP_Admin_Bar object.
	 * @param int $issues Number of all issues.
	 */
	private function add_footer_nodes( $wp_admin_bar, $issues ) {
		if ( ! $issues ) {
			$wp_admin_bar->add_node( array(
				'parent' => 'advanced_ads_ad_health',
				'id'    => 'advanced_ads_ad_health_fine',
				'title' => __( 'Everything is fine', 'advanced-ads' ),
				'href'  => false,
				'meta'   => array(
					'target' => '_blank',
				)
			) );
		}

		$wp_admin_bar->add_node( array(
			'parent' => 'advanced_ads_ad_health',
			'id'    => 'advanced_ads_ad_health_support',
			'title' => __( 'Get help', 'advanced-ads' ),
			'href'  => Advanced_Ads_Plugin::support_url( '#utm_source=advanced-ads&utm_medium=link&utm_campaign=health-support' ),
			'meta'   => array(
				'target' => '_blank',
			)
		) );
	}

	/**
	 * Filter out nodes intended to AMP pages only.
	 *
	 * @param array $nodes Nodes to add.
	 * @return array $nodes Nodes to add.
	 */
	private function filter_nodes( $nodes ) {
		return $nodes;
	}

	/**
	 * Sort nodes.
	 */
	function sort_nodes( $a, $b ) {
		if ( ! isset( $a['type'] ) || ! isset( $b['type'] ) ) {
			return 0;
		}
		if ( $a['type'] == $b['type'] ) {
			return 0;
		}
		return ( $a['type'] < $b['type'] ) ? -1 : 1;
	}

	/**
	 * Set variable to 'true' when 'the_content' filter is invoked.
	 *
	 * @param string $content
	 * @return string $content
	 */
	public function set_did_the_content( $content ) {
		if ( ! $this->did_the_content ) {
			$this->did_the_content = true;
		}

		if ( Advanced_Ads::get_instance()->has_many_the_content() ) {
			$this->has_many_the_content = true;
		}
		return $content;
	}

	/**
	 * Check conditions and display warning.
	 * Conditions:
	 *		AdBlocker enabled,
	 *		jQuery is included in header
	 *		AdSense Quick Start ads are running
	 */
	public function footer_checks() {
		ob_start();
		?><!-- Advanced Ads: <?php esc_html_e( 'the following code is used for automatic error detection and only visible to admins', 'advanced-ads' ); ?>-->
		<style>#wp-admin-bar-advanced_ads_ad_health .hidden { display: none; }
		#wp-admin-bar-advanced_ads_ad_health-default a:after { content: "\25BA"; margin-left: .5em; font-size: smaller; }
		#wp-admin-bar-advanced_ads_ad_health-default .advanced_ads_ad_health_highlight_ads div:before { content: "\f177"; margin-right: .2em; line-height: 1em; padding: 0.2em 0 0; color: inherit; }
		#wp-admin-bar-advanced_ads_ad_health-default .advanced_ads_ad_health_highlight_ads div:hover { color: #00b9eb; cursor: pointer; }
		#wpadminbar .advanced-ads-issue-counter { background-color: #d54e21; display: none; padding: 1px 7px 1px 6px!important; border-radius: 50%; color: #fff; }
		#wpadminbar .advads-adminbar-is-warnings .advanced-ads-issue-counter { display: inline; }
		.advanced-ads-highlight-ads { outline:4px solid blue !important; }

		.advads-frontend-notice { display: none; position: fixed; top: 0; z-index: 1000; left: 50%; max-width: 500px; margin-left: -250px; padding: 30px 10px 10px 10px; border: 0px solid #0074a2; border-top: 0; border-radius: 0px 0px 5px 5px; box-shadow: 0px 0px 15px rgba(0,0,0,0.3); background: #ffffff; background: rgba(255,255,255,0.95); font-size: 16px; font-family: Arial, Verdana, sans-serif; line-height: 1.5em; color: #444444; }

		.advads-frontend-notice a, .advads-frontend-notice a:link { color: #0074a2; text-decoration: none; }
		.advads-frontend-notice ul { }
		.advads-frontend-notice ul li { line-height: 1.5em; }

		.advads-frontend-notice .advads-close-notice { position: absolute; top: 5px; right: 0; display: block; font-size: 20px; width: 30px; height: 30px; line-height: 30px; text-decoration: none; text-align: center; font-weight: bold; color: #444444; cursor: pointer; }
		.advads-frontend-notice .advads-notice-var1 { font-size: 14px; font-style: italic; text-align: center; text-align: 1.3em; }
		.advads-frontend-notice .advads-frontend-notice-choice { text-align: center; }
		.advads-frontend-notice .advads-frontend-notice-choice:after { display: block; content: " "; clear: both; }

		/* CSS Smilies */
		.advads-smiley { position: relative; display: inline-block; border: 4px solid #0074a2; border-radius: 50px; width: 65px; height: 65px; background: #ffffff; cursor: pointer; margin: 0px 15px 5px 0px; }
		.advads-smiley:hover {  transform: scale(0.90); transition: all linear 0.3s; }
		.advads-smiley .eye { display: block; position: absolute; top: 25%; width: 18%; height: 18%; background: #0074a2; border-radius: 50%; }
		.advads-smiley .eye1 { left: 20%; }
		.advads-smiley .eye2 { right: 20%; }
		.advads-smiley .mouth { display: block; position: absolute; left: 20%; top: 15%; width: 60%; height: 60%; border-bottom: 5px solid #0074a2; background: none; }

		/* CSS smiley: negative */
		.advads-smiley-negative { /* border-color: #9a2d18; */ }
		.advads-smiley-negative .mouth { top: 50%; border: 5px solid #0074a2; width: 60%px; height: 60%; transform: rotate(-45deg); border-bottom-color: transparent; border-left-color: transparent; border-radius: 50%; }

		/* CSS smiley: positive */
		.advads-smiley-positive { /* border-color: #1e610f; */ }
		.advads-smiley-positive .mouth { top: 25%; border: 5px solid #0074a2; width: 60%px; height: 60%; transform: rotate(-45deg); border-top-color: transparent; border-right-color: transparent; border-radius: 50%; }

		/* CSS smiley: neutral */
		.advads-smiley-laugh .mouth { top: 25%; border-radius: 50%;  }

		@media screen and (max-width: 510px) {
			 .advads-frontend-notice { left: 0; width: 100%; margin-left: 0; }
			 .advads-smiley { width: 45px; height: 45px; }
		}
		</style>
		<?php echo ob_get_clean();

		if ( advads_is_amp() ) {
			return;
		}

		$adsense_options = Advanced_Ads_AdSense_Data::get_instance()->get_options();
		ob_start();
		?><script type="text/javascript" src="<?php echo ADVADS_BASE_URL . 'admin/assets/js/advertisement.js' ?>"></script>
		<script>
		var advanced_ads_frontend_checks = {
			showCount: function() {
				try {
					// Count only warnings that have the 'advanced_ads_ad_health_warning' class.
					var warning_count = document.querySelectorAll( '.advanced_ads_ad_health_warning:not(.hidden)' ).length;
					var fine_item = document.getElementById( 'wp-admin-bar-advanced_ads_ad_health_fine' );
				} catch ( e ) { return; }

				if ( warning_count ) {
					var header = document.querySelector( '#wp-admin-bar-advanced_ads_ad_health > a' );

					if ( fine_item ) {
						// Hide 'fine' item.
						fine_item.className += ' hidden';
					}

					if ( header ) {
						header.innerHTML = header.innerHTML.replace(/<span class="advanced-ads-issue-counter">\d*<\/span>/, '') + '<span class="advanced-ads-issue-counter">' + warning_count + '</span>';
						// add class
						header.className += ' advads-adminbar-is-warnings';
					}
				}
			},

			array_unique: function( array ) {
				var r= [];
				for ( var i = 0; i < array.length; i++ ) {
					if ( r.indexOf( array[ i ] ) === -1 ) {
						r.push( array[ i ] );
					}
				}
				return r;
			},

			/**
			 * Add item to Ad Health node.
			 *
			 * @param string selector Selector of the node.
			 * @param string/array item item(s) to add.
			 */
			add_item_to_node: function( selector, item ) {
				if ( typeof item === 'string' ) {
					item = item.split();
				}
				var selector = document.querySelector( selector );
				if ( selector ) {
					selector.className = selector.className.replace( 'hidden', '' );
					selector.innerHTML = selector.innerHTML.replace( /(<i>)(.*?)(<\/i>)/, function( match, p1, p2, p3 ) {
						p2 = ( p2 ) ? p2.split( ', ' ) : [];
						p2 = p2.concat( item );
						p2 = advanced_ads_frontend_checks.array_unique( p2 );
						return p1 + p2.join( ', ' ) + p3;
					} );
					advanced_ads_frontend_checks.showCount();
				}
			},

			/**
			 * Add item to Ad Health notices in the backend
			 *
			 * @param key of the notice
			 * @param attr
			 * @returns {undefined}
			 */
			add_item_to_notices: function( key, attr = '' ) {
				var cookie = advads.get_cookie( 'advanced_ads_ad_health_notices' );
				if( cookie ){
				    advads_cookie_notices = JSON.parse( cookie );
				} else {
				    advads_cookie_notices = new Array();
				}
				// stop if notice was added less than 1 hour ago
				if( 0 <= advads_cookie_notices.indexOf( key ) ){
					return;
				}
				var query = {
				    action: 'advads-ad-health-notice-push',
				    key: key,
				    attr: attr,
				    nonce: '<?php echo wp_create_nonce('advanced-ads-ad-health-ajax-nonce'); ?>'
				};
				// send query
				// update notices and cookie
				jQuery.post( advads_frontend_checks.ajax_url, query, function (r) {
					advads_cookie_notices.push( key );
					var notices_str = JSON.stringify( advads_cookie_notices );
					advads.set_cookie_sec( 'advanced_ads_ad_health_notices', notices_str, 3600 ); // 1 hour
				});
			},
			/**
			 * Update status of frontend notices
			 *
			 * @param key of the notice
			 * @param attr
			 * @returns {undefined}
			 */
			update_frontend_notices: function( key, attr = '' ) {
				var query = {
					action: 'advads-ad-frontend-notice-update',
					key: key,
					attr: attr,
					nonce: '<?php echo wp_create_nonce('advanced-ads-frontend-notice-nonce'); ?>'
				};
				// send query
				jQuery.post( advads_frontend_checks.ajax_url, query, function (r) {})
				// close message when done.
				.done(function() {
					content_obj.slideUp();
				});
			},

			/**
			 * Search for hidden AdSense.
			 *
			 * @param string context Context for search.
			 */
			advads_highlight_hidden_adsense: function( context ) {
				if ( ! context ) {
					 context = 'html'
				}
				if ( window.jQuery ) {
					var advads_ad_health_check_adsense_hidden_ids = [];
					var responsive_zero_width = [];
					jQuery( 'ins.adsbygoogle', context ).each( function() {
						// The parent container is invisible.
						if( ! jQuery( this ).parent().is(':visible') ){
						    // advads_ad_health_check_adsense_hidden_ids.push( this.dataset.adSlot );
                            // advanced_ads_frontend_checks.add_item_to_notices( 'adsense_hidden', { mode: 'add', append_key: this.dataset.adSlot, append_text: ' AdSense ID: ' + this.dataset.adSlot + ' URL: ' + window.location + ', ' + jQuery( document ).width() + 'px' } );
						}

						// Zero width, perhaps because a parent container is floated
						if ( jQuery( this ).attr( 'data-ad-format' ) && 0 === jQuery( this ).width() ) {
							responsive_zero_width.push( this.dataset.adSlot );
						}
					});
					if( advads_ad_health_check_adsense_hidden_ids.length ){
						// advanced_ads_frontend_checks.add_item_to_node( '.advanced_ads_ad_health_hidden_adsense', advads_ad_health_check_adsense_hidden_ids );
					}
					if ( responsive_zero_width.length ) {
						advanced_ads_frontend_checks.add_item_to_node( '.advanced_ads_ad_health_floated_responsive_adsense', responsive_zero_width );
					}
				}
			}
		};

		(function(d, w) {
				// var not_head_jQuery = typeof jQuery === 'undefined';

				var addEvent = function( obj, type, fn ) {
					if ( obj.addEventListener )
						obj.addEventListener( type, fn, false );
					else if ( obj.attachEvent )
						obj.attachEvent( 'on' + type, function() { return fn.call( obj, window.event ); } );
				};

				// highlight ads that use Advanced Ads placements or AdSense Auto ads
				function highlight_ads() {
					/**
					 * Selectors:
					 * Placement container: div[id^="<?php echo Advanced_Ads_Plugin::get_instance()->get_frontend_prefix();?>"]
					 * AdSense Auto ads: 'google-auto-placed'
					 */
					try {
					    var ad_wrappers = document.querySelectorAll('div[id^="<?php echo Advanced_Ads_Plugin::get_instance()->get_frontend_prefix();?>"],.google-auto-placed')
					} catch ( e ) { return; }
				    for ( i = 0; i < ad_wrappers.length; i++ ) {
				            ad_wrappers[i].title = ad_wrappers[i].className;
				            ad_wrappers[i].className += ' advanced-ads-highlight-ads';
				            // in case we want to remove it later
				            // ad_wrappers[i].className = ad_wrappers[i].className.replace( 'advanced-ads-highlight-ads', '' );
				    }
				}

				advanced_ads_ready( function() {
					var adblock_item = d.getElementById( 'wp-admin-bar-advanced_ads_ad_health_adblocker_enabled' );
					// jQuery_item = d.getElementById( 'wp-admin-bar-advanced_ads_ad_health_jquery' ),

					// handle click on the highlight_ads link
					var highlight_link = d.getElementById( 'wp-admin-bar-advanced_ads_ad_health_highlight_ads' );
					addEvent( highlight_link, 'click', highlight_ads );

					if ( adblock_item && typeof advanced_ads_adblocker_test === 'undefined' ) {
						// show hidden item
						adblock_item.className = adblock_item.className.replace( /hidden/, '' );
					}

					/* if ( jQuery_item && not_head_jQuery ) {
						// show hidden item
						jQuery_item.className = jQuery_item.className.replace( /hidden/, '' );
					}*/

					<?php if ( ! $this->did_the_content ) : ?>
						var the_content_item = d.getElementById( 'wp-admin-bar-advanced_ads_ad_health_the_content_not_invoked' );
						if ( the_content_item ) {
							the_content_item.className = the_content_item.className.replace( /hidden/, '' );
						}
					<?php endif; ?>

					advanced_ads_frontend_checks.showCount();
				});

				<?php if( ! isset( $adsense_options['violation-warnings-disable'] ) ) : ?>
					// show warning if AdSense ad is hidden
					// show hint if AdSense Auto ads are enabled
					setTimeout( function(){
						advanced_ads_ready( advanced_ads_frontend_checks.advads_highlight_hidden_adsense );
						advanced_ads_ready( advads_highlight_adsense_auto_ads );
					}, 2000 );

					// highlight AdSense Auto Ads ads 3 seconds after site loaded
					setTimeout( function(){
						advanced_ads_ready( advads_highlight_adsense_autoads );
					}, 3000 );
					function advads_highlight_adsense_autoads(){
						if ( ! window.jQuery ) {
							window.console && window.console.log( 'Advanced Ads: jQuery not found. Some Ad Health warnings will not be displayed.' );
							return;
						}
						var autoads_ads = document.querySelectorAll('.google-auto-placed');
						var autoads_code_enabled = document.querySelectorAll('#wp-admin-bar-advanced_ads_ad_health_auto_ads_found:not(.hidden)').length;
						<?php /* jQuery( '<p class="advads-autoads-hint" style="background-color:#0085ba;color:#fff;font-size:0.8em;padding:5px;"><?php
							printf(__( 'This ad was automatically placed here by AdSense. <a href="%s" target="_blank" style="color:#fff;border-color:#fff;">Click here to learn more</a>.', 'advanced-ads' ), ADVADS_URL . 'adsense-in-random-positions-auto-ads/#utm_source=advanced-ads&utm_medium=link&utm_campaign=frontend-autoads-ads' );
							?></p>' ).prependTo( autoads_ads ); */ ?>
						// show Auto Ads warning in Ad Health bar if relevant
						if( autoads_ads.length ){
							var advads_autoads_link = document.querySelector( '#wp-admin-bar-advanced_ads_autoads_displayed.hidden' );
							if ( advads_autoads_link ) {
								advads_autoads_link.className = advads_autoads_link.className.replace( 'hidden', '' );
							}
							advanced_ads_frontend_checks.showCount();
						}
					}

					// inform the user that AdSense Auto ads code was found
					function advads_highlight_adsense_auto_ads() {
						if (window.jQuery) {
							if (/script[^>]+data-ad-client|enable_page_level_ads:\s*true/.test(jQuery('head').html())) {
								var advads_autoads_code_link = document.querySelector('#wp-admin-bar-advanced_ads_ad_health_auto_ads_found');
								advads_autoads_code_link.className = advads_autoads_code_link.className.replace('hidden', '');
							}
						}
					}
					// show notice
					// @param notice
					function advads_frontend_notice( notice = '' ){
						var content = '';
						switch ( notice ) {
							case 'auto-ads-with-ads' :
								var autoads_ads = jQuery(document).find('.google-auto-placed');
								<?php
								$current_user          = wp_get_current_user();
								$current_user_nicename = isset( $current_user->user_nicename ) ? $current_user->user_nicename : 'admin';
								?>
								content = '' +
									'<p style="text-align: center;"><strong><?php printf( __( 'Hi %s', 'advanced-ads'), $current_user_nicename ); ?></strong><br><?php printf( __( 'Advanced Ads detected AdSense Auto ads (%sx) on this page.', 'advanced-ads' ), 'AUTO_ADS_NUM' ); ?><br><?php esc_attr_e( 'Is that correct?', 'advanced-ads' ); ?></p>' +
									'<div class="advads-frontend-notice-choice">' +
									'<span class="advads-close advads-smiley advads-smiley-positive" title="<?php esc_attr_e( 'All is fine', 'advanced-ads' ); ?>"><i class="eye eye1"></i><i class="eye eye2"></i><i class="mouth"></i></span>' +
									'<span class="advads-choice-negative advads-smiley" title="<?php esc_attr_e( 'Something is off', 'advanced-ads' ); ?>"><i class="eye eye1"></i><i class="eye eye2"></i><i class="mouth"></i></span>' +
									'</div>' +
									'<p class="advads-notice-var1"><?php esc_attr_e( 'PS: This is a one-time check from your friendly Advanced Ads plugin. It is only visible to you.', 'advanced-ads' ); ?></p>';
								// dynamically add the number of Auto ads found.
								content = content.replace( 'AUTO_ADS_NUM', autoads_ads.length );
								break;
							case 'auto-ads-without-ads' :
								<?php
								$current_user = wp_get_current_user();
								$current_user_nicename = isset( $current_user->user_nicename ) ? $current_user->user_nicename : 'admin';
								?>
								content = '' +
									'<p style="text-align: center;"><strong><?php printf( __( 'Hi %s', 'advanced-ads'), $current_user_nicename ); ?></strong><br><?php printf( __( 'Advanced Ads detected the AdSense Auto ads code and <strong>no ads on this page</strong>.', 'advanced-ads' ) ); ?><br/><?php esc_attr_e( 'Is that correct?', 'advanced-ads' ); ?></p>' +
									'<div class="advads-frontend-notice-choice">' +
									'<span class="advads-close advads-smiley advads-smiley-positive" title="<?php esc_attr_e( 'This is fine', 'advanced-ads' ); ?>"><i class="eye eye1"></i><i class="eye eye2"></i><i class="mouth"></i></span>' +
									'<span class="advads-choice-negative advads-smiley" title="<?php esc_attr_e( 'I expected something else', 'advanced-ads' ); ?>"><i class="eye eye1"></i><i class="eye eye2"></i><i class="mouth"></i></span>' +
									'</div>' +
									'<p class="advads-notice-var1"><?php esc_attr_e( 'PS: This is a one-time check from your friendly Advanced Ads plugin. It is only visible to you.', 'advanced-ads' ); ?></p>';
								break;
							case 'auto-ads-with-ads-help' :
								content = '<p style="text-align: center;"><?php esc_attr_e( 'Just click on your problem to learn more from our knowledge base.', 'advanced-ads' ); ?></p>' +
									'<ul><li><a href="<?php echo esc_url( ADVADS_URL ); ?>adsense-in-random-positions-auto-ads/?utm_source=advanced-ads&utm_medium=link&utm_campaign=frontend-auto-ads-disable#How_to_disable_Auto_Ads" target="_blank"><?php esc_attr_e( 'I want to disable AdSense Auto ads', 'advanced-ads' ); ?></a></li>' +
									'<li><a href="<?php echo esc_url( ADVADS_URL ); ?>manual/adsense-auto-ads-not-showing/?utm_source=advanced-ads&utm_medium=link&utm_campaign=frontend-auto-ads" target="_blank"><?php esc_attr_e( 'I don’t see any Auto ads', 'advanced-ads' ); ?></a></li>' +
									'<li><a href="<?php echo esc_url( ADVADS_URL ); ?>manual/adsense-auto-ads-not-showing/?utm_source=advanced-ads&utm_medium=link&utm_campaign=frontend-auto-ads-blank#Auto_ads_stay_blank" target="_blank"><?php esc_attr_e( 'I only see blank space', 'advanced-ads' ); ?></a></li>' +
									'<li><a href="<?php echo esc_url( ADVADS_URL ); ?>place-adsense-ad-unit-manually/?utm_source=advanced-ads&utm_medium=link&utm_campaign=frontend-auto-ads-position" target="_blank"><?php esc_attr_e( 'I want to change the position of the ads', 'advanced-ads' ); ?></a></li>' +
									'<li><a href="<?php echo esc_url( ADVADS_URL ); ?>adsense-auto-ads-wordpress/?utm_source=advanced-ads&utm_medium=link&utm_campaign=frontend-auto-ads-specific-pages#Display_Auto_Ads_only_on_specific_pages" target="_blank"><?php esc_attr_e( 'Display Auto ads only on specific pages', 'advanced-ads' ); ?></a></li></ul>';
								break;
							case 'auto-ads-without-ads-help' :
								content = '<p style="text-align: center;"><?php esc_attr_e( 'Just click on your problem to learn more from our knowledge base.', 'advanced-ads' ); ?></p>' +
									'<ul><li><a href="<?php echo esc_url( ADVADS_URL ); ?>manual/adsense-auto-ads-not-showing/?utm_source=advanced-ads&utm_medium=link&utm_campaign=frontend-no-auto-ads" target="_blank"><?php esc_attr_e( 'I don’t see any Auto ads', 'advanced-ads' ); ?></a></li>' +
									'<li><a href="<?php echo esc_url( ADVADS_URL ); ?>manual/adsense-auto-ads-not-showing/?utm_source=advanced-ads&utm_medium=link&utm_campaign=frontend-no-auto-ads-check-code#Check_if_the_Auto_ads_code_exists" target="_blank"><?php esc_attr_e( 'How to look for the Auto ads code', 'advanced-ads' ); ?></a></li>' +
									'<li><a href="<?php echo esc_url( ADVADS_URL ); ?>support/?utm_source=advanced-ads&utm_medium=link&utm_campaign=frontend-no-auto-ads-support" target="_blank"><?php esc_attr_e( 'I have another question or problem', 'advanced-ads' ); ?></a></li></ul>';
								break;
							case 'closing' : // show until message is closed.
								content = '<p><?php esc_attr_e( 'Closing the message', 'advanced-ads' ); ?>...</p>';
								break;
						}
						if( content ){
							// get existing content box or create new one.
							if( jQuery( '.advads-frontend-notice' ).length ){
								content_obj = jQuery( '.advads-frontend-notice' );
							} else {
								content_obj = jQuery( '<div class="advads-frontend-notice" data-choice="' + notice + '" style="display: none;"><span class="advads-close advads-close-notice">✕</span><div class="advads-content"></div></div>' );
							}
							// add content to the box.
							content_obj.find( '.advads-content' ).html( content );
							if( document.getElementById( 'wpadminbar' ) ){
								content_obj.css( 'top', jQuery( '#wpadminbar' ).height() );
							}
							jQuery( content_obj ).appendTo( 'body' ).slideDown();
							// register close event
							content_obj.on( 'click', '.advads-close', function(){
								// get notice
								if( content_obj.data( 'choice' ) ){
									advads_frontend_notice( 'closing' );
									advanced_ads_frontend_checks.update_frontend_notices( content_obj.data( 'choice' ) );
								}
								// message is hidden in update_frontend_notices
							});
							// register button choice
							jQuery( '.advads-frontend-notice[data-choice="auto-ads-with-ads"]' ).on( 'click', '.advads-choice-negative', function(){
								advads_frontend_notice( 'auto-ads-with-ads-help' );
							});
							jQuery( '.advads-frontend-notice[data-choice="auto-ads-without-ads"]' ).on( 'click', '.advads-choice-negative', function(){
								advads_frontend_notice( 'auto-ads-without-ads-help' );
							});
						}
					}
				<?php endif;
				/**
				 * Code to check if current user gave consent to show ads
				 */
				$privacy_state = Advanced_Ads_Privacy::get_instance()->get_state();
				if( 'not_needed' !== $privacy_state ) :
				?>advanced_ads_ready( function() {
					var state = ( advads.privacy ) ? advads.privacy.get_state() : "";
					var advads_consent_link = document.querySelector( '#wp-admin-bar-advanced_ads_ad_health_consent_missing.hidden' );
					if ( 'unknown' === state && advads_consent_link ) {
						advads_consent_link.className = advads_consent_link.className.replace( 'hidden', '' );
					}

					advanced_ads_frontend_checks.showCount();
				});
				<?php endif; ?>
				/**
				 * show Google Ad Manager debug link in Ad Health
				 *
				 * look for container with ID starting with `div-gpt-ad-`
				 * or `gpt-ad-` as used by our own Google Ad Manager integration
				 * we don’t look for the gpt header script because that is also used by other services that are based on Google Publisher Tags
				 */
				function advads_gam_show_debug_link(){
					var advads_gam_debug_link = document.querySelector( '.advanced_ads_ad_health_gam_debug_link.hidden' );

					if( ! advads_gam_debug_link ){
						return;
					}

					// initialized by the GAM header tag or inline body tags
					if ( document.querySelector( '[id^="div-gpt-ad-"],[id^="gpt-ad-"]' ) ) {
							advads_gam_debug_link.className = advads_gam_debug_link.className.replace( 'hidden', '' );
					}
				}
				// look for Google Ad Manager tags with a delay of 2 seconds
				setTimeout( function(){
					advanced_ads_ready( advads_gam_show_debug_link );
				}, 2000 );
		})(document, window);
		</script>
		<?php echo Advanced_Ads_Utils::get_inline_asset( ob_get_clean() );
	}

	/**
	 * Inject JS after ad content.
	 *
	 * @param str $content ad content
	 * @param obj $ad Advanced_Ads_Ad
	 * @return str $content ad content
	 */
	public function after_ad_output( $content = '', Advanced_Ads_Ad $ad ) {
		if ( ! isset( $ad->args['frontend-check'] ) ) { return $content; }

		if ( advads_is_amp() ) {
			return $content;
		}

		if ( Advanced_Ads_Ad_Debug::is_https_and_http( $ad ) ) {
			ob_start(); ?>
			<script>advanced_ads_ready( function() {
				var ad_id = '<?php echo $ad->id; ?>';
				advanced_ads_frontend_checks.add_item_to_node( '.advanced_ads_ad_health_has_http', ad_id );
				advanced_ads_frontend_checks.add_item_to_notices( 'ad_has_http', { append_key: ad_id, ad_id: ad_id } );
			});</script>
			<?php
			$content .= Advanced_Ads_Utils::get_inline_asset( ob_get_clean() );
		}

		if ( ! Advanced_Ads_Frontend_Checks::can_use_head_placement( $content, $ad ) ) {
			ob_start(); ?>
			<script>advanced_ads_ready( function() {
			var ad_id = '<?php echo $ad->id; ?>';
			advanced_ads_frontend_checks.add_item_to_node( '.advanced_ads_ad_health_incorrect_head', ad_id );
			advanced_ads_frontend_checks.add_item_to_notices( 'ad_with_output_in_head', { append_key: ad_id, ad_id: ad_id } );
			});</script>
			<?php
			$content .= Advanced_Ads_Utils::get_inline_asset( ob_get_clean() );
		}

		$adsense_options = Advanced_Ads_AdSense_Data::get_instance()->get_options();
		if ( 'adsense' === $ad->type
			&& ! empty( $ad->args['cache_busting_elementid'] )
			&& ! isset( $adsense_options['violation-warnings-disable'] )
		) {
			ob_start(); ?>
			<script>advanced_ads_ready( function() {
				var ad_id = '<?php echo $ad->id; ?>';
				var wrapper = '#<?php echo $ad->args['cache_busting_elementid']; ?>';
				advanced_ads_frontend_checks.advads_highlight_hidden_adsense( wrapper );
			});</script>
			<?php
			$content .= Advanced_Ads_Utils::get_inline_asset( ob_get_clean() );
		}

		return $content;
	}


	/**
	 * Check if the 'Header Code' placement can be used to delived the ad.
	 *
	 * @param string          $content Ad content.
	 * @param Advanced_Ads_Ad $ad Advanced_Ads_Ad.
	 * @return bool
	 */
	public static function can_use_head_placement( $content, Advanced_Ads_Ad $ad ) {

		if ( ! $ad->is_head_placement ) {
			return true;
		}

		// strip linebreaks, because, a line break after a comment is identified as a text node.
		$content = preg_replace( "/\r|\n/", "", $content );

		if ( ! $dom = self::get_ad_dom( $content ) ) {
			return true;
		}

		$body = $dom->getElementsByTagName( 'body' )->item( 0 );

		$count = $body->childNodes->length;
		for ( $i = 0; $i < $count; $i++ ) {
			$node = $body->childNodes->item( $i );

			if ( XML_TEXT_NODE  === $node->nodeType ) {
				return false;
			}

			if ( XML_ELEMENT_NODE === $node->nodeType
				&& ! in_array( $node->nodeName, array( 'meta', 'link', 'title', 'style', 'script', 'noscript', 'base' ) ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Convert ad content to a DOMDocument.
	 *
	 * @param string $content
	 * @return DOMDocument|false
	 */
	private static function get_ad_dom( $content ) {
		if ( ! extension_loaded( 'dom' ) ) {
			return false;
		}
		$libxml_previous_state = libxml_use_internal_errors( true );
		$dom = new DOMDocument();
		$result = $dom->loadHTML( '<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"></head><body>' . $content . '</body></html>' );

		libxml_clear_errors();
		libxml_use_internal_errors( $libxml_previous_state );

		if ( ! $result ) {
			return false;
		}

		return $dom;
	}

	/**
	 * Check if at least one placement uses `the_content`.
	 *
	 * @return bool True/False.
	 */
	private function has_the_content_placements() {
		$placements = Advanced_Ads::get_ad_placements_array();
		$placement_types = Advanced_Ads_Placements::get_placement_types();
		// Find a placement that depends on 'the_content' filter.
		foreach ( $placements as $placement ) {
			if ( isset ( $placement['type'] )
				&& ! empty( $placement_types[ $placement['type'] ]['options']['uses_the_content'] ) ) {
				return true;
			}
		}
		return false;
	}
}


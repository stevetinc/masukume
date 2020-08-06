<?php
/**
 * Theme settings page.
 *
 * @package Mystery Themes
 * @subpackage News Portal
 * @since 1.1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'News_Portal_Settings' ) ) :

class News_Portal_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'news_portal_admin_menu' ) );
		add_action( 'wp_loaded', array( __CLASS__, 'news_portal_hide_notices' ) );
		add_action( 'wp_loaded', array( $this, 'news_portal_admin_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'about_theme_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'about_theme_scripts' ) );
		add_filter( 'admin_footer_text', array( $this, 'news_portal_admin_footer_text' ) );

		//about theme review notice
        add_action( 'after_setup_theme', array( $this, 'news_portal_theme_rating_notice' ) );
		add_action( 'switch_theme', array( $this, 'news_portal_theme_rating_notice_data_remove' ) );

		add_action( 'wp_ajax_activate_demo_importer_plugin', array( $this, 'activate_demo_importer_plugin' ) );
		add_action( 'wp_ajax_install_demo_importer_plugin', array( $this, 'install_demo_importer_plugin' ) );
		$this->load_dependencies();
	}

	/**
	 * Load dependent files.
	 */
	public function load_dependencies() {
		require get_template_directory(). '/inc/theme-settings/mt-theme-demo-library.php';
	}

	/**
	 * Add admin menu.
	 */
	public function news_portal_admin_menu() {
		$theme = wp_get_theme( get_template() );

		$page = add_theme_page( $theme->display( 'Name' ).' '.esc_html__( 'Settings', 'news-portal' ), $theme->display( 'Name' ).' '.' '.esc_html__( 'Settings', 'news-portal' ), 'activate_plugins', 'news-portal-settings', array( $this, 'get_started_screen' ) );
	}

	/**
	 * Enqueue styles.
	 */
	public function about_theme_styles( $hook ) {
		global $news_portal_version;
		$theme_notice_option = get_option( 'news_portal_admin_notice_welcome' );
		if ( $theme_notice_option ) {
			wp_enqueue_style( 'mt-theme-review-notice', get_template_directory_uri() . '/inc/theme-settings/assets/css/theme-review-notice.css', array(), esc_attr( $news_portal_version ) );
		}

		if ( 'appearance_page_news-portal-settings' != $hook && 'themes.php' != $hook ) {
			return;
		}

		wp_enqueue_style( 'mt-theme-settings-style', get_template_directory_uri() . '/inc/theme-settings/assets/css/settings.css', array(), $news_portal_version );
	}

	/**
	 * Enqueue scripts.
	 */
	public function about_theme_scripts( $hook ) {
		global $news_portal_version;

		$theme_notice_option = get_option( 'news_portal_admin_notice_welcome' );
		if ( $theme_notice_option ) {
			wp_enqueue_script( 'mt-theme-review-notice', get_template_directory_uri() . '/inc/theme-settings/assets/js/theme-review-notice.js', array( 'jquery' ), esc_attr( $news_portal_version ) );

			$demo_importer_plugin = WP_PLUGIN_DIR . '/mysterythemes-demo-importer/mysterythemes-demo-importer.php';
			if ( file_exists( $demo_importer_plugin ) && !is_plugin_active( 'mysterythemes-demo-importer/mysterythemes-demo-importer.php' ) ) {
				$action = 'activate';
			} elseif ( !file_exists( $demo_importer_plugin ) ) {
				$action = 'install';
			} else {
				$action = 'redirect';
			}

			wp_localize_script( 'mt-theme-review-notice', 'mtaboutObject', array(
				'ajax_url'	=> esc_url( admin_url( 'admin-ajax.php' ) ),
				'_wpnonce'	=> wp_create_nonce( 'news_portal_admin_plugin_install_nonce' ),
				'action'	=> esc_html( $action )
			));
		}

		if ( 'appearance_page_news-portal-settings' != $hook ) {
			return;
		}

		$activated_plugins = apply_filters( 'news_portal_active_plugins', get_option('active_plugins') );
		$demo_import_plugin = in_array( 'mysterythemes-demo-importer/mysterythemes-demo-importer.php', $activated_plugins );
		if ( $demo_import_plugin ) {
			return;
		}

		wp_enqueue_script( 'mt-theme-settings-script', get_template_directory_uri() . '/inc/theme-settings/assets/js/settings.js', array( 'jquery' ), esc_attr( $news_portal_version ) );

		$demo_importer_plugin = WP_PLUGIN_DIR . '/mysterythemes-demo-importer/mysterythemes-demo-importer.php';
		if ( file_exists( $demo_importer_plugin ) && !is_plugin_active( 'mysterythemes-demo-importer/mysterythemes-demo-importer.php' ) ) {
			$action = 'activate';
		} else {
			$action = 'install';
		}

		wp_localize_script( 'mt-theme-settings-script', 'mtaboutObject', array(
			'ajax_url'	=> esc_url( admin_url( 'admin-ajax.php' ) ),
			'_wpnonce'	=> wp_create_nonce( 'news_portal_admin_plugin_install_nonce' ),
			'action'	=> esc_html( $action )
		));
	}

	/**
	 * Add admin notice.
	 */
	public function news_portal_admin_notice() {

		if ( isset( $_GET['activated'] ) ) {
			update_option( 'news_portal_admin_notice_welcome', true );
		}

		$theme_notice_option = get_option( 'news_portal_admin_notice_welcome' );
		// Let's bail on theme activation.
		if ( $theme_notice_option ) {
			add_action( 'admin_notices', array( $this, 'welcome_notice' ) );
		}
	}

	/**
	 * Hide a notice if the GET variable is set.
	 */
	public static function news_portal_hide_notices() {
		if ( isset( $_GET['news-portal-hide-notice'] ) && isset( $_GET['_news_portal_notice_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_GET['_news_portal_notice_nonce'], 'news_portal_hide_notices_nonce' ) ) {
				wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'news-portal' ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Cheat in &#8217; huh?', 'news-portal' ) );
			}

			$hide_notice = sanitize_text_field( $_GET['news-portal-hide-notice'] );
			update_option( 'news_portal_admin_notice_' . $hide_notice, false );
		}
	}

	/**
	 * Show welcome notice.
	 */
	public function welcome_notice() {
		$theme 		= wp_get_theme( get_template() );
		$theme_name = $theme->get( 'Name' );
?>
		<div id="mt-theme-message" class="updated notice news-portal-message">
			<a class="news-portal-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( remove_query_arg( array( 'activated' ), add_query_arg( 'news-portal-hide-notice', 'welcome' ) ), 'news_portal_hide_notices_nonce', '_news_portal_notice_nonce' ) ); ?>">
				<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'news-portal' ); ?>
			</a>
			<h2 class="welcome-title"><?php printf( esc_html__( 'Welcome to %s', 'news-portal' ), $theme_name ); ?></h2>
			<p>
				<?php printf( esc_html__( 'Welcome! Thank you for choosing %1$s ! To fully take advantage of the best our theme can offer please make sure you visit our %2$s theme settings page %3$s.', 'news-portal' ), '<strong>'. esc_html( $theme_name ).'</strong>', '<a href="' . esc_url( admin_url( 'themes.php?page=news-portal-settings' ) ) . '">', '</a>' ); ?>
			</p>
			<p>
				<?php printf( esc_html__( 'Clicking get started will process to installation of %1$s Mystery Themes Demo Importer %2$s Plugin in your dashboard. After success it will redirect to the theme settings page.', 'news-portal' ), '<strong>', '</strong>' ); ?>
			</p>
			<div class="submit">
				<button class="mt-get-started button button-primary button-hero" data-done="<?php esc_html_e( 'Done!', 'news-portal' ); ?>" data-process="<?php esc_html_e( 'Processing', 'news-portal' ); ?>" data-redirect="<?php echo esc_url( wp_nonce_url( add_query_arg( 'news-portal-hide-notice', 'welcome', admin_url( 'themes.php' ).'?page=news-portal-settings&tab=demos' ) , 'news_portal_hide_notices_nonce', '_news_portal_notice_nonce' ) ); ?>">
					<?php printf( esc_html__( 'Get started with %1$s', 'news-portal' ), esc_html( $theme_name ) ); ?>
				</button>
			</div>
			
		</div><!-- #mt-theme-message -->
<?php
	}

	/**
	 * Intro text/links shown to all about pages.
	 *
	 * @access private
	 */
	private function intro() {
		global $news_portal_version;
		$theme 				= wp_get_theme( get_template() );
		$theme_name 		= $theme->get( 'Name' );
		$author_uri 		= $theme->get( 'AuthorURI' );
		$author_name 		= $theme->get( 'Author' );

		// Drop minor version if 0
?>
		<div class="news-portal-theme-info mt-theme-info mt-clearfix">
			<h1 class="mt-about-title"> <?php echo esc_html( $theme_name ); ?> </h1>
			<div class="author-credit">
				<span class="theme-version"><?php printf( esc_html__( 'Version: %1$s', 'news-portal' ), $news_portal_version ); ?></span>
				<span class="author-link"><?php printf( wp_kses_post( 'By <a href="%1$s" target="_blank">%2$s</a>', 'news-portal' ), $author_uri, $author_name ); ?></span>
			</div>
		</div><!-- .news-portal-theme-info -->

		<div class="mt-upgrader-pro">
			<div class="mt-upgrade-title-wrap">
				<h3 class="mt-upgrader-title"><?php esc_html_e( 'Upgrade to Premium Version', 'news-portal' ); ?></h3>
				<div class="mt-upgrader-text"><?php esc_html_e( 'Upgrade to pro version for additional features and better supports.', 'news-portal' ); ?></div>
			</div>
			<div class="mt-upgrader-btn"> <a href="<?php echo esc_url( 'https://mysterythemes.com/wp-themes/news-portal-pro/' ); ?>" target="_blank" class="button button-primary"><?php esc_html_e( 'Unlock Features With Pro', 'news-portal' ); ?></a> </div>
		</div><!-- .mt-upgrader-pro -->

		<div class="mt-nav-tab-content-wrapper">
			<div class="nav-tab-wrapper">

				<a class="nav-tab <?php if ( empty( $_GET['tab'] ) && $_GET['page'] == 'news-portal-settings' ) echo 'nav-tab-active'; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'news-portal-settings' ), 'themes.php' ) ) ); ?>">
					<span class="dashicons dashicons-admin-appearance"></span> <?php esc_html_e( 'Get Started', 'news-portal' ); ?>
				</a>

				<a class="nav-tab <?php if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'demos' ) echo 'nav-tab-active'; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'news-portal-settings', 'tab' => 'demos' ), 'themes.php' ) ) ); ?>">
					<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Demos', 'news-portal' ); ?>
				</a>
				
				<a class="nav-tab <?php if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'free_vs_pro' ) echo 'nav-tab-active'; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'news-portal-settings', 'tab' => 'free_vs_pro' ), 'themes.php' ) ) ); ?>">
					<span class="dashicons dashicons-dashboard"></span> <?php esc_html_e( 'Free Vs Pro', 'news-portal' ); ?>
				</a>

				<a class="nav-tab <?php if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'changelog' ) echo 'nav-tab-active'; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'news-portal-settings', 'tab' => 'changelog' ), 'themes.php' ) ) ); ?>">
					<span class="dashicons dashicons-flag"></span> <?php esc_html_e( 'Changelog', 'news-portal' ); ?>
				</a>
			</div><!-- .nav-tab-wrapper -->
<?php
	}

	/**
	 * Get started screen page.
	 */
	public function get_started_screen() {
		$current_tab = empty( $_GET['tab'] ) ? 'about' : sanitize_title( $_GET['tab'] );

		// Look for a {$current_tab}_screen method.
		if ( is_callable( array( $this, $current_tab . '_screen' ) ) ) {
			return $this->{ $current_tab . '_screen' }();
		}

		// Fallback to about screen.
		return $this->about_screen();
	}

	/**
	 * Output the about screen.
	 */
	public function about_screen() {

		$theme 				= wp_get_theme( get_template() );
		$theme_name 		= $theme->template;

		$doc_url 		= 'https://docs.mysterythemes.com/'. $theme_name;
		$pro_theme_url 	= 'https://mysterythemes.com/wp-themes/'. $theme_name .'-pro/';
		$support_url	= 'https://wordpress.org/support/theme/'. $theme_name;
		$review_url		= 'https://wordpress.org/support/theme/'. $theme_name .'/reviews/#new-post';
?>
		<div class="wrap about-wrap">

			<?php $this->intro(); ?>
				<div class="mt-nav-content-wrap">
					<div class="theme-features-wrap welcome-panel">
						<h4><?php esc_html_e( 'Here are some usefull links for you to get started', 'news-portal' ); ?></h4>
						<div class="under-the-hood two-col">
							<div class="col">
								<h3><?php esc_html_e( 'Next Steps', 'news-portal' ); ?></h3>
								<ul>
									<li>
										<a href="<?php echo esc_url( admin_url( 'customize.php' ).'?autofocus[section]=static_front_page' ); ?>" target="_blank" class="welcome-icon welcome-setup-home"><?php esc_html_e( 'Set up your homepage', 'news-portal' ); ?></a>
									</li>
									<li>
										<a href="<?php echo esc_url( admin_url( 'customize.php' ).'?autofocus[panel]=news_portal_header_settings_panel' ); ?>" target="_blank" class="welcome-icon dashicons-editor-kitchensink"><?php esc_html_e( 'Manage header section', 'news-portal' ); ?></a>
									</li>
									<li>
										<a href="<?php echo esc_url( admin_url( 'customize.php' ).'?autofocus[section]=news_portal_post_settings_section' ); ?>" target="_blank" class="welcome-icon dashicons-text-page"><?php esc_html_e( 'Single Post Layout', 'news-portal' ); ?></a>
									</li>
									<li>
										<a href="<?php echo esc_url( admin_url( 'customize.php' ).'?autofocus[section]=news_portal_social_icons_section' ); ?>" target="_blank" class="welcome-icon dashicons-networking"><?php esc_html_e( 'Manage Social Icons', 'news-portal' ); ?></a>
									</li>
									<li>
										<a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>" target="_blank" class="welcome-icon welcome-menus"><?php esc_html_e( 'Manage menus', 'news-portal' ); ?></a>
									</li>
									<li>
										<a href="<?php echo esc_url( admin_url( 'widgets.php' ) ); ?>" target="_blank" class="welcome-icon welcome-widgets"><?php esc_html_e( 'Manage widgets', 'news-portal' ); ?></a>
									</li>
								</ul>
							</div>

							<div class="col">
								<h3><?php esc_html_e( 'More Actions', 'news-portal' ); ?></h3>
								<ul>
									<li>
										<a href="<?php echo esc_url( $doc_url ); ?>" target="_blank" class="welcome-icon dashicons-media-text"><?php esc_html_e( 'Documentation', 'news-portal' ); ?></a>
									</li>
									<li>
										<a href="<?php echo esc_url( $pro_theme_url ); ?>" target="_blank" class="welcome-icon dashicons-migrate"><?php esc_html_e( 'Premium version', 'news-portal' ); ?></a>
									</li>
									<li>
										<a href="<?php echo esc_url( $support_url ); ?>" target="_blank" class="welcome-icon dashicons-businesswoman"><?php esc_html_e( 'Need theme support?', 'news-portal' ); ?></a>
									</li>
									<li>
										<a href="<?php echo esc_url( $review_url ); ?>" target="_blank" class="welcome-icon dashicons-thumbs-up"><?php esc_html_e( 'Review theme', 'news-portal' ); ?></a>
									</li>
									<li>
										<a href="<?php echo esc_url( 'https://wpallresources.com/' ); ?>" target="_blank" class="welcome-icon dashicons-admin-users"><?php esc_html_e( 'WP Tutorials', 'news-portal' ); ?></a>
									</li>
								</ul>
							</div>
						</div>
					</div><!-- .theme-features-wrap -->

					<div class="return-to-dashboard news-portal">
						<?php if ( current_user_can( 'update_core' ) && isset( $_GET['updated'] ) ) : ?>
							<a href="<?php echo esc_url( self_admin_url( 'update-core.php' ) ); ?>">
								<?php is_multisite() ? esc_html_e( 'Return to Updates', 'news-portal' ) : esc_html_e( 'Return to Dashboard &rarr; Updates', 'news-portal' ); ?>
							</a> |
						<?php endif; ?>
						<a href="<?php echo esc_url( self_admin_url() ); ?>"><?php is_blog_admin() ? esc_html_e( 'Go to Dashboard &rarr; Home', 'news-portal' ) : esc_html_e( 'Go to Dashboard', 'news-portal' ); ?></a>
					</div><!-- .return-to-dashboard -->
				</div><!-- .mt-nav-content-wrap -->
			</div><!-- .mt-nav-tab-content-wrapper -->
		</div><!-- .about-wrap -->
<?php
	}

	/**
	 * Output the more themes screen
	 */
	public function demos_screen() {
		$activated_theme 	= get_template();
		$demodata 			= get_transient( 'news_portal_demo_packages' );
		
		if ( empty( $demodata ) || $demodata == false ) {
			$news_portal_library = new News_Portal_Demo_Library();
			$demodata = $news_portal_library->retrieve_demo_by_activatetheme();
			if ( $demodata ) {
				set_transient( 'news_portal_demo_packages', $demodata, WEEK_IN_SECONDS );
			}
		}

		$activated_demo_check 	= get_option( 'mtdi_activated_check' );
?>
		<div class="wrap about-wrap">

			<?php $this->intro(); ?>
				<div class="mt-nav-content-wrap">
					<div class="mt-theme-demos rendered">
						<?php $this->install_demo_import_plugin_popup(); ?>
						<div class="demos wp-clearfix">
						<?php
							if ( isset( $demodata ) && empty( $demodata ) ) {
								esc_html_e( 'No demos are configured for this theme, please contact the theme author', 'news-portal' );
								return;
							} else {
						?>
								<div class="mt-demo-wrapper mtdi_gl js-ocdi-gl">
									<div class="themes wp-clearfix">
									<?php
										foreach ( $demodata as $value ) {
											$theme_name 		= $value['name'];
											$theme_slug 		= $value['theme_slug'];
											$preview_screenshot = $value['preview_screen'];
											$demourl 			= $value['preview_url'];
											if ( ( strpos( $activated_theme, 'pro' ) !== false && strpos( $theme_slug, 'pro' ) !== false ) || ( strpos( $activated_theme, 'pro' ) == false ) ) {
									?>
												<div class="mt-each-demo<?php if  ( strpos( $activated_theme, 'pro' ) == false && strpos( $theme_slug, 'pro' ) !== false ) { echo ' mt-demo-pro'; } ?> theme mtdi_gl-item js-ocdi-gl-item" data-categories="ltrdemo" data-name="<?php echo esc_attr ( $theme_slug ); ?>" style="display: block;">
													<div class="mtdi-preview-screenshot mtdi_gl-item-image-container">
														<a href="<?php echo esc_url ( $demourl ); ?>" target="_blank">
															<img class="mtdi_gl-item-image" src="<?php echo esc_url ( $preview_screenshot ); ?>" />
														</a>
													</div><!-- .mtdi-preview-screenshot -->
													<div class="theme-id-container">
														<h2 class="mtdi-theme-name theme-name" id="nokri-name"><?php echo esc_html ( $theme_name ); ?></h2>
														<div class="mtdi-theme-actions theme-actions">
															<?php
																if ( $activated_demo_check != '' && $activated_demo_check == $theme_slug ) {
															?>
																	<a class="button disabled button-primary hide-if-no-js" href="javascript:void(0);" data-name="<?php echo esc_attr ( $theme_name ); ?>" data-slug="<?php echo esc_attr ( $theme_slug ); ?>" aria-label="<?php printf ( esc_html__( 'Imported %1$s', 'news-portal' ), $theme_name ); ?>">
																		<?php esc_html_e( 'Imported', 'news-portal' ); ?>
																	</a>
															<?php
																} else {
																	if ( strpos( $activated_theme, 'pro' ) == false && strpos( $theme_slug, 'pro' ) !== false ) {
																		$s_slug = explode( "-pro", $theme_slug );
																		$purchaseurl = 'https://mysterythemes.com/wp-themes/'.$s_slug[0].'-pro';
															?>
																		<a class="button button-primary mtdi-purchasenow" href="<?php echo esc_url( $purchaseurl ); ?>" target="_blank" data-name="<?php echo esc_attr ( $theme_name ); ?>" data-slug="<?php echo esc_attr ( $theme_slug ); ?>" aria-label="<?php printf ( esc_html__( 'Purchase Now', 'news-portal' ), $theme_name ); ?>">
																			<?php esc_html_e( 'Buy Now', 'news-portal' ); ?>
																		</a>
															<?php
																	} else {
																		if ( is_plugin_active( 'mysterythemes-demo-importer/mysterythemes-demo-importer.php' ) ) {
																			$button_tooltip = esc_html__( 'Click to import demo', 'news-portal' );
																		} else {
																			$button_tooltip = esc_html__( 'Demo importer plugin is not installed or activated', 'news-portal' );
																		}
															?>
																		<a title="<?php echo esc_attr( $button_tooltip ); ?>" class="button button-primary hide-if-no-js mtdi-demo-import" href="javascript:void(0);" data-name="<?php echo esc_attr ( $theme_name ); ?>" data-slug="<?php echo esc_attr ( $theme_slug ); ?>" aria-label="<?php printf ( esc_attr__( 'Import %1$s', 'news-portal' ), $theme_name ); ?>">
																			<?php esc_html_e( 'Import', 'news-portal' ); ?>
																		</a>
															<?php
																	}
																}
															?>
																<a class="button preview install-demo-preview" target="_blank" href="<?php echo esc_url ( $demourl ); ?>">
																	<?php esc_html_e( 'View Demo', 'news-portal' ); ?>
																</a>
														</div><!-- .mtdi-theme-actions -->
													</div><!-- .theme-id-container -->
												</div><!-- .mtdi-each-demo -->
									<?php
											}
										}
									?>
									</div><!-- .themes -->
								</div><!-- .mtdi-demo-wrapper -->
						<?php
							}
						?>
						</div>
					</div><!-- .theme-browser -->
				</div><!-- .mt-nav-content-wrap -->
			</div><!-- .mt-nav-tab-content-wrapper -->
		</div><!-- .wrap.about-wrap -->
<?php
	}
	
	/**
	 * Output the changelog screen.
	 */
	public function changelog_screen() {
		global $wp_filesystem;

	?>
		<div class="wrap about-wrap">

			<?php $this->intro(); ?>
				<div class="mt-nav-content-wrap">
					<h4><?php esc_html_e( 'View changelog below:', 'news-portal' ); ?></h4>

					<?php
						$changelog_file = apply_filters( 'news_portal_changelog_file', get_template_directory() . '/readme.txt' );

						// Check if the changelog file exists and is readable.
						if ( $changelog_file && is_readable( $changelog_file ) ) {
							WP_Filesystem();
							$changelog 		= $wp_filesystem->get_contents( $changelog_file );
							$changelog_list = $this->parse_changelog( $changelog );

							echo wp_kses_post( $changelog_list );
						}
					?>
				</div><!-- .mt-nav-content-wrap -->
			</div><!-- .mt-nav-tab-content-wrapper -->
		</div>
	<?php
	}

	/**
	 * Parse changelog from readme file.
	 * @param  string $content
	 * @return string
	 */
	private function parse_changelog( $content ) {
		$matches   = null;
		$regexp    = '~==\s*Changelog\s*==(.*)($)~Uis';
		$changelog = '';

		if ( preg_match( $regexp, $content, $matches ) ) {
			$changes 	= explode( '\r\n', trim( $matches[1] ) );
			$changelog .= '<pre class="changelog">';

			foreach ( $changes as $index => $line ) {
				$changelog .= wp_kses_post( preg_replace( '~(=\s*(\d+(?:\.\d+)+)\s*=|$)~Uis', '<span class="title">${1}</span>', $line ) );
			}

			$changelog .= '</pre>';
		}

		return wp_kses_post( $changelog );
	}

	/**
	 * Output the free vs pro screen.
	 */
	public function free_vs_pro_screen() {
	?>
		<div class="wrap about-wrap">

			<?php $this->intro(); ?>
				<div class="mt-nav-content-wrap">
					<h4><?php esc_html_e( 'Upgrade to PRO version for more exciting features.', 'news-portal' ); ?></h4>
					<table>
						<thead>
							<tr>
								<th class="table-feature-title"><h3><?php esc_html_e( 'Features', 'news-portal' ); ?></h3></th>
								<th><h3><?php esc_html_e( 'News Portal', 'news-portal' ); ?></h3></th>
								<th><h3><?php esc_html_e( 'News Portal Pro', 'news-portal' ); ?></h3></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><h3><?php esc_html_e( 'Price', 'news-portal' ); ?></h3></td>
								<td><?php esc_html_e( 'Free', 'news-portal' ); ?></td>
								<td><?php esc_html_e( '$59.99', 'news-portal' ); ?></td>
							</tr>
							<tr>
								<td><h3><?php esc_html_e( 'Pre Loaders', 'news-portal' ); ?></h3></td>
								<td><span class="dashicons mt-dashicons-no"></span></td>
								<td><span class="dashicons mt-dashicons-yes"></span></td>
							</tr>
							<tr>
								<td><h3><?php esc_html_e( 'Header Layouts', 'news-portal' ); ?></h3></td>
								<td><?php esc_html_e( '1', 'news-portal' ); ?></td>
								<td><?php esc_html_e( '3', 'news-portal' ); ?></td>
							</tr>
							<tr>
								<td><h3><?php esc_html_e( 'Archive Pages Layouts', 'news-portal' ); ?></h3></td>
								<td><?php esc_html_e( '1', 'news-portal' ); ?></td>
								<td><?php esc_html_e( '4', 'news-portal' ); ?></td>
							</tr>
							<tr>
								<td><h3><?php esc_html_e( 'Single Page Layouts', 'news-portal' ); ?></h3></td>
								<td><?php esc_html_e( '1', 'news-portal' ); ?></td>
								<td><?php esc_html_e( '4', 'news-portal' ); ?></td>
							</tr>
							<tr>
								<td><h3><?php esc_html_e( 'Post Review', 'news-portal' ); ?></h3></td>
								<td><span class="dashicons mt-dashicons-no"></span></td>
								<td><span class="dashicons mt-dashicons-yes"></span></td>
							</tr>
							<tr>
								<td><h3><?php esc_html_e( 'Breadcrumbs', 'news-portal' ); ?></h3></td>
								<td><span class="dashicons mt-dashicons-no"></span></td>
								<td><span class="dashicons mt-dashicons-yes"></span></td>
							</tr>
							<tr>
								<td><h3><?php esc_html_e( 'Image Hover Features', 'news-portal' ); ?></h3></td>
								<td><span class="dashicons mt-dashicons-no"></span></td>
								<td><span class="dashicons mt-dashicons-yes"></span></td>
							</tr>
							<tr>
								<td><h3><?php esc_html_e( 'Fallback Image', 'news-portal' ); ?></h3></td>
								<td><span class="dashicons mt-dashicons-no"></span></td>
								<td><span class="dashicons mt-dashicons-yes"></span></td>
							</tr>
							<tr>
								<td><h3><?php esc_html_e( 'Google Fonts', 'news-portal' ); ?></h3></td>
								<td><?php esc_html_e( '3', 'news-portal' ); ?></td>
								<td><?php esc_html_e( '600+', 'news-portal' ); ?></td>
							</tr>
							<tr>
								<td><h3><?php esc_html_e( 'Typography Options', 'news-portal' ); ?></h3></td>
								<td><span class="dashicons mt-dashicons-no"></span></td>
								<td><span class="dashicons mt-dashicons-yes"></span></td>
							</tr>
							<tr>
								<td><h3><?php esc_html_e( 'WooCommerce Compatible', 'news-portal' ); ?></h3></td>
								<td><span class="dashicons mt-dashicons-no"></span></td>
								<td><span class="dashicons mt-dashicons-yes"></span></td>
							</tr>
							<tr>
								<td><h3><?php esc_html_e( 'No. of Widgets', 'news-portal' ); ?></h3></td>
								<td><?php esc_html_e( '8', 'news-portal' ); ?></td>
								<td><?php esc_html_e( '12', 'news-portal' ); ?></td>
							</tr>
							<tr>
								<td><h3><?php esc_html_e( 'Custom 404 Page', 'news-portal' ); ?></h3></td>
								<td><span class="dashicons mt-dashicons-no"></span></td>
								<td><span class="dashicons mt-dashicons-yes"></span></td>
							</tr>
							<tr>
								<td><h3><?php esc_html_e( 'Animation on Section', 'news-portal' ); ?></h3></td>
								<td><span class="dashicons mt-dashicons-no"></span></td>
								<td><span class="dashicons mt-dashicons-yes"></span></td>
							</tr>
							<tr>
								<td><h3><?php esc_html_e( 'Bottom Footer Layouts', 'news-portal' ); ?></h3></td>
								<td><?php esc_html_e( '1', 'news-portal' ); ?></td>
								<td><?php esc_html_e( '2', 'news-portal' ); ?></td>
							</tr>					
							<tr>
								<td></td>
								<td></td>
								<td class="btn-wrapper">
									<a href="<?php echo esc_url( apply_filters( 'news_portal_pro_theme_url', 'https://mysterythemes.com/wp-themes/news-portal-pro/' ) ); ?>" class="button button-primary" target="_blank"><?php esc_html_e( 'Buy Pro', 'news-portal' ); ?></a>
								</td>
							</tr>
						</tbody>
					</table>
				</div><!-- .mt-nav-content-wrap -->
			</div><!-- .mt-nav-tab-content-wrapper -->
		</div><!-- .about-wrap -->
<?php
	}

	/**
	 * Set the required option value as needed for theme review notice.
	 */
	public function news_portal_theme_rating_notice() {

		// Set the installed time in `news_portal_theme_installed_time` option table.
		$option = get_option( 'news_portal_theme_installed_time' );

		if ( ! $option ) {
			update_option( 'news_portal_theme_installed_time', time() );
		}

		add_action( 'admin_notices', array( $this, 'news_portal_theme_review_notice' ), 0 );
		add_action( 'admin_init', array( $this, 'news_portal_ignore_theme_review_notice' ), 0 );
		add_action( 'admin_init', array( $this, 'news_portal_ignore_theme_review_notice_partially' ), 0 );

	}

	/**
	 * Display the theme review notice.
	 */
	public function news_portal_theme_review_notice() {

		global $current_user;
		$user_id                  = $current_user->ID;
		$ignored_notice           = get_user_meta( $user_id, 'news_portal_ignore_theme_review_notice', true );
		$ignored_notice_partially = get_user_meta( $user_id, 'mt_news_portal_ignore_theme_review_notice_partially', true );

		/**
		 * Return from notice display if:
		 *
		 * 1. The theme installed is less than 15 days ago.
		 * 2. If the user has ignored the message partially for 15 days.
		 * 3. Dismiss always if clicked on 'I Already Did' button.
		 */
		if ( ( get_option( 'news_portal_theme_installed_time' ) > strtotime( '- 15 seconds' ) ) || ( $ignored_notice_partially > time() ) || ( $ignored_notice ) ) {
			return;
		}
?>
		<div class="notice updated theme-review-notice">
			<p>
				<?php
					printf( esc_html__(
							'Howdy, %1$s! It seems that you have been using this theme for more than 15 days. We hope you are happy with everything that the theme has to offer. If you can spare a minute, please help us by leaving a 5-star review on WordPress.org.  By spreading the love, we can continue to develop new amazing features in the future, for free!', 'news-portal'
						),
						'<strong>' . esc_html( $current_user->display_name ) . '</strong>'
					);
				?>
			</p>

			<div class="links">
				<a href="https://wordpress.org/support/theme/news-portal/reviews/?filter=5#new-post" class="btn button-primary" target="_blank">
					<span class="dashicons dashicons-thumbs-up"></span>
					<span><?php esc_html_e( 'Sure', 'news-portal' ); ?></span>
				</a>

				<a href="?mt_news_portal_ignore_theme_review_notice_partially=0" class="btn button-secondary">
					<span class="dashicons dashicons-calendar"></span>
					<span><?php esc_html_e( 'Maybe later', 'news-portal' ); ?></span>
				</a>

				<a href="?mt_news_portal_ignore_theme_review_notice=0" class="btn button-secondary">
					<span class="dashicons dashicons-smiley"></span>
					<span><?php esc_html_e( 'I already did', 'news-portal' ); ?></span>
				</a>

				<a href="<?php echo esc_url( 'https://wordpress.org/support/theme/news-portal/' ); ?>" class="btn button-secondary" target="_blank">
					<span class="dashicons dashicons-edit"></span>
					<span><?php esc_html_e( 'Got theme support question?', 'news-portal' ); ?></span>
				</a>
			</div>

			<a class="notice-dismiss" href="?mt_news_portal_ignore_theme_review_notice_partially=0"></a>
		</div>

<?php
	}

	/**
	 * Function to remove the theme review notice permanently as requested by the user.
	 */
	public function news_portal_ignore_theme_review_notice() {

		global $current_user;
		$user_id = $current_user->ID;

		/* If user clicks to ignore the notice, add that to their user meta */
		if ( isset( $_GET['mt_news_portal_ignore_theme_review_notice'] ) && '0' == $_GET['mt_news_portal_ignore_theme_review_notice'] ) {
			add_user_meta( $user_id, 'news_portal_ignore_theme_review_notice', 'true', true );
		}

	}

	/**
	 * Function to remove the theme review notice partially as requested by the user.
	 */
	public function news_portal_ignore_theme_review_notice_partially() {

		global $current_user;
		$user_id = $current_user->ID;

		/* If user clicks to ignore the notice, add that to their user meta */
		if ( isset( $_GET['mt_news_portal_ignore_theme_review_notice_partially'] ) && '0' == $_GET['mt_news_portal_ignore_theme_review_notice_partially'] ) {
			update_user_meta( $user_id, 'mt_news_portal_ignore_theme_review_notice_partially', strtotime( '+ 7 days' ) );
		}

	}

	/**
	 * Remove the data set after the theme has been switched to other theme.
	 */
	public function news_portal_theme_rating_notice_data_remove() {

		global $current_user;
		$user_id                  = $current_user->ID;
		$theme_installed_time     = get_option( 'news_portal_theme_installed_time' );
		$ignored_notice           = get_user_meta( $user_id, 'news_portal_ignore_theme_review_notice', true );
		$ignored_notice_partially = get_user_meta( $user_id, 'mt_news_portal_ignore_theme_review_notice_partially', true );

		// Delete options data.
		if ( $theme_installed_time ) {
			delete_option( 'news_portal_theme_installed_time' );
		}

		// Delete permanent notice remove data.
		if ( $ignored_notice ) {
			delete_user_meta( $user_id, 'news_portal_ignore_theme_review_notice' );
		}

		// Delete partial notice remove data.
		if ( $ignored_notice_partially ) {
			delete_user_meta( $user_id, 'mt_news_portal_ignore_theme_review_notice_partially' );
		}

	}

	/**
     * Display custom text on theme settings page
     *
     * @param string $text
     */
    public function news_portal_admin_footer_text( $text ) {
        $screen = get_current_screen();

        if ( 'appearance_page_news-portal-settings' == $screen->id ) {

        	$theme 		= wp_get_theme( get_template() );
			$theme_name = $theme->get( 'Name' );

            $text = sprintf( __( 'If you like <strong>%1$s</strong> please leave us a %2$s rating. A huge thank you from <strong>Mystery Themes</strong> in advance &#128515!', 'news-portal' ), esc_html( $theme_name ), '<a href="https://wordpress.org/support/theme/news-portal/reviews/?filter=5#new-post" class="theme-rating" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a>' );

        }
        return $text;
	}
	
	/**
	 * Popup alert for mystery themes demo importer plugin install.
	 *
	 * @since 1.2.0
	 */
	public function install_demo_import_plugin_popup() {
		$demo_importer_plugin = WP_PLUGIN_DIR . '/mysterythemes-demo-importer/mysterythemes-demo-importer.php';
	?>
			<div id="mt-demo-import-plugin-popup">
				<div class="mt-popup-inner-wrap">
					<?php
						if ( is_plugin_active( 'mysterythemes-demo-importer/mysterythemes-demo-importer.php' ) ) {
							echo '<span class="mt-plugin-message">'.esc_html__( 'You can import available demos now!', 'news-portal' ).'</span>';
						} else {
							if ( ! file_exists( $demo_importer_plugin ) ) {
					?>
								<span class="mt-plugin-message"><?php esc_html_e( 'Mystery Themes Demo Importer Plugin is not installed!', 'news-portal' ); ?></span>
								<a href="javascript:void(0)" class="mt-install-demo-import-plugin" data-process="<?php esc_attr_e( 'Installing & Activating', 'news-portal' ); ?>" data-done="<?php esc_attr_e( 'Installed & Activated', 'news-portal' ); ?>">
									<?php esc_html_e( 'Install and Activate', 'news-portal' ); ?>
								</a>
					<?php
							} else {
					?>
								<span class="mt-plugin-message"><?php esc_html_e( 'Mystery Themes Demo Importer Plugin is installed but not activated!', 'news-portal' ); ?></span>
								<a href="javascript:void(0)" class="mt-activate-demo-import-plugin" data-process="<?php esc_attr_e( 'Activating', 'news-portal' ); ?>" data-done="<?php esc_attr_e( 'Activated', 'news-portal' ); ?>">
									<?php esc_html_e( 'Activate Now', 'news-portal' ); ?>
								</a>
					<?php
							}
						}
					?>
				</div><!-- .mt-popup-inner-wrap -->
			</div><!-- .mt-demo-import-plugin-popup -->
		<?php
	}

	/**
	 * Activate Demo Importer Plugins Ajax Method
	 *
	 * @since 1.2.0
	 */
	public function activate_demo_importer_plugin() {
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'news_portal_admin_plugin_install_nonce' ) ) {
			die( 'This action was stopped for security purposes.' );
		}

		$result = activate_plugin( '/mysterythemes-demo-importer/mysterythemes-demo-importer.php' );
		if ( is_wp_error( $result ) ) {
			// Process Error
			wp_send_json_error(
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				)
			);
		} else {
			wp_send_json_success(
				array(
					'success' => true,
					'message' => __( 'Plugin Successfully Activated.', 'news-portal' ),
				)
			);
		}
	}

	/**
	 * Activate Demo Importer Plugins Ajax Method
	 *
	 * @since 1.2.0
	 */
	function install_demo_importer_plugin() {

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'news_portal_admin_plugin_install_nonce' ) ) {
			die( 'This action was stopped for security purposes.' );
		}

		if ( ! current_user_can( 'install_plugins' ) ) {
			$status['message'] = __( 'Sorry, you are not allowed to install plugins on this site.', 'news-portal' );
			wp_send_json_error( $status );
		}

		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => esc_html( 'mysterythemes-demo-importer' ),
				'fields' => array(
					'sections' => false,
				),
			)
		);
		if ( is_wp_error( $api ) ) {
			$status['message'] = $api->get_error_message();
			wp_send_json_error( $status );
		}

		$status['pluginName'] 	= $api->name;
		$skin     				= new WP_Ajax_Upgrader_Skin();
		$upgrader 				= new Plugin_Upgrader( $skin );
		$result   				= $upgrader->install( $api->download_link );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$status['debug'] = $skin->get_upgrade_messages();
		}

		if ( is_wp_error( $result ) ) {
			$status['errorCode']    = $result->get_error_code();
			$status['message'] 		= $result->get_error_message();
			wp_send_json_error( $status );
		} elseif ( is_wp_error( $skin->result ) ) {
			$status['errorCode']    = $skin->result->get_error_code();
			$status['message'] 		= $skin->result->get_error_message();
			wp_send_json_error( $status );
		} elseif ( $skin->get_errors()->get_error_code() ) {
			$status['message'] 		= $skin->get_error_messages();
			wp_send_json_error( $status );
		} elseif ( is_null( $result ) ) {
			global $wp_filesystem;

			$status['errorCode']    = 'unable_to_connect_to_filesystem';
			$status['message'] 		= __( 'Unable to connect to the filesystem. Please confirm your credentials.', 'news-portal' );

			// Pass through the error from WP_Filesystem if one was raised.
			if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
				$status['message'] = esc_html( $wp_filesystem->errors->get_error_message() );
			}

			wp_send_json_error( $status );
		}

		if ( current_user_can( 'activate_plugin' ) ) {
			$result = activate_plugin( '/mysterythemes-demo-importer/mysterythemes-demo-importer.php' );
			if ( is_wp_error( $result ) ) {
				$status['errorCode']    = $result->get_error_code();
				$status['message'] 		= $result->get_error_message();
				wp_send_json_error( $status );
			}
		}
		$status['message'] = esc_html__( 'Plugin installed successfully', 'news-portal' );
		wp_send_json_success( $status );
	}
}

endif;

return new News_Portal_Settings();
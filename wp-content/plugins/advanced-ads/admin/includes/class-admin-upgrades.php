<?php // phpcs:ignore
/**
 * Functions around promoting upgrades
 *
 * @package Advanced Ads
 */
class Advanced_Ads_Admin_Upgrades {

	/**
	 * Advanced_Ads_Admin_Upgrades constructor.
	 */
	public function __construct() {
		// Show premium ad types on the ad edit page.
		add_action( 'advanced-ads-ad-types', array( $this, 'ad_types' ), 1000 );
		// Show notice in Ad Parameters when someone uses an Ad Manager ad in the plain text code field.
		add_filter( 'advanced-ads-ad-notices', array( $this, 'ad_notices' ), 10, 3 );
		// Show AMP options on ad edit page of AdSense ads.
		add_action( 'advanced-ads-gadsense-extra-ad-param', array( $this, 'adsense_type_amp_options' ) );
	}

	/**
	 * Add premium ad types to ad type list on ad edit screens.
	 *
	 * @param array $ad_types ad types registered with Advanced Ads.
	 * @return array ad types.
	 */
	public function ad_types( $ad_types ) {
		// Add Google Ad Manager ad type.
		if ( ! defined( 'AAGAM_VERSION' ) ) {
			$ad_types['upgrade-gam']              = new stdClass();
			$ad_types['upgrade-gam']->ID          = 'gam';
			$ad_types['upgrade-gam']->title       = 'Google Ad Manager'; // Do not translate.
			$ad_types['upgrade-gam']->description = __( 'Load ad units directly from your Google Ad Manager account', 'advanced-ads' );
			$ad_types['upgrade-gam']->is_upgrade  = true;
			$ad_types['upgrade-gam']->upgrade_url = ADVADS_URL . 'add-ons/google-ad-manager/';
		}

		// Add AMP ad type from Responsive Ads add-on.
		if ( ! defined( 'AAR_VERSION' ) ) {
			$ad_types['upgrade-amp']              = new stdClass();
			$ad_types['upgrade-amp']->ID          = 'amp';
			$ad_types['upgrade-amp']->title       = 'AMP'; // Do not translate.
			$ad_types['upgrade-amp']->description = __( 'Ads that are visible on Accelerated Mobile Pages', 'advanced-ads' );
			$ad_types['upgrade-amp']->is_upgrade  = true;
			$ad_types['upgrade-amp']->upgrade_url = ADVADS_URL . 'add-ons/responsive-ads/';
		}

		return $ad_types;
	}

	/**
	 * Show an upgrade link
	 *
	 * @param string $title link text.
	 * @param string $url target URL.
	 * @param string $utm_campaign utm_campaign value to attach to the URL.
	 */
	public static function upgrade_link( $title = '', $url = '', $utm_campaign = '' ) {

		$title              = ! empty( $title ) ? $title : __( 'Upgrade', 'advanced-ads' );
		$url                = ! empty( $url ) ? $url : ADVADS_URL . 'add-ons/';
		$utm_parameter_base = '#utm_source=advanced-ads&utm_medium=link&utm_campaign=';
		$utm_parameter      = ( $utm_campaign ) ? $utm_parameter_base . $utm_campaign : $utm_parameter_base . 'upgrade';

		// Add parameter to URL.
		$url = $url . $utm_parameter;

		include ADVADS_BASE_PATH . 'admin/views/upgrades/upgrade-link.php';
	}

	/**
	 * Show an Advanced Ads Pro upsell pitch
	 *
	 * @param string $utm_campaign utm_campaign value to attach to the URL.
	 * @deprecated use upgrade_link()
	 */
	public static function pro_feature_link( $utm_campaign = '' ) {

		self::upgrade_link(
			__( 'Pro Feature', 'advanced-ads' ),
			ADVADS_URL . 'add-ons/advanced-ads-pro/',
			$utm_campaign
		);
	}

	/**
	 * Show notices in the Ad Parameters meta box
	 *
	 * @param array   $notices Notices.
	 * @param array   $box current meta box.
	 * @param WP_Post $post post object.
	 * @return array $notices Notices.
	 */
	public function ad_notices( $notices, $box, $post ) {
		// Show notice when someone uses an Ad Manager ad in the plain text code field.
		if ( ! defined( 'AAGAM_VERSION' ) && 'ad-parameters-box' === $box['id'] ) {
			$ad = new Advanced_Ads_Ad( $post->ID );
			if ( 'plain' === $ad->type && strpos( $ad->content, 'div-gpt-ad-' ) ) {
				$notices[] = array(
					'text' => sprintf(
					// Translators: %1$s opening a tag, %2$s closing a tag.
						esc_html__( 'This looks like a Google Ad Manager ad. Use the %1$sGAM Integration%2$s.', 'advanced' ),
						'<a href="' . ADVADS_URL . 'add-ons/google-ad-manager/#utm_source=advanced-ads&utm_medium=link&utm_campaign=upgrade-ad-parameters-gam" target="_blank">',
						'</a>'
					) . ' ' . __( 'A quick and error-free way of implementing ad units from your Google Ad Manager account.', 'advanced-ads' ),
				);
			}
		}

		return $notices;
	}

	/**
	 * AMP options for AdSense ads in the Ad Parameters on the ad edit page.
	 */
	public function adsense_type_amp_options() {
		if ( ! defined( 'AAR_VERSION' ) && Advanced_Ads_Checks::active_amp_plugin() ) {
			include ADVADS_BASE_PATH . 'admin/views/upgrades/adsense-amp.php';
		}
	}
}

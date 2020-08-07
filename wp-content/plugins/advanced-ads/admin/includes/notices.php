<?php
/**
 * Array with admin notices
 */
$advanced_ads_admin_notices = apply_filters(
	'advanced-ads-notices',
	array(
		// email tutorial.
		'nl_intro'        => array(
			'type'   => 'info',
			'text'   => Advanced_Ads_Admin_Notices::get_instance()->get_welcome_panel(),
			'global' => true,
		),
		// email tutorial.
		'nl_first_steps'  => array(
			'type'         => 'subscribe',
			'text'         => __( 'Thank you for activating <strong>Advanced Ads</strong>. Would you like to receive the first steps via email?', 'advanced-ads' ),
			'confirm_text' => __( 'Yes, send it', 'advanced-ads' ),
			'global'       => true,
		),
		// free add-ons.
		'nl_free_addons'  => array(
			'type'         => 'subscribe',
			'text'         => __( 'Thank you for using <strong>Advanced Ads</strong>. Stay informed and receive <strong>2 free add-ons</strong> for joining the newsletter.', 'advanced-ads' ),
			'confirm_text' => __( 'Add me now', 'advanced-ads' ),
			'global'       => true,
		),
		// adsense newsletter group.
		'nl_adsense'      => array(
			'type'         => 'subscribe',
			'text'         => __( 'Learn more about how and <strong>how much you can earn with AdSense</strong> and Advanced Ads from my dedicated newsletter.', 'advanced-ads' ),
			'confirm_text' => __( 'Subscribe me now', 'advanced-ads' ),
			'global'       => true,
		),
		// missing license codes.
		'license_invalid' => array(
			'type' => 'plugin_error',
			'text' => __( 'One or more license keys for <strong>Advanced Ads add-ons are invalid or missing</strong>.', 'advanced-ads' ) . ' '
					  // translators: %s is a URL.
					  . sprintf( __( 'Please add valid license keys <a href="%s">here</a>.', 'advanced-ads' ), get_admin_url( 1, 'admin.php?page=advanced-ads-settings#top#licenses' ) ),
		),
		// please review.
		'review'          => array(
			'type'   => 'info',
			// 'text' => '<img src="' . ADVADS_BASE_URL . 'admin/assets/img/thomas.png" alt="Thomas" width="80" height="115" class="advads-review-image"/>'
			'text'   => '<div style="float: left; font-size: 4em; line-height: 1em; margin-right: 0.5em;">' . Advanced_Ads::get_number_of_ads() . '</div>'
						. '<div style="float:left;">'
						. '<p>' . __( '… ads created using <strong>Advanced Ads</strong>.', 'advanced-ads' ) . '</p>'
						. '<p>' . __( 'Do you find the plugin useful and would like to thank us for updates, fixing bugs and improving your ad setup?', 'advanced-ads' ) . '</p>'
						. '<p>' .
						// translators: this belongs to our message asking the user for a review. You can find a nice equivalent in your own language.
						__( 'When you give 5-stars, an actual person does a little happy dance!', 'advanced-ads' ) . '</p>'
						. '<p>'
						. '<span class="dashicons dashicons-external"></span>&nbsp;<strong><a href="https://wordpress.org/support/plugin/advanced-ads/reviews/?rate=5#new-post" target=_"blank">' . __( 'Sure, I appreciate your work', 'advanced-ads' ) . '</a></strong>'
						. ' &nbsp;&nbsp;<span class="dashicons dashicons-sos"></span>&nbsp;<a href="' . ADVADS_URL . 'support/#utm_source=advanced-ads&utm_medium=link&utm_campaign=notice-review" target=_"blank">' . __( 'Yes, but help me first to solve a problem, please', 'advanced-ads' ) . '</a>'
						. '</p></div>',
			'global' => false,
		),
		// Black Friday 2019 promotion.
		'bf2019'          => array(
			'type'   => 'info',
			'text'   => sprintf(
				// translators: %s is a link. `30%%` is correct, please use exactly that value so that it shows "30%".
				__( 'Our Black Friday / Cyber Monday Offer: <span style="font-weight: bold; font-size: 1.4em; color: green;">-30%%</span> on all add-ons and All Access.<a class="button button-primary" target="_blank" href="%s">Get All Access</a>', 'advanced-ads' ),
				ADVADS_URL . 'checkout/?edd_action=add_to_cart&download_id=95170&edd_options[price_id]=1&discount=BFCM2019#utm_source=advanced-ads&utm_medium=link&utm_campaign=bfcm-2019'
			),
			'global' => true,
		),
	)
);


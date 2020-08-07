<?php

class Advanced_Ads_Adblock_Finder {

	public function __construct() {
		add_action( 'wp_footer', array( $this, 'print_adblock_check_js' ), 9 );
	}

	public function print_adblock_check_js() {
		if ( advads_is_amp() ) {
			return;
		}

		$options = Advanced_Ads::get_instance()->options();

		$pro_options = get_option( 'advanced-ads-pro' );
		$ads_for_adblockers_enabled = defined( 'AAP_SLUG' ) && ! empty( $pro_options['ads-for-adblockers']['enabled'] );

		if ( ! empty( $options['ga-UID'] ) || $ads_for_adblockers_enabled ) {
		?><script>
		var advanced_ads_ga_UID = <?php echo ! empty( $options['ga-UID'] ) ? "'" . esc_js( $options['ga-UID'] ). "'" : 'false' ?>;
		var advanced_ads_ga_anonymIP = <?php 
		
		if ( defined( 'ADVANCED_ADS_DISABLE_ANALYTICS_ANONYMIZE_IP' ) ) {
			echo "false;\n";
		} else {
			echo "true;\n";
		}
		
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && current_user_can( 'manage_options' ) ) {
			readfile( dirname( __FILE__ ) . '/script.js' );
		} else {
			readfile( dirname( __FILE__ ) . '/script.min.js' );
		}

		?></script><?php
		}
	}
}

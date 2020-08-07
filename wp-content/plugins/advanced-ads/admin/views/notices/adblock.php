<?php
$advads_ad_blocker_id = Advanced_Ads_Plugin::get_instance()->get_frontend_prefix() ."abcheck-" . md5(microtime());
?>
<div id="<?php echo $advads_ad_blocker_id; ?>" class="message error update-message notice notice-alt notice-error" style="display: none;"><p><?php _e( 'Please disable your <strong>AdBlocker</strong> to prevent problems with your ad setup.', 'advanced-ads' ); ?></p></div>
<script>
jQuery( document ).ready( function() {
	if ( typeof advanced_ads_adblocker_test == 'undefined' ) {
		jQuery('#<?php echo $advads_ad_blocker_id; ?>.message').show();
	}
} );
</script>
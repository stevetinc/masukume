<?php
$pub_id = Advanced_Ads_AdSense_Data::get_instance()->get_adsense_id();
if ( $pub_id ) {
	if ( ! $advads_gadsense_options ) {
		$advads_gadsense_options = array();
	}
	$advads_gadsense_options = array_merge(
		array(
			'dimension_name'   => null,
			'filter_value'     => null,
			'hide_dimensions'  => false,
			'metabox_selector' => null,
			'hidden'           => null,
		),
		$advads_gadsense_options
	);
	$div_tag_extras          = '';
	if ( $advads_gadsense_options['metabox_selector'] ) {
		$div_tag_extras .= 'data-metabox_selector="' . $advads_gadsense_options['metabox_selector'] . '""';
	}

	$summary = Advanced_Ads_Overview_Widgets_Callbacks::create_dashboard_summary( $advads_gadsense_options );
	Advanced_Ads_Overview_Widgets_Callbacks::adsense_stats_js( $pub_id );


	if ( $advads_gadsense_options['hidden'] || ( 'DOMAIN_NAME' !== $advads_gadsense_options['dimension_name'] && ! $advads_gadsense_options['filter_value'] ) ) {
		$summary->hidden = true;
	}

	?>
	<div class="advanced-ads-adsense-dashboard" data-refresh='<?php echo json_encode($summary)?>' <?php echo $div_tag_extras?>></div><?php
}
else {
	echo esc_html__( 'There is an error in your AdSense setup.', 'advanced-ads' );
}
?>

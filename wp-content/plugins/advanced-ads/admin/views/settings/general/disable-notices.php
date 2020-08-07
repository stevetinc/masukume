<input id="advanced-ads-disabled-notices" type="checkbox" value="1" name="<?php echo esc_attr( ADVADS_SLUG ); ?>[disable-notices]" <?php checked( $checked, 1 ); ?>>
<p class="description">
<?php
printf(
	// translators: %1$s is a starting <a> tag and %2$s a closing one.
	esc_html__( 'Disable %1$sAd Health%2$s in frontend and backend, warnings and internal notices like tips, tutorials, email newsletters and update notices.', 'advanced-ads' ),
	'<a href="' . esc_url( ADVADS_URL ) . 'manual/ad-health/#utm_source=advanced-ads&utm_medium=link&utm_campaign=settings-ad-health" target="_blank">',
	'</a>'
);
?>
</p>

<input id="advanced-ads-editors-manage-ads" type="checkbox" <?php checked( $allow, true ); ?> name="<?php echo esc_attr( ADVADS_SLUG ); ?>[editors-manage-ads]" />
<p class="description"><?php esc_html_e( 'Allow editors to also manage and publish ads.', 'advanced-ads' ); ?>
<?php
printf(
	wp_kses(
		// translators: %s is a URL.
		__( 'You can assign different ad-related roles on a user basis with <a href="%s" target="_blank">Advanced Ads Pro</a>.', 'advanced-ads' ),
		array(
			'a' => array(
				'href'   => array(),
				'target' => array(),
			),
		)
	),
	esc_url( ADVADS_URL . 'add-ons/advanced-ads-pro/#utm_source=advanced-ads&utm_medium=link&utm_campaign=settings' )
);
?>
</p>

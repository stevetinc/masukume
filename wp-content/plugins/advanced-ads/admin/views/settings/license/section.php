<p><?php printf(
	wp_kses(
	// translators: %s is a URL.
		__( 'Enter license keys for our powerful <a href="%s" target="_blank">add-ons</a>.', 'advanced-ads' ),
		array(
			'a' => array(
				'href'   => array(),
				'target' => array(),
			),
		)
	),
	esc_url( ADVADS_URL . 'add-ons/#utm_source=advanced-ads&utm_medium=link&utm_campaign=settings-licenses' )
);
?> <?php
printf(
	wp_kses(
	// translators: %s is a URL.
		__( 'See also <a href="%s" target="_blank">Issues and questions about licenses</a>.', 'advanced-ads' ),
		array(
			'a' => array(
				'href'   => array(),
				'target' => array(),
			),
		)
	),
	esc_url( ADVADS_URL . 'manual/purchase-licenses/#utm_source=advanced-ads&utm_medium=link&utm_campaign=settings-licenses' )
);
?>
	</p>
<input type="hidden" id="advads-licenses-ajax-referrer" value="<?php echo esc_attr( wp_create_nonce( 'advads_ajax_license_nonce' ) ); ?>"/>

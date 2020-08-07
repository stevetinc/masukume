<?php $empty_options = ( ! is_array( $set_conditions ) || ! count( $set_conditions ) );
if ( $empty_options ) :
	?>
	<p><?php esc_attr_e( 'If you want to display the ad everywhere, don\'t do anything here. ', 'advanced-ads' ); ?></p>
<?php
endif;
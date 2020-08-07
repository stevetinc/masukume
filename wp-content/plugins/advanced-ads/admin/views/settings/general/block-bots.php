<input id="advanced-ads-block-bots" type="checkbox" value="1" name="<?php echo esc_attr( ADVADS_SLUG ); ?>[block-bots]" <?php checked( $checked, 1 ); ?>>
<?php
if ( Advanced_Ads::get_instance()->is_bot() ) :
	?>
	<span class="advads-error-message"><?php esc_html_e( 'You look like a bot', 'advanced-ads' ); ?></span>
	<?php
endif;
?>
<span class="description"><a href="<?php echo esc_url( ADVADS_URL . 'hide-ads-from-bots/#utm_source=advanced-ads&utm_medium=link&utm_campaign=settings' ); ?>" target="blank"><?php esc_html_e( 'Read this first', 'advanced-ads' ); ?></a></span>
<p class="description"><?php esc_html_e( 'Hide ads from crawlers, bots and empty user agents.', 'advanced-ads' ); ?></p>

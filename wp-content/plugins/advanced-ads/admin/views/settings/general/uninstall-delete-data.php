<input type="checkbox" value="1"
	   name="<?php echo esc_attr( ADVADS_SLUG ); ?>[uninstall-delete-data]" <?php checked( $enabled, 1 ); ?>>
<p class="description"><?php esc_html_e( 'Clean up all data related to Advanced Ads when removing the plugin.', 'advanced-ads' ); ?></p>
<?php

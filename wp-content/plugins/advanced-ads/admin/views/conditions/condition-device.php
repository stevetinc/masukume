<?php
/**
 * Template for the Device visitor condition
 *
 * @var string $name form name attribute.
 * @var string $operator
 * @var array $type_options array with additional information.
 */
?>
<input type="hidden" name="<?php echo esc_attr( $name ); ?>[type]" value="<?php echo esc_attr( $options['type'] ); ?>"/>
<select name="<?php echo esc_attr( $name ); ?>[operator]">
	<option
		value="is" <?php selected( 'is', $operator ); ?>><?php esc_html_e( 'Mobile (including tablets)', 'advanced-ads' ); ?></option>
	<option
		value="is_not" <?php selected( 'is_not', $operator ); ?>><?php esc_html_e( 'Desktop', 'advanced-ads' ); ?></option>
</select>
<p class="description">
	<?php
	echo $type_options[ $options['type'] ]['description'];
	if ( isset( $type_options[ $options['type'] ]['helplink'] ) ) :
		?>
	<a href="<?php echo esc_url( $type_options[ $options['type'] ]['helplink'] ); ?>" target="_blank">
		<?php esc_html_e( 'Manual and Troubleshooting', 'advanced-ads' ); ?>
		</a><?php endif; ?></p>

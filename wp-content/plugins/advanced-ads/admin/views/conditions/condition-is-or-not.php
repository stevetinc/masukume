<?php
/**
 * Template for a condition that only contains of an is/is_not choice.
 *
 * @var string $name form field name attribute.
 * @var string $operator operator.
 * @var array $type_options additional options for the condition.
 */
?><input type="hidden" name="<?php echo esc_attr( $name ); ?>[type]"
		 value="<?php echo esc_attr( $options['type'] ); ?>"/>
<?php
include ADVADS_BASE_PATH . 'admin/views/conditions/condition-operator.php';
?>
<p class="description">
	<?php
	echo esc_html( $type_options[ $options['type'] ]['description'] );
	if ( isset( $type_options[ $options['type'] ]['helplink'] ) ) :
		?>
	<a href="<?php echo esc_url( $type_options[ $options['type'] ]['helplink'] ); ?>" target="_blank">
		<?php
		esc_html_e( 'Manual and Troubleshooting', 'advanced-ads' );
		?>
		</a><?php endif; ?></p>

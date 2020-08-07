<?php
/**
 * Templace for the Author display condition
 *
 * @var string $name form name attribute.
 * @var int $max_authors number of maximum author entries to show.
 */
?>
	<div class="advads-conditions-single advads-buttonset">
		<?php
		foreach ( $authors as $_author ) {
			// don’t use strict comparision because $values contains strings.
			if ( in_array( $_author->ID, $values ) ) {
				$_val = 1;
			} else {
				$_val = 0;
			}
			$author_name = $_author->display_name;
			$field_id    = 'advads-conditions-' . absint( $_author->ID ) . $rand;
			?>
			<label class="button ui-button"
				   for="<?php echo esc_attr( $field_id ); ?>">
				<?php echo esc_attr( $author_name ); ?>
			</label><input type="checkbox"
						   id="<?php echo esc_attr( $field_id ); ?>"
						   name="<?php echo esc_attr( $name ); ?>[value][]" <?php checked( $_val, 1 ); ?>
						   value="<?php echo absint( $_author->ID ); ?>">
			<?php
		}
		include ADVADS_BASE_PATH . 'admin/views/conditions/not-selected.php';
		?>
	</div>
<?php
if ( count( $authors ) >= $max_authors ) :
	?>
	<p class="advads-error-message">
		<?php
		printf(
			wp_kses(
			// translators: %1$d is the number of elements in the list and %2$s a URL.
				__( 'Only %1$d elements are displayed above. Use the <code>advanced-ads-admin-max-terms</code> filter to change this limit according to <a href="%2$s" target="_blank">this page</a>.', 'advanced-ads' ),
				array(
					'code' => array(),
					'a'    => array(
						'href'   => array(),
						'target' => array(),
					),
				)
			),
			absint( $max_authors ),
			esc_url( ADVADS_URL . 'codex/filter-hooks/#utm_source=advanced-ads&utm_medium=link&utm_campaign=author-term-limit' )
		);
		?>
	</p>
<?php
endif;

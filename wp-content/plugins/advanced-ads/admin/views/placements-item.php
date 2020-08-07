<?php
/**
 * Render item option for placements.
 *
 * @var string $_placement_slug slug of the current placement.
 * @var array $_placement information of the current placement.
 * @var string|null $placement_item_type type of the item currently selected for the placement
 * @var string|null $placement_item_id ID of the item currently selected for the placement
 */
?>
<select id="advads-placements-item-<?php echo esc_attr( $_placement_slug ); ?>" name="advads[placements][<?php echo esc_attr( $_placement_slug ); ?>][item]">
	<option value=""><?php esc_html_e( '--not selected--', 'advanced-ads' ); ?></option>
	<?php if ( isset( $items['groups'] ) ) : ?>
	<optgroup label="<?php esc_html_e( 'Ad Groups', 'advanced-ads' ); ?>">
		<?php foreach ( $items['groups'] as $_item_id => $_item_title ) : ?>
		<option value="<?php echo esc_attr( $_item_id ); ?>"
								  <?php
									if ( isset( $_placement['item'] ) ) {
										selected( $_item_id, $_placement['item'] ); }
									?>
		><?php echo esc_html( $_item_title ); ?></option>
	<?php endforeach; ?>
	</optgroup>
	<?php endif; ?>
	<?php if ( isset( $items['ads'] ) ) : ?>
	<optgroup label="<?php esc_html_e( 'Ads', 'advanced-ads' ); ?>">
		<?php
		foreach ( $items['ads'] as $_item_id => $_item_title ) :
			?>
		<option value="<?php echo esc_attr( $_item_id ); ?>"
								  <?php
									if ( $placement_item_id ) {
										/**
										 * Select the translated version of an ad if set up with WPML.
										 *
										 * @source https://wpml.org/wpml-hook/wpml_object_id/
										 */
										$translated_item_id = 'ad_' . apply_filters( 'wpml_object_id', $placement_item_id, 'advanced_ads', true );

										selected( $_item_id, $translated_item_id ); }
									?>
		><?php echo esc_html( $_item_title ); ?></option>
	<?php endforeach; ?>
	</optgroup>
	<?php endif; ?>
</select>
<?php
// link to item.
if ( $placement_item_type ) :
	$link_to_item = false;
	switch ( $placement_item_type ) :
		case 'ad':
			/**
			 * Deliver the translated version of an ad if set up with WPML.
			 *
			 * @source https://wpml.org/wpml-hook/wpml_object_id/
			 */
			$placement_item_id = apply_filters( 'wpml_object_id', $placement_item_id, 'advanced_ads' );
			$link_to_item      = get_edit_post_link( $placement_item_id );
			break;
		case 'group':
			$link_to_item = admin_url( 'admin.php?page=advanced-ads-groups&advads-last-edited-group=' . $placement_item_id );
			break;
	endswitch;
	if ( $link_to_item ) {
		?>
		<a href="<?php echo esc_url( $link_to_item ); ?>"><span class="dashicons dashicons-external"></span></span></a>
		<?php
	} elseif ( 'ad' === $placement_item_type && defined( 'ICL_LANGUAGE_NAME' ) ) {
		// translation missing notice
		?>
		<p>
		<?php
		printf(
				// translators: %s is the name of a language in English
			esc_html__( 'The ad is not translated into %s', 'advanced-ads' ),
			esc_html( ICL_LANGUAGE_NAME )
		);
		?>
		</p>
		<?php
	}
endif;

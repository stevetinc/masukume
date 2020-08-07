<?php
/**
 * Render form to create new placements.
 *
 * @var array $placement_types types of placements.
 */
?>
<form method="POST" action="" onsubmit="return advads_validate_placement_form();" class="advads-placements-new-form" id="advads-placements-new-form"
	<?php
	if ( isset( $placements ) && count( $placements ) ) {
		echo ' style="display: none;"';
	}
	?>
>
	<h3>1. <?php esc_html_e( 'Choose a placement type', 'advanced-ads' ); ?></h3>
	<p class="description">
	<?php
	printf(
		wp_kses(
		// translators: %s is a URL.
			__( 'Placement types define where the ad is going to be displayed. Learn more about the different types from the <a href="%s">manual</a>', 'advanced-ads' ),
			array(
				'a' => array(
					'href' => array(),
				),
			)
		),
		esc_url( ADVADS_URL ) . 'manual/placements/#utm_source=advanced-ads&utm_medium=link&utm_campaign=placements'
	);
	?>
		</p>
	<div class="advads-new-placement-types advads-buttonset">
		<?php
		if ( is_array( $placement_types ) ) {
			foreach ( $placement_types as $_key => $_place ) :
				if ( isset( $_place['image'] ) ) :
					$image = '<img src="' . $_place['image'] . '" alt="' . $_place['title'] . '"/>';
				else :
					$image = '<strong>' . $_place['title'] . '</strong><br/><p class="description">' . $_place['description'] . '</p>';
				endif;
				?>
				<div class="advads-placement-type"><label for="advads-placement-type-<?php echo esc_attr( $_key ); ?>">
																								<?php
						// phpcs:ignore
						echo $image;
																								?>
						</label>
					<input type="radio" id="advads-placement-type-<?php echo esc_attr( $_key ); ?>" name="advads[placement][type]"
						   value="<?php echo esc_attr( $_key ); ?>"/>
					<p class="advads-placement-description">
						<strong><?php echo esc_html( $_place['title'] ); ?></strong><br/><?php echo esc_html( $_place['description'] ); ?></p>
				</div>
				<?php
			endforeach;
		};

		?>
	</div><div class="clear"></div>
	<?php

	// show Pro placements if Pro is not actiavated.
	if ( ! defined( 'AAP_VERSION' ) ) :
			include ADVADS_BASE_PATH . 'admin/views/upgrades/pro-placements.php';
		?>
		<div class="clear"></div>
		<?php
	endif;
	?>
	<p class="advads-error-message advads-placement-type-error"><?php esc_html_e( 'Please select a placement type.', 'advanced-ads' ); ?></p>
	<br/>
	<h3>2. <?php esc_html_e( 'Choose a Name', 'advanced-ads' ); ?></h3>
	<p class="description"><?php esc_html_e( 'The name of the placement is only visible to you. Tip: choose a descriptive one, e.g. <em>Below Post Headline</em>.', 'advanced-ads' ); ?></p>
	<p><input name="advads[placement][name]" class="advads-new-placement-name" type="text" value=""
			  placeholder="<?php esc_html_e( 'Placement Name', 'advanced-ads' ); ?>"/></p>
	<p class="advads-error-message advads-placement-name-error"><?php esc_html_e( 'Please enter a name for your placement.', 'advanced-ads' ); ?></p>
	<h3>3. <?php esc_html_e( 'Choose the Ad or Group', 'advanced-ads' ); ?></h3>
	<p class="description"><?php esc_html_e( 'The ad or group that should be displayed.', 'advanced-ads' ); ?></p>
	<p><select name="advads[placement][item]">
			<option value=""><?php esc_html_e( '--not selected--', 'advanced-ads' ); ?></option>
			<?php if ( isset( $items['groups'] ) ) : ?>
				<optgroup label="<?php esc_html_e( 'Ad Groups', 'advanced-ads' ); ?>">
					<?php foreach ( $items['groups'] as $_item_id => $_item_title ) : ?>
						<option value="<?php echo esc_attr( $_item_id ); ?>"><?php echo esc_html( $_item_title ); ?></option>
					<?php endforeach; ?>
				</optgroup>
			<?php endif; ?>
			<?php if ( isset( $items['ads'] ) ) : ?>
				<optgroup label="<?php esc_html_e( 'Ads', 'advanced-ads' ); ?>">
					<?php foreach ( $items['ads'] as $_item_id => $_item_title ) : ?>
						<option value="<?php echo esc_attr( $_item_id ); ?>"><?php echo esc_html( $_item_title ); ?></option>
					<?php endforeach; ?>
				</optgroup>
			<?php endif; ?>
		</select></p>
	<?php wp_nonce_field( 'advads-placement', 'advads_placement', true ); ?>
	<input type="submit" class="button button-primary" value="<?php esc_html_e( 'Save New Placement', 'advanced-ads' ); ?>"/>
</form>

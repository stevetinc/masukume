<?php
/**
 * Render Layout/Output meta box on ad edit screen.
 *
 * @var bool $has_position true if the position option is set.
 * @var bool $has_clearfix true if the clearfix option is enabled.
 * @var string $margin value for margin option.
 * @var string $wrapper_id value for wrapper ID option.
 * @var bool $debug_mode_enabled true if the ad debug mode option is enabled.
 */
?>
<p class="description"><?php esc_html_e( 'Everything connected to the ads layout and output.', 'advanced-ads' ); ?></p>
<div class="advads-option-list">
	<span class="label"><?php esc_html_e( 'Position', 'advanced-ads' ); ?></span>
	<div id="advanced-ad-output-position">
		<label><input type="radio" name="advanced_ad[output][position]" value="" title="<?php esc_html_e( '- default -', 'advanced-ads' ); ?>" <?php checked( $has_position, false ); ?>
																											?
			/><?php esc_html_e( 'default', 'advanced-ads' ); ?></label>
		<label title="<?php esc_html_e( 'left', 'advanced-ads' ); ?>"><input type="radio" name="advanced_ad[output][position]" value="left" <?php checked( $position, 'left' ); ?>
			/>
			<img src="<?php echo esc_url( ADVADS_BASE_URL ); ?>admin/assets/img/output-left.png" width="60" height="45"/></label>
		<label title="<?php esc_html_e( 'center', 'advanced-ads' ); ?>"><input type="radio" name="advanced_ad[output][position]" value="center" <?php checked( $position, 'center' ); ?>
			/>
			<img src="<?php echo esc_url( ADVADS_BASE_URL ); ?>admin/assets/img/output-center.png" width="60" height="45"/></label>
		<label title="<?php esc_html_e( 'right', 'advanced-ads' ); ?>"><input type="radio" name="advanced_ad[output][position]" value="right" <?php checked( $position, 'right' ); ?>
			/>
			<img src="<?php echo esc_url( ADVADS_BASE_URL ); ?>admin/assets/img/output-right.png" width="60" height="45"/></label>
	<p><label><input type="checkbox" name="advanced_ad[output][clearfix]" value="1" <?php checked( $has_clearfix, true ); ?>
	/>
	<?php
		esc_html_e( 'Check this if you don’t want the following elements to float around the ad. (adds a clearfix)', 'advanced-ads' );
	?>
		</label></p>
	</div>
	<hr/>
	<span class="label"><?php esc_html_e( 'Margin', 'advanced-ads' ); ?></span>
	<div id="advanced-ad-output-margin">
		<label><?php esc_html_e( 'top:', 'advanced-ads' ); ?> <input type="number" value="<?php echo ( isset( $margin['top'] ) ) ? esc_attr( $margin['top'] ) : ''; ?>" name="advanced_ad[output][margin][top]"/>px</label>
		<label><?php esc_html_e( 'right:', 'advanced-ads' ); ?> <input type="number" value="<?php echo ( isset( $margin['right'] ) ) ? esc_attr( $margin['right'] ) : ''; ?>" name="advanced_ad[output][margin][right]"/>px</label>
		<label><?php esc_html_e( 'bottom:', 'advanced-ads' ); ?> <input type="number" value="<?php echo ( isset( $margin['bottom'] ) ) ? esc_attr( $margin['bottom'] ) : ''; ?>" name="advanced_ad[output][margin][bottom]"/>px</label>
		<label><?php esc_html_e( 'left:', 'advanced-ads' ); ?> <input type="number" value="<?php echo ( isset( $margin['left'] ) ) ? esc_attr( $margin['left'] ) : ''; ?>" name="advanced_ad[output][margin][left]"/>px</label>
		<p class="description"><?php esc_html_e( 'tip: use this to add a margin around the ad', 'advanced-ads' ); ?></p>
	</div>
	<hr class="advads-hide-in-wizard"/>
	<label class='label advads-hide-in-wizard' for="advads-output-wrapper-id"><?php esc_html_e( 'container ID', 'advanced-ads' ); ?></label>
	<div class="advads-hide-in-wizard">
	<input type="text" id="advads-output-wrapper-id" name="advanced_ad[output][wrapper-id]" value="<?php echo esc_attr( $wrapper_id ); ?>"/>
	<p class="description"><?php esc_html_e( 'Specify the id of the ad container. Leave blank for random or no id.', 'advanced-ads' ); ?>
		&nbsp;<span class="advads-output-wrapper-id-error"><?php esc_attr_e( 'An id-like string with only letters in lower case, numbers, and hyphens.', 'advanced-ads' ); ?></span></p>
	</div>
	<hr  class="advads-hide-in-wizard"/>
	<label class='label advads-hide-in-wizard' for="advads-output-wrapper-class"><?php esc_html_e( 'container classes', 'advanced-ads' ); ?></label>
	<div class="advads-hide-in-wizard">
	<input type="text" id="advads-output-wrapper-class" name="advanced_ad[output][wrapper-class]" value="<?php echo esc_attr( $wrapper_class ); ?>"/>
	<p class="description"><?php esc_html_e( 'Specify one or more classes for the container. Separate multiple classes with a space', 'advanced-ads' ); ?>.</p>
	</div>
	<hr class="advads-hide-in-wizard"/>
	<label for="advads-output-debugmode" class="label advads-hide-in-wizard"><?php esc_html_e( 'Enable debug mode', 'advanced-ads' ); ?></label>
	<div class="advads-hide-in-wizard">
	<input id="advads-output-debugmode" type="checkbox" name="advanced_ad[output][debugmode]" value="1" <?php checked( $debug_mode_enabled, true ); ?>/>
	<a href="<?php echo esc_url( ADVADS_URL ); ?>manual/ad-debug-mode/#utm_source=advanced-ads&utm_medium=link&utm_campaign=ad-debug-mode" target="_blank"><?php esc_html_e( 'Manual', 'advanced-ads' ); ?></a>
	</div>

	<?php if ( ! defined( 'AAP_VERSION' ) ) : ?>
		<hr class="advads-hide-in-wizard"/>
		<label class="label advads-hide-in-wizard"><?php esc_html_e( 'Display only once', 'advanced-ads' ); ?></label>
		<div class="advads-hide-in-wizard">
			<?php
			esc_html_e( 'Display the ad only once per page', 'advanced-ads' );
			?>
			<p>
				<?php
				Advanced_Ads_Admin_Upgrades::pro_feature_link( 'upgrade-pro-display-only-once' );
				?>
				</p>
		</div><hr class="advads-hide-in-wizard"/>
		<label class="label advads-hide-in-wizard"><?php esc_html_e( 'Custom Code', 'advanced-ads' ); ?></label>
		<div class="advads-hide-in-wizard">
			<?php
			esc_html_e( 'Place your own code below the ad', 'advanced-ads' );
			?>
			<p>
			<?php
			Advanced_Ads_Admin_Upgrades::pro_feature_link( 'upgrade-pro-custom-code' );
			?>
				</p>		</div>
	<?php endif; ?>

	<?php do_action( 'advanced-ads-output-metabox-after', $ad ); ?>

</div>

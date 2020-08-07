<?php
/**
 * Render meta box for Display Conditions on ad edit page
 *
 * @package   Advanced_Ads_Admin
 * @license   GPL-2.0+
 * @link      https://wpadvancedads.com
 * @copyright since 2013 Thomas Maier, Advanced Ads GmbH
 */

$set_conditions = $ad->options( 'conditions' );
$empty_options  = ( ! is_array( $set_conditions ) || ! count( $set_conditions ) );
if ( $empty_options ) :
	?>
	<div class="advads-show-in-wizard">
		<p><?php esc_attr_e( 'Click on the button below if the ad should NOT show up on all pages when included automatically.', 'advanced-ads' ); ?></p>
		<button type="button" class="button button-secondary"
				id="advads-wizard-display-conditions-show"><?php esc_attr_e( 'Hide the ad on some pages', 'advanced-ads' ); ?></button>
	</div>
<?php endif; ?>
<div id="advads-display-conditions"
	<?php
	if ( $empty_options ) :
		?>
		class="advads-hide-in-wizard"<?php endif; ?>>
<?php
// display help when no conditions are given.
if ( $empty_options ) :
	$set_conditions = array();
	?>
	<p>
		<button type="button" class="advads-video-link-inline button button-primary">
			<?php esc_attr_e( 'Watch video', 'advanced-ads' ); ?>
		</button>&nbsp;<a class="button button-secondary"
				href="<?php echo esc_url( ADVADS_URL ); ?>manual/display-conditions#utm_source=advanced-ads&utm_medium=link&utm_campaign=edit-display"
				target="_blank">
			<?php esc_attr_e( 'Visit the manual', 'advanced-ads' ); ?>
		</a></p>
<?php
endif;
?>
	<p class="advads-jqueryui-error advads-error-message hidden">
		<?php
		printf(
			wp_kses(
			// translators: %s is a link to a tutorial.
				__( 'There might be a problem with layouts and scripts in your dashboard. Please check <a href="%s" target="_blank">this article to learn more</a>.', 'advanced-ads' ),
				array(
					'a' => array(
						'href'   => array(),
						'target' => array(),
					),
				)
			),
			esc_url( ADVADS_URL ) . 'manual/jquery-problem-in-dashboard/#utm_source=advanced-ads&utm_medium=link&utm_campaign=notice-jquery-error'
		);
		?></p>
	<p><?php esc_attr_e( 'A page with this ad on it must match all of the following conditions.', 'advanced-ads' ); ?></p>
<?php
$conditions_list_target = 'advads-ad-display-conditions';
Advanced_Ads_Display_Conditions::render_condition_list( $set_conditions, $conditions_list_target );
?></div><?php
do_action( 'advanced-ads-display-conditions-after', $ad );
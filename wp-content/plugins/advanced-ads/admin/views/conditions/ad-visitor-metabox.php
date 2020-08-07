<?php
/**
 * Render meta box for Visitor Conditions on ad edit page
 *
 * @package   Advanced_Ads_Admin
 * @license   GPL-2.0+
 * @link      https://wpadvancedads.com
 * @copyright since 2013 Thomas Maier, Advanced Ads GmbH
 */

$set_conditions = $ad->options( 'visitors' );
$empty_options  = ( ! is_array( $set_conditions ) || ! count( $set_conditions ) );
if ( $empty_options ) :
	?>
	<div class="advads-show-in-wizard">
	<p><?php _e( 'Click on the button below if the ad should NOT be visible to all visitors', 'advanced-ads' ); ?></p>
	<button type="button" class="button button-secondary"
			id="advads-wizard-visitor-conditions-show"><?php _e( 'Hide the ad from some users', 'advanced-ads' ); ?></button>
	</div>
<?php
endif;
?>
<div id="advads-visitor-conditions"
	<?php
	if ( $empty_options ) :
		?>
		class="advads-hide-in-wizard"<?php endif; ?>>
	<p class="description"><?php _e( 'Display conditions that are based on the user. Use with caution on cached websites.', 'advanced-ads' ); ?></p>
	<?php
	// display help when no conditions are given
	if ( $empty_options ) :
		$set_conditions = array();
		?>
		<p><a class="button button-primary"
			  href="<?php echo ADVADS_URL; ?>manual/visitor-conditions#utm_source=advanced-ads&utm_medium=link&utm_campaign=edit-visitor"
			  target="_blank">
			<?php _e( 'Visit the manual', 'advanced-ads' ); ?>
		</a></p><?php endif;
	$conditions_list_target = 'advads-ad-visitor-conditions';
	Advanced_Ads_Visitor_Conditions::render_condition_list( $set_conditions, $conditions_list_target );
	?></div><?php
do_action( 'advanced-ads-visitor-conditions-after', $ad ); ?>

<?php
defined( 'ABSPATH' ) || exit;
/**
 * The view for the placements page
 *
 * @var array $placement_types placement types.
 */
?><div class="wrap">
<?php
if ( isset( $_GET['message'] ) ) :
	if ( 'error' === $_GET['message'] ) :
		?>
	<div id="message" class="error"><p><?php esc_html_e( 'Couldn’t create the new placement. Please check your form field and whether the name is already in use.', 'advanced-ads' ); ?></p></div>
		<?php
	elseif ( 'updated' === $_GET['message'] ) :
		?>
	<div id="message" class="updated"><p><?php esc_html_e( 'Placements updated', 'advanced-ads' ); ?></p></div>
		<?php
	endif;
	?>
<?php endif; ?>
	<h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<a href="#" class="page-title-action" title="<?php esc_html_e( 'Create a new placement', 'advanced-ads' ); ?>" class="button-secondary" onclick="advads_toggle('.advads-placements-new-form'); advads_scroll_to_element('#advads-placements-new-form');">
	<?php esc_html_e( 'New Placement', 'advanced-ads' ); ?>
	</a>

	<hr class="wp-header-end">

	<p class="description"><?php esc_html_e( 'Placements are physically places in your theme and posts. You can use them if you plan to change ads and ad groups on the same place without the need to change your templates.', 'advanced-ads' ); ?></p>
	<p class="description">
	<?php
	printf(
		wp_kses(
		// translators: %s is a URL.
			__( 'See also the manual for more information on <a href="%s">placements</a>.', 'advanced-ads' ),
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
<?php

// add placement form.
require_once ADVADS_BASE_PATH . 'admin/views/placement-form.php';

if ( isset( $placements ) && is_array( $placements ) && count( $placements ) ) :
	do_action( 'advanced-ads-placements-list-before', $placements );
	?>
		<h2><?php esc_html_e( 'Placements', 'advanced-ads' ); ?></h2>
		<form method="POST" action="" id="advanced-ads-placements-form">

	<?php
	$columns         = array(
		array(
			'key'          => 'type',
			'display_name' => esc_html__( 'Type', 'advanced-ads' ),
			'sortable'     => true,
		),
		array(
			'key'          => 'name',
			'display_name' => esc_html__( 'Name', 'advanced-ads' ),
			'sortable'     => true,
		),
		array(
			'key'          => 'options',
			'display_name' => esc_html__( 'Options', 'advanced-ads' ),
		),
	);
	?>
			<table class="widefat advads-placements-table striped">
				<thead>
					<tr>
						<?php
						foreach ( $columns as $column ) {
							$class               = '';
							$column_display_name = $column['display_name'];

							if ( ! empty( $column['sortable'] ) ) {
								$column_display_name = $column_display_name
									. '<span class="advads-placement-sorting-indicator"></span>';

								if ( $orderby === $column['key'] ) {
									$class = 'class="advads-placement-sorted"';
								} else {
									$class = 'class="advads-placement-sortable"';

									$column_display_name = '<a href="' . esc_url( add_query_arg( array( 'orderby' => $column['key'] ) ) ) . '">'
										. $column_display_name
										. '</a>';
								}
							}

							echo "<th $class>$column_display_name</th>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}

						do_action( 'advanced-ads-placements-list-column-header' ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
						?>
						<th></th>
					</tr>
				</thead>
				<tbody>
	<?php
	// Sort placements.
	$placements = Advanced_Ads_Placements::sort( $placements, $orderby );

	foreach ( $placements as $_placement_slug => $_placement ) :
			$type_missing = false;
		if ( isset( $_placement['type'] ) && ! isset( $placement_types[ $_placement['type'] ] ) ) {
			$missed_type        = $_placement['type'];
			$_placement['type'] = 'default';
			$type_missing       = true;
		}
		?>
		<tr id="single-placement-<?php echo esc_attr( $_placement_slug ); ?>" class="advanced-ads-placement-row">
							<td>
							<?php
							if ( $type_missing ) :  // type is not given.
								?>
					<p class="advads-error-message">
								<?php
								printf(
									wp_kses(
									// translators: %s is the name of a placement.
										__( 'Placement type "%s" is missing and was reset to "default".<br/>Please check if the responsible add-on is activated.', 'advanced-ads' ),
										array(
											'br' => array(),
										)
									),
									esc_html( $missed_type )
								);
								?>
						</p>
								<?php
				elseif ( isset( $_placement['type'] ) ) :
					if ( isset( $placement_types[ $_placement['type'] ]['image'] ) ) :
						?>
						<img src="<?php echo esc_url( $placement_types[ $_placement['type'] ]['image'] ); ?>"
							 title="<?php echo esc_attr( $placement_types[ $_placement['type'] ]['title'] ); ?>"
							 alt="<?php echo esc_attr( $placement_types[ $_placement['type'] ]['title'] ); ?>"/>
						<?php
					else :
						echo esc_html( $placement_types[ $_placement['type'] ]['title'] );
					endif;
				else :
					__( 'default', 'advanced-ads' );
				endif;
				?>
				</td>
							<td><?php echo esc_html( $_placement['name'] ); ?><br/>
				<?php
				if ( ! isset( $_placement['type'] ) || 'default' === $_placement['type'] ) :
					$_placement['type'] = 'default';
					?>
					<a class="usage-link"><?php esc_html_e( 'show usage', 'advanced-ads' ); ?></a>
					<?php
				 endif;
				?>
				</td>
							<td class="advads-placements-table-options">
				<input type="hidden" class="advads-placement-slug" value="<?php echo esc_attr( $_placement_slug ); ?>"/>
				<?php if ( ! isset( $_placement['type'] ) || 'default' === $_placement['type'] ) : ?>
				<div class="hidden advads-usage">
					<label><?php esc_html_e( 'shortcode', 'advanced-ads' ); ?>
					<code><input type="text" onclick="this.select();" value='[the_ad_placement id="<?php echo esc_attr( $_placement_slug ); ?>"]'/></code>
					</label>
					<label><?php esc_html_e( 'template (PHP)', 'advanced-ads' ); ?>
					<code><input type="text" onclick="this.select();" value="if( function_exists('the_ad_placement') ) { the_ad_placement('<?php echo esc_attr( $_placement_slug ); ?>'); }"/></code>
					</label>
				</div>
				<?php endif; ?>

								<?php do_action( 'advanced-ads-placement-options-before', $_placement_slug, $_placement ); ?>

				<?php
				ob_start();

				// get the currently selected item
				$placement_item_array = explode( '_', $_placement['item'] );
				$placement_item_type  = is_array( $placement_item_array ) && isset( $placement_item_array[0] ) ? $placement_item_array[0] : null;
				$placement_item_id    = is_array( $placement_item_array ) && isset( $placement_item_array[1] ) ? $placement_item_array[1] : null;

				include ADVADS_BASE_PATH . 'admin/views/placements-item.php';
				$item_option_content = ob_get_clean();

				Advanced_Ads_Admin_Options::render_option(
					'placement-item',
					__( 'Item', 'advanced-ads' ),
					$item_option_content
				);
				?>
								<?php
								switch ( $_placement['type'] ) :
									case 'post_content':
										$option_index = isset( $_placement['options']['index'] ) ? absint( max( 1, (int) $_placement['options']['index'] ) ) : 1;
										$option_tag   = isset( $_placement['options']['tag'] ) ? $_placement['options']['tag'] : 'p';

										// Automatically select the 'custom' option.
										if ( ! empty( $_COOKIE['advads_frontend_picker'] ) ) {
											$option_tag = ( $_COOKIE['advads_frontend_picker'] === $_placement_slug ) ? 'custom' : $option_tag;
										}

										$option_xpath = isset( $_placement['options']['xpath'] ) ? stripslashes( $_placement['options']['xpath'] ) : '';
										$positions    = array(
											'after'  => __( 'after', 'advanced-ads' ),
											'before' => __( 'before', 'advanced-ads' ),
										);
										ob_start();
										include ADVADS_BASE_PATH . 'admin/views/placements-content-index.php';
										if ( ! defined( 'AAP_VERSION' ) ) {
											include ADVADS_BASE_PATH . 'admin/views/upgrades/repeat-the-position.php';
										}

										do_action( 'advanced-ads-placement-post-content-position', $_placement_slug, $_placement );
										$option_content = ob_get_clean();

										Advanced_Ads_Admin_Options::render_option(
											'placement-content-injection-index',
											__( 'position', 'advanced-ads' ),
											$option_content
										);

										if ( ! extension_loaded( 'dom' ) ) :
											?>
					<p><span class="advads-error-message"><?php esc_html_e( 'Important Notice', 'advanced-ads' ); ?>: </span>
																			<?php
																			printf(
																				// translators: %s is a name of a module.
																				esc_html__( 'Missing PHP extensions could cause issues. Please ask your hosting provider to enable them: %s', 'advanced-ads' ),
																				'dom (php_xml)'
																			);
																			?>
						</p>
											<?php
endif;
										break;
								endswitch;
								do_action( 'advanced-ads-placement-options-after', $_placement_slug, $_placement );
								ob_start();

								if ( 'header' !== $_placement['type'] ) :
									$type_options = isset( $placement_types[ $_placement['type'] ]['options'] ) ? $placement_types[ $_placement['type'] ]['options'] : array();

									if ( ! isset( $type_options['placement-ad-label'] ) || $type_options['placement-ad-label'] ) {
										$_label    = isset( $_placement['options']['ad_label'] ) ? $_placement['options']['ad_label'] : 'default';
										$_position = ! empty( $_placement['options']['placement_position'] ) ? $_placement['options']['placement_position'] : 'default';
										$_clearfix = ! empty( $_placement['options']['placement_clearfix'] );

										ob_start();
										include ADVADS_BASE_PATH . 'admin/views/placements-ad-label.php';
										if ( ! empty( $placement_types[ $_placement['type'] ]['options']['show_position'] ) ) :
											include ADVADS_BASE_PATH . 'admin/views/placements-ad-label-position.php';
										endif;
										$option_content = ob_get_clean();

										Advanced_Ads_Admin_Options::render_option(
											'placement-ad-label',
											__( 'ad label', 'advanced-ads' ),
											$option_content
										);
									}


									// show Pro features if Pro is not actiavated.
									if ( ! defined( 'AAP_VERSION' ) ) {
										// Display Conditions for placements.
										Advanced_Ads_Admin_Options::render_option(
											'placement-display-conditions',
											__( 'Display Conditions', 'advanced-ads' ),
											'is_pro_pitch',
											__( 'Use display conditions for placements.', 'advanced-ads' ) .
											' ' . __( 'The free version provides conditions on the ad edit page.', 'advanced-ads' )
										);

										// Visitor Condition for placements.
										Advanced_Ads_Admin_Options::render_option(
											'placement-visitor-conditions',
											__( 'Visitor Conditions', 'advanced-ads' ),
											'is_pro_pitch',
											__( 'Use visitor conditions for placements.', 'advanced-ads' ) .
											' ' . __( 'The free version provides conditions on the ad edit page.', 'advanced-ads' )
										);

										// Minimum Content Length.
										Advanced_Ads_Admin_Options::render_option(
											'placement-content-minimum-length',
											__( 'Minimum Content Length', 'advanced-ads' ),
											'is_pro_pitch',
											__( 'Minimum length of content before automatically injected ads are allowed in them.', 'advanced-ads' )
										);

										// Words Between Ads.
										Advanced_Ads_Admin_Options::render_option(
											'placement-skip-paragraph',
											__( 'Words Between Ads', 'advanced-ads' ),
											'is_pro_pitch',
											__( 'A minimum amount of words between automatically injected ads.', 'advanced-ads' )
										);
									}

					endif;

								do_action( 'advanced-ads-placement-options-after-advanced', $_placement_slug, $_placement );
								$advanced_options = ob_get_clean();
								if ( $advanced_options ) :
									?>
				<a class="advads-toggle-link advads-placement-options-link" data-placement="<?php echo esc_attr( $_placement_slug ); ?>"><?php esc_html_e( 'show all options', 'advanced-ads' ); ?></a>
									<?php
												// phpcs:ignore
												$hidden = ( isset( $_POST['advads-last-edited-placement'] ) && $_placement_slug === $_POST['advads-last-edited-placement'] ) ? '' : ' hidden';
									// phpcs:ignore ?>
				<div class="advads-placements-advanced-options advads-placements-advanced-options-<?php echo esc_attr( $_placement_slug ); echo esc_attr( $hidden ); ?>">
									<?php
					// phpcs:ignore
					echo $advanced_options;
									?>
				</div>
												<?php
				endif;
								// information after options.
								if ( isset( $_placement['type'] ) && 'header' === $_placement['type'] ) :
									?>
				<br/><p>
									<?php
									printf(
										wp_kses(
										// translators: %s is a URL.
											__( 'Tutorial: <a href="%s" target="_blank">How to place visible ads in the header of your website</a>.', 'advanced-ads' ),
											array(
												'a' => array(
													'href' => array(),
													'target' => array(),
												),
											)
										),
										esc_url( ADVADS_URL ) . 'place-ads-in-website-header/#utm_source=advanced-ads&utm_medium=link&utm_campaign=header-ad-tutorial'
									);
									?>
					</p>
									<?php
				endif;

								?>
							</td>
							<?php do_action( 'advanced-ads-placements-list-column', $_placement_slug, $_placement ); ?>
							<td>
								<input type="checkbox" id="advads-placements-item-delete-<?php echo esc_attr( $_placement_slug ); ?>" name="advads[placements][<?php echo esc_attr( $_placement_slug ); ?>][delete]" value="1"/>
								<label for="advads-placements-item-delete-<?php echo esc_attr( $_placement_slug ); ?>"><?php echo esc_html_x( 'delete', 'checkbox to remove placement', 'advanced-ads' ); ?></label>
							</td>
						</tr>
	<?php endforeach; ?>
				</tbody>
			</table>
			<div class="tablenav bottom">
			<input type="submit" id="advads-save-placements-button" class="button button-primary" value="<?php esc_html_e( 'Save Placements', 'advanced-ads' ); ?>"/>
		<?php wp_nonce_field( 'advads-placement', 'advads_placement', true ); ?>
		<button type="button" title="<?php esc_html_e( 'Create a new placement', 'advanced-ads' ); ?>" class="button-secondary" onclick="advads_toggle('.advads-placements-new-form'); advads_scroll_to_element('#advads-placements-new-form');">
			<?php
			esc_html_e( 'New Placement', 'advanced-ads' );
			?>
		</button>
		<?php do_action( 'advanced-ads-placements-list-buttons', $placements ); ?>
		</div>
		<input type="hidden" name="advads-last-edited-placement" id="advads-last-edited-placement" value="0"/>
		</form>
	<?php
	include ADVADS_BASE_PATH . 'admin/views/frontend-picker-script.php';
	do_action( 'advanced-ads-placements-list-after', $placements );
endif;

?>
</div>

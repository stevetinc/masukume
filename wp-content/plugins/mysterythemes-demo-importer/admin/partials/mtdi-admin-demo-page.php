<?php
defined('ABSPATH') or die("No script kiddies please!");
/**
* Demo importer admin page
*
* @link       https://mysterythemes.com/
* @since      1.0.0
*
* @package    Mystery Themes Demo Importer
* @subpackage /admin/partials
*/

$activated_demo_check 	= get_option( 'mtdi_activated_check' );
$template 				= get_template();
?>
<div class="wrap demo-importer">
	<h1 class="wp-heading-inline dashicons-before dashicons-upload"><?php esc_html_e( 'Mystery Themes Demo Importer', 'mysterythemes-demo-importer' ); ?></h1>
	<hr>
	<div class="theme-browser content-filterable rendered">
		<div class="mtdi-demo-themes wp-clearfix">
		<?php
			if ( isset( $demodata ) && empty( $demodata ) ) {
				esc_html_e( 'No demos are configured for this theme, please contact the theme author', 'mysterythemes-demo-importer' );
				return;
			} else {
				?>
				<div class="mtdi-demo-wrapper mtdi_gl js-ocdi-gl">
					<div class="mtdi_gl-item-container  wp-clearfix  js-ocdi-gl-item-container theme-browser rendered">
						<div class="themes wp-clearfix">
						<?php
							foreach ( $demodata as $value ) {
								$theme_name 		= $value['name'];
								$theme_slug 		= $value['theme_slug'];
								$preview_screenshot = $value['preview_screen'];
								$demourl 			= $value['preview_url'];
								if ( ( strpos($template, 'pro' ) !== false && strpos ($theme_slug, 'pro' ) !== false ) ||
								( strpos( $template, 'pro' ) == false ) ) {
						?>
									<div class="mtdi-each-demo theme mtdi_gl-item js-ocdi-gl-item" data-categories="ltrdemo" data-name="<?php echo esc_attr ( $theme_slug ); ?>" style="display: block;">
										<div class="mtdi-preview-screenshot mtdi_gl-item-image-container">
											<a href="<?php echo esc_url ( $demourl ); ?>" target="_blank">
												<img class="mtdi_gl-item-image" src="<?php echo esc_url ( $preview_screenshot ); ?>" />
											</a>
										</div><!-- .mtdi-preview-screenshot -->
										<div class="theme-id-container">
											<h2 class="mtdi-theme-name theme-name" id="current-theme-name"><?php echo esc_html ( $theme_name ); ?></h2>
											<div class="mtdi-theme-actions theme-actions">
												<?php
													if ( $activated_demo_check != '' && $activated_demo_check == $theme_slug ) {
												?>
														<a class="button disabled button-primary hide-if-no-js" href="javascript:void(0);" data-name="<?php echo esc_attr ( $theme_name ); ?>" data-slug="<?php echo esc_attr ( $theme_slug ); ?>" aria-label="<?php printf ( esc_html__( 'Imported %1$s', 'mysterythemes-demo-importer' ), $theme_name ); ?>">
															<?php esc_html_e( 'Imported', 'mysterythemes-demo-importer' ); ?>
														</a>
												<?php
													} else {

														if ( strpos( $template,  'pro' ) == false && strpos( $theme_slug, 'pro' ) !== false ) {
															$s_slug = explode( "-pro", $theme_slug );
															$purchaseurl = 'https://mysterythemes.com/wp-themes/'.$s_slug[0].'-pro';
												?>
															<a class="button button-primary mtdi-purchasenow" href="<?php echo esc_url( $purchaseurl );?>" target="_blank" data-name="<?php echo esc_attr ( $theme_name ); ?>" data-slug="<?php echo esc_attr ( $theme_slug ); ?>" aria-label="<?php printf ( esc_html__( 'Purchase Now %1$s', 'mysterythemes-demo-importer' ), $theme_name ); ?>">
																<?php esc_html_e( 'Purchase Now', 'mysterythemes-demo-importer' ); ?>
															</a>
												<?php
														} else {
												?>
															<a class="button button-primary hide-if-no-js mtdi-demo-import" href="javascript:void(0);" data-name="<?php echo esc_attr ( $theme_name ); ?>" data-slug="<?php echo esc_attr ( $theme_slug ); ?>" aria-label="<?php printf ( esc_html__( 'Import %1$s', 'mysterythemes-demo-importer' ), $theme_name ); ?>">
																<?php esc_html_e( 'Import', 'mysterythemes-demo-importer' ); ?>
															</a>
												<?php
														}
													}
												?>
													<a class="button preview install-demo-preview" target="_blank" href="<?php echo esc_url ( $demourl ); ?>">
														<?php esc_html_e( 'View Demo', 'mysterythemes-demo-importer' ); ?>
													</a>
											</div><!-- .mtdi-theme-actions -->
										</div><!-- .theme-id-container -->
									</div><!-- .mtdi-each-demo -->
						<?php
								}
							}
						?>
						</div><!-- .themes -->
					</div><!-- .mtdi_gl-item-container -->
				</div><!-- .mtdi-demo-wrapper -->
		<?php
			}
		?>
		</div><!-- .mtdi-demo-themes -->
	</div><!-- .theme-browser -->
</div><!-- .wrap.demo-importer-->
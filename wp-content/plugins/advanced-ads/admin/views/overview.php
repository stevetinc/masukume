<?php
/**
 * Advanced Ads overview page in the dashboard
 */

?>
<div class="wrap">
	<h1><?php esc_html_e( 'Ads Dashboard', 'advanced-ads' ); ?></h1>

	<div id="advads-overview">
		<?php Advanced_Ads_Overview_Widgets_Callbacks::setup_overview_widgets(); ?>
	</div>
	<?php do_action( 'advanced-ads-admin-overview-after' ); ?>
</div>

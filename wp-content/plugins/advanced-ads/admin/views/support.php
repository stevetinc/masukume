<?php
/**
 * The view for the support page
 */
?><div class="wrap">
	<p><?php esc_html_e( 'Please fix the red highlighted issues on this page or try to understand their consequences before contacting support.', 'advanced-ads' ); ?></p>
	<h2><?php esc_html_e( 'Possible Issues', 'advanced-ads' ); ?></h2>
	<ul>
	<li><a href="<?php echo esc_url( ADVADS_URL ); ?>manual/ads-not-showing-up/#utm_source=advanced-ads&utm_medium=link&utm_campaign=support"><?php esc_html_e( 'Ads not showing up', 'advanced-ads' ); ?></a></li>
	<li><a href="<?php echo esc_url( ADVADS_URL ); ?>manual/purchase-licenses/#utm_source=advanced-ads&utm_medium=link&utm_campaign=support"><?php esc_html_e( 'Purchase & Licenses', 'advanced-ads' ); ?></a></li>
	<li><a href="<?php echo esc_url( ADVADS_URL ); ?>manual-category/troubleshooting/#utm_source=advanced-ads&utm_medium=link&utm_campaign=support"><?php esc_html_e( 'General Issues', 'advanced-ads' ); ?></a></li>
	<li><a href="<?php echo esc_url( ADVADS_URL ); ?>manual-category/add-on-issues/#utm_source=advanced-ads&utm_medium=link&utm_campaign=support"><?php esc_html_e( 'Issues with Add-Ons', 'advanced-ads' ); ?></a></li>
	</ul>
	<p><?php esc_html_e( 'Use the following form to search for solutions in the manual on wpadvancedads.com', 'advanced-ads' ); ?></p>
	<form action="https://wpadvancedads.com/#utm_source=advanced-ads&utm_medium=link&utm_campaign=support-form" method="get" class="advads-support-form">
	<input type="search" name="s"/>
	<input type="submit" class="button button-primary" value="<?php esc_html_e( 'search', 'advanced-ads' ); ?>">
	</form>
	<?php if ( Advanced_Ads_Admin_Licenses::any_license_valid() ) : ?>
	<p>
		<?php
		printf(
			wp_kses(
			// translators: %s is a URL.
				__( 'Take a look at more common issues or contact us directly through the <a href="%s" target="_blank">support page</a>.', 'advanced-ads' ),
				array(
					'a' => array(
						'href'   => array(),
						'target' => array(),
					),
				)
			),
			esc_url( ADVADS_URL ) . 'support/#utm_source=advanced-ads&utm_medium=link&utm_campaign=support'
		);
		?>
		</p>
	<?php else : ?>
	<p>
		<?php
		printf(
			wp_kses(
			// translators: %s is a URL.
				__( 'Upgrade to any premium add-on and get <strong>priority email support</strong> or reach out through the <a href="%s" target="_blank">support forum</a> for individual help.', 'advanced-ads' ),
				array(
					'a' => array(
						'href'   => array(),
						'target' => array(),
					),
				)
			),
			'https://wordpress.org/support/plugin/advanced-ads'
		);
		?>
		</p>
	<?php endif; ?>
<?php if ( $constants = Advanced_Ads_Checks::get_defined_constants() ) : ?>
	<h2><?php esc_html_e( 'Advanced Ads related constants enabled', 'advanced-ads' ); ?></h2>
	<ul><li>
	<?php
	echo wp_kses(
		implode( '</li><li>', $constants ),
		array( 'li' => array() )
	);
	?>
			</li></ul>
	</p>
<?php endif; ?>
</div>

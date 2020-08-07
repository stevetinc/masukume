<?php
/**
 * Output auto ads enabled code in head
 *
 * @var bool $privacy_enabled
 * @var bool $npa_enabled
 * @var string $client_id
 * @var array $options
 */

$top_anchor      = isset( $options['top-anchor-ad'] ) && $options['top-anchor-ad'];
$top_anchor_code = sprintf(
	'(adsbygoogle = window.adsbygoogle || []).push({
			google_ad_client: "%s",
			enable_page_level_ads: true,
			overlays: {bottom: true}
		});',
	esc_attr( $client_id )
);
$script_src      = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js';

if ( $privacy_enabled ) : ?>
	<script>
		// Wait until 'advads.privacy' is available.
		(window.advanced_ads_ready || jQuery(document).ready).call(null, function () {
			var npa_enabled = !!<?php echo $npa_enabled ? 1 : 0; ?>;
			if (npa_enabled || (advads.privacy && advads.privacy.get_state() !== 'unknown')) {
				var script = document.createElement('script'),
					first = document.getElementsByTagName('script')[0];

				script.async = true;
				script.src = '<?php echo esc_url( $script_src ); ?>';
				<?php
				if ( $top_anchor ) :
				// phpcs:disable WordPress.Security.EscapeOutput
					echo $top_anchor_code;
				// phpcs:enable
				else :
				?>
				script.dataset.adClient = '<?php echo esc_attr( $client_id ); ?>';
				<?php endif; ?>
				first.parentNode.insertBefore(script, first);
			}
		});
	</script>
	<?php
	return;
endif;

// Privacy not enabled.
// phpcs:disable WordPress.WP.EnqueuedResources
if ( $top_anchor ) {
	printf(
		'<script async src="%s"></script><script>%s</script>',
		esc_attr( $script_src ),
		// phpcs:disable WordPress.Security.EscapeOutput
		$top_anchor_code
		// phpcs:enable WordPress.Security.EscapeOutput
	);
} else {
	printf(
		'<script data-ad-client="%s" async src="%s"></script>',
		esc_attr( $client_id ),
		esc_url( $script_src )
	);
}
// phpcs:enable

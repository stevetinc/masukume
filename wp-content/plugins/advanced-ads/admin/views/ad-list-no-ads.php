<div id="advads-first-ad-links">
	<button type="button" id="advads-first-ad-video-link" class="button-primary">
		<span class="dashicons dashicons-format-video"></span>
		&nbsp;<?php esc_attr_e( 'Watch the “First Ad” Tutorial (Video)', 'advanced-ads' ); ?>
	</button>
</div>
<script>
	(function ($) {
		var $videoLink = $('#advads-first-ad-video-link');
		$videoLink.click(function () {
			$('<br class="clear"/><br/><iframe width="420" height="315" src="https://www.youtube-nocookie.com/embed/A5jKAzqyWwA?rel=0&amp;showinfo=0" frameborder="0" allowfullscreen></iframe>').appendTo('#advads-first-ad-links');
		})
			.children('.dashicons').css('line-height', $videoLink.css('line-height'));
	})(jQuery)
</script>

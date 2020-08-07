<div id="advads-ad-info">
	<span><?php printf(
		// translators: %s is an ad ID.
		esc_html__( 'Ad Id: %s', 'advanced-ads' ),
		'<strong>' . absint( $post->ID ) . '</strong>'
	); ?></span>
	<label><span><?php esc_html_e( 'shortcode', 'advanced-ads' ); ?></span>
	<pre><input type="text" onclick="this.select();" value='[the_ad id="<?php echo absint( $post->ID ); ?>"]' readonly="readonly"/></pre></label>
	<label><span><?php esc_html_e( 'theme function', 'advanced-ads' ); ?></span>
	<pre><input type="text" onclick="this.select();" value="&lt;?php the_ad(<?php echo absint( $post->ID ); ?>); ?&gt;" readonly="readonly"/></pre></label>
	<span>
	<?php
	printf(
		wp_kses(
		// translators: %s is a URL.
			__( 'Find more display options in the <a href="%s" target="_blank">manual</a>.', 'advanced-ads' ),
			array(
				'a' => array(
					'href'   => array(),
					'target' => array(),
				),
			)
		),
		esc_url( ADVADS_URL ) . 'manual/display-ads/#utm_source=advanced-ads&utm_medium=link&utm_campaign=edit'
	);
	?>
		</span>
</div>
<div id="advads-ad-description">
	<?php if ( ! empty( $ad->description ) ) : ?>
	<p title="<?php esc_html_e( 'click to change', 'advanced-ads' ); ?>"
		onclick="advads_toggle('#advads-ad-description textarea'); advads_toggle('#advads-ad-description p')">
		<?php
		echo nl2br( esc_html( $ad->description ) );
		?>
		</p>
	<?php else : ?>
	<button type="button" onclick="advads_toggle('#advads-ad-description textarea'); advads_toggle('#advads-ad-description button')"><?php esc_html_e( 'Add a description', 'advanced-ads' ); ?></button>
	<?php endif; ?>
	<textarea name="advanced_ad[description]" placeholder="
	<?php
		esc_html_e( 'Internal description or your own notes about this ad.', 'advanced-ads' );
	?>
		"><?php echo esc_html( $ad->description ); ?></textarea>
</div>

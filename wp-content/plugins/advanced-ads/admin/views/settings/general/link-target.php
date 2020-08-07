<input name="<?php echo esc_attr( ADVADS_SLUG ); ?>[target-blank]" type="checkbox" value="1"
						<?php
						checked( 1, $target );
						?>
	/>
<p class="description"><?php echo wp_kses( __( 'Open programatically created links in a new window (use <code>target="_blank"</code>)', 'advanced-ads' ), array( 'code' => array() ) ); ?></p>

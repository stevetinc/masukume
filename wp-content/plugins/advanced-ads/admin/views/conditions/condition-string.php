<input type="hidden" name="<?php echo esc_attr( $name ); ?>[type]" value="<?php echo esc_attr( $options['type'] ); ?>"/>
		<div class="advads-condition-line-wrap">
			<?php include ADVADS_BASE_PATH . 'admin/views/ad-conditions-string-operators.php'; ?>
			<input type="text" name="<?php echo esc_attr( $name ); ?>[value]" value="<?php echo esc_attr( $value ); ?>"/>
		</div>
		<p class="description"><?php echo $type_options[ $options['type'] ]['description']; ?></p>

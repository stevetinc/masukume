<textarea cols="50" rows="5" id="advads-ads-txt-additional-content" name="advads-ads-txt-additional-content"><?php echo esc_textarea( $content ); ?></textarea>
<p class="description"><?php esc_html_e( 'Additional records to add to the file, one record per line. AdSense is added automatically.', 'advanced-ads' ); ?></p>
<div id="advads-ads-txt-notice-wrapper"><?php echo $notices; ?></div>
<p class="advads-error-message hidden" id="advads-ads-txt-notice-error"><?php esc_html_e( 'An error occured: %s.', 'advanced-ads' ); ?></p>
<button class="button advads-ads-txt-action" type="button" id="advads-ads-txt-notice-refresh"><?php esc_html_e( 'Check for problems', 'advanced-ads' ); ?></button>

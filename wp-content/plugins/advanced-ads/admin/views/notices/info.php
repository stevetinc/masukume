<div class="notice notice-info advads-admin-notice message is-dismissible" data-notice="<?php echo $_notice; ?>">
	<p><?php echo $text; ?></p>
	<a href="<?= add_query_arg( [
		'action'   => 'advads-close-notice',
		'notice'   => $_notice,
		'nonce'    => wp_create_nonce( 'advanced-ads-admin-ajax-nonce' ),
		'redirect' => $_SERVER['REQUEST_URI'],
	], admin_url( 'admin-ajax.php' ) ); ?>" class="notice-dismiss"><span class="screen-reader-text"><?= __( 'Dismiss this notice.' ); ?></span></a>
</div>

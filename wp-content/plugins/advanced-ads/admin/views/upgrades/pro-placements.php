<?php
// todo: move the array somewhere else.
$pro_placements = array(
	// ad injection on random position.
	'post_content_random' => array(
		'title'       => __( 'Random Paragraph', 'advanced-ads' ),
		'description' => __( 'After a random paragraph in the main content.', 'advanced-ads' ),
		'image'       => ADVADS_BASE_URL . 'admin/assets/img/placements/content-random.png',
	),
	// ad injection above the post headline.
	'post_above_headline' => array(
		'title'       => __( 'Above Headline', 'advanced-ads' ),
		'description' => __( 'Above the main headline on the page (&lt;h1&gt;).', 'advanced-ads' ),
		'image'       => ADVADS_BASE_URL . 'admin/assets/img/placements/content-above-headline.png',
	),
	// ad injection in the middle of a post.
	'post_content_middle' => array(
		'title'       => __( 'Content Middle', 'advanced-ads' ),
		'description' => __( 'In the middle of the main content based on the number of paragraphs.', 'advanced-ads' ),
		'image'       => ADVADS_BASE_URL . 'admin/assets/img/placements/content-middle.png',
	),
	// ad injection at a hand selected element in the frontend.
	'custom_position'     => array(
		'title'       => __( 'Custom Position', 'advanced-ads' ),
		'description' => __( 'Attach the ad to any element in the frontend.', 'advanced-ads' ),
		'image'       => ADVADS_BASE_URL . 'admin/assets/img/placements/custom-position.png',
	),
	// ad injection between posts on archive and category pages.
	'archive_pages'       => array(
		'title'       => __( 'Post Lists', 'advanced-ads' ),
		'description' => __( 'Display the ad between posts on post lists, e.g. home, archives, search etc.', 'advanced-ads' ),
		'image'       => ADVADS_BASE_URL . 'admin/assets/img/placements/post-list.png',
	),
	'background'          => array(
		'title'       => __( 'Background Ad', 'advanced-ads' ),
		'description' => __( 'Background of the website behind the main wrapper.', 'advanced-ads' ),
		'image'       => ADVADS_BASE_URL . 'admin/assets/img/placements/background.png',
	),
);
// BuddyBoss & BuddyPress.
if ( defined( 'BP_PLATFORM_VERSION' ) ) { // BuddyBoss
	$pro_placements['buddypress'] = array(
		'title'       => __( 'BuddyBoss Content', 'advanced-ads' ),
		'description' => __( 'Display ads on BuddyBoss related pages.', 'advanced-ads' ),
		'image'       => ADVADS_BASE_URL . 'admin/assets/img/placements/buddyboss-icon.png',
	);
} elseif ( class_exists( 'BuddyPress', false ) ) { // BuddyPress
	$pro_placements['buddypress'] = array(
		'title'       => __( 'BuddyPress Content', 'advanced-ads' ),
		'description' => __( 'Display ads on BuddyPress related pages.', 'advanced-ads' ),
		'image'       => ADVADS_BASE_URL . 'admin/assets/img/placements/buddypress-icon.png',
	);
}
// bbPress.
if ( class_exists( 'bbPress', false ) ) {
	$pro_placements['bbpress'] = array(
		'title'       => __( 'bbPress Content', 'advanced-ads' ),
		'description' => __( 'Display ads in content created with bbPress.', 'advanced-ads' ),
		'image'       => ADVADS_BASE_URL . 'admin/assets/img/placements/bbpress-reply.png',
	);
}

?><h4><?php Advanced_Ads_Admin_Upgrades::pro_feature_link( 'upgrades-pro-placements' ); ?></h4>
												  <?php
													if ( is_array( $pro_placements ) ) :
														foreach ( $pro_placements as $_key => $_place ) :
															if ( isset( $_place['image'] ) ) :
																$image = '<img src="' . $_place['image'] . '" alt="' . $_place['title'] . '"/>';
															else :
																$image = '<strong>' . $_place['title'] . '</strong><br/><p class="description">' . $_place['description'] . '</p>';
															endif;
															?>
		<div class="advads-placement-type"><label class="ui-button advads-pro-link"><span class="ui-button-text">
																				<?php
																				// phpcs:ignore
																				echo $image;
																				?>
			</label></span>
			<p class="advads-placement-description">
				<strong><?php echo esc_html( $_place['title'] ); ?></strong><br/><?php echo esc_html( $_place['description'] ); ?></p>
		</div>
															<?php
														endforeach;
endif;

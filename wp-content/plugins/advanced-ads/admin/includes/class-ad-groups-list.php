<?php

/**
 * Groups List Table class.
 *
 * @package Advanced Ads
 * @since 1.4.4
 */
class Advanced_Ads_Groups_List {

	/**
	 * Array with all groups
	 *
	 * @var $groups
	 */
	public $groups = array();

	/**
	 * Array with all ad group types
	 *
	 * @var $types
	 */
	public $types = array();

	/**
	 * Construct the current list
	 */
	public function __construct() {

		// set default vars.
		$this->taxonomy  = Advanced_Ads::AD_GROUP_TAXONOMY;
		$this->post_type = Advanced_Ads::POST_TYPE_SLUG;

		$this->load_groups();

		$this->types = $this->get_ad_group_types();
	}

	/**
	 * Load ad groups
	 */
	public function load_groups() {

		// load all groups.
		$search = ! empty( $_REQUEST['s'] ) ? trim( wp_unslash( $_REQUEST['s'] ) ) : '';

		$args = array(
			'taxonomy'   => $this->taxonomy,
			'search'     => $search,
			'hide_empty' => 0,
		);
		// get wp term objects.
		$terms = Advanced_Ads::get_ad_groups( $args );

		// add meta data to groups.
		$this->groups = $this->load_groups_objects_from_terms( $terms );
	}

	/**
	 * Load ad groups objects from wp term objects
	 *
	 * @param array $terms array of wp term objects.
	 *
	 * @return array
	 */
	protected function load_groups_objects_from_terms( array $terms ) {

		$groups = array();
		foreach ( $terms as $_group ) {
			$groups[] = new Advanced_Ads_Group( $_group );
		}

		return $groups;
	}

	/**
	 * Render group list header
	 */
	public function render_header() {
		$file = ADVADS_BASE_PATH . 'admin/views/ad-group-list-header.php';
		require_once $file;
	}

	/**
	 * Render list rows
	 */
	public function render_rows() {
		foreach ( $this->groups as $_group ) {
			$this->render_row( $_group );
			$this->render_form_row( $_group );
		}
	}


	/**
	 * Render a single row
	 *
	 * @param Advanced_Ads_Group $group the ad group object.
	 */
	public function render_row( Advanced_Ads_Group $group ) {
		$file = ADVADS_BASE_PATH . 'admin/views/ad-group-list-row.php';
		require $file;
	}

	/**
	 * Render the form row of a group
	 *
	 * @param Advanced_Ads_Group $group the ad group object.
	 */
	public function render_form_row( Advanced_Ads_Group $group ) {

		// query ads.
		$ads = $this->get_ads( $group );

		$weights      = $group->get_ad_weights();
		$ad_form_rows = $weights;
		arsort( $ad_form_rows );
		$max_weight = Advanced_Ads_Group::get_max_ad_weight( $ads->post_count );

		// The Loop.
		if ( $ads->post_count ) {
			foreach ( $ads->posts as $_ad ) {
				$row       = '';
				$ad_id     = $_ad->ID;
				$row       .= '<tr data-ad-id="' . absint( $ad_id ) . '" data-group-id="' . absint( $group->id ) . '"><td>' . esc_html( $_ad->post_title ) . '</td><td>';
				$row       .= '<select name="advads-groups[' . absint( $group->id ) . '][ads][' . absint( $_ad->ID ) . ']">';
				$ad_weight = ( isset( $weights[ $ad_id ] ) ) ? $weights[ $ad_id ] : Advanced_Ads_Group::MAX_AD_GROUP_DEFAULT_WEIGHT;
				for ( $i = 0; $i <= $max_weight; $i ++ ) {
					$row .= '<option ' . selected( $ad_weight, $i, false ) . '>' . $i . '</option>';
				}
				$row                      .= '</select</td><td><button type="button" class="advads-remove-ad-from-group button">x</button></td></tr>';
				$ad_form_rows[ $_ad->ID ] = $row;
			}
		}
		$ad_form_rows = $this->remove_empty_weights( $ad_form_rows );
		// Restore original Post Data.
		wp_reset_postdata();

		$ads_for_select = $this->ads_for_select();
		$new_ad_weights = '<select class="advads-group-add-ad-list-weights">';
		for ( $i = 0; $i <= $max_weight; $i ++ ) {
			$new_ad_weights .= '<option ' . selected( 10, $i, false ) . '>' . absint( $i ) . '</option>';
		}
		$new_ad_weights .= '</select>';

		require ADVADS_BASE_PATH . 'admin/views/ad-group-list-form-row.php';
	}

	/**
	 * Render the ads list
	 *
	 * @param Advanced_Ads_Group $group group object.
	 */
	public function render_ads_list( Advanced_Ads_Group $group ) {

		$ads = $this->get_ads( $group );

		$weights    = $group->get_ad_weights();
		$weight_sum = array_sum( $weights );
		$ads_output = $weights;
		arsort( $ads_output );

		// The Loop.
		if ( $ads->have_posts() ) {
			echo ( 'default' === $group->type && $weight_sum ) ? '<ul>' : '<ol>';
			while ( $ads->have_posts() ) {
				$ads->the_post();
				$line_output = '<li><a href="' . get_edit_post_link( get_the_ID() ) . '">' . get_the_title() . '</a>';

				$_weight = ( isset( $weights[ get_the_ID() ] ) ) ? $weights[ get_the_ID() ] : Advanced_Ads_Group::MAX_AD_GROUP_DEFAULT_WEIGHT;
				if ( 'default' === $group->type && $weight_sum ) {
					$line_output .= '<span class="ad-weight" title="' . __( 'Ad weight', 'advanced-ads' ) . '">' . number_format( ( $_weight / $weight_sum ) * 100 ) . '%</span>';
				}

				$ad                 = new Advanced_Ads_Ad( get_the_ID() );
				$expiry_date_format = get_option( 'date_format' ) . ', ' . get_option( 'time_format' );

				$post_start = get_post_time( 'U', true, $ad->id );

				$tz_option = get_option( 'timezone_string' );

				if ( $post_start > time() ) {
					// translators: %s is a date.
					$line_output .= '<br />' . sprintf( __( 'starts %s', 'advanced-ads' ), get_date_from_gmt( date( 'Y-m-d H:i:s', $post_start ), $expiry_date_format ) );
				}
				if ( isset( $ad->expiry_date ) && $ad->expiry_date ) {
					$expiry      = $ad->expiry_date;
					$expiry_date = date_create( '@' . $expiry );


					if ( $tz_option ) {
						$expiry_date->setTimezone( Advanced_Ads_Admin::get_wp_timezone() );
					} else {
						$tz_name       = Advanced_Ads_Admin::timezone_get_name( Advanced_Ads_Admin::get_wp_timezone() );
						$tz_offset     = substr( $tz_name, 3 );
						$off_time      = date_create( '2017-09-21 T10:44:02' . $tz_offset );
						$offset_in_sec = date_offset_get( $off_time );
						$expiry_date   = date_create( '@' . ( $expiry + $offset_in_sec ) );
					}

					$tz = ' ( ' . Advanced_Ads_Admin::timezone_get_name( Advanced_Ads_Admin::get_wp_timezone() ) . ' )';

					if ( $expiry > time() ) {
						// translators: %s is a date.
						$line_output .= '<br />' . sprintf( __( 'expires %s', 'advanced-ads' ), $expiry_date->format( $expiry_date_format ) ) . $tz;
					} elseif ( $expiry <= time() ) {
						// translators: %s is a date.
						$line_output .= '<br />' . sprintf( __( '<strong>expired</strong> %s', 'advanced-ads' ), $expiry_date->format( $expiry_date_format ) ) . $tz;
					}
				}
				$line_output .= '</li>';

				$ads_output[ get_the_ID() ] = $line_output;
			}

			$ads_output = $this->remove_empty_weights( $ads_output );

			echo implode( '', $ads_output );
			echo ( 'default' === $group->type && $weight_sum ) ? '</ul>' : '</ol>';

			if ( $ads->post_count > 4 ) {
				$hidden_ads = $ads->post_count - 3;
				echo '<p><a href="javascript:void(0)" class="advads-group-ads-list-show-more">+ ' .
				     // translators: %d is a number.
				     sprintf( __( 'show %d more ads', 'advanced-ads' ), $hidden_ads ) . '</a></p>';
			}

			if ( 'all' === $group->ad_count ) {
				echo '<p>' . esc_html__( 'all published ads are displayed', 'advanced-ads' ) . '</p>';
			} elseif ( $group->ad_count > 1 ) {
				// translators: %d is a number.
				echo '<p>' . sprintf( esc_html__( 'up to %d ads displayed', 'advanced-ads' ), absint( $group->ad_count ) ) . '</p>';
			}
		} else {
			esc_html_e( 'No ads assigned', 'advanced-ads' );
			?>
			<br/><a class="edit">+ <?php esc_html_e( 'Add some', 'advanced-ads' ); ?></a>
			<?php
		}
		// Restore original Post Data.
		wp_reset_postdata();
	}

	/**
	 * Remove entries from the ad weight array that are just id
	 *
	 * @param array $ads_output array with any output other that an integer.
	 *
	 * @return array $ads_output array with ad output.
	 * @since 1.5.1
	 */
	private function remove_empty_weights( array $ads_output ) {

		foreach ( $ads_output as $key => $value ) {
			if ( is_int( $value ) ) {
				unset( $ads_output[ $key ] );
			}
		}

		return $ads_output;
	}

	/**
	 * Get ads for this group
	 *
	 * @param object $group group object.
	 *
	 * @return  object $ads WP_Query result with ads for this group.
	 */
	public function get_ads( $group ) {
		$args = array(
			'post_type'      => $this->post_type,
			'post_status'    => array( 'publish', 'pending', 'future', 'private' ),
			'taxonomy'       => $group->taxonomy,
			'term'           => $group->slug,
			'posts_per_page' => - 1,
		);

		$ads = new WP_Query( $args );

		return $ads;
	}

	/**
	 * List of all ads to display in select dropdown
	 *
	 * @return array
	 */
	public function ads_for_select() {
		$select = array();
		$model  = Advanced_Ads::get_instance()->get_model();

		// load all ads.
		$ads = $model->get_ads(
			array(
				'orderby' => 'title',
				'order'   => 'ASC',
			)
		);
		foreach ( $ads as $_ad ) {
			$select[ $_ad->ID ] = esc_html( $_ad->post_title );
		}

		return $select;
	}

	/**
	 * Return ad group types
	 *
	 * @return array $types ad group information
	 */
	public function get_ad_group_types() {
		$types = array(
			'default' => array(
				'title'       => __( 'Random ads', 'advanced-ads' ),
				'description' => __( 'Display random ads based on ad weight', 'advanced-ads' ),
			),
			'ordered' => array(
				'title'       => __( 'Ordered ads', 'advanced-ads' ),
				'description' => __( 'Display ads with the highest ad weight first', 'advanced-ads' ),
			),
		);

		return apply_filters( 'advanced-ads-group-types', $types );
	}

	/**
	 * Render ad group action links
	 *
	 * @param object $group group object.
	 */
	public function render_action_links( $group ) {
		global $tax;

		$tax = get_taxonomy( $this->taxonomy );

		$actions = array();
		if ( current_user_can( $tax->cap->edit_terms ) ) {
			$actions['edit']  = '<a class="edit">' . __( 'Edit', 'advanced-ads' ) . '</a>';
			$actions['usage'] = '<a class="usage">' . __( 'Usage', 'advanced-ads' ) . '</a>';
		}

		if ( current_user_can( $tax->cap->delete_terms ) ) {
			$args              = array(
				'action'   => 'delete',
				'group_id' => $group->id,
			);
			$delete_link       = self::group_page_url( $args );
			$actions['delete'] = "<a class='delete-tag' href='" . wp_nonce_url( $delete_link, 'delete-tag_' . $group->id ) . "'>" . __( 'Delete', 'advanced-ads' ) . '</a>';
		}

		if ( ! count( $actions ) ) {
			return;
		}

		echo '<div class="row-actions">';
		foreach ( $actions as $action => $link ) {
			echo "<span class='" . esc_attr( $action ) . "'>" . wp_kses( $link, array( 'a' => array( 'class' => array(), 'href' => array() ) ) ) . '</span>';
		}
		echo '</div>';
	}

	/**
	 * Create a new group
	 */
	public function create_group() {
		// check nonce.
		if ( ! isset( $_POST['advads-group-add-nonce'] )
		     || ! wp_verify_nonce( $_POST['advads-group-add-nonce'], 'add-advads-groups' ) ) {

			return new WP_Error( 'invalid_ad_group', __( 'Invalid Ad Group', 'advanced-ads' ) );
		}

		// check user rights.
		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ) ) ) {
			return new WP_Error( 'invalid_ad_group_rights', __( 'You don’t have permission to change the ad groups', 'advanced-ads' ) );
		}

		if ( isset( $_POST['advads-group-name'] ) && '' !== $_POST['advads-group-name'] ) {

			$title     = sanitize_text_field( wp_unslash( $_POST['advads-group-name'] ) );
			$new_group = wp_create_term( $title, Advanced_Ads::AD_GROUP_TAXONOMY );

			if ( is_wp_error( $new_group ) ) {
				return $new_group;
			}

			// save default values.
			if ( is_array( $new_group ) ) {
				$group = new Advanced_Ads_Group( $new_group['term_id'] );

				// allow other add-ons to save their own group attributes.
				$atts = apply_filters(
					'advanced-ads-group-save-atts',
					array(
						'type'     => 'default',
						'ad_count' => 1,
						'options'  => array(),
					),
					$group
				);

				$group->save( $atts );
			}

			// reload groups.
			$this->load_groups();

		} else {
			return new WP_Error( 'no_ad_group_created', __( 'No ad group created', 'advanced-ads' ) );
		}

		return true;
	}

	/**
	 * Load groups with a given ad in them.
	 *
	 * @param integer $ad_id ad ID.
	 *
	 * @return array
	 */
	private function get_groups_by_ad_id( $ad_id ) {
		$ids   = array();
		$terms = wp_get_object_terms( $ad_id, $this->taxonomy );
		foreach ( $terms as $term ) {
			$ids[] = $term->term_id;
		}

		return $ids;
	}

	/**
	 * Bulk update groups
	 */
	public function update_groups() {
		// check nonce.
		if ( ! isset( $_POST['advads-group-update-nonce'] )
		     || ! wp_verify_nonce( $_POST['advads-group-update-nonce'], 'update-advads-groups' ) ) {

			return new WP_Error( 'invalid_ad_group', __( 'Invalid Ad Group', 'advanced-ads' ) );
		}

		// check user rights.
		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ) ) ) {
			return new WP_Error( 'invalid_ad_group_rights', __( 'You don’t have permission to change the ad groups', 'advanced-ads' ) );
		}

		/** Empty group settings
		 * edit: emptying disabled, because when only a few groups are saved (e.g. when filtered by search), options are reset
		 * todo: needs a solution that also removes options when the group is removed
		 */
		// empty weights.
		// update_option( 'advads-ad-weights', array() );

		$all_weights = get_option( 'advads-ad-weights', array() );

		$ad_groups_assoc = array();

		if ( isset( $_POST['advads-groups-removed-ads'] ) && is_array( $_POST['advads-groups-removed-ads'] ) && isset( $_POST['advads-groups-removed-ads-gid'] ) ) {
			$len = count( $_POST['advads-groups-removed-ads'] );
			for ( $i = 0; $i < $len; $i ++ ) {
				$ad_id                     = absint( wp_unslash( $_POST['advads-groups-removed-ads'][ $i ] ) );
				$group_id                  = absint( wp_unslash( $_POST['advads-groups-removed-ads-gid'][ $i ] ) );
				$ad_groups_assoc[ $ad_id ] = array();
				// remove it from the weights.
				if ( isset( $all_weights[ $group_id ] ) && isset( $all_weights[ $group_id ][ $ad_id ] ) ) {
					unset( $all_weights[ $group_id ][ $ad_id ] );
				}
				// we need to load all the group ids, that are allocated to this ad and then remove the right one only.
				$group_ids = $this->get_groups_by_ad_id( $ad_id );
				foreach ( $group_ids as $gid ) {
					if ( $gid !== $group_id ) {
						$ad_groups_assoc[ $ad_id ][] = $gid;
					}
				}
			}
		}


		// iterate through groups.
		$post_ad_groups = isset( $_POST['advads-groups'] ) ? wp_unslash( $_POST['advads-groups'] ) : array();

		if ( count( $post_ad_groups ) ) {

			foreach ( $post_ad_groups as $_group_id => $_group ) {

				// save basic wp term.
				wp_update_term( $_group_id, Advanced_Ads::AD_GROUP_TAXONOMY, $_group );

				$group = new Advanced_Ads_Group( $_group['id'] );
				if ( isset( $_group['ads'] ) && is_array( $_group['ads'] ) ) {
					foreach ( $_group['ads'] as $_ad_id => $_ad_weight ) {
						/**
						 * Check if this ad is representing the current group and remove it in this case
						 * could cause an infinite loop otherwise
						 * see also /classes/ad_type_group.php::remove_from_ad_group()
						 */
						$ad = new Advanced_Ads_Ad( $_ad_id );

						// we will have to load all the groups allocated to this ad.
						if ( ! isset( $ad_groups_assoc[ $_ad_id ] ) ) {
							$ad_groups_assoc[ $_ad_id ] = $this->get_groups_by_ad_id( $_ad_id );
						}

						if ( isset( $ad->type )
						     && 'group' === $ad->type
						     && isset( $ad->output['group_id'] )
						     && absint( $ad->output['group_id'] ) == $_group_id
						) {
							unset( $_group['ads'][ $_ad_id ] );
						} else {
							$ad_groups_assoc[ $_ad_id ][] = (int) $_group_id;
						}
					}

					// save ad weights.
					$all_weights[ $group->id ] = $group->sanitize_ad_weights( $_group['ads'] );
				}

				// save other attributes.
				$type     = isset( $_group['type'] ) ? $_group['type'] : 'default';
				$ad_count = isset( $_group['ad_count'] ) ? $_group['ad_count'] : 1;
				$options  = isset( $_group['options'] ) ? $_group['options'] : array();

				// allow other add-ons to save their own group attributes.
				$atts = apply_filters( 'advanced-ads-group-save-atts',
					array(
						'type'     => $type,
						'ad_count' => $ad_count,
						'options'  => $options,
					),
					$_group
				);

				$group->save( $atts );
			}

			foreach ( $ad_groups_assoc as $_ad_id => $group_ids ) {
				wp_set_object_terms( $_ad_id, $group_ids, $this->taxonomy );
			};

		}

		update_option( 'advads-ad-weights', $all_weights );

		// reload groups.
		$this->load_groups();

		return true;
	}

	/**
	 * Returns a link to the ad group list page
	 *
	 * @param array $args additional arguments, e.g. action or group_id.
	 *
	 * @return string admin url
	 * @since 1.0.0
	 */
	public static function group_page_url( $args = array() ) {
		$plugin = Advanced_Ads::get_instance();

		$default_args = array(
			'page' => 'advanced-ads-groups',
		);
		$args         = $args + $default_args;

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

}

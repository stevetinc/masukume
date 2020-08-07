<?php
/**
 * Container class for custom filters on admin ad list page.
 *
 * @package WordPress
 * @subpackage Advanced Ads Plugin
 */
class Advanced_Ads_Ad_List_Filters {
	/**
	 * The unique instance of this class.
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Ads data for the ad list table
	 *
	 * @var     array
	 */
	protected $all_ads = array();

	/**
	 * Ads ad groups
	 *
	 * @var     array
	 */
	protected $all_groups = array();

	/**
	 * Ads in each group
	 *
	 * @var     array
	 */
	protected $ads_in_groups = array();

	/**
	 * Ads array with ID as key
	 *
	 * @var     array
	 */
	protected $adsbyid = array();

	/**
	 * All filters available in the current ad list table
	 *
	 * @var     array
	 */
	protected $all_filters = array();

	/**
	 * All ad options for the ad list table
	 *
	 * @var     array
	 */
	protected $all_ads_options = array();

	/**
	 * Constructs the unique instance.
	 */
	private function __construct() {
		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			add_filter( 'posts_results', array( $this, 'post_results' ), 10, 2 );
			add_filter( 'posts_orderby', array( $this, 'orderby_filter' ), 10, 2 );
		}
	}

	/**
	 * Collect available filters for ad overview page.
	 *
	 * @param array $posts array of ads.
	 *
	 * @return null
	 */
	private function collect_filters( $posts ) {

		$all_sizes  = array();
		$all_types  = array();
		$all_dates  = array();
		$all_groups = array();

		$all_filters = array(
			'all_sizes'  => array(),
			'all_types'  => array(),
			'all_dates'  => array(),
			'all_groups' => array(),
		);

		// can not filter correctly with "trashed" posts. Do not display any filtering option in this case.
		if ( isset( $_REQUEST['post_status'] ) && 'trash' === $_REQUEST['post_status'] ) {
			$this->all_filters = $all_filters;

			return;
		}

		$advads = Advanced_Ads::get_instance();

		// put potential groups in another array which we later reduce so that we only check groups we don’t know, yet.
		$groups_to_check = $this->ads_in_groups;

		foreach ( $posts as $post ) {

			if ( ! isset( $this->all_ads_options[ $post->ID ] ) ) {
				continue;
			}
			$ad_option = $this->all_ads_options[ $post->ID ];

			/**
			 * Check if this particular ad belongs to a group and if so,
			 * add the group to the list of filterable groups
			 * skip when the group is already known
			 */
			foreach ( $groups_to_check as $key => $ads ) {
				if ( ! isset( $all_filters['all_groups'][ $key ] ) // skip if this group is already known.
					&& in_array( $post->ID, $ads, true )
					&& isset( $this->all_groups[ $key ] ) ) {
					$all_filters['all_groups'][ $key ] = $this->all_groups[ $key ]['name'];
					// remove groups that are already selected for the filter.
					unset( $groups_to_check[ $key ] );
					continue;
				}
			}

			if ( isset( $ad_option['width'], $ad_option['height'] ) && $ad_option['width'] && $ad_option['height'] ) {
				if ( ! array_key_exists( $ad_option['width'] . 'x' . $ad_option['height'], $all_filters['all_sizes'] ) ) {
					$all_filters['all_sizes'][ $ad_option['width'] . 'x' . $ad_option['height'] ] = $ad_option['width'] . ' x ' . $ad_option['height'];
				}
			}

			if ( isset( $ad_option['type'] ) && 'adsense' === $ad_option['type'] ) {
				$content     = $this->adsbyid[ $post->ID ]->post_content;
				$adsense_obj = false;
				try {
					$adsense_obj = json_decode( $content, true );
				} catch ( Exception $e ) {
					$adsense_obj = false;
				}

				if ( $adsense_obj ) {
					if ( 'responsive' === $adsense_obj['unitType'] ) {
						if ( ! array_key_exists( 'responsive', $all_filters['all_sizes'] ) ) {
							$all_filters['all_sizes']['responsive'] = __( 'Responsive', 'advanced-ads' );
						}
					}
				}
			}

			if ( isset( $ad_option['expiry_date'] ) && $ad_option['expiry_date'] ) {
				if ( time() >= absint( $ad_option['expiry_date'] ) ) {
					if ( ! array_key_exists( 'advads-filter-expired', $all_filters['all_dates'] ) ) {
						$all_filters['all_dates']['advads-filter-expired'] = __( 'expired', 'advanced-ads' );
					}
				} else {
					if ( ! array_key_exists( 'advads-filter-any-exp-date', $all_filters['all_dates'] ) ) {
						$all_filters['all_dates']['advads-filter-any-exp-date'] = __( 'any expiry date', 'advanced-ads' );
					}
				}
			}

			if ( isset( $ad_option['type'] ) // could be missing for new ads that are stored only by WP auto-save.
				&& ! array_key_exists( $ad_option['type'], $all_filters['all_types'] )
				&& isset( $advads->ad_types[ $ad_option['type'] ] )
			) {
				$all_filters['all_types'][ $ad_option['type'] ] = $advads->ad_types[ $ad_option['type'] ]->title;
			}

			$all_filters = apply_filters( 'advanced-ads-ad-list-column-filter', $all_filters, $post, $ad_option );

		}

		$this->all_filters = $all_filters;
	}

	/**
	 * Collects all ads data.
	 *
	 * @param array $posts array of ads.
	 */
	public function collect_all_ads( $posts ) {
		$postsbyid = array();

		foreach ( $posts as $post ) {
			$postsbyid[ $post->ID ] = $post;
		}

		global $wpdb;
		$meta_results = $wpdb->get_results( $wpdb->prepare( 'SELECT post_id, meta_value FROM `' . $wpdb->prefix . 'postmeta` WHERE `meta_key` = %s', 'advanced_ads_ad_options' ), 'ARRAY_A' );

		$options = array();
		foreach ( $meta_results as $_value ) {
			$value                         = maybe_unserialize( $_value['meta_value'] );
			$options[ $_value['post_id'] ] = $value;
		}

		$_groups = Advanced_Ads::get_ad_groups();

		$groups = array();

		/**
		 * It looks like there might be a third-party conflict we haven’t been able to reproduce that causes the group
		 * objects to stay empty. Hence, we introduced the `empty` check.
		 */
		foreach ( $_groups as $g ) {
			if ( empty( $g->term_id ) ) {
				continue;
			}
			$groups[ $g->term_id ] = array(
				'name' => $g->name,
				'slug' => $g->slug,
			);
		}

		$group_ids      = array_keys( $groups );
		$group_ids_str  = implode( ',', $group_ids );
		$term_relations = array();

		/**
		 * We need to use %1$s below, because when using %s the $wpdb->prepare function adds quotation marks around the value,
		 * which breaks the SQL, because the numbers are no longer recognised as such
		 */
		if ( ! empty( $group_ids ) ) {
			$term_relations = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT object_id, term_taxonomy_id FROM `' . $wpdb->prefix . 'term_relationships` WHERE `term_taxonomy_id` IN (' .
					'SELECT term_taxonomy_id FROM `' . $wpdb->prefix . 'term_taxonomy` WHERE `taxonomy` = %s' .
					')',
					Advanced_Ads::AD_GROUP_TAXONOMY
				),
				'ARRAY_A'
			);
		}
		foreach ( $term_relations as $value ) {
			if ( isset( $value['term_taxonomy_id'] ) && isset( $value['object_id'] ) ) {
				$this->ads_in_groups[ absint( $value['term_taxonomy_id'] ) ][] = absint( $value['object_id'] );
			}
		}

		/**
		 *  Store all data
		 */
		$this->all_ads         = $posts;
		$this->adsbyid         = $postsbyid;
		$this->all_ads_options = $options;

		$this->all_groups = $groups;
	}

	/**
	 * Retrieve the stored ads list.
	 */
	public function get_all_ads() {
		return $this->all_ads;
	}

	/**
	 * Retrieve all filters that can be applied.
	 */
	public function get_all_filters() {
		return $this->all_filters;
	}

	/**
	 * Re-order the posts list by post title.
	 *
	 * @param string   $orderby the previous orderby value.
	 * @param WP_Query $the_query the current WP_Query object.
	 *
	 * @return mixed
	 */
	public function orderby_filter( $orderby, $the_query ) {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}
		$scr = get_current_screen();
		global $wpdb;

		// execute only on the first query.
		if ( ! $scr ) {
			// and only on the ad list page.
			$request = wp_unslash( $_REQUEST );
			$server  = wp_unslash( $_SERVER );
			if ( false !== strpos( $server['PHP_SELF'], 'edit.php' ) && isset( $request['post_type'] ) && Advanced_Ads::POST_TYPE_SLUG === $request['post_type'] ) {
				$orderby = 'post_title ASC';
			}
		}

		return $orderby;
	}

	/**
	 * Edit the query for list table.
	 *
	 * @param array    $posts the posts array from the query.
	 * @param WP_Query $the_query the current WP_Query object.
	 *
	 * @return array with posts
	 */
	public function post_results( $posts, $the_query ) {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return $posts;
		}

		// If for some reason, requested posts are not ads, abort everything.
		if ( count( $posts ) && isset( $_REQUEST['post_type'] ) && Advanced_Ads::POST_TYPE_SLUG === $_REQUEST['post_type'] ) {
			if ( Advanced_Ads::POST_TYPE_SLUG !== $posts[0]->post_type ) {
				return $posts;
			}
		}

		$new_posts = $posts;
		$scr       = get_current_screen();

		// execute only on the first query.
		if ( ! $scr ) {
			// if there are already some records, don't re-execute. Prevents bug with plugins making early extra query.
			if ( count( $this->all_ads ) ) {
				return $posts;
			}
			// and only on the ad list page.
			$request = wp_unslash( $_REQUEST );
			$server  = wp_unslash( $_SERVER );
			if ( false !== strpos( $server['PHP_SELF'], 'edit.php' ) && isset( $request['post_type'] ) && Advanced_Ads::POST_TYPE_SLUG === $request['post_type'] ) {
				$this->collect_all_ads( $posts );
			}

			return $posts;
		}

		// edit only on ad list page.
		if ( 'edit-advanced_ads' !== $scr->id ) {
			return $posts;
		}

		// the new post list.
		if ( isset( $_REQUEST['post_status'] ) && 'trash' === $_REQUEST['post_status'] ) {
			// if looking in trash, return the original trashed posts list.
			$new_posts = $posts;
		} else {
			// in other cases, apply our custom filters.
			$new_posts = $this->ad_filters( $this->all_ads, $the_query );
		}

		// re-collect data from the search results.
		if ( isset( $_REQUEST['s'] ) && '' !== $_REQUEST['s'] ) {
			$this->collect_all_ads( $posts );
			$new_posts = $this->ad_filters( $this->all_ads, $the_query );
		}

		$per_page = $the_query->query_vars['posts_per_page'] ? $the_query->query_vars['posts_per_page'] : 20;

		if ( $per_page < count( $new_posts ) ) {
			$paged                  = isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 1;
			$total                  = count( $new_posts );
			$new_posts              = array_slice( $new_posts, ( $paged - 1 ) * $per_page, $per_page );
			$the_query->found_posts = $total;
			$the_query->post_count  = count( $new_posts );
		}

		// replace the post list.
		$the_query->posts = $new_posts;

		return $new_posts;
	}

	/**
	 * Apply ad filters on post array
	 *
	 * @param array    $posts the original post array.
	 * @param WP_Query $the_query the current WP_Query object.
	 *
	 * @return array with posts
	 */
	private function ad_filters( $posts, &$the_query ) {
		$using_original = true;
		$request        = wp_unslash( $_REQUEST );

		/**
		 *  Filter post status
		 */
		if ( isset( $request['post_status'] ) && '' !== $request['post_status'] && ! in_array( $request['post_status'], array( 'all', 'trash' ), true ) ) {
			$new_posts = array();
			foreach ( $this->all_ads as $post ) {
				if ( $request['post_status'] === $post->post_status ) {
					$new_posts[] = $post;
				}
			}
			$posts                  = $new_posts;
			$the_query->found_posts = count( $posts );
			$using_original         = false;
		}

		/**
		 *  Filter post author
		 */
		if ( isset( $request['author'] ) && '' !== $request['author'] ) {
			$author    = absint( $request['author'] );
			$new_posts = array();
			$the_list  = $using_original ? $this->all_ads : $posts;
			foreach ( $the_list as $post ) {
				if ( absint( $post->post_author ) === $author ) {
					$new_posts[] = $post;
				}
			}
			$posts                  = $new_posts;
			$the_query->found_posts = count( $posts );
			$using_original         = false;
		}

		/**
		 *  Filter groups
		 */
		if ( isset( $request['adgroup'] ) && '' !== $request['adgroup'] ) {
			$new_posts = array();
			$the_list  = $using_original ? $this->all_ads : $posts;
			foreach ( $the_list as $post ) {
				if ( isset( $this->ads_in_groups[ absint( $request['adgroup'] ) ] ) && in_array( $post->ID, $this->ads_in_groups[ absint( $request['adgroup'] ) ], true ) ) {
					$new_posts[] = $post;
				}
			}
			$posts                  = $new_posts;
			$the_query->found_posts = count( $posts );
			$using_original         = false;
		}

		/**
		 * Filter by taxonomy
		 */
		if ( isset( $request['taxonomy'] ) && isset( $request['term'] ) ) {

			$term = $request['term'];
			global $wpdb;
			$q = 'SELECT `object_id` FROM `' . $wpdb->prefix . 'term_relationships` WHERE `term_taxonomy_id` = (' .
				 'SELECT ' . $wpdb->prefix . 'terms.term_id FROM `' . $wpdb->prefix . 'terms` INNER JOIN ' .
				 $wpdb->prefix . 'term_taxonomy on ' . $wpdb->prefix . 'terms.term_id = ' . $wpdb->prefix . 'term_taxonomy.term_id ' .
				 'WHERE ' . $wpdb->prefix . 'terms.slug = %s AND ' . $wpdb->prefix . 'term_taxonomy.taxonomy = %s' .
				 ')';

			$q = $wpdb->prepare( $q, $term, Advanced_Ads::AD_GROUP_TAXONOMY );

			$object_ids  = $wpdb->get_results( $q, 'ARRAY_A' );
			$ads_in_taxo = array();

			foreach ( $object_ids as $object ) {
				$ads_in_taxo[] = absint( $object['object_id'] );
			}

			$new_posts = array();
			$the_list  = $using_original ? $this->all_ads : $posts;
			foreach ( $the_list as $post ) {
				if ( in_array( $post->ID, $ads_in_taxo, true ) ) {
					$new_posts[] = $post;
				}
			}
			$posts                  = $new_posts;
			$the_query->found_posts = count( $posts );
			$using_original         = false;

		}

		/**
		 * Filter ad type
		 */
		if ( isset( $request['adtype'] ) && '' !== $request['adtype'] ) {
			$new_posts = array();
			$the_list  = $using_original ? $this->all_ads : $posts;
			foreach ( $the_list as $post ) {
				if ( isset( $this->all_ads_options[ $post->ID ] ) ) {
					$option = $this->all_ads_options[ $post->ID ];
					if ( $request['adtype'] === $option['type'] ) {
						$new_posts[] = $post;
					}
				}
			}
			$posts                  = $new_posts;
			$the_query->found_posts = count( $posts );
			$using_original         = false;
		}

		/**
		 * Filter ad size
		 */
		if ( isset( $request['adsize'] ) && '' !== $request['adsize'] ) {
			$new_posts = array();
			$the_list  = $using_original ? $this->all_ads : $posts;
			foreach ( $the_list as $post ) {
				if ( isset( $this->all_ads_options[ $post->ID ] ) ) {
					$option = $this->all_ads_options[ $post->ID ];
					if ( 'responsive' === $request['adsize'] ) {
						if ( 'adsense' === $option['type'] ) {
							$content = false;
							try {
								$content = json_decode( $post->post_content, true );
							} catch ( Exception $e ) {
								$content = false;
							}
							if ( $content && 'responsive' === $content['unitType'] ) {
								$new_posts[] = $post;
							}
						}
					} else {
						$width  = isset( $option['width'] ) ? $option['width'] : 0;
						$height = isset( $option['height'] ) ? $option['height'] : 0;
						if ( $request['adsize'] === $width . 'x' . $height ) {
							$new_posts[] = $post;
						}
					}
				}
			}
			$posts                  = $new_posts;
			$the_query->found_posts = count( $posts );
			$using_original         = false;
		}

		/**
		 * Filter ad timing
		 */
		if ( isset( $request['addate'] ) && '' !== $request['addate'] ) {
			if ( 'advads-filter-any-exp-date' === urldecode( $request['addate'] ) ) {
				$new_posts = array();
				$the_list  = $using_original ? $this->all_ads : $posts;
				foreach ( $the_list as $post ) {
					if ( isset( $this->all_ads_options[ $post->ID ] ) ) {
						$option = $this->all_ads_options[ $post->ID ];
						if ( ! empty( $option['expiry_date'] ) ) {
							$new_posts[] = $post;
						}
					}
				}
				$posts                  = $new_posts;
				$the_query->found_posts = count( $posts );
				$using_original         = false;
			} elseif ( 'advads-filter-expired' === urldecode( $request['addate'] ) ) {
				$new_posts = array();
				$the_list  = $using_original ? $this->all_ads : $posts;
				foreach ( $the_list as $post ) {
					if ( isset( $this->all_ads_options[ $post->ID ] ) ) {
						$option = $this->all_ads_options[ $post->ID ];
						if ( $option['expiry_date'] && time() >= $option['expiry_date'] ) {
							$new_posts[] = $post;
						}
					}
				}
				$posts                  = $new_posts;
				$the_query->found_posts = count( $posts );
				$using_original         = false;
			}
		}

		$posts                  = apply_filters( 'advanced-ads-ad-list-filter', $posts, $this->all_ads_options );
		$the_query->found_posts = count( $posts );

		$this->collect_filters( $posts );

		return $posts;
	}

	/**
	 * Return the instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

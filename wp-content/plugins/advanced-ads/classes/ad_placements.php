<?php

/**
 * Advanced Ads
 *
 * @package   Advanced_Ads_Placements
 * @author    Thomas Maier <support@wpadvancedads.com>
 * @license   GPL-2.0+
 * @link      https://wpadvancedads.com
 * @copyright 2014 Thomas Maier, Advanced Ads GmbH
 */

/**
 * Grouping placements functions
 *
 * @since 1.1.0
 * @package Advanced_Ads_Placements
 * @author  Thomas Maier <support@wpadvancedads.com>
 */
class Advanced_Ads_Placements {

	/**
	 * Gather placeholders which later are replaced by the ads
	 *
	 * @var array $ads_for_placeholders
	 */
	private static $ads_for_placeholders = array();
	/**
	 * Temporarily change content during processing
	 *
	 * @var array $placements
	 */
	private static $replacements = array(
		'gcse:search' => 'gcse__search', // Google custom search namespaced tags.
	);

	/**
	 * Get placement types
	 *
	 * @return array $types array with placement types
	 * @since 1.2.1
	 */
	public static function get_placement_types() {
		$types = array(
			'default'        => array(
				'title'       => __( 'Manual Placement', 'advanced-ads' ),
				'description' => __( 'Manual placement to use as function or shortcode.', 'advanced-ads' ),
				'image'       => ADVADS_BASE_URL . 'admin/assets/img/placements/manual.png',
				'order'       => 80,
				'options'     => array(
					'show_position'  => true,
					'show_lazy_load' => true,
					'amp'            => true,
				),
			),
			'header'         => array(
				'title'       => __( 'Header Code', 'advanced-ads' ),
				'description' => __( 'Injected in Header (before closing &lt;/head&gt; Tag, often not visible).', 'advanced-ads' ),
				'image'       => ADVADS_BASE_URL . 'admin/assets/img/placements/header.png',
				'order'       => 3,
			),
			'footer'         => array(
				'title'       => __( 'Footer Code', 'advanced-ads' ),
				'description' => __( 'Injected in Footer (before closing &lt;/body&gt; Tag).', 'advanced-ads' ),
				'image'       => ADVADS_BASE_URL . 'admin/assets/img/placements/footer.png',
				'order'       => 95,
				'options'     => array( 'amp' => true ),
			),
			'post_top'       => array(
				'title'       => __( 'Before Content', 'advanced-ads' ),
				'description' => __( 'Injected before the post content.', 'advanced-ads' ),
				'image'       => ADVADS_BASE_URL . 'admin/assets/img/placements/content-before.png',
				'order'       => 20,
				'options'     => array(
					'show_position'    => true,
					'show_lazy_load'   => true,
					'uses_the_content' => true,
					'amp'              => true,
				),
			),
			'post_bottom'    => array(
				'title'       => __( 'After Content', 'advanced-ads' ),
				'description' => __( 'Injected after the post content.', 'advanced-ads' ),
				'image'       => ADVADS_BASE_URL . 'admin/assets/img/placements/content-after.png',
				'order'       => 35,
				'options'     => array(
					'show_position'    => true,
					'show_lazy_load'   => true,
					'uses_the_content' => true,
					'amp'              => true,
				),
			),
			'post_content'   => array(
				'title'       => __( 'Content', 'advanced-ads' ),
				'description' => __( 'Injected into the content. You can choose the paragraph after which the ad content is displayed.', 'advanced-ads' ),
				'image'       => ADVADS_BASE_URL . 'admin/assets/img/placements/content-within.png',
				'order'       => 21,
				'options'     => array(
					'show_position'    => true,
					'show_lazy_load'   => true,
					'uses_the_content' => true,
					'amp'              => true,
				),
			),
			'sidebar_widget' => array(
				'title'       => __( 'Sidebar Widget', 'advanced-ads' ),
				'description' => __( 'Create a sidebar widget with an ad. Can be placed and used like any other widget.', 'advanced-ads' ),
				'image'       => ADVADS_BASE_URL . 'admin/assets/img/placements/widget.png',
				'order'       => 50,
				'options'     => array(
					'show_lazy_load' => true,
					'amp'            => true,
				),
			),
		);

		return apply_filters( 'advanced-ads-placement-types', $types );
	}

	/**
	 * Update placements if sent
	 *
	 * @since 1.5.2
	 */
	public static function update_placements() {

		// check user permissions.
		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_placements' ) ) ) {
			return;
		}

		$success = null;

		// add hook of last opened placement settings to URL.
		$hook = ! empty( $_POST['advads-last-edited-placement'] ) ? '#single-placement-' . $_POST['advads-last-edited-placement'] : '';

		if ( isset( $_POST['advads']['placement'] ) && check_admin_referer( 'advads-placement', 'advads_placement' ) ) {
			$success = self::save_new_placement( $_POST['advads']['placement'] );
		}
		// save placement data.
		if ( isset( $_POST['advads']['placements'] ) && check_admin_referer( 'advads-placement', 'advads_placement' ) ) {
			$success = self::save_placements( $_POST['advads']['placements'] );
		}

		$success = apply_filters( 'advanced-ads-update-placements', $success );

		if ( isset( $success ) ) {
			$message = $success ? 'updated' : 'error';
			wp_redirect( esc_url_raw( add_query_arg( array( 'message' => $message ) ) ) . $hook );
		}
	}

	/**
	 * Save a new placement
	 *
	 * @param array $new_placement information about the new placement.
	 *
	 * @return mixed slug if saved; false if not
	 * @since 1.1.0
	 */
	public static function save_new_placement( $new_placement ) {
		// load placements // -TODO use model.
		$placements = Advanced_Ads::get_ad_placements_array();

		// create slug.
		$new_placement['slug'] = sanitize_title( $new_placement['name'] );

		if ( isset( $placements[ $new_placement['slug'] ] ) ) {
			$i = 1;
			// try to save placement until we found an empty slug.
			do {
				$i ++;
				if ( 100 === $i ) { // prevent endless loop, just in case.
					Advanced_Ads::log( 'endless loop when injecting placement' );
					break;
				}
			} while ( isset( $placements[ $new_placement['slug'] . '_' . $i ] ) );

			$new_placement['slug'] .= '_' . $i;
			$new_placement['name'] .= ' ' . $i;
		}

		// check if slug already exists or is empty.
		if ( '' === $new_placement['slug'] || isset( $placements[ $new_placement['slug'] ] ) || ! isset( $new_placement['type'] ) ) {
			return false;
		}

		// make sure only allowed types are being saved.
		$placement_types       = self::get_placement_types();
		$new_placement['type'] = ( isset( $placement_types[ $new_placement['type'] ] ) ) ? $new_placement['type'] : 'default';
		// escape name.
		$new_placement['name'] = esc_attr( $new_placement['name'] );

		// add new place to all placements.
		$placements[ $new_placement['slug'] ] = array(
			'type' => $new_placement['type'],
			'name' => $new_placement['name'],
			'item' => $new_placement['item'],
		);

		// add index options.
		if ( isset( $new_placement['options'] ) ) {
			$placements[ $new_placement['slug'] ]['options'] = $new_placement['options'];
			if ( isset( $placements[ $new_placement['slug'] ]['options']['index'] ) ) {
				$placements[ $new_placement['slug'] ]['options']['index'] = absint( $placements[ $new_placement['slug'] ]['options']['index'] );
			}
		}

		// save array.
		Advanced_Ads::get_instance()->get_model()->update_ad_placements_array( $placements );

		return $new_placement['slug'];
	}

	/**
	 * Save placements
	 *
	 * @param array $placement_items placements.
	 *
	 * @return mixed true if saved; error message if not
	 * @since 1.1.0
	 */
	public static function save_placements( $placement_items ) {

		// load placements // -TODO use model.
		$placements = Advanced_Ads::get_ad_placements_array();

		foreach ( $placement_items as $_placement_slug => $_placement ) {
			// remove the placement.
			if ( isset( $_placement['delete'] ) ) {
				unset( $placements[ $_placement_slug ] );
				continue;
			}
			// save item.
			if ( isset( $_placement['item'] ) ) {
				$placements[ $_placement_slug ]['item'] = $_placement['item'];
			}
			// save item options.
			if ( isset( $_placement['options'] ) ) {
				$placements[ $_placement_slug ]['options'] = $_placement['options'];
				if ( isset( $placements[ $_placement_slug ]['options']['index'] ) ) {
					$placements[ $_placement_slug ]['options']['index'] = absint( $placements[ $_placement_slug ]['options']['index'] );
				}
			} else {
				$placements[ $_placement_slug ]['options'] = array();
			}
		}

		// save array.
		Advanced_Ads::get_instance()->get_model()->update_ad_placements_array( $placements );

		return true;
	}

	/**
	 * Get items for item select field
	 *
	 * @return array $select items for select field
	 * @since 1.1
	 */
	public static function items_for_select() {
		$select = array();
		$model  = Advanced_Ads::get_instance()->get_model();

		// load all ad groups.
		$groups = $model->get_ad_groups();
		foreach ( $groups as $_group ) {
			$select['groups'][ 'group_' . $_group->term_id ] = $_group->name;
		}

		// load all ads.
		$ads = $model->get_ads(
			array(
				'orderby' => 'title',
				'order'   => 'ASC',
			)
		);
		foreach ( $ads as $_ad ) {
			$select['ads'][ 'ad_' . $_ad->ID ] = $_ad->post_title;
		}

		return $select;
	}

	/**
	 * Get html tags for content injection
	 *
	 * @return array $tags array with tags that can be used for content injection
	 * @since 1.3.5
	 */
	public static function tags_for_content_injection() {
		$headline_tags          = apply_filters( 'advanced-ads-headlines-for-ad-injection', array( 'h2', 'h3', 'h4' ) );
		$headline_tags_imploded = '&lt;' . implode( '&gt;, &lt;', $headline_tags ) . '&gt;';

		$tags = apply_filters(
			'advanced-ads-tags-for-injection',
			array(
				// translators: %s is an html tag.
				'p'           => sprintf( __( 'paragraph (%s)', 'advanced-ads' ), '&lt;p&gt;' ),
				// translators: %s is an html tag.
				'pwithoutimg' => sprintf( __( 'paragraph without image (%s)', 'advanced-ads' ), '&lt;p&gt;' ),
				// translators: %s is an html tag.
				'h2'          => sprintf( __( 'headline 2 (%s)', 'advanced-ads' ), '&lt;h2&gt;' ),
				// translators: %s is an html tag.
				'h3'          => sprintf( __( 'headline 3 (%s)', 'advanced-ads' ), '&lt;h3&gt;' ),
				// translators: %s is an html tag.
				'h4'          => sprintf( __( 'headline 4 (%s)', 'advanced-ads' ), '&lt;h4&gt;' ),
				// translators: %s is an html tag.
				'headlines'   => sprintf( __( 'any headline (%s)', 'advanced-ads' ), $headline_tags_imploded ),
				// translators: %s is an html tag.
				'img'         => sprintf( __( 'image (%s)', 'advanced-ads' ), '&lt;img&gt;' ),
				// translators: %s is an html tag.
				'table'       => sprintf( __( 'table (%s)', 'advanced-ads' ), '&lt;table&gt;' ),
				// translators: %s is an html tag.
				'li'          => sprintf( __( 'list item (%s)', 'advanced-ads' ), '&lt;li&gt;' ),
				// translators: %s is an html tag.
				'blockquote'  => sprintf( __( 'quote (%s)', 'advanced-ads' ), '&lt;blockquote&gt;' ),
				// translators: %s is an html tag.
				'iframe'      => sprintf( __( 'iframe (%s)', 'advanced-ads' ), '&lt;iframe&gt;' ),
				// translators: %s is an html tag.
				'div'         => sprintf( __( 'container (%s)', 'advanced-ads' ), '&lt;div&gt;' ),
				// any HTML tag.
				'anyelement'  => __( 'any element', 'advanced-ads' ),
				// custom
				'custom'      => _x( 'custom', 'for the "custom" content placement option', 'advanced-ads' ),
			)
		);

		return $tags;
	}

	/**
	 * Return content of a placement
	 *
	 * @param string $id slug of the display.
	 * @param array  $args optional arguments (passed to child).
	 *
	 * @return string
	 */
	public static function output( $id = '', $args = array() ) {
		// get placement data for the slug.
		if ( '' == $id ) {
			return;
		}

		$placements = Advanced_Ads::get_ad_placements_array();
		$placement  = ( isset( $placements[ $id ] ) && is_array( $placements[ $id ] ) ) ? $placements[ $id ] : array();

		if ( isset( $args['change-placement'] ) ) {
			// some options was provided by the user.
			$placement = Advanced_Ads_Utils::merge_deep_array( array( $placement, $args['change-placement'] ) );
		}

		if ( isset( $placement['item'] ) && '' !== $placement['item'] ) {
			$_item = explode( '_', $placement['item'] );

			if ( ! isset( $_item[1] ) || empty( $_item[1] ) ) {
				return;
			}

			// inject options.
			if ( isset( $placement['options'] ) && is_array( $placement['options'] ) ) {
				foreach ( $placement['options'] as $_k => $_v ) {
					if ( ! isset( $args[ $_k ] ) ) {
						$args[ $_k ] = $_v;
					}
				}
			}

			// inject placement type.
			if ( isset( $placement['type'] ) ) {
				$args['placement_type'] = $placement['type'];
			}

			// options.
			$prefix = Advanced_Ads_Plugin::get_instance()->get_frontend_prefix();

			// return either ad or group content.
			switch ( $_item[0] ) {
				case 'ad':
				case Advanced_Ads_Select::AD:
					// create class from placement id (not if header injection).
					if ( ! isset( $placement['type'] ) || 'header' !== $placement['type'] ) {
						if ( ! isset( $args['output'] ) ) {
							$args['output'] = array();
						}
						if ( ! isset( $args['output']['class'] ) ) {
							$args['output']['class'] = array();
						}
						$class = $prefix . $id;
						if ( ! in_array( $class, $args['output']['class'] ) ) {
							$args['output']['class'][] = $class;
						}
					}

					// fix method id.
					$_item[0] = Advanced_Ads_Select::AD;

					/**
					 * Deliver the translated version of an ad if set up with WPML.
					 * If an ad is not translated, show the ad in the original language when this is the selected option in the WPML settings.
					 *
					 * @source https://wpml.org/wpml-hook/wpml_object_id/
					 * @source https://wpml.org/forums/topic/backend-custom-post-types-page-overview-with-translation-options/
					 *
					 */
					if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
						global $sitepress;
						$_item[1] = apply_filters( 'wpml_object_id', $_item[1], 'advanced_ads', $sitepress->is_display_as_translated_post_type( 'advanced_ads' ) );
					}
					break;

				case Advanced_Ads_Select::PLACEMENT:
					// avoid loops (programmatical error).
					return;

				case Advanced_Ads_Select::GROUP:
					$class = $prefix . $id;
					if ( ( isset( $placement['type'] ) && $placement['type'] !== 'header' )
						 && ( ! isset( $args['output']['class'] )
							  || ! is_array( $args['output']['class'] )
							  || ! in_array( $class, $args['output']['class'] ) ) ) {
						$args['output']['class'][] = $class;
					}
				default:
			}

			// create placement id for various features.
			$args['output']['placement_id'] = $id;

			// add the placement to the global output array.
			$advads = Advanced_Ads::get_instance();
			$name   = isset( $placement['name'] ) ? $placement['name'] : $id;

			if ( ! isset( $args['global_output'] ) || $args['global_output'] ) {
				$advads->current_ads[] = array(
					'type'  => 'placement',
					'id'    => $id,
					'title' => $name,
				);
			}

			$result = Advanced_Ads_Select::get_instance()->get_ad_by_method( (int) $_item[1], $_item[0], $args );

			return $result;
		}

		return;
	}

	/**
	 * Inject ads directly into the content
	 *
	 * @param string $placement_id Id of the placement.
	 * @param array  $placement_opts Placement options.
	 * @param string $content Content to inject placement into.
	 *
	 * @return string $content Content with injected placement.
	 * @since 1.2.1
	 */
	public static function &inject_in_content( $placement_id, $placement_opts, &$content ) {
		if ( ! extension_loaded( 'dom' ) ) {
			return $content;
		}

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

		// get plugin options.
		$plugin_options = Advanced_Ads::get_instance()->options();

		$wp_charset = get_bloginfo( 'charset' );
		// parse document as DOM (fragment - having only a part of an actual post given).

		$content_to_load = self::get_content_to_load( $content, $wp_charset );
		if ( ! $content_to_load ) {
			return $content;
		}

		$dom = new DOMDocument( '1.0', $wp_charset );
		// may loose some fragments or add autop-like code.
		libxml_use_internal_errors( true ); // avoid notices and warnings - html is most likely malformed.

		$success = $dom->loadHtml( '<!DOCTYPE html><html><meta http-equiv="Content-Type" content="text/html; charset=' . $wp_charset . '" /><body>' . $content_to_load );
		libxml_use_internal_errors( false );
		if ( true !== $success ) {
			// -TODO handle cases were dom-parsing failed (at least inform user)
			return $content;
		}

		// parse arguments.
		$tag = isset( $placement_opts['tag'] ) ? $placement_opts['tag'] : 'p';
		$tag = preg_replace( '/[^a-z0-9]/i', '', $tag ); // simplify tag.
		/**
		 * Store the original tag value since $tag is changed on the fly and we might want to know the original selected
		 * options for some checks later.
		 */
		$tag_option = $tag;

		// allow more complex xPath expression.
		$tag = apply_filters( 'advanced-ads-placement-content-injection-xpath', $tag, $placement_opts );

		/**
		 * Handle advanced tags.
		 */
		switch ( $tag_option ) {
			case 'p':
				// exclude paragraphs within blockquote tags
				$tag = 'p[not(parent::blockquote)]';
				break;
			case 'pwithoutimg':
				// convert option name into correct path, exclude paragraphs within blockquote tags
				$tag = 'p[not(descendant::img) and not(parent::blockquote)]';
				break;
			case 'img':
				/*
				 * Handle: 1) "img" tags 2) "image" block 3) "gallery" block 4) "gallery shortcode" 5) "wp_caption" shortcode
				 * Handle the gallery created by the block or the shortcode as one image.
				 * Prevent injection of ads next to images in tables.
				*/
				// Default shortcodes, including non-HTML5 versions.
				$shortcodes = "@class and (
						contains(concat(' ', normalize-space(@class), ' '), ' gallery-size') or
						contains(concat(' ', normalize-space(@class), ' '), ' wp-caption ') )";
				$tag = "*[self::img or self::figure or self::div[$shortcodes]]
					[not(ancestor::table or ancestor::figure or ancestor::div[$shortcodes])]";
				break;
			// any headline. By default h2, h3, and h4
			case 'headlines':
				$headlines = apply_filters( 'advanced-ads-headlines-for-ad-injection', array( 'h2', 'h3', 'h4' ) );

				foreach ( $headlines as &$headline ) {
					$headline = 'self::' . $headline;
				}
				$tag = '*[' . implode( ' or ', $headlines ) . ']'; // /html/body/*[self::h2 or self::h3 or self::h4]
				break;
			// any HTML element that makes sense in the content
			case 'anyelement':
				$exclude = array(
					'html',
					'body',
					'script',
					'style',
					'tr',
					'td',
					// Inline tags.
					'a',
					'abbr',
					'b',
					'bdo',
					'br',
					'button',
					'cite',
					'code',
					'dfn',
					'em',
					'i',
					'img',
					'kbd',
					'label',
					'option',
					'q',
					'samp',
					'select',
					'small',
					'span',
					'strong',
					'sub',
					'sup',
					'textarea',
					'time',
					'tt',
					'var',
				);
				$tag     = '*[not(self::' . implode( ' or self::', $exclude ) . ')]';
				break;
			case 'custom':
				// get the path for the "custom" tag choice, use p as a fallback to prevent it from showing any ads if users left it empty
				$tag = ! empty( $placement_opts['xpath'] ) ? stripslashes( $placement_opts['xpath'] ) : 'p';
				break;
		}

		// select positions.
		$xpath = new DOMXPath( $dom );
		$items = $xpath->query( '/html/body/' . $tag );

		$options = array(
			'allowEmpty'                   => false,   // whether the tag can be empty to be counted.
			'paragraph_select_from_bottom' => isset( $placement_opts['start_from_bottom'] ) && $placement_opts['start_from_bottom'],
			// only has before and after.
			'before'                       => isset( $placement_opts['position'] ) && 'before' === $placement_opts['position'],
		);

		$options['paragraph_id'] = isset( $placement_opts['index'] ) ? $placement_opts['index'] : 1;
		$options['paragraph_id'] = max( 1, (int) $options['paragraph_id'] );

		// if there are too few items at this level test nesting.
		$options['itemLimit'] = 'p' === $tag_option ? 2 : 1;

		// trigger such a high item limit that all elements will be considered.
		if ( ! empty( $plugin_options['content-injection-level-disabled'] ) ) {
			$options['itemLimit'] = 1000;
		}

		// handle tags that are empty by definition or could be empty ("custom" option)
		if ( in_array( $tag_option, array( 'img', 'iframe', 'custom' ), true ) ) {
			$options['allowEmpty'] = true;
		}

		// allow hooks to change some options.
		$options = apply_filters(
			'advanced-ads-placement-content-injection-options',
			$options,
			$tag_option
		);

		if ( $items->length < $options['itemLimit'] ) {
			$items = $xpath->query( '/html/body/*/' . $tag );
		}
		// try third level.
		if ( $items->length < $options['itemLimit'] ) {
			$items = $xpath->query( '/html/body/*/*/' . $tag );
		}
		// try all levels as last resort.
		if ( $items->length < $options['itemLimit'] ) {
			$items = $xpath->query( '//' . $tag );
		}

		// allow to select other elements.
		$items = apply_filters( 'advanced-ads-placement-content-injection-items', $items, $xpath, $tag_option );

		// filter empty tags from items.
		$whitespaces = json_decode( '"\t\n\r \u00A0"' );
		$paragraphs  = array();
		foreach ( $items as $item ) {
			if ( $options['allowEmpty'] || ( isset( $item->textContent ) && trim( $item->textContent, $whitespaces ) !== '' ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
				$paragraphs[] = $item;
			}
		}

		$ancestors_to_limit = self::get_ancestors_to_limit( $xpath );
		$paragraphs         = self::filter_by_ancestors_to_limit( $paragraphs, $ancestors_to_limit );

		$options['paragraph_count'] = count( $paragraphs );

		if ( $options['paragraph_count'] >= $options['paragraph_id'] ) {
			$offset     = $options['paragraph_select_from_bottom'] ? $options['paragraph_count'] - $options['paragraph_id'] : $options['paragraph_id'] - 1;
			$offsets    = apply_filters( 'advanced-ads-placement-content-offsets', array( $offset ), $options, $placement_opts, $xpath, $paragraphs, $dom );
			$did_inject = false;

			foreach ( $offsets as $offset ) {

				// inject.
				$node = apply_filters( 'advanced-ads-placement-content-injection-node', $paragraphs[ $offset ], $tag, $options['before'] );

				// Prevent injection into image caption and gallery.
				$parent = $node;
				for ( $i = 0; $i < 4; $i++ ) {
					$parent = $parent->parentNode;
					if ( ! $parent instanceof DOMElement ) {
						break;
					}
					if ( preg_match( '/\b(wp-caption|gallery-size)\b/', $parent->getAttribute( 'class' ) ) ) {
						$node = $parent;
						break;
					}
				}

				// make sure that the ad is injected outside the link
				if ( 'img' === $tag_option && 'a' === $node->parentNode->tagName ) {
					if ( $options['before'] ) {
						$node->parentNode;
					} else {
						// go one level deeper if inserted after to not insert the ad into the link; probably after the paragraph
						$node->parentNode->parentNode;
					}
				}

				$ad_content = Advanced_Ads_Select::get_instance()->get_ad_by_method( $placement_id, 'placement', $placement_opts );

				if ( trim( $ad_content, $whitespaces ) === '' ) {
					continue;
				}

				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
				$ad_content = self::filter_ad_content( $ad_content, $node->tagName, $options );

				// convert HTML to XML!
				$ad_dom = new DOMDocument( '1.0', $wp_charset );
				libxml_use_internal_errors( true );
				$ad_dom->loadHtml( '<!DOCTYPE html><html><meta http-equiv="Content-Type" content="text/html; charset=' . $wp_charset . '" /><body>' . $ad_content );
				// log errors.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG && current_user_can( 'advanced_ads_manage_options' ) ) {
					foreach ( libxml_get_errors() as $_error ) {
						// continue, if there is '&' symbol, but not HTML entity.
						if ( false === stripos( $_error->message, 'htmlParseEntityRef:' ) ) {
							Advanced_Ads::log( 'possible content injection error for placement "' . $placement_id . '": ' . print_r( $_error, true ) );
						}
					}
				}

				if ( $options['before'] ) {
					$ref_node = $node;

					foreach ( $ad_dom->getElementsByTagName( 'body' )->item( 0 )->childNodes as $importedNode ) {
						$importedNode = $dom->importNode( $importedNode, true );
						$ref_node->parentNode->insertBefore( $importedNode, $ref_node );
					}
				} else {
					// append before next node or as last child to body.
					$ref_node = $node->nextSibling;
					if ( isset( $ref_node ) ) {

						foreach ( $ad_dom->getElementsByTagName( 'body' )->item( 0 )->childNodes as $importedNode ) {
							$importedNode = $dom->importNode( $importedNode, true );
							$ref_node->parentNode->insertBefore( $importedNode, $ref_node );
						}
					} else {
						// append to body; -TODO using here that we only select direct children of the body tag.
						foreach ( $ad_dom->getElementsByTagName( 'body' )->item( 0 )->childNodes as $importedNode ) {
							$importedNode = $dom->importNode( $importedNode, true );
							$node->parentNode->appendChild( $importedNode );
						}
					}
				}

				libxml_use_internal_errors( false );
				$did_inject = true;
			}

			if ( ! $did_inject ) {
				return $content;
			}

			$content_orig = $content;
			// convert to text-representation.
			$content = $dom->saveHTML();
			$content = self::prepare_output( $content, $content_orig );

			/**
			 * Show a warning to ad admins in the Ad Health bar in the frontend, when
			 *
			 * * the level limitation was not disabled
			 * * could not inject one ad (as by use of `elseif` here)
			 * * but there are enough elements on the site, but just in sub-containers
			 */
		} elseif ( current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) )
				   && empty( $plugin_options['content-injection-level-disabled'] ) ) {

			// Check if there are more elements without limitation.
			$all_items = $xpath->query( '//' . $tag );

			$paragraphs = array();
			foreach ( $all_items as $item ) {
				if ( $options['allowEmpty'] || ( isset( $item->textContent ) && trim( $item->textContent, $whitespaces ) !== '' ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
					$paragraphs[] = $item;
				}
			}

			$paragraphs = self::filter_by_ancestors_to_limit( $paragraphs, $ancestors_to_limit );
			if ( $options['paragraph_id'] <= count( $paragraphs ) ) {
				// Add a warning to ad health.
				add_filter( 'advanced-ads-ad-health-nodes', array( 'Advanced_Ads_Placements', 'add_ad_health_node' ) );
			}
		}

		// phpcs:enable

		return $content;
	}

	/**
	 * Get content to load.
	 *
	 * @param string $content Original content.
	 * @param string $wp_charset blog charset.
	 *
	 * @return string $content Content to load.
	 */
	private static function get_content_to_load( $content, $wp_charset ) {
		$plugin_options = Advanced_Ads::get_instance()->options();

		// Prevent removing closing tags in scripts.
		$content_to_load = preg_replace( '/<script.*?<\/script>/', '<!--\0-->', $content );

		// check which priority the wpautop filter has; might have been disabled on purpose.
		$wpautop_priority = has_filter( 'the_content', 'wpautop' );
		if ( $wpautop_priority && Advanced_Ads_Plugin::get_instance()->get_content_injection_priority() < $wpautop_priority ) {
			$content_to_load = wpautop( $content_to_load );
		}

		return $content_to_load;
	}

	/**
	 * Filter ad content.
	 *
	 * @param string $ad_content Ad content.
	 * @param string $tag_name tar before/after the content.
	 * @param array  $options Injection options.
	 *
	 * @return string ad content.
	 */
	private static function filter_ad_content( $ad_content, $tag_name, $options ) {
		$plugin_options = Advanced_Ads::get_instance()->options();

		// Inject placeholder.
		$id                           = count( self::$ads_for_placeholders );
		self::$ads_for_placeholders[] = array(
			'id'   => $id,
			'tag'  => $tag_name,
			'type' => $options['before'] ? 'before' : 'after',
			'ad'   => $ad_content,
		);
		$ad_content                   = '%advads_placeholder_' . $id . '%';

		return $ad_content;
	}

	/**
	 * Prepare output.
	 *
	 * @param string $content Modified content.
	 * @param string $content_orig Original content.
	 *
	 * @return string $content Content to output.
	 */
	private static function prepare_output( $content, $content_orig ) {
		$plugin_options = Advanced_Ads::get_instance()->options();

		$content                    = self::inject_ads( $content, $content_orig, self::$ads_for_placeholders );
		self::$ads_for_placeholders = array();

		return $content;
	}

	/**
	 * Search for ad placeholders in the `$content` to determine positions at which to inject ads.
	 * Given the positions, inject ads into `$content_orig.
	 *
	 * @param string $content Post content with injected ad placeholders.
	 * @param string $content_orig Unmodified post content.
	 * @param array  $options Injection options.
	 * @param array  $ads_for_placeholders Array of ads.
	 *  Each ad contains placeholder id, before or after which tag to inject the ad, the ad content.
	 *
	 * @return string $content
	 */
	private static function inject_ads( $content, $content_orig, $ads_for_placeholders ) {
		$self_closing_tags = array(
			'area',
			'base',
			'basefont',
			'bgsound',
			'br',
			'col',
			'embed',
			'frame',
			'hr',
			'img',
			'input',
			'keygen',
			'link',
			'meta',
			'param',
			'source',
			'track',
			'wbr',
		);

		// It is not possible to append/prepend in self closing tags.
		foreach ( $ads_for_placeholders as &$ad_content ) {
			if ( ( 'prepend' === $ad_content['type'] || 'append' === $ad_content['type'] )
				 && in_array( $ad_content['tag'], $self_closing_tags, true ) ) {
				$ad_content['type'] = 'after';
			}
		}
		unset( $ad_content );
		usort( $ads_for_placeholders, array( 'Advanced_Ads_Placements', 'sort_ads_for_placehoders' ) );

		// Add tags before/after which ad placehoders were injected.
		foreach ( $ads_for_placeholders as $ad_content ) {
			$tag = $ad_content['tag'];

			switch ( $ad_content['type'] ) {
				case 'before':
				case 'prepend':
					$alts[] = "<${tag}[^>]*>";
					break;
				case 'after':
					if ( in_array( $tag, $self_closing_tags, true ) ) {
						$alts[] = "<${tag}[^>]*>";
					} else {
						$alts[] = "</${tag}>";
					}
					break;
				case 'append':
					$alts[] = "</${tag}>";
					break;
			}
		}
		$alts       = array_unique( $alts );
		$tag_regexp = implode( '|', $alts );
		// Add ad placeholder.
		$alts[]                     = '%advads_placeholder_(?:\d+)%';
		$tag_and_placeholder_regexp = implode( '|', $alts );

		preg_match_all( "#{$tag_and_placeholder_regexp}#i", $content, $tag_matches );
		$count = 0;

		// For each tag located before/after an ad placeholder, find its offset among the same tags.
		foreach ( $tag_matches[0] as $r ) {
			if ( preg_match( '/%advads_placeholder_(\d+)%/', $r, $result ) ) {
				$id       = $result[1];
				$found_ad = false;
				foreach ( $ads_for_placeholders as $n => $ad ) {
					if ( (int) $ad['id'] === (int) $id ) {
						$found_ad = $ad;
						break;
					}
				}
				if ( ! $found_ad ) {
					continue;
				}

				switch ( $found_ad['type'] ) {
					case 'before':
					case 'append':
						$ads_for_placeholders[ $n ]['offset'] = $count;
						break;
					case 'after':
					case 'prepend':
						$ads_for_placeholders[ $n ]['offset'] = $count - 1;
						break;
				}
			} else {
				$count ++;
			}
		}

		// Find tags before/after which we need to inject ads.
		preg_match_all( "#{$tag_regexp}#i", $content_orig, $orig_tag_matches, PREG_OFFSET_CAPTURE );
		$new_content = '';
		$pos         = 0;

		foreach ( $orig_tag_matches[0] as $n => $r ) {
			$to_inject = array();
			// Check if we need to inject an ad at this offset.
			foreach ( $ads_for_placeholders as $ad ) {
				if ( isset( $ad['offset'] ) && $ad['offset'] === $n ) {
					$to_inject[] = $ad;
				}
			}

			foreach ( $to_inject as $item ) {
				switch ( $item['type'] ) {
					case 'before':
					case 'append':
						$found_pos = $r[1];
						break;
					case 'after':
					case 'prepend':
						$found_pos = $r[1] + strlen( $r[0] );
						break;
				}

				$new_content .= substr( $content_orig, $pos, $found_pos - $pos );
				$pos          = $found_pos;
				$new_content .= $item['ad'];
			}
		}
		$new_content .= substr( $content_orig, $pos );

		return $new_content;
	}


	/**
	 * Callback function for usort() to sort ads for placeholders.
	 *
	 * @param array $first The first array to compare.
	 * @param array $second The second array to compare.
	 *
	 * @return int 0 if both objects equal. -1 if second array should come first, 1 otherwise.
	 */
	public static function sort_ads_for_placehoders( $first, $second ) {
		if ( $first['type'] === $second['type'] ) {
			return 0;
		}

		$num = array(
			'before'  => 1,
			'prepend' => 2,
			'append'  => 3,
			'after'   => 4,
		);

		return $num[ $first['type'] ] > $num[ $second['type'] ] ? 1 : - 1;
	}

	/**
	 * Add a warning to 'Ad health'.
	 *
	 * @param array $nodes .
	 *
	 * @return array $nodes.
	 */
	public static function add_ad_health_node( $nodes ) {
		$nodes[] = array(
			'type' => 1,
			'data' => array(
				'parent' => 'advanced_ads_ad_health',
				'id'     => 'advanced_ads_ad_health_the_content_not_enough_elements',
				'title'  => sprintf(
				/* translators: %s stands for the name of the "Disable level limitation" option and automatically translated as well */
					__( 'Set <em>%s</em> to show more ads', 'advanced-ads' ),
					__( 'Disable level limitation', 'advanced-ads' )
				),
				'href'   => admin_url( '/admin.php?page=advanced-ads-settings#top#general' ),
				'meta'   => array(
					'class'  => 'advanced_ads_ad_health_warning',
					'target' => '_blank',
				),
			),
		);

		return $nodes;
	}

	/**
	 * Check if the placement can be displayed
	 *
	 * @param int $id placement id.
	 *
	 * @return bool true if placement can be displayed
	 * @since 1.6.9
	 */
	public static function can_display( $id = 0 ) {
		if ( ! isset( $id ) || 0 === $id ) {
			return true;
		}

		return apply_filters( 'advanced-ads-can-display-placement', true, $id );
	}

	/**
	 * Get the placements that includes the ad or group.
	 *
	 * @param string $type 'ad' or 'group'.
	 * @param int    $id Id.
	 *
	 * @return array
	 */
	public static function get_placements_by( $type, $id ) {
		$result = array();

		$placements = Advanced_Ads::get_ad_placements_array();
		foreach ( $placements as $_id => $_placement ) {
			if ( isset( $_placement['item'] ) && $_placement['item'] === $type . '_' . $id ) {
				$result[ $_id ] = $_placement;
			}
		}

		return $result;
	}

	/**
	 * Get paths of ancestors that should not contain ads.
	 *
	 * @param object $xpath DOMXPath object.
	 *
	 * @return array Paths of ancestors.
	 */
	private static function get_ancestors_to_limit( $xpath ) {
		$query = self::get_ancestors_to_limit_query();
		if ( ! $query ) {
			return array();
		}

		$node_list          = $xpath->query( $query );
		$ancestors_to_limit = array();

		foreach ( $node_list as $a ) {
			$ancestors_to_limit[] = $a->getNodePath();
		}

		return $ancestors_to_limit;
	}


	/**
	 * Remove paragraphs that has ancestors that should not contain ads.
	 *
	 * @param array $paragraphs An array of `DOMNode` objects to insert ads before or after.
	 * @param array $ancestors_to_limit Paths of ancestor that should not contain ads.
	 *
	 * @return array $new_paragraphs An array of `DOMNode` objects to insert ads before or after.
	 */
	private static function filter_by_ancestors_to_limit( $paragraphs, $ancestors_to_limit ) {
		$new_paragraphs = array();

		foreach ( $paragraphs as $k => $paragraph ) {
			foreach ( $ancestors_to_limit as $a ) {
				if ( 0 === stripos( $paragraph->getNodePath(), $a ) ) {
					continue 2;
				}
			}

			$new_paragraphs[] = $paragraph;
		}

		return $new_paragraphs;
	}

	/**
	 * Get query to select ancestors that should not contain ads.
	 *
	 * @return string/false DOMXPath query or false.
	 */
	private static function get_ancestors_to_limit_query() {
		/**
		 * TODO:
		 * - support `%` (rand) at the start
		 * - support plain text that node should contain instead of CSS selectors
		 * - support `prev` and `next` as `type`
		 */

		/**
		 * Filter the nodes that limit injection.
		 *
		 * @param array An array of arrays, each of which contains:
		 *
		 * @type string $type Accept: `ancestor` - limit injection inside the ancestor.
		 * @type string $node A "class selector" which targets one class (.) or "id selector" which targets one id (#),
		 *                        optionally with `%` at the end.
		 */
		$items = apply_filters(
			'advanced-ads-content-injection-nodes-without-ads',
			array(
				array(
					// a class anyone can use to prevent automatic ad injection into a specific element.
					'node' => '.advads-stop-injection',
					'type' => 'ancestor',
				),
				array(
					// Product Slider for Beaver Builder by WooPack.
					'node' => '.woopack-product-carousel',
					'type' => 'ancestor',
				),
				array(
					// WP Author Box Lite.
					'node' => '#wpautbox-%',
					'type' => 'ancestor',
				),
				array(
					// GeoDirectory Post Slider.
					'node' => '.geodir-post-slider',
					'type' => 'ancestor',
				),
			)
		);

		$query = array();
		foreach ( $items as $p ) {
			$sel = $p['node'];

			$sel_type = substr( $sel, 0, 1 );
			$sel      = substr( $sel, 1 );

			$rand_pos = strpos( $sel, '%' );
			$sel      = str_replace( '%', '', $sel );
			$sel      = sanitize_html_class( $sel );

			if ( '.' === $sel_type ) {
				if ( false !== $rand_pos ) {
					$query[] = "@class and contains(concat(' ', normalize-space(@class), ' '), ' $sel')";
				} else {
					$query[] = "@class and contains(concat(' ', normalize-space(@class), ' '), ' $sel ')";
				}
			}
			if ( '#' === $sel_type ) {
				if ( false !== $rand_pos ) {
					$query[] = "@id and starts-with(@id, '$sel')";
				} else {
					$query[] = "@id and @id = '$sel'";
				}
			}
		}

		if ( ! $query ) {
			return false;
		}

		return '//*[' . implode( ' or ', $query ) . ']';
	}

	/**
	 * Sort placements
	 *
	 * @param array  $placements Existing placements.
	 * @param string $orderby The field to order by. Accept `name` or `type`.
	 * @return array $placements Sorted placements.
	 */
	public static function sort( $placements, $orderby = 'name' ) {
		if ( ! is_array( $placements ) ) {
			return array();
		}
		if ( 'name' === $orderby ) {
			ksort( $placements );
			return $placements;
		}
		uasort( $placements, array( 'Advanced_Ads_Placements', 'sort_by_type_callback' ) );
		return $placements;

	}

	/**
	 * Callback to sort placements by type.
	 *
	 * @param array $f First placement.
	 * @param array $s Second placement.
	 * @return int 0 If placements are equal, -1 if the first should come first, 1 otherwise.
	 */
	private static function sort_by_type_callback( $f, $s ) {
		// A placement with the "Words Between Ads" option set to non-zero gets injected after others
		// because it reads existing ads.
		if ( ! empty( $f['options']['words_between_repeats'] ) xor ! empty( $s['options']['words_between_repeats'] ) ) {
			return ! empty( $f['options']['words_between_repeats'] ) ? 1 : -1;
		}

		$types = self::get_placement_types();

		$f_o = ( isset( $f['type'] ) && isset( $types[ $f['type'] ]['order'] ) ) ? $types[ $f['type'] ]['order'] : 100;
		$s_o = ( isset( $s['type'] ) && isset( $types[ $s['type'] ]['order'] ) ) ? $types[ $s['type'] ]['order'] : 100;

		if ( $f_o === $s_o ) {
			// Sort by index.
			if ( 'post_content' === $f['type'] && isset( $f['options']['index'] ) && isset( $s['options']['index'] )
				&& $f['options']['index'] !== $s['options']['index'] ) {
				return ( $f['options']['index'] < $s['options']['index'] ) ? -1 : 1;
			}

			// Sort by name.
			if ( isset( $f['name'] ) && isset( $s['name'] ) ) {
				return 0 > strcmp( $f['name'], $s['name'] ) ? -1 : 1;
			}
			return 0;
		}

		// Sort by order.
		return ( $f_o < $s_o ) ? -1 : 1;

	}


}


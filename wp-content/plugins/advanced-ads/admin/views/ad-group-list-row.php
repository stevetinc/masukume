<?php
/**
 * Template for a single row in the group list
 *
 * @package   Advanced_Ads_Admin
 * @author    Thomas Maier <support@wpadvancedads.com>
 * @license   GPL-2.0+
 * @link      https://wpadvancedads.com
 * @copyright since 2013 Thomas Maier, Advanced Ads GmbH
 *
 * @var Advanced_Ads_Group $group ad group object.
 */

?><tr class="advads-group-row">
	<td>
		<strong><a class="row-title" href="#"><?php echo esc_html( $group->name ); ?></a></strong>
		<?php
		// escaping done by the function.
		// phpcs:ignore
		echo $this->render_action_links( $group ); ?>
		<div class="hidden advads-usage">
			<label><?php esc_attr_e( 'shortcode', 'advanced-ads' ); ?>
				<code><input type="text" onclick="this.select();" style="width: 200px;" value='[the_ad_group id="<?php echo absint( $group->id ); ?>"]'/></code>
			</label><br/>
			<label><?php esc_attr_e( 'template', 'advanced-ads' ); ?>
				<code><input type="text" onclick="this.select();" value="the_ad_group(<?php echo absint( $group->id ); ?>);"/></code>
			</label>
		</div>
	</td>
	<td>
		<ul><?php $_type = isset( $this->types[ $group->type ]['title'] ) ? $this->types[ $group->type ]['title'] : 'default'; ?>
			<li><strong>
			<?php
			/*
			 * translators: %s is the name of a group type
			 */
			printf( esc_html__( 'Type: %s', 'advanced-ads' ), esc_html( $_type ) );
			?>
			</strong></li>
			<li>
			<?php
			/*
			 * translators: %s is the ID of an ad group
			 */
			printf( esc_attr__( 'ID: %s', 'advanced-ads' ), absint( $group->id ) );
			?>
			</li>
		</ul>
	</td>
	<td class="advads-ad-group-list-ads"><?php $this->render_ads_list( $group ); ?></td>
</tr>

<?php
$MAPI = Advanced_Ads_AdSense_MAPI::get_instance();
$options = $this->data->get_options();
$adsense_id = $this->data->get_adsense_id();
$mapi_options = Advanced_Ads_AdSense_MAPI::get_option();

$mapi_account_details = false;

$CID = Advanced_Ads_AdSense_MAPI::CID;

$use_user_app = Advanced_Ads_AdSense_MAPI::use_user_app();
if ( $use_user_app ) {
	$CID = ADVANCED_ADS_MAPI_CID;
}

$can_connect = true;

if ( $use_user_app && !( ( defined( 'ADVANCED_ADS_MAPI_CID' ) && '' != ADVANCED_ADS_MAPI_CID ) && ( defined( 'ADVANCED_ADS_MAPI_CIS' ) && '' != ADVANCED_ADS_MAPI_CIS ) ) ) {
	$can_connect = false;
}

$has_token = Advanced_Ads_AdSense_MAPI::has_token( $adsense_id );

if ( $has_token && isset( $mapi_options['accounts'][ $adsense_id ]['details'] ) ) {
    $mapi_account_details = $mapi_options['accounts'][ $adsense_id ]['details'];
}

$alerts = Advanced_Ads_AdSense_MAPI::get_stored_account_alerts( $adsense_id );
$alerts_heading = __( 'AdSense warnings', 'advanced-ads' );
$alerts_dismiss = __( 'dismiss', 'advanced-ads' );

$connection_error_messages = Advanced_Ads_AdSense_MAPI::get_connect_error_messages();

$alerts_advads_messages = Advanced_Ads_Adsense_MAPI::get_adsense_alert_messages();

?>
<div id="mapi-account-alerts" data-heading="<?php echo esc_attr( $alerts_heading ); ?>" data-dismiss="<?php echo esc_attr( $alerts_dismiss ); ?>">
    <?php if ( is_array( $alerts ) && isset( $alerts['items'] ) && is_array( $alerts['items'] ) && $alerts['items'] ) : ?>
    <p><?php echo esc_html( $alerts_heading ); ?></p>
    <ul>
        <?php foreach( $alerts['items'] as $alert_id => $alert ) : ?>
            <?php if ( isset( $alerts_advads_messages[ $alert['id'] ] ) ) : ?>
                <li><?php echo wp_kses( $alerts_advads_messages[ $alert['id'] ], array( 'a' => array( 'href' => true, 'target' => true, 'class' => true ) ) ); ?>&nbsp;<a href="#" class="mapi-dismiss-alert" data-id="<?php echo esc_attr( $alert_id ); ?>"><?php echo esc_html( $alerts_dismiss ); ?></a></li>
            <?php else : ?>
                <li><?php echo wp_kses( $alert['message'], array( 'a' => array( 'href' => true, 'target' => true, 'class' => true ) ) ); ?>&nbsp;<a href="#" class="mapi-dismiss-alert" data-id="<?php echo esc_attr( $alert_id ); ?>"><?php echo esc_html( $alerts_dismiss ); ?></a></li>
            <?php endif; ?>
       <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>
<div id="mapi-connect-errors">
<?php if ( !empty( $mapi_options['connect_error'] ) ) {
    
    echo '<p>';
    if ( isset( $connection_error_messages[ $mapi_options['connect_error']['reason'] ] ) ) {
        echo $connection_error_messages[ $mapi_options['connect_error']['reason'] ];
    } else {
        echo $connection_error_messages[ $mapi_options['connect_error']['message'] ];
    }
    echo '<i id="dissmiss-connect-error" class="dashicons dashicons-dismiss align';
    echo is_rtl()? 'left' : 'right';
    echo '" title=" ' . esc_attr( __( 'dismiss', 'advanced-ads' ) ) . '"></i>';
    echo '</p>';
    
}
?>
</div>
<div id="full-adsense-settings-div" <?php if ( empty( $adsense_id ) ) echo 'style="display:none"' ?>>
	<input type="text" <?php if ( $has_token ) echo 'readonly' ?> name="<?php echo GADSENSE_OPT_NAME; ?>[adsense-id]" style="margin-right:.8em" id="adsense-id" size="32" value="<?php echo $adsense_id; ?>" />
	<?php if ( !empty( $adsense_id ) && !$has_token ) : ?>
	<a id="connect-adsense" class="button-primary  <?php echo ! Advanced_Ads_Checks::php_version_minimum() ? 'disabled ' : ''; ?>preventDefault" <?php if ( ! $can_connect || ! Advanced_Ads_Checks::php_version_minimum() ) echo 'disabled'; ?>><?php esc_attr_e( 'Connect to AdSense', 'advanced-ads' ) ?></a>
	<?php endif; ?>
	<?php if ( $has_token ) : ?>
	<a id="revoke-token" class="button-secondary preventDefault"><?php esc_attr_e( 'Revoke API acccess', 'advanced-ads' ) ?></a>
	<div id="gadsense-freeze-all" style="position:fixed;top:0;bottom:0;right:0;left:0;background-color:rgba(255,255,255,.5);text-align:center;display:none;">
		<img alt="..." src="<?php echo ADVADS_BASE_URL . 'admin/assets/img/loader.gif'; ?>" style="margin-top:40vh" />
	</div>
	<?php endif; ?>
    <?php if ( $mapi_account_details ) : ?>
        <p class="description"><?php esc_html_e( 'Account holder name', 'advanced-ads' ); echo ': <strong>' . esc_html( $mapi_account_details['name'] ) . '</strong>'; ?></p>
    <?php else : ?>
		<?php if ( 0 !== strpos( $adsense_id, 'pub-' ) ) : ?>
			<p class="advads-error-message"><?php esc_html_e( 'The Publisher ID has an incorrect format. (must start with "pub-")', 'advanced-ads' ); ?></p>
		<?php else : ?>
			<p class="description"><?php _e( 'Your AdSense Publisher ID <em>(pub-xxxxxxxxxxxxxx)</em>', 'advanced-ads' ) ?></p>
		<?php endif; ?>
    <?php endif; ?>
</div>
<?php if ( empty( $adsense_id ) ) : ?>
<div id="auto-adsense-settings-div" <?php if ( !empty( $adsense_id ) ) echo 'style="display:none;"' ?>>
	<div class="widget-col">
		<h3><?php _e( 'Yes, I have an AdSense account', 'advanced-ads' ) ?></h3>
		<a id="connect-adsense" class="button-primary <?php echo ! Advanced_Ads_Checks::php_version_minimum() ? 'disabled ' : ''; ?>preventDefault" <?php echo ! Advanced_Ads_Checks::php_version_minimum() ? 'disabled' : ''; ?>><?php _e( 'Connect to AdSense', 'advanced-ads' ) ?></a>
		<a id="adsense-manual-config" class="button-secondary preventDefault"><?php _e( 'Configure everything manually', 'advanced-ads' ) ?></a>
	</div>
	<div class="widget-col">
		<h3><?php _e( "No, I still don't have an AdSense account", 'advanced-ads' ) ?></h3>
		<a class="button button-secondary" target="_blank" href="<?php echo Advanced_Ads_AdSense_Admin::ADSENSE_NEW_ACCOUNT_LINK; ?>"><?php _e( 'Get a free AdSense account', 'advanced-ads' ); ?></a>
	</div>
</div>
<style type="text/css">
	#adsense table h3 {
		margin-top: 0;
		margin-bottom: .2em;
	}
	#adsense table button {
		margin-bottom: .8em;
	}
	#adsense .form-table tr {
		display: none;
	}
	#adsense .form-table tr:first-of-type {
		display: table-row;
	}
	#auto-adsense-settings-div .widget-col {
		float: left;
		margin: 0px 5px 5px 0px;
	}
	#auto-adsense-settings-div:after {
		display: block;
		content: "";
		clear: left; 
	}
	#auto-adsense-settings-div .widget-col:first-child {
		margin-right: 20px;
		border-right: 1px solid #cccccc;
		padding: 0px 20px 0px 0px;
		position: relative;
	}
	#auto-adsense-settings-div .widget-col:first-child:after {
		position: absolute;
		content: "or";
		display: block;
		top: 20px;
		right: -10px;
		background: #ffffff;
		color: #cccccc;
		font-size: 20px; 
	}
	@media screen and (max-width: 1199px) {  
		#auto-adsense-settings-div .widget-col { float: none; margin-right: 0;  }
		#auto-adsense-settings-div .widget-col:first-child { margin: 0px 0px 20px 0px; padding: 0px 0px 20px 0px; border-bottom: 1px solid #cccccc; border-right: 0; }
		#auto-adsense-settings-div .widget-col:first-child:after { top: auto; right: auto; bottom: -10px; left: 20px; display: inline-block; padding: 0px 5px 0px 5px; }
	}
</style>
<?php
	echo "<br/><br/><br/><hr>";
	include ADVADS_BASE_PATH . 'modules/gadsense/admin/views/auto-ads-video.php';
	?><p>
	<a href="<?php echo esc_url( ADVADS_URL ); ?>place-adsense-ad-unit-manually/#utm_source=advanced-ads&utm_medium=link&utm_campaign=adsense-manually" style="text-decoration: none;" target="_blank"><span class="dashicons dashicons-welcome-learn-more"></span>&nbsp;<?php
		esc_attr_e( 'How to choose specific positions for AdSense ad units', 'advanced-ads' ); ?></a>
	</p><?php
else : ?>
<p><?php // translators: %1$s is the opening link tag to our manual; %2$s is the appropriate closing link tag; %3$s is the opening link tag to our help forum; %4$s is the appropriate closing link tag
printf(__( 'Problems with AdSense? Check out the %1$smanual%2$s or %3$sask here%4$s.', 'advanced-ads' ), 
	'<a href="' . ADVADS_URL . 'adsense-ads/#utm_source=advanced-ads&utm_medium=link&utm_campaign=adsense-manual-check" target="_blank">',
	'</a>',
	'<a href="https://wordpress.org/support/plugin/advanced-ads/#new-post" target="_blank">',
	'</a>'
	); ?></p>
<?php endif; ?>
<?php if ( ! Advanced_Ads_Checks::php_version_minimum() ) : ?>
<p class="advads-error-message"><?php _e( 'Can not connect AdSense account. PHP version is too low.', 'advanced-ads' ) ?></p>
<?php endif; ?>
<div id="mapi-alerts-overlay">
    <div style="position:relative;text-align:center;display:table;width:100%;height:100%;">
        <div style="display:table-cell;vertical-align:middle;">
            <img alt="loading" src="<?php echo esc_url( ADVADS_BASE_URL . 'admin/assets/img/loader.gif' ); ?>" />
        </div>
    </div>
</div>
<script type="text/javascript">
	if ( 'undefined' == typeof window.AdsenseMAPI ) {
		AdsenseMAPI = {};
	}
	AdsenseMAPI.alertsMsg = <?php echo json_encode( $alerts_advads_messages ) ?>;
</script>
<style type="text/css">
    #adsense {
        position: relative;
    }
    #mapi-alerts-overlay {
        position:absolute;
        top:0;
        right:0;
        bottom:0;
        left:0;
        background-color: rgb(255, 255, 255, .90);
        display: none;
    }
    #mapi-account-alerts, #mapi-connect-errors {
        background-color: #ffecd1;
        margin-bottom: .5em;
        color: #c52f00;
    }
    #mapi-account-alerts p {
        font-weight: bold;
        padding: .5em
    }
    #mapi-connect-errors p {
        padding: .5em;
    }
    #mapi-connect-errors p span {
        font-weight: bold;
    }
    #mapi-account-alerts ul {
        list-style-type: disc;
        margin: .5em;
        margin-left: 2em;
        padding: .5em;
    }
    #dissmiss-connect-error {
        cursor: pointer;
    }
    #gadsense-overlay {
        display:none;
        background-color:rgba(255,255,255,.5);
        position:absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        text-align:center;
    }
</style>

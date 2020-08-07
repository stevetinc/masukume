/**
 * Advanced Ads.
 *
 * @author    Thomas Maier <support@wpadvancedads.com>
 * @license   GPL-2.0+
 * @link      https://wpadvancedads.com
 * @copyright 2013-2018 Thomas Maier, Advanced Ads GmbH
 */
;(function($){

    function printAlerts( alerts ) {
        var $div = $( '#mapi-account-alerts' );
        $div.empty();
        if ( alerts.length ) {
            $div.append( $( '<p />' ).text( $div.attr( 'data-heading' ) ) );
            var $ul = $( '<ul />' );
            for ( var id in alerts.alerts ) {
                var msg = alerts.alerts[id].message;
                if ( 'undefined' != typeof AdsenseMAPI.alertsMsg[alerts.alerts[id]['id']] ) {
                    msg = AdsenseMAPI.alertsMsg[alerts.alerts[id]['id']];
                }
                $ul.append( $( '<li />' ).html(  msg + ' ' +
                '<a href="#" class="mapi-dismiss-alert" data-id="' + id + '">' + $div.attr( 'data-dismiss' ) + '</a>' ) );
            }
            $div.append( $ul );
        }
    }

	$( document ).on( 'click', '.preventDefault', function( ev ) {
		ev.preventDefault();
	} );

    $( document ).on( 'click', '#dissmiss-connect-error', function() {
        $( '#mapi-connect-errors' ).empty(); 
        $.ajax({
			url: ajaxurl,
			type: 'get',
			data: {
				action: 'advads-mapi-dismiss-connect-error',
				nonce: AdsenseMAPI.nonce,
			}
		});
    } );
    
	$( document ).on( 'keypress', '#adsense input[type="text"]', function( ev ) {
		if ( $( this ).hasClass( 'preventDefault' ) ) {
			ev.preventDefault();
			return;
		}
		if ( ev.which == 13 || ev.keyCode == 13 ) {
			$( '#adsense .advads-settings-tab-main-form #submit' ).trigger( 'click' );
		}
	} );

	$( document ).on( 'click', '#revoke-token', function(){

		$( '#gadsense-freeze-all' ).css( 'display', 'block' );
		var ID = $( '#adsense-id' ).val();
		$.ajax({
			url: ajaxurl,
			type: 'post',
			data: {
				action: 'advads-mapi-revoke-token',
				adsenseId: ID,
				nonce: AdsenseMAPI.nonce,
			},
			success:function(response, status, XHR){
				window.location.reload();
			},
			error:function(request, status, error){
				$( '#gadsense-freeze-all' ).css( 'display', 'none' );
			},
		});

	} );

	$( document ).on( 'click', '#adsense-manual-config', function(){
		$( '#adsense .form-table tr' ).css( 'display', 'table-row' );
		$( '#adsense #auto-adsense-settings-div' ).css( 'display', 'none' );
		$( '#adsense #full-adsense-settings-div' ).css( 'display', 'block' );
		$( '#adsense-id' ).after( $( '#connect-adsense' ) );
		$( '#adsense #submit' ).parent().show();
	} );

    // Open the code confirmation modal.
	$( document ).on( 'click', '#connect-adsense', function(){
		if ( $( this ).hasClass( 'disabled' ) ) return;
        if ( 'undefined' != typeof window.advadsMapiConnect ) {
            window.advadsMapiConnect();
        }
	} );

    $( document ).on( 'click', '.mapi-dismiss-alert', function( ev ) {
        ev.preventDefault();

        var pubId = $( '#adsense-id' ).val();
        var alertId = $( this ).attr( 'data-id' );

        $( '#gadsense-modal' ).css( 'display', 'block' );
        $( '#gadsense-modal-outer' ).css( 'display', 'none' );

        $.ajax({
            url: ajaxurl,
            type: 'post',
            data: {
                action: 'advads-mapi-dismiss-alert',
                account: pubId,
                id: alertId,
                nonce: AdsenseMAPI.nonce,
            },
            success:function(response, status, XHR){
                if ( 'undefined' != typeof response.alerts ) {
                    printAlerts( response );
                }
                $( '#gadsense-modal' ).css( 'display', 'none' );
                $( '#gadsense-modal-outer' ).css( 'display', 'block' );
            },
            error:function(request, status, error){
                $( '#gadsense-modal' ).css( 'display', 'none' );
                $( '#gadsense-modal-outer' ).css( 'display', 'block' );
            },
        });

    } );

	$( document ).on( 'click', '.mapi-create-ads-txt', function( ev ) {
		ev.preventDefault();

		var top = jQuery( '#advads-ads-txt-wrapper' ).offset().top;
		window.scrollTo( 0, top );
	} );

    $( document ).on( 'advadsMapiRefreshAlerts', function ( ev, response ) {
        if ( 'undefined' != typeof response.status && response.status && response.alerts ) {
            printAlerts( response );
        }
    } );
    
	$( function(){
		if ( '' === $( '#adsense-id' ).val().trim() && $('#adsense-manual-config').is(':visible') ) {
			$( '#adsense #submit' ).parent().css( 'display', 'none' );
		}
	} );

})(window.jQuery);

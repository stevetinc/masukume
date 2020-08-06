jQuery(function($) {
    var demo_confirm            = MTDI_JSObject.demo_confirm, //Are you sure to import demo content ?
        AjaxUrl                 = MTDI_JSObject.ajaxurl,
        _wpnonce                = MTDI_JSObject._wpnonce,
        plugin_installing       = MTDI_JSObject.plugin_installing, //Installing
        importing_demo          = MTDI_JSObject.importing_demo, //Importing
        plugin_activated        = MTDI_JSObject.plugin_activated, //Activated
        activating_installing   = MTDI_JSObject.activating_installing, //Installing & Activating
        plugin_activating       = MTDI_JSObject.plugin_activating; //Activating
    var isTimeout, isLoaded;
    $('.mtdi-demo-import').on('click', function(e) {
        e.preventDefault();
        var el          = $(this);
        $import_true    = confirm(demo_confirm);
        if ($import_true == false){
            return;
        }
        el.addClass('updating-message');
        var selected_demo = el.data('slug');
        // Get import data
        $.ajax({
            method: "POST",
            url: AjaxUrl,
            data: ({
                'action': 'mtdi_ajax_onclick_import_button',
                'plugin_slug': selected_demo,
                '_wpnonce': _wpnonce,
            }),
            success: function(response) {
                $('#mtdi-demo-popup-wrap').addClass('mtdi-popup-show');
                $('#mtdi-demo-popup-wrap').html(response);
                $(this).removeClass('updating-message');
                function success() {
                    if (isTimeout) {
                        return;
                    }
                    $('#loadingMessage').hide();
                    isLoaded = true;
                };
                setTimeout(function() {
                    if (isLoaded) {
                        return;
                    }
                    $('#loadingMessage').hide();
                    $('iframe').css('display', 'inline-block');
                    $('iframe').show();
                    isTimeout = true;
                }, 2000);
            }
        });
    });
    $('body').on('click', '.mtdi-demo-popup-close', function(e) {
        e.preventDefault();
        $('#mtdi-demo-popup-wrap').removeClass('mtdi-popup-show');
        $('#mtdi-demo-popup-wrap').html('');
        $('.mtdi-demo-import').removeClass('updating-message');
    });
    $(document).on('click', '.mtdi-install-setup', function(e) {
        var _this           = $(this);
        var $target         = $(e.target);
        var selected_slug   = $(this).data('slug');
        var pluginLists     = $('#hidden_plugin_lists-' + selected_slug).val();
        e.preventDefault();
        if ($target.hasClass('disabled') || $target.hasClass('updating-message')) {
            return;
        }
        processImport($target, pluginLists, _this);
        wp.updates.maybeRequestFilesystemCredentials(e);
    });
    $(document).on('click', '.mtdi-demo-import-step', function(e) {
        var _this           = $(this);
        var $target         = $(e.target);
        var selected_slug   = $(this).data('slug');
        var execution_time = $(this).data( 'execution' );
        processDemoImport(selected_slug, execution_time);
    });
    var json = new Array();
    processDemoImport = function(selected_slug, execution_time) {
        $.ajax({
            method: "POST",
            url: AjaxUrl,
            dataType: "json",
            data: ({
                'action': 'mtdi_import_demo',
                'plugin_slug': selected_slug,
                'execution_time': execution_time,
                '_wpnonce': _wpnonce,
            }),
            beforeSend: function() {
                $('.mtdi-demo-import-step').addClass('updating-message').text(importing_demo);
            },
            success: function(response) {
                if (response.success) {
                    $('.mtdi-demo-import-step').removeClass('updating-message');
                    $('.mtdi-demo-import-step').html('');
                    $('.mtdi-demo-import-step').addClass('imported-success');
                    $('.imported-success').removeClass('demo-import');
                    $('.imported-success').attr('href', response.data.previewUrl);
                    $('.imported-success').attr('target', '_blank');
                    $('.imported-success').html('Visit Site');
                    $( '.mtdi-customize-button' ).show();
                    $('.imported-success').removeClass('mtdi-demo-import-step');
                } else {
                    $('.mtdi-demo-import-step').removeClass('updating-message');
                    $('.demo-import-actions').append('<p class="notice notice-error is-dismissible">' + response.errorMessage + '</p>');
                    $('.mtdi-demo-import-step').addClass('error-imported');
                }
            }
        });
    };
    processImport = function($target, pluginLists, _this) {
        $(document).trigger('wp-plugin-bulk-install', pluginLists);
        // Find all the plugins which are required.
        pluginLists     = JSON.parse(pluginLists);
        var count       = Object.keys(pluginLists).length;
        var i           = 1;
        var numItems    = $('.button.activated').length;
        installPlugin( pluginLists );
    };
    installPlugin = function( pluginLists ) {
        var uninstalledContainer = $( '.mtdi-install-plugin' );
        var unactivatedContainer = $( '.mtdi-activate-now' );
        if( uninstalledContainer.length || unactivatedContainer.length ) {
            if( uninstalledContainer.length ) {
                var SinglePluginSlug =  uninstalledContainer.data( 'slug' );
                var SinglePluginName = uninstalledContainer.data( 'name' );
                var SinglePluginInit = uninstalledContainer.data( 'init' );
                var selfClass = 'mtdi-install-plugin';
            } else {
                var SinglePluginSlug =  unactivatedContainer.data( 'slug' );
                var SinglePluginName = unactivatedContainer.data( 'name' );
                var SinglePluginInit = unactivatedContainer.data( 'init' );
                var selfClass = 'mtdi-activate-now';
            }
            $.ajax({
                method: "POST",
                url: AjaxUrl,
                data: ({
                    'action': 'mtdi_requried_plugin_install',
                    'plugin_slug': SinglePluginSlug, //slug
                    'plugin_init': SinglePluginInit,
                    'plugin_name': SinglePluginName,
                    'pluginList': pluginLists,
                    '_wpnonce': _wpnonce,
                }),
                beforeSend: function() {
                    console.log( 'Installing ' + SinglePluginName );
                    var button_text = $('.mtdi-' + SinglePluginSlug).hasClass('mtdi-activate-now') ? plugin_activating : plugin_installing;
                    $('.mtdi-' + SinglePluginSlug).addClass('updating-message').text(button_text);
                    $('.mtdi-install-setup').addClass('updating-message').text(activating_installing);
                },
                success: function(response) {
                    if (response.success) {
                        console.log( 'Installed ' + SinglePluginName );
                        $('.mtdi-' + SinglePluginSlug).removeClass( selfClass ).removeClass('updating-message').addClass('disabled updated-message activated')
                            .text(plugin_activated);
                    } else {
                        $('.mtdi-' + SinglePluginSlug).removeClass( selfClass ).removeClass('updating-message').addClass('disabled updated-message activated')
                            .text(plugin_activated);
                        $('.demo-import-actions').append('<p class="notice notice-error is-dismissible">' + response.errorMessage + '</p>');
                    }
                },
                complete: function( xhr,status ) {
                    installPlugin( pluginLists );
                }
            });
        } else {
            $('.mtdi-install-setup').hide().removeClass('updating-message');
            $('.mtdi-demo-import-step').show();
            return;
        }
    }

    $( 'body' ).on( 'click', '.mtdi-responsive-view button', function() {
        $( this ).siblings().removeClass( 'active' );
        $( this ).addClass( 'active' );
        var device = $( this ).data( "device" );
        if( device == "tablet" ) {
            var width = '720px';
            var height = '1080px';
        } else if ( device == "mobile" ) {
            var width = '320px';
            var height = '480px';
        } else {
            var width = '100%';
            var height = '100%';
        }
        $( '.theme-screenshots1 iframe' ).animate({width: width, height: height}, 500 );
    })
});
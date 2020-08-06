jQuery(document).ready(function($) {

    "use strict";

    /**
     * Ticker script
     */
    $("#newsTicker").lightSlider({
        item: 1,
        vertical: true,
        loop: true,
        verticalHeight: 35,
        pager: false,
        enableTouch: false,
        enableDrag: false,
        auto: true,
        controls: true,
        speed: 2000,
        pause: 6000,
        prevHtml: '<i class="fa fa-arrow-left"></i>',
        nextHtml: '<i class="fa fa-arrow-right"></i>',
        onSliderLoad: function() {
            $('#np-newsTicker').removeClass('cS-hidden');
        }
    });

    /**
     * Slider script
     */
    $('.slider-posts').each(function() {
        $(".np-main-slider").lightSlider({
            item: 1,
            auto: true,
            pager: false,
            loop: true,
            slideMargin: 0,
            speed: 2000,
            pause: 6000,
            enableTouch: false,
            enableDrag: false,
            prevHtml: '<i class="fa fa-angle-left"></i>',
            nextHtml: '<i class="fa fa-angle-right"></i>',
            onSliderLoad: function() {
                $('.np-main-slider').removeClass('cS-hidden');
            }
        });
    });

    /**
     * Block carousel layout
     */
    $('.carousel-posts').each(function() {
        var Id = $(this).parent().attr('id');
        var NewId = Id;
        var crsItem = $(this).data('items');

        NewId = $('#' + Id + " #blockCarousel").lightSlider({
            auto: true,
            loop: true,
            pauseOnHover: true,
            pager: false,
            speed: 2000,
            pause: 6000,
            controls: false,
            prevHtml: '<i class="fa fa-angle-left"></i>',
            nextHtml: '<i class="fa fa-angle-right"></i>',
            item: 4,
            onSliderLoad: function() {
                $('#' + Id + " #blockCarousel").removeClass('cS-hidden');
            },
            responsive: [{
                    breakpoint: 840,
                    settings: {
                        item: 2,
                        slideMove: 1,
                        slideMargin: 6,
                    }
                },
                {
                    breakpoint: 480,
                    settings: {
                        item: 1,
                        slideMove: 1,
                    }
                }
            ]
        });

        $('#' + Id + ' .np-navPrev').click(function() {
            NewId.goToPrevSlide();
        });
        $('#' + Id + ' .np-navNext').click(function() {
            NewId.goToNextSlide();
        });
    });

    /**
     * Default widget tabbed
     */
    $("#np-tabbed-widget").tabs();


    //Search toggle
    $('.np-header-search-wrapper .search-main').click(function() {
        $('.search-form-main').toggleClass('active-search');
        $('.search-form-main .search-field').focus();
    });

    //responsive menu toggle
    $('.np-header-menu-wrapper .menu-toggle').click(function(event) {
        $('.np-header-menu-wrapper #site-navigation').slideToggle('slow');
    });

    //responsive sub menu toggle
    $('<a class="sub-toggle" href="javascript:void(0);"><i class="fa fa-angle-right"></i></a>').insertAfter('#site-navigation .menu-item-has-children>a, #site-navigation .page_item_has_children>a');

    $('#site-navigation .sub-toggle').click(function() {
        $(this).parent('.menu-item-has-children').children('ul.sub-menu').first().slideToggle('1000');
        $(this).parent('.page_item_has_children').children('ul.children').first().slideToggle('1000');
        $(this).children('.fa-angle-right').first().toggleClass('fa-angle-down');
    });

    // Scroll To Top
    $(window).scroll(function() {
        if ($(this).scrollTop() > 1000) {
            $('#np-scrollup').fadeIn('slow');
        } else {
            $('#np-scrollup').fadeOut('slow');
        }
    });

    $('#np-scrollup').click(function() {
        $("html, body").animate({
            scrollTop: 0
        }, 600);
        return false;
    });
});
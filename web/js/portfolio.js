/**
 * Created by victor on 02/11/16.
 */
jQuery(function($) {
    'use strict',


// portfolio filter
        $(window).load(function () {
            'use strict',
                $portfolio_selectors = $('.portfolio-filter >li>a');
            if ($portfolio_selectors != 'undefined') {
                $portfolio = $('.portfolio-items');
                // $portfolio.isotope({
                //     itemSelector : '.col-sm-3',
                //     layoutMode : 'fitRows'
                // });

                $portfolio_selectors.on('click', function () {
                    $portfolio_selectors.removeClass('active');
                    $(this).addClass('active');
                    var selector = $(this).attr('data-filter');
                    $portfolio.isotope({filter: selector});
                    return false;
                });
            }
        });

});
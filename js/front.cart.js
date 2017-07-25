/* global pp_latam_cart */
;(function( $, window, document ) {
	'use strict';
	var is_modal =  wc_ppexpress_cart_context.show_modal * 1;
	var $wc_ppexpress_mx = {
		init: function() {
			window.paypalCheckoutReady = function() {
				paypal.checkout.setup(
					wc_ppexpress_cart_context.payer_id,
					{
						environment: wc_ppexpress_cart_context.environment,
						locale: wc_ppexpress_cart_context.locale
					}
				);
			}
		}
	}

	function open_modal_cart() {
		if ( is_modal ) {
			paypal.checkout.initXO();
		}
		var data = {
			'nonce':      wc_ppexpress_cart_context.token_cart,
		};
		$.ajax( {
			type:    'POST',
			data:    data,
			url:     wc_ppexpress_cart_context.ppexpress_update_cart_url,
			success: function( response ) {
				if ( response.token ) {
					paypal.checkout.startFlow( response.token );
				} else if ( response.url ) {
					document.location.href = response.url;
				} else {
					paypal.checkout.closeFlow();
					alert( wc_ppexpress_cart_context.pp_error );
					location.reload(true);
				}
			}
		} );
	}
	function check_click() {
		$('#btn_ppexpress_mx_widget,#btn_ppexpress_mx_cart').each(function(){
			if ( ! $(this).hasClass('addedEventPP') ) {
				$(this).addClass('addedEventPP');
				$(this).click( open_modal_cart );
			}
		});
	}
	setInterval(check_click, 2000);
	$( document.body ).bind('wc_fragment_refresh', check_click);
	$( document.body ).bind('wc_fragments_loaded', check_click);
	$('#btn_ppexpress_mx_order').click(function() {
		paypal.checkout.initXO();
		paypal.checkout.startFlow($(this).attr('data-token'));
	});
	if ( is_modal ) {
		$wc_ppexpress_mx.init();
		/* if ( $('#btn_ppexpress_mx_order').length > 0 ) {
			paypal.checkout.initXO();
			paypal.checkout.startFlow($('#btn_ppexpress_mx_order').attr('data-token'));
		}*/
	}
})( jQuery, window, document );
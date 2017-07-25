/* global pp_latam_checkout */
;(function( $, window, document ) {
	'use strict';
	if ( 1 != wc_ppexpress_cart_context.show_modal * 1 ) {
		return;
	}
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
	var pp_opened = false;
	$('form.checkout').submit(function() {
		if ( wc_ppexpress_cart_context.flow_method == 'modal_on_checkout' ) {
			if ( ( 'ppexpress_installment_mx' == $('input[name="payment_method"]').val() || 'ppexpress_mx' == $('input[name="payment_method"]').val() ) && $('#not-popup-ppexpress-mx').length < 1) {
				paypal.checkout.initXO();
				pp_opened = true;
			}
		}
		return true;
	});
	$( document.body ).bind('checkout_error', function() {
		if ( wc_ppexpress_cart_context.flow_method == 'modal_on_checkout' ) {
			if ( ( 'ppexpress_installment_mx' == $('input[name="payment_method"]').val() || 'ppexpress_mx' == $('input[name="payment_method"]').val() ) && $('#not-popup-ppexpress-mx').length < 1 ) {
				var pp_token = $('#pp_latam_redirect').attr('data-token');
				if ( pp_token ) {
					paypal.checkout.initXO();
					pp_opened = true;
					paypal.checkout.startFlow(pp_token);
				} else if ( pp_opened ) {
					paypal.checkout.closeFlow();
					pp_opened = false;
				}
			}
		}
	});
	if ( wc_ppexpress_cart_context.flow_method == 'modal_on_checkout' ) {
		$wc_ppexpress_mx.init();
	}
	if ( $('#btn_ppexpress_mx_order').length > 0 ) {
		$wc_ppexpress_mx.init();
		$('#btn_ppexpress_mx_order').click(function() {
			paypal.checkout.initXO();
			paypal.checkout.startFlow($(this).attr('data-token'));
		});
		setTimeout(function(){
			paypal.checkout.initXO();
			paypal.checkout.startFlow($('#btn_ppexpress_mx_order').attr('data-token'));
		}, 1500);
	}
})( jQuery, window, document );
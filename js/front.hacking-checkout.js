/* global pp_latam_checkout */
;(function( $, window, document ) {
	'use strict';
	if ( 1 != wc_ppexpress_cart_context.show_modal * 1 ) {
		return;
	}
	$('input[name="payment_method"]').closest('form').append('<a href="" id="btn_ppexpress_latam_checkout" style="display:none"></a>');
	var $wc_ppexpress_latam = {
		init: function() {
			window.paypalCheckoutReady = function() {
				paypal.checkout.setup(
					wc_ppexpress_cart_context.payer_id,
					{
						environment: wc_ppexpress_cart_context.environment,
						button: ['btn_ppexpress_latam_order', 'btn_ppexpress_latam_checkout'],
						locale: wc_ppexpress_cart_context.locale,
						container: ['btn_ppexpress_latam_order', 'btn_ppexpress_latam_checkout']
					}
				);
			}
		}
	}
	var pp_with_error = false;
	var submitted = false;
	$('input[name="payment_method"]').closest('form').submit(function() {
		submitted = true;
		return true;
	});
	$('input[type="submit"],button[type="submit"]').click(function() {
		if ( 'ppexpress_latam' == $('input[name="payment_method"]').val() ) {
			submitted = true;
		}
	});
	$( document.body ).bind('update_checkout', function() {
		if ( submitted && 'ppexpress_latam' == $('input[name="payment_method"]').val() ) {
			setTimeout(function(){
				var pp_token = $('#pp_latam_redirect').attr('data-token');
				if ( pp_token ) {
					paypal.checkout.initXO();
					paypal.checkout.startFlow(pp_token);
				}
			}, 500);
		}
	});
	if ( wc_ppexpress_cart_context.show_modal * 1 ) {
		$wc_ppexpress_latam.init();
	}
})( jQuery, window, document );
/* global pp_mx_cart */
;(function( $, window, document ) {
	'use strict';
	var is_modal = parseInt( wc_ppexpress_cart_context.show_modal );
	var PAYPAL_REDIRECT_URL = 'https://www.paypal.com/checkoutnow?token=';
	function check_click() {
		$( '.btn_ppexpress_mx_widget,#btn_ppexpress_mx_cart' ).each(function(){
			if ( ! $( this ).hasClass( 'addedEventPP' ) ) {
				$( this ).addClass( 'addedEventPP' );
				var is_widget = $( this ).hasClass( 'btn_ppexpress_mx_widget' );
				if ( is_widget ) {
					var id = 'pp_widget_' + parseInt( Math.random() * 1000 );
					$( this ).attr( 'id', id );
				}
				var data = {
					env: wc_ppexpress_cart_context.environment,
					style: is_widget ? wc_ppexpress_cart_context.style_widget : wc_ppexpress_cart_context.style_products,
					payment: function() {
						// Make a call to your server to set up the payment
						var result = paypal.request.post( wc_ppexpress_cart_context.ppexpress_update_cart_url )
							.then(function(res) {
								if ( is_modal ) {
									return res.paymentID;
								}
								document.location.href = PAYPAL_REDIRECT_URL + res.paymentID;
								return false;
							});
						return result;
					},
					onAuthorize: function(data, actions) {
						return actions.redirect();
					},
					onCancel: function(data, actions) {
						return actions.redirect();
					}
				};
                var is_credit = data.style.credit;
                var is_branding = data.style.branding;
                if (wc_ppexpress_cart_context.locale != 'auto') {
                    data.locale = wc_ppexpress_cart_context.locale;
                }
                if (is_branding) {
                    if (data.style.layout != 'vertical') {
                        data.style.fundingicons = true;
                    }
                    data.style.branding = true;
                } else {
                    delete data.style.branding;
                }
                if (is_credit) {
                    data.funding = {
                        allowed: [paypal.FUNDING.CARD, paypal.FUNDING.CREDIT],
                        disallowed: []
                    };
                } else {
                    data.funding = {
                        allowed: [paypal.FUNDING.CARD],
                        disallowed: [paypal.FUNDING.CREDIT]
                    };
                }
                paypal.Button.render( data , $( this ).attr( 'id' ) );
			}
		});
	}
	setInterval( check_click, 2000 );
	$( document.body ).bind( 'wc_fragment_refresh', check_click );
	$( document.body ).bind( 'wc_fragments_loaded', check_click );
})( jQuery, window, document );

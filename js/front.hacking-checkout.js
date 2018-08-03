/* global pp_mx_checkout */
;(function( $, window, document ) {
	'use strict';
	if( wc_ppexpress_cart_context.btn_banner && wc_ppexpress_cart_context.btn_banner != '') {
		function check_pp_img() {
			$('.payment_box.payment_method_ppexpress_mx *').remove();
			$('.payment_box.payment_method_ppexpress_mx')
				.html('<img src="'+wc_ppexpress_cart_context.btn_banner+'" style="width:100%;max-height:86px;max-width:422px;" />')
				.css('height', '86px')
				.css('padding', '0')
				.css('margin', '0');
		}
		$( document.body ).bind( 'updated_checkout', check_pp_img );
		check_pp_img();
	}
	var is_express = parseInt( wc_ppexpress_cart_context.is_express );
	if ( is_express || 'modal_on_checkout' != wc_ppexpress_cart_context.flow_method ) {
		$( '#btn_ppexpress_mx_order' ).each(function() {
			if ( ! $( this ).hasClass( 'addedEventPP' ) ) {
				$( this ).addClass( 'addedEventPP' );
				var data = {
					env: wc_ppexpress_cart_context.environment,
					style: wc_ppexpress_cart_context.style_checkout,
					payment: function() {
						return $( '#btn_ppexpress_mx_order' ).attr( 'data-token' );
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
                paypal.Button.render( data, $( this ).attr( 'id' ) );
			}
		});
		return;
	}
	var defer_ok = false;
	var defer_er = false;
	function check_click() {
		$( 'input[name="payment_method"]' ).change(function(){
			if ( 'ppexpress_installment_mx' == $( 'input[name="payment_method"]:checked' ).val() || 'ppexpress_mx' == $( 'input[name="payment_method"]:checked' ).val() ) {
				$( '.pp_place_order_original' ).hide( 0 );
				$( '.pp_place_order_replace' ).show( 0 );
				return;
			}
			$( '.pp_place_order_original' ).show( 0 );
			$( '.pp_place_order_replace' ).hide( 0 );
		});
		$( 'form.checkout .place_order, form.checkout input[type=submit], form.checkout button[type=submit]' ).each(function(){
			if ( ! $( this ).hasClass( 'addedEventPP' ) ) {
				$( this ).addClass( 'addedEventPP' );
				$( this ).addClass( 'pp_place_order_original' );
				$( this ).parent().append( '<div id="btn_ppexpress_mx_checkout" class="pp_place_order_replace" style="width: 100%;text-align: center;"></div>' );
				$( 'input[name="payment_method"]' ).trigger( 'change' );
				var data = {
					env: wc_ppexpress_cart_context.environment,
					style: wc_ppexpress_cart_context.style_checkout,
					payment: function(ok, err) {
						var defer = new paypal.Promise(function(resolve, reject) {
							defer_ok = resolve;
							defer_er = reject;
						});
						$( 'form.checkout .place_order, form.checkout input[type=submit], form.checkout button[type=submit]' ).trigger( 'click' );
						return defer.then(function(data) {
							return data;
						});
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
                paypal.Button.render( data, 'btn_ppexpress_mx_checkout' );
			}
		});
	}
	$( document.body ).bind( 'updated_checkout', check_click );
	$( document.body ).bind('checkout_error', function() {
		if ( ( 'ppexpress_installment_mx' == $( 'input[name="payment_method"]:checked' ).val() || 'ppexpress_mx' == $( 'input[name="payment_method"]:checked' ).val() ) && $( '#not-popup-ppexpress-mx' ).length < 1 ) {
			var pp_token = $( '#pp_latam_redirect' ).attr( 'data-token' );
			if ( pp_token ) {
				defer_ok( pp_token );
			} else {
				defer_er( );
			}
		}
	});
	check_click();
	$( 'input[name="payment_method"]' ).trigger( 'change' );
})( jQuery, window, document );

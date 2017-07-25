/* global pp_latam_product */
;(function( $, window, document ) {
	'use strict';
	var is_modal =  wc_ppexpress_product_context.show_modal * 1;
	var $wc_ppexpress_mx = {
		init: function() {
			window.paypalCheckoutReady = function() {
				paypal.checkout.setup(
					wc_ppexpress_product_context.payer_id,
					{
						environment: wc_ppexpress_product_context.environment,
						locale: wc_ppexpress_product_context.locale
					}
				);
			}
		}
	}
	var get_attributes = function() {
		var select = $( '.variations_form' ).find( '.variations select' ),
			data   = {},
			count  = 0,
			chosen = 0;

		select.each( function() {
			var attribute_name = $( this ).data( 'attribute_name' ) || $( this ).attr( 'name' );
			var value	  = $( this ).val() || '';

			if ( value.length > 0 ) {
				chosen++;
			}

			count++;
			data[ attribute_name ] = value;
		} );

		return {
			'count'      : count,
			'chosenCount': chosen,
			'data'       : data
		};
	};
	$( '#btn_ppexpress_mx_product' ).click( function( event ) {
		var atts = get_attributes();
		if ( atts.count !=  atts.chosenCount ) {
			alert( wc_ppexpress_product_context.att_empty );
			return;
		}
		if ( is_modal ) {
			paypal.checkout.initXO();
		}
		var data = {
			'nonce':      wc_ppexpress_product_context.token_product,
			'qty':        $( '.quantity .qty' ).val(),
			'attributes': $( '.variations_form' ).length ? atts.data : []
		};
		$.ajax( {
			type:    'POST',
			data:    data,
			url:     wc_ppexpress_product_context.ppexpress_generate_cart_url,
			success: function( response ) {
				if ( response.token ) {
					paypal.checkout.startFlow( response.token );
				} else if ( response.url ) {
					document.location.href = response.url;
				} else {
					paypal.checkout.closeFlow();
					alert( wc_ppexpress_product_context.pp_error );
					location.reload(true);
				}
			}
		} );
	} );

	function open_modal_cart() {
		if ( is_modal ) {
			paypal.checkout.initXO();
		}
		var data = {
			'nonce':      wc_ppexpress_product_context.token_cart,
		};
		$.ajax( {
			type:    'POST',
			data:    data,
			url:     wc_ppexpress_product_context.ppexpress_update_cart_url,
			success: function( response ) {
				if ( response.token ) {
					paypal.checkout.startFlow( response.token );
				} else if ( response.url ) {
					document.location.href = response.url;
				} else {
					paypal.checkout.closeFlow();
					alert( wc_ppexpress_product_context.pp_error );
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
	if ( is_modal ) {
		$wc_ppexpress_mx.init();
	}
})( jQuery, window, document );
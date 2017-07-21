/* global pp_latam_cart */
;(function( $, window, document ) {
	'use strict';
	$( document ).ready(function() {
		if ( $('#btn_ppexpress_latam_order, #btn_ppexpress_latam_cart, #btn_ppexpress_latam_widget').length < 1 )
			return;

		var $wc_ppexpress_latam = {
			init: function() {
				window.paypalCheckoutReady = function() {
					paypal.checkout.setup(
						wc_ppexpress_cart_context.payer_id,
						{
							environment: wc_ppexpress_cart_context.environment,
							button: ['btn_ppexpress_latam_order', 'btn_ppexpress_latam_cart', 'btn_ppexpress_latam_widget'],
							locale: wc_ppexpress_cart_context.locale,
							container: ['btn_ppexpress_latam_order', 'btn_ppexpress_latam_cart', 'btn_ppexpress_latam_widget']
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

		var costs_updated = false;
		$( '#btn_ppexpress_latam_cart' ).click( function( event ) {
			if ( costs_updated ) {
				costs_updated = false;
				return;
			}
			event.stopPropagation();
			var data = {
				'nonce':      wc_ppexpress_cart_context.token_cart
			};
			$.ajax( {
				type:    'POST',
				data:    data,
				url:     wc_ppexpress_cart_context.ppexpress_update_cart_url,
				success: function( response ) {
					costs_updated = true;
					if ( wc_ppexpress_cart_context.show_modal * 1 ) {
						$( '#btn_ppexpress_latam_cart' ).click();
					} else {
						document.location.href = $( '#btn_ppexpress_latam_cart' ).attr('href');
					}
				}
			} );
		} );
		$( '#btn_ppexpress_latam_widget' ).click( function( event ) {
			if ( costs_updated ) {
				costs_updated = false;

				return;
			}

			event.preventDefault();
			event.stopPropagation();

			var data = {
				'nonce':      wc_ppexpress_cart_context.token_cart,
			};

			$.ajax( {
				type:    'POST',
				data:    data,
				url:     wc_ppexpress_cart_context.ppexpress_update_cart_url,
				success: function( response ) {
					costs_updated = true;
					if ( wc_ppexpress_cart_context.show_modal * 1 ) {
						$( '#btn_ppexpress_latam_widget' ).click();
					} else {
						document.location.href = $( '#btn_ppexpress_latam_widget' ).attr('href');
					}
				}
			} );
		} );
		if ( wc_ppexpress_cart_context.show_modal * 1 ) {
			$wc_ppexpress_latam.init();
		}
	});
})( jQuery, window, document );
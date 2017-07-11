<?php

use PayPal\CoreComponentTypes\BasicAmountType;
use PayPal\EBLBaseComponents\PaymentDetailsItemType;
use PayPal\EBLBaseComponents\PaymentDetailsType;
use PayPal\EBLBaseComponents\SetExpressCheckoutRequestDetailsType;
use PayPal\PayPalAPI\SetExpressCheckoutReq;
use PayPal\PayPalAPI\SetExpressCheckoutRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * PayPal API Interface Hander from Cart of WooCommerce
 */
class WC_PayPal_Cart_Handler_Latam {
	
	/**
	 * Initialize the plugin.
	 */
	function __construct() {
		$this->settings = (array) get_option( 'woocommerce_ppexpress_latam_settings', array() );
	}
	/**
	 * Get options.
	 */
	private function get_option( $key ) {
		return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : false ;
	}
	/**
	 * Start checkout.
	 */
	public function start_checkout( array $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'start_from'               => 'cart',
				'order_id'                 => '',
				'create_billing_agreement' => false,
			)
		);
		$cart_url = WC_Paypal_Express_MX::woocommerce_instance()->cart->get_cart_url();
		$return_url = $this->_get_return_url( $args );
		$cancel_url = $this->_get_cancel_url();
		try {
			switch ( $args['start_from'] ) {
				case 'checkout':
					$details = $this->_get_details_from_order( $args['order_id'] );
					break;
				case 'cart':
					$details = $this->_get_details_from_cart();
					break;
			}
			if ( 'checkout' === $args['start_from'] ) {
				//$params['ADDROVERRIDE'] = '1';
			}

			if ( in_array( $this->get_option( 'landing_page' ), array( 'Billing', 'Login' ) ) ) {
				//$params['LANDINGPAGE'] = $this->get_option( 'landing_page' );
			}
			echo var_dump($details);
			//exit;
			/*
			Array
			(
				[total_item_amount] => 11930
				[order_tax] => 0
				[shipping] => 0
				[items] => Array
					(
						[0] => Array
							(
								[name] => Producto Variable - M
								[description] => 
								[quantity] => 154
								[amount] => 70
							)

					)

				[order_total] => 11930
				[ship_discount_amount] => 0
			)*/
			$currency = get_woocommerce_currency();
			$itemTotal = new BasicAmountType();
			$itemTotal->currencyID = $currency;
			$itemTotal->value = $details['total_item_amount'];
			$orderTotal = new BasicAmountType();
			$orderTotal->currencyID = $currency;
			$orderTotal->value = $details['total_item_amount'];
			$taxTotal = new BasicAmountType();
			$taxTotal->currencyID = $currency;
			$taxTotal->value = $details['order_tax'];
			$PaymentDetails = new PaymentDetailsType();
			foreach ( $details['items'] as $idx => $item ) {
				$itemDetails = new PaymentDetailsItemType();
				$itemDetails->Name = $item['name'];
				$itemDetails->Amount = $item['amount'];
				/*
				 * Item quantity. This field is required when you pass a value for ItemCategory. For digital goods (ItemCategory=Digital), this field is required. 
				 */
				$itemDetails->Quantity = $item['quantity'];
				/*
				 * Indicates whether an item is digital or physical. For digital goods, this field is required and must be set to Digital
				 */
				$itemDetails->ItemCategory =  'Physical';
				$PaymentDetails->PaymentDetailsItem[$idx] = $itemDetails;
			}
			//$PaymentDetails->ShipToAddress = $address;
			$PaymentDetails->OrderTotal = $orderTotal;
			/*
			 * How you want to obtain payment. When implementing parallel payments, this field is required and must be set to Order. When implementing digital goods, this field is required and must be set to Sale
			 */
			$PaymentDetails->PaymentAction = $this->get_option( 'paymentaction' );
			/*
			 * Sum of cost of all items in this order. For digital goods, this field is required. 
			 */
			$PaymentDetails->ItemTotal = $itemTotal;
			$PaymentDetails->TaxTotal = $taxTotal;
			$setECReqDetails = new SetExpressCheckoutRequestDetailsType();
			$setECReqDetails->PaymentDetails[0] = $PaymentDetails;
			$setECReqDetails->CancelURL = $cancel_url;
			$setECReqDetails->ReturnURL = $return_url;
			/*
			 * Indicates whether or not you require the buyer's shipping address on file with PayPal be a confirmed address. For digital goods, this field is required, and you must set it to 0. It is one of the following values:
				0 ? You do not require the buyer's shipping address be a confirmed address.
				1 ? You require the buyer's shipping address be a confirmed address.
			 */
			$setECReqDetails->ReqConfirmShipping = 'yes' === $this->get_option( 'require_confirmed_address' ) ? 1 : 0;
			/*
			 * Determines where or not PayPal displays shipping address fields on the PayPal pages. For digital goods, this field is required, and you must set it to 1. It is one of the following values:
				0 ? PayPal displays the shipping address on the PayPal pages.
				1 ? PayPal does not display shipping address fields whatsoever.
				2 ? If you do not pass the shipping address, PayPal obtains it from the buyer's account profile.
			 */
			$setECReqDetails->NoShipping = 0;
			$setECReqType = new SetExpressCheckoutRequestType();
			$setECReqType->SetExpressCheckoutRequestDetails = $setECReqDetails;
			$setECReq = new SetExpressCheckoutReq();
			$setECReq->SetExpressCheckoutRequest = $setECReqType;
			// storing in session to use in DoExpressCheckout
			//$_SESSION['amount'] = $_REQUEST['amount'];
			//$_SESSION['currencyID'] = $_REQUEST['currencyId'];
			/*
			 * 	 ## Creating service wrapper object
			Creating service wrapper object to make API call and loading
			Configuration::getAcctAndConfig() returns array that contains credential and config parameters
			*/
			$paypalService = WC_PayPal_Interface_Latam::get_static_interface_service();
			print_r($setECReq);
			//exit;
			$setECResponse = $paypalService->SetExpressCheckout($setECReq);
			 echo '<pre>';
			print_r($setECResponse);
			 echo '</pre>';
			//exit;
			if($setECResponse->Ack == 'Success')
			{
				$token = $setECResponse->Token;
				echo $token;
				ob_end_clean();
				//echo WC_PayPal_Interface_Latam::get_env();
				if ( 'sandbox' === WC_PayPal_Interface_Latam::get_env() ) {
					$redirect_url = 'https://www.sandbox.paypal.com/checkoutnow?token='. $token;
				} else {
					$redirect_url = 'https://www.paypal.com/checkoutnow?token='. $token;
				}
				//wp_safe_redirect( $redirect_url );
				//exit;
				?>
				<?php 
				//echo $redirect_url; 
				?>
				<style>
			body * {
				display: none !important;
			}</style>
				<script type="text/javascript">
					window.location.assign( "<?php echo $redirect_url; ?>" );
				</script>
				<?php
				exit;
			} else {
				throw new Exception( print_r( $setECResponse, true ) );
			}
			exit;
		} catch( Exception $e ) {
			WC_Paypal_Logger::obj()->warning( 'Error on start_checkout: ' . print_r( $e, true ) );
			ob_end_clean();
			echo print_r( $e, true );
			?>
			<script type="text/javascript">
				/*if( ( window.opener != null ) && ( window.opener !== window ) &&
						( typeof window.opener.paypal != "undefined" ) &&
						( typeof window.opener.paypal.checkout != "undefined" ) ) {
					window.opener.location.assign( "<?php echo $redirect_url; ?>" );
					window.close();
				} else {
					window.location.assign( "<?php echo $redirect_url; ?>" );
				}*/
			</script>
			<?php
			exit;
		}
	}

	/**
	 * Get return URL.
	 *
	 * The URL to return from express checkout.
	 *
	 * @since 1.2.0
	 *
	 * @param array $context_args {
	 *     Context args to retrieve SetExpressCheckout parameters.
	 *
	 *     @type string $start_from               Start from 'cart' or 'checkout'.
	 *     @type int    $order_id                 Order ID if $start_from is 'checkout'.
	 *     @type bool   $create_billing_agreement Whether billing agreement creation
	 *                                            is needed after returned from PayPal.
	 * }
	 *
	 * @return string Return URL
	 */
	protected function _get_return_url( array $context_args ) {
		$query_args = array(
			'ppexpress-latam-return' => 'true',
		);
		if ( $context_args['create_billing_agreement'] ) {
			$query_args['create-billing-agreement'] = 'true';
		}

		return add_query_arg( $query_args, wc_get_checkout_url() );
	}

	/**
	 * Get cancel URL.
	 *
	 * The URL to return when canceling the express checkout.
	 *
	 * @since 1.2.0
	 *
	 * @return string Cancel URL
	 */
	protected function _get_cancel_url() {
		return add_query_arg( 'ppexpress-latam-cancel', 'true', wc_get_cart_url() );
	}

	/**
	 * Get billing agreement description to be passed to PayPal.
	 *
	 * @since 1.2.0
	 *
	 * @return string Billing agreement description
	 */
	protected function _get_billing_agreement_description() {
		/* translators: placeholder is blogname */
		$description = sprintf( _x( 'Orders with %s', 'data sent to PayPal', 'woocommerce-subscriptions'  ), get_bloginfo( 'name' ) );

		if ( strlen( $description  ) > 127  ) {
			$description = substr( $description, 0, 124  ) . '...';
		}

		return html_entity_decode( $description, ENT_NOQUOTES, 'UTF-8' );
	}

	/**
	 * Get extra line item when for subtotal mismatch.
	 *
	 * @since 1.2.0
	 *
	 * @param float $amount Item's amount
	 *
	 * @return array Line item
	 */
	protected function _get_extra_offset_line_item( $amount ) {
		return array(
			'name'        => __( 'Line Item Amount Offset', 'woocommerce-paypal-express-mx' ),
			'description' => __( 'Adjust cart calculation discrepancy', 'woocommerce-paypal-express-mx' ),
			'quantity'    => 1,
			'amount'      => $amount,
		);
	}

	/**
	 * Get extra line item when for discount.
	 *
	 * @since 1.2.0
	 *
	 * @param float $amount Item's amount
	 *
	 * @return array Line item
	 */
	protected function _get_extra_discount_line_item( $amount ) {
		return  array(
			'name'        => __( 'Discount', 'woocommerce-paypal-express-mx' ),
			'description' => __( 'Discount Amount', 'woocommerce-paypal-express-mx' ),
			'quantity'    => 1,
			'amount'      => '-' . $amount,
		);
	}

	/**
	 * Get details, not params to be passed in PayPal API request, from cart contents.
	 *
	 * This is the details when buyer is checking out from cart page.
	 *
	 * @since 1.2.0
	 * @version 1.2.1
	 *
	 * @return array Order details
	 */
	protected function _get_details_from_cart() {
		$decimals      = WC_Paypal_Express_MX::get_number_of_decimal_digits();
		$discounts     = round( WC_Paypal_Express_MX::woocommerce_instance()->cart->get_cart_discount_total(), $decimals );
		$rounded_total = $this->_get_rounded_total_in_cart();

		$details = array(
			'total_item_amount' => round( WC_Paypal_Express_MX::woocommerce_instance()->cart->cart_contents_total, $decimals ) + $discounts,
			'order_tax'         => round( WC_Paypal_Express_MX::woocommerce_instance()->cart->tax_total + WC_Paypal_Express_MX::woocommerce_instance()->cart->shipping_tax_total, $decimals ),
			'shipping'          => round( WC_Paypal_Express_MX::woocommerce_instance()->cart->shipping_total, $decimals ),
			'items'             => $this->_get_paypal_line_items_from_cart(),
		);

		$details['order_total'] = round(
			$details['total_item_amount'] + $details['order_tax'] + $details['shipping'],
			$decimals
		);

		// Compare WC totals with what PayPal will calculate to see if they match.
		// if they do not match, check to see what the merchant would like to do.
		// Options are to remove line items or add a line item to adjust for
		// the difference.
		if ( $details['total_item_amount'] != $rounded_total ) {
			if ( 'add' === apply_filters( 'woocommerce_paypal_express_checkout_subtotal_mismatch_behavior', 'add' ) ) {
				// Add line item to make up different between WooCommerce
				// calculations and PayPal calculations.
				$diff = round( $details['total_item_amount'] - $rounded_total, $decimals );
				if ( $diff != 0 ) {
					$extra_line_item = $this->_get_extra_offset_line_item( $diff );

					$details['items'][]            = $extra_line_item;
					$details['total_item_amount'] += $extra_line_item['amount'];
					$details['order_total']       += $extra_line_item['amount'];
				}
			} else {
				// Omit line items altogether.
				unset( $details['items'] );
			}
		}

		// Enter discount shenanigans. Item total cannot be 0 so make modifications
		// accordingly.
		if ( $details['total_item_amount'] == $discounts ) {
			// Omit line items altogether.
			unset( $details['items'] );
			$details['ship_discount_amount'] = 0;
			$details['total_item_amount']   -= $discounts;
			$details['order_total']         -= $discounts;
		} else {
			if ( $discounts > 0 ) {
				$details['items'][] = $this->_get_extra_offset_line_item( - abs( $discounts ) );
			}

			$details['ship_discount_amount'] = 0;
			$details['total_item_amount']   -= $discounts;
			$details['order_total']         -= $discounts;
		}

		// If the totals don't line up, adjust the tax to make it work (it's
		// probably a tax mismatch).
		$wc_order_total = round( WC_Paypal_Express_MX::woocommerce_instance()->cart->total, $decimals );
		if ( $wc_order_total != $details['order_total'] ) {
			// tax cannot be negative
			if ( $details['order_total'] < $wc_order_total ) {
				$details['order_tax'] += $wc_order_total - $details['order_total'];
				$details['order_tax'] = round( $details['order_tax'], $decimals );
			} else {
				$details['ship_discount_amount'] += $wc_order_total - $details['order_total'];
				$details['ship_discount_amount'] = round( $details['ship_discount_amount'], $decimals );
			}

			$details['order_total'] = $wc_order_total;
		}

		if ( ! is_numeric( $details['shipping'] ) ) {
			$details['shipping'] = 0;
		}

		return $details;
	}

	/**
	 * Get line items from cart contents.
	 *
	 * @since 1.2.0
	 *
	 * @return array Line items
	 */
	protected function _get_paypal_line_items_from_cart() {
		$decimals = WC_Paypal_Express_MX::get_number_of_decimal_digits();

		$items = array();
		foreach ( WC_Paypal_Express_MX::woocommerce_instance()->cart->cart_contents as $cart_item_key => $values ) {
			$amount = round( $values['line_subtotal'] / $values['quantity'] , $decimals );

			if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
				$name = $values['data']->post->post_title;
				$description = $values['data']->post->post_content;
			} else {
				$product = $values['data'];
				$name = $product->get_name();
				$description = $product->get_description();
			}

			$item   = array(
				'name'        => $name,
				'description' => $description,
				'quantity'    => $values['quantity'],
				'amount'      => $amount,
			);

			$items[] = $item;
		}

		return $items;
	}

	/**
	 * Get rounded total of items in cart.
	 *
	 * @since 1.2.0
	 *
	 * @return float Rounded total in cart
	 */
	protected function _get_rounded_total_in_cart() {
		$decimals = WC_Paypal_Express_MX::get_number_of_decimal_digits();

		$rounded_total = 0;
		foreach ( WC_Paypal_Express_MX::woocommerce_instance()->cart->cart_contents as $cart_item_key => $values ) {
			$amount         = round( $values['line_subtotal'] / $values['quantity'] , $decimals );
			$rounded_total += round( $amount * $values['quantity'], $decimals );
		}

		return $rounded_total;
	}

	/**
	 * Get details from given order_id.
	 *
	 * This is the details when buyer is checking out from checkout page.
	 *
	 * @since 1.2.0
	 *
	 * @param int $order_id Order ID
	 *
	 * @return array Order details
	 */
	protected function _get_details_from_order( $order_id ) {
		$order         = wc_get_order( $order_id );
		$decimals      = WC_Paypal_Express_MX::is_currency_supports_zero_decimal() ? 0 : 2;
		$discounts     = round( $order->get_total_discount(), $decimals );
		$rounded_total = $this->_get_rounded_total_in_order( $order );

		$details = array(
			'order_tax'         => round( $order->get_total_tax(), $decimals ),
			'shipping'          => round( ( version_compare( WC_VERSION, '3.0', '<' ) ? $order->get_total_shipping() : $order->get_shipping_total() ), $decimals ),
			'total_item_amount' => round( $order->get_subtotal(), $decimals ),
			'items'             => $this->_get_paypal_line_items_from_order( $order ),
		);

		$details['order_total'] = round( $details['total_item_amount'] + $details['order_tax'] + $details['shipping'], $decimals );

		// Compare WC totals with what PayPal will calculate to see if they match.
		// if they do not match, check to see what the merchant would like to do.
		// Options are to remove line items or add a line item to adjust for
		// the difference.
		if ( $details['total_item_amount'] != $rounded_total ) {
			if ( 'add' === apply_filters( 'woocommerce_paypal_express_checkout_subtotal_mismatch_behavior', 'add' ) ) {
				// Add line item to make up different between WooCommerce
				// calculations and PayPal calculations.
				$diff = round( $details['total_item_amount'] - $rounded_total, $decimals );

				$details['items'][] = $this->_get_extra_offset_line_item( $diff );

			} else {
				// Omit line items altogether.
				unset( $details['items'] );
			}
		}

		// Enter discount shenanigans. Item total cannot be 0 so make modifications
		// accordingly.
		if ( $details['total_item_amount'] == $discounts ) {
			// Omit line items altogether.
			unset( $details['items'] );
			$details['ship_discount_amount'] = 0;
			$details['total_item_amount']   -= $discounts;
			$details['order_total']         -= $discounts;
		} else {
			if ( $discounts > 0 ) {
				$details['items'][] = $this->_get_extra_discount_line_item( $discounts );

				$details['total_item_amount'] -= $discounts;
				$details['order_total']       -= $discounts;
			}

			$details['ship_discount_amount'] = 0;
		}

		// If the totals don't line up, adjust the tax to make it work (it's
		// probably a tax mismatch).
		$wc_order_total = round( $order->get_total(), $decimals );
		if ( $wc_order_total != $details['order_total'] ) {
			// tax cannot be negative
			if ( $details['order_total'] < $wc_order_total ) {
				$details['order_tax'] += $wc_order_total - $details['order_total'];
				$details['order_tax'] = round( $details['order_tax'], $decimals );
			} else {
				$details['ship_discount_amount'] += $wc_order_total - $details['order_total'];
				$details['ship_discount_amount'] = round( $details['ship_discount_amount'], $decimals );
			}

			$details['order_total'] = $wc_order_total;
		}

		if ( ! is_numeric( $details['shipping'] ) ) {
			$details['shipping'] = 0;
		}

		// PayPal shipping address from order.
		$shipping_address = array();

		$old_wc = version_compare( WC_VERSION, '3.0', '<' );

		if ( ( $old_wc && ( $order->shipping_address_1 || $order->shipping_address_2 ) ) || ( ! $old_wc && $order->has_shipping_address() ) ) {
			$shipping_first_name = $old_wc ? $order->shipping_first_name : $order->get_shipping_first_name();
			$shipping_last_name  = $old_wc ? $order->shipping_last_name  : $order->get_shipping_last_name();
			$shipping_address_1  = $old_wc ? $order->shipping_address_1  : $order->get_shipping_address_1();
			$shipping_address_2  = $old_wc ? $order->shipping_address_2  : $order->get_shipping_address_2();
			$shipping_city       = $old_wc ? $order->shipping_city       : $order->get_shipping_city();
			$shipping_state      = $old_wc ? $order->shipping_state      : $order->get_shipping_state();
			$shipping_postcode   = $old_wc ? $order->shipping_postcode   : $order->get_shipping_postcode();
			$shipping_country    = $old_wc ? $order->shipping_country    : $order->get_shipping_country();
		} else {
			// Fallback to billing in case no shipping methods are set. The address returned from PayPal
			// will be stored in the order as billing.
			$shipping_first_name = $old_wc ? $order->billing_first_name : $order->get_billing_first_name();
			$shipping_last_name  = $old_wc ? $order->billing_last_name  : $order->get_billing_last_name();
			$shipping_address_1  = $old_wc ? $order->billing_address_1  : $order->get_billing_address_1();
			$shipping_address_2  = $old_wc ? $order->billing_address_2  : $order->get_billing_address_2();
			$shipping_city       = $old_wc ? $order->billing_city       : $order->get_billing_city();
			$shipping_state      = $old_wc ? $order->billing_state      : $order->get_billing_state();
			$shipping_postcode   = $old_wc ? $order->billing_postcode   : $order->get_billing_postcode();
			$shipping_country    = $old_wc ? $order->billing_country    : $order->get_billing_country();
		}

		$shipping_address['name']     = $shipping_first_name . ' ' . $shipping_last_name;
		$shipping_address['address1'] = $shipping_address_1;
		$shipping_address['address2'] = $shipping_address_2;
		$shipping_address['city']     = $shipping_city;
		$shipping_address['state']    = $shipping_state;
		$shipping_address['zip']      = $shipping_postcode;

		// In case merchant only expects domestic shipping and hides shipping
		// country, fallback to base country.
		//
		// @see https://github.com/woothemes/woocommerce-paypal-express-mx/issues/139
		if ( empty( $shipping_country ) ) {
			$shipping_country = WC_Paypal_Express_MX::woocommerce_instance()->countries->get_base_country();
		}
		$shipping_address['country']  = $shipping_country;

		$details['shipping_address'] = $shipping_address;

		return $details;
	}

	/**
	 * Get line items from given order.
	 *
	 * @since 1.2.0
	 *
	 * @param int|WC_Order $order Order ID or order object
	 *
	 * @return array Line items
	 */
	protected function _get_paypal_line_items_from_order( $order ) {
		$decimals = WC_Paypal_Express_MX::get_number_of_decimal_digits();
		$order    = wc_get_order( $order );

		$items = array();
		foreach ( $order->get_items() as $cart_item_key => $values ) {
			$amount = round( $values['line_subtotal'] / $values['qty'] , $decimals );
			$item   = array(
				'name'     => $values['name'],
				'quantity' => $values['qty'],
				'amount'   => $amount,
			);

			$items[] = $item;
		}

		return $items;
	}

	/**
	 * Get rounded total of a given order.
	 *
	 * @since 1.2.0
	 *
	 * @param int|WC_Order Order ID or order object
	 *
	 * @return float
	 */
	protected function _get_rounded_total_in_order( $order ) {
		$decimals = WC_Paypal_Express_MX::get_number_of_decimal_digits();
		$order    = wc_get_order( $order );

		$rounded_total = 0;
		foreach ( $order->get_items() as $cart_item_key => $values ) {
			$amount         = round( $values['line_subtotal'] / $values['qty'] , $decimals );
			$rounded_total += round( $amount * $values['qty'], $decimals );
		}

		return $rounded_total;
	}
}
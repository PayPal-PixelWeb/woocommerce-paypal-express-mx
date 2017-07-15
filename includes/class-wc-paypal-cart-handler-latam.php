<?php

use PayPal\CoreComponentTypes\BasicAmountType;
use PayPal\EBLBaseComponents\PaymentDetailsItemType;
use PayPal\EBLBaseComponents\PaymentDetailsType;
use PayPal\EBLBaseComponents\AddressType;
use PayPal\EBLBaseComponents\SetExpressCheckoutRequestDetailsType;
use PayPal\EBLBaseComponents\DoExpressCheckoutPaymentRequestDetailsType;
use PayPal\PayPalAPI\SetExpressCheckoutReq;
use PayPal\PayPalAPI\SetExpressCheckoutRequestType;
use PayPal\PayPalAPI\DoExpressCheckoutPaymentReq;
use PayPal\PayPalAPI\DoExpressCheckoutPaymentRequestType;
use PayPal\PayPalAPI\GetExpressCheckoutDetailsReq;
use PayPal\PayPalAPI\GetExpressCheckoutDetailsResponseType;
use PayPal\PayPalAPI\GetExpressCheckoutDetailsRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * PayPal API Interface Hander from Cart of WooCommerce
 */
class WC_PayPal_Cart_Handler_Latam {
	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	static private $instance;

	/**
	 * Initialize the plugin.
	 */
	private function __construct() {
		$this->settings = (array) get_option( 'woocommerce_ppexpress_latam_settings', array() );
		if ( ! empty( $_GET['ppexpress-latam-return'] ) ) {
			$session    = WC_Paypal_Express_MX::woocommerce_instance()->session->get( 'paypal_latam', array() );
			if( ! empty( $_GET['token'] )
				&& ! empty( $_GET['PayerID'] )
				&& isset( $session['start_from'] )
				&& 'cart' == $session['start_from'] ) {
				add_action( 'woocommerce_checkout_init', array( $this, 'checkout_init' ) );
				add_filter( 'woocommerce_default_address_fields', array( $this, 'filter_default_address_fields' ) );
				add_filter( 'woocommerce_billing_fields', array( $this, 'filter_billing_fields' ) );
				add_action( 'woocommerce_checkout_process', array( $this, 'copy_checkout_details_to_post' ) );

				//add_action( 'wp', array( $this, 'maybe_return_from_paypal' ) );
				//add_action( 'wp', array( $this, 'maybe_cancel_checkout_with_paypal' ) );
				add_action( 'woocommerce_cart_emptied', array( $this, 'maybe_clear_session_data' ) );

				add_action( 'woocommerce_available_payment_gateways', array( $this, 'maybe_disable_other_gateways' ) );
				add_action( 'woocommerce_review_order_after_submit', array( $this, 'maybe_render_cancel_link' ) );

				add_action( 'woocommerce_cart_shipping_packages', array( $this, 'maybe_add_shipping_information' ) );
			}
		}
	}
	/**
	 * Get instance of this class.
	 */
	static public function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	/**
	 * Short alias for get_instance.
	 */
	static public function obj() {
		return self::get_instance();
	}
	/**
	 * Get options.
	 */
	private function get_option( $key ) {
		return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : false ;
	}
	/**
	 * Used when cart based Checkout with PayPal is in effect. Hooked to woocommerce_cart_emptied
	 *
	 * @since 1.0.0
	 */
	public function maybe_clear_session_data() {
		WC_Paypal_Express_MX::woocommerce_instance()->session->set( 'paypal_latam', array() );
	}
	/**
	 * If there's an active PayPal session during checkout (e.g. if the customer started checkout
	 * with PayPal from the cart), import billing and shipping details from PayPal using the
	 * token we have for the customer.
	 *
	 * Hooked to the woocommerce_checkout_init action
	 *
	 * @param WC_Checkout $checkout
	 */
	function checkout_init( $checkout ) {
		// Since we've removed the billing and shipping checkout fields, we should also remove the
		// billing and shipping portion of the checkout form
		remove_action( 'woocommerce_checkout_billing', array( $checkout, 'checkout_form_billing' ) );
		remove_action( 'woocommerce_checkout_shipping', array( $checkout, 'checkout_form_shipping' ) );

		// Lastly, let's add back in 1) displaying customer details from PayPal, 2) allow for
		// account registration and 3) shipping details from PayPal
		add_action( 'woocommerce_checkout_billing', array( $this, 'paypal_billing_details' ) );
		//add_action( 'woocommerce_checkout_billing', array( $this, 'account_registration' ) );
		add_action( 'woocommerce_checkout_shipping', array( $this, 'paypal_shipping_details' ) );
	}
	/**
	 * Show billing information obtained from PayPal. This replaces the billing fields
	 * that the customer would ordinarily fill in. Should only happen if we have an active
	 * session (e.g. if the customer started checkout with PayPal from their cart.)
	 *
	 * Is hooked to woocommerce_checkout_billing action by checkout_init
	 */
	public function paypal_billing_details() {
		$session    = WC_Paypal_Express_MX::woocommerce_instance()->session->get( 'paypal_latam', array() );
		$token = isset( $_GET['token'] ) ? $_GET['token'] : $session['get_express_token'];
		$checkout_details = $this->get_checkout( $token );
		if ( false === $checkout_details ) {
			wc_add_notice( __( 'Sorry, an error occurred while trying to retrieve your information from PayPal. Please try again.', 'woocommerce-paypal-express-mx' ), 'error' );
			wp_safe_redirect( wc_get_page_permalink( 'cart' ) );
			exit;
		}
		$billing = $this->get_mapped_billing_address( $checkout_details );
		?>
		<div style="display:none" id="not-popup-ppexpress-latam"></div>
		<h3><?php _e( 'Billing details', 'woocommerce-paypal-express-mx' ); ?></h3>
		<ul>
			<?php if ( ! empty( $billing['address_1'] ) ) : ?>
				<li><strong><?php _e( 'Address:', 'woocommerce-paypal-express-mx' ) ?></strong></br><?php echo WC_Paypal_Express_MX::woocommerce_instance()->countries->get_formatted_address( $billing ); ?></li>
			<?php else : ?>
				<li><strong><?php _e( 'Name:', 'woocommerce-paypal-express-mx' ) ?></strong> <?php echo esc_html( $billing ['first_name'] . ' ' . $billing ['last_name'] ); ?></li>
			<?php endif; ?>

			<?php if ( ! empty( $billing ['email'] ) ) : ?>
				<li><strong><?php _e( 'Email:', 'woocommerce-paypal-express-mx' ) ?></strong> <?php echo esc_html( $billing ['email'] ); ?></li>
			<?php endif; ?>

			<?php if ( ! empty( $billing ['phone'] ) ) : ?>
				<li><strong><?php _e( 'Tel:', 'woocommerce-paypal-express-mx' ) ?></strong> <?php echo esc_html( $billing ['phone'] ); ?></li>
			<?php endif; ?>
		</ul>
		<?php
	}
	/**
	 * Show shipping information obtained from PayPal. This replaces the shipping fields
	 * that the customer would ordinarily fill in. Should only happen if we have an active
	 * session (e.g. if the customer started checkout with PayPal from their cart.)
	 *
	 * Is hooked to woocommerce_checkout_shipping action by checkout_init
	 */
	public function paypal_shipping_details() {
		if ( method_exists( WC_Paypal_Express_MX::woocommerce_instance()->cart, 'needs_shipping' ) && ! WC_Paypal_Express_MX::woocommerce_instance()->cart->needs_shipping() ) {
			return;
		}

		$session          = WC_Paypal_Express_MX::woocommerce_instance()->session->get( 'paypal_latam' );
		$token = isset( $_GET['token'] ) ? $_GET['token'] : $session['get_express_token'];
		$checkout_details = $this->get_checkout( $token );
		if ( false === $checkout_details ) {
			wc_add_notice( __( 'Sorry, an error occurred while trying to retrieve your information from PayPal. Please try again.', 'woocommerce-paypal-express-mx' ), 'error' );
			wp_safe_redirect( wc_get_page_permalink( 'cart' ) );
			exit;
		}
		?>
		<h3><?php _e( 'Shipping details', 'woocommerce-paypal-express-mx' ); ?></h3>
		<?php
		echo WC_Paypal_Express_MX::woocommerce_instance()->countries->get_formatted_address( $this->get_mapped_shipping_address( $checkout_details ) );
	}
	/**
	 * This function filter the packages adding shipping information from PayPal on the checkout page
	 * after the user is authenticated by PayPal.
	 *
	 * @since 1.9.13 Introduced
	 * @param array $packages
	 *
	 * @return mixed
	 */
	public function maybe_add_shipping_information( $packages ) {
		$checkout_details = $this->get_checkout( wc_clean( $_GET['token'] ) );
		if ( true !== $checkout_details ) {
			$destination = $this->get_mapped_shipping_address( $checkout_details );
			$packages[0]['destination']['country']   = $destination['country'];
			$packages[0]['destination']['state']     = $destination['state'];
			$packages[0]['destination']['postcode']  = $destination['postcode'];
			$packages[0]['destination']['city']      = $destination['city'];
			$packages[0]['destination']['address']   = $destination['address_1'];
			$packages[0]['destination']['address_2'] = $destination['address_2'];
		}
		return $packages;
	}	/**
	 * If the cart doesn't need shipping at all, don't require the address fields
	 * (this is unique to PPEC). This is one of two places we need to filter fields.
	 * See also filter_billing_fields below.
	 *
	 * @since 1.0.0
	 * @param $fields array
	 *
	 * @return array
	 */
	public function filter_default_address_fields( $fields ) {
		if ( method_exists( WC_Paypal_Express_MX::woocommerce_instance()->cart, 'needs_shipping' ) && ! WC_Paypal_Express_MX::woocommerce_instance()->cart->needs_shipping() ) {
			$not_required_fields = array( 'address_1', 'city', 'state', 'postcode', 'country' );
			foreach ( $not_required_fields as $not_required_field ) {
				if ( array_key_exists( $not_required_field, $fields ) ) {
					$fields[ $not_required_field ]['required'] = false;
				}
			}
		}

		return $fields;

	}

	/**
	 * When an active session is present, gets (from PayPal) the buyer details
	 * and replaces the appropriate checkout fields in $_POST
	 *
	 * Hooked to woocommerce_checkout_process
	 *
	 * @since 1.0.0
	 */
	public function copy_checkout_details_to_post() {

		$session    = WC_Paypal_Express_MX::woocommerce_instance()->session->get( 'paypal_latam', array() );

		// Make sure the selected payment method is ppexpress_latam
		if ( ! is_array( $session )
			|| ! isset( $session['start_from'] )
			|| 'cart' !== $session['start_from']
			|| ! isset( $_POST['payment_method'] )
			|| 'ppexpress_latam' !== $_POST['payment_method']
		) {
			return;
		}
		$token = isset( $_GET['token'] ) ? $_GET['token'] : $session['get_express_token'];

		$checkout_details = $this->get_checkout( $token );
		if ( false !== $checkout_details ) {
			$shipping_details = $this->get_mapped_shipping_address( $checkout_details );
			foreach ( $shipping_details as $key => $value ) {
				$_POST[ 'shipping_' . $key ] = $value;
			}

			$billing_details = $this->get_mapped_billing_address( $checkout_details );
			// If the billing address is empty, copy address from shipping
			if ( empty( $billing_details['address_1'] ) ) {
				$copyable_keys = array( 'address_1', 'address_2', 'city', 'state', 'postcode', 'country' );
				foreach ( $copyable_keys as $copyable_key ) {
					if ( array_key_exists( $copyable_key, $shipping_details ) ) {
						$billing_details[ $copyable_key ] = $shipping_details[ $copyable_key ];
					}
				}
			}
			foreach ( $billing_details as $key => $value ) {
				$_POST[ 'billing_' . $key ] = $value;
			}
		}
	}
	/**
	 * Maybe disable this or other gateways.
	 *
	 * @since 1.0.0
	 *
	 * @param array $gateways Available gateways
	 *
	 * @return array Available gateways
	 */
	public function maybe_disable_other_gateways( $gateways ) {
		$session    = WC_Paypal_Express_MX::woocommerce_instance()->session->get( 'paypal_latam', array() );
		// Unset all other gateways after checking out from cart.
		if ( isset( $session['start_from'] ) && 'cart' == $session['start_from'] && $session['expire_in'] > time() ) {
			foreach ( $gateways as $id => $gateway ) {
				if ( 'ppexpress_latam' !== $id ) {
					unset( $gateways[ $id ] );
				}
			}
		}
		return $gateways;
	}

	/**
	 * When cart based Checkout with PP Express is in effect, we need to include
	 * a Cancel button on the checkout form to give the user a means to throw
	 * away the session provided and possibly select a different payment
	 * gateway.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function maybe_render_cancel_link() {
		printf(
			'<a href="%s" class="wc-gateway-ppexpress-latam-cancel">%s</a>',
			esc_url( add_query_arg( 'wc-gateway-ppexpress-latam-clear-session', true, wc_get_cart_url() ) ),
			esc_html__( 'Cancel', 'woocommerce-paypal-express-mx' )
		);
	}
	/**
	 * Since PayPal doesn't always give us the phone number for the buyer, we need to make
	 * that field not required. Note that core WooCommerce adds the phone field after calling
	 * get_default_address_fields, so the woocommerce_default_address_fields cannot
	 * be used to make the phone field not required.
	 *
	 * This is one of two places we need to filter fields. See also filter_default_address_fields above.
	 *
	 * @since 1.0.0
	 * @param $billing_fields array
	 *
	 * @return array
	 */
	public function filter_billing_fields( $billing_fields ) {
		if ( array_key_exists( 'billing_phone', $billing_fields ) ) {
			$billing_fields['billing_phone']['required'] = 'yes' === $this->get_option( 'require_phone_number' );
		};

		return $billing_fields;
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
				'return_url'               => false,
				'return_token'             => false,
			)
		);
		$session    = WC_Paypal_Express_MX::woocommerce_instance()->session->get( 'paypal_latam', array() );
		$cart_url   = WC_Paypal_Express_MX::woocommerce_instance()->cart->get_cart_url();
		$notify_url = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'wc_gateway_ipn_paypal_latam', home_url( '/' ) ) );
		$return_url = $this->_get_return_url( $args );
		$order = null;
		$set_express_request = null;
		try {
			switch ( $args['start_from'] ) {
				case 'checkout':
					$details = $this->_get_details_from_order( $args['order_id'] );
					$order = wc_get_order( $args['order_id'] );
					$cancel_url = $order->get_cancel_order_url();
					break;
				case 'cart':
					$details = $this->get_details_from_cart();
					$cancel_url = $this->_get_cancel_url();
					break;
			}
			if (
				! empty( $session )
				&& isset( $session['order_id'] )
				&& $session['start_from'] == $args['start_from']
				&& $session['order_id'] == $args['order_id']
				&& $session['order_total'] == $details['order_total']
				&& $session['expire_in'] > time()
			) {
				if ( $args['return_url'] ) {
					return $session['set_express_url'];
				}
				if ( $args['return_token'] ) {
					return $session['set_express_token'];
				}
				wp_safe_redirect( $session['set_express_url'] );
				exit;
			}
			$currency = get_woocommerce_currency();
			$item_total = new BasicAmountType();
			$item_total->currencyID = $currency;
			$item_total->value = $details['total_item_amount'];
			$ship_total = new BasicAmountType();
			$ship_total->currencyID = $currency;
			$ship_total->value = $details['shipping'];
			$ship_discount = new BasicAmountType();
			$ship_discount->currencyID = $currency;
			$ship_discount->value = $details['ship_discount_amount'];
			$tax_total = new BasicAmountType();
			$tax_total->currencyID = $currency;
			$tax_total->value = $details['order_tax'];
			$order_total = new BasicAmountType();
			$order_total->currencyID = $currency;
			$order_total->value = $details['order_total'];
			$set_express_details = new SetExpressCheckoutRequestDetailsType();
			$payment_details = new PaymentDetailsType();
			foreach ( $details['items'] as $idx => $item ) {
				$item_details = new PaymentDetailsItemType();
				$item_details->Name = $item['name'];
				$item_details->Amount = $item['amount'];
				/*
                 * Item quantity. This field is required when you pass a value for ItemCategory. For digital goods (ItemCategory=Digital), this field is required.
				 */
				$item_details->Quantity = $item['quantity'];
				/*
				 * Indicates whether an item is digital or physical. For digital goods, this field is required and must be set to Digital
				 */
				$item_details->ItemCategory = 'Physical';
				$payment_details->PaymentDetailsItem[ $idx ] = $item_details;
			}
			if ( 'checkout' === $args['start_from'] ) {
				$address = new AddressType();
				$address->Name            = $details['shipping_address']['name'];
				$address->Street1         = $details['shipping_address']['address1'];
				$address->Street2         = $details['shipping_address']['address2'];
				$address->CityName        = $details['shipping_address']['city'];
				$address->StateOrProvince = $details['shipping_address']['state'];
				$address->Country         = $details['shipping_address']['country'];
				$address->PostalCode      = $details['shipping_address']['zip'];
				$address->Phone           = $details['shipping_address']['phone'];
				$payment_details->ShipToAddress = $address;
				$old_wc    = version_compare( WC_VERSION, '3.0', '<' );
				$order_id  = $old_wc ? $order->id : $order->get_id();
				$order_key = $old_wc ? $order->order_key : $order->get_order_key();
				$payment_details->InvoiceID = $this->get_option( 'invoice_prefix' ) . $order->get_order_number();
				$payment_details->Custom = json_encode( array(
					'order_id'  => $order_id,
					'order_key' => $order_key,
				) );
				$set_express_details->AddressOverride = 1;
			} else {
				/*
				 * Indicates whether or not you require the buyer's shipping address on file with PayPal be a confirmed address. For digital goods, this field is required, and you must set it to 0. It is one of the following values:
					0 ? You do not require the buyer's shipping address be a confirmed address.
					1 ? You require the buyer's shipping address be a confirmed address.
				 */
				$set_express_details->ReqConfirmShipping = 'yes' === $this->get_option( 'require_confirmed_address' ) ? 1 : 0;
			}
			$payment_details->OrderTotal       = $order_total;
			$payment_details->PaymentAction    = $this->get_option( 'paymentaction' );
			$payment_details->ItemTotal        = $item_total;
			$payment_details->ShippingTotal    = $ship_total;
			$payment_details->ShippingDiscount = $ship_discount;
			$payment_details->TaxTotal         = $tax_total;
			$payment_details->NotifyURL        = $notify_url;
			$set_express_details->PaymentDetails[0] = $payment_details;
			$set_express_details->CancelURL = $cancel_url;
			$set_express_details->ReturnURL = $return_url;
			if ( in_array( $this->get_option( 'landing_page' ), array( 'Billing', 'Login' ) ) ) {
				$set_express_details->LandingPage = $this->get_option( 'landing_page' );
			}
			/*
			 * Determines where or not PayPal displays shipping address fields on the PayPal pages. For digital goods, this field is required, and you must set it to 1. It is one of the following values:
				0 ? PayPal displays the shipping address on the PayPal pages.
				1 ? PayPal does not display shipping address fields whatsoever.
				2 ? If you do not pass the shipping address, PayPal obtains it from the buyer's account profile.
			 */
			$set_express_details->NoShipping = 0;
			$set_express_request_type = new SetExpressCheckoutRequestType();
			$set_express_request_type->SetExpressCheckoutRequestDetails = $set_express_details;
			$set_express_request = new SetExpressCheckoutReq();
			$set_express_request->SetExpressCheckoutRequest = $set_express_request_type;
			$pp_service = WC_PayPal_Interface_Latam::get_static_interface_service();
			$set_express_response = $pp_service->SetExpressCheckout( $set_express_request );
			if ( in_array( $set_express_response->Ack, array( 'Success', 'SuccessWithWarning' ) ) ) {
				$token = $set_express_response->Token;
				if ( 'sandbox' === WC_PayPal_Interface_Latam::get_env() ) {
					$redirect_url = 'https://www.sandbox.paypal.com/checkoutnow?token=' . $token;
				} else {
					$redirect_url = 'https://www.paypal.com/checkoutnow?token=' . $token;
				}
				// Store values in session.
				$session = array(
					'checkout_completed' => false,
					'start_from'         => $args['start_from'],
					'order_id'           => $args['order_id'],
					'order_total'        => $details['order_total'],
					'payer_id'           => false,
					'expire_in'          => time() + 10800,
					'set_express_token'  => $token,
					'set_express_url'    => $redirect_url,
					'do_express_token'   => false,
				);
				WC_Paypal_Express_MX::woocommerce_instance()->session->set( 'paypal_latam', $session );
				if ( $args['return_url'] ) {
					return $redirect_url;
				}
				if ( $args['return_token'] ) {
					return $token;
				}
				wp_safe_redirect( $redirect_url );
				exit;
			} else {
				throw new Exception( print_r( $set_express_response, true ) );
			}
			exit;
		} catch ( Exception $e ) {
			WC_Paypal_Express_MX::woocommerce_instance()->session->set( 'paypal_latam', array() );
			WC_Paypal_Logger::obj()->warning( 'Error on start_checkout: ' . $e->getMessage() );
			WC_Paypal_Logger::obj()->warning( 'DATA for start_checkout: ' . print_r( $set_express_request, true ) );
			if ( true === $args['return_url'] || true === $args['return_token'] ) {
				return false;
			}
			ob_end_clean();
			?>
			<script type="text/javascript">
				window.location.assign( "<?php echo $cart_url; ?>" );
			</script>
			<?php
			exit;
		}// End try().
	}
	public function get_checkout( $token ) {
		$request = new GetExpressCheckoutDetailsReq();
		$request->GetExpressCheckoutDetailsRequest = new GetExpressCheckoutDetailsRequestType( $token );
		$pp_service = WC_PayPal_Interface_Latam::get_static_interface_service();
		try {
			/* wrap API method calls on the service object with a try catch */
			$response = $pp_service->GetExpressCheckoutDetails( $request );
			if ( in_array( $response->Ack, array( 'Success', 'SuccessWithWarning' ) ) ) {
				WC_Paypal_Logger::obj()->debug( 'Result on get_checkout: ' . print_r( $response, true ) );
				WC_Paypal_Logger::obj()->debug( 'DATA for get_checkout: ' . print_r( $request, true ) );
				return $response;
			} else {
				throw new Exception( print_r( $response, true ) );
			}
		} catch ( Exception $e ) {
			WC_Paypal_Logger::obj()->warning( 'Error on get_checkout: ' . $e->getMessage() );
			WC_Paypal_Logger::obj()->warning( 'DATA for get_checkout: ' . print_r( $request, true ) );
			return false;
		}
	}
	public function do_checkout( $order_id, $payer_id, $token, $custom = false, $invoice = false ) {
		$details = $this->_get_details_from_order( $order_id );
		$notify_url = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'wc_gateway_ipn_paypal_latam', home_url( '/' ) ) );
		$order_total = new BasicAmountType();
		$order_total->currencyID = get_woocommerce_currency();
		$order_total->value = $details['order_total'];
		$payment = new PaymentDetailsType();
		$payment->OrderTotal = $order_total;
		$payment->NotifyURL  = $notify_url;
		if ( false !== $custom ) {
			$payment->Custom  = $custom;
		}
		if ( false !== $invoice ) {
			$payment->InvoiceID  = $invoice;
		}
		$request_type = new DoExpressCheckoutPaymentRequestDetailsType();
		$request_type->PayerID = $payer_id;
		$request_type->Token = $token;
		$request_type->PaymentAction = $this->get_option('paymentaction');
		$request_type->PaymentDetails[0] = $payment;
		$request_details = new DoExpressCheckoutPaymentRequestType();
		$request_details->DoExpressCheckoutPaymentRequestDetails = $request_type;
		$request = new DoExpressCheckoutPaymentReq();
		$request->DoExpressCheckoutPaymentRequest = $request_details;
		$pp_service = WC_PayPal_Interface_Latam::get_static_interface_service();
		try {
			/* wrap API method calls on the service object with a try catch */
			$response = $pp_service->DoExpressCheckoutPayment( $request );
			if ( in_array( $response->Ack, array( 'Success', 'SuccessWithWarning' ) ) ) {
				WC_Paypal_Logger::obj()->debug( 'Result on do_checkout: ' . print_r( $response, true ) );
				WC_Paypal_Logger::obj()->debug( 'DATA for do_checkout: ' . print_r( $request, true ) );
				return $response;
			} else {
				throw new Exception( print_r( $response, true ) );
			}
		} catch ( Exception $e ) {
			WC_Paypal_Logger::obj()->warning( 'Error on do_checkout: ' . $e->getMessage() );
			WC_Paypal_Logger::obj()->warning( 'DATA for do_checkout: ' . print_r( $request, true ) );
			return false;
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
		$description = sprintf( _x( 'Orders with %s', 'data sent to PayPal', 'woocommerce-subscriptions' ), get_bloginfo( 'name' ) );

		if ( strlen( $description ) > 127 ) {
			$description = substr( $description, 0, 124 ) . '...';
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
	public function get_details_from_cart() {
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
			$shipping_phone      = $old_wc ? $order->billing_phone       : $order->get_billing_phone();
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
			$shipping_phone      = $old_wc ? $order->billing_phone      : $order->get_billing_phone();
		}

		$shipping_address['name']     = $shipping_first_name . ' ' . $shipping_last_name;
		$shipping_address['address1'] = $shipping_address_1;
		$shipping_address['address2'] = $shipping_address_2;
		$shipping_address['city']     = $shipping_city;
		$shipping_address['state']    = $shipping_state;
		$shipping_address['zip']      = $shipping_postcode;
		$shipping_address['phone']    = $shipping_phone;

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
	/**
	 * Map PayPal shipping address to WC shipping address.
	 *
	 * @param  object $checkout_details Checkout details
	 * @return array
	 */
	public function get_mapped_shipping_address( $get_checkout ) {
		if ( empty( $get_checkout->GetExpressCheckoutDetailsResponseDetails->PaymentDetails[0] ) || empty( $get_checkout->GetExpressCheckoutDetailsResponseDetails->PaymentDetails[0]->ShipToAddress ) ) {
			return array();
		}
		$address = $get_checkout->GetExpressCheckoutDetailsResponseDetails->PaymentDetails[0]->ShipToAddress;
		$name       = explode( ' ', $address->Name );
		$first_name = array_shift( $name );
		$last_name  = implode( ' ', $name );
		return array(
			'first_name'    => $first_name,
			'last_name'     => $last_name,
			//'company'       => $address->,
			'address_1'     => $address->Street1,
			'address_2'     => $address->Street2,
			'city'          => $address->CityName,
			'state'         => $address->StateOrProvince,
			'postcode'      => $address->PostalCode,
			'country'       => $address->Country,
		);
	}

	/**
	 * Map PayPal billing address to WC shipping address
	 * NOTE: Not all PayPal_Checkout_Payer_Details objects include a billing address
	 * @param  object $checkout_details
	 * @return array
	 */
	public function get_mapped_billing_address( $get_checkout ) {
		if ( false === $get_checkout || empty( $get_checkout->GetExpressCheckoutDetailsResponseDetails->PayerInfo ) ) {
			return array();
		}
		$pp_payer = $get_checkout->GetExpressCheckoutDetailsResponseDetails->PayerInfo;
		if ( $pp_payer->Address ) {
			return array(
				'first_name' => trim( $pp_payer->PayerName->FirstName . ' ' . $pp_payer->PayerName->MiddleName ),
				'last_name'  => $pp_payer->PayerName->LastName,
				'company'    => '',
				'address_1'  => $pp_payer->Address->Street1,
				'address_2'  => $pp_payer->Address->Street2,
				'city'       => $pp_payer->Address->CityName,
				'state'      => $pp_payer->Address->StateOrProvince,
				'postcode'   => $pp_payer->Address->PostalCode,
				'country'    => $pp_payer->Address->Country,
				'phone'      => ! empty( $pp_payer->Address->Phone ) ? $pp_payer->Address->Phone : $pp_payer->ContactPhone,
				'email'      => $pp_payer->Payer,
			);
		} else {
			return array(
				'first_name' => trim( $pp_payer->PayerName->FirstName . ' ' . $pp_payer->PayerName->MiddleName ),
				'last_name'  => $pp_payer->PayerName->LastName,
				'company'    => '',
				'address_1'  => '',
				'address_2'  => '',
				'city'       => '',
				'state'      => '',
				'postcode'   => '',
				'country'    => '',
				'phone'      => $pp_payer->ContactPhone,
				'email'      => $pp_payer->Payer,
			);

		}
	}

}

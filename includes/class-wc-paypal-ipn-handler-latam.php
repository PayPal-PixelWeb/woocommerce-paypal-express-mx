<?php

use PayPal\IPN\PPIPNMessage;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * PayPal API Interface
 */
class WC_PayPal_IPN_Handler_Latam {
	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	private static $instance = null;
	private $ipn_interface = null;
	private $ipn_data = null;
	/**
	 * Initialize the plugin.
	 */
	private function __construct() {
		$this->settings = (array) get_option( 'woocommerce_ppexpress_mx_settings', array() );  // Array containing configuration parameters. (not required if config file is used)
		$config = array(
			// values: 'sandbox' for testing
			// 'live' for production
			'mode' => WC_PayPal_Interface_Latam::get_env(),
			// These values are defaulted in SDK. If you want to override default values, uncomment it and add your value.
			// "http.ConnectionTimeOut" => "5000",
			// "http.Retry" => "2",
		);
		$this->ipn_interface = new PPIPNMessage( null, $config );
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
	public function check_ipn() {
		WC_Paypal_Logger::obj()->debug( 'Check IPN: _POST=>' . print_r( $_POST, true ) . ' _GET=>' . print_r( $_GET, true ) . ' php://input=>' . file_get_contents( 'php://input' ) );
		if ( true === $this->ipn_interface->validate() ) {
			$this->ipn_data = $this->ipn_interface->getRawData();
			WC_Paypal_Logger::obj()->debug( 'IPN is Valid. DATA: ' . json_encode( $this->ipn_data ) );
			// Lowercase returned variables.
			$this->ipn_data['payment_status'] = strtolower( $this->ipn_data['payment_status'] );
			// Sandbox fix.
			if ( ( empty( $posted_data['pending_reason'] ) || 'authorization' !== $posted_data['pending_reason'] ) && isset( $posted_data['test_ipn'] ) && 1 == $posted_data['test_ipn'] && 'pending' == $posted_data['payment_status'] ) {
				$this->ipn_data['payment_status'] = 'completed';
			}
			return $this->ipn_data;
		}
		WC_Paypal_Logger::obj()->warning( 'Invalid IPN Request: _POST=>' . print_r( $_POST, true ) . ' _GET=>' . print_r( $_GET, true ) . ' php://input=>' . file_get_contents( 'php://input' ) );
		return false;
	}
	/**
	 * Check for a valid transaction type.
	 *
	 * @param string $txn_type Transaction type
	 */
	private function validate_transaction_type( $txn_type ) {
		$accepted_types = array( 'cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money' );
		if ( ! in_array( strtolower( $txn_type ), $accepted_types ) ) {
			WC_Paypal_Logger::obj()->warning( 'Aborting, Invalid type:' . $txn_type );
			exit;
		}
	}
	/**
	 * Check currency from IPN matches the order.
	 *
	 * @param WC_Order $order Order object
	 * @param string   $currency Currency
	 */
	private function validate_currency( $order, $currency ) {
		$old_wc = version_compare( WC_VERSION, '3.0', '<' );
		$order_currency = $old_wc ? $order->order_currency : $order->get_currency();

		if ( $order_currency !== $currency ) {
			WC_Paypal_Logger::obj()->warning( 'Payment error: Currencies do not match (sent "' . $order_currency . '" | returned "' . $currency . '")' );
			// Put this order on-hold for manual checking.
			$order->update_status( 'on-hold', sprintf( __( 'Validation error: PayPal currencies do not match (code %s).', 'woocommerce-paypal-express-mx' ), $currency ) );
			exit;
		}
	}
	/**
	 * Hold order and add note.
	 *
	 * @param  WC_Order $order  Order object
	 * @param  string   $reason On-hold reason
	 */
	private function payment_on_hold( $order, $reason = '' ) {
		$order->update_status( 'on-hold', $reason );
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			if ( ! get_post_meta( $order->id, '_order_stock_reduced', true ) ) {
				$order->reduce_order_stock();
			}
		} else {
			wc_maybe_reduce_stock_levels( $order->get_id() );
		}
	}
	/**
	 * Check payment amount from IPN matches the order.
	 *
	 * @param WC_Order $order Order object
	 * @param int      $amount Amount
	 */
	private function validate_amount( $order, $amount ) {
		if ( number_format( $order->get_total(), 2, '.', '' ) != number_format( $amount, 2, '.', '' ) ) {
			WC_Paypal_Logger::obj()->warning( 'Payment error: Amounts do not match (gross ' . $amount . ')' );
			// Put this order on-hold for manual checking.
			$order->update_status( 'on-hold', sprintf( __( 'Validation error: PayPal amounts do not match (gross %s).', 'woocommerce-paypal-express-mx' ), $amount ) );
			exit;
		}
	}
	/**
	 * Send a notification to the user handling orders.
	 *
	 * @param string $subject Email subject
	 * @param string $message Email message
	 */
	private function send_ipn_email_notification( $subject, $message ) {
		$new_order_settings = get_option( 'woocommerce_new_order_settings', array() );
		$mailer             = PPWC()->mailer();
		$message            = $mailer->wrap_message( $subject, $message );
		$mailer->send( ! empty( $new_order_settings['recipient'] ) ? $new_order_settings['recipient'] : get_option( 'admin_email' ), strip_tags( $subject ), $message );
	}
	public function process_data() {
		if ( null === $this->ipn_data && false === $this->check_ipn() ) {
			return false;
		}
		$json_order = json_decode( $this->ipn_data['custom'], true );
		if ( ! isset( $json_order['order_id'] ) ) {
			return false;
		}
		$old_wc = version_compare( WC_VERSION, '3.0', '<' );
		$order_id = $json_order['order_id'];
		$order = new WC_Order( $order_id );
		if ( $order ) {
			$order_key_from_order = $old_wc ? $order->order_key : $order->get_order_key();
		} else {
			$order_key_from_order = '';
		}
		if ( $order_key_from_order !== $json_order['order_key'] ) {
			WC_Paypal_Logger::obj()->warning( 'Error: Order Keys do not match.' );
			exit;
		}
		$check_metadata = array( 'mc_fee', 'txn_id', 'parent_txn_id', 'pending_reason', 'payment_date', 'payer_status', 'address_status', 'protection_eligibility', 'payment_type', 'first_name', 'last_name', 'payer_email' );
		foreach ( $check_metadata as $meta_key ) {
			if ( isset( $this->ipn_data[ $meta_key ] ) && ! empty( $this->ipn_data[ $meta_key ] ) ) {
				$meta_value = wc_clean( $this->ipn_data[ $meta_key ] );
				WC_Paypal_Express_MX_Gateway::set_metadata( $order_id, 'ipn_' . $meta_key, $meta_value );
			}
		}
		$this->validate_currency( $order, $this->ipn_data['mc_currency'] );
		switch ( $this->ipn_data['payment_status'] ) {
			case 'completed':
				$this->validate_transaction_type( $this->ipn_data['txn_type'] );
				$this->validate_amount( $order, $this->ipn_data['mc_gross'] );
				if ( $order->has_status( array( 'processing', 'completed' ) ) ) {
					WC_Paypal_Logger::obj()->info( 'Aborting, Order #' . $order_id . ' is already complete.' );
					exit;
				}
				WC_Paypal_Express_MX_Gateway::set_metadata( $order_id, 'is_auth_order', false );
				WC_Paypal_Express_MX_Gateway::set_metadata( $order_id, 'is_refunded', false );
				$order->add_order_note( __( 'IPN payment completed', 'woocommerce-paypal-express-mx' ) );
				$order->payment_complete( ! empty( $this->ipn_data['txn_id'] ) ? wc_clean( $this->ipn_data['txn_id'] ) : '' );
				break;
			case 'pending':
				$this->validate_transaction_type( $this->ipn_data['txn_type'] );
				$this->validate_amount( $order, $this->ipn_data['mc_gross'] );
				if ( 'authorization' === $this->ipn_data['pending_reason'] ) {
					WC_Paypal_Express_MX_Gateway::set_metadata( $order_id, 'is_auth_order', true );
					$this->payment_on_hold( $order, __( 'Payment authorized. Change payment status to processing or complete to capture funds.', 'woocommerce-paypal-express-mx' ) );
				} else {
					$this->payment_on_hold( $order, sprintf( __( 'Payment pending (%s).', 'woocommerce-paypal-express-mx' ), $this->ipn_data['pending_reason'] ) );
				}
				break;
			case 'failed':
			case 'denied':
			case 'expired':
			case 'voided':
				WC_Paypal_Express_MX_Gateway::set_metadata( $order_id, 'is_auth_order', false );
				WC_Paypal_Express_MX_Gateway::set_metadata( $order_id, 'is_refunded', true );
				$order->update_status( 'failed', sprintf( __( 'Payment %s via IPN.', 'woocommerce-gateway-paypal-express-checkout' ), wc_clean( $this->ipn_data['payment_status'] ) ) );
				break;
			case 'refunded':
				if ( $order->get_total() == ( $this->ipn_data['mc_gross'] * -1 ) ) {
					WC_Paypal_Express_MX_Gateway::set_metadata( $order_id, 'is_auth_order', false );
					WC_Paypal_Express_MX_Gateway::set_metadata( $order_id, 'is_refunded', true );
					// Mark order as refunded.
					$order->update_status( 'refunded', sprintf( __( 'Payment %s via IPN.', 'woocommerce-gateway-paypal-express-checkout' ), strtolower( $this->ipn_data['payment_status'] ) ) );
					$this->send_ipn_email_notification(
						sprintf( __( 'Payment for order %s refunded', 'woocommerce-gateway-paypal-express-checkout' ), '<a class="link" href="' . esc_url( admin_url( 'post.php?post=' . $order_id . '&action=edit' ) ) . '">' . $order->get_order_number() . '</a>' ),
						sprintf( __( 'Order #%1$s has been marked as refunded - PayPal reason code: %2$s', 'woocommerce-gateway-paypal-express-checkout' ), $order->get_order_number(), $this->ipn_data['reason_code'] )
					);
				}
				break;
			case 'reversed':
				$order->update_status( 'on-hold', sprintf( __( 'Payment %s via IPN.', 'woocommerce-gateway-paypal-express-checkout' ), wc_clean( $this->ipn_data['payment_status'] ) ) );
				$this->send_ipn_email_notification(
					sprintf( __( 'Payment for order %s reversed', 'woocommerce-gateway-paypal-express-checkout' ), '<a class="link" href="' . esc_url( admin_url( 'post.php?post=' . $order_id . '&action=edit' ) ) . '">' . $order->get_order_number() . '</a>' ),
					sprintf( __( 'Order #%1$s has been marked on-hold due to a reversal - PayPal reason code: %2$s', 'woocommerce-gateway-paypal-express-checkout' ), $order->get_order_number(), wc_clean( $this->ipn_data['reason_code'] ) )
				);
				break;
			case 'canceled_reversal':
				$this->send_ipn_email_notification(
					sprintf( __( 'Reversal cancelled for order #%s', 'woocommerce-gateway-paypal-express-checkout' ), $order->get_order_number() ),
					sprintf( __( 'Order #%1$s has had a reversal cancelled. Please check the status of payment and update the order status accordingly here: %2$s', 'woocommerce-gateway-paypal-express-checkout' ), $order->get_order_number(), esc_url( admin_url( 'post.php?post=' . $order_id . '&action=edit' ) ) )
				);
				break;
		}// End switch().
		return true;
	}
}

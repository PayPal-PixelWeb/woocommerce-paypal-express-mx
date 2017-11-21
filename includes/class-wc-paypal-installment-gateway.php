<?php
/**
 * IPS Handler for WooCommerce Plugin.
 *
 * @package   WooCommerce -> Paypal Express Checkout MX
 * @author    Kijam Lopez <info@kijam.com>
 * @license   Apache-2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Paypal_Installment_Gateway' ) ) :
	/**
	 * WC_Paypal_Installment_Gateway Class.
	 *
	 * @since 1.0.0
	 */
	class WC_Paypal_Installment_Gateway extends WC_Payment_Gateway {
		/**
		 * Payment Gateway Instance.
		 *
		 * @var object
		 *
		 * @since 1.0.0
		 */
		private $pp = null;
		/**
		 * Constructor for the gateway.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->id = 'ppexpress_installment_mx';
			add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
			$this->enabled = true;
			$this->title = __( 'Paypal Installment', 'woocommerce-paypal-express-mx' );
			$this->pp = WC_Paypal_Express_MX_Gateway::obj();
		}

		/**
		 * Proxy for process_payment.
		 *
		 * @param int $order_id Order ID.
		 *
		 * @return array Redirect.
		 *
		 * @since 1.0.0
		 */
		public function process_payment( $order_id ) {
			return $this->pp->process_payment( $order_id );
		}

		/**
		 * Output for the order received page.
		 *
		 * @param object $order WC_Order..
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function receipt_page( $order ) {
			$this->pp->receipt_page( $order );
		}
	}
endif;

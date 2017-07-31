<?php
if ( ! class_exists( 'WC_Paypal_Installment_Gateway' ) ) :
	/**
	 * WC_Paypal_Installment_Gateway Class.
	 */
	class WC_Paypal_Installment_Gateway extends WC_Payment_Gateway {
		private $pp = null;
		public function __construct() {
			$this->id = 'ppexpress_installment_mx';
			add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
			$this->enabled = true;
			$this->title = __( 'Paypal Installment', 'woocommerce-paypal-express-mx' );
			$this->pp = WC_Paypal_Express_MX_Gateway::obj();
		}
		public function process_payment( $order_id ) {
			return $this->pp->process_payment( $order_id );
		}
		public function receipt_page( $order ) {
			$this->pp->receipt_page( $order );
		}
	}
endif;

<?php
/**
 * Plugin Gateway Class.
 */
include_once( dirname( __FILE__ ) . '/override/class-wc-override-payment-gateway.php' );
include_once( dirname( __FILE__ ) . '/class-wc-paypal-interface-latam.php' );
include_once( dirname( __FILE__ ) . '/class-wc-paypal-logos.php' );
if ( ! class_exists( 'WC_Paypal_Express_MX_Gateway' ) ) :
	/**
	 * WC_Paypal_Express_MX_Gateway Class.
	 */
	class WC_Paypal_Express_MX_Gateway extends WC_Payment_Gateway_Paypal {
		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		static private $instance;
		static private $cache_metadata  = array();
		private $cart_handler = null;
		/**
		 * Constructor for the gateway.
		 *
		 * @return void
		 */
		public function __construct() {
			$this->id              = 'ppexpress_mx';
			$this->icon            = apply_filters( 'woocommerce_ppexpress_mx_icon', WC_PayPal_Logos::get_logo() );
			$this->has_fields      = false;
			$this->title           = $this->get_option( 'title' );
			$this->description     = $this->get_option( 'description' );
			$this->method_title    = __( 'PayPal Express Checkout MX', 'woocommerce-paypal-express-mx' );
			$this->checkout_mode          = $this->get_option( 'checkout_mode', 'redirect' );
			if ( ! class_exists( 'WC_PayPal_Cart_Handler_Latam' ) ) {
				include_once( dirname( __FILE__ ) . '/class-wc-paypal-cart-handler-latam.php' );
			}
			$this->cart_handler = WC_PayPal_Cart_Handler_Latam::obj();
			add_action( 'admin_enqueue_scripts', array( $this, 'pplatam_script_enqueue' ) );
			add_action( 'after_setup_theme', array( $this, 'ppexpress_mx_image_sizes' ) );
			add_filter( 'image_size_names_choose', array( $this, 'ppexpress_mx_sizes' ) );
			add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'order_processed' ) );
			add_action( 'template_redirect', array( $this, 'verify_checkout' ) );
			add_action( 'template_redirect', array( $this, 'maybe_return_from_paypal' ) );
			add_action( 'woocommerce_available_payment_gateways', array( $this, 'add_installment_payment' ) );
			add_action( 'woocommerce_api_wc_gateway_ipn_paypal_latam', array( $this, 'check_ipn_response' ) );
			add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'thankyou_text' ), 10, 2 );
			add_action( 'woocommerce_ppexpress_mx_metabox', array( $this, 'show_metabox' ) );
			$this->check_nonce();
			$this->init_form_fields();
			$this->init_settings();
			self::$instance = $this;
			$this->debug = $this->get_option( 'debug' );
			if ( 'yes' !== $this->debug ) {
				WC_Paypal_Logger::set_level( WC_Paypal_Logger::SILENT );
			}
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			if ( is_user_logged_in() && is_admin() && isset( $_GET['section'] ) && $_GET['section'] === $this->id ) {
				if ( empty( $_POST ) ) { // @codingStandardsIgnoreLine
					WC_PayPal_Interface_Latam::obj()->validate_active_credentials( true, false );
				}
			}
			$this->logos = WC_PayPal_Logos::obj();
		}
		// define the woocommerce_thankyou_order_received_text callback
		function thankyou_text( $var, $order ) {
			$old_wc    = version_compare( WC_VERSION, '3.0', '<' );
			$order_id  = $old_wc ? $order->id : $order->get_id();
			$transaction_id = $this->get_metadata( $order_id, 'transaction_id' );
			if ( false !== $transaction_id && strlen( $transaction_id ) > 0 ) {
				return '<center><img width="130" src="' . plugins_url( '../img/pp-success.svg', __FILE__ ) . '" /><br /><b>' . __( 'Thank you. Your order has been received.', 'woocommerce-paypal-express-mx' ) . '<br />' . __( 'You Transaction ID is', 'woocommerce-paypal-express-mx' ) . ': ' . $transaction_id . '</b></center>';
			}
			return $var;
		}
		function pplatam_script_enqueue( $hook ) {
			if ( 'woocommerce_page_wc-settings' !== $hook ) {
				return;
			}
			wp_enqueue_media();
			wp_enqueue_script( 'pplatam_script', plugins_url( '../js/admin.js' , __FILE__ ), array( 'jquery' ), WC_Paypal_Express_MX::VERSION );
			wp_add_inline_script( 'pplatam_script', '
				var ppexpress_lang_remove = "' . __( 'Remove Image', 'woocommerce-paypal-express-mx' ) . '";
			' );
		}
		function ppexpress_mx_image_sizes() {
			add_theme_support( 'post-thumbnails' );
			add_image_size( 'pplogo', 190, 60, true );
			add_image_size( 'ppheader', 750, 90, true );
		}
		function ppexpress_mx_sizes( $sizes ) {
			$my_sizes = array(
				'pplogo' => __( 'Image Size for Logo on Paypal', 'woocommerce-paypal-express-mx' ),
				'ppheader' => __( 'Image Size for Header on Paypal', 'woocommerce-paypal-express-mx' ),
			);
			return array_merge( $sizes, $my_sizes );
		}
		/**
		 * Check if is available.
		 *
		 * @return bool
		 */
		public function is_available() {
			return true == $this->is_configured() && 'yes' === $this->get_option( 'payment_checkout_enabled' );
		}
		/**
		 * Check if all is ok.
		 *
		 * @return bool
		 */
		public function is_configured() {
			return isset( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'] && false !== WC_PayPal_Interface_Latam::get_static_interface_service();
		}
		/**
		 * Check nonce's
		 */
		private static function check_key_nonce( $key ) {
			return isset( $_GET[ $key ] ) // Input var okay.
				&& wp_verify_nonce( sanitize_key( $_GET[ $key ] ), $key );
		}
		/**
		 * Checks data is correctly set when returning from PayPal Express Checkout
		 */
		public function maybe_return_from_paypal() {
			if ( isset( $_GET['wc-gateway-ppexpress-mx-clear-session'] ) ) {
				PPWC()->session->set( 'paypal_mx', array() );
				wp_safe_redirect( wc_get_page_permalink( 'cart' ) );
				exit;
			}
			if ( empty( $_GET['ppexpress-mx-return'] ) || empty( $_GET['token'] ) || empty( $_GET['PayerID'] ) ) {
				return;
			}
			$token                    = $_GET['token'];
			$payer_id                 = $_GET['PayerID'];
			$create_billing_agreement = ! empty( $_GET['create-billing-agreement'] );
			$session                  = PPWC()->session->get( 'paypal_mx', array() );

			if ( empty( $session ) || ! isset( $session['expire_in'] ) || $session['expire_in'] < time() ) {
				wc_add_notice( __( 'Your PayPal checkout session has expired. Please check out again.', 'woocommerce-paypal-express-mx' ), 'error' );
				return;
			}

			// Store values in session.
			$session['checkout_completed'] = true;
			$session['get_express_token']  = $token;
			$session['payer_id']           = $payer_id;
			PPWC()->session->set( 'paypal_mx', $session );
			try {
				// If commit was true, take payment right now
				if ( 'checkout' === $session['start_from'] ) {
					$get_checkout = $this->cart_handler->get_checkout( $token );
					if ( false !== $get_checkout ) {
						$order_data = json_decode( $get_checkout->GetExpressCheckoutDetailsResponseDetails->Custom, true );
						if ( $order_data['order_id'] != $session['order_id'] ) {
							wc_add_notice( __( 'Sorry, you order is invalid.', 'woocommerce-paypal-express-mx' ), 'error' );
							wp_safe_redirect( wc_get_page_permalink( 'cart' ) );
							exit;
						}
						$order = wc_get_order( $order_data['order_id'] );
						if ( 'yes' === $this->get_option( 'require_confirmed_address' ) ) {
							$address = $this->cart_handler->get_mapped_shipping_address( $get_checkout );
							$order->set_address( $address, 'shipping' );
						}
						$pp_payer = $get_checkout->GetExpressCheckoutDetailsResponseDetails->PayerInfo;
						$this->set_metadata( $order_data['order_id'], 'payer_email', $pp_payer->Payer );
						$this->set_metadata( $order_data['order_id'], 'payer_status', $pp_payer->PayerStatus );
						$this->set_metadata( $order_data['order_id'], 'payer_country', $pp_payer->PayerCountry );
						$this->set_metadata( $order_data['order_id'], 'payer_business', $pp_payer->PayerBusiness );
						$this->set_metadata( $order_data['order_id'], 'payer_name', implode( ' ', array( $pp_payer->PayerName->FirstName, $pp_payer->PayerName->MiddleName, $pp_payer->PayerName->LastName ) ) );
						$this->set_metadata( $order_data['order_id'], 'get_express_token', $token );
						$this->set_metadata( $order_data['order_id'], 'set_express_token', $session['set_express_token'] );
						$this->set_metadata( $order_data['order_id'], 'environment', WC_PayPal_Interface_Latam::get_env() );
						$this->set_metadata( $order_data['order_id'], 'payer_id', $payer_id );

						// Complete the payment now.
						$do_checkout = $this->cart_handler->do_checkout( $order_data['order_id'], $payer_id, $token );
						if ( false !== $do_checkout && isset( $do_checkout->DoExpressCheckoutPaymentResponseDetails->PaymentInfo ) ) {
							$this->set_metadata( $order_data['order_id'], 'transaction_id', (string) $do_checkout->DoExpressCheckoutPaymentResponseDetails->PaymentInfo[0]->TransactionID );
							// Clear Cart
							PPWC()->cart->empty_cart();
							// Redirect
							wp_redirect( $order->get_checkout_order_received_url() );
							exit;
						} else {
							wc_add_notice( __( 'Sorry, an error occurred while trying to retrieve your information from PayPal. Please try again.', 'woocommerce-paypal-express-mx' ), 'error' );
							wp_safe_redirect( wc_get_page_permalink( 'cart' ) );
							exit;
						}
					} else {
						wc_add_notice( __( 'Sorry, an error occurred while trying to retrieve your information from PayPal. Please try again.', 'woocommerce-paypal-express-mx' ), 'error' );
						wp_safe_redirect( wc_get_page_permalink( 'cart' ) );
						exit;
					}// End if().
				}// End if().
			} catch ( Exception $e ) {
				wc_add_notice( __( 'Sorry, an error occurred while trying to retrieve your information from PayPal. Please try again.', 'woocommerce-paypal-express-mx' ), 'error' );
				wp_safe_redirect( wc_get_page_permalink( 'cart' ) );
				exit;
			}// End try().
		}
		public function add_installment_payment( $gateways ) {
			if ( isset( $gateways['ppexpress_mx'] ) && 'yes' === $this->get_option( 'show_installment_gateway' ) ) {
				if ( isset( $_GET['ppexpress-mx-return'] ) || isset( $_GET['tab'] ) && 'checkout' === $_GET['tab'] ) {
					unset( $gateways['ppexpress_installment_mx'] );
					return $gateways;
				}
				if ( ! class_exists( 'WC_Paypal_Installment_Gateway' ) ) {
					include_once( dirname( __FILE__ ) . '/class-wc-paypal-installment-gateway.php' );
				}
				if ( ! isset( $gateways['ppexpress_installment_mx'] ) ) {
					$gateways['ppexpress_installment_mx'] = new WC_Paypal_Installment_Gateway();
				}
				$gateways['ppexpress_installment_mx']->icon = $this->icon;
				$gateways['ppexpress_installment_mx']->title = $this->get_option( 'title_installment' );
				$gateways['ppexpress_installment_mx']->description = $this->get_option( 'description_installment' );
			} elseif ( isset( $gateways['ppexpress_installment_mx'] ) ) {
				unset( $gateways['ppexpress_installment_mx'] );
			}
			return $gateways;
		}
		public function check_ipn_response() {
			if ( ! class_exists( 'WC_PayPal_IPN_Handler_Latam' ) ) {
				include_once( dirname( __FILE__ ) . '/class-wc-paypal-ipn-handler-latam.php' );
			}
			$handler = WC_PayPal_IPN_Handler_Latam::obj();
			$handler->process_data();
		}
		public function verify_checkout() {
			// If there then call start_checkout() else do nothing so page loads as normal.
			if ( ! empty( $_GET['ppexpress_mx'] ) && 'true' === $_GET['ppexpress_mx'] ) {
				// Trying to prevent auto running checkout when back button is pressed from PayPal page.
				$_GET['ppexpress_mx'] = 'false';
				PPWC()->session->set( 'paypal_mx', array() );
				$this->cart_handler->start_checkout( array(
					'start_from' => 'cart',
				) );
				exit;
			}
		}
		public function check_nonce() {
			if ( isset( $_GET['wc_ppexpress_mx_ips_admin_nonce'] ) ) { // Input var okay.
				include_once( dirname( __FILE__ ) . '/class-wc-paypal-connect-ips.php' );
				$ips = new WC_PayPal_Connect_IPS();
				$ips->maybe_received_credentials();
			}
			if ( true === self::check_key_nonce( 'wc_ppexpress_mx_remove_cert' ) ) {
				@unlink( dirname( __FILE__ ) . '/cert/live_key_data.pem' ); // @codingStandardsIgnoreLine
				$settings_array = (array) get_option( 'woocommerce_ppexpress_mx_settings', array() );
				$settings_array['api_certificate'] = '';
				$settings_array['api_signature']   = '';
				update_option( 'woocommerce_ppexpress_mx_settings', $settings_array );
				wp_safe_redirect( WC_Paypal_Express_MX::get_admin_link() );
				exit;
			}
			if ( true === self::check_key_nonce( 'wc_ppexpress_mx_remove_sandbox_cert' ) ) {
				@unlink( dirname( __FILE__ ) . '/cert/sandbox_key_data.pem' ); // @codingStandardsIgnoreLine
				$settings_array = (array) get_option( 'woocommerce_ppexpress_mx_settings', array() );
				$settings_array['sandbox_api_certificate'] = '';
				$settings_array['sandbox_api_signature']   = '';
				update_option( 'woocommerce_ppexpress_mx_settings', $settings_array );
				wp_safe_redirect( WC_Paypal_Express_MX::get_admin_link() );
				exit;
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
		 * Do some additonal validation before saving options.
		 */
		public function process_admin_options() {
			// @codingStandardsIgnoreStart
			// If a certificate has been uploaded, read the contents and save that string instead.
			if ( array_key_exists( 'woocommerce_ppexpress_mx_api_certificate', $_FILES )
			&& array_key_exists( 'tmp_name', $_FILES['woocommerce_ppexpress_mx_api_certificate'] )
			&& array_key_exists( 'size', $_FILES['woocommerce_ppexpress_mx_api_certificate'] )
			&& $_FILES['woocommerce_ppexpress_mx_api_certificate']['size'] ) {
				@unlink( dirname( __FILE__ ) . '/cert/key_data.pem' );
				$_POST['woocommerce_ppexpress_mx_api_certificate'] = base64_encode( file_get_contents( $_FILES['woocommerce_ppexpress_mx_api_certificate']['tmp_name'] ) );
				unlink( $_FILES['woocommerce_ppexpress_mx_api_certificate']['tmp_name'] );
				unset( $_FILES['woocommerce_ppexpress_mx_api_certificate'] );
			} else {
				$_POST['woocommerce_ppexpress_mx_api_certificate'] = $this->get_option( 'api_certificate' );
			}
			// If a sandbox certificate has been uploaded, read the contents and save that string instead.
			if ( array_key_exists( 'woocommerce_ppexpress_mx_sandbox_api_certificate', $_FILES )
			&& array_key_exists( 'tmp_name', $_FILES['woocommerce_ppexpress_mx_sandbox_api_certificate'] )
			&& array_key_exists( 'size', $_FILES['woocommerce_ppexpress_mx_sandbox_api_certificate'] )
			&& $_FILES['woocommerce_ppexpress_mx_sandbox_api_certificate']['size'] ) {
				@unlink( dirname( __FILE__ ) . '/cert/key_data.pem' );
				$_POST['woocommerce_ppexpress_mx_sandbox_api_certificate'] = base64_encode( file_get_contents( $_FILES['woocommerce_ppexpress_mx_sandbox_api_certificate']['tmp_name'] ) );
				unlink( $_FILES['woocommerce_ppexpress_mx_sandbox_api_certificate']['tmp_name'] );
				unset( $_FILES['woocommerce_ppexpress_mx_sandbox_api_certificate'] );
			} else {
				$_POST['woocommerce_ppexpress_mx_sandbox_api_certificate'] = $this->get_option( 'sandbox_api_certificate' );
			}
			// @codingStandardsIgnoreEnd

			parent::process_admin_options();
			$this->settings = (array) get_option( 'woocommerce_ppexpress_mx_settings', array() );
			// Validate credentials.
			WC_PayPal_Interface_Latam::obj()->validate_active_credentials( true, true, $this->get_option( 'environment' ) );
		}
		/**
		 * Initialise Gateway Settings Form Fields.
		 *
		 * @return void
		 */
		public function init_form_fields() {
			include_once( dirname( __FILE__ ) . '/class-wc-paypal-connect-ips.php' );
			$ips = new WC_PayPal_Connect_IPS();
			$sandbox_api_creds_text = $api_creds_text = __( 'Your country store not is supported by PayPal, please change this...', 'woocommerce-paypal-express-mx' );
			if ( $ips->is_supported() ) {
				$live_url = $ips->get_signup_url( 'live' );
				$sandbox_url = $ips->get_signup_url( 'sandbox' );

				$api_creds_text         = '<a href="' . esc_url( $live_url ) . '" class="button button-primary">' . __( 'Setup or link an existing PayPal account', 'woocommerce-paypal-express-mx' ) . '</a>';
				$sandbox_api_creds_text = '<a href="' . esc_url( $sandbox_url ) . '" class="button button-primary">' . __( 'Setup or link an existing PayPal Sandbox account', 'woocommerce-paypal-express-mx' ) . '</a>';

				$api_creds_text .= ' <span id="ppexpress_display">' . __( 'or <a href="#woocommerce_ppexpress_mx_api_username" class="ppexpress_mx-toggle-settings">click here to toggle manual API credential input</a>.', 'woocommerce-paypal-express-mx' ) . '</span>';
				$sandbox_api_creds_text .= ' <span id="ppexpress_display_sandbox">' . __( 'or <a href="#woocommerce_ppexpress_mx_sandbox_api_username" class="ppexpress_mx-toggle-sandbox-settings">click here to toggle manual API credential input</a>.', 'woocommerce-paypal-express-mx' ) . '</span>';
			}
			$api_certificate = $this->get_option( 'api_certificate' );
			$api_certificate_msg = '';
			if ( ! empty( $api_certificate ) ) {
				$cert = @openssl_x509_read( base64_decode( $api_certificate ) ); // @codingStandardsIgnoreLine
				if ( false !== $cert ) {
					$cert_info   = openssl_x509_parse( $cert );
					$valid_until = $cert_info['validTo_time_t'];
					$api_certificate_msg = sprintf(
						/* translators: %1$s: is date of expire. %2$s: is URL for delete certificate.  */
						__( 'API certificate is <b>VALID</b> and exire on: <b>%1$s</b>, <a href="%2$s">click here</a> for remove', 'woocommerce-paypal-express-mx' ),
						date( 'Y-m-d', $valid_until ),
						add_query_arg(
							array(
							'wc_ppexpress_mx_remove_cert' => wp_create_nonce( 'wc_ppexpress_mx_remove_cert' ),
							),
							WC_Paypal_Express_MX::get_admin_link()
						)
					);

				} else {
					$api_certificate_msg = __( 'API certificate is <b>INVALID</b>.', 'woocommerce-paypal-express-mx' );
				}
			}
			$sandbox_api_certificate = $this->get_option( 'sandbox_api_certificate' );
			$sandbox_api_certificate_msg = '';
			if ( ! empty( $sandbox_api_certificate ) ) {
				$cert = @openssl_x509_read( base64_decode( $sandbox_api_certificate ) ); // @codingStandardsIgnoreLine
				if ( false !== $cert ) {
					$cert_info   = openssl_x509_parse( $cert );
					$valid_until = $cert_info['validTo_time_t'];
					$sandbox_api_certificate_msg = sprintf(
						/* translators: %1$s: is date of expire. %2$s: is URL for delete certificate.  */
						__( 'API certificate is <b>VALID</b> and exire on: <b>%1$s</b>, <a href="%2$s">click here</a> for remove', 'woocommerce-paypal-express-mx' ),
						date( 'Y-m-d', $valid_until ),
						add_query_arg(
							array(
							'wc_ppexpress_mx_remove_sandbox_cert' => wp_create_nonce( 'wc_ppexpress_mx_remove_sandbox_cert' ),
							),
							WC_Paypal_Express_MX::get_admin_link()
						)
					);

				} else {
					$sandbox_api_certificate_msg = __( 'API certificate is <b>INVALID</b>.', 'woocommerce-paypal-express-mx' );
				}
			}
			$currency_org = get_woocommerce_currency();
			$header_image_url = $this->get_option( 'header_image_url' );
			if ( isset( $_POST['woocommerce_ppexpress_mx_header_image_url'] ) ) {
				$header_image_url = $_POST['woocommerce_ppexpress_mx_header_image_url'];
			}
			$logo_image_url = $this->get_option( 'logo_image_url' );
			if ( isset( $_POST['woocommerce_ppexpress_mx_logo_image_url'] ) ) {
				$logo_image_url = $_POST['woocommerce_ppexpress_mx_logo_image_url'];
			}
			$this->form_fields = include( dirname( __FILE__ ) . '/setting/data-settings-payment.php' );
		}
		/**
		 * Whether PayPal credit is supported.
		 *
		 * @return bool Returns true if PayPal credit is supported
		 */
		public function is_credit_supported() {
			$base = wc_get_base_location();

			return 'US' === $base['country'];
		}
		/**
		 * Whether PayPal credit is available.
		 *
		 * @return bool Returns true if PayPal credit is available
		 */
		public function is_credit_available() {
			return true === $this->is_credit_available() && 'yes' === $this->get_option( 'credit_enabled' );
		}

		/**
		 * Process the payment and return the result.
		 *
		 * @param int $order_id Order ID.
		 *
		 * @return array           Redirect.
		 */
		public function process_payment( $order_id ) {
			$session    = PPWC()->session->get( 'paypal_mx', array() );
			$token = isset( $_GET['token'] ) ? $_GET['token'] : ( isset( $session['get_express_token'] ) ? $session['get_express_token'] : '' );
			$payer_id = isset( $_GET['PayerID'] ) ? $_GET['token'] : ( isset( $session['payer_id'] ) ? $session['payer_id'] : '');
			if ( ! empty( $token ) && ! empty( $session ) && 'cart' === $session['start_from'] ) {
				$transaction_id = $this->get_metadata( $order_id, 'transaction_id' );
				if ( ! empty( $transaction_id ) && strlen( $transaction_id ) > 0 ) {
					PPWC()->cart->empty_cart();
					$order = wc_get_order( $order_id );
					return array(
						'result'    => 'success',
						'redirect'  => $order->get_checkout_order_received_url(),
					);
				}
			}
			PPWC()->session->set( 'paypal_mx', array() );
			if ( 'redirect' == $this->checkout_mode ) {
				$url = $this->cart_handler->start_checkout( array(
					'start_from' => 'checkout',
					'order_id' => $order_id,
					'return_url' => true,
				) );
				return array(
					'result'    => 'success',
					'redirect'  => $url,
				);
			} else {
				if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1', '<' ) ) {
					$url = $this->cart_handler->start_checkout( array(
						'start_from' => 'checkout',
						'order_id' => $order_id,
						'return_url' => true,
					) );
					return array(
						'result'   => 'success',
						'redirect' => $url,
					);
				} else {
					$order = wc_get_order( $order_id );
					return array(
						'result'   => 'success',
						'redirect' => add_query_arg( 'order', method_exists( $order, 'get_id' )?$order->get_id():$order->id, add_query_arg( 'key', $order->order_key, get_permalink( woocommerce_get_page_id( 'pay' ) ) ) ),
					);
				}
			}
		}
		/**
		 * Process the payment and return the result.
		 *
		 * @param int $order_id Order ID.
		 *
		 * @return array           Redirect.
		 */
		public function order_processed( $order_id ) {
			$session    = PPWC()->session->get( 'paypal_mx', array() );
			$token = isset( $_GET['token'] ) ? $_GET['token'] : ( isset( $session['get_express_token'] ) ? $session['get_express_token'] : '' );
			$payer_id = isset( $_GET['PayerID'] ) ? $_GET['PayerID'] : ( isset( $session['payer_id'] ) ? $session['payer_id'] : '');
			if ( ! empty( $token ) && ! empty( $session ) && 'cart' === $session['start_from'] ) {
				$get_checkout = $this->cart_handler->get_checkout( $token );
				if ( false !== $get_checkout ) {
					$pp_payer = $get_checkout->GetExpressCheckoutDetailsResponseDetails->PayerInfo;
					$this->set_metadata( $order_id, 'payer_email', $pp_payer->Payer );
					$this->set_metadata( $order_id, 'payer_status', $pp_payer->PayerStatus );
					$this->set_metadata( $order_id, 'payer_country', $pp_payer->PayerCountry );
					$this->set_metadata( $order_id, 'payer_business', $pp_payer->PayerBusiness );
					$this->set_metadata( $order_id, 'payer_name', implode( ' ', array( $pp_payer->PayerName->FirstName, $pp_payer->PayerName->MiddleName, $pp_payer->PayerName->LastName ) ) );
					$this->set_metadata( $order_id, 'get_express_token', $token );
					$this->set_metadata( $order_id, 'set_express_token', $session['set_express_token'] );
					$this->set_metadata( $order_id, 'environment', WC_PayPal_Interface_Latam::get_env() );
					$this->set_metadata( $order_id, 'payer_id', $payer_id );

					// Maybe create billing agreement.
					// if ( $create_billing_agreement ) {
					// $this->create_billing_agreement( $order, $checkout_details );
					// }
					$order = wc_get_order( $order_id );
					if ( true == method_exists( $order, 'get_order_key' ) ) {
						$order_key = $order->get_order_key();
					} else {
						$order_key = $order->order_key;
					}
					// Complete the payment now.
					$do_checkout = $this->cart_handler->do_checkout( $order_id, $payer_id, $token, json_encode( array(
						'order_id' => $order_id,
						'order_key' => $order_key,
					) ), $this->get_option( 'invoice_prefix' ) . $order->get_order_number() );
					if ( false !== $do_checkout && isset( $do_checkout->DoExpressCheckoutPaymentResponseDetails->PaymentInfo ) ) {
						$this->set_metadata( $order_id, 'transaction_id', (string) $do_checkout->DoExpressCheckoutPaymentResponseDetails->PaymentInfo[0]->TransactionID );
						return;
					} else {
						$ret = array(
							'result'   => 'failure',
							'refresh' => true,
							'reload' => false,
							'messages' => '<div class="woocommerce-error">' . __( 'Error code 10001: Sorry, an error occurred while trying to retrieve your information from PayPal. Please try again.', 'woocommerce-paypal-express-mx' ) . '</div>',
						);
						echo json_encode( $ret );
						exit;
					}
				} else {
					$ret = array(
						'result'   => 'failure',
						'refresh' => true,
						'reload' => false,
						'messages' => __( '<div class="woocommerce-error">' . 'Error code 10002: Sorry, an error occurred while trying to retrieve your information from PayPal. Please try again.', 'woocommerce-paypal-express-mx' ) . '</div>',
					);
					echo json_encode( $ret );
					exit;
				}// End if().
			}// End if().
			if ( 'modal_on_checkout' !== $this->checkout_mode ) {
				return;
			}
			PPWC()->session->set( 'paypal_mx', array() );
			$token = $this->cart_handler->start_checkout( array(
				'start_from' => 'checkout',
				'order_id' => $order_id,
				'return_token' => true,
			) );
			if ( false === $token ) { // Error proccesing order on PayPal...
				return;
			}
			$ret = array(
				'result'   => 'failure', // WC Checkout Hacking...
				'refresh' => false,
				'reload' => false,
				'messages' => "<div style='display:none' id='pp_latam_redirect' data-order_id='{$order_id}' data-token='{$token}'></div>'",
			);
			echo json_encode( $ret );
			exit;
		}
		/**
		 * Generate the form.
		 *
		 * @param int $order_id Order ID.
		 *
		 * @return string           Payment form.
		 */
		public function generate_form( $order_id ) {
			$order = wc_get_order( $order_id );
			PPWC()->session->set( 'paypal_mx', array() );
			$token = $this->cart_handler->start_checkout( array(
				'start_from' => 'checkout',
				'order_id' => $order_id,
				'return_token' => true,
			) );
			if ( $token ) {
				$html = '<p>' . __( 'Thank you for your order, please click the button below to pay with PayPal.', 'woocommerce-paypal-express-mx' ) . '</p>';
				$html .= '<div id="btn_ppexpress_mx_order" data-token="' . $token . '" style="float: left;margin-right: 30px;"></div> <a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel &amp; Restore Cart', 'woocommerce-paypal-express-mx' ) . '</a>';
				return $html;
			} else {
				$html = '<p>' . __( 'There was a problem with Paypal, try later or contact our team.', 'woocommerce-paypal-express-mx' ) . '</p>';
				$html .= '<a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Reload Cart', 'woocommerce-paypal-express-mx' ) . '</a>';
				return $html;
			}
		}
		/**
		 * Output for the order received page.
		 *
		 * @return void
		 */
		public function receipt_page( $order ) {
			echo $this->generate_form( $order );
		}

		function show_metabox() {
			global $theorder;
			$order_id = method_exists( $theorder, 'get_id' )?$theorder->get_id():$theorder->id;
			$status = self::get_metadata( $order_id, 'transaction_id' );
			if ( ! $status || empty( $status ) ) {
				echo __( 'This order was not processed by PayPal.', 'woocommerce-mercadoenvios' );
				return;
			}
			?>
			<table width="70%" style="width:70%">
			<?php
			$check_metadata = array( 'mc_fee', 'payment_date', 'payer_status', 'address_status', 'protection_eligibility', 'payment_type', 'first_name', 'last_name', 'payer_email' );
			self::showLabelMetabox( $order_id, 'transaction_id', __( 'Transaction ID', 'woocommerce-paypal-express-mx' ) );
			self::showLabelMetabox( $order_id, 'ipn_protection_eligibility', __( 'Protection eligibility', 'woocommerce-paypal-express-mx' ) );
			self::showLabelMetabox( $order_id, 'ipn_payment_type', __( 'Payment type', 'woocommerce-paypal-express-mx' ) );
			self::showLabelMetabox( $order_id, 'ipn_first_name', __( 'Payer first name', 'woocommerce-paypal-express-mx' ) );
			self::showLabelMetabox( $order_id, 'ipn_last_name', __( 'Payer last name', 'woocommerce-paypal-express-mx' ) );
			self::showLabelMetabox( $order_id, 'ipn_payer_email', __( 'Payer email', 'woocommerce-paypal-express-mx' ) );
			self::showLabelMetabox( $order_id, 'ipn_payer_status', __( 'Payer status', 'woocommerce-paypal-express-mx' ) );
			self::showLabelMetabox( $order_id, 'ipn_address_status', __( 'Address status', 'woocommerce-paypal-express-mx' ) );
			self::showLabelMetabox( $order_id, 'ipn_mc_fee', __( 'Transaction fee', 'woocommerce-paypal-express-mx' ), true );
			?>
			</table>
			<?php
		}
		public static function showLabelMetabox( $order_id, $id, $text, $is_price = false ) {
			$data = self::get_metadata( $order_id, $id );
			if ( false === $data || empty( $data ) ) {
				return;
			}
			?>
			<tr>
				<td><strong><?php echo $text; ?>:</strong></td><td><?php echo $is_price?wc_price( $data ):str_replace( '"', '', $data ); ?></td>
			<tr>
			<?php
		}
		/**
		 * Set Metadata of Order.
		 *
		 * @param   int    $order_id Order ID.
		 * @param   string $key Key of Metadata.
		 * @param   mixed  $value Value of Metadata.
		 *
		 * @return  bool   Result of Database.
		 */
		static public function set_metadata( $order_id, $key, $value ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'woo_ppexpress_mx';
			self::check_database();
			$exists = $wpdb->get_var($wpdb->prepare(  // @codingStandardsIgnoreLine
				"SELECT `id` FROM 
                `{$table_name}`
            WHERE
                    `order_id` = %d
                AND 
                    `key` = '%s'
            LIMIT 1",
				(int) $order_id,
				$key
			));
			if ( $exists ) {
				$result = $wpdb->update( $table_name, // @codingStandardsIgnoreLine
					array(
						'data' => wp_json_encode( $value ),
					),
					array(
						'order_id' => $order_id,
						'key' => $key,
					),
					array( '%s' ),
				array( '%d', '%s' ) );
			} else {
				$result = $wpdb->insert( $table_name, // @codingStandardsIgnoreLine
					array(
					'order_id' => $order_id,
					'key' => $key,
					'data' => wp_json_encode( $value ),
				), array( '%d', '%s', '%s' ) );
			}
			self::$cache_metadata[ $order_id . '-' . $key ] = $value;
			wp_cache_set( 'ppmetadata-' . $order_id . '-' . $key, $value, 'ppmetadata' );
			WC_Paypal_Logger::obj()->debug( "set_metadata [order:{$order_id}]: [{$key}]=>" . print_r( $value, true ) . ' Result: ' . print_r( $result, true ) );
			return $result;
		}
		/**
		 * Get Metadata of Order.
		 *
		 * @param   int    $order_id Order ID.
		 * @param   string $key Key of Metadata.
		 *
		 * @return  mixed  Value of metadata.
		 */
		static public function get_metadata( $order_id, $key ) {
			global $wpdb;
			if ( isset( self::$cache_metadata[ $order_id . '-' . $key ] ) ) {
				return self::$cache_metadata[ $order_id . '-' . $key ];
			}
			$data = wp_cache_get( 'ppmetadata-' . $order_id . '-' . $key, 'ppmetadata' );
			if ( false === $data || empty( $data ) ) {
				self::check_database();
				$table_name = $wpdb->prefix . 'woo_ppexpress_mx';
				$data = $wpdb->get_var($wpdb->prepare(
					"SELECT `data` FROM 
						`{$table_name}`
					WHERE
							`order_id` = %d
						AND 
							`key` = '%s'
					LIMIT 1",
					(int) $order_id,
					$key
				));
				wp_cache_set( 'ppmetadata-' . $order_id . '-' . $key, $data, 'ppmetadata' );
				WC_Paypal_Logger::obj()->debug( "get_metadata [order:{$order_id}]: [{$key}] | Result: " . print_r( $data, true ) );
				self::$cache_metadata[ $order_id . '-' . $key ] = $data ? json_decode( $data, true ) : false;
			} else {
				self::$cache_metadata[ $order_id . '-' . $key ] = $data;
			}
			return self::$cache_metadata[ $order_id . '-' . $key ];
		}
		/**
		 * Create table in database.
		 */
		static public function check_database() {
			global $wpdb;
			$table_name = $wpdb->prefix . 'woo_ppexpress_mx';
			if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE '%s'", $table_name ) ) !== $table_name ) { // @codingStandardsIgnoreLine
				$charset_collate = $wpdb->get_charset_collate();
				$sql = "CREATE TABLE `{$table_name}` (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`order_id` bigint NOT NULL,
						`key` varchar(255) NOT NULL,
						`data` longtext NOT NULL,
						PRIMARY KEY (`id`),
						INDEX `idx_order_id` (`order_id`),
						INDEX `idx_key` (`key`)
					) {$charset_collate};";

				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $sql );
				WC_Paypal_Logger::obj()->debug( "Datebase `{$table_name}` created!" );
			}
		}
		/**
		 * Drop table of database.
		 */
		static public function uninstall_database() {
			global $wpdb;
			$table_name = $wpdb->prefix . 'woo_ppexpress_mx';
			$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS `%s`', $table_name ) ); // @codingStandardsIgnoreLine
			WC_Paypal_Logger::obj()->debug( "Datebase `{$table_name}` deleted!" );
		}
	}
endif;

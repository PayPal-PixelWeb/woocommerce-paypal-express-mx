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

use PayPal\PayPalAPI\GetBalanceReq;
use PayPal\PayPalAPI\GetBalanceRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;

if ( ! class_exists( 'WC_PayPal_Connect_IPS' ) ) :
	/**
	 * PayPal Express Integrated PayPal Signup Handler.
	 *
	 * @since 1.0.0
	 */
	class WC_PayPal_Connect_IPS {

		const MIDDLEWARE_BASE_URL = 'https://connect.woocommerce.com';

		/**
		 * Countries that support IPS.
		 *
		 * @var array
		 *
		 * @since 1.0.0
		 */
	    // @codingStandardsIgnoreStart
	    private $_supported_countries = array(
	        'AL', 'DZ', 'AO', 'AI', 'AG', 'AR', 'AM', 'AW', 'AU', 'AT', 'AZ', 'BS',
	        'BH', 'BB', 'BE', 'BZ', 'BJ', 'BM', 'BT', 'BO', 'BA', 'BW', 'VG', 'BN',
	        'BG', 'BF', 'BI', 'KH', 'CA', 'CV', 'KY', 'TD', 'CL', 'CN', 'C2', 'CO',
	        'KM', 'CG', 'CK', 'CR', 'HR', 'CY', 'CZ', 'CD', 'DK', 'DJ', 'DM', 'DO',
	        'EC', 'EG', 'SV', 'ER', 'EE', 'ET', 'FK', 'FM', 'FJ', 'FI', 'FR', 'GF',
	        'PF', 'GA', 'GM', 'GE', 'DE', 'GI', 'GR', 'GL', 'GD', 'GP', 'GU', 'GT',
	        'GN', 'GW', 'GY', 'VA', 'HN', 'HK', 'HU', 'IS', 'ID', 'IE', 'IT', 'JM',
	        'JO', 'KZ', 'KE', 'KI', 'KW', 'KG', 'LA', 'LV', 'LS', 'LI', 'LT', 'LU',
	        'MG', 'MW', 'MY', 'MV', 'ML', 'MT', 'MH', 'MQ', 'MR', 'MU', 'YT', 'MX',
	        'MN', 'MS', 'MA', 'MZ', 'NA', 'NR', 'NP', 'NL', 'AN', 'NC', 'NZ', 'NI',
	        'NE', 'NU', 'NF', 'NO', 'OM', 'PW', 'PA', 'PG', 'PE', 'PH', 'PN', 'PL',
	        'PT', 'QA', 'RE', 'RO', 'RU', 'RW', 'SH', 'KN', 'LC', 'PM', 'VC', 'WS',
	        'SM', 'ST', 'SA', 'SN', 'RS', 'SC', 'SL', 'SG', 'SK', 'SI', 'SB', 'SO',
	        'ZA', 'KR', 'ES', 'LK', 'SR', 'SJ', 'SZ', 'SE', 'CH', 'TW', 'TJ', 'TH',
	        'TG', 'TO', 'TT', 'TN', 'TR', 'TM', 'TC', 'TV', 'UG', 'UA', 'AE', 'GB',
	        'TZ', 'US', 'UY', 'VU', 'VE', 'VN', 'WF', 'YE', 'ZM',
	    );
	    // @codingStandardsIgnoreEnd

		/**
		 * Get merchant redirect URL for IPS.
		 *
		 * This is store URL that will be redirected from middleware.
		 *
		 * @param string $env Environment.
		 *
		 * @return string Redirect URL
		 *
		 * @since 1.0.0
		 */
		public function get_redirect_url( $env ) {
			if ( in_array( $env, array( 'live', 'sandbox' ), true ) ) {
				$env = 'live';
			}

			return add_query_arg(
				array(
					'env'                     => $env,
					'wc_ppexpress_mx_ips_admin_nonce' => wp_create_nonce( 'wc_ppexpress_mx_ips' ),
				),
				WC_Paypal_Express_MX::get_admin_link()
			);
		}

		/**
		 * Get login URL to WC middleware.
		 *
		 * @param string $env Environment.
		 *
		 * @return string Signup URL
		 *
		 * @since 1.0.0
		 */
		public function get_middleware_login_url( $env ) {
			$service = 'ppe';
			if ( 'sandbox' === $env ) {
				$service = 'ppesandbox';
			}

			return self::MIDDLEWARE_BASE_URL . '/login/' . $service;
		}

		/**
		 * Get signup URL to WC middleware.
		 *
		 * @param string $env Environment.
		 *
		 * @return string Signup URL
		 *
		 * @since 1.0.0
		 */
		public function get_signup_url( $env ) {
			$query_args = array(
				'redirect'    => rawurlencode( $this->get_redirect_url( $env ) ),
				'countryCode' => PPWC()->countries->get_base_country(),
				'merchantId'  => md5( site_url( '/' ) . time() ),
			);

			return add_query_arg( $query_args, $this->get_middleware_login_url( $env ) );
		}

		/**
		 * Check if base location country supports IPS.
		 *
		 * @return bool Returns true of base country in supported countries
		 */
		public function is_supported() {
			return in_array( PPWC()->countries->get_base_country(), $this->_supported_countries, true );
		}

		/**
		 * Redirect with messages.
		 *
		 * @param string $error_msg Message.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		protected function _redirect_with_messages( $error_msg ) {
			if ( ! is_array( $error_msg ) ) {
				$error_msgs = array(
					array(
						'error' => $error_msg,
					),
				);
			} else {
				$error_msgs = $error_msg;
			}

			add_option( 'woo_ppexpress_admin_error', $error_msgs );
			wp_safe_redirect( WC_Paypal_Express_MX::get_admin_link() );
			exit;
		}

		/**
		 * Maybe received credentials after successfully returned from IPS flow.
		 *
		 * @return mixed
		 *
		 * @since 1.0.0
		 */
		public function maybe_received_credentials() {
			if ( ! is_admin() || ! is_user_logged_in() ) {
				return false;
			}

			// Require the nonce.
			if ( empty( $_GET['wc_ppexpress_mx_ips_admin_nonce'] ) || empty( $_GET['env'] ) ) { // @codingStandardsIgnoreLine
				return false;
			}
			$env = in_array( wp_unslash( $_GET['env'] ), array( 'live', 'sandbox' ), true ) ? wp_unslash( $_GET['env'] ) : 'live'; // @codingStandardsIgnoreLine

			// Verify the nonce.
			if ( ! wp_verify_nonce( wp_unslash( $_GET['wc_ppexpress_mx_ips_admin_nonce'] ), 'wc_ppexpress_mx_ips' ) ) { // @codingStandardsIgnoreLine
				wp_die( __( 'Invalid connection request', 'woocommerce-paypal-express-mx' ) );
			}

			WC_Paypal_Logger::obj()->debug( sprintf( '%s: returned back from IPS flow with parameters', __METHOD__ ), array(
				'GET' => $_GET, // @codingStandardsIgnoreLine
			) );

			// Check if error.
			if ( ! empty( $_GET['error'] ) ) { // @codingStandardsIgnoreLine
				$error_message = ! empty( $_GET['error_message'] ) ? $_GET['error_message'] : ''; // @codingStandardsIgnoreLine
				WC_Paypal_Logger::obj()->debug( sprintf( '%s: returned back from IPS flow with error: %s', __METHOD__, $error_message ) );

				$this->_redirect_with_messages( __( 'Sorry, Easy Setup encountered an error.  Please try again.', 'woocommerce-paypal-express-mx' ) );
			}

			// Make sure credentials present in query string.
			foreach ( array( 'api_style', 'api_username', 'api_password', 'signature' ) as $param ) {
				if ( empty( $_GET[ $param ] ) ) { // @codingStandardsIgnoreLine
					WC_Paypal_Logger::obj()->debug( sprintf( '%s: returned back from IPS flow but missing parameter %s', __METHOD__, $param ) );

					$this->_redirect_with_messages( __( 'Sorry, Easy Setup encountered an error.  Please try again.', 'woocommerce-paypal-express-mx' ) );
				}
			}

			$error_msgs = array();
			try {
				$get_balance = new GetBalanceRequestType();
				$get_balance->ReturnAllCurrencies = 1; // @codingStandardsIgnoreLine
				$get_balance_req = new GetBalanceReq();
				$get_balance_req->GetBalanceRequest = $get_balance; // @codingStandardsIgnoreLine
				$pp_service = new PayPalAPIInterfaceServiceService(array(
					'mode' => $env,
					'acct1.UserName'  => $_GET['api_username'], // @codingStandardsIgnoreLine
					'acct1.Password'  => $_GET['api_password'], // @codingStandardsIgnoreLine
					'acct1.Signature' => $_GET['signature'], // @codingStandardsIgnoreLine
				));
				$pp_balance = $pp_service->GetBalance( $get_balance_req );
				WC_Paypal_Logger::obj()->debug( 'Received Credentials OK: ', array( $pp_balance ) );
			} catch ( Exception $ex ) {
				WC_Paypal_Logger::obj()->warning( 'Error on maybe_received_credentials: ', array( $ex ) );
				$error_msgs[] = array(
					'warning' => __( 'Easy Setup was able to obtain your API credentials, but an error occurred while trying to verify that they work correctly.  Please try Easy Setup again.', 'woocommerce-paypal-express-mx' ),
				);
			}
			if ( isset( $pp_balance ) ) {
				$error_msgs[] = array(
					'success' => __( 'Success!  Your PayPal account has been set up successfully.', 'woocommerce-paypal-express-mx' ),
				);

				if ( ! empty( $error_msgs ) ) {
					WC_Paypal_Logger::obj()->debug( sprintf( '%s: returned back from IPS flow', __METHOD__ ), array( $error_msgs ) );
				}

				// Save credentials to settings API.
				$settings_array = (array) get_option( 'woocommerce_ppexpress_mx_settings', array() );

				if ( 'live' === $env ) {
					$settings_array['environment']     = 'live';
					$settings_array['api_username']    = $_GET['api_username']; // @codingStandardsIgnoreLine
					$settings_array['api_password']    = $_GET['api_password']; // @codingStandardsIgnoreLine
					$settings_array['api_signature']   = $_GET['signature']; // @codingStandardsIgnoreLine
					$settings_array['api_certificate'] = '';
					$settings_array['api_subject']     = '';
				} else {
					$settings_array['environment']     = 'sandbox';
					$settings_array['sandbox_api_username']    = $_GET['api_username']; // @codingStandardsIgnoreLine
					$settings_array['sandbox_api_password']    = $_GET['api_password']; // @codingStandardsIgnoreLine
					$settings_array['sandbox_api_signature']   = $_GET['signature']; // @codingStandardsIgnoreLine
					$settings_array['sandbox_api_certificate'] = '';
					$settings_array['sandbox_api_subject']     = '';
				}

				update_option( 'woocommerce_ppexpress_mx_settings', $settings_array );
			}
			$this->_redirect_with_messages( $error_msgs );
		}
	}
endif;

<?php
/**
 * Interface for Paypal API.
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
use PayPal\PayPalAPI\GetPalDetailsReq;
use PayPal\PayPalAPI\GetPalDetailsRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;

if ( ! class_exists( 'WC_PayPal_Interface_Latam' ) ) :
	/**
	 * PayPal API Interface
	 */
	class WC_PayPal_Interface_Latam {
		/**
		 * Instance of this class.
		 *
		 * @var object
		 *
		 * @since 1.0.0
		 */
		private static $instance = null;
		/**
		 * Settings of Plugin.
		 *
		 * @var array
		 *
		 * @since 1.0.0
		 */
		private $settings = null;
		/**
		 * Paypal Account ID.
		 *
		 * @var string
		 *
		 * @since 1.0.0
		 */
		private $acc_id = false;
		/**
		 * Paypal Account Locale.
		 *
		 * @var string
		 *
		 * @since 1.0.0
		 */
		private $acc_locale = false;
		/**
		 * Paypal Account Balance.
		 *
		 * @var array
		 *
		 * @since 1.0.0
		 */
		private $acc_balance = false;

		/**
		 * Paypal Account Currency.
		 *
		 * @var string
		 *
		 * @since 1.0.0
		 */
		private $acc_currency = false;

		/**
		 * Paypal Service Object.
		 *
		 * @var object
		 *
		 * @since 1.0.0
		 */
		private $service = false;
		/**
		 * Initialize the plugin.
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
			$this->settings = (array) get_option( 'woocommerce_ppexpress_mx_settings', array() );
		}

		/**
		 * Get Unique Instance.
		 *
		 * @since 1.0.0
		 */
		static public function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}
		/**
		 * Short alias for get_instance.
		 *
		 * @since 1.0.0
		 */
		static public function obj() {
			return self::get_instance();
		}
		/**
		 * Get Paypal Payer ID.
		 *
		 * @since 1.0.0
		 */
		static public function get_payer_id() {
			if ( false !== self::get_static_interface_service() ) {
				return self::obj()->acc_id;
			}
			return false;
		}
		/**
		 * Get Paypal Locale.
		 *
		 * @since 1.0.0
		 */
		static public function get_locale() {
			if ( false !== self::get_static_interface_service() ) {
				return self::obj()->acc_locale;
			}
			return false;
		}
		/**
		 * Get Paypal Locale.
		 *
		 * @since 1.0.0
		 */
		static public function get_env() {
			if ( false !== self::get_static_interface_service() ) {
				return self::obj()->get_option( 'environment' );
			}
			return false;
		}
		/**
		 * Get Balance of Account.
		 *
		 * @since 1.0.0
		 */
		static public function get_balance() {
			if ( false !== self::get_static_interface_service() ) {
				return array( self::obj()->acc_balance, self::obj()->acc_currency );
			}
			return false;
		}
		/**
		 * Get options.
		 *
		 * @param string $key Key of Setting Option.
		 *
		 * @return string Value of Option.
		 *
		 * @since 1.0.0
		 */
		private function get_option( $key ) {
			return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : false ;
		}
		/**
		 * Get cache key.
		 *
		 * @param string $env Environmentn.
		 *
		 * @return string Cache Key.
		 *
		 * @since 1.0.0
		 */
		private function get_cache_key( $env = 'live' ) {
			$username        = $this->get_option( 'api_username' );
			$password        = $this->get_option( 'api_password' );
			$api_subject     = $this->get_option( 'api_subject' );
			$api_signature   = $this->get_option( 'api_signature' );
			$api_certificate = $this->get_option( 'api_certificate' );
			return md5( $env . $username . $password . $api_subject . $api_signature . $api_certificate );
		}

		/**
		 * Validate the provided credentials.
		 *
		 * @param bool $show_message Show error message for wp-admin.
		 * @param bool $force Ignore Cache, force check credentials.
		 * @param bool $env Environment.
		 *
		 * @return bool|object Paypal Instance.
		 *
		 * @since 1.0.0
		 */
		public function validate_active_credentials( $show_message = false, $force = false, $env = null ) {
			static $cache_id = null;
			if ( is_null( $env ) || ( 'live' !== $env && 'sandbox' !== $env ) ) {
				$env = $this->get_option( 'environment' );
			}
			if ( false === $force && $cache_id === $this->get_cache_key( $env ) ) {
				return $this->service;
			}
			$cache_id = $this->get_cache_key( $env );
			if ( true === $force ) {
				$this->settings = (array) get_option( 'woocommerce_ppexpress_mx_settings', array() );
				WC_Paypal_Express_MX_Gateway::set_metadata( 0, 'acc_id_' . $cache_id, array() );
				WC_Paypal_Express_MX_Gateway::set_metadata( 0, 'acc_balance_' . $cache_id, array() );
			}
			$cache_acc_id = WC_Paypal_Express_MX_Gateway::get_metadata( 0, 'acc_id_' . $cache_id );
			$cache_acc_balance = WC_Paypal_Express_MX_Gateway::get_metadata( 0, 'acc_balance_' . $cache_id );
			if ( 'live' === $env ) {
				$username        = $this->get_option( 'api_username' );
				$password        = $this->get_option( 'api_password' );
				$api_subject     = $this->get_option( 'api_subject' );
				$api_signature   = $this->get_option( 'api_signature' );
				$api_certificate = $this->get_option( 'api_certificate' );
			} else {
				$username        = $this->get_option( 'sandbox_api_username' );
				$password        = $this->get_option( 'sandbox_api_password' );
				$api_subject     = $this->get_option( 'sandbox_api_subject' );
				$api_signature   = $this->get_option( 'sandbox_api_signature' );
				$api_certificate = $this->get_option( 'sandbox_api_certificate' );
			}
			$pp_service = false;
			$this->service = false;
			if ( ! empty( $username ) ) {
				if ( empty( $password ) ) {
					if ( true === $show_message ) {
						WC_Admin_Settings::add_error( __( 'Error: You must enter API password.', 'woocommerce-paypal-express-mx' ) );
					}
					return false;
				}
				if ( empty( $api_certificate ) && ! empty( $api_signature ) ) {
					$pp_service = new PayPalAPIInterfaceServiceService( array(
						'mode' => $env,
						'acct1.UserName'  => $username,
						'acct1.Password'  => $password,
						'acct1.Signature' => $api_signature,
						'acct1.Subject'   => $api_subject,
					) );
				} elseif ( ! empty( $api_certificate ) && empty( $api_signature ) ) {
					if ( ! file_exists( dirname( __FILE__ ) . '/cert/' . $env . '_key_data.pem' ) ) {
						$cert = @openssl_x509_read( base64_decode( $api_certificate ) ); // @codingStandardsIgnoreLine
						if ( false === $cert ) {
							if ( true === $show_message ) {
								WC_Admin_Settings::add_error( __( 'Error: The API certificate is not valid.', 'woocommerce-paypal-express-mx' ) );
							}
							return false;
						}
						$cert_info   = openssl_x509_parse( $cert );
						$valid_until = $cert_info['validTo_time_t'];
						if ( $valid_until < time() ) {
							if ( true === $show_message ) {
								WC_Admin_Settings::add_error( __( 'Error: The API certificate has expired.', 'woocommerce-paypal-express-mx' ) );
							}
							return false;
						}
						if ( $cert_info['subject']['CN'] !== $username ) {
							if ( true === $show_message ) {
								WC_Admin_Settings::add_error( __( 'Error: The API username does not match the name in the API certificate.  Make sure that you have the correct API certificate.', 'woocommerce-paypal-express-mx' ) );
							}
							return false;
						}
						file_put_contents( dirname( __FILE__ ) . '/cert/' . $env . '_key_data.pem', base64_decode( $api_certificate ) ); // @codingStandardsIgnoreLine
					}
					$pp_service = new PayPalAPIInterfaceServiceService( array(
						'mode' => $env,
						'acct1.UserName'  => $username,
						'acct1.Password'  => $password,
						'acct1.Signature' => $api_signature,
						'acct1.CertPath'  => dirname( __FILE__ ) . '/cert/' . $env . '_key_data.pem',
						'acct1.Subject'   => $api_subject,
					) );
				} else {
					if ( true === $show_message ) {
						WC_Admin_Settings::add_error( __( 'Error: You must enter "API Signature" or "API Certificate" field.', 'woocommerce-paypal-express-mx' ) );
					}
					return false;
				}// End if().
				try {
					if ( true === $force || ! is_array( $cache_acc_balance ) || ! isset( $cache_acc_balance['time'] ) || $cache_acc_balance['time'] + 3600 < time() ) {
						$get_balance = new GetBalanceRequestType();
						$get_balance->ReturnAllCurrencies = 1; // @codingStandardsIgnoreLine
						$get_balance_req = new GetBalanceReq();
						$get_balance_req->GetBalanceRequest = $get_balance; // @codingStandardsIgnoreLine
						$pp_balance = $pp_service->GetBalance( $get_balance_req );
						if ( ! in_array( $pp_balance->Ack, array( 'Success', 'SuccessWithWarning' ) ) ) { // @codingStandardsIgnoreLine
							WC_Paypal_Logger::obj()->warning( 'Error on credentials: ' . print_r( $pp_balance, true ) );
							if ( true === $show_message ) {
								WC_Admin_Settings::add_error( __( 'Error: The API credentials you provided are not valid.  Please double-check that you entered them correctly and try again.', 'woocommerce-paypal-express-mx' ) );
							}
							return false;
						}
						$this->acc_balance = $pp_balance->Balance->value; // @codingStandardsIgnoreLine
						$this->acc_currency = $pp_balance->Balance->currencyID; // @codingStandardsIgnoreLine
						WC_Paypal_Logger::obj()->debug( 'Received Credentials OK: ' . print_r( $pp_balance, true ) );
						WC_Paypal_Express_MX_Gateway::set_metadata( 0, 'acc_balance_' . $cache_id, array(
							'balance' => $this->acc_balance,
							'currency' => $this->acc_currency,
							'time' => time(),
						) );
					} else {
						$this->acc_balance = (float) $cache_acc_balance['balance'];
						$this->acc_currency = $cache_acc_balance['currency'];
					}
					if ( true === $force || ! is_array( $cache_acc_id ) || ! isset( $cache_acc_id['time'] ) || $cache_acc_id['time'] + 24 * 3600 < time() ) {
						$pal_details_req = new GetPalDetailsReq();
						$pal_details_req->GetPalDetailsRequest = new GetPalDetailsRequestType(); // @codingStandardsIgnoreLine
						$pal_details = $pp_service->GetPalDetails( $pal_details_req );
						if ( ! in_array( $pal_details->Ack, array( 'Success', 'SuccessWithWarning' ), true ) ) { // @codingStandardsIgnoreLine
							WC_Paypal_Logger::obj()->warning( 'Error on get paypal details', array( $pal_details ) );
							if ( true === $show_message ) {
								WC_Admin_Settings::add_error( __( 'Error: The API credentials present problems to get details.', 'woocommerce-paypal-express-mx' ) );
							}
							return false;
						}
						$this->acc_id = $pal_details->Pal; // @codingStandardsIgnoreLine
						$this->acc_locale = $pal_details->Locale; // @codingStandardsIgnoreLine
						WC_Paypal_Logger::obj()->debug( 'Received PP_Details OK', array( $pal_details ) );
						WC_Paypal_Express_MX_Gateway::set_metadata( 0, 'acc_id_' . $cache_id, array(
							'acc_id' => $this->acc_id,
							'acc_locale' => $this->acc_locale,
							'time' => time(),
						) );
					} else {
						$this->acc_id = $cache_acc_id['acc_id'];
						$this->acc_locale = $cache_acc_id['acc_locale'];
					}
					if ( true === $show_message ) {
						WC_Admin_Settings::add_message( __( 'You credentials is OK, your actual balance is: ', 'woocommerce-paypal-express-mx' ) . $this->acc_balance . ' ' . $this->acc_currency );
						WC_Admin_Settings::add_message( __( 'You Payer ID is: ', 'woocommerce-paypal-express-mx' ) . $this->acc_id );
					}
				} catch ( Exception $ex ) {
					WC_Paypal_Logger::obj()->warning( 'Error on credentials', array( $ex ) );
					if ( true === $show_message ) {
						WC_Admin_Settings::add_error( __( 'Error: The API credentials you provided are not valid.  Please double-check that you entered them correctly and try again.', 'woocommerce-paypal-express-mx' ) );
					}
					return false;
				}// End try().
				$this->service = $pp_service;
				return $this->service;
			}// End if().
		}
		/**
		 * Get credentials.
		 *
		 * @since 1.0.0
		 */
		public function get_interface_service() {
			return $this->validate_active_credentials( false, false );
		}
		/**
		 * Get credentials.
		 *
		 * @since 1.0.0
		 */
		public static function get_static_interface_service() {
			return self::obj()->get_interface_service();
		}
	}
endif;

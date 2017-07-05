<?php

use PayPal\PayPalAPI\GetBalanceReq;
use PayPal\PayPalAPI\GetBalanceRequestType;
use PayPal\PayPalAPI\GetPalDetailsReq;
use PayPal\PayPalAPI\GetPalDetailsRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * PayPal API Interface
 */
class WC_PayPal_Interface_Latam {
	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	private static $instance = null;
	private $settings = null;
	private $acc_id = false;
	private $acc_locale = false;
	private $acc_balance = false;
	private $acc_currency = false;
	private $service = false;
	/**
	 * Initialize the plugin.
	 */
	private function __construct() {
		$this->settings = (array) get_option( 'woocommerce_ppexpress_latam_settings', array() );
	}

	/**
	 * Get Unique Instance.
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
	 * Get Paypal Payer ID.
	 */
	static public function get_payer_id() {
		if ( false !== self::get_static_interface_service() ) {
			return self::obj()->acc_id;
		}
		return false;
	}
	/**
	 * Get Paypal Locale.
	 */
	static public function get_locale() {
		if ( false !== self::get_static_interface_service() ) {
			return self::obj()->acc_locale;
		}
		return false;
	}
	/**
	 * Get Paypal Locale.
	 */
	static public function get_env() {
		if ( false !== self::get_static_interface_service() ) {
			return self::obj()->get_option( 'environment' );
		}
		return false;
	}
	/**
	 * Get Balance of Account.
	 */
	static public function get_balance() {
		if ( false !== self::get_static_interface_service() ) {
			return array( self::obj()->acc_balance, self::obj()->acc_currency );
		}
		return false;
	}
	/**
	 * Get options.
	 */
	private function get_option( $key ) {
		return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : false ;
	}
	/**
	 * Get cache key.
	 */
	private function get_cache_key() {
		$env             = $this->get_option( 'environment' );
		$username        = $this->get_option( 'api_username' );
		$password        = $this->get_option( 'api_password' );
		$api_subject     = $this->get_option( 'api_subject' );
		$api_signature   = $this->get_option( 'api_signature' );
		$api_certificate = $this->get_option( 'api_certificate' );
		return md5( $env . $username . $password . $api_subject . $api_signature . $api_certificate );
	}
	/**
	 * Validate the provided credentials.
	 */
	public function validate_active_credentials( $show_message = false, $force = false ) {
		static $cache_id = null;
		if ( false === $force && $cache_id == $this->get_cache_key() ) {
			return $this->service;
		}
		$cache_id = $this->get_cache_key();
		$cache_acc_id = WC_Paypal_Express_MX_Gateway::get_metadata( 0, 'acc_id_' . $cache_id );
		$cache_acc_balance = WC_Paypal_Express_MX_Gateway::get_metadata( 0, 'acc_balance_' . $cache_id );
		$env             = $this->get_option( 'environment' );
		$username        = $this->get_option( 'api_username' );
		$password        = $this->get_option( 'api_password' );
		$api_subject     = $this->get_option( 'api_subject' );
		$api_signature   = $this->get_option( 'api_signature' );
		$api_certificate = $this->get_option( 'api_certificate' );
		$pp_service = false;
		if ( ! empty( $username ) ) {
			if ( empty( $password ) ) {
				if ( $show_message ) {
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
				if ( ! file_exists( dirname( __FILE__ ) . '/cert/key_data.pem' ) ) {
                    $cert = @openssl_x509_read( base64_decode( $api_certificate ) ); // @codingStandardsIgnoreLine
                    if ( false === $cert ) {
						if ( $show_message ) {
							WC_Admin_Settings::add_error( __( 'Error: The API certificate is not valid.', 'woocommerce-paypal-express-mx' ) );
						}
						return false;
					}
					$cert_info   = openssl_x509_parse( $cert );
					$valid_until = $cert_info['validTo_time_t'];
					if ( $valid_until < time() ) {
						if ( $show_message ) {
							WC_Admin_Settings::add_error( __( 'Error: The API certificate has expired.', 'woocommerce-paypal-express-mx' ) );
						}
						return false;
					}
					if ( $cert_info['subject']['CN'] != $username ) {
						if ( $show_message ) {
							WC_Admin_Settings::add_error( __( 'Error: The API username does not match the name in the API certificate.  Make sure that you have the correct API certificate.', 'woocommerce-paypal-express-mx' ) );
						}
						return false;
					}
					file_put_contents( dirname( __FILE__ ) . '/cert/key_data.pem', base64_decode( $api_certificate ) );
				}
				$pp_service = new PayPalAPIInterfaceServiceService( array(
					'mode' => $env,
					'acct1.UserName'  => $username,
					'acct1.Password'  => $password,
					'acct1.Signature' => $api_signature,
					'acct1.CertPath'  => dirname( __FILE__ ) . '/cert/key_data.pem',
					'acct1.Subject'   => $api_subject,
				) );
			} else {
				if ( $show_message ) {
					WC_Admin_Settings::add_error( __( 'Error: You must enter "API Signature" or "API Certificate" field.', 'woocommerce-paypal-express-mx' ) );
				}
				return false;
			}// End if().
			try {
				if ( $force === true || ! is_array( $cache_acc_balance ) || $cache_acc_balance['time'] + 3600 < time() ) {
					$get_balance = new GetBalanceRequestType();
					$get_balance->ReturnAllCurrencies = 1;
					$get_balance_req = new GetBalanceReq();
					$get_balance_req->GetBalanceRequest = $get_balance;
					$pp_balance = $pp_service->GetBalance( $get_balance_req );
					if ( ! in_array( $pp_balance->Ack, array( 'Success', 'SuccessWithWarning' ) ) ) {
						WC_Paypal_Logger::obj()->warning( 'Error on credentials: ' . print_r( $pp_balance, true ) );
						if ( $show_message ) {
							WC_Admin_Settings::add_error( __( 'Error: The API credentials you provided are not valid.  Please double-check that you entered them correctly and try again.', 'woocommerce-paypal-express-mx' ) );
						}
						return false;
					}
					$this->acc_balance = $pp_balance->Balance->value;
					$this->acc_currency = $pp_balance->Balance->currencyID;
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
				if ( $force === true || ! is_array( $cache_acc_id ) || $cache_acc_id['time'] + 24 * 3600 < time() ) {
					$pal_details_req = new GetPalDetailsReq();
					$pal_details_req->GetPalDetailsRequest = new GetPalDetailsRequestType();
					$pal_details = $pp_service->GetPalDetails( $pal_details_req );
					if ( ! in_array( $pal_details->Ack, array( 'Success', 'SuccessWithWarning' ) ) ) {
						WC_Paypal_Logger::obj()->warning( 'Error on get paypal details: ' . print_r( $pal_details, true ) );
						if ( $show_message ) {
							WC_Admin_Settings::add_error( __( 'Error: The API credentials present problems to get details.', 'woocommerce-paypal-express-mx' ) );
						}
						return false;
					}
					$this->acc_id = $pal_details->Pal;
					$this->acc_locale = $pal_details->Locale;
					WC_Paypal_Logger::obj()->debug( 'Received PP_Details OK: ' . print_r( $pal_details, true ) );
					WC_Paypal_Express_MX_Gateway::set_metadata( 0, 'acc_id_' . $cache_id, array(
						'acc_id' => $this->acc_id,
						'acc_locale' => $this->acc_locale,
						'time' => time(),
					) );
				} else {
					$this->acc_id = $cache_acc_id['acc_id'];
					$this->acc_locale = $cache_acc_id['acc_locale'];
				}
				if ( $show_message ) {
					WC_Admin_Settings::add_message( __( 'You credentials is OK, your actual balance is: ', 'woocommerce-paypal-express-mx' ) . $this->acc_balance . ' ' . $this->acc_currency );
					WC_Admin_Settings::add_message( __( 'You Payer ID is: ', 'woocommerce-paypal-express-mx' ) . $this->acc_id );
				}
			} catch ( Exception $ex ) {
				WC_Paypal_Logger::obj()->warning( 'Error on credentials: ' . print_r( $ex, true ) );
				if ( $show_message ) {
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
	 */
	public function get_interface_service() {
		return $this->validate_active_credentials( false, false );
	}
	public static function get_static_interface_service() {
		return self::obj()->get_interface_service();
	}
}

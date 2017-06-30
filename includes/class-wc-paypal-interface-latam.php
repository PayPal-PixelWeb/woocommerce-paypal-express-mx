<?php

use PayPal\PayPalAPI\GetBalanceReq;
use PayPal\PayPalAPI\GetBalanceRequestType;
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
	protected static $instance = null;
	private $settings = null;
	private $service = false;
	/**
	 * Initialize the plugin.
	 */
	private function __construct() {
		$this->settings = (array) get_option( 'woocommerce_ppexpress_latam_settings', array() );
	}

	/**
	 * Get Unique Instance
	 */
	static public function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	static public function obj() {
		// Short alias for get_instance
		return self::get_instance();
	}

	/**
	 * Get options
	 */
	private function get_option( $key ) {
		return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : false ;
	}
	/**
	 * Get cache key
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
		$this->service = false;
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
				try {
					$get_balance = new GetBalanceRequestType();
					$get_balance->ReturnAllCurrencies = 1;
					$get_balance_req = new GetBalanceReq();
					$get_balance_req->GetBalanceRequest = $get_balance;
					$pp_service = new PayPalAPIInterfaceServiceService( array(
						'mode' => $env,
						'acct1.UserName'  => $username,
						'acct1.Password'  => $password,
						'acct1.Signature' => $api_signature,
						'acct1.Subject'   => $api_subject,
					) );
					$pp_balance = $pp_service->GetBalance( $get_balance_req );
					if ( $show_message ) {
						WC_Admin_Settings::add_message( __( 'You credentials is OK, your actual balance is: ', 'woocommerce-paypal-express-mx' ) . $pp_balance->Balance->value . ' ' . $pp_balance->Balance->currencyID );
					}
					WC_Paypal_Logger::obj()->debug( 'Received Credentials OK: ' . print_r( $pp_balance, true ) );
				} catch ( Exception $ex ) {
					WC_Paypal_Logger::obj()->warning( 'Error on maybe_received_credentials: ' . print_r( $ex, true ) );
					if ( $show_message ) {
						WC_Admin_Settings::add_error( __( 'Error: The API credentials you provided are not valid.  Please double-check that you entered them correctly and try again.', 'woocommerce-paypal-express-mx' ) );
					}
					return false;
				}
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
				try {
					$get_balance = new GetBalanceRequestType();
					$get_balance->ReturnAllCurrencies = 1;
					$get_balance_req = new GetBalanceReq();
					$get_balance_req->GetBalanceRequest = $get_balance;
					$pp_service = new PayPalAPIInterfaceServiceService( array(
						'mode' => $env,
						'acct1.UserName'  => $username,
						'acct1.Password'  => $password,
						'acct1.Signature' => $api_signature,
						'acct1.CertPath'  => dirname( __FILE__ ) . '/cert/key_data.pem',
						'acct1.Subject'   => $api_subject,
					) );
					$pp_balance = $pp_service->GetBalance( $get_balance_req );
					if ( $show_message ) {
						WC_Admin_Settings::add_message( __( 'You credentials is OK, your actual balance is: ', 'woocommerce-paypal-express-mx' ) . $pp_balance->Balance->value . ' ' . $pp_balance->Balance->currencyID );
					}
					WC_Paypal_Logger::obj()->debug( 'Received Credentials OK: ' . print_r( $pp_balance, true ) );
				} catch ( Exception $ex ) {
					WC_Paypal_Logger::obj()->warning( 'Error on maybe_received_credentials: ' . print_r( $ex, true ) );
					if ( $show_message ) {
						WC_Admin_Settings::add_error( __( 'Error: The API credentials you provided are not valid.  Please double-check that you entered them correctly and try again.', 'woocommerce-paypal-express-mx' ) );
					}
					return false;
				}
			} else {
				if ( $show_message ) {
					WC_Admin_Settings::add_error( __( 'Error: You must enter "API Signature" or "API Certificate" field.', 'woocommerce-paypal-express-mx' ) );
				}
				return false;
			}// End if().
			$this->service = $pp_service;
			/*
			 $settings_array = (array) get_option( 'woocommerce_ppec_paypal_settings', array() );

            if ( 'yes' === $settings_array['require_billing'] ) {
                $is_account_enabled_for_billing_address = false;

                try {
                    $is_account_enabled_for_billing_address = wc_gateway_ppec()->client->test_for_billing_address_enabled( $creds, $settings->get_environment() );
                } catch ( PayPal_API_Exception $ex ) {
                    $is_account_enabled_for_billing_address = false;
                }

                if ( ! $is_account_enabled_for_billing_address ) {
                    $settings_array['require_billing'] = 'no';
                    update_option( 'woocommerce_ppec_paypal_settings', $settings_array );
                    WC_Admin_Settings::add_error( __( 'The "require billing address" option is not enabled by your account and has been disabled.', 'woocommerce-paypal-express-mx' ) );
                }
            } */
			return $this->service;
		}// End if().
	}
	/**
	 * Get credentials.
	 */
	public function get_credentials() {
		return $this->validate_active_credentials( false, false );
	}
	public static function get_static_credentials() {
		return self::obj()->get_credentials();
	}
}

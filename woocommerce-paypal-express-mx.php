<?php
/**
 * Plugin Name: PayPal Express Checkout MX
 * Plugin URI: https://github.com/PayPal-PixelWeb/woocommerce-paypal-express-mx
 * Description: PayPal Express Checkout MX
 * Author: PayPal, Leivant, PixelWeb, Kijam
 * Author URI: https://github.com/PayPal-PixelWeb/woocommerce-paypal-express-mx
 * Version: 1.0.1
 * License: Apache-2.0
 * Text Domain: woocommerce-paypal-express-mx
 * Domain Path: /languages/
 *
 * @package   WooCommerce
 * @author    PayPal, Leivant, PixelWeb, Kijam
 * @copyright 2017
 * @license   Apache-2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Paypal_Express_MX' ) ) :

	require_once dirname( __FILE__ ) . '/vendor/autoload.php';

	/**
	 * PayPal Express Checkout main class.
	 *
	 * @since 1.0.0
	 */
	class WC_Paypal_Express_MX {

		/**
		 * Plugin version.
		 *
		 * @var string
		 *
		 * @since 1.0.0
		 */
		const VERSION = '1.0.1';

		/**
		 * Instance of this class.
		 *
		 * @var object
		 *
		 * @since 1.0.0
		 */
		protected static $instance = null;

		/**
		 * Initialize the plugin.
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
			self::$instance = $this;
			include_once( dirname( __FILE__ ) . '/includes/class-wc-paypal-logger.php' );
			WC_Paypal_Logger::set_level( WC_Paypal_Logger::NORMAL ); // Normal Log.
			/* WC_Paypal_Logger::set_level( WC_Paypal_Logger::PARANOID ); */ // Paranoid Log.
			WC_Paypal_Logger::set_dir( dirname( __FILE__ ) . '/logs' );

			// Load plugin text domain.
			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

			// Checks with WooCommerce is installed.
			if ( class_exists( 'WC_Payment_Gateway' ) ) {
				if ( version_compare( self::woocommerce_instance()->version, '2.5', '<' ) ) {
					add_action( 'admin_notices', array( $this, 'woocommerce_missing_version_notice' ) );
				} elseif ( false === self::woocommerce_missing_openssl() && false === self::woocommerce_missing_curl() ) {
					if ( self::currency_has_decimal_restriction() ) {
						update_option( 'woocommerce_price_num_decimals', 0 );
						update_option( 'wc_gateway_ppce_display_decimal_msg', true );
						__( 'NOTE: PayPal does not accept decimal places for the currency in which you are transacting.  The "Number of Decimals" option in WooCommerce has automatically been set to 0 for you.', 'woocommerce-paypal-express-mx' );
					}
					include_once 'includes/class-wc-paypal-express-mx-gateway.php';
					include_once 'includes/class-wc-paypal-installment-gateway.php';
					add_action( 'woocommerce_init', array( $this, 'woocommerce_loaded' ) );
					add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
					add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_action_links' ) );
					add_filter( 'allowed_redirect_hosts' , array( $this, 'whitelist_paypal_domains_for_redirect' ) );
				}
			} else {
				add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			}
		}
		/**
		 * Take care of anything that needs woocommerce to be loaded.
		 * For instance, if you need access to the $woocommerce global
		 *
		 * @since 1.0.0
		 */
		public function woocommerce_loaded() {
			$this->gateway = WC_Paypal_Express_MX_Gateway::obj();
		}
		/**
		 * Return an instance of this class.
		 *
		 * @return object A single instance of this class
		 *
		 * @since 1.0.0.
		 */
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}
		/**
		 * Add the gateway to WooCommerce.
		 *
		 * @param   array $methods WooCommerce payment methods.
		 *
		 * @return  array Payment methods with Paypal.
		 *
		 * @since 1.0.0
		 */
		public function add_gateway( $methods ) {
			if ( version_compare( self::woocommerce_instance()->version, '2.3.0', '>=' ) ) {
				$methods[] = WC_Paypal_Express_MX_Gateway::obj();
			} else {
				$methods[] = 'WC_Paypal_Express_MX_Gateway';
			}
			if ( ! isset( $_GET['tab'] ) || 'checkout' !== $_GET['tab'] ) { // @codingStandardsIgnoreLine
				$methods[] = 'WC_Paypal_Installment_Gateway';
			}
			return $methods;
		}

		/**
		 * Allow PayPal domains for redirect.
		 *
		 * @param array $domains Whitelisted domains for `wp_safe_redirect`.
		 *
		 * @return array $domains Whitelisted domains for `wp_safe_redirect`
		 *
		 * @since 1.0.0
		 */
		public function whitelist_paypal_domains_for_redirect( $domains ) {
			$domains[] = 'www.paypal.com';
			$domains[] = 'paypal.com';
			$domains[] = 'www.sandbox.paypal.com';
			$domains[] = 'sandbox.paypal.com';
			return $domains;
		}

		/**
		 * WooCommerce fallback notice.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function woocommerce_missing_notice() {
			/* translators: %1$s: is the URL of WooCommerce  */
			echo '<div class="error"><p>' . esc_html( sprintf( __( 'PayPal Gateway depends of WooCommerce on the last version of %1$s to work!', 'woocommerce-paypal-express-mx' ), '<a href="http://wordpress.org/extend/plugins/woocommerce/">' . __( 'WooCommerce', 'woocommerce-paypal-express-mx' ) . '</a>' ) ) . '</p></div>';
		}

		/**
		 * WooCommerce version fallback notice.
		 *
		 * @return  void
		 *
		 * @since 1.0.0
		 */
		public function woocommerce_missing_version_notice() {
			echo '<div class="error"><p>' . esc_html( __( 'WooCommerce Gateway PayPal Express Checkout MX requires WooCommerce version 2.5 or greater', 'woocommerce-paypal-express-mx' ) ) . '</p></div>';
		}

		/**
		 * Check Curl is Installed
		 *
		 * @return bool
		 *
		 * @since 1.0.0
		 */
		private static function woocommerce_missing_curl() {
			if ( ! function_exists( 'curl_init' ) ) {
				WC_Admin_Settings::add_error( __( 'WooCommerce Gateway PayPal Express Checkout requires cURL to be installed on your server', 'woocommerce-paypal-express-mx' ) );
				return true;
			}
			return false;
		}

		/**
		 * Check OpenSSL is Installed
		 *
		 * @return bool
		 *
		 * @since 1.0.0
		 */
		private static function woocommerce_missing_openssl() {
			$openssl_warning = __( 'WooCommerce Gateway PayPal Express Checkout requires OpenSSL >= 1.0.1 to be installed on your server', 'woocommerce-paypal-express-mx' );
			if ( ! defined( 'OPENSSL_VERSION_TEXT' ) ) {
				WC_Admin_Settings::add_error( $openssl_warning );
				return true;
			}
			preg_match( '/^OpenSSL ([\d.]+)/', OPENSSL_VERSION_TEXT, $matches );
			if ( empty( $matches[1] ) ) {
				WC_Admin_Settings::add_error( $openssl_warning );
				return true;
			}
			if ( ! version_compare( $matches[1], '1.0.1', '>=' ) ) {
				WC_Admin_Settings::add_error( $openssl_warning );
				return true;
			}
			return false;
		}
		/**
		 * Whether currency has decimal restriction for PPCE to functions?
		 *
		 * @return bool True if it has restriction otherwise false
		 *
		 * @since 1.0.0
		 */
		public static function currency_has_decimal_restriction() {
			return (
				in_array( get_woocommerce_currency(), array( 'HUF', 'TWD', 'JPY' ), true )
				&&
				0 !== absint( get_option( 'woocommerce_price_num_decimals', 2 ) )
			);
		}
		/**
		 * Checks if currency in setting supports 0 decimal places.
		 *
		 * @return bool Returns true if currency supports 0 decimal places
		 *
		 * @since 1.0.0
		 */
		public static function is_currency_supports_zero_decimal() {
			return in_array( get_woocommerce_currency(), array( 'HUF', 'JPY', 'TWD' ), true );
		}

		/**
		 * Get number of digits after the decimal point.
		 *
		 * @return int Number of digits after the decimal point. Either 2 or 0
		 *
		 * @since 1.0.0
		 */
		public static function get_number_of_decimal_digits() {
			return self::is_currency_supports_zero_decimal() ? 0 : 2;
		}
		/**
		 * Add admin links.
		 *
		 * @param array $links List of links from WordPress.
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		function add_action_links( $links ) {
			$new_links = array(
				'<a style="font-weight: bold;color: #3b7bbf" href="' . self::get_admin_link() . '">' . __( 'Settings', 'woocommerce-paypal-express-mx' ) . '</a>',
			);
			return array_merge( $links, $new_links );
		}
		/**
		 * Backwards compatibility with version prior to 2.1.
		 *
		 * @return object Returns the main instance of WooCommerce class.
		 *
		 * @since 1.0.0
		 */
		public static function woocommerce_instance() {
			if ( function_exists( 'WC' ) ) {
				return WC();
			} else {
				global $woocommerce;
				return $woocommerce;
			}
		}
		/**
		 * Link to setting page.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public static function get_admin_link() {
			if ( version_compare( self::woocommerce_instance()->version, '2.6', '>=' ) ) {
				$section_slug = 'ppexpress_mx';
			} else {
				$section_slug = strtolower( 'WC_Paypal_Express_MX_Gateway' );
			}
			return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $section_slug );
		}
		/**
		 * Load the plugin text domain for translation.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function load_plugin_textdomain() {
			$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-paypal-express-mx' );
			load_textdomain( 'woocommerce-paypal-express-mx', trailingslashit( WP_LANG_DIR ) . 'woocommerce-paypal-express-mx/woocommerce-paypal-express-mx-' . $locale . '.mo' );
			load_plugin_textdomain( 'woocommerce-paypal-express-mx', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}
	}
	/**
	 * Callback to Add Metabox to WP-Admin
	 *
	 * @since 1.0.0
	 */
	function ppexpress_mx_metabox_cb() {
		$woocommerce = PPWC();
		$woocommerce->payment_gateways();
		do_action( 'woocommerce_ppexpress_mx_metabox' );
	}
	/**
	 * Add Metabox to WP-Admin
	 *
	 * @since 1.0.0
	 */
	function ppexpress_mx_metabox() {
		add_meta_box( 'ppexpress_mx-metabox', __( 'Paypal Information', 'woocommerce-paypal-express-mx' ), 'ppexpress_mx_metabox_cb', 'shop_order', 'normal', 'high' );
	}
	add_action( 'add_meta_boxes', 'ppexpress_mx_metabox' );
	/**
	 * Install actions.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	function ppexpress_mx_activate() {
		include_once dirname( __FILE__ ) . '/includes/class-wc-paypal-express-mx-gateway.php';
		WC_Paypal_Express_MX::get_instance();
		WC_Paypal_Express_MX_Gateway::check_database();
	}
	/**
	 * Unistall actions.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	function ppexpress_mx_uninstall() {
		// include_once dirname(__FILE__) . '/includes/class-wc-paypal-express-mx-gateway.php'; //...
		// WC_Paypal_Express_MX::get_instance(); //...
		// WC_Paypal_Express_MX_Gateway::uninstall_database(); //...
	}
	register_activation_hook( __FILE__, 'ppexpress_mx_activate' );
	register_uninstall_hook( __FILE__, 'ppexpress_mx_uninstall' );
	add_action( 'plugins_loaded', array( 'WC_Paypal_Express_MX', 'get_instance' ), 0 );
	/**
	 * Initialize WooCommerce.
	 *
	 * @since 1.0.0
	 */
	function PPWC() { // @codingStandardsIgnoreLine
		return WC_Paypal_Express_MX::woocommerce_instance();
	}
endif;

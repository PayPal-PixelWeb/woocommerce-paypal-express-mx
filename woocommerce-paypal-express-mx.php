<?php
/**
 * Plugin Name: PayPal Express Checkout MX-Latam
 * Plugin URI: https://github.com/PayPal-PixelWeb/PayPal-Woo
 * Description: PayPal Express Checkout MX-Latam
 * Author: PayPal, Leivant, PixelWeb, Kijam
 * Author URI: https://github.com/PayPal-PixelWeb/PayPal-Woo
 * Version: 1.0.0
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
	 */
	class WC_Paypal_Express_MX {

		/**
	 * Plugin version.
	 *
	 * @var string
	 */
		const VERSION = '1.0.0';

		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		protected static $instance = null;

		/**
		 * Initialize the plugin.
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
				include_once 'includes/class-wc-paypal-express-mx-gateway.php';
				add_action( 'woocommerce_init', array( $this, 'woocommerce_loaded' ) );
				add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
			} else {
				add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			}
		}
		/**
		 * Take care of anything that needs woocommerce to be loaded.
		 * For instance, if you need access to the $woocommerce global
		 */
		public function woocommerce_loaded() {
			$this->gateway = WC_Paypal_Express_MX_Gateway::obj();
		}
		/**
		 * Return an instance of this class.
		 *
		 * @return object A single instance of this class.
		 */
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( null === self::$instance ) {
				self::$instance = new self;
			}
			return self::$instance;
		}
		/**
		 * Add the gateway to WooCommerce.
		 *
		 * @param   array $methods WooCommerce payment methods.
		 *
		 * @return  array          Payment methods with Paypal.
		 */
		public function add_gateway( $methods ) {
			if ( version_compare( self::woocommerce_instance()->version, '2.3.0', '>=' ) ) {
				$methods[] = WC_Paypal_Express_MX_Gateway::obj();
			} else {
				$methods[] = 'WC_Paypal_Express_MX_Gateway';
			}
			return $methods;
		}

		/**
		 * WooCommerce fallback notice.
		 *
		 * @return  void
		 */
		public function woocommerce_missing_notice() {
			$txt = '<div class="error"><p>';
			$txt .= sprintf(
				/* translators: %s: is url of woocommerce plugin. */
			__( 'PayPal Express Checkout depends on the last version of %s to work!', 'woocommerce-paypal-express-mx' ), '<a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a>' ) . '</p></div>';
		}
		/**
		 * Backwards compatibility with version prior to 2.1.
		 *
		 * @return object Returns the main instance of WooCommerce class.
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
		 * @return  string
		 */
		public static function get_admin_link() {
			if ( version_compare( self::woocommerce_instance()->version, '2.6', '>=' ) ) {
				$section_slug = 'ppexpress_latam';
			} else {
				$section_slug = strtolower( 'WC_Paypal_Express_MX_Gateway' );
			}
			return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $section_slug );
		}
		/**
		 * Load the plugin text domain for translation.
		 *
		 * @return void
		 */
		public function load_plugin_textdomain() {
			$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-paypal-express-mx' );
			load_textdomain( 'woocommerce-paypal-express-mx', trailingslashit( WP_LANG_DIR ) . 'woocommerce-paypal-express-mx/woocommerce-paypal-express-mx-' . $locale . '.mo' );
			load_plugin_textdomain( 'woocommerce-paypal-express-mx', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}
	}
	/**
	 * Add admin links.
	 *
	 * @param array $links List of links from Wordpress.
	 *
	 * @return array
	 */
	function ppexpress_mx_add_action_links( $links ) {
		 $new_links = array(
		'<a style="font-weight: bold;color: #3b7bbf" href="' . WC_Paypal_Express_MX::get_admin_link() . '">' . __( 'Settings', 'woocommerce-paypal-express-mx' ) . '</a>',
			 );
			return array_merge( $links, $new_links );
	}
	/**
	 * Install actions.
	 *
	 * @return void
	 */
	function ppexpress_mx_activate() {
		include_once dirname( __FILE__ ) . '/includes/class-wc-paypal-mx-gateway.php';
		WC_Paypal_Express_MX::get_instance();
		WC_Paypal_Express_MX_Gateway::check_database();
	}
	/**
	 * Unistall actions.
	 *
	 * @return void
	 */
	function ppexpress_mx_uninstall() {
		// include_once dirname(__FILE__) . '/includes/class-wc-paypal-mx-gateway.php'; //...
		// WC_Paypal_Express_MX::get_instance(); //...
		// WC_Paypal_Express_MX_Gateway::uninstall_database(); //...
	}
	register_activation_hook( __FILE__, 'ppexpress_mx_activate' );
	register_uninstall_hook( __FILE__, 'ppexpress_mx_uninstall' );
	add_action( 'plugins_loaded', array( 'WC_Paypal_Express_MX', 'get_instance' ), 0 );
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ppexpress_mx_add_action_links' );
endif;

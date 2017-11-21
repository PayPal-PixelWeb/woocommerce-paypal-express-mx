<?php
/**
 * Logo for WooCommerce Plugin.
 *
 * @package   WooCommerce -> Paypal Express Checkout MX
 * @author    Kijam Lopez <info@kijam.com>
 * @license   Apache-2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * PayPal CDN Logos
 */
class WC_PayPal_Logos {
	/**
	 * Instance of this class.
	 *
	 * @var object
	 *
	 * @since 1.0.0
	 */
	private static $instance = null;
	/**
	 * Instance of cart hanlder.
	 *
	 * @var object
	 *
	 * @since 1.0.0
	 */
	private static $cart_handler = null;
	/**
	 * List of PayPal Logos.
	 *
	 * @var array
	 *
	 * @since 1.0.0
	 */
	private static $images = array(
		'logo' => array(
			'white_s' => 'https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_37x23.jpg',
			'white_m' => 'https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_74x46.jpg',
			'white_l' => 'https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_111x69.jpg',
			'transparent_s' => 'https://www.paypalobjects.com/webstatic/en_US/i/buttons/PP_logo_h_100x26.png',
			'transparent_m' => 'https://www.paypalobjects.com/webstatic/en_US/i/buttons/PP_logo_h_150x38.png',
			'transparent_l' => 'https://www.paypalobjects.com/webstatic/en_US/i/buttons/PP_logo_h_200x51.png',
		),
		'es' => array(
			'pay_with' => 'https://www.paypalobjects.com/webstatic/mktg/logo-center/logotipo_paypal_pagos.png',
		),
		'en' => array(
			'pay_with' => 'https://www.paypalobjects.com/digitalassets/c/website/marketing/na/us/logo-center/9_bdg_secured_by_pp_2line.png',
		),
	);
	/**
	 * Settings of Plugin.
	 *
	 * @var array
	 *
	 * @since 1.0.0
	 */
	private $settings = null;
	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
        
		$this->settings = (array) get_option( 'woocommerce_ppexpress_mx_settings', array() );
		if ( true === WC_Paypal_Express_MX_Gateway::obj()->is_configured() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'wc_ajax_wc_ppexpress_update_cart', array( $this, 'wc_ajax_update_cart' ) );
			add_action( 'wc_ajax_wc_ppexpress_dummy', array( $this, 'wc_ppexpress_dummy' ) );
			add_action( 'wc_ajax_wc_ppexpress_generate_cart', array( $this, 'wc_ajax_generate_cart' ) );
			if ( 'yes' === $this->get_option( 'cart_checkout_enabled' ) ) {
				add_action( 'woocommerce_widget_shopping_cart_buttons', array( $this, 'widget_paypal_button' ), 20 );
				add_action( 'woocommerce_proceed_to_checkout', array( $this, 'display_paypal_button_checkout' ), 20 );
			}
			if ( 'yes' === $this->get_option( 'product_checkout_enabled' ) ) {
				add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'display_paypal_button_product' ), 1 );
			}
			if ( 'yes' === $this->get_option( 'paypal_logo_footer' ) ) {
				add_action( 'wp_footer', array( $this, 'footer_logo' ) );
			}
			$this->checkout_mode = $this->get_option( 'checkout_mode' );
			$this->show_modal = (bool) apply_filters( 'woocommerce_paypal_express_checkout_show_cart_modal', in_array( $this->checkout_mode, array( 'modal_on_checkout', 'modal' ), true ) );
		}
	}
	/**
	 * Dummy request...
	 *
	 * @since 1.0.0
	 */
	public function wc_ppexpress_dummy() {
		wp_send_json( new stdClass() );
		exit;
	}
	/**
	 * Paypal Widget Button.
	 *
	 * @since 1.0.0
	 */
	function widget_paypal_button() {
		echo wp_kses_post( apply_filters( 'ppexpress_widget_paypal_button', '<div class="btn_ppexpress_mx_widget" style="width: 100%;text-align: center;"></div>' ) );
	}
	/**
	 * Paypal Product Button.
	 *
	 * @since 1.0.0
	 */
	function display_paypal_button_product() {
		echo wp_kses_post( apply_filters( 'ppexpress_display_paypal_button_product', '<div id="btn_ppexpress_mx_product" style="width: 100%;text-align: center;"></div>' ) );
	}
	/**
	 * Paypal Checkout Button.
	 *
	 * @since 1.0.0
	 */
	function display_paypal_button_checkout() {
		echo wp_kses_post( apply_filters( 'ppexpress_display_paypal_button_checkout_separator', '<div style="text-align:center;width:100%;color: #b6b6b6;">' . __( '&mdash; or &mdash;', 'woocommerce-paypal-express-mx' ) . '</div>' ) );
		echo wp_kses_post( apply_filters( 'ppexpress_display_paypal_button_checkout', '<div id="btn_ppexpress_mx_cart" style="width: 100%;text-align: center;"></div>' ) );
	}
	/**
	 * Reload totals before checkout handler when cart is loaded.
	 *
	 * @since 1.0.0
	 */
	public function wc_ajax_update_cart() {
		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}
		PPWC()->shipping->reset_shipping();
		PPWC()->cart->calculate_totals();
		PPWC()->session->set( 'paypal_mx', array() );
		$token = WC_PayPal_Cart_Handler_Latam::obj()->start_checkout( array(
			'start_from' => 'cart',
			'return_token' => true,
		) );
		wp_send_json( array(
			'is_ok' => PPWC()->cart,
			'paymentID' => $token,
		) );
		exit;
	}
	/**
	 * Generates the cart for express checkout on a product level.
	 *
	 * @since 1.0.0
	 */
	public function wc_ajax_generate_cart() {
		global $post;
		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}

		PPWC()->shipping->reset_shipping();

		/**
		 * If this page is single product page, we need to simulate
		 * adding the product to the cart taken account if it is a
		 * simple or variable product.
		 */
		if ( is_product() ) {
			PPWC()->cart->empty_cart();
			$product = wc_get_product( $post->ID );
			$qty     = ! isset( $_POST['qty'] ) ? 1 : absint( $_POST['qty'] ); // @codingStandardsIgnoreLine

			if ( $product->is_type( 'variable' ) ) {
				if ( ! isset( $_POST['attributes'] ) || ! is_array( $_POST['attributes'] ) ) { // @codingStandardsIgnoreLine
					$_POST['attributes'] = array();
				}
				$attributes = array_map( 'wc_clean', $_POST['attributes'] ); // @codingStandardsIgnoreLine

				if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
					$variation_id = $product->get_matching_variation( $attributes );
				} else {
					$data_store = WC_Data_Store::load( 'product' );
					$variation_id = $data_store->find_matching_product_variation( $product, $attributes );
				}

				PPWC()->cart->add_to_cart( $product->get_id(), $qty, $variation_id, $attributes );
			} elseif ( $product->is_type( 'simple' ) ) {
				PPWC()->cart->add_to_cart( $product->get_id(), $qty );
			}

			PPWC()->cart->calculate_totals();
			PPWC()->session->set( 'paypal_mx', array() );
			$token = WC_PayPal_Cart_Handler_Latam::obj()->start_checkout( array(
				'start_from' => 'cart',
				'return_token' => true,
			) );
			wp_send_json( array(
				'is_ok' => PPWC()->cart,
				'paymentID' => $token,
			) );
			exit;
		}

		wp_send_json( new stdClass() );
	}
	/**
	 * Generates the cart for express checkout on a product level.
	 *
	 * @param string $name Name of button.
	 *
	 * @return string URL
	 *
	 * @since 1.0.0
	 */
	static public function get_button( $name ) {
		static $lang = false;
		if ( false === $lang ) {
			$lang = substr( get_bloginfo( 'language' ), 0, 2 );
			if ( 'es' !== $lang ) {
				$lang = 'en';
			}
		}
		return isset( self::$images[ $lang ][ $name ] ) ? self::$images[ $lang ][ $name ] : '';
	}
	/**
	 * Display logo on Footer.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	function footer_logo() {
		echo apply_filters( 'ppexpress_footer', '<div style="width: 100%;height: 120px;background-color: #211d71;"><a href="https://paypal.com/" target="_blank"><img style="margin: auto;padding-top: 20px;width: 100%;max-width: 700px;" src="' . plugins_url( '../img/banner-big.jpg', __FILE__ ) . '" /></a></div>' ); // @codingStandardsIgnoreLine
	}
	/**
	 * Get options.
	 *
	 * @param sttring     $key Key of setting.
	 * @param bool|string $default Default value if Key not exists.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	private function get_option( $key, $default = false ) {
		return isset( $this->settings[ $key ] ) && ! empty( $this->settings[ $key ] ) ? $this->settings[ $key ] : $default ;
	}
	/**
	 * Frontend scripts
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		if ( true !== WC_Paypal_Express_MX_Gateway::obj()->is_configured() ) {
			return;
		}
		if ( is_product() ) {
			wp_enqueue_script( 'wc-ppexpress-checkout-js', 'https://www.paypalobjects.com/api/checkout.js', array(), null, true );
			wp_enqueue_script( 'wc-ppexpress-product-js', plugins_url( 'woocommerce-paypal-express-mx/js/front.product.js' , basename( __FILE__ ) ), array( 'jquery' ), WC_Paypal_Express_MX::VERSION, true );
			wp_localize_script( 'wc-ppexpress-product-js', 'wc_ppexpress_product_context',
				array(
					'payer_id'      => WC_PayPal_Interface_Latam::get_payer_id(),
					'environment'   => WC_PayPal_Interface_Latam::get_env(),
					'locale'        => $this->get_option( 'button_locale', 'es_ES' ),
					'style'         => array(
						'size'  => $this->get_option( 'button_size_product', 'medium' ),
						'color' => $this->get_option( 'button_color', 'gold' ),
						'shape' => $this->get_option( 'button_type', 'pill' ),
						'label' => 'checkout',
					),
					'style_widget'         => array(
						'size'  => 'small',
						'color' => $this->get_option( 'button_color', 'gold' ),
						'shape' => $this->get_option( 'button_type', 'pill' ),
						'label' => 'checkout',
					),
					'att_empty'     => __( 'Please select all attributes', 'woocommerce-paypal-express-mx' ),
					'pp_error'      => __( 'Error sending you cart to paypal, try later please', 'woocommerce-paypal-express-mx' ),
					'flow_method'   => $this->checkout_mode,
					'show_modal'    => $this->show_modal,
					'token_product' => wp_create_nonce( 'ppexpress_token_product' ),
					'ppexpress_generate_cart_url' => WC_AJAX::get_endpoint( 'wc_ppexpress_generate_cart' ),
					'token_cart'    => wp_create_nonce( 'ppexpress_token_cart' ),
					'ppexpress_update_cart_url' => WC_AJAX::get_endpoint( 'wc_ppexpress_update_cart' ),
				)
			);
		} elseif ( is_checkout() ) {
			wp_enqueue_script( 'wc-ppexpress-checkout-js', 'https://www.paypalobjects.com/api/checkout.js', array(), null, true );
			wp_enqueue_script( 'wc-ppexpress-front-js', plugins_url( 'woocommerce-paypal-express-mx/js/front.hacking-checkout.js' , basename( __FILE__ ) ), array( 'jquery' ), WC_Paypal_Express_MX::VERSION, true );
			wp_localize_script( 'wc-ppexpress-front-js', 'wc_ppexpress_cart_context',
				array(
					'payer_id'      => WC_PayPal_Interface_Latam::get_payer_id(),
					'environment'   => WC_PayPal_Interface_Latam::get_env(),
					'locale'        => $this->get_option( 'button_locale', 'es_ES' ),
					'style'         => array(
						'size'  => $this->get_option( 'button_size_cart', 'medium' ),
						'color' => $this->get_option( 'button_color', 'gold' ),
						'shape' => $this->get_option( 'button_type', 'pill' ),
						'label' => 'checkout',
					),
					'style_widget'         => array(
						'size'  => 'small',
						'color' => $this->get_option( 'button_color', 'gold' ),
						'shape' => $this->get_option( 'button_type', 'pill' ),
						'label' => 'checkout',
					),
					'btn_banner' => plugins_url( '../img/banner-payment-btn.jpg', __FILE__ ),
					'att_empty'     => __( 'Please select all attributes', 'woocommerce-paypal-express-mx' ),
					'pp_error'      => __( 'Error sending you cart to paypal, try later please', 'woocommerce-paypal-express-mx' ),
					'flow_method'   => $this->checkout_mode,
					'start_flow'    => esc_url( add_query_arg( array(
						'ppexpress_mx' => 'true',
					), wc_get_page_permalink( 'cart' ) ) ),
					'show_modal'    => $this->show_modal,
					'is_express'    => isset( $_GET['ppexpress-mx-return'] ) && 'true' === $_GET['ppexpress-mx-return'], // @codingStandardsIgnoreLine
					'token_cart'    => wp_create_nonce( 'ppexpress_token_cart' ),
					'ppexpress_update_cart_url' => WC_AJAX::get_endpoint( 'wc_ppexpress_update_cart' ),
					'ppexpress_dummy_ajax_url' => WC_AJAX::get_endpoint( 'wc_ppexpress_dummy' ),
				)
			);
		} else {
			wp_enqueue_script( 'wc-ppexpress-checkout-js', 'https://www.paypalobjects.com/api/checkout.js', array(), null, true );
			wp_enqueue_script( 'wc-ppexpress-front-js', plugins_url( 'woocommerce-paypal-express-mx/js/front.cart.js' , basename( __FILE__ ) ), array( 'jquery' ), WC_Paypal_Express_MX::VERSION, true );
			wp_localize_script( 'wc-ppexpress-front-js', 'wc_ppexpress_cart_context',
				array(
					'payer_id'      => WC_PayPal_Interface_Latam::get_payer_id(),
					'environment'   => WC_PayPal_Interface_Latam::get_env(),
					'att_empty'     => __( 'Please select all attributes', 'woocommerce-paypal-express-mx' ),
					'pp_error'      => __( 'Error sending you cart to paypal, try later please', 'woocommerce-paypal-express-mx' ),
					'flow_method'   => $this->checkout_mode,
					'locale'        => $this->get_option( 'button_locale', 'es_ES' ),
					'style'         => array(
						'size'  => $this->get_option( 'button_size_cart', 'medium' ),
						'color' => $this->get_option( 'button_color', 'gold' ),
						'shape' => $this->get_option( 'button_type', 'pill' ),
						'label' => 'checkout',
					),
					'style_widget'         => array(
						'size'  => 'small',
						'color' => $this->get_option( 'button_color', 'gold' ),
						'shape' => $this->get_option( 'button_type', 'pill' ),
						'label' => 'checkout',
					),
					'start_flow'    => esc_url( add_query_arg( array(
						'ppexpress_mx' => 'true',
					), wc_get_page_permalink( 'cart' ) ) ),
					'show_modal'    => $this->show_modal,
					'token_cart'    => wp_create_nonce( 'ppexpress_token_cart' ),
					'ppexpress_update_cart_url' => WC_AJAX::get_endpoint( 'wc_ppexpress_update_cart' ),
				)
			);
		}// End if().
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
	 * Return Paypal Logo
	 *
	 * @param sttring $size Size of logo.
	 * @param string  $type Type of Logo.
	 *
	 * @return string URL
	 *
	 * @since 1.0.0
	 */
	static public function get_logo( $size = 's', $type = 'transparent' ) {
		return isset( self::$images['logo'][ $type . '_' . $size ] ) ? self::$images['logo'][ $type . '_' . $size ] : '';
	}
	/**
	 * Return size list available for Paypal Logos.
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	static public function get_logo_sizes() {
		return array(
			's' => __( 'Small', 'woocommerce-paypal-express-mx' ),
			'm' => __( 'Medium', 'woocommerce-paypal-express-mx' ),
			'l' => __( 'Large', 'woocommerce-paypal-express-mx' ),
		);
	}
	/**
	 * Return type list available for Paypal Logos (White/Transparent).
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	static public function get_logo_types() {
		return array(
			'white' => __( 'White', 'woocommerce-paypal-express-mx' ),
			'transparent' => __( 'Transparent', 'woocommerce-paypal-express-mx' ),
		);
	}
}

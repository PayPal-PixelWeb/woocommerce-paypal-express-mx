<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * PayPal API Interface
 */
class WC_PayPal_Logos {
	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	private static $instance = null;
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
			'paypal_accepted' => 'https://www.paypalobjects.com/webstatic/mktg/logo-center/logotipo_paypal_seguridad.png',
			'paypal_payment_1' => 'https://www.paypalobjects.com/webstatic/mktg/logo/AM_SbyPP_mc_vs_dc_ae.jpg',
			'paypal_payment_2' => 'https://www.paypalobjects.com/webstatic/mktg/logo/AM_mc_vs_dc_ae.jpg'
		),
		'en' => array(
			'pay_with' => 'https://www.paypalobjects.com/digitalassets/c/website/marketing/na/us/logo-center/9_bdg_secured_by_pp_2line.png',
			'paypal_accepted' => 'https://www.paypalobjects.com/digitalassets/c/website/marketing/na/us/logo-center/15_nowaccepting_blue_badge.jpg',
			'paypal_payment_1' => 'https://www.paypalobjects.com/webstatic/mktg/logo/AM_SbyPP_mc_vs_dc_ae.jpg',
			'paypal_payment_2' => 'https://www.paypalobjects.com/webstatic/mktg/logo/AM_mc_vs_dc_ae.jpg'
		),
		'checkout' => array(
			'squere_blue_small' => 'https://www.paypalobjects.com/webstatic/en_US/i/btn/png/blue-rect-paypalcheckout-26px.png',
			'squere_blue_medium' => 'https://www.paypalobjects.com/webstatic/en_US/i/btn/png/blue-rect-paypalcheckout-34px.png',
			'squere_blue_large' => 'https://www.paypalobjects.com/webstatic/en_US/i/btn/png/blue-rect-paypalcheckout-44px.png',

			'oval_blue_small' => 'https://www.paypalobjects.com/webstatic/en_US/i/btn/png/blue-pill-paypalcheckout-26px.png',
			'oval_blue_medium' => 'https://www.paypalobjects.com/webstatic/en_US/i/btn/png/blue-pill-paypalcheckout-34px.png',
			'oval_blue_large' => 'https://www.paypalobjects.com/webstatic/en_US/i/btn/png/blue-pill-paypalcheckout-44px.png',

			'squere_gold_small' => 'https://www.paypalobjects.com/webstatic/en_US/i/btn/png/gold-rect-paypalcheckout-26px.png',
			'squere_gold_medium' => 'https://www.paypalobjects.com/webstatic/en_US/i/btn/png/gold-rect-paypalcheckout-34px.png',
			'squere_gold_large' => 'https://www.paypalobjects.com/webstatic/en_US/i/btn/png/gold-rect-paypalcheckout-44px.png',

			'oval_gold_small' => 'https://www.paypalobjects.com/webstatic/en_US/i/btn/png/gold-pill-paypalcheckout-26px.png',
			'oval_gold_medium' => 'https://www.paypalobjects.com/webstatic/en_US/i/btn/png/gold-pill-paypalcheckout-34px.png',
			'oval_gold_large' => 'https://www.paypalobjects.com/webstatic/en_US/i/btn/png/gold-pill-paypalcheckout-44px.png',

			'squere_silver_small' => 'https://www.paypalobjects.com/webstatic/en_US/i/btn/png/silver-rect-paypalcheckout-26px.png',
			'squere_silver_medium' => 'https://www.paypalobjects.com/webstatic/en_US/i/btn/png/silver-rect-paypalcheckout-34px.png',
			'squere_silver_large' => 'https://www.paypalobjects.com/webstatic/en_US/i/btn/png/silver-rect-paypalcheckout-44px.png',

			'oval_silver_small' => 'https://www.paypalobjects.com/webstatic/en_US/i/btn/png/silver-pill-paypalcheckout-26px.png',
			'oval_silver_medium' => 'https://www.paypalobjects.com/webstatic/en_US/i/btn/png/silver-pill-paypalcheckout-34px.png',
			'oval_silver_large' => 'https://www.paypalobjects.com/webstatic/en_US/i/btn/png/silver-pill-paypalcheckout-44px.png'
		)
	);
	private $settings = null;
	/**
	 * Initialize the plugin.
	 */
	private function __construct() {
		$this->settings = (array) get_option( 'woocommerce_ppexpress_latam_settings', array() );
		if ( true === WC_Paypal_Express_MX_Gateway::obj()->is_configured() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			if ( 'yes' == $this->get_option( 'cart_checkout_enabled' ) ) {
				add_action( 'woocommerce_before_cart_totals', array( $this, 'before_cart_totals' ) );
				add_action( 'woocommerce_widget_shopping_cart_buttons', array( $this, 'widget_paypal_button' ), 20 );
				add_action( 'woocommerce_proceed_to_checkout', array( $this, 'display_paypal_button_checkout' ), 20 );
				add_action( 'wc_ajax_wc_ppexpress_update_shipping_costs', array( $this, 'wc_ajax_update_shipping_costs' ) );
			}
			if ( 'yes' == $this->get_option( 'product_checkout_enabled' ) ) {
				add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'display_paypal_button_product' ), 1 );
				add_action( 'wc_ajax_wc_ppexpress_generate_cart', array( $this, 'wc_ajax_generate_cart' ) );
			}
			if ( 'yes' == $this->get_option( 'paypal_logo_footer' ) ) {
				add_action( 'wp_footer', array( $this, 'footer_logo' ) );
			}
		}
	}
	function footer_logo() {
		echo apply_filters( 'ppexpress_footer', '<div style="width: 100%;height: 100px;background-color: #003087;"><a href="https://paypal.com/" target="_blank"><img style="margin: auto;padding-top: 23px;" src="' . self::get_button( 'paypal_accepted' ) . '" /></a></div>' );
	}
	function widget_paypal_button() {
		echo '<a href="https://paypal.com/" target="_blank">';
		echo apply_filters( 'ppexpress_widget_paypal_button', '<img style="margin: 10px auto;" src="' . self::get_button_checkout( $this->get_option( 'button_type' ), $this->get_option( 'button_color' ), 'small' ) . '" />' );
		echo '</a>';
	}
	function display_paypal_button_product() {
		echo '<a href="https://paypal.com/" target="_blank">';
		echo apply_filters( 'ppexpress_display_paypal_button_product', '<img style="margin: 10px auto;" src="' . self::get_button_checkout( $this->get_option( 'button_type' ), $this->get_option( 'button_color' ), $this->get_option( 'button_size_product' ) ) . '" />' );
		echo '</a>';
	}
	function display_paypal_button_checkout() {
		echo apply_filters( 'ppexpress_display_paypal_button_checkout_separator', '<div style="text-align:center;width:100%;color: #b6b6b6;">' . __( '&mdash; or &mdash;', 'woocommerce-paypal-express-mx' ) . '</div>' );
		echo '<a href="https://paypal.com/" target="_blank">';
		echo apply_filters( 'ppexpress_display_paypal_button_checkout', '<img style="margin: 10px auto;" src="' . self::get_button_checkout( $this->get_option( 'button_type' ), $this->get_option( 'button_color' ), $this->get_option( 'button_size_cart' ) ) . '" />' );
		echo '</a>';
	}
	function before_cart_totals() {

	}
	/**
	 * Get options.
	 */
	private function get_option( $key ) {
		return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : false ;
	}
	/**
	 * Frontend scripts
	 */
	public function enqueue_scripts() {
		if ( true !== WC_Paypal_Express_MX_Gateway::obj()->is_configured() ) {
			return;
		}
		// wp_enqueue_style( 'wc-ppexpress-front-css', plugins_url( 'woocommerce-paypal-express-mx/css/front.css' , basename( __FILE__ ) ) );
		if ( is_cart() ) {
			wp_enqueue_script( 'wc-ppexpress-checkout-js', 'https://www.paypalobjects.com/api/checkout.js', array(), null, true );
			wp_enqueue_script( 'wc-ppexpress-front-js', plugins_url( 'woocommerce-paypal-express-mx/js/front.cart.js' , basename( __FILE__ ) ), array( 'jquery' ), WC_Paypal_Express_MX::VERSION, true );
			wp_localize_script( 'wc-ppexpress-front-js', 'wc_ppexpress_cart_context',
				array(
					'payer_id'      => WC_PayPal_Interface_Latam::get_payer_id(),
					'environment'   => WC_PayPal_Interface_Latam::get_env(),
					'locale'        => WC_PayPal_Interface_Latam::get_locale(),
					'start_flow'    => esc_url( add_query_arg( array(
						'ppexpress' => 'true',
					), wc_get_page_permalink( 'cart' ) ) ),
					'show_modal'    => apply_filters( 'woocommerce_paypal_express_checkout_show_cart_modal', true ),
					'token_cart'    => wp_create_nonce( 'ppexpress_token_cart' ),
					'ppexpress_url' => WC_AJAX::get_endpoint( 'wc_ajax_ppexpress_shipping_costs' ),
				)
			);
		}
		if ( is_product() ) {
			wp_enqueue_script( 'wc-ppexpress-product-js', plugins_url( 'woocommerce-paypal-express-mx/js/front.product.js' , basename( __FILE__ ) ), array( 'jquery' ), WC_Paypal_Express_MX::VERSION, true );
			wp_localize_script( 'wc-ppexpress-product-js', 'wc_ppexpress_product_context',
				array(
					'token_product' => wp_create_nonce( 'ppexpress_token_product' ),
					'ppexpress_url' => WC_AJAX::get_endpoint( 'wc_ajax_ppexpress_generate_cart' ),
				)
			);
		}
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
	static public function get_logo( $size = 's', $type = 'transparent' ) {
		return isset( self::$images['logo'][ $type . '_' . $size ] ) ? self::$images['logo'][ $type . '_' . $size ] : '';
	}
	static public function get_logo_sizes() {
		return array(
			's' => __( 'Small', 'woocommerce-paypal-express-mx' ),
			'm' => __( 'Medium', 'woocommerce-paypal-express-mx' ),
			'l' => __( 'Large', 'woocommerce-paypal-express-mx' ),
		);
	}
	static public function get_logo_types() {
		return array(
			'white' => __( 'White', 'woocommerce-paypal-express-mx' ),
			'transparent' => __( 'Transparent', 'woocommerce-paypal-express-mx' ),
		);
	}
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
	static public function get_button_checkout( $format, $color, $size ) {
		$name = "{$format}_{$color}_{$size}";
		return isset( self::$images['checkout'][ $name ] ) ? self::$images['checkout'][ $name ] : '';
	}
}

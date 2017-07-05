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
			'transparent_l' => 'https://www.paypalobjects.com/webstatic/en_US/i/buttons/PP_logo_h_200x51.png'
		),
		'es' => array(
			'pay_with' => 'https://www.paypalobjects.com/webstatic/mktg/logo-center/logotipo_paypal_pagos.png',
			'paypal_accepted' => 'https://www.paypalobjects.com/webstatic/mktg/logo-center/logotipo_paypal_seguridad.png',
			'paypal_payment_1' => 'https://www.paypalobjects.com/webstatic/mktg/logo/AM_SbyPP_mc_vs_dc_ae.jpg',
			'paypal_payment_2' => 'https://www.paypalobjects.com/webstatic/mktg/logo/AM_mc_vs_dc_ae.jpg',
			'paypal_checkout' => 'https://www.paypalobjects.com/webstatic/en_US/i/btn/png/gold-rect-paypalcheckout-26px.png'
		),
		'en' => array(
			'pay_with' => 'https://www.paypalobjects.com/digitalassets/c/website/marketing/na/us/logo-center/9_bdg_secured_by_pp_2line.png',
			'paypal_accepted' => 'https://www.paypalobjects.com/digitalassets/c/website/marketing/na/us/logo-center/15_nowaccepting_blue_badge.jpg',
			'paypal_payment_1' => 'https://www.paypalobjects.com/webstatic/mktg/logo/AM_SbyPP_mc_vs_dc_ae.jpg',
			'paypal_payment_2' => 'https://www.paypalobjects.com/webstatic/mktg/logo/AM_mc_vs_dc_ae.jpg',
			'paypal_checkout' => 'https://www.paypalobjects.com/webstatic/en_US/i/btn/png/gold-rect-paypalcheckout-26px.png'
		),
	);
	private $settings = null;
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
	static public function get_logo($size = 's', $type = 'transparent') {
		return isset( self::$images['logo'][ $type . '_' . $size ] ) ? self::$images['logo'][ $type . '_' . $size ] : '';
	}
	static public function get_logo_sizes() {
		return array('s' => __('Small', 'woocommerce-paypal-express-mx' ), 'm' => __('Medium', 'woocommerce-paypal-express-mx' ), 'l' => __('Large', 'woocommerce-paypal-express-mx' ));
	}
	static public function get_logo_types() {
		return array('white' => __('White', 'woocommerce-paypal-express-mx' ), 'transparent' => __('Transparent', 'woocommerce-paypal-express-mx' ));
	}
	static public function get_button($name) {
		static $lang = false;
		if ( false === $lang ) {
			$lang = substr(get_bloginfo ( 'language' ), 0, 2);
			if ( 'es' !== $lang ) {
				$lang = 'en';
			}
		}
		return isset(self::$images[$lang][$name]) ? self::$images[$lang][$name] : '';
	}
}

<?php
/**
 * Cart Handler for WooCommerce Plugin.
 *
 * @package   WooCommerce -> Paypal Express Checkout MX
 * @author    Kijam Lopez <info@kijam.com>
 * @license   Apache-2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use PayPal\CoreComponentTypes\BasicAmountType;
use PayPal\EBLBaseComponents\PaymentDetailsItemType;
use PayPal\EBLBaseComponents\PaymentDetailsType;
use PayPal\EBLBaseComponents\AddressType;
use PayPal\EBLBaseComponents\SetExpressCheckoutRequestDetailsType;
use PayPal\EBLBaseComponents\DoExpressCheckoutPaymentRequestDetailsType;
use PayPal\PayPalAPI\SetExpressCheckoutReq;
use PayPal\PayPalAPI\SetExpressCheckoutRequestType;
use PayPal\PayPalAPI\DoExpressCheckoutPaymentReq;
use PayPal\PayPalAPI\DoExpressCheckoutPaymentRequestType;
use PayPal\PayPalAPI\GetExpressCheckoutDetailsReq;
use PayPal\PayPalAPI\GetExpressCheckoutDetailsResponseType;
use PayPal\PayPalAPI\GetExpressCheckoutDetailsRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;

/**
 * PayPal API Interface Hander from Cart of WooCommerce
 */
class WC_PayPal_Cart_Handler_Latam {
	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	static private $instance;
	/**
	 * Parse city/state of API to Real Name
	 *
	 * @var array
	 */
	static private $parse_state = array(
		'CIUDAD AUTÓNOMA DE BUENOS AIRES' => 'Buenos Aires (Ciudad)',
		'BUENOS AIRES' => 'Buenos Aires (Provincia)',
		'CATAMARCA' => 'Catamarca',
		'CHACO' => 'Chaco',
		'CHUBUT' => 'Chubut',
		'CORRIENTES' => 'Corrientes',
		'CÓRDOBA' => 'Córdoba',
		'ENTRE RÍOS' => 'Entre Ríos',
		'FORMOSA' => 'Formosa',
		'JUJUY' => 'Jujuy',
		'LA PAMPA' => 'La Pampa',
		'LA RIOJA' => 'La Rioja',
		'MENDOZA' => 'Mendoza',
		'MISIONES' => 'Misiones',
		'NEUQUÉN' => 'Neuquén',
		'RÍO NEGRO' => 'Río Negro',
		'SALTA' => 'Salta',
		'SAN JUAN' => 'San Juan',
		'SAN LUIS' => 'San Luis',
		'SANTA CRUZ' => 'Santa Cruz',
		'SANTA FE' => 'Santa Fe',
		'SANTIAGO DEL ESTERO' => 'Santiago del Estero',
		'TIERRA DEL FUEGO' => 'Tierra del Fuego',
		'TUCUMÁN' => 'Tucumán',
		'AC' => 'Acre',
		'AL' => 'Alabama',
		'AP' => 'Armed Forces Pacific',
		'AM' => 'Amazonas',
		'BA' => 'Bari',
		'CE' => 'Caserta',
		'DF' => 'Distrito Federal',
		'ES' => 'Espírito Santo',
		'GO' => 'Gorizia',
		'MA' => 'Massachusetts',
		'MT' => 'Montana',
		'MS' => 'Mississippi',
		'MG' => 'Minas Gerais',
		'PR' => 'Puerto Rico',
		'PB' => 'Paraíba',
		'PA' => 'Pennsylvania',
		'PE' => 'Pescara',
		'PI' => 'Pisa',
		'RN' => 'Rimini',
		'RS' => 'Rio Grande do Sul',
		'RJ' => 'Rio de Janeiro',
		'RO' => 'Rovigo',
		'RR' => 'Roraima',
		'SC' => 'South Carolina',
		'SE' => 'Sergipe',
		'SP' => 'La Spezia',
		'TO' => 'Torino',
		'AB' => 'Alberta',
		'BC' => 'Baja California',
		'MB' => 'Monza e della Brianza',
		'NB' => 'New Brunswick',
		'NL' => 'Nuevo León',
		'NT' => 'Northwest Territories',
		'NS' => 'Nova Scotia',
		'NU' => 'Nuoro',
		'ON' => 'Ontario',
		'QC' => 'Quebec',
		'SK' => 'Saskatchewan',
		'YT' => 'Yukon',
		'Andaman and Nicobar Islands' => 'Andaman and Nicobar Islands',
		'Andhra Pradesh' => 'Andhra Pradesh',
		'APO' => 'Army Post Office',
		'Arunachal Pradesh' => 'Arunachal Pradesh',
		'Assam' => 'Assam',
		'Bihar' => 'Bihar',
		'Chandigarh' => 'Chandigarh',
		'Chhattisgarh' => 'Chhattisgarh',
		'Dadra and Nagar Haveli' => 'Dadra and Nagar Haveli',
		'Daman and Diu' => 'Daman and Diu',
		'Goa' => 'Goa',
		'Gujarat' => 'Gujarat',
		'Haryana' => 'Haryana',
		'Himachal Pradesh' => 'Himachal Pradesh',
		'Jammu and Kashmir' => 'Jammu and Kashmir',
		'Jharkhand' => 'Jharkhand',
		'Karnataka' => 'Karnataka',
		'Kerala' => 'Kerala',
		'Lakshadweep' => 'Lakshadweep',
		'Madhya Pradesh' => 'Madhya Pradesh',
		'Maharashtra' => 'Maharashtra',
		'Manipur' => 'Manipur',
		'Meghalaya' => 'Meghalaya',
		'Mizoram' => 'Mizoram',
		'Nagaland' => 'Nagaland',
		'Delhi (NCT)' => 'National Capital Territory of Delhi',
		'Odisha' => 'Odisha',
		'Puducherry' => 'Puducherry',
		'Punjab' => 'Punjab',
		'Rajasthan' => 'Rajasthan',
		'Sikkim' => 'Sikkim',
		'Tamil Nadu' => 'Tamil Nadu',
		'Telangana' => 'Telangana',
		'Tripura' => 'Tripura',
		'Uttar Pradesh' => 'Uttar Pradesh',
		'Uttarakhand' => 'Uttarakhand',
		'West Bengal' => 'West Bengal',
		'AG' => 'Agrigento',
		'AN' => 'Ancona',
		'AO' => 'Aosta',
		'AR' => 'Arkansas',
		'AT' => 'Asti',
		'AV' => 'Avellino',
		'BT' => 'Barletta-Andria-Trani',
		'BL' => 'Belluno',
		'BN' => 'Benevento',
		'BG' => 'Bergamo',
		'BI' => 'Biella',
		'BO' => 'Bologna',
		'BZ' => 'Bolzano',
		'BS' => 'Brescia',
		'BR' => 'Brindisi',
		'CA' => 'California',
		'CL' => 'Caltanissetta',
		'CB' => 'Campobasso',
		'CI' => 'Carbonia-Iglesias',
		'CT' => 'Connecticut',
		'CZ' => 'Catanzaro',
		'CH' => 'Chieti',
		'CO' => 'Colorado',
		'CS' => 'Cosenza',
		'CR' => 'Cremona',
		'KR' => 'Crotone',
		'CN' => 'Cuneo',
		'EN' => 'Enna',
		'FM' => 'Federated States of Micronesia',
		'FE' => 'Ferrara',
		'FI' => 'Firenze',
		'FG' => 'Foggia',
		'FC' => 'Forlì-Cesena',
		'FR' => 'Frosinone',
		'GE' => 'Genova',
		'GR' => 'Grosseto',
		'IM' => 'Imperia',
		'IS' => 'Isernia',
		'AQ' => "L'Aquila",
		'LT' => 'Latina',
		'LE' => 'Lecce',
		'LC' => 'Lecco',
		'LI' => 'Livorno',
		'LO' => 'Lodi',
		'LU' => 'Lucca',
		'MC' => 'Macerata',
		'MN' => 'Minnesota',
		'VS' => 'Medio Campidano',
		'ME' => 'Maine',
		'MI' => 'Michigan',
		'MO' => 'Missouri',
		'NA' => 'Napoli',
		'NO' => 'Novara',
		'OG' => 'Ogliastra',
		'OT' => 'Olbia-Tempio',
		'OR' => 'Oregon',
		'PD' => 'Padova',
		'PV' => 'Pavia',
		'PG' => 'Perugia',
		'PU' => 'Pesaro e Urbino',
		'PC' => 'Piacenza',
		'PT' => 'Pistoia',
		'PN' => 'Pordenone',
		'PZ' => 'Potenza',
		'PO' => 'Prato',
		'RG' => 'Ragusa',
		'RA' => 'Ravenna',
		'RC' => 'Reggio Calabria',
		'RE' => 'Reggio Emilia',
		'RI' => 'Rhode Island',
		'RM' => 'Roma',
		'SA' => 'Salerno',
		'SS' => 'Sassari',
		'SV' => 'Savona',
		'SI' => 'Siena',
		'SR' => 'Siracusa',
		'SO' => 'Sondrio',
		'TA' => 'Taranto',
		'TE' => 'Teramo',
		'TR' => 'Terni',
		'TP' => 'Trapani',
		'TN' => 'Tennessee',
		'TV' => 'Treviso',
		'TS' => 'Trieste',
		'UD' => 'Udine',
		'VA' => 'Virginia',
		'VE' => 'Venezia',
		'VB' => 'Verbano-Cusio-Ossola',
		'VC' => 'Vercelli',
		'VR' => 'Verona',
		'VV' => 'Vibo Valentia',
		'VI' => 'Virgin Islands',
		'VT' => 'Vermont',
		'AICHI-KEN' => 'Aichi',
		'AKITA-KEN' => 'Akita',
		'AOMORI-KEN' => 'Aomori',
		'CHIBA-KEN' => 'Chiba',
		'EHIME-KEN' => 'Ehime',
		'FUKUI-KEN' => 'Fukui',
		'FUKUOKA-KEN' => 'Fukuoka',
		'FUKUSHIMA-KEN' => 'Fukushima',
		'GIFU-KEN' => 'Gifu',
		'GUNMA-KEN' => 'Gunma',
		'HIROSHIMA-KEN' => 'Hiroshima',
		'HOKKAIDO' => 'Hokkaido',
		'HYOGO-KEN' => 'Hyogo',
		'IBARAKI-KEN' => 'Ibaraki',
		'ISHIKAWA-KEN' => 'Ishikawa',
		'IWATE-KEN' => 'Iwate',
		'KAGAWA-KEN' => 'Kagawa',
		'KAGOSHIMA-KEN' => 'Kagoshima',
		'KANAGAWA-KEN' => 'Kanagawa',
		'KOCHI-KEN' => 'Kochi',
		'KUMAMOTO-KEN' => 'Kumamoto',
		'KYOTO-FU' => 'Kyoto',
		'MIE-KEN' => 'Mie',
		'MIYAGI-KEN' => 'Miyagi',
		'MIYAZAKI-KEN' => 'Miyazaki',
		'NAGANO-KEN' => 'Nagano',
		'NAGASAKI-KEN' => 'Nagasaki',
		'NARA-KEN' => 'Nara',
		'NIIGATA-KEN' => 'Niigata',
		'OITA-KEN' => 'Oita',
		'OKAYAMA-KEN' => 'Okayama',
		'OKINAWA-KEN' => 'Okinawa',
		'OSAKA-FU' => 'Osaka',
		'SAGA-KEN' => 'Saga',
		'SAITAMA-KEN' => 'Saitama',
		'SHIGA-KEN' => 'Shiga',
		'SHIMANE-KEN' => 'Shimane',
		'SHIZUOKA-KEN' => 'Shizuoka',
		'TOCHIGI-KEN' => 'Tochigi',
		'TOKUSHIMA-KEN' => 'Tokushima',
		'TOKYO-TO' => 'Tokyo',
		'TOTTORI-KEN' => 'Tottori',
		'TOYAMA-KEN' => 'Toyama',
		'WAKAYAMA-KEN' => 'Wakayama',
		'YAMAGATA-KEN' => 'Yamagata',
		'YAMAGUCHI-KEN' => 'Yamaguchi',
		'YAMANASHI-KEN' => 'Yamanashi',
		'AGS' => 'Aguascalientes',
		'BCS' => 'Baja California Sur',
		'CAMP' => 'Campeche',
		'CHIS' => 'Chiapas',
		'CHIH' => 'Chihuahua',
		'COAH' => 'Coahuila',
		'COL' => 'Colima',
		'DGO' => 'Durango',
		'MEX' => 'Estado de México',
		'GTO' => 'Guanajuato',
		'GRO' => 'Guerrero',
		'HGO' => 'Hidalgo',
		'JAL' => 'Jalisco',
		'MICH' => 'Michoacán',
		'MOR' => 'Morelos',
		'NAY' => 'Nayarit',
		'OAX' => 'Oaxaca',
		'PUE' => 'Puebla',
		'QRO' => 'Querétaro',
		'Q ROO' => 'Quintana Roo',
		'SLP' => 'San Luis Potosí',
		'SIN' => 'Sinaloa',
		'SON' => 'Sonora',
		'TAB' => 'Tabasco',
		'TAMPS' => 'Tamaulipas',
		'TLAX' => 'Tlaxcala',
		'VER' => 'Veracruz',
		'YUC' => 'Yucatán',
		'ZAC' => 'Zacatecas',
		'AK' => 'Alaska',
		'AZ' => 'Arizona',
		'DE' => 'Delaware',
		'DC' => 'District of Columbia',
		'FL' => 'Florida',
		'GA' => 'Georgia',
		'HI' => 'Hawaii',
		'ID' => 'Idaho',
		'IL' => 'Illinois',
		'IN' => 'Indiana',
		'IA' => 'Iowa',
		'KS' => 'Kansas',
		'KY' => 'Kentucky',
		'LA' => 'Louisiana',
		'MD' => 'Maryland',
		'NE' => 'Nebraska',
		'NV' => 'Nevada',
		'NH' => 'New Hampshire',
		'NJ' => 'New Jersey',
		'NM' => 'New Mexico',
		'NY' => 'New York',
		'NC' => 'North Carolina',
		'ND' => 'North Dakota',
		'OH' => 'Ohio',
		'OK' => 'Oklahoma',
		'SD' => 'South Dakota',
		'TX' => 'Texas',
		'UT' => 'Utah',
		'WA' => 'Washington',
		'WV' => 'West Virginia',
		'WI' => 'Wisconsin',
		'WY' => 'Wyoming',
		'AA' => 'Armed Forces Americas',
		'AE' => 'Armed Forces Europe',
		'AS' => 'American Samoa',
		'GU' => 'Guam',
		'MH' => 'Marshall Islands',
		'MP' => 'Northern Mariana Islands',
		'PW' => 'Palau',
	);
	/**
	 * Initialize the plugin.
	 */
	private function __construct() {
		$this->settings = (array) get_option( 'woocommerce_ppexpress_mx_settings', array() );
		if ( ! empty( $_GET['ppexpress-mx-return'] ) ) { // @codingStandardsIgnoreLine
			$session    = PPWC()->session->get( 'paypal_mx', array() );
			if ( ! empty( $_GET['token'] ) // @codingStandardsIgnoreLine
				&& ! empty( $_GET['PayerID'] ) // @codingStandardsIgnoreLine
				&& isset( $session['start_from'] )
				&& 'cart' === $session['start_from'] ) {
				add_action( 'woocommerce_checkout_init', array( $this, 'checkout_init' ) );
				add_filter( 'woocommerce_default_address_fields', array( $this, 'filter_default_address_fields' ) );
				add_filter( 'woocommerce_billing_fields', array( $this, 'filter_billing_fields' ) );
				add_action( 'woocommerce_checkout_process', array( $this, 'copy_checkout_details_to_post' ) );

				add_action( 'woocommerce_cart_emptied', array( $this, 'maybe_clear_session_data' ) );

				add_action( 'woocommerce_available_payment_gateways', array( $this, 'maybe_disable_other_gateways' ) );
				add_action( 'woocommerce_review_order_after_submit', array( $this, 'maybe_render_cancel_link' ) );

				add_action( 'woocommerce_cart_shipping_packages', array( $this, 'maybe_add_shipping_information' ) );
			}
		}
	}
	/**
	 * Get instance of this class.
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
	 * Get options.
	 *
	 * @param string $key Key of Option.
	 *
	 * @return mixed
	 *
	 * @since 1.0.0
	 */
	private function get_option( $key ) {
		return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : false ;
	}
	/**
	 * Used when cart based Checkout with PayPal is in effect. Hooked to woocommerce_cart_emptied
	 *
	 * @since 1.0.0
	 */
	public function maybe_clear_session_data() {
		PPWC()->session->set( 'paypal_mx', array() );
	}
	/**
	 * If there's an active PayPal session during checkout (e.g. if the customer started checkout
	 * with PayPal from the cart), import billing and shipping details from PayPal using the
	 * token we have for the customer.
	 *
	 * Hooked to the woocommerce_checkout_init action
	 *
	 * @param object $checkout is WP_Checkout object.
	 *
	 * @since 1.0.0
	 */
	function checkout_init( $checkout ) {
		// Since we've removed the billing and shipping checkout fields, we should also remove the
		// billing and shipping portion of the checkout form.
		remove_action( 'woocommerce_checkout_billing', array( $checkout, 'checkout_form_billing' ) );
		remove_action( 'woocommerce_checkout_shipping', array( $checkout, 'checkout_form_shipping' ) );

		add_action( 'woocommerce_checkout_billing', array( $this, 'paypal_billing_details' ) );
		add_action( 'woocommerce_checkout_shipping', array( $this, 'paypal_shipping_details' ) );
	}
	/**
	 * Show billing information obtained from PayPal. This replaces the billing fields
	 * that the customer would ordinarily fill in. Should only happen if we have an active
	 * session (e.g. if the customer started checkout with PayPal from their cart.)
	 *
	 * Is hooked to woocommerce_checkout_billing action by checkout_init
	 *
	 * @since 1.0.0
	 */
	public function paypal_billing_details() {
		$session    = PPWC()->session->get( 'paypal_mx', array() );
		$token = isset( $_GET['token'] ) ? $_GET['token'] : $session['get_express_token'];  // @codingStandardsIgnoreLine
		$checkout_details = $this->get_checkout( $token );
		if ( false === $checkout_details ) {
			wc_add_notice( __( 'Sorry, an error occurred while trying to retrieve your information from PayPal. Please try again.', 'woocommerce-paypal-express-mx' ), 'error' );
			wp_safe_redirect( wc_get_page_permalink( 'cart' ) );
			exit;
		}
		$billing = $this->get_mapped_billing_address( $checkout_details );
		?>
		<div class="woocommerce-billing-fields__field-wrapper" id="not-popup-ppexpress-mx"></div>
		<h3><?php echo esc_html( __( 'Billing details', 'woocommerce-paypal-express-mx' ) ); ?></h3>
		<ul>
			<?php if ( ! empty( $billing['address_1'] ) ) : ?>
				<li>
					<strong>
						<?php
							echo esc_html( __( 'Address:', 'woocommerce-paypal-express-mx' ) );
						?>
					</strong>
				</br>
				<?php
					echo PPWC()->countries->get_formatted_address( $billing ); // @codingStandardsIgnoreLine
				?>
				</li>
			<?php else : ?>
				<li>
					<strong>
						<?php
							echo esc_html( __( 'Name:', 'woocommerce-paypal-express-mx' ) );
						?>
					</strong>
					<?php
						echo esc_html( $billing ['first_name'] . ' ' . $billing ['last_name'] );
					?>
				</li>
			<?php endif; ?>

			<?php if ( ! empty( $billing ['email'] ) ) : ?>
				<li>
					<strong>
						<?php
							echo esc_html( __( 'Email:', 'woocommerce-paypal-express-mx' ) );
						?>
					</strong>
					<?php
						echo esc_html( $billing ['email'] );
					?>
				</li>
			<?php endif; ?>

			<?php if ( ! empty( $billing ['phone'] ) ) : ?>
				<li>
					<strong>
						<?php
							echo esc_html( __( 'Tel:', 'woocommerce-paypal-express-mx' ) );
						?>
					</strong>
					<?php
						echo esc_html( $billing ['phone'] );
					?>
				</li>
			<?php elseif ( 'yes' === $this->get_option( 'require_phone_number' ) ) : ?>
				<li>
					<?php
						woocommerce_form_field( 'billing_phone', array(
							'label' => __( 'Phone', 'woocommerce-paypal-express-mx' ),
							'required' => true,
							'validate' => array( 'phone' ),
						) );
					?>
				</li>
			<?php endif; ?>
		</ul>
		<?php
	}
	/**
	 * Show shipping information obtained from PayPal. This replaces the shipping fields
	 * that the customer would ordinarily fill in. Should only happen if we have an active
	 * session (e.g. if the customer started checkout with PayPal from their cart.)
	 *
	 * Is hooked to woocommerce_checkout_shipping action by checkout_init
	 *
	 * @since 1.0.0
	 */
	public function paypal_shipping_details() {
		if ( method_exists( PPWC()->cart, 'needs_shipping' ) && ! PPWC()->cart->needs_shipping() ) {
			return;
		}

		$session          = PPWC()->session->get( 'paypal_mx' );
		$token = isset( $_GET['token'] ) ? $_GET['token'] : $session['get_express_token']; // @codingStandardsIgnoreLine
		$checkout_details = $this->get_checkout( $token );
		if ( false === $checkout_details ) {
			wc_add_notice( __( 'Sorry, an error occurred while trying to retrieve your information from PayPal. Please try again.', 'woocommerce-paypal-express-mx' ), 'error' );
			wp_safe_redirect( wc_get_page_permalink( 'cart' ) );
			exit;
		}
		?>
		<h3><?php __( 'Shipping details', 'woocommerce-paypal-express-mx' ); ?></h3>
		<?php
		echo PPWC()->countries->get_formatted_address( $this->get_mapped_shipping_address( $checkout_details ) ); // @codingStandardsIgnoreLine
	}
	/**
	 * This function filter the packages adding shipping information from PayPal on the checkout page
	 * after the user is authenticated by PayPal.
	 *
	 * @param array $packages list of packages.
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function maybe_add_shipping_information( $packages ) {
		$checkout_details = $this->get_checkout( wc_clean( $_GET['token'] ) ); // @codingStandardsIgnoreLine
		if ( true !== $checkout_details ) {
			$destination = $this->get_mapped_shipping_address( $checkout_details );
			$packages[0]['destination']['country']   = $destination['country'];
			$packages[0]['destination']['state']     = $destination['state'];
			$packages[0]['destination']['postcode']  = $destination['postcode'];
			$packages[0]['destination']['city']      = $destination['city'];
			$packages[0]['destination']['address']   = $destination['address_1'];
			$packages[0]['destination']['address_2'] = $destination['address_2'];
		}
		return $packages;
	}

	/**
	 * If the cart doesn't need shipping at all, don't require the address fields
	 * This is one of two places we need to filter fields.
	 * See also filter_billing_fields below.
	 *
	 * @param array $fields list of fields to check.
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function filter_default_address_fields( $fields ) {
		$session    = PPWC()->session->get( 'paypal_mx', array() );
		if ( ! empty( $_GET['token'] ) // @codingStandardsIgnoreLine
			&& ! empty( $_GET['PayerID'] ) // @codingStandardsIgnoreLine
			&& isset( $session['start_from'] ) ) {
			$not_required_fields = array( 'address_1', 'city', 'state', 'postcode', 'country' );
			foreach ( $not_required_fields as $not_required_field ) {
				if ( array_key_exists( $not_required_field, $fields ) ) {
					$fields[ $not_required_field ]['required'] = false;
					$fields[ $not_required_field ]['validate'] = array();
				}
			}
		}

		return $fields;

	}

	/**
	 * When an active session is present, gets (from PayPal) the buyer details
	 * and replaces the appropriate checkout fields in $_POST
	 *
	 * Hooked to woocommerce_checkout_process
	 *
	 * @since 1.0.0
	 */
	public function copy_checkout_details_to_post() {

		$session    = PPWC()->session->get( 'paypal_mx', array() );

		// Make sure the selected payment method is ppexpress_mx.
		if ( ! is_array( $session )
			|| ! isset( $session['start_from'] )
			|| 'cart' !== $session['start_from']
			|| ! isset( $_POST['payment_method'] ) // @codingStandardsIgnoreLine
			|| ( 'ppexpress_mx' !== $_POST['payment_method'] && 'ppexpress_installment_mx' !== $_POST['payment_method'] ) // @codingStandardsIgnoreLine
		) {
			return;
		}
		$token = isset( $_GET['token'] ) ? $_GET['token'] : $session['get_express_token']; // @codingStandardsIgnoreLine

		$checkout_details = $this->get_checkout( $token );
		if ( false !== $checkout_details ) {
			$shipping_details = $this->get_mapped_shipping_address( $checkout_details );
			foreach ( $shipping_details as $key => $value ) {
				$_POST[ 'shipping_' . $key ] = $value;
			}

			$billing_details = $this->get_mapped_billing_address( $checkout_details );
			// If the billing address is empty, copy address from shipping.
			if ( empty( $billing_details['address_1'] ) ) {
				$copyable_keys = array( 'address_1', 'address_2', 'city', 'state', 'postcode', 'country' );
				foreach ( $copyable_keys as $copyable_key ) {
					if ( array_key_exists( $copyable_key, $shipping_details ) ) {
						$billing_details[ $copyable_key ] = $shipping_details[ $copyable_key ];
					}
				}
			}
			foreach ( $billing_details as $key => $value ) {
				if ( 'phone' === $key && empty( $value ) && isset( $_POST['billing_phone'] ) ) { // @codingStandardsIgnoreLine
					continue;
				}
				$_POST[ 'billing_' . $key ] = $value;
			}
		}
	}
	/**
	 * Maybe disable this or other gateways.
	 *
	 * @param array $gateways Available gateways.
	 *
	 * @return array Available gateways
	 *
	 * @since 1.0.0
	 */
	public function maybe_disable_other_gateways( $gateways ) {
		$session    = PPWC()->session->get( 'paypal_mx', array() );
		// Unset all other gateways after checking out from cart.
		if ( isset( $session['start_from'] ) && 'cart' === $session['start_from'] && $session['expire_in'] > time() ) {
			foreach ( $gateways as $id => $gateway ) {
				if ( 'ppexpress_mx' !== $id && 'ppexpress_installment_mx' !== $id ) {
					unset( $gateways[ $id ] );
				}
			}
		}
		return $gateways;
	}

	/**
	 * When cart based Checkout with PP Express is in effect, we need to include
	 * a Cancel button on the checkout form to give the user a means to throw
	 * away the session provided and possibly select a different payment
	 * gateway.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function maybe_render_cancel_link() {
		printf(
			'<a href="%s" class="wc-gateway-ppexpress-mx-cancel">%s</a>',
			esc_url( add_query_arg( 'wc-gateway-ppexpress-mx-clear-session', true, wc_get_cart_url() ) ),
			esc_html__( 'Cancel', 'woocommerce-paypal-express-mx' )
		);
	}
	/**
	 * Since PayPal doesn't always give us the phone number for the buyer, we need to make
	 * that field not required. Note that core WooCommerce adds the phone field after calling
	 * get_default_address_fields, so the woocommerce_default_address_fields cannot
	 * be used to make the phone field not required.
	 *
	 * This is one of two places we need to filter fields. See also filter_default_address_fields above.
	 *
	 * @param array $billing_fields List of fields to check if phone is required.
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function filter_billing_fields( $billing_fields ) {
		if ( array_key_exists( 'billing_phone', $billing_fields ) ) {
			$billing_fields['billing_phone']['required'] = 'yes' === $this->get_option( 'require_phone_number' );
		};
		return $billing_fields;
	}
	/**
	 * Start checkout.
	 *
	 * @param array $args List of fields to check if phone is required.
	 *
	 * @return array
	 *
	 * @throws Exception Capture in LOG File.
	 *
	 * @since 1.0.0
	 */
	public function start_checkout( array $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'start_from'               => 'cart',
				'order_id'                 => '',
				'create_billing_agreement' => false,
				'return_url'               => false,
				'return_token'             => false,
			)
		);
		$session    = PPWC()->session->get( 'paypal_mx', array() );
		$cart_url   = PPWC()->cart->get_cart_url();
		$notify_url = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'wc_gateway_ipn_paypal_latam', home_url( '/' ) ) );
		$return_url = $this->_get_return_url( $args );
		$order = null;
		$details = null;
		$set_express_request = null;
		$old_wc    = version_compare( WC_VERSION, '3.0', '<' );
		try {
			switch ( $args['start_from'] ) {
				case 'checkout':
					$details = $this->_get_details_from_order( $args['order_id'] );
					$order = wc_get_order( $args['order_id'] );
					$cancel_url = $order->get_cancel_order_url();
					break;
				case 'cart':
					$details = $this->get_details_from_cart();
					$cancel_url = $this->_get_cancel_url();
					break;
			}
			if (
				! empty( $session )
				&& isset( $session['order_id'] )
				&& $session['start_from'] === $args['start_from']
				&& $session['order_id'] === $args['order_id']
				&& (float) $session['order_total'] === (float) $details['order_total']
				&& $session['expire_in'] > time()
			) {
				if ( $args['return_url'] ) {
					return $session['set_express_url'];
				}
				if ( $args['return_token'] ) {
					return $session['set_express_token'];
				}
				wp_safe_redirect( $session['set_express_url'] );
				exit;
			}
			$currency = get_woocommerce_currency();
			$item_total = new BasicAmountType();
			$item_total->currencyID = $currency; // @codingStandardsIgnoreLine
			$item_total->value = $details['total_item_amount'];
			$ship_total = new BasicAmountType();
			$ship_total->currencyID = $currency; // @codingStandardsIgnoreLine
			$ship_total->value = $details['shipping'];
			$ship_discount = new BasicAmountType();
			$ship_discount->currencyID = $currency; // @codingStandardsIgnoreLine
			$ship_discount->value = $details['ship_discount_amount'];
			$tax_total = new BasicAmountType();
			$tax_total->currencyID = $currency; // @codingStandardsIgnoreLine
			$tax_total->value = $details['order_tax'];
			$order_total = new BasicAmountType();
			$order_total->currencyID = $currency; // @codingStandardsIgnoreLine
			$order_total->value = $details['order_total'];
			$set_express_details = new SetExpressCheckoutRequestDetailsType();

			$logo_image_url = wp_get_attachment_image_url( $this->get_option( 'logo_image_url' ), 'pplogo' );
			if ( ! empty( $logo_image_url ) ) {
				$set_express_details->cpplogoimage = false !== stristr( $logo_image_url, 'http://' ) ? str_ireplace( 'http://', 'https://images.weserv.nl/?url=' , $logo_image_url ) : $logo_image_url;
			}
			$payment_details = new PaymentDetailsType();
			foreach ( $details['items'] as $idx => $item ) {
				$item_details = new PaymentDetailsItemType();

				$item_details->Name = $item['name']; // @codingStandardsIgnoreLine
				$item_details->Amount = $item['amount']; // @codingStandardsIgnoreLine
				$item_details->Quantity = $item['quantity']; // @codingStandardsIgnoreLine

				/*
				 * Indicates whether an item is digital or physical. For digital goods, this field is required and must be set to Digital
				 */
				$item_details->ItemCategory = 'Physical'; // @codingStandardsIgnoreLine
				$payment_details->PaymentDetailsItem[ $idx ] = $item_details; // @codingStandardsIgnoreLine
			}
			if ( 'checkout' === $args['start_from'] ) {
				$order_id  = $old_wc ? $order->id : $order->get_id();
				$order_key = $old_wc ? $order->order_key : $order->get_order_key();
				$payment_details->InvoiceID = $this->get_option( 'invoice_prefix' ) . $order->get_order_number(); // @codingStandardsIgnoreLine
				$payment_details->Custom = wp_json_encode( array( // @codingStandardsIgnoreLine
					'order_id'  => $order_id,
					'order_key' => $order_key,
				) );
			}
			if ( 'checkout' === $args['start_from'] && 'yes' !== $this->get_option( 'require_confirmed_address' ) ) {
				$address = new AddressType();
				$address->Name            = $details['shipping_address']['name']; // @codingStandardsIgnoreLine
				$address->Street1         = $details['shipping_address']['address1']; // @codingStandardsIgnoreLine
				$address->Street2         = $details['shipping_address']['address2']; // @codingStandardsIgnoreLine
				$address->CityName        = $details['shipping_address']['city']; // @codingStandardsIgnoreLine
				$address->StateOrProvince = $details['shipping_address']['state']; // @codingStandardsIgnoreLine
				$address->Country         = $details['shipping_address']['country']; // @codingStandardsIgnoreLine
				$address->PostalCode      = $details['shipping_address']['zip']; // @codingStandardsIgnoreLine
				$address->Phone           = $details['shipping_address']['phone']; // @codingStandardsIgnoreLine
				$payment_details->ShipToAddress = $address; // @codingStandardsIgnoreLine
				$set_express_details->AddressOverride = 1; // @codingStandardsIgnoreLine
			} else {
				/*
				 * Indicates whether or not you require the buyer's shipping address on file with PayPal be a confirmed address. For digital goods, this field is required, and you must set it to 0. It is one of the following values:
					0 ? You do not require the buyer's shipping address be a confirmed address.
					1 ? You require the buyer's shipping address be a confirmed address.
				 */
				$set_express_details->ReqConfirmShipping = 'yes' === $this->get_option( 'require_confirmed_address' ) ? 1 : 0; // @codingStandardsIgnoreLine
			}
			$payment_details->OrderTotal       = $order_total; // @codingStandardsIgnoreLine
			$payment_details->PaymentAction    = $this->get_option( 'paymentaction' ); // @codingStandardsIgnoreLine
			$payment_details->ItemTotal        = $item_total; // @codingStandardsIgnoreLine
			$payment_details->ShippingTotal    = $ship_total; // @codingStandardsIgnoreLine
			$payment_details->ShippingDiscount = $ship_discount; // @codingStandardsIgnoreLine
			$payment_details->TaxTotal         = $tax_total; // @codingStandardsIgnoreLine
			$payment_details->NotifyURL        = $notify_url; // @codingStandardsIgnoreLine
			$set_express_details->PaymentDetails[0] = $payment_details; // @codingStandardsIgnoreLine
			$set_express_details->CancelURL = $cancel_url; // @codingStandardsIgnoreLine
			$set_express_details->ReturnURL = $return_url; // @codingStandardsIgnoreLine
			if ( in_array( $this->get_option( 'landing_page' ), array( 'Billing', 'Login' ), true ) ) {
				$set_express_details->LandingPage = $this->get_option( 'landing_page' ); // @codingStandardsIgnoreLine
			}

			/*
			 * Determines where or not PayPal displays shipping address fields on the PayPal pages. For digital goods, this field is required, and you must set it to 1. It is one of the following values:
				0 ? PayPal displays the shipping address on the PayPal pages.
				1 ? PayPal does not display shipping address fields whatsoever.
				2 ? If you do not pass the shipping address, PayPal obtains it from the buyer's account profile.
			 */
			$set_express_details->NoShipping = 0; // @codingStandardsIgnoreLine
			$set_express_request_type = new SetExpressCheckoutRequestType();
			$set_express_request_type->SetExpressCheckoutRequestDetails = $set_express_details; // @codingStandardsIgnoreLine
			$set_express_request = new SetExpressCheckoutReq();
			$set_express_request->SetExpressCheckoutRequest = $set_express_request_type; // @codingStandardsIgnoreLine
			$pp_service = WC_PayPal_Interface_Latam::get_static_interface_service();
			$set_express_response = $pp_service->SetExpressCheckout( $set_express_request );
			if ( in_array( $set_express_response->Ack, array( 'Success', 'SuccessWithWarning' ), true ) ) { // @codingStandardsIgnoreLine
				$token = $set_express_response->Token; // @codingStandardsIgnoreLine
				if ( 'sandbox' === WC_PayPal_Interface_Latam::get_env() ) {
					$redirect_url = 'https://www.sandbox.paypal.com/checkoutnow?token=' . $token;
				} else {
					$redirect_url = 'https://www.paypal.com/checkoutnow?token=' . $token;
				}
				// Store values in session.
				$session = array(
					'checkout_completed' => false,
					'start_from'         => $args['start_from'],
					'order_id'           => $args['order_id'],
					'order_total'        => $details['order_total'],
					'payer_id'           => false,
					'expire_in'          => time() + 10800,
					'set_express_token'  => $token,
					'set_express_url'    => $redirect_url,
					'do_express_token'   => false,
				);
				PPWC()->session->set( 'paypal_mx', $session );
				if ( $args['return_url'] ) {
					return $redirect_url;
				}
				if ( $args['return_token'] ) {
					return $token;
				}
				wp_safe_redirect( $redirect_url );
				exit;
			} else {
				throw new Exception( print_r( $set_express_response, true ) ); // @codingStandardsIgnoreLine
			}
			exit;
		} catch ( Exception $e ) {
			WC_Paypal_Logger::obj()->warning( 'Error on start_checkout' . $e->getMessage() );
			WC_Paypal_Logger::obj()->warning( 'DATA for start_checkout', array( $set_express_request ) );
			WC_Paypal_Logger::obj()->warning( 'DATA for session', array( $session ) );
			WC_Paypal_Logger::obj()->warning( 'DATA for details', array( $details ) );
			PPWC()->session->set( 'paypal_mx', array() );
			if ( true === $args['return_url'] || true === $args['return_token'] ) {
				return false;
			}
			ob_end_clean();
			echo '<script type="text/javascript">
				window.location.assign( "<?php echo esc_url( $cart_url ); ?>" );
			</script>';
			exit;
		}// End try().
	}

	/**
	 * Get checkout from Paypal API.
	 *
	 * @param string $token Token.
	 *
	 * @return array
	 *
	 * @throws Exception Capture in LOG File.
	 *
	 * @since 1.0.0
	 */
	public function get_checkout( $token ) {
		$request = new GetExpressCheckoutDetailsReq();
		$request->GetExpressCheckoutDetailsRequest = new GetExpressCheckoutDetailsRequestType( $token ); // @codingStandardsIgnoreLine
		$pp_service = WC_PayPal_Interface_Latam::get_static_interface_service();
		try {
			/* wrap API method calls on the service object with a try catch */
			$response = $pp_service->GetExpressCheckoutDetails( $request );
			if ( in_array( $response->Ack, array( 'Success', 'SuccessWithWarning' ), true ) ) { // @codingStandardsIgnoreLine
				WC_Paypal_Logger::obj()->debug( 'Result on get_checkout', array( $response ) );
				WC_Paypal_Logger::obj()->debug( 'DATA for get_checkout', array( $request ) );
				return $response;
			} else {
				throw new Exception( print_r( $response, true ) ); // @codingStandardsIgnoreLine
			}
		} catch ( Exception $e ) {
			WC_Paypal_Logger::obj()->warning( 'Error on get_checkout: ' . $e->getMessage() );
			WC_Paypal_Logger::obj()->warning( 'DATA for get_checkout', array( $request ) );
			return false;
		}
	}

	/**
	 * Do checkout in Paypal API.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $payer_id Payer ID on Paypal.
	 * @param string $token Token.
	 * @param string $custom Custom ID for Paypal.
	 * @param string $invoice InvoiceID for Patoak.
	 *
	 * @return array
	 *
	 * @throws Exception Capture in LOG File.
	 *
	 * @since 1.0.0
	 */
	public function do_checkout( $order_id, $payer_id, $token, $custom = false, $invoice = false ) {
		$details = $this->_get_details_from_order( $order_id );
		$notify_url = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'wc_gateway_ipn_paypal_latam', home_url( '/' ) ) );
		$order_total = new BasicAmountType();
		$order_total->currencyID = get_woocommerce_currency(); // @codingStandardsIgnoreLine
		$order_total->value = $details['order_total'];
		$payment = new PaymentDetailsType();
		$payment->OrderTotal = $order_total; // @codingStandardsIgnoreLine
		$payment->NotifyURL  = $notify_url; // @codingStandardsIgnoreLine
		if ( false !== $custom ) {
			$payment->Custom  = $custom; // @codingStandardsIgnoreLine
		}
		if ( false !== $invoice ) {
			$payment->InvoiceID  = $invoice; // @codingStandardsIgnoreLine
		}
		$request_type = new DoExpressCheckoutPaymentRequestDetailsType();
		$request_type->PayerID = $payer_id; // @codingStandardsIgnoreLine
		$request_type->Token = $token; // @codingStandardsIgnoreLine
		$request_type->PaymentAction = $this->get_option( 'paymentaction' ); // @codingStandardsIgnoreLine
		$request_type->PaymentDetails[0] = $payment; // @codingStandardsIgnoreLine
		$request_details = new DoExpressCheckoutPaymentRequestType();
		$request_details->DoExpressCheckoutPaymentRequestDetails = $request_type; // @codingStandardsIgnoreLine
		$request = new DoExpressCheckoutPaymentReq();
		$request->DoExpressCheckoutPaymentRequest = $request_details; // @codingStandardsIgnoreLine
		$pp_service = WC_PayPal_Interface_Latam::get_static_interface_service();
		try {
			/* wrap API method calls on the service object with a try catch */
			$response = $pp_service->DoExpressCheckoutPayment( $request );
			if ( in_array( $response->Ack, array( 'Success', 'SuccessWithWarning' ), true ) ) { // @codingStandardsIgnoreLine
				WC_Paypal_Logger::obj()->debug( 'Result on do_checkout', array( $response ) );
				WC_Paypal_Logger::obj()->debug( 'DATA for do_checkout', array( $request ) );
				return $response;
			} else {
				throw new Exception( print_r( $response, true ) ); // @codingStandardsIgnoreLine
			}
		} catch ( Exception $e ) {
			WC_Paypal_Logger::obj()->warning( 'Error on do_checkout: ' . $e->getMessage() );
			WC_Paypal_Logger::obj()->warning( 'DATA for do_checkout: ', array( $request ) );
			return false;
		}
	}

	/**
	 * Get return URL.
	 *
	 * The URL to return from express checkout.
	 *
	 * @param array $context_args {
	 *     Context args to retrieve SetExpressCheckout parameters.
	 *
	 *     @type string $start_from               Start from 'cart' or 'checkout'.
	 *     @type int    $order_id                 Order ID if $start_from is 'checkout'.
	 *     @type bool   $create_billing_agreement Whether billing agreement creation
	 *                                            is needed after returned from PayPal.
	 * }
	 *
	 * @return string Return URL
	 *
	 * @since 1.0.0
	 */
	protected function _get_return_url( array $context_args ) {
		$query_args = array(
			'ppexpress-mx-return' => 'true',
		);
		if ( $context_args['create_billing_agreement'] ) {
			$query_args['create-billing-agreement'] = 'true';
		}

		return add_query_arg( $query_args, wc_get_checkout_url() );
	}

	/**
	 * Get cancel URL. The URL to return when canceling the express checkout.
	 *
	 * @return string Cancel URL
	 *
	 * @since 1.0.0
	 */
	protected function _get_cancel_url() {
		return add_query_arg( 'ppexpress-mx-cancel', 'true', wc_get_cart_url() );
	}

	/**
	 * Get billing agreement description to be passed to PayPal.
	 *
	 * @return string Billing agreement description
	 *
	 * @since 1.0.0
	 */
	protected function _get_billing_agreement_description() {
		/* translators: placeholder is blogname */
		$description = sprintf( _x( 'Orders with %s', 'data sent to PayPal', 'woocommerce-subscriptions' ), get_bloginfo( 'name' ) );

		if ( strlen( $description ) > 127 ) {
			$description = substr( $description, 0, 124 ) . '...';
		}

		return html_entity_decode( $description, ENT_NOQUOTES, 'UTF-8' );
	}

	/**
	 * Get extra line item when for subtotal mismatch.
	 *
	 * @param float $amount Item's amount.
	 *
	 * @return array Line item
	 *
	 * @since 1.0.0
	 */
	protected function _get_extra_offset_line_item( $amount ) {
		return array(
			'name'        => __( 'Line Item Amount Offset', 'woocommerce-paypal-express-mx' ),
			'description' => __( 'Adjust cart calculation discrepancy', 'woocommerce-paypal-express-mx' ),
			'quantity'    => 1,
			'amount'      => $amount,
		);
	}

	/**
	 * Get extra line item when for discount.
	 *
	 * @param float $amount Item's amount.
	 *
	 * @return array Line item
	 *
	 * @since 1.0.0
	 */
	protected function _get_extra_discount_line_item( $amount ) {
		return  array(
			'name'        => __( 'Discount', 'woocommerce-paypal-express-mx' ),
			'description' => __( 'Discount Amount', 'woocommerce-paypal-express-mx' ),
			'quantity'    => 1,
			'amount'      => '-' . $amount,
		);
	}

	/**
	 * Get details, not params to be passed in PayPal API request, from cart contents.
	 *
	 * This is the details when buyer is checking out from cart page.
	 *
	 * @since 1.0.0
	 *
	 * @return array Order details
	 */
	public function get_details_from_cart() {
		$decimals      = WC_Paypal_Express_MX::get_number_of_decimal_digits();
		$discounts     = round( PPWC()->cart->get_cart_discount_total(), $decimals );
		$rounded_total = $this->_get_rounded_total_in_cart();

		$details = array(
			'total_item_amount' => round( PPWC()->cart->cart_contents_total, $decimals ) + $discounts,
			'order_tax'         => round( PPWC()->cart->tax_total + PPWC()->cart->shipping_tax_total, $decimals ),
			'shipping'          => round( PPWC()->cart->shipping_total, $decimals ),
			'items'             => $this->_get_paypal_line_items_from_cart(),
		);

		$details['order_total'] = round(
			$details['total_item_amount'] + $details['order_tax'] + $details['shipping'],
			$decimals
		);

		// Compare WC totals with what PayPal will calculate to see if they match.
		// if they do not match, check to see what the merchant would like to do.
		// Options are to remove line items or add a line item to adjust for
		// the difference.
		if ( (float) $details['total_item_amount'] !== (float) $rounded_total ) {
			if ( 'add' === apply_filters( 'woocommerce_paypal_express_checkout_subtotal_mismatch_behavior', 'add' ) ) {
				// Add line item to make up different between WooCommerce
				// calculations and PayPal calculations.
				$diff = round( $details['total_item_amount'] - $rounded_total, $decimals );
				if ( 0.0 !== $diff ) {
					$extra_line_item = $this->_get_extra_offset_line_item( $diff );
					$details['items'][]            = $extra_line_item;
					$details['total_item_amount'] += $extra_line_item['amount'];
					$details['order_total']       += $extra_line_item['amount'];
				}
			} else {
				// Omit line items altogether.
				unset( $details['items'] );
			}
		}

		// Enter discount shenanigans. Item total cannot be 0 so make modifications
		// accordingly.
		if ( (float) $details['total_item_amount'] === (float) $discounts ) {
			// Omit line items altogether.
			unset( $details['items'] );
			$details['ship_discount_amount'] = 0;
			$details['total_item_amount']   -= $discounts;
			$details['order_total']         -= $discounts;
		} else {
			if ( $discounts > 0 ) {
				$details['items'][] = $this->_get_extra_offset_line_item( - abs( $discounts ) );
			}

			$details['ship_discount_amount'] = 0;
			$details['total_item_amount']   -= $discounts;
			$details['order_total']         -= $discounts;
		}

		// If the totals don't line up, adjust the tax to make it work (it's
		// probably a tax mismatch).
		$wc_order_total = round( PPWC()->cart->total, $decimals );
		if ( (float) $wc_order_total !== (float) $details['order_total'] ) {
			// tax cannot be negative.
			if ( $details['order_total'] < $wc_order_total ) {
				$details['order_tax'] += $wc_order_total - $details['order_total'];
				$details['order_tax'] = round( $details['order_tax'], $decimals );
			} else {
				$details['ship_discount_amount'] += $wc_order_total - $details['order_total'];
				$details['ship_discount_amount'] = round( $details['ship_discount_amount'], $decimals );
			}

			$details['order_total'] = $wc_order_total;
		}

		if ( ! is_numeric( $details['shipping'] ) ) {
			$details['shipping'] = 0;
		}

		return $details;
	}

	/**
	 * Get line items from cart contents.
	 *
	 * @return array Line items
	 *
	 * @since 1.0.0
	 */
	protected function _get_paypal_line_items_from_cart() {
		$decimals = WC_Paypal_Express_MX::get_number_of_decimal_digits();

		$items = array();
		foreach ( PPWC()->cart->cart_contents as $cart_item_key => $values ) {
			$amount = round( $values['line_subtotal'] / $values['quantity'] , $decimals );

			if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
				$name = $values['data']->post->post_title;
				$description = $values['data']->post->post_content;
			} else {
				$product = $values['data'];
				$name = $product->get_name();
				$description = $product->get_description();
			}

			$item   = array(
				'name'        => $name,
				'description' => $description,
				'quantity'    => $values['quantity'],
				'amount'      => $amount,
			);

			$items[] = $item;
		}

		return $items;
	}

	/**
	 * Get rounded total of items in cart.
	 *
	 * @return float Rounded total in cart
	 *
	 * @since 1.0.0
	 */
	protected function _get_rounded_total_in_cart() {
		$decimals = WC_Paypal_Express_MX::get_number_of_decimal_digits();

		$rounded_total = 0;
		foreach ( PPWC()->cart->cart_contents as $cart_item_key => $values ) {
			$amount         = round( $values['line_subtotal'] / $values['quantity'] , $decimals );
			$rounded_total += round( $amount * $values['quantity'], $decimals );
		}

		return $rounded_total;
	}

	/**
	 * Get details from given order_id.
	 *
	 * This is the details when buyer is checking out from checkout page.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array Order details
	 *
	 * @since 1.0.0
	 */
	protected function _get_details_from_order( $order_id ) {
		$order         = wc_get_order( $order_id );
		$decimals      = WC_Paypal_Express_MX::is_currency_supports_zero_decimal() ? 0 : 2;
		$discounts     = round( $order->get_total_discount(), $decimals );
		$rounded_total = $this->_get_rounded_total_in_order( $order );

		$details = array(
			'order_tax'         => round( $order->get_total_tax(), $decimals ),
			'shipping'          => round( ( version_compare( WC_VERSION, '3.0', '<' ) ? $order->get_total_shipping() : $order->get_shipping_total() ), $decimals ),
			'total_item_amount' => round( $order->get_subtotal(), $decimals ),
			'items'             => $this->_get_paypal_line_items_from_order( $order ),
		);

		$details['order_total'] = round( $details['total_item_amount'] + $details['order_tax'] + $details['shipping'], $decimals );

		// Compare WC totals with what PayPal will calculate to see if they match.
		// if they do not match, check to see what the merchant would like to do.
		// Options are to remove line items or add a line item to adjust for
		// the difference.
		if ( (float) $details['total_item_amount'] !== (float) $rounded_total ) {
			if ( 'add' === apply_filters( 'woocommerce_paypal_express_checkout_subtotal_mismatch_behavior', 'add' ) ) {
				// Add line item to make up different between WooCommerce
				// calculations and PayPal calculations.
				$diff = round( $details['total_item_amount'] - $rounded_total, $decimals );

				$details['items'][] = $this->_get_extra_offset_line_item( $diff );

			} else {
				// Omit line items altogether.
				unset( $details['items'] );
			}
		}

		// Enter discount shenanigans. Item total cannot be 0 so make modifications
		// accordingly.
		if ( (float) $details['total_item_amount'] === (float) $discounts ) {
			// Omit line items altogether.
			unset( $details['items'] );
			$details['ship_discount_amount'] = 0;
			$details['total_item_amount']   -= $discounts;
			$details['order_total']         -= $discounts;
		} else {
			if ( $discounts > 0 ) {
				$details['items'][] = $this->_get_extra_discount_line_item( $discounts );

				$details['total_item_amount'] -= $discounts;
				$details['order_total']       -= $discounts;
			}

			$details['ship_discount_amount'] = 0;
		}

		// If the totals don't line up, adjust the tax to make it work (it's
		// probably a tax mismatch).
		$wc_order_total = round( $order->get_total(), $decimals );
		if ( (float) $wc_order_total !== (float) $details['order_total'] ) {
			// tax cannot be negative.
			if ( $details['order_total'] < $wc_order_total ) {
				$details['order_tax'] += $wc_order_total - $details['order_total'];
				$details['order_tax'] = round( $details['order_tax'], $decimals );
			} else {
				$details['ship_discount_amount'] += $wc_order_total - $details['order_total'];
				$details['ship_discount_amount'] = round( $details['ship_discount_amount'], $decimals );
			}

			$details['order_total'] = $wc_order_total;
		}

		if ( ! is_numeric( $details['shipping'] ) ) {
			$details['shipping'] = 0;
		}

		// PayPal shipping address from order.
		$shipping_address = array();

		$old_wc = version_compare( WC_VERSION, '3.0', '<' );

		if ( ( $old_wc && ( $order->shipping_address_1 || $order->shipping_address_2 ) ) || ( ! $old_wc && $order->has_shipping_address() ) ) {
			$shipping_first_name = $old_wc ? $order->shipping_first_name : $order->get_shipping_first_name();
			$shipping_last_name  = $old_wc ? $order->shipping_last_name : $order->get_shipping_last_name();
			$shipping_address_1  = $old_wc ? $order->shipping_address_1 : $order->get_shipping_address_1();
			$shipping_address_2  = $old_wc ? $order->shipping_address_2 : $order->get_shipping_address_2();
			$shipping_city       = $old_wc ? $order->shipping_city : $order->get_shipping_city();
			$shipping_state      = $old_wc ? $order->shipping_state : $order->get_shipping_state();
			$shipping_postcode   = $old_wc ? $order->shipping_postcode : $order->get_shipping_postcode();
			$shipping_country    = $old_wc ? $order->shipping_country : $order->get_shipping_country();
			$shipping_phone      = $old_wc ? $order->billing_phone : $order->get_billing_phone();
		} else {
			// Fallback to billing in case no shipping methods are set. The address returned from PayPal
			// will be stored in the order as billing.
			$shipping_first_name = $old_wc ? $order->billing_first_name : $order->get_billing_first_name();
			$shipping_last_name  = $old_wc ? $order->billing_last_name : $order->get_billing_last_name();
			$shipping_address_1  = $old_wc ? $order->billing_address_1 : $order->get_billing_address_1();
			$shipping_address_2  = $old_wc ? $order->billing_address_2 : $order->get_billing_address_2();
			$shipping_city       = $old_wc ? $order->billing_city : $order->get_billing_city();
			$shipping_state      = $old_wc ? $order->billing_state : $order->get_billing_state();
			$shipping_postcode   = $old_wc ? $order->billing_postcode : $order->get_billing_postcode();
			$shipping_country    = $old_wc ? $order->billing_country : $order->get_billing_country();
			$shipping_phone      = $old_wc ? $order->billing_phone : $order->get_billing_phone();
		}

		$shipping_address['name']     = $shipping_first_name . ' ' . $shipping_last_name;
		$shipping_address['address1'] = $shipping_address_1;
		$shipping_address['address2'] = $shipping_address_2;
		$shipping_address['city']     = $shipping_city;
		$shipping_address['state']    = $shipping_state;
		$shipping_address['zip']      = $shipping_postcode;
		$shipping_address['phone']    = $shipping_phone;

		// In case merchant only expects domestic shipping and hides shipping
		// country, fallback to base country.
		if ( empty( $shipping_country ) ) {
			$shipping_country = PPWC()->countries->get_base_country();
		}
		$shipping_address['country']  = $shipping_country;

		$details['shipping_address'] = $shipping_address;

		return $details;
	}

	/**
	 * Get line items from given order.
	 *
	 * @param int $order Order ID or order object.
	 *
	 * @return array Line items
	 *
	 * @since 1.0.0
	 */
	protected function _get_paypal_line_items_from_order( $order ) {
		$decimals = WC_Paypal_Express_MX::get_number_of_decimal_digits();
		$order    = wc_get_order( $order );

		$items = array();
		foreach ( $order->get_items() as $cart_item_key => $values ) {
			$amount = round( $values['line_subtotal'] / $values['qty'] , $decimals );
			$item   = array(
				'name'     => $values['name'],
				'quantity' => $values['qty'],
				'amount'   => $amount,
			);

			$items[] = $item;
		}

		return $items;
	}

	/**
	 * Get rounded total of a given order.
	 *
	 * @param int $order Order ID or order object.
	 *
	 * @return float
	 *
	 * @since 1.0.0
	 */
	protected function _get_rounded_total_in_order( $order ) {
		$decimals = WC_Paypal_Express_MX::get_number_of_decimal_digits();
		$order    = wc_get_order( $order );

		$rounded_total = 0;
		foreach ( $order->get_items() as $cart_item_key => $values ) {
			$amount         = round( $values['line_subtotal'] / $values['qty'] , $decimals );
			$rounded_total += round( $amount * $values['qty'], $decimals );
		}

		return $rounded_total;
	}

	/**
	 * Map PayPal shipping address to WC shipping address
	 *
	 * @param  object $get_checkout Paypal Checkout Details Object.
	 *
	 * @return array
	 */
	public function get_mapped_shipping_address( $get_checkout ) {
		if ( empty( $get_checkout->GetExpressCheckoutDetailsResponseDetails->PaymentDetails[0] ) // @codingStandardsIgnoreLine
			|| empty( $get_checkout->GetExpressCheckoutDetailsResponseDetails->PaymentDetails[0]->ShipToAddress ) // @codingStandardsIgnoreLine
		) {
			return array();
		}
		$address = $get_checkout->GetExpressCheckoutDetailsResponseDetails->PaymentDetails[0]->ShipToAddress; // @codingStandardsIgnoreLine
		$name       = explode( ' ', $address->Name );
		$first_name = array_shift( $name );
		$last_name  = implode( ' ', $name );
		$state = $address->StateOrProvince; // @codingStandardsIgnoreLine
		if ( isset( self::$parse_state[ $state ] ) ) {
			$state = self::$parse_state[ $state ];
		}
		return array(
			'first_name'    => $first_name,
			'last_name'     => $last_name,
			// 'company'   //...
			'address_1'     => $address->Street1, // @codingStandardsIgnoreLine
			'address_2'     => $address->Street2, // @codingStandardsIgnoreLine
			'city'          => $address->CityName, // @codingStandardsIgnoreLine
			'state'         => $state,
			'postcode'      => $address->PostalCode, // @codingStandardsIgnoreLine
			'country'       => $address->Country, // @codingStandardsIgnoreLine
		);
	}

	/**
	 * Map PayPal billing address to WC shipping address
	 * NOTE: Not all PayPal_Checkout_Payer_Details objects include a billing address
	 *
	 * @param  object $get_checkout Paypal Checkout Details Object.
	 *
	 * @return array
	 */
	public function get_mapped_billing_address( $get_checkout ) {
		if ( false === $get_checkout || empty( $get_checkout->GetExpressCheckoutDetailsResponseDetails->PayerInfo ) ) { // @codingStandardsIgnoreLine
			return array();
		}
		$pp_payer = $get_checkout->GetExpressCheckoutDetailsResponseDetails->PayerInfo; // @codingStandardsIgnoreLine
		if ( $pp_payer->Address ) { // @codingStandardsIgnoreLine
			$state = $pp_payer->Address->StateOrProvince; // @codingStandardsIgnoreLine
			if ( isset( self::$parse_state[ $state ] ) ) {
				$state = self::$parse_state[ $state ];
			}
			return array(
				'first_name' => trim( $pp_payer->PayerName->FirstName . ' ' . $pp_payer->PayerName->MiddleName ), // @codingStandardsIgnoreLine
				'last_name'  => $pp_payer->PayerName->LastName, // @codingStandardsIgnoreLine
				'company'    => '',
				'address_1'  => $pp_payer->Address->Street1, // @codingStandardsIgnoreLine
				'address_2'  => $pp_payer->Address->Street2, // @codingStandardsIgnoreLine
				'city'       => $pp_payer->Address->CityName, // @codingStandardsIgnoreLine
				'state'      => $state,
				'postcode'   => $pp_payer->Address->PostalCode, // @codingStandardsIgnoreLine
				'country'    => $pp_payer->Address->Country, // @codingStandardsIgnoreLine
				'phone'      => ! empty( $pp_payer->Address->Phone ) ? $pp_payer->Address->Phone : $pp_payer->ContactPhone, // @codingStandardsIgnoreLine
				'email'      => $pp_payer->Payer, // @codingStandardsIgnoreLine
			);
		} else {
			return array(
				'first_name' => trim( $pp_payer->PayerName->FirstName . ' ' . $pp_payer->PayerName->MiddleName ), // @codingStandardsIgnoreLine
				'last_name'  => $pp_payer->PayerName->LastName, // @codingStandardsIgnoreLine
				'company'    => '',
				'address_1'  => '',
				'address_2'  => '',
				'city'       => '',
				'state'      => '',
				'postcode'   => '',
				'country'    => '',
				'phone'      => $pp_payer->ContactPhone, // @codingStandardsIgnoreLine
				'email'      => $pp_payer->Payer,
			);

		}
	}

}

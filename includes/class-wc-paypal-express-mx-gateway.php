<?php
/**
 * Plugin Gateway Class.
 */
include_once( dirname( __FILE__ ) . '/override/class-wc-override-payment-gateway.php' );
include_once( dirname( __FILE__ ) . '/class-wc-paypal-interface-latam.php' );
if ( ! class_exists( 'WC_Paypal_Express_MX_Gateway' ) ) :
	/**
	 * WC_Paypal_Express_MX_Gateway Class.
	 */
	class WC_Paypal_Express_MX_Gateway extends WC_Payment_Gateway_Paypal {
		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		static private $instance;
		/**
		 * Constructor for the gateway.
		 *
		 * @return void
		 */
		public function __construct() {
			$this->id              = 'ppexpress_latam';
			$this->icon            = apply_filters( 'woocommerce_ppexpress_latam_icon', plugins_url( 'images/logo.png', plugin_dir_path( __FILE__ ) ) );
			$this->has_fields      = false;
			$this->method_title    = __( 'PayPal Express Checkout MX-Latam', 'woocommerce-paypal-express-mx' );

					$this->check_nonce();

					$this->init_form_fields();
			$this->init_settings();
			// die(print_r($this->settings, true)); //...
			$this->debug = $this->get_option( 'debug' );
			if ( 'yes' !== $this->debug ) {
				WC_Paypal_Logger::set_level( WC_Paypal_Logger::SILENT );
			}
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

			/*
			// Pending...
			add_action( 'woocommerce_paypalexpress_metabox', array( $this, 'woocommerce_paypalexpress_metabox' ) );
			add_action( 'wp_head', array( $this, 'hook_js' ) );
			add_action( 'wp_head', array( $this, 'hook_css' ) );

			// IPN Handler...
			$this->notify_url      = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'ppexpress_latam', home_url( '/' ) ) );
			add_action( 'woocommerce_api_ppexpress_latam', array( $this, 'check_ipn_response' ) );
			 */

					$this->admn_url      = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'ppexpress_latam', home_url( '/' ) ) );
			self::$instance = $this;
			if ( is_user_logged_in() && is_admin() && isset( $_GET['section'] ) && $_GET['section'] === $this->id && empty( $_POST ) ) { // @codingStandardsIgnoreLine
				WC_PayPal_Interface_Latam::obj()->validate_active_credentials( true, true );
			}
		}
		/**
		 * Check nonce's
		 */
		public function check_nonce() {
			if ( isset( $_GET['wc_ppexpress_latam_ips_admin_nonce'] ) ) { // Input var okay.
				include_once( dirname( __FILE__ ) . '/class-wc-paypal-connect-ips.php' );
				$ips = new WC_PayPal_Connect_IPS();
				$ips->maybe_received_credentials();
			}
			if ( isset( $_GET['wc_ppexpress_latam_remove_cert_admin_nonce'] ) // Input var okay.
				&& wp_verify_nonce( sanitize_key( $_GET['wc_ppexpress_latam_remove_cert_admin_nonce'] ), 'wc_ppexpress_latam_remove_cert' ) // Input var okay.
			) {
				@unlink( dirname( __FILE__ ) . '/cert/key_data.pem' ); // @codingStandardsIgnoreLine
				$settings_array = (array) get_option( 'woocommerce_ppexpress_latam_settings', array() );
				$settings_array['api_certificate'] = '';
				$settings_array['api_signature']   = '';
				update_option( 'woocommerce_ppexpress_latam_settings', $settings_array );
				wp_safe_redirect( WC_Paypal_Express_MX::get_admin_link() );
				exit;
			}
		}
		/**
		 * Get instance of this class.
		 */
		static public function get_instance() {
			if ( null === self::$logger ) {
				self::$logger = new self;
			}
			return self::$logger;
		}
		/**
		 * Short alias for get_instance.
		 */
		static public function obj() {
			return self::get_instance();
		}
		/**
		 * Do some additonal validation before saving options.
		 */
		public function process_admin_options() {
			// @codingStandardsIgnoreStart
			// If a certificate has been uploaded, read the contents and save that string instead.
			if ( array_key_exists( 'woocommerce_ppexpress_latam_api_certificate', $_FILES )
			&& array_key_exists( 'tmp_name', $_FILES['woocommerce_ppexpress_latam_api_certificate'] )
			&& array_key_exists( 'size', $_FILES['woocommerce_ppexpress_latam_api_certificate'] )
			&& $_FILES['woocommerce_ppexpress_latam_api_certificate']['size'] ) {
				@unlink( dirname( __FILE__ ) . '/cert/key_data.pem' );
				$_POST['woocommerce_ppexpress_latam_api_certificate'] = base64_encode( file_get_contents( $_FILES['woocommerce_ppexpress_latam_api_certificate']['tmp_name'] ) );
				unlink( $_FILES['woocommerce_ppexpress_latam_api_certificate']['tmp_name'] );
				unset( $_FILES['woocommerce_ppexpress_latam_api_certificate'] );
			} else {
				$_POST['woocommerce_ppexpress_latam_api_certificate'] = $this->get_option( 'api_certificate' );
			}
			// @codingStandardsIgnoreEnd

			parent::process_admin_options();

			// Validate credentials.
			WC_PayPal_Interface_Latam::obj()->validate_active_credentials( true, true );
		}
		/**
		 * Initialise Gateway Settings Form Fields.
		 *
		 * @return void
		 */
		public function init_form_fields() {
			include_once( dirname( __FILE__ ) . '/class-wc-paypal-connect-ips.php' );
			$ips = new WC_PayPal_Connect_IPS();
			$api_creds_text = __( 'Your country store not is supported by PayPal, please change this...', 'woocommerce-paypal-express-mx' );
			if ( $ips->is_supported() ) {
				$live_url = $ips->get_signup_url( 'live' );
				$sandbox_url = $ips->get_signup_url( 'sandbox' );
				$api_creds_text = sprintf(
					/* translators: %1$s: is URL of auto-get Live API Credential. %2$s: is URL of manual-get Live API Credential.  */
					__( '- To get NVP/SOAP API integration credential <a href="%1$s">click here</a> or get this fields manualy <a href="%2$s" target="_blank">here</a><br />', 'woocommerce-paypal-express-mx' ),
					$live_url,
					'https://www.paypal.com/businessprofile/mytools/apiaccess/firstparty'
				);
				$api_creds_text .= sprintf(
					/* translators: %1$s: is URL of auto-get Sandbox API Credential. %2$s: is URL of manual-get Sandbox API Credential.  */
					__( '- To get Sandbox NVP/SOAP API integration credential <a href="%1$s">click here</a> or get this fields manualy <a href="%2$s" target="_blank">here</a>', 'woocommerce-paypal-express-mx' ),
					$sandbox_url,
					'https://www.sandbox.paypal.com/businessprofile/mytools/apiaccess/firstparty'
				);
			}
			$api_certificate = $this->get_option( 'api_certificate' );
			$api_certificate_msg = '';
			if ( ! empty( $api_certificate ) ) {
				$cert = @openssl_x509_read( base64_decode( $api_certificate ) ); // @codingStandardsIgnoreLine
				if ( false !== $cert ) {
					$cert_info   = openssl_x509_parse( $cert );
					$valid_until = $cert_info['validTo_time_t'];
					$api_certificate_msg = sprintf(
						/* translators: %1$s: is date of expire. %2$s: is URL for delete certificate.  */
						__( 'API certificate is <b>VALID</b> and exire on: <b>%1$s</b>, <a href="%2$s">click here</a> for remove', 'woocommerce-paypal-express-mx' ),
						date( 'Y-m-d', $valid_until ),
						add_query_arg(
							array(
							'wc_ppexpress_latam_remove_cert_admin_nonce' => wp_create_nonce( 'wc_ppexpress_latam_remove_cert' ),
							),
							WC_Paypal_Express_MX::get_admin_link()
						)
					);

				} else {
					$api_certificate_msg = __( 'API certificate is <b>INVALID</b>.', 'woocommerce-paypal-express-mx' );
				}
			}
			$currency_org = get_woocommerce_currency();
			$this->form_fields = include( dirname( __FILE__ ) . '/setting/data-settings-payment.php' );
		}
		/**
		 * Set Metadata of Order.
		 *
		 * @param   int    $order_id Order ID.
		 * @param   string $key Key of Metadata.
		 * @param   mixed  $value Value of Metadata.
		 *
		 * @return  bool   Result of Database.
		 */
		static public function set_metadata( $order_id, $key, $value ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'woo_ppexpress_mx';
			$exists = $wpdb->get_var($wpdb->prepare(  // @codingStandardsIgnoreLine
				"SELECT `id` FROM 
                `%s`
            WHERE
                    `order_id` = %d
                AND 
                    `key` = '%s'
            LIMIT 1",
				$table_name,
				(int) $order_id,
				$key
			));
			if ( $exists ) {
				$result = $wpdb->update( $table_name, // @codingStandardsIgnoreLine
					array(
						'data' => wp_json_encode( $value ),
					),
					array(
						'order_id' => $order_id,
						'key' => $key,
					),
					array( '%s' ),
				array( '%d', '%s' ) );
			} else {
				$result = $wpdb->insert( $table_name, // @codingStandardsIgnoreLine
					array(
					'order_id' => $order_id,
					'key' => $key,
					'data' => wp_json_encode( $value ),
				), array( '%d', '%s', '%s' ) );
			}
			wp_cache_set( 'ppmetadata-' . $order_id, $value, 'ppmetadata' );
			WC_Paypal_Logger::obj()->debug( "set_metadata [order:{$order_id}]: [{$key}]=>" . print_r( $value, true ) . ' Result: ' . print_r( $result, true ) );
			return $result;
		}
		/**
		 * Get Metadata of Order.
		 *
		 * @param   int    $order_id Order ID.
		 * @param   string $key Key of Metadata.
		 *
		 * @return  mixed  Value of metadata.
		 */
		static public function get_metadata( $order_id, $key ) {
			global $wpdb;
			$data = wp_cache_get( 'ppmetadata-' . $order_id, 'ppmetadata' );
			if ( false === $data || empty( $data ) ) {
				$table_name = $wpdb->prefix . 'woo_ppexpress_mx';
				$data = $wpdb->get_var($wpdb->prepare(
					"SELECT `data` FROM 
						`%s`
					WHERE
							`order_id` = %d
						AND 
							`key` = '%s'
					LIMIT 1",
					$table_name,
					(int) $order_id,
					$key
				));
				wp_cache_set( 'ppmetadata-' . $order_id, $data, 'ppmetadata' );
				WC_Paypal_Logger::obj()->debug( "get_metadata [order:{$order_id}]: [{$key}] | Result: " . print_r( $data, true ) );
				return $data ? json_decode( $data ) : false;
			} else {
				return $data;
			}
		}
		/**
		 * Create table in database.
		 */
		static public function check_database() {
			global $wpdb;
			$table_name = $wpdb->prefix . 'woo_ppexpress_mx';
			if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE '%s'", $table_name ) ) !== $table_name ) { // @codingStandardsIgnoreLine
				$charset_collate = $wpdb->get_charset_collate();
				$sql = "CREATE TABLE `{$table_name}` (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`order_id` bigint NOT NULL,
						`key` varchar(255) NOT NULL,
						`data` longtext NOT NULL,
						PRIMARY KEY (`id`),
						INDEX `idx_order_id` (`order_id`),
						INDEX `idx_key` (`key`)
					) {$charset_collate};";

				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $sql );
				WC_Paypal_Logger::obj()->debug( "Datebase `{$table_name}` created!" );
			}
		}
		/**
		 * Drop table of database.
		 */
		static public function uninstall_database() {
			global $wpdb;
			$table_name = $wpdb->prefix . 'woo_ppexpress_mx';
			$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS `%s`', $table_name ) ); // @codingStandardsIgnoreLine
			WC_Paypal_Logger::obj()->debug( "Datebase `{$table_name}` deleted!" );
		}
	}
endif;

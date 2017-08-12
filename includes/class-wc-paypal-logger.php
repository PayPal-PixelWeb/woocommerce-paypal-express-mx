<?php
/**
 * Logger for WooCommerce Plugin.
 *
 * @category  Class
 * @package   WooCommerce -> Paypal Express Checkout MX
 * @author    Kijam Lopez <info@kijam.com>
 * @license   Apache-2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once dirname( __FILE__ ) . '/../vendor/autoload.php';

if ( ! class_exists( 'WC_Paypal_Logger' ) ) :
	/**
	 * Logger for WooCommerce Plugin
	 */
	class WC_Paypal_Logger extends \Psr\Log\AbstractLogger {

		const PARANOID = 'PARANOID';
		const NORMAL   = 'NORMAL';
		const SILENT   = 'SILENT';

		/**
		 * Instance attribute
		 *
		 * @var object
		 */
		static private $logger = null;
		/**
		 * Log dir attribute
		 *
		 * @var string
		 */
		static private $log_dir = false;

		/**
		 * Log level attribute
		 *
		 * @var string
		 */
		static private $log_level = WC_Paypal_Logger::PARANOID;

		/**
		 * Set level of the log (SILENT; NORMAL; PARANOID)
		 *
		 * @param   string $new_level Choice SILENT; NORMAL; PARANOID ...
		 */
		static public function set_level( $new_level ) {
			if ( ! in_array( $new_level, array( WC_Paypal_Logger::PARANOID, WC_Paypal_Logger::NORMAL, WC_Paypal_Logger::SILENT ), true ) ) {
				return;
			}
			self::$log_level = $new_level;
		}

		/**
		 * Set directory of the log
		 *
		 * @param   string $new_dir Directory of log.
		 */
		static public function set_dir( $new_dir ) {
			// @codingStandardsIgnoreStart
			if ( ! @is_dir( $new_dir ) ) {
				@mkdir( $new_dir );
				if ( ! @is_dir( $new_dir ) ) {
					return false;
				} else {
					$fp = @fopen( $new_dir . '/index.php', 'a' );
					@fwrite( $fp, '<?php exit; ?>' );
					@fclose( $fp );
				}
			}
			// @codingStandardsIgnoreEnd
			self::$log_dir = $new_dir;
			return true;
		}

		/**
		 * Get unique instance of this class
		 */
		static public function get_instance() {
			if ( null === self::$logger ) {
				self::$logger = new self();
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
		 * Log implement.
		 *
		 * @param   string $level Level of log.
		 * @param   string $message Message to write in the log.
		 * @param   array  $context Context log.
		 *
		 * @return void
		 */
		public function log( $level, $message, array $context = array() ) {
			static $dir = null;
			if ( false === self::$log_dir || self::SILENT === self::$log_level ) {
				return;
			}
			if ( self::NORMAL === self::$log_level ) {
				if ( in_array( $level, array( \Psr\Log\LogLevel::NOTICE, \Psr\Log\LogLevel::INFO, \Psr\Log\LogLevel::DEBUG ), true ) ) {
					return;
				}
			}
			// @codingStandardsIgnoreStart
			if ( null === $dir ) {
				$dir = self::$log_dir . '/' . date( 'Y-m' );
				if ( ! @is_dir( $dir ) ) {
					@mkdir( $dir );
					if ( ! @is_dir( $dir ) ) {
						$dir = null;
						return;
					} else {
						$fp = @fopen( $dir . '/index.php', 'a' );
						@fwrite( $fp, '<?php exit; ?>' );
						@fclose( $fp );
					}
				}
			}
			$fp = @fopen( $dir . '/log-' . date( 'Y-m-d' ) . '.log', 'a' );
			@fwrite( $fp, "\n----- " . date( 'Y-m-d H:i:s' ) . " -----\n" );
			@fwrite( $fp, 'LEVEL: ' . $level . "\n" );
			@fwrite( $fp, 'URL: ' . ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}\n" );
			@fwrite( $fp, 'CONTEXT: ' . print_r( $context, true ) . "\n" );
			if ( self::$log_level == self::PARANOID ) {
				@fwrite( $fp, 'POST: ' . print_r( $_POST, true ) );
				@fwrite( $fp, 'GET: ' . print_r( $_GET, true ) );
				@fwrite( $fp, "TRACE:\n" );
				$e = new Exception();
				$trace = explode( "\n", $e->getTraceAsString() );
				$trace = array_reverse( $trace );
				array_shift( $trace ); // remove {main}
				array_pop( $trace ); // remove call to this method
				$length = count( $trace );
				for ( $i = 0 ; $i < $length ; $i++ ) {
					@fwrite( $fp, ($i + 1) . ')' . substr( $trace[ $i ], strpos( $trace[ $i ], ' ' ) ) . "\n" );
				}
			}
			@fwrite( $fp, "MESSAGE:\n" . print_r( $message, true ) . "\n" );
			@fclose( $fp );
			// @codingStandardsIgnoreEnd
		}
	}

endif;
